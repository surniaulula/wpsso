<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
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
					'idx' => $idx,
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

			$info['lca'] = $lca = isset( $info['lca'] ) ? $info['lca'] : $this->p->cf['lca'];	// wpsso, wpssoum, etc.

			$url = isset( $this->p->cf['plugin'][$lca]['url'] ) ?	// an array of plugin urls (download, purchase, etc.)
				$this->p->cf['plugin'][$lca]['url'] : array();

			$url['purchase'] = empty( $url['purchase'] ) ?
				'' : add_query_arg( 'utm_source', $idx, $url['purchase'] );

			foreach ( array( 'short', 'name', 'version' ) as $key ) {

				if ( ! isset( $info[$key] ) ) {
					if ( ! isset( $this->p->cf['plugin'][$lca][$key] ) ) {	// just in case
						$info[$key] = null;
					} else {
						$info[$key] = $this->p->cf['plugin'][$lca][$key];
					}
				}

				$info[$key.'_pro'] = SucomUtil::get_pkg_name( $info[$key], 'Pro' );

				$info[$key.'_pro_purchase'] = empty( $url['purchase'] ) ?
					$info[$key.'_pro'] : '<a href="'.$url['purchase'].'">'.$info[$key.'_pro'].'</a>';
			}

			$fb_recommends = __( 'Facebook has published a preference for Open Graph image dimensions of 1200x630px cropped (for retina and high-PPI displays), 600x315px cropped as a minimum (the default settings value), and ignores images smaller than 200x200px.', 'wpsso' );

			/*
			 * All tooltips
			 */
			if ( strpos( $idx, 'tooltip-' ) === 0 ) {
				if ( strpos( $idx, 'tooltip-meta-' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-meta-sharing_url':
							$text = __( 'A custom sharing URL used for the Facebook / Open Graph / Pinterest Rich Pin meta tags, Schema markup, and (optional) social sharing buttons.', 'wpsso' ).' '.__( 'Please make sure any custom URL you enter here is functional and redirects correctly.', 'wpsso' );
						 	break;

						case 'tooltip-meta-schema_title':
							$text = __( 'A custom name / title for the Schema item type\'s name property.', 'wpsso' );
						 	break;

						case 'tooltip-meta-schema_desc':
							$text = __( 'A custom description for the Schema item type\'s description property.', 'wpsso' );
						 	break;

						case 'tooltip-meta-og_title':
							$text = __( 'A custom title for the Facebook / Open Graph, Pinterest Rich Pin, and Twitter Card meta tags (all Twitter Card formats).', 'wpsso' );
						 	break;

						case 'tooltip-meta-og_desc':
							$text = sprintf( __( 'A custom description for the Facebook / Open Graph %1$s meta tag and the default value for all other description meta tags.', 'wpsso' ), '<code>og:description</code>' ).' '.__( 'The default description value is based on the category / tag description or biographical info for users.', 'wpsso' ).' '.__( 'Update and save the custom Facebook / Open Graph description to change the default value of all other description fields.', 'wpsso' );
						 	break;

						case 'tooltip-meta-seo_desc':
							$text = __( 'A custom description for the Google Search / SEO description meta tag.', 'wpsso' );
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

							// use ucfirst() for the french translation which puts the (lowercase) product meta name first 
							$text = sprintf( __( 'You may select a custom %1$s for your product, or leave the default value as-is.', 'wpsso' ), $product_meta_name ).' '.
							ucfirst( sprintf( __( 'The product %1$s may be used in Open Graph product meta tags and Schema markup for products with a single variation.', 'wpsso' ), $product_meta_name ).' '.
							sprintf( __( 'The Schema markup for products with multiple variations will include all product variations with the specific %1$s of each variation.', 'wpsso' ), $product_meta_name ) );
						 	break;	// stop here

						case 'tooltip-meta-og_img_id':
							$text = __( 'A custom image ID to include first, before any featured, attached, or content images.', 'wpsso' );
						 	break;

						case 'tooltip-meta-og_img_url':
							$text = __( 'A custom image URL (instead of an image ID) to include first, before any featured, attached, or content images.', 'wpsso' ).' '.__( 'Please make sure your custom image is large enough, or it may be ignored by social website(s).', 'wpsso' ).' '.$fb_recommends.' <em>'.__( 'This field is disabled if a custom image ID has been selected.', 'wpsso' ).'</em>';
							break;

						case 'tooltip-meta-og_img_max':
							$text = __( 'The maximum number of images to include in the Facebook / Open Graph meta tags.', 'wpsso' ).' '.__( 'There is no advantage in selecting a maximum value greater than 1.', 'wpsso' );
						 	break;

						case 'tooltip-meta-og_vid_embed':

							$option_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_custom_meta',
								_x( 'Video Embed HTML Custom Field', 'option label', 'wpsso' ) );

							$text = 'Custom Video Embed HTML to use for the first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Player\' Twitter Card meta tags. If the URL is from Youtube, Vimeo or Wistia, an API connection will be made to retrieve the preferred sharing URL, video dimensions, and video preview image. The '.$option_page_link.' advanced option also allows a 3rd-party theme or plugin to provide custom Video Embed HTML for this option.';

						 	break;

						case 'tooltip-meta-og_vid_url':

							$option_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_custom_meta',
								_x( 'Video URL Custom Field', 'option label', 'wpsso' ) );

							$text = 'A custom Video URL to include first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Player\' Twitter Card meta tags. If the URL is from Youtube, Vimeo or Wistia, an API connection will be made to retrieve the preferred sharing URL, video dimensions, and video preview image. The '.$option_page_link.' advanced option allows a 3rd-party theme or plugin to provide a custom Video URL value for this option.';

						 	break;

						case 'tooltip-meta-og_vid_title':
						case 'tooltip-meta-og_vid_desc':
							$text = sprintf( __( 'The %1$s video API modules include the video name / title and description <em>when available</em>.', 'wpsso' ), $info['name_pro'] ).' '.__( 'The video name / title and description text is used for Schema JSON-LD markup (extension plugin required), which can be read by both Google and Pinterest.', 'wpsso' );
							break;

						case 'tooltip-meta-og_vid_max':
							$text = __( 'The maximum number of embedded videos to include in the Facebook / Open Graph meta tags.', 'wpsso' ).' '.__( 'There is no advantage in selecting a maximum value greater than 1.', 'wpsso' );
						 	break;

						case 'tooltip-meta-og_vid_prev_img':
							$text = 'When video preview images are enabled and available, they are included in webpage meta tags before any custom, featured, attached, etc. images.';
						 	break;

						case 'tooltip-meta-p_img_id':
							$text = __( 'A custom image ID to include first when the Pinterest crawler is detected, before any featured, attached, or content images.', 'wpsso' );
						 	break;

						case 'tooltip-meta-p_img_url':
							$text = __( 'A custom image URL (instead of an image ID) to include first when the Pinterest crawler is detected.', 'wpsso' ).' <em>'.__( 'This field is disabled if a custom image ID has been selected.', 'wpsso' ).'</em>';
						 	break;

						case 'tooltip-meta-schema_img_id':
							$text = __( 'A custom image ID to include first in the Google / Schema meta tags and JSON-LD markup, before any featured, attached, or content images.', 'wpsso' );
						 	break;

						case 'tooltip-meta-schema_img_url':
							$text = __( 'A custom image URL (instead of an image ID) to include first in the Google / Schema meta tags and JSON-LD markup.', 'wpsso' ).' <em>'.__( 'This field is disabled if a custom image ID has been selected.', 'wpsso' ).'</em>';
						 	break;

						case 'tooltip-meta-schema_img_max':
							$text = __( 'The maximum number of images to include in the Google / Schema meta tags and JSON-LD markup.', 'wpsso' );
						 	break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip_meta', $text, $idx, $info );
							break;
					}	// end of tooltip-user switch
				/*
				 * Post Meta settings
				 */
				} elseif ( strpos( $idx, 'tooltip-post-' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-post-og_art_section':
							$text = __( 'A custom topic, different from the default Article Topic selected in the General Settings.', 'wpsso' ).' '.sprintf( __( 'The Facebook / Open Graph %1$s meta tag must be an "article" to enable this option.', 'wpsso' ), '<code>og:type</code>' ).' '.sprintf( __( 'This value will be used in the %1$s Facebook / Open Graph and Pinterest Rich Pin meta tags. Select "[None]" if you prefer to exclude the %1$s meta tag.', 'wpsso' ), '<code>article:section</code>' );
						 	break;

						case 'tooltip-post-og_desc':
							$text = sprintf( __( 'A custom description for the Facebook / Open Graph %1$s meta tag and the default value for all other description meta tags.', 'wpsso' ), '<code>og:description</code>' ).' '.__( 'The default description value is based on the excerpt (if one is available) or content.', 'wpsso' ).' '.__( 'Update and save the custom Facebook / Open Graph description to change the default value of all other description fields.', 'wpsso' );
						 	break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip_post', $text, $idx, $info );
							break;
					}	// end of tooltip-post switch
				/*
				 * Site settings
				 */
				} elseif ( strpos( $idx, 'tooltip-site_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-site_name':

							$settings_url = get_admin_url( null, 'options-general.php' );

							$text = sprintf( __( 'The WordPress Site Name is used for the Facebook / Open Graph and Pinterest Rich Pin %1$s meta tag. You may override <a href="%2$s">the default WordPress Site Title value</a>.', 'wpsso' ), '<code>og:site_name</code>', $settings_url );

							break;

						case 'tooltip-site_desc':

							$settings_url = get_admin_url( null, 'options-general.php' );

							$text = sprintf( __( 'The WordPress tagline is used as a description for the blog (non-static) front page, and as a fallback for the Facebook / Open Graph and Pinterest Rich Pin %1$s meta tag.', 'wpsso' ), '<code>og:description</code>' ).' '.sprintf( __( 'You may override <a href="%1$s">the default WordPress Tagline value</a> here, to provide a longer and more complete description of your website.', 'wpsso' ), $settings_url );

							break;

						case 'tooltip-site_org_type':
							$text = __( 'You may select a more descriptive Schema type from the Organization sub-types (default is Organization).', 'wpsso' );
							break;

						case 'tooltip-site_place_id':
							if ( isset( $this->p->cf['plugin']['wpssoplm'] ) ) {
								$plm_info = $this->p->cf['plugin']['wpssoplm'];
								$text = sprintf( __( 'Select an optional Place / Location address for this Organization (requires the %s extension).', 'wpsso' ), '<a href="'.$plm_info['url']['home'].'">'.$plm_info['name'].'</a>' );
							}
							break;
					}
				/*
				 * Open Graph settings
				 */
				} elseif ( strpos( $idx, 'tooltip-og_' ) === 0 ) {
					switch ( $idx ) {
						/*
						 * 'Priority Media' settings
						 */
						case 'tooltip-og_img_dimensions':
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'getting defaults for og_img (width, height, crop)' );
							}
							$def_dimensions = $this->p->opt->get_defaults( 'og_img_width' ).'x'.
								$this->p->opt->get_defaults( 'og_img_height' ).' '.
								( $this->p->opt->get_defaults( 'og_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = 'The image dimensions used in the Facebook / Open Graph meta tags (the default dimensions are '.$def_dimensions.'). '.$fb_recommends.' Note that images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.';
							break;

						case 'tooltip-og_def_img_id':
							$text = __( 'An image ID and media library selection for your default / fallback website image.', 'wpsso' ).' '.__( 'The default image is used for index / archive pages, and as a fallback for Posts and Pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' );
							break;

						case 'tooltip-og_def_img_url':
							$text = __( 'You can enter a default image URL (including the http:// prefix) instead of choosing an image ID &mdash; if a default image ID is specified, the image URL option is disabled.', 'wpsso' ).' <strong>'.__( 'The image URL option allows you to use an image outside of a managed collection (WordPress Media Library or NextGEN Gallery), and/or a smaller logo style image.', 'wpsso' ).'</strong> '.sprintf( __( 'The image should be at least %s or more in width and height.', 'wpsso' ), $this->p->cf['head']['limit_min']['og_img_width'].'x'.$this->p->cf['head']['limit_min']['og_img_height'] ).' '.__( 'The default image is used for index / archive pages, and as a fallback for Posts and Pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' );
							break;

						case 'tooltip-og_def_img_on_index':
							$text = 'Check this option to force the default image on index webpages (blog front page, archives, categories). If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$info['short'].' will use image(s) from the first entry on the webpage (default is checked).';
							break;

						case 'tooltip-og_def_img_on_search':
							$text = 'Check this option to force the default image on search results. If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$info['short'].' will use image(s) returned in the search results (default is unchecked).';
							break;

						case 'tooltip-og_ngg_tags':
							$text = 'If the <em>featured</em> image in a Post or Page is from a NextGEN Gallery, then add that image\'s tags to the Facebook / Open Graph and Pinterest Rich Pin tag list (default is unchecked).';
							break;

						case 'tooltip-og_img_max':
							$text = 'The maximum number of images to include in the Facebook / Open Graph meta tags -- this includes the <em>featured</em> image, <em>attached</em> images, and any images found in the content. If you select "0", then no images will be listed in the Facebook / Open Graph meta tags (<strong>not recommended</strong>). If no images are listed in your meta tags, social websites may choose an unsuitable image from your webpage (including headers, sidebars, etc.). There is no advantage in selecting a maximum value greater than 1.';
							break;

						case 'tooltip-og_vid_max':
							$text = 'The maximum number of videos, found in the Post or Page content, to include in the Facebook / Open Graph and Pinterest Rich Pin meta tags. If you select "0", then no videos will be listed in the Facebook / Open Graph and Pinterest Rich Pin meta tags. There is no advantage in selecting a maximum value greater than 1.';
							break;

						case 'tooltip-og_vid_https':
							$text = 'Use an HTTPS connection whenever possible to retrieve information about videos from YouTube, Vimeo, Wistia, etc. (default is checked).';
							break;

						case 'tooltip-og_vid_autoplay':
							$text = 'When possible, add or modify the "autoplay" argument of video URLs in webpage meta tags (default is checked).';
							break;

						case 'tooltip-og_vid_prev_img':
							$text = 'Include video preview images in the webpage meta tags (default is unchecked). When video preview images are enabled and available, they are included before any custom, featured, attached, etc. images.';
							break;

						case 'tooltip-og_vid_html_type':
							$text = 'Include additional Open Graph meta tags for the embed video URL as a text/html video type (default is checked).';
							break;

						/*
						 * 'Description' settings
						 */
						case 'tooltip-og_post_type':
							$text = __( 'The default Open Graph type for the WordPress post object (posts, pages, and custom post types). Custom post types with a matching Open Graph type name (article, book, place, product, etc.) will use that type name instead of the default selected here. Please note that each type has a unique set of meta tags, so by selecting "website" here, you are excluding all "article" related meta tags (<code>article:author</code>, <code>article:section</code>, etc.).', 'wpsso' );
							break;

						case 'tooltip-og_art_section':
							$text = __( 'The topic that best describes the Posts and Pages on your website.', 'wpsso' ).' '.sprintf( __( 'This value will be used in the %1$s Facebook / Open Graph and Pinterest Rich Pin meta tags. Select "[None]" if you prefer to exclude the %1$s meta tag.', 'wpsso' ), '<code>article:section</code>' ).' '.__( 'The Pro version also allows you to select a custom Topic for each individual Post and Page.', 'wpsso' );
							break;

						case 'tooltip-og_title_sep':
							$text = 'One or more characters used to separate values (category parent names, page numbers, etc.) within the Facebook / Open Graph and Pinterest Rich Pin title string (the default is the hyphen "'.$this->p->opt->get_defaults( 'og_title_sep' ).'" character).';
							break;

						case 'tooltip-og_title_len':
							$text = 'The maximum length of text used in the Facebook / Open Graph and Rich Pin title tag (default is '.$this->p->opt->get_defaults( 'og_title_len' ).' characters).';
							break;

						case 'tooltip-og_desc_len':
							$text = 'The maximum length of text used in the Facebook / Open Graph and Rich Pin description tag. The length should be at least '.$this->p->cf['head']['limit_min']['og_desc_len'].' characters or more, and the default is '.$this->p->opt->get_defaults( 'og_desc_len' ).' characters.';
							break;

						case 'tooltip-og_page_title_tag':
							$text = 'Add the title of the <em>Page</em> to the Facebook / Open Graph and Pinterest Rich Pin article tag and Hashtag list (default is unchecked). If the Add Page Ancestor Tags option is checked, all the titles of the ancestor Pages will be added as well. This option works well if the title of your Pages are short (one or two words) and subject-oriented.';
							break;

						case 'tooltip-og_page_parent_tags':
							$text = 'Add the WordPress tags from the <em>Page</em> ancestors (parent, parent of parent, etc.) to the Facebook / Open Graph and Pinterest Rich Pin article tags and Hashtag list (default is unchecked).';
							break;

						case 'tooltip-og_desc_hashtags':
							$text = 'The maximum number of tag names (converted to hashtags) to include in the Facebook / Open Graph and Pinterest Rich Pin description, tweet text, and social captions. Each tag name is converted to lowercase with whitespaces removed.  Select "0" to disable the addition of hashtags.';
							break;

						/*
						 * 'Authorship' settings
						 */
						case 'tooltip-og_author_field':
							$text = sprintf( __( 'Select which contact field to use from the author\'s WordPress profile page for the Facebook / Open Graph %1$s meta tag. The preferred setting is the Facebook URL field (default value). Select "[None]" if you prefer to exclude the %1$s meta tag, and prevent Facebook from including author attribution in shared links.', 'wpsso' ), '<code>article:author</code>' );
							break;

						case 'tooltip-og_author_fallback':
							$text = sprintf( __( 'If the %1$s is not a valid URL, then fallback to using the author archive URL from this website (example: "%2$s").', 'wpsso' ), _x( 'Author Profile URL Field', 'option label', 'wpsso' ), trailingslashit( site_url() ).'author/username' ).' '.__( 'Uncheck this option to disable the author URL fallback feature (default is unchecked).', 'wpsso' );
							break;

						case 'tooltip-og_author_gravatar':	// aka plugin_gravatar_api
							$text = 'Check this option to include the author\'s Gravatar image in meta tags for author index / archive webpages (default is checked).';
							break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip_og', $text, $idx, $info );
							break;
					}	// end of tooltip-og switch
				/*
				 * Advanced plugin settings
				 */
				} elseif ( strpos( $idx, 'tooltip-plugin_' ) === 0 ) {
					switch ( $idx ) {
						/*
						 * 'Plugin Settings' settings
						 */
						case 'tooltip-plugin_preserve':	// Preserve Settings on Uninstall
							$text = sprintf( __( 'Check this option if you would like to preserve the %s settings when you <em>uninstall</em> the plugin (default is unchecked).', 'wpsso' ), $info['short'] );
							break;

						case 'tooltip-plugin_debug':	// Add Hidden Debug Messages
							$text = __( 'Add debugging messages as hidden HTML comments to back-end and front-end webpages (default is unchecked).', 'wpsso' );
							break;

						case 'tooltip-plugin_hide_pro':	// Hide All Pro Settings
							$text = __( 'Remove Pro version preview options from settings pages and metaboxes (default is unchecked).',
								'wpsso' ).' '.
							sprintf( __( 'Enabling this option also re-orders the %1$s metabox tabs for your convenience &ndash; moving the %2$s, %3$s, and %4$s tabs topmost.',
								'wpsso' ), _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' ),
									_x( 'Preview', 'metabox tab', 'wpsso' ),
									_x( 'Head Tags', 'metabox tab', 'wpsso' ),
									_x( 'Validate', 'metabox tab', 'wpsso' ) ).' '.
							sprintf( __( 'Please note that some metaboxes and tabs may be empty, showing only a "<em>%s</em>" message, after enabling this option.',
								'wpsso' ), __( 'No options available.', 'wpsso' ) );
							break;

						case 'tooltip-plugin_show_opts':	// Options to Show by Default
							$text = sprintf( __( 'Select the set of options to display by default in %1$s settings pages and %2$s metabox.',
								'wpsso' ), $info['short'], _x( $this->p->cf['meta']['title'], 'metabox title',
									'wpsso' ) ).' '.
							__( 'The basic view shows only the most commonly used options, and includes a link to temporarily unhide all options.',
								'wpsso' ).' '.
							__( 'Showing all available options by default could prove to be overwhelming for new users.',
								'wpsso' );
							break;

						/*
						 * 'Content and Filters' settings
						 */
						case 'tooltip-plugin_filter_title':
							$text = __( 'The title values provided by WordPress may include modifications by themes and/or SEO plugins (appending the site name, for example, is common practice). Uncheck this option to use the original title value without modifications (default is unchecked).', 'wpsso' );
							break;

						case 'tooltip-plugin_filter_content':
							$text = __( 'Apply the WordPress "the_content" filter to the content text (default is checked).', 'wpsso' ).' '.__( 'The WordPress content filter formats text and expands shortcodes, which may be required to include additional images and videos.', 'wpsso' );
							break;

						case 'tooltip-plugin_filter_excerpt':
							$text = __( 'Apply the WordPress "get_the_excerpt" filter to the excerpt text (default is unchecked). Check this option if you use shortcodes in your excerpts, for example.', 'wpsso' );
							break;

						case 'tooltip-plugin_p_strip':
							$text = sprintf( __( 'If a post / page does not have an excerpt, and this option is checked, %s will ignore all text before the first paragraph tag in the content.', 'wpsso' ), $info['short'] ).' '.__( 'If an excerpt is available, then this option is ignored and the complete text of the excerpt is used (excerpts have priority over the content text).', 'wpsso' );
							break;

						case 'tooltip-plugin_use_img_alt':
							$text = sprintf( __( 'If the content text is comprised entirely of HTML tags (which must be removed to create text-only descriptions), %s can extract and use image <em>alt</em> attributes instead of returning an empty description.', 'wpsso' ), $info['short'] );
							break;

						case 'tooltip-plugin_img_alt_prefix':
							$text = sprintf( __( 'When use of image <em>alt</em> attributes is enabled, %s can prefix the attribute text with an optional string.', 'wpsso' ), $info['short'] ).' '.__( 'Leave this value empty to prevent image alt attribute text from being prefixed.', 'wpsso' );
							break;

						case 'tooltip-plugin_p_cap_prefix':
							$text = sprintf( __( '%s can add a prefix to paragraphs found with the "wp-caption-text" class.',
								'wpsso' ), $info['short'] ).' '.
							__( 'Leave this value empty to prevent caption paragraphs from being prefixed.',
								'wpsso' );
							break;

						case 'tooltip-plugin_embedded_media':
							$text = __( 'Check the content for embedded media URLs from supported media providers (Vimeo, Wistia, YouTube, etc.). If a supported media URL is found, an API connection to the provider will be made to retrieve information about the media (preview image URL, flash player URL, oembed player URL, the video width / height, etc.).', 'wpsso' );
							break;

						/*
						 * 'Custom Meta' settings
						 */
						case 'tooltip-plugin_show_columns':
							$text = __( 'Additional columns can be included in admin list pages to show the Schema type ID, Open Graph image, etc. When a column is enabled, <strong>individual users can also hide that column</strong> by using the <em>Screen Options</em> tab on each admin list page.', 'wpsso' );
							break;

						case 'tooltip-plugin_add_to':
							$text = sprintf( __( 'Add or remove the %s metabox from admin editing pages for posts, pages, custom post types, terms (categories and tags), and user profile pages.', 'wpsso' ), _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' ) );
							break;

						case 'tooltip-plugin_wpseo_social_meta':

							$text = __( 'Read the Yoast SEO custom social meta text for Posts, Terms, and Users.', 'wpsso' ).' '.__( 'This option is checked by default if the Yoast SEO plugin is active, or its settings are found in the database.', 'wpsso' );

							break;

						case 'tooltip-plugin_def_currency':
							$text = __( 'The default currency used for money related options (product price, job salary, etc.).', 'wpsso' );
							break;

						case 'tooltip-plugin_cf_img_url':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'an image URL', 'tooltip fragment', 'wpsso' ),
									_x( 'Image URL', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_vid_url':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a video URL (not HTML code)', 'tooltip fragment', 'wpsso' ),
									_x( 'Video URL', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_vid_embed':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'video embed HTML code (not a URL)', 'tooltip fragment', 'wpsso' ),
									_x( 'Video Embed HTML', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_add_type_urls':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'additional Schema type URLs', 'tooltip fragment', 'wpsso' ),
									_x( 'Additional Type URLs', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_recipe_ingredients':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'recipe ingredients', 'tooltip fragment', 'wpsso' ),
									_x( 'Recipe Ingredients', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_recipe_instructions':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'recipe instructions', 'tooltip fragment', 'wpsso' ),
									_x( 'Recipe Instructions', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_product_avail':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product availability', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Availability', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_product_brand':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product brand', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Brand', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_product_color':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product color', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Color', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_product_condition':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product condition', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Condition', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_product_currency':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product currency', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Currency', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_product_material':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product material', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Material', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_product_price':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product price', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Price', 'option label', 'wpsso' ) );
							}
							// no break - fall through
						case 'tooltip-plugin_cf_product_size':
							if ( ! isset( $plugin_cf_info ) ) {
								$plugin_cf_info = array(
									_x( 'a product size', 'tooltip fragment', 'wpsso' ),
									_x( 'Product Size', 'option label', 'wpsso' ) );
							}
							// no break - fall through

							$text = sprintf( __( 'If your theme or another plugin provides a custom field for %1$s, you may enter its custom field name here.', 'wpsso' ), $plugin_cf_info[0] ).' '.sprintf( __( 'If a custom field matching that name is found, its value may be used for the %1$s option in the %2$s metabox.', 'wpsso' ), $plugin_cf_info[1], _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' ) );
							break;	// stop here

						/*
						 * 'Integration' settings
						 */
						case 'tooltip-plugin_honor_force_ssl':	// Honor the FORCE_SSL Constant
							$text = sprintf( __( 'If the FORCE_SSL constant is defined as true, %s can redirect front-end URLs from HTTP to HTTPS when required (default is checked).', 'wpsso' ), $info['short'] );
							break;

						case 'tooltip-plugin_check_head':

							$check_head_count = (int) SucomUtil::get_const( 'WPSSO_CHECK_HEADER_COUNT', 6 );

							$text = sprintf( __( 'When editing Posts and Pages, %1$s can check the head section of webpages for conflicting and/or duplicate HTML tags. After %2$d <em>successful</em> checks, no additional checks will be performed &mdash; until the theme and/or any plugin is updated, when another %2$d checks are performed.', 'wpsso' ), $info['short'], $check_head_count );

							break;

						case 'tooltip-plugin_html_attr_filter':
							$text = $info['short'].' hooks the "language_attributes" filter by default to add / modify required Open Graph namespace prefix values. The "language_attributes" WordPress function and filter are used by most themes &mdash; if the namespace prefix values are missing from your &amp;lt;html&amp;gt; element, make sure your header template(s) use the language_attributes() function. Leaving this option blank disables the addition of Open Graph namespace values. Example template code: <pre><code>&amp;lt;html &amp;lt;?php language_attributes(); ?&amp;gt;&amp;gt;</code></pre>';
							break;

						case 'tooltip-plugin_head_attr_filter':
							$text = $info['short'].' hooks the "head_attributes" filter by default to add / modify the <code>&amp;lt;head&amp;gt;</code> element attributes for the Schema itemscope / itemtype markup. If your theme offers a filter for <code>&amp;lt;head&amp;gt;</code> element attributes, enter its name here (most themes do not). Alternatively, you can add an action manually in your header templates to call the "head_attributes" filter. Example code: <pre><code>&amp;lt;head &amp;lt;?php do_action( \'add_head_attributes\' ); ?&amp;gt;&amp;gt;</code></pre>';
							break;

						case 'tooltip-plugin_filter_lang':
							$text = $info['name_pro'].' can use the WordPress locale to select the correct language for the Facebook / Open Graph and Pinterest Rich Pin meta tags'.( empty( $this->p->avail['p_ext']['ssb'] ) ? '' : ', along with the Google, Facebook, and Twitter social sharing buttons' ).'. If your website is available in multiple languages, this can be a useful feature. Uncheck this option to ignore the WordPress locale and always use the configured language.'; 
							break;

						case 'tooltip-plugin_create_wp_sizes':
							$text = __( 'Automatically create missing and/or incorrect images in the WordPress Media Library (default is checked).', 'wpsso' );
							break;

						case 'tooltip-plugin_check_img_dims':

							$settings_page_link = $this->p->util->get_admin_url( 'image-dimensions',
								_x( 'Social and Search Image Dimensions', 'lib file description', 'wpsso' ) );

							$text = sprintf( __( 'When this option is enabled, full size images used for meta tags and Schema markup must be equal to (or larger) than the image dimensions you\'ve defined in the %s settings &mdash; images that do not meet or exceed the minimum requirements will be ignored.', 'nextgen-faceboook' ), $settings_page_link ).' '.__( '<strong>Enabling this option is highly recommended</strong> &mdash; the option is disabled by default to avoid excessive warnings on sites with small / thumbnail images in their media library.', 'wpsso' );

							break;

						case 'tooltip-plugin_upscale_images':

							$text = __( 'WordPress does not upscale / enlarge images &mdash; WordPress can only create smaller images from larger full size originals.', 'wpsso' ).' '.__( 'Upscaled images do not look as sharp or clear, and if enlarged too much, will look fuzzy and unappealing &mdash; not something you want to promote on social and search sites.', 'wpsso' ).' '.sprintf( __( '%1$s includes a feature that allows upscaling of WordPress Media Library images for %2$s image sizes (up to a maximum upscale percentage).', 'wpsso' ), $info['name_pro'], $info['short'] ).' <strong>'.__( 'Do not enable this option unless you want to publish lower quality images on social and search sites.', 'wpsso' ).'</strong>';

							break;

						case 'tooltip-plugin_upscale_img_max':

							$upscale_max = WpssoConfig::$cf['opt']['defaults']['plugin_upscale_img_max'];	// use original un-filtered value

							$text = sprintf( __( 'When upscaling of %1$s image sizes is allowed, %2$s can make sure smaller images are not upscaled beyond reason, which would publish very low quality / fuzzy images on social and search sites (the default maximum is %3$s%%).', 'wpsso' ), $info['short'], $info['name_pro'], $upscale_max ).' '.__( 'If an image needs to be upscaled beyond this maximum, in either width or height, the image will not be upscaled.', 'wpsso' );

							break;

						case 'tooltip-plugin_shortcodes':

							$text = 'Enable the '.$info['short'].' shortcode features (default is checked).';

							break;

						case 'tooltip-plugin_widgets':

							$text = 'Enable the '.$info['short'].' widget features (default is checked).';

							break;

						case 'tooltip-plugin_page_excerpt':

							$text = 'Enable the excerpt editing metabox for Pages. Excerpts are optional hand-crafted summaries of your content that '.$info['short'].' can use as a default description value.';

							break;

						case 'tooltip-plugin_page_tags':

							$text = 'Enable the tags editing metabox for Pages. Tags are optional keywords that highlight the content subject(s), often used for searches and "tag clouds". '.$info['short'].' converts tags into hashtags for some social websites (Twitter, Facebook, Google+, etc.).';

							break;

						/*
						 * 'Cache Settings' settings
						 */
						case 'tooltip-plugin_head_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_head_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'Head meta tags and Schema markup are saved to the WordPress transient cache to optimize performance.',
								'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).',
									'wpsso' ), $cache_exp, $cache_diff );
							break;

						case 'tooltip-plugin_content_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_content_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'Filtered post content is saved to the WordPress <em>non-persistent</em> object cache to optimize performance.',
								'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).',
									'wpsso' ), $cache_exp, $cache_diff );
							break;

						case 'tooltip-plugin_imgsize_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_imgsize_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'The size for image URLs (not image IDs) is retrieved and saved to the WordPress transient cache to optimize performance and network bandwidth.',
								'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).',
									'wpsso' ), $cache_exp, $cache_diff );
							break;

						case 'tooltip-plugin_short_url_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_short_url_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'Shortened URLs are saved to the WordPress transient cache to optimize performance and API connections.',
								'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).',
									'wpsso' ), $cache_exp, $cache_diff );
							break;

						case 'tooltip-plugin_topics_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_topics_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'The filtered article topics array is saved to the WordPress transient cache to optimize performance and disk access.', 'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp, $cache_diff );
							break;

						case 'tooltip-plugin_types_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_types_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'The filtered Schema types array is saved to the WordPress transient cache to optimize performance.',
								'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).',
									'wpsso' ), $cache_exp, $cache_diff );
							break;

						case 'tooltip-plugin_show_purge_count':
							$text = __( 'Report the number of objects removed from the WordPress cache when posts, terms, and users are updated.', 'wpsso' );
							break;

						case 'tooltip-plugin_clear_on_save':	// Clear All Cache on Save Settings
							$text = sprintf( __( 'Automatically clear all known plugin cache(s) when saving the %s settings (default is checked).', 'wpsso' ), $info['short'] );
							break;

						case 'tooltip-plugin_clear_short_urls':
							$cache_exp = (int) apply_filters( $lca.'_cache_expire_short_url', $this->p->options['plugin_short_url_cache_exp'] );
							$text = sprintf( __( 'Clear all shortened URLs when clearing all %s transients from the WordPress database (default is unchecked).', 'wpsso' ), $info['short'] ).' '.sprintf( __( 'Shortened URLs are cached for %s to minimize external service API calls. Updating all shortened URLs at once may exceed API call limits imposed by your shortening service provider.', 'wpsso' ), human_time_diff( 0, $cache_exp ) );
							break;

						case 'tooltip-plugin_clear_for_comment':	// Clear Post Cache for New Comment

							$text = __( 'Automatically clear the post cache when a new comment is added, or the status of an existing comment is changed.', 'wpsso' );

							break;

						/*
						 * 'Service APIs' (URL Shortening) settings
						 */
						case 'tooltip-plugin_shortener':
							$text = sprintf( __( 'A preferred URL shortening service for %s plugin filters and/or extensions that may need to shorten URLs &mdash; don\'t forget to define the service API keys for the URL shortening service of your choice.', 'wpsso' ), $info['short'] );
							break;

						case 'tooltip-plugin_shortlink':
							$text = __( 'The <em>Get Shortlink</em> button and the shortlink meta tag on Posts / Pages provides the shortened sharing URL instead of the default WordPress shortlink URL.', 'wpsso' );
							break;

						case 'tooltip-plugin_min_shorten':
							$text = sprintf( __( 'URLs shorter than this length will not be shortened (the default suggested by Twitter is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'plugin_min_shorten' ) );
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

						case 'tooltip-plugin_google_api_key':
							$text = sprintf( __( 'The Google BrowserKey value for this website (project). If you don\'t already have a Google project, visit <a href="%s">Google\'s Cloud Console</a> and create a new project for your website (use the "Select a project" drop-down).', 'wpsso' ), 'https://console.developers.google.com/start' );
							break;

						case 'tooltip-plugin_google_shorten':
							$text = sprintf( __( 'In order to use Google\'s URL Shortener API service, you must <em>Enable</em> the URL Shortener API from <a href="%s">Google\'s Cloud Console</a> (under the project\'s <em>API &amp; auth / APIs / URL Shortener API</em> settings page).', 'wpsso' ), 'https://console.developers.google.com/start' ).' '.__( 'Confirm that you have enabled Google\'s URL Shortener API service by checking the "Yes" option value.', 'wpsso' );
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
				/*
				 * Publisher 'Facebook' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-fb_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-fb_publisher_url':
							$text = sprintf( __( 'If you have a <a href="%1$s">Facebook Business Page for your website / business</a>, you may enter its URL here (for example, the Facebook Business Page URL for %2$s is <a href="%3$s">%4$s</a>).', 'wpsso' ), 'https://www.facebook.com/business', 'Surnia Ulula', 'https://www.facebook.com/SurniaUlulaCom', 'https://www.facebook.com/SurniaUlulaCom' ).' '.__( 'The Facebook Business Page URL will be used in Open Graph <em>article</em> webpages and in the site\'s Schema Organization markup.', 'wpsso' ).' '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
							break;

						case 'tooltip-fb_admins':
							$text = sprintf( __( 'The %1$s are used by Facebook to allow access to <a href="%2$s">Facebook Insight</a> data for your website. Note that these are <strong>user account names, not Facebook Page names</strong>. Enter one or more Facebook user names, separated with commas. When viewing your own Facebook wall, your user name is located in the URL (for example, https://www.facebook.com/<strong>user_name</strong>). Enter only the user names, not the URLs.', 'wpsso' ), _x( 'Facebook Admin Username(s)', 'option label', 'wpsso' ), 'https://developers.facebook.com/docs/insights/' ).' '.sprintf( __( 'You may update your Facebook user name in the <a href="%1$s">Facebook General Account Settings</a>.', 'wpsso' ), 'https://www.facebook.com/settings?tab=account&section=username&view' );
							break;

						case 'tooltip-fb_app_id':
							$text = sprintf( __( 'If you have a <a href="%1$s">Facebook Application ID for your website</a>, enter it here. The Facebook Application ID will appear in webpage meta tags and is used by Facebook to allow access to <a href="%2$s">Facebook Insight</a> data for accounts associated with that Application ID.', 'wpsso' ), 'https://developers.facebook.com/apps', 'https://developers.facebook.com/docs/insights/' );
							break;

						case 'tooltip-fb_author_name':
							$text = sprintf( __( '%1$s uses the Facebook contact field value in the author\'s WordPress profile for %2$s Open Graph meta tags. This allows Facebook to credit an author on shares, and link their Facebook page URL.', 'wpsso' ), $info['short'], '<code>article:author</code>' ).' '.sprintf( __( 'If an author does not have a Facebook page URL, %1$s can fallback and use the <em>%2$s</em> instead (the recommended value is "Display Name").', 'wpsso' ), $info['short'], _x( 'Author Name Format', 'option label', 'wpsso' ) );
							break;

						case 'tooltip-fb_locale':
							$text = sprintf( __( 'Facebook does not support all WordPress locale values. If the Facebook debugger returns an error parsing the %1$s meta tag, you may have to choose an alternate Facebook language for that WordPress locale.', 'wpsso' ), '<code>og:locale</code>' );
							break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip_fb', $text, $idx, $info );
							break;
					}	// end of tooltip-fb switch
				/*
				 * Publisher 'Google' / SEO settings
				 */
				} elseif ( strpos( $idx, 'tooltip-seo_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-seo_publisher_url':
							$text = 'If you have a <a href="http://www.google.com/+/business/">Google+ Business Page for your website / business</a>, you may enter its URL here (for example, the Google+ Business Page URL for Surnia Ulula is <a href="https://plus.google.com/+SurniaUlula/">https://plus.google.com/+SurniaUlula/</a>). The Google+ Business Page URL will be used in a link relation head tag, and the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
							break;

						case 'tooltip-seo_desc_len':
							$text = 'The maximum length of text used for the Google Search / SEO description meta tag. The length should be at least '.$this->p->cf['head']['limit_min']['og_desc_len'].' characters or more (the default is '.$this->p->opt->get_defaults( 'seo_desc_len' ).' characters).';
							break;

						case 'tooltip-seo_author_field':
							$text = $info['short'].' can include an <em>author</em> and <em>publisher</em> links in the webpage head section. These are not Facebook / Open Graph and Pinterest Rich Pin meta property tags &mdash; they are used primarily by Google\'s search engine to associate Google+ profiles with search results. Select which field to use from the author\'s profile for the <em>author</em> link tag.';
							break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip_seo', $text, $idx, $info );
							break;
					}	// end of tooltip-google switch
				/*
				 * Publisher 'Schema' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-schema_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-schema_add_noscript':
							$text = 'When additional schema properties are available (product ratings, recipe ingredients, etc.), one or more <code>noscript</code> containers may be included in the webpage head section. <code>noscript</code> containers are read correctly by Google and Pinterest, but the W3C Validator will show errors for the included meta tags (these errors can be safely ignored). The <code>noscript</code> containers are always disabled for AMP webpages, and always enabled for the Pinterest crawler.';
							break;

						case 'tooltip-schema_knowledge_graph':

							$settings_page_link = $this->p->util->get_admin_url( 'social-accounts',
								_x( 'Website Social Pages and Accounts', 'lib file description', 'wpsso' ) );

							$text = __( 'Include Website, Organization, and/or Person Schema markup in the front page for Google\'s Knowledge Graph.', 'wpsso' ).' '.__( 'The Website markup includes the site name, alternate site name, site URL and search query URL.', 'wpsso' ).' '.sprintf( __( 'Developers can hook the "%s" filter to modify the site search URL (or disable its addition by returning false).', 'wpsso' ), $lca.'_json_ld_search_url' ).' '.sprintf( __( 'The Organization markup includes all URLs entered on the %s settings page.', 'wpsso' ), $settings_page_link ).' '.__( 'The Person markup includes all contact method URLs entered in the user\'s WordPress profile page.', 'wpsso' );
							break;

						case 'tooltip-schema_person_id':
							$text = __( 'Select a site owner for the optional Person markup included in the front page.', 'wpsso' ).' '.__( 'The Person markup includes all contact method URLs entered in the user\'s WordPress profile page.', 'wpsso' );
							break;

						case 'tooltip-schema_alt_name':
							$text = __( 'An alternate name for your Website that you want Google to consider (optional).', 'wpsso' );
							break;

						case 'tooltip-schema_logo_url':
							$text = 'A URL for the website / organization\'s logo image that Google can use in search results and its <em>Knowledge Graph</em>.';
							break;

						case 'tooltip-schema_banner_url':
							$text = 'A URL for the website / organization\'s banner image &mdash; <strong>measuring exactly 600x60px</strong> &mdash; that Google / Google News can use to display content from Schema Article webpages.';
							break;

						case 'tooltip-schema_img_max':
							$text = 'The maximum number of images to include in the Google / Schema markup -- this includes the <em>featured</em> or <em>attached</em> images, and any images found in the Post or Page content. If you select "0", then no images will be listed in the Google / Schema meta tags (<strong>not recommended</strong>).';
							break;

						case 'tooltip-schema_img_dimensions':
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'getting defaults for schema_img (width, height, crop)' );
							$def_dimensions = $this->p->opt->get_defaults( 'schema_img_width' ).'x'.
								$this->p->opt->get_defaults( 'schema_img_height' ).' '.
								( $this->p->opt->get_defaults( 'schema_img_crop' ) == 0 ? 'uncropped' : 'cropped' );
							$text = 'The image dimensions used in the Google / Schema meta tags and JSON-LD markup (the default dimensions are '.$def_dimensions.'). The minimum image width required by Google is 696px for the resulting resized image. If you do not choose to crop this image size, make sure the height value is large enough for portrait / vertical images.';
							break;

						case 'tooltip-schema_desc_len':
							$text = 'The maximum length of text used for the Google+ / Schema description meta tag. The length should be at least '.$this->p->cf['head']['limit_min']['og_desc_len'].' characters or more (the default is '.$this->p->opt->get_defaults( 'schema_desc_len' ).' characters).';
							break;

						case 'tooltip-schema_author_name':
							$text = sprintf( __( 'Select an <em>%1$s</em> for the author / Person markup, or "[None]" to disable this feature (the recommended value is "Display Name").', 'wpsso' ), _x( 'Author Name Format', 'option label', 'wpsso' ) );
							break;

						case 'tooltip-schema_type_for_home_index':
							$text = sprintf( __( 'Select the Schema type for a blog (non-static) front page. The default Schema type is %s.',
								'wpsso' ), 'https://schema.org/CollectionPage' );
							break;

						case 'tooltip-schema_type_for_home_page':
							$text = sprintf( __( 'Select the Schema type for a static front page. The default Schema type is %s.',
								'wpsso' ), 'https://schema.org/WebSite' );
							break;

						case 'tooltip-schema_type_for_archive_page':
							$text = sprintf( __( 'Select the Schema type for archive pages (Category, Tags, etc.). The default Schema type is %s.',
								'wpsso' ), 'https://schema.org/CollectionPage' );
							break;

						case 'tooltip-schema_type_for_user_page':
							$text = sprintf( __( 'Select the Schema type for user / author pages. The default Schema type is %s.',
								'wpsso' ), 'https://schema.org/ProfilePage' );
							break;

						case 'tooltip-schema_type_for_search_page':
							$text = sprintf( __( 'Select the Schema type for search results pages. The default Schema type is %s.',
								'wpsso' ), 'https://schema.org/SearchResultsPage' );
							break;

						case 'tooltip-schema_type_for_ptn':
							$text = __( 'Select the Schema type for each WordPress post type. The Schema type defines the item type for Schema JSON-LD markup and/or meta tags in the webpage head section.', 'wpsso' );
							break;

						case 'tooltip-schema_review_item_type':
							$text = __( 'The default Schema Item Type for reviewed items.', 'wpsso' );
							break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip_schema', $text, $idx, $info );
							break;
					}	// end of tooltip-google switch
				/*
				 * Publisher 'Twitter Card' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-tc_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-tc_site':
							$text = 'The <a href="https://business.twitter.com/">Twitter @username for your website and/or business</a> (not your personal Twitter @username). As an example, the Twitter @username for Surnia Ulula is <a href="https://twitter.com/surniaululacom">@surniaululacom</a>. The website / business @username is also used for the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
							break;

						case 'tooltip-tc_desc_len':
							$text = 'The maximum length of text used for the Twitter Card description. The length should be at least '.$this->p->cf['head']['limit_min']['og_desc_len'].' characters or more (the default is '.$this->p->opt->get_defaults( 'tc_desc_len' ).' characters).';
							break;

						case 'tooltip-tc_type_post':
							$text = 'The Twitter Card type for posts / pages with a custom, featured, and/or attached image.';
							break;

						case 'tooltip-tc_type_default':
							$text = 'The Twitter Card type for all other images (default, image from content text, etc).';
							break;

						case 'tooltip-tc_sum_img_dimensions':
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'getting defaults for tc_sum_img (width, height, crop)' );
							$def_dimensions = $this->p->opt->get_defaults( 'tc_sum_img_width' ).'x'.
								$this->p->opt->get_defaults( 'tc_sum_img_height' ).' '.
								( $this->p->opt->get_defaults( 'tc_sum_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = 'The dimension of content images provided for the <a href="https://dev.twitter.com/docs/cards/types/summary-card">Summary Card</a> (should be at least 120x120, larger than 60x60, and less than 1MB). The default image dimensions are '.$def_dimensions.'.';
							break;

						case 'tooltip-tc_lrg_img_dimensions':
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'getting defaults for tc_lrg_img (width, height, crop)' );
							$def_dimensions = $this->p->opt->get_defaults( 'tc_lrg_img_width' ).'x'.
								$this->p->opt->get_defaults( 'tc_lrg_img_height' ).' '.
								( $this->p->opt->get_defaults( 'tc_lrg_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = 'The dimension of Post Meta, Featured or Attached images provided for the <a href="https://dev.twitter.com/docs/cards/large-image-summary-card">Large Image Summary Card</a> (must be larger than 280x150 and less than 1MB). The default image dimensions are '.$def_dimensions.'.';
							break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip_tc', $text, $idx, $info );
							break;
					}	// end of tooltip-tc switch
				/*
				 * Publisher 'Pinterest' (Rich Pin) settings
				 */
				} elseif ( strpos( $idx, 'tooltip-p_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-p_publisher_url':
							$text = 'If you have a <a href="https://business.pinterest.com/">Pinterest Business Page for your website / business</a>, you may enter its URL here. The Publisher Business Page URL will be used in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
							break;

							$text = 'The image dimensions specifically for Rich Pin meta tags when the Pinterest crawler is detected (the default dimensions are '.$def_dimensions.'). Images in the Facebook / Open Graph meta tags are usually cropped square, where-as images on Pinterest often look better in their original aspect ratio (uncropped) and/or cropped using portrait photo dimensions. Note that original images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.';
							break;

						case 'tooltip-p_author_name':
							$text = sprintf( __( 'Pinterest ignores Facebook-style Author Profile URLs in the %1$s Open Graph meta tags.', 'wpsso' ), '<code>article:author</code>' ).' '.__( 'A different meta tag value can be used when the Pinterest crawler is detected.', 'wpsso' ).' '.sprintf( __( 'Select an <em>%1$s</em> for the %2$s meta tag or "[None]" to disable this feature (the recommended value is "Display Name").', 'wpsso' ), _x( 'Author Name Format', 'option label', 'wpsso' ), '<code>article:author</code>' );
							break;

						case 'tooltip-p_dom_verify':
							$text = sprintf( __( 'To <a href="%s">verify your website</a> with Pinterest, edit your business account profile on Pinterest and click the "Verify Website" button.', 'wpsso' ), 'https://help.pinterest.com/en/articles/verify-your-website#meta_tag' ).' '.__( 'Enter the supplied "p:domain_verify" meta tag <em>content</em> value here.', 'wpsso' );
							break;

						case 'tooltip-p_add_img_html':
							$text = __( 'Add the Google / Schema image to the content (in a hidden container) for the Pinterest Pin It browser button.', 'wpsso' );
							break;

						case 'tooltip-p_add_nopin_media_img_tag':
							$text = __( 'Add a "nopin" attribute to images from the WordPress Media Library to prevent the Pin It button from suggesting those images.', 'wpsso' );
							break;

						case 'tooltip-p_add_nopin_header_img_tag':
							$text = __( 'Add a "nopin" attribute to the header image (since WP v4.4) to prevent the Pin It button from suggesting that image.', 'wpsso' );
							break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip_p', $text, $idx, $info );
							break;
					}	// end of tooltip-p switch
				/*
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

				/*
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
				/*
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
				/*
				 * All other settings
				 */
				} else {
					switch ( $idx ) {
						case 'tooltip-custom-cm-field-name':

							$settings_page_link = $this->p->util->get_admin_url( 'general',
								_x( 'General Settings', 'lib file description', 'wpsso' ) );

							$text = '<strong>You should not modify the contact field names unless you have a specific reason to do so.</strong> As an example, to match the contact field name of a theme or other plugin, you might change "gplus" to "googleplus". If you change the Facebook or Google+ field names, please make sure to update the Open Graph <em>Author Profile URL</em> and <em>Google Author Link URL</em> options in the '.$settings_page_link.' as well.';

							break;

						case 'tooltip-wp-cm-field-name':
							$text = __( 'The built-in WordPress contact field names cannot be modified.', 'wpsso' );
							break;

						case 'tooltip-site-use':
							$text = __( 'Individual sites/blogs may use this value as a default (when the plugin is first activated), if the current site/blog option value is blank, or force every site/blog to use this specific value.', 'wpsso' );
							break;

						default:
							$text = apply_filters( $lca.'_messages_tooltip', $text, $idx, $info );
							break;
					} 	// end of all other settings switch
				}	// end of tooltips
			/*
			 * Misc informational messages
			 */
			} elseif ( strpos( $idx, 'info-' ) === 0 ) {
				if ( strpos( $idx, 'info-meta-' ) === 0 ) {
					switch ( $idx ) {
						case 'info-meta-validate-facebook':
							$text = '<p>'.__( 'Facebook and most social websites read Open Graph meta tags.', 'wpsso' ).' '.__( 'The Facebook debugger allows you to refresh Facebook\'s cache, while also validating the Open Graph meta tag values.', 'wpsso' ).' '.__( 'The Facebook debugger remains the most stable and reliable method to verify Open Graph meta tags.', 'wpsso' ).'</p><p><i>'.__( 'You may have to click the "Fetch new scrape information" button a few times to refresh Facebook\'s cache.', 'wpsso' ).'</i></p>';
						 	break;
	
						case 'info-meta-validate-google':
							$text = '<p>'.__( 'Verify that Google can correctly parse your structured data markup (meta tags, Schema, Microdata, and JSON-LD markup) for Google Search and Google+.', 'wpsso' ).'</p>';
						 	break;
	
						case 'info-meta-validate-pinterest':
							$text = '<p>'.__( 'Validate the Open Graph / Rich Pin meta tags and apply to have them shown on Pinterest zoomed pins.', 'wpsso' ).'</p>';
						 	break;
	
						case 'info-meta-validate-twitter':
							$text = '<p><i>'.__( 'The Twitter Card Validator does not accept query arguments &mdash; paste the following URL in the Twitter Card Validator "Card URL" input field (copy the URL using the clipboard icon):', 'wpsso' ).'</i></p>';
						 	break;
	
						case 'info-meta-validate-w3c':

							$settings_page_link = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_google',
								_x( 'Meta Property Containers', 'option label', 'wpsso' ) );

							$text = '<p>'.__( 'Validate the HTML syntax and HTML 5 conformance of your meta tags and theme templates markup.', 'wpsso' ).'</p>'.( empty( $this->p->options['schema_add_noscript'] ) ? '' : '<p><i>'.sprintf( __( 'When the %1$s option is enabled, the W3C validator will show errors for itemprop attributes in meta elements &mdash; you may ignore these errors or disable the %1$s option.', 'wpsso' ), $settings_page_link ).'</i></p>' );

						 	break;
	
						case 'info-meta-validate-amp':
							$text = '<p>'.__( 'Validate the HTML syntax and HTML AMP conformance of your meta tags and the AMP markup of your templates.', 'wpsso' ).'</p>'.( $this->p->avail['*']['amp'] ? '' : '<p><i>'.sprintf( __( 'The <a href="%s">AMP plugin by Automattic</a> is required to validate AMP formatted webpages.', 'wpsso' ), 'https://wordpress.org/plugins/amp/' ).'</i></p>' );
						 	break;
	
						case 'info-meta-social-preview':
						 	$text = '<p style="text-align:right;">'.__( 'An <em>example</em> link share on Facebook. Images are displayed using Facebooks suggested minimum image dimensions of 600x315px. Actual shares on Facebook and other social websites may look significantly different than this example (depending on the client platform, resolution, orientation, etc.).', 'wpsso' ).'</p>';
						 	break;
					}	// end of info-meta switch
				} else {
					switch ( $idx ) {
						case 'info-plugin-tid':	// displayed on the Pro Licenses settings page

							$um_info = $this->p->cf['plugin']['wpssoum'];
							$name_pro_main = $info['name_pro'].' ('.__( 'Main Plugin', 'wpsso' ).')';
	
							$text = '<blockquote class="top-info"><p>'.sprintf( __( 'After purchasing license(s) for the %1$s, or any of its Pro extensions, you\'ll receive an email with a unique Authentication ID and installation instructions.', 'wpsso' ), $name_pro_main ).' '. __( 'Enter each unique Authentication ID on this settings page to enable Pro version updates for the Pro plugin / extension(s) you purchased.', 'wpsso' ).' '.sprintf( __( '%1$s and its %2$s Free extension must be installed and active to use Pro extensions and check for Pro version updates.', 'wpsso' ), $name_pro_main, $um_info['name'] ).'</blockquote>';

							break;
	
						case 'info-plugin-tid-network':	// displayed on the Network Pro Licenses settings page

							$um_info = $this->p->cf['plugin']['wpssoum'];
							$name_pro_main = $info['name_pro'].' ('.__( 'Main Plugin', 'wpsso' ).')';
							$settings_page_link = $this->p->util->get_admin_url( 'licenses',
								_x( 'Extension Plugins and Pro Licenses', 'lib file description', 'wpsso' ) );

							$text = '<blockquote class="top-info"><p>'.sprintf( __( 'After purchasing license(s) for the %1$s, or any of its Pro extensions, you\'ll receive an email with a unique Authentication ID and installation instructions.', 'wpsso' ), $name_pro_main ).' '.sprintf( __( 'You may enter each unique Authentication ID on this page <em>to define a value for all sites within the network</em> &mdash; or enter Authentication IDs individually on each site\'s %1$s settings page.', 'wpsso' ), $settings_page_link ).'</p>';

							$text.= '<p>'.__( 'If you enter Authentication IDs on this network settings page, <em>please make sure you have purchased enough licenses for all sites within the network</em> &mdash; as an example, to license an individual plugin / extension for 10 sites, you would need an Authentication ID from a 10 license pack purchase of that plugin / extension.', 'wpsso' ).'</p>';

							$text .= '<p>'.sprintf( __( '<strong>WordPress uses the default site / blog ID to install and/or update plugins from the Network Admin interface</strong> &mdash; to update the %1$s and its Pro extensions, please make sure the %2$s Free extension is active on the default site, and the default site is licensed.', 'wpsso' ), $name_pro_main, $um_info['name'] ).'</p></blockquote>';

							break;
	
						case 'info-cm':
							$text = '<blockquote class="top-info"><p>'.sprintf( __( 'The following options allow you to customize the contact fields shown in <a href="%s">the user profile page</a> in the <strong>Contact Info</strong> section.', 'wpsso' ), get_admin_url( null, 'profile.php' ) ).' '.sprintf( __( '%s uses the Facebook, Google+, and Twitter contact values for Facebook / Open Graph, Google / Schema, and Twitter Card meta tags.', 'wpsso' ), $info['short'] ).'</p><p><strong>'.sprintf( __( 'You should not modify the <em>%s</em> unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field Name', 'column title', 'wpsso' ) ).'</strong> '.sprintf( __( 'The <em>%s</em> on the other hand is for display purposes only and it can be changed as you wish.', 'wpsso' ), _x( 'Profile Contact Label', 'column title', 'wpsso' ) ).' ;-)</p><p>'.sprintf( __( 'Enabled contact methods are included on user profile editing pages automatically. Your theme is responsible for using their values in its templates (see the WordPress <a href="%s">get_the_author_meta()</a> documentation for examples).', 'wpsso' ), 'https://codex.wordpress.org/Function_Reference/get_the_author_meta' ).'</p><p><center><strong>'.__( 'DO NOT ENTER YOUR CONTACT INFORMATION HERE &ndash; THESE ARE CONTACT FIELD LABELS ONLY.', 'wpsso' ).'</strong><br/>'.sprintf( __( 'Enter your personal contact information on <a href="%1$s">the user profile page</a>.', 'wpsso' ), get_admin_url( null, 'profile.php' ) ).'</center></p></blockquote>';
							break;

						case 'info-taglist':
							$text = '<blockquote class="top-info"><p>'.sprintf( __( '%s adds the following Google Rich Card / SEO, Facebook / Open Graph, Pinterest Rich Pin, Schema Markup, and Twitter Card HTML tags to the <code>&lt;head&gt;</code> section of your webpages.', 'wpsso' ), $info['short'] ).' '.__( 'If your theme or another plugin already creates one or more of these HTML tags, you can uncheck them here to prevent duplicates from being added.', 'wpsso' ).' '.__( 'As an example, the "meta name description" HTML tag is automatically unchecked if a <em>known</em> SEO plugin is detected.', 'wpsso' ).' '.__( 'The "meta name canonical" HTML tag is unchecked by default since themes often include this meta tag in their header template(s).', 'wpsso' ).'</p></blockquote>';
							break;
	
						case 'info-social-accounts':
							
							$settings_page_link = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_google',
								_x( 'Google / Schema', 'metabox tab', 'wpsso' ) );

							$text = '<blockquote class="top-info"><p>';
							$text .= sprintf( __( 'The website / business social account values are used for SEO, Schema, Open Graph, and other social meta tags &ndash; including publisher (Organization) <a href="%s">social markup for Google Search</a>.', 'wpsso' ), 'https://developers.google.com/search/docs/data-types/social-profile-links' ).' ';
							$text .= sprintf( __( 'See the %s settings tab to define an organization logo for Google Search results and enable / disable the addition of publisher (Organization) and/or author (Person) JSON-LD markup.', 'wpsso' ), $settings_page_link );
							$text .= '</p></blockquote>';

							break;
	
						default:
							$text = apply_filters( $lca.'_messages_info', $text, $idx, $info );
							break;
					}	// end of info switch
				}
			/*
			 * Misc pro messages
			 */
			} elseif ( strpos( $idx, 'pro-' ) === 0 ) {
				switch ( $idx ) {
					case 'pro-feature-msg':
						$pdir = $this->p->avail['*']['p_dir'];
						if ( $lca !== $this->p->cf['lca'] && ! $this->p->check->aop( $this->p->cf['lca'], true, $pdir ) ) {
							$req_short = $this->p->cf['plugin'][$this->p->cf['lca']]['short'].' Pro';
							$req_msg = '<br>'.sprintf( __( '(note that all %1$s extensions also require a licensed %1$s plugin)',
								'wpsso' ), $req_short );
						} else {
							$req_msg = '';
						}
						if ( $this->p->check->aop( $lca, false ) ) {
							$text = '<p class="pro-feature-msg"><a href="'.$url['purchase'].'">'.
								sprintf( __( 'Purchase %s licence(s) to install its Pro modules and use the following features / options.',
									'wpsso' ), $info['short_pro'] ).'</a>'.$req_msg.'</p>';
						} else {
							$text = '<p class="pro-feature-msg"><a href="'.$url['purchase'].'">'.
								sprintf( __( 'Purchase the %s plugin to install its Pro modules and use the following features / options.',
									'wpsso' ), $info['short_pro'] ).'</a>'.$req_msg.'</p>';
						}
						break;

					case 'pro-option-msg':
						$text = '<p class="pro-option-msg"><a href="'.$url['purchase'].'">'.
							sprintf( _x( 'option requires %s', 'option comment', 'wpsso' ),
								$info['short_pro'] ).'</a></p>';
						break;

					case 'pro-purchase-text':
						if ( ! empty( $info['ext'] ) && WpssoAdmin::$pkg[$info['ext']]['aop'] ) {
							$text = _x( 'More Licenses', 'plugin action link', 'wpsso' );
						} else {
							$text = _x( 'Purchase Pro', 'plugin action link', 'wpsso' );
						}
						if ( ! empty( $info['url'] ) ) {
							$text = '<a href="'.$info['url'].'"'.
								( empty( $info['tabindex'] ) ? '' : ' tabindex="'.$info['tabindex'].'"' ).'>'.
									$text.'</a>';
						}
						if ( ! empty( $info['ext'] ) && ! WpssoAdmin::$pkg[$lca]['aop'] && $info['ext'] !== $lca ) {
							$text .= ' <em>'.sprintf( _x( '(%s required)', 'plugin action link',
								'wpsso' ), $info['short_pro'] ).'</em>';
						}
						break;

					case 'pro-about-msg-post-text':
						$text = '<p>'.__( 'You can update the excerpt or content text to change the default description values.', 'wpsso' ).'</p>';
						break;

					case 'pro-about-msg-post-media':
						$text = '<p>'.__( 'You can change the social image by selecting a featured image, attaching image(s) or including images in the content.', 'wpsso' ).'<br/>'.sprintf( __( 'Video service API modules &mdash; required to detect embedded videos &mdash; are available in the %s version.', 'wpsso' ),  $info['name_pro'] ).'</p>';
						break;

					default:
						$text = apply_filters( $lca.'_messages_pro', $text, $idx, $info );
						break;
				}
			/*
			 * Misc notice messages
			 */
			} elseif ( strpos( $idx, 'notice-' ) === 0 ) {
				switch ( $idx ) {
					case 'notice-image-rejected':

						$hide_const_name = strtoupper( $lca ).'_HIDE_ALL_WARNINGS';
						$hidden_warnings = SucomUtil::get_const( $hide_const_name );
						$is_settings_page = strpos( SucomUtil::get_screen_id(), '_page_'.$lca = $this->p->cf['lca'].'-' );

						// do not add this text if hidding pro options or on a settings page
						if ( empty( $this->p->options['plugin_hide_pro'] ) && $is_settings_page === false ) {
							$text = sprintf( __( 'A larger and/or different custom image, specifically for meta tags and Schema markup, can be selected in the %s metabox under the <em>Select Media</em> tab.', 'wpsso' ), _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' ) );
						} else {
							$text = '';
						}

						static $do_once_upscale_notice = null;	// show the upscale details only once

						if ( $do_once_upscale_notice !== true && current_user_can( 'manage_options' ) && 
							( ! isset( $info['allow_upscale'] ) || ! empty( $info['allow_upscale'] ) ) ) {

							$do_once_upscale_notice = true;

							$img_dim_page_link = $this->p->util->get_admin_url( 'image-dimensions', 
								_x( 'Social and Search Image Dimensions', 'lib file description', 'wpsso' ) );

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

							if ( empty( $hidden_warnings ) ) {
								$text .= '<li>'.sprintf( __( 'Define the %1$s constant as <em>true</em> to auto-hide all dismissable warnings.', 'wpsso' ), $hide_const_name ).'</li>';
							}
							$text .= '</ul>';
						}

						break;

					case 'notice-missing-og-image':
						$text = sprintf( __( 'An Open Graph image meta tag could not be generated from this webpage content or its custom %s settings. Facebook <em>requires at least one image meta tag</em> to render shared content correctly.', 'wpsso' ), _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' ) );
						break;

					case 'notice-missing-og-description':
						$text = sprintf( __( 'An Open Graph description meta tag could not be generated from this webpage content or its custom %s settings. Facebook <em>requires a description meta tag</em> to render shared content correctly.', 'wpsso' ), _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' ) );
						break;

					case 'notice-missing-schema-image':
						$text = sprintf( __( 'A Schema image property could not be generated from this webpage content or its custom %s settings. Google <em>requires at least one image property</em> for this Schema item type.', 'wpsso' ), _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' ) );
						break;

					case 'notice-content-filters-disabled':

						$settings_page_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content' );
						
						$filters_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content',
							_x( 'Apply WordPress Content Filters', 'option label', 'wpsso' ) );

						$text = '<p><b>'.sprintf( __( 'The %1$s advanced option is currently disabled.', 'wpsso' ), $filters_option_link ).'</b> '.sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for description values and detect additional images / embedded videos provided by shortcodes.', 'wpsso' ), $info['short'] ).' '.sprintf( __( 'If you use any shortcodes in your content text, <a href="%s">this option should be enabled</a>.', 'wpsso' ), $settings_page_url ).'</p>';

						break;

					case 'notice-header-tmpl-no-head-attr':

						$action_url = wp_nonce_url( $this->p->util->get_admin_url( '?'.$this->p->cf['lca'].'-action=modify_tmpl_head_attributes' ),
							WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

						$text = '<p><b>'.__( 'At least one of your theme header templates does not support Schema markup of the webpage head section &mdash; this is especially important for Google and Pinterest.', 'wpsso' ).'</b> '.sprintf( __( 'The %s element in your header templates should include a function, action, or filter for its attributes.', 'wpsso' ), '<code>&lt;head&gt;</code>' ).' '.sprintf( __( '%1$s can update your header templates automatically to change the default %2$s element to:', 'wpsso' ), $info['short'], '<code>&lt;head&gt;</code>' ).'</p>';

						$text .= '<pre><code>&lt;head &lt;?php do_action( \'add_head_attributes\' ); ?&gt;&gt;</code></pre>';

						$text .= '<p>'.sprintf( __( '<b><a href="%1$s">Click here to update header templates automatically</a></b> or update the templates yourself manually.', 'wpsso' ), $action_url ).'</p>';

						break;

					case 'notice-pro-tid-missing':

						if ( ! is_multisite() ) {
							$settings_page_link = $this->p->util->get_admin_url( 'licenses',
								_x( 'Extension Plugins and Pro Licenses', 'lib file description', 'wpsso' ) );

							$text = '<p><b>'.sprintf( __( 'The %1$s plugin Authentication ID option is empty.', 'wpsso' ), $info['name'] ).'</b><br/>'.sprintf( __( 'To enable Pro version features and allow the plugin to authenticate itself for updates, please enter the unique Authentication ID you received by email on the %s settings page.', 'wpsso' ), $settings_page_link ).'</p>';
						}

						break;

					case 'notice-pro-not-installed':

						$settings_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Extension Plugins and Pro Licenses', 'lib file description', 'wpsso' ) );

						$text = sprintf( __( 'An Authentication ID has been provided for %1$s but the plugin has not been installed &mdash; you can install and activate the Pro version from the %2$s settings page.', 'wpsso' ), '<b>'.$info['name'].'</b>', $settings_page_link ).' ;-)';

						break;

					case 'notice-pro-not-updated':

						$settings_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Extension Plugins and Pro Licenses', 'lib file description', 'wpsso' ) );

						$text = sprintf( __( 'An Authentication ID has been provided for %1$s on the %2$s settings page but the Pro version has not been installed &mdash; don\'t forget to update the current plugin to install the latest Pro version.', 'wpsso' ), '<b>'.$info['name'].'</b>', $settings_page_link ).' ;-)';

						break;

					case 'notice-um-extension-required':
					case 'notice-um-activate-extension':

						$um_info = $this->p->cf['plugin']['wpssoum'];
						$name_pro_main = $info['name_pro'].' ('.__( 'Main Plugin', 'wpsso' ).')';
						$settings_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Extension Plugins and Pro Licenses', 'lib file description', 'wpsso' ) );
						$plugins_page_link = '<a href="'.get_admin_url( null, 'plugins.php' ).'">'.__( 'Plugins' ).'</a>';

						$text = '<p><b>'.sprintf( __( 'At least one Authentication ID has been entered on the %1$s settings page,<br/>but the %2$s extension is not active.', 'wpsso' ), $settings_page_link, $um_info['name'] ).'</b> '.sprintf( __( 'This Free extension is required to update and enable the %1$s and its Pro extensions.', 'wpsso' ), $name_pro_main ).'</p><p>';

						if ( $idx === 'notice-um-extension-required' ) {
							$text .= '<b>'.sprintf( __( 'Install and activate the %1$s extension from the %2$s settings page.', 'wpsso' ), $um_info['name'], $settings_page_link ).'</b>';
						} else {
							$text .= '<b>'.sprintf( __( 'The %1$s extension can be activated from the WordPress %2$s page.', 'wpsso' ), $um_info['name'], $plugins_page_link ).'</b> '.__( 'Please activate this Free extension now.', 'wpsso' );
						}

						$text .= ' '.sprintf( __( 'When the %1$s extension is active, one or more Pro version updates may be available for your licensed plugin / extension(s).', 'wpsso' ), $um_info['name'] ).'</p>';

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
						
						$text .= sprintf( __( 'If an update for %1$s is not available under the WordPress %2$s page, use the <em>%3$s</em> button on the %4$s settings page to force an immediate refresh of all Pro update information.', 'wpsso' ), $um_info['name'], $wp_updates_page_link, $um_check_updates_transl, $um_settings_page_link );

						break;

					case 'notice-recommend-version':

						$text = sprintf( __( 'You are using %1$s version %2$s &mdash; <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.', 'wpsso' ), $info['app_label'], $info['app_version'], $info['version_url'] ).' '.sprintf( __( 'If possible, please update to the latest %1$s stable release (or at least version %2$s).', 'wpsso' ), $info['app_label'], $info['rec_version'] );

						break;

					default:
						$text = apply_filters( $lca.'_messages_notice', $text, $idx, $info );
						break;
			}
			/*
			 * Misc sidebox messages
			 */
			} elseif ( strpos( $idx, 'column-' ) === 0 ) {

				switch ( $idx ) {

					case 'column-purchase-pro':

						$text = '<p>'.sprintf( __( '<strong>%s includes:</strong>', 'wpsso' ), $info['short_pro'] ).'</p>';
						$text .= '<ul>';
						$text .= '<li>'.sprintf( __( '%s options for posts, pages, custom post types, terms (categories, tags, and custom taxonomies), and user profiles.', 'wpsso' ), _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' ) ).'</li>';
						$text .= '<li>'.__( 'Integration with numerous 3rd party plugins and external service APIs.', 'wpsso' ).'</li>';
						$text .= '<li>'.__( 'Advanced plugin features and settings page.', 'wpsso' ).'</li>';
						$text .= '</ul>';
						$text .= '<p>'.__( '<strong>Nontransferable Pro licenses never expire</strong> &mdash; you may receive unlimited / lifetime updates and support for each licensed WordPress Site Address.', 'wpsso' ).' '.__( 'How great is that!?', 'wpsso' ).' :-)</p>';

						if ( $this->p->avail['*']['p_dir'] ) {
							$text .= '<p>'.sprintf( __( '<strong>Purchase %s easily and quickly with PayPal</strong> &mdash; license the Pro version immediately after your purchase!', 'wpsso' ), $info['short_pro'] ).'</p>';
						} else {
							$text .= '<p>'.sprintf( __( '<strong>Purchase %s easily and quickly with PayPal</strong> &mdash; update the Free plugin to Pro immediately after your purchase!', 'wpsso' ), $info['short_pro'] ).'</p>';
						}
						break;

					case 'column-help-support':

						$text = '<p>'.sprintf( __( '<strong>The continued development of %1$s is driven by user requests</strong> &mdash; we welcome all your comments and suggestions!', 'wpsso' ), $info['short'] ).'</p>';
						break;

					case 'column-rate-review':

						$text = '<p>'.__( '<strong>Great ratings are a terrific way to encourage plugin developers</strong> &mdash; and it only takes a minute (or less).', 'wpsso' ).' ;-)</p><p>'.__( 'Please encourage us by rating the plugins you use.', 'wpsso' ).'</p>';
						break;

					default:

						$text = apply_filters( $lca.'_messages_side', $text, $idx, $info );
						break;
				}
			} else $text = apply_filters( $lca.'_messages', $text, $idx, $info );

			if ( is_array( $info ) && ! empty( $info['is_locale'] ) ) {

				$lang_plugins = '<a href="https://wordpress.org/plugins/wpsso-user-locale/">WPSSO User Locale Selector</a>, '.
					'<a href="https://wordpress.org/plugins/polylang/">Polylang</a>';

				$text .= ' '.sprintf( __( 'This option is localized &mdash; you may change the WordPress locale (see %s) to define alternate values for different languages.', 'wpsso' ), $lang_plugins );
			}

			if ( strpos( $idx, 'tooltip-' ) === 0 && ! empty( $text ) ) {
				$text = '<img src="'.WPSSO_URLPATH.'images/question-mark.png" width="14" height="14" class="'.
					( isset( $info['class'] ) ? $info['class'] : $this->p->cf['form']['tooltip_class'] ).
						'" alt="'.esc_attr( $text ).'" />';
			}

			return $text;
		}
	}
}

?>
