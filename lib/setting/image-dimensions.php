<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSettingImageDimensions' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSettingImageDimensions extends WpssoAdmin {

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
			), -10000 );
		}

		public function filter_form_button_rows( $form_button_rows, $menu_id ) {

			$row_num = null;

			switch ( $menu_id ) {
				case 'image-dimensions':
					$row_num = 0;
					break;
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
			$metabox_title   = _x( 'Social and Search Image Sizes / Dimensions', 'metabox title', 'wpsso' );
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

			sort( $table_rows );

			foreach ( $table_rows as $num => $row ) {
				echo '<tr>'.$row.'</tr>' . "\n";
			}

			echo '</table>';
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			switch ( $metabox_id.'-'.$tab_key ) {

				case 'image-dimensions-general':

					$table_rows['og_img_dimensions'] = $this->form->get_th_html( _x( 'Facebook / Open Graph',
						'option label', 'wpsso' ), null, 'og_img_dimensions' ).
					'<td>'.$this->form->get_input_image_dimensions( 'og_img' ).'</td>';	// $use_opts = false

					$table_rows['schema_img_dimensions'] = $this->form->get_th_html( _x( 'Google / Schema / Pinterest',
						'option label', 'wpsso' ), null, 'schema_img_dimensions' ).
					'<td>'.$this->form->get_input_image_dimensions( 'schema_img' ).'</td>';	// $use_opts = false

					$table_rows['tc_sum_img_dimensions'] = $this->form->get_th_html( _x( 'Twitter <em>Summary</em> Card',
						'option label', 'wpsso' ), null, 'tc_sum_img_dimensions' ).
					'<td>'.$this->form->get_input_image_dimensions( 'tc_sum_img' ).'</td>';	// $use_opts = false

					$table_rows['tc_lrg_img_dimensions'] = $this->form->get_th_html( _x( 'Twitter <em>Large Image Summary</em> Card',
						'option label', 'wpsso' ), null, 'tc_lrg_img_dimensions' ).
					'<td>'.$this->form->get_input_image_dimensions( 'tc_lrg_img' ).'</td>';	// $use_opts = false

					break;
			}

			return $table_rows;
		}
	}
}
