<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
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

			$text = '';

			switch ( $msg_key ) {

				/**
				 * Plugin Admin settings.
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
				 * Interface settings.
				 */
				case 'tooltip-plugin_show_opts': 		// Options to Show by Default.

					$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					$text = sprintf( __( 'You can select the default set of options to display in settings pages and the %s metabox.', 'wpsso' ), $mb_title ) . ' ';

					$text .= __( 'The basic view shows the most commonly used options, and includes a link to temporarily show all options when desired.', 'wpsso' ) . ' ';

					$text .= __( 'Note that showing all options by default could be a bit overwhelming for new users.', 'wpsso' );

					break;

				case 'tooltip-plugin_show_validate_toolbar':	// Show Validators Toolbar Menu.

					$menu_title = _x( 'Validators', 'toolbar menu title', 'wpsso' );

					$text = sprintf( __( 'Show a "%s" menu in the admin toolbar.', 'wpsso' ), $menu_title ) . ' ';

					$text .= __( 'Please note that the Twitter Card validator does not (currently) accept query arguments, so it cannot be included in this menu.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-plugin_add_to':		// Show Document SSO Metabox.

					$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					$text = sprintf( __( 'Add or remove the %s metabox from admin editing pages for posts, pages, custom post types, terms (categories and tags), and user profile pages.', 'wpsso' ), $mb_title );

					break;

				case 'tooltip-plugin_show_columns':	// Additional List Table Columns.

					$text = __( 'Additional columns can be included in admin list tables for posts, pages, etc.', 'wpsso' ) . ' ';

					$text .= __( 'Users can also hide enabled columns by using the <em>Screen Options</em> tab on these admin list table pages.', 'wpsso' );

					break;

				/**
				 * Integration settings.
				 */
				case 'tooltip-plugin_document_title':	// Webpage Document Title.

					if ( ! current_theme_supports( 'title-tag' ) ) {

						$text .= '<strong>' . sprintf( __( 'Your theme does not support the <a href="%s">WordPress Title Tag</a>.', 'wpsso' ), __( 'https://codex.wordpress.org/Title_Tag', 'wpsso' ) ) . '</strong> ';

						$text .= __( 'Please contact your theme author and request that they add support for the WordPress Title Tag feature (available since WordPress v4.1).', 'wpsso' ) . ' ';

						$text .= '<br/><br/>';
					}

					$text .= sprintf( __( '%1$s can provide a customized value for the %2$s HTML tag.', 'wpsso' ), $this->p_name, '<code>&amp;lt;title&amp;gt;</code>' ) . ' ';

					$text .= sprintf( __( 'The %s HTML tag value is used by web browsers to display the current webpage title in the browser tab.', 'wpsso' ), '<code>&amp;lt;title&amp;gt;</code>' ) . ' ';

					break;

				case 'tooltip-plugin_filter_title':	// Use Filtered "SEO" Title.

					$def_checked = $this->get_def_checked( 'plugin_filter_title' );

					$text = sprintf( __( 'The title value provided by WordPress to %s may include modifications from themes and/or other SEO plugins (appending the site name or expanding inline variables, for example, is a common practice).', 'wpsso' ), $this->p_name ) . ' ';

					$text .= sprintf( __( 'Uncheck this option to always use the original unmodified title value from WordPress (default is %1$s) or enable this option to allow themes and plugins to modify the title value provided to %2$s.', 'wpsso' ), $def_checked, $this->p_name ) . ' ';

					break;

				case 'tooltip-plugin_filter_content':	// Use Filtered Content.

					$text .= sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions and detect additional images and/or embedded videos provided by shortcodes.', 'wpsso' ), $this->p_name ) . ' ';

					$text .= __( 'Many themes and plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ) . ' ';

					$text .= __( 'If you use shortcodes in your content text, this option should be enabled - IF YOU EXPERIENCE WEBPAGE LAYOUT OR PERFORMANCE ISSUES AFTER ENABLING THIS OPTION, determine which theme or plugin is filtering the content incorrectly and report the problem to its author(s).', 'wpsso' );

					break;

				case 'tooltip-plugin_filter_excerpt':	// Use Filtered Excerpt.

					$text = __( 'Apply the WordPress "get_the_excerpt" filter to the excerpt text (default is unchecked). Enable this option if you use shortcodes in your excerpts, for example.', 'wpsso' ) . ' ';

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

				case 'tooltip-plugin_clear_post_terms':		// Clear Term Cache when Publishing.

					$def_checked = $this->get_def_checked( 'plugin_clear_post_terms' );

					$text = sprintf( __( 'When a published post, page, or custom post type is updated, automatically clear the cache of its selected terms (default is %s).', 'wpsso' ), $def_checked );

					break;

				case 'tooltip-plugin_clear_for_comment':	// Clear Post Cache for New Comment.

					$def_checked = $this->get_def_checked( 'plugin_clear_for_comment' );

					$text = sprintf( __( 'Automatically clear the post cache when a new comment is added, or the status of an existing comment is changed (default is %s).', 'wpsso' ), $def_checked );

					break;

				case 'tooltip-plugin_check_img_dims':	// Enforce Image Dimension Checks.

					$image_sizes_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_image_sizes',
						_x( 'Image Sizes', 'lib file description', 'wpsso' ) );

					$text = __( 'Content authors often upload small featured images, without knowing that WordPress creates resized images based on predefined image sizes, so this option is disabled by default.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'See <a href="%s">Why shouldn\'t I upload small images to the media library?</a> for more information on WordPress image sizes.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/faqs/why-shouldnt-i-upload-small-images-to-the-media-library/' ). ' ';

					$text .= sprintf( __( 'When this option is enabled, full size images used for meta tags and Schema markup must be equal to (or larger) than the image dimensions you\'ve selected in the %s settings page - images that do not meet or exceed the minimum requirements are ignored.', 'wpsso' ), $image_sizes_tab_link ) . ' ';

					$text .= '<strong>' . __( 'Providing social and search sites with perfectly resized images is highly recommended, so this option should be enabled if possible.', 'wpsso' ) . '</strong> ';

					break;

				case 'tooltip-plugin_upscale_images':	// Upscale Media Library Images.

					$text = __( 'WordPress does not upscale (enlarge) images - WordPress can only create smaller images from larger full size originals.', 'wpsso' ) . ' ';

					$text .= __( 'Upscaled images do not look as sharp or clear, and if upscaled too much, will look fuzzy and unappealing - not something you want to promote on social and search sites.', 'wpsso' ) . ' ';

					$text .= sprintf( __( '%s includes an optional module to allow upscaling of WordPress Media Library images (up to a maximum upscale percentage).', 'wpsso' ), $this->p_name_pro ) . ' ';

					$text .= '<strong>' . __( 'Do not enable this option unless you want to publish lower quality images on social and search sites.', 'wpsso' ) . '</strong>';

					break;

				case 'tooltip-plugin_upscale_img_max':	// Maximum Image Upscale Percent.

					$upscale_max = $this->p->opt->get_defaults( 'plugin_upscale_img_max' );

					$text = sprintf( __( 'When upscaling of %1$s image sizes is allowed, %2$s can make sure smaller images are not upscaled beyond reason, which would publish very low quality / fuzzy images on social and search sites (the default maximum is %3$s%%).', 'wpsso' ), $info[ 'short' ], $this->p_name_pro, $upscale_max ) . ' ';

					$text .= __( 'If an image needs to be upscaled beyond this maximum, in either width or height, the image will not be upscaled.', 'wpsso' );

					break;

				case 'tooltip-plugin_img_alt_prefix':	// Content Image Alt Prefix.

					$text = sprintf( __( 'When the text from image %1$s attributes is used, %2$s can prefix the attribute text with an optional string (for example, "Image:").', 'wpsso' ), '<em>alt</em>', $info[ 'short' ] ) . ' ';

					$text .= sprintf( __( 'Leave this option blank to prevent the text from image %s attributes from being prefixed.', 'wpsso' ), '<em>alt</em>' );

					break;

				case 'tooltip-plugin_p_cap_prefix':	// WP Caption Text Prefix.

					$text = sprintf( __( '%1$s can prefix caption paragraphs found with the "%2$s" class (for example, "Caption:").', 'wpsso' ), $info[ 'short' ], 'wp-caption-text' ) . ' ';

					$text .= __( 'Leave this option blank to prevent caption paragraphs from being prefixed.', 'wpsso' );

					break;

				case 'tooltip-plugin_no_title_text':	// No Title Text.

					$text = __( 'A fallback string to use when there is no title text available (for example, "No Title").' );

					break;

				case 'tooltip-plugin_no_desc_text':	// No Description Text.

					$text = __( 'A fallback string to use when there is no description text available (for example, "No Description.").' );

					break;

				/**
				 * Integration settings (Plugin and Theme Integration).
				 */
				case 'tooltip-plugin_check_head':	// Check for Duplicate Meta Tags.

					$check_head_count = SucomUtil::get_const( 'WPSSO_DUPE_CHECK_HEADER_COUNT', 5 );

					$text = sprintf( __( 'When editing Posts and Pages, %1$s can check the head section of webpages for conflicting and/or duplicate HTML tags. After %2$d <em>successful</em> checks, no additional checks will be performed - until the theme and/or any plugin is updated, when another %2$d checks are performed.', 'wpsso' ), $info[ 'short' ], $check_head_count );

					break;

				case 'tooltip-plugin_product_include_vat':	// Include VAT in Product Prices.

					$text = __( 'Retrieve product prices from e-Commerce plugins with VAT included.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-plugin_wpseo_social_meta':	// Import Yoast SEO Social Meta.

					$text = __( 'Import the Yoast SEO custom social meta text for Posts, Terms, and Users.', 'wpsso' ) . ' ';

					$text .= __( 'This option is checked by default if the Yoast SEO plugin is active, or if no SEO plugin is active and Yoast SEO settings are found in the database.', 'wpsso' );

					break;

				case 'tooltip-plugin_wpseo_show_import':	// Show Yoast SEO Import Details.

					$text = __( 'Show notification messages for imported Yoast SEO custom social meta text for Posts, Terms, and Users.', 'wpsso' ) . ' ';

					break;

				/**
				 * Media Services settings.
				 */
				case 'tooltip-plugin_gravatar_api':	// Gravatar is Default Author Image.

					$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					$text .= sprintf( __( 'A customized image for each author can be selected in the WordPress user profile %s metabox.', 'wpsso' ), $mb_title ) . ' ';

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
				 * Shortening Services settings.
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
					$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : _x( 'disabled', 'option comment', 'wpsso' );

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
				 * Ratings and Reviews settings.
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
				 * Product Attributes settings.
				 */
				case ( 0 === strpos( $msg_key, 'tooltip-plugin_attr_product_' ) ? true : false ):

					$attr_key = str_replace( 'tooltip-', '', $msg_key );

					$text = __( 'Enter the name of a product attribute available in your e-commerce plugin.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The product attribute name allows %s to request the attribute value from your e-commerce plugin.', 'wpsso' ), $this->p_name_pro ) . ' ';

					$text .= sprintf( __( 'The default attribute name is "%s".', 'wpsso' ), $this->p->opt->get_defaults( $attr_key ) );

					break;

				/**
				 * Custom Fields settings
				 */
				case ( 0 === strpos( $msg_key, 'tooltip-plugin_cf_' ) ? true : false ):

					$cf_key      = str_replace( 'tooltip-', '', $msg_key );
					$cf_frags    = $this->get_cf_tooltip_fragments( preg_replace( '/^tooltip-plugin_cf_/', '', $msg_key ) );
					$cf_md_index = $this->p->cf[ 'opt' ][ 'cf_md_index' ];
					$cf_md_key   = empty( $cf_md_index[ $cf_key ] ) ? '' : $cf_md_index[ $cf_key ];
					$cf_is_multi = empty( $this->p->cf[ 'opt' ][ 'cf_md_multi' ][ $cf_md_key ] ) ? false : true;
					$mb_title    = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

					if ( ! empty( $cf_frags ) ) {	// Just in case.

						$text = sprintf( __( 'If your theme or another plugin provides a custom field (aka metadata) for %s, you may enter its custom field name here.', 'wpsso' ), $cf_frags[ 'desc' ] ) . ' ';

						// translators: %1$s is the metabox name, %2$s is the option name.
						$text .= sprintf( __( 'If a custom field matching this name is found, its value will be imported for the %1$s "%2$s" option.', 'wpsso' ), $mb_title, $cf_frags[ 'label' ] ) . ' ';

						if ( $cf_is_multi ) {

							$text .= '</br></br>';

							$text .= sprintf( __( 'Note that the "%s" option provides multiple input fields - the custom field value will be split on newline characters, and each line will be assigned to an individual input field.', 'wpsso' ), $cf_frags[ 'label' ] );
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
