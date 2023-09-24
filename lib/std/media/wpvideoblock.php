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

if ( ! class_exists( 'WpssoStdMediaWpvideoblock' ) ) {

	class WpssoStdMediaWpvideoblock {

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
				'content_videos' => 3,
			), $prio = 100 );
		}

		public function filter_content_videos( $videos, $content, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Example video block:
			 *
			 * <figure class="wp-block-video">
			 *
			 *	<video controls poster="http://adm.surniaulula.com/wp-content/uploads/2023/02/Eddy-Need-Remix-mp3-image.jpg"
			 *		src="http://adm.surniaulula.com/wp-content/uploads/2023/08/sample-mp4-file.mp4"></video>
			 *
			 *	<figcaption class="wp-element-caption">Big bunny caption.</figcaption>
			 *
			 * </figure>
			 *
			 */
			if ( preg_match_all( '/<figure class="wp-block-video[^"]*"><video [^<>]* src=[\'"]([^\'"<>]+)[\'"].*<\/figure>/Ui',
					$content, $all_matches, PREG_SET_ORDER )  ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ).' x <figure/> WordPress video block tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					$video_url = remove_query_arg( '_', $media[ 1 ] );	// Remove the instance id.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found video URL: ' . $video_url );
					}

					if ( $this->p->notice->is_admin_pre_notices() ) {

						$this->p->msgs->pro_feature_video_found_notice( __( 'WordPress block', 'wpsso' ), $mod );
					}
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <figure/> WordPress video block tag(s) found' );
			}

			return $videos;
		}
	}
}
