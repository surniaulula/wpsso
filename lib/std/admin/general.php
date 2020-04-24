<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdAdminGeneral' ) ) {

	class WpssoStdAdminGeneral {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'og_author_rows' => 2,
				'og_images_rows' => 2,
				'og_videos_rows' => 2,
			) );
		}

		public function filter_og_author_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_gravatar_api' ] = '' . 
			$form->get_th_html( _x( 'Gravatar is Author Default Image', 'option label', 'wpsso' ), $css_class = '', $css_id = 'plugin_gravatar_api' ) . 
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			return $table_rows;
		}

		public function filter_og_images_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_check_img_dims' ] = '' . 
			$form->get_th_html( _x( 'Enforce Image Dimension Checks', 'option label', 'wpsso' ), $css_class = '', $css_id = 'plugin_check_img_dims' ) . 
			$form->get_no_td_checkbox( 'plugin_check_img_dims', '<em>' . _x( 'recommended', 'option comment', 'wpsso' ) . '</em>' );

			$table_rows[ 'plugin_upscale_images' ] = '' . 
			$form->get_th_html( _x( 'Upscale Media Library Images', 'option label', 'wpsso' ), '', 'plugin_upscale_images' ) . 
			$form->get_no_td_checkbox( 'plugin_upscale_images' );

			return $table_rows;
		}

		public function filter_og_videos_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature_video_api( 'wpsso' ) . '</td>';

			$table_rows[ 'og_vid_max' ] = $form->get_tr_hide( 'basic', 'og_vid_max' ) . 
			$form->get_th_html( _x( 'Maximum Videos to Include', 'option label', 'wpsso' ), null, 'og_vid_max' ) . 
			'<td class="blank">' . $form->options[ 'og_vid_max' ] . '</td>';

			$table_rows[ 'og_vid_prev_img' ] = '' . 
			$form->get_th_html( _x( 'Include Video Preview Images', 'option label', 'wpsso' ), null, 'og_vid_prev_img' ) . 
			'<td class="blank"><input type="checkbox" disabled="disabled" />' . $this->p->msgs->preview_images_first() . '</td>';

			$table_rows[ 'og_vid_autoplay' ] = '' . 
			$form->get_th_html( _x( 'Force Autoplay when Possible', 'option label', 'wpsso' ), null, 'og_vid_autoplay' ) . 
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			$check_embed_html = '';

			foreach ( $this->p->cf[ 'form' ][ 'embed_media_apis' ] as $opt_key => $opt_label ) {
				$check_embed_html .= '<p>' . $form->get_no_checkbox_comment( $opt_key ) . ' ' . _x( $opt_label, 'option value', 'wpsso' ) . '</p>';
			}

			$table_rows[ 'plugin_embed_media_apis' ] = $form->get_tr_hide( 'basic', $this->p->cf[ 'form' ][ 'embed_media_apis' ] ) . 
			$form->get_th_html( _x( 'Check for Embedded Media', 'option label', 'wpsso' ), '', 'plugin_embed_media_apis' ) . 
			'<td class="blank">' . $check_embed_html . '</td>';

			return $table_rows;
		}
	}
}
