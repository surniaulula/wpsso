<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
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
		 * Maybe disable the WordPress and AMP canonical tags.
		 *
		 * Called by 'wp_head' and 'amp_post_template_head' actions.
		 */
		public function maybe_disable_rel_canonical() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * WpssoUtil->is_canonical_disabled() returns true if:
			 *
			 *	- An SEO plugin is active.
			 *	- The 'add_link_rel_canonical' option is unchecked.
			 *	- The 'wpsso_add_link_rel_canonical' filter returns false.
			 *	- The 'wpsso_canonical_disabled' filter returns true.
			 */
			if ( $this->p->util->is_canonical_disabled() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: canonical is disabled' );
				}

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
			 *
			 * WpssoUtil->is_canonical_disabled() returns true if:
			 *
			 *	- An SEO plugin is active.
			 *	- The 'add_link_rel_canonical' option is unchecked.
			 *	- The 'wpsso_add_link_rel_canonical' filter returns false.
			 *	- The 'wpsso_canonical_disabled' filter returns true.
			 */
			if ( ! $this->p->util->is_canonical_disabled() ) {

				$link_rel[ 'canonical' ] = $this->p->util->get_canonical_url( $mod );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'skipping canonical: canonical is disabled' );
			}

			/*
			 * Link rel shortlink.
			 *
			 * WpssoUtil->is_shortlink_disabled() returns true if:
			 *
			 *	- The 'add_link_rel_shortlink' option is unchecked.
			 *	- The 'wpsso_add_link_rel_shortlink' filter returns false.
			 *	- The 'wpsso_shortlink_disabled' filter returns true.
			 */
			if ( ! $this->p->util->is_shortlink_disabled() ) {

				$canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = true );

				$shortlink = '';

				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

					$shortlink = $this->p->util->get_shortlink( $mod, $context = 'post' );

				} elseif ( ! empty( $canonical_url ) ) {	// Just in case.

					/*
					 * Shorten URL using the selected shortening service.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'calling WpssoUtil->shorten_url() to shorten the canonical URL' );
					}

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

				$this->p->debug->log( 'skipping shortlink: shortlink is disabled' );
			}

			return apply_filters( 'wpsso_link_rel', $link_rel, $mod );
		}
	}
}
