<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomDebug' ) ) {

	class SucomDebug {

		private $p;				// Plugin class object.
		private $display_name = '';
		private $log_prefix   = '';
		private $log_buffer   = array();	// Accumulate text strings going to html output.
		private $outputs      = array();	// Associative array to enable various outputs.
		private $const_stats  = array();
		private $begin_stats  = array();
		private $last_stats   = array();
		private $log_fmt_cols = array( '%s ::', '%s :' );

		public $enabled = false;	// True if at least one $outputs array element is true.

		public function __construct( &$plugin, array $outputs = array( 'html' => false, 'log' => false ) ) {

			if ( ! class_exists( 'SucomUtil' ) ) {	// Just in case.

				require_once trailingslashit( dirname( __FILE__ ) ) . 'util.php';
			}

			if ( ! class_exists( 'SucomUtilWP' ) ) {	// Just in case.

				require_once trailingslashit( dirname( __FILE__ ) ) . 'util-wp.php';
			}

			$this->p =& $plugin;

			$this->const_stats  = $this->last_stats = array( 'mtime' => microtime( $get_float = true ), 'mem' => memory_get_usage() );
			$this->display_name = isset( $this->p->id ) ? $this->p->id : 'sucom';
			$this->log_prefix   = strtoupper( $this->display_name );
			$this->outputs      = $outputs;

			$this->is_enabled();	// Sets $this->enabled value.

			if ( ! empty( $this->outputs[ 'log' ] ) ) {

				if ( ! isset( $_SESSION ) ) {

					session_start();
				}
			}

			if ( $this->enabled ) {

				$this->mark();
			}

			add_action( 'shutdown', array( $this, 'shutdown_stats' ), -1000, 0 );
		}

		public function shutdown_stats() {
			
			if ( $this->enabled ) {

				$cur_stats  = array( 'mtime' => microtime( $get_float = true ), 'mem' => memory_get_usage() );
				$mtime_diff = $cur_stats[ 'mtime' ] - $this->const_stats[ 'mtime' ];
				$mem_diff   = $cur_stats[ 'mem' ] - $this->const_stats[ 'mem' ];
				
				$this->log( 'time diff = ' . $this->get_time_text( $mtime_diff ) );
				$this->log( 'mem diff = ' . $this->get_mem_text( $mem_diff ) );
				$this->log( 'mem peak = ' . $this->get_mem_text( memory_get_peak_usage() ) );
			}
		}

		public function is_enabled( $name = '' ) {

			if ( ! empty( $name ) ) {

				return isset( $this->outputs[ $name ] ) ? $this->outputs[ $name ] : false;
			}

			return $this->enabled = in_array( true, $this->outputs ) ? true : false;	// True if any sybsys is true.
		}

		public function enable( $name, $state = true ) {

			$prev_state = $this->is_enabled( $name );

			if ( ! empty( $name ) ) {

				$this->outputs[ $name ] = $state;

				if ( 'log' === $name ) {

					if ( ! isset( $_SESSION ) ) {

						session_start();
					}
				}
			}

			$this->is_enabled();	// Sets $this->enabled value.

			return $prev_state;	// Return the previous state to save and restore.
		}

		public function disable( $name, $state = false ) {

			return $this->enable( $name, $state );	// Return the previous state to save and restore.
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

			$this->log( 'args ' . SucomUtil::get_array_pretty( $arr, $flatten = true ), $class_seq, $func_seq );
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

				$this->log( $prefix . ' = ' . trim( print_r( SucomUtil::get_array_pretty( $mixed, false ), true ) ), $class_seq, $func_seq );

			} else {

				$this->log( $prefix . ' = ' . $mixed, $class_seq, $func_seq );
			}
		}

		public function log( $input = '', $class_seq = 1, $func_seq = false ) {

			if ( ! $this->enabled ) {

				return;
			}

			$stack   = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			$log_msg = '';

			if ( is_int( $class_seq ) ) {

				if ( false === $func_seq ) $func_seq = $class_seq;

				$class_name = empty( $stack[ $class_seq ][ 'class' ] ) ? '' : $stack[ $class_seq ][ 'class' ];

				$log_msg .= sprintf( $this->log_fmt_cols[ 0 ], $class_name ) . ' ';

			} else {

				if ( false === $func_seq ) $func_seq = 1;

				$log_msg .= sprintf( $this->log_fmt_cols[ 0 ], $class_seq ) . ' ';
			}

			if ( is_int( $func_seq ) ) {

				$func_name = empty( $stack[ $func_seq ][ 'function' ] ) ? '' : $stack[ $func_seq ][ 'function' ];

				$log_msg .= sprintf( $this->log_fmt_cols[ 1 ], $func_name ) . ' ';

			} else $log_msg .= sprintf( $this->log_fmt_cols[ 1 ], $func_seq ) . ' ';

			if ( is_multisite() ) {

				global $blog_id;

				$log_msg .= '[blog ' . $blog_id . '] ';
			}

			if ( is_array( $input ) ) {

				$log_msg .= trim( print_r( $input, true ) );

			} elseif ( is_object( $input ) ) {

				$log_msg .= print_r( 'object ' . get_class( $input ), true );

			} else $log_msg .= $input;

			if ( $this->outputs[ 'html' ] ) {

				$this->log_buffer[] = $log_msg;
			}

			if ( $this->outputs[ 'log' ] ) {

				$session_id = session_id();

				$connection_id = $session_id ? $session_id : $_SERVER[ 'REMOTE_ADDR' ];
				$connection_id = SucomUtilWP::doing_ajax() ? 'ajax ' . $connection_id : $connection_id;
				$connection_id = SucomUtilWP::doing_cron() ? 'cron ' . $connection_id : $connection_id;

				error_log( $connection_id . ' ' . $this->log_prefix . ' ' . $log_msg );
			}
		}

		public function mark( $id = false, $comment = '', $class_seq = 2 ) {

			if ( ! $this->enabled ) {

				return;
			}

			$cur_stats = array( 'mtime' => microtime( $get_float = true ), 'mem' => memory_get_usage() );
			$comment   = $comment ? ' ' . $comment : '';
			$sep_text  = '';

			if ( false !== $id ) {

				$sep_text .= "\n\t" . '- - - - - - ' . $id;

				if ( isset( $this->begin_stats[ $id ] ) ) {

					$mtime_diff = $cur_stats[ 'mtime' ] - $this->begin_stats[ $id ][ 'mtime' ];
					$mem_diff   = $cur_stats[ 'mem' ] - $this->begin_stats[ $id ][ 'mem' ];
					$stats_text = '+' . $this->get_time_text( $mtime_diff ) . ' / +' . $this->get_mem_text( $mem_diff );

					$sep_text .= ' end diff (' . $stats_text . ')';

					unset( $this->begin_stats[ $id ] );

				} else {

					$this->begin_stats[ $id ] = array( 'mtime' => $cur_stats[ 'mtime' ], 'mem'   => $cur_stats[ 'mem' ] );

					$sep_text .= ' begin';
				}
			}

			/*
			 * $this->const_stats is defined in the class __construct().
			 */
			$mtime_diff = $cur_stats[ 'mtime' ] - $this->const_stats[ 'mtime' ];
			$mem_diff   = $cur_stats[ 'mem' ] - $this->const_stats[ 'mem' ];
			$stats_text = $this->get_time_text( $mtime_diff ) . ' / ' . $this->get_mem_text( $mem_diff );

			$this->log( 'mark (' . $stats_text . ')' . $comment . $sep_text, $class_seq );
		}

		/*
		 * See WpssoComment->get_mod().
		 * See WpssoComment->get_options().
		 * See WpssoPost->get_mod().
		 * See WpssoPost->get_options().
		 * See WpssoTerm->get_mod().
		 * See WpssoTerm->get_options().
		 * See WpssoUser->get_mod().
		 * See WpssoUser->get_options().
		 */
		public function mark_caller( $comment = '' ) {

			$this->mark( $id = false, $comment, $class_seq = 4 );
		}

		public function mark_diff( $comment = '', $class_seq = 2 ) {

			if ( ! $this->enabled ) {

				return;
			}

			$comment    = $comment ? ' ' . $comment : '';
			$cur_stats  = array( 'mtime' => microtime( $get_float = true ), 'mem' => memory_get_usage() );
			$mtime_diff = $cur_stats[ 'mtime' ] - $this->last_stats[ 'mtime' ];
			$mem_diff   = $cur_stats[ 'mem' ] - $this->last_stats[ 'mem' ];
			$stats_text = '+' . $this->get_time_text( $mtime_diff ) . ' / +' . $this->get_mem_text( $mem_diff );

			$this->last_stats = $cur_stats;

			$this->log( 'mark diff (' . $stats_text . ')' . $comment, $class_seq );
		}

		private function get_time_text( $time ) {

			return sprintf( '%f secs', $time );
		}

		private function get_mem_text( $mem ) {

			return SucomUtil::format_mem_use( $mem, $dec = 2 );
		}

		/*
		 * See Wpsso->debug_hooks().
		 */
		public function show_html( $data = null, $title = null ) {

			if ( ! $this->is_enabled( 'html' ) ) return;

			echo $this->get_html( $data, $title, 2 );
		}

		public function get_html( $data = null, $title = null, $class_seq = 1, $func_seq = false ) {

			if ( ! $this->is_enabled( 'html' ) ) return;

			if ( false === $func_seq ) {

				$func_seq = $class_seq;
			}

			$html  = '<!-- ' . $this->display_name . ' debug';
			$stack = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			$from  = '';

			if ( ! empty( $stack[ $class_seq ][ 'class' ] ) ) {

				$from .= $stack[ $class_seq ][ 'class' ] . '::';
			}

			if ( ! empty( $stack[ $func_seq ][ 'function' ] ) ) {

				$from .= $stack[ $func_seq ][ 'function' ];
			}

			if ( null === $data ) {

				$data = $this->log_buffer;

				$this->log_buffer = array();	// Truncate the buffer.
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

						/*
						 * Firefox does not allow double-dashes inside comment blocks.
						 */
						$val = str_replace( array(
							'--',
						), array(
							'&hyphen;&hyphen;',
						), $val );

						$html .= $is_assoc ? "\t$key = $val\n" : "\t$val\n";

						unset( $data[ $key ] );	// Optimize memory usage.
					}

				} else $html .= $data;
			}

			$html .= ' -->' . "\n";

			return $html;
		}
	}
}
