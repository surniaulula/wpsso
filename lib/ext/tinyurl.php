<?php
/**
 * TinyUrl class
 *
 * This source file can be used to communicate with TinyURL.com (http://tinyurl.com)
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to php-tinyurl-bugs[at]verkoyen[dot]eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * Changelog since 1.0.0
 * - corrected some documentation
 * - wrote some explanation for the method-parameters
 *
 * License
 * Copyright (c) 2009, Tijs Verkoyen. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author		Tijs Verkoyen <php-tinyurl@verkoyen.eu>
 * @version		1.0.1
 *
 * @copyright	Copyright (c) 2008, Tijs Verkoyen. All rights reserved.
 * @license		BSD License
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SuextTinyUrl' ) ) {

	class SuextTinyUrl {

		// internal constant to enable/disable debugging
		const DEBUG = false;

		// url for the twitter-api
		const API_URL = 'http://tinyurl.com';

		// port for the tinyUrl-API
		const API_PORT = 80;

		// current version
		const VERSION = '1.0.1';


		/**
		 * The timeout
		 *
		 * @var	int
		 */
		private $timeOut = 60;


		/**
		 * The user agent
		 *
		 * @var	string
		 */
		private $userAgent;


		/**
		 * Default constructor
		 *
		 * @return	void
		 */
		public function __construct()
		{
			// nothing to do
		}


		/**
		 * Make the call
		 *
		 * @return	string
		 * @param	string $url
		 * @param	array[optional] $aParameters
		 */
		private function doCall( $url, $aParameters = array() ) {

			// redefine
			$url = (string) $url;
			$aParameters = (array) $aParameters;

			// rebuild url if we don't use post
			if( ! empty( $aParameters ) ) {

				// init var
				$queryString = '';

				// loop parameters and add them to the queryString
				foreach( $aParameters as $key => $value) {

					$queryString .= '&'. $key .'='. urlencode(utf8_encode($value));
				}

				// cleanup querystring
				$queryString = trim($queryString, '&');

				// append to url
				$url .= (strpos($url, '?') === false ? '?' : '&').$queryString;
			}

			// set options
			$options[ CURLOPT_URL ]            = $url;
			$options[ CURLOPT_PORT ]           = self::API_PORT;
			$options[ CURLOPT_USERAGENT ]      = $this->getUserAgent();
			$options[ CURLOPT_FOLLOWLOCATION ] = 1;
			$options[ CURLOPT_RETURNTRANSFER ] = 1;
			$options[ CURLOPT_TIMEOUT ]        = (int) $this->getTimeOut();

			$ch = curl_init();

			curl_setopt_array( $ch, $options );

			$response    = curl_exec( $ch );
			$curl_errnum = curl_errno( $ch );
			$curl_errmsg = curl_error( $ch );	// See https://curl.se/libcurl/c/libcurl-errors.html.
			$ssl_verify  = (int) curl_getinfo( $ch, CURLINFO_SSL_VERIFYRESULT );	// See https://www.php.net/manual/en/function.curl-getinfo.php.
			$http_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			curl_close( $ch );

			$http_success_codes = array( 200, 201 );

			try {

				if ( ! empty( $curl_errnum ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'TinyURL', $curl_errnum . ' ' . $curl_errmsg );

					throw new WpssoErrorException( $except_msg );

				} elseif ( ! in_array( $http_code, $http_success_codes ) ) {

					$except_msg = sprintf( __( '%1$s shortener API error: %2$s', 'wpsso' ), 'TinyURL', '' );

					throw new WpssoErrorException( $except_msg, $http_code );

				} else {

					return $response;
				}

			} catch ( WpssoErrorException $e ) {

				$e->errorMessage();
			}

			return false;
		}


		/**
		 * Get the timeout that will be used
		 *
		 * @return	int
		 */
		public function getTimeOut()
		{
			return (int) $this->timeOut;
		}


		/**
		 * Get the useragent that will be used. Our version will be prepended to yours.
		 * It will look like: "PHP Tinyurl/<version> <your-user-agent>"
		 *
		 * @return	string
		 */
		public function getUserAgent()
		{
			return (string) 'PHP TinyUrl/' . self::VERSION . ' ' . $this->userAgent;
		}


		/**
		 * Set the timeout
		 * After this time the request will stop. You should handle any errors triggered by this.
		 *
		 * @return	void
		 * @param	int $seconds	The timeout in seconds
		 */
		public function setTimeOut($seconds)
		{
			$this->timeOut = (int) $seconds;
		}


		/**
		 * Set the user-agent for you application
		 * It will be appended to ours, the result will look like: "PHP TinyUrl/<version> <your-user-agent>"
		 *
		 * @return	void
		 * @param	string $userAgent	Your user-agent, it should look like <app-name>/<app-version>
		 */
		public function setUserAgent($userAgent)
		{
			$this->userAgent = (string) $userAgent;
		}


		/**
		 * Create a TinyUrl
		 *
		 * @return	string
		 * @param	string $url	The orginal url that should be shortened
		 */
		public function create($url) {

			// redefine
			$url = (string) $url;

			// build parameters
			$aParameters[ 'url' ] = $url;

			// make the call
			return $this->doCall( self::API_URL .'/api-create.php', $aParameters );
		}


		/**
		 * Reverse a TinyUrl into a real url
		 *
		 * @return	mixed	If something fails it will return false, otherwise the orginal url will be returned as a string
		 * @param	string $url	The short tinyUrl that should be reversed
		 */
		public function reverse($url) {

			// redefine
			$url = (string) $url;

			// explode on .com
			$aChunks = explode( 'tinyurl.com/', $url);

			if( isset( $aChunks[ 1 ] ) ) {

				// rebuild url
				$requestUrl = 'http://preview.tinyurl.com/' . $aChunks[1];

				// make the call
				if ( $response = $this->doCall( $requestUrl ) ) {

					// init var
					$aMatches = array();

					// match
					preg_match( '/redirecturl" href="(.*)">/', $response, $aMatches);

					// return if something was found
					if ( isset( $aMatches[ 1 ] ) ) {

						return (string) $aMatches[ 1 ];
					}
				}
			}

			// fallback
			return false;
		}
	}
}
