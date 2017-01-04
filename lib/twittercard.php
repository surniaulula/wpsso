<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoTwitterCard' ) ) {

	class WpssoTwitterCard {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

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

		// use reference for $mt_og argument to allow unset of existing twitter meta tags.
		public function get_array( array &$mod, array &$mt_og, $crawler_name = false ) {

			if ( $crawler_name === false )
				$crawler_name = SucomUtil::crawler_name();

			// pinterest does not read twitter card markup
			switch ( $crawler_name ) {
				case 'pinterest':
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: '.$crawler_name.' crawler detected' );
					return array();
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$post_id = $mod['is_post'] ? $mod['id'] : false;
			$max = $this->p->util->get_max_nums( $mod );
			$mt_tc = SucomUtil::preg_grep_keys( '/^twitter:/', $mt_og, false, false, true );	// read and unset pre-defined twitter card values
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
					if ( $mod['post_author'] )
						$mt_tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $mod['post_author'] );
				} elseif ( $mod['is_user'] )
					$mt_tc['twitter:creator'] = get_the_author_meta( $this->p->options['plugin_cm_twitter_name'], $mod['id'] );
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
						$embed_url = '';
						$stream_url = '';

						// check for embed or text/html video URL
						if ( ! empty( $video['og:video:embed_url'] ) ) {
							$embed_url = $video['og:video:embed_url'];
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'player card: embed url = '.$embed_url );
						} elseif ( isset( $video['og:video:type'] ) &&
							$video['og:video:type'] === 'text/html' ) {
							$embed_url = SucomUtil::get_mt_media_url( $video, 'og:video' );
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'player card: text/html url = '.$embed_url );
						}

						// check for a video/mp4 stream URL
						if ( isset( $video['og:video:type'] ) &&
							$video['og:video:type'] === 'video/mp4' ) {
							$stream_url = SucomUtil::get_mt_media_url( $video, 'og:video' );
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'player card: video/mp4 url = '.$embed_url );
						}

						if ( ! empty( $embed_url ) ) {
							$mt_tc['twitter:card'] = 'player';
							$mt_tc['twitter:player'] = $embed_url;
						}

						if ( ! empty( $stream_url ) ) {
							$mt_tc['twitter:card'] = 'player';
							$mt_tc['twitter:player:stream'] = $stream_url;
							$mt_tc['twitter:player:stream:content_type'] = $video['og:video:type'];
						}

						if ( ! empty( $mt_tc['twitter:card'] ) ) {
							if ( ! empty( $video['og:video:width'] ) )
								$mt_tc['twitter:player:width'] = $video['og:video:width'];
	
							if ( ! empty( $video['og:video:height'] ) )
								$mt_tc['twitter:player:height'] = $video['og:video:height'];

							// get the video preview image (if one is available)
							$mt_tc['twitter:image'] = SucomUtil::get_mt_media_url( $video, 'og:image' );

							// fallback to open graph image
							if ( empty( $mt_tc['twitter:image'] ) && ! empty( $mt_og['og:image'] ) ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'player card: no video image - using og:image instead' );
								$mt_tc['twitter:image'] = SucomUtil::get_mt_media_url( $mt_og['og:image'], 'og:image' );
							}
						}

						break;	// only use the first video
					}
				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'player card: no videos found' );
			}

			/*
			 * All Image Cards
			 */
			if ( ! empty( $max['og_img_max'] ) ) {

				// default image for archive
				if ( ! isset( $mt_tc['twitter:card'] ) && ! $mod['use_post'] ) {

					list( $card_type, $size_name ) = $this->get_card_type_size( 'default' );

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'use_post is false: checking for forced default image' );

					if ( $this->p->util->force_default_image( $mod, 'og' ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $card_type.' card: getting default image' );

						$og_image = $this->p->media->get_default_image( 1, $size_name );

						if ( count( $og_image ) > 0 ) {
							$image = reset( $og_image );
							$mt_tc['twitter:card'] = $card_type;
							$mt_tc['twitter:image'] = $image['og:image'];
						} elseif ( $this->p->debug->enabled )
							$this->p->debug->log( 'no default image returned' );

						$post_id = 0;	// skip additional image checks

					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( $card_type.' card: no forced default image' );
				}

				if ( ! empty( $post_id ) ) {

					list( $card_type, $size_name ) = $this->get_card_type_size( 'post' );

					// post meta image
					if ( ! isset( $mt_tc['twitter:card'] ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $card_type.' card: getting post image (meta, featured, attached)' );

						$og_image = $this->p->media->get_post_images( 1, $size_name, $post_id, false );

						if ( count( $og_image ) > 0 ) {
							$image = reset( $og_image );
							$mt_tc['twitter:card'] = $card_type;
							$mt_tc['twitter:image'] = $image['og:image'];
						} elseif ( $this->p->debug->enabled )
							$this->p->debug->log( 'no post image returned' );
					}

					// singlepic shortcode image
					if ( ! isset( $mt_tc['twitter:card'] ) ) {
						if ( ! empty( $this->p->is_avail['media']['ngg'] ) ) {
							if ( ! empty( $this->p->m['media']['ngg'] ) ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( $card_type.' card: checking for singlepic image' );
	
								$og_image = $this->p->m['media']['ngg']->get_singlepic_images( 1, $size_name, $post_id, false );
	
								if ( count( $og_image ) > 0 ) {
									$image = reset( $og_image );
									$mt_tc['twitter:card'] = $card_type;
									$mt_tc['twitter:image'] = $image['og:image'];
								} elseif ( $this->p->debug->enabled )
									$this->p->debug->log( 'no singlepic image returned' );
	
							} elseif ( $this->p->debug->enabled )
								$this->p->debug->log( $card_type.' card: ngg plugin module is not defined' );
	
						} elseif ( $this->p->debug->enabled )
							$this->p->debug->log( $card_type.' card: skipped singlepic check (ngg not available)' );
					}

				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'empty post_id: skipped post images' );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'images disabled: maximum images = 0' );

			/*
			 * Summary Card (default)
			 */
			if ( ! isset( $mt_tc['twitter:card'] ) ) {

				list( $card_type, $size_name ) = $this->get_card_type_size( 'default' );

				if ( $this->p->debug->enabled )
					$this->p->debug->log( $card_type.' card: using default card type' );

				$mt_tc['twitter:card'] = $card_type;

				if ( ! empty( $max['og_img_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $card_type.' card: checking for content image' );

					$og_image = $this->p->og->get_all_images( 1, $size_name, $mod, false );

					if ( count( $og_image ) > 0 ) {
						$image = reset( $og_image );
						$mt_tc['twitter:image'] = $image['og:image'];
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'no content image returned' );
				}
			}

			if ( $this->p->debug->enabled ) {
				if ( ! empty( $mt_tc['twitter:image'] ) )
					$this->p->debug->log( $mt_tc['twitter:card'].' card: image '.$mt_tc['twitter:image'] );
				else $this->p->debug->log( $mt_tc['twitter:card'].' card: no image defined' );
			}

			return apply_filters( $lca.'_tc', $mt_tc, $mod['use_post'], $mod );
		}

		public function get_card_type_size( $opt_suffix ) {
			$lca = $this->p->cf['lca'];

			$card_type = isset( $this->p->options['tc_type_'.$opt_suffix] ) ?
				$this->p->options['tc_type_'.$opt_suffix] : 'summary';

			switch ( $card_type ) {
				case 'summary_large_image':
					$size_name = $lca.'-tc-lrgimg';
					break;
				case 'summary':
				default:
					$size_name = $lca.'-tc-summary';
					break;
			}

			return array( $card_type, $size_name );
		}
	}
}

?>
