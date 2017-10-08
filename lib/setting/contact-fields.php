<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSettingContactfields' ) && class_exists( 'WpssoSubmenuAdvanced' ) ) {

	class WpssoSettingContactfields extends WpssoSubmenuAdvanced {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;	// lowercase acronyn for plugin or extension
		}

		// called by the extended WpssoAdmin class
		protected function add_meta_boxes() {
			add_meta_box( $this->pagehook.'_contact_fields',
				_x( 'Contact Field Names and Labels', 'metabox title', 'wpsso' ),
					array( &$this, 'show_metabox_contact_fields' ), $this->pagehook, 'normal' );
		}

	}
}

?>
