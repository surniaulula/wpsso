<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

/**
 * This class may be extended by some add-ons.
 */
if ( ! class_exists( 'WpssoWpMeta' ) ) {

	$dir_name = dirname( __FILE__ );

	if ( file_exists( $dir_name . '/abstracts/wp-meta.php' ) ) {

		require_once $dir_name . '/abstracts/wp-meta.php';

	} else wpdie( 'WpssoWpMeta class not found.' );
}

if ( ! class_exists( 'WpssoComment' ) ) {

	class WpssoComment extends WpssoWpMeta {

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
			$mod[ 'is_comment' ]    = true;

			/**
			 * Hooked by the 'coauthors' pro module.
			 */
			return $local_cache[ $comment_id ] = apply_filters( 'wpsso_get_comment_mod', $mod, $comment_id );
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
