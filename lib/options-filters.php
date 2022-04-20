<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoOptionsFilters' ) ) {

	/**
	 * Since WPSSO Core v9.0.0.
	 */
	class WpssoOptionsFilters {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by WpssoOptions->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'option_type' => 4,
			), $prio = -1000 );	// Run first.
		}

		/**
		 * Return the sanitation type for a given option key.
		 */
		public function filter_option_type( $type, $base_key, $network, $mod ) {

			if ( ! empty( $type ) ) {	// Return early if we already have a type.

				return $type;

			} elseif ( 0 === strpos( $base_key, 'schema_' ) ) {

				switch ( $base_key ) {

					/**
					 * Cast as integer (zero and -1 is ok).
					 */
					case 'schema_vid_max':

						return 'integer';	// Aka 'int'.

					/**
					 * Must be numeric (blank and zero are ok).
					 */
					case 'schema_book_audio_duration_days':		// Audiobook Duration.
					case 'schema_book_audio_duration_hours':
					case 'schema_book_audio_duration_mins':
					case 'schema_book_audio_duration_secs':
					case 'schema_event_offer_price':
					case 'schema_howto_prep_days':			// How-To Preparation Time.
					case 'schema_howto_prep_hours':
					case 'schema_howto_prep_mins':
					case 'schema_howto_prep_secs':
					case 'schema_howto_total_days':			// How-To Total Time.
					case 'schema_howto_total_hours':
					case 'schema_howto_total_mins':
					case 'schema_howto_total_secs':
					case 'schema_job_salary':			// Job Base Salary.
					case 'schema_movie_duration_days':		// Movie Runtime.
					case 'schema_movie_duration_hours':
					case 'schema_movie_duration_mins':
					case 'schema_movie_duration_secs':
					case 'schema_recipe_cook_days':			// Recipe Cooking Time.
					case 'schema_recipe_cook_hours':
					case 'schema_recipe_cook_mins':
					case 'schema_recipe_cook_secs':
					case 'schema_recipe_nutri_cal':
					case 'schema_recipe_nutri_prot':
					case 'schema_recipe_nutri_fib':
					case 'schema_recipe_nutri_carb':
					case 'schema_recipe_nutri_sugar':
					case 'schema_recipe_nutri_sod':
					case 'schema_recipe_nutri_fat':
					case 'schema_recipe_nutri_sat_fat':
					case 'schema_recipe_nutri_unsat_fat':
					case 'schema_recipe_nutri_chol':
					case 'schema_recipe_prep_days':			// Recipe Preparation Time.
					case 'schema_recipe_prep_hours':
					case 'schema_recipe_prep_mins':
					case 'schema_recipe_prep_secs':
					case 'schema_recipe_total_days':		// Recipe Total Time.
					case 'schema_recipe_total_hours':
					case 'schema_recipe_total_mins':
					case 'schema_recipe_total_secs':
					case 'schema_review_rating':
					case 'schema_review_rating_from':
					case 'schema_review_rating_to':

						return 'blank_num';

					/**
					 * Empty string or an image ID.
					 */
					case 'schema_img_id':

						return 'img_id';

					/**
					 * Text strings that can be blank (line breaks are removed).
					 */
					case 'schema_title':				// Schema Name.
					case 'schema_title_alt':			// Schema Alternate Name.
					case 'schema_title_bc':				// Schema Breadcrumb Name.
					case 'schema_book_author_name':			// Book Author Name.
					case 'schema_desc':				// Schema Description.
					case 'schema_headline':				// Headline.
					case 'schema_text':				// Full Text.
					case 'schema_copyright_year':			// Copyright Year.
					case 'schema_event_offer_name':
					case 'schema_howto_step':			// How-To Step Name.
					case 'schema_howto_step_text':			// How-To Step Description.
					case 'schema_howto_supply':			// How-To Supplies.
					case 'schema_howto_tool':			// How-To Tools.
					case 'schema_howto_yield':			// How-To Makes.
					case 'schema_job_title':			// Job Title.
					case 'schema_job_currency':			// Job Base Salary Currency.
					case 'schema_movie_actor_person_name':		// Movie Cast Names.
					case 'schema_movie_director_person_name':	// Movie Director Names.
					case 'schema_recipe_cook_method':
					case 'schema_recipe_course':
					case 'schema_recipe_cuisine':
					case 'schema_recipe_ingredient':		// Recipe Ingredients.
					case 'schema_recipe_instruction':		// Recipe Instructions.
					case 'schema_recipe_instruction_text':		// Recipe Instruction Description.
					case 'schema_recipe_nutri_serv':
					case 'schema_recipe_yield':			// Recipe Makes.
					case 'schema_review_rating_alt_name':
					case 'schema_review_claim_reviewed':
					case 'schema_review_item_name':					// Review: Subject Name.
					case 'schema_review_item_desc':					// Review: Subject Description.
					case 'schema_review_item_cw_author_name':			// Review: Subject Author Name.
					case 'schema_review_item_cw_movie_actor_person_name':		// Review: Subject Movie Cast Names.
					case 'schema_review_item_cw_movie_director_person_name':	// Review: Subject Movie Director Names.
					case 'schema_review_item_software_app_cat':
					case 'schema_review_item_software_app_os':
					case 'schema_software_app_cat':
					case 'schema_software_app_os':

						return 'one_line';

					/**
					 * CSV strings that can be blank (line breaks are removed).
					 */
					case 'schema_keywords':	// Keywords.

						return 'csv_blank';

					/**
					 * Options that cannot be blank.
					 */
					case 'schema_book_author_type':			// Book Author Type.
					case 'schema_def_book_format':			// Default Book Format.
					case 'schema_def_event_location_id':		// Default Physical Venue.
					case 'schema_def_event_organizer_org_id':	// Default Organizer Org.
					case 'schema_def_event_organizer_person_id':	// Default Organizer Person.
					case 'schema_def_event_performer_org_id':	// Default Performer Org.
					case 'schema_def_event_performer_person_id':	// Default Performer Person.
					case 'schema_def_family_friendly':		// Default Family Friendly.
					case 'schema_def_job_hiring_org_id':		// Default Hiring Organization.
					case 'schema_def_job_location_id':		// Default Job Location.
					case 'schema_def_job_location_type':		// Default Job Location Type.
					case 'schema_def_prov_org_id':			// Default Service Prov. Org.
					case 'schema_def_prov_person_id':		// Default Service Prov. Person.
					case 'schema_def_pub_org_id':			// Default Publisher Org.
					case 'schema_def_pub_person_id':		// Default Publisher Person.
					case 'schema_def_review_item_type':		// Default Subject Webpage Type.
					case 'schema_event_lang':			// Event Language.
					case 'schema_event_location_id':		// Event Physical Venue.
					case 'schema_event_offer_currency':
					case 'schema_event_offer_avail':
					case 'schema_event_organizer_org_id':		// Event Organizer Org.
					case 'schema_event_organizer_person_id':	// Event Organizer Person.
					case 'schema_event_performer_org_id':		// Event Performer Org.
					case 'schema_event_performer_person_id':	// Event Performer Person.
					case 'schema_event_attendance':			// Event Attendance.
					case 'schema_event_status':			// Event Status.
					case 'schema_family_friendly':			// Family Friendly.
					case 'schema_job_hiring_org_id':		// Hiring Organization.
					case 'schema_job_location_id':			// Job Location.
					case 'schema_job_location_type':		// Job Location Type.
					case 'schema_job_salary_currency':		// Job Base Salary Currency.
					case 'schema_job_salary_period':		// Job Base Salary per Year, Month, Week, Hour.
					case 'schema_lang':				// Language.
					case 'schema_movie_prodco_org_id':		// Production Company.
					case 'schema_prov_org_id':			// Service Prov. Org.
					case 'schema_prov_person_id':			// Service Prov. Person.
					case 'schema_pub_org_id':			// Publisher Org.
					case 'schema_pub_person_id':			// Publisher Person.
					case 'schema_review_item_type':			// Review: Subject Webpage Type.
					case 'schema_review_item_cw_author_type':	// Review: Subject Author Type.
					case 'schema_type':				// Schema Type.

						return 'not_blank';

					/**
					 * Empty string or image URL.
					 */
					case 'schema_img_url':

						return 'img_url';

					/**
					 * Empty string or a URL.
					 */
					case 'schema_addl_type_url':			// Microdata Type URLs.
					case 'schema_book_author_url':			// Book Author URL.
					case 'schema_ispartof_url':			// Is Part of URLs.
					case 'schema_license_url':			// License URL.
					case 'schema_event_online_url':			// Event Online URL.
					case 'schema_review_item_url':			// Review: Subject Webpage URL.
					case 'schema_review_item_sameas_url':		// Review: Subject Same-As URL.
					case 'schema_review_item_cw_author_url':	// Review: Subject Author URL.
					case 'schema_review_claim_first_url':		// First Appearance URL.
					case 'schema_sameas_url':			// Same-As URLs.

						return 'url';
				}
			}

			switch ( $base_key ) {

				/**
				 * The "use" value should be 'default', 'empty', or 'force'.
				 */
				case ( preg_match( '/:use$/', $base_key ) ? true : false ):

					return 'not_blank';

				/**
				 * Optimize and check for add meta tags options first.
				 */
				case ( 0 === strpos( $base_key, 'add_' ) ? true : false ):
				case ( 0 === strpos( $base_key, 'plugin_filter_' ) ? true : false ):

					return 'checkbox';

				/**
				 * twitter-style usernames (prepend with an at).
				 */
				case 'tc_site':

					return 'at_name';

				/**
				 * Empty or alpha-numeric (upper or lower case), plus underscores.
				 */
				case 'fb_app_id':
				case 'fb_app_secret':
				case 'fb_site_verify':			// Facebook Domain Verification ID.
				case 'g_site_verify':			// Google Website Verification ID.
				case 'pin_site_verify':			// Pinterest Website Verification ID.
				case 'plugin_stamped_key_public':	// Stamped.io API Key Public.
				case ( preg_match( '/_api_key$/', $base_key ) ? true : false ):

					return 'api_key';

				/**
				 * Applies sanitize_title_with_dashes().
				 */
				case ( preg_match( '/_utm_(medium|source|campaign|content|term)$/', $base_key ) ? true : false ):

					return 'dashed';

				/**
				 * Empty or 'none' string, or color as #000000.
				 */
				case ( false !== strpos( $base_key, '_color_' ) ? true : false ):

					return 'color';

				/**
				 * JS and CSS code (cannot be blank).
				 */
				case ( false !== strpos( $base_key, '_js_' ) ? true : false ):
				case ( false !== strpos( $base_key, '_css_' ) ? true : false ):
				case ( preg_match( '/(_css|_js|_html)$/', $base_key ) ? true : false ):

					return 'code';

				/**
				 * Gravity View field IDs.
				 */
				case 'gv_id_title':			// Title Field ID.
				case 'gv_id_desc':			// Description Field ID.
				case 'gv_id_img':			// Post Image Field ID.

					return 'blank_int';

				case 'reading_mins':
				case 'robots_max_snippet':		// Snippet Max. Length.
				case 'robots_max_video_preview':	// Video Max. Previews.
				case 'schema_book_pages':		// Number of Pages.

					if ( empty( $mod[ 'name' ] ) ) {	// Must be an interger for plugin settings (ie. no module).

						return 'integer';
					}

					return 'blank_int';		// Allow blank (ie. default) for options.

				/**
				 * Cast as integer (zero and -1 is ok).
				 */
				case 'og_img_max':			// Maximum Images.
				case 'og_vid_max':			// Maximum Videos.
				case 'og_desc_hashtags': 		// Description Hashtags.
				case 'primary_term_id':			// Primary Category.
				case ( preg_match( '/_(cache_exp|caption_hashtags|filter_prio)$/', $base_key ) ? true : false ):
				case ( preg_match( '/_(img|logo|banner)_url(:width|:height)$/', $base_key ) ? true : false ):

					return 'integer';	// Aka 'int'.

				/**
				 * Numeric options that must be positive (1 or more).
				 */
				case 'plugin_gravatar_size':		// Gravatar Image Size.
				case 'plugin_min_shorten':		// Minimum URL Length to Shorten.
				case 'plugin_ratings_reviews_num_max':	// Maximum Number of Reviews.
				case 'plugin_ratings_reviews_age_max':	// Maximum Age of Reviews.
				case 'plugin_upscale_pct_max':		// Maximum Image Upscale Percent.
				case ( preg_match( '/_(len|warn)$/', $base_key ) ? true : false ):

					return 'pos_integer';	// Aka 'pos_int'.

				/**
				 * Must be numeric (blank and zero are ok).
				 */
				case 'product_depth_value':
				case 'product_fluid_volume_value':
				case 'product_gtin14':
				case 'product_gtin13':
				case 'product_gtin12':
				case 'product_gtin8':
				case 'product_gtin':
				case 'product_height_value':
				case 'product_isbn':			// Product ISBN.
				case 'product_length_value':
				case 'product_min_advert_price':	// Product Min Advert Price.
				case 'product_price':			// Product Price.
				case 'product_weight_value':
				case 'product_width_value':
				case 'plugin_stamped_store_hash':	// Stamped.io Store Hash.
				case 'schema_book_isbn':		// Book ISBN.
				case 'schema_review_item_cw_book_isbn':	// Review: Subject Book ISBN.

					return 'blank_num';

				/**
				 * Empty string or an image ID.
				 */
				case 'og_def_img_id':
				case 'og_img_id':
				case 'tc_lrg_img_id':
				case 'tc_sum_img_id':

					return 'img_id';

				/**
				 * Empty string or must include at least one HTML tag.
				 */
				case 'og_vid_embed':

					return 'html';

				/**
				 * Must be texturized.
				 */
				case 'og_title_sep':

					return 'textured';

				/**
				 * Text strings that can be blank (line breaks are removed).
				 */
				case 'site_name':
				case 'site_name_alt':
				case 'site_desc':
				case 'seo_title':			// SEO Title Tag.
				case 'seo_desc':			// SEO Meta Description.
				case 'og_title':
				case 'og_desc':
				case 'tc_title':			// Twitter Card Title.
				case 'tc_desc':				// Twitter Card Description.
				case 'pin_desc':
				case 'article_section':
				case 'product_category':		// Product Type.
				case 'product_brand':
				case 'product_color':
				case 'product_currency':		// Product Price Currency.
				case 'product_material':
				case 'product_mfr_part_no':		// Product MPN.
				case 'product_min_advert_currency':	// Product Min Advert Price Currency.
				case 'product_pattern':
				case 'product_retailer_part_no':	// Product SKU.
				case 'product_size':
				case 'plugin_title_part_site':		// Title Tag Site Prefix / Suffix.
				case 'plugin_title_part_tagline':	// Title Tag Tagline Prefix / Suffix.
				case 'plugin_img_alt_prefix':		// Content Image Alt Prefix.
				case 'plugin_p_cap_prefix':		// WP Caption Text Prefix.
				case 'plugin_bitly_access_token':	// Bitly Generic Access Token.
				case 'plugin_bitly_domain':		// Bitly Short Domain (Optional).
				case 'plugin_bitly_group_name':		// Bitly Group Name (Optional).
				case 'plugin_dlmyapp_api_key':
				case 'plugin_owly_api_key':
				case 'plugin_shopperapproved_site_id':	// Shopper Approved Site ID.
				case 'plugin_shopperapproved_token':	// Shopper Approved API Token.
				case 'plugin_yourls_username':
				case 'plugin_yourls_password':
				case 'plugin_yourls_token':
				case ( 0 === strpos( $base_key, 'plugin_cf_' ) ? true : false ):		// Value is the name of a meta key.
				case ( 0 === strpos( $base_key, 'plugin_attr_product_' ) ? true : false ):	// Value is the name of a product attribute.
				case 'schema_book_edition':		// Book Edition.

					return 'one_line';

				/**
				 * Options that cannot be blank.
				 */
				case 'site_org_place_id':
				case 'site_org_schema_type':
				case 'og_def_article_section':
				case 'og_def_product_category':
				case 'og_def_country':
				case 'og_def_timezone':
				case 'og_def_currency':
				case 'og_def_img_id_lib': 
				case 'og_img_id_lib': 
				case 'plugin_comment_title':			// Comment Title.
				case 'plugin_comment_reply_title':		// Reply Comment Title.
				case 'plugin_comment_review_title':		// Review Comment Title.
				case 'plugin_product_var_title':		// Product Variation Title.
				case 'plugin_404_page_title':			// 404 Page Title.
				case 'plugin_404_page_desc':			// 404 Page Description.
				case 'plugin_no_title_text':			// No Title Text.
				case 'plugin_no_desc_text':			// No Description Text.
				case 'plugin_shortener':			// Example: 'none' or name of shortener
				case 'product_avail':				// Select option with 'none' as default.
				case 'product_condition':			// Select option with 'none' as default.
				case 'product_size_type':			// Select option with 'none' as default.
				case 'product_target_gender':			// Select option with 'none' as default.
				case 'robots_max_image_preview':		// Image Preview Size.
				case ( false !== strpos( $base_key, '_crop_x' ) ? true : false ):
				case ( false !== strpos( $base_key, '_crop_y' ) ? true : false ):
				case ( false !== strpos( $base_key, '_type_for_' ) ? true : false ):
				case ( preg_match( '/^(plugin|wp)_cm_[a-z]+_(name|label)$/', $base_key ) ? true : false ):

					return 'not_blank';

				/**
				 * Empty string or image URL.
				 */
				case 'og_def_img_url':
				case 'og_img_url':
				case 'site_org_banner_url':
				case 'site_org_logo_url':
				case 'tc_lrg_img_url':
				case 'tc_sum_img_url':

					return 'img_url';

				/**
				 * Empty string or a URL.
				 *
				 * Option key exceptions:
				 *
				 *	'add_meta_property_og:image:secure_url' = 1
				 *	'add_meta_property_og:video:secure_url' = 1
				 *	'plugin_cf_img_url'                     = ''	// Image URL Custom Field.
				 *	'plugin_cf_vid_url'                     = ''	// Video URL Custom Field.
				 */
				case 'site_home_url':
				case 'site_wp_url':
				case 'canonical_url':
				case 'redirect_url':
				case 'fb_page_url':
				case 'og_vid_url':
				case 'pin_publisher_url':
				case 'plugin_yourls_api_url':
				case ( strpos( $base_key, '_url' ) && isset( $this->p->cf[ 'form' ][ 'social_accounts' ][ $base_key ] ) ? true : false ):

					return 'url';

				/**
				 * Empty or alpha-numeric uppercase (hyphens are allowed as well).
				 */
				case ( preg_match( '/_tid$/', $base_key ) ? true : false ):

					return 'auth_id';

				/**
				 * Image width, subject to minimum value (typically, at least 200px).
				 */
				case ( preg_match( '/_img_width$/', $base_key ) ? true : false ):

					return 'img_width';

				/**
				 * Image height, subject to minimum value (typically, at least 200px).
				 */
				case ( preg_match( '/_img_height$/', $base_key ) ? true : false ):

					return 'img_height';

				/**
				 * Empty or 'none' string, or date as yyyy-mm-dd.
				 */
				case ( preg_match( '/_date$/', $base_key ) ? true : false ):

					return 'date';

				/**
				 * Empty or 'none' string, or time as hh:mm or hh:mm:ss.
				 *
				 * Check last in case there are taxonomy option names like 'og_type_for_tax_product_delivery_time'.
				 */
				case ( preg_match( '/_time$/', $base_key ) ? true : false ):

					return 'time';

				/**
				 * A regular expression.
				 */
				case ( preg_match( '/_preg$/', $base_key ) ? true : false ):

					return 'preg';
			}

			return $type;
		}
	}
}
