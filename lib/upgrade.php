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

if ( ! class_exists( 'WpssoOptionsUpgrade' ) ) {

	class WpssoOptionsUpgrade {

		private $p;	// Wpsso class object.

		private static $rename_keys_by_ext = array(
			'wpsso' => array(	// WPSSO Core plugin.
				500 => array(
					'og_img_resize'                  => '',
					'plugin_tid'                     => 'plugin_wpsso_tid',
					'og_publisher_url'               => 'fb_publisher_url',
					'add_meta_property_og:video'     => 'add_meta_property_og:video:url',
					'twitter_shortener'              => 'plugin_shortener',
					'og_desc_strip'                  => '',
					'og_desc_alt'                    => '',
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
					'plugin_add_to_taxonomy'         => '',
					'plugin_ignore_small_img'        => 'plugin_check_img_dims',
					'plugin_file_cache_exp'          => '',
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
					'schema_img_article_width'       => '',
					'schema_img_article_height'      => '',
					'schema_img_article_crop'        => '',
					'schema_img_article_crop_x'      => '',
					'schema_img_article_crop_y'      => '',
					'og_site_name'                   => 'site_name',
					'og_site_description'            => 'site_desc',
					'org_url'                        => 'site_home_url',
					'org_type'                       => 'site_org_schema_type',
					'org_place_id'                   => 'site_org_place_id',
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
					'rp_publisher_url' => 'pin_publisher_url',
					'rp_author_name'   => '',
					'rp_img_width'     => 'pin_img_width',
					'rp_img_height'    => 'pin_img_height',
					'rp_img_crop'      => 'pin_img_crop',
					'rp_img_crop_x'    => 'pin_img_crop_x',
					'rp_img_crop_y'    => 'pin_img_crop_y',
					'rp_dom_verify'    => 'pin_site_verify',
				),
				525 => array(
					'add_meta_itemprop_url'               => '',
					'add_meta_itemprop_image'             => '',
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
					'plugin_shorten_cache_exp' => '',
				),
				541 => array(
					'plugin_content_img_max' => '',
					'plugin_content_vid_max' => '',
					'og_def_vid_url'         => '',
					'og_def_vid_on_index'    => '',
					'og_def_vid_on_search'   => '',
				),
				559 => array(
					'plugin_product_currency' => 'og_def_currency',
				),
				561 => array(
					'plugin_shortlink' => 'plugin_wp_shortlink',
				),
				569 => array(
					'plugin_cf_add_type_urls'  => 'plugin_cf_addl_type_urls',
					'schema_organization_json' => 'schema_add_home_organization',
					'schema_person_json'       => 'schema_add_home_person',
					'schema_website_json'      => '',
					'schema_person_id'         => 'site_pub_person_id',
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
					'schema_desc_len' => '',
					'og_title_len'    => '',
					'og_title_warn'   => '',
					'og_desc_len'     => '',
					'og_desc_warn'    => '',
					'seo_desc_len'    => '',
					'tc_desc_len'     => '',
				),
				618 => array(
					'fb_author_name'     => '',
					'schema_author_name' => '',
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
					'schema_add_noscript'               => '',
					'schema_article_amp1x1_img_width'   => 'schema_1x1_img_width',
					'schema_article_amp1x1_img_height'  => 'schema_1x1_img_height',
					'schema_article_amp1x1_img_crop'    => 'schema_1x1_img_crop',
					'schema_article_amp1x1_img_crop_x'  => 'schema_1x1_img_crop_x',
					'schema_article_amp1x1_img_crop_y'  => 'schema_1x1_img_crop_y',
					'schema_article_amp4x3_img_width'   => 'schema_4x3_img_width',
					'schema_article_amp4x3_img_height'  => 'schema_4x3_img_height',
					'schema_article_amp4x3_img_crop'    => 'schema_4x3_img_crop',
					'schema_article_amp4x3_img_crop_x'  => 'schema_4x3_img_crop_x',
					'schema_article_amp4x3_img_crop_y'  => 'schema_4x3_img_crop_y',
					'schema_article_amp16x9_img_width'  => 'schema_16x9_img_width',
					'schema_article_amp16x9_img_height' => 'schema_16x9_img_height',
					'schema_article_amp16x9_img_crop'   => 'schema_16x9_img_crop',
					'schema_article_amp16x9_img_crop_x' => 'schema_16x9_img_crop_x',
					'schema_article_amp16x9_img_crop_y' => 'schema_16x9_img_crop_y',
				),
				671 => array(
					'p_dom_verify' => 'pin_site_verify',
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
				692 => array(
					'plugin_cf_product_mpn'   => 'plugin_cf_product_mfr_part_no',
					'plugin_cf_product_sku'   => 'plugin_cf_product_retailer_part_no',
					'plugin_product_attr_mpn' => 'plugin_attr_product_mfr_part_no',
				),
				700 => array(
					'og_art_section'          => 'schema_def_article_section',
					'plugin_topics_cache_exp' => '',
				),
				701 => array(
					'og_def_article_topic' => 'schema_def_article_section',
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
					'plugin_list_cache_exp' => '',
				),
				722 => array(
					'plugin_add_to_term' => '',	// Replaced by "plugin_add_to_tax_{tax_slug}" options.
					'plugin_add_to_user' => 'plugin_add_to_user_page',
				),
				744 => array(
					'schema_banner_url'     => 'site_org_banner_url',
					'schema_home_person_id' => 'site_pub_person_id',
					'schema_logo_url'       => 'site_org_logo_url',
					'site_place_id'         => 'site_org_place_id',
				),
				748 => array(
					'schema_article_img_width'       => '',
					'schema_article_img_height'      => '',
					'schema_article_img_crop'        => '',
					'schema_article_img_crop_x'      => '',
					'schema_article_img_crop_y'      => '',
					'schema_article_1_1_img_width'   => 'schema_1x1_img_width',
					'schema_article_1_1_img_height'  => 'schema_1x1_img_height',
					'schema_article_1_1_img_crop'    => 'schema_1x1_img_crop',
					'schema_article_1_1_img_crop_x'  => 'schema_1x1_img_crop_x',
					'schema_article_1_1_img_crop_y'  => 'schema_1x1_img_crop_y',
					'schema_article_4_3_img_width'   => 'schema_4x3_img_width',
					'schema_article_4_3_img_height'  => 'schema_4x3_img_height',
					'schema_article_4_3_img_crop'    => 'schema_4x3_img_crop',
					'schema_article_4_3_img_crop_x'  => 'schema_4x3_img_crop_x',
					'schema_article_4_3_img_crop_y'  => 'schema_4x3_img_crop_y',
					'schema_article_16_9_img_width'  => 'schema_16x9_img_width',
					'schema_article_16_9_img_height' => 'schema_16x9_img_height',
					'schema_article_16_9_img_crop'   => 'schema_16x9_img_crop',
					'schema_article_16_9_img_crop_x' => 'schema_16x9_img_crop_x',
					'schema_article_16_9_img_crop_y' => 'schema_16x9_img_crop_y',
					'schema_img_width'               => '',
					'schema_img_height'              => '',
					'schema_img_crop'                => '',
					'schema_img_crop_x'              => '',
					'schema_img_crop_y'              => '',
				),
				751 => array(
					'og_type_for_wpsc-product'     => '',
					'plugin_add_to_wpsc-product'   => '',
					'schema_type_for_wpsc-product' => '',
				),
				752 => array(
					'plugin_clear_on_save' => '',
				),
				768 => array(
					'plugin_def_currency' => 'og_def_currency',
				),
				772 => array(
					'site_url' => 'site_home_url',
				),
				773 => array(
					'plugin_debug' => 'plugin_debug_html',
				),
				784 => array(
					'schema_1_1_img_width'   => 'schema_1x1_img_width',
					'schema_1_1_img_height'  => 'schema_1x1_img_height',
					'schema_1_1_img_crop'    => 'schema_1x1_img_crop',
					'schema_1_1_img_crop_x'  => 'schema_1x1_img_crop_x',
					'schema_1_1_img_crop_y'  => 'schema_1x1_img_crop_y',
					'schema_4_3_img_width'   => 'schema_4x3_img_width',
					'schema_4_3_img_height'  => 'schema_4x3_img_height',
					'schema_4_3_img_crop'    => 'schema_4x3_img_crop',
					'schema_4_3_img_crop_x'  => 'schema_4x3_img_crop_x',
					'schema_4_3_img_crop_y'  => 'schema_4x3_img_crop_y',
					'schema_16_9_img_width'  => 'schema_16x9_img_width',
					'schema_16_9_img_height' => 'schema_16x9_img_height',
					'schema_16_9_img_crop'   => 'schema_16x9_img_crop',
					'schema_16_9_img_crop_x' => 'schema_16x9_img_crop_x',
					'schema_16_9_img_crop_y' => 'schema_16x9_img_crop_y',
				),
				786 => array(
					'og_def_img_id_pre' => 'og_def_img_id_lib',
				),
				795 => array(
					'plugin_clear_on_activate'   => '',	// Deprecated on 2021/06/22.
					'plugin_clear_on_deactivate' => '',	// Deprecated on 2021/06/22.
					'plugin_add_to_shop_coupon'  => '',	// Fix for private post type.
					'plugin_add_to_shop_order'   => '',	// Fix for private post type.
				),
				797 => array(
					'plugin_vidinfo_cache_exp'            => '',		// Renamed on 2021/06/24.
					'plugin_shopperapproved_num_max'      => 'plugin_ratings_reviews_num_max',	// Renamed on 2021/06/24.
					'plugin_shopperapproved_age_max'      => 'plugin_ratings_reviews_age_max',	// Renamed on 2021/06/24.
					'plugin_shopperapproved_for_download' => 'plugin_ratings_reviews_for_download',	// Renamed on 2021/06/24.
					'plugin_shopperapproved_for_product'  => 'plugin_ratings_reviews_for_product',	// Renamed on 2021/06/24.
				),
				798 => array(
					'og_author_field' => '',	// Renamed to 'fb_author_field' on 2021/07/01, then deleted on 2022/01/29.
				),
				800 => array(
					'plugin_apiresp_cache_exp'   => '',
					'plugin_cache_attach_page'   => '',
					'plugin_content_cache_exp'   => '',
					'plugin_head_cache_exp'      => '',
					'plugin_imgsize_cache_exp'   => '',
					'plugin_select_cache_exp'    => '',
					'plugin_short_url_cache_exp' => '',
					'plugin_types_cache_exp'     => '',
				),
				801 => array(
					'fb_admins'          => '',	// Deprecated on 2020/10/23.
					'plugin_p_strip'     => '',
					'plugin_use_img_alt' => '',
				),
				811 => array(
					'plm_def_country'     => 'og_def_country',	// Moved to WPSSO Core on 2021/08/27.
					'seo_author_name'     => '',			// Deprecated on 2021/09/01.
				),
				814 => array(
					'add_5_star_rating'          => '',	// Deprecated on 2021/09/08.
					'plugin_col_title_width'     => '',	// Deprecated on 2021/09/08.
					'plugin_col_title_width_max' => '',	// Deprecated on 2021/09/08.
					'plugin_col_def_width'       => '',	// Deprecated on 2021/09/08.
					'plugin_col_def_width_max'   => '',	// Deprecated on 2021/09/08.
				),
				815 => array(
					'plugin_wpssoam_tid'         => '',	// Deprecated on 2019/11/14.
					'plugin_wpssoorg_tid'        => '',	// Deprecated on 2021/08/25.
					'plugin_wpssoplm_tid'        => '',	// Deprecated on 2021/08/30.
					'plugin_wpssorrssb_tid'      => '',	// Deprecated on 2019/11/06.
					'plugin_wpssotie_tid'        => '',	// Deprecated on 2019/11/21.
					'p_site_verify'              => 'pin_site_verify',
					'p_publisher_url'            => 'pin_publisher_url',
					'p_add_nopin_header_img_tag' => 'pin_add_nopin_header_img_tag',
					'p_add_nopin_media_img_tag'  => 'pin_add_nopin_media_img_tag',
					'p_add_img_html'             => 'pin_add_img_html',
					'p_img_width'                => 'pin_img_width',
					'p_img_height'               => 'pin_img_height',
					'p_img_crop'                 => 'pin_img_crop',
					'p_img_crop_x'               => 'pin_img_crop_x',
					'p_img_crop_y'               => 'pin_img_crop_y',
					'p_img_desc_max_len'         => '',
					'p_img_desc_warn_len'        => '',
				),
				816 => array(
					'add_link_itemprop_url'                         => '',	// Deprecated on 2021/09/15.
					'add_link_itemprop_image'                       => '',	// Deprecated on 2021/09/15.
					'add_link_itemprop_thumbnailurl'                => '',	// Deprecated on 2021/09/15.
					'add_link_itemprop_hasmenu'                     => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_name'                        => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_alternatename'               => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_description'                 => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_aggregaterating.ratingvalue' => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_aggregaterating.ratingcount' => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_aggregaterating.worstrating' => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_aggregaterating.bestrating'  => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_aggregaterating.reviewcount' => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_address'                     => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_telephone'                   => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_currenciesaccepted'          => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_paymentaccepted'             => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_pricerange'                  => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_acceptsreservations'         => '',	// Deprecated on 2021/09/15.
					'add_meta_itemprop_servescuisine'               => '',	// Deprecated on 2021/09/15.
				),
				831 => array(
					'plugin_clear_post_terms'  => '',	// Deprecated on 2021/11/01.
					'plugin_clear_for_comment' => '',	// Deprecated on 2021/11/01.
				),
				832 => array(
					'upscale_img_max'  => 'upscale_pct_max',	// Renamed on 2021/11/03.
				),
				846 => array(
					'plugin_schema_type_col_post' => '',
					'plugin_schema_type_col_term' => '',
					'plugin_schema_type_col_user' => '',
					'plugin_og_type_col_post'     => '',
					'plugin_og_type_col_term'     => '',
					'plugin_og_type_col_user'     => '',
					'plugin_og_img_col_post'      => '',
					'plugin_og_img_col_term'      => '',
					'plugin_og_img_col_user'      => '',
					'plugin_og_desc_col_post'     => '',
					'plugin_og_desc_col_term'     => '',
					'plugin_og_desc_col_user'     => '',
				),
				853 => array(
					'plugin_wpseo_social_meta' => 'plugin_import_wpseo_meta',	// Renamed on 2022/01/14.
					'plugin_wpseo_show_import' => '',				// Deprecated on 2022/01/15.
				),
				864 => array(
					'og_type_for_post_archive'     => '',
					'schema_type_for_post_archive' => '',
					'fb_author_field'              => '',	// Deprecated on 2022/01/29.
				),
				870 => array(
					'plugin_checksum'       => 'checksum',	// Renamed on 2022/02/04.
					'plugin_filter_title'   => '',		// Deprecated on 2022/02/02.
					'plugin_document_title' => '',		// Deprecated on 2022/02/04.
					'plugin_seo_title_part' => '',		// Deprecated on 2022/02/04.
				),
				877 => array(
					'seo_title_max_len'     => '',
					'seo_desc_max_len'      => '',
					'og_title_max_len'      => '',
					'og_title_warn_len'     => '',
					'og_desc_max_len'       => '',
					'og_desc_warn_len'      => '',
					'pin_img_desc_max_len'  => '',
					'pin_img_desc_warn_len' => '',
					'tc_title_max_len'      => '',
					'tc_desc_max_len'       => '',
					'schema_title_max_len'  => '',
					'schema_desc_max_len'   => '',
					'schema_text_max_len'   => '',
				),
				881 => array(
					'og_type_for_elementor_library'       => '',
					'schema_type_for_elementor_library'   => '',
					'wpsm_sitemaps_for_elementor_library' => '',
				),
				891 => array(
					'plugin_show_validate_toolbar' => 'plugin_add_toolbar_validate',
				),
				898 => array(
					'home_url' => 'site_home_url',
				),
				914 => array(
					'og_def_product_category'   => 'schema_def_product_category',
					'og_def_product_condition'  => 'schema_def_product_condition',
					'og_def_product_price_type' => 'schema_def_product_price_type',
				),
				920 => array(
					'og_def_article_section' => 'schema_def_article_section',	// Renamed for WPSSO Core v13.5.0.
				),
			),
		);

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function options( $opts_name, $opts = array(), $defs = array(), $network = false ) {

			if ( constant( 'WPSSO_OPTIONS_NAME' ) === $opts_name ) {

				$is_site_options = false;

			} elseif ( constant( 'WPSSO_SITE_OPTIONS_NAME' ) === $opts_name ) {

				$is_site_options = true;

			} else {	// Nothing to do.

				return $opts;
			}

			/**
			 * Get the current options version number for checks to follow.
			 */
			$prev_version = $this->p->opt->get_version( $opts, 'wpsso' );	// Returns 'opt_version'.

			/**
			 * Maybe renamed some option keys.
			 */
			$version_keys = $is_site_options ?
				apply_filters( 'wpsso_rename_site_options_keys', self::$rename_keys_by_ext ) :	// Network options filter.
				apply_filters( 'wpsso_rename_options_keys', self::$rename_keys_by_ext );

			$opts = $this->p->util->rename_options_by_ext( $opts, $version_keys );

			/**
			 * Maybe update some option values.
			 */
			if ( ! $is_site_options ) {

				/**
				 * Check for schema type IDs to be renamed.
				 */
				$schema_type_keys_preg = '/^(schema_type_.*|site_org_schema_type|org_schema_type|place_schema_type|plm_place_schema_type)(_[0-9]+)?$/';

				foreach ( SucomUtil::preg_grep_keys( $schema_type_keys_preg, $opts ) as $key => $val ) {

					if ( ! empty( $this->p->cf[ 'head' ][ 'schema_renamed' ][ $val ] ) ) {

						$opts[ $key ] = $this->p->cf[ 'head' ][ 'schema_renamed' ][ $val ];
					}
				}

				if ( $prev_version > 0 && $prev_version <= 270 ) {

					foreach ( SucomUtil::get_opts_begin( 'inc_', $opts ) as $key => $val ) {

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

				if ( $prev_version > 0 && $prev_version <= 296 ) {

					if ( empty( $opts[ 'plugin_min_shorten' ] ) || $opts[ 'plugin_min_shorten' ] < 22 ) {

						$opts[ 'plugin_min_shorten' ] = 22;
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
				 * Enable og:image and og:video meta tags, and disable the og:image:url and og:video:url meta tags,
				 * which are functionally identical.
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

						while ( $result = SucomUtil::get_roles_users_ids( $roles = array( 'subscriber' ), $blog_id = null, $limit = 1000 ) ) {

							foreach ( $result as $user_id ) {

								$user_obj = get_user_by( 'ID', $user_id );

								$user_obj->remove_role( 'person' );
							}
						}
					}
				}

				/**
				 * The Google URL Shortener was discontinued by Google in March 2018.
				 */
				if ( $prev_version > 0 && $prev_version <= 614 ) {

					if ( isset( $this->p->options[ 'plugin_shortener' ] ) ) {

						if ( 'googl' === $this->p->options[ 'plugin_shortener' ] ||
							'google-url-shortener' === $this->p->options[ 'plugin_shortener' ] ) {

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

				if ( $prev_version > 0 && $prev_version <= 744 ) {

					if ( ! empty( $opts[ 'schema_add_home_organization' ] ) ) {

						$opts[ 'site_pub_schema_type' ] = 'organization';

					} elseif ( ! empty( $opts[ 'schema_add_home_person' ] ) ) {

						$opts[ 'site_pub_schema_type' ] = 'person';
					}

					unset( $opts[ 'schema_add_home_organization' ] );

					unset( $opts[ 'schema_add_home_person' ] );
				}

				/**
				 * Remove the options from deprecated add-ons.
				 */
				if ( $prev_version > 0 && $prev_version <= 765 ) {

					if ( ! class_exists( 'WpssoSsb' ) ) {	// Make sure the deprecated add-on is not active.

						unset(
							$opts[ 'buttons_preset_ssb-content' ],
							$opts[ 'buttons_preset_ssb-excerpt' ],
							$opts[ 'buttons_preset_ssb-sidebar' ],
							$opts[ 'buttons_preset_ssb-shortcode' ],
							$opts[ 'buttons_preset_ssb-widget' ],
							$opts[ 'buttons_css_ssb-content' ],
							$opts[ 'buttons_css_ssb-excerpt' ],
							$opts[ 'buttons_css_ssb-sharing' ],
							$opts[ 'buttons_css_ssb-shortcode' ],
							$opts[ 'buttons_css_ssb-sidebar' ],
							$opts[ 'buttons_css_ssb-widget' ],
							$opts[ 'buttons_js_ssb-sidebar' ],
							$opts[ 'email_ssb_html' ],
							$opts[ 'plugin_social_file_cache_exp' ],
							$opts[ 'wa_ssb_html' ]
						);
					}

					if ( ! class_exists( 'WpssoTaq' ) ) {	// Make sure the deprecated add-on is not active.

						unset(
							$opts[ 'taq_add_via' ],
							$opts[ 'taq_rec_author' ],
							$opts[ 'taq_link_text' ],
							$opts[ 'taq_add_button' ],
							$opts[ 'taq_use_style' ],
							$opts[ 'taq_use_script' ],
							$opts[ 'taq_popup_width' ],
							$opts[ 'taq_popup_height' ]
						);
					}
				}

				/**
				 * Update the Facebook App ID to its default value.
				 */
				if ( $prev_version > 0 && $prev_version <= 778 ) {

					if ( empty( $opts[ 'fb_app_id' ] ) ) {

						$opts[ 'fb_app_id' ] = '966242223397117';
					}
				}

				/**
				 * Deprecated on 2021/05/28.
				 */
				if ( $prev_version > 0 && $prev_version <= 785 ) {

					delete_post_meta_by_key( '_wpsso_wprecipemaker' );
				}

				/**
				 * Fix default publisher.
				 */
				if ( $prev_version > 0 && $prev_version <= 827 ) {

					if ( 'none' === $opts[ 'schema_def_pub_org_id' ] && 'none' === $opts[ 'schema_def_pub_person_id' ] ) {

						switch ( $opts[ 'site_pub_schema_type' ] ) {

							case 'person':

								$opts[ 'schema_def_pub_org_id' ]    = 'none';
								$opts[ 'schema_def_pub_person_id' ] = $opts[ 'site_pub_person_id' ];

								break;

							case 'organization':

								$opts[ 'schema_def_pub_org_id' ]    = 'site';
								$opts[ 'schema_def_pub_person_id' ] = 'none';

								break;
						}
					}
				}

				/**
				 * Rename 'plugin_sitemaps_for' options to 'wpsm_sitemaps_for' for the WPSSO WPSM add-on.
				 */
				if ( $prev_version > 0 && $prev_version <= 834 ) {

					foreach ( SucomUtil::get_opts_begin( 'plugin_sitemaps_for', $opts ) as $key => $val ) {

						$new_key = preg_replace( '/^plugin_sitemaps_for/', 'wpsm_sitemaps_for', $key );

						$opts[ $new_key ] = $val;

						unset( $opts[ $key ] );
					}
				}

				if ( $prev_version > 0 && $prev_version <= 846 ) {

					$opts = SucomUtil::preg_grep_keys( '/^plugin_.*_col_.*$/', $opts, $invert = true );
				}

				/**
				 * If the Twitter Card image sizes have not been changed from their old default values, then update
				 * the options to the new default values.
				 */
				if ( $prev_version > 0 && $prev_version <= 889 ) {

					if ( 1200 === $opts[ 'tc_sum_img_width' ] && 630 === $opts[ 'tc_sum_img_height' ] && $opts[ 'tc_sum_img_crop' ] &&
						'center' === $opts[ 'tc_sum_img_crop_x' ] && 'center' === $opts[ 'tc_sum_img_crop_y' ] ) {

						$opts[ 'tc_sum_img_width' ]  = 1200;
						$opts[ 'tc_sum_img_height' ] = 1200;
						$opts[ 'tc_sum_img_crop' ]   = 1;
						$opts[ 'tc_sum_img_crop_x' ] = 'center';
						$opts[ 'tc_sum_img_crop_y' ] = 'center';
					}

					if ( 1200 === $opts[ 'tc_lrg_img_width' ] && 1800 === $opts[ 'tc_lrg_img_height' ] && ! $opts[ 'tc_lrg_img_crop' ] &&
						'center' === $opts[ 'tc_lrg_img_crop_x' ] && 'center' === $opts[ 'tc_lrg_img_crop_y' ] ) {

						$opts[ 'tc_lrg_img_width' ]  = 1200;
						$opts[ 'tc_lrg_img_height' ] = 630;
						$opts[ 'tc_lrg_img_crop' ]   = 1;
						$opts[ 'tc_lrg_img_crop_x' ] = 'center';
						$opts[ 'tc_lrg_img_crop_y' ] = 'center';
					}
				}

				/**
				 * The '%%term_title%%' inline variable no longer includes parent names.
				 *
				 * Replace the old '%%term_title%%' variable by '%%term_hierarchy%%'.
				 */
				if ( $prev_version > 0 && $prev_version <= 925 ) {

					if ( ! empty( $opts[ 'plugin_term_page_title' ] ) && '%%term_title%%' === $opts[ 'plugin_term_page_title' ] ) {

						$opts[ 'plugin_term_page_title' ] = '%%term_hierarchy%%';
					}
				}
			}

			/**
			 * Maybe add any new / missing options keys.
			 */
			$opts = array_merge( $defs, $opts );

			$opts = $is_site_options ?
				apply_filters( 'wpsso_upgraded_site_options', $opts, $defs ) :
				apply_filters( 'wpsso_upgraded_options', $opts, $defs );

			/**
			 * The options array should not contain any numeric keys.
			 */
			SucomUtil::unset_numeric_keys( $opts );

			/**
			 * Refresh the schema types transient cache.
			 */
			$this->p->schema->get_schema_types_array( $flatten = true, $read_cache = false );

			return $opts;
		}
	}
}
