<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegUtilElementor' ) ) {

	class WpssoIntegUtilElementor {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_filter( 'sucom_get_post_types', array( $this, 'exclude_post_types' ), 10, 3 );

			add_action( 'elementor/editor/after_save', array( $this, 'refresh_post_cache' ), 1000, 2 );

			$this->p->util->add_plugin_filters( $this, array(
				'content_videos' => 2,
			), $prio = 110 );
		}

		/*
		 * Elementor incorrectly registers the 'elementor_library' post type as 'public' = true and 'show_ui' = true, so we
		 * need to remove it from the list of public post types.
		 */
		public function exclude_post_types( $post_types, $output, $args ) {

			if ( 'objects' === $output ) {

				foreach ( $post_types as $num => $obj ) {

					if ( 'elementor_library' === $obj->name ) {

						unset( $post_types[ $num ] );
					}
				}

			} else unset( $post_types[ 'elementor_library' ] );

			return $post_types;
		}

		public function refresh_post_cache( $post_id, $editor_data ) {

			$this->p->post->refresh_cache( $post_id );	// Refresh the cache for a single post ID.
		}

		public function filter_content_videos( $videos, $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Elementor widget for videos.
			 *
			 * Example:
			 *
			 * 	<div class="elementor-element elementor-element-5c62c7a elementor-aspect-ratio-169 elementor-widget elementor-widget-video" data-id="5c62c7a" data-element_type="widget" data-settings="{"youtube_url":"https:\/\/www.youtube.com\/watch?v=vfeYTg4POxw","modestbranding":"yes","yt_privacy":"yes","video_type":"youtube","controls":"yes","aspect_ratio":"169"}" data-widget_type="video.default">
			 */
			if ( preg_match_all( '/<(div)[^<>]*? class=[\'"][^\'"]*(elementor-widget-video)[^\'"]*[\'"][^<>]* data-settings=[\'"]([^ ]+)[\'"][^<>]*>/i',
				$content, $all_matches, PREG_SET_ORDER ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' <div/> elementor widget video tag(s) found' );
				}

				foreach ( $all_matches as $match_num => $media ) {

					$media[ 3 ] = html_entity_decode( $media[ 3 ] );	// Just in case.

					$json_decoded = json_decode( $media[ 3 ], $assoc = true );

					if ( ! empty( $json_decoded[ 'video_type' ] ) ) {	// Example: 'youtube'.

						$video_type = $json_decoded[ 'video_type' ];

						if ( ! empty( $json_decoded[ $video_type . '_url' ] ) ) {	// Example: 'youtube_url'.

							$videos[] = array(
								'url' => $json_decoded[ $video_type . '_url' ],
							);
						}
					}
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <div/> elementor widget video tag(s) found' );
			}

			return $videos;
		}
	}
}
