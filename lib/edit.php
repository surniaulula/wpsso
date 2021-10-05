<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoEdit' ) ) {

	class WpssoEdit {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$min_int = SucomUtil::get_min_int();

			/**
			 * Default Document SSO metabox tabs are defined in WpssoWpMeta->get_document_meta_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array( 
				'metabox_sso_edit_rows'     => 4,
				'metabox_sso_robots_rows'   => 4,
				'metabox_sso_social_rows'   => 4,
				'metabox_sso_oembed_rows'   => 4,
				'metabox_sso_head_rows'     => 4,
				'metabox_sso_validate_rows' => 4,
			), $min_int );	// Run before any add-on filters.
		}

		public function filter_metabox_sso_edit_rows( $table_rows, $form, $head_info, $mod ) {

			$dots           = '...';
			$read_cache     = true;
			$no_hashtags    = false;
			$maybe_hashtags = true;
			$do_encode      = true;

			$pin_img_disabled       = empty( $this->p->options[ 'pin_add_img_html' ] ) ? true : false;
			$seo_desc_disabled      = empty( $this->p->options[ 'add_meta_name_description' ] ) ? true : false;
			$canonical_url_disabled = empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? true : false;

			$pin_img_msg       = $pin_img_disabled ? $this->p->msgs->pin_img_disabled() : '';
			$seo_desc_msg      = $seo_desc_disabled ? $this->p->msgs->seo_option_disabled( 'meta name description' ) : '';
			$canonical_url_msg = $canonical_url_disabled ? $this->p->msgs->seo_option_disabled( 'link rel canonical' ) : '';

			/**
			 * Select option arrays.
			 */
			$og_types         = $this->p->og->get_og_types_select();
			$schema_types     = $this->p->schema->get_schema_types_select( $context = 'meta' );
			$primary_terms    = $this->p->post->get_primary_terms( $mod, $tax_slug = 'category', $output = 'names' );
			$article_sections = $this->p->util->get_article_sections();

			/**
			 * Maximum option lengths.
			 */
			$og_title_max_len      = $this->p->options[ 'og_title_max_len' ];
			$og_title_warn_len     = $this->p->options[ 'og_title_warn_len' ];
			$og_desc_max_len       = $this->p->options[ 'og_desc_max_len' ];
			$og_desc_warn_len      = $this->p->options[ 'og_desc_warn_len' ];
			$pin_img_desc_max_len  = $this->p->options[ 'pin_img_desc_max_len' ];
			$pin_img_desc_warn_len = $this->p->options[ 'pin_img_desc_warn_len' ];
			$tc_title_max_len      = $this->p->options[ 'tc_title_max_len' ];
			$tc_desc_max_len       = $this->p->options[ 'tc_desc_max_len' ];
			$seo_desc_max_len      = $this->p->options[ 'seo_desc_max_len' ];		// Description Meta Tag Max. Length.

			/**
			 * Default option values.
			 */
			$def_og_title      = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, 'none' );
			$def_og_desc       = $this->p->page->get_description( $og_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags, $do_encode, 'none' );
			$def_pin_img_desc  = $pin_img_disabled ? '' : $this->p->page->get_description( $pin_img_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags );
			$def_tc_title      = $this->p->page->get_title( $tc_title_max_len, $dots, $mod, $read_cache );
			$def_tc_desc       = $this->p->page->get_description( $tc_desc_max_len, $dots, $mod, $read_cache );
			$def_seo_desc      = $seo_desc_disabled ? '' : $this->p->page->get_description( $seo_desc_max_len, $dots, $mod, $read_cache, $no_hashtags );
			$def_canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = false );
			$def_reading_mins  = $this->p->page->get_reading_mins( $mod );

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
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-faq' ) . '</td>',
				),
				'info_schema_qa' => array(
					'tr_class'  => $schema_type_row_class[ 'qa' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-qa' ) . '</td>',
				),
				'info_schema_question' => array(
					'tr_class'  => $schema_type_row_class[ 'question' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-question' ) . '</td>',
				),
				'attach_img_crop' => $mod[ 'is_attachment' ] && wp_attachment_is_image( $mod[ 'id' ] ) ? array(
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
								'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
								'is_transl' => true,					// No label translation required.
								'is_sorted' => true,					// No label sorting required.
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
				'primary_term_id' => ! empty( $primary_terms ) ? array(	// Show the option if we have post category terms.
					'th_class' => 'medium',
					'label'    => _x( 'Primary Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-primary_term_id',
					'content'  => $form->get_select( 'primary_term_id', $primary_terms,
						$css_class = 'primary_term_id', $css_id = '', $is_assoc = true ),
				) : array(),
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
				'pin_img_desc' => array(
					'tr_class' => $pin_img_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Pinterest Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-pin_img_desc',
					'content'  => $form->get_textarea( 'pin_img_desc', $css_class = '', $css_id = '',
						array( 'max' => $pin_img_desc_max_len, 'warn' => $pin_img_desc_warn_len ),
							$def_pin_img_desc, $pin_img_disabled ) . ' ' . $pin_img_msg,
				),
				'tc_title' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Twitter Card Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-tc_title',
					'content'  => $form->get_input( 'tc_title', $css_class = 'wide', $css_id = '',
						$tc_title_max_len, $def_tc_title ),
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
				'canonical_url' => array(
					'tr_class' => $canonical_url_disabled ? 'hide_in_basic' : '',
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
								'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
								'is_transl' => true,					// No label translation required.
								'is_sorted' => true,					// No label sorting required.
							)
						),
				),
				'og_reading_mins' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'th_class' => 'medium',
					'label'    => _x( 'Est. Reading Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-reading_mins',
					'content'  => $form->get_input( 'reading_mins', $css_class = 'xshort', $css_id = '', 0, $def_reading_mins ) . ' ' .
						__( 'minute(s)', 'wpsso' ),
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
					'content'  => $form->get_input( 'book_isbn', $css_class = '', $css_id = '', array( 'min' => 10, 'max' => 13 ) ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/**
		 * See https://developers.google.com/search/reference/robots_meta_tag.
		 */
		public function filter_metabox_sso_robots_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

				return $this->p->msgs->get_robots_disabled_rows( $table_rows );
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-robots-meta' ) . '</td>';

			$table_rows[ 'robots_noarchive' ] = '' .
			$form->get_th_html( _x( 'No Archive', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'meta-robots_noarchive' ) . 
			'<td>' . $form->get_checkbox( 'robots_noarchive' ) . ' ' .
			_x( 'do not show a cached link in search results', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'robots_nofollow' ] = '' .
			$form->get_th_html( _x( 'No Follow', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'meta-robots_nofollow' ) . 
			'<td>' . $form->get_checkbox( 'robots_nofollow' ) . ' ' .
			_x( 'do not follow links on this webpage', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'robots_noimageindex' ] = '' .
			$form->get_th_html( _x( 'No Image Index', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'meta-robots_noimageindex' ) . 
			'<td>' . $form->get_checkbox( 'robots_noimageindex' ) . ' ' .
			_x( 'do not index images on this webpage', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'robots_noindex' ] = '' .
			$form->get_th_html( _x( 'No Index', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'meta-robots_noindex' ) . 
			'<td>' . $form->get_checkbox( 'robots_noindex' ) . ' ' .
			_x( 'do not show this webpage in search results', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'robots_nosnippet' ] = '' .
			$form->get_th_html( _x( 'No Snippet', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'meta-robots_nosnippet' ) . 
			'<td>' . $form->get_checkbox( 'robots_nosnippet' ) . ' ' .
			_x( 'do not show a text snippet or a video preview in search results', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'robots_notranslate' ] = '' .
			$form->get_th_html( _x( 'No Translate', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'meta-robots_notranslate' ) . 
			'<td>' . $form->get_checkbox( 'robots_notranslate' ) . ' ' .
			_x( 'do not offer translation of this webpage in search results', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'robots_max_snippet' ] = '' .
			$form->get_th_html( _x( 'Snippet Max. Length', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'robots_max_snippet' ) .	// Use the tooltip from plugin settings. 
			'<td>' . $form->get_input( 'robots_max_snippet', $css_class = 'chars', $css_id = '', $len = 0, $holder = true ) . ' ' .
			_x( 'characters or less', 'option comment', 'wpsso' ) . ' (' . _x( '-1 for no limit', 'option comment', 'wpsso' ) . ')</td>';

			$table_rows[ 'robots_max_image_preview' ] = '' .
			$form->get_th_html( _x( 'Image Preview Size', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'robots_max_image_preview' ) .	// Use the tooltip from plugin settings.
			'<td>' . $form->get_select( 'robots_max_image_preview', $this->p->cf[ 'form' ][ 'robots_max_image_preview' ] ) . '</td>';

			$table_rows[ 'robots_max_video_preview' ] = '' .
			$form->get_th_html( _x( 'Video Max. Previews', 'option label', 'wpsso' ),
				$css_class = 'medium', $css_id = 'robots_max_video_preview' ) .	// Use the tooltip from plugin settings.
			'<td>' . $form->get_input( 'robots_max_video_preview', $css_class = 'chars', $css_id = '', $len = 0, $holder = true ) .
			_x( 'seconds', 'option comment', 'wpsso' ) . ' (' . _x( '-1 for no limit', 'option comment', 'wpsso' ) . ')</td>';

			return $table_rows;
		}

		/**
		 * Social Preview tab content.
		 */
		public function filter_metabox_sso_social_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

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

			$table_rows[] = '' . 
				$form->get_th_html( _x( 'Canonical URL', 'option label', 'wpsso' ), $css_class = 'medium nowrap' ) . 
				'<td>' . SucomForm::get_no_input_clipboard( $canonical_url ) . '</td>';

			$table_rows[] = '' .
				$form->get_th_html( _x( 'Shortlink URL', 'option label', 'wpsso' ), $css_class = 'medium nowrap' ) . 
				'<td>' . SucomForm::get_no_input_clipboard( $shortlink_url ) . '</td>';

			$table_rows[ 'subsection_og_example' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'Facebook / Open Graph Example', 'option label', 'wpsso' ) . '</h4></td>';

			$table_rows[] = '' .
				'<td colspan="2" class="fb_preview_container">
					<div class="fb_preview_box_border">
						<div class="fb_preview_box">
							' . $og_prev_img_html . '
							<div class="fb_preview_text">
								<div class="fb_preview_title">' . ( empty( $head_info[ 'og:title' ] ) ?
									_x( 'No Title', 'default title', 'wpsso' ) : $head_info[ 'og:title' ] ) . 
								'</div><!-- .fb_preview_title -->
								<div class="fb_preview_desc">' . ( empty( $head_info[ 'og:description' ] ) ?
									_x( 'No Description.', 'default description', 'wpsso' ) : $head_info[ 'og:description' ] ) . 
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

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-social-preview' ) . '</td>';

			return $table_rows;
		}

		/**
		 * oEmbed Preview tab content.
		 */
		public function filter_metabox_sso_oembed_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$oembed_data  = false;
			$oembed_html  = '';
			$oembed_width = 600;
			$json_url     = $this->p->util->get_oembed_url( $mod, 'json' );
			$xml_url      = $this->p->util->get_oembed_url( $mod, 'xml' );
			$oembed_data  = $this->p->util->get_oembed_data( $mod, $oembed_width );

			$table_rows[] = '' .
				$form->get_th_html( _x( 'oEmbed JSON URL', 'option label', 'wpsso' ), $css_class = 'medium' ) . 
				'<td>' . SucomForm::get_no_input_clipboard( $json_url ) . '</td>';

			$table_rows[] = '' .
				$form->get_th_html( _x( 'oEmbed XML URL', 'option label', 'wpsso' ), $css_class = 'medium' ) . 
				'<td>' . SucomForm::get_no_input_clipboard( $xml_url ) . '</td>';

			$table_rows[ 'subsection_oembed_data' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'oEmbed Data', 'option label', 'wpsso' ) . '</h4></td>';

			if ( ! empty( $oembed_data ) && is_array( $oembed_data ) ) {

				foreach( $oembed_data as $key => $val ) {

					if ( 'html' === $key ) {

						$oembed_html = $val;

						$val = __( '(see below)', 'wpsso' );
					}

					$table_rows[] = '' .
						'<th class="medium">' . esc_html( $key ) . '</th>' .
						'<td>' . SucomUtil::maybe_link_url( esc_html( $val ) ) . '</td>';
				}

			} else {

				$table_rows[] = '<td colspan="2"><p class="status-msg">' . __( 'No oEmbed data found.', 'wpsso' ) . '</p></td>';
			}

			$table_rows[ 'subsection_oembed_html' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'oEmbed HTML', 'option label', 'wpsso' ) . '</h4></td>';

			if ( ! empty( $oembed_html ) ) {

				$table_rows[] = '<td colspan="2" class="oembed_container">' . $oembed_html . '</td><!-- .oembed_container -->';

				$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-oembed-footer' ) . '</td>';

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

					if ( 0 === strpos( $parts[ 0 ], '<meta name="wpsso-' ) ) {

						continue;

					} elseif ( 0 === strpos( $parts[ 0 ], '<script ' ) ) {

						$script_class = 'script';

					} elseif ( 0 === strpos( $parts[ 0 ], '<noscript ' ) ) {

						$script_class = 'noscript';
					}

					$table_rows[] = '<td colspan="5" class="html ' . $script_class . '"><pre>' . esc_html( $parts[ 0 ] ) . '</pre></td>';

					if ( 'script' === $script_class || 0 === strpos( $parts[ 0 ], '</noscript>' ) ) {

						$script_class = '';
					}

				} elseif ( isset( $parts[ 5 ] ) ) {

					/**
					 * Skip meta tags with reserved values but display empty values.
					 */
					if ( $parts[ 5 ] === WPSSO_UNDEF || $parts[ 5 ] === (string) WPSSO_UNDEF ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $parts[ 3 ] . ' value is ' . WPSSO_UNDEF . ' (skipped)' );
						}

						continue;
					}

					if ( $parts[ 1 ] === 'meta' && $parts[ 2 ] === 'itemprop' && strpos( $parts[ 3 ], '.' ) !== 0 ) {

						$match_name = preg_replace( '/^.*\./', '', $parts[ 3 ] );

					} else {

						$match_name = $parts[ 3 ];
					}

					$opt_name    = strtolower( 'add_' . $parts[ 1 ] . '_' . $parts[ 2 ] . '_' . $parts[ 3 ] );
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
						'<th class="xshort">' . $parts[ 1 ] . '</th>' . 
						'<th class="xshort">' . $parts[ 2 ] . '</th>' . 
						'<td class="">' . ( empty( $parts[ 6 ] ) ? '' : '<!-- ' . $parts[ 6 ] . ' -->' ) . $match_name . '</td>' . 
						'<th class="xshort">' . $parts[ 4 ] . '</th>' . 
						'<td class="wide">' . SucomUtil::maybe_link_url( $parts[ 5 ] ) . '</td>';
				}
			}

			return $table_rows;
		}

		public function filter_metabox_sso_validate_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$validators = $this->p->util->get_validators( $mod );

			foreach ( $validators as $key => $el ) {

				if ( empty( $el[ 'type' ] ) ) {

					continue;
				}

				/**
				 * Example extra message string: SucomForm::get_no_input_clipboard( $canonical_url )
				 */
				$extra_msg = isset( $el[ 'extra_msg' ] ) ? $el[ 'extra_msg' ] : '';

				$button_label = sprintf( _x( 'Validate %s', 'submit button', 'wpsso' ), $el[ 'type' ] );

				$is_disabled = empty( $el[ 'url' ] ) ? true : false;

				$table_rows[ 'validate_' . $key ] = '' .
					$form->get_th_html( $el[ 'title' ], $css_class = 'medium' ) .
					'<td class="validate">' . $this->p->msgs->get( 'info-meta-validate-' . $key ) . $extra_msg . '</td>' .
					'<td class="validate">' . $form->get_button( $button_label, $css_class = 'button-secondary', $css_id = '',
						$el[ 'url' ], $newtab = true, $is_disabled ) . '</td>';
			}

			$table_rows[ 'validate_info' ] = '<td class="validate" colspan="3">' . $this->p->msgs->get( 'info-meta-validate-footer' ) . '</td>';

			return $table_rows;
		}
	}
}
