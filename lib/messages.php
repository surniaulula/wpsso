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

if ( ! class_exists( 'WpssoMessages' ) ) {

	class WpssoMessages {

		protected $p;	// Wpsso class object.

		protected $pkg_info   = array();
		protected $p_name     = '';
		protected $p_name_pro = '';
		protected $dist_pro   = '';
		protected $dist_std   = '';
		protected $fb_prefs   = '';

		private $tooltip = null;	// WpssoMessagesTooltip class object.

		/**
		 * Instantiated by Wpsso->set_objects() when is_admin() is true.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		/**
		 * Define and translate certain strings only once. 
		 */
		public function maybe_set_properties() {

			static $do_once = null;

			if ( null === $do_once ) {

				$this->pkg_info   = $this->p->admin->get_pkg_info();	// Returns an array from cache.
				$this->p_name     = $this->pkg_info[ $this->p->id ][ 'name' ];
				$this->p_name_pro = $this->pkg_info[ $this->p->id ][ 'name_pro' ];
				$this->dist_pro   = _x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' );
				$this->dist_std   = _x( $this->p->cf[ 'dist' ][ 'std' ], 'distribution name', 'wpsso' );
				$this->fb_prefs   = __( 'Facebook prefers images of 1200x630px cropped (for Retina and high-PPI displays), 600x315px cropped as a recommended minimum, and ignores images smaller than 200x200px.', 'wpsso' );
			}
		}

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$msg_key = sanitize_title_with_dashes( $msg_key );

			/**
			 * Set a default text string, if one is provided.
			 */
			$text = '';

			if ( is_string( $info ) ) {

				$text = $info;

				$info = array( 'text' => $text );

			} elseif ( isset( $info[ 'text' ] ) ) {

				$text = $info[ 'text' ];
			}

			/**
			 * Set a lowercase acronym.
			 *
			 * Example plugin IDs: wpsso, wpssojson, wpssoum, etc.
			 */
			$info[ 'plugin_id' ] = $plugin_id = isset( $info[ 'plugin_id' ] ) ? $info[ 'plugin_id' ] : $this->p->id;

			/**
			 * Get the array of plugin URLs (download, purchase, etc.).
			 */
			$url = isset( $this->p->cf[ 'plugin' ][ $plugin_id ][ 'url' ] ) ? $this->p->cf[ 'plugin' ][ $plugin_id ][ 'url' ] : array();

			/**
			 * Make sure specific plugin information is available, like 'short', 'short_pro', etc.
			 */
			foreach ( array( 'short', 'name', 'version' ) as $info_key ) {

				if ( ! isset( $info[ $info_key ] ) ) {

					if ( ! isset( $this->p->cf[ 'plugin' ][ $plugin_id ][ $info_key ] ) ) {	// Just in case.

						$info[ $info_key ] = null;

						continue;
					}

					$info[ $info_key ] = $this->p->cf[ 'plugin' ][ $plugin_id ][ $info_key ];
				}

				if ( 'name' === $info_key ) {

					$info[ $info_key ] = _x( $info[ $info_key ], 'plugin name', 'wpsso' );
				}

				if ( 'version' !== $info_key ) {

					if ( ! isset( $info[ $info_key . '_pro' ] ) ) {

						$info[ $info_key . '_pro' ] = SucomUtil::get_dist_name( $info[ $info_key ], $this->dist_pro );
					}
				}
			}

			/**
			 * All tooltips.
			 */
			if ( 0 === strpos( $msg_key, 'tooltip-' ) ) {

				/**
				 * Instantiate WpssoMessagesTooltip only when needed.
				 */
				if ( null === $this->tooltip ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip.php';

					$this->tooltip = new WpssoMessagesTooltip( $this->p );
				}

				$text = $this->tooltip->get( $msg_key, $info );

			/**
			 * Informational messages.
			 */
			} elseif ( 0 === strpos( $msg_key, 'info-' ) ) {

				if ( 0 === strpos( $msg_key, 'info-meta-' ) ) {

					switch ( $msg_key ) {

						/**
						 * Validate tab.
						 */
						case 'info-meta-validate-amp':

							$text = '<p class="top">';

							$text .= __( 'Validate the HTML syntax and conformance of the AMP (aka Accelerated Mobile Pages) webpage.', 'wpsso' ) . ' ';

							if ( ! function_exists( 'amp_get_permalink' ) ) {

								$text .= __( 'Note that an AMP plugin is required to create AMP webpages for WordPress.', 'wpsso' );
							}

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-facebook-debugger':

							$text = '<p class="top">';

							$text .= __( 'All social sites (except for LinkedIn) read Open Graph meta tags.', 'wpsso' ) . ' ';

							$text .= __( 'The Facebook debugger allows you to validate Open Graph meta tags and refresh Facebook\'s cache.', 'wpsso' ) . ' ';

							$text .= __( 'The Facebook debugger is the most reliable validation tool for Open Graph meta tags.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-facebook-microdata':

							$text = '<p class="top">';

							$text .= __( 'The Facebook catalog microdata debug tool allows you to validate the structured data used to indicate key information about the items on your website, such as their name, description and prices.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-google-page-speed':

							$text = '<p class="top">';

							$text .= __( 'Analyzes the webpage content and suggests ways to make the webpage faster for better ranking in search results.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-google-rich-results':

							$text = '<p class="top">';

							$text .= sprintf( __( 'Check the webpage structured data markup for <a href="%s">Google Rich Result types</a> (Job posting, Product, Recipe, etc.).', 'wpsso' ), __( 'https://developers.google.com/search/docs/guides/search-gallery', 'wpsso' ) ) . ' ';

							$text .= __( 'To test and validate Schema markup beyond the limited subset of Google Rich Result types, use the Schema Markup Validator.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-linkedin':

							$text = '<p class="top">';

							$text .= __( 'Refresh LinkedIn\'s cache and validate the webpage oEmbed data.', 'wpsso' ) . ' ';

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-pinterest':

							$text = '<p class="top">';

							$text .= __( 'Validate Rich Pin markup and submit a request to show Rich Pin markup in zoomed pins.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-schema-markup-validator':

							$text = '<p class="top">';

							$text .= __( 'Validate the webpage Schema JSON-LD, Microdata and RDFa structured data markup.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'This tool provides additional validation for Schema types beyond the limited subset of <a href="%s">Google Rich Result types</a>.', 'wpsso' ), __( 'https://developers.google.com/search/docs/guides/search-gallery', 'wpsso' ) );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-twitter':

							$text = '<p class="top">';

							$text .= __( 'The Twitter Card validator does not (currently) accept query arguments - paste the following URL in the Twitter Card validator "Card URL" input field:', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-w3c':

							$text = '<p class="top">';

							$text .= __( 'Validate the HTML syntax and HTML 5 conformance of your meta tags and theme templates.', 'wpsso' ) . ' ';

							$text .= __( 'Validating your theme templates is important - theme templates with serious errors can prevent social and search crawlers from understanding the webpage structure.', 'wpsso' ) . ' ';

							$text .= '</p>';

						 	break;

						/**
						 * Called at the bottom of the Document SSO > Validate tab.
						 *
						 * Return an empty string if there are no special status messages. 
						 */
						case 'info-meta-validate-info':

							if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

								$text .= '<p class="status-msg left">* ';

								$text .= __( 'Schema markup is disabled.', 'wpsso' );

								$text .= '</p>';

							} elseif ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

								$json_info       = $this->p->cf[ 'plugin' ][ 'wpssojson' ];
								$json_addon_link = $this->p->util->get_admin_url( 'addons#wpssojson', $json_info[ 'short' ] );

								$text .= '<p class="status-msg left">* ';

								$text .= sprintf( __( 'Activate the %s add-on for Google structured data markup.',
									'wpsso' ), $json_addon_link );

								$text .= '</p>';
							}

							if ( ! function_exists( 'amp_get_permalink' ) ) {

								$text .= '<p class="status-msg left">** ';

								$text .= __( 'Activate an AMP plugin to create and validate AMP pages.', 'wpsso' );

								$text .= '</p>';
							}

						 	break;

						case 'info-meta-social-preview':

							$upload_page_url = get_admin_url( $blog_id = null, 'upload.php' );

							$fb_img_dims = '600x315px';

						 	$text = '<p class="status-msg">';

							$text .= sprintf( __( 'The example image container uses the minimum recommended Facebook image dimensions of %s.', 'wpsso' ), $fb_img_dims ) . ' ';

							$text .= '<br/>' . "\n";

							$text .= sprintf( __( 'You can edit images in the <a href="%s">WordPress Media Library</a> to select a preferred cropping area (ie. top or bottom), along with optimizing the social and SEO texts for the image.', 'wpsso' ), $upload_page_url );

							$text .= '</p>' . "\n";

						 	break;

						case 'info-meta-oembed-html':

						 	$text = '<p class="status-msg">';

							$text .= sprintf( __( 'The oEmbed HTML is created by the <code>%s</code> template.', 'wpsso' ), 'wpsso/embed-content' );

							$text .= '</p>';

						 	break;

					}	// End of info-meta switch.

				} else {

					switch ( $msg_key ) {

						case 'info-schema-faq':

							/**
							 * If the WPSSO FAQ add-on is active, avoid showing possible duplicate and confusing information.
							 */
							if ( ! empty( $this->p->avail[ 'p_ext' ][ 'faq' ] ) ) {

								break;
							}

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= __( 'Schema FAQPage markup is a collection of Questions and Answers, and WordPress manages a collection of related content in two different ways:', 'wpsso' ) . ' ';

							$text .= __( 'Schema FAQPage can be a parent page with Schema Question child pages, or a taxonomy term (ie. categories, tags or custom taxonomies) with Schema Question posts / pages assigned to that term.', 'wpsso' ) . ' ';

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-schema-qa':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= __( 'Google requires that Schema QAPage markup include one or more user submitted and upvoted answers.', 'wpsso' ) . ' ';

							$text .= __( 'The Schema QAPage document title is a summary of the question and the content text is the complete question.', 'wpsso' ) . ' ';

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-schema-question':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							/**
							 * If the WPSSO FAQ add-on is active, avoid showing possible duplicate and confusing information.
							 */
							if ( empty( $this->p->avail[ 'p_ext' ][ 'faq' ] ) ) {

								$text .= __( 'The Schema Question type can be a child page of a Schema FAQPage parent, or assigned to a Schema FAQPage taxonomy term.', 'wpsso' ) . ' ';
							}

							$text .= __( 'The Schema Question document title is a summary of the question and the content text is the complete answer for that question.', 'wpsso' ) . ' ';

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-priority-media':

							$upload_page_url = get_admin_url( $blog_id = null, 'upload.php' );

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'You can edit images in the <a href="%s">WordPress Media Library</a> to select a preferred cropping area (ie. top or bottom), along with optimizing the image social and SEO texts.', 'wpsso' ), $upload_page_url );

							$text .= '</p>' . "\n";

							$text .= '</blockquote>';

							break;

						case 'info-robots-meta':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= __( 'The robots meta tag lets you utilize a granular, webpage-specific approach to controlling how an individual webpage should be indexed and served to users in Google Search results.', 'wpsso' ) . ' ';

							$text .= '</p>';

							$text .= '</blockquote>';

						 	break;

						case 'info-plugin-tid':		// Shown in the Licenses settings page.

							$um_info       = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
							$um_info_name  = _x( $um_info[ 'name' ], 'plugin name', 'wpsso' );
							$um_addon_link = $this->p->util->get_admin_url( 'addons#wpssoum', $um_info_name );

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'After purchasing the %1$s plugin or any complementary %2$s add-on, you\'ll receive an email with a unique Authentication ID for the plugin or add-on you purchased.', 'wpsso' ), $this->p_name_pro, $this->dist_pro ) . ' ';

							$text .=  __( 'Enter the Authentication ID you received in the option field corresponding to the plugin or add-on you purchased.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Don\'t forget that the %1$s add-on must be installed and active to check for %2$s version updates.', 'wpsso' ), $um_addon_link, $this->dist_pro ) . ' ;-)';

							$text .= '</p>';


							$text .= '</blockquote>';

							break;

						case 'info-plugin-tid-network':	// Shown in the Network Licenses settings page.

							$um_info      = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
							$um_info_name = _x( $um_info[ 'name' ], 'plugin name', 'wpsso' );

							$licenses_page_link = $this->p->util->get_admin_url( 'licenses',
								_x( 'Premium Licenses', 'lib file description', 'wpsso' ) );

							$text = '<blockquote class="top-info">';

							$text .= '<p>' . sprintf( __( 'After purchasing the %1$s plugin or any complementary %2$s add-on, you\'ll receive an email with a unique Authentication ID for the plugin or add-on you purchased.', 'wpsso' ), $this->p_name_pro, $this->dist_pro ) . ' ';

							$text .= sprintf( __( 'You may enter each Authentication ID on this page <em>to define a value for all sites within the network</em> - or enter Authentication IDs individually on each site\'s %1$s settings page.', 'wpsso' ), $licenses_page_link ) . '</p>';

							$text.= '<p>' . sprintf( __( 'If you enter Authentication IDs in this network settings page, <em>please make sure you have purchased enough licenses for all sites within the network</em> - for example, to license a %1$s add-on for 10 sites, you would need an Authentication ID from a 10 license pack purchase (or better) of that %1$s add-on.', 'wpsso' ), $this->dist_pro ) . '</p>';

							$text .= '<p>' . sprintf( __( '<strong>WordPress uses the default blog to install and/or update plugins from the Network Admin interface</strong> - to update the %1$s and its %2$s add-ons, please make sure the %3$s add-on is active on the default blog, and the default blog is licensed.', 'wpsso' ), $this->p_name_pro, $this->dist_pro, $um_info_name ) . '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-cm':

							// translators: Please ignore - translation uses a different text domain.
							$section_label = __( 'Contact Info' );

							$profile_page_url = get_admin_url( $blog_id = null, 'profile.php' );

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'These options allow you to customize contact fields shown in the "%1$s" section of <a href="%2$s">the user profile page</a>.', 'wpsso' ), $section_label, $profile_page_url ) . ' ';

							$text .= __( 'Contact information from the user profile can be included in meta tags and Schema markup.', 'wpsso' ) . ' ';

							$text .= '<strong>' . sprintf( __( 'You should not modify the <em>%s</em> column unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) ) . '</strong> ';

							$text .= sprintf( __( 'The %s column is for display purposes only and can be changed as you wish.', 'wpsso' ), _x( 'Contact Field Label', 'column title', 'wpsso' ) ) . ' ';

							$text .= '</p> <p>';

							$text .= '<center>';

							$text .= '<strong>' . __( 'Do not enter your contact information here &ndash; these options are for contact field ids and labels only.', 'wpsso' ) . '</strong><br/>';

							$text .= sprintf( __( 'Enter your personal contact information in <a href="%s">the user profile page</a>.', 'wpsso' ), $profile_page_url );

							$text .= '</center>';

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-user-about':

							// translators: Please ignore - translation uses a different text domain.
							$section_label = __( 'About Yourself' );

							$profile_page_url = get_admin_url( $blog_id = null, 'profile.php' );

							$text = '<blockquote class="top-info"><p>';

							$text .= sprintf( __( 'These options allow you to customize additional fields shown in the "%1$s" section of <a href="%2$s">the user profile page</a>.', 'wpsso' ), $section_label, $profile_page_url ) . ' ';

							$text .= __( 'This additional user profile information can be included in meta tags and Schema markup.', 'wpsso' ) . ' ';

							$text .= '</blockquote>';

							break;

						case 'info-product-attrs':

							$text = '<blockquote class="top-info"><p>';

							$text .= sprintf( __( 'These options allow you to customize product attribute names (aka attribute labels) that %s can use to request additional product information from your e-commerce plugin.', 'wpsso' ), $this->p_name_pro ) . ' ';

							$text .= __( 'Note that these are product attribute names that you can create in your e-commerce plugin and not their values.', 'wpsso' ) . ' ';

							$text .= '</p> <p><center><strong>';

							$text .= __( 'Do not enter product attribute values here &ndash; these options are for product attribute names only.', 'wpsso' );

							$text .= '</strong><br/>';

							$text .= __( 'You can create the following product attribute names and enter their corresponding values in your e-commerce plugin.', 'wpsso' );

							$text .= '</center></p>';

							if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {

								$text .= '<p><center><strong>';

								$text .= __( 'An active WooCommerce plugin has been detected.', 'wpsso' );

								$text .= '</strong></br>';

								$text .= __( 'Please note that WooCommerce creates a selector on the purchase page for product attributes used for variations.', 'wpsso' ) . ' ';

								// translators: Please ignore - translation uses a different text domain.
								$used_for_variations = __( 'Used for variations', 'woocommerce' );

								$text .= sprintf( __( 'Enabling the WooCommerce "%s" attribute option may not be suitable for some product attributes (like GTIN, ISBN, and MPN).', 'wpsso' ), $used_for_variations ) . ' ';

								$text .= __( 'We suggest using a supported third-party plugin to manage Brand, GTIN, ISBN, and MPN values for variations.', 'wpsso' );

								$text .= '</center></p>';
							}

							$text .= '</blockquote>';

							break;

						case 'info-custom-fields':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'These options allow you to customize custom field names (aka metadata names) that %s can use to get additional information about your content.', 'wpsso' ), $this->p_name_pro ) . ' ';

							$text .= '</p> <p><center><strong>';

							$text .= __( 'Do not enter custom field values here &ndash; these options are for custom field names only.', 'wpsso' ) . ' ';

							$text .= '</strong><br/>';

							$text .= __( 'Use the following custom field names when creating custom fields for your posts, pages, and custom post types.', 'wpsso' ) . ' ';

							$text .= '</center></p>';

							if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {


								$text .= '<p><center><strong>';

								$text .= __( 'An active WooCommerce plugin has been detected.', 'wpsso' ) . ' ';

								$text .= '</strong></br>';

								$text .= __( 'Note that product attributes from WooCommerce have precedence over custom field values.', 'wpsso' ) . ' ';

								$text .= sprintf( __( 'Refer to the <a href="%s">WooCommerce integration notes</a> for information on setting up product attributes and custom fields.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/installation/integration/woocommerce-integration/' ) . ' ';

								$text .= __( 'We suggest using a supported third-party plugin to manage Brand, GTIN, ISBN, and MPN values for variations.', 'wpsso' ) . ' ';

								$text .= '</center></p>';
							}

							$text .= '</blockquote>';

							break;

						case 'info-head_tags':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							// translators: %1$s is the plugin name, %2$s is <head>.
							$text .= sprintf( __( '%1$s adds the following Facebook, Open Graph, Twitter, Schema, Pinterest, and SEO HTML tags to the %2$s section of your webpages.', 'wpsso' ), $info[ 'short' ], '<code>&lt;head&gt;</code>' ) . ' ';

							$text .= __( 'If your theme or another plugin already creates one or more of these HTML tags, you can uncheck them here to prevent duplicates from being added.', 'wpsso' ) . ' ';

							// translators: %1$s is "link rel canonical", %2$s is "meta name description", and %3$s is "meta name robots".
							$text .= sprintf( __( 'Please note that the %1$s HTML tag is disabled by default (as themes often include this HTML tag in their header templates), and the %2$s and %3$s HTML tags are disabled automatically if a known SEO plugin is detected.', 'wpsso' ), '<code>link rel canonical</code>', '<code>meta name description</code>', '<code>meta name robots</code>' );

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-image_dimensions':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( '%s and WordPress create image files for social sites and search engines based on the following image dimensions and crop settings.', 'wpsso' ), $info[ 'short' ] ) . ' ';

							$text .= __( 'Image sizes that use the same dimensions and crop settings will create just one image file.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The default dimensions and crop settings from %1$s create only %2$s image files from an original full size image (provided the original image is large enough or image upscaling has been enabled).', 'wpsso' ), $info[ 'short' ], __( 'five', 'wpsso' ) );

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-wp_sitemaps':

							$sitemap_url    = get_site_url( $blog_id = null, $path = '/wp-sitemap.xml' );
							$no_index_label = _x( 'No Index', 'option label', 'wpsso' );
							$mb_title       = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
							$robots_tab     = _x( 'Robots Meta', 'metabox tab', 'wpsso' );

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'These options allow you to customize post and taxonomy types included in the <a href="%s">WordPress sitemap XML</a>.', 'wpsso' ), $sitemap_url ) . ' ';

							$text .= '</p><p>';

							$text .= sprintf( __( 'To <strong>exclude</strong> individual posts, pages, custom post types, taxonomy terms (categories, tags, etc.), or user profile pages from the WordPress sitemap XML, enable the <strong>%1$s</strong> option under their %2$s &gt; %3$s tab.', 'wpsso' ), $no_index_label, $mb_title, $robots_tab ) . ' ';

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						default:

							$text = apply_filters( 'wpsso_messages_info', $text, $msg_key, $info );

							break;

					}	// End of info switch.
				}

			/**
			 * Misc pro messages
			 */
			} elseif ( 0 === strpos( $msg_key, 'pro-' ) ) {

				switch ( $msg_key ) {

					case 'pro-feature-msg':

						$text = '<p class="pro-feature-msg">';

						$text .= empty( $url[ 'purchase' ] ) ? '' : '<a href="' . $url[ 'purchase' ] . '">';

						if ( 'wpsso' === $plugin_id ) {

							$text .= sprintf( __( 'Purchase the %s plugin to upgrade and get the following features.', 'wpsso' ),
								$info[ 'short_pro' ] );

						} else {

							$text .= sprintf( __( 'Purchase the %s add-on to upgrade and get the following features.', 'wpsso' ),
								$info[ 'short_pro' ] );
						}

						$text .= empty( $url[ 'purchase' ] ) ? '' : '</a>';

						$text .= '</p>';

						break;

					case 'pro-ecom-product-msg':

						if ( empty( $this->p->avail[ 'ecom' ][ 'any' ] ) ) {	// Just in case.

							$text = '';

						} else {

							if ( ! empty( $this->pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

								if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {

									// translators: Please ignore - translation uses a different text domain.
									$wc_mb_name = '<strong>' . __( 'Product data', 'woocommerce' ) . '</strong>';

									$text = '<p class="pro-feature-msg">';

									$text .= sprintf( __( 'Disabled product information fields show values imported from the WooCommerce %s metabox.', 'wpsso' ), $wc_mb_name ) . '<br/>';

									$text .= sprintf( __( 'Edit product information in the WooCommerce %s metabox to update the default values.', 'wpsso' ), $wc_mb_name );

									$text .= '</p>';

								} else {

									$text = '<p class="pro-feature-msg">';

									$text .= __( 'An e-commerce plugin is active &ndash; disabled product information fields show values imported from the e-commerce plugin.', 'wpsso' );

									$text .= '</p>';
								}

							} else {

								$text = '<p class="pro-feature-msg">';

								$text .= empty( $url[ 'purchase' ] ) ? '' : '<a href="' . $url[ 'purchase' ] . '">';

								$text .= sprintf( __( 'An e-commerce plugin is active &ndash; product information may be imported by the %s plugin.', 'wpsso' ), $this->p_name_pro );

								$text .= empty( $url[ 'purchase' ] ) ? '' : '</a>';

								$text .= '</p>';
							}
						}

						break;

					case 'pro-purchase-link':

						if ( empty( $info[ 'ext' ] ) ) {	// Nothing to do.

							break;
						}

						if ( $this->pkg_info[ $info[ 'ext' ] ][ 'pp' ] ) {

							$text = _x( 'Get More Licenses', 'plugin action link', 'wpsso' );

						} elseif ( $info[ 'ext' ] === $plugin_id ) {

							$text = sprintf( _x( 'Purchase %s Plugin', 'plugin action link', 'wpsso' ), $this->dist_pro );

						} else {

							$text = sprintf( _x( 'Purchase %s Add-on', 'plugin action link', 'wpsso' ), $this->dist_pro );
						}

						if ( ! empty( $info[ 'url' ] ) ) {

							$text = '<a href="' . $info[ 'url' ] . '"' . ( empty( $info[ 'tabindex' ] ) ? '' :
								' tabindex="' . $info[ 'tabindex' ] . '"' ) . '>' .  $text . '</a>';
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_pro', $text, $msg_key, $info );

						break;
				}

			/**
			 * Misc notice messages
			 */
			} elseif ( 0 === strpos( $msg_key, 'notice-' ) ) {

				switch ( $msg_key ) {

					case 'notice-image-rejected':

						$mb_title     = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
						$media_tab    = _x( 'Priority Media', 'metabox tab', 'wpsso' );
						$is_meta_page = WpssoWpMeta::is_meta_page();

						$text = '<!-- show-once -->';

						$text .= ' <p>';

						$text .= __( 'Please note that a correctly sized image improves click-through-rates by presenting your content at its best on social sites and in search results.', 'wpsso' ) . ' ';

						if ( $is_meta_page ) {

							$text .= sprintf( __( 'A larger image can be uploaded and/or selected in the %1$s metabox under the %2$s tab.', 'wpsso' ), $mb_title, $media_tab );

						} else {

							$text .= __( 'Consider replacing the original image with a higher resolution version.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'See <a href="%s">Why shouldn\'t I upload small images to the media library?</a> for more information on WordPress image sizes.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/faqs/why-shouldnt-i-upload-small-images-to-the-media-library/' ). ' ';
						}

						$text .= '</p>';

						/**
						 * WpssoMedia->is_image_within_config_limits() sets 'show_adjust_img_opts' = false
						 * for images with an aspect ratio that exceeds the hard-coded config limits.
						 */
						if ( ! isset( $info[ 'show_adjust_img_opts' ] ) || ! empty( $info[ 'show_adjust_img_opts' ] ) ) {

							if ( current_user_can( 'manage_options' ) ) {

								$upscale_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Upscale Media Library Images', 'option label', 'wpsso' ) );

								$percent_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ) );

								$image_dim_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Enforce Image Dimension Checks', 'option label', 'wpsso' ) );

								$image_sizes_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_image_sizes',
									_x( 'Image Sizes', 'lib file description', 'wpsso' ) );

								$text .= ' <p><strong>';

								$text .= __( 'Additional information shown only to users with Administrative privileges:', 'wpsso' );

								$text .= '</strong></p>';

								$text .= '<ul>';

								$text .= ' <li>' . __( 'Replace the original image with a higher resolution version.', 'wpsso' ) . '</li>';

								if ( $is_meta_page ) {

									$text .= ' <li>' . sprintf( __( 'Select a larger image under the %1$s &gt; %2$s tab.', 'wpsso' ), $mb_title, $media_tab ) . '</li>';
								}

								if ( empty( $this->p->options[ 'plugin_upscale_images' ] ) ) {

									$text .= ' <li>' . sprintf( __( 'Enable the %s option.', 'wpsso' ), $upscale_option_link ) . '</li>';

								} else {

									$text .= ' <li>' . sprintf( __( 'Increase the %s option value.', 'wpsso' ), $percent_option_link ) . '</li>';
								}

								/**
								 * Note that WpssoMedia->is_image_within_config_limits() sets
								 * 'show_adjust_img_size_opts' to false for images that are too
								 * small for the hard-coded config limits.
								 */
								if ( ! isset( $info[ 'show_adjust_img_size_opts' ] ) || ! empty( $info[ 'show_adjust_img_size_opts' ] ) ) {

									$text .= ' <li>' . sprintf( __( 'Update image size dimensions in the %s settings page.', 'wpsso' ), $image_sizes_tab_link ) . '</li>';

									if ( ! empty( $this->p->options[ 'plugin_check_img_dims' ] ) ) {

										$text .= ' <li>' . sprintf( __( 'Disable the %s option (not recommended).', 'wpsso' ), $image_dim_option_link ) . '</li>';
									}
								}

								$text .= '</ul>';
							}
						}

						$text .= '<!-- /show-once -->';

						break;

					case 'notice-missing-og-image':

						$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'An Open Graph image meta tag could not be generated from this webpage content or its custom %s metabox settings. Facebook <em>requires at least one image meta tag</em> to render shared content correctly.', 'wpsso' ), $mb_title );

						break;

					case 'notice-missing-og-description':

						$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'An Open Graph description meta tag could not be generated from this webpage content or its custom %s metabox settings. Facebook <em>requires a description meta tag</em> to render shared content correctly.', 'wpsso' ), $mb_title );

						break;

					case 'notice-missing-schema-image':

						$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'A Schema "image" property could not be generated from this webpage content or its custom %s metabox settings. Google <em>requires at least one "image" property</em> for this Schema type.', 'wpsso' ), $mb_title );

						break;

					/**
					 * Notice shown when saving settings if the "Use Filtered Content" option is unchecked.
					 */
					case 'notice-content-filters-disabled':

						$option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
							_x( 'Use Filtered Content', 'option label', 'wpsso' ) );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %s advanced option is currently disabled.', 'wpsso' ), $option_link ) . '</b> ';

						$text .= sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions and detect additional images and/or embedded videos provided by shortcodes.', 'wpsso' ), $this->p_name );

						$text .= '</p> <p>';

						$text .= '<b>' . __( 'Many themes and plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ) . '</b> ';

						$text .= __( 'If you use shortcodes in your content text, this option should be enabled - IF YOU EXPERIENCE WEBPAGE LAYOUT OR PERFORMANCE ISSUES AFTER ENABLING THIS OPTION, determine which theme or plugin is filtering the content incorrectly and report the problem to its author(s).', 'wpsso' );

						$text .= '</p>';

						if ( empty( $this->pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

							$text .= '<p>' . sprintf( __( 'Note that the %1$s option is an advanced %2$s feature.', 'wpsso' ), $option_link, $this->p_name_pro ) . '</p>';
						}

						break;

					/**
					 * Notice shown when saving settings if the "Enforce Image Dimension Checks" option is unchecked.
					 */
					case 'notice-check-img-dims-disabled':

						$option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
							_x( 'Enforce Image Dimension Checks', 'option label', 'wpsso' ) );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %s advanced option is currently disabled.', 'wpsso' ), $option_link ) . '</b> ';

						$text .= __( 'Providing social and search sites with perfectly resized images is highly recommended, so this option should be enabled if possible.', 'wpsso' ) . ' ';

						$text .= __( 'Content authors often upload small featured images, without knowing that WordPress creates resized images based on predefined image sizes, so this option is disabled by default.', 'wpsso' ) . ' ';

						$text .= sprintf( __( 'See <a href="%s">Why shouldn\'t I upload small images to the media library?</a> for more information on WordPress image sizes.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/faqs/why-shouldnt-i-upload-small-images-to-the-media-library/' ). ' ';

						$text .= '</p>';

						if ( empty( $this->pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

							$text .= '<p>' . sprintf( __( 'Note that the %1$s option is an advanced %2$s feature.', 'wpsso' ), $option_link, $this->p_name_pro ) . '</p>';
						}

						break;

					case 'notice-ratings-reviews-wc-enabled':

						$option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_services-tab_ratings_reviews',
							_x( 'Ratings and Reviews Service', 'option label', 'wpsso' ) );

						$wc_settings_page_url = get_admin_url( $blog_id = null, 'admin.php?page=wc-settings&tab=products' );

						$text = sprintf( __( 'WooCommerce product reviews are not compatible with the selected %s service API.', 'wpsso' ),
							_x( 'Stamped.io (Ratings and Reviews)', 'metabox title', 'wpsso' ) ) . ' ';

						$text .= sprintf( __( 'Please choose another %1$s or <a href="%2$s">disable the product reviews in WooCommerce</a>.',
							'wpsso' ), $option_link, $wc_settings_page_url ) . ' ';

						break;

					case 'notice-wp-config-php-variable-home':

						$const_html = '<code>WP_HOME</code>';

						$cfg_php_html = '<code>wp-config.php</code>';

						$text = sprintf( __( 'The %1$s constant definition in your %2$s file contains a variable.', 'wpsso' ), $const_html, $cfg_php_html ) . ' ';

						$text .= sprintf( __( 'WordPress uses the %s constant to provide a single unique canonical URL for each webpage and Media Library content.', 'wpsso' ), $const_html ) . ' ';

						$text .= sprintf( __( 'A changing %s value will create different canonical URLs in your webpages, leading to duplicate content penalties from Google, incorrect social share counts, possible broken media links, mixed content issues, and SSL certificate errors.', 'wpsso' ), $const_html ) . ' ';

						$text .= sprintf( __( 'Please update your %1$s file and provide a fixed, non-variable value for the %2$s constant.', 'wpsso' ), $cfg_php_html, $const_html );

						break;

					case 'notice-header-tmpl-no-head-attr':

						$filter_name = 'head_attributes';
						$tag_code    = '<code>&lt;head&gt;</code>';
						$php_code    = '<pre><code>&lt;head &lt;?php do_action( &#39;add_head_attributes&#39; ); ?&gt;&gt;</code></pre>';
						$action_url  = wp_nonce_url( $this->p->util->get_admin_url( '?wpsso-action=modify_tmpl_head_attributes' ),
							WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

						$text = '<p class="top">';

						$text .= __( 'At least one of your theme header templates does not offer a recognized way to modify the head HTML tag attributes.', 'wpsso' ) . ' ';

						$text .= __( 'Adding the document Schema item type to the head HTML tag attributes is important for Pinterest.', 'wpsso' ) . ' ';

						if ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

							$text .= __( 'It is also important for Google in cases where Schema markup describing the content is not available in the webpage (for example, when the complementary WPSSO JSON add-on is not active).', 'wpsso' ) . ' ';
						}

						$text .= '</p> <p>';

						$text .= sprintf( __( 'The %s HTML tag in your header template(s) should include a function, action, or filter for its attributes.', 'wpsso' ), $tag_code ) . ' ';

						$text .= sprintf( __( '%1$s can update your header template(s) automatically and change the existing %2$s HTML tag to:', 'wpsso' ), $info[ 'short' ], $tag_code );

						$text .= '</p>' . $php_code . '<p>';

						$text .= sprintf( __( '<b><a href="%s">Click here to update header template(s) automatically</a></b> (recommended) or update the template(s) manually.', 'wpsso' ), $action_url );

						$text .= '</p>';

						break;

					case 'notice-pro-not-installed':

						$licenses_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Premium Licenses', 'lib file description', 'wpsso' ) );

						$text = sprintf( __( 'An Authentication ID has been entered for %1$s but the plugin is not installed - you can install and activate the %2$s version from the %3$s settings page.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $this->dist_pro, $licenses_page_link ) . ' ;-)';

						break;

					case 'notice-pro-not-updated':

						$licenses_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Premium Licenses', 'lib file description', 'wpsso' ) );

						$text = sprintf( __( 'An Authentication ID has been entered for %1$s in the %2$s settings page but the %3$s version is not installed - don\'t forget to update the plugin to install the latest %3$s version.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $licenses_page_link, $this->dist_pro ) . ' ;-)';

						break;

					case 'notice-um-add-on-required':
					case 'notice-um-activate-add-on':

						$um_info      = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
						$um_info_name = _x( $um_info[ 'name' ], 'plugin name', 'wpsso' );

						$addons_page_link = $this->p->util->get_admin_url( 'addons#wpssoum',
							_x( 'Complementary Add-ons', 'lib file description', 'wpsso' ) );

						$licenses_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Premium Licenses', 'lib file description', 'wpsso' ) );

						$plugins_page_url = get_admin_url( $blog_id = null, 'plugins.php' );

						// translators: Please ignore - translation uses a different text domain.
						$plugins_page_link = '<a href="' . $plugins_page_url . '">' . __( 'Plugins' ) . '</a>';

						$text = '<p>';

						$text .= '<b>' . sprintf( __( 'At least one Authentication ID has been entered in the %1$s settings page, but the %2$s add-on is not active.', 'wpsso' ), $licenses_page_link, $um_info_name ) . '</b> ';

						$text .= '</p> <p>';

						$text .= sprintf( __( 'This complementary add-on is required to update and enable the %1$s plugin and its %2$s add-ons.', 'wpsso' ), $this->p_name_pro, $this->dist_pro ) . ' ';

						if ( 'notice-um-add-on-required' === $msg_key ) {

							$text .= sprintf( __( 'Install and activate the %1$s add-on from the %2$s settings page.', 'wpsso' ), $um_info_name, $addons_page_link ) . ' ';

						} else {

							$text .= sprintf( __( 'The %1$s add-on can be activated from the WordPress %2$s page - please activate this complementary add-on now.', 'wpsso' ), $um_info_name, $plugins_page_link ) . ' ';
						}

						$text .= sprintf( __( 'When the %1$s add-on is active, one or more %2$s updates may be available for the %3$s plugin and its add-on(s).', 'wpsso' ), $um_info_name, $this->dist_pro, $this->p_name_pro );

						$text .= '</p>';

						break;

					case 'notice-um-version-recommended':

						$um_info          = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
						$um_info_name     = _x( $um_info[ 'name' ], 'plugin name', 'wpsso' );
						$um_version       = isset( $um_info[ 'version' ] ) ? $um_info[ 'version' ] : 'unknown';
						$um_rec_version   = WpssoConfig::$cf[ 'um' ][ 'rec_version' ];
						$um_check_updates = _x( 'Check for Plugin Updates', 'submit button', 'wpsso' );

						$tools_page_link = $this->p->util->get_admin_url( 'tools',
							_x( 'Tools and Actions', 'lib file description', 'wpsso' ) );

						$wp_updates_page_link = '<a href="' . admin_url( 'update-core.php' ) . '">' . 
							// translators: Please ignore - translation uses a different text domain.
							__( 'Dashboard' ) . ' &gt; ' . 
							// translators: Please ignore - translation uses a different text domain.
							__( 'Updates' ) . '</a>';

						$text = sprintf( __( '%1$s version %2$s requires the use of %3$s version %4$s or newer (version %5$s is currently installed).', 'wpsso' ), $this->p_name_pro, $info[ 'version' ], $um_info_name, $um_rec_version, $um_version ) . ' ';

						// translators: %1$s is the WPSSO Update Manager add-on name.
						$text .= sprintf( __( 'If an update for the %1$s add-on is not available under the WordPress %2$s page, use the <em>%3$s</em> button in the %4$s settings page to force an immediate refresh of the plugin update information.', 'wpsso' ), $um_info_name, $wp_updates_page_link, $um_check_updates, $tools_page_link );

						break;

					case 'notice-recommend-version':

						$text = sprintf( __( 'You are using %1$s version %2$s - <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.', 'wpsso' ), $info[ 'app_label' ], $info[ 'app_version' ], $info[ 'version_url' ] ) . ' ';

						$text .= sprintf( __( 'If possible, please update to the latest %1$s stable release (or at least version %2$s).', 'wpsso' ), $info[ 'app_label' ], $info[ 'rec_version' ] );

						break;

					default:

						$text = apply_filters( 'wpsso_messages_notice', $text, $msg_key, $info );

						break;
				}

			/**
			 * Misc sidebox messages
			 */
			} elseif ( 0 === strpos( $msg_key, 'column-' ) ) {

				$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

				$li_support_link = empty( $info[ 'url' ][ 'support' ] ) ? '' :
					'<li><a href="' . $info[ 'url' ][ 'support' ] . '">' . __( 'Premium plugin support.', 'wpsso' ) . '</a></li>';

				switch ( $msg_key ) {

					case 'column-purchase-wpsso':

						$advanced_page_url = $this->p->util->get_admin_url( 'advanced' );

						$text = '<p><strong>' . sprintf( __( 'The %s plugin includes:', 'wpsso' ), $info[ 'name_pro' ] ) . '</strong></p>';

						$text .= '<ul>';

						$text .= ' <li>' . __( 'Integration with third-party plugins and service APIs (WooCommerce, Yoast SEO, YouTube, Bitly, and many more).', 'wpsso' ) . '</li>';

						$text .= ' <li>' . __( 'Detection of embedded videos in content text.', 'wpsso' ) . '</li>';

						$text .= ' <li>' . __( 'Provides Twitter Player Card meta tags.', 'wpsso' ) . '</li>';

						$text .= ' <li>' . __( 'Upscaling of images and URL shortening.', 'wpsso' ) . '</li>';

						$text .= ' <li>' . sprintf( __( '<a href="%s">Customize advanced settings</a>, including image sizes, cache expiry, video services, shortening services, document types, contact fields, product attributes, custom fields, and more.', 'wpsso' ), $advanced_page_url ) . '</li>';

						$text .= $li_support_link;

						$text .= '</ul>';

						break;

					case 'column-purchase-wpssojson':

						$text = '<p><strong>' . sprintf( __( 'The %s add-on includes:', 'wpsso' ), $info[ 'name_pro' ] ) . '</strong></p>';

						$text .= '<ul>';

						$text .= ' <li>' . sprintf( __( 'Additional Schema options in the %s metabox to customize creative works, events, how-tos, job postings, movies, products, recipes, reviews, and many more.', 'wpsso' ), $mb_title ) . '</li>';

						$text .= $li_support_link;

						$text .= '</ul>';

						break;

					case 'column-help-support':

						$text = '<p>';

						$text .= sprintf( __( '<strong>Development of %s is driven by user requests</strong> - we welcome all your comments and suggestions.', 'wpsso' ), $info[ 'short' ] ) . ' ;-)';

						$text .= '</p>';

						break;

					case 'column-rate-review':

						$text = '<p><strong>';

						$text .= __( 'It would help tremendously if you could rate the following plugins on WordPress.org!', 'wpsso' ) . ' ';

						$text .= '</strong></p>' . "\n";

						$text .= '<p>';

						$text .= __( 'New ratings are an excellent way to ensure the continued success of your favorite plugins.', 'wpsso' ) . ' ';

						$text .= '</p>' . "\n";

						$text .= '<p>';

						$text .= __( 'Without new ratings, plugins and add-ons that you depend on could be discontinued prematurely.', 'wpsso' ) . ' ';

						$text .= __( 'Don\'t let that happen!', 'wpsso' ) . ' ';

						$text .= __( 'Rate your active plugins today - it only takes a few seconds to rate a plugin!', 'wpsso' ) . ' ;-)';

						$text .= '</p>' . "\n";

						break;

					default:

						$text = apply_filters( 'wpsso_messages_column', $text, $msg_key, $info );

						break;
				}

			} else {

				$text = apply_filters( 'wpsso_messages', $text, $msg_key, $info );
			}

			if ( ! empty( $info[ 'is_locale' ] ) ) {

				// translators: %s is the wordpress.org URL for the WPSSO User Locale Selector add-on.
				$text .= ' ' . sprintf( __( 'This option is localized - <a href="%s">you may change the WordPress locale</a> to define alternate values for different languages.', 'wpsso' ), 'https://wordpress.org/plugins/wpsso-user-locale/' );
			}

			if ( 0 === strpos( $msg_key, 'tooltip-' ) && ! empty( $text ) ) {

				$text = '<span class="' . $this->p->cf[ 'form' ][ 'tooltip_class' ] . '" data-help="' . esc_attr( $text ) . '">' .
					'<span class="' . $this->p->cf[ 'form' ][ 'tooltip_class' ] . '-icon"></span></span>';
			}

			return $text;
		}

		/**
		 * If an add-on is not active, return a short message that this add-on is required.
		 *
		 * Used by WPSSO Core and several add-ons to get the WPSSO ORG and PLM add-on required message.
		 */
		public function maybe_ext_required( $ext ) {

			list( $ext, $p_ext ) = $this->ext_p_ext( $ext );

			if ( empty( $ext ) ) {							// Just in case.

				return '';

			} elseif ( 'wpsso' === $ext ) {						// The main plugin is not considered an add-on.

				return '';

			} elseif ( ! empty( $this->p->avail[ 'p_ext' ][ $p_ext ] ) ) {		// Add-on is already active.

				return '';

			} elseif ( empty( $this->p->cf[ 'plugin' ][ $ext ][ 'short' ] ) ) {	// Unknown add-on.

				return '';
			}

			// translators: %s is is the short add-on name.
			$text = sprintf( _x( '%s required', 'option comment', 'wpsso' ), $this->p->cf[ 'plugin' ][ $ext ][ 'short' ] );

			$text = $this->p->util->get_admin_url( 'addons#' . $ext, $text );

			return ' <span class="ext-req-msg">' . $text . '</span>';
		}

		/**
		 * Used by the General Settings page.
		 */
		public function maybe_preview_images_first() {

			$text = '';

			if ( ! empty( $this->form->options[ 'og_vid_prev_img' ] ) ) {

				$text .= ' ' . _x( 'note that video preview images are enabled (and included first)', 'option comment', 'wpsso' );
			}

			return $text;
		}

		/**
		 * Used by the Advanced Settings page for the "Webpage Document Title" option.
		 */
		public function maybe_title_tag_disabled() {

			if ( current_theme_supports( 'title-tag' ) ) {

				return '';
			}

			$text = sprintf( __( '<a href="%s">WordPress Title Tag</a> not supported by theme', 'wpsso' ),
				__( 'https://codex.wordpress.org/Title_Tag', 'wpsso' ) );

			return '<span class="option-warning">' . $text . '</span>';
		}

		public function preview_images_are_first() {

			$html = ' ' . _x( 'note that video preview images are included first', 'option comment', 'wpsso' );

			return $html;
		}

		public function pro_feature( $ext ) {

			list( $ext, $p_ext ) = $this->ext_p_ext( $ext );

			if ( empty( $ext ) ) {

				return '';
			}

			return $this->get( 'pro-feature-msg', array( 'plugin_id' => $ext ) );
		}

		public function pro_feature_video_api() {

			$this->maybe_set_properties();

			$short_pro = $this->pkg_info[ $this->p->id ][ 'short_pro' ];

			$html = '<p class="pro-feature-msg">';

			$html .= sprintf( __( 'Video discovery and service API modules are provided with the %s version.', 'wpsso' ), $short_pro );

			$html .= '</p>';

			return $html . $this->get( 'pro-feature-msg' );
		}

		public function more_schema_options() {

			if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				return $this->schema_disabled();

			}

			$json_info = $this->p->cf[ 'plugin' ][ 'wpssojson' ];

			$json_info_name = _x( $json_info[ 'name' ], 'plugin name', 'wpsso' );

			$json_addon_link = $this->p->util->get_admin_url( 'addons#wpssojson', $json_info_name );

			// translators: %s is is the add-on name (and a link to the add-on page).
			$text = sprintf( __( 'Activate the %s add-on<br/>if you require additional options for Schema markup and structured data.',
				'wpsso' ), $json_addon_link );

			return '<p class="status-msg">' . $text . '</p>';
		}

		/**
		 * Head cache disabled.
		 */
		public function head_cache_disabled() {

			$text = __( 'head cache is disabled (caching plugin or service detected).', 'wpsso' );

			return '<span class="option-info">' . $text . '</span>';
		}

		/**
		 * Pinterest disabled.
		 *
		 * $extra_css_class can be empty, 'left', or 'inline'.
		 */
		public function pin_img_disabled( $extra_css_class = '' ) {

			$option_link = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_pinterest',
				_x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' ) );

			// translators: %s is the option name, linked to its settings page.
			$text = sprintf( __( 'Modifications disabled (%s option is unchecked).', 'wpsso' ), $option_link );

			return '<p class="status-msg smaller disabled ' . $extra_css_class . '">' . $text . '</p>';
		}

		/**
		 * Robots disabled.
		 */
		public function robots_disabled() {

			$seo_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other' );

			$html = '<p class="status-msg"><a href="' . $seo_tab_url . '">' . __( 'Robots meta tag is disabled.', 'wpsso' ) . '</a></p>';

			$html .= '<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>';

			return $html;
		}

		public function get_robots_disabled_rows( $table_rows = array() ) {

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				$table_rows = array();
			}

			$table_rows[ 'robots_disabled' ] = '<tr><td align="center">' . $this->robots_disabled() . '</td></tr>';

			return $table_rows;
		}

		/**
		 * Schema disabled.
		 */
		public function schema_disabled() {

			$html = '<p class="status-msg">' . __( 'Schema markup is disabled.', 'wpsso' ) . '</p>';

			$html .= '<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>';

			return $html;
		}

		public function get_schema_disabled_rows( $table_rows = array(), $col_span = 1 ) {

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				$table_rows = array();
			}

			$table_rows[ 'schema_disabled' ] = '<tr><td align="center" colspan="' . $col_span . '">' . $this->schema_disabled() . '</td></tr>';

			return $table_rows;
		}

		/**
		 * SEO option disabled.
		 */
		public function seo_option_disabled( $mt_name ) {

			// translators: %s is the meta tag name (aka meta name canonical).
			$text = sprintf( __( 'Modifications disabled (<code>%s</code> tag disabled or SEO plugin detected).', 'wpsso' ), $mt_name );

			return '<p class="status-msg smaller disabled">' . $text . '</p>';
		}

		/**
		 * WordPress sitemaps disabled.
		 */
		public function wp_sitemaps_disabled() {

			$html = '<p class="status-msg">' . __( 'WordPress sitemaps are disabled.', 'wpsso' ) . '</p>';

			$html .= '<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>';

			return $html;
		}

		public function get_wp_sitemaps_disabled_rows( $table_rows = array() ) {

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				$table_rows = array();
			}

			$table_rows[ 'wp_sitemaps_disabled' ] = '<tr><td align="center">' . $this->wp_sitemaps_disabled() . '</td></tr>';

			return $table_rows;
		}

		protected function maybe_html_tag_disabled_text( array $parts ) {

			$text = '';

			if ( empty( $parts[ 2 ] ) ) {	// Check for an incomplete HTML tag parts array.

				return $text;
			}

			$opt_key = strtolower( 'add_' . implode( '_', $parts ) );	// Use same concatenation technique as WpssoHead->add_mt_singles().

			$html_tag = implode( ' ', $parts );	// HTML tag string for display.

			$is_disabled = empty( $this->p->options[ $opt_key ] ) ? true : false;

			if ( $is_disabled ) {

				$seo_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other',
					_x( 'SSO', 'menu title', 'wpsso' ) . ' &gt; ' .
					_x( 'Advanced Settings', 'lib file description', 'wpsso' ) . ' &gt; ' .
					_x( 'HTML Tags', 'metabox title', 'wpsso' ) . ' &gt; ' .
					_x( 'SEO / Other', 'metabox tab', 'wpsso' ) );

				$text .= ' ' . sprintf( __( 'Note that the <code>%s</code> HTML tag is currently disabled.', 'wpsso' ), $html_tag ) . ' ';

				$text .= sprintf( __( 'You can re-enable this option under the %s tab.', 'wpsso' ), $seo_tab_link );
			}

			return $text;
		}

		/**
		 * Returns an array of two elements - the custom field option label and a tooltip fragment.
		 */
		protected function get_cf_tooltip_fragments( $msg_key = false ) {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'addl_type_urls' => array(
						'label' => _x( 'Microdata Type URLs', 'option label', 'wpsso' ),
						'desc'  => _x( 'additional microdata type URLs', 'tooltip fragment', 'wpsso' ),
					),
					'book_isbn' => array(
						'label' => _x( 'Book ISBN', 'option label', 'wpsso' ),
						'desc'  => _x( 'an ISBN code (aka International Standard Book Number)', 'tooltip fragment', 'wpsso' ),
					),
					'howto_steps' => array(
						'label' => _x( 'How-To Steps', 'option label', 'wpsso' ),
						'desc'  => _x( 'how-to steps', 'tooltip fragment', 'wpsso' ),
					),
					'howto_supplies' => array(
						'label' => _x( 'How-To Supplies', 'option label', 'wpsso' ),
						'desc'  => _x( 'how-to supplies', 'tooltip fragment', 'wpsso' ),
					),
					'howto_tools' => array(
						'label' => _x( 'How-To Tools', 'option label', 'wpsso' ),
						'desc'  => _x( 'how-to tools', 'tooltip fragment', 'wpsso' ),
					),
					'img_url' => array(
						'label' => _x( 'Image URL', 'option label', 'wpsso' ),
						'desc'  => _x( 'an image URL', 'tooltip fragment', 'wpsso' ),
					),
					'product_avail' => array(
						'label' => _x( 'Product Availability', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product availability', 'tooltip fragment', 'wpsso' ),
					),
					'product_brand' => array(
						'label' => _x( 'Product Brand', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product brand', 'tooltip fragment', 'wpsso' ),
					),
					'product_category' => array(
						'label' => _x( 'Product Type', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a <a href="%s">Google product type</a>', 'tooltip fragment', 'wpsso' ),
							__( 'https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt', 'wpsso' ) ),
					),
					'product_color' => array(
						'label' => _x( 'Product Color', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product color', 'tooltip fragment', 'wpsso' ),
					),
					'product_condition' => array(
						'label' => _x( 'Product Condition', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product condition', 'tooltip fragment', 'wpsso' ),
					),
					'product_currency' => array(
						'label' => _x( 'Product Currency', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product currency', 'tooltip fragment', 'wpsso' ),
					),
					'product_depth_value' => array(
						'label' => _x( 'Product Depth', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product depth (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'depth' ) ),
					),
					'product_gtin14' => array(
						'label' => _x( 'Product GTIN-14', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-14 code (aka ITF-14)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin13' => array(
						'label' => _x( 'Product GTIN-13 (EAN)', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-13 code (aka 13-digit ISBN codes or EAN/UCC-13)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin12' => array(
						'label' => _x( 'Product GTIN-12 (UPC)', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-12 code (12-digit GS1 identification key composed of a UPC company prefix, item reference, and check digit)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin8' => array(
						'label' => _x( 'Product GTIN-8', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-8 code (aka EAN/UCC-8 or 8-digit EAN)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin' => array(
						'label' => _x( 'Product GTIN', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN code (GTIN-8, GTIN-12/UPC, GTIN-13/EAN, or GTIN-14)', 'tooltip fragment', 'wpsso' ),
					),
					'product_height_value' => array(
						'label' => _x( 'Product Height', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product height (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'height' ) ),
					),
					'product_isbn' => array(
						'label' => _x( 'Product ISBN', 'option label', 'wpsso' ),
						'desc'  => _x( 'an ISBN code (aka International Standard Book Number)', 'tooltip fragment', 'wpsso' ),
					),
					'product_length_value' => array(
						'label' => _x( 'Product Length', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product length (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'length' ) ),
					),
					'product_material' => array(
						'label' => _x( 'Product Material', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product material', 'tooltip fragment', 'wpsso' ),
					),
					'product_mfr_part_no' => array(
						'label' => _x( 'Product MPN', 'option label', 'wpsso' ),
						'desc'  => _x( 'a Manufacturer Part Number (MPN)', 'tooltip fragment', 'wpsso' ),
					),
					'product_price' => array(
						'label' => _x( 'Product Price', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product price', 'tooltip fragment', 'wpsso' ),
					),
					'product_retailer_part_no' => array(
						'label' => _x( 'Product SKU', 'option label', 'wpsso' ),
						'desc'  => _x( 'a Stock-Keeping Unit (SKU)', 'tooltip fragment', 'wpsso' ),
					),
					'product_size' => array(
						'label' => _x( 'Product Size', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product size', 'tooltip fragment', 'wpsso' ),
					),
					'product_target_gender' => array(
						'label' => _x( 'Product Target Gender', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product target gender', 'tooltip fragment', 'wpsso' ),
					),
					'product_fluid_volume_value' => array(
						'label' => _x( 'Product Fluid Volume', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product fluid volume (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'fluid_volume' ) ),
					),
					'product_weight_value' => array(
						'label' => _x( 'Product Weight', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product weight (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'weight' ) ),
					),
					'product_width_value' => array(
						'label' => _x( 'Product Width', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product width (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'width' ) ),
					),
					'recipe_ingredients' => array(
						'label' => _x( 'Recipe Ingredients', 'option label', 'wpsso' ),
						'desc'  => _x( 'recipe ingredients', 'tooltip fragment', 'wpsso' ),
					),
					'recipe_instructions' => array(
						'label' => _x( 'Recipe Instructions', 'option label', 'wpsso' ),
						'desc'  => _x( 'recipe instructions', 'tooltip fragment', 'wpsso' ),
					),
					'sameas_urls' => array(
						'label' => _x( 'Same-As URLs', 'option label', 'wpsso' ),
						'desc'  => _x( 'additional Same-As URLs', 'tooltip fragment', 'wpsso' ),
					),
					'vid_embed' => array(
						'label' => _x( 'Video Embed HTML', 'option label', 'wpsso' ),
						'desc'  => _x( 'video embed HTML code (not a URL)', 'tooltip fragment', 'wpsso' ),
					),
					'vid_url' => array(
						'label' => _x( 'Video URL', 'option label', 'wpsso' ),
						'desc'  => _x( 'a video URL (not HTML code)', 'tooltip fragment', 'wpsso' ),
					),
				);
			}

			if ( false !== $local_cache ) {

				if ( isset( $local_cache[ $msg_key ] ) ) {

					return $local_cache[ $msg_key ];
				}

				return null;
			}

			return $local_cache;
		}

		protected function get_def_checked( $opt_key ) {

			$def_checked = $this->p->opt->get_defaults( $opt_key ) ?
				_x( 'checked', 'option value', 'wpsso' ) :
				_x( 'unchecked', 'option value', 'wpsso' );

			return $def_checked;
		}

		protected function get_def_img_dims( $opt_pre ) {

			$def_opts    = $this->p->opt->get_defaults();
			$img_width   = empty( $def_opts[ $opt_pre . '_img_width' ] ) ? 0 : $def_opts[ $opt_pre . '_img_width' ];
			$img_height  = empty( $def_opts[ $opt_pre . '_img_height' ] ) ? 0 : $def_opts[ $opt_pre . '_img_height' ];
			$img_cropped = empty( $def_opts[ $opt_pre . '_img_crop' ] ) ? _x( 'uncropped', 'option value', 'wpsso' ) : _x( 'cropped', 'option value', 'wpsso' );

			return $img_width . 'x' . $img_height . 'px ' . $img_cropped;
		}

		/**
		 * Returns an array of two elements.
		 */
		protected function ext_p_ext( $ext ) {

			if ( is_string( $ext ) ) {

				if ( 0 !== strpos( $ext, $this->p->id ) ) {

					$ext = $this->p->id . $ext;
				}

				$p_ext = substr( $ext, strlen( $this->p->id ) );

			} else {

				$ext = '';

				$p_ext = '';
			}

			return array( $ext, $p_ext );
		}
	}
}
