<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegEcomAbstractWooCommerceBrands' ) ) {

	class WpssoIntegEcomAbstractWooCommerceBrands {

		protected $p;
		protected $brand_tax_slug  = '';
		protected $brand_image_key = '';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			SucomUtilOptions::set_key_value_disabled( 'plugin_cf_product_brand', '', $this->p->options );

			SucomUtilOptions::set_key_value_locale_disabled( 'plugin_attr_product_brand', '', $this->p->options );

			$this->p->util->add_plugin_filters( $this, array(
				'term_image_ids'                     => 4,
				'og_ecom_woocommerce'                => 2,
				'get_md_defaults_woocommerce'        => 2,
				'json_data_https_schema_org_product' => 5,
			), $prio = 1000 );
		}

		public function filter_term_image_ids( $image_ids, $size_names, $term_id, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $this->brand_tax_slug ) || empty( $this->brand_image_key ) ) {	// Nothing to do.

				return $image_ids;
			}

			if ( $this->brand_tax_slug === $mod[ 'tax_slug' ] ) {

				$pid = get_metadata( 'term', $term_id, $this->brand_image_key, $single = true );

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

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error getting terms for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' = ' . $terms->get_error_message() );
				}

			} elseif ( is_array( $terms ) ) {

				foreach ( $terms as $term_num => $term_obj ) {

					if ( empty( $term_obj->term_id ) || empty( $term_obj->name ) ) {	// Just in case.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipping term object #' . $term_num . ' empty id or name' );
						}

					} elseif ( ! empty( $mt_ecom[ 'product:brand' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipping term id #' . $term_obj->term_id . ' product:brand already defined' );
						}

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'checking if term id #' . $term_obj->term_id . ' is noindex' );
						}

						if ( $this->p->util->robots->is_noindex( 'term', $term_obj->term_id ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipping term id #' . $term_obj->term_id . ' is noindex' );
							}

						} else {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'adding term id #' . $term_obj->term_id . ' name = ' . $term_obj->name );
							}

							$mt_ecom[ 'product:brand' ] = $term_obj->name;
						}
					}
				}
			}

			return $mt_ecom;
		}

		/*
		 * See WpssoIntegEcomWooCommerce->filter_get_md_defaults().
		 * See WpssoIntegEcomWooCommerce->filter_get_post_options().
		 */
		public function filter_get_md_defaults_woocommerce( array $md_defs, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mt_ecom = $this->filter_og_ecom_woocommerce( array(), $mod );

			if ( ! empty( $mt_ecom[ 'product:brand' ] ) ) {

				$md_defs[ 'product_brand' ] = $mt_ecom[ 'product:brand' ];
			}

			return $md_defs;
		}

		/*
		 * Note that product group variants will automatically inherit the brand property from the main product.
		 *
		 * See WpssoConfig::$cf[ 'form' ][ 'inherit_variant_props' ].
		 * See WpssoJsonTypeProductGroup->filter_json_data_https_schema_org_productgroup().
		 */
		public function filter_json_data_https_schema_org_product( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $this->brand_tax_slug ) ) {	// Nothing to do.

				return $json_data;
			}

			$json_ret = array();
			$brands   = array();

			/*
			 * Move any existing properties (from shortcodes, for example) so we can filter them and add new ones.
			 */
			if ( isset( $json_data[ 'brand' ] ) ) {
				
				/*
				 * Example:
				 *
				 * Array (
				 * 	[@context] => https://schema.org
				 * 	[@type] => Brand
				 * 	[name] => Brand A
				 * )
				 */
				if ( isset( $json_data[ 'brand' ][ 0 ] ) ) {	// Has an array of one or more brands.

					$brands = $json_data[ 'brand' ];

				} elseif ( ! empty( $json_data[ 'brand' ] ) ) {

					$brands[] = $json_data[ 'brand' ];
				}

				unset( $json_data[ 'brand' ] );
			}

			/*
			 * Allows getting the array key number using the brand name.
			 */
			$brands_by_name = array_flip( wp_list_pluck( $brands, 'name' ) );

			/*
			 * Retrieve the terms of the taxonomy that are attached to the post ID.
			 *
			 * get_the_terms() returns an array of WP_Term objects, false if there are no terms (or the post does not
			 * exist), or a WP_Error object on failure.
			 */
			$terms = get_the_terms( $mod[ 'id' ], $this->brand_tax_slug );

			if ( is_wp_error( $terms ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error getting terms for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' = ' . $terms->get_error_message() );
				}

			} elseif ( is_array( $terms ) ) {

				foreach ( $terms as $term_num => $term_obj ) {

					if ( empty( $term_obj->term_id ) || empty( $term_obj->name ) ) {	// Just in case.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipping term object #' . $term_num . ' empty id or name' );
						}

					} else {
					
						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'checking if term id #' . $term_obj->term_id . ' is noindex' );
						}

						if ( $this->p->util->robots->is_noindex( 'term', $term_obj->term_id ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipping term id #' . $term_obj->term_id . ' is noindex' );
							}

						} else {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'adding term id #' . $term_obj->term_id . ' name = ' . $term_obj->name );
							}

							$term_mod   = $this->p->term->get_mod( $term_obj->term_id, $term_obj->taxonomy );
							$term_mt_og = $this->p->og->get_array( $term_mod, $size_names = 'schema', $md_pre = array( 'schema', 'og' ) );
	
							/*
							 * WpssoSchema->get_json_data() returns a two dimensional array of json data unless $single is true.
							 */
							$single_brand = $this->p->schema->get_json_data( $term_mod, $term_mt_og,
								$page_type_id = 'brand', $is_main = false, $single = true );
	
							/*
							 * Maybe overwrite an existing brand with the same name.
							 */
							if ( isset( $brands_by_name[ $term_obj->name ] ) ) {
		
								$key_num = $brands_by_name[ $term_obj->name ];	// Get the array key number using the brand name.
		
								$brands[ $key_num ] = $single_brand;
		
							} else {
		
								$brands[] = $single_brand;	// Add a new brand.
	
								$brands_by_name[ $term_obj->name ] = SucomUtil::array_key_last( $brands );	// Prevent duplicates.
							}
						}
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'brands', $brands );
			}

			if ( ! empty( $brands ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding first brands array element to json' );
				}

				$json_ret[ 'brand' ][] = reset( $brands );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
