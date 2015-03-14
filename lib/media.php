<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoMedia' ) ) {

	class WpssoMedia {

		private $p;

		public $data_tags_preg = '(img)';
		public $data_attr_preg = '(data-[a-z]+-pid)';

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();

			// prevent image_downsize() from lying about image width and height
			if ( is_admin() )
				add_filter( 'editor_max_image_size', array( &$this, 'editor_max_image_size' ), 10, 3 );

			add_filter( 'wp_get_attachment_image_attributes', array( &$this, 'add_attachment_image_attributes' ), 10, 2 );
			add_filter( 'get_image_tag', array( &$this, 'add_image_tag' ), 10, 6 );
		}

		// note that $size_name can be a string or an array()
		public function editor_max_image_size( $max_sizes = array(), $size_name = '', $context = '' ) {
			// allow only our sizes to exceed the editor width
			if ( is_string( $size_name ) &&
				strpos( $size_name, $this->p->cf['lca'].'-' ) !== false )
					$max_sizes = array( 0, 0 );
			return $max_sizes;
		}

		// $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment );
		public function add_attachment_image_attributes( $attr, $attach ) {
			$attr['data-wp-pid'] = $attach->ID;
			return $attr;
		}

		// $html = apply_filters( 'get_image_tag', $html, $id, $alt, $title, $align, $size );
		public function add_image_tag( $html, $id, $alt, $title, $align, $size ) {
			if ( strpos( $html, ' data-wp-pid=' ) === false )
				$html = preg_replace( '/ *\/?>/', ' data-wp-pid="'.$id.'"$0', $html );
			return $html;
		}

		public function get_size_info( $size_name = 'thumbnail' ) {
			if ( is_integer( $size_name ) ) 
				return;
			if ( is_array( $size_name ) ) 
				return;

			global $_wp_additional_image_sizes;

			if ( isset( $_wp_additional_image_sizes[$size_name]['width'] ) )
				$width = intval( $_wp_additional_image_sizes[$size_name]['width'] );
			else $width = get_option( $size_name.'_size_w' );

			if ( isset( $_wp_additional_image_sizes[$size_name]['height'] ) )
				$height = intval( $_wp_additional_image_sizes[$size_name]['height'] );
			else $height = get_option( $size_name.'_size_h' );

			if ( isset( $_wp_additional_image_sizes[$size_name]['crop'] ) )
				$crop = $_wp_additional_image_sizes[$size_name]['crop'];
			else $crop = get_option( $size_name.'_crop' );

			if ( ! is_array( $crop ) )
				$crop = empty( $crop ) ? false : true;

			return array( 'width' => $width, 'height' => $height, 'crop' => $crop );
		}

		public function num_remains( &$arr, $num = 0 ) {
			$remains = 0;
			if ( ! is_array( $arr ) ) return false;
			if ( $num > 0 && $num >= count( $arr ) )
				$remains = $num - count( $arr );
			return $remains;
		}

		public function get_post_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $meta_pre = 'og' ) {
			$this->p->debug->args( array(
				'num' => $num,
				'size_name' => $size_name,
				'post_id' => $post_id,
				'check_dupes' => $check_dupes,
				'meta_pre' => $meta_pre,
			) );
			$og_ret = array();
			$force_regen = false;

			if ( ! empty( $post_id ) ) {

				if ( ! empty( $this->p->options['plugin_auto_img_resize'] ) ) {
					$force_regen_transient_id = $this->p->cf['lca'].'_post_'.$post_id.'_regen_'.$meta_pre;
					$force_regen = get_transient( $force_regen_transient_id );
					if ( $force_regen !== false )
						delete_transient( $force_regen_transient_id );
				} 
	
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
					$num_remains = $this->num_remains( $og_ret, $num );
					$og_ret = array_merge( $og_ret, $this->p->mods['util']['postmeta']->get_og_image( $num_remains, 
						$size_name, $post_id, $check_dupes, $force_regen, $meta_pre ) );
				}
			}
	
			// allow for empty post_id in order to execute featured/attached image filters for modules
			if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$num_remains = $this->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, $this->get_featured( $num_remains, 
					$size_name, $post_id, $check_dupes, $force_regen ) );
			}

			if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$num_remains = $this->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, $this->get_attached_images( $num_remains, 
					$size_name, $post_id, $check_dupes, $force_regen ) );
			}

			return $og_ret;
		}

		public function get_featured( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $force_regen = false ) {
			$this->p->debug->args( array(
				'num' => $num,
				'size_name' => $size_name,
				'post_id' => $post_id,
				'check_dupes' => $check_dupes,
				'force_regen' => $force_regen,
			) );
			$og_ret = array();
			$og_image = array();
			if ( ! empty( $post_id ) ) {
				// check for an attachment page, just in case
				if ( ( is_attachment( $post_id ) || get_post_type( $post_id ) === 'attachment' ) &&
					wp_attachment_is_image( $post_id ) ) {
					$this->p->debug->log( 'post_type is an attachment - using post_id '.$post_id. ' as the image id' );
					$pid = $post_id;
				} elseif ( $this->p->is_avail['postthumb'] == true && has_post_thumbnail( $post_id ) )
					$pid = get_post_thumbnail_id( $post_id );
				else $pid = false;

				if ( ! empty( $pid ) ) {
					list( $og_image['og:image'], $og_image['og:image:width'], $og_image['og:image:height'], $og_image['og:image:cropped'], 
						$og_image['og:image:id'] ) = $this->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );
					if ( ! empty( $og_image['og:image'] ) )
						$this->p->util->push_max( $og_ret, $og_image, $num );
				}
			}
			return apply_filters( $this->p->cf['lca'].'_og_featured', $og_ret, $num, 
				$size_name, $post_id, $check_dupes, $force_regen );
		}

		public function get_first_attached_image_id( $post_id ) {
			if ( ! empty( $post_id ) ) {
				// check for an attachment page, just in case
				if ( ( is_attachment( $post_id ) || get_post_type( $post_id ) === 'attachment' ) &&
					wp_attachment_is_image( $post_id ) )
						return $post_id;
				else {
					$images = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image' ) );
					$attach = reset( $images );
					if ( ! empty( $attach->ID ) )
						return $attach->ID;
				}
			}
			return false;
		}

		public function get_attachment_image( $num = 0, $size_name = 'thumbnail', $attach_id, $check_dupes = true, $force_regen = false ) {
			$this->p->debug->args( array( 
				'num' => $num,
				'size_name' => $size_name,
				'attach_id' => $attach_id,
				'check_dupes' => $check_dupes,
				'force_regen' => $force_regen,
			) );
			$og_ret = array();
			if ( ! empty( $attach_id ) ) {
				if ( wp_attachment_is_image( $attach_id ) ) {	// since wp 2.1.0 
					$og_image = array();
					list( $og_image['og:image'], $og_image['og:image:width'], $og_image['og:image:height'], $og_image['og:image:cropped'], 
						$og_image['og:image:id'] ) = $this->get_attachment_image_src( $attach_id, $size_name, $check_dupes, $force_regen );
					if ( ! empty( $og_image['og:image'] ) &&
						$this->p->util->push_max( $og_ret, $og_image, $num ) )
							return $og_ret;
				} else $this->p->debug->log( 'attachment id '.$attach_id.' is not an image' );
			}
			return $og_ret;
		}

		public function get_attached_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $force_regen = false ) {
			$this->p->debug->args( array(
				'num' => $num,
				'size_name' => $size_name,
				'post_id' => $post_id,
				'check_dupes' => $check_dupes,
				'force_regen' => $force_regen,
			) );
			$og_ret = array();
			if ( ! empty( $post_id ) ) {
				$images = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image') );
				if ( is_array( $images ) )
					$attach_ids = array();
					foreach ( $images as $attach ) {
						if ( ! empty( $attach->ID ) )
							$attach_ids[] = $attach->ID;
					}
					rsort( $attach_ids, SORT_NUMERIC ); 
					$this->p->debug->log( 'found '.count( $attach_ids ).' attached images for post_id '.$post_id );
					$attach_ids = apply_filters( $this->p->cf['lca'].'_attached_image_ids', $attach_ids, $post_id );
					foreach ( $attach_ids as $pid ) {
						$og_image = array();
						list( $og_image['og:image'], $og_image['og:image:width'], $og_image['og:image:height'], $og_image['og:image:cropped'],
							$og_image['og:image:id'] ) = $this->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );
						if ( ! empty( $og_image['og:image'] ) &&
							$this->p->util->push_max( $og_ret, $og_image, $num ) )
								break;	// end foreach and apply filters
					}
			}
			return apply_filters( $this->p->cf['lca'].'_attached_images', $og_ret, $num, 
				$size_name, $post_id, $check_dupes );
		}

		public function get_attachment_image_src( $pid, $size_name = 'thumbnail', $check_dupes = true, $force_regen = false ) {
			$this->p->debug->args( array(
				'pid' => $pid,
				'size_name' => $size_name,
				'check_dupes' => $check_dupes,
				'force_regen' => $force_regen,
			) );
			$size_info = $this->get_size_info( $size_name );
			$img_url = '';
			$img_width = -1;
			$img_height = -1;
			$img_cropped = $size_info['crop'] === false ? 0 : 1;	// get_size_info() returns false, true, or an array
			$ret_empty = array( null, null, null, null, null );

			if ( $this->p->is_avail['media']['ngg'] === true && strpos( $pid, 'ngg-' ) === 0 ) {
				if ( ! empty( $this->p->mods['media']['ngg'] ) )
					return $this->p->mods['media']['ngg']->get_image_src( $pid, $size_name, $check_dupes );
				else {
					if ( is_admin() )
						$this->p->notice->err( 'The NextGEN Gallery module is not available: image id '.$pid.' ignored.' ); 
					$this->p->debug->log( 'ngg module is not available: image id '.$attr_value.' ignored' ); 
					return $ret_empty; 
				}
			} elseif ( ! wp_attachment_is_image( $pid ) ) {
				$this->p->debug->log( 'exiting early: attachment '.$pid.' is not an image' ); 
				return $ret_empty; 
			}

			if ( strpos( $size_name, $this->p->cf['lca'].'-' ) !== false ) {		// only resize our own custom image sizes
				if ( ! empty( $this->p->options['plugin_auto_img_resize'] ) ) {		// auto-resize images option must be enabled

					$img_meta = wp_get_attachment_metadata( $pid );

					// does the image metadata contain our image sizes?
					if ( $force_regen === true || empty( $img_meta['sizes'][$size_name] ) ) {
						$is_accurate_width = false;
						$is_accurate_height = false;
					} else {
						// is the width and height in the image metadata accurate?
						$is_accurate_width = ! empty( $img_meta['sizes'][$size_name]['width'] ) &&
							$img_meta['sizes'][$size_name]['width'] == $size_info['width'] ? true : false;
						$is_accurate_height = ! empty( $img_meta['sizes'][$size_name]['height'] ) &&
							$img_meta['sizes'][$size_name]['height'] == $size_info['height'] ? true : false;

						// if not cropped, make sure the resized image respects the original aspect ratio
						if ( $is_accurate_width && $is_accurate_height && $img_cropped === 0 ) {
							if ( $img_meta['width'] > $img_meta['height'] ) {
								$ratio = $img_meta['width'] / $size_info['width'];
								$check = 'height';
							} else {
								$ratio = $img_meta['height'] / $size_info['height'];
								$check = 'width';
							}
							$should_be = (int) round( $img_meta[$check] / $ratio );
							// allow for a +/-1 pixel difference
							if ( $img_meta['sizes'][$size_name][$check] < ( $should_be - 1 ) ||
								$img_meta['sizes'][$size_name][$check] > ( $should_be + 1 ) ) {
									$is_accurate_width = false;
									$is_accurate_height = false;
							}
						}
					}

					// depending on cropping, one or both sides of the image must be accurate
					// if not, attempt to create a resized image by calling image_make_intermediate_size()
					if ( ( empty( $size_info['crop'] ) && ( ! $is_accurate_width && ! $is_accurate_height ) ) ||
						( ! empty( $size_info['crop'] ) && ( ! $is_accurate_width || ! $is_accurate_height ) ) ) {

						if ( $this->p->debug->is_on() ) {
							if ( empty( $img_meta['sizes'][$size_name] ) )
								$this->p->debug->log( $size_name.' size not defined in the image meta' );
							else $this->p->debug->log( 'image metadata ('.
								( empty( $img_meta['sizes'][$size_name]['width'] ) ? 0 : $img_meta['sizes'][$size_name]['width'] ).'x'.
								( empty( $img_meta['sizes'][$size_name]['height'] ) ? 0 : $img_meta['sizes'][$size_name]['height'] ).') does not match '.
								$size_name.' ('.$size_info['width'].'x'.$size_info['height'].( $img_cropped === 0 ? '' : ' cropped' ).')' );
						}
	
						$fullsizepath = get_attached_file( $pid );
						$resized = image_make_intermediate_size( $fullsizepath, $size_info['width'], $size_info['height'], $size_info['crop'] );
						$this->p->debug->log( 'image_make_intermediate_size() reported '.( $resized === false ? 'failure' : 'success' ) );
						if ( $resized !== false ) {
							$img_meta['sizes'][$size_name] = $resized;
							wp_update_attachment_metadata( $pid, $img_meta );
						}
					}
				} else $this->p->debug->log( 'image metadata check skipped: plugin_auto_img_resize option is disabled' );
			}

			list( $img_url, $img_width, $img_height ) = apply_filters( $this->p->cf['lca'].'_image_downsize', 
				image_downsize( $pid, $size_name ), $pid, $size_name );
			$this->p->debug->log( 'image_downsize() = '.$img_url.' ('.$img_width.'x'.$img_height.')' );

			if ( empty( $img_url ) ) {
				$this->p->debug->log( 'exiting early: returned image_downsize() url is empty' );
				return $ret_empty;
			}

			// check for resulting image dimensions that may be too small
			if ( ! empty( $this->p->options['plugin_ignore_small_img'] ) ) {

				$is_sufficient_width = $img_width >= $size_info['width'] ? true : false;
				$is_sufficient_height = $img_height >= $size_info['height'] ? true : false;

				if ( $img_width > 0 && $img_height > 0 )	// just in case
					$ratio = $img_width >= $img_height ? 
						$img_width / $img_height : 
						$img_height / $img_width;
				else $ratio = 0;

				// depending on cropping, one or both sides of the image must be large enough / sufficient
				// return an empty array after showing an appropriate warning
				if ( ( empty( $size_info['crop'] ) && ( ! $is_sufficient_width && ! $is_sufficient_height ) ) ||
					( ! empty( $size_info['crop'] ) && ( ! $is_sufficient_width || ! $is_sufficient_height ) ) ) {

					$img_meta = wp_get_attachment_metadata( $pid );
					$is_too_small_text = ' is too small for '.$size_name.' ('.$size_info['width'].'x'.$size_info['height'].
						( $img_cropped === 0 ? '' : ' cropped' ).')';
					if ( ! empty( $img_meta['width'] ) && ! empty( $img_meta['height'] ) &&
						$img_meta['width'] < $size_info['width'] && $img_meta['height'] < $size_info['height'] )
							$rejected_text = 'image id '.$pid.' rejected - full size image ('.
								$img_meta['width'].'x'.$img_meta['height'].')'.$is_too_small_text;
					else $rejected_text = 'image id '.$pid.' rejected - '.$img_width.'x'.$img_height.$is_too_small_text;
					$this->p->debug->log( 'exiting early: '.$rejected_text );
					if ( is_admin() )
						$this->p->notice->err( 'Media Library '.$rejected_text.
							'. Upload a larger / different image or adjust the "'.
							$this->p->util->get_image_size_label( $size_name ).
							'" option.', false, true, 'dim_wp_'.$pid );
					return $ret_empty;

				// if this is an open graph image, make sure it is larger than 200x200
				} elseif ( $size_name == $this->p->cf['lca'].'-opengraph' &&
					( $img_width < $this->p->cf['head']['min_img_dim'] ||
					$img_height < $this->p->cf['head']['min_img_dim'] ) ) {

					$this->p->debug->log( 'exiting early: image id '.$pid.' rejected - '.
						$img_width.'x'.$img_height.' is smaller than the hard-coded minimum of '.
						$this->p->cf['head']['min_img_dim'].'x'.$this->p->cf['head']['min_img_dim'] );
					if ( is_admin() )
						$this->p->notice->err( 'Media Library image id '.$pid.' rejected - the resulting '.
							$img_width.'x'.$img_height.' image for the "'.
							$this->p->util->get_image_size_label( $size_name ).
							'" option is smaller than the hard-coded minimum of '.
							$this->p->cf['head']['min_img_dim'].'x'.$this->p->cf['head']['min_img_dim'].
							' allowed by the Facebook / Open Graph standard.', false, true, 'dim_wp_'.$pid );
					return $ret_empty;

				} elseif ( $ratio >= $this->p->cf['head']['max_img_ratio'] ) {

					$rejected_text = 'image id '.$pid.' rejected - '.$img_width.'x'.$img_height.
						' aspect ratio is equal to / or greater than '.$this->p->cf['head']['max_img_ratio'].':1';
					$this->p->debug->log( 'exiting early: '.$rejected_text );
					if ( is_admin() )
						$this->p->notice->err( 'Media Library '.$rejected_text.
							'. Upload a larger / different image or adjust the "'.
							$this->p->util->get_image_size_label( $size_name ).
							'" option.', false, true, 'dim_wp_'.$pid );
					return $ret_empty;

				} else $this->p->debug->log( 'returned image dimensions ('.$img_width.'x'.$img_height.') are sufficient' );
			}

			if ( $check_dupes == false || $this->p->util->is_uniq_url( $img_url ) )
				return array( apply_filters( $this->p->cf['lca'].'_rewrite_url', $img_url ),
					$img_width, $img_height, $img_cropped, $pid );

			return $ret_empty;
		}

		public function get_author_image( $num = 0, $size_name = 'thumbnail', $author_id, $check_dupes = true, $force_regen = false ) {
			$this->p->debug->args( array(
				'num' => $num,
				'size_name' => $size_name,
				'author_id' => $author_id,
				'check_dupes' => $check_dupes,
				'force_regen' => $force_regen,
			) );
			$og_ret = array();
			$og_image = array();

			if ( empty( $author_id ) || ! isset( $this->p->mods['util']['user'] ) )
				return $og_ret;

			$pid = $this->p->mods['util']['user']->get_options( $author_id, 'og_img_id' );
			$pre = $this->p->mods['util']['user']->get_options( $author_id, 'og_img_id_pre' );
			$img_url = $this->p->mods['util']['user']->get_options( $author_id, 'og_img_url', array( 'size_name' => $size_name ) );

			if ( $pid > 0 ) {
				$pid = $pre === 'ngg' ? 'ngg-'.$pid : $pid;
				$this->p->debug->log( 'found custom user image id = '.$pid );
				list(
					$og_image['og:image'], 
					$og_image['og:image:width'], 
					$og_image['og:image:height'], 
					$og_image['og:image:cropped'], 
					$og_image['og:image:id']
				) = $this->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );
			}

			if ( empty( $og_image['og:image'] ) && ! empty( $img_url ) ) {
				$this->p->debug->log( 'found custom user image url = "'.$img_url.'"' );
				list(
					$og_image['og:image'],
					$og_image['og:image:width'],
					$og_image['og:image:height'],
					$og_image['og:image:cropped'], 
					$og_image['og:image:id']
				) = array( $img_url, -1, -1, -1, -1 );
			}

			if ( ! empty( $og_image['og:image'] ) &&
				$this->p->util->push_max( $og_ret, $og_image, $num ) )
					return $og_ret;
			return $og_ret;
		}

		public function get_default_image( $num = 0, $size_name = 'thumbnail', $check_dupes = true, $force_regen = false ) {
			$this->p->debug->args( array(
				'num' => $num,
				'size_name' => $size_name,
				'check_dupes' => $check_dupes,
				'force_regen' => $force_regen,
			) );
			$og_ret = array();
			$og_image = array();

			$pid = empty( $this->p->options['og_def_img_id'] ) ? '' : $this->p->options['og_def_img_id'];
			$pre = empty( $this->p->options['og_def_img_id_pre'] ) ? '' : $this->p->options['og_def_img_id_pre'];
			$url = empty( $this->p->options['og_def_img_url'] ) ? '' : $this->p->options['og_def_img_url'];

			if ( $pid === '' && $url === '' ) {
				$this->p->debug->log( 'exiting early: no default image defined' );
				return $og_ret;
			}

			if ( $pid > 0 ) {
				$pid = $pre === 'ngg' ? 'ngg-'.$pid : $pid;
				$this->p->debug->log( 'using default img pid = '.$pid );
				list(
					$og_image['og:image'],
					$og_image['og:image:width'],
					$og_image['og:image:height'],
					$og_image['og:image:cropped'],
					$og_image['og:image:id']
				) = $this->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );
			}

			if ( empty( $og_image['og:image'] ) && ! empty( $url ) ) {
				$this->p->debug->log( 'using default img url = '.$url );
				$og_image = array();	// clear all array values
				$og_image['og:image'] = $url;
			}

			if ( ! empty( $og_image['og:image'] ) && 
				$this->p->util->push_max( $og_ret, $og_image, $num ) )
					return $og_ret;
			return $og_ret;
		}

		public function get_content_images( $num = 0, $size_name = 'thumbnail', $post_id = 0, $check_dupes = true, $content = '' ) {
			$this->p->debug->args( array( 'num' => $num, 'size_name' => $size_name, 'post_id' => $post_id, 'check_dupes' => $check_dupes, 'content' => strlen( $content ).' chars' ) );
			$og_ret = array();
			$size_info = $this->get_size_info( $size_name );

			// allow custom content to be passed as argument
			if ( empty( $content ) )
				$content = $this->p->webpage->get_content( $post_id, false );	// use_post = false

			if ( empty( $content ) ) { 
				$this->p->debug->log( 'exiting early: empty post content' ); 
				return $og_ret; 
			}

			// img attributes in order of preference
			// data_tags_preg provides a filter hook for 3rd party modules like ngg to return image information
			if ( preg_match_all( '/<('.$this->data_tags_preg.'[^>]*? '.$this->data_attr_preg.'=[\'"]([0-9]+)[\'"]|'.
				'(img)[^>]*? (data-share-src|src)=[\'"]([^\'"]+)[\'"])[^>]*>/s', $content, $match, PREG_SET_ORDER ) ) {
				$this->p->debug->log( count( $match ).' x matching <'.$this->data_tags_preg.'/> html tag(s) found' );
				foreach ( $match as $img_num => $img_arr ) {
					$tag_value = $img_arr[0];
					if ( empty( $img_arr[5] ) ) {
						$tag_name = $img_arr[2];	// img
						$attr_name = $img_arr[3];	// data-wp-pid
						$attr_value = $img_arr[4];	// id
					} else {
						$tag_name = $img_arr[5];	// img
						$attr_name = $img_arr[6];	// data-share-src|src
						$attr_value = $img_arr[7];	// url
					}
					$this->p->debug->log( 'match '.$img_num.': '.$tag_name.' '.$attr_name.'="'.$attr_value.'"' );
					$og_image = array();
					switch ( $attr_name ) {
						case 'data-wp-pid' :
							list( $og_image['og:image'], $og_image['og:image:width'], $og_image['og:image:height'], $og_image['og:image:cropped'],
								$og_image['og:image:id'] ) = $this->get_attachment_image_src( $attr_value, $size_name, false );
							break;
						// filter hook for 3rd party modules to return image information
						case ( preg_match( '/^data-[a-z]+-pid$/', $attr_name ) ? true : false ):
							$filter_name = $this->p->cf['lca'].'_get_content_'.$tag_name.'_'.( preg_replace( '/-/', '_', $attr_name ) );
							list( $og_image['og:image'], $og_image['og:image:width'], $og_image['og:image:height'], $og_image['og:image:cropped'],
								$og_image['og:image:id'] ) = apply_filters( $filter_name, array( null, null, null, null ), $attr_value, $size_name, false );
							break;
						default :
							// prevent duplicates by silently ignoring ngg images (already processed by the ngg module)
							if ( $this->p->is_avail['media']['ngg'] === true && 
								! empty( $this->p->mods['media']['ngg'] ) &&
									( strpos( $tag_value, " class='ngg-" ) !== false || 
										preg_match( '/^'.$this->p->mods['media']['ngg']->img_src_preg.'$/', $attr_value ) ) )
											break;
	
							// recognize gravatar images in the content
							if ( preg_match( '/^https?:\/\/([^\.]+\.)?gravatar\.com\/avatar\/[a-zA-Z0-9]+/', $attr_value, $match) ) {
								$og_image['og:image'] = $match[0].'?s='.$size_info['width'].'&d=404&r=G';
								$og_image['og:image:width'] = $size_info['width'];
								$og_image['og:image:height'] = $size_info['width'];
								break;
							}

							$og_image = array(
								'og:image' => $attr_value,
								'og:image:width' => -1,
								'og:image:height' => -1,
							);

							// try and get the width and height from the image attributes
							if ( ! empty( $og_image['og:image'] ) ) {
								if ( preg_match( '/ width=[\'"]?([0-9]+)[\'"]?/i', $tag_value, $match) ) 
									$og_image['og:image:width'] = $match[1];
								if ( preg_match( '/ height=[\'"]?([0-9]+)[\'"]?/i', $tag_value, $match) ) 
									$og_image['og:image:height'] = $match[1];
							}

							$is_sufficient_width = $og_image['og:image:width'] >= $size_info['width'] ? true : false;
							$is_sufficient_height = $og_image['og:image:height'] >= $size_info['height'] ? true : false;

							// make sure the image width and height are large enough
							if ( $attr_name == 'data-share-src' || 
								( $attr_name == 'src' && empty( $this->p->options['plugin_ignore_small_img'] ) ) ||
								( $attr_name == 'src' && $size_info['crop'] === 1 && $is_sufficient_width && $is_sufficient_height ) ||
								( $attr_name == 'src' && $size_info['crop'] !== 1 && ( $is_sufficient_width || $is_sufficient_height ) ) ) {

								// data-share-src attribute used and/or image size is acceptable
								// check for relative urls, just in case
								$og_image['og:image'] = $this->p->util->fix_relative_url( $og_image['og:image'] );

							} else {
								if ( is_admin() && $this->p->debug->is_on() === true )
									$this->p->notice->err( 'Image '.$og_image['og:image'].' rejected - width / height missing or too small for '.$size_name.'.' );
								$this->p->debug->log( 'image rejected: width / height attributes missing or too small for '.$size_name.' size.' );
								$og_image = array();
							}
							break;
					}
					if ( ! empty( $og_image['og:image'] ) && 
						( $check_dupes === false || $this->p->util->is_uniq_url( $og_image['og:image'] ) ) )
							if ( $this->p->util->push_max( $og_ret, $og_image, $num ) )
								return $og_ret;
				}
				return $og_ret;
			}
			$this->p->debug->log( 'no matching <'.$this->data_tags_preg.'/> html tag(s) found' );
			return $og_ret;
		}

		// called by TwitterCard class to build the Gallery Card
		public function get_gallery_images( $num = 0, $size_name = 'large', $get = 'gallery', $check_dupes = false ) {
			$this->p->debug->args( array(
				'num' => $num,
				'size_name' => $size_name,
				'get' => $get,
				'check_dupes' => $check_dupes,
			) );
			global $post;
			$og_ret = array();
			if ( $get == 'gallery' ) {
				if ( empty( $post ) ) { 
					$this->p->debug->log( 'exiting early: empty post object' ); 
					return $og_ret;
				} elseif ( empty( $post->post_content ) ) { 
					$this->p->debug->log( 'exiting early: empty post content' ); 
					return $og_ret;
				}
				if ( preg_match( '/\[(gallery)[^\]]*\]/im', $post->post_content, $match ) ) {
					$shortcode_type = strtolower( $match[1] );
					$this->p->debug->log( '['.$shortcode_type.'] shortcode found' );
					switch ( $shortcode_type ) {
						case 'gallery' :
							$content = do_shortcode( $match[0] );
							$content = preg_replace( '/\['.$shortcode_type.'[^\]]*\]/', '', $content );	// prevent loops, just in case
							// provide content to the method and extract its images
							// $post_id argument is 0 since we're passing the content
							$og_ret = array_merge( $og_ret, $this->p->media->get_content_images( $num, $size_name, 0, $check_dupes, $content ) );
							if ( ! empty( $og_ret ) ) 
								return $og_ret;		// return immediately and ignore any other type of image
							break;
					}
				} else $this->p->debug->log( '[gallery] shortcode(s) not found' );
			}
			// check for ngg gallery
			if ( $this->p->is_avail['media']['ngg'] === true && ! empty( $this->p->mods['media']['ngg'] ) ) {
				$og_ret = $this->p->mods['media']['ngg']->get_gallery_images( $num , $size_name, $get, $check_dupes );
				if ( $this->p->util->is_maxed( $og_ret, $num ) )
					return $og_ret;
			}
			$this->p->util->slice_max( $og_ret, $num );
			return $og_ret;
		}

		public function get_default_video( $num = 0, $check_dupes = true ) {
			$this->p->debug->args( array( 'num' => $num, 'check_dupes' => $check_dupes ) );
			$og_ret = array();
			$url = empty( $this->p->options['og_def_vid_url'] ) ? '' : $this->p->options['og_def_vid_url'];
			if ( ! empty( $url ) && 
				( $check_dupes == false || $this->p->util->is_uniq_url( $url ) ) ) {

				$this->p->debug->log( 'using default video url = '.$url );
				$og_video = $this->get_video_info( $url, 0, 0, $check_dupes );
				if ( empty( $og_video ) )	// fallback to the original custom video URL
					$og_video['og:video'] = $url;
				if ( $this->p->util->push_max( $og_ret, $og_video, $num ) ) 
					return $og_ret;
			}
			return $og_ret;
		}

		/* Purpose: Check the content for generic <iframe|embed/> html tags. Apply wpsso_content_videos filter for more specialized checks. */
		public function get_content_videos( $num = 0, $post_id = 0, $check_dupes = true, $content = '' ) {
			$this->p->debug->args( array( 'num' => $num, 'post_id' => $post_id, 'check_dupes' => $check_dupes, 'content' => strlen( $content ).' chars' ) );
			$og_ret = array();

			// allow custom content to be passed as argument
			if ( empty( $content ) )
				$content = $this->p->webpage->get_content( $post_id, false );	// use_post = false

			if ( empty( $content ) ) { 
				$this->p->debug->log( 'exiting early: empty post content' ); 
				return $og_ret; 
			}

			// detect standard iframe/embed tags - use the wpsso_content_videos filter for additional html5/javascript methods
			// the src url must contain /embed|embed_code|swf|video/ in its path to be recognized as an embedded video url
			if ( preg_match_all( '/<(iframe|embed)[^<>]*? src=[\'"]([^\'"<>]+\/(embed|embed_code|swf|video|v)\/[^\'"<>]+)[\'"][^<>]*>/i', $content, $match_all, PREG_SET_ORDER ) ) {
				$this->p->debug->log( count( $match_all ).' x video <iframe|embed/> html tag(s) found' );
				foreach ( $match_all as $media ) {
					$this->p->debug->log( '<'.$media[1].'/> html tag found = '.$media[2] );
					$embed_url = $media[2];
					if ( ! empty( $embed_url ) &&
						( $check_dupes == false || $this->p->util->is_uniq_url( $embed_url ) ) ) {
						$embed_width = preg_match( '/ width=[\'"]?([0-9]+)[\'"]?/i', $media[0], $match) ? $match[1] : -1;
						$embed_height = preg_match( '/ height=[\'"]?([0-9]+)[\'"]?/i', $media[0], $match) ? $match[1] : -1;
						$og_video = $this->get_video_info( $embed_url, $embed_width, $embed_height, $check_dupes );
						if ( ! empty( $og_video ) && 
							$this->p->util->push_max( $og_ret, $og_video, $num ) ) 
								return $og_ret;
					}
				}
			} else $this->p->debug->log( 'no <iframe|embed/> html tag(s) found' );

			$filter_name = $this->p->cf['lca'].'_content_videos';
			if ( has_filter( $filter_name ) ) {
				$this->p->debug->log( 'applying filter '.$filter_name ); 
				// should return an array of arrays
				if ( ( $match_all = apply_filters( $filter_name, false, $content ) ) !== false ) {
					if ( is_array( $match_all ) ) {
						$this->p->debug->log( count( $match_all ).' x videos returned by '.$filter_name.' filter' );
						foreach ( $match_all as $media ) {
							if ( ! empty( $media[0] ) && 
								( $check_dupes == false || $this->p->util->is_uniq_url( $media[0] ) ) ) {
								$og_video = $this->get_video_info( $media[0], $media[1], $media[2], $check_dupes );
								if ( ! empty( $og_video ) && 
									$this->p->util->push_max( $og_ret, $og_video, $num ) ) 
										return $og_ret;
							}
						}
					} else $this->p->debug->log( $filter_name.' filter did not return false or an array' ); 
				}
			}
			return $og_ret;
		}

		public function get_video_info( $embed_url, $embed_width = 0, $embed_height = 0, $check_dupes = true ) {
			if ( empty( $embed_url ) ) 
				return array();

			$og_video = array(
				'og:video' => '',
				'og:video:type' => 'application/x-shockwave-flash',
				'og:video:width' => $embed_width,
				'og:video:height' => $embed_height,
				'og:image' => '',
				'og:image:width' => -1,
				'og:image:height' => -1,
			);
			$og_video = apply_filters( $this->p->cf['lca'].'_video_info', $og_video, $embed_url, $embed_width, $embed_height );

			$this->p->debug->log( 'video = '.$og_video['og:video'].' ('.$og_video['og:video:width'].'x'.$og_video['og:video:height'].')' );
			$this->p->debug->log( 'image = '.$og_video['og:image'].' ('.$og_video['og:image:width'].'x'.$og_video['og:image:height'].')' );

			// cleanup any extra video meta tags - just in case
			if ( empty( $og_video['og:video'] ) || 
				( $check_dupes && ! $this->p->util->is_uniq_url( $og_video['og:video'] ) ) )
					unset ( 
						$og_video['og:video'],
						$og_video['og:video:type'],
						$og_video['og:video:width'],
						$og_video['og:video:height']
					);

			// cleanup any extra image meta tags, or remove the preview image 
			// if 'Include Video Preview Image' is not checked
			if ( empty( $this->p->options['og_vid_prev_img'] ) ||
				empty( $og_video['og:image'] ) || 
					( $check_dupes && ! $this->p->util->is_uniq_url( $og_video['og:image'] ) ) )
						unset ( 
							$og_video['og:image'],
							$og_video['og:image:secure_url'],
							$og_video['og:image:width'],
							$og_video['og:image:height']
						);

			if ( empty( $og_video['og:video'] ) && 
				empty( $og_video['og:image'] ) ) 
					return array();
			else return $og_video;
		}
	}
}

?>
