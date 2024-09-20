<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoConfig' ) ) {

	class WpssoConfig {

		public static $cf = array(
			'packages' => array(
				'pro' => 'Premium',
				'std' => 'Standard',
			),
			'plugin' => array(
				'wpsso' => array(			// Plugin acronym.
					'version'     => '18.10-dev.1',	// Plugin version.
					'opt_version' => '1021',	// Increment when changing default option values.
					'short'       => 'WPSSO Core',	// Short plugin name.
					'name'        => 'WPSSO Core',
					'desc'        => 'Present your content at its best for social sites and search results, no matter how URLs are shared, reshared, messaged, posted, embedded, or crawled.',
					'slug'        => 'wpsso',
					'base'        => 'wpsso/wpsso.php',
					'update_auth' => 'tid',
					'text_domain' => 'wpsso',
					'domain_path' => '/languages',
					'admin_l10n'  => 'wpssoAdminPageL10n',

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'images/icon-128x128.png',
							'2x' => 'images/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso',
						'review' => 'https://wordpress.org/support/plugin/wpsso/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'debug_html' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso/master/html/debug.html',
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso/master/readme.txt',
						'setup_html' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso/master/html/setup.html',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso/installation/',
						'faqs'      => 'https://wpsso.com/docs/plugins/wpsso/faqs/',
						'notes'     => 'https://wpsso.com/docs/plugins/wpsso/notes/',
						'support'   => 'https://surniaulula.com/support/create_ticket/',	// Premium support ticket.
						'purchase'  => 'https://wpsso.com/extend/plugins/wpsso/',		// Purchase page.
						'info'      => 'https://wpsso.com/extend/plugins/wpsso/info/',		// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso/update/',
						'download'  => '',
					),
					'lib' => array(
						'dashboard' => array(
						),
						'integ' => array(
							'data' => array(
								'aioseop-meta'      => 'Import All in One SEO Pack Metadata',
								'rankmath-meta'     => 'Import Rank Math SEO Metadata',
								'seoframework-meta' => 'Import The SEO Framework Metadata',
								'wpmetaseo-meta'    => 'Import WP Meta SEO Metadata',
								'wpseo-blocks'      => 'Import Yoast SEO Block Attrs',
								'wpseo-meta'        => 'Import Yoast SEO Metadata',
							),
							'ecom' => array(
								'perfect-woocommerce-brands'    => 'Perfect Brands for WooCommerce',
								'woocommerce'                   => 'WooCommerce',
								'woocommerce-brands'            => 'WooCommerce Brands',
								'woocommerce-currency-switcher' => 'WooCommerce Currency Switcher',
								'woo-add-gtin'                  => 'WooCommerce UPC, EAN, and ISBN',
								'wpm-product-gtin-wc'           => 'Product GTIN for WooCommerce',
								'yith-woocommerce-brands'       => 'YITH WooCommerce Brands Add-on',
							),
							'event' => array(
								'the-events-calendar' => 'The Events Calendar',
							),
							'form' => array(
								'gravityforms' => 'Gravity Forms',
								'gravityview'  => 'Gravity View',
							),
							'job' => array(
								'simplejobboard' => 'Simple Job Board',
								'wpjobmanager'   => 'WP Job Manager',
							),
							'lang' => array(
								'polylang'      => 'Polylang',
								'qtranslate-xt' => 'qTranslate-XT',
								'wpml'          => 'WPML',
							),
							'media' => array(
								'wp-retina-2x' => 'Perfect Images',
							),
							'rating' => array(
								'rate-my-post'  => 'Rate my Post',
								'wppostratings' => 'WP-PostRatings',
							),
							'recipe' => array(
								'wprecipemaker' => 'WP Recipe Maker',
							),
							'review' => array(
								'judgeme-for-wc'  => 'Judge.me Product Reviews for WooCommerce',
								'wpproductreview' => 'WP Product Review',
								'yotpowc'         => 'Yotpo Social Reviews for WooCommerce',
							),
							'seo' => array(
								'aioseop'      => 'All in One SEO Pack',
								'rankmath'     => 'Rank Math SEO',
								'seoframework' => 'The SEO Framework',
								'seopress'     => 'SEOPress',
								'wpmetaseo'    => 'WP Meta SEO',
								'wpseo'        => 'Yoast SEO',
							),
							'user' => array(
								'coauthors'       => 'Co-Authors Plus',
								'ultimate-member' => 'Ultimate Member',
							),
							'util' => array(
								'elementor'     => 'Elementor Website Builder',
								'jetpack'       => 'Jetpack',
								'jetpack-boost' => 'Jetpack Boost',
							),
						),
						'json' => array(
							'type' => array(
								'article'             => 'Schema Type Article',
								'audiobook'           => 'Schema Type Audiobook',
								'blog'                => 'Schema Type Blog',
								'book'                => 'Schema Type Book',
								'brand'               => 'Schema Type Brand',
								'claimreview'         => 'Schema Type Claim Review',
								'collectionpage'      => 'Schema Type Collection Page',
								'course'              => 'Schema Type Course',
								'creativework'        => 'Schema Type CreativeWork',
								'event'               => 'Schema Type Event',
								'faqpage'             => 'Schema Type FAQPage',
								'foodestablishment'   => 'Schema Type Food Establishment',
								'howto'               => 'Schema Type How-To',
								'itemlist'            => 'Schema Type ItemList',
								'jobposting'          => 'Schema Type Job Posting',
								'learningresource'    => 'Schema Type Learning Resource',
								'localbusiness'       => 'Schema Type Local Business',
								'movie'               => 'Schema Type Movie',
								'organization'        => 'Schema Type Organization',
								'person'              => 'Schema Type Person',
								'place'               => 'Schema Type Place',
								'product'             => 'Schema Type Product',
								'productgroup'        => 'Schema Type Product Group',
								'profilepage'         => 'Schema Type Profile Page',
								'qapage'              => 'Schema Type QAPage',
								'question'            => 'Schema Type Question and Answer',
								'recipe'              => 'Schema Type Recipe',
								'review'              => 'Schema Type Review',
								'searchresultspage'   => 'Schema Type Search Results Page',
								'softwareapplication' => 'Schema Type Software Application',
								'thing'               => 'Schema Type Thing',
								'webpage'             => 'Schema Type WebPage',
								'website'             => 'Schema Type WebSite',
							),
							'prop' => array(
								'aggregaterating' => 'Schema Property aggregateRating',
								'haspart'         => 'Schema Property hasPart',
								'review'          => 'Schema Property review',
							),
						),
						'pro' => array(
							'admin' => array(
								'advanced' => 'Advanced Settings',
								'edit'     => 'Document SSO Metabox',
								'general'  => 'General Settings',
							),
							'media' => array(
								'facebook'         => 'Facebook Videos',
								'gravatar'         => 'Gravatar Images',
								'slideshare'       => 'Slideshare Presentations',
								'soundcloud'       => 'Soundcloud Tracks',
								'upscale'          => 'Upscale Media Library Images',
								'vimeo'            => 'Vimeo Videos',
								'wistia'           => 'Wistia Videos',
								'wpvideoblock'     => 'WP Media Library Video Blocks',
								'wpvideoshortcode' => 'WP Media Library Video Shortcodes',
								'youtube'          => 'YouTube Videos and Playlists',
							),
							'review' => array(
								'judgeme'         => 'Judge.me Reviews',
								'shopperapproved' => 'Shopper Approved Reviews',
								'stamped'         => 'Stamped.io Reviews',
							),
							'util' => array(
								'shorten' => 'URL Shortening Services',
							),
						),
						'profile' => array(
							'your-sso' => 'Profile SSO - Social and Search Optimization',
						),
						'sitesubmenu' => array(
							'site-advanced' => 'Advanced Settings',
							'site-licenses' => 'Premium Licenses',
							'site-addons'   => 'Plugin Add-ons',
							'site-setup'    => 'Setup Guide',
							'site-debug'    => 'Troubleshooting',
						),
						'std' => array(	// Standard distribution modules.
							'admin' => array(
								'advanced' => 'Advanced Settings',
								'edit'     => 'Document SSO Metabox',
								'general'  => 'General Settings',
							),
							'media' => array(
								'facebook'         => 'Facebook Videos',
								'slideshare'       => 'Slideshare Presentations',
								'soundcloud'       => 'Soundcloud Tracks',
								'vimeo'            => 'Vimeo Videos',
								'wistia'           => 'Wistia Videos',
								'wpvideoblock'     => 'WP Media Library Video Blocks',
								'wpvideoshortcode' => 'WP Media Library Video Shortcodes',
								'youtube'          => 'YouTube Videos and Playlists',
							),
							'review' => array(
								'judgeme' => 'Judge.me Reviews',
							),
						),
						'submenu' => array(
							'essential' => 'Essential Settings',
							'general'   => 'General Settings',
							'advanced'  => 'Advanced Settings',
							'licenses'  => 'Premium Licenses',
							'addons'    => 'Plugin Add-ons',
							'tools'     => 'Tools and Actions',
							'setup'     => 'Setup Guide',
							'debug'     => 'Troubleshooting',
						),
						'users' => array(
							'add-person' => 'Add Person',
						),
					),

					/*
					 * Declare compatibility with WooCommerce HPOS.
					 *
					 * See https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book.
					 */
					'wc_compat' => array(
						'custom_order_tables',
					),
				),
				'wpssoafs' => array(			// Plugin acronym.
					'short'       => 'WPSSO AFS',	// Short plugin name.
					'name'        => 'WPSSO Add Five Stars',
					'desc'        => 'Add a 5 star rating and review from the site organization if the Schema markup does not already have an \'aggregateRating\' property.',
					'slug'        => 'wpsso-add-five-stars',
					'base'        => 'wpsso-add-five-stars/wpsso-add-five-stars.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-add-five-stars/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-add-five-stars/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-add-five-stars/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-add-five-stars/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-add-five-stars/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-add-five-stars',
						'review' => 'https://wordpress.org/support/plugin/wpsso-add-five-stars/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-add-five-stars/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-add-five-stars/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-add-five-stars/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-add-five-stars/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-add-five-stars/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-add-five-stars/latest/',
					),
				),
				'wpssoam' => array(			// Plugin acronym.
					'short'       => 'WPSSO AM',	// Short plugin name.
					'name'        => 'WPSSO Mobile App Meta Tags',
					'desc'        => 'Apple Store and Google Play App meta tags for Apple\'s mobile Safari banner and X\'s (Twitter) App Card.',
					'slug'        => 'wpsso-am',
					'base'        => 'wpsso-am/wpsso-am.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-am/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-am/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-am/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-am/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-am/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-am',
						'review' => 'https://wordpress.org/support/plugin/wpsso-am/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-am/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-am/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-am/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-am/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-am/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-am/latest/',
					),
				),
				'wpssobc' => array(			// Plugin acronym.
					'short'       => 'WPSSO BC',	// Short plugin name.
					'name'        => 'WPSSO Schema Breadcrumbs Markup',
					'desc'        => 'Schema BreadcrumbList markup in JSON-LD format for Google Rich Results.',
					'slug'        => 'wpsso-breadcrumbs',
					'base'        => 'wpsso-breadcrumbs/wpsso-breadcrumbs.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-breadcrumbs/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-breadcrumbs/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-breadcrumbs/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-breadcrumbs/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-breadcrumbs/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-breadcrumbs',
						'review' => 'https://wordpress.org/support/plugin/wpsso-breadcrumbs/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-breadcrumbs/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-breadcrumbs/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-breadcrumbs/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-breadcrumbs/installation/',
						'faqs'      => 'https://wpsso.com/docs/plugins/wpsso-breadcrumbs/faqs/',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-breadcrumbs/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-breadcrumbs/latest/',
					),
				),
				'wpssocmcf' => array(			// Plugin acronym.
					'short'       => 'WPSSO CMCF',	// Short plugin name.
					'name'        => 'WPSSO Commerce Manager Catalog Feed XML',
					'desc'        => 'Facebook and Instagram Commerce Manager Catalog Feed XMLs for WooCommerce and custom product pages.',
					'slug'        => 'wpsso-commerce-manager-catalog-feed',
					'base'        => 'wpsso-commerce-manager-catalog-feed/wpsso-commerce-manager-catalog-feed.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-commerce-manager-catalog-feed/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-commerce-manager-catalog-feed/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-commerce-manager-catalog-feed/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-commerce-manager-catalog-feed/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-commerce-manager-catalog-feed/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-commerce-manager-catalog-feed',
						'review' => 'https://wordpress.org/support/plugin/wpsso-commerce-manager-catalog-feed/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-commerce-manager-catalog-feed/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-commerce-manager-catalog-feed/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-commerce-manager-catalog-feed/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-commerce-manager-catalog-feed/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-commerce-manager-catalog-feed/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-commerce-manager-catalog-feed/latest/',
					),
				),
				'wpssofaq' => array(			// Plugin acronym.
					'short'       => 'WPSSO FAQ',	// Short plugin name.
					'name'        => 'WPSSO FAQ Manager',
					'desc'        => 'Create FAQ and Question / Answer Pages with optional shortcodes to include FAQs and Questions / Answers in your content.',
					'slug'        => 'wpsso-faq',
					'base'        => 'wpsso-faq/wpsso-faq.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-faq/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-faq/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-faq/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-faq/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-faq/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-faq',
						'review' => 'https://wordpress.org/support/plugin/wpsso-faq/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-faq/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-faq/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-faq/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-faq/installation/',
						'faqs'      => 'https://wpsso.com/docs/plugins/wpsso-faq/faqs/',
						'notes'     => 'https://wpsso.com/docs/plugins/wpsso-faq/notes/',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-faq/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-faq/latest/',
					),
				),
				'wpssogmf' => array(			// Plugin acronym.
					'short'       => 'WPSSO GMF',	// Short plugin name.
					'name'        => 'WPSSO Google Merchant Feed XML',
					'desc'        => 'Google Merchant product and inventory feed XML for WooCommerce and custom product pages, including multilingual support.',
					'slug'        => 'wpsso-google-merchant-feed',
					'base'        => 'wpsso-google-merchant-feed/wpsso-google-merchant-feed.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-google-merchant-feed/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-google-merchant-feed/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-google-merchant-feed/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-google-merchant-feed/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-google-merchant-feed/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-google-merchant-feed',
						'review' => 'https://wordpress.org/support/plugin/wpsso-google-merchant-feed/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-google-merchant-feed/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-google-merchant-feed/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-google-merchant-feed/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-google-merchant-feed/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-google-merchant-feed/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-google-merchant-feed/latest/',
					),
				),
				'wpssomrp' => array(			// Plugin acronym.
					'short'       => 'WPSSO MRP',	// Short plugin name.
					'name'        => 'WPSSO Merchant Return Policy Manager',
					'desc'        => 'Manage any number of Merchant Return Policies for Google Merchant listings.',
					'slug'        => 'wpsso-merchant-return-policy',
					'base'        => 'wpsso-merchant-return-policy/wpsso-merchant-return-policy.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-merchant-return-policy/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-merchant-return-policy/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-merchant-return-policy/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-merchant-return-policy/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-merchant-return-policy/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-merchant-return-policy',
						'review' => 'https://wordpress.org/support/plugin/wpsso-merchant-return-policy/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-merchant-return-policy/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-merchant-return-policy/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-merchant-return-policy/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-merchant-return-policy/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-merchant-return-policy/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-merchant-return-policy/latest/',
					),
				),
				'wpssoopm' => array(			// Plugin acronym.
					'short'       => 'WPSSO OPM',	// Short plugin name.
					'name'        => 'WPSSO Organization and Place Manager',
					'desc'        => 'Manage Organizations (publisher, organizer, etc.) and Places for Facebook, Pinterest, and Google local business markup.',
					'slug'        => 'wpsso-organization-place',
					'base'        => 'wpsso-organization-place/wpsso-organization-place.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-organization-place/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-organization-place/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-organization-place/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-organization-place/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-organization-place/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-organization-place',
						'review' => 'https://wordpress.org/support/plugin/wpsso-organization-place/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-organization-place/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-organization-place/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-organization-place/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-organization-place/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-organization-place/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-organization-place/latest/',
					),
				),
				'wpssorar' => array(			// Plugin acronym.
					'short'       => 'WPSSO RAR',	// Short plugin name.
					'name'        => 'WPSSO Ratings and Reviews',
					'desc'        => 'Adds Ratings and Reviews Features to the WordPress Comments System.',
					'slug'        => 'wpsso-ratings-and-reviews',
					'base'        => 'wpsso-ratings-and-reviews/wpsso-ratings-and-reviews.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-ratings-and-reviews/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-ratings-and-reviews/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-ratings-and-reviews/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-ratings-and-reviews/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-ratings-and-reviews/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-ratings-and-reviews',
						'review' => 'https://wordpress.org/support/plugin/wpsso-ratings-and-reviews/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-ratings-and-reviews/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-ratings-and-reviews/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-ratings-and-reviews/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-ratings-and-reviews/installation/',
						'faqs'      => '',
						'notes'     => 'https://wpsso.com/docs/plugins/wpsso-ratings-and-reviews/notes/',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-ratings-and-reviews/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-ratings-and-reviews/latest/',
					),
				),
				'wpssorest' => array(			// Plugin acronym.
					'short'       => 'WPSSO REST',	// Short plugin name.
					'name'        => 'WPSSO REST API',
					'desc'        => 'Extends the WordPress REST API post, term, and user query results with an array of meta tags and Schema JSON-LD markup.',
					'slug'        => 'wpsso-rest-api',
					'base'        => 'wpsso-rest-api/wpsso-rest-api.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-rest-api/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-rest-api/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-rest-api/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-rest-api/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-rest-api/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-rest-api',
						'review' => 'https://wordpress.org/support/plugin/wpsso-rest-api/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-rest-api/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-rest-api/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-rest-api/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-rest-api/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-rest-api/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-rest-api/latest/',
					),
				),
				'wpssorrssb' => array(			// Plugin acronym.
					'short'       => 'WPSSO RRSSB',	// Short plugin name.
					'name'        => 'WPSSO Ridiculously Responsive Social Sharing Buttons',
					'desc'        => 'Ridiculously Responsive (SVG) Social Sharing Buttons for your content, excerpts, CSS sidebar, widget, shortcode, templates, and editor.',
					'slug'        => 'wpsso-rrssb',
					'base'        => 'wpsso-rrssb/wpsso-rrssb.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-rrssb/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-rrssb/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-rrssb/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-rrssb/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-rrssb/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-rrssb',
						'review' => 'https://wordpress.org/support/plugin/wpsso-rrssb/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-rrssb/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-rrssb/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-rrssb/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-rrssb/installation/',
						'faqs'      => '',
						'notes'     => 'https://wpsso.com/docs/plugins/wpsso-rrssb/notes/',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-rrssb/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-rrssb/latest/',
					),
				),
				'wpssossm' => array(			// Plugin acronym.
					'short'       => 'WPSSO SSM',	// Short plugin name.
					'name'        => 'WPSSO Strip Schema Microdata',
					'desc'        => 'Remove Schema Microdata and RDFa from the webpage for better Google Rich Results using Schema JSON-LD markup.',
					'slug'        => 'wpsso-strip-schema-microdata',
					'base'        => 'wpsso-strip-schema-microdata/wpsso-strip-schema-microdata.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-strip-schema-microdata/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-strip-schema-microdata/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-strip-schema-microdata/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-strip-schema-microdata/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-strip-schema-microdata/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-strip-schema-microdata',
						'review' => 'https://wordpress.org/support/plugin/wpsso-strip-schema-microdata/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-strip-schema-microdata/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-strip-schema-microdata/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-strip-schema-microdata/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-strip-schema-microdata/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-strip-schema-microdata/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-strip-schema-microdata/latest/',
					),
				),
				'wpssotie' => array(			// Plugin acronym.
					'short'       => 'WPSSO TIE',	// Short plugin name.
					'name'        => 'WPSSO Tune WP Image Editors',
					'desc'        => 'Improves the appearance of WordPress images for better click through rates from social and search sites.',
					'slug'        => 'wpsso-tune-image-editors',
					'base'        => 'wpsso-tune-image-editors/wpsso-tune-image-editors.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-tune-image-editors/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-tune-image-editors/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-tune-image-editors/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-tune-image-editors/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-tune-image-editors/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-tune-image-editors',
						'review' => 'https://wordpress.org/support/plugin/wpsso-tune-image-editors/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-tune-image-editors/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-tune-image-editors/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-tune-image-editors/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-tune-image-editors/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-tune-image-editors/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-tune-image-editors/latest/',
					),
				),
				'wpssoul' => array(			// Plugin acronym.
					'short'       => 'WPSSO UL',	// Short plugin name.
					'name'        => 'WPSSO User Locale Selector',
					'desc'        => 'Quick and easy locale / language / region selector for the WordPress admin toolbar.',
					'slug'        => 'wpsso-user-locale',
					'base'        => 'wpsso-user-locale/wpsso-user-locale.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-user-locale/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-user-locale/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-user-locale/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-user-locale/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-user-locale/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-user-locale',
						'review' => 'https://wordpress.org/support/plugin/wpsso-user-locale/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-user-locale/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-user-locale/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-user-locale/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-user-locale/installation/',
						'faqs'      => '',
						'notes'     => 'https://wpsso.com/docs/plugins/wpsso-user-locale/notes/',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-user-locale/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-user-locale/latest/',
					),
				),
				'wpssoum' => array(			// Plugin acronym.
					'short'       => 'WPSSO UM',	// Short plugin name.
					'name'        => 'WPSSO Update Manager',
					'desc'        => 'Update Manager for the WPSSO Core Premium plugin.',
					'slug'        => 'wpsso-um',
					'base'        => 'wpsso-um/wpsso-um.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-um/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-um/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-um/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-um/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => false,	// Add-on is not available on wordpress.org.
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-um/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'home'      => 'https://wpsso.com/extend/plugins/wpsso-um/',
						'forum'     => '',
						'review'    => '',
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-um/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-um/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-um/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-um/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-um/latest/',
					),
				),
				'wpssowcmd' => array(			// Plugin acronym.
					'short'       => 'WPSSO WCMD',	// Short plugin name.
					'name'        => 'WPSSO Product Metadata for WooCommerce SEO',
					'desc'        => 'MPN, ISBN, GTIN, GTIN-8, UPC, EAN, GTIN-14, net dimensions, and fluid volume for WooCommerce products and variations.',
					'slug'        => 'wpsso-wc-metadata',
					'base'        => 'wpsso-wc-metadata/wpsso-wc-metadata.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-wc-metadata/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-wc-metadata/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-wc-metadata/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-wc-metadata/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-wc-metadata/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-wc-metadata',
						'review' => 'https://wordpress.org/support/plugin/wpsso-wc-metadata/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-wc-metadata/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-wc-metadata/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-wc-metadata/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-wc-metadata/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-wc-metadata/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-wc-metadata/latest/',
					),
				),
				'wpssowcsdt' => array(			// Plugin acronym.
					'short'       => 'WPSSO WCSDT',	// Short plugin name.
					'name'        => 'WPSSO Shipping Delivery Time for WooCommerce SEO',
					'desc'        => 'Shipping delivery time estimates for WooCommerce shipping zones, methods, and classes.',
					'slug'        => 'wpsso-wc-shipping-delivery-time',
					'base'        => 'wpsso-wc-shipping-delivery-time/wpsso-wc-shipping-delivery-time.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-wc-shipping-delivery-time/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-wc-shipping-delivery-time/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-wc-shipping-delivery-time/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-wc-shipping-delivery-time/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-wc-shipping-delivery-time/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-wc-shipping-delivery-time',
						'review' => 'https://wordpress.org/support/plugin/wpsso-wc-shipping-delivery-time/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' =>	'https://raw.githubusercontent.com/SurniaUlula/wpsso-wc-shipping-delivery-time/main/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-wc-shipping-delivery-time/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-wc-shipping-delivery-time/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-wc-shipping-delivery-time/installation/',
						'faqs'      => 'https://wpsso.com/docs/plugins/wpsso-wc-shipping-delivery-time/faqs/',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-wc-shipping-delivery-time/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-wc-shipping-delivery-time/latest/',
					),
				),
				'wpssowpsm' => array(			// Plugin acronym.
					'short'       => 'WPSSO WPSM',	// Short plugin name.
					'name'        => 'WPSSO WP Sitemaps XML',
					'desc'        => 'Improves the WordPress sitemaps XML with article modification times, alternate language URLs, images sitemaps, news sitemaps and more.',
					'slug'        => 'wpsso-wp-sitemaps',
					'base'        => 'wpsso-wp-sitemaps/wpsso-wp-sitemaps.php',
					'update_auth' => '',		// No premium version.

					/*
					 * URLs or relative paths to plugin banners and icons.
					 */
					'assets' => array(

						/*
						 * Banner image array keys are 'low' and 'high'.
						 */
						'banners' => array(
							'low'  => 'https://surniaulula.github.io/wpsso-wp-sitemaps/assets/banner-772x250.jpg',
							'high' => 'https://surniaulula.github.io/wpsso-wp-sitemaps/assets/banner-1544x500.jpg',
						),

						/*
						 * Icon image array keys are '1x' and '2x'.
						 */
						'icons' => array(
							'1x' => 'https://surniaulula.github.io/wpsso-wp-sitemaps/assets/icon-128x128.png',
							'2x' => 'https://surniaulula.github.io/wpsso-wp-sitemaps/assets/icon-256x256.png',
						),
					),
					'hosts' => array(
						'wp_org' => true,
						'github' => true,
						'wpsso'  => true,
					),
					'url' => array(

						/*
						 * WordPress.org URLs.
						 */
						'home'   => 'https://wordpress.org/plugins/wpsso-wp-sitemaps/',
						'forum'  => 'https://wordpress.org/support/plugin/wpsso-wp-sitemaps',
						'review' => 'https://wordpress.org/support/plugin/wpsso-wp-sitemaps/reviews/?rate=5#new-post',

						/*
						 * GitHub.com URLs.
						 */
						'readme_txt' => 'https://raw.githubusercontent.com/SurniaUlula/wpsso-wp-sitemaps/master/readme.txt',

						/*
						 * WPSSO.com URLs.
						 */
						'changelog' => 'https://wpsso.com/extend/plugins/wpsso-wp-sitemaps/changelog/',
						'docs'      => 'https://wpsso.com/docs/plugins/wpsso-wp-sitemaps/',
						'install'   => 'https://wpsso.com/docs/plugins/wpsso-wp-sitemaps/installation/',
						'faqs'      => '',
						'notes'     => '',
						'support'   => '',	// Premium support ticket.
						'purchase'  => '',	// Purchase page.
						'info'      => '',	// License information.
						'update'    => 'https://wpsso.com/extend/plugins/wpsso-wp-sitemaps/update/',
						'download'  => 'https://wpsso.com/extend/plugins/wpsso-wp-sitemaps/latest/',
					),
				),
			),
			'opt' => array(
				'defaults' => array(
					'checksum'        => '',	// Checksum of plugin versions.
					'opt_checksum'    => '',	// Checksum of option versions.
					'opt_versions'    => array(),

					/*
					 * Site options.
					 */
					'site_name'                       => '',		// Site Name (localized).
					'site_name_alt'                   => '',		// Site Alternate Name (localized).
					'site_desc'                       => '',		// Site Description (localized).
					'site_home_url'                   => '',		// Site Home URL (localized).
					'site_pub_schema_type'            => 'organization',	// Site Publisher Type.
					'site_pub_person_id'              => 'none',		// Site Publisher Person.
					'site_org_logo_url'               => '',		// Organization Logo URL (localized)
					'site_org_banner_url'             => '',		// Organization Banner URL (localized).
					'site_org_place_id'               => 'none',		// Organization Location.
					'site_org_schema_type'            => 'organization',	// Organization Schema Type.

					/*
					 * Facebook options.
					 */
					'fb_publisher_url' => '',			// Facebook Business Page URL (localized).
					'fb_app_id'        => '966242223397117',	// Facebook Application ID.
					'fb_locale'        => 'en_US',			// Facebook Locale.

					/*
					 * Open Graph options.
					 *
					 * See WpssoOpengraph->add_data_og_type_md().
					 */
					'og_def_img_id'             => '',		// Default Image ID.
					'og_def_img_url'            => '',		// Default Image URL.
					'og_def_currency'           => 'USD',		// Default Currency.
					'og_def_country'            => 'none',		// Default Country.
					'og_def_timezone'           => 'UTC',		// Default Timezone.
					'og_def_dimension_units'    => 'cm',		// Default Dimension Units.
					'og_def_weight_units'       => 'kg',		// Default Weight Units.
					'og_def_fluid_volume_units' => 'ml',		// Default Fluid Volume Units.
					'og_img_width'              => 1200,
					'og_img_height'             => 628,
					'og_img_crop'               => 1,
					'og_img_crop_x'             => 'center',
					'og_img_crop_y'             => 'center',
					'og_img_max'                => 1,		// Maximum Images to Include.
					'og_title_sep'              => '-',		// Title Separator.
					'og_ellipsis'               => '...',		// Truncated Text Ellipsis.
					'og_desc_hashtags'          => 0,		// Description Hashtags.
					'og_vid_max'                => 1,
					'og_vid_autoplay'           => 1,
					'og_vid_prev_img'           => 1,		// Include Video Preview Images.

					/*
					 * Advanced Settings > Document Types > Open Graph.
					 */
					'og_type_for_archive_page'   => 'website',
					'og_type_for_comment'        => 'article',
					'og_type_for_comment_reply'  => 'article',
					'og_type_for_comment_review' => 'article',
					'og_type_for_home_page'      => 'website',
					'og_type_for_home_posts'     => 'website',
					'og_type_for_search_page'    => 'website',
					'og_type_for_user_page'      => 'profile',

					/*
					 * Advanced Settings > Document Types > Open Graph tab: Post Types.
					 */
					'og_type_for_attachment'        => 'website',
					'og_type_for_article'           => 'article',
					'og_type_for_book'              => 'book',
					'og_type_for_business'          => 'place',
					'og_type_for_download'          => 'product',	// For Easy Digital Downloads.
					'og_type_for_organization'      => 'website',
					'og_type_for_page'              => 'article',
					'og_type_for_place'             => 'place',
					'og_type_for_post'              => 'article',
					'og_type_for_product'           => 'product', 	// For WooCommerce.
					'og_type_for_product_variation' => 'product', 	// For WooCommerce.
					'og_type_for_question'          => 'article',
					'og_type_for_tc_events'         => 'article',	// For Tickera.
					'og_type_for_tribe_events'      => 'article',	// For The Events Calendar.
					'og_type_for_website'           => 'website',

					/*
					 * Advanced Settings > Document Types > Open Graph tab: Taxonomies.
					 */
					'og_type_for_tax_category'           => 'website',
					'og_type_for_tax_faq_category'       => 'website',
					'og_type_for_tax_link_category'      => 'website',
					'og_type_for_tax_post_tag'           => 'website',
					'og_type_for_tax_product_brand'      => 'website',	// For WooCommerce Brands.
					'og_type_for_tax_product_cat'        => 'website',	// For WooCommerce.
					'og_type_for_tax_product_tag'        => 'website',	// For WooCommerce.
					'og_type_for_tax_pwb-brand'          => 'website',	// For Perfect Brands for WooCommerce.
					'og_type_for_tax_tribe_events_cat'   => 'website',	// For The Events Calendar.
					'og_type_for_tax_yith_product_brand' => 'website',	// For YITH WooCommerce Brands Add-on.

					/*
					 * Pinterest options.
					 */
					'pin_publisher_url'            => '',
					'pin_add_nopin_header_img_tag' => 1,		// Add "nopin" to Site Header Image.
					'pin_add_nopin_media_img_tag'  => 0,		// Add Pinterest "nopin" to Images.
					'pin_add_img_html'             => 0,		// Add Hidden Image for Pinterest.
					'pin_img_width'                => 1200,
					'pin_img_height'               => 1800,
					'pin_img_crop'                 => 0,
					'pin_img_crop_x'               => 'center',
					'pin_img_crop_y'               => 'center',

					/*
					 * Robots options.
					 */
					'robots_max_snippet'       => -1,
					'robots_max_image_preview' => 'large',
					'robots_max_video_preview' => -1,

					/*
					 * Schema options.
					 */
					'schema_1x1_img_width'   => 1200,
					'schema_1x1_img_height'  => 1200,
					'schema_1x1_img_crop'    => 1,
					'schema_1x1_img_crop_x'  => 'center',
					'schema_1x1_img_crop_y'  => 'center',
					'schema_4x3_img_width'   => 1200,
					'schema_4x3_img_height'  => 900,
					'schema_4x3_img_crop'    => 1,
					'schema_4x3_img_crop_x'  => 'center',
					'schema_4x3_img_crop_y'  => 'center',
					'schema_16x9_img_width'  => 1200,
					'schema_16x9_img_height' => 675,
					'schema_16x9_img_crop'   => 1,
					'schema_16x9_img_crop_x' => 'center',
					'schema_16x9_img_crop_y' => 'center',

					/*
					 * Advanced Settings > Document Types > Schema.
					 */
					'schema_type_for_archive_page'   => 'item.list',
					'schema_type_for_comment'        => 'comment',
					'schema_type_for_comment_reply'  => 'comment',
					'schema_type_for_comment_review' => 'review',
					'schema_type_for_home_page'      => 'website',
					'schema_type_for_home_posts'     => 'blog',
					'schema_type_for_search_page'    => 'webpage.search.results',
					'schema_type_for_user_page'      => 'webpage.profile',

					/*
					 * Advanced Settings > Document Types > Open Graph tab: Post Types.
					 */
					'schema_type_for_attachment'    => 'webpage',
					'schema_type_for_article'       => 'article',
					'schema_type_for_book'          => 'book',
					'schema_type_for_business'      => 'local.business',
					'schema_type_for_download'      => 'product',	// For Easy Digital Downloads.
					'schema_type_for_event'         => 'event',
					'schema_type_for_howto'         => 'howto',
					'schema_type_for_job_listing'   => 'job.posting',	// For WP Job Manager.
					'schema_type_for_jobpost'       => 'job.posting',	// For Simple Job Board.
					'schema_type_for_organization'  => 'organization',
					'schema_type_for_page'          => 'article',
					'schema_type_for_person'        => 'person',
					'schema_type_for_place'         => 'place',
					'schema_type_for_post'          => 'blog.posting',
					'schema_type_for_product'       => 'product',	// For WooCommerce.
					'schema_type_for_product_group' => 'product.group',	// For WooCommerce.
					'schema_type_for_qa'            => 'webpage.qa',
					'schema_type_for_question'      => 'question',	// For WPSSO FAQ.
					'schema_type_for_review'        => 'review',	// For WP Product Review.
					'schema_type_for_tc_events'     => 'event',		// For Tickera.
					'schema_type_for_tribe_events'  => 'event',		// For The Events Calendar.
					'schema_type_for_webpage'       => 'webpage',
					'schema_type_for_website'       => 'website',

					/*
					 * Advanced Settings > Document Types > Open Graph tab: Taxonomies.
					 */
					'schema_type_for_tax_category'           => 'item.list',
					'schema_type_for_tax_faq_category'       => 'webpage.faq',	// For WPSSO FAQ.
					'schema_type_for_tax_link_category'      => 'item.list',
					'schema_type_for_tax_post_tag'           => 'item.list',
					'schema_type_for_tax_product_brand'      => 'item.list',	// For WooCommerce Brands.
					'schema_type_for_tax_product_cat'        => 'item.list',	// For WooCommerce.
					'schema_type_for_tax_product_tag'        => 'item.list',	// For WooCommerce.
					'schema_type_for_tax_pwb-brand'          => 'item.list',	// For Perfect WooCommerce Brands Add-on.
					'schema_type_for_tax_tribe_events_cat'   => 'item.list',	// For The Events Calendar.
					'schema_type_for_tax_yith_product_brand' => 'item.list',	// For YITH WooCommerce Brands Add-on.

					/*
					 * Advanced Settings > Schema Defaults > Article.
					 */
					'schema_def_add_articlebody_prop' => 0,		// Add Article Body Property.
					'schema_def_article_section'      => 'none',	// Default Article Section.

					/*
					 * Advanced Settings > Schema Defaults > Book.
					 */
					'schema_def_book_format' => 'none',	// Default Book Format.

					/*
					 * Advanced Settings > Schema Defaults > Creative Work.
					 */
					'schema_def_add_text_prop'   => 0,	// Add Text Property.
					'schema_def_family_friendly' => 'none',	// Default Family Friendly.
					'schema_def_pub_org_id'      => 'site',	// Default Publisher Org.
					'schema_def_pub_person_id'   => 'none',	// Default Publisher Person.
					'schema_def_prov_org_id'     => 'none',	// Default Provider Org.
					'schema_def_prov_person_id'  => 'none',	// Default Provider Person.
					'schema_def_fund_org_id'     => 'none',	// Default Funder Org.
					'schema_def_fund_person_id'  => 'none',	// Default Funder Person.

					/*
					 * Advanced Settings > Schema Defaults > Event.
					 */
					'schema_def_event_attendance'          => 'https://schema.org/OfflineEventAttendanceMode',	// Default Event Attendance.
					'schema_def_event_location_id'         => 'none',						// Default Event Venue.
					'schema_def_event_performer_org_id'    => 'none',						// Default Event Performer Org.
					'schema_def_event_performer_person_id' => 'none',						// Default Event Performer Person.
					'schema_def_event_organizer_org_id'    => 'none',						// Default Event Organizer Org.
					'schema_def_event_organizer_person_id' => 'none',						// Default Event Organizer Person.
					'schema_def_event_fund_org_id'         => 'none',						// Default Event Funder Org.
					'schema_def_event_fund_person_id'      => 'none',						// Default Event Funder Person.

					/*
					 * Advanced Settings > Schema Defaults > Job Posting.
					 */
					'schema_def_job_hiring_org_id' => 'none',	// Default Hiring Org.
					'schema_def_job_location_id'   => 'none',	// Default Job Location.
					'schema_def_job_location_type' => 'none',	// Default Job Location Type.

					/*
					 * Advanced Settings > Schema Defaults > Place.
					 */
					'schema_def_place_schema_type' => 'local.business',	// Default Place Schema Type.
					'schema_def_place_country'     => 'none',		// Default Place Country.
					'schema_def_place_timezone'    => 'UTC',		// Default Place Timezone.

					/*
					 * Advanced Settings > Schema Defaults > Product.
					 */
					'schema_def_product_aggr_offers'           => 0,						// Aggregate Offers by Currency.
					'schema_def_product_adult_type'            => 'none',						// Default Product Adult Type.
					'schema_def_product_age_group'             => 'none',						// Default Product Age Group.
					'schema_def_product_category'              => 'none',						// Default Product Google Category.
					'schema_def_product_condition'             => 'https://schema.org/NewCondition',		// Default Product Condition.
					'schema_def_product_energy_efficiency_min' => 'https://schema.org/EUEnergyEfficiencyCategoryD',
					'schema_def_product_energy_efficiency_max' => 'https://schema.org/EUEnergyEfficiencyCategoryA3Plus',
					'schema_def_product_mrp'                   => 'none',						// Default Product Return Policy.
					'schema_def_product_price_type'            => 'https://schema.org/ListPrice',			// Default Product Price Type.
					'schema_def_product_size_group_0'          => 'none',
					'schema_def_product_size_group_1'          => 'none',
					'schema_def_product_size_system'           => 'none',
					'schema_def_product_target_gender'         => 'none',						// Default Product Target Gender.

					/*
					 * Advanced Settings > Schema Defaults > Profile Page.
					 */
					'schema_def_profile_page_mentions_prop' => 0,	// Add Mentions Property.

					/*
					 * Advanced Settings > Schema Defaults > Review.
					 */
					'schema_def_review_rating_min' => 0.5,		// Default Review Rating Min.
					'schema_def_review_rating_max' => 5.0,		// Default Review Rating Max.
					'schema_def_review_item_type'  => 'product',	// Default Subject Schema Type.

					/*
					 * X (Twitter) Card options.
					 */
					'tc_site'           => '',			// X (Twitter) Business @username (localized).
					'tc_type_singular'  => 'summary_large_image',	// X (Twitter) Card for Post / Page Image.
					'tc_type_default'   => 'summary',		// X (Twitter) Card Type by Default.

					/*
					 * X (Twitter) Summary Card.
					 *
					 * See https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/summary.
					 */
					'tc_sum_img_width'  => 1200,
					'tc_sum_img_height' => 1200,
					'tc_sum_img_crop'   => 1,
					'tc_sum_img_crop_x' => 'center',
					'tc_sum_img_crop_y' => 'center',

					/*
					 * X (Twitter) Summary Card Large Image.
					 *
					 * See https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/summary-card-with-large-image.
					 */
					'tc_lrg_img_width'  => 1200,
					'tc_lrg_img_height' => 628,
					'tc_lrg_img_crop'   => 1,
					'tc_lrg_img_crop_x' => 'center',
					'tc_lrg_img_crop_y' => 'center',

					/*
					 * Schema thumbnail image size.
					 */
					'thumb_img_width'  => 1200,
					'thumb_img_height' => 628,
					'thumb_img_crop'   => 1,
					'thumb_img_crop_x' => 'center',
					'thumb_img_crop_y' => 'center',

					/*
					 * Other publisher page URLs.
					 */
					'instagram_publisher_url' => '',	// Instagram Business Page URL (localized).
					'linkedin_publisher_url'  => '',	// LinkedIn Company Page URL (localized).
					'medium_publisher_url'    => '',	// Medium Business Page URL (localized).
					'myspace_publisher_url'   => '',	// Myspace Business Page URL (localized).
					'sc_publisher_url'        => '',	// Soundcloud Business Page URL (localized).
					'tiktok_publisher_url'    => '',	// TikTok Business Page URL (localized).
					'tumblr_publisher_url'    => '',	// Tumblr Business Page URL (localized).
					'wikipedia_publisher_url' => '',	// Wikipedia Organization Page URL (localized).
					'yt_publisher_url'        => '',	// YouTube Business Channel URL (localized).

					/*
					 * Website verification IDs.
					 */
					'ahrefs_site_verify' => '',	// Ahrefs Website Verification ID.
					'baidu_site_verify'  => '',	// Baidu Website Verification ID.
					'fb_site_verify'     => '',	// Facebook Domain Verification ID.
					'g_site_verify'      => '',	// Google Website Verification ID.
					'bing_site_verify'   => '',	// Microsoft Bing Website Verification ID.
					'pin_site_verify'    => '',	// Pinterest Website Verification ID.
					'yandex_site_verify' => '',	// Yandex Website Verification ID.

					/*
					 * Enable / disable individual head HTML tags.
					 */
					'add_link_rel_author'                               => 0,	// Deprecated - no longer used by Google.
					'add_link_rel_canonical'                            => 1,
					'add_link_rel_publisher'                            => 0,	// Deprecated - no longer used by Google.
					'add_link_rel_shortlink'                            => 1,
					'add_meta_property_fb:admins'                       => 1,
					'add_meta_property_fb:app_id'                       => 1,
					'add_meta_property_al:android:app_name'             => 1,
					'add_meta_property_al:android:package'              => 1,
					'add_meta_property_al:android:url'                  => 1,
					'add_meta_property_al:ios:app_name'                 => 1,
					'add_meta_property_al:ios:app_store_id'             => 1,
					'add_meta_property_al:ios:url'                      => 1,
					'add_meta_property_al:web:url'                      => 1,
					'add_meta_property_al:web:should_fallback'          => 1,
					'add_meta_property_og:altitude'                     => 1,
					'add_meta_property_og:description'                  => 1,
					'add_meta_property_og:image:secure_url'             => 0,	// Use og:image instead.
					'add_meta_property_og:image:url'                    => 0,	// Use og:image instead.
					'add_meta_property_og:image'                        => 1,
					'add_meta_property_og:image:width'                  => 1,
					'add_meta_property_og:image:height'                 => 1,
					'add_meta_property_og:image:alt'                    => 1,
					'add_meta_property_og:latitude'                     => 1,
					'add_meta_property_og:locale'                       => 1,
					'add_meta_property_og:longitude'                    => 1,
					'add_meta_property_og:site_name'                    => 1,
					'add_meta_property_og:title'                        => 1,
					'add_meta_property_og:type'                         => 1,
					'add_meta_property_og:updated_time'                 => 1,
					'add_meta_property_og:url'                          => 1,
					'add_meta_property_og:video:secure_url'             => 0,	// Use og:video instead.
					'add_meta_property_og:video:url'                    => 0,	// Use og:video instead.
					'add_meta_property_og:video'                        => 1,
					'add_meta_property_og:video:type'                   => 1,
					'add_meta_property_og:video:width'                  => 1,
					'add_meta_property_og:video:height'                 => 1,
					'add_meta_property_og:video:tag'                    => 0,	// Disabled by default.
					'add_meta_property_article:author'                  => 1,
					'add_meta_property_article:publisher'               => 1,
					'add_meta_property_article:published_time'          => 1,
					'add_meta_property_article:modified_time'           => 1,
					'add_meta_property_article:expiration_time'         => 1,
					'add_meta_property_article:section'                 => 1,
					'add_meta_property_article:tag'                     => 1,
					'add_meta_property_book:author'                     => 1,
					'add_meta_property_book:isbn'                       => 1,
					'add_meta_property_book:release_date'               => 1,
					'add_meta_property_book:tag'                        => 1,
					'add_meta_property_music:album'                     => 1,
					'add_meta_property_music:album:disc'                => 1,
					'add_meta_property_music:album:track'               => 1,
					'add_meta_property_music:creator'                   => 1,
					'add_meta_property_music:duration'                  => 1,
					'add_meta_property_music:musician'                  => 1,
					'add_meta_property_music:release_date'              => 1,
					'add_meta_property_music:song'                      => 1,
					'add_meta_property_music:song:disc'                 => 1,
					'add_meta_property_music:song:track'                => 1,
					'add_meta_property_place:location:altitude'         => 1,
					'add_meta_property_place:location:latitude'         => 1,
					'add_meta_property_place:location:longitude'        => 1,
					'add_meta_property_place:street_address'            => 1,
					'add_meta_property_place:locality'                  => 1,
					'add_meta_property_place:region'                    => 1,
					'add_meta_property_place:postal_code'               => 1,
					'add_meta_property_place:country_name'              => 1,
					'add_meta_property_product:age_group'               => 1,
					'add_meta_property_product:availability'            => 1,
					'add_meta_property_product:brand'                   => 1,
					'add_meta_property_product:category'                => 1,	// Product Google Category.
					'add_meta_property_product:color'                   => 1,
					'add_meta_property_product:condition'               => 1,
					'add_meta_property_product:ean'                     => 1,
					'add_meta_property_product:expiration_time'         => 1,
					'add_meta_property_product:isbn'                    => 1,
					'add_meta_property_product:item_group_id'           => 1,
					'add_meta_property_product:material'                => 1,
					'add_meta_property_product:mfr_part_no'             => 1,
					'add_meta_property_product:offers'                  => 1,	// Internal meta tag.
					'add_meta_property_product:variants'                => 1,	// Internal meta tag.
					'add_meta_property_product:original_price:amount'   => 1,
					'add_meta_property_product:original_price:currency' => 1,
					'add_meta_property_product:pattern'                 => 1,
					'add_meta_property_product:pretax_price:amount'     => 1,
					'add_meta_property_product:pretax_price:currency'   => 1,
					'add_meta_property_product:price:amount'            => 1,
					'add_meta_property_product:price:currency'          => 1,
					'add_meta_property_product:purchase_limit'          => 1,
					'add_meta_property_product:retailer_item_id'        => 1,
					'add_meta_property_product:retailer_part_no'        => 1,
					'add_meta_property_product:sale_price:amount'       => 1,
					'add_meta_property_product:sale_price:currency'     => 1,
					'add_meta_property_product:sale_price_dates:start'  => 1,
					'add_meta_property_product:sale_price_dates:end'    => 1,
					'add_meta_property_product:shipping_cost:amount'    => 1,
					'add_meta_property_product:shipping_cost:currency'  => 1,
					'add_meta_property_product:shipping_weight:value'   => 1,
					'add_meta_property_product:shipping_weight:units'   => 1,
					'add_meta_property_product:size'                    => 1,
					'add_meta_property_product:target_gender'           => 1,
					'add_meta_property_product:upc'                     => 1,
					'add_meta_property_product:weight:value'            => 1,
					'add_meta_property_product:weight:units'            => 1,
					'add_meta_name_ahrefs-site-verification'            => 0,	// Ahrefs Website Verification ID.
					'add_meta_name_author'                              => 1,
					'add_meta_name_baidu-site-verification'             => 0,	// Baidu Website Verification ID.
					'add_meta_name_description'                         => 1,
					'add_meta_name_facebook-domain-verification'        => 0,
					'add_meta_name_generator'                           => 1,
					'add_meta_name_google-site-verification'            => 0,	// Google Website Verification ID.
					'add_meta_name_msvalidate.01'                       => 0,	// Microsoft Bing Website Verification ID.
					'add_meta_name_p:domain_verify'                     => 0,	// Pinterest Website Verification ID.
					'add_meta_name_robots'                              => 1,
					'add_meta_name_thumbnail'                           => 1,
					'add_meta_name_twitter:card'                        => 1,
					'add_meta_name_twitter:creator'                     => 1,
					'add_meta_name_twitter:domain'                      => 1,
					'add_meta_name_twitter:site'                        => 1,
					'add_meta_name_twitter:title'                       => 1,
					'add_meta_name_twitter:description'                 => 1,
					'add_meta_name_twitter:image'                       => 1,
					'add_meta_name_twitter:image:width'                 => 1,
					'add_meta_name_twitter:image:height'                => 1,
					'add_meta_name_twitter:image:alt'                   => 1,
					'add_meta_name_twitter:player'                      => 1,
					'add_meta_name_twitter:player:stream'               => 1,
					'add_meta_name_twitter:player:stream:content_type'  => 1,
					'add_meta_name_twitter:player:width'                => 1,
					'add_meta_name_twitter:player:height'               => 1,
					'add_meta_name_twitter:app:name:iphone'             => 1,
					'add_meta_name_twitter:app:id:iphone'               => 1,
					'add_meta_name_twitter:app:url:iphone'              => 1,
					'add_meta_name_twitter:app:name:ipad'               => 1,
					'add_meta_name_twitter:app:id:ipad'                 => 1,
					'add_meta_name_twitter:app:url:ipad'                => 1,
					'add_meta_name_twitter:app:name:googleplay'         => 1,
					'add_meta_name_twitter:app:id:googleplay'           => 1,
					'add_meta_name_twitter:app:url:googleplay'          => 1,
					'add_meta_name_twitter:label1'                      => 1,
					'add_meta_name_twitter:data1'                       => 1,
					'add_meta_name_twitter:label2'                      => 1,
					'add_meta_name_twitter:data2'                       => 1,
					'add_meta_name_yandex-verification'                 => 0,	// Yandex Website Verification ID.

					/*
					 * Advanced Settings > Plugin Admin.
					 */
					'plugin_clean_on_uninstall' => 0,	// Remove Settings on Uninstall.
					'plugin_schema_json_min'    => 0,	// Minimize Schema JSON-LD.
					'plugin_load_mofiles'       => 0,	// Use Local Plugin Translations.
					'plugin_debug_html'         => 0,	// Add HTML Debug Messages.
					'plugin_cache_disable'      => 0,	// Disable Cache for Debugging.

					/*
					 * Advanced Settings > Integration.
					 */
					'plugin_title_tag'               => 'seo_title',	// Webpage Title Tag.
					'plugin_filter_content'          => 0,			// Use Filtered Content.
					'plugin_filter_excerpt'          => 0,			// Use Filtered Excerpt.
					'plugin_page_excerpt'            => 0,			// Enable Excerpt for Pages.
					'plugin_page_tags'               => 0,			// Enable Tags for Pages.
					'plugin_new_user_is_person'      => 0,			// Add Person Role for New Users.
					'plugin_inherit_featured'        => 1,			// Inherit Featured Image.
					'plugin_inherit_images'          => 1,			// Inherit Custom Images.
					'plugin_check_img_dims'          => 1,			// Image Dimension Checks.
					'plugin_prevent_thumb_conflicts' => 1,			// Prevent Thumbnail Conflicts.
					'plugin_upscale_images'          => 0,			// Upscale Media Library Images.
					'plugin_upscale_pct_max'         => 33,			// Maximum Image Upscale Percent.

					/*
					 * Advanced Settings > Integration > Plugin and Theme Integration.
					 */
					'plugin_speakable_css_csv'        => '',	// Speakable CSS Selectors.
					'plugin_check_head'               => 1,		// Check for Duplicate Meta Tags.
					'plugin_product_include_vat'      => 0,		// Include VAT in Product Prices.
					'plugin_import_aioseop_meta'      => 0,		// Import All in One SEO Pack Metadata.
					'plugin_import_rankmath_meta'     => 0,		// Import Rank Math SEO Metadata.
					'plugin_import_seoframework_meta' => 0,		// Import The SEO Framework Metadata.
					'plugin_import_wpmetaseo_meta'    => 0,		// Import WP Meta SEO Metadata.
					'plugin_import_wpseo_meta'        => 0,		// Import Yoast SEO Metadata.
					'plugin_import_wpseo_blocks'      => 0,		// Import Yoast SEO Block Attrs.

					/*
					 * Advanced Settings > Default Text.
					 *
					 * See WpssoOptions->get_defaults()
					 * See WpssoOptions->get_text()
					 *
					 * Advanced Settings > Interface.
					 */
					'plugin_show_opts'                     => 'basic',	// Options to Show by Default.
					'plugin_og_types_select_format'        => 'name',	// Open Graph Type Select Format.
					'plugin_schema_types_select_format'    => 'name',	// Schema Type Select Format.
					'plugin_add_toolbar_validate'          => 1,		// Show Validators Toolbar Menu.
					'plugin_add_submenu_essential'         => 1,
					'plugin_add_submenu_general'           => 1,
					'plugin_add_submenu_advanced'          => 1,
					'plugin_add_submenu_licenses'          => 1,
					'plugin_add_submenu_addons'            => 1,
					'plugin_add_submenu_tools'             => 1,
					'plugin_add_submenu_setup'             => 1,
					'plugin_add_submenu_debug'             => 1,
					'plugin_add_to_attachment'             => 1,
					'plugin_add_to_download'               => 1,		// For Easy Digital Downloads.
					'plugin_add_to_page'                   => 1,
					'plugin_add_to_post'                   => 1,
					'plugin_add_to_product'                => 1,		// For WooCommerce and other e-commerce plugins.
					'plugin_add_to_reply'                  => 0,		// For Bbpress
					'plugin_add_to_tax_category'           => 1,
					'plugin_add_to_tax_faq_category'       => 1,
					'plugin_add_to_tax_link_category'      => 1,
					'plugin_add_to_tax_post_tag'           => 1,
					'plugin_add_to_tax_product_brand'      => 1,		// For WooCommerce Brands.
					'plugin_add_to_tax_product_cat'        => 1,		// For WooCommerce.
					'plugin_add_to_tax_product_tag'        => 1,		// For WooCommerce.
					'plugin_add_to_tax_pwb-brand'          => 1,		// For Perfect WooCommerce Brands Add-on.
					'plugin_add_to_tax_tribe_events_cat'   => 0,		// For The Events Calendar.
					'plugin_add_to_tax_yith_product_brand' => 1,		// For YITH WooCommerce Brands Add-on.
					'plugin_add_to_topic'                  => 0,		// For Bbpress
					'plugin_add_to_tribe_events'           => 1,		// For The Events Calendar.
					'plugin_add_to_tribe-ea-record'        => 1,		// For The Events Calendar.
					'plugin_add_to_user_page'              => 1,

					/*
					 * Advanced Settings > Interface > WP List Table Columns: Open Graph Description.
					 */
					'plugin_og_desc_col_attachment'           => 1,
					'plugin_og_desc_col_download'             => 0,		// For Easy Digital Downloads.
					'plugin_og_desc_col_page'                 => 0,
					'plugin_og_desc_col_post'                 => 0,
					'plugin_og_desc_col_product'              => 0,		// For WooCommerce.
					'plugin_og_desc_col_tax_category'         => 1,
					'plugin_og_desc_col_tax_faq_category'     => 1,
					'plugin_og_desc_col_tax_link_category'    => 1,
					'plugin_og_desc_col_tax_post_tag'         => 1,
					'plugin_og_desc_col_tax_product_cat'      => 1,		// For WooCommerce.
					'plugin_og_desc_col_tax_product_tag'      => 1,		// For WooCommerce.
					'plugin_og_desc_col_tax_tribe_events_cat' => 0,		// For The Events Calendar.
					'plugin_og_desc_col_user_page'            => 1,

					/*
					 * Advanced Settings > Interface > WP List Table Columns: Open Graph Image.
					 */
					'plugin_og_img_col_attachment'           => 0,
					'plugin_og_img_col_download'             => 1,		// For Easy Digital Downloads.
					'plugin_og_img_col_page'                 => 1,
					'plugin_og_img_col_post'                 => 1,
					'plugin_og_img_col_product'              => 1,		// For WooCommerce.
					'plugin_og_img_col_tax_category'         => 1,
					'plugin_og_img_col_tax_faq_category'     => 1,
					'plugin_og_img_col_tax_link_category'    => 1,
					'plugin_og_img_col_tax_post_tag'         => 1,
					'plugin_og_img_col_tax_product_cat'      => 1,		// For WooCommerce.
					'plugin_og_img_col_tax_product_tag'      => 1,		// For WooCommerce.
					'plugin_og_img_col_tax_tribe_events_cat' => 0,		// For The Events Calendar.
					'plugin_og_img_col_user_page'            => 1,

					/*
					 * Advanced Settings > Interface > WP List Table Columns: Open Graph Type.
					 */
					'plugin_og_type_col_attachment'           => 0,
					'plugin_og_type_col_download'             => 0,		// For Easy Digital Downloads.
					'plugin_og_type_col_page'                 => 0,
					'plugin_og_type_col_post'                 => 0,
					'plugin_og_type_col_product'              => 0,		// For WooCommerce.
					'plugin_og_type_col_tax_category'         => 0,
					'plugin_og_type_col_tax_faq_category'     => 0,
					'plugin_og_type_col_tax_link_category'    => 0,
					'plugin_og_type_col_tax_post_tag'         => 0,
					'plugin_og_type_col_tax_product_cat'      => 0,		// For WooCommerce.
					'plugin_og_type_col_tax_product_tag'      => 0,		// For WooCommerce.
					'plugin_og_type_col_tax_tribe_events_cat' => 0,		// For The Events Calendar.
					'plugin_og_type_col_user_page'            => 0,

					/*
					 * Advanced Settings > Interface > WP List Table Columns: Open Graph Locale.
					 */
					'plugin_schema_lang_col_attachment'           => 0,
					'plugin_schema_lang_col_download'             => 0,	// For Easy Digital Downloads.
					'plugin_schema_lang_col_page'                 => 0,
					'plugin_schema_lang_col_post'                 => 0,
					'plugin_schema_lang_col_product'              => 0,	// For WooCommerce.
					'plugin_schema_lang_col_tax_category'         => 0,
					'plugin_schema_lang_col_tax_faq_category'     => 0,
					'plugin_schema_lang_col_tax_link_category'    => 0,
					'plugin_schema_lang_col_tax_post_tag'         => 0,
					'plugin_schema_lang_col_tax_product_cat'      => 0,	// For WooCommerce.
					'plugin_schema_lang_col_tax_product_tag'      => 0,	// For WooCommerce.
					'plugin_schema_lang_col_tax_tribe_events_cat' => 0,	// For The Events Calendar.
					'plugin_schema_lang_col_user_page'            => 0,

					/*
					 * Advanced Settings > Interface > WP List Table Columns: Schema ID.
					 */
					'plugin_schema_type_col_attachment'           => 0,
					'plugin_schema_type_col_download'             => 0,	// For Easy Digital Downloads.
					'plugin_schema_type_col_page'                 => 0,
					'plugin_schema_type_col_post'                 => 0,
					'plugin_schema_type_col_product'              => 0,	// For WooCommerce.
					'plugin_schema_type_col_tax_category'         => 0,
					'plugin_schema_type_col_tax_faq_category'     => 0,
					'plugin_schema_type_col_tax_link_category'    => 0,
					'plugin_schema_type_col_tax_post_tag'         => 0,
					'plugin_schema_type_col_tax_product_cat'      => 0,	// For WooCommerce.
					'plugin_schema_type_col_tax_product_tag'      => 0,	// For WooCommerce.
					'plugin_schema_type_col_tax_tribe_events_cat' => 0,	// For The Events Calendar.
					'plugin_schema_type_col_user_page'            => 0,

					/*
					 * Advanced Settings > Interface > WP List Table Columns: Schema Type.
					 */
					'plugin_schema_type_name_col_attachment'           => 0,
					'plugin_schema_type_name_col_download'             => 0,	// For Easy Digital Downloads.
					'plugin_schema_type_name_col_page'                 => 0,
					'plugin_schema_type_name_col_post'                 => 0,
					'plugin_schema_type_name_col_product'              => 0,	// For WooCommerce.
					'plugin_schema_type_name_col_tax_category'         => 0,
					'plugin_schema_type_name_col_tax_faq_category'     => 0,
					'plugin_schema_type_name_col_tax_link_category'    => 0,
					'plugin_schema_type_name_col_tax_post_tag'         => 0,
					'plugin_schema_type_name_col_tax_product_cat'      => 0,	// For WooCommerce.
					'plugin_schema_type_name_col_tax_product_tag'      => 0,	// For WooCommerce.
					'plugin_schema_type_name_col_tax_tribe_events_cat' => 0,	// For The Events Calendar.
					'plugin_schema_type_name_col_user_page'            => 0,

					/*
					 * Advanced Settings > Service APIs > Media Services.
					 */
					'plugin_gravatar_image'         => 1,		// Gravatar is Default Author Image.
					'plugin_gravatar_size'          => 1200,	// Gravatar Image Size.
					'plugin_media_facebook'         => 1,		// Detect Embedded Media: Facebook Videos.
					'plugin_media_slideshare'       => 1,		// Detect Embedded Media: Slideshare Presentations.
					'plugin_media_soundcloud'       => 1,		// Detect Embedded Media: Soundcloud Tracks.
					'plugin_media_vimeo'            => 1,		// Detect Embedded Media: Vimeo Videos.
					'plugin_media_wistia'           => 1,		// Detect Embedded Media: Wistia Videos.
					'plugin_media_wpvideoblock'     => 1,		// Detect Embedded Media: WP Media Library Video Blocks.
					'plugin_media_wpvideoshortcode' => 1,		// Detect Embedded Media: WP Media Library Video Shortcodes.
					'plugin_media_youtube'          => 1,		// Detect Embedded Media: Youtube Videos and Playlists.

					/*
					 * Advanced Settings > Service APIs > Shortening Services.
					 */
					'plugin_shortener'          => 'none',	// URL Shortening Service.
					'plugin_min_shorten'        => 23,	// Minimum URL Length to Shorten.
					'plugin_wp_shortlink'       => 1,	// Use Short URL for WP Shortlink.
					'plugin_bitly_access_token' => '',	// Bitly Access Token.
					'plugin_bitly_domain'       => '',	// Bitly Short Domain (Optional).
					'plugin_bitly_group_name'   => '',	// Bitly Group Name (Optional).
					'plugin_dlmyapp_api_key'    => '',	// DLMY.App API Key.
					'plugin_owly_api_key'       => '',	// Ow.ly API Key.
					'plugin_yourls_api_url'     => '',	// YOURLS API URL.
					'plugin_yourls_username'    => '',	// YOURLS Username.
					'plugin_yourls_password'    => '',	// YOURLS Password.
					'plugin_yourls_token'       => '',	// YOURLS Token.

					/*
					 * Advanced Settings > Service APIs > Ratings and Reviews.
					 */
					'plugin_ratings_reviews_svc'          => 'none',	// Ratings and Reviews Service.
					'plugin_ratings_reviews_num_max'      => 100,		// Maximum Number of Reviews.
					'plugin_ratings_reviews_months_max'   => 60,		// Maximum Age of Reviews.
					'plugin_ratings_reviews_for_download' => 1,		// For Easy Digital Downloads.
					'plugin_ratings_reviews_for_product'  => 1,		// For WooCommerce.
					'plugin_judgeme_shop_domain'          => '',		// Judge.me Shop Domain.
					'plugin_judgeme_shop_token'           => '',		// Judge.me Shop Token.
					'plugin_shopperapproved_site_id'      => '',		// Shopper Approved Site ID.
					'plugin_shopperapproved_token'        => '',		// Shopper Approved API Token.
					'plugin_stamped_store_hash'           => '',		// Stamped Store Hash.
					'plugin_stamped_key_public'           => '',		// Stamped API Key Public.

					/*
					 * Advanced Settings > Contact Fields > Custom Contacts.
					 */
					'plugin_cm_behance_name'      => 'behance',
					'plugin_cm_behance_label'     => 'Behance Profile URL',
					'plugin_cm_behance_enabled'   => 1,
					'plugin_cm_fb_name'           => 'facebook',
					'plugin_cm_fb_label'          => 'Facebook User URL',
					'plugin_cm_fb_enabled'        => 1,
					'plugin_cm_instagram_name'    => 'instagram',
					'plugin_cm_instagram_label'   => 'Instagram URL',
					'plugin_cm_instagram_enabled' => 1,
					'plugin_cm_linkedin_name'     => 'linkedin',
					'plugin_cm_linkedin_label'    => 'LinkedIn URL',
					'plugin_cm_linkedin_enabled'  => 1,
					'plugin_cm_medium_name'       => 'medium',
					'plugin_cm_medium_label'      => 'Medium URL',
					'plugin_cm_medium_enabled'    => 1,
					'plugin_cm_myspace_name'      => 'myspace',
					'plugin_cm_myspace_label'     => 'Myspace URL',
					'plugin_cm_myspace_enabled'   => 1,
					'plugin_cm_pin_name'          => 'pinterest',
					'plugin_cm_pin_label'         => 'Pinterest URL',
					'plugin_cm_pin_enabled'       => 1,
					'plugin_cm_sc_name'           => 'soundcloud',
					'plugin_cm_sc_label'          => 'Soundcloud URL',
					'plugin_cm_sc_enabled'        => 1,
					'plugin_cm_skype_name'        => 'skype',
					'plugin_cm_skype_label'       => 'Skype Username',
					'plugin_cm_skype_enabled'     => 1,
					'plugin_cm_tiktok_name'       => 'tiktok',
					'plugin_cm_tiktok_label'      => 'TikTok URL',
					'plugin_cm_tiktok_enabled'    => 1,
					'plugin_cm_tumblr_name'       => 'tumblr',
					'plugin_cm_tumblr_label'      => 'Tumblr URL',
					'plugin_cm_tumblr_enabled'    => 1,
					'plugin_cm_wikipedia_name'    => 'wikipedia',
					'plugin_cm_wikipedia_label'   => 'Wikipedia Page URL',
					'plugin_cm_wikipedia_enabled' => 1,
					'plugin_cm_twitter_name'      => 'twitter',
					'plugin_cm_twitter_label'     => 'X (Twitter) @username',
					'plugin_cm_twitter_enabled'   => 1,
					'plugin_cm_yt_name'           => 'youtube',
					'plugin_cm_yt_label'          => 'YouTube Channel URL',
					'plugin_cm_yt_enabled'        => 1,

					/*
					 * Advanced Settings > Contact Fields > Default Contacts.
					 */
					'wp_cm_aim_name'       => 'aim',
					'wp_cm_aim_label'      => 'AIM',
					'wp_cm_aim_enabled'    => 1,
					'wp_cm_jabber_name'    => 'jabber',
					'wp_cm_jabber_label'   => 'Google Talk',
					'wp_cm_jabber_enabled' => 1,
					'wp_cm_yim_name'       => 'yim',
					'wp_cm_yim_label'      => 'Yahoo Messenger',
					'wp_cm_yim_enabled'    => 1,

					/*
					 * Advanced Settings > About the User.
					 */
					'plugin_user_about_job_title'        => 1,	// Job Title.
					'plugin_user_about_honorific_prefix' => 1,	// Honorific Prefix.
					'plugin_user_about_honorific_suffix' => 1,	// Honorific Suffix.
					'plugin_user_about_additional_name'  => 1,	// Middle or Additional Name.
					'plugin_user_about_award'            => 1,	// Awards.

					/*
					 * Advanced Settings > Attributes and Metadata > Product Attributes.
					 */
					'plugin_attr_product_adult_type'              => 'Adult Type',		// Adult Type Attribute.
					'plugin_attr_product_adult_type#fr_BE'        => 'Type adulte',
					'plugin_attr_product_adult_type#fr_CA'        => 'Type adulte',
					'plugin_attr_product_adult_type#fr_FR'        => 'Type adulte',
					'plugin_attr_product_age_group'               => 'Age Group',		// Age Group Attribute.
					'plugin_attr_product_age_group#fr_BE'         => 'Groupe d\'âge',
					'plugin_attr_product_age_group#fr_CA'         => 'Groupe d\'âge',
					'plugin_attr_product_age_group#fr_FR'         => 'Groupe d\'âge',
					'plugin_attr_product_brand'                   => 'Brand',		// Brand Attribute.
					'plugin_attr_product_brand#fr_BE'             => 'Marque',
					'plugin_attr_product_brand#fr_CA'             => 'Marque',
					'plugin_attr_product_brand#fr_FR'             => 'Marque',
					'plugin_attr_product_color'                   => 'Color',		// Color Attribute.
					'plugin_attr_product_color#fr_BE'             => 'Couleur',
					'plugin_attr_product_color#fr_CA'             => 'Couleur',
					'plugin_attr_product_color#fr_FR'             => 'Couleur',
					'plugin_attr_product_condition'               => 'Condition',		// Condition Attribute.
					'plugin_attr_product_condition#fr_BE'         => 'État',
					'plugin_attr_product_condition#fr_CA'         => 'État',
					'plugin_attr_product_condition#fr_FR'         => 'État',
					'plugin_attr_product_energy_efficiency'       => 'Energy Rating',	// Energy Rating Attribute.
					'plugin_attr_product_energy_efficiency#fr_BE' => 'Classe énergétique',
					'plugin_attr_product_energy_efficiency#fr_CA' => 'Classe énergétique',
					'plugin_attr_product_energy_efficiency#fr_FR' => 'Classe énergétique',
					'plugin_attr_product_fluid_volume_value'      => '',			// Fluid Volume Attribute.
					'plugin_attr_product_gtin14'                  => '',			// GTIN-14 Attribute.
					'plugin_attr_product_gtin13'                  => '',			// GTIN-13 (EAN) Attribute.
					'plugin_attr_product_gtin12'                  => '',			// GTIN-12 (UPC) Attribute.
					'plugin_attr_product_gtin8'                   => '',			// GTIN-8 Attribute.
					'plugin_attr_product_gtin'                    => '',			// GTIN Attribute.
					'plugin_attr_product_height_value'            => '',			// Net Height Attribute.
					'plugin_attr_product_isbn'                    => '',			// ISBN Attribute.
					'plugin_attr_product_length_value'            => '',			// Net Len. / Depth Attribute.
					'plugin_attr_product_material'                => 'Material',		// Material Attribute.
					'plugin_attr_product_material#fr_BE'          => 'Matériel',
					'plugin_attr_product_material#fr_CA'          => 'Matériel',
					'plugin_attr_product_material#fr_FR'          => 'Matériel',
					'plugin_attr_product_mfr_part_no'             => '',			// MPN Attribute.
					'plugin_attr_product_pattern'                 => 'Pattern',		// Pattern Attribute.
					'plugin_attr_product_pattern#fr_BE'           => 'Motif',
					'plugin_attr_product_pattern#fr_CA'           => 'Motif',
					'plugin_attr_product_pattern#fr_FR'           => 'Motif',
					'plugin_attr_product_size'                    => 'Size',		// Size Attribute.
					'plugin_attr_product_size#fr_BE'              => 'Taille',
					'plugin_attr_product_size#fr_CA'              => 'Taille',
					'plugin_attr_product_size#fr_FR'              => 'Taille',
					'plugin_attr_product_size_group'              => 'Size Group',		// Size Group Attribute.
					'plugin_attr_product_size_group#fr_BE'        => 'Groupe de taille',
					'plugin_attr_product_size_group#fr_CA'        => 'Groupe de taille',
					'plugin_attr_product_size_group#fr_FR'        => 'Groupe de taille',
					'plugin_attr_product_size_system'             => 'Size System',		// Size System Attribute.
					'plugin_attr_product_size_system#fr_BE'       => 'Système de taille',
					'plugin_attr_product_size_system#fr_CA'       => 'Système de taille',
					'plugin_attr_product_size_system#fr_FR'       => 'Système de taille',
					'plugin_attr_product_target_gender'           => 'Gender',		// Target Gender Attribute.
					'plugin_attr_product_target_gender#fr_BE'     => 'Sexe',
					'plugin_attr_product_target_gender#fr_CA'     => 'Sexe',
					'plugin_attr_product_target_gender#fr_FR'     => 'Sexe',
					'plugin_attr_product_weight_value'            => '',			// Net Weight Attribute.
					'plugin_attr_product_width_value'             => '',			// Net Width Attribute.

					/*
					 * Advanced Settings > Attributes and Metadata > Custom Fields.
					 */
					'plugin_cf_addl_type_urls'                => '',	// Microdata Type URLs Custom Field.
					'plugin_cf_book_isbn'                     => '',	// Book ISBN Custom Field.
					'plugin_cf_img_url'                       => '',	// Image URL Custom Field.
					'plugin_cf_product_adult_type'            => '',	// Product Adult Type Custom Field.
					'plugin_cf_product_age_group'             => '',	// Product Age Group Custom Field.
					'plugin_cf_product_avail'                 => '',	// Product Availability Custom Field.
					'plugin_cf_product_brand'                 => '',	// Product Brand Custom Field.
					'plugin_cf_product_category'              => '',	// Product Google Cat. ID Custom Field.
					'plugin_cf_product_color'                 => '',	// Product Color Custom Field.
					'plugin_cf_product_condition'             => '',	// Product Condition Custom Field.
					'plugin_cf_product_currency'              => '',	// Product Currency Custom Field.
					'plugin_cf_product_energy_efficiency'     => '',	// Product Energy Rating Custom Field.
					'plugin_cf_product_fluid_volume_value'    => '',	// Product Fluid Volume Custom Field.
					'plugin_cf_product_gtin14'                => '',	// Product GTIN-14 Custom Field.
					'plugin_cf_product_gtin13'                => '',	// Product GTIN-13 (EAN) Custom Field.
					'plugin_cf_product_gtin12'                => '',	// Product GTIN-12 (UPC) Custom Field.
					'plugin_cf_product_gtin8'                 => '',	// Product GTIN-8 Custom Field.
					'plugin_cf_product_gtin'                  => '',	// Product GTIN Custom Field.
					'plugin_cf_product_isbn'                  => '',	// Product ISBN Custom Field.
					'plugin_cf_product_height_value'          => '',	// Product Net Height Custom Field.
					'plugin_cf_product_length_value'          => '',	// Product Net Len. / Depth Custom Field.
					'plugin_cf_product_material'              => '',	// Product Material Custom Field.
					'plugin_cf_product_mfr_part_no'           => '',	// Product MPN Custom Field.
					'plugin_cf_product_min_advert_price'      => '',	// Product Min Advert Price Custom Field.
					'plugin_cf_product_pattern'               => '',	// Product Pattern Custom Field.
					'plugin_cf_product_price'                 => '',	// Product Price Custom Field.
					'plugin_cf_product_price_type'            => '',	// Product Price Type Custom Field.
					'plugin_cf_product_retailer_part_no'      => '',	// Product SKU Custom Field.
					'plugin_cf_product_shipping_height_value' => '',	// Product Shipping Height Custom Field.
					'plugin_cf_product_shipping_length_value' => '',	// Product Shipping Length Custom Field.
					'plugin_cf_product_shipping_weight_value' => '',	// Product Shipping Weight Custom Field.
					'plugin_cf_product_shipping_width_value'  => '',	// Product Shipping Width Custom Field.
					'plugin_cf_product_size'                  => '',	// Product Size Custom Field.
					'plugin_cf_product_size_group'            => '',	// Product Size Group Custom Field.
					'plugin_cf_product_size_system'           => '',	// Product Size System Custom Field.
					'plugin_cf_product_target_gender'         => '',	// Product Target Gender Custom Field.
					'plugin_cf_product_weight_value'          => '',	// Product Net Weight Custom Field.
					'plugin_cf_product_width_value'           => '',	// Product Net Width Custom Field.
					'plugin_cf_review_item_name'              => '',	// Review Subject Name Custom Field.
					'plugin_cf_review_item_desc'              => '',	// Review Subject Desc Custom Field.
					'plugin_cf_review_rating'                 => '',	// Review Rating Custom Field.
					'plugin_cf_review_rating_alt_name'        => '',	// Review Rating Alt Name Custom Field.
					'plugin_cf_sameas_urls'                   => '',	// Same-As URLs Custom Field.
					'plugin_cf_vid_embed'                     => '',	// Video Embed HTML Custom Field.
					'plugin_cf_vid_url'                       => '',	// Video URL Custom Field.

					/*
					 * Premium Licenses.
					 */
					'plugin_wpsso_tid' => '',

				),	// End of 'defaults' array.

				/*
				 * Multisite options.
				 *
				 * Automatically includes all advanced plugin options.
				 *
				 * See the WpssoOptions->get_site_defaults() method.
				 */
				'site_defaults' => array(
					'checksum'     => '',	// Checksum of plugin versions.
					'opt_checksum' => '',	// Checksum of option versions.
					'opt_versions' => array(),
				),

				/*
				 * Contact method and social options prefix.
				 *
				 * The options prefix is used for contact method options (for example, 'plugin_cm_fb_enabled') and
				 * social sharing buttons options (for example, 'fb_utm_source').
				 */
				'cm_prefix' => array(
					'behance'     => 'behance',
					'email'       => 'email',
					'facebook'    => 'fb',
					'twitter'     => 'twitter',
					'instagram'   => 'instagram',
					'linkedin'    => 'linkedin',
					'medium'      => 'medium',
					'myspace'     => 'myspace',
					'pinterest'   => 'pin',
					'pocket'      => 'pocket',
					'buffer'      => 'buffer',
					'reddit'      => 'reddit',
					'managewp'    => 'managewp',
					'soundcloud'  => 'sc',
					'tiktok'      => 'tiktok',
					'tumblr'      => 'tumblr',
					'skype'       => 'skype',
					'vk'          => 'vk',
					'whatsapp'    => 'wa',
					'wikipedia'   => 'wikipedia',
					'youtube'     => 'yt',
				),

				/*
				 * WpssoConfig::$cf[ 'opt' ][ 'attr_md_index' ]
				 *
				 * Provides a key index for product attributes to meta data options.
				 */
				'attr_md_index' => array(
					'plugin_attr_product_adult_type'         => 'product_adult_type',
					'plugin_attr_product_age_group'          => 'product_age_group',
					'plugin_attr_product_brand'              => 'product_brand',
					'plugin_attr_product_color'              => 'product_color',
					'plugin_attr_product_condition'          => 'product_condition',
					'plugin_attr_product_energy_efficiency'  => 'product_energy_efficiency',
					'plugin_attr_product_fluid_volume_value' => 'product_fluid_volume_value',
					'plugin_attr_product_gtin14'             => 'product_gtin14',
					'plugin_attr_product_gtin13'             => 'product_gtin13',
					'plugin_attr_product_gtin12'             => 'product_gtin12',
					'plugin_attr_product_gtin8'              => 'product_gtin8',
					'plugin_attr_product_gtin'               => 'product_gtin',
					'plugin_attr_product_height_value'       => 'product_height_value',
					'plugin_attr_product_isbn'               => 'product_isbn',
					'plugin_attr_product_length_value'       => 'product_length_value',
					'plugin_attr_product_material'           => 'product_material',
					'plugin_attr_product_mfr_part_no'        => 'product_mfr_part_no',
					'plugin_attr_product_pattern'            => 'product_pattern',
					'plugin_attr_product_size'               => 'product_size',
					'plugin_attr_product_size_group'         => 'product_size_group',
					'plugin_attr_product_size_system'        => 'product_size_system',
					'plugin_attr_product_target_gender'      => 'product_target_gender',
					'plugin_attr_product_weight_value'       => 'product_weight_value',
					'plugin_attr_product_width_value'        => 'product_width_value',
				),

				/*
				 * WpssoConfig::$cf[ 'opt' ][ 'cf_md_index' ]
				 *
				 * Provides a key index for custom fields to meta data options.
				 */
				'cf_md_index' => array(
					'plugin_cf_addl_type_urls'                => 'schema_addl_type_url',		// Microdata Type URLs Custom Field.
					'plugin_cf_book_isbn'                     => 'schema_book_isbn',
					'plugin_cf_img_url'                       => 'og_img_url',
					'plugin_cf_product_adult_type'            => 'product_adult_type',
					'plugin_cf_product_age_group'             => 'product_age_group',
					'plugin_cf_product_avail'                 => 'product_avail',
					'plugin_cf_product_brand'                 => 'product_brand',			// Product Brand Custom Field.
					'plugin_cf_product_category'              => 'product_category',		// Product Google Category Custom Field.
					'plugin_cf_product_color'                 => 'product_color',
					'plugin_cf_product_condition'             => 'product_condition',		// Product Condition Custom Field.
					'plugin_cf_product_currency'              => 'product_currency',
					'plugin_cf_product_energy_efficiency'     => 'product_energy_efficiency',	// Product Energy Rating Custom Field.
					'plugin_cf_product_fluid_volume_value'    => 'product_fluid_volume_value',	// Product Fluid Volume Custom Field.
					'plugin_cf_product_gtin14'                => 'product_gtin14',
					'plugin_cf_product_gtin13'                => 'product_gtin13',
					'plugin_cf_product_gtin12'                => 'product_gtin12',
					'plugin_cf_product_gtin8'                 => 'product_gtin8',
					'plugin_cf_product_gtin'                  => 'product_gtin',
					'plugin_cf_product_height_value'          => 'product_height_value',		// Product Net Height Custom Field.
					'plugin_cf_product_isbn'                  => 'product_isbn',
					'plugin_cf_product_length_value'          => 'product_length_value',		// Product Net Len. / Depth Custom Field.
					'plugin_cf_product_material'              => 'product_material',
					'plugin_cf_product_mfr_part_no'           => 'product_mfr_part_no',
					'plugin_cf_product_min_advert_price'      => 'product_min_advert_price',
					'plugin_cf_product_pattern'               => 'product_pattern',
					'plugin_cf_product_price'                 => 'product_price',
					'plugin_cf_product_price_type'            => 'product_price_type',
					'plugin_cf_product_retailer_part_no'      => 'product_retailer_part_no',
					'plugin_cf_product_shipping_height_value' => 'product_shipping_height_value',	// Product Shipping Height Custom Field.
					'plugin_cf_product_shipping_length_value' => 'product_shipping_length_value',	// Product Shipping Length Custom Field.
					'plugin_cf_product_shipping_weight_value' => 'product_shipping_weight_value',	// Product Shipping Weight Custom Field.
					'plugin_cf_product_shipping_width_value'  => 'product_shipping_width_value',	// Product Shipping Width Custom Field.
					'plugin_cf_product_size'                  => 'product_size',
					'plugin_cf_product_size_group'            => 'product_size_group',
					'plugin_cf_product_size_system'           => 'product_size_system',
					'plugin_cf_product_target_gender'         => 'product_target_gender',
					'plugin_cf_product_weight_value'          => 'product_weight_value',		// Product Net Weight Custom Field.
					'plugin_cf_product_width_value'           => 'product_width_value',		// Product Net Width Custom Field.
					'plugin_cf_review_item_name'              => 'schema_review_item_name',		// Review Subject Name Custom Field.
					'plugin_cf_review_item_desc'              => 'schema_review_item_desc',		// Review Subject Desc Custom Field.
					'plugin_cf_review_rating'                 => 'schema_review_rating',		// Review Rating Custom Field.
					'plugin_cf_review_rating_alt_name'        => 'schema_review_rating_alt_name',	// Review Rating Alt Name Custom Field.
					'plugin_cf_sameas_urls'                   => 'schema_sameas_url',
					'plugin_cf_vid_embed'                     => 'og_vid_embed',
					'plugin_cf_vid_url'                       => 'og_vid_url',
				),

				/*
				 * WpssoConfig::$cf[ 'opt' ][ 'md_keys_multi' ]
				 *
				 * Provides information to re-number multiple input/textarea fields and read/split custom field
				 * values into numbered options.
				 */
				'md_keys_multi' => array(
					'org_award'            => true,				// Organization Awards.
					'product_award'        => true,				// Product Awards.
					'product_size_group'   => true,				// Product Size Group.
					'schema_addl_type_url' => true,				// Microdata Type URLs.
					'schema_award'         => true,				// Creative Work Awards.
					'schema_citation'      => true,				// Reference Citations.
					'schema_howto_step'    => array(			// How-To Name.
						'schema_howto_step_section',			// How-To Step or Section.
						'schema_howto_step_text',			// How-To Step Description.
						'schema_howto_step_anchor_id',			// How-To Step Anchor ID.
						'schema_howto_step_img_id',			// How-To Step Image ID.
						'schema_howto_step_img_id_lib',
					),
					'schema_howto_supply'               => true,		// How-To Supplies.
					'schema_howto_tool'                 => true,		// How-To Tools.
					'schema_ispartof_url'               => true,		// Is Part of URLs.
					'schema_movie_actor_person_name'    => true,
					'schema_movie_director_person_name' => true,
					'schema_recipe_ingredient'          => true,		// Recipe Ingredients.
					'schema_recipe_instruction'         => array(		// Recipe Instructions.
						'schema_recipe_instruction_section',		// Recipe Instruction or Section.
						'schema_recipe_instruction_text',		// Recipe Instruction Description.
						'schema_recipe_instruction_anchor_id',		// Recipe Instruction Anchor ID.
						'schema_recipe_instruction_img_id',		// Recipe Instruction Image ID.
						'schema_recipe_instruction_img_id_lib',
					),
					'schema_review_item_cw_movie_actor_person_name'    => true,
					'schema_review_item_cw_movie_director_person_name' => true,
					'schema_sameas_url'                                => true,	// Same-As URLs.
					'schema_webpage_reviewed_by_org_id'                => true,
					'schema_webpage_reviewed_by_person_id'             => true,
				),

				/*
				 * Fallback order for custom Document SSO metadata keys.
				 */
				'md_keys_fallback' => array(

					/*
					 * SEO and Schema metadata keys.
					 */
					'seo_title'        => array( 'seo_title' ),
					'seo_desc'         => array( 'seo_desc' ),
					'schema_title'     => array( 'schema_title', 'seo_title' ),
					'schema_title_alt' => array( 'schema_title_alt', 'schema_title', 'seo_title' ),
					'schema_title_bc'  => array( 'schema_title_bc', 'schema_title_alt', 'schema_title', 'seo_title' ),
					'schema_headline'  => array( 'schema_headline', 'schema_title', 'seo_title' ),
					'schema_job_title' => array( 'schema_job_title', 'schema_title', 'seo_title' ),
					'schema_desc'      => array( 'schema_desc', 'seo_desc' ),
					'schema_text'      => array( 'schema_text' ),

					/*
					 * Open Graph and Social metadata keys.
					 */
					'og_title'     => array( 'og_title', 'seo_title' ),
					'og_desc'      => array( 'og_desc', 'seo_desc' ),
					'og_caption'   => array( 'og_caption' ),
					'tc_title'     => array( 'tc_title', 'og_title', 'seo_title' ),
					'tc_desc'      => array( 'tc_desc', 'og_desc', 'seo_desc' ),
					'pin_img_desc' => array( 'pin_img_desc', 'og_desc', 'seo_desc' ),
				),

				/*
				 * Additional fields for the user profile About Yourself / About the user sections.
				 */
				'user_about' => array(
					'job_title'        => 'Job Title',
					'honorific_prefix' => 'Honorific Prefix',
					'honorific_suffix' => 'Honorific Suffix',
					'additional_name'  => 'Middle or Additional Name',
					'award'            => 'Awards',
				),

				'site_verify_meta_names' => array(
					'ahrefs_site_verify' => 'ahrefs-site-verification',	// Ahrefs Website Verification ID.
					'baidu_site_verify'  => 'baidu-site-verification',	// Baidu Website Verification ID.
					'fb_site_verify'     => 'facebook-domain-verification',	// Facebook Domain Verification ID.
					'g_site_verify'      => 'google-site-verification',	// Google Website Verification ID.
					'bing_site_verify'   => 'msvalidate.01',		// Microsoft Bing Website Verification ID.
					'pin_site_verify'    => 'p:domain_verify',		// Pinterest Website Verification ID.
					'yandex_site_verify' => 'yandex-verification',		// Yandex Website Verification ID.
				),
			),	// End of 'opt' array.

			/*
			 * Update manager.
			 */
			'um' => array(
				'min_version' => '6.0.1',	// Released on 2024/08/27.
				'rec_version' => '7.2.0',	// Released on 2024/09/14.
				'version_filter' => array(
					'dev'    => 'Development and Up',
					'alpha'  => 'Alpha and Up',
					'beta'   => 'Beta and Up',
					'rc'     => 'Release Candidate and Up',
					'stable' => 'Stable / Production',
				),
				'version_regex' => array(
					'dev'    => '/^[0-9][0-9\.\-]+((dev|a|alpha|b|beta|rc)[0-9\.\-]+)?$/',
					'alpha'  => '/^[0-9][0-9\.\-]+((a|alpha|b|beta|rc)[0-9\.\-]+)?$/',
					'beta'   => '/^[0-9][0-9\.\-]+((b|beta|rc)[0-9\.\-]+)?$/',
					'rc'     => '/^[0-9][0-9\.\-]+((rc)[0-9\.\-]+)?$/',
					'stable' => '/^[0-9][0-9\.\-]+$/',
				),
			),	// End of 'um' array.

			/*
			 * PHP.
			 */
			'php' => array(
				'label'       => 'PHP',
				'min_version' => '7.4.33',	// Hard limit - deactivate the plugin when activating.
				'rec_version' => '7.4.33',	// Soft limit - issue warning if lower version found.
				'version_url' => 'https://www.php.net/supported-versions.php',
				'extensions'  => array(
					'curl' => array(	// PHP extension name.
						'label' => 'Client URL Library (cURL)',
						'url'   => 'https://www.php.net/manual/en/book.curl.php',
					),
					'dom' => array(		// PHP extension name.
						'label' => 'Document Object Model',
						'url'   => 'https://www.php.net/manual/en/book.dom.php',
						'classes' => array(	// Extra checks to make sure the PHP extension is complete.
							'DOMDocument',
						),
					),
					'gd' => array(		// PHP extension name.
						'label' => 'Image Processing (GD)',
						'url'   => 'https://www.php.net/manual/en/book.image.php',
						'wp_image_editor' => array(
							'class' => 'WP_Image_Editor_GD',
							'url'   => 'https://developer.wordpress.org/reference/classes/wp_image_editor_gd/',
						),
					),
					'json' => array(	// PHP extension name.
						'label' => 'JavaScript Object Notation (JSON)',
						'url'   => 'https://www.php.net/manual/en/book.json.php',
					),
					'libxml' => array(	// PHP extension name.
						'label' => 'libxml',
						'url'   => 'https://www.php.net/manual/en/book.libxml.php',
						'functions' => array(	// Extra checks to make sure the PHP extension is complete.
							'libxml_use_internal_errors',
						),
					),
					'mbstring' => array(	// PHP extension name.
						'label' => 'Multibyte String',
						'url'   => 'https://www.php.net/manual/en/book.mbstring.php',
						'functions' => array(	// Extra checks to make sure the PHP extension is complete.
							'mb_convert_encoding',
							'mb_decode_numericentity',
							'mb_detect_encoding',
							'mb_strlen',
							'mb_strtolower',
							'mb_substr',
						),
					),
					'simplexml' => array(	// PHP extension name.
						'label' => 'SimpleXML',
						'url'   => 'https://www.php.net/manual/en/book.simplexml.php',
						'functions' => array(	// Extra checks to make sure the PHP extension is complete.
							'simplexml_load_string',
						),
					),
					'xml' => array(		// PHP extension name.
						'label' => 'XML Parser',
						'url'   => 'https://www.php.net/manual/en/book.xml.php',
						'functions' => array(	// Extra checks to make sure the PHP extension is complete.
							'utf8_encode',
						),
					),
				),
			),	// End of 'php' array.

			/*
			 * WordPress.
			 */
			'wp' => array(
				'label'       => 'WordPress',
				'min_version' => '5.9',		// Hard limit - deactivate the plugin when activating.
				'rec_version' => '6.6.2',	// Soft limit - issue warning if lower version found.
				'version_url' => 'https://codex.wordpress.org/Supported_Versions?nocache=1',
				'tb_iframe'   => array(		// Thickbox iframe.
					'width'  => 772,	// Url query argument.
					'height' => 550,	// Url query argument.
				),
				'cm_names' => array(
					'aim'    => 'AIM',
					'jabber' => 'Google Talk',
					'yim'    => 'Yahoo Messenger',
				),
				'admin' => array(
					'dashboard' => array(
						'page' => 'index.php',
						'cap'  => 'manage_options',
					),
					'plugins' => array(
						'page' => 'plugins.php',
						'cap'  => 'install_plugins',
					),
					'profile' => array(
						'page' => 'profile.php',
						'cap'  => 'publish_posts',
					),
					'settings' => array(
						'page' => 'options-general.php',
						'cap'  => 'manage_options',
					),
					'sitesubmenu' => array(
						'page' => 'admin.php',
						'cap'  => 'manage_options',
					),
					'submenu' => array(
						'page' => 'admin.php',
						'cap'  => 'manage_options',
					),
					'tools' => array(
						'page' => 'tools.php',
						'cap'  => 'manage_options',
					),
					'users' => array(
						'page' => 'users.php',
						'cap'  => 'list_users',
						'sub'  => array(
							'add-person' => array(
								'cap' => 'create_users',
								'pos' => 2,
							),
						),
					),
				),
				'roles' => array(
					'all' => array(
						'administrator',
						'editor',
						'author',
						'contributor',
						'subscriber',
					),
					'creator' => array(	// Users that can create content.
						'administrator',
						'editor',
						'author',
						'contributor',
					),
					'publisher' => array(	// Users that can publish content.
						'administrator',
						'editor',
						'author',
					),
					'owner' => array(	// Users that can manage content (edit, publish, delete, etc.).
						'administrator',
						'editor',
					),
					'admin' => array(
						'administrator',
					),
					'person' => array(	// Users for Schema Person select.
						'person',
					),
				),

				/*
				 * See WpssoAdmin->get_ext_file_content().
				 */
				'cache' => array(
					'file' => array(
						
						/*
						 * See WpssoAdmin->get_ext_file_content().
						 */
						'wpsso_c_' => array(
							'label'  => 'File Content',
							'value'  => DAY_IN_SECONDS,
							'filter' => 'wpsso_cache_expire_file_content',
						),

						/*
						 * See WpssoProMediaYoutube->filter_video_details().
						 */
						'wpsso_y_' => array(
							'label'  => 'YouTube Video Details',
							'value'  => WEEK_IN_SECONDS,
							'filter' => 'wpsso_cache_expire_youtube_video_details',
						),

						'wpsso_' => array(
							'label' => 'All Files',
						),
					),
					'transient' => array(

						'wpsso_!_' => array(
						),

						/*
						 * See WpssoProMediaFacebook->filter_video_details().
						 * See WpssoProMediaSlideshare->filter_video_details().
						 * See WpssoProMediaVimeo->filter_video_details().
						 * See WpssoProMediaWistia->filter_video_details().
						 * See WpssoProReviewJudgeme->filter_og().
						 * See WpssoProReviewShopperApproved->filter_og().
						 * See WpssoProReviewStamped->filter_og().
						 */
						'wpsso_a_' => array(
							'label'  => 'API Response Info',
							'value'  => DAY_IN_SECONDS,
							'filter' => 'wpsso_cache_expire_api_response',
						),

						/*
						 * See WpssoAdmin->settings_saved_notice().
						 * See WpssoHead->clear_head_array().
						 * See WpssoHead->get_head_array().
						 */
						'wpsso_h_' => array(
							'label'  => 'Document Markup',
							'value'  => MONTH_IN_SECONDS,
							'filter' => 'wpsso_cache_expire_head_markup',

							/*
							 * If the $mod array condition is true, then use the associated value.
							 */
							'conditional_values' => array(
								'is_404'        => 0,
								'is_attachment' => 0,
								'is_date'       => 0,
								'is_search'     => 0,
							),
						),

						'wpsso_i_' => array(
							'label'  => 'Image URL Info',
							'value'  => DAY_IN_SECONDS,
							'filter' => 'wpsso_cache_expire_image_info',
						),

						/*
						 * See WpssoAdmin->get_readme_info().
						 */
						'wpsso_r_' => array(
							'label'  => 'Plugin Readme Info',
							'value'  => DAY_IN_SECONDS,
							'filter' => 'wpsso_cache_expire_readme_info',
						),

						'wpsso_s_' => array(
							'label'  => 'Short URLs Info',
							'value'  => 3 * MONTH_IN_SECONDS,
							'filter' => 'wpsso_cache_expire_short_url',
						),

						/*
						 * See WpssoSchema->get_schema_types().
						 * See WpssoSchema->get_schema_type_child_family().
						 * See WpssoSchema->get_schema_type_children().
						 * See WpssoSchema->get_schema_type_row_class().
						 */
						'wpsso_t_' => array(
							'label'  => 'Schema Types Info',
							'value'  => WEEK_IN_SECONDS,
							'filter' => 'wpsso_cache_expire_schema_types',
						),

						'wpsso_' => array(
							'label' => 'All Transients',
						),
					),
					'wp_cache' => array(

						/*
						 * See WpssoPage->get_the_content().
						 */
						'wpsso_c_' => array(
							'label'   => 'Filtered Content',
							'value'   => HOUR_IN_SECONDS,
							'filter'  => 'wpsso_cache_expire_the_content',
						),

						'wpsso_' => array(
							'label' => 'All WP Objects',
						),
					),
				),	// End of 'cache' array.
			),	// End of 'wp' array.

			/*
			 * Used by WpssoScript->admin_enqueue_scripts().
			 *
			 * See http://qtip2.com/download.
			 */
			'jquery-qtip' => array(
				'label'   => 'jQuery qTip',
				'version' => '3.0.3',
			),

			/*
			 * Required for the datepicker popup.
			 *
			 * See https://developers.google.com/speed/libraries/.
			 *
			 * See WpssoStyle->admin_enqueue_styles().
			 */
			'jquery-ui' => array(
				'label'   => 'jQuery UI',
				'version' => '1.13.3',
			),

			'menu' => array(
				'title'     => 'SSO - Social and Search Optimization',	// Menu title.
				'icon-font' => 'WpssoIcons',				// Icon font family.
				'icon-code' => '\e81e',					// Icon CSS code.
				'dashicons' => array(
					'add-person'    => 'admin-users',
					'addons'        => 'admin-plugins',
					'advanced'      => 'superhero',
					'dashboard'     => 'dashboard',
					'essential'     => 'star-filled',
					'general'       => 'admin-settings',
					'licenses'      => 'admin-network',
					'setup'         => 'welcome-learn-more',
					'debug'         => 'code-standards',
					'site-addons'   => 'admin-plugins',
					'site-licenses' => 'admin-network',
					'site-setup'    => 'welcome-learn-more',
					'site-debug'    => 'code-standards',
					'tools'         => 'admin-tools',
					'your-sso'      => 'id-alt',
					'*'             => 'admin-generic',	// Default icon.
				),
				'must_load' => array(	// Settings pages that cannot be disabled.
					'general'       => true,
					'advanced'      => true,
					'licenses'      => true,
					'site-advanced' => true,
					'site-licenses' => true,
				),
			),
			'notice' => array(
				'title' => 'SSO',	// Notice title.
			),
			'meta' => array(	// Post, term, user add_meta_box() settings.
				'id'    => 'sso',
				'title' => 'Document SSO',
			),
			'edit' => array(	// Post, term, user lists.

				/*
				 * Array is defined by the preferred table column order.
				 */
				'columns' => array(
					'schema_type_name' => array(
						'header'   => 'SSO Schema',
						'mt_name'  => 'schema:type:name',
						'meta_key' => '_wpsso_head_info_schema_type_name',
						'orderby'  => 'meta_value',
						'width'    => '10em',
						'height'   => 'auto',
						'def_val'  => 'none',
					),
					'schema_lang' => array(
						'header'    => 'SSO Lang',
						'mt_name'   => 'schema:language',
						'meta_key'  => '_wpsso_head_info_schema_lang',
						'orderby'   => 'meta_value',
						'width'     => '8em',
						'height'    => 'auto',
						'def_val'   => 'none',
					),
					'og_img' => array(
						'header'    => 'SSO Image',
						'mt_name'   => 'og:image',
						'meta_key'  => '_wpsso_head_info_og_img_thumb',
						'orderby'   => false,	// Do not offer column sorting.
						'width'     => '75px',
						'height'    => '40px',
						'def_val'   => 'none',
					),
					'og_desc' => array(
						'header'    => 'SSO Desc',
						'mt_name'   => 'og:description',
						'meta_key'  => '_wpsso_head_info_og_desc',
						'orderby'   => true,
						'width'     => '200px',
						'height'    => 'auto',
						'def_val'   => 'none',
					),
					'og_type' => array(	// Open Graph Type ID.
						'mt_name'  => 'og:type',
						'meta_key' => '_wpsso_head_info_og_type',
						'def_val'  => 'none',
					),
					'schema_type' => array(	// Schema Type ID.
						'mt_name'  => 'schema:type:id',
						'meta_key' => '_wpsso_head_info_schema_type',
						'def_val'  => 'none',
					),
					'is_noindex' => array(
						'mt_name'  => 'is_noindex',
						'meta_key' => '_wpsso_head_info_is_noindex',
						'def_val'  => '0',
					),
					'is_redirect' => array(
						'mt_name'  => 'is_redirect',
						'meta_key' => '_wpsso_head_info_is_redirect',
						'def_val'  => '0',
					),
				),
			),
			'form' => array(
				'max_hashtags'    => 5,
				'max_media_items' => 5,
				'tooltip_class'   => 'sucom-tooltip',
				'yes_no' => array(
					1 => 'Yes',
					0 => 'No',
				),
				'true_false' => array(
					1 => 'True',
					0 => 'False',
				),
				'on_off' => array(
					1 => 'On',
					0 => 'Off',
				),
				'weekdays' => array(
					'sunday'         => 'Sunday',
					'monday'         => 'Monday',
					'tuesday'        => 'Tuesday',
					'wednesday'      => 'Wednesday',
					'thursday'       => 'Thursday',
					'friday'         => 'Friday',
					'saturday'       => 'Saturday',
					'publicholidays' => 'Public Holidays',
				),
				'time_seconds' => array(
					'hour'  => HOUR_IN_SECONDS,
					'day'   => DAY_IN_SECONDS,
					'week'  => WEEK_IN_SECONDS,
					'month' => MONTH_IN_SECONDS,
					'year'  => YEAR_IN_SECONDS,
				),
				'time_text' => array(
					'hour'  => 'Hour',
					'day'   => 'Day',
					'week'  => 'Week',
					'month' => 'Month',
					'year'  => 'Year',
				),
				'qualifiers' => array(
					'(default)',
					'(no images)',
					'(no videos)',
					'(settings value)',
					'(option disabled)',
					'%1$s (default is %2$s)',
					'default is %s',
					'checked',
					'unchecked',
					'Enabled',
					'Disabled',
					'enabled',
					'disabled',
					'at',
					'tz',

					/*
					 * See SucomForm->get_checklist_post_types().
					 * See SucomForm->get_checklist_post_tax_user_values().
					 */
					'Post Type',
					'Taxonomy',
					'User Profiles',
				),
				'document_title' => array(	// Webpage Title Tag.
					'wp_title'         => '[WordPress Title]',
					'seo_title'        => 'SEO Title Tag',
					'schema_title'     => 'Schema Name',
					'schema_title_alt' => 'Schema Alternate Name',
				),
				'show_options' => array(
					'basic' => 'Basic Options',
					'all'   => 'All Options',
				),
				'site_option_use' => array(	// Site Use.
					'default' => 'First Activation',
					'empty'   => 'If Option Is Empty',
					'force'   => 'Always Force Value',
				),
				'script_locations' => array(
					'none'   => '[None]',
					'header' => 'Header',
					'footer' => 'Footer',
				),
				'caption_types' => array(
					'none'    => '[None]',
					'title'   => 'Title Only',
					'excerpt' => 'Excerpt Only',
					'both'    => 'Title and Excerpt',
				),
				'author_types' => array(
					'none'         => '[None]',
					'organization' => 'Organization',
					'person'       => 'Person',
				),
				'publisher_types' => array(
					'organization' => 'Organization',
					'person'       => 'Person',
				),
				'user_name_fields' => array(
					'none'         => '[None]',
					'fullname'     => 'First and Last Names',
					'display_name' => 'Display Name',
					'nickname'     => 'Nickname',
				),
				'addr_select' => array(
					'none'   => '[None]',
					'custom' => '[Custom Address]',
					'new'    => '[New Address]',
				),
				'org_select' => array(
					'none' => '[None]',
					'new'  => '[New Organization]',
					'site' => '[Site Organization]',
				),
				'og_schema_types_select_format' => array(
					'id'      => 'ID',
					'id_url'  => 'ID | Host/Name',
					'id_name' => 'ID | Name',
					'name_id' => 'Name [ID]',
					'name'    => 'Name',
				),

				/*
				 * Image preferred cropping.
				 */
				'position_crop_x' => array(
					'left'   => 'Left',
					'center' => 'Center',
					'right'  => 'Right',
				),
				'position_crop_y' => array(
					'top' => 'Top',
					'center' => 'Center',
					'bottom' => 'Bottom',
				),

				/*
				 * Robots meta.
				 *
				 * See https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag#max-image-preview.
				 */
				'robots_max_image_preview' => array(
					'none'     => '[None]',
					'standard' => 'Standard',
					'large'    => 'Large',
				),

				/*
				 * Breadcrumbs settings.
				 */
				'breadcrumbs_for_posts' => array(	// Breadcrumbs by Post Type.
					'none'        => '[No Breadcrumbs]',
					'categories'  => 'Home Page(s) and Categories',
					'ancestors'   => 'Home Page(s) and Parents',
					'primary_cat' => 'Home Page(s) and Primary Category',
				),
				'breadcrumbs_for_terms' => array(	// Breadcrumbs by Taxonomy.
					'none'      => '[No Breadcrumbs]',
					'ancestors' => 'Home Page(s) and Parents',
				),

				/*
				 * Shortening service.
				 */
				'shorteners' => array(
					'none'    => '[None]',
					'bitly'   => 'Bitly',		// Requires lib/pro/com/bitly.php.
					'dlmyapp' => 'DLMY.App',	// Requires lib/pro/ext/dlmy.php.
					'owly'    => 'Ow.ly',		// Requires lib/pro/ext/owly.php.
					'tinyurl' => 'TinyURL',		// Requires lib/pro/ext/tinyurl.php.
					'yourls'  => 'YOURLS',		// Requires lib/pro/ext/yourls.php.
				),

				/*
				 * Ratings and reviews services.
				 */
				'ratings_reviews' => array(
					'none'            => '[None]',
					'judgeme'         => 'Judge.me',
					'shopperapproved' => 'Shopper Approved',
					'stamped'         => 'Stamped.io',
				),

				/*
				 * Social account keys and labels for Organization SameAs.
				 */
				'social_accounts' => array(
					'behance_publisher_url'   => 'Behance Business Profile URL',
					'fb_publisher_url'        => 'Facebook Business Page URL',
					'instagram_publisher_url' => 'Instagram Business Profile URL',
					'linkedin_publisher_url'  => 'LinkedIn Business Page URL',
					'medium_publisher_url'    => 'Medium Business Page URL',
					'myspace_publisher_url'   => 'Myspace Business Page URL',
					'pin_publisher_url'       => 'Pinterest Business Page URL',
					'sc_publisher_url'        => 'Soundcloud Business Page URL',
					'tiktok_publisher_url'    => 'TikTok Business Page URL',
					'tumblr_publisher_url'    => 'Tumblr Business Page URL',
					'wikipedia_publisher_url' => 'Wikipedia Organization Page URL',
					'tc_site'                 => 'X (Twitter) Business @username',
					'yt_publisher_url'        => 'YouTube Business Channel URL',
				),

				'embed_media' => array(	// Check for Embedded Media.
					'plugin_media_facebook'         => 'Facebook Videos',
					'plugin_media_slideshare'       => 'Slideshare Presentations',
					'plugin_media_soundcloud'       => 'Soundcloud Tracks',
					'plugin_media_vimeo'            => 'Vimeo Videos',
					'plugin_media_wistia'           => 'Wistia Videos',
					'plugin_media_wpvideoblock'     => 'WP Media Library Video Blocks',
					'plugin_media_wpvideoshortcode' => 'WP Media Library Video Shortcodes',
					'plugin_media_youtube'          => 'YouTube Videos and Playlists',
				),

				/*
				 * Attribute option labels.
				 */
				'attr_labels' => array(
					'plugin_attr_product_adult_type'         => 'Adult Type Attribute',
					'plugin_attr_product_age_group'          => 'Age Group Attribute',
					'plugin_attr_product_brand'              => 'Brand Attribute',
					'plugin_attr_product_color'              => 'Color Attribute',
					'plugin_attr_product_condition'          => 'Condition Attribute',
					'plugin_attr_product_energy_efficiency'  => 'Energy Rating Attribute',
					'plugin_attr_product_fluid_volume_value' => 'Fluid Volume Attribute',
					'plugin_attr_product_gtin14'             => 'GTIN-14 Attribute',
					'plugin_attr_product_gtin13'             => 'GTIN-13 (EAN) Attribute',
					'plugin_attr_product_gtin12'             => 'GTIN-12 (UPC) Attribute',
					'plugin_attr_product_gtin8'              => 'GTIN-8 Attribute',
					'plugin_attr_product_gtin'               => 'GTIN Attribute',
					'plugin_attr_product_height_value'       => 'Net Height Attribute',
					'plugin_attr_product_isbn'               => 'ISBN Attribute',
					'plugin_attr_product_length_value'       => 'Net Len. / Depth Attribute',
					'plugin_attr_product_material'           => 'Material Attribute',
					'plugin_attr_product_mfr_part_no'        => 'MPN Attribute',
					'plugin_attr_product_pattern'            => 'Pattern Attribute',
					'plugin_attr_product_size'               => 'Size Attribute',
					'plugin_attr_product_size_group'         => 'Size Group Attribute',
					'plugin_attr_product_size_system'        => 'Size System Attribute',
					'plugin_attr_product_target_gender'      => 'Target Gender Attribute',
					'plugin_attr_product_weight_value'       => 'Net Weight Attribute',
					'plugin_attr_product_width_value'        => 'Net Width Attribute',
				),

				/*
				 * Custom field option labels.
				 */
				'cf_labels' => array(
					'plugin_cf_addl_type_urls'                => 'Microdata Type URLs Custom Field',
					'plugin_cf_book_isbn'                     => 'Book ISBN Custom Field',
					'plugin_cf_img_url'                       => 'Image URL Custom Field',
					'plugin_cf_product_adult_type'            => 'Product Adult Type Custom Field',
					'plugin_cf_product_age_group'             => 'Product Age Group Custom Field',
					'plugin_cf_product_avail'                 => 'Product Availability Custom Field',
					'plugin_cf_product_brand'                 => 'Product Brand Custom Field',
					'plugin_cf_product_category'              => 'Product Google Cat. ID Custom Field',
					'plugin_cf_product_color'                 => 'Product Color Custom Field',
					'plugin_cf_product_condition'             => 'Product Condition Custom Field',
					'plugin_cf_product_currency'              => 'Product Currency Custom Field',
					'plugin_cf_product_energy_efficiency'     => 'Product Energy Rating Custom Field',
					'plugin_cf_product_fluid_volume_value'    => 'Product Fluid Volume Custom Field',
					'plugin_cf_product_gtin14'                => 'Product GTIN-14 Custom Field',
					'plugin_cf_product_gtin13'                => 'Product GTIN-13 (EAN) Custom Field',
					'plugin_cf_product_gtin12'                => 'Product GTIN-12 (UPC) Custom Field',
					'plugin_cf_product_gtin8'                 => 'Product GTIN-8 Custom Field',
					'plugin_cf_product_gtin'                  => 'Product GTIN Custom Field',
					'plugin_cf_product_isbn'                  => 'Product ISBN Custom Field',
					'plugin_cf_product_height_value'          => 'Product Net Height Custom Field',
					'plugin_cf_product_length_value'          => 'Product Net Len. / Depth Custom Field',
					'plugin_cf_product_material'              => 'Product Material Custom Field',
					'plugin_cf_product_mfr_part_no'           => 'Product MPN Custom Field',
					'plugin_cf_product_min_advert_price'      => 'Product Min Advert Price Custom Field',
					'plugin_cf_product_pattern'               => 'Product Pattern Custom Field',
					'plugin_cf_product_price'                 => 'Product Price Custom Field',
					'plugin_cf_product_price_type'            => 'Product Price Type Custom Field',
					'plugin_cf_product_retailer_part_no'      => 'Product SKU Custom Field',
					'plugin_cf_product_shipping_height_value' => 'Product Shipping Height Custom Field',
					'plugin_cf_product_shipping_length_value' => 'Product Shipping Length Custom Field',
					'plugin_cf_product_shipping_weight_value' => 'Product Shipping Weight Custom Field',
					'plugin_cf_product_shipping_width_value'  => 'Product Shipping Width Custom Field',
					'plugin_cf_product_size'                  => 'Product Size Custom Field',
					'plugin_cf_product_size_group'            => 'Product Size Group Custom Field',
					'plugin_cf_product_size_system'           => 'Product Size System Custom Field',
					'plugin_cf_product_target_gender'         => 'Product Target Gender Custom Field',
					'plugin_cf_product_weight_value'          => 'Product Net Weight Custom Field',
					'plugin_cf_product_width_value'           => 'Product Net Width Custom Field',
					'plugin_cf_review_item_name'              => 'Review Subject Name Custom Field',
					'plugin_cf_review_item_desc'              => 'Review Subject Desc Custom Field',
					'plugin_cf_review_rating'                 => 'Review Rating Custom Field',
					'plugin_cf_review_rating_alt_name'        => 'Review Rating Alt Name Custom Field',
					'plugin_cf_sameas_urls'                   => 'Same-As URLs Custom Field',
					'plugin_cf_vid_url'                       => 'Video URL Custom Field',
					'plugin_cf_vid_embed'                     => 'Video Embed HTML Custom Field',
				),

				/*
				 * Used with array_intersect_key() to determine which metadata keys can be inherited.
				 *
				 * See WpssoAbstractWpMeta->get_inherited_md_opts().
				 */
				'inherit_md_opts' => array(

					/*
					 * Inherited image options.
					 */
					'og_img_max'        => null,
					'og_img_id'         => null,
					'og_img_id_lib'     => null,
					'og_img_url'        => null,
					'pin_img_id'        => null,
					'pin_img_id_lib'    => null,
					'pin_img_url'       => null,
					'schema_img_id'     => null,
					'schema_img_id_lib' => null,
					'schema_img_url'    => null,
					'tc_lrg_img_id'     => null,
					'tc_lrg_img_id_lib' => null,
					'tc_lrg_img_url'    => null,
					'tc_sum_img_id'     => null,
					'tc_sum_img_id_lib' => null,
					'tc_sum_img_url'    => null,

					/*
					 * Inherited product options.
					 */
					'product_adult_type'            => null,
					'product_age_group'             => null,
					'product_brand'                 => null,
					'product_category'              => null,	// Product Google Category.
					'product_energy_efficiency'     => null,
					'product_energy_efficiency_min' => null,
					'product_energy_efficiency_max' => null,
					'product_min_advert_price'      => null,
					'product_mrp'                   => null,	// Product Return Policy.
					'product_price_type'            => null,
					'product_size_group_0'          => null,
					'product_size_group_1'          => null,
					'product_size_system'           => null,
					'product_target_gender'         => null,

					/*
					 * Inherited Schema options.
					 */
					'schema_article_section' => null,
				),

				/*
				 * Inherit required properties from the product group for Google.
				 *
				 * Use an associative array to avoid property names from being translated.
				 *
				 * See WpssoJsonTypeProductGroup->filter_json_data_https_schema_org_productgroup().
				 */
				'inherit_variant_props' => array(
					'name'        => null,
					'description' => null,
					'brand'       => null,
					'review'      => null,
				),
				'excl_varies_by_props' => array(
					'@id'                  => null,
					'@context'             => null,
					'@type'                => null,
					'url'                  => null,
					'name'                 => null,
					'description'          => null,
					'image'                => null,
					'subjectOf'            => null,
					'inProductGroupWithID' => null,
				),

				/*
				 * Validated on 2022/12/26.
				 *
				 * Used for the Schema Product and Offer 'hasAdultConsideration' property.
				 *
				 * See https://schema.org/AdultOrientedEnumeration.
				 * See https://support.google.com/merchants/answer/6324508.
				 */
				'adult_type' => array(
					'none'                                                        => '[None]',
					'https://schema.org/AlcoholConsideration'                     => 'Alcohol',
					'https://schema.org/DangerousGoodConsideration'               => 'Dangerous Good',
					'https://schema.org/HealthcareConsideration'                  => 'Healthcare',
					'https://schema.org/NarcoticConsideration'                    => 'Narcotic',
					'https://schema.org/ReducedRelevanceForChildrenConsideration' => 'Reduced Relevance for Children',
					'https://schema.org/SexualContentConsideration'               => 'Sexual Content',
					'https://schema.org/TobaccoNicotineConsideration'             => 'Tobacco Nicotine',
					'https://schema.org/UnclassifiedAdultConsideration'           => 'Unclassified Adult',
					'https://schema.org/ViolenceConsideration'                    => 'Violence',
					'https://schema.org/WeaponConsideration'                      => 'Weapon',
				),

				/*
				 * Validated on 2022/09/24.
				 *
				 * See https://developers.facebook.com/docs/marketing-api/catalog/reference/.
				 * See https://support.google.com/merchants/answer/6324463.
				 */
				'age_group' => array(
					'none'     => '[None]',
					'adult'    => 'Adult (13 years old or more)',
					'all ages' => 'All Ages (13 years old or more)',
					'infant'   => 'Infant (3–12 months old)',
					'kids'     => 'Kids (5–13 years old)',
					'newborn'  => 'Newborn (0-3 months old)',
					'teen'     => 'Teen (13 years old or more)',
					'toddler'  => 'Toddler (1–5 years old)',
				),

				/*
				 * Validated on 2022/12/26.
				 *
				 * See https://schema.org/BookFormatType.
				 */
				'book_format' => array(
					'none'                               => '[None]',
					'https://schema.org/AudiobookFormat' => 'Audiobook',
					'https://schema.org/EBook'           => 'eBook',
					'https://schema.org/GraphicNovel'    => 'Graphic Novel',
					'https://schema.org/Hardcover'       => 'Hardcover',
					'https://schema.org/Paperback'       => 'Paperback',
				),

				/*
				 * Validated on 2020/08/17.
				 *
				 * See https://developers.google.com/search/docs/data-types/job-postings.
				 */
				'employment_type' => array(
					'none'       => '[None]',
					'FULL_TIME'  => 'Full Time',
					'PART_TIME'  => 'Part Time',
					'CONTRACTOR' => 'Contractor',
					'TEMPORARY'  => 'Temporary',
					'INTERN'     => 'Intern',
					'VOLUNTEER'  => 'Volunteer',
					'PER_DIEM'   => 'Per Diem',
					'OTHER'      => 'Other',
				),

				/*
				 * Validated on 2022/12/26.
				 *
				 * See https://schema.org/EUEnergyEfficiencyEnumeration.
				 * See https://support.google.com/merchants/answer/7562785.
				 */
				'energy_efficiency' => array(
					'none'                                                => '[None]',
					'https://schema.org/EUEnergyEfficiencyCategoryA3Plus' => 'A+++',
					'https://schema.org/EUEnergyEfficiencyCategoryA2Plus' => 'A++',
					'https://schema.org/EUEnergyEfficiencyCategoryA1Plus' => 'A+',
					'https://schema.org/EUEnergyEfficiencyCategoryA'      => 'A',
					'https://schema.org/EUEnergyEfficiencyCategoryB'      => 'B',
					'https://schema.org/EUEnergyEfficiencyCategoryC'      => 'C',
					'https://schema.org/EUEnergyEfficiencyCategoryD'      => 'D',
					'https://schema.org/EUEnergyEfficiencyCategoryE'      => 'E',
					'https://schema.org/EUEnergyEfficiencyCategoryF'      => 'F',
					'https://schema.org/EUEnergyEfficiencyCategoryG'      => 'G',
				),

				/*
				 * Validated on 2022/12/26.
				 *
				 * Used by WpssoSchema->filter_sanitize_md_options().
				 *
				 * See https://schema.org/EventAttendanceModeEnumeration.
				 */
				'event_attendance' => array(
					'none'                                          => '[None]',
					'https://schema.org/MixedEventAttendanceMode'   => 'Mixed',
					'https://schema.org/OnlineEventAttendanceMode'  => 'Online',
					'https://schema.org/OfflineEventAttendanceMode' => 'Physical Location',
				),

				/*
				 * Validated on 2022/12/26.
				 *
				 * Used by WpssoSchema->filter_sanitize_md_options().
				 *
				 * See https://schema.org/EventStatusType.
				 */
				'event_status' => array(
					'none'                                => '[None]',
					'https://schema.org/EventCancelled'   => 'Cancelled',
					'https://schema.org/EventMovedOnline' => 'Moved Online',
					'https://schema.org/EventPostponed'   => 'Postponed',
					'https://schema.org/EventRescheduled' => 'Rescheduled',
					'https://schema.org/EventScheduled'   => 'Scheduled',	// Default.
				),

				/*
				 * Validated on 2022/12/26.
				 *
				 * Used by WpssoSchema->filter_sanitize_md_options().
				 *
				 * See https://schema.org/ItemAvailability.
				 */
				'item_availability' => array(
					'none'                                   => '[None]',
			 		'https://schema.org/BackOrder'           => 'Back Order',	// Indicates that the item is available on back order.
			 		'https://schema.org/Discontinued'        => 'Discontinued',
			 		'https://schema.org/InStock'             => 'In Stock',
			 		'https://schema.org/InStoreOnly'         => 'In Store Only',
			 		'https://schema.org/LimitedAvailability' => 'Limited Availability',
			 		'https://schema.org/OnlineOnly'          => 'Online Only',
			 		'https://schema.org/OutOfStock'          => 'Out Of Stock',
			 		'https://schema.org/PreOrder'            => 'Pre-Order',
			 		'https://schema.org/PreSale'             => 'Pre-Sale',
			 		'https://schema.org/SoldOut'             => 'Sold Out',
				),

				/*
				 * Validated on 2022/12/26.
				 *
				 * Used by WpssoSchema->filter_sanitize_md_options().
				 *
				 * See https://schema.org/OfferItemCondition.
				 * See https://support.google.com/merchants/answer/6324469.
				 */
				'item_condition' => array(
					'none'                                    => '[None]',
					'https://schema.org/DamagedCondition'     => 'Damaged',
					'https://schema.org/NewCondition'         => 'New',
					'https://schema.org/RefurbishedCondition' => 'Refurbished',
					'https://schema.org/UsedCondition'        => 'Used',
				),

				/*
				 * Validated on 2021/04/15.
				 *
				 * See https://developers.google.com/search/docs/data-types/job-postings.
				 */
				'job_location_type' => array(
					'none'        => '[None]',
					'TELECOMMUTE' => 'Telecommute (100% Remote)',
				),

				/*
				 * Validated on 2024/07/31.
				 *
				 * See https://schema.org/MerchantReturnEnumeration.
				 * See https://developers.google.com/search/docs/appearance/structured-data/merchant-listing#returns.
				 */
				'merchant_return' => array(
					'https://schema.org/MerchantReturnFiniteReturnWindow' => 'Limited Return Window',
					'https://schema.org/MerchantReturnNotPermitted'       => 'Not Permitted',
					'https://schema.org/MerchantReturnUnlimitedWindow'    => 'Unlimited Window',
					'https://schema.org/MerchantReturnUnspecified'        => 'Unspecified',
				),

				/*
				 * Validated on 2022/09/14.
				 *
				 * Used by WpssoSchema->filter_sanitize_md_options().
				 *
				 * See https://schema.org/PriceTypeEnumeration.
				 */
				'price_type' => array(
					'none'                                      => '[None]',
					'https://schema.org/InvoicePrice'           => 'Invoice Price',
					'https://schema.org/ListPrice'              => 'List Price',
					'https://schema.org/MSRP'                   => 'Manufacturer Suggested Retail Price',
					'https://schema.org/MinimumAdvertisedPrice' => 'Minimum Advertised Price',
					'https://schema.org/SalePrice'              => 'Sale Price',
					'https://schema.org/SRP'                    => 'Suggested Retail Price',
				),

				/*
				 * Validated on 2024/07/31.
				 *
				 * See https://schema.org/ReturnFeesEnumeration.
				 * See https://developers.google.com/search/docs/appearance/structured-data/merchant-listing#returns.
				 */
				'return_fees' => array(
					'https://schema.org/FreeReturn'                       => 'Free Return',
					'https://schema.org/ReturnFeesCustomerResponsibility' => 'Customer Pays Shipping',
					'https://schema.org/ReturnShippingFees'               => 'Return Has Shipping Fees',
				),

				/*
				 * Validated on 2023/06/02.
				 *
				 * See https://schema.org/ReturnMethodEnumeration.
				 * See https://developers.google.com/search/docs/appearance/structured-data/merchant-listing#returns.
				 */
				'return_method' => array(
					'https://schema.org/ReturnAtKiosk' => 'At Kiosk',
					'https://schema.org/ReturnByMail'  => 'By Mail',
					'https://schema.org/ReturnInStore' => 'In Store',
				),

				/*
				 * Validated on 2021/11/09.
				 *
				 * Used by WpssoSchema->filter_sanitize_md_options().
				 *
				 * See https://support.google.com/merchants/answer/6324497.
				 */
				'size_group' => array(
					'none'                                          => '[None]',
					'https://schema.org/WearableSizeGroupRegular'   => 'Regular',
					'https://schema.org/WearableSizeGroupPetite'    => 'Petite',
					'https://schema.org/WearableSizeGroupPlus'      => 'Plus',
					'https://schema.org/WearableSizeGroupTall'      => 'Tall',
					'https://schema.org/WearableSizeGroupBig'       => 'Big',
					'https://schema.org/WearableSizeGroupMaternity' => 'Maternity',
				),

				/*
				 * Validated on 2022/12/26.
				 *
				 * Used by WpssoSchema->filter_sanitize_md_options().
				 *
				 * Not supposed by Google:
				 *
				 *	https://schema.org/WearableSizeSystemContinental
				 *	https://schema.org/WearableSizeSystemEN13402
				 *	https://schema.org/WearableSizeSystemGS1
				 *
				 * See https://support.google.com/merchants/answer/6324502.
				 */
				'size_system' => array(
					'none'                                        => '[None]',
					'https://schema.org/WearableSizeSystemAU'     => 'AU',
					'https://schema.org/WearableSizeSystemBR'     => 'BR',
					'https://schema.org/WearableSizeSystemCN'     => 'CN',
					'https://schema.org/WearableSizeSystemDE'     => 'DE',
					'https://schema.org/WearableSizeSystemEurope' => 'EU',
					'https://schema.org/WearableSizeSystemFR'     => 'FR',
					'https://schema.org/WearableSizeSystemIT'     => 'IT',
					'https://schema.org/WearableSizeSystemJP'     => 'JP',
					'https://schema.org/WearableSizeSystemMX'     => 'MX',
					'https://schema.org/WearableSizeSystemUK'     => 'UK',
					'https://schema.org/WearableSizeSystemUS'     => 'US',
				),

				/*
				 * Validated on 2020/08/17.
				 *
				 * See https://developers.facebook.com/docs/marketing-api/catalog/reference/.
				 * See https://schema.org/suggestedGender.
				 * See https://support.google.com/merchants/answer/6324479.
				 */
				'target_gender' => array(
					'none'   => '[None]',
					'female' => 'Female',
					'male'   => 'Male',
					'unisex' => 'Unisex',
				),

				/*
				 * The default warning is 90% of the maximum length.
				 *
				 * See sucomTextLen() in wpsso/live/js/com/jquery-admin-page.js.
				 */
				'input_limits' => array(

					/*
					 * While Google does not specify a recommended length for title tags, most desktop and
					 * mobile browsers are able to display the first 50–60 characters of a title tag. If you
					 * keep your titles under 60 characters, our research suggests that you can expect about
					 * 90% of your titles to display properly in the SERPs. (There's no exact character limit
					 * because characters can vary in pixel width. Google SERPs can usually display up to 600
					 * pixels.) While writing concise titles is important for human readability and
					 * comprehension, Google’s spiders will take into account the entire title tag (within
					 * reason) when they crawl the page, even if it is not displayed in full in the SERPs.
					 *
					 * See https://moz.com/learn/seo/title-tag.
					 */
					'seo_title' => array(
						'min'  => 10,
						'warn' => 60,
						'max'  => 70,
					),

					/*
					 * Meta descriptions can technically be any length, but Google generally truncates snippets
					 * to ~155-160 characters. It's best to keep meta descriptions long enough that they're
					 * sufficiently descriptive, so we recommend descriptions between 50 and 160 characters.
					 *
					 * See https://moz.com/learn/seo/meta-description.
					 */
					'seo_desc' => array(
						'min'  => 50,
						'warn' => 150,
						'max'  => 180,
					),

					/*
					 * Keep it short to prevent overflow. There’s no official guidance on this, but 40
					 * characters for mobile and 60 for desktop is roughly the sweet spot.
					 *
					 * See https://ahrefs.com/blog/open-graph-meta-tags/.
					 */
					'og_title' => array(
						'warn' => 40,
						'max'  => 70,
					),

					/*
					 * Keep it short and sweet. Facebook recommends 2–4 sentences, but that often truncates.
					 *
					 * See https://ahrefs.com/blog/open-graph-meta-tags/.
					 */
					'og_desc' => array(
						'warn' => 200,
						'max'  => 300,
					),
					'schema_title' => array(
						'min'  => 10,
						'warn' => 60,
						'max'  => 70,
					),
					'schema_title_alt' => array(
						'max' => 110,
					),
					'schema_title_bc' => array(
						'max' => 70,
					),

					/*
					 * The headline of the article. Headlines should not exceed 110 characters. For AMP
					 * stories, the headline should match the text in the first cover page in the AMP Story.
					 *
					 * See https://developers.google.com/search/docs/advanced/structured-data/article.
					 */
					'schema_headline' => array(
						'max' => 110,
					),
					'schema_desc' => array(
						'min'  => 50,
						'warn' => 150,
						'max'  => 300,
					),
					'schema_text' => array(
						'max' => 10000,
					),
					'pin_img_desc' => array(
						'warn' => 100,
						'max'  => 300,
					),
					'tc_title' => array(
						'max' => 70,
					),
					'tc_desc' => array(
						'max' => 200,
					),
				),
			),	// End of 'form' array.

			/*
			 * Reference for meta tag and Schema property values (limits, conversions, etc.).
			 */
			'head' => array(
				'limit' => array(
					'schema_1x1_img_ratio'  => 1.000,
					'schema_4x3_img_ratio'  => 1.333,
					'schema_16x9_img_ratio' => 1.778,
					'org_banner_width'      => 600,
					'org_banner_height'     => 60,
				),
				'limit_min' => array(
					'og_desc_len'            => 160,
					'og_img_width'           => 200,
					'og_img_height'          => 200,
					'schema_desc_len'        => 156,
					'seo_desc_len'           => 156,
					'tc_desc_len'            => 160,
					'tc_sum_img_width'       => 144,
					'tc_sum_img_height'      => 144,
					'tc_lrg_img_width'       => 300,
					'tc_lrg_img_height'      => 157,
					'schema_1x1_img_width'   => 1200,
					'schema_1x1_img_height'  => 1200,
					'schema_4x3_img_width'   => 1200,
					'schema_4x3_img_height'  => 900,
					'schema_16x9_img_width'  => 1200,
					'schema_16x9_img_height' => 675,
					'thumb_img_width'        => 300,
					'thumb_img_height'       => 200,
					'org_logo_width'         => 112,
					'org_logo_height'        => 112,
				),
				'limit_max' => array(
					'og_img_ratio'        => 3.000,
					'schema_headline_len' => 110,
					'tc_sum_img_width'    => 4096,
					'tc_sum_img_height'   => 4096,
					'tc_lrg_img_width'    => 4096,
					'tc_lrg_img_height'   => 4096,
				),

				/*
				 * Hard-code the Open Graph type based on the WordPress post type.
				 *
				 * Example, if the post type is 'product', hard-code the Open Graph type to 'product'.
				 */
				'og_type_by_post_type' => array(
					'book'         => 'book',
					'download'     => 'product',
					'organization' => 'website',
					'place'        => 'place',
					'product'      => 'product',
					'profile'      => 'profile',
					'question'     => 'article',
				),

				/*
				 * Hard-code the Open Graph type based on the Schema type.
				 *
				 * Example, if the Schema type is 'product', hard-code the Open Graph type to 'product'.
				 */
				'og_type_by_schema_type' => array(
					'article'              => 'article',
					'book'                 => 'book',
					'item.list'            => 'website',
					'place'                => 'place',	// Check for Schema place before Schema organization.
					'organization'         => 'website',	// Check for Schema place before Schema organization.
					'product'              => 'product',	// Allows for product offer options.
					'question'             => 'article',
					'review'               => 'article',
					'software.application' => 'product',	// Allows for product offer options.
					'webpage.profile'      => 'profile',
					'website'              => 'website',
				),

				'og_type_ns' => array(		// See https://ogp.me/#types.
					'article'             => 'https://ogp.me/ns/article#',
					'book'                => 'https://ogp.me/ns/book#',
					'books.author'        => 'https://ogp.me/ns/books#',
					'books.book'          => 'https://ogp.me/ns/books#',
					'books.genre'         => 'https://ogp.me/ns/books#',
					'business.business'   => 'https://ogp.me/ns/business#',
					'music.album'         => 'https://ogp.me/ns/music#',
					'music.playlist'      => 'https://ogp.me/ns/music#',
					'music.radio_station' => 'https://ogp.me/ns/music#',
					'music.song'          => 'https://ogp.me/ns/music#',
					'place'               => 'https://ogp.me/ns/place#',	// Supported by Facebook and Pinterest.
					'product'             => 'https://ogp.me/ns/product#',	// Supported by Facebook and Pinterest.
					'profile'             => 'https://ogp.me/ns/profile#',
					'video.episode'       => 'https://ogp.me/ns/video#',
					'video.movie'         => 'https://ogp.me/ns/video#',
					'video.other'         => 'https://ogp.me/ns/video#',
					'video.tv_show'       => 'https://ogp.me/ns/video#',
					'website'             => 'https://ogp.me/ns/website#',
				),
				'og_type_ns_compat' => array(
					'article' => 'https://ogp.me/ns/article#',
					'book'    => 'https://ogp.me/ns/book#',
					'place'   => 'https://ogp.me/ns/place#',	// Supported by Facebook and Pinterest.
					'product' => 'https://ogp.me/ns/product#',	// Supported by Facebook and Pinterest.
					'profile' => 'https://ogp.me/ns/profile#',
					'website' => 'https://ogp.me/ns/website#',
				),

				/*
				 * An array of Open Graph types, their meta tags, and their associated metadata keys.
				 *
				 * See WpssoOpengraph->add_data_og_type_md().
				 * See WpssoOpengraph->sanitize_mt_array().
				 * See https://developers.facebook.com/docs/reference/opengraph/.
				 */
				'og_type_mt' => array(

					/*
					 * See https://developers.facebook.com/docs/reference/opengraph/object-type/article/.
					 */
					'article' => array(
						'article:author'          => '',
						'article:publisher'       => '',
						'article:published_time'  => '',
						'article:modified_time'   => '',
						'article:expiration_time' => '',
						'article:section'         => 'schema_article_section',
						'article:tag'             => '',
					),
					'book' => array(
						'book:author'       => 'schema_book_author_url',
						'book:isbn'         => 'schema_book_isbn',
						'book:release_date' => '',
						'book:tag'          => '',
					),
					'books.author' => array(
						'books:book'          => '',
						'books:gender'        => '',
						'books:genre'         => '',
						'books:official_site' => '',
					),
					'books.book' => array(
						'books:author'                  => '',
						'books:genre'                   => '',
						'books:initial_release_date'    => '',
						'books:isbn'                    => '',
						'books:language:locale'         => '',
						'books:language:alternate'      => '',
						'books:page_count'              => '',
						'books:rating:value'            => '',
						'books:rating:scale'            => '',
						'books:rating:normalized_value' => '',
						'books:release_date'            => '',
						'books:sample'                  => '',
					),
					'books.genre' => array(
						'books:author'         => '',
						'books:book'           => '',
						'books:canonical_name' => '',
					),
					'business.business' => array(
						'business:contact_data:street_address' => '',
						'business:contact_data:locality'       => '',
						'business:contact_data:region'         => '',
						'business:contact_data:postal_code'    => '',
						'business:contact_data:country_name'   => '',
						'business:contact_data:email'          => '',
						'business:contact_data:phone_number'   => '',
						'business:contact_data:phone_number'   => '',
						'business:contact_data:website'        => '',
						'business:hours:day'                   => '',
						'business:hours:start'                 => '',
						'business:hours:end'                   => '',
					),
					'music.album' => array(
						'music:song'         => '',
						'music:song:disc'    => '',
						'music:song:track'   => '',
						'music:musician'     => '',
						'music:release_date' => '',
					),
					'music.playlist' => array(
						'music:creator'    => '',
						'music:song'       => '',
						'music:song:disc'  => '',
						'music:song:track' => '',
					),
					'music.radio_station' => array(
						'music:creator' => '',
					),
					'music.song' => array(
						'music:album'       => '',
						'music:album:disc'  => '',
						'music:album:track' => '',
						'music:duration'    => '',
						'music:musician'    => '',
					),
					'place' => array(
						'place:location:latitude'  => '',
						'place:location:longitude' => '',
						'place:location:altitude'  => '',
						'place:street_address'     => '',
						'place:locality'           => '',
						'place:region'             => '',
						'place:postal_code'        => '',
						'place:country_name'       => '',
					),

					/*
					 * See https://developers.facebook.com/docs/marketing-api/catalog/reference/.
					 */
					'product' => array(
						'product:adult_type'                  => 'product_adult_type',		// Internal meta tag.
						'product:age_group'                   => 'product_age_group',
						'product:availability'                => 'product_avail',
						'product:awards'                      => '',				// Internal meta tag.
						'product:brand'                       => 'product_brand',
						'product:category'                    => 'product_category',		// Product Google Category.
						'product:color'                       => 'product_color',
						'product:condition'                   => 'product_condition',
						'product:ean'                         => 'product_gtin13',
						'product:energy_efficiency:value'     => 'product_energy_efficiency',		// Internal meta tag.
						'product:energy_efficiency:min_value' => 'product_energy_efficiency_min',	// Internal meta tag.
						'product:energy_efficiency:max_value' => 'product_energy_efficiency_max',	// Internal meta tag.
						'product:expiration_time'             => '',
						'product:fluid_volume:value'          => 'product_fluid_volume_value',	// Internal meta tag.
						'product:fluid_volume:units'          => 'product_fluid_volume_units',	// Internal meta tag.
						'product:gtin14'                      => 'product_gtin14',		// Internal meta tag.
						'product:gtin13'                      => 'product_gtin13',		// Internal meta tag.
						'product:gtin12'                      => 'product_gtin12',		// Internal meta tag.
						'product:gtin8'                       => 'product_gtin8',		// Internal meta tag.
						'product:gtin'                        => 'product_gtin',		// Internal meta tag.
						'product:height:value'                => 'product_height_value',	// Internal meta tag.
						'product:height:units'                => 'product_height_units',	// Internal meta tag.
						'product:isbn'                        => 'product_isbn',
						'product:item_group_id'               => '',				// Product variant group ID.
						'product:length:value'                => 'product_length_value',	// Internal meta tag.
						'product:length:units'                => 'product_length_units',	// Internal meta tag.
						'product:material'                    => 'product_material',
						'product:mfr_part_no'                 => 'product_mfr_part_no',		// Product MPN.
						'product:min_advert_price:amount'     => 'product_min_advert_price',	// Internal meta tag.
						'product:min_advert_price:currency'   => '',				// Internal meta tag.
						'product:mrp_id'                      => 'product_mrp',			// Internal meta tag.
						'product:original_price:amount'       => '',				// Used by WooCommerce module.
						'product:original_price:currency'     => '',				// Used by WooCommerce module.
						'product:pattern'                     => 'product_pattern',
						'product:pretax_price:amount'         => '',				// Used by WooCommerce module.
						'product:pretax_price:currency'       => '',				// Used by WooCommerce module.
						'product:price_type'                  => 'product_price_type',		// Internal meta tag.
						'product:price:amount'                => 'product_price',
						'product:price:currency'              => 'product_currency',
						'product:purchase_limit'              => '',
						'product:retailer_category'           => '',				// Internal meta tag.
						'product:retailer_item_id'            => '',				// Product ID.
						'product:retailer_part_no'            => 'product_retailer_part_no',	// Product SKU.
						'product:sale_price:amount'           => '',				// Used by WooCommerce module.
						'product:sale_price:currency'         => '',				// Used by WooCommerce module.
						'product:sale_price_dates:start'      => '',				// Used by WooCommerce module.
						'product:sale_price_dates:end'        => '',				// Used by WooCommerce module.
						'product:shipping_cost:amount'        => '',
						'product:shipping_cost:currency'      => '',
						'product:shipping_height:value'       => 'product_shipping_height_value',	// Internal meta tag.
						'product:shipping_height:units'       => 'product_shipping_height_units',	// Internal meta tag.
						'product:shipping_length:value'       => 'product_shipping_length_value',	// Internal meta tag.
						'product:shipping_length:units'       => 'product_shipping_length_units',	// Internal meta tag.
						'product:shipping_weight:value'       => 'product_shipping_weight_value',
						'product:shipping_weight:units'       => 'product_shipping_weight_units',
						'product:shipping_width:value'        => 'product_shipping_width_value',	// Internal meta tag.
						'product:shipping_width:units'        => 'product_shipping_width_units',	// Internal meta tag.
						'product:size'                        => 'product_size',
						'product:size_group'                  => 'product_size_group',		// Internal meta tag.
						'product:size_system'                 => 'product_size_system',		// Internal meta tag.
						'product:target_gender'               => 'product_target_gender',
						'product:upc'                         => 'product_gtin12',
						'product:weight:value'                => 'product_weight_value',
						'product:weight:units'                => 'product_weight_units',
						'product:width:value'                 => 'product_width_value',		// Internal meta tag.
						'product:width:units'                 => 'product_width_units',		// Internal meta tag.
					),
					'profile' => array(
						'profile:first_name' => '',
						'profile:last_name'  => '',
						'profile:username'   => '',
						'profile:gender'     => '',
					),
					'video.episode' => array(
						'video:actor'        => '',
						'video:actor:role'   => '',
						'video:director'     => '',
						'video:writer'       => '',
						'video:duration'     => '',
						'video:release_date' => '',
						'video:tag'          => '',
						'video:series'       => '',
					),
					'video.movie' => array(
						'video:actor'        => '',
						'video:actor:role'   => '',
						'video:director'     => '',
						'video:writer'       => '',
						'video:duration'     => '',
						'video:release_date' => '',
						'video:tag'          => '',
					),
					'video.other' => array(
						'video:actor'        => '',
						'video:actor:role'   => '',
						'video:director'     => '',
						'video:writer'       => '',
						'video:duration'     => '',
						'video:release_date' => '',
						'video:tag'          => '',
					),
					'video.tv_show' => array(
						'video:actor'        => '',
						'video:actor:role'   => '',
						'video:director'     => '',
						'video:writer'       => '',
						'video:duration'     => '',
						'video:release_date' => '',
						'video:tag'          => '',
					),
				),
				'og_content_map' => array(	// Element of 'head' array.

					/*
					 * Validated on 2021/07/22.
					 *
					 * See https://developers.facebook.com/docs/marketing-api/catalog/reference/#og-tags.
					 *
					 * Required. Current availability of the item: in stock, out of stock, available for order,
					 * discontinued. Supports pixel-based catalogs.
					 */
					'product:availability' => array(
						'https://schema.org/BackOrder'           => 'available for order',
						'https://schema.org/Discontinued'        => 'discontinued',
						'https://schema.org/InStock'             => 'in stock',
						'https://schema.org/InStoreOnly'         => 'in stock',
						'https://schema.org/LimitedAvailability' => 'in stock',
						'https://schema.org/OnlineOnly'          => 'in stock',
						'https://schema.org/OutOfStock'          => 'out of stock',
						'https://schema.org/PreOrder'            => 'available for order',
						'https://schema.org/PreSale'             => 'available for order',
						'https://schema.org/SoldOut'             => 'out of stock',
					),

					/*
					 * Validated on 2020/08/17.
					 *
					 * See https://developers.facebook.com/docs/marketing-api/catalog/reference/#og-tags.
					 *
					 * Required. Current condition of the item: new, refurbished, used. Supports pixel-based
					 * catalogs.
					 */
					'product:condition' => array(
						'https://schema.org/DamagedCondition'     => 'used',
						'https://schema.org/NewCondition'         => 'new',
						'https://schema.org/RefurbishedCondition' => 'refurbished',
						'https://schema.org/UsedCondition'        => 'used',
					),
				),
				'schema_type' => array(				// Element of 'head' array.
					'thing' => array(			// Most generic type.
						'creative.work' => array(	// Creative work, including books, movies, photographs, software programs, etc.
							'amp.story' => 'https://schema.org/AmpStory',
							'article'   => array(
								'article'                    => 'https://schema.org/Article',
								'article.advertiser.content' => 'https://schema.org/AdvertiserContentArticle',
								'article.news'               => array(
									'article.news'            => 'https://schema.org/NewsArticle',
									'article.news.analysis'   => 'https://schema.org/AnalysisNewsArticle',
									'article.news.ask.public' => 'https://schema.org/AskPublicNewsArticle',
									'article.news.background' => 'https://schema.org/BackgroundNewsArticle',
									'article.news.opinion'    => 'https://schema.org/OpinionNewsArticle',
									'article.news.reportage'  => 'https://schema.org/ReportageNewsArticle',
									'article.news.review'     => 'https://schema.org/ReviewNewsArticle',
								),
								'article.satirical' => 'https://schema.org/SatiricalArticle',
								'article.scholarly' => array(
									'article.scholarly'         => 'https://schema.org/ScholarlyArticle',
									'article.scholarly.medical' => 'https://schema.org/MedicalScholarlyArticle',
								),
								'article.tech' => array(
									'article.tech'               => 'https://schema.org/TechArticle',
									'article.tech.reference.api' => 'https://schema.org/APIReference',
								),
								'report'               => 'https://schema.org/Report',
								'social.media.posting' => array(
									'blog.posting' => array(
										'blog.posting'      => 'https://schema.org/BlogPosting',
										'blog.posting.live' => 'https://schema.org/LiveBlogPosting',
									),
									'discussion.forum.posting' => 'https://schema.org/DiscussionForumPosting',
									'social.media.posting'     => 'https://schema.org/SocialMediaPosting',
								),
							),
							'blog' => 'https://schema.org/Blog',
							'book' => array(
								'book'       => 'https://schema.org/Book',
								'book.audio' => 'https://schema.org/Audiobook',
							),
							'claim'       => 'https://schema.org/Claim',
							'clip'        => 'https://schema.org/Clip',
							'comic.story' => 'https://schema.org/ComicStory',
							'comment'     => array(
								'answer'   => 'https://schema.org/Answer',
								'comment'  => 'https://schema.org/Comment',
								'question' => 'https://schema.org/Question',
							),
							'conversation'         => 'https://schema.org/Conversation',
							'course'               => 'https://schema.org/Course',
							'creative.work'        => 'https://schema.org/CreativeWork',
							'creative.work.season' => array(
								'creative.work.season' => 'https://schema.org/CreativeWorkSeason',
								'podcast.season'       => 'https://schema.org/PodcastSeason',
								'radio.season'         => 'https://schema.org/RadioSeason',
								'tv.season'            => 'https://schema.org/TVSeason',
							),
							'creative.work.series' => array(
								'book.series'          => 'https://schema.org/BookSeries',
								'creative.work.series' => 'https://schema.org/CreativeWorkSeries',
								'movie.series'         => 'https://schema.org/MovieSeries',
								'periodical'           => 'https://schema.org/Periodical',
								'podcast.series'       => 'https://schema.org/PodcastSeries',
								'radio.series'         => 'https://schema.org/RadioSeries',
								'tv.series'            => 'https://schema.org/TVSeries',
								'video.game.series'    => 'https://schema.org/VideoGameSeries',
							),
							'data.catalog'         => 'https://schema.org/DataCatalog',
							'data.set'             => 'https://schema.org/DataSet',
							'digital.document'     => 'https://schema.org/DigitalDocument',
							'episode'              => array(
								'episode'         => 'https://schema.org/Episode',
								'episode.podcast' => 'https://schema.org/PodcastEpisode',
								'episode.radio'   => 'https://schema.org/RadioEpisode',
								'episode.tv'      => 'https://schema.org/TVEpisode',
							),
							'game'   => 'https://schema.org/Game',
							'howto' => array(
								'howto'  => 'https://schema.org/HowTo',
								'recipe' => 'https://schema.org/Recipe',	// Recipe is a sub-type of HowTo.
							),
							'learning.resource' => array(
								'learning.course'   => 'https://schema.org/Course',
								'learning.resource' => 'https://schema.org/LearningResource',
								'learning.quiz'     => 'https://schema.org/Quiz',
								'learning.syllabus' => 'https://schema.org/Syllabus',
							),
							'map'          => 'https://schema.org/Map',
							'media.object' => array(
								'audio.object' => array(
									'audio.object' => 'https://schema.org/AudioObject',
									'book.audio'   => 'https://schema.org/Audiobook',
								),
								'data.download'      => 'https://schema.org/DataDownload',
								'image.object'       => 'https://schema.org/ImageObject',
								'media.object'       => 'https://schema.org/MediaObject',
								'music.video.object' => 'https://schema.org/MusicVideoObject',
								'video.object'       => 'https://schema.org/VideoObject',
							),
							'menu'              => 'https://schema.org/Menu',
							'menu.section'      => 'https://schema.org/MenuSection',
							'message'           => 'https://schema.org/Message',
							'movie'             => 'https://schema.org/Movie',
							'music.composition' => 'https://schema.org/MusicComposition',
							'music.playlist'    => array(
								'music.album'    => 'https://schema.org/MusicAlbum',
								'music.playlist' => 'https://schema.org/MusicPlaylist',
								'music.release'  => 'https://schema.org/MusicRelease',
							),
							'music.recording'    => 'https://schema.org/MusicRecording',
							'painting'           => 'https://schema.org/Painting',
							'photograph'         => 'https://schema.org/Photograph',
							'publication.issue'  => 'https://schema.org/PublicationIssue',
							'publication.volume' => 'https://schema.org/PublicationVolume',
							'review'             => array(
								'review'        => 'https://schema.org/Review',
								'review.claim'  => 'https://schema.org/ClaimReview',
								'review.critic' => array(
									'review.critic'       => 'https://schema.org/CriticReview',
									'article.news.review' => 'https://schema.org/ReviewNewsArticle',
								),
								'review.employer'       => 'https://schema.org/EmployerReview',
								'review.media'          => 'https://schema.org/MediaReview',
								'review.recommendation' => 'https://schema.org/Recommendation',
								'review.user'           => 'https://schema.org/UserReview',
							),
							'sculpture'            => 'https://schema.org/Sculpture',
							'series'               => 'https://schema.org/Series',
							'software.application' => array(
								'software.application'            => 'https://schema.org/SoftwareApplication',
								'software.application.mobile'     => 'https://schema.org/MobileApplication',
								'software.application.video.game' => 'https://schema.org/VideoGame',
								'software.application.web'        => 'https://schema.org/WebApplication',
							),
							'software.source.code' => 'https://schema.org/SoftwareSourceCode',
							'special.announcement' => 'https://schema.org/SpecialAnnouncement',
							'tv.season'            => 'https://schema.org/TVSeason',
							'tv.series'            => 'https://schema.org/TVSeries',
							'visual.artwork'       => 'https://schema.org/VisualArtwork',
							'webpage'              => array(
								'webpage'            => 'https://schema.org/WebPage',
								'webpage.about'      => 'https://schema.org/AboutPage',
								'webpage.checkout'   => 'https://schema.org/CheckoutPage',
								'webpage.collection' => array(
									'webpage.collection'    => 'https://schema.org/CollectionPage',
									'webpage.gallery.media' => array(
										'webpage.gallery.media' => 'https://schema.org/MediaGallery',
										'webpage.gallery.image' => 'https://schema.org/ImageGallery',
										'webpage.gallery.video' => 'https://schema.org/VideoGallery',
									),
								),
								'webpage.contact'             => 'https://schema.org/ContactPage',
								'webpage.faq'                 => 'https://schema.org/FAQPage',
								'webpage.item'                => 'https://schema.org/ItemPage',
								'webpage.medical'             => 'https://schema.org/MedicalWebPage',
								'webpage.profile'             => 'https://schema.org/ProfilePage',
								'webpage.qa'                  => 'https://schema.org/QAPage',
								'webpage.real.estate.listing' => 'https://schema.org/RealEstateListing',
								'webpage.search.results'      => 'https://schema.org/SearchResultsPage',
							),
							'webpage.element' => 'https://schema.org/WebPageElement',
							'website'         => 'https://schema.org/WebSite',
						),
						'event' => array(
							'event'             => 'https://schema.org/Event',
							'event.business'    => 'https://schema.org/BusinessEvent',
							'event.childrens'   => 'https://schema.org/ChildrensEvent',
							'event.comedy'      => 'https://schema.org/ComedyEvent',
							'event.dance'       => 'https://schema.org/DanceEvent',
							'event.delivery'    => 'https://schema.org/DeliveryEvent',
							'event.education'   => 'https://schema.org/EducationEvent',
							'event.exhibition'  => 'https://schema.org/ExhibitionEvent',
							'event.festival'    => 'https://schema.org/Festival',
							'event.food'        => 'https://schema.org/FoodEvent',
							'event.literary'    => 'https://schema.org/LiteraryEvent',
							'event.music'       => 'https://schema.org/MusicEvent',
							'event.publication' => 'https://schema.org/PublicationEvent',
							'event.sale'        => 'https://schema.org/SaleEvent',
							'event.screening'   => 'https://schema.org/ScreeningEvent',
							'event.social'      => 'https://schema.org/SocialEvent',
							'event.sports'      => 'https://schema.org/SportsEvent',
							'event.theater'     => 'https://schema.org/TheaterEvent',
							'event.visual.arts' => 'https://schema.org/VisualArtsEvent',
						),
						'intangible' => array(
							'alignment.object' => 'https://schema.org/AlignmentObject',
							'audience'         => array(
								'audience'             => 'https://schema.org/Audience',
								'audience.business'    => 'https://schema.org/BusinessAudience',
								'audience.educational' => 'https://schema.org/EducationalAudience',
								'audience.medical'     => 'https://schema.org/MedicalAudience',
								'audience.people'      => 'https://schema.org/PeopleAudience',
							),
							'bed.details'                 => 'https://schema.org/BedDetails',
							'brand'                       => 'https://schema.org/Brand',
							'broadcast.channel'           => 'https://schema.org/BroadcastChannel',
							'computer.language'           => 'https://schema.org/ComputerLanguage',
							'data.feed.item'              => 'https://schema.org/DataFeedItem',
							'demand'                      => 'https://schema.org/Demand',
							'digital.document.permission' => 'https://schema.org/DigitalDocumentPermission',
							'entry.point'                 => 'https://schema.org/EntryPoint',
							'enumeration'                 => array(
								'action.status.type'               => 'https://schema.org/ActionStatusType',
								'boarding.policy.type'             => 'https://schema.org/BoardingPolicyType',
								'book.format.type'                 => 'https://schema.org/BookFormatType',
								'business.entity.type'             => 'https://schema.org/BusinessEntityType',
								'business.function'                => 'https://schema.org/BusinessFunction',
								'contact.point.option'             => 'https://schema.org/ContactPointOption',
								'day.of.week'                      => 'https://schema.org/DayOfWeek',
								'delivery.method'                  => 'https://schema.org/DeliveryMethod',
								'digital.document.permission.type' => 'https://schema.org/DigitalDocumentPermissionType',
								'enumeration'                      => 'https://schema.org/Enumeration',
								'event.status.type'                => 'https://schema.org/EventStatusType',
								'game.play.mode'                   => 'https://schema.org/GamePlayMode',
								'game.server.status'               => 'https://schema.org/GameServerStatus',
								'gender.type'                      => 'https://schema.org/GenderType',
								'item.availability'                => 'https://schema.org/ItemAvailability',
								'item.list.order.type'             => 'https://schema.org/ItemListOrderType',
								'map.category.type'                => 'https://schema.org/MapCategoryType',
								'medical.enumeration' => array(
									'audience.medical'    => 'https://schema.org/MedicalAudience',
									'medical.enumeration' => 'https://schema.org/MedicalEnumeration',
									'medical.specialty'   => array(
										'anesthesia.specialty'           => 'https://schema.org/Anesthesia',
										'cardiovascular.specialty'       => 'https://schema.org/Cardiovascular',
										'community.health.specialty'     => 'https://schema.org/CommunityHealth',
										'dentistry.specialty'            => 'https://schema.org/Dentistry',
										'dermatologic.specialty'         => 'https://schema.org/Dermatologic',
										'dermatology.specialty'          => 'https://schema.org/Dermatology',
										'diet.nutrition.specialty'       => 'https://schema.org/DietNutrition',
										'emergency.specialty'            => 'https://schema.org/Emergency',
										'endocrine.specialty'            => 'https://schema.org/Endocrine',
										'gastroenterologic.specialty'    => 'https://schema.org/Gastroenterologic',
										'genetic.specialty'              => 'https://schema.org/Genetic',
										'geriatric.specialty'            => 'https://schema.org/Geriatric',
										'gynecologic.specialty'          => 'https://schema.org/Gynecologic',
										'hematologic.specialty'          => 'https://schema.org/Hematologic',
										'infectious.specialty'           => 'https://schema.org/Infectious',
										'laboratory.science.specialty'   => 'https://schema.org/LaboratoryScience',
										'medical.specialty'              => 'https://schema.org/MedicalSpecialty',
										'midwifery.specialty'            => 'https://schema.org/Midwifery',
										'musculoskeletal.specialty'      => 'https://schema.org/Musculoskeletal',
										'neurologic.specialty'           => 'https://schema.org/Neurologic',
										'nursing.specialty'              => 'https://schema.org/Nursing',
										'obstetric.specialty'            => 'https://schema.org/Obstetric',
										'occupational.therapy.specialty' => 'https://schema.org/OccupationalTherapy',
										'oncologic.specialty'            => 'https://schema.org/Oncologic',
										'optometric.specialty'           => 'https://schema.org/Optometric',
										'otolaryngologic.specialty'      => 'https://schema.org/Otolaryngologic',
										'pathology.specialty'            => 'https://schema.org/Pathology',
										'pediatric.specialty'            => 'https://schema.org/Pediatric',
										'pharmacy.specialty'             => 'https://schema.org/PharmacySpecialty',
										'physiotherapy.specialty'        => 'https://schema.org/Physiotherapy',
										'plastic.surgery.specialty'      => 'https://schema.org/PlasticSurgery',
										'podiatric.specialty'            => 'https://schema.org/Podiatric',
										'primary.care.specialty'         => 'https://schema.org/PrimaryCare',
										'psychiatric.specialty'          => 'https://schema.org/Psychiatric',
										'public.health.specialty'        => 'https://schema.org/PublicHealth',
										'pulmonary.specialty'            => 'https://schema.org/Pulmonary',
										'radiography.specialty'          => 'https://schema.org/Radiography',
										'renal.specialty'                => 'https://schema.org/Renal',
										'respiratory.therapy.specialty'  => 'https://schema.org/RespiratoryTherapy',
										'rheumatologic.specialty'        => 'https://schema.org/Rheumatologic',
										'speech.pathology.specialty'     => 'https://schema.org/SpeechPathology',
										'surgical.specialty'             => 'https://schema.org/Surgical',
										'toxicologic.specialty'          => 'https://schema.org/Toxicologic',
										'urologic.specialty'             => 'https://schema.org/Urologic',
									),
									'medicine.system' => array(
										'ayurvedic.system'            => 'https://schema.org/Ayurvedic',
										'chiropractic.system'         => 'https://schema.org/Chiropractic',
										'homeopathic.system'          => 'https://schema.org/Homeopathic',
										'medicine.system'             => 'https://schema.org/MedicineSystem',
										'osteopathic.system'          => 'https://schema.org/Osteopathic',
										'traditional.chinese.system'  => 'https://schema.org/TraditionalChinese',
										'western.conventional.system' => 'https://schema.org/WesternConventional',
									),
								),
								'music.album.production.type'      => 'https://schema.org/MusicAlbumProductionType',
								'music.album.release.type'         => 'https://schema.org/MusicAlbumReleaseType',
								'music.release.format.type'        => 'https://schema.org/MusicReleaseFormatType',
								'offer.item.condition'             => 'https://schema.org/OfferItemCondition',
								'order.status'                     => 'https://schema.org/OrderStatus',
								'payment.method'                   => array(
									'payment.card' => array(
										'credit.card'  => 'https://schema.org/CreditCard',
										'payment.card' => 'https://schema.org/PaymentCard',
									),
									'payment.method' => 'https://schema.org/PaymentMethod',
								),
								'payment.status.type'              => 'https://schema.org/PaymentStatusType',
								'qualitative.value'                => 'https://schema.org/QualitativeValue',
								'reservation.status.type'          => 'https://schema.org/ReservationStatusType',
								'restricted.diet'                  => 'https://schema.org/RestrictedDiet',
								'rsvp.response.type'               => 'https://schema.org/RsvpResponseType',
								'specialty'                        => array(
									// 'medical.specialty' array added by WpssoSchema::add_schema_type_xrefs().
									'specialty'                 => 'https://schema.org/Specialty',
								),
								'warranty.scope' => 'https://schema.org/WarrantyScope',
							),
							'game.server' => 'https://schema.org/GameServer',
							'intangible'  => 'https://schema.org/Intangible',
							'invoice'     => 'https://schema.org/Invoice',
							'item.list'   => array(
								'breadcrumb.list' => 'https://schema.org/BreadcrumbList',
								'howto.section'   => 'https://schema.org/HowToSection',
								'howto.step'      => 'https://schema.org/HowToStep',
								'item.list'       => 'https://schema.org/ItemList',
								'offer.catalog'   => 'https://schema.org/OfferCatalog',
							),
							'job.posting' => 'https://schema.org/JobPosting',
							'language'    => 'https://schema.org/Language',
							'list.item'   => array(
								'howto.direction' => 'https://schema.org/HowToDirection',
								'howto.item'      => array(
									'howto.item'   => 'https://schema.org/HowToItem',
									'howto.supply' => 'https://schema.org/HowToSupply',
									'howto.tool'   => 'https://schema.org/HowToTool',
								),
								'howto.section' => 'https://schema.org/HowToSection',
								'howto.step'    => 'https://schema.org/HowToStep',
								'howto.tip'     => 'https://schema.org/HowToTip',
								'list.item'     => 'https://schema.org/ListItem',
							),
							'menu.item' => 'https://schema.org/MenuItem',
							'offer'     => array(
								'offer'           => 'https://schema.org/Offer',
								'offer.aggregate' => 'https://schema.org/AggregateOffer',
							),
							'order'      => 'https://schema.org/Order',
							'order.item' => 'https://schema.org/OrderItem',
							'parcel.delivery'              => 'https://schema.org/ParcelDelivery',
							'permit'                       => 'https://schema.org/Permit',
							'program.membership'           => 'https://schema.org/ProgramMembership',
							'property.value.specification' => 'https://schema.org/PropertyValueSpecification',
							'quantity'                     => 'https://schema.org/Quantity',
							'rating'                       => array(
								'rating'           => 'https://schema.org/Rating',
								'rating.aggregate' => 'https://schema.org/AggregateRating',
							),
							'reservation' => 'https://schema.org/Reservation',
							'role'        => 'https://schema.org/Role',
							'seat'        => 'https://schema.org/Seat',

							/*
							 * A service provided by an organization, e.g. delivery service, print services, etc.
							 */
							'service' => array(
								'service'                    => 'https://schema.org/Service',
								'service.broadcast'          => 'https://schema.org/BroadcastService',
								'service.cable.or.satellite' => 'https://schema.org/CableOrSatelliteService',
								'service.financial.product'  => array(
									'bank.account' => array(
										'bank.account'    => 'https://schema.org/BankAccount',
										'deposit.account' => 'https://schema.org/DepositAccount',
									),
									'service.currency.conversion'   => 'https://schema.org/CurrencyConversionService',
									'service.financial.product'     => 'https://schema.org/FinancialProduct',
									'service.investment.or.deposit' => array(
										'brokerage.account'             => 'https://schema.org/BrokerageAccount',
										'deposit.account'               => 'https://schema.org/DepositAccount',
										'service.investment.or.deposit' => 'https://schema.org/InvestmentOrDeposit',
										'service.investment.fund'       => 'https://schema.org/InvestmentFund',
									),
									'service.loan.or.credit' => array(
										'credit.card'            => 'https://schema.org/CreditCard',
										'mortgage.loan'          => 'https://schema.org/MortgageLoan',
										'service.loan.or.credit' => 'https://schema.org/LoanOrCredit',
									),
									// 'payment.card' array added by WpssoSchema::add_schema_type_xrefs().
									'service.payment' => 'https://schema.org/PaymentService',
								),
								'service.food'       => 'https://schema.org/FoodService',
								'service.government' => 'https://schema.org/GovernmentService',
								'service.taxi'       => 'https://schema.org/TaxiService',
							),
							'service.channel'  => 'https://schema.org/ServiceChannel',
							'ticket'           => 'https://schema.org/Ticket',
							'trip'             => array(
								'trip'         => 'https://schema.org/Trip',
								'trip.bus'     => 'https://schema.org/BusTrip',
								'trip.flight'  => 'https://schema.org/Flight',
								'trip.train'   => 'https://schema.org/TrainTrip',
								'trip.tourist' => 'https://schema.org/TouristTrip',
							),
							'virtual.location' => 'https://schema.org/VirtualLocation',
						),
						'medical.entity' => array(
							'medical.anatomical.structure'    => 'https://schema.org/AnatomicalStructure',
							'medical.anatomical.systems'      => 'https://schema.org/AnatomicalSystem',
							'medical.cause'                   => 'https://schema.org/MedicalCause',
							'medical.condition'               => array(
								'medical.condition'          => 'https://schema.org/MedicalCondition',
								'medical.infectious.disease' => 'https://schema.org/InfectiousDisease',
								'medical.sign.or.symptom'    => array(
									'medical.sign.or.symptom' => 'https://schema.org/MedicalSignOrSymptom',
									'medical.sign'            => array(
										'medical.sign'       => 'https://schema.org/MedicalSign',
										'medical.sign.vital' => 'https://schema.org/MedicalVitalSign',
									),
									'medical.symptom' => 'https://schema.org/MedicalSymptom',
								),
							),
							'medical.contraindication'        => 'https://schema.org/MedicalContraindication',
							'medical.device'                  => 'https://schema.org/MedicalDevice',
							'medical.entity'                  => 'https://schema.org/MedicalEntity',
							'medical.guideline'               => 'https://schema.org/MedicalGuideline',
							'medical.indication'              => 'https://schema.org/MedicalIndication',
							'medical.intangible'              => 'https://schema.org/MedicalIntangible',
							'medical.lifestyle.modifications' => 'https://schema.org/LifestyleModification',
							'medical.procedure'               => 'https://schema.org/MedicalProcedure',
							'medical.risk.estimator'          => 'https://schema.org/MedicalRiskEstimator',
							'medical.risk.factor'             => 'https://schema.org/MedicalRiskFactor',
							'medical.study'                   => 'https://schema.org/MedicalStudy',
							'medical.substance'               => 'https://schema.org/Substance',
							'medical.superficial.anatomy'     => 'https://schema.org/SuperficialAnatomy',
							'medical.test'                    => 'https://schema.org/MedicalTest',
						),
						'organization' => array(
							'airline'                 => 'https://schema.org/Airline',
							'consortium'              => 'https://schema.org/Consortium',
							'corporation'             => 'https://schema.org/Corporation',
							// 'educational.organization' array added by WpssoSchema::add_schema_type_xrefs().
							'funding.scheme'          => 'https://schema.org/FundingScheme',
							'government.organization' => 'https://schema.org/GovernmentOrganization',
							'library.system'          => 'https://schema.org/LibrarySystem',
							// 'local.business' array added by WpssoSchema::add_schema_type_xrefs().
							'medical.organization'    => array(
								'dentist.organization'        => 'https://schema.org/Dentist',
								'diagnostic.lab.organization' => 'https://schema.org/DiagnosticLab',
								'hospital'                    => 'https://schema.org/Hospital',
								'medical.clinic'              => array(
									'medical.clinic'         => 'https://schema.org/MedicalClinic',
									'covid.testing.facility' => 'https://schema.org/CovidTestingFacility',
								),
								'pharmacy.organization'       => 'https://schema.org/Pharmacy',
								'physician.organization'      => 'https://schema.org/Physician',
								'veterinary.care'         => 'https://schema.org/VeterinaryCare',
							),
							'non-governmental.organization' => 'https://schema.org/NGO',
							'news.media.organization'       => 'https://schema.org/NewsMediaOrganization',
							'online.business'               => array(
								'online.business' => 'https://schema.org/OnlineBusiness',
								'online.store'    => 'https://schema.org/OnlineStore',
							),
							'organization'                  => 'https://schema.org/Organization',
							'performing.group'              => array(
								'dance.group'      => 'https://schema.org/DanceGroup',
								'music.group'      => 'https://schema.org/MusicGroup',
								'performing.group' => 'https://schema.org/PerformingGroup',
								'theater.group'    => 'https://schema.org/TheaterGroup',
							),
							'project' => array(
								'funding.agency'   => 'https://schema.org/FundingAgency',
								'project'          => 'https://schema.org/Project',
								'research.project' => 'https://schema.org/ResearchProject',
							),
							'research.organization'      => 'https://schema.org/ResearchOrganization',
							'search.rescue.organization' => 'https://schema.org/SearchRescueOrganization',
							'sports.organization'        => array(
								'sports.team'         => 'https://schema.org/SportsTeam',
								'sports.organization' => 'https://schema.org/SportsOrganization',
							),
							'workers.union' => 'https://schema.org/WorkersUnion',
						),
						'person' => 'https://schema.org/Person',
						'place'  => array(
							'accommodation' => array(
								'accommodation' => 'https://schema.org/Accommodation',
								'apartment'     => 'https://schema.org/Apartment',
								'camping.pitch' => 'https://schema.org/CampingPitch',
								'house'         => array(
									'house'               => 'https://schema.org/House',
									'house.single.family' => 'https://schema.org/SingleFamilyResidence',
								),
								'room' => array(
									'room'         => 'https://schema.org/Room',
									'room.hotel'   => 'https://schema.org/HotelRoom',
									'room.meeting' => 'https://schema.org/MeetingRoom',
								),
								'suite' => 'https://schema.org/Suite',
							),
							'administrative.area' => array(
								'administrative.area' => 'https://schema.org/AdministrativeArea',
								'city'                => 'https://schema.org/City',
								'country'             => 'https://schema.org/Country',
								'state'               => 'https://schema.org/State',
							),
							'civic.structure' => array(
								'airport'                 => 'https://schema.org/Airport',
								'aquarium'                => 'https://schema.org/Aquarium',
								'beach'                   => 'https://schema.org/Beach',
								'boat.terminal'           => 'https://schema.org/BoatTerminal',
								'bridge'                  => 'https://schema.org/Bridge',
								'bus.station'             => 'https://schema.org/BusStation',
								'bus.stop'                => 'https://schema.org/BusStop',
								'campground'              => 'https://schema.org/Campground',
								'cemetary'                => 'https://schema.org/Cemetery',
								'civic.structure'         => 'https://schema.org/CivicStructure',
								'crematorium'             => 'https://schema.org/Crematorium',
								'educational.organization' => array(
									'college.or.university'    => 'https://schema.org/CollegeOrUniversity',
									'educational.organization' => 'https://schema.org/EducationalOrganization',
									'elementary.school'        => 'https://schema.org/ElementarySchool',
									'high.school'              => 'https://schema.org/HighSchool',
									'middle.school'            => 'https://schema.org/MiddleSchool',
									'preschool'                => 'https://schema.org/Preschool',
									'school'                   => 'https://schema.org/School',
								),
								'event.venue'             => 'https://schema.org/EventVenue',
								'fire.station'            => 'https://schema.org/FireStation',
								'government.building'     => 'https://schema.org/GovernmentBuilding',
								'hospital'                => 'https://schema.org/Hospital',
								'movie.theater'           => 'https://schema.org/MovieTheater',
								'museum'                  => 'https://schema.org/Museum',
								'music.venue'             => 'https://schema.org/MusicVenue',
								'park'                    => 'https://schema.org/Park',
								'parking.facility'        => 'https://schema.org/ParkingFacility',
								'performing.arts.theatre' => 'https://schema.org/PerformingArtsTheater',
								'place.of.worship'        => 'https://schema.org/PlaceOfWorship',
								'playground'              => 'https://schema.org/Playground',
								'rv.park'                 => 'https://schema.org/RVPark',
								'police.station'          => 'https://schema.org/PoliceStation',
								'public.toilet'           => 'https://schema.org/PublicToilet',
								'stadium.or.arena'        => 'https://schema.org/StadiumOrArena',
								'subway.station'          => 'https://schema.org/SubwayStation',
								'taxi.stand'              => 'https://schema.org/TaxiStand',
								'train.station'           => 'https://schema.org/TrainStation',
								'zoo'                     => 'https://schema.org/Zoo',
							),
							'landform'                          => 'https://schema.org/Landform',
							'landmarks.or.historical.buildings' => 'https://schema.org/LandmarksOrHistoricalBuildings',
							'local.business'                    => array(
								'animal.shelter'      => 'https://schema.org/AnimalShelter',
								'automotive.business' => array(
									'auto.body.shop'      => 'https://schema.org/AutoBodyShop',
									'auto.dealer'         => 'https://schema.org/AutoDealer',
									'auto.parts.store'    => 'https://schema.org/AutoPartsStore',
									'auto.rental'         => 'https://schema.org/AutoRental',
									'auto.repair'         => 'https://schema.org/AutoRepair',
									'auto.wash'           => 'https://schema.org/AutoWash',
									'automotive.business' => 'https://schema.org/AutomotiveBusiness',
									'gas.station'         => 'https://schema.org/GasStation',
									'motorcycle.dealer'   => 'https://schema.org/MotorcycleDealer',
									'motorcycle.repair'   => 'https://schema.org/MotorcycleRepair',
								),
								'child.care'                   => 'https://schema.org/ChildCare',
								'dentist.organization'         => 'https://schema.org/Dentist',
								'dry.cleaning.or.laundry'      => 'https://schema.org/DryCleaningOrLaundry',
								'emergency.service'            => array(
									'emergency.service' => 'https://schema.org/EmergencyService',
									'fire.station'      => 'https://schema.org/FireStation',
									'hospital'          => 'https://schema.org/Hospital',
									'police.station'    => 'https://schema.org/PoliceStation',
								),
								'employment.agency'      => 'https://schema.org/EmploymentAgency',
								'entertainment.business' => array(
									'adult.entertainment'    => 'https://schema.org/AdultEntertainment',
									'amusement.park'         => 'https://schema.org/AmusementPark',
									'art.gallery'            => 'https://schema.org/ArtGallery',
									'casino'                 => 'https://schema.org/Casino',
									'comedy.club'            => 'https://schema.org/ComedyClub',
									'entertainment.business' => 'https://schema.org/EntertainmentBusiness',
									'movie.theater'          => 'https://schema.org/MovieTheater',
									'night.club'             => 'https://schema.org/NightClub',
								),
								'financial.service'  => 'https://schema.org/FinancialService',
								'food.establishment' => array(
									'bakery'               => 'https://schema.org/Bakery',
									'bar.or.pub'           => 'https://schema.org/BarOrPub',
									'brewery'              => 'https://schema.org/Brewery',
									'cafe.or.coffee.shop'  => 'https://schema.org/CafeOrCoffeeShop',
									'fast.food.restaurant' => 'https://schema.org/FastFoodRestaurant',
									'food.establishment'   => 'https://schema.org/FoodEstablishment',
									'ice.cream.shop'       => 'https://schema.org/IceCreamShop',
									'restaurant'           => 'https://schema.org/Restaurant',
									'winery'               => 'https://schema.org/Winery',
								),
								'government.office'          => 'https://schema.org/GovernmentOffice',
								'health.and.beauty.business' => array(
									'beauty.salon'               => 'https://schema.org/BeautySalon',
									'day.spa'                    => 'https://schema.org/DaySpa',
									'health.and.beauty.business' => 'https://schema.org/HealthAndBeautyBusiness',
									'hair.salon'                 => 'https://schema.org/HairSalon',
									'health.club'                => 'https://schema.org/HealthClub',
									'nail.salon'                 => 'https://schema.org/NailSalon',
									'tattoo.parlor'              => 'https://schema.org/TattooParlor',
								),
								'home.and.construction.business' => array(
									'electrician'                    => 'https://schema.org/Electrician',
									'general.contractor'             => 'https://schema.org/GeneralContractor',
									'hvac.business'                  => 'https://schema.org/HVACBusiness',
									'home.and.construction.business' => 'https://schema.org/HomeAndConstructionBusiness',
									'house.painter'                  => 'https://schema.org/HousePainter',
									'locksmith'                      => 'https://schema.org/Locksmith',
									'moving.company'                 => 'https://schema.org/MovingCompany',
									'plumber'                        => 'https://schema.org/Plumber',
									'roofing.contractor'             => 'https://schema.org/RoofingContractor',
								),
								'internet.cafe' => 'https://schema.org/InternetCafe',
								'legal.service' => array(
									'attorney'      => 'https://schema.org/Attorney',
									'legal.service' => 'https://schema.org/LegalService',
									'notary'        => 'https://schema.org/Notary',
								),
								'library'          => 'https://schema.org/Library',
								'local.business'   => 'https://schema.org/LocalBusiness',
								'lodging.business' => array(
									'bed.and.breakfast' => 'https://schema.org/BedAndBreakfast',
									'campground'        => 'https://schema.org/Campground',
									'lodging.business'  => 'https://schema.org/LodgingBusiness',
									'hostel'            => 'https://schema.org/Hostel',
									'hotel'             => 'https://schema.org/Hotel',
									'motel'             => 'https://schema.org/Motel',
									'resort'            => 'https://schema.org/Resort',
								),
								'medical.business' => array(
									'community.health.business' => 'https://schema.org/CommunityHealth',
									'dentist.business'          => 'https://schema.org/Dentist',
									'dermatology.business'      => 'https://schema.org/Dermatology',
									'diet.nutrition.business'   => 'https://schema.org/DietNutrition',
									'emergency.business'        => 'https://schema.org/Emergency',
									'geriatric.business'        => 'https://schema.org/Geriatric',
									'gynecologic.business'      => 'https://schema.org/Gynecologic',
									'medical.business'          => 'https://schema.org/MedicalBusiness',
									'medical.clinic'   => array(
										'medical.clinic'         => 'https://schema.org/MedicalClinic',
										'covid.testing.facility' => 'https://schema.org/CovidTestingFacility',
									),
									'midwifery.business'        => 'https://schema.org/Midwifery',
									'nursing.business'          => 'https://schema.org/Nursing',
									'obstetric.business'        => 'https://schema.org/Obstetric',
									'oncologic.business'        => 'https://schema.org/Oncologic',
									'optician.business'         => 'https://schema.org/Optician',
									'optometric.business'       => 'https://schema.org/Optometric',
									'otolaryngologic.business'  => 'https://schema.org/Otolaryngologic',
									'pediatric.business'        => 'https://schema.org/Pediatric',
									'pharmacy.business'         => 'https://schema.org/Pharmacy',
									'physician.business'        => 'https://schema.org/Physician',
									'physiotherapy.business'    => 'https://schema.org/Physiotherapy',
									'plastic.surgery.business'  => 'https://schema.org/PlasticSurgery',
									'podiatric.business'        => 'https://schema.org/Podiatric',
									'primary.care.business'     => 'https://schema.org/PrimaryCare',
									'psychiatric.business'      => 'https://schema.org/Psychiatric',
									'public.health.business'    => 'https://schema.org/PublicHealth',
								),
								'professional.service'     => 'https://schema.org/ProfessionalService',
								'radio.station'            => 'https://schema.org/RadioStation',
								'real.estate.agent'        => 'https://schema.org/RealEstateAgent',
								'recycling.center'         => 'https://schema.org/RecyclingCenter',
								'self.storage'             => 'https://schema.org/SelfStorage',
								'shopping.center'          => 'https://schema.org/ShoppingCenter',
								'sports.activity.location' => array(
									'sports.activity.location' => 'https://schema.org/SportsActivityLocation',
									'stadium.or.arena'         => 'https://schema.org/StadiumOrArena',
								),
								'store' => array(
									'auto.parts.store'       => 'https://schema.org/AutoPartsStore',
									'bike.store'             => 'https://schema.org/BikeStore',
									'book.store'             => 'https://schema.org/BookStore',
									'clothing.store'         => 'https://schema.org/ClothingStore',
									'computer.store'         => 'https://schema.org/ComputerStore',
									'convenience.store'      => 'https://schema.org/ConvenienceStore',
									'department.store'       => 'https://schema.org/DepartmentStore',
									'electronics.store'      => 'https://schema.org/ElectronicsStore',
									'florist'                => 'https://schema.org/Florist',
									'flower.shop'            => 'https://schema.org/Florist',
									'furniture.store'        => 'https://schema.org/FurnitureStore',
									'garden.store'           => 'https://schema.org/GardenStore',
									'grocery.store'          => 'https://schema.org/GroceryStore',
									'hardware.store'         => 'https://schema.org/HardwareStore',
									'hobby.shop'             => 'https://schema.org/HobbyShop',
									'home.goods.store'       => 'https://schema.org/HomeGoodsStore',
									'jewelry.store'          => 'https://schema.org/JewelryStore',
									'liquor.store'           => 'https://schema.org/LiquorStore',
									'mens.clothing.store'    => 'https://schema.org/MensClothingStore',
									'mobile.phone.store'     => 'https://schema.org/MobilePhoneStore',
									'movie.rental.store'     => 'https://schema.org/MovieRentalStore',
									'music.store'            => 'https://schema.org/MusicStore',
									'office.equipment.store' => 'https://schema.org/OfficeEquipmentStore',
									'outlet.store'           => 'https://schema.org/OutletStore',
									'pawn.shop'              => 'https://schema.org/PawnShop',
									'pet.store'              => 'https://schema.org/PetStore',
									'shoe.store'             => 'https://schema.org/ShoeStore',
									'sporting.goods.store'   => 'https://schema.org/SportingGoodsStore',
									'store'                  => 'https://schema.org/Store',
									'tire.shop'              => 'https://schema.org/TireShop',
									'toy.store'              => 'https://schema.org/ToyStore',
									'wholesale.store'        => 'https://schema.org/WholesaleStore',
								),
								'television.station'         => 'https://schema.org/TelevisionStation',
								'tourist.information.center' => 'https://schema.org/TouristInformationCenter',
								'travel.agency'              => 'https://schema.org/TravelAgency',
							),
							'place'     => 'https://schema.org/Place',
							'residence' => array(
								'residence'                   => 'https://schema.org/Residence',
								'residence.apartment.complex' => 'https://schema.org/ApartmentComplex',
								'residence.gated.community'   => 'https://schema.org/GatedResidenceCommunity',
							),
							'tourist.attraction'  => 'https://schema.org/TouristAttraction',
							'tourist.destination' => 'https://schema.org/TouristDestination',
						),
						'product' => array(
							'individual.product' => 'https://schema.org/IndividualProduct',	// Individual product with unique serial number.
							'product'            => 'https://schema.org/Product',
							'product.group'      => 'https://schema.org/ProductGroup',
							'product.model'      => 'https://schema.org/ProductModel',
							'some.products'      => 'https://schema.org/SomeProducts',
							'vehicle'            => array(
								'bus.or.coach'      => 'https://auto.schema.org/BusOrCoach',
								'car'               => 'https://auto.schema.org/Car',
								'motorcycle'        => 'https://auto.schema.org/Motorcycle',
								'motorized.bicycle' => 'https://auto.schema.org/MotorizedBicycle',
								'vehicle'           => 'https://auto.schema.org/Vehicle',
							),
						),
						'thing' => 'https://schema.org/Thing',
					),
				),	// End of 'schema_type' array.
				'schema_renamed' => array(	// Element of 'head' array.
					'anesthesia'              => 'anesthesia.specialty',
					'cardiovascular'          => 'cardiovascular.specialty',
					'community.health'        => 'community.health.specialty',
					'dentist'                 => 'dentist.organization',
					'dentistry'               => 'dentistry.specialty',
					'dermatologic'            => 'dermatologic.specialty',
					'dermatology'             => 'dermatology.specialty',
					'diet.nutrition'          => 'diet.nutrition.specialty',
					'emergency'               => 'emergency.specialty',
					'endocrine'               => 'endocrine.specialty',
					'event.venu'              => 'event.venue',
					'gastroenterologic'       => 'gastroenterologic.specialty',
					'genetic'                 => 'genetic.specialty',
					'geriatric'               => 'geriatric.specialty',
					'gynecologic'             => 'gynecologic.specialty',
					'hematologic'             => 'hematologic.specialty',
					'how.to'                  => 'howto',
					'infectious'              => 'infectious.specialty',
					'laboratory.science'      => 'laboratory.science.specialty',
					'medical.clinic.business' => 'medical.clinic',
					'medical.audience'        => 'audience.medical',
					'midwifery'               => 'midwifery.specialty',
					'movie.theatre'           => 'movie.theater',
					'musculoskeletal'         => 'musculoskeletal.specialty',
					'music.venu'              => 'music.venue',
					'neurologic'              => 'neurologic.specialty',
					'nursing'                 => 'nursing.specialty',
					'obstetric'               => 'obstetric.specialty',
					'occupational.therapy'    => 'occupational.therapy.specialty',
					'oncologic'               => 'oncologic.specialty',
					'optometric'              => 'optometric.specialty',
					'otolaryngologic'         => 'otolaryngologic.specialty',
					'pathology'               => 'pathology.specialty',
					'pediatric'               => 'pediatric.specialty',
					'pharmacy'                => 'pharmacy.organization',
					'physician'               => 'physician.organization',
					'physiotherapy'           => 'physiotherapy.specialty',
					'plastic.surgery'         => 'plastic.surgery.specialty',
					'podiatric'               => 'podiatric.specialty',
					'primary.care'            => 'primary.care.specialty',
					'psychiatric'             => 'psychiatric.specialty',
					'public.health'           => 'public.health.specialty',
					'pulmonary'               => 'pulmonary.specialty',
					'radiography'             => 'radiography.specialty',
					'renal'                   => 'renal.specialty',
					'respiratory.therapy'     => 'respiratory.therapy.specialty',
					'rheumatologic'           => 'rheumatologic.specialty',
					'service.bank.account'    => 'bank.account',
					'service.credit.card'     => 'credit.card',
					'service.deposit.account' => 'deposit.account',
					'residence.single.family' => 'house.single.family',
					'speech.pathology'        => 'speech.pathology.specialty',
					'surgical'                => 'surgical.specialty',
					'toxicologic'             => 'toxicologic.specialty',
					'train.trip'              => 'trip.train',
					'urologic'                => 'urologic.specialty',
				),

				/*
				 * See http://wiki.goodrelations-vocabulary.org/Documentation/UN/CEFACT_Common_Codes.
				 */
				'schema_units' => array(		// Element of 'head' array.
					'fluid_volume' => array(		// Unitcode index value.
						'additionalProperty' => array(	// Schema property name.
							'@context'   => 'https://schema.org',
							'@type'      => 'PropertyValue',
							'propertyID' => 'fluid_volume',
							'name'       => 'Fluid Volume',
							'unitText'   => 'ml',
							'unitCode'   => 'MLT',
						),
					),
					'height' => array(		// Unitcode index value.
						'height' => array(	// Schema property name.
							'@context' => 'https://schema.org',
							'@type'    => 'QuantitativeValue',
							'name'     => 'Height',
							'unitText' => 'cm',
							'unitCode' => 'CMT',
						),
					),
					'height_px' => array(		// Unitcode index value.
						'height' => array(	// Schema property name.
							'@context' => 'https://schema.org',
							'@type'    => 'QuantitativeValue',
							'name'     => 'Height',
							'unitText' => 'px',
							'unitCode' => 'E37',
						),
					),
					'length' => array(		// Unitcode index value.
						'depth' => array(	// Schema property name.
							'@context' => 'https://schema.org',
							'@type'    => 'QuantitativeValue',
							'name'     => 'Length or Depth',
							'unitText' => 'cm',
							'unitCode' => 'CMT',
						),
					),
					'size' => array(			// Unitcode index value.
						'additionalProperty' => array(	// Schema property name.
							'@context'   => 'https://schema.org',
							'@type'      => 'PropertyValue',
							'propertyID' => 'size',
							'name'       => 'Size',
						),
					),
					'weight' => array(		// Unitcode index value.
						'weight' => array(	// Schema property name.
							'@context' => 'https://schema.org',
							'@type'    => 'QuantitativeValue',
							'name'     => 'Weight',
							'unitText' => 'kg',
							'unitCode' => 'KGM',
						),
					),
					'width' => array(		// Unitcode index value.
						'width' => array(	// Schema property name.
							'@context' => 'https://schema.org',
							'@type'    => 'QuantitativeValue',
							'name'     => 'Width',
							'unitText' => 'cm',
							'unitCode' => 'CMT',
						),
					),
					'width_px' => array(		// Unitcode index value.
						'width' => array(	// Schema property name.
							'@context' => 'https://schema.org',
							'@type'    => 'QuantitativeValue',
							'name'     => 'Width',
							'unitText' => 'px',
							'unitCode' => 'E37',
						),
					),
				),

				/*
				 * See https://developers.google.com/search/docs/appearance/structured-data/review-snippet.
				 *
				 * Google allows the 'aggregateRating' property only for these types:
				 *
				 *	Book
				 *	Course
				 *	Event
				 *	HowTo (includes Recipe)
				 *	LocalBusiness
				 *	Movie
				 *	Product
				 *	SoftwareApplication
				 *
				 * Google allows the 'review' property only for these types:
				 *
				 *	Book
				 *	Course
				 *	CreativeWorkSeason
				 *	CreativeWorkSeries
				 *	Episode
				 *	Event
				 *	Game
				 *	HowTo (includes Recipe)
				 *	LocalBusiness
				 *	MediaObject
				 *	Movie
				 *	MusicPlaylist
				 * 	MusicRecording
				 *	Organization
				 *	Product
				 *	SoftwareApplication
				 */
				'schema_aggregate_rating_parents' => array(	// Element of 'head' array.
					'book',
					'course',
					'event',
					'howto',
					'local.business',
					'movie',
					'product',
					'software.application',
				),
				'schema_review_parents' => array(	// Element of 'head' array.
					'book',
					'course',
					'creative.work.season',
					'creative.work.series',
					'episode',
					'event',
					'game',
					'howto',
					'local.business',
					'media.object',
					'movie',
					'music.playlist',
					'music.recording',
					'organization',
					'product',
					'software.application',
				),

				/*
				 * Good Relations.
				 */
				'schema_gr' => array(			// Element of 'head' array.
					'DeliveryMethod' => array(
						'Download' => 'http://purl.org/goodrelations/v1#DeliveryModeDirectDownload',
						'Freight'  => 'http://purl.org/goodrelations/v1#DeliveryModeFreight',
						'Mail'     => 'http://purl.org/goodrelations/v1#DeliveryModeMail',
						'Fleet'    => 'http://purl.org/goodrelations/v1#DeliveryModeOwnFleet',
						'PickUp'   => 'http://purl.org/goodrelations/v1#DeliveryModePickUp',
						'DHL'      => 'http://purl.org/goodrelations/v1#DHL',
						'FedEx'    => 'http://purl.org/goodrelations/v1#FederalExpress',
						'UPS'      => 'http://purl.org/goodrelations/v1#UPS',
					),
				),
			),	// End of 'head' array.
			'extend' => array(
				'https://wpsso.com/extend/plugins/',
			),
		);	// End of 'cf' array.

		public static function get_version( $add_slug = false ) {

			$info = self::$cf[ 'plugin' ][ 'wpsso' ];

			return $add_slug ? $info[ 'slug' ] . '-' . $info[ 'version' ] : $info[ 'version' ];
		}

		public static function get_config( $read_cache = true ) {

			if ( $read_cache ) {

				if ( ! empty( self::$cf[ 'config_filtered' ] ) ) {	// Only if already filtered.

					return self::$cf;
				}
			}

			self::$cf[ '*' ] = array(
				'base' => array(),
				'lib'  => array(
					'pro' => array(),
					'std' => array(),
				),
				'version' => '',
			);

			self::$cf[ 'opt' ][ 'version' ] = '';

			self::$cf[ 'config_filtered' ] = false;

			/*
			 * Wpsso->__construct() calls WpssoConfig::get_config() before WpssoConfig::set_constants(), which defines
			 * 'WPSSO_VERSION', so use 'WPSSO_VERSION' as a signal to skip applying filters until later.
			 */
			if ( ! defined( 'WPSSO_VERSION' ) ) {

				return self::$cf;
			}

			/*
			 * Apply filters to have add-ons include their config.
			 */
			if ( empty( self::$cf[ 'config_filtered' ] ) ) {

				self::$cf = apply_filters( 'wpsso_get_config', self::$cf );
			}

			self::$cf[ 'config_filtered' ] = true;

			$pro_disable = defined( 'WPSSO_PRO_DISABLE' ) && WPSSO_PRO_DISABLE ? true : false;

			foreach ( self::$cf[ 'plugin' ] as $ext => $info ) {

				$pkg_dir = 'std';

				if ( ! $pro_disable ) {

					$ext_dir = self::get_ext_dir( $ext, $read_cache );

					if ( is_dir( $ext_dir . 'lib/pro/' ) ) {

						$pkg_dir = 'pro';
					}
				}

				if ( isset( $info[ 'slug' ] ) ) {

					self::$cf[ '*' ][ 'slug' ][ $info[ 'slug' ] ] = $ext;
				}

				if ( isset( $info[ 'base' ] ) ) {

					self::$cf[ '*' ][ 'base' ][ $info[ 'base' ] ] = $ext;
				}

				if ( isset( $info[ 'lib' ] ) && is_array( $info[ 'lib' ] ) ) {

					self::$cf[ '*' ][ 'lib' ] = SucomUtil::array_merge_recursive_distinct( self::$cf[ '*' ][ 'lib' ], $info[ 'lib' ] );
				}

				if ( isset( $info[ 'version' ] ) ) {

					self::$cf[ '*' ][ 'version' ] .= '-' . $ext . $info[ 'version' ] . $pkg_dir;
				}

				if ( isset( $info[ 'opt_version' ] ) ) {

					self::$cf[ 'opt' ][ 'version' ] .= '-' . $ext . $info[ 'opt_version' ] . $pkg_dir;
				}

				/*
				 * Maybe complete relative paths in the assets array.
				 */
				if ( ! empty( $info[ 'base' ] ) ) {	// Just in case.

					/*
					 * Returns a plugin base URL like 'https://wpsso.com/wp-content/plugins/wpsso/'.
					 */
					$plugins_url_base = trailingslashit( plugins_url( '', $info[ 'base' ] ) );

					array_walk_recursive( self::$cf[ 'plugin' ][ $ext ][ 'assets' ], array( __CLASS__, 'maybe_prefix_base_url' ), $plugins_url_base );
				}
			}

			return self::$cf;
		}

		private static function maybe_prefix_base_url( &$url, $key, $plugins_url_base ) {

			if ( ! empty( $url ) && false === strpos( $url, '//' ) ) {

				$url = $plugins_url_base . $url;
			}
		}

		public static function set_constants( $plugin_file ) {

			if ( defined( 'WPSSO_VERSION' ) ) {	// Define constants only once.

				return;
			}

			$info =& self::$cf[ 'plugin' ][ 'wpsso' ];

			$nonce_key = defined( 'NONCE_KEY' ) ? NONCE_KEY : '';

			/*
			 * Define fixed constants.
			 */
			define( 'WPSSO_DATA_ID', 'wpsso structured data' );
			define( 'WPSSO_FILEPATH', $plugin_file );
			define( 'WPSSO_NONCE_NAME', md5( $nonce_key . var_export( $info, $return = true ) ) );
			define( 'WPSSO_PLUGINBASE', $info[ 'base' ] );	// Example: wpsso/wpsso.php.
			define( 'WPSSO_PLUGINDIR', trailingslashit( realpath( dirname( $plugin_file ) ) ) );
			define( 'WPSSO_PLUGINSLUG', $info[ 'slug' ] );	// Example: wpsso.
			define( 'WPSSO_UNDEF', -1 );			// Default undefined image width / height value.
			define( 'WPSSO_URLPATH', trailingslashit( plugins_url( '', $plugin_file ) ) );
			define( 'WPSSO_VERSION', $info[ 'version' ] );

			define( 'WPSSO_INIT_CONFIG_PRIORITY', -10 );
			define( 'WPSSO_INIT_OPTIONS_PRIORITY', 9 );
			define( 'WPSSO_INIT_OBJECTS_PRIORITY', 10 );
			define( 'WPSSO_INIT_JSON_FILTERS_PRIORITY', 11 );
			define( 'WPSSO_INIT_SHORTCODES_PRIORITY', 11 );
			define( 'WPSSO_INIT_PLUGIN_PRIORITY', 12 );

			/*
			 * Define variable constants.
			 */
			self::set_variable_constants();
		}

		public static function set_variable_constants( $var_const = null ) {

			if ( ! is_array( $var_const ) ) {

				$var_const = self::get_variable_constants();
			}

			/*
			 * Define the variable constants, if not already defined.
			 */
			foreach ( $var_const as $name => $value ) {

				if ( ! defined( $name ) ) {

					define( $name, $value );
				}
			}
		}

		public static function get_variable_constants() {

			$var_const = array();

			if ( defined( 'WPSSO_PLUGINDIR' ) ) {

				$var_const[ 'WPSSO_ARTICLE_SECTIONS_LIST' ]   = WPSSO_PLUGINDIR . 'share/article-sections.txt';
				$var_const[ 'WPSSO_PRODUCT_CATEGORIES_LIST' ] = WPSSO_PLUGINDIR . 'share/product-categories.txt';
			}


			/*
			 * MENU_ORDER (aka menu_position):
			 *
			 *	null – below Comments
			 *	5 – below Posts
			 *	10 – below Media
			 *	15 – below Links
			 *	20 – below Pages
			 *	25 – below comments
			 *	60 – below first separator
			 *	65 – below Plugins
			 *	70 – below Users
			 *	75 – below Tools
			 *	80 – below Settings
			 *	100 – below second separator
			 */
			$var_const[ 'WPSSO_MENU_ORDER' ]                  = 80;			// Position of the SSO settings menu item.
			$var_const[ 'WPSSO_TB_NOTICE_MENU_ORDER' ]        = 55;			// Position of the SSO notices toolbar menu item.
			$var_const[ 'WPSSO_TB_LOCALE_MENU_ORDER' ]        = 56;			// Position of the user locale toolbar menu item.
			$var_const[ 'WPSSO_TB_VALIDATE_MENU_ORDER' ]      = 57;			// Position of the validate menu item.
			$var_const[ 'WPSSO_TB_VIEW_PROFILE_MENU_ORDER' ]  = 80;			// Position of the view profile menu item.
			$var_const[ 'WPSSO_ADD_ROLE_MAX_TIME' ]           = 300;		// 5 minutes.
			$var_const[ 'WPSSO_REMOVE_ROLE_MAX_TIME' ]        = 300;		// 5 minutes.
			$var_const[ 'WPSSO_CACHE_ARRAY_FIFO_MAX' ]        = 5;
			$var_const[ 'WPSSO_CACHE_DIR' ]                   = self::get_cache_dir();
			$var_const[ 'WPSSO_CACHE_FILES_EXP_SECS' ]        = MONTH_IN_SECONDS;	// See WpssoUtilCache->clear_expired_cache_files().
			$var_const[ 'WPSSO_CACHE_REFRESH_MAX_TIME' ]      = 1800;		// 30 minutes.
			$var_const[ 'WPSSO_CACHE_SELECT_JSON_EXP_SECS' ]  = WEEK_IN_SECONDS;	// Javascript URLs for Schema types, article sections, and product categories.
			$var_const[ 'WPSSO_CACHE_URL' ]                   = self::get_cache_url();
			$var_const[ 'WPSSO_CONTENT_BLOCK_FILTER_OUTPUT' ] = true;		// Monitor and fix incorrectly coded filter hooks.
			$var_const[ 'WPSSO_CONTENT_FILTERS_MAX_TIME' ]    = 1.50;		// Issue a warning if the content filter takes longer than 1 second.
			$var_const[ 'WPSSO_CONTENT_IMAGES_MAX' ]          = 5;			// Maximum number of images extracted from the content.
			$var_const[ 'WPSSO_DUPE_CHECK_HEADER_COUNT' ]     = 3;			// Maximum number of times to check for duplicates.
			$var_const[ 'WPSSO_DUPE_CHECK_TIMEOUT_TIME' ]     = 3.00;		// Hard-limit - most crawlers time-out after 3 seconds.
			$var_const[ 'WPSSO_DUPE_CHECK_WARNING_TIME' ]     = 2.50;		// Issue a warning if getting shortlink took more than 2.5 seconds.
			$var_const[ 'WPSSO_IMAGE_MAKE_SIZE_MAX_TIME' ]    = 5.00;		// Send error to trigger_error() if image_make_intermediate_size() takes longer.
			$var_const[ 'WPSSO_METABOX_TAB_LAYOUT' ]          = 'vertical';		// Default tab layout (vertical or horizontal).
			$var_const[ 'WPSSO_PHP_GETIMGSIZE_MAX_TIME' ]     = 3.00;		// Send an error to trigger_error() if getimagesize() takes longer.
			$var_const[ 'WPSSO_READING_WORDS_PER_MIN' ]       = 200;		// Estimated reading words per minute.
			$var_const[ 'WPSSO_SCHEDULE_SINGLE_EVENT_TIME' ]  = 10;			// Schedule single events for now + 10 seconds.
			$var_const[ 'WPSSO_SELECT_PERSON_NAMES_MAX' ]     = 200;		// Maximum number of persons to include in a form select.

			/*
			 * Schema limits.
			 */
			$var_const[ 'WPSSO_SCHEMA_COMMENTS_MAX' ]            = 50;		// Maximum number of comments when "Break comments into pages" is unchecked.
			$var_const[ 'WPSSO_SCHEMA_HOWTO_STEPS_MAX' ]         = 40;
			$var_const[ 'WPSSO_SCHEMA_HOWTO_SUPPLIES_MAX' ]      = 30;
			$var_const[ 'WPSSO_SCHEMA_HOWTO_TOOLS_MAX' ]         = 20;
			$var_const[ 'WPSSO_SCHEMA_PRODUCT_VALID_MAX_TIME' ]  = YEAR_IN_SECONDS;	// Used for Schema 'priceValidUntil' property default.
			$var_const[ 'WPSSO_SCHEMA_RECIPE_INGREDIENTS_MAX' ]  = 40;
			$var_const[ 'WPSSO_SCHEMA_RECIPE_INSTRUCTIONS_MAX' ] = 40;
			$var_const[ 'WPSSO_SCHEMA_REVIEWS_MAX' ]             = 100;

			/*
			 * Setting and meta array names.
			 */
			$var_const[ 'WPSSO_DISMISS_NAME' ]          = 'wpsso_dismissed';
			$var_const[ 'WPSSO_NOTICES_NAME' ]          = 'wpsso_notices';
			$var_const[ 'WPSSO_META_NAME' ]             = '_wpsso_meta';
			$var_const[ 'WPSSO_META_ATTACHED_NAME' ]    = '_wpsso_meta_attached';
			$var_const[ 'WPSSO_META_RATING_NAME' ]      = 'rating';
			$var_const[ 'WPSSO_PREF_NAME' ]             = '_wpsso_pref';
			$var_const[ 'WPSSO_OPTIONS_NAME' ]          = 'wpsso_options';
			$var_const[ 'WPSSO_REG_TS_NAME' ]           = 'wpsso_timestamps';
			$var_const[ 'WPSSO_SITE_OPTIONS_NAME' ]     = 'wpsso_site_options';
			$var_const[ 'WPSSO_POST_CHECK_COUNT_NAME' ] = 'wpsso_post_check_count';
			$var_const[ 'WPSSO_TMPL_HEAD_CHECK_NAME' ]  = 'wpsso_tmpl_head_check';
			$var_const[ 'WPSSO_WP_CONFIG_CHECK_NAME' ]  = 'wpsso_wp_config_check';
			$var_const[ 'WPSSO_PAGE_TAG_TAXONOMY' ]     = 'page_tag';

			/*
			 * Hook priorities.
			 */
			$var_const[ 'WPSSO_ADD_MENU_PRIORITY' ]      = -20;	// 'admin_menu' hook priority.
			$var_const[ 'WPSSO_ADD_SUBMENU_PRIORITY' ]   = -10;	// 'admin_menu' hook priority.
			$var_const[ 'WPSSO_ADD_COLUMN_PRIORITY' ]    = 100;
			$var_const[ 'WPSSO_ADMIN_SCRIPTS_PRIORITY' ] = -1000;	// 'admin_enqueue_scripts' hook priority.
			$var_const[ 'WPSSO_BLOCK_ASSETS_PRIORITY' ]  = -1000;	// 'enqueue_block_editor_assets' hook priority.
			$var_const[ 'WPSSO_HEAD_PRIORITY' ]          = -10;
			$var_const[ 'WPSSO_META_SAVE_PRIORITY' ]     = -1000;	// Save custom post/term/user meta before clearing the cache.
			$var_const[ 'WPSSO_META_CLEAR_PRIORITY' ]    = -100;	// Clear cache before priority 10 (where most caching plugins are hooked).
			$var_const[ 'WPSSO_META_REFRESH_PRIORITY' ]  = -10;	// Refresh cache before priority 10 (where most caching plugins are hooked).
			$var_const[ 'WPSSO_TITLE_TAG_PRIORITY' ]     = 1000;	// Priority for the WordPress 'document_title' filters.

			/*
			 * PHP cURL library settings.
			 */
			$var_const[ 'WPSSO_PHP_CURL_CAINFO' ]             = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
			$var_const[ 'WPSSO_PHP_CURL_USERAGENT' ]          = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:128.0) Gecko/20100101 Firefox/128.0';
			$var_const[ 'WPSSO_PHP_CURL_USERAGENT_FACEBOOK' ] = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';

			/*
			 * Maybe override the default constant value with a pre-defined constant value.
			 */
			foreach ( $var_const as $name => $value ) {

				if ( defined( $name ) ) {

					$var_const[ $name ] = constant( $name );
				}
			}

			return $var_const;
		}

		/*
		 * Load all essential library files.
		 *
		 * Avoid calling is_admin() here as it can be unreliable this early in the load process - some plugins that operate
		 * outside of the standard WordPress load process do not define WP_ADMIN as they should (which is required by
		 * is_admin() this early in the WordPress load process).
		 */
		public static function require_libs( $plugin_file ) {

			require_once WPSSO_PLUGINDIR . 'lib/com/cache.php';
			require_once WPSSO_PLUGINDIR . 'lib/com/nodebug.php';	// Always load the debug fallback class.
			require_once WPSSO_PLUGINDIR . 'lib/com/nonotice.php';	// Always load the notice fallback class.
			require_once WPSSO_PLUGINDIR . 'lib/com/plugin.php';
			require_once WPSSO_PLUGINDIR . 'lib/com/util.php';
			require_once WPSSO_PLUGINDIR . 'lib/com/util-options.php';
			require_once WPSSO_PLUGINDIR . 'lib/com/util-wp.php';

			require_once WPSSO_PLUGINDIR . 'lib/check.php';
			require_once WPSSO_PLUGINDIR . 'lib/exception.php';	// Extends ErrorException.
			require_once WPSSO_PLUGINDIR . 'lib/functions.php';
			require_once WPSSO_PLUGINDIR . 'lib/head.php';
			require_once WPSSO_PLUGINDIR . 'lib/media.php';
			require_once WPSSO_PLUGINDIR . 'lib/options.php';
			require_once WPSSO_PLUGINDIR . 'lib/page.php';
			require_once WPSSO_PLUGINDIR . 'lib/register.php';
			require_once WPSSO_PLUGINDIR . 'lib/script.php';
			require_once WPSSO_PLUGINDIR . 'lib/style.php';
			require_once WPSSO_PLUGINDIR . 'lib/util.php';		// Extends SucomUtil.

			/*
			 * Comment, post, term, user modules.
			 */
			require_once WPSSO_PLUGINDIR . 'lib/abstract/wp-meta.php';
			require_once WPSSO_PLUGINDIR . 'lib/comment.php';	// Extends WpssoAbstractWpMeta.
			require_once WPSSO_PLUGINDIR . 'lib/post.php';		// Extends WpssoAbstractWpMeta.
			require_once WPSSO_PLUGINDIR . 'lib/term.php';		// Extends WpssoAbstractWpMeta.
			require_once WPSSO_PLUGINDIR . 'lib/user.php';		// Extends WpssoAbstractWpMeta.

			/*
			 * Meta tags and markup.
			 */
			require_once WPSSO_PLUGINDIR . 'lib/link-rel.php';
			require_once WPSSO_PLUGINDIR . 'lib/meta-name.php';
			require_once WPSSO_PLUGINDIR . 'lib/oembed.php';
			require_once WPSSO_PLUGINDIR . 'lib/opengraph.php';
			require_once WPSSO_PLUGINDIR . 'lib/pinterest.php';
			require_once WPSSO_PLUGINDIR . 'lib/schema.php';
			require_once WPSSO_PLUGINDIR . 'lib/twittercard.php';

			/*
			 * Module library loader.
			 */
			require_once WPSSO_PLUGINDIR . 'lib/loader.php';

			/*
			 * Add-ons library.
			 */
			require_once WPSSO_PLUGINDIR . 'lib/abstract/add-on.php';

			add_filter( 'wpsso_load_lib', array( __CLASS__, 'load_lib' ), 10, 3 );
		}

		public static function load_lib( $success = false, $filespec = '', $classname = '' ) {

			if ( false !== $success ) {

				return $success;
			}

			if ( ! empty( $classname ) ) {

				if ( class_exists( $classname ) ) {

					return $classname;
				}
			}

			if ( ! empty( $filespec ) ) {

				$file_path = WPSSO_PLUGINDIR . 'lib/' . $filespec . '.php';

				if ( file_exists( $file_path ) ) {

					require_once $file_path;

					if ( empty( $classname ) ) {

						return SucomUtil::sanitize_classname( 'wpsso' . $filespec, $allow_underscore = false );
					}

					return $classname;
				}
			}

			return $success;
		}

		/*
		 * Since WPSSO Core v4.18.1.
		 */
		public static function get_cache_dir() {

			if ( defined( 'WPSSO_CACHE_DIR' ) ) {

				return WPSSO_CACHE_DIR;
			}

			if ( defined( 'WP_CONTENT_DIR' ) ) {

				$content_dir = trailingslashit( WP_CONTENT_DIR );

				if ( self::is_cache_dir( $content_dir . 'cache/wpsso/' ) ) {

					return $content_dir . 'cache/wpsso/';
				}
			}

			if ( defined( 'WPSSO_PLUGINDIR' ) ) {

				if ( self::is_cache_dir( WPSSO_PLUGINDIR . 'cache/' ) ) {

					return WPSSO_PLUGINDIR . 'cache/';
				}
			}

			return false;
		}

		/*
		 * Since WPSSO Core v4.18.1.
		 */
		public static function get_cache_url() {

			if ( defined( 'WPSSO_CACHE_URL' ) ) {

				return WPSSO_CACHE_URL;
			}

			if ( defined( 'WP_CONTENT_DIR' ) ) {

				$content_dir = trailingslashit( WP_CONTENT_DIR );

				if ( self::is_cache_dir( $content_dir . 'cache/wpsso/' ) ) {

					return content_url( 'cache/wpsso/' );
				}
			}

			if ( defined( 'WPSSO_PLUGINDIR' ) ) {

				if ( self::is_cache_dir( WPSSO_PLUGINDIR . 'cache/' ) ) {

					if ( defined( 'WPSSO_URLPATH' ) ) {	// Just in case.

						return WPSSO_URLPATH . 'cache/';
					}
				}
			}

			return false;
		}

		/*
		 * Since WPSSO Core v4.18.1.
		 */
		private static function is_cache_dir( $dir ) {

			$dir = trailingslashit( $dir );		// Just in case.

			$index_file  = $dir . 'index.php';
			$access_file = $dir . '.htaccess';

			if ( file_exists( $index_file ) ) {	// Assume directory permissions are good.

				return true;
			}

			$index_str  = '<?php // These aren\'t the droids you\'re looking for.' . "\n";
			$access_str = '<FilesMatch "\.(php|pl|cgi|shtml)$">
	# Apache 2.2
	<IfModule !mod_authz_core.c>
		Order	deny,allow
		Deny	from all
	</IfModule>
	# Apache 2.4
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
</FilesMatch>' . "\n";

			if ( is_dir( $dir ) || @mkdir( $dir, $mode = 0755, $recursive = true ) ) {

				if ( ( $index_fh = @fopen( $index_file, $mode = 'wb' ) ) &&
					( $access_fh = @fopen( $access_file, $mode = 'wb' ) ) ) {

					if ( @fwrite( $index_fh, $index_str ) && @fwrite( $access_fh, $access_str ) ) {

						fclose( $index_fh );
						fclose( $access_fh );

						@chmod( $index_file, $mode = 0644 );
						@chmod( $access_file, $mode = 0644 );

						return true;
					}
				}
			}

			return false;
		}

		/*
		 * Since WPSSO Core v3.38.3.
		 *
		 * Returns $cf[ 'plugin' ] with 'wpsso' as the first array element and all other plugins sorted by translated name.
		 */
		public static function get_ext_sorted() {

			$cf = self::get_config();

			/*
			 * Sort the array by plugin name and maintain index association.
			 */
			uasort( $cf[ 'plugin' ], array( __CLASS__, 'sort_plugin_by_name_key' ) );

			reset( $cf[ 'plugin' ] );	// Just in case.

			$first_key = key( $cf[ 'plugin' ] );

			/*
			 * Make sure the core plugin is listed first.
			 */
			if ( 'wpsso' !== $first_key ) {

				SucomUtil::move_to_front( $cf[ 'plugin' ], 'wpsso' );
			}

			return $cf[ 'plugin' ];
		}

		/*
		 * Returns 'plugin' or 'add-on' translated string.
		 */
		public static function get_ext_type_transl( $ext ) {

			return 'wpsso' === $ext ? _x( 'plugin', 'plugin type', 'wpsso' ) : _x( 'add-on', 'plugin type', 'wpsso' );
		}

		public static function get_ext_text_domain( $ext ) {

			$cf          = self::get_config();
			$text_domain = false;

			if ( isset( $cf[ 'plugin' ][ $ext ][ 'text_domain' ] ) ) {

				$text_domain = $cf[ 'plugin' ][ $ext ][ 'text_domain' ];
			}

			return $text_domain;
		}

		/*
		 * Since WPSSO Core v7.8.0.
		 *
		 * Returns false or a slashed directory path.
		 */
		public static function get_ext_dir( $ext, $read_cache = true ) {

			static $local_cache = array();

			if ( $read_cache ) {

				if ( isset( $local_cache[ $ext ] ) ) {

					return $local_cache[ $ext ];
				}
			}

			/*
			 * Check for active plugin constant first.
			 */
			$ext_dir_const = strtoupper( $ext ) . '_PLUGINDIR';

			if ( defined( $ext_dir_const ) && is_dir( $ext_dir = constant( $ext_dir_const ) ) ) {

				return $local_cache[ $ext ] = trailingslashit( $ext_dir );
			}

			/*
			 * Fallback to checking for inactive plugin folder.
			 */
			$cf = self::get_config();

			if ( isset( $cf[ 'plugin' ][ $ext ][ 'slug' ] ) ) {

				$slug = $cf[ 'plugin' ][ $ext ][ 'slug' ];

				if ( defined ( 'WPMU_PLUGIN_DIR' ) && is_dir( $ext_dir = trailingslashit( WPMU_PLUGIN_DIR ) . $slug . '/' ) ) {

					return $local_cache[ $ext ] = $ext_dir;
				}

				if ( defined ( 'WP_PLUGIN_DIR' ) && is_dir( $ext_dir = trailingslashit( WP_PLUGIN_DIR ) . $slug . '/' ) ) {

					return $local_cache[ $ext ] = $ext_dir;
				}
			}

			return $local_cache[ $ext ] = false;
		}

		/*
		 * Returns false, a slashed directory path, or the file name path.
		 *
		 * Use $file_is_dir = true when specifically checking for a sub-folder path.
		 */
		public static function get_ext_file_path( $ext, $rel_file, $file_is_dir = false ) {

			$file_path = false;

			if ( $ext_dir = self::get_ext_dir( $ext ) ) {	// Returns false or a slashed directory path.

				$rel_file = SucomUtil::sanitize_file_path( $rel_file );

				if ( $file_is_dir ) {	// Must be a directory.

					if ( is_dir( trailingslashit( $ext_dir . $rel_file ) ) ) {

						$file_path = trailingslashit( $ext_dir . $rel_file );
					}

				} elseif ( file_exists( $ext_dir . $rel_file ) ) {

					$file_path = $ext_dir . $rel_file;
				}
			}

			return $file_path;
		}

		public static function get_ext_file_url( $ext, $rel_file ) {

			$cf = self::get_config();

			$url_key = SucomUtil::sanitize_hookname( basename( $rel_file ) );	// Changes html/setup.html to setup_html (note underscore).

			$file_url = false;

			if ( isset( $cf[ 'plugin' ][ $ext ][ 'url' ][ $url_key ] ) ) {

				/*
				 * Returns URL or false on failure.
				 *
				 * See https://developer.wordpress.org/reference/functions/wp_http_validate_url/
				 */
				$file_url = wp_http_validate_url( $cf[ 'plugin' ][ $ext ][ 'url' ][ $url_key ] );
			}

			return $file_url;
		}

		/*
		 * Since WPSSO Core v14.0.0.
		 */
		public static function get_schema_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = apply_filters( 'wpsso_schema_units', self::$cf[ 'head' ][ 'schema_units' ] );
			}

			return $local_cache;
		}

		/*
		 * Since WPSSO Core v9.0.0.
		 */
		public static function get_social_accounts( $key = false ) {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = apply_filters( 'wpsso_social_accounts', self::$cf[ 'form' ][ 'social_accounts' ] );

				foreach ( $local_cache as $k => $label ) {

					$local_cache[ $k ] = _x( $label, 'option value', 'wpsso' );
				}

				/*
				 * Sort the associative array by value (ie. the translated label).
				 */
				method_exists( 'SucomUtil', 'natasort' ) ? SucomUtil::natasort( $local_cache ) : uasort( $local_cache, 'strnatcasecmp' );
			}

			if ( false !== $key ) {

				if ( $key && is_string( $key ) ) {

					if ( isset( $local_cache[ $key ] ) ) {	// Just in case.

						return $local_cache[ $key ];
					}
				}

				return null;
			}

			return $local_cache;
		}

		/*
		 * Since WPSSO Core v11.5.0.
		 */
		public static function get_input_limits( $opt_key = false ) {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = self::$cf[ 'form' ][ 'input_limits' ];

				foreach ( $local_cache as $key => $input_limits ) {

					$filter_name = SucomUtil::sanitize_hookname( 'wpsso_input_limits_' . $key );

					$local_cache[ $key ] = apply_filters( $filter_name, $input_limits );
				}
			}

			if ( false !== $opt_key ) {

				if ( $opt_key && is_string( $opt_key ) ) {

					if ( isset( $local_cache[ $opt_key ] ) ) {

						return $local_cache[ $opt_key ];
					}
				}

				return null;
			}

			return $local_cache;
		}

		/*
		 * Since WPSSO Core v14.2.0.
		 *
		 * Provides a key index for attributes to meta data options.
		 *
		 * Returns false or an options key.
		 */
		public static function get_attr_md_index( $md_key = false ) {

			return self::get_opt_md_info( $md_index = 'attr_md_index', $md_key );
		}

		/*
		 * Since WPSSO Core v14.0.0.
		 *
		 * Provides a key index for custom fields to meta data options.
		 *
		 * Returns false or an options key.
		 */
		public static function get_cf_md_index( $md_key = false ) {

			return self::get_opt_md_info( $md_index = 'cf_md_index', $md_key );
		}

		/*
		 * Since WPSSO Core v14.0.0.
		 *
		 * Provides information to help read and split custom field values into numbered options.
		 *
		 * Returns true, false, or an array.
		 */
		public static function get_md_keys_multi( $md_key = false ) {

			return self::get_opt_md_info( $md_index = 'md_keys_multi', $md_key );
		}

		/*
		 * Since WPSSO Core v11.6.0.
		 *
		 * Returns an array of metadata keys (can be empty).
		 *
		 * $md_key = true | false | string | array
		 */
		public static function get_md_keys_fallback( $md_key = false ) {

			static $local_cache = null;

			if ( is_array( $md_key ) ) {	// Just in case.

				return $md_key;		// Return an array.

			} elseif ( null === $local_cache ) {

				$local_cache = self::$cf[ 'opt' ][ 'md_keys_fallback' ];

				foreach ( $local_cache as $key => $fallback ) {

					$filter_name = SucomUtil::sanitize_hookname( 'wpsso_md_keys_fallback_' . $key );

					$local_cache[ $key ] = apply_filters( $filter_name, $fallback );
				}
			}

			if ( false !== $md_key ) {

				if ( $md_key && is_string( $md_key ) ) {

					if ( isset( $local_cache[ $md_key ] ) ) {

						return $local_cache[ $md_key ];	// Return an array.
					}

					return array( $md_key );	// Return an array.
				}

				return array();	// Return an array.
			}

			return $local_cache;	// Return an array.
		}

		/*
		 * Since WPSSO Core v14.2.0.
		 *
		 * Provides a key index for attributes and custom fields to meta data options.
		 *
		 * Provides information to help read and split attribute and custom field values into numbered options.
		 *
		 * Example $md_index values: 'attr_md_index', 'cf_md_index', or 'md_keys_multi'.
		 */
		private static function get_opt_md_info( $md_index, $md_key = false ) {

			static $local_cache = array();

			if ( ! isset( $local_cache[ $md_index ] ) ) {

				$local_cache[ $md_index ] = isset( self::$cf[ 'opt' ][ $md_index ] ) ? self::$cf[ 'opt' ][ $md_index ] : array();

				/*
				 * See WpssoIntegRecipeWpRecipeMaker->filter_cf_md_index().
				 */
				$local_cache[ $md_index ] = apply_filters( 'wpsso_' . $md_index, $local_cache[ $md_index ] );
			}

			if ( false !== $md_key ) {

				if ( isset( $local_cache[ $md_index ][ $md_key ] ) ) {

					return $local_cache[ $md_index ][ $md_key ];
				}

				return false;
			}

			return $local_cache[ $md_index ];
		}

		private static function sort_plugin_by_name_key( $a, $b ) {

			if ( isset( $a[ 'name' ] ) && isset( $b[ 'name' ] ) ) {

				$a[ 'name' ] = _x( $a[ 'name' ], 'plugin name', 'wpsso' );
				$b[ 'name' ] = _x( $b[ 'name' ], 'plugin name', 'wpsso' );

				/*
				 * Case insensitive string comparisons using a "natural order" algorithm.
				 */
				return strnatcasecmp( $a[ 'name' ], $b[ 'name' ] );
			}

			return 0;	// No change.
		}
	}
}
