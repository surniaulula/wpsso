<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoMetaName' ) ) {

	class WpssoMetaName {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public function get_array( array &$mod, array &$mt_og, $crawler_name, $author_id ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mt_name = apply_filters( $this->p->lca . '_meta_name_seed', array(), $mod );

			/**
			 * Meta name author.
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_author' ] ) ) {

				if ( isset( $mt_og[ 'og:type' ] ) && $mt_og[ 'og:type' ] === 'article' ) {

					if ( is_object( $this->p->m[ 'util' ][ 'user' ] ) ) {	// Just in case.

						$mt_name[ 'author' ] = $this->p->m[ 'util' ][ 'user' ]->get_author_meta( $author_id,
							$this->p->options[ 'seo_author_name' ] );

					} elseif ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'skipped author meta tag - user module not defined' );
					}

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipped author meta tag - og:type is not an article' );
				}
			}

			/**
			 * Meta name description.
			 */
			$add_meta_name_description = empty( $this->p->options[ 'add_meta_name_description' ] ) ? false : true;
			$add_meta_name_description = apply_filters( $this->p->lca . '_add_meta_name_description', $add_meta_name_description, $mod );

			if ( $add_meta_name_description ) {
				$mt_name[ 'description' ] = $this->p->page->get_description( $this->p->options[ 'seo_desc_max_len' ],
					'...', $mod, true, false, true, 'seo_desc' );	// $add_hashtags is false.
			}

			/**
			 * Meta name thumbnail.
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_thumbnail' ] ) ) {

				$og_images = $this->p->og->get_all_images( 1, 'thumbnail', $mod, false, $md_pre = 'schema' );

				$mt_name[ 'thumbnail' ] = SucomUtil::get_mt_media_url( $og_images );
			}

			/**
			 * Meta name p:domain_verify.
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_p:domain_verify' ] ) ) {

				if ( ! empty( $this->p->options[ 'p_dom_verify' ] ) ) {
					$mt_name[ 'p:domain_verify' ] = $this->p->options[ 'p_dom_verify' ];
				}
			}

			/**
			 * Meta name robots.
			 */
			if ( ! empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {
				$mt_name[ 'robots' ] = $this->p->util->get_robots_content( $mod );
			}

			return (array) apply_filters( $this->p->lca . '_meta_name', $mt_name, $mod );
		}
	}
}
