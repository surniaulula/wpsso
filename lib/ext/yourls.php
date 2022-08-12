<?php
/**
 * API client for yourls.org
 *
 * @author Šarūnas Dubinskas <s.dubinskas@evp.lt>
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SuextYourls' ) ) {

	class SuextYourls {

	    /**
	     * Available actions
	     */
	    const ACTION_SHORTURL  = 'shorturl';
	    const ACTION_URL_STATS = 'url-stats';
	    const ACTION_EXPAND    = 'expand';

	    /**
	     * @var string
	     */
	    protected $apiUrl;

	    /**
	     * @var null|string
	     */
	    protected $username;

	    /**
	     * @var null|string
	     */
	    protected $password;

	    /**
	     * @var null|string
	     */
	    protected $token;

	    /**
	     * @var string
	     */
	    private $lastResponse;

	    /**
	     * Class constructor
	     *
	     * @param string $apiUrl
	     * @param null|string $username
	     * @param null|string $password
	     * @param null|string $token
	     */
		public function __construct( $apiUrl, $username = null, $password = null, $token = null ) {

			$this->apiUrl   = $apiUrl;
			$this->username = $username;
			$this->password = $password;
			$this->token    = $token;
		}

	    /**
	     * Get short URL for a URL
	     *
	     * @param string $url
	     * @param null|string $keyword
	     *
	     * @return string
	     */
		public function shorten( $url, $keyword = null ) {

			$resp_data = $this->call( self::ACTION_SHORTURL, array( 'url' => $url, 'keyword' => $keyword ) );

			return isset( $resp_data[ 'shorturl' ] ) ? $resp_data[ 'shorturl' ] : false;
		}

	    /**
	     * Get stats about short URL
	     *
	     * @param string $shortUrl
	     *
	     * @return array
	     */
		public function getUrlStats( $shortUrl ) {

			return $this->call( self::ACTION_URL_STATS, array( 'shorturl' => $shortUrl ) );
		}

	    /**
	     * Get long URL of a short URL
	     *
	     * @param string $shortUrl
	     *
	     * @return string
	     */
		public function expand( $shortUrl ) {

			$resp_data = $this->call( self::ACTION_EXPAND, array( 'shorturl' => $shortUrl ) );

			return isset( $resp_data[ 'longurl' ] ) ? $resp_data[ 'longurl' ] : false;
		}

	    /**
	     * Returns last raw response from API
	     *
	     * @return string
	     */
		public function getLastResponse() {

			return $this->lastResponse;
		}

	    /**
	     * Calls API action with specified params
	     *
	     * @param string $action
	     * @param array $params
	     *
	     * @return array
	     */
		protected function call( $action, $params = array() ) {

		        $params['action'] = $action;

		        if ( $this->username ) {

				$params[ 'username' ] = $this->username;
				$params[ 'password' ] = $this->password;

			} else {

				$params[ 'timestamp' ] = time();
				$params[ 'signature' ] = md5( $this->token.$params['timestamp'] );
			}

			$params[ 'format' ] = 'json';

			$url = $this->apiUrl . ( strpos( $this->apiUrl, '?' ) === false ? '?' : '&' ) . http_build_query( $params );

		        $ch = curl_init();

		        curl_setopt( $ch, CURLOPT_URL, $url );
		        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );

			/**
			 * When negotiating a TLS or SSL connection, the server sends a
			 * certificate indicating its identity. Curl verifies whether the
			 * certificate is authentic, i.e. that you can trust that the server is
			 * who the certificate says it is. This trust is based on a chain of
			 * digital signatures, rooted in certification authority (CA)
			 * certificates you supply. curl uses a default bundle of CA
			 * certificates (the path for that is determined at build time) and you
			 * can specify alternate certificates with the CURLOPT_CAINFO option or
			 * the CURLOPT_CAPATH option. 
			 *
			 * See https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYPEER.html
			 */
		        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );

			/**
			 * When CURLOPT_SSL_VERIFYHOST is 2, that certificate must indicate
			 * that the server is the server to which you meant to connect, or the
			 * connection fails. Simply put, it means it has to have the same name
			 * in the certificate as is in the URL you operate against.
			 *
			 * See https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html
			 */
		        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );

			/**
			 * Define and disable the "Expect: 100-continue" header.
			 */
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );

			if ( ! ini_get('safe_mode') && ! ini_get('open_basedir') ) {

				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
				curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
			}

		        $response    = $this->lastResponse = curl_exec( $ch );
			$curl_errnum = curl_errno( $ch );
			$curl_errmsg = curl_error( $ch );	// See https://curl.se/libcurl/c/libcurl-errors.html.
			$ssl_verify  = (int) curl_getinfo( $ch, CURLINFO_SSL_VERIFYRESULT );	// See https://www.php.net/manual/en/function.curl-getinfo.php.
			$http_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			curl_close( $ch );

			$http_success_codes = array( 200, 201 );

			try {

				if ( ! empty( $curl_errnum ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'YOURLS', $curl_errnum . ' ' . $curl_errmsg );

					throw new WpssoErrorException( $except_msg );

				} elseif ( ! in_array( $http_code, $http_success_codes ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'YOURLS', '' );

					throw new WpssoErrorException( $except_msg, $http_code );

				} else {

					$resp_data = json_decode( $response, $assoc = true );

					if ( null === $resp_data ) {

						$json_msg = sprintf( __( 'JSON response decode error code %d.', 'wpsso' ), json_last_error() );

						$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'YOURLS', $json_msg );

						throw new WpssoErrorException( $except_msg );

					} else {

						return $resp_data;
					}
				}

			} catch ( WpssoErrorException $e ) {

				return $e->errorMessage();
			}

		        return false;
		}
	}
}
