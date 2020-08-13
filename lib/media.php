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

if ( ! class_exists( 'WpssoMedia' ) ) {

	class WpssoMedia {

		private $p;

		private $default_content_img_preg = array(
			'html_tag' => 'img',
			'pid_attr' => 'data-[a-z]+-pid',
			'ngg_src'  => '[^\'"]+\/cache\/([0-9]+)_(crop)?_[0-9]+x[0-9]+_[^\/\'"]+|[^\'"]+-nggid0[1-f]([0-9]+)-[^\'"]+',
		);

		private static $image_src_args  = null;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$max_int = SucomUtil::get_max_int();

			if ( ! empty( $this->p->options[ 'plugin_check_img_dims' ] ) ) {

				$this->p->util->add_plugin_filters( $this, array(
					'attached_accept_img_dims' => 6,
					'content_accept_img_dims'  => 6,
				) );
			}

			add_action( 'init', array( $this, 'allow_img_data_attributes' ) );
			add_action( 'post-upload-ui', array( $this, 'show_post_upload_ui_message' ) );

			add_filter( 'editor_max_image_size', array( $this, 'maybe_adjust_max_image_size' ), 10, 3 );
			add_filter( 'image_make_intermediate_size', array( $this, 'maybe_update_image_filename' ), -5000, 1 );
			add_filter( 'wp_image_resize_identical_dimensions', array( $this, 'maybe_resize_fuzzy_dimensions' ), $max_int, 1 );
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_attachment_image_attributes' ), 10, 2 );
			add_filter( 'get_image_tag', array( $this, 'get_image_tag' ), 10, 6 );
		}

		/**
		 * $size_name must be a string.
		 */
		public function filter_attached_accept_img_dims( $accept, $img_url, $img_width, $img_height, $size_name, $pid ) {

			/**
			 * Don't re-check already rejected images.
			 */
			if ( ! $accept ) {	// Value is false.

				return false;
			}

			if ( 0 !== strpos( $size_name, $this->p->lca . '-' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . $size_name . ' not a ' . $this->p->lca . ' custom image size' );
				}

				return $accept;
			}

			$size_info       = $this->p->util->get_size_info( $size_name, $pid );
			$is_sufficient_w = $img_width >= $size_info[ 'width' ] ? true : false;
			$is_sufficient_h = $img_height >= $size_info[ 'height' ] ? true : false;

			if ( ( ! $size_info[ 'is_cropped' ] && ( $is_sufficient_w || $is_sufficient_h ) ) ||
				( $size_info[ 'is_cropped' ] && ( $is_sufficient_w && $is_sufficient_h ) ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'image id ' . $pid . ' dimensions (' . $img_width . 'x' . $img_height . ') are sufficient' );
				}

				return true;	// Image dimensions are sufficient.
			}

			$img_meta = wp_get_attachment_metadata( $pid );

			if ( isset( $img_meta[ 'width' ] ) && isset( $img_meta[ 'height' ] ) &&
				$img_meta[ 'width' ] < $size_info[ 'width' ] && $img_meta[ 'height' ] < $size_info[ 'height' ] ) {

				$size_text = $img_meta[ 'width' ] . 'x' . $img_meta[ 'height' ] . ' (' . __( 'full size original', 'wpsso' ) . ')';

			} else {

				$size_text = $img_width . 'x' . $img_height;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'image id ' . $pid . ' rejected - ' . $size_text .
					' too small for the ' . $size_name . ' (' . $size_info[ 'dimensions' ] . ') image size' );
			}

			/**
			 * Add notice only if the admin notices have not already been shown.
			 */
			if ( $this->p->notice->is_admin_pre_notices() ) {

				$img_lib      = __( 'Media Library', 'wpsso' );
				$img_edit_url = get_edit_post_link( $pid );
				$img_title    = get_the_title( $pid );
				$img_label    = sprintf( __( 'image ID %1$s (%2$s)', 'wpsso' ), $pid, $img_title );
				$img_label    = empty( $img_edit_url ) ? $img_label : '<a href="' . $img_edit_url . '">' . $img_label . '</a>';

				$notice_msg = sprintf( __( '%1$s %2$s ignored &mdash; the resulting image of %3$s is too small for the required %4$s image dimensions.',
					'wpsso' ), $img_lib, $img_label, $size_text, '<b>' . $size_info[ 'label_transl' ] . '</b> (' . $size_info[ 'dimensions' ] . ')' ) .
						' ' . $this->p->msgs->get( 'notice-image-rejected' );

				$notice_key = 'wp_' . $pid . '_' . $size_text . '_' . $size_name . '_' . $size_info[ 'dimensions' ] . '_rejected';

				$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
			}

			return false;
		}

		/**
		 * $size_name must be a string.
		 */
		public function filter_content_accept_img_dims( $accept, $og_image, $size_name, $attr_name, $content_passed ) {

			/**
			 * Don't re-check already rejected images.
			 */
			if ( ! $accept ) {	// Value is false.

				return false;
			}

			if ( 0 !== strpos( $size_name, $this->p->lca . '-' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . $size_name . ' not a ' . $this->p->lca . ' custom image size' );
				}

				return $accept;
			}

			$size_info       = $this->p->util->get_size_info( $size_name );
			$is_sufficient_w = $og_image[ 'og:image:width' ] >= $size_info[ 'width' ] ? true : false;
			$is_sufficient_h = $og_image[ 'og:image:height' ] >= $size_info[ 'height' ] ? true : false;
			$image_url       = SucomUtil::get_first_mt_media_url( $og_image );

			if ( ( $attr_name == 'src' && ! $size_info[ 'is_cropped' ] && ( $is_sufficient_w || $is_sufficient_h ) ) ||
				( $attr_name == 'src' && $size_info[ 'is_cropped' ] && ( $is_sufficient_w && $is_sufficient_h ) ) ||
					$attr_name == 'data-share-src' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $image_url . ' dimensions (' . $og_image[ 'og:image:width' ] . 'x' .
						$og_image[ 'og:image:height' ] . ') are sufficient' );
				}

				return true;	// Image dimensions are sufficient.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'content image rejected: width / height missing or too small for ' . $size_name );
			}

			/**
			 * Add notice only if the admin notices have not already been shown.
			 */
			if ( $this->p->notice->is_admin_pre_notices() ) {

				$notice_msg = sprintf( __( 'Image %1$s in content ignored &mdash; the image width and height is too small for the required %2$s image dimensions.', 'wpsso' ), $image_url, '<b>' . $size_info[ 'label_transl' ] . '</b> (' . $size_info[ 'dimensions' ] . ')' );

				$notice_msg .= $content_passed ? '' : ' ' . sprintf( __( '%1$s includes an additional \'data-wp-pid\' attribute for Media Library images &mdash; if this image was selected from the Media Library before %1$s was activated, try removing and adding the image back to your content.', 'wpsso' ), $this->p->cf[ 'plugin' ][ $this->p->lca ][ 'short' ] );

				$notice_key = 'content_' . $image_url . '_' . $size_name . '_rejected';

				$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
			}

			return false;
		}

		public function allow_img_data_attributes() {

			global $allowedposttags;

			$allowedposttags[ 'img' ][ 'data-wp-pid' ] = true;
		}

		public function show_post_upload_ui_message() {

			$msg_transl = __( '<strong>Suggested minimum dimension for uploaded images</strong> (to satisfy all %1$d image sizes): %2$d by %3$d pixels.', 'wpsso' );

			list( $min_width, $min_height, $size_count ) = SucomUtilWP::get_minimum_image_wh();

			echo '<p class="minimum-dimensions">';

			echo sprintf( $msg_transl, $size_count, $min_width, $min_height );

			echo '</p>' . "\n";

			/**
			 * Hide the "suggested image dimensions" as they are less than the minimum we suggest here.
			 */
			echo '<style type="text/css">p.suggested-dimensions{ display:none; }</style>' . "\n";
		}

		/**
		 * Note that $size can be a string or an array().
		 */
		public function maybe_adjust_max_image_size( $max_image_size = array(), $size = '', $context = '' ) {

			/**
			 * Allow our sizes to exceed the editor width.
			 */
			if ( is_string( $size ) && 0 === strpos( $size, $this->p->lca . '-' ) ) {

				$max_image_size = array( 0, 0 );
			}

			return $max_image_size;
		}

		/**
		 * By default, WordPress adds only the resolution of the resized image to the file name. If the image size is ours,
		 * then add crop information to the file name as well. This allows for different cropped versions for the same
		 * image resolution.
		 *
		 * Example:
		 *
		 * 	unicorn-wallpaper-1200x630.jpg
		 * 	unicorn-wallpaper-1200x630-cropped.jpg
		 *	unicorn-wallpaper-1200x630-cropped-center-top.jpg
		 */
		public function maybe_update_image_filename( $file_path ) {

			/**
			 * get_attachment_image_src() in the WpssoMedia class saves / sets the image information (pid, size_name,
			 * etc) before calling the image_make_intermediate_size() function (and others). Returns null if no image
			 * information was set (presumably because we arrived here without passing through our own method).
			 */
			$img_info = (array) self::get_image_src_args();

			if ( empty( $img_info[ 'size_name' ] ) || 0 !== strpos( $img_info[ 'size_name' ], $this->p->lca . '-' ) ) {

				return $file_path;
			}

			$size_info = $this->p->util->get_size_info( $img_info[ 'size_name' ], $img_info[ 'pid' ] );

			/**
			 * If the resized image is not cropped, then leave the file name as-is.
			 */
			if ( empty( $size_info[ 'crop' ] ) ) {

				return $file_path;
			}

			$new_file_path = $this->get_cropped_image_filename( $file_path, $size_info );

			if ( $file_path !== $new_file_path ) {		// Just in case

				if ( copy( $file_path, $new_file_path ) ) {

					return $new_file_path;		// Return the new file path on success.
				}
			}

			return $file_path;
		}

		/**
		 * Hooked to the 'wp_image_resize_identical_dimensions' filter added in WP v5.3.
		 *
		 * The 'wp_image_resize_identical_dimensions' filter always returns for resized dimensions that are close to the
		 * original image dimensions.
		 */
		public function maybe_resize_fuzzy_dimensions( $resize ) {

			$img_info = (array) self::get_image_src_args();

			if ( empty( $img_info[ 'size_name' ] ) || 0 !== strpos( $img_info[ 'size_name' ], $this->p->lca . '-' ) ) {

				return $resize;
			}

			return true;
		}

		/**
		 * $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment );
		 */
		public function add_attachment_image_attributes( $attr, $attachment ) {

			$attr[ 'data-wp-pid' ] = $attachment->ID;

			return $attr;
		}

		/**
		 * $html = apply_filters( 'get_image_tag', $html, $id, $alt, $title, $align, $size );
		 */
		public function get_image_tag( $html, $id, $alt, $title, $align, $size ) {

			$html = SucomUtil::insert_html_tag_attributes( $html, array(
				'data-wp-pid' => $id,
			) );

			return $html;
		}

		/**
		 * $size_name must be a string.
		 */
		public function get_post_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'         => $num,
					'size_name'   => $size_name,
					'post_id'     => $post_id,
					'check_dupes' => $check_dupes,
					'md_pre'      => $md_pre,
				) );
			}

			$mt_ret = array();

			if ( ! empty( $post_id ) ) {

				/**
				 * get_og_images() provides filter hooks for additional image ids and urls.
				 *
				 * Unless $md_pre is 'none', get_og_images() will fallback to using the 'og' custom meta.
				 */
				$mt_ret = array_merge( $mt_ret, $this->p->post->get_og_images( $num, $size_name, $post_id, $check_dupes, $md_pre ) );
			}

			/**
			 * Allow for empty post_id in order to execute featured / attached image filters for modules.
			 */
			if ( ! $this->p->util->is_maxed( $mt_ret, $num ) ) {

				$num_diff = SucomUtil::count_diff( $mt_ret, $num );

				$mt_ret = array_merge( $mt_ret, $this->get_featured( $num_diff, $size_name, $post_id, $check_dupes ) );
			}

			/**
			 * 'wpsso_attached_images' filter is used by the buddypress module.
			 */
			if ( ! $this->p->util->is_maxed( $mt_ret, $num ) ) {

				$num_diff = SucomUtil::count_diff( $mt_ret, $num );

				$mt_ret = array_merge( $mt_ret, $this->get_attached_images( $num_diff, $size_name, $post_id, $check_dupes ) );
			}

			return $mt_ret;
		}

		/**
		 * $size_name must be a string.
		 */
		public function get_featured( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'         => $num,
					'size_name'   => $size_name,
					'post_id'     => $post_id,
					'check_dupes' => $check_dupes,
				) );
			}

			$mt_ret = array();

			if ( ! empty( $post_id ) ) {

				/**
				 * Avoid making duplicate queries to the database.
				 */
				static $local_cache_featured_ids = array();

				if ( isset( $local_cache_featured_ids[ $post_id ] ) ) {	// False or image ID.

					$pid = $local_cache_featured_ids[ $post_id ];

				/**
				 * If the post ID is an attachment page, then use the post ID as the image ID.
				 */
				} elseif ( ( is_attachment( $post_id ) || 'attachment' === get_post_type( $post_id ) ) && wp_attachment_is_image( $post_id ) ) {
	
					$pid = $local_cache_featured_ids[ $post_id ] = $post_id;
	
				} elseif ( $this->p->avail[ 'wp' ][ 'featured' ] && has_post_thumbnail( $post_id ) ) {
	
					$pid = $local_cache_featured_ids[ $post_id ] = get_post_thumbnail_id( $post_id );
	
				} else {
	
					$pid = $local_cache_featured_ids[ $post_id ] = false;
				}

				if ( ! empty( $pid ) ) {	// False or image ID.

					/**
					 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
					 */
					$mt_single_image = $this->get_mt_single_image_src( $pid, $size_name, $check_dupes );

					if ( ! empty( $mt_single_image[ 'og:image:url' ] ) ) {

						$this->p->util->push_max( $mt_ret, $mt_single_image, $num );

						// Continue and apply the 'wpsso_og_featured' filter.
					}
				}
			}

			return apply_filters( $this->p->lca . '_og_featured', $mt_ret, $num, $size_name, $post_id, $check_dupes );
		}

		/**
		 * $size_name must be a string.
		 */
		public function get_attached_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'         => $num,
					'size_name'   => $size_name,
					'post_id'     => $post_id,
					'check_dupes' => $check_dupes,
				) );
			}

			$mt_ret = array();

			if ( ! empty( $post_id ) ) {

				/**
				 * Avoid making duplicate queries to the database.
				 */
				static $local_cache_attached_ids = array();

				if ( ! isset( $local_cache_attached_ids[ $post_id ] ) ) {

					$local_cache_attached_ids[ $post_id ] = array();

					$images = get_children( array(
						'post_parent'    => $post_id,
						'post_type'      => 'attachment',
						'post_mime_type' => 'image'
					), OBJECT );	// OBJECT, ARRAY_A, or ARRAY_N.

					foreach ( $images as $attachment ) {

						if ( ! empty( $attachment->ID ) ) {

							$local_cache_attached_ids[ $post_id ][] = $attachment->ID;
						}
					}

					rsort( $local_cache_attached_ids[ $post_id ], SORT_NUMERIC );

					$filter_name = $this->p->lca . '_attached_image_ids';

					$local_cache_attached_ids[ $post_id ] = array_unique( apply_filters( $filter_name, $local_cache_attached_ids[ $post_id ], $post_id ) );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'found ' . count( $local_cache_attached_ids[ $post_id ] ) . ' attached images for post_id ' . $post_id );
				}

				foreach ( $local_cache_attached_ids[ $post_id ] as $pid ) {

					/**
					 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
					 */
					$mt_single_image = $this->get_mt_single_image_src( $pid, $size_name, $check_dupes );

					if ( ! empty( $mt_single_image[ 'og:image:url' ] ) ) {

						if ( $this->p->util->push_max( $mt_ret, $mt_single_image, $num ) ) {

							break;	// Stop here and apply the 'wpsso_attached_images' filter.
						}
					}
				}
			}

			/**
			 * The 'wpsso_attached_images' filter is used by the buddypress module.
			 */
			return apply_filters( $this->p->lca . '_attached_images', $mt_ret, $num, $size_name, $post_id, $check_dupes );
		}

		/**
		 * $size_name must be a string.
		 */
		public function get_content_images( $num = 0, $size_name = 'thumbnail', $mod = true, $check_dupes = true, $content = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'             => $num,
					'size_name'       => $size_name,
					'mod'             => $mod,
					'check_dupes'     => $check_dupes,
					'strlen(content)' => strlen( $content ),
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->p->util->get_page_mod( $mod );
			}

			$mt_ret = array();

			/**
			 * Allow custom content to be passed as an argument in $content.
			 *
			 * Allow empty post IDs to get additional content from filter hooks.
			 */
			if ( empty( $content ) ) {

				$content = $this->p->page->get_the_content( $mod );

				$content_passed = false;

			} else {
				$content_passed = true;
			}

			if ( empty( $content ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: empty post content' );
				}

				return $mt_ret;
			}

			$content_img_preg = $this->default_content_img_preg;

			$mt_single_image = SucomUtil::get_mt_image_seed();

			/**
			 * Allow the html_tag and pid_attr regex to be modified.
			 */
			foreach( array( 'html_tag', 'pid_attr' ) as $type ) {

				$filter_name = $this->p->lca . '_content_image_preg_' . $type;	// No need to sanitize.

				if ( false !== has_filter( $filter_name ) ) {

					$content_img_preg[ $type ] = apply_filters( $filter_name, $this->default_content_img_preg[ $type ] );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'filtered image preg ' . $type . ' = "' . $content_img_preg[ $type ] . '"' );
					}
				}
			}

			/**
			 * <img/> attributes in order of preference.
			 */
			if ( preg_match_all( '/<((' . $content_img_preg[ 'html_tag' ] . ')[^>]*? (' . $content_img_preg[ 'pid_attr' ] . ')=[\'"]([0-9]+)[\'"]|' . 
				'(img)[^>]*? (data-lazy-src|data-share-src|data-src|src)=[\'"]([^\'"]+)[\'"])[^>]*>/s',
					$content, $all_matches, PREG_SET_ORDER ) ) {

				$content_img_max = SucomUtil::get_const( 'WPSSO_CONTENT_IMAGES_MAX_LIMIT', 5 );

				if ( count( $all_matches ) > $content_img_max ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'limiting matches returned from ' . count( $all_matches ) . ' to ' . $content_img_max );
					}

					$all_matches = array_splice( $all_matches, 0, $content_img_max );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' matching <' . $content_img_preg[ 'html_tag' ] . '/> html tag(s) found' );
				}

				foreach ( $all_matches as $img_num => $img_arr ) {

					$tag_value = $img_arr[ 0 ];

					if ( empty( $img_arr[ 5 ] ) ) {

						$tag_name   = $img_arr[ 2 ];	// img
						$attr_name  = $img_arr[ 3 ];	// data-wp-pid
						$attr_value = $img_arr[ 4 ];	// id

					} else {

						$tag_name   = $img_arr[ 5 ];	// img
						$attr_name  = $img_arr[ 6 ];	// data-share-src|data-lazy-src|data-src|src
						$attr_value = $img_arr[ 7 ];	// url
					}

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'match ' . $img_num . ': ' . $tag_name . ' ' . $attr_name . '="' . $attr_value . '"' );
					}

					switch ( $attr_name ) {

						/**
						 * WordPress media library image id.
						 */
						case 'data-wp-pid':

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'WP image attribute id found: ' . $attr_value );
							}

							$this->add_mt_single_image_src( $mt_single_image, $attr_value, $size_name, false );

							break;

						/**
						 * Check for other data attributes like 'data-ngg-pid'.
						 */
						case ( preg_match( '/^' . $content_img_preg[ 'pid_attr' ] . '$/', $attr_name ) ? true : false ):

							// Filter hook for 3rd party modules to return image information.
							$filter_name = SucomUtil::sanitize_hookname( $this->p->lca . '_get_content_' . $tag_name . '_' . $attr_name );

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'applying ' . $filter_name . ' filters' );
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

							break;

						/**
						 * data-share-src | data-lazy-src | data-src | src
						 */
						default:

							/**
							 * Prevent duplicates by silently ignoring ngg images (already processed by the ngg module).
							 */
							if ( ! empty( $this->p->avail[ 'media' ][ 'ngg' ] ) && 
								! empty( $this->p->m[ 'media' ][ 'ngg' ] ) &&
									( preg_match( '/ class=[\'"]ngg[_-]/', $tag_value ) ||
										preg_match( '/^(' . $content_img_preg[ 'ngg_src' ] . ')$/', $attr_value ) ) ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'silently ignoring ngg image for ' . $attr_name );
								}

								break;	// Stop here.
							}

							/**
							 * Recognize gravatar images in the content.
							 */
							if ( preg_match( '/^https?:?\/\/[^\/]*gravatar\.com\/avatar\/([a-zA-Z0-9]+)/', $attr_value, $match ) ) {

								$size_info = $this->p->util->get_size_info( $size_name );

								$mt_single_image[ 'og:image:url' ] = 'https://secure.gravatar.com/avatar/' . $match[ 1 ] .
									'.jpg?s=' . $size_info[ 'width' ] . '&d=404&r=G';

								$mt_single_image[ 'og:image:width' ] = $size_info[ 'width' ] > WPSSO_GRAVATAR_IMAGE_SIZE_MAX ?
									WPSSO_GRAVATAR_IMAGE_SIZE_MAX : $size_info[ 'width' ];

								$mt_single_image[ 'og:image:height' ] = $mt_single_image[ 'og:image:width' ];

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'gravatar image found: ' . $mt_single_image[ 'og:image:url' ] );
								}

								break;	// Stop here.
							}

							/**
							 * Check for image id in class for old content w/o the data-wp-pid attribute.
							 */
							if ( preg_match( '/class="[^"]+ wp-image-([0-9]+)/', $tag_value, $match ) ) {

								$this->add_mt_single_image_src( $mt_single_image, $match[ 1 ], $size_name, false );

								break;	// Stop here.

							} else {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'using attribute value for og:image = ' . $attr_value . 
										' (' . WPSSO_UNDEF . 'x' . WPSSO_UNDEF . ')' );
								}

								$mt_single_image = array(
									'og:image:url'    => $attr_value,
									'og:image:width'  => WPSSO_UNDEF,
									'og:image:height' => WPSSO_UNDEF,
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

							/**
							 * Get the actual width and height of the image using http / https.
							 */
							if ( empty( $mt_single_image[ 'og:image:width' ] ) || $mt_single_image[ 'og:image:width' ] < 0 ||
								empty( $mt_single_image[ 'og:image:height' ] ) || $mt_single_image[ 'og:image:height' ] < 0 ) {

								/**
								 * Add correct image sizes for the image URL using getimagesize().
								 */
								$this->p->util->add_image_url_size( $mt_single_image );

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'returned / fetched image url size: ' . 
										$mt_single_image[ 'og:image:width' ] . 'x' . $mt_single_image[ 'og:image:height' ] );
								}

								/**
								 * No use checking / retrieving the image size twice.
								 */
								if ( $mt_single_image[ 'og:image:width' ] === WPSSO_UNDEF &&
									$mt_single_image[ 'og:image:height' ] === WPSSO_UNDEF ) {

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

								/**
								 * Check if image exceeds hard-coded limits (dimensions, ratio, etc.).
								 */
								$img_within_limits = $this->is_image_within_config_limits( $mt_single_image[ 'og:image:url' ],
									$size_name, $mt_single_image[ 'og:image:width' ], $mt_single_image[ 'og:image:height' ],
										__( 'Content', 'wpsso' ) );

							} elseif ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipped image size limits for ' . $mt_single_image[ 'og:image:url' ] . 
									' (' . $mt_single_image[ 'og:image:width' ] . 'x' . $mt_single_image[ 'og:image:height' ] . ')' );
							}

							if ( ! apply_filters( $this->p->lca . '_content_accept_img_dims', $img_within_limits,
								$mt_single_image, $size_name, $attr_name, $content_passed ) ) {

								$mt_single_image = array();
							}

							break;
					}

					if ( ! empty( $mt_single_image[ 'og:image:url' ] ) ) {

						$mt_single_image[ 'og:image:url' ] = $this->p->util->fix_relative_url( $mt_single_image[ 'og:image:url' ] );

						$mt_single_image[ 'og:image:url' ] = apply_filters( $this->p->lca . '_rewrite_image_url', $mt_single_image[ 'og:image:url' ] );

						if ( ! $check_dupes || $this->p->util->is_uniq_url( $mt_single_image[ 'og:image:url' ], $size_name ) ) {

							if ( $this->p->util->push_max( $mt_ret, $mt_single_image, $num ) ) {

								return $mt_ret;
							}
						}
					}
				}

				return $mt_ret;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no matching <' . $content_img_preg[ 'html_tag' ] . '/> html tag(s) found' );
			}

			return $mt_ret;
		}

		/**
		 * $size_name must be a string.
		 */
		public function get_default_images( $num = 1, $size_name = 'thumbnail', $check_dupes = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'         => $num,
					'size_name'   => $size_name,
					'check_dupes' => $check_dupes,
				) );
			}

			$mt_ret = array();

			foreach ( array( 'id', 'id_pre', 'url', 'url:width', 'url:height' ) as $key ) {

				$def_img[ $key ] = empty( $this->p->options[ 'og_def_img_' . $key ] ) ? '' : $this->p->options[ 'og_def_img_' . $key ];
			}

			if ( empty( $def_img[ 'id' ] ) && empty( $def_img[ 'url' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no default image defined' );
				}

				return $mt_ret;
			}

			$mt_single_image = SucomUtil::get_mt_image_seed();

			if ( ! empty( $def_img[ 'id' ] ) ) {

				$def_img[ 'id' ] = $def_img[ 'id_pre' ] === 'ngg' ? 'ngg-' . $def_img[ 'id' ] : $def_img[ 'id' ];

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using default image pid: ' . $def_img[ 'id' ] );
				}

				$this->add_mt_single_image_src( $mt_single_image, $def_img[ 'id' ], $size_name, $check_dupes );
			}

			if ( empty( $mt_single_image[ 'og:image:url' ] ) && ! empty( $def_img[ 'url' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using default image url: ' . $def_img[ 'url' ] );
				}

				$mt_single_image = array(
					'og:image:url'    => $def_img[ 'url' ],
					'og:image:width'  => $def_img[ 'url:width' ],
					'og:image:height' => $def_img[ 'url:height' ],
				);
			}

			if ( ! empty( $mt_single_image[ 'og:image:url' ] ) ) {

				if ( $this->p->util->push_max( $mt_ret, $mt_single_image, $num ) ) {

					return $mt_ret;
				}
			}

			return $mt_ret;
		}

		/**
		 * $size_name must be a string.
		 */
		public function get_attachment_image( $num = 0, $size_name = 'thumbnail', $attachment_id, $check_dupes = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'           => $num,
					'size_name'     => $size_name,
					'attachment_id' => $attachment_id,
					'check_dupes'   => $check_dupes,
				) );
			}

			$mt_ret = array();

			if ( ! empty( $attachment_id ) ) {

				if ( wp_attachment_is_image( $attachment_id ) ) {

					/**
					 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
					 */
					$mt_single_image = $this->get_mt_single_image_src( $attachment_id, $size_name, $check_dupes );

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

		/**
		 * Return only the image URL, which will be the first array element returned by get_attachment_image_src().
		 */
		public function get_attachment_image_url( $pid, $size_name = 'thumbnail', $check_dupes = true ) {

			$image_src = $this->get_attachment_image_src( $pid, $size_name, $check_dupes );

			foreach ( $image_src as $num => $value ) {

				return $value;
			}

			return null;	// Return null if array is empty.
		}

		/**
		 * Note that that every return in this method must call self::reset_image_src_args().
		 */
		public function get_attachment_image_src( $pid, $size_name = 'thumbnail', $check_dupes = true ) {

			/**
			 * Save arguments for the 'image_make_intermediate_size' and 'image_resize_dimensions' filters.
			 */
			self::set_image_src_args( $args = array(
				'pid'         => $pid,
				'size_name'   => $size_name,
				'check_dupes' => $check_dupes,
			) );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( $args );
			}

			$size_info = $this->p->util->get_size_info( $size_name, $pid );

			if ( empty( $size_info[ 'width' ] ) && empty( $size_info[ 'height' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: missing size information for ' . $size_name );
				}

				return self::reset_image_src_args();
			}

			$img_url       = '';
			$img_width     = WPSSO_UNDEF;
			$img_height    = WPSSO_UNDEF;
			$use_full_size = false;

			if ( $this->p->avail[ 'media' ][ 'ngg' ] && 0 === strpos( $pid, 'ngg-' ) ) {

				if ( ! empty( $this->p->m[ 'media' ][ 'ngg' ] ) ) {

					return self::reset_image_src_args( $this->p->m[ 'media' ][ 'ngg' ]->get_image_src( $pid, $size_name, $check_dupes ) );

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'ngg module is not available: image id ' . $attr_value . ' ignored' );
					}

					return self::reset_image_src_args();
				}

			} elseif ( ! wp_attachment_is_image( $pid ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: attachment ' . $pid . ' is not an image' );
				}

				return self::reset_image_src_args();
			}

			$img_meta = wp_get_attachment_metadata( $pid );
			$img_alt  = get_post_meta( $pid, '_wp_attachment_image_alt', true );

			/**
			 * Check to see if the full size image width / height matches the resize width / height we require. If so,
			 * then use the full size image instead.
			 */
			if ( isset( $img_meta[ 'file' ] ) && isset( $img_meta[ 'width' ] ) && isset( $img_meta[ 'height' ] ) ) {

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
								' (' . $size_info[ 'dimensions' ] . ')' );
						}

						$use_full_size = true;
					}
				}

			} else {

				$img_lib       = __( 'Media Library', 'wpsso' );
				$img_edit_url  = get_edit_post_link( $pid );
				$img_title     = get_the_title( $pid );
				$img_func_url  = __( 'https://developer.wordpress.org/reference/functions/wp_get_attachment_metadata/', 'wpsso' );
				$img_func_name = 'wp_get_attachment_metadata()';
				$img_regen_msg = sprintf( __( 'You may consider regenerating the thumbnails of all WordPress Media Library images using one of <a href="%s">several available plugins from WordPress.org</a>.', 'wpsso' ), 'https://wordpress.org/plugins/search/regenerate+thumbnails/' );

				if ( isset( $img_meta[ 'file' ] ) ) {

					/**
					 * Image dimensions are missing, but full size image path is present.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'full size image ' . $img_meta[ 'file' ] . ' dimensions missing from image metadata' );
					}

					/**
					 * Add notice only if the admin notices have not already been shown.
					 */
					if ( $this->p->notice->is_admin_pre_notices() ) {

						$notice_msg = sprintf( __( 'Possible %1$s corruption detected &mdash; the full size image dimensions for <a href="%2$s">image ID %3$s</a> are missing from the image metadata returned by the <a href="%4$s">WordPress %5$s function</a>.', 'wpsso' ), $img_lib, $img_edit_url, $pid, $img_func_url, '<code>' . $img_func_name . '</code>' ) . ' ' . $img_regen_msg;

						$notice_key = 'full-size-image-' . $pid . '-dimensions-missing';

						$this->p->notice->err( $notice_msg, null, $notice_key, $dismiss_time = WEEK_IN_SECONDS );
					}

				} else {

					/**
					 * Both the image dimensions and full size image path are missing.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'full size image file path for ' . $pid . ' missing from image metadata' );
					}

					/**
					 * Add notice only if the admin notices have not already been shown.
					 */
					if ( $this->p->notice->is_admin_pre_notices() ) {

						$notice_msg = sprintf( __( 'Possible %1$s corruption detected &mdash; the full size image file path for <a href="%2$s">image ID %3$s</a> is missing from the image metadata returned by the <a href="%4$s">WordPress %5$s function</a>.', 'wpsso' ), $img_lib, $img_edit_url, $pid, $img_func_url, '<code>' . $img_func_name . '</code>' ) . ' ' . $img_regen_msg;

						$notice_key = 'full-size-image-' . $pid . '-file-path-missing';

						$this->p->notice->err( $notice_msg, null, $notice_key, $dismiss_time = WEEK_IN_SECONDS );
					}
				}
			}

			/**
			 * Only resize our own custom image sizes.
			 */
			if ( 0 === strpos( $size_name, $this->p->lca . '-' ) ) {

				if ( ! $use_full_size ) {

					$is_accurate_filename = false;
					$is_accurate_width    = false;
					$is_accurate_height   = false;

					/**
					 * Make sure the metadata contains complete image information for the requested size.
					 */
					if ( ! empty( $img_meta[ 'sizes' ][ $size_name ] ) &&
						! empty( $img_meta[ 'sizes' ][ $size_name ][ 'file' ] ) &&
						! empty( $img_meta[ 'sizes' ][ $size_name ][ 'width' ] )  &&
						! empty( $img_meta[ 'sizes' ][ $size_name ][ 'height' ] ) ) {

						/**
						 * By default, WordPress adds only the resolution of the resized image to the file
						 * name. If the image size is ours, then add crop information to the file name as
						 * well. This allows for different cropped versions for the same image resolution.
						 */
						$img_filename = $this->get_cropped_image_filename( $img_meta[ 'sizes' ][ $size_name ][ 'file' ], $size_info );

						$is_accurate_filename = $img_filename === $img_meta[ 'sizes' ][ $size_name ][ 'file' ] ? true : false;
						$is_accurate_width    = $size_info[ 'width' ] === $img_meta[ 'sizes' ][ $size_name ][ 'width' ] ? true : false;
						$is_accurate_height   = $size_info[ 'height' ] === $img_meta[ 'sizes' ][ $size_name ][ 'height' ] ? true : false;

						/**
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

								/**
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

					/**
					 * Depending on cropping, one or both sides of the image must be accurate. If not, attempt
					 * to create a resized image by calling image_make_intermediate_size().
					 */
					if ( ! $is_accurate_filename ||
						( ! $size_info[ 'is_cropped' ] && ( ! $is_accurate_width && ! $is_accurate_height ) ) ||
							( $size_info[ 'is_cropped' ] && ( ! $is_accurate_width || ! $is_accurate_height ) ) ) {

						if ( $this->can_make_intermediate_size( $img_meta, $size_info ) ) {

							$img_lib       = __( 'Media Library', 'wpsso' );
							$img_func_url  = __( 'https://developer.wordpress.org/reference/functions/image_make_intermediate_size/', 'wpsso' );
							$img_func_name = 'image_make_intermediate_size()';
							$fullsizepath  = get_attached_file( $pid );

							$mtime_start = microtime( true );

							/**
							 * image_make_intermediate_size() resizes an image to make a thumbnail or
							 * intermediate size.
							 *
							 * Returns (array|false) metadata array on success, false if no image was created.
							 */
							$resized_meta = image_make_intermediate_size( $fullsizepath,
								$size_info[ 'width' ], $size_info[ 'height' ], $size_info[ 'crop' ] );

							$mtime_total = microtime( true ) - $mtime_start;

							$mtime_max = SucomUtil::get_const( 'WPSSO_IMAGE_MAKE_SIZE_MAX_TIME', 1.00 );

							/**
							 * Issue warning for slow image_make_intermediate_size() request.
							 */
							if ( $mtime_max > 0 && $mtime_total > $mtime_max ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( sprintf( 'slow WordPress function detected - %1$s took %2$0.3f secs to make size "%3$s" from %4$s', $img_func_name, $mtime_total, $size_name, $fullsizepath ) );
								}
							}

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'WordPress ' . $img_func_name . ' reported ' .
									( false === $resized_meta ? 'failure' : 'success' ) );
							}

							/**
							 * Returns (array|false) metadata array on success, false if no image was created.
							 */
							if ( false === $resized_meta ) {

								/**
								 * Add notice only if the admin notices have not already been shown.
								 */
								if ( $this->p->notice->is_admin_pre_notices() ) {

									$notice_msg = sprintf( __( 'Possible %1$s corruption detected &mdash; the <a href="%2$s">WordPress %3$s function</a> failed to create the "%4$s" image size (%5$s) from %6$s.', 'wpsso' ), $img_lib, $img_func_url, '<code>' . $img_func_name . '</code>', $size_name, $size_info[ 'dimensions' ], $fullsizepath ) . ' ';

									$notice_msg .= sprintf( __( 'You may consider regenerating the thumbnails of all WordPress Media Library images using one of <a href="%s">several available plugins from WordPress.org</a>.', 'wpsso' ), 'https://wordpress.org/plugins/search/regenerate+thumbnails/' );

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

						unset ( $img_meta[ 'sizes' ][ $size_name ] );

						wp_update_attachment_metadata( $pid, $img_meta );
					}
				}
			}

			/**
			 * Some image_downsize hooks may return only 3 elements, so use array_pad() to sanitize the returned array.
			 */
			$img_downsize = array_pad( image_downsize( $pid, ( $use_full_size ? 'full' : $size_name ) ), 4, null );

			list(
				$img_url,
				$img_width,
				$img_height,
				$img_intermediate
			) = apply_filters( $this->p->lca . '_image_downsize', $img_downsize, $pid, $size_name );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'image_downsize for ' . $size_name . ' returned ' . $img_url . ' (' . $img_width . 'x' . $img_height . ')' );
			}

			if ( empty( $img_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: image_downsize for ' . $size_name . ' returned an empty url' );
				}

				return self::reset_image_src_args();

			} elseif ( $check_dupes && $this->p->util->is_dupe_url( $img_url, $size_name ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: duplicate ' . $size_name . ' url: ' . $img_url );
				}

				return self::reset_image_src_args();
			}

			/**
			 * Check if image exceeds hard-coded limits (dimensions, ratio, etc.).
			 */
			$img_within_limits = $this->is_image_within_config_limits( $pid, $size_name, $img_width, $img_height );

			if ( apply_filters( $this->p->lca . '_attached_accept_img_dims', $img_within_limits, $img_url, $img_width, $img_height, $size_name, $pid ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying rewrite_image_url filter for ' . $img_url );
				}

				$img_url = $this->p->util->fix_relative_url( $img_url );

				$img_url = apply_filters( $this->p->lca . '_rewrite_image_url', $img_url );

				return self::reset_image_src_args( array( $img_url, $img_width, $img_height, $size_info[ 'is_cropped' ], $pid, $img_alt, $size_name ) );
			}

			return self::reset_image_src_args();
		}

		/**
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 */
		public function get_mt_pid_images( $pid, $size_names = 'thumbnail', $check_dupes = true, $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'pid'         => $pid,
					'size_names'  => $size_names,
					'check_dupes' => $check_dupes,
					'mt_pre'      => $mt_pre,
				) );
			}

			$mt_ret = array();

			$size_names = $this->p->util->get_image_size_names( $size_names );	// Always returns an array.

			foreach ( $size_names as $size_name ) {

				/**
				 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
				 */
				$mt_single_image = $this->get_mt_single_image_src( $pid, $size_name, $check_dupes, $mt_pre );

				if ( ! empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {

					$mt_ret[] = $mt_single_image;
				}
			}

			return $mt_ret;

		}

		/**
		 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
		 *
		 * $size_name must be a string.
		 */
		public function get_mt_single_image_src( $pid, $size_name = 'thumbnail', $check_dupes = true, $mt_pre = 'og' ) {

			$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );
		
			$this->add_mt_single_image_src( $mt_single_image, $pid, $size_name, $check_dupes, $mt_pre );

			return $mt_single_image;
		}

		/**
		 * $size_name must be a string.
		 */
		public function add_mt_single_image_src( array &$mt_single_image, $pid, $size_name = 'thumbnail', $check_dupes = true, $mt_pre = 'og' ) {

			list(
				$mt_single_image[ $mt_pre . ':image:url' ],
				$mt_single_image[ $mt_pre . ':image:width' ],
				$mt_single_image[ $mt_pre . ':image:height' ],
				$mt_single_image[ $mt_pre . ':image:cropped' ],
				$mt_single_image[ $mt_pre . ':image:id' ],
				$mt_single_image[ $mt_pre . ':image:alt' ],
				$mt_single_image[ $mt_pre . ':image:size_name' ],
			) = $this->get_attachment_image_src( $pid, $size_name, $check_dupes );
		}

		/**
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 */
		public function get_mt_opts_images( $opts, $size_names = null, $img_pre = 'og_img', $key_num = null, $mt_pre = 'og' ) {

			$img_opts = array();

			foreach ( array( 'id', 'id_pre', 'url', 'url:width', 'url:height' ) as $key ) {

				$key_suffix     = $key_num === null ? $key : $key . '_' . $key_num;	// Use a numbered multi-option key.
				$opt_key        = $img_pre . '_' . $key_suffix;
				$opt_key_locale = SucomUtil::get_key_locale( $img_pre . '_' . $key_suffix, $opts );

				$img_opts[ $key ] = empty( $opts[ $opt_key_locale ] ) ? '' : $opts[ $opt_key_locale ];
			}

			$mt_ret = array();

			if ( ! empty( $img_opts[ 'id' ] ) && ! empty( $size_names ) ) {

				$pid = 'ngg' === $img_opts[ 'id_pre' ] ? 'ngg-' . $img_opts[ 'id' ] : $img_opts[ 'id' ];

				$mt_ret = $this->get_mt_pid_images( $pid, $size_names, $check_dupes = false, $mt_pre );

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

		/**
		 * Used by WpssoOptions->check_banner_image_size() and WpssoOrgFilters->check_banner_image_size().
		 */
		public function get_mt_img_pre_url( $opts, $img_pre = 'og_img', $key_num = null, $mt_pre = 'og' ) {

			/**
			 * $size_name is false to ignore image IDs and only use image URLs.
			 */
			$mt_ret = $this->get_mt_opts_images( $opts, $size_name = false, $img_pre, $key_num, $mt_pre );

			return isset( $mt_ret[ 0 ] ) ? $mt_ret[ 0 ] : array();
		}

		/**
		 * Deprecated on 2020/08/10.
		 */
		public function get_opts_single_image( $opts, $size_name = null, $img_pre = 'og_img', $key_num = null ) {

			$mt_ret = $this->get_mt_opts_images( $opts, $size_name, $img_pre, $key_num );

			return isset( $mt_ret[ 0 ] ) ? $mt_ret[ 0 ] : array();
		}

		/**
		 * Returns an array of single video associative arrays.
		 */
		public function get_content_videos( $num = 0, $mod = true, $check_dupes = true, $content = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'         => $num,
					'mod'         => $mod,
					'check_dupes' => $check_dupes,
					'content'     => strlen( $content ) . ' chars',
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->p->util->get_page_mod( $mod );
			}

			$mt_videos = array();

			/**
			 * Allow custom content to be passed as an argument in $content.
			 *
			 * Allow empty post IDs to get additional content from filter hooks.
			 */
			if ( empty( $content ) ) {

				$content = $this->p->page->get_the_content( $mod );

				$content_passed = false;

			} else {

				$content_passed = true;
			}

			if ( empty( $content ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: empty post content' );
				}

				return $mt_videos;
			}

			/**
			 * Detect standard video tags.
			 *
			 * Hook the 'wpsso_content_videos' filter for additional html5 / javascript embed methods.
			 */
			$figure_block_matches = array();
			$iframe_embed_matches = array();

			if ( preg_match_all( '/<(figure) class="(wp-block-embed-[^ ]+) [^"]+ is-type-video [^"]+"><div class="wp-block-embed__wrapper">' . 
				' *([^ \'"<>]+\/(embed\/|embed_code\/|player\/|swf\/|v\/|videos?\/|video\.php\?)[^ \'"<>]+) *<\/div><\/figure>/i',
					$content, $figure_block_matches, PREG_SET_ORDER ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $figure_block_matches ) . ' <figure/> video html tag(s) found' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no <figure/> video html tag(s) found' );
				}
			}

			if ( preg_match_all( '/<(iframe|embed)[^<>]*? (data-lazy-src|data-share-src|data-src|src)=[\'"]' . 
				'([^ \'"<>]+\/(embed\/|embed_code\/|player\/|swf\/|v\/|videos?\/|video\.php\?)[^ \'"<>]+)[\'"][^<>]*>/i',
					$content, $iframe_embed_matches, PREG_SET_ORDER ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $iframe_embed_matches ) . ' <iframe|embed/> video html tag(s) found' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no <iframe|embed/> video html tag(s) found' );
				}
			}

			$all_matches = array_merge( $iframe_embed_matches, $figure_block_matches );

			if ( ! empty( $all_matches ) ) {

				$content_vid_max = SucomUtil::get_const( 'WPSSO_CONTENT_VIDEOS_MAX_LIMIT', 5 );

				if ( count( $all_matches ) > $content_vid_max ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'limiting matches returned from ' . count( $all_matches ) . ' to ' . $content_vid_max );
					}

					$all_matches = array_splice( $all_matches, 0, $content_vid_max );
				}

				foreach ( $all_matches as $media ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( '<' . $media[ 1 ] . '/> video html tag found ' . $media[ 2 ] . ' = ' . $media[ 3 ] );
					}

					if ( ! empty( $media[ 3 ] ) ) {

						if ( ! $check_dupes || $this->p->util->is_uniq_url( $media[ 3 ], 'content_video' ) ) {

							$args = array(
								'url'    => $media[ 3 ],
								'width'  => preg_match( '/ width=[\'"]?([0-9]+)[\'"]?/i', $media[ 0 ], $match ) ? $match[ 1 ] : WPSSO_UNDEF,
								'height' => preg_match( '/ height=[\'"]?([0-9]+)[\'"]?/i', $media[ 0 ], $match ) ? $match[ 1 ] : WPSSO_UNDEF,
							);

							/**
							 * Returns a single video associative array.
							 */
							$mt_single_video = $this->get_video_details( $args, $check_dupes );

							if ( ! empty( $mt_single_video ) ) {

								if ( $this->p->util->push_max( $mt_videos, $mt_single_video, $num ) ) {

									if ( $this->p->debug->enabled ) {

										$this->p->debug->log( 'returning ' . count( $mt_videos ) . ' videos' );
									}

									return $mt_videos;
								}
							}
						}
					}
				}
			}

			/**
			 * Additional filters / modules may detect other embedded video markup.
			 */
			$filter_name = $this->p->lca . '_content_videos';	// No need to sanitize.

			if ( false !== has_filter( $filter_name ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying ' . $filter_name . ' filters' );
				}

				/**
				 * Must return false or an array of associative arrays.
				 */
				if ( false !== ( $all_matches = apply_filters( $filter_name, false, $content ) ) ) {

					if ( is_array( $all_matches ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( count( $all_matches ) . ' videos returned by ' . $filter_name . ' filters' );
						}

						foreach ( $all_matches as $match_num => $args ) {

							if ( is_array( $args ) ) { // Just in case.

								if ( ! empty( $args[ 'url' ] ) ) {

									if ( ! $check_dupes || $this->p->util->is_uniq_url( $args[ 'url' ], 'content_video' ) ) {

										/**
										 * Returns a single video associative array.
										 */
										$mt_single_video = $this->get_video_details( $args, $check_dupes );

										if ( ! empty( $mt_single_video ) ) {

											if ( $this->p->util->push_max( $mt_videos, $mt_single_video, $num ) ) {

												if ( $this->p->debug->enabled ) {

													$this->p->debug->log( 'returning ' . count( $mt_videos ) . ' videos' );
												}

												return $mt_videos;
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

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( $filter_name . ' filter did not return false or an array' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( $filter_name . ' filter returned false (no videos found)' );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning ' . count( $mt_videos ) . ' videos' );
			}

			return $mt_videos;
		}

		/**
		 * Returns a single video associative array.
		 */
		public function get_video_details( array $args, $check_dupes = true, $fallback = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Make sure we have all array keys defined.
			 */
			$args = array_merge( array(
				'url'      => '',
				'width'    => WPSSO_UNDEF,
				'height'   => WPSSO_UNDEF,
				'type'     => '',
				'prev_url' => '',
				'post_id'  => null,
				'api'      => '',
			), $args );

			if ( empty( $args[ 'url' ] ) ) {

				return array();
			}

			$filter_name = $this->p->lca . '_video_details';	// No need to sanitize.

			/**
			 * Maybe filter using a specific API library hook.
			 */
			if ( ! empty( $args[ 'api' ] ) ) {

				$filter_name .= '_' . SucomUtil::sanitize_hookname( $args[ 'api' ] );
			}

			$mt_single_video = array_merge( SucomUtil::get_mt_video_seed(), array(
				'og:video:width'  => $args[ 'width' ],	// Default width.
				'og:video:height' => $args[ 'height' ],	// Default height.
			) );

			$mt_single_video = apply_filters( $filter_name, $mt_single_video, $args );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'single video after filters', $mt_single_video );
			}

			if ( isset( $mt_single_video[ 'al:web:url' ] ) ) {	// Just in case.

				if ( $mt_single_video[ 'al:web:url' ] === '' ) {

					$mt_single_video[ 'al:web:should_fallback' ] = '';	// False by default.
				}
			}

			/**
			 * Sanitation of media.
			 */
			foreach ( array( 'og:video', 'og:image' ) as $media_pre ) {

				$media_url = SucomUtil::get_first_mt_media_url( $mt_single_video, $media_pre );

				if ( 'og:video' === $media_pre ) {

					/**
					 * Fallback to the original video url.
					 */
					if ( empty( $media_url ) && $fallback ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no video returned by filters' );

							$this->p->debug->log( 'falling back to media url: ' . $args[ 'url' ] );
						}

						$media_url = $mt_single_video[ 'og:video:url' ] = $args[ 'url' ];
					}

					/**
					 * Check for an empty mime_type.
					 */
					if ( empty( $mt_single_video[ 'og:video:type' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'og:video:type is empty - using URL to determine the mime-type' );
						}

						/**
						 * Check for filename extension, slash, or common words (in that order), followed
						 * by an optional query string (which is ignored).
						 */
						if ( preg_match( '/(\.[a-z0-9]+|\/|\/embed\/.*|\/iframe\/.*)(\?[^\?]*)?$/', $media_url, $match ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'matched url substr "' . $match[ 1 ] . '"' );
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

				$have_media[ $media_pre ] = empty( $media_url ) ? false : true;

				/**
				 * Remove all meta tags if there's no media URL or media is a duplicate.
				 */
				if ( ! $have_media[ $media_pre ] || ( $check_dupes && ! $this->p->util->is_uniq_url( $media_url, 'video_details' ) ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no media url or duplicate media - removing ' . $media_pre . ' meta tags' );
					}

					foreach( SucomUtil::preg_grep_keys( '/^' . $media_pre . '(:.*)?$/', $mt_single_video ) as $k => $v ) {

						unset ( $mt_single_video[ $k ] );
					}

				/**
				 * If the media is an image, then check and maybe add missing sizes.
				 */
				} elseif ( $media_pre === 'og:image' ) {

					if ( empty( $mt_single_video[ 'og:image:width' ] ) || $mt_single_video[ 'og:image:width' ] < 0 ||
						empty( $mt_single_video[ 'og:image:height' ] ) || $mt_single_video[ 'og:image:height' ] < 0 ) {

						/**
						 * Add correct image sizes for the image URL using getimagesize().
						 */
						$this->p->util->add_image_url_size( $mt_single_video );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'returned / fetched video image url size: ' . 
								$mt_single_video[ 'og:image:width' ] . 'x' . $mt_single_video[ 'og:image:height' ] );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'video image width / height values: ' . 
							$mt_single_video[ 'og:image:width' ] . 'x' . $mt_single_video[ 'og:image:height' ] );
					}
				}
			}

			/**
			 * If there's no video or preview image, then return an empty array.
			 */
			if ( ! $have_media[ 'og:video' ] && ! $have_media[ 'og:image' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no media found - returning an empty array' );
				}

				return array();

			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning single video array' );
			}

			return $mt_single_video;
		}

		/**
		 * Modifies a single video associative array.
		 */
		public function add_og_video_from_url( array &$mt_single_video, $url ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Fetch HTML using the Facebook user agent to get Open Graph meta tags.
			 *
			 * get_html_head_meta( $request, $query, $libxml_errors, $curl_opts )
			 */
			$curl_opts = array(
				'CURLOPT_USERAGENT' => WPSSO_PHP_CURL_USERAGENT_FACEBOOK,
			);

			$metas = $this->p->util->get_html_head_meta( $url, $query = '//meta', $libxml_errors = false, $curl_opts );

			if ( isset( $metas[ 'meta' ] ) ) {

				foreach ( $metas as $m ) {		// Loop through all meta tags.

					foreach ( $m as $a ) {		// Loop through all attributes for that meta tag.

						$meta_type  = key( $a );
						$meta_name  = reset( $a );
						$meta_match = $meta_type . '-' . $meta_name;

						switch ( $meta_match ) {

							/**
							 * Use the property meta tag content as-is.
							 */
							case 'property-og:video:width':
							case 'property-og:video:height':
							case 'property-og:video:type':
							case ( strpos( $meta_match, 'property-al:' ) === 0 ? true : false ):	// Facebook AppLink.

								if ( ! empty( $a[ 'content' ] ) ) {

									$mt_single_video[ $a[ 'property' ] ] = $a[ 'content' ];
								}

								break;

							/**
							 * Add the property meta tag content as an array.
							 */
							case 'property-og:video:tag':

								if ( ! empty( $a[ 'content' ] ) ) {

									$mt_single_video[ $a[ 'property' ] ][] = $a[ 'content' ];	// Array of tags.
								}

								break;

							case 'property-og:image:secure_url':
							case 'property-og:image:url':
							case 'property-og:image':

								if ( ! empty( $a[ 'content' ] ) ) {

									/**
									 * Add the meta name as a query string to know where the value came from.
									 */
									$a[ 'content' ] = add_query_arg( 'meta', $meta_name, $a[ 'content' ] );

									if ( SucomUtil::is_https( $a[ 'content' ] ) ) {

										$mt_single_video[ 'og:image:secure_url' ]    = $a[ 'content' ];
										$mt_single_video[ 'og:video:thumbnail_url' ] = $a[ 'content' ];

									} else {

										$mt_single_video[ 'og:image:url' ] = $a[ 'content' ];

										if ( empty( $mt_single_video[ 'og:video:thumbnail_url' ] ) ) {

											$mt_single_video[ 'og:video:thumbnail_url' ] = $a[ 'content' ];
										}
									}

									$mt_single_video[ 'og:video:has_image' ] = true;
								}

								break;

							/**
							 * Add additional, non-standard properties, like og:video:title and og:video:description.
							 */
							case 'property-og:title':
							case 'property-og:description':

								if ( ! empty( $a[ 'content' ] ) ) {

									$og_key = 'og:video:' . substr( $a[ 'property' ], 3 );

									$mt_single_video[ $og_key ] = $this->p->util->cleanup_html_tags( $a[ 'content' ] );

									if ( $this->p->debug->enabled ) {

										$this->p->debug->log( 'adding ' . $og_key . ' = ' . $mt_single_video[ $og_key ] );
									}

									if ( empty( $mt_single_video[ 'og:image:alt' ] ) ) {

										$mt_single_video[ 'og:image:alt' ] = $mt_single_video[ $og_key ];
									}
								}

								break;

							/**
							 * twitter:app:name:iphone
							 * twitter:app:id:iphone
							 * twitter:app:url:iphone
							 */
							case ( strpos( $meta_match, 'name-twitter:app:' ) === 0 ? true : false ):	// Twitter Apps

								if ( ! empty( $a[ 'content' ] ) ) {

									if ( preg_match( '/^twitter:app:([a-z]+):([a-z]+)$/', $meta_name, $match ) ) {

										$mt_single_video[ 'og:video:' . $match[ 2 ] . '_' .
											$match[ 1 ] ] = SucomUtil::decode_html( $a[ 'content' ] );
									}
								}

								break;

							case 'itemprop-datePublished':

								if ( ! empty( $a[ 'content' ] ) ) {

									$mt_single_video[ 'og:video:upload_date' ] = gmdate( 'c', strtotime( $a[ 'content' ] ) );
								}

								break;

							case 'itemprop-embedUrl':
							case 'itemprop-embedURL':

								if ( ! empty( $a[ 'content' ] ) ) {

									$mt_single_video[ 'og:video:embed_url' ] = SucomUtil::decode_html( $a[ 'content' ] );
								}

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

		/**
		 * $img_mixed can be an image id or URL.
		 *
		 * $img_lib can be 'Media Library', 'NextGEN Gallery', 'Content', etc.
		 */
		public function is_image_within_config_limits( $img_mixed, $size_name, $img_width, $img_height, $img_lib = null ) {

			if ( 0 !== strpos( $size_name, $this->p->lca . '-' ) ) {	// Only check our own sizes.

				return true;
			}

			if ( null === $img_lib ) {	// Default to the WordPress Media Library.

				$img_lib = __( 'Media Library', 'wpsso' );
			}

			$img_ratio = 0;
			$img_label = $img_mixed;

			if ( is_numeric( $img_mixed ) ) {

				$img_edit_url = get_edit_post_link( $img_mixed );
				$img_title    = get_the_title( $img_mixed );
				$img_label    = sprintf( __( 'image ID %1$s (%2$s)', 'wpsso' ), $img_mixed, $img_title );
				$img_label    = empty( $img_edit_url ) ? $img_label : '<a href="' . $img_edit_url . '">' . $img_label . '</a>';

			} elseif ( false !== strpos( $img_mixed, '://' ) ) {

				if ( $img_width === WPSSO_UNDEF || $img_height === WPSSO_UNDEF ) {

					list(
						$img_width,
						$img_height,
						$img_type,
						$img_attr
					) = $this->p->util->get_image_url_info( $img_mixed );
				}

				$img_label = '<a href="' . $img_mixed . '">' . $img_mixed . '</a>';
				$img_label = sprintf( __( 'image URL %s', 'wpsso' ), $img_mixed );
			}

			/**
			 * Exit silently if the image width and/or height is not valid.
			 */
			if ( $img_width === WPSSO_UNDEF || $img_height === WPSSO_UNDEF ) {

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

			/**
			 * Check the maximum image aspect ratio.
			 */
			if ( $max_ratio && $img_ratio > $max_ratio ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . strtolower( $img_lib ) . ' ' . $img_mixed . ' rejected - ' . 
						$img_width . 'x' . $img_height . ' aspect ratio is equal to/or greater than ' . $max_ratio . ':1' );
				}

				/**
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$size_label = $this->p->util->get_image_size_label( $size_name );	// Returns pre-translated labels.

		 			/**
					 * $img_lib can be 'Media Library', 'NextGEN Gallery', 'Content', etc.
					 */
					$notice_msg = sprintf( __( '%1$s %2$s ignored &mdash; the resulting image of %3$s has an <strong>aspect ratio equal to/or greater than %4$d:1 allowed by the %5$s standard</strong>.', 'wpsso' ), $img_lib, $img_label, $img_width . 'x' . $img_height, $max_ratio, $size_label ). ' ';

					$notice_msg .= $this->p->msgs->get( 'notice-image-rejected', array( 'show_adjust_img_opts' => false ) );

					$notice_key = 'image_' . $img_mixed . '_' . $img_width . 'x' . $img_height . '_' . $size_name . '_ratio_greater_than_allowed';

					$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
				}

				return false;	// Image rejected.
			}

			/**
			 * Check the minimum image width and/or height.
			 */
			if ( ( $min_width > 0 || $min_height > 0 ) && ( $img_width < $min_width || $img_height < $min_height ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . strtolower( $img_lib ) . ' ' . $img_mixed . ' rejected - ' . 
						$img_width . 'x' . $img_height . ' smaller than minimum ' . $min_width . 'x' . $min_height . ' for ' . $size_name );
				}

				/**
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$size_label = $this->p->util->get_image_size_label( $size_name );	// Returns pre-translated labels.

		 			/**
					 * $img_lib can be 'Media Library', 'NextGEN Gallery', 'Content', etc.
					 */
					$notice_msg = sprintf( __( '%1$s %2$s ignored &mdash; the resulting image of %3$s is <strong>smaller than the minimum of %4$s allowed by the %5$s standard</strong>.', 'wpsso' ), $img_lib, $img_label, $img_width . 'x' . $img_height, $min_width . 'x' . $min_height, $size_label ) . ' ';

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

			if ( $this->p->options[ 'plugin_upscale_images' ] ) {

				$img_info = (array) self::get_image_src_args();

				$upscale_img_max     = apply_filters( $this->p->lca . '_image_upscale_max', $this->p->options[ 'plugin_upscale_img_max' ], $img_info );
				$upscale_multiplier  = 1 + ( $upscale_img_max / 100 );
				$upscale_full_width  = round( $full_width * $upscale_multiplier );
				$upscale_full_height = round( $full_height * $upscale_multiplier );

				$is_sufficient_w = $upscale_full_width >= $size_info[ 'width' ] ? true : false;
				$is_sufficient_h = $upscale_full_height >= $size_info[ 'height' ] ? true : false;
			}

			if ( ( ! $size_info[ 'is_cropped' ] && ( ! $is_sufficient_w && ! $is_sufficient_h ) ) ||
				( $size_info[ 'is_cropped' ] && ( ! $is_sufficient_w || ! $is_sufficient_h ) ) ) {

				$ret = false;

			} else {

				$ret = true;
			}

			if ( $this->p->debug->enabled ) {

				$upscaled_by = $upscale_multiplier !== 1 ? ' (' . $upscale_full_width . 'x' . $upscale_full_height .
					' upscaled by ' . $upscale_multiplier . ')' : '';

				$this->p->debug->log( 'full size image of ' . $full_width . 'x' . $full_height . $upscaled_by .
					( $ret ? ' sufficient' : ' too small' ) . ' to create ' . $size_info[ 'dimensions' ] );
			}

			return $ret;
		}

		public function get_cropped_image_filename( $file_path, $size_info ) {

			$dir  = pathinfo( $file_path, PATHINFO_DIRNAME );	// Returns '.' for filenames without paths.
			$ext  = pathinfo( $file_path, PATHINFO_EXTENSION ); 
			$base = wp_basename( $file_path, '.' . $ext );

			$new_dir    = '.' === $dir ? '' : trailingslashit( $dir );
			$new_ext    = '.' . $ext;
			$new_base   = preg_replace( '/-cropped(-[a-z]+-[a-z]+)?$/', '', $base );
			$new_suffix = '';

			if ( ! empty( $size_info[ 'crop' ] ) ) {

				$new_suffix .= '-cropped';

				if ( is_array( $size_info[ 'crop' ] ) ) {

					if ( $size_info[ 'crop' ] !== array( 'center', 'center' ) ) {

						$new_suffix .= '-' . implode( '-', $size_info[ 'crop' ] );
					}
				}
			}

			return $new_dir . $new_base . $new_suffix . $new_ext; 
		}

		/**
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
