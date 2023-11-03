<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuAddons' ) && class_exists( 'WpssoAdmin' ) ) {

	/*
	 * Please note that this settings page also requires enqueuing special scripts and styles for the plugin details / install
	 * thickbox link. See the WpssoScript and WpssoStyle classes for more info.
	 */
	class WpssoSubmenuAddons extends WpssoAdmin {

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
				'addons' => _x( 'Free Plugin Add-ons', 'metabox title', 'wpsso' ),
			);
		}

		/*
		 * Add settings page filters and actions hooks.
		 *
		 * Called by WpssoAdmin->load_settings_page() after the 'wpsso-action' query is handled.
		 */
		protected function add_plugin_hooks() {

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows' => 1,	// Form buttons for this settings page.
			), PHP_INT_MAX );			// Run filter last to remove all form buttons.
		}

		/*
		 * Remove all action buttons from this settings page.
		 */
		public function filter_form_button_rows( $form_button_rows ) {

			return array();
		}

		public function show_metabox_addons( $obj, $mb ) {

			$this->addons_metabox_content( $network = false );
		}
	}
}
