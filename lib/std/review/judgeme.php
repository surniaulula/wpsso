<?php
/*
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2020-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdReviewJudgeme' ) ) {

	class WpssoStdReviewJudgeme {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $this->p->notice->is_admin_pre_notices() ) {

				$pkg_info      = $this->p->util->get_pkg_info();	// Uses a local cache.
				$short_pro     = $pkg_info[ 'wpsso' ][ 'short_pro' ];
				$judgeme_short = __( 'Judge.me', 'judgeme-product-reviews-woocommerce' );
				$judgeme_long  = __( 'Judge.me Product Reviews for WooCommerce', 'judgeme-product-reviews-woocommerce' );
				$notice_key    = 'notice-pro-required-judgeme';
				$notice_msg    = sprintf( __( 'The %1$s plugin is active.', 'wpsso' ), $judgeme_long ) . ' ';
				$notice_msg    .= sprintf( __( 'Please note that service API modules, like the one required to retrieve data from the %1$s service API, are provided with the %2$s edition.', 'wpsso' ), $judgeme_short, $short_pro );

				$this->p->notice->warn( $notice_msg, $user_id = null, $notice_key, $dismiss_time = true );
			}
		}
	}
}
