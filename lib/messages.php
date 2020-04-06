<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoMessages' ) ) {

	class WpssoMessages {

		private $p;

		/**
		 * Instantiated by Wpsso->set_objects() when is_admin() is true.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public function get( $msg_key = false, $info = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'msg_key' => $msg_key,
					'info'    => $info,
				) );
			}

			$msg_key = sanitize_title_with_dashes( $msg_key );

			/**
			 * Set a default text string, if one is provided.
			 */
			if ( is_string( $info ) ) {
				$text = $info;
				$info = array( 'text' => $text );
			} else {
				$text = isset( $info[ 'text' ] ) ? $info[ 'text' ] : '';
			}

			/**
			 * Define and translate certain strings only once. 
			 */
			static $do_once        = null;
			static $pro_transl     = null;
			static $std_transl     = null;
			static $fb_recs_transl = null;

			if ( null === $do_once ) {

				$do_once = true;

				$pro_transl = _x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' );
				$std_transl = _x( $this->p->cf[ 'dist' ][ 'std' ], 'distribution name', 'wpsso' );

				$fb_recs_transl = __( 'Facebook prefers images of 1200x630px cropped (for Retina and high-PPI displays), 600x315px cropped as a recommended minimum, and ignores images smaller than 200x200px.', 'wpsso' );
			}

			/**
			 * Set a lowercase acronym.
			 *
			 * Example lcas: wpsso, wpssojson, wpssoum, etc.
			 */
			$info[ 'lca' ] = $lca = isset( $info[ 'lca' ] ) ? $info[ 'lca' ] : $this->p->lca;

			/**
			 * Get the array of plugin urls (download, purchase, etc.).
			 */
			$url = isset( $this->p->cf[ 'plugin' ][ $lca ][ 'url' ] ) ?
				$this->p->cf[ 'plugin' ][ $lca ][ 'url' ] : array();

			/**
			 * Add query arguments to the Premium purchase URL.
			 *
			 * utm_source   = Use utm_source to identify a search engine,
			 *                newsletter name, or other source. Example: google.
			 *
			 * utm_medium   = Use utm_medium to identify a medium such as email or
			 *                cost-per-click. Example: cpc.
			 *
			 * utm_campaign = Used for keyword analysis. Use utm_campaign to
			 *                identify a specific product promotion or strategic
			 *                campaign. Example: spring_sale.
			 *
			 * utm_term     = Used for paid search. Use utm_term to note the
			 *                keywords for this ad. Example: running+shoes.
			 *
			 * utm_content  = Used for A/B testing and content-targeted ads. Use
			 *                utm_content to differentiate ads or links that point
			 *                to the same URL. Examples: logolink or textlink
			 */
			if ( ! empty( $url[ 'purchase' ] ) ) {

				$url[ 'purchase' ] = add_query_arg( array(
					'utm_source'  => $lca,
					'utm_medium'  => 'plugin',
					'utm_content' => $msg_key,
				), $url[ 'purchase' ] );

			} else {
				$url[ 'purchase' ] = '';
			}

			/**
			 * Make sure specific plugin information is available, like 'short', 'short_pro', etc.
			 */
			foreach ( array( 'short', 'name', 'version' ) as $info_key ) {

				if ( ! isset( $info[ $info_key ] ) ) {
					if ( ! isset( $this->p->cf[ 'plugin' ][ $lca ][ $info_key ] ) ) {	// Just in case.
						$info[ $info_key ] = null;
					} else {
						$info[ $info_key ] = $this->p->cf[ 'plugin' ][ $lca ][ $info_key ];
					}
				}

				$info[ $info_key . '_pro' ] = SucomUtil::get_dist_name( $info[ $info_key ], $pro_transl );

				$info[ $info_key . '_pro_purchase' ] = empty( $url[ 'purchase' ] ) ?
					$info[ $info_key . '_pro' ] : '<a href="' . $url[ 'purchase' ] . '">' . $info[ $info_key . '_pro' ] . '</a>';
			}

			/**
			 * All tooltips
			 */
			if ( strpos( $msg_key, 'tooltip-' ) === 0 ) {

				if ( strpos( $msg_key, 'tooltip-meta-' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-meta-og_schema_type':	// Schema Type.

							$text = __( 'Select a document Schema type that best describes the main content of this webpage.', 'wpsso' );

							$text .= '<br/><br/>';

							$text .= __( 'The Schema type option offers a much larger selection of types than the Open Graph type, and the Open Graph type may reflect the Schema type selected (the Open Graph type option will be disabled in this case).', 'wpsso' ) . ' ';

							$text .= __( 'As an example, a Schema type or sub-type of "Article" will change the Open Graph type to "article", a Schema type or sub-type of "Place" will change the Open Graph type to "place", a Schema type or sub-type of "Product" will change the Open Graph type to "product",  etc.', 'wpsso' ) . ' ';

						 	break;

						case 'tooltip-meta-og_type':		// Open Graph Type

							$text = __( 'Select a document Facebook / Open Graph type that best describes the main content of this webpage.', 'wpsso' ) . ' ';

							$text .= '<br/><br/>';

							$text .= __( 'The Schema type option offers a much larger selection of types than the Open Graph type, and the Open Graph type may reflect the Schema type selected (the Open Graph type option will be disabled in this case).', 'wpsso' ) . ' ';

							$text .= __( 'As an example, a Schema type or sub-type of "Article" will change the Open Graph type to "article", a Schema type or sub-type of "Place" will change the Open Graph type to "place", a Schema type or sub-type of "Product" will change the Open Graph type to "product",  etc.', 'wpsso' ) . ' ';

							$text .= '<br/><br/>';

							$text .= __( 'Note that for social sharing purposes, the document Open Graph type must be "article", "place", "product", or "website".', 'wpsso' ) . ' ';

						 	break;

						case 'tooltip-meta-og_title':		// Default Title

							$text = sprintf( __( 'A customized title for the Facebook / Open Graph %s meta tag, and the default for all other title values.', 'wpsso' ), '<code>og:title</code>' );

						 	break;

						case 'tooltip-meta-og_desc':		// Default Description.

							$text = sprintf( __( 'A customized description for the Facebook / Open Graph %s meta tag, and the default for all other description values.', 'wpsso' ), '<code>og:description</code>' ) . ' ';

							$text .= __( 'Update and save the custom Facebook / Open Graph description to change the default value of all other description fields.', 'wpsso' );

						 	break;

						case 'tooltip-meta-p_img_desc':		// Pinterest Description.

							$text = __( 'A customized description for the Pinterest Pin It browser button.', 'wpsso' );

						 	break;

						case 'tooltip-meta-tc_desc':		// Twitter Card Description.

							$text = __( 'A customized description for the Twitter Card description meta tag (all Twitter Card formats).', 'wpsso' );

						 	break;

						case 'tooltip-meta-seo_desc':		// Search Description.

							$text = __( 'A customized description for the Google Search "description" meta tag.', 'wpsso' );

						 	break;

						case 'tooltip-meta-sharing_url':	// Sharing URL.

							$text = __( 'A customized sharing URL for Facebook / Open Graph and Pinterest Rich Pin meta tags, Schema markup, and social sharing add-ons.', 'wpsso' ) . ' ';

							$text .= __( 'Please make sure the custom URL you enter here is functional and redirects correctly.', 'wpsso' );

						 	break;

						case 'tooltip-meta-canonical_url':	// Canonical URL.

							$text = sprintf( __( 'A customized URL used for the "%1$s" head tag.', 'wpsso' ), 'link rel canonical' ) . ' ';

							$text .= __( 'Please make sure the custom URL you enter here is functional and redirects correctly.', 'wpsso' );

						 	break;

						case 'tooltip-meta-article_section':	// Article Section.

							$general_page_link = $this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_site',
								_x( 'Default Article Section', 'option label', 'wpsso' ) );

							$text = sprintf( __( 'A customized section for this article, which may be different than the %s option value.',
								'wpsso' ), $general_page_link ) . ' ';

							$text .= sprintf( __( 'Select "[None]" if you prefer to exclude the %s meta tag.', 'wpsso' ),
								'<code>article:section</code>' );

						 	break;

						case 'tooltip-meta-product_category':	// Product Category.

							$general_page_link = $this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_site',
								_x( 'Default Product Category', 'option label', 'wpsso' ) );

							$text = sprintf( __( 'A customized category for this product, which may be different than the %s option value.',
								'wpsso' ), $general_page_link ) . ' ';

							$text .= sprintf( __( 'Select "[None]" if you prefer to exclude the %s meta tag.',
								'wpsso' ), '<code>product:category</code>' );

						 	break;

						case ( 0 === strpos( $msg_key, 'tooltip-meta-product_' ) ? true : false ):

							$cf_info = $this->get_cf_info( preg_replace( '/^tooltip-meta-/', '', $msg_key ) );

							if ( ! empty( $cf_info ) ) {	// Just in case.

								$text = sprintf( __( 'The value of %1$s can be used in meta tags and Schema markup for simple products.', 'wpsso' ), $cf_info[ 1 ] ) . ' ';

								$text .= __( 'When e-commerce product variations are available, the value from each variation will be used instead.', 'wpsso' ) . ' ';

								$text .= __( 'This option may be disabled when a supported e-commerce plugin manages the source value.', 'wpsso' );
							}

						 	break;

						case 'tooltip-meta-og_img_crop_area':

							$text = __( 'The main subject area of the Facebook / Open Graph image.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_img_id':

							$text = __( 'A customized image ID to include first, before any featured, attached, or content images.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_img_url':

							$text = __( 'A customized image URL (instead of an image ID) to include first, before any featured, attached, or content images.', 'wpsso' ) . ' ';

							$text .= __( 'Please make sure your custom image is large enough, or it may be ignored by social website(s).', 'wpsso' ) . ' ';

							$text .= $fb_recs_transl . ' ';

							$text .= '<em>' . __( 'This field is disabled if an image ID is selected.', 'wpsso' ) . '</em>';

							break;

						case 'tooltip-meta-og_vid_dimensions':

							$text = sprintf( __( 'The %1$s video API modules can offer default video width and height values, provided that information is available from the API service.', 'wpsso' ), $info[ 'short_pro' ] ) . ' ';

							$text .= __( 'If the default video width and/or height values are incorrect, you may adjust their values here.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_vid_embed':

							$text = __( 'Custom video embed HTML for the first video in the Facebook / Open Graph and Twitter Card meta tags, and in the Schema JSON-LD markup.', 'wpsso' ) . ' ';

							$text .= __( 'If the video is from a recognized external video service, an API connection will be made to retrieve additional information about the video.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_vid_url':

							$text = __( 'A customized video URL for the first video in the Facebook / Open Graph and Twitter Card meta tags, and in the Schema JSON-LD markup.', 'wpsso' ) . ' ';

							$text .= __( 'If the video is from a recognized external video service, an API connection will be made to retrieve additional information about the video.', 'wpsso' );

						 	break;

						case 'tooltip-meta-og_vid_title':
						case 'tooltip-meta-og_vid_desc':

							$text = sprintf( __( 'The %1$s video API modules can offer a default video name / title and description, provided that information is available from the API service.', 'wpsso' ), $info[ 'short_pro' ] ) . ' ';

							$text .= __( 'The video name / title and description will be used in the video Schema JSON-LD markup (add-on required).', 'wpsso' );

							break;

						case 'tooltip-meta-schema_img_crop_area':

							$text = __( 'The main subject area of the Google / Pinterest / Schema image.', 'wpsso' );

							break;

						case 'tooltip-meta-schema_img_id':

							$text = __( 'A customized image ID to include first in the Google / Schema meta tags and JSON-LD markup, before any featured, attached, or content images.', 'wpsso' );

						 	break;

						case 'tooltip-meta-schema_img_url':

							$text = __( 'A customized image URL (instead of an image ID) to include first in the Google / Schema meta tags and JSON-LD markup.', 'wpsso' ) . ' ';
							
							$text .= '<em>' . __( 'This field is disabled if an image ID is selected.', 'wpsso' ) . '</em>';

						 	break;

						case 'tooltip-meta-tc_lrg_img_crop_area':
						case 'tooltip-meta-tc_sum_img_crop_area':

							$text = __( 'The main subject area of the Twitter Card image.', 'wpsso' );

						 	break;

						case 'tooltip-meta-tc_lrg_img_id':
						case 'tooltip-meta-tc_sum_img_id':

							$text = __( 'A customized image ID for the Twitter Card image.', 'wpsso' );

						 	break;

						case 'tooltip-meta-tc_lrg_img_url':
						case 'tooltip-meta-tc_sum_img_url':

							$text = __( 'A customized image URL (instead of an image ID) for the Twitter Card image.', 'wpsso' ) . ' ';
							
							$text .= '<em>' . __( 'This field is disabled if an image ID is selected.', 'wpsso' ) . '</em>';

						 	break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_meta', $text, $msg_key, $info );

							break;

					}	// End of tooltip-user switch.

				/**
				 * Site settings
				 */
				} elseif ( strpos( $msg_key, 'tooltip-site_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-site_name':

							$text = sprintf( __( 'The website name is used for the Facebook / Open Graph and Pinterest Rich Pin %s meta tag.',
								'wpsso' ), '<code>og:site_name</code>' ) . ' ';
							
							break;

						case 'tooltip-site_name_alt':

							$text = __( 'An optional alternate name for your website that you want Google to consider.', 'wpsso' );

							break;

						case 'tooltip-site_desc':

							$text = __( 'The website description is used for the WordPress blog (non-static) front page.', 'wpsso' );

							break;

						case 'tooltip-site_org_schema_type':

							$text = __( 'Google does not recognize all Schema Organization sub-types as valid organization and publisher types.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The WebSite organization type ID should be "%s" unless you are confident that Google will recognize your preferred Schema Organization sub-type as a valid organization.', 'wpsso' ), 'organization' ) . ' ';
							
							$text .= sprintf( __( 'To select a different organization type ID for your WebSite, define the %s constant with your preferred type ID (the type ID, not the Schema type URL).', 'wpsso' ), '<code>WPSSO_SCHEMA_ORGANIZATION_TYPE_ID</code>' );

							break;

						case 'tooltip-site_place_id':

							if ( isset( $this->p->cf[ 'plugin' ][ 'wpssoplm' ] ) ) {

								$plm_info = $this->p->cf[ 'plugin' ][ 'wpssoplm' ];

								$plm_addon_link = $this->p->util->get_admin_url( 'addons#wpssoplm', $plm_info[ 'short' ] );

								$text = sprintf( __( 'Select an optional location for this organization (requires the %s add-on).',
									'wpsso' ), $plm_addon_link );
							}

							break;
					}

				/**
				 * Open Graph settings
				 */
				} elseif ( strpos( $msg_key, 'tooltip-og_' ) === 0 ) {

					switch ( $msg_key ) {

						/**
						 * Site Information tab.
						 */
						case 'tooltip-og_def_article_section':	// Default Article Section.

							$text = __( 'The section that describes the content of the articles on your site.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Your selection will be used by default for the Facebook %s meta tag value.', 'wpsso' ), '<code>article:section</code>' ) . ' ';

							$text .= sprintf( __( 'Select "[None]" to exclude the %s meta tag by default (you can still select a custom section when editing an article).', 'wpsso' ), '<code>article:section</code>' );

							break;

						case 'tooltip-og_def_product_category':	// Default Product Category.

							$text = __( 'The category that describes the products on your site.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Your selection will be used by default for the Facebook %s meta tag value.', 'wpsso' ), '<code>product:category</code>' ) . ' ';

							$text .= sprintf( __( 'Select "[None]" to exclude the %s meta tag by default (you can still select a custom category when editing a product).', 'wpsso' ), '<code>product:category</code>' );

							break;

						case 'tooltip-og_type_for_home_page':	// Type for Page Homepage.

							$def_type = $this->p->opt->get_defaults( 'og_type_for_home_page' );

							$text = sprintf( __( 'Select the %1$s type for a static front page.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_home_posts':	// Type for Posts Homepage.

							$def_type = $this->p->opt->get_defaults( 'og_type_for_home_posts' );

							$text = sprintf( __( 'Select the %1$s type for a blog (non-static) front page.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_user_page':	// Type for User / Author.

							$def_type = $this->p->opt->get_defaults( 'og_type_for_user_page' );

							$text = sprintf( __( 'Select the %1$s type for user / author pages.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_search_page':	// Type for Search Results.

							$def_type = $this->p->opt->get_defaults( 'og_type_for_search_page' );

							$text = sprintf( __( 'Select the %1$s type for search results pages.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_archive_page':	// Type for Other Archive.

							$def_type = $this->p->opt->get_defaults( 'og_type_for_archive_page' );

							$text = sprintf( __( 'Select the %1$s type for other archive pages (example: date-based archive pages).', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Open Graph', $def_type  );

							break;

						case 'tooltip-og_type_for_ptn':		// Type by Post Type.

							$text = sprintf( __( 'Select the %1$s type for each WordPress post type.', 'wpsso' ), 'Open Graph' ) . ' ';

							$text .= __( 'Please note that each Open Graph type has a unique set of meta tags, so by selecting "website" here (for example), you would be excluding all "article" related meta tags (<code>article:author</code>, <code>article:section</code>, etc.).', 'wpsso' );

							break;

						case 'tooltip-og_type_for_ttn':		// Type by Taxonomy.

							$text = __( 'Select the Open Graph type for each WordPress taxonomy.', 'wpsso' );

							break;

						/**
						 * Content and Text tab.
						 */
						case 'tooltip-og_title_sep':		// Title Separator.

							$text = sprintf( __( 'One or more characters used to separate values (category parent names, page numbers, etc.) within the Facebook / Open Graph title string (the default is the hyphen "%s" character).', 'wpsso' ), $this->p->opt->get_defaults( 'og_title_sep' ) );

							break;

						case 'tooltip-og_title_max_len':	// Maximum Title Length.

							$text = sprintf( __( 'The maximum length for the Facebook / Open Graph title value (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'og_title_max_len' ) );

							break;

						case 'tooltip-og_desc_max_len':		// Maximum Description Length.

							$text = sprintf( __( 'The maximum length for the Facebook / Open Graph description value (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'og_desc_max_len' ) ) . ' ';

							$text .= sprintf( __( 'The maximum length must be at least %d characters or more.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ 'og_desc_len' ] );

							break;

						case 'tooltip-og_desc_hashtags':	// Add Hashtags to Descriptions.

							$text = __( 'The maximum number of tag names (converted to hashtags) to include in the Facebook / Open Graph description.', 'wpsso' ) . ' ';

							$text .= __( 'Each tag name is converted to lowercase with whitespaces removed.', 'wpsso' ) . ' ';

							$text .= __( 'Select "0" to disable the addition of hashtags.', 'wpsso' );

							break;

						/**
						 * Authorship tab.
						 */
						case 'tooltip-og_author_field':		// Author Profile URL Field.

							$text = sprintf( __( 'Select the contact field to use from the author\'s WordPress profile page for the Facebook / Open Graph %s meta tag value.', 'wpsso' ), '<code>article:author</code>' ) . ' ';

							$text .= __( 'The suggested setting is the "Facebook URL" user profile contact field (default value).', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Select "[None]" if you prefer to exclude the %s meta tag and prevent Facebook from showing author attribution in shared links.', 'wpsso' ), '<code>article:author</code>' );

							break;

						/**
						 * Images tab.
						 */
						case 'tooltip-og_img_max':		// Maximum Images to Include.

							$text = __( 'The maximum number of images to include in Open Graph meta tags, including custom, featured, attached, and content images.', 'wpsso' ) . ' ';

							$text .= __( 'If you select "0", then no images will be included in Open Graph meta tags (<strong>not recommended</strong>).', 'wpsso' ) . ' ';

							$text .= __( 'If no images are included in your Open Graph meta tags, then social sites may select any image from the webpage (including headers, sidebars, thumbnails, etc.).', 'wpsso' );

							break;

						case 'tooltip-og_img_size':		// Open Graph.

							$def_img_dims = $this->get_def_img_dims( 'og_img' );

							$text = sprintf( __( 'The image dimensions used for Facebook / Open Graph meta tags and oEmbed markup (the default dimensions are %s).', 'wpsso' ), $def_img_dims ) . ' ';

							$text .= $fb_recs_transl;

							break;

						case 'tooltip-og_def_img_id':		// Default / Fallback Image ID.

							$text = __( 'An image ID and media library selection for your default / fallback website image.', 'wpsso' ) . ' ';

							$text .= __( 'The default image is used for index / archive pages, and as a fallback for Posts and Pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' );

							break;

						case 'tooltip-og_def_img_url':		// or Default / Fallback Image URL.

							$text = __( 'You can enter a default image URL instead of choosing an image ID &mdash; if a default image ID is specified, it has precedence and the image URL option is disabled.', 'wpsso' ) . ' ';

							$text .= __( 'The image URL option allows you to use an image outside of a managed collection (WordPress Media Library or NextGEN Gallery), and/or a smaller logo style image.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The image should be at least %s or more in width and height.', 'wpsso' ),
								$this->p->cf[ 'head' ][ 'limit_min' ][ 'og_img_width' ] . 'x' .
									$this->p->cf[ 'head' ][ 'limit_min' ][ 'og_img_height' ] . 'px' ) . ' ';

							$text .= __( 'The default image is used for index / archive pages, and as a fallback for Posts and Pages that do not have a suitable image featured, attached, or in their content.', 'wpsso' );

							break;

						/**
						 * Videos tab.
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

						default:

							$text = apply_filters( $lca . '_messages_tooltip_og', $text, $msg_key, $info );

							break;

					}	// End of tooltip-og switch.


				/**
				 * Advanced plugin settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-plugin_' ) === 0 ) {

					switch ( $msg_key ) {

						/**
						 * Plugin Behavior settings.
						 */
						case 'tooltip-plugin_clean_on_uninstall': // Remove Settings on Uninstall.

							$text = sprintf( __( 'Check this option to remove all %s settings when you <em>uninstall</em> the plugin. This includes any custom post, term, and user meta.', 'wpsso' ), $info[ 'short' ] );

							break;

						case 'tooltip-plugin_debug': 		// Add Hidden Debug Messages.

							$text = __( 'Add debugging messages as hidden HTML comments to back-end and front-end webpages (default is unchecked).', 'wpsso' );

							break;

						case 'tooltip-plugin_show_opts': 	// Options to Show by Default.

							$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

							$text = sprintf( __( 'You can select the default set of options to display in settings pages and the %1$s metabox.', 'wpsso' ), $metabox_title ) . ' ';

							$text .= __( 'The basic view shows the most commonly used options, and includes a link to temporarily show all options when desired.', 'wpsso' ) . ' ';

							$text .= __( 'Note that showing all options by default could be a bit overwhelming for new users.', 'wpsso' );

							break;

						case 'tooltip-plugin_notice_system': 	// Notification System.

							$text .= __( 'Select the notification system for SSO notices.', 'wpsso' ) . ' ';

							$text .= __( 'Toolbar notices are available under a single toolbar menu item, which changes color based on the notice types it contains.', 'wpsso' ) . ' ';

							$text .= __( 'Alternatively, you can select to use the standard WordPress admin notification system.', 'wpsso' );

							break;

						/**
						 * Content settings.
						 */
						case 'tooltip-plugin_filter_title':	// Use Filtered (aka SEO) Title.

							$def_checked = $this->p->opt->get_defaults( 'plugin_filter_title' ) ?
								_x( 'checked', 'option value', 'wpsso' ) :
								_x( 'unchecked', 'option value', 'wpsso' );

							$text = __( 'The title values provided by WordPress may include modifications by themes and/or SEO plugins (appending the site name or expanding inline variables, for example, is a common practice).', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Uncheck this option to always use the original unmodified title value from WordPress (default is %s).', 'wpsso' ), $def_checked ) . ' ';

							break;

						case 'tooltip-plugin_filter_content':	// Use WordPress Content Filters.

							$text .= __( 'The use of WordPress content filters allows the plugin to fully render your content text for meta tag descriptions and detect additional images and/or embedded videos provided by shortcodes.', 'wpsso' ) . ' ';

							$text .= __( 'Many themes and plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ) . ' ';

							$text .= __( 'If you use shortcodes in your content text, this option should be enabled &mdash; IF YOU EXPERIENCE WEBPAGE LAYOUT OR PERFORMANCE ISSUES AFTER ENABLING THIS OPTION, determine which theme or plugin is filtering the content incorrectly and report the problem to its author(s).', 'wpsso' ) . ' ';

							break;

						case 'tooltip-plugin_filter_excerpt':	// Use WordPress Excerpt Filters.

							$text = __( 'Apply the WordPress "get_the_excerpt" filter to the excerpt text (default is unchecked). Enable this option if you use shortcodes in your excerpts, for example.', 'wpsso' ) . ' ';

							break;

						case 'tooltip-plugin_p_strip':	// Content Starts at 1st Paragraph.

							$text = sprintf( __( 'If a post, page, or custom post type does not have an excerpt, %s will use the content text to create a description value.', 'wpsso' ), $info[ 'short' ] ) . ' ';

							$text .= __( 'When this option is enabled, all text before the first paragraph tag in the content will be ignored.', 'wpsso' ) . ' ';

							$text .= __( 'The option is enabled by default since WordPress should provide correct paragraph tags in the content.', 'wpsso' );

							break;

						case 'tooltip-plugin_use_img_alt':	// Use Image Alt if No Content.

							$text = sprintf( __( 'If the content text is comprised entirely of HTML tags (which must be removed to create a text-only description), %1$s can extract and use the image %2$s attributes it finds, instead of returning an empty description.', 'wpsso' ), $info[ 'short' ], '<em>alt</em>' );

							break;

						case 'tooltip-plugin_img_alt_prefix':	// Content Image Alt Prefix.

							$text = sprintf( __( 'When the text from image %1$s attributes is used, %2$s can prefix the attribute text with an optional string (for example, "Image:").', 'wpsso' ), '<em>alt</em>', $info[ 'short' ] ) . ' ';

							$text .= sprintf( __( 'Leave this option blank to prevent the text from image %s attributes from being prefixed.', 'wpsso' ), '<em>alt</em>' );

							break;

						case 'tooltip-plugin_p_cap_prefix':	// WP Caption Text Prefix.

							$text = sprintf( __( '%1$s can prefix caption paragraphs found with the "%2$s" class (for example, "Caption:").', 'wpsso' ), $info[ 'short' ], 'wp-caption-text' ) . ' ';

							$text .= __( 'Leave this option blank to prevent caption paragraphs from being prefixed.', 'wpsso' );

							break;

						case 'tooltip-plugin_no_title_text':	// No Title Text.

							$text = __( 'A fallback string to use when there is no title text available (for example, "No Title").' );

							break;

						case 'tooltip-plugin_no_desc_text':	// No Description Text.

							$text = __( 'A fallback string to use when there is no description text available (for example, "No Description.").' );

							break;

						case 'tooltip-plugin_embed_media_apis':

							$text = __( 'Check the content for embedded media URLs from supported media providers (Vimeo, Wistia, YouTube, etc.). If a supported media URL is found, an API connection to the provider will be made to retrieve information about the media (preview image URL, flash player URL, oembed player URL, the video width / height, etc.).', 'wpsso' );

							break;

						/**
						 * Integration settings
						 */
						case 'tooltip-plugin_html_attr_filter':	// <html> Attributes Filter Hook.

							$func_name = 'language_attributes()';

							$func_url = __( 'https://developer.wordpress.org/reference/functions/language_attributes/', 'wpsso' );

							$filter_name = 'language_attributes';

							$tag_code = '<code>&amp;lt;html&amp;gt;</code>';

							$php_code = '<pre><code>&amp;lt;html &amp;lt;?php language_attributes(); ?&amp;gt;&amp;gt;</code></pre>';

							$text = sprintf( __( '%1$s hooks the "%2$s" filter (by default) to add / modify the %3$s HTML tag attributes for Open Graph namespace prefix values.', 'wpsso' ), $info[ 'short' ], $filter_name, $tag_code ) . ' ';

							$text .= sprintf( __( 'The <a href="%1$s">WordPress %2$s function</a> and its "%3$s" filter are used by most themes &mdash; if the namespace prefix values are missing from your %4$s HTML tag attributes, make sure your header template(s) use the %2$s function.', 'wpsso' ), $func_url, '<code>' . $func_name . '</code>', $filter_name, $tag_code ) . ' ';

							$text .= __( 'Leaving this option empty disables the addition of Open Graph namespace values.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Example code for header templates: %1$s', 'wpsso' ), $php_code );

							break;

						case 'tooltip-plugin_head_attr_filter':	// <head> Attributes Filter Hook.

							$filter_name = 'head_attributes';

							$tag_code = '<code>&amp;lt;head&amp;gt;</code>';

							$php_code = '<pre><code>&amp;lt;head &amp;lt;?php do_action( &#39;add_head_attributes&#39; ); ?&amp;gt;&amp;gt;</code></pre>';

							$text = sprintf( __( '%1$s hooks the "%2$s" filter (by default) to add / modify the %3$s HTML tag attributes for Schema itemscope / itemtype markup.', 'wpsso' ), $info[ 'short' ], $filter_name, $tag_code ) . ' ';

							$text .= sprintf( __( 'If your theme already offers a filter for the %1$s HTML tag attributes, enter its name here (most themes do not offer this filter).', 'wpsso' ), $tag_code ) . ' ';

							$text .= sprintf( __( 'Alternatively, you can edit your your theme header templates and add an action to call the "%1$s" filter.', 'wpsso' ), $filter_name ) . ' ';

							$text .= sprintf( __( 'Example code for header templates: %1$s', 'wpsso' ), $php_code );

							break;

						case 'tooltip-plugin_new_user_is_person':	// Add Person Role for New Users.

							$text = sprintf( __( 'Automatically add the "%s" role when a new user is created.', 'wpsso' ),
								_x( 'Person', 'user role', 'wpsso' ) );

							break;

						case 'tooltip-plugin_page_excerpt':	// Enable WP Excerpt for Pages.

							$text = __( 'Enable the WordPress excerpt metabox for Pages.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'An excerpt is an optional hand-crafted summary of your content, that %s can also use as a default description value for meta tags and Schema markup.', 'wpsso' ), $info[ 'short' ] );

							break;

						case 'tooltip-plugin_page_tags':	// Enable WP Tags for Pages.

							$text = __( 'Enable the WordPress tags metabox for Pages.', 'wpsso' ) . ' ';

							$text .= __( 'WordPress tags are optional keywords about the content subject, often used for searches and "tag clouds".', 'wpsso' ) . ' ';

							$text .= sprintf( __( '%s can convert WordPress tags into hashtags for some social sites.', 'wpsso' ), $info[ 'short' ] );

							break;

						case 'tooltip-plugin_check_head':	// Check for Duplicate Meta Tags.

							$check_head_count = SucomUtil::get_const( 'WPSSO_DUPE_CHECK_HEADER_COUNT', 10 );

							$text = sprintf( __( 'When editing Posts and Pages, %1$s can check the head section of webpages for conflicting and/or duplicate HTML tags. After %2$d <em>successful</em> checks, no additional checks will be performed &mdash; until the theme and/or any plugin is updated, when another %2$d checks are performed.', 'wpsso' ), $info[ 'short' ], $check_head_count );

							break;

						case 'tooltip-plugin_check_img_dims':	// Enforce Image Size Checks.

							$img_sizes_page_link = $this->p->util->get_admin_url( 'image-sizes',
								_x( 'Image Sizes', 'lib file description', 'wpsso' ) );

							$text = sprintf( __( 'When this option is enabled, full size images used for meta tags and Schema markup must be equal to (or larger) than the image dimensions you\'ve defined in the %s settings &mdash; images that do not meet or exceed the minimum requirements will be ignored.', 'wpsso' ), $img_sizes_page_link );

							break;

						case 'tooltip-plugin_upscale_images':	// Upscale Media Library Images.

							$text = __( 'WordPress does not upscale / enlarge images &mdash; WordPress can only create smaller images from larger full size originals.', 'wpsso' ) . ' ';

							$text .= __( 'Upscaled images do not look as sharp or clear, and if enlarged too much, will look fuzzy and unappealing &mdash; not something you want to promote on social and search sites.', 'wpsso' ) . ' ';

							$text .= sprintf( __( '%1$s includes a feature that allows upscaling of WordPress Media Library images for %2$s image sizes (up to a maximum upscale percentage).', 'wpsso' ), $info[ 'name_pro' ], $info[ 'short' ] ) . ' ';

							$text .= '<strong>' . __( 'Do not enable this option unless you want to publish lower quality images on social and search sites.', 'wpsso' ) . '</strong>';

							break;

						case 'tooltip-plugin_upscale_img_max':	// Maximum Image Upscale Percent.

							$upscale_max = WpssoConfig::$cf[ 'opt' ][ 'defaults' ][ 'plugin_upscale_img_max' ];

							$text = sprintf( __( 'When upscaling of %1$s image sizes is allowed, %2$s can make sure smaller images are not upscaled beyond reason, which would publish very low quality / fuzzy images on social and search sites (the default maximum is %3$s%%).', 'wpsso' ), $info[ 'short' ], $info[ 'name_pro' ], $upscale_max ) . ' ';

							$text .= __( 'If an image needs to be upscaled beyond this maximum, in either width or height, the image will not be upscaled.', 'wpsso' );

							break;

						/**
						 * Cache settings.
						 */
						case 'tooltip-plugin_head_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf[ 'opt' ][ 'defaults' ][ 'plugin_head_cache_exp' ];

							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'Head meta tags and Schema markup are saved to the WordPress transient cache to optimize performance.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_content_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf[ 'opt' ][ 'defaults' ][ 'plugin_content_cache_exp' ];

							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'Filtered post content is saved to the WordPress <em>non-persistent</em> object cache to optimize performance.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_short_url_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf[ 'opt' ][ 'defaults' ][ 'plugin_short_url_cache_exp' ];

							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'Shortened URLs are saved to the WordPress transient cache to optimize performance and API connections.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_imgsize_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf[ 'opt' ][ 'defaults' ][ 'plugin_imgsize_cache_exp' ];

							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'The size for image URLs (not image IDs) is retrieved and saved to the WordPress transient cache to optimize performance and network bandwidth.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_select_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf[ 'opt' ][ 'defaults' ][ 'plugin_select_cache_exp' ];

							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'The filtered text list arrays (for example, article sections and product categories) are saved to the WordPress transient cache to optimize performance and disk access.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_types_cache_exp':

							$cache_exp_secs = WpssoConfig::$cf[ 'opt' ][ 'defaults' ][ 'plugin_types_cache_exp' ];

							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = __( 'The filtered Schema types array is saved to the WordPress transient cache to optimize performance.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The suggested cache expiration value is %1$s seconds (%2$s).', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_clear_on_activate':	// Clear All Caches on Activate.

							$text = sprintf( __( 'Automatically clear all caches when the %s plugin is activated.', 'wpsso' ), $info[ 'short' ] );

							break;

						case 'tooltip-plugin_clear_on_deactivate':	// Clear All Caches on Deactivate.

							$text = sprintf( __( 'Automatically clear all caches when the %s plugin is deactivated.', 'wpsso' ), $info[ 'short' ] );

							break;

						case 'tooltip-plugin_clear_on_save':		// Clear All Caches on Save Settings.

							$text = sprintf( __( 'Automatically clear all caches when the %s plugin settings are saved.', 'wpsso' ), $info[ 'short' ] );

							break;

						case 'tooltip-plugin_clear_short_urls':		// Refresh Short URLs on Clear Cache.

							$cache_exp_secs = (int) apply_filters( $this->p->lca . '_cache_expire_short_url',
								$this->p->options[ 'plugin_short_url_cache_exp' ] );

							$cache_exp_human = $cache_exp_secs ? human_time_diff( 0, $cache_exp_secs ) : 
								_x( 'disabled', 'option comment', 'wpsso' );

							$text = sprintf( __( 'Clear all shortened URLs when clearing all %s transients from the WordPress database (default is unchecked).', 'wpsso' ), $info[ 'short' ] ) . ' ';

							$text .= sprintf( __( 'Shortened URLs are cached for %1$s seconds (%2$s) to minimize external service API calls. Updating all shortened URLs at once may exceed API call limits imposed by your shortening service provider.', 'wpsso' ), $cache_exp_secs, $cache_exp_human );

							break;

						case 'tooltip-plugin_clear_post_terms':		// Clear Term Cache for Published Post.

							$text = __( 'When a published post, page, or custom post type is updated, automatically clear the cache of its selected terms (categories, tags, etc.).', 'wpsso' );

							break;

						case 'tooltip-plugin_clear_for_comment':	// Clear Post Cache for New Comment.

							$text = __( 'Automatically clear the post cache when a new comment is added or the status of an existing comment is changed.', 'wpsso' );

							break;

						/**
						 * Service APIs settings.
						 */
						case 'tooltip-plugin_gravatar_api':	// Gravatar is Author Default Image.

							$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

							$text = __( 'If no custom image has been defined for an author, fallback to using their Gravatar image (if available) in author related meta tags and Schema markup.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'A customized image can be selected for/by each author in the WordPress user profile %s metabox.', 'wpsso' ), $metabox_title );

							break;

						case 'tooltip-plugin_shortener':

							$text = sprintf( __( 'A preferred URL shortening service for %s plugin filters and/or add-ons that may need to shorten URLs &mdash; don\'t forget to define the service API keys for the URL shortening service of your choice.', 'wpsso' ), $info[ 'short' ] );

							break;

						case 'tooltip-plugin_min_shorten':

							$text = sprintf( __( 'URLs shorter than this length will not be shortened (the default suggested by Twitter is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'plugin_min_shorten' ) );

							break;

						case 'tooltip-plugin_wp_shortlink':

							$text = sprintf( __( 'Use the shortened sharing URL for the <em>Get Shortlink</em> button in admin editing pages, along with the "%s" HTML tag value.', 'wpsso' ), 'link&nbsp;rel&nbsp;shortlink' );

							break;

						case 'tooltip-plugin_add_link_rel_shortlink':

							$text = sprintf( __( 'Add a "%s" HTML tag for social sites and web browsers to the head section of webpages.', 'wpsso' ), 'link&nbsp;rel&nbsp;shortlink' );

							break;

						case 'tooltip-plugin_bitly_access_token':	// Bitly Generic Access Token.

							$text = __( 'The Bitly shortening service requires a Generic Access Token to shorten URLs.', 'wpsso' ) . ' ';

							$text .= sprintf( __( '<a href="%s">You can create a Generic Access Token in your Bitly profile settings</a> and enter its value here.', 'wpsso' ), 'https://bitly.com/a/oauth_apps' );

							break;

						case 'tooltip-plugin_bitly_domain':		// Bitly Short Domain (Optional).

							$text = __( 'An optional Bitly short domain to use - either bit.ly, j.mp, bitly.com, or another custom short domain.', 'wpsso' ) . ' ';

							$text .= __( 'If no value is entered here, the short domain selected in your Bitly account settings will be used.', 'wpsso' );

							break;

						case 'tooltip-plugin_bitly_group_name':		// Bitly Group Name (Optional).

							$text = sprintf( __( 'An optional <a href="%s">Bitly group name to organize your Bitly account links</a>.', 'wpsso' ),
								'https://support.bitly.com/hc/en-us/articles/115004551268' );

							break;

						case 'tooltip-plugin_dlmyapp_api_key':

							$text = __( 'The DLMY.App secret API Key can be found in the DLMY.App user account &gt; Tools &gt; Developer API webpage.', 'wpsso' );

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

						/**
						 * Table Columns settings
						 */
						case 'tooltip-plugin_show_columns':	// Additional List Table Columns.

							$text = __( 'Additional columns can be included in admin list tables to show the Schema type ID, Open Graph image, etc.', 'wpsso' ) . ' ';

							$text .= __( 'When a column is enabled, <strong>each user can still hide that column</strong> by using the <em>Screen Options</em> tab on the list table page.', 'wpsso' );

							break;

						case 'tooltip-plugin_col_title_width':	// Title / Name Column Width.

							$text .= __( 'WordPress does not define a column width for its Title column, which can create display issues when showing list tables with additional columns.', 'wpsso' ) . ' ';

							$text .= __( 'This option allows you to define a custom width for the Title column, to prevent these kinds of issues.', 'wpsso' ) . ' ';

							break;

						case 'tooltip-plugin_col_def_width':	// Default Width for Posts / Pages.

							$text .= __( 'A default column width for the admin Posts and Pages list table.', 'wpsso' ) . ' ';

							$text .= __( 'All columns should have a width defined, but some 3rd party plugins do not provide width information for their columns.', 'wpsso' ) . ' ';

							$text .= __( 'This option offers a way to set a generic width for all Posts and Pages list table columns.', 'wpsso' ) . ' ';

							break;

						/**
						 * Document Meta settings
						 */
						case 'tooltip-plugin_add_to':

							$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

							$text = sprintf( __( 'Add or remove the %s metabox from admin editing pages for posts, pages, custom post types, terms (categories and tags), and user profile pages.', 'wpsso' ), $metabox_title );

							break;

						case 'tooltip-plugin_wpseo_social_meta':

							$text = __( 'Import the Yoast SEO custom social meta text for Posts, Terms, and Users.', 'wpsso' ) . ' ';

							$text .= __( 'This option is checked by default if the Yoast SEO plugin is active or its settings are found in the database.', 'wpsso' );

							break;

						case 'tooltip-plugin_def_currency':

							$text = __( 'The default currency used for money related options (product price, job salary, etc.).', 'wpsso' );

							break;

						/**
						 * Product Attributes settings.
						 */
						case ( 0 === strpos( $msg_key, 'tooltip-plugin_product_attr_' ) ? true : false ):

							$attr_key = substr( $msg_key, 8 );	// Remove the 'tooltip-' prefix.

							$text .= __( 'Enter the name (aka label) of a product attribute that you have created in your e-commerce plugin (in WooCommerce, for example).', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The product attribute name allows %s to request the attribute value from your e-commerce plugin.', 'wpsso' ), $info[ 'short_pro' ] ) . ' ';

							$text .= sprintf( __( 'The default attribute name is "%s".', 'wpsso' ), $this->p->opt->get_defaults( $attr_key ) );

							break;

						/**
						 * Custom Fields settings
						 */
						case ( 0 === strpos( $msg_key, 'tooltip-plugin_cf_' ) ? true : false ):

							$cf_key = preg_replace( '/^tooltip-/', '', $msg_key );

							$cf_info = $this->get_cf_info( preg_replace( '/^tooltip-plugin_cf_/', '', $msg_key ) );

							$cf_md_key = empty( $this->p->cf[ 'opt' ][ 'cf_md_key' ][ $cf_key ] ) ?
								false : $this->p->cf[ 'opt' ][ 'cf_md_key' ][ $cf_key ];

							$cf_is_multi = empty( $this->p->cf[ 'opt' ][ 'cf_md_multi' ][ $cf_md_key ] ) ? false : true;

							$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

							if ( ! empty( $cf_info ) ) {	// Just in case.

								$text = sprintf( __( 'If your theme or another plugin provides a custom field for %1$s, you may enter its custom field name here.', 'wpsso' ), $cf_info[ 1 ] ) . ' ';

								$text .= sprintf( __( 'If a custom field matching that name is found, its value may be used for the "%1$s" option in the %2$s metabox.', 'wpsso' ), $cf_info[ 0 ], $metabox_title ) . ' ';

								if ( $cf_is_multi ) {
									$text .= sprintf( __( 'The "%1$s" option offers multiple input fields &mdash; the custom field value will be split on newline characters, and each line used for an individual input field.', 'wpsso' ), $cf_info[ 0 ] );
								}
							}

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_plugin', $text, $msg_key, $info );

							break;

					}	// End of tooltip-plugin switch.

				/**
				 * Publisher 'Facebook' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-fb_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-fb_publisher_url':

							$publisher_url_label = _x( $this->p->cf[ 'form' ][ 'social_accounts' ][ 'fb_publisher_url' ], 'option value', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">Facebook page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://www.facebook.com/business', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in Open Graph <em>article</em> meta tags and the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );

							break;

						case 'tooltip-fb_app_id':

							$fb_apps_url     = __( 'https://developers.facebook.com/apps', 'wpsso' );
							$fb_docs_reg_url = __( 'https://developers.facebook.com/docs/apps/register', 'wpsso' );
							$fb_insights_url = __( 'https://developers.facebook.com/docs/insights/', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">Facebook App ID for your website</a>, enter it here (see <a href="%2$s">Register and Configure an App</a> for help on creating a Facebook App ID).', 'wpsso' ), $fb_apps_url, $fb_docs_reg_url ) . ' ';

							$text .= '<br/><br/>';

							$text .= sprintf( __( 'The Facebook App ID will appear in webpage meta tags and is used by Facebook to allow access to <a href="%1$s">Facebook Insight</a> data for accounts associated with that App ID.', 'wpsso' ), $fb_insights_url );

							break;

						case 'tooltip-fb_admins':

							$fb_insights_url = __( 'https://developers.facebook.com/docs/insights/', 'wpsso' );
							$fb_username_url = __( 'https://www.facebook.com/settings?tab=account&section=username&view', 'wpsso' );

							$text .= sprintf( __( 'The Facebook admin usernames are used by Facebook to allow access to <a href="%1$s">Facebook Insight</a> data for your website. Note that these are Facebook user account names, not Facebook Page names. You may enter one or more Facebook usernames (comma delimited).', 'wpsso' ), $fb_insights_url );

							$text .= '<br/><br/>';

							$text .= __( 'When viewing your own Facebook wall, your username is located in the URL (for example, https://www.facebook.com/<strong>username</strong>). Enter only the usernames, not the URLs.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'You may update your Facebook username in the <a href="%1$s">Facebook General Account Settings</a>.', 'wpsso' ), $fb_username_url );

							break;

						case 'tooltip-fb_locale':

							$text = sprintf( __( 'Facebook does not support all WordPress locale values. If the Facebook debugger returns an error parsing the %1$s meta tag, you may have to choose an alternate Facebook language for that WordPress locale.', 'wpsso' ), '<code>og:locale</code>' );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_fb', $text, $msg_key, $info );

							break;

					}	// End of tooltip-fb switch.

				/**
				 * Publisher 'Google' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-g_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-g_site_verify':	// Google Website Verification ID.

							$text .= sprintf( __( 'To verify your website ownership with <a href="%1$s">Google\'s Search Console</a>, select the "Settings" left-side menu option in the Search Console, then "Ownership and verification", and choose the "HTML tag" method.', 'wpsso' ), 'https://search.google.com/search-console' ) . ' ';

							$text .= __( 'Enter the "google-site-verification" meta tag <code>content</code> value here (enter only the verification ID string, not the meta tag HTML).', 'wpsso' );

							break;
					}
				
				/**
				 * Publisher 'SEO' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-seo_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-seo_author_name':		// Author / Person Name Format.

							$text =  __( 'Select a name format for author meta tags and/or Schema Person markup.', 'wpsso' );

							break;

						case 'tooltip-seo_desc_max_len':	// Max. Description Meta Tag Length.

							$text = sprintf( __( 'The maximum length for the Google Search / SEO description value (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'seo_desc_max_len' ) ) . ' ';

							$text .= sprintf( __( 'The maximum length must be at least %d characters or more.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ 'seo_desc_len' ] );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_seo', $text, $msg_key, $info );

							break;

					}	// End of tooltip-google switch.

				/**
				 * Publisher 'Schema' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-schema_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-schema_knowledge_graph':	// Knowledge Graph for Home Page.

							$social_accounts_link = $this->p->util->get_admin_url( 'social-accounts',
								_x( 'Social Pages', 'lib file description', 'wpsso' ) );

							$text = __( 'Include WebSite, Organization and/or Person Schema markup in the front page for Google\'s <em>Knowledge Graph</em>.', 'wpsso' ) . '<br/><br/>';

							$text .= __( 'The WebSite markup includes the site name, alternate site name, site URL and search query URL.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Developers can hook the "%s" filter to modify the site search URL (or disable its addition by returning false).', 'wpsso' ), $this->p->lca . '_json_ld_search_url' ) . '<br/><br/>';

							$text .= sprintf( __( 'The Organization markup includes all URLs entered in the %s settings page.', 'wpsso' ), $social_accounts_link ) . '<br/><br/>';

							$text .= __( 'The Person markup includes all contact method URLs entered in the user\'s WordPress profile page.', 'wpsso' );

							break;

						case 'tooltip-schema_home_person_id':	// User for Person Social Profile.

							$text = __( 'Select an optional site owner for the <em>Knowledge Graph</em> Person markup included in the front page.', 'wpsso' ) . ' ';

							$text .= __( 'The Person markup includes all contact method URLs entered in the user\'s WordPress profile page.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The available Person list includes users with "%1$s" and/or "%2$s" roles.', 'wpsso' ),
								_x( 'Administrator', 'user role', 'wpsso' ), _x( 'Editor', 'user role', 'wpsso' ) );

							break;

						case 'tooltip-schema_logo_url':		// Organization Logo URL.

							$text = __( 'A URL for this organization\'s logo image that Google can use in its search results and <em>Knowledge Graph</em>.', 'wpsso' );

							break;

						case 'tooltip-schema_banner_url':	// Organization Banner URL.

							$text = __( 'A URL for this organization\'s banner image &mdash; <strong>measuring exactly 600x60px</strong> &mdash; that Google News can use for Schema Article content from this publisher.', 'wpsso' );

							break;

						case 'tooltip-schema_img_max':		// Maximum Images to Include.

							$text = __( 'The maximum number of images to include in Schema markup, including custom, featured, attached, and content images.', 'wpsso' ) . ' ';

							$text .= __( 'If you select "0", then no images will be included in the Schema markup (<strong>not recommended</strong>).', 'wpsso' );

							break;

						case 'tooltip-schema_img_size':		// Schema Image Size.

							$def_img_dims = $this->get_def_img_dims( 'schema_img' );

							$text = sprintf( __( 'The image dimensions used for Schema meta tags and JSON-LD markup (the default dimensions are %s).', 'wpsso' ), $def_img_dims );

							break;

						case 'tooltip-schema_article_img_size':	// Schema Article Image Size.

							$def_img_dims = $this->get_def_img_dims( 'schema_article_img' );

							$text = sprintf( __( 'The image dimensions used for Schema Article meta tags and JSON-LD markup (the default dimensions are %s).', 'wpsso' ), $def_img_dims ) . ' ';

							$text .= sprintf( __( 'The minimum image width required by Google is %dpx.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ 'schema_article_img_width' ] ). ' ';

							$text .= sprintf( __( 'If this image size is uncropped (default setting), the height value must be large enough to accommodate portrait / vertical images (default height is %dpx).', 'wpsso' ), $this->p->opt->get_defaults( 'schema_article_img_height' ) );

							break;

						case 'tooltip-schema_article_1_1_img_size':	// Schema Article AMP 1:1 Img Size.
						case 'tooltip-schema_article_4_3_img_size':	// Schema Article AMP 4:3 Img Size.
						case 'tooltip-schema_article_16_9_img_size':	// Schema Article AMP 16:9 Img Size.
		
							$opt_pre = preg_replace( '/^tooltip-(schema_article_.*_img)_size$/', '$1', $msg_key );

							$opt_ratio = preg_replace( '/^schema_article_([0-9]+)_([0-9]+)_img$/', '$1:$2', $opt_pre );

							$def_img_dims = $this->get_def_img_dims( $opt_pre );

							$text = sprintf( __( 'The AMP %1$s image dimensions for Schema Article JSON-LD markup (the default dimensions are %2$s).', 'wpsso' ), $opt_ratio, $def_img_dims ) . ' ';

							$text .= sprintf( __( 'The minimum image width required by Google is %dpx.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ $opt_pre . '_width' ] ). ' ';

							break;

						case 'tooltip-schema_desc_max_len':		// Max. Schema Description Length.

							$text = sprintf( __( 'The maximum length for the Schema description value (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'schema_desc_max_len' ) ) . ' ';

							$text .= sprintf( __( 'The maximum length must be at least %d characters or more.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ 'schema_desc_len' ] );

							break;

						case 'tooltip-schema_type_for_home_page':	// Type for Page Homepage.

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_home_page' );

							$text = sprintf( __( 'Select the %1$s type for a static front page.', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_home_posts':	// Type for Posts Homepage.

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_home_posts' );

							$text = sprintf( __( 'Select the %1$s type for a blog (non-static) front page.', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_user_page':	// Type for User / Author.

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_user_page' );

							$text = sprintf( __( 'Select the %1$s type for user / author pages.', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_search_page':	// Type for Search Results.

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_search_page' );

							$text = sprintf( __( 'Select the %1$s type for search results pages.', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_archive_page':	// Type for Other Archive.

							$def_type = $this->p->opt->get_defaults( 'schema_type_for_archive_page' );

							$text = sprintf( __( 'Select the %1$s type for other archive pages (example: date-based archive pages).', 'wpsso' ), 'Schema' ) . ' ';

							$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

							break;

						case 'tooltip-schema_type_for_ptn':	// Type by Post Type.

							$text = sprintf( __( 'Select the %1$s type for each WordPress post type.', 'wpsso' ), 'Schema' );

							break;

						case 'tooltip-schema_type_for_ttn':	// Type by Taxonomy.

							$text = __( 'Select the Schema type for each WordPress taxonomy.', 'wpsso' );


							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_schema', $text, $msg_key, $info );

							break;

					}	// End of tooltip-google switch.

				/**
				 * Publisher 'Pinterest' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-p_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-p_site_verify':	// Pinterest Website Verification ID.

							$text = sprintf( __( 'To <a href="%s">claim your website with Pinterest</a>: Edit your account settings on Pinterest, select the "Claim" section, enter your website URL, then click the "Claim" button.', 'wpsso' ), 'https://help.pinterest.com/en/business/article/claim-your-website' ) . ' ';

							$text .= __( 'Choose "Add HTML tag" and enter the "p:domain_verify" meta tag <code>content</code> value here (enter only the verification ID string, not the meta tag HTML).', 'wpsso' );

							break;

						case 'tooltip-p_publisher_url':

							$publisher_url_label = _x( $this->p->cf[ 'form' ][ 'social_accounts' ][ 'p_publisher_url' ], 'option value', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">Pinterest page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://business.pinterest.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );

							break;

						case 'tooltip-p_add_nopin_header_img_tag':	// Add "nopin" to Site Header Image.

							$text = __( 'Add a "nopin" attribute to the site header and Gravatar images to prevent the Pin It browser button from suggesting those images.', 'wpsso' );

							break;

						case 'tooltip-p_add_nopin_media_img_tag':	// Add "nopin" to WordPress Media.

							$add_img_html_label = _x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' );

							$text = __( 'Add a "nopin" attribute to images from the WordPress Media Library to prevent the Pin It browser button from suggesting those images.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'If this option is enabled, you should also enable the "%s" option to provide an image for the Pin It browser button.', 'wpsso' ), $add_img_html_label );

							break;

						case 'tooltip-p_add_img_html':			// Add Hidden Image for Pinterest.

							$text = __( 'Add an extra hidden image in the WordPress post / page content for the Pinterest Pin It browser button.', 'wpsso' );

							break;

						case 'tooltip-p_img_desc_max_len':		// Maximum Image Description Length.

							$text = sprintf( __( 'The maximum length used for the Pinterest Pin It browser button description (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'p_img_desc_max_len' ) ) . ' ';

							break;

						case 'tooltip-p_img_size':			// Pinterest Pin It Image Size.

							$def_img_dims = $this->get_def_img_dims( 'p_img' );

							$text = sprintf( __( 'The dimensions used for the Pinterest Pin It browser button image (the default dimensions are %s).', 'wpsso' ), $def_img_dims );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_p', $text, $msg_key, $info );

							break;

					}	// End of tooltip-p switch.

				/**
				 * Publisher 'Twitter' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-tc_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-tc_site':

							$publisher_url_label = _x( $this->p->cf[ 'form' ][ 'social_accounts' ][ 'tc_site' ], 'option value', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">Twitter @username for your business</a> (not your personal Twitter @username), you may enter its name here.', 'wpsso' ), __( 'https://business.twitter.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in in Twitter Card meta tags and the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );

							break;

						case 'tooltip-tc_desc_max_len':

							$text = sprintf( __( 'The maximum length for the Twitter Card description value (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'tc_desc_max_len' ) ) . ' ';

							$text .= sprintf( __( 'The maximum length must be at least %d characters or more.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ 'tc_desc_len' ] );

							break;

						case 'tooltip-tc_type_singular':

							$text = 'The Twitter Card type for posts / pages with a custom, featured, and/or attached image.';

							break;

						case 'tooltip-tc_type_default':

							$text = 'The Twitter Card type for all other images (default, image from content text, etc).';

							break;

						case 'tooltip-tc_sum_img_size':

							$def_img_dims = $this->get_def_img_dims( 'tc_sum_img' );

							$text = sprintf( __( 'The image dimensions for the <a href="%1$s">Summary Card</a> (should be at least %2$s and less than %3$s).', 'wpsso' ), 'https://dev.twitter.com/docs/cards/types/summary-card', '120x120px', __( '1MB', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The default image dimensions are %s.', 'wpsso' ), $def_img_dims );

							break;

						case 'tooltip-tc_lrg_img_size':

							$def_img_dims = $this->get_def_img_dims( 'tc_lrg_img' );

							$text = sprintf( __( 'The image dimensions for the <a href="%1$s">Large Image Summary Card</a> (must be larger than %2$s and less than %3$s).', 'wpsso' ), 'https://dev.twitter.com/docs/cards/large-image-summary-card', '280x150px', __( '1MB', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The default image dimensions are %s.', 'wpsso' ), $def_img_dims );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_tc', $text, $msg_key, $info );

							break;

					}	// End of tooltip-tc switch.

				/**
				 * Publisher 'Instagram' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-instagram_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-instagram_publisher_url':

							$publisher_url_label = _x( $this->p->cf[ 'form' ][ 'social_accounts' ][ 'instagram_publisher_url' ], 'option value', 'wpsso' );

							$text = sprintf( __( 'If you have an <a href="%1$s">Intagram profile for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://business.instagram.com/getting-started', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_instagram', $text, $msg_key, $info );

							break;

					}	// End of tooltip-instagram switch.

				/**
				 * Publisher 'LinkedIn' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-linkedin_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-linkedin_publisher_url':

							$publisher_url_label = _x( $this->p->cf[ 'form' ][ 'social_accounts' ][ 'linkedin_publisher_url' ], 'option value', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">LinkedIn page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://business.linkedin.com/marketing-solutions/linkedin-pages', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_linkedin', $text, $msg_key, $info );

							break;

					}	// End of tooltip-linkedin switch.

				/**
				 * Publisher 'Myspace' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-myspace_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-myspace_publisher_url':

							$publisher_url_label = _x( $this->p->cf[ 'form' ][ 'social_accounts' ][ 'myspace_publisher_url' ], 'option value', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">Myspace page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'http://myspace.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_myspace', $text, $msg_key, $info );

							break;

						}	// End of tooltip-myspace switch.

				/**
				 * Publisher 'Wikipedia' settings.
				 */
				} elseif ( strpos( $msg_key, 'tooltip-wikipedia_' ) === 0 ) {

					switch ( $msg_key ) {

						case 'tooltip-wikipedia_publisher_url':

							$publisher_url_label = _x( $this->p->cf[ 'form' ][ 'social_accounts' ][ 'wikipedia_publisher_url' ], 'option value', 'wpsso' );

							$text = sprintf( __( 'If you have a <a href="%1$s">Wikipedia page for your organization</a>, you may enter its URL here.', 'wpsso' ), __( 'https://en.wikipedia.org/wiki/Wikipedia:FAQ/Organizations', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip_wikipedia', $text, $msg_key, $info );

							break;

					}	// End of tooltip-wikipedia switch.

				/**
				 * All other settings.
				 */
				} else {

					switch ( $msg_key ) {

						case 'tooltip-custom-cm-field-id':

							$text .= '<strong>' . sprintf( __( 'You should not modify the <em>%1$s</em> column unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) ) . '</strong> ';

							$text .= sprintf( __( 'As an example, to match the <em>%1$s</em> of a theme or other plugin, you might change "%2$s" to "%3$s" or some other value.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ), 'facebook', 'fb' );

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

						case 'tooltip-thumb_img_size':

							$text = sprintf( __( 'The image dimensions used for the Schema "%1$s" property and the "%2$s" tag (the default dimensions are %3$s).', 'wpsso' ), 'thumbnailUrl', 'meta name thumbnail', $this->get_def_img_dims( 'thumb_img' ) );

							break;

						default:

							$text = apply_filters( $lca . '_messages_tooltip', $text, $msg_key, $info );

							break;

					} 	// End of all other settings switch.

				}	// End of tooltips.

			/**
			 * Misc informational messages.
			 */
			} elseif ( strpos( $msg_key, 'info-' ) === 0 ) {

				if ( strpos( $msg_key, 'info-meta-' ) === 0 ) {

					switch ( $msg_key ) {

						/**
						 * Validate tab.
						 */
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

						case 'info-meta-validate-google-testing-tool':

							$text = '<p class="top">';

							$text .= __( 'Verify the webpage structured data markup.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-google-rich-results':

							$text = '<p class="top">';

							$text .= __( 'Check the webpage structured data markup for Google Rich Result types (Job posting, Recipe, etc.).', 'wpsso' );

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

						case 'info-meta-validate-twitter':

							$text = '<p class="top">';

							$text .= __( 'The Twitter Card validator does not currently accept query arguments &mdash; paste the following URL in the Twitter Card validator "Card URL" input field:', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-amp':

							$text = '<p class="top">';

							$text .= __( 'Validate the HTML syntax and conformance of the AMP webpage.', 'wpsso' );

							$text .= '</p>';

						 	break;

						case 'info-meta-validate-w3c':

							$text = '<p class="top">';

							$text .= __( 'Validate the HTML syntax and HTML 5 conformance of your meta tags and theme templates.', 'wpsso' ) . ' ';

							$text .= __( 'Validating your theme templates is important - theme templates with serious errors can prevent social and search crawlers from understanding the webpage structure.', 'wpsso' ) . ' ';

							$text .= '</p>';

						 	break;

						/**
						 * Called at the bottom of the Document SSO > Validate tab.
						 *
						 * Return an empty string if there are no special status messages. 
						 */
						case 'info-meta-validate-info':

							if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

								$text .= '<p class="status-msg left">* ';

								$text .= __( 'Schema markup is disabled.', 'wpsso' );

								$text .= '</p>';

							} elseif ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

								$link = $this->p->util->get_admin_url( 'addons#wpssojson',
									$this->p->cf[ 'plugin' ][ 'wpssojson' ][ 'short' ] );

								$text .= '<p class="status-msg left">* ';

								$text .= sprintf( __( 'Activate the %s add-on for Google structured data markup.', 'wpsso' ), $link );

								$text .= '</p>';
							}

							if ( ! function_exists( 'amp_get_permalink' ) ) {

								$text .= '<p class="status-msg left">** ';

								$text .= __( 'Activate an AMP plugin to create and validate AMP pages.', 'wpsso' );

								$text .= '</p>';
							}

						 	break;

						case 'info-meta-social-preview':

							$fb_img_dims = '600x315px';

						 	$text = '<p class="status-msg">';

							$text .= sprintf( __( 'The example image container uses the minimum recommended Facebook image dimensions of %s.', 'wpsso' ), $fb_img_dims );

							$text .= '</p>';

						 	break;

						case 'info-meta-oembed-html':

						 	$text = '<p class="status-msg">';

							$text .= sprintf( __( 'oEmbed HTML is provided by the WordPress or theme <code>%s</code> template, which may not use all available oEmbed data.', 'wpsso' ), 'embed-content' );

							$text .= '</p>';

						 	break;

					}	// End of info-meta switch.

				} else {

					switch ( $msg_key ) {

						case 'info-priority-media':

							$upload_url = get_admin_url( $blog_id = null, 'upload.php' );

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'Edit images in the <a href="%s">WordPress Media Library</a> to select a preferred cropping area and optimize image SEO.', 'wpsso' ), $upload_url );

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-plugin-tid':	// Displayed in the Licenses settings page.

							$um_info = $this->p->cf[ 'plugin' ][ 'wpssoum' ];

							$um_addon_link = $this->p->util->get_admin_url( 'addons#wpssoum', $um_info[ 'name' ] );

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'After purchasing the %1$s plugin or any complementary %2$s add-on, you\'ll receive an email with a unique Authentication ID for the plugin or add-on you purchased.', 'wpsso' ), $info[ 'short_pro' ], $pro_transl ) . ' ';

							$text .=  __( 'Enter the Authentication ID in the option field corresponding to the plugin or add-on you purchased.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'Don\'t forget that the %1$s add-on must be installed and active to check for %2$s version updates.', 'wpsso' ), $um_addon_link, $pro_transl ) . ' ;-)';

							$text .= '</p>';


							$text .= '</blockquote>';

							break;

						case 'info-plugin-tid-network':	// Displayed in the Network Licenses settings page.

							$um_info = $this->p->cf[ 'plugin' ][ 'wpssoum' ];

							$licenses_page_link = $this->p->util->get_admin_url( 'licenses',
								_x( 'Premium Licenses', 'lib file description', 'wpsso' ) );

							$text = '<blockquote class="top-info">';

							$text .= '<p>' . sprintf( __( 'After purchasing the %1$s plugin or any complementary %2$s add-on, you\'ll receive an email with a unique Authentication ID for the plugin or add-on you purchased.', 'wpsso' ), $info[ 'short_pro' ], $pro_transl ) . ' ';

							$text .= sprintf( __( 'You may enter each Authentication ID on this page <em>to define a value for all sites within the network</em> &mdash; or enter Authentication IDs individually on each site\'s %1$s settings page.', 'wpsso' ), $licenses_page_link ) . '</p>';

							$text.= '<p>' . sprintf( __( 'If you enter Authentication IDs in this network settings page, <em>please make sure you have purchased enough licenses for all sites within the network</em> &mdash; for example, to license a %1$s add-on for 10 sites, you would need an Authentication ID from a 10 license pack purchase (or better) of that %1$s add-on.', 'wpsso' ), $pro_transl ) . '</p>';

							$text .= '<p>' . sprintf( __( '<strong>WordPress uses the default blog to install and/or update plugins from the Network Admin interface</strong> &mdash; to update the %1$s and its %2$s add-ons, please make sure the %3$s add-on is active on the default blog, and the default blog is licensed.', 'wpsso' ), $info[ 'name_pro' ], $pro_transl, $um_info[ 'name' ] ) . '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-cf-attr':

							$text .= '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'These options allow you to customize the custom field names (aka metadata names) that %s can use to get additional information about your content.', 'wpsso' ), $info[ 'short_pro' ] ) . ' ';

							$text .= '</p><p>';

							$text .= '<center>';

							$text .= '<strong>' . __( 'Do not enter custom field values here &ndash; these options are for custom field names only.', 'wpsso' ) . '</strong><br/>';

							$text .= __( 'Use the following custom field names when creating custom fields for your posts, pages, and custom post types.', 'wpsso' );

							$text .= '</center>';

							$text .= '</p>';

							if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {


								$text .= '<p>';

								$text .= __( 'An active WooCommerce plugin has been detected.', 'wpsso' ) . ' ';

								$text .= __( 'Please note that custom product attributes from WooCommerce have precedence over custom field values.', 'wpsso' ) . ' ';

								$text .= sprintf( __( 'Refer to the <a href="%s">WooCommerce integration notes</a> for information on setting up product attributes and custom fields.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/installation/integration/woocommerce-integration/' );

								$text .= '</p>';
							}

							$text .= '</blockquote>';

							break;

						case 'info-product-attr':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'These options allow you to customize the product attribute names (aka attribute labels) that %s uses to request additional product information from your e-commerce plugin.', 'wpsso' ), $info[ 'short_pro' ] ) . ' ';

							$text .= __( 'These are the product attribute names that you can create in your e-commerce plugin and not their values.', 'wpsso' ) . ' ';

							$text .= '</p><p>';

							$text .= '<center>';

							$text .= '<strong>' . __( 'Do not enter product attribute values here &ndash; these options are for product attribute names only.', 'wpsso' ) . '</strong><br/>';

							$text .= __( 'You can create the following product attribute names and choose their corresponding values in your e-commerce plugin.', 'wpsso' );

							$text .= '</center>';

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-cm':

							// translators: Please ignore - translation uses a different text domain.
							$contact_info_transl = __( 'Contact Info' );

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'These options allow you to customize the list of contact fields shown in the %1$s section of <a href="%2$s">the user profile page</a>.', 'wpsso' ), $contact_info_transl, get_admin_url( $blog_id = null, 'profile.php' ) ) . ' ';

							$text .= sprintf( __( '%1$s uses the Facebook and Twitter contact field values in its meta tags and Schema markup.', 'wpsso' ), $info[ 'short' ] ) . ' ';

							$text .= '<strong>' . sprintf( __( 'You should not modify the <em>%1$s</em> column unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) ) . '</strong> ';

							$text .= sprintf( __( 'The <em>%1$s</em> column on the other hand is for display purposes only and can be changed as you wish.', 'wpsso' ), _x( 'Contact Field Label', 'column title', 'wpsso' ) ) . ' ';

							$text .= '</p><p>';

							$text .= '<center>';

							$text .= '<strong>' . __( 'Do not enter your contact information here &ndash; these options are for contact field ids and labels only.', 'wpsso' ) . '</strong><br/>';

							$text .= sprintf( __( 'Enter your personal contact information in <a href="%1$s">the user profile page</a>.', 'wpsso' ), get_admin_url( $blog_id = null, 'profile.php' ) );

							$text .= '</center>';

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-head_tags':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( '%s adds the following Facebook, Open Graph, Twitter, Schema, Pinterest, and SEO HTML tags to the <code>&lt;head&gt;</code> section of your webpages.', 'wpsso' ), $info[ 'short' ] ) . ' ';

							$text .= __( 'If your theme or another plugin already creates one or more of these HTML tags, you can uncheck them here to prevent duplicates from being added.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'As an example, the "%1$s" SEO HTML tag is automatically unchecked if a <em>known</em> SEO plugin is detected, and the "%2$s" HTML tag is unchecked by default (themes often include this HTML tag in their header template).', 'wpsso' ), 'meta name description', 'link rel canonical' );

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						case 'info-image-sizes':

							$text = '<blockquote class="top-info">';

							$text .= '<p>';

							$text .= sprintf( __( 'WordPress and %s create resized image files based on image size dimensions and crop settings.', 'wpsso' ), $info[ 'short' ] ) . ' ';

							$text .= __( 'Image sizes using the same dimensions and crop settings will create only a single image file.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'The default dimensions and crop settings from %1$s create only %2$s resized image files (%3$s if an AMP plugin is active) per original full size image.', 'wpsso' ), $info[ 'short' ], __( 'two', 'wpsso' ), __( 'five', 'wpsso' ) );

							$text .= '</p>';

							$text .= '</blockquote>';

							break;

						default:

							$text = apply_filters( $lca . '_messages_info', $text, $msg_key, $info );

							break;

					}	// End of info switch.
				}
			/**
			 * Misc pro messages
			 */
			} elseif ( strpos( $msg_key, 'pro-' ) === 0 ) {

				switch ( $msg_key ) {

					case 'pro-feature-msg':

						$text = '<p class="pro-feature-msg">';

						$text .= empty( $url[ 'purchase' ] ) ? '' : '<a href="' . $url[ 'purchase' ] . '">';

						if ( $lca === $this->p->lca ) {
							$text .= sprintf( __( 'Purchase the %1$s plugin to upgrade and get the following features.',
								'wpsso' ), $info[ 'short_pro' ] );
						} else {
							$text .= sprintf( __( 'Purchase the %1$s add-on to upgrade and get the following features.',
								'wpsso' ), $info[ 'short_pro' ] );
						}

						$text .= empty( $url[ 'purchase' ] ) ? '' : '</a>';

						$text .= '</p>';

						break;

					case 'pro-ecom-product-msg':

						if ( empty( $this->p->avail[ 'ecom' ][ 'any' ] ) ) {	// Just in case.

							$text = '';

						} else {

							$text = '<p class="pro-feature-msg">';

							if ( WpssoAdmin::$pkg[ $this->p->lca ][ 'pp' ] ) {

								$text .= __( 'An e-commerce plugin is active &ndash; product attributes may be managed by the e-commerce plugin.', 'wpsso' );

							} else {

								$text .= empty( $url[ 'purchase' ] ) ? '' : '<a href="' . $url[ 'purchase' ] . '">';

								$text .= sprintf( __( 'An e-commerce plugin is active &ndash; product attributes may be retrieved by the %s plugin.', 'wpsso' ), $info[ 'short_pro' ] );

								$text .= empty( $url[ 'purchase' ] ) ? '' : '</a>';
							}

							$text .= '</p>';
						}

						break;

					case 'pro-purchase-link':

						if ( empty( $info[ 'ext' ] ) ) {	// Nothing to do.
							break;
						}

						if ( WpssoAdmin::$pkg[ $info[ 'ext' ] ][ 'pp' ] ) {

							$text = _x( 'Get More Licenses', 'plugin action link', 'wpsso' );

						} elseif ( $info[ 'ext' ] === $lca ) {
							$text = sprintf( _x( 'Purchase %s Plugin', 'plugin action link', 'wpsso' ), $pro_transl );
						} else {
							$text = sprintf( _x( 'Purchase %s Add-on', 'plugin action link', 'wpsso' ), $pro_transl );
						}

						if ( ! empty( $info[ 'url' ] ) ) {
							$text = '<a href="' . $info[ 'url' ] . '"' . ( empty( $info[ 'tabindex' ] ) ? '' :
								' tabindex="' . $info[ 'tabindex' ] . '"' ) . '>' .  $text . '</a>';
						}

						break;

					default:

						$text = apply_filters( $lca . '_messages_pro', $text, $msg_key, $info );

						break;
				}
			/**
			 * Misc notice messages
			 */
			} elseif ( strpos( $msg_key, 'notice-' ) === 0 ) {

				switch ( $msg_key ) {

					case 'notice-image-rejected':

						if ( WpssoWpMeta::is_meta_page() ) {

							$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

							$metabox_tab = _x( 'Priority Media', 'metabox tab', 'wpsso' );

							$text .= sprintf( __( 'A larger custom image can be selected in the %1$s metabox under the %2$s tab.',
								'wpsso' ), $metabox_title, $metabox_tab );
						}

						/**
						 * WpssoMedia->img_size_within_limits() uses show_adjust_img_opts = false for
						 * images with an incorrect aspect ratio.
						 */
						if ( ! isset( $info[ 'show_adjust_img_opts' ] ) || ! empty( $info[ 'show_adjust_img_opts' ] ) ) {

							if ( current_user_can( 'manage_options' ) ) {

								/**
								 * Signal that additional md5() matching sections should be removed from the notice messages.
								 */
								$text .= '<!-- show-once -->';

								$text .= '<p style="margin-left:0;"><em>' . __( 'Additional information shown only to users with Administrative privileges:', 'wpsso' ) . '</em></p>';

								$text .= '<ul>';

								$img_sizes_page_link = $this->p->util->get_admin_url( 'image-sizes', _x( 'Image Sizes', 'lib file description', 'wpsso' ) );

								$text .= '<li>' . sprintf( __( 'Update image size dimensions in the %s settings page.', 'wpsso' ), $img_sizes_page_link ) . '</li>';

								if ( empty( $this->p->options[ 'plugin_upscale_images' ] ) ) {

									$upscale_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', _x( 'Upscale Media Library Images', 'option label', 'wpsso' ) );

									$text .= '<li>' . sprintf( __( 'Enable the %s option.', 'wpsso' ), $upscale_option_link ) . '</li>';
								}

								$percent_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', _x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ) );

								$text .= '<li>' . sprintf( __( 'Increase the %s option value.', 'wpsso' ), $percent_option_link ) . '</li>';

								if ( ! empty( $this->p->options[ 'plugin_check_img_dims' ] ) ) {

									$img_dim_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', _x( 'Enforce Image Size Checks', 'option label', 'wpsso' ) );

									$text .= '<li>' . sprintf( __( 'Disable the %s option (not recommended).', 'wpsso' ), $img_dim_option_link ) . '</li>';
								}

								$text .= '</ul>';

								$text .= '<!-- /show-once -->';
							}
						}

						break;

					case 'notice-missing-og-image':

						$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'An Open Graph image meta tag could not be generated from this webpage content or its custom %s metabox settings. Facebook <em>requires at least one image meta tag</em> to render shared content correctly.', 'wpsso' ), $metabox_title );

						break;

					case 'notice-missing-og-description':

						$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'An Open Graph description meta tag could not be generated from this webpage content or its custom %s metabox settings. Facebook <em>requires a description meta tag</em> to render shared content correctly.', 'wpsso' ), $metabox_title );

						break;

					case 'notice-missing-schema-image':

						$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'A Schema "image" property could not be generated from this webpage content or its custom %s metabox settings. Google <em>requires at least one "image" property</em> for this Schema type.', 'wpsso' ), $metabox_title );

						break;

					case 'notice-content-filters-disabled':

						$filters_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content',
							_x( 'Use WordPress Content Filters', 'option label', 'wpsso' ) );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %1$s advanced option is currently disabled.', 'wpsso' ), $filters_option_link ) . '</b> ';

						$text .= sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions and detect additional images and/or embedded videos provided by shortcodes.', 'wpsso' ), $info[ 'name' ] );

						$text .= '</p><p>';

						$text .= '<b>' . __( 'Many themes and plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ) . '</b> ';

						$text .= __( 'If you use shortcodes in your content text, this option should be enabled &mdash; IF YOU EXPERIENCE WEBPAGE LAYOUT OR PERFORMANCE ISSUES AFTER ENABLING THIS OPTION, you should determine which theme or plugin is filtering the content incorrectly, and report the problem to its author(s).', 'wpsso' );

						$text .= '</p>';

						break;

					case 'notice-wp-config-php-variable-home':

						$const_html = '<code>WP_HOME</code>';

						$cfg_php_html = '<code>wp-config.php</code>';

						$text = sprintf( __( 'The %1$s constant definition in your %2$s file contains a variable.', 'wpsso' ), $const_html, $cfg_php_html ) . ' ';

						$text .= sprintf( __( 'WordPress uses the %1$s constant to provide a single unique canonical URL for each webpage and Media Library content.', 'wpsso' ), $const_html ) . ' ';

						$text .= sprintf( __( 'A changing %1$s value will create different canonical URLs in your webpages, leading to duplicate content penalties from Google, incorrect social share counts, possible broken media links, mixed content issues, and SSL certificate errors.', 'wpsso' ), $const_html ) . ' ';

						$text .= sprintf( __( 'Please update your %1$s file and provide a fixed, non-variable value for the %2$s constant.', 'wpsso' ), $cfg_php_html, $const_html );

						break;

					case 'notice-header-tmpl-no-head-attr':

						$filter_name = 'head_attributes';

						$tag_code = '<code>&lt;head&gt;</code>';

						$php_code  = '<pre><code>&lt;head &lt;?php do_action( &#39;add_head_attributes&#39; ); ?&gt;&gt;</code></pre>';

						$action_url  = wp_nonce_url( $this->p->util->get_admin_url( '?' . $this->p->lca . '-action=modify_tmpl_head_attributes' ),
							WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

						$text = '<p class="top">';

						$text .= __( 'At least one of your theme header templates does not offer a recognized way to modify the head HTML tag attributes.', 'wpsso' ) . ' ';
						
						$text .= __( 'Adding the document Schema item type to the head HTML tag attributes is important for Pinterest.', 'wpsso' ) . ' ';

						if ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

							$text .= __( 'It is also important for Google in cases where Schema markup describing the content is not available in the webpage (for example, when the complementary WPSSO JSON add-on is not active).', 'wpsso' ) . ' ';
						}

						$text .= '</p><p>';

						$text .= sprintf( __( 'The %1$s HTML tag in your header template(s) should include a function, action, or filter for its attributes.', 'wpsso' ), $tag_code ) . ' ';

						$text .= sprintf( __( '%1$s can update your header template(s) automatically and change the existing %2$s HTML tag to:', 'wpsso' ), $info[ 'short' ], $tag_code );

						$text .= '</p>' . $php_code . '<p>';

						$text .= sprintf( __( '<b><a href="%1$s">Click here to update header template(s) automatically</a></b> (recommended) or update the template(s) manually.', 'wpsso' ), $action_url );

						$text .= '</p>';

						break;

					case 'notice-pro-not-installed':

						$licenses_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Premium Licenses', 'lib file description', 'wpsso' ) );

						$text = sprintf( __( 'An Authentication ID has been entered for %1$s but the plugin is not installed &mdash; you can install and activate the %2$s version from the %3$s settings page.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $pro_transl, $licenses_page_link ) . ' ;-)';

						break;

					case 'notice-pro-not-updated':

						$licenses_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Premium Licenses', 'lib file description', 'wpsso' ) );

						$text = sprintf( __( 'An Authentication ID has been entered for %1$s in the %2$s settings page but the %3$s version is not installed &mdash; don\'t forget to update the plugin to install the latest %3$s version.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $licenses_page_link, $pro_transl ) . ' ;-)';

						break;

					case 'notice-um-add-on-required':
					case 'notice-um-activate-add-on':

						$um_info = $this->p->cf[ 'plugin' ][ 'wpssoum' ];

						$addons_page_link = $this->p->util->get_admin_url( 'addons#wpssoum',
							_x( 'Complementary Add-ons', 'lib file description', 'wpsso' ) );

						$licenses_page_link = $this->p->util->get_admin_url( 'licenses',
							_x( 'Premium Licenses', 'lib file description', 'wpsso' ) );

						// translators: Please ignore - translation uses a different text domain.
						$plugins_page_link = '<a href="' . get_admin_url( $blog_id = null, 'plugins.php' ) . '">' . __( 'Plugins' ) . '</a>';

						$text = '<p>';

						$text .= '<b>' . sprintf( __( 'At least one Authentication ID has been entered in the %1$s settings page,<br/>but the %2$s add-on is not active.', 'wpsso' ), $licenses_page_link, $um_info[ 'name' ] ) . '</b> ';

						$text .= '</p><p>';

						$text .= sprintf( __( 'This complementary add-on is required to update and enable the %1$s plugin and its %2$s add-ons.', 'wpsso' ), $info[ 'name_pro' ], $pro_transl ) . ' ';

						if ( $msg_key === 'notice-um-add-on-required' ) {

							$text .= sprintf( __( 'Install and activate the %1$s add-on from the %2$s settings page.', 'wpsso' ),
								$um_info[ 'name' ], $addons_page_link ) . ' ';

						} else {

							$text .= sprintf( __( 'The %1$s add-on can be activated from the WordPress %2$s page &mdash; please activate this complementary add-on now.', 'wpsso' ), $um_info[ 'name' ], $plugins_page_link ) . ' ';
						}

						$text .= sprintf( __( 'When the %1$s add-on is active, one or more %2$s version updates may be available for your licensed plugin and its add-on(s).', 'wpsso' ), $um_info[ 'name' ], $pro_transl );

						$text .= '</p>';

						break;

					case 'notice-um-version-recommended':

						$um_info = $this->p->cf[ 'plugin' ][ 'wpssoum' ];

						$um_version = isset( $um_info[ 'version' ] ) ? $um_info[ 'version' ] : 'unknown';

						$um_rec_version = WpssoConfig::$cf[ 'um' ][ 'rec_version' ];

						$um_check_updates_transl = _x( 'Check for Updates', 'submit button', 'wpsso' );

						$tools_settings_page_link = $this->p->util->get_admin_url( 'tools',
							_x( 'Tools and Actions', 'lib file description', 'wpsso' ) );

						$wp_updates_page_link = '<a href="' . admin_url( 'update-core.php' ) . '">' . 
							// translators: Please ignore - translation uses a different text domain.
							__( 'Dashboard' ) . ' &gt; ' . 
							// translators: Please ignore - translation uses a different text domain.
							__( 'Updates' ) . '</a>';

						$text = sprintf( __( '%1$s version %2$s requires the use of %3$s version %4$s or newer (version %5$s is currently installed).', 'wpsso' ), $info[ 'name_pro' ], $info[ 'version' ], $um_info[ 'short' ], $um_rec_version, $um_version ) . ' ';

						$text .= sprintf( __( 'If an update for %1$s is not available under the WordPress %2$s page, use the <em>%3$s</em> button in the %4$s settings page to force an immediate refresh of the plugin update information.', 'wpsso' ), $um_info[ 'name' ], $wp_updates_page_link, $um_check_updates_transl, $tools_settings_page_link );

						break;

					case 'notice-recommend-version':

						$text = sprintf( __( 'You are using %1$s version %2$s &mdash; <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.', 'wpsso' ), $info[ 'app_label' ], $info[ 'app_version' ], $info[ 'version_url' ] ) . ' ';

						$text .= sprintf( __( 'If possible, please update to the latest %1$s stable release (or at least version %2$s).', 'wpsso' ), $info[ 'app_label' ], $info[ 'rec_version' ] );

						break;

					default:

						$text = apply_filters( $lca . '_messages_notice', $text, $msg_key, $info );

						break;
			}
			/**
			 * Misc sidebox messages
			 */
			} elseif ( strpos( $msg_key, 'column-' ) === 0 ) {

				switch ( $msg_key ) {

					case 'column-purchase-pro':

						$metabox_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = '<p>' . sprintf( __( '<strong>%s includes:</strong>', 'wpsso' ), $info[ 'short_pro' ] ) . '</p>';

						$text .= '<ul>';

						$text .= '<li>' . sprintf( __( 'Additional options in the %s metabox.', 'wpsso' ), $metabox_title ) . '</li>';

						$text .= '<li>' . __( 'Integration with 3rd party plugins and service APIs.', 'wpsso' ) . '</li>';

						$text .= '<li>' . __( 'Advanced plugin settings.', 'wpsso' ) . '</li>';

						$text .= '<li>' . __( 'Access to development and release candidate updates.', 'wpsso' ) . '</li>';

						$text .= '<li>' . __( 'Premium plugin support.', 'wpsso' ) . '</li>';

						$text .= '</ul>';

						$text .= '<p>' . sprintf( __( '<strong>%1$s licenses never expire</strong> &mdash; you may receive unlimited / lifetime updates and support for each licensed WordPress Site Address.', 'wpsso' ), $pro_transl ) . ' ';

						$text .= __( 'How great is that!?', 'wpsso' ) . ' :-)</p>';


						$text .= '<p><strong>' . sprintf( __( 'Purchase %1$s quickly and easily with PayPal!', 'wpsso' ), $info[ 'short_pro' ] ) . '</strong></p>';

						break;

					case 'column-help-support':

						$text = '<p>';

						$text .= sprintf( __( '<strong>Development of %1$s is driven by user requests</strong> &mdash; we welcome all your comments and suggestions.', 'wpsso' ), $info[ 'short' ] ) . ' ;-)';

						$text .= '</p>';

						break;

					case 'column-rate-review':

						$text = '<p style="text-align:center;">';

						$text .= __( 'Great ratings are an excellent way to encourage your plugin developers.', 'wpsso' );

						$text .= '</p><p style="text-align:center;">';

						$text .= '<span class="' . $lca . '-rate-heart"></span> ';

						$text .= '</p><p style="text-align:center;">';

						$text .= __( 'Please take a moment to support the continued development of your favorite plugins by quickly rating those you value.', 'wpsso' );

						$text .= '</p>';

						break;

					default:

						$text = apply_filters( $lca . '_messages_side', $text, $msg_key, $info );

						break;
				}
			} else {
				$text = apply_filters( $lca . '_messages', $text, $msg_key, $info );
			}

			if ( is_array( $info ) && ! empty( $info[ 'is_locale' ] ) ) {

				// translators: %s is the wordpress.org URL for the WPSSO User Locale Selector add-on.
				$text .= ' ' . sprintf( __( 'This option is localized &mdash; <a href="%s">you may change the WordPress locale</a> to define alternate values for different languages.', 'wpsso' ), 'https://wordpress.org/plugins/wpsso-user-locale/' );
			}

			if ( strpos( $msg_key, 'tooltip-' ) === 0 && ! empty( $text ) ) {

				$text = '<img src="' . WPSSO_URLPATH . 'images/question-mark.png" width="14" height="14" class="' .
					( isset( $info[ 'class' ] ) ? $info[ 'class' ] : $this->p->cf[ 'form' ][ 'tooltip_class' ] ) .
						'" alt="' . esc_attr( $text ) . '" />';
			}

			return $text;
		}

		private function get_cf_info( $msg_key = false ) {

			static $local_cache = null;

			if ( null === $local_cache ) {
				$local_cache = array(
					'addl_type_urls' => array(
						_x( 'Microdata Type URLs', 'option label', 'wpsso' ),
						_x( 'additional microdata type URLs', 'tooltip fragment', 'wpsso' ),
					),
					'howto_steps' => array(
						_x( 'How-To Steps', 'option label', 'wpsso' ),
						_x( 'how-to steps', 'tooltip fragment', 'wpsso' ),
					),
					'howto_supplies' => array(
						_x( 'How-To Supplies', 'option label', 'wpsso' ),
						_x( 'how-to supplies', 'tooltip fragment', 'wpsso' ),
					),
					'howto_tools' => array(
						_x( 'How-To Tools', 'option label', 'wpsso' ),
						_x( 'how-to tools', 'tooltip fragment', 'wpsso' ),
					),
					'img_url' => array(
						_x( 'Image URL', 'option label', 'wpsso' ),
						_x( 'an image URL', 'tooltip fragment', 'wpsso' ),
					),
					'product_avail' => array(
						_x( 'Product Availability', 'option label', 'wpsso' ),
						_x( 'a product availability', 'tooltip fragment', 'wpsso' ),
					),
					'product_brand' => array(
						_x( 'Product Brand', 'option label', 'wpsso' ),
						_x( 'a product brand', 'tooltip fragment', 'wpsso' ),
					),
					'product_category' => array(
						_x( 'Product Category', 'option label', 'wpsso' ),
						sprintf( _x( 'a <a href="%s">Google product type ID</a>', 'tooltip fragment', 'wpsso' ),
							__( 'https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt', 'wpsso' ) ),
					),
					'product_color' => array(
						_x( 'Product Color', 'option label', 'wpsso' ),
						_x( 'a product color', 'tooltip fragment', 'wpsso' ),
					),
					'product_condition' => array(
						_x( 'Product Condition', 'option label', 'wpsso' ),
						_x( 'a product condition', 'tooltip fragment', 'wpsso' ),
					),
					'product_currency' => array(
						_x( 'Product Currency', 'option label', 'wpsso' ),
						_x( 'a product currency', 'tooltip fragment', 'wpsso' ),
					),
					'product_depth_value' => array(
						_x( 'Product Depth', 'option label', 'wpsso' ),
						sprintf( _x( 'a product depth (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'depth' ) ),
					),
					'product_gtin14' => array(
						_x( 'Product GTIN-14', 'option label', 'wpsso' ),
						_x( 'a product GTIN-14 code', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin13' => array(
						_x( 'Product GTIN-13/EAN', 'option label', 'wpsso' ),
						_x( 'a product GTIN-13 code (aka 13-digit ISBN codes or EAN/UCC-13)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin12' => array(
						_x( 'Product GTIN-12/UPC', 'option label', 'wpsso' ),
						_x( 'a product GTIN-12 code (12-digit GS1 identification key composed of a U.P.C. company prefix, item reference, and check digit)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin8' => array(
						_x( 'Product GTIN-8', 'option label', 'wpsso' ),
						_x( 'a product GTIN-8 code (aka EAN/UCC-8 or 8-digit EAN)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin' => array(
						_x( 'Product GTIN', 'option label', 'wpsso' ),
						_x( 'a product GTIN code (GTIN-8, GTIN-12/UPC, GTIN-13/EAN, or GTIN-14)', 'tooltip fragment', 'wpsso' ),
					),
					'product_height_value' => array(
						_x( 'Product Height', 'option label', 'wpsso' ),
						sprintf( _x( 'a product height (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'height' ) ),
					),
					'product_isbn' => array(
						_x( 'Product ISBN', 'option label', 'wpsso' ),
						_x( 'an ISBN code (aka International Standard Book Number)', 'tooltip fragment', 'wpsso' ),
					),
					'product_length_value' => array(
						_x( 'Product Length', 'option label', 'wpsso' ),
						sprintf( _x( 'a product length (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'length' ) ),
					),
					'product_material' => array(
						_x( 'Product Material', 'option label', 'wpsso' ),
						_x( 'a product material', 'tooltip fragment', 'wpsso' ),
					),
					'product_mfr_part_no' => array(
						_x( 'Product MPN', 'option label', 'wpsso' ),
						_x( 'a Manufacturer Part Number (MPN)', 'tooltip fragment', 'wpsso' ),
					),
					'product_price' => array(
						_x( 'Product Price', 'option label', 'wpsso' ),
						_x( 'a product price', 'tooltip fragment', 'wpsso' ),
					),
					'product_retailer_part_no' => array(
						_x( 'Product SKU', 'option label', 'wpsso' ),
						_x( 'a Stock-Keeping Unit (SKU)', 'tooltip fragment', 'wpsso' ),
					),
					'product_size' => array(
						_x( 'Product Size', 'option label', 'wpsso' ),
						_x( 'a product size', 'tooltip fragment', 'wpsso' ),
					),
					'product_target_gender' => array(
						_x( 'Product Target Gender', 'option label', 'wpsso' ),
						_x( 'a product target gender', 'tooltip fragment', 'wpsso' ),
					),
					'product_volume_value' => array(
						_x( 'Product Volume', 'option label', 'wpsso' ),
						sprintf( _x( 'a product volume (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'volume' ) ),
					),
					'product_weight_value' => array(
						_x( 'Product Weight', 'option label', 'wpsso' ),
						sprintf( _x( 'a product weight (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'weight' ) ),
					),
					'product_width_value' => array(
						_x( 'Product Width', 'option label', 'wpsso' ),
						sprintf( _x( 'a product width (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'width' ) ),
					),
					'recipe_ingredients' => array(
						_x( 'Recipe Ingredients', 'option label', 'wpsso' ),
						_x( 'recipe ingredients', 'tooltip fragment', 'wpsso' ),
					),
					'recipe_instructions' => array(
						_x( 'Recipe Instructions', 'option label', 'wpsso' ),
						_x( 'recipe instructions', 'tooltip fragment', 'wpsso' ),
					),
					'sameas_urls' => array(
						_x( 'Same-As URLs', 'option label', 'wpsso' ),
						_x( 'additional Same-As URLs', 'tooltip fragment', 'wpsso' ),
					),
					'vid_embed' => array(
						_x( 'Video Embed HTML', 'option label', 'wpsso' ),
						_x( 'video embed HTML code (not a URL)', 'tooltip fragment', 'wpsso' ),
					),
					'vid_url' => array(
						_x( 'Video URL', 'option label', 'wpsso' ),
						_x( 'a video URL (not HTML code)', 'tooltip fragment', 'wpsso' ),
					),
				);
			}

			if ( false !== $local_cache ) {
				if ( isset( $local_cache[ $msg_key ] ) ) {
					return $local_cache[ $msg_key ];
				} else {
					return null;
				}
			} else {
				return $local_cache;
			}
		}

		public function pro_feature( $ext ) {

			list( $ext, $p_ext ) = $this->get_ext_p_ext( $ext );

			if ( empty( $ext ) ) {
				return '';
			}

			return $this->get( 'pro-feature-msg', array( 'lca' => $ext ) );
		}

		public function pro_feature_video_api( $ext ) {

			$html = '<p class="pro-feature-msg">';

			$html .= sprintf( __( 'Video discovery and service API modules are provided with the %s version.', 'wpsso' ),
					_x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' ) );

			$html .= '</p>';
			
			return $html . $this->pro_feature( 'wpsso' );
		}

		/**
		 * If an add-on is not active, return a short message that this add-on is required.
		 */
		public function maybe_ext_required( $ext ) {

			list( $ext, $p_ext ) = $this->get_ext_p_ext( $ext );

			if ( empty( $ext ) ) {							// Just in case.
				return '';
			} elseif ( $this->p->lca === $ext ) {					// The main plugin is not considered an add-on.
				return '';
			} elseif ( ! empty( $this->p->avail[ 'p_ext' ][ $p_ext ] ) ) {		// Add-on is already active.
				return '';
			} elseif ( empty( $this->p->cf[ 'plugin' ][ $ext ][ 'short' ] ) ) {	// Unknown add-on.
				return '';
			}

			// translators: %s is is the short add-on name.
			$text = sprintf( _x( '%s required', 'option comment', 'wpsso' ), $this->p->cf[ 'plugin' ][ $ext ][ 'short' ] );

			$text = $this->p->util->get_admin_url( 'addons#' . $ext, $text );

			return ' <span class="ext-req-msg">' . $text . '</span>';
		}

		public function preview_images_first() {

			$html = ' <em>' . _x( 'note that video preview images are included first', 'option comment', 'wpsso' ) . '</em>';

			return $html;
		}

		public function maybe_preview_images_first() {

			$html = '';

			if ( ! empty( $this->form->options[ 'og_vid_prev_img' ] ) ) {
				$html .= ' <em>' . _x( 'note that video preview images are enabled (and included first)', 'option comment', 'wpsso' ) . '</em>';
			}

			return $html;
		}

		public function recommend_img_dims( $opt_pre ) {

			$og_def_img_dims = $this->p->msgs->get_def_img_dims( $opt_pre );

			return ' <em>' . $this->p->msgs->get_def_img_dims( 'og_img' ). ' ' . __( 'recommended', 'wpsso' ) . '</em>';
		}

		/**
		 * $extra_css_class can be empty, 'left', or 'inline'.
		 */
		public function p_img_disabled( $extra_css_class = '' ) {

			$link = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_pinterest',
				_x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' ) );

			// translators: %s is the option name, linked to its settings page.
			$text = sprintf( __( 'Modifications disabled (%s option is unchecked).', 'wpsso' ), $link );

			return '<p class="status-msg smaller disabled ' . $extra_css_class . '">' . $text . '</p>';
		}

		/**
		 * $extra_css_class can be empty, 'left', or 'inline'.
		 */
		public function amp_img_disabled( $extra_css_class = '' ) {

			$text = __( 'Modifications disabled (no AMP plugin active).', 'wpsso' );

			return '<p class="status-msg smaller disabled ' . $extra_css_class . '">' . $text . '</p>';
		}

		public function seo_option_disabled( $mt_name ) {

			// translators: %s is the meta tag name (aka meta name canonical).
			$text = sprintf( __( 'Modifications disabled (<code>%s</code> tag disabled or SEO plugin detected).', 'wpsso' ), $mt_name );

			return '<p class="status-msg smaller disabled">' . $text . '</p>';
		}

		public function more_schema_options() {

			if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

				return $this->schema_disabled();

			} else {

				$link = $this->p->util->get_admin_url( 'addons#wpssojson', $this->p->cf[ 'plugin' ][ 'wpssojson' ][ 'name' ] );

				// translators: %s is is the add-on name (and a link to the add-on page).
				$text = sprintf( __( 'Activate the %s add-on<br/>for Schema markup and structured data options.', 'wpsso' ), $link );

				return '<p class="status-msg">' . $text . '</p>';
			}
		}

		public function schema_disabled() {

			return '<p class="status-msg">' . __( 'Schema markup is disabled.', 'wpsso' ) . '</p>' .
				'<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>';
		}

		public function get_schema_disabled_rows( array &$table_rows, $col_span = 1 ) {

			$table_rows[ 'schema_disabled' ] = '<tr><td align="center" colspan="' . $col_span . '">' .
				$this->schema_disabled() . '</td></tr>';

			return $table_rows;
		}

		private function get_ext_p_ext( $ext ) {

			if ( is_string( $ext ) ) {

				if ( strpos( $ext, $this->p->lca ) !== 0 ) {
					$ext = $this->p->lca . $ext;
				}

				$p_ext = substr( $ext, strlen( $this->p->lca ) );

			} else {

				$ext = '';

				$p_ext = '';
			}

			return array( $ext, $p_ext );
		}

		private function get_def_img_dims( $opt_pre ) {

			$def_opts = $this->p->opt->get_defaults();

			$img_width = empty( $def_opts[ $opt_pre . '_width' ] ) ? 0 : $def_opts[ $opt_pre . '_width' ];

			$img_height = empty( $def_opts[ $opt_pre . '_height' ] ) ? 0 : $def_opts[ $opt_pre . '_height' ];

			$img_cropped = empty( $def_opts[ $opt_pre . '_crop' ] ) ?
				_x( 'uncropped', 'option value', 'wpsso' ) :
				_x( 'cropped', 'option value', 'wpsso' );

			return $img_width . 'x' . $img_height . 'px ' . $img_cropped;
		}
	}
}
