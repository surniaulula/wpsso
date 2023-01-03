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

if ( ! class_exists( 'WpssoMessagesTooltipPlugin' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltip->get() only when needed.
	 */
	class WpssoMessagesTooltipPlugin extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$text = '';

			switch ( $msg_key ) {

				/**
				 * SSO > Advanced Settings > Plugin Settings > Plugin Admin tab.
				 */
				case 'tooltip-plugin_clean_on_uninstall':	// Remove Settings on Uninstall.

					$text = sprintf( __( 'Check this option to remove all %s settings when you <em>uninstall</em> the plugin. This includes any custom post, term, and user meta.', 'wpsso' ), $info[ 'short' ] );

					break;

				case 'tooltip-plugin_load_mofiles': 		// Use Local Plugin Translations.

					$def_checked = $this->get_def_checked( 'plugin_load_mofiles' );

					$text = sprintf( __( 'Prefer local translation files instead of translations from WordPress.org (default is %s).', 'wpsso' ), $def_checked );

					break;

				case 'tooltip-plugin_cache_disable': 		// Disable Cache for Debugging.

					$def_checked = $this->get_def_checked( 'plugin_cache_disable' );

					$text = sprintf( __( 'Disable the head markup transient cache for debugging purposes (default is %s).', 'wpsso' ), $def_checked );

					break;

				case 'tooltip-plugin_debug_html': 		// Add HTML Debug Messages.

					$def_checked = $this->get_def_checked( 'plugin_debug_html' );

					$text = sprintf( __( 'Add hidden debugging messages as HTML comments to front-end and admin webpages (default is %s).', 'wpsso' ), $def_checked );

					break;

				/**
				 * SSO > Advanced Settings > Plugin Settings > Integration tab.
				 */
				case 'tooltip-plugin_title_tag':	// Webpage Title Tag.

					if ( ! current_theme_supports( 'title-tag' ) ) {

						$text .= '<strong>' . sprintf( __( 'Your theme does not support the <a href="%s">WordPress Title Tag</a>.', 'wpsso' ), __( 'https://codex.wordpress.org/Title_Tag', 'wpsso' ) ) . '</strong> ';

						$text .= __( 'Please contact your theme author and request that they add support for the WordPress Title Tag feature (available since WordPress v4.1).', 'wpsso' ) . ' ';

						$text .= '<br/><br/>';
					}

					$text .= sprintf( __( '%1$s can provide a customized value for the %2$s HTML tag.', 'wpsso' ), $this->p_name, '<code>&amp;lt;title&amp;gt;</code>' ) . ' ';

					$text .= sprintf( __( 'The %s HTML tag value is used by web browsers to display the current webpage title in the browser tab.', 'wpsso' ), '<code>&amp;lt;title&amp;gt;</code>' ) . ' ';

					break;

				case 'tooltip-plugin_filter_content':	// Use Filtered Content.

					$text .= sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions and detect additional images and/or embedded videos provided by shortcodes.', 'wpsso' ), $this->p_name ) . ' ';

					$text .= __( 'Many themes and plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ) . ' ';

					$text .= __( 'If you use shortcodes in your content text, this option should be enabled - IF YOU EXPERIENCE WEBPAGE LAYOUT OR PERFORMANCE ISSUES AFTER ENABLING THIS OPTION, determine which theme or plugin is filtering the content incorrectly and report the problem to its author(s).', 'wpsso' );

					break;

				case 'tooltip-plugin_filter_excerpt':	// Use Filtered Excerpt.

					$text = __( 'Apply the WordPress "get_the_excerpt" filter to excerpt text (default is unchecked).', 'wpsso' ) . ' ';

					$text .= __( 'You should enable this option if you use shortcodes in your excerpts.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-plugin_page_excerpt':	// Enable Excerpt for Pages.

					$text = __( 'Enable the WordPress Excerpt metabox when editing a Page.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'An excerpt is an optional hand-crafted summary of your content that %s can use as a default description for meta tags and Schema markup.', 'wpsso' ), $info[ 'short' ] );

					break;

				case 'tooltip-plugin_page_tags':	// Enable Tags for Pages.

					$text = __( 'Register a non-public Page Tags taxonomy and enable a WordPress Page Tags metabox when editing a Page.', 'wpsso' ) . ' ';

					$text .= sprintf( __( '%s can convert WordPress tags into Schema keywords and hashtags for social sites.', 'wpsso' ), $info[ 'short' ] );

					break;

				case 'tooltip-plugin_new_user_is_person':	// Add Person Role for New Users.

					$text = sprintf( __( 'Automatically add the "%s" role when a new user is created.', 'wpsso' ), _x( 'Person', 'user role', 'wpsso' ) ) . ' ';

					$text .= sprintf( __( 'You may also consider activating <a href="%s">a plugin from WordPress.org to manage user roles and their members</a>.', 'wpsso' ), 'https://wordpress.org/plugins/search/user+role/' );

					break;

				case 'tooltip-plugin_inherit_featured':	// Inherit Featured Image.

					$text = __( 'Every <strong>publicly accessible</strong> post, page, custom post type, category, tag, custom taxonomy term, and user profile should have at least one image available for its meta tags and Schema markup.', 'wpsso' ) . ' ';

					$text .= __( 'Enable this option to make sure publicly accessible child pages without a featured image can inherit the featured image of their parents.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-plugin_inherit_images':	// Inherit Custom Images.

					$text = __( 'Every <strong>publicly accessible</strong> post, page, custom post type, category, tag, custom taxonomy term, and user profile should have at least one image available for its meta tags and Schema markup.', 'wpsso' ) . ' ';

					$text .= __( 'Enable this option to make sure publicly accessible child pages and terms without custom images can inherit the custom images of their parents.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-plugin_check_img_dims':	// Image Dimension Checks.

					$text = __( 'Providing social and search sites with perfectly resized images is highly recommended, so this option should be enabled if possible.', 'wpsso' ) . ' ';

					$text .= __( 'Content authors often upload small featured images to the Media Library, without knowing that WordPress can create several different image sizes from the original, so this option is disabled by default to avoid excessive warning messages.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'See <a href="%s">Why shouldn\'t I upload small images to the media library?</a> for more information on WordPress and its image sizes.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/faqs/why-shouldnt-i-upload-small-images-to-the-media-library/' ). ' ';

					break;

				case 'tooltip-plugin_upscale_images':	// Upscale Media Library Images.

					$text = __( 'WordPress does not upscale (enlarge) images - WordPress can only create smaller images from larger full size originals.', 'wpsso' ) . ' ';

					$text .= __( 'Upscaled images do not look as sharp or clear, and if upscaled too much, will look fuzzy and unappealing - not something you want to promote on social and search sites.', 'wpsso' ) . ' ';

					$text .= sprintf( __( '%s includes an optional module to allow upscaling of WordPress Media Library images (up to a maximum upscale percentage).', 'wpsso' ), $this->p_name_pro ) . ' ';

					$text .= '<strong>' . __( 'Do not enable this option unless you want to publish lower quality images on social and search sites.', 'wpsso' ) . '</strong>';

					break;

				case 'tooltip-plugin_upscale_pct_max':	// Maximum Image Upscale Percent.

					$upscale_max = $this->p->opt->get_defaults( 'plugin_upscale_pct_max' );

					$text = sprintf( __( 'When upscaling of %1$s image sizes is allowed, %2$s can make sure smaller images are not upscaled beyond reason, which would publish very low quality / fuzzy images on social and search sites (the default maximum is %3$s%%).', 'wpsso' ), $info[ 'short' ], $this->p_name_pro, $upscale_max ) . ' ';

					$text .= __( 'If an image needs to be upscaled beyond this maximum, in either width or height, the image will not be upscaled.', 'wpsso' );

					break;

				/**
				 * SSO > Advanced Settings > Plugin Settings > Integration tab (Plugin and Theme Integration section).
				 */
				case 'tooltip-plugin_check_head':	// Check for Duplicate Meta Tags.

					$check_head_count = SucomUtil::get_const( 'WPSSO_DUPE_CHECK_HEADER_COUNT', 5 );

					$text = sprintf( __( 'When editing Posts and Pages, %1$s can check the head section of webpages for conflicting and/or duplicate HTML tags. After %2$d <em>successful</em> checks, no additional checks will be performed - until the theme and/or any plugin is updated, when another %2$d checks are performed.', 'wpsso' ), $info[ 'short' ], $check_head_count );

					break;

				case 'tooltip-plugin_product_include_vat':	// Include VAT in Product Prices.

					$text = __( 'Retrieve product prices from e-Commerce plugins with VAT included.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-plugin_import_aioseop_meta':	// Import All in One SEO Pack Metadata.

					if ( empty( $plugin_name_transl ) ) {

						$plugin_name_transl = __( 'All in One SEO Pack', 'wpsso' );
					}

					// No break.

				case 'tooltip-plugin_import_rankmath_meta':	// Import Rank Math SEO Metadata.

					if ( empty( $plugin_name_transl ) ) {

						$plugin_name_transl = __( 'Rank Math SEO', 'wpsso' );
					}

					// No break.

				case 'tooltip-plugin_import_seoframework_meta':	// Import The SEO Framework Metadata.

					if ( empty( $plugin_name_transl ) ) {

						$plugin_name_transl = __( 'The SEO Framework', 'wpsso' );
					}

					// No break.

				case 'tooltip-plugin_import_wpseo_meta':	// Import Yoast SEO Metadata.

					if ( empty( $plugin_name_transl ) ) {

						$plugin_name_transl = __( 'Yoast SEO', 'wpsso' );
					}

					$mb_title_transl = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					// translators: %1$s is the plugin name, %2$s is the Document SSO metabox name.
					$text = sprintf( __( 'Import %1$s custom metadata for posts, pages, custom post types, terms, and users into the %2$s metabox options.', 'wpsso' ), $plugin_name_transl, $mb_title_transl ) . ' ';

					// translators: %1$s is the plugin name, %2$s is the Document SSO metabox name.
					$text .= sprintf( __( 'Note that %1$s custom metadata will only be imported into %2$s metabox options that are empty (ie. options that have not already been customized).', 'wpsso' ), $plugin_name_transl, $mb_title_transl ) . ' ';

					// translators: %1$s is the plugin name.
					$text .= sprintf( __( 'This option is checked by default if %1$s is active, or no SEO plugin is active and %1$s settings are found in the database.', 'wpsso' ), $plugin_name_transl ) . ' ';

					// translators: %1$s is the plugin name.
					$text .= sprintf( __( 'If this option is checked and %1$s is active, then only social metadata will be imported, and if %1$s is not active, then all available metadata will be imported.', 'wpsso' ), $plugin_name_transl ) . ' ';

					break;

				case 'tooltip-plugin_import_wpseo_blocks':	// Import Yoast SEO Block Attrs.

					if ( empty( $plugin_name_transl ) ) {

						$plugin_name_transl = __( 'Yoast SEO', 'wpsso' );
					}

					$mb_title_transl = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					// translators: %1$s is the plugin name, %2$s is the Document SSO metabox name.
					$text = sprintf( __( 'Import the %1$s block attributes (how-to block step details, for example) into the %2$s metabox options.', 'wpsso' ), $plugin_name_transl, $mb_title_transl ) . ' ';

					// translators: %1$s is the plugin name.
					$text .= sprintf( __( 'This option is checked by default if %1$s is active, or no SEO plugin is active and %1$s settings are found in the database.', 'wpsso' ), $plugin_name_transl ) . ' ';

					break;

				/**
				 * SSO > Advanced Settings > Plugin Settings > Default Text tab.
				 */
				case 'tooltip-plugin_title_part_site':	// Title Tag Site Prefix / Suffix.

					$option_label = _x( 'Webpage Title Tag', 'option label', 'wpsso' );
					$option_link  = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', $option_label );

					// translators: %1$s is the "Webpage Title Tag" option label.
					$text = sprintf( __( 'The SEO site name suffix for the %1$s option value.', 'wpsso' ), $option_link ) . ' ';

					$option_label = _x( 'WebSite Name', 'option label', 'wpsso' );
					$option_link  = $this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_site', $option_label );

					// translators: %1$s is the "WebSite Name" option label, %2$s is the "%%sitename%%" inline variable.
					$text .= sprintf( __( 'The default SEO site name suffix is the %1$s value (aka %2$s inline variable).', 'wpsso' ),
						$option_link, '<code>%%sitename%%</code>' );

					break;

				case 'tooltip-plugin_title_part_tagline':	// Title Tag Tagline Prefix / Suffix.

					$option_label = _x( 'Webpage Title Tag', 'option label', 'wpsso' );
					$option_link  = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', $option_label );

					// translators: %1$s is the "Webpage Title Tag" option label.
					$text = sprintf( __( 'The SEO tagline suffix for the %1$s option value.', 'wpsso' ), $option_link ) . ' ';

					$option_label = _x( 'WebSite Description', 'option label', 'wpsso' );
					$option_link  = $this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_site', $option_label );

					// translators: %1$s is the "WebSite Description" option label, %2$s is the "%%sitedesc%%" inline variable.
					$text .= sprintf( __( 'The default SEO tagline suffix is the %1$s value (aka %2$s inline variable).', 'wpsso' ),
						$option_link, '<code>%%sitedesc%%</code>' );

					break;

				case 'tooltip-plugin_img_alt_prefix':	// Content Image Alt Prefix.

					$text = __( 'Image <code>alt</code> text used in descriptions can be prefixed with an optional string.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_img_alt_prefix', $use_opts = false ) );

					$text .= __( 'Leave this option blank to prevent the image <code>alt</code> text from being prefixed.', 'wpsso' );

					break;

				case 'tooltip-plugin_p_cap_prefix':	// WP Caption Text Prefix.

					$text = __( 'Caption paragraphs found with the "wp-caption-text" CSS class can be prefixed with an optional string.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_p_cap_prefix', $use_opts = false ) );

					$text .= __( 'Leave this option blank to prevent caption paragraphs from being prefixed.', 'wpsso' );

					break;

				case 'tooltip-plugin_comment_title':	// Comment Title.

					$text = __( 'The comment title used for Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_comment_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_comment_reply_title':	// Reply Comment Title.

					$text = __( 'The comment title used for Schema markup when the comment has a parent.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_comment_reply_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_comment_review_title':	// Review Comment Title.

					$text = __( 'The comment title used for Schema markup when the comment has a rating.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_comment_review_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_product_var_title':	// Product Variation Title.

					$text = __( 'The product variation title used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_product_var_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_404_page_title':	// 404 Page Title.

					$text = __( 'The 404 page title used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_404_page_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_404_page_desc':	// 404 Page Description.

					$text = __( 'The 404 page description used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_404_page_desc', $use_opts = false ) );

					break;

				case 'tooltip-plugin_no_title_text':	// No Title Text.

					$text = __( 'A fallback title to use when there is no title text available.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_no_title_text', $use_opts = false ) );

					break;

				case 'tooltip-plugin_no_desc_text':	// No Description Text.

					$text = __( 'A fallback description to use when there is no description text available.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_no_desc_text', $use_opts = false ) );

					break;

				/**
				 * SSO > Advanced Settings > Plugin Settings > Default Text tab (Archive Pages section).
				 */
				case 'tooltip-plugin_term_page_title':	// Term Archive Title.

					$text = __( 'The term (categories, tags, and custom taxonomy terms) title used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_term_page_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_term_page_desc':	// Term Archive Description.

					$text = __( 'A fallback term (categories, tags, and custom taxonomy terms) description used for meta tags and Schema markup when the term description is empty.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_term_page_desc', $use_opts = false ) );

					break;

				case 'tooltip-plugin_author_page_title':	// Author Archive Title.

					$text = __( 'The author title used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_author_page_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_author_page_desc':	// Author Archive Description.

					$text = __( 'A fallback author description used for meta tags and Schema markup when the author description is empty.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_author_page_desc', $use_opts = false ) );

					break;

				case 'tooltip-plugin_search_page_title':	// Search Results Title.

					$text = __( 'The search results page title used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_search_page_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_search_page_desc':	// Search Results Description.

					$text = __( 'The search results page description used for meta tags and Schema markup.' ) .  ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_search_page_desc', $use_opts = false ) );

					break;

				case 'tooltip-plugin_year_page_title':	// Year Archive Title.

					$text = __( 'The yearly archive page title used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_year_page_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_year_page_desc':	// Year Archive Description.

					$text = __( 'The yearly archive page description used for meta tags and Schema markup.' ) .  ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_year_page_desc', $use_opts = false ) );

					break;

				case 'tooltip-plugin_month_page_title':	// Month Archive Title.

					$text = __( 'The monthly archive page title used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_month_page_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_month_page_desc':	// Month Archive Description.

					$text = __( 'The monthly archive page description used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_month_page_desc', $use_opts = false ) );

					break;

				case 'tooltip-plugin_day_page_title':	// Day Archive Title.

					$text = __( 'The daily archive page title used for meta tags and Schema markup.' ) . ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_day_page_title', $use_opts = false ) );

					break;

				case 'tooltip-plugin_day_page_desc':	// Day Archive Description.

					$text = __( 'The daily archive page description used for meta tags and Schema markup.' ) .  ' ';

					$text .= sprintf( __( 'The default value is "<em>%s</em>".', 'wpsso' ),
						$this->p->opt->get_text( 'plugin_day_page_desc', $use_opts = false ) );

					break;

				/**
				 * SSO > Advanced Settings > Plugin Settings > Interface tab.
				 */
				case 'tooltip-plugin_show_opts': 		// Options to Show by Default.

					$mb_title_transl = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					$text = sprintf( __( 'You can select the default set of options to display in settings pages and the %s metabox.', 'wpsso' ), $mb_title_transl ) . ' ';

					$text .= __( 'The basic view shows the most commonly used options, and includes a link to temporarily show all options when desired.', 'wpsso' ) . ' ';

					$text .= __( 'Note that showing all options by default could be a bit overwhelming for new users.', 'wpsso' );

					break;

				case 'tooltip-plugin_add_toolbar_validate':	// Show Validators Toolbar Menu.

					$menu_title = _x( 'Validators', 'toolbar menu title', 'wpsso' );

					$text = sprintf( __( 'Show a "%s" menu in the admin toolbar.', 'wpsso' ), $menu_title ) . ' ';

					break;

				case 'tooltip-plugin_add_submenu':	// Show SSO Menu Items.

					$menu_title = $this->p->admin->get_menu_title();

					$text = sprintf( __( 'Optionally remove settings pages from the %s menu.', 'wpsso' ), $menu_title );

					break;

				case 'tooltip-plugin_add_to':		// Show Document SSO Metabox.

					$menu_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					$text = sprintf( __( 'Add or remove the %s metabox from editing pages for posts, pages, custom post types, terms (categories and tags), and user profile pages.', 'wpsso' ), $menu_title );

					break;

				case 'tooltip-plugin_show_columns':	// WP List Table Columns.

					$text = __( 'Additional columns can be included by default in the admin list tables for posts, pages, custom post types, terms (categories and tags), and user profile pages.', 'wpsso' ) . ' ';

					$text .= __( 'Users can also hide columns by using the <em>Screen Options</em> tab when viewing admin list tables.', 'wpsso' );

					break;

				case 'tooltip-plugin_og_types_select_format':	// Open Graph Type Select Format.

					$text = __( 'Select a preferred format for the Open Graph Type drop-down options.', 'wpsso' );

					break;

				case 'tooltip-plugin_schema_types_select_format':	// Schema Type Select Format.

					$text = __( 'Select a preferred format for the Schema Type drop-down options.', 'wpsso' );

					break;


				/**
				 * SSO > Advanced Settings > Service APIs > Media Services tab.
				 */
				case 'tooltip-plugin_gravatar_api':	// Gravatar is Default Author Image.

					$mb_title_transl = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					$text .= sprintf( __( 'A customized image for each author can be selected in the WordPress user profile %s metabox.', 'wpsso' ), $mb_title_transl ) . ' ';

					$text = __( 'If a custom image has not been selected, fallback to using their Gravatar image.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-plugin_gravatar_size':	// Gravatar Image Size.

					$def_value = $this->p->opt->get_defaults( 'plugin_gravatar_size' );

					$text = __( 'The requested Gravatar image width and height (a number from 1 to 2048).', 'wpsso' ) . ' ';

					$text .= __( 'Note that users often upload low resolution images to Gravatar, so choosing a larger image size may result in pixelation and lower-quality images.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The default Gravatar image size is %d pixels.', 'wpsso' ), $def_value );

					break;

				case 'tooltip-plugin_embed_media_apis':	// Check for Embedded Media.

					$text = __( 'Check the content for embedded media URLs from supported media providers (Vimeo, Wistia, YouTube, etc.). If a supported media URL is found, an API connection to the provider will be made to retrieve information about the media (preview image URL, flash player URL, oembed player URL, the video width / height, etc.).', 'wpsso' );

					break;

				/**
				 * SSO > Advanced Settings > Service APIs > Shortening Services tab.
				 */
				case 'tooltip-plugin_shortener':	// URL Shortening Service.

					$text = sprintf( __( 'A preferred URL shortening service used for the <code>%s</code> HTML tag value, Schema markup, and social sharing buttons.', 'wpsso' ), 'link rel shortlink' );

					break;

				case 'tooltip-plugin_min_shorten':	// Minimum URL Length to Shorten.

					$def_value = $this->p->opt->get_defaults( 'plugin_min_shorten' );

					$text = sprintf( __( 'Shorten URLs longer than this length (the default suggested by Twitter is %d characters).', 'wpsso' ), $def_value );

					break;

				case 'tooltip-plugin_clear_short_urls':		// Clear Short URLs on Clear Cache.

					$def_checked     = $this->get_def_checked( 'plugin_clear_short_urls' );
					$cache_exp_secs  = $this->p->util->get_cache_exp_secs( $cache_md5_pre = 'wpsso_s_' );
					$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : _x( 'disabled', 'tooltip fragment', 'wpsso' );

					$text = sprintf( __( 'Clear shortened URLs when clearing the %1$s transient cache (default is %2$s).', 'wpsso' ), $info[ 'short' ], $def_checked ) . ' ';

					$text .= sprintf( __( 'Shortened URLs are cached for %1$s seconds (%2$s) to minimize external service API calls.', 'wpsso' ), $cache_exp_secs, $cache_exp_human ) . ' ';

					$text .= '<strong>' . __( 'Note that clearing and then re-updating all shortened URLs at once may exceed API limits imposed by your shortening service provider.', 'wpsso' ) . '</strong>';

					break;

				case 'tooltip-plugin_wp_shortlink':	// Use Short URL for WP Shortlink.

					$text = sprintf( __( 'Use the selected URL shortening service to replace the WordPress <code>%s</code> function value.', 'wpsso' ), 'wp_get_shortlink()' );

					break;

				case 'tooltip-plugin_add_link_rel_shortlink':	// Add "link rel shortlink" HTML Tag.

					$text = sprintf( __( 'Use the selected URL shortening service to replace the WordPress <code>%s</code> HTML tag.', 'wpsso' ), 'link rel shortlink' );

					break;

				case 'tooltip-plugin_bitly_access_token':	// Bitly Generic Access Token.

					$text = __( 'The Bitly shortening service requires a Generic Access Token to shorten URLs.', 'wpsso' ) . ' ';

					$text .= sprintf( __( '<a href="%s">You can create a Generic Access Token in your Bitly profile settings</a> and enter its value here.', 'wpsso' ), 'https://bitly.com/a/oauth_apps' );

					break;

				case 'tooltip-plugin_bitly_domain':	// Bitly Short Domain (Optional).

					$text = __( 'An optional Bitly short domain to use - either bit.ly, j.mp, bitly.com, or another custom short domain.', 'wpsso' ) . ' ';

					$text .= __( 'If no value is entered here, the short domain selected in your Bitly account settings will be used.', 'wpsso' );

					break;

				case 'tooltip-plugin_bitly_group_name':	// Bitly Group Name (Optional).

					$text = sprintf( __( 'An optional <a href="%s">Bitly group name to organize your Bitly account links</a>.', 'wpsso' ),
						'https://support.bitly.com/hc/en-us/articles/115004551268' );

					break;

				case 'tooltip-plugin_dlmyapp_api_key':	// DLMY.App API Key.

					$text = __( 'The DLMY.App secret API Key can be found in the DLMY.App user account &gt; Tools &gt; Developer API webpage.', 'wpsso' );

					break;

				case 'tooltip-plugin_owly_api_key':	// Ow.ly API Key.

					$text = sprintf( __( 'To use Ow.ly as your preferred shortening service, you must provide the Ow.ly API Key for this website (complete this form to <a href="%s">Request Ow.ly API Access</a>).', 'wpsso' ), 'https://docs.google.com/forms/d/1Fn8E-XlJvZwlN4uSRNrAIWaY-nN_QA3xAHUJ7aEF7NU/viewform' );

					break;

				case 'tooltip-plugin_yourls_api_url':	// YOURLS API URL.

					$text = sprintf( __( 'The URL to <a href="%s">Your Own URL Shortener</a> (YOURLS) shortening service.', 'wpsso' ), 'https://yourls.org/' );
					break;

				case 'tooltip-plugin_yourls_username':	// YOURLS Username.

					$text = sprintf( __( 'If <a href="%s">Your Own URL Shortener</a> (YOURLS) shortening service is private, enter a configured username (see YOURLS Token for an alternative to the username / password options).', 'wpsso' ), 'https://yourls.org/' );

					break;

				case 'tooltip-plugin_yourls_password':	// YOURLS Password.

					$text = sprintf( __( 'If <a href="%s">Your Own URL Shortener</a> (YOURLS) shortening service is private, enter a configured user password (see YOURLS Token for an alternative to the username / password options).', 'wpsso' ), 'https://yourls.org/' );

					break;

				case 'tooltip-plugin_yourls_token':	// YOURLS Token.

					$text = sprintf( __( 'If <a href="%s">Your Own URL Shortener</a> (YOURLS) shortening service is private, you can use a token string for authentication instead of a username / password combination.', 'wpsso' ), 'https://yourls.org/' );

					break;

				/**
				 * SSO > Advanced Settings > Service APIs > Ratings and Reviews tab.
				 */
				case 'tooltip-plugin_ratings_reviews_svc':	// Ratings and Reviews Service.

					$text = sprintf( __( 'An external service API used to retrieve ratings and reviews for meta tags and Schema markup.', 'wpsso' ), $info[ 'short' ] );

					break;

				case 'tooltip-plugin_ratings_reviews_num_max':	// Maximum Number of Reviews.

					$text = __( 'The maximum number of reviews retrieved from the service API.', 'wpsso' );

					break;

				case 'tooltip-plugin_ratings_reviews_age_max':	// Maximum Age of Reviews.

					$text = __( 'The maximum age of reviews retrieved from the service API.', 'wpsso' );

					break;

				case 'tooltip-plugin_ratings_reviews_for':	// Get Reviews for Post Types.

					$text = __( 'Get ratings and reviews for the selected post types from the service API.', 'wpsso' );

					break;

				case 'tooltip-plugin_shopperapproved_site_id':	// Shopper Approved Site ID.
				case 'tooltip-plugin_shopperapproved_token':	// Shopper Approved API Token.

					$text = __( 'Your unique Shopper Approved site ID and API token values are required to retrieve ratings and reviews from the Shopper Approved service API.', 'wpsso' ) . ' ';

					$text .= sprintf( __( '<a href="%s">Login to your Shopper Approved account and go to the API Dashboard</a>, then scroll down to find your Site ID and API Token.', 'wpsso' ), 'https://www.shopperapproved.com/account/setup/api/merchant-api' );

					break;

				case 'tooltip-plugin_stamped_store_hash':	// Stamped Store Hash.
				case 'tooltip-plugin_stamped_key_public':	// Stamped API Key Public.

					$text = __( 'Your unique Stamped.io store hash and API public key values are required to retrieve ratings and reviews from the Stamped.io service API.', 'wpsso' ) . ' ';

					break;

				/**
				 * SSO > Advanced Settings > Attributes and Metadata > Product Attributes tab.
				 */
				case ( 0 === strpos( $msg_key, 'tooltip-plugin_attr_product_' ) ? true : false ):

					$tp_frags    = $this->get_tooltip_fragments( preg_replace( '/^tooltip-plugin_attr_/', '', $msg_key ) );	// Uses a local cache.
					$attr_key    = str_replace( 'tooltip-', '', $msg_key );
					$attr_md_key = WpssoConfig::get_attr_md_index( $attr_key );
					$is_multi    = $attr_md_key ? WpssoConfig::get_md_keys_multi( $attr_md_key ) : false;	// Uses a local cache.
					$def_attr    = $this->p->opt->get_defaults( $attr_key );

					if ( ! empty( $tp_frags ) ) {	// Just in case.
	
						$text = sprintf( __( 'The product attribute name allows %s to request a product attribute value from an e-commerce plugin.',
							'wpsso' ), $this->p_name ) . ' ';

						if ( ! empty( $def_attr ) ) {
	
							$text .= sprintf( __( 'The default attribute name is "%s".', 'wpsso' ), $def_attr ) . ' ';
						}
	
						if ( ! empty( $tp_frags[ 'about' ] ) ) {
	
							// translators: %1$s is a webpage URL and %2$s is a singular item reference, for example 'a product Google category'.
							$text .= sprintf( __( '<a href="%1$s">See this webpage for more information about choosing %2$s value</a>.',
								'wpsso' ), $tp_frags[ 'about' ], $tp_frags[ 'desc' ] ) . ' ';
						}
	
						if ( ! empty( $tp_frags[ 'values' ] ) ) {
	
							$text .= sprintf( __( 'The product attribute value can be an empty string or one of these values (case sensitive): %s',
								'wpsso' ), SucomUtil::array_to_list_html( $tp_frags[ 'values' ] ) ) . ' ';
						}

						if ( ! empty( $is_multi ) ) {

							$text .= sprintf( __( 'Note that the "%s" option supports multiple values.', 'wpsso' ), $tp_frags[ 'label' ] ) . ' ';

							$text .= __( 'The product attribute string will be split using the comma "," character.', 'wpsso' ) . ' ';
						}
					}

					break;

				/**
				 * SSO > Advanced Settings > Attributes and Metadata > Custom Fields tab.
				 */
				case ( 0 === strpos( $msg_key, 'tooltip-plugin_cf_' ) ? true : false ):

					$tp_frags        = $this->get_tooltip_fragments( preg_replace( '/^tooltip-plugin_cf_/', '', $msg_key ) );	// Uses a local cache.
					$cf_key          = str_replace( 'tooltip-', '', $msg_key );
					$cf_md_key       = WpssoConfig::get_cf_md_index( $cf_key );
					$is_multi        = $cf_md_key ? WpssoConfig::get_md_keys_multi( $cf_md_key ) : false;	// Uses a local cache.
					$mb_title_transl = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					if ( ! empty( $tp_frags ) ) {	// Just in case.

						$text = sprintf( __( 'If your theme or another plugin provides a custom field (aka metadata) for %s, you may enter its custom field name here.', 'wpsso' ), $tp_frags[ 'desc' ] ) . ' ';

						// translators: %1$s is the metabox name, %2$s is the option name.
						$text .= sprintf( __( 'If a custom field matching this name is found, its value will be imported for the %1$s "%2$s" option.',
							'wpsso' ), $mb_title_transl, $tp_frags[ 'label' ] ) . ' ';

						if ( ! empty( $tp_frags[ 'about' ] ) ) {

							// translators: %1$s is a webpage URL and %2$s is a singular item reference, for example 'a product Google category'.
							$text .= sprintf( __( '<a href="%1$s">See this webpage for more information about choosing %2$s value</a>.',
								'wpsso' ), $tp_frags[ 'about' ], $tp_frags[ 'desc' ] ) . ' ';
						}

						if ( ! empty( $tp_frags[ 'values' ] ) ) {

							$text .= sprintf( __( 'The custom field value can be an empty string or one of these values (case sensitive): %s',
								'wpsso' ), SucomUtil::array_to_list_html( $tp_frags[ 'values' ] ) ) . ' ';
						}

						if ( ! empty( $is_multi ) ) {

							$text .= sprintf( __( 'Note that the "%s" option supports multiple values.', 'wpsso' ), $tp_frags[ 'label' ] ) . ' ';

							$text .= __( 'If the custom field value is a string, it will be split on the end of line character.', 'wpsso' ) . ' ';
						}
					}

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_plugin', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-plugin' switch.

			return $text;
		}
	}
}
