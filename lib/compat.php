<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoCompat' ) ) {

	/*
	 * Third-party plugin and theme compatibility actions and filters.
	 */
	class WpssoCompat {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			is_admin() ? $this->back_end() : $this->front_end();
		}

		public function back_end() {
		}

		public function front_end() {

			/*
			 * JetPack.
			 */
			if ( ! empty( $this->p->avail[ 'util' ][ 'jetpack' ] ) ) {

				add_filter( 'jetpack_enable_opengraph', '__return_false', 1000 );
				add_filter( 'jetpack_enable_open_graph', '__return_false', 1000 );
				add_filter( 'jetpack_disable_twitter_cards', '__return_true', 1000 );
			}

			/*
			 * JetPack Boost.
			 */
			if ( ! empty( $this->p->avail[ 'util' ][ 'jetpack-boost' ] ) ) {
				
				/*
				 * Exclude the WPSSO schema markup from the Jetpack Boost "Defer Non-Essential JavaScript" function.
				 */
				add_filter( 'jetpack_boost_render_blocking_js_exclude_scripts', array( $this, 'cleanup_jetpack_boost_defer_scripts' ), 1000, 1 );
			}

			/*
			 * NextScripts: Social Networks Auto-Poster.
			 */
			if ( function_exists( 'nxs_initSNAP' ) ) {

				add_action( 'wp_head', array( $this, 'cleanup_snap_og_meta_tags_holder' ), -2000 );
			}
		}

		/*
		 * Exclude the WPSSO schema markup from the Jetpack Boost "Defer Non-Essential JavaScript" function.
		 */
		public function cleanup_jetpack_boost_defer_scripts( array $script_tags ) {

			foreach ( $script_tags as $num => $match ) {

				if ( isset( $match[ 0 ] ) ) {	// Just in case.

					if ( false !== strpos( $match[ 0 ], '<script type="application/ld+json" id="wpsso-schema-' ) ) {

						unset( $script_tags[ $num ] );
					}
				}
			}

			return $script_tags;
		}

		public function cleanup_snap_og_meta_tags_holder() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Prevent SNAP from adding meta tags for the Facebook user agent.
			 */
			remove_action( 'wp_head', 'nxs_addOGTagsPreHolder', 150 );
		}
	}
}
