<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomErrorException' ) ) {

	class SucomErrorException extends ErrorException {

		protected $codes = array(

			/**
			 * CURLINFO_HTTP_CODE.
			 */
			'http' => array(
				100 => 'Continue',
				101 => 'Switching Protocols',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				306 => '(Unused)',
				307 => 'Temporary Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported'
			),

			/**
			 * CURLINFO_SSL_VERIFYRESULT.
			 */
			'ssl' => array(
				0  => 'Operation successful',
				2  => 'Unable to get issuer certificate',
				3  => 'Unable to get certificate CRL',
				4  => 'Unable to decrypt certificate\'s signature',
				5  => 'Unable to decrypt CRL\'s signature',
				6  => 'Unable to decode issuer public key',
				7  => 'Certificate signature failure',
				8  => 'CRL signature failure',
				9  => 'Certificate is not yet valid',
				10 => 'Certificate has expired',
				11 => 'CRL is not yet valid',
				12 => 'CRL has expired',
				13 => 'Format error in certificate\'s notBefore field',
				14 => 'Format error in certificate\'s notAfter field',
				15 => 'Format error in CRL\'s lastUpdate field',
				16 => 'Format error in CRL\'s nextUpdate field',
				17 => 'Out of memory',
				18 => 'Self signed certificate',
				19 => 'Self signed certificate in certificate chain',
				20 => 'Unable to get local issuer certificate',
				21 => 'Unable to verify the first certificate',
				22 => 'Certificate chain too long',
				23 => 'Certificate revoked',
				24 => 'Invalid CA certificate',
				25 => 'Path length constraint exceeded',
				26 => 'Unsupported certificate purpose',
				27 => 'Certificate not trusted',
				28 => 'Certificate rejected',
				29 => 'Subject issuer mismatch',
				30 => 'Authority and subject key identifier mismatch',
				31 => 'Authority and issuer serial number mismatch',
				32 => 'Key usage does not include certificate signing',
				50 => 'Application Verification Failure',
			),
		);

		public function __construct( $errstr = '', $errcode = null, $severity = E_ERROR, $filename = __FILE__, $lineno = __LINE__, Exception $previous = null ) {

			$http_code = null;

			if ( is_numeric( $errcode ) ) {	// Error codes are HTTP codes by default.

				$http_code = (int) $errcode;
			}

			if ( null !== $http_code && isset( $this->codes[ 'http' ][ $http_code ] ) ) {

				$errstr = trim( $errstr ) . ' HTTP ' . $http_code . ' ' . $this->codes[ 'http' ][ $http_code ] . '.';
			}

			parent::__construct( $errstr, $errcode, $severity, $filename, $lineno, $previous );	// Calls ErrorException::__construct().
		}
	}
}
