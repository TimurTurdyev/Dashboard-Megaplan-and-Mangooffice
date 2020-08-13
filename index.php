<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

set_time_limit(0);

ini_set("memory_limit", "8096M");
ini_set("max_execution_time", 600000000);
ini_set("max_input_time", 600000000);

define('DIR_PROJECT', __DIR__ . '/');
define('DIR_CONFIG', __DIR__ . '/config/');
define('DIR_LOGS', __DIR__ . '/logs/');
define('DIR_DATA', __DIR__ . '/data/');

require_once 'app/megaplan.php';
require_once 'app/mangooffice.php';
//$megaplan = new Megaplan();
//print_r($megaplan->getDeals(100));
//die();
if (isset($_GET['type'])) {
    $json = [];
    if ($_GET['type'] == 'megaplan') {
        $megaplan = new Megaplan();
        $json['deals_to_manager'] = $megaplan->getFileDealsToManager();
        $manager_total = $megaplan->getFileManagerTotal();
        $json['manager_total'] = $manager_total ?? [];
        $json['manager_day_total'] = $manager_total ? $megaplan->getFileManagerDayTotal() : array();
        $json['change_file'] = $megaplan->getChangeFileTime();
    }

    if ($_GET['type'] == 'mangooffice') {
        $mango = new Mango();
        $mango->setUsers(['ext_fields' => ['general.use_status']]);
        $mango->setStats(date('Y-m-d') . ' 00:00:00');
        $mango->sortManagerTotal();
        $json['users'] = $mango->get('users');
    }
    //var_dump($json);die();
    header("Access-Control-Allow-Origin: *");
    header('Content-Type: application/json');
    echo json_encode($json);
    die();
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content='width=600, initial-scale=0.5, minimum-scale=0.2, maximum-scale=10'>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Данные по сделкам</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" crossorigin="anonymous">
    <style>
        :-webkit-full-screen {
            background: #343a40 !important
        }

        :-moz-full-screen {
            background: #343a40 !important
        }

        body {
            font-size: 170% !important;
            /*overflow: hidden;*/
        }

        .container-fluid {
            min-height: 100%;
        }

        #js_fullscreen {
            position: fixed;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        .manager_1000001, .manager_1000043 {
            display: none;
        }

        #table_B .manager_1000043 {
            display: contents;
        }

        span#change_time {
            color: #ffffff6b;
            font-size: 1rem;
            display: block;
            width: 100%;
            text-align: center;
            padding: 1rem 0;
            border-top: .1rem solid #607d8bab;
            margin-top: 1rem;
        }
    </style>
</head>
<?php $month_names = array(
    '01' => 'Январь',
    '02' => 'Февраль',
    '03' => 'Март',
    '04' => 'Апрель',
    '05' => 'Май',
    '06' => 'Июнь',
    '07' => 'Июль',
    '08' => 'Август',
    '09' => 'Сентябрь',
    '10' => 'Октябрь',
    '11' => 'Ноябрь',
    '12' => 'Декабрь',
); ?>
<body class="bg-dark">
<div class="container-fluid bg-dark">
    <div class="row pt-3">
        <div class="col-sm-4">
            <table class="table table-bordered table-dark table-striped table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col" colspan="2">Выполнение плана за <?php echo $month_names[date('m')]; ?></th>
                </tr>
                </thead>
                <tbody id="table_A"></tbody>
            </table>
            <table class="table table-bordered table-dark table-striped table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Необработанные заявки</th>
                    <th scope="col">Кол-во</th>
                </tr>
                </thead>
                <tbody id="table_B"></tbody>
            </table>
        </div>
        <div class="col-sm-4">
            <div class="d-flex flex-column" style="height: 100%;" id="js_column_2">
                <table class="table table-bordered table-dark table-striped table-hover table-sm">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Сумма оплат в день</th>
                        <th scope="col"><?php echo date('d.m.Y'); ?></th>
                    </tr>
                    </thead>
                    <tbody id="table_D"></tbody>
                </table>
                <div>
                    <canvas id="chart_month" width="100" height="70"></canvas>
                </div>
                <div><span id="change_time">Обновлен: 12.09.2020 12:56:12</span></div>
            </div>
        </div>
        <div class="col-sm-4">
            <table class="table table-bordered table-dark table-striped table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Исходящие звонки в день</th>
                    <th scope="col">Кол-во</th>
                </tr>
                </thead>
                <tbody id="table_C"></tbody>
            </table>
            <table class="table table-bordered table-dark table-striped table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col" colspan="2">KPD сотрудников</th>
                </tr>
                </thead>
                <tbody id="table_E"></tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-center" id="js_error"></div>
</div>
<!--<div class="container-fluid d-none">
    <div class="row">
        <div class="col-sm-8">
            <canvas id="managersChart" width="100" height="50"></canvas>
        </div>
        <div class="col-sm-4">
            <canvas id="managersCall" width="100" height="50"></canvas>
        </div>
    </div>
</div>-->
<button type="button" class="btn btn-light btn-sm" id="js_fullscreen" data-full="off">Развернуть</button>
<script src="js/Chart.min.js"></script>
<script src="js/axios.min.js"></script>
<script src="js/index.js?ver=12"></script>
<script>
    setTimeout(function () {
        document.body.style.zoom = 0.7;
        this.blur();
    }, 2000);
</script>
</body>
</html>