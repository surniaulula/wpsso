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
		private $lca = '';
		private $uca = '';
		private $label = '';
		private $log = array(
			'nag' => array(),
			'err' => array(),
			'inf' => array(),
		);

		public function __construct( &$plugin, $lca = 'sucom', $label = '' ) {
			$this->p =& $plugin;
			if ( ! empty( $this->p->debug->enabled ) )
				$this->p->debug->mark();
			$this->lca = empty( $this->p->cf['lca'] ) ?
				$lca : $this->p->cf['lca'];
			$this->uca = strtoupper( $this->lca );
			$this->label = empty( $label ) ? 
				$this->uca : $label;

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
				$msg_opt = $this->lca.'_notices_'.$type;		// the option name
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

		public function trunc( $idx = '' ) {
			$types = empty( $idx ) ?
				array_keys( $this->log ) : array( $idx );
			foreach ( $types as $type ) {
				$msg_opt = $this->lca.'_notices_'.$type;
				$user_id = get_current_user_id();			// since wp 3.0
				if ( get_option( $msg_opt ) ) {
					update_option( $msg_opt, array() );		// delete doesn't always work, so set empty value first
					delete_option( $msg_opt );
				}
				if ( ! empty( $user_id ) ) {				// exclude wp-cron and/or empty user ids
					if ( get_user_option( $msg_opt, $user_id ) ) {
						update_user_option( $user_id, $msg_opt, array() );
						delete_user_option( $user_id, $msg_opt );
					}
				}
				$this->log[$type] = array();
			}
		}

		public function admin_notices() {
			$all_nag_msgs = '';
			foreach ( array_keys( $this->log ) as $type ) {
				$user_id = get_current_user_id();	// since wp 3.0
				$msg_opt = $this->lca.'_notices_'.$type;
				$msg_arr = array_unique( array_merge( 
					(array) get_option( $msg_opt ), 
					(array) get_user_option( $msg_opt, $user_id ), 
					$this->log[$type] 
				) );
				$this->trunc( $type );
				if ( $type === 'err' && 
					isset( $this->p->cf['plugin'] ) &&
					class_exists( 'SucomUpdate' ) ) {

					foreach ( array_keys( $this->p->cf['plugin'] ) as $lca ) {
						if ( ! empty( $this->p->options['plugin_'.$lca.'_tid'] ) ) {
							$umsg = SucomUpdate::get_umsg( $lca );
							if ( $umsg !== false && $umsg !== true )
								$msg_arr[] = $umsg;
						}
					}
				}
				if ( ! empty( $msg_arr ) ) {
					if ( $type == 'nag' )
						echo $this->get_nag_style( $this->lca );
					foreach ( $msg_arr as $key => $msg ) {
						if ( ! empty( $msg ) ) {
							$label = '';
							$class = '';
							$cssid_attr = strpos( $key, $type.'_' ) === 0 ? ' id="'.$key.'"' : '';
							switch ( $type ) {
								case 'nag':
									$all_nag_msgs .= $msg;	// append to echo later in single div block
									break;

								case 'err':
									if ( empty( $class ) )
										$class = 'error';
									if ( empty( $label ) && ! empty( $this->label ) )
										$label = $this->label.' Notice';	// or 'Warning'
									// no break

								case 'inf':
									// allow for variable definitions in previous case blocks
									if ( empty( $class ) )
										$class = 'updated fade';
									if ( empty( $label ) && ! empty( $this->label ) )
										$label = $this->label.' Notice';	// or 'Info'

									echo '<div class="'.$class.'"'.$cssid_attr.'>';
									if ( ! empty( $label ) )
										echo '<div style="display:table-cell;">
											<p style="margin:5px 0;white-space:nowrap;">
												<b>'.$label.'</b>:</p></div>';
									echo '<div style="display:table-cell;">
										<p style="margin:5px;text-align:left">'.$msg.'</p></div>';
									echo '</div>';

									break;
							}
						}
					}
				}
			}
			if ( ! empty( $all_nag_msgs ) )
				echo '<div class="update-nag '.$this->lca.'-update-nag">', $all_nag_msgs, '</div>', "\n";
		}

		private function get_nag_style( $lca ) {
			return '<style type="text/css">
.'.$lca.'-update-nag {
	display:block;
	line-height:1.4em;
	background-color:'.( empty( $this->p->cf['bgcolor'] ) ?
		'none' : '#'.$this->p->cf['bgcolor'] ).';
	background-image:'.( empty( $this->p->cf['plugin'][$this->lca]['img']['background'] ) ?
		'none' : 'url("'.$this->p->cf['plugin'][$this->lca]['img']['background'].'")' ).';
	background-position:top;
	background-size:cover;
	border:1px dashed #ccc;
	padding:10px 40px 10px 40px;
	margin-top:0;
}
.'.$lca.'-update-nag p,
.'.$lca.'-update-nag ul,
.'.$lca.'-update-nag ol {
	font-size:1em;
	clear:both;
	max-width:720px;
	margin:15px auto 15px auto;
	text-align:center;
}
.'.$lca.'-update-nag ul li {
	list-style-type:square;
}
.'.$lca.'-update-nag ol li {
	list-style-type:decimal;
}
.'.$lca.'-update-nag li {
	text-align:left;
	margin:5px 0 5px 60px;
}
</style>';
		}
	}
}

?>
