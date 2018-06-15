<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoMedia' ) ) {

	class WpssoMedia {

		private $p;
		private $def_img_preg = array(
			'html_tag' => 'img',
			'pid_attr' => 'data-[a-z]+-pid',
			'ngg_src' => '[^\'"]+\/cache\/([0-9]+)_(crop)?_[0-9]+x[0-9]+_[^\/\'"]+|[^\'"]+-nggid0[1-f]([0-9]+)-[^\'"]+',
		);
		private static $image_src_info = null;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			add_action( 'init', array( $this, 'allow_img_data_attributes' ) );

			// prevent image_downsize from lying about image width and height
			if ( is_admin() ) {
				add_filter( 'editor_max_image_size', array( $this, 'editor_max_image_size' ), 10, 3 );
			}

			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_attachment_image_attributes' ), 10, 2 );
			add_filter( 'get_image_tag', array( $this, 'get_image_tag' ), 10, 6 );
			add_filter( 'get_header_image_tag', array( $this, 'get_header_image_tag' ), 10, 3 );
		}

		public function allow_img_data_attributes() {
			global $allowedposttags;
			$allowedposttags['img']['data-wp-pid'] = true;
			if ( ! empty( $this->p->options['p_add_nopin_media_img_tag'] ) ) {
				$allowedposttags['img']['nopin'] = true;
			}
		}

		// note that $size_name can be a string or an array()
		public function editor_max_image_size( $max_sizes = array(), $size_name = '', $context = '' ) {
			// allow only our sizes to exceed the editor width
			if ( is_string( $size_name ) && strpos( $size_name, $this->p->lca . '-' ) === 0 ) {
				$max_sizes = array( 0, 0 );
			}
			return $max_sizes;
		}

		// $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment );
		public function add_attachment_image_attributes( $attr, $attach ) {
			$attr['data-wp-pid'] = $attach->ID;
			if ( ! empty( $this->p->options['p_add_nopin_media_img_tag'] ) ) {
				$attr['nopin'] = 'nopin';
			}
			return $attr;
		}

		// $html = apply_filters( 'get_image_tag', $html, $id, $alt, $title, $align, $size );
		public function get_image_tag( $html, $id, $alt, $title, $align, $size ) {
			return $this->add_header_image_tag( $html, array(
				'data-wp-pid' => $id,
				'nopin' => empty( $this->p->options['p_add_nopin_media_img_tag'] ) ? false : 'nopin'
			) );
		}

		// $html = apply_filters( 'get_header_image_tag', $html, $header, $attr );
		public function get_header_image_tag( $html, $header, $attr ) {
			return $this->add_header_image_tag( $html, array(
				'nopin' => empty( $this->p->options['p_add_nopin_header_img_tag'] ) ? false : 'nopin'
			) );
		}

		private function add_header_image_tag( $html, $add_attr ) {
			foreach ( $add_attr as $attr_name => $attr_value ) {
				if ( $attr_value !== false && strpos( $html, ' '.$attr_name.'=' ) === false ) {
					$html = preg_replace( '/ *\/?'.'>/', ' '.$attr_name.'="'.$attr_value.'"$0', $html );
				}
			}
			return $html;
		}

		public function get_post_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num' => $num,
					'size_name' => $size_name,
					'post_id' => $post_id,
					'check_dupes' => $check_dupes,
					'md_pre' => $md_pre,
				) );
			}

			$og_ret = array();
			$force_regen = $this->p->util->is_force_regen( $post_id, $md_pre );	// false by default

			if ( ! empty( $post_id ) ) {

				// get_og_images() also provides filter hooks for additional image ids and urls
				// unless $md_pre is 'none', get_og_images() will fallback to the 'og' custom meta
				$og_ret = array_merge( $og_ret, $this->p->m['util']['post']->get_og_images( 1,
					$size_name, $post_id, $check_dupes, $force_regen, $md_pre ) );
			}

			// allow for empty post_id in order to execute featured / attached image filters for modules
			if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$num_diff = SucomUtil::count_diff( $og_ret, $num );
				$og_ret = array_merge( $og_ret, $this->get_featured( $num_diff,
					$size_name, $post_id, $check_dupes, $force_regen ) );
			}

			// 'wpsso_attached_images' filter is used by the buddypress module
			if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$num_diff = SucomUtil::count_diff( $og_ret, $num );
				$og_ret = array_merge( $og_ret, $this->get_attached_images( $num_diff,
					$size_name, $post_id, $check_dupes, $force_regen ) );
			}

			return $og_ret;
		}

		public function get_featured( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $force_regen = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num' => $num,
					'size_name' => $size_name,
					'post_id' => $post_id,
					'check_dupes' => $check_dupes,
					'force_regen' => $force_regen,
				) );
			}

			$og_ret = array();
			$og_single_image = SucomUtil::get_mt_prop_image();

			if ( ! empty( $post_id ) ) {
				// check for an attachment page, just in case
				if ( ( is_attachment( $post_id ) || get_post_type( $post_id ) === 'attachment' ) && wp_attachment_is_image( $post_id ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'post_type is an attachment - using post_id '.$post_id. ' as the image id' );
					}
					$pid = $post_id;

				} elseif ( $this->p->avail['*']['featured'] == true && has_post_thumbnail( $post_id ) ) {
					$pid = get_post_thumbnail_id( $post_id );
				} else {
					$pid = false;
				}

				if ( ! empty( $pid ) ) {
					list(
						$og_single_image['og:image'],
						$og_single_image['og:image:width'],
						$og_single_image['og:image:height'],
						$og_single_image['og:image:cropped'],
						$og_single_image['og:image:id']
					) = $this->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );

					if ( ! empty( $og_single_image['og:image'] ) ) {
						$this->p->util->push_max( $og_ret, $og_single_image, $num );
					}
				}
			}
			return apply_filters( $this->p->lca . '_og_featured', $og_ret, $num, $size_name, $post_id, $check_dupes, $force_regen );
		}

		public function get_first_attached_image_id( $post_id ) {
			if ( ! empty( $post_id ) ) {
				// check for an attachment page, just in case
				if ( ( is_attachment( $post_id ) || get_post_type( $post_id ) === 'attachment' ) && wp_attachment_is_image( $post_id ) ) {
					return $post_id;
				} else {
					$images = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image' ) );
					$attach = reset( $images );
					if ( ! empty( $attach->ID ) ) {
						return $attach->ID;
					}
				}
			}
			return false;
		}

		public function get_attachment_image( $num = 0, $size_name = 'thumbnail', $attach_id, $check_dupes = true, $force_regen = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num' => $num,
					'size_name' => $size_name,
					'attach_id' => $attach_id,
					'check_dupes' => $check_dupes,
					'force_regen' => $force_regen,
				) );
			}

			$og_ret = array();
			$og_single_image = SucomUtil::get_mt_prop_image();

			if ( ! empty( $attach_id ) ) {
				if ( wp_attachment_is_image( $attach_id ) ) {	// since wp 2.1.0
					list(
						$og_single_image['og:image'],
						$og_single_image['og:image:width'],
						$og_single_image['og:image:height'],
						$og_single_image['og:image:cropped'],
						$og_single_image['og:image:id']
					) = $this->get_attachment_image_src( $attach_id, $size_name, $check_dupes, $force_regen );

					if ( ! empty( $og_single_image['og:image'] ) && $this->p->util->push_max( $og_ret, $og_single_image, $num ) ) {
						return $og_ret;
					}
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'attachment id '.$attach_id.' is not an image' );
				}
			}
			return $og_ret;
		}

		public function get_attached_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $force_regen = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num' => $num,
					'size_name' => $size_name,
					'post_id' => $post_id,
					'check_dupes' => $check_dupes,
					'force_regen' => $force_regen,
				) );
			}

			$og_ret = array();
			$og_single_image = SucomUtil::get_mt_prop_image();

			if ( ! empty( $post_id ) ) {

				$images = get_children( array(
					'post_parent' => $post_id,
					'post_type' => 'attachment',
					'post_mime_type' => 'image'
				), OBJECT );	// OBJECT, ARRAY_A, or ARRAY_N

				$attach_ids = array();
				foreach ( $images as $attach ) {
					if ( ! empty( $attach->ID ) ) {
						$attach_ids[] = $attach->ID;
					}
				}
				rsort( $attach_ids, SORT_NUMERIC );

				$attach_ids = array_unique( apply_filters( $this->p->lca . '_attached_image_ids', $attach_ids, $post_id ) );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'found '.count( $attach_ids ).' attached images for post_id '.$post_id );
				}

				foreach ( $attach_ids as $pid ) {
					list(
						$og_single_image['og:image'],
						$og_single_image['og:image:width'],
						$og_single_image['og:image:height'],
						$og_single_image['og:image:cropped'],
						$og_single_image['og:image:id']
					) = $this->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );

					if ( ! empty( $og_single_image['og:image'] ) && $this->p->util->push_max( $og_ret, $og_single_image, $num ) ) {
						break;	// stop here and apply the 'wpsso_attached_images' filter
					}
				}
			}

			// 'wpsso_attached_images' filter is used by the buddypress module
			return apply_filters( $this->p->lca . '_attached_images', $og_ret, $num, $size_name, $post_id, $check_dupes, $force_regen );
		}

		/**
		 * Use these static methods in get_attachment_image_src() to set/reset information about
		 * the image being processed for down-stream filters / methods lacking this information.
		 * They can call WpssoMedia::get_image_src_info() to retrieve the image information.
		 */
		public static function set_image_src_info( $image_src_args = null ) {
			self::$image_src_info = $image_src_args;
		}

		public static function get_image_src_info( $idx = false ) {
			if ( $idx !== false ) {
				if ( isset( self::$image_src_info[$idx] ) ) {
					return self::$image_src_info[$idx];
				} else {
					return null;
				}
			} else {
				return self::$image_src_info;
			}
		}

		/**
		 * Return an empty image array by default.
		 */
		public static function reset_image_src_info( $image_src_ret = array( null, null, null, null, null ) ) {
			self::$image_src_info = null;
			return $image_src_ret;
		}

		/**
		 * Note that every return must be wrapped with self::reset_image_src_info().
		 */
		public function get_attachment_image_src( $pid, $size_name = 'thumbnail', $check_dupes = true, $force_regen = false ) {

			self::set_image_src_info( $args = array(
				'pid' => $pid,
				'size_name' => $size_name,
				'check_dupes' => $check_dupes,
				'force_regen' => $force_regen,
			) );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log_args( $args );
			}

			$size_info = SucomUtil::get_size_info( $size_name );
			$img_url = '';
			$img_width = WPSSO_UNDEF_INT;
			$img_height = WPSSO_UNDEF_INT;
			$img_cropped = empty( $size_info['crop'] ) ? 0 : 1;	// get_size_info() returns false, true, or an array

			if ( $this->p->avail['media']['ngg'] && strpos( $pid, 'ngg-' ) === 0 ) {

				if ( ! empty( $this->p->m['media']['ngg'] ) ) {

					return self::reset_image_src_info( $this->p->m['media']['ngg']->get_image_src( $pid, $size_name, $check_dupes ) );

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'ngg module is not available: image id '.$attr_value.' ignored' );
					}

					if ( $this->p->notice->is_admin_pre_notices() ) {	// skip if notices already shown
						$error_msg = __( 'The NGG integration module provided by %1$s is required to read information for image ID %2$s.', 'wpsso' );
						$this->p->notice->err( sprintf( $error_msg, $this->p->cf['plugin'][$this->p->lca]['short'].' Pro', $pid ) );
					}

					return self::reset_image_src_info();
				}

			} elseif ( ! wp_attachment_is_image( $pid ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: attachment '.$pid.' is not an image' );
				}

				return self::reset_image_src_info();
			}

			$use_full = false;
			$img_meta = wp_get_attachment_metadata( $pid );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_arr( 'wp_get_attachment_metadata', $img_meta );
			}

			if ( isset( $img_meta['width'] ) && isset( $img_meta['height'] ) ) {

				/**
				 * Image dimensions are present.
				 */
				if ( $img_meta['width'] === $size_info['width'] && $img_meta['height'] === $size_info['height'] ) {
					$use_full = true;
				}
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'full size image '.$img_meta['file'].' dimensions '.$img_meta['width'].'x'.$img_meta['height'] );
				}

			} else {

				$media_lib = __( 'Media Library', 'wpsso' );
				$edit_url  = get_edit_post_link( $pid );
				$func_name = 'wp_get_attachment_metadata()';
				$func_url  = __( 'https://developer.wordpress.org/reference/functions/wp_get_attachment_metadata/', 'wpsso' );
				$regen_msg = sprintf( __( 'You may consider regenerating the thumbnails of all WordPress Media Library images using one of <a href="%s">several available plugins from WordPress.org</a>.', 'wpsso' ), 'https://wordpress.org/plugins/search/regenerate+thumbnails/' );

				if ( isset( $img_meta['file'] ) ) {

					/**
					 * Image dimensions are missing, but full size image path is present.
					 */
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'full size image '.$img_meta['file'].' dimensions missing from image metadata' );
					}

					$dismiss_key = 'full-size-image-'.$pid.'-dimensions-missing';
					$dismiss_time = WEEK_IN_SECONDS;

					if ( $this->p->notice->is_admin_pre_notices() ) { // Skip if notices already shown.

						$error_msg = sprintf( __( 'Possible %1$s corruption detected &mdash; the full size image dimensions for <a href="%2$s">image ID %3$s</a> are missing from the image metadata returned by the <a href="%4$s">WordPress %5$s function</a>.', 'wpsso' ), $media_lib, $edit_url, $pid, $func_url, '<code>'.$func_name.'</code>' );

						$this->p->notice->err( $error_msg.' '.$regen_msg, true, $dismiss_key, $dismiss_time );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'admin error notice for missing full size image id '.$pid.' dimensions metadata is ' .
							( $this->p->notice->is_dismissed( $dismiss_key ) ? 'dismissed' : 'shown (not dismissed)' ) );
					}

				} else {

					/**
					 * Both the image dimensions and full size image path are missing.
					 */
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'full size image file path meta for '.$pid.' missing from image metadata' );
					}

					$dismiss_key = 'full-size-image-'.$pid.'-file-path-missing';
					$dismiss_time = WEEK_IN_SECONDS;

					if ( $this->p->notice->is_admin_pre_notices() ) { // Skip if notices already shown.

						$error_msg = printf( __( 'Possible %1$s corruption detected &mdash; the full size image file path for <a href="%2$s">image ID %3$s</a> is missing from the image metadata returned by the <a href="%4$s">WordPress %5$s function</a>.', 'wpsso' ), $media_lib, $edit_url, $pid, $func_url, '<code>'.$func_name.'</code>' );

						$this->p->notice->err( $error_msg.' '.$regen_msg, true, $dismiss_key, $dismiss_time );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'admin error notice for missing full size image id '.$pid.' file path metadata is ' .
							( $this->p->notice->is_dismissed( $dismiss_key ) ? 'dismissed' : 'shown (not dismissed)' ) );
					}
				}
			}

			if ( true === $use_full ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'requesting full size instead - image dimensions same as '.
						$size_name.' ('.$size_info['width'].'x'.$size_info['height'].')' );
				}

			} elseif ( strpos( $size_name, $this->p->lca.'-' ) === 0 ) { // Only resize our own custom image sizes.

				if ( $force_regen || ! empty( $this->p->options['plugin_create_wp_sizes'] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking image metadata for inconsistencies' );
						if ( $force_regen ) {
							$this->p->debug->log( 'force regen is true' );
						} elseif ( empty( $img_meta['sizes'][$size_name] ) ) {
							$this->p->debug->log( $size_name.' size missing from image metadata' );
						}
					}

					// does the image metadata contain our image sizes?
					if ( $force_regen || empty( $img_meta['sizes'][$size_name] ) ) {

						$is_accurate_width = false;
						$is_accurate_height = false;

					} else {

						// is the width and height in the image metadata accurate?
						$is_accurate_width = ! empty( $img_meta['sizes'][$size_name]['width'] ) &&
							$img_meta['sizes'][$size_name]['width'] == $size_info['width'] ? true : false;
						$is_accurate_height = ! empty( $img_meta['sizes'][$size_name]['height'] ) &&
							$img_meta['sizes'][$size_name]['height'] == $size_info['height'] ? true : false;

						// if not cropped, make sure the resized image respects the original aspect ratio
						if ( $is_accurate_width && $is_accurate_height && empty( $img_cropped ) &&
							isset( $img_meta['width'] ) && isset( $img_meta['height'] ) ) {

							if ( $img_meta['width'] > $img_meta['height'] ) {
								$ratio = $img_meta['width'] / $size_info['width'];
								$check = 'height';
							} else {
								$ratio = $img_meta['height'] / $size_info['height'];
								$check = 'width';
							}
							$should_be = (int) round( $img_meta[$check] / $ratio );

							// allow for a +/- one pixel difference
							if ( $img_meta['sizes'][$size_name][$check] < ( $should_be - 1 ) ||
								$img_meta['sizes'][$size_name][$check] > ( $should_be + 1 ) ) {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $size_name.' image metadata not accurate' );
								}
								$is_accurate_width = false;
								$is_accurate_height = false;
							}
						}
					}

					/**
					 * Depending on cropping, one or both sides of the image must be accurate.
					 * If not, attempt to create a resized image by calling image_make_intermediate_size().
					 */
					if ( ( ! $img_cropped && ( ! $is_accurate_width && ! $is_accurate_height ) ) ||
						( $img_cropped && ( ! $is_accurate_width || ! $is_accurate_height ) ) ) {

						if ( $this->p->debug->enabled ) {
							if ( ! $force_regen && ! empty( $img_meta['sizes'][$size_name] ) ) {
								$this->p->debug->log( 'image metadata ('.
									( empty( $img_meta['sizes'][$size_name]['width'] ) ? 0 :
										$img_meta['sizes'][$size_name]['width'] ).'x'.
									( empty( $img_meta['sizes'][$size_name]['height'] ) ? 0 :
										$img_meta['sizes'][$size_name]['height'] ).') does not match '.
									$size_name.' ('.$size_info['width'].'x'.$size_info['height'].
										( $img_cropped ? ' cropped' : '' ).')' );
							}
						}

						if ( $this->can_make_size( $img_meta, $size_info ) ) {

							$fullsizepath = get_attached_file( $pid );
							$resized_meta = image_make_intermediate_size( $fullsizepath,
								$size_info['width'], $size_info['height'], $size_info['crop'] );
	
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'WordPress image_make_intermediate_size() reported '.
									( false === $resized_meta ? 'failure' : 'success' ) );
							}
	
							if ( $resized_meta == false ) {

								$dismiss_key = 'image-make-intermediate-size-'.$fullsizepath.'-failure';
								$dismiss_time = WEEK_IN_SECONDS;

								if ( $this->p->notice->is_admin_pre_notices() ) { // Skip if notices already shown.

									$media_lib = __( 'Media Library', 'wpsso' );
									$func_name = 'image_make_intermediate_size()';
									$func_url  = __( 'https://developer.wordpress.org/reference/functions/image_make_intermediate_size/', 'wpsso' );
									$regen_msg = sprintf( __( 'You may consider regenerating the thumbnails of all WordPress Media Library images using one of <a href="%s">several available plugins from WordPress.org</a>.', 'wpsso' ), 'https://wordpress.org/plugins/search/regenerate+thumbnails/' );

									$error_msg = sprintf( __( 'Possible %1$s corruption detected &mdash; the <a href="%2$s">WordPress %3$s function</a> reported an error when trying to create an image size from %4$s.', 'wpsso' ), $media_lib, $func_url, '<code>'.$func_name.'</code>', $fullsizepath );

									$this->p->notice->err( $error_msg.' '.$regen_msg, true, $dismiss_key, $dismiss_time );

								} elseif ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'admin error notice for image_make_intermediate_size() from '.$fullsizepath.' is ' .
										( $this->p->notice->is_dismissed( $dismiss_key ) ? 'dismissed' : 'shown (not dismissed)' ) );
								}

							} else {
								$img_meta['sizes'][$size_name] = $resized_meta;
								wp_update_attachment_metadata( $pid, $img_meta );
							}
						} elseif ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'skipped image_make_intermediate_size()' );
						}
					}
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'image metadata check skipped: plugin_create_wp_sizes option is disabled' );
				}
			}

			/**
			 * Some image_downsize hooks may return only 3 elements, so use array_pad() to sanitize the returned array.
			 */
			list( $img_url, $img_width, $img_height, $img_intermediate ) = apply_filters( $this->p->lca.'_image_downsize',
				array_pad( image_downsize( $pid, ( true === $use_full ? 'full' : $size_name ) ), 4, null ), $pid, $size_name );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'image_downsize returned '.$img_url.' ('.$img_width.'x'.$img_height.')' );
			}

			if ( empty( $img_url ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: image_downsize returned an empty url' );
				}
				return self::reset_image_src_info();
			}

			/**
			 * Check if image exceeds hard-coded limits (dimensions, ratio, etc.).
			 */
			$img_size_within_limits = $this->img_size_within_limits( $pid, $size_name, $img_width, $img_height );

			/**
			 * The 'wpsso_attached_accept_img_dims' filter is hooked by the WpssoProCheckImgSize class.
			 */
			if ( apply_filters( $this->p->lca.'_attached_accept_img_dims', $img_size_within_limits,
				$img_url, $img_width, $img_height, $size_name, $pid ) ) {

				if ( ! $check_dupes || $this->p->util->is_uniq_url( $img_url, $size_name ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'applying rewrite_image_url filter for '.$img_url );
					}

					return self::reset_image_src_info( array( apply_filters( $this->p->lca.'_rewrite_image_url',
						$this->p->util->fix_relative_url( $img_url ) ),	// Just in case.
							$img_width, $img_height, $img_cropped, $pid ) );
				}
			}

			return self::reset_image_src_info();
		}

		public function get_default_images( $num = 1, $size_name = 'thumbnail', $check_dupes = true, $force_regen = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num' => $num,
					'size_name' => $size_name,
					'check_dupes' => $check_dupes,
					'force_regen' => $force_regen,
				) );
			}

			$og_ret = array();
			$og_single_image = SucomUtil::get_mt_prop_image();

			foreach ( array( 'id', 'id_pre', 'url', 'url:width', 'url:height' ) as $key ) {
				$img[$key] = empty( $this->p->options['og_def_img_'.$key] ) ?
					'' : $this->p->options['og_def_img_'.$key];
			}

			if ( empty( $img['id'] ) && empty( $img['url'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: no default image defined' );
				}
				return $og_ret;
			}

			if ( ! empty( $img['id'] ) ) {

				$img['id'] = $img['id_pre'] === 'ngg' ?
					'ngg-'.$img['id'] : $img['id'];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'using default image pid: '.$img['id'] );
				}

				list(
					$og_single_image['og:image'],
					$og_single_image['og:image:width'],
					$og_single_image['og:image:height'],
					$og_single_image['og:image:cropped'],
					$og_single_image['og:image:id']
				) = $this->get_attachment_image_src( $img['id'], $size_name, $check_dupes, $force_regen );
			}

			if ( empty( $og_single_image['og:image'] ) && ! empty( $img['url'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'using default image url: '.$img['url'] );
				}

				$og_single_image = array(
					'og:image' => $img['url'],
					'og:image:width' => $img['url:width'],
					'og:image:height' => $img['url:height'],
				);
			}

			if ( ! empty( $og_single_image['og:image'] ) && $this->p->util->push_max( $og_ret, $og_single_image, $num ) ) {
				return $og_ret;
			}

			return $og_ret;
		}

		public function get_content_images( $num = 0, $size_name = 'thumbnail', $mod = true, $check_dupes = true, $force_regen = false, $content = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num' => $num,
					'size_name' => $size_name,
					'mod' => $mod,
					'check_dupes' => $check_dupes,
					'content strlen' => strlen( $content ),
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}
				$mod = $this->p->util->get_page_mod( $mod );
			}

			$og_ret = array();

			/**
			 * Allow custom content to be passed as an argument in $content.
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
				return $og_ret;
			}

			$og_single_image = SucomUtil::get_mt_prop_image();
			$size_info = SucomUtil::get_size_info( $size_name );
			$img_preg = $this->def_img_preg;

			// allow the html_tag and pid_attr regex to be modified
			foreach( array( 'html_tag', 'pid_attr' ) as $type ) {
				$filter_name = $this->p->lca . '_content_image_preg_' . $type;
				if ( has_filter( $filter_name ) ) {
					$img_preg[$type] = apply_filters( $filter_name, $this->def_img_preg[$type] );
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'filtered image preg '.$type.' = \''.$img_preg[$type].'\'' );
					}
				}
			}

			// img attributes in order of preference
			if ( preg_match_all( '/<(('.$img_preg['html_tag'].')[^>]*? ('.$img_preg['pid_attr'].')=[\'"]([0-9]+)[\'"]|'.
				'(img)[^>]*? (data-share-src|data-lazy-src|data-src|src)=[\'"]([^\'"]+)[\'"])[^>]*>/s',
					$content, $all_matches, PREG_SET_ORDER ) ) {

				$content_img_max = SucomUtil::get_const( 'WPSSO_CONTENT_IMAGES_MAX_LIMIT', 5 );

				if ( count( $all_matches ) > $content_img_max ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'limiting matches returned from '.
							count( $all_matches ).' to '.$content_img_max );
					}
					$all_matches = array_splice( $all_matches, 0, $content_img_max );
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( count( $all_matches ).' x matching <'.$img_preg['html_tag'].'/> html tag(s) found' );
				}

				foreach ( $all_matches as $img_num => $img_arr ) {

					$tag_value = $img_arr[0];

					if ( empty( $img_arr[5] ) ) {
						$tag_name = $img_arr[2];	// img
						$attr_name = $img_arr[3];	// data-wp-pid
						$attr_value = $img_arr[4];	// id
					} else {
						$tag_name = $img_arr[5];	// img
						$attr_name = $img_arr[6];	// data-share-src|data-lazy-src|data-src|src
						$attr_value = $img_arr[7];	// url
					}

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'match '.$img_num.': '.$tag_name.' '.$attr_name.'="'.$attr_value.'"' );
					}

					switch ( $attr_name ) {

						// WordPress media library image id
						case 'data-wp-pid':

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'WP image attribute id found: '.$attr_value );
							}

							list(
								$og_single_image['og:image'],
								$og_single_image['og:image:width'],
								$og_single_image['og:image:height'],
								$og_single_image['og:image:cropped'],
								$og_single_image['og:image:id']
							) = $this->get_attachment_image_src( $attr_value, $size_name, false, $force_regen );

							break;

						// check for other data attributes like 'data-ngg-pid'
						case ( preg_match( '/^'.$img_preg['pid_attr'].'$/', $attr_name ) ? true : false ):

							// build a filter hook for 3rd party modules to return image information
							$filter_name = $this->p->lca . '_get_content_' . $tag_name . '_' . ( preg_replace( '/-/', '_', $attr_name ) );

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'applying ' . $filter_name . ' filters' );
							}

							list(
								$og_single_image['og:image'],
								$og_single_image['og:image:width'],
								$og_single_image['og:image:height'],
								$og_single_image['og:image:cropped'],
								$og_single_image['og:image:id']
							) = apply_filters( $filter_name, array( null, null, null, null, null ), $attr_value, $size_name, false );

							break;

						// data-share-src|data-lazy-src|data-src|src
						default:

							// prevent duplicates by silently ignoring ngg images (already processed by the ngg module)
							if ( true === $this->p->avail['media']['ngg'] && ! empty( $this->p->m['media']['ngg'] ) &&
								( preg_match( '/ class=[\'"]ngg[_-]/', $tag_value ) ||
									preg_match( '/^('.$img_preg['ngg_src'].')$/', $attr_value ) ) ) {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'silently ignoring ngg image for '.$attr_name );
								}
								break;	// stop here
							}

							// recognize gravatar images in the content
							if ( preg_match( '/^(https?:)?(\/\/([^\.]+\.)?gravatar\.com\/avatar\/[a-zA-Z0-9]+)/', $attr_value, $match ) ) {

								$og_single_image['og:image'] = SucomUtil::get_prot().':'.$match[2].'?s='.$size_info['width'].'&d=404&r=G';
								$og_single_image['og:image:width'] = $size_info['width'];
								$og_single_image['og:image:height'] = $size_info['width'];	// square image

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'gravatar image found: '.$og_single_image['og:image'] );
								}

								break;	// stop here
							}

							// check for image id in class for old content w/o the data-wp-pid attribute
							if ( preg_match( '/class="[^"]+ wp-image-([0-9]+)/', $tag_value, $match ) ) {
								list(
									$og_single_image['og:image'],
									$og_single_image['og:image:width'],
									$og_single_image['og:image:height'],
									$og_single_image['og:image:cropped'],
									$og_single_image['og:image:id']
								) = $this->get_attachment_image_src( $match[1], $size_name, false, $force_regen );
								break;	// stop here
							} else {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'using attribute value for og:image = '.$attr_value.
										' ('.WPSSO_UNDEF_INT.'x'.WPSSO_UNDEF_INT.')' );
								}
								$og_single_image = array(
									'og:image' => $attr_value,
									'og:image:width' => WPSSO_UNDEF_INT,
									'og:image:height' => WPSSO_UNDEF_INT,
								);
							}

							if ( empty( $og_single_image['og:image'] ) ) {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'single image og:image value is empty' );
								}
								break;	// stop here
							}

							$check_size_limits = true;
							$img_size_within_limits = true;

							// get the actual width and height of the image using http / https
							if ( empty( $og_single_image['og:image:width'] ) || $og_single_image['og:image:width'] < 0 ||
								empty( $og_single_image['og:image:height'] ) || $og_single_image['og:image:height'] < 0 ) {

								/**
								 * Add correct image sizes for the image URL using getimagesize().
								 */
								$this->p->util->add_image_url_size( 'og:image', $og_single_image );

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'returned / fetched image url size: '.
										$og_single_image['og:image:width'].'x'.$og_single_image['og:image:height'] );
								}

								/**
								 * No use checking / retrieving the image size twice.
								 */
								if ( $og_single_image['og:image:width'] === WPSSO_UNDEF_INT &&
									$og_single_image['og:image:height'] === WPSSO_UNDEF_INT ) {

									$check_size_limits = false;
									$img_size_within_limits = false;
								}

							} elseif ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'image width / height values: '.
									$og_single_image['og:image:width'].'x'.$og_single_image['og:image:height'] );
							}

							if ( $check_size_limits ) {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'checking image size limits for '.$og_single_image['og:image'].
										' ('.$og_single_image['og:image:width'].'x'.$og_single_image['og:image:height'].')' );
								}

								/**
								 * Check if image exceeds hard-coded limits (dimensions, ratio, etc.).
								 */
								$img_size_within_limits = $this->img_size_within_limits( $og_single_image['og:image'],
									$size_name, $og_single_image['og:image:width'], $og_single_image['og:image:height'],
										__( 'Content', 'wpsso' ) );

							} elseif ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'skipped image size limits for '.$og_single_image['og:image'].
									' ('.$og_single_image['og:image:width'].'x'.$og_single_image['og:image:height'].')' );
							}

							/**
							 * The 'wpsso_attached_accept_img_dims' filter is hooked by the WpssoProCheckImgSize class.
							 */
							if ( ! apply_filters( $this->p->lca . '_content_accept_img_dims', $img_size_within_limits,
								$og_single_image, $size_name, $attr_name, $content_passed ) ) {

								$og_single_image = array();
							}

							break;
					}

					if ( ! empty( $og_single_image['og:image'] ) ) {

						$og_single_image['og:image'] = apply_filters( $this->p->lca . '_rewrite_image_url',
							$this->p->util->fix_relative_url( $og_single_image['og:image'] ) );

						if ( false === $check_dupes || $this->p->util->is_uniq_url( $og_single_image['og:image'], $size_name ) ) {
							if ( $this->p->util->push_max( $og_ret, $og_single_image, $num ) ) {
								return $og_ret;
							}
						}
					}
				}
				return $og_ret;
			}
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'no matching <'.$img_preg['html_tag'].'/> html tag(s) found' );
			}
			return $og_ret;
		}

		/**
		 * Example $opt_img_pre values: 'og_img', 'schema_banner', 'org_banner'.
		 */
		public function get_opts_single_image( $opts, $size_name = null, $opt_img_pre = 'og_img', $opt_num = null ) {

			$mt_pre = 'og';
			$check_dupes = false;
			$force_regen = false;
			$img_opts = array();

			foreach ( array( 'id', 'id_pre', 'url', 'url:width', 'url:height' ) as $key ) {
				$opt_suf        = $opt_num === null ? $key : $key . '_' . $opt_num;	// Use a numbered multi-option key.
				$opt_key        = $opt_img_pre . '_' . $opt_suf;
				$opt_key_locale = SucomUtil::get_key_locale( $opt_img_pre . '_' . $opt_suf, $opts );
				$img_opts[$key] = empty( $opts[$opt_key_locale] ) ? '' : $opts[$opt_key_locale];
			}

			$mt_image = array();

			if ( ! empty( $img_opts['id'] ) && is_string( $size_name ) ) {

				$img_opts['id'] = $img_opts['id_pre'] === 'ngg' ? 'ngg-' . $img_opts['id'] : $img_opts['id'];

				list( 
					$mt_image[$mt_pre . ':image'],
					$mt_image[$mt_pre . ':image:width'],
					$mt_image[$mt_pre . ':image:height'],
					$mt_image[$mt_pre . ':image:cropped'],
					$mt_image[$mt_pre . ':image:id']
				) = $this->get_attachment_image_src( $img_opts['id'], $size_name, $check_dupes, $force_regen );

			} elseif ( ! empty( $img_opts['url'] ) ) {

				$mt_image = array(
					$mt_pre . ':image' => $img_opts['url'],
					$mt_pre . ':image:width' => $img_opts['url:width'] > 0 ? $img_opts['url:width'] : WPSSO_UNDEF_INT,
					$mt_pre . ':image:height' => $img_opts['url:height'] > 0 ? $img_opts['url:height'] : WPSSO_UNDEF_INT,
					$mt_pre . ':image:cropped' => null,
					$mt_pre . ':image:id' => null,
				);
			}

			return $mt_image;
		}

		public function get_content_videos( $num = 0, $mod = true, $check_dupes = true, $content = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num'         => $num,
					'mod'         => $mod,
					'check_dupes' => $check_dupes,
					'content'     => strlen( $content ).' chars',
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}
				$mod = $this->p->util->get_page_mod( $mod );
			}

			$og_ret = array();

			/**
			 * Allow custom content to be passed as an argument in $content.
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
				return $og_ret;
			}

			/**
			 * Detect standard iframe / embed tags - use the 'wpsso_content_videos' filter
			 * for additional html5 / javascript embed methods.
			 */
			if ( preg_match_all( '/<(iframe|embed)[^<>]*? (data-share-src|data-lazy-src|data-src|src)=[\'"]'.
				'([^\'"<>]+\/(embed\/|embed_code\/|player\/|swf\/|v\/|video\/|video\.php\?)[^\'"<>]+)[\'"][^<>]*>/i',
					$content, $all_matches, PREG_SET_ORDER ) ) {

				$content_vid_max = SucomUtil::get_const( 'WPSSO_CONTENT_VIDEOS_MAX_LIMIT', 5 );

				if ( count( $all_matches ) > $content_vid_max ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'limiting matches returned from '.count( $all_matches ).' to '.$content_vid_max );
					}
					$all_matches = array_splice( $all_matches, 0, $content_vid_max );
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( count( $all_matches ).' x video <iframe|embed/> html tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( '<'.$media[1].'/> html tag found '.$media[2].' = '.$media[3] );
					}

					if ( ! empty( $media[3] ) ) {

						if ( $check_dupes == false || $this->p->util->is_uniq_url( $media[3], 'content_video' ) ) {

							$args = array(
								'url'    => $media[3],
								'width'  => preg_match( '/ width=[\'"]?([0-9]+)[\'"]?/i', $media[0], $match ) ? $match[1] : WPSSO_UNDEF_INT,
								'height' => preg_match( '/ height=[\'"]?([0-9]+)[\'"]?/i', $media[0], $match ) ? $match[1] : WPSSO_UNDEF_INT,
							);

							$og_video  = $this->get_video_info( $args, $check_dupes );

							if ( ! empty( $og_video ) && $this->p->util->push_max( $og_ret, $og_video, $num ) ) {
								return $og_ret;
							}
						}
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'no <iframe|embed/> html tag(s) found' );
			}

			/**
			 * Additional filters / Pro modules may detect other embedded video markup.
			 */
			$filter_name = $this->p->lca . '_content_videos';

			if ( has_filter( $filter_name ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'applying ' . $filter_name . ' filters' );
				}

				/**
				 * Must return false or an array of associative arrays.
				 */
				if ( ( $all_matches = apply_filters( $filter_name, false, $content ) ) !== false ) {

					if ( is_array( $all_matches ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( count( $all_matches ).' x videos returned by '.$filter_name.' filters' );
						}

						foreach ( $all_matches as $match_num => $args ) {

							if ( is_array( $args ) ) { // Just in case.

								if ( ! empty( $args['url'] ) ) {

									if ( $check_dupes == false || $this->p->util->is_uniq_url( $args['url'], 'content_video' ) ) {

										$og_video = $this->get_video_info( $args, $check_dupes );

										if ( ! empty( $og_video ) && $this->p->util->push_max( $og_ret, $og_video, $num ) ) {
											return $og_ret;
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
						$this->p->debug->log( $filter_name.' filter did not return false or an array' );
					}
				}
			}

			return $og_ret;
		}

		public function get_video_info( array $args, $check_dupes = true, $fallback = false ) {

			/**
			 * Make sure we have all array keys defined.
			 */
			$args = array_merge( array(
				'url' => '',
				'width' => WPSSO_UNDEF_INT,
				'height' => WPSSO_UNDEF_INT,
				'type' => '',
				'prev_url' => '',
				'post_id' => null,
				'api' => '',
			), $args );

			if ( empty( $args['url'] ) ) {
				return array();
			}

			$filter_name = $this->p->lca . '_video_info';

			/**
			 * Maybe filter using a specific API library hook.
			 */
			if ( ! empty( $args['api'] ) ) {
				$filter_name .= '_' . SucomUtil::sanitize_hookname( $args['api'] );
			}

			$og_video = array_merge( SucomUtil::get_mt_prop_video(), array(
				'og:video:width'  => $args['width'],	// Default width.
				'og:video:height' => $args['height'],	// Default height.
			) );

			$og_video = apply_filters( $filter_name, $og_video, $args );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_arr( 'og_video after filters', $og_video );
			}

			if ( $og_video['al:web:url'] === '' ) {
				$og_video['al:web:should_fallback'] = '';	// false by default
			}

			/**
			 * Sanitation of media.
			 */
			foreach ( array( 'og:video', 'og:image' ) as $prefix ) {

				$have_url = SucomUtil::get_mt_media_url( $og_video, $prefix );

				if ( 'og:video' === $prefix ) {

					/**
					 * Fallback to the original video url.
					 */
					if ( empty( $have_url ) && $fallback ) {
	
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'no video returned by filters' );
							$this->p->debug->log( 'falling back to media url: '.$args['url'] );
						}
	
						/**
						 * Define the og:video:secure_url meta tag if possible.
						 */
						if ( ! empty( $this->p->options['add_meta_property_og:video:secure_url'] ) ) {
							$og_video['og:video:secure_url'] = strpos( $args['url'], 'https:' ) === 0 ? $args['url'] : '';
						}
	
						$have_url = $og_video['og:video:url'] = $args['url'];
					}
	
					/**
					 * Check for an empty mime_type.
					 */
					if ( empty( $og_video['og:video:type'] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'og:video:type is empty - using URL to determine the mime-type' );
						}

						/**
						 * Check for filename extension, slash, or common words (in that order),
						 * followed by an optional query string (which is ignored).
						 */
						if ( preg_match( '/(\.[a-z0-9]+|\/|\/embed\/.*|\/iframe\/.*)(\?[^\?]*)?$/', $have_url, $match ) ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'matched url substr "' . $match[1] . '"' );
							}

							switch ( $match[1] ) {
								case '/':	// WebPage
								case '.htm':
								case '.html':
								case ( strpos( $match[1], '/embed/' ) === 0 ? true : false ):
								case ( strpos( $match[1], '/iframe/' ) === 0 ? true : false ):
									$og_video['og:video:type'] = 'text/html';
									break;
								case '.3gp':	// 3GP Mobile
									$og_video['og:video:type'] = 'video/3gpp';
									break;
								case '.avi':	// A/V Interleave
									$og_video['og:video:type'] = 'video/x-msvideo';
									break;
								case '.flv':	// Flash
									$og_video['og:video:type'] = 'video/x-flv';
									break;
								case '.m3u8':	// iPhone Index
									$og_video['og:video:type'] = 'application/x-mpegURL';
									break;
								case '.mov':	// QuickTime
									$og_video['og:video:type'] = 'video/quicktime';
									break;
								case '.mp4':	// MPEG-4
									$og_video['og:video:type'] = 'video/mp4';
									break;
								case '.swf':	// Shockwave Flash
									$og_video['og:video:type'] = 'application/x-shockwave-flash';
									break;
								case '.ts':	// iPhone Segment
									$og_video['og:video:type'] = 'video/MP2T';
									break;
								case '.wmv':	// Windows Media
									$og_video['og:video:type'] = 'video/x-ms-wmv';
									break;
								default:
									if ( $this->p->debug->enabled ) {
										$this->p->debug->log( 'unknown video extension "' . $match[1] . '"' );
									}
									$og_video['og:video:type'] = '';
									break;
							}

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'setting og:video:type = '.$og_video['og:video:type'] );
							}
						}
					}
				}

				$have_media[$prefix] = empty( $have_url ) ? false : true;

				/**
				 * Remove all meta tags if there's no media URL or media is a duplicate.
				 */
				if ( ! $have_media[$prefix] || ( $check_dupes && ! $this->p->util->is_uniq_url( $have_url, 'video_info' ) ) ) {

					foreach( SucomUtil::preg_grep_keys( '/^'.$prefix.'(:.*)?$/', $og_video ) as $k => $v ) {
						unset ( $og_video[$k] );
					}

				/**
				 * If the media is an image, then check and maybe add missing sizes.
				 */
				} elseif ( $prefix === 'og:image' ) {

					if ( empty( $og_video['og:image:width'] ) || $og_video['og:image:width'] < 0 ||
						empty( $og_video['og:image:height'] ) || $og_video['og:image:height'] < 0 ) {

						/**
						 * Add correct image sizes for the image URL using getimagesize().
						 */
						$this->p->util->add_image_url_size( 'og:image', $og_video );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'returned / fetched video image url size: '.
								$og_video['og:image:width'].'x'.$og_video['og:image:height'] );
						}

					} elseif ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'video image width / height values: '.
							$og_video['og:image:width'].'x'.$og_video['og:image:height'] );
					}
				}
			}

			/**
			 * If there's no video or preview image, then return an empty array.
			 */
			if ( ! $have_media['og:video'] && ! $have_media['og:image'] ) {
				return array();
			} else {
				return $og_video;
			}
		}

		/**
		 * $img_mixed can be an image id or URL.
		 *
		 * $media_lib can be 'Media Library', 'NextGEN Gallery', 'Content', etc.
		 */
		public function img_size_within_limits( $img_mixed, $size_name, $img_width, $img_height, $media_lib = null ) {

			$cf_min    = $this->p->cf['head']['limit_min'];
			$cf_max    = $this->p->cf['head']['limit_max'];
			$img_ratio = 0;
			$img_label = $img_mixed;

			if ( strpos( $size_name, $this->p->lca.'-' ) !== 0 ) {	// only check our own sizes
				return true;
			}

			if ( $media_lib === null ) {
				$media_lib = __( 'Media Library', 'wpsso' );
			}

			if ( is_numeric( $img_mixed ) ) {

				$edit_url  = get_edit_post_link( $img_mixed );
				$img_label = sprintf( __( 'image ID %s', 'wpsso' ), $img_mixed );
				$img_label = empty( $edit_url ) ? $img_label : '<a href="'.$edit_url.'">'.$img_label.'</a>';

			} elseif ( strpos( $img_mixed, '://' ) !== false ) {

				if ( $img_width === WPSSO_UNDEF_INT || $img_height === WPSSO_UNDEF_INT ) {
					list( $img_width, $img_height, $img_type, $img_attr ) = $this->p->util->get_image_url_info( $img_mixed );
				}

				$img_label = '<a href="'.$img_mixed.'">'.$img_mixed.'</a>';
				$img_label = sprintf( __( 'image URL %s', 'wpsso' ), $img_mixed );
			}

			/**
			 * Exit silently if the image width and/or height is not valid.
			 */
			if ( $img_width === WPSSO_UNDEF_INT || $img_height === WPSSO_UNDEF_INT ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: '.strtolower( $media_lib ).' '.$img_mixed.' rejected - '.
						'invalid width and/or height '.$img_width.'x'.$img_height );
				}
				return false;	// image rejected
			}

			if ( $img_width > 0 && $img_height > 0 ) {
				$img_ratio = $img_width >= $img_height ? $img_width / $img_height : $img_height / $img_width;
			}

			switch ( $size_name ) {
				case $this->p->lca.'-opengraph':
					$spec_name  = 'Facebook / Open Graph';
					$min_width  = $cf_min['og_img_width'];
					$min_height = $cf_min['og_img_height'];
					$max_ratio  = $cf_max['og_img_ratio'];
					break;

				case $this->p->lca.'-schema':
					$spec_name  = 'Google / Schema';
					$min_width  = $cf_min['schema_img_width'];
					$min_height = $cf_min['schema_img_height'];
					$max_ratio  = $cf_max['schema_img_ratio'];
					break;

				case $this->p->lca.'-schema-article':
					$spec_name  = 'Google / Schema Article';
					$min_width  = $cf_min['schema_article_img_width'];
					$min_height = $cf_min['schema_article_img_height'];
					$max_ratio  = $cf_max['schema_article_img_ratio'];
					break;

				default:
					$spec_name  = '';
					$min_width  = 0;
					$min_height = 0;
					$max_ratio  = 0;
					break;
			}

			/**
			 * Filter name example: 'wpsso_opengraph_img_size_limits'.
			 */
			list( $min_width, $min_height, $max_ratio ) = apply_filters( SucomUtil::sanitize_hookname( $size_name ).'_img_size_limits',
				array( $min_width, $min_height, $max_ratio ) );

			/**
			 * Check the maximum image aspect ratio.
			 */
			if ( $max_ratio > 0 && $img_ratio >= $max_ratio ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: '.strtolower( $media_lib ).' '.$img_mixed.' rejected - '.
						$img_width.'x'.$img_height.' aspect ratio is equal to/or greater than '.$max_ratio.':1' );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {	// skip if notices already shown

					$dismiss_key  = 'image_' . $img_mixed . '_' . $img_width . 'x' . $img_height . '_' . $size_name . '_ratio_greater_than_allowed';
					$dismiss_time = true;
					$size_label   = $this->p->util->get_image_size_label( $size_name );
					$error_msg    = __( '%1$s %2$s ignored &mdash; the resulting image of %3$s has an <strong>aspect ratio equal to/or greater than %4$d:1 allowed by the %5$s standard</strong>.', 'wpsso' );
					$rejected_msg = $this->p->msgs->get( 'notice-image-rejected', array( 'size_label' => $size_label, 'allow_upscale' => false ) );

		 			/**
					 * $media_lib can be 'Media Library', 'NextGEN Gallery', 'Content', etc.
					 */
					$this->p->notice->err( sprintf( $error_msg, $media_lib, $img_label, $img_width.'x'.$img_height,
						$max_ratio, $spec_name ).' '.$rejected_msg, true, $dismiss_key, $dismiss_time );
				}

				return false;	// image rejected
			}

			/**
			 * Check the minimum image width and/or height.
			 */
			if ( ( $min_width > 0 || $min_height > 0 ) && ( $img_width < $min_width || $img_height < $min_height ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: '.strtolower( $media_lib ).' '.$img_mixed.' rejected - '.
						$img_width.'x'.$img_height.' smaller than minimum '.$min_width.'x'.$min_height.' for '.$size_name );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {	// Skip if notices already shown.

					$dismiss_key  = 'image_' . $img_mixed . '_' . $img_width . 'x' . $img_height . '_' . $size_name . '_smaller_than_minimum_allowed';
					$dismiss_time = true;
					$size_label   = $this->p->util->get_image_size_label( $size_name );
					$error_msg    = __( '%1$s %2$s ignored &mdash; the resulting image of %3$s is <strong>smaller than the minimum of %4$s allowed by the %5$s standard</strong>.', 'wpsso' );
					$rejected_msg = $this->p->msgs->get( 'notice-image-rejected', array( 'size_label' => $size_label, 'allow_upscale' => true ) );

		 			/**
					 * $media_lib can be 'Media Library', 'NextGEN Gallery', 'Content', etc.
					 */
					$this->p->notice->err( sprintf( $error_msg, $media_lib, $img_label, $img_width.'x'.$img_height,
						$min_width.'x'.$min_height, $spec_name ).' '.$rejected_msg, true, $dismiss_key, $dismiss_time );
				}

				return false;	// image rejected
			}

			return true;	// image accepted
		}

		public function can_make_size( $img_meta, $size_info ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$full_width = empty( $img_meta['width'] ) ? 0 : $img_meta['width'];
			$full_height = empty( $img_meta['height'] ) ? 0 : $img_meta['height'];

			$is_sufficient_w = $full_width >= $size_info['width'] ? true : false;
			$is_sufficient_h = $full_height >= $size_info['height'] ? true : false;

			$img_cropped = empty( $size_info['crop'] ) ? 0 : 1;
			$upscale_multiplier = 1;

			if ( $this->p->options['plugin_upscale_images'] ) {
				$img_info            = (array) self::get_image_src_info();
				$upscale_img_max     = apply_filters( $this->p->lca . '_image_upscale_max', $this->p->options['plugin_upscale_img_max'], $img_info );
				$upscale_multiplier  = 1 + ( $upscale_img_max / 100 );
				$upscale_full_width  = round( $full_width * $upscale_multiplier );
				$upscale_full_height = round( $full_height * $upscale_multiplier );
				$is_sufficient_w     = $upscale_full_width >= $size_info['width'] ? true : false;
				$is_sufficient_h     = $upscale_full_height >= $size_info['height'] ? true : false;
			}

			if ( ( ! $img_cropped && ( ! $is_sufficient_w && ! $is_sufficient_h ) ) ||
				( $img_cropped && ( ! $is_sufficient_w || ! $is_sufficient_h ) ) ) {
				$ret = false;
			} else {
				$ret = true;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'full size image of '.$full_width.'x'.$full_height.( $upscale_multiplier !== 1 ?
					' ('.$upscale_full_width.'x'.$upscale_full_height.' upscaled by '.$upscale_multiplier.')' : '' ).
					( $ret ? ' sufficient' : ' too small' ).' to create size '.$size_info['width'].'x'.$size_info['height'].
					( $img_cropped ? ' cropped' : '' ) );
			}

			return $ret;
		}

		public function add_og_video_from_url( array &$og_video, $url ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Fetch HTML using the Facebook user agent to get Open Graph meta tags.
			 *
			 * get_head_meta( $request, $query, $libxml_errors, $curl_opts );
			 */
			$curl_opts = array( 'CURLOPT_USERAGENT' => WPSSO_PHP_CURL_USERAGENT_FACEBOOK );

			$metas = $this->p->util->get_head_meta( $url, '//meta', false, $curl_opts );

			if ( isset( $metas['meta'] ) ) {

				foreach ( $metas as $m ) {		// Loop through all meta tags.

					foreach ( $m as $a ) {		// Loop through all attributes for that meta tag.

						$meta_type = key( $a );
						$meta_name = reset( $a );
						$meta_match = $meta_type.'-'.$meta_name;

						switch ( $meta_match ) {

							/**
							 * Use the property meta tag content as-is.
							 */
							case 'property-og:video:width':
							case 'property-og:video:height':
							case 'property-og:video:type':
							case ( strpos( $meta_match, 'property-al:' ) === 0 ? true : false ):	// Facebook AppLink.
								if ( ! empty( $a['content'] ) ) {
									$og_video[$a['property']] = $a['content'];
								}
								break;

							/**
							 * Add the property meta tag content as an array.
							 */
							case 'property-og:video:tag':
								if ( ! empty( $a['content'] ) ) {
									$og_video[$a['property']][] = $a['content'];	// Array of tags.
								}
								break;

							case 'property-og:image:secure_url':
								if ( ! empty( $a['content'] ) ) {

									/**
									 * Add the meta name as a query string to know where the value came from.
									 */
									$a['content'] = add_query_arg( 'm', $meta_name, $a['content'] );

									if ( ! empty( $this->p->options['add_meta_property_og:image:secure_url'] ) ) {
										$og_video['og:image:secure_url'] = $a['content'];
									} else {
										$og_video['og:image'] = $a['content'];
									}

									$og_video['og:video:thumbnail_url'] = $a['content'];
									$og_video['og:video:has_image'] = true;
								}
								break;

							case 'property-og:image:url':
							case 'property-og:image':
								if ( ! empty( $a['content'] ) ) {

									// add the meta name as a query string to know where the value came from
									$a['content'] = add_query_arg( 'm', $meta_name, $a['content'] );

									if ( strpos( $a['content'], 'https:' ) === 0 &&
										! empty( $this->p->options['add_meta_property_og:image:secure_url'] ) ) {
										$og_video['og:image:secure_url'] = $a['content'];
									}

									$og_video['og:image'] = $a['content'];
									$og_video['og:video:thumbnail_url'] = $a['content'];
									$og_video['og:video:has_image'] = true;
								}
								break;

							/**
							 * Add additional, non-standard properties, like og:video:title and og:video:description.
							 */
							case 'property-og:title':
							case 'property-og:description':
								if ( ! empty( $a['content'] ) ) {
									$og_key = 'og:video:'.substr( $a['property'], 3 );
									$og_video[$og_key] = $this->p->util->cleanup_html_tags( $a['content'] );
									if ( $this->p->debug->enabled ) {
										$this->p->debug->log( 'adding '.$og_key.' = '.$og_video[$og_key] );
									}
								}
								break;

							/**
							 * twitter:app:name:iphone
							 * twitter:app:id:iphone
							 * twitter:app:url:iphone
							 */
							case ( strpos( $meta_match, 'name-twitter:app:' ) === 0 ? true : false ):	// Twitter Apps
								if ( ! empty( $a['content'] ) ) {
									if ( preg_match( '/^twitter:app:([a-z]+):([a-z]+)$/', $meta_name, $matches ) ) {
										$og_video['og:video:'.$matches[2].'_'.$matches[1]] = SucomUtil::decode_html( $a['content'] );
									}
								}
								break;

							case 'itemprop-datePublished':
								if ( ! empty( $a['content'] ) ) {
									$og_video['og:video:upload_date'] = gmdate( 'c', strtotime( $a['content'] ) );
								}
								break;

							case 'itemprop-embedUrl':
							case 'itemprop-embedURL':
								if ( ! empty( $a['content'] ) ) {
									$og_video['og:video:embed_url'] = SucomUtil::decode_html( $a['content'] );
								}
								break;
						}
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $og_video );
				}
	
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'no head meta found in '.$url );
			}
		}
	}
}
