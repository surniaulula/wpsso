<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoWeibo' ) ) {

	class WpssoWeibo {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public function get_array( array &$mod, array &$mt_og, $crawler_name ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mt_weibo = SucomUtil::preg_grep_keys( '/^weibo:/', $mt_og );	// read any pre-defined weibo meta tag values
			$mt_weibo = apply_filters( $this->p->lca . '_weibo_seed', $mt_weibo, $mod );

			if ( $mt_og['og:type'] === 'article' ) {
				foreach ( array(
					'weibo:article:create_at' => 'article:published_time',
					'weibo:article:update_at' => 'article:modified_time',
				) as $mt_name => $key_name ) {
					if ( isset( $mt_og[$key_name] ) && $mt_og[$key_name] !== '' ) {	// exclude empty strings
						$mt_weibo[$mt_name] = gmdate( 'Y-m-d H:i:s', strtotime( $mt_og[$key_name] ) );
					}
				}
			}

			return (array) apply_filters( $this->p->lca . '_weibo', $mt_weibo, $mod );
		}
	}
}
