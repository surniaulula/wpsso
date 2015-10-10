<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomDebug' ) ) {

	class SucomDebug {

		public $enabled = false;	// true if at least one subsys is true

		private $p;
		private $display_name = '';
		private $log_prefix = '';
		private $buffer = array();	// accumulate text strings going to html output
		private $subsys = array();	// associative array to enable various outputs 
		private $start_stats = null;
		private $begin_marks = array();

		public function __construct( &$plugin, $subsys = array( 'html' => false, 'wp' => false ) ) {
			$this->p =& $plugin;
			$this->start_stats = array(
				'time' => microtime( true ),
				'mem' => memory_get_usage( true ),
			);
			$this->display_name = $this->p->cf['lca'];
			$this->log_prefix = $this->p->cf['uca'];
			$this->subsys = $subsys;
			$this->is_enabled();		// set $this->enabled
			$this->mark();
		}

		public function mark( $id = false ) { 
			if ( $this->enabled !== true ) 
				return;

			$cur_stats = array(
				'time' => microtime( true ),
				'mem' => memory_get_usage( true ),
			);
			if ( $this->start_stats === null )
				$this->start_stats = $cur_stats;

			if ( $id !== false ) {
				$id_text = '- - - - - - '.$id;
				if ( isset( $this->begin_marks[$id] ) ) {
					$id_text .= ' end + ('.
						$this->get_time_text( $cur_stats['time'] - $this->begin_marks[$id]['time'] ).' / '.
						$this->get_mem_text( $cur_stats['mem'] - $this->begin_marks[$id]['mem'] ).')';
					unset( $this->begin_marks[$id] );
				} else {
					$id_text .= ' begin';
					$this->begin_marks[$id] = array(
						'time' => $cur_stats['time'],
						'mem' => $cur_stats['mem'],
					);
				}
			}
			$this->log( 'mark ('.
				$this->get_time_text( $cur_stats['time'] - $this->start_stats['time'] ).' / '.
				$this->get_mem_text( $cur_stats['mem'] - $this->start_stats['mem'] ).')'.
				( $id !== false ? "\n\t".$id_text : '' ), 2 );
		}

		private function get_time_text( $time ) {
			return sprintf( '%f secs', $time );
		}

		private function get_mem_text( $mem ) {
			if ( $mem < 1024 )
				return $mem.' bytes';
			elseif ( $mem < 1048576 )
				return round( $mem / 1024, 2).' kb';
			else return round( $mem / 1048576, 2).' mb'; 
		}

		public function args( $args = array(), $class_idx = 1, $function_idx = false ) { 
			if ( $this->enabled !== true ) 
				return;

			if ( is_int( $class_idx ) ) {
				if ( $function_idx === false )
					$function_idx = $class_idx;
				$class_idx++;
			}

			if ( is_int( $function_idx ) )
				$function_idx++;
			elseif ( $function_idx === false )
				$function_idx = 2;

			$this->log( 'args '.$this->fmt_array( $args ),
				$class_idx, $function_idx ); 
		}

		public function log( $input = '', $class_idx = 1, $function_idx = false ) {
			if ( $this->enabled !== true ) 
				return;
			$log_msg = '';
			$stack = debug_backtrace();

			if ( is_int( $class_idx ) ) {
				if ( $function_idx === false )
					$function_idx = $class_idx;
				$log_msg .= sprintf( '%-35s:: ', 
					( empty( $stack[$class_idx]['class'] ) ? 
						'' : $stack[$class_idx]['class'] ) );
			} else {
				if ( $function_idx === false )
					$function_idx = 1;
				$log_msg .= sprintf( '%-35s:: ', $class_idx );
			}

			if ( is_int( $function_idx ) ) {
				$log_msg .= sprintf( '%-28s : ', 
					( empty( $stack[$function_idx]['function'] ) ? 
						'' : $stack[$function_idx]['function'] ) );
			} else $log_msg .= sprintf( '%-28s : ', $function_idx );

			if ( is_multisite() ) {
				global $blog_id; 
				$log_msg .= '[blog '.$blog_id.'] ';
			}

			if ( is_array( $input ) || is_object( $input ) )
				$log_msg .= print_r( $input, true );
			else $log_msg .= $input;

			if ( $this->subsys['html'] == true )
				$this->buffer[] = $log_msg;

			if ( $this->subsys['wp'] == true ) {
				$sid = session_id();
				error_log( ( $sid ? $sid : $_SERVER['REMOTE_ADDR'] ).
					' '.$this->log_prefix.' '.$log_msg );
			}
		}

		public function show_html( $data = null, $title = null ) {
			if ( $this->is_enabled( 'html' ) !== true ) 
				return;
			echo $this->get_html( $data, $title, 2 );
		}

		public function get_html( $data = null, $title = null, $class_idx = 1, $function_idx = false ) {
			if ( $this->is_enabled( 'html' ) !== true ) 
				return;
			if ( $function_idx === false )
				$function_idx = $class_idx;
			$from = '';
			$html = '<!-- '.$this->display_name.' debug';
			$stack = debug_backtrace();
			if ( ! empty( $stack[$class_idx]['class'] ) ) 
				$from .= $stack[$class_idx]['class'].'::';
			if ( ! empty( $stack[$function_idx]['function'] ) )
				$from .= $stack[$function_idx]['function'];
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
			return $this->is_enabled();
		}

		public function is_enabled( $name = '' ) {
			if ( ! empty( $name ) )
				return isset( $this->subsys[$name] ) ? 
					$this->subsys[$name] : false;
			// return true if any sybsys is true (use strict checking)
			else $this->enabled = in_array( true, $this->subsys, true ) ?
				true : false;
			return $this->enabled;
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
