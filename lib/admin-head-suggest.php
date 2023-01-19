<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
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
		}
	}
}
