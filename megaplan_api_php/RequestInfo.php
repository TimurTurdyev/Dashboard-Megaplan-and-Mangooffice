<?php

//{{{ SdfApi_RequestInfo

	/**
	 * Объект-контейнер параметров запроса
	 *
	 * @since 01.04.2010 12:25:00
	 * @author megaplan
	 */
	class SdfApi_RequestInfo
	{

		/** Список параметров @var array */
		protected $params;
		/** Список поддерживаемых HTTP-методов @var array */
		protected static $supportingMethods = array( 'GET', 'POST', 'PUT', 'DELETE' );
		/** Список принимаемых HTTP-заголовков @var array */
		protected static $acceptedHeaders = array( 'Date', 'Content-Type', 'Content-MD5', 'Post-Fields' );
//-----------------------------------------------------------------------------

//{{{ create
		/**
		 * Создает и возвращает объект
		 * @param string $Method Метод запроса
		 * @param string $Host Хост мегаплана
		 * @param string $Uri URI запроса
		 * @param array $Headers Заголовки запроса
		 * @return SdfApi_RequestInfo
		 * @author megaplan
		 * @since 01.04.2010 13:46
		 */
		public static function create( $Method, $Host, $Uri, array $Headers )
		{
			$Method = mb_strtoupper( $Method );
			if ( !in_array( $Method, self::$supportingMethods ) ) {
				throw new Exception( "Unsupporting HTTP-Method '$Method'" );
			}

			$params = array(
				'Method' => $Method,
				'Host'   => $Host,
				'Uri'    => $Uri,
			);

			// фильтруем заголовки
			$validHeaders = array_intersect_key( $Headers, array_flip( self::$acceptedHeaders ) );
			$params = array_merge( $params, $validHeaders );

			$request = new self( $params );

			return $request;
		}
//===========================================================================}}}
//{{{ __construct
		/**
		 * Создает объект
		 * @param array $Params Параметры запроса
		 * @author megaplan
		 * @since 01.04.2010 13:59
		 */
		protected function __construct( array $Params )
		{
			$this->params = $Params;
		}
//===========================================================================}}}
//{{{ __get
		/**
		 * Возвращает параметры запроса
		 * @param string $Name
		 * @return string
		 * @since 01.04.2010 14:26
		 * @author megaplan
		 */
		public function __get( $Name )
		{
			$Name = preg_replace( "/([a-z]{1})([A-Z]{1})/u", '$1-$2', $Name );

			if ( !empty( $this->params[ $Name ] ) ) {
				return $this->params[ $Name ];
			} else {
				return '';
			}
		}
//===========================================================================}}}

	}
	/*============================================================================*
	 *  END OF SdfApi_RequestInfo                                                 *
	 *=========================================================================}}}*/