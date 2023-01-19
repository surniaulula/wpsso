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

if ( ! class_exists( 'WpssoIntegEcomWoocommerce' ) ) {

	class WpssoIntegEcomWoocommerce {

		private $p;	// Wpsso class object.

		private $prod_post_type = 'product';
		private $var_post_type  = 'product_variation';
		private $cat_taxonomy   = 'product_cat';
		private $tag_taxonomy   = 'product_tag';
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

			$this->page_ids[ 'account' ]  = wc_get_page_id( 'myaccount' );	// Returns -1 if no page selected.
			$this->page_ids[ 'cart' ]     = wc_get_page_id( 'cart' );	// Returns -1 if no page selected.
			$this->page_ids[ 'checkout' ] = wc_get_page_id( 'checkout' );	// Returns -1 if no page selected.
			$this->page_ids[ 'shop' ]     = wc_get_page_id( 'shop' );	// Returns -1 if no page selected.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'page_ids', $this->page_ids );
			}

			/*
			 * Check for possible missing page ID selections.
			 */
			if ( is_admin() ) {

				add_action( 'wp_loaded', array( $this, 'check_woocommerce_pages' ), 10, 0 );

				add_action( 'woocommerce_product_options_attributes', array( $this, 'show_product_attributes_footer' ), -1000, 0 );
			}

			/*
			 * Clear the post ID cache after WooCommerce updates the product object on the front-end (or back-end).
			 */
			add_action( 'woocommerce_after_product_object_save', array( $this, 'maybe_clear_cache' ), 10, 2 );

			/*
			 * Maybe load missing WooCommerce front-end libraries for 'the_content' filter.
			 */
			$this->p->util->add_plugin_actions( $this, array(
				'admin_post_head'        => 1,
				'scheduled_task_started' => 1,
			) );

			$this->p->util->add_plugin_filters( $this, array(
				'url_query_cache_disable'   => 4,
				'head_cache_index'          => 1,
				'use_post'                  => 1,
				'get_post_mod'              => 2,
				'schema_type_id'            => 3,
				'primary_tax_slug'          => 2,	// See WpssoPost->get_primary_terms().
				'the_content_seed'          => 2,
				'description_seed'          => 4,
				'attached_image_ids'        => 2,
				'term_image_ids'            => 3,
				'get_md_defaults'           => 2,
				'get_post_options'          => 3,
				'og_seed'                   => 2,
				'tag_names_seed'            => 2,
				'import_product_attributes' => 3,
			) );

			/*
			 * Optionally add the Pinterest image to the WooCommerce template for displaying product archives,
			 * including the main shop page which is a post type archive.
			 */
			if ( ! empty( $this->p->options[ 'pin_add_img_html' ] ) ) {

				add_action( 'woocommerce_archive_description', array( $this->p->pinterest, 'show_pinterest_img_html' ) );
			}

			$this->disable_options_keys();
		}

		/*
		 * Since WPSSO Core v14.0.0.
		 */
		public function disable_options_keys() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$dimension_unit_text = get_option( 'woocommerce_dimension_unit', $default = 'cm' );
			$weight_unit_text    = get_option( 'woocommerce_weight_unit', $default = 'kg' );
			$weight_unit_text    = 'lbs' === $weight_unit_text ? 'lb' : $weight_unit_text;	// WooCommerce uses 'lbs' and WPSSO uses 'lb'.

			foreach ( array(
				'og_def_dimension_units'                  => $dimension_unit_text,	// Default Dimension Units.
				'og_def_weight_units'                     => $weight_unit_text,		// Default Weight Units.
				'plugin_cf_product_retailer_part_no'      => '',
				'plugin_cf_product_shipping_length_value' => '',
				'plugin_cf_product_shipping_length_units' => '',
				'plugin_cf_product_shipping_width_value'  => '',
				'plugin_cf_product_shipping_width_units'  => '',
				'plugin_cf_product_shipping_height_value' => '',
				'plugin_cf_product_shipping_height_units' => '',
				'plugin_cf_product_shipping_weight_value' => '',
				'plugin_cf_product_shipping_weight_units' => '',
			) as $opt_key => $opt_val ) {

				$this->p->options[ $opt_key ] = $opt_val;

				$this->p->options[ $opt_key . ':disabled' ] = true;
			}
		}

		public function check_woocommerce_pages() {

			$wc_advanced_msg = sprintf( __( 'Please select a page in the <a href="%s">WooCommerce Settings &gt; Advanced &gt; Page setup</a> section.',
				'wpsso' ), get_admin_url( $blog_id = null, 'admin.php?page=wc-settings&tab=advanced&section' ) );

			$wc_products_msg = sprintf( __( 'Please select a page in the <a href="%s">WooCommerce Settings &gt; Products &gt; General</a> section.',
				'wpsso' ), get_admin_url( null, 'admin.php?page=wc-settings&tab=products&section' ) );

			foreach ( array(
				'account'  => __( 'My account page', 'woocommerce' ),
				'cart'     => __( 'Cart page', 'woocommerce' ),
				'checkout' => __( 'Checkout page', 'woocommerce' ),
				'shop'     => __( 'Shop page', 'woocommerce' ),
			) as $page_type => $label_transl ) {

				if ( ! is_int( $this->page_ids[ $page_type ] ) || $this->page_ids[ $page_type ] < 1 ) {

					$this->p->notice->warn( sprintf( __( 'The WooCommerce "%1$s" option value is empty.', 'wpsso' ),
						$label_transl ) . ' ' . ( 'shop' === $page_type ? $wc_products_msg : $wc_advanced_msg ) );
				}
			}
		}

		public function show_product_attributes_footer() {

			global $post;

			$product        = $this->p->util->wc->get_product( $post->ID );
			$attr_md_index  = WpssoConfig::get_attr_md_index();	// Uses a local cache.
			$wc_attributes  = $product->get_attributes();
			$wc_attr_labels = array();
			$suggest_names  = array();

			foreach ( $wc_attributes as $attribute ) {

				$attr_name  = $attribute->get_name();
				$attr_label = wc_attribute_label( $attr_name, $product );

				$wc_attr_labels[ $attr_label ] = true;
			}

			foreach ( $attr_md_index as $opt_attr_key => $md_key ) {

				if ( empty( $md_key ) ) {	// Just in case.

					continue;

				} elseif ( empty( $this->p->options[ $opt_attr_key ] ) ) {

					continue;
				}

				$attr_name = $this->p->options[ $opt_attr_key ];	// Example: 'Size Group'.

				if ( empty( $wc_attr_labels[ $attr_name ] ) ) {

					$suggest_names[] = $attr_name;
				}
			}

			$suggest_transl = __( 'Suggested attributes:', 'wpsso' );
			$suggest_list   = implode( ', ', $suggest_names );

			if ( current_user_can( 'manage_options' ) ) {

				$suggest_transl = $this->p->util->get_admin_url( 'advanced#sucom-tabset_metadata-tab_product_attrs', $suggest_transl );
			}

			echo '<div class="toolbar">';
			echo '<strong>' . $suggest_transl . '</strong> ' . $suggest_list;
			echo '</div>';
		}

		/*
		 * Clear the post ID cache after WooCommerce updates the product object on the front-end (or back-end). The
		 * WordPress 'save_post' action runs before this action (on the back-end) and WpssoPost->clear_cache() uses a
		 * static cache to clear the cache only once per post ID, so it is safe to call WpssoPost->clear_cache() more than
		 * once.
		 */
		public function maybe_clear_cache( $product, $data_store ) {

			$product_id = $this->p->util->wc->get_product_id( $product );	// Returns product id from product object.

			if ( $product_id ) {

				/*
				 * Uses a local cache to clear the cache only once per post ID per page load.
				 */
				$this->p->post->clear_cache( $product_id, $rel_id = false );
			}
		}

		public function action_admin_post_head( $mod ) {

			$user_id = get_current_user_id();

			$this->include_frontend_libs( $user_id );
		}

		public function action_scheduled_task_started( $user_id ) {

			$this->include_frontend_libs( $user_id );
		}

		/*
		 * Maybe load missing WooCommerce front-end libraries for 'the_content' filter.
		 */
		public function include_frontend_libs( $user_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			WC()->frontend_includes();

			WC()->initialize_session();	// Since WC v3.6.4.
		}

		/*
		 * WooCommerce product attributes do not have their own webpages - product attribute query strings are used to
		 * pre-fill product selections on the front-end. The WpssoIntegEcomWoocommerce->filter_url_query_cache_disable()
		 * removes all product attributes from the request URL, and if the $request_url and $canonical_url values match,
		 * the filter will return false.
		 */
		public function filter_url_query_cache_disable( $bool, $request_url, $canonical_url, $mod ) {

			if ( is_product() ) {

				if ( false !== strpos( $request_url, 'attribute_' ) ) {

					$request_url = preg_replace( '/[\?\&]attribute_[^=]+=[^\&]*/', '', $request_url );

					if ( $request_url === $canonical_url ) {

						return false;
					}
				}
			}

			return $bool;
		}

		public function filter_head_cache_index( $cache_index ) {

			return $cache_index . '_currency:' . $this->get_currency();
		}

		public function filter_use_post( $use_post ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( in_the_loop() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: in the loop' );
				}

				return $use_post;

			} elseif ( is_account_page() ) {

				$use_post = $this->page_ids[ 'account' ];

			} elseif ( is_cart() ) {

				$use_post = $this->page_ids[ 'cart' ];

			} elseif ( is_checkout() ) {

				$use_post = $this->page_ids[ 'checkout' ];

			} elseif ( is_shop() ) {

				$use_post = $this->page_ids[ 'shop' ];

			} elseif ( is_product() ) {

				$use_post = true;

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: not a woocommerce page' );
				}

				return $use_post;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'woocommerce use_post is ' . SucomUtil::get_use_post_string( $use_post ) );
			}

			return $use_post;
		}

		public function filter_get_post_mod( $mod, $post_id ) {

			if ( $mod[ 'id' ] === $this->page_ids[ 'shop' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'post is shop page' );
				}

				$mod[ 'post_type' ]            = $this->prod_post_type;
				$mod[ 'is_post_type_archive' ] = true;
				$mod[ 'is_archive' ]           = true;
			}

			return $mod;
		}

		public function filter_schema_type_id( $type_id, array $mod, $is_custom ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $is_custom ) {	// Skip if we have a custom type from the post meta.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: custom schema type is ' . $type_id );
				}

				return $type_id;
			}

			if ( $mod[ 'is_post' ] ) {

				if ( $mod[ 'id' ] === $this->page_ids[ 'account' ] ) {

					$type_id = 'webpage.profile';

				} elseif ( $mod[ 'id' ] === $this->page_ids[ 'cart' ] ) {

					$type_id = 'webpage.checkout';

				} elseif ( $mod[ 'id' ] === $this->page_ids[ 'checkout' ] ) {

					$type_id = 'webpage.checkout';

				} elseif ( $mod[ 'id' ] === $this->page_ids[ 'shop' ] ) {

					$type_id = $this->p->schema->get_schema_type_id_for( 'pta_' . $this->prod_post_type );
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

				if ( $mod[ 'id' ] === $this->page_ids[ 'account' ] ) {

					$content = false;

				} elseif ( $mod[ 'id' ] === $this->page_ids[ 'cart' ] ) {

					$content = false;

				} elseif ( $mod[ 'id' ] === $this->page_ids[ 'checkout' ] ) {

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

				if ( $mod[ 'id' ] === $this->page_ids[ 'account' ] ) {

					$desc_text = 'Account Page';

				} elseif ( $mod[ 'id' ] === $this->page_ids[ 'cart' ] ) {

					$desc_text = 'Shopping Cart';

				} elseif ( $mod[ 'id' ] === $this->page_ids[ 'checkout' ] ) {

					$desc_text = 'Checkout Page';
				}
			}

			return $desc_text;
		}

		/*
		 * Note that images can only be attached to a post ID.
		 *
		 * See WpssoMedia->get_attached_images().
		 */
		public function filter_attached_image_ids( $image_ids, $post_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mod = $this->p->post->get_mod( $post_id );

			if ( ! SucomUtil::is_mod_post_type( $mod, $this->prod_post_type ) ) {

				return $image_ids;

			} elseif ( false === ( $product = $this->p->util->wc->get_product( $post_id ) ) ) {

				return $image_ids;
			}

			$attach_ids = null;

			if ( is_callable( array( $product, 'get_gallery_image_ids' ) ) ) {

				$attach_ids = $product->get_gallery_image_ids();

			} elseif ( is_callable( array( $product, 'get_gallery_attachment_ids' ) ) ) {

				$attach_ids = $product->get_gallery_attachment_ids();
			}

			if ( is_array( $attach_ids ) ) {

				$image_ids = array_merge( $image_ids, $attach_ids );
			}

			return $image_ids;	// array_unique() is applied to the returned array.
		}

		public function filter_term_image_ids( $image_ids, $size_names, $term_id ) {

			if ( SucomUtil::is_term_tax_slug( $term_id, $this->cat_taxonomy ) || SucomUtil::is_term_tax_slug( $term_id, $this->tag_taxonomy ) ) {

				if ( function_exists( 'get_term_meta' ) ) {

					$pid = get_term_meta( $term_id, $key = 'thumbnail_id', $single = true );

				} else {

					$pid = get_metadata( 'woocommerce_term', $term_id, $key = 'thumbnail_id', $single = true );
				}

				if ( ! empty( $pid ) ) {

					$image_ids[] = $pid;
				}
			}

			return $image_ids;
		}

		public function filter_get_md_defaults( array $md_defs, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtil::is_mod_post_type( $mod, $this->prod_post_type ) ) {

				return $md_defs;

			} elseif ( false === ( $product = $this->p->util->wc->get_product( $mod[ 'id' ] ) ) ) {

				return $md_defs;
			}

			$md_defs[ 'og_type' ] = 'product';

			if ( function_exists( 'is_sitemap' ) && is_sitemap() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping getting md defaults for sitemap' );
				}

				return $md_defs;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting product defaults' );	// Begin timer.
			}

			$product_price = $this->get_product_price( $product );
			$currency      = $this->get_currency();
			$include_vat   = $this->p->options[ 'plugin_product_include_vat' ] ? true : false;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product_price = ' . $product_price );
				$this->p->debug->log( 'currency = ' . $currency );
				$this->p->debug->log( 'include_vat = ' . ( $include_vat ? 'true' : 'false' ) );
			}

			$md_defs[ 'product_price' ]            = $this->get_product_price_formatted( $product, $product_price, $include_vat );
			$md_defs[ 'product_currency' ]         = $currency;
			$md_defs[ 'product_retailer_part_no' ] = $product->get_sku();	// Product SKU.

			/*
			 * Add product availability.
			 *
			 * See https://woocommerce.github.io/code-reference/classes/WC-Product.html#method_is_in_stock
			 *
			 * Hook 'woocommerce_product_is_in_stock' (returns true or false) to customize the "in stock" status.
			 */
			if ( $product->is_in_stock() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is in stock' );
				}

				$md_defs[ 'product_avail' ] = 'https://schema.org/InStock';

			} elseif ( $product->is_on_backorder() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is on backorder' );
				}

				$md_defs[ 'product_avail' ] = 'https://schema.org/BackOrder';

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is out of stock' );
				}

				$md_defs[ 'product_avail' ] = 'https://schema.org/OutOfStock';
			}

			/*
			 * Get product shipping dimensions and weight.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting product shipping dimensions' );
			}

			list(
				$md_defs[ 'product_shipping_length_value' ],
				$md_defs[ 'product_shipping_length_units' ],
				$md_defs[ 'product_shipping_width_value' ],
				$md_defs[ 'product_shipping_width_units' ],
				$md_defs[ 'product_shipping_height_value' ],
				$md_defs[ 'product_shipping_height_units' ],
				$md_defs[ 'product_shipping_weight_value' ],
				$md_defs[ 'product_shipping_weight_units' ],
			) = $this->get_shipping_length_width_height_weight( $product );

			$md_defs = apply_filters( 'wpsso_get_md_defaults_woocommerce', $md_defs, $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting product defaults' );	// End timer.
			}

			return $md_defs;
		}

		/*
		 * Disable options where the value comes from the e-commerce plugin.
		 */
		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$prod_opts = $this->filter_get_md_defaults( array(), $mod );

			foreach ( $prod_opts as $opt_key => $opt_val ) {

				$md_opts[ $opt_key ] = $opt_val;

				$md_opts[ $opt_key . ':disabled' ] = true;
			}

			return $md_opts;
		}

		public function filter_og_seed( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtil::is_mod_post_type( $mod, $this->prod_post_type ) ) {

				return $mt_og;

			} elseif ( false === ( $product = $this->p->util->wc->get_product( $mod[ 'id' ] ) ) ) {

				return $mt_og;
			}

			$og_type      = 'product';
			$rating_meta  = 'rating';
			$worst_rating = 1;
			$best_rating  = 5;
			$have_schema  = $this->p->avail[ 'p' ][ 'schema' ] ? true : false;

			/*
			 * Get the pre-sorted product meta tags, with the og:type meta tag top-most in the array.
			 */
			$mt_ecom = SucomUtil::get_mt_product_seed( $og_type, array( 'og:type' => $og_type ) );

			$this->add_mt_product( $mt_ecom, $mod, $product );

			/*
			 * Add product offers.
			 */
			if ( apply_filters( 'wpsso_og_add_mt_offers', $have_schema, $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add offers meta tags is true' );
				}

				if ( $this->p->util->wc->is_product_variable( $product ) ) {

					/*
					 * Similar to the WooCommerce method, except it does not exclude out of stock variations.
					 */
					$avail_variations = $this->p->util->wc->get_available_variations( $product );	// Always returns an array.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( count( $avail_variations ) . ' variations returned' );
					}

					foreach( $avail_variations as $num => $variation ) {

						$offer_og = array();	// Start with an empty array.

						$this->add_mt_product( $offer_og, $mod, $variation );

						if ( ! empty( $offer_og ) ) {

							$mt_ecom[ $og_type . ':offers' ][] = $offer_og;
						}
					}
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add offers meta tags is false' );
			}

			/*
			 * Add product ratings.
			 */
			$wc_rating_enabled = 'yes' === get_option( 'woocommerce_enable_review_rating' ) ? true : false;
			$wc_rating_enabled = apply_filters( 'wpsso_og_add_wc_mt_rating', $wc_rating_enabled );

			$wc_reviews_enabled = 'yes' === get_option( 'woocommerce_enable_reviews' ) ? true : false;
			$wc_reviews_enabled = apply_filters( 'wpsso_og_add_wc_mt_reviews', $wc_reviews_enabled );

			$add_mt_rating = $this->p->avail[ 'p' ][ 'schema' ] ? true : false;

			if ( apply_filters( 'wpsso_og_add_mt_rating', true, $mod ) ) {	// Enabled by default.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add rating meta tags is true' );
				}

				/*
				 * Add rating meta tags if WooCommerce product reviews and review ratings are enabled.
				 */
				if ( $wc_reviews_enabled && $wc_rating_enabled ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'woocommerce reviews and ratings are enabled' );
					}

					$average_rating = (float) $product->get_average_rating();
					$rating_count   = (int) $product->get_rating_count();
					$review_count   = (int) $product->get_review_count();

					/*
					 * An average rating value must be greater than 0.
					 */
					if ( $average_rating > 0 ) {

						/*
						 * At least one rating or review is required.
						 */
						if ( $rating_count > 0 || $review_count > 0 ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'adding rating meta tags for product id ' . $mod[ 'id' ] );
							}

							$mt_ecom[ $og_type . ':rating:average' ] = $average_rating;
							$mt_ecom[ $og_type . ':rating:count' ]   = $rating_count;
							$mt_ecom[ $og_type . ':rating:worst' ]   = $worst_rating;
							$mt_ecom[ $og_type . ':rating:best' ]    = $best_rating;
							$mt_ecom[ $og_type . ':review:count' ]   = $review_count;

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( SucomUtil::preg_grep_keys( '/:(rating|review):/', $mt_ecom ) );
							}

						} else {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'rating and review count is invalid (must be greater than 0)' );
							}

							$notice_msg = sprintf( __( 'The rating and review counts provided by WooCommerce for product ID %d are invalid.',
								'wpsso' ), $mod[ 'id' ] ) . ' ';

							$notice_msg .= sprintf( __( 'The average rating is %.2f, but the rating count is %d and the review count is %d.',
								'wpsso' ), $average_rating, $rating_count, $review_count ) . ' ';

							$notice_msg .= __( 'The rating count or the review count must be greater than 0.', 'wpsso' );

							$this->p->notice->warn( $notice_msg );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'average rating is invalid (must be greater than 0)' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'woocommerce ratings are disabled' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add rating meta tags is false' );
			}

			/*
			 * Add product reviews.
			 */
			if ( apply_filters( 'wpsso_og_add_mt_reviews', $have_schema, $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add reviews meta tags is true' );
				}

				/*
				 * Add reviews array if WooCommerce product reviews are enabled.
				 */
				if ( $wc_reviews_enabled ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'woocommerce reviews are enabled' );
					}

					$mt_ecom[ $og_type . ':reviews' ] = $mod[ 'obj' ]->get_mt_reviews( $mod[ 'id' ], $rating_meta, $worst_rating, $best_rating );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'woocommerce reviews are disabled' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add reviews meta tags is false' );
			}

			$mt_ecom = (array) apply_filters( 'wpsso_og_ecom_woocommerce', $mt_ecom, $mod );

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

		/*
		 * $mixed must be a post object, product object, or variation array.
		 */
		public function filter_import_product_attributes( array $md_opts, array $mod, $mixed ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$is_variation = false;	// Default value.

			if ( function_exists( 'is_sitemap' ) && is_sitemap() ) {

				return $md_opts;

			} elseif ( $product = $this->p->util->wc->get_variation_product( $mixed ) ) {	// Product variation array.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using variation array' );
				}

				$is_variation = true;
				$variation    = $mixed;

			} elseif ( is_object( $mixed ) ) {

				if ( $mixed instanceof WC_Product ) {	// Product object.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'using product object' );
					}

					$product = $mixed;

				} elseif ( $mixed instanceof WP_Post ) {	// Post object.

					if ( SucomUtil::is_post_type( $mixed, $this->prod_post_type ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'getting product object' );
						}

						$product = $this->p->util->wc->get_product( $mixed->ID );

					} else return $md_opts;

				} else return $md_opts;

			} else return $md_opts;	// $mixed is not a variation array, product or post object.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'importing product attributes' );	// Begin timer.
			}

			$product_id     = $this->p->util->wc->get_product_id( $product );	// Returns product id from product object.
			$parent_id      = $is_variation ? $product->get_parent_id() : $product_id;
			$parent_product = $is_variation ? $this->p->util->wc->get_product( $parent_id ) : $product;
			$attr_md_index  = WpssoConfig::get_attr_md_index();	// Uses a local cache.
			$md_keys_multi  = WpssoConfig::get_md_keys_multi();	// Uses a local cache.

			foreach ( $attr_md_index as $opt_attr_key => $md_key ) {

				if ( empty( $md_key ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'attribute ' . $opt_attr_key . ' key is disabled' );
					}

					continue;

				} elseif ( empty( $this->p->options[ $opt_attr_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'attribute ' . $opt_attr_key . ' option is empty' );
					}

					continue;
				}

				$attr_name = $this->p->options[ $opt_attr_key ];	// Example: 'Size Group'.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using attribute ' . $attr_name . ' name for ' . $md_key . ' option' );
				}

				$values = array();

				if ( $is_variation ) {

					if ( '' !== ( $attr_val = $product->get_attribute( $attr_name ) ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'assigning ' . $attr_name . ' value to ' . $md_key . ' = ' . $attr_val );
						}

						$values[] = $attr_val;

					/*
					 * Fallback to the default value.
					 */
					} elseif ( '' !== ( $attr_val = $parent_product->get_variation_default_attribute( $attr_name ) ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'assigning ' . $attr_name . ' default value to ' . $md_key . ' = ' . $attr_val );
						}

						$values[] = $attr_val;

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no ' . $attr_name . ' default value for variation' );
						}
					}

				} else {

					/*
					 * Skip attributes with select options (example: Small | Medium | Large).
					 */
					if ( $this->is_variation_selectable_attribute( $product, $attr_name ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipping ' . $attr_name . ' selectable value = ' . $attr_val );
						}

					} else {

						if ( '' !== ( $attr_val = $product->get_attribute( $attr_name ) ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'assigning ' . $attr_name . ' value to ' . $md_key . ' = ' . $attr_val );
							}

							$values[] = $attr_val;
						}
					}
				}

				/*
				 * Check if the value(s) should be split into multiple numeric options.
				 */
				if ( ! empty( $values ) ) {	// Just in case.

					if ( empty( $md_keys_multi[ $md_key ] ) ) {

						$md_opts[ $md_key ] = reset( $values );

						$md_opts[ $md_key . ':disabled' ] = true;

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'option ' . $md_key . ' = ' . print_r( $md_opts[ $md_key ], true ) );
						}

						/*
						 * If this is a '_value' option, add the '_units' option.
						 */
						$this->p->util->maybe_add_md_key_units( $md_opts, $md_key );

					} else {

						/*
						 * Explode the first element into an array.
						 */
						$values = array_map( 'trim', explode( ',', reset( $values ) ) );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'exploded ' . $md_key . ' into array of ' . count( $values ) . ' elements' );
						}

						$this->p->util->maybe_renum_md_key( $md_opts, $md_key, $values );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'importing product attributes' );	// End timer.
			}

			return $md_opts;
		}

		/*
		 * This method does not return an array.
		 *
		 * $mt_ecom must be passed by reference to add the required meta tags.
		 *
		 * $mixed must be a product object or variation array.
		 */
		private function add_mt_product( array &$mt_ecom, array $mod, $mixed ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$is_variation = false;	// Default value.

			if ( $product = $this->p->util->wc->get_variation_product( $mixed ) ) {	// Product variation array.

				$is_variation = true;
				$variation    = $mixed;

			} elseif ( is_object( $mixed ) ) {

				if ( $mixed instanceof WC_Product ) {	// Product object.

					$product = $mixed;

				} elseif ( $mixed instanceof WP_Post ) {	// Post object.

					if ( SucomUtil::is_post_type( $mixed, $this->prod_post_type ) ) {

						$product = $this->p->util->wc->get_product( $mixed->ID );

					} else return false;

				} else return false;

			} else return false;	// $mixed is not a variation array, product or post object.

			$product_id     = $this->p->util->wc->get_product_id( $product );	// Returns product id from product object.
			$parent_id      = $is_variation ? $product->get_parent_id() : $product_id;
			$parent_product = $is_variation ? $this->p->util->wc->get_product( $parent_id ) : $product;
			$product_price  = $this->get_product_price( $product );
			$currency       = $this->get_currency();
			$include_vat    = $this->p->options[ 'plugin_product_include_vat' ] ? true : false;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product_id = ' . $product_id );
				$this->p->debug->log( 'parent_id = ' . $parent_id );
				$this->p->debug->log( 'product_price = ' . $product_price );
				$this->p->debug->log( 'currency = ' . $currency );
				$this->p->debug->log( 'include_vat = ' . ( $include_vat ? 'true' : 'false' ) );
			}

			$mt_ecom[ 'product:url' ]              = $product->get_permalink();
			$mt_ecom[ 'product:item_group_id' ]    = $is_variation ? $parent_id : '';	// Product variation group ID.
			$mt_ecom[ 'product:retailer_item_id' ] = $product_id;				// Product ID.
			$mt_ecom[ 'product:retailer_part_no' ] = $product->get_sku();			// Product SKU.

			/*
			 * Add shipping information.
			 */
			$this->add_mt_shipping_offers( $mt_ecom, $mod, $product, $parent_product );

			/*
			 * Add product availability.
			 *
			 * See https://docs.woocommerce.com/wc-apidocs/source-class-WC_Product.html#1534-1542
			 *
			 * Hook 'woocommerce_product_is_in_stock' (returns true or false) to customize the "in stock" status.
			 */
			if ( $product->is_in_stock() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is in stock' );
				}

				$mt_ecom[ 'product:availability' ] = 'https://schema.org/InStock';

			} elseif ( $product->is_on_backorder() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is on backorder' );
				}

				$mt_ecom[ 'product:availability' ] = 'https://schema.org/BackOrder';

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is out of stock' );
				}

				$mt_ecom[ 'product:availability' ] = 'https://schema.org/OutOfStock';
			}

			if ( $is_variation ) {

				$var_mod  = $this->p->page->get_mod( $product_id );
				$var_opts = $var_mod[ 'obj' ]->get_options( $var_mod[ 'id' ] );

				$this->add_variation_title( $mt_ecom, $mod, $product, $variation );
				$this->add_variation_description( $mt_ecom, $mod, $product, $variation );

				/*
				 * Variation product attributes.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting variation product attributes' );
				}

			 	/*
				 * Format the WooCommerce meta data as WordPress meta data.
				 */
				$var_wp_meta = $this->p->util->wc->get_product_wp_meta( $product );

				/*
				 * See WpssoIntegEcomWooAddGtin->filter_wc_variation_alt_options().
				 */
				$alt_opts = apply_filters( 'wpsso_wc_variation_alt_options', array() );

				/*
				 * The 'import_custom_fields' filter is executed before the 'wpsso_get_md_options' and
				 * 'wpsso_get_post_options' filters, so values retrieved from custom fields may get overwritten by
				 * later filters.
				 *
				 * The 'import_custom_fields' filter is also executed before the 'wpsso_get_md_defaults' and
				 * 'wpsso_get_post_defaults' filters, so submitted form values that are identical to their defaults
				 * can be removed before saving the options array.
				 *
				 * See WpssoPost->get_options().
				 * See WpssoAbstractWpMeta->get_defaults().
				 * See WpssoUtilCustomFields->filter_import_custom_fields().
				 * See WpssoIntegEcomWoocommerce->add_mt_product().
				 * See WpssoIntegEcomWooAddGtin->filter_wc_variation_alt_options().
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying import_custom_fields filters for variation id ' . $product_id . ' metadata' );
				}

				$var_opts = (array) apply_filters( 'wpsso_import_custom_fields', $var_opts, $mod, $var_wp_meta, $alt_opts );

				/*
				 * Since WPSSO Core v14.2.0.
				 *
				 * See WpssoProEcomWoocommerce->add_mt_product().
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying import_product_attributes filters for variation id ' . $product_id );
				}

				$var_opts = (array) apply_filters( 'wpsso_import_product_attributes', $var_opts, $mod, $variation );

				/*
				 * Since WPSSO Core v12.2.0.
				 *
				 * Overwrite parent options with those of the child, allowing only undefined child options to be
				 * inherited from the parent.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'inheriting variable parent metadata options' );
				}

				$parent_opts = $var_mod[ 'obj' ]->get_inherited_md_opts( $var_mod );

				if ( ! empty( $parent_opts ) ) {

					$var_opts = array_merge( $parent_opts, $var_opts );
				}

				/*
				 * Add custom fields meta data to the Open Graph meta tags.
				 */
				$this->p->og->add_data_og_type_md( $mt_ecom, 'product', $var_opts );
			}

			/*
			 * Add an extra meta tag to signal that VAT is included (used for the Schema valueAddedTaxIncluded property).
			 */
			if ( $include_vat ) {

				$mt_ecom[ 'product:price:vat_included' ] = true;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting price and pretax price formatted' );
			}

			$mt_ecom[ 'product:pretax_price:amount' ]   = $this->get_product_price_formatted( $product, $product_price, false );	// Exclude VAT.
			$mt_ecom[ 'product:pretax_price:currency' ] = $currency;
			$mt_ecom[ 'product:price_type' ]            = 'https://schema.org/ListPrice';
			$mt_ecom[ 'product:price:amount' ]          = $this->get_product_price_formatted( $product, $product_price, $include_vat );
			$mt_ecom[ 'product:price:currency' ]        = $currency;

			if ( method_exists( $product, 'get_regular_price' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting original price formatted' );
				}

				$regular_price = $product->get_regular_price();

				$mt_ecom[ 'product:original_price:amount' ]   = $this->get_product_price_formatted( $product, $regular_price, $include_vat );
				$mt_ecom[ 'product:original_price:currency' ] = $currency;

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product get_regular_price() method not found' );
			}

			if ( $product->is_on_sale() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is on sale' );
				}

				$mt_ecom[ 'product:price_type' ] = 'https://schema.org/SalePrice';

				if ( method_exists( $product, 'get_sale_price' ) ) {

					$sale_price = $product->get_sale_price();

					$mt_ecom[ 'product:sale_price:amount' ]   = $this->get_product_price_formatted( $product, $sale_price, $include_vat );
					$mt_ecom[ 'product:sale_price:currency' ] = $currency;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product get_sale_price() method not found' );
				}

				if ( method_exists( $product, 'get_date_on_sale_from' ) ) {

					if ( $product->get_date_on_sale_from() ) {	// Since WC v3.0.

						$sale_start_ts = $product->get_date_on_sale_from()->getTimestamp();

						$mt_ecom[ 'product:sale_price_dates:start' ]          = date( 'c', $sale_start_ts );
						$mt_ecom[ 'product:sale_price_dates:start_date' ]     = date( 'Y-m-d', $sale_start_ts );
						$mt_ecom[ 'product:sale_price_dates:start_time' ]     = date( 'H:i:s', $sale_start_ts );
						$mt_ecom[ 'product:sale_price_dates:start_timezone' ] = wc_timezone_string();
						$mt_ecom[ 'product:sale_price_dates:start_iso' ]      = date( 'c', $sale_start_ts );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'product get_date_on_sale_from() returned an empty value' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product get_date_on_sale_from() method not found' );
				}

				if ( method_exists( $product, 'get_date_on_sale_to' ) ) {

					if ( $product->get_date_on_sale_to() ) {	// Since WC v3.0.

						$sale_end_ts = $product->get_date_on_sale_to()->getTimestamp();

						$mt_ecom[ 'product:sale_price_dates:end' ]          = date( 'c', $sale_end_ts );
						$mt_ecom[ 'product:sale_price_dates:end_date' ]     = date( 'Y-m-d', $sale_end_ts );
						$mt_ecom[ 'product:sale_price_dates:end_time' ]     = date( 'H:i:s', $sale_end_ts );
						$mt_ecom[ 'product:sale_price_dates:end_timezone' ] = wc_timezone_string();
						$mt_ecom[ 'product:sale_price_dates:end_iso' ]      = date( 'c', $sale_end_ts );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'product get_date_on_sale_to() returned an empty value' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product get_date_on_sale_to() method not found' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product is not on sale' );
			}

			/*
			 * Get product shipping dimensions and weight.
			 */
			list(
				$mt_ecom[ 'product:shipping_length:value' ],
				$mt_ecom[ 'product:shipping_length:units' ],
				$mt_ecom[ 'product:shipping_width:value' ],
				$mt_ecom[ 'product:shipping_width:units' ],
				$mt_ecom[ 'product:shipping_height:value' ],
				$mt_ecom[ 'product:shipping_height:units' ],
				$mt_ecom[ 'product:shipping_weight:value' ],
				$mt_ecom[ 'product:shipping_weight:units' ],
			) = $this->get_shipping_length_width_height_weight( $product );

			/*
			 * Product variations do not have terms (categories or tags) so skip this section for variations.
			 */
			if ( ! $is_variation ) {

				/*
				 * Retrieve the terms of the taxonomy that are attached to the post ID.
				 *
				 * get_the_terms() returns an array of WP_Term objects, false if there are no terms (or the post does not
				 * exist), or a WP_Error object on failure.
				 */
				$terms = get_the_terms( $product_id, $this->tag_taxonomy );

				if ( is_array( $terms ) ) {

					foreach( $terms as $term ) {

						$mt_ecom[ 'product:tag' ][] = $term->name;
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mt_ecom', $mt_ecom );
			}
		}

		/*
		 * Add shipping information.
		 *
		 * The $shipping_class_id corresponds to the "Shipping class" selected when editing a product.
		 *
		 * Unless $product is a variation, $product and $parent_product will be the same.
		 */
		private function add_mt_shipping_offers( array &$mt_ecom, $mod, $product, $parent_product ) {

			static $shipping_zones      = null;
			static $shipping_continents = null;
			static $shipping_countries  = null;
			static $shipping_states     = null;
			static $shipping_enabled    = null;

			if ( null === $shipping_zones ) {	// Load values only once.

				$shipping_zones      = WC_Shipping_Zones::get_zones( $context = 'admin' );
				$shipping_states     = WC()->countries->get_states();
				$shipping_continents = WC()->countries->get_shipping_continents();	// Since WC v3.6.0.
				$shipping_countries  = WC()->countries->get_shipping_countries();
				$shipping_enabled    = $shipping_continents || $shipping_countries ? true : false;
			}

			$product_id       = $this->p->util->wc->get_product_id( $product );
			$product_url      = $product->get_permalink();
			$product_can_ship = $product->needs_shipping();
			$parent_url       = $parent_product->get_permalink();
			$currency         = $this->get_currency();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'shipping enabled = ' . ( $shipping_enabled ? 'true' : 'false' ) );
				$this->p->debug->log( 'product can ship = ' . ( $product_can_ship ? 'true' : 'false' ) );
			}

			/*
			 * The WPSSO WCSDT add-on returns true for the 'wpsso_og_add_mt_shipping_offers' filter.
			 */
			if ( $product_can_ship && $shipping_enabled && apply_filters( 'wpsso_og_add_mt_shipping_offers', false, $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'creating shipping offers' );
				}

				$shipping_class_id = $product->get_shipping_class_id();	// 0 or a selected product "Shipping class".

				$mt_ecom[ 'product:shipping_class_id' ] = $shipping_class_id;
				$mt_ecom[ 'product:shipping_offers' ]   = array();

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'shipping class id = ' . $shipping_class_id );
				}

				/*
				 * Each zone consists of shipping locations and shipping methods.
				 */
				foreach ( $shipping_zones as $zone_id => $zone ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'zone id = ' . $zone_id );
					}

					$zone_obj       = WC_Shipping_Zones::get_zone( $zone_id );
					$zone_name      = $zone_obj->get_zone_name( $context = 'admin' );
					$zone_locations = $zone_obj->get_zone_locations( $context = 'admin' );
					$zone_methods   = $zone_obj->get_shipping_methods( $enabled_only = true, $context = 'admin' );

					$shipping_destinations = array();
					$shipping_postcodes    = array();

					/*
					 * Get postal code limits first as they are applied to countries and regions.
					 */
					foreach ( $zone_locations as $location_key => $location_obj ) {

						if ( 'postcode' === $location_obj->type ) {

							$shipping_postcodes[] = $location_obj->code;
						}
					}

					/*
					 * Create an options array of countries, a single country, or a single country and state -
					 * all with postal code limits, if any were found above.
					 */
					foreach ( $zone_locations as $location_key => $location_obj ) {

						$destination_opts = array();

						if ( 'continent' === $location_obj->type ) {

							if ( isset( $shipping_continents[ $location_obj->code ][ 'countries' ] ) ) {

								$destination_opts[ 'country_code' ] = $shipping_continents[ $location_obj->code ][ 'countries' ];
							}

						} elseif ( 'country' === $location_obj->type ) {

							if ( isset( $shipping_countries[ $location_obj->code ] ) ) {	// Just in case.

								$destination_opts[ 'country_code' ] = $location_obj->code;
							}

						} elseif ( 'state' === $location_obj->type ) {

							$codes = explode( ':', $location_obj->code );

							if ( isset( $shipping_countries[ $codes[ 0 ] ] ) ) {	// Just in case.

								if ( isset( $shipping_states[ $codes[ 0 ] ][ $codes[ 1 ] ] ) ) {	// Just in case.

									$destination_opts[ 'country_code' ] = $codes[ 0 ];
									$destination_opts[ 'region_code' ]  = $codes[ 1 ];
								}
							}
						}

						if ( ! empty( $destination_opts ) ) {

							if ( ! empty( $shipping_postcodes ) ) {

								$destination_opts[ 'postal_code' ] = $shipping_postcodes;
							}

							$destination_opts[ 'destination_id' ]  = 'dest-z' . $zone_id . '-d' . $location_key;
							$destination_opts[ 'destination_rel' ] = $parent_url;

							$shipping_destinations[] = $destination_opts;
						}
					}

					/*
					 * Get shipping methods and rates for this zone.
					 */
					foreach ( $zone_methods as $method_inst_id => $method_obj ) {

						/*
						 * Returns false or a shipping offer options array.
						 */
						if ( $shipping_offer = $this->get_zone_method_shipping_offer( $zone_id, $zone_name,
							$method_inst_id, $method_obj, $shipping_class_id, $product, $parent_product ) ) {

							if ( ! empty( $shipping_destinations ) ) {

								$shipping_offer[ 'shipping_destinations' ] = $shipping_destinations;
							}

							$mt_ecom[ 'product:shipping_offers' ][] = $shipping_offer;
						}

					}	// End of $zone_methods loop.

				}	// End of $shipping_zones loop.

				$world_zone_id      = 0;
				$world_zone_obj     = WC_Shipping_Zones::get_zone( $world_zone_id );	// Locations not covered by your other zones.
				$world_zone_name    = __( 'World', 'wpsso' );
				$world_zone_methods = $world_zone_obj->get_shipping_methods();

				/*
				 * Get shipping methods and rates for the world zone.
				 */
				if ( ! empty( $world_zone_methods ) ) {

					foreach ( $world_zone_methods as $method_inst_id => $method_obj ) {

						/*
						 * Returns false or a shipping offer options array.
						 */
						if ( $shipping_offer = $this->get_zone_method_shipping_offer( $world_zone_id, $world_zone_name,
							$method_inst_id, $method_obj, $shipping_class_id, $product, $parent_product ) ) {

							$mt_ecom[ 'product:shipping_offers' ][] = $shipping_offer;
						}

					}	// End of $world_zone_methods loop.
				}
			}

		}

		/*
		 * Returns false or a shipping offer options array.
		 */
		private function get_zone_method_shipping_offer( $zone_id, $zone_name, $method_inst_id, $method_obj, $shipping_class_id, $product, $parent_product ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'method instance id = ' . $method_inst_id );
			}

			$parent_url    = $parent_product->get_permalink();
			$product_price = $this->get_product_price( $product );
			$currency      = $this->get_currency();

			$shipping_offer      = false;
			$shipping_class_obj  = $shipping_class_id ? get_term_by( 'id', $shipping_class_id, 'product_shipping_class' ) : false;
			$shipping_class_name = isset( $shipping_class_obj->name ) ? $shipping_class_obj->name : '';

			$method_rate_id = $method_obj->get_rate_id();
			$method_name    = $method_obj->get_title();
			$method_data    = $method_obj->instance_settings;

			$rate_ids  = explode( ':', $method_rate_id );
			$rate_name = empty( $shipping_class_name ) ? $method_name : $method_name . ' (' . $shipping_class_name . ')';
			$rate_type = reset( $rate_ids );
			$rate_cost = null;

			if ( 'local_pickup' === $rate_type ) {	// Pickup is not a shipping method.

				return false;

			} elseif ( 'free_shipping' === $rate_type ) {	// Optimize.

				$rate_cost = 0;

			/*
			 * A shipping class for this product is available.
			 */
			} elseif ( ! empty( $shipping_class_id ) &&
				isset( $method_data[ 'class_cost_' . $shipping_class_id ] ) &&
					'' !== $method_data[ 'class_cost_' . $shipping_class_id ] ) {	// Allow for 0 and shortcodes.

				$rate_cost = $method_data[ 'class_cost_' . $shipping_class_id ];

			/*
			 * A shipping class for this product is not available but a "no class cost" value is available.
			 */
			} elseif ( empty( $shipping_class_id ) &&
				isset( $method_data[ 'no_class_cost'] ) &&
					'' !== $method_data[ 'no_class_cost'] ) {	// Allow for 0 and shortcodes.

				$rate_cost = $method_data[ 'no_class_cost' ];

			/*
			 * A shipping class for this product is not available and a "no class cost" value is not available.
			 */
			} elseif ( isset( $method_data[ 'cost' ] ) &&
				'' !== $method_data[ 'cost' ] ) {	// Allow for 0 and shortcodes.

				$rate_cost = $method_data[ 'cost' ];

			/*
			 * Free shipping.
			 */
			} else {

				$rate_cost = 0;
			}

			/*
			 * Maybe resolve the [cost], [qty], and [fee] shortcodes.
			 *
			 * See woocommerce/includes/shipping/flat-rate/class-wc-shipping-flat-rate.php.
			 */
			if ( ! empty( $rate_cost ) && ! is_numeric( $rate_cost ) ) {

				/*
				 * evaluate_cost() is protected, so make it accessible.
				 *
				 * See https://www.php.net/manual/en/class.reflectionmethod.php.
				 */
				$reflect = new ReflectionMethod( $method_obj, 'evaluate_cost' );	// Since PHP v5.4.

				$reflect->setAccessible( true );

				$rate_cost = $reflect->invoke( $method_obj, $rate_cost, array( 'qty'  => 1, 'cost' => $product_price ) );
			}

			if ( ! empty( $method_data[ 'requires' ] ) ) {

				switch ( $method_data[ 'requires' ] ) {

					case 'both':		// Requires a coupon and minimum quantity.
					case 'coupon':		// Requires a coupon.
					case 'either':		// Requires a coupon OR a minimum amount.
					case 'min_amount':	// Requires a minimum amount.

						/*
						 * https://schema.org/OfferShippingDetails does not provide a way to specify
						 * conditions for shipping rates, like coupon or minimum amount.
						 */
						return false;

					default:		// Unknown requirement.

						return false;
				}
			}

			if ( is_numeric( $rate_cost ) ) {	// Just in case.

				$shipping_rate = array(
					'shipping_rate_name'     => $rate_name,
					'shipping_rate_cost'     => $rate_cost,
					'shipping_rate_currency' => $currency,
				);

				/*
				 * Returns shipping department, handling, and transit options for $shipping_class_id and $method_inst_id.
				 *
				 * Array (
				 * 	[shipdept_rel] => http://adm.surniaulula.com/produit/a-variable-product/
				 * 	[shipdept_timezone] => America/Vancouver
				 * 	[shipdept_midday_close] => 12:00
				 * 	[shipdept_midday_open] => 13:00
				 * 	[shipdept_cutoff] => 16:00
				 * 	[shipdept_day_sunday_open] => none
				 * 	[shipdept_day_sunday_close] => none
				 * 	[shipdept_day_monday_open] => 09:00
				 * 	[shipdept_day_monday_close] => 17:00
				 * 	[shipdept_day_tuesday_open] => 09:00
				 * 	[shipdept_day_tuesday_close] => 17:00
				 * 	[shipdept_day_wednesday_open] => 09:00
				 * 	[shipdept_day_wednesday_close] => 17:00
				 * 	[shipdept_day_thursday_open] => 09:00
				 * 	[shipdept_day_thursday_close] => 17:00
				 * 	[shipdept_day_friday_open] => 09:00
				 * 	[shipdept_day_friday_close] => 17:00
				 * 	[shipdept_day_saturday_open] => none
				 * 	[shipdept_day_saturday_close] => none
				 * 	[shipdept_day_publicholidays_open] => 09:00
				 * 	[shipdept_day_publicholidays_close] => 12:00
				 *  	[handling_rel] => http://adm.surniaulula.com/produit/a-variable-product/
				 * 	[handling_maximum] => 1.5
				 * 	[handling_unit_code] => DAY
				 * 	[handling_unit_text] => d
				 * 	[handling_name] => Days
				 * 	[transit_rel] => http://adm.surniaulula.com/produit/a-variable-product/
				 * 	[transit_minimum] => 5
				 * 	[transit_maximum] => 7
				 * 	[transit_unit_code] => DAY
				 * 	[transit_unit_text] => d
				 * 	[transit_name] => Days
				 * )
				 */
				$delivery_time = apply_filters( 'wpsso_wc_shipping_delivery_time', array(), $zone_id, $method_inst_id, $shipping_class_id, $parent_url );

				$shipping_offer = array(
					'shipping_id'           => 'shipping-z' . $zone_id . '-m' . $method_inst_id . '-c' . $shipping_class_id,
					'shipping_rel'          => $parent_url,
					'shipping_name'         => $zone_name,
					'shipping_rate'         => $shipping_rate,
					'delivery_time'         => $delivery_time,
				);
			}

			return $shipping_offer;
		}

		/*
		 * Example:
		 *
		 *	list(
		 *		$md_defs[ 'product_shipping_length_value' ],
		 *		$md_defs[ 'product_shipping_length_units' ],
		 *		$md_defs[ 'product_shipping_width_value' ],
		 *		$md_defs[ 'product_shipping_width_units' ],
		 *		$md_defs[ 'product_shipping_height_value' ],
		 *		$md_defs[ 'product_shipping_height_units' ],
		 *		$md_defs[ 'product_shipping_weight_value' ],
		 *		$md_defs[ 'product_shipping_weight_units' ],
		 *	) = $this->get_shipping_length_width_height_weight( $product );
		 */
		private function get_shipping_length_width_height_weight( $product ) {

			$dimension_unit_text = WpssoUtilUnits::get_dimension_text();
			$weight_unit_text    = WpssoUtilUnits::get_weight_text();


			$ret = array(
				0 => '',			// Shipping length value.
				1 => $dimension_unit_text,	// Shipping lenth units.
				2 => '',			// Shipping width value.
				3 => $dimension_unit_text,	// Shipping width units.
				4 => '',			// Shipping height value.
				5 => $dimension_unit_text,	// Shipping height units.
				6 => '',			// Shipping weight value.
				7 => $weight_unit_text,		// Shipping weight units.
			);

			if ( $product->has_dimensions() ) {	// Has shipping dimensions.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting product shipping dimensions' );
				}

				if ( is_callable( array( $product, 'get_length' ) ) ) {

					$length = $product->get_length();	// Shipping length.

					if ( is_numeric( $length ) ) {		// Required to ignore undefined values.

						$ret[ 0 ] = $length;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'product shipping length is not numeric' );
					}
				}

				if ( is_callable( array( $product, 'get_width' ) ) ) {

					$width = $product->get_width();	// Shipping width.

					if ( is_numeric( $width ) ) {	// Required to ignore undefined values.

						$ret[ 2 ] = $width;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'product shipping width is not numeric' );
					}
				}

				if ( is_callable( array( $product, 'get_height' ) ) ) {

					$height = $product->get_height();	// Shipping height.

					if ( is_numeric( $height ) ) {		// Required to ignore undefined values.

						$ret[ 4 ] = $height;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'product shipping height is not numeric' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product does not have shipping dimensions' );
			}

			if ( $product->has_weight() ) {	// Has shipping weight.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting product shipping weight' );
				}

				if ( is_callable( array( $product, 'get_weight' ) ) ) {	// Just in case.

					$weight = $product->get_weight();	// Shipping weight.

					if ( is_numeric( $weight ) ) {		// Required to ignore undefined values.

						$ret[ 6 ] = $weight;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'product shipping weight is not numeric' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product does not have a shipping weight' );
			}

			return $ret;
		}

		private function get_currency() {

			static $currency = null;

			if ( null === $currency ) {	// Get value only once.

				$currency = get_woocommerce_currency();
				$currency = apply_filters( 'wpsso_currency', $currency );
			}

			return $currency;
		}

		private function get_product_price( $product ) {

			$product_price = $product->get_price();
			$product_price = apply_filters( 'wpsso_product_price', $product_price, $product );

			return $product_price;
		}

		private function get_product_price_formatted( $product, $product_price, $include_vat = false ) {

			if ( is_numeric( $product_price ) ) {	// Just in case.

				if ( $include_vat ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'calling wc_get_price_including_tax() for ' . $product_price );
					}

					$product_price = wc_get_price_including_tax( $product, array( 'price' => $product_price ) );	// Since WC v3.0.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wc_get_price_including_tax() returned ' . $product_price );
					}
				}

				/*
				 * $decimals = Number of decimal points, blank to use woocommerce_price_num_decimals, or false
				 * false to avoid all rounding.
				 */
				$product_price = wc_format_decimal( $product_price, $decimals = '', $trim_zeros = false );

			} else {

				$product_price = '';
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product price formatted = ' . $product_price );
			}

			return $product_price;
		}

		private function add_variation_title( &$mt_ecom, $mod, $product, $variation ) {

			$title_text = $this->p->opt->get_text( 'plugin_product_var_title' );

			$title_text = $this->p->util->inline->replace_variables( $title_text, $mod, $atts = array(
				'var_title' => $product->get_title(),
				'var_sku'   => $mt_ecom[ 'product:retailer_part_no' ],
			) );

			$mt_ecom[ 'product:title' ] = apply_filters( 'wpsso_variation_title', $title_text, $variation );
		}

		private function add_variation_description( &$mt_ecom, $mod, $product, $variation ) {

			if ( empty( $variation[ 'variation_description' ] ) ) {

				$desc_text = '';

			} else {

				$desc_text = $this->p->util->cleanup_html_tags( $variation[ 'variation_description' ] );
			}

			$mt_ecom[ 'product:description' ] = apply_filters( 'wpsso_variation_description', $desc_text, $variation );
		}

		private function is_variation_selectable_attribute( $product, $attr_name ) {

			if ( method_exists( $product, 'get_variation_attributes' ) ) {	// Just in case.

				$variation_attrs = $product->get_variation_attributes();

				foreach ( $variation_attrs as $variation_name => $arr ) {

					if ( $variation_name === $attr_name ) {

						return true;
					}
				}
			}

			return false;
		}
	}
}
