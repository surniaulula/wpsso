<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoEditPrev' ) ) {

	class WpssoEditPrev {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * See WpssoAbstractWpMeta->get_document_meta_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'metabox_sso_prev_social_rows' => 4,
				'metabox_sso_prev_oembed_rows' => 4,
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_metabox_sso_prev_social_rows( $table_rows, $form, $head_info, $mod ) {

			$og_prev_width    = 600;
			$og_prev_height   = 315;
			$og_prev_img_html = '';

			$image_url     = SucomUtil::get_first_mt_media_url( $head_info );
			$canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = false );

			if ( $mod[ 'is_post' ] ) {

				$shortlink_url = SucomUtilWP::wp_get_shortlink( $mod[ 'id' ], $context = 'post' );

			} else {

				$shortlink_url = $this->p->util->shorten_url( $canonical_url, $mod );
			}

			$have_sizes = isset( $head_info[ 'og:image:width' ] ) && $head_info[ 'og:image:width' ] > 0 &&
				isset( $head_info[ 'og:image:height' ] ) && $head_info[ 'og:image:height' ] > 0 ? true : false;

			$is_sufficient = true === $have_sizes && $head_info[ 'og:image:width' ] >= $og_prev_width &&
				$head_info[ 'og:image:height' ] >= $og_prev_height ? true : false;

			if ( ! empty( $image_url ) ) {

				if ( $have_sizes ) {

					$og_prev_img_html .= '<div class="fb_preview_img" style=" background-size:' ;

					if ( $is_sufficient ) {

						$og_prev_img_html .= 'cover';

					} else {

						$og_prev_img_html .= $head_info[ 'og:image:width' ] . 'px ' . $head_info[ 'og:image:height' ] . 'px';
					}

					$og_prev_img_html .= '; background-image:url(' . $image_url . ');" />';

					if ( ! $is_sufficient ) {

						$og_prev_img_html .= '<p>' . sprintf( _x( 'Image Size Smaller<br/>than Suggested Minimum<br/>of %s',
							'preview image error', 'wpsso' ), $og_prev_width . 'x' . $og_prev_height . 'px' ) . '</p>';
					}

					$og_prev_img_html .= '</div>';

				} else {

					$og_prev_img_html .= '<div class="fb_preview_img" style="background-image:url(' . $image_url . ');" />';
					$og_prev_img_html .= '<p>' . _x( 'Image Size Unknown<br/>or Not Available', 'preview image error', 'wpsso' ) . '</p>';
					$og_prev_img_html .= '</div>';
				}

			} else {

				$og_prev_img_html .= '<div class="fb_preview_img">';
				$og_prev_img_html .= '<p>' . _x( 'No Open Graph Image Found', 'preview image error', 'wpsso' ) . '</p>';
				$og_prev_img_html .= '</div>';
			}

			$table_rows[ 'prev_canonical_url' ] = '' .
				$form->get_th_html( _x( 'Canonical URL', 'option label', 'wpsso' ), $css_class = 'medium nowrap' ) .
				'<td>' . $form->get_no_input_clipboard( $canonical_url ) . '</td>';

			$table_rows[ 'prev_shortlink_url' ] = '' .
				$form->get_th_html( _x( 'Shortlink URL', 'option label', 'wpsso' ), $css_class = 'medium nowrap' ) .
				'<td>' . $form->get_no_input_clipboard( $shortlink_url ) . '</td>';

			$table_rows[ 'subsection_prev_og' ] = '<td colspan="2" class="subsection"><h4>' .
				_x( 'Facebook / Open Graph Example', 'metabox title', 'wpsso' ) . '</h4></td>';

			$table_rows[ 'prev_og' ] = '' .
				'<td colspan="2" class="fb_preview_container">
					<div class="fb_preview_box_border">
						<div class="fb_preview_box">
							' . $og_prev_img_html . '
							<div class="fb_preview_text">
								<div class="fb_preview_title">' .
								( empty( $head_info[ 'og:title' ] ) ? '' : $head_info[ 'og:title' ] ) .
								'</div><!-- .fb_preview_title -->
								<div class="fb_preview_desc">' .
								( empty( $head_info[ 'og:description' ] ) ? '' : $head_info[ 'og:description' ] ) .
								'</div><!-- .fb_preview_desc -->
								<div class="fb_preview_by">' .
									$_SERVER[ 'SERVER_NAME' ] .
									( empty( $this->p->options[ 'add_meta_property_article:author' ] ) ||
										empty( $head_info[ 'article:author:name' ] ) ?
											'' : ' | By ' . $head_info[ 'article:author:name' ] ) .
								'</div><!-- .fb_preview_by -->
							</div><!-- .fb_preview_text -->
						</div><!-- .fb_preview_box -->
					</div><!-- .fb_preview_box_border -->
				</td><!-- .fb_preview_container -->';

			$table_rows[ 'prev_og_footer' ] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-social-preview' ) . '</td>';

			return $table_rows;
		}

		public function filter_metabox_sso_prev_oembed_rows( $table_rows, $form, $head_info, $mod ) {

			$json_url     = $this->p->util->get_oembed_url( $mod, 'json' );
			$xml_url      = $this->p->util->get_oembed_url( $mod, 'xml' );
			$oembed_data  = $this->p->util->get_oembed_data( $mod, $oembed_width = 600 );

			$table_rows[ 'oembed_json_url' ] = '' .
				$form->get_th_html( _x( 'oEmbed JSON URL', 'option label', 'wpsso' ), $css_class = 'medium' ) .
				'<td>' . $form->get_no_input_clipboard( $json_url ) . '</td>';

			$table_rows[ 'oembed_xml_url' ] = '' .
				$form->get_th_html( _x( 'oEmbed XML URL', 'option label', 'wpsso' ), $css_class = 'medium' ) .
				'<td>' . $form->get_no_input_clipboard( $xml_url ) . '</td>';

			$table_rows[ 'subsection_oembed_html' ] = '<td colspan="2" class="subsection"><h4>' .
				_x( 'oEmbed HTML', 'metabox title', 'wpsso' ) . '</h4></td>';

			if ( ! empty( $oembed_data[ 'html' ] ) ) {

				$table_rows[ 'oembed_html' ] = '<td colspan="2" class="oembed_container">' . $oembed_data[ 'html' ] . '</td><!-- .oembed_container -->';

				$table_rows[ 'oembed_footer' ] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-oembed-footer' ) . '</td>';

			} else {

				$table_rows[ 'no_oembed_html' ] = '<td colspan="2"><p class="status-msg">' . __( 'No oEmbed HTML found.', 'wpsso' ) . '</p></td>';
			}

			return $table_rows;
		}
	}
}
