<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoLinkRel' ) ) {

	class WpssoLinkRel {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$add_link_rel_shortlink = empty( $this->p->options[ 'add_link_rel_shortlink' ] ) ? false : true;

			/**
			 * Remove the 'wp_shortlink_wp_head' hook so we can add our own shortlink meta tag.
			 */
			if ( $add_link_rel_shortlink ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'removing default wp_shortlink_wp_head action' );
				}

				remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			}
		}

		public function get_array( array &$mod, array &$mt_og, $crawler_name, $author_id, $sharing_url ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$link_rel = apply_filters( $this->p->lca . '_link_rel_seed', array(), $mod );

			/**
			 * Link rel author.
			 */
			if ( ! empty( $author_id ) ) {

				$add_link_rel_author = empty( $this->p->options[ 'add_link_rel_author' ] ) ? false : true;

				if ( apply_filters( $this->p->lca . '_add_link_rel_author', $add_link_rel_author, $mod ) ) {

					if ( is_object( $this->p->m[ 'util' ][ 'user' ] ) ) {	// Just in case.

						$link_rel[ 'author' ] = $this->p->m[ 'util' ][ 'user' ]->get_author_website( $author_id,
							$this->p->options[ 'seo_author_field' ] );
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipping author: author id is empty' );
			}

			/**
			 * Link rel canonical.
			 */
			$add_link_rel_canonical = empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? false : true;

			if ( apply_filters( $this->p->lca . '_add_link_rel_canonical', $add_link_rel_canonical, $mod ) ) {
				$link_rel[ 'canonical' ] = $this->p->util->get_canonical_url( $mod );
			}

			/**
			 * Link rel publisher.
			 */
			if ( ! empty( $this->p->options[ 'seo_publisher_url' ] ) ) {

				$add_link_rel_publisher = empty( $this->p->options[ 'add_link_rel_publisher' ] ) ? false : true;

				if ( apply_filters( $this->p->lca . '_add_link_rel_publisher', $add_link_rel_publisher, $mod ) ) {
					$link_rel[ 'publisher' ] = $this->p->options[ 'seo_publisher_url' ];
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipping publisher: seo publisher url is empty' );
			}

			/**
			 * Link rel shortlink.
			 */
			$add_link_rel_shortlink = empty( $this->p->options[ 'add_link_rel_shortlink' ] ) || is_404() || is_search() ? false : true;

			if ( $add_link_rel_shortlink ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'pre-filter add_link_rel_shortlink is true' );
				}
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'pre-filter add_link_rel_shortlink is false' );
			}

			if ( apply_filters( $this->p->lca . '_add_link_rel_shortlink', $add_link_rel_shortlink, $mod ) ) {

				$shortlink = '';

				if ( $mod[ 'is_post' ] ) {

					$shortlink = SucomUtilWP::wp_get_shortlink( $mod[ 'id' ], 'post' );	// $context = post

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'WordPress wp_get_shortlink() = ' . wp_get_shortlink( $mod[ 'id' ], 'post' ) );
						$this->p->debug->log( 'SucomUtilWP::wp_get_shortlink() = ' . $shortlink );
					}

				} elseif ( ! empty( $mt_og[ 'og:url' ] ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'using ' . $this->p->lca . '_get_short_url filters to get shortlink' );
					}

					$shortlink = apply_filters( $this->p->lca . '_get_short_url', $sharing_url,
						$this->p->options[ 'plugin_shortener' ], $mod );
				}

				if ( empty( $shortlink ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'skipping shortlink: short url is empty' );
					}

				} elseif ( $shortlink === $sharing_url ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'skipping shortlink: short url is identical to sharing url' );
					}

				} else {
					$link_rel[ 'shortlink' ] = $shortlink;
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipping shortlink: add_link_rel_shortlink filter returned false' );
			}

			return (array) apply_filters( $this->p->lca . '_link_rel', $link_rel, $mod );
		}
	}
}
