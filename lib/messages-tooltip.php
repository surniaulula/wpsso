<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoMessagesTooltip' ) ) {

	/*
	 * Instantiated by WpssoMessages->get() only when needed.
	 */
	class WpssoMessagesTooltip extends WpssoMessages {

		private $meta   = null;	// WpssoMessagesTooltipMeta class object.
		private $og     = null;	// WpssoMessagesTooltipOpenGraph class object.
		private $plugin = null;	// WpssoMessagesTooltipPlugin class object.
		private $schema = null;	// WpssoMessagesTooltipSchema class object.
		private $site   = null;	// WpssoMessagesTooltipSite class object.

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$text = '';

			/*
			 * Document SSO metabox tooltips.
			 */
			if ( 0 === strpos( $msg_key, 'tooltip-meta-' ) ) {

				/*
				 * Instantiate WpssoMessagesTooltipMeta only when needed.
				 */
				if ( null === $this->meta ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip-meta.php';

					$this->meta = new WpssoMessagesTooltipMeta( $this->p );
				}

				return $this->meta->get( $msg_key, $info );

			/*
			 * Open Graph settings
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-og_' ) ) {

				/*
				 * Instantiate WpssoMessagesTooltipOpenGraph only when needed.
				 */
				if ( null === $this->og ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip-opengraph.php';

					$this->og = new WpssoMessagesTooltipOpenGraph( $this->p );
				}

				return $this->og->get( $msg_key, $info );

			/*
			 * Advanced plugin settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-plugin_' ) ) {

				/*
				 * Instantiate WpssoMessagesTooltipPlugin only when needed.
				 */
				if ( null === $this->plugin ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip-plugin.php';

					$this->plugin = new WpssoMessagesTooltipPlugin( $this->p );
				}

				return $this->plugin->get( $msg_key, $info );

			/*
			 * Schema settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-schema_' ) ) {

				/*
				 * Instantiate WpssoMessagesTooltipSchema only when needed.
				 */
				if ( null === $this->schema ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip-schema.php';

					$this->schema = new WpssoMessagesTooltipSchema( $this->p );
				}

				return $this->schema->get( $msg_key, $info );

			/*
			 * Site settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-site_' ) ) {

				/*
				 * Instantiate WpssoMessagesTooltipSite only when needed.
				 */
				if ( null === $this->site ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip-site.php';

					$this->site = new WpssoMessagesTooltipSite( $this->p );
				}

				return $this->site->get( $msg_key, $info );

			/*
			 * Behance settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-behance_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-behance_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'behance_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">Behance profile for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://www.behance.net/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;
				}

			/*
			 * Facebook settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-fb_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-fb_site_verify':		// Facebook Domain Verification ID.

						$text = sprintf( __( 'To <a href="%s">verify your domain with Facebook</a>, enter the "facebook-domain-verification" meta tag <code>content</code> value here (enter only the verification ID value, not the whole HTML tag).', 'wpsso' ), 'https://developers.facebook.com/docs/sharing/domain-verification/verifying-your-domain/' );

						break;

					case 'tooltip-fb_publisher_url':	// Facebook Business Page URL (localized).

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'fb_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">Facebook page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://www.facebook.com/business', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in Open Graph <em>article</em> meta tags and the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					case 'tooltip-fb_author_field':	// Author Profile URL Field.

						$cm_label_value = SucomUtilOptions::get_key_value( 'plugin_cm_fb_label', $this->p->options );

						$text = sprintf( __( 'Choose a contact field from the WordPress profile page to use for the Facebook / Open Graph %s meta tag value.', 'wpsso' ), '<code>article:author</code>' ) . ' ';

						$text .= sprintf( __( 'The suggested value is the "%s" user profile contact field.', 'wpsso' ), $cm_label_value ) . ' ';

						break;

					case 'tooltip-fb_app_id':	// Facebook Application ID.

						$fb_docs_url = __( 'https://developers.facebook.com/docs/development/create-an-app', 'wpsso' );

						$text = sprintf( __( 'If you have a Facebook App ID for your WebSite App, enter it here (see <a href="%s">Create an App</a> for help on creating a Facebook App ID for your WebSite).', 'wpsso' ), $fb_docs_url ) . ' ';

						break;

					case 'tooltip-fb_locale':	// Facebook Locale.

						/*
						 * See SucomUtil::get_publisher_languages( 'facebook' ).
						 */
						$text = __( 'Facebook does not understand all WordPress locale values.', 'wpsso' ) . ' ';

						$text .= sprintf( __( 'If the Facebook debugger returns an error parsing the %s meta tag, you should choose an alternate Facebook language for that WordPress locale.', 'wpsso' ), '<code>og:locale</code>' );

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_fb', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-fb' switch.

			/*
			 * Google settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-g_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-g_site_verify':	// Google Website Verification ID.

						$text = sprintf( __( 'To verify your website ownership with <a href="%s">Google\'s Search Console</a>, select the <em>Settings</em> left-side menu option in the Search Console, then <em>Ownership and verification</em>, and then choose the <em>HTML tag</em> method.', 'wpsso' ), 'https://search.google.com/search-console' ) . ' ';

						$text .= __( 'Enter the "google-site-verification" meta tag <code>content</code> value here (enter only the verification ID value, not the whole HTML tag).', 'wpsso' );

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_g', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-g' switch.

			/*
			 * SEO settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-seo_' ) ) {

				switch ( $msg_key ) {

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_seo', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-seo' switch.

			/*
			 * Robots settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-robots_' ) ) {

				switch ( $msg_key ) {

					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#max-snippet.
					 */
					case 'tooltip-robots_max_snippet':	// Robots Snippet Max. Length

						$text = __( 'Suggest a maximum of number characters for the textual snippet in search results.', 'wpsso' ) . ' ';

						$text .= __( 'This does not affect image or video previews, or apply to text in Schema markup.', 'wpsso' );

					 	break;

					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#max-image-preview.
					 */
					case 'tooltip-robots_max_image_preview':

						$none     = _x( $this->p->cf[ 'form' ][ 'robots_max_image_preview' ][ 'none' ], 'option value', 'wpsso' );
						$standard = _x( $this->p->cf[ 'form' ][ 'robots_max_image_preview' ][ 'standard' ], 'option value', 'wpsso' );
						$large    = _x( $this->p->cf[ 'form' ][ 'robots_max_image_preview' ][ 'large' ], 'option value', 'wpsso' );

						$text = __( 'Suggest a maximum size for the image preview in search results.', 'wpsso' ) . ' ';

						$text .= '<ul>';

						$text .= '<li>' . sprintf( __( '%s = No image preview will be shown.', 'wpsso' ), $none ) . '</li> ';

						$text .= '<li>' . sprintf( __( '%s = A default image preview size may be used.', 'wpsso' ), $standard ) . '</li> ';

						$text .= '<li>' . sprintf( __( '%s = A larger image preview size, up to the width of the viewport, may be used.',
							'wpsso' ), $large ) . '</li> ';

						$text .= '</ul> ';

						$text .= sprintf( __( 'If you don\'t want Google to use a larger thumbnail when an AMP page or canonical version of an article is shown in Search or Discover, select %1$s or %2$s.', 'wpsso' ), $standard, $none );

					 	break;

					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#max-video-preview.
					 */
					case 'tooltip-robots_max_video_preview':

						$text = __( 'Suggest a maximum of number seconds for video snippets in search results.', 'wpsso' );

						$text .= '<ul>';

						$text .= '<li>' . __( '0 = Shows a static image for videos, if image previews are allowed in search results.', 'wpsso' ) . '</li>';

						$text .= '<li>' . __( '-1 = No limit.', 'wpsso' ) . '</li>';

						$text .= '</ul>';

					 	break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_robots', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-robots' switch.

				$text .= $this->maybe_add_seo_tag_disabled_link( 'meta name robots' );

			/*
			 * Pinterest settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-pin_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-pin_site_verify':	// Pinterest Website Verification ID.

						$text = sprintf( __( 'To <a href="%s">claim your website with Pinterest</a>: Edit your account settings on Pinterest, select the "Claim" section, enter your website URL, then click the "Claim" button.', 'wpsso' ), 'https://help.pinterest.com/en/business/article/claim-your-website' ) . ' ';

						$text .= __( 'Choose "Add HTML tag" and enter the "p:domain_verify" meta tag <code>content</code> value here (enter only the verification ID string, not the meta tag HTML).', 'wpsso' );

						break;

					case 'tooltip-pin_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'pin_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">Pinterest page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://business.pinterest.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					case 'tooltip-pin_add_nopin_header_img_tag':	// Add "nopin" to Site Header Image.

						$text = sprintf( __( 'Add a %s attribute to the site header and Gravatar images to prevent the Pinterest Pin It browser button from suggesting these images.', 'wpsso' ), '<code>data-pin-nopin</code>' );

						break;

					case 'tooltip-pin_add_nopin_media_img_tag':	// Add Pinterest "nopin" to Images.

						$add_img_html_label = _x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' );

						$text = sprintf( __( '%1$s can add a %2$s attribute to resized images from the WordPress Media Library.', 'wpsso' ), $info[ 'short' ], '<code>data-pin-nopin</code>' ) . ' ';

						$text .=  __( 'This prevents the Pinterest Pin It browser button from suggesting images that may be too small.', 'wpsso' ) . ' ';

						$text .= sprintf( __( 'When enabling this option, you should also enable the "%s" option to provide an image for the Pin It browser button.', 'wpsso' ), $add_img_html_label ) . ' ';

						break;

					case 'tooltip-pin_add_img_html':			// Add Hidden Image for Pinterest.

						$text = __( 'Add an extra hidden image for the Pinterest Pin It browser button in the WordPress singular post/page content, author description, post types archive description, and term description.', 'wpsso' ) . ' ';

						$text .= __( 'Although generally recommended, this option is disabled by default since the extra image can affect page load speed (the image cannot be lazy loaded).', 'wpsso' ) . ' ';

						$text .= __( 'If your website visitors use the Pinterest Pin It browser button, you may enable this option, otherwise leave it unchecked.', 'wpsso' ) . ' ';

						break;

					case 'tooltip-pin_img_size':	// Pinterest Pin It Image Size.

						$def_img_dims = $this->get_def_img_dims( 'pin' );

						$text = sprintf( __( 'The dimensions used for the Pinterest Pin It browser button image (default dimensions are %s).', 'wpsso' ), $def_img_dims ) . ' ';

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_p', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-p' switch.

			/*
			 * X (Twitter) settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-tc_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-tc_site':		// X (Twitter) Business @username.

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'tc_site' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">X (Twitter) @username for your business</a> (not your personal @username), you may enter its name here.', 'wpsso' ), __( 'https://business.twitter.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in in X (Twitter) Card meta tags and the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					case 'tooltip-tc_type_singular':

						$text = 'The X (Twitter) Card type for singular content (posts, pages, or custom post types) with a custom, featured, and/or attached image.';

						break;

					case 'tooltip-tc_type_default':

						$text = 'The X (Twitter) Card type for all other images (default, image from content text, etc).';

						break;

					case 'tooltip-tc_sum_img_size':	// X (Twitter) Summary Card.

						$def_img_dims = $this->get_def_img_dims( $opt_pre = 'tc_sum' );

						$text = sprintf( __( 'The dimensions used for the <a href="%1$s">Summary Card</a> image should be at least %2$s (default dimensions are %3$s).', 'wpsso' ), 'https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/summary', '144x144px', $def_img_dims ) . ' ';

						break;

					case 'tooltip-tc_lrg_img_size':	// X (Twitter) Summary Card Large Image.

						$def_img_dims = $this->get_def_img_dims( $opt_pre = 'tc_lrg' );

						$text = sprintf( __( 'The dimensions used for the <a href="%1$s">Large Image Summary Card</a> must be larger than %2$s (default dimensions are %3$s).', 'wpsso' ), 'https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/summary-card-with-large-image', '300x157px', $def_img_dims ) . ' ';

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_tc', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-tc' switch.

			/*
			 * Instagram settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-instagram_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-instagram_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'instagram_publisher_url' ) ) {

							$text = sprintf( __( 'If you have an <a href="%s">Intagram profile for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://business.instagram.com/getting-started', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_instagram', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-instagram' switch.

			/*
			 * LinkedIn settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-linkedin_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-linkedin_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'linkedin_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">LinkedIn page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://business.linkedin.com/marketing-solutions/linkedin-pages', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_linkedin', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-linkedin' switch.

			/*
			 * Medium settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-medium_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-medium_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'medium_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">Medium page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://medium.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_medium', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-medium' switch.

			/*
			 * Myspace settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-myspace_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-myspace_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'myspace_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">Myspace page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://myspace.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_myspace', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-myspace' switch.

			/*
			 * Soundcloud settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-sc_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-sc_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'sc_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">Soundcloud page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://soundcloud.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_sc', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-sc' switch.

			/*
			 * TikTok settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-tiktok_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-tiktok_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'tiktok_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">TikTok page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://tiktok.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_tiktok', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-tiktok' switch.

			/*
			 * Tumblr settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-tumblr_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-tumblr_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'tumblr_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">Tumblr page for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://tumblr.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_tumblr', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-tumblr' switch.

			/*
			 * Wikipedia settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-wikipedia_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-wikipedia_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'wikipedia_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">Wikipedia page for your organization</a>, you may enter its URL here.', 'wpsso' ), __( 'https://en.wikipedia.org/wiki/Wikipedia:FAQ/Organizations', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_wikipedia', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-wikipedia' switch.

			/*
			 * YouTube settings.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-yt_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-yt_publisher_url':

						if ( $publisher_url_label = WpssoConfig::get_social_accounts( 'yt_publisher_url' ) ) {

							$text = sprintf( __( 'If you have a <a href="%s">YouTube channel for your business</a>, you may enter its URL here.', 'wpsso' ), __( 'https://youtube.com/', 'wpsso' ) ) . ' ';

							$text .= sprintf( __( 'The %s will be included in the website\'s Schema Organization markup.', 'wpsso' ), $publisher_url_label ) . ' ';

							$text .= __( 'Google Search may use this URL to display additional information about the website, business, or company in its search results.', 'wpsso' );
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip_yt', $text, $msg_key, $info );

						break;

				}	// End of 'tooltip-yt' switch.

			/*
			 * WPSSO AM add-on.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-am_' ) ) {

				return apply_filters( 'wpsso_messages_tooltip_am', $text, $msg_key, $info );

			/*
			 * WPSSO RRSSB add-on.
			 */
			} elseif ( 0 === strpos( $msg_key, 'tooltip-buttons_' ) ) {

				return apply_filters( 'wpsso_messages_tooltip_buttons', $text, $msg_key, $info );

			/*
			 * All other tooltips.
			 */
			} else {

				switch ( $msg_key ) {

					case 'tooltip-custom-cm-field-id':

						$text .= '<strong>' . sprintf( __( 'You should not modify the <em>%s</em> column unless you have a <em>very</em> good reason to do so.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) ) . '</strong> ';

						$text .= sprintf( __( 'As an example, to match the <em>%1$s</em> of a theme or other plugin, you might change "%2$s" to "%3$s" or some other value.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ), 'facebook', 'fb' );

						break;

					case 'tooltip-custom-cm-field-label':

						$text = sprintf( __( 'The %s column is for display purposes only and can be changed as you wish.', 'wpsso' ), _x( 'Contact Field Label', 'column title', 'wpsso' ) );

						break;

					case 'tooltip-wp-cm-field-id':

						$text = sprintf( __( 'The WordPress <em>%s</em> column cannot be modified.', 'wpsso' ), _x( 'Contact Field ID', 'column title', 'wpsso' ) );

						break;

					default:

						$text = apply_filters( 'wpsso_messages_tooltip', $text, $msg_key, $info );

						break;

				} 	// End of other tooltips.

			}	// End of tooltips.

			return $text;
		}
	}
}
