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

if ( ! class_exists( 'WpssoMessagesTooltipOpenGraph' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltip->get() only when needed.
	 */
	class WpssoMessagesTooltipOpenGraph extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				/**
				 * SSO > General Settings > Site Information tab.
				 */
				case 'tooltip-og_def_article_section':	// Default Article Section.

					$text = __( 'The section that best describes the content of articles on your site.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'Your selection will be used by default for the Facebook %s meta tag value (you can also select a custom section when editing an article).', 'wpsso' ), '<code>article:section</code>' ) . ' ';

					$text .= sprintf( __( 'Select "[None]" to exclude the %s meta tag by default.', 'wpsso' ), '<code>article:section</code>' );

					break;

				case 'tooltip-og_def_product_category':	// Default Product Type.

					$text = __( 'The Google product type that best describes the products on your site.', 'wpsso' ) . ' ';

					/**
					 * See https://developers.facebook.com/docs/marketing-api/catalog/reference/.
					 */
					$text .= sprintf( __( 'Your selection will be used by default for Schema product markup and the %s meta tag.',
						'wpsso' ), '<code>product:category</code>' ) . ' ';

					$text .= __( 'Select "[None]" if you prefer to exclude the product type from Schema markup and meta tags by default (you can still select a custom product type when editing a product).', 'wpsso' );

					break;

				case 'tooltip-og_def_country':		// Default Country.

					$text = __( 'The default country when entering information about a place or location.', 'wpsso' );

					break;

				case 'tooltip-og_def_timezone':		// Default Timezone.

					$text = __( 'The default timezone when entering information about a place or location.', 'wpsso' );

					break;

				case 'tooltip-og_def_currency':		// Default Currency.

					$text = __( 'The default currency for money related options (product price, job salary, etc.).', 'wpsso' );

					break;

				/**
				 * SSO > General Settings > Titles / Descriptions tab.
				 */
				case 'tooltip-og_title_sep':		// Title Separator.

					$text = sprintf( __( 'One or more characters used to separate values (category parent names, page numbers, etc.) within the Facebook / Open Graph title string (the default is a hyphen "%s" character).', 'wpsso' ), $this->p->opt->get_defaults( 'og_title_sep' ) );

					break;

				case 'tooltip-og_title_max_len':	// Title Max. Length.

					$text = sprintf( __( 'The maximum length for the Facebook / Open Graph title value (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'og_title_max_len' ) );

					break;

				case 'tooltip-og_desc_max_len':		// Description Max. Length.

					$text = sprintf( __( 'The maximum length for the Facebook / Open Graph description value (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'og_desc_max_len' ) ) . ' ';

					$text .= sprintf( __( 'The maximum length must be at least %d characters or more.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ 'og_desc_len' ] );

					break;

				case 'tooltip-og_desc_hashtags':	// Description Hashtags.

					$text = __( 'The maximum number of tag names (converted to hashtags) to include in the Facebook / Open Graph description.', 'wpsso' ) . ' ';

					$text .= __( 'Each tag name is converted to lowercase with whitespaces removed.', 'wpsso' ) . ' ';

					$text .= __( 'Select "0" to disable the addition of hashtags.', 'wpsso' );

					break;

				/**
				 * SSO > General Settings > Images tab.
				 */
				case 'tooltip-og_img_max':		// Maximum Images to Include.

					$text = __( 'The maximum number of images to include in the Open Graph meta tags for the webpage.', 'wpsso' ) . ' ';

					$text .= __( 'If you select "0", then no images will be included (not recommended).', 'wpsso' ) . ' ';

					$text .= __( 'If no images are available in the Open Graph meta tags, social sites may choose any random image from the webpage, including headers, thumbnails, ads, etc.', 'wpsso' );

					break;

				case 'tooltip-og_img_size':		// Open Graph (Facebook and oEmbed) Image Size.

					$def_img_dims = $this->get_def_img_dims( 'og' );

					$text = sprintf( __( 'The image dimensions used for Facebook / Open Graph meta tags and oEmbed markup (the default dimensions are %s).', 'wpsso' ), $def_img_dims ) . ' ';

					$text .= $this->fb_prefs;

					break;

				case 'tooltip-og_def_img_id':		// Default Image ID.

					$text = __( 'An image ID for your site\'s default image (ie. when an image is required, and no other image is available).', 'wpsso' ) . ' ';

					$text .= __( 'The default image is used for archive pages (ie. blog, category, and tag archive page) and as a fallback for posts and pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';

					break;

				case 'tooltip-og_def_img_url':		// or Default Image URL.

					$text = __( 'You can enter a default image URL instead of choosing an image ID.', 'wpsso' ) . ' ';

					$text .= __( 'The image URL option allows you to use an image outside of a managed collection (WordPress Media Library or NextGEN Gallery), and/or a smaller logo style image.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The image should be at least %s or more in width and height.', 'wpsso' ),
						$this->p->cf[ 'head' ][ 'limit_min' ][ 'og_img_width' ] . 'x' .
							$this->p->cf[ 'head' ][ 'limit_min' ][ 'og_img_height' ] . 'px' ) . ' ';

					$text .= __( 'The default image is used for archive pages (ie. blog, category, and tag archive page) and as a fallback for posts and pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image ID is selected.', 'wpsso' ) . '</em>';

					break;

				/**
				 * SSO > General Settings > Videos tab.
				 */
				case 'tooltip-og_vid_max':		// Maximum Videos to Include.

					$text = __( 'The maximum number of embedded videos to include in meta tags and Schema markup.', 'wpsso' );

					break;

				case 'tooltip-og_vid_prev_img':		// Include Video Preview Images.

					$text = __( 'Include video preview images in meta tags and Schema markup.', 'wpsso' ) . ' ';

					$text .= __( 'When video preview images are enabled and a preview image is available, it will be included in meta tags and Schema markup before any other image (custom, featured, attached, or content image).', 'wpsso' );

					break;

				case 'tooltip-og_vid_autoplay':		// Force Autoplay when Possible.

					$text = __( 'If possible, add or modify the video URL "autoplay" argument for videos in meta tags and Schema markup.', 'wpsso' );

					break;

				/**
				 * SSO > Advanced Settings > Document Types > Open Graph tab.
				 */
				case 'tooltip-og_type_for_home_page':	// Type for Page Homepage.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_home_page' );

					$text = sprintf( __( 'Select the %s type for a static front page.', 'wpsso' ), 'Open Graph' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_home_posts':	// Type for Posts Homepage.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_home_posts' );

					$text = sprintf( __( 'Select the %s type for a blog (non-static) front page.', 'wpsso' ), 'Open Graph' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_user_page':	// Type for User / Author.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_user_page' );

					$text = sprintf( __( 'Select the %s type for user / author pages.', 'wpsso' ), 'Open Graph' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_search_page':	// Type for Search Results.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_search_page' );

					$text = sprintf( __( 'Select the %s type for search results pages.', 'wpsso' ), 'Open Graph' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_archive_page':	// Type for Other Archive.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_archive_page' );

					$text = sprintf( __( 'Select the %s type for other archive pages (example: date-based archive pages).', 'wpsso' ), 'Open Graph' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_ptn':		// Type by Post Type.

					$text = sprintf( __( 'Select the %s type for each WordPress post type.', 'wpsso' ), 'Open Graph' ) . ' ';

					$text .= __( 'Please note that each Open Graph type has a unique set of meta tags, so by selecting "website" here (for example), you would be excluding all "article" related meta tags (<code>article:author</code>, <code>article:section</code>, etc.).', 'wpsso' );

					break;

				case 'tooltip-og_type_for_ttn':		// Type by Taxonomy.

					$text = __( 'Select the Open Graph type for each WordPress taxonomy.', 'wpsso' );

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_og', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-og' switch.

			return $text;
		}
	}
}
