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

/*
 * Integration module for the WP-PostRatings plugin.
 *
 * https://wordpress.org/plugins/wp-postratings/
 */
if ( ! class_exists( 'WpssoIntegRatingWpPostRatings' ) ) {

	class WpssoIntegRatingWpPostRatings {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'og' => 2,
			), $prio = 2000 );	// Run after the WPSSO RAR add-on.

			if ( is_admin() ) {

				$this->conflict_check();
			}
		}

		private function conflict_check() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$opts = get_option( 'postratings_options' );

			$richsnippet = isset( $opts[ 'richsnippet' ] ) ? $opts[ 'richsnippet' ] : 1;

			if ( ! empty( $richsnippet ) ) {

				$log_pre = 'plugin conflict detected - ';

				$notice_pre =  __( 'Plugin conflict detected:', 'wpsso' ) . ' ';

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Enable Google Rich Snippets?', 'wp-postratings' ) . '</strong>';

				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=wp-postratings%2Fpostratings-options.php' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'WP-PostRatings', 'wp-postratings' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Ratings', 'wp-postratings' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Ratings Options', 'wp-postratings' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $log_pre . 'wp-postratings richsnippet option is enabled' );
				}

				$this->p->notice->err( $notice_pre . sprintf( __( 'Please disable the %1$s option in the %2$s settings page.', 'wpsso' ),
					$label_transl, $settings_link ) );
			}
		}

		public function filter_og( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

				$average_rating = (float) get_post_meta( $mod[ 'id' ], 'ratings_average', true );
				$rating_count   = (int) get_post_meta( $mod[ 'id' ], 'ratings_users', true );
				$worst_rating   = 1;
				$best_rating    = (int) get_option( 'postratings_max' );

				/*
				 * An average rating value must be greater than 0.
				 */
				if ( $average_rating > 0 ) {

					/*
					 * At least one rating is required.
					 */
					if ( $rating_count > 0 ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'adding rating meta tags for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] );
						}

						$og_type = $mt_og[ 'og:type' ];

						$mt_og[ $og_type . ':rating:average' ] = $average_rating;
						$mt_og[ $og_type . ':rating:count' ]   = $rating_count;
						$mt_og[ $og_type . ':rating:worst' ]   = $worst_rating;
						$mt_og[ $og_type . ':rating:best' ]    = $best_rating;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'rating count is invalid (must be greater than 0)' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'average rating is invalid (must be greater than 0)' );
				}
			}

			return $mt_og;
		}
	}
}
