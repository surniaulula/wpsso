<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoOptionsUpgrade' ) && class_exists( 'WpssoOptions' ) ) {

	class WpssoOptionsUpgrade extends WpssoOptions {

		private $renamed_site_keys = array(
			'plugin_tid' => 'plugin_wpsso_tid',
			'plugin_tid:use' => 'plugin_wpsso_tid:use',
			'plugin_ignore_small_img' => 'plugin_check_img_dims',		// renamed in v3.31.1-1
			'plugin_ignore_small_img:use' => 'plugin_check_img_dims:use',	// renamed in v3.31.1-1
			'plugin_file_cache_exp' => 'plugin_social_file_cache_exp',
			'plugin_file_cache_exp:use' => 'plugin_social_file_cache_exp:use',
		);

		private $renamed_keys = array(
			'og_img_resize' => 'plugin_create_wp_sizes',
			'plugin_tid' => 'plugin_wpsso_tid',
			'og_publisher_url' => 'fb_publisher_url',
			'add_meta_property_og:video' => 'add_meta_property_og:video:url',
			'twitter_shortener' => 'plugin_shortener',
			'stumble_js_loc' => 'stumble_script_loc',	// wpsso ssb
			'pin_js_loc' => 'pin_script_loc',		// wpsso ssb
			'tumblr_js_loc' => 'tumblr_script_loc',		// wpsso ssb
			'gp_js_loc' => 'gp_script_loc',			// wpsso ssb
			'fb_js_loc' => 'fb_script_loc',			// wpsso ssb
			'twitter_js_loc' => 'twitter_script_loc',	// wpsso ssb
			'buffer_js_loc' => 'buffer_script_loc',		// wpsso ssb
			'linkedin_js_loc' => 'linkedin_script_loc',	// wpsso ssb
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
			'buttons_use_social_css' => 'buttons_use_social_style',
			'buttons_enqueue_social_css' => 'buttons_enqueue_social_style',
			'fb_type' => 'fb_share_layout',
			'plugin_schema_type_id_col_post' => 'plugin_schema_type_col_post',	// renamed in v3.39.1-1
			'plugin_schema_type_id_col_term' => 'plugin_schema_type_col_term',	// renamed in v3.39.1-1
			'plugin_schema_type_id_col_user' => 'plugin_schema_type_col_user',	// renamed in v3.39.1-1
			'plugin_auto_img_resize' => 'plugin_create_wp_sizes',
		);

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		// def_opts accepts output from functions, so don't force reference
		public function options( $options_name, &$opts = array(), $def_opts = array() ) {

			// each plugin options version is saved to a unique key
			$opts_version = empty( $opts['plugin_'.$this->p->cf['lca'].'_opt_version'] ) ?
				0 : $opts['plugin_'.$this->p->cf['lca'].'_opt_version'];

			// older versions had a single string with all versions appended
			if ( empty( $opts_version ) )
				// our own options version should be the first numeric string
				$opts_version = empty( $opts['options_version'] ) ?
					0 : preg_replace( '/^[^0-9]*([0-9]*).*$/',
						'$1', $opts['options_version'] );

			if ( $options_name === constant( 'WPSSO_OPTIONS_NAME' ) ) {

				$opts = SucomUtil::rename_keys( $opts, $this->renamed_keys );

				if ( $opts_version && $opts_version <= 260 ) {
					if ( $opts['og_img_width'] == 1200 &&
						$opts['og_img_height'] == 630 &&
						! empty( $opts['og_img_crop'] ) ) {

						$this->p->notice->warn( 'Open Graph Image Dimentions have been updated from '.
							$opts['og_img_width'].'x'.$opts['og_img_height'].', '.
							( $opts['og_img_crop'] ? '' : 'un' ).'cropped to '.
							$def_opts['og_img_width'].'x'.$def_opts['og_img_height'].', '.
							( $def_opts['og_img_crop'] ? '' : 'un' ).'cropped.' );
	
						$opts['og_img_width'] = $def_opts['og_img_width'];
						$opts['og_img_height'] = $def_opts['og_img_height'];
						$opts['og_img_crop'] = $def_opts['og_img_crop'];
					}
				}

				if ( $opts_version && $opts_version <= 270 ) {
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
							if ( ! empty( $new_key ) )
								$opts[$new_key] = $val;
							unset( $opts[$key] );
						}
					}
				}

				if ( $opts_version && $opts_version <= 296 ) {
					if ( empty( $opts['plugin_min_shorten'] ) || 
						$opts['plugin_min_shorten'] < 22 ) 
							$opts['plugin_min_shorten'] = 22;
				}

				if ( $opts_version && $opts_version <= 373 ) {
					if ( ! empty( $opts['plugin_head_attr_filter_name'] ) &&
						$opts['plugin_head_attr_filter_name'] === 'language_attributes' ) 
							$opts['plugin_head_attr_filter_name'] = 'head_attributes';
				}

				if ( $opts_version && $opts_version <= 453 ) {
					$opts['add_meta_property_og:image:secure_url'] = 1;
					$opts['add_meta_property_og:video:secure_url'] = 1;
				}

				// removed default author options in v3.36.0-1
				if ( $opts_version && $opts_version <= 458 ) {
					unset (
						$opts['link_def_author_id'],
						$opts['link_def_author_on_index'],
						$opts['link_def_author_on_search'],
						$opts['seo_def_author_id'],
						$opts['seo_def_author_on_index'],
						$opts['seo_def_author_on_search'],
						$opts['og_def_author_id'],
						$opts['og_def_author_on_index'],
						$opts['og_def_author_on_search']
					);
				}

			} elseif ( $options_name === constant( 'WPSSO_SITE_OPTIONS_NAME' ) )
				$opts = SucomUtil::rename_keys( $opts, $this->renamed_site_keys );

			if ( $opts_version && $opts_version <= 342 ) {
				if ( isset( $opts['plugin_file_cache_hrs'] ) ) {
					$opts['plugin_social_file_cache_exp'] = $opts['plugin_file_cache_hrs'] * 3600;
					unset( $opts['plugin_file_cache_hrs'] );
				}
			}

			if ( $opts_version && $opts_version <= 473 ) {
				unset( $opts['plugin_object_cache_exp'] );
				unset( $opts['plugin_object_cache_exp:use'] );
			}

			if ( $opts_version && $opts_version <= 476 ) {
				unset( $opts['ngfb_verify_certs'] );
				unset( $opts['plugin_verify_certs'] );
			}

			return $this->sanitize( $opts, $def_opts );	// cleanup options and sanitize
		}
	}
}

?>
