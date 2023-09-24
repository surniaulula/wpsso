<?php
/*
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdMediaSlideshare' ) ) {

	class WpssoStdMediaSlideshare {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Filter priorities:
			 *
			 *	10   = Youtube Videos.
			 *	20   = Vimeo Videos.
			 * 	30   = Wistia Videos.
			 *	40   = Slideshare Presentations.
			 * 	60   = Facebook Videos.
			 *	80   = Soundcloud Tracks.
			 *	100  = WP Media Library Video Blocks.
			 *	110  = WP Media Library Video Shortcodes.
			 *	1000 = Gravatar Images.
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'content_videos' => 2,
				'video_details'  => 3,
			), $prio = 40 );
		}

		public function filter_content_videos( $videos, $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Example:
			 *
			 *	<object type='application/x-shockwave-flash' wmode='opaque'
			 *		data='http://static.slideshare.net/swf/ssplayer2.swf?id=29776875&doc=album-design-part-3-visuals-140107132112-phpapp01'
			 *			width='650' height='533'>
			 */
			if ( preg_match_all( '/<object[^<>]*? data=[\'"]([^\'"<>]+\.slideshare.net\/swf[^\'"]+)[\'"][^<>]*>/i', $content, $all_matches, PREG_SET_ORDER )  ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' x <object/> slideshare video tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					$video_url = $media[ 1 ];

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found video URL: ' . $video_url );
					}

					$videos[] = array( 'url' => $video_url );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <object/> slideshare video tag(s) found' );
			}

			return $videos;
		}

		public function filter_video_details( $mt_single_video, $args, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $args[ 'attach_id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: have attachment ID' );
				}

				return array();

			} elseif ( false === strpos( $args[ 'url' ], 'slideshare.net' ) ) {	// Optimize before preg_match().

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "slideshare.net" string not found in video URL' );
				}

				return array();

			/*
			 * This matches both the iframe and object URLs.
			 */
			} elseif ( ! preg_match( '/^.*(slideshare\.net)\/.*(\/([0-9]+)|\?id=([0-9]+).*)$/i', $args[ 'url' ], $match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: slideshare video URL pattern not found' );
				}

				return array();
			}

			if ( $this->p->notice->is_admin_pre_notices() ) {

				$this->p->msgs->pro_feature_video_found_notice( __( 'SlideShare', 'wpsso' ), $mod );
			}

			return array();
		}
	}
}
