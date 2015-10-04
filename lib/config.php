<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://wpsso.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoConfig' ) ) {

	class WpssoConfig {

		public static $cf = array(
			'lca' => 'wpsso',		// lowercase acronym
			'uca' => 'WPSSO',		// uppercase acronym
			'menu' => 'SSO',		// menu item label
			'color' => '47d147',		// menu item color - lime green / shades of 33cc33
			'feed_cache_exp' => 86400,	// 24 hours
			'plugin' => array(
				'wpsso' => array(
					'version' => '3.10.3',		// plugin version
					'short' => 'WPSSO',		// short plugin name
					'name' => 'WordPress Social Sharing Optimization (WPSSO)',
					'desc' => 'Improve WordPress editing and publishing for better content on all social websites - no matter how your content is shared or re-shared!',
					'slug' => 'wpsso',
					'base' => 'wpsso/wpsso.php',
					'update_auth' => 'tid',
					'text_domain' => 'wpsso',
					'domain_path' => '/languages',
					'img' => array(
						'icon_small' => 'images/icon-128x128.png',
						'icon_medium' => 'images/icon-256x256.png',
						'background' => 'images/background.jpg',
					),
					'url' => array(
						// wordpress
						'download' => 'https://wordpress.org/plugins/wpsso/',
						'review' => 'https://wordpress.org/support/view/plugin-reviews/wpsso?filter=5&rate=5#postform',
						'readme' => 'https://plugins.svn.wordpress.org/wpsso/trunk/readme.txt',
						'setup' => 'https://plugins.svn.wordpress.org/wpsso/trunk/setup.html',
						'wp_support' => 'https://wordpress.org/support/plugin/wpsso',
						// surniaulula
						'update' => 'http://wpsso.com/extend/plugins/wpsso/update/',
						'purchase' => 'http://wpsso.com/extend/plugins/wpsso/',
						'changelog' => 'http://wpsso.com/extend/plugins/wpsso/changelog/',
						'codex' => 'http://wpsso.com/codex/plugins/wpsso/',
						'faq' => 'http://wpsso.com/codex/plugins/wpsso/faq/',
						'notes' => 'http://wpsso.com/codex/plugins/wpsso/notes/',
						'feed' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso/feed/',
						'pro_support' => 'http://wpsso.support.wpsso.com/',
					),
					'lib' => array(			// libraries
						'setting' => array (
							'wpsso-separator-0' => 'SSO',
							'image-dimensions' => 'Social Image Dimensions',
							'social-accounts' => 'Website / Business Social Accounts',
							'contact-fields' => 'User Profile Contact Methods',
							'wpsso-separator-1' => '',
						),
						'submenu' => array (
							'general' => 'General',
							'advanced' => 'Advanced',
							'readme' => 'Read Me',
							'setup' => 'Setup Guide',
							'licenses' => 'Extension Plugins and Pro Licenses',
						),
						'sitesubmenu' => array(
							'siteadvanced' => 'Advanced',
							'sitereadme' => 'Read Me',
							'sitesetup' => 'Setup Guide',
							'sitelicenses' => 'Extension Plugins and Pro Licenses',
						),
						'gpl' => array(
							'admin' => array(
								'general' => 'General Settings',
								'advanced' => 'Advanced Settings',
								'post' => 'Post Social Settings',
								'taxonomy' => 'Taxonomy Social Settings',
								'user' => 'User Social Settings',
							),
							'util' => array(
								'post' => 'Post Social Settings',
								'taxonomy' => 'Taxonomy Social Settings',
								'user' => 'User Social Settings',
							),
						),
						'pro' => array(
							'admin' => array(
								'general' => 'General Settings',
								'advanced' => 'Advanced Settings',
								'post' => 'Post Social Settings',
								'taxonomy' => 'Taxonomy Social Settings',
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
								'wpseo' => 'Yoast SEO',
							),
							'social' => array(
								'buddypress' => 'BuddyPress',
							),
							'util' => array(
								'language' => 'Publisher Language',
								'shorten' => 'URL Shortening',
								'post' => 'Post Social Settings',
								'restapi' => 'WP REST API v2',
								'taxonomy' => 'Taxonomy Social Settings',
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
					'base' => 'wpsso-am/wpsso-am.php',
					'update_auth' => 'tid',
					'img' => array(
						'icon_small' => 'https://surniaulula.github.io/wpsso-am/assets/icon-128x128.png',
						'icon_medium' => 'https://surniaulula.github.io/wpsso-am/assets/icon-256x256.png',
					),
					'url' => array(
						// wordpress
						'download' => 'https://wordpress.org/plugins/wpsso-am/',
						'review' => 'https://wordpress.org/support/view/plugin-reviews/wpsso-am?filter=5&rate=5#postform',
						'readme' => 'https://plugins.svn.wordpress.org/wpsso-am/trunk/readme.txt',
						'wp_support' => 'https://wordpress.org/support/plugin/wpsso-am',
						// surniaulula
						'update' => 'http://wpsso.com/extend/plugins/wpsso-am/update/',
						'purchase' => 'http://wpsso.com/extend/plugins/wpsso-am/',
						'changelog' => 'http://wpsso.com/extend/plugins/wpsso-am/changelog/',
						'codex' => 'http://wpsso.com/codex/plugins/wpsso-am/',
						'faq' => 'http://wpsso.com/codex/plugins/wpsso-am/faq/',
						'notes' => '',
						'feed' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso-am/feed/',
						'pro_support' => 'http://wpsso-am.support.wpsso.com/',
					),
				),
				'wpssoplm' => array(
					'short' => 'WPSSO PLM',		// short plugin name
					'name' => 'WPSSO Place and Location Meta (WPSSO PLM)',
					'desc' => 'WPSSO extension to provide Facebook / Open Graph "Location" and Pinterest "Place" Rich Pin meta tags.',
					'slug' => 'wpsso-plm',
					'base' => 'wpsso-plm/wpsso-plm.php',
					'update_auth' => 'tid',
					'img' => array(
						'icon_small' => 'https://surniaulula.github.io/wpsso-plm/assets/icon-128x128.png',
						'icon_medium' => 'https://surniaulula.github.io/wpsso-plm/assets/icon-256x256.png',
					),
					'url' => array(
						// wordpress
						'download' => 'https://wordpress.org/plugins/wpsso-plm/',
						'review' => 'https://wordpress.org/support/view/plugin-reviews/wpsso-plm?filter=5&rate=5#postform',
						'readme' => 'https://plugins.svn.wordpress.org/wpsso-plm/trunk/readme.txt',
						'wp_support' => 'https://wordpress.org/support/plugin/wpsso-plm',
						// surniaulula
						'update' => 'http://wpsso.com/extend/plugins/wpsso-plm/update/',
						'purchase' => 'http://wpsso.com/extend/plugins/wpsso-plm/',
						'changelog' => 'http://wpsso.com/extend/plugins/wpsso-plm/changelog/',
						'codex' => 'http://wpsso.com/codex/plugins/wpsso-plm/',
						'faq' => 'http://wpsso.com/codex/plugins/wpsso-plm/faq/',
						'notes' => '',
						'feed' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso-plm/feed/',
						'pro_support' => 'http://wpsso-plm.support.wpsso.com/',
					),
				),
				'wpssorrssb' => array(
					'short' => 'WPSSO RRSSB',		// short plugin name
					'name' => 'WPSSO Ridiculously Responsive Social Sharing Buttons (WPSSO RRSSB)',
					'desc' => 'WPSSO extension to add Ridiculously Responsive (SVG) Social Sharing Buttons in your content, excerpts, CSS sidebar, widget, shortcode, etc.',
					'slug' => 'wpsso-rrssb',
					'base' => 'wpsso-rrssb/wpsso-rrssb.php',
					'update_auth' => 'tid',
					'img' => array(
						'icon_small' => 'https://surniaulula.github.io/wpsso-rrssb/assets/icon-128x128.png',
						'icon_medium' => 'https://surniaulula.github.io/wpsso-rrssb/assets/icon-256x256.png',
					),
					'url' => array(
						// wordpress.org
						'download' => 'https://wordpress.org/plugins/wpsso-rrssb/',
						'review' => 'https://wordpress.org/support/view/plugin-reviews/wpsso-rrssb?filter=5&rate=5#postform',
						'readme' => 'https://plugins.svn.wordpress.org/wpsso-rrssb/trunk/readme.txt',
						'wp_support' => 'https://wordpress.org/support/plugin/wpsso-rrssb',
						// wpsso.com
						'update' => 'http://wpsso.com/extend/plugins/wpsso-rrssb/update/',
						'purchase' => 'http://wpsso.com/extend/plugins/wpsso-rrssb/',
						'changelog' => 'http://wpsso.com/extend/plugins/wpsso-rrssb/changelog/',
						'codex' => 'http://wpsso.com/codex/plugins/wpsso-rrssb/',
						'faq' => 'http://wpsso.com/codex/plugins/wpsso-rrssb/faq/',
						'notes' => '',
						'feed' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso-rrssb/feed/',
						'pro_support' => 'http://wpsso-rrssb.support.wpsso.com/',
					),
				),
				'wpssossb' => array(
					'short' => 'WPSSO SSB',		// short plugin name
					'name' => 'WPSSO Social Sharing Buttons (WPSSO SSB)',
					'desc' => 'WPSSO extension to provide fast and accurate Social Sharing Buttons, including support for hashtags, short URLs, bbPress, BuddyPress, and WooCommerce.',
					'slug' => 'wpsso-ssb',
					'base' => 'wpsso-ssb/wpsso-ssb.php',
					'update_auth' => 'tid',
					'img' => array(
						'icon_small' => 'https://surniaulula.github.io/wpsso-ssb/assets/icon-128x128.png',
						'icon_medium' => 'https://surniaulula.github.io/wpsso-ssb/assets/icon-256x256.png',
					),
					'url' => array(
						// wordpress
						'download' => 'https://wordpress.org/plugins/wpsso-ssb/',
						'review' => 'https://wordpress.org/support/view/plugin-reviews/wpsso-ssb?filter=5&rate=5#postform',
						'readme' => 'https://plugins.svn.wordpress.org/wpsso-ssb/trunk/readme.txt',
						'wp_support' => 'https://wordpress.org/support/plugin/wpsso-ssb',
						// surniaulula
						'update' => 'http://wpsso.com/extend/plugins/wpsso-ssb/update/',
						'purchase' => 'http://wpsso.com/extend/plugins/wpsso-ssb/',
						'changelog' => 'http://wpsso.com/extend/plugins/wpsso-ssb/changelog/',
						'codex' => 'http://wpsso.com/codex/plugins/wpsso-ssb/',
						'faq' => 'http://wpsso.com/codex/plugins/wpsso-ssb/faq/',
						'notes' => '',
						'feed' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso-ssb/feed/',
						'pro_support' => 'http://wpsso-ssb.support.wpsso.com/',
					),
				),
				'wpssoum' => array(
					'short' => 'WPSSO UM',		// short plugin name
					'name' => 'WPSSO Pro Update Manager (WPSSO UM)',
					'desc' => 'WPSSO extension to provide updates for the WordPress Social Sharing Optimization (WPSSO) Pro plugin and its extensions.',
					'slug' => 'wpsso-um',
					'base' => 'wpsso-um/wpsso-um.php',
					'update_auth' => '',
					'img' => array(
						'icon_small' => 'https://surniaulula.github.io/wpsso-um/assets/icon-128x128.png',
						'icon_medium' => 'https://surniaulula.github.io/wpsso-um/assets/icon-256x256.png',
					),
					'url' => array(
						// surniaulula
						'download' => 'http://wpsso.com/extend/plugins/wpsso-um/',
						'latest_zip' => 'http://wpsso.com/extend/plugins/wpsso-um/latest/',
						'review' => '',
						'readme' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-um/master/readme.txt',
						'wp_support' => '',
						'update' => 'http://wpsso.com/extend/plugins/wpsso-um/update/',
						'purchase' => '',
						'changelog' => 'http://wpsso.com/extend/plugins/wpsso-um/changelog/',
						'codex' => '',
						'faq' => '',
						'notes' => '',
						'feed' => '',
						'pro_support' => '',
					),
				),
			),
			'opt' => array(						// options
				'version' => 'sso360',				// increment when changing default options
				'defaults' => array(
					'options_filtered' => false,
					'schema_desc_len' => 250,		// meta itemprop="description" maximum text length
					'schema_website_json' => 1,
					'schema_publisher_json' => 1,
					'schema_author_json' => 1,
					'schema_logo_url' => '',
					'seo_desc_len' => 156,			// meta name="description" maximum text length
					'seo_author_name' => 'none',		// meta name="author" format
					'seo_def_author_id' => 0,
					'seo_def_author_on_index' => 0,
					'seo_def_author_on_search' => 0,
					'seo_author_field' => '',		// default value set by WpssoOptions::get_defaults()
					'seo_publisher_url' => '',
					'fb_publisher_url' => '',
					'fb_admins' => '',
					'fb_app_id' => '',
					'fb_lang' => 'en_US',
					'instgram_publisher_url' => '',
					'linkedin_publisher_url' => '',
					'myspace_publisher_url' => '',
					'og_site_name' => '',
					'og_site_description' => '',
					'og_art_section' => 'none',
					'og_img_width' => 600,
					'og_img_height' => 600,
					'og_img_crop' => 1,
					'og_img_crop_x' => 'center',
					'og_img_crop_y' => 'center',
					'og_img_max' => 1,
					'og_vid_max' => 1,
					'og_vid_https' => 1,
					'og_vid_prev_img' => 0,
					'og_vid_html_type' => 1,
					'og_def_img_id_pre' => 'wp',
					'og_def_img_id' => '',
					'og_def_img_url' => '',
					'og_def_img_on_index' => 1,
					'og_def_img_on_search' => 0,
					'og_def_vid_url' => '',
					'og_def_vid_on_index' => 1,
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
					'rp_publisher_url' => '',
					'rp_author_name' => 'display_name',	// rich-pin specific article:author
					'rp_img_width' => 600,
					'rp_img_height' => 600,
					'rp_img_crop' => 0,
					'rp_img_crop_x' => 'center',
					'rp_img_crop_y' => 'center',
					'rp_dom_verify' => '',
					'tc_site' => '',
					'tc_desc_len' => 200,
					// summary card
					'tc_sum_width' => 300,
					'tc_sum_height' => 300,
					'tc_sum_crop' => 1,
					'tc_sum_crop_x' => 'center',
					'tc_sum_crop_y' => 'center',
					// large image summary card
					'tc_lrgimg_width' => 600,
					'tc_lrgimg_height' => 600,
					'tc_lrgimg_crop' => 0,
					'tc_lrgimg_crop_x' => 'center',
					'tc_lrgimg_crop_y' => 'center',
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
					'add_meta_property_og:video:url' => 1,
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
					'add_meta_name_author' => 1,
					'add_meta_name_canonical' => 0,
					'add_meta_name_description' => 1,
					'add_meta_name_generator' => 1,
					'add_meta_name_p:domain_verify' => 1,
					'add_meta_name_twitter:card' => 1,
					'add_meta_name_twitter:creator' => 1,
					'add_meta_name_twitter:domain' => 1,
					'add_meta_name_twitter:site' => 1,
					'add_meta_name_twitter:title' => 1,
					'add_meta_name_twitter:description' => 1,
					'add_meta_name_twitter:image' => 1,
					'add_meta_name_twitter:image:width' => 1,
					'add_meta_name_twitter:image:height' => 1,
					'add_meta_name_twitter:player' => 1,
					'add_meta_name_twitter:player:width' => 1,
					'add_meta_name_twitter:player:height' => 1,
					'add_meta_itemprop_name' => 1,
					'add_meta_itemprop_headline' => 1,
					'add_meta_itemprop_datepublished' => 1,
					'add_meta_itemprop_description' => 1,
					'add_meta_itemprop_url' => 1,
					'add_meta_itemprop_image' => 1,
					/*
					 * Advanced Settings
					 */
					// Plugin Settings Tab
					'plugin_debug' => 0,				// Add Hidden Debug Messages
					'plugin_preserve' => 0,				// Preserve Settings on Uninstall
					'plugin_show_opts' => 'basic',			// Options to Show by Default
					'plugin_cache_info' => 0,			// Report Cache Purge Count
					'plugin_filter_lang' => 1,			// Use WP Locale for Language
					'plugin_auto_img_resize' => 1,			// Auto-Resize Media Images
					'plugin_ignore_small_img' => 1,			// Check Image Dimensions
					'plugin_page_excerpt' => 0,			// Enable WP Excerpt for Pages
					'plugin_page_tags' => 0,			// Enable WP Tags for Pages
					'plugin_check_head' => 1,
					'plugin_filter_title' => 1,
					'plugin_filter_content' => 0,
					'plugin_filter_excerpt' => 0,
					'plugin_p_strip' => 0,
					'plugin_p_cap_prefix' => 'Caption:',
					'plugin_use_img_alt' => 1,
					'plugin_img_alt_prefix' => 'Image:',
					'plugin_shortcodes' => 1,
					'plugin_widgets' => 1,
					'plugin_gravatar_api' => 1,
					'plugin_slideshare_api' => 1,
					'plugin_vimeo_api' => 1,
					'plugin_wistia_api' => 1,
					'plugin_youtube_api' => 1,
					'plugin_cf_img_url' => '_format_image_url',
					'plugin_cf_vid_url' => '_format_video_url',
					'plugin_cf_vid_embed' => '_format_video_embed',
					'plugin_add_to_post' => 1,
					'plugin_add_to_page' => 1,
					'plugin_add_to_taxonomy' => 1,
					'plugin_add_to_user' => 1,
					'plugin_add_to_attachment' => 1,
					'plugin_html_attr_filter_name' => 'language_attributes',
					'plugin_html_attr_filter_prio' => 100,
					'plugin_head_attr_filter_name' => 'language_attributes',
					'plugin_head_attr_filter_prio' => 100,
					// File and Object Cache Tab
					'plugin_object_cache_exp' => 86400,		// Object Cache Expiry
					'plugin_file_cache_exp' => 0,
					'plugin_verify_certs' => 0,			// Verify SSL Certificates
					'plugin_shortener' => 'none',
					'plugin_min_shorten' => 22,
					'plugin_bitly_login' => '',
					'plugin_bitly_api_key' => '',
					'plugin_google_api_key' => '',
					'plugin_google_shorten' => 0,
					// Contact Field Names and Labels
					'plugin_cm_fb_name' => 'facebook', 
					'plugin_cm_fb_label' => 'Facebook URL', 
					'plugin_cm_fb_enabled' => 1,
					'plugin_cm_gp_name' => 'gplus', 
					'plugin_cm_gp_label' => 'Google+ URL', 
					'plugin_cm_gp_enabled' => 1,
					'plugin_cm_instgram_name' => 'instagram', 
					'plugin_cm_instgram_label' => 'Instagram URL', 
					'plugin_cm_instgram_enabled' => 1,
					'plugin_cm_linkedin_name' => 'linkedin', 
					'plugin_cm_linkedin_label' => 'LinkedIn URL', 
					'plugin_cm_linkedin_enabled' => 1,
					'plugin_cm_myspace_name' => 'myspace', 
					'plugin_cm_myspace_label' => 'MySpace URL', 
					'plugin_cm_myspace_enabled' => 1,
					'plugin_cm_pin_name' => 'pinterest', 
					'plugin_cm_pin_label' => 'Pinterest URL', 
					'plugin_cm_pin_enabled' => 1,
					'plugin_cm_tumblr_name' => 'tumblr', 
					'plugin_cm_tumblr_label' => 'Tumblr URL', 
					'plugin_cm_tumblr_enabled' => 1,
					'plugin_cm_twitter_name' => 'twitter', 
					'plugin_cm_twitter_label' => 'Twitter @username', 
					'plugin_cm_twitter_enabled' => 1,
					'plugin_cm_yt_name' => 'youtube', 
					'plugin_cm_yt_label' => 'YouTube Channel URL', 
					'plugin_cm_yt_enabled' => 1,
					'plugin_cm_skype_name' => 'skype', 
					'plugin_cm_skype_label' => 'Skype Username', 
					'plugin_cm_skype_enabled' => 1,
					'wp_cm_aim_name' => 'aim', 
					'wp_cm_aim_label' => 'AIM', 
					'wp_cm_aim_enabled' => 1,
					'wp_cm_jabber_name' => 'jabber', 
					'wp_cm_jabber_label' => 'Google Talk', 
					'wp_cm_jabber_enabled' => 1,
					'wp_cm_yim_name' => 'yim',
					'wp_cm_yim_label' => 'Yahoo IM', 
					'wp_cm_yim_enabled' => 1,
					// Extension Plugins and Pro Licenses
					'plugin_wpsso_tid' => '',
				),
				'site_defaults' => array(
					'options_filtered' => false,
					/*
					 * Advanced Settings
					 */
					// Plugin Settings Tab
					'plugin_debug' => 0,				// Add Hidden Debug Messages
					'plugin_debug:use' => 'default',
					'plugin_preserve' => 0,				// Preserve Settings on Uninstall
					'plugin_preserve:use' => 'default',
					'plugin_show_opts' => 'basic',			// Options to Show by Default
					'plugin_show_opts:use' => 'default',
					'plugin_cache_info' => 0,			// Report Cache Purge Count
					'plugin_cache_info:use' => 'default',
					'plugin_filter_lang' => 1,			// Use WP Locale for Language
					'plugin_filter_lang:use' => 'default',
					'plugin_auto_img_resize' => 1,			// Auto-Resize Media Images
					'plugin_auto_img_resize:use' => 'default',
					'plugin_ignore_small_img' => 1,			// Check Image Dimensions
					'plugin_ignore_small_img:use' => 'default',
					'plugin_page_excerpt' => 0,			// Enable WP Excerpt for Pages
					'plugin_page_excerpt:use' => 'default',
					'plugin_page_tags' => 0,			// Enable WP Tags for Pages
					'plugin_page_tags:use' => 'default',
					// File and Object Cache Tab
					'plugin_object_cache_exp' => 86400,		// Object Cache Expiry
					'plugin_object_cache_exp:use' => 'default',
					'plugin_file_cache_exp' => 0,
					'plugin_file_cache_exp:use' => 'default',
					'plugin_verify_certs' => 0,			// Verify SSL Certificates
					'plugin_verify_certs:use' => 'default',
					// Extension Plugins and Pro Licenses
					'plugin_wpsso_tid' => '',
					'plugin_wpsso_tid:use' => 'default',
				),
				'pre' => array(
					'email' => 'email', 
					'facebook' => 'fb', 
					'gplus' => 'gp',
					'twitter' => 'twitter',
					'instagram' => 'instgram',
					'linkedin' => 'linkedin',
					'myspace' => 'myspace',
					'pinterest' => 'pin',
					'pocket' => 'pocket',
					'buffer' => 'buffer',
					'reddit' => 'reddit',
					'managewp' => 'managewp',
					'stumbleupon' => 'stumble',
					'tumblr' => 'tumblr',
					'youtube' => 'yt',
					'skype' => 'skype',
					'vk' => 'vk',
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
				'min_version' => '4.1.0',	// minimum php version
			),
			'follow' => array(
				'size' => 24,
				'src' => array(
					'images/follow/Wordpress.png' => 'https://profiles.wordpress.org/jsmoriss/',
					'images/follow/Github.png' => 'https://github.com/SurniaUlula',
					'images/follow/Facebook.png' => 'https://www.facebook.com/SurniaUlulaCom',
					'images/follow/GooglePlus.png' => 'https://plus.google.com/+SurniaUlula/',
					'images/follow/Linkedin.png' => 'https://www.linkedin.com/company/surnia-ulula-ltd',
					'images/follow/Twitter.png' => 'https://twitter.com/surniaululacom',
					'images/follow/Youtube.png' => 'https://www.youtube.com/user/SurniaUlulaCom',
					'images/follow/Rss.png' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso/feed/',
				),
			),
			'form' => array(
				'og_image_col_width' => '70px',
				'og_image_col_height' => '37px',
				'tooltip_class' => 'sucom_tooltip',
				'max_hashtags' => 10,
				'max_media_items' => 20,
				'yes_no' => array(
					'1' => 'Yes',
					'0' => 'No',
				),
				'time_by_name' => array(
					'hour' => 3600,
					'day' => 86400,
					'week' => 604800,
					'month' => 18144000,
				),
				'file_cache_hrs' => array(
					0 => 0,
					3600 => 1,
					7200 => 3,
					21600 => 6,
					32400 => 9,
					43200 => 12,
					86400 => 24,
					129600 => 36,
					172800 => 48,
					259200 => 72,
					604800 => 168,
				),
				'script_locations' => array(
					'none' => '[none]',
					'header' => 'Header',
					'footer' => 'Footer',
				),
				'caption_types' => array(
					'none' => '[none]',
					'title' => 'Title Only',
					'excerpt' => 'Excerpt Only',
					'both' => 'Title and Excerpt',
				),
				'user_name_fields' => array(
					'none' => '[none]',
					'fullname' => 'First and Last Names',
					'display_name' => 'Display Name',
					'nickname' => 'Nickname',
				),
				'show_options' => array(
					'basic' => 'Basic Options',
					'all' => 'All Options',
				),
				'site_option_use' => array(
					'default' => 'Default value',
					'empty' => 'If value is empty',
					'force' => 'Force this value',
				),
				'position_crop_x' => array(
					'left' => 'Left',
					'center' => 'Center',
					'right' => 'Right',
				),
				'position_crop_y' => array(
					'top' => 'Top',
					'center' => 'Center',
					'bottom' => 'Bottom',
				),
				'shorteners' => array(
					'none' => '[none]',
					'bitly' => 'Bit.ly',
					'googl' => 'Goo.gl',
				),
			),
			'head' => array(
				'max_img_ratio' => 3,
				'min_img_dim' => 200,
				'min_desc_len' => 156,
				'og_type_ns' => array(		// from http://ogp.me/#types
					'article' => 'http://ogp.me/ns/article#',
					'book' => 'http://ogp.me/ns/book#',
					'music.album' => 'http://ogp.me/ns/music#',
					'music.playlist' => 'http://ogp.me/ns/music#',
					'music.radio_station' => 'http://ogp.me/ns/music#',
					'music.song' => 'http://ogp.me/ns/music#',
					'place' => 'http://ogp.me/ns/place#',		// for Facebook and Pinterest
					'product' => 'http://ogp.me/ns/product#',	// for Facebook and Pinterest
					'profile' => 'http://ogp.me/ns/profile#',
					'video.episode' => 'http://ogp.me/ns/video#',
					'video.movie' => 'http://ogp.me/ns/video#',
					'video.other' => 'http://ogp.me/ns/video#',
					'video.tv_show' => 'http://ogp.me/ns/video#',
					'website' => 'http://ogp.me/ns/website#',
				),
				'schema_type' => array(
					'article' => 'http://schema.org/Article',
					'book' => 'http://schema.org/Book',
					'blog' => 'http://schema.org/Blog',
					'event' => 'http://schema.org/Event',
					'organization' => 'http://schema.org/Organization',
					'person' => 'http://schema.org/Person',
					'place' => 'http://schema.org/Place',
					'product' => 'http://schema.org/Product',
					'recipe' => 'http://schema.org/Recipe',
					'review' => 'http://schema.org/Review',
					'other' => 'http://schema.org/Other',
					'local.business' => 'http://schema.org/LocalBusiness',
					'webpage' => 'http://schema.org/WebPage',
					'website' => 'http://schema.org/WebSite',
				),
			),
			'cache' => array(
				'file' => false,
				'object' => true,
				'transient' => true,
			),
		);

		// get_config is called very early, so don't apply filters unless instructed
		public static function get_config( $idx = false, $filter = false ) { 

			if ( ! isset( self::$cf['config_filtered'] ) || 
				self::$cf['config_filtered'] !== true ) {

				if ( $filter === true ) {
					self::$cf['opt']['version'] .= is_dir( trailingslashit( dirname( __FILE__ ) ).'pro/' ) ? 'pro' : 'gpl';
					self::$cf = apply_filters( self::$cf['lca'].'_get_config', self::$cf );
					self::$cf['config_filtered'] = true;
					self::$cf['*'] = array(
						'base' => array(),
						'lib' => array(),
						'version' => '',
					);
					foreach ( self::$cf['plugin'] as $lca => $info ) {
						if ( isset( $info['base'] ) )
							self::$cf['*']['base'][$info['base']] = $lca;
						if ( isset( $info['lib'] ) && is_array( $info['lib'] ) )
							self::$cf['*']['lib'] = SucomUtil::array_merge_recursive_distinct( 
								self::$cf['*']['lib'], 
								$info['lib']
							);
						if ( isset( $info['version'] ) )
							self::$cf['*']['version'] .= '-'.$lca.$info['version'];
					}
					self::$cf['*']['version'] = trim( self::$cf['*']['version'], '-' );
				}

				// complete relative paths in the image array
				foreach ( self::$cf['plugin'] as $lca => $info ) {
					foreach ( $info['img'] as $id => $url )
						if ( ! empty( $url ) && strpos( $url, '//' ) === false )
							self::$cf['plugin'][$lca]['img'][$id] = trailingslashit( plugins_url( '', $info['base'] ) ).$url;
				}
			}

			if ( $idx !== false ) {
				if ( isset( self::$cf[$idx] ) )
					return self::$cf[$idx];
				else return false;
			} else return self::$cf;
		}

		public static function set_constants( $plugin_filepath ) { 
			define( 'WPSSO_FILEPATH', $plugin_filepath );						
			define( 'WPSSO_PLUGINDIR', trailingslashit( realpath( dirname( $plugin_filepath ) ) ) );
			define( 'WPSSO_PLUGINBASE', self::$cf['plugin']['wpsso']['base'] );		// wpsso/wpsso.php
			define( 'WPSSO_URLPATH', trailingslashit( plugins_url( '', $plugin_filepath ) ) );
			define( 'WPSSO_NONCE', md5( WPSSO_PLUGINDIR.'-'.self::$cf['plugin']['wpsso']['version'].
				( defined( 'NONCE_SALT' ) ? NONCE_SALT : '' ) ) );
			self::set_variable_constants();
		}

		public static function set_variable_constants() { 
			foreach ( self::get_variable_constants() as $name => $value )
				if ( ! defined( $name ) )
					define( $name, $value );
		}

		public static function get_variable_constants() { 
			$var_const = array();

			if ( defined( 'WPSSO_PLUGINDIR' ) ) {
				$var_const['WPSSO_CACHEDIR'] = WPSSO_PLUGINDIR.'cache/';
				$var_const['WPSSO_TOPICS_LIST'] = WPSSO_PLUGINDIR.'share/topics.txt';
			}

			if ( defined( 'WPSSO_URLPATH' ) )
				$var_const['WPSSO_CACHEURL'] = WPSSO_URLPATH.'cache/';

			$var_const['WPSSO_DEBUG_FILE_EXP'] = 300;
			$var_const['WPSSO_MENU_ORDER'] = '99.10';
			$var_const['WPSSO_MENU_ICON_HIGHLIGHT'] = true;

			/*
			 * WPSSO option and meta array names
			 */
			$var_const['WPSSO_TS_NAME'] = 'wpsso_timestamps';
			$var_const['WPSSO_OPTIONS_NAME'] = 'wpsso_options';
			$var_const['WPSSO_SITE_OPTIONS_NAME'] = 'wpsso_site_options';
			$var_const['WPSSO_NOTICE_NAME'] = 'ngfb_notices';	// stored notices
			$var_const['WPSSO_DISMISS_NAME'] = 'ngfb_dismissed';	// dismissed notices
			$var_const['WPSSO_META_NAME'] = '_wpsso_meta';		// post meta
			$var_const['WPSSO_PREF_NAME'] = '_wpsso_pref';		// user meta

			/*
			 * WPSSO option and meta array alternate / fallback names
			 */
			$var_const['WPSSO_OPTIONS_NAME_ALT'] = 'ngfb_options';
			$var_const['WPSSO_SITE_OPTIONS_NAME_ALT'] = 'ngfb_site_options';
			$var_const['WPSSO_META_NAME_ALT'] = '_ngfb_meta';
			$var_const['WPSSO_PREF_NAME_ALT'] = '_ngfb_pref';

			/*
			 * WPSSO hook priorities
			 */
			$var_const['WPSSO_ADD_MENU_PRIORITY'] = -20;
			$var_const['WPSSO_ADD_SETTINGS_PRIORITY'] = -10;
			$var_const['WPSSO_META_SAVE_PRIORITY'] = 6;
			$var_const['WPSSO_META_CACHE_PRIORITY'] = 9;
			$var_const['WPSSO_INIT_PRIORITY'] = 12;
			$var_const['WPSSO_HEAD_PRIORITY'] = 10;
			$var_const['WPSSO_SEO_FILTERS_PRIORITY'] = 100;

			/*
			 * WPSSO curl settings
			 */
			if ( defined( 'WPSSO_PLUGINDIR' ) )
				$var_const['WPSSO_CURL_CAINFO'] = WPSSO_PLUGINDIR.'share/curl/cacert.pem';
			$var_const['WPSSO_CURL_USERAGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:40.0) Gecko/20100101 Firefox/40.0';

			// disable 3rd-party caching for duplicate meta tag checks
			if ( ! empty( $_GET['WPSSO_META_TAGS_DISABLE'] ) ) {
				$var_const['DONOTCACHEPAGE'] = true;		// wp super cache
				$var_const['QUICK_CACHE_ALLOWED'] = false;	// quick cache
				$var_const['ZENCACHE_ALLOWED'] = false;		// zencache
			}

			foreach ( $var_const as $name => $value )
				if ( defined( $name ) )
					$var_const[$name] = constant( $name );	// inherit existing values

			return $var_const;
		}

		public static function require_libs( $plugin_filepath ) {
			
			require_once( WPSSO_PLUGINDIR.'lib/com/util.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/cache.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/notice.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/script.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/style.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/webpage.php' );

			require_once( WPSSO_PLUGINDIR.'lib/register.php' );
			require_once( WPSSO_PLUGINDIR.'lib/check.php' );
			require_once( WPSSO_PLUGINDIR.'lib/util.php' );
			require_once( WPSSO_PLUGINDIR.'lib/options.php' );
			require_once( WPSSO_PLUGINDIR.'lib/meta.php' );
			require_once( WPSSO_PLUGINDIR.'lib/post.php' );		// extends meta.php
			require_once( WPSSO_PLUGINDIR.'lib/taxonomy.php' );	// extends meta.php
			require_once( WPSSO_PLUGINDIR.'lib/user.php' );		// extends meta.php
			require_once( WPSSO_PLUGINDIR.'lib/media.php' );
			require_once( WPSSO_PLUGINDIR.'lib/head.php' );
			require_once( WPSSO_PLUGINDIR.'lib/opengraph.php' );
			require_once( WPSSO_PLUGINDIR.'lib/twittercard.php' );
			require_once( WPSSO_PLUGINDIR.'lib/schema.php' );
			require_once( WPSSO_PLUGINDIR.'lib/functions.php' );

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
