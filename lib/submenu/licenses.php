<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuLicenses' ) && class_exists( 'WpssoAdmin' ) ) {

	/*
	 * Please note that this settings page also requires enqueuing special scripts and styles for the plugin details / install
	 * thickbox link. See the WpssoScript and WpssoStyle classes for more info.
	 */
	class WpssoSubmenuLicenses extends WpssoAdmin {

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
				'licenses' => _x( 'Plugin and Add-on Licenses', 'metabox title', 'wpsso' ),
			);
		}

		protected function add_plugin_hooks() {

			$this->p->util->add_plugin_filters( $this, array( 'form_button_rows' => 1 ) );
		}

		public function filter_form_button_rows( $form_button_rows ) {

			/*
			 * Remove the "Change to View" button from this settings page.
			 */
			if ( isset( $form_button_rows[ 0 ] ) ) {

				$form_button_rows[ 0 ] = SucomUtil::preg_grep_keys( '/^change_show_options/', $form_button_rows[ 0 ], $invert = true );
			}

			return $form_button_rows;
		}

		public function show_metabox_licenses( $obj, $mb ) {

			$this->licenses_metabox_content( $network = false );
		}
	}
}
