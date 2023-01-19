<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegEcomEdd' ) ) {

	class WpssoIntegEcomEdd {

		private $p;	// Wpsso class object.

		private $prod_post_type = 'download';
		private $cat_taxonomy   = 'download_category';
		private $tag_taxonomy   = 'download_tag';
		private $page_ids       = array(
			'account'     => -1,
			'cart'        => -1,
			'checkout'    => -1,
			'transaction' => -1,
			'shop'        => -1,
		);

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->page_ids[ 'checkout' ] = edd_get_option( 'purchase_page' );	// Primary checkout page.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'page_ids', $this->page_ids );
			}

			$this->p->util->add_plugin_filters( $this, array(
				'head_cache_index' => 1,
				'schema_type_id'   => 3,
				'primary_tax_slug' => 2,	// See WpssoPost->get_primary_terms().
				'the_content_seed' => 2,
				'description_seed' => 4,
				'get_md_defaults'  => 2,
				'get_post_options' => 3,
				'og_seed'          => 2,
				'tag_names_seed'   => 2,
			) );
		}

		public function filter_head_cache_index( $cache_index ) {

			return $cache_index . '_currency:' . $this->get_currency();
		}

		public function filter_schema_type_id( $type_id, array $mod, $is_custom ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $is_custom ) {	// Skip if we have a custom type from the post meta.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: custom schema type = ' . $type_id );
				}

				return $type_id;
			}

			if ( $mod[ 'is_post' ] ) {

				if ( $mod[ 'id' ] === $this->page_ids[ 'checkout' ] ) {

					$type_id = 'webpage.checkout';
				}
			}

			return $type_id;
		}

		public function filter_primary_tax_slug( $tax_slug, $mod ) {

			if ( $this->prod_post_type === $mod[ 'post_type' ] ) {

				if ( 'category' === $tax_slug ) {

					$tax_slug = $this->cat_taxonomy;

				} elseif ( 'tag' === $tax_slug ) {

					$tax_slug = $this->tag_taxonomy;
				}
			}

			return $tax_slug;
		}

		public function filter_the_content_seed( $content, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] ) {

				if ( $mod[ 'id' ] === $this->page_ids[ 'checkout' ] ) {

					$content = false;
				}
			}

			return $content;
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] ) {

				if ( $mod[ 'id' ] === $this->page_ids[ 'checkout' ] ) {

					$desc_text = 'Checkout Page';
				}
			}

			return $desc_text;
		}

		public function filter_get_md_defaults( array $md_defs, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtil::is_mod_post_type( $mod, $this->prod_post_type ) ) {

				return $md_defs;
			}

			$prod = $this->get_product_details( $mod );

			$md_defs[ 'og_type' ]                  = 'product';
			$md_defs[ 'product_retailer_part_no' ] = $prod[ 'retailer_part_no' ];	// Product SKU.
			$md_defs[ 'product_price' ]            = $prod[ 'price' ];
			$md_defs[ 'product_price_type' ]       = $prod[ 'price_type' ];
			$md_defs[ 'product_currency' ]         = $prod[ 'currency' ];

			return $md_defs;
		}

		/**
		 * Disable options where the reference value is the e-commerce plugin.
		 */
		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			$prod_opts = $this->filter_get_md_defaults( array(), $mod );

			foreach ( $prod_opts as $key => $val ) {

				$md_opts[ $key ]               = $val;
				$md_opts[ $key . ':disabled' ] = true;
			}

			return $md_opts;
		}

		public function filter_og_seed( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtil::is_mod_post_type( $mod, $this->prod_post_type ) ) {

				return $mt_og;	// Stop here.
			}

			$prod = $this->get_product_details( $mod );

			/**
			 * Get the pre-sorted product meta tags, with the og:type meta tag top-most in the array.
			 */
			$mt_ecom = SucomUtil::get_mt_product_seed( 'product', array( 'og:type' => 'product' ) );

			$mt_ecom[ 'product:retailer_item_id' ] = $prod[ 'retailer_item_id' ];	// Product ID.
			$mt_ecom[ 'product:retailer_part_no' ] = $prod[ 'retailer_part_no' ];	// Product SKU.
			$mt_ecom[ 'product:price_type' ]       = $prod[ 'price_type' ];
			$mt_ecom[ 'product:price:amount' ]     = $prod[ 'price' ];
			$mt_ecom[ 'product:price:currency' ]   = $prod[ 'currency' ];

			/**
			 * Retrieve the terms of the taxonomy that are attached to the post ID.
			 *
			 * get_the_terms() returns an array of WP_Term objects, false if there are no terms (or the post does not
			 * exist), or a WP_Error object on failure.
			 */
			$terms = get_the_terms( $mod[ 'id' ], $this->tag_taxonomy );

			if ( is_array( $terms ) ) {

				foreach( $terms as $term ) {

					$mt_ecom[ 'product:tag' ][] = $term->name;
				}
			}

			$mt_ecom = apply_filters( 'wpsso_og_ecom_edd', $mt_ecom, $mod );

			return array_merge( $mt_og, $mt_ecom );
		}

		public function filter_tag_names_seed( $tags, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtil::is_mod_post_type( $mod, $this->prod_post_type ) ) {

				return $tags;
			}

			return wp_get_post_terms( $mod[ 'id' ], $this->tag_taxonomy, $args = array( 'fields' => 'names' ) );
		}

		private function get_product_details( $mod ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ] ) ) {

				return $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ];
			}

			$local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ] = array();	// Create the array.

			$prod =& $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ];	// Use shorter variable name.

			$prod[ 'retailer_item_id' ] = $mod[ 'id' ];				// Product ID.
			$prod[ 'retailer_part_no' ] = edd_get_download_sku( $mod[ 'id' ] );	// Product SKU.
			$prod[ 'price' ]            = $this->get_product_price( $mod[ 'id' ] );
			$prod[ 'price_type' ]       = 'https://schema.org/ListPrice';
			$prod[ 'currency' ]         = $this->get_currency();

			return $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ];
		}

		private function get_product_price( $product_id ) {

			if ( edd_has_variable_prices( $product_id ) ) {

				$product_price = edd_get_lowest_price_option( $product_id );

			} else {

				$product_price = edd_get_download_price( $product_id );
			}

			$product_price = apply_filters( 'wpsso_product_price', $product_price, $product_id );

			return $product_price;
		}

		private function get_currency() {

			static $currency = null;

			if ( null === $currency ) {	// Get value only once.

				$currency = edd_get_currency();
				$currency = apply_filters( 'wpsso_currency', $currency );
			}

			return $currency;
		}
	}
}
