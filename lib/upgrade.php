<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoOptionsUpgrade' ) && class_exists( 'WpssoOptions' ) ) {

	class WpssoOptionsUpgrade extends WpssoOptions {

		private $renamed_site_keys = array(
			'plugin_tid' => 'plugin_wpsso_tid',
		);

		private $renamed_keys = array(
			'og_img_resize' => 'plugin_auto_img_resize',
			'link_def_author_id' => 'seo_def_author_id',
			'link_def_author_on_index' => 'seo_def_author_on_index',
			'link_def_author_on_search' => 'seo_def_author_on_search',
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
		);

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		// def_opts accepts output from functions, so don't force reference
		public function options( $options_name, &$opts = array(), $def_opts = array() ) {

			// retrieve the first numeric string
			$opts_version = empty( $opts['options_version'] ) ? 0 :
				preg_replace( '/^[^0-9]*([0-9]*).*$/', '$1', $opts['options_version'] );

			if ( $options_name === constant( 'WPSSO_OPTIONS_NAME' ) ) {

				$opts = SucomUtil::rename_keys( $opts, $this->renamed_keys );

				if ( version_compare( $opts_version, 260, '<=' ) ) {
					if ( $opts['og_img_width'] == 1200 &&
						$opts['og_img_height'] == 630 &&
						! empty( $opts['og_img_crop'] ) ) {

						$this->p->notice->inf( 'Open Graph Image Dimentions have been updated from '.
							$opts['og_img_width'].'x'.$opts['og_img_height'].', '.
							( $opts['og_img_crop'] ? '' : 'un' ).'cropped to '.
							$def_opts['og_img_width'].'x'.$def_opts['og_img_height'].', '.
							( $def_opts['og_img_crop'] ? '' : 'un' ).'cropped.', true );
	
						$opts['og_img_width'] = $def_opts['og_img_width'];
						$opts['og_img_height'] = $def_opts['og_img_height'];
						$opts['og_img_crop'] = $def_opts['og_img_crop'];
					}
				}

				if ( version_compare( $opts_version, 270, '<=' ) ) {
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

			} elseif ( $options_name === constant( 'WPSSO_SITE_OPTIONS_NAME' ) )
				$opts = SucomUtil::rename_keys( $opts, $this->renamed_site_keys );

			if ( version_compare( $opts_version, 342, '<=' ) ) {
				if ( isset( $opts['plugin_file_cache_hrs'] ) ) {
					$opts['plugin_file_cache_exp'] = $opts['plugin_file_cache_hrs'] * 3600;
					unset( $opts['plugin_file_cache_hrs'] );
				}
			}

			return $this->sanitize( $opts, $def_opts );	// cleanup options and sanitize
		}
	}
}

?>
