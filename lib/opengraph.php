<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoOpenGraph' ) ) {

	class WpssoOpenGraph {

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes' => 1,
			) );

			foreach ( array( 'plugin_html_attr_filter', 'plugin_head_attr_filter' ) as $opt_prefix ) {
				if ( ! empty( $this->p->options[$opt_prefix.'_name'] ) &&
					$this->p->options[$opt_prefix.'_name'] !== 'none' ) {

					$ogpns_filter_name = $this->p->options[$opt_prefix.'_name'];
					$ogpns_filter_prio = isset( $this->p->options[$opt_prefix.'_prio'] ) ?
						(int) $this->p->options[$opt_prefix.'_prio'] : 100;

					add_filter( $ogpns_filter_name, array( &$this, 'add_ogpns_attributes' ), $ogpns_filter_prio, 1 );

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'added add_ogpns_attributes filter for '.$ogpns_filter_name );

					break;	// stop here

				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'skipping add_ogpns_attributes for '.$opt_prefix.' - name value is empty or disabled' );
			}
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

		public function add_ogpns_attributes( $html_attr ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array (
					'html_attr' => $html_attr,
				) );
			}

			$html_attr = ' '.$html_attr;	// prepare the string for testing
			$prefix_ns = apply_filters( $this->p->cf['lca'].'_og_prefix_ns', array(
				'og' => 'http://ogp.me/ns#',
				'fb' => 'http://ogp.me/ns/fb#',
				'article' => 'http://ogp.me/ns/article#',
			) );

			if ( $this->p->is_avail['amp_endpoint'] && is_amp_endpoint() ) {
				// nothing to do				

			} else {
				// find and extract an existing prefix attribute value (if any)
				if ( strpos( $html_attr, ' prefix=' ) &&
					preg_match( '/^(.*) prefix=["\']([^"\']*)["\'](.*)$/', $html_attr, $match ) ) {
						$html_attr = $match[1].$match[3];	// remove the prefix
						$prefix_value = ' '.$match[2];
				} else $prefix_value = '';
	
				foreach ( $prefix_ns as $ns => $url )
					if ( strpos( $prefix_value, ' '.$ns.': '.$url ) === false )
						$prefix_value .= ' '.$ns.': '.$url;
	
				$html_attr .= ' prefix="'.trim( $prefix_value ).'"';
			}

			return trim( $html_attr );
		}

		public function get_array( array &$mod, array &$mt_og, $crawler_name = false ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( $crawler_name === false )
				$crawler_name = SucomUtil::crawler_name();

			$lca = $this->p->cf['lca'];
			$max = $this->p->util->get_max_nums( $mod );
			$aop = $this->p->check->aop( $lca, true, $this->p->is_avail['aop'] );
			$post_id = $mod['is_post'] ? $mod['id'] : false;
			$check_dupes = true;
			$prev_count = 0;

			$mt_og = apply_filters( $lca.'_og_seed', $mt_og, $mod['use_post'], $mod );

			if ( ! empty( $mt_og ) && 
				$this->p->debug->enabled ) {
					$this->p->debug->log( $lca.'_og_seed filter returned:' );
					$this->p->debug->log( $mt_og );
			}

			if ( ! isset( $mt_og['fb:admins'] ) && ! empty( $this->p->options['fb_admins'] ) )
				foreach ( explode( ',', $this->p->options['fb_admins'] ) as $fb_admin )
					$mt_og['fb:admins'][] = trim( $fb_admin );

			if ( ! isset( $mt_og['fb:app_id'] ) )
				$mt_og['fb:app_id'] = $this->p->options['fb_app_id'];

			if ( ! isset( $mt_og['og:url'] ) )
				$mt_og['og:url'] = $this->p->util->get_sharing_url( $mod );

			// define the type after the url
			if ( ! isset( $mt_og['og:type'] ) ) {

				// singular posts / pages are articles by default
				// check the post_type for a match with a known open graph type
				if ( $mod['is_post'] ) {
					if ( ! empty( $mod['post_type'] ) && 
						isset( $this->p->cf['head']['og_type_ns'][$mod['post_type']] ) )
							$mt_og['og:type'] = $mod['post_type'];
					else $mt_og['og:type'] = 'article';

				// default for everything else is 'website'
				} else $mt_og['og:type'] = 'website';

				$mt_og['og:type'] = apply_filters( $lca.'_og_type', $mt_og['og:type'], $mod['use_post'] );

				// pre-define basic open graph meta tags for this type
				if ( isset( $this->p->cf['head']['og_type_mt'][$mt_og['og:type']] ) ) {
					foreach( $this->p->cf['head']['og_type_mt'][$mt_og['og:type']] as $mt_name ) {
						if ( ! isset( $mt_og[$mt_name] ) ) {
							$mt_og[$mt_name] = null;
							if ( $this->p->debug->enabled )
								$this->p->debug->log( $mt_og['og:type'].' pre-defined mt: '.$mt_name );
						}
					}
				}
			}

			if ( ! isset( $mt_og['og:locale'] ) )
				$mt_og['og:locale'] = SucomUtil::get_fb_locale( $this->p->options, $mod );	// localized

			if ( ! isset( $mt_og['og:site_name'] ) )
				$mt_og['og:site_name'] = SucomUtil::get_site_name( $this->p->options, $mod );	// localized

			if ( ! isset( $mt_og['og:title'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'getting title for og:title meta tag' );
				$mt_og['og:title'] = $this->p->webpage->get_title( $this->p->options['og_title_len'], '...', $mod );
			}

			if ( ! isset( $mt_og['og:description'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'getting description for og:description meta tag' );
				$mt_og['og:description'] = $this->p->webpage->get_description( $this->p->options['og_desc_len'],
					'...', $mod, true, $this->p->options['og_desc_hashtags'], true, 'og_desc' );
			}

			// if the page is an article, then define the other article meta tags
			if ( isset( $mt_og['og:type'] ) && $mt_og['og:type'] == 'article' ) {

				// meta tag not defined or value is null
				if ( ! isset( $mt_og['article:author'] ) ) {
					if ( $mod['is_post'] ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'getting names / urls for article:author meta tags' );

						if ( $mod['post_author'] ) {
							$mt_og['article:author'] = $this->p->m['util']['user']->get_og_profile_urls( $mod['post_author'], $crawler_name );
							$mt_og['article:author:name'] = $this->p->m['util']['user']->get_author_meta( $mod['post_author'],
								$this->p->options['fb_author_name'] );

						} else $mt_og['article:author'] = array();

						if ( ! empty( $mod['post_coauthors'] ) )
							$mt_og['article:author'] = array_merge( $mt_og['article:author'],
								$this->p->m['util']['user']->get_og_profile_urls( $mod['post_coauthors'], $crawler_name ) );
					}
				}

				// meta tag not defined or value is null
				if ( ! isset( $mt_og['article:publisher'] ) )
					$mt_og['article:publisher'] = SucomUtil::get_locale_opt( 'fb_publisher_url', $this->p->options, $mod );

				// meta tag not defined or value is null
				if ( ! isset( $mt_og['article:tag'] ) )
					$mt_og['article:tag'] = $this->p->webpage->get_tags( $post_id );

				// meta tag not defined or value is null
				if ( ! isset( $mt_og['article:section'] ) )
					$mt_og['article:section'] = $this->p->webpage->get_article_section( $post_id );

				// meta tag not defined or value is null
				if ( ! isset( $mt_og['article:published_time'] ) )
					$mt_og['article:published_time'] = trim( get_post_time( 'c', true, $post_id ) );	// $gmt = true

				// meta tag not defined or value is null
				if ( ! isset( $mt_og['article:modified_time'] ) )
					$mt_og['article:modified_time'] = trim( get_post_modified_time( 'c', true, $post_id ) );	// $gmt = true
			}

			// get all videos
			// call before getting all images to find / use preview images
			if ( ! isset( $mt_og['og:video'] ) && $aop ) {
				if ( empty( $max['og_vid_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'videos disabled: maximum videos = 0' );
				} else {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'getting videos for og:video meta tag' );
					$mt_og['og:video'] = $this->get_all_videos( $max['og_vid_max'], $mod, $check_dupes, 'og' );
					if ( ! empty( $mt_og['og:video'] ) && is_array( $mt_og['og:video'] ) ) {
						foreach ( $mt_og['og:video'] as $num => $og_video ) {
							if ( isset( $og_video['og:video:type'] ) && 
								$og_video['og:video:type'] !== 'text/html' &&
									SucomUtil::get_mt_media_url( $og_video, 'og:image' ) ) {
								$prev_count++;
								$mt_og['og:video'][$num]['og:video:has_image'] = true;
							} else $mt_og['og:video'][$num]['og:video:has_image'] = false;
						}
						if ( $prev_count > 0 ) {
							$max['og_img_max'] -= $prev_count;
							if ( $this->p->debug->enabled )
								$this->p->debug->log( $prev_count.
									' video preview images found (og_img_max adjusted to '.
										$max['og_img_max'].')' );
						}
					}
				} 
			}

			// get all images
			if ( ! isset( $mt_og['og:image'] ) ) {
				if ( empty( $max['og_img_max'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'images disabled: maximum images = 0' );
				} else {
					$img_sizes = array( 'og' => $lca.'-opengraph' );

					if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) ) {
						// add richpin to process both image sizes
						if ( is_admin() )
							SucomUtil::add_before_key( $img_sizes, 'og', array( 'rp' => $lca.'-richpin' ) );
						// use only pinterest (rich pin) image size
						elseif ( $crawler_name === 'pinterest' )
							SucomUtil::do_replace_key( $img_sizes, 'og', array( 'rp' => $lca.'-richpin' ) );
					}

					foreach ( $img_sizes as $md_pre => $size_name ) {

						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'getting images for '.$md_pre.' ('.$size_name.')' );

						// the size_name is used as a context for duplicate checks
						$mt_og[$md_pre.':image'] = $this->get_all_images( $max['og_img_max'], $size_name, $mod, $check_dupes, $md_pre );

						// if there's no image, and no video preview, then add the default image for singular (aka post) webpages
						if ( empty( $mt_og[$md_pre.':image'] ) && ! $prev_count && $mod['is_post'] )
							$mt_og[$md_pre.':image'] = $this->p->media->get_default_image( $max['og_img_max'],
								$size_name, $check_dupes );

						switch ( $md_pre ) {
							case 'rp':
								if ( is_admin() ) {
									// show both og and pinterest meta tags in the head tags tab
									// by renaming each og:image to pinterest:image 
									foreach ( $mt_og[$md_pre.':image'] as $num => $arr )
										$mt_og[$md_pre.':image'][$num] = SucomUtil::preg_grep_keys( '/^og:/',
											$arr, false, 'pinterest:' );

								// rename the rp:image array to og:image
								} else $mt_og = SucomUtil::rename_keys( $mt_og, array( $md_pre.':image' => 'og:image' ) );

								break;
						}
					}
				} 
			}

			return apply_filters( $lca.'_og', $mt_og, $mod['use_post'], $mod );
		}

		public function get_all_videos( $num = 0, array &$mod, $check_dupes = true, $md_pre = 'og', $force_prev = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'num' => $num,
					'mod' => $mod,
					'check_dupes' => $check_dupes,
					'md_pre' => $md_pre,
					'force_prev' => $force_prev,
				) );
			}

			$og_ret = array();
			$lca = $this->p->cf['lca'];
			$aop = $this->p->check->aop( $lca, true, $this->p->is_avail['aop'] );
			$use_prev = $this->p->options['og_vid_prev_img'];		// default option value is true/false
			$num_diff = SucomUtil::count_diff( $og_ret, $num );
			$this->p->util->clear_uniq_urls( 'video' );			// clear cache for 'video' context

			if ( $aop && ! empty( $mod['obj'] ) ) {
				if ( ( $mod_prev = $mod['obj']->get_options( $mod['id'], 'og_vid_prev_img' ) ) !== null ) {
					$use_prev = $mod_prev;	// use module option value
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'setting use_prev to '.$use_prev.' from meta data' );
				}
				$og_ret = array_merge( $og_ret, $mod['obj']->get_og_video( $num_diff, $mod['id'], $check_dupes, $md_pre ) );
			}

			if ( count( $og_ret ) < 1 && 
				$this->p->util->force_default_video( $mod ) )
					$og_ret = array_merge( $og_ret, $this->p->media->get_default_video( $num_diff, $check_dupes ) );
			else {
				$num_diff = SucomUtil::count_diff( $og_ret, $num );

				if ( $mod['is_post'] && 
					! $this->p->util->is_maxed( $og_ret, $num ) )
						$og_ret = array_merge( $og_ret, 
							$this->p->media->get_content_videos( $num_diff, $mod, $check_dupes ) );

				$this->p->util->slice_max( $og_ret, $num );

				if ( empty( $use_prev ) && 
					$force_prev === false ) {

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'use_prev and force_prev are false: removing video preview images' );
					foreach ( $og_ret as $num => $og_video ) {
						$og_ret[$num]['og:video:has_image'] = false;
						foreach ( SucomUtil::preg_grep_keys( '/^og:image(:.*)?$/', $og_video ) as $k => $v )
							unset ( $og_ret[$num][$k] );
					}
				}
			}

			// if $md_pre is 'none' (special index keyword), don't load custom video title / description
			// only the first video is given the custom title and description (if one was entered)
			if ( $aop && ! empty( $mod['obj'] ) && $md_pre !== 'none' ) {
				foreach ( array(
					'og_vid_title' => 'og:video:title',
					'og_vid_desc' => 'og:video:description',
				) as $key => $tag ) {
					$value = $mod['obj']->get_options( $mod['id'], $key );
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
					if ( ! empty( $og_video['og:video:embed_url'] ) ) {
						$og_embed = $og_video;		// start with a copy of all meta tags

						if ( strpos( $og_video['og:video:embed_url'], 'https:' ) !== false ) {
							if ( ! empty( $this->p->options['add_meta_property_og:video:secure_url'] ) )
								$og_embed['og:video:secure_url'] = $og_video['og:video:embed_url'];
							else $og_embed['og:video:secure_url'] = '';	// just in case
						}

						$og_embed['og:video:url'] = $og_video['og:video:embed_url'];
						$og_embed['og:video:type'] = 'text/html';

						$og_extend[] = $og_video;	// add application/x-shockwave-flash first
						$og_extend[] = $og_embed;	// add the new text/html video second

					} else $og_extend[] = $og_video;
				}
				return $og_extend;
			} else return $og_ret;
		}

		public function get_all_images( $num = 0, $size_name = 'thumbnail', array &$mod, $check_dupes = true, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num' => $num,
					'size_name' => $size_name,
					'mod' => $mod,
					'check_dupes' => $check_dupes,
					'md_pre' => $md_pre,
				) );
			}

			$og_ret = array();
			$lca = $this->p->cf['lca'];
			$num_diff = SucomUtil::count_diff( $og_ret, $num );
			$force_regen = $this->p->util->is_force_regen( $mod, $md_pre );	// false by default
			$this->p->util->clear_uniq_urls( $size_name );			// clear cache for $size_name context

			if ( $mod['is_post'] ) {

				// is_attachment() only works on the front-end, so check the post_type as well
				if ( ( is_attachment( $mod['id'] ) || get_post_type( $mod['id'] ) === 'attachment' ) && 
					wp_attachment_is_image( $mod['id'] ) ) {

					$og_image = $this->p->media->get_attachment_image( $num_diff, 
						$size_name, $mod['id'], $check_dupes );

					// exiting early
					if ( empty( $og_image ) )
						return array_merge( $og_ret, $this->p->media->get_default_image( $num_diff, 
							$size_name, $check_dupes, $force_regen ) );
					else return array_merge( $og_ret, $og_image );
				}

				// check for custom meta, featured, or attached image(s)
				// allow for empty post ID in order to execute featured / attached image filters for modules
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) )
					$og_ret = array_merge( $og_ret, $this->p->media->get_post_images( $num_diff, 
						$size_name, $mod['id'], $check_dupes, $md_pre ) );

				// check for ngg shortcodes and query vars
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) &&
					$this->p->is_avail['media']['ngg'] && 
						! empty( $this->p->m['media']['ngg'] ) ) {

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'checking for ngg shortcodes and query vars' );

					// ngg pre-v2 used query arguments
					$ngg_query_og_ret = array();
					$num_diff = SucomUtil::count_diff( $og_ret, $num );
					if ( version_compare( $this->p->m['media']['ngg']->ngg_version, '2.0.0', '<' ) )
						$ngg_query_og_ret = $this->p->m['media']['ngg']->get_query_images( $num_diff, 
							$size_name, $mod['id'], $check_dupes );

					// if we found images in the query, skip content shortcodes
					if ( count( $ngg_query_og_ret ) > 0 ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'skipping additional shortcode images: '.
								count( $ngg_query_og_ret ).' image(s) returned' );
						$og_ret = array_merge( $og_ret, $ngg_query_og_ret );

					// if no query images were found, continue with ngg shortcodes in content
					} elseif ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
						$num_diff = SucomUtil::count_diff( $og_ret, $num );
						$og_ret = array_merge( $og_ret, 
							$this->p->m['media']['ngg']->get_shortcode_images( $num_diff, 
								$size_name, $mod['id'], $check_dupes ) );
					}
				} // end of check for ngg shortcodes and query vars

				// if we haven't reached the limit of images yet, keep going and check the content text
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'checking the content text for images' );

					$num_diff = SucomUtil::count_diff( $og_ret, $num );
					$og_ret = array_merge( $og_ret, $this->p->media->get_content_images( $num_diff, 
						$size_name, $mod, $check_dupes, $force_regen ) );
				}

			} else {
				// get_og_images() also provides filter hooks for additional image ids and urls
				if ( ! empty( $mod['obj'] ) )	// term or user
					$og_ret = array_merge( $og_ret, $mod['obj']->get_og_image( $num_diff, 
						$size_name, $mod['id'], $check_dupes, $force_regen, $md_pre ) );

				if ( count( $og_ret ) < 1 && 
					$this->p->util->force_default_image( $mod, 'og' ) )
						return array_merge( $og_ret, $this->p->media->get_default_image( $num_diff, 
							$size_name, $check_dupes, $force_regen ) );
			}

			$this->p->util->slice_max( $og_ret, $num );

			return $og_ret;
		}

		// returned array can include a varying number of elements, depending on the $output value
		public function get_the_media_info( $size_name, array $output, array &$mod, $md_pre = 'og', $mt_pre = 'og', &$head = array() ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$ret = array();
			$lca = $this->p->cf['lca'];
			$aop = $this->p->check->aop( $lca, true, $this->p->is_avail['aop'] );
			$og_image = null;
			$og_video = null;

			if ( empty( $head ) ) {
				foreach ( $output as $key ) {
					switch ( $key ) {
						case 'pid':
						case ( preg_match( '/^(image|img)/', $key ) ? true : false ):
							if ( $og_image === null )	// get images only once
								$og_image = $this->get_all_images( 1, $size_name, $mod, false, $md_pre );
							break;
						case ( preg_match( '/^(vid|prev)/', $key ) ? true : false ):
							if ( $og_video === null && $aop )	// get videos only once
								$og_video = $this->get_all_videos( 1, $mod, false, $md_pre );	// $check_dupes = false
							break;
					}
				}
			} else $og_image = $og_video = array( $head );

			foreach ( $output as $key ) {
				switch ( $key ) {
					case 'pid':
					case 'image':
					case 'img_url':
						$mt_name = $key === 'pid' ?
							$mt_pre.':image:id' : $mt_pre.':image';

						if ( $og_video !== null )
							$ret[$key] = self::get_first_media_info( $mt_name, $og_video );

						if ( empty( $ret[$key] ) )
							$ret[$key] = self::get_first_media_info( $mt_name, $og_image );

						// if there's no image, and no video preview image, 
						// then add the default image for singular (aka post) webpages
						if ( empty( $ret[$key] ) && $mod['is_post'] ) {
							$og_image = $this->p->media->get_default_image( 1, $size_name, false );	// $check_dupes = false
							$ret[$key] = self::get_first_media_info( $mt_name, $og_image );
						}
						break;
					case 'video':
					case 'vid_url':
						$ret[$key] = self::get_first_media_info( $mt_pre.':video', $og_video );
						break;
					case 'vid_title':
						$ret[$key] = self::get_first_media_info( $mt_pre.':video:title', $og_video );
						break;
					case 'vid_desc':
						$ret[$key] = self::get_first_media_info( $mt_pre.':video:description', $og_video );
						break;
					case 'prev_url':
					case 'preview':
						$ret[$key] = self::get_first_media_info( $mt_pre.':video:thumbnail_url', $og_video );
						break;
					default:
						$ret[$key] = '';
						break;
				}
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->log( $ret );

			return $ret;
		}

		public static function get_first_media_info( $prefix, $mt_og ) {
			if ( empty( $mt_og ) || 
				! is_array( $mt_og ) )
					return '';

			switch ( $prefix ) {
				// if we're asking for an image or video url, then search all three values sequentially
				case ( preg_match( '/:(image|video)(:secure_url|:url)?$/', $prefix ) ? true : false ):
					$mt_search = array(
						$prefix.':secure_url',	// og:image:secure_url
						$prefix.':url',		// og:image:url
						$prefix,		// og:image
					);
					break;
				// otherwise, only search for that specific meta tag name
				default:
					$mt_search = array( $prefix );
					break;
			}

			$og_media = reset( $mt_og );	// only search the first media array

			foreach ( $mt_search as $key )
				if ( ! empty( $og_media[$key] ) && 
					$og_media[$key] !== -1 )
						return $og_media[$key];

			return '';
		}
	}
}

?>
