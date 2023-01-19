<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2022-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegMediaWpRetina2x' ) ) {

	class WpssoIntegMediaWpRetina2x {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Filter for the get_option() and update_option() functions.
			 */
			add_filter( 'option_wr2x_retina_sizes', array( $this, 'update_wr2x_retina_sizes' ), 1000, 1 );
			add_filter( 'pre_update_option_wr2x_retina_sizes', array( $this, 'update_wr2x_retina_sizes' ), 1000, 1 );
			add_filter( 'wr2x_custom_crop', array( $this, 'filter_set_image_src_args' ), -1000, 3 );

			add_action( 'wr2x_generate_retina', array( $this, 'action_reset_image_src_args' ), -1000, 1 );
		}

		/**
		 * Filter for the get_option() and update_option() functions.
		 *
		 * Prevent Perfect Images + Retina (aka WP Retina 2x) from creating 2x images for WPSSO image sizes.
		 */
		public function update_wr2x_retina_sizes( $mixed ) {

			if ( is_array( $mixed ) ) {

				foreach ( $mixed as $num => $size_name ) {

					if ( 0 === strpos( $size_name, 'wpsso-' ) ) {

						unset( $mixed[ $num ] );
					}
				}
			}

			return $mixed;
		}

		/**
		 * Save arguments for the 'image_make_intermediate_size' and 'image_resize_dimensions' filters.
		 */
		public function filter_set_image_src_args( $custom_crop, $pid, $size_name ) {

			 WpssoMedia::set_image_src_args( $args = array(
			 	'pid'       => $pid,
				'size_name' => $size_name,
			) );

			return $custom_crop;
		}

		public function action_reset_image_src_args( $pid ) {

			WpssoMedia::reset_image_src_args();
		}
	}
}
