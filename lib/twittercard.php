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

if ( ! class_exists( 'WpssoTwitterCard' ) ) {

	class WpssoTwitterCard {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'plugin_image_sizes' => 1,
			) );
		}

		public function filter_plugin_image_sizes( array $sizes ) {

			$sizes[ 'tc_sum' ] = array(	// Option prefix.
				'name'         => 'tc-summary',
				'label_transl' => _x( 'Twitter Summary Card', 'option label', 'wpsso' ),
			);

			$sizes[ 'tc_lrg' ] = array(	// Option prefix.
				'name'         => 'tc-lrgimg',
				'label_transl' => _x( 'Twitter Summary Card Large Image', 'option label', 'wpsso' ),
			);

			return $sizes;
		}

		/**
		 * Use reference for $mt_og argument to allow unset of existing twitter meta tags.
		 */
		public function get_array( array $mod, array $mt_og = array(), $author_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mt_tc = (array) apply_filters( 'wpsso_tc_seed', array(), $mod );

			/**
			 * The twitter:domain is used in place of the 'view on web' text.
			 */
			if ( ! isset( $mt_tc[ 'twitter:domain' ] ) && ! empty( $mt_og[ 'og:url' ] ) ) {

				$mt_tc[ 'twitter:domain' ] = preg_replace( '/^.*\/\/([^\/]+).*$/', '$1', $mt_og[ 'og:url' ] );
			}

			if ( ! isset( $mt_tc[ 'twitter:site' ] ) ) {

				$mt_tc[ 'twitter:site' ] = SucomUtil::get_key_value( 'tc_site', $this->p->options, $mod );
			}

			if ( ! isset( $mt_tc[ 'twitter:title' ] ) ) {

				$mt_tc[ 'twitter:title' ] = $this->p->page->get_title( $mod, $md_key = 'tc_title', $max_len = 'tc_title' );
			}

			if ( ! isset( $mt_tc[ 'twitter:description' ] ) ) {

				$mt_tc[ 'twitter:description' ] = $this->p->page->get_description( $mod, $md_key = 'tc_desc', $max_len = 'tc_desc', $num_hashtags = true );
			}

			if ( ! isset( $mt_tc[ 'twitter:creator' ] ) ) {

				if ( $author_id ) {

					$mt_tc[ 'twitter:creator' ] = get_the_author_meta( $this->p->options[ 'plugin_cm_twitter_name' ], $author_id );

					$mt_tc[ 'twitter:creator' ] = SucomUtil::sanitize_twitter_name( $mt_tc[ 'twitter:creator' ] );	// Just in case.
				}
			}

			/**
			 * Player video card.
			 */
			$this->maybe_add_player_card( $mt_tc, $mod, $mt_og, $author_id );

			/**
			 * Post summary or large image summary card.
			 */
			$this->maybe_add_post_card( $mt_tc, $mod, $mt_og, $author_id );

			/**
			 * Default card.
			 */
			$this->maybe_add_default_card( $mt_tc, $mod, $mt_og, $author_id );

			/**
			 * Additional article and product data.
			 */
			$this->maybe_add_extra_data( $mt_tc, $mod, $mt_og, $author_id );

			return (array) apply_filters( 'wpsso_tc', $mt_tc, $mod );
		}

		/**
		 * $mixed = 'singular' | 'default' | $mod.
		 *
		 * Example return:
		 *
		 *	array(
		 *		'summary_large_image',
		 *		'Twitter Summary Card Large Image',
		 *		'wpsso-tc-lrgimg',
		 *		'tc_lrg',
		 *	)
		 */
		public function get_card_info( $mixed, array $head_tags = array(), $use_md_opts = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$card_type  = 'summary';
			$size_label = '';
			$size_name  = 'thumbnail';	// Just in case.
			$md_pre     = '';

			if ( ! empty( $head_tags[ 'twitter:card' ] ) ) {

				$card_type = $head_tags[ 'twitter:card' ];

			} elseif ( is_string( $mixed ) ) {	// $mixed is 'singular' or 'default'.

				if ( ! empty( $this->p->options[ 'tc_type_' . $mixed ] ) ) {

					$card_type = $this->p->options[ 'tc_type_' . $mixed ];
				}

			} elseif ( is_array( $mixed ) ) {	// $mixed is $mod.

				/**
				 * Get the default card type for this post type.
				 */
				if ( ! empty( $mixed[ 'is_post' ] ) ) {

					if ( ! empty( $mixed[ 'post_type' ] ) && ! empty( $this->p->options[ 'tc_type_for_' . $mixed[ 'post_type' ] ] ) ) {

						$card_type = $this->p->options[ 'tc_type_for_' . $mixed[ 'post_type' ] ];

					} elseif ( ! empty( $this->p->options[ 'tc_type_singular' ] ) ) {

						$card_type = $this->p->options[ 'tc_type_singular' ];
					}
				}

				/**
				 * Maybe override the default with a custom card type.
				 */
				if ( ! empty( $mixed[ 'obj' ] ) && $use_md_opts ) {

					$md_card_type = $mixed[ 'obj' ]->get_options( $mixed[ 'id' ], 'tc_type' );	// Returns null if index key not found.

					if ( ! empty( $md_card_type ) ) {

						$card_type = $md_card_type;
					}
				}
			}

			switch ( $card_type ) {

				case 'app':

					$card_label = _x( 'Twitter App Card', 'metabox title', 'wpsso' );
					$size_name  = '';
					$md_pre     = 'tc_app';

					break;

				case 'player':

					$card_label = _x( 'Twitter Player Card', 'metabox title', 'wpsso' );
					$size_name  = '';
					$md_pre     = 'tc_play';

					break;

				case 'summary':

					$card_label = _x( 'Twitter Summary Card', 'metabox title', 'wpsso' );
					$size_name  = 'wpsso-tc-summary';
					$md_pre     = 'tc_sum';

					break;

				case 'summary_large_image':

					$card_label = _x( 'Twitter Summary Card Large Image', 'metabox title', 'wpsso' );
					$size_name  = 'wpsso-tc-lrgimg';
					$md_pre     = 'tc_lrg';

					break;
			}

			/**
			 * Example return:
			 *
			 *	array(
			 *		'summary_large_image',
			 *		'Twitter Summary Card Large Image',
			 *		'wpsso-tc-lrgimg',
			 *		'tc_lrg',
			 *	)
			 */
			$card_info = array( $card_type, $card_label, $size_name, $md_pre );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'card_info', $card_info );
			}

			return $card_info;
		}

		/**
		 * Player video card.
		 *
		 * The twitter:player:stream meta tags are used for self-hosted MP4 videos. The videos provided by
		 * YouTube, Vimeo, Wistia, etc. are application/x-shockwave-flash or text/html.
		 *
		 * twitter:player:stream
		 * 	This is a URL to the video file itself (not a video embed). The video must be an mp4 file. The
		 * 	supported codecs within the file are: H.264 video, Baseline Profile Level 3.0, up to 640 x 480 at
		 * 	30 fps and AAC Low Complexity Profile (LC) audio. This property is optional.
		 *
		 * twitter:player:stream:content_type
		 *	The MIME type for your video file (video/mp4). This property is only required if you have set a
		 *	twitter:player:stream meta tag.
		 */
		private function maybe_add_player_card( &$mt_tc, $mod, $mt_og, $author_id ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( isset( $mt_tc[ 'twitter:card' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: twitter card is set' );
				}

				return;
			}

			if ( empty( $mt_og[ 'og:video' ] ) || ! count( $mt_og[ 'og:video' ] ) > 1 ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no videos for player card' );
				}

				return;
			}

			foreach ( $mt_og[ 'og:video' ] as $mt_single_video ) {

				$player_embed_url  = '';
				$player_stream_url = '';

				/**
				 * Check for internal meta tag embed_url or stream_url.
				 */
				if ( ! empty( $mt_single_video[ 'og:video:embed_url' ] ) ) {

					$player_embed_url = $mt_single_video[ 'og:video:embed_url' ];

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'player card: embed url = ' . $player_embed_url );
					}
				}

				if ( ! empty( $mt_single_video[ 'og:video:stream_url' ] ) ) {

					$player_stream_url = $mt_single_video[ 'og:video:stream_url' ];

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'player card: stream url = ' . $player_stream_url );
					}
				}

				/**
				 * Check for a video mime-type meta tag.
				 */
				if ( isset( $mt_single_video[ 'og:video:type' ] ) ) {

					switch ( $mt_single_video[ 'og:video:type' ] ) {

						/**
						 * twitter:player
						 *
						 * HTTPS URL to iFrame player. This must be a HTTPS URL which does not generate active
						 * mixed content warnings in a web browser. The audio or video player must not require
						 * plugins such as Adobe Flash.
						 */
						case 'text/html':

							if ( empty( $player_embed_url ) ) {

								$player_embed_url = SucomUtil::get_first_mt_media_url( $mt_single_video, $media_pre = 'og:video' );

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'player card: ' . $mt_single_video[ 'og:video:type' ] .
										' url = ' . $player_embed_url );
								}
							}

							break;

						/**
						 * twitter:player:stream
						 */
						case 'video/mp4':

							if ( empty( $player_stream_url ) ) {

								$player_stream_url = SucomUtil::get_first_mt_media_url( $mt_single_video, $media_pre = 'og:video' );

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'player card: ' . $mt_single_video[ 'og:video:type' ] .
										' url = ' . $player_stream_url );
								}
							}

							break;

						default:

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'player card: video type "' . $mt_single_video[ 'og:video:type' ] . '" is unknown' );
							}

							break;
					}
				}

				/**
				 * Set the twitter:player meta tag value(s).
				 */
				if ( ! empty( $player_embed_url ) ) {

					$mt_tc[ 'twitter:card' ]   = 'player';
					$mt_tc[ 'twitter:player' ] = $player_embed_url;
				}

				if ( ! empty( $player_stream_url ) ) {

					$mt_tc[ 'twitter:card' ] = 'player';

					if ( empty( $mt_tc[ 'twitter:player' ] ) ) {

						$mt_tc[ 'twitter:player' ] = $player_stream_url;	// Fallback to video/mp4.
					}

					$mt_tc[ 'twitter:player:stream' ]              = $player_stream_url;
					$mt_tc[ 'twitter:player:stream:content_type' ] = $mt_single_video[ 'og:video:type' ];
				}

				/**
				 * Set twitter:player related values (player width, height, mobile apps, etc.)
				 */
				if ( ! empty( $mt_tc[ 'twitter:card' ] ) ) {

					foreach ( array(
						'og:video:width'           => 'twitter:player:width',
						'og:video:height'          => 'twitter:player:height',
						'og:video:iphone_name'     => 'twitter:app:name:iphone',
						'og:video:iphone_id'       => 'twitter:app:id:iphone',
						'og:video:iphone_url'      => 'twitter:app:url:iphone',
						'og:video:ipad_name'       => 'twitter:app:name:ipad',
						'og:video:ipad_id'         => 'twitter:app:id:ipad',
						'og:video:ipad_url'        => 'twitter:app:url:ipad',
						'og:video:googleplay_name' => 'twitter:app:name:googleplay',
						'og:video:googleplay_id'   => 'twitter:app:id:googleplay',
						'og:video:googleplay_url'  => 'twitter:app:url:googleplay',
					) as $og_name => $tc_name ) {

						if ( ! empty( $mt_single_video[ $og_name ] ) ) {

							$mt_tc[ $tc_name ] = $mt_single_video[ $og_name ];
						}
					}

					/**
					 * Get the video preview image (if one is available).
					 */
					$mt_tc[ 'twitter:image' ] = SucomUtil::get_first_mt_media_url( $mt_single_video );

					if ( ! empty( $mt_single_video[ 'og:image:alt' ] ) ) {

						$mt_tc[ 'twitter:image:alt' ] = $mt_single_video[ 'og:image:alt' ];
					}

					/**
					 * Fallback to the open graph image.
					 */
					if ( empty( $mt_tc[ 'twitter:image' ] ) && ! empty( $mt_og[ 'og:image' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'player card: no video image - using og:image instead' );
						}

						$mt_tc[ 'twitter:image' ] = SucomUtil::get_first_mt_media_url( $mt_og[ 'og:image' ] );
					}
				}

				return;	// Use only the first video.
			}
		}

		/**
		 * Post summary or large image summary card.
		 */
		private function maybe_add_post_card( &$mt_tc, $mod, $mt_og, $author_id ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( isset( $mt_tc[ 'twitter:card' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: twitter card is set' );
				}

				return;
			}

			if ( empty( $mod[ 'is_post' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: module is not post object' );
				}

				return;
			}

			list( $card_type, $card_label, $size_name, $md_pre ) = $this->get_card_info( $mod );

			/**
			 * Post image.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $card_type . ' card: checking for post image (meta, featured, attached)' );
			}

			$mt_images = $this->p->media->get_post_images( 1, $size_name, $mod[ 'id' ], $md_pre );

			if ( count( $mt_images ) > 0 ) {

				$mt_single_image = reset( $mt_images );

				$image_url = SucomUtil::get_first_mt_media_url( $mt_single_image );

				/**
				 * 'summary_large_image' webpages cannot have the same image URLs, so add the post ID to all
				 * 'summary_large_image' images.
				 */
				if ( 'summary_large_image' === $card_type ) {

					$image_url = add_query_arg( 'p', $mod[ 'id' ], $image_url );
				}

				$mt_tc[ 'twitter:card' ]  = $card_type;
				$mt_tc[ 'twitter:image' ] = $image_url;

				if ( ! empty( $mt_single_image[ 'og:image:alt' ] ) ) {

					$mt_tc[ 'twitter:image:alt' ] = $mt_single_image[ 'og:image:alt' ];
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no post image found' );
			}
		}

		/**
		 * Default card.
		 */
		private function maybe_add_default_card( &$mt_tc, $mod, $mt_og, $author_id ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( isset( $mt_tc[ 'twitter:card' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: twitter card is set' );
				}

				return;
			}

			list( $card_type, $card_label, $size_name, $md_pre ) = $this->get_card_info( 'default' );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $card_type . ' card: using default card type' );
			}

			$mt_tc[ 'twitter:card' ] = $card_type;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $card_type . ' card: checking other images' );
			}

			$mt_images = $this->p->media->get_all_images( $num = 1, $size_name, $mod, $md_pre );

			if ( count( $mt_images ) > 0 ) {

				$mt_single_image = reset( $mt_images );

				$image_url = SucomUtil::get_first_mt_media_url( $mt_single_image );

				$mt_tc[ 'twitter:image' ] = $image_url;

				if ( ! empty( $mt_single_image[ 'og:image:alt' ] ) ) {

					$mt_tc[ 'twitter:image:alt' ] = $mt_single_image[ 'og:image:alt' ];
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no other images found' );
			}
		}

		/**
		 * Additional article and product data.
		 */
		private function maybe_add_extra_data( &$mt_tc, $mod, $mt_og, $author_id ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( isset( $mt_og[ 'og:type' ] ) ) {

				switch ( $mt_og[ 'og:type' ] ) {

					case 'article':

						if ( ! empty( $mt_og[ 'article:author:name' ] ) ) {

							$mt_tc[ 'twitter:label1' ] = __( 'Written by', 'wpsso' );

							$mt_tc[ 'twitter:data1' ] = $mt_og[ 'article:author:name' ];
						}

						if ( ! empty( $mt_og[ 'article:reading_mins' ] ) ) {

							$mt_tc[ 'twitter:label2' ] = __( 'Est. reading time', 'wpsso' );

							$mt_tc[ 'twitter:data2' ] = $this->p->page->fmt_reading_mins( $mt_og[ 'article:reading_mins' ] );
						}

						break;

					case 'product':

						if ( isset( $mt_og[ 'product:price:amount' ] ) && isset( $mt_og[ 'product:price:currency' ] ) ) {

							$mt_tc[ 'twitter:label1' ] = __( 'Price', 'wpsso' );

							$mt_tc[ 'twitter:data1' ] = $mt_og[ 'product:price:amount' ] . ' ' . $mt_og[ 'product:price:currency' ];
						}

						if ( isset( $mt_og[ 'product:availability' ] ) ) {

							$mt_name = 'product:availability';
							$map_key = $mt_og[ $mt_name ];

							/**
							 * Map 'https://schema.org/InStock' to 'in stock', for example.
							 */
							if ( ! empty( $this->p->cf[ 'head' ][ 'og_content_map' ][ $mt_name ][ $map_key ] ) ) {

								$map_key = $this->p->cf[ 'head' ][ 'og_content_map' ][ $mt_name ][ $map_key ];
							}

							$mt_tc[ 'twitter:label2' ] = __( 'Availability', 'wpsso' );

							$mt_tc[ 'twitter:data2' ] = ucwords( $map_key );
						}

						break;
				}
			}
		}
	}
}
