<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomNotice' ) ) {

	class SucomNotice {

		private $p;
		private $lca;
		private $log = array(
			'err' => array(),
			'inf' => array(),
			'nag' => array(),
		);

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->lca = empty( $this->p->cf['lca'] ) ? 
				'sucom' : $this->p->cf['lca'];

			add_action( 'all_admin_notices', array( &$this, 'admin_notices' ) );
		}

		public function nag( $msg = '', $store = false, $user = true, $cssid = null ) { 
			$this->log( 'nag', $msg, $store, $user, $cssid );
		}

		public function err( $msg = '', $store = false, $user = true, $cssid = null ) {
			$this->log( 'err', $msg, $store, $user, $cssid );
		}

		public function inf( $msg = '', $store = false, $user = true, $cssid = null ) {
			$this->log( 'inf', $msg, $store, $user, $cssid );
		}

		public function log( $type, $msg = '', $store = false, $user = true, $cssid = null ) {
			if ( empty( $msg ) ) 
				return;
			if ( $store == true ) {						// save the message in the database
				$user_id = get_current_user_id();			// since wp 3.0
				if ( empty( $user_id ) )				// exclude wp-cron and/or empty user ids
					$user = false;
				$msg_opt = $this->lca.'_notices_'.$type;	// the option name
				if ( $user == true )					// get the message array from the user table
					$msg_arr = get_user_option( $msg_opt, $user_id );
				else $msg_arr = get_option( $msg_opt );			// get the message array from the options table
				if ( $msg_arr === false ) 
					$msg_arr = array();				// if the array doesn't already exist, define a new one
				if ( ! in_array( $msg, $msg_arr ) ) {			// dont't save duplicates
					if ( ! empty( $cssid ) )
						$msg_arr[$type.'_'.$cssid] = $msg;
					else $msg_arr[] = $msg;
				}
				if ( $user == true )					// update the user option table
					update_user_option( $user_id, $msg_opt, $msg_arr );
				else update_option( $msg_opt, $msg_arr );		// update the option table
			} elseif ( ! in_array( $msg, $this->log[$type] ) ) {		// dont't save duplicates
				if ( ! empty( $cssid ) )
					$this->log[$type][$type.'_'.$cssid] = $msg;
				else $this->log[$type][] = $msg;
			}
		}

		public function trunc( $type ) {
			$user_id = get_current_user_id();	// since wp 3.0
			$msg_opt = $this->lca.'_notices_'.$type;
			// delete doesn't always work, so set an empty value first
			if ( get_option( $msg_opt ) ) {
				update_option( $msg_opt, array() );
				delete_option( $msg_opt );
			}
			if ( get_user_option( $msg_opt, $user_id ) ) {
				update_user_option( $user_id, $msg_opt, array() );
				delete_user_option( $user_id, $msg_opt );
			}
			$this->log[$type] = array();
		}

		public function admin_notices() {
			$all_nag_msgs = '';
			foreach ( array( 'nag', 'err', 'inf' ) as $type ) {
				$user_id = get_current_user_id();	// since wp 3.0
				$msg_opt = $this->lca.'_notices_'.$type;
				$msg_arr = array_unique( array_merge( 
					(array) get_option( $msg_opt ), 
					(array) get_user_option( $msg_opt, $user_id ), 
					$this->log[$type] 
				) );
				$this->trunc( $type );
				if ( $type === 'err' && class_exists( 'SucomUpdate' ) ) {
					foreach ( $this->p->cf['plugin'] as $lca => $info ) {
						if ( ( $umsg = SucomUpdate::get_umsg( $lca ) ) !== false && $umsg !== true )
							$msg_arr[] = $umsg;
					}
				}
				if ( ! empty( $msg_arr ) ) {
					if ( $type == 'nag' ) 
						echo '<style type="text/css">
	.'.$this->lca.'-update-nag {
		display:block;
		line-height:1.4em;
		background-image:url("'.constant( $this->p->cf['uca'].'_URLPATH' ).'images/background.jpg");
		background-position:top;
		background-size:cover;
		border:1px dashed #ccc;
		padding:10px 40px 10px 40px;
		margin-top:0;
	}
	.'.$this->lca.'-update-nag p,
	.'.$this->lca.'-update-nag ul {
		clear:both;
		max-width:900px;
		margin:15px auto 15px auto;
		text-align:center;
	}
	.'.$this->lca.'-update-nag li {
		list-style:circle outside none;
		text-align:left;
		margin:5px 0 5px 20px;
	}
</style>';
					foreach ( $msg_arr as $key => $msg ) {
						if ( ! empty( $msg ) ) {
							$cssid = strpos( $key, $type.'_' ) === 0 ? $cssid=' id="'.$key.'"' : '';
							switch ( $type ) {
								case 'nag':
									$all_nag_msgs .= $msg;
									break;
								case 'err':
									echo '<div class="error"'.$cssid.'>
									<div style="float:left;margin-right:5px;"><p style="white-space:nowrap;"><b>'.
										$this->p->cf['menu'].' Warning</b>:</p></div>
									<div><p style="text-align:left">'.$msg.'</p></div>
									</div>';
									break;
								case 'inf':
									echo '<div class="updated fade"'.$cssid.'>
									<div style="float:left;margin-right:5px;"><p style="white-space:nowrap;"><b>'.
										$this->p->cf['menu'].' Info</b>:</p></div>
									<div><p style="text-align:left">'.$msg.'</p></div>
									</div>';
									break;
							}
						}
					}
				}
			}
			if ( ! empty( $all_nag_msgs ) )
				echo '<div class="update-nag '.$this->lca.'-update-nag">', $all_nag_msgs, '</div>', "\n";
		}
	}
}

?>
