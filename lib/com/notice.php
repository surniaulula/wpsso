<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomNotice' ) ) {

	class SucomNotice {

		private $p;
		private $lca = 'sucom';
		private $text_dom = 'sucom';
		private $opt_name = 'sucom_notices';
		private $dis_name = 'sucom_dismissed';
		private $hide_err = false;
		private $hide_warn = false;
		private $all_types = array( 'nag', 'err', 'warn', 'upd', 'inf' );
		private $notice_cache = array();
		private $reference_url = null;
		private $has_shown = false;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( ! empty( $this->p->debug->enabled ) )
				$this->p->debug->mark();

			if ( ! empty( $this->p->cf['lca'] ) ) {
				$this->lca = $this->p->cf['lca'];
				if ( ! empty( $this->p->cf['plugin'][$this->lca]['text_domain'] ) )
					$this->text_dom = $this->p->cf['plugin'][$this->lca]['text_domain'];
			}

			$uca = strtoupper( $this->lca );

			$this->opt_name = defined( $uca.'_NOTICE_NAME' ) ? 
				constant( $uca.'_NOTICE_NAME' ) : $this->lca.'_notices';

			$this->dis_name = defined( $uca.'_DISMISS_NAME' ) ? 
				constant( $uca.'_DISMISS_NAME' ) : $this->lca.'_dismissed';

			$this->hide_err = defined( $uca.'_HIDE_ALL_ERRORS' ) ? 
				constant( $uca.'_HIDE_ALL_ERRORS' ) : false;

			$this->hide_warn = defined( $uca.'_HIDE_ALL_WARNINGS' ) ? 
				constant( $uca.'_HIDE_ALL_WARNINGS' ) : false;

			if ( is_admin() ) {
				add_action( 'wp_ajax_'.$this->lca.'_dismiss_notice', array( &$this, 'ajax_dismiss_notice' ) );
				add_action( 'admin_footer', array( &$this, 'admin_footer_script' ) );
				add_action( 'in_admin_header', array( &$this, 'hook_admin_notices' ), 300000 );
			}

			add_action( 'shutdown', array( &$this, 'shutdown_save_notices' ) );
		}

		public function hook_admin_notices() { 
			add_action( 'all_admin_notices', array( &$this, 'show_admin_notices' ), -10 );
		}

		public function nag( $msg_txt, $user_id = true, $msg_id = false ) { 
			$this->log( 'nag', $msg_txt, $user_id, $msg_id, false );	// $dismiss = false
		}

		public function err( $msg_txt, $user_id = true, $msg_id = false, $dismiss = false ) {
			$this->log( 'err', $msg_txt, $user_id, $msg_id, $dismiss );
		}

		public function warn( $msg_txt, $user_id = true, $msg_id = false, $dismiss = false ) {
			$this->log( 'warn', $msg_txt, $user_id, $msg_id, $dismiss );
		}

		public function upd( $msg_txt, $user_id = true, $msg_id = false, $dismiss = false ) {
			$this->log( 'upd', $msg_txt, $user_id, $msg_id, $dismiss );
		}

		public function inf( $msg_txt, $user_id = true, $msg_id = false, $dismiss = false ) {
			$this->log( 'inf', $msg_txt, $user_id, $msg_id, $dismiss );
		}

		public function log( $msg_type, $msg_txt, $user_id = true, $msg_id = false, $dismiss = false, $payload = array() ) {

			if ( empty( $msg_type ) || 
				empty( $msg_txt ) )
					return;

			$payload['msg_id'] = empty( $msg_id ) ? false : $msg_type.'_'.$msg_id;

			// msg_id required to dismiss a notice
			$payload['dismiss'] = ! empty( $msg_id ) && ! empty( $dismiss ) && $this->can_dismiss() ? $dismiss : false;

			if ( is_numeric( $payload['dismiss'] ) && $payload['dismiss'] > 0 ) {
				$msg_txt .= ' '.sprintf( __( 'This notice may be dismissed for %s.', $this->text_dom ), 
					human_time_diff( 0, $payload['dismiss'] ) );
			}

			if ( $this->reference_url ) {
				$msg_txt .= '<br/><small>'.sprintf( __( 'Reference URL: %s', $this->text_dom ),
					'<a href="'.$this->reference_url.'">'.$this->reference_url.'</a>' ).'</small>';
			}

			if ( $user_id === true )
				$user_id = (int) get_current_user_id();
			else $user_id = (int) $user_id;	// false = 0

			$user_notices =& $this->get_user_notices( $user_id );	// returns reference

			if ( ! isset( $user_notices[$msg_type][$msg_txt] ) ) 
				$user_notices[$msg_type][$msg_txt] = $payload;
		}

		public function trunc_id( $msg_id, $user_id = true ) {
			$this->trunc( '', '', $user_id, $msg_id );
		}

		public function trunc_all() {
			$this->trunc( '', '', 'all', false );
		}

		public function trunc( $msg_type = '', $msg_txt = '', $user_id = true, $msg_id = false ) {

			if ( $user_id === true )
				$user_ids = array( get_current_user_id() );
			elseif ( $user_id === 'all' )
				$user_ids = $this->get_user_ids();	// returns reference
			elseif ( is_array( $user_id ) )
				$user_ids = $user_id;
			else $user_ids = array( $user_id );

			$trunc_types = empty( $msg_type ) ? 
				$this->all_types : array( (string) $msg_type );

			foreach ( $user_ids as $user_id ) {
				$user_notices =& $this->get_user_notices( $user_id );	// returns reference
				foreach ( $trunc_types as $msg_type ) {
					if ( isset( $user_notices[$msg_type] ) ) {
						if ( ! empty( $msg_id ) &&				// clear notice for a specific msg id
							is_array( $user_notices[$msg_type] ) ) {
							foreach ( $user_notices[$msg_type] as $msg_txt => &$payload ) {	// use payload reference
								if ( ! empty( $payload['msg_id'] ) &&
									$payload['msg_id'] === $msg_type.'_'.$msg_id )
										unset( $payload );	// unset using reference
							}
						} elseif ( empty( $msg_txt ) ) {			// clear all notices for that type
							$user_notices[$msg_type] = array();
						} elseif ( isset( $user_notices[$msg_type][$msg_txt] ) ) {	// clear a specific message string
							unset( $user_notices[$msg_type][$msg_txt] );
						}
					}
				}
			}
		}

		// returns the previous URL
		public function set_reference_url( $url = null ) {
			$previous_url = $this->reference_url;
			$this->reference_url = $url;
			return $previous_url;
		}

		public function get_reference_url() {
			return $this->reference_url;
		}

		public function is_admin_pre_notices() {
			if ( is_admin() && ! $this->has_shown )
				return true;
			else return false;
		}

		public function show_admin_notices() {
			$hidden = array();
			$msg_html = '';
			$nag_msgs = '';
			$all_msgs = array();	// duplicate check
			$dismissed_updated = false;
			$user_id = (int) get_current_user_id();
			$user_notices =& $this->get_user_notices( $user_id );	// returns reference
			$user_dismissed = empty( $user_id ) ? false : 		// just in case
				get_user_option( $this->dis_name, $user_id );	// get dismissed message ids
			$this->has_shown = true;

			if ( isset( $this->p->cf['plugin'] ) && class_exists( 'SucomUpdate' ) ) {
				foreach ( array_keys( $this->p->cf['plugin'] ) as $lca ) {
					if ( ! empty( $this->p->options['plugin_'.$lca.'_tid'] ) ) {
						$uerr = SucomUpdate::get_umsg( $lca );
						if ( ! empty( $uerr ) )
							$user_notices['err'][$uerr] = array();
					}
				}
			}

			foreach ( $this->all_types as $msg_type ) {
				if ( ! isset( $user_notices[$msg_type] ) )	// just in case
					continue;
				foreach ( $user_notices[$msg_type] as $msg_txt => $payload ) {
					if ( empty( $msg_txt ) || 
						isset( $all_msgs[$msg_txt] ) )	// skip duplicates
							continue;
					$all_msgs[$msg_txt] = true;		// avoid duplicates
					switch ( $msg_type ) {
						case 'nag':
							$nag_msgs .= $msg_txt;	// append to echo a single message
							continue;
						default:
							// dismiss will always be false if there's no msg id
							if ( ! empty( $payload['dismiss'] ) ) {
								if ( ! isset( $hidden[$msg_type] ) )
									$hidden[$msg_type] = 0;

								// check for automatically hidden errors and/or warnings
								if ( ( $msg_type === 'err' && $this->hide_err ) ||
									( $msg_type === 'warn' && $this->hide_warn ) ) {

									$payload['hidden'] = true;
									if ( empty( $payload['silent'] ) )
										$hidden[$msg_type]++;

								// msg id has been dismissed
								} elseif ( ! empty( $payload['msg_id'] ) && 
									isset( $user_dismissed[$payload['msg_id']] ) ) {

									$now_time = time();
									$dis_time = $user_dismissed[$payload['msg_id']];

									if ( empty( $dis_time ) || $dis_time > $now_time ) {
										$payload['hidden'] = true;
										if ( empty( $payload['silent'] ) )
											$hidden[$msg_type]++;
									} else {	// dismiss has expired
										$dismissed_updated = true;	// update the array when done
										unset( $user_dismissed[$payload['msg_id']] );
									}
								}
							}
							$msg_html .= $this->get_notice_html( $msg_type, $msg_txt, $payload );
							break;
					}
				}
			}

			// delete all notices for the current user id
			$this->trunc();

			// don't save unless we've changed something
			if ( $dismissed_updated === true && ! empty( $user_id ) ) {
				if ( empty( $user_dismissed ) )
					delete_user_option( $user_id, $this->dis_name );
				else update_user_option( $user_id, $this->dis_name, $user_dismissed );
			}

			echo "\n";
			echo '<!-- '.$this->lca.' admin notices begin -->'."\n";

			if ( ! empty( $nag_msgs ) ) {
				echo $this->get_nag_style();
				echo $this->get_notice_html( 'nag', $nag_msgs );
			}

			// remind the user that there are hidden error messages
			foreach ( array(
				'err' => _x( 'error', 'notification type', $this->text_dom ),
				'warn' => _x( 'warning', 'notification type', $this->text_dom ),
			) as $msg_type => $log_name ) {
				if ( empty( $hidden[$msg_type] ) )
					continue;
				elseif ( $hidden[$msg_type] > 1 )
					$msg_text = __( '%1$d important %2$s notices have been hidden and/or dismissed &mdash; <a id="%3$s">unhide and view the %2$s messages</a>.', $this->text_dom );
				else $msg_text = __( '%1$d important %2$s notice has been hidden and/or dismissed &mdash; <a id="%3$s">unhide and view the %2$s message</a>.', $this->text_dom );
				echo $this->get_notice_html( $msg_type, sprintf( $msg_text, $hidden[$msg_type], $log_name, $this->lca.'-unhide-notices' ) );
			}

			echo $msg_html;
			echo '<!-- '.$this->lca.' admin notices end -->'."\n";
		}

		public function ajax_dismiss_notice() {
			$dismiss = array();
			$user_id = get_current_user_id();

			if ( empty( $user_id ) || 
				! current_user_can( 'edit_user', $user_id ) )
					die( '-1' );

			check_ajax_referer( __FILE__, '_ajax_nonce', true );

			// read arguments
			foreach ( array( 'id', 'time' ) as $key )
				$dismiss[$key] = sanitize_text_field( filter_input( INPUT_POST, $key ) );

			if ( empty( $dismiss['id'] ) )
				die( '-1' );

			// site specific user options
			$user_dismissed = get_user_option( $this->dis_name, $user_id );
			if ( ! is_array( $user_dismissed ) ) 
				$user_dismissed = array();

			// save the message id and expiration time (0 = never)
			$user_dismissed[$dismiss['id']] = empty( $dismiss['time'] ) || 
				! is_numeric( $dismiss['time'] ) ? 0 : time() + $dismiss['time'];

			update_user_option( $user_id, $this->dis_name, $user_dismissed );

			die( '1' );
		}

		public function admin_footer_script() {
			echo '
<script type="text/javascript">
	jQuery( document ).ready( function() {
		jQuery("#'.$this->lca.'-unhide-notices").click( function() {
			var notice = jQuery( this ).parents(".'.$this->lca.'-notice"); 
			jQuery(".'.$this->lca.'-dismissible").show();
			notice.hide();
		});
		jQuery(".'.$this->lca.'-dismissible > .notice-dismiss").click( function() {
			var notice = jQuery( this ).parent(".'.$this->lca.'-dismissible"); 
			var dismiss_nonce = notice.data( "dismiss-nonce" );
			var dismiss_id = notice.data( "dismiss-id" );
			var dismiss_time = notice.data( "dismiss-time" );
			jQuery.post(
				ajaxurl, {
					action: "'.$this->lca.'_dismiss_notice",
					_ajax_nonce: dismiss_nonce,
					id: dismiss_id,
					time: dismiss_time
				}
			);
			notice.hide();
		});
	});
</script>
			';
		}

		private function get_notice_html( $msg_type, $msg_txt, $payload = array() ) {
			$charset = get_bloginfo( 'charset' );

			if ( ! isset( $payload['label'] ) )
				$payload['label'] = sprintf( __( '%s Note', $this->text_dom ), strtoupper( $this->lca ) );

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
				default:
					$msg_class = 'notice notice-info';
					break;
			}

			// msg_id and dismiss must have values to create a dismissible notice
			$is_dismissible = empty( $payload['msg_id'] ) || 
				empty( $payload['dismiss'] ) ? false : true;

			$cssid_attr = empty( $payload['msg_id'] ) ? 
				'' : ' id="'.$payload['msg_id'].'"';

			$data_attr = ! $is_dismissible ? 
				'' : ' data-dismiss-nonce="'.wp_create_nonce( __FILE__ ).'"'.
					' data-dismiss-id="'.$payload['msg_id'].'"'.
					' data-dismiss-time="'.( is_numeric( $payload['dismiss'] ) ? $payload['dismiss'] : 0 ).'"';

			// optionally hide notices if required
			$style_attr = ' style="'.
				( empty( $payload['style'] ) ? 
					'' : $payload['style'] ).
				( empty( $payload['hidden'] ) ? 'display:block !important; visibility:visible !important;' : 'display:none;' ).'"';

			$msg_html = '<div class="'.$this->lca.'-notice '.
				( ! $is_dismissible ? '' : $this->lca.'-dismissible ' ).
					$msg_class.'"'.$cssid_attr.$style_attr.$data_attr.'>';	// display block or none

			if ( ! empty( $payload['dismiss'] ) )
				$msg_html .= '<div class="notice-dismiss"><div class="notice-dismiss-text">Dismiss</div></div>';

			if ( ! empty( $payload['label'] ) ) {
				$msg_html .= '<div class="notice-label">'.
					$payload['label'].'</div>';
			}

			$msg_html .= '<div class="notice-message">'.$msg_txt.'</div>';
			$msg_html .= '</div>'."\n";

			return $msg_html;
		}

		private function get_nag_style() {
			return '<style type="text/css">
.'.$this->lca.'-notice.update-nag {
	line-height:1.4em;
	padding:0 40px;
	margin-top:0;
	border:1px dotted #ccc;
	background-color:'.( empty( $this->p->cf['bgcolor'] ) ?
		'none' : '#'.$this->p->cf['bgcolor'] ).';
	background-image:'.( empty( $this->p->cf['plugin'][$this->lca]['img']['background'] ) ?
		'none' : 'url("'.$this->p->cf['plugin'][$this->lca]['img']['background'].'")' ).';
	background-position:top;
	background-size:cover;
}
.'.$this->lca.'-notice.update-nag > div {
	clear:both;
	display:block !important;
	margin:0 auto;
	max-width:850px;
}
.'.$this->lca.'-notice.update-nag p,
.'.$this->lca.'-notice.update-nag ul,
.'.$this->lca.'-notice.update-nag ol {
	font-size:1em;
	text-align:center;
	margin:15px auto 15px auto;
}
.'.$this->lca.'-notice.update-nag ul li {
	list-style-type:square;
}
.'.$this->lca.'-notice.update-nag ol li {
	list-style-type:decimal;
}
.'.$this->lca.'-notice.update-nag li {
	text-align:left;
	margin:5px 0 5px 60px;
}
</style>'."\n";
		}

		private function can_dismiss() {
			global $wp_version;
			if ( version_compare( $wp_version, 4.2, '>=' ) )
				return true;
			else return false;
		}

		private function &get_user_ids() {
			$user_ids = array();
			foreach ( get_users() as $user )
				$user_ids[] = $user->ID;
			return $user_ids;
		}

		private function &get_user_notices( $user_id = true ) {
			if ( $user_id === true )
				$user_id = (int) get_current_user_id();
			else $user_id = (int) $user_id;	// false = 0

			if ( isset( $this->notice_cache[$user_id] ) )
				return $this->notice_cache[$user_id];

			if ( $user_id > 0 ) {
				$this->notice_cache[$user_id] = get_user_option( $this->opt_name, $user_id );
				if ( is_array( $this->notice_cache[$user_id] ) )
					$this->notice_cache[$user_id]['have_notices'] = true;
				else $this->notice_cache[$user_id] = array( 'have_notices' => false );
			}

			foreach ( $this->all_types as $msg_type )
				if ( ! isset( $this->notice_cache[$user_id][$msg_type] ) )
					$this->notice_cache[$user_id][$msg_type] = array();

			return $this->notice_cache[$user_id];
		}

		public function shutdown_save_notices() {
			$user_id = (int) get_current_user_id();
			$have_notices = false;
			if ( $user_id > 0 ) {
				if ( isset( $this->notice_cache[$user_id]['have_notices'] ) ) {
					$have_notices = $this->notice_cache[$user_id]['have_notices'];
					unset( $this->notice_cache[$user_id]['have_notices'] );
				}
				if ( empty( $this->notice_cache[$user_id] ) ) {
					if ( $have_notices )
						delete_user_option( $user_id, $this->opt_name );
				} else update_user_option( $user_id, $this->opt_name, $this->notice_cache[$user_id] );
			}
		}
	}
}

?>
