<?php
//ini_set("display_errors", "1");
//ini_set("display_startup_errors", "1");
//ini_set('error_reporting', E_ALL);
//ini_set("memory_limit", "8096M");
//ini_set("max_execution_time", 600000000);
//ini_set("max_input_time", 600000000);
define('DIR_PROJECT', realpath(__DIR__ . '/..') . '/');
define('DIR_CONFIG', DIR_PROJECT . 'config/');
define('DIR_LOGS', DIR_PROJECT . 'logs/');
define('DIR_DATA', DIR_PROJECT . 'data/');

$file_blocked = DIR_LOGS . 'megaplan/block.txt';
$file_log = DIR_LOGS . 'megaplan/log.log';
$sapi_type = php_sapi_name();

//$STDOUT=fopen("/tmp/php_stdout.txt","a");
//print_r(DIR_PROJECT);
//fclose($STDOUT);
//die();

if ($sapi_type === 'cli') {

    require_once DIR_PROJECT . 'app/megaplan.php';
    require_once DIR_PROJECT . 'app/mangooffice.php';

    if (file_exists($file_blocked)) {
        $log = date('Y-m-d H:i:s') . ' ' . print_r('file to blocked', true) . PHP_EOL;
        $date_expire = date("Y-m-d H:i", filectime($file_blocked));
        $date = new DateTime($date_expire);
        $now = new DateTime();
        $interval = $date->diff($now);

        if ($interval->h > 1) {
            unlink($file_blocked);
        }

        file_put_contents(
            $file_log,
            $log,
            FILE_APPEND | LOCK_EX);
        die();
    }

    file_put_contents($file_blocked, '');

    try {
        $page = 0;
        $megaplan = new Megaplan();
        while (true) {
            $result = $megaplan->arrayEnumeration(40, $page);
            $log = date('Y-m-d H:i:s') . ' ' . print_r($result, true);
            file_put_contents(
                $file_log,
                $log,
                FILE_APPEND | LOCK_EX);

            if ($result['action'] !== 'next') {
                break;
            }
            $page++;
            sleep(30);
        }
        if (file_exists($file_blocked)) {
            unlink($file_blocked);
        }
    } catch (Exception $error) {
        $log = date('Y-m-d H:i:s') . ' ' . print_r($error[0], true);
        file_put_contents(
            $file_log,
            $log,
            FILE_APPEND | LOCK_EX);
        if (file_exists($file_blocked)) {
            unlink($file_blocked);
        }
    }
} else {
    $log = date('Y-m-d H:i:s') . ' ' . print_r('access denied', true);
    file_put_contents(
        $file_log,
        $log,
        FILE_APPEND | LOCK_EX);
}
?>