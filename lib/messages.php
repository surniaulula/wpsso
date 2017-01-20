<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoMessages' ) ) {

	class WpssoMessages {

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
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
			} else $text = isset( $info['text'] ) ?
				$info['text'] : '';

			$idx = sanitize_title_with_dashes( $idx );

			/*
			 * Define some basic values that can be used in any message text.
			 */
			$info['lca'] = $lca = isset( $info['lca'] ) ?	// wpsso, wpssoum, etc.
				$info['lca'] : $this->p->cf['lca'];

			foreach ( array( 'short', 'name' ) as $key ) {
				$info[$key] = isset( $info[$key] ) ?
					$info[$key] : $this->p->cf['plugin'][$lca][$key];
				$info[$key.'_pro'] = $info[$key].' Pro';
			}

			// an array of plugin urls (download, purchase, etc.)
			$url = isset( $this->p->cf['plugin'][$lca]['url'] ) ?
				$this->p->cf['plugin'][$lca]['url'] : array();

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
							$text = 'Custom Video Embed HTML to use for the first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Player\' Twitter Card meta tags. If the URL is from Youtube, Vimeo or Wistia, an API connection will be made to retrieve the preferred sharing URL, video dimensions, and video preview image. The '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_social', 'Video Embed HTML Custom Field' ).' advanced option also allows a 3rd-party theme or plugin to provide custom Video Embed HTML for this option.';
						 	break;
						case 'tooltip-meta-og_vid_url':
							$text = 'A custom Video URL to include first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Player\' Twitter Card meta tags. If the URL is from Youtube, Vimeo or Wistia, an API connection will be made to retrieve the preferred sharing URL, video dimensions, and video preview image. The '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_social', 'Video URL Custom Field' ).' advanced option allows a 3rd-party theme or plugin to provide a custom Video URL value for this option.';
						 	break;
						case 'tooltip-meta-og_vid_title':
						case 'tooltip-meta-og_vid_desc':
							$text = sprintf( __( 'The %1$s video API modules include the video name / title and description <em>when available</em>.', 'wpsso' ), $info['short_pro'] ).' '.__( 'The video name / title and description text is used for Schema JSON-LD markup (extension plugin required), which can be read by both Google and Pinterest.', 'wpsso' );
							break;
						case 'tooltip-meta-og_vid_max':
							$text = __( 'The maximum number of embedded videos to include in the Facebook / Open Graph meta tags.', 'wpsso' ).' '.__( 'There is no advantage in selecting a maximum value greater than 1.', 'wpsso' );
						 	break;
						case 'tooltip-meta-og_vid_prev_img':
							$text = 'When video preview images are enabled and available, they are included in webpage meta tags before any custom, featured, attached, etc. images.';
						 	break;
						case 'tooltip-meta-rp_img_id':
							$text = __( 'A custom image ID to include first when the Pinterest crawler is detected, before any featured, attached, or content images.', 'wpsso' );
						 	break;
						case 'tooltip-meta-rp_img_url':
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
				 * Open Graph settings
				 */
				} elseif ( strpos( $idx, 'tooltip-og_' ) === 0 ) {
					switch ( $idx ) {
						/*
						 * 'Priority Media' settings
						 */
						case 'tooltip-og_img_dimensions':
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'getting defaults for og_img (width, height, crop)' );
							$def_dimensions = $this->p->opt->get_defaults( 'og_img_width' ).'x'.
								$this->p->opt->get_defaults( 'og_img_height' ).' '.
								( $this->p->opt->get_defaults( 'og_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = 'The image dimensions used in the Facebook / Open Graph meta tags (the default dimensions are '.$def_dimensions.'). '.$fb_recommends.' Note that images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.';
							break;
						case 'tooltip-og_def_img_id':
							$text = 'An image ID and media library for your default / fallback website image. The default image ID will be used for index / archive pages, and as a fallback for Posts / Pages that do not have a suitable image featured, attached, or in their content.';
							break;
						case 'tooltip-og_def_img_url':
							$text = 'You can enter a default image URL (including the http:// prefix) instead of choosing a default image ID &mdash; if a default image ID is specified, the default image URL option is disabled. The default image URL option allows you to <strong>use an image outside of a managed collection (WordPress Media Library or NextGEN Gallery), and/or a smaller logo style image</strong>. The image should be at least '.$this->p->cf['head']['limit_min']['og_img_width'].'x'.$this->p->cf['head']['limit_min']['og_img_height'].' or more in width and height. The default image ID or URL is used for index / archive pages, and as a fallback for Posts and Pages that do not have a suitable image featured, attached, or in their content.';
							break;
						case 'tooltip-og_def_img_on_index':
							$text = 'Check this option to force the default image on index webpages (<strong>non-static</strong> homepage, archives, categories). If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$info['short'].' will use image(s) from the first entry on the webpage (default is checked).';
							break;
						case 'tooltip-og_def_img_on_search':
							$text = 'Check this option to force the default image on search results. If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$info['short'].' will use image(s) returned in the search results (default is unchecked).';
							break;
						case 'tooltip-og_def_vid_url':
							$text = 'The Default Video URL is used as a <strong>fallback value for Posts and Pages that do not have any videos</strong> in their content. Do not specify a Default Video URL <strong>unless you want to include video information in all your Posts and Pages</strong>.';
							break;
						case 'tooltip-og_def_vid_on_index':
							$text = 'Check this option to force the default video on index webpages (<strong>non-static</strong> homepage, archives, categories). If this option is <em>checked</em>, but a Default Video URL has not been defined, then <strong>no video will be included in the meta tags</strong> (this is usually preferred). If the option is <em>unchecked</em>, then '.$info['short'].' will use video(s) from the first entry on the webpage (default is checked).';
							break;
						case 'tooltip-og_def_vid_on_search':
							$text = 'Check this option to force the default video on search results. If this option is <em>checked</em>, but a Default Video URL has not been defined, then <strong>no video will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$info['short'].' will use video(s) returned in the search results (default is unchecked).';
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
						case 'tooltip-og_art_section':
							$text = __( 'The topic that best describes the Posts and Pages on your website.', 'wpsso' ).' '.sprintf( __( 'This value will be used in the %1$s Facebook / Open Graph and Pinterest Rich Pin meta tags. Select "[None]" if you prefer to exclude the %1$s meta tag.', 'wpsso' ), '<code>article:section</code>' ).' '.__( 'The Pro version also allows you to select a custom Topic for each individual Post and Page.', 'wpsso' );
							break;
						case 'tooltip-og_site_name':
							$text = sprintf( __( 'The WordPress Site Name is used for the Facebook / Open Graph and Pinterest Rich Pin %1$s meta tag. You may override <a href="%2$s">the default WordPress Site Title value</a>.', 'wpsso' ), '<code>og:site_name</code>', get_admin_url( null, 'options-general.php' ) );
							break;
						case 'tooltip-og_site_description':
							$text = sprintf( __( 'The WordPress tagline is used as a description for the <em>index</em> (non-static) home page, and as a fallback for the Facebook / Open Graph and Pinterest Rich Pin %1$s meta tag.', 'wpsso' ), '<code>og:description</code>' ).' '.sprintf( __( 'You may override <a href="%1$s">the default WordPress Tagline value</a> here, to provide a longer and more complete description of your website.', 'wpsso' ), get_admin_url( null, 'options-general.php' ) );
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
							$text = sprintf( __( 'Select which contact field to use from the author\'s WordPress profile page for the Facebook / Open Graph %1$s meta tag. The preferred setting is the Facebook URL field (default value).', 'wpsso' ), '<code>article:author</code>' );
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
						case 'tooltip-plugin_honor_force_ssl':	// Honor the FORCE_SSL Constant
							$text = sprintf( __( 'If the FORCE_SSL constant is defined as true, %s can redirect front-end URLs from HTTP to HTTPS when required (default is checked).', 'wpsso' ), $info['short'] );
							break;
						case 'tooltip-plugin_clear_on_save':	// Clear Cache(s) on Save Settings
							$text = sprintf( __( 'Automatically clear all known plugin cache(s) when saving the %s settings (default is checked).', 'wpsso' ), $info['short'] );
							break;
						case 'tooltip-plugin_preserve':	// Preserve Settings on Uninstall
							$text = sprintf( __( 'Check this option if you would like to preserve all %s settings when you <em>uninstall</em> the plugin (default is unchecked).', 'wpsso' ), $info['short'] );
							break;
						case 'tooltip-plugin_debug':	// Add Hidden Debug Messages
							$text = __( 'Add debugging messages to back-end and front-end webpages as hidden HTML comments (default is unchecked).', 'wpsso' );
							break;
						case 'tooltip-plugin_hide_pro':	// Hide All Pro Settings
							$text = __( 'Hide all Pro version settings, tabs, and options (default is unchecked).', 'wpsso' );
							break;
						case 'tooltip-plugin_show_opts':	// Options to Show by Default
							$text = sprintf( __( 'Select the default set of options to display in the %s settings pages. The basic view shows only the most commonly used options.', 'wpsso' ), $info['short'] );
							break;
						/*
						 * 'Content and Filters' settings
						 */
						case 'tooltip-plugin_filter_title':
							$text = 'By default, '.$info['short'].' uses the title values provided by WordPress, which may include modifications by themes and/or SEO plugins (appending the blog name to all titles, for example, is a fairly common practice). Uncheck this option to use the original title value without modifications.';
							break;
						case 'tooltip-plugin_filter_content':
							$text = 'Apply the standard WordPress "the_content" filter to render content text (default is unchecked). This renders all shortcodes, and allows '.$info['short'].' to detect images and embedded videos that may be provided by these.';
							break;
						case 'tooltip-plugin_filter_excerpt':
							$text = 'Apply the standard WordPress "get_the_excerpt" filter to render the excerpt text (default is unchecked). Check this option if you use shortcodes in your excerpt, for example.';
							break;
						case 'tooltip-plugin_p_strip':
							$text = 'If a Page or Post does <em>not</em> have an excerpt, and this option is checked, the plugin will ignore all text until the first html paragraph tag in the content. If an excerpt exists, then this option is ignored and the complete text of the excerpt is used.';
							break;
						case 'tooltip-plugin_use_img_alt':
							$text = 'If the content is empty, or comprised entirely of HTML tags (that must be stripped to create a description text), '.$info['short'].' can extract and use text from the image <em>alt=""</em> attributes instead of returning an empty description.';
							break;
						case 'tooltip-plugin_img_alt_prefix':
							$text = 'When use of the image <em>alt=""</em> text is enabled, '.$info['short'].' can prefix that text with an optional string. Leave this option empty to prevent image alt text from being prefixed.';
							break;
						case 'tooltip-plugin_p_cap_prefix':
							$text = $info['short'].' can add a custom text prefix to paragraphs assigned the "wp-caption-text" class. Leave this option empty to prevent caption paragraphs from being prefixed.';
							break;
						case 'tooltip-plugin_content_img_max':
							$text = 'The maximum number of images that '.$info['short'].' will consider using from your content.';
							break;
						case 'tooltip-plugin_content_vid_max':
							$text = 'The maximum number of embedded videos that '.$info['short'].' will consider using from your content.';
							break;
						case 'tooltip-plugin_embedded_media':
							$text = 'Check the Post and Page content, along with the custom Social Settings, for embedded media URLs from supported media providers (Youtube, Wistia, etc.). If a supported URL is found, an API connection to the provider will be made to retrieve information about the media (preview image, flash player url, oembed player url, video width / height, etc.).';
							break;
						/*
						 * 'Social Settings' settings
						 */
						case 'tooltip-plugin_schema_type_col':
							if ( empty( $col_name ) )
								$col_name = sprintf( _x( '%s Schema',
									'column title', 'wpsso' ),
										$this->p->cf['menu_label'] );
							// no break
						case 'tooltip-plugin_og_img_col':
							if ( empty( $col_name ) )
								$col_name = sprintf( _x( '%s Img',
									'column title', 'wpsso' ),
										$this->p->cf['menu_label'] );
							// no break
						case 'tooltip-plugin_og_desc_col':
							if ( empty( $col_name ) )
								$col_name = sprintf( _x( '%s Desc',
									'column title', 'wpsso' ),
										$this->p->cf['menu_label'] );

							$text = sprintf( __( 'An "%1$s" column can be added to the Posts, Pages, Taxonomy / Terms, and Users admin list pages. When enabled, <b>users can also hide this column</b> by using the <em>Screen Options</em> tab on each admin list page.', 'wpsso' ), $col_name );
							break;
						case 'tooltip-plugin_add_to':
							$text = 'The Social Settings metabox, which allows you to enter custom Facebook / Open Graph values (among other options), is available on the User, Posts, Pages, Media, and Product admin pages by default. If your theme (or another plugin) supports additional custom post types, and you would like to include the Social Settings metabox on their admin pages, check the appropriate option(s) here.';
							break;
						case 'tooltip-plugin_add_tab':
							$text = __( 'Include and exclude specific tabs in the Social Settings metabox.', 'wpsso' );
							break;
						case 'tooltip-plugin_wpseo_social_meta':
							$text = __( 'Read the Yoast SEO custom social meta text for Posts, Terms, and Users.', 'wpsso' ).' '.
							__( 'This option is checked by default if the Yoast SEO plugin is active or its settings are found in the database.',
								'wpsso' );
							break;
						case 'tooltip-plugin_cf_img_url':
							$text = 'If your theme or another plugin provides a custom field for image URLs, you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the "<strong>Image URL</strong>" option in the Social Settings metabox. The default custom field name is "'.$this->p->opt->get_defaults( 'plugin_cf_img_url' ).'".';
							break;
						case 'tooltip-plugin_cf_vid_url':
							$text = 'If your theme or another plugin provides a custom field for video URLs (not embed HTML code), you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the "<strong>Video URL</strong>" option in the Social Settings metabox. The default custom field name is "'.$this->p->opt->get_defaults( 'plugin_cf_vid_url' ).'".';
							break;
						case 'tooltip-plugin_cf_vid_embed':
							$text = 'If your theme or another plugin provides a custom field for video embed HTML code (not simply a URL), you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the "<strong>Video Embed HTML</strong>" option in the Social Settings metabox. The default custom field name is "'.$this->p->opt->get_defaults( 'plugin_cf_vid_embed' ).'".';
							break;
						case 'tooltip-plugin_cf_recipe_ingredients':
							$text = 'If your theme or another plugin provides a custom field for recipe ingredients, you may enter its custom field name here. If a custom field matching that name is found, its value may be used to create additional meta tags and Schema markup. The default custom field name is "'.$this->p->opt->get_defaults( 'plugin_cf_recipe_ingredients' ).'".';
							break;
						/*
						 * 'WP / Theme Integration' settings
						 */
						case 'tooltip-plugin_check_head':
							$max_count = (int) SucomUtil::get_const( 'WPSSO_CHECK_HEADER_COUNT', 6 );
							$text = sprintf( __( 'When editing Posts and Pages, %1$s can check the head section of webpages for conflicting and/or duplicate HTML tags. After %2$d <em>successful</em> checks, no additional checks will be performed &mdash; until the theme and/or any plugin is updated, when another %2$d checks are performed.', 'wpsso' ), $info['short'], $max_count );
							break;
						case 'tooltip-plugin_html_attr_filter':
							$text = $info['short'].' hooks the "language_attributes" filter by default to add / modify required Open Graph namespace prefix values. The "language_attributes" WordPress function and filter are used by most themes &mdash; if the namespace prefix values are missing from your &amp;lt;html&amp;gt; element, make sure your header template(s) use the language_attributes() function. Leaving this option blank disables the addition of Open Graph namespace values. Example template code: <pre><code>&amp;lt;html &amp;lt;?php language_attributes(); ?&amp;gt;&amp;gt;</code></pre>';
							break;
						case 'tooltip-plugin_head_attr_filter':
							$text = $info['short'].' hooks the "head_attributes" filter by default to add / modify the <code>&amp;lt;head&amp;gt;</code> element attributes for the Schema itemscope / itemtype markup. If your theme offers a filter for <code>&amp;lt;head&amp;gt;</code> element attributes, enter its name here (most themes do not). Alternatively, you can add an action manually in your header templates to call the "head_attributes" filter. Example code: <pre><code>&amp;lt;head &amp;lt;?php do_action( \'add_head_attributes\' ); ?&amp;gt;&amp;gt;</code></pre>';
							break;
						case 'tooltip-plugin_filter_lang':
							$text = $info['short_pro'].' can use the WordPress locale to select the correct language for the Facebook / Open Graph and Pinterest Rich Pin meta tags'.( empty( $this->p->is_avail['ssb'] ) ? '' : ', along with the Google, Facebook, and Twitter social sharing buttons' ).'. If your website is available in multiple languages, this can be a useful feature. Uncheck this option to ignore the WordPress locale and always use the configured language.'; 
							break;
						case 'tooltip-plugin_create_wp_sizes':
							$text = __( 'Automatically create missing and/or incorrect images in the WordPress Media Library (default is checked).', 'wpsso' );
							break;
						case 'tooltip-plugin_check_img_dims':
							$text = 'When this option is enabled, selected images must be equal to (or larger) than the '.$this->p->util->get_admin_url( 'image-dimensions', 'Social and SEO Image Dimensions' ).' you\'ve defined -- images that do not meet or exceed the minimum requirements will be rejects / ignored. <strong>Enabling this option is highly recommended</strong> &mdash; it is disabled by default to avoid excessive warnings on sites with small / thumbnail images in their media library.';
							break;
						case 'tooltip-plugin_upscale_images':
							$text = 'WordPress does not upscale (enlarge) images &mdash; WordPress only creates smaller images from larger full-size originals. Upscaled images do not look as sharp or clean when upscaled, and if enlarged too much, images will look fuzzy and unappealing &mdash; not something you want to promote on social sites. '.$info['short_pro'].' includes an optional module that allows upscaling of WordPress Media Library images for '.$info['short'].' image sizes (up to a maximum upscale percentage). <strong>Do not enable this option unless you want to publish lower quality images on social sites</strong>.';
							break;
						case 'tooltip-plugin_upscale_img_max':
							$text = 'When upscaling of '.$info['short'].' image sizes is allowed, '.$info['short_pro'].' can make sure smaller / thumbnail images are not upscaled beyond reason, which could publish very low quality / fuzzy images on social sites (the default maximum is 33%). If an image needs to be upscaled beyond this maximum, <em>in either width or height</em>, the image will not be upscaled.';
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
						case 'tooltip-plugin_column_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_column_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'The content of list table columns is saved to the WordPress transient cache to optimize performance.',
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
						case 'tooltip-plugin_shorten_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_shorten_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'Shortened URLs are saved to the WordPress transient cache to optimize performance and API connections.',
								'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).',
									'wpsso' ), $cache_exp, $cache_diff );
							break;
						case 'tooltip-plugin_topics_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_topics_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'The filtered article topics array is saved to the WordPress transient cache to optimize performance and disk access.',
								'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).',
									'wpsso' ), $cache_exp, $cache_diff );
							break;
						case 'tooltip-plugin_types_cache_exp':
							$cache_exp = WpssoConfig::$cf['opt']['defaults']['plugin_types_cache_exp'];	// use original un-filtered value
							$cache_diff = $cache_exp ? human_time_diff( 0, $cache_exp ) : _x( 'disabled', 'option comment', 'wpsso' );
							$text = __( 'The filtered Schema types array is saved to the WordPress transient cache to optimize performance.',
								'wpsso' ).' '.sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).',
									'wpsso' ), $cache_exp, $cache_diff );
							break;
						case 'tooltip-plugin_cache_info':
							$text = __( 'Report the number of objects removed from the WordPress cache when Posts and Pages are updated.',
								'wpsso' );
							break;
						/*
						 * 'Service API Keys' (URL Shortening) settings
						 */
						case 'tooltip-plugin_shortener':
							$text = sprintf( __( 'A preferred URL shortening service for %s plugin filters and/or extensions that may need to shorten URLs &mdash; don\'t forget to define the Service API Keys for the URL shortening service of your choice.', 'wpsso' ), $info['short'] );
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
						case 'tooltip-plugin_bitly_token':
							$text = sprintf( __( 'The Bitly shortening service requires a <a href="%s" target="_blank">Generic Access Token</a> or API Key (deprecated) to shorten URLs.', 'wpsso' ), 'https://bitly.com/a/oauth_apps' );
							break;
						case 'tooltip-plugin_bitly_api_key':
							$text = sprintf( __( 'The Bitly <a href="%s" target="_blank">API Key</a> authentication method has been deprecated by Bitly.', 'wpsso' ), 'https://bitly.com/a/your_api_key' );
							break;
						case 'tooltip-plugin_google_api_key':
							$text = sprintf( __( 'The Google BrowserKey value for this website (project). If you don\'t already have a Google project, visit <a href="%s" target="_blank">Google\'s Cloud Console</a> and create a new project for your website (use the "Select a project" drop-down).', 'wpsso' ), 'https://console.developers.google.com/start' );
							break;
						case 'tooltip-plugin_google_shorten':
							$text = sprintf( __( 'In order to use Google\'s URL Shortener API service, you must <em>Enable</em> the URL Shortener API from <a href="%s" target="_blank">Google\'s Cloud Console</a> (under the project\'s <em>API &amp; auth / APIs / URL Shortener API</em> settings page).', 'wpsso' ), 'https://console.developers.google.com/start' ).' '.__( 'Confirm that you have enabled Google\'s URL Shortener API service by checking the "Yes" option value.', 'wpsso' );
							break;
						case 'tooltip-plugin_owly_api_key':
							$text = sprintf( __( 'To use Ow.ly as your preferred shortening service, you must provide the Ow.ly API Key for this website (complete this form to <a href="%s" target="_blank">Request Ow.ly API Access</a>).', 'wpsso' ), 'https://docs.google.com/forms/d/1Fn8E-XlJvZwlN4uSRNrAIWaY-nN_QA3xAHUJ7aEF7NU/viewform' );
							break;
						case 'tooltip-plugin_yourls_api_url':
							$text = sprintf( __( 'The URL to <a href="%1$s" target="_blank">Your Own URL Shortener</a> (YOURLS) shortening service.', 'wpsso' ), 'http://yourls.org/' );
							break;
						case 'tooltip-plugin_yourls_username':
							$text = sprintf( __( 'If <a href="%1$s" target="_blank">Your Own URL Shortener</a> (YOURLS) shortening service is private, enter a configured username (see YOURLS Token for an alternative to the username / password options).', 'wpsso' ), 'http://yourls.org/' );
							break;
						case 'tooltip-plugin_yourls_password':
							$text = sprintf( __( 'If <a href="%1$s" target="_blank">Your Own URL Shortener</a> (YOURLS) shortening service is private, enter a configured user password (see YOURLS Token for an alternative to the username / password options).', 'wpsso' ), 'http://yourls.org/' );
							break;
						case 'tooltip-plugin_yourls_token':
							$text = sprintf( __( 'If <a href="%1$s" target="_blank">Your Own URL Shortener</a> (YOURLS) shortening service is private, you can use a token string for authentication instead of a username / password combination.', 'wpsso' ), 'http://yourls.org/' );
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
							$text = sprintf( __( 'If you have a <a href="%1$s" target="_blank">Facebook Business Page for your website / business</a>, you may enter its URL here (for example, the Facebook Business Page URL for %2$s is <a href="%3$s" target="_blank">%4$s</a>).', 'wpsso' ), 'https://www.facebook.com/business', 'Surnia Ulula', 'https://www.facebook.com/SurniaUlulaCom', 'https://www.facebook.com/SurniaUlulaCom' ).' '.__( 'The Facebook Business Page URL will be used in Open Graph <em>article</em> type webpages (not index or archive webpages) and schema publisher (Organization) social JSON.', 'wpsso' ).' '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
							break;
						case 'tooltip-fb_admins':
							$text = sprintf( __( 'The %1$s are used by Facebook to allow access to <a href="%2$s" target="_blank">Facebook Insight</a> data for your website. Note that these are <strong>user account names, not Facebook Page names</strong>. Enter one or more Facebook user names, separated with commas. When viewing your own Facebook wall, your user name is located in the URL (for example, https://www.facebook.com/<strong>user_name</strong>). Enter only the user names, not the URLs.', 'wpsso' ), _x( 'Facebook Admin Username(s)', 'option label', 'wpsso' ), 'https://developers.facebook.com/docs/insights/' ).' '.sprintf( __( 'You may update your Facebook user name in the <a href="%1$s" target="_blank">Facebook General Account Settings</a>.', 'wpsso' ), 'https://www.facebook.com/settings?tab=account&section=username&view' );
							break;
						case 'tooltip-fb_app_id':
							$text = sprintf( __( 'If you have a <a href="%1$s" target="_blank">Facebook Application ID for your website</a>, enter it here. The Facebook Application ID will appear in webpage meta tags and is used by Facebook to allow access to <a href="%2$s" target="_blank">Facebook Insight</a> data for accounts associated with that Application ID.', 'wpsso' ), 'https://developers.facebook.com/apps', 'https://developers.facebook.com/docs/insights/' );
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
							$text = 'If you have a <a href="http://www.google.com/+/business/" target="_blank">Google+ Business Page for your website / business</a>, you may enter its URL here (for example, the Google+ Business Page URL for Surnia Ulula is <a href="https://plus.google.com/+SurniaUlula/" target="_blank">https://plus.google.com/+SurniaUlula/</a>). The Google+ Business Page URL will be used in a link relation head tag, and the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
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
						case 'tooltip-schema_social_json':
							$text = 'Include Website, Organization, and/or Person schema markup in the home page for Google\'s Knowledge Graph. The Website markup includes the site name, alternate site name, site URL and search query URL. Developers can hook the "'.$lca.'_json_ld_search_url" filter to modify the site search URL (or disable its addition by returning false). The Organization markup includes all URLs entered on the '.$this->p->util->get_admin_url( 'social-accounts', 'Website Social Pages and Accounts' ).' settings page. The Person markup includes all contact method URLs from the user\'s profile page.';
							break;
						case 'tooltip-schema_alt_name':
							$text = 'An alternate name for your Website that you want Google to consider (optional).';
							break;
						case 'tooltip-schema_logo_url':
							$text = 'A URL for the website / organization\'s logo image that Google can use in search results and its <em>Knowledge Graph</em>.';
							break;
						case 'tooltip-schema_banner_url':
							$text = 'A URL for the website / organization\'s banner image &mdash; <em>measuring exactly 600x60px</em> &mdash; that Google can use as a banner for Articles.';
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
							$text = sprintf( __( 'Select the Schema type for a blog (non-static) home page. The default Schema type is %s.',
								'wpsso' ), 'https://schema.org/CollectionPage' );
							break;
						case 'tooltip-schema_type_for_home_page':
							$text = sprintf( __( 'Select the Schema type for a static home page. The default Schema type is %s.',
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
							$text = 'The <a href="https://business.twitter.com/" target="_blank">Twitter @username for your website and/or business</a> (not your personal Twitter @username). As an example, the Twitter @username for Surnia Ulula is <a href="https://twitter.com/surniaululacom" target="_blank">@surniaululacom</a>. The website / business @username is also used for the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
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
						case 'tooltip-tc_sum_dimensions':
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'getting defaults for tc_sum (width, height, crop)' );
							$def_dimensions = $this->p->opt->get_defaults( 'tc_sum_width' ).'x'.
								$this->p->opt->get_defaults( 'tc_sum_height' ).' '.
								( $this->p->opt->get_defaults( 'tc_sum_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = 'The dimension of content images provided for the <a href="https://dev.twitter.com/docs/cards/types/summary-card" target="_blank">Summary Card</a> (should be at least 120x120, larger than 60x60, and less than 1MB). The default image dimensions are '.$def_dimensions.'.';
							break;
						case 'tooltip-tc_lrgimg_dimensions':
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'getting defaults for tc_lrgimg (width, height, crop)' );
							$def_dimensions = $this->p->opt->get_defaults( 'tc_lrgimg_width' ).'x'.
								$this->p->opt->get_defaults( 'tc_lrgimg_height' ).' '.
								( $this->p->opt->get_defaults( 'tc_lrgimg_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = 'The dimension of Post Meta, Featured or Attached images provided for the <a href="https://dev.twitter.com/docs/cards/large-image-summary-card" target="_blank">Large Image Summary Card</a> (must be larger than 280x150 and less than 1MB). The default image dimensions are '.$def_dimensions.'.';
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_tc', $text, $idx, $info );
							break;
					}	// end of tooltip-tc switch
				/*
				 * Publisher 'Pinterest' (Rich Pin) settings
				 */
				} elseif ( strpos( $idx, 'tooltip-rp_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-rp_publisher_url':
							$text = 'If you have a <a href="https://business.pinterest.com/" target="_blank">Pinterest Business Page for your website / business</a>, you may enter its URL here. The Publisher Business Page URL will be used in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
							break;
						case 'tooltip-rp_img_dimensions':
							$def_dimensions = $this->p->opt->get_defaults( 'rp_img_width' ).'x'.
								$this->p->opt->get_defaults( 'rp_img_height' ).' '.
								( $this->p->opt->get_defaults( 'rp_img_crop' ) == 0 ? 'uncropped' : 'cropped' );

							$text = 'The image dimensions specifically for Rich Pin meta tags when the Pinterest crawler is detected (the default dimensions are '.$def_dimensions.'). Images in the Facebook / Open Graph meta tags are usually cropped square, where-as images on Pinterest often look better in their original aspect ratio (uncropped) and/or cropped using portrait photo dimensions. Note that original images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.';
							break;
						case 'tooltip-rp_author_name':
							$text = sprintf( __( 'Pinterest ignores Facebook-style Author Profile URLs in the %1$s Open Graph meta tags.', 'wpsso' ), '<code>article:author</code>' ).' '.__( 'A different meta tag value can be used when the Pinterest crawler is detected.', 'wpsso' ).' '.sprintf( __( 'Select an <em>%1$s</em> for the %2$s meta tag or "[None]" to disable this feature (the recommended value is "Display Name").', 'wpsso' ), _x( 'Author Name Format', 'option label', 'wpsso' ), '<code>article:author</code>' );
							break;
						case 'tooltip-rp_dom_verify':
							$text = sprintf( __( 'To <a href="%s" target="_blank">verify your website</a> with Pinterest, edit your business account profile on Pinterest and click the "Verify Website" button.', 'wpsso' ), 'https://help.pinterest.com/en/articles/verify-your-website#meta_tag' ).' '.__( 'Enter the supplied "p:domain_verify" meta tag <em>content</em> value here.', 'wpsso' );
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_rp', $text, $idx, $info );
							break;
					}	// end of tooltip-rp switch
				/*
				 * Publisher 'Instagram' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-instgram_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-instgram_publisher_url':
							$text = 'If you have an <a href="http://blog.business.instagram.com/" target="_blank">Instagram account for your website / business</a>, you may enter its URL here. The Instagram Business URL will be used in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
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
							$text = 'If you have a <a href="https://business.linkedin.com/marketing-solutions/company-pages/get-started" target="_blank">LinkedIn Company Page for your website / business</a>, you may enter its URL here (for example, the LinkedIn Company Page URL for Surnia Ulula is <a href="https://www.linkedin.com/company/surnia-ulula-ltd" target="_blank">https://www.linkedin.com/company/surnia-ulula-ltd</a>). The LinkedIn Company Page URL will be included in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_linkedin', $text, $idx, $info );
							break;
					}	// end of tooltip-linkedin switch
				/*
				 * Publisher 'MySpace' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-myspace_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-myspace_publisher_url':
							$text = 'If you have a <a href="http://myspace.com/" target="_blank">MySpace account for your website / business</a>, you may enter its URL here. The MySpace Business (Brand) URL will be used in the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
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
							$text = '<strong>You should not modify the contact field names unless you have a specific reason to do so.</strong> As an example, to match the contact field name of a theme or other plugin, you might change "gplus" to "googleplus". If you change the Facebook or Google+ field names, please make sure to update the Open Graph <em>Author Profile URL</em> and <em>Google Author Link URL</em> options in the '.$this->p->util->get_admin_url( 'general', 'General Settings' ).' as well.';
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
				switch ( $idx ) {
					case 'info-meta-social-preview':
					 	$text = '<p style="text-align:right;">'.__( 'The social preview shows an <em>example</em> link share on Facebook. Images are displayed using Facebooks suggested minimum image dimensions of 600x315px. Actual shares on Facebook and other social websites may look significantly different than this example (depending on the client platform, resolution, orientation, etc.).', 'wpsso' ).'</p>';
					 	break;
					case 'info-plugin-tid':
						$um_info = $this->p->cf['plugin']['wpssoum'];
						$text = '<blockquote class="top-info"><p>'.sprintf( __( 'After purchasing license(s) for %1$s or its Pro extensions, you\'ll receive an email with a unique Authentication ID and installation instructions.', 'wpsso' ), $info['short_pro'] ).' '.__( 'Enter the Authentication IDs in this settings page to upgrade the Free version and access future Pro version updates.', 'wpsso' ).' '.sprintf( __( 'The %1$s extension must be active in order to check for Pro version updates, and %2$s is required to use any of its Pro extensions.', 'wpsso' ), $um_info['name'], $info['short_pro'] ).'</blockquote>';
						break;
					case 'info-plugin-tid-network':
						$um_info = $this->p->cf['plugin']['wpssoum'];
						$text = '<blockquote class="top-info"><p>'.sprintf( __( 'After purchasing license(s) for %1$s or its Pro extensions, you\'ll receive an email with a unique Authentication ID and installation instructions.', 'wpsso' ), $info['short_pro'] ).' '.__( 'You may enter the Authentication IDs on this page <em>to define a value for all sites within the network</em> &mdash; or enter the Authentication IDs individually on each site\'s Pro Licenses settings page.', 'wpsso' ).' '.__( 'If you enter Authentication IDs here, <em>make sure you have purchased enough licenses to license all sites within the network</em> (for example, if you have 10 sites, you will need at least 10 licenses).', 'wpsso' ).' '.__( 'To license one or more sites individually, enter the Authentication ID in each site\'s Pro Licenses settings page.', 'wpsso' ).'</p><p>'.sprintf( __( 'Note that <strong>the default site / blog must be licensed</strong> and the %1$s extension active in order to install Pro version updates from the network admin interface.', 'wpsso' ), $um_info['name'] ).'</p></blockquote>';
						break;
					case 'info-pub-pinterest':
						$text = '<blockquote class="top-info"><p>'.__( 'These options allow you to customize some Open Graph meta tag and Schema markup values for the Pinterest crawler.', 'wpsso' ).' '.__( 'If you use a caching plugin (or front-end caching service), it should detect the Pinterest user-agent and bypass its cache (for example, look for a <em>User-Agent Exclusion Pattern</em> setting and add "Pinterest" to that list).', 'wpsso' ).'</p></blockquote>';
						break;
					case 'info-cm':
						$text = '<blockquote class="top-info"><p>'.sprintf( __( 'The following options allow you to customize the contact fields shown in <a href="%s">the user profile page</a> in the <strong>Contact Info</strong> section.', 'wpsso' ), get_admin_url( null, 'profile.php' ) ).' '.sprintf( __( '%s uses the Facebook, Google+, and Twitter contact values for Facebook / Open Graph, Google / Schema, and Twitter Card meta tags.', 'wpsso' ), $info['short'] ).'</p><p><strong>'.sprintf( __( 'You should not modify the <em>%s</em> unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field Name', 'column title', 'wpsso' ) ).'</strong> '.sprintf( __( 'The <em>%s</em> on the other hand is for display purposes only and it can be changed as you wish.', 'wpsso' ), _x( 'Profile Contact Label', 'column title', 'wpsso' ) ).' ;-)</p><p>'.sprintf( __( 'Enabled contact methods are included on user profile editing pages automatically. Your theme is responsible for using their values in its templates (see the WordPress <a href="%s" target="_blank">get_the_author_meta()</a> documentation for examples).', 'wpsso' ), 'https://codex.wordpress.org/Function_Reference/get_the_author_meta' ).'</p><p><center><strong>'.__( 'DO NOT ENTER YOUR CONTACT INFORMATION HERE &ndash; THESE ARE CONTACT FIELD LABELS ONLY.', 'wpsso' ).'</strong><br/>'.sprintf( __( 'Enter your personal contact information on <a href="%1$s">the user profile page</a>.', 'wpsso' ), get_admin_url( null, 'profile.php' ) ).'</center></p></blockquote>';
						break;
					case 'info-taglist':
						$text = '<blockquote class="top-info"><p>'.sprintf( __( '%s adds the following Google / SEO, Facebook, Open Graph, Rich Pin, Schema, and Twitter Card HTML tags to the <code>&lt;head&gt;</code> section of your webpages.', 'wpsso' ), $info['short'] ).' '.__( 'If your theme or another plugin already creates one or more of these HTML tags, you can uncheck them here to prevent duplicates from being added.', 'wpsso' ).' '.__( 'As an example, the "meta name description" HTML tag is automatically unchecked if a <em>known</em> SEO plugin is detected.', 'wpsso' ).' '.__( 'The "meta name canonical" HTML tag is unchecked by default since themes often include this meta tag in their header template(s).', 'wpsso' ).'</p></blockquote>';
						break;
					case 'info-social-accounts':
						$text = '<blockquote class="top-info"><p>'.__( 'The website / business social account values are used for SEO, Schema, Open Graph, and other social meta tags &ndash; including publisher (Organization) social markup for Google Search.', 'wpsso' ).'</p><p>'.sprintf( __( 'See the <a href="%s">Google / Schema settings tab</a> to define a website / business logo for Google Search results, and enable / disable the addition of publisher (Organization) and/or author (Person) JSON-LD markup.', 'wpsso' ), $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_google' ) ).'</p></blockquote>';
						break;
					default:
						$text = apply_filters( $lca.'_messages_info', $text, $idx, $info );
						break;
				}	// end of info switch
			/*
			 * Misc pro messages
			 */
			} elseif ( strpos( $idx, 'pro-' ) === 0 ) {
				switch ( $idx ) {
					case 'pro-feature-msg':
						if ( $lca !== $this->p->cf['lca'] &&
							! $this->p->check->aop( $this->p->cf['lca'], true, $this->p->is_avail['aop'] ) ) {
								$req_short = $this->p->cf['plugin'][$this->p->cf['lca']]['short'].' Pro';
								$req_msg = '<br>'.sprintf( __( '(note that all %1$s extensions also require a licensed %1$s plugin)',
									'wpsso' ), $req_short );
						} else $req_msg = '';

						$purchase_url = add_query_arg( 'utm_source', $idx, $url['purchase'] );
						if ( $this->p->check->aop( $lca, false ) )
							$text = '<p class="pro-feature-msg"><a href="'.$purchase_url.'" target="_blank">'.
								sprintf( __( 'Purchase %s licence(s) to install its Pro modules and use the following features / options.',
									'wpsso' ), $info['short_pro'] ).'</a>'.$req_msg.'</p>';
						else $text = '<p class="pro-feature-msg"><a href="'.$purchase_url.'" target="_blank">'.
							sprintf( __( 'Purchase the %s plugin to install its Pro modules and use the following features / options.',
								'wpsso' ), $info['short_pro'] ).'</a>'.$req_msg.'</p>';
						break;
					case 'pro-option-msg':
						$purchase_url = add_query_arg( 'utm_source', $idx, $url['purchase'] );
						$text = '<p class="pro-option-msg"><a href="'.$purchase_url.'" target="_blank">'.
							sprintf( _x( '%s required to use this option', 'option comment', 'wpsso' ),
								$info['short_pro'] ).'</a></p>';
						break;
					case 'pro-about-msg-post-text':
						$text = '<p>'.__( 'You can update the excerpt or content text to change the default description values.', 'wpsso' ).'</p>';
						break;
					case 'pro-about-msg-post-media':
						$text = '<p>'.__( 'You can change the social image by selecting a featured image, attaching image(s) or including images in the content.', 'wpsso' ).'<br/>'.sprintf( __( 'The video service modules &mdash; required to detect embedded videos &mdash; are available with the %s Pro version.', 'wpsso' ),  $info['short'] ).'</p>';
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

						if ( empty( $this->p->options['plugin_hide_pro'] ) )
							$text = __( 'A larger and/or different custom image &mdash; specifically for social meta tags and markup &mdash; can be selected in the Social Settings metabox under the <em>Select Media</em> tab.', 'wpsso' );
						else $text = '';

						if ( empty( $info['hard_limit'] ) && current_user_can( 'manage_options' ) ) {
							$text .= '<p><em>'.__( 'Additional information shown only to users with Administrative privileges:', 'wpsso' ).'</em></p>';
							$text .= '<ul>';
							$text .= '<li>'.sprintf( __( 'You can also adjust the <b>%2$s</b> option in the <a href="%1$s">Social and SEO Image Dimensions</a> settings.', 'wpsso' ), $this->p->util->get_admin_url( 'image-dimensions' ), $info['size_label'] ).'</li>';
							$text .= '<li>'.sprintf( __( 'Enable or increase the <a href="%1$s">WP / Theme Integration</a> <em>image upscaling percentage</em> feature.', 'wpsso' ), $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration' ) ).'</li>';
							$text .= '<li>'.sprintf( __( 'Disable the <a href="%1$s">WP / Theme Integration</a> <em>image dimensions check</em> option (not recommended).', 'wpsso' ), $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration' ) ).'</li>';
							if ( empty( $hidden_warnings ) )
								$text .= '<li>'.sprintf( __( 'Define the %1$s constant as <em>true</em> to auto-hide all dismissable warnings.', 'wpsso' ), $hide_const_name ).'</li>';
							$text .= '</ul>';
						}
						break;
					case 'notice-missing-og-image':
						$text = __( 'An Open Graph image meta tag could not be created from this webpage content or its custom Social Settings. Facebook <em>requires at least one image meta tag</em> to render shared content correctly.', 'wpsso' );
						break;
					case 'notice-missing-og-description':
						$text = __( 'An Open Graph description meta tag could not be created from this webpage content or its custom Social Settings. Facebook <em>requires a description meta tag</em> to render shared content correctly.', 'wpsso' );
						break;
					case 'notice-missing-schema-image':
						$text = __( 'A Schema image property could not be created from this webpage content and/or custom settings. Google <em>requires at least one image property</em> for this Schema item type.', 'wpsso' );
						break;
					case 'notice-content-filters-disabled':
						$text = '<p><b>'.sprintf( __( 'The <a href="%1$s">%2$s</a> advanced option is currently disabled.', 'wpsso' ), $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content' ), _x( 'Apply WordPress Content Filters', 'option label', 'wpsso' ) ).'</b> '.sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions, and detect additional images / embedded videos provided by shortcodes.', 'wpsso' ), $info['short'] ).'</p><p><b>'.__( 'Some theme / plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ).'</b> '.sprintf( __( '<a href="%s">If you use any shortcodes in your content text, this option should be enabled</a> &mdash; if you experience display issues after enabling this option, determine which theme / plugin content filter is at fault, and report the problem to its author(s).', 'wpsso' ), $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content' ) ).'</p>';
						break;
					case 'notice-header-tmpl-no-head-attr':
						$action_url = wp_nonce_url( $this->p->util->get_admin_url( '?'.$this->p->cf['lca'].'-action=modify_tmpl_head_attributes' ),
							WpssoAdmin::get_nonce(), WPSSO_NONCE );
						$text = '<p><b>'.__( 'At least one of your theme header templates does not support Schema markup of the webpage head section &mdash; this is especially important for Google and Pinterest.', 'wpsso' ).'</b> '.sprintf( __( 'The %s element in your header templates should include a function, action, or filter for its attributes.', 'wpsso' ), '<code>&lt;head&gt;</code>' ).' '.sprintf( __( '%1$s can update your header templates automatically to change the default %2$s element to:', 'wpsso' ), $info['short'], '<code>&lt;head&gt;</code>' ).'</p>';
						$text .= '<pre><code>&lt;head &lt;?php do_action( \'add_head_attributes\' ); ?&gt;&gt;</code></pre>';
						$text .= '<p>'.sprintf( __( '<b><a href="%1$s">Click here to update header templates automatically</a></b> or update the templates yourself manually.', 'wpsso' ), $action_url ).'</p>';
						break;
					case 'notice-pro-tid-missing':
						if ( ! is_multisite() )
							$text = '<p><b>'.sprintf( __( 'The %1$s plugin %2$s option is empty.', 'wpsso' ), $info['name'], _x( 'Pro Authentication ID', 'option label', 'wpsso' ) ).'</b> '.sprintf( __( 'To enable Pro version features and allow the plugin to authenticate itself for updates, please enter the unique Authentication ID you received by email on the <a href="%s">Extension Plugins and Pro Licenses</a> settings page.', 'wpsso' ), $this->p->util->get_admin_url( 'licenses' ) ).'</p>';
						break;
					case 'notice-pro-not-installed':
						$text = sprintf( __( 'An Authentication ID has been entered for %s, but the Pro version is not yet installed &ndash; don\'t forget to update the plugin to install the latest Pro version. ;-)', 'wpsso' ), $info['name'] );
						break;
					case 'notice-um-extension-required':
					case 'notice-um-activate-extension':
						$um_info = $this->p->cf['plugin']['wpssoum'];
						$wp_upload_url = get_admin_url( null, 'plugin-install.php?tab=upload' );
						$text = '<p><b>'.sprintf( __( 'At least one Authentication ID has been entered on the <a href="%1$s">Extension Plugins and Pro Licenses</a> settings page, but the %2$s plugin is not active.', 'wpsso' ), $this->p->util->get_admin_url( 'licenses' ), $um_info['name'] ).'</b> ';

						if ( $idx === 'notice-um-extension-required' ) {
							$text .= sprintf( __( 'This Free plugin is required to update and enable the %s plugin and its Pro extensions.', 'wpsso' ), $info['name_pro'] ).'</p><ol><li><b>'.sprintf( __( 'Download the Free <a href="%1$s">%2$s plugin archive</a> (ZIP).', 'wpsso' ), $um_info['url']['latest'], $um_info['name'] ).'</b></li><li><b>'.sprintf( __( 'Then <a href="%s">upload and activate the plugin</a> on the WordPress plugin upload page.', 'wpsso' ), $wp_upload_url ).'</b></li></ol>';
						} else $text .= '</p>';

						$text .= '<p>'.sprintf( __( 'Once the %s extension has been activated, one or more Pro version updates may be available for your licensed plugin(s).', 'wpsso' ), $um_info['name'] ).' '.sprintf( __( 'Read more <a href="%1$s" target="_blank">about the %2$s extension plugin</a>.', 'wpsso' ), $um_info['url']['download'], $um_info['name'] ).'</p>';
						break;
					case 'notice-um-version-required':
						$um_info = $this->p->cf['plugin']['wpssoum'];
						$um_version = isset( $um_info['version'] ) ? $um_info['version'] : 'unknown';
						$text = sprintf( __( '%1$s version %2$s requires the use of %3$s version %4$s or newer (version %5$s is currently installed).', 'wpsso' ), $info['name_pro'], $this->p->cf['plugin']['wpsso']['version'], $um_info['short'], $info['min_version'], $um_version ).' '.sprintf( __( 'Use the <em>%1$s</em> button from any %2$s settings page to retrieve the latest update information, or <a href="%3$s" target="_blank">download the latest %4$s extension version</a> and install the ZIP file manually.', 'wpsso' ), _x( 'Check for Pro Update(s)', 'submit button', 'wpsso' ), $this->p->cf['menu_label'], $um_info['url']['download'], $um_info['short'] );
						break;
					case 'notice-recommend-version':
						$text = sprintf( __( 'You are using %1$s version %2$s &mdash; <a href="%3$s">this version is outdated, unsupported, and insecure</a> (and lacks important features). Please update to the latest %1$s stable production release (or at least version %3$s).' ), $info['app_label'], $info['cur_version'], $info['rec_version'], $info['sup_version_url'] );
						break;
					default:
						$text = apply_filters( $lca.'_messages_notice', $text, $idx, $info );
						break;
			}
			/*
			 * Misc sidebox messages
			 */
			} elseif ( strpos( $idx, 'side-' ) === 0 ) {
				switch ( $idx ) {
					case 'side-purchase':
						$text = '<p>';
						if ( $this->p->is_avail['aop'] )
							$text .= sprintf( __( '%s can be purchased quickly and easily via Paypal &ndash; allowing you to license and enable Pro version features within seconds of your purchase.', 'wpsso' ), $info['short_pro'] );
						else $text .= sprintf( __( '%s can be purchased quickly and easily via Paypal &ndash; allowing you to update the plugin within seconds of your purchase.', 'wpsso' ), $info['short_pro'] );
						$text .= ' '.__( 'Pro version licenses do not expire &ndash; there are no yearly or recurring fees for updates and support.', 'wpsso' );
						$text .= '<p>';
						break;
					case 'side-help-support':
						$text = '<p>'.sprintf( __( 'The development of %1$s is driven mostly by customer requests &mdash; we welcome your comments and suggestions. ;-)', 'wpsso' ), $info['short'] ).'</p>';
						break;
					default:
						$text = apply_filters( $lca.'_messages_side', $text, $idx, $info );
						break;
				}
			} else $text = apply_filters( $lca.'_messages', $text, $idx, $info );

			if ( is_array( $info ) && ! empty( $info['is_locale'] ) ) {
				$lang_plugins = '<a href="https://wordpress.org/plugins/wpsso-user-locale/" target="_blank">WPSSO User Locale</a>, '.
					'<a href="https://wordpress.org/plugins/polylang/" target="_blank">Polylang</a>, '.
					'<a href="https://wordpress.org/plugins/wp-native-dashboard/" target="_blank">WP Native Dashboard</a>';
				$text .= ' '.sprintf( __( 'This option is localized &mdash; you may change the WordPress locale with %s, etc., to define alternate option values for different languages.', 'wpsso' ), $lang_plugins );
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
