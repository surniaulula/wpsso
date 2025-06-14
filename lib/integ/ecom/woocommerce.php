<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegEcomWooCommerce' ) ) {

	class WpssoIntegEcomWooCommerce {

		private $p;	// Wpsso class object.

		private $og_type        = 'product';
		private $rating_meta    = 'rating';
		private $worst_rating   = 1;
		private $best_rating    = 5;
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
		private $reviews_enabled = null;
		private $rating_enabled  = null;

		public function __construct( &$plugin ) {	// Pass by reference is OK.

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->page_ids[ 'account' ]  = wc_get_page_id( 'myaccount' );	// Returns -1 if no page selected.
			$this->page_ids[ 'cart' ]     = wc_get_page_id( 'cart' );	// Returns -1 if no page selected.
			$this->page_ids[ 'checkout' ] = wc_get_page_id( 'checkout' );	// Returns -1 if no page selected.
			$this->page_ids[ 'shop' ]     = wc_get_page_id( 'shop' );	// Returns -1 if no page selected.

			$this->reviews_enabled = 'yes' === get_option( 'woocommerce_enable_reviews' ) ? true : false;
			$this->rating_enabled  = 'yes' === get_option( 'woocommerce_enable_review_rating' ) ? true : false;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'page_ids', $this->page_ids );
			}

			if ( is_admin() ) {

				/*
				 * Check for possible missing page ID selections.
				 *
				 * This hook is fired once WordPress, plugins, and the theme are fully loaded and instantiated.
				 */
				add_action( 'wp_loaded', array( $this, 'check_woocommerce_pages' ), 10, 0 );

				/*
				 * Show a suggested list of product attributes.
				 */
				add_action( 'woocommerce_product_options_attributes', array( $this, 'show_product_attributes_footer' ), -1000, 0 );

				/*
				 * Update the Document SSO metabox and toobar notices after saving product variations.
				 *
				 * See WC_AJAX->save_variations() in woocommerce/includes/class-wc-ajax.php.
				 */
				add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'ajax_save_product_variations' ), 1000, 1 );

				/*
				 * Add WPSSO RAR add-on filters.
				 *
				 * See WpssoIntegEcomWooCommerce->disable_options_keys().
				 */
				if ( ! empty( $this->p->avail[ 'p_ext' ][ 'rar' ] ) ) {

					$this->p->util->add_plugin_filters( $this, array(
						'post_column_rating_value' => 3,
					), 10, 'wpssorar' );
				}
			}

			/*
			 * Clear the post ID cache after WooCommerce updates the product metadata.
			 */
			add_action( 'woocommerce_product_object_updated_props', array( $this, 'clear_product_cache' ), 1000, 2 );

			/*
			 * Refresh the post ID cache after WooCommerce updates the product object.
			 */
			add_action( 'woocommerce_after_product_object_save', array( $this, 'refresh_product_cache' ), 1000, 2 );

			/*
			 * Return the primary category term for WooCommerce product breadcrumbs.
			 */
			add_filter( 'woocommerce_breadcrumb_main_term', array( $this, 'woocommerce_breadcrumb_main_term' ), 100, 2 );

			/*
			 * Disable the default WooCommerce Schema markup.
			 */
			add_filter( 'woocommerce_structured_data_product', '__return_empty_array', PHP_INT_MAX );
			add_filter( 'woocommerce_structured_data_review', '__return_empty_array', PHP_INT_MAX );
			add_filter( 'woocommerce_structured_data_website', '__return_empty_array', PHP_INT_MAX );

			/*
			 * Maybe load missing WooCommerce front-end libraries for 'the_content' filter.
			 */
			$this->p->util->add_plugin_actions( $this, array(
				'admin_post_head'        => 1,
				'scheduled_task_started' => 1,
			) );

			$this->p->util->add_plugin_filters( $this, array(
				'request_url_query_cache_disable' => 4,
				'head_cache_index'                => 1,
				'use_post'                        => 1,
				'get_post_type'                   => 2,
				'schema_type'                     => 3,
				'schema_type_post_type_labels'    => 1,
				'primary_tax_slug'                => 2,	// See WpssoPost->get_primary_terms().
				'the_content_seed'                => 2,
				'description_seed'                => 4,
				'attached_image_ids'              => 2,
				'term_image_ids'                  => 3,
				'get_md_defaults'                 => 2,
				'get_post_options'                => 3,
				'og_seed'                         => 2,
				'tag_names_seed'                  => 2,
				'import_product_attributes'       => 3,
			) );

			/*
			 * Maybe add the Pinterest image to the WooCommerce template for displaying product archives - including
			 * the main shop page, which is a post type archive.
			 */
			if ( ! empty( $this->p->options[ 'pin_add_img_html' ] ) ) {

				add_action( 'woocommerce_archive_description', array( $this->p->pinterest, 'show_image_html' ) );
			}

			$this->disable_options_keys();
		}

		/*
		 * Return the primary category term for WooCommerce product breadcrumbs.
		 *
		 * See WC_Breadcrumb->add_crumbs_attachment().
		 * See WC_Breadcrumb->add_crumbs_single().
		 */
		public function woocommerce_breadcrumb_main_term( $term, $terms ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			global $post;

			if ( ! empty( $post->ID ) ) {	// Just in case.

				$mod = $this->p->post->get_mod( $post->ID );

				if ( ! empty( $mod[ 'id' ] ) ) {	// Just in case.

					$primary_id = $this->p->post->get_primary_term_id( $mod, $this->cat_taxonomy );

					if ( ! empty( $primary_id ) ) {	// Just in case.

						$primary_term = get_term( $primary_id, $this->cat_taxonomy );

						if ( $primary_term instanceof WP_Term ) {	// Just in case.

							return $primary_term;
						}
					}
				}
			}

			return $term;
		}

		/*
		 * Define and disable some plugin options.
		 *
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
				'rar_add_to_product'                      => 0,		// Disable WPSSO Ratings and Reviews for WC Products.
			) as $opt_key => $opt_val ) {

				$this->p->options[ $opt_key ] = $opt_val;

				$this->p->options[ $opt_key . ':disabled' ] = true;
			}
		}

		public function check_woocommerce_pages() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

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

				if ( ! is_int( $this->page_ids[ $page_type ] ) || $this->page_ids[ $page_type ] < 1 ||
					! SucomUtilWP::post_exists( $this->page_ids[ $page_type ] ) ) {

					$notice_msg = sprintf( __( 'The WooCommerce "%1$s" option value is empty.', 'wpsso' ), $label_transl ) . ' ';

					$notice_msg .= 'shop' === $page_type ? $wc_products_msg : $wc_advanced_msg;

					$this->p->notice->warn( $notice_msg );
				}
			}
		}

		/*
		 * Update the Document SSO metabox and toobar notices after saving product variations.
		 *
		 * See WC_AJAX->save_variations() in woocommerce/includes/class-wc-ajax.php.
		 */
		public function ajax_save_product_variations( $product_id ) {

			$admin_l10n = $this->p->cf[ 'plugin' ][ 'wpsso' ][ 'admin_l10n' ];

			echo '<script>';
			echo 'window.allowScrollToHash = false;';
			echo 'if ( \'function\' === typeof sucomEditorPostbox ) {';
			echo ' sucomEditorPostbox( \'wpsso\', \'' . $admin_l10n . '\', \'' . $product_id . '\' );';
			echo '}';
			echo 'if ( \'function\' === typeof sucomToolbarNotices ) {';
			echo ' sucomToolbarNotices( \'wpsso\', \'' . $admin_l10n . '\' );';
			echo '}';
			echo '</script>' . "\n";
		}

		/*
		 * Show a suggested list of translated product attribute names.
		 *
		 * Hooked to the 'woocommerce_product_options_attributes' action.
		 */
		public function show_product_attributes_footer() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			global $post;

			$product        = $this->p->util->wc->get_product( $post->ID );
			$attr_md_index  = WpssoConfig::get_attr_md_index();
			$wc_attributes  = $product->get_attributes();
			$wc_attr_labels = array();
			$suggest_names  = array();

			foreach ( $wc_attributes as $attribute ) {

				$attr_name = $attribute->get_name();

				$wc_attr_names[ $attr_name ] = true;
			}

			foreach ( $attr_md_index as $opt_attr_key => $md_key ) {

				if ( empty( $md_key ) ) continue;

				if ( empty( $this->p->options[ $opt_attr_key ] ) ) continue;

				$attr_name  = $this->p->options[ $opt_attr_key ];	// Untranslated name.

				/*
				 * Check that this attribute name has not already been added to this product.
				 */
				if ( empty( $wc_attr_names[ $attr_name ] ) ) {

					$suggest_names[] = SucomUtilOptions::get_key_value( $opt_attr_key, $this->p->options );	// Translated name.
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

		public function filter_post_column_rating_value( $value, $post_id, $rating_enabled ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( '' === $value && ! $rating_enabled && $this->rating_enabled ) {

				if ( $this->prod_post_type === get_post_type( $post_id ) ) {

					if ( $product = $this->p->util->wc->get_product( $post_id ) ) {

						$average_rating = (float) $product->get_average_rating();

						return number_format( $average_rating, 2, '.', '' );
					}
				}
			}

			return $value;
		}

		/*
		 * Clear the post ID cache after WooCommerce updates the product metadata.
		 *
		 * The $updated_props array may include one or more of the following keys:
		 *
		 * $updated_props = array(
		 *	'sku',
		 *	'global_unique_id',
		 *	'regular_price',
		 *	'sale_price',
		 *	'date_on_sale_from',
		 *	'date_on_sale_to',
		 *	'total_sales',
		 *	'tax_status',
		 *	'tax_class',
		 *	'manage_stock',
		 *	'backorders',
		 *	'low_stock_amount',
		 *	'sold_individually',
		 *	'weight',
		 *	'length',
		 *	'width',
		 *	'height',
		 *	'upsell_ids',
		 *	'cross_sell_ids',
		 *	'purchase_note',
		 *	'default_attributes',
		 *	'virtual',
		 *	'downloadable',
		 *	'gallery_image_ids',
		 *	'download_limit',
		 *	'download_expiry',
		 *	'image_id',
		 *	'stock_quantity',
		 *	'stock_status',
		 *	'average_rating',
		 *	'rating_counts',
		 *	'review_count',
		 * );
		 */
		public function clear_product_cache( $product, $updated_props ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product = ' . get_class( $product ) );	// WC_Product, WC_Product_Variable, or WC_Product_Grouped.
			}

			if ( $product instanceof WC_Product ) {   // WC_Product, WC_Product_Variable, or WC_Product_Grouped.

				$product_id = $this->p->util->wc->get_product_id( $product );	// Returns product id from product object.

				if ( $product_id ) $this->p->post->clear_cache( $product_id );	// Refresh the cache for a single post ID.
			}
		}

		/*
		 * Refresh the post ID cache after WooCommerce updates the product object.
		 */
		public function refresh_product_cache( $product, $data_store ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product = ' . get_class( $product ) );	// WC_Product, WC_Product_Variable, or WC_Product_Grouped.
			}

			if ( $product instanceof WC_Product ) {   // WC_Product, WC_Product_Variable, or WC_Product_Grouped.

				$product_id = $this->p->util->wc->get_product_id( $product );	// Returns product id from product object.

				/*
				 * Refresh the cache for a single post ID.
				 *
				 * This method will only execute once per post ID per page load.
				 */
				if ( $product_id ) $this->p->post->refresh_cache( $product_id );	// Refresh the cache for a single post ID.
			}
		}

		public function action_admin_post_head( $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$user_id = get_current_user_id();

			$this->include_frontend_libs( $user_id );
		}

		public function action_scheduled_task_started( $user_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

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

			WC()->initialize_session();
		}

		/*
		 * WooCommerce product attributes do not have their own webpages - product attribute query strings are used to
		 * pre-fill product selections on the front-end. The
		 * WpssoIntegEcomWooCommerce->filter_request_url_query_cache_disable() method removes all product attributes from
		 * the request URL, and if the $request_url and $canonical_url values match, the filter returns false (ie. do not
		 * disable the cache).
		 */
		public function filter_request_url_query_cache_disable( $cache_disable, $request_url, $canonical_url, $mod ) {

			if ( is_product() ) {

				if ( false !== strpos( $request_url, 'attribute_' ) ) {

					$request_url_no_attrs = preg_replace( '/[\?\&]attribute_[^=]+=[^\&]*/', '', $request_url );

					if ( $request_url_no_attrs === $canonical_url ) {

						return false;
					}
				}
			}

			return $cache_disable;
		}

		public function filter_head_cache_index( $cache_index ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $cache_index . '_currency:' . $this->get_product_currency();
		}

		public function filter_use_post( $use_post ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Note that in_the_loop() can be true in both archive and singular pages.
			 */
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

		public function filter_get_post_type( $post_type, $post_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $post_id === $this->page_ids[ 'shop' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'post is shop page' );
				}

				return $this->prod_post_type;
			}

			return $post_type;
		}

		public function filter_schema_type( $type_id, array $mod, $is_custom ) {

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

				} elseif ( $this->p->util->wc->is_mod_variable( $mod ) ) {

					if ( $this->prod_post_type === $mod[ 'post_type' ] ) {

						$type_id = $this->p->schema->get_schema_type_id_for( 'product_group' );
					}
				}
			}

			return $type_id;
		}

		public function filter_schema_type_post_type_labels( array $type_labels ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$type_labels[ 'schema_type_for_product_group' ] = __( 'Products Group', 'wpsso' );

			asort( $type_labels );

			return $type_labels;
		}

		public function filter_primary_tax_slug( $tax_slug, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $this->prod_post_type === $mod[ 'post_type' ] ) {

				if ( 'category' === $tax_slug ) {

					$tax_slug = $this->cat_taxonomy;

				} elseif ( 'tag' === $tax_slug ) {

					$tax_slug = $this->tag_taxonomy;
				}
			}

			return $tax_slug;
		}

		/*
		 * Return false to prevent the comment or post content from being used.
		 */
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

			if ( ! SucomUtilWP::is_mod_post_type( $mod, $this->prod_post_type ) ) {

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

			if ( SucomUtilWP::is_term_tax_slug( $term_id, $this->cat_taxonomy ) || 
				SucomUtilWP::is_term_tax_slug( $term_id, $this->tag_taxonomy ) ) {

				$pid = get_metadata( 'term', $term_id, $key = 'thumbnail_id', $single = true );

				if ( ! empty( $pid ) ) {

					$image_ids[] = $pid;
				}
			}

			return $image_ids;
		}

		/*
		 * See WpssoIntegEcomAbstractWooCommerceBrands->filter_get_md_defaults_woocommerce().
		 */
		public function filter_get_md_defaults( array $md_defs, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtilWP::is_mod_post_type( $mod, $this->prod_post_type ) ) {

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

				$this->p->debug->log( 'product = ' . get_class( $product ) );	// WC_Product, WC_Product_Variable, or WC_Product_Grouped.
			}

			$product_incl_vat   = $this->p->options[ 'plugin_product_include_vat' ] ? true : false;
			$product_price      = $this->get_product_price( $product );
			$product_price_fmtd = $this->get_product_price_formatted( $product, $product_price, $product_incl_vat );
			$product_currency   = $this->get_product_currency();
			$product_avail      = $this->get_product_avail( $product );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product_incl_vat = ' . ( $product_incl_vat ? 'true' : 'false' ) );
				$this->p->debug->log( 'product_price = ' . $product_price );
				$this->p->debug->log( 'product_price_fmtd = ' . $product_price_fmtd );
				$this->p->debug->log( 'product_currency = ' . $product_currency );
				$this->p->debug->log( 'product_avail = ' . $product_avail );
			}

			if ( $product->is_on_sale() ) {

				$md_defs[ 'product_price_type' ]= 'https://schema.org/SalePrice';

			} else $md_defs[ 'product_price_type' ] = $this->p->get_options( 'schema_def_product_price_type', 'https://schema.org/ListPrice' );

			$md_defs[ 'product_price' ]            = $product_price_fmtd;
			$md_defs[ 'product_currency' ]         = $product_currency;
			$md_defs[ 'product_avail' ]            = $product_avail;
			$md_defs[ 'product_retailer_part_no' ] = $product->get_sku();	// Product SKU.

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

			/*
			 * Add event offers.
			 */
			$md_defs = SucomUtil::preg_grep_keys( '/^schema_event_offer_/', $md_defs, $invert = true );

			$avail_variations = $this->p->util->wc->get_available_variations( $product );	// Always returns an array.

			if ( ! empty( $avail_variations ) ) {

				foreach( $avail_variations as $num => $variation ) {

					if ( $var_product = $this->p->util->wc->get_variation_product( $variation ) ) {	// Get the product for the variation.

						$var_product_price      = $this->get_product_price( $var_product );
						$var_product_price_fmtd = $this->get_product_price_formatted( $var_product, $var_product_price, $product_incl_vat );

						$md_defs[ 'schema_event_offer_name_' . $num ]     = $this->get_product_variation_title( $mod, $var_product, $variation );
						$md_defs[ 'schema_event_offer_url_' . $num ]      = $this->get_product_url( $var_product );
						$md_defs[ 'schema_event_offer_price_' . $num ]    = $var_product_price_fmtd;
						$md_defs[ 'schema_event_offer_currency_' . $num ] = $product_currency;
						$md_defs[ 'schema_event_offer_avail_' . $num ]    = $this->get_product_avail( $var_product );
					}
				}

			} else {

				$md_defs[ 'schema_event_offer_name_0' ]     = $this->get_product_title( $product );
				$md_defs[ 'schema_event_offer_url_0' ]      = $this->get_product_url( $product );
				$md_defs[ 'schema_event_offer_price_0' ]    = $product_price_fmtd;
				$md_defs[ 'schema_event_offer_currency_0' ] = $product_currency;
				$md_defs[ 'schema_event_offer_avail_0' ]    = $product_avail;
			}

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

			if ( ! SucomUtilWP::is_mod_post_type( $mod, $this->prod_post_type ) ) {

				return $mt_og;

			} elseif ( false === ( $product = $this->p->util->wc->get_product( $mod[ 'id' ] ) ) ) {

				return $mt_og;
			}

			/*
			 * Get the pre-sorted product meta tags, with the og:type meta tag top-most in the array.
			 */
			$mt_ecom = SucomUtil::get_mt_product_seed( $this->og_type, array( 'og:type' => $this->og_type ) );

			$this->add_mt_product( $mt_ecom, $mod, $product );

			$this->add_mt_ratings( $mt_ecom, $mod, $product );

			if ( $this->p->avail[ 'p' ][ 'schema' ] ) {

				$this->add_mt_reviews( $mt_ecom, $mod, $product );

				if ( $this->p->util->wc->is_product_variable( $product ) ) {

					$schema_type = $this->p->schema->get_mod_schema_type_id( $mod, $use_md_opts = true );

					$og_mt_suffix = 'product.group' === $schema_type ? 'variants' : 'offers';

					/*
					 * Add product variants or offers.
					 */
					if ( apply_filters( 'wpsso_og_add_mt_' . $og_mt_suffix, true, $mod ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'add ' . $og_mt_suffix . ' meta tags is true' );
						}

						/*
						 * Similar to the WooCommerce method, except it does not exclude out of stock variations.
						 */
						$avail_variations = $this->p->util->wc->get_available_variations( $product );	// Always returns an array.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( count( $avail_variations ) . ' variations returned' );
						}

						foreach( $avail_variations as $num => $variation ) {

							/*
							 * Get the pre-sorted product meta tags, with the og:type meta tag top-most in the array.
							 */
							$mt_ecom_var = SucomUtil::get_mt_product_seed( $this->og_type );

							$this->add_mt_product( $mt_ecom_var, $mod, $variation );

							if ( ! empty( $mt_ecom_var ) ) {

								$mt_ecom[ $this->og_type . ':' . $og_mt_suffix ][] = $mt_ecom_var;
							}
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'add variants meta tags is false' );
					}
				}
			}

			$mt_ecom = apply_filters( 'wpsso_og_ecom_woocommerce', $mt_ecom, $mod );

			return array_merge( $mt_og, $mt_ecom );
		}

		public function filter_tag_names_seed( $tags, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtilWP::is_mod_post_type( $mod, $this->prod_post_type ) ) {

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

			if ( function_exists( 'is_sitemap' ) && is_sitemap() ) {	// Nothing to do - no attribute values in sitemaps.

				return $md_opts;

			} elseif ( $product = $this->p->util->wc->get_variation_product( $mixed ) ) {	// Get the product for the variation.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using variation array' );
				}

				$is_variation = true;
				$variation    = $mixed;

			} elseif ( is_object( $mixed ) ) {

				if ( $mixed instanceof WC_Product ) {	// WC_Product, WC_Product_Variable, or WC_Product_Grouped.

					$product = $mixed;

				} elseif ( $mixed instanceof WP_Post ) {

					if ( SucomUtilWP::is_post_type( $mixed, $this->prod_post_type ) ) {

						$product = $this->p->util->wc->get_product( $mixed->ID );

					} else return $md_opts;

				} else return $md_opts;

			} else return $md_opts;	// $mixed is not a variation array, product or post object.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'importing product attributes' );	// Begin timer.
			}

			$product_id        = $this->p->util->wc->get_product_id( $product );	// Returns product id from product object.
			$product_parent_id = $is_variation ? $product->get_parent_id() : $product_id;
			$product_parent    = $is_variation ? $this->p->util->wc->get_product( $product_parent_id ) : $product;
			$attr_md_index     = WpssoConfig::get_attr_md_index();
			$md_keys_multi     = WpssoConfig::get_md_keys_multi();

			foreach ( $attr_md_index as $opt_attr_key => $md_key ) {

				if ( empty( $md_key ) ) continue;

				if ( empty( $this->p->options[ $opt_attr_key ] ) ) continue;

				$attr_name  = $this->p->options[ $opt_attr_key ];	// Untranslated name.
				$attr_value = false;

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'checking for ' . $attr_name . ' attribute values' );
				}

				$values = array();

				/*
				 * Product variation.
				 */
				if ( $is_variation ) {

					if ( '' !== ( $attr_value = $product->get_attribute( $attr_name ) ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'assigning ' . $attr_name . ' value to ' . $md_key . ' = ' . $attr_value );
						}

						$values[] = $attr_value;

					/*
					 * Fallback to the default value.
					 */
					} elseif ( '' !== ( $attr_value = $product_parent->get_variation_default_attribute( $attr_name ) ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'assigning ' . $attr_name . ' default value to ' . $md_key . ' = ' . $attr_value );
						}

						$values[] = $attr_value;
					}

				/*
				 * Main product or simple product.
				 */
				} else {

					/*
					 * Skip attributes with select options (example: Small | Medium | Large).
					 */
					if ( $this->is_variation_selectable_attribute( $product_id, $product, $attr_name ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipping ' . $attr_name . ' selectable value = ' . $attr_value );
						}

					} elseif ( '' !== ( $attr_value = $product->get_attribute( $attr_name ) ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'assigning ' . $attr_name . ' value to ' . $md_key . ' = ' . $attr_value );
						}

						$values[] = $attr_value;
					}
				}

				/*
				 * Check if the value(s) should be split into multiple numeric options.
				 */
				if ( ! empty( $values ) ) {	// Just in case.

					if ( ! empty( $md_keys_multi[ $md_key ] ) ) {

						/*
						 * If $attr_value was not an array, then $values[ 0 ] will be a string - split that string into an array.
						 */
						if ( ! is_array( $attr_value ) ) {

							$values = array_map( 'trim', explode( ',', reset( $values ) ) );

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'exploded ' . $md_key . ' into array of ' . count( $values ) . ' elements' );
							}
						}

						$this->p->util->maybe_renum_md_key( $md_opts, $md_key, $values, $is_disabled = true );

					} else {

						$md_opts[ $md_key ] = reset( $values );

						$md_opts[ $md_key . ':disabled' ] = true;

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'option ' . $md_key . ' = ' . print_r( $md_opts[ $md_key ], true ) );
						}

						/*
						 * If this is a '_value' option, add the '_units' option.
						 */
						$this->p->util->maybe_add_md_key_units( $md_opts, $md_key );
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
		 * $mod contains the main product information (not the variant).
		 * $mixed is a product object or variation array.
		 */
		private function add_mt_product( array &$mt_ecom, array $mod, $mixed ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$is_variation = false;	// Default value.

			if ( $product = $this->p->util->wc->get_variation_product( $mixed ) ) {	// Get the product for the variation.

				$is_variation = true;
				$variation    = $mixed;

			} elseif ( is_object( $mixed ) ) {

				if ( $mixed instanceof WC_Product ) {	// WC_Product, WC_Product_Variable, or WC_Product_Grouped.

					$product = $mixed;

				} elseif ( $mixed instanceof WP_Post ) {

					if ( SucomUtilWP::is_post_type( $mixed, $this->prod_post_type ) ) {

						$product = $this->p->util->wc->get_product( $mixed->ID );

					} else return false;

				} else return false;

			} else return false;	// $mixed is not a variation array, product or post object.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product = ' . get_class( $product ) );	// WC_Product, WC_Product_Variable, or WC_Product_Grouped.
			}

			$product_id         = $this->p->util->wc->get_product_id( $product );	// Returns product id from product object.
			$product_parent_id  = $is_variation ? $product->get_parent_id() : $product_id;
			$product_parent     = $is_variation ? $this->p->util->wc->get_product( $product_parent_id ) : $product;
			$product_incl_vat   = $this->p->options[ 'plugin_product_include_vat' ] ? true : false;
			$product_price      = $this->get_product_price( $product );
			$product_price_fmtd = $this->get_product_price_formatted( $product, $product_price, $product_incl_vat );
			$product_currency   = $this->get_product_currency();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product_id = ' . $product_id );
				$this->p->debug->log( 'product_parent_id = ' . $product_parent_id );
				$this->p->debug->log( 'product_parent = ' . get_class( $product_parent ) );
				$this->p->debug->log( 'product_incl_vat = ' . ( $product_incl_vat ? 'true' : 'false' ) );
				$this->p->debug->log( 'product_price = ' . $product_price );
				$this->p->debug->log( 'product_price_fmtd = ' . $product_price_fmtd );
				$this->p->debug->log( 'product_currency = ' . $product_currency );
			}

			if ( $is_variation ) {

				/*
		 		 * $mod contains the main product information (not the variant).
		 		 * $product contains the main product object (not the variant).
		 		 * $variation contains the variation information.
				 */
				$this->add_product_variation_title( $mt_ecom, $mod, $product, $variation );
				$this->add_product_variation_description( $mt_ecom, $mod, $product, $variation );

			} else {

				$mt_ecom[ 'product:title' ]       = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_title' );
				$mt_ecom[ 'product:description' ] = $this->p->page->get_description( $mod, $md_key = 'schema_desc', $max_len = 'schema_desc' );
			}

			/*
			 * Note that the 'product:retailer_item_id' value is important for Schema ProductGroup and hasVariant
			 * markup. The 'product:item_group_id' must be provided for variations, but not simple products (as they
			 * are not in a product group).
			 */
			$mt_ecom[ 'product:updated_time' ]     = $mod[ 'post_modified_time' ];
			$mt_ecom[ 'product:url' ]              = $this->get_product_url( $product );
			$mt_ecom[ 'product:retailer_item_id' ] = $product_id;					// Product ID.
			$mt_ecom[ 'product:retailer_part_no' ] = $product->get_sku();				// Product SKU.
			$mt_ecom[ 'product:item_group_id' ]    = $is_variation ? $product_parent_id : '';	// Product variation group ID.
			$mt_ecom[ 'product:is_virtual' ]       = $product->is_virtual();

			/*
			 * Add product availability.
			 *
			 * See https://woocommerce.github.io/code-reference/classes/WC-Product.html#method_get_manage_stock.
			 * See https://woocommerce.github.io/code-reference/classes/WC-Product.html#method_get_stock_quantity.
			 */
			if ( $product->get_manage_stock() ) {	// Returns trus, false, or 'parent'.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product stock is managed' );
				}

				$mt_ecom[ 'product:quantity' ] = $product->get_stock_quantity();
			}

			/*
			 * Add product availability.
			 *
			 * See https://woocommerce.github.io/code-reference/classes/WC-Product.html#method_is_in_stock.
			 * See https://woocommerce.github.io/code-reference/classes/WC-Product.html#method_is_on_backorder.
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

				$var_mod = $this->p->page->get_mod( $product_id );

				$var_opts = $var_mod[ 'obj' ]->get_options( $var_mod[ 'id' ] );

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
				 * 'wpsso_get_post_options' filters, custom field values may get overwritten by these filters.
				 *
				 * The 'import_custom_fields' filter is also executed before the 'wpsso_get_md_defaults' and
				 * 'wpsso_get_post_defaults' filters, so submitted form values that are identical to their defaults
				 * can be removed before saving the options array.
				 *
				 * See WpssoPost->get_options().
				 * See WpssoAbstractWpMeta->get_defaults().
				 * See WpssoUtilCustomFields->filter_import_custom_fields().
				 * See WpssoIntegEcomWooCommerce->add_mt_product().
				 * See WpssoIntegEcomWooAddGtin->filter_wc_variation_alt_options().
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters "wpsso_import_custom_fields" for variation id ' . $product_id . ' metadata' );
				}

				$var_opts = apply_filters( 'wpsso_import_custom_fields', $var_opts, $mod, $var_wp_meta, $alt_opts );

				unset( $var_wp_meta, $alt_opts );

				/*
				 * Since WPSSO Core v14.2.0.
				 *
				 * See WpssoIntegEcomWooCommerce->add_mt_product().
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters "wpsso_import_product_attributes" for variation id ' . $product_id );
				}

				$var_opts = apply_filters( 'wpsso_import_product_attributes', $var_opts, $mod, $variation );

				/*
				 * Since WPSSO Core v12.2.0.
				 *
				 * Overwrite parent options with those of the child, allowing only undefined child options to be
				 * inherited from the parent.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'inheriting variable parent metadata options' );
				}

				$product_parent_opts = $var_mod[ 'obj' ]->get_inherited_md_opts( $var_mod );

				if ( ! empty( $product_parent_opts ) ) {

					$var_opts = SucomUtil::array_merge_recursive_distinct( $product_parent_opts, $var_opts );
				}

				unset( $product_parent_opts );

				/*
				 * Add custom fields meta data to the Open Graph meta tags.
				 */
				$this->p->og->add_data_og_type_md( $mt_ecom, 'product', $var_opts );

			} else {	// Not a variation.

				/*
				 * Product variations do not have terms (categories or tags) so skip this section for variations.
				 *
				 * Retrieve the terms of the taxonomy that are attached to the post ID.  get_the_terms() returns an
				 * array of WP_Term objects, false if there are no terms (or the post does not exist), or a
				 * WP_Error object on failure.
				 */
				$terms = get_the_terms( $product_id, $this->tag_taxonomy );

				if ( is_array( $terms ) ) {	// Not false or WP_Error object.

					foreach( $terms as $term ) {

						$mt_ecom[ 'product:tag' ][] = $term->name;
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting price and pretax price formatted' );
			}

			$mt_ecom[ 'product:pretax_price:amount' ]   = $this->get_product_price_formatted( $product, $product_price, false );	// Exclude VAT.
			$mt_ecom[ 'product:pretax_price:currency' ] = $product_currency;
			$mt_ecom[ 'product:price:amount' ]          = $product_price_fmtd;
			$mt_ecom[ 'product:price:currency' ]        = $product_currency;
			$mt_ecom[ 'product:price:vat_included' ]    = $product_incl_vat;

			if ( method_exists( $product, 'get_regular_price' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting original price formatted' );
				}

				$regular_price      = $product->get_regular_price();
				$regular_price_fmtd = $this->get_product_price_formatted( $product, $regular_price, $product_incl_vat );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'get_regular_price() returned "' . $regular_price . '" (type is ' . gettype( $regular_price ) . ')' );
				}

				$mt_ecom[ 'product:original_price:type' ]     = $this->p->get_options( 'schema_def_product_price_type', 'https://schema.org/ListPrice' );
				$mt_ecom[ 'product:original_price:amount' ]   = $regular_price_fmtd;
				$mt_ecom[ 'product:original_price:currency' ] = $product_currency;

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product get_regular_price() method not found' );
			}

			if ( $product->is_on_sale() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is on sale' );
				}

				$mt_ecom[ 'product:price:type' ] = $mt_ecom[ 'product:sale_price:type' ] = 'https://schema.org/SalePrice';

				if ( method_exists( $product, 'get_sale_price' ) ) {

					$sale_price      = $product->get_sale_price();
					$sale_price_fmtd = $this->get_product_price_formatted( $product, $sale_price, $product_incl_vat );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'get_sale_price() returned "' . $sale_price . '" (type is ' . gettype( $sale_price ) . ')' );
					}

					$mt_ecom[ 'product:sale_price:amount' ]   = $sale_price_fmtd;
					$mt_ecom[ 'product:sale_price:currency' ] = $product_currency;

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
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting product shipping dimensions' );
			}

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
			 * Add shipping offers.
			 */
			$this->add_mt_shipping_offers( $mt_ecom, $mod, $product, $product_parent );
		}

		/*
		 * Add shipping information.
		 *
		 * The $shipping_class_id corresponds to the "Shipping class" selected when editing a product.
		 *
		 * Unless $product is a WC_Product_Variation, $product and $product_parent will be the same.
		 */
		private function add_mt_shipping_offers( array &$mt_ecom, $mod, WC_Product $product, WC_Product $product_parent ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Define static variables only once to prevent duplicate WooCommerce queries.
			 */
			static $shipping_zones      = null;
			static $shipping_continents = null;
			static $shipping_countries  = null;
			static $shipping_states     = null;
			static $shipping_enabled    = null;
			static $world_zone_id       = null;
			static $world_zone_obj      = null;
			static $world_zone_name     = null;
			static $world_zone_methods  = null;

			if ( null === $shipping_zones ) {

				$shipping_zones      = WC_Shipping_Zones::get_zones( $context = 'admin' );
				$shipping_zones      = apply_filters( 'wpsso_wc_shipping_zones', $shipping_zones );
				$shipping_continents = WC()->countries->get_shipping_continents();
				$shipping_countries  = WC()->countries->get_shipping_countries();
				$shipping_states     = WC()->countries->get_states();
				$shipping_enabled    = $shipping_continents || $shipping_countries ? true : false;

				$world_zone_id      = 0;
				$world_zone_obj     = WC_Shipping_Zones::get_zone( $world_zone_id );	// Locations not covered by your other zones.
				$world_zone_name    = __( 'World', 'wpsso' );
				$world_zone_methods = $world_zone_obj->get_shipping_methods();
			}

			$product_id         = $this->p->util->wc->get_product_id( $product );
			$product_url        = $this->get_product_url( $product );
			$product_can_ship   = $product->needs_shipping();
			$product_parent_url = $this->get_product_url( $product_parent );
			$product_currency   = $this->get_product_currency();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'shipping enabled = ' . ( $shipping_enabled ? 'true' : 'false' ) );
				$this->p->debug->log( 'product can ship = ' . ( $product_can_ship ? 'true' : 'false' ) );
			}

			/*
			 * The WPSSO WCSDT add-on returns true at priority -1000.
			 *
			 * The WPSSO GMF add-on returns true or false (depending on the 'gmf_add_shipping' option) at priority 1000.
			 *
			 * See WpssoGmfXml->get().
			 * See WpssoWcsdtFilters->__construct().
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
				static $local_fifo = array();

				foreach ( $shipping_zones as $zone_id => $zone ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'zone id = ' . $zone_id );
					}

					if ( isset( $local_fifo[ $zone_id ] ) ) {

						list( $zone_obj, $zone_name, $zone_locations, $zone_methods ) = $local_fifo[ $zone_id ];

					} else {

						$zone_obj       = WC_Shipping_Zones::get_zone( $zone_id );
						$zone_name      = $zone_obj->get_zone_name( $context = 'admin' );
						$zone_locations = $zone_obj->get_zone_locations( $context = 'admin' );
						$zone_methods   = $zone_obj->get_shipping_methods( $enabled_only = true, $context = 'admin' );
						$zone_methods   = apply_filters( 'wpsso_wc_shipping_zone_methods', $zone_methods, $zone_id, $zone_obj );

						/*
						 * Maybe limit the number of array elements.
						 */
						$local_fifo = SucomUtil::array_slice_fifo( $local_fifo, WPSSO_CACHE_ARRAY_FIFO_MAX );

						$local_fifo[ $zone_id ] = array( $zone_obj, $zone_name, $zone_locations, $zone_methods );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log_size( 'local_fifo', $local_fifo );
						}
					}

					$zone_ship_dest = $this->get_zone_shipping_destinations( $zone_id, $zone_obj, $zone_locations,
						$shipping_continents, $shipping_countries, $shipping_states, $product_parent_url );

					/*
					 * Get shipping methods and rates for this zone.
					 */
					foreach ( $zone_methods as $method_inst_id => $method_obj ) {

						/*
						 * Returns false or a shipping offer options array.
						 */
						$shipping_offer = $this->get_zone_method_shipping_offer( $zone_id, $zone_name,
							$method_inst_id, $method_obj, $shipping_class_id, $product, $product_parent );

						if ( $shipping_offer ) {

							if ( empty( $zone_ship_dest ) ) {	// Ships to the World.

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'shipping offer for zone ' . $zone_name . ' ships to world' );
								}

								$shipping_offer[ 'shipping_destinations' ] = $this->get_world_shipping_destinations();

							} else {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'shipping offer for zone ' . $zone_name . ' ships to destinations' );
								}

								$shipping_offer[ 'shipping_destinations' ] = $zone_ship_dest;
							}

							$mt_ecom[ 'product:shipping_offers' ][] = $shipping_offer;

						} elseif ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no shipping offer for zone ' . $zone_name );
						}

					} // End of $zone_methods loop.

					unset( $method_inst_id, $method_obj, $shipping_offer );

				} // End of $shipping_zones loop.

				unset( $zone_id, $zone, $zone_obj, $zone_name, $zone_locations, $zone_methods, $zone_ship_dest );

				/*
				 * Get shipping methods and rates for the world zone.
				 */
				if ( ! empty( $world_zone_methods ) ) {

					foreach ( $world_zone_methods as $method_inst_id => $method_obj ) {

						/*
						 * Returns false or a shipping offer options array.
						 */
						$shipping_offer = $this->get_zone_method_shipping_offer( $world_zone_id, $world_zone_name,
							$method_inst_id, $method_obj, $shipping_class_id, $product, $product_parent );

						if ( $shipping_offer ) {

							$shipping_offer[ 'shipping_destinations' ] = $this->get_world_shipping_destinations();

							$mt_ecom[ 'product:shipping_offers' ][] = $shipping_offer;

						} elseif ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no shipping offer for zone World' );
						}

					} // End of $world_zone_methods loop.

					unset ( $method_inst_id, $method_obj, $shipping_offer );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no shipping methods for zone World' );
				}
			}
		}

		private function add_mt_ratings( array &$mt_ecom, $mod, WC_Product $product ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$wc_reviews_enabled = apply_filters( 'wpsso_og_add_wc_mt_reviews', $this->reviews_enabled );
			$wc_rating_enabled  = apply_filters( 'wpsso_og_add_wc_mt_rating', $this->rating_enabled );

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

							$mt_ecom[ $this->og_type . ':rating:average' ] = $average_rating;
							$mt_ecom[ $this->og_type . ':rating:count' ]   = $rating_count;
							$mt_ecom[ $this->og_type . ':rating:worst' ]   = $this->worst_rating;
							$mt_ecom[ $this->og_type . ':rating:best' ]    = $this->best_rating;
							$mt_ecom[ $this->og_type . ':review:count' ]   = $review_count;

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

		}

		private function add_mt_reviews( array &$mt_ecom, $mod, WC_Product $product ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$wc_reviews_enabled = apply_filters( 'wpsso_og_add_wc_mt_reviews', $this->reviews_enabled );
			$wc_rating_enabled  = apply_filters( 'wpsso_og_add_wc_mt_rating', $this->rating_enabled );

			if ( apply_filters( 'wpsso_og_add_mt_reviews', true, $mod ) ) {

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

					$mt_ecom[ $this->og_type . ':reviews' ] = $mod[ 'obj' ]->get_mt_reviews( $mod[ 'id' ],
						$this->rating_meta, $this->worst_rating, $this->best_rating );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'woocommerce reviews are disabled' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add reviews meta tags is false' );
			}

		}

		private function get_world_shipping_destinations() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = null;

			if ( null === $local_cache ) {

				$all_countries = SucomUtil::get_alpha2_countries();

				foreach ( $all_countries as $country_code => $country_name ) {

					$local_cache[] = array(
						'destination_id'  => 'country-a2-' . $country_code,
						'destination_rel' => '/',
						'country_code'    => $country_code,
					);
				}
			}

			return $local_cache;
		}

		private function get_zone_shipping_destinations( $zone_id, $zone_obj, $zone_locations,
			$shipping_continents, $shipping_countries, $shipping_states, $product_parent_url ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_fifo = array();

			if ( isset( $local_fifo[ $zone_id ][ $product_parent_url ] ) ) {

				return $local_fifo[ $zone_id ][ $product_parent_url ];

			} elseif ( isset( $local_fifo[ $zone_id ] ) ) {

				/*
				 * Maybe limit the number of array elements.
				 */
				$local_fifo[ $zone_id ] = SucomUtil::array_slice_fifo( $local_fifo[ $zone_id ], WPSSO_CACHE_ARRAY_FIFO_MAX );

			} else $local_fifo[ $zone_id ] = array();

			$base_location  = wc_get_base_location();	// Example: array( [country] => US [state] => CA ).
			$zone_ship_dest = array();

			foreach ( $zone_locations as $location_key => $location_obj ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'zone location ' . $location_key. ' ' . $location_obj->type . ' = ' . $location_obj->code );
				}

				$dest_opts = array();

				if ( 'continent' === $location_obj->type ) {

					if ( isset( $shipping_continents[ $location_obj->code ][ 'countries' ] ) ) {

						$dest_opts[ 'country_code' ] = $shipping_continents[ $location_obj->code ][ 'countries' ];
					}

				} elseif ( 'country' === $location_obj->type ) {

					if ( isset( $shipping_countries[ $location_obj->code ] ) ) {	// Just in case.

						$dest_opts[ 'country_code' ] = $location_obj->code;
					}

				} elseif ( 'state' === $location_obj->type ) {

					$codes = explode( ':', $location_obj->code );

					if ( isset( $shipping_countries[ $codes[ 0 ] ] ) ) {	// Just in case.

						if ( isset( $shipping_states[ $codes[ 0 ] ][ $codes[ 1 ] ] ) ) {	// Just in case.

							$dest_opts[ 'country_code' ] = $codes[ 0 ];
							$dest_opts[ 'region_code' ]  = $codes[ 1 ];
						}
					}

				} elseif ( 'postcode' === $location_obj->type ) {

					$dest_opts[ 'country_code' ] = apply_filters( 'wpsso_wc_shipping_zone_location_postal_code_country',
						$base_location[ 'country' ], $zone_obj, $location_key, $location_obj );

					$dest_opts[ 'postal_code' ] = $location_obj->code;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'dest_opts', $dest_opts );
				}

				if ( ! empty( $dest_opts ) ) {

					$dest_opts[ 'destination_id' ]  = 'dest-z' . $zone_id . '-d' . $location_key;
					$dest_opts[ 'destination_rel' ] = $product_parent_url;

					$zone_ship_dest[] = $dest_opts;
				}

			} // End of $zone_locations.

			unset( $location_key, $location_obj, $dest_opts );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'zone_ship_dest', $zone_ship_dest );
			}

			$local_fifo[ $zone_id ][ $product_parent_url ] = $zone_ship_dest;

			unset ( $zone_ship_dest );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_size( 'local_fifo', $local_fifo );
			}

			return $local_fifo[ $zone_id ][ $product_parent_url ];
		}

		/*
		 * Returns false or a shipping offer options array.
		 */
		private function get_zone_method_shipping_offer( $zone_id, $zone_name, $method_inst_id, $method_obj, $shipping_class_id,
			WC_Product $product, WC_Product $product_parent ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'method instance id = ' . $method_inst_id );
			}

			$product_price      = $this->get_product_price( $product );
			$product_currency   = $this->get_product_currency();
			$product_parent_url = $this->get_product_url( $product_parent );

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

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'rate type = ' . $rate_type );
			}

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
			} else $rate_cost = 0;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'rate cost = ' . $rate_cost );
			}

			/*
			 * Maybe resolve the shipping [fee] shortcode.
			 *
			 * See woocommerce/includes/shipping/flat-rate/class-wc-shipping-flat-rate.php.
			 */
			if ( ! empty( $rate_cost ) && ! is_numeric( $rate_cost ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'resolving [fee] shortcode for product qty 1 x price ' . $product_price );
				}

				/*
				 * evaluate_cost() is protected, so make it accessible.
				 *
				 * See https://www.php.net/manual/en/class.reflectionmethod.php.
				 */
				$reflect = new ReflectionMethod( $method_obj, 'evaluate_cost' );	// Since PHP v5.4.

				$reflect->setAccessible( true );

				/*
				 * Call the protected evaluate_cost method.
				 *
				 * See woocommerce/includes/shipping/flat-rate/class-wc-shipping-flat-rate.php line 75.
				 */
				$rate_cost = $reflect->invoke( $method_obj, $rate_cost, array( 'qty'  => 1, 'cost' => $product_price ) );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'resolved rate cost = ' . $rate_cost );
				}
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
						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no markup for shipping requires = ' . $method_data[ 'requires' ] );
						}

						return false;

					default:		// Unknown requirement.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'unknown shipping requires = ' . $method_data[ 'requires' ] );
						}

						return false;
				}
			}

			if ( is_numeric( $rate_cost ) ) {	// Just in case.

				$shipping_rate = array(
					'shipping_rate_name'     => $rate_name,
					'shipping_rate_cost'     => $rate_cost,
					'shipping_rate_currency' => $product_currency,
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
				 *
				 * See WpssoWcsdtFilters->filter_wc_shipping_delivery_time().
				 */
				$delivery_time = apply_filters( 'wpsso_wc_shipping_delivery_time', array(),
					$zone_id, $method_inst_id, $shipping_class_id, $product_parent_url );

				$shipping_offer = array(
					'shipping_id'   => 'shipping-z' . $zone_id . '-m' . $method_inst_id . '-c' . $shipping_class_id,
					'shipping_rel'  => $product_parent_url,
					'shipping_name' => $zone_name,
					'shipping_rate' => $shipping_rate,
					'delivery_time' => $delivery_time,
				);

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'invalid rate cost = ' . $rate_cost );
			}

			$shipping_offer = apply_filters( 'wpsso_wc_shipping_zone_offer', $shipping_offer,
				$zone_id, $zone_name, $method_inst_id, $method_obj, $shipping_class_id, $product, $product_parent );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'shipping_offer', $shipping_offer );
			}

			return $shipping_offer;
		}

		/*
		 * Example:
		 *
		 *	list(
		 *		$mt_ecom[ 'product:shipping_length:value' ],
		 *		$mt_ecom[ 'product:shipping_length:units' ],
		 *		$mt_ecom[ 'product:shipping_width:value' ],
		 *		$mt_ecom[ 'product:shipping_width:units' ],
		 *		$mt_ecom[ 'product:shipping_height:value' ],
		 *		$mt_ecom[ 'product:shipping_height:units' ],
		 *		$mt_ecom[ 'product:shipping_weight:value' ],
		 *		$mt_ecom[ 'product:shipping_weight:units' ],
		 *	) = $this->get_shipping_length_width_height_weight( $product );
		 *
		 * See WpssoIntegEcomWooCommerce->add_mt_product().
		 * See WpssoIntegEcomWooCommerce->filter_get_md_defaults().
		 */
		private function get_shipping_length_width_height_weight( WC_Product $product ) {	// WC_Product, WC_Product_Variable, or WC_Product_Grouped.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product = ' . get_class( $product ) );	// WC_Product, WC_Product_Variable, or WC_Product_Grouped.
			}

			$has_dimensions      = $product->has_dimensions();
			$has_weight          = $product->has_weight();
			$dimension_unit_text = WpssoUtilUnits::get_dimension_text();	// Returns 'og_def_dimension_units' value.
			$weight_unit_text    = WpssoUtilUnits::get_weight_text();	// Returns 'og_def_weight_units' value.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'has_dimensions = ' . ( $has_dimensions ? 'true' : 'false' ) );
				$this->p->debug->log( 'has_weight = ' . ( $has_weight ? 'true' : 'false' ) );
				$this->p->debug->log( 'dimension_unit_text = ' . $dimension_unit_text );
				$this->p->debug->log( 'weight_unit_text = ' . $weight_unit_text );
			}

			$ship_dims_weight    = array(
				0 => null,			// Shipping length value.
				1 => $dimension_unit_text,	// Shipping lenth units.
				2 => null,			// Shipping width value.
				3 => $dimension_unit_text,	// Shipping width units.
				4 => null,			// Shipping height value.
				5 => $dimension_unit_text,	// Shipping height units.
				6 => null,			// Shipping weight value.
				7 => $weight_unit_text,		// Shipping weight units.
			);

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting product shipping dimensions' );
			}

			if ( is_callable( array( $product, 'get_length' ) ) ) {

				$length = $product->get_length();	// Shipping length.

				if ( is_numeric( $length ) ) {		// Required to ignore undefined values.

					$ship_dims_weight[ 0 ] = $length;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product shipping length is not numeric' );
				}
			}

			if ( is_callable( array( $product, 'get_width' ) ) ) {

				$width = $product->get_width();	// Shipping width.

				if ( is_numeric( $width ) ) {	// Required to ignore undefined values.

					$ship_dims_weight[ 2 ] = $width;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product shipping width is not numeric' );
				}
			}

			if ( is_callable( array( $product, 'get_height' ) ) ) {

				$height = $product->get_height();	// Shipping height.

				if ( is_numeric( $height ) ) {		// Required to ignore undefined values.

					$ship_dims_weight[ 4 ] = $height;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product shipping height is not numeric' );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting product shipping weight' );
			}

			if ( is_callable( array( $product, 'get_weight' ) ) ) {	// Just in case.

				$weight = $product->get_weight();	// Shipping weight.

				if ( is_numeric( $weight ) ) {		// Required to ignore undefined values.

					$ship_dims_weight[ 6 ] = $weight;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product shipping weight is not numeric' );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'ship_dims_weight', $ship_dims_weight );
			}

			return $ship_dims_weight;
		}

		/*
		 * See WpssoIntegEcomWooCommerce->filter_head_cache_index().
		 */
		private function get_product_currency() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = null;

			if ( null === $local_cache ) {	// Get value only once.

				$local_cache = get_woocommerce_currency();

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'get_woocommerce_currency() returned ' . $local_cache );
				}

				$local_cache = apply_filters( 'wpsso_product_currency', $local_cache );
			}

			return $local_cache;
		}

		/*
		 * Get product availability.
		 *
		 * See https://woocommerce.github.io/code-reference/classes/WC-Product.html#method_is_in_stock
		 *
		 * Hook 'woocommerce_product_is_in_stock' (returns true or false) to customize the "in stock" status.
		 */
		private function get_product_avail( $product ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$product_avail = null;

			if ( $product->is_in_stock() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is in stock' );
				}

				$product_avail = 'https://schema.org/InStock';

			} elseif ( $product->is_on_backorder() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is on backorder' );
				}

				$product_avail = 'https://schema.org/BackOrder';

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product is out of stock' );
				}

				$product_avail = 'https://schema.org/OutOfStock';
			}

			return $product_avail;
		}

		private function get_product_price( $product ) {

			$product_price = $product->get_price();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'get_price() returned "' . $product_price . '" (type is ' . gettype( $product_price ) . ')' );
			}

			$product_price = apply_filters( 'wpsso_product_price', $product_price, $product );

			/*
			 * Check that the product price returned by WooCommerce is not an empty string, to avoid an error in:
			 *
			 * PHP Fatal error: Uncaught TypeError: Unsupported operand types: string * float in
			 *	woocommerce/includes/shipping/flat-rate/class-wc-shipping-flat-rate.php:141
			 */
			if ( empty( $product_price ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning product price 0' );
				}

				$product_price = 0;
			}

			return $product_price;
		}

		private function get_product_price_formatted( $product, $product_price, $product_incl_vat = false ) {

			if ( is_numeric( $product_price ) ) {	// Just in case.

				if ( $product_incl_vat ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'calling wc_get_price_including_tax() for ' . $product_price );
					}

					$product_price = wc_get_price_including_tax( $product, array( 'price' => $product_price ) );	// Since WC v3.0.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wc_get_price_including_tax() returned ' . $product_price );
					}
				}

				/*
				 * $decimals = Number of decimal points, blank to use woocommerce_price_num_decimals, or false to
				 * avoid all rounding.
				 */
				$product_price = wc_format_decimal( $product_price, $decimals = '', $trim_zeros = false );

			} else $product_price = '';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'product price formatted = ' . $product_price );
			}

			return $product_price;
		}

		private function get_product_title( WC_Product $product ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$title_text = $product->get_title();

			return apply_filters( 'wpsso_product_title', $title_text, $product );
		}

		private function get_product_url( WC_Product $product ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$url = $product->get_permalink();

			return apply_filters( 'wpsso_product_url', $url, $product );
		}

		private function add_product_variation_title( &$mt_ecom, $mod, WC_Product $product, $variation ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mt_ecom[ 'product:title' ] = $this->get_product_variation_title( $mod, $product, $variation );
		}

		private function get_product_variation_title( $mod, WC_Product $product, $variation ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$title_text = $this->p->opt->get_text( 'plugin_product_var_title' );

			$var_attrs = array_filter( array_values( $product->get_variation_attributes() ) );

			$title_atts = array(
				'var_title' => $this->get_product_title( $product ),
				'var_sku'   => $product->get_sku(),
				'var_attrs' => implode( ' %%sep%% ', $var_attrs ),
			);

			$title_text = $this->p->util->inline->replace_variables( $title_text, $mod, $title_atts );

			return apply_filters( 'wpsso_product_variation_title', $title_text, $product, $variation );
		}

		/*
		 * Empty variation descriptions are fixed in WpssoOpenGraphNS->filter_og_data_https_ogp_me_ns_product().
		 */
		private function add_product_variation_description( &$mt_ecom, $mod, WC_Product $product, $variation ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mt_ecom[ 'product:description' ] = $this->get_product_variation_description( $mod, $product, $variation );
		}

		private function get_product_variation_description( $mod, WC_Product $product, $variation ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$desc_text = empty( $variation[ 'variation_description' ] ) ?
				null : $this->p->util->cleanup_html_tags( $variation[ 'variation_description' ] );

			$desc_text = apply_filters( 'wpsso_the_description', $desc_text, $mod );

			return apply_filters( 'wpsso_product_variation_description', $desc_text, $product, $variation );
		}

		private function is_variation_selectable_attribute( $product_id, $product, $attr_name ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'product_id' => $product_id,
					'product'    => $product,
					'attr_name'  => $attr_name,
				) );
			}

			if ( method_exists( $product, 'get_variation_attributes' ) ) {	// Just in case.

				static $local_fifo = array();

				if ( ! isset( $local_fifo[ $product_id ] ) ) {

					/*
					 * Maybe limit the number of array elements.
					 */
					$local_fifo = SucomUtil::array_slice_fifo( $local_fifo, WPSSO_CACHE_ARRAY_FIFO_MAX );

					$local_fifo[ $product_id ] = $product->get_variation_attributes();
				}

				if ( isset( $local_fifo[ $product_id ][ $attr_name ] ) && is_array( $local_fifo[ $product_id ][ $attr_name ] ) ) {

					return true;
				}
			}

			return false;
		}
	}
}
