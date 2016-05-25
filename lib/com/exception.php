<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomException' ) ) {

	class SucomException extends Exception {

		protected $p;

		public function __construct( $message = null, $code = 0, Exception $previous = null ) {

			if ( class_exists( 'Wpsso' ) )
				$this->p =& Wpsso::get_instance();
			elseif ( class_exists( 'Ngfb' ) )
				$this->p =& Ngfb::get_instance();

			if ( is_object( $this->p ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->mark();
			}

			parent::__construct( $message, $code, $previous );
		}

		public function errorMessage() {
			/*
			 * getMessage();        // message of exception
			 * getCode();           // code of exception
			 * getFile();           // source filename
			 * getLine();           // source line
			 * getTrace();          // an array of the backtrace()
			 * getPrevious();       // previous exception
			 * getTraceAsString();  // formatted string of trace
			 */
			if ( is_object( $this->p ) )
				$this->p->notice->err( $this->getMessage(), true );
		}
	}
}

?>
