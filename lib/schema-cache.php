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
 */
if ( ! class_exists( 'WpssoSchemaCache' ) ) {

	class WpssoSchemaCache {

		public function __construct( &$plugin ) {
		}

		public static function get_single( array $mod, $mt_og, $page_type_id ) {
			return array();
		}

		public static function get_mod_json_data( array $mod, $mt_og, $page_type_id ) {
			return array();
		}

		public static function get_mod_index( $mixed, $page_type_id ) {
			return false;
		}

		public static function get_mod_data( $mod, $cache_index ) {
			return false;
		}

		public static function save_mod_data( $mod, $cache_data ) {
			return false;
		}

		public static function delete_mod_data( $mod ) {
			return false;
		}
	}
}
