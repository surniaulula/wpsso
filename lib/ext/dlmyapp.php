<?php

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SuextDlmyapp' ) ) {

	class SuextDlmyapp {

		private $api_key;

		function __construct( $api_key = null ) {

			if ( $api_key ) {

				$this->api_key = $api_key;
			}
		}

		public function shorten( $long_url ) {

			static $local_cache = array();

			if ( ! empty( $local_cache[ $long_url ] ) ) {

				return $local_cache[ $long_url ];
			}

			$request_url = 'https://dlmy.app/api/?key=' . urlencode( $this->api_key ) . '&url=' . urlencode( $long_url );

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $request_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Accept: application/json',
			) );

			$response    = curl_exec( $ch );
			$curl_errnum = curl_errno( $ch );
			$curl_errmsg = curl_error( $ch );	// See https://curl.se/libcurl/c/libcurl-errors.html.
			$ssl_verify  = (int) curl_getinfo( $ch, CURLINFO_SSL_VERIFYRESULT );	// See https://www.php.net/manual/en/function.curl-getinfo.php.
			$http_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			curl_close( $ch );

			$http_success_codes = array( 200, 201 );

			try {

				if ( ! empty( $curl_errnum ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'DLMY.App', $curl_errnum . ' ' . $curl_errmsg );

					throw new WpssoErrorException( $except_msg );

				} elseif ( ! in_array( $http_code, $http_success_codes ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'DLMY.App', '' );

					throw new WpssoErrorException( $except_msg, $http_code );

				} else {

					$resp_data = json_decode( $response, $assoc = false );

					if ( null === $resp_data ) {

						$json_msg = sprintf( __( 'JSON response decode error code %d.', 'wpsso' ), json_last_error() );

						$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'DLMY.App', $json_msg );

						throw new WpssoErrorException( $except_msg );

					} elseif ( ! empty( $resp_data->short ) ) {

						return $local_cache[ $long_url ] = $resp_data->short;
					}
				}

			} catch ( WpssoErrorException $e ) {

				$e->errorMessage();
			}

			return false;
		}
	}
}
