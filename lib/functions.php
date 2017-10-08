<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! function_exists( 'wpsso_get_page_mod' ) ) {
	function wpsso_get_page_mod( $use_post = false ) {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->util ) ) {
			return $wpsso->util->get_page_mod( $use_post );
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsso_get_post_mod' ) ) {
	function wpsso_get_post_mod( $post_id ) {
		$wpsso =& Wpsso::get_instance();
		if ( isset( $wpsso->m['util']['post'] ) ) {
			return $wpsso->m['util']['post']->get_mod( $post_id );
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsso_get_term_mod' ) ) {
	function wpsso_get_term_mod( $term_id ) {
		$wpsso =& Wpsso::get_instance();
		if ( isset( $wpsso->m['util']['term'] ) ) {
			return $wpsso->m['util']['term']->get_mod( $term_id );
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsso_get_user_mod' ) ) {
	function wpsso_get_user_mod( $user_id ) {
		$wpsso =& Wpsso::get_instance();
		if ( isset( $wpsso->m['util']['user'] ) ) {
			return $wpsso->m['util']['user']->get_mod( $user_id );
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsso_get_sharing_url' ) ) {
	function wpsso_get_sharing_url( $mod = false, $add_page = true ) {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->util ) ) {
			return $wpsso->util->get_sharing_url( $mod, $add_page );
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsso_get_short_url' ) ) {
	function wpsso_get_short_url( $mod = false, $add_page = true ) {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->util ) ) {
			$sharing_url = $wpsso->util->get_sharing_url( $mod, $add_page );
			return apply_filters( 'wpsso_get_short_url', $sharing_url, $wpsso->options['plugin_shortener'] );
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'wpsso_schema_attributes' ) ) {
	function wpsso_schema_attributes( $attr = '' ) {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->schema ) ) {
			echo $wpsso->schema->filter_head_attributes( $attr );
		}
	}
}

if ( ! function_exists( 'wpsso_clear_all_cache' ) ) {
	function wpsso_clear_all_cache( $clear_ext = false ) {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->util ) ) {
			$wpsso->util->clear_all_cache( $clear_ext, __FUNCTION__.'_function', true );
		}
	}
}

if ( ! function_exists( 'wpsso_clear_post_cache' ) ) {
	function wpsso_clear_post_cache( $post_id ) {
		$wpsso =& Wpsso::get_instance();
		if ( isset( $wpsso->m['util']['post'] ) ) {	// just in case
			$wpsso->m['util']['post']->clear_cache( $post_id );
		}
	}
}

if ( ! function_exists( 'wpsso_is_mobile' ) ) {
	function wpsso_is_mobile() {
		// return null if the content is not allowed to vary
		// make sure the class exists in case we're called before the library is loaded 
		if ( ! SucomUtil::get_const( 'WPSSO_VARY_USER_AGENT_DISABLE' ) && class_exists( 'SucomUtil' ) ) {
			return SucomUtil::is_mobile();
		} else {
			return null;
		}
	}
}

?>
