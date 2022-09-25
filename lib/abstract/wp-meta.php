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

/**
 * WordPress metadata class, extended by the WpssoPost, WpssoTerm, and WpssoUser classes.
 */
if ( ! class_exists( 'WpssoAbstractWpMeta' ) ) {

	abstract class WpssoAbstractWpMeta {

		protected $p;		// Wpsso class object.
		protected $form;	// SucomForm class object.

		protected $md_cache_disabled = false;	// Disable local caches when saving options.

		protected static $head_tags = false;	// Must be false by default.

		protected static $head_info = array();

		protected static $rename_keys_by_ext = array(
			'wpsso' => array(
				499 => array(
					'link_desc' => 'seo_desc',
					'meta_desc' => 'seo_desc',
				),
				503 => array(
					'schema_recipe_calories' => 'schema_recipe_nutri_cal',
				),
				514 => array(
					'rp_img_id'     => 'pin_img_id',
					'rp_img_id_pre' => 'pin_img_id_lib',
					'rp_img_width'  => '',
					'rp_img_height' => '',
					'rp_img_crop'   => '',
					'rp_img_crop_x' => '',
					'rp_img_crop_y' => '',
					'rp_img_url'    => 'pin_img_url',
				),
				537 => array(
					'schema_add_type_url' => 'schema_addl_type_url_0',
				),
				569 => array(
					'schema_add_type_url' => 'schema_addl_type_url',	// Option modifiers are preserved.
				),
				615 => array(
					'org_type' => 'org_schema_type',
				),
				628 => array(
					'product_gender' => 'product_target_gender',
				),
				649 => array(
					'product_ean' => 'product_gtin13',
				),

				/**
				 * The custom width, height, and crop options were removed in preference for attachment specific
				 * options (ie. 'attach_img_crop_x' and 'attach_img_crop_y').
				 */
				660 => array(
					'og_img_width'              => '',
					'og_img_height'             => '',
					'og_img_crop'               => '',
					'og_img_crop_x'             => '',
					'og_img_crop_y'             => '',
					'schema_article_img_width'  => '',
					'schema_article_img_height' => '',
					'schema_article_img_crop'   => '',
					'schema_article_img_crop_x' => '',
					'schema_article_img_crop_y' => '',
					'schema_img_width'          => '',
					'schema_img_height'         => '',
					'schema_img_crop'           => '',
					'schema_img_crop_x'         => '',
					'schema_img_crop_y'         => '',
					'tc_sum_img_width'          => '',
					'tc_sum_img_height'         => '',
					'tc_sum_img_crop'           => '',
					'tc_sum_img_crop_x'         => '',
					'tc_sum_img_crop_y'         => '',
					'tc_lrg_img_width'          => '',
					'tc_lrg_img_height'         => '',
					'tc_lrg_img_crop'           => '',
					'tc_lrg_img_crop_x'         => '',
					'tc_lrg_img_crop_y'         => '',
					'thumb_img_width'           => '',
					'thumb_img_height'          => '',
					'thumb_img_crop'            => '',
					'thumb_img_crop_x'          => '',
					'thumb_img_crop_y'          => '',
				),
				692 => array(
					'product_mpn' => 'product_mfr_part_no',
					'product_sku' => 'product_retailer_part_no',
				),
				696 => array(
					'og_art_section' => 'article_section',
				),
				701 => array(
					'article_topic' => 'article_section',
				),
				725 => array(
					'product_volume_value' => 'product_fluid_volume_value',
				),
				786 => array(
					'og_img_id_pre'     => 'og_img_id_lib',
					'p_img_id_pre'      => 'pin_img_id_lib',
					'tc_lrg_img_id_pre' => 'tc_lrg_img_id_lib',
					'tc_sum_img_id_pre' => 'tc_sum_img_id_lib',
					'schema_img_id_pre' => 'schema_img_id_lib',
				),
				812 => array(
					'sharing_url' => '',
				),
				815 => array(
					'p_img_id'     => 'pin_img_id',
					'p_img_id_lib' => 'pin_img_id_lib',
					'p_img_url'    => 'pin_img_url',
				),
				829 => array(
					'book_isbn' => 'schema_book_isbn',
				),
			),
		);

		private static $mod_defaults = array(
			'id'                     => 0,		// Post, term, or user ID.
			'name'                   => false,	// Module name ('post', 'term', or 'user').
			'name_transl'            => false,	// Module name translated.
			'obj'                    => false,	// Module object.
			'wp_obj'                 => false,	// WP object (WP_Post, WP_Term, etc.).
			'query_vars'             => array(),	// Defined by WpssoPage->get_mod().
			'posts_args'             => array(),
			'paged'                  => false,	// False or numeric (aka $wp_query->query_vars[ 'paged' ]).
			'paged_total'            => false,	// False or numberic (aka $wp_query->max_num_pages).
			'is_404'                 => false,
			'is_archive'             => false,
			'is_attachment'          => false,	// Post type is 'attachment'.
			'is_comment'             => false,
			'is_date'                => false,
			'is_day'                 => false,
			'is_home'                => false,	// Home page (static or blog archive).
			'is_home_page'           => false,	// Static front page (singular post).
			'is_home_posts'          => false,	// Static posts page or blog archive page.
			'is_month'               => false,
			'is_post'                => false,
			'is_post_type_archive'   => false,	// Post is an archive page.
			'is_public'              => true,
			'is_search'              => false,
			'is_term'                => false,
			'is_user'                => false,
			'is_year'                => false,
			'comment_author'         => false,	// Comment author user ID.
			'comment_author_name'    => false,	// Comment author name.
			'comment_author_url'     => false,	// Comment author URL.
			'comment_paged'          => false,	// False or numeric (aka $wp_query->query_vars[ 'cpage' ]).
			'comment_parent'         => false,
			'comment_rating'         => false,
			'comment_time'           => false,
			'use_post'               => false,
			'post_slug'              => false,	// Post name (aka slug).
			'post_type'              => false,	// Post type name.
			'post_type_label_plural' => false,	// Post type plural name.
			'post_type_label_single' => false,	// Post type singular name.
			'post_mime'              => false,	// Post mime type (ie. image/jpg).
			'post_status'            => false,	// Post status name.
			'post_author'            => false,	// Post author id.
			'post_coauthors'         => array(),
			'post_time'              => false,	// Post published time (ISO 8601 date or false).
			'post_modified_time'     => false,	// Post modified time (ISO 8601 date or false).
			'post_parent'            => false,	// Post parent id.
			'term_tax_id'            => false,
			'tax_slug'               => '',
			'tax_label_plural'       => false,	// Taxonomy plural name.
			'tax_label_single'       => false,	// Taxonomy singular name.
			'user_name'              => '',		// User display name.
			'wpml_code'              => '',
		);

		public function __construct( &$plugin ) {

			return self::must_be_extended();
		}

		/**
		 * Add WordPress action and filters hooks.
		 */
		public function add_wp_hooks() {

			return self::must_be_extended();
		}

		/**
		 * Get the $mod object for a post, term, or user ID.
		 */
		public function get_mod( $obj_id ) {

			return self::must_be_extended( self::$mod_defaults );
		}

		public function get_mod_wp_object( array $mod ) {

			return self::must_be_extended( $ret_val = false );
		}

		public static function get_mod_defaults() {

			return self::$mod_defaults;
		}

		/**
		 * Get the $mod object for the home page.
		 */
		public static function get_mod_home() {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$mod = self::$mod_defaults;

			$post_id = 0;

			if ( 'page' === get_option( 'show_on_front' ) ) {

				if ( ! $post_id = (int) get_option( 'page_on_front', $default = 0 ) ) {

					$post_id = (int) get_option( 'page_for_posts', $default = 0 );
				}

				$mod = $wpsso->post->get_mod( $post_id );
			}

			/**
			 * Post elements.
			 */
			$mod[ 'is_home' ] = true;	// Home page (static or blog archive).

			return $mod;
		}

		/**
		 * Option handling methods:
		 *
		 *	get_defaults()
		 *	get_options()
		 *	save_options()
		 *	delete_options()
		 */
		public function get_defaults( $obj_id, $md_key = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'obj_id' => $obj_id,
					'md_key' => $md_key,
				) );
			}

			/**
			 * Note that the local cache is unique to each child class, so we can simply index by the object ID.
			 */
			static $local_cache = array();

			if ( ! isset( $local_cache[ $obj_id ] ) ) {

				$local_cache[ $obj_id ] = array();

			} elseif ( $this->md_cache_disabled ) {

				$local_cache[ $obj_id ] = array();
			}

			$md_defs =& $local_cache[ $obj_id ];	// Shortcut variable name.

			if ( empty( $md_defs[ 'opt_filtered' ] ) ) {

				$mod = $this->get_mod( $obj_id );

				$opts =& $this->p->options;		// Shortcut variable name.

				$def_og_type         = $this->p->og->get_mod_og_type_id( $mod, $use_md_opts = false );
				$def_schema_type     = $this->p->schema->get_mod_schema_type_id( $mod, $use_md_opts = false );
				$def_primary_term_id = $this->p->post->get_default_term_id( $mod, $tax_slug = 'category' );	// Returns term ID or false.
				$def_reading_mins    = $this->p->page->get_reading_mins( $mod );

				$def_img_id_lib      = isset( $opts[ 'og_def_img_id_lib' ] ) ? $opts[ 'og_def_img_id_lib' ] : 'wp';
				$def_currency        = isset( $opts[ 'og_def_currency' ] ) ? $opts[ 'og_def_currency' ] : 'USD';
				$def_article_section = isset( $opts[ 'og_def_article_section' ] ) ? $opts[ 'og_def_article_section' ] : 'none';

				$def_adult_oriented  = isset( $opts[ 'schema_def_product_adult_oriented' ] ) ? $opts[ 'schema_def_product_adult_oriented' ] : 'none';
				$def_age_group       = isset( $opts[ 'schema_def_product_age_group' ] ) ? $opts[ 'schema_def_product_age_group' ] : 'none';
				$def_product_cat     = isset( $opts[ 'schema_def_product_category' ] ) ? $opts[ 'schema_def_product_category' ] : 'none';
				$def_product_cond    = isset( $opts[ 'schema_def_product_condition' ] ) ? $opts[ 'schema_def_product_condition' ] : 'none';
				$def_price_type      = isset( $opts[ 'schema_def_product_price_type' ] ) ? $opts[ 'schema_def_product_price_type' ] :
					'https://schema.org/ListPrice';

				$def_lang            = SucomUtil::get_locale( $mod, $read_cache = false );	// Get locale for post, term, or user object.
				$job_locations_max   = SucomUtil::get_const( 'WPSSO_SCHEMA_JOB_LOCATIONS_MAX', 5 );

				/**
				 * Default timezone.
				 *
				 * Note that the timezone option will be empty if a UTC offset (instead of a city) has been
				 * selected in the WordPress settings.
				 */
				$def_timezone = get_option( 'timezone_string' );

				if ( empty( $def_timezone ) ) {

					$def_timezone = 'UTC';
				}

				$md_defs = array(
					'checksum'          => '',	// Checksum of plugin versions.
					'opt_checksum'      => '',	// Checksum of option versions.
					'opt_versions'      => array(),
					'attach_img_crop_x' => 'none',
					'attach_img_crop_y' => 'none',

					/**
					 * Gravity View (Side Metabox).
					 */
					'gv_id_title' => 0,	// Title Field ID
					'gv_id_desc'  => 0,	// Description Field ID
					'gv_id_img'   => 0,	// Post Image Field ID

					/**
					 * Edit General.
					 */
					'article_section' => $def_article_section,	// Article Section.
					'reading_mins'    => $def_reading_mins,		// Est. Reading Time.
					'primary_term_id' => $def_primary_term_id,	// Primary Category.
					'seo_title'       => '',			// SEO Title Tag.
					'seo_desc'        => '',			// SEO Meta Description.
					'og_type'         => $def_og_type,		// Open Graph Type.
					'og_title'        => '',			// Social Title.
					'og_desc'         => '',			// Social Description.
					'pin_img_desc'    => '',			// Pinterest Description.
					'tc_title'        => '',			// Twitter Card Title.
					'tc_desc'         => '',			// Twitter Card Description.

					/**
					 * Edit Media tab.
					 */
					'og_img_max'        => isset( $opts[ 'og_img_max' ] ) ? (int) $opts[ 'og_img_max' ] : 1,	// 1 by default.
					'og_vid_max'        => isset( $opts[ 'og_vid_max' ] ) ? (int) $opts[ 'og_vid_max' ] : 1,	// 1 by default.
					'og_vid_prev_img'   => empty( $opts[ 'og_vid_prev_img' ] ) ? 0 : 1,	// Enabled by default.
					'og_vid_autoplay'   => empty( $opts[ 'og_vid_autoplay' ] ) ? 0 : 1,	// Enabled by default.
					'og_img_id'         => '',
					'og_img_id_lib'     => $def_img_id_lib,
					'og_img_url'        => '',
					'og_vid_embed'      => '',
					'og_vid_url'        => '',
					'og_vid_title'      => '',
					'og_vid_desc'       => '',
					'og_vid_stream_url' => '',
					'og_vid_width'      => '',
					'og_vid_height'     => '',
					'pin_img_id'        => '',
					'pin_img_id_lib'    => $def_img_id_lib,
					'pin_img_url'       => '',
					'tc_lrg_img_id'     => '',
					'tc_lrg_img_id_lib' => $def_img_id_lib,
					'tc_lrg_img_url'    => '',
					'tc_sum_img_id'     => '',
					'tc_sum_img_id_lib' => $def_img_id_lib,
					'tc_sum_img_url'    => '',
					'schema_img_id'     => '',
					'schema_img_id_lib' => $def_img_id_lib,
					'schema_img_url'    => '',

					/**
					 * Edit Visibility tab.
					 */
					'canonical_url' => '',		// Canonical URL.
					'redirect_url'  => '',		// 301 Redirect URL.

					/**
					 * Open Graph and Schema Product type.
					 *
					 * See WpssoOpengraph->add_og_type_mt_md().
					 */
					'product_category'            => $def_product_cat,
					'product_brand'               => '',
					'product_price'               => '0.00',	// Product Price.
					'product_price_type'          => $def_price_type,
					'product_currency'            => $def_currency,
					'product_min_advert_price'    => '0.00',	// Product Min Advert Price.
					'product_avail'               => 'none',
					'product_condition'           => $def_product_cond,
					'product_color'               => '',
					'product_material'            => '',
					'product_pattern'             => '',
					'product_target_gender'       => 'none',
					'product_size'                => '',
					'product_size_type'           => 'none',
					'product_age_group'           => $def_age_group,
					'product_adult_oriented'      => $def_adult_oriented,
					'product_weight_value'        => '',
					'product_length_value'        => '',
					'product_width_value'         => '',
					'product_height_value'        => '',
					'product_depth_value'         => '',
					'product_retailer_part_no'    => '',	// Product SKU.
					'product_mfr_part_no'         => '',	// Product MPN.
					'product_gtin14'              => '',
					'product_gtin13'              => '',
					'product_gtin12'              => '',
					'product_gtin8'               => '',
					'product_gtin'                => '',
					'product_isbn'                => '',

					/**
					 * All Schema Types.
					 */
					'schema_type'      => $def_schema_type,	// Schema Type.
					'schema_title'     => '',		// Schema Name.
					'schema_title_alt' => '',		// Schema Alternate Name.
					'schema_title_bc'  => '',		// Schema Breadcrumb Name.
					'schema_desc'      => '',		// Schema Description.

					/**
					 * Schema Creative Work.
					 */
					'schema_ispartof_url'    => '',						// Is Part of URLs.
					'schema_headline'        => '',						// Headline.
					'schema_text'            => '',						// Full Text.
					'schema_keywords'        => '',						// Keywords.
					'schema_lang'            => $def_lang,					// Language.
					'schema_family_friendly' => $opts[ 'schema_def_family_friendly' ],	// Family Friendly.
					'schema_copyright_year'  => '',						// Copyright Year.
					'schema_license_url'     => '',						// License URL.
					'schema_prov_org_id'     => $opts[ 'schema_def_prov_org_id' ],		// Service Prov. Org.
					'schema_prov_person_id'  => $opts[ 'schema_def_prov_person_id' ],	// Service Prov. Person.
					'schema_pub_org_id'      => $opts[ 'schema_def_pub_org_id' ],		// Publisher Org.
					'schema_pub_person_id'   => $opts[ 'schema_def_pub_person_id' ],	// Publisher Person.

					/**
					 * Schema Book.
					 */
					'schema_book_author_type'      => 'none',				// Book Author Type.
					'schema_book_author_name'      => '',					// Book Author Name.
					'schema_book_author_url'       => '',					// Book Author URL.
					'schema_book_pub_date'         => '',					// Book Published Date.
					'schema_book_pub_time'         => 'none',				// Book Published Time.
					'schema_book_pub_timezone'     => $def_timezone,			// Book Published Timezone.
					'schema_book_created_date'     => '',					// Book Created Date.
					'schema_book_created_time'     => 'none',				// Book Created Time.
					'schema_book_created_timezone' => $def_timezone,			// Book Created Timezone.
					'schema_book_edition'          => '',					// Book Edition.
					'schema_book_format'           => $opts[ 'schema_def_book_format' ],	// Book Format.
					'schema_book_pages'            => '',					// Number of Pages.
					'schema_book_isbn'             => '',					// Book ISBN.

					/**
					 * Schema Book > Audiobook.
					 */
					'schema_book_audio_duration_days'  => 0,	// Audiobook Duration (Days).
					'schema_book_audio_duration_hours' => 0,	// Audiobook Duration (Hours).
					'schema_book_audio_duration_mins'  => 0,	// Audiobook Duration (Mins).
					'schema_book_audio_duration_secs'  => 0,	// Audiobook Duration (Secs).

					/**
					 * Schema Event.
					 */
					'schema_event_lang'                  => $def_lang,						// Event Language.
					'schema_event_attendance'            => $opts[ 'schema_def_event_attendance' ],			// Event Attendance.
					'schema_event_online_url'            => '',							// Event Online URL.
					'schema_event_location_id'           => $opts[ 'schema_def_event_location_id' ],		// Event Physical Venue.
					'schema_event_organizer_org_id'      => $opts[ 'schema_def_event_organizer_org_id' ],		// Organizer Org.
					'schema_event_organizer_person_id'   => $opts[ 'schema_def_event_organizer_person_id' ],	// Organizer Person.
					'schema_event_performer_org_id'      => $opts[ 'schema_def_event_performer_org_id' ],		// Performer Org.
					'schema_event_performer_person_id'   => $opts[ 'schema_def_event_performer_person_id' ],	// Performer Person.
					'schema_event_status'                => 'https://schema.org/EventScheduled',			// Event Status.
					'schema_event_start_date'            => '',							// Event Start (Date).
					'schema_event_start_time'            => 'none',							// Event Start (Time).
					'schema_event_start_timezone'        => $def_timezone,						// Event Start (Timezone).
					'schema_event_end_date'              => '',							// Event End (Date).
					'schema_event_end_time'              => 'none',							// Event End (Time).
					'schema_event_end_timezone'          => $def_timezone,						// Event End (Timezone).
					'schema_event_previous_date'         => '',							// Event Previous Start (Date).
					'schema_event_previous_time'         => 'none',							// Event Previous Start (Time).
					'schema_event_previous_timezone'     => $def_timezone,						// Event Previous Start (Timezone).
					'schema_event_offers_start_date'     => '',							// Event Offers Start (Date.
					'schema_event_offers_start_time'     => 'none',							// Event Offers Start (Time.
					'schema_event_offers_start_timezone' => $def_timezone,						// Event Offers Start (Timezone.
					'schema_event_offers_end_date'       => '',							// Event Offers End (Date).
					'schema_event_offers_end_time'       => 'none',							// Event Offers End (Time).
					'schema_event_offers_end_timezone'   => $def_timezone,						// Event Offers End (Timezone).

					/**
					 * Schema How-To.
					 */
					'schema_howto_prep_days'   => 0,	// How-To Preparation Time (Days).
					'schema_howto_prep_hours'  => 0,	// How-To Preparation Time (Hours).
					'schema_howto_prep_mins'   => 0,	// How-To Preparation Time (Mins).
					'schema_howto_prep_secs'   => 0,	// How-To Preparation Time (Secs).
					'schema_howto_total_days'  => 0,	// How-To Total Time (Days).
					'schema_howto_total_hours' => 0,	// How-To Total Time (Hours).
					'schema_howto_total_mins'  => 0,	// How-To Total Time (Mins).
					'schema_howto_total_secs'  => 0,	// How-To Total Time (Secs).
					'schema_howto_yield'       => '',	// How-To Yield.

					/**
					 * Schema Job Posting.
					 */
					'schema_job_title'                => '',					// Job Title.
					'schema_job_hiring_org_id'        => $opts[ 'schema_def_job_hiring_org_id' ],	// Job Hiring Organization.
					'schema_job_location_id'          => $opts[ 'schema_def_job_location_id' ],	// Job Location.
					'schema_job_location_type'        => $opts[ 'schema_def_job_location_type' ],	// Job Location Type.
					'schema_job_salary'               => '',					// Job Base Salary.
					'schema_job_salary_currency'      => $def_currency,				// Job Base Salary Currency.
					'schema_job_salary_period'        => 'year',					// Job Base Salary per Year, Month, Week, Hour.
					'schema_job_empl_type_FULL_TIME'  => 0,						// Job Employment Type.
					'schema_job_empl_type_PART_TIME'  => 0,
					'schema_job_empl_type_CONTRACTOR' => 0,
					'schema_job_empl_type_TEMPORARY'  => 0,
					'schema_job_empl_type_INTERN'     => 0,
					'schema_job_empl_type_VOLUNTEER'  => 0,
					'schema_job_empl_type_PER_DIEM'   => 0,
					'schema_job_empl_type_OTHER'      => 0,
					'schema_job_expire_date'          => '',					// Job Posting Expires (Date).
					'schema_job_expire_time'          => 'none',					// Job Posting Expires (Time).
					'schema_job_expire_timezone'      => $def_timezone,				// Job Posting Expires (Timezone).

					/**
					 * Schema Movie.
					 */
					'schema_movie_prodco_org_id'     => 'none',		// Movie Production Company.
					'schema_movie_released_date'     => '',			// Movie Release Date.
					'schema_movie_released_time'     => 'none',		// Movie Release Time.
					'schema_movie_released_timezone' => $def_timezone,	// Movie Release Timezone.
					'schema_movie_duration_days'     => 0,			// Movie Runtime (Days).
					'schema_movie_duration_hours'    => 0,			// Movie Runtime (Hours).
					'schema_movie_duration_mins'     => 0,			// Movie Runtime (Mins).
					'schema_movie_duration_secs'     => 0,			// Movie Runtime (Secs).

					/**
					 * Schema Organization.
					 */
					'schema_organization_id' => 'none',	// Organization.

					/**
					 * Schema Person.
					 */
					'schema_person_id' => 'none',	// Person.

					/**
					 * Schema Place.
					 */
					'schema_place_id' => 'none',	// Place.

					/**
					 * Schema QA Page.
					 */
					'schema_qa_desc' => '',		// QA Heading.

					/**
					 * Schema Recipe.
					 */
					'schema_recipe_cook_method' => '',	// Recipe Cooking Method.
					'schema_recipe_course'      => '',	// Recipe Course.
					'schema_recipe_cuisine'     => '',	// Recipe Cuisine.
					'schema_recipe_prep_days'   => 0,	// Recipe Preparation Time (Days).
					'schema_recipe_prep_hours'  => 0,	// Recipe Preparation Time (Hours).
					'schema_recipe_prep_mins'   => 0,	// Recipe Preparation Time (Mins).
					'schema_recipe_prep_secs'   => 0,	// Recipe Preparation Time (Secs).
					'schema_recipe_cook_days'   => 0,	// Recipe Cooking Time (Days).
					'schema_recipe_cook_hours'  => 0,	// Recipe Cooking Time (Hours).
					'schema_recipe_cook_mins'   => 0,	// Recipe Cooking Time (Mins).
					'schema_recipe_cook_secs'   => 0,	// Recipe Cooking Time (Secs).
					'schema_recipe_total_days'  => 0,	// How-To Total Time (Days).
					'schema_recipe_total_hours' => 0,	// How-To Total Time (Hours).
					'schema_recipe_total_mins'  => 0,	// How-To Total Time (Mins).
					'schema_recipe_total_secs'  => 0,	// How-To Total Time (Secs).

					/**
					 * Schema Recipe - Nutrition Information.
					 */
					'schema_recipe_nutri_serv'      => '',	// Serving Size.
					'schema_recipe_nutri_cal'       => '',	// Calories.
					'schema_recipe_nutri_prot'      => '',	// Protein.
					'schema_recipe_nutri_fib'       => '',	// Fiber.
					'schema_recipe_nutri_carb'      => '',	// Carbohydrates.
					'schema_recipe_nutri_sugar'     => '',	// Sugar.
					'schema_recipe_nutri_sod'       => '',	// Sodium.
					'schema_recipe_nutri_fat'       => '',	// Fat.
					'schema_recipe_nutri_trans_fat' => '',	// Trans Fat.
					'schema_recipe_nutri_sat_fat'   => '',	// Saturated Fat.
					'schema_recipe_nutri_unsat_fat' => '',	// Unsaturated Fat.
					'schema_recipe_nutri_chol'      => '',	// Cholesterol.
					'schema_recipe_yield'           => '',	// Recipe Yield.

					/**
					 * Schema Review.
					 */
					'schema_review_rating'          => '0.0',	// Review Rating.
					'schema_review_rating_from'     => '1',		// Review Rating (From).
					'schema_review_rating_to'       => '5',		// Review Rating (To).
					'schema_review_rating_alt_name' => '',		// Rating Value Name.

					/**
					 * Schema Reviewed Subject.
					 */
					'schema_review_item_name' => '',					// Subject Name.
					'schema_review_item_desc' => '',					// Subject Description.
					'schema_review_item_type' => $opts[ 'schema_def_review_item_type' ],	// Subject Webpage Type.
					'schema_review_item_url'  => '',					// Subject Webpage URL.

					/**
					 * Schema Reviewed Subject: Creative Work.
					 */
					'schema_review_item_cw_author_type'      => 'none',		// Subject Author Type.
					'schema_review_item_cw_author_name'      => '',			// Subject Author Name.
					'schema_review_item_cw_author_url'       => '',			// Subject Author URL.
					'schema_review_item_cw_pub_date'         => '',			// Subject Published Date.
					'schema_review_item_cw_pub_time'         => 'none',		// Subject Published Time.
					'schema_review_item_cw_pub_timezone'     => $def_timezone,	// Subject Published Timezone.
					'schema_review_item_cw_created_date'     => '',			// Subject Created Date.
					'schema_review_item_cw_created_time'     => 'none',		// Subject Created Time.
					'schema_review_item_cw_created_timezone' => $def_timezone,	// Subject Created Timezone.

					/**
					 * Schema Reviewed Subject: Book.
					 */
					'schema_review_item_cw_book_isbn' => '',	// Subject Book ISBN.

					/**
					 * Schema Reviewed Subject: Product.
					 */
					'schema_review_item_product_brand'            => '',	// Product Brand.
					'schema_review_item_product_retailer_part_no' => '',	// Product SKU.
					'schema_review_item_product_mfr_part_no'      => '',	// Product MPN.

					/**
					 * Schema Reviewed Subject: Software Application.
					 */
					'schema_review_item_software_app_cat' => '',	// Application Category.
					'schema_review_item_software_app_os'  => '',	// Operating System.

					/**
					 * Schema Claim Review.
					 */
					'schema_review_claim_reviewed'  => '',	// Short Summary of Claim.
					'schema_review_claim_first_url' => '',	// First Appearance URL.

					/**
					 * Schema Software Application.
					 */
					'schema_software_app_os'  => '',	// Operating System.
					'schema_software_app_cat' => '',	// Application Category.
				);

				/**
				 * Set before calling filters to prevent recursion.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'setting opt_filtered to 1' );
				}

				$md_defs[ 'opt_filtered' ] = 1;

				/**
				 * See https://developers.google.com/search/reference/robots_meta_tag.
				 */
				$directives = SucomUtilRobots::get_default_directives();

				foreach ( $directives as $directive_key => $directive_value ) {

					$opt_key = str_replace( '-', '_', 'robots_' . $directive_key );	// Convert dashes to underscores.

					/**
					 * Use a default value from the plugin settings, if one exists.
					 */
					if ( isset( $this->p->options[ $opt_key ] ) ) {

						$md_defs[ $opt_key ] = $this->p->options[ $opt_key ];

					/**
					 * Save as a checkbox:
					 *
					 *	'robots_noarchive'
					 *	'robots_nofollow'
					 *	'robots_noimageindex'
					 *	'robots_noindex'
					 *	'robots_nosnippet'
					 *	'robots_notranslate'
					 */
					} elseif ( 0 === strpos( $directive_key, 'no' ) && is_bool( $directive_value ) ) {

						$md_defs[ $opt_key ] = $directive_value ? 1 : 0;

					/**
					 * Save the directive value:
					 *
					 *	'robots_max_snippet'
					 *	'robots_max_image_preview'
					 *	'robots_max_video_preview'
					 */
					} elseif ( 0 === strpos( $directive_key, 'max' ) ) {

						$md_defs[ $opt_key ] = $directive_value;
					}
				}

				/**
				 * Since WPSSO Core v9.5.0.
				 *
				 * Overwrite the default options with any custom options from the parent.
				 */
				$parent_opts = $this->get_inherited_md_opts( $mod );

				if ( ! empty( $parent_opts ) ) {

					$md_defs = array_merge( $md_defs, $parent_opts );
				}

				/**
				 * The 'import_custom_fields' filter is executed before the 'wpsso_get_md_options' and
				 * 'wpsso_get_post_options' filters, so values retrieved from custom fields may get overwritten by
				 * later filters.
				 *
				 * The 'import_custom_fields' filter is also executed before the 'wpsso_get_md_defaults' and
				 * 'wpsso_get_post_defaults' filters, so submitted form values that are identical to their defaults
				 * can be removed before saving the options array.
				 *
				 * See WpssoPost->get_options().
				 * See WpssoAbstractWpMeta->get_defaults().
				 * See WpssoUtilCustomFields->filter_import_custom_fields().
				 * See WpssoProEcomWoocommerce->add_mt_product() - imports variation metadata.
				 * See WpssoProEcomWooAddGtin->filter_wc_variation_cf_meta_keys().
				 */
				if ( 'post' === $mod[ 'name' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying import_custom_fields filters for post ID ' . $mod[ 'id' ] . ' metadata' );
					}

					$md_defs = apply_filters( 'wpsso_import_custom_fields', $md_defs, get_post_meta( $mod[ 'id' ] ) );
				}

				/**
				 * Since WPSSO Core v3.28.0.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying get_md_defaults filters' );
				}

				$md_defs = apply_filters( 'wpsso_get_md_defaults', $md_defs, $mod );

				if ( ! empty( $mod[ 'name' ] ) ) {

					$filter_name = SucomUtil::sanitize_hookname( 'wpsso_get_' . $mod[ 'name' ] . '_defaults' );

					$md_defs = apply_filters( $filter_name, $md_defs, $mod[ 'id' ], $mod[ 'term_tax_id' ], $mod );
				}

				/**
				 * Since WPSSO Core v8.2.0.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying sanitize_md_defaults filters' );
				}

				$md_defs = apply_filters( 'wpsso_sanitize_md_defaults', $md_defs, $mod );

				/**
				 * If caching is not allowed yet, re-apply the filters on next method call.
				 */
				if ( ! WpssoOptions::is_cache_allowed() ) {

					unset( $md_defs[ 'opt_filtered' ] );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'get_md_defaults filters skipped' );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'md_defs', $md_defs );
			}

			if ( false !== $md_key ) {

				if ( isset( $md_defs[ $md_key ] ) ) {

					return $md_defs[ $md_key ];
				}

				return null;
			}

			return $md_defs;
		}

		public function get_options( $obj_id, $md_key = false, $filter_opts = true, $merge_defs = false ) {

			$ret_val = false === $md_key ? array() : null;	// Allow for $md_key = 0.

			return self::must_be_extended( $ret_val );
		}

		protected function upgrade_options( array $md_opts, $obj_id ) {

			/**
			 * Get the current options version number for checks to follow.
			 */
			$prev_version = $this->p->opt->get_version( $md_opts, 'wpsso' );	// Returns 'opt_version'.

			/**
			 * Maybe renamed some option keys.
			 */
			$version_keys = apply_filters( 'wpsso_rename_md_options_keys', self::$rename_keys_by_ext );

			$md_opts = $this->p->util->rename_options_by_ext( $md_opts, $version_keys );

			/**
			 * Check for schema type IDs that need to be renamed.
			 */
			$schema_type_keys_preg = '/^(schema_type|place_schema_type|plm_place_schema_type)(_[0-9]+)?$/';

			foreach ( SucomUtil::preg_grep_keys( $schema_type_keys_preg, $md_opts ) as $md_key => $md_val ) {

				if ( ! empty( $this->p->cf[ 'head' ][ 'schema_renamed' ][ $md_val ] ) ) {

					$md_opts[ $md_key ] = $this->p->cf[ 'head' ][ 'schema_renamed' ][ $md_val ];
				}
			}

			/**
			 * Import and delete deprecated robots post metadata.
			 */
			if ( $prev_version > 0 && $prev_version <= 759 ) {

				foreach ( array(
					'noarchive',
					'nofollow',
					'noimageindex',
					'noindex',
					'nosnippet',
					'notranslate',
				) as $directive_key ) {

					$opt_key  = 'robots_' . $directive_key;
					$meta_key = '_wpsso_' . $directive_key;

					$directive_value = static::get_meta( $obj_id, $meta_key, $single = true );	// Use static method from child.

					if ( '' !== $directive_value ) {

						$md_opts[ $opt_key ] = (int) $directive_value;

						static::delete_meta( $obj_id, $meta_key );	// Use static method from child.
					}
				}
			}

			if ( $prev_version > 0 && $prev_version <= 902 ) {

				/**
				 * If there is a multilingual plugin available, trust the plugin and ignore any previous /
				 * inherited custom language value.
				 */
				if ( $this->p->avail[ 'lang' ][ 'any' ] ) {

					unset( $md_opts[ 'schema_lang' ] );
				}
			}

			if ( $prev_version > 0 && $prev_version <= 917 ) {

				if ( ! empty( $md_opts[ 'product_target_gender' ] ) ) {

					$md_opts[ 'product_target_gender' ] = strtolower( $md_opts[ 'product_target_gender' ] );
				}
			}

			$md_opts = (array) apply_filters( 'wpsso_upgraded_md_options', $md_opts );

			/**
			 * Add plugin and add-on option versions (ie. 'checksum', 'opt_checksum', and 'opt_versions').
			 */
			$this->p->opt->add_versions( $md_opts );	// Note that $md_opts must be an array.

			return $md_opts;
		}

		/**
		 * Do not pass $md_opts by reference as the options array may get merged with default values.
		 */
		protected function return_options( $obj_id, array $md_opts, $md_key = false, $merge_defs = false ) {

			/**
			 * If there is a multilingual plugin available, trust the plugin and ignore any previous / inherited custom
			 * language value.
			 */
			if ( $this->p->avail[ 'lang' ][ 'any' ] ) {

				unset( $md_opts[ 'schema_lang' ] );
			}

			if ( $merge_defs ) {

				$md_opts = $this->merge_defaults( $obj_id, $md_opts );
			}

			if ( false !== $md_key ) {

				if ( ! isset( $md_opts[ $md_key ] ) || '' === $md_opts[ $md_key ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'returning null value: ' . $md_key . ' not set or empty string' );
					}

					return null;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning meta value: ' . $md_key . ' = ' . $md_opts[ $md_key ] );
				}

				return $md_opts[ $md_key ];
			}

			return $md_opts;
		}

		protected function merge_defaults( $obj_id, array $md_opts ) {

			if ( empty( $md_opts[ 'options_merged' ] ) ) {

				$md_defs = $this->get_defaults( $obj_id );

				if ( is_array( $md_defs ) ) {	// Just in case.

					foreach ( $md_defs as $md_defs_key => $md_defs_val ) {

						if ( ! isset( $md_opts[ $md_defs_key ] ) && $md_defs_val !== '' ) {

							$md_opts[ $md_defs_key ] = $md_defs[ $md_defs_key ];
						}
					}
				}

				$md_opts[ 'options_merged' ] = true;
			}

			return $md_opts;
		}

		/**
		 * Extended by WpssoPost->save_options( $post_id, $rel = false );
		 * Extended by WpssoTerm->save_options( $term_id, $term_tax_id = false );
		 * Extended by WpssoUser->save_options( $user_id, $rel = false );
		 */
		public function save_options( $obj_id, $rel = false ) {

			return self::must_be_extended( $ret_val = false );
		}

		/**
		 * Extended by WpssoPost->delete_options( $post_id, $rel = false );
		 * Extended by WpssoTerm->delete_options( $term_id, $term_tax_id = false );
		 * Extended by WpssoUser->delete_options( $user_id, $rel = false );
		 */
		public function delete_options( $obj_id, $rel = false ) {

			return self::must_be_extended( $ret_val = false );
		}

		/**
		 * Get all publicly accessible post, term, or user IDs.
		 */
		public static function get_public_ids() {

			return self::must_be_extended( $ret_val = array() );
		}

		/**
		 * Return an array of post mods for a given $mod object.
		 *
		 * Called by WpssoPage->get_posts_mods().
		 */
		public function get_posts_mods( array $mod ) {

			$posts_mods = array();

			$post_ids = $this->get_posts_ids( $mod );

			foreach ( $post_ids as $post_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting mod for post object ID ' . $post_id );
				}

				$posts_mods[] = $this->p->post->get_mod( $post_id );
			}

			return $posts_mods;
		}

		/**
		 * Return an array of post IDs for a given $mod object.
		 *
		 * Called by WpssoAbstractWpMeta->get_posts_mods().
		 */
		public function get_posts_ids( array $mod ) {

			return self::must_be_extended( $ret_val = array() );	// Return an empty array.
		}

		public function ajax_get_metabox_document_meta() {

			self::must_be_extended();

			die( -1 );	// Nothing to do.
		}

		public function show_metabox_document_meta( $obj ) {

			echo $this->get_metabox_document_meta( $obj );
		}

		public function get_metabox_document_meta( $obj ) {

			return self::must_be_extended( $ret_val = '' );	 // Empty html.
		}

		public function get_metabox_javascript( $container_id ) {

			$doing_ajax   = SucomUtilWP::doing_ajax();
			$container_id = empty( $container_id ) ? '' : '#' . $container_id;
			$metabox_html = '';

			if ( $doing_ajax ) {

				/**
				 * Provide a parent container_id value to initialize a single metabox (when loading a single
				 * metabox via ajax, for example).
				 */
				$metabox_html .= '<!-- init metabox javascript for ajax call -->' . "\n" .
					'<script>wpssoInitMetabox( \'' . $container_id . '\', true );</script>' . "\n";
			}

			return $metabox_html;
		}

		public function load_meta_page( $screen = false ) {

			return self::must_be_extended();
		}

		protected function check_head_info( $mod ) {

			$canonical_url = $this->p->util->maybe_set_ref( $canonical_url = null, $mod, __( 'checking meta tags', 'wpsso' ) );

			foreach ( array( 'image', 'description' ) as $mt_suffix ) {

				if ( empty( self::$head_info[ 'og:' . $mt_suffix ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'og:' . $mt_suffix . ' meta tag is value empty and required' );
					}

					/**
					 * An is_admin() test is required to make sure the WpssoMessages class is available.
					 */
					if ( $this->p->notice->is_admin_pre_notices() ) {

						$notice_msg = $this->p->msgs->get( 'notice-missing-og-' . $mt_suffix );

						$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-og-' . $mt_suffix;

						$this->p->notice->err( $notice_msg, null, $notice_key );
					}
				}
			}

			$this->p->util->maybe_unset_ref( $canonical_url );

			do_action( 'wpsso_check_head_info', self::$head_info, $mod, $canonical_url );
		}

		/**
		 * Extended by WpssoPost->add_meta_boxes( $post_type, $post_obj = false );
		 * Extended by WpssoTerm->add_meta_boxes( $term_obj, $tax_slug = false );
		 * Extended by WpssoUser->add_meta_boxes( $user_obj, $rel = false );
		 */
		public function add_meta_boxes( $obj, $rel = false ) {

			return self::must_be_extended();
		}

		/**
		 * Does this page have a Document SSO metabox?
		 *
		 * If this is a post/term/user editing page, then the self::$head_tags variable will be an array.
		 */
		public static function is_meta_page() {

			if ( is_array( self::$head_tags ) ) {

				return true;
			}

			return false;
		}

		public static function get_head_tags() {

			return self::$head_tags;
		}

		protected function get_document_meta_tabs( $metabox_id, array $mod ) {

			$tabs = array();

			switch ( $metabox_id ) {

				case $this->p->cf[ 'meta' ][ 'id' ]:	// 'sso' metabox ID.

					$tabs[ 'edit_general' ] = _x( 'Edit General', 'metabox tab', 'wpsso' );
					$tabs[ 'edit_schema' ]  = _x( 'Edit Schema', 'metabox tab', 'wpsso' );
					$tabs[ 'edit_media' ]   = _x( 'Edit Media', 'metabox tab', 'wpsso' );

					if ( $mod[ 'is_public' ] ) {

						$tabs[ 'edit_visibility' ] = _x( 'Edit Visibility', 'metabox tab', 'wpsso' );
						$tabs[ 'prev_social' ]     = _x( 'Preview Social', 'metabox tab', 'wpsso' );
						$tabs[ 'prev_oembed' ]     = _x( 'Preview oEmbed', 'metabox tab', 'wpsso' );
						$tabs[ 'validators' ]      = _x( 'Validators', 'metabox tab', 'wpsso' );
					}

					break;
			}

			/**
			 * Exclude the 'Edit Media' tab from attachment editing pages.
			 */
			if ( $mod[ 'is_attachment' ] ) {

				unset( $tabs[ 'edit_media' ] );
			}

			/**
			 * Exclude the 'oEmbed' tab from non-post editing pages.
			 *
			 * get_oembed_response_data() is available since WP v4.4.
			 */
			if ( ! function_exists( 'get_oembed_response_data' ) ||	! $mod[ 'is_post' ] || ! $mod[ 'id' ] ) {

				unset( $tabs[ 'prev_oembed' ] );
			}

			return apply_filters( 'wpsso_' . $mod[ 'name' ] . '_document_meta_tabs', $tabs, $mod, $metabox_id );
		}

		/**
		 * Called by WpssoAbstractWpMeta->check_sortable_meta().
		 * Called by WpssoOembed->post_oembed_response_data().
		 * Called by WpssoOembed->post_oembed_response_data_rich.
		 * Called by WpssoOembed->the_embed_thumbnail_url().
		 * Called by WpssoOembed->the_embed_thumbnail_url_image_shape().
		 * Called by WpssoOembed->the_embed_thumbnail_id().
		 * Called by WpssoOembed->the_embed_excerpt().
		 */
		public function get_head_info( $mixed, $read_cache = true ) {

			static $local_cache = array();

			if ( is_array( $mixed ) ) {

				$mod =& $mixed;

			} else {

				$mod = $this->get_mod( $mixed );
			}

			if ( $read_cache ) {

				if ( isset( $local_cache[ $mod[ 'id' ] ] ) ) {

					return $local_cache[ $mod[ 'id' ] ];
				}
			}

			$head_tags = $this->p->head->get_head_array( $use_post = false, $mod, $read_cache );

			$head_info = $this->p->head->extract_head_info( $head_tags, $mod );

			return $local_cache[ $mod[ 'id' ] ] = $head_info;
		}

		/**
		 * Called by WpssoHead->extract_head_info().
		 */
		public function get_head_info_thumb_bg_img( $head_info, $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting thumbnail image' );	// Begin timer.
			}

			$media_html = '';

			if ( empty( $head_info[ $mt_pre . ':image:id' ] ) ) {

				$media_html .= '<!-- checking head info array for image URL -->';

			} else {

				$pid = $head_info[ $mt_pre . ':image:id' ];

				$size_name = 'thumbnail';

				/**
				 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
				 *
				 * Example:
				 *
				 * 	array(
				 *		$mt_pre . ':image:url'        => '',
				 *		$mt_pre . ':image:width'      => '',
				 *		$mt_pre . ':image:height'     => '',
				 *		$mt_pre . ':image:cropped'    => '',	// Non-standard / internal meta tag.
				 *		$mt_pre . ':image:id'         => '',	// Non-standard / internal meta tag.
				 *		$mt_pre . ':image:alt'        => '',
				 *		$mt_pre . ':image:size_name'  => '',	// Non-standard / internal meta tag.
				 * 	);
				 */
				$mt_single_image = $this->p->media->get_mt_single_image_src( $pid, $size_name, $mt_pre );

				$media_html .= '<!-- getting ' . $size_name . ' size for image ID ' . $pid . ' = ';

				if ( empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {	// Just in case.

					$media_html .= 'failed';

				} else {

					$media_html .= 'success';

					$head_info =& $mt_single_image;	// Use the updated image information.
				}

				$media_html .= ' -->';
			}

			$image_url = SucomUtil::get_first_mt_media_url( $head_info );

			if ( empty( $image_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no thumbnail image URL' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'thumbnail image URL = ' . $image_url );
				}

				$media_html .= '<div class="wp-thumb-bg-img" style="background-image:url(' . $image_url . ');"></div><!-- .wp-thumb-bg-img -->';
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting thumbnail image' );	// End timer.
			}

			return $media_html;
		}

		/**
		 * Return a specific option from the custom social settings meta with fallback for multiple option keys. If $md_key
		 * is an array, then get the first non-empty option from the options array. This is an easy way to provide a
		 * fallback value for the first array key. Use 'none' as a key name to skip this fallback behavior.
		 *
		 * Example: get_options_multi( $obj_id, array( 'seo_desc', 'og_desc' ) );
		 */
		public function get_options_multi( $obj_id, $md_key = false, $filter_opts = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'obj_id'      => $obj_id,
					'md_key'      => $md_key,
					'filter_opts' => $filter_opts,
				) );
			}

			if ( empty( $obj_id ) ) {

				return null;
			}

			if ( false === $md_key ) {	// Return the whole options array.

				$md_val = $this->get_options( $obj_id, $md_key, $filter_opts );

			} elseif ( true === $md_key ) {	// True is invalid for a custom meta key.

				$md_val = null;

			} else {	// Return the first matching index value.

				if ( is_array( $md_key ) ) {

					$check_md_keys = array_unique( $md_key );	// Prevent duplicate key values.

				} else {

					$check_md_keys = array( $md_key );	// Convert a string to an array.
				}

				foreach ( $check_md_keys as $md_key ) {

					if ( 'none' === $md_key ) {	// Special index keyword - stop here.

						return null;

					} elseif ( empty( $md_key ) ) {	// Skip empty array keys.

						continue;

					} elseif ( is_array( $md_key ) ) {	// An array of arrays is not valid.

						continue;

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'getting id ' . $obj_id . ' option ' . $md_key . ' value' );
						}

						if ( ( $md_val = $this->get_options( $obj_id, $md_key, $filter_opts ) ) !== null ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'option ' . $md_key . ' value found (not null)' );
							}

							break;	// Stop after first match.
						}
					}
				}
			}

			if ( null !== $md_val ) {

				if ( $this->p->debug->enabled ) {

					$mod = $this->get_mod( $obj_id );

					$this->p->debug->log( 'custom ' . $mod[ 'name' ] . ' ' . ( false === $md_key ? 'options' : 
						( is_array( $md_key ) ? implode( $glue = ', ', $md_key ) : $md_key ) ) . ' = ' . 
						( is_array( $md_val ) ? print_r( $md_val, true ) : '"' . $md_val . '"' ) );
				}
			}

			return $md_val;
		}

		/**
		 * Extended by WpssoPost->clear_cache( $post_id, $rel = false );
		 * Extended by WpssoTerm->clear_cache( $term_id, $term_tax_id = false );
		 * Extended by WpssoUser->clear_cache( $user_id, $rel = false );
		 */
		public function clear_cache( $obj_id, $rel = false ) {

			return self::must_be_extended();
		}

		/**
		 * Called by WpssoPost->clear_cache().
		 * Called by WpssoTerm->clear_cache().
		 * Called by WpssoUser->clear_cache().
		 */
		protected function clear_mod_cache( array $mod ) {

			/**
			 * Clear the WpssoPage->get_the_content() cache.
			 */
			$this->p->page->clear_the_content( $mod );

			/**
			 * Clear the WpssoHead->get_head_array() cache.
			 */
			$this->p->head->clear_head_array( $mod );

			/**
			 * WordPress stores data using a post, term, or user ID, along with a group string.
			 */
			wp_cache_delete( $mod[ 'id' ], $mod[ 'name' ] . '_meta' );

			update_meta_cache( $mod[ 'name' ], $mod[ 'id' ] );

			do_action( 'wpsso_clear_mod_cache', $mod );
		}

		/**
		 * Extended by WpssoPost->user_can_save( $post_id, $rel = false );
		 * Extended by WpssoTerm->user_can_save( $term_id, $term_tax_id = false );
		 * Extended by WpssoUser->user_can_save( $user_id, $rel = false );
		 */
		public function user_can_save( $obj_id, $rel = false ) {

			return self::must_be_extended( $ret_val = false );	// Return false by default.
		}

		/**
		 * Called by WpssoPost->user_can_save();
		 * Called by WpssoTerm->user_can_save();
		 * Called by WpssoUser->user_can_save();
		 */
		protected function verify_submit_nonce() {

			if ( empty( $_POST ) ) {	// Nothing to save - nonce not required.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'submit POST is empty' );
				}

				return false;
			}

			if ( empty( $_POST[ WPSSO_NONCE_NAME ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'submit POST nonce token missing' );
				}

				return false;
			}

			if ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'submit nonce token validation failed' );
				}

				if ( is_admin() ) {

					$this->p->notice->err( __( 'Nonce token validation failed for the submitted form (update ignored).', 'wpsso' ) );
				}

				return false;

			}

			return true;
		}

		/**
		 * Merge and check submitted post, term, and user metabox options.
		 */
		protected function get_submit_opts( $mod ) {

			/**
			 * The $mod array argument is preferred but not required.
			 */
			if ( ! is_array( $mod ) ) {

				$mod = $this->get_mod( $mod );
			}

			$md_defs = $this->get_defaults( $mod[ 'id' ] );
			$md_prev = $this->get_options( $mod[ 'id' ] );

			/**
			 * Merge and sanitize the new options.
			 */
			$md_opts = empty( $_POST[ WPSSO_META_NAME ] ) ? array() : $_POST[ WPSSO_META_NAME ];
			$md_opts = SucomUtil::restore_checkboxes( $md_opts );
			$md_opts = array_merge( $md_prev, $md_opts );	// Complete the array with previous options.
			$md_opts = $this->p->opt->sanitize( $md_opts, $md_defs, $network = false, $mod );

			/**
			 * Do not save the SEO meta description if the meta description is disabled.
			 */
			if ( empty( $this->p->options[ 'add_meta_name_description' ] ) ) {

				unset( $md_opts[ 'seo_desc' ] );
			}

			/**
			 * Check image size options (id, prefix, width, height, crop, etc.).
			 */
			foreach ( array( 'og', 'pin', 'schema', 'tc_lrg', 'tc_sum' ) as $md_pre ) {

				/**
				 * If there's no image ID, then remove the image ID library prefix.
				 *
				 * If an image ID is being used, then remove the image URL (only one can be defined).
				 */
				if ( empty( $md_opts[ $md_pre . '_img_id' ] ) ) {

					unset( $md_opts[ $md_pre . '_img_id_lib' ] );

				} else {

					unset( $md_opts[ $md_pre . '_img_url' ] );
				}

				if ( empty( $md_opts[ $md_pre . '_img_url' ] ) ) {	// Just in case.

					unset( $md_opts[ $md_pre . '_img_url:width' ] );
					unset( $md_opts[ $md_pre . '_img_url:height' ] );
				}
			}

			/**
			 * Remove empty or default values.
			 *
			 * Use strict comparison to manage conversion (don't allow string to integer conversion, for example).
			 */
			foreach ( $md_opts as $md_key => $md_val ) {

				if ( '' === $md_val ) {

					unset( $md_opts[ $md_key ] );

				} elseif ( is_array( $md_val ) ) {	// ie. 'opt_versions' or other array.

					if ( isset( $md_defs[ $md_key ] ) && $md_val === $md_defs[ $md_key ] ) {

						unset( $md_opts[ $md_key ] );
					}

				} elseif ( $md_val === WPSSO_UNDEF || $md_val === (string) WPSSO_UNDEF ) {

					switch ( $md_key ) {

						case 'robots_max_snippet':
						case 'robots_max_video_preview':

							break;

						default:

							unset( $md_opts[ $md_key ] );
					}

				} elseif ( isset( $md_defs[ $md_key ] ) ) {

					if ( $md_val === $md_defs[ $md_key ] || $md_val === (string) $md_defs[ $md_key ] ) {

						unset( $md_opts[ $md_key ] );
					}
				}
			}

			/**
			 * Re-number multi options (example: schema type url, recipe ingredient, recipe instruction, etc.).
			 */
			foreach ( $this->p->cf[ 'opt' ][ 'cf_md_multi' ] as $md_multi => $is_multi ) {

				if ( empty( $is_multi ) ) {	// True, false, or array.

					continue;
				}

				/**
				 * Get multi option values indexed only by their number.
				 */
				$md_multi_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi . '_([0-9]+)$/', $md_opts, $invert = false, $replace = '$1' );

				$md_renum_opts = array();	// Start with a fresh array.

				$renum = 0;	// Start a new index at 0.

				foreach ( $md_multi_opts as $md_num => $md_val ) {

					if ( $md_val !== '' ) {	// Only save non-empty values.

						$md_renum_opts[ $md_multi . '_' . $renum ] = $md_val;
					}

					/**
					 * Check if there are linked options, and if so, re-number those options as well.
					 */
					if ( is_array( $is_multi ) ) {

						foreach ( $is_multi as $md_multi_linked ) {

							if ( isset( $md_opts[ $md_multi_linked . '_' . $md_num ] ) ) {	// Just in case.

								$md_renum_opts[ $md_multi_linked . '_' . $renum ] = $md_opts[ $md_multi_linked . '_' . $md_num ];
							}
						}
					}

					$renum++;	// Increment the new index number.
				}

				/**
				 * Remove any existing multi options, including any linked options.
				 */
				$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi . '_([0-9]+)$/', $md_opts, $invert = true );

				if ( is_array( $is_multi ) ) {

					foreach ( $is_multi as $md_multi_linked ) {

						$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi_linked . '_([0-9]+)$/', $md_opts, $invert = true );
					}
				}

				/**
				 * Save the re-numbered options.
				 */
				foreach ( $md_renum_opts as $md_key => $md_val ) {

					$md_opts[ $md_key ] = $md_val;
				}

				unset( $md_renum_opts );
			}

			/**
			 * Check for default recipe values.
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^schema_recipe_(prep|cook|total)_(days|hours|mins|secs)$/', $md_opts ) as $md_key => $value ) {

				$md_opts[ $md_key ] = (int) $value;

				if ( $md_opts[ $md_key ] === $md_defs[ $md_key ] ) {

					unset( $md_opts[ $md_key ] );
				}
			}

			/**
			 * A review rating must be greater than 0.
			 */
			if ( isset( $md_opts[ 'schema_review_rating' ] ) && $md_opts[ 'schema_review_rating' ] > 0 ) {

				/**
				 * Fallback to the default values if the from/to is empty.
				 */
				foreach ( array(
					'schema_review_rating_from',
					'schema_review_rating_to',
				) as $md_key ) {

					if ( empty( $md_opts[ $md_key ] ) && isset( $md_defs[ $md_key ] ) ) {

						$md_opts[ $md_key ] = $md_defs[ $md_key ];
					}
				}

			} else {

				foreach ( array(
					'schema_review_rating',
					'schema_review_rating_from',
					'schema_review_rating_to',
				) as $md_key ) {

					unset( $md_opts[ $md_key ] );
				}
			}

			/**
			 * Check and maybe fix missing event date, time, and timezone values.
			 */
			foreach ( array(
				'schema_event_start',
				'schema_event_end',
				'schema_event_previous',
			) as $md_pre ) {

				/**
				 * Unset date / time if same as the default value.
				 */
				foreach ( array( 'date', 'time', 'timezone' ) as $md_ext ) {

					if ( isset( $md_opts[ $md_pre . '_' . $md_ext ] ) &&
						( $md_opts[ $md_pre . '_' . $md_ext ] === $md_defs[ $md_pre . '_' . $md_ext ] ||
							$md_opts[ $md_pre . '_' . $md_ext ] === 'none' ) ) {

						unset( $md_opts[ $md_pre . '_' . $md_ext ] );
					}
				}

				if ( empty( $md_opts[ $md_pre . '_date' ] ) && empty( $md_opts[ $md_pre . '_time' ] ) ) {	// No date or time.

					unset( $md_opts[ $md_pre . '_date' ] );
					unset( $md_opts[ $md_pre . '_time' ] );
					unset( $md_opts[ $md_pre . '_timezone' ] );

				} elseif ( ! empty( $md_opts[ $md_pre . '_date' ] ) && empty( $md_opts[ $md_pre . '_time' ] ) ) {	// Date with no time.

					$md_opts[ $md_pre . '_time' ] = '00:00';

				} elseif ( empty( $md_opts[ $md_pre . '_date' ] ) && ! empty( $md_opts[ $md_pre . '_time' ] ) ) {	// Time with no date.

					if ( 'schema_event_previous' === $md_pre ) {

						unset( $md_opts[ $md_pre . '_date' ] );
						unset( $md_opts[ $md_pre . '_time' ] );
						unset( $md_opts[ $md_pre . '_timezone' ] );

					} else {

						$md_opts[ $md_pre . '_date' ] = gmdate( 'Y-m-d', time() );
					}
				}
			}

			/**
			 * Events with a previous start date must have "rescheduled" as their status.
			 *
			 * A rescheduled event, without a previous start date, is also invalid.
			 */
			if ( ! empty( $md_opts[ 'schema_event_previous_date' ] ) ) {

				$md_opts[ 'schema_event_status' ]          = 'https://schema.org/EventRescheduled';
				$md_opts[ 'schema_event_status:disabled' ] = true;

			} elseif ( isset( $md_opts[ 'schema_event_status' ] ) && 'https://schema.org/EventRescheduled' === $md_opts[ 'schema_event_status' ] ) {

				$md_opts[ 'schema_event_status' ] = 'https://schema.org/EventScheduled';
			}

			/**
			 * Check offer options to make sure they have at least a name or a price.
			 */
			$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );

			foreach( array(
				'schema_event',
				'schema_review_item_product',
				'schema_review_item_software_app',
			) as $md_pre ) {

				foreach ( range( 0, $metadata_offers_max - 1, 1 ) as $key_num ) {

					$is_valid_offer = false;

					foreach ( array(
						$md_pre . '_offer_name',
						$md_pre . '_offer_price'
					) as $md_offer_pre ) {

						if ( isset( $md_opts[ $md_offer_pre . '_' . $key_num] ) && $md_opts[ $md_offer_pre . '_' . $key_num] !== '' ) {

							$is_valid_offer = true;
						}
					}

					if ( ! $is_valid_offer ) {

						unset( $md_opts[ $md_pre . '_offer_currency_' . $key_num] );
						unset( $md_opts[ $md_pre . '_offer_avail_' . $key_num] );
					}
				}
			}

			/**
			 * Check for invalid Schema type combinations (ie. a claim review of a claim review).
			 */
			if ( isset( $md_opts[ 'schema_type' ] ) ) {	// Just in case.

				if ( 'book.audio' === $md_opts[ 'schema_type' ] ) {

					$md_opts[ 'schema_book_format' ] = 'https://schema.org/AudiobookFormat';

				} elseif ( 'review.claim' === $md_opts[ 'schema_type' ] ) {

					if ( isset( $md_opts[ 'schema_review_item_type' ] ) && 'review.claim' === $md_opts[ 'schema_review_item_type' ] ) {

						$md_opts[ 'schema_review_item_type' ] = $this->p->options[ 'schema_def_review_item_type' ];

						$notice_msg = __( 'A claim review cannot be the subject of another claim review.', 'wpsso' ) . ' ';

						$notice_msg .= __( 'Please select a subject webpage type that better describes the subject of the webpage (ie. the content) being reviewed.', 'wpsso' );

						$this->p->notice->err( $notice_msg );
					}
				}
			}

			/**
			 * Add plugin and add-on option versions (ie. 'checksum', 'opt_checksum', and 'opt_versions').
			 */
			$this->p->opt->add_versions( $md_opts );	// Note that $md_opts must be an array.

			/**
			 * Return and apply filters, like 'wpsso_save_md_options' and 'wpsso_save_post_options'.
			 */
			return $md_opts;
		}

		/**
		 * Return sortable column keys and their query sort info.
		 */
		public static function get_sortable_columns( $col_key = false ) {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$cf = WpssoConfig::get_config();

				$columns = $cf[ 'edit' ][ 'columns' ];

				$local_cache = (array) apply_filters( 'wpsso_get_sortable_columns', $columns );
			}

			if ( false !== $col_key ) {

				if ( isset( $local_cache[ $col_key ] ) ) {

					return $local_cache[ $col_key ];
				}

				return null;	// Column key not found.
			}

			return $local_cache;	// Always an array.
		}

		public static function get_column_headers() {

			$headers = array();

			$sortable_cols = self::get_sortable_columns();

			foreach ( $sortable_cols as $col_key => $col_info ) {

				if ( ! empty( $col_info[ 'header' ] ) ) {

					$headers[ $col_key ] = _x( $col_info[ 'header' ], 'column header', 'wpsso' );
				}
			}

			return $headers;
		}

		public static function get_column_meta_keys() {

			$meta_keys = array();

			$sortable_cols = self::get_sortable_columns();

			foreach ( $sortable_cols as $col_key => $col_info ) {

				if ( ! empty( $col_info[ 'meta_key' ] ) ) {

					$meta_keys[ $col_key ] = $col_info[ 'meta_key' ];
				}
			}

			return $meta_keys;
		}

		public static function get_column_by_meta_key( $meta_key ) {

			$sortable_cols = self::get_sortable_columns();

			foreach ( $sortable_cols as $col_key => $col_info ) {

				if ( ! empty( $col_info[ 'meta_key' ] ) ) {

					if ( $col_info[ 'meta_key' ] === $meta_key ) {

						return $col_info;
					}
				}
			}

			return array();
		}

		public function get_column_content( $value, $column_name, $obj_id ) {

			if ( empty( $obj_id ) || 0 !== strpos( $column_name, 'wpsso_' ) ) {	// Just in case.

				return $value;
			}

			$col_key = str_replace( 'wpsso_', '', $column_name );

			if ( $col_info = self::get_sortable_columns( $col_key ) ) {

				$mod = $this->get_mod( $obj_id );

				$value = $this->get_column_wp_cache( $mod, $col_info );	// Can return 'none' or an empty string.

				/**
				 * Callback added by WpssoRarAdmin->filter_get_sortable_columns().
				 */
				$callbacks_key = $mod[ 'name' ] . '_callbacks';

				if ( isset( $col_info[ $callbacks_key ] ) && is_array( $col_info[ $callbacks_key ] ) ) {

					foreach( $col_info[ $callbacks_key ] as $input_callback ) {

						$value = call_user_func( $input_callback, $value, $obj_id );
					}
				}

				if ( 'none' === $value ) {

					$value = '';
				}
			}

			return $value;
		}

		/**
		 * Can return 'none' or an empty string.
		 *
		 * Called by WpssoPost->get_column_content().
		 * Called by WpssoTerm->get_column_content().
		 * Called by WpssoUser->get_column_content().
		 */
		public function get_column_wp_cache( array $mod, array $col_info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$value = '';

			$locale = empty( $col_info[ 'localized' ] ) ? '' : SucomUtil::get_locale();

			if ( empty( $col_info[ 'meta_key' ] ) ) {	// Just in case.

				return $value;
			}

			/**
			 * Retrieves the cache contents from the cache by key and group.
			 */
			$metadata = $mod[ 'obj' ]->get_update_meta_cache( $mod[ 'id' ] );

			if ( ! empty( $metadata[ $col_info[ 'meta_key' ] ] ) ) {

				$value = maybe_unserialize( $metadata[ $col_info[ 'meta_key' ] ][ 0 ] );
			}

			/**
			 * $value is an empty string by default. If get_update_meta_cache() did not return a value for this meta
			 * key, then $value will still be an empty string. If this meta key is localized, and the locale key does
			 * not exist in the array, then fetch a new / updated array value.
			 */
			if ( '' === $value || ( $locale && ! isset( $value[ $locale ] ) ) ) {

				$value = static::get_meta( $mod[ 'id' ], $col_info[ 'meta_key' ], $single = true );	// Use static method from child.
			}

			if ( $locale ) {	// Values stored by locale.

				$value = isset( $value[ $locale ] ) ? $value[ $locale ] : '';
			}

			return (string) $value;
		}

		/**
		 * Filters all post, term, and user metadata.
		 */
		public function check_sortable_meta( $value, $obj_id, $meta_key, $single ) {

			if ( empty( $obj_id ) || 0 !== strpos( $meta_key, '_wpsso_head_info_' ) ) {

				return $value;
			}

			$mod = $this->get_mod( $obj_id );

			$mod_salt = SucomUtil::get_mod_salt( $mod );

			static $local_is_recursion = array();

			if ( ! empty( $local_is_recursion[ $mod_salt ][ $meta_key ] ) ) {

				return $value;	// Return null.
			}

			$col_info = self::get_column_by_meta_key( $meta_key );

			if ( ! empty( $col_info ) ) {

				$local_is_recursion[ $mod_salt ][ $meta_key ] = true;	// Prevent recursion.

				$metadata = static::get_meta( $obj_id, $meta_key, $single = true );	// Use static method from child.

				$do_head_info = false;

				if ( '' === $metadata ) {

					$do_head_info = true;

				} elseif ( ! empty( $col_info[ 'localized' ] ) ) {	// Values stored by locale.

					$locale = SucomUtil::get_locale();

					if ( empty( $metadata[ $locale ] ) ) {

						$do_head_info = true;
					}
				}

				if ( $do_head_info ) {

					$this->get_head_info( $mod, $read_cache = true );
				}

				unset( $local_is_recursion[ $mod_salt ][ $meta_key ] );
			}

			return $value;
		}

		public function update_sortable_meta( $obj_id, $col_key, $content ) {

			if ( empty( $obj_id ) ) {	// Just in case.

				return;
			}

			if ( $col_info = self::get_sortable_columns( $col_key ) ) {

				if ( ! empty( $col_info[ 'meta_key' ] ) ) {	// Just in case.

					if ( ! empty( $col_info[ 'localized' ] ) ) {	// Values stored by locale.

						$locale   = SucomUtil::get_locale();
						$content  = array( $locale => $content );
						$metadata = static::get_meta( $obj_id, $col_info[ 'meta_key' ], $single = true );

						if ( is_array( $metadata ) ) {

							$content = array_merge( $metadata, $content );
						}
					}

					static::update_meta( $obj_id, $col_info[ 'meta_key' ], $content );	// Use static method from child.
				}
			}
		}

		public function add_sortable_columns( $columns ) {

			$sortable_cols = self::get_sortable_columns();

			foreach ( $sortable_cols as $col_key => $col_info ) {

				if ( ! empty( $col_info[ 'orderby' ] ) ) {

					$columns[ 'wpsso_' . $col_key ] = 'wpsso_' . $col_key;
				}
			}

			return $columns;
		}

		public function set_column_orderby( $query ) {

			$col_name = $query->get( 'orderby' );

			if ( is_string( $col_name ) && strpos( $col_name, 'wpsso_' ) === 0 ) {

				$col_key = str_replace( 'wpsso_', '', $col_name );

				if ( $col_info = self::get_sortable_columns( $col_key ) ) {

					foreach ( array( 'meta_key', 'orderby' ) as $set_name ) {

						if ( ! empty( $col_info[ $set_name ] ) ) {

							$query->set( $set_name, $col_info[ $set_name ] );
						}
					}
				}
			}
		}

		/**
		 * Called by WpssoPost->add_post_column_headings().
		 * Called by WpssoPost->add_media_column_headings().
		 * Called by WpssoTerm->add_term_column_headings().
		 * Called by WpssoUser->add_user_column_headings().
		 */
		protected function add_column_headings( $columns, $opt_suffix ) {

			foreach ( self::get_column_headers() as $col_key => $col_header ) {

				/**
				 * Check if the column is enabled globally for the post, media, term, or user edit list.
				 */
				if ( ! empty( $this->p->options[ 'plugin_' . $col_key . '_col_' . $opt_suffix ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding wpsso_' . $col_key . ' column' );
					}

					$columns[ 'wpsso_' . $col_key ] = $col_header;
				}
			}

			return $columns;
		}

		/**
		 * Retrieves or updates the metadata cache by key and group.
		 */
		public function get_update_meta_cache( $obj_id ) {

			return self::must_be_extended( $ret_val = array() );	// Return an empty array.
		}

		/**
		 * Return merged custom options from the post or term parents.
		 *
		 * Called by WpssoAbstractWpMeta->get_defaults(), WpssoPost->get_options(), and WpssoTerm->get_options().
		 */
		public function get_inherited_md_opts( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();

				$this->p->debug->log( $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' is ' . ( $mod[ 'is_public' ] ? 'public' : 'private' ) );
			}

			$md_opts = array();

			/**
			 * Filter 'wpsso_inherit_md_opts' added in WPSSO Core v12.2.0.
			 */
			$inherit_opts = $this->p->cf[ 'form' ][ 'inherit_md_opts' ];
			$inherit_opts = apply_filters( 'wpsso_inherit_md_opts', $inherit_opts, $mod );

			/**
			 * Filter 'wpsso_inherit_custom_images' added in WPSSO Core v9.10.0.
			 *
			 * Note that by default only public children can inherit parent images.
			 *
			 * Use add_filter( 'wpsso_inherit_custom_images', '__return_true' ) to inherit images for private children.
			 */
			$inherit_custom = empty( $this->p->options[ 'plugin_inherit_custom' ] ) ? false : $mod[ 'is_public' ];
			$inherit_custom = apply_filters( 'wpsso_inherit_custom_images', $inherit_custom, $mod );

			if ( $inherit_custom ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'inherit md images for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' is enabled' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'inherit md images for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' is disabled' );
				}

				$inherit_opts = SucomUtil::preg_grep_keys( '/_img_/', $inherit_opts, $invert = true );
			}

			if ( $mod[ 'is_post' ] ) {

				/**
				 * $object_type = The type of object for which we'll be retrieving ancestors. Accepts a post type or a taxonomy name. 
				 *
				 * $resource_type = Type of resource $object_type is. Accepts 'post_type' or 'taxonomy'.
				 */
				$parent_ids = get_ancestors( $mod[ 'id' ], $object_type = $mod[ 'post_type' ], $resource_type = 'post_type' );

			} elseif ( $mod[ 'is_term' ] ) {

				$parent_ids = get_ancestors( $mod[ 'id' ], $object_type = $mod[ 'tax_slug' ], $resource_type = 'taxonomy' );

			} else {

				$parent_ids = array();
			}

			/**
			 * Merge the custom options array top-down, so we merge the closest parent last.
			 */
			if ( empty( $parent_ids ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no parent ids for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] );
				}

			} elseif ( empty( $inherit_opts ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no inherit opts for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] );
				}

			} else {

				$parent_ids = array_reverse( $parent_ids );

				foreach ( $parent_ids as $parent_id ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' parent id ' . $parent_id . ' metadata' );
					}

					$metadata = $mod[ 'obj' ]->get_update_meta_cache( $parent_id );

					if ( isset( $metadata[ WPSSO_META_NAME ][ 0 ] ) ) {

						$parent_opts = maybe_unserialize( $metadata[ WPSSO_META_NAME ][ 0 ] );

						$parent_opts = array_intersect_key( $parent_opts, $inherit_opts );

						$md_opts = array_merge( $md_opts, $parent_opts );
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'inherit_opts', $inherit_opts );
					$this->p->debug->log_arr( 'md_opts', $md_opts );
				}
			}

			return $md_opts;
		}

		/**
		 * Returns an array of single image associative arrays.
		 *
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 *
		 * $md_pre can be a text string or array of prefixes.
		 */
		public function get_md_images( $num, $size_names, array $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'num'        => $num,
					'size_names' => $size_names,
					'mod'        => $mod,
					'md_pre'     => $md_pre,
					'mt_pre'     => $mt_pre,
				), get_class( $this ) );
			}

			if ( empty( $mod[ 'id' ] ) ) {	// Just in case.

				return array();

			} elseif ( $num < 1 ) {	// Just in case.

				return array();
			}

			$size_names = $this->p->util->get_image_size_names( $size_names );	// Always returns an array.
			$md_pre     = is_array( $md_pre ) ? array_merge( $md_pre, array( 'og' ) ) : array( $md_pre, 'og' );
			$mt_images  = array();

			foreach( array_unique( $md_pre ) as $opt_pre ) {

				if ( $opt_pre === 'none' ) {		// Special index keyword.

					break;

				} elseif ( empty( $opt_pre ) ) {	// Skip empty md_pre values.

					continue;
				}

				$pid = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_id' );
				$lib = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_id_lib' );
				$url = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url' );

				if ( $pid > 0 ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'using custom ' . $opt_pre . ' image ID = "' . $pid . '"', get_class( $this ) );
					}

					$mt_images = $this->p->media->get_mt_pid_images( $pid, $size_names, $mt_pre );
				}

				if ( empty( $mt_images ) && $url ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'using custom ' . $opt_pre . ' image URL = "' . $url . '"', get_class( $this ) );
					}

					$img_width  = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url:width' );
					$img_height = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url:height' );

					$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

					$mt_single_image[ $mt_pre . ':image:url' ]    = $url;
					$mt_single_image[ $mt_pre . ':image:width' ]  = $img_width > 0 ? $img_width : WPSSO_UNDEF;
					$mt_single_image[ $mt_pre . ':image:height' ] = $img_height > 0 ? $img_height : WPSSO_UNDEF;

					if ( $this->p->util->push_max( $mt_images, $mt_single_image, $num ) ) {

						return $mt_images;
					}
				}

				if ( $pid || $url ) {	// Stop after first $md_pre image found.

					break;
				}
			}

			if ( $this->p->util->is_maxed( $mt_images, $num ) ) {

				return $mt_images;
			}

			/**
			 * Example filter names:
			 *
			 *	'wpsso_post_image_ids'
			 *	'wpsso_term_image_ids'
			 *	'wpsso_user_image_ids'
			 */
			$filter_name = 'wpsso_' . $mod[ 'name' ] . '_image_ids';

			$image_ids = apply_filters( $filter_name, array(), $size_names, $mod[ 'id' ], $mod );

			foreach ( $image_ids as $pid ) {

				if ( $pid > 0 ) {	// Quick sanity check.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding image pid: ' . $pid );
					}

					$mt_pid_images = $this->p->media->get_mt_pid_images( $pid, $size_names, $mt_pre );

					if ( $this->p->util->merge_max( $mt_images, $mt_pid_images, $num ) ) {

						return $mt_images;
					}
				}
			}

			/**
			 * Example filter names:
			 *
			 *	'wpsso_post_image_urls'
			 *	'wpsso_term_image_urls'
			 *	'wpsso_user_image_urls'
			 */
			$filter_name  = 'wpsso_' . $mod[ 'name' ] . '_image_urls';
			$image_urls   = apply_filters( $filter_name, array(), $size_names, $mod[ 'id' ], $mod );

			foreach ( array_unique( $image_urls ) as $num => $url ) {

				if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {	// Just in case.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping image #' . $num . ': url "' . $url . '" is invalid' );
					}

				} else {

					if ( $this->p->util->is_uniq_url( $url, 'image_urls', $mod ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'adding image url: ' . $url );
						}

						$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

						$mt_single_image[ $mt_pre . ':image:url' ] = $url;

						$this->p->util->add_image_url_size( $mt_single_image, $mt_pre . ':image' );

						if ( $this->p->util->push_max( $mt_images, $mt_single_image, $num ) ) {

							return $mt_images;
						}
					}
				}
			}

			return $mt_images;
		}

		/**
		 * Extended by the WpssoUser class to support non-WordPress user images.
		 *
		 * Returns an array of single image associative arrays.
		 *
		 * $md_pre can be a text string or array of prefixes.
		 */
		public function get_og_images( $num, $size_names, $obj_id, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mod = $this->get_mod( $obj_id );

			return $this->get_md_images( $num, $size_names, $mod, $md_pre, $mt_pre );
		}

		/**
		 * Returns an array of single video associative arrays.
		 *
		 * $md_pre can be a text string or array of prefixes.
		 */
		public function get_og_videos( $num, $obj_id, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'num'    => $num,
					'obj_id' => $obj_id,
					'md_pre' => $md_pre,
					'mt_pre' => $mt_pre,
				), get_class( $this ) );
			}

			if ( empty( $obj_id ) ) {	// Just in case.

				return array();

			} elseif ( $num < 1 ) {	// Just in case.

				return array();
			}

			$mod = $this->get_mod( $obj_id );	// Required for get_content_videos().

			$md_pre = is_array( $md_pre ) ? array_merge( $md_pre, array( 'og' ) ) : array( $md_pre, 'og' );

			$mt_videos = array();

			foreach( array_unique( $md_pre ) as $opt_pre ) {

				if ( $opt_pre === 'none' ) {		// Special index keyword.

					break;

				} elseif ( empty( $opt_pre ) ) {	// Skip empty md_pre values.

					continue;
				}

				$embed_html = $this->get_options( $obj_id, $opt_pre . '_vid_embed' );
				$embed_url  = $this->get_options( $obj_id, $opt_pre . '_vid_url' );

				if ( $embed_html ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'fetching video(s) from custom ' . $opt_pre . ' embed code', get_class( $this ) );
					}

					/**
					 * Returns an array of single video associative arrays.
					 */
					$mt_videos = $this->p->media->get_content_videos( $num, $mod, $embed_html );
				}

				if ( empty( $mt_videos ) && $embed_url ) {

					if ( $this->p->util->is_uniq_url( $embed_url, $uniq_context = 'video', $mod ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'fetching video from custom ' . $opt_pre . ' url ' . $embed_url, get_class( $this ) );
						}

						$args = array(
							'url'      => $embed_url,
							'width'    => null,
							'height'   => null,
							'type'     => '',
							'prev_url' => '',
							'post_id'  => null,
							'api'      => '',
						);

						/**
						 * Returns a single video associative array.
						 */
						$mt_single_video = $this->p->media->get_video_details( $args, $mod, $fallback = true );

						if ( ! empty( $mt_single_video ) ) {

							if ( $this->p->util->push_max( $mt_videos, $mt_single_video, $num ) ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'returning ' . count( $mt_videos ) . ' videos' );
								}

								return $mt_videos;
							}
						}
					}
				}

				if ( $embed_html || $embed_url ) {	// Stop after first $md_pre video found.

					break;
				}
			}

			return $mt_videos;
		}

		public function get_mt_reviews( $obj_id, $rating_meta = 'rating', $worst_rating = 1, $best_rating = 5 ) {

			return self::must_be_extended( $ret_val = array() );	// Return an empty array.
		}

		public function get_mt_comment_review( $comment_obj, $rating_meta = 'rating', $worst_rating = 1, $best_rating = 5 ) {

			$mt_ret = array();

			if ( empty( $comment_obj->comment_ID ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: comment object ID is empty' );
				}

				return $mt_ret;
			}

			$comment_mod = $this->p->comment->get_mod( $comment_obj->comment_ID );

			$mt_comment = $this->p->og->get_array( $comment_mod, $size_names = 'schema', $md_pre = array( 'schema', 'og' ) );

			$mt_ret[ 'review:id' ]           = $comment_mod[ 'id' ];
			$mt_ret[ 'review:url' ]          = isset( $mt_comment[ 'og:url' ] ) ? $mt_comment[ 'og:url' ] : '';
			$mt_ret[ 'review:title' ]        = isset( $mt_comment[ 'og:title' ] ) ? $mt_comment[ 'og:title' ] : '';
			$mt_ret[ 'review:description' ]  = isset( $mt_comment[ 'og:description' ] ) ? $mt_comment[ 'og:description' ] : '';
			$mt_ret[ 'review:text' ]         = $this->p->page->get_text( $comment_mod, $md_key = 'schema_text', $max_len = 'schema_text' );
			$mt_ret[ 'review:created_time' ] = $comment_mod[ 'comment_time' ];
			$mt_ret[ 'review:author:id' ]    = $comment_mod[ 'comment_author' ];
			$mt_ret[ 'review:author:name' ]  = $comment_mod[ 'comment_author_name' ];
			$mt_ret[ 'review:author:url' ]   = $comment_mod[ 'comment_author_url' ];
			$mt_ret[ 'review:image' ]        = isset( $mt_comment[ 'og:image' ] ) ? $mt_comment[ 'og:image' ] : array();
			$mt_ret[ 'review:video' ]        = isset( $mt_comment[ 'og:video' ] ) ? $mt_comment[ 'og:video' ] : array();

			/**
			 * Review rating.
			 *
			 * Rating values must be larger than 0 to include rating info.
			 */
			$rating_value = (float) get_comment_meta( $comment_mod[ 'id' ], $rating_meta, $single = true );

			if ( $rating_value > 0 ) {

				$mt_ret[ 'review:rating:value' ] = $rating_value;
				$mt_ret[ 'review:rating:worst' ] = $worst_rating;
				$mt_ret[ 'review:rating:best' ]  = $best_rating;
			}

			return $mt_ret;
		}

		/**
		 * WpssoPost class specific methods.
		 */
		public function get_canonical_shortlink( $shortlink = false, $obj_id = 0, $context = 'post', $allow_slugs = true ) {

			return self::must_be_extended( $ret_val = '' );
		}

		public function maybe_restore_shortlink( $shortlink = false, $obj_id = 0, $context = 'post', $allow_slugs = true ) {

			return self::must_be_extended( $ret_val = '' );
		}

		/**
		 * Since WPSSO Core v7.6.0.
		 */
		public static function get_attached( $obj_id, $attach_type ) {

			$opts = static::get_meta( $obj_id, WPSSO_META_ATTACHED_NAME, $single = true );		// Use static method from child.

			if ( isset( $opts[ $attach_type ] ) ) {

				if ( is_array( $opts[ $attach_type ] ) ) {	// Just in case.

					return $opts[ $attach_type ];
				}
			}

			return array();	// No values.
		}

		/**
		 * Since WPSSO Core v7.6.0.
		 *
		 * Used by WpssoFaqShortcodeQuestion->do_shortcode().
		 */
		public static function add_attached( $obj_id, $attach_type, $attachment_id ) {

			$opts = static::get_meta( $obj_id, WPSSO_META_ATTACHED_NAME, $single = true );		// Use static method from child.

			if ( ! isset( $opts[ $attach_type ][ $attachment_id ] ) ) {

				if ( ! is_array( $opts ) ) {

					$opts = array();
				}

				$opts[ $attach_type ][ $attachment_id ] = true;

				return static::update_meta( $obj_id, WPSSO_META_ATTACHED_NAME, $opts );		// Use static method from child.
			}

			return false;	// No addition.
		}

		/**
		 * Since WPSSO Core v7.6.0.
		 */
		public static function delete_attached( $obj_id, $attach_type, $attachment_id ) {

			$opts = static::get_meta( $obj_id, WPSSO_META_ATTACHED_NAME, $single = true );		// Use static method from child.

			if ( isset( $opts[ $attach_type ][ $attachment_id ] ) ) {

				unset( $opts[ $attach_type ][ $attachment_id ] );

				if ( empty( $opts ) ) {	// Cleanup.

					return static::delete_meta( $obj_id, WPSSO_META_ATTACHED_NAME );	// Use static method from child.
				}

				return static::update_meta( $obj_id, WPSSO_META_ATTACHED_NAME, $opts );		// Use static method from child.
			}

			return false;	// No delete.
		}

		/**
		 * Since WPSSO Core v8.15.0.
		 *
		 * Returns a custom or default term ID, or false if a term for the $tax_slug is not found.
		 */
		public function get_primary_term_id( array $mod, $tax_slug = 'category' ) {

			return self::must_be_extended( $ret_val = false );	// Return false by default.
		}

		/**
		 * Since WPSSO Core v8.18.0.
		 *
		 * Returns the first taxonomy term ID, , or false if a term for the $tax_slug is not found.
		 */
		public function get_default_term_id( array $mod, $tax_slug = 'category' ) {

			return self::must_be_extended( $ret_val = false );	// Return false by default.
		}

		/**
		 * Since WPSSO Core v8.16.0.
		 *
		 * Returns an associative array of term IDs and their names or objects.
		 *
		 * The primary or default term ID will be included as the first array element.
		 */
		public function get_primary_terms( array $mod, $tax_slug = 'category', $output = 'objects' ) {

			return self::must_be_extended( $ret_val = array() );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 *
		 * Always call this method as static::get_meta(), and not self::get_meta(), to execute the method via the child
		 * class instead of the parent class. This method can also be called via $mod[ 'obj' ]::get_meta().
		 */
		public static function get_meta( $obj_id, $meta_key = '', $single = false ) {

			$ret_val = $single ? '' : array();

			return self::must_be_extended( $ret_val );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 *
		 * Always call this method as static::update_meta(), and not self::update_meta(), to execute the method via the
		 * child class instead of the parent class. This method can also be called via $mod[ 'obj' ]::update_meta().
		 */
		public static function update_meta( $obj_id, $meta_key, $value ) {

			return self::must_be_extended( $ret_val = false ); // No update.
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 *
		 * Always call this method as staticdelete_meta(), and not selfdelete_meta(), to execute the method via the child
		 * class instead of the parent class. This method can also be called via $mod[ 'obj' ]delete_meta().
		 */
		public static function delete_meta( $obj_id, $meta_key ) {

			return self::must_be_extended( $ret_val = false ); // No delete.
		}

		/**
		 * Since WPSSO Core v8.12.0.
		 *
		 * Used by several SEO integration modules.
		 */
		public static function get_mod_meta( $mod, $meta_key, $single = false, $delete = false ) {

			$ret_val = $not_found = $single ? '' : array();	// Default if no metadata found.

			if ( $meta_key ) {	// Just in case.

				if ( ! empty( $mod[ 'id' ] ) ) {	// Just in case.

					if ( ! empty( $mod[ 'is_post' ] ) ) {

						$ret_val = get_post_meta( $mod[ 'id' ], $meta_key, $single );

						if ( $delete && $ret_val !== $not_found ) {	// Only delete if we have something to delete.

							delete_post_meta( $mod[ 'id' ], $meta_key );
						}

					} elseif ( ! empty( $mod[ 'is_term' ] ) ) {

						$ret_val = get_term_meta( $mod[ 'id' ], $meta_key, $single );

						if ( $delete && $ret_val !== $not_found ) {	// Only delete if we have something to delete.

							delete_term_meta( $mod[ 'id' ], $meta_key );
						}

					} elseif ( ! empty( $mod[ 'is_user' ] ) ) {

						$ret_val = get_user_meta( $mod[ 'id' ], $meta_key, $single );

						if ( $delete && $ret_val !== $not_found ) {	// Only delete if we have something to delete.

							delete_user_meta( $mod[ 'id' ], $meta_key );
						}
					}

					return $ret_val;
				}
			}

			return $ret_val;
		}

		protected static function must_be_extended( $method, $ret_val = null ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'method must be extended', $class_seq = 2, $func_seq = 2 );
			}

			return $ret_val;
		}
	}
}
