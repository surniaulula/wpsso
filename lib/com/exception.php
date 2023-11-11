<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomErrorException' ) ) {

	class SucomErrorException extends ErrorException {

		protected static $codes = array(

			'curl' => array(
				1  => 'CURLE_UNSUPPORTED_PROTOCOL',
				2  => 'CURLE_FAILED_INIT',
				3  => 'CURLE_URL_MALFORMAT',
				4  => 'CURLE_URL_MALFORMAT_USER',
				5  => 'CURLE_COULDNT_RESOLVE_PROXY',
				6  => 'CURLE_COULDNT_RESOLVE_HOST',
				7  => 'CURLE_COULDNT_CONNECT',
				8  => 'CURLE_FTP_WEIRD_SERVER_REPLY',
				9  => 'CURLE_REMOTE_ACCESS_DENIED',
				11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
				13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
				14 => 'CURLE_FTP_WEIRD_227_FORMAT',
				15 => 'CURLE_FTP_CANT_GET_HOST',
				17 => 'CURLE_FTP_COULDNT_SET_TYPE',
				18 => 'CURLE_PARTIAL_FILE',
				19 => 'CURLE_FTP_COULDNT_RETR_FILE',
				21 => 'CURLE_QUOTE_ERROR',
				22 => 'CURLE_HTTP_RETURNED_ERROR',
				23 => 'CURLE_WRITE_ERROR',
				25 => 'CURLE_UPLOAD_FAILED',
				26 => 'CURLE_READ_ERROR',
				27 => 'CURLE_OUT_OF_MEMORY',
				28 => 'CURLE_OPERATION_TIMEDOUT',
				30 => 'CURLE_FTP_PORT_FAILED',
				31 => 'CURLE_FTP_COULDNT_USE_REST',
				33 => 'CURLE_RANGE_ERROR',
				34 => 'CURLE_HTTP_POST_ERROR',
				35 => 'CURLE_SSL_CONNECT_ERROR',
				36 => 'CURLE_BAD_DOWNLOAD_RESUME',
				37 => 'CURLE_FILE_COULDNT_READ_FILE',
				38 => 'CURLE_LDAP_CANNOT_BIND',
				39 => 'CURLE_LDAP_SEARCH_FAILED',
				41 => 'CURLE_FUNCTION_NOT_FOUND',
				42 => 'CURLE_ABORTED_BY_CALLBACK',
				43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
				45 => 'CURLE_INTERFACE_FAILED',
				47 => 'CURLE_TOO_MANY_REDIRECTS',
				48 => 'CURLE_UNKNOWN_TELNET_OPTION',
				49 => 'CURLE_TELNET_OPTION_SYNTAX',
				51 => 'CURLE_PEER_FAILED_VERIFICATION',
				52 => 'CURLE_GOT_NOTHING',
				53 => 'CURLE_SSL_ENGINE_NOTFOUND',
				54 => 'CURLE_SSL_ENGINE_SETFAILED',
				55 => 'CURLE_SEND_ERROR',
				56 => 'CURLE_RECV_ERROR',
				58 => 'CURLE_SSL_CERTPROBLEM',
				59 => 'CURLE_SSL_CIPHER',
				60 => 'CURLE_SSL_CACERT',
				61 => 'CURLE_BAD_CONTENT_ENCODING',
				62 => 'CURLE_LDAP_INVALID_URL',
				63 => 'CURLE_FILESIZE_EXCEEDED',
				64 => 'CURLE_USE_SSL_FAILED',
				65 => 'CURLE_SEND_FAIL_REWIND',
				66 => 'CURLE_SSL_ENGINE_INITFAILED',
				67 => 'CURLE_LOGIN_DENIED',
				68 => 'CURLE_TFTP_NOTFOUND',
				69 => 'CURLE_TFTP_PERM',
				70 => 'CURLE_REMOTE_DISK_FULL',
				71 => 'CURLE_TFTP_ILLEGAL',
				72 => 'CURLE_TFTP_UNKNOWNID',
				73 => 'CURLE_REMOTE_FILE_EXISTS',
				74 => 'CURLE_TFTP_NOSUCHUSER',
				75 => 'CURLE_CONV_FAILED',
				76 => 'CURLE_CONV_REQD',
				77 => 'CURLE_SSL_CACERT_BADFILE',
				78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
				79 => 'CURLE_SSH',
				80 => 'CURLE_SSL_SHUTDOWN_FAILED',
				81 => 'CURLE_AGAIN',
				82 => 'CURLE_SSL_CRL_BADFILE',
				83 => 'CURLE_SSL_ISSUER_ERROR',
				84 => 'CURLE_FTP_PRET_FAILED',
				84 => 'CURLE_FTP_PRET_FAILED',
				85 => 'CURLE_RTSP_CSEQ_ERROR',
				86 => 'CURLE_RTSP_SESSION_ERROR',
				87 => 'CURLE_FTP_BAD_FILE_LIST',
				88 => 'CURLE_CHUNK_FAILED',
			),

			/*
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

			/*
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

			if ( isset( self::$codes[ 'http' ][ $errcode ] ) ) {

				$errstr = trim( $errstr . ' HTTP ' . $errcode . ' ' . self::$codes[ 'http' ][ $errcode ] . '.' );
			}

			parent::__construct( $errstr, $errcode, $severity, $filename, $lineno, $previous );	// Calls ErrorException::__construct().
		}

		public static function http_error( $errcode, $context = '' ) {

			if ( isset( self::$codes[ 'http' ][ $errcode ] ) ) {

				$http_error = $errcode . ' ' . self::$codes[ 'http' ][ $errcode ];

				header( 'HTTP/1.1 ' . $http_error );	// Must be HTTP/1.1 for error code and error message.

				if ( ! empty( $context ) ) {

					header( 'Content-Type: text/html' );

					echo '<!DOCTYPE html>';
					echo '<html>';
					echo '<head>';
					echo '<title>' . $http_error . '</title>';
					echo '</head>';
					echo '<body>';
					echo '<h1>' . $http_error . '</h1>';
					echo '<p>' . $context . '</p>';
					echo '</body>';
					echo '</html>';
				}

			} else header( 'HTTP/1.0 ' . $errcode );

			exit();
		}
	}
}
