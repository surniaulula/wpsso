<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoTwitterCard' ) ) {

	class WpssoTwitterCard {

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

		public function get_array( $use_post = false, &$mod = false, &$mt_og = array(), $crawler_name = 'unknown' ) {
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

			$mt_tc = SucomUtil::preg_grep_keys( '/^twitter:/', $mt_og );		// read any pre-defined twitter card values
			$mt_tc = apply_filters( $lca.'_tc_seed', $mt_tc, $mod['use_post'], $mod );

			// the twitter:domain is used in place of the 'view on web' text
			if ( ! isset( $mt_tc['twitter:domain'] ) &&
				! empty( $mt_og['og:url'] ) )
					$mt_tc['twitter:domain'] = preg_replace( '/^.*\/\/([^\/]+).*$/', '$1', $mt_og['og:url'] );

			if ( ! isset( $mt_tc['twitter:site'] ) )
				$mt_tc['twitter:site'] = SucomUtil::get_locale_opt( 'tc_site', $this->p->options, $mod );

			if ( ! isset( $mt_tc['twitter:title'] ) )
				$mt_tc['twitter:title'] = $this->p->webpage->get_title( 70, 
					'...', $mod, true, false, true, 'og_title' );

			if ( ! isset( $mt_tc['twitter:description'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'getting description for twitter:description meta tag' );
				$mt_tc['twitter:description'] = $this->p->webpage->get_description( $this->p->options['tc_desc_len'], 
					'...', $mod, true, true, true, 'tc_desc' );	// $add_hashtags = true
			}

			if ( ! isset( $mt_tc['twitter:creator'] ) ) {
				if ( $mod['is_post'] ) {
					if ( ! empty( $mod['post_author'] ) )
						$mt_tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $mod['post_author'] );
					elseif ( $def_author_id = $this->p->util->get_default_author_id( 'og' ) )
						$mt_tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $def_author_id );

				} elseif ( $mod['is_user'] ) {
					$mt_tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $mod['id'] );

				} elseif ( $def_author_id = $this->p->util->force_default_author( $mod, 'og' ) )
					$mt_tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $def_author_id );
			}

			/*
			 * Player Card
			 *
			 * The twitter:player:stream meta tags are used for self-hosted MP4 videos. The videos provided by
			 * YouTube, Vimeo, Wistia, etc. are application/x-shockwave-flash or text/html (embed URL).
			 *
			 * twitter:player:stream
			 * 	This is a URL to the video file itself (not a video embed). The video must be an mp4 file. The
			 * 	supported codecs within the file are: H.264 video, Baseline Profile Level 3.0, up to 640 x 480 at
			 * 	30 fps and AAC Low Complexity Profile (LC) audio. This property is optional.
			 *
			 * twitter:player:stream:content_type
			 *	The MIME type for your video file (video/mp4). This property is only required if you have set a
			 *	twitter:player:stream meta tag.
			 */
			if ( ! isset( $mt_tc['twitter:card'] ) ) {
				if ( isset( $mt_og['og:video'] ) && count( $mt_og['og:video'] ) > 0 ) {
					foreach ( $mt_og['og:video'] as $video ) {

						if ( ! empty( $video['og:video:embed_url'] ) ) {
							$embed_url = $video['og:video:embed_url'];
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'player card: embed url = '.$embed_url );
						} elseif ( ! empty( $video['og:video:type'] ) &&
							$video['og:video:type'] === 'text/html' ) {
							$embed_url = SucomUtil::get_mt_media_url( $video, 'og:video' );
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'player card: text/html url = '.$embed_url );
						} else continue;

						$mt_tc['twitter:card'] = 'player';
						$mt_tc['twitter:player'] = $embed_url;

						if ( ! empty( $video['og:video:width'] ) )
							$mt_tc['twitter:player:width'] = $video['og:video:width'];

						if ( ! empty( $video['og:video:height'] ) )
							$mt_tc['twitter:player:height'] = $video['og:video:height'];

						if ( ! empty( $video['og:image'] ) )
							$mt_tc['twitter:image'] = $video['og:image'];

						break;	// only use the first video
					}
				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'player card: no videos found' );
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
				if ( ! isset( $mt_tc['twitter:card'] ) && ! $mod['use_post'] ) {
					if ( $this->p->util->force_default_image( $mod, 'og' ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'large image card: checking for default image' );

						$og_image = $this->p->media->get_default_image( 1, $lca.'-tc-lrgimg' );

						if ( count( $og_image ) > 0 ) {
							$image = reset( $og_image );
							$mt_tc['twitter:card'] = 'summary_large_image';
							$mt_tc['twitter:image'] = $image['og:image'];
						}

						$post_id = 0;	// skip additional image checks
					}
				}

				if ( empty( $post_id ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'no post_id: image related cards skipped' );
				} else {
					// post meta image
					if ( ! isset( $mt_tc['twitter:card'] ) ) {

						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'large image card: checking for post image (meta, featured, attached)' );
						$og_image = $this->p->media->get_post_images( 1, $lca.'-tc-lrgimg', $post_id, false );
						if ( count( $og_image ) > 0 ) {
							$image = reset( $og_image );
							$mt_tc['twitter:card'] = 'summary_large_image';
							$mt_tc['twitter:image'] = $image['og:image'];
						}
					}
					// singlepic shortcode image
					if ( ! isset( $mt_tc['twitter:card'] ) && 
						$this->p->is_avail['media']['ngg'] === true ) {

						if ( ! empty( $this->p->m['media']['ngg'] ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'large image card: checking for singlepic image' );
							$og_image = $this->p->m['media']['ngg']->get_singlepic_images( 1, $lca.'-tc-lrgimg', $post_id, false );
							if ( count( $og_image ) > 0 ) {
								$image = reset( $og_image );
								$mt_tc['twitter:card'] = 'summary_large_image';
								$mt_tc['twitter:image'] = $image['og:image'];
							}
						} elseif ( $this->p->debug->enabled )
							$this->p->debug->log( 'large image card: singlepic check skipped - NGG module not available' );
					}
				} 
			}

			/*
			 * Summary Card (default)
			 */
			if ( ! isset( $mt_tc['twitter:card'] ) ) {
				$mt_tc['twitter:card'] = 'summary';
				if ( ! empty( $max['og_img_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'summary card: checking for content image' );
					$og_image = $this->p->og->get_all_images( 1, $lca.'-tc-summary', $mod, false );
					if ( count( $og_image ) > 0 ) {
						$image = reset( $og_image );
						$mt_tc['twitter:image'] = $image['og:image'];
					}
				}
			}

			return apply_filters( $lca.'_tc', $mt_tc, $mod['use_post'], $mod );
		}
	}
}

?>
