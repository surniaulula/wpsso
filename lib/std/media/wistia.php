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

if ( ! class_exists( 'WpssoStdMediaWistia' ) ) {

	class WpssoStdMediaWistia {

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
			), $prio = 30 );
		}

		public function filter_content_videos( $videos, $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Example:
			 *
			 *	<div id="wistia_wb36s0vwcg" class="wistia_embed" style="width:640px;height:360px;">&nbsp;</div>
			 *
			 * 	<div class="wistia_embed wistia_async_j38ihh83m5" style="height:349px;width:620px">
			 */
			if ( preg_match_all( '/<div[^<>]*? (id=[\'"]wistia_([^\'"<>]+)[\'"][^<>]* class=[\'"]wistia_embed[\'"]|' .
				'class=[\'"]wistia_embed wistia_async_([^\'"<>]+)[^\'"]*[\'"])[^<>]*>/i', $content,
					$all_matches, PREG_SET_ORDER )  ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' x <div/> wistia_embed video tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					$video_url = 'http://fast.wistia.net/embed/iframe/' . ( empty( $media[ 2 ] ) ? $media[ 3 ] : $media[ 2 ] );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found video URL: ' . $video_url );
					}

					$videos[] = array( 'url' => $video_url );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <div/> wistia_embed video tag(s) found' );
			}

			/*
			 * example: <a href="//fast.wistia.net/embed/iframe/wb36s0vwcg?popover=true" class="wistia-popover[height=360,playerColor=7b796a,width=640]">
			 */
			if ( preg_match_all( '/<a[^<>]*? href=[\'"]([^\'"]+)[\'"][^<>]* class=[\'"]wistia-popover\[([^\]]+)\][\'"][^<>]*>/i',
				$content, $all_matches, PREG_SET_ORDER )  ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' x <a/> wistia-popover video tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					$video_url = $media[ 1 ];

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found video URL: ' . $video_url );
					}

					$videos[] = array( 'url' => $video_url );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <a/> wistia-popover video tag(s) found' );
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

			} elseif ( false === strpos( $args[ 'url' ], 'wistia' ) ) {	// Optimize before preg_match().

				if ( false === strpos( $args[ 'url' ], 'wi.st' ) ) {	// Optimize before preg_match().

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: "wistia" or "wi.st" string not found in video URL' );
					}

					return array();
				}

			} elseif ( ! preg_match( '/^.*(wistia\.net|wistia\.com|wi\.st)\/([^\?\&\#<>]+).*$/i', $args[ 'url' ], $match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: wistia video URL pattern not found' );
				}

				return array();
			}

			if ( $this->p->notice->is_admin_pre_notices() ) {

				$this->p->msgs->pro_feature_video_found_notice( __( 'Wistia', 'wpsso' ), $mod );
			}

			return array();
		}
	}
}
