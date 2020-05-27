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

			add_action( 'wp_head', array( $this, 'maybe_disable_rel_canonical' ), -1000 );

			if ( ! empty( $this->p->avail[ 'amp' ][ 'any' ] ) ) {
				add_action( 'amp_post_template_head', array( $this, 'maybe_disable_rel_canonical' ), -1000 );
			}
		}

		/**
		 * Called by 'wp_head' and 'amp_post_template_head' actions.
		 */
		public function maybe_disable_rel_canonical() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->options[ 'add_link_rel_canonical' ] ) ) {

				if ( function_exists( 'current_action' ) ) {	// Since WP v3.9.
					$current  = current_action();
				} else {
					$current  = current_filter();
				}

				switch( $current ) {

					case 'wp_head':

						remove_filter( $current, 'rel_canonical' );	// WordPress.

						remove_action( $current, 'amp_frontend_add_canonical' );	// AMP.

						break;

					case 'amp_post_template_head':

						remove_action( $current, 'amp_post_template_add_canonical' );	// AMP.

						break;
				}
			}
		}

		public function get_array( array $mod, array $mt_og = array(), $author_id = 0, $sharing_url = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$link_rel = apply_filters( $this->p->lca . '_link_rel_seed', array(), $mod );

			/**
			 * Link rel canonical.
			 */
			$add_link_rel_canonical = empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? false : true;

			if ( apply_filters( $this->p->lca . '_add_link_rel_canonical', $add_link_rel_canonical, $mod ) ) {

				$link_rel[ 'canonical' ] = $this->p->util->get_canonical_url( $mod );
			}

			/**
			 * Link rel shortlink.
			 */
			$add_link_rel_shortlink = empty( $this->p->options[ 'add_link_rel_shortlink' ] ) ? false : true;

			if ( apply_filters( $this->p->lca . '_add_link_rel_shortlink', $add_link_rel_shortlink, $mod ) ) {

				$shortlink = '';

				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

					$shortlink = SucomUtilWP::wp_get_shortlink( $mod[ 'id' ], $context = 'post' );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'WordPress wp_get_shortlink() = ' . wp_get_shortlink( $mod[ 'id' ], 'post' ) );

						$this->p->debug->log( 'SucomUtilWP::wp_get_shortlink() = ' . $shortlink );
					}

				/**
				 * Shortlinks are used by social sites, so use the sharing URL instead of the canonical URL.
				 */
				} elseif ( ! empty( $sharing_url ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'using ' . $this->p->lca . '_get_short_url filters to get shortlink' );
					}

					$shortener = $this->p->options[ 'plugin_shortener' ];

					$shortlink = apply_filters( $this->p->lca . '_get_short_url', $sharing_url, $shortener, $mod, $is_main = true );
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
