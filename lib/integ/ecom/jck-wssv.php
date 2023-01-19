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

/**
 * Integration module for the WooCommerce Show Single Variations plugin.
 *
 * See https://iconicwp.com/products/woocommerce-show-single-variations/.
 */
if ( ! class_exists( 'WpssoIntegEcomJckWssv' ) ) {

	class WpssoIntegEcomJckWssv {

		private $p;	// Wpsso class object.

		private static $meta_name = '_jck_wssv_display_title';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'variation_title' => 2,
			) );
		}

		public function filter_variation_title( $title, $variation ) {

			$jck_wssv_display_title = (string) get_post_meta( $variation[ 'variation_id' ], self::$meta_name, true );

			if ( ! empty( $jck_wssv_display_title ) ) {

				$title = $jck_wssv_display_title;
			}

			return $title;
		}
	}
}
