<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! function_exists( 'wpsso_get_sharing_url' ) ) {
	function wpsso_get_sharing_url( $use_post = false, $add_page = true ) {
		$wpsso =& Wpsso::get_instance();
		return $wpsso->util->get_sharing_url( $use_post, $add_page );
	}
}

if ( ! function_exists( 'wpsso_get_short_url' ) ) {
	function wpsso_get_short_url( $use_post = false, $add_page = true ) {
		$wpsso =& Wpsso::get_instance();
		return apply_filters( 'wpsso_shorten_url', 
			$wpsso->util->get_sharing_url( $use_post, $add_page ),
			$wpsso->options['plugin_shortener'] );
	}
}

if ( ! function_exists( 'wpsso_schema_attributes' ) ) {
	function wpsso_schema_attributes( $attr = '' ) {
		$wpsso =& Wpsso::get_instance();
		echo $wpsso->schema->add_head_attributes( $attr );
	}
}

if ( ! function_exists( 'wpsso_clear_all_cache' ) ) {
	function wpsso_clear_all_cache() {
		$wpsso =& Wpsso::get_instance();
		return $wpsso->util->clear_all_cache( false );	// $ext_cache = false
	}
}

if ( ! function_exists( 'wpsso_clear_post_cache' ) ) {
	function wpsso_clear_post_cache( $post_id ) {
		$wpsso =& Wpsso::get_instance();
		return $wpsso->util->clear_post_cache( $post_id );
	}
}

if ( ! function_exists( 'wpsso_is_mobile' ) ) {
	function wpsso_is_mobile() {
		if ( class_exists( 'SucomUtil' ) )	// just in case
			return SucomUtil::is_mobile();
		else return null;
	}
}

?>
