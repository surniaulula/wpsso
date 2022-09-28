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

if ( ! class_exists( 'WpssoMessagesTooltipMetaOpenGraph' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltipMeta->get() only when needed.
	 */
	class WpssoMessagesTooltipMetaOpenGraph extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

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

				case 'tooltip-meta-og_title':	// Social Title.

					$text = sprintf( __( 'A customized title for the Facebook / Open Graph %s meta tag.', 'wpsso' ), '<code>og:title</code>' ) . ' ';

					$text .= __( 'The default value is inherited from the SEO title.', 'wpsso' ) . ' ';

				 	break;

				case 'tooltip-meta-og_desc':	// Social Description.

					$text = sprintf( __( 'A customized description for the Facebook / Open Graph %s meta tag.', 'wpsso' ), '<code>og:description</code>' ) . ' ';

					$text .= __( 'The default value is inherited from the SEO description.', 'wpsso' ) . ' ';

				 	break;

				case 'tooltip-meta-og_img_crop_area':	// Crop Area.

					$text = __( 'Select the preferred cropping (ie. main subject) area of the image.', 'wpsso' );

				 	break;

				case 'tooltip-meta-og_img_id':	// Image ID.

					$text = __( 'A customized image ID to include first, before any featured, attached, or content images.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';

				 	break;

				case 'tooltip-meta-og_img_url':	// or an Image URL.

					$text = __( 'A customized image URL (instead of an image ID) to include first, before any featured, attached, or content images.', 'wpsso' ) . ' ';

					$text .= __( 'Make sure your custom image is large enough or it may be ignored by social website(s).', 'wpsso' ) . ' ';

					$text .= $this->fb_prefs_transl . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image ID is selected.', 'wpsso' ) . '</em>';

					break;

				case 'tooltip-meta-og_vid_embed':	// Video Embed HTML.

					$text = __( 'Video embed HTML code (ie. figure, iframe, or embed HTML code) for the first video included in the meta tags and Schema JSON-LD markup.', 'wpsso' ) . ' ';

					$text .= __( 'If the video is from a recognized external video service, an API connection will be made to retrieve additional information about the video.', 'wpsso' );

				 	break;

				case 'tooltip-meta-og_vid_url':		// or a Video URL.

					$text = __( 'A video URL for the first video included in the meta tags and Schema JSON-LD markup.', 'wpsso' ) . ' ';

					$text .= __( 'If the video is from a recognized external video service, an API connection will be made to retrieve additional information about the video.', 'wpsso' );

				 	break;

				case 'tooltip-meta-og_vid_title':	// Video Name.
				case 'tooltip-meta-og_vid_desc':	// Video Description.
				case 'tooltip-meta-og_vid_dimensions':	// Video Dimensions.

					$text = sprintf( __( 'The %s video APIs can include additional information about a video (ie. name / title, description, dimensions, stream URL, etc.), provided that this information is available from the video service API, or the information has been entered here manually.', 'wpsso' ), $this->p_name_pro ) . ' ';

					break;

				case 'tooltip-meta-og_vid_stream_url':	// Video Stream URL.

					$text = $this->get( 'tooltip-meta-og_vid_title' ) . ' ';

					$text .= __( 'The stream URL should be a publicly available URL to the video file (mkv, mp4, etc.) or video data stream (not an HTML webpage).', 'wpsso' ) . ' ';

					$text .= __( 'The stream URL will be used for the Schema contentUrl property value in the Schema VideoObject markup.', 'wpsso' ) . ' ';

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta_og', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-meta-og' switch.

			return $text;
		}
	}
}
