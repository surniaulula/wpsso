<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

/**
 * The wpsso_error_handler() function can be used for cases where errors need
 * to be captured and sent to the toolbar notification area. Example:
 *
 * $previous_error_handler = set_error_handler( 'wpsso_error_handler' );
 *
 * $image_size = getimagesize( $filepath );
 *
 * restore_error_handler();
 */
if ( ! function_exists( 'wpsso_error_handler' ) ) {

	if ( ! class_exists( 'WpssoErrorException' ) ) {		// Just in case.
		require_once WPSSO_PLUGINDIR . 'lib/exception.php';	// Extends ErrorException.
	}

	function wpsso_error_handler( $severity, $errstr, $filename, $lineno, array $errcontext ) {
		try {
			throw new WpssoErrorException( $errstr, $errno = 0, $severity, $filename, $lineno );
		} catch ( WpssoErrorException $e ) {
			return $e->errorMessage();
		}
	}
}

if ( ! function_exists( 'wpsso_is_mobile' ) ) {

	function wpsso_is_mobile() {

		if ( SucomUtil::get_const( 'WPSSO_VARY_USER_AGENT_DISABLE' ) ) {
			return null;
		}

		return SucomUtil::is_mobile();
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

	function wpsso_clear_all_cache( $clear_other = false ) {

		$wpsso =& Wpsso::get_instance();

		if ( is_object( $wpsso->util ) ) {

			$user_id = get_current_user_id();

			return $wpsso->util->schedule_clear_all_cache( $user_id, $clear_other );
		}
	}
}

if ( ! function_exists( 'wpsso_clear_post_cache' ) ) {

	function wpsso_clear_post_cache( $post_id ) {

		$wpsso =& Wpsso::get_instance();

		if ( isset( $wpsso->m[ 'util' ][ 'post' ] ) ) {	// Just in case.
			$wpsso->m[ 'util' ][ 'post' ]->clear_cache( $post_id );
		}
	}
}

/**
 * Get the $mod array for the current webpage. If $use_post is true, then the
 * requested object is assumed to be a post, and the global $post object will
 * be used to determine the post ID.
 */
if ( ! function_exists( 'wpsso_get_page_mod' ) ) {

	function wpsso_get_page_mod( $use_post = false ) {

		$wpsso =& Wpsso::get_instance();

		if ( is_object( $wpsso->util ) ) {
			return $wpsso->util->get_page_mod( $use_post );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_post_mod' ) ) {

	function wpsso_get_post_mod( $post_id ) {

		$wpsso =& Wpsso::get_instance();

		if ( isset( $wpsso->m[ 'util' ][ 'post' ] ) ) {	// Just in case.
			return $wpsso->m[ 'util' ][ 'post' ]->get_mod( $post_id );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_term_mod' ) ) {

	function wpsso_get_term_mod( $term_id ) {

		$wpsso =& Wpsso::get_instance();

		if ( isset( $wpsso->m[ 'util' ][ 'term' ] ) ) {	// Just in case.
			return $wpsso->m[ 'util' ][ 'term' ]->get_mod( $term_id );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_user_mod' ) ) {

	function wpsso_get_user_mod( $user_id ) {

		$wpsso =& Wpsso::get_instance();

		if ( isset( $wpsso->m[ 'util' ][ 'user' ] ) ) {	// Just in case.
			return $wpsso->m[ 'util' ][ 'user' ]->get_mod( $user_id );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_mod_og_image' ) ) {

	function wpsso_get_mod_og_image( array $mod, $size_name = 'thumbnail' ) {

		$wpsso =& Wpsso::get_instance();

		$og_image = array();
		$og_video = $wpsso->og->get_all_videos( $num = 1, $mod, $check_dupes = false, $md_pre = 'og' );

		/**
		 * If there are videos, check for a preview image, and return the first one.
		 */
		if ( is_array( $og_video ) ) {	// Just in case.

			foreach ( $og_video as $num => $og_single_video ) {

				if ( SucomUtil::get_mt_media_url( $og_single_video, $mt_media_pre = 'og:image' ) ) {
					return SucomUtil::preg_grep_keys( '/^og:image/', $og_single_video );	// Return one dimension array.
				}
			}
		}

		$og_image = $wpsso->og->get_all_images( $num = 1, $size_name, $mod, $check_dupes = false, $md_pre = 'og' );

		if ( ! empty( $og_image[ 0 ] ) ) {
			return $og_image[ 0 ];	// Return one dimension array.
		}

		return false;
	}
}

/**
 * Returns a single dimension array or false on error. Example:
 *
 * Array (
 * 	[og:image:secure_url] =>
 *	[og:image:url] => http://adm.surniaulula.com/wp-content/uploads/2013/03/captain-america-150x150.jpg
 *	[og:image:width] => 150
 *	[og:image:height] => 150
 *	[og:image:cropped] => 1
 *	[og:image:id] => 1261
 *	[og:image:alt] => Captain America
 * )
 *
 * An image URL may be located in the :secure_url or :url meta tags (or both).
 * An easy way to get the image URL would be to use the get_mt_media_url()
 * method. Example:
 *
 * if ( $og_image = wpsso_get_post_og_image( $post_id ) ) {	// Returns false or array.
 * 	$image_url = SucomUtil::get_mt_media_url( $og_image );	// Returns a string.
 * }
 */
if ( ! function_exists( 'wpsso_get_post_og_image' ) ) {

	function wpsso_get_post_og_image( $post_id, $size_name = 'thumbnail' ) {

		if ( $mod = wpsso_get_post_mod( $post_id ) ) {
			return wpsso_get_mod_og_image( $mod, $size_name );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_term_og_image' ) ) {

	function wpsso_get_term_og_image( $term_id, $size_name = 'thumbnail' ) {

		if ( $mod = wpsso_get_term_mod( $term_id ) ) {
			return wpsso_get_mod_og_image( $mod, $size_name );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_user_og_image' ) ) {

	function wpsso_get_user_og_image( $user_id, $size_name = 'thumbnail' ) {

		if ( $mod = wpsso_get_user_mod( $user_id ) ) {
			return wpsso_get_mod_og_image( $mod, $size_name );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_sharing_url' ) ) {

	function wpsso_get_sharing_url( $mod = false, $add_page = true ) {

		$wpsso =& Wpsso::get_instance();

		if ( is_object( $wpsso->util ) ) {
			return $wpsso->util->get_sharing_url( $mod, $add_page );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_short_url' ) ) {

	function wpsso_get_short_url( $mod = false, $add_page = true ) {

		$wpsso =& Wpsso::get_instance();

		$sharing_url = $wpsso->util->get_sharing_url( $mod, $add_page );
		$service_id  = $wpsso->options['plugin_shortener'];

		return apply_filters( 'wpsso_get_short_url', $sharing_url, $service_id, $mod );
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

		/**
		 * Check that the id value is not true, false, null, or 'none'.
		 */
		if ( ! SucomUtil::is_valid_option_id( $type_id ) ) {
			return array();
		}

		$wpsso =& Wpsso::get_instance();

		if ( empty( $post_id ) ) {	// Just in case.
			return false;
		} elseif ( isset( $wpsso->m[ 'util' ][ 'post' ] ) ) {	// Just in case.
			$mod = $wpsso->m[ 'util' ][ 'post' ]->get_mod( $post_id );
		} else {
			return false;
		}

		$org_opts = apply_filters( $wpsso->lca . '_get_organization_options', false, $mod, $type_id );

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
		} elseif ( isset( $wpsso->m[ 'util' ][ 'post' ] ) ) {	// Just in case.
			$mod = $wpsso->m[ 'util' ][ 'post' ]->get_mod( $post_id );
		} else {
			return false;
		}

		return apply_filters( $wpsso->lca . '_get_place_options', false, $mod, $type_id );
	}
}
