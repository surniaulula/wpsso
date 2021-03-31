<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoWpMeta' ) ) {

	require_once dirname( __FILE__ ) . '/abstracts/wp-meta.php';	// SucomAddOn class.
}

if ( ! class_exists( 'WpssoComment' ) ) {

	class WpssoComment extends WpssoWpMeta {

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->options[ 'plugin_clear_for_comment' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding clear cache for comment actions' );
				}

				/**
				 * Fires when a comment is inserted into the database.
				 */
				add_action ( 'comment_post', array( $this, 'clear_cache_for_new_comment' ), 1000, 2 );

				/**
				 * Fires before transitioning a comment's status.
				 */
				add_action ( 'wp_set_comment_status', array( $this, 'clear_cache_for_comment_status' ), 1000, 2 );
			}
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

		public function clear_cache_for_comment_status( $comment_id, $comment_status ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $comment_id ) {	// Just in case.

				if ( ( $comment = get_comment( $comment_id ) ) && $comment->comment_post_ID ) {

					$post_id = $comment->comment_post_ID;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'clearing post_id ' . $post_id . ' cache for comment_id ' . $comment_id );
					}

					$this->p->post->clear_cache( $post_id );
				}
			}
		}

		public function clear_cache_for_new_comment( $comment_id, $comment_approved ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $comment_id && $comment_approved === 1 ) {

				if ( ( $comment = get_comment( $comment_id ) ) && $comment->comment_post_ID ) {

					$post_id = $comment->comment_post_ID;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'clearing post_id ' . $post_id . ' cache for comment_id ' . $comment_id );
					}

					$this->p->post->clear_cache( $post_id );
				}
			}
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
