<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! function_exists( 'wpsso_get_sharing_url' ) ) {
	function wpsso_get_sharing_url( $use_post = false, $add_page = true, $src_id = false ) {
		$wpsso =& Wpsso::get_instance();
		return $wpsso->util->get_sharing_url( $post_id, $add_page, $src_id );
	}
}

if ( ! function_exists( 'wpsso_get_short_url' ) ) {
	function wpsso_get_short_url( $use_post = false, $add_page = true, $src_id = false ) {
		$wpsso =& Wpsso::get_instance();
		return apply_filters( 'wpsso_shorten_url', 
			$wpsso->util->get_sharing_url( $post_id, $add_page, $src_id ),
			$wpsso->options['plugin_shortener'] );
	}
}

if ( ! function_exists( 'wpsso_schema_attributes' ) ) {
	function wpsso_schema_attributes( $attr = '' ) {
		$wpsso =& Wpsso::get_instance();
		echo $wpsso->schema->add_head_attributes( $attr );
	}
}

?>
