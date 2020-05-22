<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {
	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoOptionsUpgrade' ) ) {

	class WpssoOptionsUpgrade {

		private $p;		// Wpsso class object.

		private static $rename_options_keys = array(
			'wpsso' => array(	// WPSSO Core plugin.
				500 => array(
					'og_img_resize'                  => '',
					'plugin_tid'                     => 'plugin_wpsso_tid',
					'og_publisher_url'               => 'fb_publisher_url',
					'add_meta_property_og:video'     => 'add_meta_property_og:video:url',
					'twitter_shortener'              => 'plugin_shortener',
					'og_desc_strip'                  => 'plugin_p_strip',
					'og_desc_alt'                    => 'plugin_use_img_alt',
					'add_meta_name_twitter:data1'    => '',
					'add_meta_name_twitter:label1'   => '',
					'add_meta_name_twitter:data2'    => '',
					'add_meta_name_twitter:label2'   => '',
					'add_meta_name_twitter:data3'    => '',
					'add_meta_name_twitter:label3'   => '',
					'add_meta_name_twitter:data4'    => '',
					'add_meta_name_twitter:label4'   => '',
					'tc_enable'                      => '',
					'tc_photo_width'                 => '',
					'tc_photo_height'                => '',
					'tc_photo_crop'                  => '',
					'tc_photo_crop_x'                => '',
					'tc_photo_crop_y'                => '',
					'tc_gal_min'                     => '',
					'tc_gal_width'                   => '',
					'tc_gal_height'                  => '',
					'tc_gal_crop'                    => '',
					'tc_gal_crop_x'                  => '',
					'tc_gal_crop_y'                  => '',
					'tc_prod_width'                  => '',
					'tc_prod_height'                 => '',
					'tc_prod_crop'                   => '',
					'tc_prod_crop_x'                 => '',
					'tc_prod_crop_y'                 => '',
					'tc_prod_labels'                 => '',
					'tc_prod_def_label2'             => '',
					'tc_prod_def_data2'              => '',
					'plugin_version'                 => '',
					'plugin_columns_taxonomy'        => 'plugin_columns_term',
					'plugin_add_to_taxonomy'         => '',	// Replaced by "plugin_add_to_tax_{tax_slug}" options.
					'plugin_ignore_small_img'        => 'plugin_check_img_dims',
					'plugin_file_cache_exp'          => 'plugin_social_file_cache_exp',
					'plugin_object_cache_exp'        => '',
					'buttons_use_social_css'         => 'buttons_use_social_style',
					'buttons_enqueue_social_css'     => 'buttons_enqueue_social_style',
					'fb_type'                        => 'fb_share_layout',
					'plugin_schema_type_id_col_post' => 'plugin_schema_type_col_post',
					'plugin_schema_type_id_col_term' => 'plugin_schema_type_col_term',
					'plugin_schema_type_id_col_user' => 'plugin_schema_type_col_user',
					'plugin_auto_img_resize'         => '',
					'plugin_cache_info'              => '',
					'tc_sum_width'                   => 'tc_sum_img_width',
					'tc_sum_height'                  => 'tc_sum_img_height',
					'tc_sum_crop'                    => 'tc_sum_img_crop',
					'tc_sum_crop_x'                  => 'tc_sum_img_crop_x',
					'tc_sum_crop_y'                  => 'tc_sum_img_crop_y',
					'tc_lrgimg_width'                => 'tc_lrg_img_width',
					'tc_lrgimg_height'               => 'tc_lrg_img_height',
					'tc_lrgimg_crop'                 => 'tc_lrg_img_crop',
					'tc_lrgimg_crop_x'               => 'tc_lrg_img_crop_x',
					'tc_lrgimg_crop_y'               => 'tc_lrg_img_crop_y',
					'schema_img_article_width'       => 'schema_article_img_width',
					'schema_img_article_height'      => 'schema_article_img_height',
					'schema_img_article_crop'        => 'schema_article_img_crop',
					'schema_img_article_crop_x'      => 'schema_article_img_crop_x',
					'schema_img_article_crop_y'      => 'schema_article_img_crop_y',
					'og_site_name'                   => 'site_name',
					'og_site_description'            => 'site_desc',
					'org_url'                        => 'site_url',
					'org_type'                       => 'site_org_schema_type',
					'org_place_id'                   => 'site_place_id',
					'link_def_author_id'             => '',
					'link_def_author_on_index'       => '',
					'link_def_author_on_search'      => '',
					'seo_def_author_id'              => '',
					'seo_def_author_on_index'        => '',
					'seo_def_author_on_search'       => '',
					'og_def_author_id'               => '',
					'og_def_author_on_index'         => '',
					'og_def_author_on_search'        => '',
					'tweet_button_css'               => '',
					'tweet_button_js'                => '',
					'plugin_verify_certs'            => '',
				),
				514 => array(
					'rp_publisher_url' => 'p_publisher_url',
					'rp_author_name'   => '',
					'rp_img_width'     => 'p_img_width',
					'rp_img_height'    => 'p_img_height',
					'rp_img_crop'      => 'p_img_crop',
					'rp_img_crop_x'    => 'p_img_crop_x',
					'rp_img_crop_y'    => 'p_img_crop_y',
					'rp_dom_verify'    => 'p_site_verify',
				),
				525 => array(
					'add_meta_itemprop_url'               => 'add_link_itemprop_url',
					'add_meta_itemprop_image'             => 'add_link_itemprop_image',
					'add_meta_itemprop_image.url'         => '',
					'add_meta_itemprop_author.url'        => '',
					'add_meta_itemprop_author.image'      => '',
					'add_meta_itemprop_contributor.url'   => '',
					'add_meta_itemprop_contributor.image' => '',
					'add_meta_itemprop_menu'              => '',
				),
				532 => array(
					'plugin_bitly_token' => 'plugin_bitly_access_token',
				),
				539 => array(
					'plugin_shorten_cache_exp' => 'plugin_short_url_cache_exp',
				),
				541 => array(
					'plugin_content_img_max' => '',
					'plugin_content_vid_max' => '',
					'og_def_vid_url'         => '',
					'og_def_vid_on_index'    => '',
					'og_def_vid_on_search'   => '',
				),
				559 => array(
					'plugin_product_currency' => 'plugin_def_currency',
				),
				561 => array(
					'plugin_shortlink' => 'plugin_wp_shortlink',
				),
				569 => array(
					'plugin_cf_add_type_urls'  => 'plugin_cf_addl_type_urls',
					'schema_organization_json' => 'schema_add_home_organization',
					'schema_person_json'       => 'schema_add_home_person',
					'schema_website_json'      => '',
					'schema_person_id'         => 'schema_home_person_id',
				),
				574 => array(
					'plugin_json_post_data_cache_exp' => '',
				),
				575 => array(
					'site_alt_name'   => 'site_name_alt',
					'schema_alt_name' => 'site_name_alt',
				),
				580 => array(
					'plugin_shortcodes' => '',
					'plugin_widgets'    => '',
				),
				581 => array(
					'og_page_title_tag'   => '',
					'og_page_parent_tags' => '',
				),
				582 => array(
					'add_meta_property_og:image' => 'add_meta_property_og:image:url',
				),
				583 => array(
					'og_author_fallback'   => '',
					'og_def_img_on_index'  => '',
					'og_def_img_on_search' => '',
					'og_ngg_tags'          => '',
				),
				587 => array(
					'og_post_type' => '',
				),
				592 => array(
					'add_meta_property_product:rating:average' => '',	// Non-standard / internal meta tag.
					'add_meta_property_product:rating:count'   => '',	// Non-standard / internal meta tag.
					'add_meta_property_product:rating:worst'   => '',	// Non-standard / internal meta tag.
					'add_meta_property_product:rating:best'    => '',	// Non-standard / internal meta tag.
				),
				599 => array(
					'plugin_add_person_role' => 'plugin_new_user_is_person',
				),
				602 => array(
					'plugin_preserve' => '',
				),
				609 => array(
					'p_author_name' => '',
				),
				616 => array(
					'site_org_type'   => 'site_org_schema_type',
					'schema_desc_len' => 'schema_desc_max_len',
					'og_title_len'    => 'og_title_max_len',
					'og_title_warn'   => 'og_title_warn_len',
					'og_desc_len'     => 'og_desc_max_len',
					'og_desc_warn'    => 'og_desc_warn_len',
					'seo_desc_len'    => 'seo_desc_max_len',
					'tc_desc_len'     => 'tc_desc_max_len',
				),
				618 => array(
					'fb_author_name'     => '',
					'schema_author_name' => 'seo_author_name',
				),
				624 => array(
					'plugin_create_wp_sizes' => '',
				),
				627 => array(
					'plugin_cm_gp_name'    => '',
					'plugin_cm_gp_label'   => '',
					'plugin_cm_gp_enabled' => '',
				),
				629 => array(
					'seo_author_field'         => '',
					'seo_publisher_url'        => '',
					'plugin_cf_product_gender' => 'plugin_cf_product_target_gender',
				),
				631 => array(
					'tc_type_post' => 'tc_type_singular',
				),
				640 => array(
					'add_meta_property_product:sku' => '',
				),
				641 => array(
					'plugin_hide_pro' => '',
				),
				649 => array(
					'plugin_cf_product_ean' => 'plugin_cf_product_gtin13',
				),
				650 => array(
					'plugin_product_attr_volume' => 'plugin_attr_product_fluid_volume_value',
					'plugin_cf_product_volume'   => 'plugin_cf_product_fluid_volume_value',
				),
				651 => array(
					'plugin_honor_force_ssl' => '',
				),
				654 => array(
					'plugin_json_data_cache_exp' => '',
				),
				656 => array(
					'og_vid_https' => '',
				),
				667 => array(
					'schema_add_noscript' => '',
					'schema_article_amp1x1_img_width'   => 'schema_article_1_1_img_width',
					'schema_article_amp1x1_img_height'  => 'schema_article_1_1_img_height',
					'schema_article_amp1x1_img_crop'    => 'schema_article_1_1_img_crop',
					'schema_article_amp1x1_img_crop_x'  => 'schema_article_1_1_img_crop_x',
					'schema_article_amp1x1_img_crop_y'  => 'schema_article_1_1_img_crop_y',
					'schema_article_amp4x3_img_width'   => 'schema_article_4_3_img_width',
					'schema_article_amp4x3_img_height'  => 'schema_article_4_3_img_height',
					'schema_article_amp4x3_img_crop'    => 'schema_article_4_3_img_crop',
					'schema_article_amp4x3_img_crop_x'  => 'schema_article_4_3_img_crop_x',
					'schema_article_amp4x3_img_crop_y'  => 'schema_article_4_3_img_crop_y',
					'schema_article_amp16x9_img_width'  => 'schema_article_16_9_img_width',
					'schema_article_amp16x9_img_height' => 'schema_article_16_9_img_height',
					'schema_article_amp16x9_img_crop'   => 'schema_article_16_9_img_crop',
					'schema_article_amp16x9_img_crop_x' => 'schema_article_16_9_img_crop_x',
					'schema_article_amp16x9_img_crop_y' => 'schema_article_16_9_img_crop_y',
				),
				671 => array(
					'p_dom_verify' => 'p_site_verify',
				),
				673 => array(
					'add_link_itemprop_author.url'                             => '',
					'add_link_itemprop_author.image'                           => '',
					'add_link_itemprop_contributor.url'                        => '',
					'add_link_itemprop_contributor.image'                      => '',
					'add_link_itemprop_image.url'                              => '',
					'add_meta_itemprop_email'                                  => '',
					'add_meta_itemprop_image.width'                            => '',
					'add_meta_itemprop_image.height'                           => '',
					'add_meta_itemprop_publisher.name'                         => '',
					'add_meta_itemprop_author.name'                            => '',
					'add_meta_itemprop_author.description'                     => '',
					'add_meta_itemprop_contributor.name'                       => '',
					'add_meta_itemprop_contributor.description'                => '',
					'add_meta_itemprop_openinghoursspecification.dayofweek'    => '',
					'add_meta_itemprop_openinghoursspecification.opens'        => '',
					'add_meta_itemprop_openinghoursspecification.closes'       => '',
					'add_meta_itemprop_openinghoursspecification.validfrom'    => '',
					'add_meta_itemprop_openinghoursspecification.validthrough' => '',
					'add_meta_itemprop_startdate'                              => '',
					'add_meta_itemprop_enddate'                                => '',
					'add_meta_itemprop_location'                               => '',
					'add_meta_itemprop_preptime'                               => '',
					'add_meta_itemprop_cooktime'                               => '',
					'add_meta_itemprop_totaltime'                              => '',
					'add_meta_itemprop_recipeyield'                            => '',
					'add_meta_itemprop_recipeingredient'                       => '',
					'add_meta_itemprop_recipeinstructions'                     => '',
				),
				674 => array(
					'instgram_publisher_url' => 'instagram_publisher_url',
				),
				686 => array(
					'plugin_wpssoam_tid'    => '',	// Deprecated on 2019/11/14.
					'plugin_wpssorrssb_tid' => '',	// Deprecated on 2019/11/06.
					'plugin_wpssotie_tid'   => '',	// Deprecated on 2019/11/21.
				),
				692 => array(
					'plugin_cf_product_mpn'   => 'plugin_cf_product_mfr_part_no',
					'plugin_cf_product_sku'   => 'plugin_cf_product_retailer_part_no',
					'plugin_product_attr_mpn' => 'plugin_attr_product_mfr_part_no',
				),
				700 => array(
					'og_art_section'          => 'og_def_article_section',
					'plugin_topics_cache_exp' => 'plugin_select_cache_exp',
				),
				701 => array(
					'og_def_article_topic' => 'og_def_article_section',
				),
				703 => array(
					'plugin_clear_all_refresh' => '',
				),
				708 => array(
					'plugin_bitly_login'   => '',	// Bitly Username.
					'plugin_bitly_api_key' => '',	// Bitly API Key (deprecated).
				),
				711 => array(
					'plugin_term_title_prefix' => '',
				),
				712 => array(
					'add_meta_name_weibo:article:create_at' => '',
					'add_meta_name_weibo:article:update_at' => '',
				),
				715 => array(
					'schema_type_for_home_blog'  => 'schema_type_for_home_posts',
					'schema_type_for_home_index' => 'schema_type_for_home_posts',
					'og_type_for_home_blog'      => 'og_type_for_home_posts',
					'og_type_for_home_index'     => 'og_type_for_home_posts',
				),
				718 => array(
					'plugin_list_cache_exp' => 'plugin_select_cache_exp',
				),
				722 => array(
					'plugin_add_to_term' => '',	// Replaced by "plugin_add_to_tax_{tax_slug}" options.
					'plugin_add_to_user' => 'plugin_add_to_user_page',
				),
			),
		);

		private static $rename_site_options_keys = array(
			'wpsso' => array(
				500 => array(
					'plugin_tid'              => 'plugin_wpsso_tid',
					'plugin_ignore_small_img' => 'plugin_check_img_dims',
					'plugin_file_cache_exp'   => 'plugin_social_file_cache_exp',
					'plugin_object_cache_exp' => '',
					'plugin_cache_info'       => '',
					'plugin_verify_certs'     => '',
				),
				539 => array(
					'plugin_shorten_cache_exp' => 'plugin_short_url_cache_exp',
				),
				580 => array(
					'plugin_shortcodes' => '',
					'plugin_widgets'    => '',
				),
				602 => array(
					'plugin_preserve' => '',
				),
				651 => array(
					'plugin_honor_force_ssl' => '',
				),
				686 => array(
					'plugin_wpssoam_tid' => '',	// Deprecated on 2019/11/14.
					'plugin_wpssorrssb_tid' => '',	// Deprecated on 2019/11/06.
					'plugin_wpssotie_tid' => '',	// Deprecated on 2019/11/21.
				),
				700 => array(
					'plugin_topics_cache_exp' => 'plugin_select_cache_exp',
				),
				703 => array(
					'plugin_clear_all_refresh' => '',
				),
				711 => array(
					'plugin_term_title_prefix' => '',
				),
				718 => array(
					'plugin_list_cache_exp' => 'plugin_select_cache_exp',
				),
				723 => array(
					'plugin_product_attr_brand'         => 'plugin_attr_product_brand',
					'plugin_product_attr_color'         => 'plugin_attr_product_color',
					'plugin_product_attr_condition'     => 'plugin_attr_product_condition',
					'plugin_product_attr_depth_value'   => 'plugin_attr_product_depth_value',
					'plugin_product_attr_gtin14'        => 'plugin_attr_product_gtin14',
					'plugin_product_attr_gtin13'        => 'plugin_attr_product_gtin13',
					'plugin_product_attr_gtin12'        => 'plugin_attr_product_gtin12',
					'plugin_product_attr_gtin8'         => 'plugin_attr_product_gtin8',
					'plugin_product_attr_gtin'          => 'plugin_attr_product_gtin',
					'plugin_product_attr_isbn'          => 'plugin_attr_product_isbn',
					'plugin_product_attr_material'      => 'plugin_attr_product_material',
					'plugin_product_attr_mfr_part_no'   => 'plugin_attr_product_mfr_part_no',
					'plugin_product_attr_size'          => 'plugin_attr_product_size',
					'plugin_product_attr_target_gender' => 'plugin_attr_product_target_gender',
					'plugin_product_attr_volume_value'  => 'plugin_attr_product_fluid_volume_value',
				),
				725 => array(
					'plugin_attr_product_volume_value' => 'plugin_attr_product_fluid_volume_value',
					'plugin_cf_product_volume_value'   => 'plugin_cf_product_fluid_volume_value',
				),
			),
		);

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		/**
		 * The $defs argument accepts output from functions, so don't force reference.
		 */
		public function options( $options_name, &$opts = array(), $defs = array(), $network = false ) {

			/**
			 * Save / create the current options version number for version checks to follow.
			 */
			$prev_version = empty( $opts[ 'plugin_' . $this->p->lca . '_opt_version' ] ) ?
				0 : $opts[ 'plugin_' . $this->p->lca . '_opt_version' ];

			/**
			 * Adjust before renaming the option key(s).
			 */
			if ( $prev_version > 0 && $prev_version <= 342 ) {

				if ( ! empty( $opts[ 'plugin_file_cache_hrs' ] ) ) {
					$opts[ 'plugin_social_file_cache_exp' ] = $opts[ 'plugin_file_cache_hrs' ] * HOUR_IN_SECONDS;
				}

				unset( $opts[ 'plugin_file_cache_hrs' ] );
			}

			if ( $options_name === constant( 'WPSSO_OPTIONS_NAME' ) ) {

				$rename_filter_name = $this->p->lca . '_rename_options_keys';

				$upgraded_filter_name = $this->p->lca . '_upgraded_options';

				$rename_options_keys = apply_filters( $rename_filter_name, self::$rename_options_keys );

				$this->p->util->rename_opts_by_ext( $opts, $rename_options_keys );

				/**
				 * Check for schema type IDs to be renamed.
				 */
				$keys_preg = 'schema_type_.*|site_org_schema_type|org_schema_type|plm_place_schema_type';

				foreach ( SucomUtil::preg_grep_keys( '/^(' . $keys_preg . ')(_[0-9]+)?$/', $opts ) as $key => $val ) {

					if ( ! empty( $this->p->cf[ 'head' ][ 'schema_renamed' ][ $val ] ) ) {
						$opts[ $key ] = $this->p->cf[ 'head' ][ 'schema_renamed' ][ $val ];
					}
				}

				if ( $prev_version > 0 && $prev_version <= 270 ) {

					foreach ( $opts as $key => $val ) {

						if ( strpos( $key, 'inc_' ) === 0 ) {

							$new_key = '';

							switch ( $key ) {

								case ( preg_match( '/^inc_(description|twitter:)/', $key ) ? true : false ):

									$new_key = preg_replace( '/^inc_/', 'add_meta_name_', $key );

									break;

								default:

									$new_key = preg_replace( '/^inc_/', 'add_meta_property_', $key );

									break;

							}

							if ( ! empty( $new_key ) ) {
								$opts[ $new_key ] = $val;
							}

							unset( $opts[ $key ] );
						}
					}
				}

				if ( $prev_version > 0 && $prev_version <= 296 ) {

					if ( empty( $opts[ 'plugin_min_shorten' ] ) || $opts[ 'plugin_min_shorten' ] < 22 ) {
						$opts[ 'plugin_min_shorten' ] = 22;
					}
				}

				if ( $prev_version > 0 && $prev_version <= 373 ) {

					if ( ! empty( $opts[ 'plugin_head_attr_filter_name' ] ) && $opts[ 'plugin_head_attr_filter_name' ] === 'language_attributes' ) {
						$opts[ 'plugin_head_attr_filter_name' ] = 'head_attributes';
					}
				}

				if ( $prev_version > 0 && $prev_version <= 557 ) {

					if ( isset( $opts[ 'plugin_cm_fb_label' ] ) && $opts[ 'plugin_cm_fb_label' ] === 'Facebook URL' ) {
						$opts[ 'plugin_cm_fb_label' ] = 'Facebook User URL';
					}

					SucomUtil::transl_key_values( '/^plugin_(cm_.*_label|.*_prefix)$/', $this->p->options, 'wpsso' );
				}

				if ( $prev_version > 0 && $prev_version <= 564 ) {

					if ( isset( $opts[ 'schema_type_for_job_listing' ] ) && $opts[ 'schema_type_for_job_listing' ] === 'webpage' ) {
						$opts[ 'schema_type_for_job_listing' ] = 'job.posting';
					}
				}

				/**
				 * Enable og:image and og:video meta tags, and disable the og:image:url
				 * and og:video:url meta tags, which are functionally identical.
				 */
				if ( $prev_version > 0 && $prev_version <= 591 ) {

					foreach ( array( 'og:image', 'og:video' ) as $mt_name ) {

						$opts[ 'add_meta_property_' . $mt_name] = 1;
						$opts[ 'add_meta_property_' . $mt_name . ':url' ] = 0;
					}
				}

				/**
				 * Remove the 'person' role from all subscribers.
				 */
				if ( $prev_version > 0 && $prev_version <= 599 ) {

					if ( empty( $this->p->options[ 'plugin_new_user_is_person' ] ) ) {

						foreach ( SucomUtilWP::get_roles_user_ids( array( 'subscriber' ) ) as $user_id ) {

							$user_obj = get_user_by( 'ID', $user_id );

							$user_obj->remove_role( 'person' );
						}
					}
				}

				/**
				 * The Google URL Shortener was discontinued by Google in March 2018.
				 */
				if ( $prev_version > 0 && $prev_version <= 614 ) {

					if ( isset( $this->p->options[ 'plugin_shortener' ] ) ) {

						if ( $this->p->options[ 'plugin_shortener' ] === 'googl' ||
							$this->p->options[ 'plugin_shortener' ] === 'google-url-shortener' ) {

							$this->p->options[ 'plugin_shortener' ] = 'none';
						}
					}
				}

				if ( $prev_version > 0 && $prev_version <= 619 ) {

					foreach ( array( 'og:image', 'og:video' ) as $mt_name ) {

						$opts[ 'add_meta_property_' . $mt_name . ':secure_url' ] = 0;
						$opts[ 'add_meta_property_' . $mt_name . ':url' ]        = 0;
						$opts[ 'add_meta_property_' . $mt_name ]                 = 1;
					}
				}

				if ( $prev_version > 0 && $prev_version <= 625 ) {

					$opts[ 'plugin_attr_product_brand' ]     = 'Brand';
					$opts[ 'plugin_attr_product_color' ]     = 'Color';
					$opts[ 'plugin_attr_product_condition' ] = 'Condition';
					$opts[ 'plugin_attr_product_gtin14' ]    = 'GTIN-14';
					$opts[ 'plugin_attr_product_gtin13' ]    = 'GTIN-13';
					$opts[ 'plugin_attr_product_gtin12' ]    = 'GTIN-12';
					$opts[ 'plugin_attr_product_gtin8' ]     = 'GTIN-8';
					$opts[ 'plugin_attr_product_gtin' ]      = 'GTIN';
					$opts[ 'plugin_attr_product_isbn' ]      = 'ISBN';
					$opts[ 'plugin_attr_product_material' ]  = 'Material';
					$opts[ 'plugin_attr_product_size' ]      = 'Size';
				}

				/**
				 * All product meta tags are not enabled by default.
				 */
				if ( $prev_version > 0 && $prev_version <= 637 ) {

					foreach ( SucomUtil::get_opts_begin( 'add_meta_property_product:', $opts ) as $key => $val ) {
						$opts[ $key ] = 1;
					}
				}

				/**
				 * Increase the default SEO description length from 156 to 220 characters.
				 */
				if ( $prev_version > 0 && $prev_version <= 637 ) {

					if ( isset( $opts[ 'seo_desc_max_len' ] ) && $opts[ 'seo_desc_max_len' ] === 156 ) {
						$opts[ 'seo_desc_max_len' ] = 220;
					}
				}

				/**
				 * Refresh the schema types transient cache.
				 */
				$this->p->schema->get_schema_types_array( $flatten = true, $read_cache = false );

			} elseif ( $options_name === constant( 'WPSSO_SITE_OPTIONS_NAME' ) ) {

				$rename_filter_name = $this->p->lca . '_rename_site_options_keys';

				$upgraded_filter_name = $this->p->lca . '_upgraded_site_options';

				$this->p->util->rename_opts_by_ext( $opts, apply_filters( $rename_filter_name, self::$rename_site_options_keys ) );
			}

			$opts = apply_filters( $upgraded_filter_name, $opts, $defs );

			$opts = $this->p->opt->sanitize( $opts, $defs, $network );	// Create any new / missing options.

			return $opts;
		}
	}
}
