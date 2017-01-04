<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoProfileSocialSettings' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoProfileSocialSettings extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;
		}

		protected function add_meta_boxes() {
			add_meta_box( $this->pagehook.'_social_settings', 
				_x( 'Social Settings', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_social_settings' ), $this->pagehook, 'normal' );

		}

		public function show_metabox_social_settings() {
			$user_id = get_current_user_id();	// since wp 3.0
			$user = get_userdata( $user_id );
			if ( empty( $user->ID ) )
				wp_die( __( 'Invalid user ID.' ) );
			$this->p->m['util']['user']->show_metabox_social_settings( $user );
		}
	}
}

?>
