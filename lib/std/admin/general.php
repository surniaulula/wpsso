<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
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
				'og_videos_rows' => 2,
			) );
		}

		/*
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
