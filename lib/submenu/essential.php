<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoSubmenuGeneral' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/submenu/general.php';
}

if ( ! class_exists( 'WpssoSubmenuEssential' ) && class_exists( 'WpssoSubmenuGeneral' ) ) {

	class WpssoSubmenuEssential extends WpssoSubmenuGeneral {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;

			$this->menu_metaboxes = array(
				'settings' => _x( 'Essential Settings', 'metabox title', 'wpsso' ),
			);
		}

		/*
		 * Remove the "Change to View" button from this settings page.
		 */
		protected function add_form_buttons_change_show_options( &$form_button_rows ) {
		}
	}
}
