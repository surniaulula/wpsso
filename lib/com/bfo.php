<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomBFO' ) ) {

	class SucomBFO {

		private $p;
		private $lca = 'sucom';
		private $text_domain = 'sucom';
		private $notice_label = '';
		private $bfo_check_id = 'check_output_buffer';	// string id to detect our check callback using __call()

		/*
		 * This common library class may be called by more than one
		 * plugin, so track which filters have been hooked using the
		 * $filter_hooked static property, and only hook a filter once.
		 * This allows different plugins to hook different filters, but
		 * not the same filter - which would be redundant - we only
		 * need to warn about filter output once. ;-)
		 */
		private static $filter_hooked = array();

		public function __construct( $plugin = null, $lca = null, $text_domain = null, $notice_label = null ) {
			$this->set_config( $plugin, $lca, $text_domain, $notice_label );
		}

		/*
		 * Wildcard method callbacks are added after each filter hook
		 * to check the output buffer for a non-empty string.
		 *
		 * The urlencoded wildcard suffix is used to extract the
		 * previous / reference hook prority and name.
		 */
		public function __call( $method_name, $args ) {
			if ( strpos( $method_name, $this->bfo_check_id.'_' ) === 0 ) {
				array_unshift( $args, $method_name );
				return call_user_func_array( array( &$this, '__check_output_buffer' ), $args );
			}
		}

		/*
		 * Loop through each filter name in the $filter_names argument
		 * and add a start hook (which starts the output buffer, adds a
		 * check hook after each callback, and adds a stop output
		 * buffer hook at the end).
		 */
		public function add_start_output_hooks( array $filter_names = array( 'the_content' ) ) {
			global $wp_actions;
			foreach ( $filter_names as $filter_name ) {
				if ( empty( $wp_actions[$filter_name] ) ) {
					if ( ! isset( self::$filter_hooked[$filter_name] ) ) {	// only hook a filter once
						self::$filter_hooked[$filter_name] = true;
						add_filter( $filter_name, array( &$this, 'start_output_buffer' ), self::get_min_prio(), 1 );
					}
				}
			}
		}

		/*
		 * Runs at the beginning of a filter to start the PHP output
		 * buffer, add a check hook after each callback, and add a stop
		 * hook at the end. When the special 'all' filter is hooked,
		 * this method will be called for actions as well, so check
		 * $wp_actions to exclude actions.
		 */
		public function start_output_buffer( $value ) {
			global $wp_actions;
			$filter_name = current_filter();
			if ( empty( $wp_actions[$filter_name] ) ) {				// only check filters, not actions 
				static $filter_count = array();
				$filter_count[$filter_name] = isset( $filter_count[$filter_name] ) ? $filter_count[$filter_name]++ : 1;
				if ( ob_start() ) {
					if ( $filter_count[$filter_name] === 1 ) {		// only check output on the first run
						$this->add_check_output_hooks( $filter_name );
					} elseif ( $filter_count[$filter_name] === 2 ) {	// remove check hooks on second run
						$this->remove_check_output_hooks( $filter_name );
					}
					add_filter( $filter_name, array( &$this, 'stop_output_buffer' ), self::get_max_int(), 1 );
				}
			}
			return $value;
		}

		/*
		 * Runs at the end of a filter to clean (truncate) and end
		 * (terminate) the output buffer.
		 */
		public function stop_output_buffer( $value ) {
			ob_end_clean();
			return $value;
		}

		/*
		 * Called once by start_output_buffer() at the beginning of a
		 * filter to add a check hook after each callback.
		 */
		private function add_check_output_hooks( $filter_name ) {
			global $wp_filter;
			if ( isset( $wp_filter[$filter_name]->callbacks ) ) {
				foreach ( $wp_filter[$filter_name]->callbacks as $hook_prio => &$hook_group ) {
					$new_hook_group = array();						// create a new group to insert a check after each hook
					foreach ( $hook_group as $hook_ref => $hook_info ) {
						$new_hook_group[$hook_ref] = $hook_info;			// add the original callback first, followed by the check
						$hook_name = self::get_hook_function_name( $hook_info );	// create a human readable class / method name
						if ( $hook_name === '' ) {					// just in case
							continue;
						} elseif ( strpos( $hook_name, __CLASS__.'::' ) === 0 ) {	// exclude our own class / methods from being checked
							continue;
						}
						$check_hook_ref = $hook_ref.'_'.$this->bfo_check_id;		// include the previous hook ref for visual clue
						$check_hook_arg = urlencode( '['.$hook_prio.']'.$hook_name );	// pass previous hook priority and name
						$new_hook_group[$check_hook_ref] = array(
							'function' => array(
								&$this,
								$this->bfo_check_id.'_'.$check_hook_arg		// hooks the __call() method
							),
							'accepted_args' => 1,
						);
					}
					$hook_group = $new_hook_group;
				}
			}
		}

		/*
		 * Remove the output check hooks if/when a filter is applied a
		 * second time.
		 */
		private function remove_check_output_hooks( $filter_name ) {
			global $wp_filter;
			foreach ( $wp_filter[$filter_name]->callbacks as $hook_prio => &$hook_group ) {
				foreach ( $hook_group as $hook_ref => $hook_info ) {
					if ( strpos( $hook_ref, '_'.$this->bfo_check_id ) !== false ) {
						unset( $hook_group[$hook_ref] );
					}
				}
			}
		}

		/*
		 * Set property values for text domain, notice label, etc.
		 */
		private function set_config( $plugin = null, $lca = null, $text_domain = null, $notice_label = null ) {

			if ( $plugin !== null ) {
				$this->p =& $plugin;
				if ( ! empty( $this->p->debug->enabled ) ) {
					$this->p->debug->mark();
				}
			}

			if ( $lca !== null ) {
				$this->lca = $lca;
			} elseif ( ! empty( $this->p->cf['lca'] ) ) {
				$this->lca = $this->p->cf['lca'];
			}

			if ( $text_domain !== null ) {
				$this->text_domain = $text_domain;
			} elseif ( ! empty( $this->p->cf['plugin'][$this->lca]['text_domain'] ) ) {
				$this->text_domain = $this->p->cf['plugin'][$this->lca]['text_domain'];
			}

			if ( $notice_label !== null ) {
				$this->notice_label = $notice_label;	// label should already be translated
			} elseif ( ! empty( $this->p->cf['menu']['title'] ) ) {
				$this->notice_label = sprintf( __( '%s Notice', $this->text_domain ),
					_x( $this->p->cf['menu']['title'], 'menu title', $this->text_domain ) );
			} else {
				$this->notice_label = __( 'Notice', $this->text_domain );
			}
		}

		/*
		 * Called by the __call() method after each filter hook.
		 * Checks the output buffer for any non-empty string.
		 */
		private function __check_output_buffer( $method_name, $value ) {
			$output = ob_get_contents();
			if ( $output !== '' ) {	// the previous hook has contributed some output

				$error_text = __( 'The "%1$s" filter hook with priority %2$d in the "%3$s" filter has mistakenly provided some webpage output.',
					$this->text_domain ).' '.
				__( 'All WordPress filter hooks must return their text - not send it to the webpage output.',
					$this->text_domain ).' '.
				__( 'Please contact the author of that filter hook and report this issue as a coding error / bug.',
					$this->text_domain );

				if ( preg_match( '/^'.$this->bfo_check_id.'_\[([0-9]+)\](.+)$/', urldecode( $method_name ), $matches ) ) {
					$error_msg = sprintf( $error_text, $matches[2], $matches[1], current_filter() );
					/*
					 * Filters are rarely applied on the admin / back-end side, 
					 * but if they are, then take advantage of this and show a 
					 * notice. :)
					 */
					if ( is_admin() ) {
						$lib_com_dir = trailingslashit( realpath( dirname( __FILE__ ) ) );
						require_once $lib_com_dir.'notice.php';	// load the SucomNotice class
						$notice = new SucomNotice( $this->p, $this->lca, $this->text_domain, $this->notice_label );
						$notice->err( $error_msg );
					}
					error_log(
						$error_msg.' '.
						__( 'Incorrect webpage output:', $this->text_domain )."\n".
						__( '---BEGIN---', $this->text_domain )."\n".
						print_r( $output, true )."\n".
						__( '---END---', $this->text_domain )
					);
				}

				ob_clean();	// clean the output buffer for the next hook check
			}
			return $value;
		}

		/*
		 * Get a human readable class/method/function name from the
		 * callback array. 
		 */
		private static function get_hook_function_name( array $hook_info ) {
			$hook_name = '';
			if ( ! isset( $hook_info['function'] ) ) {		// just in case
				return $hook_name;				// stop here - return an empty string
			} elseif ( is_array( $hook_info['function'] ) ) {	// hook is a class / method
				$class_name = '';
				$function_name = '';
				if ( is_object( $hook_info['function'][0] ) ) {
					$class_name = get_class( $hook_info['function'][0] );
				} elseif ( is_string( $hook_info['function'][0] ) ) {
					$class_name = $hook_info['function'][0];
				}
				if ( is_string( $hook_info['function'][1] ) ) {
					$function_name = $hook_info['function'][1];

				}
				return $class_name.'::'.$function_name;
			} elseif ( is_string ( $hook_info['function'] ) ) {	// hook is a function
				return $hook_info['function'];
			}
			return $hook_name;
		}
		
		private static function get_min_int() {
			return defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : -2147483648;	// available since PHP 7.0.0
		}

		private static function get_max_int() {
			return defined( 'PHP_INT_MAX' ) ? PHP_INT_MAX : 2147483647;	// available since PHP 5.0.2
		}
	}
}

?>
