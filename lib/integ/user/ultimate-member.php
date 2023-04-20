<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegUserUltimateMember' ) ) {

	class WpssoIntegUserUltimateMember {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'user_image_urls' => 4,
			), $prio = 500 );
		}

		public function filter_user_image_urls( $urls, $size_names, $user_id, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			if ( ! isset( $local_cache[ $user_id ] ) ) {	// Key does not exist or value is not null.

				$img_size = $this->p->media->get_gravatar_size();
				$img_url  = (string) um_get_user_avatar_url( $mod[ 'id' ], $img_size );

				$local_cache[ $user_id ] = $img_url;	// Empty string or image URL.
			}

			if ( ! empty( $local_cache[ $user_id ] ) ) {	// Not false or empty string.

				$urls[] = $local_cache[ $user_id ];
			}

			return $urls;
		}
	}
}
