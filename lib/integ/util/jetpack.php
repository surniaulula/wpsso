<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegUtilJetpack' ) ) {

	class WpssoIntegUtilJetpack {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! is_admin() ) {
				
				add_filter( 'jetpack_enable_opengraph', '__return_false', 1000 );
				add_filter( 'jetpack_enable_open_graph', '__return_false', 1000 );
				add_filter( 'jetpack_disable_twitter_cards', '__return_true', 1000 );
			}
		}
	}
}
