<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {
	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoOembed' ) ) {

	class WpssoOembed {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Filters that receive a $post object.
			 */
			add_filter( 'oembed_response_data', array( $this, 'post_oembed_response_data' ), 10000, 4 );
			add_filter( 'oembed_response_data', array( $this, 'post_oembed_response_data_rich' ), 11000, 4 );

			/**
			 * Filters that are called in the loop.
			 */
			add_filter( 'embed_thumbnail_id', array( $this, 'the_embed_thumbnail_id' ), 10000, 1 );
			add_filter( 'embed_thumbnail_image_size', array( $this, 'the_embed_thumbnail_image_size' ), 10000, 2 );
			add_filter( 'embed_thumbnail_image_shape', array( $this, 'the_embed_thumbnail_image_shape' ), 10000, 2 );
			add_filter( 'the_excerpt_embed', array( $this, 'the_embed_excerpt' ), 10000, 1 );
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
		 * 	$data[ 'author_name' ] = $author->display_name;
		 *	$data[ 'author_url' ]  = get_author_posts_url( $author->ID );
		 * }
		 */
		public function post_oembed_response_data( $data, $post, $width, $height ) {
			
			if ( ! empty( $post->ID ) ) {	// Just in case.

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a static local cache.

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

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a static local cache.

				if ( isset( $head_info[ 'og:image:width' ] ) && $head_info[ 'og:image:width' ] > 0 && 
					isset( $head_info[ 'og:image:height' ] ) && $head_info[ 'og:image:height' ] > 0 ) {

					$og_image_url = SucomUtil::get_mt_media_url( $head_info, $mt_media_pre = 'og:image' );

					if ( $og_image_url ) {
						$data[ 'thumbnail_url' ]    = $og_image_url;
						$data[ 'thumbnail_width' ]  = $head_info[ 'og:image:width' ];
						$data[ 'thumbnail_height' ] = $head_info[ 'og:image:height' ];
					}
				}
			}

			return $data;
		}

		/**
		 * Filters the thumbnail image ID for use in the embed template.
		 */
		public function the_embed_thumbnail_id( $pid ) {

			global $post;

			if ( ! empty( $post->ID ) ) {	// Just in case.

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a static local cache.

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

			$size_name = $this->p->lca . '-opengraph';

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

				$head_info = $this->p->post->get_head_info( $post->ID );	// Uses a static local cache.

				if ( ! empty( $head_info[ 'og:description' ] ) ) {
					$excerpt = $head_info[ 'og:description' ];
				}
			}

			return $excerpt;
		}
	}
}
