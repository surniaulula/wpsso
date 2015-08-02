<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! function_exists( 'wpsso_get_sharing_url' ) ) {
	function wpsso_get_sharing_url( $use_post = false, $add_page = true, $source_id = false ) {
		$wpsso =& Wpsso::get_instance();
		return $wpsso->util->get_sharing_url( $post_id, $add_page, $source_id );
	}
}

if ( ! function_exists( 'wpsso_get_short_url' ) ) {
	function wpsso_get_short_url( $use_post = false, $add_page = true, $source_id = false ) {
		$wpsso =& Wpsso::get_instance();
		return apply_filters( 'wpsso_shorten_url', 
			$wpsso->util->get_sharing_url( $post_id, $add_page, $source_id ),
			$wpsso->options['plugin_shortener'] );
	}
}

?>
