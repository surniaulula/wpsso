<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2017-2022 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomBFO' ) ) {

	class SucomBFO {

		private $p;	// Plugin class object.

		private $plugin_id    = 'sucom';
		private $text_domain  = 'sucom';
		private $label_transl = '';
		private $bfo_check_id = 'check_output_buffer';	// String id to detect our check callback using __call().

		/*
		 * The SucomBFO common library class may be called by more than one plugin, so track which filters have been hooked
		 * using the $filter_hooked static property, and only hook a filter once. This allows different plugins to hook
		 * different filters, but not the same filter - which would be redundant - we only need to warn about filter output
		 * once. ;-)
		 */
		private static $filter_hooked = array();

		public function __construct( $plugin = null, $plugin_id = null, $text_domain = null, $label_transl = null ) {

			$this->set_config( $plugin, $plugin_id, $text_domain, $label_transl );
		}

		/*
		 * Wildcard method callbacks are added after each filter hook to check the output buffer for a non-empty string.
		 *
		 * The urlencoded wildcard suffix is used to extract the previous / reference hook prority and name.
		 */
		public function __call( $method_name, $args ) {

			if ( strpos( $method_name, $this->bfo_check_id . '_' ) === 0 ) {	// Method name starts with 'check_output_buffer_'.

				array_unshift( $args, $method_name );	// Set $method_name as first element.

				return call_user_func_array( array( $this, '__check_output_buffer' ), $args );
			}
		}

		/*
		 * Loop through each filter name in the $filter_names argument and add a start hook (which starts the output
		 * buffer, adds a check hook after each callback, and adds a stop output buffer hook at the end).
		 */
		public function add_start_hooks( array $filter_names = array( 'the_content' ) ) {

			global $wp_actions;

			foreach ( $filter_names as $filter_name ) {

				if ( empty( $wp_actions[ $filter_name ] ) ) {	// Just in case - skip actions.

					if ( ! isset( self::$filter_hooked[ $filter_name ] ) ) {	// Only hook a filter once.

						self::$filter_hooked[ $filter_name ] = true;

						add_filter( $filter_name, array( $this, 'start_output_buffer' ), PHP_INT_MIN, 1 );
					}
				}
			}
		}

		/*
		 * Loop through each filter name in the $filter_names argument and add remove the start, check, and stop output
		 * hooks.
		 */
		public function remove_all_hooks( array $filter_names = array( 'the_content' ) ) {

			global $wp_actions;

			foreach ( $filter_names as $filter_name ) {

				if ( empty( $wp_actions[ $filter_name ] ) ) {	// Just in case - skip actions.

					if ( isset( self::$filter_hooked[ $filter_name ] ) ) {	// Skip if not already hooked.

						unset( self::$filter_hooked[ $filter_name ] );

						remove_filter( $filter_name, array( $this, 'start_output_buffer' ), PHP_INT_MIN, 1 );

						$this->remove_check_output_hooks( $filter_name );

						remove_filter( $filter_name, array( $this, 'stop_output_buffer' ), PHP_INT_MAX, 1 );
					}
				}
			}
		}

		/*
		 * Runs at the beginning of a filter to start the PHP output buffer, add a check hook after each callback, and add
		 * a stop hook at the end. When the special 'all' filter is hooked, this method will be called for actions as well,
		 * so check $wp_actions to exclude actions.
		 */
		public function start_output_buffer( $value ) {

			global $wp_actions;

			$filter_name = current_filter();

			if ( empty( $wp_actions[ $filter_name ] ) ) {	// Only check filters, not actions.

				static $filter_count = array();

				$filter_count[ $filter_name ] = isset( $filter_count[ $filter_name ] ) ? $filter_count[ $filter_name ]++ : 1;

				if ( ob_start() ) {

					if ( $filter_count[ $filter_name ] === 1 ) {	// Only check output on the first run.

						$this->add_check_output_hooks( $filter_name );

					} elseif ( $filter_count[ $filter_name ] === 2 ) {	// Remove check hooks on second run.

						$this->remove_check_output_hooks( $filter_name );
					}

					add_filter( $filter_name, array( $this, 'stop_output_buffer' ), PHP_INT_MAX, 1 );
				}
			}

			return $value;
		}

		/*
		 * Runs at the end of a filter to clean (truncate) and end (terminate) the output buffer.
		 */
		public function stop_output_buffer( $value ) {

			ob_end_clean();

			return $value;
		}

		/*
		 * Called once by start_output_buffer() at the beginning of a filter to add a check hook after each callback.
		 */
		private function add_check_output_hooks( $filter_name ) {

			global $wp_filter;

			if ( isset( $wp_filter[ $filter_name ]->callbacks ) ) {

				$bfo_check_str = '_' . __CLASS__ . '::' . $this->bfo_check_id;	// '_SucomBFO::check_output_buffer'

				foreach ( $wp_filter[ $filter_name ]->callbacks as $hook_prio => &$hook_group ) {	// Use reference to modify $hook_group.

					$new_hook_group = array();	// Create a new group to insert a check after each hook.

					foreach ( $hook_group as $hook_ref => $hook_info ) {

						$new_hook_group[ $hook_ref ] = $hook_info;	// Add the original callback first, followed by the check.

						$hook_name = self::get_hook_function_name( $hook_info );	// Create a human readable class / method name.

						if ( $hook_name === '' ) {	// Just in case.

							continue;

						} elseif ( strpos( $hook_name, __CLASS__ . '::' ) === 0 ) {	// Exclude our own class methods from being checked.

							continue;

						} elseif ( false !== strpos( $hook_ref, $bfo_check_str ) ) {	// Just in case - don't check the check hooks.

							continue;
						}

						$check_ref = $hook_ref . $bfo_check_str;	// Include the previous hook ref for visual clue.

						$check_arg = urlencode( '[' . $hook_prio . ']' . $hook_name );	// Include previous hook priority and name.

						$new_hook_group[ $check_ref ] = array(
							'function' => array(
								$this,
								$this->bfo_check_id . '_' . $check_arg	// Hooks the __call() method.
							),
							'accepted_args' => 1,
						);
					}

					$hook_group = $new_hook_group;
				}
			}
		}

		/*
		 * Remove the output check hooks if/when a filter is applied a second time.
		 */
		private function remove_check_output_hooks( $filter_name ) {

			global $wp_filter;

			if ( isset( $wp_filter[ $filter_name ]->callbacks ) ) {

				$bfo_check_str = '_' . __CLASS__ . '::' . $this->bfo_check_id;

				foreach ( $wp_filter[ $filter_name ]->callbacks as $hook_prio => &$hook_group ) {	// Use reference to modify $hook_group.

					foreach ( $hook_group as $hook_ref => $hook_info ) {

						if ( false !== strpos( $hook_ref, $bfo_check_str ) ) {

							unset( $hook_group[ $hook_ref ] );
						}
					}
				}
			}
		}

		/*
		 * Set property values for text domain, notice label, etc.
		 */
		private function set_config( $plugin = null, $plugin_id = null, $text_domain = null, $label_transl = null ) {

			if ( $plugin !== null ) {

				$this->p =& $plugin;

				if ( ! empty( $this->p->debug->enabled ) ) {

					$this->p->debug->mark();
				}
			}

			if ( $plugin_id !== null ) {

				$this->plugin_id = $plugin_id;

			} elseif ( ! empty( $this->p->id ) ) {

				$this->plugin_id = $this->p->id;
			}

			if ( $text_domain !== null ) {

				$this->text_domain = $text_domain;

			} elseif ( ! empty( $this->p->cf[ 'plugin' ][ $this->plugin_id ][ 'text_domain' ] ) ) {

				$this->text_domain = $this->p->cf[ 'plugin' ][ $this->plugin_id ][ 'text_domain' ];
			}

			if ( $label_transl !== null ) {

				$this->label_transl = $label_transl;	// Argument is already translated.

			} elseif ( ! empty( $this->p->cf[ 'menu' ][ 'title' ] ) ) {

				$this->label_transl = sprintf( __( '%s Notice', $this->text_domain ),
					_x( $this->p->cf[ 'menu' ][ 'title' ], 'menu title', $this->text_domain ) );

			} else {

				$this->label_transl = __( 'Notice', $this->text_domain );
			}
		}

		/*
		 * Called by the __call() method after each filter hook. Checks the output buffer for any non-empty string.
		 */
		private function __check_output_buffer( $method_name, $value ) {

			$output = ob_get_contents();

			/*
			 * Check if the previous hook has contributed some output.
			 */
			if ( $output !== '' ) {

				$error_text = __( 'The "%1$s" hook with priority %2$d in the "%3$s" filter has incorrectly sent output to the webpage.',
					$this->text_domain ) . ' ';

				$error_text .= __( 'Unlike WordPress actions, WordPress filters must always return their text, not echo it to the webpage output.',
					$this->text_domain ) . ' ';

				$error_text .= __( 'Please contact the author of that filter and report this issue as a coding error.',
					$this->text_domain );

				if ( preg_match( '/^' . $this->bfo_check_id . '_\[([0-9]+)\](.+)$/', urldecode( $method_name ), $matches ) ) {

					$error_pre = sprintf( '%s error:', __METHOD__ );

					$error_msg = sprintf( $error_text, $matches[ 2 ], $matches[ 1 ], current_filter() );

					/*
					 * Filters are rarely applied on the admin / back-end side, but if they are, then take
					 * advantage of this and show a notice. :)
					 */
					if ( is_admin() ) {

						if ( isset( $this->p->notice ) ) {

							/*
							 * Add notice only if the admin notices have not already been shown.
							 */
							if ( $this->p->notice->is_admin_pre_notices() ) {

								$this->p->notice->err( $error_msg );
							}

						} else {

							$lib_dir = trailingslashit( realpath( dirname( __FILE__ ) ) );

							require_once $lib_dir . 'com/notice.php';	// Load the SucomNotice class.

							$notice = new SucomNotice( $this->p, $this->plugin_id, $this->text_domain, $this->label_transl );

							/*
							 * Add notice only if the admin notices have not already been shown.
							 */
							if ( $notice->is_admin_pre_notices() ) {

								$notice->err( $error_msg );
							}
						}
					}

					$output_msg = __( 'Incorrect webpage output:', $this->text_domain ) . "\n" .
						'-----' . __( 'BEGIN OUTPUT', $this->text_domain ) . '-----' . "\n" . print_r( $output, true ) . "\n" .
						'-----' . __( 'END OUTPUT', $this->text_domain ) . '-----' . "\n";

					/*
					 * Use SucomUtil::safe_error_log() if available to define the debug.log path and prevent
					 * the error message from being displayed in the webpage.
					 */
					if ( method_exists( 'SucomUtil', 'safe_error_log' ) ) {

						SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg . "\n" . $output_msg );

					} else {

						error_log( $error_pre . ' ' . $error_msg . "\n" . $output_msg );
					}
				}

				ob_clean();	// Clean the output buffer for the next hook check.
			}

			return $value;
		}

		/*
		 * Get a human readable class/method/function name from the callback array.
		 */
		public static function get_hook_function_name( array $hook_info ) {

			$hook_name = '';

			if ( isset( $hook_info[ 'function' ] ) ) {

				if ( is_array( $hook_info[ 'function' ] ) ) {	// Hook is a class method.

					$class_name = '';

					$function_name = '';

					if ( is_object( $hook_info[ 'function' ][ 0 ] ) ) {

						$class_name = get_class( $hook_info[ 'function' ][ 0 ] );

					} elseif ( is_string( $hook_info[ 'function' ][ 0 ] ) ) {

						$class_name = $hook_info[ 'function' ][ 0 ];
					}

					if ( is_string( $hook_info[ 'function' ][ 1 ] ) ) {

						$function_name = $hook_info[ 'function' ][ 1 ];
					}

					$hook_name = $class_name . '::' . $function_name;

				} elseif ( is_string( $hook_info[ 'function' ] ) ) {	// Hook is a function.

					$hook_name = $hook_info[ 'function' ];
				}
			}

			return $hook_name;
		}
	}
}
