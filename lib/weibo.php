<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoWeibo' ) ) {

	class WpssoWeibo {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
		}

		public function get_array( $use_post = false, $mod = false, $mt_og = array(), $crawler_name = 'none' ) {

			// pinterest does not read weibo meta tags
			if ( $crawler_name === 'pinterest' ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: '.$crawler_name.' crawler detected' );
				return array();
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $use_post );	// get post/user/term id, module name, and module object reference
			$post_id = $mod['is_post'] ?
				$mod['id'] : false;
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
