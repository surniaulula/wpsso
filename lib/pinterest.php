<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoPinterest' ) ) {

	class WpssoPinterest {

		private $p;	// Wpsso class object.

		/**
		 * Note that options from the WPSSO Core setting pages and Document SSO metabox use a "p" option prefix.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'init', array( $this, 'allow_img_data_attributes' ) );

			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_attachment_image_attributes' ), 10, 2 );
			add_filter( 'get_header_image_tag', array( $this, 'get_header_image_tag' ), 10, 3 );
			add_filter( 'get_avatar', array( $this, 'get_avatar_image_tag' ), 10, 6 );
			add_filter( 'get_image_tag', array( $this, 'get_image_tag' ), 10, 6 );

			if ( ! empty( $this->p->options[ 'pin_add_img_html' ] ) ) {

				$this->p->util->add_plugin_filters( $this, array(
					'plugin_image_sizes' => 1,
				) );

				add_filter( 'the_content', array( $this, 'get_pinterest_img_html' ), PHP_INT_MAX );
			}
		}

		public function allow_img_data_attributes() {

			global $allowedposttags;

			if ( ! empty( $this->p->options[ 'pin_add_nopin_media_img_tag' ] ) ) {

				$allowedposttags[ 'img' ][ 'data-pin-nopin' ] = true;
			}
		}

		/**
		 * $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment );
		 */
		public function add_attachment_image_attributes( $attr, $attach ) {

			if ( ! empty( $this->p->options[ 'pin_add_nopin_media_img_tag' ] ) ) {

				$attr[ 'data-pin-nopin' ] = 'nopin';
			}

			return $attr;
		}

		/**
		 * $html = apply_filters( 'get_header_image_tag', $html, $header, $attr );
		 */
		public function get_header_image_tag( $html, $header, $attr ) {

			if ( ! empty( $this->p->options[ 'pin_add_nopin_header_img_tag' ] ) ) {

				$html = SucomUtil::insert_html_tag_attributes( $html, array( 'data-pin-nopin' => 'nopin' ) );
			}

			return $html;
		}

		/**
		 * $html = apply_filters( 'get_avatar', $html, $id_or_email, $size_px, $default_type, $alt, $data_args );
		 */
		public function get_avatar_image_tag( $html, $id_or_email, $size_px, $default_type, $alt, $data_args ) {

			if ( ! empty( $this->p->options[ 'pin_add_nopin_header_img_tag' ] ) ) {

				$html = SucomUtil::insert_html_tag_attributes( $html, array( 'data-pin-nopin' => 'nopin' ) );
			}

			return $html;
		}

		/**
		 * $html = apply_filters( 'get_image_tag', $html, $id, $alt, $title, $align, $size );
		 */
		public function get_image_tag( $html, $id, $alt, $title, $align, $size ) {

			if ( ! empty( $this->p->options[ 'pin_add_nopin_media_img_tag' ] ) ) {

				$html = SucomUtil::insert_html_tag_attributes( $html, array( 'data-pin-nopin' => 'nopin' ) );
			}

			return $html;
		}

		public function filter_plugin_image_sizes( array $sizes ) {

			if ( ! empty( $this->p->options[ 'pin_add_img_html' ] ) ) {	// Just in case.

				$sizes[ 'pin' ] = array(	// Option prefix.
					'name'         => 'pinterest',
					'label_transl' => _x( 'Pinterest Pin It', 'option label', 'wpsso' ),
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
			if ( ! empty( $GLOBALS[ 'wpsso_doing_filter_the_content' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: doing the content filter' );
				}

				return $content;	// Stop here.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
			}

			$use_post = apply_filters( 'wpsso_use_post', in_the_loop() ? true : false );

			$mod = $this->p->page->get_mod( $use_post );	// $use_post is true by default.

			$cache_salt = SucomUtil::get_mod_salt( $mod );

			static $local_is_recursion = array();

			if ( ! empty( $local_is_recursion[ $cache_salt ] ) ) {

				return $content;
			}

			$local_is_recursion[ $cache_salt ] = true;

			$size_name = 'wpsso-pinterest';

			$mt_images = $this->p->media->get_all_images( $num = 1, $size_name, $mod, $md_pre = array( 'pin', 'schema', 'og' ) );

			$image_url = SucomUtil::get_first_mt_media_url( $mt_images );

			$image_html = '<div class="wpsso-pinterest-pin-it-image" style="display:none !important;">' . "\n";

			if ( empty( $image_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'image URL for pinterest is empty' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding image URL for pinterest = ' . $image_url );
				}

				$data_pin_desc = $this->p->page->get_description( $mod, $md_key = 'pin_img_desc', $max_len = 'pin_img_desc', $num_hashtags = true );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'pinterest data pin description = ' . $data_pin_desc );
				}

				/**
				 * Note that an empty alt attribute is required for W3C validation.
				 *
				 * Adding a 'loading="lazy"' attribute breaks the Pinterest Save button.
				 *
				 * The 'skip-lazy' class is used by WP Rocket to skip the lazy loading of an image.
				 */
				$image_html .= "\t" . '<img src="' . SucomUtil::esc_url_encode( $image_url ) . '" ' .
					'width="0" height="0" class="skip-lazy" style="width:0;height:0;" alt="" ' .
					'data-pin-description="' . esc_attr( $data_pin_desc ) . '" />' . "\n";
			}

			$image_html .= '</div><!-- .wpsso-pinterest-pin-it-image -->' . "\n\n";

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'done' );
			}

			unset( $local_is_recursion[ $cache_salt ] );

			return $image_html . $content;
		}
	}
}
