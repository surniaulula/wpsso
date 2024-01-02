<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdMediaFacebook' ) ) {

	class WpssoStdMediaFacebook {

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
			), $prio = 60 );
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

			} elseif ( false === strpos( $args[ 'url' ], 'facebook.com' ) ) {	// Optimize before preg_match().

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "facebook.com" string not found in video URL' );
				}

				return array();

			/*
			 * Note that forward-slashes in the 'href' query value are encoded as %2F.
			 *
			 * Example: https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Ffacebook%2Fvideos%2F10153231379946729%2F&width=500&show_text=false&appId=525239184171769&height=281
			 */
			} elseif ( preg_match( '/^.*(facebook\.com)\/plugins\/video.php\?href=([^\/\?\&\#<>]+).*$/', $args[ 'url' ], $match ) ) {

				$this->p->msgs->pro_feature_video_found_notice( _x( 'Facebook', 'video service name', 'wpsso' ), $mod );

			/*
			 * Example: https://www.facebook.com/DrDainHeer/videos/943226206036691/
			 */
			} elseif ( preg_match( '/^(.*facebook\.com\/.*\/videos\/[^\?\#]+).*$/', $args[ 'url' ], $match ) ) {

				$this->p->msgs->pro_feature_video_found_notice( _x( 'Facebook', 'video service name', 'wpsso' ), $mod );
			}

			return array();
		}
	}
}
