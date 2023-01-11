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

if ( ! class_exists( 'WpssoMessagesInfoMeta' ) ) {

	/**
	 * Instantiated by WpssoMessagesInfo->get() only when needed.
	 */
	class WpssoMessagesInfoMeta extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$text = '';

			switch ( $msg_key ) {

				/**
				 * Document SSO > Edit Schema tab.
				 */
				case 'info-meta-schema-faq':

					/**
					 * Avoid showing possible duplicate and confusing information.
					 */
					if ( empty( $this->p->avail[ 'p_ext' ][ 'faq' ] ) ) {

						$faq_info      = $this->p->cf[ 'plugin' ][ 'wpssofaq' ];
						$faq_info_name = _x( $faq_info[ 'name' ], 'plugin name', 'wpsso' );

						$text = '<blockquote class="top-info">';

						$text .= '<p>';

						$text .= __( 'Schema FAQPage markup is a collection of Questions and Answers.', 'wpsso' ) . ' ';

						$text .= __( 'WordPress manages related singular content, like Questions and Answers, in two different ways:', 'wpsso' ) . ' ';

						$text .= __( 'A Schema FAQPage can be a parent page with Schema Question child pages, or a taxonomy term (ie. categories, tags or custom taxonomies) with Schema Question pages assigned to that term.', 'wpsso' ) . ' ';

						$text .= sprintf( __( 'Note that using the %1$s add-on is the easiest and preferred way to manage FAQ pages and its associated question pages.', 'wpsso' ), $faq_info_name ) . ' ';

						$text .= '</p>';

						$text .= '</blockquote>';
					}

					break;

				case 'info-meta-schema-qa':

					$text = '<blockquote class="top-info">';

					$text .= '<p>';

					$text .= __( 'Google requires that Schema QAPage markup include one or more user submitted and upvoted answers.', 'wpsso' ) . ' ';

					$text .= __( 'The Schema QAPage document name / title is a summary of the question, and the full text is the complete question.', 'wpsso' ) . ' ';

					$text .= '</p>';

					$text .= '</blockquote>';

					break;

				case 'info-meta-schema-question':

					$text = '<blockquote class="top-info">';

					$text .= '<p>';

					$text .= __( 'The Schema Question document name / title is a summary of the question, the description is a summary of the answer, and the full text is the complete answer.', 'wpsso' ) . ' ';

					/**
					 * Avoid showing possible duplicate and confusing information.
					 */
					if ( empty( $this->p->avail[ 'p_ext' ][ 'faq' ] ) ) {

						$faq_info      = $this->p->cf[ 'plugin' ][ 'wpssofaq' ];
						$faq_info_name = _x( $faq_info[ 'name' ], 'plugin name', 'wpsso' );

						$text .= __( 'The Schema Question type can be a child page of a Schema FAQPage parent, or assigned to a Schema FAQPage taxonomy term.', 'wpsso' ) . ' ';
						$text .= sprintf( __( 'Note that using the %1$s add-on is often the easiest and preferred way to manage FAQ groups and Question pages.', 'wpsso' ), $faq_info_name ) . ' ';
					}

					$text .= '</p>';

					$text .= '</blockquote>';

					break;

				/**
				 * Document SSO > Edit Media tab.
				 */
				case 'info-meta-priority-media':

					$upload_page_url = get_admin_url( $blog_id = null, 'upload.php' );

					$text = '<blockquote class="top-info">';

					$text .= '<p>';

					$text .= sprintf( __( 'You can edit images in the <a href="%s">WordPress Media Library</a> to select a preferred image cropping area (ie. top or bottom) and optimize the image SEO information.', 'wpsso' ), $upload_page_url ) . ' ';

					$text .= '</p><p>';

					$text .= __( 'Note that the Schema CreativeWork type (and its sub-types) has a \'video\' property for VideoObject markup, but other Schema types (like Event, Job Posting, Place, Product, and Brand) do not have a \'video\' property.', 'wpsso' ). ' ';

					$text .= __( 'In these cases - assuming the video(s) are about the content subject - they will be added to a \'subjectOf\' property instead.', 'wpsso' ). ' ';

					$text .= '</p>' . "\n";

					$text .= '</blockquote>';

					break;

				/**
				 * Document SSO > Edit Visibility tab.
				 */
				case 'info-meta-robots-meta':

					$text = '<blockquote class="top-info">';

					$text .= '<p>';

					$text .= __( 'The robots meta tag lets you utilize a granular, webpage-specific approach to controlling how an individual webpage should be indexed and served to users in Google Search results.', 'wpsso' ) . ' ';

					$text .= '</p>';

					$text .= '</blockquote>';

				 	break;

				/**
				 * Document SSO > Preview Social tab.
				 */
				case 'info-meta-social-preview':

					$upload_page_url = get_admin_url( $blog_id = null, 'upload.php' );

					$fb_img_dims = '600x314px';

				 	$text = '<p class="status-msg">';

					$text .= sprintf( __( 'The example image container uses the minimum recommended Facebook image dimensions of %s.', 'wpsso' ), $fb_img_dims ) . ' ';

					$text .= '<br/>' . "\n";

					$text .= sprintf( __( 'You can edit images in the <a href="%s">WordPress Media Library</a> to select a preferred cropping area (ie. top or bottom), along with optimizing the social and SEO texts for the image.', 'wpsso' ), $upload_page_url );

					$text .= '</p>' . "\n";

				 	break;

				/**
				 * Document SSO > Preview oEmbed tab.
				 */
				case 'info-meta-oembed-footer':

				 	$text = '<p class="status-msg">';

					$text .= sprintf( __( 'oEmbed HTML provided by the <code>%s</code> template.', 'wpsso' ), 'wpsso/embed-content' );

					$text .= '</p>';

				 	break;

				/**
				 * Document SSO > Validators tab.
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

					$text .= sprintf( __( 'Check the webpage structured data markup for <a href="%s">Google Rich Result types</a> (Job posting, Product, Recipe, etc.).', 'wpsso' ), __( 'https://developers.google.com/search/docs/appearance/structured-data/search-gallery', 'wpsso' ) ) . ' ';

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

					$text .= sprintf( __( 'This tool provides additional validation for Schema types beyond the limited subset of <a href="%s">Google Rich Result types</a>.', 'wpsso' ), __( 'https://developers.google.com/search/docs/appearance/structured-data/search-gallery', 'wpsso' ) );

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

				case 'info-meta-validate-footer':

					if ( ! function_exists( 'amp_get_permalink' ) ) {

						$text .= '<p class="status-msg left">* ';

						$text .= __( 'Activate an AMP plugin to create and validate AMP pages.', 'wpsso' );

						$text .= '</p>';
					}

					if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

						$text .= '<p class="status-msg left">** ';

						$text .= __( 'Schema markup is disabled.', 'wpsso' );

						$text .= '</p>';
					}

				 	break;

			}	// End of 'info-meta' switch.

			return $text;
		}
	}
}
