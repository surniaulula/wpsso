<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

/**
 * Integration module for the Rate my Post plugin.
 *
 * https://wordpress.org/plugins/rate-my-post/
 */
if ( ! class_exists( 'WpssoIntegRatingRateMyPost' ) ) {

	class WpssoIntegRatingRateMyPost {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'og' => 2,
			), $prio = 2000 );	// Run after the WPSSO RAR add-on.

			add_action( 'rmp_after_vote', array( $this, 'clear_post_cache' ), 10, 4 );

			if ( is_admin() ) {

				$this->conflict_check();
			}
		}

		private function conflict_check() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$opts = get_option( 'rmp_options' );

			$structured = isset( $opts[ 'structuredDataType' ] ) ? $opts[ 'structuredDataType' ] : false;

			if ( ! empty( $structured ) && 'none' !== $structured ) {

				$log_pre = 'plugin conflict detected - ';

				$notice_pre =  __( 'Plugin conflict detected:', 'wpsso' ) . ' ';

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Type of structured data for rich snippets', 'rate-my-post' ) . '</strong>';

				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=rate-my-post' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Rate my Post', 'rate-my-post' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Settings', 'rate-my-post' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Rating Widget Settings', 'rate-my-post' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $log_pre . 'rate-my-post structuredDataType option is enabled' );
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

				$average_rating = (float) Rate_My_Post_Common::get_average_rating( $mod[ 'id' ] );
				$rating_count   = (int) Rate_My_Post_Common::get_vote_count( $mod[ 'id' ] );
				$worst_rating   = 1;
				$best_rating    = (int) Rate_My_Post_Common::max_rating();

				/**
				 * An average rating value must be greater than 0.
				 */
				if ( $average_rating > 0 ) {

					/**
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

		public function clear_post_cache( $post_id, $avg_rating, $new_vote_count, $submitted_rating ) {

			return $this->p->post->clear_cache( $post_id );
		}
	}
}
