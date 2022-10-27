<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdAdminGeneral' ) ) {

	class WpssoStdAdminGeneral {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'og_images_rows' => 2,
				'og_videos_rows' => 2,
			) );
		}

		/**
		 * SSO > General Settings > Images tab.
		 */
		public function filter_og_images_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_inherit_featured' ] = '' .
				$form->get_th_html( _x( 'Inherit Featured Image', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_inherit_featured' ) .
				$form->get_no_td_checkbox( 'plugin_inherit_featured' );

			$table_rows[ 'plugin_inherit_custom' ] = '' .
				$form->get_th_html( _x( 'Inherit Custom Images', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_inherit_custom' ) .
				$form->get_no_td_checkbox( 'plugin_inherit_custom' );

			$table_rows[ 'plugin_check_img_dims' ] = '' .
				$form->get_th_html( _x( 'Image Dimension Checks', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_check_img_dims' ) .
				$form->get_no_td_checkbox( 'plugin_check_img_dims', _x( '(recommended)', 'option comment', 'wpsso' ) );

			$table_rows[ 'plugin_upscale_images' ] = '' .
				$form->get_th_html( _x( 'Upscale Media Library Images', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_upscale_images' ) .
				$form->get_no_td_checkbox( 'plugin_upscale_images', _x( '(not recommended)', 'option comment', 'wpsso' ) );

			$table_rows[ 'plugin_gravatar_api' ] = '' .
				$form->get_th_html( _x( 'Gravatar is Default Author Image', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_gravatar_api' ) .
				$form->get_no_td_checkbox( 'plugin_gravatar_api' );

			return $table_rows;
		}

		/**
		 * SSO > General Settings > Videos tab.
		 */
		public function filter_og_videos_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature_video_api() . '</td>';

			$table_rows[ 'og_vid_max' ] = $form->get_tr_hide( $in_view = 'basic', 'og_vid_max' ) .
				$form->get_th_html( _x( 'Maximum Videos to Include', 'option label', 'wpsso' ), null, 'og_vid_max' ) .
				'<td class="blank">' . $form->get_no_select( 'og_vid_max', range( 0, $max_media_items ),
					$css_class = 'short', $css_id = '', $is_assoc = true ) . '</td>';

			$table_rows[ 'og_vid_prev_img' ] = '' .
				$form->get_th_html( _x( 'Include Video Preview Images', 'option label', 'wpsso' ), null, 'og_vid_prev_img' ) .
				$form->get_no_td_checkbox( 'og_vid_prev_img', $this->p->msgs->preview_images_are_first() );

			$table_rows[ 'og_vid_autoplay' ] = '' .
				$form->get_th_html( _x( 'Force Autoplay when Possible', 'option label', 'wpsso' ), null, 'og_vid_autoplay' ) .
				$form->get_no_td_checkbox( 'og_vid_autoplay' );

			$check_embed_html = '';

			foreach ( $this->p->cf[ 'form' ][ 'embed_media_apis' ] as $opt_key => $opt_label ) {

				$check_embed_html .= '<p>' . $form->get_no_checkbox_comment( $opt_key ) . ' ' . _x( $opt_label, 'option value', 'wpsso' ) . '</p>';
			}

			$table_rows[ 'plugin_embed_media_apis' ] = '' .
				$form->get_th_html( _x( 'Check for Embedded Media', 'option label', 'wpsso' ), '', 'plugin_embed_media_apis' ) .
				'<td class="blank">' . $check_embed_html . '</td>';

			return $table_rows;
		}
	}
}
