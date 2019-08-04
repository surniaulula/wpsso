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

		/**
		 * Maintain for WPSSO JSON add-on pre-v2.6.0.
		 */
		public static function get_mod_json_data( array $post_mod ) {

			if ( ! is_object( $post_mod[ 'obj' ] ) || ! $post_mod[ 'id' ] ) {
				return false;
			}

			$wpsso =& Wpsso::get_instance();

			$post_type_id     = $wpsso->schema->get_mod_schema_type( $post_mod, $get_schema_id = true );
			$post_sharing_url = $wpsso->util->maybe_set_ref( null, $post_mod, __( 'adding schema', 'wpsso' ) );
			$post_mt_og       = $wpsso->og->get_array( $post_mod, array() );
			$post_json_data   = $wpsso->schema->get_json_data( $post_mod, $post_mt_og, $post_type_id, $post_is_main = true );

			$wpsso->util->maybe_unset_ref( $post_sharing_url );

			return $post_json_data;
		}
	}
}
