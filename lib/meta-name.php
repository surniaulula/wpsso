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

if ( ! class_exists( 'WpssoMetaName' ) ) {

	class WpssoMetaName {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'wp_head', array( $this, 'maybe_disable_noindex' ), -1000 );
		}

		public function maybe_disable_noindex() {

			if ( ! empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

				if ( ! get_option( 'blog_public' ) ) {

					remove_action( 'wp_head', 'noindex', 1 );
				}
			}
		}

		public function get_array( array $mod, array $mt_og = array(), $author_id = 0 ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mt_name = apply_filters( $this->p->lca . '_meta_name_seed', array(), $mod );

			/**
			 * Meta name "author".
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_author' ] ) ) {

				if ( isset( $mt_og[ 'og:type' ] ) && $mt_og[ 'og:type' ] === 'article' ) {

					$mt_name[ 'author' ] = $this->p->user->get_author_meta( $author_id, $this->p->options[ 'seo_author_name' ] );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipped author meta tag - og:type is not an article' );
				}
			}

			/**
			 * Meta name "description".
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_description' ] ) ) {

				$mt_name[ 'description' ] = $this->p->page->get_description( $this->p->options[ 'seo_desc_max_len' ],
					$dots = '...', $mod, $read_cache = true, $add_hashtags = false, $do_encode = true, $md_key = 'seo_desc' );
			}

			/**
			 * Meta name "thumbnail".
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_thumbnail' ] ) ) {

				$mt_name[ 'thumbnail' ] = $this->p->og->get_thumbnail_url( $this->p->lca . '-thumbnail', $mod, $md_pre = 'og' );

				if ( empty( $mt_name[ 'thumbnail' ] ) ) {

					unset( $mt_name[ 'thumbnail' ] );
				}
			}

			/**
			 * Meta name "google-site-verification".
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_google-site-verification' ] ) ) {

				if ( ! empty( $this->p->options[ 'g_site_verify' ] ) ) {	// Google Website Verification ID.

					$mt_name[ 'google-site-verification' ] = $this->p->options[ 'g_site_verify' ];
				}
			}

			/**
			 * Meta name "p:domain_verify".
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_p:domain_verify' ] ) ) {

				if ( ! empty( $this->p->options[ 'p_site_verify' ] ) ) {	// Pinterest Website Verification ID.

					$mt_name[ 'p:domain_verify' ] = $this->p->options[ 'p_site_verify' ];
				}
			}

			/**
			 * Meta name "robots".
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

				$mt_name[ 'robots' ] = $this->p->util->get_robots_content( $mod );
			}

			return (array) apply_filters( $this->p->lca . '_meta_name', $mt_name, $mod );
		}
	}
}
