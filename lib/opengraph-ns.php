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

if ( ! class_exists( 'WpssoOpenGraphNS' ) ) {

	class WpssoOpenGraphNS {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$filter_name = SucomUtil::get_const( 'WPSSO_HTML_ATTR_FILTER_NAME', 'language_attributes' );
			$filter_prio = SucomUtil::get_const( 'WPSSO_HTML_ATTR_FILTER_PRIO', 1000 );

			if ( empty( $filter_name ) || 'none' === $filter_name ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipped filter_html_attributes - filter name is empty or disabled' );
				}

			} else {

				add_filter( $filter_name, array( $this, 'filter_html_attributes' ), $filter_prio, 1 );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'added filter_html_attributes filter for ' . $filter_name );
				}
			}

			$this->p->util->add_plugin_filters( $this, array(
				'og_data_https_ogp_me_ns_article' => 2,
				'og_data_https_ogp_me_ns_book'    => 2,
				'og_data_https_ogp_me_ns_product' => 2,
			) );
		}

		public function filter_html_attributes( $html_attr ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array (
					'html_attr' => $html_attr,
				) );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
			}

			$use_post = apply_filters( 'wpsso_use_post', false );

			$mod = $this->p->page->get_mod( $use_post );

			$type_id = $this->p->og->get_mod_og_type_id( $mod );

			$og_ns = array(
				'og' => 'https://ogp.me/ns#',
				'fb' => 'https://ogp.me/ns/fb#',
			);

			/**
			 * Check that the og_type is known and add it's namespace value.
			 *
			 * Example: article, place, product, website, etc.
			 */
			if ( ! empty( $this->p->cf[ 'head' ][ 'og_type_ns' ][ $type_id ] ) ) {

				$og_ns[ $type_id ] = $this->p->cf[ 'head' ][ 'og_type_ns' ][ $type_id ];
			}

			$og_ns = apply_filters( 'wpsso_og_ns', $og_ns, $mod );

			if ( SucomUtil::is_amp() ) {	// Returns null, true, or false.

				/**
				 * Nothing to do.
				 */

			} else {

				$html_attr = ' ' . $html_attr;	// Prepare the string for testing.

				/**
				 * Find and remove an existing prefix attribute value.
				 */
				if ( strpos( $html_attr, 'prefix=' ) ) {

					/**
				 	 * s = A dot metacharacter in the pattern matches all characters, including newlines.
					 *
					 * See https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php
					 */
					if ( preg_match( '/^(.*)\sprefix=["\']([^"\']*)["\'](.*)$/s', $html_attr, $match ) ) {

						$html_attr    = $match[1] . $match[3];	// Remove the prefix.
					}
				}

				$attr_val = '';

				foreach ( $og_ns as $name => $url ) {

					if ( false === strpos( $attr_val, ' ' . $name . ': ' . $url ) ) {

						$attr_val .= ' ' . $name . ': ' . $url;
					}
				}

				$html_attr .= ' prefix="' . trim( $attr_val ) . '"';
			}

			return trim( $html_attr );
		}

		/**
		 * The output from this method is provided to the JSON data filters, so be careful when removing any array
		 * elements. If you need to remove array elements after the Schema JSON-LD markup has been created, but before the
		 * meta tags have been generated, use the WpssoOpenGraph->sanitize_mt_array() method.
		 */
		public function filter_og_data_https_ogp_me_ns_article( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $mt_og;
		}

		/**
		 * The output from this method is provided to the JSON data filters, so be careful when removing any array
		 * elements. If you need to remove array elements after the Schema JSON-LD markup has been created, but before the
		 * meta tags have been generated, use the WpssoOpenGraph->sanitize_mt_array() method.
		 */
		public function filter_og_data_https_ogp_me_ns_book( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $mt_og;
		}

		/**
		 * The output from this method is provided to the JSON data filters, so be careful when removing any array
		 * elements. If you need to remove array elements after the Schema JSON-LD markup has been created, but before the
		 * meta tags have been generated, use the WpssoOpenGraph->sanitize_mt_array() method.
		 *
		 * The WPSSO BC add-on also hooks this filter to populate the 'product:retailer_category' value (a string used to
		 * organize bidding and reporting in Google Ads Shopping campaigns).
		 */
		public function filter_og_data_https_ogp_me_ns_product( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mt_og[ 'product:retailer_category' ] = $this->p->og->get_product_retailer_category( $mod );

			$mt_og[ 'product:retailer_item_id' ] = $mod[ 'id' ];	// The product ID is the post ID by default.

			WpssoOpenGraph::check_mt_value_gtin( $mt_og, $mt_pre = 'product' );

			WpssoOpenGraph::check_mt_value_price( $mt_og, $mt_pre = 'product' );

			/**
			 * Include variations (aka product offers) if available.
			 */
			if ( ! empty( $mt_og[ 'product:offers' ] ) && is_array( $mt_og[ 'product:offers' ] ) ) {

				foreach ( $mt_og[ 'product:offers' ] as $num => &$offer ) {	// Allow changes to the offer array.

					WpssoOpenGraph::check_mt_value_gtin( $offer, $mt_pre = 'product' );

					WpssoOpenGraph::check_mt_value_price( $offer, $mt_pre = 'product' );

					/**
					 * Allow only a single main product brand.
					 */
					if ( ! empty( $offer[ 'product:brand' ] ) ) {

						if ( empty( $mt_og[ 'product:brand' ] ) ) {

							$mt_og[ 'product:brand' ] = $offer[ 'product:brand' ];
						}

						unset ( $offer[ 'product:brand' ] );
					}
				}
			}

			return $mt_og;
		}
	}
}
