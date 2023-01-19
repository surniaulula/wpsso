<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuEssential' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuEssential extends WpssoAdmin {

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

		/**
		 * Called by WpssoAdmin->load_setting_page() after the 'wpsso-action' query is handled.
		 *
		 * Add settings page filter and action hooks.
		 */
		protected function add_plugin_hooks() {

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows' => 1,	// Form buttons for this settings page.
			) );
		}

		/**
		 * Remove the "Change to View" button from this settings page.
		 */
		public function filter_form_button_rows( $form_button_rows ) {

			if ( isset( $form_button_rows[ 0 ] ) ) {

				$form_button_rows[ 0 ] = SucomUtil::preg_grep_keys( '/^change_show_options/', $form_button_rows[ 0 ], $invert = true );
			}

			return $form_button_rows;
		}

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$this->maybe_show_language_notice();

			$this->p->media->get_default_images( $size_name = 'wpsso-opengraph' );

			/**
			 * Essential Settings metabox.
			 */
			$metabox_id      = 'general';
			$metabox_title   = _x( 'Essential Settings', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'page_id'    => $this->menu_id,
				'metabox_id' => $metabox_id,
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_table' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );
		}

		protected function get_table_rows( $page_id, $metabox_id ) {

			$table_rows = array();

			switch ( $page_id . '-' . $metabox_id ) {

				case 'essential-general':

					$def_site_name = get_bloginfo( 'name' );
					$def_site_desc = get_bloginfo( 'description' );

					$table_rows[ 'site_name' ] = '' .
						$this->form->get_th_html_locale( _x( 'WebSite Name', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_name' ) .
						'<td>' . $this->form->get_input_locale( 'site_name', $css_class = 'long_name', $css_id = '',
							$len = 0, $def_site_name ) . '</td>';

					$table_rows[ 'site_desc' ] = '' .
						$this->form->get_th_html_locale( _x( 'WebSite Description', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_desc' ) .
						'<td>' . $this->form->get_input_locale( 'site_desc', $css_class = 'wide', $css_id = '',
							$len = 0, $def_site_desc ) . '</td>';

					$table_rows[ 'og_def_img_id' ] = '' .
						$this->form->get_th_html_locale( _x( 'Default Image ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_img_id' ) .
						'<td>' . $this->form->get_input_image_upload_locale( 'og_def_img' ) . '</td>';

					$table_rows[ 'og_def_img_url' ] = '' .
						$this->form->get_th_html_locale( _x( 'or Default Image URL', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_img_url' ) .
						'<td>' . $this->form->get_input_image_url_locale( 'og_def_img' ) . '</td>';

					if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

						$this->add_schema_publisher_type_table_rows( $table_rows, $this->form );	// Also used in the General Settings page.
					}

					break;
			}

			return $table_rows;
		}
	}
}
