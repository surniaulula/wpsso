<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoPinterest' ) ) {

	class WpssoPinterest {

		private $p;

		/**
		 * Note that options from the WPSSO Core setting pages and Document SSO metabox use a "p" option prefix.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$max_int = SucomUtil::get_max_int();

			add_action( 'init', array( $this, 'allow_img_data_attributes' ) );

			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_attachment_image_attributes' ), 10, 2 );
			add_filter( 'get_header_image_tag', array( $this, 'get_header_image_tag' ), 10, 3 );
			add_filter( 'get_avatar', array( $this, 'get_avatar_image_tag' ), 10, 5 );
			add_filter( 'get_image_tag', array( $this, 'get_image_tag' ), 10, 6 );

			if ( ! empty( $this->p->options[ 'p_add_img_html' ] ) ) {

				$this->p->util->add_plugin_filters( $this, array( 
					'plugin_image_sizes' => 1,
				) );

				add_filter( 'the_content', array( $this, 'get_pinterest_img_html' ), $max_int );
			}
		}

		public function allow_img_data_attributes() {

			global $allowedposttags;

			if ( ! empty( $this->p->options[ 'p_add_nopin_media_img_tag' ] ) ) {
				$allowedposttags[ 'img' ][ 'nopin' ] = true;
			}
		}

		/**
		 * $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment );
		 */
		public function add_attachment_image_attributes( $attr, $attach ) {

			if ( ! empty( $this->p->options[ 'p_add_nopin_media_img_tag' ] ) ) {
				$attr[ 'nopin' ] = 'nopin';
			}

			return $attr;
		}

		/**
		 * $html = apply_filters( 'get_header_image_tag', $html, $header, $attr );
		 */
		public function get_header_image_tag( $html, $header, $attr ) {

			$html = SucomUtil::insert_html_tag_attributes( $html, array(
				'nopin' => empty( $this->p->options[ 'p_add_nopin_header_img_tag' ] ) ? false : 'nopin'
			) );

			return $html;
		}

		/**
		 * $html = apply_filters( 'get_avatar', $html, $id_or_email, $size_px, $default_type, $alt );
		 *
		 * Since WP v4.2.0:
		 *
		 * $html = apply_filters( 'get_avatar', $html, $id_or_email, $size_px, $default_type, $alt, $data_args );
		 */
		public function get_avatar_image_tag( $html, $id_or_email, $size_px, $default_type, $alt ) {

			$html = SucomUtil::insert_html_tag_attributes( $html, array(
				'nopin' => empty( $this->p->options[ 'p_add_nopin_header_img_tag' ] ) ? false : 'nopin'
			) );

			return $html;
		}

		/**
		 * $html = apply_filters( 'get_image_tag', $html, $id, $alt, $title, $align, $size );
		 */
		public function get_image_tag( $html, $id, $alt, $title, $align, $size ) {

			$html = SucomUtil::insert_html_tag_attributes( $html, array(
				'nopin' => empty( $this->p->options[ 'p_add_nopin_media_img_tag' ] ) ? false : 'nopin'
			) );

			return $html;
		}

		public function filter_plugin_image_sizes( $sizes ) {

			if ( ! empty( $this->p->options[ 'p_add_img_html' ] ) ) {

				$sizes[ 'p' ] = array(	// Option prefix.
					'name'  => 'pinterest',
					'label' => _x( 'Pinterest Pin It Image', 'image size label', 'wpsso' ),
				);
			}

			return $sizes;
		}

		public function show_pinterest_img_html() {

			echo $this->get_pinterest_img_html();
		}

		public function get_pinterest_img_html( $content = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Do not add the pinterest image if the current webpage is amp or rss feed.
			 */
			if ( SucomUtil::is_amp() || is_feed() ) {
				
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: is amp or feed' );
				}

				return $content;	// Stop here.
			}

			/**
			 * Check if the content filter is being applied to create a description text.
			 */
			if ( ! empty( $GLOBALS[ $this->p->lca . '_doing_filter_the_content' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: doing the content filter' );
				}

				return $content;	// Stop here.
			}

			static $local_recursion = array();			// Use a static variable to prevent recursion.

			$use_post = in_the_loop() ? true : false;		// Use the $post object inside the loop.

			$use_post = apply_filters( $this->p->lca . '_use_post', $use_post );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'required call to get_page_mod()' );
			}

			$mod = $this->p->util->get_page_mod( $use_post );	// $use_post is true by default.

			$cache_salt = SucomUtil::get_mod_salt( $mod );

			if ( ! empty( $local_recursion[ $cache_salt ] ) ) {		// Check for recursion.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: content filter recursion detected' );
				}

				return $content;	// Stop here.

			}

			$local_recursion[ $cache_salt ] = true;

			$size_name = $this->p->lca . '-pinterest';

			$og_images = $this->p->og->get_all_images( 1, $size_name, $mod, false, $md_pre = array( 'p', 'schema', 'og' ) );

			$image_url = SucomUtil::get_mt_media_url( $og_images );

			$image_html = '<!-- ' . $this->p->lca . ' pinterest pin it image added on ' . date( 'c' ) . ' -->' . "\n";

			$image_html .= '<div class="' . $this->p->lca . '-pinterest-pin-it-image" style="display:none !important;">' . "\n";

			if ( empty( $image_url ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'image url for pinterest is empty' );
				}

			} else {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding image url for pinterest = ' . $image_url );
				}

				$data_pin_desc = $this->p->page->get_description( $this->p->options[ 'p_img_desc_max_len' ],
					$dots = '...', $mod, $read_cache = true, $add_hashtags = true, $do_encode = true,
						$md_key = array( 'p_img_desc', 'og_desc' ) );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'pinterest data pin description = ' . $data_pin_desc );
				}

				/**
				 * Note that an empty alt attribute is required for W3C validation. Also note that adding a
				 * 'loading="lazy"' attribute breaks the Pinterest Save button.
				 */
				$image_html .= "\t" . '<img src="' . SucomUtil::esc_url_encode( $image_url ) . '" ' .
					'width="0" height="0" style="width:0;height:0;" alt="" ' . 
					'data-pin-description="' . esc_attr( $data_pin_desc ) . '" />' . "\n";
			}

			$image_html .= '</div><!-- .' . $this->p->lca . '-pinterest-pin-it-image -->' . "\n\n";

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'done' );
			}

			unset( $local_recursion[ $cache_salt ] );

			return $image_html . $content;
		}
	}
}
