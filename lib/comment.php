<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

/**
 * This class may be extended by some add-ons.
 */
if ( ! class_exists( 'WpssoAbstractWpMeta' ) ) {

	$dir_name = dirname( __FILE__ );

	if ( file_exists( $dir_name . '/abstract/wp-meta.php' ) ) {

		require_once $dir_name . '/abstract/wp-meta.php';

	} else wpdie( 'WpssoAbstractWpMeta class not found.' );
}

if ( ! class_exists( 'WpssoComment' ) ) {

	class WpssoComment extends WpssoAbstractWpMeta {

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'comment_post', array( $this, 'clear_cache_comment_post' ), PHP_INT_MAX, 2 );
			add_action( 'transition_comment_status', array( $this, 'clear_cache_transition_comment_status' ), PHP_INT_MAX, 3 );
		}

		/**
		 * Get the $mod object for a comment ID.
		 */
		public function get_mod( $comment_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			if ( isset( $local_cache[ $comment_id ] ) ) {

				return $local_cache[ $comment_id ];
			}

			$mod = self::get_mod_defaults();

			/**
			 * Common elements.
			 */
			$mod[ 'id' ]          = is_numeric( $comment_id ) ? (int) $comment_id : 0;	// Cast as integer.
			$mod[ 'name' ]        = 'comment';
			$mod[ 'name_transl' ] = _x( 'comment', 'module name', 'wpsso' );
			$mod[ 'obj' ]         =& $this;

			/**
			 * WpssoComment elements.
			 */
			$mod[ 'is_comment' ] = true;

			if ( $mod[ 'id' ] ) {	// Just in case.

				$mod[ 'wp_obj' ] = get_comment( $mod[ 'id' ] );	// Optimize and fetch once.

				if ( $mod[ 'wp_obj' ] instanceof WP_Comment ) {	// Just in case.

					$mod[ 'comment_author' ]      = (int) $mod[ 'wp_obj' ]->user_id;		// Comment author user ID.
					$mod[ 'comment_author_name' ] = $mod[ 'wp_obj' ]->comment_author;		// Comment author name.
					$mod[ 'comment_author_url' ]  = $mod[ 'wp_obj' ]->comment_author_url;
					$mod[ 'comment_parent' ]      = $mod[ 'wp_obj' ]->comment_parent;
					$mod[ 'comment_time' ]        = mysql2date( 'c', $mod[ 'wp_obj' ]->comment_date_gmt );	// ISO 8601 date.

					$comment_rating = get_comment_meta( $mod[ 'id' ], WPSSO_META_RATING_NAME, $single = true );

					if ( is_numeric( $comment_rating ) ) {
					
						$mod[ 'comment_rating' ] = $comment_rating;
					}

				} else $mod[ 'wp_obj' ] = false;
			}

			/**
			 * Hooked by the 'coauthors' pro module.
			 */
			return $local_cache[ $comment_id ] = apply_filters( 'wpsso_get_comment_mod', $mod, $comment_id );
		}

		public function get_mod_wp_object( array $mod ) {

			return get_comment( $mod[ 'id' ] );
		}

		public function clear_cache_comment_post( $comment_id, $comment_approved ) {

			if ( $comment_id && $comment_approved ) {

				$comment = get_comment( $comment_id );

				if ( ! empty( $comment->comment_post_ID ) ) {

					$this->p->post->clear_cache( $comment->comment_post_ID );
				}
			}
		}

		public function clear_cache_transition_comment_status( $new_status, $old_status, $comment ) {

			if ( 'approved' === $new_status || 'approved' === $old_status ) {

				if ( ! empty( $comment->comment_post_ID ) ) {

					$this->p->post->clear_cache( $comment->comment_post_ID );
				}
			}
		}

		/**
		 * Retrieves or updates the metadata cache by key and group.
		 */
		public function get_update_meta_cache( $comment_id ) {

			return SucomUtilWP::get_update_meta_cache( $comment_id, $meta_type = 'comment' );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function get_meta( $comment_id, $meta_key, $single = false ) {

			return get_comment_meta( $comment_id, $meta_key, $single );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function update_meta( $comment_id, $meta_key, $value ) {

			return update_comment_meta( $comment_id, $meta_key, $value );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function delete_meta( $comment_id, $meta_key ) {

			return delete_comment_meta( $comment_id, $meta_key );
		}
	}
}
