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

if ( ! class_exists( 'WpssoMetaName' ) ) {

	class WpssoMetaName {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'wp_head', array( $this, 'maybe_disable_noindex' ), -1000 );
		}

		public function maybe_disable_noindex() {

			if ( $this->p->util->robots->is_enabled() ) {

				remove_action( 'wp_head', 'noindex', 1 );

				remove_action( 'wp_head', 'wp_robots', 1 );
			}
		}

		/*
		 * Meta Name Tags.
		 */
		public function get_array( array $mod, array $mt_og = array(), $author_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mt_name = apply_filters( 'wpsso_meta_name_seed', array(), $mod );

			/*
			 * Meta name "author".
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_author' ] ) ) {

				if ( isset( $mt_og[ 'og:type' ] ) && 'article' === $mt_og[ 'og:type' ] ) {

					$mt_name[ 'author' ] = $this->p->user->get_author_meta( $author_id, 'display_name' );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipped author meta tag - og:type is not an article' );
				}
			}

			/*
			 * Meta name "description".
			 */
			if ( ! $this->p->util->is_seo_desc_disabled() ) {

				$mt_name[ 'description' ] = $this->p->page->get_description( $mod, $md_key = 'seo_desc', $max_len = 'seo_desc' );
			}

			/*
			 * Meta name "thumbnail".
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_thumbnail' ] ) ) {

				$mt_name[ 'thumbnail' ] = $this->p->media->get_thumbnail_url( 'wpsso-thumbnail', $mod, $md_pre = 'og' );

				if ( empty( $mt_name[ 'thumbnail' ] ) ) {

					unset( $mt_name[ 'thumbnail' ] );
				}
			}

			/*
			 * Baidu, Google, Microsoft Bing, Pinterest, and Yandex website verification IDs.
			 */
			foreach ( WpssoConfig::$cf[ 'opt' ][ 'site_verify_meta_names' ] as $site_verify => $meta_name ) {

				if ( ! empty( $this->p->options[ 'add_meta_name_' . $meta_name ] ) ) {

					if ( ! empty( $this->p->options[ $site_verify ] ) ) {

						$mt_name[ $meta_name ] = $this->p->options[ $site_verify ];
					}
				}
			}

			/*
			 * Meta name "robots".
			 */
			if ( $this->p->util->robots->is_enabled() ) {

				$mt_name[ 'robots' ] = $this->p->util->robots->get_content( $mod );
			}

			return apply_filters( 'wpsso_meta_name', $mt_name, $mod );
		}
	}
}
