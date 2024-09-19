<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'SucomErrorException' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/com/exception.php';
}

if ( ! class_exists( 'WpssoErrorException' ) ) {

	class WpssoErrorException extends SucomErrorException {

		protected $p;

		public function __construct( $errstr = '', $errcode = null, $severity = E_ERROR, $filename = __FILE__, $lineno = __LINE__, Exception $previous = null ) {

			$this->p =& Wpsso::get_instance();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			parent::__construct( $errstr, $errcode, $severity, $filename, $lineno, $previous );	// Calls SucomErrorException::__construct().
		}

		public function errorMessage( $ret = false ) {

			/*
			 * getMessage();	// Message of exception.
			 * getCode();		// Code of exception.
			 * getFile();		// Source filename.
			 * getLine();		// Source line.
			 * getTrace();		// An array of the backtrace().
			 * getPrevious();	// Previous exception.
			 * getTraceAsString();	// Formatted string of trace.
			 */
			$errstr = $this->getMessage();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $errstr );
			}

			$this->p->notice->err( $errstr );
		}
	}
}
