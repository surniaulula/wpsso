<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoOptionsUpgrade' ) && class_exists( 'WpssoOptions' ) ) {

	class WpssoOptionsUpgrade extends WpssoOptions {

		private static $rename_site_options_keys = array(
			'wpsso' => array(
				500 => array(
					'plugin_tid' => 'plugin_wpsso_tid',
					'plugin_ignore_small_img' => 'plugin_check_img_dims',		// renamed in v3.31.1-1
					'plugin_file_cache_exp' => 'plugin_social_file_cache_exp',
					'plugin_object_cache_exp' => '',
					'plugin_cache_info' => 'plugin_show_purge_count',
					'plugin_verify_certs' => '',
				),
			),
		);

		private static $rename_options_keys = array(
			'wpsso' => array(
				500 => array(
					'og_img_resize' => 'plugin_create_wp_sizes',
					'plugin_tid' => 'plugin_wpsso_tid',
					'og_publisher_url' => 'fb_publisher_url',
					'add_meta_property_og:video' => 'add_meta_property_og:video:url',
					'twitter_shortener' => 'plugin_shortener',
					'og_desc_strip' => 'plugin_p_strip',
					'og_desc_alt' => 'plugin_use_img_alt',
					'add_meta_name_twitter:data1' => '',
					'add_meta_name_twitter:label1' => '',
					'add_meta_name_twitter:data2' => '',
					'add_meta_name_twitter:label2' => '',
					'add_meta_name_twitter:data3' => '',
					'add_meta_name_twitter:label3' => '',
					'add_meta_name_twitter:data4' => '',
					'add_meta_name_twitter:label4' => '',
					'tc_enable' => '',
					'tc_photo_width' => '',
					'tc_photo_height' => '',
					'tc_photo_crop' => '',
					'tc_photo_crop_x' => '',
					'tc_photo_crop_y' => '',
					'tc_gal_min' => '',
					'tc_gal_width' => '',
					'tc_gal_height' => '',
					'tc_gal_crop' => '',
					'tc_gal_crop_x' => '',
					'tc_gal_crop_y' => '',
					'tc_prod_width' => '',
					'tc_prod_height' => '',
					'tc_prod_crop' => '',
					'tc_prod_crop_x' => '',
					'tc_prod_crop_y' => '',
					'tc_prod_labels' => '',
					'tc_prod_def_label2' => '',
					'tc_prod_def_data2' => '',
					'plugin_version' => '',
					'seo_author_name' => '',
					'plugin_columns_taxonomy' => 'plugin_columns_term',		// renamed in v3.31.0-1
					'plugin_add_to_taxonomy' => 'plugin_add_to_term',		// renamed in v3.31.0-1
					'plugin_ignore_small_img' => 'plugin_check_img_dims',		// renamed in v3.31.1-1
					'plugin_file_cache_exp' => 'plugin_social_file_cache_exp',
					'plugin_object_cache_exp' => '',
					'buttons_use_social_css' => 'buttons_use_social_style',
					'buttons_enqueue_social_css' => 'buttons_enqueue_social_style',
					'fb_type' => 'fb_share_layout',
					'plugin_schema_type_id_col_post' => 'plugin_schema_type_col_post',	// renamed in v3.39.1-1
					'plugin_schema_type_id_col_term' => 'plugin_schema_type_col_term',	// renamed in v3.39.1-1
					'plugin_schema_type_id_col_user' => 'plugin_schema_type_col_user',	// renamed in v3.39.1-1
					'plugin_auto_img_resize' => 'plugin_create_wp_sizes',
					'plugin_cache_info' => 'plugin_show_purge_count',
					'tc_sum_width' => 'tc_sum_img_width',
					'tc_sum_height' => 'tc_sum_img_height',
					'tc_sum_crop' => 'tc_sum_img_crop',
					'tc_sum_crop_x' => 'tc_sum_img_crop_x',
					'tc_sum_crop_y' => 'tc_sum_img_crop_y',
					'tc_lrgimg_width' => 'tc_lrg_img_width',
					'tc_lrgimg_height' => 'tc_lrg_img_height',
					'tc_lrgimg_crop' => 'tc_lrg_img_crop',
					'tc_lrgimg_crop_x' => 'tc_lrg_img_crop_x',
					'tc_lrgimg_crop_y' => 'tc_lrg_img_crop_y',
					'schema_img_article_width' => 'schema_article_img_width',
					'schema_img_article_height' => 'schema_article_img_height',
					'schema_img_article_crop' => 'schema_article_img_crop',
					'schema_img_article_crop_x' => 'schema_article_img_crop_x',
					'schema_img_article_crop_y' => 'schema_article_img_crop_y',
					'og_site_name' => 'site_name',
					'og_site_description' => 'site_desc',
					'org_url' => 'site_url',
					'org_type' => 'site_org_type',
					'org_place_id' => 'site_place_id',
					'link_def_author_id' => '',
					'link_def_author_on_index' => '',
					'link_def_author_on_search' => '',
					'seo_def_author_id' => '',
					'seo_def_author_on_index' => '',
					'seo_def_author_on_search' => '',
					'og_def_author_id' => '',
					'og_def_author_on_index' => '',
					'og_def_author_on_search' => '',
					'tweet_button_css' => '',
					'tweet_button_js' => '',
					'plugin_verify_certs' => '',
				),
				514 => array(
					'rp_publisher_url' => 'p_publisher_url',
					'rp_author_name' => 'p_author_name',
					'rp_img_width' => 'p_img_width',
					'rp_img_height' => 'p_img_height',
					'rp_img_crop' => 'p_img_crop',
					'rp_img_crop_x' => 'p_img_crop_x',
					'rp_img_crop_y' => 'p_img_crop_y',
					'rp_dom_verify' => 'p_dom_verify',
				),
			),
			'wpssossb' => array(
				14 => array(
					'stumble_js_loc' => 'stumble_script_loc',
					'pin_js_loc' => 'pin_script_loc',
					'tumblr_js_loc' => 'tumblr_script_loc',
					'gp_js_loc' => 'gp_script_loc',
					'fb_js_loc' => 'fb_script_loc',
					'twitter_js_loc' => 'twitter_script_loc',
					'buffer_js_loc' => 'buffer_script_loc',
					'linkedin_js_loc' => 'linkedin_script_loc',
				),
				525 => array(
					'add_meta_itemprop_url' => 'add_link_itemprop_url',
					'add_meta_itemprop_image' => 'add_link_itemprop_image',
					'add_meta_itemprop_image.url' => 'add_link_itemprop_image.url',
					'add_meta_itemprop_author.url' => 'add_link_itemprop_author.url',
					'add_meta_itemprop_author.image' => 'add_link_itemprop_author.image',
					'add_meta_itemprop_contributor.url' => 'add_link_itemprop_contributor.url',
					'add_meta_itemprop_contributor.image' => 'add_link_itemprop_contributor.image',
					'add_meta_itemprop_menu' => 'add_link_itemprop_menu',
				),
			),
		);

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		// def_opts accepts output from functions, so don't force reference
		public function options( $options_name, &$opts = array(), $def_opts = array(), $network = false ) {

			$lca = $this->p->cf['lca'];
			$prev_version = empty( $opts['plugin_'.$lca.'_opt_version'] ) ?	// just in case
				0 : $opts['plugin_'.$lca.'_opt_version'];

			// adjust before renaming the option key
			if ( $prev_version > 0 && $prev_version <= 342 ) {
				if ( ! empty( $opts['plugin_file_cache_hrs'] ) ) {
					$opts['plugin_social_file_cache_exp'] = $opts['plugin_file_cache_hrs'] * HOUR_IN_SECONDS;
				}
				unset( $opts['plugin_file_cache_hrs'] );
			}

			if ( $options_name === constant( 'WPSSO_OPTIONS_NAME' ) ) {

				$this->p->util->rename_opts_by_ext( $opts, 
					apply_filters( $lca.'_rename_options_keys', 
						self::$rename_options_keys ) );

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
								$opts[$new_key] = $val;
							}
							unset( $opts[$key] );
						}
					}
				}

				if ( $prev_version > 0 && $prev_version <= 296 ) {
					if ( empty( $opts['plugin_min_shorten'] ) || 
						$opts['plugin_min_shorten'] < 22 ) 
							$opts['plugin_min_shorten'] = 22;
				}

				if ( $prev_version > 0 && $prev_version <= 373 ) {
					if ( ! empty( $opts['plugin_head_attr_filter_name'] ) &&
						$opts['plugin_head_attr_filter_name'] === 'language_attributes' ) 
							$opts['plugin_head_attr_filter_name'] = 'head_attributes';
				}

				if ( $prev_version > 0 && $prev_version <= 453 ) {
					$opts['add_meta_property_og:image:secure_url'] = 1;
					$opts['add_meta_property_og:video:secure_url'] = 1;
				}

			} elseif ( $options_name === constant( 'WPSSO_SITE_OPTIONS_NAME' ) ) {
				$this->p->util->rename_opts_by_ext( $opts,
					apply_filters( $lca.'_rename_site_options_keys',
						self::$rename_site_options_keys ) );
			}

			return $this->sanitize( $opts, $def_opts, $network );	// cleanup options and sanitize
		}
	}
}

?>
