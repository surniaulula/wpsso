<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoPinterest' ) ) {

	class WpssoPinterest {

		protected $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->options[ 'p_add_img_html' ] ) ) {
				add_filter( 'the_content', array( $this, 'get_pinterest_img_html' ) );
			}
		}

		public function show_pinterest_img_html() {

			echo $this->get_pinterest_img_html();
		}

		public function get_pinterest_img_html( $content = '' ) {

			/**
			 * Do not add the pinterest image if the current webpage is amp or rss feed.
			 */
			if ( SucomUtil::is_amp() || is_feed() ) {
				return $content;
			}

			/**
			 * Check if the content filter is being applied to create a description text.
			 */
			if ( ! empty( $GLOBALS[ $this->p->lca . '_doing_filter_the_content' ] ) ) {
				return $content;
			}

			static $do_once = array();						// Prevent recursion.

			$use_post = in_the_loop() ? true : false;				// Use the $post object inside the loop.
			$use_post = apply_filters( $this->p->lca . '_use_post', $use_post );	// Used by woocommerce with is_shop().

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'required call to get_page_mod()' );
			}

			$mod        = $this->p->util->get_page_mod( $use_post );		// $use_post is true by default.
			$cache_salt = SucomUtil::get_mod_salt( $mod );

			if ( ! empty( $do_once[ $cache_salt ] ) ) {				// Check for recursion.
				return $content;
			} else {
				$do_once[ $cache_salt ] = true;
			}

			$size_name = $this->p->lca . '-schema';
			$og_images = $this->p->og->get_all_images( 1, $size_name, $mod, false, $md_pre = 'schema' );
			$image_url = SucomUtil::get_mt_media_url( $og_images );

			if ( ! empty( $image_url ) ) {

				$data_pin_desc = $this->p->page->get_description( $this->p->options[ 'schema_desc_max_len' ],
					$dots = '...', $mod, $read_cache = true, $add_hashtags = false, $do_encode = true,
						$md_key = array( 'schema_desc', 'seo_desc', 'og_desc' ) );

				$img_html = "\n" . '<!-- ' . $this->p->lca . ' schema image for pinterest pin it button -->' . "\n";
				$img_html .= '<div class="' . $this->p->lca . '-schema-image-for-pinterest" style="display:none;">' . "\n";
				$img_html .= '<img src="' . SucomUtil::esc_url_encode( $image_url ) . '" width="0" height="0" style="width:0;height:0;" ' . 
					'data-pin-description="' . esc_attr( $data_pin_desc ) . '" alt=""/>' . "\n"; 	// Empty alt required for w3c validation.
				$img_html .= '</div><!-- .' . $this->p->lca . '-schema-image-for-pinterest -->' . "\n\n";

				$content = $img_html . $content;
			}

			return $content;
		}
	}
}
