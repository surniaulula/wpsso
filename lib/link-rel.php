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

if ( ! class_exists( 'WpssoLinkRel' ) ) {

	class WpssoLinkRel {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Always disable the WordPress shortlink meta tag.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'removing default wp_shortlink_wp_head action' );
			}

			remove_action( 'wp_head', 'wp_shortlink_wp_head' );

			add_action( 'wp_head', array( $this, 'maybe_disable_rel_canonical' ), -1000 );

			if ( ! empty( $this->p->avail[ 'amp' ][ 'any' ] ) ) {

				add_action( 'amp_post_template_head', array( $this, 'maybe_disable_rel_canonical' ), -1000 );
			}
		}

		/*
		 * Called by 'wp_head' and 'amp_post_template_head' actions.
		 */
		public function maybe_disable_rel_canonical() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $this->p->util->is_canonical_disabled() ) {	// WPSSO canonical URL meta tag is disabled.

				return;
			}

			/*
			 * If WPSSO is providing the canonical URL, then disable the WordPress and AMP canonical meta tags.
			 */
			$current = function_exists( 'current_action' ) ? current_action() : current_filter();

			switch( $current ) {

				case 'wp_head':

					remove_filter( $current, 'rel_canonical' );
					remove_action( $current, 'amp_frontend_add_canonical' );

					break;

				case 'amp_post_template_head':

					remove_action( $current, 'amp_post_template_add_canonical' );

					break;
			}
		}

		/*
		 * Link Relation URL Tags.
		 */
		public function get_array( array $mod, array $mt_og = array(), $author_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$link_rel = apply_filters( 'wpsso_link_rel_seed', array(), $mod );

			/*
			 * Link rel canonical.
			 */
			$add_link_rel_canonical = $this->p->util->is_canonical_disabled() ? false : true;

			if ( apply_filters( 'wpsso_add_link_rel_canonical', $add_link_rel_canonical, $mod ) ) {

				$link_rel[ 'canonical' ] = $this->p->util->get_canonical_url( $mod );
			}

			/*
			 * Link rel shortlink.
			 */
			$add_link_rel_shortlink = empty( $this->p->options[ 'add_link_rel_shortlink' ] ) ? false : true;

			if ( apply_filters( 'wpsso_add_link_rel_shortlink', $add_link_rel_shortlink, $mod ) ) {

				$canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = true );

				$shortlink = '';

				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

					$shortlink = $this->p->util->get_shortlink( $mod, $context = 'post' );

				} elseif ( ! empty( $canonical_url ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'calling WpssoUtil->shorten_url() to shorten the canonical URL' );
					}

					/*
					 * Shorten URL using the selected shortening service.
					 */
					$shortlink = $this->p->util->shorten_url( $canonical_url, $mod );
				}

				if ( empty( $shortlink ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping shortlink: short url is empty' );
					}

				} elseif ( $shortlink === $canonical_url ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping shortlink: short url is identical to canonical url' );
					}

				} else {

					$link_rel[ 'shortlink' ] = $shortlink;
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'skipping shortlink: add_link_rel_shortlink filter returned false' );
			}

			return apply_filters( 'wpsso_link_rel', $link_rel, $mod );
		}
	}
}
