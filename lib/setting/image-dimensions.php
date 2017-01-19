<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSettingImagedimensions' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSettingImagedimensions extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;
			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_image_dimensions',
				_x( 'Image Dimensions', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_image_dimensions' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_image_dimensions() {
			$metabox = $this->menu_id;
			echo '<table class="sucom-setting '.$this->p->cf['lca'].'">';
			$table_rows = array_merge( $this->get_table_rows( $metabox, 'general' ), 
				apply_filters( SucomUtil::sanitize_hookname( $this->p->cf['lca'].'_'.$metabox.'_general_rows' ),
					array(), $this->form ) );
			sort( $table_rows );
			foreach ( $table_rows as $num => $row ) 
				echo '<tr>'.$row.'</tr>'."\n";
			echo '</table>';
		}

		protected function get_table_rows( $metabox, $key ) {
			$table_rows = array();

			switch ( $metabox.'-'.$key ) {

				case 'image-dimensions-general':

					$table_rows['og_img_dimensions'] = $this->form->get_th_html( _x( 'Facebook / Open Graph Images',
						'option label', 'wpsso' ), null, 'og_img_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'og_img' ).'</td>';	// $use_opts = false

					if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) ) {
						$table_rows['rp_img_dimensions'] = $this->form->get_th_html( _x( 'Pinterest Rich Pin Images',
							'option label', 'wpsso' ), null, 'rp_img_dimensions' ).
						'<td>'.$this->form->get_image_dimensions_input( 'rp_img' ).'</td>';	// $use_opts = false
					}

					$table_rows['schema_img_dimensions'] = $this->form->get_th_html( _x( 'Google / Schema Images',
						'option label', 'wpsso' ), null, 'schema_img_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'schema_img' ).'</td>';	// $use_opts = false

					$table_rows['tc_sum_dimensions'] = $this->form->get_th_html( _x( 'Twitter <em>Summary</em> Card',
						'option label', 'wpsso' ), null, 'tc_sum_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'tc_sum' ).'</td>';	// $use_opts = false

					$table_rows['tc_lrgimg_dimensions'] = $this->form->get_th_html( _x( 'Twitter <em>Large Image Summary</em> Card',
						'option label', 'wpsso' ), null, 'tc_lrgimg_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'tc_lrgimg' ).'</td>';	// $use_opts = false

					break;
			}
			return $table_rows;
		}
	}
}

?>
