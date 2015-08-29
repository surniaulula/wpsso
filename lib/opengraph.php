<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoOpengraph' ) ) {

	class WpssoOpengraph {

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes' => 1,
			) );
			add_filter( 'language_attributes', 
				array( &$this, 'add_doctype' ), 100, 1 );
		}

		public function filter_plugin_image_sizes( $sizes ) {
			$sizes['rp_img'] = array(
				'name' => 'richpin',
				'label' => 'Pinterest Rich Pin'
			);
			$sizes['og_img'] = array( 
				'name' => 'opengraph', 
				'label' => 'Facebook / Open Graph'
			);
			return $sizes;
		}

		public function add_doctype( $doctype ) {
			/*
			 * HTML5 Compliant
			 */
			$html_prefix = apply_filters( $this->p->cf['lca'].'_doctype_prefix_ns', array(
				'og' => 'http://ogp.me/ns#',
				'fb' => 'http://ogp.me/ns/fb#',
			) );
	
			// find and extract an existing prefix attribute value
			if ( strpos( $doctype, ' prefix=' ) &&
				preg_match( '/^(.*) prefix=["\']([^"\']*)["\'](.*)$/', $doctype, $match ) ) {
					$doctype = $match[1].$match[3];
					$attr_value = ' '.$match[2];
			} else $attr_value = '';

			foreach ( $html_prefix as $ns => $url )
				if ( strpos( $attr_value, ' '.$ns.': '.$url ) === false )
					$attr_value .= ' '.$ns.': '.$url;

			$doctype .= ' prefix="'.trim( $attr_value ).'"';

			return $doctype;
		}

		public function get_array( &$og = array(), $use_post = false, $obj = false ) {

			if ( ! is_object( $obj ) )
				$obj = $this->p->util->get_post_object( $use_post );
			$post_id = empty( $obj->ID ) || empty( $obj->post_type ) || 
				( ! is_singular() && $use_post === false ) ? 0 : $obj->ID;

			$post_type = '';
			$video_previews = 0;
			$og_max = $this->p->util->get_max_nums( $post_id, 'post' );	// post_id 0 returns the plugin settings 
			$og = apply_filters( $this->p->cf['lca'].'_og_seed', $og, $use_post, $obj );

			if ( ! empty( $og ) && 
				$this->p->debug->enabled ) {
					$this->p->debug->log( $this->p->cf['lca'].'_og_seed filter returned:' );
					$this->p->debug->log( $og );
			}

			if ( ! isset( $og['fb:admins'] ) && ! empty( $this->p->options['fb_admins'] ) )
				foreach ( explode( ',', $this->p->options['fb_admins'] ) as $fb_admin )
					$og['fb:admins'][] = trim( $fb_admin );

			if ( ! isset( $og['fb:app_id'] ) )
				$og['fb:app_id'] = $this->p->options['fb_app_id'];

			if ( ! isset( $og['og:url'] ) )
				$og['og:url'] = $this->p->util->get_sharing_url( $use_post, true, 
					$this->p->util->get_source_id( 'opengraph' ) );

			// define the type after the url
			if ( ! isset( $og['og:type'] ) ) {

				// singular posts / pages are articles by default
				// check the post_type for a match with a known open graph type
				if ( is_singular() || $use_post !== false ) {

					if ( ! empty( $obj->post_type ) )
						$post_type = $obj->post_type;

					if ( isset( $this->p->cf['head']['og_type_ns'][$post_type] ) )
						$og['og:type'] = $post_type;
					else $og['og:type'] = 'article';

				// check for default author info on indexes and searches
				} elseif ( $this->p->util->force_default_author( $use_post, 'og' ) &&
					! empty( $this->p->options['og_def_author_id'] ) ) {

					$og['og:type'] = 'article';
					if ( ! isset( $og['article:author'] ) )
						$og['article:author'] = $this->p->mods['util']['user']->get_article_author( $this->p->options['og_def_author_id'] );

				// default for everything else is 'website'
				} else $og['og:type'] = 'website';

				$og['og:type'] = apply_filters( $this->p->cf['lca'].'_og_type', $og['og:type'], $use_post );
			}

			if ( ! isset( $og['og:locale'] ) ) {
				// get the current or configured language for og:locale
				$lang = empty( $this->p->options['fb_lang'] ) ? 
					SucomUtil::get_locale( $post_id ) : $this->p->options['fb_lang'];

				$lang = apply_filters( $this->p->cf['lca'].'_lang', 
					$lang, SucomUtil::get_pub_lang( 'facebook' ), $post_id );

				$og['og:locale'] = $lang;
			}

			if ( ! isset( $og['og:site_name'] ) )
				$og['og:site_name'] = $this->get_site_name( $post_id );

			if ( ! isset( $og['og:title'] ) )
				$og['og:title'] = $this->p->webpage->get_title( $this->p->options['og_title_len'], '...', $use_post );

			if ( ! isset( $og['og:description'] ) )
				$og['og:description'] = $this->p->webpage->get_description( $this->p->options['og_desc_len'], '...', $use_post );

			// if the page is an article, then define the other article meta tags
			if ( isset( $og['og:type'] ) && $og['og:type'] == 'article' ) {

				if ( ! isset( $og['article:author'] ) ) {
					if ( is_singular() || $use_post !== false ) {
						if ( ! empty( $obj->post_author ) )
							$og['article:author'] = $this->p->mods['util']['user']->get_article_author( $obj->post_author );
						elseif ( ! empty( $this->p->options['og_def_author_id'] ) )
							$og['article:author'] = $this->p->mods['util']['user']->get_article_author( $this->p->options['og_def_author_id'] );
					}
				}

				if ( ! isset( $og['article:publisher'] ) )
					$og['article:publisher'] = $this->p->options['fb_publisher_url'];

				if ( ! isset( $og['article:tag'] ) )
					$og['article:tag'] = $this->p->webpage->get_tags( $post_id );

				if ( ! isset( $og['article:section'] ) )
					$og['article:section'] = $this->p->webpage->get_section( $post_id );

				if ( ! isset( $og['article:published_time'] ) )
					$og['article:published_time'] = trim( get_the_date('c') );

				if ( ! isset( $og['article:modified_time'] ) )
					$og['article:modified_time'] = trim( get_the_modified_date('c') );
			}

			// get all videos
			// call before getting all images to find / use preview images
			if ( ! isset( $og['og:video'] ) ) {
				if ( empty( $og_max['og_vid_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'videos disabled: maximum videos = 0' );
				} else {
					$og['og:video'] = $this->get_all_videos( $og_max['og_vid_max'], $post_id, true, 'og' );
					if ( is_array( $og['og:video'] ) ) {
						foreach ( $og['og:video'] as $val )
							if ( ! empty( $val['og:image'] ) )
								$video_previews++;
						if ( $video_previews > 0 ) {
							$og_max['og_img_max'] -= $video_previews;
							if ( $this->p->debug->enabled )
								$this->p->debug->log( $video_previews.
									' video preview images found (og_img_max adjusted to '.$og_max['og_img_max'].')' );
						}
					}
				} 
			}

			// get all images
			if ( ! isset( $og['og:image'] ) ) {
				if ( empty( $og_max['og_img_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'images disabled: maximum images = 0' );
				} else {
					if ( is_admin() ) {
						$img_sizes = array (
							'rp' => $this->p->cf['lca'].'-richpin',
							'og' => $this->p->cf['lca'].'-opengraph',	// must be last for meta tags preview
						);
					} else {
						switch ( SucomUtil::crawler_name() ) {
							case 'pinterest':
								$img_sizes = array ( 'rp' => $this->p->cf['lca'].'-richpin' );
								break;
							default:
								$img_sizes = array ( 'og' => $this->p->cf['lca'].'-opengraph' );
								break;
						}
					}
					foreach ( $img_sizes as $meta_pre => $size_name ) {
						// only check for dupes on last image size
						$check_dupes = ( is_admin() && $meta_pre !== 'og' ) ? false : true;

						$og['og:image'] = $this->get_all_images( $og_max['og_img_max'], 
							$size_name, $post_id, $check_dupes, $meta_pre );

						// if there's no image, and no video preview image, then add the default image for non-index webpages
						if ( empty( $og['og:image'] ) && $video_previews === 0 &&
							( is_singular() || $use_post !== false ) )
								$og['og:image'] = $this->p->media->get_default_image( $og_max['og_img_max'],
									$size_name, $check_dupes );
					}
				} 
			}

			// only a few opengraph meta tags are allowed to be empty
			foreach ( $og as $key => $val ) {
				switch ( $key ) {
					case 'og:locale':
					case 'og:site_name':
					case 'og:description':
						break;
					default:
						if ( $val === '' || ( is_array( $val ) && empty( $val ) ) )
							unset( $og[$key] );
						break;
				}
			}

			// twitter cards are hooked into this filter to use existing open graph values
			return apply_filters( $this->p->cf['lca'].'_og', $og, $use_post, $obj );
		}

		public function get_all_videos( $num = 0, $post_id, $check_dupes = true, $meta_pre = 'og', $force_img = false ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->args( array( 
					'num' => $num,
					'post_id' => $post_id,
					'check_dupes' => $check_dupes,
					'meta_pre' => $meta_pre,
					'force_img' => $force_img,
				) );
			$og_ret = array();
			$opt_prev_img = $this->p->options['og_vid_prev_img'];	// default value

			if ( $this->p->util->force_default_video() ) {
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, $this->p->media->get_default_video( $num_remains, $check_dupes ) );
				return $og_ret;	// stop here and return the video array
			}

			// get_og_video() is only partially implemented in the Free version (video modules are not available)
			if ( $this->p->check->aop() ) {
				$num_remains = $this->p->media->num_remains( $og_ret, $num );

				if ( ! empty( $post_id ) ) {
					$id = $post_id;
					$mod =& $this->p->mods['util']['post'];
				} elseif ( SucomUtil::is_term_page() ) {
					$id = $this->p->util->get_term_object( 'id' );
					$mod =& $this->p->mods['util']['taxonomy'];
				} elseif ( SucomUtil::is_author_page() ) {
					$id = $this->p->util->get_author_object( 'id' );
					$mod =& $this->p->mods['util']['user'];
				} else $mod = false;

				if ( $mod !== false ) {
					$opt_prev_img = $mod->get_options( $id, 'og_vid_prev_img' ); 
					$og_ret = array_merge( $og_ret, $mod->get_og_video( $num_remains, $id, $check_dupes, $meta_pre ) );
				}
			}

			// if we haven't reached the limit of videos yet, keep going
			if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, $this->p->media->get_content_videos( $num_remains,
					$post_id, $check_dupes ) );
			}

			$this->p->util->slice_max( $og_ret, $num );

			// remove preview images if the 'og_vid_prev_img' option is disabled (unless forced by method argument)
			if ( empty( $opt_prev_img ) ) {
				if ( $force_img === false ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'og_vid_prev_img is 0 - removing image preview meta tags' );
					foreach ( $og_ret as $num => $og_video ) {
						unset ( 
							$og_ret[$num]['og:image'],
							$og_ret[$num]['og:image:secure_url'],
							$og_ret[$num]['og:image:width'],
							$og_ret[$num]['og:image:height']
						);
					}
				} 
			}

			if ( ! empty( $this->p->options['og_vid_html_type'] ) ) {
				$og_extend = array();
				foreach ( $og_ret as $num => $og_video ) {
					$og_extend[] = $og_video;
					if ( ! empty( $og_video['og:video:embed_url'] ) ) {
						$og_embed['og:video:url'] = $og_video['og:video:embed_url'];
						$og_embed['og:video:type'] = 'text/html';		// define the type after the url
						foreach ( array( 'og:video:width', 'og:video:height' ) as $key ) 
							if ( isset( $og_video[$key] ) )
								$og_embed[$key] = $og_video[$key];
						$og_extend[] = $og_embed;
					}
				}
				return $og_extend;
			} else return $og_ret;
		}

		public function get_all_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $meta_pre = 'og' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->args( array(
					'num' => $num,
					'size_name' => $size_name,
					'post_id' => $post_id,
					'check_dupes' => $check_dupes,
					'meta_pre' => $meta_pre,
				) );
			$og_ret = array();
			$force_regen = false;

			// check for an attachment page
			// is_attachment() only works on the front-end, so check the post_type as well
			if ( ! empty( $post_id ) ) {
				if ( ( is_attachment( $post_id ) || get_post_type( $post_id ) === 'attachment' ) &&
					wp_attachment_is_image( $post_id ) ) {

					$og_image = array();
					$num_remains = $this->p->media->num_remains( $og_ret, $num );
					$og_image = $this->p->media->get_attachment_image( $num_remains, 
						$size_name, $post_id, $check_dupes );
	
					// if an attachment is not an image, then use the default image instead
					if ( empty( $og_image ) ) {
						$num_remains = $this->p->media->num_remains( $og_ret, $num );
						$og_ret = array_merge( $og_ret, $this->p->media->get_default_image( $num_remains, $size_name ) );
					} else $og_ret = array_merge( $og_ret, $og_image );
	
					return $og_ret;
				}
			}

			if ( $this->p->util->force_default_image() ) {
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, $this->p->media->get_default_image( $num_remains, $size_name ) );
				return $og_ret;	// stop here and return the image array
			}

			$num_remains = $this->p->media->num_remains( $og_ret, $num );
			if ( SucomUtil::is_term_page() ) {
				$term_id = $this->p->util->get_term_object( 'id' );
				$og_ret = array_merge( $og_ret, $this->p->mods['util']['taxonomy']->get_og_image( $num_remains, 
					$size_name, $term_id, $check_dupes, $force_regen, $meta_pre ) );

				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
					$num_remains = $this->p->media->num_remains( $og_ret, $num );
					$og_ret = array_merge( $og_ret, $this->p->mods['util']['taxonomy']->get_term_images( $num_remains, 
						$size_name, $term_id, $check_dupes, $force_regen, $meta_pre ) );
				}
			} elseif ( SucomUtil::is_author_page() ) {
				$author_id = $this->p->util->get_author_object( 'id' );
				$og_ret = array_merge( $og_ret, $this->p->mods['util']['user']->get_og_image( $num_remains, 
					$size_name, $author_id, $check_dupes, $force_regen, $meta_pre ) );

			} else {
				// check for custom meta, featured, or attached image(s)
				// allow for empty post_id in order to execute featured/attached image filters for modules
				$og_ret = array_merge( $og_ret, $this->p->media->get_post_images( $num_remains, 
					$size_name, $post_id, $check_dupes, $meta_pre ) );

				// check for ngg shortcodes and query vars
				if ( $this->p->is_avail['media']['ngg'] === true && 
					! empty( $this->p->mods['media']['ngg'] ) &&
					! $this->p->util->is_maxed( $og_ret, $num ) ) {
	
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'checking for ngg shortcodes and query vars' );
	
					// ngg pre-v2 used query arguments
					$ngg_query_og_ret = array();
					$num_remains = $this->p->media->num_remains( $og_ret, $num );
					if ( version_compare( $this->p->mods['media']['ngg']->ngg_version, '2.0.0', '<' ) )
						$ngg_query_og_ret = $this->p->mods['media']['ngg']->get_query_images( $num_remains, 
							$size_name, $check_dupes );
	
					// if we found images in the query, skip content shortcodes
					if ( count( $ngg_query_og_ret ) > 0 ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( count( $ngg_query_og_ret ).
								' image(s) returned - skipping additional shortcode images' );
						$og_ret = array_merge( $og_ret, $ngg_query_og_ret );
	
					// if no query images were found, continue with ngg shortcodes in content
					} elseif ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
						$num_remains = $this->p->media->num_remains( $og_ret, $num );
						$og_ret = array_merge( $og_ret, 
							$this->p->mods['media']['ngg']->get_shortcode_images( $num_remains, 
								$size_name, $check_dupes ) );
					}
				} // end of check for ngg shortcodes and query vars

				// if we haven't reached the limit of images yet, keep going and check the content text
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
					$num_remains = $this->p->media->num_remains( $og_ret, $num );
					$og_ret = array_merge( $og_ret, $this->p->media->get_content_images( $num_remains, 
						$size_name, $post_id, $check_dupes ) );
				}
			}

			$this->p->util->slice_max( $og_ret, $num );

			return $og_ret;
		}

		public function get_site_name( $get = 'current' ) {
			// provide options array to allow fallback if locale option does not exist
			$key = SucomUtil::get_locale_key( 'og_site_name', $this->p->options, $get );
			if ( ! empty( $this->p->options[$key] ) )
				return $this->p->options[$key];
			else return get_bloginfo( 'name', 'display' );
		}

		public function get_the_media_urls( $size_name = 'thumbnail', $id, $meta_pre = 'og',
			$items = array( 'image', 'video' ) ) {

			if ( empty( $id ) )
				return array();

			$ret = array();

			foreach ( $items as $item ) {
				switch ( $item ) {
					case 'pid':
					case 'image':
						if ( ! isset( $og_image ) )
							$og_image = $this->get_all_images( 1, $size_name, $id, false, $meta_pre );
						break;
					case 'video':
					case 'preview':
						if ( ! isset( $og_video ) )
							$og_video = $this->get_all_videos( 1, $id, false, $meta_pre, true );	// prev_img = true
						break;
				}
				switch ( $item ) {
					case 'pid':
						$ret[] = $this->get_og_media_url( 'pid', $og_image );
						break;
					case 'image':
						$ret[] = $this->get_og_media_url( 'image', $og_image );
						break;
					case 'video':
						$ret[] = $this->get_og_media_url( 'video', $og_video );
						break;
					case 'preview':
						$ret[] = $this->get_og_media_url( 'image', $og_video );
						break;
					default:
						$ret[] = '';
						break;
				}
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->log( $ret );

			return $ret;
		}

		public function get_og_media_url( $name, $og, $tag_pre = 'og' ) {
			if ( ! empty( $og ) && is_array( $og ) ) {
				$media = reset( $og );
				switch ( $name ) {
					case 'pid':
						$search = array(
							$tag_pre.':image:id',
						);
						break;
					default:
						$search = array(
							$tag_pre.':'.$name.':secure_url',
							$tag_pre.':'.$name.':url',
							$tag_pre.':'.$name,
						);
						break;
				}
				foreach ( $search as $key )
					if ( isset( $media[$key] ) &&
						! empty( $media[$key] ) &&
							$media[$key] !== -1 )
								return $media[$key];
			}
			return '';
		}
	}
}

?>
