<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoOembed' ) ) {

	class WpssoOembed {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtilWP::oembed_enabled() ) {	// Nothing to do.

				return;
			}

			/**
			 * Replace the WordPress theme-compat/embed.php and any theme embed.php template with our own.
			 */
			add_filter( 'template_include', array( $this, 'template_include_embed' ), 10000, 1 );

			/**
			 * The WordPress locate_template() and load_template() functions are not filtered and only check the
			 * STYLESHEETPATH, TEMPLATEPATH, and WordPress theme-compat folders, so preempt their use in
			 * get_template_part() by hooking the "get_template_part_{$slug}" action to require our own embed template
			 * part(s).
			 */
			add_action( 'get_template_part_wpsso/embed', array( $this, 'template_part_embed' ), 10, 3 );

			/**
			 * Filters that receive a $post object.
			 */
			add_filter( 'oembed_response_data', array( $this, 'post_oembed_response_data' ), 10000, 4 );
			add_filter( 'oembed_response_data', array( $this, 'post_oembed_response_data_rich' ), 11000, 4 );
			add_filter( 'post_embed_url', array( $this, 'post_embed_url' ), 10000, 2 );
			add_filter( 'embed_html', array( $this, 'post_embed_html' ), 10000, 4 );

			/**
			 * Filters that are called in the loop.
			 */
			add_filter( 'embed_thumbnail_url', array( $this, 'the_embed_thumbnail_url' ), 10000, 1 );
			add_filter( 'embed_thumbnail_url_image_shape', array( $this, 'the_embed_thumbnail_url_image_shape' ), 10000, 2 );

			add_filter( 'embed_thumbnail_id', array( $this, 'the_embed_thumbnail_id' ), 10000, 1 );
			add_filter( 'embed_thumbnail_image_size', array( $this, 'the_embed_thumbnail_image_size' ), 10000, 2 );
			add_filter( 'embed_thumbnail_image_shape', array( $this, 'the_embed_thumbnail_image_shape' ), 10000, 2 );

			add_filter( 'the_excerpt_embed', array( $this, 'the_embed_excerpt' ), 10000, 1 );
			add_filter( 'embed_site_title_html', array( $this, 'the_embed_site_title_html' ), 10000, 1 );
		}

		/**
		 * Replace the WordPress theme-compat/embed.php and any theme embed.php template with our own.
		 */
		public function template_include_embed( $template ) {

			if ( false !== strpos( $template, '/embed.php' ) ) {

				$template = preg_replace( '/^.*\/(embed\.php)$/', WPSSO_PLUGINDIR . 'lib/theme-compat/$1', $template );
			}

			return $template;
		}

		/**
		 * The WordPress locate_template() and load_template() functions are not filtered and only check the
		 * STYLESHEETPATH, TEMPLATEPATH, and WordPress theme-compat folders, so preempt their use in get_template_part() by
		 * hooking the "get_template_part_{$slug}" action to require our own embed template part(s).
		 *
		 * Example:
		 *
		 *	$slug = 'wpsso/embed'
		 *	$name = 'content'
		 */
		public function template_part_embed( $slug, $name, $args ) {

			if ( 0 === strpos( $slug, 'wpsso/' ) ) {	// Just in case.

				$template = preg_replace( '/^wpsso\/(.*)/', WPSSO_PLUGINDIR . 'lib/theme-compat/$1-' . $name . '.php', $slug );

				if ( file_exists( $template ) ) {	// Just in case.

					require $template;
				}
			}
		}

		/**
		 * Filters the oEmbed response data.
		 *
		 * $data = array(
		 *	'version'       => '1.0',
		 *	'provider_name' => get_bloginfo( 'name' ),
		 *	'provider_url'  => get_home_url(),
		 *	'author_name'   => get_bloginfo( 'name' ),
		 *	'author_url'    => get_home_url(),
		 *	'title'         => get_the_title( $post ),
		 *	'type'          => 'link',
		 * );
		 *
		 * $author = get_userdata( $post->post_author );
		 *
		 * if ( $author ) {
		 *
		 * 	$data[ 'author_name' ] = $author->display_name;
		 *	$data[ 'author_url' ]  = get_author_posts_url( $author->ID );
		 * }
		 */
		public function post_oembed_response_data( $data, $post, $width, $height ) {

			if ( ! empty( $post->ID ) ) {	// Just in case.

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a local cache.

				if ( ! empty( $head_info[ 'og:title' ] ) ) {

					$data[ 'title' ] = $head_info[ 'og:title' ];
				}
			}

			return $data;
		}

		/**
		 * Filters the oEmbed response data to return an iframe embed code.
		 *
		 * $data[ 'width' ]            = absint( $width );
		 * $data[ 'height' ]           = absint( $height );
		 * $data[ 'type' ]             = 'rich';
		 * $data[ 'html' ]             = get_post_embed_html( $width, $height, $post );
		 * $data[ 'thumbnail_url' ]    = $thumbnail_url;
		 * $data[ 'thumbnail_width' ]  = $thumbnail_width;
		 * $data[ 'thumbnail_height' ] = $thumbnail_height;
		 */
		public function post_oembed_response_data_rich( $data, $post, $width, $height ) {

			if ( ! empty( $post->ID ) ) {	// Just in case.

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a local cache.

				if ( isset( $head_info[ 'og:image:width' ] ) && $head_info[ 'og:image:width' ] > 0 &&
					isset( $head_info[ 'og:image:height' ] ) && $head_info[ 'og:image:height' ] > 0 ) {

					if ( $image_url = SucomUtil::get_first_mt_media_url( $head_info ) ) {

						$data[ 'thumbnail_url' ]    = $image_url;
						$data[ 'thumbnail_width' ]  = $head_info[ 'og:image:width' ];
						$data[ 'thumbnail_height' ] = $head_info[ 'og:image:height' ];
					}
				}
			}

			return $data;
		}

		public function post_embed_url( $embed_url, $post ) {

			return $embed_url;
		}

		public function post_embed_html( $output, $post, $width, $height ) {

			return $output;
		}

		/**
		 * Filters the thumbnail image URL for use in the embed template.
		 */
		public function the_embed_thumbnail_url( $thumbnail_url ) {

			global $post;

			if ( ! empty( $post->ID ) ) {	// Just in case.

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a local cache.

				if ( $image_url = SucomUtil::get_first_mt_media_url( $head_info ) ) {

					$thumbnail_url = $image_url;
				}
			}

			return $thumbnail_url;
		}

		public function the_embed_thumbnail_url_image_shape( $shape, $thumbnail_url ) {

			global $post;

			if ( ! empty( $post->ID ) ) {	// Just in case.

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a local cache.

				if ( ! empty( $head_info[ 'og:image:width' ] ) && ! empty( $head_info[ 'og:image:height' ] ) ) {

					if ( $head_info[ 'og:image:width' ] > $head_info[ 'og:image:height' ] ) {

						$shape = 'rectangular';

					} else {

						$shape = 'square';
					}
				}
			}

			return $shape;
		}

		/**
		 * Filters the thumbnail image ID for use in the embed template.
		 */
		public function the_embed_thumbnail_id( $pid ) {

			global $post;

			if ( ! empty( $post->ID ) ) {	// Just in case.

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a local cache.

				if ( ! empty( $head_info[ 'og:image:id' ] ) ) {

					$pid = $head_info[ 'og:image:id' ];
				}
			}

			return $pid;
		}

		/**
		 * Filters the thumbnail image size for use in the embed template.
		 */
		public function the_embed_thumbnail_image_size( $size_name, $pid ) {

			$size_name = 'wpsso-opengraph';

			return $size_name;
		}

		/**
		 * Filters the thumbnail shape for use in the embed template.
		 *
		 * The 'rectangular' shape puts the image above the title (like Facebook) and the 'square' shape puts the image
		 * below the title.
		 */
		public function the_embed_thumbnail_image_shape( $shape, $pid ) {

			$shape = 'rectangular';

			return $shape;
		}

		/**
		 * Filters the post excerpt for the embed template.
		 *
		 * $excerpt = get_the_excerpt();
		 */
		public function the_embed_excerpt( $excerpt ) {

			global $post;

			if ( ! empty( $post->ID ) ) {	// Just in case.

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a local cache.

				if ( ! empty( $head_info[ 'og:description' ] ) ) {

					$excerpt = $head_info[ 'og:description' ];
				}
			}

			return $excerpt;
		}

		public function the_embed_site_title_html( $site_title ) {

			return $site_title;
		}
	}
}
