<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2022 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomUtilRobots' ) ) {

	class SucomUtilRobots {

		private static $default_directives = array(
			'follow'            => true,	// Follow by default.
			'index'             => true,	// Index by default.
			'noarchive'         => false,
			'nofollow'          => false,	// Do not follow links on this webpage.
			'noimageindex'      => false,	// Do not index images on this webpage.
			'noindex'           => false,	// Do not show this webpage in search results.
			'nosnippet'         => false,	// Do not show a text snippet or a video preview in search results.
			'notranslate'       => false,
			'max-snippet'       => -1,	// Max characters for textual snippet (-1 = no limit).
			'max-image-preview' => 'large',	// Max size for image preview.
			'max-video-preview' => -1,	// Max seconds for video snippet (-1 = no limit).
		);

		private static $inverse_directives = array(
			'nofollow'     => array( 'follow' ),				// Do not follow links on this webpage.
			'noimageindex' => array( 'max-image-preview' ),			// Do not index images on this webpage.
			'noindex'      => array( 'index' ),				// Do not show this webpage in search results.
			'nosnippet'    => array( 'max-snippet', 'max-video-preview' ),	// Do not show a text snippet or a video preview in search results.
		);

		/*
		 * See https://developers.google.com/search/reference/robots_meta_tag.
		 */
		public static function get_default_directives() {

			$directives = self::$default_directives;
			$is_public  = get_option( 'blog_public' );

			/*
			 * If the site is not public, discourage robots from indexing the site.
			 */
			if ( ! $is_public ) {

				$directives[ 'follow' ]       = false;	// No follow.
				$directives[ 'index' ]        = false;	// No index.
				$directives[ 'noarchive' ]    = true;
				$directives[ 'nofollow' ]     = true;
				$directives[ 'noimageindex' ] = true;
				$directives[ 'noindex' ]      = true;
				$directives[ 'nosnippet' ]    = true;
				$directives[ 'notranslate' ]  = true;

			/*
			 * The webpage should not be indexed, but allow robots to follow links.
			 */
			} elseif ( isset( $_GET[ 'replytocom' ] ) || is_embed() || is_404() || is_search() ) {

				$directives[ 'index' ]     = false;	// No index.
				$directives[ 'noarchive' ] = true;
				$directives[ 'noindex' ]   = true;
				$directives[ 'nosnippet' ] = true;
			}

			return apply_filters( 'sucom_robots_default_directives', $directives );
		}

		/*
		 * Properly set boolean directives and their inverse boolean directives.
		 */
		public static function set_directive( $key, $value, array &$directives ) {

			if ( isset( self::$default_directives[ $key ] ) ) {	// Directive must be known.

				if ( is_bool( self::$default_directives[ $key ] ) ) {	// Default boolean, so set as boolean.

					$directives[ $key ] = $value ? true : false;	// Convert to boolean.

					/*
					 * Check for inverse directives.
					 */
					if ( isset( self::$inverse_directives[ $key ] ) ) {

						foreach ( self::$inverse_directives[ $key ] as $inverse_key ) {

							/*
							 * If the inverse is also a boolean, then set the inverse boolean value.
							 */
							if ( isset( self::$default_directives[ $inverse_key ] ) ) {	// Directive must be known.

								if ( is_bool( self::$default_directives[ $inverse_key ] ) ) {	// Also a boolean.

									$directives[ $inverse_key ] = $value ? false : true;	// Inverse boolean.
								}
							}
						}
					}

				} else {

					$directives[ $key ] = $value;
				}
			}
		}

		/*
		 * Sanity check - make sure inverse directives are removed.
		 */
		public static function sanitize_directives( array &$directives ) {

			foreach ( self::$inverse_directives as $key => $inverse_keys ) {

				if ( ! empty( $directives[ $key ] ) ) {	// $key exists and is true.

					foreach ( $inverse_keys as $inverse_key ) {	// Unset each inverse directive.

						if ( isset( $directives[ $inverse_key ] ) ) {	// Just in case.

							unset( $directives[ $inverse_key ] );
						}
					}
				}
			}
		}
	}
}
