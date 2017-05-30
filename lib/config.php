<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoConfig' ) ) {

	class WpssoConfig {

		public static $cf = array(
			'lca' => 'wpsso',			// lowercase acronym
			'readme_cache_exp' => DAY_IN_SECONDS,	// 1 day
			'setup_cache_exp' => DAY_IN_SECONDS,	// 1 day
			'install_hosts' => array(		// allow extensions to be installed from these hosts
				'https://wpsso.com/extend/plugins/',
			),
			'plugin' => array(
				'wpsso' => array(
					'version' => '3.44.2',		// plugin version
					'opt_version' => '528',		// increment when changing default options
					'short' => 'WPSSO',		// short plugin name
					'name' => 'WPSSO (Core Plugin)',
					'desc' => 'Automatically generate complete and accurate meta tags + Schema markup from your content for social media optimization (SMO) and SEO.',
					'slug' => 'wpsso',
					'base' => 'wpsso/wpsso.php',
					'update_auth' => 'tid',
					'text_domain' => 'wpsso',
					'domain_path' => '/languages',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'images/icon-128x128.png',
							'high' => 'images/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso',
						'review' => 'https://wordpress.org/support/plugin/wpsso/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso/master/readme.txt',
						'setup_html' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso/master/setup.html',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso/',
						'faqs' => 'https://wpsso.com/docs/plugins/wpsso/faqs/',
						'notes' => 'https://wpsso.com/docs/plugins/wpsso/notes/',
						'support' => 'http://wpsso.support.wpsso.com/support/tickets/new',
						'purchase' => 'https://wpsso.com/extend/plugins/wpsso/',
						'update' => 'https://wpsso.com/extend/plugins/wpsso/update/',
						'latest' => '',
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
							'setup' => '<color>Plugin Setup Guide and Other Notes</color>',
							'licenses' => 'Extension Plugins and Pro Licenses',
							'dashboard' => 'Plugin Dashboard and Features Status',
						),
						'sitesubmenu' => array(	// lib file descriptions will be translated
							'siteadvanced' => 'Advanced Settings',
							'sitesetup' => '<color>Plugin Setup Guide and Other Notes</color>',
							'sitelicenses' => 'Extension Plugins and Pro Licenses',
						),
						'gpl' => array(
							'admin' => array(
								'general' => 'General Settings',
								'advanced' => 'Advanced Settings',
								'post' => 'Post Settings',
								'meta' => 'Term and User Settings',
							),
							'social' => array(
								'buddypress' => '(plugin) BuddyPress',
							),
							'util' => array(
								'post' => '(tool) Custom Post Meta',
								'term' => '(tool) Custom Term Meta',
								'user' => '(tool) Custom User Meta',
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
							'form' => array(
								'gravityview' => '(plugin) GravityView',
							),
							'forum' => array(
								'bbpress' => '(plugin) bbPress',
							),
							'lang' => array(
								'polylang' => '(plugin) Polylang',
							),
							'media' => array(
								'facebook' => '(api) Facebook Video API',
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
								'wpseo_meta' => '(tool) Yoast SEO Social Meta',
							),
						),
					),
				),
				'wpssoam' => array(
					'short' => 'WPSSO AM',		// short plugin name
					'name' => 'WPSSO Mobile App Meta',
					'desc' => 'WPSSO extension to provide Apple Store / iTunes and Google Play App meta tags for Apple\'s mobile Safari and Twitter\'s App Card.',
					'slug' => 'wpsso-am',
					'base' => 'wpsso-am/wpsso-am.php',
					'update_auth' => 'tid',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-am/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-am/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-am/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-am/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso-am/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-am',
						'review' => 'https://wordpress.org/support/plugin/wpsso-am/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-am/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-am/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-am/',
						'faqs' => '',
						'notes' => '',
						'support' => 'http://wpsso-am.support.wpsso.com/support/tickets/new',
						'purchase' => 'https://wpsso.com/extend/plugins/wpsso-am/',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-am/update/',
						'latest' => '',
					),
				),
				'wpssojson' => array(
					'short' => 'WPSSO JSON',		// short plugin name
					'name' => 'WPSSO Schema JSON-LD Markup',
					'desc' => 'WPSSO extension to add Schema JSON-LD / SEO markup for Articles, Events, Local Business, Products, Recipes, Reviews + many more.',
					'slug' => 'wpsso-schema-json-ld',
					'base' => 'wpsso-schema-json-ld/wpsso-schema-json-ld.php',
					'update_auth' => 'tid',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-schema-json-ld/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-schema-json-ld/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-schema-json-ld/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-schema-json-ld/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso-schema-json-ld/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-schema-json-ld',
						'review' => 'https://wordpress.org/support/plugin/wpsso-schema-json-ld/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-schema-json-ld/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-schema-json-ld/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-schema-json-ld/',
						'faqs' => '',
						'notes' => 'https://wpsso.com/docs/plugins/wpsso-schema-json-ld/notes/',
						'support' => 'http://wpsso-schema-json-ld.support.wpsso.com/support/tickets/new',
						'purchase' => 'https://wpsso.com/extend/plugins/wpsso-schema-json-ld/',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-schema-json-ld/update/',
						'latest' => '',
					),
				),
				'wpssoorg' => array(
					'short' => 'WPSSO ORG',		// short plugin name
					'name' => 'WPSSO Organization Markup',
					'desc' => 'WPSSO extension to manage Organizations and additional Schema Article / Event properties (Publisher, Organizer, Performer, etc.).',
					'slug' => 'wpsso-organization',
					'base' => 'wpsso-organization/wpsso-organization.php',
					'update_auth' => 'tid',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-organization/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-organization/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-organization/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-organization/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso-organization/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-organization',
						'review' => 'https://wordpress.org/support/plugin/wpsso-organization/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-organization/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-organization/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-organization/',
						'faqs' => '',
						'notes' => '',
						'support' => 'http://wpsso-organization.support.wpsso.com/support/tickets/new',
						'purchase' => 'https://wpsso.com/extend/plugins/wpsso-organization/',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-organization/update/',
						'latest' => '',
					),
				),
				'wpssoplm' => array(
					'short' => 'WPSSO PLM',		// short plugin name
					'name' => 'WPSSO Place / Location and Local Business Meta',
					'desc' => 'WPSSO extension to provide Pinterest Place, Facebook / Open Graph Location, Schema Local Business, and Local SEO meta tags.',
					'slug' => 'wpsso-plm',
					'base' => 'wpsso-plm/wpsso-plm.php',
					'update_auth' => 'tid',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-plm/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-plm/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-plm/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-plm/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso-plm/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-plm',
						'review' => 'https://wordpress.org/support/plugin/wpsso-plm/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-plm/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-plm/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-plm/',
						'faqs' => '',
						'notes' => '',
						'support' => 'http://wpsso-plm.support.wpsso.com/support/tickets/new',
						'purchase' => 'https://wpsso.com/extend/plugins/wpsso-plm/',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-plm/update/',
						'latest' => '',
					),
				),
				'wpssorar' => array(
					'short' => 'WPSSO RAR',		// short plugin name
					'name' => 'WPSSO Ratings and Reviews',
					'desc' => 'WPSSO extension to add ratings and reviews for WordPress comments, with Aggregate Rating meta tags and optional Schema Review markup.',
					'slug' => 'wpsso-ratings-and-reviews',
					'base' => 'wpsso-ratings-and-reviews/wpsso-ratings-and-reviews.php',
					'update_auth' => '',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-ratings-and-reviews/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-ratings-and-reviews/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-ratings-and-reviews/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-ratings-and-reviews/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress.org
						'home' => 'https://wordpress.org/plugins/wpsso-ratings-and-reviews/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-ratings-and-reviews',
						'review' => 'https://wordpress.org/support/plugin/wpsso-ratings-and-reviews/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-ratings-and-reviews/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-ratings-and-reviews/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-ratings-and-reviews/',
						'faqs' => '',
						'notes' => '',
						'support' => '',
						'purchase' => '',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-ratings-and-reviews/update/',
						'latest' => '',
					),
				),
				'wpssorrssb' => array(
					'short' => 'WPSSO RRSSB',		// short plugin name
					'name' => 'WPSSO Ridiculously Responsive Social Sharing Buttons',
					'desc' => 'WPSSO extension to add Ridiculously Responsive (SVG) Social Sharing Buttons in your content, excerpts, CSS sidebar, widget, shortcode, etc.',
					'slug' => 'wpsso-rrssb',
					'base' => 'wpsso-rrssb/wpsso-rrssb.php',
					'update_auth' => 'tid',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-rrssb/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-rrssb/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-rrssb/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-rrssb/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress.org
						'home' => 'https://wordpress.org/plugins/wpsso-rrssb/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-rrssb',
						'review' => 'https://wordpress.org/support/plugin/wpsso-rrssb/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-rrssb/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-rrssb/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-rrssb/',
						'faqs' => '',
						'notes' => 'https://wpsso.com/docs/plugins/wpsso-rrssb/notes/',
						'support' => 'http://wpsso-rrssb.support.wpsso.com/support/tickets/new',
						'purchase' => 'https://wpsso.com/extend/plugins/wpsso-rrssb/',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-rrssb/update/',
						'latest' => '',
					),
				),
				'wpssossb' => array(
					'short' => 'WPSSO SSB',		// short plugin name
					'name' => 'WPSSO Social Sharing Buttons',
					'desc' => 'WPSSO extension to add Social Sharing Buttons with support for hashtags, short URLs, bbPress, BuddyPress, WooCommerce, and much more.',
					'slug' => 'wpsso-ssb',
					'base' => 'wpsso-ssb/wpsso-ssb.php',
					'update_auth' => 'tid',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-ssb/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-ssb/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-ssb/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-ssb/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso-ssb/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-ssb',
						'review' => 'https://wordpress.org/support/plugin/wpsso-ssb/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-ssb/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-ssb/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-ssb/',
						'faqs' => 'https://wpsso.com/docs/plugins/wpsso-ssb/faqs/',
						'notes' => 'https://wpsso.com/docs/plugins/wpsso-ssb/notes/',
						'support' => 'http://wpsso-ssb.support.wpsso.com/support/tickets/new',
						'purchase' => 'https://wpsso.com/extend/plugins/wpsso-ssb/',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-ssb/update/',
						'latest' => '',
					),
				),
				'wpssossm' => array(
					'short' => 'WPSSO SSM',		// short plugin name
					'name' => 'WPSSO Strip Schema Microdata',
					'desc' => 'WPSSO extension to remove outdated / incomplete Schema Microdata, leaving the Google recommended Schema JSON-LD markup untouched.',
					'slug' => 'wpsso-strip-schema-microdata',
					'base' => 'wpsso-strip-schema-microdata/wpsso-strip-schema-microdata.php',
					'update_auth' => '',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-strip-schema-microdata/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-strip-schema-microdata/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-strip-schema-microdata/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-strip-schema-microdata/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso-strip-schema-microdata/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-strip-schema-microdata',
						'review' => 'https://wordpress.org/support/plugin/wpsso-strip-schema-microdata/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-strip-schema-microdata/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-strip-schema-microdata/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-strip-schema-microdata/',
						'faqs' => '',
						'notes' => '',
						'support' => '',
						'purchase' => '',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-strip-schema-microdata/update/',
						'latest' => '',
					),
				),
				'wpssotaq' => array(
					'short' => 'WPSSO TAQ',		// short plugin name
					'name' => 'WPSSO Tweet a Quote',
					'desc' => 'WPSSO extension to add CSS Twitter-style quoted text with a Tweet share link to post and page content (uses easily customized CSS).',
					'slug' => 'wpsso-tweet-a-quote',
					'base' => 'wpsso-tweet-a-quote/wpsso-tweet-a-quote.php',
					'update_auth' => '',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-tweet-a-quote/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-tweet-a-quote/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-tweet-a-quote/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-tweet-a-quote/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso-tweet-a-quote/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-tweet-a-quote',
						'review' => 'https://wordpress.org/support/plugin/wpsso-tweet-a-quote/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-tweet-a-quote/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-tweet-a-quote/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-tweet-a-quote/',
						'faqs' => '',
						'notes' => '',
						'support' => '',
						'purchase' => '',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-tweet-a-quote/update/',
						'latest' => '',
					),
				),
				'wpssoul' => array(
					'short' => 'WPSSO UL',		// short plugin name
					'name' => 'WPSSO User Locale Selector',
					'desc' => 'WPSSO extension to add a user locale / language / region selector in the WordPress admin back-end and front-end toolbar menus.',
					'slug' => 'wpsso-user-locale',
					'base' => 'wpsso-user-locale/wpsso-user-locale.php',
					'update_auth' => '',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-user-locale/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-user-locale/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-user-locale/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-user-locale/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'home' => 'https://wordpress.org/plugins/wpsso-user-locale/',
						'forum' => 'https://wordpress.org/support/plugin/wpsso-user-locale',
						'review' => 'https://wordpress.org/support/plugin/wpsso-user-locale/reviews/?rate=5#new-post',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-user-locale/master/readme.txt',
						// wpsso
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-user-locale/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-user-locale/',
						'faqs' => '',
						'notes' => 'https://wpsso.com/docs/plugins/wpsso-user-locale/notes/',
						'support' => '',
						'purchase' => '',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-user-locale/update/',
						'latest' => '',
					),
				),
				'wpssoum' => array(
					'short' => 'WPSSO UM',		// short plugin name
					'name' => 'WPSSO Update Manager',
					'desc' => 'WPSSO extension to provide updates for the WPSSO Pro plugin and its Pro extensions.',
					'slug' => 'wpsso-um',
					'base' => 'wpsso-um/wpsso-um.php',
					'update_auth' => '',
					'img' => array(
						'banners' => array(
							'low' => 'https://surniaulula.github.io/wpsso-um/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-um/assets/banner-1544x500.jpg',
						),
						'icons' => array(
							'low' => 'https://surniaulula.github.io/wpsso-um/assets/icon-128x128.png',
							'high' => 'https://surniaulula.github.io/wpsso-um/assets/icon-256x256.png',
						),
					),
					'url' => array(
						// wordpress
						'forum' => '',
						'review' => '',
						// github
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-um/master/readme.txt',
						// wpsso
						'home' => 'https://wpsso.com/extend/plugins/wpsso-um/',
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-um/changelog/',
						'docs' => 'https://wpsso.com/docs/plugins/wpsso-um/',
						'faqs' => '',
						'notes' => '',
						'support' => '',
						'purchase' => '',
						'update' => 'https://wpsso.com/extend/plugins/wpsso-um/update/',
						'latest' => 'https://wpsso.com/extend/plugins/wpsso-um/latest/',
					),
				),
			),
			'opt' => array(						// options
				'defaults' => array(
					'options_version' => '',		// example: -wpsso512pro-wpssoum3gpl
					'options_filtered' => false,
					'site_name' => '',			// (localized)
					'site_desc' => '',			// (localized)
					'site_url' => '',
					'site_org_type' => 'organization',
					'site_place_id' => 'none',
					'schema_add_noscript' => 1,
					'schema_website_json' => 1,
					'schema_organization_json' => 1,
					'schema_person_json' => 0,
					'schema_person_id' => '',
					'schema_alt_name' => '',
					'schema_logo_url' => '',
					'schema_banner_url' => '',
					'schema_img_max' => 1,
					'schema_img_width' => 800,			// must be at least 696px for Articles
					'schema_img_height' => 1600,
					'schema_img_crop' => 0,
					'schema_img_crop_x' => 'center',
					'schema_img_crop_y' => 'center',
					'schema_desc_len' => 250,			// meta itemprop="description" maximum text length (hard limit)
					'schema_author_name' => 'display_name',
					// standard types
					'schema_type_for_archive_page' => 'webpage.collection',
					'schema_type_for_attachment' => 'webpage',
					'schema_type_for_home_index' => 'blog',
					'schema_type_for_home_page' => 'website',
					'schema_type_for_page' => 'article',
					'schema_type_for_post' => 'blog.posting',
					'schema_type_for_search_page' => 'webpage.search.results',
					'schema_type_for_user_page' => 'webpage.profile',
					// custom post types
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
					'schema_review_item_type' => 'none',	// Default Reviewed Item Type
					'seo_desc_len' => 156,			// meta name="description" maximum text length (hard limit)
					'seo_author_field' => 'gplus',
					'seo_publisher_url' => '',		// (localized)
					'fb_publisher_url' => '',		// (localized)
					'fb_app_id' => '',
					'fb_admins' => '',
					'fb_author_name' => 'display_name',
					'fb_locale' => 'en_US',
					'instgram_publisher_url' => '',		// (localized)
					'linkedin_publisher_url' => '',		// (localized)
					'myspace_publisher_url' => '',		// (localized)
					'og_post_type' => 'article',
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
					'og_ngg_tags' => 0,
					'og_page_parent_tags' => 0,
					'og_page_title_tag' => 0,
					'og_author_field' => 'facebook',
					'og_author_fallback' => 0,
					'og_title_sep' => '-',
					'og_title_len' => 70,
					'og_title_warn' => 40,
					'og_desc_len' => 300,			// maximum length in characters (hard limit)
					'og_desc_warn' => 200,			// recommended maximum length in characters for Facebook (soft limit)
					'og_desc_hashtags' => 3,
					'p_publisher_url' => '',		// (localized)
					'p_author_name' => 'display_name',	// rich-pin specific article:author
					'p_dom_verify' => '',
					'p_add_img_html' => 1,
					'p_add_nopin_media_img_tag' => 1,
					'p_add_nopin_header_img_tag' => 1,
					'tc_site' => '',			// Twitter Business @username (localized)
					'tc_desc_len' => 200,			// Maximum Description Length (hard limit)
					'tc_type_post' => 'summary_large_image',
					'tc_type_default' => 'summary',
					// summary card
					'tc_sum_img_width' => 600,		// Summary Card Image Dimensions
					'tc_sum_img_height' => 600,
					'tc_sum_img_crop' => 1,
					'tc_sum_img_crop_x' => 'center',
					'tc_sum_img_crop_y' => 'center',
					// large image summary card
					'tc_lrg_img_width' => 800,		// Large Image Card Img Dimensions
					'tc_lrg_img_height' => 1600,
					'tc_lrg_img_crop' => 0,
					'tc_lrg_img_crop_x' => 'center',
					'tc_lrg_img_crop_y' => 'center',
					// enable/disable head html tags
					'add_link_rel_author' => 1,
					'add_link_rel_canonical' => 0,
					'add_link_rel_publisher' => 1,
					'add_link_rel_shortlink' => 0,
					// facebook
					'add_meta_property_fb:admins' => 1,
					'add_meta_property_fb:app_id' => 1,
					// facebook applink
					'add_meta_property_al:ios:app_name' => 1,
					'add_meta_property_al:ios:app_store_id' => 1,
					'add_meta_property_al:ios:url' => 1,
					'add_meta_property_al:android:app_name' => 1,
					'add_meta_property_al:android:package' => 1,
					'add_meta_property_al:android:url' => 1,
					'add_meta_property_al:web:url' => 1,
					'add_meta_property_al:web:should_fallback' => 1,
					// open graph
					'add_meta_property_og:altitude' => 1,
					'add_meta_property_og:description' => 1,
					'add_meta_property_og:image:secure_url' => 1,
					'add_meta_property_og:image' => 1,
					'add_meta_property_og:image:width' => 1,
					'add_meta_property_og:image:height' => 1,
					'add_meta_property_og:latitude' => 1,
					'add_meta_property_og:locale' => 1,
					'add_meta_property_og:longitude' => 1,
					'add_meta_property_og:site_name' => 1,
					'add_meta_property_og:title' => 1,
					'add_meta_property_og:type' => 1,
					'add_meta_property_og:updated_time' => 1,
					'add_meta_property_og:url' => 1,
					'add_meta_property_og:video:secure_url' => 1,
					'add_meta_property_og:video:url' => 1,
					'add_meta_property_og:video:type' => 1,
					'add_meta_property_og:video:width' => 1,
					'add_meta_property_og:video:height' => 1,
					'add_meta_property_og:video:tag' => 0,
					// open graph (article)
					'add_meta_property_article:author' => 1,
					'add_meta_property_article:publisher' => 1,
					'add_meta_property_article:published_time' => 1,
					'add_meta_property_article:modified_time' => 1,
					'add_meta_property_article:expiration_time' => 1,
					'add_meta_property_article:section' => 1,
					'add_meta_property_article:tag' => 1,
					// open graph (book)
					'add_meta_property_book:author' => 1,
					'add_meta_property_book:isbn' => 1,
					'add_meta_property_book:release_date' => 1,
					'add_meta_property_book:tag' => 1,
					// open graph (music)
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
					// open graph (place)
					'add_meta_property_place:location:altitude' => 1,
					'add_meta_property_place:location:latitude' => 1,
					'add_meta_property_place:location:longitude' => 1,
					'add_meta_property_place:street_address' => 1,
					'add_meta_property_place:locality' => 1,
					'add_meta_property_place:region' => 1,
					'add_meta_property_place:postal_code' => 1,
					'add_meta_property_place:country_name' => 1,
					// open graph (product)
					'add_meta_property_product:availability' => 1,
					'add_meta_property_product:condition' => 1,
					'add_meta_property_product:price:amount' => 1,
					'add_meta_property_product:price:currency' => 1,
					'add_meta_property_product:weight:value' => 1,
					'add_meta_property_product:weight:units' => 1,
					// open graph (profile)
					'add_meta_property_profile:first_name' => 1,
					'add_meta_property_profile:last_name' => 1,
					'add_meta_property_profile:username' => 1,
					'add_meta_property_profile:gender' => 1,
					// open graph (video)
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
					'add_meta_name_description' => 1,
					'add_meta_name_generator' => 1,
					// pinterest
					'add_meta_name_p:domain_verify' => 1,
					// weibo
					'add_meta_name_weibo:article:create_at' => 1,
					'add_meta_name_weibo:article:update_at' => 1,
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
					'add_meta_name_twitter:player:stream' => 1,
					'add_meta_name_twitter:player:stream:content_type' => 1,
					'add_meta_name_twitter:player:width' => 1,
					'add_meta_name_twitter:player:height' => 1,
					'add_meta_name_twitter:app:name:iphone' => 1,
					'add_meta_name_twitter:app:id:iphone' => 1,
					'add_meta_name_twitter:app:url:iphone' => 1,
					'add_meta_name_twitter:app:name:ipad' => 1,
					'add_meta_name_twitter:app:id:ipad' => 1,
					'add_meta_name_twitter:app:url:ipad' => 1,
					'add_meta_name_twitter:app:name:googleplay' => 1,
					'add_meta_name_twitter:app:id:googleplay' => 1,
					'add_meta_name_twitter:app:url:googleplay' => 1,
					// schema link
					'add_link_itemprop_url' => 1,
					'add_link_itemprop_image' => 1,
					'add_link_itemprop_image.url' => 1,
					'add_link_itemprop_author.url' => 1,
					'add_link_itemprop_author.image' => 1,
					'add_link_itemprop_contributor.url' => 1,
					'add_link_itemprop_contributor.image' => 1,
					'add_link_itemprop_menu' => 1,
					// schema meta
					'add_meta_itemprop_name' => 1,
					'add_meta_itemprop_alternatename' => 1,
					'add_meta_itemprop_description' => 1,
					'add_meta_itemprop_email' => 1,
					'add_meta_itemprop_telephone' => 1,
					'add_meta_itemprop_address' => 1,
					'add_meta_itemprop_datepublished' => 1,
					'add_meta_itemprop_datemodified' => 1,
					'add_meta_itemprop_image.width' => 1,
					'add_meta_itemprop_image.height' => 1,
					'add_meta_itemprop_publisher.name' => 1,
					'add_meta_itemprop_author.name' => 1,
					'add_meta_itemprop_author.description' => 1,
					'add_meta_itemprop_contributor.name' => 1,
					'add_meta_itemprop_contributor.description' => 1,
					'add_meta_itemprop_openinghoursspecification.dayofweek' => 1,
					'add_meta_itemprop_openinghoursspecification.opens' => 1,
					'add_meta_itemprop_openinghoursspecification.closes' => 1,
					'add_meta_itemprop_openinghoursspecification.validfrom' => 1,
					'add_meta_itemprop_openinghoursspecification.validthrough' => 1,
					'add_meta_itemprop_currenciesaccepted' => 1,
					'add_meta_itemprop_paymentaccepted' => 1,
					'add_meta_itemprop_pricerange' => 1,
					'add_meta_itemprop_acceptsreservations' => 1,
					'add_meta_itemprop_aggregaterating.ratingvalue' => 1,
					'add_meta_itemprop_aggregaterating.ratingcount' => 1,
					'add_meta_itemprop_aggregaterating.worstrating' => 1,
					'add_meta_itemprop_aggregaterating.bestrating' => 1,
					'add_meta_itemprop_aggregaterating.reviewcount' => 1,
					'add_meta_itemprop_startdate' => 1,		// Schema Event
					'add_meta_itemprop_enddate' => 1,		// Schema Event
					'add_meta_itemprop_location' => 1,		// Schema Event
					'add_meta_itemprop_preptime' => 1,		// Schema Recipe
					'add_meta_itemprop_cooktime' => 1,		// Schema Recipe
					'add_meta_itemprop_totaltime' => 1,		// Schema Recipe
					'add_meta_itemprop_recipeyield' => 1,		// Schema Recipe
					'add_meta_itemprop_recipeingredient' => 1,	// Schema Recipe (supersedes ingredients)
					'add_meta_itemprop_recipeinstructions' => 1,	// Schema Recipe
					/*
					 * Advanced Settings
					 */
					// Plugin Settings Tab
					'plugin_preserve' => 0,				// Preserve Settings on Uninstall
					'plugin_debug' => 0,				// Add Hidden Debug Messages
					'plugin_hide_pro' => 0,				// Hide All Pro Version Options
					'plugin_show_opts' => 'basic',			// Options to Show by Default
					// Content and Filters Tab
					'plugin_filter_title' => 0,			// Use Filtered (SEO) Title
					'plugin_filter_content' => 0,			// Apply WordPress Content Filters
					'plugin_filter_excerpt' => 0,			// Apply WordPress Excerpt Filters
					'plugin_p_strip' => 0,				// Content Starts at 1st Paragraph
					'plugin_use_img_alt' => 1,			// Use Image Alt if No Content
					'plugin_img_alt_prefix' => 'Image:',		// Image Alt Text Prefix
					'plugin_p_cap_prefix' => 'Caption:',		// WP Caption Prefix
					'plugin_content_img_max' => 5,			// Maximum Images from Content
					'plugin_content_vid_max' => 5,			// Maximum Videos from Content
					'plugin_gravatar_api' => 1,			// Include Author Gravatar Image
					'plugin_facebook_api' => 1,			// Check for Embedded Media: Facebook
					'plugin_slideshare_api' => 1,			// Check for Embedded Media: Slideshare
					'plugin_vimeo_api' => 1,			// Check for Embedded Media: Vimeo
					'plugin_wistia_api' => 1,			// Check for Embedded Media: Wistia
					'plugin_youtube_api' => 1,			// Check for Embedded Media: Youtube
					// Integration Tab
					'plugin_honor_force_ssl' => 1,			// Honor the FORCE_SSL Constant
					'plugin_html_attr_filter_name' => 'language_attributes',
					'plugin_html_attr_filter_prio' => 100,
					'plugin_head_attr_filter_name' => 'head_attributes',
					'plugin_head_attr_filter_prio' => 100,
					'plugin_check_head' => 1,			// Check for Duplicate Meta Tags
					'plugin_filter_lang' => 1,			// Use WP Locale for Language
					'plugin_create_wp_sizes' => 1,			// Create Missing WP Media Sizes
					'plugin_check_img_dims' => 0,			// Enforce Image Dimensions Check
					'plugin_upscale_images' => 0,			// Allow Upscale of Smaller Images
					'plugin_upscale_img_max' => 33,			// Maximum Image Upscale Percent
					'plugin_shortcodes' => 1,			// Enable Plugin Shortcode(s)
					'plugin_widgets' => 1,				// Enable Plugin Widget(s)
					'plugin_page_excerpt' => 0,			// Enable WP Excerpt for Pages
					'plugin_page_tags' => 0,			// Enable WP Tags for Pages
					// Custom Meta Tab
					'plugin_schema_type_col_media' => 0,
					'plugin_schema_type_col_post' => 1,
					'plugin_schema_type_col_term' => 0,
					'plugin_schema_type_col_user' => 0,
					'plugin_og_img_col_media' => 0,
					'plugin_og_img_col_post' => 1,
					'plugin_og_img_col_term' => 1,
					'plugin_og_img_col_user' => 1,
					'plugin_og_desc_col_media' => 0,
					'plugin_og_desc_col_post' => 0,
					'plugin_og_desc_col_term' => 0,
					'plugin_og_desc_col_user' => 1,
					'plugin_add_to_attachment' => 1,
					'plugin_add_to_page' => 1,
					'plugin_add_to_post' => 1,
					'plugin_add_to_product' => 1,
					'plugin_add_to_reply' => 0,	// bbpress
					'plugin_add_to_term' => 1,
					'plugin_add_to_topic' => 0,	// bbpress
					'plugin_add_to_user' => 1,
					'plugin_wpseo_social_meta' => 0,		// Read Yoast SEO Social Meta
					'plugin_cf_img_url' => '_format_image_url',	// Image URL Custom Field
					'plugin_cf_vid_url' => '_format_video_url',	// Video URL Custom Field
					'plugin_cf_vid_embed' => '',			// Video Embed HTML Custom Field
					'plugin_cf_recipe_ingredients' => '',		// Recipe Ingredients Custom Field
					'plugin_cf_recipe_instructions' => '',		// Recipe Instructions Custom Field
					'plugin_cf_product_avail' => '',		// Product Availability Custom Field
					'plugin_cf_product_condition' => '',		// Product Condition Custom Field
					'plugin_cf_product_price' => '',		// Product Price Custom Field
					'plugin_cf_product_currency' => '',		// Product Currency Custom Field
					// Cache Settings Tab
					'plugin_head_cache_exp' => 259200,		// Head Markup Array Cache Expiry (3 days)
					'plugin_shorten_cache_exp' => 5184000,		// Shortened URL Cache Expiry (60 days / 2 months)
					'plugin_content_cache_exp' => HOUR_IN_SECONDS,	// Filtered Content Text Cache Expiry (1 hour)
					'plugin_imgsize_cache_exp' => DAY_IN_SECONDS,	// Get Image (URL) Size Cache Expiry (1 day)
					'plugin_topics_cache_exp' => MONTH_IN_SECONDS,	// Article Topics Array Cache Expiry (1 month)
					'plugin_types_cache_exp' => MONTH_IN_SECONDS,	// Schema Types Array Cache Expiry (1 month)
					'plugin_show_purge_count' => 0,			// Show Cache Purge Count on Update
					'plugin_clear_on_save' => 1,			// Clear All Cache on Save Settings
					'plugin_clear_short_urls' => 0,			// Clear Short URLs on Clear All Cache
					'plugin_clear_for_comment' => 1,		// Clear Post Cache for Comment
					// Service APIs Tab
					'plugin_shortener' => 'none',
					'plugin_shortlink' => 1,			// Use Shortnened URL for WP Shortlink
					'plugin_min_shorten' => 23,
					'plugin_bitly_login' => '',
					'plugin_bitly_token' => '',
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
					'plugin_cm_myspace_label' => 'Myspace URL',
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
				),	// end of defaults
				'site_defaults' => array(
					'options_version' => '',		// example: -wpsso512pro-wpssoum3gpl
					'options_filtered' => false,
					/*
					 * Advanced Settings
					 */
					// Plugin Settings Tab
					'plugin_preserve' => 0,				// Preserve Settings on Uninstall
					'plugin_preserve:use' => 'default',
					'plugin_debug' => 0,				// Add Hidden Debug Messages
					'plugin_debug:use' => 'default',
					'plugin_hide_pro' => 0,				// Hide All Pro Version Options
					'plugin_hide_pro:use' => 'default',
					'plugin_show_opts' => 'basic',			// Options to Show by Default
					'plugin_show_opts:use' => 'default',
					// Content and Filters Tab
					// Social Settings Tab
					// Integration Tab
					'plugin_honor_force_ssl' => 1,			// Honor the FORCE_SSL Constant
					'plugin_honor_force_ssl:use' => 'default',
					'plugin_check_head' => 1,			// Check for Duplicate Meta Tags
					'plugin_check_head:use' => 'default',
					'plugin_filter_lang' => 1,			// Use WP Locale for Language
					'plugin_filter_lang:use' => 'default',
					'plugin_create_wp_sizes' => 1,			// Recreate Missing WP Media Sizes
					'plugin_create_wp_sizes:use' => 'default',
					'plugin_check_img_dims' => 0,			// Enforce Image Dimensions Check
					'plugin_check_img_dims:use' => 'default',
					'plugin_upscale_images' => 0,			// Allow Upscale of Smaller Images
					'plugin_upscale_images:use' => 'default',
					'plugin_upscale_img_max' => 33,			// Maximum Image Upscale Percent
					'plugin_upscale_img_max:use' => 'default',
					'plugin_shortcodes' => 1,			// Enable Plugin Shortcode(s)
					'plugin_shortcodes:use' => 'default',
					'plugin_widgets' => 1,				// Enable Plugin Widget(s)
					'plugin_widgets:use' => 'default',
					'plugin_page_excerpt' => 0,			// Enable WP Excerpt for Pages
					'plugin_page_excerpt:use' => 'default',
					'plugin_page_tags' => 0,			// Enable WP Tags for Pages
					'plugin_page_tags:use' => 'default',
					// Cache Settings Tab
					'plugin_head_cache_exp' => 259200,		// Head Markup Array Cache Expiry (3 days)
					'plugin_head_cache_exp:use' => 'default',
					'plugin_shorten_cache_exp' => 5184000,		// Shortened URL Cache Expiry (60 days / 2 months)
					'plugin_shorten_cache_exp:use' => 'default',
					'plugin_content_cache_exp' => HOUR_IN_SECONDS,	// Filtered Content Text Cache Expiry (1 hour)
					'plugin_content_cache_exp:use' => 'default',
					'plugin_imgsize_cache_exp' => DAY_IN_SECONDS,	// Get Image (URL) Size Cache Expiry (1 day)
					'plugin_imgsize_cache_exp:use' => 'default',
					'plugin_topics_cache_exp' => MONTH_IN_SECONDS,	// Article Topics Array Cache Expiry (1 month)
					'plugin_topics_cache_exp:use' => 'default',
					'plugin_types_cache_exp' => MONTH_IN_SECONDS,	// Schema Types Array Cache Expiry (1 month)
					'plugin_types_cache_exp:use' => 'default',
					'plugin_show_purge_count' => 0,			// Show Cache Purge Count on Update
					'plugin_show_purge_count:use' => 'default',
					'plugin_clear_on_save' => 1,			// Clear All Cache on Save Settings
					'plugin_clear_on_save:use' => 'default',
					'plugin_clear_short_urls' => 0,			// Clear Short URLs on Clear All Cache
					'plugin_clear_short_urls:use' => 'default',
					'plugin_clear_for_comment' => 1,		// Clear Post Cache for Comment
					'plugin_clear_for_comment:use' => 'default',
				),	// end of site defaults
				'cm_prefix' => array(		// contact method options prefix
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
				'cf_md_idx' => array(		// custom field to meta data index
					'plugin_cf_img_url' => 'og_img_url',
					'plugin_cf_vid_url' => 'og_vid_url',
					'plugin_cf_vid_embed' => 'og_vid_embed',
					'plugin_cf_recipe_ingredients' => 'schema_recipe_ingredient',
					'plugin_cf_recipe_instructions' => 'schema_recipe_instruction',
					'plugin_cf_product_avail' => 'product_avail',
					'plugin_cf_product_condition' => 'product_condition',
					'plugin_cf_product_price' => 'product_price',
					'plugin_cf_product_currency' => 'product_currency',
				),
				'md_multi' => array(		// read values into numeric meta data index
					'schema_recipe_ingredient' => true,
					'schema_recipe_instruction' => true,
				),
			),
			'um' => array(				// update manager
				'min_version' => '1.6.2',	// minimum update manager version (hard limit)
				'check_hours' => array(
					24 => 'Every day',
					48 => 'Every two days',
					72 => 'Every three days',
					96 => 'Every four days',
					120 => 'Every five days',
					144 => 'Every six days',
					168 => 'Every week',
					336 => 'Every two weeks',
					504 => 'Every three weeks',
					720 => 'Every month',
				),
				'version_filter' => array(
					'dev' => 'Development and Up',
					'alpha' => 'Alpha and Up',
					'beta' => 'Beta and Up',
					'rc' => 'Release Candidate and Up',
					'stable' => 'Stable / Production',
				),
				'version_regex' => array(
					'dev' => '/^[0-9][0-9\.\-]+(dev|a|alpha|b|beta|rc)?[0-9\.\+]+$/',
					'alpha' => '/^[0-9][0-9\.\-]+(a|alpha|b|beta|rc)?[0-9\.\+]+$/',
					'beta' => '/^[0-9][0-9\.\-]+(b|beta|rc)?[0-9\.\+]+$/',
					'rc' => '/^[0-9][0-9\.\-]+(rc)?[0-9\.\+]+$/',
					'stable' => '/^[0-9][0-9\.\+\-]+$/',
				),
			),
			'wp' => array(				// wordpress
				'label' => 'WordPress',
				/*
				 * https://codex.wordpress.org/Supported_Versions
				 *
				 * The only current officially supported version is WordPress 4.7.1. Previous major
				 * releases from 3.7 onwards may or may not get security updates as serious exploits
				 * are discovered.
				 */
				'min_version' => '3.7',		// hard limit - deactivate the plugin when activating
				'rec_version' => '4.7',		// soft limit - issue warning if lower version found
				'version_url' => 'https://codex.wordpress.org/Supported_Versions?nocache=1',
				'tb_iframe' => array(		// thickbox iframe
					'width' => 772,		// url query argument
					'height' => 550,	// url query argument
				),
				'cm_names' => array(
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
				'label' => 'PHP',
				'min_version' => '5.3',		// hard limit - deactivate the plugin when activating
				'rec_version' => '5.6',		// soft limit - issue warning if lower version found
				'version_url' => 'http://php.net/supported-versions.php',
				'extensions' => array(
					'curl' => 'Client URL Library (cURL)',
					'json' => 'JavaScript Object Notation (JSON)',
					'mbstring' => 'Multibyte String',
					'simplexml' => 'SimpleXML',
				),
			),
			'menu' => array(
				'label' => 'SSO',		// menu item label
				'color' => '33cc33',		// menu item color (lime green)
			),
			'edit' => array(
				'columns' => array(
					'schema_type' => array(
						'header' => 'SSO Schema',
						'meta_key' => '_wpsso_head_info_schema_type',
						'orderby' => 'meta_value',
						'width' => '130px',	// 120 + 10 for the sorting arrow
						'height' => 'auto',
					),
					'og_img' => array(
						'header' => 'SSO Img',
						'meta_key' => '_wpsso_head_info_og_img_thumb',
						'orderby' => false,	// do not offer column sorting
						'width' => '70px',
						'height' => '37px',
					),
					'og_desc' => array(
						'header' => 'SSO Desc',
						'meta_key' => '_wpsso_head_info_og_desc',
						'orderby' => false,	// do not offer column sorting
						'width' => '12%',
						'height' => 'auto',
					),
				),
			),
			'form' => array(
				'max_hashtags' => 10,
				'max_media_items' => 20,
				'tooltip_class' => 'sucom_tooltip',
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
				'time_by_name' => array(	// in seconds
					'hour' => HOUR_IN_SECONDS,
					'day' => DAY_IN_SECONDS,
					'week' => WEEK_IN_SECONDS,	// 7 days
					'month' => MONTH_IN_SECONDS,	// 30 days
					'year' => YEAR_IN_SECONDS,	// 365 days
				),
				'cache_hrs' => array(
					0 => 0,
					3600 => 1,	// 1 hour
					7200 => 2,	// 2 hours
					10800 => 3,	// 3 hours
					21600 => 6,	// 6 hours
					32400 => 9,	// 9 hours
					43200 => 12,	// 12 hours
					86400 => 24,	// 1 day
					129600 => 36,	// 1.5 days
					172800 => 48,	// 2 days
					259200 => 72,	// 3 days
					604800 => 168,	// 7 days
					1209600 => 336,	// 14 days
					2419200 => 672,	// 28 days
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
					'myspace_publisher_url' => 'Myspace Business Page URL',
					'p_publisher_url' => 'Pinterest Company Page URL',
					'sc_publisher_url' => 'SoundCloud Business URL',
					'seo_publisher_url' => 'Google+ Business Page URL',
					'tc_site' => 'Twitter Business @username',
					'tumblr_publisher_url' => 'Tumblr Business Page URL',
					'yt_publisher_url' => 'YouTube Business Channel URL',
				),
				// https://schema.org/ItemAvailability
				'item_availability' => array(
					'none' => '[None]',
			 		'Discontinued' => 'Discontinued',
			 		'InStock' => 'In Stock',
			 		'InStoreOnly' => 'In Store Only',
			 		'LimitedAvailability' => 'Limited Availability',
			 		'OnlineOnly' => 'Online Only',
			 		'OutOfStock' => 'Out of Stock',
			 		'PreOrder' => 'Pre-Order',
			 		'SoldOut ' => 'Sold Out',
				),
				// https://schema.org/OfferItemCondition
				'item_condition' => array(
					'none' => '[None]',
					'DamagedCondition' => 'Damaged',
					'NewCondition' => 'New',
					'RefurbishedCondition' => 'Refurbished',
					'UsedCondition' => 'Used',
				),
				'cf_labels' => array(		// custom field option labels
					'plugin_cf_img_url' => 'Image URL Custom Field',
					'plugin_cf_vid_url' => 'Video URL Custom Field',
					'plugin_cf_vid_embed' => 'Video Embed HTML Custom Field',
					'plugin_cf_recipe_ingredients' => 'Recipe Ingredients Custom Field',
					'plugin_cf_recipe_instructions' => 'Recipe Instructions Custom Field',
					'plugin_cf_product_avail' => 'Product Availability Custom Field',
					'plugin_cf_product_condition' => 'Product Condition Custom Field',
					'plugin_cf_product_price' => 'Product Price Custom Field',
					'plugin_cf_product_currency' => 'Product Currency Custom Field',
				),
			),
			'head' => array(
				'limit_min' => array(
					'og_desc_len' => 156,
					'og_img_width' => 200,			// https://developers.facebook.com/docs/sharing/best-practices
					'og_img_height' => 200,
					'schema_img_width' => 400,		// https://developers.google.com/+/web/snippet/article-rendering
					'schema_img_height' => 160,
					'schema_article_img_width' => 696,	// https://developers.google.com/search/docs/data-types/articles
					'schema_article_img_height' => 279,	// based on the max image ratio
				),
				'limit_max' => array(
					'og_img_ratio' => 3,
					'schema_img_ratio' => 2.5,		// https://developers.google.com/+/web/snippet/article-rendering
					'schema_article_img_ratio' => 2.5,
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
				// https://developers.facebook.com/docs/reference/opengraph/
				'og_type_mt' => array(
					// https://developers.facebook.com/docs/reference/opengraph/object-type/article/
					'article' => array(
						'article:author' => '',
						'article:publisher' => '',
						'article:published_time' => '',
						'article:modified_time' => '',
						'article:expiration_time' => '',
						'article:section' => '',
						'article:tag' => '',
					),
					'book' => array(
						'book:author' => '',
						'book:isbn' => '',
						'book:release_date' => '',
						'book:tag' => '',
					),
					'music.album' => array(
						'music:song' => '',
						'music:song:disc' => '',
						'music:song:track' => '',
						'music:musician' => '',
						'music:release_date' => '',
					),
					'music.playlist' => array(
						'music:creator' => '',
						'music:song' => '',
						'music:song:disc' => '',
						'music:song:track' => '',
					),
					'music.radio_station' => array(
						'music:creator' => '',
					),
					'music.song' => array(
						'music:album' => '',
						'music:album:disc' => '',
						'music:album:track' => '',
						'music:duration' => '',
						'music:musician' => '',
					),
					'place' => array(
						'place:location:latitude' => '',
						'place:location:longitude' => '',
						'place:location:altitude' => '',
						'place:street_address' => '',
						'place:locality' => '',
						'place:region' => '',
						'place:postal_code' => '',
						'place:country_name' => '',
					),
					// https://developers.facebook.com/docs/reference/opengraph/object-type/product/
					'product' => array(
						'product:availability' => 'product_avail',
						'product:condition' => 'product_condition',
						'product:price:amount' => 'product_price',
						'product:price:currency' => 'product_currency',
						'product:weight:value' => '',
						'product:weight:units' => '',
					),
					'profile' => array(
						'profile:first_name' => '',
						'profile:last_name' => '',
						'profile:username' => '',
						'profile:gender' => '',
					),
					'video.episode' => array(
						'video:actor' => '',
						'video:actor:role' => '',
						'video:director' => '',
						'video:writer' => '',
						'video:duration' => '',
						'video:release_date' => '',
						'video:tag' => '',
						'video:series' => '',
					),
					'video.movie' => array(
						'video:actor' => '',
						'video:actor:role' => '',
						'video:director' => '',
						'video:writer' => '',
						'video:duration' => '',
						'video:release_date' => '',
						'video:tag' => '',
					),
					'video.other' => array(
						'video:actor' => '',
						'video:actor:role' => '',
						'video:director' => '',
						'video:writer' => '',
						'video:duration' => '',
						'video:release_date' => '',
						'video:tag' => '',
					),
					'video.tv_show' => array(
						'video:actor' => '',
						'video:actor:role' => '',
						'video:director' => '',
						'video:writer' => '',
						'video:duration' => '',
						'video:release_date' => '',
						'video:tag' => '',
					),
				),
				'og_content_map' => array(
					'product:availability' => array(	// 'instock', 'oos', or 'pending'
				 		'Discontinued' => 'oos',
				 		'InStock' => 'instock',
				 		'InStoreOnly' => 'instock',
				 		'LimitedAvailability' => 'instock',
				 		'OnlineOnly' => 'instock',
				 		'OutOfStock' => 'oos',
				 		'PreOrder' => 'pending',
				 		'SoldOut ' => 'oos',
					),
					'product:condition' => array(		// 'new', 'refurbished', or 'used'
						'DamagedCondition' => 'used',
						'NewCondition' => 'new',
						'RefurbishedCondition' => 'refurbished',
						'UsedCondition' => 'used',
					),
				),
				/*
				 * WpssoSchema::get_schema_types_array() flattens the array, so AVOID DUPLICATE KEY NAMES.
				 *
				 * https URLs are preferred - for more info, see https://schema.org/docs/faq.html#19:
				 *
				 * Q: Should we write 'https://schema.org' or 'http://schema.org' in our markup?
				 * A: There is a general trend towards using 'https' more widely, and you can
				 *    already write 'https://schema.org' in your structured data. Over time we will
				 *    migrate the schema.org site itself towards using https: as the default
				 *    version of the site and our preferred form in examples.
				 */
				'schema_type' => array(
					'thing' => array(	// parent of all schema types
						'creative.work' => array(
							'article' => array(
								'article' => 'https://schema.org/Article',
								'article.news' => 'https://schema.org/NewsArticle',
								'article.tech' => 'https://schema.org/TechArticle',
								'article.scholarly' => 'https://schema.org/ScholarlyArticle',
								'blog.posting' => 'https://schema.org/BlogPosting',
								'report' => 'https://schema.org/Report',
								'social.media.posting' => 'https://schema.org/SocialMediaPosting',
							),
							'book' => 'https://schema.org/Book',
							'blog' => 'https://schema.org/Blog',
							'creative.work' => 'https://schema.org/CreativeWork',
							'game' => 'https://schema.org/Game',
							'movie' => 'https://schema.org/Movie',
							'painting' => 'https://schema.org/Painting',
							'photograph' => 'https://schema.org/Photograph',
							'recipe' => 'https://schema.org/Recipe',
							'review' => 'https://schema.org/Review',
							'sculpture' => 'https://schema.org/Sculpture',
							'software.application' => 'https://schema.org/SoftwareApplication',
							'visual.artwork' => 'https://schema.org/VisualArtwork',
							'webpage' => array(
								'webpage' => 'https://schema.org/WebPage',
								'webpage.about' => 'https://schema.org/AboutPage',
								'webpage.checkout' => 'https://schema.org/CheckoutPage',
								'webpage.collection' => 'https://schema.org/CollectionPage',
								'webpage.contact' => 'https://schema.org/ContactPage',
								'webpage.item' => 'https://schema.org/ItemPage',
								'webpage.profile' => 'https://schema.org/ProfilePage',
								'webpage.qa' => 'https://schema.org/QAPage',
								'webpage.search.results' => 'https://schema.org/SearchResultsPage',
							),
							'website' => 'https://schema.org/WebSite',
						),
						'event' => array(
							'event' => 'https://schema.org/Event',
							'event.business' => 'https://schema.org/BusinessEvent',
							'event.childrens' => 'https://schema.org/ChildrensEvent',
							'event.comedy' => 'https://schema.org/ComedyEvent',
							'event.dance' => 'https://schema.org/DanceEvent',
							'event.delivery' => 'https://schema.org/DeliveryEvent',
							'event.education' => 'https://schema.org/EducationEvent',
							'event.exhibition' => 'https://schema.org/ExhibitionEvent',
							'event.festival' => 'https://schema.org/Festival',
							'event.food' => 'https://schema.org/FoodEvent',
							'event.literary' => 'https://schema.org/LiteraryEvent',
							'event.music' => 'https://schema.org/MusicEvent',
							'event.publication' => 'https://schema.org/PublicationEvent',
							'event.sale' => 'https://schema.org/SaleEvent',
							'event.screening' => 'https://schema.org/ScreeningEvent',
							'event.social' => 'https://schema.org/SocialEvent',
							'event.sports' => 'https://schema.org/SportsEvent',
							'event.theater' => 'https://schema.org/TheaterEvent',
							'event.visual.arts' => 'https://schema.org/VisualArtsEvent',
						),
						'organization' => array(
							'airline' => 'https://schema.org/Airline',
							'corporation' => 'https://schema.org/Corporation',
							'educational.organization' => array(
								'college.or.university' => 'https://schema.org/CollegeOrUniversity',
								'educational.organization' => 'https://schema.org/EducationalOrganization',
								'elementary.school' => 'https://schema.org/ElementarySchool',
								'high.school' => 'https://schema.org/HighSchool',
								'middle.school' => 'https://schema.org/MiddleSchool',
								'preschool' => 'https://schema.org/Preschool',
								'school' => 'https://schema.org/School',
							),
							'government.organization' => 'https://schema.org/GovernmentOrganization',
							'medical.organization' => array(
								'medical.organization' => 'https://schema.org/MedicalOrganization',
								'pharmacy' => 'https://schema.org/Pharmacy',
								'physician' => 'https://schema.org/Physician',
							),
							'non-governmental.organization' => 'https://schema.org/NGO',
							'organization' => 'https://schema.org/Organization',
							'performing.group' => array(
								'dance.group' => 'https://schema.org/DanceGroup',
								'music.group' => 'https://schema.org/MusicGroup',
								'performing.group' => 'https://schema.org/PerformingGroup',
								'theater.group' => 'https://schema.org/TheaterGroup',
							),
							'sports.organization' => array(
								'sports.team' => 'https://schema.org/SportsTeam',
								'sports.organization' => 'https://schema.org/SportsOrganization',
							),
						),
						'person' => 'https://schema.org/Person',
						'place' => array(
							'administrative.area' => 'https://schema.org/AdministrativeArea',
							'civic.structure' => array(
								'airport' => 'https://schema.org/Airport',
								'aquarium' => 'https://schema.org/Aquarium',
								'beach' => 'https://schema.org/Beach',
								'bridge' => 'https://schema.org/Bridge',
								'bus.station' => 'https://schema.org/BusStation',
								'bus.stop' => 'https://schema.org/BusStop',
								'cemetary' => 'https://schema.org/Cemetery',
								'civic.structure' => 'https://schema.org/CivicStructure',
								'crematorium' => 'https://schema.org/Crematorium',
								'event.venu' => 'https://schema.org/EventVenue',
								'government.building' => 'https://schema.org/GovernmentBuilding',
								'museum' => 'https://schema.org/Museum',
								'music.venu' => 'https://schema.org/MusicVenue',
								'park' => 'https://schema.org/Park',
								'parking.facility' => 'https://schema.org/ParkingFacility',
								'performing.arts.theatre' => 'https://schema.org/PerformingArtsTheater',
								'place.of.worship' => 'https://schema.org/PlaceOfWorship',
								'playground' => 'https://schema.org/Playground',
								'rv.park' => 'https://schema.org/RVPark',
								'subway.station' => 'https://schema.org/SubwayStation',
								'taxi.stand' => 'https://schema.org/TaxiStand',
								'train.station' => 'https://schema.org/TrainStation',
								'zoo' => 'https://schema.org/Zoo',
							),
							'landform' => 'https://schema.org/Landform',
							'landmarks.or.historical.buildings' => 'https://schema.org/LandmarksOrHistoricalBuildings',
							'local.business' => array(
								'animal.shelter' => 'https://schema.org/AnimalShelter',
								'automotive.business' => array(
									'auto.body.shop' => 'https://schema.org/AutoBodyShop',
									'auto.dealer' => 'https://schema.org/AutoDealer',
									'auto.parts.store' => 'https://schema.org/AutoPartsStore',
									'auto.rental' => 'https://schema.org/AutoRental',
									'auto.repair' => 'https://schema.org/AutoRepair',
									'auto.wash' => 'https://schema.org/AutoWash',
									'automotive.business' => 'https://schema.org/AutomotiveBusiness',
									'gas.station' => 'https://schema.org/GasStation',
									'motorcycle.dealer' => 'https://schema.org/MotorcycleDealer',
									'motorcycle.repair' => 'https://schema.org/MotorcycleRepair ',
								),
								'child.care' => 'https://schema.org/ChildCare',
								'dentist' => 'https://schema.org/Dentist',
								'dry.cleaning.or.laundry' => 'https://schema.org/DryCleaningOrLaundry',
								'emergency.service' => array(
									'emergency.service' => 'https://schema.org/EmergencyService',
									'fire.station' => 'https://schema.org/FireStation',
									'hospital' => 'https://schema.org/Hospital',
									'police.station' => 'https://schema.org/PoliceStation',
								),
								'employement.agency' => 'https://schema.org/EmploymentAgency',
								'entertainment.business' => array(
									'entertainment.business' => 'https://schema.org/EntertainmentBusiness',
									'movie.theatre' => 'https://schema.org/MovieTheatre',
								),
								'financial.service' => 'https://schema.org/FinancialService',
								'food.establishment' => array(
									'bakery' => 'https://schema.org/Bakery',
									'bar.or.pub' => 'https://schema.org/BarOrPub',
									'brewery' => 'https://schema.org/Brewery',
									'cafe.or.coffee.shop' => 'https://schema.org/CafeOrCoffeeShop',
									'fast.food.restaurant' => 'https://schema.org/FastFoodRestaurant',
									'food.establishment' => 'https://schema.org/FoodEstablishment',
									'ice.cream.shop' => 'https://schema.org/IceCreamShop',
									'restaurant' => 'https://schema.org/Restaurant',
									'winery' => 'https://schema.org/Winery',
								),
								'government.office' => 'https://schema.org/GovernmentOffice',
								'health.and.beauty.business' => 'https://schema.org/HealthAndBeautyBusiness',
								'home.and.construction.business' => array(
									'electrician' => 'https://schema.org/Electrician',
									'general.contractor' => 'https://schema.org/GeneralContractor',
									'hvac.business' => 'https://schema.org/HVACBusiness',
									'home.and.construction.business' => 'https://schema.org/HomeAndConstructionBusiness',
									'house.painter' => 'https://schema.org/HousePainter',
									'locksmith' => 'https://schema.org/Locksmith',
									'moving.company' => 'https://schema.org/MovingCompany',
									'plumber' => 'https://schema.org/Plumber',
									'roofing.contractor' => 'https://schema.org/RoofingContractor',
								),
								'internet.cafe' => 'https://schema.org/InternetCafe',
								'legal.service' => array(
									'attorney' => 'https://schema.org/Attorney',
									'legal.service' => 'https://schema.org/LegalService',
									'notary' => 'https://schema.org/Notary',
								),
								'library' => 'https://schema.org/Library',
								'local.business' => 'https://schema.org/LocalBusiness',
								'lodging.business' => array(
									'campground' => 'https://schema.org/Campground',
									'lodging.business' => 'https://schema.org/LodgingBusiness',
								),
								'professional.service' => 'https://schema.org/ProfessionalService',
								'radio.station' => 'https://schema.org/RadioStation',
								'real.estate.agent' => 'https://schema.org/RealEstateAgent',
								'recycling.center' => 'https://schema.org/RecyclingCenter',
								'self.storage' => 'https://schema.org/SelfStorage',
								'shopping.center' => 'https://schema.org/ShoppingCenter',
								'sports.activity.location' => array(
									'sports.activity.location' => 'https://schema.org/SportsActivityLocation',
									'stadium.or.arena' => 'https://schema.org/StadiumOrArena',
								),
								'store' => array(
									'auto.parts.store' => 'https://schema.org/AutoPartsStore',
									'bike.store' => 'https://schema.org/BikeStore',
									'book.store' => 'https://schema.org/BookStore',
									'clothing.store' => 'https://schema.org/ClothingStore',
									'computer.store' => 'https://schema.org/ComputerStore',
									'convenience.store' => 'https://schema.org/ConvenienceStore',
									'department.store' => 'https://schema.org/DepartmentStore',
									'electronics.store' => 'https://schema.org/ElectronicsStore',
									'florist' => 'https://schema.org/Florist',
									'flower.shop' => 'https://schema.org/Florist',
									'furniture.store' => 'https://schema.org/FurnitureStore',
									'garden.store' => 'https://schema.org/GardenStore',
									'grocery.store' => 'https://schema.org/GroceryStore',
									'hardware.store' => 'https://schema.org/HardwareStore',
									'hobby.shop' => 'https://schema.org/HobbyShop',
									'home.goods.store' => 'https://schema.org/HomeGoodsStore',
									'jewelry.store' => 'https://schema.org/JewelryStore',
									'liquor.store' => 'https://schema.org/LiquorStore',
									'mens.clothing.store' => 'https://schema.org/MensClothingStore',
									'mobile.phone.store' => 'https://schema.org/MobilePhoneStore',
									'movie.rental.store' => 'https://schema.org/MovieRentalStore',
									'music.store' => 'https://schema.org/MusicStore',
									'office.equipment.store' => 'https://schema.org/OfficeEquipmentStore',
									'outlet.store' => 'https://schema.org/OutletStore',
									'pawn.shop' => 'https://schema.org/PawnShop',
									'pet.store' => 'https://schema.org/PetStore',
									'shoe.store' => 'https://schema.org/ShoeStore',
									'sporting.goods.store' => 'https://schema.org/SportingGoodsStore',
									'store' => 'https://schema.org/Store',
									'tire.shop' => 'https://schema.org/TireShop',
									'toy.store' => 'https://schema.org/ToyStore',
									'wholesale.store' => 'https://schema.org/WholesaleStore',
								),
								'television.station' => 'https://schema.org/TelevisionStation',
								'tourist.information.center' => 'https://schema.org/TouristInformationCenter',
								'travel.agency' => 'https://schema.org/TravelAgency',
							),
							'place' => 'https://schema.org/Place',
							'residence' => 'https://schema.org/Residence',
							'tourist.attraction' => 'https://schema.org/TouristAttraction',
						),
						'product' => array(
							'product' => 'https://schema.org/Product',
							'vehicle' => array(
								'bus.or.coach' => 'https://auto.schema.org/BusOrCoach',
								'car' => 'https://auto.schema.org/Car',
								'motorcycle' => 'https://auto.schema.org/Motorcycle',
								'motorized.bicycle' => 'https://auto.schema.org/MotorizedBicycle',
								'vehicle' => 'https://auto.schema.org/Vehicle',
							),
						),
						'thing' => 'https://schema.org/Thing',
					),
				),
			),
			'follow' => array(
				'size' => 32,
				'src' => array(
					'images/follow/Wordpress.png' => 'https://profiles.wordpress.org/jsmoriss/',
					'images/follow/Github.png' => 'https://github.com/SurniaUlula',
					'images/follow/Facebook.png' => 'https://www.facebook.com/SurniaUlulaCom',
					'images/follow/GooglePlus.png' => 'https://plus.google.com/+SurniaUlula/',
					'images/follow/Twitter.png' => 'https://twitter.com/surniaululacom',
					'images/follow/Rss.png' => 'https://wpsso.com/category/application/wordpress/wp-plugins/wpsso/feed/',
				),
			),
		);

		public static function get_version() {
			return self::$cf['plugin']['wpsso']['version'];
		}

		// get_config is called very early, so don't apply filters unless instructed
		public static function get_config( $idx = false, $filter_cf = false ) {

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

				if ( $filter_cf ) {

					self::$cf = apply_filters( self::$cf['lca'].'_get_config', self::$cf, self::get_version() );

					self::$cf['config_filtered'] = true;

					foreach ( self::$cf['plugin'] as $ext => $info ) {

						if ( defined( strtoupper( $ext ).'_PLUGINDIR' ) ) {
							$pkg_lctype = is_dir( constant( strtoupper( $ext ).
								'_PLUGINDIR' ).'lib/pro/' ) ? 'pro' : 'gpl';
						} else {
							$pkg_lctype = '';
						}

						if ( isset( $info['slug'] ) ) {
							self::$cf['*']['slug'][$info['slug']] = $ext;
						}

						if ( isset( $info['base'] ) ) {
							self::$cf['*']['base'][$info['base']] = $ext;
						}

						if ( isset( $info['lib'] ) && is_array( $info['lib'] ) ) {
							self::$cf['*']['lib'] = SucomUtil::array_merge_recursive_distinct(
								self::$cf['*']['lib'], $info['lib'] );
						}

						if ( isset( $info['version'] ) ) {
							self::$cf['*']['version'] .= '-'.$ext.$info['version'].$pkg_lctype;
						}

						if ( isset( $info['opt_version'] ) ) {
							self::$cf['opt']['version'] .= '-'.$ext.$info['opt_version'].$pkg_lctype;
						}

						// complete relative paths in the image arrays
						$plugin_base = trailingslashit( plugins_url( '', $info['base'] ) );
						array_walk_recursive( self::$cf['plugin'][$ext]['img'], 
							array( __CLASS__, 'maybe_prefix_base_url' ), $plugin_base );
					}
				}
			}

			if ( $idx !== false ) {
				if ( isset( self::$cf[$idx] ) ) {
					return self::$cf[$idx];
				} else {
					return null;
				}
			} else {
				return self::$cf;
			}
		}

		private static function maybe_prefix_base_url( &$url, $key, $plugin_base ) {
			if ( ! empty( $url ) && strpos( $url, '//' ) === false ) {
				$url = $plugin_base.$url;
			}
		}

		/*
		 * Sort the 'plugin' array by each extension's 'name' value.
		 */
		public static function get_ext_sorted( $filter_cf = false ) {
			$ext = self::get_config( 'plugin', $filter_cf );
			uasort( $ext, array( 'self', 'sort_ext_by_name' ) );	// sort array and maintain index association
			return $ext;
		}

		private static function sort_ext_by_name( $a, $b ) {
			if ( isset( $a['name'] ) && isset( $b['name'] ) )	// just in case
				return strcasecmp( $a['name'], $b['name'] );	// case-insensitive string comparison
			else return 0;						// no change
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

		public static function set_variable_constants( $var_const = null ) {
			if ( $var_const === null ) {
				$var_const = self::get_variable_constants();
			}
			foreach ( $var_const as $name => $value ) {
				if ( ! defined( $name ) ) {
					define( $name, $value );
				}
			}
		}

		public static function get_variable_constants() {
			$var_const = array();

			if ( defined( 'WPSSO_PLUGINDIR' ) ) {
				$var_const['WPSSO_CACHEDIR'] = WPSSO_PLUGINDIR.'cache/';
				$var_const['WPSSO_TOPICS_LIST'] = WPSSO_PLUGINDIR.'share/topics.txt';
			}

			if ( defined( 'WPSSO_URLPATH' ) ) {
				$var_const['WPSSO_CACHEURL'] = WPSSO_URLPATH.'cache/';
			}

			$var_const['WPSSO_DEBUG_FILE_EXP'] = 300;
			$var_const['WPSSO_MENU_ORDER'] = '99.10';		// position of the SSO menu item
			$var_const['WPSSO_MENU_ICON_HIGHLIGHT'] = true;		// highlight the SSO menu icon
			$var_const['WPSSO_HIDE_ALL_ERRORS'] = false;		// auto-hide all error notices
			$var_const['WPSSO_HIDE_ALL_WARNINGS'] = false;		// auto-hide all warning notices
			$var_const['WPSSO_JSON_PRETTY_PRINT'] = true;		// output pretty / human readable json
			$var_const['WPSSO_PROD_CURRENCY'] = 'USD';		// default for 'product_currency'
			$var_const['WPSSO_UNDEF_INT'] = -1;			// undefined width / height value

			/*
			 * WPSSO option and meta array names
			 */
			$var_const['WPSSO_TS_NAME'] = 'wpsso_timestamps';
			$var_const['WPSSO_OPTIONS_NAME'] = 'wpsso_options';
			$var_const['WPSSO_SITE_OPTIONS_NAME'] = 'wpsso_site_options';
			$var_const['WPSSO_NOTICE_NAME'] = 'wpsso_notices';		// stored notices
			$var_const['WPSSO_DISMISS_NAME'] = 'wpsso_dismissed';		// dismissed notices
			$var_const['WPSSO_META_NAME'] = '_wpsso_meta';			// post meta
			$var_const['WPSSO_PREF_NAME'] = '_wpsso_pref';			// user meta
			$var_const['WPSSO_POST_CHECK_NAME'] = 'wpsso_post_head_count';	// duplicate check counter

			/*
			 * WPSSO option and meta array alternate / fallback names
			 */
			$var_const['WPSSO_OPTIONS_NAME_ALT'] = 'ngfb_options';			// fallback name
			$var_const['WPSSO_SITE_OPTIONS_NAME_ALT'] = 'ngfb_site_options';	// fallback name
			$var_const['WPSSO_META_NAME_ALT'] = '_ngfb_meta';			// fallback name
			$var_const['WPSSO_PREF_NAME_ALT'] = '_ngfb_pref';			// fallback name

			/*
			 * WPSSO hook priorities
			 */
			$var_const['WPSSO_ADD_MENU_PRIORITY'] = -20;
			$var_const['WPSSO_ADD_SUBMENU_PRIORITY'] = -10;
			$var_const['WPSSO_ADD_COLUMN_PRIORITY'] = 100;
			$var_const['WPSSO_META_SAVE_PRIORITY'] = 5;
			$var_const['WPSSO_META_CACHE_PRIORITY'] = 10;
			$var_const['WPSSO_INIT_PRIORITY'] = 12;
			$var_const['WPSSO_HEAD_PRIORITY'] = 10;
			$var_const['WPSSO_FOOTER_PRIORITY'] = 100;
			$var_const['WPSSO_SEO_FILTERS_PRIORITY'] = 100;

			/*
			 * WPSSO cURL settings
			 */
			if ( defined( 'WPSSO_PLUGINDIR' ) ) {
				$var_const['WPSSO_PHP_CURL_CAINFO'] = ABSPATH.WPINC.'/certificates/ca-bundle.crt';
			}
			$var_const['WPSSO_PHP_CURL_USERAGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:47.0) Gecko/20100101 Firefox/47.0';

			foreach ( $var_const as $name => $value ) {
				if ( defined( $name ) ) {
					$var_const[$name] = constant( $name );	// inherit existing values
				}
			}

			return $var_const;
		}

		public static function require_libs( $plugin_filepath ) {

			require_once WPSSO_PLUGINDIR.'lib/com/cache.php';
			require_once WPSSO_PLUGINDIR.'lib/com/nodebug.php';	// always load fallback class
			require_once WPSSO_PLUGINDIR.'lib/com/nonotice.php';	// always load fallback class
			require_once WPSSO_PLUGINDIR.'lib/com/util.php';

			require_once WPSSO_PLUGINDIR.'lib/check.php';
			require_once WPSSO_PLUGINDIR.'lib/exception.php';	// extends exception
			require_once WPSSO_PLUGINDIR.'lib/filters.php';
			require_once WPSSO_PLUGINDIR.'lib/functions.php';
			require_once WPSSO_PLUGINDIR.'lib/head.php';
			require_once WPSSO_PLUGINDIR.'lib/media.php';
			require_once WPSSO_PLUGINDIR.'lib/meta.php';
			require_once WPSSO_PLUGINDIR.'lib/opengraph.php';
			require_once WPSSO_PLUGINDIR.'lib/options.php';
			require_once WPSSO_PLUGINDIR.'lib/page.php';
			require_once WPSSO_PLUGINDIR.'lib/post.php';		// extends meta.php
			require_once WPSSO_PLUGINDIR.'lib/register.php';
			require_once WPSSO_PLUGINDIR.'lib/schema.php';
			require_once WPSSO_PLUGINDIR.'lib/script.php';
			require_once WPSSO_PLUGINDIR.'lib/style.php';
			require_once WPSSO_PLUGINDIR.'lib/term.php';		// extends meta.php
			require_once WPSSO_PLUGINDIR.'lib/twittercard.php';
			require_once WPSSO_PLUGINDIR.'lib/user.php';		// extends meta.php
			require_once WPSSO_PLUGINDIR.'lib/util.php';
			require_once WPSSO_PLUGINDIR.'lib/weibo.php';


			if ( is_admin() ) {
				require_once WPSSO_PLUGINDIR.'lib/messages.php';
				require_once WPSSO_PLUGINDIR.'lib/admin.php';
				require_once WPSSO_PLUGINDIR.'lib/com/form.php';
				require_once WPSSO_PLUGINDIR.'lib/ext/parse-readme.php';
			}

			if ( file_exists( WPSSO_PLUGINDIR.'lib/loader.php' ) ) {
				require_once WPSSO_PLUGINDIR.'lib/loader.php';
			}

			add_filter( 'wpsso_load_lib', array( 'WpssoConfig', 'load_lib' ), 10, 3 );
		}

		public static function load_lib( $ret = false, $filespec = '', $classname = '' ) {
			if ( $ret === false && ! empty( $filespec ) ) {
				$filepath = WPSSO_PLUGINDIR.'lib/'.$filespec.'.php';
				if ( file_exists( $filepath ) ) {
					require_once $filepath;
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
