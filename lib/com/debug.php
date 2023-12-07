<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomDebug' ) ) {

	class SucomDebug {

		private $p;	// Plugin class object.

		private $display_name = '';
		private $log_prefix   = '';
		private $log_buffer   = array();	// Accumulate text strings going to html output.
		private $outputs      = array();	// Associative array to enable various outputs.
		private $start_stats  = null;
		private $begin_marks  = array();
		private $log_msg_cols = array(
			'%-40s:: ',
			'%-55s: ',
		);

		public $enabled = false;	// True if at least one $outputs array element is true.

		public function __construct( &$plugin, array $outputs = array( 'html' => false, 'log' => false ) ) {

			if ( ! class_exists( 'SucomUtil' ) ) {	// Just in case.

				require_once trailingslashit( dirname( __FILE__ ) ) . 'util.php';
			}

			$this->p =& $plugin;

			$this->start_stats = array(
				'mtime' => microtime( $get_float = true ),
				'mem'   => memory_get_usage(),
			);

			$this->display_name = $this->p->id;
			$this->log_prefix   = strtoupper( $this->display_name );
			$this->outputs      = $outputs;

			$this->is_enabled();	// Sets $this->enabled value.

			if ( ! empty( $outputs[ 'log' ] ) ) {

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

			$this->log( 'args ' . SucomUtil::pretty_array( $arr, $flatten = true ), $class_seq, $func_seq );
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

				$this->log( $prefix . ' = ' . trim( print_r( SucomUtil::pretty_array( $mixed, false ), true ) ), $class_seq, $func_seq );

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

				if ( false === $func_seq ) {

					$func_seq = $class_seq;
				}

				$class_name = empty( $stack[ $class_seq ][ 'class' ] ) ? '' : $stack[ $class_seq ][ 'class' ];

				$log_msg .= sprintf( $this->log_msg_cols[ 0 ], $class_name );

			} else {

				if ( false === $func_seq ) {

					$func_seq = 1;
				}

				$log_msg .= sprintf( $this->log_msg_cols[ 0 ], $class_seq );
			}

			if ( is_int( $func_seq ) ) {

				$func_name = empty( $stack[ $func_seq ][ 'function' ] ) ? '' : $stack[ $func_seq ][ 'function' ];

				$log_msg .= sprintf( $this->log_msg_cols[ 1 ], $func_name );

			} else {

				$log_msg .= sprintf( $this->log_msg_cols[ 1 ], $func_seq );
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

			if ( $this->outputs[ 'html' ] ) {

				$this->log_buffer[] = $log_msg;
			}

			if ( $this->outputs[ 'log' ] ) {

				$session_id = session_id();

				$connection_id = $session_id ? $session_id : $_SERVER[ 'REMOTE_ADDR' ];

				error_log( $connection_id . ' ' . $this->log_prefix . ' ' . $log_msg );
			}
		}

		public function mark( $id = false, $comment = '' ) {

			if ( ! $this->enabled ) {

				return;
			}

			$cur_stats = array(
				'mtime' => microtime( $get_float = true ),
				'mem'   => memory_get_usage(),
			);

			if ( null === $this->start_stats ) {

				$this->start_stats = $cur_stats;
			}

			if ( false !== $id ) {

				$append_text = '- - - - - - ' . $id;

				if ( isset( $this->begin_marks[ $id ] ) ) {

					$mtime_diff = $cur_stats[ 'mtime' ] - $this->begin_marks[ $id ][ 'mtime' ];
					$mem_diff   = $cur_stats[ 'mem' ] - $this->begin_marks[ $id ][ 'mem' ];
					$stats_text = $this->get_time_text( $mtime_diff ) . ' / ' . $this->get_mem_text( $mem_diff );

					$append_text .= ' end + (' . $stats_text . ')';

					unset( $this->begin_marks[ $id ] );

				} else {

					$append_text .= ' begin';

					$this->begin_marks[ $id ] = array(
						'mtime' => $cur_stats[ 'mtime' ],
						'mem'   => $cur_stats[ 'mem' ],
					);
				}
			}

			$mtime_diff = $cur_stats[ 'mtime' ] - $this->start_stats[ 'mtime' ];
			$mem_diff   = $cur_stats[ 'mem' ] - $this->start_stats[ 'mem' ];
			$stats_text = $this->get_time_text( $mtime_diff ) . ' / ' . $this->get_mem_text( $mem_diff );

			$this->log( 'mark (' . $stats_text . ')' . ( $comment ? ' ' . $comment : '' ) . ( false !== $id ? "\n\t" . $append_text : '' ), $class_seq = 2 );
		}

		/*
		 * See WpssoPost->get_mod().
		 */
		public function caller() {

			if ( ! $this->enabled ) {

				return;
			}

			$cur_stats = array(
				'mtime' => microtime( $get_float = true ),
				'mem'   => memory_get_usage(),
			);

			if ( null === $this->start_stats ) {

				$this->start_stats = $cur_stats;
			}

			$mtime_diff = $cur_stats[ 'mtime' ] - $this->start_stats[ 'mtime' ];
			$mem_diff   = $cur_stats[ 'mem' ] - $this->start_stats[ 'mem' ];
			$stats_text = $this->get_time_text( $mtime_diff ) . ' / ' . $this->get_mem_text( $mem_diff );

			$this->log( 'mark caller (' . $stats_text . ')', $class_seq = 3 );
		}

		private function get_time_text( $time ) {

			return sprintf( '%f secs', $time );
		}

		private function get_mem_text( $mem ) {

			if ( $mem < 1024 ) {

				return $mem . ' bytes';

			} elseif ( $mem < 1048576 ) {

				return round( $mem / 1024, 2) . ' kb';

			} else return round( $mem / 1048576, 2) . ' mb';
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

				$this->log_buffer = array();
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
						$val = str_replace( '--', '&hyphen;&hyphen;', $val );

						$html .= $is_assoc ? "\t$key = $val\n" : "\t$val\n";
					}

				} else {
					$html .= $data;
				}
			}

			$html .= ' -->' . "\n";

			return $html;
		}
	}
}
