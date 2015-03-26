<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoConfig' ) ) {

	class WpssoConfig {

		private static $cf = array(
			'lca' => 'wpsso',		// lowercase acronym
			'uca' => 'WPSSO',		// uppercase acronym
			'menu' => 'SSO',		// menu item label
			'color' => '3c3',		// menu item color - lime green
			'update_check_hours' => 24,
			'plugin' => array(
				'wpsso' => array(
					'version' => '2.8.5',		// plugin version
					'short' => 'WPSSO',		// short plugin name
					'name' => 'WordPress Social Sharing Optimization (WPSSO)',
					'desc' => 'Make sure social websites present your content correctly, no matter how your webpage is shared - from buttons, browser add-ons, or pasted URLs.',
					'slug' => 'wpsso',
					'base' => 'wpsso/wpsso.php',
					'img' => array(
						'icon-small' => 'https://ps.w.org/wpsso/assets/icon-128x128.png?rev=',
						'icon-medium' => 'https://ps.w.org/wpsso/assets/icon-256x256.png?rev=',
					),
					'url' => array(
						// wordpress
						'download' => 'https://wordpress.org/plugins/wpsso/',
						'review' => 'https://wordpress.org/support/view/plugin-reviews/wpsso#postform',
						'readme' => 'https://plugins.svn.wordpress.org/wpsso/trunk/readme.txt',
						'setup' => 'https://plugins.svn.wordpress.org/wpsso/trunk/setup.html',
						'wp_support' => 'https://wordpress.org/support/plugin/wpsso',
						// surniaulula
						'update' => 'http://surniaulula.com/extend/plugins/wpsso/update/',
						'purchase' => 'http://surniaulula.com/extend/plugins/wpsso/',
						'changelog' => 'http://surniaulula.com/extend/plugins/wpsso/changelog/',
						'codex' => 'http://surniaulula.com/codex/plugins/wpsso/',
						'faq' => 'http://surniaulula.com/codex/plugins/wpsso/faq/',
						'notes' => 'http://surniaulula.com/codex/plugins/wpsso/notes/',
						'feed' => 'http://surniaulula.com/category/application/wordpress/wp-plugins/wpsso/feed/',
						'pro_support' => 'http://support.wpsso.surniaulula.com/',
						'pro_ticket' => 'http://ticket.wpsso.surniaulula.com/',
					),
					'lib' => array(			// libraries
						'setting' => array (
							'contact-fields' => 'Contact Fields',
						),
						'submenu' => array (
							'general' => 'General',
							'advanced' => 'Advanced',
							'licenses' => 'Pro Licenses',
							'readme' => 'Read Me',
							'setup' => 'Setup Guide',
						),
						'sitesubmenu' => array(
							'siteadvanced' => 'Advanced',
							'sitelicenses' => 'Pro Licenses',
							'sitereadme' => 'Read Me',
							'sitesetup' => 'Setup Guide',
						),
						'gpl' => array(
							'admin' => array(
								'general' => 'General Settings',
								'advanced' => 'Advanced Settings',
								'postmeta' => 'Post Social Settings',
								'user' => 'User Social Settings',
							),
							'util' => array(
								'postmeta' => 'Post Social Settings',
								'user' => 'User Social Settings',
							),
						),
						'pro' => array(
							'admin' => array(
								'general' => 'General Settings',
								'advanced' => 'Advanced Settings',
								'postmeta' => 'Post Social Settings',
								'user' => 'User Social Settings',
							),
							'ecom' => array(
								'edd' => 'Easy Digital Downloads',
								'marketpress' => 'MarketPress',
								'woocommerce' => 'WooCommerce',
								'wpecommerce' => 'WP e-Commerce',
							),
							'forum' => array(
								'bbpress' => 'bbPress',
							),
							'head' => array(
								'twittercard' => 'Twitter Cards',
							),
							'lang' => array(
								'polylang' => 'Polylang',
							),
							'media' => array(
								'gravatar' => 'Author Gravatar',
								'ngg' => 'NextGEN Gallery',
								'photon' => 'Jetpack Photon',
								'slideshare' => 'Slideshare API',
								'vimeo' => 'Vimeo Video API',
								'wistia' => 'Wistia Video API',
								'youtube' => 'YouTube Video / Playlist API',
							),
							'seo' => array(
								'aioseop' => 'All in One SEO Pack',
								'headspace2' => 'HeadSpace2 SEO',
								'wpseo' => 'WordPress SEO',
							),
							'social' => array(
								'buddypress' => 'BuddyPress',
							),
							'util' => array(
								'language' => 'Publisher Language',
								'postmeta' => 'Post Social Settings',
								'user' => 'User Social Settings',
							),
						),
					),
				),
				'wpssoam' => array(
					'short' => 'WPSSO AM',		// short plugin name
					'name' => 'WPSSO App Meta (WPSSO AM)',
					'desc' => 'WPSSO extension to provide Apple Store / iTunes and Google Play App meta tags for Apple\'s mobile Safari and Twitter\'s App Card.',
					'slug' => 'wpsso-am',
					'img' => array(
						'icon-small' => 'https://ps.w.org/wpsso-am/assets/icon-128x128.png?rev=',
						'icon-medium' => 'https://ps.w.org/wpsso-am/assets/icon-256x256.png?rev=',
					),
					'url' => array(
						'download' => 'https://wordpress.org/plugins/wpsso-am/',
					),
				),
				'wpssoplm' => array(
					'short' => 'WPSSO PLM',		// short plugin name
					'name' => 'WPSSO Place and Location Meta (WPSSO PLM)',
					'desc' => 'WPSSO extension to provide Facebook / Open Graph "Location" and Pinterest "Place" Rich Pin meta tags.',
					'slug' => 'wpsso-plm',
					'img' => array(
						'icon-small' => 'https://ps.w.org/wpsso-plm/assets/icon-128x128.png?rev=',
						'icon-medium' => 'https://ps.w.org/wpsso-plm/assets/icon-256x256.png?rev=',
					),
					'url' => array(
						'download' => 'https://wordpress.org/plugins/wpsso-plm/',
					),
				),
				'wpssossb' => array(
					'short' => 'WPSSO SSB',		// short plugin name
					'name' => 'WPSSO Social Sharing Buttons (WPSSO SSB)',
					'desc' => 'WPSSO extension to provide fast and accurate Social Sharing Buttons, including support for hashtags, shortening, bbPress, BuddyPress, and WooCommerce.',
					'slug' => 'wpsso-ssb',
					'img' => array(
						'icon-small' => 'https://ps.w.org/wpsso-ssb/assets/icon-128x128.png?rev=',
						'icon-medium' => 'https://ps.w.org/wpsso-ssb/assets/icon-256x256.png?rev=',
					),
					'url' => array(
						'download' => 'https://wordpress.org/plugins/wpsso-ssb/',
					),
				),
			),
			'opt' => array(				// options
				'version' => 326,		// increment when changing default options
				'defaults' => array(
					'options_filtered' => false,
					'options_version' => '',
					'schema_desc_len' => 300,		// meta itemprop="description" maximum text length
					'schema_publisher_json' => 1,
					'schema_author_json' => 1,
					'schema_logo_url' => '',
					'seo_desc_len' => 156,			// meta name="description" maximum text length
					'seo_author_name' => 'none',		// meta name="author" format
					'seo_def_author_id' => 0,
					'seo_def_author_on_index' => 0,
					'seo_def_author_on_search' => 0,
					'link_author_field' => '',		// default value set by WpssoOptions::get_defaults()
					'link_publisher_url' => '',
					'fb_publisher_url' => '',
					'fb_admins' => '',
					'fb_app_id' => '',
					'fb_lang' => 'en_US',
					'linkedin_publisher_url' => '',
					'og_site_name' => '',
					'og_site_description' => '',
					'og_art_section' => 'none',
					'og_img_width' => 800,
					'og_img_height' => 800,
					'og_img_crop' => 1,
					'og_img_crop_x' => 'center',
					'og_img_crop_y' => 'center',
					'og_img_max' => 1,
					'og_vid_max' => 1,
					'og_vid_prev_img' => 1,
					'og_vid_https' => 1,
					'og_def_img_id_pre' => 'wp',
					'og_def_img_id' => '',
					'og_def_img_url' => '',
					'og_def_img_on_index' => 1,
					'og_def_img_on_author' => 0,
					'og_def_img_on_search' => 0,
					'og_def_vid_url' => '',
					'og_def_vid_on_index' => 1,
					'og_def_vid_on_author' => 0,
					'og_def_vid_on_search' => 0,
					'og_def_author_id' => 0,
					'og_def_author_on_index' => 0,
					'og_def_author_on_search' => 0,
					'og_ngg_tags' => 0,
					'og_page_parent_tags' => 0,
					'og_page_title_tag' => 0,
					'og_author_field' => '',		// default value set by WpssoOptions::get_defaults()
					'og_author_fallback' => 0,
					'og_title_sep' => '-',
					'og_title_len' => 70,
					'og_desc_len' => 300,
					'og_desc_hashtags' => 3,
					'og_desc_strip' => 0,
					'og_desc_alt' => 1,
					'rp_publisher_url' => '',
					'rp_author_name' => 'display_name',	// rich-pin specific article:author
					'rp_img_width' => 800,
					'rp_img_height' => 800,
					'rp_img_crop' => 0,
					'rp_img_crop_x' => 'center',
					'rp_img_crop_y' => 'center',
					'tc_enable' => 1,
					'tc_site' => '',
					'tc_desc_len' => 200,
					// summary card
					'tc_sum_width' => 300,
					'tc_sum_height' => 300,
					'tc_sum_crop' => 1,
					'tc_sum_crop_x' => 'center',
					'tc_sum_crop_y' => 'center',
					// large image summary card
					'tc_lrgimg_width' => 800,
					'tc_lrgimg_height' => 800,
					'tc_lrgimg_crop' => 0,
					'tc_lrgimg_crop_x' => 'center',
					'tc_lrgimg_crop_y' => 'center',
					// photo card
					'tc_photo_width' => 800,
					'tc_photo_height' => 800,
					'tc_photo_crop' => 0,
					'tc_photo_crop_x' => 'center',
					'tc_photo_crop_y' => 'center',
					// gallery card
					'tc_gal_min' => 4,
					'tc_gal_width' => 300,
					'tc_gal_height' => 300,
					'tc_gal_crop' => 0,
					'tc_gal_crop_x' => 'center',
					'tc_gal_crop_y' => 'center',
					// product card
					'tc_prod_width' => 300,
					'tc_prod_height' => 300,
					'tc_prod_crop' => 1,   			 // prefers square product images
					'tc_prod_crop_x' => 'center',
					'tc_prod_crop_y' => 'center',
					'tc_prod_labels' => 2,
					'tc_prod_def_label2' => 'Location',
					'tc_prod_def_data2' => 'Unknown',
					// enable/disable header html tags
					'add_link_rel_author' => 1,
					'add_link_rel_publisher' => 1,
					'add_meta_property_fb:admins' => 1,
					'add_meta_property_fb:app_id' => 1,
					'add_meta_property_og:locale' => 1,
					'add_meta_property_og:site_name' => 1,
					'add_meta_property_og:description' => 1,
					'add_meta_property_og:title' => 1,
					'add_meta_property_og:type' => 1,
					'add_meta_property_og:url' => 1,
					'add_meta_property_og:image' => 1,
					'add_meta_property_og:image:secure_url' => 1,
					'add_meta_property_og:image:width' => 1,
					'add_meta_property_og:image:height' => 1,
					'add_meta_property_og:video' => 1,
					'add_meta_property_og:video:secure_url' => 1,
					'add_meta_property_og:video:width' => 1,
					'add_meta_property_og:video:height' => 1,
					'add_meta_property_og:video:type' => 1,
					'add_meta_property_article:author' => 1,
					'add_meta_property_article:publisher' => 1,
					'add_meta_property_article:published_time' => 1,
					'add_meta_property_article:modified_time' => 1,
					'add_meta_property_article:section' => 1,
					'add_meta_property_article:tag' => 1,
					'add_meta_property_product:price:amount' => 1,
					'add_meta_property_product:price:currency' => 1,
					'add_meta_property_product:availability' => 1,
					'add_meta_name_twitter:card' => 1,
					'add_meta_name_twitter:creator' => 1,
					'add_meta_name_twitter:domain' => 1,
					'add_meta_name_twitter:site' => 1,
					'add_meta_name_twitter:title' => 1,
					'add_meta_name_twitter:description' => 1,
					'add_meta_name_twitter:image' => 1,
					'add_meta_name_twitter:image:width' => 1,
					'add_meta_name_twitter:image:height' => 1,
					'add_meta_name_twitter:image0' => 1,
					'add_meta_name_twitter:image1' => 1,
					'add_meta_name_twitter:image2' => 1,
					'add_meta_name_twitter:image3' => 1,
					'add_meta_name_twitter:player' => 1,
					'add_meta_name_twitter:player:width' => 1,
					'add_meta_name_twitter:player:height' => 1,
					'add_meta_name_twitter:data1' => 1,
					'add_meta_name_twitter:label1' => 1,
					'add_meta_name_twitter:data2' => 1,
					'add_meta_name_twitter:label2' => 1,
					'add_meta_name_twitter:data3' => 1,
					'add_meta_name_twitter:label3' => 1,
					'add_meta_name_twitter:data4' => 1,
					'add_meta_name_twitter:label4' => 1,
					'add_meta_name_generator' => 1,
					'add_meta_name_author' => 1,
					'add_meta_name_description' => 0,
					'add_meta_itemprop_description' => 1,
					'add_meta_itemprop_url' => 1,
					'add_meta_itemprop_image' => 1,
					// advanced plugin options
					'plugin_version' => '',
					'plugin_wpsso_tid' => '',
					'plugin_show_opts' => 'basic',
					'plugin_preserve' => 0,
					'plugin_debug' => 0,
					'plugin_cache_info' => 0,
					'plugin_check_head' => 1,
					'plugin_filter_title' => 1,
					'plugin_filter_excerpt' => 0,
					'plugin_filter_content' => 1,
					'plugin_filter_lang' => 1,
					'plugin_shortcodes' => 1,
					'plugin_widgets' => 1,
					'plugin_auto_img_resize' => 1,
					'plugin_ignore_small_img' => 1,
					'plugin_page_excerpt' => 1,
					'plugin_page_tags' => 1,
					'plugin_gravatar_api' => 1,
					'plugin_slideshare_api' => 1,
					'plugin_vimeo_api' => 1,
					'plugin_wistia_api' => 1,
					'plugin_youtube_api' => 1,
					'plugin_cf_img_url' => '_image_url',
					'plugin_cf_vid_url' => '_video_url',
					'plugin_cf_vid_embed' => '_video_embed',
					'plugin_add_to_user' => 1,
					'plugin_add_to_post' => 1,
					'plugin_add_to_page' => 1,
					'plugin_add_to_attachment' => 1,
					'plugin_object_cache_exp' => 21600,	// 6 hours
					'plugin_file_cache_hrs' => 0,
					'plugin_verify_certs' => 0,
					'plugin_cm_fb_name' => 'facebook', 
					'plugin_cm_fb_label' => 'Facebook URL', 
					'plugin_cm_fb_enabled' => 1,
					'plugin_cm_gp_name' => 'gplus', 
					'plugin_cm_gp_label' => 'Google+ URL', 
					'plugin_cm_gp_enabled' => 1,
					'plugin_cm_linkedin_name' => 'linkedin', 
					'plugin_cm_linkedin_label' => 'LinkedIn URL', 
					'plugin_cm_linkedin_enabled' => 0,
					'plugin_cm_pin_name' => 'pinterest', 
					'plugin_cm_pin_label' => 'Pinterest URL', 
					'plugin_cm_pin_enabled' => 0,
					'plugin_cm_tumblr_name' => 'tumblr', 
					'plugin_cm_tumblr_label' => 'Tumblr URL', 
					'plugin_cm_tumblr_enabled' => 0,
					'plugin_cm_twitter_name' => 'twitter', 
					'plugin_cm_twitter_label' => 'Twitter @username', 
					'plugin_cm_twitter_enabled' => 1,
					'plugin_cm_yt_name' => 'youtube', 
					'plugin_cm_yt_label' => 'YouTube Channel URL', 
					'plugin_cm_yt_enabled' => 0,
					'plugin_cm_skype_name' => 'skype', 
					'plugin_cm_skype_label' => 'Skype Username', 
					'plugin_cm_skype_enabled' => 0,
					'wp_cm_aim_name' => 'aim', 
					'wp_cm_aim_label' => 'AIM', 
					'wp_cm_aim_enabled' => 1,
					'wp_cm_jabber_name' => 'jabber', 
					'wp_cm_jabber_label' => 'Google Talk', 
					'wp_cm_jabber_enabled' => 1,
					'wp_cm_yim_name' => 'yim',
					'wp_cm_yim_label' => 'Yahoo IM', 
					'wp_cm_yim_enabled' => 1,
				),
				'site_defaults' => array(
					'options_filtered' => false,
					'options_version' => '',
					'plugin_version' => '',
					'plugin_wpsso_tid' => '',
					'plugin_wpsso_tid:use' => 'default',
					'plugin_preserve' => 1,
					'plugin_preserve:use' => 'default',
					'plugin_debug' => 0,
					'plugin_debug:use' => 'default',
					'plugin_object_cache_exp' => 21600,	// 6 hours
					'plugin_object_cache_exp:use' => 'default',
					'plugin_file_cache_hrs' => 0,
					'plugin_file_cache_hrs:use' => 'default',
					'plugin_verify_certs' => 0,
					'plugin_verify_certs:use' => 'default',
				),
				'pre' => array(
					'facebook' => 'fb', 
					'gplus' => 'gp',
					'twitter' => 'twitter',
					'linkedin' => 'linkedin',
					'pinterest' => 'pin',
					'buffer' => 'buffer',
					'reddit' => 'reddit',
					'managewp' => 'managewp',
					'stumbleupon' => 'stumble',
					'tumblr' => 'tumblr',
					'youtube' => 'yt',
					'skype' => 'skype',
				),
			),
			'wp' => array(				// wordpress
				'min_version' => '3.0',		// minimum wordpress version
				'cm' => array(
					'aim' => 'AIM',
					'jabber' => 'Google Talk',
					'yim' => 'Yahoo IM',
				),
			),
			'php' => array(				// php
				'min_version' => '4.0.6',	// minimum php version
			),
			'follow' => array(
				'size' => 32,
				'src' => array(
					'facebook.png' => 'https://www.facebook.com/SurniaUlulaCom',
					'gplus.png' => 'https://plus.google.com/+SurniaUlula/',
					'linkedin.png' => 'https://www.linkedin.com/in/jsmoriss',
					'twitter.png' => 'https://twitter.com/surniaululacom',
					'youtube.png' => 'https://www.youtube.com/user/SurniaUlulaCom',
					'feed.png' => 'http://feed.surniaulula.com/category/application/wordpress/wp-plugins/wpsso/feed/',
				),
			),
			'form' => array(
				'tooltip_class' => 'sucom_tooltip',
				'max_desc_hashtags' => 10,
				'max_media_items' => 20,
				'yes_no' => array( '1' => 'Yes', '0' => 'No' ),
				'file_cache_hrs' => array( 0, 1, 3, 6, 9, 12, 24, 36, 48, 72, 168 ),
				'js_locations' => array( 'none' => '[none]', 'header' => 'Header', 'footer' => 'Footer' ),
				'caption_types' => array( 'none' => '[none]', 'title' => 'Title Only', 'excerpt' => 'Excerpt Only', 'both' => 'Title and Excerpt' ),
				'user_name_fields' => array( 'none' => '[none]', 'fullname' => 'First and Last Names', 'display_name' => 'Display Name', 'nickname' => 'Nickname' ),
				'show_options' => array( 'basic' => 'Basic Options', 'all' => 'All Options' ),
				'site_option_use' => array( 'default' => 'Default Site Value', 'empty' => 'If Value is Empty', 'force' => 'Force This Value' ),
				'position_crop_x' => array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right' ),
				'position_crop_y' => array( 'top' => 'Top', 'center' => 'Center', 'bottom' => 'Bottom' ),
			),
			'head' => array(
				'max_img_ratio' => 3,
				'min_img_dim' => 200,
				'min_desc_len' => 156,
			),
			'cache' => array(
				'file' => true,
				'object' => true,
				'transient' => true,
			),
		);

		// get_config is called very early, so don't apply filters unless instructed
		public static function get_config( $idx = false, $filter = false ) { 

			if ( ! isset( self::$cf['config_filtered'] ) || self::$cf['config_filtered'] !== true ) {
				if ( $filter === true ) {
					self::$cf = apply_filters( self::$cf['lca'].'_get_config', self::$cf );
					self::$cf['config_filtered'] = true;
					self::$cf['*'] = array(
						'lib' => array(),
						'version' => '',
					);
					foreach ( self::$cf['plugin'] as $lca => $info ) {
						if ( isset( $info['lib'] ) && is_array( $info['lib'] ) )
							self::$cf['*']['lib'] = SucomUtil::array_merge_recursive_distinct( self::$cf['*']['lib'], $info['lib'] );
						if ( isset( $info['version'] ) )
							self::$cf['*']['version'] .= '-'.$lca.$info['version'];
					}
					self::$cf['*']['version'] = trim( self::$cf['*']['version'], '-' );
				}
			}

			if ( ! empty( $idx ) ) {
				if ( array_key_exists( $idx, self::$cf ) )
					return self::$cf[$idx];
				else return false;
			} else return self::$cf;
		}

		public static function set_constants( $plugin_filepath ) { 

			$cf = self::get_config();
			$slug = $cf['plugin'][$cf['lca']]['slug'];
			$version = $cf['plugin'][$cf['lca']]['version'];

			define( 'WPSSO_FILEPATH', $plugin_filepath );						
			define( 'WPSSO_PLUGINDIR', trailingslashit( plugin_dir_path( $plugin_filepath ) ) );
			define( 'WPSSO_PLUGINBASE', plugin_basename( $plugin_filepath ) );
			define( 'WPSSO_TEXTDOM', $slug );
			define( 'WPSSO_URLPATH', trailingslashit( plugins_url( '', $plugin_filepath ) ) );
			define( 'WPSSO_NONCE', md5( WPSSO_PLUGINDIR.'-'.$version.
				( defined( 'NONCE_SALT' ) ? NONCE_SALT : '' ) ) );

			if ( defined( 'WPSSO_DEBUG' ) && 
				! defined( 'WPSSO_HTML_DEBUG' ) )
					define( 'WPSSO_HTML_DEBUG', WPSSO_DEBUG );

			if ( ! defined( 'WPSSO_DEBUG_FILE_EXP' ) )
				define( 'WPSSO_DEBUG_FILE_EXP', 300 );

			if ( ! defined( 'WPSSO_CACHEDIR' ) )
				define( 'WPSSO_CACHEDIR', WPSSO_PLUGINDIR.'cache/' );

			if ( ! defined( 'WPSSO_CACHEURL' ) )
				define( 'WPSSO_CACHEURL', WPSSO_URLPATH.'cache/' );

			if ( ! defined( 'WPSSO_TOPICS_LIST' ) )
				define( 'WPSSO_TOPICS_LIST', WPSSO_PLUGINDIR.'share/topics.txt' );

			/*
			 * WPSSO option and meta array names
			 */
			if ( ! defined( 'WPSSO_OPTIONS_NAME' ) )
				define( 'WPSSO_OPTIONS_NAME', 'wpsso_options' );

			if ( ! defined( 'WPSSO_SITE_OPTIONS_NAME' ) )
				define( 'WPSSO_SITE_OPTIONS_NAME', 'wpsso_site_options' );

			if ( ! defined( 'WPSSO_META_NAME' ) )
				define( 'WPSSO_META_NAME', '_wpsso_meta' );

			if ( ! defined( 'WPSSO_PREF_NAME' ) )
				define( 'WPSSO_PREF_NAME', '_wpsso_pref' );

			/*
			 * WPSSO option and meta array alternate / fallback names
			 */
			if ( ! defined( 'WPSSO_OPTIONS_NAME_ALT' ) )
				define( 'WPSSO_OPTIONS_NAME_ALT', 'ngfb_options' );

			if ( ! defined( 'WPSSO_SITE_OPTIONS_NAME_ALT' ) )
				define( 'WPSSO_SITE_OPTIONS_NAME_ALT', 'ngfb_site_options' );

			if ( ! defined( 'WPSSO_META_NAME_ALT' ) )
				define( 'WPSSO_META_NAME_ALT', '_ngfb_meta' );

			if ( ! defined( 'WPSSO_PREF_NAME_ALT' ) )
				define( 'WPSSO_PREF_NAME_ALT', '_ngfb_pref' );

			/*
			 * WPSSO hook priorities
			 */
			if ( ! defined( 'WPSSO_META_SAVE_PRIORITY' ) )
				define( 'WPSSO_META_SAVE_PRIORITY', 6 );

			if ( ! defined( 'WPSSO_META_CACHE_PRIORITY' ) )
				define( 'WPSSO_META_CACHE_PRIORITY', 9 );

			if ( ! defined( 'WPSSO_MENU_PRIORITY' ) )
				define( 'WPSSO_MENU_PRIORITY', '99.10' );

			if ( ! defined( 'WPSSO_INIT_PRIORITY' ) )
				define( 'WPSSO_INIT_PRIORITY', 12 );

			if ( ! defined( 'WPSSO_HEAD_PRIORITY' ) )
				define( 'WPSSO_HEAD_PRIORITY', 10 );

			/*
			 * WPSSO curl settings
			 */
			if ( ! defined( 'WPSSO_CURL_USERAGENT' ) )
				define( 'WPSSO_CURL_USERAGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36' );

			if ( ! defined( 'WPSSO_CURL_CAINFO' ) )
				define( 'WPSSO_CURL_CAINFO', WPSSO_PLUGINDIR.'share/curl/cacert.pem' );
		}

		public static function require_libs( $plugin_filepath ) {
			
			$cf = self::get_config();

			require_once( WPSSO_PLUGINDIR.'lib/com/update.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/util.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/cache.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/notice.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/script.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/style.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/webpage.php' );

			require_once( WPSSO_PLUGINDIR.'lib/check.php' );
			require_once( WPSSO_PLUGINDIR.'lib/util.php' );
			require_once( WPSSO_PLUGINDIR.'lib/options.php' );
			require_once( WPSSO_PLUGINDIR.'lib/postmeta.php' );
			require_once( WPSSO_PLUGINDIR.'lib/user.php' );
			require_once( WPSSO_PLUGINDIR.'lib/media.php' );
			require_once( WPSSO_PLUGINDIR.'lib/head.php' );
			require_once( WPSSO_PLUGINDIR.'lib/opengraph.php' );
			require_once( WPSSO_PLUGINDIR.'lib/schema.php' );

			if ( is_admin() ) {
				require_once( WPSSO_PLUGINDIR.'lib/messages.php' );
				require_once( WPSSO_PLUGINDIR.'lib/admin.php' );
				require_once( WPSSO_PLUGINDIR.'lib/com/form.php' );
				require_once( WPSSO_PLUGINDIR.'lib/ext/parse-readme.php' );
			}

			if ( file_exists( WPSSO_PLUGINDIR.'lib/loader.php' ) )
				require_once( WPSSO_PLUGINDIR.'lib/loader.php' );

			add_filter( 'wpsso_load_lib', array( 'WpssoConfig', 'load_lib' ), 10, 3 );
		}

		public static function load_lib( $ret = false, $filespec = '', $classname = '' ) {
			if ( $ret === false && ! empty( $filespec ) ) {
				$filepath = WPSSO_PLUGINDIR.'lib/'.$filespec.'.php';
				if ( file_exists( $filepath ) ) {
					require_once( $filepath );
					if ( empty( $classname ) )
						return 'wpsso'.str_replace( array( '/', '-' ), '', $filespec );
					else return $classname;
				}
			}
			return $ret;
		}
	}
}

?>
