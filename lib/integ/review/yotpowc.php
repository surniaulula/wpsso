<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoIntegReviewYotpoWc' ) ) {

	class WpssoIntegReviewYotpoWc {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'og_ecom_woocommerce' => 2,
			) );
		}

		public function filter_og_ecom_woocommerce( array $mt_ecom, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$yotpo = $this->get_yotpo_instance();

			$have_schema = $this->p->avail[ 'p' ][ 'schema' ] ? true : false;

			if ( ! is_object( $yotpo ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: get_yotpo_instance() did not return an object' );
				}

				return $mt_ecom;
			}

			/*
			 * Add rating meta tags.
			 */
			if ( apply_filters( 'wpsso_og_add_mt_rating', true, $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add rating meta tags is true' );

					$this->p->debug->log( 'calling get_product_bottom_line() for product ID ' . $mod[ 'id' ] );
				}

				$resp = $yotpo->get_product_bottom_line( array(
					'product_id' => $mod[ 'id' ],
				) );

				if ( isset( $resp[ 'response' ][ 'bottomline' ][ 'average_score' ] ) &&
					isset( $resp[ 'response' ][ 'bottomline' ][ 'total_reviews' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding product rating from yotpo API response' );
					}

					$mt_ecom[ 'product:rating:average' ] = (float) $resp[ 'response' ][ 'bottomline' ][ 'average_score' ];
					$mt_ecom[ 'product:rating:count' ]   = (int) $resp[ 'response' ][ 'bottomline' ][ 'total_reviews' ];
					$mt_ecom[ 'product:rating:worst' ]   = 1;
					$mt_ecom[ 'product:rating:best' ]    = 5;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error: average_score and/or total_reviews missing from response' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add rating meta tags is false' );
			}

			/*
			 * Add reviews meta tags.
			 */
			if ( apply_filters( 'wpsso_og_add_mt_reviews', $have_schema, $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add review meta tags is true' );

					$this->p->debug->log( 'calling get_product_reviews() for product ID ' . $mod[ 'id' ] );
				}

				$resp = $yotpo->get_product_reviews( array(
					'product_id' => $mod[ 'id' ],
					'page'       => 1,
					'count'      => WPSSO_SCHEMA_REVIEWS_MAX,
					'since_date' => '',
				) );

				if ( isset( $resp[ 'response' ][ 'total_reviews' ] ) && isset( $resp[ 'response' ][ 'reviews' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding product reviews from yotpo API response' );
					}

					if ( is_array( $resp[ 'response' ][ 'reviews' ] ) ) {	// Just in case.

						foreach ( $resp[ 'response' ][ 'reviews' ] as $review ) {

							$single_review = array(
								'review:id'           => isset( $review[ 'id' ] ) ? $review[ 'id' ] : '',
								'review:url'          => '',
								'review:title'        => isset( $review[ 'title' ] ) ? $review[ 'title' ] : '',
								'review:description'  => isset( $review[ 'content' ] ) ? $review[ 'content' ] : '',
								'review:created_time' => isset( $review[ 'created_at' ] ) ? $review[ 'created_at' ] : '',
								'review:author:id'    => isset( $review[ 'user' ][ 'id' ] ) ? $review[ 'user' ][ 'id' ] : '',
								'review:author:name'  => isset( $review[ 'user' ][ 'display_name' ] ) ? $review[ 'user' ][ 'display_name' ] : '',
								'review:rating:value' => isset( $review[ 'score' ] ) ? (float) $review[ 'score' ] : 0,
								'review:rating:worst' => 1,
								'review:rating:best'  => 5,
							);

							$mt_ecom[ 'product:reviews' ][] = $single_review;
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'error: reviews in response is not an array' );
					}

					$mt_ecom[ 'product:review:count' ] = (int) $resp[ 'response' ][ 'total_reviews' ];

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error: total_reviews and/or reviews missing from response' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add review meta tags is false' );
			}

			return $mt_ecom;
		}

		private function get_yotpo_instance() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $yotpo = null;	// Only load the Yotpo class once.

			if ( null !== $yotpo ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: yotpo class object already defined' );
				}

				return $yotpo;
			}

			$plugin_dir  = false;
			$plugin_slug = 'yotpo-social-reviews-for-woocommerce';
			$settings    = get_option( 'yotpo_settings' );
			$error_pre   = sprintf( __( '%s error:', 'wpsso' ), __METHOD__ );

			if ( empty( $settings[ 'app_key' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "app_key" missing from yotpo settings' );
				}

				if ( is_admin() ) {

					// translators: %s is "App Key".
					$error_msg = sprintf( __( 'Yotpo for WooCommerce "%s" option value is empty.', 'wpsso' ), 'App Key' );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return $yotpo = false;
			}

			if ( empty( $settings[ 'secret' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "secret" missing from yotpo settings' );
				}

				if ( is_admin() ) {

					// translators: %s is "Secret Token".
					$error_msg = sprintf( __( 'Yotpo for WooCommerce "%s" option value is empty.', 'wpsso' ), 'Secret Token' );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return $yotpo = false;
			}

			if ( ! function_exists( 'wc_yotpo_compatible' ) ) {	// Just in case

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: wc_yotpo_compatible() function missing' );
				}

				if ( is_admin() ) {

					// translators: %s is "wc_yotpo_compatible()".
					$error_msg = sprintf( __( 'Yotpo for WooCommerce %s function is missing.', 'wpsso' ), '<code>wc_yotpo_compatible()</code>' );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return $yotpo = false;

			}

			if ( ! wc_yotpo_compatible() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: wc_yotpo_compatible() returned false' );
				}

				if ( is_admin() ) {

					// translators: %s is "wc_yotpo_compatible()".
					$error_msg = sprintf( __( 'Yotpo for WooCommerce %s function returned false.', 'wpsso' ), 'wc_yotpo_compatible()' );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return $yotpo = false;
			}

			if ( defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . $plugin_slug ) ) {

				$plugin_dir = WPMU_PLUGIN_DIR;

			} elseif ( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {

				$plugin_dir = WP_PLUGIN_DIR;

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "' . $plugin_slug . '" not in the WPMU_PLUGIN_DIR or WP_PLUGIN_DIR folders' );
				}

				if ( is_admin() ) {

					// translators: %s is "yotpo-social-reviews-for-woocommerce".
					$error_msg = sprintf( __( '"%s" plugin not found in the WPMU_PLUGIN_DIR or WP_PLUGIN_DIR folders.', 'wpsso' ), $plugin_slug );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return $yotpo = false;
			}

			$api_lib = realpath( $plugin_dir . '/' . $plugin_slug . '/lib/yotpo-api/Yotpo.php' );

			if ( ! file_exists( $api_lib ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: API library file "' . $api_lib . '" not found' );
				}

				if ( is_admin() ) {

					// translators: %s is the API library file path.
					$error_msg = sprintf( __( 'Yotpo for WooCommerce API library file "%s" not found.', 'wpsso' ), $api_lib );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return $yotpo = false;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'loading api library file ' . $api_lib );
			}

			require_once $api_lib;

			if ( ! class_exists( 'Yotpo' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: Yotpo API class does not exist' );
				}

				if ( is_admin() ) {

					// translators: %s is the API library file path.
					$error_msg = sprintf( __( 'Yotpo for WooCommerce "%s" class does not exist.', 'wpsso' ), 'Yotpo' );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return $yotpo = false;
			}

			return $yotpo = new Yotpo( $settings[ 'app_key' ], $settings[ 'secret' ] );
		}
	}
}
