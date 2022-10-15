<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2019-2022 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomBitly' ) ) {

	class SucomBitly {

		private $access_token         = '';
		private $domain               = '';
		private $group_guid           = '';
		private $curl_connect_timeout = 5;	// The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
		private $curl_timeout         = 15;	// The maximum number of seconds to allow cURL functions to execute.
		private $curl_max_redirs      = 10;	// The maximum amount of HTTP redirections to follow.

		private static $groups_api_url  = 'https://api-ssl.bitly.com/v4/groups';
		private static $shorten_api_url = 'https://api-ssl.bitly.com/v4/shorten';

		public function __construct( $access_token, $domain = '', $group_name = '' ) {

			$this->access_token = $access_token;
			$this->domain       = $domain;
			$this->group_guid   = $this->get_group_guid( $group_name );
		}

		public function get_group_guid( $group_name = '' ) {

			if ( '' === $group_name ) {

				return '';
			}

			$resp_data = $this->api_call( self::$groups_api_url );

			try {

				if ( is_array( $resp_data->groups ) ) {

					foreach ( $resp_data->groups as $group ) {

						if ( ! empty( $group->guid ) ) {	// Just in case.

							if ( $group->name === $group_name ) {

								return $group->guid;	// Return guid for the group name.
							}
						}
					}

					$group_msg = sprintf( __( 'Group name "%s" not found in the Bitly API response data.', 'wpsso' ), $group_name );

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Bitly', $group_msg );

					throw new WpssoErrorException( $except_msg );

				} else {

					$group_msg = __( 'Groups property missing from the Bitly API response data.', 'wpsso' );

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Bitly', $group_msg );

					throw new WpssoErrorException( $except_msg );
				}

			} catch ( WpssoErrorException $e ) {

				return $e->errorMessage( $ret = false );
			}

			return false;
		}

		public function shorten( $long_url ) {

			if ( empty( $long_url ) ) {

				return false;	// Nothing to shorten.
			}

			$api_data = array(
				'long_url' => (string) $long_url,
			);

			if ( ! empty( $this->domain ) ) {

				$api_data[ 'domain' ] = (string) $this->domain;
			}

			if ( ! empty( $this->group_guid ) ) {

				$api_data[ 'group_guid' ] = (string) $this->group_guid;
			}

			$resp_data = $this->api_call( self::$shorten_api_url, $api_data );

			try {

				if ( empty( $resp_data->link ) ) {

					$url_msg = __( 'Link property value is empty.', 'wpsso' );

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Bitly', $url_msg );

					throw new WpssoErrorException( $except_msg );

				} else {

					return $resp_data->link;
				}

			} catch ( WpssoErrorException $e ) {

				return $e->errorMessage();
			}

			return false;
		}

		private function api_call( $api_url, $api_data = array() ) {

			$api_data_enc = json_encode( $api_data );

			$ch = curl_init();

			$curl_headers = array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $this->access_token,
				'Accept: application/json',
				'Expect:',
			);

			if ( ! empty( $api_data ) ) {

				$curl_headers[] = 'Content-Length: ' . strlen( $api_data_enc );

				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $api_data_enc );
			}

			curl_setopt( $ch, CURLOPT_URL, $api_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $this->curl_timeout );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $curl_headers );

			$response    = curl_exec( $ch );
			$curl_errnum = curl_errno( $ch );
			$curl_errmsg = curl_error( $ch );	// See https://curl.se/libcurl/c/libcurl-errors.html.
			$ssl_verify  = (int) curl_getinfo( $ch, CURLINFO_SSL_VERIFYRESULT );	// See https://www.php.net/manual/en/function.curl-getinfo.php.
			$http_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			curl_close( $ch );

			$http_success_codes = array( 200, 201 );

			try {

				if ( ! empty( $curl_errnum ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Bitly', $curl_errnum . ' ' . $curl_errmsg );

					throw new WpssoErrorException( $except_msg );

				} elseif ( ! in_array( $http_code, $http_success_codes ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Bitly', '' );

					throw new WpssoErrorException( $except_msg, $http_code );

				} else {

					$resp_data = json_decode( $response, $assoc = false );

					if ( null === $resp_data ) {

						$json_msg = sprintf( __( 'JSON response decode error code %d.', 'wpsso' ), json_last_error() );

						$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'Bitly', $json_msg );

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
