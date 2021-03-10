<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdAdminImageSizes' ) ) {

	class WpssoStdAdminImageSizes {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'image_sizes_image_dimensions_rows' => 2,
			) );
		}

		public function filter_image_sizes_image_dimensions_rows( $table_rows, $form ) {

			if ( $info_msg = $this->p->msgs->get( 'info-image_dimensions' ) ) {

				$table_rows[ 'info-image_dimensions' ] = '<td colspan="2">' . $info_msg . '</td>';
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$json_req_msg   = $this->p->msgs->maybe_ext_required( 'wpssojson' );
			$p_img_disabled = empty( $this->p->options[ 'p_add_img_html' ] ) ? true : false;
			$p_img_msg      = $p_img_disabled ? $this->p->msgs->p_img_disabled( $extra_css_class = 'inline' ) : '';

			$table_rows[ 'og_img_size' ] = '' .
				$form->get_th_html( _x( 'Open Graph (Facebook and oEmbed)', 'option label', 'wpsso' ), '', 'og_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'og_img' ) . '</td>';

			$table_rows[ 'p_img_size' ] = ( $p_img_disabled ? $form->get_tr_hide( 'basic' ) : '' ) .
				$form->get_th_html( _x( 'Pinterest Pin It', 'option label', 'wpsso' ), '', 'p_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'p_img', $p_img_disabled ) . $p_img_msg . '</td>';

			$table_rows[ 'schema_01_01_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 1:1 (Google)', 'option label', 'wpsso' ), '', 'schema_1_1_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_1_1_img' ) . $json_req_msg . '</td>';

			$table_rows[ 'schema_04_03_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 4:3 (Google)', 'option label', 'wpsso' ), '', 'schema_4_3_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_4_3_img' ) . $json_req_msg . '</td>';

			$table_rows[ 'schema_16_09_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 16:9 (Google)', 'option label', 'wpsso' ), '', 'schema_16_9_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_16_9_img' ) . $json_req_msg . '</td>';

			$table_rows[ 'schema_thumb_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema Thumbnail Image', 'option label', 'wpsso' ), '', 'thumb_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'thumb_img' ) . '</td>';

			$table_rows[ 'tc_00_sum_img_size' ] = '' .
				$form->get_th_html( _x( 'Twitter Summary Card', 'option label', 'wpsso' ), '', 'tc_sum_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'tc_sum_img' ) . '</td>';

			$table_rows[ 'tc_01_lrg_img_size' ] = '' .
				$form->get_th_html( _x( 'Twitter Large Image Summary Card', 'option label', 'wpsso' ), '', 'tc_lrg_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'tc_lrg_img' ) . '</td>';

			return $table_rows;
		}
	}
}
