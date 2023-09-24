<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdMediaYoutube' ) ) {

	class WpssoStdMediaYoutube {

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
				'video_details' => 3,
			), $prio = 10 );
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

			} elseif ( false === strpos( $args[ 'url' ], 'youtu' ) ) {	// Optimize before preg_match().

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "youtu" string not found in video URL' );
				}

				return array();

			} elseif ( ! preg_match( '/^.*(youtube\.com|youtube-nocookie\.com|youtu\.be)\/(watch\/?\?v=)?([^\?\&\#<>]+)' .
				'(\?(list)=([^\?\&\#<>]+)|.*)$/', $args[ 'url' ], $match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: youtube video URL pattern not found' );
				}

				return array();
			}

			if ( $this->p->notice->is_admin_pre_notices() ) {

				$this->p->msgs->pro_feature_video_found_notice( __( 'YouTube', 'wpsso' ), $mod );
			}

			return array();
		}
	}
}
