<?php
/*
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

if ( ! class_exists( 'WpssoIntegEcomAbstractWoocommerceBrands' ) ) {

	class WpssoIntegEcomAbstractWoocommerceBrands {

		protected $p;
		protected $brand_tax_slug  = '';
		protected $brand_image_key = '';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->options[ 'plugin_cf_product_brand' ]          = '';
			$this->p->options[ 'plugin_cf_product_brand:disabled' ] = true;

			$this->p->options[ 'plugin_attr_product_brand' ]          = '';
			$this->p->options[ 'plugin_attr_product_brand:disabled' ] = true;

			$this->p->util->add_plugin_filters( $this, array(
				'term_image_ids'                     => 4,
				'og_ecom_woocommerce'                => 2,
				'get_md_defaults_woocommerce'        => 2,
				'json_data_https_schema_org_product' => 5,
			), $prio = 1000 );
		}

		public function filter_term_image_ids( $image_ids, $size_names, $term_id, $mod ) {

			if ( empty( $this->brand_tax_slug ) || empty( $this->brand_image_key ) ) {	// Nothing to do.

				return $image_ids;
			}

			if ( $this->brand_tax_slug === $mod[ 'tax_slug' ] ) {

				if ( function_exists( 'get_term_meta' ) ) {	// Since WP v4.4.

					$pid = get_term_meta( $term_id, $key = $this->brand_image_key, $single = true );

				} else {

					$pid = get_metadata( 'woocommerce_term', $term_id, $key = $this->brand_image_key, $single = true );
				}

				if ( ! empty( $pid ) ) {

					$image_ids[] = $pid;
				}
			}

			return $image_ids;
		}

		/*
		 * The 'wpsso_og_ecom_woocommerce' filter is only applied to WooCommerce products, so we don't have to check the post type.
		 */
		public function filter_og_ecom_woocommerce( $mt_ecom, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $this->brand_tax_slug ) ) {	// Nothing to do.

				return $mt_ecom;
			}

			if ( ! empty( $mt_ecom[ 'product:brand' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product:brand already defined = "' . $mt_ecom[ 'product:brand' ] . '"' );
				}

				return $mt_ecom;
			}

			/*
			 * Retrieve the terms of the taxonomy that are attached to the post ID.
			 *
			 * get_the_terms() returns an array of WP_Term objects, false if there are no terms (or the post does not
			 * exist), or a WP_Error object on failure.
			 */
			$terms = get_the_terms( $mod[ 'id' ], $this->brand_tax_slug );

			if ( is_wp_error( $terms ) ) {

				$notice_msg = sprintf( 'WooCommerce brands error getting "%1$s" taxonomy terms for %2$s ID %3$d: %4$s',
					$this->brand_tax_slug, $mod[ 'name' ], $mod[ 'id' ], $terms->get_error_message() );

				$notice_key = $this->brand_tax_slug . '-' . $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-' . $terms->get_error_message();

				$this->p->notice->err( $notice_msg, $user_id = null, $notice_key );

				SucomUtil::safe_error_log( $notice_msg );

			} elseif ( is_array( $terms ) ) {

				if ( $term_names = wp_list_pluck( $terms, 'name' ) ) {

					/*
					 * There can only be one Open Graph brand meta tag.
					 */
					$mt_ecom[ 'product:brand' ] = reset( $term_names );
				}
			}

			return $mt_ecom;
		}

		public function filter_get_md_defaults_woocommerce( array $md_defs, array $mod ) {

			$mt_ecom = $this->filter_og_ecom_woocommerce( array(), $mod );

			if ( ! empty( $mt_ecom[ 'product:brand' ] ) ) {

				$md_defs[ 'product_brand' ] = $mt_ecom[ 'product:brand' ];
			}

			return $md_defs;
		}

		public function filter_json_data_https_schema_org_product( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $this->brand_tax_slug ) ) {	// Nothing to do.

				return $json_data;
			}

			$json_ret = array();

			$all_brands = array();

			/*
			 * Move any existing properties (from shortcodes, for example) so we can filter them and add new ones.
			 */
			if ( isset( $json_data[ 'brand' ] ) ) {

				if ( isset( $json_data[ 'brand' ][ 0 ] ) ) {	// Has an array of brands.

					$all_brands = $json_data[ 'brand' ];

				} elseif ( ! empty( $json_data[ 'brand' ] ) ) {

					$all_brands[] = $json_data[ 'brand' ];	// Markup for a single brand.
				}

				unset( $json_data[ 'brand' ] );
			}

			/*
			 * Allows getting the array key number using the brand name.
			 */
			$brand_names = array_flip( wp_list_pluck( $all_brands, 'name' ) );

			/*
			 * Retrieve the terms of the taxonomy that are attached to the post ID.
			 *
			 * get_the_terms() returns an array of WP_Term objects, false if there are no terms (or the post does not
			 * exist), or a WP_Error object on failure.
			 */
			$terms = get_the_terms( $mod[ 'id' ], $this->brand_tax_slug );

			if ( is_wp_error( $terms ) ) {

				/*
				 * Nothing to do - the error will have been already reported in $this->filter_og_ecom_woocommerce().
				 */

			} elseif ( is_array( $terms ) ) {

				foreach( $terms as $term ) {

					$term_mod = $this->p->term->get_mod( $term->term_id, $term->taxonomy );

					$term_mt_og = $this->p->og->get_array( $term_mod, $size_names = 'schema', $md_pre = array( 'schema', 'og' ) );

					/*
					 * WpssoSchema->get_json_data() returns a two dimensional array of json data unless $single is true.
					 */
					$single_brand = $this->p->schema->get_json_data( $term_mod, $term_mt_og, $page_type_id = 'brand', $is_main = false, $single = true );

					/*
					 * Maybe overwrite an existing brand with the same name.
					 */
					if ( isset( $brand_names[ $term->name ] ) ) {

						$key_num = $brand_names[ $term->name ];	// Get the array key number using the brand name.

						$all_brands[ $key_num ] = $single_brand;

					} else {

						$all_brands[] = $single_brand;	// Add a new brand.

						$brand_names[ $term->name ] = SucomUtil::array_key_last( $all_brands );	// Prevent duplicates.
					}
				}
			}

			if ( ! empty( $all_brands ) ) {

				$json_ret[ 'brand' ] = $all_brands;
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
