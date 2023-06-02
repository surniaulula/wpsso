<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoMessagesTooltipMeta' ) ) {

	/*
	 * Instantiated by WpssoMessagesTooltip->get() only when needed.
	 */
	class WpssoMessagesTooltipMeta extends WpssoMessages {

		private $msgs = array();	// WpssoMessagesTooltipMeta* class objects.

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$text = '';

			foreach ( array(
				'tooltip-meta-og_'      => 'opengraph',
				'tooltip-meta-org_'     => 'org',
				'tooltip-meta-place_'   => 'place',
				'tooltip-meta-product_' => 'product',
				'tooltip-meta-schema_'  => 'schema',
			) as $msg_key_prefix => $class_suffix ) {

				if ( 0 === strpos( $msg_key, $msg_key_prefix ) ) {

					if ( ! isset( $this->msgs[ $msg_key_prefix ] ) ) {

						$filename  = WPSSO_PLUGINDIR . 'lib/messages-tooltip-meta-' . $class_suffix . '.php';
						$classname = 'WpssoMessagesTooltipMeta' . $class_suffix;

						require_once $filename;

						$this->msgs[ $msg_key_prefix ] = new $classname( $this->p );
					}

					return $this->msgs[ $msg_key_prefix ]->get( $msg_key, $info );
				}
			}

			/*
			 * WPSSO MRP add-on.
			 */
			if ( 0 === strpos( $msg_key, 'tooltip-meta-mrp_' ) ) {

				return apply_filters( 'wpsso_messages_tooltip_meta_mrp', $text, $msg_key, $info );

			} elseif ( 0 === strpos( $msg_key, 'tooltip-meta-pin_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-meta-pin_img_desc':	// Pinterest Description.
	
						$text = __( 'A customized description for the Pinterest Pin It browser button.', 'wpsso' ) . ' ';
	
						$text .= __( 'The default value is inherited from the social or SEO description.', 'wpsso' ) . ' ';
	
					 	break;
	
					case 'tooltip-meta-pin_img_id':		// Image ID.
	
						$text = __( 'A customized image ID for the Pinterest Pin It browser button.', 'wpsso' ) . ' ';
	
						$text .= __( 'The default value is inherited from the Schema markup or priority image.', 'wpsso' ) . ' ';
	
						$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';
	
					 	break;
	
					case 'tooltip-meta-pin_img_url':	// or an Image URL.
	
						$text = __( 'A customized image URL (instead of an image ID) for the Pinterest Pin It browser button.', 'wpsso' ) . ' ';
	
						$text .= __( 'The default value is inherited from the Schema markup or priority image.', 'wpsso' ) . ' ';
	
						$text .= '<em>' . __( 'This option is disabled if a custom image ID is selected.', 'wpsso' ) . '</em>';
	
					 	break;

					default:
	
						$text = apply_filters( 'wpsso_messages_tooltip_meta_pin', $text, $msg_key, $info );
	
						break;

				}	// End of 'tooltip-meta-pin' switch.

			} elseif ( 0 === strpos( $msg_key, 'tooltip-meta-robots_' ) ) {

				switch ( $msg_key ) {

					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#noarchive.
					 */
					case 'tooltip-meta-robots_noarchive':
	
						$text = __( 'Do not show a cached link in search results.', 'wpsso' );
	
					 	break;
	
					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#nofollow.
					 */
					case 'tooltip-meta-robots_nofollow':
	
						$text = __( 'Do not follow links on this webpage.', 'wpsso' );
	
					 	break;
	
					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#noimageindex.
					 */
					case 'tooltip-meta-robots_noimageindex':
	
						$text = __( 'Do not index images on this webpage.', 'wpsso' );
	
					 	break;
	
					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#noindex.
					 */
					case 'tooltip-meta-robots_noindex':
	
						$text = __( 'Do not show this webpage in search results.', 'wpsso' );
	
					 	break;
	
					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#nosnippet.
					 */
					case 'tooltip-meta-robots_nosnippet':
	
						$text = __( 'Do not show a text snippet or a video preview in search results.', 'wpsso' ) . ' ';
	
						$text .= __( 'Google may still show a static image thumbnail (if available) when it determines that using an image provides a better user-experience.', 'wpsso' );
	
					 	break;
	
					/*
					 * See https://developers.google.com/search/reference/robots_meta_tag#notranslate.
					 */
					case 'tooltip-meta-robots_notranslate':
	
						$text = __( 'Do not offer translation of this webpage in search results.', 'wpsso' );
	
					 	break;

					default:
	
						$text = apply_filters( 'wpsso_messages_tooltip_meta_robots', $text, $msg_key, $info );
	
						break;

				}	// End of 'tooltip-meta-robots' switch.
	
			} elseif ( 0 === strpos( $msg_key, 'tooltip-meta-tc_' ) ) {

				switch ( $msg_key ) {

					case 'tooltip-meta-tc_title':		// Twitter Card Title.
	
						$text = __( 'A customized title for the Twitter Card title meta tag (all Twitter Card formats).', 'wpsso' ) . ' ';
	
						$text .= __( 'The default value is inherited from the social or SEO title.', 'wpsso' ) . ' ';
	
					 	break;
	
					case 'tooltip-meta-tc_desc':		// Twitter Card Description.
	
						$text = __( 'A customized description for the Twitter Card description meta tag (all Twitter Card formats).', 'wpsso' ) . ' ';
	
						$text .= __( 'The default value is inherited from the social or SEO description.', 'wpsso' ) . ' ';
	
					 	break;
	
					/*
					 * Document SSO > Edit Media tab.
					 */
					case 'tooltip-meta-tc_lrg_img_id':	// Image ID.
					case 'tooltip-meta-tc_sum_img_id':	// Image ID.
	
						$text = __( 'A customized image ID for the Twitter Card image.', 'wpsso' ) . ' ';
	
						$text .= __( 'The default value is inherited from the priority image.', 'wpsso' ) . ' ';
	
						$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';
	
					 	break;
	
					case 'tooltip-meta-tc_lrg_img_url':	// or an Image URL.
					case 'tooltip-meta-tc_sum_img_url':	// or an Image URL.

						$text = __( 'A customized image URL (instead of an image ID) for the Twitter Card image.', 'wpsso' ) . ' ';
	
						$text .= __( 'The default value is inherited from the priority image.', 'wpsso' ) . ' ';
	
						$text .= '<em>' . __( 'This option is disabled if a custom image ID is selected.', 'wpsso' ) . '</em>';
	
					 	break;

					default:
	
						$text = apply_filters( 'wpsso_messages_tooltip_meta_tc', $text, $msg_key, $info );
	
						break;

				}	// End of 'tooltip-meta-tc' switch.

			} else {

				switch ( $msg_key ) {
	
					case 'tooltip-meta-primary_term_id':	// Primary Category.
	
						$text .= __( 'The primary (ie. top most) category for breadcrumbs markup.' );
	
					 	break;
	
					case 'tooltip-meta-seo_title':		// SEO Title Tag.
	
						$text = __( 'A customized description for the SEO title tag and the default for all other title values.', 'wpsso' );
	
					 	break;
	
					case 'tooltip-meta-seo_desc':		// SEO Meta Description.
	
						$text = __( 'A customized description for the SEO description meta tag and the default for all other description values.', 'wpsso' );
	
						$text .= $this->maybe_add_seo_tag_disabled_link( 'meta name description' );
	
					 	break;
	
					case 'tooltip-meta-canonical_url':	// Canonical URL.
	
						$text = __( 'A customized URL for meta tags and Schema markup.', 'wpsso' ) . ' ';
	
						$text .= __( 'Make sure the custom URL you enter here is functional and redirects correctly.', 'wpsso' );
	
					 	break;
	
					case 'tooltip-meta-redirect_url':	// 301 Redirect URL.
	
						$text = __( 'Permanently redirect this URL to another.', 'wpsso' ) . ' ';
	
						$text .= __( 'Make sure the custom URL you enter here is functional and redirects correctly.', 'wpsso' );
	
					 	break;
	
					default:
	
						$text = apply_filters( 'wpsso_messages_tooltip_meta', $text, $msg_key, $info );
	
						break;

				}	// End of 'tooltip-meta' switch.
			}

			return $text;
		}
	}
}
