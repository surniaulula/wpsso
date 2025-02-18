<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2024-2025 Jean-Sebastien Morisset (https://wpsso.com/)
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
		}

		public function suggest( $suggest_max = 2 ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'suggest_max' => $suggest_max,
				) );
			}

			$suggested = 0;

			/*
			 * Suggest woocommerce attributes, in that order.
			 */
			foreach ( array( 'woocommerce' ) as $suffix ) {

				$methodname = 'suggest_attributes_' . $suffix;
				$suggested  = $suggested + $this->$methodname( $suggest_max - $suggested );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $methodname . ' suggested = ' . $suggested );
				}

				if ( $suggested >= $suggest_max ) break;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'return suggested = ' . $suggested );
			}

			return $suggested;
		}

		private function suggest_attributes_woocommerce( $suggest_max ) {

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
