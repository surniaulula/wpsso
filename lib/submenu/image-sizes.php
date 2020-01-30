<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuImageSizes' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuImageSizes extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows' => 2,
			), $prio = -10000 );
		}

		public function filter_form_button_rows( $form_button_rows, $menu_id ) {

			$row_num = null;

			switch ( $menu_id ) {

				case 'image-sizes':

					$row_num = 0;

					break;

				case 'sso-tools':
				case 'tools':

					$row_num = 2;

					break;
			}

			if ( null !== $row_num ) {
				$form_button_rows[ $row_num ][ 'reload_default_image_sizes' ] = _x( 'Reload Default Image Sizes',
					'submit button', 'wpsso' );
			}

			return $form_button_rows;
		}

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$metabox_id      = 'image_dimensions';
			$metabox_title   = _x( 'Social and Search Image Sizes', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_image_dimensions' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );
		}

		public function show_metabox_image_dimensions() {

			$metabox_id = $this->menu_id;

			echo '<table class="sucom-settings ' . $this->p->lca . '">';

			$table_rows = array_merge( $this->get_table_rows( $metabox_id, 'general' ),
				apply_filters( SucomUtil::sanitize_hookname( $this->p->lca . '_' . $metabox_id . '_general_rows' ),
					array(), $this->form ) );

			ksort( $table_rows );

			foreach ( $table_rows as $num => $row ) {
				echo '<tr>' . $row . '</tr>' . "\n";
			}

			echo '</table>';
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			switch ( $metabox_id . '-' . $tab_key ) {

				case 'image-sizes-general':

					$json_req_msg = $this->p->msgs->maybe_ext_required( 'wpssojson' );

					$table_rows[ 'og_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Open Graph (Facebook and Others)',
						'option label', 'wpsso' ), '', 'og_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'og_img' ) . '</td>';

					$table_rows[ 'schema_00_img_size' ] = '' .		// Use a key name that sorts first.
					$this->form->get_th_html( _x( 'Schema (Google and Pinterest)',
						'option label', 'wpsso' ), '', 'schema_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'schema_img' ) . '</td>';

					$table_rows[ 'schema_article_00_img_size' ] = '' .	// Use a key name that sorts first.
					$this->form->get_th_html( _x( 'Schema Article (Google and Pinterest)',
						'option label', 'wpsso' ), '', 'schema_article_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'schema_article_img' ) . '</td>';

					if ( ! empty( $this->p->avail[ 'amp' ][ 'any' ] ) ) {

						$table_rows[ 'schema_article_01_01_img_size' ] = '' .
						$this->form->get_th_html( _x( 'Schema Article AMP 1:1 (Google)',
							'option label', 'wpsso' ), '', 'schema_article_1_1_img_size' ) . 
						'<td>' . $this->form->get_input_image_dimensions( 'schema_article_1_1_img' ) . $json_req_msg . '</td>';

						$table_rows[ 'schema_article_04_03_img_size' ] = '' .
						$this->form->get_th_html( _x( 'Schema Article AMP 4:3 (Google)',
							'option label', 'wpsso' ), '', 'schema_article_4_3_img_size' ) . 
						'<td>' . $this->form->get_input_image_dimensions( 'schema_article_4_3_img' ) . $json_req_msg . '</td>';

						$table_rows[ 'schema_article_16_09_img_size' ] = '' .
						$this->form->get_th_html( _x( 'Schema Article AMP 16:9 (Google)',
							'option label', 'wpsso' ), '', 'schema_article_16_9_img_size' ) . 
						'<td>' . $this->form->get_input_image_dimensions( 'schema_article_16_9_img' ) . $json_req_msg . '</td>';
					}

					$table_rows[ 'schema_thumb_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Schema Thumbnail Image',
						'option label', 'wpsso' ), '', 'thumb_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'thumb_img' ) . '</td>';

					$table_rows[ 'tc_00_sum_img_size' ] = '' .	// Use a key name that sorts first.
					$this->form->get_th_html( _x( 'Twitter Summary Card',
						'option label', 'wpsso' ), '', 'tc_sum_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'tc_sum_img' ) . '</td>';

					$table_rows[ 'tc_lrg_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Twitter Large Image Summary Card',
						'option label', 'wpsso' ), '', 'tc_lrg_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'tc_lrg_img' ) . '</td>';

					break;
			}

			return $table_rows;
		}
	}
}
