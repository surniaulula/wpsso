<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

/**
 * The wpsso_error_handler() function can be used for cases where errors need
 * to be captured and sent to the toolbar notification area. Example:
 *
 * $previous_error_handler = set_error_handler( 'wpsso_error_handler' );
 *
 * $image_size = getimagesize( $file_path );
 *
 * restore_error_handler();
 */
if ( ! function_exists( 'wpsso_error_handler' ) ) {

	if ( ! class_exists( 'WpssoErrorException' ) ) {		// Just in case.

		require_once WPSSO_PLUGINDIR . 'lib/exception.php';	// Extends ErrorException.
	}

	function wpsso_error_handler( $severity, $errstr, $filename, $lineno ) {

		try {

			throw new WpssoErrorException( $errstr, $errno = 0, $severity, $filename, $lineno );

		} catch ( WpssoErrorException $e ) {

			return $e->errorMessage( $ret = false );
		}
	}
}

if ( ! function_exists( 'wpsso_is_mobile' ) ) {

	function wpsso_is_mobile() {

		$wpsso =& Wpsso::get_instance();

		if ( $wpsso->avail[ 'p' ][ 'vary_ua' ] ) {

			return SucomUtil::is_mobile();
		}

		return null;
	}
}

if ( ! function_exists( 'wpsso_get_is_functions' ) ) {

	function wpsso_get_is_functions() {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->util ) ) {	// Just in case.

			return $wpsso->util->get_is_functions();
		}
	}
}

if ( ! function_exists( 'wpsso_schema_attributes' ) ) {

	function wpsso_schema_attributes( $attr = '' ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->meta_item ) ) {	// Just in case.

			echo $wpsso->meta_item->filter_head_attributes( $attr );
		}
	}
}

if ( ! function_exists( 'wpsso_show_head' ) ) {

	function wpsso_show_head( $attr = '' ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->head ) ) {	// Just in case.

			echo $wpsso->head->show_head();
		}
	}
}

/**
 * Deprecated on 2020/05/05.
 */
if ( ! function_exists( 'wpsso_clear_all_cache' ) ) {

	function wpsso_clear_all_cache( $clear_other = false ) {

		return wpsso_clear_cache( $clear_other );
	}
}

if ( ! function_exists( 'wpsso_clear_cache' ) ) {

	function wpsso_clear_cache( $clear_other = false ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->util ) ) {	// Just in case.

			$user_id = get_current_user_id();

			return $wpsso->util->cache->schedule_clear( $user_id, $clear_other );
		}
	}
}

if ( ! function_exists( 'wpsso_clear_post_cache' ) ) {

	function wpsso_clear_post_cache( $post_id ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->post ) ) {	// Just in case.

			$wpsso->post->clear_cache( $post_id );
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

		if ( ! empty( $wpsso->util ) ) {	// Just in case.

			return $wpsso->util->get_page_mod( $use_post );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_post_mod' ) ) {

	function wpsso_get_post_mod( $post_id ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->post ) ) {	// Just in case.

			return $wpsso->post->get_mod( $post_id );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_term_mod' ) ) {

	function wpsso_get_term_mod( $term_id ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->term ) ) {	// Just in case.

			return $wpsso->term->get_mod( $term_id );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_user_mod' ) ) {

	function wpsso_get_user_mod( $user_id ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->user ) ) {	// Just in case.

			return $wpsso->user->get_mod( $user_id );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_mod_og_image' ) ) {

	function wpsso_get_mod_og_image( array $mod, $size_name = 'thumbnail' ) {

		$wpsso =& Wpsso::get_instance();

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
 *	[og:image:url]        => http://adm.surniaulula.com/wp-content/uploads/2013/03/captain-america-150x150.jpg
 *	[og:image:width]      => 150
 *	[og:image:height]     => 150
 *	[og:image:cropped]    => 1
 *	[og:image:id]         => 1261
 *	[og:image:alt]        => Captain America
 *	[og:image:size_name]  => wpsso-schema
 * )
 *
 * An image URL may be located in the :secure_url or :url meta tags (or both).
 *
 * An easy way to get the image URL would be to use the get_first_mt_media_url() method. Example:
 *
 * if ( $og_image = wpsso_get_post_og_image( $post_id ) ) {	// Returns false or array.
 *
 * 	$image_url = SucomUtil::get_first_mt_media_url( $og_image );	// Returns a string.
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

if ( ! function_exists( 'wpsso_get_canonical_url' ) ) {

	function wpsso_get_canonical_url( $mod = false, $add_page = true ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->util ) ) {	// Just in case.

			return $wpsso->util->get_canonical_url( $mod, $add_page );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_sharing_url' ) ) {

	function wpsso_get_sharing_url( $mod = false, $add_page = true ) {

		$wpsso =& Wpsso::get_instance();

		if ( ! empty( $wpsso->util ) ) {	// Just in case.

			return $wpsso->util->get_sharing_url( $mod, $add_page );
		}

		return false;
	}
}

if ( ! function_exists( 'wpsso_get_short_url' ) ) {

	function wpsso_get_short_url( $mod = false, $add_page = true ) {

		$wpsso =& Wpsso::get_instance();

		$sharing_url = $wpsso->util->get_sharing_url( $mod, $add_page );

		$service_id = $wpsso->options[ 'plugin_shortener' ];

		return apply_filters( 'wpsso_get_short_url', $sharing_url, $service_id, $mod );
	}
}

if ( ! function_exists( 'wpsso_get_post_event_options' ) ) {

	function wpsso_get_post_event_options( $post_id, $type_id = false ) {

		return WpssoSchema::get_post_type_options( $post_id, 'event', $type_id );
	}
}

if ( ! function_exists( 'wpsso_get_post_job_options' ) ) {

	function wpsso_get_post_job_options( $post_id, $type_id = false ) {

		return WpssoSchema::get_post_type_options( $post_id, 'job', $type_id );
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
		} elseif ( ! empty( $wpsso->post ) ) {	// Just in case.

			$mod = $wpsso->post->get_mod( $post_id );

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

		} elseif ( ! empty( $wpsso->post ) ) {	// Just in case.

			$mod = $wpsso->post->get_mod( $post_id );

		} else {

			return false;
		}

		return apply_filters( $wpsso->lca . '_get_place_options', false, $mod, $type_id );
	}
}
