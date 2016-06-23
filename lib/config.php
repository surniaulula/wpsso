<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoConfig' ) ) {

	class WpssoConfig {

		public static $cf = array(
			'lca' => 'wpsso',		// lowercase acronym
			'uca' => 'WPSSO',		// uppercase acronym
			'menu' => 'SSO',		// menu item label
			'color' => '33cc33',		// menu item color - lime green
			'feed_cache_exp' => 86400,	// 24 hours
			'plugin' => array(
				'wpsso' => array(
					'version' => '3.33.1-1',	// plugin version
					'opt_version' => '437',		// increment when changing default options
					'short' => 'WPSSO',		// short plugin name
					'name' => 'WordPress Social Sharing Optimization (WPSSO)',
					'desc' => 'Fast, light-weight, full-featured plugin for great looking shares on all social sites - no matter how your content is shared or re-shared!',
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
						'profile' => array (	// lib file descriptions will be translated
							'social-settings' => 'Your Social Settings',
						),
						'setting' => array (	// lib file descriptions will be translated
							'image-dimensions' => 'Social and SEO Image Dimensions',
							'social-accounts' => 'Website Social Pages and Accounts',
							'contact-fields' => 'User Profile Contact Methods',
						),
						'submenu' => array (	// lib file descriptions will be translated
							'essential' => 'Essential Settings',
							'general' => 'General Settings',
							'advanced' => 'Advanced Settings',
							'setup' => '<color>Plugin Setup Guide and Notes</color>',
							'licenses' => 'Pro Licenses and Extension Plugins',
						),
						'sitesubmenu' => array(	// lib file descriptions will be translated
							'siteadvanced' => 'Advanced Settings',
							'sitesetup' => '<color>Plugin Setup Guide and Notes</color>',
							'sitelicenses' => 'Pro Licenses and Extension Plugins',
						),
						'gpl' => array(
							'admin' => array(
								'general' => 'General Settings',
								'advanced' => 'Advanced Settings',
								'post' => 'Post Settings',
								'meta' => 'Term and User Settings',
							),
							'util' => array(
								'post' => 'Custom Post Meta',
								'term' => 'Custom Term Meta',
								'user' => 'Custom User Meta',
							),
						),
						'pro' => array(
							'admin' => array(
								'general' => 'General Settings',
								'advanced' => 'Advanced Settings',
								'post' => 'Post Settings',
								'meta' => 'Term and User Settings',
							),
							'ecom' => array(
								'edd' => '(plugin) Easy Digital Downloads',
								'marketpress' => '(plugin) MarketPress',
								'woocommerce' => '(plugin) WooCommerce',
								'wpecommerce' => '(plugin) WP eCommerce',
								'yotpowc' => '(plugin) Yotpo Social Reviews for WooCommerce',
							),
							'event' => array(
								'tribe_events' => '(plugin) The Events Calendar',
							),
							'forum' => array(
								'bbpress' => '(plugin) bbPress',
							),
							'lang' => array(
								'polylang' => '(plugin) Polylang',
							),
							'media' => array(
								'gravatar' => '(api) Author Gravatar Image',
								'ngg' => '(plugin) NextGEN Gallery',
								'rtmedia' => '(plugin) rtMedia for WordPress, BuddyPress and bbPress',
								'slideshare' => '(api) Slideshare API',
								'upscale' => '(tool) WP Media Library Image Upscaling',
								'vimeo' => '(api) Vimeo Video API',
								'wistia' => '(api) Wistia Video API',
								'youtube' => '(api) YouTube Video / Playlist API',
							),
							'seo' => array(
								'aioseop' => '(plugin) All in One SEO Pack',
								'autodescription' => '(plugin) The SEO Framework',
								'headspace2' => '(plugin) HeadSpace2 SEO',
								'wpseo' => '(plugin) Yoast SEO',
							),
							'social' => array(
								'buddypress' => '(plugin) BuddyPress',
							),
							'util' => array(
								'checkimgdims' => '(tool) Verify Image Dimensions',
								'coauthors' => '(plugin) Co-Authors Plus',
								'language' => '(tool) WP Locale to Publisher Language Mapping',
								'shorten' => '(api) URL Shortening Service APIs',
								'post' => '(tool) Custom Post Meta',
								'restapi' => '(plugin) WordPress REST API (Version 2)',
								'term' => '(tool) Custom Term Meta',
								'user' => '(tool) Custom User Meta',
							),
						),
					),
				),
				'wpssoam' => array(
					'short' => 'WPSSO AM',		// short plugin name
					'name' => 'WPSSO Mobile App Meta (WPSSO AM)',
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
				'wpssojson' => array(
					'short' => 'WPSSO JSON',		// short plugin name
					'name' => 'WPSSO Schema JSON-LD Markup (WPSSO JSON)',
					'desc' => 'WPSSO extension to add complete Schema JSON-LD markup (BlogPosting, Article, Place, Product, etc.) for Google and Pinterest.',
					'slug' => 'wpsso-schema-json-ld',
					'base' => 'wpsso-schema-json-ld/wpsso-schema-json-ld.php',
					'update_auth' => 'tid',
					'img' => array(
						'icon_small' => 'https://surniaulula.github.io/wpsso-schema-json-ld/assets/icon-128x128.png',
						'icon_medium' => 'https://surniaulula.github.io/wpsso-schema-json-ld/assets/icon-256x256.png',
					),
					'url' => array(
						// wordpress
						'download' => 'https://wordpress.org/plugins/wpsso-schema-json-ld/',
						'review' => 'https://wordpress.org/support/view/plugin-reviews/wpsso-schema-json-ld?filter=5&rate=5#postform',
						'readme' => 'https://plugins.svn.wordpress.org/wpsso-schema-json-ld/trunk/readme.txt',
						'wp_support' => 'https://wordpress.org/support/plugin/wpsso-schema-json-ld',
						// surniaulula
						'update' => 'http://wpsso.com/extend/plugins/wpsso-schema-json-ld/update/',
						'purchase' => 'http://wpsso.com/extend/plugins/wpsso-schema-json-ld/',
						'changelog' => 'http://wpsso.com/extend/plugins/wpsso-schema-json-ld/changelog/',
						'codex' => 'http://wpsso.com/codex/plugins/wpsso-schema-json-ld/',
						'faq' => 'http://wpsso.com/codex/plugins/wpsso-schema-json-ld/faq/',
						'notes' => '',
						'feed' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso-schema-json-ld/feed/',
						'pro_support' => 'http://wpsso-schema-json-ld.support.wpsso.com/',
					),
				),
				'wpssoorg' => array(
					'short' => 'WPSSO ORG',		// short plugin name
					'name' => 'WPSSO Organization Markup (WPSSO ORG)',
					'desc' => 'WPSSO extension to manage multiple Organizations / Publishers and additional properties for the Schema Article types (BlogPosting, etc.).',
					'slug' => 'wpsso-organization',
					'base' => 'wpsso-organization/wpsso-organization.php',
					'update_auth' => 'tid',
					'img' => array(
						'icon_small' => 'https://surniaulula.github.io/wpsso-organization/assets/icon-128x128.png',
						'icon_medium' => 'https://surniaulula.github.io/wpsso-organization/assets/icon-256x256.png',
					),
					'url' => array(
						// wordpress
						'download' => 'https://wordpress.org/plugins/wpsso-organization/',
						'review' => 'https://wordpress.org/support/view/plugin-reviews/wpsso-organization?filter=5&rate=5#postform',
						'readme' => 'https://plugins.svn.wordpress.org/wpsso-organization/trunk/readme.txt',
						'wp_support' => 'https://wordpress.org/support/plugin/wpsso-organization',
						// surniaulula
						'update' => 'http://wpsso.com/extend/plugins/wpsso-organization/update/',
						'purchase' => 'http://wpsso.com/extend/plugins/wpsso-organization/',
						'changelog' => 'http://wpsso.com/extend/plugins/wpsso-organization/changelog/',
						'codex' => 'http://wpsso.com/codex/plugins/wpsso-organization/',
						'faq' => 'http://wpsso.com/codex/plugins/wpsso-organization/faq/',
						'notes' => '',
						'feed' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso-organization/feed/',
						'pro_support' => 'http://wpsso-organization.support.wpsso.com/',
					),
				),
				'wpssoplm' => array(
					'short' => 'WPSSO PLM',		// short plugin name
					'name' => 'WPSSO Place / Location and Local Business Meta (WPSSO PLM)',
					'desc' => 'WPSSO extension to provide Pinterest Place, Facebook / Open Graph Location, Schema Local Business + Local SEO meta tags.',
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
					'desc' => 'WPSSO extension to add traditional Social Sharing Buttons with support for hashtags, short URLs, bbPress, BuddyPress, WooCommerce, and much more.',
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
					'desc' => 'WPSSO extension to provide updates for the WordPress Social Sharing Optimization (WPSSO) Pro plugin and its Pro extensions.',
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
				'defaults' => array(
					'options_filtered' => false,
					'schema_add_noscript' => 1,
					'schema_website_json' => 1,
					'schema_organization_json' => 1,
					'schema_person_json' => 0,
					'schema_person_id' => '',
					'schema_alt_name' => '',
					'schema_logo_url' => '',
					'schema_banner_url' => '',
					'schema_desc_len' => 250,		// meta itemprop="description" maximum text length
					'schema_type_for_home_page' => 'website',
					'schema_type_for_post' => 'blog.posting',
					'schema_type_for_page' => 'webpage',
					'schema_type_for_attachment' => 'webpage',
					'schema_type_for_article' => 'article',
					'schema_type_for_book' => 'book',
					'schema_type_for_blog' => 'blog',
					'schema_type_for_business' => 'local.business',
					'schema_type_for_download' => 'product',
					'schema_type_for_event' => 'event',
					'schema_type_for_organization' => 'organization',
					'schema_type_for_other' => 'other',
					'schema_type_for_person' => 'person',
					'schema_type_for_place' => 'place',
					'schema_type_for_product' => 'product',
					'schema_type_for_recipe' => 'recipe',
					'schema_type_for_review' => 'review',
					'schema_type_for_tribe_events' => 'event',
					'schema_type_for_webpage' => 'webpage',
					'schema_type_for_website' => 'website',
					'schema_author_name' => 'display_name',
					'schema_img_max' => 1,
					'schema_img_width' => 800,		// must be at least 696px
					'schema_img_height' => 1600,
					'schema_img_crop' => 0,
					'schema_img_crop_x' => 'center',
					'schema_img_crop_y' => 'center',
					'seo_desc_len' => 156,			// meta name="description" maximum text length
					'seo_def_author_id' => 0,
					'seo_def_author_on_index' => 0,
					'seo_def_author_on_search' => 0,
					'seo_author_field' => '',		// default value set by WpssoOptions::get_defaults()
					'seo_publisher_url' => '',		// (multilingual)
					'fb_publisher_url' => '',		// (multilingual)
					'fb_app_id' => '',
					'fb_admins' => '',
					'fb_author_name' => 'display_name',
					'fb_lang' => 'en_US',
					'instgram_publisher_url' => '',		// (multilingual)
					'linkedin_publisher_url' => '',		// (multilingual)
					'myspace_publisher_url' => '',		// (multilingual)
					'og_site_name' => '',			// (multilingual)
					'og_site_description' => '',		// (multilingual)
					'og_art_section' => 'none',
					'og_img_width' => 600,
					'og_img_height' => 315,
					'og_img_crop' => 1,
					'og_img_crop_x' => 'center',
					'og_img_crop_y' => 'center',
					'og_img_max' => 1,
					'og_vid_max' => 1,
					'og_vid_https' => 1,
					'og_vid_autoplay' => 1,
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
					'rp_publisher_url' => '',		// (multilingual)
					'rp_author_name' => 'display_name',	// rich-pin specific article:author
					'rp_img_width' => 800,
					'rp_img_height' => 1600,
					'rp_img_crop' => 0,
					'rp_img_crop_x' => 'center',
					'rp_img_crop_y' => 'center',
					'rp_dom_verify' => '',
					'tc_site' => '',			// Twitter Business @username (multilingual)
					'tc_desc_len' => 200,			// Maximum Description Length
					// summary card
					'tc_sum_width' => 600,
					'tc_sum_height' => 600,
					'tc_sum_crop' => 1,
					'tc_sum_crop_x' => 'center',
					'tc_sum_crop_y' => 'center',
					// large image summary card
					'tc_lrgimg_width' => 800,
					'tc_lrgimg_height' => 1600,
					'tc_lrgimg_crop' => 0,
					'tc_lrgimg_crop_x' => 'center',
					'tc_lrgimg_crop_y' => 'center',
					// enable/disable header html tags
					'add_link_rel_author' => 1,
					'add_link_rel_publisher' => 1,
					// facebook
					'add_meta_property_fb:admins' => 1,
					'add_meta_property_fb:app_id' => 1,
					// open graph
					'add_meta_property_og:locale' => 1,
					'add_meta_property_og:site_name' => 1,
					'add_meta_property_og:description' => 1,
					'add_meta_property_og:title' => 1,
					'add_meta_property_og:type' => 1,
					'add_meta_property_og:url' => 1,
					'add_meta_property_og:image:secure_url' => 1,
					'add_meta_property_og:image' => 1,
					'add_meta_property_og:image:width' => 1,
					'add_meta_property_og:image:height' => 1,
					'add_meta_property_og:video:secure_url' => 1,
					'add_meta_property_og:video:url' => 1,
					'add_meta_property_og:video:type' => 1,
					'add_meta_property_og:video:width' => 1,
					'add_meta_property_og:video:height' => 1,
					'add_meta_property_og:video:tag' => 1,
					'add_meta_property_og:altitude' => 1,
					'add_meta_property_og:latitude' => 1,
					'add_meta_property_og:longitude' => 1,
					// article
					'add_meta_property_article:author' => 1,
					'add_meta_property_article:publisher' => 1,
					'add_meta_property_article:published_time' => 1,
					'add_meta_property_article:modified_time' => 1,
					'add_meta_property_article:section' => 1,
					'add_meta_property_article:tag' => 1,
					// book
					'add_meta_property_book:author' => 1,
					'add_meta_property_book:isbn' => 1,
					'add_meta_property_book:release_date' => 1,
					'add_meta_property_book:tag' => 1,
					// music
					'add_meta_property_music:album' => 1,
					'add_meta_property_music:album:disc' => 1,
					'add_meta_property_music:album:track' => 1,
					'add_meta_property_music:creator' => 1,
					'add_meta_property_music:duration' => 1,
					'add_meta_property_music:musician' => 1,
					'add_meta_property_music:release_date' => 1,
					'add_meta_property_music:song' => 1,
					'add_meta_property_music:song:disc' => 1,
					'add_meta_property_music:song:track' => 1,
					// place
					'add_meta_property_place:location:altitude' => 1,
					'add_meta_property_place:location:latitude' => 1,
					'add_meta_property_place:location:longitude' => 1,
					'add_meta_property_place:street_address' => 1,
					'add_meta_property_place:locality' => 1,
					'add_meta_property_place:region' => 1,
					'add_meta_property_place:postal_code' => 1,
					'add_meta_property_place:country_name' => 1,
					// product
					'add_meta_property_product:availability' => 1,
					'add_meta_property_product:price:amount' => 1,
					'add_meta_property_product:price:currency' => 1,
					// profile
					'add_meta_property_profile:first_name' => 1,
					'add_meta_property_profile:last_name' => 1,
					'add_meta_property_profile:username' => 1,
					'add_meta_property_profile:gender' => 1,
					// video
					'add_meta_property_video:actor' => 1,
					'add_meta_property_video:actor:role' => 1,
					'add_meta_property_video:director' => 1,
					'add_meta_property_video:writer' => 1,
					'add_meta_property_video:duration' => 1,
					'add_meta_property_video:release_date' => 1,
					'add_meta_property_video:tag' => 1,
					'add_meta_property_video:series' => 1,
					// seo
					'add_meta_name_author' => 1,
					'add_meta_name_canonical' => 0,
					'add_meta_name_description' => 1,
					'add_meta_name_generator' => 1,
					// pinterest
					'add_meta_name_p:domain_verify' => 1,
					// twitter cards
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
					// schema
					'add_meta_itemprop_name' => 1,
					'add_meta_itemprop_datepublished' => 1,
					'add_meta_itemprop_datemodified' => 1,
					'add_meta_itemprop_description' => 1,
					'add_meta_itemprop_url' => 1,
					'add_meta_itemprop_image' => 1,
					'add_meta_itemprop_image.url' => 1,
					'add_meta_itemprop_image.width' => 1,
					'add_meta_itemprop_image.height' => 1,
					'add_meta_itemprop_publisher.name' => 1,
					'add_meta_itemprop_author.url' => 1,
					'add_meta_itemprop_author.name' => 1,
					'add_meta_itemprop_author.description' => 1,
					'add_meta_itemprop_author.image' => 1,
					'add_meta_itemprop_contributor.url' => 1,
					'add_meta_itemprop_contributor.name' => 1,
					'add_meta_itemprop_contributor.description' => 1,
					'add_meta_itemprop_contributor.image' => 1,
					'add_meta_itemprop_address' => 1,
					'add_meta_itemprop_openinghoursspecification.dayofweek' => 1,
					'add_meta_itemprop_openinghoursspecification.opens' => 1,
					'add_meta_itemprop_openinghoursspecification.closes' => 1,
					'add_meta_itemprop_openinghoursspecification.validfrom' => 1,
					'add_meta_itemprop_openinghoursspecification.validthrough' => 1,
					'add_meta_itemprop_menu' => 1,
					'add_meta_itemprop_acceptsreservations' => 1,
					'add_meta_itemprop_ratingvalue' => 1,
					'add_meta_itemprop_ratingcount' => 1,
					'add_meta_itemprop_worstrating' => 1,
					'add_meta_itemprop_bestrating' => 1,
					'add_meta_itemprop_reviewcount' => 1,
					'add_meta_itemprop_startdate' => 1,	// Schema Event
					'add_meta_itemprop_enddate' => 1,	// Schema Event
					'add_meta_itemprop_location' => 1,	// Schema Event
					/*
					 * Advanced Settings
					 */
					// Plugin Settings Tab
					'plugin_preserve' => 0,				// Preserve Settings on Uninstall
					'plugin_debug' => 0,				// Add Hidden Debug Messages
					'plugin_clear_on_save' => 1,			// Clear All Cache(s) on Save Settings
					'plugin_show_opts' => 'basic',			// Options to Show by Default
					// Content and Filters Tab
					'plugin_filter_title' => 0,
					'plugin_filter_content' => 0,
					'plugin_filter_excerpt' => 0,
					'plugin_p_strip' => 0,
					'plugin_use_img_alt' => 1,
					'plugin_img_alt_prefix' => 'Image:',
					'plugin_p_cap_prefix' => 'Caption:',
					'plugin_gravatar_api' => 1,
					'plugin_slideshare_api' => 1,
					'plugin_vimeo_api' => 1,
					'plugin_wistia_api' => 1,
					'plugin_youtube_api' => 1,
					// Social Settings Tab
					'plugin_og_img_col_post' => 1,
					'plugin_og_img_col_term' => 1,
					'plugin_og_img_col_user' => 1,
					'plugin_og_desc_col_post' => 0,
					'plugin_og_desc_col_term' => 0,
					'plugin_og_desc_col_user' => 0,
					'plugin_add_to_attachment' => 1,
					'plugin_add_to_page' => 1,
					'plugin_add_to_post' => 1,
					'plugin_add_to_product' => 1,
					'plugin_add_to_reply' => 0,	// bbpress
					'plugin_add_to_term' => 1,
					'plugin_add_to_topic' => 0,	// bbpress
					'plugin_add_to_user' => 1,
					'plugin_cf_img_url' => '_format_image_url',
					'plugin_cf_vid_url' => '_format_video_url',
					'plugin_cf_vid_embed' => '_format_video_embed',
					// Theme Integration Tab
					'plugin_html_attr_filter_name' => 'language_attributes',
					'plugin_html_attr_filter_prio' => 100,
					'plugin_head_attr_filter_name' => 'head_attributes',
					'plugin_head_attr_filter_prio' => 100,
					'plugin_check_head' => 1,			// Check for Duplicate Meta Tags
					'plugin_filter_lang' => 1,			// Use WP Locale for Language
					'plugin_auto_img_resize' => 1,			// Create Missing WP Media Images
					'plugin_check_img_dims' => 0,			// Enforce Image Dimensions Check
					'plugin_upscale_images' => 0,			// Allow Upscaling of Smaller Images
					'plugin_upscale_img_max' => 50,			// Maximum Image Upscale Percentage
					'plugin_shortcodes' => 1,			// Enable Plugin Shortcode(s)
					'plugin_widgets' => 1,				// Enable Plugin Widget(s)
					'plugin_page_excerpt' => 0,			// Enable WP Excerpt for Pages
					'plugin_page_tags' => 0,			// Enable WP Tags for Pages
					// File and Object Cache Tab
					'plugin_object_cache_exp' => 259200,		// Object Cache Expiry (259200 secs = 3 days)
					'plugin_verify_certs' => 0,			// Verify SSL Certificates
					'plugin_cache_info' => 0,			// Report Cache Purge Count
					'plugin_file_cache_exp' => 0,			// File Cache Expiry
					// Service API Keys Tab
					'plugin_shortener' => 'none',
					'plugin_shortlink' => 1,
					'plugin_min_shorten' => 23,
					'plugin_bitly_login' => '',
					'plugin_bitly_api_key' => '',
					'plugin_google_api_key' => '',
					'plugin_google_shorten' => 0,
					'plugin_owly_api_key' => '',
					'plugin_yourls_api_url' => '',
					'plugin_yourls_username' => '',
					'plugin_yourls_password' => '',
					'plugin_yourls_token' => '',
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
					// Pro Licenses and Extension Plugins
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
					'plugin_check_img_dims' => 0,			// Enforce Image Dimensions Check
					'plugin_check_img_dims:use' => 'default',
					'plugin_shortcodes' => 1,			// Enable Plugin Shortcode(s)
					'plugin_shortcodes:use' => 'default',
					'plugin_widgets' => 1,				// Enable Plugin Widget(s)
					'plugin_widgets:use' => 'default',
					'plugin_page_excerpt' => 0,			// Enable WP Excerpt for Pages
					'plugin_page_excerpt:use' => 'default',
					'plugin_page_tags' => 0,			// Enable WP Tags for Pages
					'plugin_page_tags:use' => 'default',
					// File and Object Cache Tab
					'plugin_object_cache_exp' => 259200,		// Object Cache Expiry (259200 secs = 3 days)
					'plugin_object_cache_exp:use' => 'default',
					'plugin_file_cache_exp' => 0,			// File Cache Expiry
					'plugin_file_cache_exp:use' => 'default',
					'plugin_verify_certs' => 0,			// Verify SSL Certificates
					'plugin_verify_certs:use' => 'default',
					// Pro Licenses and Extension Plugins
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
					'whatsapp' => 'wa',
				),
			),
			'wp' => array(				// wordpress
				'min_version' => '3.0',		// minimum wordpress version
				'cm' => array(
					'aim' => 'AIM',
					'jabber' => 'Google Talk',
					'yim' => 'Yahoo IM',
				),
				'admin' => array(
					'users' => array(
						'page' => 'users.php',
						'cap' => 'list_users',
					),
					'profile' => array(
						'page' => 'profile.php',
						'cap' => 'edit_posts',
					),
					'setting' => array(
						'page' => 'options-general.php',
						'cap' => 'manage_options',
					),
					'submenu' => array(
						'page' => 'admin.php',
						'cap' => 'manage_options',
					),
					'sitesubmenu' => array(
						'page' => 'admin.php',
						'cap' => 'manage_options',
					),
				),
			),
			'php' => array(				// php
				'min_version' => '4.1.0',	// minimum php version
			),
			'form' => array(
				'og_img_col_width' => '70px',
				'og_img_col_height' => '37px',
				'tooltip_class' => 'sucom_tooltip',
				'max_hashtags' => 10,
				'max_media_items' => 20,
				'yes_no' => array(
					'1' => 'Yes',
					'0' => 'No',
				),
				'weekdays' => array(
					'sunday' => 'Sunday',
					'monday' => 'Monday',
					'tuesday' => 'Tuesday',
					'wednesday' => 'Wednesday',
					'thursday' => 'Thursday',
					'friday' => 'Friday',
					'saturday' => 'Saturday',
					'publicholidays' => 'Public Holidays',
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
				'qualifiers' => array(
					'default' => '(default)',
					'no_images' => '(no images)',
					'no_videos' => '(no videos)',
					'settings' => '(settings value)',
					'disabled' => '(option disabled)',
				),
				'script_locations' => array(
					'none' => '[None]',
					'header' => 'Header',
					'footer' => 'Footer',
				),
				'caption_types' => array(
					'none' => '[None]',
					'title' => 'Title Only',
					'excerpt' => 'Excerpt Only',
					'both' => 'Title and Excerpt',
				),
				'user_name_fields' => array(
					'none' => '[None]',
					'fullname' => 'First and Last Names',
					'display_name' => 'Display Name',
					'nickname' => 'Nickname',
				),
				'show_options' => array(
					'basic' => 'Basic Options',
					'all' => 'All Options',
				),
				'site_option_use' => array(
					'default' => 'New activation',
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
				// shortener key is also its filename under lib/pro/ext/
				'shorteners' => array(
					'none' => '[None]',
					'bitly' => 'Bitly (suggested)',
					'googl' => 'Google',
					'owly' => 'Ow.ly',
					'tinyurl' => 'TinyURL',
					'yourls' => 'YOURLS',
				),
				// social account keys and labels for organization sameas
				'social_accounts' => array(
					'fb_publisher_url' => 'Facebook Business Page URL',
					'instgram_publisher_url' => 'Instagram Business URL',
					'linkedin_publisher_url' => 'LinkedIn Company Page URL',
					'myspace_publisher_url' => 'MySpace Business Page URL',
					'rp_publisher_url' => 'Pinterest Company Page URL',
					'seo_publisher_url' => 'Google+ Business Page URL',
					'tc_site' => 'Twitter Business @username',
				),
			),
			'head' => array(
				'min' => array(
					'og_desc_len' => 156,
					'og_img_width' => 200,
					'og_img_height' => 200,
					'schema_img_width' => 696,	// https://developers.google.com/search/docs/data-types/articles#article_types
				),
				'max' => array(
					'og_img_ratio' => 3,
					'schema_img_ratio' => 2.5,	// https://developers.google.com/+/web/snippet/article-rendering
				),
				'og_type_ns' => array(		// http://ogp.me/#types
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
				'og_type_mt' => array(
					'article' => array(
						'article:author',
						'article:section',
						'article:tag',
						'article:published_time',
						'article:modified_time',
						'article:expiration_time',
					),
					'book' => array(
						'book:author',
						'book:isbn',
						'book:release_date',
						'book:tag',
					),
					'music.album' => array(
						'music:song',
						'music:song:disc',
						'music:song:track',
						'music:musician',
						'music:release_date',
					),
					'music.playlist' => array(
						'music:creator',
						'music:song',
						'music:song:disc',
						'music:song:track',
					),
					'music.radio_station' => array(
						'music:creator',
					),
					'music.song' => array(
						'music:album',
						'music:album:disc',
						'music:album:track',
						'music:duration',
						'music:musician',
					),
					'place' => array(
						'place:location:latitude',
						'place:location:longitude',
						'place:location:altitude',
						'place:street_address',
						'place:locality',
						'place:region',
						'place:postal_code',
						'place:country_name',
					),
					'product' => array(
						'product:availability',
						'product:price:amount',
						'product:price:currency',
					),
					'profile' => array(
						'profile:first_name',
						'profile:last_name',
						'profile:username',
						'profile:gender',
					),
					'video.episode' => array(
						'video:actor',
						'video:actor:role',
						'video:director',
						'video:writer',
						'video:duration',
						'video:release_date',
						'video:tag',
						'video:series',
					),
					'video.movie' => array(
						'video:actor',
						'video:actor:role',
						'video:director',
						'video:writer',
						'video:duration',
						'video:release_date',
						'video:tag',
					),
					'video.other' => array(
						'video:actor',
						'video:actor:role',
						'video:director',
						'video:writer',
						'video:duration',
						'video:release_date',
						'video:tag',
					),
					'video.tv_show' => array(
						'video:actor',
						'video:actor:role',
						'video:director',
						'video:writer',
						'video:duration',
						'video:release_date',
						'video:tag',
					),
				),
				// NgfbSchema get_schema_types() flattens the array.
				// MAKE SURE NOT TO USE DUPLICATE KEYS IN THE ARRAY.
				'schema_type' => array(
					'creative.work' => array(
						'article' => array( 
							'article' => 'http://schema.org/Article',
							'article.news' => 'http://schema.org/NewsArticle',
							'article.tech' => 'http://schema.org/TechArticle',
							'article.scholarly' => 'http://schema.org/ScholarlyArticle',
							'blog.posting' => 'http://schema.org/BlogPosting',
							'report' => 'http://schema.org/Report',
							'social.media.posting' => 'http://schema.org/SocialMediaPosting',
						),
						'book' => 'http://schema.org/Book',
						'blog' => 'http://schema.org/Blog',
						'creative.work' => 'http://schema.org/CreativeWork',
						'recipe' => 'http://schema.org/Recipe',
						'review' => 'http://schema.org/Review',
						'webpage' => 'http://schema.org/WebPage',
						'website' => 'http://schema.org/WebSite',
					),
					'event' => array(
						'event' => 'http://schema.org/Event',
						'event.business' => 'http://schema.org/BusinessEvent',
						'event.childrens' => 'http://schema.org/ChildrensEvent',
						'event.comedy' => 'http://schema.org/ComedyEvent',
						'event.dance' => 'http://schema.org/DanceEvent',
						'event.delivery' => 'http://schema.org/DeliveryEvent',
						'event.education' => 'http://schema.org/EducationEvent',
						'event.exhibition' => 'http://schema.org/ExhibitionEvent',
						'event.festival' => 'http://schema.org/Festival',
						'event.food' => 'http://schema.org/FoodEvent',
						'event.literary' => 'http://schema.org/LiteraryEvent',
						'event.music' => 'http://schema.org/MusicEvent',
						'event.publication' => 'http://schema.org/PublicationEvent',
						'event.sale' => 'http://schema.org/SaleEvent',
						'event.screening' => 'http://schema.org/ScreeningEvent',
						'event.social' => 'http://schema.org/SocialEvent',
						'event.sports' => 'http://schema.org/SportsEvent',
						'event.theater' => 'http://schema.org/TheaterEvent',
						'event.visual.arts' => 'http://schema.org/VisualArtsEvent',
					),
					'organization' => array(
						'airline' => 'http://schema.org/Airline',
						'corporation' => 'http://schema.org/Corporation',
						'educational.organization' => array(
							'college.or.university' => 'http://schema.org/CollegeOrUniversity',
							'educational.organization' => 'http://schema.org/EducationalOrganization',
							'elementary.school' => 'http://schema.org/ElementarySchool',
							'middle.school' => 'http://schema.org/MiddleSchool',
							'preschool' => 'http://schema.org/Preschool',
							'school' => 'http://schema.org/School',
						),
						'government.organization' => 'http://schema.org/GovernmentOrganization',
						'medical.organization' => array(
							'dentist' => 'http://schema.org/Dentist',
							'hospital' => 'http://schema.org/Hospital',
							'medical.organization' => 'http://schema.org/MedicalOrganization',
							'pharmacy' => 'http://schema.org/Pharmacy',
							'physician' => 'http://schema.org/Physician',
						),
						'non-governmental.organization' => 'http://schema.org/NGO',
						'organization' => 'http://schema.org/Organization',
						'performing.group' => array(
							'dance.group' => 'http://schema.org/DanceGroup',
							'music.group' => 'http://schema.org/MusicGroup',
							'performing.group' => 'http://schema.org/PerformingGroup',
							'theater.group' => 'http://schema.org/TheaterGroup',
						),
						'sports.organization' => array(
							'sports.team' => 'http://schema.org/SportsTeam',
							'sports.organization' => 'http://schema.org/SportsOrganization',
						),
					),
					'person' => 'http://schema.org/Person',
					'place' => array(
						'administrative.area' => 'http://schema.org/AdministrativeArea',
						'civic.structure' => 'http://schema.org/CivicStructure',
						'landform' => 'http://schema.org/Landform',
						'landmarks.or.historical.buildings' => 'http://schema.org/LandmarksOrHistoricalBuildings',
						'local.business' => array( 
							'animal.shelter' => 'http://schema.org/AnimalShelter',
							'automotive.business' => 'http://schema.org/AutomotiveBusiness',
							'child.care' => 'http://schema.org/ChildCare',
							'dry.cleaning.or.laundry' => 'http://schema.org/DryCleaningOrLaundry',
							'emergency.service' => 'http://schema.org/EmergencyService',
							'employement.agency' => 'http://schema.org/EmploymentAgency',
							'entertainment.business' => 'http://schema.org/EntertainmentBusiness',
							'financial.service' => 'http://schema.org/FinancialService',
							'food.establishment' => array( 
								'bakery' => 'http://schema.org/Bakery',
								'bar.or.pub' => 'http://schema.org/BarOrPub',
								'brewery' => 'http://schema.org/Brewery',
								'cafe.or.coffee.shop' => 'http://schema.org/CafeOrCoffeeShop',
								'fast.food.restaurant' => 'http://schema.org/FastFoodRestaurant',
								'food.establishment' => 'http://schema.org/FoodEstablishment',
								'ice.cream.shop' => 'http://schema.org/IceCreamShop',
								'restaurant' => 'http://schema.org/Restaurant',
								'winery' => 'http://schema.org/Winery',
							),
							'government.office' => 'http://schema.org/GovernmentOffice',
							'health.and.beauty.business' => 'http://schema.org/HealthAndBeautyBusiness',
							'home.and.construction.business' => 'http://schema.org/HomeAndConstructionBusiness',
							'internet.cafe' => 'http://schema.org/InternetCafe',
							'legal.service' => 'http://schema.org/LegalService',
							'library' => 'http://schema.org/Library',
							'local.business' => 'http://schema.org/LocalBusiness',
							'lodging.business' => 'http://schema.org/LodgingBusiness',
							'medical.organization' => 'http://schema.org/MedicalOrganization',
							'professional.service' => 'http://schema.org/ProfessionalService',
							'radio.station' => 'http://schema.org/RadioStation',
							'real.estate.agent' => 'http://schema.org/RealEstateAgent',
							'recycling.center' => 'http://schema.org/RecyclingCenter',
							'self.storage' => 'http://schema.org/SelfStorage',
							'shopping.center' => 'http://schema.org/ShoppingCenter',
							'sports.activity.location' => 'http://schema.org/SportsActivityLocation',
							'store' => array(
								'auto.parts.store' => 'http://schema.org/AutoPartsStore',
								'bike.store' => 'http://schema.org/BikeStore',
								'book.store' => 'http://schema.org/BookStore',
								'clothing.store' => 'http://schema.org/ClothingStore',
								'computer.store' => 'http://schema.org/ComputerStore',
								'convenience.store' => 'http://schema.org/ConvenienceStore',
								'department.store' => 'http://schema.org/DepartmentStore',
								'electronics.store' => 'http://schema.org/ElectronicsStore',
								'florist' => 'http://schema.org/Florist',
								'flower.shop' => 'http://schema.org/Florist',
								'furniture.store' => 'http://schema.org/FurnitureStore',
								'garden.store' => 'http://schema.org/GardenStore',
								'grocery.store' => 'http://schema.org/GroceryStore',
								'hardware.store' => 'http://schema.org/HardwareStore',
								'hobby.shop' => 'http://schema.org/HobbyShop',
								'home.goods.store' => 'http://schema.org/HomeGoodsStore',
								'jewelry.store' => 'http://schema.org/JewelryStore',
								'liquor.store' => 'http://schema.org/LiquorStore',
								'mens.clothing.store' => 'http://schema.org/MensClothingStore',
								'mobile.phone.store' => 'http://schema.org/MobilePhoneStore',
								'movie.rental.store' => 'http://schema.org/MovieRentalStore',
								'music.store' => 'http://schema.org/MusicStore',
								'office.equipment.store' => 'http://schema.org/OfficeEquipmentStore',
								'outlet.store' => 'http://schema.org/OutletStore',
								'pawn.shop' => 'http://schema.org/PawnShop',
								'pet.store' => 'http://schema.org/PetStore',
								'shoe.store' => 'http://schema.org/ShoeStore',
								'sporting.goods.store' => 'http://schema.org/SportingGoodsStore',
								'store' => 'http://schema.org/Store',
								'tire.shop' => 'http://schema.org/TireShop',
								'toy.store' => 'http://schema.org/ToyStore',
								'wholesale.store' => 'http://schema.org/WholesaleStore',
							),
							'television.station' => 'http://schema.org/TelevisionStation',
							'tourist.information.center' => 'http://schema.org/TouristInformationCenter',
							'travel.agency' => 'http://schema.org/TravelAgency',
						),
						'place' => 'http://schema.org/Place',
						'residence' => 'http://schema.org/Residence',
						'tourist.attraction' => 'http://schema.org/TouristAttraction',
					),
					'product' => 'http://schema.org/Product',
				),
			),
			'cache' => array(
				'file' => false,
				'object' => true,
				'transient' => true,
			),
			'follow' => array(
				'size' => 24,
				'src' => array(
					'images/follow/Wordpress.png' => 'https://profiles.wordpress.org/jsmoriss/',
					'images/follow/Github.png' => 'https://github.com/SurniaUlula',
					'images/follow/Facebook.png' => 'https://www.facebook.com/SurniaUlulaCom',
					'images/follow/GooglePlus.png' => 'https://plus.google.com/+SurniaUlula/',
					//'images/follow/Linkedin.png' => 'https://www.linkedin.com/company/surnia-ulula-ltd',
					'images/follow/Twitter.png' => 'https://twitter.com/surniaululacom',
					//'images/follow/Youtube.png' => 'https://www.youtube.com/user/SurniaUlulaCom',
					'images/follow/Rss.png' => 'http://wpsso.com/category/application/wordpress/wp-plugins/wpsso/feed/',
				),
			),
		);

		public static function get_version() { 
			return self::$cf['plugin']['wpsso']['version'];
		}

		// get_config is called very early, so don't apply filters unless instructed
		public static function get_config( $idx = false, $do_filter = false ) { 

			if ( ! isset( self::$cf['config_filtered'] ) || 
				self::$cf['config_filtered'] !== true ) {

				self::$cf['*'] = array(
					'base' => array(),
					'lib' => array(
						'gpl' => array (),
						'pro' => array (),
					),
					'version' => '',		// -wpsso3.29.0pro-wpssoplm1.5.1pro-wpssoum1.4.0gpl
				);
	
				self::$cf['opt']['version'] = '';	// -wpsso416pro-wpssoplm8pro

				if ( $do_filter ) {

					self::$cf = apply_filters( self::$cf['lca'].'_get_config', self::$cf );

					self::$cf['config_filtered'] = true;

					foreach ( self::$cf['plugin'] as $ext => $info ) {
	
						if ( defined( strtoupper( $ext ).'_PLUGINDIR' ) )
							$pkg = is_dir( constant( strtoupper( $ext ).
								'_PLUGINDIR' ).'lib/pro/' ) ? 'pro' : 'gpl';
						else $pkg = '';
	
						if ( isset( $info['base'] ) )
							self::$cf['*']['base'][$info['base']] = $ext;
	
						if ( isset( $info['lib'] ) && is_array( $info['lib'] ) )
							self::$cf['*']['lib'] = SucomUtil::array_merge_recursive_distinct( 
								self::$cf['*']['lib'], $info['lib'] );
	
						if ( isset( $info['version'] ) )
							self::$cf['*']['version'] .= '-'.$ext.$info['version'].$pkg;
	
						if ( isset( $info['opt_version'] ) )
							self::$cf['opt']['version'] .= '-'.$ext.$info['opt_version'].$pkg;

						// complete relative paths in the image array
						foreach ( $info['img'] as $id => $url )
							if ( ! empty( $url ) && strpos( $url, '//' ) === false )
								self::$cf['plugin'][$ext]['img'][$id] = trailingslashit( plugins_url( '', $info['base'] ) ).$url;
					}
				}
			}

			if ( $idx !== false ) {
				if ( isset( self::$cf[$idx] ) )
					return self::$cf[$idx];
				else return null;
			} else return self::$cf;
		}

		public static function set_constants( $plugin_filepath ) { 
			define( 'WPSSO_FILEPATH', $plugin_filepath );						
			define( 'WPSSO_PLUGINDIR', trailingslashit( realpath( dirname( $plugin_filepath ) ) ) );
			define( 'WPSSO_PLUGINSLUG', self::$cf['plugin']['wpsso']['slug'] );		// wpsso
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
			$var_const['WPSSO_MENU_ORDER'] = '99.10';		// position of the SSO menu item
			$var_const['WPSSO_MENU_ICON_HIGHLIGHT'] = true;		// highlight the SSO menu icon
			$var_const['WPSSO_HIDE_ALL_WARNINGS'] = false;		// auto-hide all warning notices
			$var_const['WPSSO_JSON_PRETTY_PRINT'] = true;		// don't minimize json code

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
			$var_const['WPSSO_ADD_SUBMENU_PRIORITY'] = -10;
			$var_const['WPSSO_META_SAVE_PRIORITY'] = 6;
			$var_const['WPSSO_META_CACHE_PRIORITY'] = 9;
			$var_const['WPSSO_INIT_PRIORITY'] = 12;
			$var_const['WPSSO_HEAD_PRIORITY'] = 10;
			$var_const['WPSSO_SEO_FILTERS_PRIORITY'] = 100;

			/*
			 * WPSSO curl settings
			 */
			if ( defined( 'WPSSO_PLUGINDIR' ) )
				$var_const['WPSSO_CURL_CAINFO'] = WPSSO_PLUGINDIR.'share/curl/ca-bundle.crt';
			$var_const['WPSSO_CURL_USERAGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:40.0) Gecko/20100101 Firefox/40.0';

			// disable 3rd-party caching for duplicate meta tag checks
			if ( ! empty( $_GET['WPSSO_META_TAGS_DISABLE'] ) ) {
				$var_const['DONOTCACHEPAGE'] = true;		// wp super cache and w3tc
				$var_const['COMET_CACHE_ALLOWED'] = false;	// comet cache
				$var_const['QUICK_CACHE_ALLOWED'] = false;	// quick cache
				$var_const['ZENCACHE_ALLOWED'] = false;		// zencache
			}

			foreach ( $var_const as $name => $value )
				if ( defined( $name ) )
					$var_const[$name] = constant( $name );	// inherit existing values

			return $var_const;
		}

		public static function require_libs( $plugin_filepath ) {
			
			require_once( WPSSO_PLUGINDIR.'lib/com/exception.php' );	// extends Exception
			require_once( WPSSO_PLUGINDIR.'lib/com/util.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/cache.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/script.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/style.php' );
			require_once( WPSSO_PLUGINDIR.'lib/com/webpage.php' );

			require_once( WPSSO_PLUGINDIR.'lib/register.php' );
			require_once( WPSSO_PLUGINDIR.'lib/check.php' );
			require_once( WPSSO_PLUGINDIR.'lib/util.php' );
			require_once( WPSSO_PLUGINDIR.'lib/options.php' );
			require_once( WPSSO_PLUGINDIR.'lib/meta.php' );
			require_once( WPSSO_PLUGINDIR.'lib/post.php' );		// extends meta.php
			require_once( WPSSO_PLUGINDIR.'lib/term.php' );		// extends meta.php
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
						return SucomUtil::sanitize_classname( 'wpsso'.$filespec, false );	// $underscore = false
					else return $classname;
				}
			}
			return $ret;
		}
	}
}

?>
