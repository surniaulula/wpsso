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

		public function get_array( $use_post = false, $post_obj = false, &$og = array(), $crawler_name = 'unknown' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			switch ( $crawler_name ) {
				case 'pinterest':
					// behave normally if rich pin support is disabled
					if ( SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) )
						break;

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: '.$crawler_name.' crawler detected' );
					return array();

					break;
			}

			if ( ! is_object( $post_obj ) )
				$post_obj = $this->p->util->get_post_object( $use_post );

			$post_id = empty( $post_obj->ID ) || empty( $post_obj->post_type ) || 
				! SucomUtil::is_post_page( $use_post ) ? 0 : $post_obj->ID;

			$max = $this->p->util->get_max_nums( $post_id, 'post' );	// a post_id of 0 returns the plugin settings
			$tc = SucomUtil::preg_grep_keys( '/^twitter:/', $og );		// read any pre-defined twitter card values
			$tc = apply_filters( $this->p->cf['lca'].'_tc_seed', $tc, $use_post, $post_obj );

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

					if ( ! empty( $post_obj->post_author ) )
						$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $post_obj->post_author );

					elseif ( $def_user_id = $this->p->util->get_default_author_id( 'og' ) )
						$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $def_user_id );

				} elseif ( SucomUtil::is_user_page() ) {
					$user_id = $this->p->util->get_user_object( 'id' );
					$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $user_id );

				} elseif ( $def_user_id = $this->p->util->force_default_author( $use_post, 'og' ) )
					$tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $def_user_id );
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
				if ( ! isset( $tc['twitter:card'] ) && ! $use_post ) {
					if ( $this->p->util->force_default_image( $use_post, 'og' ) ) {
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

						if ( ! empty( $this->p->m['media']['ngg'] ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'large image card: checking for singlepic image' );
							$og_image = $this->p->m['media']['ngg']->get_singlepic_images( 1, 
								$this->p->cf['lca'].'-tc-lrgimg', $post_id, false );
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
					$og_image = $this->p->og->get_all_images( 1, $this->p->cf['lca'].'-tc-summary', $post_id, false );
					if ( count( $og_image ) > 0 ) {
						$image = reset( $og_image );
						$tc['twitter:image'] = $image['og:image'];
					}
				}
			}

			return apply_filters( $this->p->cf['lca'].'_tc', $tc, $use_post, $post_obj );
		}
	}
}

?>
