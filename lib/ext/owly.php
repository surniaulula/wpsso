<?php
/**
 * PHP Client class to interface with Owly's REST-based API 
 * @see http://ow.ly/api-docs
 * 
 * Currently only supports the follow methods
 * shorten - Shorten a URL
 * 
 * Based on the Zend Ow.ly URL helper by Maxime Parmentier <maxime.parmentier@invokemedia.com>
 * Support can be logged at https://github.com/invokemedia/owly-api-php
 *
 * @version 	1.0.0
 * @author	Shih Oon Liong <shihoon.liong@invokemedia.com>
 * @created	24/07/2012
 * @copyright	Invoke Media / Biplane
 * @license	http://opensource.org/licenses/mit-license.php MIT
 * 
 * @example example.php
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SuextOwly' ) ) {

	class SuextOwly {

		private static $base_api_url = '//ow.ly/api/';

		private
			$apiKey = null,
			$version = "1.1",
			$protocol = 'http:',
			$apiCalls = array (
				'url-shorten'		=> 'url/shorten',
				'url-expand'		=> 'url/expand',
				'url-info'		=> 'url/info',
				'url-stats-click'	=> 'url/clickStats',
				'photo-upload'		=> 'photo/upload',
				'doc-upload'		=> 'doc/upload',
			);

		/**
		 * Constructor 
		 * @param array $options An array of options for the class. Possible options:
		 *	'key'     - The ow.ly API Key
		 *	'version' - (optional) The version number of the API to use.
		 *		By default, it will use version 1.1.
		 *	'protocol' - (optional) The protocol to use when talking to the API. 
		 *		By default it will use 'http:'.
		 *		To set it to secure, use 'https:'
		 * @return void
		 */
		public function __construct( $options ) {

			try {

				if ( ! array_key_exists( 'key', $options ) || empty( $options['key'] ) ) {

					$key_msg = __( 'API key missing.', 'wpsso' );

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Ow.ly', $key_msg );

					throw new WpssoErrorException( $except_msg );

				} else {

					$this->apiKey = $options['key'];

					if ( array_key_exists( 'version', $options ) ) {

						$this->version = $options[ 'version' ];
					}

					if ( array_key_exists( 'protocol', $options ) ) {

						$this->version = $options['protocol'];
					}
				}

			} catch ( WpssoErrorException $e ) {

				$e->errorMessage();
			}
		}

		/**
		 * Factory constructor
		 * @see SuextOwly::__constructor
		 * @param type $options
		 * @return SuextOwly 
		 */
		public static function factory( $options ) {

			$classname = __CLASS__;

			$instance = new $classname( $options );

			return $instance;
		}

		/**
		 * Get the API Method path to use
		 * @param type $apiCall
		 * @return type 
		 */
		private function getApiMethod( $apiCall ) {

			$url = $this->protocol . self::$base_api_url . $this->version.'/';

			if ( ! empty( $apiCall ) ) {

				$url .= $this->apiCalls[$apiCall];
			}

			return $url;
		}

		/**
		 * Given a full URL, returns an ow.ly short URL. 
		 * Currently the API only supports shortening a single URL per API call.
		 * 
		 * @see http://ow.ly/api-docs#shorten
		 * @param type $longUrl The URL to shorten. Must be a valid URL
		 * @return mixed 
		 * @throws WpssoErrorException 
		 */
		public function shorten( $longUrl ) {

			try {

				$apiUrl   = $this->getApiMethod( 'url-shorten' );
				$params   = array( 'longUrl' => $longUrl );
				$response = $this->send( $apiUrl, $params );

				if ( ! empty( $response->shortUrl ) ) {

					return $response->shortUrl;
				}

			} catch ( WpssoErrorException $e ) {

				$e->errorMessage();
			}

			return false;
		}

		/**
		 * Do an API call on Ow.ly
		 * @param type $apiUrl
		 * @return mixed
		 * @throws WpssoErrorException 
		 */
		private function send( $apiUrl, $args = array() ) {

			try {

				$args[ 'apiKey' ] = $this->apiKey;

				$apiUrl = $apiUrl . ( strpos( $apiUrl, '?' ) === false ? '?' : '&' ) . http_build_query( $args );

				$ch = curl_init();

				curl_setopt( $ch, CURLOPT_URL, $apiUrl );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );

				$response    = curl_exec( $ch );
				$curl_errnum = curl_errno( $ch );
				$curl_errmsg = curl_error( $ch );	// See https://curl.se/libcurl/c/libcurl-errors.html.
				$ssl_verify  = (int) curl_getinfo( $ch, CURLINFO_SSL_VERIFYRESULT );	// See https://www.php.net/manual/en/function.curl-getinfo.php.
				$http_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );

				curl_close( $ch );

				$http_success_codes = array( 200, 201 );

				if ( ! empty( $curl_errnum ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Ow.ly', $curl_errnum . ' ' . $curl_errmsg );

					throw new WpssoErrorException( $except_msg );

				} elseif ( ! in_array( $http_code, $http_success_codes ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Ow.ly', '' );

					throw new WpssoErrorException( $except_msg, $http_code );

				} else {

					$resp_data = json_decode( $response, $assoc = false );

					if ( null === $resp_data ) {

						$json_msg = sprintf( __( 'JSON response decode error code %d.', 'wpsso' ), json_last_error() );

						$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Ow.ly', $json_msg );

						throw new WpssoErrorException( $except_msg );

					} elseif ( ! empty( $resp_data->error ) ) {

						$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Ow.ly', $resp_data->error );

						throw new WpssoErrorException( $except_msg );

					} else {

						return $resp_data->results;
					}

				}

			} catch ( WpssoErrorException $e ) {

				$e->errorMessage();
			}

			return false;
		}
	}
}
