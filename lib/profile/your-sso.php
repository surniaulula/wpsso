<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
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
		}

		/*
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$metabox_id      = $this->p->cf[ 'meta' ][ 'id' ];
			$metabox_title   = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'metabox_id'    => $metabox_id,
				'metabox_title' => $metabox_title,
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title, array( $this, 'show_metabox_' . $metabox_id ),
				$this->pagehook, $metabox_context, $metabox_prio, $callback_args );
		}

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
