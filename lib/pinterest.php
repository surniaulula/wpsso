<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
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

		/*
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

			if ( empty( $this->p->options[ 'pin_add_img_html' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'pinterest hidden image is disabled' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'pinterest hidden image is enabled' );
				}

				$this->p->util->add_plugin_filters( $this, array(
					'plugin_image_sizes' => 1,
				) );

				/*
				 * Filters the post content.
				 *
				 * See https://developer.wordpress.org/reference/hooks/the_content/.
				 */
				add_filter( 'the_content', array( $this, 'prepend_image_html' ), PHP_INT_MAX );

				/*
				 * Filters the author description, post type archive description, and the term description.
				 *
				 * See https://developer.wordpress.org/reference/functions/get_the_archive_description/.
				 */
				add_filter( 'get_the_archive_description', array( $this, 'prepend_image_html' ), PHP_INT_MAX );
			}
		}

		public function allow_img_data_attributes() {

			global $allowedposttags;

			if ( ! empty( $this->p->options[ 'pin_add_nopin_media_img_tag' ] ) ) {

				$allowedposttags[ 'img' ][ 'data-pin-nopin' ] = true;
			}
		}

		/*
		 * $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment );
		 */
		public function add_attachment_image_attributes( $attr, $attach ) {

			if ( ! empty( $this->p->options[ 'pin_add_nopin_media_img_tag' ] ) ) {

				$attr[ 'data-pin-nopin' ] = 'nopin';
			}

			return $attr;
		}

		/*
		 * $html = apply_filters( 'get_header_image_tag', $html, $header, $attr );
		 */
		public function get_header_image_tag( $html, $header, $attr ) {

			if ( ! empty( $this->p->options[ 'pin_add_nopin_header_img_tag' ] ) ) {

				$html = SucomUtil::insert_html_tag_attributes( $html, array( 'data-pin-nopin' => 'nopin' ) );
			}

			return $html;
		}

		/*
		 * $html = apply_filters( 'get_avatar', $html, $id_or_email, $size_px, $default_type, $alt, $data_args );
		 */
		public function get_avatar_image_tag( $html, $id_or_email, $size_px, $default_type, $alt, $data_args ) {

			if ( ! empty( $this->p->options[ 'pin_add_nopin_header_img_tag' ] ) ) {

				$html = SucomUtil::insert_html_tag_attributes( $html, array( 'data-pin-nopin' => 'nopin' ) );
			}

			return $html;
		}

		/*
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

		/*
		 * Hooked to the 'woocommerce_archive_description' action.
		 */
		public function show_image_html() {

			echo $this->prepend_image_html();
		}

		/*
		 * Hooked to 'the_content' and 'get_the_archive_description' filters.
		 */
		public function prepend_image_html( $content = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Do not add a Pinterest image if the current webpage is amp, an rss feed, we're in the loop, or WPSSO is
			 * applying the content filter to create a Schema or meta tag description value.
			 */
			if ( SucomUtilWP::is_amp() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: is amp' );
				}

				return $content;	// Stop here.

			} elseif ( is_feed() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: is feed' );
				}

				return $content;	// Stop here.

			/*
			 * Do not add a Pinterest image for individual posts within an archive page.
			 *
			 * Note that in_the_loop() can be true in both archive and singular pages.
			 */
			} elseif ( is_archive() && in_the_loop() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: archive page and in the loop' );
				}

				return $content;	// Stop here.

			} elseif ( ! empty( $GLOBALS[ 'wpsso_doing_filter_the_content' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: doing the content filter' );
				}

				return $content;	// Stop here.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
			}

			/*
			 * Note that in_the_loop() can be true in both archive and singular pages.
			 */
			$use_post = apply_filters( 'wpsso_use_post', in_the_loop() ? true : false );

			$mod = $this->p->page->get_mod( $use_post );	// $use_post is true by default.

			$image_html = $this->get_mod_image_html( $mod );

			return $image_html . $content;
		}

		/*
		 * See WpssoPinterest->prepend_image_html().
		 */
		public function get_mod_image_html( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$image_html = '';

			if ( empty( $mod[ 'name' ] ) || empty( $mod[ 'id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: mod name or id is empty' );
				}

				return $image_html;	// Stop here.
			}

			/*
			 * Note that the sort order, page number, locale, amp and embed checks are provided by
			 * WpssoHead->get_head_cache_index() and not SucomUtil::get_mod_salt().
			 */
			$cache_salt = SucomUtil::get_mod_salt( $mod );

			static $do_once = array();	// Just in case - prevent recursion and duplicate CSS IDs in the webpage.

			if ( ! empty( $do_once[ $cache_salt ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: already added for ' . $cache_salt );
				}

				return $image_html;	// Stop here.
			}

			$do_once[ $cache_salt ] = true;	// Set early to prevent recursion.

			$size_name = 'wpsso-pinterest';
			$mt_images = $this->p->media->get_all_images( $num = 1, $size_name, $mod, $md_pre = array( 'pin', 'schema', 'og' ) );
			$image_url = SucomUtil::get_first_mt_media_url( $mt_images );
			$css_id    = 'pin-it-' . SucomUtil::sanitize_css_id( $cache_salt );

			/*
			 * Avoid newline characters and HTML comments as they can be wrapped in paragraph tags by some WordPress filters.
			 */
			$image_html = '<div class="wpsso-pinterest-image" id="' . $css_id . '" style="width:0;height:0;display:none !important;">';

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

				/*
				 * Note that an empty alt attribute is required for W3C validation.
				 *
				 * Adding a 'loading="lazy"' attribute breaks the Pinterest Save button.
				 *
				 * The 'skip-lazy' class is used by WP Rocket to skip the lazy loading of an image.
				 */
				$image_html .= '<img src="' . SucomUtil::esc_url_encode( $image_url ) . '" ' .
					'width="0" height="0" class="skip-lazy" style="width:0;height:0;" alt="" ' .
					'data-pin-description="' . esc_attr( $data_pin_desc ) . '" />';
			}

			$image_html .= '</div>';

			return $image_html;
		}
	}
}
