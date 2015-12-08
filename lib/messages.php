<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
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

		public function get( $idx = false, $atts = array() ) {

			if ( is_string( $atts ) ) {
				$text = $atts;
				$atts = array();
			} else {
				$text = isset( $atts['text'] ) ?
					$atts['text'] : '';
			}

			$idx = sanitize_title_with_dashes( $idx );

			$atts['lca'] = isset( $atts['lca'] ) ?
				$atts['lca'] : $this->p->cf['lca'];
			$lca = $atts['lca'];

			$atts['short'] = isset( $atts['short'] ) ?
				$atts['short'] : $this->p->cf['plugin'][$lca]['short'];
			$atts['short_pro'] = $atts['short'].' Pro';

			$atts['name'] = isset( $atts['name'] ) ?
				$atts['name'] : $this->p->cf['plugin'][$lca]['name'];
			$atts['name_pro'] = $atts['name'].' Pro';

			$url = isset( $this->p->cf['plugin'][$lca]['url'] ) ?
				$this->p->cf['plugin'][$lca]['url'] : array();
			/*
			 * All tooltips
			 */
			if ( strpos( $idx, 'tooltip-' ) === 0 ) {
				/*
				 * 'Plugin Features' side metabox
				 */
				if ( strpos( $idx, 'tooltip-side-' ) === 0 ) {
					switch ( $idx ) {
						/*
						 * Free version
						 */
						case 'tooltip-side-author-json-ld':
							$text = __( 'Include author (Person) social profiles markup to webpage headers in schema.org JSON-LD format for Google Search.', 'wpsso' );
							break;
						case 'tooltip-side-debug-messages':
							$text = sprintf( __( 'The debug library is loaded when the <em>Add Hidden Debug Messages</em> option is checked, or one of the debugging <a href="%s" target="_blank">constants</a> is defined.', 'wpsso' ), 'http://surniaulula.com/codex/plugins/wpsso/notes/constants/' );
							break;
						case 'tooltip-side-non-persistant-cache':
							$text = sprintf( __( 'The plugin saves filtered / rendered content to a non-persistant cache (aka <a href="%1$s" target="_blank">WP Object Cache</a>) for re-use within the same page load. You can disable the use of non-persistant cache (not recommended) using one of the available <a href="%2$s" target="_blank">constants</a>.', 'wpsso' ), 'https://codex.wordpress.org/Class_Reference/WP_Object_Cache', 'http://surniaulula.com/codex/plugins/wpsso/notes/constants/' );
							break;
						case 'tooltip-side-open-graph-rich-pin':
							$text = __( 'Facebook / Open Graph and Pinterest Rich Pin meta tags are added to the head section of all webpages. You must have a supported eCommerce plugin installed to add <em>Product</em> Rich Pins, including product prices and attributes.', 'wpsso' );
							break;
						case 'tooltip-side-publisher-json-ld':
							$text = __( 'Include publisher (Organization) social profiles markup to webpage headers in schema.org JSON-LD format for Google Search.', 'wpsso' );
							break;
						case 'tooltip-side-transient-cache':
							$text = sprintf( __( 'The plugin saves Facebook / Open Graph, Pinterest Rich Pin, Twitter Card meta tags, and JSON-LD markup to a persistant (aka <a href="%1$s" target="_blank">Transient</a>) cache for %2$d seconds. You can adjust the Transient cache expiration value on the <a href="%3$s">%4$s</a> settings page, or disable it completely by using one of the available <a href="%5$s" target="_blank">constants</a>.', 'wpsso' ), 'https://codex.wordpress.org/Transients_API', $this->p->options['plugin_object_cache_exp'], $this->p->util->get_admin_url( 'advanced' ), _x( 'Advanced', 'lib file description', 'wpsso' ), 'http://surniaulula.com/codex/plugins/wpsso/notes/constants/' );
							break;
						case 'tooltip-side-twitter-cards':
							$text = __( 'Twitter Cards extend the standard Facebook / Open Graph and Pinterest Rich Pin meta tags with additional information about your content. Twitter Cards are displayed more prominently on Twitter, allowing you to highlight your content more effectively.', 'wpsso' );
							break;
						/*
						 * Pro version
						 */
						case 'tooltip-side-author-gravatar':
							$text = 'Include the author\'s Gravatar image in meta tags for author index / archive webpages. Enable or disable this option from the '.$this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_author', 'General settings page' ).'.';
							break;
						case 'tooltip-side-post-settings':
							$text = 'The Post Settings feature adds a Social Settings metabox to the Post, Page, and custom post type editing pages. Custom descriptions and images can be entered for Facebook / Open Graph, Pinterest Rich Pin, and Twitter Card meta tags.';
							break;
						case 'tooltip-side-publisher-language':
							$text = $atts['short_pro'].' can use the WordPress locale to select the correct language for the Facebook / Open Graph and Pinterest Rich Pin meta tags'.( empty( $this->p->is_avail['ssb'] ) ? '' : ', along with the Google, Facebook, and Twitter social sharing buttons' ).'. If your website is available in multiple languages, this can be a useful feature.';
							break;
						case 'tooltip-side-slideshare-api':
							$text = 'If the embedded Slideshare Presentations option on the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content', 'Advanced settings' ).' page is checked, '.$atts['short_pro'].' will load an integration module for Slideshare, to detect embedded Slideshare presentations and retrieve slide information using Slideshare\'s oEmbed API (media dimentions, preview image, etc).';
							break;
						case 'tooltip-side-taxonomy-settings':
							$text = 'The Taxonomy Settings feature adds a Social Settings metabox to taxonomy (category and tags) editing pages. Custom descriptions and images can be entered for Facebook / Open Graph, Pinterest Rich Pin, and Twitter Card meta tags.';
							break;
						case 'tooltip-side-url-shortening':
							$text = 'When a Preferred URL Shortening Service has been selected on the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_apikeys', 'Advanced settings' ).' page, '.$atts['short_pro'].' will load an integration module for various '.$atts['short'].' plugin filters and/or extensions that may need to shorten URLs.';
							break;
						case 'tooltip-side-user-settings':
							$text = 'The User Settings feature adds a Social Settings metabox to the user profile pages. Custom descriptions and images can be entered for Facebook / Open Graph, Pinterest Rich Pin, and Twitter Card meta tags.';
							break;
						case 'tooltip-side-vimeo-video-api':
							$text = 'If the embedded Vimeo Videos option in the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content', 'Advanced settings' ).' page is checked, '.$atts['short_pro'].' will load an integration module for Vimeo, to detect embedded Vimeo videos and retrieve video information using Vimeo\'s oEmbed API (media dimentions, preview image, etc).';
							break;
						case 'tooltip-side-wistia-video-api':
							$text = 'If the embedded Wistia Videos option in the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content', 'Advanced settings' ).' page is checked, '.$atts['short_pro'].' will load an integration module for Wistia to detect embedded Wistia videos, and retrieve video information using Wistia\'s oEmbed API (media dimentions, preview image, etc).';
							break;
						case 'tooltip-side-wp-rest-api-routes':
							$text = $atts['short_pro'].' loads a module to extend the WordPress REST API routes.';
							break;
						case 'tooltip-side-youtube-video-playlist-api':
							$text = 'If the embedded Youtube Videos and Playlists option in the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content', 'Advanced settings' ).' page is checked, '.$atts['short_pro'].' will load an integration module for YouTube to detect embedded YouTube videos and playlists, and retrieve video information using Youtube\'s XML and oEmbed APIs (media dimentions, preview image, etc).';
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_side', $text, $idx, $atts );
							break;
					}	// end of tooltip-side switch
				/*
				 * Generic Meta settings
				 */
				} elseif ( strpos( $idx, 'tooltip-meta-' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-meta-social-preview':
						 	$text = 'The Open Graph social preview shows an <em>example</em> of a typical share on a social website. Images are displayed using Facebooks suggested minimum image dimensions of 600x315px. Actual shares on Facebook and other social networks may look significantly different than this <em>example</em> (depending on the viewing platform resolution, orientation, etc.).';
						 	break;
						case 'tooltip-meta-og_title':
							$text = 'A custom title for the Facebook / Open Graph, Pinterest Rich Pin, Twitter Card meta tags (all Twitter Card formats), and the Pinterest, Tumblr, and Twitter sharing captions / texts, depending on some option settings.';
						 	break;
						case 'tooltip-meta-og_desc':
							$text = 'A custom description for the Facebook / Open Graph, Pinterest Rich Pin, and fallback description for other meta tags. The default description value is based on the category / tag description, or user biographical info. Update and save this description to change the default value of all other description fields.';
						 	break;
						case 'tooltip-meta-schema_desc':
							$text = 'A custom description for the Google+ schema description meta tag.';
						 	break;
						case 'tooltip-meta-seo_desc':
							$text = 'A custom description for the Google Search / SEO description meta tag.';
						 	break;
						case 'tooltip-meta-tc_desc':
							$text = 'A custom description for the Twitter Card description meta tag (all Twitter Card formats).';
						 	break;
						case 'tooltip-meta-sharing_url':
							$text = 'A custom sharing URL used in the Facebook / Open Graph, Pinterest Rich Pin meta tags and social sharing buttons. The default sharing URL may be influenced by settings from supported SEO plugins. Please make sure any custom URL you enter here is functional and redirects correctly.';
						 	break;
						case 'tooltip-meta-og_img_id':
							$text = 'A custom Image ID to include first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Large Image Summary\' Twitter Card meta tags,'.( empty( $this->p->is_avail['ssb'] ) ? '' : ' along with the Pinterest and Tumblr social sharing buttons,' ).' before any featured, attached, or content images.';
						 	break;
						case 'tooltip-meta-og_img_url':
							$text = 'A custom image URL (instead of an Image ID) to include first in the Facebook / Open Graph, and \'Large Image Summary\' Twitter Card meta tags. Please make sure your custom image is large enough, or it may be ignored by the social website(s). Facebook recommends an image size of 1200x630 (for retina and high-PPI displays), 600x315 as a minimum, and will ignore any images less than 200x200 (1200x1200 is recommended). <em>This field is disabled if an Image ID has been specified</em>.';
							break;
						case 'tooltip-meta-og_img_max':
							$text = 'The maximum number of images to include in the Facebook / Open Graph meta tags.';
						 	break;
						case 'tooltip-meta-og_vid_embed':
							$text = 'Custom Video Embed HTML to use for the first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Player\' Twitter Card meta tags. If the URL is from Youtube, Vimeo or Wistia, an API connection will be made to retrieve the preferred sharing URL, video dimensions, and video preview image. The '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_social', 'Video Embed HTML Custom Field' ).' advanced option also allows a 3rd-party theme or plugin to provide custom Video Embed HTML for this option.';
						 	break;
						case 'tooltip-meta-og_vid_url':
							$text = 'A custom Video URL to include first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Player\' Twitter Card meta tags. If the URL is from Youtube, Vimeo or Wistia, an API connection will be made to retrieve the preferred sharing URL, video dimensions, and video preview image. The '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_social', 'Video URL Custom Field' ).' advanced option allows a 3rd-party theme or plugin to provide a custom Video URL value for this option.';
						 	break;
						case 'tooltip-meta-og_vid_max':
							$text = 'The maximum number of embedded videos to include in the Facebook / Open Graph meta tags.';
						 	break;
						case 'tooltip-meta-og_vid_prev_img':
							$text = 'When video preview images are enabled and available, they are included in webpage meta tags before any custom, featured, attached, etc. images.';
						 	break;
						case 'tooltip-meta-rp_img_id':
							$text = 'A custom Image ID to include first when the Pinterest crawler is detected.';
						 	break;
						case 'tooltip-meta-rp_img_url':
							$text = 'A custom image URL (instead of an Image ID) to include first when the Pinterest crawler is detected. <em>This field is disabled if an Image ID has been specified</em>.';
						 	break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_user', $text, $idx, $atts );
							break;
					}	// end of tooltip-user switch
				/*
				 * Post Meta settings
				 */
				} elseif ( strpos( $idx, 'tooltip-post-' ) === 0 ) {
					$ptn = empty( $atts['ptn'] ) ? 'Post' : $atts['ptn'];
					switch ( $idx ) {
						case 'tooltip-post-og_art_section':
							$text = 'A custom topic, different from the default Article Topic selected in the General settings. The Facebook / Open Graph \'og:type\' meta tag must be an \'article\' to enable this option. The value will be used in the \'article:section\' Facebook / Open Graph and Pinterest Rich Pin meta tags. Select \'[none]\' if you prefer to exclude the \'article:section\' meta tag.';
						 	break;
						case 'tooltip-post-og_desc':
							$text = 'A custom description for the Facebook / Open Graph, Pinterest Rich Pin, and fallback description for other meta tags. The default description value is based on the content, or excerpt if one is available, and is refreshed when the (draft or published) '.$ptn.' is saved. Update and save this description to change the default value of all other description fields.';
						 	break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_post', $text, $idx, $atts );
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
							$text = 'The image dimensions used in the Facebook / Open Graph meta tags (the default dimensions are '.$this->p->opt->get_defaults( 'og_img_width' ).'x'.$this->p->opt->get_defaults( 'og_img_height' ).' '.( $this->p->opt->get_defaults( 'og_img_crop' ) == 0 ? 'un' : '' ).'cropped). Facebook recommends 1200x630 cropped (for retina and high-PPI displays), and 600x315 as a minimum. <strong>1200x1200 cropped provides the greatest compatibility with all social websites (Facebook, Google+, LinkedIn, etc.)</strong>. Note that original images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.';
							break;
						case 'tooltip-og_def_img_id':
							$text = 'The ID number and media location of your default image (example: 123). The Default Image ID will be used as a <strong>fallback for Posts and Pages that do not have any images</strong> <em>featured</em>, <em>attached</em>, or suitable &lt;img/&gt; HTML tags in their content. The ID number for images in the WordPress Media Library can be found in the URL when editing an image (post=123 in the URL, for example). The NextGEN Gallery image IDs are easier to find -- it\'s the number in the first column when viewing a Gallery.';
							break;
						case 'tooltip-og_def_img_url':
							$text = 'You can enter a Default Image URL (including the http:// prefix) instead of choosing a Default Image ID (if a Default Image ID is specified, the Default Image URL option is disabled). The Default Image URL option allows you to <strong>use an image outside of a managed collection (WordPress Media Library or NextGEN Gallery), and/or a smaller logo style image</strong>. The image should be at least '.$this->p->cf['head']['min_img_dim'].'x'.$this->p->cf['head']['min_img_dim'].' or more in width and height. The Default Image ID or URL is used as a <strong>fallback for Posts and Pages that do not have any images</strong> <em>featured</em>, <em>attached</em>, or suitable &lt;img/&gt; HTML tags in their content.';
							break;
						case 'tooltip-og_def_img_on_index':
							$text = 'Check this option to force the default image on index webpages (<strong>non-static</strong> homepage, archives, categories). If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$atts['short'].' will use image(s) from the first entry on the webpage (default is checked).';
							break;
						case 'tooltip-og_def_img_on_search':
							$text = 'Check this option to force the default image on search results. If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$atts['short'].' will use image(s) returned in the search results (default is unchecked).';
							break;
						case 'tooltip-og_def_vid_url':
							$text = 'The Default Video URL is used as a <strong>fallback value for Posts and Pages that do not have any videos</strong> in their content. Do not specify a Default Video URL <strong>unless you want to include video information in all your Posts and Pages</strong>.';
							break;
						case 'tooltip-og_def_vid_on_index':
							$text = 'Check this option to force the default video on index webpages (<strong>non-static</strong> homepage, archives, categories). If this option is <em>checked</em>, but a Default Video URL has not been defined, then <strong>no video will be included in the meta tags</strong> (this is usually preferred). If the option is <em>unchecked</em>, then '.$atts['short'].' will use video(s) from the first entry on the webpage (default is checked).';
							break;
						case 'tooltip-og_def_vid_on_search':
							$text = 'Check this option to force the default video on search results. If this option is <em>checked</em>, but a Default Video URL has not been defined, then <strong>no video will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$atts['short'].' will use video(s) returned in the search results (default is unchecked).';
							break;
						case 'tooltip-og_ngg_tags':
							$text = 'If the <em>featured</em> image in a Post or Page is from a NextGEN Gallery, then add that image\'s tags to the Facebook / Open Graph and Pinterest Rich Pin tag list (default is unchecked).';
							break;
						case 'tooltip-og_img_max':
							$text = 'The maximum number of images to list in the Facebook / Open Graph and Pinterest Rich Pin meta tags -- this includes the <em>featured</em> or <em>attached</em> images, and any images found in the Post or Page content. If you select \'0\', then no images will be listed in the facebook / Open Graph and Pinterest Rich Pin meta tags (<strong>not recommended</strong>). If no images are listed in your meta tags, then social websites may choose an unsuitable image from your webpage (including headers, sidebars, etc.).';
							break;
						case 'tooltip-og_vid_max':
							$text = 'The maximum number of videos, found in the Post or Page content, to include in the Facebook / Open Graph and Pinterest Rich Pin meta tags. If you select \'0\', then no videos will be listed in the Facebook / Open Graph and Pinterest Rich Pin meta tags.';
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
							$text = 'The topic that best describes the Posts and Pages on your website. This value will be used in the \'article:section\' Facebook / Open Graph and Pinterest Rich Pin meta tags. Select \'[none]\' if you prefer to exclude the \'article:section\' meta tag. The Pro version also allows you to select a custom Topic for each individual Post and Page.';
							break;
						case 'tooltip-og_site_name':
							$text = 'The WordPress Site Name is used for the Facebook / Open Graph and Pinterest Rich Pin site name (og:site_name) meta tag. You may override <a href="'.get_admin_url( null, 'options-general.php' ).'">the default WordPress Site Title value</a>.';
							break;
						case 'tooltip-og_site_description':
							$text = 'The WordPress Tagline is used as a description for the <em>index</em> (non-static) home page, and as a fallback for the Facebook / Open Graph and Pinterest Rich Pin description (og:description) meta tag. You may override <a href="'.get_admin_url( null, 'options-general.php' ).'">the default WordPress Tagline value</a> here, to provide a longer and more complete description of your website.';
							break;
						case 'tooltip-og_title_sep':
							$text = 'One or more characters used to separate values (category parent names, page numbers, etc.) within the Facebook / Open Graph and Pinterest Rich Pin title string (the default is the hyphen \''.$this->p->opt->get_defaults( 'og_title_sep' ).'\' character).';
							break;
						case 'tooltip-og_title_len':
							$text = 'The maximum length of text used in the Facebook / Open Graph and Rich Pin title tag (default is '.$this->p->opt->get_defaults( 'og_title_len' ).' characters).';
							break;
						case 'tooltip-og_desc_len':
							$text = 'The maximum length of text used in the Facebook / Open Graph and Rich Pin description tag. The length should be at least '.$this->p->cf['head']['min_desc_len'].' characters or more, and the default is '.$this->p->opt->get_defaults( 'og_desc_len' ).' characters.';
							break;
						case 'tooltip-og_page_title_tag':
							$text = 'Add the title of the <em>Page</em> to the Facebook / Open Graph and Pinterest Rich Pin article tag and Hashtag list (default is unchecked). If the Add Page Ancestor Tags option is checked, all the titles of the ancestor Pages will be added as well. This option works well if the title of your Pages are short (one or two words) and subject-oriented.';
							break;
						case 'tooltip-og_page_parent_tags':
							$text = 'Add the WordPress tags from the <em>Page</em> ancestors (parent, parent of parent, etc.) to the Facebook / Open Graph and Pinterest Rich Pin article tags and Hashtag list (default is unchecked).';
							break;
						case 'tooltip-og_desc_hashtags':
							$text = 'The maximum number of tag names (converted to hashtags) to include in the Facebook / Open Graph and Pinterest Rich Pin description, tweet text, and social captions. Each tag name is converted to lowercase with whitespaces removed.  Select \'0\' to disable the addition of hashtags.';
							break;
						/*
						 * 'Authorship' settings
						 */
						case 'tooltip-og_author_field':
							$text = __( 'Select which contact field to use from the author\'s profile page for the Facebook / Open Graph and Pinterest Rich Pin \'article:author\' meta tag(s). The preferred setting is the Facebook URL field (default value).', 'wpsso' );
							break;
						case 'tooltip-og_author_fallback':
							$text = sprintf( __( 'If the \'%1$s\' (and the \'%2$s\' in the Google settings below) is not a valid URL, then %3$s can fallback to using the author index / archive page on this website (for example, \'%4$s\').', 'wpsso' ), _x( 'Author Profile URL Field', 'option label', 'wpsso' ), _x( 'Author Link URL Field', 'option label', 'wpsso' ), $atts['short'], trailingslashit( site_url() ).'author/username' ).' '.__( 'Uncheck this option to disable the fallback feature (default is unchecked).', 'wpsso' );
							break;
						case 'tooltip-og_def_author_id':
							$text = 'A default author for webpages <em>missing authorship information</em> (for example, an index webpage without posts). If you have several authors on your website, you should probably leave this option set to <em>[none]</em> (the default).';
							break;
						case 'tooltip-og_def_author_on_index':
							$text = 'Check this option if you would like to force the Default Author on index webpages (<strong>non-static</strong> homepage, archives, categories, author, etc.). If this option is checked, index webpages will be labeled as a an \'article\' with authorship attributed to the Default Author (default is unchecked). If the Default Author is <em>[none]</em>, then the index webpages will be labeled as a \'website\'.';
							break;
						case 'tooltip-og_def_author_on_search':
							$text = 'Check this option if you would like to force the Default Author on search result webpages as well.  If this option is checked, search results will be labeled as a an \'article\' with authorship attributed to the Default Author (default is unchecked).';
							break;
						case 'tooltip-og_author_gravatar':
							$text = 'Check this option to include the author\'s Gravatar image in meta tags for author index / archive webpages. If the "<strong>Use Default Image on <em>Author</em> Index</strong>" option is also checked under the Images tab (unchecked by default), then the default image will be used instead for author index / archive webpages.';
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_og', $text, $idx, $atts );
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
						case 'tooltip-plugin_debug':
							$text = 'Add hidden debug messages to the HTML of webpages (default is unchecked).';
							break;
						case 'tooltip-plugin_show_opts':
							$text = 'Select the default number of options to display on the '.$atts['short'].' settings pages by default. The basic view shows only the essential options that are most commonly used.';
							break;
						case 'tooltip-plugin_preserve':
							$text = 'Check this option if you would like to preserve all '.$atts['short'].' settings when you <em>uninstall</em> the plugin (default is unchecked).';
							break;
						case 'tooltip-plugin_cache_info':
							$text = 'Report the number of objects removed from the cache when updating Posts and Pages.';
							break;
						case 'tooltip-plugin_filter_lang':
							$text = $atts['short_pro'].' can use the WordPress locale to select the correct language for the Facebook / Open Graph and Pinterest Rich Pin meta tags'.( empty( $this->p->is_avail['ssb'] ) ? '' : ', along with the Google, Facebook, and Twitter social sharing buttons' ).'. If your website is available in multiple languages, this can be a useful feature. Uncheck this option to ignore the WordPress locale and always use the configured language.'; 
							break;
						case 'tooltip-plugin_auto_img_resize':
							$text = 'Automatically generate missing or incorrect image sizes for previously uploaded images in the WordPress Media Library (default is checked).';
							break;
						case 'tooltip-plugin_ignore_small_img':
							$text = 'Images that are detected by '.$atts['short'].' must be equal to (or larger) than the '.$this->p->util->get_admin_url( 'image-dimensions', 'Social Image Dimensions' ).' you\'ve chosen. Uncheck this option to disable the image dimension checks. <em>Unchecking this option is not advised</em> &mdash; if you uncheck this option, images that are too small for some social websites may be included in your meta tags.';
							break;
						case 'tooltip-plugin_shortcodes':
							$text = 'Enable the '.$atts['short'].' shortcode features (default is checked).';
							break;
						case 'tooltip-plugin_widgets':
							$text = 'Enable the '.$atts['short'].' widget features (default is checked).';
							break;
						/*
						 * 'Content and Filters' settings
						 */
						case 'tooltip-plugin_filter_title':
							$text = 'By default, '.$atts['short'].' uses the title values provided by WordPress, which may include modifications by themes and/or SEO plugins (appending the blog name to all titles, for example, is a fairly common practice). If you wish to use the original title value without these modifications, uncheck this option.';
							break;
						case 'tooltip-plugin_filter_content':
							$text = 'Apply the standard WordPress \'the_content\' filter to render content text (default is unchecked). This renders all shortcodes, and allows '.$atts['short'].' to detect images and embedded videos that may be provided by these.';
							break;
						case 'tooltip-plugin_filter_excerpt':
							$text = 'Apply the standard WordPress \'get_the_excerpt\' filter to render the excerpt text (default is unchecked). Check this option if you use shortcodes in your excerpt, for example.';
							break;
						case 'tooltip-plugin_p_strip':
							$text = 'If a Page or Post does <em>not</em> have an excerpt, and this option is checked, the plugin will ignore all text until the first html paragraph tag in the content. If an excerpt exists, then this option is ignored and the complete text of the excerpt is used.';
							break;
						case 'tooltip-plugin_use_img_alt':
							$text = 'If the content is empty, or comprised entirely of HTML tags (that must be stripped to create a description text), '.$atts['short'].' can extract and use text from the image <em>alt=""</em> attributes instead of returning an empty description.';
							break;
						case 'tooltip-plugin_img_alt_prefix':
							$text = 'When use of the image <em>alt=""</em> text is enabled, '.$atts['short'].' can prefix that text with an optional string. Leave this option empty to prevent image alt text from being prefixed.';
							break;
						case 'tooltip-plugin_p_cap_prefix':
							$text = $atts['short'].' can add a custom text prefix to paragraphs assigned the "wp-caption-text" class. Leave this option empty to prevent caption paragraphs from being prefixed.';
							break;
						case 'tooltip-plugin_embedded_media':
							$text = 'Check the Post and Page content, along with the custom Social Settings, for embedded media URLs from supported media providers (Youtube, Wistia, etc.). If a supported URL is found, an API connection to the provider will be made to retrieve information about the media (preview image, flash player url, oembed player url, video width / height, etc.).';
							break;
						case 'tooltip-plugin_page_excerpt':
							$text = 'Enable the excerpt editing metabox for Pages. Excerpts are optional hand-crafted summaries of your content that '.$atts['short'].' can use as a default description value.';
							break;
						case 'tooltip-plugin_page_tags':
							$text = 'Enable the tags editing metabox for Pages. Tags are optional keywords that highlight the content subject(s), often used for searches and "tag clouds". '.$atts['short'].' converts tags into hashtags for some social websites (Twitter, Facebook, Google+, etc.).';
							break;
						/*
						 * 'Social Settings' settings
						 */
						case 'tooltip-plugin_social_columns':
							$text = '\'Social Image\' and \'Social Description\' columns are added to the Posts, Pages, Taxonomy, and Users list pages by default. You can exclude the columns individually from the \'Screen Options\' tab on the list pages, or disable the columns globally by unchecking these options.';
							break;
						case 'tooltip-plugin_add_to':
							$text = 'The Social Settings metabox, which allows you to enter custom Facebook / Open Graph values (among other options), is available on the User, Posts, Pages, Media, and Product admin pages by default. If your theme (or another plugin) supports additional custom post types, and you would like to include the Social Settings metabox on their admin pages, check the appropriate option(s) here.';
							break;
						case 'tooltip-plugin_add_tab':
							$text = 'Include and exclude specific tabs in the Social Settings metabox.';
							break;
						case 'tooltip-plugin_cf_img_url':
							$text = 'If your theme or another plugin provides a custom field for image URLs, you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the "<strong>Image URL</strong>" option in the Social Settings metabox. The default value is "'.$this->p->opt->get_defaults( 'plugin_cf_img_url' ).'".';
							break;
						case 'tooltip-plugin_cf_vid_url':
							$text = 'If your theme or another plugin provides a custom field for video URLs (not embed HTML code), you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the "<strong>Video URL</strong>" option in the Social Settings metabox. The default value is "'.$this->p->opt->get_defaults( 'plugin_cf_vid_url' ).'".';
							break;
						case 'tooltip-plugin_cf_vid_embed':
							$text = 'If your theme or another plugin provides a custom field for video embed HTML code (not simply a URL), you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the "<strong>Video Embed HTML</strong>" option in the Social Settings metabox. The default value is "'.$this->p->opt->get_defaults( 'plugin_cf_vid_embed' ).'".';
							break;
						/*
						 * 'WP / Theme Integration' settings
						 */
						case 'tooltip-plugin_check_head':
							$text = $atts['short'].' can check the front-end webpage head section for duplicate HTML tags when editing Posts and Pages. You may uncheck this option if you\'ve edited a few Posts and Pages without seeing any warning messages about duplicate HTML tags.';
							break;
						case 'tooltip-plugin_html_attr_filter':
							$text = $atts['short'].' hooks the "language_attributes" filter to add / modify required Open Graph namespace prefix values by default. The "language_attributes" filter and function are used by most themes &mdash; if the namespace prefix values are missing from your &amp;lt;html&amp;gt; element, make sure your header template(s) use the language_attributes() function. Leaving this option blank disables the addition of Open Graph namespace values. Example template code: <pre><code>&amp;lt;html &amp;lt;?php language_attributes(); ?&amp;gt;&amp;gt;</code></pre>';
							break;
						case 'tooltip-plugin_head_attr_filter':
							$text = $atts['short'].' hooks the "head_attributes" filter to add / modify the <code>&amp;lt;head&amp;gt;</code> element attributes for the Schema itemscope / itemtype markup. If your theme offers a filter for <code>&amp;lt;head&amp;gt;</code> element attributes, enter its name here. Alternatively, you can add an action manually in your header templates to call the "head_attributes" filter. Example code:
<pre><code>&amp;lt;head &amp;lt;?php do_action( \'add_head_attributes\' ); ?&amp;gt;&amp;gt;</code></pre>';
							break;
						/*
						 * 'File and Object Cache' settings
						 */
						case 'tooltip-plugin_object_cache_exp':
							// use the original un-filtered value
							$exp_sec = WpssoConfig::$cf['opt']['defaults']['plugin_object_cache_exp'];
							$exp_hrs = sprintf( '%0.2d', $exp_sec / 60 / 60 );
							$text = '<p>'.$atts['short'].' saves filtered and rendered content to a non-persistant cache (aka <a href="https://codex.wordpress.org/Class_Reference/WP_Object_Cache" target="_blank">WP Object Cache</a>), and the meta tag HTMLs to a persistant (aka <a href="https://codex.wordpress.org/Transients_API" target="_blank">Transient</a>) cache. The default is '.$exp_sec.' seconds ('.$exp_hrs.' hrs), and the minimum value is 1 second (values bellow 3600 seconds are not recommended).</p><p>If you have database performance issues, or don’t use an object / transient cache (like APC, XCache, memcache, etc.), you may want to disable the transient caching feature completely by setting the WPSSO_TRANSIENT_CACHE_DISABLE constant to true.</p>';
							break;
						case 'tooltip-plugin_verify_certs':
							$text = 'Enable verification of peer SSL certificates when fetching content to be cached using HTTPS. The PHP \'curl\' function will use the '.WPSSO_CURL_CAINFO.' certificate file by default. You can define a WPSSO_CURL_CAINFO constant in your wp-config.php file to use an alternate certificate file.';
							break;
						case 'tooltip-plugin_file_cache_exp':
							$text = $atts['short_pro'].' can save most social sharing JavaScript and images to a cache folder, providing URLs to these cached files instead of the originals. A value of 0 hours (the default) disables the file caching feature. If your hosting infrastructure performs reasonably well, this option can improve page load times significantly. All social sharing images and javascripts will be cached, except for the Facebook JavaScript SDK, which does not work correctly when cached.';
							break;
						/*
						 * 'Service API Keys' (URL Shortening) settings
						 */
						case 'tooltip-plugin_shortener':
							$text = sprintf( __( 'A preferred URL shortening service for %s plugin filters and/or extensions that may need to shorten URLs &mdash; don\'t forget to define the Service API Keys for the URL shortening service of your choice.', 'wpsso' ), $atts['short'] );
							break;
						case 'tooltip-plugin_shortlink':
							$text = __( 'The <em>Get Shortlink</em> button on Posts / Pages admin editing pages provides the shortened sharing URL instead of the default WordPress shortlink URL.', 'wpsso' );
							break;
						case 'tooltip-plugin_min_shorten':
							$text = sprintf( __( 'URLs shorter than this length will not be shortened (the default suggested by Twitter is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'plugin_min_shorten' ) );
							break;
						case 'tooltip-plugin_bitly_login':
							$text = sprintf( __( 'The username for your Bit.ly API key (see <a href="%s" target="_blank">Your Bit.ly API Key</a> for details).', 'wpsso' ), 'https://bitly.com/a/your_api_key' );
							break;
						case 'tooltip-plugin_bitly_api_key':
							$text = sprintf( __( 'To use Bit.ly as your preferred shortening service, you must provide the Bit.ly API key for this website (see <a href="%s" target="_blank">Your Bit.ly API Key</a> for details).', 'wpsso' ), 'https://bitly.com/a/your_api_key' );
							break;
						case 'tooltip-plugin_owly_api_key':
							$text = sprintf( __( 'To use Ow.ly as your preferred shortening service, you must provide the Ow.ly API key for this website (complete this form to <a href="%s" target="_blank">Request Ow.ly API Access</a>).', 'wpsso' ), 'https://docs.google.com/forms/d/1Fn8E-XlJvZwlN4uSRNrAIWaY-nN_QA3xAHUJ7aEF7NU/viewform' );
							break;
						case 'tooltip-plugin_google_api_key':
							$text = sprintf( __( 'The Google BrowserKey value for this website (project). If you don\'t already have a Google project, visit <a href="%s" target="_blank">Google\'s Cloud Console</a> and create a new project for your website (use the \'Select a project\' drop-down).', 'wpsso' ), 'https://console.developers.google.com/start' );
							break;
						case 'tooltip-plugin_google_shorten':
							$text = sprintf( __( 'In order to use Google\'s URL Shortener API service, you must <em>Enable</em> the URL Shortener API from <a href="%s" target="_blank">Google\'s Cloud Console</a> (under the project\'s <em>API &amp; auth / APIs / URL Shortener API</em> settings page).', 'wpsso' ), 'https://console.developers.google.com/start' ).' '.__( 'Confirm that you have enabled Google\'s URL Shortener API service by checking the \'Yes\' option here.', 'wpsso' );
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_plugin', $text, $idx, $atts );
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
							$text = sprintf( __( 'The \'%1$s\' are used by Facebook to allow access to <a href="%2$s" target="_blank">Facebook Insight</a> data for your website. Note that these are <strong>user account names, not Facebook Page names</strong>. Enter one or more Facebook user names, separated with commas. When viewing your own Facebook wall, your user name is located in the URL (for example, https://www.facebook.com/<strong>user_name</strong>). Enter only the user names, not the URLs.', 'wpsso' ), _x( 'Facebook Admin Username(s)', 'option label', 'wpsso' ), 'https://developers.facebook.com/docs/insights/' ).' '.sprintf( __( 'You may update your Facebook user name in the <a href="%1$s" target="_blank">Facebook General Account Settings</a>.', 'wpsso' ), 'https://www.facebook.com/settings?tab=account&section=username&view' );
							break;
						case 'tooltip-fb_app_id':
							$text = sprintf( __( 'If you have a <a href="%1$s" target="_blank">Facebook Application ID for your website</a>, enter it here. The Facebook Application ID will appear in webpage meta tags and is used by Facebook to allow access to <a href="%2$s" target="_blank">Facebook Insight</a> data for accounts associated with that Application ID.', 'wpsso' ), 'https://developers.facebook.com/apps', 'https://developers.facebook.com/docs/insights/' );
							break;
						case 'tooltip-fb_lang':
							$text = __( 'The default language of your website content, used in the Facebook / Open Graph and Pinterest Rich Pin meta tags. The Pro version can also use the WordPress locale to adjust the language value dynamically (useful for websites with multilingual content).', 'wpsso' );
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_fb', $text, $idx, $atts );
							break;
					}	// end of tooltip-fb switch
				/*
				 * Publisher 'Google' settings
				 */
				} elseif ( strpos( $idx, 'tooltip-google_' ) === 0 ) {
					switch ( $idx ) {
						case 'tooltip-google_publisher_url':
							$text = 'If you have a <a href="http://www.google.com/+/business/" target="_blank">Google+ Business Page for your website / business</a>, you may enter its URL here (for example, the Google+ Business Page URL for Surnia Ulula is <a href="https://plus.google.com/+SurniaUlula/" target="_blank">https://plus.google.com/+SurniaUlula/</a>). The Google+ Business Page URL will be used in a link relation header tag, and the schema publisher (Organization) social JSON. '.__( 'Google Search may use this information to display additional publisher / business details in its search results.', 'wpsso' );
							break;
						case 'tooltip-google_seo_desc_len':
							$text = 'The maximum length of text used for the Google Search / SEO description meta tag. The length should be at least '.$this->p->cf['head']['min_desc_len'].' characters or more (the default is '.$this->p->opt->get_defaults( 'seo_desc_len' ).' characters).';
							break;
						case 'tooltip-google_author_name':
							$text = sprintf( __( 'Select an \'%1$s\' to use for the \'%2$s\' meta tag, or \'[none]\' to disable this feature (the recommended value is \'Display Name\').', 'wpsso' ), _x( 'Author Name Format', 'option label', 'wpsso' ), 'author' ).' Facebook uses the "author" meta tag value to credit the author on timeline shares, but the <strong>Facebook Debugger will show a warning</strong> &mdash; thus it is disabled by default. Now that you know about the false warning from the Facebook Debugger, you should set this option to \'Display Name\'. ;-)';
							break;
						case 'tooltip-google_author_field':
							$text = $atts['short'].' can include an <em>author</em> and <em>publisher</em> link in your webpage headers. These are not Facebook / Open Graph and Pinterest Rich Pin meta property tags &mdash; they are used primarily by Google\'s search engine to associate Google+ profiles with search results. Select which field to use from the author\'s profile for the <em>author</em> link tag.';
							break;
						case 'tooltip-google_def_author_id':
							$text = 'A default author for webpages missing authorship information (for example, an index webpage without posts). If you have several authors on your website, you should probably leave this option set to <em>[none]</em> (the default). This option is similar to the Facebook / Open Graph and Pinterest Rich Pin Default Author, except that it\'s applied to the Link meta tag instead.';
							break;
						case 'tooltip-google_def_author_on_index':
							$text = 'Check this option if you would like to force the Default Author on index webpages (<strong>non-static</strong> homepage, archives, categories, author, etc.).';
							break;
						case 'tooltip-google_def_author_on_search':
							$text = 'Check this option if you would like to force the Default Author on search result webpages as well.';
							break;
						case 'tooltip-google_schema_logo_url':
							$text = 'The URL to an image that Google should use as your organization\'s logo in search results and their <em>Knowledge Graph</em>.';
							break;
						case 'tooltip-google_schema_desc_len':
							$text = 'The maximum length of text used for the Google+ / Schema description meta tag. The length should be at least '.$this->p->cf['head']['min_desc_len'].' characters or more (the default is '.$this->p->opt->get_defaults( 'schema_desc_len' ).' characters).';
							break;
						case 'tooltip-google_schema_post_type':
							$text = 'Select the Schema item type used in webpage headers for each WordPress post type.';
							break;
						case 'tooltip-google_schema_author_json':
							$text = 'Include author (Person) social profiles markup to webpage headers for Google Search. <strong>The author must have entered a valid URL in the Website field of their user profile page</strong>. All URLs within the various contact method fields will be listed in the social profile markup. The "Twitter @username" field will be used to include a URL for their Twitter profile.';
							break;
						case 'tooltip-google_schema_publisher_json':
							$text = 'Include publisher (Organization) social profiles markup to webpage headers for Google Search. All URLs entered on the '.$this->p->util->get_admin_url( 'social-accounts', 'Website / Business Social Accounts settings page' ).' will be included. The Open Graph Default Image ID / URL will be used as the Organization image, and the Schema Website / Business Logo URL will be used as the Organization\'s logo.';
							break;
						case 'tooltip-google_schema_website_json':
							$text = 'Include Website schema markup in webpage headers for Google Search. The Website information includes the site name, URL, and search query URL.';
							break;
						case 'tooltip-google_schema_add_noscript':
							$text = 'When additional schema properties are available (product ratings, for example), one or more "noscript" containers can be included in webpage headers. The "noscript" container is read correctly by the Google Structured Data Testing Tool, but the W3C Validator will show errors for the included meta tags (these errors can be safely ignored).';
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_google', $text, $idx, $atts );
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
							$text = 'The maximum length of text used for the Twitter Card description. The length should be at least '.$this->p->cf['head']['min_desc_len'].' characters or more (the default is '.$this->p->opt->get_defaults( 'tc_desc_len' ).' characters).';
							break;
						case 'tooltip-tc_sum_dimensions':
							$card = 'sum';
							$text = 'The dimension of content images provided for the <a href="https://dev.twitter.com/docs/cards/types/summary-card" target="_blank">Summary Card</a> (should be at least 120x120, larger than 60x60, and less than 1MB). The default image dimensions are '.$this->p->opt->get_defaults( 'tc_'.$card.'_width' ).'x'.$this->p->opt->get_defaults( 'tc_'.$card.'_height' ).', '.( $this->p->opt->get_defaults( 'tc_'.$card.'_crop' ) ? '' : 'un' ).'cropped.';
							break;
						case 'tooltip-tc_lrgimg_dimensions':
							$card = 'lrgimg';
							$text = 'The dimension of Post Meta, Featured or Attached images provided for the <a href="https://dev.twitter.com/docs/cards/large-image-summary-card" target="_blank">Large Image Summary Card</a> (must be larger than 280x150 and less than 1MB). The default image dimensions are '.$this->p->opt->get_defaults( 'tc_'.$card.'_width' ).'x'.$this->p->opt->get_defaults( 'tc_'.$card.'_height' ).', '.( $this->p->opt->get_defaults( 'tc_'.$card.'_crop' ) ? '' : 'un' ).'cropped.';
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_tc', $text, $idx, $atts );
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
							$text = 'The image dimensions specifically for Rich Pin meta tags when the Pinterest crawler is detected (the default dimensions are '.$this->p->opt->get_defaults( 'rp_img_width' ).'x'.$this->p->opt->get_defaults( 'rp_img_height' ).' '.( $this->p->opt->get_defaults( 'rp_img_crop' ) == 0 ? 'un' : '' ).'cropped). Images in the Facebook / Open Graph meta tags are usually cropped square, where-as images on Pinterest often look better in their original aspect ratio (uncropped) and/or cropped using portrait photo dimensions. Note that original images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.';
							break;
						case 'tooltip-rp_author_name':
							$text = __( 'Pinterest ignores Facebook-style Author Profile URLs in the \'article:author\' Open Graph meta tags.', 'wpsso' ).' '.__( 'An additional \'article:author\' meta tag can be included when the Pinterest crawler is detected.', 'wpsso' ).' '.sprintf( __( 'Select an \'%1$s\' to use for the \'%2$s\' meta tag, or \'[none]\' to disable this feature (the recommended value is \'Display Name\').', 'wpsso' ), _x( 'Author Name Format', 'option label', 'wpsso' ), 'article:author' );
							break;
						case 'tooltip-rp_dom_verify':
							$text = sprintf( __( 'To <a href="%s" target="_blank">verify your website</a> with Pinterest, edit your business account profile on Pinterest and click the "Verify Website" button.', 'wpsso' ), 'https://help.pinterest.com/en/articles/verify-your-website#meta_tag' ).' '.__( 'Enter the supplied \'p:domain_verify\' meta tag <em>content</em> value here.', 'wpsso' );
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip_rp', $text, $idx, $atts );
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
							$text = apply_filters( $lca.'_messages_tooltip_instgram', $text, $idx, $atts );
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
							$text = apply_filters( $lca.'_messages_tooltip_linkedin', $text, $idx, $atts );
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
							$text = apply_filters( $lca.'_messages_tooltip_myspace', $text, $idx, $atts );
							break;
						}	// end of tooltip-myspace switch
				/*
				 * All other settings
				 */
				} else {
					switch ( $idx ) {
						case 'tooltip-custom-cm-field-name':
							$text = '<strong>You should not modify the contact field names unless you have a specific reason to do so.</strong> As an example, to match the contact field name of a theme or other plugin, you might change \'gplus\' to \'googleplus\'. If you change the Facebook or Google+ field names, please make sure to update the Open Graph <em>Author Profile URL</em> and <em>Google Author Link URL</em> options in the '.$this->p->util->get_admin_url( 'general', 'General settings' ).' as well.';
							break;
						case 'tooltip-wp-cm-field-name':
							$text = __( 'The built-in WordPress contact field names cannot be modified.', 'wpsso' );
							break;
						case 'tooltip-site-use':
							$text = __( 'Individual sites/blogs may use this value as a default (when the plugin is first activated), if the current site/blog option value is blank, or force every site/blog to use this specific value.', 'wpsso' );
							break;
						default:
							$text = apply_filters( $lca.'_messages_tooltip', $text, $idx, $atts );
							break;
					} 	// end of all other settings switch
				}	// end of tooltips
			/*
			 * Misc informational messages
			 */
			} elseif ( strpos( $idx, 'info-' ) === 0 ) {
				switch ( $idx ) {
					case 'info-plugin-tid':
						$um_lca = $this->p->cf['lca'].'um';
						$um_short = $this->p->cf['plugin'][$um_lca]['short'];
						$um_name = $this->p->cf['plugin'][$um_lca]['name'];
						
						$text = '<blockquote class="top-info"><p>'.__( 'After purchasing Pro version license(s), an email is sent to you with a unique Authentication ID and installation / activation instructions.', 'wpsso' ).' '.__( 'Enter the unique Authentication ID on this settings page to check for Pro version updates immediately, and every 24 hours thereafter.', 'wpsso' ).'</p><p><strong>'.sprintf( __( 'The %s extension must be active in order to check for Pro version updates.', 'wpsso' ), $um_name ).'</strong> '.sprintf( __( 'If you accidentally de-activate the %1$s extension, update information will be provided by the WordPress.org plugin repository, and any update notices will be for the Free versions &mdash; always update the Pro version when the %2$s extension is active.', 'wpsso' ), $um_short, $um_short ).' '.__( 'If you accidentally re-install the Free version from WordPress.org &ndash; don\'t worry &ndash; your Authentication ID will always allow you update back to the Pro version.', 'wpsso' ).' ;-)</p></blockquote>';
						break;
					case 'info-plugin-tid-network':
						$um_lca = $this->p->cf['lca'].'um';
						$um_short = $this->p->cf['plugin'][$um_lca]['short'];
						$um_name = $this->p->cf['plugin'][$um_lca]['name'];

						$text = '<blockquote class="top-info"><p>'.__( 'After purchasing Pro version license(s), an email is sent to you with a unique Authentication ID and installation / activation instructions.', 'wpsso' ).' '.__( 'Enter the unique Authentication ID on this page to define a default / forced value for <em>all</em> sites within the network, or enter the Authentication ID(s) individually on each site\'s Pro Licenses settings page.', 'wpsso' ).' <strong>'.sprintf( __( 'Please note that the <em>default</em> site / blog must be licensed and the %1$s extension must be active in order to install %2$s Pro updates from the Network admin interface.', 'wpsso' ), $um_name, $atts['short'] ).'</strong></p></blockquote>';
						break;
					case 'info-review':
						$text = '<blockquote class="top-info"><p>'.sprintf( __( 'If you appreciate the features, quality and support of this plugin, please <a href="%1$s" target="_blank">take a moment to rate the %2$s plugin on WordPress.org</a>.', 'wpsso' ), $url['review'], $atts['short'] ).' '.sprintf( __( 'Your rating will help other WordPress users find higher quality and well supported plugins &mdash; along with <strong>encouraging us to keep improving %s</strong> as well!', 'wpsso' ), $atts['short'] ).' ;-)</p></blockquote>';
						break;
					case 'info-pub-pinterest':
						$text = '<blockquote class="top-info"><p>'.__( 'Pinterest uses the Open Graph standard meta tags for their Rich Pins.', 'wpsso' ).' '.__( 'These options allow you to manage and/or override some Pinterest-specific Open Graph settings.', 'wpsso' ).' '.__( 'Please note that if you use a caching plugin, or front-end caching service, it should detect the Pinterest crawler user-agent and bypass its cache (for example, look for a <em>User-Agent Exclusion Pattern</em> option and add "Pinterest/" to that list).', 'wpsso' ).' '.sprintf( __( 'This will allow %s to provide different / customized meta tags specifically for the Pinterest crawler.', 'wpsso' ), $atts['short'] ).'</p></blockquote>';
						break;
					case 'info-pub-twitter':
						$text = '<blockquote class="top-info"><p><strong>'.__( 'The Photo, Gallery, and Product Cards were deprecated by Twitter on July 3rd, 2015.', 'wpsso' ).'</strong> '.sprintf( __( '%1s continues to support all <a href="%2s">current Twitter Card formats</a>, including the Summary, Summary with Large Image, App (extension plugin required), and Player Cards.', 'wpsso' ), $atts['short'], 'https://dev.twitter.com/cards/types' ).'</p></blockquote>';
						break;
					case 'info-cm':
						$text = '<blockquote class="top-info"><p>'.sprintf( __( 'The following options allow you to customize the contact field names and labels shown on <a href="%s">the user profile page</a>.', 'wpsso' ), get_admin_url( null, 'profile.php' ) ).' '.sprintf( __( '%s uses the Facebook, Google+, and Twitter contact fields for Facebook / Open Graph, Schema, and Twitter Card meta tags.', 'wpsso' ), $atts['short'] ).' <strong>'.sprintf( __( 'You should not modify the <em>%s</em> unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field Name', 'column title', 'wpsso' ) ).'</strong> '.sprintf( __( 'The <em>%s</em> on the other hand is for display purposes only and it can be changed as you wish.', 'wpsso' ), _x( 'Profile Contact Label', 'column title', 'wpsso' ) ).' '.sprintf( __( 'Enabled contact methods are shown on user profile pages automatically, but your theme is responsible for displaying them in other locations throughout your website (see the WordPress <a href="%s" target="_blank">get_the_author_meta()</a> documentation for examples).', 'wpsso' ), 'https://codex.wordpress.org/Function_Reference/get_the_author_meta' ).'</p><p><center><strong>'.__( 'DO NOT ENTER YOUR CONTACT INFORMATION HERE &ndash; THESE ARE CONTACT FIELD LABELS ONLY.', 'wpsso' ).'</strong><br/>'.sprintf( __( 'Enter your personal contact information on <a href="%1$s">the user profile page</a>.', 'wpsso' ), get_admin_url( null, 'profile.php' ) ).'</center></p></blockquote>';
						break;
					case 'info-taglist':
						$text = '<blockquote class="top-info"><p>'.sprintf( __( '%s adds the following Google / SEO, Facebook, Open Graph, Rich Pin, Schema, and Twitter Card HTML tags to the <code>&lt;head&gt;</code> section of your webpages.', 'wpsso' ), $atts['short'] ).' '.__( 'If your theme or another plugin already creates one or more of these HTML tags, you can uncheck them here to prevent duplicates from being added.', 'wpsso' ).' '.__( 'As an example, the "meta name description" HTML tag is automatically unchecked if a <em>known</em> SEO plugin is detected.', 'wpsso' ).' '.__( 'The "meta name canonical" HTML tag is unchecked by default since themes often include this meta tag in their header template(s).', 'wpsso' ).'</p></blockquote>';
						break;
					case 'info-image-dimensions':
						$text = '<blockquote class="top-info"><p>'.sprintf( __( '%s uses several image dimensions, based on their intended use (Facebook / Open Graph, Twitter Cards, Pinterest Rich Pins, etc.).', 'wpsso' ), $atts['short'] ).' '.__( 'Facebook has published a preference for images measuring 1200x630px (to support retina and high-PPI displays), but horizontally cropped images may not show as well on all social sites.', 'wpsso' ).' '.__( 'A good compromise for your Open Graph image dimensions might be 1200x1200px cropped.', 'wpsso' ).' '.__( 'If you use these dimensions, make sure your original images are at least 1200px in <em>both</em> width and height.', 'wpsso' ).'</p></blockquote>';
						break;
					case 'info-social-accounts':
						$text = '<blockquote class="top-info"><p>'.__( 'The website / business social account values are used for SEO, Schema, Open Graph, and other social meta tags &ndash; including publisher (Organization) social markup for Google Search.', 'wpsso' ).' '.sprintf( __( 'See the <a href="%s">Google / Schema settings tab</a> to define a website / business logo for Google Search, and/or enable / disable the addition of publisher (Organization) and/or author (Person) JSON-LD markup in your webpage headers.', 'wpsso' ), $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_google' ) ).'</p></blockquote>';
						break;
					case 'info-sharing-include':
						$text = '<blockquote class="top-info"><p>'.__( 'The buttons enabled bellow (along with those in the widget) can be included or excluded as a group based on the webpage content type.', 'wpsso' ).' '.__( 'This does <em>not</em> apply to the shortcode and/or function buttons, which are managed with their own parameter options.', 'wpsso' ).'</p></blockquote>';
						break;
					default:
						$text = apply_filters( $lca.'_messages_info', $text, $idx, $atts );
						break;
				}	// end of info switch
			/*
			 * Misc pro messages
			 */
			} elseif ( strpos( $idx, 'pro-' ) === 0 ) {
				switch ( $idx ) {
					case 'pro-feature-msg':
						if ( $this->p->check->aop( $lca, false ) )
							$text = '<p class="pro-feature-msg"><a href="'.$url['purchase'].'" target="_blank">'.sprintf( __( 'Purchase %s licence(s) to install / enable Pro modules and modify the following options.', 'wpsso' ), $atts['short_pro'] ).'</a></p>';
						else $text = '<p class="pro-feature-msg"><a href="'.$url['purchase'].'" target="_blank">'.sprintf( __( 'Purchase the %s plugin to install / enable Pro modules and modify the following options.', 'wpsso' ), $atts['short_pro'] ).'</a></p>';
						break;
					case 'pro-option-msg':
						$text = '<p class="pro-option-msg"><a href="'.$url['purchase'].'" target="_blank">'.sprintf( __( '%s required to use this option', 'option comment', 'wpsso' ), $atts['short_pro'] ).'</a></p>';
						break;
					default:
						$text = apply_filters( $lca.'_messages_pro', $text, $idx, $atts );
						break;
				}
			/*
			 * Misc notice messages
			 */
			} elseif ( strpos( $idx, 'notice-' ) === 0 ) {
				switch ( $idx ) {
					case 'notice-missing-og-image':
						$text = __( 'An Open Graph image meta tag could not be created from this webpage content &mdash; Facebook and other social websites <em>require</em> at least one Open Graph image meta tag to render shared content correctly.', 'wpsso' ).' '.__( 'You may select an optional customized image, for Facebook and other social websites, in the Social Settings metabox under the Priority Media tab.', 'wpsso' );
						break;
					case 'notice-object-cache-exp':
						$text = sprintf( __( 'Please note that the <a href="%1$s">%2$s</a> advanced option is currently set at %3$d seconds &mdash; this is lower than the recommended default value of %4$d seconds.', 'wpsso' ), $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_cache' ), _x( 'Object Cache Expiry', 'option label', 'wpsso' ), $this->p->options['plugin_object_cache_exp'], $this->p->opt->get_defaults( 'plugin_object_cache_exp' ) );
						break;
					case 'notice-content-filters-disabled':
						$text = '<p><b>'.sprintf( __( 'The <a href="%1$s">%2$s</a> advanced option is currently disabled.', 'wpsso' ), $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content' ), _x( 'Apply WordPress Content Filters', 'option label', 'wpsso' ) ).'</b> '.sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions, and detect additional images / embedded videos provided by shortcodes.', 'wpsso' ), $atts['short'] ).'</p><p><b>'.__( 'Some theme / plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ).'</b> '.sprintf( __( '<a href="%s">If you use any shortcodes in your content text, this option should be enabled</a> (Pro version required) &mdash; if you experience display issues after enabling this option, determine which theme / plugin content filter is at fault, and report the problem to its author(s).', 'wpsso' ), $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content' ) ).'</p>';
						break;
					case 'notice-header-tmpl-no-head-attr':
						$action_url = wp_nonce_url( $this->p->util->get_admin_url( '?'.$this->p->cf['lca'].
							'-action=modify_tmpl_head_elements' ), WpssoAdmin::get_nonce(), WPSSO_NONCE );
						$text = '<p><b>'.__( 'At least one of your theme header templates does not support Schema markup of the webpage head section.', 'wpsso' ).'</b> '.sprintf( __( 'The %s element in your theme\'s header templates should include a function / action / filter call for its attributes.', 'wpsso' ), '<code>&lt;head&gt;</code>' ).' '.sprintf( __( '%1$s can update your theme header templates automatically to change the default %2$s element to:', 'wpsso' ), $atts['short'], '<code>&lt;head&gt;</code>' ).'</p><pre><code>&lt;head &lt;?php do_action( \'add_head_attributes\' ); ?&gt;&gt;</code></pre><p>'.sprintf( __( '<b><a href="%1$s">Click here to update theme header templates automatically</a></b> or update the theme templates yourself manually.', 'wpsso' ), $action_url ).'</p>';
						break;
					case 'notice-pro-tid-missing':
						if ( ! is_multisite() )
							$text = '<b>'.sprintf( __( 'The %1$s plugin %2$s option is empty.', 'wpsso' ), $atts['name'], _x( 'Pro Authentication ID', 'option label', 'wpsso' ) ).'</b> '.sprintf( __( 'To enable Pro version features and allow the plugin to authenticate itself for updates, please enter the unique Authentication ID you received by email on the <a href="%s">Pro Licenses settings page</a>.', 'wpsso' ), $this->p->util->get_admin_url( 'licenses' ) );
						break;
					case 'notice-pro-not-installed':
						$text = sprintf( __( 'An Authentication ID has been entered for %s, but the Pro version is not yet installed &ndash; don\'t forget to update this plugin to install the latest Pro version.', 'wpsso' ), $atts['name'] );
						break;
					case 'notice-um-extension-required':
					case 'notice-um-activate-extension':
						$um_lca = $lca.'um';
						$um_name = $this->p->cf['plugin'][$um_lca]['name'];
						$um_dl = $this->p->cf['plugin'][$um_lca]['url']['download'];
						$um_latest = $this->p->cf['plugin'][$um_lca]['url']['latest_zip'];
						$upload_url = get_admin_url( null, 'plugin-install.php?tab=upload' );

						$text = '<p>'.sprintf( __( 'At least one Authentication ID has been entered on the <a href="%1$s">Pro Licenses settings page</a>, but the <b>%2$s</b> plugin is not active.', 'wpsso' ), $this->p->util->get_admin_url( 'licenses' ), $um_name ).' ';

						if ( $idx === 'notice-um-extension-required' ) {
							$text .= sprintf( __( 'This <b>Free extension</b> is required to update and enable the %s plugin and its Pro extensions.', 'wpsso' ), $atts['name_pro'] ).'</p><ol><li><b>'.sprintf( __( 'Download the Free <a href="%1$s">%2$s plugin archive</a> (ZIP).', 'wpsso' ), $um_latest, $um_name ).'</b></li><li><b>'.sprintf( __( 'Then <a href="%s">upload and activate the plugin</a> on the WordPress plugin upload page.', 'wpsso' ), $upload_url ).'</b></li></ol>';
						} else $text .= '</p>';

						$text .= '<p>'.sprintf( __( 'Once the %s extension has been activated, one or more Pro version updates may be available for your licensed plugin(s).', 'wpsso' ), $um_name ).'</p><p>'.sprintf( __( 'Read more <a href="%1$s" target="_blank">about the %2$s extension plugin</a>.', 'wpsso' ), $um_dl, $um_name ).'</p>';
						break;
					default:
						$text = apply_filters( $lca.'_messages_notice', $text, $idx, $atts );
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
							$text .= sprintf( __( '%s can be purchased quickly and easily via Paypal &ndash; allowing you to license and enable Pro version features within seconds of your purchase.', 'wpsso' ), $atts['short_pro'] );
						else $text .= sprintf( __( '%s can be purchased quickly and easily via Paypal &ndash; allowing you to update the plugin within seconds of your purchase.', 'wpsso' ), $atts['short_pro'] );
						$text .= ' '.__( 'Pro version licenses do not expire &ndash; there are no yearly or recurring fees for updates and support.', 'wpsso' );
						$text .= '<p>';
						break;
					case 'side-help':
						$submit_text = _x( 'Save All Plugin Settings', 'submit button', 'wpsso' );
						$text = '<p>'.sprintf( __( 'Metaboxes (like this one) can be opened / closed by clicking on their title bar, moved and re-ordered by dragging them, or removed / added from the <em>Screen Options</em> tab (top-right of page).', 'wpsso' ).' '.__( 'Option values in multiple tabs can be modified before clicking the \'%s\' button.', 'wpsso' ), $submit_text ).'</p>';
						break;
					default:
						$text = apply_filters( $lca.'_messages_side', $text, $idx, $atts );
						break;
				}
			} else $text = apply_filters( $lca.'_messages', $text, $idx, $atts );

			if ( is_array( $atts ) && 
				! empty( $atts['is_locale'] ) )
					$text .= ' '.sprintf( __( 'This option is localized &mdash; you may change the WordPress admin locale with <a href="%1$s" target="_blank">Polylang</a>, <a href="%2$s" target="_blank">WP Native Dashboard</a>, etc., to define alternate option values for different languages.', 'wpsso' ), 'https://wordpress.org/plugins/polylang/', 'https://wordpress.org/plugins/wp-native-dashboard/' );

			if ( strpos( $idx, 'tooltip-' ) === 0 && ! empty( $text ) )
				return '<img src="'.WPSSO_URLPATH.'images/question-mark.png" width="14" height="14" class="'.
					( isset( $atts['class'] ) ? $atts['class'] : $this->p->cf['form']['tooltip_class'] ).
						'" alt="'.esc_attr( $text ).'" />';
			else return $text;
		}
	}
}

?>
