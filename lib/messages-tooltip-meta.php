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

if ( ! class_exists( 'WpssoMessagesTooltipMeta' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltip->get() only when needed.
	 */
	class WpssoMessagesTooltipMeta extends WpssoMessages {

		private $og     = null;	// WpssoMessagesTooltipMetaOpenGraph class object.
		private $schema = null;	// WpssoMessagesTooltipMetaSchema class object.

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			if ( 0 === strpos( $msg_key, 'tooltip-meta-og_' ) ) {

				/**
				 * Instantiate WpssoMessagesTooltipMetaOpenGraph only when needed.
				 */
				if ( null === $this->og ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip-meta-opengraph.php';

					$this->og = new WpssoMessagesTooltipMetaOpenGraph( $this->p );
				}

				return $this->og->get( $msg_key, $info );

			} elseif ( 0 === strpos( $msg_key, 'tooltip-meta-schema_' ) ) {

				/**
				 * Instantiate WpssoMessagesTooltipMetaSchema only when needed.
				 */
				if ( null === $this->schema ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip-meta-schema.php';

					$this->schema = new WpssoMessagesTooltipMetaSchema( $this->p );
				}

				return $this->schema->get( $msg_key, $info );
			}

			switch ( $msg_key ) {

				case 'tooltip-meta-primary_term_id':	// Primary Category.

					$text .= __( 'Select a primary category for breadcrumbs.' );

				 	break;

				case 'tooltip-meta-pin_img_desc':	// Pinterest Description.

					$text = __( 'A customized description for the Pinterest Pin It browser button.', 'wpsso' );

				 	break;

				case 'tooltip-meta-tc_title':		// Twitter Card Title.

					$text = __( 'A customized title for the Twitter Card title meta tag (all Twitter Card formats).', 'wpsso' );

				 	break;

				case 'tooltip-meta-tc_desc':		// Twitter Card Description.

					$text = __( 'A customized description for the Twitter Card description meta tag (all Twitter Card formats).', 'wpsso' );

				 	break;

				case 'tooltip-meta-seo_desc':		// Search Description.

					$text = __( 'A customized description for the SEO description meta tag.', 'wpsso' );

					$text .= $this->maybe_html_tag_disabled_text( $parts = array( 'meta', 'name', 'description' ) );

				 	break;

				case 'tooltip-meta-canonical_url':	// Canonical URL.

					$text = __( 'A customized URL for meta tags and Schema markup.', 'wpsso' ) . ' ';

					$text .= __( 'Please make sure the custom URL you enter here is functional and redirects correctly.', 'wpsso' );

				 	break;

				case 'tooltip-meta-article_section':	// Article Section.

					$option_link = $this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_site',
						_x( 'Default Article Section', 'option label', 'wpsso' ) );

					$text = sprintf( __( 'A customized section for this article, which may be different than the %s option value.',
						'wpsso' ), $option_link ) . ' ';

					$text .= sprintf( __( 'Select "[None]" if you prefer to exclude the %s meta tag.', 'wpsso' ),
						'<code>article:section</code>' );

				 	break;

				case 'tooltip-meta-reading_mins':	// Est. Reading Time.

					$text = __( 'The estimated reading time (in minutes) for this article.', 'wpsso' ) . ' ';

					$text .= __( 'A value of 0 minutes disables the estimated reading time meta tags.', 'wpsso' );

				 	break;

				case 'tooltip-meta-book_isbn':		// Book ISBN.

					$cf_frags = $this->get_cf_tooltip_fragments( preg_replace( '/^tooltip-meta-/', '', $msg_key ) );

					$text = sprintf( __( 'The value of %s can be used in meta tags and Schema markup.', 'wpsso' ), $cf_frags[ 'desc' ] );

				 	break;

				case 'tooltip-meta-product_category':	// Product Type.

					$option_link = $this->p->util->get_admin_url( 'general#sucom-tabset_og-tab_site',
						_x( 'Default Product Type', 'option label', 'wpsso' ) );

					$text = sprintf( __( 'A custom Google product type, which may be different than the %s option value.',
						'wpsso' ), $option_link ) . ' ';

					/**
					 * See https://developers.facebook.com/docs/marketing-api/catalog/reference/.
					 */
					$text .= sprintf( __( 'Your selection will be used for Schema product markup and the %s meta tag.',
						'wpsso' ), '<code>product:category</code>' ) . ' ';

					$text .= __( 'Select "[None]" if you prefer to exclude the product type from Schema markup and meta tags.',
						'wpsso' );

				 	break;

				case ( 0 === strpos( $msg_key, 'tooltip-meta-product_' ) ? true : false ):

					$cf_frags = $this->get_cf_tooltip_fragments( preg_replace( '/^tooltip-meta-/', '', $msg_key ) );

					if ( ! empty( $cf_frags ) ) {	// Just in case.

						$text = sprintf( __( 'The value of %s can be used in meta tags and Schema markup for simple products.', 'wpsso' ), $cf_frags[ 'desc' ] ) . ' ';

						$text .= __( 'When e-commerce product variations are available, the value from each variation will be used instead.', 'wpsso' ) . ' ';

						$text .= __( 'This option may be disabled when a supported e-commerce plugin is the authoritative source of this data.', 'wpsso' );
					}

				 	break;

				case 'tooltip-meta-pin_img_id':		// Image ID.

					$text = __( 'A customized image ID for the Pinterest Pin It browser button.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';

				 	break;

				case 'tooltip-meta-pin_img_url':		// or an Image URL.

					$text = __( 'A customized image URL (instead of an image ID) for the Pinterest Pin It browser button.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image ID is selected.', 'wpsso' ) . '</em>';

				 	break;

				case 'tooltip-meta-tc_lrg_img_id':	// Image ID.
				case 'tooltip-meta-tc_sum_img_id':	// Image ID.

					$text = __( 'A customized image ID for the Twitter Card image.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';

				 	break;

				case 'tooltip-meta-tc_lrg_img_url':	// or an Image URL.
				case 'tooltip-meta-tc_sum_img_url':	// or an Image URL.

					$text = __( 'A customized image URL (instead of an image ID) for the Twitter Card image.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image ID is selected.', 'wpsso' ) . '</em>';

				 	break;

				/**
				 * See https://developers.google.com/search/reference/robots_meta_tag#noarchive.
				 */
				case 'tooltip-meta-robots_noarchive':

					$text = __( 'Do not show a cached link in search results.', 'wpsso' );

				 	break;

				/**
				 * See https://developers.google.com/search/reference/robots_meta_tag#nofollow.
				 */
				case 'tooltip-meta-robots_nofollow':

					$text = __( 'Do not follow links on this webpage.', 'wpsso' );

				 	break;

				/**
				 * See https://developers.google.com/search/reference/robots_meta_tag#noimageindex.
				 */
				case 'tooltip-meta-robots_noimageindex':

					$text = __( 'Do not index images on this webpage.', 'wpsso' );

				 	break;

				/**
				 * See https://developers.google.com/search/reference/robots_meta_tag#noindex.
				 */
				case 'tooltip-meta-robots_noindex':

					$text = __( 'Do not show this webpage in search results.', 'wpsso' );

				 	break;

				/**
				 * See https://developers.google.com/search/reference/robots_meta_tag#nosnippet.
				 */
				case 'tooltip-meta-robots_nosnippet':

					$text = __( 'Do not show a text snippet or a video preview in search results.', 'wpsso' ) . ' ';

					$text .= __( 'Google may still show a static image thumbnail (if available) when it determines that using an image provides a better user-experience.', 'wpsso' );

				 	break;

				/**
				 * See https://developers.google.com/search/reference/robots_meta_tag#notranslate.
				 */
				case 'tooltip-meta-robots_notranslate':

					$text = __( 'Do not offer translation of this webpage in search results.', 'wpsso' );

				 	break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-meta' switch.

			return $text;
		}
	}
}
