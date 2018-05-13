<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! function_exists( 'wpsso_is_mobile' ) ) {

	function wpsso_is_mobile() {

		/**
		 * Return null if the content is not allowed to vary.
		 */
		if ( ! SucomUtil::get_const( 'WPSSO_VARY_USER_AGENT_DISABLE' ) ) {
			return SucomUtil::is_mobile();
		} else {
			return null;
		}
	}
}

if ( ! function_exists( 'wpsso_get_is_functions' ) ) {
	function wpsso_get_is_functions() {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->util ) ) {
			return $wpsso->util->get_is_functions();
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
	function wpsso_clear_all_cache( $clear_external = false ) {
		$wpsso =& Wpsso::get_instance();
		if ( is_object( $wpsso->util ) ) {
			$dismiss_key = __FUNCTION__.'-function-and-external';
			return $wpsso->util->clear_all_cache( $clear_external, null, null, $dismiss_key );
		}
		return 0;
	}
}

if ( ! function_exists( 'wpsso_clear_post_cache' ) ) {
	function wpsso_clear_post_cache( $post_id ) {
		$wpsso =& Wpsso::get_instance();
		if ( isset( $wpsso->m['util']['post'] ) ) {	// Just in case.
			$wpsso->m['util']['post']->clear_cache( $post_id );
		}
	}
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
		$sharing_url = $wpsso->util->get_sharing_url( $mod, $add_page );
		$service_key = $wpsso->options['plugin_shortener'];
		return apply_filters( 'wpsso_get_short_url', $sharing_url, $service_key, $mod, $mod['name'] );
	}
}

if ( ! function_exists( 'wpsso_get_post_event_options' ) ) {
	function wpsso_get_post_event_options( $post_id, $type_id = false ) {
		return WpssoSchema::get_post_md_type_opts( $post_id, 'event', $type_id );
	}
}

if ( ! function_exists( 'wpsso_get_post_job_options' ) ) {
	function wpsso_get_post_job_options( $post_id, $type_id = false ) {
		return WpssoSchema::get_post_md_type_opts( $post_id, 'job', $type_id );
	}
}

if ( ! function_exists( 'wpsso_get_post_organization_options' ) ) {
	function wpsso_get_post_organization_options( $post_id, $type_id = 'site' ) {
		if ( ! SucomUtil::is_opt_id( $type_id ) ) {	// allow for 0 but not false or null
			return array();
		}
		$wpsso =& Wpsso::get_instance();
		if ( empty( $post_id ) ) {	// Just in case.
			return false;
		} elseif ( isset( $wpsso->m['util']['post'] ) ) {	// Just in case.
			$mod = $wpsso->m['util']['post']->get_mod( $post_id );
		} else {
			return false;
		}
		// skip WpssoSchema::get_post_md_type_opts() as there are no "schema_organization" custom options
		$org_opts = apply_filters( $wpsso->cf['lca'].'_get_organization_options', false, $mod, $type_id );
		if ( empty( $org_opts ) ) {
			if ( $org_id === 'site' ) {
				$org_opts = WpssoSchema::get_site_organization( $mod );	// returns localized values
			}
		}
		return $org_opts;
	}
}

if ( ! function_exists( 'wpsso_get_post_place_options' ) ) {
	function wpsso_get_post_place_options( $post_id, $type_id = 'custom' ) {
		$wpsso =& Wpsso::get_instance();
		if ( empty( $post_id ) ) {	// Just in case.
			return false;
		} elseif ( isset( $wpsso->m['util']['post'] ) ) {	// Just in case.
			$mod = $wpsso->m['util']['post']->get_mod( $post_id );
		} else {
			return false;
		}
		// skip WpssoSchema::get_post_md_type_opts() as there are no "schema_place" custom options
		return apply_filters( $wpsso->cf['lca'].'_get_place_options', false, $mod, $type_id );
	}
}
