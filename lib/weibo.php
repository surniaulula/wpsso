<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoWeibo' ) ) {

	class WpssoWeibo {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		public function get_array( array &$mod, array &$mt_og, $crawler_name = false ) {

			if ( $crawler_name === false )
				$crawler_name = SucomUtil::crawler_name();

			// pinterest does not read weibo meta tags
			if ( $crawler_name === 'pinterest' ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: '.$crawler_name.' crawler detected' );
				return array();
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$mt_weibo = SucomUtil::preg_grep_keys( '/^weibo:/', $mt_og );	// read any pre-defined weibo meta tag values
			$mt_weibo = apply_filters( $lca.'_weibo_seed', $mt_weibo, $mod['use_post'], $mod );

			foreach ( array(
				'weibo:article:create_at' => 'article:published_time',
				'weibo:article:update_at' => 'article:modified_time',
			) as $mt_name => $key_name ) {
				if ( isset( $mt_og[$key_name] ) && $mt_og[$key_name] !== '' ) {	// exclude empty strings
					$mt_weibo[$mt_name] = date( 'Y-m-d H:i:s', strtotime( $mt_og[$key_name] ) );
				}
			}

			return apply_filters( $lca.'_weibo', $mt_weibo, $mod['use_post'], $mod );
		}
	}
}

?>
