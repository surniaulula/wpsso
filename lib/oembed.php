<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoOembed' ) ) {

	class WpssoOembed {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}


			/**
			 * TODO:
			 *
			 * Hook 'oembed_response_data' and replace get_oembed_response_data_rich() to provide custom image.
			 *
			 * Hook 'oembed_iframe_title_attribute' filter and return custom title.
			 */
		}
	}
}
