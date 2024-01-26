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

		/*
		 * Instantiated by WpssoAdminHead->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'admin_head', array( $this, 'suggest' ), 100, 0 );
		}

		public function suggest( $suggest_max = 2 ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'suggest_max' => $suggest_max,
				) );
			}

			$suggested = 0;

			/*
			 * Suggest options, addons, and attributes, in that order.
			 */
			foreach ( array( 'options', 'addons', 'attributes' ) as $lib ) {

				require_once WPSSO_PLUGINDIR . 'lib/admin-head-suggest-' . $lib . '.php';

				$classname   = 'wpssoadminheadsuggest' . $lib;
				$suggest_obj = new $classname( $this->p );
				$suggested   = $suggested + $suggest_obj->suggest( $suggest_max - $suggested );

				if ( $suggested >= $suggest_max ) break;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'return suggested = ' . $suggested );
			}

			return $suggested;
		}
	}
}
