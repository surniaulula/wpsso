<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

/**
 * Deprecated on 2019/08/04.
 * Maintain for WPSSO JSON add-on pre-v2.6.0.
 */
if ( ! class_exists( 'WpssoSchemaCache' ) ) {

	class WpssoSchemaCache {

		public function __construct() {
		}

		public static function get_mod_json_data( array $mod ) {

			$wpsso =& Wpsso::get_instance();

			return $wpsso->schema->get_mod_json_data( $mod );
		}
	}
}
