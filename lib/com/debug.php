<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomDebug' ) ) {

	class SucomDebug {

		private $p;
		private $display_name = '';
		private $log_prefix = '';
		private $active = false;	// true if at least one subsys is true
		private $buffer = array();	// accumulate text strings going to html output
		private $subsys = array();	// associative array to enable various outputs 

		public function __construct( &$plugin, $subsys = array( 'html' => false, 'wp' => false ) ) {
			$this->p =& $plugin;
			$this->display_name = $this->p->cf['lca'];
			$this->log_prefix = $this->p->cf['uca'];
			$this->subsys = $subsys;
			$this->is_on();
			$this->mark();
		}

		public function mark() { 
			if ( $this->active !== true ) 
				return;

			$this->log( 'mark', 2 ); 
		}

		public function args( $args = array() ) { 
			if ( $this->active !== true ) 
				return;

			$this->log( 'args '.$this->fmt_array( $args ), 2 ); 
		}

		public function log( $input = '', $backtrace = 1 ) {
			if ( $this->active !== true ) 
				return;

			$stack = debug_backtrace();
			$log_msg = '';
			$log_msg .= sprintf( '%-35s:: ', 
				( empty( $stack[$backtrace]['class'] ) ? '' : $stack[$backtrace]['class'] ) );
			$log_msg .= sprintf( '%-25s : ', 
				( empty( $stack[$backtrace]['function'] ) ? '' : $stack[$backtrace]['function'] ) );

			if ( is_multisite() ) {
				global $blog_id; 
				$log_msg .= '[blog '.$blog_id.'] ';
			}

			if ( is_array( $input ) || is_object( $input ) )
				$log_msg .= print_r( $input, true );
			else $log_msg .= $input;

			if ( $this->subsys['html'] == true )
				$this->buffer[] = $log_msg;
			if ( $this->subsys['wp'] == true )
				error_log( $this->log_prefix.' '.$log_msg );
		}

		public function show_html( $data = null, $title = null ) {
			if ( $this->is_on( 'html' ) !== true ) return;
			echo $this->get_html( $data, $title, 2 );
		}

		public function get_html( $data = null, $title = null, $backtrace = 1 ) {
			if ( $this->is_on( 'html' ) !== true ) 
				return;

			$from = '';
			$html = '<!-- '.$this->display_name.' debug';
			$stack = debug_backtrace();
			if ( ! empty( $stack[$backtrace]['class'] ) ) 
				$from .= $stack[$backtrace]['class'].'::';
			if ( ! empty( $stack[$backtrace]['function'] ) )
				$from .= $stack[$backtrace]['function'];
			if ( $data === null ) {
				//$this->log( 'truncating debug log' );
				$data = $this->buffer;
				$this->buffer = array();
			}
			if ( ! empty( $from ) ) $html .= ' from '.$from.'()';
			if ( ! empty( $title ) ) $html .= ' '.$title;
			if ( ! empty( $data ) ) {
				$html .= ' : ';
				if ( is_array( $data ) ) {
					$html .= "\n";
					$is_assoc = SucomUtil::is_assoc( $data );
					if ( $is_assoc ) ksort( $data );
					foreach ( $data as $key => $val ) 
						$html .= $is_assoc ? "\t$key = $val\n" : "\t$val\n";
				} else {
					if ( preg_match( '/^Array/', $data ) ) $html .= "\n";	// check for print_r() output
					$html .= $data;
				}
			}
			$html .= ' -->'."\n";
			return $html;
		}

		public function switch_on( $name ) {
			return $this->switch_to( $name, true );
		}

		public function switch_off( $name ) {
			return $this->switch_to( $name, false );
		}

		private function switch_to( $name, $state ) {
			if ( ! empty( $name ) )
				$this->subsys[$name] = $state;
			return $this->is_on();
		}

		public function is_on( $name = '' ) {
			if ( ! empty( $name ) )
				return array_key_exists( $name, $this->subsys ) ? $this->subsys[$name] : false;
			else 
				$this->active = in_array( true, $this->subsys, true ) ? true : false;
			return $this->active;
		}

		private function fmt_array( $input ) {
			if ( is_array( $input ) ) {
				$line = '';
				foreach ( $input as $key => $val ) {
					if ( is_array( $val ) )
						$val = $this->fmt_array( $val );
					elseif ( $val === false )
						$val = 'false';
					elseif ( $val === true )
						$val = 'true';
					$line .= $key.'='.$val.', ';
				}
				return '('.trim( $line, ', ' ).')'; 
			} else return $input;
		}	
	}
}

?>
