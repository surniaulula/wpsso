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

if ( ! class_exists( 'WpssoMessagesTooltipOpenGraph' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltip->get() only when needed.
	 */
	class WpssoMessagesTooltipOpenGraph extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$text = '';

			switch ( $msg_key ) {

				/**
				 * SSO > General Settings > Site Information tab.
				 */
				case ( 0 === strpos( $msg_key, 'tooltip-og_def_article_' ) ? true : false ):

					$def_frags = $this->get_tooltip_fragments( preg_replace( '/^tooltip-og_def_/', '', $msg_key ) );	// Uses a local cache.

					if ( ! empty( $def_frags ) ) {	// Just in case.

						$text = sprintf( __( 'The %s that best describes the content of articles on your site.', 'wpsso' ),
							$def_frags[ 'name' ] ) . ' ';

						$text .= sprintf( __( 'You can select a different %s when editing an article.', 'wpsso' ),
							$def_frags[ 'name' ] ) . ' ';
	
						$text .= sprintf( __( 'Select "[None]" to exclude the %s by default from Open Graph meta tags.', 'wpsso' ),
							$def_frags[ 'name' ] ) . ' ';
					}
	
					break;

				case 'tooltip-og_def_currency':		// Default Currency.

					$text = __( 'The default currency for money related options (product price, job salary, etc.).', 'wpsso' );

					break;

				case 'tooltip-og_def_country':		// Default Country.

					$text = __( 'The default country when entering information about a place or location.', 'wpsso' );

					break;

				case 'tooltip-og_def_timezone':		// Default Timezone.

					$text = __( 'The default timezone when entering information about a place or location.', 'wpsso' );

					break;

				/**
				 * SSO > General Settings > Titles and Descriptions tab.
				 */
				case 'tooltip-og_title_sep':		// Title Separator.

					$def_title_sep = $this->p->opt->get_defaults( 'og_title_sep' );

					$text = sprintf( __( 'One or more characters used to separate values (category parent names, page numbers, site name, etc.) within a title string (default is a hyphen "%s").', 'wpsso' ), $def_title_sep );

					break;

				case 'tooltip-og_ellipsis':		// Truncated Text Ellipsis.

					$def_title_sep = $this->p->opt->get_defaults( 'og_ellipsis' );

					$text = sprintf( __( 'One or more characters used to suffix a truncated (ie. shortened) text string (default is three dots "%s").', 'wpsso' ), $def_title_sep );

					break;

				case 'tooltip-og_desc_hashtags':	// Description Hashtags.

					$text = __( 'The maximum number of WordPress tag names (automatically converted to hashtags) to include in the Facebook / Open Graph and Twitter Card descriptions.', 'wpsso' ) . ' ';

					$text .= __( 'Select "0" to disable the addition of hashtags.', 'wpsso' );

					break;

				/**
				 * SSO > General Settings > Images tab.
				 */
				case 'tooltip-og_img_max':		// Maximum Images to Include.

					$text = __( 'The maximum number of images to include for the webpage meta tags and Schema markup.', 'wpsso' ) . ' ';

					$text .= __( 'If you select "0", then no images will be included (not recommended).', 'wpsso' ) . ' ';

					$text .= __( 'If no images are available in the Open Graph meta tags, social sites may choose any random image from the webpage, including headers, thumbnails, ads, etc.', 'wpsso' );

					break;

				case 'tooltip-og_img_size':		// Open Graph (Facebook and oEmbed) Image Size.

					$def_img_dims = $this->get_def_img_dims( 'og' );

					$text = sprintf( __( 'The dimensions used for the Facebook / Open Graph and oEmbed images (default dimensions are %s).', 'wpsso' ), $def_img_dims ) . ' ';

					$text .= $this->fb_prefs_transl;

					break;

				case 'tooltip-og_def_img_id':		// Default Image ID.

					$text = __( 'An image ID for your site\'s default image (ie. when an image is required, and no other image is available).', 'wpsso' ) . ' ';

					$text .= __( 'The default image is used for archive pages (ie. blog, category, and tag archive page) and a fallback for public posts and pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';

					break;

				case 'tooltip-og_def_img_url':		// or Default Image URL.

					$limit_min_width  = $this->p->cf[ 'head' ][ 'limit_min' ][ 'og_img_width' ];
					$limit_min_height = $this->p->cf[ 'head' ][ 'limit_min' ][ 'og_img_height' ];

					$text = __( 'You can enter a default image URL instead of choosing an image ID.', 'wpsso' ) . ' ';

					$text .= __( 'The image URL option allows you to choose an image outside of the WordPress Media Library and/or a smaller logo style image.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The image should be at least %s or more in width and height.', 'wpsso' ), $limit_min_width . 'x' . $limit_min_height . 'px' ) . ' ';

					$text .= __( 'The default image is used for archive pages (ie. blog, category, and tag archive page) and a fallback for public posts and pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' ) . ' ';

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

					// translators: %s is the markup standard name (ie. Open Graph or Schema).
					$text = sprintf( __( 'Select a default %s type for a static front page.', 'wpsso' ), 'Open Graph' ) . ' ';

					// translators: %1$s is the markup standard name (ie. Open Graph or Schema) and %2$s is the type name.
					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_home_posts':	// Type for Posts Homepage.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_home_posts' );

					// translators: %s is the markup standard name (ie. Open Graph or Schema).
					$text = sprintf( __( 'Select a default %s type for a blog (non-static) front page.', 'wpsso' ), 'Open Graph' ) . ' ';

					// translators: %1$s is the markup standard name (ie. Open Graph or Schema) and %2$s is the type name.
					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_user_page':	// Type for User / Author.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_user_page' );

					// translators: %s is the markup standard name (ie. Open Graph or Schema).
					$text = sprintf( __( 'Select a default %s type for user / author profile pages.', 'wpsso' ), 'Open Graph' ) . ' ';

					// translators: %1$s is the markup standard name (ie. Open Graph or Schema) and %2$s is the type name.
					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_search_page':	// Type for Search Results.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_search_page' );

					// translators: %s is the markup standard name (ie. Open Graph or Schema).
					$text = sprintf( __( 'Select a default %s type for search results pages.', 'wpsso' ), 'Open Graph' ) . ' ';

					// translators: %1$s is the markup standard name (ie. Open Graph or Schema) and %2$s is the type name.
					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_archive_page':	// Type for Archive Page.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_archive_page' );

					// translators: %s is the markup standard name (ie. Open Graph or Schema).
					$text = sprintf( __( 'Select a default %s type for other archive pages (date-based archive pages, for example).', 'wpsso' ), 'Open Graph' ) . ' ';

					// translators: %1$s is the markup standard name (ie. Open Graph or Schema) and %2$s is the type name.
					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_pt':		// Type by Post Type.

					// translators: %s is the markup standard name (ie. Open Graph or Schema).
					$text = sprintf( __( 'Select a default %s type for each post type.', 'wpsso' ), 'Open Graph' ) . ' ';

					$text .= __( 'Please note that each Open Graph type has a unique set of meta tags, so by selecting "website" here (for example), you would be excluding all "article" related meta tags (<code>article:author</code>, <code>article:section</code>, etc.).', 'wpsso' );

					break;

				case 'tooltip-og_type_for_pta':		// Type by Post Type Archive.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_archive_page' );

					// translators: %s is the markup standard name (ie. Open Graph or Schema).
					$text = sprintf( __( 'Select a default %s type for each post type archive.', 'wpsso' ), 'Open Graph' ) . ' ';

					// translators: %1$s is the markup standard name (ie. Open Graph or Schema) and %2$s is the type name.
					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				case 'tooltip-og_type_for_tax':		// Type by Taxonomy.

					$def_type = $this->p->opt->get_defaults( 'og_type_for_archive_page' );

					// translators: %s is the markup standard name (ie. Open Graph or Schema).
					$text = sprintf( __( 'Select a default %s type for each taxonomy.', 'wpsso' ), 'Open Graph' ) . ' ';

					// translators: %1$s is the markup standard name (ie. Open Graph or Schema) and %2$s is the type name.
					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_og', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-og' switch.

			return $text;
		}
	}
}
