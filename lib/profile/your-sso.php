<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoProfileYourSSO' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoProfileYourSSO extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;

			$metabox_id    = $this->p->cf[ 'meta' ][ 'id' ];
			$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

			$this->menu_metaboxes = array( $metabox_id => $metabox_title );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_sso() {

			$user_id  = get_current_user_id();
			$user_obj = get_userdata( $user_id );

			if ( empty( $user_obj->ID ) ) {	// Just in case.

				wp_die( __( 'Invalid user ID.' ) );
			}

			$this->p->user->show_metabox_sso( $user_obj );
		}
	}
}
