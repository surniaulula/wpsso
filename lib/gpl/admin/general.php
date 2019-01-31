<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoGplAdminGeneral' ) ) {

	class WpssoGplAdminGeneral {

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

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$table_rows[ 'og_author_gravatar' ] = '' . 
			$form->get_th_html( _x( 'Gravatar is Author Default Image', 'option label', 'wpsso' ), null, 'og_author_gravatar' ) . 
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			return $table_rows;
		}

		public function filter_og_images_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$table_rows[ 'plugin_check_img_dims' ] = '' . 
			$form->get_th_html( _x( 'Enforce Image Dimensions Check', 'option label', 'wpsso' ), '', 'plugin_check_img_dims' ) . 
			$form->get_td_no_checkbox( 'plugin_check_img_dims', '<em>' . _x( 'recommended', 'option comment', 'wpsso' ) . '</em>' );

			$table_rows[ 'plugin_upscale_images' ] = '' . 
			$form->get_th_html( _x( 'Allow Upscale of WP Media Images', 'option label', 'wpsso' ), '', 'plugin_upscale_images' ) . 
			$form->get_td_no_checkbox( 'plugin_upscale_images' );

			$table_rows[ 'plugin_upscale_img_max' ] = $form->get_tr_hide( 'basic', 'plugin_upscale_img_max' ) . 
			$form->get_th_html( _x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ), '', 'plugin_upscale_img_max' ) . 
			'<td class="blank">' . $form->options[ 'plugin_upscale_img_max' ] . ' %</td>';

			return $table_rows;
		}

		public function filter_og_videos_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2"><p style="text-align:center;margin:0;">' .
				__( 'Video discovery and integration modules are provided with the Pro version.', 'wpsso' ) .
					'</p>' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$table_rows[ 'og_vid_max' ] = $form->get_tr_hide( 'basic', 'og_vid_max' ) . 
			$form->get_th_html( _x( 'Maximum Videos to Include', 'option label', 'wpsso' ), null, 'og_vid_max' ) . 
			'<td class="blank">' . $form->options[ 'og_vid_max' ] . '</td>';

			$table_rows[ 'og_vid_https' ] = $form->get_tr_hide( 'basic', 'og_vid_https' ) . 
			$form->get_th_html( _x( 'Use HTTPS for Video API Requests', 'option label', 'wpsso' ), null, 'og_vid_https' ) . 
			'<td class="blank"><input type="checkbox" disabled="disabled" /> <em>' . sprintf( _x( 'uses %s', 'option comment', 'wpsso' ),
				str_replace( WPSSO_PLUGINDIR, WPSSO_PLUGINSLUG . '/', WPSSO_PHP_CURL_CAINFO ) ) . '</em></td>';

			$table_rows[ 'og_vid_prev_img' ] = '' . 
			$form->get_th_html( _x( 'Include Video Preview Images', 'option label', 'wpsso' ), null, 'og_vid_prev_img' ) . 
			'<td class="blank"><input type="checkbox" disabled="disabled" />  <em>' . _x( 'video preview images are included first',
				'option comment', 'wpsso' ) . '</em></td>';

			$table_rows[ 'og_vid_html_type' ] = '' . 
			$form->get_th_html( _x( 'Include text/html Type Meta Tags', 'option label', 'wpsso' ), null, 'og_vid_html_type' ) . 
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

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
