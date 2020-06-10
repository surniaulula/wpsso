<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {
	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoOpenGraphNS' ) ) {

	class WpssoOpenGraphNS {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Hook the first available filter name (example: 'language_attributes').
			 */
			foreach ( array( 'plugin_html_attr_filter', 'plugin_head_attr_filter' ) as $opt_pre ) {

				if ( ! empty( $this->p->options[ $opt_pre . '_name' ] ) && $this->p->options[ $opt_pre . '_name' ] !== 'none' ) {

					$wp_filter_name = $this->p->options[ $opt_pre . '_name' ];

					add_filter( $wp_filter_name, array( $this, 'add_og_ns_attributes' ),
						 ( isset( $this->p->options[ $opt_pre . '_prio' ] ) ?
						 	(int) $this->p->options[ $opt_pre . '_prio' ] : 100 ), 1 );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'added add_og_ns_attributes filter for ' . $wp_filter_name );
					}

					break;	// Stop here.

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipping add_og_ns_attributes for ' . $opt_pre . ' - filter name is empty or disabled' );
				}
			}

			$this->p->util->add_plugin_filters( $this, array( 
				'og_data_https_ogp_me_ns_article' => 2,
				'og_data_https_ogp_me_ns_book'    => 2,
				'og_data_https_ogp_me_ns_product' => 2,
			) );
		}

		public function add_og_ns_attributes( $html_attr ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array (
					'html_attr' => $html_attr,
				) );
			}

			$use_post = apply_filters( $this->p->lca . '_use_post', false );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'required call to get_page_mod()' );
			}

			$mod = $this->p->util->get_page_mod( $use_post );

			$type_id = $this->p->og->get_mod_og_type( $mod );

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

			$og_ns = apply_filters( $this->p->lca . '_og_ns', $og_ns, $mod );

			if ( SucomUtil::is_amp() ) {

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

				$prefix_value = '';

				foreach ( $og_ns as $name => $url ) {

					if ( false === strpos( $prefix_value, ' ' . $name . ': ' . $url ) ) {
						$prefix_value .= ' ' . $name . ': ' . $url;
					}
				}

				$html_attr .= ' prefix="' . trim( $prefix_value ) . '"';
			}

			return trim( $html_attr );
		}

		public function filter_og_data_https_ogp_me_ns_article( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			return $mt_og;
		}

		public function filter_og_data_https_ogp_me_ns_book( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! isset( $mt_og[ 'book:author' ] ) ) {
			}

			return $mt_og;
		}

		public function filter_og_data_https_ogp_me_ns_product( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * If we have a GTIN number, try to improve the assigned property name.
			 */
			WpssoOpenGraph::check_gtin_mt_value( $mt_og, $prefix = 'product' );

			/**
			 * Include variations (aka product offers) if available.
			 */
			if ( ! empty( $mt_og[ 'product:offers' ] ) && is_array( $mt_og[ 'product:offers' ] ) ) {

				foreach ( $mt_og[ 'product:offers' ] as $num => $offer ) {

					foreach( $offer as $mt_name => $mt_value ) {

						if ( isset( $this->p->cf[ 'head' ][ 'og_type_array' ][ 'product' ][ $mt_name ] ) ) {

							$mt_og[ 'product' ][ $num ][ $mt_name ] = $mt_value;

							if ( isset( $mt_og[ $mt_name ] ) ) {
								unset ( $mt_og[ $mt_name ] );
							}
						}
					}

					/**
					 * If we have a GTIN number, try to improve the assigned property name.
					 */
					WpssoOpenGraph::check_gtin_mt_value( $offer );
				}

			} elseif ( isset( $mt_og[ 'product:price:amount' ] ) ) {

				if ( is_numeric( $mt_og[ 'product:price:amount' ] ) ) {	// Allow for price of 0.

					if ( empty( $mt_og[ 'product:price:currency' ] ) ) {
						$mt_og[ 'product:price:currency' ] = $this->p->options[ 'plugin_def_currency' ];
					}

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'product price amount must be numeric' );
					}

					unset( $mt_og[ 'product:price:amount' ] );
					unset( $mt_og[ 'product:price:currency' ] );
				}
			}

			return $mt_og;
		}
	}
}
