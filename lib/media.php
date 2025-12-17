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

if ( ! class_exists( 'WpssoMedia' ) ) {

	class WpssoMedia {

		private $p;		// Wpsso class object.
		private $filters;	// WpssoMediaFilters class object.

		private $default_content_img_preg = array(
			'html_tag' => 'img',
			'pid_attr' => 'data-[a-z]+-pid',
		);

		private static $image_src_args  = null;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			require_once WPSSO_PLUGINDIR . 'lib/media-filters.php';

			$this->filters = new WpssoMediaFilters( $plugin );

			add_action( 'init', array( $this, 'allow_img_data_attributes' ) );
			add_action( 'post-upload-ui', array( $this, 'show_post_upload_ui_message' ) );

			add_filter( 'editor_max_image_size', array( $this, 'maybe_adjust_max_image_size' ), 10, 3 );
			add_filter( 'image_make_intermediate_size', array( $this, 'maybe_update_intermediate_size_filepath' ), -5000, 1 );
			add_filter( 'wp_image_resize_identical_dimensions', array( $this, 'maybe_resize_fuzzy_dimensions' ), PHP_INT_MAX, 1 );
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_attachment_image_attributes' ), 10, 2 );
			add_filter( 'get_image_tag', array( $this, 'get_image_tag' ), 10, 6 );
		}

		public function allow_img_data_attributes() {

			global $allowedposttags;

			$allowedposttags[ 'img' ][ 'data-wp-pid' ] = true;
		}

		public function show_post_upload_ui_message() {

			$msg_transl = '<strong>' . __( 'Minimum image dimension to satisfy all %1$d image sizes: %2$dpx width by %3$dpx height.', 'wpsso' ) . '</strong>';

			list( $min_width, $min_height, $size_count ) = SucomUtilWP::get_minimum_image_wh();

			echo '<p class="minimum-image-dimensions">';

			echo sprintf( $msg_transl, $size_count, $min_width, $min_height );

			echo '</p>' . "\n";

			/*
			 * Hide the "suggested image dimensions" as they are less than the minimum we suggest here.
			 */
			echo '<style type="text/css">p.suggested-dimensions{ display:none; }</style>' . "\n";
		}

		/*
		 * Note that $size can be a string or an array().
		 */
		public function maybe_adjust_max_image_size( $max_image_size = array(), $size = '', $context = '' ) {

			/*
			 * Allow our sizes to exceed the editor width.
			 */
			if ( is_string( $size ) && 0 === strpos( $size, 'wpsso-' ) ) {

				$max_image_size = array( 0, 0 );
			}

			return $max_image_size;
		}

		/*
		 * By default, WordPress adds only the resolution of the resized image to the file name. If the image size is ours,
		 * then add crop information to the file name as well. This allows for different cropped versions for the same
		 * image resolution.
		 *
		 * Example:
		 *
		 * 	unicorn-wallpaper-1200x628.jpg
		 * 	unicorn-wallpaper-1200x628-cropped.jpg
		 *	unicorn-wallpaper-1200x628-cropped-center-top.jpg
		 */
		public function maybe_update_intermediate_size_filepath( $filepath ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * get_attachment_image_src() in the WpssoMedia class saves / sets the image information (pid, size_name,
			 * etc) before calling the image_make_intermediate_size() function (and others). Returns null if no image
			 * information was set (presumably because we arrived here without passing through our own method).
			 */
			$img_src_args = self::get_image_src_args();

			if ( empty( $img_src_args[ 'size_name' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping ' . $filepath . ': size name is empty' );
				}

				return $filepath;

			} elseif ( 0 !== strpos( $img_src_args[ 'size_name' ], 'wpsso-' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping ' . $filepath . ': size name is ' . $img_src_args[ 'size_name' ] );
				}

				return $filepath;
			}

			/*
			 * If the resized image is not cropped, then leave the file name as-is.
			 */
			$size_is_cropped = $this->p->util->is_size_cropped( $img_src_args[ 'size_name' ], $img_src_args[ 'pid' ] );

			if ( ! $size_is_cropped ) {

				return $filepath;
			}

			/*
			 * Example $size_info = Array (
			 *	[size_name]     => wpsso-opengraph,
			 *	[attachment_id] => false,
			 *	[width]         => 1200,
			 *	[height]        => 630,
			 *	[crop]          => 1,
			 *	[is_cropped]    => 1,
			 *	[dims_transl]   => 1200x630 cropped,
			 *	[label_transl]  => Open Graph (Facebook and oEmbed),
			 *	[opt_prefix]    => og,
			 * );
			 */
			$size_info = $this->p->util->get_size_info( $img_src_args[ 'size_name' ], $img_src_args[ 'pid' ] );	// Uses a local static cache.

			$new_filepath = $this->maybe_update_cropped_image_filepath( $filepath, $size_info );

			if ( $filepath !== $new_filepath ) {	// Just in case

				/*
				 * Determine if an image can be renamed or needs to be copied.
				 *
				 * Check for conflicting image sizes (ie. same dimensions uncropped, or same dimensions from other WordPress sizes).
				 */
				$can_rename = $this->can_rename_image_filename( $img_src_args[ 'size_name' ], $img_src_args[ 'pid' ] );

				if ( $can_rename ) {

					if ( rename( $filepath, $new_filepath ) ) {	// No other plugin/theme uses the same dimensions.

						return $new_filepath;	// Return the new file path on success.

					} elseif ( copy( $filepath, $new_filepath ) ) {	// If rename failed, then copy and delete.

						if ( unlink( $filepath ) ) {	// Remove the old file path.

							return $new_filepath;	// Return the new file path on success.

						} else {	// If deleting the original failed, then never mind. :)

							unlink( $new_filepath );
						}
					}

				} elseif ( copy( $filepath, $new_filepath ) ) {

					return $new_filepath;	// Return the new file path on successful copy.
				}
			}

			return $filepath;
		}

		/*
		 * Hooked to the 'wp_image_resize_identical_dimensions' filter added in WP v5.3.
		 */
		public function maybe_resize_fuzzy_dimensions( $resize ) {

			$img_src_args = self::get_image_src_args();

			if ( empty( $img_src_args[ 'size_name' ] ) || 0 !== strpos( $img_src_args[ 'size_name' ], 'wpsso-' ) ) {

				return $resize;
			}

			return true;
		}

		/*
		 * $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment );
		 */
		public function add_attachment_image_attributes( $attr, $attachment ) {

			$attr[ 'data-wp-pid' ] = $attachment->ID;

			return $attr;
		}

		/*
		 * $html = apply_filters( 'get_image_tag', $html, $id, $alt, $title, $align, $size );
		 */
		public function get_image_tag( $html, $id, $alt, $title, $align, $size ) {

			$html = SucomUtil::insert_html_tag_attributes( $html, array( 'data-wp-pid' => $id ) );

			return $html;
		}

		public function get_all_previews( $num, array $mod, $md_pre = 'og', $force_prev = false ) {

			/*
			 * The get_all_videos() method uses the 'og_vid_max' argument as part of its caching salt, so re-use the
			 * original number to get all possible videos (from its cache), then maybe limit the number of preview
			 * images if necessary.
			 */
			$max_nums = $this->p->util->get_max_nums( $mod );

			$mt_videos = $this->get_all_videos( $max_nums[ 'og_vid_max' ], $mod, $md_pre, $force_prev );

			$this->p->util->clear_uniq_urls( $uniq_context = array( 'preview' ), $mod );

			$mt_images = array();

			foreach ( $mt_videos as $num => $mt_single_video ) {

				$image_url = SucomUtil::get_first_mt_media_url( $mt_single_video );

				/*
				 * Check preview images for duplicates since the same videos may be available in different formats
				 * (application/x-shockwave-flash and text/html for example).
				 */
				if ( $image_url ) {

					if ( $this->p->util->is_uniq_url( $image_url, $uniq_context = 'preview', $mod ) ) {

						$mt_single_image = SucomUtil::preg_grep_keys( '/^og:image/', $mt_single_video );

						if ( $this->p->util->push_max( $mt_images, $mt_single_image, $num ) ) {

							return $mt_images;
						}
					}
				}

				unset( $mt_videos[ $num ] );
			}

			return $mt_images;
		}

		/*
		 * Returns an array of single video associative arrays.
		 */
		public function get_all_videos( $num, array $mod, $md_pre = 'og', $force_prev = false ) {

			$cache_salt = array(
				'num'        => $num,
				'mod'        => $mod,
				'md_pre'     => $md_pre,
				'force_prev' => $force_prev,
			);

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting all videos' );	// Begin timer.

				$this->p->debug->log_args( $cache_salt );
			}

			static $local_fifo = array();

			$cache_id = md5( SucomUtil::get_array_pretty( $cache_salt, $flatten = true ) );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'cache id = ' . $cache_id );
			}

			if ( isset( $local_fifo[ $cache_id ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning video data from local cache' );

					$this->p->debug->mark( 'getting all videos' );	// End timer.
				}

				return $local_fifo[ $cache_id ];
			}

			/*
			 * Maybe limit the number of array elements.
			 */
			$local_fifo = SucomUtil::array_slice_fifo( $local_fifo, WPSSO_CACHE_ARRAY_FIFO_MAX );

			$local_fifo[ $cache_id ] = array();

			$mt_videos =& $local_fifo[ $cache_id ];	// Set reference variable.

			$this->p->util->clear_uniq_urls( array( 'video', 'video_details' ), $mod );

			$add_vid_prev = empty( $this->p->options[ 'og_vid_prev_img' ] ) ? false : true;	// Change value from 0/1 to false/true.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( '$add_vid_prev is ' . ( $add_vid_prev ? 'true' : 'false' ) );
			}

			$num_diff = SucomUtil::array_count_diff( $mt_videos, $num );

			/*
			 * Get video information and preview enable/disable option from the post/term/user meta.
			 */
			if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

				/*
				 * Note that get_options() returns null if an index key is not found.
				 */
				if ( ( $mod_vid_prev = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'og_vid_prev_img' ) ) !== null ) {	// Returns null, 0, or 1.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( '$mod_vid_prev is ' . ( $mod_vid_prev ? 'true' : 'false' ) );
					}

					$add_vid_prev = empty( $mod_vid_prev ) ? false : true;	// Change value from 0/1 to false/true.
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'checking for videos in ' . $mod[ 'name' ] . ' options' );	// Begin timer.
				}

				/*
				 * get_og_videos() converts the $md_pre value to an array and always checks for 'og' metadata as a fallback.
				 */
				$mt_videos = array_merge( $mt_videos, $mod[ 'obj' ]->get_og_videos( $num_diff, $mod[ 'id' ], $md_pre ) );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'checking for videos in ' . $mod[ 'name' ] . ' options' );	// End timer.
				}
			}

			$num_diff = SucomUtil::array_count_diff( $mt_videos, $num );

			/*
			 * Maybe get more videos from the comment or post content.
			 */
			if ( $mod[ 'is_comment' ] || $mod[ 'is_post' ] ) {

				if ( ! $this->p->util->is_maxed( $mt_videos, $num ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'checking for videos in the ' . $mod[ 'name' ] . ' content' );	// Begin timer.
					}

					$mt_videos = array_merge( $mt_videos, $this->get_content_videos( $num_diff, $mod ) );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'checking for videos in the ' . $mod[ 'name' ] . ' content' );	// End timer.
					}
				}
			}

			$this->p->util->slice_max( $mt_videos, $num );	// Maybe trim the $mt_videos array.

			/*
			 * Maybe remove the image meta tags (aka video preview).
			 */
			if ( empty( $add_vid_prev ) && empty( $force_prev ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'removing video preview images: $add_vid_prev and $force_prev are both false' );
				}

				foreach ( $mt_videos as &$mt_single_video ) {	// Uses reference.

					$mt_single_video = SucomUtil::preg_grep_keys( '/^og:image/', $mt_single_video, $invert = true );

					$mt_single_video[ 'og:video:has_image' ] = false;
				}
			}

			/*
			 * Get custom video information from post/term/user meta data for the FIRST video.
			 *
			 * If $md_pre is 'none' (special index keyword), then don't load any custom video information.
			 *
			 * The og:video:title and og:video:description meta tags are not standard and their values will only appear in Schema markup.
			 */
			if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] && $md_pre !== 'none' ) {

				foreach ( $mt_videos as &$mt_single_video ) {	// Uses reference.

					foreach ( array(
						'og_vid_title'      => 'og:video:title',
						'og_vid_desc'       => 'og:video:description',
						'og_vid_stream_url' => 'og:video:stream_url',	// VideoObject contentUrl.
						'og_vid_width'      => 'og:video:width',
						'og_vid_height'     => 'og:video:height',
						'og_vid_upload'     => 'og:video:upload_date',
					) as $md_key => $mt_name ) {

						if ( 'og_vid_upload' === $md_key ) {

							if ( ! empty( $mt_single_video[ 'og:video:upload_date' ] ) ) {

								/*
								 * Use the existing upload date, time, and timezone as defaults.
								 */
								$value = WpssoSchema::get_mod_date_iso( $mod, $md_key, $mt_single_video[ 'og:video:upload_date' ] );

							} else $value = WpssoSchema::get_mod_date_iso( $mod, $md_key );

						} else {

							/*
							 * Note that get_options() returns null if an index key is not found.
							 */
							$value = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $md_key );
						}

						if ( ! empty( $value ) ) {	// Must be a non-empty string.

							$mt_single_video[ $mt_name ] = $value;
						}
					}

					break;	// Only do the first video.
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'creating $mt_extend array' );
			}

			$mt_extend = array();

			foreach ( $mt_videos as &$mt_single_video ) {	// Uses reference.

				if ( ! is_array( $mt_single_video ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'video ignored: $mt_single_video is not an array' );
					}

					continue;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( '' );
				}

				if ( ! empty( $mt_single_video[ 'og:video:embed_url' ] ) && 'text/html' !== $mt_single_video[ 'og:video:type' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding og:video:type text/html for ' . $mt_single_video[ 'og:video:embed_url' ] );
					}

					/*
					 * Start with a fresh copy of all og meta tags.
					 */
					$og_single_embed = SucomUtil::get_mt_video_seed( 'og', $mt_single_video, false );

					/*
					 * Use only og meta tags, excluding the facebook applink meta tags.
					 */
					$og_single_embed = SucomUtil::preg_grep_keys( '/^og:/', $og_single_embed );

					unset( $og_single_embed[ 'og:video:secure_url' ] );	// Just in case.

					$og_single_embed[ 'og:video:url' ]       = $mt_single_video[ 'og:video:embed_url' ];
					$og_single_embed[ 'og:video:has_video' ] = true;	// Used by video API modules.
					$og_single_embed[ 'og:video:type' ]      = 'text/html';

					/*
					 * Embedded videos may not have width / height information defined.
					 */
					foreach ( array( 'og:video:width', 'og:video:height' ) as $mt_name ) {

						if ( isset( $og_single_embed[ $mt_name ] ) && $og_single_embed[ $mt_name ] === '' ) {

							unset( $og_single_embed[ $mt_name ] );
						}
					}

					/*
					 * Add application/x-shockwave-flash video first and the text/html video second.
					 */
					if ( SucomUtil::get_first_mt_media_url( $mt_single_video, $mt_media_pre = 'og:video',
						$mt_suffixes = array( ':secure_url', ':url', '' ) ) ) {

						$mt_extend[] = $mt_single_video;
					}

					$mt_extend[] = $og_single_embed;

				} else $mt_extend[] = $mt_single_video;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning ' . count( $mt_extend ) . ' videos' );

				$this->p->debug->log_arr( 'mt_extend', $mt_extend );

				$this->p->debug->mark( 'getting all videos' );	// End timer.
			}

			/*
			 * Update the local static cache and return the videos array.
			 */
			return $mt_videos = $mt_extend;
		}

		/*
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 */
		public function get_thumbnail_url( $size_names, array $mod, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mt_ret = $this->get_all_images( $num = 1, $size_names, $mod, $md_pre );

			return SucomUtil::get_first_mt_media_url( $mt_ret );
		}

		/*
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 */
		public function get_all_images( $num, $size_names, array $mod, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting all images' );	// Begin timer.

				$this->p->debug->log_args( array(
					'num'        => $num,
					'size_names' => $size_names,
					'mod'        => $mod,
					'md_pre'     => $md_pre,
				) );
			}

			$mt_ret = array();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting all video preview images' );
			}

			$preview_images = $this->get_all_previews( $num, $mod, $md_pre );

			if ( empty( $preview_images ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no video preview images' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'merging video preview images' );
				}

				$mt_ret = array_merge( $mt_ret, $preview_images );
			}

			$num_diff = SucomUtil::array_count_diff( $mt_ret, $num );

			$size_names = $this->p->util->get_image_size_names( $size_names );	// Always returns an array.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'size_names', $size_names );
			}

			if ( $num_diff >= 1 ) {	// Just in case.

				$this->p->util->clear_uniq_urls( $size_names );

				foreach ( $size_names as $size_name ) {

					/*
					 * $size_name must be a string.
					 */
					$mt_images = $this->get_size_name_images( $num_diff, $size_name, $mod, $md_pre );

					if ( empty( $mt_images ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no images for size name ' . $size_name );
						}

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'merging ' . count( $mt_images ) . ' images for size name ' . $size_name );
						}

						$mt_ret = array_merge( $mt_ret, $mt_images );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting all images' );	// End timer.
			}

			return $mt_ret;
		}

		/*
		 * $size_name must be a string.
		 */
		public function get_size_name_images( $num, $size_name, array $mod, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! is_string( $size_name ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: size_name argument must be a string' );
				}

				return array();

			} elseif ( $num < 1 ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: num argument must be 1 or more' );
				}

				return array();
			}

			/*
			 * Example $size_info = Array (
			 *	[size_name]     => wpsso-opengraph,
			 *	[attachment_id] => false,
			 *	[width]         => 1200,
			 *	[height]        => 630,
			 *	[crop]          => 1,
			 *	[is_cropped]    => 1,
			 *	[dims_transl]   => 1200x630 cropped,
			 *	[label_transl]  => Open Graph (Facebook and oEmbed),
			 *	[opt_prefix]    => og,
			 * );
			 */
			$size_info = $this->p->util->get_size_info( $size_name );

			if ( empty( $size_info[ 'width' ] ) && empty( $size_info[ 'height' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: missing size information for ' . $size_name );
				}

				return array();
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting ' . $num . ' images for size name ' . $size_name );
			}

			$mt_ret = array();

			if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Comment, post, term, or user.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting ' . $mod[ 'name' ] . ' images' );
				}

				if ( $mod[ 'is_post' ] ) {

					if ( $mod[ 'is_attachment' ] && wp_attachment_is( 'image', $mod[ 'id' ] ) ) {

						/*
						 * $size_name must be a string.
						 */
						$mt_single_image = $this->get_attachment_image( $num, $size_name, $mod[ 'id' ] );

						if ( empty( $mt_single_image ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'exiting early: no attachment image' );
							}

							return $mt_ret;	// Stop here.
						}

						$mt_ret = array_merge( $mt_ret, $mt_single_image );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'returning attachment images' );

							$this->p->debug->log_arr( 'mt_ret', $mt_ret );
						}

						return $mt_ret;	// Stop here.
					}

					/*
					 * Check for custom meta, featured, or attached image(s).
					 *
					 * Allow for empty post ID in order to execute featured / attached image filters for modules.
					 */
					$post_images = $this->get_post_images( $num, $size_name, $mod[ 'id' ], $md_pre );

					if ( ! empty( $post_images ) ) {

						$mt_ret = array_merge( $mt_ret, $post_images );
					}

				} else {

					/*
					 * get_og_images() provides filter hooks for additional image IDs and URLs.
					 *
					 * Unless $md_pre is 'none', get_og_images() will fallback to using the 'og' custom meta.
					 */
					$mt_images = $mod[ 'obj' ]->get_og_images( $num, $size_name, $mod[ 'id' ], $md_pre );

					if ( ! empty( $mt_images ) ) {

						$mt_ret = array_merge( $mt_ret, $mt_images );
					}
				}

				/*
				 * If we haven't reached the limit of images yet, keep going and check the content text.
				 */
				if ( empty( $this->p->options[ 'plugin_content_images' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping content images' );
					}

				} else {

					if ( ! $this->p->util->is_maxed( $mt_ret, $num ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'getting content images' );
						}

						$num_diff = SucomUtil::array_count_diff( $mt_ret, $num );

						$content_images = $this->get_content_images( $num_diff, $size_name, $mod );

						if ( ! empty( $content_images ) ) {

							$mt_ret = array_merge( $mt_ret, $content_images );
						}

						unset( $content_images );
					}
				}
			}

			if ( empty( $mt_ret ) ) {

				if ( $mod[ 'is_home' ] ||  $mod[ 'is_archive' ] || $mod[ 'is_post' ] ) {

					if ( $mod[ 'is_attachment' ] ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipping default image: post is attachment' );
						}

					} elseif ( ! $mod[ 'is_public' ] ) {	// WooCommerce variations, for example.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipping default image: not public' );

							$this->p->debug->log_arr( 'mod', $mod );
						}

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'using the default image' );
						}

						$mt_ret = $this->get_default_images( $size_name );

						if ( $this->p->debug->enabled ) {

							if ( empty( $mt_ret ) ) {

								$this->p->debug->log( 'no default image' );
							}
						}
					}
				}
			}

			$this->p->util->slice_max( $mt_ret, $num );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning ' . count( $mt_ret ) . ' images' );

				$this->p->debug->log_arr( 'mt_ret', $mt_ret );
			}

			return $mt_ret;
		}

		/*
		 * $size_name must be a string.
		 */
		public function get_post_images( $num, $size_name, $post_id, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'       => $num,
					'size_name' => $size_name,
					'post_id'   => $post_id,
					'md_pre'    => $md_pre,
				) );
			}

			$mt_ret = array();

			if ( ! empty( $post_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting og images' );
				}

				/*
				 * get_og_images() provides filter hooks for additional image IDs and URLs.
				 *
				 * Unless $md_pre is 'none', get_og_images() will fallback to using the 'og' custom meta.
				 */
				$mt_ret = array_merge( $mt_ret, $this->p->post->get_og_images( $num, $size_name, $post_id, $md_pre ) );
			}

			/*
			 * Allow for empty post_id in order to execute featured / attached image filters for modules.
			 */
			if ( ! $this->p->util->is_maxed( $mt_ret, $num ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting featured images' );
				}

				$num_diff = SucomUtil::array_count_diff( $mt_ret, $num );

				$mt_ret = array_merge( $mt_ret, $this->get_featured( $num_diff, $size_name, $post_id ) );
			}

			/*
			 * Maybe get attached images, including WooCommerce product gallery images.
			 */
			if ( empty( $this->p->options[ 'plugin_attached_images' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping attached images' );
				}

			} else {

				if ( ! $this->p->util->is_maxed( $mt_ret, $num ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting attached images' );
					}

					$num_diff = SucomUtil::array_count_diff( $mt_ret, $num );

					$attached_images = $this->get_attached_images( $num_diff, $size_name, $post_id );

					if ( ! empty( $attached_images ) ) {

						$mt_ret = array_merge( $mt_ret, $attached_images );
					}

					unset( $attached_images );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mt_ret', $mt_ret );
			}

			return $mt_ret;
		}

		/*
		 * $size_name must be a string.
		 */
		public function get_featured( $num, $size_name, $post_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'       => $num,
					'size_name' => $size_name,
					'post_id'   => $post_id,
				) );
			}

			$mt_ret = array();

			if ( ! empty( $post_id ) ) {	// Just in case.

				/*
				 * If the post ID is an attachment page, then use the post ID as the image ID.
				 */
				if ( ( is_attachment( $post_id ) || 'attachment' === get_post_type( $post_id ) ) && wp_attachment_is( 'image', $post_id ) ) {

					$pid = $post_id;

				} elseif ( has_post_thumbnail( $post_id ) ) {

					$pid = get_post_thumbnail_id( $post_id );

				} else $pid = false;

				$filter_name = 'wpsso_featured_image_id';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
				}

				$pid = apply_filters( $filter_name, $pid, $post_id );

				if ( ! empty( $pid ) && is_numeric( $pid ) ) {	// Just in case.

					/*
					 * Returns an og:image:url value, not an og:image:secure_url.
					 */
					$mt_single_image = $this->get_mt_single_image_src( $pid, $size_name );

					if ( ! empty( $mt_single_image[ 'og:image:url' ] ) ) {

						$this->p->util->push_max( $mt_ret, $mt_single_image, $num );

						// Continue and apply the 'wpsso_og_featured' filter.
					}
				}
			}

			$filter_name = 'wpsso_og_featured';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
			}

			return apply_filters( $filter_name, $mt_ret, $num, $size_name, $post_id );
		}

		/*
		 * $size_name must be a string.
		 *
		 * See WpssoMedia->get_post_images().
		 */
		public function get_attached_images( $num, $size_name, $post_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'       => $num,
					'size_name' => $size_name,
					'post_id'   => $post_id,
				) );
			}

			$mt_ret = array();

			if ( ! empty( $post_id ) ) {	// Just in case.

				static $local_fifo = array();

				if ( ! isset( $local_fifo[ $post_id ] ) ) {

					/*
					 * Maybe limit the number of array elements.
					 */
					$local_fifo = SucomUtil::array_slice_fifo( $local_fifo, WPSSO_CACHE_ARRAY_FIFO_MAX );

					$local_fifo[ $post_id ] = array();

					$images = get_children( array(
						'post_parent'    => $post_id,
						'post_type'      => 'attachment',
						'post_mime_type' => 'image'	// Aka 'image/%' query.
					), OBJECT );	// OBJECT, ARRAY_A, or ARRAY_N.

					/*
					 * Featured images are handled beforehand by WpssoMedia->get_featured().
					 *
					 * Avoid duplicates by excluding attached image IDs that are also featured image IDs.
					 */
					$post_thumbnail_id = get_post_thumbnail_id( $post_id );

					foreach ( $images as $attachment ) {

						if ( ! empty( $attachment->ID ) && $post_thumbnail_id !== $attachment->ID ) {

							$local_fifo[ $post_id ][] = $attachment->ID;
						}
					}

					rsort( $local_fifo[ $post_id ], SORT_NUMERIC );

					$filter_name = 'wpsso_attached_image_ids';

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
					}

					$local_fifo[ $post_id ] = apply_filters( $filter_name, $local_fifo[ $post_id ], $post_id );
					$local_fifo[ $post_id ] = array_unique( $local_fifo[ $post_id ] );	// Just in case.
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'found ' . count( $local_fifo[ $post_id ] ) . ' attached images for post id ' . $post_id );
				}

				foreach ( $local_fifo[ $post_id ] as $pid ) {

					/*
					 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
					 */
					$mt_single_image = $this->get_mt_single_image_src( $pid, $size_name );

					if ( ! empty( $mt_single_image[ 'og:image:url' ] ) ) {

						if ( $this->p->util->push_max( $mt_ret, $mt_single_image, $num ) ) {

							break;	// Stop here and apply the 'wpsso_og_attached_images' filter.
						}
					}
				}
			}

			$filter_name = 'wpsso_og_attached_images';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
			}

			return apply_filters( $filter_name, $mt_ret, $num, $size_name, $post_id );
		}

		/*
		 * $size_name must be a string.
		 *
		 * See WpssoMedia->get_size_name_images().
		 */
		public function get_content_images( $num = 0, $size_name = 'thumbnail', $mod = true, $content = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			$mt_images = array();

			/*
			 * Allow custom content to be passed as an argument in $content.
			 */
			if ( empty( $content ) ) {

				$content = $this->p->page->get_the_content( $mod );

				$content_passed = false;

			} else $content_passed = true;

			if ( empty( $content ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: empty ' . $mod[ 'name' ] . ' content' );
				}

				return $mt_images;
			}

			$content_img_preg = $this->default_content_img_preg;

			$mt_single_image = SucomUtil::get_mt_image_seed();

			/*
			 * Allow the html_tag and pid_attr regex to be modified.
			 */
			foreach( array( 'html_tag', 'pid_attr' ) as $type ) {

				$filter_name = 'wpsso_content_image_preg_' . $type;	// No need to sanitize.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
				}

				$content_img_preg[ $type ] = apply_filters( $filter_name, $this->default_content_img_preg[ $type ] );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'content_img_preg', $content_img_preg );
			}

			/*
			 * <img/> attributes in order of preference.
			 */
			if ( preg_match_all( '/<((' . $content_img_preg[ 'html_tag' ] . ')[^>]*? (' . $content_img_preg[ 'pid_attr' ] . ')=[\'"]([0-9]+)[\'"]|' .
				'(img)[^>]*? (data-lazy-src|data-share-src|data-src|src)=[\'"]([^\'"]+)[\'"])[^>]*>/s',
					$content, $all_matches, PREG_SET_ORDER ) ) {

				$content_img_max = SucomUtil::get_const( 'WPSSO_CONTENT_IMAGES_MAX', 5 );

				if ( count( $all_matches ) > $content_img_max ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'limiting matches returned from ' . count( $all_matches ) . ' to ' . $content_img_max );
					}

					$all_matches = array_splice( $all_matches, 0, $content_img_max );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' matching <' . $content_img_preg[ 'html_tag' ] . '/> html tag(s) found' );
				}

				foreach ( $all_matches as $match_num => $media ) {

					$tag_value = $media[ 0 ];

					if ( empty( $media[ 5 ] ) ) {

						$tag_name   = $media[ 2 ];	// img
						$attr_name  = $media[ 3 ];	// data-wp-pid
						$attr_value = $media[ 4 ];	// id

					} else {

						$tag_name   = $media[ 5 ];	// img
						$attr_name  = $media[ 6 ];	// data-share-src|data-lazy-src|data-src|src
						$attr_value = $media[ 7 ];	// url
					}

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'match ' . $match_num . ': ' . $tag_name . ' ' . $attr_name . '="' . $attr_value . '"' );
					}

					switch ( $attr_name ) {

						/*
						 * WordPress media library image ID.
						 */
						case 'data-wp-pid':

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'WP image attribute id found: ' . $attr_value );
							}

							$this->add_mt_single_image_src( $mt_single_image, $attr_value, $size_name, false );

							break;

						/*
						 * Check for other data attributes like 'data-ngg-pid'.
						 */
						case ( preg_match( '/^' . $content_img_preg[ 'pid_attr' ] . '$/', $attr_name ) ? true : false ):

							/*
							 * Filter hook for third-party modules to return image information.
							 */
							$filter_name = SucomUtil::sanitize_hookname( 'wpsso_get_content_' . $tag_name . '_' . $attr_name );

							if ( false !== has_filter( $filter_name ) ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
								}

								list(
									$mt_single_image[ 'og:image:url' ],
									$mt_single_image[ 'og:image:width' ],
									$mt_single_image[ 'og:image:height' ],
									$mt_single_image[ 'og:image:cropped' ],
									$mt_single_image[ 'og:image:id' ],
									$mt_single_image[ 'og:image:alt' ],
									$mt_single_image[ 'og:image:size_name' ],
								) = apply_filters( $filter_name, self::reset_image_src_args(), $attr_value, $size_name, false );
							}

							break;

						/*
						 * data-share-src | data-lazy-src | data-src | src
						 */
						default:

							/*
							 * Recognize gravatar images in the content.
							 */
							if ( preg_match( '/^https?:?\/\/[^\/]*gravatar\.com\/avatar\/([a-zA-Z0-9]+)/', $attr_value, $match ) ) {

								$img_size = $this->get_gravatar_size();
								$img_url  = 'https://secure.gravatar.com/avatar/' . $match[ 1 ] . '.jpg?s=' . $img_size . '&d=404&r=G';

								$mt_single_image[ 'og:image:url' ]    = $img_url;
								$mt_single_image[ 'og:image:width' ]  = $img_size;
								$mt_single_image[ 'og:image:height' ] = $img_size;

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'gravatar image found: ' . $img_url );
								}

								break;	// Stop here.
							}

							/*
							 * Check for image ID in class for old content w/o the data-wp-pid attribute.
							 */
							if ( preg_match( '/class="[^"]+ wp-image-([0-9]+)/', $tag_value, $match ) ) {

								$this->add_mt_single_image_src( $mt_single_image, $match[ 1 ], $size_name, false );

								break;	// Stop here.

							} else {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'using attribute value for og:image = ' .
										$attr_value . ' (' . WPSSO_UNDEF . 'x' . WPSSO_UNDEF . ')' );
								}

								$mt_single_image = array(
									'og:image:url'    => $attr_value,
									'og:image:width'  => WPSSO_UNDEF,
									'og:image:height' => WPSSO_UNDEF,
									'og:image:alt'    => preg_match( '/ alt="([^"]+)"/', $tag_value, $match ) ? $match[ 1 ] : '',
								);
							}

							if ( empty( $mt_single_image[ 'og:image:url' ] ) ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'single image og:image value is empty' );
								}

								break;	// Stop here.
							}

							$check_size_limits = true;
							$img_within_limits = true;

							/*
							 * Get the actual width and height of the image using http / https.
							 */
							if ( empty( $mt_single_image[ 'og:image:width' ] ) || $mt_single_image[ 'og:image:width' ] < 0 ||
								empty( $mt_single_image[ 'og:image:height' ] ) || $mt_single_image[ 'og:image:height' ] < 0 ) {

								/*
								 * Add correct image sizes for the image URL using getimagesize().
								 *
								 * Note that PHP v7.1 or better is required to get the image size of WebP images.
								 */
								$this->p->util->add_image_url_size( $mt_single_image );

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'image URL size: ' .
										$mt_single_image[ 'og:image:width' ] . 'x' . $mt_single_image[ 'og:image:height' ] );
								}

								/*
								 * No use checking / retrieving the image size twice.
								 */
								if ( WPSSO_UNDEF === $mt_single_image[ 'og:image:width' ] &&
									WPSSO_UNDEF === $mt_single_image[ 'og:image:height' ] ) {

									$check_size_limits = false;
									$img_within_limits = false;
								}

							} elseif ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'image width / height values: ' .
									$mt_single_image[ 'og:image:width' ] . 'x' . $mt_single_image[ 'og:image:height' ] );
							}

							if ( $check_size_limits ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'checking image size limits for ' . $mt_single_image[ 'og:image:url' ] .
										' (' . $mt_single_image[ 'og:image:width' ] . 'x' . $mt_single_image[ 'og:image:height' ] . ')' );
								}

								/*
								 * Check if image exceeds hard-coded limits (dimensions, ratio, etc.).
								 */
								$img_within_limits = $this->is_image_within_config_limits( $mt_single_image[ 'og:image:url' ],
									$size_name, $mt_single_image[ 'og:image:width' ], $mt_single_image[ 'og:image:height' ],
										__( 'Content', 'wpsso' ) );

							} elseif ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipped image size limits for ' . $mt_single_image[ 'og:image:url' ] .
									' (' . $mt_single_image[ 'og:image:width' ] . 'x' . $mt_single_image[ 'og:image:height' ] . ')' );
							}

							if ( ! apply_filters( 'wpsso_content_accept_img_dims', $img_within_limits,
								$mt_single_image, $size_name, $attr_name, $content_passed ) ) {

								$mt_single_image = array();
							}

							break;
					}

					if ( ! empty( $mt_single_image[ 'og:image:url' ] ) ) {

						$mt_single_image[ 'og:image:url' ] = $this->p->util->fix_relative_url( $mt_single_image[ 'og:image:url' ] );

						$filter_name = 'wpsso_rewrite_image_url';

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'applying filters "' . $filter_name . '" for ' . $mt_single_image[ 'og:image:url' ] );
						}

						$mt_single_image[ 'og:image:url' ] = apply_filters( $filter_name, $mt_single_image[ 'og:image:url' ] );

						if ( $this->p->util->is_uniq_url( $mt_single_image[ 'og:image:url' ], $size_name, $mod ) ) {

							if ( $this->p->util->push_max( $mt_images, $mt_single_image, $num ) ) {

								return $mt_images;
							}
						}
					}
				}

				return $mt_images;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no matching <' . $content_img_preg[ 'html_tag' ] . '/> html tag(s) found' );
			}

			return $mt_images;
		}

		/*
		 * $size_name must be a string.
		 */
		public function get_attachment_image( $num, $size_name, $attachment_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'           => $num,
					'size_name'     => $size_name,
					'attachment_id' => $attachment_id,
				) );
			}

			$mt_ret = array();

			if ( ! empty( $attachment_id ) ) {

				if ( wp_attachment_is( 'image', $attachment_id ) ) {

					/*
					 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
					 */
					$mt_single_image = $this->get_mt_single_image_src( $attachment_id, $size_name );

					if ( ! empty( $mt_single_image[ 'og:image:url' ] ) ) {

						if ( $this->p->util->push_max( $mt_ret, $mt_single_image, $num ) ) {

							return $mt_ret;
						}
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'attachment id ' . $attachment_id . ' is not an image' );
				}
			}

			return $mt_ret;
		}

		/*
		 * Return only the image URL, which will be the first array element returned by get_attachment_image_src().
		 */
		public function get_attachment_image_url( $pid, $size_name = 'thumbnail' ) {

			$image_src = $this->get_attachment_image_src( $pid, $size_name );

			foreach ( $image_src as $num => $value ) {

				return $value;
			}

			return null;	// Return null if array is empty.
		}

		/*
		 * Note that that every return in this method must call self::reset_image_src_args().
		 */
		public function get_attachment_image_src( $pid, $size_name = 'thumbnail' ) {

			/*
			 * Save arguments for the 'image_make_intermediate_size' and 'image_resize_dimensions' filters.
			 */
			self::set_image_src_args( $args = array(
				'pid'       => $pid,
				'size_name' => $size_name,
			) );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( $args );
			}

			/*
			 * Example $size_info = Array (
			 *	[size_name]     => wpsso-opengraph,
			 *	[attachment_id] => false,
			 *	[width]         => 1200,
			 *	[height]        => 630,
			 *	[crop]          => 1,
			 *	[is_cropped]    => 1,
			 *	[dims_transl]   => 1200x630 cropped,
			 *	[label_transl]  => Open Graph (Facebook and oEmbed),
			 *	[opt_prefix]    => og,
			 * );
			 */
			$size_info = $this->p->util->get_size_info( $size_name, $pid );

			if ( empty( $size_info[ 'width' ] ) && empty( $size_info[ 'height' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: missing size information for ' . $size_name );
				}

				return self::reset_image_src_args();
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'size info', $size_info );
			}

			$img_url       = '';
			$img_width     = WPSSO_UNDEF;
			$img_height    = WPSSO_UNDEF;
			$use_full_size = false;

			if ( ! wp_attachment_is( 'image', $pid ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: attachment ' . $pid . ' is not an image' );
				}

				return self::reset_image_src_args();
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'calling wp_get_attachment_metadata() for pid ' . $pid );
			}

			$img_meta = wp_get_attachment_metadata( $pid );	// Returns a WP_Error object on failure.
			$img_alt  = get_metadata( 'post', $pid, '_wp_attachment_image_alt', $single = true );

			/*
			 * Check to see if the full size image width / height matches the resize width / height we require.
			 */
			if ( is_array( $img_meta ) && isset( $img_meta[ 'file' ] ) && isset( $img_meta[ 'width' ] ) && isset( $img_meta[ 'height' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'full size image ' . $img_meta[ 'file' ] .
						' (' . $img_meta[ 'width' ] . 'x' . $img_meta[ 'height' ] . ')' );
				}

				if ( $img_meta[ 'width' ] === $size_info[ 'width' ] && $img_meta[ 'height' ] === $size_info[ 'height' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'using full size image - dimensions identical to ' . $size_name .
							' (' . $size_info[ 'width' ] . 'x' . $size_info[ 'height' ] . ' ' .
								( $size_info[ 'crop' ] ? 'cropped' : 'uncropped' ) . ')' );

					}

					$use_full_size = true;

				} elseif ( ! $size_info[ 'is_cropped' ] ) {

					if ( $img_meta[ 'width' ] === $size_info[ 'width' ] || $img_meta[ 'height' ] === $size_info[ 'height' ] ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'using full size image - dimension matches ' . $size_name .
								' (' . $size_info[ 'dims_transl' ] . ')' );
						}

						$use_full_size = true;
					}
				}

			} else {

				// translators: Please ignore - translation uses a different text domain.
				$img_lib   = __( 'Media Library' );
				$edit_url  = get_edit_post_link( $pid );
				$img_title = get_the_title( $pid );
				$func_name = 'wp_get_attachment_metadata()';
				$func_url  = __( 'https://developer.wordpress.org/reference/functions/wp_get_attachment_metadata/', 'wpsso' );
				$regen_url = 'https://wordpress.org/plugins/search/regenerate+thumbnails/';
				$regen_msg = sprintf( __( 'You may consider regenerating the sizes of all WordPress Media Library images using one of <a href="%s">several available plugins from WordPress.org</a>.', 'wpsso' ), $regen_url );

				/*
				 * wp_get_attachment_metadata() returned a WP_Error object.
				 */
				if ( is_wp_error( $img_meta ) ) {

					$error_msg = $img_meta->get_error_message();

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $func_name . ' error for ' . $pid . ': ' . $error_msg );
					}

					if ( $this->p->notice->is_admin_pre_notices() ) {

						$notice_msg = sprintf( __( 'Possible %1$s corruption detected - the <a href="%2$s">WordPress %3$s function</a> returned an error for <a href="%4$s">image ID %5$s</a>: %6$s.', 'wpsso' ), $img_lib, $func_url, '<code>' . $func_name . '</code>', $edit_url, $pid, $error_msg ) . ' ' . $regen_msg;

						$notice_key = 'wp-get-attachment-metadata-error-for-' . $pid;

						$this->p->notice->err( $notice_msg, null, $notice_key );
					}

					$img_meta = array();	// Avoid "cannot use object of type WP_Error as array" error.

				/*
				 * Image dimensions are missing, but full size image path is present.
				 */
				} elseif ( isset( $img_meta[ 'file' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'full size image ' . $img_meta[ 'file' ] . ' dimensions missing from image metadata' );
					}

					if ( $this->p->notice->is_admin_pre_notices() ) {

						$notice_msg = sprintf( __( 'Possible %1$s corruption detected - the full size image dimensions for <a href="%2$s">image ID %3$s</a> are missing from the image metadata returned by the <a href="%4$s">WordPress %5$s function</a>.', 'wpsso' ), $img_lib, $edit_url, $pid, $func_url, '<code>' . $func_name . '</code>' ) . ' ' . $regen_msg;

						$notice_key = 'full-size-image-' . $pid . '-dimensions-missing';

						$this->p->notice->err( $notice_msg, null, $notice_key );
					}

				/*
				 * Both the image dimensions and full size image path are missing.
				 */
				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'full size image file path for ' . $pid . ' missing from image metadata' );
					}

					if ( $this->p->notice->is_admin_pre_notices() ) {

						$notice_msg = sprintf( __( 'Possible %1$s corruption detected - the full size image file path for <a href="%2$s">image ID %3$s</a> is missing from the image metadata returned by the <a href="%4$s">WordPress %5$s function</a>.', 'wpsso' ), $img_lib, $edit_url, $pid, $func_url, '<code>' . $func_name . '</code>' ) . ' ' . $regen_msg;

						$notice_key = 'full-size-image-' . $pid . '-file-path-missing';

						$this->p->notice->err( $notice_msg, null, $notice_key );
					}
				}
			}

			/*
			 * Only resize our own custom image sizes.
			 */
			if ( 0 === strpos( $size_name, 'wpsso-' ) ) {

				if ( ! $use_full_size ) {

					$is_accurate_filename = false;
					$is_accurate_width    = false;
					$is_accurate_height   = false;

					/*
					 * Make sure the metadata contains complete image information for the requested size.
					 */
					if ( ! empty( $img_meta[ 'sizes' ][ $size_name ] ) &&
						! empty( $img_meta[ 'sizes' ][ $size_name ][ 'file' ] ) &&
						! empty( $img_meta[ 'sizes' ][ $size_name ][ 'width' ] )  &&
						! empty( $img_meta[ 'sizes' ][ $size_name ][ 'height' ] ) ) {

						/*
						 * By default, WordPress adds only the resolution of the resized image to the file
						 * name. If the image size is ours, then add crop information to the file name as
						 * well. This allows for different cropped versions for the same image resolution.
						 */
						$new_filepath         = $this->maybe_update_cropped_image_filepath( $img_meta[ 'sizes' ][ $size_name ][ 'file' ], $size_info );
						$is_accurate_filename = $new_filepath === $img_meta[ 'sizes' ][ $size_name ][ 'file' ] ? true : false;
						$is_accurate_width    = $size_info[ 'width' ] === $img_meta[ 'sizes' ][ $size_name ][ 'width' ] ? true : false;
						$is_accurate_height   = $size_info[ 'height' ] === $img_meta[ 'sizes' ][ $size_name ][ 'height' ] ? true : false;

						/*
						 * If not cropped, make sure the resized image respects the original aspect ratio.
						 */
						if ( ! $size_info[ 'is_cropped' ] ) {

							if ( $is_accurate_width && $is_accurate_height &&
								isset( $img_meta[ 'width' ] ) && isset( $img_meta[ 'height' ] ) ) {

								if ( $img_meta[ 'width' ] > $img_meta[ 'height' ] ) {

									$ratio = $img_meta[ 'width' ] / $size_info[ 'width' ];
									$check = 'height';

								} else {

									$ratio = $img_meta[ 'height' ] / $size_info[ 'height' ];
									$check = 'width';
								}

								$should_be = (int) round( $img_meta[ $check ] / $ratio );

								/*
								 * Allow for a +/- one pixel difference.
								 */
								if ( $img_meta[ 'sizes' ][ $size_name ][ $check ] < ( $should_be - 1 ) ||
									$img_meta[ 'sizes' ][ $size_name ][ $check ] > ( $should_be + 1 ) ) {

									if ( $this->p->debug->enabled ) {

										$this->p->debug->log( $size_name . ' image metadata not accurate' );
									}

									$is_accurate_width  = false;
									$is_accurate_height = false;
								}
							}
						}
					}

					/*
					 * Depending on cropping, one or both sides of the image must be accurate. If the image is
					 * not accurate, then attempt to create a resized image by calling
					 * image_make_intermediate_size().
					 */
					if ( ! $is_accurate_filename ||
						( ! $size_info[ 'is_cropped' ] && ( ! $is_accurate_width && ! $is_accurate_height ) ) ||
							( $size_info[ 'is_cropped' ] && ( ! $is_accurate_width || ! $is_accurate_height ) ) ) {

						if ( $this->can_make_intermediate_size( $img_meta, $size_info ) ) {

							// translators: Please ignore - translation uses a different text domain.
							$img_lib      = __( 'Media Library' );
							$func_name    = 'image_make_intermediate_size()';
							$func_url     = __( 'https://developer.wordpress.org/reference/functions/image_make_intermediate_size/', 'wpsso' );
							$fullsizepath = get_attached_file( $pid );

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'full size path = ' . $fullsizepath );
								$this->p->debug->log( 'calling image_make_intermediate_size()' );
							}

							/*
							 * image_make_intermediate_size() resizes an image to make a thumbnail or
							 * intermediate size. Returns (array|false) metadata array on success,
							 * false if no image was created.
							 */
							$mtime_start  = microtime( $get_float = true );
							$resized_meta = image_make_intermediate_size( $fullsizepath,
								$size_info[ 'width' ], $size_info[ 'height' ], $size_info[ 'crop' ] );
							$mtime_total  = microtime( $get_float = true ) - $mtime_start;
							$mtime_max    = WPSSO_IMAGE_MAKE_SIZE_MAX_TIME;

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'mtime total = ' . $mtime_total );

								if ( false === $resized_meta ) {

									$this->p->debug->log( 'resized meta = ' . false );

								} else {

									$this->p->debug->log_arr( 'resized meta', $resized_meta );
								}
							}

							if ( $mtime_total > $mtime_max ) {

								$error_pre   = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );
								$rec_max_msg = sprintf( __( 'longer than recommended max of %1$.3f secs', 'wpsso' ), $mtime_max );
								$notice_msg  = sprintf( __( 'Slow WordPress function detected - %1$s took %2$.3f secs to make image size "%3$s" from %4$s (%5$s).', 'wpsso' ), '<code>' . $func_name . '</code>', $mtime_total, $size_name, $fullsizepath, $rec_max_msg );

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( sprintf( 'slow WordPress function detected - %1$s took %2$.3f secs' .
										' to make image size "%3$s" from %4$s', $func_name, $mtime_total, $size_name,
											$fullsizepath ) );
								}

								if ( $this->p->notice->is_admin_pre_notices() ) {

									$this->p->notice->err( $notice_msg );
								}

								SucomUtil::safe_error_log( $error_pre . ' ' . $notice_msg, $strip_html = true );
							}

							/*
							 * Returns (array|false) metadata array on success, false if no image was created.
							 */
							if ( false === $resized_meta ) {

								/*
								 * Add notice only if the admin notices have not already been shown.
								 */
								if ( $this->p->notice->is_admin_pre_notices() ) {

									$notice_msg = sprintf( __( 'Possible %1$s corruption detected - the <a href="%2$s">WordPress %3$s function</a> failed to create the "%4$s" image size (%5$s) from %6$s.', 'wpsso' ), $img_lib, $func_url, '<code>' . $func_name . '</code>', $size_name, $size_info[ 'dims_transl' ], $fullsizepath ) . ' ';

									$notice_msg .= sprintf( __( 'You may consider regenerating the sizes of all WordPress Media Library images using one of <a href="%s">several available plugins from WordPress.org</a>.', 'wpsso' ), 'https://wordpress.org/plugins/search/regenerate+thumbnails/' );

									$notice_key = 'image-make-intermediate-size-' . $fullsizepath . '-failure';

									$this->p->notice->err( $notice_msg, null, $notice_key, $dismiss_time = WEEK_IN_SECONDS );
								}

								$use_full_size = true;

							} else {

								$img_meta[ 'sizes' ][ $size_name ] = $resized_meta;

								wp_update_attachment_metadata( $pid, $img_meta );
							}

						} else {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipped image_make_intermediate_size()' );
							}

							if ( isset( $img_meta[ 'file' ] ) && isset( $img_meta[ 'width' ] ) && isset( $img_meta[ 'height' ] ) ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'falling back to full size image ' . $img_meta[ 'file' ] .
										' (' . $img_meta[ 'width' ] . 'x' . $img_meta[ 'height' ] . ')' );
								}

								$use_full_size = true;
							}
						}
					}
				}

				if ( $use_full_size ) {

					if ( isset( $img_meta[ 'sizes' ][ $size_name ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'using full size image - removing ' . $size_name . ' image metadata' );
						}

						unset( $img_meta[ 'sizes' ][ $size_name ] );

						wp_update_attachment_metadata( $pid, $img_meta );
					}
				}
			}

			/*
			 * image_downsize() returns an array of image data, or boolean false if no image is available.
			 */
			$img_downsized = image_downsize( $pid, ( $use_full_size ? 'full' : $size_name ) );	// Returns array or false.

			if ( ! is_array( $img_downsized ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: image downsize for ' . $size_name . ' returned false' );
				}

				return self::reset_image_src_args();
			}

			/*
			 * Some image_downsize() filter hooks may return only 3 elements, so use array_pad() to sanitize the returned array.
			 */
			$img_downsized = array_pad( $img_downsized, 4, null );

			list(
				$img_url,
				$img_width,
				$img_height,
				$img_intermediate
			) = apply_filters( 'wpsso_image_downsize', $img_downsized, $pid, $size_name );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'image downsize for ' . $size_name . ' returned ' . $img_url . ' (' . $img_width . 'x' . $img_height . ')' );
			}

			if ( empty( $img_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: image downsize for ' . $size_name . ' returned an empty url' );
				}

				return self::reset_image_src_args();
			}

			/*
			 * Check if image exceeds hard-coded limits (dimensions, ratio, etc.).
			 */
			$img_within_limits = $this->is_image_within_config_limits( $pid, $size_name, $img_width, $img_height );

			if ( apply_filters( 'wpsso_attached_accept_img_dims', $img_within_limits, $img_url, $img_width, $img_height, $size_name, $pid ) ) {

				$img_url = $this->p->util->fix_relative_url( $img_url );

				$filter_name = 'wpsso_rewrite_image_url';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters "' . $filter_name . '" for ' . $img_url );
				}

				$img_url = apply_filters( 'wpsso_rewrite_image_url', $img_url );

				return self::reset_image_src_args( array( $img_url, $img_width, $img_height, $size_info[ 'is_cropped' ], $pid, $img_alt, $size_name ) );
			}

			return self::reset_image_src_args();
		}

		/*
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 */
		public function get_default_images( $size_names = 'thumbnail' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->get_mt_opts_images( $this->p->options, $size_names, $img_prefix = 'og_def_img', $key_num = null, $mt_pre = 'og' );
		}

		/*
		 * The returned array can include a varying number of elements, depending on the $media_request value.
		 *
		 * $md_pre may be 'none' when getting Open Graph option defaults (and not their custom values).
		 *
		 * $size_name should be a string, not an array.
		 *
		 * See WpssoCmcfFiltersEdit->filter_mb_sso_edit_media_og_rows() for 'pid'.
		 * See WpssoGmfFiltersEdit->filter_mb_sso_edit_media_schema_rows() for 'pid'.
		 * See WpssoEditMedia->filter_mb_sso_edit_media_prio_image_rows() for 'pid'.
		 * See WpssoEditMedia->filter_mb_sso_edit_media_twitter_rows() for 'pid'.
		 * See WpssoEditMedia->filter_mb_sso_edit_media_schema_rows() for 'pid'.
		 * See WpssoEditMedia->filter_mb_sso_edit_media_pinterest_rows() for 'pid'.
		 * See WpssoProAdminEdit->filter_mb_sso_edit_media_prio_video_rows() for 'og_vid_url', 'og_vid_title',
		 * 	'og_vid_desc', 'og_vid_stream_url', 'og_vid_width', 'og_vid_height', 'og_vid_upload'.
		 * See WpssoRrssbSubmenuSharePinterest->get_html() for 'og_img_url'.
		 */
		public function get_media_info( $size_name, array $media_request, array $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( ! is_string( $size_name ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: $size_name must be a string' );
				}

				return array();

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$media_info = array();
			$mt_images  = null;
			$mt_videos  = null;

			foreach ( $media_request as $key ) {

				switch ( $key ) {

					case 'pid':
					case ( preg_match( '/^(' . $mt_pre . '_img)/', $key ) ? true : false ):

						/*
						 * Get images only once.
						 */
						if ( null === $mt_images ) {

							$mt_images = $this->get_size_name_images( $num = 1, $size_name, $mod, $md_pre );
						}

						break;

					case ( preg_match( '/^(' . $mt_pre . '_vid)/', $key ) ? true : false ):

						/*
						 * Get videos only once.
						 */
						if ( null === $mt_videos ) {

							$mt_videos = $this->get_all_videos( $num = 1, $mod, $md_pre );
						}

						break;
				}
			}

			foreach ( $media_request as $key ) {

				switch ( $key ) {

					case 'pid':

						if ( empty( $get_mt_name ) ) {

							$get_mt_name = $mt_pre . ':image:id';
						}

						// No break.

					case $mt_pre . '_img_url':

						if ( empty( $get_mt_name ) ) {

							$get_mt_name = $mt_pre . ':image';
						}

						// No break.

						if ( null !== $mt_videos ) {

							$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $get_mt_name );
						}

						if ( empty( $media_info[ $key ] ) ) {

							$media_info[ $key ] = $this->get_mt_media_value( $mt_images, $get_mt_name );
						}

						break;

					case $mt_pre . '_img_alt':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_images, $mt_pre . ':image:alt' );

						break;

					case $mt_pre . '_vid_url':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video' );

						break;

					case $mt_pre . '_vid_type':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video:type' );

						break;

					case $mt_pre . '_vid_title':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video:title' );

						break;

					case $mt_pre . '_vid_desc':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video:description' );

						break;

					case $mt_pre . '_vid_stream_url':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video:stream_url' );

						break;

					case $mt_pre . '_vid_width':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video:width' );

						break;

					case $mt_pre . '_vid_height':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video:height' );

						break;

					case $mt_pre . '_vid_prev':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video:thumbnail_url' );

						break;

					case $mt_pre . '_vid_upload':

						$media_info[ $key ] = $this->get_mt_media_value( $mt_videos, $mt_pre . ':video:upload_date' );

						WpssoSchema::add_date_time_timezone_opts( $media_info[ $key ], $media_info, $key );

						break;

					default:

						$media_info[ $key ] = '';

						break;
				}

				unset( $get_mt_name );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $media_info );
			}

			return $media_info;
		}

		/*
		 * To get the first image ID or media URL, use the following methods instead:
		 *
		 * SucomUtil::get_first_og_image_id()
		 * SucomUtil::get_first_og_image_url()
		 * SucomUtil::get_first_mt_media_id()
		 * SucomUtil::get_first_mt_media_url()
		 */
		public function get_mt_media_value( $mt_og, $mt_media_pre ) {

			if ( empty( $mt_og ) || ! is_array( $mt_og ) ) {	// Nothing to do.

				return '';
			}

			if ( ! SucomUtil::is_assoc( $mt_og ) ) {

				$og_media = reset( $mt_og );	// Only search the first media array.
			}

			switch ( $mt_media_pre ) {

				/*
				 * If we're asking for an image or video url, then search all three values sequentially.
				 */
				case ( preg_match( '/:(image|video)(:secure_url|:url)?$/', $mt_media_pre ) ? true : false ):

					$mt_search = array(
						$mt_media_pre . ':secure_url',	// og:image:secure_url
						$mt_media_pre . ':url',		// og:image:url
						$mt_media_pre,			// og:image
					);

					break;

				/*
				 * Otherwise, only search for that specific meta tag name.
				 */
				default:

					$mt_search = array( $mt_media_pre );

					break;
			}

			foreach ( $mt_search as $key ) {

				if ( ! isset( $og_media[ $key ] ) ) {

					continue;

				} elseif ( '' === $og_media[ $key ] || null === $og_media[ $key ] ) {	// Allow for 0.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $og_media[ $key ] . ' value is empty (skipped)' );
					}

				} elseif ( WPSSO_UNDEF === $og_media[ $key ] || (string) WPSSO_UNDEF === $og_media[ $key ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $og_media[ $key ] . ' value is ' . WPSSO_UNDEF . ' (skipped)' );
					}

				} else {

					return $og_media[ $key ];
				}
			}

			return '';
		}

		/*
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 */
		public function get_mt_pid_images( $pid, $size_names = 'thumbnail', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'pid'        => $pid,
					'size_names' => $size_names,
					'mt_pre'     => $mt_pre,
				) );
			}

			$mt_ret = array();

			$size_names = $this->p->util->get_image_size_names( $size_names );	// Always returns an array.

			foreach ( $size_names as $size_name ) {

				/*
				 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
				 */
				$mt_single_image = $this->get_mt_single_image_src( $pid, $size_name, $mt_pre );

				if ( ! empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {

					$mt_ret[] = $mt_single_image;
				}
			}

			return $mt_ret;
		}

		public function get_mt_single_image_url( $url, $mt_pre = 'og' ) {

			$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

			$this->add_mt_single_image_url( $mt_single_image, $url, $mt_pre );

			return $mt_single_image;
		}

		/*
		 * $size_name must be a string.
		 */
		public function get_mt_single_image_src( $pid, $size_name = 'thumbnail', $mt_pre = 'og' ) {

			$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

			$this->add_mt_single_image_src( $mt_single_image, $pid, $size_name, $mt_pre );

			return $mt_single_image;
		}

		/*
		 * $size_name must be a string.
		 */
		public function add_mt_single_image_src( array &$mt_single_image, $pid, $size_name = 'thumbnail', $mt_pre = 'og' ) {

			list(
				$mt_single_image[ $mt_pre . ':image:url' ],
				$mt_single_image[ $mt_pre . ':image:width' ],
				$mt_single_image[ $mt_pre . ':image:height' ],
				$mt_single_image[ $mt_pre . ':image:cropped' ],
				$mt_single_image[ $mt_pre . ':image:id' ],
				$mt_single_image[ $mt_pre . ':image:alt' ],
				$mt_single_image[ $mt_pre . ':image:size_name' ],
			) = $this->get_attachment_image_src( $pid, $size_name );
		}

		public function add_mt_single_image_url( array &$mt_single_image, $url, $mt_pre = 'og' ) {

			/*
			 * Example $image_info:
			 *
			 * Array (
			 *	[0] => 2048
			 *	[1] => 2048
			 *	[2] => 3
			 *	[3] => width="2048" height="2048"
			 *	[bits] => 8
			 *	[mime] => image/png
			 * )
			 */
			list( $img_width, $img_height, $img_type, $img_attr ) = $this->p->util->get_image_url_info( $url );

			$mt_single_image[ $mt_pre . ':image:url' ]    = $url;
			$mt_single_image[ $mt_pre . ':image:width' ]  = $img_width;
			$mt_single_image[ $mt_pre . ':image:height' ] = $img_height;
		}

		/*
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 *
		 * $size_name can also be false to ignore image IDs and only use image URLs.
		 */
		public function get_mt_opts_images( $opts, $size_names, $img_prefix = 'og_img', $key_num = null, $mt_pre = 'og' ) {

			$img_opts = array();

			foreach ( array( 'id', 'id_lib', 'url', 'url:width', 'url:height' ) as $key ) {

				$key_suffix = null === $key_num ? $key : $key . '_' . $key_num;	// Use a numbered multi-option key.

				$opt_key = $img_prefix . '_' . $key_suffix;

				$img_opts[ $key ] = SucomUtilOptions::get_key_value( $opt_key, $opts );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'img_opts', $img_opts );
			}

			$mt_ret = array();

			if ( ! empty( $img_opts[ 'id' ] ) && ! empty( $size_names ) ) {

				$mt_ret = $this->get_mt_pid_images( $img_opts[ 'id' ], $size_names, $mt_pre );

			} elseif ( ! empty( $img_opts[ 'url' ] ) ) {

				$mt_ret[] = array(
					$mt_pre . ':image:url'       => $img_opts[ 'url' ],
					$mt_pre . ':image:width'     => $img_opts[ 'url:width' ] > 0 ? $img_opts[ 'url:width' ] : WPSSO_UNDEF,
					$mt_pre . ':image:height'    => $img_opts[ 'url:height' ] > 0 ? $img_opts[ 'url:height' ] : WPSSO_UNDEF,
					$mt_pre . ':image:cropped'   => null,
					$mt_pre . ':image:id'        => null,
					$mt_pre . ':image:alt'       => null,
					$mt_pre . ':image:size_name' => null,
				);
			}

			return $mt_ret;
		}

		public function get_mt_img_pre_url( $opts, $img_prefix = 'og_img', $key_num = null, $mt_pre = 'og' ) {

			/*
			 * $size_name is false to ignore image IDs and only use image URLs.
			 */
			$mt_ret = $this->get_mt_opts_images( $opts, $size_name = false, $img_prefix, $key_num, $mt_pre );

			return isset( $mt_ret[ 0 ] ) ? $mt_ret[ 0 ] : array();
		}

		/*
		 * Returns an array of single video associative arrays.
		 */
		public function get_content_videos( $num = 0, $mod = true, $content = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'     => $num,
					'mod'     => $mod,
					'content' => strlen( $content ) . ' chars',
				) );
			}

			/*
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			$mt_videos = array();

			/*
			 * Allow custom content to be passed as an argument in $content.
			 */
			if ( empty( $content ) ) {

				$content = $this->p->page->get_the_content( $mod );

				$content_passed = false;

			} else $content_passed = true;

			if ( empty( $content ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: empty ' . $mod[ 'name' ] . ' content' );
				}

				return $mt_videos;
			}

			/*
			 * Detect standard video tags.
			 *
			 * $media[ 1 ] = The tag matched (ie. figure, iframe, or embed).
			 * $media[ 2 ] = The attribute matched.
			 * $media[ 3 ] = The video URL.
			 */
			$all_matches = array();

			/*
			 * Matches raw video URLs when the "Use Filtered Content" option is disabled.
			 */
			if ( preg_match_all( '/<(figure) class="(wp-block-embed[^ "]*) [^"]+"><div class="wp-block-embed__wrapper">' .
				' *([^ \'"<>]+\/(embed\/|embed_code\/|player\/|swf\/|v\/|videos?\/|video\.php\?|watch\?)[^ \'"<>]+) *' .	// Raw URL.
				'<\/div><\/figure>/i', $content, $html_tag_matches, PREG_SET_ORDER ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $html_tag_matches ) . ' <figure/> video html tag(s) found' );
				}

				$all_matches = array_merge( $all_matches, $html_tag_matches );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <figure/> video html tag(s) found' );
			}

			if ( preg_match_all( '/<(iframe|embed)[^<>]*? (data-lazy-src|data-share-src|data-src|src)=[\'"]' .
				'([^ \'"<>]+\/(embed\/|embed_code\/|player\/|swf\/|v\/|videos?\/|video\.php\?|watch\?)[^ \'"<>]+)[\'"][^<>]*>/i',
					$content, $html_tag_matches, PREG_SET_ORDER ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $html_tag_matches ) . ' <iframe|embed/> video html tag(s) found' );
				}

				$all_matches = array_merge( $all_matches, $html_tag_matches );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <iframe|embed/> video html tag(s) found' );
			}

			/*
			 * Get video details for standard video tags.
			 *
			 * $media[ 1 ] = The tag matched (ie. figure, iframe, or embed).
			 * $media[ 2 ] = The attribute matched.
			 * $media[ 3 ] = The video URL.
			 */
			if ( is_array( $all_matches ) ) {	// Just in case.

				foreach ( $all_matches as $match_num => $media ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( '<' . $media[ 1 ] . '/> video html tag found ' . $media[ 2 ] . ' = ' . $media[ 3 ] );
					}

					if ( ! empty( $media[ 3 ] ) ) {

						if ( $this->p->util->is_uniq_url( $media[ 3 ], $uniq_context = 'video', $mod ) ) {

							$args = array(
								'url'    => $media[ 3 ],
								'width'  => preg_match( '/ width=[\'"]?([0-9]+)[\'"]?/i', $media[ 0 ], $match ) ? $match[ 1 ] : null,
								'height' => preg_match( '/ height=[\'"]?([0-9]+)[\'"]?/i', $media[ 0 ], $match ) ? $match[ 1 ] : null,
							);

							$mt_single_video = $this->get_video_details( $args, $mod );	// Returns a single video associative array.

							if ( ! empty( $mt_single_video ) ) {

								if ( $this->p->util->push_max( $mt_videos, $mt_single_video, $num ) ) {

									return $mt_videos;
								}
							}
						}
					}
				}
			}

			/*
			 * Filters / modules may detect additional embedded video markup.
			 *
			 * See WpssoIntegUtilElementor->filter_content_videos().
			 * See WpssoProMediaSlideshare->filter_content_videos().
			 * See WpssoProMediaWistia->filter_content_videos().
			 * See WpssoProMediaWpvideoblock->filter_content_videos().
			 * See WpssoProMediaWpvideoshortcode->filter_content_videos().
			 * See WpssoStdMediaSlideshare->filter_content_videos().
			 * See WpssoStdMediaWistia->filter_content_videos().
			 * See WpssoStdMediaWpvideoblock->filter_content_videos().
			 * See WpssoStdMediaWpvideoshortcode->filter_content_videos().
			 */
			$filter_name = 'wpsso_content_videos';	// No need to sanitize.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
			}

			$all_matches = apply_filters( $filter_name, array(), $content, $mod );

			if ( is_array( $all_matches ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' videos returned by ' . $filter_name . ' filters' );
				}

				foreach ( $all_matches as $match_num => $args ) {	// Check each match until WpssoUtil->push_max() is true.

					if ( is_array( $args ) ) {	// Just in case.

						if ( ! empty( $args[ 'url' ] ) ) {

							if ( $this->p->util->is_uniq_url( $args[ 'url' ], $uniq_context = 'video', $mod ) ) {

								$mt_single_video = $this->get_video_details( $args, $mod );	// Returns a single video associative array.

								if ( ! empty( $mt_single_video ) ) {

									if ( $this->p->util->push_max( $mt_videos, $mt_single_video, $num ) ) {

										break;	// Stop here.
									}
								}
							}

						} elseif ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'args url missing from videos array element #' . $match_num );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'videos array element #' . $match_num . ' is not a media array' );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning ' . count( $mt_videos ) . ' videos' );
			}

			return $mt_videos;
		}

		/*
		 * Returns a single video associative array.
		 *
		 * $fallback = true when called from WpssoAbstractWpMeta->get_og_videos().
		 */
		public function get_video_details( array $args, array $mod, $fallback = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $args[ 'url' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no video URL' );
				}

				return array();
			}

			/*
			 * Make sure we have all array keys defined.
			 */
			$args = array_merge( array(
				'url'        => '',
				'type'       => '',
				'stream_url' => '',
				'prev_url'   => '',
				'width'      => null,
				'height'     => null,
				'attach_id'  => null,
			), $args );

			/*
			 * Create the array of video details.
			 */
			$mt_single_video = SucomUtil::get_mt_video_seed();

			if ( ! empty( $args[ 'url' ] ) ) {

				foreach ( array( 'url', 'stream_url', 'width', 'height', 'type' ) as $key ) {

					if ( ! empty( $args[ $key ] ) ) {	// Just in case.

						$mt_single_video[ 'og:video:' . $key ] = $args[ $key ];
					}
				}

				$mt_single_video[ 'og:video:has_image' ] = false;	// Default.

				/*
				 * Check for preview image.
				 */
				$vid_img_url = '';

				if ( ! empty( $args[ 'prev_url' ] ) ) {

					$vid_img_url = $args[ 'prev_url' ];

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'prev_url missing from args array' );
				}

				$filter_name = 'wpsso_og_video_image_url';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters "' . $filter_name . '" for "' . $vid_img_url . '"' );
				}

				if ( $vid_img_url = apply_filters( $filter_name, $vid_img_url, $args[ 'url' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding video preview url = ' . $vid_img_url );
					}

					if ( SucomUtil::is_https( $vid_img_url ) ) {	// Just in case.

						$mt_single_video[ 'og:image:secure_url' ] = $vid_img_url;

						unset( $mt_single_video[ 'og:image:url' ] );	// Just in case.

					} else $mt_single_video[ 'og:image:url' ] = $vid_img_url;

					$mt_single_video[ 'og:video:thumbnail_url' ] = $vid_img_url;
					$mt_single_video[ 'og:video:has_image' ]     = true;

					/*
					 * Add correct image sizes for the image URL using getimagesize().
					 *
					 * Note that PHP v7.1 or better is required to get the image size of WebP images.
					 */
					$this->p->util->add_image_url_size( $mt_single_video );
				}

				/*
				 * If we have a WordPress attachment ID for the video URL (self-hosted videos), then add the Open
				 * Graph video meta tags from the attachment's post.
				 */
				if ( ! empty( $args[ 'attach_id' ] ) || $args[ 'attach_id' ] = attachment_url_to_postid( $args[ 'url' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding video meta tags from attachment ID = ' . $args[ 'attach_id' ] );
					}

					$attach_mod = $this->p->post->get_mod( $args[ 'attach_id' ] );

					$this->p->media->add_og_video_from_attachment( $mt_single_video, $attach_mod );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'args', $args );
			}

			/*
			 * Filter the array of video details.
			 *
			 * See WpssoProMediaFacebook->filter_video_details().
			 * See WpssoProMediaSlideshare->filter_video_details().
			 * See WpssoProMediaSoundcloud->filter_video_details().
			 * See WpssoProMediaVimeo->filter_video_details().
			 * See WpssoProMediaWistia->filter_video_details().
			 * See WpssoProMediaYoutube->filter_video_details().
			 * See WpssoStdMediaFacebook->filter_video_details().
			 * See WpssostdMediaSlideshare->filter_video_details().
			 * See WpssoStdMediaSoundcloud->filter_video_details().
			 * See WpssoStdMediaVimeo->filter_video_details().
			 * See WpssoStdMediaWistia->filter_video_details().
			 * See WpssoStdMediaYoutube->filter_video_details().
			 */
			$filter_name = 'wpsso_video_details';	// No need to sanitize.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
			}

			$mt_single_video = apply_filters( $filter_name, $mt_single_video, $args, $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mt_single_video', $mt_single_video );
			}

			/*
			 * Sanitize the array of video details.
			 */
			if ( isset( $mt_single_video[ 'al:web:url' ] ) ) {	// Just in case.

				if ( '' === $mt_single_video[ 'al:web:url' ] ) {

					$mt_single_video[ 'al:web:should_fallback' ] = '';	// False by default.
				}
			}

			foreach ( array( 'og:video', 'og:image' ) as $mt_media_pre ) {

				$media_url = SucomUtil::get_first_mt_media_url( $mt_single_video, $mt_media_pre );

				if ( 'og:video' === $mt_media_pre ) {

					/*
					 * Fallback to the original video url.
					 */
					if ( empty( $media_url ) && $fallback ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'falling back to media url = ' . $args[ 'url' ] );
						}

						$media_url = $mt_single_video[ 'og:video:url' ] = $args[ 'url' ];
					}

					/*
					 * Check for an empty mime_type.
					 */
					if ( empty( $mt_single_video[ 'og:video:type' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'og:video:type is empty - using media URL to determine the mime-type' );
						}

						/*
						 * Check for filename extension, slash, or common words (in that order), followed
						 * by an optional query string (which is ignored).
						 */
						if ( preg_match( '/(\.[a-z0-9]+|\/|\/embed\/.*|\/iframe\/.*)(\?[^\?]*)?$/', $media_url, $match ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'matched media URL substr "' . $match[ 1 ] . '"' );
							}

							switch ( $match[ 1 ] ) {

								case '/':	// WebPage
								case '.htm':
								case '.html':
								case ( strpos( $match[ 1 ], '/embed/' ) === 0 ? true : false ):
								case ( strpos( $match[ 1 ], '/iframe/' ) === 0 ? true : false ):

									$mt_single_video[ 'og:video:type' ] = 'text/html';

									break;

								case '.3gp':	// 3GP Mobile

									$mt_single_video[ 'og:video:type' ] = 'video/3gpp';

									break;

								case '.avi':	// A/V Interleave

									$mt_single_video[ 'og:video:type' ] = 'video/x-msvideo';

									break;

								case '.flv':	// Flash

									$mt_single_video[ 'og:video:type' ] = 'video/x-flv';

									break;

								case '.m3u8':	// iPhone Index

									$mt_single_video[ 'og:video:type' ] = 'application/x-mpegURL';

									break;

								case '.mov':	// QuickTime

									$mt_single_video[ 'og:video:type' ] = 'video/quicktime';

									break;

								case '.mp4':	// MPEG-4

									$mt_single_video[ 'og:video:type' ] = 'video/mp4';

									break;

								case '.swf':	// Shockwave Flash

									$mt_single_video[ 'og:video:type' ] = 'application/x-shockwave-flash';

									break;

								case '.ts':	// iPhone Segment

									$mt_single_video[ 'og:video:type' ] = 'video/MP2T';

									break;

								case '.wmv':	// Windows Media

									$mt_single_video[ 'og:video:type' ] = 'video/x-ms-wmv';

									break;

								default:

									if ( $this->p->debug->enabled ) {

										$this->p->debug->log( 'unknown video extension "' . $match[ 1 ] . '"' );
									}

									$mt_single_video[ 'og:video:type' ] = '';

									break;
							}

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'setting og:video:type = ' . $mt_single_video[ 'og:video:type' ] );
							}
						}
					}
				}

				$have_media[ $mt_media_pre ] = empty( $media_url ) ? false : true;

				/*
				 * Remove all meta tags if there's no media URL or the media URL is a duplicate.
				 */
				if ( empty( $have_media[ $mt_media_pre ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no media URL: removing ' . $mt_media_pre . ' meta tags' );
					}

					$mt_single_video = SucomUtil::preg_grep_keys( '/^' . $mt_media_pre . '(:.*)?$/', $mt_single_video, $invert = true );

				} elseif ( empty( $mt_single_video[ 'og:video:embed_url' ] ) && 
					isset( $mt_single_video[ 'og:video:type' ] ) && 'text/html' === $mt_single_video[ 'og:video:type' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'text/html media URL: removing ' . $mt_media_pre . ' meta tags' );
					}

					$mt_single_video = SucomUtil::preg_grep_keys( '/^' . $mt_media_pre . '(:.*)?$/', $mt_single_video, $invert = true );

				} elseif ( $this->p->util->is_dupe_url( $media_url, $uniq_context = 'video_details', $mod ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'duplicate media URL: removing ' . $mt_media_pre . ' meta tags' );
					}

					$mt_single_video = SucomUtil::preg_grep_keys( '/^' . $mt_media_pre . '(:.*)?$/', $mt_single_video, $invert = true );

				} elseif ( 'og:image' === $mt_media_pre ) {	// If the media is an image, then maybe add missing sizes.

					if ( empty( $mt_single_video[ 'og:image:width' ] ) || $mt_single_video[ 'og:image:width' ] < 0 ||
						empty( $mt_single_video[ 'og:image:height' ] ) || $mt_single_video[ 'og:image:height' ] < 0 ) {

						/*
						 * Add correct image sizes for the image URL using getimagesize().
						 *
						 * Note that PHP v7.1 or better is required to get the image size of WebP images.
						 */
						$this->p->util->add_image_url_size( $mt_single_video );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'video image URL size: ' .
								$mt_single_video[ 'og:image:width' ] . 'x' . $mt_single_video[ 'og:image:height' ] );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'video image width and height: ' .
							$mt_single_video[ 'og:image:width' ] . 'x' . $mt_single_video[ 'og:image:height' ] );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'accepting ' . $mt_media_pre . ' media URL: ' . $media_url );
				}
			}

			/*
			 * If there's no video or preview image, then return an empty array.
			 */
			if ( ! $have_media[ 'og:video' ] && ! $have_media[ 'og:image' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no media found' );
				}

				return array();

			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning single video array' );
			}

			return $mt_single_video;
		}

		public function add_og_video_from_attachment( array &$mt_single_video, array $attach_mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $attach_mod[ 'id' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: args attachment mod id is empty' );
				}

				return;

			} elseif ( ! wp_attachment_is( 'video', $attach_mod[ 'id' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: args attachment mod id is not a video' );
				}

				return;
			}

			$post_obj = SucomUtilWP::get_post_object( $attach_mod[ 'id' ] );

			$attach_metadata = wp_get_attachment_metadata( $attach_mod[ 'id' ] );	// Returns a WP_Error object on failure.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'attach_metadata', $attach_metadata );
			}

			foreach( array(
				'og:video:url',
				'og:video:embed_url',
				'og:video:stream_url',
				'og:video:stream_size',
				'og:video:thumbnail_url',
				'og:video:title',
				'og:video:description',
				'og:video:upload_date',
				'og:video:has_video',	// Used by video API modules.
				'og:video:type',
				'og:video:width',
				'og:video:height',
				'og:video:duration',
			) as $mt_name ) {

				if ( ! empty( $mt_single_video[ $mt_name ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping ' . $mt_name . ' = ' . $mt_single_video[ $mt_name ] );
					}

					continue;
				}

				$mt_single_video[ $mt_name ] = null;	// Just in case.

				switch ( $mt_name ) {

					case 'og:video:url':
					case 'og:video:stream_url':	// VideoObject contentUrl.

						$mt_single_video[ $mt_name ] = wp_get_attachment_url( $attach_mod[ 'id' ] );

						break;

					case 'og:video:stream_size':	// VideoObject contentSize.

						if ( ! empty( $attach_metadata[ 'filesize' ] ) ) {

							$mt_single_video[ $mt_name ] = $attach_metadata[ 'filesize' ];
						}

						break;

					case 'og:video:embed_url':

						$mt_single_video[ $mt_name ] = get_post_embed_url( $attach_mod[ 'id' ] );

						break;

					case 'og:video:thumbnail_url':

						if ( ! empty( $mt_single_video[ 'og:video:has_image' ] ) ) {	// Just in case.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipping ' . $mt_name . ' - og:video:has_image is true' );
							}

							break;
						}

						$featured_image_id = get_post_thumbnail_id( $attach_mod[ 'id' ] );

						if ( ! empty( $featured_image_id ) ) {	// Just in case.

							$mt_single_video[ $mt_name ] = wp_get_attachment_image_url( $featured_image_id, 'full' );

							if ( ! empty( $mt_single_video[ $mt_name ] ) ) {	// Just in case.

								$mt_single_video[ 'og:video:has_image' ] = true;

								$mt_single_video[ 'og:image:url' ] = $mt_single_video[ $mt_name ];

								/*
								 * Add correct image sizes for the image URL using getimagesize().
								 *
								 * Note that PHP v7.1 or better is required to get the image size of WebP images.
								 */
								$this->p->util->add_image_url_size( $mt_single_video );
							}
						}

						break;

					case 'og:video:title':

						$mt_single_video[ $mt_name ] = $this->p->page->get_title( $attach_mod, $md_key = 'og_title', $max_len = 'og_title' );

						break;

					case 'og:video:description':

						$mt_single_video[ $mt_name ] = $this->p->page->get_description( $attach_mod, $md_key = 'og_desc', $max_len = 'og_desc' );

						break;

					case 'og:video:upload_date':

						if ( ! empty( $post_obj->post_date_gmt ) ) {

							$mt_single_video[ $mt_name ] = date_format( date_create( (string) $post_obj->post_date_gmt ), 'c' );

						} elseif ( ! empty( $post_obj->post_date ) ) {

							$mt_single_video[ $mt_name ] = date_format( date_create( (string) $post_obj->post_date ), 'c' );
						}

						break;

					case 'og:video:has_video':	// Used by video API modules.

						$mt_single_video[ $mt_name ] = true;

						break;

					case 'og:video:type':

						if ( ! empty( $attach_metadata[ 'mime_type' ] ) ) {

							$mt_single_video[ $mt_name ] = $attach_metadata[ 'mime_type' ];
						}

						break;

					case 'og:video:width':

						if ( ! empty( $attach_metadata[ 'width' ] ) ) {

							$mt_single_video[ $mt_name ] = $attach_metadata[ 'width' ];
						}

						break;

					case 'og:video:height':

						if ( ! empty( $attach_metadata[ 'height' ] ) ) {

							$mt_single_video[ $mt_name ] = $attach_metadata[ 'height' ];
						}

						break;

					case 'og:video:duration':

						if ( ! empty( $attach_metadata[ 'length' ] ) ) {

							$mt_single_video[ $mt_name ] = 'PT' . $attach_metadata[ 'length' ] . 'S';
						}

						break;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'added ' . $mt_name . ' = ' . $mt_single_video[ $mt_name ] );
				}
			}
		}

		/*
		 * Modifies a single video associative array.
		 */
		public function add_og_video_from_url( array &$mt_single_video, $url, $cache_exp_secs = 0, $throttle_secs = 0 ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Use the Facebook user agent to get Open Graph meta tags.
			 */
			$curl_opts = array(
				'CURLOPT_USERAGENT'      => WPSSO_PHP_CURL_USERAGENT_FACEBOOK,
				'CURLOPT_COOKIELIST'     => 'ALL',		// Erases all cookies held in memory.
				'CURLOPT_CACHE_THROTTLE' => $throttle_secs,	// Internal curl option.
			);

			if ( $this->p->notice->is_admin_pre_notices() ) {

				if ( $cache_url = $this->p->util->is_html_head_meta_url_cached( $url, $cache_exp_secs ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting video details for ' . $url . ' from file cache ' . $cache_url );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting video details for ' . $url );
					}

					$notice_msg = sprintf( __( 'Getting video details for %s.', 'wpsso' ), '<a href="' . $url . '">' . $url . '</a>' ) . ' ';

					$notice_key = 'video-details-' . $url;

					$this->p->notice->inf( $notice_msg, null, $notice_key );
				}
			}

			$metas = $this->p->util->get_html_head_meta( $url, $query = '//meta', $libxml_errors = false, $cache_exp_secs, $curl_opts );

			/*
			 * Array
			 * (
			 *     [meta] => Array
			 *         (
			 *             [0] => Array
			 *                 (
			 *                     [http-equiv] => X-UA-Compatible
			 *                     [content] => IE=edge
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [1] => Array
			 *                 (
			 *                     [name] => theme-color
			 *                     [content] => rgba(255, 255, 255, 0.98)
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [2] => Array
			 *                 (
			 *                     [name] => title
			 *                     [content] => Entering Namibia's TRIBAL LANDS [S5 - Eps. 58]
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [3] => Array
			 *                 (
			 *                     [name] => description
			 *                     [content] => In this episode I am riding to Epupa Falls, gorgeous waterfalls right at the border between Namibia and Angola. The border with Angola is closed, so I can't ...
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [4] => Array
			 *                 (
			 *                     [name] => keywords
			 *                     [content] => honda, Honda CRF250L, crf250L review, dual purpose bike, RTW, round the world on motorbike, adv rider, motorbike traveller, female motorcycle traveler, solo female traveller, best dirt bike, things to see namibia, motorcycling namibia, best roads namibia, solo ride namibia, Namibia, Southern Africa, off-roading namibia, himba tribe, tribes namibia, opuwo, epupa, epupa falls
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [5] => Array
			 *                 (
			 *                     [property] => og:site_name
			 *                     [content] => YouTube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [6] => Array
			 *                 (
			 *                     [property] => og:url
			 *                     [content] => https://www.youtube.com/watch?v=IQu7ox_UxpA
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [7] => Array
			 *                 (
			 *                     [property] => og:title
			 *                     [content] => Entering Namibia's TRIBAL LANDS [S5 - Eps. 58]
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [8] => Array
			 *                 (
			 *                     [property] => og:image
			 *                     [content] => https://i.ytimg.com/vi/IQu7ox_UxpA/maxresdefault.jpg
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [9] => Array
			 *                 (
			 *                     [property] => og:image:width
			 *                     [content] => 1280
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [10] => Array
			 *                 (
			 *                     [property] => og:image:height
			 *                     [content] => 720
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [11] => Array
			 *                 (
			 *                     [property] => og:description
			 *                     [content] => In this episode I am riding to Epupa Falls, gorgeous waterfalls right at the border between Namibia and Angola. The border with Angola is closed, so I can't ...
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [12] => Array
			 *                 (
			 *                     [property] => al:ios:app_store_id
			 *                     [content] => 544007664
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [13] => Array
			 *                 (
			 *                     [property] => al:ios:app_name
			 *                     [content] => YouTube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [14] => Array
			 *                 (
			 *                     [property] => al:ios:url
			 *                     [content] => vnd.youtube://www.youtube.com/watch?v=IQu7ox_UxpA&feature=applinks
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [15] => Array
			 *                 (
			 *                     [property] => al:android:url
			 *                     [content] => vnd.youtube://www.youtube.com/watch?v=IQu7ox_UxpA&feature=applinks
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [16] => Array
			 *                 (
			 *                     [property] => al:web:url
			 *                     [content] => http://www.youtube.com/watch?v=IQu7ox_UxpA&feature=applinks
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [17] => Array
			 *                 (
			 *                     [property] => og:type
			 *                     [content] => video.other
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [18] => Array
			 *                 (
			 *                     [property] => og:video:url
			 *                     [content] => https://www.youtube.com/embed/IQu7ox_UxpA
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [19] => Array
			 *                 (
			 *                     [property] => og:video:secure_url
			 *                     [content] => https://www.youtube.com/embed/IQu7ox_UxpA
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [20] => Array
			 *                 (
			 *                     [property] => og:video:type
			 *                     [content] => text/html
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [21] => Array
			 *                 (
			 *                     [property] => og:video:width
			 *                     [content] => 1280
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [22] => Array
			 *                 (
			 *                     [property] => og:video:height
			 *                     [content] => 720
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [23] => Array
			 *                 (
			 *                     [property] => al:android:app_name
			 *                     [content] => YouTube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [24] => Array
			 *                 (
			 *                     [property] => al:android:package
			 *                     [content] => com.google.android.youtube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [25] => Array
			 *                 (
			 *                     [property] => og:video:tag
			 *                     [content] => honda
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [48] => Array
			 *                 (
			 *                     [property] => fb:app_id
			 *                     [content] => 87741124305
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [49] => Array
			 *                 (
			 *                     [name] => twitter:card
			 *                     [content] => player
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [50] => Array
			 *                 (
			 *                     [name] => twitter:site
			 *                     [content] => @youtube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [51] => Array
			 *                 (
			 *                     [name] => twitter:url
			 *                     [content] => https://www.youtube.com/watch?v=IQu7ox_UxpA
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [52] => Array
			 *                 (
			 *                     [name] => twitter:title
			 *                     [content] => Entering Namibia's TRIBAL LANDS [S5 - Eps. 58]
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [53] => Array
			 *                 (
			 *                     [name] => twitter:description
			 *                     [content] => In this episode I am riding to Epupa Falls, gorgeous waterfalls right at the border between Namibia and Angola. The border with Angola is closed, so I can't ...
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [54] => Array
			 *                 (
			 *                     [name] => twitter:image
			 *                     [content] => https://i.ytimg.com/vi/IQu7ox_UxpA/maxresdefault.jpg
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [55] => Array
			 *                 (
			 *                     [name] => twitter:app:name:iphone
			 *                     [content] => YouTube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [56] => Array
			 *                 (
			 *                     [name] => twitter:app:id:iphone
			 *                     [content] => 544007664
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [57] => Array
			 *                 (
			 *                     [name] => twitter:app:name:ipad
			 *                     [content] => YouTube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [58] => Array
			 *                 (
			 *                     [name] => twitter:app:id:ipad
			 *                     [content] => 544007664
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [59] => Array
			 *                 (
			 *                     [name] => twitter:app:url:iphone
			 *                     [content] => vnd.youtube://www.youtube.com/watch?v=IQu7ox_UxpA&feature=applinks
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [60] => Array
			 *                 (
			 *                     [name] => twitter:app:url:ipad
			 *                     [content] => vnd.youtube://www.youtube.com/watch?v=IQu7ox_UxpA&feature=applinks
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [61] => Array
			 *                 (
			 *                     [name] => twitter:app:name:googleplay
			 *                     [content] => YouTube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [62] => Array
			 *                 (
			 *                     [name] => twitter:app:id:googleplay
			 *                     [content] => com.google.android.youtube
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [63] => Array
			 *                 (
			 *                     [name] => twitter:app:url:googleplay
			 *                     [content] => https://www.youtube.com/watch?v=IQu7ox_UxpA
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [64] => Array
			 *                 (
			 *                     [name] => twitter:player
			 *                     [content] => https://www.youtube.com/embed/IQu7ox_UxpA
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [65] => Array
			 *                 (
			 *                     [name] => twitter:player:width
			 *                     [content] => 1280
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [66] => Array
			 *                 (
			 *                     [name] => twitter:player:height
			 *                     [content] => 720
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [67] => Array
			 *                 (
			 *                     [itemprop] => name
			 *                     [content] => Entering Namibia's TRIBAL LANDS [S5 - Eps. 58]
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [68] => Array
			 *                 (
			 *                     [itemprop] => description
			 *                     [content] => In this episode I am riding to Epupa Falls, gorgeous waterfalls right at the border between Namibia and Angola. The border with Angola is closed, so I can't ...
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [69] => Array
			 *                 (
			 *                     [itemprop] => paid
			 *                     [content] => False
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [70] => Array
			 *                 (
			 *                     [itemprop] => channelId
			 *                     [content] => UCEIs9nkveW9WmYtsOcJBwTg
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [71] => Array
			 *                 (
			 *                     [itemprop] => videoId
			 *                     [content] => IQu7ox_UxpA
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [72] => Array
			 *                 (
			 *                     [itemprop] => duration
			 *                     [content] => PT21M53S
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [73] => Array
			 *                 (
			 *                     [itemprop] => unlisted
			 *                     [content] => False
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [74] => Array
			 *                 (
			 *                     [itemprop] => width
			 *                     [content] => 1280
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [75] => Array
			 *                 (
			 *                     [itemprop] => height
			 *                     [content] => 720
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [76] => Array
			 *                 (
			 *                     [itemprop] => playerType
			 *                     [content] => HTML5 Flash
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [77] => Array
			 *                 (
			 *                     [itemprop] => width
			 *                     [content] => 1280
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [78] => Array
			 *                 (
			 *                     [itemprop] => height
			 *                     [content] => 720
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [79] => Array
			 *                 (
			 *                     [itemprop] => isFamilyFriendly
			 *                     [content] => true
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [80] => Array
			 *                 (
			 *                     [itemprop] => regionsAllowed
			 *                     [content] => AD,AE,AF,AG,AI,AL,AM,AO,AQ,AR,AS,AT,AU,AW,AX,AZ,BA,BB,BD,BE,BF,BG,BH,BI,BJ,BL,BM,BN,BO,BQ,BR,BS,BT,BV,BW,BY,BZ,CA,CC,CD,CF,CG,CH,CI,CK,CL,CM,CN,CO,CR,CU,CV,CW,CX,CY,CZ,DE,DJ,DK,DM,DO,DZ,EC,EE,EG,EH,ER,ES,ET,FI,FJ,FK,FM,FO,FR,GA,GB,GD,GE,GF,GG,GH,GI,GL,GM,GN,GP,GQ,GR,GS,GT,GU,GW,GY,HK,HM,HN,HR,HT,HU,ID,IE,IL,IM,IN,IO,IQ,IR,IS,IT,JE,JM,JO,JP,KE,KG,KH,KI,KM,KN,KP,KR,KW,KY,KZ,LA,LB,LC,LI,LK,LR,LS,LT,LU,LV,LY,MA,MC,MD,ME,MF,MG,MH,MK,ML,MM,MN,MO,MP,MQ,MR,MS,MT,MU,MV,MW,MX,MY,MZ,NA,NC,NE,NF,NG,NI,NL,NO,NP,NR,NU,NZ,OM,PA,PE,PF,PG,PH,PK,PL,PM,PN,PR,PS,PT,PW,PY,QA,RE,RO,RS,RU,RW,SA,SB,SC,SD,SE,SG,SH,SI,SJ,SK,SL,SM,SN,SO,SR,SS,ST,SV,SX,SY,SZ,TC,TD,TF,TG,TH,TJ,TK,TL,TM,TN,TO,TR,TT,TV,TW,TZ,UA,UG,UM,US,UY,UZ,VA,VC,VE,VG,VI,VN,VU,WF,WS,YE,YT,ZA,ZM,ZW
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [81] => Array
			 *                 (
			 *                     [itemprop] => interactionCount
			 *                     [content] => 180432
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [82] => Array
			 *                 (
			 *                     [itemprop] => datePublished
			 *                     [content] => 2021-07-30
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [83] => Array
			 *                 (
			 *                     [itemprop] => uploadDate
			 *                     [content] => 2021-07-30
			 *                     [textContent] =>
			 *                 )
			 *
			 *             [84] => Array
			 *                 (
			 *                     [itemprop] => genre
			 *                     [content] => Education
			 *                     [textContent] =>
			 *                 )
			 *
			 *         )
			 *
			 * )
			 */
			if ( isset( $metas[ 'meta' ] ) ) {

				foreach ( $metas as $m ) {		// Loop through all meta tags.

					foreach ( $m as $a ) {		// Loop through all attributes for that meta tag.

						$meta_type  = key( $a );			// Example: itemprop
						$meta_name  = reset( $a );			// Example: uploadDate
						$meta_match = $meta_type . '-' . $meta_name;	// Example: itemprop-uploadDate
						$content    = isset( $a[ 'content' ] ) ? $a[ 'content' ] : '';

						switch ( $meta_match ) {

							/*
							 * Use the property meta tag content as-is.
							 */
							case 'property-og:video:width':
							case 'property-og:video:height':
							case 'property-og:video:type':
							case ( strpos( $meta_match, 'property-al:' ) === 0 ? true : false ):	// Facebook AppLink.

								$mt_single_video[ $a[ 'property' ] ] = $content;

								break;

							/*
							 * Add the property meta tag content as an array.
							 */
							case 'property-og:video:tag':

								$mt_single_video[ $a[ 'property' ] ][] = $content;	// Array of tags.

								break;

							case 'property-og:image:secure_url':
							case 'property-og:image:url':
							case 'property-og:image':

								/*
								 * Add the meta name as a query string to know where the value came from.
								 */
								$content = add_query_arg( 'meta', $meta_name, $content );

								if ( SucomUtil::is_https( $content ) ) {

									$mt_single_video[ 'og:image:secure_url' ]    = $content;
									$mt_single_video[ 'og:video:thumbnail_url' ] = $content;

								} else {

									$mt_single_video[ 'og:image:url' ] = $content;

									if ( empty( $mt_single_video[ 'og:video:thumbnail_url' ] ) ) {

										$mt_single_video[ 'og:video:thumbnail_url' ] = $content;
									}
								}

								$mt_single_video[ 'og:video:has_image' ] = true;

								break;

							/*
							 * Add additional, non-standard properties, like og:video:title and og:video:description.
							 */
							case 'property-og:title':
							case 'property-og:description':

								$og_key = 'og:video:' . substr( $a[ 'property' ], 3 );

								$mt_single_video[ $og_key ] = $this->p->util->cleanup_html_tags( $content );

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'adding ' . $og_key . ' = ' . $mt_single_video[ $og_key ] );
								}

								if ( empty( $mt_single_video[ 'og:image:alt' ] ) ) {

									$mt_single_video[ 'og:image:alt' ] = $mt_single_video[ $og_key ];
								}

								break;

							/*
							 * twitter:app:name:iphone
							 * twitter:app:id:iphone
							 * twitter:app:url:iphone
							 */
							case ( strpos( $meta_match, 'name-twitter:app:' ) === 0 ? true : false ):	// X (Twitter) apps.

								if ( preg_match( '/^twitter:app:([a-z]+):([a-z]+)$/', $meta_name, $match ) ) {

									$mt_single_video[ 'og:video:' . $match[ 2 ] . '_' . $match[ 1 ] ] = SucomUtil::decode_html( $content );
								}

								break;

							case 'itemprop-datePublished':

								$mt_single_video[ 'og:video:published_date' ] = gmdate( 'c', strtotime( $content ) );

								if ( empty( $mt_single_video[ 'og:video:upload_date' ] ) ) {

									$mt_single_video[ 'og:video:upload_date' ] = $mt_single_video[ 'og:video:published_date' ];
								}

								break;

							case 'itemprop-uploadDate':

								$mt_single_video[ 'og:video:upload_date' ] = gmdate( 'c', strtotime( $content ) );

								if ( empty( $mt_single_video[ 'og:video:published_date' ] ) ) {

									$mt_single_video[ 'og:video:published_date' ] = $mt_single_video[ 'og:video:upload_date' ];
								}

								break;

							case 'itemprop-duration':

								$mt_single_video[ 'og:video:duration' ] = $content;	// Example: PT21M53S.

								break;

							case 'itemprop-embedUrl':
							case 'itemprop-embedURL':

								$mt_single_video[ 'og:video:embed_url' ] = SucomUtil::decode_html( $content );

								break;

							case 'itemprop-isFamilyFriendly':

								/*
								 * See WpssoUtil->get_sitemaps_videos().
								 */
								switch ( $content ) {

									case 'yes':
									case 'true':

										$mt_single_video[ 'og:video:family_friendly' ] = 'yes';

										break;

									case 'no':
									case 'false':

										$mt_single_video[ 'og:video:family_friendly' ] = 'no';

										break;
								}

								break;

							case 'itemprop-regionsAllowed':

								$mt_single_video[ 'og:video:regions_allowed' ] = preg_split( '|,[\s]*?|', trim( $content ) );	// Example: AD,AE,AF,AG,AI,AL,AM,AO,AQ,AR,AS,AT,AU,AW,AX,AZ,BA,BB,BD,BE,BF,BG,BH,BI,BJ,BL,BM,BN,BO,BQ,BR,BS,BT,BV,BW,BY,BZ,CA,CC,CD,CF,CG,CH,CI,CK,CL,CM,CN,CO,CR,CU,CV,CW,CX,CY,CZ,DE,DJ,DK,DM,DO,DZ,EC,EE,EG,EH,ER,ES,ET,FI,FJ,FK,FM,FO,FR,GA,GB,GD,GE,GF,GG,GH,GI,GL,GM,GN,GP,GQ,GR,GS,GT,GU,GW,GY,HK,HM,HN,HR,HT,HU,ID,IE,IL,IM,IN,IO,IQ,IR,IS,IT,JE,JM,JO,JP,KE,KG,KH,KI,KM,KN,KP,KR,KW,KY,KZ,LA,LB,LC,LI,LK,LR,LS,LT,LU,LV,LY,MA,MC,MD,ME,MF,MG,MH,MK,ML,MM,MN,MO,MP,MQ,MR,MS,MT,MU,MV,MW,MX,MY,MZ,NA,NC,NE,NF,NG,NI,NL,NO,NP,NR,NU,NZ,OM,PA,PE,PF,PG,PH,PK,PL,PM,PN,PR,PS,PT,PW,PY,QA,RE,RO,RS,RU,RW,SA,SB,SC,SD,SE,SG,SH,SI,SJ,SK,SL,SM,SN,SO,SR,SS,ST,SV,SX,SY,SZ,TC,TD,TF,TG,TH,TJ,TK,TL,TM,TN,TO,TR,TT,TV,TW,TZ,UA,UG,UM,US,UY,UZ,VA,VC,VE,VG,VI,VN,VU,WF,WS,YE,YT,ZA,ZM,ZW.

								break;
						}
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $mt_single_video );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no head meta found in ' . $url );
			}
		}

		/*
		 * $img_mixed can be an image ID or URL.
		 *
		 * $img_lib can be 'Media Library', 'Content', etc.
		 */
		public function is_image_within_config_limits( $img_mixed, $size_name, $img_width, $img_height, $img_lib = null ) {

			if ( 0 !== strpos( $size_name, 'wpsso-' ) ) {	// Only check our own sizes.

				return true;
			}

			if ( null === $img_lib ) {	// Default to the WordPress Media Library.

				// translators: Please ignore - translation uses a different text domain.
				$img_lib = __( 'Media Library' );
			}

			$img_ratio = 0;
			$img_label = $img_mixed;

			if ( is_numeric( $img_mixed ) ) {

				$edit_url  = get_edit_post_link( $img_mixed );
				$img_title = get_the_title( $img_mixed );
				$img_label = sprintf( __( 'image ID %1$s (%2$s)', 'wpsso' ), $img_mixed, $img_title );
				$img_label = empty( $edit_url ) ? $img_label : '<a href="' . $edit_url . '">' . $img_label . '</a>';

			} elseif ( false !== strpos( $img_mixed, '://' ) ) {

				if ( WPSSO_UNDEF === $img_width || WPSSO_UNDEF === $img_height ) {

					list( $img_width, $img_height, $img_type, $img_attr ) = $this->p->util->get_image_url_info( $img_mixed );
				}

				$img_label = '<a href="' . $img_mixed . '">' . $img_mixed . '</a>';
				$img_label = sprintf( __( 'image URL %s', 'wpsso' ), $img_mixed );
			}

			/*
			 * Exit silently if the image width and/or height is not valid.
			 */
			if ( WPSSO_UNDEF === $img_width || WPSSO_UNDEF === $img_height ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . strtolower( $img_lib ) . ' ' . $img_mixed . ' rejected - ' .
						'invalid width and/or height ' . $img_width . 'x' . $img_height );
				}

				return false;	// Image rejected.
			}

			if ( $img_width > 0 && $img_height > 0 ) {

				$img_ratio = $img_width >= $img_height ? $img_width / $img_height : $img_height / $img_width;
				$img_ratio = number_format( $img_ratio, 3, '.', '' );
			}

			$cf_min     = $this->p->cf[ 'head' ][ 'limit_min' ];
			$cf_max     = $this->p->cf[ 'head' ][ 'limit_max' ];
			$opt_pre    = $this->p->util->get_image_size_opt( $size_name );
			$min_width  = isset( $cf_min[ $opt_pre . '_img_width' ] ) ? $cf_min[ $opt_pre . '_img_width' ] : 0;
			$min_height = isset( $cf_min[ $opt_pre . '_img_height' ] ) ? $cf_min[ $opt_pre . '_img_height' ] : 0;
			$max_ratio  = isset( $cf_max[ $opt_pre . '_img_ratio' ] ) ? $cf_max[ $opt_pre . '_img_ratio' ] : 0;

			/*
			 * Check the maximum image aspect ratio.
			 */
			if ( $max_ratio && $img_ratio > $max_ratio ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . strtolower( $img_lib ) . ' ' . $img_mixed . ' rejected - ' .
						$img_width . 'x' . $img_height . ' aspect ratio is equal to/or greater than ' . $max_ratio . ':1' );
				}

				/*
				 * Add notice only if the admin notices have not already been shown.
				 *
				 * An is_admin() test is required to make sure the WpssoMessages class is available.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$size_label = $this->p->util->get_image_size_label( $size_name );	// Returns pre-translated labels.

		 			/*
					 * $img_lib can be 'Media Library', 'Content', etc.
					 */
					$notice_msg = sprintf( __( '%1$s %2$s ignored - the resulting resized image of %3$s has an <strong>aspect ratio equal to/or greater than %4$d:1 allowed by the %5$s standard</strong>.', 'wpsso' ), $img_lib, $img_label, $img_width . 'x' . $img_height, $max_ratio, $size_label ). ' ';

					$notice_msg .= $this->p->msgs->get( 'notice-image-rejected', array( 'show_adjust_img_opts' => false ) );

					$notice_key = 'image_' . $img_mixed . '_' . $img_width . 'x' . $img_height . '_' . $size_name . '_ratio_greater_than_allowed';

					$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
				}

				return false;	// Image rejected.
			}

			/*
			 * Check the minimum image width and/or height.
			 */
			if ( ( $min_width > 0 || $min_height > 0 ) && ( $img_width < $min_width || $img_height < $min_height ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . strtolower( $img_lib ) . ' ' . $img_mixed . ' rejected - ' .
						$img_width . 'x' . $img_height . ' smaller than minimum ' . $min_width . 'x' . $min_height . ' for ' . $size_name );
				}

				/*
				 * Add notice only if the admin notices have not already been shown.
				 *
				 * An is_admin() test is required to make sure the WpssoMessages class is available.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$size_label = $this->p->util->get_image_size_label( $size_name );	// Returns pre-translated labels.

		 			/*
					 * $img_lib can be 'Media Library', 'Content', etc.
					 */
					$notice_msg = sprintf( __( '%1$s %2$s ignored - the resulting resized image of %3$s is <strong>smaller than the minimum of %4$s allowed by the %5$s standard</strong>.', 'wpsso' ), $img_lib, $img_label, $img_width . 'x' . $img_height, $min_width . 'x' . $min_height, $size_label ) . ' ';

					$notice_msg .= $this->p->msgs->get( 'notice-image-rejected', array( 'show_adjust_img_size_opts' => false ) );

					$notice_key = 'image_' . $img_mixed . '_' . $img_width . 'x' . $img_height . '_' . $size_name . '_smaller_than_minimum_allowed';

					$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
				}

				return false;	// Image rejected.
			}

			return true;	// Image accepted.
		}

		public function can_make_intermediate_size( $img_meta, $size_info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$full_width      = empty( $img_meta[ 'width' ] ) ? 0 : $img_meta[ 'width' ];
			$full_height     = empty( $img_meta[ 'height' ] ) ? 0 : $img_meta[ 'height' ];
			$is_sufficient_w = $full_width >= $size_info[ 'width' ] ? true : false;
			$is_sufficient_h = $full_height >= $size_info[ 'height' ] ? true : false;

			$upscale_multiplier = 1;

			if ( ! empty( $this->p->options[ 'plugin_upscale_images' ] ) ) {

				$img_src_args        = self::get_image_src_args();
				$upscale_pct_max     = apply_filters( 'wpsso_image_upscale_max', $this->p->options[ 'plugin_upscale_pct_max' ], $img_src_args );
				$upscale_multiplier  = 1 + ( $upscale_pct_max / 100 );
				$upscale_full_width  = round( $full_width * $upscale_multiplier );
				$upscale_full_height = round( $full_height * $upscale_multiplier );
				$is_sufficient_w     = $upscale_full_width >= $size_info[ 'width' ] ? true : false;
				$is_sufficient_h     = $upscale_full_height >= $size_info[ 'height' ] ? true : false;
			}

			if ( ( ! $size_info[ 'is_cropped' ] && ( ! $is_sufficient_w && ! $is_sufficient_h ) ) ||
				( $size_info[ 'is_cropped' ] && ( ! $is_sufficient_w || ! $is_sufficient_h ) ) ) {

				$ret = false;

			} else $ret = true;

			if ( $this->p->debug->enabled ) {

				$upscaled_by = $upscale_multiplier !== 1 ? ' (' . $upscale_full_width . 'x' . $upscale_full_height .
					' upscaled by ' . $upscale_multiplier . ')' : '';

				$this->p->debug->log( 'full size image of ' . $full_width . 'x' . $full_height . $upscaled_by .
					( $ret ? ' sufficient' : ' too small' ) . ' to create ' . $size_info[ 'dims_transl' ] );
			}

			return $ret;
		}

		/*
		 * Determine if an image can be renamed or needs to be copied.
		 *
		 * Check for conflicting image sizes (ie. same dimensions uncropped, or same dimensions from other WordPress sizes).
		 */
		public function can_rename_image_filename( $size_name, $pid ) {

			$img_meta = wp_get_attachment_metadata( $pid );	// Returns a WP_Error object on failure.

			if ( isset( $img_meta[ 'sizes' ] ) ) {	// Just in case.

				if ( isset( $img_meta[ 'sizes' ][ $size_name ] ) ) {	// Just in case.

					$size_dims       = $img_meta[ 'sizes' ][ $size_name ];
					$size_is_cropped = $this->p->util->is_size_cropped( $size_name, $pid );

					foreach ( $img_meta[ 'sizes' ] as $img_meta_size_name => $img_meta_size_dims ) {

						if ( $img_meta_size_name === $size_name ) {	// Ignore ourselves.

							continue;
						}

						if ( $img_meta_size_dims[ 'width' ] == $size_dims[ 'width' ] && $img_meta_size_dims[ 'height' ] == $size_dims[ 'height' ] ) {

							if ( 0 === strpos( $img_meta_size_name, 'wpsso-' ) ) {

								$img_meta_is_cropped = $this->p->util->is_size_cropped( $img_meta_size_name, $pid );

								if ( $img_meta_is_cropped !== $size_is_cropped ) {

									return false;
								}

							} else {

								return false;
							}
						}
					}
				}
			}

			return true;
		}

		public function maybe_update_cropped_image_filepath( $filepath, $size_info ) {

			$dir          = pathinfo( $filepath, PATHINFO_DIRNAME );	// Returns '.' for filenames without paths.
			$ext          = pathinfo( $filepath, PATHINFO_EXTENSION );
			$base         = wp_basename( $filepath, '.' . $ext );
			$new_dir      = '.' === $dir ? '' : trailingslashit( $dir );
			$new_ext      = '.' . $ext;
			$new_base     = preg_replace( '/-cropped(-[a-z]+-[a-z]+)?$/', '', $base );
			$new_suffix   = '';
			$new_filepath = $new_dir . $new_base . $new_suffix . $new_ext;

			if ( ! empty( $size_info[ 'crop' ] ) ) {

				if ( ! empty( $this->p->options[ 'plugin_prevent_thumb_conflicts' ] ) ) {	// Since WPSSO Core v15.6.0.

					$new_suffix .= '-cropped';

					if ( is_array( $size_info[ 'crop' ] ) ) {

						if ( $size_info[ 'crop' ] !== array( 'center', 'center' ) ) {

							$new_suffix .= '-' . implode( '-', $size_info[ 'crop' ] );
						}
					}

					$new_filepath = $new_dir . $new_base . $new_suffix . $new_ext;
				}

				$new_filepath = apply_filters( 'wpsso_cropped_image_filepath', $new_filepath, $filepath, $size_info );
			}

			return $new_filepath;
		}

		public function get_gravatar_size() {

			$def_size = 1200;
			$max_size = 2048;
			$img_size = empty( $this->p->options[ 'plugin_gravatar_size' ] ) ? 1200 : $this->p->options[ 'plugin_gravatar_size' ];
			$img_size = $img_size > 2048 ? 2048 : $img_size;

			return $img_size;
		}

		/*
		 * Use these static methods set / reset information about the image being processed for down-stream filters /
		 * methods lacking this information.
		 */
		public static function set_image_src_args( $image_src_args = null ) {

			self::$image_src_args = $image_src_args;
		}

		public static function get_image_src_args( $key = false ) {

			if ( false !== $key ) {

				if ( isset( self::$image_src_args[ $key ] ) ) {

					return self::$image_src_args[ $key ];

				}

				return null;
			}

			return self::$image_src_args;
		}

		public static function reset_image_src_args( array $args = array() ) {

			self::$image_src_args = null;

			$have_count = count( $args );

		 	$args_count = count( array(
				'og:image:url'       => null,
				'og:image:width'     => null,
				'og:image:height'    => null,
				'og:image:cropped'   => null,
				'og:image:id'        => null,
				'og:image:alt'       => null,
				'og:image:size_name' => null,
			) );

			if ( $have_count < $args_count ) {

				$args = array_pad( $args, $args_count, null );

			} elseif ( $have_count > $args_count ) {

				$args = array_slice( $args, 0, $args_count );
			}

			return $args;
		}
	}
}
