<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoProfileYourSSO' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoProfileYourSSO extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;
		}

		protected function add_plugin_hooks() {
			$this->p->util->add_plugin_filters( $this, array(
				'action_buttons' => 1,
			) );
		}

		// called by the extended WpssoAdmin class
		protected function add_meta_boxes() {

			$metabox_id = $this->p->cf['meta']['id'];
			$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

			add_meta_box( $this->pagehook.'_'.$metabox_id, $metabox_title, 
				array( $this, 'show_metabox_custom_meta' ), $this->pagehook, 'normal' );

		}

		public function filter_action_buttons( $action_buttons ) {

			unset( $action_buttons[1]['clear_all_cache'] );

			return $action_buttons;
		}

		public function show_metabox_custom_meta() {

			$user_id = get_current_user_id();	// since wp 3.0
			$user = get_userdata( $user_id );

			if ( empty( $user->ID ) ) {	// just in case
				wp_die( __( 'Invalid user ID.' ) );
			}

			$this->p->m['util']['user']->show_metabox_custom_meta( $user );
		}
	}
}

