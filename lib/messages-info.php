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

if ( ! class_exists( 'WpssoMessagesInfo' ) ) {

	class WpssoMessagesInfo extends WpssoMessages {

		protected $p;	// Wpsso class object.

		private $meta = null;	// WpssoMessagesInfoMeta class object.

		/**
		 * Instantiated by WpssoMessages->get() only when needed.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			if ( 0 === strpos( $msg_key, 'info-meta-' ) ) {

				/**
				 * Instantiate WpssoMessagesInfoMeta only when needed.
				 */
				if ( null === $this->meta ) {
		
					require_once WPSSO_PLUGINDIR . 'lib/messages-info-meta.php';
		
					$this->meta = new WpssoMessagesInfoMeta( $this->p );
				}
	
				return $this->meta->get( $msg_key, $info );
			}

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

			}	// End of 'info' switch.

			return $text;
		}
	}
}
