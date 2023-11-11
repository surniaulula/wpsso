<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuSetup' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuSetup extends WpssoAdmin {

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
				'setup_guide' => _x( 'Setup Guide', 'metabox title', 'wpsso' ),
			);
		}

		/*
		 * Remove all action buttons.
		 */
		protected function add_form_buttons( &$form_button_rows ) {

			$form_button_rows = array();
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_setup_guide( $obj, $mb ) {

			echo '<table class="sucom-settings wpsso html-content-metabox">';

			echo '<tr><td>';

			echo $this->get_ext_file_content( $ext = 'wpsso', $rel_file = 'html/setup.html' );

			echo '</td></tr></table>';
		}
	}
}
