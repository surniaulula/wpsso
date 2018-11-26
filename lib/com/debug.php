<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomDebug' ) ) {

	class SucomDebug {

		private $p;
		private $display_name = '';
		private $log_prefix   = '';
		private $buffer       = array();	// Accumulate text strings going to html output.
		private $subsys       = array();	// Associative array to enable various outputs.
		private $start_stats  = null;
		private $begin_marks  = array();

		public $enabled = false;	// true if at least one subsys is true

		public function __construct( &$plugin, $subsys = array( 'html' => false, 'log' => false ) ) {

			$this->p =& $plugin;

			$this->start_stats = array(
				'mtime' => microtime( true ),
				'mem'   => memory_get_usage(),
			);

			$this->display_name = $this->p->lca;
			$this->log_prefix   = strtoupper( $this->display_name );
			$this->subsys       = $subsys;

			$this->is_enabled();	// sets $this->enabled value

			if ( ! empty( $subsys['log'] ) ) {
				if ( ! isset( $_SESSION ) ) {
					session_start();
				}
			}

			if ( $this->enabled ) {
				$this->mark();
			}
		}

		public function is_enabled( $name = '' ) {

			if ( ! empty( $name ) ) {
				return isset( $this->subsys[$name] ) ? $this->subsys[$name] : false;
			} else {
				// return true if any sybsys is true (use strict checking)
				$this->enabled = in_array( true, $this->subsys, true ) ? true : false;
			}

			return $this->enabled;
		}

		public function enable( $name, $state = true ) {

			if ( ! empty( $name ) ) {

				$this->subsys[$name] = $state;

				if ( $name === 'log' ) {
					if ( ! isset( $_SESSION ) ) {
						session_start();
					}
				}
			}

			$this->is_enabled();	// sets $this->enabled value
		}

		public function disable( $name ) {

			$this->enable( $name, false );
		}

		public function log_args( array $arr, $class_seq = 1, $func_seq = false ) {

			if ( ! $this->enabled ) {
				return;
			}

			if ( is_int( $class_seq ) ) {
				if ( false === $func_seq ) {
					$func_seq = $class_seq;
				}
				$class_seq++;
			}

			if ( is_int( $func_seq ) ) {
				$func_seq++;
			} elseif ( false === $func_seq ) {
				$func_seq = 2;
			}

			$this->log( 'args ' . self::pretty_array( $arr, true ), $class_seq, $func_seq );
		}

		public function log_arr( $prefix, $mixed, $class_seq = 1, $func_seq = false ) {

			if ( ! $this->enabled ) {
				return;
			}

			if ( is_int( $class_seq ) ) {
				if ( false === $func_seq ) {
					$func_seq = $class_seq;
				}
				$class_seq++;
			}

			if ( is_int( $func_seq ) ) {
				$func_seq++;
			} elseif ( false === $func_seq ) {
				$func_seq = 2;
			}

			if ( is_object( $mixed ) ) {
				$prefix = trim( $prefix . ' ' . get_class( $mixed ) . ' object vars' );
				$mixed = get_object_vars( $mixed );
			}

			if ( is_array( $mixed ) ) {
				$this->log( $prefix . ' ' . trim( print_r( self::pretty_array( $mixed, false ), true ) ), $class_seq, $func_seq );
			} else {
				$this->log( $prefix . ' ' . $mixed, $class_seq, $func_seq );
			}
		}

		public function log( $input = '', $class_seq = 1, $func_seq = false ) {

			if ( ! $this->enabled ) {
				return;
			}

			$first_col  = '%-38s:: ';
			$second_col = '%-48s: ';
			$stack      = debug_backtrace();
			$log_msg    = '';

			if ( is_int( $class_seq ) ) {
				if ( false === $func_seq ) {
					$func_seq = $class_seq;
				}
				$log_msg .= sprintf( $first_col, ( empty( $stack[$class_seq][ 'class' ] ) ? '' : $stack[$class_seq][ 'class' ] ) );
			} else {
				if ( false === $func_seq ) {
					$func_seq = 1;
				}
				$log_msg .= sprintf( $first_col, $class_seq );
			}

			if ( is_int( $func_seq ) ) {
				$log_msg .= sprintf( $second_col, ( empty( $stack[$func_seq][ 'function' ] ) ? '' : $stack[$func_seq][ 'function' ] ) );
			} else {
				$log_msg .= sprintf( $second_col, $func_seq );
			}

			if ( is_multisite() ) {
				global $blog_id;
				$log_msg .= '[blog ' . $blog_id . '] ';
			}

			if ( is_array( $input ) ) {
				$log_msg .= trim( print_r( $input, true ) );
			} elseif ( is_object( $input ) ) {
				$log_msg .= print_r( 'object ' . get_class( $input ), true );
			} else {
				$log_msg .= $input;
			}

			if ( $this->subsys['html'] ) {
				$this->buffer[] = $log_msg;
			}

			if ( $this->subsys['log'] ) {

				$session_id    = session_id();
				$connection_id = $session_id ? $session_id : $_SERVER['REMOTE_ADDR'];

				error_log( $connection_id . ' ' . $this->log_prefix . ' ' . $log_msg );
			}
		}

		public function mark( $id = false, $comment = '' ) {

			if ( ! $this->enabled ) {
				return;
			}

			$cur_stats = array(
				'mtime' => microtime( true ),
				'mem'   => memory_get_usage(),
			);

			if ( null === $this->start_stats ) {
				$this->start_stats = $cur_stats;
			}

			if ( false !== $id ) {

				$append_text = '- - - - - - ' . $id;

				if ( isset( $this->begin_marks[$id] ) ) {

					$mtime_diff = $cur_stats['mtime'] - $this->begin_marks[$id]['mtime'];
					$mem_diff   = $cur_stats['mem'] - $this->begin_marks[$id]['mem'];
					$stats_text = $this->get_time_text( $mtime_diff ) . ' / ' . $this->get_mem_text( $mem_diff );

					$append_text .= ' end + (' . $stats_text . ')';

					unset( $this->begin_marks[$id] );

				} else {

					$append_text .= ' begin';

					$this->begin_marks[ $id ] = array(
						'mtime' => $cur_stats['mtime'],
						'mem'   => $cur_stats['mem'],
					);
				}
			}

			$mtime_diff = $cur_stats['mtime'] - $this->start_stats['mtime'];
			$mem_diff   = $cur_stats['mem'] - $this->start_stats['mem'];
			$stats_text = $this->get_time_text( $mtime_diff ) . ' / ' . $this->get_mem_text( $mem_diff );

			$this->log( 'mark (' . $stats_text . ')' . ( $comment ? ' ' . $comment : '' ) . ( false !== $id ? "\n\t" . $append_text : '' ), 2 );
		}

		private function get_time_text( $time ) {

			return sprintf( '%f secs', $time );
		}

		private function get_mem_text( $mem ) {

			if ( $mem < 1024 ) {
				return $mem . ' bytes';
			} elseif ( $mem < 1048576 ) {
				return round( $mem / 1024, 2) . ' kb';
			} else {
				return round( $mem / 1048576, 2) . ' mb';
			}
		}

		public function show_html( $data = null, $title = null ) {

			if ( ! $this->is_enabled( 'html' ) ) {
				return;
			}

			echo $this->get_html( $data, $title, 2 );
		}

		public function get_html( $data = null, $title = null, $class_seq = 1, $func_seq = false ) {

			if ( ! $this->is_enabled( 'html' ) ) {
				return;
			}

			if ( false === $func_seq ) {
				$func_seq = $class_seq;
			}

			$from  = '';
			$html  = '<!-- ' . $this->display_name . ' debug';
			$stack = debug_backtrace();

			if ( ! empty( $stack[$class_seq][ 'class' ] ) ) {
				$from .= $stack[$class_seq][ 'class' ] . '::';
			}

			if ( ! empty( $stack[$func_seq][ 'function' ] ) ) {
				$from .= $stack[$func_seq][ 'function' ];
			}

			if ( null === $data ) {
				$data = $this->buffer;
				$this->buffer = array();
			}

			if ( ! empty( $from ) ) {
				$html .= ' from ' . $from . '()';
			}

			if ( ! empty( $title ) ) {
				$html .= ' ' . $title;
			}

			if ( ! empty( $data ) ) {

				$html .= ' : ';

				if ( is_array( $data ) ) {

					$html .= "\n";

					$is_assoc = SucomUtil::is_assoc( $data );

					if ( $is_assoc ) {
						ksort( $data );
					}

					foreach ( $data as $key => $val ) {
						if ( is_string( $val ) && false !== strpos( $val, '<!--' ) ) {	// Remove HTML comments.
							$val = preg_replace( '/<!--.*-->/Ums', '', $val );
						} elseif ( is_array( $val ) ) {	// Just in case.
							$val = print_r( $val, true );
						}

						$html .= $is_assoc ? "\t$key = $val\n" : "\t$val\n";
					}

				} else {
					$html .= $data;
				}
			}

			$html .= ' -->' . "\n";

			return $html;
		}

		public static function pretty_array( $mixed, $flatten = false ) {

			$ret = '';

			if ( is_array( $mixed ) ) {
				foreach ( $mixed as $key => $val ) {
					$val = self::pretty_array( $val, $flatten );
					if ( $flatten ) {
						$ret .= $key.'=' . $val.', ';
					} else {
						if ( is_object( $mixed[$key] ) )
							unset ( $mixed[$key] );	// dereference the object first
						$mixed[$key] = $val;
					}
				}
				if ( $flatten ) {
					$ret = '(' . trim( $ret, ', ' ) . ')';
				} else {
					$ret = $mixed;
				}
			} elseif ( false === $mixed ) {
				$ret = 'false';
			} elseif ( true === $mixed ) {
				$ret = 'true';
			} elseif ( null === $mixed ) {
				$ret = 'null';
			} elseif ( '' === $mixed ) {
				$ret = '\'\'';
			} elseif ( is_object( $mixed ) ) {
				$ret = 'object ' . get_class( $mixed );
			} else {
				$ret = $mixed;
			}

			return $ret;
		}
	}
}

