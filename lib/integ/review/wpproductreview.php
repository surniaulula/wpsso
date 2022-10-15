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

if ( ! class_exists( 'WpssoIntegReviewWpProductReview' ) ) {

	class WpssoIntegReviewWpProductReview {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'get_post_options'  => 3,
				'save_post_options' => 3,
			) );
		}

		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $this->get_review_id( $post_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post ID ' . $post_id . ' is not a review' );
				}

				return $md_opts;
			}

			$review_opts = $this->get_review_options( $post_id );

			foreach ( $review_opts as $key => $val ) {

				$md_opts[ $key ]               = $val;
				$md_opts[ $key . ':disabled' ] = true;
			}

			return $md_opts;
		}

		public function filter_save_post_options( array $md_opts, $post_id, array $mod ) {

			if ( ! $this->get_review_id( $post_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post ID ' . $post_id . ' is not a review' );
				}

				return $md_opts;
			}

			$review_opts = $this->get_review_options( $post_id );	// From post meta if available.

			foreach ( $review_opts as $key => $val ) {		// Remove auto-updated review options.

				unset( $md_opts[ $key ] );
			}

			return $md_opts;
		}

		public function get_review_options( $post_id ) {

			return array(
				'schema_review_rating'      => (float) get_post_meta( $post_id, $rating_meta = 'wppr_rating', $single = true ),
				'schema_review_rating_from' => 1,
				'schema_review_rating_to'   => 100,
			);
		}

		public function get_review_id( $post_id ) {

			static $ids_cache = array();	// Cache for $post_id => $review_id.

			if ( isset( $ids_cache[ $post_id ] ) ) {

				return $ids_cache[ $post_id ];
			}

			/**
			 * Check for sumitted value which may not have been saved yet.
			 */
			if ( ! $review_enabled = SucomUtil::get_request_value( 'cwp_meta_box_check', 'POST' ) ) {	// Uses sanitize_text_field.

				$review_enabled = get_post_meta( $post_id, 'cwp_meta_box_check', $single = true );
			}

			if ( SucomUtil::is_true( $review_enabled ) ) {	// Handles true/false/yes/no.

				$review_id = $post_id;

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'post_id ' . $post_id . ' review checkbox is disabled' );
				}

				$review_id = false;
			}

			return $ids_cache[ $post_id ] = $review_id;
		}
	}
}
