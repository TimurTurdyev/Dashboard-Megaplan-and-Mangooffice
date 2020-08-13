<?php
// ini_set("display_errors","1");
// ini_set("display_startup_errors","1");
// ini_set('error_reporting', E_ALL);
if (!defined('DIR_PROJECT')) {
    die('access denied');
}
require_once DIR_PROJECT . 'megaplan_api_php/Request.php';

class Megaplan
{
    protected $test = 0;
    private $setting = [
        'MEGAPLAN_DOMAIN' => '',// В случае с коробочной версией, домен может отличаться от действительного. У меня например домен был просто rcmgp.com, а апи заработало только на www.rcmgp.com
        'MEGAPLAN_LOGIN' => '',
        'MEGAPLAN_PASSWORD' => '',
        'MEGAPLAN_SCHEMA' => '',// Ид схемы сделок
    ];
    private $request;
    private $blacklist = [1000037, 1000053, 1000016];
    private $blackStatus = [6];
    private $manager = [];
    private $error = [];
    private $total = 0;

    public function __construct()
    {
        $this->file_deals_to_manager = DIR_DATA . 'megaplan/deals_to_manager_' . date('Y-m') . '.json';
        $this->file_deals_to_manager_total = DIR_LOGS . 'megaplan/deals_to_manager_total.json';
        $this->file_deals = DIR_LOGS . 'megaplan/deals.json';
        $this->file_manager_total = DIR_LOGS . 'megaplan/manager_total.json';
        $this->file_manager_day_total = DIR_LOGS . 'megaplan/manager_day_total.json';

        if (!file_exists($this->file_deals_to_manager)) {
            file_put_contents($this->file_deals_to_manager, '[]');
        }

        if (!file_exists($this->file_deals_to_manager_total)) {
            file_put_contents($this->file_deals_to_manager_total, '[]');
        }

        if (!file_exists($this->file_deals)) {
            file_put_contents($this->file_deals, '[]');
        }

        if (!file_exists($this->file_manager_total)) {
            file_put_contents($this->file_manager_total, '[]');
        }

        if (!file_exists($this->file_manager_day_total)) {
            file_put_contents($this->file_manager_day_total, '[]');
        }

        $this->setting = json_decode(file_get_contents(DIR_CONFIG . 'config_megaplan.json'));

        if (file_exists(DIR_LOGS . 'megaplan_access.json')
            && $access = json_decode(file_get_contents(DIR_LOGS . 'megaplan_access.json'))) {

            $access = json_decode(file_get_contents(DIR_LOGS . 'megaplan_access.json'));
            // Вытаскиваем AccessId и SecretKey
            $accessId = $access->AccessId;
            $secretKey = $access->SecretKey;

        } else {

            $this->request = new SdfApi_Request('', '', $this->setting->MEGAPLAN_DOMAIN, true);
            $response = json_decode(
                $this->request->get(
                    '/BumsCommonApiV01/User/authorize.api',
                    array(
                        'Login' => $this->setting->MEGAPLAN_LOGIN,
                        'Password' => md5($this->setting->MEGAPLAN_PASSWORD),
                    )
                )
            );
            // Переподключаемся с полученными AccessId и SecretKey
            unset($this->request);
            // Получаем AccessId и SecretKey
            $accessId = $response->data->AccessId;
            $secretKey = $response->data->SecretKey;
            file_put_contents(
                DIR_LOGS . 'megaplan_access.json',
                json_encode(
                    array(
                        'AccessId' => $accessId,
                        'SecretKey' => $secretKey
                    )
                )
            );

        }

        $this->request = new SdfApi_Request($accessId, $secretKey, $this->setting->MEGAPLAN_DOMAIN, true);
    }

    public function getFileDealsToManager()
    {
        $managers = array();
        $deals = json_decode(file_get_contents($this->file_deals_to_manager_total), true);

        foreach ($deals as $id => $manager) {
            $all_deals = count($manager['all_deals']);
            $closed_deals = count($manager['closed_deals']);
            // 28.07.2020
            // Никита сказал вывести в кпд колонку только тех у кого больше или равно 20 закрытых сделок
            if ($closed_deals >= 20) {
                $managers[] = array(
                    'manager_id' => $id,
                    'name' => $manager['name'],
                    'all_deals' => $all_deals,
                    'closed_deals' => $closed_deals,
                    'total' => floor($closed_deals / $all_deals * 100)
                );
            }
        }

        usort($managers, function ($a, $b) {
            return -(round($a['total']) - round($b['total']));
        });
        return $managers;
    }

    public function getChangeFileTime()
    {
        if (file_exists($this->file_manager_total)) {
            return "Обновлен: " . date("F d Y H:i:s.", filectime($this->file_manager_total));
        }

        return '';
    }

    public function getFileManagerTotal()
    {
        return json_decode(file_get_contents($this->file_manager_total), true);
    }

    public function getFileManagerDayTotal()
    {
        return json_decode(file_get_contents($this->file_manager_day_total), true);
    }

    public function arrayEnumeration($limit = 40, $offset = 0)
    {
        if ($offset === 0) {
            file_put_contents($this->file_deals, '[]');
        }

        $response_data = $this->getDeals($limit, $offset * 40);

        if ($response_data['status']['code'] !== 'ok') {
            return array(
                'code' => $response_data['status']['code'],
                'action' => 'error',
                'error' => $response_data['status']['message'],
            );
        }

        if (count($response_data['data']['deals']) === 0) {

            file_put_contents($this->file_deals_to_manager_total,
                file_get_contents($this->file_deals_to_manager)
            );

            $this->dataParse(json_decode(file_get_contents($this->file_deals), true));

            $this->sortManagerTotal();

            file_put_contents($this->file_manager_total,
                json_encode($this->getManagerTotal(), true)
            );

            $this->sortManagerDayTotal();

            file_put_contents($this->file_manager_day_total,
                json_encode($this->getManagerDayTotal(), true)
            );

            return array(
                'action' => 'ready'
            );
        }

        $deals_to_manager = json_decode(file_get_contents($this->file_deals_to_manager), true);
        $data = [];
        $current_month = date('Y.m');
        $current_day = date('d');

        foreach ($response_data['data']['deals'] as $value) {

            if (empty($value['Manager']) || in_array($value['Manager']['Id'], $this->blacklist)) {
                continue;
            }

            if (!isset($deals_to_manager[$value['Manager']['Id']])) {
                $deals_to_manager[$value['Manager']['Id']] = array(
                    'name' => $value['Manager']['Name'],
                    'all_deals' => array(),
                    'closed_deals' => array()
                );
            }

            $deals_to_manager[$value['Manager']['Id']]['all_deals'][$value['Id']] = '' . $value['TimeCreated'] . ' | ' . $value['TimeUpdated'];

            if (!$value['IsPaid']) {
                $value['CustomFieldDataOplati'] = '';
                $value['current_day_sale'] = '';
                $data[$value['Id']] = $value;
                continue;
            }
            //file_put_contents(DIR_LOGS . 'test.txt', $value['Id'] . PHP_EOL, FILE_APPEND | LOCK_EX);
            $current_deal = $this->getDeal($value['Id']);

            if (empty($current_deal['data']) ||
                empty($current_deal['data']['deal']) ||
                empty($current_deal['data']['deal']['Category1000046CustomFieldDataOplati']) ||
                (date('Y.m', strtotime($current_deal['data']['deal']['Category1000046CustomFieldDataOplati'])) !== $current_month)
            ) {
                $value['CustomFieldDataOplati'] = '';
                $value['current_day_sale'] = '';
            } else {
                $deals_to_manager[$value['Manager']['Id']]['closed_deals'][$value['Id']] = '' . $value['TimeCreated'] . ' | ' . $value['TimeUpdated'];
                $value['CustomFieldDataOplati'] = $current_deal['data']['deal']['Category1000046CustomFieldDataOplati'];
                $value['current_day_sale'] = (date('d', strtotime($current_deal['data']['deal']['Category1000046CustomFieldDataOplati'])) === $current_day);
            }

            $data[$value['Id']] = $value;
        }

        file_put_contents($this->file_deals_to_manager,
            json_encode(
                $deals_to_manager
            ),
            LOCK_EX
        );

        if (count($data) > 0) {
            file_put_contents($this->file_deals,
                json_encode(
                    array_merge(
                        json_decode(
                            file_get_contents($this->file_deals), true),
                        $data
                    )
                ),
                LOCK_EX
            );
        }

        return array(
            'current_page' => $offset,
            'action' => 'next'
        );
    }

    public function getDeals($limit = 100, $offset = 0)
    {
        return json_decode(
            $this->request->get('/BumsTradeApiV01/Deal/list.api',
                array(
                    'FilterFields' => array(
                        'or' => array(
                            'TimeCreated' => [
                                'greaterOrEqual' => date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1)),
                            ],
                            'TimeUpdated' => [
                                'greaterOrEqual' => date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1)),
                            ],
                        )
                    ),
                    'RequestedFields' => array('TimeCreated', 'TimeUpdated', 'FinalPrice', 'Manager', 'Status', 'IsPaid', 'Program', 'Statuses'),
                    'ExtraFields' => array('Shipping'),
                    'Limit' => $limit,
                    'Offset' => $offset,
                )
            ), 1);
    }

    protected function dataParse($data)
    {
        foreach ($data as $index => $value) {
            if (in_array($value['Manager']['Id'], $this->blacklist)) {
                continue;
            }
            $this->setDataManager($value['Manager']['Id'], $value);
        }
    }

    protected function setDataManager($manager_id, $deal)
    {
        if (!isset($this->manager['manager_id.' . $manager_id])) {
            $this->manager['manager_id.' . $manager_id]['manager_id'] = $manager_id;
            $this->manager['manager_id.' . $manager_id]['name'] = $deal['Manager']['Name'];
            $this->manager['manager_id.' . $manager_id]['total'] = 0;
            $this->manager['manager_id.' . $manager_id]['current_day_total'] = 0;
            $this->manager['manager_id.' . $manager_id]['deal_count'] = 0;
            $this->manager['manager_id.' . $manager_id]['not_processed'] = 0;
        }

        if ((int)$deal['Status']['Id'] == 2) {
            $this->manager['manager_id.' . $manager_id]['not_processed'] += 1;
        }
        //if ($deal['Status']['Id'] !== 13) return;
        if ($deal['CustomFieldDataOplati']) {
            $this->manager['manager_id.' . $manager_id]['total'] += (float)$deal['FinalPrice']['Value'];
            $this->manager['manager_id.' . $manager_id]['current_day_total'] += $deal['current_day_sale'] ? (float)$deal['FinalPrice']['Value'] : 0;
            $this->manager['manager_id.' . $manager_id]['deal_count'] += 1;
            $this->manager['manager_id.' . $manager_id]['status_id.' . $deal['Status']['Id']]['status_name'] = $deal['Status']['Name'];
            $this->manager['manager_id.' . $manager_id]['status_id.' . $deal['Status']['Id']][] = [
                'created' => $deal['TimeCreated'],
                'updated' => $deal['TimeUpdated'],
                'price' => $deal['FinalPrice']['Value'],
                'currency' => $deal['FinalPrice']['CurrencyAbbreviation'],
                'deal_id' => $deal['Id'],
                'deal_number' => $deal['Name'],
            ];
        }

    }

    public function sortManagerTotal()
    {
        usort($this->manager, function ($a, $b) {
            return -(round($a['total']) - round($b['total']));
        });
    }

    public function getManagerTotal()
    {
        $data = [];
        foreach ($this->manager as $key => $manager) {
            $data[] = [
                'manager_id' => $manager['manager_id'],
                'name' => $manager['name'],
                'total' => $manager['total'],
                'deal_count' => $manager['deal_count'],
                'not_processed' => $manager['not_processed'],
            ];
        }

        return $data;
    }

    public function sortManagerDayTotal()
    {
        usort($this->manager, function ($a, $b) {
            return -(round($a['current_day_total']) - round($b['current_day_total']));
        });
    }

    public function getManagerDayTotal()
    {
        $data = [];
        foreach ($this->manager as $key => $manager) {
            $data[] = [
                'manager_id' => $manager['manager_id'],
                'name' => $manager['name'],
                'current_day_total' => $manager['current_day_total'],
            ];
        }

        return $data;
    }

    protected function getDeal($id)
    {
        return json_decode($this->request->get('/BumsTradeApiV01/Deal/card.api', array(
            'Id' => $id,
        )), 1);
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getError()
    {
        if ($this->error) {
            return '<div class="alert alert-warning">' . $this->error['message'] . '</div>';
        }
        return '';
    }

    public function setManagerData()
    {
        $limit = 0;
        while ($limit += 100) {
            $response_data = $this->getDeals($limit);
            if ($response_data['status']['code'] == 'ok') {
                if (count($response_data['data']['deals']) > 0) {
                    $this->dataParse($response_data['data']['deals']);
                    continue;
                }
            } else {
                $this->error['message'] = $response_data['status']['message'];
            }

            break;
        }
    }

    public function saveManagerData()
    {
        $offset = 0;
        $data = [];
        while ($offset += 99) {
            $response_data = $this->getDeals(100, $offset);
            if ($response_data['status']['code'] == 'ok') {
                if (count($response_data['data']['deals']) > 0) {
                    foreach ($response_data['data']['deals'] as $index => $value) {
                        if (in_array($value['Manager']['Id'], $this->blacklist) || !$value['IsPaid']) {
                            continue;
                        }
                        $data[] = $value;
                    }
                    continue;
                }
            } else {
                $this->error['message'] = $response_data['status']['message'];
            }
            break;
        }

        file_put_contents($this->file_deals, json_encode($data, 1));
    }

    public function getManager()
    {
        return $this->manager;
    }

    protected function getAccounts($id)
    {
        return json_decode($this->request->get('/BumsInvoiceApiV01/Invoice/card.api', array(
            'Id' => $id,
        )), 1);
    }
}

?>