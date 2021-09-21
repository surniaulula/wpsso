<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdAdminAdvanced' ) ) {

	class WpssoStdAdminAdvanced {

		private $p;	// Wpsso class object.

		private $html_tag_shown = array();	// Cache for HTML tags already shown.
		private $og_types       = null;
		private $schema_types   = null;
		private $org_req_msg    = null;
		private $org_names      = null;
		private $person_names   = null;
		private $plm_req_msg    = null;
		private $place_names    = null;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			$this->p->util->add_plugin_filters( $this, array(
				'plugin_integration_rows'       => 3,	// Advanced Settings > Integration tab.
				'plugin_image_sizes_rows'       => 2,	// Advanced Settings > Image Sizes tab.
				'plugin_interface_rows'         => 2,	// Advanced Settings > Interface tab.
				'services_media_rows'           => 2,	// Service APIs > Media Services tab.
				'services_shortening_rows'      => 2,	// Service APIs > Shortening Services tab.
				'services_ratings_reviews_rows' => 2,	// Service APIs > Ratings and Reviews tab.
				'doc_types_og_types_rows'       => 2,	// Document Types > Schema tab.
				'doc_types_schema_types_rows'   => 2,	// Document Types > Open Graph tab.
				'def_schema_creative_work_rows' => 2,	// Schema Defaults > Creative Work tab.
				'def_schema_event_rows'         => 2,	// Schema Defaults > Event tab.
				'def_schema_job_posting_rows'   => 2,	// Schema Defaults > Job Posting tab.
				'def_schema_review_rows'        => 2,	// Schema Defaults > Review tab.
				'cm_custom_contacts_rows'       => 2,	// Contact Fields > Custom Contacts tab.
				'cm_default_contacts_rows'      => 2,	// Contact Fields > Default Contacts tab.
				'advanced_user_about_rows'      => 2,	// About the User metabox.
				'metadata_product_attrs_rows'   => 2,	// Metadata > Product Attributes tab.
				'metadata_custom_fields_rows'   => 2,	// Metadata > Custom Fields tab.
				'head_tags_facebook_rows'       => 3,	// HTML Tags > Facebook tab.
				'head_tags_open_graph_rows'     => 3,	// HTML Tags > Open Graph tab.
				'head_tags_twitter_rows'        => 3,	// HTML Tags > Twitter tab.
				'head_tags_schema_rows'         => 3,	// HTML Tags > Schema tab.
				'head_tags_seo_other_rows'      => 3,	// HTML Tags > SEO / Other tab.
				'advanced_wp_sitemaps_rows'     => 3,	// WordPress Sitemaps metabox.
			), $prio = 20 );
		}

		private function maybe_set_vars() {

			if ( null !== $this->og_types ) {	// Aleady setup.

				return;
			}

			$this->og_types     = $this->p->og->get_og_types_select();
			$this->schema_types = $this->p->schema->get_schema_types_select( $context = 'settings' );
			$this->org_req_msg  = $this->p->msgs->maybe_ext_required( 'wpssoorg' );
			$this->org_names    = $this->p->util->get_form_cache( 'org_names', $add_none = true );
			$this->person_names = $this->p->util->get_form_cache( 'person_names', $add_none = true );
			$this->plm_req_msg  = $this->p->msgs->maybe_ext_required( 'wpssoplm' );
			$this->place_names  = $this->p->util->get_form_cache( 'place_names', $add_none = true );
		}

		/**
		 * Advanced Settings > Integration tab.
		 */
		public function filter_plugin_integration_rows( $table_rows, $form, $network = false ) {

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_document_title' ] = '' .
				$form->get_th_html( _x( 'Webpage Document Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_document_title' ) .
				'<td class="blank">' . $form->get_no_select( 'plugin_document_title',  $this->p->cf[ 'form' ][ 'document_title' ] ) .
				$this->p->msgs->maybe_title_tag_disabled() . '</td>' .
				WpssoAdmin::get_option_site_use( 'plugin_document_title', $form, $network );

			$table_rows[ 'plugin_filter_title' ] = '' . 
				$form->get_th_html( _x( 'Use Filtered "SEO" Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_filter_title' ) . 
				$form->get_no_td_checkbox( 'plugin_filter_title', _x( 'not recommended', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_filter_title', $form, $network );

			$table_rows[ 'plugin_filter_content' ] = '' . 
				$form->get_th_html( _x( 'Use Filtered Content', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_filter_content' ) . 
				$form->get_no_td_checkbox( 'plugin_filter_content', _x( 'recommended (see help text)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_filter_content', $form, $network );

			$table_rows[ 'plugin_filter_excerpt' ] = '' . 
				$form->get_th_html( _x( 'Use Filtered Excerpt', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_filter_excerpt' ) . 
				$form->get_no_td_checkbox( 'plugin_filter_excerpt', _x( 'recommended if shortcodes in excerpts', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_filter_excerpt', $form, $network );

			$table_rows[ 'plugin_page_excerpt' ] = '' . 
				$form->get_th_html( _x( 'Enable Excerpt for Pages', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_page_excerpt' ) . 
				$form->get_no_td_checkbox( 'plugin_page_excerpt' ) .
				WpssoAdmin::get_option_site_use( 'plugin_page_excerpt', $form, $network );

			$table_rows[ 'plugin_page_tags' ] = '' .
				$form->get_th_html( _x( 'Enable Tags for Pages', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_page_tags' ) . 
				$form->get_no_td_checkbox( 'plugin_page_tags' ) .
				WpssoAdmin::get_option_site_use( 'plugin_page_tags', $form, $network );

			$table_rows[ 'plugin_new_user_is_person' ] = '' . 
				$form->get_th_html( _x( 'Add Person Role for New Users', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_new_user_is_person' ) . 
				$form->get_no_td_checkbox( 'plugin_new_user_is_person' ) .
				WpssoAdmin::get_option_site_use( 'plugin_new_user_is_person', $form, $network );

			$table_rows[ 'plugin_clear_post_terms' ] = $form->get_tr_hide( 'basic', 'plugin_clear_post_terms' ) . 
				$form->get_th_html( _x( 'Clear Term Cache when Publishing', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_clear_post_terms' ) . 
				$form->get_no_td_checkbox( 'plugin_clear_post_terms' ) . 
				WpssoAdmin::get_option_site_use( 'plugin_clear_post_terms', $form, $network );

			$table_rows[ 'plugin_clear_for_comment' ] = $form->get_tr_hide( 'basic', 'plugin_clear_for_comment' ) . 
				$form->get_th_html( _x( 'Clear Post Cache for New Comment', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_clear_for_comment' ) . 
				$form->get_no_td_checkbox( 'plugin_clear_for_comment' ) . 
				WpssoAdmin::get_option_site_use( 'plugin_clear_for_comment', $form, $network );

			$table_rows[ 'plugin_check_img_dims' ] = '' . 
				$form->get_th_html( _x( 'Enforce Image Dimension Checks', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_check_img_dims' ) . 
				$form->get_no_td_checkbox( 'plugin_check_img_dims', _x( 'recommended (see help text)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_check_img_dims', $form, $network );

			$table_rows[ 'plugin_upscale_images' ] = '' . 
				$form->get_th_html( _x( 'Upscale Media Library Images', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_upscale_images' ) . 
				$form->get_no_td_checkbox( 'plugin_upscale_images' ) .
				WpssoAdmin::get_option_site_use( 'plugin_upscale_images', $form, $network );

			$table_rows[ 'plugin_upscale_img_max' ] = $form->get_tr_hide( 'basic', 'plugin_upscale_img_max' ) .
				$form->get_th_html( _x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_upscale_img_max' ) . 
				'<td class="blank">' . $form->get_no_input( 'plugin_upscale_img_max', $css_class = 'short' ) . ' %</td>' .
				WpssoAdmin::get_option_site_use( 'plugin_upscale_img_max', $form, $network );

			$table_rows[ 'plugin_img_alt_prefix' ] = '' . 
				$form->get_th_html_locale( _x( 'Content Image Alt Prefix', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_img_alt_prefix' ) . 
				'<td class="blank">' . SucomUtil::get_key_value( 'plugin_img_alt_prefix', $form->options ) . '</td>' .
				WpssoAdmin::get_option_site_use( 'plugin_img_alt_prefix', $form, $network );

			$table_rows[ 'plugin_p_cap_prefix' ] = '' . 
				$form->get_th_html_locale( _x( 'WP Caption Text Prefix', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_p_cap_prefix' ) . 
				'<td class="blank">' . SucomUtil::get_key_value( 'plugin_p_cap_prefix', $form->options ) . '</td>' .
				WpssoAdmin::get_option_site_use( 'plugin_p_cap_prefix', $form, $network );

			$table_rows[ 'plugin_no_title_text' ] = '' . 
				$form->get_th_html_locale( _x( 'No Title Text', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_no_title_text' ) . 
				'<td class="blank">' . SucomUtil::get_key_value( 'plugin_no_title_text', $form->options ) . '</td>' .
				WpssoAdmin::get_option_site_use( 'plugin_no_title_text', $form, $network );

			$table_rows[ 'plugin_no_desc_text' ] = '' . 
				$form->get_th_html_locale( _x( 'No Description Text', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_no_desc_text' ) . 
				'<td class="blank">' . SucomUtil::get_key_value( 'plugin_no_desc_text', $form->options ) . '</td>' .
				WpssoAdmin::get_option_site_use( 'plugin_no_desc_text', $form, $network );

			/**
			 * Plugin and theme integration options.
			 */
			$table_rows[ 'subsection_plugin_theme_integration' ] = '' .
				'<td colspan="4" class="subsection"><h4>' . _x( 'Plugin and Theme Integration', 'metabox title', 'wpsso' ) . '</h4></td>';

			$table_rows[ 'plugin_check_head' ] = $form->get_tr_hide( 'basic', 'plugin_check_head' ) .
				$form->get_th_html( _x( 'Check for Duplicate Meta Tags', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_check_head' ) . 
				$form->get_no_td_checkbox( 'plugin_check_head' ) .
				WpssoAdmin::get_option_site_use( 'plugin_check_head', $form, $network );

			$table_rows[ 'plugin_product_include_vat' ] = '' .
				$form->get_th_html( _x( 'Include VAT in Product Prices', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_product_include_vat' ) .
				$form->get_no_td_checkbox( 'plugin_product_include_vat' ) .
				WpssoAdmin::get_option_site_use( 'plugin_product_include_vat', $form, $network );

			$table_rows[ 'plugin_wpseo_social_meta' ] = '' .
				$form->get_th_html( _x( 'Import Yoast SEO Social Meta', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_wpseo_social_meta' ) . 
				$form->get_no_td_checkbox( 'plugin_wpseo_social_meta' ) .
				WpssoAdmin::get_option_site_use( 'plugin_wpseo_social_meta', $form, $network );

			$table_rows[ 'plugin_wpseo_show_import' ] = $form->get_tr_hide( 'basic', 'plugin_wpseo_show_import' ) .
				$form->get_th_html( _x( 'Show Yoast SEO Import Details', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_wpseo_show_import' ) .
				$form->get_no_td_checkbox( 'plugin_wpseo_show_import' ) .
				WpssoAdmin::get_option_site_use( 'plugin_wpseo_show_import', $form, $network );

			return $table_rows;
		}

		/**
		 * Advanced Settings > Image Sizes tab.
		 */
		public function filter_plugin_image_sizes_rows( $table_rows, $form ) {

			if ( $info_msg = $this->p->msgs->get( 'info-image_dimensions' ) ) {

				$table_rows[ 'info-image_dimensions' ] = '<td colspan="2">' . $info_msg . '</td>';
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$json_req_msg     = $this->p->msgs->maybe_ext_required( 'wpssojson' );
			$pin_img_disabled = empty( $this->p->options[ 'pin_add_img_html' ] ) ? true : false;
			$pin_img_msg      = $pin_img_disabled ? $this->p->msgs->pin_img_disabled( $extra_css_class = 'inline' ) : '';

			$table_rows[ 'og_img_size' ] = '' .
				$form->get_th_html( _x( 'Open Graph (Facebook and oEmbed)', 'option label', 'wpsso' ), '', 'og_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'og_img' ) . '</td>';

			$table_rows[ 'pin_img_size' ] = ( $pin_img_disabled ? $form->get_tr_hide( 'basic' ) : '' ) .
				$form->get_th_html( _x( 'Pinterest Pin It', 'option label', 'wpsso' ), '', 'pin_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'pin_img', $pin_img_disabled ) . $pin_img_msg . '</td>';

			$table_rows[ 'schema_01x01_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 1:1 (Google)', 'option label', 'wpsso' ), '', 'schema_1x1_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_1x1_img' ) . $json_req_msg . '</td>';

			$table_rows[ 'schema_04x03_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 4:3 (Google)', 'option label', 'wpsso' ), '', 'schema_4x3_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_4x3_img' ) . $json_req_msg . '</td>';

			$table_rows[ 'schema_16x09_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 16:9 (Google)', 'option label', 'wpsso' ), '', 'schema_16x9_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_16x9_img' ) . $json_req_msg . '</td>';

			$table_rows[ 'schema_thumb_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema Thumbnail', 'option label', 'wpsso' ), '', 'schema_thumb_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'thumb_img' ) . '</td>';

			$table_rows[ 'tc_00_sum_img_size' ] = '' .
				$form->get_th_html( _x( 'Twitter Summary Card', 'option label', 'wpsso' ), '', 'tc_sum_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'tc_sum_img' ) . '</td>';

			$table_rows[ 'tc_01_lrg_img_size' ] = '' .
				$form->get_th_html( _x( 'Twitter Large Image Summary Card', 'option label', 'wpsso' ), '', 'tc_lrg_img_size' ) . 
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'tc_lrg_img' ) . '</td>';

			return $table_rows;
		}

		/**
		 * Advanced Settings > Interface tab.
		 */
		public function filter_plugin_interface_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_show_opts' ] = '' .
				$form->get_th_html( _x( 'Options to Show by Default', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_show_opts' ) .
				'<td class="blank">' . $form->get_no_select( 'plugin_show_opts', $this->p->cf[ 'form' ][ 'show_options' ] ) . '</td>';

			$menu_title = _x( 'Validators', 'toolbar menu title', 'wpsso' );

			$table_rows[ 'plugin_show_validate_toolbar' ] = '' .	// Show Validators Toolbar Menu.
				$form->get_th_html( sprintf( _x( 'Show %s Toolbar Menu', 'option label', 'wpsso' ), $menu_title ),
					$css_class = '', $css_id = 'plugin_show_validate_toolbar' ) .
				$form->get_no_td_checkbox( 'plugin_show_validate_toolbar' );

			/**
			 * Show custom meta metaboxes.
			 */
			$add_to_metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

			$table_rows[ 'plugin_add_to' ] = '' .	// Show Document SSO Metabox.
				$form->get_th_html( sprintf( _x( 'Show %s Metabox', 'option label', 'wpsso' ), $add_to_metabox_title ),
					$css_class = '', $css_id = 'plugin_add_to' ) . 
				'<td class="blank">' . $form->get_no_checklist_post_tax_user( $name_prefix = 'plugin_add_to' ) . '</td>';

			/**
			 * Additional item list columns.
			 */
			$list_cols = '<table class="plugin-list-columns">' . "\n" . '<tr>';

			foreach ( WpssoWpMeta::get_column_headers() as $col_key => $col_header ) {

				$list_cols .= '<th>' . $col_header . '</th>';
			}

			$list_cols .= '<td class="underline"></td></tr>' . "\n";

			foreach ( array(
				'post'  => __( 'Posts, Pages, and Custom Post Types', 'wpsso' ),
				'media' => __( 'Media Library', 'wpsso' ),
				'term'  => __( 'Categories, Tags, and Custom Taxonomies', 'wpsso' ),
				// translators: Please ignore - translation uses a different text domain.
				'user'  => __( 'Users' ),
			) as $mod_name => $mod_label ) {

				$list_cols .= '<tr>';

				foreach ( WpssoWpMeta::get_column_headers() as $col_key => $col_header ) {

					$opt_key = 'plugin_' . $col_key . '_col_' . $mod_name;

					if ( $form->in_defaults( $opt_key ) ) {	// Just in case.

						$list_cols .= $form->get_no_td_checkbox( $opt_key, $comment = '', $extra_css_class = 'checkbox' );	// Narrow column.

					} else {

						$list_cols .= '<td class="checkbox"></td>';
					}
				}

				$list_cols .= '<td class="blank"><p>' . $mod_label . '</p></td></tr>' . "\n";
			}

			$list_cols .= '</table>' . "\n";

			$table_rows[ 'plugin_show_columns' ] = '' .
				$form->get_th_html( _x( 'Additional List Table Columns', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_show_columns' ) .
				'<td>' . $list_cols . '</td>';

			return $table_rows;
		}

		/**
		 * Service APIs > Media Services tab.
		 */
		public function filter_services_media_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_gravatar_api' ] = '' . 
				$form->get_th_html( _x( 'Gravatar is Default Author Image', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_gravatar_api' ) . 
				$form->get_no_td_checkbox( 'plugin_gravatar_api' );

			$table_rows[ 'plugin_gravatar_size' ] = $form->get_tr_hide( 'basic', 'plugin_gravatar_size' ) . 
				$form->get_th_html( _x( 'Gravatar Image Size', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_gravatar_size' ) . 
				'<td class="blank">' . $form->get_no_input( 'plugin_gravatar_size', $css_class = 'short' ) . '</td>';

			$check_embed_html = '';

			foreach ( $this->p->cf[ 'form' ][ 'embed_media_apis' ] as $opt_key => $opt_label ) {

				$check_embed_html .= '<p>' . $form->get_no_checkbox_comment( $opt_key ) . ' ' . _x( $opt_label, 'option value', 'wpsso' ) . '</p>';
			}

			$table_rows[ 'plugin_embed_media_apis' ] = '' .
				$form->get_th_html( _x( 'Check for Embedded Media', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_embed_media_apis' ).
				'<td class="blank">' . $check_embed_html . '</td>';

			return $table_rows;
		}

		/**
		 * Service APIs > Shortening Services tab.
		 */
		public function filter_services_shortening_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_shortener' ] = '' . 
				$form->get_th_html( _x( 'URL Shortening Service', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_shortener' ) . 
				'<td class="blank">' . $form->get_no_select_none( 'plugin_shortener' ) . '</td>';

			$table_rows[ 'plugin_min_shorten' ] = $form->get_tr_hide( 'basic', 'plugin_min_shorten' ) . 
				$form->get_th_html( _x( 'Minimum URL Length to Shorten', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_min_shorten' ) . 
				'<td class="blank">' . $form->get_no_input( 'plugin_min_shorten', $css_class = 'short' ) . ' ' .
				_x( 'characters', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_clear_short_urls' ] = '' .
				$form->get_th_html( _x( 'Clear Short URLs on Clear Cache', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_clear_short_urls' ) . 
				$form->get_no_td_checkbox( 'plugin_clear_short_urls' );

			$table_rows[ 'plugin_wp_shortlink' ] = $form->get_tr_hide( 'basic', 'plugin_wp_shortlink' ) .
				$form->get_th_html( _x( 'Use Short URL for WP Shortlink', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_wp_shortlink' ) . 
				$form->get_no_td_checkbox( 'plugin_wp_shortlink' );

			$table_rows[ 'plugin_add_link_rel_shortlink' ] = $form->get_tr_hide( 'basic', 'add_link_rel_shortlink' ) .
				$form->get_th_html( sprintf( _x( 'Add "%s" HTML Tag', 'option label', 'wpsso' ), 'link&nbsp;rel&nbsp;shortlink' ),
					$css_class = '', $css_id = 'plugin_add_link_rel_shortlink' ) . 
				'<td class="blank">' . $form->get_no_checkbox( 'add_link_rel_shortlink', $css_class = '', $css_id = 'add_link_rel_shortlink_html_tag',
					$force = null, $group = 'add_link_rel_shortlink' ) . '</td>';	// Group with option in head tags list

			return $table_rows;
		}

		/**
		 * Service APIs > Ratings and Reviews tab.
		 */
		public function filter_services_ratings_reviews_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$ratings_reviews = $this->p->cf[ 'form' ][ 'ratings_reviews' ];

			$table_rows[ 'plugin_ratings_reviews_svc' ] = '' .
				$form->get_th_html( _x( 'Ratings and Reviews Service', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_ratings_reviews_svc' ) .
				'<td class="blank">' . $form->get_no_select_none( 'plugin_ratings_reviews_svc' ) . '</td>';

			$table_rows[ 'plugin_ratings_reviews_num_max' ] = $form->get_tr_hide( 'basic', 'plugin_ratings_reviews_num_max' ) .
				$form->get_th_html( _x( 'Maximum Number of Reviews', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_ratings_reviews_num_max' ) .
				'<td class="blank">' . $form->get_no_input( 'plugin_ratings_reviews_num_max', $css_class = 'short' ) . '</td>';

			$table_rows[ 'plugin_ratings_reviews_age_max' ] = $form->get_tr_hide( 'basic', 'plugin_ratings_reviews_age_max' ) .
				$form->get_th_html( _x( 'Maximum Age of Reviews', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_ratings_reviews_age_max' ) .
				'<td class="blank">' . $form->get_no_input( 'plugin_ratings_reviews_age_max', $css_class = 'short' ) . ' ' .
				_x( 'months', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_ratings_reviews_for' ] = '' .
				$form->get_th_html( _x( 'Get Reviews for Post Types', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_ratings_reviews_for' ) .
				'<td>' . $form->get_no_checklist_post_types( $name_prefix = 'plugin_ratings_reviews_for' ) . '</td>';

			return $table_rows;
		}

		/**
		 * Document Types > Open Graph tab.
		 */
		public function filter_doc_types_og_types_rows( $table_rows, $form ) {

			$this->maybe_set_vars();

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			/**
			 * Open Graph Type.
			 */
			foreach ( array( 
				'home_page'    => _x( 'Type for Page Homepage', 'option label', 'wpsso' ),
				'home_posts'   => _x( 'Type for Posts Homepage', 'option label', 'wpsso' ),
				'user_page'    => _x( 'Type for User Profiles', 'option label', 'wpsso' ),
				'search_page'  => _x( 'Type for Search Results', 'option label', 'wpsso' ),
				'archive_page' => _x( 'Type for Other Archive', 'option label', 'wpsso' ),
			) as $type_name => $th_label ) {

				$opt_key = 'og_type_for_' . $type_name;	// Hard-coded value - no sanitation required.

				$table_rows[ $opt_key ] = $form->get_tr_hide( 'basic', $opt_key ) .
					$form->get_th_html( $th_label, $css_class = '', $opt_key ) . 
					'<td class="blank">' . $form->get_no_select( $opt_key, $this->og_types, $css_class = 'og_type' ) . '</td>';
			}

			/**
			 * Open Graph Type by Post Type.
			 */
			$type_select = '';

			$post_types = SucomUtilWP::get_post_types( $output = 'objects' );

			foreach ( $post_types as $obj ) {

				$opt_key = SucomUtil::sanitize_hookname( 'og_type_for_' . $obj->name );

				$obj_label = SucomUtilWP::get_object_label( $obj );

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $this->og_types, $css_class = 'og_type' ) . ' ' .
					sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $obj_label ) . '</p>' . "\n";
			}

			$opt_key = 'og_type_for_post_archive';	// Hard-coded value - no sanitation required.

			$type_select .= '<p>' . $form->get_no_select( $opt_key, $this->og_types, $css_class = 'og_type' ) . ' ' .
				sprintf( _x( 'for %s', 'option comment', 'wpsso' ), _x( 'Post Type Archive Page', 'option comment', 'wpsso' ) ) . '</p>' . "\n";

			$table_rows[ 'og_type_for_ptn' ] = '' .
				$form->get_th_html( _x( 'Type by Post Type', 'option label', 'wpsso' ), $css_class = '', $css_id = 'og_type_for_ptn' ) .
				'<td class="blank">' . $type_select . '</td>';

			/**
			 * Open Graph Type by Taxonomy.
			 */
			$type_select = '';
			$type_keys   = array();
			$taxonomies  = SucomUtilWP::get_taxonomies( $output = 'objects' );

			foreach ( $taxonomies as $obj ) {

				$type_keys[] = $opt_key = SucomUtil::sanitize_hookname( 'og_type_for_tax_' . $obj->name );

				$obj_label = SucomUtilWP::get_object_label( $obj );

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $this->og_types, $css_class = 'og_type' ) . ' ' .
					sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $obj_label ) . '</p>' . "\n";
			}

			$table_rows[ 'og_type_for_ttn' ] = $form->get_tr_hide( 'basic', $type_keys ) .
				$form->get_th_html( _x( 'Type by Taxonomy', 'option label', 'wpsso' ), $css_class = '', $css_id = 'og_type_for_ttn' ) .
				'<td class="blank">' . $type_select . '</td>';

			return $table_rows;
		}

		/**
		 * Document Types > Schema tab.
		 */
		public function filter_doc_types_schema_types_rows( $table_rows, $form ) {

			$this->maybe_set_vars();

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			/**
			 * Schema Type.
			 */
			foreach ( array( 
				'home_page'    => _x( 'Type for Page Homepage', 'option label', 'wpsso' ),
				'home_posts'   => _x( 'Type for Posts Homepage', 'option label', 'wpsso' ),
				'user_page'    => _x( 'Type for User Profiles', 'option label', 'wpsso' ),
				'search_page'  => _x( 'Type for Search Results', 'option label', 'wpsso' ),
				'archive_page' => _x( 'Type for Other Archive', 'option label', 'wpsso' ),
			) as $type_name => $th_label ) {

				$opt_key = 'schema_type_for_' . $type_name;	// Hard-coded value - no sanitation required.

				$table_rows[ $opt_key ] = $form->get_tr_hide( 'basic', $opt_key ) . 
					$form->get_th_html( $th_label, $css_class = '', $opt_key ) . 
					'<td class="blank">' . $form->get_no_select( $opt_key, $this->schema_types, $css_class = 'schema_type', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
							$event_args = array(
								'json_var'  => 'schema_types',
								'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
								'is_transl' => true,					// No label translation required.
								'is_sorted' => true,					// No label sorting required.
							)
						) .
					'</td>';
			}

			/**
			 * Schema Type by Post Type.
			 */
			$type_select = '';
			$post_types  = SucomUtilWP::get_post_types( $output = 'objects' );

			foreach ( $post_types as $obj ) {

				$opt_key = SucomUtil::sanitize_hookname( 'schema_type_for_' . $obj->name );

				$obj_label = SucomUtilWP::get_object_label( $obj );

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $this->schema_types, $css_class = 'schema_type', $css_id = '',
					$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
						$event_args = array(
							'json_var'  => 'schema_types',
							'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
							'is_transl' => true,					// No label translation required.
							'is_sorted' => true,					// No label sorting required.
						)
					) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $obj_label ) . '</p>' . "\n";
			}

			$opt_key = 'schema_type_for_post_archive';	// Hard-coded value - no sanitation required.

			$type_select .= '<p>' . $form->get_no_select( $opt_key, $this->schema_types, $css_class = 'schema_type', $css_id = '',
				$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
					$event_args = array(
						'json_var'  => 'schema_types',
						'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
						'is_transl' => true,					// No label translation required.
						'is_sorted' => true,					// No label sorting required.
					)
				) . ' ' .
				sprintf( _x( 'for %s', 'option comment', 'wpsso' ), _x( 'Post Type Archive Page', 'option comment', 'wpsso' ) ) .
				'</p>' . "\n";

			$table_rows[ 'schema_type_for_ptn' ] = '' .
				$form->get_th_html( _x( 'Type by Post Type', 'option label', 'wpsso' ), $css_class = '', $css_id = 'schema_type_for_ptn' ) .
				'<td class="blank">' . $type_select . '</td>';

			/**
			 * Schema Type by Taxonomy.
			 */
			$type_select = '';
			$type_keys   = array();
			$taxonomies  = SucomUtilWP::get_taxonomies( $output = 'objects' );

			foreach ( $taxonomies as $obj ) {

				$type_keys[] = $opt_key = SucomUtil::sanitize_hookname( 'schema_type_for_tax_' . $obj->name );

				$obj_label = SucomUtilWP::get_object_label( $obj );

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $this->schema_types, $css_class = 'schema_type', $css_id = '',
					$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
						$event_args = array(
							'json_var'  => 'schema_types',
							'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
							'is_transl' => true,					// No label translation required.
							'is_sorted' => true,					// No label sorting required.
						)
					) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $obj_label ) . '</p>' . "\n";
			}

			$table_rows[ 'schema_type_for_ttn' ] = $form->get_tr_hide( 'basic', $type_keys ) .
				$form->get_th_html( _x( 'Type by Taxonomy', 'option label', 'wpsso' ), $css_id = '', $css_class = 'schema_type_for_ttn' ) .
				'<td class="blank">' . $type_select . '</td>';

			return $table_rows;
		}

		public function filter_def_schema_creative_work_rows( $table_rows, $form ) {

			$this->maybe_set_vars();

			$form_rows = array(
				'wpssojson_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_family_friendly' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Family Friendly', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_family_friendly',
					'content'  => $form->get_no_select_none( 'schema_def_family_friendly',
						$this->p->cf[ 'form' ][ 'yes_no' ], $css_class = 'yes-no', $css_id = '', $is_assoc = true ),
				),
				'schema_def_pub_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Publisher Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_pub_org_id',
					'content'  => $form->get_no_select( 'schema_def_pub_org_id', $this->org_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $this->org_req_msg,
				),
				'schema_def_pub_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Publisher Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_pub_person_id',
					'content'  => $form->get_no_select( 'schema_def_pub_person_id', $this->person_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_def_prov_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Service Prov. Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_prov_org_id',
					'content'  => $form->get_no_select( 'schema_def_prov_org_id', $this->org_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $this->org_req_msg,
				),
				'schema_def_prov_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Service Prov. Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_prov_person_id',
					'content'  => $form->get_no_select( 'schema_def_prov_person_id', $this->person_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}


		public function filter_def_schema_event_rows( $table_rows, $form ) {

			$this->maybe_set_vars();

			$form_rows = array(
				'wpssojson_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_event_location_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Physical Venue', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_location_id',
					'content'  => $form->get_no_select( 'schema_def_event_location_id', $this->place_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $this->plm_req_msg,
				),
				'schema_def_event_organizer_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Organizer Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_organizer_org_id',
					'content'  => $form->get_no_select( 'schema_def_event_organizer_org_id', $this->org_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $this->org_req_msg,
				),
				'schema_def_event_organizer_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Organizer Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_organizer_person_id',
					'content'  => $form->get_no_select( 'schema_def_event_organizer_person_id', $this->person_names,
						$css_class = 'long_name' ),
				),
				'schema_def_event_performer_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Performer Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_performer_org_id',
					'content'  => $form->get_no_select( 'schema_def_event_performer_org_id', $this->org_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $this->org_req_msg,
				),
				'schema_def_event_performer_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Performer Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_performer_person_id',
					'content'  => $form->get_no_select( 'schema_def_event_performer_person_id', $this->person_names,
						$css_class = 'long_name' ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_def_schema_job_posting_rows( $table_rows, $form ) {

			$this->maybe_set_vars();

			$form_rows = array(
				'wpssojson_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_job_hiring_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Hiring Organization', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_job_hiring_org_id',
					'content'  => $form->get_no_select( 'schema_def_job_hiring_org_id', $this->org_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $this->org_req_msg,
				),
				'schema_def_job_location_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Job Location', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_job_location_id',
					'content'  => $form->get_no_select( 'schema_def_job_location_id', $this->place_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $this->plm_req_msg,
				),
				'schema_def_job_location_type' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Job Location Type', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_job_location_type',
					'content'  => $form->get_no_select( 'schema_def_job_location_type', $this->p->cf[ 'form' ][ 'job_location_type' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_def_schema_review_rows( $table_rows, $form ) {

			$this->maybe_set_vars();

			$form_rows = array(
				'wpssojson_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_review_item_type' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Subject Webpage Type', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_review_item_type',
					'content'  => $form->get_no_select( 'schema_def_review_item_type',
						$this->schema_types, $css_class = 'schema_type' ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		/**
		 * Contact Fields > Custom Contacts tab.
		 */
		public function filter_cm_custom_contacts_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[] = '<th></th>' .
				$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ), $css_class = 'checkbox left', 'custom-cm-show-checkbox' ) . 
				$form->get_th_html( _x( 'Contact Field ID', 'column title', 'wpsso' ), $css_class = 'medium left', 'custom-cm-field-id' ) . 
				$form->get_th_html_locale( _x( 'Contact Field Label', 'column title', 'wpsso' ), $css_class = 'wide left', 'custom-cm-field-label' );

			foreach ( $this->p->cf[ 'opt' ][ 'cm_prefix' ] as $cm_id => $opt_pre ) {

				$cm_enabled_key = 'plugin_cm_' . $opt_pre . '_enabled';
				$cm_name_key    = 'plugin_cm_' . $opt_pre . '_name';
				$cm_label_key   = 'plugin_cm_' . $opt_pre . '_label';

				if ( isset( $form->options[ $cm_enabled_key ] ) ) {

					$table_rows[] = '' .
						$form->get_th_html( ucfirst( $cm_id ) ) .
						$form->get_no_td_checkbox( $cm_enabled_key, $comment = '', $extra_css_class = 'checkbox' ) . 
						'<td class="blank medium">' . $form->get_no_input( $cm_name_key, $css_class = 'medium' ) . '</td>' . 
						'<td class="blank wide">' . $form->get_no_input_locale( $cm_label_key ) . '</td>';
				}
			}

			return $table_rows;
		}

		/**
		 * Contact Fields > Default Contacts tab.
		 */
		public function filter_cm_default_contacts_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[] = '<th></th>' .
				$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ),
					$css_class = 'checkbox left', 'custom-cm-show-checkbox' ) . 
				$form->get_th_html( _x( 'Contact Field ID', 'column title', 'wpsso' ),
					$css_class = 'medium left', 'wp-cm-field-id' ) . 
				$form->get_th_html_locale( _x( 'Contact Field Label', 'column title', 'wpsso' ),
					$css_class = 'wide left', 'custom-cm-field-label' );

			$sorted_cm_names = $this->p->cf[ 'wp' ][ 'cm_names' ];

			ksort( $sorted_cm_names );

			foreach ( $sorted_cm_names as $cm_id => $opt_label ) {

				$cm_enabled_key = 'wp_cm_' . $cm_id . '_enabled';
				$cm_name_key    = 'wp_cm_' . $cm_id . '_name';
				$cm_label_key   = 'wp_cm_' . $cm_id . '_label';

				/**
				 * Not all social websites have a contact method field.
				 */
				if ( ! isset( $form->options[ $cm_enabled_key ] ) ) {

					continue;
				}

				$table_rows[] = '' .
					$form->get_th_html( $opt_label ) . 
					$form->get_no_td_checkbox( $cm_enabled_key, $comment = '', $extra_css_class = 'checkbox' ) . 
					'<td class="medium">' . $form->get_no_input( $cm_name_key, $css_class = 'medium' ) . '</td>' . 
					'<td class="blank wide">' . $form->get_no_input_locale( $cm_label_key ) . '</td>';
			}

			return $table_rows;
		}

		/**
		 * About the User metabox.
		 */
		public function filter_advanced_user_about_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="3">' . $this->p->msgs->get( 'info-user-about' ) . '</td>';

			$table_rows[] = '<td colspan="3">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[] = '<th></th>' .
				$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ),
					$css_class = 'checkbox left', $css_id = 'user-about-show-checkbox' ) .
				'<td class="wide"></td>';

			foreach ( $this->p->cf[ 'opt' ][ 'user_about' ] as $key => $opt_label ) {

				$opt_key = 'plugin_user_about_' . $key;

				$table_rows[ $opt_key ] = '' .
					$form->get_th_html( _x( $opt_label, 'option label', 'wpsso' ), '', $opt_key ) . 
					$form->get_no_td_checkbox( $opt_key, $comment = '', $extra_css_class = 'checkbox' );
			}

			return $table_rows;
		}

		/**
		 * Metadata > Product Attributes tab.
		 */
		public function filter_metadata_product_attrs_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-product-attrs' ) . '</td>';

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			foreach ( $this->p->cf[ 'form' ][ 'attr_labels' ] as $opt_key => $opt_label ) {

				$cmt_transl = WpssoAdmin::get_option_unit_comment( $opt_key );

				$table_rows[ $opt_key ] = '' .
					$form->get_th_html( _x( $opt_label, 'option label', 'wpsso' ), '', $opt_key ) . 
					'<td class="blank">' . $form->get_no_input( $opt_key ) . $cmt_transl . '</td>';
			}

			return $table_rows;
		}

		/**
		 * Metadata > Custom Fields tab.
		 */
		public function filter_metadata_custom_fields_rows( $table_rows, $form ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-custom-fields' ) . '</td>';

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			/**
			 * Example config:
			 *
			 * 	$cf_md_index = array(
			 *		'plugin_cf_addl_type_urls'           => 'schema_addl_type_url',
			 *		'plugin_cf_howto_steps'              => 'schema_howto_step',
			 *		'plugin_cf_howto_supplies'           => 'schema_howto_supply',
			 *		'plugin_cf_howto_tools'              => 'schema_howto_tool',
			 *		'plugin_cf_img_url'                  => 'og_img_url',
			 *		'plugin_cf_product_avail'            => 'product_avail',
			 *		'plugin_cf_product_brand'            => 'product_brand',
			 *		'plugin_cf_product_color'            => 'product_color',
			 *		'plugin_cf_product_condition'        => 'product_condition',
			 *		'plugin_cf_product_currency'         => 'product_currency',
			 *		'plugin_cf_product_material'         => 'product_material',
			 *		'plugin_cf_product_mfr_part_no'      => 'product_mfr_part_no',		// Product MPN.
			 *		'plugin_cf_product_price'            => 'product_price',
			 *		'plugin_cf_product_retailer_part_no' => 'product_retailer_part_no',	// Product SKU.
			 *		'plugin_cf_product_size'             => 'product_size',
			 *		'plugin_cf_product_target_gender'    => 'product_target_gender',
			 *		'plugin_cf_recipe_ingredients'       => 'schema_recipe_ingredient',
			 *		'plugin_cf_recipe_instructions'      => 'schema_recipe_instruction',
			 *		'plugin_cf_sameas_urls'              => 'schema_sameas_url',
			 *		'plugin_cf_vid_embed'                => 'og_vid_embed',
			 *		'plugin_cf_vid_url'                  => 'og_vid_url',
			 * 	);
			 *
			 * Hooked by WpssoProRecipeWpRecipeMaker to clear the 'plugin_cf_recipe_ingredients' and 'plugin_cf_recipe_instructions' values.
			 */
			$cf_md_index = (array) apply_filters( 'wpsso_cf_md_index', $this->p->cf[ 'opt' ][ 'cf_md_index' ] );

			$opt_labels = array();

			foreach ( $cf_md_index as $opt_key => $md_key ) {

				/**
				 * Make sure we have a label for the custom field option.
				 */
				if ( ! empty( $this->p->cf[ 'form' ][ 'cf_labels' ][ $opt_key ] ) ) {

					$opt_labels[ $opt_key ] = $this->p->cf[ 'form' ][ 'cf_labels' ][ $opt_key ];
				}
			}

			asort( $opt_labels );

			foreach ( $opt_labels as $opt_key => $opt_label ) {

				/**
				 * If we don't have a meta data key, then clear the custom field name (just in case) and disable
				 * the option.
				 */
				if ( empty( $cf_md_index[ $opt_key ] ) ) {

					$form->options[ $opt_key ] = '';

					$always_disabled = true;

				} else {
					$always_disabled = false;
				}

				$cmt_transl = WpssoAdmin::get_option_unit_comment( $opt_key );

				$table_rows[ $opt_key ] = '' .
					$form->get_th_html( _x( $opt_label, 'option label', 'wpsso' ), '', $opt_key ) . 
					'<td class="blank">' . $form->get_no_input( $opt_key, $css_class = '', $css_id = '',
						$max_len = 0, $holder = '', $always_disabled ) . $cmt_transl . '</td>';
			}
			return $table_rows;
		}

		/**
		 * HTML Tags > Facebook tab.
		 */
		public function filter_head_tags_facebook_rows( $table_rows, $form, $network = false ) {

			return $this->get_head_tags_rows( $table_rows, $form, $network, array( '/^add_(meta)_(property)_((fb|al):.+)$/' ) );
		}

		/**
		 * HTML Tags > Open Graph tab.
		 */
		public function filter_head_tags_open_graph_rows( $table_rows, $form, $network = false ) {

			return $this->get_head_tags_rows( $table_rows, $form, $network, array( '/^add_(meta)_(property)_(.+)$/' ) );
		}

		/**
		 * HTML Tags > Twitter tab.
		 */
		public function filter_head_tags_twitter_rows( $table_rows, $form, $network = false ) {

			return $this->get_head_tags_rows( $table_rows, $form, $network, array( '/^add_(meta)_(name)_(twitter:.+)$/' ) );
		}

		/**
		 * HTML Tags > SEO / Other tab.
		 */
		public function filter_head_tags_seo_other_rows( $table_rows, $form, $network = false ) {

			if ( ! empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

				$table_rows[] = '<td colspan="8"><blockquote class="top-info"><p>' . 
					__( 'An SEO plugin has been detected - some basic SEO meta tags have been unchecked and disabled automatically.', 'wpsso' ) . 
						'</p></blockquote></td>';
			}

			return $this->get_head_tags_rows( $table_rows, $form, $network, array( '/^add_(link)_([^_]+)_(.+)$/', '/^add_(meta)_(name)_(.+)$/' ) );
		}

		private function get_head_tags_rows( $table_rows, $form, $network, array $opt_preg_include ) {

			$table_cells = array();

			foreach ( $opt_preg_include as $preg ) {

				foreach ( $form->defaults as $opt_key => $opt_val ) {

					if ( strpos( $opt_key, 'add_' ) !== 0 ) {	// Optimize

						continue;

					} elseif ( ! empty( $this->html_tag_shown[ $opt_key ] ) ) {	// Check cache for HTML tags already shown.

						continue;

					} elseif ( ! preg_match( $preg, $opt_key, $match ) ) {	// Check option name for a match.

						continue;
					}

					$highlight = '';
					$css_class = '';
					$css_id    = '';
					$force     = null;
					$group     = null;

					$this->html_tag_shown[ $opt_key ] = true;

					switch ( $opt_key ) {

						case 'add_meta_name_generator':	// Disabled with a constant instead.

							continue 2;

						case 'add_link_rel_shortlink':

							$group = 'add_link_rel_shortlink';

							break;
					}

					$table_cells[] = '<!-- ' . ( implode( ' ', $match ) ) . ' -->' . 	// Required for sorting.
						'<td class="checkbox blank">' . $form->get_no_checkbox( $opt_key, $css_class, $css_id, $force, $group ) . '</td>' . 
						'<td class="xshort' . $highlight . '">' . $match[1] . '</td>' . 
						'<td class="head_tags' . $highlight . '">' . $match[2] . '</td>' . 
						'<th class="head_tags' . $highlight . '">' . $match[3] . '</th>';
				}
			}

			return array_merge( $table_rows, SucomUtil::get_column_rows( $table_cells, 2 ) );
		}

		/**
		 * WordPress Sitemaps metabox.
		 */
		public function filter_advanced_wp_sitemaps_rows( $table_rows, $form ) {

			if ( ! SucomUtilWP::sitemaps_enabled() ) {	// Nothing to do.

				return $table_rows;
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_sitemaps_for' ] = '' .
				$form->get_th_html( _x( 'Include in Sitemaps', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_sitemaps_for' ) .
				'<td class="blank">' . $form->get_no_checklist_post_tax_user( $name_prefix = 'plugin_sitemaps_for' ) . '</td>';

			return $table_rows;
		}
	}
}
