<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomNotice' ) ) {

	class SucomNotice {

		private $p;	// Plugin class object.

		private $plugin_id     = 'sucom';
		private $plugin_ucid   = 'SUCOM';
		private $text_domain   = 'sucom';
		private $dismiss_name  = 'sucom_dismissed';
		private $notices_name  = 'sucom_notices';
		private $nonce_name    = '';
		private $default_ttl   = 600;
		private $label_transl  = false;
		private $doing_dev     = false;
		private $has_shown     = false;
		private $all_types     = array( 'nag', 'err', 'warn', 'inf', 'upd' );	// Sort by importance (most to least).
		private $tb_types      = array( 'err', 'warn', 'inf', 'upd' );
		private $notice_info   = array();
		private $notice_cache  = array();
		private $notice_noload = array();

		public $enabled = true;

		public function __construct( $plugin = null, $plugin_id = null, $text_domain = null, $label_transl = false ) {

			if ( ! class_exists( 'SucomUtil' ) ) {	// Just in case.

				require_once trailingslashit( dirname( __FILE__ ) ) . 'util.php';
			}

			if ( ! class_exists( 'SucomUtilWP' ) ) {	// Just in case.

				require_once trailingslashit( dirname( __FILE__ ) ) . 'util-wp.php';
			}

			$this->set_config( $plugin, $plugin_id, $text_domain, $label_transl );

			$this->add_wp_hooks();
		}

		public function set_textdomain( $text_domain = null ) {

			if ( null !== $text_domain ) {

				$this->text_domain = $text_domain;

			} elseif ( ! empty( $this->p->cf[ 'plugin' ][ $this->plugin_id ][ 'text_domain' ] ) ) {

				$this->text_domain = $this->p->cf[ 'plugin' ][ $this->plugin_id ][ 'text_domain' ];
			}
		}

		public function set_label_transl( $label_transl = false ) {

			if ( false !== $label_transl ) {

				$this->label_transl = $label_transl;

			} elseif ( ! empty( $this->p->cf[ 'notice' ][ 'title' ] ) ) {

				$this->label_transl = sprintf( __( '%s Notice', $this->text_domain ),
					_x( $this->p->cf[ 'notice' ][ 'title' ], 'notice title', $this->text_domain ) );

			} else {

				$this->label_transl = __( 'Notice', $this->text_domain );
			}
		}

		public function is_enabled() {

			return $this->enabled ? true : false;
		}

		public function enable( $state = true ) {

			$prev_state = $this->is_enabled();

			$this->enabled = $state;

			return $prev_state;	// Return the previous state to save and restore.
		}

		public function disable( $state = false ) {

			return $this->enable( $state );	// Return the previous state to save and restore.
		}

		/*
		 * Note that only a single nag message is shown at a time.
		 */
		public function nag( $msg_text, $user_id = null, $notice_key = false, $dismiss_time = false, $payload = array() ) {

			$this->add_notice( 'nag', $msg_text, $user_id, $notice_key, $dismiss_time, $payload );
		}

		public function err( $msg_text, $user_id = null, $notice_key = false, $dismiss_time = false, $payload = array() ) {

			$this->add_notice( 'err', $msg_text, $user_id, $notice_key, $dismiss_time, $payload );
		}

		public function warn( $msg_text, $user_id = null, $notice_key = false, $dismiss_time = false, $payload = array() ) {

			$this->add_notice( 'warn', $msg_text, $user_id, $notice_key, $dismiss_time, $payload );
		}

		public function inf( $msg_text, $user_id = null, $notice_key = false, $dismiss_time = false, $payload = array() ) {

			$this->add_notice( 'inf', $msg_text, $user_id, $notice_key, $dismiss_time, $payload );
		}

		public function upd( $msg_text, $user_id = null, $notice_key = false, $dismiss_time = false, $payload = array() ) {

			$this->add_notice( 'upd', $msg_text, $user_id, $notice_key, $dismiss_time, $payload );
		}

		/*
		 * Clear a message type, message text, notice key from the notice cache, or clear all notices.
		 */
		public function clear( $msg_type = '', $notice_key = false, $user_id = null ) {

			$cur_uid = get_current_user_id();	// Always returns an integer.

			if ( is_array( $user_id ) ) {

				$clear_uids = $user_id;

			} else {

				$user_id = is_numeric( $user_id ) ? (int) $user_id : $cur_uid;	// $user_id can be true, false, null, or numeric.

				if ( empty( $user_id ) ) {	// User ID is 0 (cron user, for example).

					return false;
				}

				$clear_uids = array( $user_id );
			}

			unset( $user_id );	// A reminder that we are re-using this variable name below.

			$clear_types = empty( $msg_type ) ? $this->all_types : array( (string) $msg_type );

			foreach ( $clear_uids as $user_id ) {

				$this->load_notice_cache( $user_id );	// Read and merge notices from transient cache.

				foreach ( $clear_types as $msg_type ) {

					if ( ! isset( $this->notice_cache[ $user_id ][ $msg_type ] ) ) {	// Just in case.

						continue;
					}

					foreach ( $this->notice_cache[ $user_id ][ $msg_type ] as $msg_key => $payload ) {

						if ( empty( $notice_key ) || ( ! empty( $payload[ 'notice_key' ] ) && $notice_key === $payload[ 'notice_key' ] ) ) {

							$this->unload_notice_cache( $user_id, $msg_type, $msg_key );
						}
					}
				}

				/*
				 * Save the notice cache now.
				 */
				if ( $cur_uid !== $user_id ) {

					$this->update_notice_cache( $user_id );
				}
			}
		}

		/*
		 * Clear a single notice key from the notice cache.
		 */
		public function clear_key( $notice_key, $user_id = null ) {

			$this->clear( $msg_type = '', $notice_key, $user_id );
		}

		/*
		 * Set reference values for admin notices.
		 */
		public function set_ref( $url = null, $mod = false, $context_transl = null ) {

			$this->notice_info[] = array(
				'url'            => $url,
				'mod'            => $mod,
				'context_transl' => $context_transl,
			);

			return $url;
		}

		/*
		 * Restore previous reference values for admin notices.
		 */
		public function unset_ref( $url = null ) {

			if ( null === $url || $this->is_ref_url( $url ) ) {

				array_pop( $this->notice_info );

				return true;

			}

			return false;
		}

		public function get_ref( $ref_key = false, $text_prefix = '', $text_suffix = '' ) {

			$refs = end( $this->notice_info );	// Get the last reference added.

			if ( 'edit' === $ref_key ) {

				$link = '';

				if ( ! empty( $refs[ 'mod' ] ) ) {

					if ( ! empty( $refs[ 'mod' ][ 'id' ] ) && is_numeric( $refs[ 'mod' ][ 'id' ] ) ) {

						if ( $refs[ 'mod' ][ 'is_comment' ] ) {

							$link = get_edit_comment_link( $refs[ 'mod' ][ 'id' ] );

						} elseif ( $refs[ 'mod' ][ 'is_post' ] ) {

							$link = get_edit_post_link( $refs[ 'mod' ][ 'id' ], $display = false );

						} elseif ( $refs[ 'mod' ][ 'is_user' ] ) {

							$link = get_edit_user_link( $refs[ 'mod' ][ 'id' ] );

						} elseif ( $refs[ 'mod' ][ 'is_term' ] ) {

							$link = get_edit_term_link( $refs[ 'mod' ][ 'id' ], $refs[ 'mod' ][ 'tax_slug' ] );
						}
					}
				}

				return empty( $link ) ? '' : $text_prefix . $link . $text_suffix;

			} elseif ( false !== $ref_key ) {

				if ( isset( $refs[ $ref_key ] ) ) {

					return $text_prefix . $refs[ $ref_key ] . $text_suffix;
				}

				return null;

			}

			return $refs;
		}

		public function get_ref_url_html() {

			$ref_html = '';

			if ( $url = $this->get_ref( $ref_key = 'url' ) ) {

				/*
				 * Show a shorter relative URL, if possible.
				 */
				$pretty_url = strtolower( str_replace( home_url(), '', $url ) );

				$context_transl = $this->get_ref( $ref_key = 'context_transl' );

				$context_transl = empty( $context_transl ) ?
					'<a href="' . $url . '">' . $pretty_url . '</a>' :
					'<a href="' . $url . '">' . $context_transl . '</a>';

				/*
				 * Returns an empty string or a clickable (Edit) link.
				 */
				$edit_html = $this->get_ref(
					$ref_key     = 'edit',
					$text_prefix = ' (<a href="',
					$text_suffix = '">' . __( 'Edit', $this->text_domain ) . '</a>)'
				);

				$ref_html .= ' <p class="reference-message">' .
					sprintf( __( 'Reference: %s', $this->text_domain ),
						$context_transl . $edit_html ) . '</p>';
			}

			return $ref_html;
		}

		public function is_ref_url( $url = null ) {

			if ( null === $url || $url === $this->get_ref( $ref_key = 'url' ) ) {

				return true;

			}

			return false;
		}

		public function is_admin_pre_notices( $notice_key = false, $user_id = null ) {

			if ( is_admin() ) {

				if ( ! empty( $notice_key ) ) {

					/*
					 * If notice is dismissed, say that we've already shown the notices.
					 */
					if ( $this->is_dismissed( $notice_key, $user_id ) ) {

						if ( ! empty( $this->p->debug->enabled ) ) {

							$this->p->debug->log( 'returning false: ' . $notice_key . ' is dismissed' );
						}

						return false;
					}
				}

				if ( $this->has_shown ) {

					if ( ! empty( $this->p->debug->enabled ) ) {

						$this->p->debug->log( 'returning false: notices have been shown' );
					}

					return false;
				}

			} else {

				if ( ! empty( $this->p->debug->enabled ) ) {

					$this->p->debug->log( 'returning false: is not admin' );
				}

				return false;

			}

			return true;
		}

		public function clear_dismissed( $notice_key = false, $user_id = null ) {

			$this->is_dismissed( $notice_key, $user_id, $force_expire = true );
		}

		public function reset_dismissed( $user_id = null ) {

			$cur_uid = get_current_user_id();					// Always returns an integer.
			$user_id = is_numeric( $user_id ) ? (int) $user_id : $cur_uid;	// $user_id can be true, false, null, or numeric.

			if ( $user_id ) {

				delete_user_option( $user_id, $this->dismiss_name, $global = false );
			}
		}

		public function is_dismissed( $notice_keys = false, $user_id = null, $force_expire = false ) {

			if ( empty( $notice_keys ) || ! $this->can_dismiss() ) {	// Just in case.

				return false;
			}

			$cur_uid = get_current_user_id();					// Always returns an integer.
			$user_id = is_numeric( $user_id ) ? (int) $user_id : $cur_uid;	// $user_id can be true, false, null, or numeric.

			if ( empty( $user_id ) ) {	// User ID is 0 (cron user, for example).

				return false;
			}

			$user_dismissed   = get_user_option( $this->dismiss_name, $user_id );	// Note that $user_id is the second argument.
			$update_dismissed = false;

			if ( ! is_array( $user_dismissed ) ) {	// Nothing to do.

				return false;
			}

			if ( ! is_array( $notice_keys ) ) {

				$notice_keys = array( $notice_keys );
			}

			foreach ( $notice_keys as $notice_key ) {

				if ( isset( $user_dismissed[ $notice_key ] ) ) {	// Notice has been dismissed.

					$current_time = time();

					$dismiss_time = $user_dismissed[ $notice_key ];

					if ( ! $force_expire && ( empty( $dismiss_time ) || $dismiss_time > $current_time ) ) {

						return true;
					}

					unset( $user_dismissed[ $notice_key ] );	// Dismiss time has expired.

					$update_dismissed = true;
				}
			}

			if ( $update_dismissed ) {

				if ( empty( $user_dismissed ) ) {

					delete_user_option( $user_id, $this->dismiss_name, $global = false );

				} else {

					update_user_option( $user_id, $this->dismiss_name, $user_dismissed, $global = false );
				}
			}

			return false;
		}

		public function can_dismiss() {

			global $wp_version;

			if ( version_compare( $wp_version, '4.2', '>=' ) ) {

				return true;
			}

			return false;
		}

		/*
		 * Hooked to the 'in_admin_header' action.
		 *
		 * The 'in_admin_header' action executes at the beginning of the content section in an admin page.
		 */
		public function admin_header_notices() {

			add_action( 'all_admin_notices', array( $this, 'show_admin_notices' ), -1000 );
		}

		public function show_admin_notices() {

			$user_id = get_current_user_id();	// Always returns an integer.

			$notice_types = $this->all_types;

			echo $this->get_notice_style();		// Always show the notice stylesheet.

			/*
			 * If toolbar notices are being used, exclude these from being shown.
			 */
			$tb_types_showing = $this->get_tb_types_showing();	// Returns false or array.

			if ( is_array( $tb_types_showing ) ) {	// Admin toolbar is available.

				if ( ! empty( $tb_types_showing ) ) {

					$notice_types = array_diff( $notice_types, $tb_types_showing );
				}

			} elseif ( is_admin() ) {	// Just in case.

				/*
				 * SucomNotice->get_tb_types_showing() will always return false for these types of requests.
				 *
				 * See is_admin_bar_showing() in wordpress/wp-includes/admin-bar.php for details.
				 */
				if ( defined( 'XMLRPC_REQUEST' ) || defined( 'DOING_AJAX' ) || defined( 'IFRAME_REQUEST' ) || wp_is_json_request() || is_embed() ) {

					return;
				}

				$msg_text = sprintf( __( 'The WordPress admin toolbar appears to be disabled (ie. the WordPress <code>%s</code> function returned false).',
					$this->text_domain ), 'is_admin_bar_showing()' ) . ' ';

				$msg_text .= __( 'As a consequence, showing discreet notices in the admin toolbar is not possible.', $this->text_domain ) . ' ';

				$msg_text .= __( 'Please diagnose the issue to re-enable the admin toolbar.', $this->text_domain ) . ' ';

				$notice_key = 'is_admin-is_admin_bar_showing-returned-false';

				/*
				 * Clear all notices and show only this error.
				 */
				$this->clear();

				$this->err( $msg_text, $user_id, $notice_key );
			}

			if ( empty( $notice_types ) ) {	// Just in case.

				return;
			}

			/*
			 * An alternative to the 'admin_head' action hook to add notices.
			 */
			do_action( 'wpsso_show_admin_notices', $user_id );

			/*
			 * Exit early if this is a block editor page. The notices will be retrieved using an ajax call on page load
			 * and post save.
			 */
			if ( SucomUtilWP::doing_block_editor() ) {

				if ( ! empty( $this->p->debug->enabled ) ) {

					$this->p->debug->log( 'exiting early: doing block editor is true' );
				}

				return;
			}

			if ( ! empty( $this->p->debug->enabled ) ) {

				$this->p->debug->log( 'doing block editor is false' );
			}

			$nag_html         = '';
			$msg_html         = '';
			$user_dismissed   = $user_id ? get_user_option( $this->dismiss_name, $user_id ) : false;	// Note that $user_id is the second argument.
			$update_dismissed = false;

			$this->has_shown = true;

			$this->load_notice_cache( $user_id );	// Read and merge notices from transient cache.

			$this->load_update_notices( $user_id );

			/*
			 * Loop through all the msg types and show them all.
			 */
			foreach ( $notice_types as $msg_type ) {

				if ( ! isset( $this->notice_cache[ $user_id ][ $msg_type ] ) ) {	// Just in case.

					continue;
				}

				foreach ( $this->notice_cache[ $user_id ][ $msg_type ] as $msg_key => $payload ) {

					$this->unload_notice_cache( $user_id, $msg_type, $msg_key );

					if ( empty( $payload[ 'msg_text' ] ) ) {	// Nothing to show.

						continue;
					}

					/*
					 * Make sure the notice has not exceeded its TTL.
					 *
					 * A 'notice_ttl' value of 0 disables the notice message expiration.
					 */
					if ( ! empty( $payload[ 'notice_time' ] ) && ! empty( $payload[ 'notice_ttl' ] ) ) {

						if ( time() > $payload[ 'notice_time' ] + $payload[ 'notice_ttl' ] ) {

							continue;
						}
					}

					if ( ! empty( $payload[ 'dismiss_time' ] ) ) {	// True or seconds greater than 0.

						/*
						 * Check for automatically hidden errors and/or warnings.
						 */
						if ( ! empty( $payload[ 'notice_key' ] ) && isset( $user_dismissed[ $payload[ 'notice_key' ] ] ) ) {

							$current_time = time();
							$dismiss_time = $user_dismissed[ $payload[ 'notice_key' ] ];	// Get time for key.

							if ( empty( $dismiss_time ) || $dismiss_time > $current_time ) {	// 0 or time in future.

								$payload[ 'hidden' ] = true;

							} else {	// Dismiss has expired.

								unset( $user_dismissed[ $payload[ 'notice_key' ] ] );

								$update_dismissed = true;	// Update the user meta when done.
							}
						}
					}

					if ( 'nag' === $msg_type ) {

						if ( empty( $nag_html ) ) {	// Only show a single nag message at a time.

							$nag_html .= $this->get_notice_html( $msg_type, $payload );
						}

					} else {

						$msg_html .= $this->get_notice_html( $msg_type, $payload );
					}
				}
			}

			/*
			 * Don't save unless we've changed something.
			 */
			if ( $user_id && $update_dismissed ) {

				if ( empty( $user_dismissed ) ) {

					delete_user_option( $user_id, $this->dismiss_name, $global = false );

				} else {

					update_user_option( $user_id, $this->dismiss_name, $user_dismissed, $global = false );
				}
			}

			echo "\n" . '<!-- ' . $this->plugin_id . ' admin notices begin -->' . "\n";

			echo '<div id="' . sanitize_html_class( $this->plugin_id . '-admin-notices-begin' ) . '"></div>' . "\n";

			echo $nag_html . "\n";

			echo $msg_html . "\n";

			echo '<!-- ' . $this->plugin_id . ' admin notices end -->' . "\n";
		}

		public function admin_footer_script() {

			echo $this->get_notice_script();
		}

		public function refresh_notice_style() {

			$this->get_notice_style( $read_cache = false );
		}

		public function ajax_get_notices_json() {

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) {	// Just in case.

				return;

			} elseif ( SucomUtil::get_const( 'DOING_AUTOSAVE' ) ) {

				die( -1 );
			}

			$notice_types = $this->all_types;

			if ( ! empty( $_REQUEST[ '_notice_types' ] ) ) {

				if ( is_array( $_REQUEST[ '_notice_types' ] ) ) {

					$notice_types = $_REQUEST[ '_notice_types' ];

				} else {

					$notice_types = explode( ',', $_REQUEST[ '_notice_types' ] );
				}
			}

			if ( ! empty( $_REQUEST[ '_exclude_types' ] ) ) {

				if ( is_array( $_REQUEST[ '_exclude_types' ] ) ) {

					$exclude_types = $_REQUEST[ '_exclude_types' ];

				} else {

					$exclude_types = explode( ',', $_REQUEST[ '_exclude_types' ] );
				}

				$notice_types = array_diff( $notice_types, $exclude_types );
			}

			if ( empty( $notice_types ) ) {	// Just in case.

				die( -1 );
			}

			check_ajax_referer( $this->nonce_name, '_ajax_nonce', $die = true );

			$user_id          = get_current_user_id();	// Always returns an integer.
			$user_dismissed   = $user_id ? get_user_option( $this->dismiss_name, $user_id ) : false;	// Note that $user_id is the second argument.
			$update_dismissed = false;
			$json_notices     = array();
			$ajax_context     = empty( $_REQUEST[ 'context' ] ) ? '' : $_REQUEST[ 'context' ];	// 'block_editor' or 'toolbar_notices'

			$this->has_shown = true;

			$this->load_notice_cache( $user_id );	// Read and merge notices from transient cache.

			$this->load_update_notices( $user_id );

			/*
			 * Loop through all the msg types and show them all.
			 */
			foreach ( $notice_types as $msg_type ) {

				if ( ! isset( $this->notice_cache[ $user_id ][ $msg_type ] ) ) {	// Just in case.

					continue;
				}

				foreach ( $this->notice_cache[ $user_id ][ $msg_type ] as $msg_key => $payload ) {

					$this->unload_notice_cache( $user_id, $msg_type, $msg_key );

					if ( empty( $payload[ 'msg_text' ] ) ) {	// Nothing to show.

						continue;
					}

					/*
					 * Make sure the notice has not exceeded its TTL.
					 *
					 * A 'notice_ttl' value of 0 disables the notice message expiration.
					 */
					if ( ! empty( $payload[ 'notice_time' ] ) && ! empty( $payload[ 'notice_ttl' ] ) ) {

						if ( time() > $payload[ 'notice_time' ] + $payload[ 'notice_ttl' ] ) {

							continue;
						}
					}

					if ( ! empty( $payload[ 'dismiss_time' ] ) ) {	// True or seconds greater than 0.

						/*
						 * Check for automatically hidden errors and/or warnings.
						 */
						if ( ! empty( $payload[ 'notice_key' ] ) && isset( $user_dismissed[ $payload[ 'notice_key' ] ] ) ) {

							$current_time = time();

							$dismiss_time = $user_dismissed[ $payload[ 'notice_key' ] ];	// Get time for key.

							if ( empty( $dismiss_time ) || $dismiss_time > $current_time ) {	// 0 or time in future.

								$payload[ 'hidden' ] = true;

							} else {	// Dismiss has expired.

								unset( $user_dismissed[ $payload[ 'notice_key' ] ] );

								$update_dismissed = true;	// Update the user meta when done.
							}
						}
					}

					$payload[ 'msg_html' ] = $this->get_notice_html( $msg_type, $payload, $notice_alt = true );

					$json_notices[ $msg_type ][ $msg_key ] = $payload;
				}
			}

			/*
			 * Don't save unless we've changed something.
			 */
			if ( $user_id && $update_dismissed ) {

				if ( empty( $user_dismissed ) ) {

					delete_user_option( $user_id, $this->dismiss_name, $global = false );

				} else {

					update_user_option( $user_id, $this->dismiss_name, $user_dismissed, $global = false );
				}
			}

			$json_encoded = wp_json_encode( $json_notices );

			die( $json_encoded );
		}

		public function ajax_dismiss_notice() {

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) {	// Just in case.

				return;
			}

			$user_id      = get_current_user_id();	// Always returns an integer.
			$dismiss_info = array();

			if ( empty( $user_id ) || ! current_user_can( 'edit_user', $user_id ) ) {

				die( -1 );
			}

			check_ajax_referer( __FILE__, 'dismiss_nonce', $die = true );

			/*
			 * Quick sanitation of input values.
			 */
			foreach ( array( 'notice_key', 'dismiss_time' ) as $key ) {

				$dismiss_info[ $key ] = sanitize_text_field( filter_input( INPUT_POST, $key ) );
			}

			if ( empty( $dismiss_info[ 'notice_key' ] ) ) {	// Just in case.

				die( -1 );
			}

			$user_dismissed = get_user_option( $this->dismiss_name, $user_id );	// Note that $user_id is the second argument.

			if ( ! is_array( $user_dismissed ) ) {

				$user_dismissed = array();
			}

			if ( empty( $dismiss_info[ 'dismiss_time' ] ) || ! is_numeric( $dismiss_info[ 'dismiss_time' ] ) ) {

				$user_dismissed[ $dismiss_info[ 'notice_key' ] ] = 0;

			} else {

				$user_dismissed[ $dismiss_info[ 'notice_key' ] ] = time() + $dismiss_info[ 'dismiss_time' ];
			}

			update_user_option( $user_id, $this->dismiss_name, $user_dismissed, $global = false );

			die( '1' );
		}

		/*
		 * Returns false or an array of notice types to include in the toolbar menu.
		 *
		 * Called by WpssoScript->get_admin_page_script_data() to define the types shown for ajax calls.
		 */
		public function get_tb_types_showing() {

			/*
			 * is_admin_bar_showing() should always return true in the back-end for a standard request (ie. not xmlrpc, ajax, iframe).
			 */
			$tb_types_showing = is_admin_bar_showing() ? $this->tb_types : false;

			return $tb_types_showing;
		}

		/*
		 * Called by the WordPress 'shutdown' action. Save notices for all user IDs in the notice cache.
		 */
		public function shutdown_notice_cache() {

			foreach ( $this->notice_cache as $user_id => $msg_types ) {

				$this->update_notice_cache( $user_id );
			}
		}

		/*
		 * Set property values for text domain, notice label, etc.
		 */
		private function set_config( $plugin = null, $plugin_id = null, $text_domain = null, $label_transl = false ) {

			if ( null !== $plugin ) {

				$this->p =& $plugin;

				if ( ! empty( $this->p->debug->enabled ) ) {

					$this->p->debug->mark();
				}
			}

			/*
			 * Set the lower and upper case acronyms.
			 */
			if ( null !== $plugin_id ) {

				$this->plugin_id = $plugin_id;

			} elseif ( ! empty( $this->p->id ) ) {

				$this->plugin_id = $this->p->id;
			}

			$this->plugin_ucid = strtoupper( $this->plugin_id );

			/*
			 * Set the text domain.
			 */
			$this->set_textdomain( $text_domain );

			/*
			 * Set the dismiss key name.
			 */
			if ( defined( $this->plugin_ucid . '_DISMISS_NAME' ) ) {

				$this->dismiss_name = constant( $this->plugin_ucid . '_DISMISS_NAME' );

			} else {

				$this->dismiss_name = $this->plugin_id . '_dismissed';
			}

			/*
			 * Set the notices key name.
			 */
			if ( defined( $this->plugin_ucid . '_NOTICES_NAME' ) ) {

				$this->notices_name = constant( $this->plugin_ucid . '_NOTICES_NAME' );

			} else {

				$this->notices_name = $this->plugin_id . '_notices';
			}

			/*
			 * Set the nonce key name.
			 */
			if ( defined( $this->plugin_ucid . '_NONCE_NAME' ) ) {

				$this->nonce_name = constant( $this->plugin_ucid . '_NONCE_NAME' );

			} elseif ( defined( 'NONCE_KEY' ) ) {

				$this->nonce_name = NONCE_KEY;
			}

			/*
			 * Set the translated notice label.
			 */
			$this->set_label_transl( $label_transl );

			/*
			 * Determine if the DEV constant is defined.
			 */
			$this->doing_dev = SucomUtil::get_const( $this->plugin_ucid . '_DEV' );
		}

		/*
		 * Add WordPress action and filters hooks.
		 */
		private function add_wp_hooks() {

			static $do_once = null;	// Just in case.

			if ( true === $do_once ) {

				return;
			}

			$do_once = true;

			$doing_cron = defined( 'DOING_CRON' ) ? DOING_CRON : false;

			if ( is_admin() ) {

				add_action( 'wp_ajax_' . $this->plugin_id . '_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );

				add_action( 'wp_ajax_' . $this->plugin_id . '_get_notices_json', array( $this, 'ajax_get_notices_json' ) );

				/*
				 * The 'in_admin_header' action executes at the beginning of the content section in an admin page.
				 */
				add_action( 'in_admin_header', array( $this, 'admin_header_notices' ), PHP_INT_MAX );

				add_action( 'admin_footer', array( $this, 'admin_footer_script' ) );
			}

			if ( is_admin() || $doing_cron ) {

				add_action( 'shutdown', array( $this, 'shutdown_notice_cache' ), 10, 0 );
			}
		}

		/*
		 * $msg_text can be a single text string, or an array of text strings.
		 */
		private function add_notice( $msg_type, $msg_text, $user_id = null, $notice_key = false, $dismiss_time = false, $payload = array() ) {

			if ( ! $this->enabled ) {	// Just in case.

				return false;
			}

			/*
			 * If $msg_text is an array of text strings, implode the array into a single text string.
			 */
			$msg_text = is_array( $msg_text ) ? implode( $glue = ' ', $msg_text ) : (string) $msg_text;
			$msg_text = trim( $msg_text );

			if ( empty( $msg_type ) || empty( $msg_text ) ) {

				return false;
			}

			$cur_uid = get_current_user_id();				// Always returns an integer.
			$user_id = is_numeric( $user_id ) ? (int) $user_id : $cur_uid;	// $user_id can be true, false, null, or numeric.

			if ( empty( $user_id ) ) {	// User ID is 0 (cron user, for example).

				return false;
			}

			$payload[ 'notice_label' ] = isset( $payload[ 'notice_label' ] ) ? $payload[ 'notice_label' ] : $this->label_transl;
			$payload[ 'notice_key' ]   = empty( $notice_key ) ? false : sanitize_key( $notice_key );
			$payload[ 'notice_time' ]  = time();

			/*
			 * 0 disables notice expiration.
			 */
			$payload[ 'notice_ttl' ]   = isset( $payload[ 'notice_ttl' ] ) ? (int) $payload[ 'notice_ttl' ] : $this->default_ttl;
			$payload[ 'dismiss_time' ] = false;
			$payload[ 'dismiss_diff' ] = isset( $payload[ 'dismiss_diff' ] ) ? $payload[ 'dismiss_diff' ] : null;

			/*
			 * Add dismiss text for dismiss button and notice message.
			 */
			if ( $this->can_dismiss() ) {

				$payload[ 'dismiss_time' ] = $dismiss_time;	// Maybe true, false, 0, or seconds greater than 0.

				if ( null === $payload[ 'dismiss_diff' ] ) {	// Has not been provided, so set a default value.

					$dismiss_suffix_msg = false;

					if ( true === $payload[ 'dismiss_time' ] ) {	// True.

						$payload[ 'dismiss_diff' ] = __( 'Forever', $this->text_domain );

						$dismiss_suffix_msg = __( 'This notice can be dismissed permanently.', $this->text_domain );

					} elseif ( empty( $payload[ 'dismiss_time' ] ) ) {	// False or 0 seconds.

						$payload[ 'dismiss_time' ] = false;

						$payload[ 'dismiss_diff' ] = false;

					} elseif ( is_numeric( $payload[ 'dismiss_time' ] ) ) {	// Seconds greater than 0.

						$payload[ 'dismiss_diff' ] = human_time_diff( 0, $payload[ 'dismiss_time' ] );

						$dismiss_suffix_msg = __( 'This notice can be dismissed for %s.', $this->text_domain );
					}

					if ( ! empty( $payload[ 'dismiss_diff' ] ) && $dismiss_suffix_msg ) {

						$msg_close_div = '';

						if ( '</div>' === substr( $msg_text, -6 ) ) {

							$msg_text = substr( $msg_text, 0, -6 );

							$msg_close_div = '</div>';
						}

						$msg_add_p = '</p>' === substr( $msg_text, -4 ) ? true : false;

						$msg_text .= $msg_add_p || $msg_close_div ? '<p>' : ' ';
						$msg_text .= sprintf( $dismiss_suffix_msg, $payload[ 'dismiss_diff' ] );
						$msg_text .= $msg_add_p || $msg_close_div ? '</p>' : '';
						$msg_text .= $msg_close_div;
					}
				}
			}

			/*
			 * Maybe add a reference URL at the end.
			 */
			$msg_text .= $this->get_ref_url_html();

			$payload[ 'msg_text' ]   = preg_replace( '/<!--spoken-->(.*?)<!--\/spoken-->/Us', ' ', $msg_text );
			$payload[ 'msg_spoken' ] = preg_replace( '/<!--not-spoken-->(.*?)<!--\/not-spoken-->/Us', ' ', $msg_text );
			$payload[ 'msg_spoken' ] = SucomUtil::decode_html( SucomUtil::strip_html( $payload[ 'msg_spoken' ] ) );

			$msg_key = empty( $payload[ 'notice_key' ] ) ? sanitize_key( $payload[ 'msg_spoken' ] ) : $payload[ 'notice_key' ];

			$this->notice_cache[ $user_id ][ $msg_type ][ $msg_key ] = $payload;

			/*
			 * Save the notice cache now.
			 */
			if ( $cur_uid !== $user_id ) {

				$this->update_notice_cache( $user_id );
			}
		}

		private function unload_notice_cache( $user_id, $msg_type, $msg_key ) {

			unset( $this->notice_cache[ $user_id ][ $msg_type ][ $msg_key ] );

			if ( empty( $this->notice_cache[ $user_id ][ $msg_type ] ) ) {

				unset( $this->notice_cache[ $user_id ][ $msg_type ] );
			}

			$this->notice_noload[ $user_id ][ $msg_type ][ $msg_key ] = true;
		}

		/*
		 * Merge notice cache with saved notices (without overwriting).
		 */
		private function load_notice_cache( $user_id = null ) {

			$cur_uid = get_current_user_id();				// Always returns an integer.
			$user_id = is_numeric( $user_id ) ? (int) $user_id : $cur_uid;	// $user_id can be true, false, null, or numeric.
			$result  = $this->get_notice_cache( $user_id );			// Always returns an array.

			if ( ! empty( $result ) ) {

				foreach ( $this->all_types as $msg_type ) {

					if ( ! empty( $result[ $msg_type ] ) ) {

						foreach ( $result[ $msg_type ] as $msg_key => $payload ) {

							if ( ! isset( $this->notice_cache[ $user_id ][ $msg_type ][ $msg_key ] ) &&
								! isset( $this->notice_noload[ $user_id ][ $msg_type ][ $msg_key ] ) ) {

								$this->notice_cache[ $user_id ][ $msg_type ][ $msg_key ] = $payload;

								$this->notice_noload[ $user_id ][ $msg_type ][ $msg_key ] = true;
							}
						}
					}
				}
			}
		}

		private function load_update_notices( $user_id ) {

			if ( ! class_exists( 'SucomUpdate' ) ) {

				return;
			}

			if ( empty( $this->p->cf[ 'plugin' ] ) ) {

				return;
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( SucomUpdate::is_configured( $ext ) ) {

					foreach ( array( 'inf', 'err' ) as $type ) {

						if ( $msg = SucomUpdate::get_umsg( $ext, $type ) ) {

							$msg_text   = preg_replace( '/<!--spoken-->(.*?)<!--\/spoken-->/Us', ' ', $msg );
							$msg_spoken = preg_replace( '/<!--not-spoken-->(.*?)<!--\/not-spoken-->/Us', ' ', $msg );
							$msg_spoken = SucomUtil::decode_html( SucomUtil::strip_html( $msg_spoken ) );
							$msg_key    = sanitize_key( $msg_spoken );

							$this->notice_cache[ $user_id ][ $type ][ $msg_key ] = array(
								'notice_label' => $this->label_transl,
								'notice_key'   => $msg_key,
								'msg_text'     => $msg_text,
								'msg_spoken'   => $msg_spoken,
							);
						}
					}
				}
			}
		}

		private function update_notice_cache( $user_id = null ) {

			$cur_uid = get_current_user_id();				// Always returns an integer.
			$user_id = is_numeric( $user_id ) ? (int) $user_id : $cur_uid;	// $user_id can be true, false, null, or numeric.
			$update  = true;
			$result  = false;

			if ( ! empty( $user_id ) ) {	// Just in case.

				if ( $cur_uid !== $user_id ) {

					$this->load_notice_cache( $user_id );	// Read and merge notices from transient cache.
				}

				$result = update_user_option( $user_id, $this->notices_name, $this->notice_cache[ $user_id ], $global = false );
			}

			$this->notice_cache[ $user_id ] = array();

			return $result;
		}

		private function get_notice_cache( $user_id = null ) {

			$cur_uid = get_current_user_id();				// Always returns an integer.
			$user_id = is_numeric( $user_id ) ? (int) $user_id : $cur_uid;	// $user_id can be true, false, null, or numeric.
			$result  = array();

			if ( ! empty( $user_id ) ) {	// Nothing to do.

				$result = get_user_option( $this->notices_name, $user_id );

				if ( ! is_array( $result ) ) {	// Always return an array.

					$result = array();
				}
			}

			return $result;
		}

		/*
		 * Use a reference for the $payload variable so we can modify the 'msg_text' element and remove text that should be
		 * shown only once.
		 */
		private function get_notice_html( $msg_type, array &$payload, $notice_alt = false ) {

			/*
			 * Add an 'inline' class in toolbar notices to prevent WordPress from moving the notice.
			 *
			 * See wordpress/wp-admin/js/common.js:1083
			 */
			$notice_type    = $notice_alt ? 'notice notice-alt inline' : 'notice';
			$notice_display = 'block';

			switch ( $msg_type ) {

				case 'nag':

					$payload[ 'notice_label' ] = '';	// No label for nag notices.

					$msg_type       = 'nag';
					$css_class      = 'update-nag';
					$notice_display = 'inline-block';

					break;

				case 'err':
				case 'error':

					$msg_type  = 'err';
					$css_class = $notice_type . ' notice-error error';

					break;

				case 'warn':
				case 'warning':

					$msg_type  = 'warn';
					$css_class = $notice_type . ' notice-warning';

					break;

				case 'inf':
				case 'info':

					$msg_type  = 'inf';
					$css_class = $notice_type . ' notice-info';

					break;

				case 'upd':
				case 'updated':

					$msg_type  = 'upd';
					$css_class = $notice_type . ' notice-success updated';

					break;

				default:	// Unknown $msg_type.

					$msg_type  = 'unknown';
					$css_class = $notice_type;

					break;
			}

			$css_id_attr    = empty( $payload[ 'notice_key' ] ) ? '' : ' id="' . $msg_type . '-' . $payload[ 'notice_key' ] . '"';
			$is_dismissible = empty( $payload[ 'dismiss_time' ] ) ? false : true;
			$data_attr      = '';

			if ( $is_dismissible ) {

				$data_attr .= ' data-notice-key="' . ( isset( $payload[ 'notice_key' ] ) ?
					esc_attr( $payload[ 'notice_key' ] ) : '' ). '"';

				$data_attr .= ' data-dismiss-time="' . ( isset( $payload[ 'dismiss_time' ] ) &&
					is_numeric( $payload[ 'dismiss_time' ] ) ?
						esc_attr( $payload[ 'dismiss_time' ] ) : 0 ) . '"';

				$data_attr .= ' data-dismiss-nonce="' . wp_create_nonce( __FILE__ ) . '"';
			}

			$style_attr = ' style="' .
				( empty( $payload[ 'style' ] ) ? '' : $payload[ 'style' ] ) .
				( empty( $payload[ 'hidden' ] ) ? 'display:' . $notice_display . ' !important;' : 'display:none !important;' ) . '"';

			$msg_html = '<div class="' . $this->plugin_id . '-notice ' .
				( $is_dismissible ? $this->plugin_id . '-dismissible ' : '' ) .
				$css_class . '"' . $css_id_attr . $style_attr . $data_attr . '>';	// Display block or none.

			/*
			 * Float the dismiss button on the right, so the button must be added first.
			 */
			if ( ! empty( $payload[ 'dismiss_diff' ] ) && $is_dismissible ) {

				$msg_html .= '<button class="notice-dismiss" type="button">' .
					'<span class="notice-dismiss-text">' . $payload[ 'dismiss_diff' ] . '</span>' .
						'</button><!-- .notice-dismiss -->';
			}

			/*
			 * The notice label can be false, an empty string, or translated string.
			 */
			if ( ! empty( $payload[ 'notice_label' ] ) ) {

				$msg_html .= '<div class="notice-label">' . $payload[ 'notice_label' ] . '</div><!-- .notice-label -->';
			}

			/*
			 * Check to see if there's a section that should be shown only once.
			 */
			if ( preg_match( '/<!-- *show-once *-->.*<!-- *\/show-once *-->/Us', $payload[ 'msg_text' ], $matches ) ) {

				static $show_once = array();

				$match_md5 = md5( $matches[ 0 ] );

				if ( isset( $show_once[ $match_md5 ] ) ) {

					/*
					 * The $payload is a reference variable so we can modify the 'msg_text' element and remove
					 * text that should be shown only once.
					 */
					$payload[ 'msg_text' ] = str_replace( $matches[ 0 ], '', $payload[ 'msg_text' ] );

				} else {

					$show_once[ $match_md5 ] = true;
				}
			}

			$msg_html .= '<div class="notice-message">' . $payload[ 'msg_text' ] . '</div><!-- .notice-message -->';

			$msg_html .= '</div><!-- .' . $this->plugin_id . '-notice -->' . "\n";

			return $msg_html;
		}

		private function get_notice_style( $read_cache = true ) {

			global $wp_version;

			$cache_md5_pre  = $this->plugin_id . '_';
			$cache_exp_secs = WEEK_IN_SECONDS;
			$cache_salt     = __METHOD__ . '(wp_version:' . $wp_version . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			if ( $read_cache && ! $this->doing_dev ) {

				if ( $custom_style_css = get_transient( $cache_id ) ) {

					return '<style type="text/css">' . $custom_style_css . '</style>';
				}
			}

			$custom_style_css = '
				body.wp-admin.has-toolbar-notices #wpadminbar,
				body.wp-admin.is-fullscreen-mode.has-toolbar-notices #wpadminbar,
				body.wp-admin.is-wp-toolbar-disabled.has-toolbar-notices #wpadminbar,
				body.wp-admin.woocommerce-embed-page.has-toolbar-notices #wpadminbar {
					display:block !important;
				}
				body.wp-admin.is-fullscreen-mode.has-toolbar-notices .block-editor__container {
					min-height:calc(100vh - 32px);
				}
				body.wp-admin.is-fullscreen-mode.has-toolbar-notices .interface-interface-skeleton {
					top:32px;
				}
				body.wp-admin.is-fullscreen-mode.has-toolbar-notices .block-editor__container .block-editor-editor-skeleton,
				body.wp-admin.is-fullscreen-mode.has-toolbar-notices .block-editor__container .block-editor-editor-skeleton .editor-post-publish-panel {
					top:32px;
				}
				@keyframes blinker {
					25% { opacity: 0; }
					75% { opacity: 1; }
				}
				.components-notice-list .' . $this->plugin_id . '-notice {
					margin:0;
					min-height:0;
					-webkit-box-shadow:none;
					-moz-box-shadow:none;
					box-shadow:none;
				}
				.components-notice-list .is-dismissible .' . $this->plugin_id . '-notice {
					padding-right:30px;
				}
				.components-notice-list .' . $this->plugin_id . '-notice *,
				#wpadminbar .' . $this->plugin_id . '-notice *,
				.' . $this->plugin_id . '-notice * {
					line-height:1.4em;
				}
				.components-notice-list .' . $this->plugin_id . '-notice .notice-label,
				.components-notice-list .' . $this->plugin_id . '-notice .notice-message,
				.components-notice-list .' . $this->plugin_id . '-notice .notice-dismiss {
					padding:8px;
					margin:0;
					border:0;
					background:inherit;
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices.show-timeout div.ab-sub-wrapper {
					display:block;
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices div.ab-item {
					color:inherit !important;
					background:inherit !important;
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices #' . $this->plugin_id . '-toolbar-notices-icon.ab-icon::before {
					color:#fff;			/* White on background color. */
					background-color:inherit;
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices #' . $this->plugin_id . '-toolbar-notices-count {
					color:#fff;			/* White on background color. */
					background-color:inherit;
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices.toolbar-notices-error {
					background-color:#dc3232;	/* Red. */
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices.toolbar-notices-warning {
					background-color:#ffb900;	/* Yellow. */
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices.toolbar-notices-info {
					background-color:#00a0d2;	/* Blue. */
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices.toolbar-notices-success {
					background-color:#46b450;	/* Green. */
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices #wp-admin-bar-' . $this->plugin_id . '-toolbar-notices-default {
					padding:0;
				}
				#wpadminbar #wp-toolbar li.has-toolbar-notices #wp-admin-bar-' . $this->plugin_id . '-toolbar-notices-container {
					min-width:65vw;
					max-height:90vh;
					overflow-y:scroll;
				}
				@media screen and ( max-width:1330px ) {
					#wpadminbar #wp-toolbar li.has-toolbar-notices #wp-admin-bar-' . $this->plugin_id . '-toolbar-notices-container {
						min-width:70vw;
					}
				}
				#wpadminbar .' . $this->plugin_id . '-notice,
				#wpadminbar .' . $this->plugin_id . '-notice.error,
				#wpadminbar .' . $this->plugin_id . '-notice.updated,
				.' . $this->plugin_id . '-notice,
				.' . $this->plugin_id . '-notice.error,
				.' . $this->plugin_id . '-notice.updated {
					clear:both;
					padding:0;
					-webkit-box-shadow:none;
					-moz-box-shadow:none;
					box-shadow:none;
				}
				#wpadminbar .' . $this->plugin_id . '-notice,
				#wpadminbar .' . $this->plugin_id . '-notice.error,
				#wpadminbar .' . $this->plugin_id . '-notice.updated {
					background:inherit;
					border-bottom:none;
					border-right:none;
				}
				#wpadminbar .' . $this->plugin_id . '-notice > div,
				#wpadminbar .' . $this->plugin_id . '-notice.error > div,
				#wpadminbar .' . $this->plugin_id . '-notice.updated > div {
					min-height:50px;
				}
				#wpadminbar .' . $this->plugin_id . '-notice.notice.notice-alt {
					display:block !important;	/* Fix Squirrly SEO display:none !important. */
					position:static !important;	/* Fix Squirrly SEO position:absolute !important. */
					top:inherit !important;		/* Fix Squirrly SEO top:-1000px !important. */
					height:auto !important;		/* Fix Squirrly SEO height:0 !important. */
				}
				#wpadminbar div.' . $this->plugin_id . '-notice.notice-copy {
					font-size:0.9em;
					line-height:1;
					text-align:center;
					min-height:auto;
				}
				#wpadminbar div.' . $this->plugin_id . '-notice.notice-copy > div {
					min-height:auto;
				}
				#wpadminbar div.' . $this->plugin_id . '-notice.notice-copy div.notice-message {
					display:inline-block;
					padding:5px 20px;
				}
				#wpadminbar div.' . $this->plugin_id . '-notice.notice-copy div.notice-message a {
					font-size:0.9em;
					font-weight:200;
					letter-spacing:0.2px;
				}
				#wpadminbar div.' . $this->plugin_id . '-notice a,
				.' . $this->plugin_id . '-notice a {
					display:inline;
					text-decoration:underline;
					padding:0;
				}
				#wpadminbar div.' . $this->plugin_id . '-notice .notice-label,
				#wpadminbar div.' . $this->plugin_id . '-notice .notice-message,
				#wpadminbar div.' . $this->plugin_id . '-notice .notice-dismiss {
					position:relative;
					display:table-cell;
					padding:20px;
					margin:0;
					border:none;
					vertical-align:top;
					background:inherit;
				}
				@media screen and ( max-width:1200px ) {
					#wpadminbar div.' . $this->plugin_id . '-notice .notice-label {
						display:none;
					}
				}
				.' . $this->plugin_id . '-notice div.notice-actions {
					text-align:center;
					margin:20px 0 15px 0;
				}
				.' . $this->plugin_id . '-notice div.notice-single-button {
					display:inline-block;
					vertical-align:top;
					margin:5px;
				}
				.' . $this->plugin_id . '-notice .notice-label,
				.' . $this->plugin_id . '-notice .notice-message,
				.' . $this->plugin_id . '-notice .notice-dismiss {
					position:relative;
					display:table-cell;
					padding:15px 20px;
					margin:0;
					border:none;
					vertical-align:top;
				}
				.components-notice-list .' . $this->plugin_id . '-notice .notice-dismiss,
				#wpadminbar .' . $this->plugin_id . '-notice .notice-dismiss,
				.' . $this->plugin_id . '-notice .notice-dismiss {
					clear:both;	/* Clear the "Screen Options" tab in nags. */
					display:block;
					float:right;
					top:0;
					right:0;
					padding-left:0;
					padding-bottom:15px;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-label,
				.' . $this->plugin_id . '-notice .notice-label {
					font-weight:600;
					color:#444;			/* Default text color. */
					background-color:#fcfcfc;	/* Default background color. */
					white-space:nowrap;
				}
				#wpadminbar .' . $this->plugin_id . '-notice.notice-error .notice-label,
				.' . $this->plugin_id . '-notice.notice-error .notice-label {
					background-color: #fbeaea;
				}
				#wpadminbar .' . $this->plugin_id . '-notice.notice-warning .notice-label,
				.' . $this->plugin_id . '-notice.notice-warning .notice-label {
					background-color: #fff8e5;
				}
				#wpadminbar .' . $this->plugin_id . '-notice.notice-info .notice-label,
				.' . $this->plugin_id . '-notice.notice-info .notice-label {
					background-color: #e5f5fa;
				}
				#wpadminbar .' . $this->plugin_id . '-notice.notice-success .notice-label,
				.' . $this->plugin_id . '-notice.notice-success .notice-label {
					background-color: #ecf7ed;
				}
				.' . $this->plugin_id . '-notice.notice-success .notice-label::before,
				.' . $this->plugin_id . '-notice.notice-info .notice-label::before,
				.' . $this->plugin_id . '-notice.notice-warning .notice-label::before,
				.' . $this->plugin_id . '-notice.notice-error .notice-label::before {
					font-family:"Dashicons";
					font-size:1.2em;
					vertical-align:bottom;
					margin-right:6px;
				}
				.' . $this->plugin_id . '-notice.notice-error .notice-label::before {
					content:"\f488";	/* megaphone */
				}
				.' . $this->plugin_id . '-notice.notice-warning .notice-label::before {
					content:"\f227";	/* flag */
				}
				.' . $this->plugin_id . '-notice.notice-info .notice-label::before {
					content:"\f537";	/* sticky */
				}
				.' . $this->plugin_id . '-notice.notice-success .notice-label::before {
					content:"\f147";	/* yes */
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message h2,
				.' . $this->plugin_id . '-notice .notice-message h2 {
					font-size:1.2em;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message h3,
				.' . $this->plugin_id . '-notice .notice-message h3 {
					font-size:1.1em;
					margin-top:1.2em;
					margin-bottom:0.8em;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message code,
				.' . $this->plugin_id . '-notice .notice-message code {
					font-family:"Courier", monospace;
					font-size:1em;
					vertical-align:middle;
					padding:0 2px;
					margin:0;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message a,
				.' . $this->plugin_id . '-notice .notice-message a {
					display:inline;
					text-decoration:underline;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message a code,
				.' . $this->plugin_id . '-notice .notice-message a code {
					padding:0;
					vertical-align:middle;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message p,
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message pre,
				.' . $this->plugin_id . '-notice .notice-message p,
				.' . $this->plugin_id . '-notice .notice-message pre {
					margin:0.8em 0 0 0;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message .top,
				.' . $this->plugin_id . '-notice .notice-message .top {
					margin-top:0;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message p.reference-message,
				.' . $this->plugin_id . '-notice .notice-message p.reference-message {
					font-size:0.9em;
					margin:10px 0 0 0;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message p.reference-message a {
					font-size:0.9em;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message p.smaller-message,
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message p.smaller-message a,
				.' . $this->plugin_id . '-notice .notice-message p.smaller-message,
				.' . $this->plugin_id . '-notice .notice-message p.smaller-message a {
					font-size:0.9em;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message ul,
				.' . $this->plugin_id . '-notice .notice-message ul {
					margin:1em 0 1em 3em;
					list-style:disc outside none;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message ol,
				.' . $this->plugin_id . '-notice .notice-message ol {
					margin:1em 0 1em 3em;
					list-style:decimal outside none;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message li,
				.' . $this->plugin_id . '-notice .notice-message li {
					text-align:left;
					margin:5px 0 5px 0;
					padding-left:0.8em;
					list-style:inherit;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message b,
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message b a,
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message strong,
				#wpadminbar .' . $this->plugin_id . '-notice .notice-message strong a,
				.' . $this->plugin_id . '-notice .notice-message b,
				.' . $this->plugin_id . '-notice .notice-message b a,
				.' . $this->plugin_id . '-notice .notice-message strong,
				.' . $this->plugin_id . '-notice .notice-message strong a {
					font-weight:600;
				}
				#wpadminbar .' . $this->plugin_id . '-notice .notice-dismiss .notice-dismiss-text,
				.' . $this->plugin_id . '-notice .notice-dismiss .notice-dismiss-text {
					display:inline-block;
					font-size:12px;
					padding:2px;
					vertical-align:top;
					white-space:nowrap;
				}
				.' . $this->plugin_id . '-notice .notice-message .button-highlight {
					border-color:#0074a2;
					background-color:#daeefc;
				}
				.' . $this->plugin_id . '-notice .notice-message .button-highlight:hover {
					background-color:#c8e6fb;
				}
				.' . $this->plugin_id . '-notice .notice-dismiss::before {
					display:inline-block;
					padding:2px;
				}
			';

			if ( ! $this->doing_dev ) {

				$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->plugin_id );

				set_transient( $cache_id, $custom_style_css, $cache_exp_secs );
			}

			return '<style type="text/css">' . $custom_style_css . '</style>';
		}

		private function get_notice_script() {

			return '
<script>

	jQuery( document ).on( "click", "div.' . $this->plugin_id . '-dismissible > button.notice-dismiss, div.' .
		$this->plugin_id . '-dismissible .dismiss-on-click", function() {

		var notice = jQuery( this ).closest( ".' . $this->plugin_id . '-dismissible" );

		var dismiss_msg = jQuery( this ).data( "dismiss-msg" );

		var ajaxDismissData = {
			action: "' . $this->plugin_id . '_dismiss_notice",
			notice_key: notice.data( "notice-key" ),
			dismiss_time: notice.data( "dismiss-time" ),
			dismiss_nonce: notice.data( "dismiss-nonce" ),
		}

		if ( notice.data( "notice-key" ) ) {

			jQuery.post( ajaxurl, ajaxDismissData );
		}

		/*
		 * We use remove() instead of hide() for containers with "display:block !important;".
		 */
		if ( dismiss_msg ) {

			notice.children( "button.notice-dismiss" ).remove();

			jQuery( this ).closest( "div.notice-message" ).html( dismiss_msg );

		} else {

			notice.remove();
		}
	} );

</script>' . "\n";
		}
	}
}
