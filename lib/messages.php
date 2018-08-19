<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoMessages' ) ) {

	class WpssoMessages {

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public function get( $idx = false, $info = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'idx'  => $idx,
					'info' => $info,
				) );
			}

			if ( is_string( $info ) ) {
				$text = $info;
				$info = array( 'text' => $text );
			} else {
				$text = isset( $info['text'] ) ? $info['text'] : '';
			}

			$idx = sanitize_title_with_dashes( $idx );

			/**
			 * Example lcas: wpsso, wpssojson, wpssoum, etc.
			 */
			$info['lca'] = $lca = isset( $info['lca'] ) ?
				$info['lca'] : $this->p->cf['lca'];

			/**
			 * An array of plugin urls (download, purchase, etc.).
			 */
			$url = isset( $this->p->cf['plugin'][$lca]['url'] ) ?
				$this->p->cf['plugin'][$lca]['url'] : array();

			if ( ! empty( $url['purchase'] ) ) {
				$url['purchase'] = add_query_arg( 'utm_source', $idx, $url['purchase'] );
			} else {
				$url['purchase'] = '';
			}

			foreach ( array( 'short', 'name', 'version' ) as $key ) {

				if ( ! isset( $info[$key] ) ) {
					if ( ! isset( $this->p->cf['plugin'][$lca][$key] ) ) {	// Just in case.
						$info[$key] = null;
					} else {
						$info[$key] = $this->p->cf['plugin'][$lca][$key];
					}
				}

				$info[$key.'_pro'] = SucomUtil::get_pkg_name( $info[$key], 'Pro' );

				$info[$key.'_pro_purchase'] = empty( $url['purchase'] ) ?
					$info[$key.'_pro'] : '<a href="'.$url['purchase'].'">'.$info[$key.'_pro'].'</a>';
			}

			$fb_recommends_transl = __( 'Facebook has published a preference for Open Graph image dimensions of 1200x630px cropped (for retina and high-PPI displays), 600x315px cropped as a minimum (the default settings value), and ignores images smaller than 200x200px.', 'wpsso' );

			/**
			 * All tooltips
			 */
			if ( strpos( $idx, 'tooltip-' ) === 0 ) {

				if ( strpos( $idx, 'tooltip-meta-' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-meta-sharing_url':

							$text = __( 'A custom sharing URL used for the Facebook / Open Graph and Pinterest Rich Pin meta tags, Schema markup, and any social sharing add-ons.', 'wpsso' ).' '.__( 'Please make sure the custom URL you enter here is functional and redirects correctly.', 'wpsso' );

						 	break;

						case 'tooltip-meta-canonical_url':

							$text = sprintf( __( 'A custom URL used for the "%1$s" head tag.', 'wpsso' ), 'link rel canonical' ).' '.__( 'Please make sure the custom URL you enter here is functional and redirects correctly.', 'wpsso' );

						 	break;

						case 'tooltip-meta-schema_title':

							$text = __( 'A custom name / title for the Schema item type\'s name property.', 'wpsso' );

						 	break;

						case 'tooltip-meta-schema_title_alt':

							$text = __( 'An optional alternate custom name / title for the Schema item type\'s alternateName property.', 'wpsso' );

						 	break;

						case 'tooltip-meta-schema_desc':

							$text = __( 'A custom description for the Schema item type\'s description property.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_title':

							$settings_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content',
								_x( 'Use Filtered (SEO) Title', 'option label', 'wpsso' ) );

							$text = __( 'A custom title for the Facebook / Open Graph, Pinterest Rich Pin, and Twitter Card meta tags (all Twitter Card formats).', 'wpsso' ) . ' ';
							// translators: %s is a link to the (translated) "Use Filtered (SEO) Title" option settings page
							$text .= sprintf( __( 'If the %s option is enabled, the default title value may be provided by your theme or another SEO plugin.', 'wpsso' ), $settings_page_link );

						 	break;

						case 'tooltip-meta-og_desc':

							$text = sprintf( __( 'A custom description for the Facebook / Open Graph %s meta tag, and the default value for all other description meta tags.', 'wpsso' ), '<code>og:description</code>' ).' ';
							
							$text .= __( 'The default description value is based on the category / tag description or biographical info for users.', 'wpsso' ).' ';
							
							$text .= __( 'Update and save the custom Facebook / Open Graph description to change the default value of all other description fields.', 'wpsso' );

						 	break;

						case 'tooltip-meta-seo_desc':

							$text = __( 'A custom description for the Google Search "description" meta tag.', 'wpsso' );

						 	break;

						case 'tooltip-meta-tc_desc':

							$text = __( 'A custom description for the Twitter Card description meta tag (all Twitter Card formats).', 'wpsso' );

						 	break;

						case 'tooltip-meta-product_avail':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'availability', 'product meta name', 'wpsso' );
							}
							// no break - fall through

						case 'tooltip-meta-product_brand':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'brand', 'product meta name', 'wpsso' );
							}
							// no break - fall through

						case 'tooltip-meta-product_color':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'color', 'product meta name', 'wpsso' );
							}
							// no break - fall through

						case 'tooltip-meta-product_condition':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'condition', 'product meta name', 'wpsso' );
							}
							// no break - fall through

						case 'tooltip-meta-product_currency':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'currency', 'product meta name', 'wpsso' );
							}
							// no break - fall through

						case 'tooltip-meta-product_material':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'material', 'product meta name', 'wpsso' );
							}
							// no break - fall through

						case 'tooltip-meta-product_price':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'price', 'product meta name', 'wpsso' );
							}
							// no break - fall through

						case 'tooltip-meta-product_size':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'size', 'product meta name', 'wpsso' );
							}
							// no break - fall through

						case 'tooltip-meta-product_gender':

							if ( ! isset( $product_meta_name ) ) {
								$product_meta_name = _x( 'target gender', 'product meta name', 'wpsso' );
							}
							// no break - fall through

							$text = sprintf( __( 'You may select a custom %1$s for your product, or leave the default value as-is.', 'wpsso' ), $product_meta_name ).' ';

							// translators: %1$s is the product meta name - the first letter of this sentence is capitalized automatically.
							$text .= ucfirst( sprintf( __( 'The product %1$s may be used in Open Graph product meta tags and Schema markup for products with a single variation.', 'wpsso' ), $product_meta_name ) ).' ';

							$text .= sprintf( __( 'The Schema markup for products with multiple variations will include all product variations with the specific %1$s of each variation.', 'wpsso' ), $product_meta_name );

						 	break;	// stop here

						case 'tooltip-meta-og_img_max':

							$text = __( 'The maximum number of images to include in the Facebook / Open Graph meta tags.', 'wpsso' ).' ';
							
						 	break;

						case 'tooltip-meta-og_img_id':

							$text = __( 'A custom image ID to include first, before any featured, attached, or content images.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_img_url':

							$text = __( 'A custom image URL (instead of an image ID) to include first, before any featured, attached, or content images.', 'wpsso' ).' ';
							
							$text .= __( 'Please make sure your custom image is large enough, or it may be ignored by social website(s).', 'wpsso' ).' ';
							
							$text .= $fb_recommends_transl.' ';
							
							$text .= '<em>' . __( 'This field is disabled if a custom image ID has been selected.', 'wpsso' ) . '</em>';

							break;

						case 'tooltip-meta-og_vid_prev_img':

							$prev_option_link = $this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_videos',
								_x( 'Include Video Preview Images', 'option label', 'wpsso' ) );

							$text = sprintf( __( 'When the %s option is enabled, and a preview image is available, it will be included in the Facebook / Open Graph meta tags before any other image (custom, featured, attached, etc.).', 'wpsso' ), $prev_option_link );

						 	break;

						case 'tooltip-meta-og_vid_max':

							$text = __( 'The maximum number of embedded videos to include in the Facebook / Open Graph meta tags and Schema markup.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_vid_dimensions':

							$text = sprintf( __( 'The %1$s video API modules can offer default video width and height values, provided that information is available from the API service.', 'wpsso' ), $info['short_pro'] ).' ';

							$text .= __( 'If the default video width and/or height values are incorrect, you may adjust their values here.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_vid_embed':

							$text = __( 'Custom video embed HTML for the first video in the Facebook / Open Graph and Twitter Card meta tags, and in the Schema JSON-LD markup.', 'wpsso' ).' ';
							
							$text .= __( 'If the video is from a recognized external video service, an API connection will be made to retrieve additional information about the video.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_vid_url':

							$text = __( 'A custom video URL for the first video in the Facebook / Open Graph and Twitter Card meta tags, and in the Schema JSON-LD markup.', 'wpsso' ).' ';
							
							$text .= __( 'If the video is from a recognized external video service, an API connection will be made to retrieve additional information about the video.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_vid_title':
						case 'tooltip-meta-og_vid_desc':

							$text = sprintf( __( 'The %1$s video API modules can offer a default video name / title and description, provided that information is available from the API service.', 'wpsso' ), $info['short_pro'] ).' ';

							$text .= __( 'The video name / title and description will be used in the video Schema JSON-LD markup (add-on required).', 'wpsso' );

							break;

						case 'tooltip-meta-schema_img_max':

							$text = __( 'The maximum number of images to include in the Schema meta tags and JSON-LD markup.', 'wpsso' );

						 	break;

						case 'tooltip-meta-schema_img_id':

							$text = __( 'A custom image ID to include first in the Google / Schema meta tags and JSON-LD markup, before any featured, attached, or content images.', 'wpsso' );

						 	break;

						case 'tooltip-meta-schema_img_url':

							$text = __( 'A custom image URL (instead of an image ID) to include first in the Google / Schema meta tags and JSON-LD markup.', 'wpsso' ).' <em>'.__( 'This field is disabled if a custom image ID has been selected.', 'wpsso' ).'</em>';

						 	break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_meta', $text, $idx, $info );

							break;

					}	// end of tooltip-user switch

				/**
				 * Post Meta settings
				 */
				} elseif ( strpos( $idx, 'tooltip-post-' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-post-og_type':

							$text = __( 'A custom Facebook / Open Graph type for this content.', 'wpsso' ).' ';

							$text .= __( 'Please note that for sharing purposes, the Open Graph Type must be "article", "place", "product", or "website".', 'wpsso' ).' ';

						 	break;

						case 'tooltip-post-og_art_section':

							$text = __( 'A custom topic for this article, which may be different from the default Article Topic selected in the General settings page.', 'wpsso' ).' ';

							$text .= sprintf( __( 'Select "[None]" if you prefer to exclude the %s meta tag.', 'wpsso' ), '<code>article:section</code>' );

						 	break;

						case 'tooltip-post-og_desc':

							$text = sprintf( __( 'A custom description for the Facebook / Open Graph %s meta tag, and the default value for all other description meta tags.', 'wpsso' ), '<code>og:description</code>' ).' ';

							$text .= __( 'The default description value is based on the excerpt (if one is available) or content.', 'wpsso' ).' ';

							$text .= __( 'Update and save the custom Facebook / Open Graph description to change the default value of all other description fields.', 'wpsso' );

						 	break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_post', $text, $idx, $info );

							break;

					}	// end of tooltip-post switch

				/**
				 * Site settings
				 */
				} elseif ( strpos( $idx, 'tooltip-site_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-site_name':

							$settings_page_url = get_admin_url( null, 'options-general.php' );

							$text = sprintf( __( 'The WordPress Site Name is used for the Facebook / Open Graph and Pinterest Rich Pin %1$s meta tag. You may override <a href="%2$s">the default WordPress Site Title value</a>.', 'wpsso' ), '<code>og:site_name</code>', $settings_page_url );

							break;

						case 'tooltip-site_name_alt':

							$text = __( 'An optional alternate name for your WebSite that you want Google to consider.', 'wpsso' );

							break;

						case 'tooltip-site_desc':

							$settings_page_url = get_admin_url( null, 'options-general.php' );

							$text = sprintf( __( 'The WordPress tagline is used as a description for the blog (non-static) front page, and as a fallback for the Facebook / Open Graph and Pinterest Rich Pin %1$s meta tag.', 'wpsso' ), '<code>og:description</code>' ).' '.sprintf( __( 'You may override <a href="%1$s">the default WordPress Tagline value</a> here, to provide a longer and more complete description of your website.', 'wpsso' ), $settings_page_url );

							break;

						case 'tooltip-site_org_type':

							$text = __( 'You may select a more descriptive Schema type from the Organization sub-types (default is Organization).', 'wpsso' );
							break;

						case 'tooltip-site_place_id':

							if ( isset( $this->p->cf['plugin']['wpssoplm'] ) ) {

								$plm_info = $this->p->cf['plugin']['wpssoplm'];

								$text = sprintf( __( 'Select an optional Place / Location address for this Organization (requires the %s add-on).', 'wpsso' ), '<a href="'.$plm_info['url']['home'].'">'.$plm_info['name'].'</a>' );
							}

							break;
					}

				/**
				 * Open Graph settings
				 */
				} elseif ( strpos( $idx, 'tooltip-og_' ) === 0 ) {

					switch ( $idx ) {

						/**
						 * Site Information tab.
						 */
						case 'tooltip-og_art_section':	// Default Article Topic

							$text = __( 'The topic that best describes the Posts and Pages on your website.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'This value will be used in the %s Facebook / Open Graph and Pinterest Rich Pin meta tags.', 'wpsso' ), '<code>article:section</code>' ).' ';
							
							$text .= sprintf( __( 'Select "[None]" if you prefer to exclude the %s meta tag.', 'wpsso' ), '<code>article:section</code>' ).' ';
							
							$text .= __( 'The Pro version also allows you to select a custom Topic for each individual Post and Page.', 'wpsso' );

							break;

						case 'tooltip-og_type_for_home_index':	// Type for Blog Front Page

							$def_type = $this->p->opt->get_defaults( 'og_type_for_home_index' );

							$text = sprintf( __( 'Select the %1$s type for a blog (non-static) front page.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_home_page':	// Type for Static Front Page

							$def_type = $this->p->opt->get_defaults( 'og_type_for_home_page' );

							$text = sprintf( __( 'Select the %1$s type for a static front page.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_user_page':	// Type for User / Author Page

							$def_type = $this->p->opt->get_defaults( 'og_type_for_user_page' );

							$text = sprintf( __( 'Select the %1$s type for user / author pages.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_search_page':	// Type for Search Results Page

							$def_type = $this->p->opt->get_defaults( 'og_type_for_search_page' );

							$text = sprintf( __( 'Select the %1$s type for search results pages.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_archive_page':	// Type for Other Archive Page

							$def_type = $this->p->opt->get_defaults( 'og_type_for_archive_page' );

							$text = sprintf( __( 'Select the %1$s type for other archive pages (example: date-based archive pages).', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_ptn':	// Type by Post Type

							$text = sprintf( __( 'Select the %1$s type for each WordPress post type.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= __( 'Please note that each Open Graph type has a unique set of meta tags, so by selecting "website" here (for example), you would be excluding all "article" related meta tags (<code>article:author</code>, <code>article:section</code>, etc.).', 'wpsso' );

							break;

						case 'tooltip-og_type_for_ttn':	// Type by Term Taxonomy

							$text = sprintf( __( 'Select the %1$s type for each WordPress term taxonomy.', 'wpsso' ), 'Open Graph' );

							break;

						/**
						 * Titles / Descriptions tab.
						 */
						case 'tooltip-og_title_sep':	// Title Separator

							$text = sprintf( __( 'One or more characters used to separate values (category parent names, page numbers, etc.) within the Facebook / Open Graph title string (the default is the hyphen "%s" character).', 'wpsso' ), $this->p->opt->get_defaults( 'og_title_sep' ) );

							break;

						case 'tooltip-og_title_len':	// Maximum Title Length

							$text = sprintf( __( 'The maximum length of text used in the Facebook / Open Graph title tag (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'og_title_len' ) );

							break;

						case 'tooltip-og_desc_len':	// Maximum Description Length

							$text = __( 'The maximum length of text used in the Facebook / Open Graph description tag.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The length should be at least %1$d characters or more (the default is %2$d characters).',
								'wpsso' ), $this->p->cf['head']['limit_min']['og_desc_len'],
									$this->p->opt->get_defaults( 'og_desc_len' ) );

							break;

						case 'tooltip-og_desc_hashtags':	// Add Hashtags to Descriptions

							$text = __( 'The maximum number of tag names (converted to hashtags) to include in the Facebook / Open Graph description.', 'wpsso' ).' ';
							
							$text .= __( 'Each tag name is converted to lowercase with whitespaces removed.', 'wpsso' ).' ';
							
							$text .= __( 'Select "0" to disable the addition of hashtags.', 'wpsso' );

							break;

						/**
						 * Authorship tab.
						 */
						case 'tooltip-og_author_field':	// Author Profile URL Field

							$text = sprintf( __( 'Select the contact field to use from the author\'s WordPress profile page for the Facebook / Open Graph %s meta tag value.', 'wpsso' ), '<code>article:author</code>' ).' ';
							
							$text .= __( 'The suggested setting is the Facebook URL contact field (default value).', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'Select "[None]" if you prefer to exclude the %s meta tag and prevent Facebook from showing author attribution in shared links.', 'wpsso' ), '<code>article:author</code>' );

							break;

						case 'tooltip-og_author_gravatar':	// Gravatar is Default / Fallback Image

							$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

							$text = __( 'If no custom image has been defined for an author, fallback to using their Gravatar image in author related meta tags and Schema markup.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'A custom image can be selected for / by each author in their WordPress user profile %s metabox.', 'wpsso' ), $metabox_title );

							break;

						/**
						 * Images tab.
						 */
						case 'tooltip-og_img_max':	// Maximum Images to Include

							$text = __( 'The maximum number of images to include in the Facebook / Open Graph meta tags &mdash; this includes the <em>featured</em> image, <em>attached</em> images, and any images found in the content.', 'wpsso' ).' ';
							
							$text .= __( 'If you select "0", then no images will be included in the Facebook / Open Graph meta tags (<strong>not recommended</strong>).', 'wpsso' ).' ';
							
							$text .= __( 'If no images are available in your meta tags, social sites may choose any image from your webpage (including headers, sidebars, thumbnails, etc.).', 'wpsso' );

							break;

						case 'tooltip-og_img_dimensions':	// Open Graph Image Dimensions

							$def_dimensions = $this->p->opt->get_defaults( 'og_img_width' ).'x'.
								$this->p->opt->get_defaults( 'og_img_height' ).' '.
								( $this->p->opt->get_defaults( 'og_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = sprintf( __( 'The image dimensions used for the Facebook / Open Graph meta tags (the default dimensions are %s).', 'wpsso' ), $def_dimensions ).' ';
							$text .= $fb_recommends_transl.' ';
							
							$text .= __( 'Note that images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.', 'wpsso' );

							break;

						case 'tooltip-og_def_img_id':	// Default / Fallback Image ID

							$text = __( 'An image ID and media library selection for your default / fallback website image.', 'wpsso' ).' '.__( 'The default image is used for index / archive pages, and as a fallback for Posts and Pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' );

							break;

						case 'tooltip-og_def_img_url':	// or Default / Fallback Image URL

							$text = __( 'You can enter a default image URL (including the http:// prefix) instead of choosing an image ID &mdash; if a default image ID is specified, the image URL option is disabled.', 'wpsso' ).' ';
							
							$text .= '<strong>'.__( 'The image URL option allows you to use an image outside of a managed collection (WordPress Media Library or NextGEN Gallery), and/or a smaller logo style image.', 'wpsso' ).'</strong> ';
							
							$text .= sprintf( __( 'The image should be at least %s or more in width and height.', 'wpsso' ), $this->p->cf['head']['limit_min']['og_img_width'].'x'.$this->p->cf['head']['limit_min']['og_img_height'].'px' ).' ';
							
							$text .= __( 'The default image is used for index / archive pages, and as a fallback for Posts and Pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' );

							break;

						/**
						 * Videos tab.
						 */
						case 'tooltip-og_vid_max':	// Maximum Videos to Include

							$text = 'The maximum number of videos, found in the Post or Page content, to include in the Facebook / Open Graph and Pinterest Rich Pin meta tags. If you select "0", then no videos will be listed in the Facebook / Open Graph and Pinterest Rich Pin meta tags. There is no advantage in selecting a maximum value greater than 1.';

							break;

						case 'tooltip-og_vid_https':	// Use HTTPS for Video API Requests

							$text = 'Use an HTTPS connection whenever possible to retrieve information about videos from YouTube, Vimeo, Wistia, etc. (default is checked).';

							break;

						case 'tooltip-og_vid_prev_img':	// Include Video Preview Images

							$text = 'Include video preview images in the webpage meta tags (default is unchecked). When video preview images are enabled and available, they are included before any custom, featured, attached, etc. images.';

							break;

						case 'tooltip-og_vid_html_type':	// Include text/html Type Meta Tags

							$text = 'Include additional Open Graph meta tags for the embed video URL as a text/html video type (default is checked).';

							break;

						case 'tooltip-og_vid_autoplay':	// Force Autoplay when Possible

							$text = 'When possible, add or modify the "autoplay" argument of video URLs in webpage meta tags (default is checked).';

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_og', $text, $idx, $info );

							break;

					}	// end of tooltip-og switch


				/**
				 * Advanced plugin settings.
				 */
				} elseif ( strpos( $idx, 'tooltip-plugin_' ) === 0 ) {

					switch ( $idx ) {

						/**
						 * 'Plugin Settings' settings.
						 */
						case 'tooltip-plugin_preserve': // Preserve Settings on Uninstall.

							$text = sprintf( __( 'Check this option if you would like to preserve the %s settings when you <em>uninstall</em> the plugin (default is unchecked).', 'wpsso' ), $info['short'] );

							break;

						case 'tooltip-plugin_debug': // Add Hidden Debug Messages.

							$text = __( 'Add debugging messages as hidden HTML comments to back-end and front-end webpages (default is unchecked).', 'wpsso' );

							break;

						case 'tooltip-plugin_hide_pro': // Hide All Pro Settings.

							$text = __( 'Remove Pro version preview options from settings pages and metaboxes (default is unchecked).', 'wpsso' ).' ';

							$text .= sprintf( __( 'Please note that some metaboxes and tabs may be empty - showing only a "<em>%s</em>" message - after enabling this option.', 'wpsso' ), __( 'No options available.', 'wpsso' ) );

							break;

						case 'tooltip-plugin_show_opts': // Options to Show by Default.

							$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

							$text = sprintf( __( 'Select the set of options to display by default in %1$s settings pages and %2$s metabox.', 'wpsso' ), $info['short'], $metabox_title ).' ';
							
							$text .= __( 'The basic view shows only the most commonly used options, and includes a link to temporarily unhide all options.', 'wpsso' ).' ';
							
							$text .= __( 'Showing all available options by default could prove to be overwhelming for new users.', 'wpsso' );

							break;

						/**
						 * 'Content and Filters' settings.
						 */
						case 'tooltip-plugin_filter_title':

							$def_checked = $this->p->opt->get_defaults( 'plugin_filter_title' ) ?
								_x( 'checked', 'option value', 'wpsso' ) : _x( 'unchecked', 'option value', 'wpsso' );

							$text = __( 'The title values provided by WordPress may include modifications by themes and/or SEO plugins (appending the site name or expanding inline variables, for example, is a common practice).', 'wpsso' ).' ';

							$text .= sprintf( __( 'Uncheck this option to always use the original unmodified title value from WordPress (default is %s).', 'wpsso' ), $def_checked ).' ';

							$text .= sprintf( __( 'Advanced users can also hook the \'%s\' filter and return true / false to enable / disable this feature.', 'wpsso' ), $this->p->lca.'_filter_title' );

							break;

						case 'tooltip-plugin_filter_content':

							$text = __( 'Apply the WordPress \'the_content\' filter to the content text (default is unchecked). The content filter renders all registered shortcodes, which may be required to detect images and videos added by these shortcodes.', 'wpsso' ).' ';
							
							$text .= __( 'Some themes / plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ).' ';
							
							$text .= __( 'If you use shortcodes in your content text, this option should be enabled &mdash; if you experience display issues after enabling this option, determine which theme / plugin is filtering the content incorrectly and report the problem to its author(s).', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'Advanced users can also hook the \'%s\' filter and return true / false to enable / disable this feature.', 'wpsso' ), $this->p->lca.'_filter_content' );

							break;

						case 'tooltip-plugin_filter_excerpt':

							$text = __( 'Apply the WordPress \'get_the_excerpt\' filter to the excerpt text (default is unchecked). Enable this option if you use shortcodes in your excerpts, for example.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'Advanced users can also hook the \'%s\' filter and return true / false to enable / disable this feature.', 'wpsso' ), $this->p->lca.'_filter_excerpt' );

							break;

						case 'tooltip-plugin_p_strip':

							$text = sprintf( __( 'If a post, page, or custom post type does not have an excerpt, %s will use the content text to create a description value.', 'wpsso' ), $info['short'] ) . ' ';
							
							$text .= __( 'When this option is enabled, all text before the first paragraph tag in the content will be ignored.', 'wpsso' ) . ' ';
							
							$text .= __( 'The option is enabled by default since WordPress should provide correct paragraph tags in the content.', 'wpsso' );

							break;

						case 'tooltip-plugin_use_img_alt':

							$text = sprintf( __( 'If the content text is comprised entirely of HTML tags (which must be removed to create text-only descriptions), %s can extract and use image <em>alt</em> attributes instead of returning an empty description.', 'wpsso' ), $info['short'] );

							break;

						case 'tooltip-plugin_img_alt_prefix':

							$text = sprintf( __( 'When use of image <em>alt</em> attributes is enabled, %s can prefix the attribute text with an optional string.', 'wpsso' ), $info['short'] ).' '.__( 'Leave this value empty to prevent image alt attribute text from being prefixed.', 'wpsso' );

							break;

						case 'tooltip-plugin_p_cap_prefix':

							$text = sprintf( __( '%s can add a prefix to paragraphs found with the "wp-caption-text" class.', 'wpsso' ), $info['short'] ).' '.__( 'Leave this value empty to prevent caption paragraphs from being prefixed.', 'wpsso' );

							break;

						case 'tooltip-plugin_embed_media_apis':

							$text = __( 'Check the content for embedded media URLs from supported media providers (Vimeo, Wistia, YouTube, etc.). If a supported media URL is found, an API connection to the provider will be made to retrieve information about the media (preview image URL, flash player URL, oembed player URL, the video width / height, etc.).', 'wpsso' );

							break;

						/**
						 * 'Custom Meta' settings
						 */
						case 'tooltip-plugin_show_columns':

							$text = __( 'Additional columns can be included in admin list tables to show the Schema type ID, Open Graph image, etc.', 'wpsso' ) . ' ';
							
							$text .= __( 'When a column is enabled, <strong>each user can still hide that column</strong> by using the <em>Screen Options</em> tab on the list table page.', 'wpsso' );

							break;

						case 'tooltip-plugin_col_def_width':

							$text .= __( 'A default column width for the admin Posts and Pages list table.', 'wpsso' ) . ' ';

							$text .= __( 'All columns should have a width defined, but some 3rd party plugins do not provide width information for their columns.', 'wpsso' ) . ' ';

							$text .= __( 'This option offers a way to set a generic width for all Posts and Pages list table columns.', 'wpsso' ) . ' ';

							$text .= __( 'Leave this option blank to prevent setting a column width.', 'wpsso' );

							break;

						case 'tooltip-plugin_col_title_width':

							$text .= __( 'WordPress does not define a column width for its Title column, which can create display issues when showing list tables with additional columns.', 'wpsso' ) . ' ';

							$text .= __( 'This option allows you to define a custom width for the Title column, to prevent these kinds of issues.', 'wpsso' ) . ' ';

							$text .= __( 'Leave this option blank to prevent setting a column width.', 'wpsso' );

							break;

						case 'tooltip-plugin_add_to':

							$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

							$text = sprintf( __( 'Add or remove the %s metabox from admin editing pages for posts, pages, custom post types, terms (categories and tags), and user profile pages.', 'wpsso' ), $metabox_title );

							break;

						case 'tooltip-plugin_wpseo_social_meta':

							$text = __( 'Read the Yoast SEO custom social meta text for Posts, Terms, and Users.', 'wpsso' ).' ';
							
							$text .= __( 'This option is checked by default if the Yoast SEO plugin is active, or its settings are found in the database.', 'wpsso' );

							break;

						case 'tooltip-plugin_def_currency':

							$text = __( 'The default currency used for money related options (product price, job salary, etc.).', 'wpsso' );

							break;

						case 'tooltip-plugin_cf_img_url':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'an image URL', 'tooltip fragment', 'wpsso' ),
									_x( 'Image URL', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_vid_url':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a video URL (not HTML code)', 'tooltip fragment', 'wpsso' ),
									_x( 'Video URL', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_vid_embed':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'video embed HTML code (not a URL)', 'tooltip fragment', 'wpsso' ),
									_x( 'Video Embed HTML', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_addl_type_urls':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'additional microdata type URLs', 'tooltip fragment', 'wpsso' ),
									_x( 'Microdata Type URLs', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_recipe_ingredients':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'recipe ingredients', 'tooltip fragment', 'wpsso' ),
									_x( 'Recipe Ingredients', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_recipe_instructions':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'recipe instructions', 'tooltip fragment', 'wpsso' ),
									_x( 'Recipe Instructions', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_avail':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product availability', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Availability', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_brand':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product brand', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Brand', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_color':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product color', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Color', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_condition':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product condition', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Condition', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_currency':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product currency', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Currency', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_material':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product material', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Material', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_price':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product price', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Price', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_size':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product size', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Size', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_product_gender':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product target gender', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Target Gender', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

						case 'tooltip-plugin_cf_sameas_urls':

							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'additional Same-As URLs', 'tooltip fragment', 'wpsso' ),
									_x( 'Same-As URLs', 'option label', 'wpsso' ),
								);
							}

							// no break - fall through

							$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

							$text = sprintf( __( 'If your theme or another plugin provides a custom field for %1$s, you may enter its custom field name here.', 'wpsso' ), $plugin_cf_info[0] ).' ';
							
							$text .= sprintf( __( 'If a custom field matching that name is found, its value may be used for the "%1$s" option in the %2$s metabox.', 'wpsso' ), $plugin_cf_info[1], $metabox_title );

							break;	// stop here

						/**
						 * 'Integration' settings
						 */
						case 'tooltip-plugin_honor_force_ssl':	// Honor the FORCE_SSL Constant

							$text = sprintf( __( 'If the FORCE_SSL constant is defined as true, %s can redirect front-end URLs from HTTP to HTTPS when required (default is checked).', 'wpsso' ), $info['short'] );
							break;

						case 'tooltip-plugin_html_attr_filter':

							$func_name   = 'language_attributes()';
							$func_url    = __( 'https://developer.wordpress.org/reference/functions/language_attributes/', 'wpsso' );
							$filter_name = 'language_attributes';
							$html_tag    = '<code>&amp;lt;html&amp;gt;</code>';
							$php_code    = '<pre><code>&amp;lt;html &amp;lt;?php language_attributes(); ?&amp;gt;&amp;gt;</code></pre>';

							$text = sprintf( __( '%1$s hooks the \'%2$s\' filter (by default) to add / modify the %3$s HTML tag attributes for Open Graph namespace prefix values.', 'wpsso' ), $info['short'], $filter_name, $html_tag ).' ';

							$text .= sprintf( __( 'The <a href="%1$s">WordPress %2$s function</a> and its \'%3$s\' filter are used by most themes &mdash; if the namespace prefix values are missing from your %4$s HTML tag attributes, make sure your header template(s) use the %2$s function.', 'wpsso' ), $func_url, '<code>'.$func_name.'</code>', $filter_name, $html_tag ).' ';

							$text .= __( 'Leaving this option empty disables the addition of Open Graph namespace values.', 'wpsso' ).' ';

							$text .= sprintf( __( 'Example code for header templates: %1$s', 'wpsso' ), $php_code );

							break;

						case 'tooltip-plugin_head_attr_filter':

							$filter_name = 'head_attributes';
							$html_tag    = '<code>&amp;lt;head&amp;gt;</code>';
							$php_code    = '<pre><code>&amp;lt;head &amp;lt;?php do_action( &#39;add_head_attributes&#39; ); ?&amp;gt;&amp;gt;</code></pre>';

							$text = sprintf( __( '%1$s hooks the \'%2$s\' filter (by default) to add / modify the %3$s HTML tag attributes for Schema itemscope / itemtype markup.', 'wpsso' ), $info['short'], $filter_name, $html_tag ).' ';

							$text .= sprintf( __( 'If your theme already offers a filter for the %1$s HTML tag attributes, enter its name here (most themes do not offer this filter).', 'wpsso' ), $html_tag ).' ';

							$text .= sprintf( __( 'Alternatively, you can edit your your theme header templates and add an action to call the \'%1$s\' filter.', 'wpsso' ), $filter_name ).' ';

							$text .= sprintf( __( 'Example code for header templates: %1$s', 'wpsso' ), $php_code );

							break;

						case 'tooltip-plugin_check_head':

							$check_head_count = SucomUtil::get_const( 'WPSSO_DUPE_CHECK_HEADER_COUNT' );

							$text = sprintf( __( 'When editing Posts and Pages, %1$s can check the head section of webpages for conflicting and/or duplicate HTML tags. After %2$d <em>successful</em> checks, no additional checks will be performed &mdash; until the theme and/or any plugin is updated, when another %2$d checks are performed.', 'wpsso' ), $info['short'], $check_head_count );

							break;

						case 'tooltip-plugin_filter_lang':

							$text = sprintf( __( '%1$s can use the WordPress locale to dynamically select the correct language for the Facebook / Open Graph and Pinterest Rich Pin meta tags.', 'wpsso' ), $info['short'] ).' ';
							
							$text .= __( 'If your website is available in multiple languages, this can be a useful feature.', 'wpsso' ).' ';
							
							$text .= __( 'Uncheck this option to ignore the WordPress locale and always use the configured language.', 'wpsso' ); 

							break;

						case 'tooltip-plugin_create_wp_sizes':

							$text = __( 'Automatically create missing and/or incorrect images in the WordPress Media Library (default is checked).', 'wpsso' );
							break;

						case 'tooltip-plugin_check_img_dims':

							$settings_page_link = $this->p->util->get_admin_url( 'image-dimensions',
								_x( 'SSO Image Sizes', 'lib file description', 'wpsso' ) );

							$text = sprintf( __( 'When this option is enabled, full size images used for meta tags and Schema markup must be equal to (or larger) than the image dimensions you\'ve defined in the %s settings &mdash; images that do not meet or exceed the minimum requirements will be ignored.', 'wpsso' ), $settings_page_link ).' ';
							
							$text .= __( '<strong>Enabling this option is highly recommended</strong> &mdash; the option is disabled by default to avoid excessive warnings on sites with small / thumbnail images in their media library.', 'wpsso' );

							break;

						case 'tooltip-plugin_upscale_images':

							$text = __( 'WordPress does not upscale / enlarge images &mdash; WordPress can only create smaller images from larger full size originals.', 'wpsso' ).' ';
							
							$text .= __( 'Upscaled images do not look as sharp or clear, and if enlarged too much, will look fuzzy and unappealing &mdash; not something you want to promote on social and search sites.', 'wpsso' ).' ';
							
							$text .= sprintf( __( '%1$s includes a feature that allows upscaling of WordPress Media Library images for %2$s image sizes (up to a maximum upscale percentage).', 'wpsso' ), $info['name_pro'], $info['short'] ).' ';
							
							$text .= '<strong>'.__( 'Do not enable this option unless you want to publish lower quality images on social and search sites.', 'wpsso' ).'</strong>';

							break;

						case 'tooltip-plugin_upscale_img_max':

							$upscale_max = WpssoConfig::$cf['opt']['defaults']['plugin_upscale_img_max'];

							$text = sprintf( __( 'When upscaling of %1$s image sizes is allowed, %2$s can make sure smaller images are not upscaled beyond reason, which would publish very low quality / fuzzy images on social and search sites (the default maximum is %3$s%%).', 'wpsso' ), $info['short'], $info['name_pro'], $upscale_max ).' ';
							
							$text .= __( 'If an image needs to be upscaled beyond this maximum, in either width or height, the image will not be upscaled.', 'wpsso' );

							break;

						case 'tooltip-plugin_page_excerpt':

							$text = __( 'Enable the WordPress excerpt metabox for Pages.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'An excerpt is an optional hand-crafted summary of your content, that %s can also use as a default description value for meta tags and Schema markup.', 'wpsso' ), $info['short'] );

							break;

						case 'tooltip-plugin_page_tags':

							$text = __( 'Enable the WordPress tags metabox for Pages.', 'wpsso' ).' ';
							
							$text .= __( 'WordPress tags are optional keywords about the content subject, often used for searches and "tag clouds".', 'wpsso' ).' ';
							
							$text .= sprintf( __( '%s can convert WordPress tags into hashtags for some social sites (Twitter, Facebook, Google+, etc.).', 'wpsso' ), $info['short'] );

							break;

						case 'tooltip-plugin_add_person_role':

							$text = sprintf( __( 'Automatically add the \'%s\' role when new users are created.', 'wpsso' ), _x( 'Person', 'user role', 'wpsso' ) );

							break;

						/**
						 * 'Cache Settings' settings.
						 */
						case 'tooltip-plugin_head_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf['opt']['defaults']['plugin_head_cache_exp'];
							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'Head meta tags and Schema markup are saved to the WordPress transient cache to optimize performance.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_content_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf['opt']['defaults']['plugin_content_cache_exp'];
							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'Filtered post content is saved to the WordPress <em>non-persistent</em> object cache to optimize performance.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_short_url_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf['opt']['defaults']['plugin_short_url_cache_exp'];
							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'Shortened URLs are saved to the WordPress transient cache to optimize performance and API connections.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_imgsize_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf['opt']['defaults']['plugin_imgsize_cache_exp'];
							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'The size for image URLs (not image IDs) is retrieved and saved to the WordPress transient cache to optimize performance and network bandwidth.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_topics_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf['opt']['defaults']['plugin_topics_cache_exp'];
							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'The filtered article topics array is saved to the WordPress transient cache to optimize performance and disk access.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_json_data_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf['opt']['defaults']['plugin_json_data_cache_exp'];
							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = sprintf( __( 'When %s creates Schema markup for the Blog, CollectionPage ProfilePage, and SearchResultsPage types, the JSON-LD of each individual post is saved to the WordPress transient cache to optimize performance.', 'wpsso' ), $info['short'] ).' ';
							
							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

				 			break;

						case 'tooltip-plugin_types_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf['opt']['defaults']['plugin_types_cache_exp'];
							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'The filtered Schema types array is saved to the WordPress transient cache to optimize performance.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_show_purge_count':

							$text = __( 'Report the number of objects removed from the WordPress cache when posts, terms, and users are updated.', 'wpsso' );

							break;

						case 'tooltip-plugin_clear_on_save':	// Clear All Caches on Save Settings.

							$text = sprintf( __( 'Automatically clear all known plugin cache(s) when saving the %s settings (default is checked).', 'wpsso' ), $info['short'] );

							break;

						case 'tooltip-plugin_clear_all_refresh':	// Auto-Refresh Cache After Clear All.

							$text = sprintf( __( 'After clearing all %1$s cache objects, %1$s can automatically re-create the post, term, and user cache objects from a background task (does not affect page load time).', 'wpsso' ), $info['short'] );

							break;

						case 'tooltip-plugin_clear_short_urls':

							$cache_exp_secs = (int) apply_filters( $this->p->lca.'_cache_expire_short_url',
								$this->p->options['plugin_short_url_cache_exp'] );

							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = sprintf( __( 'Clear all shortened URLs when clearing all %s transients from the WordPress database (default is unchecked).', 'wpsso' ), $info['short'] ).' ';
							
							$text .= sprintf( __( 'Shortened URLs are cached for %1$s seconds (%2$s) to minimize external service API calls. Updating all shortened URLs at once may exceed API call limits imposed by your shortening service provider.', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_clear_for_comment':	// Clear Post Cache for New Comment.

							$text = __( 'Automatically clear the post cache when a new comment is added, or the status of an existing comment is changed.', 'wpsso' );

							break;

						/**
						 * 'Service APIs' (URL Shortening) settings.
						 */
						case 'tooltip-plugin_shortener':

							$text = sprintf( __( 'A preferred URL shortening service for %s plugin filters and/or add-ons that may need to shorten URLs &mdash; don\'t forget to define the service API keys for the URL shortening service of your choice.', 'wpsso' ), $info['short'] );

							break;

						case 'tooltip-plugin_min_shorten':

							$text = sprintf( __( 'URLs shorter than this length will not be shortened (the default suggested by Twitter is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'plugin_min_shorten' ) );

							break;

						case 'tooltip-plugin_wp_shortlink':

							$text = sprintf( __( 'Use the shortened sharing URL for the <em>Get Shortlink</em> button in admin editing pages, along with the "%s" HTML tag value.', 'wpsso' ), 'link&nbsp;rel&nbsp;shortlink' );

							break;

						case 'tooltip-plugin_add_link_rel_shortlink':

							$text = sprintf( __( 'Add a "%s" HTML tag for social crawlers and web browsers to the head section of webpages.', 'wpsso' ), 'link&nbsp;rel&nbsp;shortlink' );

							break;

						case 'tooltip-plugin_bitly_login':

							$text = __( 'The Bitly username to use with the Generic Access Token or API Key (deprecated).', 'wpsso' );

							break;

						case 'tooltip-plugin_bitly_access_token':

							$text = sprintf( __( 'The Bitly shortening service requires a <a href="%s">Generic Access Token</a> or API Key (deprecated) to shorten URLs.', 'wpsso' ), 'https://bitly.com/a/oauth_apps' );

							break;

						case 'tooltip-plugin_bitly_api_key':

							$text = sprintf( __( 'The Bitly <a href="%s">API Key</a> authentication method has been deprecated by Bitly.', 'wpsso' ), 'https://bitly.com/a/your_api_key' );

							break;

						case 'tooltip-plugin_bitly_domain':

							$text = __( 'An optional Bitly short domain to use; either bit.ly, j.mp, bitly.com, or another custom short domain. If no value is entered here, the short domain selected in your Bitly account settings will be used.', 'wpsso' );

							break;

						case 'tooltip-plugin_dlmyapp_api_key':

							$text = __( 'The DLMY.App secret API Key can be found in the DLMY.App user account &gt; Tools &gt; Developer API webpage.', 'wpsso' );

							break;

						case 'tooltip-plugin_google_api_key':

							$text = sprintf( __( 'The Google Project API Key for this website / project. If you don\'t already have a Google project for your website, visit the <a href="%s">Google APIs developers console</a> and create a new project for your website.', 'wpsso' ), 'https://console.developers.google.com/apis/dashboard' );

							break;

						case 'tooltip-plugin_google_shorten':

							$text = sprintf( __( 'In order to use Google\'s URL Shortener API service, you must <em>Enable</em> the URL Shortener API service from the <a href="%s">Google APIs developers console</a> (under the project\'s <em>Dashboard</em> settings page).', 'wpsso' ), 'https://console.developers.google.com/apis/dashboard' ).' '.__( 'Confirm that you have enabled Google\'s URL Shortener API service by checking the "Yes" option value.', 'wpsso' );

							break;

						case 'tooltip-plugin_owly_api_key':

							$text = sprintf( __( 'To use Ow.ly as your preferred shortening service, you must provide the Ow.ly API Key for this website (complete this form to <a href="%s">Request Ow.ly API Access</a>).', 'wpsso' ), 'https://docs.google.com/forms/d/1Fn8E-XlJvZwlN4uSRNrAIWaY-nN_QA3xAHUJ7aEF7NU/viewform' );

							break;

						case 'tooltip-plugin_yourls_api_url':

							$text = sprintf( __( 'The URL to <a href="%1$s">Your Own URL Shortener</a> (YOURLS) shortening service.', 'wpsso' ), 'http://yourls.org/' );
							break;

						case 'tooltip-plugin_yourls_username':

							$text = sprintf( __( 'If <a href="%1$s">Your Own URL Shortener</a> (YOURLS) shortening service is private, enter a configured username (see YOURLS Token for an alternative to the username / password options).', 'wpsso' ), 'http://yourls.org/' );

							break;

						case 'tooltip-plugin_yourls_password':

							$text = sprintf( __( 'If <a href="%1$s">Your Own URL Shortener</a> (YOURLS) shortening service is private, enter a configured user password (see YOURLS Token for an alternative to the username / password options).', 'wpsso' ), 'http://yourls.org/' );

							break;

						case 'tooltip-plugin_yourls_token':

							$text = sprintf( __( 'If <a href="%1$s">Your Own URL Shortener</a> (YOURLS) shortening service is private, you can use a token string for authentication instead of a username / password combination.', 'wpsso' ), 'http://yourls.org/' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_plugin', $text, $idx, $info );

							break;

					}	// end of tooltip-plugin switch
				/**
				 * Publisher 'Facebook' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-fb_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-fb_publisher_url':

							$fb_business_url = __( 'https://www.facebook.com/business', 'wpsso' );
							$fb_sucom_url    = __( 'https://www.facebook.com/SurniaUlulaCom', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">Facebook Business Page for your website / business</a>, you may enter its URL here (for example, the Facebook Business Page URL for %2$s is <a href="%3$s">%4$s</a>).', 'wpsso' ), $fb_business_url, 'Surnia Ulula', $fb_sucom_url, $fb_sucom_url ).' ';

							$text .= '<br/><br/>';

							$text .= __( 'The Facebook Business Page URL will be used in Open Graph <em>article</em> webpages and in the site\'s Schema Organization markup.', 'wpsso' ).' '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );

							$text .= '<br/><br/>';

							// "This option is localized" text will be added here.

							break;

						case 'tooltip-fb_app_id':

							$fb_apps_url     = __( 'https://developers.facebook.com/apps', 'wpsso' );
							$fb_docs_reg_url = __( 'https://developers.facebook.com/docs/apps/register', 'wpsso' );
							$fb_insights_url = __( 'https://developers.facebook.com/docs/insights/', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">Facebook App ID for your website</a>, enter it here (see <a href="%2$s">Register and Configure an App</a> for help on creating a Facebook App ID).', 'wpsso' ), $fb_apps_url, $fb_docs_reg_url ).' ';

							$text .= '<br/><br/>';

							$text .= sprintf( __( 'The Facebook App ID will appear in webpage meta tags and is used by Facebook to allow access to <a href="%1$s">Facebook Insight</a> data for accounts associated with that App ID.', 'wpsso' ), $fb_insights_url );

							break;

						case 'tooltip-fb_admins':

							$fb_insights_url = __( 'https://developers.facebook.com/docs/insights/', 'wpsso' );
							$fb_username_url = __( 'https://www.facebook.com/settings?tab=account&section=username&view', 'wpsso' );

							$text .= sprintf( __( 'The Facebook admin usernames are used by Facebook to allow access to <a href="%1$s">Facebook Insight</a> data for your website. Note that these are Facebook user account names, not Facebook Page names. You may enter one or more Facebook usernames (comma delimited).', 'wpsso' ), $fb_insights_url );

							$text .= '<br/><br/>';

							$text .= __( 'When viewing your own Facebook wall, your username is located in the URL (for example, https://www.facebook.com/<strong>username</strong>). Enter only the usernames, not the URLs.', 'wpsso' ).' '.sprintf( __( 'You may update your Facebook username in the <a href="%1$s">Facebook General Account Settings</a>.', 'wpsso' ), $fb_username_url );

							break;

						case 'tooltip-fb_author_name':

							$text = sprintf( __( '%1$s uses the Facebook contact field value from the author\'s WordPress profile for %2$s Open Graph meta tags. This allows Facebook to credit an author on shares and link their Facebook page URL.', 'wpsso' ), $info['short'], '<code>article:author</code>' ) . ' ';
							
							$text .= sprintf( __( 'If an author does not have a Facebook page URL in their WordPress profile, %1$s can fallback and use the <em>%2$s</em> instead (the recommended value is "Display Name").', 'wpsso' ), $info['short'], _x( 'Author Name Format', 'option label', 'wpsso' ) );

							break;

						case 'tooltip-fb_locale':

							$text = sprintf( __( 'Facebook does not support all WordPress locale values. If the Facebook debugger returns an error parsing the %1$s meta tag, you may have to choose an alternate Facebook language for that WordPress locale.', 'wpsso' ), '<code>og:locale</code>' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_fb', $text, $idx, $info );

							break;

					}	// end of tooltip-fb switch

				/**
				 * Publisher 'Google' / SEO settings
				 */
				} elseif ( strpos( $idx, 'tooltip-seo_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-seo_publisher_url':

							$text = 'If you have a <a href="http://www.google.com/+/business/">Google+ Business Page for your website / business</a>, you may enter its URL here (for example, the Google+ Business Page URL for Surnia Ulula is <a href="https://plus.google.com/+SurniaUlula/">https://plus.google.com/+SurniaUlula/</a>). The Google+ Business Page URL will be used in a link relation head tag, and the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );

							break;

						case 'tooltip-seo_desc_len':

							$text = __( 'The maximum length of text used for the Google Search "description" meta tag.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The length should be at least %1$d characters or more (the default is %2$d characters).',
								'wpsso' ), $this->p->cf['head']['limit_min']['seo_desc_len'],
									$this->p->opt->get_defaults( 'seo_desc_len' ) );

							break;

						case 'tooltip-seo_author_field':

							$text = sprintf( __( '%1$s can include an %2$s link URL in the head section for Google.', 'wpsso' ), $info['short'], '<code>author</code>' ) . ' ';

							$text = sprintf( __( 'Select the contact field to use from the author\'s WordPress profile page for the %s link value.', 'wpsso' ), '<code>author</code>' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_seo', $text, $idx, $info );

							break;

					}	// end of tooltip-google switch

				/**
				 * Publisher 'Schema' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-schema_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-schema_add_noscript':

							$text = 'When additional schema properties are available (product ratings, recipe ingredients, etc.), one or more <code>noscript</code> containers may be included in the webpage head section. <code>noscript</code> containers are read correctly by Google and Pinterest, but the W3C Validator will show errors for the included meta tags (these errors can be safely ignored). The <code>noscript</code> containers are always disabled for AMP webpages, and always enabled for the Pinterest crawler.';

							break;

						case 'tooltip-schema_knowledge_graph':

							$settings_page_link = $this->p->util->get_admin_url( 'social-accounts',
								_x( 'SSO WebSite Pages', 'lib file description', 'wpsso' ) );

							$text = __( 'Include WebSite, Organization and/or Person Schema markup in the front page for Google\'s <em>Knowledge Graph</em>.', 'wpsso' ).'<br/><br/>';
							
							$text .= __( 'The WebSite markup includes the site name, alternate site name, site URL and search query URL.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'Developers can hook the \'%s\' filter to modify the site search URL (or disable its addition by returning false).', 'wpsso' ), $this->p->lca.'_json_ld_search_url' ).'<br/><br/>';
							
							$text .= sprintf( __( 'The Organization markup includes all URLs entered in the %s settings page.', 'wpsso' ), $settings_page_link ).'<br/><br/>';
							
							$text .= __( 'The Person markup includes all contact method URLs entered in the user\'s WordPress profile page.', 'wpsso' );

							break;

						case 'tooltip-schema_home_person_id':

							$text = __( 'Select an optional site owner for the <em>Knowledge Graph</em> Person markup included in the front page.', 'wpsso' ).' '.__( 'The Person markup includes all contact method URLs entered in the user\'s WordPress profile page.', 'wpsso' ).' '.sprintf( __( 'The available Person list includes users with \'%1$s\' and/or \'%2$s\' roles.', 'wpsso' ), _x( 'Administrator', 'user role', 'wpsso' ), _x( 'Editor', 'user role', 'wpsso' ) );

							break;

						case 'tooltip-schema_logo_url':

							$text = __( 'A URL for this organization\'s logo image that Google can use in its search results and <em>Knowledge Graph</em>.', 'wpsso' );

							break;

						case 'tooltip-schema_banner_url':

							$text = __( 'A URL for this organization\'s banner image &mdash; <strong>measuring exactly 600x60px</strong> &mdash; that Google News can use for Schema Article content from this publisher.', 'wpsso' );

							break;

						case 'tooltip-schema_img_max':

							$text = __( 'The maximum number of images to include in the Schema markup &mdash; this includes the <em>featured</em> or <em>attached</em> images, and any images found in the Post or Page content.', 'wpsso' ).' ';

							$text .= __( 'If you select "0", then no images will be included in the Schema markup (<strong>not recommended</strong>).', 'wpsso' );

							break;

						case 'tooltip-schema_img_dimensions':

							$def_dimensions = $this->p->opt->get_defaults( 'schema_img_width' ).'x'.
								$this->p->opt->get_defaults( 'schema_img_height' ).' '.
								( $this->p->opt->get_defaults( 'schema_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = sprintf( __( 'The image dimensions used for the Google / Schema meta tags and JSON-LD markup (the default dimensions are %s).', 'wpsso' ), $def_dimensions ) . ' ';
							
							$text .= __( 'The minimum width required by Google for the resulting image is 696px.', 'wpsso' ) . ' ';
							
							$text .= __( 'If you choose not to crop this image size, make sure the height value is large enough for portrait / vertical images.', 'wpsso' );

							break;

						case 'tooltip-schema_desc_len':

							$text = __( 'The maximum length of text used for the Google+ / Schema description meta tag.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The length should be at least %1$d characters or more (the default is %2$d characters).',
								'wpsso' ), $this->p->cf['head']['limit_min']['schema_desc_len'],
									$this->p->opt->get_defaults( 'schema_desc_len' ) );

							break;

						case 'tooltip-schema_author_name':

							$text = sprintf( __( 'Select an <em>%1$s</em> for the author / Person markup, or "[None]" to disable this feature (the recommended value is "Display Name").', 'wpsso' ), _x( 'Author Name Format', 'option label', 'wpsso' ) );

							break;

						case 'tooltip-schema_type_for_home_index':	// Item Type for Blog Front Page

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_home_index' );

							$text = sprintf( __( 'Select the %1$s type for a blog (non-static) front page.', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_home_page':	// Item Type for Static Front Page

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_home_page' );

							$text = sprintf( __( 'Select the %1$s type for a static front page.', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_user_page':	// Item Type for User / Author Page

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_user_page' );

							$text = sprintf( __( 'Select the %1$s type for user / author pages.', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_search_page':	// Item Type for Search Results Page

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_search_page' );

							$text = sprintf( __( 'Select the %1$s type for search results pages.', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_archive_page':	// Item Type for Other Archive Page

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_archive_page' );

							$text = sprintf( __( 'Select the %1$s type for other archive pages (example: date-based archive pages).', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_ptn':	// Item Type by Post Type

							$text = sprintf( __( 'Select the %1$s type for each WordPress post type.', 'wpsso' ), 'Schema' );

							break;

						case 'tooltip-schema_type_for_ttn':	// Item Type by Term Taxonomy

							$text = sprintf( __( 'Select the %1$s type for each WordPress term taxonomy.', 'wpsso' ), 'Schema' );


							break;

						case 'tooltip-schema_review_item_type':	// Default Reviewed Item Type

							$text = sprintf( __( 'Select the default Schema type for reviewed items (used when the content Schema type is a %s).',
								'wpsso' ), 'https://schema.org/Review' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_schema', $text, $idx, $info );

							break;

					}	// end of tooltip-google switch

				/**
				 * Publisher 'Twitter Card' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-tc_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-tc_site':

							$text = 'The <a href="https://business.twitter.com/">Twitter @username for your website and/or business</a> (not your personal Twitter @username). As an example, the Twitter @username for Surnia Ulula is <a href="https://twitter.com/surniaululacom">@surniaululacom</a>. The website / business @username is also used for the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );

							break;

						case 'tooltip-tc_desc_len':

							$text = __( 'The maximum length of text used for the Twitter Card description.', 'wpsso' ).' ';
							
							$text .= sprintf( __( 'The length should be at least %1$d characters or more (the default is %2$d characters).',
								'wpsso' ), $this->p->cf['head']['limit_min']['tc_desc_len'],
									$this->p->opt->get_defaults( 'tc_desc_len' ) );

							break;

						case 'tooltip-tc_type_post':

							$text = 'The Twitter Card type for posts / pages with a custom, featured, and/or attached image.';

							break;

						case 'tooltip-tc_type_default':

							$text = 'The Twitter Card type for all other images (default, image from content text, etc).';

							break;

						case 'tooltip-tc_sum_img_dimensions':

							$def_dimensions = $this->p->opt->get_defaults( 'tc_sum_img_width' ) . 'x' . 
								$this->p->opt->get_defaults( 'tc_sum_img_height' ) . ' ' . 
								( $this->p->opt->get_defaults( 'tc_sum_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = sprintf( __( 'The image dimensions used for the <a href="%1$s">Summary Card</a> (should be at least %2$s and less than %3$s).', 'wpsso' ), 'https://dev.twitter.com/docs/cards/types/summary-card', '120x120px', __( '1MB', 'wpsso' ) ) . ' ';
							
							$text .= sprintf( __( 'The default image dimensions are %s.', 'wpsso' ), $def_dimensions );

							break;

						case 'tooltip-tc_lrg_img_dimensions':

							$def_dimensions = $this->p->opt->get_defaults( 'tc_lrg_img_width' ) . 'x' . 
								$this->p->opt->get_defaults( 'tc_lrg_img_height' ) . ' ' . 
								( $this->p->opt->get_defaults( 'tc_lrg_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = sprintf( __( 'The image dimensions used for the <a href="%1$s">Large Image Summary Card</a> (must be larger than %2$s and less than %3$s).', 'wpsso' ), 'https://dev.twitter.com/docs/cards/large-image-summary-card', '280x150px', __( '1MB', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The default image dimensions are %s.', 'wpsso' ), $def_dimensions );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_tc', $text, $idx, $info );

							break;

					}	// end of tooltip-tc switch

				/**
				 * Publisher 'Pinterest' (Rich Pin) settings
				 */
				} elseif ( strpos( $idx, 'tooltip-p_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-p_publisher_url':

							$text = 'If you have a <a href="https://business.pinterest.com/">Pinterest Business Page for your website / business</a>, you may enter its URL here. The Publisher Business Page URL will be used in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );

							break;

						case 'tooltip-p_dom_verify':

							$text = sprintf( __( 'To <a href="%s">verify your website</a> with Pinterest, edit your business account profile on Pinterest and click the "Verify WebSite" button.', 'wpsso' ), 'https://help.pinterest.com/en/articles/verify-your-website#meta_tag' ).' '.__( 'Enter the supplied "p:domain_verify" meta tag <em>content</em> value here.', 'wpsso' );

							break;

						case 'tooltip-p_author_name':

							$text = sprintf( __( 'Pinterest ignores Facebook-style Author Profile URLs in the %1$s Open Graph meta tags.', 'wpsso' ), '<code>article:author</code>' ).' '.__( 'A different meta tag value can be used when the Pinterest crawler is detected.', 'wpsso' ).' '.sprintf( __( 'Select an <em>%1$s</em> for the %2$s meta tag or "[None]" to disable this feature (the recommended value is "Display Name").', 'wpsso' ), _x( 'Author Name Format', 'option label', 'wpsso' ), '<code>article:author</code>' );

							break;

						case 'tooltip-p_add_img_html':

							$text = __( 'Add the Google / Schema image to the content (in a hidden container) for the Pinterest Pin It browser button.', 'wpsso' );

							break;

						case 'tooltip-p_add_nopin_header_img_tag':

							$text = __( 'Add a "nopin" attribute to the header image (since WP v4.4) to prevent the Pin It button from suggesting that image.', 'wpsso' );

							break;

						case 'tooltip-p_add_nopin_media_img_tag':

							$text = __( 'Add a "nopin" attribute to images from the WordPress Media Library to prevent the Pin It button from suggesting those images.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_p', $text, $idx, $info );

							break;

					}	// end of tooltip-p switch

				/**
				 * Publisher 'Instagram' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-instgram_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-instgram_publisher_url':

							$text = 'If you have an <a href="http://blog.business.instagram.com/">Instagram account for your website / business</a>, you may enter its URL here. The Instagram Business Page URL will be used in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_instgram', $text, $idx, $info );

							break;

					}	// end of tooltip-instgram switch

				/**
				 * Publisher 'LinkedIn' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-linkedin_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-linkedin_publisher_url':

							$text = 'If you have a <a href="https://business.linkedin.com/marketing-solutions/company-pages/get-started">LinkedIn Company Page for your website / business</a>, you may enter its URL here (for example, the LinkedIn Company Page URL for Surnia Ulula is <a href="https://www.linkedin.com/company/surnia-ulula-ltd">https://www.linkedin.com/company/surnia-ulula-ltd</a>). The LinkedIn Company Page URL will be included in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_linkedin', $text, $idx, $info );

							break;

					}	// end of tooltip-linkedin switch

				/**
				 * Publisher 'Myspace' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-myspace_' ) === 0 ) {

					switch ( $idx ) {

						case 'tooltip-myspace_publisher_url':

							$text = 'If you have a <a href="http://myspace.com/">Myspace account for your website / business</a>, you may enter its URL here. The Myspace Business (Brand) URL will be used in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip_myspace', $text, $idx, $info );

							break;

						}	// end of tooltip-myspace switch

				/**
				 * All other settings
				 */
				} else {

					switch ( $idx ) {

						case 'tooltip-custom-cm-field-id':

							$text .= '<strong>' . sprintf( __( 'You should not modify the <em>%1$s</em> column unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) ) . '</strong> ';

							$text .= sprintf( __( 'For example, to match the <em>%1$s</em> of a theme or other plugin, you might change "gplus" to "googleplus".', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) );

							break;

						case 'tooltip-custom-cm-field-label':

							$text = sprintf( __( 'The <em>%1$s</em> column is for display purposes only and can be changed as you wish.', 'wpsso' ), _x( 'Contact Field Label', 'column title', 'wpsso' ) );

							break;

						case 'tooltip-wp-cm-field-id':

							$text = sprintf( __( 'The built-in WordPress <em>%1$s</em> column cannot be modified.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) );

							break;

						case 'tooltip-site-use':

							$text = __( 'Individual sites/blogs may use this value as a default (when the plugin is first activated), if the current site/blog option value is blank, or force every site/blog to use this specific value.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca.'_messages_tooltip', $text, $idx, $info );

							break;

					} 	// end of all other settings switch

				}	// end of tooltips

			/**
			 * Misc informational messages
			 */
			} elseif ( strpos( $idx, 'info-' ) === 0 ) {

				if ( strpos( $idx, 'info-meta-' ) === 0 ) {

					switch ( $idx ) {

						case 'info-meta-validate-facebook':

							$text = '<p class="top">';

							$text .= __( 'Facebook and most social websites read Open Graph meta tags.', 'wpsso' ).' ';

							$text .= __( 'The Facebook debugger allows you to refresh Facebook\'s cache, while also validating the Open Graph meta tag values.', 'wpsso' ).' ';

							$text .= __( 'The Facebook debugger remains the most stable and reliable method to verify Open Graph meta tags.', 'wpsso' );

							$text .= '</p><p><i>';

							$text .= __( 'You may have to click the "Fetch new scrape information" button a few times to refresh Facebook\'s cache.', 'wpsso' );

							$text .= '</i></p>';

						 	break;

						case 'info-meta-validate-google':

							$text = '<p class="top">';

							$text .= __( 'Verify that Google can correctly parse your structured data markup (meta tags, Schema, Microdata, and JSON-LD markup) for Google Search and Google+.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-pinterest':

							$text = '<p class="top">';

							$text .= __( 'Validate the Open Graph / Rich Pin meta tags and apply to have them shown on Pinterest zoomed pins.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-twitter':

							$text = '<p class="top"><i>';

							$text .= __( 'The Twitter Card Validator does not accept query arguments &mdash; paste the following URL in the Twitter Card Validator "Card URL" input field (copy the URL using the clipboard icon):', 'wpsso' );

							$text .= '</i></p>';

						 	break;

						case 'info-meta-validate-w3c':

							$settings_page_link = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_google',
								_x( 'Meta Property Containers', 'option label', 'wpsso' ) );

							$text = '<p class="top">';

							$text .= __( 'Validate the HTML syntax and HTML 5 conformance of your meta tags and theme templates.', 'wpsso' ).' ';

							$text .= __( 'Theme templates with serious errors may prevent social and search crawlers from parsing the webpage HTML, so validating your theme template markup is important.', 'wpsso' );

							$text .= '</p>';

							if ( $this->p->schema->is_noscript_enabled() ) {

								$text .= '<p><i>';

								$text .= sprintf( __( 'When the %1$s option is enabled, the W3C validator will show errors for itemprop attributes in meta elements &mdash; you may ignore these errors or disable the %1$s option.', 'wpsso' ), $settings_page_link );

								$text .= '</i></p>';
							}

						 	break;

						case 'info-meta-validate-amp':

							$text = '<p class="top">'.__( 'Validate the HTML syntax and HTML AMP conformance of your meta tags and the AMP markup of your templates.', 'wpsso' ).'</p>'.( $this->p->avail['*']['amp'] ? '' : '<p><i>'.sprintf( __( 'The <a href="%s">AMP plugin by Automattic</a> is required to validate AMP formatted webpages.', 'wpsso' ), 'https://wordpress.org/plugins/amp/' ).'</i></p>' );

						 	break;

						case 'info-meta-social-preview':

							$fb_img_dims = '600x315px';

						 	$text = '<p class="status-msg">';

							$text .= sprintf( __( 'The example image container uses the minimum recommended Facebook image dimensions of %s.', 'wpsso' ), $fb_img_dims );

							$text .= '</p>';

						 	break;

					}	// end of info-meta switch

				} else {

					switch ( $idx ) {

						case 'info-plugin-tid':	// Displayed in the Licenses settings page.

							$um_info = $this->p->cf['plugin']['wpssoum'];

							$text = '<blockquote class="top-info">';

							$text .= '<p>' . sprintf( __( 'After purchasing the %1$s plugin &mdash; or any of its Pro add-ons &mdash; you\'ll receive an email with a unique Authentication ID for the plugin / add-on you purchased.', 'wpsso' ), $info['short_pro'] ) . ' ';

							$text .=  __( 'Enter the Authentication ID in the option field corresponding to the plugin / add-on you purchased.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Don\'t forget that the %1$s Free add-on must also be installed and active to check for Pro version updates.', 'wpsso' ), $um_info['name'] ) . ' ;-)</p>';

							if ( ! WpssoAdmin::$pkg[$lca]['aop'] ) {
								$text .= '<p>' . sprintf( __( 'Please note that Pro add-ons use several %1$s features. This means that all Pro add-ons require an active and licensed %1$s plugin &mdash; don\'t forget to purchase %1$s before purchasing any of its Pro add-ons.', 'wpsso' ), $info['short_pro'] ) . ' ;-)</p>';
							}

							$text .= '</blockquote>';

							break;

						case 'info-plugin-tid-network':	// Displayed in the Network Licenses settings page.

							$um_info = $this->p->cf['plugin']['wpssoum'];

							$settings_page_link = $this->p->util->get_admin_url( 'licenses',
								_x( 'Licenses', 'lib file description', 'wpsso' ) );

							$text = '<blockquote class="top-info">';

							$text .= '<p>' . sprintf( __( 'After purchasing the %1$s plugin &mdash; or any of its Pro add-ons &mdash; you\'ll receive an email with a unique Authentication ID for the plugin / add-on you purchased.', 'wpsso' ), $info['short_pro'] ) . ' ';

							$text .= sprintf( __( 'You may enter each Authentication ID on this page <em>to define a value for all sites within the network</em> &mdash; or enter Authentication IDs individually on each site\'s %1$s settings page.', 'wpsso' ), $settings_page_link ) . '</p>';

							$text.= '<p>' . __( 'If you enter Authentication IDs in this network settings page, <em>please make sure you have purchased enough licenses for all sites within the network</em> &mdash; for example, to license a Pro add-on for 10 sites, you would need an Authentication ID from a 10 license pack purchase (or more) of that Pro add-on.', 'wpsso' ) . '</p>';

							$text .= '<p>' . sprintf( __( '<strong>WordPress uses the default site / blog ID to install and/or update plugins from the Network Admin interface</strong> &mdash; to update the %1$s and its Pro add-ons, please make sure the %2$s Free add-on is active on the default site, and the default site is licensed.', 'wpsso' ), $info['name_pro'], $um_info['name'] ) . '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-cm':

							$text = '<blockquote class="top-info">';
							
							$text .= '<p>';
							
							$text .= sprintf( __( 'The following options allow you to customize the list of contact fields shown in the <strong>%1$s</strong> section of <a href="%2$s">the user profile page</a>.', 'wpsso' ), __( 'Contact Info' ), get_admin_url( null, 'profile.php' ) ) . ' ';
							
							$text .= sprintf( __( '%1$s uses the Facebook, Google+, and Twitter contact field values in its meta tags and Schema markup.', 'wpsso' ), $info['short'] ) . ' ';
							
							$text .= '<strong>' . sprintf( __( 'You should not modify the <em>%1$s</em> column unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) ) . '</strong> ';
							
							$text .= sprintf( __( 'The <em>%1$s</em> column on the other hand is for display purposes only and can be changed as you wish.', 'wpsso' ), _x( 'Contact Field Label', 'column title', 'wpsso' ) ) . ' ';
							
							$text .= '</p><p><center>';
							
							$text .= '<strong>'.__( 'DO NOT ENTER YOUR CONTACT INFORMATION HERE &ndash; THESE ARE CONTACT FIELD LABELS ONLY.', 'wpsso' ).'</strong><br/>';
							
							$text .= sprintf( __( 'Enter your personal contact information in <a href="%1$s">the user profile page</a>.', 'wpsso' ), get_admin_url( null, 'profile.php' ) );

							$text .= '</center></p>';

							$text .= '</blockquote>';

							break;

						case 'info-taglist':

							$text = '<blockquote class="top-info"><p>';

							$text .= sprintf( __( '%s adds the following Facebook, Open Graph, Twitter, Schema, Pinterest, Google Rich Card / SEO meta tags to the <code>&lt;head&gt;</code> section of your webpages.', 'wpsso' ), $info['short'] ).' ';

							$text .= __( 'If your theme or another plugin already creates one or more of these meta tags, you can uncheck them here to prevent duplicates from being added.', 'wpsso' ).' ';

							$text .= sprintf( __( 'For example, the "%1$s" SEO meta tag is automatically unchecked if a <em>known</em> SEO plugin is detected, and the "%2$s" meta tag is unchecked by default (themes often include this meta tag in their header template).', 'wpsso' ), 'meta name description', 'link rel canonical' );

							$text .= '</p></blockquote>';

							break;

						default:

							$text = apply_filters( $lca.'_messages_info', $text, $idx, $info );

							break;

					}	// end of info switch
				}
			/**
			 * Misc pro messages
			 */
			} elseif ( strpos( $idx, 'pro-' ) === 0 ) {

				switch ( $idx ) {

					case 'pro-feature-msg':

						/**
						 * The $idx value has already been added to the purchase URL as a 'utm_source' query value.
						 */
						$begin_p = '<p class="pro-feature-msg">' .
							( empty( $url['purchase'] ) ? '' : '<a href="' . $url['purchase'] . '">' );

						$end_p   = ( empty( $url['purchase'] ) ? '' : '</a>' ) . '</p>';

						if ( $lca === $this->p->lca ) {

							if ( $this->p->check->aop( $lca, false ) ) {
								$text = $begin_p . sprintf( __( 'Purchase %s plugin license(s) to use the following features / options.',
									'wpsso' ), $info['short_pro'] ) . $end_p;
							} else {
								$text = $begin_p . sprintf( __( 'Purchase the %s plugin to install the Pro update and use the following features / options.',
									'wpsso' ), $info['short_pro'] ) . $end_p;
							}

						} else {

							$has_pdir = $this->p->avail['*']['p_dir'];

							if ( ! $this->p->check->aop( $this->p->lca, true, $has_pdir ) ) {
								$req_short = $this->p->cf['plugin'][$this->p->lca]['short'] . ' Pro';
								$req_msg   = sprintf( __( '(note that all Pro add-ons require a licensed and active %1$s plugin)',
									'wpsso' ), $req_short );
								$end_p     = ( empty( $url['purchase'] ) ? '' : '</a>' ) . '<br/>' . $req_msg . '</p>';
							}

							if ( $this->p->check->aop( $lca, false ) ) {
								$text = $begin_p . sprintf( __( 'Purchase %s add-on licence(s) to use the following features / options.',
									'wpsso' ), $info['short_pro'] ) . $end_p;
							} else {
								$text = $begin_p . sprintf( __( 'Purchase the %s add-on to install the Pro update and use the following features / options.',
									'wpsso' ), $info['short_pro'] ) . $end_p;
							}
						}

						break;

					case 'pro-select-msg':

						$text = '<span class="pro-select-msg">';

						$text .= _x( '[select preview]', 'option comment', 'wpsso' ) . ' ';

						/**
						 * The $idx value has already been added to the purchase URL as a 'utm_source' query value.
						 */
						$text .= empty( $url['purchase'] ) ? '' : '<a href="' . $url['purchase'] . '">';

						$text .= sprintf( _x( '%s required', 'option comment', 'wpsso' ), $info['short_pro'] );

						$text .= empty( $url['purchase'] ) ? '' : '</a>';

						$text .= '</span>';

						break;

					case 'pro-purchase-link':

						if ( empty( $info['ext'] ) ) {	// Nothing to do.
							break;
						}
						
						if ( WpssoAdmin::$pkg[$info['ext']]['aop'] ) {
							$text = _x( 'More Licenses', 'plugin action link', 'wpsso' );
						} elseif ( $info['ext'] === $lca ) {
							$text = _x( 'Purchase Core Pro', 'plugin action link', 'wpsso' );
						} else {
							$text = _x( 'Purchase Pro Add-on', 'plugin action link', 'wpsso' );
						}

						if ( ! empty( $info['url'] ) ) {

							if ( $info['ext'] !== $lca && ! WpssoAdmin::$pkg[$lca]['aop'] ) {
								$text .= ' <em>' . _x( '(Core Pro required)', 'plugin action link', 'wpsso' ) . '</em>';
							} else {
								$text = '<a href="' . $info['url'] . '"' . ( empty( $info['tabindex'] ) ? '' :
									' tabindex="' . $info['tabindex'] . '"' ) . '>' .  $text . '</a>';
							}
						}

						break;

					case 'pro-about-msg-post-text':

						$text = '<p style="text-align:center;margin:0;">';

						$text .= sprintf( __( 'You can update the %s excerpt or content text to change the default description values.', 'wpsso' ), $info['post_type'] );

						$text .= '</p>';

						break;

					case 'pro-about-msg-post-media':

						$text = '<p style="text-align:center;margin:0;">';

						$text .= __( 'You can change the social image by selecting a featured image, attaching image(s) or including images in the content.', 'wpsso' );

						$text .= '</br/>';

						$text .= sprintf( __( 'Video service API modules, required to detect embedded videos, are provided by the %s plugin.', 'wpsso' ),  $info['short_pro'] );
						
						$text .= '</p>';

						break;

					default:

						$text = apply_filters( $lca.'_messages_pro', $text, $idx, $info );

						break;
				}
			/**
			 * Misc notice messages
			 */
			} elseif ( strpos( $idx, 'notice-' ) === 0 ) {

				switch ( $idx ) {

					case 'notice-image-rejected':

						/**
						 * Do not add this text if hidding pro options or on a settings page.
						 */
						if ( empty( $this->p->options['plugin_hide_pro'] ) && WpssoMeta::is_meta_page() ) {

							$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );
							$metabox_tab   = _x( 'Priority Media', 'metabox tab', 'wpsso' );

							$text = sprintf( __( 'A larger and/or different custom image, specifically for meta tags and Schema markup, can be selected in the %1$s metabox under the %2$s tab.', 'wpsso' ), $metabox_title, $metabox_tab );

						} else {
							$text = '';
						}

						static $do_once_upscale_notice = null;	// Show the upscale details only once.

						if ( $do_once_upscale_notice !== true && current_user_can( 'manage_options' ) && 
							( ! isset( $info['allow_upscale'] ) || ! empty( $info['allow_upscale'] ) ) ) {

							$do_once_upscale_notice = true;

							$img_dim_page_link = $this->p->util->get_admin_url( 'image-dimensions', 
								_x( 'SSO Image Sizes', 'lib file description', 'wpsso' ) );

							$img_dim_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
								_x( 'Enforce Image Dimensions Check', 'option label', 'wpsso' ) );

							$upscale_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
								_x( 'Allow Upscale of WP Media Images', 'option label', 'wpsso' ) );

							$percent_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
								_x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ) );

							$text .= '<p style="margin-left:0;"><em>'.
								__( 'Additional information shown only to users with Administrative privileges:',
									'wpsso' ).'</em></p>';

							$text .= '<ul>';

							$text .= '<li>'.sprintf( __( 'You can adjust the <b>%1$s</b> option in the %2$s settings.', 'wpsso' ), $info['size_label'], $img_dim_page_link ).'</li>';

							if ( empty( $this->p->options['plugin_upscale_images'] ) ) {
								$text .= '<li>'.sprintf( __( 'Enable the %1$s option.', 'wpsso' ), $upscale_option_link ).'</li>';
							}

							$text .= '<li>'.sprintf( __( 'Increase the %1$s option value.', 'wpsso' ), $percent_option_link ).'</li>';

							$text .= '<li>'.sprintf( __( 'Disable the %1$s option (not recommended).', 'wpsso' ), $img_dim_option_link ).'</li>';

							$text .= '</ul>';
						}

						break;

					case 'notice-missing-og-image':

						$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'An Open Graph image meta tag could not be generated from this webpage content or its custom %s metabox settings. Facebook <em>requires at least one image meta tag</em> to render shared content correctly.', 'wpsso' ), $metabox_title );

						break;

					case 'notice-missing-og-description':

						$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'An Open Graph description meta tag could not be generated from this webpage content or its custom %s metabox settings. Facebook <em>requires a description meta tag</em> to render shared content correctly.', 'wpsso' ), $metabox_title );

						break;

					case 'notice-missing-schema-image':

						$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'A Schema image property could not be generated from this webpage content or its custom %s metabox settings. Google <em>requires at least one image property</em> for this Schema item type.', 'wpsso' ), $metabox_title );

						break;

					case 'notice-content-filters-disabled':

						$settings_page_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content' );

						$filters_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content',
							_x( 'Apply WordPress Content Filters', 'option label', 'wpsso' ) );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %1$s advanced option is currently disabled.', 'wpsso' ), $filters_option_link ) . '</b> ';

						$text .= sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions and detect additional images / embedded videos provided by shortcodes.', 'wpsso' ), $info['name'] );

						$text .= '</p><p>';

						$text .= '<b>' . __( 'Some themes / plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ).'</b> ';

						$text .= sprintf( __( '<a href="%s">If you use shortcodes in your content text, this option should be enabled</a> &mdash; if you experience display issues after enabling this option, determine which theme / plugin is filtering the content incorrectly and report the problem to its author(s).', 'wpsso' ), $settings_page_url ).' ';

						$text .= sprintf( __( 'Advanced users can also hook the \'%s\' filter and return true / false to enable / disable this feature.', 'wpsso' ), $this->p->lca.'_filter_content' );

						$text .= '</p>';

						break;

					case 'notice-header-tmpl-no-head-attr':

						$filter_name = 'head_attributes';
						$html_tag    = '<code>&lt;head&gt;</code>';
						$php_code    = '<pre><code>&lt;head &lt;?php do_action( &#39;add_head_attributes&#39; ); ?&gt;&gt;</code></pre>';
						$action_url  = wp_nonce_url( $this->p->util->get_admin_url( '?'.$this->p->cf['lca'].'-action=modify_tmpl_head_attributes' ),
							WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

						$text = '<p class="top">';

						$text .= __( '<b>At least one of your theme header templates does not support Schema markup of the webpage head section</b> &mdash; this is especially important for Pinterest (and Google if not using the JSON-LD add-on).', 'wpsso' ).' ';

						$text .= '</p><p>';

						$text .= sprintf( __( 'The %1$s HTML tag in your header template(s) should include a function, action, or filter for its attributes.', 'wpsso' ), $html_tag ).' ';

						$text .= sprintf( __( '%1$s can update your header template(s) automatically and change the existing %2$s HTML tag to:', 'wpsso' ), $info['short'], $html_tag );

						$text .= '</p>'.$php_code.'<p>';

						$text .= sprintf( __( '<b><a href="%1$s">Click here to update header template(s) automatically</a></b> (recommended) or update the template(s) manually.', 'wpsso' ), $action_url );

						$text .= '</p>';

						break;

					case 'notice-pro-tid-missing':

						if ( ! is_multisite() ) {
							$settings_page_link = $this->p->util->get_admin_url( 'licenses',
								_x( 'Licenses', 'lib file description', 'wpsso' ) );

							$text = '<p><b>'.sprintf( __( 'The %1$s plugin Authentication ID option is empty.', 'wpsso' ), $info['name'] ).'</b><br/>'.sprintf( __( 'To enable Pro version features and allow the plugin to authenticate itself for updates, please enter the unique Authentication ID you received by email in the %s settings page.', 'wpsso' ), $settings_page_link ).'</p>';
						}

						break;

					case 'notice-pro-not-installed':

						$settings_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Licenses', 'lib file description', 'wpsso' ) );

						$text = sprintf( __( 'An Authentication ID has been entered for %1$s but the plugin has not been installed &mdash; you can install and activate the Pro version from the %2$s settings page.', 'wpsso' ), '<b>'.$info['name'].'</b>', $settings_page_link ).' ;-)';

						break;

					case 'notice-pro-not-updated':

						$settings_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Licenses', 'lib file description', 'wpsso' ) );

						$text = sprintf( __( 'An Authentication ID has been entered for %1$s in the %2$s settings page but the Pro version has not been installed &mdash; don\'t forget to update the plugin to install the latest Pro version.', 'wpsso' ), '<b>'.$info['name'].'</b>', $settings_page_link ).' ;-)';

						break;

					case 'notice-um-add-on-required':
					case 'notice-um-activate-add-on':

						$um_info = $this->p->cf['plugin']['wpssoum'];

						$settings_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Licenses', 'lib file description', 'wpsso' ) );

						$plugins_page_link = '<a href="'.get_admin_url( null, 'plugins.php' ).'">'.__( 'Plugins' ).'</a>';

						$text = '<p>';

						$text .= '<b>'.sprintf( __( 'At least one Authentication ID has been entered in the %1$s settings page,<br/>but the %2$s add-on is not active.', 'wpsso' ), $settings_page_link, $um_info['name'] ).'</b> ';

						$text .= sprintf( __( 'This Free add-on is required to update and enable the %1$s plugin and its Pro add-ons.', 'wpsso' ), $info['name_pro'] );

						$text .= '</p><p>';

						if ( $idx === 'notice-um-add-on-required' ) {
							$text .= '<b>'.sprintf( __( 'Install and activate the %1$s add-on from the %2$s settings page.', 'wpsso' ), $um_info['name'], $settings_page_link ).'</b>';
						} else {
							$text .= '<b>'.sprintf( __( 'The %1$s add-on can be activated from the WordPress %2$s page.', 'wpsso' ), $um_info['name'], $plugins_page_link ).'</b> '.__( 'Please activate this Free add-on now.', 'wpsso' );
						}

						$text .= ' '.sprintf( __( 'When the %1$s add-on is active, one or more Pro version updates may be available for your licensed plugin and/or its add-on(s).', 'wpsso' ), $um_info['name'] );

						$text .= '</p>';

						break;

					case 'notice-um-version-recommended':

						$um_info = $this->p->cf['plugin']['wpssoum'];

						$um_version = isset( $um_info['version'] ) ? $um_info['version'] : 'unknown';

						$um_rec_version = isset( $info['um_rec_version'] ) ?
							$info['um_rec_version'] : WpssoConfig::$cf['um']['rec_version'];

						$um_check_updates_transl = _x( 'Check for Updates', 'submit button', 'wpsso' );

						$um_settings_page_link = $this->p->util->get_admin_url( 'um-general',
							_x( 'Update Manager', 'lib file description', 'wpsso' ) );

						$wp_updates_page_link = '<a href="'.admin_url( 'update-core.php' ).'">'.
							__( 'Dashboard' ).' &gt; '.__( 'Updates' ).'</a>';

						$text = sprintf( __( '%1$s version %2$s requires the use of %3$s version %4$s or newer (version %5$s is currently installed).', 'wpsso' ), $info['name_pro'], $info['version'], $um_info['short'], $um_rec_version, $um_version ).' ';

						$text .= sprintf( __( 'If an update for %1$s is not available under the WordPress %2$s page, use the <em>%3$s</em> button in the %4$s settings page to force an immediate refresh of all Pro update information.', 'wpsso' ), $um_info['name'], $wp_updates_page_link, $um_check_updates_transl, $um_settings_page_link );

						break;

					case 'notice-recommend-version':

						$text = sprintf( __( 'You are using %1$s version %2$s &mdash; <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.', 'wpsso' ), $info['app_label'], $info['app_version'], $info['version_url'] ).' '.sprintf( __( 'If possible, please update to the latest %1$s stable release (or at least version %2$s).', 'wpsso' ), $info['app_label'], $info['rec_version'] );

						break;

					default:

						$text = apply_filters( $lca.'_messages_notice', $text, $idx, $info );

						break;
			}
			/**
			 * Misc sidebox messages
			 */
			} elseif ( strpos( $idx, 'column-' ) === 0 ) {

				switch ( $idx ) {

					case 'column-purchase-pro':

						$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

						$text = '<p>'.sprintf( __( '<strong>%s includes:</strong>', 'wpsso' ), $info['short_pro'] ).'</p>';

						$text .= '<ul>';

						$text .= '<li>'.sprintf( __( '%s options for posts, pages, custom post types, terms (categories, tags, custom taxonomies), and user profiles.', 'wpsso' ), $metabox_title ).'</li>';

						$text .= '<li>'.__( 'Advanced features and settings page.', 'wpsso' ).'</li>';

						$text .= '<li>'.__( 'Integration with 3rd party plugins and service APIs.', 'wpsso' ).'</li>';

						$text .= '<li>'.__( 'Ability to purchase Pro add-ons.', 'wpsso' ).'</li>';

						$text .= '</ul>';

						$text .= '<p>'.__( '<strong>Pro licenses never expire</strong> &mdash; you may receive unlimited / lifetime updates and support for each licensed WordPress Site Address.', 'wpsso' ).' '.__( 'How great is that!?', 'wpsso' ).' :-)</p>';

						if ( $this->p->avail['*']['p_dir'] ) {
							$text .= '<p>'.sprintf( __( '<strong>Purchase %s easily and quickly with PayPal</strong> &mdash; license the Pro version immediately after your purchase!', 'wpsso' ), $info['short_pro'] ).'</p>';
						} else {
							$text .= '<p>'.sprintf( __( '<strong>Purchase %s easily and quickly with PayPal</strong> &mdash; update the Free plugin to Pro immediately after your purchase!', 'wpsso' ), $info['short_pro'] ).'</p>';
						}

						break;

					case 'column-help-support':

						$text = '<p>';
						
						$text .= sprintf( __( '<strong>Development of %1$s is driven by user requests</strong> &mdash; we welcome all your comments and suggestions.', 'wpsso' ), $info['short'] ) . ' ;-)';
						
						$text .= '</p>';

						break;

					case 'column-rate-review':

						$text = '<p>';
						
						$text .= __( '<strong>Great ratings are a terrific way to encourage your plugin developers</strong> &mdash; and it only takes a minute.', 'wpsso' ) . ' ';

						$text .= sprintf( __( 'Say "Thank you" %s by rating the plugins you use.', 'wpsso' ),
							'<span class="' . $lca . '-rate-heart"></span>' ) . ' :-)';

						$text .= '</p>';

						break;

					default:

						$text = apply_filters( $lca.'_messages_side', $text, $idx, $info );

						break;
				}
			} else {
				$text = apply_filters( $lca.'_messages', $text, $idx, $info );
			}

			if ( is_array( $info ) && ! empty( $info['is_locale'] ) ) {
				// translators: %s is the wordpress.org URL for the WPSSO User Locale Selector add-on
				$text .= ' ' . sprintf( __( 'This option is localized &mdash; <a href="%s">you may change the WordPress locale</a> to define alternate values for different languages.', 'wpsso' ), 'https://wordpress.org/plugins/wpsso-user-locale/' );
			}

			if ( strpos( $idx, 'tooltip-' ) === 0 && ! empty( $text ) ) {
				$text = '<img src="' . WPSSO_URLPATH . 'images/question-mark.png" width="14" height="14" class="' .
					( isset( $info['class'] ) ? $info['class'] : $this->p->cf['form']['tooltip_class'] ) .
						'" alt="' . esc_attr( $text ) . '" />';
			}

			return $text;
		}
	}
}
