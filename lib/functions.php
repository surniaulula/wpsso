<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

/*
 * Additional generic return functions.
 *
 * WordPress already defines:
 *
 *	__return_empty_array()
 *	__return_empty_string()
 *	__return_false()
 *	__return_null()
 *	__return_true()
 *	__return_zero()
 */
if ( ! function_exists( '__return_hour_in_seconds' ) ) {

	function __return_hour_in_seconds() {

		return HOUR_IN_SECONDS;
	}
}

if ( ! function_exists( '__return_day_in_seconds' ) ) {

	function __return_day_in_seconds() {

		return DAY_IN_SECONDS;
	}
}

if ( ! function_exists( '__return_week_in_seconds' ) ) {

	function __return_week_in_seconds() {

		return WEEK_IN_SECONDS;
	}
}

if ( ! function_exists( '__return_month_in_seconds' ) ) {

	function __return_month_in_seconds() {

		return MONTH_IN_SECONDS;
	}
}

if ( ! function_exists( '__return_year_in_seconds' ) ) {

	function __return_year_in_seconds() {

		return YEAR_IN_SECONDS;
	}
}

/*
 * wpsso_error_handler() can be used when PHP errors need to be captured and sent to the toolbar notification area.
 *
 * Example:
 *
 *	$previous_error_handler = set_error_handler( 'wpsso_error_handler' );
 *
 *	$image_size = getimagesize( $file_path );
 *
 *	restore_error_handler();
 */
if ( ! function_exists( 'wpsso_error_handler' ) ) {

	if ( ! class_exists( 'WpssoErrorException' ) ) {		// Just in case.

		require_once WPSSO_PLUGINDIR . 'lib/exception.php';	// Extends ErrorException.
	}

	function wpsso_error_handler( $severity, $errstr, $filename, $lineno ) {

		try {

			throw new WpssoErrorException( $errstr, $errcode = null, $severity, $filename, $lineno );

		} catch ( WpssoErrorException $e ) {

			return $e->errorMessage( $ret = false );
		}
	}
}

/*
 * Get the canonical URL.
 */
if ( ! function_exists( 'wpsso_get_canonical_url' ) ) {

	function wpsso_get_canonical_url( $mod = false, $add_page = true ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->util->get_canonical_url( $mod, $add_page );
	}
}

/*
 * Get the shortened canonical URL.
 */
if ( ! function_exists( 'wpsso_get_canonical_short_url' ) ) {

	function wpsso_get_canonical_short_url( $mod = false, $add_page = true ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->util->get_canonical_short_url( $mod, $add_page );
	}
}

/*
 * Get the $mod array for a given comment ID.
 */
if ( ! function_exists( 'wpsso_get_comment_mod' ) ) {

	function wpsso_get_comment_mod( $comment_id ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->comment->get_mod( $comment_id );
	}
}

/*
 * Get an array for the results of test functions.
 */
if ( ! function_exists( 'wpsso_get_is_functions' ) ) {

	function wpsso_get_is_functions() {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->util->get_is_functions();
	}
}

/*
 * Get a single dimension array of image meta tags, or false if no image is found.
 *
 * Example:
 *
 *	Array (
 *		[og:image:secure_url] =>
 *		[og:image:url]        => http://adm.surniaulula.com/wp-content/uploads/2013/03/captain-america-1200x628.jpg
 *		[og:image:width]      => 1200
 *		[og:image:height]     => 628
 *		[og:image:cropped]    => 1
 *		[og:image:id]         => 1261
 *		[og:image:alt]        => Captain America
 *		[og:image:size_name]  => wpsso-opengraph
 *	)
 *
 * You can use the SucomUtil::get_first_og_image_url() method to get an image URL from the returned array.
 */
if ( ! function_exists( 'wpsso_get_mod_og_image' ) ) {

	function wpsso_get_mod_og_image( array $mod, $size_name = 'thumbnail' ) {

		$wpsso =& Wpsso::get_instance();

		$og_image = $wpsso->media->get_all_images( $num = 1, $size_name, $mod, $md_pre = 'og' );

		if ( empty( $og_image[ 0 ] ) ) {	// Just in case.

			return false;
		}

		return $og_image[ 0 ];	// Return a single dimension array.
	}
}

/*
 * Get an image URL, or an empty string.
 */
if ( ! function_exists( 'wpsso_get_mod_og_image_url' ) ) {

	function wpsso_get_mod_og_image_url( array $mod, $size_name = 'thumbnail' ) {

		$mt_single_image = wpsso_get_mod_og_image( $mod, $size_name );

		return SucomUtil::get_first_og_image_url( $mt_single_image );
	}
}

/*
 * Get the $mod array for the current webpage. If $use_post = true, then the requested object is assumed to be a post and the
 * global $post object will be used to determine the post ID.
 *
 * The use of 'page' here refers to a webpage, not the WordPress Page post type.
 */
if ( ! function_exists( 'wpsso_get_page_mod' ) ) {

	function wpsso_get_page_mod( $use_post = false ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->page->get_mod( $use_post );
	}
}

/*
 * Get the shortened post canonical URL.
 */
if ( ! function_exists( 'wpsso_get_post_canonical_short_url' ) ) {

	function wpsso_get_post_canonical_short_url( $post_id ) {

		$mod = wpsso_get_post_mod( $post_id );

		return wpsso_get_canonical_short_url( $mod, $add_page = false );
	}
}

/*
 * Get third-party and custom post options for a given event ID.
 *
 * See WpssoIntegEventTheEventsCalendar->filter_get_event_options().
 */
if ( ! function_exists( 'wpsso_get_post_event_options' ) ) {

	function wpsso_get_post_event_options( $post_id, $event_id = false ) {

		return WpssoSchema::get_post_type_options( $post_id, $type = 'event', $event_id );
	}
}

/*
 * Get third-party and custom post options for a given job ID.
 *
 * See WpssoIntegJobSimpleJobBoard->filter_get_job_options().
 * See WpssoIntegJobWpJobManager->filter_get_job_options().
 */
if ( ! function_exists( 'wpsso_get_post_job_options' ) ) {

	function wpsso_get_post_job_options( $post_id, $job_id = false ) {

		return WpssoSchema::get_post_type_options( $post_id, $type = 'job', $job_id );
	}
}

/*
 * Get the $mod array for a given post ID.
 */
if ( ! function_exists( 'wpsso_get_post_mod' ) ) {

	function wpsso_get_post_mod( $post_id ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->post->get_mod( $post_id );
	}
}

/*
 * Get a single dimension array of image meta tags, or false if no image is found.
 */
if ( ! function_exists( 'wpsso_get_post_og_image' ) ) {

	function wpsso_get_post_og_image( $post_id, $size_name = 'thumbnail' ) {

		$mod = wpsso_get_post_mod( $post_id );

		return wpsso_get_mod_og_image( $mod, $size_name );
	}
}

/*
 * Get an image URL or an empty string.
 */
if ( ! function_exists( 'wpsso_get_post_og_image_url' ) ) {

	function wpsso_get_post_og_image_url( $post_id, $size_name = 'thumbnail' ) {

		$mod = wpsso_get_post_mod( $post_id );

		return wpsso_get_mod_og_image_url( $mod, $size_name );
	}
}

/*
 * Get the complete filtered post options array, or the value of a single key if $md_key is provided.
 */
if ( ! function_exists( 'wpsso_get_post_options_full' ) ) {

	function wpsso_get_post_options_full( $post_id, $md_key = false ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->post->get_options( $post_id, $md_key, $filter_opts = true, $merge_defs = true );
	}
}

/*
 * Get third-party and custom post options for a given organization ID.
 *
 * See WpssoIntegJobWpJobManager->filter_get_organization_options().
 * See WpssoOpmOrgFiltersOptions->filter_get_organization_options().
 */
if ( ! function_exists( 'wpsso_get_post_organization_options' ) ) {

	function wpsso_get_post_organization_options( $post_id, $org_id = 'site' ) {

		/*
		 * Check that the option value is not true, false, null, empty string, or 'none'.
		 */
		if ( ! SucomUtil::is_valid_option_value( $org_id ) ) {

			return array();
		}

		if ( empty( $post_id ) ) {	// Just in case.

			return false;

		}

		$mod = wpsso_get_post_mod( $post_id );

		$org_opts = apply_filters( 'wpsso_get_organization_options', false, $mod, $org_id );

		if ( empty( $org_opts ) ) {

			if ( $org_id === 'site' ) {

				$org_opts = WpssoSchema::get_site_organization( $mod );
			}
		}

		return $org_opts;
	}
}

/*
 * Get third-party and custom post options for a given place ID.
 *
 * See WpssoIntegEventTheEventsCalendar->filter_get_place_options().
 * See WpssoIntegJobWpJobManager->filter_get_place_options().
 * See WpssoOpmPlaceFiltersOptions->filter_get_place_options().
 */
if ( ! function_exists( 'wpsso_get_post_place_options' ) ) {

	function wpsso_get_post_place_options( $post_id, $place_id = 'custom' ) {

		if ( empty( $post_id ) ) {	// Just in case.

			return false;
		}

		$mod = wpsso_get_post_mod( $post_id );

		return apply_filters( 'wpsso_get_place_options', false, $mod, $place_id );
	}
}

/*
 * Get a custom or default term ID, or false if a term for the $tax_slug is not found.
 */
if ( ! function_exists( 'wpsso_get_post_primary_category' ) ) {

	function wpsso_get_post_primary_category( $post_id, $tax_slug = 'category' ) {

		if ( empty( $post_id ) ) {	// Just in case.

			return false;
		}

		$wpsso =& Wpsso::get_instance();

		$mod = wpsso_get_post_mod( $post_id );

		return $wpsso->post->get_primary_term_id( $mod, $tax_slug );
	}
}

/*
 * Get the sharing URL, which may be different than the canonical URL.
 */
if ( ! function_exists( 'wpsso_get_sharing_url' ) ) {

	function wpsso_get_sharing_url( $mod = false, $add_page = true, $atts = array() ) {

		return $wpsso->util->get_sharing_url( $mod, $add_page, $atts );
	}
}

/*
 * Get the shortened sharing URL, which may be different than the shortened canonical URL.
 */
if ( ! function_exists( 'wpsso_get_sharing_short_url' ) ) {

	function wpsso_get_sharing_short_url( $mod = false, $add_page = true, $atts = array() ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->util->get_sharing_short_url( $mod, $add_page, $atts );
	}
}

/*
 * Get the $mod array for a given term ID.
 */
if ( ! function_exists( 'wpsso_get_term_mod' ) ) {

	function wpsso_get_term_mod( $term_id ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->term->get_mod( $term_id );
	}
}

/*
 * Get a single dimension array of image meta tags or false if no image is found.
 */
if ( ! function_exists( 'wpsso_get_term_og_image' ) ) {

	function wpsso_get_term_og_image( $term_id, $size_name = 'thumbnail' ) {

		$mod = wpsso_get_term_mod( $term_id );

		return wpsso_get_mod_og_image( $mod, $size_name );
	}
}

/*
 * Get an image URL or an empty string.
 */
if ( ! function_exists( 'wpsso_get_term_og_image_url' ) ) {

	function wpsso_get_term_og_image_url( $term_id, $size_name = 'thumbnail' ) {

		$mod = wpsso_get_term_mod( $term_id );

		return wpsso_get_mod_og_image_url( $mod, $size_name );
	}
}

/*
 * Get the complete filtered term options array, or the value of a single key if $md_key is provided.
 */
if ( ! function_exists( 'wpsso_get_term_options_full' ) ) {

	function wpsso_get_term_options_full( $term_id, $md_key = false ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->term->get_options( $term_id, $md_key, $filter_opts = true, $merge_defs = true );
	}
}

/*
 * Get the $mod array for a given user ID.
 */
if ( ! function_exists( 'wpsso_get_user_mod' ) ) {

	function wpsso_get_user_mod( $user_id ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->user->get_mod( $user_id );
	}
}

/*
 * Get a single dimension array of image meta tags or false if no image is found.
 */
if ( ! function_exists( 'wpsso_get_user_og_image' ) ) {

	function wpsso_get_user_og_image( $user_id, $size_name = 'thumbnail' ) {

		$mod = wpsso_get_user_mod( $user_id );

		return wpsso_get_mod_og_image( $mod, $size_name );
	}
}

/*
 * Get an image URL or an empty string.
 */
if ( ! function_exists( 'wpsso_get_user_og_image_url' ) ) {

	function wpsso_get_user_og_image_url( $user_id, $size_name = 'thumbnail' ) {

		$mod = wpsso_get_user_mod( $user_id );

		return wpsso_get_mod_og_image_url( $mod, $size_name );
	}
}

/*
 * Get the complete filtered user options array, or the value of a single key if $md_key is provided.
 */
if ( ! function_exists( 'wpsso_get_user_options_full' ) ) {

	function wpsso_get_user_options_full( $user_id, $md_key = false ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->user->get_options( $user_id, $md_key, $filter_opts = true, $merge_defs = true );
	}
}

/*
 * Refresh all cache.
 */
if ( ! function_exists( 'wpsso_refresh_cache' ) ) {

	function wpsso_refresh_cache() {

		$wpsso =& Wpsso::get_instance();

		$user_id = get_current_user_id();

		return $wpsso->util->cache->refresh( $user_id );
	}
}

/*
 * Refresh only the post cache.
 */
if ( ! function_exists( 'wpsso_refresh_post_cache' ) ) {

	function wpsso_refresh_post_cache( $post_id ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->post->refresh_cache( $post_id );
	}
}

/*
 * Shorten any URL.
 */
if ( ! function_exists( 'wpsso_shorten_url' ) ) {

	function wpsso_shorten_url( $url ) {

		$wpsso =& Wpsso::get_instance();

		return $wpsso->util->shorten_url( $url );
	}
}

/*
 * Show head meta tags and Schema markup.
 */
if ( ! function_exists( 'wpsso_show_head' ) ) {

	function wpsso_show_head( $attr = '' ) {

		$wpsso =& Wpsso::get_instance();

		$wpsso->head->show_head();
	}
}

/*
 * Deprecated on 2023/02/12.
 */
if ( ! function_exists( 'wpsso_clear_cache' ) ) {

	function wpsso_clear_cache() {

		_deprecated_function( __FUNCTION__ . '()', '2023/02/12', $replacement = 'wpsso_refresh_cache()' );	// Deprecation message.
	}
}

/*
 * Deprecated on 2023/10/19.
 */
if ( ! function_exists( 'wpsso_clear_post_cache' ) ) {

	function wpsso_clear_post_cache() {

		_deprecated_function( __FUNCTION__ . '()', '2023/10/19', $replacement = 'wpsso_refresh_post_cache()' );	// Deprecation message.
	}
}
