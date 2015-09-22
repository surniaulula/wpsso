<?php
/*
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING,
 * RUNNING, OR OTHERWISE USING THE WORDPRESS SOCIAL SHARING OPTIMIZATION (WPSSO) PRO APPLICATION, YOU
 * AGREE TO BE BOUND BY THE TERMS OF THIS LICENSE AGREEMENT. IF YOU DO NOT AGREE
 * TO THE TERMS OF THIS LICENSE AGREEMENT, PLEASE DO NOT INSTALL, RUN, COPY, OR
 * OTHERWISE USE THE WORDPRESS SOCIAL SHARING OPTIMIZATION (WPSSO) PRO APPLICATION.
 * 
 * License: Nontransferable License for a WordPress Site Address URL
 * License URI: http://surniaulula.com/wp-content/plugins/wpsso/license/pro.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoTwittercard' ) ) {

	class WpssoTwittercard {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 'plugin_image_sizes' => 1 ) );
		}

		public function filter_plugin_image_sizes( $sizes ) {
			// the app and player cards do not use an image size
			$sizes['tc_sum'] = array( 'name' => 'tc-summary', 'label' => 'Twitter Summary Card' );
			$sizes['tc_lrgimg'] = array( 'name' => 'tc-lrgimg', 'label' => 'Twitter Large Image Card' );
			return $sizes;
		}

		public function get_array( $use_post = false, $obj = false, &$og = array() ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! is_object( $obj ) )
				$obj = $this->p->util->get_post_object( $use_post );

			$post_id = empty( $obj->ID ) || empty( $obj->post_type ) || 
				( ! is_singular() && $use_post === false ) ? 0 : $obj->ID;

			$og_max = $this->p->util->get_max_nums( $post_id, 'post' );	// post_id 0 returns the plugin settings
			$tc = SucomUtil::preg_grep_keys( '/^twitter:/', $og );		// read any pre-defined twitter card values
			$tc = apply_filters( $this->p->cf['lca'].'_tc_seed', $tc, $use_post, $obj );

			// the twitter:domain is used in place of the 'view on web' text
			if ( ! isset( $tc['twitter:domain'] ) &&
				! empty( $og['og:url'] ) )
					$tc['twitter:domain'] = preg_replace( '/^.*\/\/([^\/]+).*$/', '$1', $og['og:url'] );

			if ( ! isset( $tc['twitter:site'] ) && 
				! empty( $this->p->options['tc_site'] ) )
					$tc['twitter:site'] = $this->p->options['tc_site'];

			if ( ! isset( $tc['twitter:title'] ) )
				$tc['twitter:title'] = $this->p->webpage->get_title( 70, 
					'...', $use_post, true, false, true, 'og_title' );

			if ( ! isset( $tc['twitter:description'] ) )
				$tc['twitter:description'] = $this->p->webpage->get_description( $this->p->options['tc_desc_len'], 
					'...', $use_post, true, true, true, 'tc_desc' );

			if ( ! isset( $tc['twitter:creator'] ) ) {
				if ( SucomUtil::is_post_page( $use_post ) ) {
					if ( ! empty( $obj->post_author ) )
						$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], 
							$obj->post_author );
					elseif ( ! empty( $this->p->options['og_def_author_id'] ) )
						$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], 
							$this->p->options['og_def_author_id'] );

				} elseif ( SucomUtil::is_author_page() ) {
					$author_id = $this->p->util->get_author_object( 'id' );
					$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $author_id );

				} elseif ( $this->p->util->force_default_author( $use_post ) ) {
					$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], 
						$this->p->options['og_def_author_id'] );
				}
			}

			/*
			 * Player Card
			 */
			// player card relies on existing og meta tags - a valid post_id is not required
			if ( ! isset( $tc['twitter:card'] ) ) {

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'player card: checking for videos' );
				if ( isset( $og['og:video'] ) && count( $og['og:video'] ) > 0 ) {
					foreach ( $og['og:video'] as $video ) {
						if ( ! empty( $video['og:video:embed_url'] ) ) {
							$tc['twitter:card'] = 'player';
							$tc['twitter:player'] = $video['og:video:embed_url'];
							if ( ! empty( $video['og:image'] ) )
								$tc['twitter:image'] = $video['og:image'];
							if ( ! empty( $video['og:video:width'] ) )
								$tc['twitter:player:width'] = $video['og:video:width'];
							if ( ! empty( $video['og:video:height'] ) )
								$tc['twitter:player:height'] = $video['og:video:height'];
							break;	// only list the first video
						}
					}
				}
			}

			/*
			 * All Image Cards
			 */
			if ( empty( $og_max['og_img_max'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'images disabled: maximum images = 0' );
			} else {
				/*
				 * Default Image for Indexes
				 */
				if ( ! isset( $tc['twitter:card'] ) && ! $use_post ) {
					if ( $this->p->util->force_default_image() ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'large image card: checking for default image' );
						$og_image = $this->p->media->get_default_image( 1, $this->p->cf['lca'].'-tc-lrgimg' );
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
						$og_image = $this->p->media->get_post_images( 1, $this->p->cf['lca'].'-tc-lrgimg', $post_id, false );
						if ( count( $og_image ) > 0 ) {
							$image = reset( $og_image );
							$tc['twitter:card'] = 'summary_large_image';
							$tc['twitter:image'] = $image['og:image'];
						}
					}
					// singlepic shortcode image
					if ( ! isset( $tc['twitter:card'] ) && 
						$this->p->is_avail['media']['ngg'] === true ) {

						if ( ! empty( $this->p->mods['media']['ngg'] ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'large image card: checking for singlepic image' );
							$og_image = $this->p->mods['media']['ngg']->get_singlepic_images( 1, 
								$this->p->cf['lca'].'-tc-lrgimg', false );
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
				if ( ! empty( $og_max['og_img_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'summary card: checking for content image' );
					$og_image = $this->p->og->get_all_images( 1, $this->p->cf['lca'].'-tc-summary', $post_id, false );
					if ( count( $og_image ) > 0 ) {
						$image = reset( $og_image );
						$tc['twitter:image'] = $image['og:image'];
					}
				}
			}

			$tc = apply_filters( $this->p->cf['lca'].'_tc', $tc, $use_post, $obj );

			return $tc;
		}
	}
}

?>
