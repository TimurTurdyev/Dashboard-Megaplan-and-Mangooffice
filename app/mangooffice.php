<?php
//	ini_set( "display_errors", "1" );
//	ini_set( "display_startup_errors", "1" );
//	ini_set( 'error_reporting', E_ALL );

	class Mango
	{
		private $api_key  = ''; // указать свой ключ
		private $api_salt = ''; // указать свою подпись
		private $fields   = [];

		private $flag  = 0;
		private $response;
		private $users = [];
		private $stats = [];

		public function __construct()
		{

			$setting = json_decode(file_get_contents( './config/config_mangooffice.json' ));
			$this->dir = $setting;
			$this->api_key = $setting->api_key;
			$this->api_salt = $setting->api_salt;
		}

		public function get( $name )
		{
			if ( isset( $this->{$name} ) ) {
				return $this->{$name};
			}
			return null;
		}

		public function sortManagerTotal()
		{
			usort( $this->users, function( $a, $b ) {
				return -( round( $a['count_calls'] ) - round( $b['count_calls'] ) );
			} );
			//var_dump($this->manager);
		}

		public function setUsers( $fields = [] )
		{
			$this->requestCurl( 'https://app.mango-office.ru/vpbx/config/users/request', json_encode( $fields ) );
			$response = json_decode( $this->response );
			//var_dump($response);
			foreach( $response->users as $user ) {
				if ( $user->general->use_status == 1 ) {
					if ((int)$user->telephony->extension === 200) continue;
					$this->users[ 'extension:' . $user->telephony->extension ] = [
						'name'        => $user->general->name,
						'count_calls' => 0,
						'time_calls'  => 0,
						'time_answer' => 0,
					];
				}
			}
		}

		public function setStats( $date_from, $date_to = 'now', $fields = [ 'records', 'start', 'finish', 'answer', 'from_extension', 'from_number', 'to_extension', 'to_number', 'disconnect_reason', 'line_number', 'location', 'entry_id' ] )
		{
			$this->fields = $fields;
			$json = json_encode( array(
				'date_from' => strtotime( $date_from ),
				'date_to'   => strtotime( $date_to ),
				'from'      => array(
					'extension' => '',
					'number'    => '',
				),
				'to'        => array(
					'extension' => '',
					'number'    => '',
				),
				'fields'    => implode( ',', $this->fields ),
			) );
			$this->requestCurl( 'https://app.mango-office.ru/vpbx/stats/request', $json );
			$this->requestGetStats( $this->response );
		}

		private function requestGetStats( $response_key )
		{

			$this->flag += 1;
			$this->response = '';
			//теперь с ключом запрос на получение массива данных статистики вызовов
			$this->requestCurl( 'https://app.mango-office.ru/vpbx/stats/result', $response_key );

			if ( $this->response === '' && $this->flag < 5 ) {
				sleep( 5 );
				$this->requestGetStats( $response_key );
			} else {
				$this->parseCsvToArray( $this->response );
			}
		}

		private function parseCsvToArray( $data_csv )
		{
			$rows = explode( "\n", $data_csv );

			foreach( $rows as $key => $row ) {
				$value = str_getcsv( $row, ";", "'" );
				$date_start = 0;
				$date_finish = 0;
				$to_extension = '';
				$data = [];
				foreach( $value as $index => $col ) {

					if ( $this->fields[ $index ] == 'start' || $this->fields[ $index ] == 'finish' ) {
						if ( $this->fields[ $index ] == 'start' ) {
							$date_start = $col;
						}

						if ( $this->fields[ $index ] == 'finish' ) {
							$date_finish = $col;
						}

						$col = date( 'd H:i:s', $col );
					}

					if ( $this->fields[ $index ] == 'answer' ) {
						$col = ( $col - $date_start > 0 ? $col - $date_start : 0 );
						$data['talk_time'] = $date_finish - $date_start;
					}

					if ( $this->fields[ $index ] == 'from_extension' && $col !== '' ) {
						$to_extension = $col;
					}

					$data[ $this->fields[ $index ] ] = $col;
				}
				if ( $to_extension ) {
					if ( !isset( $this->stats[ $to_extension ] ) ) {
						$this->stats[ $to_extension ] = [];
					}

					if ( isset( $this->users[ 'extension:' . $to_extension ] ) ) {
						$this->users[ 'extension:' . $to_extension ]['count_calls'] += 1;
						// 'count_calls' => 0,
						// 'time_calls'  => 0,
						// 'time_answer' => 0,
						$this->users[ 'extension:' . $to_extension ]['time_calls'] += (int)$data['talk_time'];
						$this->users[ 'extension:' . $to_extension ]['time_answer'] += (int)$data['answer'];
					}
					array_push( $this->stats[ $to_extension ], $data );
				}
			}
		}

		private function requestCurl( $url, $json )
		{
			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
				'vpbx_api_key' => $this->api_key,
				'sign'         => hash( 'sha256', $this->api_key . $json . $this->api_salt ),
				'json'         => $json,
			) ) );
			$this->response = curl_exec( $ch );
			curl_close( $ch );
		}
	}

?>