<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomNotice' ) ) {

	class SucomNotice {

		private $p;
		private $lca          = 'sucom';
		private $text_domain  = 'sucom';
		private $label_transl = false;
		private $dis_name     = 'sucom_dismissed';
		private $hide_err     = false;
		private $hide_warn    = false;
		private $tb_notices   = false;
		private $has_shown    = false;
		private $all_types    = array( 'nag', 'err', 'warn', 'upd', 'inf' );	// Sort by importance.
		private $ref_cache    = array();
		private $notice_cache = array();

		public $enabled = true;

		public function __construct( $plugin = null, $lca = null, $text_domain = null, $label_transl = false ) {

			static $do_once = null;	// Just in case.

			if ( null === $do_once ) {

				$do_once = true;

				$this->set_config( $plugin, $lca, $text_domain, $label_transl );

				$this->add_actions();
			}
		}

		/**
		 * Set property values for text domain, notice label, etc.
		 */
		private function set_config( $plugin = null, $lca = null, $text_domain = null, $label_transl = false ) {

			if ( $plugin !== null ) {
				$this->p =& $plugin;
				if ( ! empty( $this->p->debug->enabled ) ) {
					$this->p->debug->mark();
				}
			}

			if ( $lca !== null ) {
				$this->lca = $lca;
			} elseif ( ! empty( $this->p->lca ) ) {
				$this->lca = $this->p->lca;
			}

			if ( $text_domain !== null ) {
				$this->text_domain = $text_domain;
			} elseif ( ! empty( $this->p->cf['plugin'][$this->lca]['text_domain'] ) ) {
				$this->text_domain = $this->p->cf['plugin'][$this->lca]['text_domain'];
			}

			if ( $label_transl !== false ) {
				$this->label_transl = $label_transl;	// Argument is already translated.
			} elseif ( ! empty( $this->p->cf['menu']['title'] ) ) {
				$this->label_transl = sprintf( __( '%s Notice', $this->text_domain ),
					_x( $this->p->cf['menu']['title'], 'menu title', $this->text_domain ) );
			} else {
				$this->label_transl = __( 'Notice', $this->text_domain );
			}

			$uca = strtoupper( $this->lca );

			$this->dis_name   = defined( $uca . '_DISMISS_NAME' ) ? constant( $uca . '_DISMISS_NAME' ) : $this->lca . '_dismissed';
			$this->hide_err   = defined( $uca . '_HIDE_ALL_ERRORS' ) ? constant( $uca . '_HIDE_ALL_ERRORS' ) : false;
			$this->hide_warn  = defined( $uca . '_HIDE_ALL_WARNINGS' ) ? constant( $uca . '_HIDE_ALL_WARNINGS' ) : false;
			$this->tb_notices = defined( $uca . '_TOOLBAR_NOTICES' ) ? constant( $uca . '_TOOLBAR_NOTICES' ) : false;

			if ( $this->tb_notices === true ) {
				$this->tb_notices = array( 'err', 'warn', 'inf' );
			}

			if ( empty( $this->tb_notices ) || ! is_array( $this->tb_notices ) ) {	// Quick sanity check.
				$this->tb_notices = false;
			}
		}

		private function add_actions() {
			if ( is_admin() ) {
				add_action( 'wp_ajax_' . $this->lca . '_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
				add_action( 'wp_ajax_' . $this->lca . '_get_notices_json', array( $this, 'ajax_get_notices_json' ) );
				add_action( 'in_admin_header', array( $this, 'hook_admin_notices' ), PHP_INT_MAX );
				add_action( 'admin_footer', array( $this, 'admin_footer_script' ) );
				add_action( 'shutdown', array( $this, 'shutdown_notice_cache' ) );
			}
		}

		public function nag( $msg_text, $user_id = true, $dismiss_key = false ) {
			$this->log( 'nag', $msg_text, $user_id, $dismiss_key, false );	// $dismiss_time is false
		}

		public function upd( $msg_text, $user_id = true, $dismiss_key = false, $dismiss_time = false ) {
			$this->log( 'upd', $msg_text, $user_id, $dismiss_key, $dismiss_time );
		}

		public function inf( $msg_text, $user_id = true, $dismiss_key = false, $dismiss_time = false ) {
			$this->log( 'inf', $msg_text, $user_id, $dismiss_key, $dismiss_time );
		}

		public function err( $msg_text, $user_id = true, $dismiss_key = false, $dismiss_time = false ) {
			$this->log( 'err', $msg_text, $user_id, $dismiss_key, $dismiss_time );
		}

		public function warn( $msg_text, $user_id = true, $dismiss_key = false, $dismiss_time = false, $no_unhide = false ) {
			$payload = array( 'no-unhide' => $no_unhide ? true : false );
			$this->log( 'warn', $msg_text, $user_id, $dismiss_key, $dismiss_time, $payload );
		}

		public function log( $msg_type, $msg_text, $user_id = true, $dismiss_key = false, $dismiss_time = false, $payload = array() ) {

			if ( empty( $msg_type ) || empty( $msg_text ) ) {
				return;
			}

			if ( ! is_numeric( $user_id ) ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = $user_id;
			}

			$payload['dismiss_key'] = empty( $dismiss_key ) ? false : sanitize_key( $dismiss_key );
			$payload['dismiss_time'] = false;
			$payload['dismiss_diff'] = isset( $payload['dismiss_diff'] ) ? $payload['dismiss_diff'] : null;

			/**
			 * Add dismiss text for dismiss button and notice message.
			 */
			if ( $msg_type !== 'nag' && $this->can_dismiss() ) {	// Do not allow dismiss of nag messages.

				$payload['dismiss_time'] = $dismiss_time;	// Maybe true, false, 0, or seconds greater than 0.

				if ( $payload['dismiss_diff'] === null ) {

					$dismiss_suffix_msg = false;

					if ( $payload['dismiss_time'] === true ) {	// True.

						$payload['dismiss_diff'] = __( 'Forever', $this->text_domain );

						$dismiss_suffix_msg = __( 'This notice can be dismissed permanently.', $this->text_domain );

					} elseif ( empty( $payload['dismiss_time'] ) ) {	// False or 0 seconds.

						// Nothing to do.

					} elseif ( is_numeric( $payload['dismiss_time'] ) ) {	// Seconds greater than 0.

						$payload['dismiss_diff'] = human_time_diff( 0, $payload['dismiss_time'] );

						$dismiss_suffix_msg = __( 'This notice can be dismissed for %s.', $this->text_domain );
					}

					if ( ! empty( $payload['dismiss_diff'] ) && $dismiss_suffix_msg ) {

						$msg_text = trim( $msg_text );
						$msg_close_div = '';

						if ( substr( $msg_text, -6 ) === '</div>' ) {
							$msg_text = substr( $msg_text, 0, -6 );
							$msg_close_div = '</div>';
						}

						$msg_add_p = substr( $msg_text, -4 ) === '</p>' ? true : false;

						$msg_text .= $msg_add_p || $msg_close_div ? '<p>' : ' ';
						$msg_text .= sprintf( $dismiss_suffix_msg, $payload['dismiss_diff'] );
						$msg_text .= $msg_add_p || $msg_close_div ? '</p>' : '';
						$msg_text .= $msg_close_div;
					}
				}
			}

			/**
			 * Maybe add a reference URL at the end.
			 */
			$msg_text .= $this->get_ref_url_html();

			$payload['msg_text'] = preg_replace( '/<!--spoken-->(.*?)<!--\/spoken-->/Us', ' ', $msg_text );
			$payload['msg_spoken'] = preg_replace( '/<!--not-spoken-->(.*?)<!--\/not-spoken-->/Us', ' ', $msg_text );
			$payload['msg_spoken'] = SucomUtil::decode_html( SucomUtil::strip_html( $payload['msg_spoken'] ) );

			$msg_key = empty( $payload['dismiss_key'] ) ? sanitize_key( $payload['msg_spoken'] ) : $payload['dismiss_key'];

			/**
			 * Returns a reference to the cache array.
			 */
			$user_notices =& $this->get_notice_cache( $user_id );

			/**
			 * User notices are saved on shutdown.
			 */
			$user_notices[$msg_type][$msg_key] = $payload;
		}

		public function trunc_key( $dismiss_key, $user_id = true ) {
			$this->trunc( '', '', $dismiss_key, $user_id );
		}

		public function trunc_all() {
			$this->trunc( '', '', false, 'all' );
		}

		public function trunc( $msg_type = '', $msg_text = '', $dismiss_key = false, $user_id = true ) {

			if ( $user_id === 'all' ) {
				$user_ids = $this->get_all_user_ids();
			} elseif ( is_array( $user_id ) ) {
				$user_ids = $user_id;
			} elseif ( ! is_numeric( $user_id ) ) {
				$user_ids = array( get_current_user_id() );
			} else {
				$user_ids = array( $user_id );
			}

			$trunc_types = empty( $msg_type ) ? $this->all_types : array( (string) $msg_type );

			foreach ( $user_ids as $user_id ) {

				/**
				 * Returns a reference to the cache array.
				 */
				$user_notices =& $this->get_notice_cache( $user_id );

				foreach ( $trunc_types as $msg_type ) {

					if ( isset( $user_notices[$msg_type] ) ) {

						/**
						 * Clear notice for a specific dismiss key.
						 */
						if ( ! empty( $dismiss_key ) && is_array( $user_notices[$msg_type] ) ) {

							foreach ( $user_notices[$msg_type] as $msg_key => &$payload ) {	// use reference for payload
								if ( ! empty( $payload['dismiss_key'] ) && $payload['dismiss_key'] === $dismiss_key ) {
									unset( $payload );	// unset by reference
								}
							}

						/**
						 * Clear all notices for a message type.
						 */
						} elseif ( empty( $msg_text ) ) {

							$user_notices[$msg_type] = array();

						/**
						 * Clear a specific message text.
						 */
						} elseif ( is_array( $user_notices[$msg_type] ) ) {

							foreach ( $user_notices[$msg_type] as $msg_key => &$payload ) {	// use reference for payload
								if ( ! empty( $payload['msg_text'] ) && $payload['msg_text'] === $msg_text ) {
									unset( $payload );	// unset by reference
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Set reference values for admin notices
		 */
		public function set_ref( $url = null, $mod = null, $context_transl = null ) {
			$this->ref_cache[] = array( 'url' => $url, 'mod' => $mod, 'context_transl' => $context_transl );
		}

		/**
		 * Restore previous reference values for admin notices.
		 */
		public function unset_ref( $url = null ) {
			if ( null === $url || $this->is_ref_url( $url ) ) {
				array_pop( $this->ref_cache );
				return true;
			} else {
				return false;
			}
		}

		public function get_ref( $idx = false, $text_prefix = '', $text_suffix = '' ) {

			$refs = end( $this->ref_cache );	// Get the last reference added.

			if ( $idx === 'edit' ) {
				if ( isset( $refs['mod'] ) ) {
					if ( $refs['mod']['is_post'] && $refs['mod']['id'] ) {
						return $text_prefix . get_edit_post_link( $refs['mod']['id'], false ) . $text_suffix;	// $display is false.
					} else {
						return '';
					}
				} else {
					return '';
				}
			} elseif ( $idx !== false ) {
				if ( isset( $refs[$idx] ) ) {
					return $text_prefix . $refs[$idx] . $text_suffix;
				} else {
					null;
				}
			} else {
				return $refs;
			}
		}

		public function get_ref_url_html() {

			$ref_html = '';

			if ( $url = $this->get_ref( 'url' ) ) {

				/**
				 * Show a shorter relative URL, if possible.
				 */
				$pretty_url = strtolower( str_replace( home_url(), '', $url ) );

				$context_transl = $this->get_ref( 'context_transl' );

				$context_transl = empty( $context_transl ) ?
					'<a href="' . $url . '">' . $pretty_url . '</a>' :
					'<a href="' . $url . '">' . $context_transl . '</a>';

				$edit_link = $this->get_ref( 'edit', ' (<a href="', '">' . __( 'edit', $this->text_domain ) . '</a>)' );

				$ref_html .= '<p class="reference-message">' . sprintf( __( 'Reference: %s', $this->text_domain ),
					$context_transl . $edit_link ) . '</p>';
			}

			return $ref_html;
		}

		public function is_ref_url( $url = null ) {
			if ( null === $url || $url === $this->get_ref( 'url' ) ) {
				return true;
			} else {
				return false;
			}
		}

		public function is_admin_pre_notices( $dismiss_key = false, $user_id = true ) {

			if ( is_admin() ) {

				if ( ! empty( $dismiss_key ) ) {

					/**
					 * If notice is dismissed, say that we've already shown the notices.
					 */
					if ( $this->is_dismissed( $dismiss_key, $user_id ) ) {
						if ( ! empty( $this->p->debug->enabled ) ) {
							$this->p->debug->log( 'returning false: ' . $dismiss_key . ' is dismissed' );
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

		public function is_dismissed( $dismiss_key = false, $user_id = true ) {

			if ( empty( $dismiss_key ) || ! $this->can_dismiss() ) {	// Just in case.
				return false;
			}

			if ( ! is_numeric( $user_id ) ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = $user_id;
			}

			if ( empty( $user_id ) ) {
				return false;
			}

			$user_dismissed = get_user_option( $this->dis_name, $user_id );

			if ( ! is_array( $user_dismissed ) ) {
				return false;
			}

			if ( isset( $user_dismissed[$dismiss_key] ) ) {	// Notice has been dismissed.

				$now_time = time();
				$dismiss_time = $user_dismissed[$dismiss_key];

				if ( empty( $dismiss_time ) || $dismiss_time > $now_time ) {

					return true;

				} else {	// Dismiss time has expired.
					unset( $user_dismissed[$dismiss_key] );

					if ( empty( $user_dismissed ) ) {
						delete_user_option( $user_id, $this->dis_name, false );	// $global is false.
						delete_user_option( $user_id, $this->dis_name, true );	// $global is true.
					} else {
						update_user_option( $user_id, $this->dis_name, $user_dismissed, false );	// $global is false.
					}
				}
			}

			return false;
		}

		public function can_dismiss() {

			global $wp_version;

			if ( version_compare( $wp_version, '4.2', '>=' ) ) {
				return true;
			} else {
				return false;
			}
		}

		public function hook_admin_notices() {
			add_action( 'all_admin_notices', array( $this, 'show_admin_notices' ), -10 );
		}

		public function show_admin_notices() {

			$notice_types = $this->all_types;

			$doing_block_editor = defined( 'DOING_BLOCK_EDITOR' ) ? DOING_BLOCK_EDITOR : false;

			/**
			 * If toolbar notices are being used, exclude these from being shown.
			 * The default toolbar notices array is err, warn, and inf.
			 */
			if ( is_array( $this->tb_notices ) ) {
				$notice_types = array_diff( $notice_types, $this->tb_notices );
			}

			if ( empty( $notice_types ) ) {	// Just in case.
				return;
			}

			echo "\n";
			echo '<!-- ' . $this->lca . ' admin notices begin -->' . "\n";
			echo '<div id="' . sanitize_html_class( $this->lca . '-admin-notices-begin' ) . '"></div>' . "\n";
			echo $this->get_notice_style();

			/**
			 * Exit early if this is a block editor (aka Gutenberg) page.
			 * The notices will be retrieved using an ajax call on page load and post save.
			 */
			if ( $doing_block_editor ) {
				return;
			}

			$hidden          = array();
			$msg_html        = '';
			$nag_text        = '';
			$user_id         = get_current_user_id();
			$user_notices    =& $this->get_notice_cache( $user_id );
			$user_notices    = $this->add_update_errors( $user_notices );
			$user_dismissed  = empty( $user_id ) ? false : get_user_option( $this->dis_name, $user_id );
			$dismissed_upd   = false;
			$this->has_shown = true;

			$allow_unhide = array(
				'err' => _x( 'error', 'notification type', $this->text_domain ),
				'warn' => _x( 'warning', 'notification type', $this->text_domain ),
			);

			/**
			 * Loop through all the msg types and show them all.
			 */
			foreach ( $notice_types as $msg_type ) {

				if ( ! isset( $user_notices[$msg_type] ) ) {	// Just in case.
					continue;
				}

				foreach ( $user_notices[$msg_type] as $msg_key => $payload ) {

					unset( $user_notices[$msg_type][$msg_key] );	// Don't show it twice.

					if ( empty( $payload['msg_text'] ) ) {	// Nothing to show.
						continue;
					}

					if ( $msg_type === 'nag' ) {
						$nag_text .= $payload['msg_text'];	// Append to echo a single msg block.
						continue;
					}

					if ( ! empty( $payload['dismiss_time'] ) ) {	// True or seconds greater than 0.

						if ( ! isset( $hidden[$msg_type] ) ) {
							$hidden[$msg_type] = 0;
						}

						/**
						 * Check for automatically hidden errors and/or warnings.
						 */
						if ( ( $msg_type === 'err' && $this->hide_err ) || ( $msg_type === 'warn' && $this->hide_warn ) ) {

							$payload['hidden'] = true;

							if ( empty( $payload['no-unhide'] ) ) {
								$hidden[$msg_type]++;
							}

						} elseif ( ! empty( $payload['dismiss_key'] ) && isset( $user_dismissed[$payload['dismiss_key']] ) ) {

							$now_time = time();
							$dismiss_time = $user_dismissed[$payload['dismiss_key']];	// Get time for key.

							if ( empty( $dismiss_time ) || $dismiss_time > $now_time ) {	// 0 or time in future.

								$payload['hidden'] = true;

								if ( empty( $payload['no-unhide'] ) ) {
									$hidden[$msg_type]++;
								}

							} else {	// Dismiss has expired.

								$dismissed_upd = true;	// Update the user meta when done.

								unset( $user_dismissed[$payload['dismiss_key']] );
							}
						}
					}

					/**
					 * If the notice is hidden, and we don't provide a way to unhide it, then skip this notice.
					 */
					if ( ! empty( $payload['hidden'] ) && empty( $allow_unhide[$msg_type] ) ) {
						continue;
					}

					$msg_html .= $this->get_notice_html( $msg_type, $payload );
				}
			}

			/**
			 * Don't save unless we've changed something.
			 */
			if ( ! empty( $user_id ) ) {	// Just in case.
				if ( true === $dismissed_upd ) {
					if ( empty( $user_dismissed ) ) {
						delete_user_option( $user_id, $this->dis_name, false );	// $global is false
						delete_user_option( $user_id, $this->dis_name, true );	// $global is true
					} else {
						update_user_option( $user_id, $this->dis_name, $user_dismissed, false );	// $global is false
					}
				}
			}

			if ( ! empty( $nag_text ) ) {
				$payload = array();
				$payload['msg_text'] = preg_replace( '/<!--spoken-->(.*?)<!--\/spoken-->/Us', ' ', $nag_text );
				$payload['msg_spoken'] = preg_replace( '/<!--not-spoken-->(.*?)<!--\/not-spoken-->/Us', ' ', $nag_text );
				$payload['msg_spoken'] = SucomUtil::decode_html( SucomUtil::strip_html( $payload['msg_spoken'] ) );
				echo $this->get_nag_style();
				echo $this->get_notice_html( 'nag', $payload );
			}

			/**
			 * Remind the user that there are hidden error messages.
			 */
			foreach ( $allow_unhide as $msg_type => $log_name ) {

				if ( empty( $hidden[$msg_type] ) ) {
					continue;
				}
				
				$payload = array();

				$msg_text_transl = _n(
					'%1$d %2$s notice has been dismissed &mdash; <a id="%3$s">show the %2$s message</a>.',
					'%1$d %2$s notices have been dismissed &mdash; <a id="%3$s">show the %2$s messages</a>.',
					$hidden[$msg_type], $this->text_domain
				);

				$payload['msg_text']   = sprintf( $msg_text_transl, $hidden[$msg_type], $log_name, $this->lca . '-unhide-notice-' . $msg_type );
				$payload['msg_spoken'] = SucomUtil::decode_html( SucomUtil::strip_html( $payload['msg_text'] ) );
				$payload['no-unhide']  = true;
				$payload['no-count']   = true;

				echo $this->get_notice_html( $msg_type, $payload );
			}

			echo $msg_html;

			echo '<!-- ' . $this->lca . ' admin notices end -->' . "\n";
		}

		public function admin_footer_script() {
			echo $this->get_notice_script();
		}

		/**
		 * can be called if 'dismiss_key' has a value.
		 */
		public function ajax_dismiss_notice() {

			$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;

			if ( ! $doing_ajax ) {	// Just in case.
				return;
			}

			$dismiss_info = array();
			$user_id  = get_current_user_id();

			if ( empty( $user_id ) || ! current_user_can( 'edit_user', $user_id ) ) {
				die( '-1' );
			}

			check_ajax_referer( __FILE__, 'dismiss_nonce', true );

			foreach ( array( 'dismiss_key', 'dismiss_time' ) as $key ) {
				$dismiss_info[$key] = sanitize_text_field( filter_input( INPUT_POST, $key ) );
			}

			if ( empty( $dismiss_info['dismiss_key'] ) ) {	// Just in case.
				die( '-1' );
			}

			$user_dismissed = get_user_option( $this->dis_name, $user_id );

			if ( ! is_array( $user_dismissed ) ) {
				$user_dismissed = array();
			}

			if ( empty( $dismiss_info['dismiss_time'] ) || ! is_numeric( $dismiss_info['dismiss_time'] ) ) {
				$user_dismissed[$dismiss_info['dismiss_key']] = 0;
			} else {
				$user_dismissed[$dismiss_info['dismiss_key']] = time() + $dismiss_info['dismiss_time'];
			}

			update_user_option( $user_id, $this->dis_name, $user_dismissed, false );	// $global is false

			die( '1' );
		}

		public function ajax_get_notices_json() {

			$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;

			$doing_autosave = defined( 'DOING_AUTOSAVE' ) ? DOING_AUTOSAVE : false;

			if ( ! $doing_ajax ) {
				return;
			} elseif ( $doing_autosave ) {
				die( -1 );
			} elseif ( ! class_exists( 'SucomUtil' ) ) {
				die( -1 );
			}

			$notice_types = $this->all_types;

			if ( ! empty( $_REQUEST['_notice_types'] ) ) {
				if ( is_array( $_REQUEST['_notice_types'] ) ) {
					$notice_types = $_REQUEST['_notice_types'];
				} else {
					$notice_types = array();	// Just in case.
				}
			}

			if ( ! empty( $_REQUEST['_exclude_notices'] ) ) {
				if ( is_array( $_REQUEST['_exclude_notices'] ) ) {
					$notice_types = array_diff( $notice_types, $_REQUEST['_exclude_notices'] );
				}
			}

			if ( empty( $notice_types ) ) {	// Just in case.
				die( -1 );
			}

			check_ajax_referer( WPSSO_NONCE_NAME, '_ajax_nonce', true );

			$user_id         = get_current_user_id();
			$user_notices    =& $this->get_notice_cache( $user_id );
			$user_notices    = $this->add_update_errors( $user_notices );
			$user_dismissed  = empty( $user_id ) ? false : get_user_option( $this->dis_name, $user_id );
			$dismissed_upd   = false;
			$this->has_shown = true;
			$json_notices    = array();
			$ajax_context    = empty( $_REQUEST['context'] ) ? '' : $_REQUEST['context'];	// 'block_editor' or 'toolbar_notices'

			$allow_unhide = array(
				'err' => _x( 'error', 'notification type', $this->text_domain ),
				'warn' => _x( 'warning', 'notification type', $this->text_domain ),
			);

			/**
			 * Loop through all the msg types and show them all.
			 */
			foreach ( $notice_types as $msg_type ) {

				if ( ! isset( $user_notices[$msg_type] ) ) {	// Just in case.
					continue;
				}

				foreach ( $user_notices[$msg_type] as $msg_key => $payload ) {

					unset( $user_notices[$msg_type][$msg_key] );	// Don't show it twice.

					if ( empty( $payload['msg_text'] ) ) {	// Nothing to show.
						continue;
					}

					if ( ! empty( $payload['dismiss_time'] ) ) {	// True or seconds greater than 0.

						if ( ! isset( $hidden[$msg_type] ) ) {
							$hidden[$msg_type] = 0;
						}

						/**
						 * Check for automatically hidden errors and/or warnings.
						 */
						$auto_hide = false;	// Don't auto hide by default.

						if ( $msg_type === 'err' && $this->hide_err ) {
							$auto_hide = true;
						} elseif ( $msg_type === 'warn' && $this->hide_warn ) {
							$auto_hide = true;
						}

						if ( $auto_hide ) {

							$payload['hidden'] = true;

							if ( empty( $payload['no-unhide'] ) ) {
								$hidden[$msg_type]++;
							}

						} elseif ( ! empty( $payload['dismiss_key'] ) && isset( $user_dismissed[$payload['dismiss_key']] ) ) {

							$now_time = time();
							$dismiss_time = $user_dismissed[$payload['dismiss_key']];	// Get time for key.

							if ( empty( $dismiss_time ) || $dismiss_time > $now_time ) {	// 0 or time in future.

								$payload['hidden'] = true;

								if ( empty( $payload['no-unhide'] ) ) {
									$hidden[$msg_type]++;
								}

							} else {	// Dismiss has expired.

								$dismissed_upd = true;	// Update the user meta when done.

								unset( $user_dismissed[$payload['dismiss_key']] );
							}
						}
					}

					/**
					 * If the notice is hidden, and we don't provide a way to unhide it, then skip this notice.
					 */
					if ( ! empty( $payload['hidden'] ) && empty( $allow_unhide[$msg_type] ) ) {
						continue;
					}

					$payload['msg_html'] = $this->get_notice_html( $msg_type, $payload, true );	// $notice_alt is true.

					/**
					 * Add paragraph tags for Gutenberg in case we want to use the 'msg_text' instead of 'msg_html'.
					 */
					if ( stripos( $payload['msg_text'], '<p>' ) === false ) {
						$payload['msg_text'] = '<p>' . $payload['msg_text'] . '</p>';
					}
				
					$json_notices[$msg_type][$msg_key] = $payload;
				}
			}

			/**
			 * Don't save unless we've changed something.
			 */
			if ( ! empty( $user_id ) ) {	// Just in case.
				if ( true === $dismissed_upd ) {
					if ( empty( $user_dismissed ) ) {
						delete_user_option( $user_id, $this->dis_name, false );	// $global is false
						delete_user_option( $user_id, $this->dis_name, true );	// $global is true
					} else {
						update_user_option( $user_id, $this->dis_name, $user_dismissed, false );	// $global is false
					}
				}
			}

			/**
			 * Remind the user that there are hidden error messages.
			 */
			foreach ( $allow_unhide as $msg_type => $log_name ) {

				if ( empty( $hidden[$msg_type] ) ) {
					continue;
				}
				
				$payload = array();

				$msg_text_transl = _n(
					'%1$d %2$s notice has been dismissed &mdash; <a id="%3$s">show the %2$s message</a>.',
					'%1$d %2$s notices have been dismissed &mdash; <a id="%3$s">show the %2$s messages</a>.',
					$hidden[$msg_type], $this->text_domain
				);

				$payload['msg_text']   = sprintf( $msg_text_transl, $hidden[$msg_type], $log_name, $this->lca . '-unhide-notice-' . $msg_type );
				$payload['msg_spoken'] = SucomUtil::decode_html( SucomUtil::strip_html( $payload['msg_text'] ) );
				$payload['msg_html']   = $this->get_notice_html( $msg_type, $payload );
				$payload['no-unhide']  = true;
				$payload['no-count']   = true;

				/**
				 * Add paragraph tags for Gutenberg in case we want to use the 'msg_text' instead of 'msg_html'.
				 */
				if ( stripos( $payload['msg_text'], '<p>' ) === false ) {
					$payload['msg_text'] = '<p>' . $payload['msg_text'] . '</p>';
				}

				$msg_key = sanitize_key( $payload['msg_spoken'] );

				$json_notices[$msg_type][$msg_key] = $payload;
			}

			$json_encoded = SucomUtil::json_encode_array( $json_notices );

			die( $json_encoded );
		}

		private function add_update_errors( $user_notices ) {
			if ( isset( $this->p->cf['plugin'] ) && class_exists( 'SucomUpdate' ) ) {
				foreach ( array_keys( $this->p->cf['plugin'] ) as $ext ) {
					if ( ! empty( $this->p->options['plugin_' . $ext . '_tid'] ) ) {
						$uerr = SucomUpdate::get_umsg( $ext );
						if ( ! empty( $uerr ) ) {
							$payload = array();
							$payload['msg_text'] = preg_replace( '/<!--spoken-->(.*?)<!--\/spoken-->/Us', ' ', $uerr );
							$payload['msg_spoken'] = preg_replace( '/<!--not-spoken-->(.*?)<!--\/not-spoken-->/Us', ' ', $uerr );
							$payload['msg_spoken'] = SucomUtil::decode_html( SucomUtil::strip_html( $payload['msg_spoken'] ) );
							$msg_key = sanitize_key( $payload['msg_spoken'] );
							$user_notices['err'][$msg_key] = $payload;
						}
					}
				}
			}
			return $user_notices;
		}

		private function get_notice_html( $msg_type, array $payload, $notice_alt = false ) {

			$charset = get_bloginfo( 'charset' );

			$notice_class = $notice_alt ? 'notice notice-alt' : 'notice';

			switch ( $msg_type ) {
				case 'nag':
					$payload['notice_label'] = false;	// No label for nag notices.
					$wp_class = 'update-nag';
					break;
				case 'warn':
					$wp_class = $notice_class . ' notice-warning';
					break;
				case 'err':
					$wp_class = $notice_class . ' notice-error error';
					break;
				case 'upd':
					$wp_class = $notice_class . ' notice-success updated';
					break;
				case 'inf':
				default:
					$msg_type = 'inf';
					$wp_class = $notice_class . ' notice-info';
					break;
			}

			if ( ! isset( $payload['notice_label'] ) ) {	// Can be unset, false, empty string, or translated string.
				$payload['notice_label'] = $this->label_transl;
			}

			$is_dismissible = empty( $payload['dismiss_time'] ) ? false : true;

			$css_id_attr = empty( $payload['dismiss_key'] ) ? '' : ' id="' . $msg_type . '-' . $payload['dismiss_key'] . '"';

			$data_attr = $is_dismissible ?
				' data-dismiss-nonce="' . wp_create_nonce( __FILE__ ) . '"' . 
				' data-dismiss-key="' . ( isset( $payload['dismiss_key'] ) ? esc_attr( $payload['dismiss_key'] ) : '' ). '"' . 
				' data-dismiss-time="' . ( isset( $payload['dismiss_time'] ) && is_numeric( $payload['dismiss_time'] ) ?
					esc_attr( $payload['dismiss_time'] ) : 0 ) . '"' : '';

			/**
			 * Optionally hide / show notices by default.
			 */
			$style_attr = ' style="' . 
				( empty( $payload['style'] ) ? '' : $payload['style'] ).
				( empty( $payload['hidden'] ) ? 'display:block;' : 'display:none;' ) . '"';

			$msg_html = '<div class="' . $this->lca . '-notice ' . 
				( ! $is_dismissible ? '' : $this->lca . '-dismissible ' ).
				( empty( $payload['no-unhide'] ) ? '' : $this->lca . '-no-unhide ' ).
				$wp_class . '"' . $css_id_attr . $style_attr . $data_attr . '>';	// display block or none

			/**
			 * Float the dismiss button on the right, so the button must be added first.
			 */
			if ( ! empty( $payload['dismiss_diff'] ) && $is_dismissible ) {
				$msg_html .= '<button class="notice-dismiss" type="button">' .
					'<span class="notice-dismiss-text">' . $payload['dismiss_diff'] . '</span>' .
						'</button><!-- .notice-dismiss -->';
			}

			if ( false !== $payload['notice_label'] ) {	// Can be false, empty string, or translated string.
				$msg_html .= '<div class="notice-label">' . $payload['notice_label'] . '</div><!-- .notice-label -->';
			}

			$msg_html .= '<div class="notice-message">' . $payload['msg_text'] . '</div><!-- .notice-message -->';

			$msg_html .= '</div><!-- .' . $this->lca . '-notice -->' . "\n";

			return $msg_html;
		}

		private function get_all_user_ids() {

			$user_ids = array();

			foreach ( get_users() as $user ) {
				if ( ! empty( $user->ID ) ) {
					$user_ids[] = $user->ID;
				}
			}

			return $user_ids;
		}

		public function get_user_notices( $user_id = true, $use_cache = true ) {

			/**
			 * Returns a reference to the cache array.
			 */
			$user_notices =& $this->get_notice_cache( $user_id, $use_cache );

			return $user_notices;
		}

		/**
		 * Returns a reference to the cache array.
		 */
		private function &get_notice_cache( $user_id = true, $use_cache = true ) {

			if ( ! is_numeric( $user_id ) ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = $user_id;
			}

			if ( $use_cache && isset( $this->notice_cache[$user_id] ) ) {
				return $this->notice_cache[$user_id];
			}

			if ( $user_id > 0 ) {
				$this->notice_cache[$user_id] = $this->get_notice_transient( $user_id );

				if ( ! is_array( $this->notice_cache[$user_id] ) ) {
					$this->notice_cache[$user_id] = array();
				}
			}

			foreach ( $this->all_types as $msg_type ) {
				if ( ! isset( $this->notice_cache[$user_id][$msg_type] ) ) {
					$this->notice_cache[$user_id][$msg_type] = array();
				}
			}

			return $this->notice_cache[$user_id];
		}

		/**
		 * Called by the WordPress 'shutdown' action.
		 */
		public function shutdown_notice_cache() {

			$user_id = get_current_user_id();

			if ( empty( $user_id ) ) {
				return;
			}

			$user_notices =& $this->get_notice_cache( $user_id );

			if ( empty( $this->notice_cache[$user_id] ) ) {
				$this->delete_notice_transient( $user_id );
			} else {
				$this->set_notice_transient( $user_id, $this->notice_cache[$user_id] );
			}
		}

		private function get_notice_transient( $user_id ) {

			$cache_md5_pre = $this->lca . '_';
			$cache_salt    = 'sucom_notice_transient(user_id:' . $user_id . ')';
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			return get_transient( $cache_id );
		}

		private function set_notice_transient( $user_id, $value ) {

			$cache_exp_secs = 3600;
			$cache_md5_pre  = $this->lca . '_';
			$cache_salt     = 'sucom_notice_transient(user_id:' . $user_id . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			return set_transient( $cache_id, $value, $cache_exp_secs );
		}

		private function delete_notice_transient( $user_id ) {

			$cache_md5_pre = $this->lca . '_';
			$cache_salt    = 'sucom_notice_transient(user_id:' . $user_id . ')';
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			return delete_transient( $cache_id );
		}

		private function get_notice_style() {

			$cache_md5_pre  = $this->p->lca . '_';
			$cache_exp_secs = DAY_IN_SECONDS;
			$cache_salt     = __METHOD__;
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			/**
			 * Do not use the transient cache if the DEV constant is defined.
			 */
			$dev_const = strtoupper( $this->lca ) . '_DEV';
			$r_cache   = defined( $dev_const ) && constant( $dev_const ) ? false : true;

			if ( $r_cache ) {
				if ( $custom_style_css = get_transient( $cache_id ) ) {	// Not empty.
					return '<style type="text/css">' . $custom_style_css . '</style>';
				}
			}

			$custom_style_css = '
				@keyframes blinker {
					25% { opacity: 0; }
					75% { opacity: 1; }
				}
				.components-notice-list .' . $this->lca . '-notice {
					min-height:0;
					-webkit-box-shadow:none;
					-moz-box-shadow:none;
					box-shadow:none;
				}
				.components-notice-list .' . $this->lca . '-notice *,
				#wpadminbar .' . $this->lca . '-notice *,
				.' . $this->lca . '-notice * {
					line-height:1.5em;
				}
				.components-notice-list .' . $this->lca . '-notice .notice-label,
				.components-notice-list .' . $this->lca . '-notice .notice-message,
				.components-notice-list .' . $this->lca . '-notice .notice-dismiss {
					padding:8px;
					margin:0;
					border:0;
					background:inherit;
				}
				.components-notice-list .' . $this->lca . '-notice .notice-dismiss {
					padding:2px;
				}
				#wpadminbar #wp-toolbar .have-notices .ab-item:hover,
				#wpadminbar #wp-toolbar .have-notices.hover .ab-item { 
					color:inherit;
					background:inherit;
				}
				#wpadminbar #wp-toolbar #' . $this->p->lca . '-toolbar-notices-icon.ab-icon { 
					margin:0;
					padding:0;
					line-height:1em;
				}
				#wpadminbar #wp-toolbar #' . $this->p->lca . '-toolbar-notices-count {
					margin-left:8px;
				}
				#wpadminbar #wp-toolbar .have-notices #' . $this->p->lca . '-toolbar-notices-icon.ab-icon::before { 
					display:inline-block;
					color:#fff;
				}
				#wpadminbar #wp-toolbar .have-notices #' . $this->p->lca . '-toolbar-notices-count {
					display:inline-block;
					color:#fff;
				}
				#wpadminbar .have-notices.have-notices-error {
					background-color:#dc3232;
				}
				#wpadminbar .have-notices.have-notices-warning {
					background-color:#ffb900;
				}
				#wpadminbar .have-notices.have-notices-info {
					background-color:#00a0d2;
				}
				#wpadminbar .have-notices.have-notices-success {
					background-color:#46b450;
				}
				#wpadminbar .have-notices #wp-admin-bar-'.$this->p->lca.'-toolbar-notices-default { 
					padding:0;
				}
				#wpadminbar .have-notices #wp-admin-bar-'.$this->p->lca.'-toolbar-notices-container { 
					min-width:70vw;	/* 70% of the viewing window width */
					max-height:100vh;
					overflow-y:scroll;
				}
				#wpadminbar .' . $this->lca . '-notice,
				#wpadminbar .' . $this->lca . '-notice.error,
				#wpadminbar .' . $this->lca . '-notice.updated,
				.' . $this->lca . '-notice,
				.' . $this->lca . '-notice.error,	/* wp sets padding to 1px 12px */
				.' . $this->lca . '-notice.updated {	/* wp sets padding to 1px 12px */
					clear:both;
					padding:0;
					-webkit-box-shadow:none;
					-moz-box-shadow:none;
					box-shadow:none;
				}
				#wpadminbar .' . $this->lca . '-notice,
				#wpadminbar .' . $this->lca . '-notice.error,
				#wpadminbar .' . $this->lca . '-notice.updated {
					background:inherit;
				}
				#wpadminbar .' . $this->lca . '-notice > div,
				#wpadminbar .' . $this->lca . '-notice.error > div,
				#wpadminbar .' . $this->lca . '-notice.updated > div {
					min-height:50px;
				}
				#wpadminbar .' . $this->lca . '-notice a,
				.' . $this->lca . '-notice a {
					display:inline;
					text-decoration:underline;
					padding:0;
				}
				#wpadminbar .' . $this->lca . '-notice .button-primary,
				#wpadminbar .' . $this->lca . '-notice .button-secondary {
					padding:0.3em 1em;
					-webkit-border-radius:0;
					-moz-border-radius:0;
					border-radius:0;
					-webkit-box-shadow:none;
					-moz-box-shadow:none;
					box-shadow:none;
				}
				#wpadminbar .'.$this->p->lca.'-notice .notice-label,
				#wpadminbar .'.$this->p->lca.'-notice .notice-message,
				#wpadminbar .'.$this->p->lca.'-notice .notice-dismiss {
					position:relative;
					display:table-cell;
					padding:20px;
					margin:0;
					border:none;
					vertical-align:top;
					background:inherit;
				}
				.'.$this->p->lca.'-notice .notice-label,
				.'.$this->p->lca.'-notice .notice-message,
				.'.$this->p->lca.'-notice .notice-dismiss {
					position:relative;
					display:table-cell;
					padding:15px 20px;
					margin:0;
					border:none;
					vertical-align:top;
				}
				.components-notice-list .' . $this->lca . '-notice .notice-dismiss,
				#wpadminbar .'.$this->p->lca.'-notice .notice-dismiss,
				.'.$this->p->lca.'-notice .notice-dismiss {
					display:block;
					float:right;
					top:0;
					right:0;
					padding-left:0;
					padding-bottom:15px;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-label,
				.' . $this->lca . '-notice .notice-label {
					font-weight:bold;
					color:#444;	/* default text color */
					background-color:#fcfcfc;	/* default background color */
					white-space:nowrap;
				}
				#wpadminbar .' . $this->lca . '-notice.notice-error .notice-label,
				.' . $this->lca . '-notice.notice-error .notice-label {
					/* color:#dc3232; */
					background-color: #fbeaea;
				}
				#wpadminbar .' . $this->lca . '-notice.notice-warning .notice-label,
				.' . $this->lca . '-notice.notice-warning .notice-label {
					/* color:#ffb900; */
					background-color: #fff8e5;
				}
				#wpadminbar .' . $this->lca . '-notice.notice-info .notice-label,
				.' . $this->lca . '-notice.notice-info .notice-label {
					/* color:#00a0d2; */
					background-color: #e5f5fa;
				}
				#wpadminbar .' . $this->lca . '-notice.notice-success .notice-label,
				.' . $this->lca . '-notice.notice-success .notice-label {
					/* color:#46b450; */
					background-color: #ecf7ed;
				}
				.' . $this->lca . '-notice.notice-success .notice-label::before,
				.' . $this->lca . '-notice.notice-info .notice-label::before,
				.' . $this->lca . '-notice.notice-warning .notice-label::before,
				.' . $this->lca . '-notice.notice-error .notice-label::before {
					vertical-align:bottom;
					font-family:dashicons;
					font-size:1.2em;
					margin-right:6px;
				}
				.' . $this->lca . '-notice.notice-error .notice-label::before {
					content:"\f488";	/* megaphone */
				}
				.' . $this->lca . '-notice.notice-warning .notice-label::before {
					content:"\f227";	/* flag */
				}
				.' . $this->lca . '-notice.notice-info .notice-label::before {
					content:"\f537";	/* sticky */
				}
				.' . $this->lca . '-notice.notice-success .notice-label::before {
					content:"\f147";	/* yes */
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message h2,
				.' . $this->lca . '-notice .notice-message h2 {
					font-size:1.2em;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message h3,
				.' . $this->lca . '-notice .notice-message h3 {
					font-size:1.1em;
					margin-top:1.2em;
					margin-bottom:0.8em;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message code,
				.' . $this->lca . '-notice .notice-message code {
					font-family:"Courier", monospace;
					font-size:1em;
					vertical-align:middle;
					padding:0 2px;
					margin:0;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message a,
				.' . $this->lca . '-notice .notice-message a {
					display:inline;
					text-decoration:underline;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message a code,
				.' . $this->lca . '-notice .notice-message a code {
					padding:0;
					vertical-align:middle;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message p,
				#wpadminbar .' . $this->lca . '-notice .notice-message pre,
				.' . $this->lca . '-notice .notice-message p,
				.' . $this->lca . '-notice .notice-message pre {
					margin:0.8em 0 0 0;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message .top,
				.' . $this->lca . '-notice .notice-message .top {
					margin-top:0;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message p.reference-message,
				.' . $this->lca . '-notice .notice-message p.reference-message {
					font-size:0.9em;
					margin:10px 0 0 0;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message p.reference-message a {
					font-size:0.9em;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message p.smaller-message,
				#wpadminbar .' . $this->lca . '-notice .notice-message p.smaller-message a,
				.' . $this->lca . '-notice .notice-message p.smaller-message,
				.' . $this->lca . '-notice .notice-message p.smaller-message a {
					font-size:0.9em;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message ul,
				.' . $this->lca . '-notice .notice-message ul {
					margin:1em 0 1em 3em;
					list-style:disc outside none;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message ol,
				.' . $this->lca . '-notice .notice-message ol {
					margin:1em 0 1em 3em;
					list-style:decimal outside none;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message li,
				.' . $this->lca . '-notice .notice-message li {
					text-align:left;
					margin:5px 0 5px 0;
					padding-left:0.8em;
					list-style:inherit;
				}
				#wpadminbar .' . $this->lca . '-notice .notice-message b,
				#wpadminbar .' . $this->lca . '-notice .notice-message strong,
				.' . $this->lca . '-notice .notice-message b,
				.' . $this->lca . '-notice .notice-message strong {
					font-weight:bold;
				}
				.' . $this->lca . '-notice .notice-message .button-highlight {
					border-color:#0074a2;
					background-color:#daeefc;
				}
				.' . $this->lca . '-notice .notice-message .button-highlight:hover {
					background-color:#c8e6fb;
				}
				.' . $this->lca . '-notice .notice-dismiss::before {
					display:inline-block;
					padding:2px;
				}
				.' . $this->lca . '-notice .notice-dismiss .notice-dismiss-text {
					display:inline-block;
					font-size:12px;
					padding:2px;
					vertical-align:top;
					white-space:nowrap;
				}
			';

			if ( $r_cache ) {
				if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
					$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->lca );
				}
				set_transient( $cache_id, $custom_style_css, $cache_exp_secs );
			}

			return '<style type="text/css">' . $custom_style_css . '</style>';
		}

		private function get_nag_style() {

			$cache_md5_pre  = $this->p->lca . '_';
			$cache_exp_secs = DAY_IN_SECONDS;
			$cache_salt     = __METHOD__;
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			if ( $custom_style_css = get_transient( $cache_id ) ) {	// Not empty.
				return '<style type="text/css">' . $custom_style_css . '</style>';
			}

			$uca = strtoupper( $this->lca );
			$custom_style_css = '';

			if ( isset( $this->p->cf['notice'] ) ) {
				foreach ( $this->p->cf['notice'] as $css_class => $css_props ) {
					foreach ( $css_props as $prop_name => $prop_value ) {
						$custom_style_css .= '.' . $this->lca . '-notice.' . $css_class . '{' . $prop_name . ':' . $prop_value . ';}' . "\n";
					}
				}
			}

			$custom_style_css .= '
				.' . $this->lca . '-notice.update-nag {
					margin-top:0;
					clear:none;
				}
				.' . $this->lca . '-notice.update-nag > div {
					display:block;
					margin:0 auto;
					max-width:800px;
				}
				.' . $this->lca . '-notice.update-nag p,
				.' . $this->lca . '-notice.update-nag ul,
				.' . $this->lca . '-notice.update-nag ol {
					font-size:1em;
					text-align:center;
					margin:15px auto 15px auto;
				}
				.' . $this->lca . '-notice.update-nag ul li {
					list-style-type:square;
				}
				.' . $this->lca . '-notice.update-nag ol li {
					list-style-type:decimal;
				}
				.' . $this->lca . '-notice.update-nag li {
					text-align:left;
					margin:5px 0 5px 60px;
				}
			';
			
			if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
				$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->lca );
			}

			set_transient( $cache_id, $custom_style_css, $cache_exp_secs );

			return '<style type="text/css">' . $custom_style_css . '</style>';
		}

		private function get_notice_script() {
			return '
<script type="text/javascript">

	jQuery( document ).on( "click", "#' . $this->lca . '-unhide-notice-err", function() {
		jQuery( ".' . $this->lca . '-notice.' . $this->lca . '-dismissible.notice-error" ).not( ".' . $this->lca . '-no-unhide" ).show();
		jQuery( this ).parents(".' . $this->lca . '-notice.notice-error").hide();
	} );

	jQuery( document ).on( "click", "#' . $this->lca . '-unhide-notice-warn", function() {
		jQuery( ".' . $this->lca . '-notice.' . $this->lca . '-dismissible.notice-warning" ).not( ".' . $this->lca . '-no-unhide" ).show();
		jQuery( this ).parents( ".' . $this->lca . '-notice.notice-warning" ).hide();
	} );

	jQuery( document ).on( "click", "div.' . $this->lca . '-dismissible > button.notice-dismiss, div.' . $this->lca . '-dismissible .dismiss-on-click", function() {

		var notice      = jQuery( this ).closest( ".' . $this->lca . '-dismissible" );
		var dismiss_msg = jQuery( this ).data( "dismiss-msg" );

		var ajaxDismissData = {
			action: "' . $this->lca . '_dismiss_notice",
			dismiss_nonce: notice.data( "dismiss-nonce" ),
			dismiss_key: notice.data( "dismiss-key" ),
			dismiss_time: notice.data( "dismiss-time" ),
		}

		if ( notice.data( "dismiss-key" ) ) {
			jQuery.post( ajaxurl, ajaxDismissData );
		}

		if ( dismiss_msg ) {
			notice.children( "button.notice-dismiss" ).hide();
			jQuery( this ).closest( "div.notice-message" ).html( dismiss_msg );
		} else {
			notice.hide();
		}
	} ); 

</script>' . "\n";
		}
	}
}
