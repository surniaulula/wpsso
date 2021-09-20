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

if ( ! class_exists( 'WpssoMessagesInfoMeta' ) ) {

	/**
	 * Instantiated by WpssoMessagesInfo->get() only when needed.
	 */
	class WpssoMessagesInfoMeta extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

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
				 * Called at the bottom of the Document SSO > Validators tab.
				 */
				case 'info-meta-validate-info':

					if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

						$text .= '<p class="status-msg left">* ';

						$text .= __( 'Schema markup is disabled.', 'wpsso' );

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

			}	// End of 'info-meta' switch.

			return $text;
		}
	}
}
