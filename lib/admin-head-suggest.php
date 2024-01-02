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

if ( ! class_exists( 'WpssoAdminHeadSuggest' ) ) {

	class WpssoAdminHeadSuggest {

		private $p;	// Wpsso class object.

		protected $addons;
		protected $options;

		/*
		 * Instantiated by WpssoAdminHead->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			require_once WPSSO_PLUGINDIR . 'lib/admin-head-suggest-addons.php';

			$this->addons = new WpssoAdminHeadSuggestAddons( $plugin );

			require_once WPSSO_PLUGINDIR . 'lib/admin-head-suggest-options.php';

			$this->options = new WpssoAdminHeadSuggestOptions( $plugin );

			add_action( 'admin_head', array( $this, 'suggest_attributes' ), 100 );
		}

		public function suggest_attributes() {

			if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {	// WooCommerce plugin is active.

				$notice_key = 'notice-wc-attributes-available';

				if ( $this->p->notice->is_admin_pre_notices( $notice_key ) ) {

					if ( $notice_msg = $this->p->msgs->get( $notice_key ) ) {

						$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );
					}
				}
			}
		}
	}
}
