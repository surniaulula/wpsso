<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2024-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegUtilJetpackBoost' ) ) {

	class WpssoIntegUtilJetpackBoost {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! is_admin() ) {

				/*
				 * Exclude the WPSSO schema markup from the Jetpack Boost "Defer Non-Essential JavaScript" function.
				 */
				add_filter( 'jetpack_boost_render_blocking_js_exclude_scripts', array( $this, 'cleanup_defer_scripts' ), 1000, 1 );
			}
		}

		public function cleanup_defer_scripts( array $script_tags ) {

			foreach ( $script_tags as $num => $match ) {

				if ( isset( $match[ 0 ] ) ) {	// Just in case.

					if ( false !== strpos( $match[ 0 ], '<script type="application/ld+json" id="wpsso-' ) ) {

						unset( $script_tags[ $num ] );
					}
				}
			}

			return $script_tags;
		}
	}
}
