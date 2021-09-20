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

if ( ! class_exists( 'WpssoMessagesTooltipMetaOpenGraph' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltipMeta->get() only when needed.
	 */
	class WpssoMessagesTooltipMetaOpenGraph extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-meta-og_schema_type':	// Schema Type.

					$text = __( 'Select a document Schema type that best describes the main content of this webpage.', 'wpsso' ) . ' ';

					$text .= __( 'The Schema type option offers a much larger selection of types than the Open Graph type, and the Open Graph type may reflect the Schema type selected (the Open Graph type option will be disabled in this case).', 'wpsso' ) . ' ';

					$text .= __( 'As an example, a Schema type of "Article" will change the Open Graph type to "article", a Schema type of "Place" will change the Open Graph type to "place", a Schema type of "Product" will change the Open Graph type to "product", etc.', 'wpsso' ) . ' ';

				 	break;

				case 'tooltip-meta-og_type':	// Open Graph Type.

					$text = __( 'Select a document Facebook / Open Graph type that best describes the main content of this webpage.', 'wpsso' ) . ' ';

					$text .= __( 'The Schema type option offers a much larger selection of types than the Open Graph type, and the Open Graph type may reflect the Schema type selected (the Open Graph type option will be disabled in this case).', 'wpsso' ) . ' ';

					$text .= __( 'As an example, a Schema type of "Article" will change the Open Graph type to "article", a Schema type of "Place" will change the Open Graph type to "place", a Schema type of "Product" will change the Open Graph type to "product", etc.', 'wpsso' ) . ' ';

					$text .= __( 'Note that for social sharing purposes, the document Open Graph type must be "article", "place", "product", or "website".', 'wpsso' ) . ' ';

				 	break;

				case 'tooltip-meta-og_title':	// Default Title.

					$text = sprintf( __( 'A customized title for the Facebook / Open Graph %s meta tag and the default for all other title values.', 'wpsso' ), '<code>og:title</code>' );

				 	break;

				case 'tooltip-meta-og_desc':	// Default Description.

					$text = sprintf( __( 'A customized description for the Facebook / Open Graph %s meta tag and the default for all other description values.', 'wpsso' ), '<code>og:description</code>' ) . ' ';

					$text .= __( 'Update and save the custom Facebook / Open Graph description to change the default value of all other description fields.', 'wpsso' );

				 	break;

				case 'tooltip-meta-og_img_crop_area':	// Preferred Cropping.

					$text = __( 'Select the preferred cropping (ie. main subject) area of the image.', 'wpsso' );

				 	break;

				case 'tooltip-meta-og_img_id':	// Image ID.

					$text = __( 'A customized image ID to include first, before any featured, attached, or content images.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';

				 	break;

				case 'tooltip-meta-og_img_url':	// or an Image URL.

					$text = __( 'A customized image URL (instead of an image ID) to include first, before any featured, attached, or content images.', 'wpsso' ) . ' ';

					$text .= __( 'Make sure your custom image is large enough or it may be ignored by social website(s).', 'wpsso' ) . ' ';

					$text .= $this->fb_prefs . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image ID is selected.', 'wpsso' ) . '</em>';

					break;

				case 'tooltip-meta-og_vid_dimensions':	// Video Dimensions.

					$text = sprintf( __( 'The %s video API modules can offer default video width and height values, provided that information is available from the service API.', 'wpsso' ), $this->p_name_pro ) . ' ';

					$text .= __( 'If the default video width and/or height values are incorrect, you may adjust their values here.', 'wpsso' );

				 	break;

				case 'tooltip-meta-og_vid_embed':	// Video Embed HTML.

					$text = __( 'Custom video embed HTML for the first video in the Facebook / Open Graph and Twitter Card meta tags, and in the Schema JSON-LD markup.', 'wpsso' ) . ' ';

					$text .= __( 'If the video is from a recognized external video service, an API connection will be made to retrieve additional information about the video.', 'wpsso' );

				 	break;

				case 'tooltip-meta-og_vid_url':		// or a Video URL.

					$text = __( 'A customized video URL for the first video in the Facebook / Open Graph and Twitter Card meta tags, and in the Schema JSON-LD markup.', 'wpsso' ) . ' ';

					$text .= __( 'If the video is from a recognized external video service, an API connection will be made to retrieve additional information about the video.', 'wpsso' );

				 	break;

				case 'tooltip-meta-og_vid_title':	// Video Name / Title.
				case 'tooltip-meta-og_vid_desc':	// Video Description.

					$text = sprintf( __( 'The %s video API modules can offer a default video name / title and description, provided that information is available from the service API.', 'wpsso' ), $this->p_name_pro ) . ' ';

					$text .= __( 'The video name / title and description will be used in the video Schema JSON-LD markup (add-on required).', 'wpsso' );

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta_og', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-meta-og' switch.

			return $text;
		}
	}
}
