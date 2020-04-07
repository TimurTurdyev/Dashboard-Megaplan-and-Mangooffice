<?php
	define( 'DIR_CONFIG', 'config/' );

	require_once 'app/megaplan.php';
	require_once 'app/mangooffice.php';

	if ( isset( $_GET['type'] ) ) {
		$json = [];
		if ( $_GET['type'] == 'megaplan' ) {
			$megaplan = new Megaplan();
			$megaplan->setManagerData();
			$megaplan->sortManagerTotal();
			$json['manager_total'] = $megaplan->getManagerTotal();
			$json['error'] = $megaplan->getError();
		}

		if ( $_GET['type'] == 'mangooffice' ) {
			$mango = new Mango();
			$mango->setUsers( [ 'ext_fields' => [ 'general.use_status' ] ] );
			$mango->setStats( date( 'Y-m-d' ) . ' 00:00:00' );
			$mango->sortManagerTotal();
			$json['users'] = $mango->get( 'users' );
		}
		header( 'Content-Type: application/json' );
		echo json_encode( $json );
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
            font-size: 178% !important;
            overflow: hidden;
        }

        .container-fluid {
            height: 100vh;

        }

        #js_fullscreen {
            position: fixed;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        .manager_1000043 {
            display: none;
        }
    </style>
</head>
<body class="bg-dark">
<div class="container-fluid bg-dark">
    <div class="row pt-3">
        <div class="col-sm-4">
            <table class="table table-bordered table-dark table-striped table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Менеджер</th>
                    <th scope="col">Итого</th>
                </tr>
                </thead>
                <tbody id="table_A"></tbody>
            </table>
        </div>
        <div class="col-sm-4">
            <table class="table table-bordered table-dark table-striped table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Менеджер</th>
                    <th scope="col">Не обработанн</th>
                </tr>
                </thead>
                <tbody id="table_B"></tbody>
            </table>
        </div>
        <div class="col-sm-4">
            <table class="table table-bordered table-dark table-striped table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Менеджер</th>
                    <th scope="col">Исх.звон.день</th>
                </tr>
                </thead>
                <tbody id="table_C"></tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-center" id="js_error"></div>
</div>

<div class="container-fluid d-none">
    <div class="row">
        <div class="col-sm-8">
            <canvas id="managersChart" width="100" height="50"></canvas>
        </div>
        <div class="col-sm-4">
            <canvas id="managersCall" width="100" height="50"></canvas>
        </div>
    </div>
</div>
<button type="button" class="btn btn-light btn-sm" id="js_fullscreen" data-full="off">Развернуть</button>
<!--<script src="js/Chart.min.js"></script>-->
<script src="js/axios.min.js"></script>
<script src="js/index.js"></script>
</body>
</html>