<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoEdit' ) ) {

	class WpssoEdit {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$min_int = SucomUtil::get_min_int();

			$this->p->util->add_plugin_filters( $this, array( 
				'metabox_sso_edit_rows'     => 4,
				'metabox_sso_media_rows'    => 4,
				'metabox_sso_preview_rows'  => 4,
				'metabox_sso_oembed_rows'   => 4,
				'metabox_sso_head_rows'     => 4,
				'metabox_sso_validate_rows' => 4,
			), $min_int );
		}

		public function filter_metabox_sso_edit_rows( $table_rows, $form, $head_info, $mod ) {

			$dots           = '...';
			$read_cache     = true;
			$no_hashtags    = false;
			$maybe_hashtags = true;
			$do_encode      = true;

			$p_img_disabled         = empty( $this->p->options[ 'p_add_img_html' ] ) ? true : false;
			$seo_desc_disabled      = empty( $this->p->options[ 'add_meta_name_description' ] ) ? true : false;
			$canonical_url_disabled = empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? true : false;

			$p_img_msg         = $p_img_disabled ? $this->p->msgs->p_img_disabled() : '';
			$seo_desc_msg      = $seo_desc_disabled ? $this->p->msgs->seo_option_disabled( 'meta name description' ) : '';
			$canonical_url_msg = $canonical_url_disabled ? $this->p->msgs->seo_option_disabled( 'link rel canonical' ) : '';

			/**
			 * Select option arrays.
			 */
			$select_exp_secs = $this->p->util->get_cache_exp_secs( $this->p->lca . '_f_' );	// Default is month in seconds.
			$schema_exp_secs = $this->p->util->get_cache_exp_secs( $this->p->lca . '_t_' );	// Default is month in seconds.

			$og_types         = $this->p->og->get_og_types_select();
			$schema_types     = $this->p->schema->get_schema_types_select( $context = 'meta' );
			$article_sections = $this->p->util->get_article_sections();

			/**
			 * Maximum option lengths.
			 */
			$og_title_max_len    = $this->p->options[ 'og_title_max_len' ];
			$og_title_warn_len   = $this->p->options[ 'og_title_warn_len' ];
			$og_desc_max_len     = $this->p->options[ 'og_desc_max_len' ];
			$og_desc_warn_len    = $this->p->options[ 'og_desc_warn_len' ];
			$p_img_desc_max_len  = $this->p->options[ 'p_img_desc_max_len' ];
			$p_img_desc_warn_len = $this->p->options[ 'p_img_desc_warn_len' ];
			$tc_desc_max_len     = $this->p->options[ 'tc_desc_max_len' ];
			$seo_desc_max_len    = $this->p->options[ 'seo_desc_max_len' ];		// Max. Description Meta Tag Length.

			/**
			 * Default option values.
			 */
			$def_og_title      = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, 'none' );
			$def_og_desc       = $this->p->page->get_description( $og_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags, $do_encode, 'none' );
			$def_p_img_desc    = $p_img_disabled ? '' : $this->p->page->get_description( $p_img_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags );
			$def_tc_desc       = $this->p->page->get_description( $tc_desc_max_len, $dots, $mod, $read_cache );
			$def_seo_desc      = $seo_desc_disabled ? '' : $this->p->page->get_description( $seo_desc_max_len, $dots, $mod, $read_cache, $no_hashtags );
			$def_sharing_url   = $this->p->util->get_sharing_url( $mod, $add_page = false );
			$def_canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = false );

			/**
			 * Javascript classes to hide/show rows by selected schema type.
			 */
			$schema_type_row_class = WpssoSchema::get_schema_type_row_class( 'schema_type' );

			/**
			 * Metabox form rows.
			 */
			$form_rows = array(
				'info_schema_faq' => array(
					'tr_class'  => $schema_type_row_class[ 'faq' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-schema-faq' ) . '</td>',
				),
				'info_schema_qa' => array(
					'tr_class'  => $schema_type_row_class[ 'qa' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-schema-qa' ) . '</td>',
				),
				'info_schema_question' => array(
					'tr_class'  => $schema_type_row_class[ 'question' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-schema-question' ) . '</td>',
				),
				'attach_img_crop' => $mod[ 'post_type' ] === 'attachment' && wp_attachment_is_image( $mod[ 'id' ] ) ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Preferred Cropping', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_crop_area',
					'content'  => $form->get_input_image_crop_area( 'attach_img', $add_none = true ),
				) : array(),
				'og_schema_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_schema_type',
					'content'  => $form->get_select( 'schema_type', $schema_types, $css_class = 'schema_type', $css_id = 'og_schema_type',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json', 'on_change_unhide_rows' ),
							$event_args = array(
								'json_var'  => 'schema_types',
								'exp_secs'  => $schema_exp_secs,	// Create and read from a javascript URL.
								'is_transl' => true,			// No label translation required.
								'is_sorted' => true,			// No label sorting required.
							)
						),
				),
				'og_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Open Graph Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_type',
					'content'  => $form->get_select( 'og_type', $og_types, $css_class = 'og_type', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = true, $event_names = array( 'on_change_unhide_rows' ) ),
				),
				'og_title' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Default Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_title',
					'content'  => $form->get_input( 'og_title', $css_class = 'wide', $css_id = '',
						array( 'max' => $og_title_max_len, 'warn' => $og_title_warn_len ), $def_og_title ),
				),
				'og_desc' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Default Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_desc',
					'content'  => $form->get_textarea( 'og_desc', $css_class = '', $css_id = '', 
						array( 'max' => $og_desc_max_len, 'warn' => $og_desc_warn_len ), $def_og_desc ),
				),
				'p_img_desc' => array(
					'tr_class' => $p_img_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Pinterest Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-p_img_desc',
					'content'  => $form->get_textarea( 'p_img_desc', $css_class = '', $css_id = '',
						array( 'max' => $p_img_desc_max_len, 'warn' => $p_img_desc_warn_len ),
							$def_p_img_desc, $p_img_disabled ) . ' ' . $p_img_msg,
				),
				'tc_desc' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-tc_desc',
					'content'  => $form->get_textarea( 'tc_desc', $css_class = '', $css_id = '',
						$tc_desc_max_len, $def_tc_desc ),
				),
				'seo_desc' => array(
					'tr_class' => $seo_desc_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Search Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-seo_desc',
					'content'  => $form->get_textarea( 'seo_desc', $css_class = '', $css_id = '',
						$seo_desc_max_len, $def_seo_desc, $seo_desc_disabled ) . ' ' . $seo_desc_msg,
				),
				'sharing_url' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'sharing_url' ),
					'th_class' => 'medium',
					'label'    => _x( 'Sharing URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-sharing_url',
					'content'  => $form->get_input( 'sharing_url', $css_class = 'wide', $css_id = '',
						$max_len = 0, $def_sharing_url ),
				),
				'canonical_url' => array(
					'tr_class' => $canonical_url_disabled ? 'hide_in_basic' : $form->get_css_class_hide( 'basic', 'canonical_url' ),
					'th_class' => 'medium',
					'label'    => _x( 'Canonical URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-canonical_url',
					'content'  => $form->get_input( 'canonical_url', $css_class = 'wide', $css_id = '',
						$max_len = 0, $def_canonical_url, $canonical_url_disabled ) . ' ' . $canonical_url_msg,
				),

				/**
				 * Open Graph Article type.
				 */
				'subsection_og_article' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Article Information', 'metabox title', 'wpsso' )
				),
				'og_article_section' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'th_class' => 'medium',
					'label'    => _x( 'Article Section', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-article_section',
					'content'  => $form->get_select( 'article_section', $article_sections, $css_class = 'article_section', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
							$event_args = array(
								'json_var'  => 'article_sections',
								'exp_secs'  => $select_exp_secs,	// Create and read from a javascript URL.
								'is_transl' => true,			// No label translation required.
								'is_sorted' => true,			// No label sorting required.
							)
						),
				),

				/**
				 * Open Graph Book type.
				 */
				'subsection_og_book' => array(
					'tr_class' => 'hide_og_type hide_og_type_book',
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Book Information', 'metabox title', 'wpsso' )
				),
				'og_book_isbn' => array(		// Open Graph meta tag book:isbn.
					'tr_class' => 'hide_og_type hide_og_type_book',
					'th_class' => 'medium',
					'label'    => _x( 'Book ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-book_isbn',
					'content'  => $form->get_input( 'book_isbn', $css_class = '', $css_id = '',
						array( 'min' => 10, 'max' => 13 ), $placeholder = true ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_media_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			/**
			 * Get the default Open Graph image pid and URL.
			 */
			$size_name       = $this->p->lca . '-opengraph';
			$media_request   = array( 'pid', 'img_url' );
			$media_info      = $this->p->og->get_media_info( $size_name, $media_request, $mod, $md_pre = 'none' );

			/**
			 * Metabox form rows.
			 */
			$form_rows = array(
				'info_priority_media' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-priority-media' ) . '</td>',
				),
				'subsection_opengraph' => array(
					'td_class' => 'subsection top',
					'header'   => 'h4',
					'label'    => _x( 'Facebook / Open Graph and Default Media', 'metabox title', 'wpsso' ),
				),
				'subsection_priority_image' => array(
					'td_class' => 'subsection top',
					'header'   => 'h5',
					'label'    => _x( 'Priority Image Information', 'metabox title', 'wpsso' )
				),
				'og_img_max' => $mod[ 'is_post' ] ? array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_img_max' ),
					'th_class' => 'medium',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'og_img_max',		// Use tooltip message from settings.
					'content'  => $form->get_select( 'og_img_max', range( 0, $max_media_items ), $css_class = 'medium' ),
				) : '',	// Placeholder if not a post module.
				'og_img_id' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_id',
					'content'  => $form->get_input_image_upload( 'og_img', $media_info[ 'pid' ] ),
				),
				'og_img_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_url',
					'content'  => $form->get_input_image_url( 'og_img', $media_info[ 'img_url' ] ),
				),
			);

			/**
			 * Additional sections and sub-sections added by the 'wpsso_metabox_sso_media_rows' filter:
			 *
			 * 	Facebook / Open Graph and Default Media
			 *
			 * 		Priority Video Information
			 *
			 * 	Pinterest Pin It
			 *
			 * 	Twitter Card
			 *
			 * 	Schema JSON-LD Markup / Google Rich Results
			 */

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_preview_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$og_prev_width    = 600;
			$og_prev_height   = 315;
			$og_prev_img_html = '';
			$image_url        = SucomUtil::get_first_mt_media_url( $head_info );
			$sharing_url      = $this->p->util->get_sharing_url( $mod, $add_page = false );
			$canonical_url    = $this->p->util->get_canonical_url( $mod, $add_page = false );

			if ( $mod[ 'is_post' ] ) {

				$shortlink_url = SucomUtilWP::wp_get_shortlink( $mod[ 'id' ], $context = 'post' );

			} else {

				$shortlink_url = apply_filters( $this->p->lca . '_get_short_url', $sharing_url,
					$this->p->options[ 'plugin_shortener' ], $mod );
			}

			$have_sizes = isset( $head_info[ 'og:image:width' ] ) && $head_info[ 'og:image:width' ] > 0 && 
				isset( $head_info[ 'og:image:height' ] ) && $head_info[ 'og:image:height' ] > 0 ? true : false;

			$is_sufficient = true === $have_sizes && $head_info[ 'og:image:width' ] >= $og_prev_width && 
				$head_info[ 'og:image:height' ] >= $og_prev_height ? true : false;

			if ( ! empty( $image_url ) ) {

				if ( $have_sizes ) {

					$og_prev_img_html .= '<div class="preview_img" style=" background-size:' ;

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

					$og_prev_img_html .= '<div class="preview_img" style="background-image:url(' . $image_url . ');" />';
					$og_prev_img_html .= '<p>' . _x( 'Image Size Unknown<br/>or Not Available', 'preview image error', 'wpsso' ) . '</p>';
					$og_prev_img_html .= '</div>';
				}

			} else {

				$og_prev_img_html .= '<div class="preview_img">';
				$og_prev_img_html .= '<p>' . _x( 'No Open Graph Image Found', 'preview image error', 'wpsso' ) . '</p>';
				$og_prev_img_html .= '</div>';
			}

			$table_rows[] = '' . 
			$form->get_th_html( _x( 'Sharing URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $sharing_url ) . '</td>';

			$table_rows[] = ( $sharing_url === $canonical_url ? '<tr class="hide_in_basic">' : '' ) . 
			$form->get_th_html( _x( 'Canonical URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $canonical_url ) . '</td>';

			$table_rows[] = ( empty( $this->p->options[ 'plugin_shortener' ] ) || 
				$this->p->options[ 'plugin_shortener' ] === 'none' ||
					$sharing_url === $shortlink_url ? '<tr class="hide_in_basic">' : '' ) . 
			$form->get_th_html( _x( 'Shortlink URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $shortlink_url ) . '</td>';

			$table_rows[ 'subsection_og_example' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'Facebook / Open Graph Example', 'option label', 'wpsso' ) . '</h4></td>';

			$table_rows[] = '' .
			'<td colspan="2" class="preview_container">
				<div class="preview_box_border">
					<div class="preview_box">
						' . $og_prev_img_html . '
						<div class="preview_txt">
							<div class="preview_title">' . ( empty( $head_info[ 'og:title' ] ) ?
								_x( 'No Title', 'default title', 'wpsso' ) : $head_info[ 'og:title' ] ) . 
							'</div><!-- .preview_title -->
							<div class="preview_desc">' . ( empty( $head_info[ 'og:description' ] ) ?
								_x( 'No Description.', 'default description', 'wpsso' ) : $head_info[ 'og:description' ] ) . 
							'</div><!-- .preview_desc -->
							<div class="preview_by">' . 
								$_SERVER[ 'SERVER_NAME' ] . 
								( empty( $this->p->options[ 'add_meta_property_article:author' ] ) ||
									empty( $head_info[ 'article:author:name' ] ) ?
										'' : ' | By ' . $head_info[ 'article:author:name' ] ) . 
							'</div><!-- .preview_by -->
						</div><!-- .preview_txt -->
					</div><!-- .preview_box -->
				</div><!-- .preview_box_border -->
			</td><!-- .preview_container -->';

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-social-preview' ) . '</td>';

			return $table_rows;
		}

		public function filter_metabox_sso_oembed_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$oembed_data    = false;
			$oembed_html    = '';
			$oembed_width   = 600;

			$json_url    = $this->p->util->get_oembed_url( $mod, 'json' );
			$xml_url     = $this->p->util->get_oembed_url( $mod, 'xml' );
			$oembed_data = $this->p->util->get_oembed_data( $mod, $oembed_width );

			$table_rows[] = $form->get_th_html( _x( 'oEmbed JSON URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $json_url ) . '</td>';

			$table_rows[] = $form->get_th_html( _x( 'oEmbed XML URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $xml_url ) . '</td>';

			$table_rows[ 'subsection_oembed_data' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'oEmbed Data', 'option label', 'wpsso' ) . '</h4></td>';

			if ( ! empty( $oembed_data ) && is_array( $oembed_data ) ) {

				foreach( $oembed_data as $key => $val ) {

					if ( 'html' === $key ) {

						$oembed_html = $val;

						$val = __( '(see below)', 'wpsso' );
					}

					$table_rows[] = '<th class="short">' . esc_html( $key ) . '</th>' .
						'<td class="wide">' . SucomUtil::maybe_link_url( esc_html( $val ) ) . '</td>';
				}

			} else {
				$table_rows[] = '<td colspan="2"><p class="status-msg">' . __( 'No oEmbed data found.', 'wpsso' ) . '</p></td>';
			}

			$table_rows[ 'subsection_oembed_html' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'oEmbed HTML', 'option label', 'wpsso' ) . '</h4></td>';

			if ( ! empty( $oembed_html ) ) {

				$table_rows[] = '<td colspan="2" class="oembed_container">' . $oembed_html . '</td><!-- .oembed_container -->';

				$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-oembed-html' ) . '</td>';

			} else {
				$table_rows[] = '<td colspan="2"><p class="status-msg">' . __( 'No oEmbed HTML found.', 'wpsso' ) . '</p></td>';
			}

			return $table_rows;
		}

		public function filter_metabox_sso_head_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$head_tags = $mod[ 'obj' ]->get_head_tags();

			if ( ! is_array( $head_tags ) ) {	// Just in case.

				return $table_rows;
			}

			$script_class = '';

			foreach ( $head_tags as $parts ) {

				if ( 1 === count( $parts ) ) {

					if ( 0 === strpos( $parts[0], '<script ' ) ) {

						$script_class = 'script';

					} elseif ( 0 === strpos( $parts[0], '<noscript ' ) ) {

						$script_class = 'noscript';
					}

					$table_rows[] = '<td colspan="5" class="html ' . $script_class . '"><pre>' . esc_html( $parts[0] ) . '</pre></td>';

					if ( 'script' === $script_class || 0 === strpos( $parts[0], '</noscript>' ) ) {

						$script_class = '';
					}

				} elseif ( isset( $parts[5] ) ) {

					/**
					 * Skip meta tags with reserved values but display empty values.
					 */
					if ( $parts[5] === WPSSO_UNDEF || $parts[5] === (string) WPSSO_UNDEF ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $parts[3] . ' value is ' . WPSSO_UNDEF . ' (skipped)' );
						}

						continue;
					}

					if ( $parts[1] === 'meta' && $parts[2] === 'itemprop' && strpos( $parts[3], '.' ) !== 0 ) {

						$match_name = preg_replace( '/^.*\./', '', $parts[3] );

					} else {

						$match_name = $parts[3];
					}

					$opt_name    = strtolower( 'add_' . $parts[1] . '_' . $parts[2] . '_' . $parts[3] );
					$opt_exists  = isset( $this->p->options[ $opt_name ] ) ? true : false;
					$opt_enabled = empty( $this->p->options[ $opt_name ] ) ? false : true;

					$tr_class = empty( $script_class ) ? '' : ' ' . $script_class;

					/**
					 * If there's no HTML to include in the webpage head section,
					 * then mark the meta tag as disabled and hide it in basic view.
					 */
					if ( empty( $parts[ 0 ] ) ) {

						$tr_class .= ' is_disabled hide_row_in_basic';

					} else {

						$tr_class .= ' is_enabled';
					}

					/**
					 * The meta tag is enabled, but its value is empty (and not 0).
					 */
					if ( $opt_enabled && isset( $parts[ 5 ] ) && empty( $parts[ 5 ] ) && ! is_numeric( $parts[ 5 ] ) ) {

						$tr_class .= ' is_empty';
					}

					/**
					 * The meta tag is "standard" if an option exists to enable / disable
					 * the meta tag, otherwise it's a meta tag meant for internal use.
					 */
					$tr_class .= $opt_exists ? ' is_standard' : ' is_internal';

					$table_rows[] = '<tr class="' . trim( $tr_class ) . '">' .
						'<th class="xshort">' . $parts[1] . '</th>' . 
						'<th class="xshort">' . $parts[2] . '</th>' . 
						'<td class="">' . ( empty( $parts[6] ) ? '' : '<!-- ' . $parts[6] . ' -->' ) . $match_name . '</td>' . 
						'<th class="xshort">' . $parts[4] . '</th>' . 
						'<td class="wide">' . SucomUtil::maybe_link_url( $parts[5] ) . '</td>';
				}
			}

			return $table_rows;
		}

		public function filter_metabox_sso_validate_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$sharing_url = $this->p->util->get_sharing_url( $mod, $add_page = false );

			$sharing_url_encoded = urlencode( $sharing_url );

			$have_schema = empty( $this->p->avail[ 'p' ][ 'schema' ] ) || empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ?  false : true;

			$have_amp = function_exists( 'amp_get_permalink' ) ? true : false;

			$amp_url_encoded = $have_amp ? urlencode( amp_get_permalink( $mod[ 'id' ] ) ) : '';

			$buttons = array(
				'facebook-debugger' => array(
					'title' => _x( 'Facebook Sharing Debugger', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Open Graph', 'submit button', 'wpsso' ),
					'url'   => 'https://developers.facebook.com/tools/debug/?q=' . $sharing_url_encoded,
				),
				'facebook-microdata' => array(
					'title' => _x( 'Facebook Microdata Debug Tool', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Microdata', 'submit button', 'wpsso' ),
					'url'   => 'https://business.facebook.com/ads/microdata/debug',
					'msg'   => $this->p->msgs->get( 'info-meta-validate-facebook-microdata' ) .
						SucomForm::get_no_input_clipboard( $sharing_url ),
				),
				'google-page-speed' => array(
					'title' => _x( 'Google PageSpeed Insights', 'option label', 'wpsso' ),
					'label' => _x( 'Validate PageSpeed', 'submit button', 'wpsso' ),
					'url'   => 'https://developers.google.com/speed/pagespeed/insights/?url=' . $sharing_url_encoded,
				),
				'google-rich-results' => array(
					'title' => _x( 'Google Rich Results Test', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Rich Results', 'submit button', 'wpsso' ) . ( $have_schema ? '' : ' *' ),
					'url'   => $have_schema ? 'https://search.google.com/test/rich-results?url=' . $sharing_url_encoded : '',
				),
				'google-testing-tool' => array(
					'title' => _x( 'Google Structured Data Test (Deprecated)', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Structured Data', 'submit button', 'wpsso' ) . ( $have_schema ? '' : ' *' ),
					'url'   => $have_schema ? 'https://search.google.com/structured-data/testing-tool/u/0/#url=' . $sharing_url_encoded : '',
				),
				'linkedin' => array(
					'title' => _x( 'LinkedIn Post Inspector', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Metadata', 'submit button', 'wpsso' ),
					'url'   => 'https://www.linkedin.com/post-inspector/inspect/' . $sharing_url_encoded,
				),
				'pinterest' => array(
					'title' => _x( 'Pinterest Rich Pins Validator', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Rich Pins', 'submit button', 'wpsso' ),
					'url'   => 'https://developers.pinterest.com/tools/url-debugger/?link=' . $sharing_url_encoded,
				),
				'twitter' => array(
					'title' => _x( 'Twitter Card Validator', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Twitter Card', 'submit button', 'wpsso' ),
					'url'   => 'https://cards-dev.twitter.com/validator',
					'msg'   => $this->p->msgs->get( 'info-meta-validate-twitter' ) .
						SucomForm::get_no_input_clipboard( $sharing_url ),
				),
				'amp' => array(
					'title' => $mod[ 'is_post' ] ? _x( 'The AMP Project Validator', 'option label', 'wpsso' ) : '',
					'label' => $mod[ 'is_post' ] ? _x( 'Validate AMP Markup', 'submit button', 'wpsso' ) . ( $have_amp ? '' : ' **' ) : '',
					'url'   => $mod[ 'is_post' ] && $have_amp ? 'https://validator.ampproject.org/#url=' . $amp_url_encoded : '',
				),
				'w3c' => array(
					'title' => _x( 'W3C Markup Validation', 'option label', 'wpsso' ),
					'label' => _x( 'Validate HTML Markup', 'submit button', 'wpsso' ),
					'url'   => 'https://validator.w3.org/nu/?doc=' . $sharing_url_encoded,
				),
			);

			foreach ( $buttons as $key => $b ) {

				if ( ! empty( $b[ 'title' ] ) ) {

					$table_rows[ 'validate_' . $key ] = $form->get_th_html( $b[ 'title' ], 'medium' );

					$table_rows[ 'validate_' . $key ] .= '<td class="validate">' . 
						( isset( $b[ 'msg' ] ) ? $b[ 'msg' ] : $this->p->msgs->get( 'info-meta-validate-' . $key ) ) .
							'</td>';

					$table_rows[ 'validate_' . $key ] .= '<td class="validate">' .
						$form->get_button( $b[ 'label' ], 'button-secondary', '', $b[ 'url' ], $newtab = true, ( $b[ 'url' ] ? false : true ) ) .
							'</td>';
				}
			}

			$table_rows[ 'validate_info' ] = '<td class="validate" colspan="3">' . $this->p->msgs->get( 'info-meta-validate-info' ) . '</td>';

			return $table_rows;
		}
	}
}
