<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
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
		private $log = array(
			'nag' => array(),
			'err' => array(),
			'inf' => array(),
		);

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

			if ( is_admin() ) {
				add_action( 'wp_ajax_'.$this->lca.'_dismiss_notice', array( &$this, 'ajax_dismiss_notice' ) );
				add_action( 'admin_footer', array( &$this, 'admin_footer_script' ) );
				add_action( 'all_admin_notices', array( &$this, 'show_admin_notices' ), 5 );	// since wp 3.1
			}
		}

		public function can_dismiss() {
			global $wp_version;
			if ( version_compare( $wp_version, 4.2, '>=' ) )
				return true;
			else return false;
		}

		public function nag( $msg_txt, $store = false, $user_id = true, $msg_id = false ) { 
			$this->log( 'nag', $msg_txt, $store, $user_id, $msg_id, false );	// $dismiss = false
		}

		public function err( $msg_txt, $store = false, $user_id = true, $msg_id = false, $dismiss = false ) {
			$this->log( 'err', $msg_txt, $store, $user_id, $msg_id, $dismiss );
		}

		public function inf( $msg_txt, $store = false, $user_id = true, $msg_id = false, $dismiss = false ) {
			$this->log( 'inf', $msg_txt, $store, $user_id, $msg_id, $dismiss );
		}

		// $user_id can be true, false, or an id number
		// $dismiss can be true, false, or a number of seconds
		public function log( $type, $msg_txt, $store = false, $user_id = true, $msg_id = false, $dismiss = false, 
			$payload = array() ) {

			// sanity checks
			if ( empty( $type ) ||
				empty( $msg_txt ) ) 
					return;

			$payload['msg_id'] = empty( $msg_id ) ? 
				'' : $type.'_'.$msg_id;

			$payload['dismiss'] = ! empty( $dismiss ) && 
				! empty( $msg_id ) && 
					$this->can_dismiss() === true ? 
						$dismiss : false;
			
			// save message until it can be displayed
			if ( $store === true ) {
				if ( $user_id === true )
					$user_id = get_current_user_id();
				if ( empty( $user_id ) )
					$msg_arr = get_option( $this->opt_name );
				else $msg_arr = get_user_option( $this->opt_name, $user_id );

				if ( ! is_array( $msg_arr ) ) 
					foreach ( array_keys( $this->log ) as $check )
						$msg_arr[$check] = array();

				if ( ! isset( $msg_arr[$type][$msg_txt] ) ) {
					$msg_arr[$type][$msg_txt] = $payload;
					if ( empty( $user_id ) )
						update_option( $this->opt_name, $msg_arr );
					else update_user_option( $user_id, $this->opt_name, $msg_arr );
				}
			} elseif ( ! isset( $this->log[$type][$msg_txt] ) )
				$this->log[$type][$msg_txt] = $payload;
		}

		public function trunc_id( $msg_id ) {
			return $this->trunc( '', '', true, true, $msg_id );
		}

		// truncates all notices by default
		public function trunc( $type = '', $msg_txt = '', $store = true, $user_id = true, $msg_id = false ) {
			$types = empty( $type ) ? 
				array_keys( $this->log ) : 
				array( $type );
			if ( $user_id === true )
				$user_id = get_current_user_id();
			$all_opts = $this->get_all_options( $user_id );
			foreach ( array( 'opt', 'usr', 'log' ) as $name ) {
				$have_changes = false;
				foreach ( $types as $type ) {
					if ( isset( $all_opts[$name][$type] ) ) {
						// clear msg for a specific msg id
						if ( ! empty( $msg_id ) ) {
							foreach ( $all_opts[$name][$type] as $msg_txt => $payload ) {
								if ( $payload['msg_id'] === $type.'_'.$msg_id ) {
									unset( $all_opts[$name][$type][$msg_txt] );
									$have_changes = true;
								}
							}
						// clear all msgs for that type
						} elseif ( empty( $msg_txt ) ) {
							if ( $name === 'log' )
								$this->log[$type] = array();
							else {
								unset( $all_opts[$name][$type] );
								$have_changes = true;
							}
						// clear a specific message
						} elseif ( isset( $all_opts[$name][$type][$msg_txt] ) ) {
							if ( $name === 'log' )
								unset( $this->log[$type][$msg_txt] );
							else {
								unset( $all_opts[$name][$type][$msg_txt] );
								$have_changes = true;
							}
						}
					}
				}
				if ( $store === true && 
					$have_changes === true ) {
					switch( $name ) {
						case 'opt':
							if ( empty( $all_opts[$name] ) )
								delete_option( $this->opt_name );
							else update_option( $this->opt_name, $all_opts[$name] );
							break;
						case 'usr':
							if ( ! empty( $user_id ) ) {
								if ( empty( $all_opts[$name] ) )
									delete_user_option( $user_id, $this->opt_name );
								else update_user_option( $user_id, $this->opt_name, $all_opts[$name] );
							}
							break;
					}
				}
			}
		}

		public function show_admin_notices() {
			$hidden = array();
			$msg_html = '';
			$nag_msgs = '';
			$all_opts = $this->get_all_options();
			$all_msgs = array();

			$have_changes = false;
			$user_id = get_current_user_id();
			$dis_arr = empty( $user_id ) ? false : 			// just in case
				get_user_option( $this->dis_name, $user_id );	// get dismissed message ids

			if ( isset( $this->p->cf['plugin'] ) && class_exists( 'SucomUpdate' ) ) {
				foreach ( array_keys( $this->p->cf['plugin'] ) as $lca ) {
					if ( ! empty( $this->p->options['plugin_'.$lca.'_tid'] ) ) {
						$umsg = SucomUpdate::get_umsg( $lca );
						if ( $umsg !== false && $umsg !== true )
							$all_opts['log']['err'][$umsg] = array();
					}
				}
			}

			foreach ( array( 'opt', 'usr', 'log' ) as $name ) {
				foreach ( array_keys( $this->log ) as $type ) {
					foreach ( $all_opts[$name][$type] as $msg_txt => $payload ) {
						if ( empty( $msg_txt ) || 
							isset( $all_msgs[$msg_txt] ) )
								continue;
						$all_msgs[$msg_txt] = true;	// avoid duplicates
						switch ( $type ) {
							case 'nag':
								$nag_msgs .= $msg_txt;	// append to echo a single message
								continue;
							default:
								if ( ! empty( $payload['dismiss'] ) &&
									isset( $dis_arr[$payload['msg_id']] ) ) {

									$now_time = time();
									$dis_time = $dis_arr[$payload['msg_id']];
									if ( empty( $dis_time ) || $dis_time > $now_time ) {
										$payload['hidden'] = true;
										if ( isset( $hidden[$type] ) )
											$hidden[$type]++;
										else $hidden[$type] = 1;
									} else {
										$have_changes = true;
										unset( $dis_arr[$payload['msg_id']] );
									}
								}
								$msg_html .= $this->get_notice_html( $type, $msg_txt, $payload );
								break;
						}
					}
				}
			}
			$this->trunc();

			// don't save unless we've changes something
			if ( $have_changes === true &&
				! empty( $user_id ) ) {
				if ( empty( $dis_arr ) )
					delete_user_option( $user_id, $this->dis_name );
				else update_user_option( $user_id, $this->dis_name, $dis_arr );
			}

			echo "\n";
			echo '<!-- '.$this->lca.' admin notices begin -->'."\n";

			if ( ! empty( $nag_msgs ) ) {
				echo $this->get_nag_style();
				echo $this->get_notice_html( 'nag', $nag_msgs );
			}

			// remind the user that there are hidden warning messages
			if ( isset( $hidden['err'] ) ) {
				if ( $hidden['err'] > 1 )
					echo $this->get_notice_html( 'inf', sprintf( __( '%1$d warning messages have been hidden &mdash; <a id="%2$s">unhide these notices temporarily</a>.', $this->text_dom ), $hidden['err'], $this->lca.'-unhide-notices' ) );
				elseif ( $hidden['err'] > 0 )
					echo $this->get_notice_html( 'inf', sprintf( __( '%1$d warning message has been hidden &mdash; <a id="%2$s">unhide this notice temporarily</a>.', $this->text_dom ), $hidden['err'], $this->lca.'-unhide-notices' ) );
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
			$dis_arr = get_user_option( $this->dis_name, $user_id );
			if ( ! is_array( $dis_arr ) ) 
				$dis_arr = array();

			// save the message id and expiration time (0 = never)
			$dis_arr[$dismiss['id']] = empty( $dismiss['time'] ) || 
				! is_numeric( $dismiss['time'] ) ? 0 : time() + $dismiss['time'];

			update_user_option( $user_id, $this->dis_name, $dis_arr );

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

		private function get_notice_html( $type, $msg_txt, $payload = array() ) {

			if ( ! isset( $payload['label'] ) )
				$payload['label'] = sprintf( __( '%s Notice',
					$this->text_dom ), strtoupper( $this->lca ) );

			switch ( $type ) {
				case 'nag':
					$payload['label'] = '';
					$msg_class = 'update-nag';
					break;
				case 'err':
					$msg_class = 'error';
					break;
				case 'inf':
				default:
					$msg_class = 'updated fade';
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
					' data-dismiss-time="'.( is_numeric( $payload['dismiss'] ) ? 
						$payload['dismiss'] : 0 ).'"';

			// optionally hide notices if required
			$style_attr = ' style="'.
				( empty( $payload['style'] ) ? 
					'' : $payload['style'] ).
				( empty( $payload['hidden'] ) ? 
					'display:block !important; visibility:visible !important;' : 
					'display:none;' ).'"';

			$msg_html = '<div class="'.$this->lca.'-notice '.
				( ! $is_dismissible ? '' : $this->lca.'-dismissible ' ).
					$msg_class.'"'.$cssid_attr.$style_attr.$data_attr.'>';	// display block or none

			if ( ! empty( $payload['dismiss'] ) )
				$msg_html .= '<div class="notice-dismiss"><div class="notice-dismiss-text">Dismiss</div></div>';

			if ( ! empty( $payload['label'] ) ) {
				$msg_html .= '<div class="notice-label">'.
					$payload['label'].'</div>';
			}

			$msg_html .= '<div class="notice-message">'.
				$msg_txt.'</div>';

			$msg_html .= '</div>'."\n";

			return $msg_html;
		}

		private function get_all_options( $user_id = true ) {
			if ( $user_id === true )
				$user_id = get_current_user_id();
			$all_opts = array(
				'opt' => get_option( $this->opt_name ),
				'usr' => empty( $user_id ) ? array() : 
					get_user_option( $this->opt_name, $user_id ),
				'log' => $this->log,
			);
			// sanity check - make sure array types exist
			foreach ( array( 'opt', 'usr' ) as $name ) {
				foreach ( array_keys( $this->log ) as $type ) {
					if ( ! isset( $all_opts[$name][$type] ) )
						$all_opts[$name][$type] = array();
				}
			}
			return $all_opts;
		}

		private function get_nag_style() {
			return '<style type="text/css">
.'.$this->lca.'-notice.update-nag {
	line-height:1.4em;
	background-color:'.( empty( $this->p->cf['bgcolor'] ) ?
		'none' : '#'.$this->p->cf['bgcolor'] ).';
	background-image:'.( empty( $this->p->cf['plugin'][$this->lca]['img']['background'] ) ?
		'none' : 'url("'.$this->p->cf['plugin'][$this->lca]['img']['background'].'")' ).';
	background-position:top;
	background-size:cover;
	border:1px dashed #ccc;
	padding:0 40px;
	margin-top:0;
}
.'.$this->lca.'-notice.update-nag > div {
	clear:both;
	display:block !important;
	margin:0 auto;
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
	}
}

?>
