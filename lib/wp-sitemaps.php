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

		public function wp_sitemaps_posts_query_args( $args, $post_type ) {

			$args[ 'orderby' ] = 'modified';
			$args[ 'order' ]   = 'DESC';

			return $args;
		}

		public function wp_sitemaps_posts_entry( $sitemap_entry, $post, $post_type ) {

			if ( empty( $post->ID ) ) {	// Just in case.

				return $sitemap_entry;
			}

			$mod = $this->p->post->get_mod( $post->ID );

			$directives = $this->p->util->get_robots_directives( $mod );

			if ( ! empty( $directives[ 'noindex' ] ) ) {

				return array();
			}

			$og_type = $this->p->og->get_mod_og_type( $mod );

			if ( 'article' === $og_type ) {

				if ( $mod[ 'post_modified_time' ] ) {

					$sitemap_entry[ 'lastmod' ] = $mod[ 'post_modified_time' ];
				}
			}

			return $sitemap_entry;
		}
	}
}
