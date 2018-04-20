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
		private $lca = 'sucom';
		private $text_domain = 'sucom';
		private $label_transl = '';
		private $dis_name = 'sucom_dismissed';
		private $hide_err = false;
		private $hide_warn = false;
		private $has_shown = false;
		private $all_types = array( 'nag', 'err', 'warn', 'upd', 'inf' );
		private $ref_cache = array();
		private $notice_cache = array();

		public $enabled = true;

		public function __construct( $plugin = null, $lca = null, $text_domain = null, $label_transl = null ) {

			static $do_once = null;	// just in case

			if ( null === $do_once ) {

				$do_once = true;

				$this->set_config( $plugin, $lca, $text_domain, $label_transl );

				$this->add_actions();
			}
		}

		/**
		 * Set property values for text domain, notice label, etc.
		 */
		private function set_config( $plugin = null, $lca = null, $text_domain = null, $label_transl = null ) {

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

			if ( $label_transl !== null ) {
				$this->label_transl = $label_transl;	// argument is already translated
			} elseif ( ! empty( $this->p->cf['menu']['title'] ) ) {
				$this->label_transl = sprintf( __( '%s Notice', $this->text_domain ),
					_x( $this->p->cf['menu']['title'], 'menu title', $this->text_domain ) );
			} else {
				$this->label_transl = __( 'Notice', $this->text_domain );
			}

			$uca = strtoupper( $this->lca );

			$this->dis_name  = defined( $uca . '_DISMISS_NAME' ) ? constant( $uca . '_DISMISS_NAME' ) : $this->lca . '_dismissed';
			$this->hide_err  = defined( $uca . '_HIDE_ALL_ERRORS' ) ? constant( $uca . '_HIDE_ALL_ERRORS' ) : false;
			$this->hide_warn = defined( $uca . '_HIDE_ALL_WARNINGS' ) ? constant( $uca . '_HIDE_ALL_WARNINGS' ) : false;
		}

		private function add_actions() {
			if ( is_admin() ) {
				add_action( 'in_admin_header', array( &$this, 'hook_admin_notices' ), PHP_INT_MAX );
				add_action( 'admin_footer', array( &$this, 'admin_footer_script' ) );
				add_action( 'wp_ajax_' . $this->lca . '_dismiss_notice', array( &$this, 'ajax_dismiss_notice' ) );
				add_action( 'wp_ajax_' . $this->lca . '_get_notices_json', array( &$this, 'ajax_get_notices_json' ) );
			}
			add_action( 'shutdown', array( &$this, 'save_notice_cache' ) );
		}

		public function nag( $msg_text, $user_id = true, $dismiss_key = false ) {
			$this->log( 'nag', $msg_text, $user_id, $dismiss_key, false );	// $dismiss_time = false
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

		public function warn( $msg_text, $user_id = true, $dismiss_key = false, $dismiss_time = false, $silent = false ) {
			$payload = array( 'silent' => $silent ? true : false );
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

			/**
			 * 'dis_key' is independant of the $msg_type, so all can be hidden with one 'dis_key'.
			 */
			$payload['dis_key'] = empty( $dismiss_key ) ? false : $dismiss_key;

			/**
			 * 'dis_key' and 'dis_time' (true or seconds) are both required to dismiss a notice.
			 */
			if ( ! empty( $dismiss_key ) && ! empty( $dismiss_time ) && $this->can_dismiss() ) {

				$payload['dis_time'] = $dismiss_time;

				if ( is_numeric( $payload['dis_time'] ) ) {

					/**
					 * If the message ends with a paragraph tag, add text as a paragraph
					 */
					$has_p = substr( $msg_text, -4 ) === '</p>' ? true : false;

					$dismiss_transl = __( 'This notice can be dismissed temporarily for a period of %s.', $this->text_domain );

					$msg_text .= $has_p ? '<p>' : ' ';
					$msg_text .= sprintf( $dismiss_transl, human_time_diff( 0, $payload['dis_time'] ) );
					$msg_text .= $has_p ? '</p>' : '';
				}
			} else {
				$payload['dis_time'] = false;
			}

			/**
			 * Maybe add a reference URL at the end.
			 */
			$msg_text .= $this->get_ref_url_html();

			$payload['msg_text'] = preg_replace( '/<!--spoken-->(.*?)<!--\/spoken-->/Us', ' ', $msg_text );
			$payload['msg_spoken'] = preg_replace( '/<!--not-spoken-->(.*?)<!--\/not-spoken-->/Us', ' ', $msg_text );
			$payload['msg_spoken'] = SucomUtil::decode_html( SucomUtil::strip_html( $payload['msg_spoken'] ) );
			$msg_key = sanitize_key( $payload['msg_spoken'] );

			/**
			 * Returns a reference to the cache array.
			 */
			$user_notices =& $this->get_notice_cache( $user_id );

			/**
			 * User notices are saved on shutdown.
			 */
			if ( ! isset( $user_notices[$msg_type][$msg_key] ) ) {
				$user_notices[$msg_type][$msg_key] = $payload;
			}
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
								if ( ! empty( $payload['dis_key'] ) && $payload['dis_key'] === $dismiss_key ) {
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

		public function get_ref( $idx = false, $prefix = '', $suffix = '' ) {
			$refs = end( $this->ref_cache );	// get the last reference added
			if ( $idx === 'edit' ) {
				if ( isset( $refs['mod'] ) ) {
					if ( $refs['mod']['is_post'] && $refs['mod']['id'] ) {
						return $prefix.get_edit_post_link( $refs['mod']['id'], false ) . $suffix;	// $display = false
					} else {
						return '';
					}
				} else {
					return '';
				}
			} elseif ( $idx !== false ) {
				if ( isset( $refs[$idx] ) ) {
					return $prefix . $refs[$idx] . $suffix;
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
				$context_transl = $this->get_ref( 'context_transl', '', ' ' );
				$url_link = '<a href="' . $url . '">' . strtolower( $url ) . '</a>';
				$edit_link = $this->get_ref( 'edit', ' (<a href="', '">' . __( 'edit', $this->text_domain ) . '</a>)' );
				$ref_html .= '<p class="reference-message">' . sprintf( __( 'Reference: %s', $this->text_domain ),
					$context_transl . $url_link . $edit_link ) . '</p>';
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
							$this->p->debug->log( 'returning false: '.$dismiss_key.' is dismissed' );
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

			$user_dismissed = get_user_meta( $user_id, $this->dis_name, true );

			if ( ! is_array( $user_dismissed ) ) {
				return false;
			}

			// notice has been dismissed
			if ( isset( $user_dismissed[$dismiss_key] ) ) {

				$now_time = time();
				$dismiss_time = $user_dismissed[$dismiss_key];

				if ( empty( $dismiss_time ) || $dismiss_time > $now_time ) {
					return true;

				// dismiss time has expired
				} else {
					unset( $user_dismissed[$dismiss_key] );
					if ( empty( $user_dismissed ) ) {
						delete_user_meta( $user_id, $this->dis_name );
					} else {
						update_user_meta( $user_id, $this->dis_name, $user_dismissed );
					}
				}
			}

			return false;
		}

		public function can_dismiss() {
			global $wp_version;
			if ( version_compare( $wp_version, 4.2, '>=' ) ) {
				return true;
			} else {
				return false;
			}
		}

		public function hook_admin_notices() {
			add_action( 'all_admin_notices', array( &$this, 'show_admin_notices' ), -10 );
		}

		public function show_admin_notices() {

			$doing_gutenberg = defined( 'DOING_GUTENBERG' ) && DOING_GUTENBERG ? true : false;

			if ( $doing_gutenberg ) {
				return;
			}

			$hidden          = array();
			$msg_html        = '';
			$nag_text        = '';
			$shown_msgs      = array();	// duplicate check
			$dismissed_upd   = false;
			$user_id         = get_current_user_id();
			$user_notices    =& $this->get_notice_cache( $user_id );
			$user_notices    = $this->add_update_errors( $user_notices );
			$user_dismissed  = empty( $user_id ) ? false : get_user_meta( $user_id, $this->dis_name, true );
			$this->has_shown = true;

			/**
			 * Loop through all the msg types and show them all.
			 */
			foreach ( $this->all_types as $msg_type ) {

				if ( ! isset( $user_notices[$msg_type] ) ) {	// Just in case.
					continue;
				}

				foreach ( $user_notices[$msg_type] as $msg_key => $payload ) {

					if ( empty( $payload['msg_text'] ) || isset( $shown_msgs[$msg_key] ) ) {	// skip duplicates
						continue;
					}

					$shown_msgs[$msg_key] = true;	// avoid duplicates

					switch ( $msg_type ) {

						case 'nag':

							$nag_text .= $payload['msg_text'];	// append to echo a single msg block

							continue;

						default:

							/**
							 * 'dis_time' will be false if there's no 'dis_key'.
							 */
							if ( ! empty( $payload['dis_time'] ) ) {

								/**
								 * Initialize the counter.
								 */
								if ( ! isset( $hidden[$msg_type] ) ) {
									$hidden[$msg_type] = 0;
								}

								/**
								 * Check for automatically hidden errors and/or warnings.
								 */
								if ( ( $msg_type === 'err' && $this->hide_err ) ||
									( $msg_type === 'warn' && $this->hide_warn ) ) {

									$payload['hidden'] = true;
									if ( empty( $payload['silent'] ) ) {
										$hidden[$msg_type]++;
									}

								/**
								 * 'dis_key' has been dismissed.
								 */
								} elseif ( ! empty( $payload['dis_key'] ) &&
									isset( $user_dismissed[$payload['dis_key']] ) ) {

									$now_time = time();
									$dismiss_time = $user_dismissed[$payload['dis_key']];

									if ( empty( $dismiss_time ) || $dismiss_time > $now_time ) {
										$payload['hidden'] = true;
										if ( empty( $payload['silent'] ) ) {
											$hidden[$msg_type]++;
										}
									} else {	// dismiss has expired
										$dismissed_upd = true;	// update the array when done
										unset( $user_dismissed[$payload['dis_key']] );
									}
								}
							}

							$msg_html .= $this->get_notice_html( $msg_type, $payload );

							break;
					}
				}
			}

			/**
			 * Don't save unless we've changed something.
			 */
			if ( true === $dismissed_upd && ! empty( $user_id ) ) {
				if ( empty( $user_dismissed ) ) {
					delete_user_meta( $user_id, $this->dis_name );
				} else {
					update_user_meta( $user_id, $this->dis_name, $user_dismissed );
				}
			}

			echo "\n";
			echo '<!-- ' . $this->lca . ' admin notices begin -->' . "\n";
			echo '<div id="' . sanitize_html_class( $this->lca . '-admin-notices-begin' ) . '"></div>' . "\n";
			echo $this->get_notice_style();

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
			foreach ( array(
				'err' => _x( 'error', 'notification type', $this->text_domain ),
				'warn' => _x( 'warning', 'notification type', $this->text_domain ),
			) as $msg_type => $log_name ) {

				if ( empty( $hidden[$msg_type] ) ) {
					continue;
				}
				
				$payload = array();
				$payload['msg_text'] = _n(
					'%1$d important %2$s notice has been hidden and/or dismissed &mdash; <a id="%3$s">unhide and view the %2$s message</a>.',
					'%1$d important %2$s notices have been hidden and/or dismissed &mdash; <a id="%3$s">unhide and view the %2$s messages</a>.',
					$hidden[$msg_type], $this->text_domain
				);
				$payload['msg_text'] = sprintf( $payload['msg_text'], $hidden[$msg_type], $log_name, $this->lca . '-unhide-notice-' . $msg_type );
				$payload['msg_spoken'] = SucomUtil::decode_html( SucomUtil::strip_html( $payload['msg_spoken'] ) );

				echo $this->get_notice_html( $msg_type, $payload );
			}

			echo $msg_html;
			echo '<!-- ' . $this->lca . ' admin notices end -->' . "\n";
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

		private function get_notice_html( $msg_type, array $payload ) {

			$charset = get_bloginfo( 'charset' );

			if ( ! isset( $payload['label'] ) ) {
				$payload['label'] = $this->label_transl;
			}

			switch ( $msg_type ) {
				case 'nag':
					$payload['label'] = '';
					$msg_class = 'update-nag';
					break;
				case 'warn':
					$msg_class = 'notice notice-warning';
					break;
				case 'err':
					$msg_class = 'notice notice-error error';
					break;
				case 'upd':
					$msg_class = 'notice notice-success updated';
					break;
				case 'inf':
				default:	// Fallback.
					$msg_type = 'inf';
					$msg_class = 'notice notice-info';
					break;
			}

			/**
			 * 'dis_key' and 'dis_time' must have a value to create a dismissible notice.
			 */
			$is_dismissible = empty( $payload['dis_key'] ) || empty( $payload['dis_time'] ) ? false : true;

			$css_id_attr = empty( $payload['dis_key'] ) ? '' : ' id="' . $msg_type . '_' . $payload['dis_key'] . '"';

			$data_attr = $is_dismissible ?
				' data-dismiss-nonce="' . wp_create_nonce( __FILE__ ) . '"' . 
				' data-dismiss-key="' . esc_attr( $payload['dis_key'] ) . '"' . 
				' data-dismiss-time="' . ( is_numeric( $payload['dis_time'] ) ? esc_attr( $payload['dis_time'] ) : 0 ) . '"' : '';

			/**
			 * Optionally hide / show notices by default.
			 */
			$style_attr = ' style="' . 
				( empty( $payload['style'] ) ? '' : $payload['style'] ).
				( empty( $payload['hidden'] ) ? 'display:block;' : 'display:none;' ) . '"';

			$msg_html = '<div class="' . $this->lca . '-notice ' . 
				( ! $is_dismissible ? '' : $this->lca . '-dismissible ' ).
					$msg_class . '"' . $css_id_attr . $style_attr . $data_attr . '>';	// display block or none

			if ( ! empty( $payload['dis_time'] ) ) {
				$msg_html .= '<div class="notice-dismiss"><div class="notice-dismiss-text">' . 
					__( 'Dismiss', $this->text_domain ) . '</div></div>';
			}

			if ( ! empty( $payload['label'] ) ) {
				$msg_html .= '<div class="notice-label">' . $payload['label'] . '</div>';
			}

			$msg_html .= '<div class="notice-message">' . $payload['msg_text'] . '</div>';
			$msg_html .= '</div>' . "\n";

			return $msg_html;
		}

		private function get_notice_style() {

			$custom_style_css = '
				body.gutenberg-editor-page #wpbody-content .' . $this->lca . '-notice {
					display:none !important;
				}
				.' . $this->lca . '-notice.notice {
					padding:0;
				}
				.' . $this->lca . '-notice ul {
					margin:5px 0 5px 40px;
					list-style:disc outside none;
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
				.' . $this->lca . '-notice.notice-success .notice-label::before {
					content:"\f147";	/* yes */
				}
				.' . $this->lca . '-notice.notice-info .notice-label::before {
					content:"\f537";	/* sticky */
				}
				.' . $this->lca . '-notice.notice-warning .notice-label::before {
					content:"\f227";	/* flag */
				}
				.' . $this->lca . '-notice.notice-error .notice-label::before {
					content:"\f488";	/* megaphone */
				}
				.' . $this->lca . '-notice .notice-label {
					display:table-cell;
					vertical-align:top;
					padding:10px;
					margin:0;
					white-space:nowrap;
					font-weight:bold;
					background:#fcfcfc;
					border-right:1px solid #ddd;
				}
				.' . $this->lca . '-notice .notice-message {
					display:table-cell;
					vertical-align:top;
					padding:10px 20px;
					margin:0;
					line-height:1.5em;
				}
				.' . $this->lca . '-notice .notice-message h2 {
					font-size:1.2em;
				}
				.' . $this->lca . '-notice .notice-message h3 {
					font-size:1.1em;
					margin-top:1.2em;
					margin-bottom:0.8em;
				}
				.' . $this->lca . '-notice .notice-message code {
					font-family:"Courier", monospace;
					font-size:0.95em;
					padding:0 2px;
					margin:0;
				}
				.' . $this->lca . '-notice .notice-message a {
					text-decoration:underline;
				}
				.' . $this->lca . '-notice .notice-message a code {
					vertical-align:middle;
					padding:0;
				}
				.' . $this->lca . '-notice .notice-message p {
					margin:1em 0;
				}
				.' . $this->lca . '-notice .notice-message p.reference-message {
					font-size:0.8em;
					margin:10px 0 0 0;
				}
				.' . $this->lca . '-notice .notice-message ul {
					margin-top:0.8em;
					margin-bottom:1.2em;
				}
				.' . $this->lca . '-notice .notice-message ul li {
					margin-top:3px;
					margin-bottom:3px;
				}
				.' . $this->lca . '-notice .notice-message .button-highlight {
					border-color:#0074a2;
					background-color:#daeefc;
				}
				.' . $this->lca . '-notice .notice-message .button-highlight:hover {
					background-color:#c8e6fb;
				}
				.' . $this->lca . '-dismissible div.notice-dismiss::before {
					display:inline-block;
					margin-right:2px;
				}
				.' . $this->lca . '-dismissible div.notice-dismiss {
					float:right;
					position:relative;
					padding:10px;
					margin:0;
					top:0;
					right:0;
				}
				.' . $this->lca . '-dismissible div.notice-dismiss-text {
					display:inline-block;
					font-size:12px;
					vertical-align:top;
				}
			';

			if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
				$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->lca );
			}

			return '<style type="text/css">' . $custom_style_css . '</style>';
		}

		private function get_nag_style() {

			$custom_style_css = '';
			$uca = strtoupper( $this->lca );

			if ( isset( $this->p->cf['nag_colors'] ) ) {
				foreach ( $this->p->cf['nag_colors'] as $css_class => $nag_colors ) {
					foreach ( $nag_colors as $prop_name => $prop_value ) {
						$custom_style_css .= '.' . $this->lca . '-notice.' . $css_class . '{' . $prop_name . ':' . $prop_value . ';}' . "\n";
					}
				}
			}

			$custom_style_css .= '
				.' . $this->lca . '-notice.update-nag {
					margin-top:0;
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

			return '<style type="text/css">' . $custom_style_css . '</style>';
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

		public function admin_footer_script() {
			echo '
<script type="text/javascript">
	jQuery( document ).ready( function() {

		jQuery( "#' . $this->lca . '-unhide-notice-err" ).click( function() {
			var notice = jQuery( this ).parents(".' . $this->lca . '-notice.notice-error");
			jQuery(".' . $this->lca . '-notice.' . $this->lca . '-dismissible.notice-error").show();
			notice.hide();
		} );

		jQuery( "#' . $this->lca . '-unhide-notice-warn" ).click( function() {
			var notice = jQuery( this ).parents(".' . $this->lca . '-notice.notice-warning");
			jQuery(".' . $this->lca . '-notice.' . $this->lca . '-dismissible.notice-warning").show();
			notice.hide();
		} );

		jQuery( "div.' . $this->lca . '-dismissible > div.notice-dismiss, div.' . $this->lca . '-dismissible .dismiss-on-click" ).click( function() {

			var notice        = jQuery( this ).closest(".' . $this->lca . '-dismissible");
			var dismiss_msg   = jQuery( this ).data( "dismiss-msg" );
			var dismiss_nonce = notice.data( "dismiss-nonce" );
			var dismiss_key   = notice.data( "dismiss-key" );
			var dismiss_time  = notice.data( "dismiss-time" );

			jQuery.post(
				ajaxurl, {
					action: "' . $this->lca . '_dismiss_notice",
					_ajax_nonce: dismiss_nonce,
					key: dismiss_key,
					time: dismiss_time
				}
			);

			if ( dismiss_msg ) {
				notice.children("div.notice-dismiss").hide();
				jQuery( this ).closest("div.notice-message").html( dismiss_msg );
			} else {
				notice.hide();
			}
		} );
	} );
</script>
			';
		}

		public function ajax_dismiss_notice() {

			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX ? true : false;

			if ( ! $doing_ajax ) {	// Just in case.
				return;
			}

			$dis_info = array();
			$user_id  = get_current_user_id();

			if ( empty( $user_id ) || ! current_user_can( 'edit_user', $user_id ) ) {
				die( '-1' );
			}

			check_ajax_referer( __FILE__, '_ajax_nonce', true );

			/**
			 * Read arguments.
			 */
			foreach ( array( 'key', 'time' ) as $key ) {
				$dis_info[$key] = sanitize_text_field( filter_input( INPUT_POST, $key ) );
			}

			if ( empty( $dis_info['key'] ) ) {
				die( '-1' );
			}

			/**
			 * Site specific user options.
			 */
			$user_dismissed = get_user_meta( $user_id, $this->dis_name, true );

			if ( ! is_array( $user_dismissed ) ) {
				$user_dismissed = array();
			}

			/**
			 * Save the message id and expiration time (0 = never).
			 */
			$user_dismissed[$dis_info['key']] = empty( $dis_info['time'] ) || ! is_numeric( $dis_info['time'] ) ? 0 : time() + $dis_info['time'];

			update_user_meta( $user_id, $this->dis_name, $user_dismissed );

			die( '1' );
		}

		public function ajax_get_notices_json() {

			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX ? true : false;
			$doing_autosave = defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ? true : false;

			if ( ! $doing_ajax ) {
				return;
			} elseif ( $doing_autosave ) {
				die( -1 );
			} elseif ( ! class_exists( 'SucomUtil' ) ) {
				die( -1 );
			}

			check_ajax_referer( WPSSO_NONCE_NAME, '_ajax_nonce', true );

			$shown_msgs      = array();	// duplicate check
			$user_id         = get_current_user_id();
			$user_notices    =& $this->get_notice_cache( $user_id );
			$user_notices    = $this->add_update_errors( $user_notices );
			$json_notices    = array();
			$this->has_shown = true;

			foreach ( $this->all_types as $msg_type ) {

				if ( ! isset( $user_notices[$msg_type] ) ) {	// Just in case.
					continue;
				}

				foreach ( $user_notices[$msg_type] as $msg_key => $payload ) {

					if ( empty( $payload['msg_text'] ) || isset( $shown_msgs[$msg_key] ) ) {	// skip duplicates
						continue;
					}

					$shown_msgs[$msg_key] = true;	// avoid duplicates

					if ( stripos( $payload['msg_text'], '<p>' ) === false ) {
						$payload['msg_text'] = '<p>' . $payload['msg_text'] . '</p>';
					}
					
					$json_notices[$msg_type][$msg_key] = $payload;
				}
			}

			$json_encoded = SucomUtil::json_encode_array( $json_notices );

			die( $json_encoded );
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
		public function save_notice_cache() {

			$user_id = get_current_user_id();

			if ( empty( $user_id ) ) {
				return;
			}

			$user_notices =& $this->get_notice_cache( $user_id );

			if ( $this->has_shown ) {
				$this->trunc( '', '', false, $user_id );
			}

			if ( empty( $this->notice_cache[$user_id] ) ) {
				$this->delete_notice_transient( $user_id );
			} else {
				$this->set_notice_transient( $user_id, $this->notice_cache[$user_id] );
			}
		}

		private function get_notice_transient( $user_id ) {

			$cache_md5_pre = $this->lca.'_';
			$cache_salt  = 'sucom_notice_transient(user_id:'.$user_id.')';
			$cache_id    = $cache_md5_pre.md5( $cache_salt );

			return get_transient( $cache_id );
		}

		private function set_notice_transient( $user_id, $value ) {

			$cache_exp_secs = 3600;
			$cache_md5_pre = $this->lca.'_';
			$cache_salt  = 'sucom_notice_transient(user_id:'.$user_id.')';
			$cache_id    = $cache_md5_pre.md5( $cache_salt );

			return set_transient( $cache_id, $value, $cache_exp_secs );
		}

		private function delete_notice_transient( $user_id ) {

			$cache_md5_pre = $this->lca.'_';
			$cache_salt  = 'sucom_notice_transient(user_id:'.$user_id.')';
			$cache_id    = $cache_md5_pre.md5( $cache_salt );

			return delete_transient( $cache_id );
		}
	}
}
