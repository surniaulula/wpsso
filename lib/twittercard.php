<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoTwittercard' ) ) {

	class WpssoTwittercard {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes' => 1,
			) );
		}

		public function filter_plugin_image_sizes( $sizes ) {

			$sizes['tc_sum'] = array(		// options prefix
				'name' => 'tc-summary',		// wpsso-tc-summary
				'label' => _x( 'Twitter Summary Card',
					'image size label', 'wpsso' ),
			);

			$sizes['tc_lrgimg'] = array(		// options prefix
				'name' => 'tc-lrgimg',		// wpsso-tc-lrgimg
				'label' => _x( 'Twitter Large Image Card',
					'image size label', 'wpsso' ),
			);

			return $sizes;
		}

		public function get_array( $use_post = false, &$mod = false, &$og = array(), $crawler_name = 'unknown' ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			switch ( $crawler_name ) {
				case 'pinterest':
					// twitter card meta tags are not necessary for the pinterest crawler
					// behave normally if rich pin support is disabled, otherwise, exit early
					if ( SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) )
						break;

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: '.$crawler_name.' crawler detected' );
					return array();

					break;
			}

			$lca = $this->p->cf['lca'];
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $use_post );	// get post/user/term id, module name, and module object reference
			$max = $this->p->util->get_max_nums( $mod );
			$post_id = $mod['is_post'] ? $mod['id'] : false;

			$tc = SucomUtil::preg_grep_keys( '/^twitter:/', $og );		// read any pre-defined twitter card values
			$tc = apply_filters( $lca.'_tc_seed', $tc, $mod['use_post'], $mod );

			// the twitter:domain is used in place of the 'view on web' text
			if ( ! isset( $tc['twitter:domain'] ) &&
				! empty( $og['og:url'] ) )
					$tc['twitter:domain'] = preg_replace( '/^.*\/\/([^\/]+).*$/', '$1', $og['og:url'] );

			if ( ! isset( $tc['twitter:site'] ) )
				$tc['twitter:site'] = SucomUtil::get_locale_opt( 'tc_site', $this->p->options, $mod );

			if ( ! isset( $tc['twitter:title'] ) )
				$tc['twitter:title'] = $this->p->webpage->get_title( 70, 
					'...', $mod, true, false, true, 'og_title' );

			if ( ! isset( $tc['twitter:description'] ) )
				$tc['twitter:description'] = $this->p->webpage->get_description( $this->p->options['tc_desc_len'], 
					'...', $mod, true, true, true, 'tc_desc' );	// $add_hashtags = true

			if ( ! isset( $tc['twitter:creator'] ) ) {
				if ( $mod['is_post'] ) {
					if ( ! empty( $mod['post_author'] ) )
						$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $mod['post_author'] );
					elseif ( $def_author_id = $this->p->util->get_default_author_id( 'og' ) )
						$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $def_author_id );

				} elseif ( $mod['is_user'] ) {
					$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $mod['id'] );

				} elseif ( $def_author_id = $this->p->util->force_default_author( $mod, 'og' ) )
					$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $def_author_id );
			}

			/*
			 * Player Card
			 */
			if ( ! isset( $tc['twitter:card'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'player card: checking for videos' );

				if ( isset( $og['og:video'] ) && count( $og['og:video'] ) > 0 ) {
					foreach ( $og['og:video'] as $video ) {
						if ( ! empty( $video['og:video:embed_url'] ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'using og:video:embed_url = '.
									$video['og:video:embed_url'] );
							$tc['twitter:card'] = 'player';
							$tc['twitter:player'] = $video['og:video:embed_url'];
							if ( ! empty( $video['og:video:width'] ) )
								$tc['twitter:player:width'] = $video['og:video:width'];
							if ( ! empty( $video['og:video:height'] ) )
								$tc['twitter:player:height'] = $video['og:video:height'];
							if ( ! empty( $video['og:image'] ) )
								$tc['twitter:image'] = $video['og:image'];
							break;	// only list the first video
						}
					}
				}
			}

			/*
			 * All Image Cards
			 */
			if ( empty( $max['og_img_max'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'images disabled: maximum images = 0' );
			} else {
				/*
				 * Default Image for Indexes
				 */
				if ( ! isset( $tc['twitter:card'] ) && ! $mod['use_post'] ) {
					if ( $this->p->util->force_default_image( $mod, 'og' ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'large image card: checking for default image' );

						$og_image = $this->p->media->get_default_image( 1, $lca.'-tc-lrgimg' );

						if ( count( $og_image ) > 0 ) {
							$image = reset( $og_image );
							$tc['twitter:card'] = 'summary_large_image';
							$tc['twitter:image'] = $image['og:image'];
						}

						$post_id = 0;	// skip additional image checks
					}
				}

				if ( empty( $post_id ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'no post_id: image related cards skipped' );
				} else {
					// post meta image
					if ( ! isset( $tc['twitter:card'] ) ) {

						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'large image card: checking for post image (meta, featured, attached)' );
						$og_image = $this->p->media->get_post_images( 1, $lca.'-tc-lrgimg', $post_id, false );
						if ( count( $og_image ) > 0 ) {
							$image = reset( $og_image );
							$tc['twitter:card'] = 'summary_large_image';
							$tc['twitter:image'] = $image['og:image'];
						}
					}
					// singlepic shortcode image
					if ( ! isset( $tc['twitter:card'] ) && 
						$this->p->is_avail['media']['ngg'] === true ) {

						if ( ! empty( $this->p->m['media']['ngg'] ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'large image card: checking for singlepic image' );
							$og_image = $this->p->m['media']['ngg']->get_singlepic_images( 1, $lca.'-tc-lrgimg', $post_id, false );
							if ( count( $og_image ) > 0 ) {
								$image = reset( $og_image );
								$tc['twitter:card'] = 'summary_large_image';
								$tc['twitter:image'] = $image['og:image'];
							}
						} elseif ( $this->p->debug->enabled )
							$this->p->debug->log( 'large image card: singlepic check skipped - NGG module not available' );
					}
				} 
			}

			/*
			 * Summary Card (default)
			 */
			if ( ! isset( $tc['twitter:card'] ) ) {
				$tc['twitter:card'] = 'summary';
				if ( ! empty( $max['og_img_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'summary card: checking for content image' );
					$og_image = $this->p->og->get_all_images( 1, $lca.'-tc-summary', $mod, false );
					if ( count( $og_image ) > 0 ) {
						$image = reset( $og_image );
						$tc['twitter:image'] = $image['og:image'];
					}
				}
			}

			return apply_filters( $lca.'_tc', $tc, $mod['use_post'], $mod );
		}
	}
}

?>
