<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoWpSitemaps' ) ) {

	class WpssoWpSitemaps {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtilWP::sitemaps_enabled() ) {	// Nothing to do.

				return;
			}

			add_filter( 'wp_sitemaps_post_types', array( $this, 'wp_sitemaps_post_types' ), 10, 1 );
			add_filter( 'wp_sitemaps_posts_entry', array( $this, 'wp_sitemaps_posts_entry' ), 10, 3 );
			add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'wp_sitemaps_posts_query_args' ), 10, 2 );
			add_filter( 'wp_sitemaps_taxonomies', array( $this, 'wp_sitemaps_taxonomies' ), 10, 1 );
			add_filter( 'wp_sitemaps_taxonomies_query_args', array( $this, 'wp_sitemaps_taxonomies_query_args' ), 10, 2 );
			add_filter( 'wp_sitemaps_users_query_args', array( $this, 'wp_sitemaps_users_query_args' ), 10, 1 );
		}

		public function wp_sitemaps_post_types( $post_types ) {

			$post_types = SucomUtilWP::get_post_types( $output = 'objects', $sort_by_label = false );

			foreach ( $post_types as $name => $obj ) {

				if ( empty( $this->p->options[ 'plugin_sitemaps_for_' . $name ] ) ) {

					unset( $post_types[ $name ] );
				}
			}

			return $post_types;
		}

		/**
		 * Add a modification time for Open Graph type non-website posts (ie. article, book, product, etc.).
		 */
		public function wp_sitemaps_posts_entry( $sitemap_entry, $post, $post_type ) {

			if ( empty( $post->ID ) ) {	// Just in case.

				return $sitemap_entry;
			}

			$mod = $this->p->post->get_mod( $post->ID );

			$og_type = $this->p->og->get_mod_og_type( $mod );

			if ( 'website' !== $og_type ) {

				if ( $mod[ 'post_modified_time' ] ) {

					$sitemap_entry[ 'lastmod' ] = $mod[ 'post_modified_time' ];
				}
			}

			return $sitemap_entry;
		}

		/**
		 * Exclude posts from the sitemap that have been defined as noindex.
		 */
		public function wp_sitemaps_posts_query_args( $args, $post_type ) {

			/**
			 * The published post status for attachments is 'inherit'.
			 */
			if ( 'attachment' === $post_type ) {

				$args[ 'post_status' ] = array( 'inherit' );
			}

			if ( ! empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

				static $local_cache = array();	// Create post ID exclusion only once.

				if ( ! isset( $local_cache[ $post_type ] ) ) {

					$local_cache[ $post_type ] = array();

					$query = new WP_Query( array_merge( $args, array(
						'fields'        => 'ids',
						'no_found_rows' => true,
						'post_type'     => $post_type,
					) ) );

					if ( ! empty( $query->posts ) ) {	// Just in case.

						foreach ( $query->posts as $post_id ) {

							if ( $this->p->util->robots->is_noindex( 'post', $post_id ) ) {

								$local_cache[ $post_type ][] = $post_id;
							}
						}
					}
				}

				if ( ! empty( $local_cache[ $post_type ] ) ) {

					$args[ 'post__not_in' ] = empty( $args[ 'post__not_in' ] ) ?
						$local_cache[ $post_type ] :
						array_merge( $args[ 'post__not_in' ], $local_cache[ $post_type ] );
				}
			}

			return $args;
		}

		public function wp_sitemaps_taxonomies( $taxonomies ) {

			$taxonomies = SucomUtilWP::get_taxonomies( $output = 'objects', $sort_by_label = false );

			foreach ( $taxonomies as $name => $obj ) {

				if ( empty( $this->p->options[ 'plugin_sitemaps_for_tax_' . $name ] ) ) {

					unset( $taxonomies[ $name ] );
				}
			}

			return $taxonomies;
		}

		/**
		 * Exclude terms from the sitemap that have been defined as noindex.
		 */
		public function wp_sitemaps_taxonomies_query_args( $args, $taxonomy ) {

			if ( ! empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

				static $local_cache = array();	// Create term ID exclusion only once.

				if ( ! isset( $local_cache[ $taxonomy ] ) ) {

					$local_cache[ $taxonomy ] = array();

					$query = new WP_Term_Query( array_merge( $args, array(
						'fields'        => 'ids',
						'no_found_rows' => true,
					) ) );

					if ( ! empty( $query->terms ) ) {	// Just in case.

						foreach ( $query->terms as $term_id ) {

							if ( $this->p->util->robots->is_noindex( 'term', $term_id ) ) {

								$local_cache[ $taxonomy ][] = $term_id;
							}
						}
					}
				}

				if ( ! empty( $local_cache[ $taxonomy ] ) ) {

					$args[ 'exclude' ] = empty( $args[ 'exclude' ] ) ?
						$local_cache[ $taxonomy ] :
						array_merge( $args[ 'exclude' ], $local_cache[ $taxonomy ] );
				}
			}

			return $args;
		}

		/**
		 * Exclude users from the sitemap that have been defined as noindex.
		 */
		public function wp_sitemaps_users_query_args( $args ) {

			if ( empty( $this->p->options[ 'plugin_sitemaps_for_user_page' ] ) ) {

				/**
				 * Exclude all user pages by including only user ID 0 (which does not exist).
				 */
				$args[ 'include' ] = array( 0 );

			} elseif ( ! empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

				static $local_cache = null;	// Create user ID exclusion only once.

				if ( null === $local_cache ) {

					$local_cache = array();

					$query = new WP_User_Query( array_merge( $args, array(
						'fields'        => 'ids',
						'no_found_rows' => true,
					) ) );

					$users = $query->get_results();

					if ( ! empty( $users ) ) {	// Just in case.

						foreach ( $users as $user_id ) {

							if ( $this->p->util->robots->is_noindex( 'user', $user_id ) ) {

								$local_cache[] = $user_id;
							}
						}
					}
				}

				if ( ! empty( $local_cache ) ) {

					$args[ 'exclude' ] = empty( $args[ 'exclude' ] ) ?
						$local_cache :
						array_merge( $args[ 'exclude' ], $local_cache );
				}
			}

			return $args;
		}
	}
}
