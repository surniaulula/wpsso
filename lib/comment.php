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

if ( ! class_exists( 'WpssoAbstractWpMeta' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/abstract/wp-meta.php';
}

if ( ! class_exists( 'WpssoComment' ) ) {

	class WpssoComment extends WpssoAbstractWpMeta {

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Called by wp_insert_comment(), which is called by wp_new_comment().
			 */
			add_action( 'wp_insert_comment', array( $this, 'refresh_cache_insert_comment' ), PHP_INT_MAX, 2 );

			/*
			 * Called by wp_transition_comment_status().
			 */
			add_action( 'transition_comment_status', array( $this, 'refresh_cache_comment_status' ), PHP_INT_MAX, 3 );
		}

		/*
		 * Get the $mod object for a comment ID.
		 */
		public function get_mod( $comment_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->caller();

				$this->p->debug->log_args( array(
					'comment_id' => $comment_id,
				) );
			}

			static $local_cache = array();

			/*
			 * Maybe return the array from the local cache.
			 */
			if ( isset( $local_cache[ $comment_id ] ) ) {

				if ( ! $this->md_cache_disabled ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: returning comment id ' . $comment_id . ' mod array from local cache' );
					}

					return $local_cache[ $comment_id ];

				} else unset( $local_cache[ $comment_id ] );
			}

			$mod = self::get_mod_defaults();

			/*
			 * Common elements.
			 */
			$mod[ 'id' ]          = is_numeric( $comment_id ) ? (int) $comment_id : 0;	// Cast as integer.
			$mod[ 'name' ]        = 'comment';
			$mod[ 'name_transl' ] = _x( 'comment', 'module name', 'wpsso' );
			$mod[ 'obj' ]         =& $this;

			/*
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
					$mod[ 'is_public' ]           = $mod[ 'wp_obj' ]->comment_approved ? true : false;

					$comment_rating = self::get_meta( $mod[ 'id' ], WPSSO_META_RATING_NAME, $single = true );

					if ( is_numeric( $comment_rating ) ) {

						$mod[ 'comment_rating' ] = $comment_rating;
					}

				} else $mod[ 'wp_obj' ] = false;
			}

			/*
			 * Filter the comment mod array.
			 */
			$mod = apply_filters( 'wpsso_get_comment_mod', $mod, $comment_id );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mod', $mod );
			}

			/*
			 * Maybe save the array to the local cache.
			 */
			if ( ! $this->md_cache_disabled ) {

				$local_cache[ $comment_id ] = $mod;
			}

			return $mod;
		}

		public function get_mod_wp_object( array $mod ) {

			return get_comment( $mod[ 'id' ] );
		}

		/*
		 * Option handling methods:
		 *
		 *	get_defaults()
		 *	get_options()
		 *	save_options()
		 *	delete_options()
		 */
		public function get_options( $comment_id, $md_key = false, $filter_opts = true, $merge_defs = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->caller();

				$this->p->debug->log_args( array(
					'comment_id'  => $comment_id,
					'md_key'      => $md_key,
					'filter_opts' => $filter_opts,
					'merge_defs'  => $merge_defs,
				) );
			}

			static $local_cache = array();

			/*
			 * Use $comment_id and $filter_opts to create the cache ID string, but do not add $merge_defs.
			 */
			$cache_id = SucomUtil::get_assoc_salt( array( 'id' => $comment_id, 'filter' => $filter_opts ) );

			/*
			 * Maybe initialize a new local cache element. Use isset() instead of empty() to allow for an empty array.
			 */
			if ( ! isset( $local_cache[ $cache_id ] ) ) {

				$local_cache[ $cache_id ] = null;
			}

			$md_opts =& $local_cache[ $cache_id ];	// Reference the local cache element.

			if ( null === $md_opts ) {	// Maybe read metadata into a new local cache element.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting metadata for comment id ' . $comment_id );
				}

				$md_opts = self::get_meta( $comment_id, WPSSO_META_NAME, $single = true );

				if ( ! is_array( $md_opts ) ) {

					$md_opts = array();	// WPSSO_META_NAME not found.
				}

				unset( $md_opts[ 'opt_filtered' ] );	// Just in case.

				/*
				 * Check if options need to be upgraded and saved.
				 */
				if ( $this->p->opt->is_upgrade_required( $md_opts ) ) {

					$md_opts = $this->upgrade_options( $md_opts, $comment_id );

					self::update_meta( $comment_id, WPSSO_META_NAME, $md_opts );
				}
			}

			if ( $filter_opts ) {

				if ( ! empty( $md_opts[ 'opt_filtered' ] ) ) {	// Set before calling filters to prevent recursion.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping filters: options already filtered' );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'setting opt_filtered to 1' );
					}

					$md_opts[ 'opt_filtered' ] = 1;	// Set before calling filters to prevent recursion.

					$mod = $this->get_mod( $comment_id );

					/*
					 * Since WPSSO Core v7.1.0.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying get_md_options filters for comment id ' . $comment_id );
					}

					$md_opts = apply_filters( 'wpsso_get_md_options', $md_opts, $mod );

					/*
					 * Since WPSSO Core v4.31.0.
					 *
					 * Hooked by several integration modules to provide information about the current content.
					 * e-Commerce integration modules will provide information on their product (price,
					 * condition, etc.) and disable these options in the Document SSO metabox.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying get_' . $mod[ 'name' ] . '_options filters for comment id ' . $comment_id );
					}

					$md_opts = apply_filters( 'wpsso_get_' . $mod[ 'name' ] . '_options', $md_opts, $comment_id, $mod );

					/*
					 * Since WPSSO Core v15.1.1.
					 */
					if ( $this->p->util->is_seo_title_disabled() ) {

						unset( $md_opts[ 'seo_title' ] );
					}

					if ( $this->p->util->is_seo_desc_disabled() ) {

						unset( $md_opts[ 'seo_desc' ] );
					}

					/*
					 * Since WPSSO Core v8.2.0.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying sanitize_md_options filters for comment id ' . $comment_id );
					}

					$md_opts = apply_filters( 'wpsso_sanitize_md_options', $md_opts, $mod );
				}
			}

			/*
			 * Maybe save the array to the local cache.
			 */
			if ( $this->md_cache_disabled ) {

				$deref_md_opts = $local_cache[ $cache_id ];

				unset( $local_cache[ $cache_id ], $md_opts );

				return $this->return_options( $comment_id, $deref_md_opts, $md_key, $merge_defs );
			}

			return $this->return_options( $comment_id, $md_opts, $md_key, $merge_defs );
		}

		/*
		 * Use $rel = false to extend WpssoAbstractWpMeta->save_options().
		 */
		public function save_options( $comment_id, $rel = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'comment_id' => $comment_id,
				) );
			}

			if ( empty( $comment_id ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: comment id is empty' );
				}

				return;
			}

			if ( ! $this->user_can_save( $comment_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: user cannot save comment id ' . $comment_id );
				}

				return;
			}

			$this->md_cache_disable();	// Disable the local cache.

			$mod = $this->get_mod( $comment_id );

			$md_opts = $this->get_submit_opts( $mod );	// Merge previous + submitted options and then sanitize.

			$this->md_cache_enable();	// Re-enable the local cache.

			if ( false === $md_opts ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: returned submit options is false' );
				}

				return;
			}

			$md_opts = apply_filters( 'wpsso_save_md_options', $md_opts, $mod );

			$md_opts = apply_filters( 'wpsso_save_' . $mod[ 'name' ] . '_options', $md_opts, $comment_id, $mod );

			return self::update_meta( $comment_id, WPSSO_META_NAME, $md_opts );
		}

		/*
		 * Use $rel = false to extend WpssoAbstractWpMeta->delete_options().
		 */
		public function delete_options( $comment_id, $rel = false ) {

			return self::delete_meta( $comment_id, WPSSO_META_NAME );
		}

		public function refresh_cache_insert_comment( $comment_id, $comment ) {

			if ( ! empty( $comment->comment_approved ) ) {

				if ( ! empty( $comment->comment_post_ID ) ) {

					$this->p->post->refresh_cache( $comment->comment_post_ID );	// Refresh the cache for a single post ID.
				}
			}
		}

		public function refresh_cache_comment_status( $new_status, $old_status, $comment ) {

			if ( 'approved' === $new_status || 'approved' === $old_status ) {

				if ( ! empty( $comment->comment_post_ID ) ) {

					$this->p->post->refresh_cache( $comment->comment_post_ID );	// Refresh the cache for a single post ID.
				}
			}
		}

		/*
		 * Retrieves or updates the metadata cache by key and group.
		 */
		public function get_update_meta_cache( $comment_id ) {

			return SucomUtilWP::get_update_meta_cache( $comment_id, $meta_type = 'comment' );
		}

		/*
		 * Use $rel = false to extend WpssoAbstractWpMeta->user_can_save().
		 */
		public function user_can_save( $comment_id, $rel = false ) {

			if ( ! $this->verify_submit_nonce() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: verify_submit_nonce failed' );
				}

				return false;
			}

			$comment_obj = get_comment( $comment_id );

			$capability = 'edit_comment';

			if ( ! current_user_can( $capability, $comment_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot ' . $capability . ' for comment id ' . $comment_id );
				}

				/*
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->err( sprintf( __( 'Insufficient privileges to save settings for comment ID %1$s.', 'wpsso' ), $comment_id ) );
				}

				return false;
			}

			return true;
		}

		/*
		 * Since WPSSO Core v8.4.0.
		 *
		 * Use get_metadata() instead of get_comment_meta() for consistency.
		 */
		public static function get_meta( $comment_id, $meta_key = '', $single = false ) {

			return get_metadata( 'comment', $comment_id, $meta_key, $single );
		}

		/*
		 * Since WPSSO Core v8.4.0.
		 *
		 * Use update_metadata() instead of update_comment_meta() for consistency.
		 */
		public static function update_meta( $comment_id, $meta_key, $value ) {

			return update_metadata( 'comment', $comment_id, $meta_key, $value );
		}

		/*
		 * Since WPSSO Core v8.4.0.
		 *
		 * Use delete_metadata() instead of delete_comment_meta() for consistency.
		 */
		public static function delete_meta( $comment_id, $meta_key ) {

			return delete_metadata( 'comment', $comment_id, $meta_key );
		}
	}
}
