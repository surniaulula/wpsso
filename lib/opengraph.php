<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
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

			if ( ! empty( $this->p->options['plugin_html_attr_filter_name'] ) &&
				$this->p->options['plugin_html_attr_filter_name'] !== 'none' ) {

					$prio = empty( $this->p->options['plugin_html_attr_filter_prio'] ) ? 
						100 : $this->p->options['plugin_html_attr_filter_prio'];

					// add open graph namespace attributes to the <html> tag
					add_filter( $this->p->options['plugin_html_attr_filter_name'], 
						array( &$this, 'add_html_attributes' ), $prio, 1 );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'add_html_attributes skipped: plugin_html_attr_filter_name option is empty' );
		}

		public function filter_plugin_image_sizes( $sizes ) {

			$sizes['og_img'] = array( 		// options prefix
				'name' => 'opengraph',		// wpsso-opengraph
				'label' => _x( 'Facebook / Open Graph',
					'image size label', 'wpsso' ),
			);

			if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) ) {
				$sizes['rp_img'] = array(	// options prefix
					'name' => 'richpin',	// wpsso-richpin
					'label' => _x( 'Pinterest Rich Pin',
						'image size label', 'wpsso' ),
				);
			}

			return $sizes;
		}

		public function add_html_attributes( $html_attr ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			
			$prefix_ns = apply_filters( $this->p->cf['lca'].'_og_prefix_ns', array(
				'og' => 'http://ogp.me/ns#',
				'fb' => 'http://ogp.me/ns/fb#',
			) );
	
			// find and extract an existing prefix attribute value
			if ( strpos( $html_attr, ' prefix=' ) &&
				preg_match( '/^(.*) prefix=["\']([^"\']*)["\'](.*)$/', $html_attr, $match ) ) {
					$html_attr = $match[1].$match[3];
					$prefix_value = ' '.$match[2];
			} else $prefix_value = '';

			foreach ( $prefix_ns as $ns => $url )
				if ( strpos( $prefix_value, ' '.$ns.': '.$url ) === false )
					$prefix_value .= ' '.$ns.': '.$url;

			$html_attr .= ' prefix="'.trim( $prefix_value ).'"';

			return trim( $html_attr );
		}

		public function get_array( $use_post = false, $post_obj = false, &$og = array(), $crawler_name = 'unknown' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! is_object( $post_obj ) )
				$post_obj = $this->p->util->get_post_object( $use_post );
			$post_id = empty( $post_obj->ID ) || empty( $post_obj->post_type ) ||
				! SucomUtil::is_post_page( $use_post ) ? 0 : $post_obj->ID;
			$lca = $this->p->cf['lca'];
			$check_dupes = true;

			// counter for video previews found
			$video_previews = 0;

			// a post_id of 0 returns the default plugin settings 
			$max = $this->p->util->get_max_nums( $post_id, 'post' );
			$og = apply_filters( $lca.'_og_seed', $og, $use_post, $post_obj );

			if ( ! empty( $og ) && 
				$this->p->debug->enabled ) {
					$this->p->debug->log( $lca.'_og_seed filter returned:' );
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
				if ( SucomUtil::is_post_page( $use_post ) ) {
					if ( ! empty( $post_obj->post_type ) && 
						isset( $this->p->cf['head']['og_type_ns'][$post_obj->post_type] ) )
							$og['og:type'] = $post_obj->post_type;
					else $og['og:type'] = 'article';

				// check for default author info on indexes and searches
				} elseif ( $def_user_id = $this->p->util->force_default_author( $use_post, 'og' ) ) {

					$og['og:type'] = 'article';

					// meta tag not defined or value is null
					if ( ! isset( $og['article:author'] ) )
						$og['article:author'] = $this->p->m['util']['user']->get_og_profile_urls( $def_user_id );

				// default for everything else is 'website'
				} else $og['og:type'] = 'website';

				$og['og:type'] = apply_filters( $lca.'_og_type', $og['og:type'], $use_post );

				// pre-define basic open graph meta tags for this type
				if ( isset( $this->p->cf['head']['og_type_mt'][$og['og:type']] ) ) {
					foreach( $this->p->cf['head']['og_type_mt'][$og['og:type']] as $mt_name ) {
						if ( ! isset( $og[$mt_name] ) ) {
							$og[$mt_name] = null;
							if ( $this->p->debug->enabled )
								$this->p->debug->log( $og['og:type'].' pre-defined mt: '.$mt_name );
						}
					}
				}
			}

			if ( ! isset( $og['og:locale'] ) ) {
				// get the current or configured language for og:locale
				$lang = empty( $this->p->options['fb_lang'] ) ? 
					SucomUtil::get_locale( $post_id ) :
					$this->p->options['fb_lang'];
				$og['og:locale'] = apply_filters( $lca.'_pub_lang', $lang, 'facebook', $post_id );
			}

			if ( ! isset( $og['og:site_name'] ) )
				$og['og:site_name'] = $this->get_site_name( $post_id );

			if ( ! isset( $og['og:title'] ) )
				$og['og:title'] = $this->p->webpage->get_title( $this->p->options['og_title_len'], '...', $use_post );

			if ( ! isset( $og['og:description'] ) )
				$og['og:description'] = $this->p->webpage->get_description( $this->p->options['og_desc_len'], '...', $use_post );

			// if the page is an article, then define the other article meta tags
			if ( isset( $og['og:type'] ) && 
				$og['og:type'] == 'article' ) {

				// meta tag not defined or value is null
				if ( ! isset( $og['article:author'] ) ) {

					if ( SucomUtil::is_post_page( $use_post ) ) {

						if ( ! empty( $post_obj->post_author ) )
							$og['article:author'] = $this->p->m['util']['user']->get_og_profile_urls( $post_obj->post_author );

						elseif ( $def_user_id = $this->p->util->get_default_author_id( 'og' ) )
							$og['article:author'] = $this->p->m['util']['user']->get_og_profile_urls( $def_user_id );
					}
				}

				// meta tag not defined or value is null
				if ( ! isset( $og['article:publisher'] ) ) {
					$key_locale = SucomUtil::get_key_locale( 'fb_publisher_url', $this->p->options, $post_id );
					$og['article:publisher'] = $this->p->options[$key_locale];
				}

				// meta tag not defined or value is null
				if ( ! isset( $og['article:tag'] ) )
					$og['article:tag'] = $this->p->webpage->get_tags( $post_id );

				// meta tag not defined or value is null
				if ( ! isset( $og['article:section'] ) )
					$og['article:section'] = $this->p->webpage->get_section( $post_id );

				// meta tag not defined or value is null
				if ( ! isset( $og['article:published_time'] ) )
					$og['article:published_time'] = trim( get_post_time( 'c', null, $post_id ) );

				// meta tag not defined or value is null
				if ( ! isset( $og['article:modified_time'] ) )
					$og['article:modified_time'] = trim( get_post_modified_time( 'c', null, $post_id ) );
			}

			// get all videos
			// call before getting all images to find / use preview images
			if ( ! isset( $og['og:video'] ) ) {
				if ( empty( $max['og_vid_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'videos disabled: maximum videos = 0' );
				} else {
					$og['og:video'] = $this->get_all_videos( $max['og_vid_max'], $post_id, 'post', $check_dupes, 'og' );

					if ( ! empty( $og['og:video'] ) && is_array( $og['og:video'] ) ) {
						foreach ( $og['og:video'] as $video_num => $video_arr ) {
							if ( ! empty( $video_arr['og:image'] ) ) {
								$og['og:video'][$video_num]['og:video:has_image'] = true;
								$video_previews++;
							}
						}
						if ( $video_previews > 0 ) {
							$max['og_img_max'] -= $video_previews;
							if ( $this->p->debug->enabled )
								$this->p->debug->log( $video_previews.
									' video preview images found (og_img_max adjusted to '.$max['og_img_max'].')' );
						}
					}
				} 
			}

			// get all images
			if ( ! isset( $og['og:image'] ) ) {
				if ( empty( $max['og_img_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'images disabled: maximum images = 0' );
				} else {
					$img_sizes = array( 'og' => $lca.'-opengraph' );

					if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) ) {
						if ( is_admin() ) {					// process both image sizes
							$img_sizes = array(
								'rp' => $lca.'-richpin',
								'og' => $lca.'-opengraph',
							);
						} elseif ( $crawler_name === 'pinterest' )
							$img_sizes = array( 'rp' => $lca.'-richpin' );	// use only pinterest image size
					}

					foreach ( $img_sizes as $md_pre => $size_name ) {

						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'getting all images for '.$md_pre.' ('.$size_name.')' );

						// the size_name is used as a context for duplicate checks
						$og[$md_pre.':image'] = $this->get_all_images( $max['og_img_max'], 
							$size_name, $post_id, $check_dupes, $md_pre );

						// if there's no image, and no video preview image, 
						// then add the default image for singular (aka post) webpages
						if ( $video_previews === 0 && 
							empty( $og[$md_pre.':image'] ) && 
								SucomUtil::is_post_page( $use_post ) ) {

							$og[$md_pre.':image'] = $this->p->media->get_default_image( $max['og_img_max'],
								$size_name, $check_dupes );
						}

						switch ( $md_pre ) {
							case 'rp':
								if ( is_admin() ) {
									// show both og and pinterest meta tags in the head tags tab
									// by renaming each og:image to pinterest:image 
									foreach ( $og[$md_pre.':image'] as $num => $arr )
										$og[$md_pre.':image'][$num] = SucomUtil::preg_grep_keys( '/^og:/',
											$arr, false, 'pinterest:' );
								} else {
									// rename the rp:image array to og:image
									$og['og:image'] = $og[$md_pre.':image'];
									unset ( $og[$md_pre.':image'] );
								}
								break;
						}
					}
				} 
			}

			return apply_filters( $lca.'_og', $og, $use_post, $post_obj );
		}

		public function get_all_videos( $num = 0, $id, $mod_name = 'post', $check_dupes = true, $md_pre = 'og', $force_prev_img = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->args( array( 
					'num' => $num,
					'id' => $id,
					'mod_name' => $mod_name,
					'check_dupes' => $check_dupes,
					'md_pre' => $md_pre,
					'force_prev_img' => $force_prev_img,
				) );
			}

			$og_ret = array();
			$lca = $this->p->cf['lca'];
			$aop = $this->p->check->aop( $lca, true, $this->p->is_avail['aop'] );
			$use_prev_img = $this->p->options['og_vid_prev_img'];	// default option value
			$num_diff = SucomUtil::count_diff( $og_ret, $num );
			$mod_obj = null;

			if ( $aop ) {
				list( $id, $mod_name, $mod_obj ) = $this->p->util->get_object_id_mod( false, $id, $mod_name );
				if ( ! empty( $mod_obj ) ) {
					if ( ( $mod_prev_img = $mod_obj->get_options( $id, 'og_vid_prev_img' ) ) !== null ) {
						$use_prev_img = $mod_prev_img;	// use module option value
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'setting use_prev_img to '.
								$use_prev_img.' from meta data' );
					}
					$og_ret = array_merge( $og_ret, $mod_obj->get_og_video( $num_diff, $id, $check_dupes, $md_pre ) );
				}
			}

			if ( count( $og_ret ) < 1 && $this->p->util->force_default_video() ) {
				$og_ret = array_merge( $og_ret, $this->p->media->get_default_video( $num_diff, $check_dupes ) );

			} else {
				$num_diff = SucomUtil::count_diff( $og_ret, $num );

				if ( $mod_name === 'post' && 
					! $this->p->util->is_maxed( $og_ret, $num ) )
						$og_ret = array_merge( $og_ret, 
							$this->p->media->get_content_videos( $num_diff, 
								$id, $check_dupes ) );

				$this->p->util->slice_max( $og_ret, $num );

				if ( empty( $use_prev_img ) && 
					$force_prev_img === false ) {

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'use_prev_img and force_prev_img are false: removing video preview images' );
					foreach ( $og_ret as $num => $og_video ) {
						$og_ret[$num]['og:video:has_image'] = false;
						foreach( SucomUtil::preg_grep_keys( '/^og:image(:.*)?$/', $og_video ) as $k => $v )
							unset ( $og_ret[$num][$k] );
					}
				}
			}

			// if $md_pre is 'none' (special index keyword), don't load custom video title / description
			if ( $aop && ! empty( $mod_obj ) && $md_pre !== 'none' ) {
				foreach ( array(
					'og_vid_title' => 'og:video:title',
					'og_vid_desc' => 'og:video:description',
				) as $key => $tag ) {
					$value = $mod_obj->get_options( $id, $key );
					if ( ! empty( $value ) ) {
						foreach ( $og_ret as $num => $og_video ) {
							$og_ret[$num][$tag] = $value;
							break;	// only do the first video
						}
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

		public function get_all_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->args( array(
					'num' => $num,
					'size_name' => $size_name,
					'post_id' => $post_id,
					'check_dupes' => $check_dupes,
					'md_pre' => $md_pre,
				) );

			$og_ret = array();
			$force_regen = false;
			$num_diff = SucomUtil::count_diff( $og_ret, $num );

			// if requesting images for a specific post_id
			if ( SucomUtil::is_post_page( $post_id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'is_post_page() = true' );

				// is_attachment() only works on the front-end, so check the post_type as well
				if ( ( is_attachment( $post_id ) || get_post_type( $post_id ) === 'attachment' ) && 
					wp_attachment_is_image( $post_id ) ) {

					$og_image = $this->p->media->get_attachment_image( $num_diff, 
						$size_name, $post_id, $check_dupes );

					// exiting early
					if ( empty( $og_image ) )
						return array_merge( $og_ret, $this->p->media->get_default_image( $num_diff, 
							$size_name, $check_dupes, $force_regen ) );
					else return array_merge( $og_ret, $og_image );
				}

				// check for custom meta, featured, or attached image(s)
				// allow for empty post_id in order to execute featured / attached image filters for modules
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) )
					$og_ret = array_merge( $og_ret, $this->p->media->get_post_images( $num_diff, 
						$size_name, $post_id, $check_dupes, $md_pre ) );

				// check for ngg shortcodes and query vars
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) &&
					$this->p->is_avail['media']['ngg'] && ! empty( $this->p->m['media']['ngg'] ) ) {
	
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'checking for ngg shortcodes and query vars' );
	
					// ngg pre-v2 used query arguments
					$ngg_query_og_ret = array();
					$num_diff = SucomUtil::count_diff( $og_ret, $num );
					if ( version_compare( $this->p->m['media']['ngg']->ngg_version, '2.0.0', '<' ) )
						$ngg_query_og_ret = $this->p->m['media']['ngg']->get_query_images( $num_diff, 
							$size_name, $post_id, $check_dupes );
	
					// if we found images in the query, skip content shortcodes
					if ( count( $ngg_query_og_ret ) > 0 ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( count( $ngg_query_og_ret ).
								' image(s) returned - skipping additional shortcode images' );
						$og_ret = array_merge( $og_ret, $ngg_query_og_ret );
	
					// if no query images were found, continue with ngg shortcodes in content
					} elseif ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
						$num_diff = SucomUtil::count_diff( $og_ret, $num );
						$og_ret = array_merge( $og_ret, 
							$this->p->m['media']['ngg']->get_shortcode_images( $num_diff, 
								$size_name, $post_id, $check_dupes ) );
					}
				} // end of check for ngg shortcodes and query vars
	
				// if we haven't reached the limit of images yet, keep going and check the content text
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
					$num_diff = SucomUtil::count_diff( $og_ret, $num );
					$og_ret = array_merge( $og_ret, $this->p->media->get_content_images( $num_diff, 
						$size_name, $post_id, $check_dupes ) );
				}

			} else {
				// check for priority (meta) media before using the default image
				if ( SucomUtil::is_term_page() ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'is_term_page() = true' );

					$term_id = $this->p->util->get_term_object( 'id' );

					// get_og_images() also provides filter hooks for additional image ids and urls
					$og_ret = array_merge( $og_ret, $this->p->m['util']['taxonomy']->get_og_image( $num_diff, 
						$size_name, $term_id, $check_dupes, $force_regen, $md_pre ) );
	
				} elseif ( SucomUtil::is_user_page() ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'is_user_page() = true' );

					$user_id = $this->p->util->get_user_object( 'id' );

					// get_og_images() also provides filter hooks for additional image ids and urls
					$og_ret = array_merge( $og_ret, $this->p->m['util']['user']->get_og_image( $num_diff, 
						$size_name, $user_id, $check_dupes, $force_regen, $md_pre ) );
				}
	
				if ( count( $og_ret ) < 1 && $this->p->util->force_default_image( $post_id, 'og' ) ) {
					return array_merge( $og_ret, $this->p->media->get_default_image( $num_diff, 
						$size_name, $check_dupes, $force_regen ) );
				}
	
				$num_diff = SucomUtil::count_diff( $og_ret, $num );
			}

			$this->p->util->slice_max( $og_ret, $num );

			return $og_ret;
		}

		public function get_site_name( $get = 'current' ) {
			// provide options array to allow fallback if locale option does not exist
			$key_locale = SucomUtil::get_key_locale( 'og_site_name', $this->p->options, $get );

			if ( ! empty( $this->p->options[$key_locale] ) )
				return $this->p->options[$key_locale];
			else return get_bloginfo( 'name', 'display' );
		}

		public function get_site_desc( $get = 'current' ) {
			// provide options array to allow fallback if locale option does not exist
			$key_locale = SucomUtil::get_key_locale( 'og_site_description', $this->p->options, $get );

			if ( ! empty( $this->p->options[$key_locale] ) )
				return $this->p->options[$key_locale];
			else return get_bloginfo( 'description', 'display' );
		}

		// deprecated on 2016/03/07
		public function get_the_media_urls( $size_name, $post_id, $md_pre = 'og', $items = array( 'image', 'video' ) ) {
			return array_values( $this->get_the_media_info( $size_name, $post_id, $md_pre, $items ) );
		}

		// used by some sharing buttons (buffer, pinterest, tumblr)
		// creates image and video information for a specific $size_name ('wpsso-buffer-button' for example)
		// the returned array can include a varying number of elements, depending on the $items value
		public function get_the_media_info( $size_name, $post_id, $md_pre = 'og', $items = array() ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$ret = array();
			$og_image = null;
			$og_video = null;
			$image_url = null;
			$preview_url = null;

			// get the media first
			foreach ( $items as $item ) {
				switch ( $item ) {
					case 'pid':
					case ( preg_match( '/^(image|img)/', $item ) ? true : false ):
						if ( $og_image === null )	// only get images once
							$og_image = $this->get_all_images( 1, $size_name, $post_id, false, $md_pre );
						break;
					case ( preg_match( '/^(prev|vid)/', $item ) ? true : false ):
						if ( $og_video === null )	// only get videos once
							$og_video = $this->get_all_videos( 1, $post_id, 'post', false, $md_pre, true );
						break;
				}
			}

			foreach ( $items as $item ) {
				switch ( $item ) {
					case 'pid':
						$ret[$item] = self::get_first_media_info( 'og:image:id', $og_image );
						break;
					case 'image':
					case 'img_url':
						$image_url = self::get_first_media_info( 'og:image', $og_image );
						// get the preview image, if there is one
						if ( empty( $image_url ) && $og_video !== null )
							$image_url = $preview_url = self::get_first_media_info( 'og:image', $og_video );
						$ret[$item] = $image_url;
						break;
					case 'video':
					case 'vid_url':
						$ret[$item] = self::get_first_media_info( 'og:video', $og_video );
						break;
					case 'vid_title':
						$ret[$item] = self::get_first_media_info( 'og:video:title', $og_video );
						break;
					case 'vid_desc':
						$ret[$item] = self::get_first_media_info( 'og:video:description', $og_video );
						break;
					case 'prev_url':
					case 'preview':
						if ( $preview_url !== null )
							$preview_url = self::get_first_media_info( 'og:image', $og_video );
						$ret[$item] = $preview_url;
						break;
					default:
						$ret[$item] = '';
						break;
				}
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->log( $ret );

			return $ret;
		}

		// deprecated on 2016/03/07
		public static function get_first_media_url( $prefix, $og ) {
			return self::get_first_media_info( $prefix, $og );
		}

		public static function get_first_media_info( $prefix, $og ) {
			if ( empty( $og ) || 
				! is_array( $og ) )
					return '';

			switch ( $prefix ) {
				// search all three values, in order of preference
				case ( preg_match( '/^og:(image|video)(:secure_url|:url)?$/', $prefix ) ? true : false ):
					$search = array(
						$prefix.':secure_url',	// og:image:secure_url
						$prefix.':url',		// og:image:url
						$prefix,		// og:image
					);
					break;
				default:
					$search = array( $prefix );
					break;
			}

			$og_media = reset( $og );	// only search the first media array

			foreach ( $search as $key ) {
				if ( ! empty( $og_media[$key] ) && 
					$og_media[$key] !== -1 ) {
						return $og_media[$key];
				}
			}

			return '';
		}
	}
}

?>
