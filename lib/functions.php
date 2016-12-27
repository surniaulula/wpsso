<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! function_exists( 'wpsso_get_sharing_url' ) ) {
	function wpsso_get_sharing_url( $mod = false, $add_page = true ) {
		$wpsso =& Wpsso::get_instance();
		return $wpsso->util->get_sharing_url( $mod, $add_page );
	}
}

if ( ! function_exists( 'wpsso_get_short_url' ) ) {
	function wpsso_get_short_url( $mod = false, $add_page = true ) {
		$wpsso =& Wpsso::get_instance();
		return apply_filters( 'wpsso_shorten_url', 
			$wpsso->util->get_sharing_url( $mod, $add_page ),
			$wpsso->options['plugin_shortener'] );
	}
}

if ( ! function_exists( 'wpsso_schema_attributes' ) ) {
	function wpsso_schema_attributes( $attr = '' ) {
		$wpsso =& Wpsso::get_instance();
		echo $wpsso->schema->filter_head_attributes( $attr );
	}
}

if ( ! function_exists( 'wpsso_clear_all_cache' ) ) {
	function wpsso_clear_all_cache( $clear_external = false ) {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->util ) )	// just in case
			return $wpsso->util->clear_all_cache( $clear_external, __FUNCTION__, true );
	}
}

if ( ! function_exists( 'wpsso_clear_post_cache' ) ) {
	function wpsso_clear_post_cache( $post_id ) {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->m['util']['post'] ) )	// just in case
			$wpsso->m['util']['post']->clear_cache( $post_id );
	}
}

if ( ! function_exists( 'wpsso_is_mobile' ) ) {
	function wpsso_is_mobile() {
		if ( class_exists( 'SucomUtil' ) )	// just in case
			return SucomUtil::is_mobile();
		else return null;
	}
}

/*
 * Define WPSSO_READ_WPSEO_META as true in your wp-config.php file to allow
 * reading of Yoast SEO post, term, and user meta - even when the Yoast SEO
 * plugin is not active.
 */
if ( defined( 'WPSSO_READ_WPSEO_META' ) && WPSSO_READ_WPSEO_META ) {

	add_filter( 'wpsso_get_post_options', 'filter_get_post_options_wpseo_meta', 10, 2 );
	add_filter( 'wpsso_get_term_options', 'filter_get_term_options_wpseo_meta', 10, 2 );
	add_filter( 'wpsso_get_user_options', 'filter_get_user_options_wpseo_meta', 10, 2 );

	if ( ! function_exists( 'filter_get_post_options_wpseo_meta' ) ) {
		function filter_get_post_options_wpseo_meta( $opts, $post_id ) {

			if ( empty( $opts['og_title'] ) )
				$opts['og_title'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_opengraph-title', true );

			if ( empty( $opts['og_title'] ) )	// fallback to the SEO title
				$opts['og_title'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_title', true );

			if ( empty( $opts['og_desc'] ) )
				$opts['og_desc'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_opengraph-description', true );

			if ( empty( $opts['og_desc'] ) )	// fallback to the SEO description
				$opts['og_desc'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_metadesc', true );

			if ( empty( $opts['og_img_id'] ) && empty( $opts['og_img_url'] ) )
				$opts['og_img_url'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_opengraph-image', true );

			if ( empty( $opts['tc_desc'] ) )
				$opts['tc_desc'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_twitter-description', true );

			if ( empty( $opts['schema_desc'] ) )
				$opts['schema_desc'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_metadesc', true );

			$opts['seo_desc'] = (string) get_post_meta( $post_id,
				'_yoast_wpseo_metadesc', true );

			return $opts;
		}
	}

	if ( ! function_exists( 'filter_get_term_options_wpseo_meta' ) ) {
		/*
		 * Yoast SEO does not support wordpress term meta (added in wp 4.4).
		 * Read term meta from the 'wpseo_taxonomy_meta' option instead.
		 */
		function filter_get_term_options_wpseo_meta( $opts, $term_id ) {

			$term_obj = get_term( $term_id );
			$tax_opts = get_option( 'wpseo_taxonomy_meta' );

			if ( ! isset( $term_obj->taxonomy ) || 
				! isset( $tax_opts[$term_obj->taxonomy][$term_id] ) )
					return $opts;

			$term_opts = $tax_opts[$term_obj->taxonomy][$term_id];

			if ( empty( $opts['og_title'] ) && 
				isset( $term_opts['wpseo_opengraph-title'] ) )
					$opts['og_title'] = (string) $term_opts['wpseo_opengraph-title'];

			if ( empty( $opts['og_title'] ) &&	// fallback to the SEO title
				isset( $term_opts['wpseo_title'] ) )
					$opts['og_title'] = (string) $term_opts['wpseo_title'];

			if ( empty( $opts['og_desc'] ) && 
				isset( $term_opts['wpseo_opengraph-description'] ) )
					$opts['og_desc'] = (string) $term_opts['wpseo_opengraph-description'];

			if ( empty( $opts['og_desc'] ) &&	// fallback to the SEO description
				isset( $term_opts['wpseo_desc'] ) )
					$opts['og_desc'] = (string) $term_opts['wpseo_desc'];

			if ( empty( $opts['og_img_id'] ) && empty( $opts['og_img_url'] ) &&
				isset( $term_opts['wpseo_opengraph-image'] ) )
					$opts['og_img_url'] = (string) $term_opts['wpseo_opengraph-image'];

			if ( empty( $opts['tc_desc'] ) &&
				isset( $term_opts['wpseo_twitter-description'] ) )
					$opts['tc_desc'] = (string) $term_opts['wpseo_twitter-description'];

			if ( empty( $opts['schema_desc'] ) && 
				isset( $term_opts['wpseo_desc'] ) )
					$opts['tc_desc'] = (string) $term_opts['wpseo_desc'];

			if ( isset( $term_opts['wpseo_desc'] ) )
				$opts['seo_desc'] = (string) $term_opts['wpseo_desc'];

			return $opts;
		}
	}

	if ( ! function_exists( 'filter_get_user_options_wpseo_meta' ) ) {
		/*
		 * Yoast SEO does not provide social settings for users.
		 */
		function filter_get_user_options_wpseo_meta( $opts, $user_id ) {

			if ( empty( $opts['og_title'] ) )
				$opts['og_title'] = (string) get_user_meta( $user_id,
					'wpseo_title', true );

			if ( empty( $opts['og_desc'] ) )
				$opts['og_desc'] = (string) get_user_meta( $user_id,
					'wpseo_metadesc', true );

			return $opts;
		}
	}
}

?>
