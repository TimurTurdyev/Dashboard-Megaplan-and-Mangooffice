<?php
	// ini_set("display_errors","1");
	// ini_set("display_startup_errors","1");
	// ini_set('error_reporting', E_ALL);

	require_once 'megaplan_api_php/Request.php';

	class Megaplan
	{

		private $setting      = [
			'MEGAPLAN_DOMAIN'   => '',// В случае с коробочной версией, домен может отличаться от действительного. У меня например домен был просто rcmgp.com, а апи заработало только на www.rcmgp.com
			'MEGAPLAN_LOGIN'    => '',
			'MEGAPLAN_PASSWORD' => '',
			'MEGAPLAN_SCHEMA'   => '',// Ид схемы сделок
		];
		private $request;
		private $blacklist   = [ 1000037, 1000053, 1000016 ];
		private $blackStatus = [ 6 ];
		private $manager     = [];
		private $error       = [];

		public function __construct()
		{
			$this->setting = json_decode( file_get_contents( DIR_CONFIG  . 'config_megaplan.json' ) );

			$this->request = new SdfApi_Request( '', '', $this->setting->MEGAPLAN_DOMAIN, true );
			$response = json_decode(
				$this->request->get(
					'/BumsCommonApiV01/User/authorize.api',
					array(
						'Login'    => $this->setting->MEGAPLAN_LOGIN,
						'Password' => md5( $this->setting->MEGAPLAN_PASSWORD ),
					)
				)
			);

			// Получаем AccessId и SecretKey
			$accessId = $response->data->AccessId;
			$secretKey = $response->data->SecretKey;

			// Переподключаемся с полученными AccessId и SecretKey
			unset( $this->request );
			$this->request = new SdfApi_Request( $accessId, $secretKey, $this->setting->MEGAPLAN_DOMAIN, true );
		}

		public function getDeals( $offset = 0 )
		{

			return json_decode(
				$this->request->get( '/BumsTradeApiV01/Deal/list.api',
					array(
						'FilterFields'    => array(
							'TimeUpdated' => [
								'greaterOrEqual' => date( 'Y-m-d H:i:s', mktime( 0, 0, 0, date( 'm' ), 1 ) ),
							],
							//'IsPaid'      => true,
						),
						'RequestedFields' => array( 'TimeCreated', 'TimeUpdated', 'FinalPrice', 'Manager', 'Status', 'IsPaid' ),
						'Limit'           => 100,
						'Offset'          => $offset,
					)
				), 1 );
		}

		public function setManagerData()
		{
			$limit = 0;
			while( $limit += 100 ) {
				$response_data = $this->getDeals( $limit );
				//var_dump( $response_data );
				if ( $response_data['status']['code'] == 'ok' ) {
					if ( count( $response_data['data']['deals'] ) > 0 ) {
						$this->dataParse( $response_data['data']['deals'] );
						continue;
					}
				} else {
					$this->error['message'] = $response_data['status']['message'];
				}

				break;
			}
		}

		protected function dataParse( $data )
		{
			foreach( $data as $value ) {
				if ( in_array( $value['Manager']['Id'], $this->blacklist ) ) continue;
				$this->setDataManager( $value['Manager']['Id'], $value );
			}
		}

		protected function setDataManager( $manager_id, $deal )
		{
			if ( !isset( $this->manager[ 'manager_id.' . $manager_id ] ) ) {
				$this->manager[ 'manager_id.' . $manager_id ]['manager_id'] = $manager_id;
				$this->manager[ 'manager_id.' . $manager_id ]['name'] = $deal['Manager']['Name'];
				$this->manager[ 'manager_id.' . $manager_id ]['total'] = 0;
				$this->manager[ 'manager_id.' . $manager_id ]['deal_count'] = 0;
				$this->manager[ 'manager_id.' . $manager_id ]['not_processed'] = 0;
			}

			if ( $deal['IsPaid'] ) {
				$this->manager[ 'manager_id.' . $manager_id ]['total'] += (float)$deal['FinalPrice']['Value'];
				$this->manager[ 'manager_id.' . $manager_id ]['deal_count'] += 1;
				$this->manager[ 'manager_id.' . $manager_id ][ 'status_id.' . $deal['Status']['Id'] ]['status_name'] = $deal['Status']['Name'];
				$this->manager[ 'manager_id.' . $manager_id ][ 'status_id.' . $deal['Status']['Id'] ][] = [
					'created'     => $deal['TimeCreated'],
					'updated'     => $deal['TimeUpdated'],
					'price'       => $deal['FinalPrice']['Value'],
					'currency'    => $deal['FinalPrice']['CurrencyAbbreviation'],
					'deal_number' => $deal['Name'],
				];
			}

			if ( (int)$deal['Status']['Id'] == 2 ) {
				$this->manager[ 'manager_id.' . $manager_id ]['not_processed'] += 1;
			}
		}

		public function sortManagerTotal()
		{
			usort( $this->manager, function( $a, $b ) {
				return -( round( $a['total'] ) - round( $b['total'] ) );
			} );
		}

		public function getManager()
		{
			return $this->manager;
		}

		public function getManagerTotal()
		{
			$data = [];
			foreach( $this->manager as $key => $manager ) {
				$data[] = [
					'manager_id'    => $manager['manager_id'],
					'name'          => $manager['name'],
					'total'         => $manager['total'],
					'deal_count'    => $manager['deal_count'],
					'not_processed' => $manager['not_processed'],
				];
			}

			return $data;
		}

		public function getError()
		{
			if ( $this->error ) {
				return '<div class="alert alert-warning">' . $this->error['message'] . '</div>';
			}
			return '';
		}
	}

?>