<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdAdminAdvanced' ) ) {

	class WpssoStdAdminAdvanced {

		private $p;	// Wpsso class object.

		private $html_tag_shown = array();	// Cache for HTML tags already shown.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			$this->p->util->add_plugin_filters( $this, array(
				'mb_advanced_plugin_integration_rows' => array(		// Plugin Settings > Integration tab.
					'mb_advanced_plugin_integration_rows'      => 3,
					'mb_site_advanced_plugin_integration_rows' => 3,
				),
				'mb_advanced_plugin_default_text_rows'        => 3,	// Plugin Settings > Default Text tab.
				'mb_advanced_plugin_image_sizes_rows'         => 3,	// Plugin Settings > Image Sizes tab.
				'mb_advanced_plugin_interface_rows'           => 3,	// Plugin Settings > Interface tab.
				'mb_advanced_services_media_rows'             => 3,	// Service APIs > Media Services tab.
				'mb_advanced_services_shortening_rows'        => 3,	// Service APIs > Shortening Services tab.
				'mb_advanced_services_ratings_reviews_rows'   => 3,	// Service APIs > Ratings and Reviews tab.
				'mb_advanced_doc_types_og_types_rows'         => 3,	// Document Types > Open Graph tab.
				'mb_advanced_doc_types_schema_types_rows'     => 3,	// Document Types > Schema tab.
				'mb_advanced_schema_defs_article_rows'        => 3,	// Schema Defaults > Article tab.
				'mb_advanced_schema_defs_book_rows'           => 3,	// Schema Defaults > Book tab.
				'mb_advanced_schema_defs_creative_work_rows'  => 3,	// Schema Defaults > Creative Work tab.
				'mb_advanced_schema_defs_event_rows'          => 3,	// Schema Defaults > Event tab.
				'mb_advanced_schema_defs_job_posting_rows'    => 3,	// Schema Defaults > Job Posting tab.
				'mb_advanced_schema_defs_place_rows'          => 3,	// Schema Defaults > Place tab.
				'mb_advanced_schema_defs_product_rows'        => 3,	// Schema Defaults > Product tab.
				'mb_advanced_schema_defs_profile_page_rows'   => 3,	// Schema Defaults > Profile Page tab.
				'mb_advanced_schema_defs_review_rows'         => 3,	// Schema Defaults > Review tab.
				'mb_advanced_schema_defs_service_rows'        => 3,	// Schema Defaults > Service tab.
				'mb_advanced_contact_fields_default_cm_rows'  => 3,	// Contact Fields > Default Contacts tab.
				'mb_advanced_contact_fields_custom_cm_rows'   => 3,	// Contact Fields > Custom Contacts tab.
				'mb_advanced_user_about_rows'                 => 3,	// About the User metabox.
				'mb_advanced_metadata_product_attrs_rows'     => 3,	// Attributes and Metadata > Product Attributes tab.
				'mb_advanced_metadata_custom_fields_rows'     => 3,	// Attributes and Metadata > Custom Fields tab.
				'mb_advanced_head_tags_facebook_rows'         => 3,	// HTML Tags > Facebook tab.
				'mb_advanced_head_tags_open_graph_rows'       => 3,	// HTML Tags > Open Graph tab.
				'mb_advanced_head_tags_twitter_rows'          => 3,	// HTML Tags > X (Twitter) tab.
				'mb_advanced_head_tags_seo_other_rows'        => 3,	// HTML Tags > SEO / Other tab.
			) );
		}

		/*
		 * Plugin Settings > Integration tab.
		 */
		public function filter_mb_advanced_plugin_integration_rows( $table_rows, $form, $args ) {

			/*
			 * WpssoMessages->maybe_doc_title_disabled() returns a message if:
			 *
			 *	- An SEO plugin is active.
			 *	- The theme does not support the 'title-tag' feature.
			 *	- The WPSSO_TITLE_TAG_DISABLE constant is true.
			 */
			$doc_title_msg      = $this->p->msgs->maybe_doc_title_disabled();
			$doc_title_disabled = $doc_title_msg ? true : false;
			$doc_title_source   = $this->p->cf[ 'form' ][ 'document_title' ];

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_title_tag' ] = '' .
				$form->get_th_html( _x( 'Webpage Title Tag', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_title_tag' ) .
				'<td class="blank">' . $form->get_no_select( 'plugin_title_tag', $doc_title_source,
					$css_class = 'long_name', $css_id = '', $is_assoc = true ) .
				$doc_title_msg . '</td>' .
				WpssoAdmin::get_option_site_use( 'plugin_title_tag', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_filter_content' ] = '' .
				$form->get_th_html( _x( 'Use Filtered Content', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_filter_content' ) .
				$form->get_no_td_checkbox( 'plugin_filter_content', _x( '(recommended after reading help text)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_filter_content', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_filter_excerpt' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_filter_excerpt' ) .
				$form->get_th_html( _x( 'Use Filtered Excerpt', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_filter_excerpt' ) .
				$form->get_no_td_checkbox( 'plugin_filter_excerpt', _x( '(recommended - only if using shortcodes in excerpts)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_filter_excerpt', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_page_excerpt' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_page_excerpt' ) .
				$form->get_th_html( _x( 'Enable Excerpt for Pages', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_page_excerpt' ) .
				$form->get_no_td_checkbox( 'plugin_page_excerpt' ) .
				WpssoAdmin::get_option_site_use( 'plugin_page_excerpt', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_page_tags' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_page_tags' ) .
				$form->get_th_html( _x( 'Enable Tags for Pages', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_page_tags' ) .
				$form->get_no_td_checkbox( 'plugin_page_tags' ) .
				WpssoAdmin::get_option_site_use( 'plugin_page_tags', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_new_user_is_person' ] = '' .
				$form->get_th_html( _x( 'Add Person Role for New Users', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_new_user_is_person' ) .
				$form->get_no_td_checkbox( 'plugin_new_user_is_person' ) .
				WpssoAdmin::get_option_site_use( 'plugin_new_user_is_person', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_inherit_featured' ] = '' .
				$form->get_th_html( _x( 'Inherit Featured Image', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_inherit_featured' ) .
				$form->get_no_td_checkbox( 'plugin_inherit_featured', _x( '(recommended)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_inherit_featured', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_inherit_images' ] = '' .
				$form->get_th_html( _x( 'Inherit Custom Images', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_inherit_images' ) .
				$form->get_no_td_checkbox( 'plugin_inherit_images', _x( '(recommended)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_inherit_featured', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_attached_images' ] = '' .
				$form->get_th_html( _x( 'Consider Attached Images', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_attached_images' ) .
				$form->get_no_td_checkbox( 'plugin_attached_images', _x( '(recommended for WooCommerce product gallery images)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_attached_images', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_content_images' ] = '' .
				$form->get_th_html( _x( 'Consider Content Images', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_content_images' ) .
				$form->get_no_td_checkbox( 'plugin_content_images' ) .
				WpssoAdmin::get_option_site_use( 'plugin_content_images', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_check_img_dims' ] = '' .
				$form->get_th_html( _x( 'Image Dimension Checks', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_check_img_dims' ) .
				$form->get_no_td_checkbox( 'plugin_check_img_dims', _x( '(recommended)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_check_img_dims', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_prevent_thumb_conflicts' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_prevent_thumb_conflicts' ) .
				$form->get_th_html( _x( 'Prevent Thumbnail Conflicts', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_prevent_thumb_conflicts' ) .
				$form->get_no_td_checkbox( 'plugin_prevent_thumb_conflicts', _x( '(recommended)', 'option comment', 'wpsso' ) ) .
				WpssoAdmin::get_option_site_use( 'plugin_prevent_thumb_conflicts', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_upscale_images' ] = '' .
				$form->get_th_html( _x( 'Upscale Media Library Images', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_upscale_images' ) .
				$form->get_no_td_checkbox( 'plugin_upscale_images' ) .
				WpssoAdmin::get_option_site_use( 'plugin_upscale_images', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_upscale_pct_max' ] = '' .
				$form->get_th_html( _x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_upscale_pct_max' ) .
				'<td class="blank">' . $form->get_no_input( 'plugin_upscale_pct_max', $css_class = 'short' ) . ' %</td>' .
				WpssoAdmin::get_option_site_use( 'plugin_upscale_pct_max', $form, $args[ 'network' ] );

			/*
			 * Plugin and theme integration options.
			 */
			$table_rows[ 'subsection_plugin_theme_integration' ] = '' .
				'<td colspan="4" class="subsection"><h4>' . _x( 'Plugin and Theme Integration', 'metabox title', 'wpsso' ) . '</h4></td>';

			$table_rows[ 'plugin_speakable_css_csv' ] = '' .
				$form->get_th_html( _x( 'Speakable CSS Selectors', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_speakable_css_csv' ) .
				'<td class="blank">' . $form->get_no_input( 'plugin_speakable_css_csv', $css_class = 'wide' ) .
				WpssoAdmin::get_option_site_use( 'plugin_speakable_css_csv', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_check_head' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_check_head' ) .
				$form->get_th_html( _x( 'Check for Duplicate Meta Tags', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_check_head' ) .
				$form->get_no_td_checkbox( 'plugin_check_head' ) .
				WpssoAdmin::get_option_site_use( 'plugin_check_head', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_product_include_vat' ] = '' .
				$form->get_th_html( _x( 'Include VAT in Product Prices', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_product_include_vat' ) .
				$form->get_no_td_checkbox( 'plugin_product_include_vat' ) .
				WpssoAdmin::get_option_site_use( 'plugin_product_include_vat', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_import_aioseop_meta' ] = '' .
				$form->get_th_html( _x( 'Import All in One SEO Pack Metadata', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_import_aioseop_meta' ) .
				$form->get_no_td_checkbox( 'plugin_import_aioseop_meta' ) .
				WpssoAdmin::get_option_site_use( 'plugin_import_aioseop_meta', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_import_rankmath_meta' ] = '' .
				$form->get_th_html( _x( 'Import Rank Math SEO Metadata', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_import_rankmath_meta' ) .
				$form->get_no_td_checkbox( 'plugin_import_rankmath_meta' ) .
				WpssoAdmin::get_option_site_use( 'plugin_import_rankmath_meta', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_import_seoframework_meta' ] = '' .
				$form->get_th_html( _x( 'Import The SEO Framework Metadata', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_import_seoframework_meta' ) .
				$form->get_no_td_checkbox( 'plugin_import_seoframework_meta' ) .
				WpssoAdmin::get_option_site_use( 'plugin_import_seoframework_meta', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_import_wpmetaseo_meta' ] = '' .
				$form->get_th_html( _x( 'Import WP Meta SEO Metadata', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_import_wpmetaseo_meta' ) .
				$form->get_no_td_checkbox( 'plugin_import_wpmetaseo_meta' ) .
				WpssoAdmin::get_option_site_use( 'plugin_import_wpmetaseo_meta', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_import_wpseo_meta' ] = '' .
				$form->get_th_html( _x( 'Import Yoast SEO Metadata', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_import_wpseo_meta' ) .
				$form->get_no_td_checkbox( 'plugin_import_wpseo_meta' ) .
				WpssoAdmin::get_option_site_use( 'plugin_import_wpseo_meta', $form, $args[ 'network' ] );

			$table_rows[ 'plugin_import_wpseo_blocks' ] = '' .
				$form->get_th_html( _x( 'Import Yoast SEO Block Attrs', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_import_wpseo_blocks' ) .
				$form->get_no_td_checkbox( 'plugin_import_wpseo_blocks' ) .
				WpssoAdmin::get_option_site_use( 'plugin_import_wpseo_blocks', $form, $args[ 'network' ] );

			return $table_rows;
		}

		/*
		 * Plugin Settings > Default Text tab.
		 */
		public function filter_mb_advanced_plugin_default_text_rows( $table_rows, $form, $args ) {

			/*
			 * WpssoMessages->maybe_doc_title_disabled() returns a message if:
			 *
			 *	- An SEO plugin is active.
			 *	- The theme does not support the 'title-tag' feature.
			 *	- The WPSSO_TITLE_TAG_DISABLE constant is true.
			 */
			$doc_title_msg      = $this->p->msgs->maybe_doc_title_disabled();
			$doc_title_disabled = $doc_title_msg ? true : false;

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_title_part_site' ] = '' .
				$form->get_th_html_locale( _x( 'Title Tag Site Suffix', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_title_part_site' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_title_part_site', $css_class = 'long_name' ) .
				$doc_title_msg . '</td>';

			$table_rows[ 'plugin_title_part_tagline' ] = '' .
				$form->get_th_html_locale( _x( 'Title Tag Tagline Suffix', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_title_part_tagline' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_title_part_tagline', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_img_alt_prefix' ] = '' .
				$form->get_th_html_locale( _x( 'Content Image Alt Prefix', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_img_alt_prefix' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_img_alt_prefix' ) . '</td>';

			$table_rows[ 'plugin_p_cap_prefix' ] = '' .
				$form->get_th_html_locale( _x( 'WP Caption Text Prefix', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_p_cap_prefix' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_p_cap_prefix' ) . '</td>';

			$table_rows[ 'plugin_comment_title' ] = '' .
				$form->get_th_html_locale( _x( 'Comment Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_comment_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_comment_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_comment_reply_title' ] = '' .
				$form->get_th_html_locale( _x( 'Reply Comment Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_comment_reply_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_comment_reply_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_comment_review_title' ] = '' .
				$form->get_th_html_locale( _x( 'Review Comment Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_comment_review_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_comment_review_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_product_var_title' ] = '' .
				$form->get_th_html_locale( _x( 'Product Variation Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_product_var_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_product_var_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_feed_title' ] = '' .
				$form->get_th_html_locale( _x( 'RSS Feed Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_feed_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_feed_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_404_page_title' ] = '' .
				$form->get_th_html_locale( _x( '404 Page Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_404_page_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_404_page_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_404_page_desc' ] = '' .
				$form->get_th_html_locale( _x( '404 Page Description', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_404_page_desc' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_404_page_desc', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_no_title_text' ] = '' .
				$form->get_th_html_locale( _x( 'No Title Text', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_no_title_text' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_no_title_text', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_no_desc_text' ] = '' .
				$form->get_th_html_locale( _x( 'No Description Text', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_no_desc_text' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_no_desc_text', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'subsection_archive_pages' ] = '' .
				'<td colspan="2" class="subsection"><h4>' . _x( 'Archive Pages', 'metabox title', 'wpsso' ) . '</h4></td>';

			$table_rows[ 'plugin_term_page_title' ] = '' .
				$form->get_th_html_locale( _x( 'Term Archive Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_term_page_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_term_page_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_term_page_desc' ] = '' .
				$form->get_th_html_locale( _x( 'Term Archive Description', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_term_page_desc' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_term_page_desc', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_author_page_title' ] = '' .
				$form->get_th_html_locale( _x( 'Author Archive Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_author_page_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_author_page_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_author_page_desc' ] = '' .
				$form->get_th_html_locale( _x( 'Author Archive Description', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_author_page_desc' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_author_page_desc', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_search_page_title' ] = '' .
				$form->get_th_html_locale( _x( 'Search Results Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_search_page_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_search_page_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_search_page_desc' ] = '' .
				$form->get_th_html_locale( _x( 'Search Results Description', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_search_page_desc' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_search_page_desc', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_year_page_title' ] = '' .
				$form->get_th_html_locale( _x( 'Year Archive Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_year_page_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_year_page_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_year_page_desc' ] = '' .
				$form->get_th_html_locale( _x( 'Year Archive Description', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_year_page_desc' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_year_page_desc', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_month_page_title' ] = '' .
				$form->get_th_html_locale( _x( 'Month Archive Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_month_page_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_month_page_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_month_page_desc' ] = '' .
				$form->get_th_html_locale( _x( 'Month Archive Description', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_month_page_desc' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_month_page_desc', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_day_page_title' ] = '' .
				$form->get_th_html_locale( _x( 'Day Archive Title', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_day_page_title' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_day_page_title', $css_class = 'wide' ) . '</td>';

			$table_rows[ 'plugin_day_page_desc' ] = '' .
				$form->get_th_html_locale( _x( 'Day Archive Description', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_day_page_desc' ) .
				'<td class="blank">' . $form->get_no_input_locale( 'plugin_day_page_desc', $css_class = 'wide' ) . '</td>';

			$post_type_archives = SucomUtilWP::get_post_type_archives( $output = 'objects', $sort = true );

			if ( ! empty( $post_type_archives ) ) {

				$table_rows[ 'subsection_post_type_archive_pages' ] = '' .
					'<td colspan="2" class="subsection"><h4>' . _x( 'Post Type Archive Pages', 'metabox title', 'wpsso' ) . '</h4></td>';

				foreach ( $post_type_archives as $num => $post_type_obj ) {

					if ( ! is_object( $post_type_obj ) ) continue;	// Just in case.

					$obj_label = sprintf( _x( '%s Archive Page', 'metabox title', 'wpsso' ), SucomUtilWP::get_object_label( $post_type_obj ) );
					$title_key = 'plugin_pta_' . $post_type_obj->name . '_title';
					$desc_key  = 'plugin_pta_' . $post_type_obj->name . '_desc';

					$table_rows[ 'subsection_pta_' . $post_type_obj->name ] = '' .
						'<td colspan="2" class="subsection' . ( $num ? '' : ' top' ) . '"><h5>' . $obj_label . '</h4></td>';

					$def_title_text = empty( $post_type_obj->label ) ?
						$this->p->opt->get_text( 'plugin_no_title_text' ) : $post_type_obj->label;

					$def_desc_text  = empty( $post_type_obj->description ) ?	// The post type object may not have a description.
						$this->p->opt->get_text( 'plugin_no_desc_text' ) : $post_type_obj->description;

					$table_rows[ $title_key ] = '' .
						$form->get_th_html_locale( _x( 'Archive Page Title', 'option label', 'wpsso' ), $css_class = '', $title_key ) .
						'<td class="blank">' . $form->get_no_input_locale( $title_key, $css_class = 'wide', $css_id = '',
							$def_title_text ) . '</td>';

					$table_rows[ $desc_key ] = '' .
						$form->get_th_html_locale( _x( 'Archive Page Description', 'option label', 'wpsso' ), $css_class = '', $desc_key ) .
						'<td class="blank">' . $form->get_no_textarea_locale( $desc_key, $css_class = '', $css_id = '',
							$len = 0, $def_desc_text ) . '</td>';
				}
			}

			return $table_rows;
		}

		/*
		 * SSO > Advanced Settings > Plugin Settings > Image Sizes tab.
		 */
		public function filter_mb_advanced_plugin_image_sizes_rows( $table_rows, $form, $args ) {

			$pin_img_disabled = $this->p->util->is_pin_img_disabled();
			$pin_img_msg      = $this->p->msgs->maybe_pin_img_disabled( $extra_css_class = 'inline' );

			if ( $info_msg = $this->p->msgs->get( 'info-image_dimensions' ) ) {

				$table_rows[ 'info-image_dimensions' ] = '<td colspan="2">' . $info_msg . '</td>';
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'og_img_size' ] = '' .
				$form->get_th_html( _x( 'Open Graph (Facebook and oEmbed)', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'og_img_size' ) .
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'og_img' ) . '</td>';

			$table_rows[ 'pin_img_size' ] = ( $pin_img_disabled ? $form->get_tr_hide( $in_view = 'basic' ) : '' ) .
				$form->get_th_html( _x( 'Pinterest Pin It', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'pin_img_size' ) .
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'pin_img', $pin_img_disabled ) . $pin_img_msg . '</td>';

			$table_rows[ 'schema_01x01_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 1:1 (Google Rich Results)', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'schema_1x1_img_size' ) .
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_1x1_img' ) . '</td>';

			$table_rows[ 'schema_04x03_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 4:3 (Google Rich Results)', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'schema_4x3_img_size' ) .
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_4x3_img' ) . '</td>';

			$table_rows[ 'schema_16x09_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema 16:9 (Google Rich Results)', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'schema_16x9_img_size' ) .
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'schema_16x9_img' ) . '</td>';

			$table_rows[ 'schema_thumb_img_size' ] = '' .
				$form->get_th_html( _x( 'Schema Thumbnail', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'schema_thumb_img_size' ) .
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'thumb_img' ) . '</td>';

			$table_rows[ 'tc_00_sum_img_size' ] = '' .
				$form->get_th_html( _x( 'X (Twitter) Summary Card', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'tc_sum_img_size' ) .
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'tc_sum_img' ) . '</td>';

			$table_rows[ 'tc_01_lrg_img_size' ] = '' .
				$form->get_th_html( _x( 'X (Twitter) Summary Card Large Image', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'tc_lrg_img_size' ) .
				'<td class="blank">' . $form->get_no_input_image_dimensions( 'tc_lrg_img' ) . '</td>';

			return $table_rows;
		}

		/*
		 * Plugin Settings > Interface tab.
		 */
		public function filter_mb_advanced_plugin_interface_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_show_opts' ] = '' .
				$form->get_th_html( _x( 'Show Options by Default', 'option label', 'wpsso' ),
					$css_class = 'medium', $css_id = 'plugin_show_opts' ) .
				'<td class="blank">' . $form->get_no_select( 'plugin_show_opts', $this->p->cf[ 'form' ][ 'show_options' ] ) . '</td>';

			$table_rows[ 'plugin_og_types_select_format' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_og_types_select_format' ) .
				$form->get_th_html( _x( 'Open Graph Select', 'option label', 'wpsso' ),
					$css_class = 'medium', $css_id = 'plugin_og_types_select_format' ) .
				'<td class="blank">' . $form->get_no_select( 'plugin_og_types_select_format',
					$this->p->cf[ 'form' ][ 'og_schema_types_select_format' ] ) . '</td>';

			$table_rows[ 'plugin_schema_types_select_format' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_schema_types_select_format' ) .
				$form->get_th_html( _x( 'Schema Type Select', 'option label', 'wpsso' ),
					$css_class = 'medium', $css_id = 'plugin_schema_types_select_format' ) .
				'<td class="blank">' . $form->get_no_select( 'plugin_schema_types_select_format',
					$this->p->cf[ 'form' ][ 'og_schema_types_select_format' ] ) . '</td>';

			/*
			 * Show validators toolbar menu.
			 */
			$menu_title = _x( 'Validators', 'toolbar menu title', 'wpsso' );

			$table_rows[ 'plugin_add_toolbar_validate' ] = '' .
				$form->get_th_html( sprintf( _x( 'Show %s Toolbar', 'option label', 'wpsso' ), $menu_title ),	// Show Validators Toolbar.
					$css_class = 'medium', $css_id = 'plugin_add_toolbar_validate' ) .
				$form->get_no_td_checkbox( 'plugin_add_toolbar_validate' );

			/*
			 * Show SSO menu items.
			 */
			$menu_title = $this->p->admin->get_menu_title();
			$menu_args  = $this->p->admin->get_submenu_args( $menu_lib = 'submenu' );

			foreach ( $menu_args as $menu_id => $args ) {

				if ( ! isset( $form->defaults[ 'plugin_add_submenu_' . $menu_id ] ) )	// Just in case.
					$form->defaults[ 'plugin_add_submenu_' . $menu_id ] = 1;

				if ( ! isset( $form->options[ 'plugin_add_submenu_' . $menu_id ] ) )	// Just in case.
					$form->options[ 'plugin_add_submenu_' . $menu_id ] = 1;

				if ( empty( $this->p->cf[ 'menu' ][ 'must_load' ][ $menu_id ] ) )	// Exclude must-load menu items.
					$values[ $menu_id ] = $args[ 2 ];
			}

			$table_rows[ 'plugin_add_submenu' ] = $form->get_tr_hide_prefix( $in_view = 'basic', 'plugin_add_submenu_' ) .
				$form->get_th_html( sprintf( _x( 'Show %s Menu Items', 'option label', 'wpsso' ), $menu_title ),
					$css_class = 'medium', $css_id = 'plugin_add_submenu' ) .
				'<td class="blank">' . $form->get_no_checklist( $name_prefix = 'plugin_add_submenu', $values ) . '</td>';

			/*
			 * Show custom meta metaboxes.
			 */
			$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

			$table_rows[ 'plugin_add_to' ] = $form->get_tr_hide_prefix( $in_view = 'basic', 'plugin_add_to_' ) .
				$form->get_th_html( sprintf( _x( 'Show %s Metabox', 'option label', 'wpsso' ), $metabox_title ),
					$css_class = 'medium', $css_id = 'plugin_add_to' ) .
				'<td class="blank">' . $form->get_no_checklist_post_tax_user( $name_prefix = 'plugin_add_to' ) . '</td>';

			/*
			 * Additional item list columns.
			 */
			$col_headers = WpssoAbstractWpMeta::get_column_headers();

			$table_rows[ 'plugin_show_columns' ] = '' .
				$form->get_th_html( _x( 'WP List Table Columns', 'option label', 'wpsso' ),
					$css_class = 'medium', $css_id = 'plugin_show_columns' ) .
				'<td>' . $form->get_no_columns_post_tax_user( $name_prefix = 'plugin',
					$col_headers, $table_class = 'plugin_list_table_cols' ) . '</td>';

			return $table_rows;
		}

		/*
		 * Service APIs > Media Services tab.
		 */
		public function filter_mb_advanced_services_media_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_gravatar_image' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_gravatar_image' ) .
				$form->get_th_html( _x( 'Gravatar is Default Author Image', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_gravatar_image' ) .
				$form->get_no_td_checkbox( 'plugin_gravatar_image' );

			$table_rows[ 'plugin_gravatar_size' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_gravatar_size' ) .
				$form->get_th_html( _x( 'Gravatar Image Size', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_gravatar_size' ) .
				'<td class="blank">' . $form->get_no_input( 'plugin_gravatar_size', $css_class = 'short' ) . '</td>';

			$checkboxes = '';

			foreach ( $this->p->cf[ 'form' ][ 'embed_media' ] as $opt_key => $opt_label ) {

				$checkboxes .= '<p>' . $form->get_no_checkbox_comment( $opt_key ) . ' ' . _x( $opt_label, 'option value', 'wpsso' ) . '</p>';
			}

			$table_rows[ 'plugin_embed_media' ] = '' .
				$form->get_th_html( _x( 'Detect Embedded Media', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_embed_media' ).
				'<td class="blank">' . $checkboxes . '</td>';

			return $table_rows;
		}

		/*
		 * Service APIs > Shortening Services tab.
		 */
		public function filter_mb_advanced_services_shortening_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_shortener' ] = '' .
				$form->get_th_html( _x( 'URL Shortening Service', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_shortener' ) .
				'<td class="blank">' . $form->get_no_select_none( 'plugin_shortener' ) . '</td>';

			$table_rows[ 'plugin_min_shorten' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_min_shorten' ) .
				$form->get_th_html( _x( 'Minimum URL Length to Shorten', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_min_shorten' ) .
				'<td class="blank">' . $form->get_no_input( 'plugin_min_shorten', $css_class = 'short' ) . ' ' .
				_x( 'characters', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_wp_shortlink' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_wp_shortlink' ) .
				$form->get_th_html( _x( 'Use Short URL for WP Shortlink', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_wp_shortlink' ) .
				$form->get_no_td_checkbox( 'plugin_wp_shortlink' );

			$table_rows[ 'plugin_add_link_rel_shortlink' ] = $form->get_tr_hide( $in_view = 'basic', 'add_link_rel_shortlink' ) .
				$form->get_th_html( sprintf( _x( 'Add "%s" HTML Tag', 'option label', 'wpsso' ), 'link&nbsp;rel&nbsp;shortlink' ),
					$css_class = '', $css_id = 'plugin_add_link_rel_shortlink' ) .
				'<td class="blank">' . $form->get_no_checkbox( 'add_link_rel_shortlink', $css_class = '', $css_id = 'add_link_rel_shortlink_html_tag',
					$force = null, $group = 'add_link_rel_shortlink' ) . '</td>';	// Group with option in head tags list

			return $table_rows;
		}

		/*
		 * Service APIs > Ratings and Reviews tab.
		 */
		public function filter_mb_advanced_services_ratings_reviews_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$ratings_reviews = $this->p->cf[ 'form' ][ 'ratings_reviews' ];

			$table_rows[ 'plugin_ratings_reviews_svc' ] = '' .
				$form->get_th_html( _x( 'Ratings and Reviews Service', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_ratings_reviews_svc' ) .
				'<td class="blank">' . $form->get_no_select_none( 'plugin_ratings_reviews_svc' ) . '</td>';

			$table_rows[ 'plugin_ratings_reviews_num_max' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_ratings_reviews_num_max' ) .
				$form->get_th_html( _x( 'Maximum Number of Reviews', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_ratings_reviews_num_max' ) .
				'<td class="blank">' . $form->get_no_input( 'plugin_ratings_reviews_num_max', $css_class = 'short' ) . '</td>';

			$table_rows[ 'plugin_ratings_reviews_months_max' ] = $form->get_tr_hide( $in_view = 'basic', 'plugin_ratings_reviews_months_max' ) .
				$form->get_th_html( _x( 'Maximum Age of Reviews', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_ratings_reviews_months_max' ) .
				'<td class="blank">' . $form->get_no_input( 'plugin_ratings_reviews_months_max', $css_class = 'short' ) . ' ' .
				_x( 'months', 'option comment', 'wpsso' ) . '</td>';

			$table_rows[ 'plugin_ratings_reviews_for' ] = '' .
				$form->get_th_html( _x( 'Get Reviews for Post Types', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_ratings_reviews_for' ) .
				'<td class="blank">' . $form->get_no_checklist_post_types( $name_prefix = 'plugin_ratings_reviews_for' ) . '</td>';

			return $table_rows;
		}

		/*
		 * Document Types > Open Graph tab.
		 */
		public function filter_mb_advanced_doc_types_og_types_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			/*
			 * Open Graph Type.
			 */
			foreach ( array(
				'og_type_for_home_page'    => _x( 'Type for Page Homepage', 'option label', 'wpsso' ),
				'og_type_for_home_posts'   => _x( 'Type for Posts Homepage', 'option label', 'wpsso' ),
				'og_type_for_user_page'    => _x( 'Type for User Profiles', 'option label', 'wpsso' ),
				'og_type_for_search_page'  => _x( 'Type for Search Results', 'option label', 'wpsso' ),
				'og_type_for_archive_page' => _x( 'Type for Archive Page', 'option label', 'wpsso' ),
			) as $opt_key => $th_label ) {

				$table_rows[ $opt_key ] = $form->get_tr_hide( $in_view = 'basic', $opt_key ) .
					$form->get_th_html( $th_label, $css_class = '', $opt_key ) .
					'<td class="blank">' . $form->get_no_select( $opt_key, $args[ 'select' ][ 'og_types' ],
						$css_class = 'og_type' ) . '</td>';
			}

			/*
			 * Open Graph Type by Post Type.
			 *
			 * SucomUtilWP::get_post_type_labels() calls SucomUtilWP::get_post_types(), which returns post types
			 * registered as 'public' = true and 'show_ui' = true by default. Note that the 'wp_block' custom post type
			 * for reusable blocks is registered as 'public' = false and 'show_ui' = true.
			 */
			$type_select = '';
			$type_labels = SucomUtilWP::get_post_type_labels( $val_prefix = 'og_type_for_' );

			foreach ( $type_labels as $opt_key => $obj_label ) {

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $args[ 'select' ][ 'og_types' ],
					$css_class = 'og_type' ) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ),
						$obj_label ) . '</p>' . "\n";
			}

			$table_rows[ 'og_type_for_pt' ] = '' .
				$form->get_th_html( _x( 'Type by Post Type', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'og_type_for_pt' ) .
				'<td class="blank">' . $type_select . '</td>';

			/*
			 * Open Graph Type by Post Type Archive.
			 */
			$type_select = '';
			$type_keys   = array();
			$type_labels = SucomUtilWP::get_post_type_archive_labels( $val_prefix = 'og_type_for_pta_' );

			foreach ( $type_labels as $opt_key => $obj_label ) {

				$type_keys[] = $opt_key;

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $args[ 'select' ][ 'og_types' ],
					$css_class = 'og_type' ) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ),
						$obj_label ) . '</p>' . "\n";
			}

			if ( ! empty( $type_select ) ) {

				$table_rows[ 'og_type_for_pta' ] = $form->get_tr_hide( $in_view = 'basic', $type_keys ) .
					$form->get_th_html( _x( 'Type by Post Type Archive', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_type_for_pta' ) .
					'<td class="blank">' . $type_select . '</td>';
			}

			/*
			 * Open Graph Type by Taxonomy.
			 */
			$type_select = '';
			$type_keys   = array();
			$type_labels = SucomUtilWP::get_taxonomy_labels( $val_prefix = 'og_type_for_tax_' );

			foreach ( $type_labels as $opt_key => $obj_label ) {

				$type_keys[] = $opt_key;

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $args[ 'select' ][ 'og_types' ],
					$css_class = 'og_type' ) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ),
						$obj_label ) . '</p>' . "\n";
			}

			$table_rows[ 'og_type_for_tax' ] = $form->get_tr_hide( $in_view = 'basic', $type_keys ) .
				$form->get_th_html( _x( 'Type by Taxonomy', 'option label', 'wpsso' ), $css_class = '', $css_id = 'og_type_for_tax' ) .
				'<td class="blank">' . $type_select . '</td>';

			return $table_rows;
		}

		/*
		 * Document Types > Schema tab.
		 */
		public function filter_mb_advanced_doc_types_schema_types_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			/*
			 * Schema Type.
			 */
			foreach ( array(
				'schema_type_for_home_page'    => _x( 'Type for Page Homepage', 'option label', 'wpsso' ),
				'schema_type_for_home_posts'   => _x( 'Type for Posts Homepage', 'option label', 'wpsso' ),
				'schema_type_for_user_page'    => _x( 'Type for User Profiles', 'option label', 'wpsso' ),
				'schema_type_for_search_page'  => _x( 'Type for Search Results', 'option label', 'wpsso' ),
				'schema_type_for_archive_page' => _x( 'Type for Archive Page', 'option label', 'wpsso' ),
			) as $opt_key => $th_label ) {

				$table_rows[ $opt_key ] = $form->get_tr_hide( $in_view = 'basic', $opt_key ) .
					$form->get_th_html( $th_label, $css_class = '', $opt_key ) .
					'<td class="blank">' . $form->get_no_select( $opt_key, $args[ 'select' ][ 'schema_types' ],
						$css_class = 'schema_type' ) . '</td>';
			}

			/*
			 * Schema Type by Post Type.
			 *
			 * SucomUtilWP::get_post_type_labels() calls SucomUtilWP::get_post_types(), which returns post types
			 * registered as 'public' = true and 'show_ui' = true by default. Note that the 'wp_block' custom post type
			 * for reusable blocks is registered as 'public' = false and 'show_ui' = true.
			 */
			$type_select = '';
			$type_labels = SucomUtilWP::get_post_type_labels( $val_prefix = 'schema_type_for_' );
			$type_labels = apply_filters( 'wpsso_schema_type_post_type_labels', $type_labels );

			foreach ( $type_labels as $opt_key => $obj_label ) {

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $args[ 'select' ][ 'schema_types' ],
					$css_class = 'schema_type' ) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ),
						$obj_label ) . '</p>' . "\n";
			}

			$table_rows[ 'schema_type_for_pt' ] = '' .
				$form->get_th_html( _x( 'Type by Post Type', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'schema_type_for_pt' ) .
				'<td class="blank">' . $type_select . '</td>';

			/*
			 * Schema Type by Post Type Archive.
			 */
			$type_select = '';
			$type_keys   = array();
			$type_labels = SucomUtilWP::get_post_type_archive_labels( $val_prefix = 'schema_type_for_pta_' );

			foreach ( $type_labels as $opt_key => $obj_label ) {

				$type_keys[] = $opt_key;

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $args[ 'select' ][ 'schema_types' ],
					$css_class = 'schema_type' ) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ),
						$obj_label ) . '</p>' . "\n";
			}

			if ( ! empty( $type_select ) ) {

				$table_rows[ 'schema_type_for_pta' ] = $form->get_tr_hide( $in_view = 'basic', $type_keys ) .
					$form->get_th_html( _x( 'Type by Post Type Archive', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'schema_type_for_pta' ) .
					'<td class="blank">' . $type_select . '</td>';
			}

			/*
			 * Schema Type by Taxonomy.
			 */
			$type_select = '';
			$type_keys   = array();
			$type_labels = SucomUtilWP::get_taxonomy_labels( $val_prefix = 'schema_type_for_tax_' );

			foreach ( $type_labels as $opt_key => $obj_label ) {

				$type_keys[] = $opt_key;

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $args[ 'select' ][ 'schema_types' ],
					$css_class = 'schema_type' ) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ),
						$obj_label ) . '</p>' . "\n";
			}

			$table_rows[ 'schema_type_for_tax' ] = $form->get_tr_hide( $in_view = 'basic', $type_keys ) .
				$form->get_th_html( _x( 'Type by Taxonomy', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'schema_type_for_tax' ) .
				'<td class="blank">' . $type_select . '</td>';

			return $table_rows;
		}

		/*
		 * Since WPSSO Core v13.5.0.
		 */
		public function filter_mb_advanced_schema_defs_article_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'wpsso_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_add_articlebody_prop' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Add Article Body Property', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_add_articlebody_prop',
					'content'  => $form->get_no_checkbox( 'schema_def_add_articlebody_prop' ),
				),
				'schema_def_article_section' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Article Section', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_article_section',
					'content'  => $form->get_no_select( 'schema_def_article_section', $args[ 'select' ][ 'article_sections' ] ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_mb_advanced_schema_defs_book_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'wpsso_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_book_format' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Book Format', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_book_format',
					'content'  => $form->get_no_select( 'schema_def_book_format', $this->p->cf[ 'form' ][ 'book_format' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_mb_advanced_schema_defs_creative_work_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'wpsso_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_add_date_created' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Add Date Created Property', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_add_date_created',
					'content'  => $form->get_no_checkbox( 'schema_def_add_date_created' ),
				),
				'schema_def_add_date_published' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Add Date Published Property', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_add_date_published',
					'content'  => $form->get_no_checkbox( 'schema_def_add_date_published' ),
				),
				'schema_def_add_date_modified' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Add Date Modified Property', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_add_date_modified',
					'content'  => $form->get_no_checkbox( 'schema_def_add_date_modified' ),
				),
				'schema_def_add_text_prop' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Add Text Property', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_add_text_prop',
					'content'  => $form->get_no_checkbox( 'schema_def_add_text_prop' ),
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
					'content'  => $form->get_no_select( 'schema_def_pub_org_id', $args[ 'select' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_pub_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Publisher Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_pub_person_id',
					'content'  => $form->get_no_select( 'schema_def_pub_person_id', $args[ 'select' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_prov_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Provider Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_prov_org_id',
					'content'  => $form->get_no_select( 'schema_def_prov_org_id', $args[ 'select' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_prov_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Provider Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_prov_person_id',
					'content'  => $form->get_no_select( 'schema_def_prov_person_id', $args[ 'select' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_fund_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Funder Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_fund_org_id',
					'content'  => $form->get_no_select( 'schema_def_fund_org_id', $args[ 'select' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_fund_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Funder Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_fund_person_id',
					'content'  => $form->get_no_select( 'schema_def_fund_person_id', $args[ 'select' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_mb_advanced_schema_defs_event_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'wpsso_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_event_attendance' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Event Attendance', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_attendance',
					'content'  => $form->get_no_select( 'schema_def_event_attendance', $this->p->cf[ 'form' ][ 'event_attendance' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_def_event_location_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Event Venue', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_location_id',
					'content'  => $form->get_no_select( 'schema_def_event_location_id', $args[ 'select' ][ 'place' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_event_performer_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Event Performer Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_performer_org_id',
					'content'  => $form->get_no_select( 'schema_def_event_performer_org_id', $args[ 'select' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_event_performer_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Event Performer Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_performer_person_id',
					'content'  => $form->get_no_select( 'schema_def_event_performer_person_id', $args[ 'select' ][ 'person' ],
						$css_class = 'wide' ),
				),
				'schema_def_event_organizer_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Event Organizer Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_organizer_org_id',
					'content'  => $form->get_no_select( 'schema_def_event_organizer_org_id', $args[ 'select' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_event_organizer_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Event Organizer Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_organizer_person_id',
					'content'  => $form->get_no_select( 'schema_def_event_organizer_person_id', $args[ 'select' ][ 'person' ],
						$css_class = 'wide' ),
				),
				'schema_def_event_fund_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Event Funder Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_fund_org_id',
					'content'  => $form->get_no_select( 'schema_def_event_fund_org_id', $args[ 'select' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_event_fund_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Event Funder Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_event_fund_person_id',
					'content'  => $form->get_no_select( 'schema_def_event_fund_person_id', $args[ 'select' ][ 'person' ],
						$css_class = 'wide' ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_mb_advanced_schema_defs_job_posting_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'wpsso_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_job_hiring_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Job Hiring Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_job_hiring_org_id',
					'content'  => $form->get_no_select( 'schema_def_job_hiring_org_id', $args[ 'select' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_job_location_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Job Location', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_job_location_id',
					'content'  => $form->get_no_select( 'schema_def_job_location_id', $args[ 'select' ][ 'place' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
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

		public function filter_mb_advanced_schema_defs_place_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'wpsso_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_place_schema_type' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Place Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_place_schema_type',
					'content'  => $form->get_no_select( 'schema_def_place_schema_type', $args[ 'select' ][ 'place_types' ],
						$css_class = 'schema_type' ),
				),
				'schema_def_place_country' => array(
					'td_class' => 'blank',
					'label'   => _x( 'Default Place Country', 'option label', 'wpsso' ),
					'tooltip' => 'schema_def_place_country',
					'content' => $form->get_no_select_country( 'schema_def_place_country' ),
				),
				'schema_def_place_timezone' => array(
					'td_class' => 'blank',
					'label'   => _x( 'Default Place Timezone', 'option label', 'wpsso' ),
					'tooltip' => 'schema_def_place_country',
					'content' => $form->get_no_select_timezone( 'schema_def_place_timezone' ),
				),
			);


			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_mb_advanced_schema_defs_product_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'wpsso_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_product_aggr_offers' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Aggregate Offers by Currency', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_aggr_offers',
					'content'  => $form->get_no_checkbox( 'schema_def_product_aggr_offers' ) . ' ' .
						sprintf( _x( '(not compatible with <a href="%s">price drop appearance</a>)', 'option comment', 'wpsso' ),
							'https://developers.google.com/search/docs/data-types/product#price-drop'),
				),
				'schema_def_product_mrp' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Return Policy', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_mrp',
					'content'  => $form->get_no_select( 'schema_def_product_mrp', $args[ 'select' ][ 'mrp' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_product_category' => array(	// Product Google Category ID.
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Google Category', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_category',
					'content'  => $form->get_no_select( 'schema_def_product_category', $args[ 'select' ][ 'google_prod_cats' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_product_price_type' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Price Type', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_price_type',
					'content'  => $form->get_no_select( 'schema_def_product_price_type', $this->p->cf[ 'form' ][ 'price_type' ] ),
				),
				'schema_def_product_condition' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Condition', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_condition',
					'content'  => $form->get_no_select( 'schema_def_product_condition', $this->p->cf[ 'form' ][ 'item_condition' ] ),
				),
				'schema_def_product_energy_efficiency_min_max' => array(
					'td_class' => 'blank',
					'label'   => _x( 'Default Product Energy Rating Range', 'option label', 'wpsso' ),
					'tooltip' => 'schema_def_product_energy_efficiency_min_max',
					'content' => '' .
						$form->get_no_select( 'schema_def_product_energy_efficiency_min', $this->p->cf[ 'form' ][ 'energy_efficiency' ],
							$css_class = 'energy_efficiency', $css_id = '', $is_assoc = 'sorted' ) . ' ' .
						_x( 'to', 'option comment', 'wpsso' ) . ' ' .
						$form->get_no_select( 'schema_def_product_energy_efficiency_max', $this->p->cf[ 'form' ][ 'energy_efficiency' ],
							$css_class = 'energy_efficiency', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_def_product_target_gender' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Target Gender', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_target_gender',
					'content'  => $form->get_no_select( 'schema_def_product_target_gender', $this->p->cf[ 'form' ][ 'target_gender' ],
						$css_class = 'gender', $css_id = '', $is_assoc = true ),
				),
				'schema_def_product_size_group' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Size Group', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_size_group',
					'content'  => '' .
						$form->get_no_select( 'schema_def_product_size_group_0', $this->p->cf[ 'form' ][ 'size_group' ],
							$css_class = 'size_group', $css_id = '', $is_assoc = true ) . ' ' .
						$form->get_no_select( 'schema_def_product_size_group_1', $this->p->cf[ 'form' ][ 'size_group' ],
							$css_class = 'size_group', $css_id = '', $is_assoc = true ),
				),
				'schema_def_product_size_system' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Size System', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_size_system',
					'content'  => $form->get_no_select( 'schema_def_product_size_system', $this->p->cf[ 'form' ][ 'size_system' ] ),
				),
				'schema_def_product_age_group' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Age Group', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_age_group',
					'content'  => $form->get_no_select( 'schema_def_product_age_group', $this->p->cf[ 'form' ][ 'age_group' ] ),
				),
				'schema_def_product_adult_type' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Product Adult Type', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_product_adult_type',
					'content'  => $form->get_no_select( 'schema_def_product_adult_type', $this->p->cf[ 'form' ][ 'adult_type' ] ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_mb_advanced_schema_defs_profile_page_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'schema_def_profile_page_mentions_prop' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Add Mentions Property', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_profile_page_mentions_prop',
					'content'  => $form->get_no_checkbox( 'schema_def_profile_page_mentions_prop' ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_mb_advanced_schema_defs_review_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'wpsso_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_def_review_rating_min' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Review Rating Min', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_review_rating_min',
					'content'  => $form->get_no_input( 'schema_def_review_rating_min', $css_class = 'rating' ),
				),
				'schema_def_review_rating_max' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Review Rating Max', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_review_rating_max',
					'content'  => $form->get_no_input( 'schema_def_review_rating_max', $css_class = 'rating' ),
				),
				'schema_def_review_item_type' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Subject Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_review_item_type',
					'content'  => $form->get_no_select( 'schema_def_review_item_type', $args[ 'select' ][ 'schema_types' ],
						$css_class = 'schema_type' ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		public function filter_mb_advanced_schema_defs_service_rows( $table_rows, $form, $args ) {

			$form_rows = array(
				'schema_def_service_prov_org_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Service Provider Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_service_prov_org_id',
					'content'  => $form->get_no_select( 'schema_def_service_prov_org_id', $args[ 'select' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_def_service_prov_person_id' => array(
					'td_class' => 'blank',
					'label'    => _x( 'Default Service Provider Person', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_def_service_prov_person_id',
					'content'  => $form->get_no_select( 'schema_def_service_prov_person_id', $args[ 'select' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows );

			return $table_rows;
		}

		/*
		 * Contact Fields > Default Contacts tab.
		 */
		public function filter_mb_advanced_contact_fields_default_cm_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[] = '<th class="medium"></th>' .
				$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ), $css_class = 'checkbox left', 'custom-cm-show-checkbox' ) .
				$form->get_th_html( _x( 'Contact Field ID', 'column title', 'wpsso' ), $css_class = 'medium left', 'wp-cm-field-id' ) .
				$form->get_th_html_locale( _x( 'Contact Field Label', 'column title', 'wpsso' ), $css_class = 'wide left', 'custom-cm-field-label' );

			$sorted_cm_names = $this->p->cf[ 'wp' ][ 'cm_names' ];

			ksort( $sorted_cm_names );

			foreach ( $sorted_cm_names as $cm_id => $opt_label ) {

				$cm_enabled_key = 'wp_cm_' . $cm_id . '_enabled';
				$cm_name_key    = 'wp_cm_' . $cm_id . '_name';
				$cm_label_key   = 'wp_cm_' . $cm_id . '_label';

				/*
				 * Not all social websites have a contact method field.
				 */
				if ( ! isset( $form->options[ $cm_enabled_key ] ) ) {

					continue;
				}

				$table_rows[] = '' .
					$form->get_th_html( $opt_label, $css_class = 'medium' ) .
					$form->get_no_td_checkbox( $cm_enabled_key, $comment = '', $extra_css_class = 'checkbox' ) .
					'<td class="blank medium">' . $form->get_no_input( $cm_name_key, $css_class = 'medium' ) . '</td>' .
					'<td class="blank wide">' . $form->get_no_input_locale( $cm_label_key ) . '</td>';
			}

			return $table_rows;
		}

		/*
		 * Contact Fields > Custom Contacts tab.
		 */
		public function filter_mb_advanced_contact_fields_custom_cm_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$table_rows[] = '<th class="medium"></th>' .
				$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ), $css_class = 'checkbox left', 'custom-cm-show-checkbox' ) .
				$form->get_th_html( _x( 'Contact Field ID', 'column title', 'wpsso' ), $css_class = 'medium left', 'custom-cm-field-id' ) .
				$form->get_th_html_locale( _x( 'Contact Field Label', 'column title', 'wpsso' ), $css_class = 'wide left', 'custom-cm-field-label' );

			foreach ( $this->p->cf[ 'opt' ][ 'cm_prefix' ] as $cm_id => $opt_pre ) {

				$cm_enabled_key = 'plugin_cm_' . $opt_pre . '_enabled';
				$cm_name_key    = 'plugin_cm_' . $opt_pre . '_name';
				$cm_label_key   = 'plugin_cm_' . $opt_pre . '_label';

				if ( isset( $form->options[ $cm_enabled_key ] ) ) {

					$table_rows[] = '' .
						$form->get_th_html( ucfirst( $cm_id ), $css_class = 'medium' ) .
						$form->get_no_td_checkbox( $cm_enabled_key, $comment = '', $extra_css_class = 'checkbox' ) .
						'<td class="blank medium">' . $form->get_no_input( $cm_name_key, $css_class = 'medium' ) . '</td>' .
						'<td class="blank wide">' . $form->get_no_input_locale( $cm_label_key ) . '</td>';
				}
			}

			return $table_rows;
		}

		/*
		 * About the User metabox.
		 */
		public function filter_mb_advanced_user_about_rows( $table_rows, $form, $args ) {

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

		/*
		 * Attributes and Metadata > Product Attributes tab.
		 */
		public function filter_mb_advanced_metadata_product_attrs_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-product-attrs' ) . '</td>';

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$opts_transl = SucomUtilOptions::get_opts_labels_transl( $this->p->cf[ 'form' ][ 'attr_labels' ], $text_domain = 'wpsso' );

			foreach ( $opts_transl as $opt_key => $opt_label_transl ) {

				$cmt_transl = WpssoAdmin::get_option_unit_comment( $opt_key );

				$table_rows[ $opt_key ] = '' .
					$form->get_th_html_locale( $opt_label_transl, '', $opt_key ) .
					'<td class="blank">' . $form->get_no_input_locale( $opt_key ) . $cmt_transl . '</td>';
			}

			return $table_rows;
		}

		/*
		 * Attributes and Metadata > Custom Fields tab.
		 */
		public function filter_mb_advanced_metadata_custom_fields_rows( $table_rows, $form, $args ) {

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-custom-fields' ) . '</td>';

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			$opts_labels = array();
			$cf_md_index = WpssoConfig::get_cf_md_index();	// Uses a local cache.

			foreach ( $cf_md_index as $opt_cf_key => $md_key ) {

				/*
				 * Make sure we have a label for the custom field option.
				 */
				if ( ! empty( $this->p->cf[ 'form' ][ 'cf_labels' ][ $opt_cf_key ] ) ) {

					$opts_labels[ $opt_cf_key ] = $this->p->cf[ 'form' ][ 'cf_labels' ][ $opt_cf_key ];
				}
			}

			$opts_transl = SucomUtilOptions::get_opts_labels_transl( $opts_labels, $text_domain = 'wpsso' );

			foreach ( $opts_transl as $opt_cf_key => $opt_label_transl ) {

				/*
				 * If we don't have a meta data options key, then clear the custom field name (just in case) and
				 * disable the option.
				 */
				if ( empty( $cf_md_index[ $opt_cf_key ] ) ) {

					$form->options[ $opt_cf_key ] = '';
				}

				$cmt_transl = WpssoAdmin::get_option_unit_comment( $opt_cf_key );

				$table_rows[ $opt_cf_key ] = '' .
					$form->get_th_html( $opt_label_transl, '', $opt_cf_key ) .
					'<td class="blank">' . $form->get_no_input( $opt_cf_key, $css_class = '', $css_id = '', $holder = '' ) . $cmt_transl . '</td>';
			}

			return $table_rows;
		}

		/*
		 * HTML Tags > Facebook tab.
		 */
		public function filter_mb_advanced_head_tags_facebook_rows( $table_rows, $form, $args ) {

			return $this->get_head_tags_rows( $table_rows, $form, array( '/^add_(meta)_(property)_((fb|al):.+)$/' ) );
		}

		/*
		 * HTML Tags > Open Graph tab.
		 */
		public function filter_mb_advanced_head_tags_open_graph_rows( $table_rows, $form, $args ) {

			return $this->get_head_tags_rows( $table_rows, $form, array( '/^add_(meta)_(property)_(.+)$/' ) );
		}

		/*
		 * HTML Tags > X (Twitter) tab.
		 */
		public function filter_mb_advanced_head_tags_twitter_rows( $table_rows, $form, $args ) {

			return $this->get_head_tags_rows( $table_rows, $form, array( '/^add_(meta)_(name)_(twitter:.+)$/' ) );
		}

		/*
		 * HTML Tags > SEO / Other tab.
		 */
		public function filter_mb_advanced_head_tags_seo_other_rows( $table_rows, $form, $args ) {

			if ( ! empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

				$table_rows[] = '<td colspan="8"><blockquote class="top-info"><p>' .
					__( 'An SEO plugin has been detected - some basic SEO meta tags have been unchecked and disabled automatically.', 'wpsso' ) .
						'</p></blockquote></td>';
			}

			return $this->get_head_tags_rows( $table_rows, $form, array( '/^add_(link)_([^_]+)_(.+)$/', '/^add_(meta)_(name)_(.+)$/' ) );
		}

		private function get_head_tags_rows( $table_rows, $form, array $opt_preg_include ) {

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

					$css_class   = '';
					$css_id      = '';
					$force       = null;
					$group       = null;
					$extra_class = '';

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
						'<td class="xshort' . $extra_class . '">' . $match[ 1 ] . '</td>' .
						'<td class="head_tags' . $extra_class . '">' . $match[ 2 ] . '</td>' .
						'<th class="head_tags' . $extra_class . '">' . $match[ 3 ] . '</th>';
				}
			}

			return array_merge( $table_rows, $this->p->admin->get_table_rows_cols( $table_cells, 2 ) );
		}
	}
}
