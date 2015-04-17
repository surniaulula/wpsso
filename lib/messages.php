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
			$this->p->debug->mark();
		}

		public function get( $idx = false, $atts = null, $class = '' ) {
			$text = is_array( $atts ) || is_object( $atts ) ? '' : $atts;
			$idx = sanitize_title_with_dashes( $idx );
			$lca = isset( $atts['lca'] ) ?
				$atts['lca'] : $this->p->cf['lca'];
			$url = $this->p->cf['plugin'][$lca]['url'];

			$short = isset( $atts['short'] ) ?
				$atts['short'] :
				$this->p->cf['plugin'][$lca]['short'];
			$short_pro = $short.' Pro';

			$name = isset( $atts['name'] ) ?
				$atts['name'] :
				$this->p->cf['plugin'][$lca]['name'];
			$name_pro = $name.' Pro';

			if ( strpos( $idx, 'tooltip-' ) !== false && empty( $class ) )
				$class = $this->p->cf['form']['tooltip_class'];	// default tooltip class

			switch ( $idx ) {
				/*
				 * 'Plugin Features' side metabox
				 */
				case ( strpos( $idx, 'tooltip-side-' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'tooltip-side-author-json-ld':
							$text = 'Add author (Person) social profiles markup to webpage headers in schema.org JSON-LD format for Google Search.';
							break;
						case 'tooltip-side-debug-messages':
							$text = 'Debug code is loaded when the \'Add Hidden Debug HTML Messages\' option is checked, or one of the available <a href="http://surniaulula.com/codex/plugins/wpsso/notes/constants/" target="_blank">debugging constants</a> is defined.';
							break;
						case 'tooltip-side-non-persistant-cache':
							$text = $short.' saves filtered / rendered content to a non-persistant cache (aka <a href="https://codex.wordpress.org/Class_Reference/WP_Object_Cache" target="_blank">WP Object Cache</a>) for re-use within the same page load. You can disable the use of non-persistant cache (not recommended) using one of the available <a href="http://surniaulula.com/codex/plugins/wpsso/notes/constants/" target="_blank">constants</a>.';
							break;
						case 'tooltip-side-open-graph-rich-pin':
							$text = 'Facebook / Open Graph and Pinterest Rich Pin meta tags are added to the head section of all webpages. You must have a compatible eCommerce plugin installed to add <em>Product</em> Rich Pins, including product prices, images, and other attributes.';
							break;
						case 'tooltip-side-transient-cache':
							$text = $short.' saves Facebook / Open Graph, Pinterest Rich Pin, Twitter Card meta tags, etc. to a persistant (aka <a href="https://codex.wordpress.org/Transients_API" target="_blank">Transient</a>) cache for '.$this->p->options['plugin_object_cache_exp'].' seconds (default is '.$this->p->opt->get_defaults( 'plugin_object_cache_exp' ).' seconds). You can adjust the Transient / Object Cache expiration value in the '.$this->p->util->get_admin_url( 'advanced', 'Advanced settings' ).', or disable it completely using an available <a href="http://surniaulula.com/codex/plugins/wpsso/notes/constants/" target="_blank">constant</a>.';
							break;
						case 'tooltip-side-publisher-json-ld':
							$text = 'Add publisher (Organization) social profiles markup to webpage headers in schema.org JSON-LD format for Google Search.';
							break;
						case 'tooltip-side-post-social-settings':
							$text = 'The Post Social Settings feature adds a Social Settings metabox to the Post, Page, and custom post type editing pages.  Custom descriptions and images can be entered for Facebook / Open Graph, Pinterest Rich Pin, and Twitter Card meta tags.';
							break;
						case 'tooltip-side-user-social-settings':
							$text = 'The User Social Settings feature adds a Social Settings metabox to the user profile pages. Custom descriptions and images can be entered for Facebook / Open Graph, Pinterest Rich Pin, and Twitter Card meta tags.';
							break;
						case 'tooltip-side-publisher-language':
							$text = $short_pro.' can use the WordPress locale to select the correct language for the Facebook / Open Graph and Pinterest Rich Pin meta tags'.( empty( $this->p->is_avail['ssb'] ) ? '' : ', along with the Google, Facebook, and Twitter social sharing buttons' ).'. If your website is available in multiple languages, this can be a useful feature.';
							break;
						case 'tooltip-side-twitter-cards':
							$text = 'Twitter Cards extend the standard Facebook / Open Graph and Pinterest Rich Pin meta tags with content-specific information for image galleries, photographs, eCommerce products, etc. Twitter Cards are displayed differently on Twitter, either online or from mobile Twitter clients, allowing you to highlight your content. The Twitter Cards meta tags can be enabled from the '.$this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_twitter', 'General settings' ).' page.';
							break;
						case 'tooltip-side-author-gravatar':
							$text = 'Include the author\'s Gravatar image in meta tags for author index / archive webpages. Enable or disable this option from the '.$this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_author', 'General settings page' ).'.';
							break;
						case 'tooltip-side-slideshare-api':
							$text = 'If the embedded Slideshare Presentations option on the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content', 'Advanced settings' ).' page is checked, '.$short_pro.' will load an integration module for Slideshare, to detect embedded Slideshare presentations and retrieve slide information using Slideshare\'s oEmbed API (media dimentions, preview image, etc).';
							break;
						case 'tooltip-side-vimeo-video-api':
							$text = 'If the embedded Vimeo Videos option in the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content', 'Advanced settings' ).' page is checked, '.$short_pro.' will load an integration module for Vimeo, to detect embedded Vimeo videos and retrieve video information using Vimeo\'s oEmbed API (media dimentions, preview image, etc).';
							break;
						case 'tooltip-side-wistia-video-api':
							$text = 'If the embedded Wistia Videos option in the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content', 'Advanced settings' ).' page is checked, '.$short_pro.' will load an integration module for Wistia to detect embedded Wistia videos, and retrieve video information using Wistia\'s oEmbed API (media dimentions, preview image, etc).';
							break;
						case 'tooltip-side-youtube-video-playlist-api':
							$text = 'If the embedded Youtube Videos and Playlists option in the '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content', 'Advanced settings' ).' page is checked, '.$short_pro.' will load an integration module for YouTube to detect embedded YouTube videos and playlists, and retrieve video information using Youtube\'s XML and oEmbed APIs (media dimentions, preview image, etc).';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_side', $text, $idx );
							break;
					}
					break;

				/*
				 * User Meta settings
				 */
				case ( strpos( $idx, 'tooltip-user-' ) !== false ? true : false ):
					$ptn = empty( $atts['ptn'] ) ? 'Post' : $atts['ptn'];
					switch ( $idx ) {
						 case 'tooltip-user-og_title':
							$text = 'A custom title for the Facebook / Open Graph, Pinterest Rich Pin, Twitter Card meta tags (all Twitter Card formats), and possibly the Pinterest, Tumblr, and Twitter sharing captions / texts, depending on some option settings.';
						 	break;
						 case 'tooltip-user-og_desc':
							$text = 'A custom description for the Facebook / Open Graph, Pinterest Rich Pin, and fallback description for other meta tags'.( empty( $this->p->is_avail['ssb'] ) ? '' : ', along with some social sharing buttons' ).'. '.'The default description value is based on the biographical info, if one is available. Update and save this description to change the default value of all other description fields.';
						 	break;
						 case 'tooltip-user-seo_desc':
							$text = 'A custom description for the Google Search / SEO description meta tag.';
						 	break;
						 case 'tooltip-user-schema_desc':
							$text = 'A custom description for the Google+ schema description meta tag.';
						 	break;
						 case 'tooltip-user-tc_desc':
							$text = 'A custom description for the Twitter Card description meta tag (all Twitter Card formats).';
						 	break;
						default:
							$text = apply_filters( $lca.'_tooltip_user', $text, $idx, $atts );
							break;
					}
					break;

				/*
				 * Post Meta settings
				 */
				case ( strpos( $idx, 'tooltip-postmeta-' ) !== false ? true : false ):
					$ptn = empty( $atts['ptn'] ) ? 'Post' : $atts['ptn'];
					switch ( $idx ) {
						 case 'tooltip-postmeta-social-preview':
						 	$text = 'The Open Graph social preview shows an <em>example</em> of a typical share on a social website. Images are displayed using Facebooks suggested minimum image dimensions of 600x315px. Actual shares on Facebook and other social networks may look significantly different than this <em>example</em> (depending on the viewing platform resolution, orientation, etc.).';
						 	break;
						 case 'tooltip-postmeta-og_art_section':
							$text = 'A custom topic, different from the default Article Topic selected in the General settings. The Facebook / Open Graph \'og:type\' meta tag must be an \'article\' to enable this option. The value will be used in the \'article:section\' Facebook / Open Graph and Pinterest Rich Pin meta tags. Select \'[none]\' if you prefer to exclude the \'article:section\' meta tag.';
						 	break;
						 case 'tooltip-postmeta-og_title':
							$text = 'A custom title for the Facebook / Open Graph, Pinterest Rich Pin, Twitter Card meta tags (all Twitter Card formats), and possibly the Pinterest, Tumblr, and Twitter sharing caption / text, depending on some option settings. The default title value is refreshed when the (draft or published) '.$ptn.' is saved.';
						 	break;
						 case 'tooltip-postmeta-og_desc':
							$text = 'A custom description for the Facebook / Open Graph, Pinterest Rich Pin, and fallback description for other meta tags'.( empty( $this->p->is_avail['ssb'] ) ? '' : ', along with some social sharing buttons' ).'. The default description value is based on the content, or excerpt if one is available, and is refreshed when the (draft or published) '.$ptn.' is saved. Update and save this description to change the default value of all other description fields.';
						 	break;
						 case 'tooltip-postmeta-seo_desc':
							$text = 'A custom description for the Google Search / SEO description meta tag. The default description value is refreshed when the '.$ptn.' is saved.';
						 	break;
						 case 'tooltip-postmeta-schema_desc':
							$text = 'A custom description for the Google+ / Schema description meta tag. The default description value is refreshed when the '.$ptn.' is saved.';
						 	break;
						 case 'tooltip-postmeta-tc_desc':
							$text = 'A custom description for the Twitter Card description meta tag (all Twitter Card formats). The default description value is refreshed when the '.$ptn.' is saved.';
						 	break;
						 case 'tooltip-postmeta-og_img_id':
							$text = 'A custom Image ID to include first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Large Image Summary\' Twitter Card meta tags, '.( empty( $this->p->is_avail['ssb'] ) ? '' : 'along with the Pinterest and Tumblr social sharing buttons, ' ).'before any featured, attached, or content images.';
						 	break;
						 case 'tooltip-postmeta-og_img_url':
							$text = 'A custom image URL (instead of an Image ID) to include first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Large Image Summary\' Twitter Card meta tags. Please make sure your custom image is large enough, or it may be ignored by the social website(s). Facebook recommends an image size of 1200x630, 600x315 as a minimum, and will ignore any images less than 200x200 (1200x1200 is recommended). <em>This field is disabled if an Image ID has been specified</em>.';
						 	break;
						 case 'tooltip-postmeta-og_img_max':
							$text = 'The maximum number of images to include in the Facebook / Open Graph meta tags for this '.$ptn.'.';
						 	break;
						 case 'tooltip-postmeta-og_vid_url':
							$text = 'A custom Video URL to include first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Player\' Twitter Card meta tags. If the URL is from Youtube, Vimeo or Wistia, an API connection will be made to retrieve the preferred sharing URL, video dimensions, and video preview image. The '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_social', 'Video URL Custom Field' ).' Advanced option allows a 3rd-party theme or plugin to provide a custom Video URL value for this option.';
						 	break;
						 case 'tooltip-postmeta-og_vid_embed':
							$text = 'Custom Video Embed HTML to use for the first in the Facebook / Open Graph, Pinterest Rich Pin, and \'Player\' Twitter Card meta tags. If the URL is from Youtube, Vimeo or Wistia, an API connection will be made to retrieve the preferred sharing URL, video dimensions, and video preview image. The '.$this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_social', 'Video Embed HTML Custom Field' ).' Advanced option also allows a 3rd-party theme or plugin to provide custom Video Embed HTML for this option.';
						 	break;
						 case 'tooltip-postmeta-og_vid_max':
							$text = 'The maximum number of embedded videos to include in the Facebook / Open Graph meta tags for this '.$ptn.'.';
						 	break;
						 case 'tooltip-postmeta-sharing_url':
							$text = 'A custom sharing URL used in the Facebook / Open Graph, Pinterest Rich Pin meta tags and social sharing buttons. The default sharing URL may be influenced by settings from supported SEO plugins. Please make sure any custom URL you enter here is functional and redirects correctly.';
						 	break;
						 case 'tooltip-postmeta-rp_img_id':
							$text = 'A custom Image ID to include first when the Pinterest crawler is detected.';
						 	break;
						 case 'tooltip-postmeta-rp_img_url':
							$text = 'A custom image URL (instead of an Image ID) to include first when the Pinterest crawler is detected. <em>This field is disabled if an Image ID has been specified</em>.';
						 	break;
						default:
							$text = apply_filters( $lca.'_tooltip_postmeta', $text, $idx, $atts );
							break;
					}
					break;

				/*
				 * Open Graph settings
				 */
				case ( strpos( $idx, 'tooltip-og_' ) !== false ? true : false ):
					switch ( $idx ) {
						/*
						 * 'Priority Media' settings
						 */
						case 'tooltip-og_img_dimensions':
							$text = 'The image dimensions used in the Facebook / Open Graph meta tags (defaults is '.$this->p->opt->get_defaults( 'og_img_width' ).'x'.$this->p->opt->get_defaults( 'og_img_height' ).' '.( $this->p->opt->get_defaults( 'og_img_crop' ) == 0 ? 'un' : '' ).'cropped). Facebook recommends 1200x630 cropped, and 600x315 as a minimum. <strong>1200x1200 cropped provides the greatest compatibility with all social websites (Facebook, Google+, LinkedIn, etc.)</strong>. Note that original images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.';
							break;
						case 'tooltip-og_def_img_id':
							$text = 'The ID number and media location of your default image (example: 123). The Default Image ID will be used as a <strong>fallback for Posts and Pages that do not have any images</strong> <em>featured</em>, <em>attached</em>, or suitable &lt;img/&gt; HTML tags in their content. The ID number for images in the WordPress Media Library can be found in the URL when editing an image (post=123 in the URL, for example). The NextGEN Gallery image IDs are easier to find -- it\'s the number in the first column when viewing a Gallery.';
							break;
						case 'tooltip-og_def_img_url':
							$text = 'You can enter a Default Image URL (including the http:// prefix) instead of choosing a Default Image ID (if a Default Image ID is specified, the Default Image URL option is disabled). The Default Image URL option allows you to <strong>use an image outside of a managed collection (WordPress Media Library or NextGEN Gallery), and/or a smaller logo style image</strong>. The image should be at least '.$this->p->cf['head']['min_img_dim'].'x'.$this->p->cf['head']['min_img_dim'].' or more in width and height. The Default Image ID or URL is used as a <strong>fallback for Posts and Pages that do not have any images</strong> <em>featured</em>, <em>attached</em>, or suitable &lt;img/&gt; HTML tags in their content.';
							break;
						case 'tooltip-og_def_img_on_index':
							$text = 'Check this option to force the default image on index webpages (<strong>non-static</strong> homepage, archives, categories). If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$short.' will use image(s) from the first entry on the webpage (default is checked).';
							break;
						case 'tooltip-og_def_img_on_author':
							$text = 'Check this option to force the default image on author index webpages. If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong> (default is unchecked).';
							break;
						case 'tooltip-og_def_img_on_search':
							$text = 'Check this option to force the default image on search results. If this option is <em>checked</em>, but a Default Image ID or URL has not been defined, then <strong>no image will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$short.' will use image(s) returned in the search results (default is unchecked).';
							break;
						case 'tooltip-og_def_vid_url':
							$text = 'The Default Video URL is used as a <strong>fallback value for Posts and Pages that do not have any videos</strong> in their content. Do not specify a Default Video URL <strong>unless you want to include video information in all your Posts and Pages</strong>.';
							break;
						case 'tooltip-og_def_vid_on_index':
							$text = 'Check this option to force the default video on index webpages (<strong>non-static</strong> homepage, archives, categories). If this option is <em>checked</em>, but a Default Video URL has not been defined, then <strong>no video will be included in the meta tags</strong> (this is usually preferred). If the option is <em>unchecked</em>, then '.$short.' will use video(s) from the first entry on the webpage (default is checked).';
							break;
						case 'tooltip-og_def_vid_on_author':
							$text = 'Check this option to force the default video on author index webpages. If this option is <em>checked</em>, but a Default Video URL has not been defined, then <strong>no video will be included in the meta tags</strong> (default is unchecked).';
							break;
						case 'tooltip-og_def_vid_on_search':
							$text = 'Check this option to force the default video on search results. If this option is <em>checked</em>, but a Default Video URL has not been defined, then <strong>no video will be included in the meta tags</strong>. If the option is <em>unchecked</em>, then '.$short.' will use video(s) returned in the search results (default is unchecked).';
							break;
						case 'tooltip-og_ngg_tags':
							$text = 'If the <em>featured</em> image in a Post or Page is from a NextGEN Gallery, then add that image\'s tags to the Facebook / Open Graph and Pinterest Rich Pin tag list (default is unchecked).';
							break;
						case 'tooltip-og_img_max':
							$text = 'The maximum number of images to list in the Facebook / Open Graph and Pinterest Rich Pin meta tags -- this includes the <em>featured</em> or <em>attached</em> images, and any images found in the Post or Page content. If you select \'0\', then no images will be listed in the facebook / Open Graph and Pinterest Rich Pin meta tags (<strong>not recommended</strong>). If no images are listed in your meta tags, then social websites may choose an unsuitable image from your webpage (including headers, sidebars, etc.).';
							break;
						case 'tooltip-og_vid_max':
							$text = 'The maximum number of videos, found in the Post or Page content, to include in the Facebook / Open Graph and Pinterest Rich Pin meta tags. If you select \'0\', then no videos will be listed in the facebook / Open Graph and Pinterest Rich Pin meta tags.';
							break;
						case 'tooltip-og_vid_prev_img':
							$text = 'Include video preview images in the meta tags (default is checked).';
							break;
						case 'tooltip-og_vid_https':
							$text = 'Use an HTTPS connection whenever possible to retrieve information about videos from YouTube, Vimeo, Wistia, etc. (default is checked).';
							break;
						/*
						 * 'Title / Description' settings
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
						case 'tooltip-og_desc_strip':
							$text = 'If a Page or Post does <em>not</em> have an excerpt, the plugin will ignore all text until the first html paragraph tag in the content. If an excerpt exists, then this option is ignored and the complete text of the excerpt is used.';
							break;
						case 'tooltip-og_desc_alt':
							$text = 'If the content is empty or comprised entirely of HTML tags &mdash; which must be stripped to create a description &mdash; '.$short.' can extract and use the text from the image <em>alt=""</em> attributes instead of returning an empty description.';
							break;
						/*
						 * 'Authorship' settings
						 */
						case 'tooltip-og_author_field':
							$text = 'Select which field to use from the author\'s profile for the Facebook / Open Graph and Pinterest Rich Pin \'article:author\' meta tag(s). The preferred (and default) setting is the Facebook URL field.';
							break;
						case 'tooltip-og_author_fallback':
							$text = 'If the Author Profile URL (and the Author Link URL in the Google Settings below) is not a valid URL, then '.$short.' can fallback to using the author index on this website (\''.trailingslashit( site_url() ).'author/username\' for example). Uncheck this option to disable the fallback feature (default is unchecked).';
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
							$text = 'Check this option to include the author\'s Gravatar image in meta tags for author index / archive webpages. If the "Force Default Image on <em>Author</em> Index" option is also checked under the \'Images\' tab (unchecked by default), then the default image will be used instead for author index / archive webpages.';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_og', $text, $idx );
							break;
					}
					break;

				/*
				 * Advanced plugin settings
				 */
				case ( strpos( $idx, 'tooltip-plugin_' ) !== false ? true : false ):
					switch ( $idx ) {
						/*
						 * 'Plugin Settings' settings
						 */
						case 'tooltip-plugin_show_opts':
							$text = 'Select the default number of options to display on the '.$short.' settings pages. The basic view shows only the essential options that are most commonly used.';
							break;
						case 'tooltip-plugin_preserve':
							$text = 'Check this option if you would like to preserve all '.$short.' settings when you <em>uninstall</em> the plugin (default is unchecked).';
							break;
						case 'tooltip-plugin_debug':
							$text = 'Add hidden debug messages to the HTML of webpages (default is unchecked).';
							break;
						case 'tooltip-plugin_cache_info':
							$text = 'Report the number of objects removed from the cache when updating Posts and Pages.';
							break;
						case 'tooltip-plugin_check_head':
							$text = $short.' can check the front-end webpage head section for duplicate HTML tags when editing Posts and Pages. You may uncheck this option if you\'ve edited a few Posts and Pages without seeing any warning messages about duplicate HTML tags.';
							break;
						case 'tooltip-plugin_filter_lang':
							$text = $short_pro.' can use the WordPress locale to select the correct language for the Facebook / Open Graph and Pinterest Rich Pin meta tags'.( empty( $this->p->is_avail['ssb'] ) ? '' : ', along with the Google, Facebook, and Twitter social sharing buttons' ).'. If your website is available in multiple languages, this can be a useful feature. Uncheck this option to ignore the WordPress locale and always use the configured language.'; 
							break;
						case 'tooltip-plugin_auto_img_resize':
							$text = 'Automatically generate missing or incorrect image sizes for previously uploaded images in the WordPress Media Library (default is checked).';
							break;
						case 'tooltip-plugin_shortcodes':
							$text = 'Enable the '.$short.' shortcode features (default is checked).';
							break;
						case 'tooltip-plugin_widgets':
							$text = 'Enable the '.$short.' widget features (default is checked).';
							break;
						/*
						 * 'Content and Filters' settings
						 */
						case 'tooltip-plugin_filter_title':
							$text = 'By default, '.$short.' uses the title values provided by WordPress, which may include modifications by themes and/or SEO plugins (appending the blog name to all titles, for example, is fairly common practice). If you wish to use the original title value without these modifications, uncheck this option.';
							break;
						case 'tooltip-plugin_filter_excerpt':
							$text = 'Apply the standard WordPress \'get_the_excerpt\' filter to render the excerpt text (default is unchecked). Check this option if you use shortcodes in your excerpt, for example.';
							break;
						case 'tooltip-plugin_filter_content':
							$text = 'Apply the standard WordPress \'the_content\' filter to render the content text (default is checked). This renders all shortcodes, and allows '.$short.' to detect images and embedded videos that may be provided by these.';
							break;
						case 'tooltip-plugin_ignore_small_img':
							$text = $short.' will retrieve image URLs from HTML tags in the <strong>content</strong>. The &amp;amp;lt;img/&amp;amp;gt; HTML tags must have a width and height attribute, and their size must be equal to (or larger) than the Image Dimensions you\'ve entered on the General settings page. Uncheck this option to include smaller images from the content. <strong>Unchecking this option is not advised</strong> - images that are too small for some social websites may be included in your meta tags.';
							break;
						case 'tooltip-plugin_page_excerpt':
							$text = 'Enable the excerpt editing metabox for Pages. Excerpts are optional hand-crafted summaries of your content that '.$short.' can use as a default description value.';
							break;
						case 'tooltip-plugin_page_tags':
							$text = 'Enable the tags editing metabox for Pages. Tags are optional keywords that highlight the content subject(s), often used for searches and "tag clouds". '.$short.' converts tags into hashtags for some social websites (Twitter, Facebook, Google+, etc.).';
							break;
						case 'tooltip-plugin_embedded_media':
							$text = 'Check the Post and Page content, along with the custom Social Settings, for embedded media URLs from supported media providers (Youtube, Wistia, etc.). If a supported URL is found, an API connection to the provider will be made to retrieve information about the media (preview image, flash player url, oembed player url, video width / height, etc.).';
							break;
						/*
						 * 'Social Settings' settings
						 */
						case 'tooltip-plugin_add_to':
							$text = 'The Social Settings metabox, which allows you to enter custom Facebook / Open Graph values (among other options), is available on the User, Posts, Pages, Media, and Product admin pages by default. If your theme (or another plugin) supports additional custom post types, and you would like to include the Social Settings metabox on their admin pages, check the appropriate option(s) here.';
							break;
						case 'tooltip-plugin_cf_img_url':
							$text = 'If your theme or another plugin provides a custom field for image URLs, you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the Image URL option in the Social Settings metabox. The default value is "'.$this->p->opt->get_defaults( 'plugin_cf_img_url' ).'".';
							break;
						case 'tooltip-plugin_cf_vid_url':
							$text = 'If your theme or another plugin provides a custom field for video URLs (not embed HTML code), you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the Video URL option in the Social Settings metabox. The default value is "'.$this->p->opt->get_defaults( 'plugin_cf_vid_url' ).'".';
							break;
						case 'tooltip-plugin_cf_vid_embed':
							$text = 'If your theme or another plugin provides a custom field for video embed HTML code (not simply a URL), you may enter its custom field name here. If a custom field matching that name is found, its value will be used for the Video Embed HTML option in the Social Settings metabox. The default value is "'.$this->p->opt->get_defaults( 'plugin_cf_vid_embed' ).'".';
							break;
						/*
						 * 'File and Object Cache' settings
						 */
						case 'tooltip-plugin_object_cache_exp':
							$text = $short.' saves filtered and rendered content to a non-persistant cache (aka <a href="https://codex.wordpress.org/Class_Reference/WP_Object_Cache" target="_blank">WP Object Cache</a>), and the meta tag HTMLs to a persistant (aka <a href="https://codex.wordpress.org/Transients_API" target="_blank">Transient</a>) cache. The default is '.$this->p->opt->get_defaults( 'plugin_object_cache_exp' ).' seconds ('.( $this->p->opt->get_defaults( 'plugin_object_cache_exp' ) / 60 / 60 ).' hrs), and the minimum value is 1 second (values bellow 3600 seconds are not recommended).<br/><br/>If you have database performance issues, or don’t use an object / transient cache (like APC, XCache, memcache, etc.), you may want to disable the transient caching feature completely by setting the WPSSO_TRANSIENT_CACHE_DISABLE constant to true.';
							break;
						case 'tooltip-plugin_file_cache_hrs':
							$text = $short_pro.' can save social sharing JavaScript and images to a cache folder, providing URLs to these cached files instead of the originals. A value of 0 hours (the default) disables the file caching feature. If your hosting infrastructure performs reasonably well, this option can improve page load times significantly. All social sharing images and javascripts will be cached, except for the Facebook JavaScript SDK, which does not work correctly when cached.';
							break;
						case 'tooltip-plugin_verify_certs':
							$text = 'Enable verification of peer SSL certificates when fetching content to be cached using HTTPS. The PHP \'curl\' function will use the '.WPSSO_CURL_CAINFO.' certificate file by default. You can define a WPSSO_CURL_CAINFO constant in your wp-config.php file to use an alternate certificate file.';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_plugin', $text, $idx );
							break;
					}
					break;

				/*
				 * Publisher 'Facebook' settings
				 */
				case ( strpos( $idx, 'tooltip-fb_' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'tooltip-fb_publisher_url':
							$text = 'If you have a <a href="https://www.facebook.com/business" target="_blank">Facebook Business Page for your website / business</a>, you may enter its URL here. For example, the Facebook Business Page URL for Surnia Ulula is <a href="https://www.facebook.com/SurniaUlulaCom" target="_blank">https://www.facebook.com/SurniaUlulaCom</a>. The Facebook Business Page URL will be used in Open Graph <em>article</em> type webpages (not index / archive webpages) and schema publisher (Organization) social JSON. Google Search may use this information to display additional publisher / business details in its search results.';
							break;
						case 'tooltip-fb_admins':
							$text = 'The Facebook Admin Username(s) are used by Facebook to allow access to <a href="https://developers.facebook.com/docs/insights/" target="_blank">Facebook Insight</a> data for your website. Note that these are <strong>user account names, and not Facebook age names</strong>. Enter one or more Facebook user names, separated with commas. When viewing your own Facebook wall, your user name is located in the URL (example: https://www.facebook.com/<strong>user_name</strong>). Enter only the user name(s), not the URL(s). <a href="https://www.facebook.com/settings?tab=account&section=username&view" target="_blank">You may update your Facebook user name in the Facebook General Account Settings</a>.';
							break;
						case 'tooltip-fb_app_id':
							$text = 'If you have a <a href="https://developers.facebook.com/apps" target="_blank">Facebook Application ID for your website</a>, enter it here. The Facebook Application ID will appear in your webpage meta tags, and is used by Facebook to allow access to <a href="https://developers.facebook.com/docs/insights/" target="_blank">Facebook Insight</a> data for accounts associated with that Application ID.';
							break;
						case 'tooltip-fb_lang':
							$text = 'The default language of your website content, used in the Facebook / Open Graph and Pinterest Rich Pin meta tags. The Pro version can also use the WordPress locale to adjust the language value dynamically (useful for websites with multilingual content).';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_fb', $text, $idx );
							break;
					}
					break;

				/*
				 * Publisher 'Google' settings
				 */
				case ( strpos( $idx, 'tooltip-google_' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'tooltip-google_publisher_url':
							$text = 'If you have a <a href="http://www.google.com/+/business/" target="_blank">Google+ Business Page for your website / business</a>, you may enter its URL here. For example, the Google+ Business Page URL for Surnia Ulula is <a href="https://plus.google.com/+SurniaUlula/" target="_blank">https://plus.google.com/+SurniaUlula/</a>. The Google+ Business Page URL will be used in a link relation header tag, and the schema publisher (Organization) social JSON. Google Search may use this information to display additional publisher / business details in its search results.';
							break;
						case 'tooltip-google_seo_desc_len':
							$text = 'The maximum length of text used for the Google Search / SEO description meta tag. The length should be at least '.$this->p->cf['head']['min_desc_len'].' characters or more (the default is '.$this->p->opt->get_defaults( 'seo_desc_len' ).' characters).';
							break;
						case 'tooltip-google_schema_desc_len':
							$text = 'The maximum length of text used for the Google+ / Schema description meta tag. The length should be at least '.$this->p->cf['head']['min_desc_len'].' characters or more (the default is '.$this->p->opt->get_defaults( 'schema_desc_len' ).' characters).';
							break;
						case 'tooltip-google_schema_logo_url':
							$text = 'The URL to an image that Google should use as your organization\'s logo in search results and their <em>Knowledge Graph</em>.';
							break;
						case 'tooltip-google_author_name':
							$text = 'Select an Author Name Format for the "author" meta tag, or \'none\' to disable this feature (the recommended value is \'Display Name\'). Facebook uses the "author" meta tag value to credit the author on timeline shares, but the <strong>Facebook Debugger will show a warning</strong> &mdash; thus it is disabled by default. Now that you know about the false warning from the Facebook Debugger, you should set this option to \'Display Name\'. ;-)';
							break;
						case 'tooltip-google_author_field':
							$text = $short.' can include an <em>author</em> and <em>publisher</em> link in your webpage headers. These are not Facebook / Open Graph and Pinterest Rich Pin meta property tags &mdash; they are used primarily by Google\'s search engine to associate Google+ profiles with search results. Select which field to use from the author\'s profile for the <em>author</em> link tag.';
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
						case 'tooltip-google_schema_author_json':
							$text = 'Add author (Person) social profiles markup to webpage headers in schema.org JSON-LD format for Google Search. The author must have entered a valid URL in the Website field on their user profile page. All URLs within the various contact method fields will be listed in the social profile markup. The "Twitter @username" field will be used to include a URL for their Twitter profile.';
							break;
						case 'tooltip-google_schema_publisher_json':
							$text = 'Add publisher (Organization) social profiles markup to webpage headers in schema.org JSON-LD format for Google Search. The Open Graph "Article Publisher Page URL" and "Publisher Link URL" will be listed in the social profile markup. The Open Graph Default Image ID / URL will be used as the Organization image.';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_google', $text, $idx );
							break;
					}
					break;

				/*
				 * Publisher 'Twitter Card' settings
				 */
				case ( strpos( $idx, 'tooltip-tc_' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'tooltip-tc_site':
							$text = 'The <a href="https://business.twitter.com/" target="_blank">Twitter @username for your website and/or business</a> (not your personal Twitter @username). As an example, the Twitter @username for Surnia Ulula is <a href="https://twitter.com/surniaululacom" target="_blank">@surniaululacom</a>. The website / business @username is also used for the schema publisher (Organization) social JSON. Google Search may use this information to display additional publisher / business details in its search results.';
							break;
						case 'tooltip-tc_enable':
							$text = 'Add Twitter Card meta tags to all webpage headers. <strong>Your website must be "authorized" by Twitter for each type of Twitter Card you support</strong>. See the FAQ entry titled <a href="http://surniaulula.com/codex/plugins/wpsso/faq/why-dont-my-twitter-cards-show-on-twitter/" target="_blank">Why don’t my Twitter Cards show on Twitter?</a> for more information on Twitter\'s authorization process.';
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
						case 'tooltip-tc_photo_dimensions':
							$card = 'photo';
							$text = 'The dimension of ImageBrowser or Attachment Page images provided for the <a href="https://dev.twitter.com/docs/cards/types/photo-card" target="_blank">Photo Card</a> (should be at least 560x750 and less than 1MB). The default image dimensions are '.$this->p->opt->get_defaults( 'tc_'.$card.'_width' ).'x'.$this->p->opt->get_defaults( 'tc_'.$card.'_height' ).', '.( $this->p->opt->get_defaults( 'tc_'.$card.'_crop' ) ? '' : 'un' ).'cropped.';
							break;
						case 'tooltip-tc_gal_minimum':
							$text = 'The minimum number of images found in a gallery to qualify for the <a href="https://dev.twitter.com/docs/cards/types/gallery-card" target="_blank">Gallery Card</a>.';
							break;
						case 'tooltip-tc_gal_dimensions':
							$card = 'gal';
							$text = 'The dimension of gallery images provided for the <a href="https://dev.twitter.com/docs/cards/types/gallery-card" target="_blank">Gallery Card</a>. The default image dimensions are '.$this->p->opt->get_defaults( 'tc_'.$card.'_width' ).'x'.$this->p->opt->get_defaults( 'tc_'.$card.'_height' ).', '.( $this->p->opt->get_defaults( 'tc_'.$card.'_crop' ) ? '' : 'un' ).'cropped.';
							break;
						case 'tooltip-tc_prod_dimensions':
							$card = 'prod';
							$text = 'The dimension of a <em>featured product image</em> for the <a href="https://dev.twitter.com/docs/cards/types/product-card" target="_blank">Product Card</a>. The product card requires an image of size 160 x 160 or greater. A square (aka cropped) image is better, but Twitter can crop/resize oddly shaped images to fit, as long as both dimensions are greater than or equal to 160 pixels. The default image dimensions are '.$this->p->opt->get_defaults( 'tc_'.$card.'_width' ).'x'.$this->p->opt->get_defaults( 'tc_'.$card.'_height' ).', '.( $this->p->opt->get_defaults( 'tc_'.$card.'_crop' ) ? '' : 'un' ).'cropped.';
							break;
						case 'tooltip-tc_prod_labels':
							$text = 'The maximum number of label and data meta tags to include for the <em>Product</em> Twitter Card.';
							break;
						case 'tooltip-tc_prod_defaults':
							$text = 'The <em>Product</em> Twitter Card needs a <strong>minimum of two product attributes</strong>. The first attribute will be the product price, and if your product has additional attribute fields associated with it (weight, size, color, etc), these will be included in the <em>Product</em> Card as well (maximum of 4 attributes). <strong>If your product does not have additional attributes beyond its price</strong>, then this default second attribute label and value will be used. You may modify both the Label <em>and</em> Value for whatever is most appropriate for your website and/or products. Some examples: Promotion / Free Shipping, Ships from / Hong Kong, Made in / China, etc.';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_tc', $text, $idx );
							break;
					}
					break;

				/*
				 * Publisher 'Pinterest' (Rich Pin) settings
				 */
				case ( strpos( $idx, 'tooltip-rp_' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'tooltip-rp_publisher_url':
							$text = 'If you have a <a href="https://business.pinterest.com/" target="_blank">Pinterest Business Page for your website / business</a>, you may enter its URL here. The Publisher Business Page URL will be used in the schema publisher (Organization) social JSON. Google Search may use this information to display additional publisher / business details in its search results.';
							break;
						case 'tooltip-rp_img_dimensions':
							$text = 'The image dimensions specifically for Rich Pin meta tags when the Pinterest crawler is detected (defaults is '.$this->p->opt->get_defaults( 'rp_img_width' ).'x'.$this->p->opt->get_defaults( 'rp_img_height' ).' '.( $this->p->opt->get_defaults( 'rp_img_crop' ) == 0 ? 'un' : '' ).'cropped). Images in the Facebook / Open Graph meta tags are usually cropped, where-as images on Pinterest often look better in their original aspect ratio (aka uncropped). Note that original images in the WordPress Media Library and/or NextGEN Gallery must be larger than your chosen image dimensions.';
							break;
						case 'tooltip-rp_author_name':
							$text = 'Pinterest ignores Facebook-style Author Profile URLs in the \'article:author\' Open Graph meta tags. An <em>additional</em> \'article:author\' meta tag may be included when the Pinterest crawler is detected. Select an Author Name Format, or \'[none]\' to disable this feature (the default and recommended value is \'Display Name\').';
							break;
						case 'tooltip-rp_dom_verify':
							$text = 'To <a href="https://help.pinterest.com/en/articles/verify-your-website#meta_tag" target="_blank">verify your website</a> with Pinterest, edit your business account profile on Pinterest, click the \'Verify Website\' button, and enter the p:domain_verify meta tag <em>content</em> value here.';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_rp', $text, $idx );
							break;
					}
					break;

				/*
				 * Publisher 'Instagram' settings
				 */
				case ( strpos( $idx, 'tooltip-instgram_' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'tooltip-instgram_publisher_url':
							$text = 'If you have an <a href="http://blog.business.instagram.com/" target="_blank">Instagram account for your website / business</a>, you may enter its URL here. The Instagram Business URL will be used in the schema publisher (Organization) social JSON. Google Search may use this information to display additional publisher / business details in its search results.';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_instgram', $text, $idx );
							break;
					}
					break;

				/*
				 * Publisher 'LinkedIn' settings
				 */
				case ( strpos( $idx, 'tooltip-linkedin_' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'tooltip-linkedin_publisher_url':
							$text = 'If you have a <a href="https://business.linkedin.com/marketing-solutions/company-pages/get-started" target="_blank">LinkedIn Company Page for your website / business</a>, you may enter its URL here. For example, the LinkedIn Company Page URL for Surnia Ulula is <a href="https://www.linkedin.com/company/surnia-ulula-ltd" target="_blank">https://www.linkedin.com/company/surnia-ulula-ltd</a>. The LinkedIn Company Page URL will be included in the schema publisher (Organization) social JSON. Google Search may use this information to display additional publisher / business details in its search results.';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_linkedin', $text, $idx );
							break;
					}
					break;

				/*
				 * Publisher 'MySpace' settings
				 */
				case ( strpos( $idx, 'tooltip-myspace_' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'tooltip-myspace_publisher_url':
							$text = 'If you have a <a href="http://myspace.com/" target="_blank">MySpace account for your website / business</a>, you may enter its URL here. The MySpace Business (Brand) URL will be used in the schema publisher (Organization) social JSON. Google Search may use this information to display additional publisher / business details in its search results.';
							break;
						default:
							$text = apply_filters( $lca.'_tooltip_instgram', $text, $idx );
							break;
					}
					break;

				/*
				 * 'Profile Contact Fields' settings
				 */
				case 'tooltip-custom-cm-field-name':
					$text = '<strong>You should not modify the contact field names unless you have a specific reason to do so.</strong> As an example, to match the contact field name of a theme or other plugin, you might change \'gplus\' to \'googleplus\'. If you change the Facebook or Google+ field names, please make sure to update the Open Graph <em>Author Profile URL</em> and <em>Google Author Link URL</em> options in the '.$this->p->util->get_admin_url( 'general', 'General settings' ).' as well.';
					break;
				case 'tooltip-wp-cm-field-name':
					$text = 'The built-in WordPress contact field names cannot be modified.';
					break;

				/*
				 * Misc informational messages
				 */
				case ( strpos( $idx, 'info-' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'info-plugin-tid':
							$text = '<blockquote style="margin-top:0;margin-bottom:10px;">
							<p>After purchasing Pro version license(s), an email is sent to you with a <strong>unique Authentication ID</strong> and installation / activation instructions. Enter the unique Authentication ID on this page to check for Pro version updates immediately and every 24 hours thereafter.</p>
							<p><strong>'.$name.' must be active in order to check for Pro version updates.</strong> If you accidentally de-activate the plugin, update information will be provided by the WordPress.org Free plugin repository, and any update notices will be for the Free version &mdash; always update the Pro version when '.$short.' is active. If you accidentally re-install the Free version from WordPress.org &mdash; don\'t worry &mdash; your Authentication ID will always allow you update back to the Pro version. ;-)</p>
							</blockquote>';
							break;
						case 'info-plugin-tid-network':
							$text = '<blockquote style="margin-top:0;margin-bottom:10px;">
							<p>After purchasing Pro version license(s), an email is sent to you with a <strong>unique Authentication ID</strong> and installation / activation instructions. Enter the unique Authentication ID on this page to define default/forced a value for <em>all</em> sites within the network, or enter the Authentication ID(s) individually on each site\'s <em>Extension Plugins and Pro Licenses</em> settings page. <strong>Please note that the <em>default</em> site / blog must be licensed in order to update the plugin from the Network admin interface</strong>.</p>
							</blockquote>';
							break;
						case 'info-review':
							$text = '<blockquote style="margin-top:0;">
							<p>If you appreciate the features or quality of this plugin, and/or the support we provide, please <a href="'.$url['review'].'" target="_blank">take a moment to rate the '.$short.' plugin on WordPress.org</a>. Your rating will help other WordPress users find higher quality and better supported plugins &mdash; and <strong>encourage us to keep improving '.$short.'</strong> as well! ;-)</p>
							</blockquote>';
							break;
						case 'info-pub-pinterest':
							$text = '<blockquote style="margin-top:0;margin-bottom:10px;">
							<p>Pinterest uses Open Graph meta tags for their Rich Pins. These options allow you to manage and/or override some Pinterest-specific Open Graph settings. Please note that if you use a full-page caching plugin or front-end caching service, it should detect the Pinterest crawler user-agent and bypass the cache, so that different meta tags can be provided to the Pinterest crawler (for example, look for a "<em>User-Agent Exclusion Pattern</em>" option and add "Pinterest/" to that list).</p>
							</blockquote>';
							break;
						case 'info-taglist':
							$text = '<blockquote style="margin:0;">
							<p>'.$short.' will add the following Google / SEO, Facebook, Open Graph, Rich Pin, Schema, and Twitter Card HTML tags to the <code>head</code> section of your webpages. If your theme or another plugin already generates one or more of these HTML tags, you can uncheck them here to prevent duplicates from being added (as an example, the "meta name description" HTML tag is automatically unchecked if a known SEO plugin is detected).</p>
							</blockquote>';
							break;
						case 'info-cm':
							$text = '<blockquote style="margin-top:0;margin-bottom:10px;">
							<p>The following options allow you to customize the contact field names and labels shown on the <a href="'.get_admin_url( null, 'profile.php' ).'">user profile</a> page. '.$short.' uses the Facebook, Google+ and Twitter contact field values for Open Graph and Twitter Card meta tags'.( empty( $this->p->is_avail['ssb'] ) ? '' : ', along with the Twitter social sharing button' ).'. <strong>You should not modify the <em>Contact Field Name</em> unless you have a very good reason to do so.</strong> The <em>Profile Contact Label</em> on the other hand is for <strong>display purposes only</strong>, and its text can be changed as you wish. Although the following contact fields may be shown on user profile pages, your theme is responsible for using and displaying their values appropriately (see <a href="https://codex.wordpress.org/Function_Reference/get_the_author_meta" target="_blank">get_the_author_meta()</a> for examples).</p>
							<p><center><strong>DO NOT ENTER YOUR CONTACT INFORMATION HERE &ndash; THESE ARE CONTACT FIELD LABELS ONLY.</strong><br/>Enter your contact information on the <a href="'.get_admin_url( null, 'profile.php' ).'">user profile</a> page.</center></p>
							</blockquote>';
							break;
						case 'info-image-dimensions':
							$text = '<blockquote style="margin-top:0;margin-bottom:0;">
							<p>'.$short.' provides several image dimension options, depending on the intended use for the image (Open Graph meta tags, Twitter Card formats, etc.). The image dimensions should always be chosen for their intended use. For example, Open Graph meta tags are read by Facebook, Google+, LinkedIn, and others. Facebook has published a preference for images measuring 1200x630px, but horizontally cropped images may not show as well on all social sites. A good compromise for your Open Graph image dimensions is 1200x1200px cropped. If you use these dimensions, make sure your original images are at least 1200px in <em>both</em> width and height.</p>
							</blockquote>';
							break;
						case 'info-social-accounts':
							$text = '<blockquote style="margin-top:0;margin-bottom:0;">
							<p>The social account values are used for Google / SEO, Schema, Open Graph, and other meta tag standards, including the publisher (Organization) social profiles markup in schema.org JSON-LD format. These social accounts may be displayed by Google in search results for your website / business. See the '.$this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_google', 'Google specific settings' ).' to define a website / business logo, and/or enable / disable the addition of publisher (Organization) and author (Person) JSON-LD markup to your webpage headers.</p>
							</blockquote>';
							break;
						case 'info-sharing-include':
							$text = '<blockquote style="margin-top:0;margin-bottom:10px;">
							<p>The buttons enabled bellow (along with those in the widget) can be included or excluded from specific webpage types. This does <em>not</em> apply to the shortcode and function buttons, which are displayed (or not) based on their own parameter options.</p>
							</blockquote>';
							break;
						default:
							$text = apply_filters( $lca.'_messages_info', $text, $idx );
							break;
					}
					break;
				/*
				 * Misc informational messages
				 */
				case ( strpos( $idx, 'pro-' ) !== false ? true : false ):
					switch ( $idx ) {
						case 'pro-feature-msg':
							if ( $this->p->check->aop( $lca, false ) )
								$text = '<p class="pro-feature-msg"><a href="'.$url['purchase'].'" target="_blank">Purchase '.
									$short_pro.' licence(s) to modify the following options and access Pro modules</a></p>';
							else $text = '<p class="pro-feature-msg"><a href="'.$url['purchase'].'" target="_blank">Purchase the '.
								$short_pro.' plugin to modify the following options and get all Pro modules</a></p>';
							break;
						case 'pro-option-msg':
							$text = '<p class="pro-option-msg"><a href="'.$url['purchase'].'" target="_blank">'.
								$short_pro.' is required to use this option</a></p>';
							break;
						case 'pro-activate-msg':
							if ( ! is_multisite() ) {
								$text = '<p><strong>The '.$name.' Authentication ID option is empty.</strong><br/>To enable Pro version features and allow the plugin to authenticate itself for updates, please enter the unique Authentication ID you received by email on the '.$this->p->util->get_admin_url( 'licenses', 'Extension Plugins and Pro Licenses settings page' ).'.</p>';
							}
							break;
						case 'pro-not-installed':
							$text = 'An Authentication ID has been entered for '.$name.', but the Pro version is not yet installed &ndash; don\'t forget to update this plugin to install the latest Pro version.';
							break;
						case 'pro-um-extension-required':
							$um_lca = $lca.'um';
							$um_name = $this->p->cf['plugin'][$um_lca]['name'];
							$um_dl = $this->p->cf['plugin'][$um_lca]['url']['download'];
							$um_latest = $this->p->cf['plugin'][$um_lca]['url']['latest_zip'];
							$upload_url = get_admin_url( null, 'plugin-install.php?tab=upload' );
							$text = '<p>At least one Authentication ID has been entered, but the <strong>'.$um_name.'</strong> extension plugin is not active. This <strong>free extension</strong> is required to update and enable the '.$name_pro.' plugin and its extensions.</p>
							<ol>
							<li><strong>Download the free <a href="'.$um_latest.'">'.$um_name.' plugin archive</a> (zip file).</strong>
							<li><strong>Then <a href="'.$upload_url.'">upload and activate the plugin on this WordPress admin page</a></strong>.</li>
							</ol>
							<p>Once the plugin has been activated, one or more Pro version updates will be available for your licensed plugin(s). You can also <a href="'.$um_dl.'" target="_blank">read more about the '.$um_name.'</a> extension plugin.</p>
							<ol>';
							break;
					}
					break;

				case 'tooltip-site-use':
					$text = 'Individual sites/blogs may use this option value as a default (when the plugin is first activated), if the current site/blog value is blank, or force every site/blog to use this value (disabling the option).';
					break;
				case 'side-purchase':
					$text = '<p>'.$short_pro.' can be purchased quickly and easily via Paypal &ndash; and '.( $this->p->is_avail['aop'] == true ? 'licensed' : 'installed' ).' immediately following your purchase. Pro version licenses do not expire and there are no recurring or yearly fees for updates and support.';
					break;
				case 'side-help':
					$text = '<p>Individual option boxes (like this one) can be opened / closed by clicking on their title bar, moved and re-ordered by dragging them, and removed / added from the <em>Screen Options</em> tab (top-right). Values in multiple tabs can be edited before clicking the \'Save All Changes\' button.</p>';
					break;
				default:
					$text = apply_filters( $lca.'_messages', $text, $idx );
					break;

			}
			if ( is_array( $atts ) && ! empty( $atts['is_locale'] ) )
				$text .= ' This option is localized &mdash; you may change the WordPress admin locale with <a href="https://wordpress.org/plugins/polylang/" target="_blank">Polylang</a>, <a href="https://wordpress.org/plugins/wp-native-dashboard/" target="_blank">WP Native Dashboard</a>, etc., to define alternate values for different languages.';

			if ( strpos( $idx, 'tooltip-' ) !== false && ! empty( $text ) )
				return '<img src="'.WPSSO_URLPATH.'images/question-mark.png" width="14" height="14" class="'.
					$class.'" alt="'.esc_attr( $text ).'" />';
			else return $text;
		}
	}
}

?>
