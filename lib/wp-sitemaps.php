<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoWpSiteMaps' ) ) {

	class WpssoWpSiteMaps {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtilWP::wp_sitemaps_enabled() ) {	// Nothing to do.

				return;
			}

			add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'wp_sitemaps_posts_query_args' ), 10, 2 );
			add_filter( 'wp_sitemaps_posts_entry', array( $this, 'wp_sitemaps_posts_entry' ), 10, 3 );
		}

		/**
		 * The 'wp_sitemaps_posts_query_args' filter is applied at least twice; to get the maximum number of pages, and to
		 * get posts for each of those pages. This method uses a local cache to avoid running the same code more than once.
		 */
		public function wp_sitemaps_posts_query_args( $args, $post_type ) {

			$args[ 'orderby' ] = 'modified';
			$args[ 'order' ]   = 'DESC';

			if ( ! empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

				static $local_cache = array();

				if ( ! isset( $local_cache[ $post_type ] ) ) {

					$local_cache[ $post_type ] = array();

					$query = new WP_Query( array_merge( $args, array(
						'fields'        => 'ids',	// Return an array of post ids.
						'no_found_rows' => true,	// Skip counting total rows found.
					) ) );

					foreach ( $query->posts as $post_id ) {
				
						if ( $this->p->util->robots->is_noindex( 'post', $post_id ) ) {

							$local_cache[ $post_type ][] = $post_id;
						}
					}
				}

				if ( ! empty( $local_cache[ $post_type ] ) ) {

					$args[ 'post__not_in' ] = empty( $args[ 'post__not_in' ] ) ? $local_cache[ $post_type ] :
						array_merge( $args[ 'post__not_in' ], $local_cache[ $post_type ] );
				}
			}

			return $args;
		}

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
	}
}
