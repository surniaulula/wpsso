<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoAdminHeadSuggestAttributes' ) ) {

	class WpssoAdminHeadSuggestAttributes {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by WpssoAdminHeadSuggest->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function suggest() {

			if ( $suggested = $this->suggest_attributes_woocommerce() ) return $suggested;
		}

		private function suggest_attributes_woocommerce() {

			$suggested  = 0;
			$notice_key = 'notice-wc-attributes-available';

			if ( empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) return $suggested;	// WooCommerce plugin is not active.

			if ( ! $this->p->notice->is_admin_pre_notices( $notice_key ) ) return $suggested;

			$notice_msg = $this->p->msgs->get( $notice_key );

			$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );

			return ++$suggested;
		}
	}
}
