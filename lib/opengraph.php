<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoOpenGraph' ) ) {

	class WpssoOpenGraph {

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'plugin_image_sizes' => 1,
			) );

			// hook the first available filter name (example: 'language_attributes')
			foreach ( array( 'plugin_html_attr_filter', 'plugin_head_attr_filter' ) as $opt_prefix ) {
				if ( ! empty( $this->p->options[$opt_prefix.'_name'] ) &&
					$this->p->options[$opt_prefix.'_name'] !== 'none' ) {

					$wp_filter_name = $this->p->options[$opt_prefix.'_name'];
					add_filter( $wp_filter_name, array( &$this, 'add_ogpns_attributes' ),
						 ( isset( $this->p->options[$opt_prefix.'_prio'] ) ?
						 	(int) $this->p->options[$opt_prefix.'_prio'] : 100 ), 1 );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'added add_ogpns_attributes filter for '.$wp_filter_name );
					}

					break;	// stop here

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipping add_ogpns_attributes for '.
						$opt_prefix.' - filter name is empty or disabled' );
				}
			}
		}

		public function filter_plugin_image_sizes( $sizes ) {

			$sizes['og_img'] = array( 		// options prefix
				'name' => 'opengraph',		// wpsso-opengraph
				'label' => _x( 'Facebook / Open Graph',
					'image size label', 'wpsso' ),
			);

			return $sizes;
		}

		public function add_ogpns_attributes( $html_attr ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array (
					'html_attr' => $html_attr,
				) );
			}

			$lca = $this->p->cf['lca'];
			$use_post = apply_filters( $lca.'_use_post', false );	// used by woocommerce with is_shop()
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'required call to get_page_mod()' );
			}
			$mod = $this->p->util->get_page_mod( $use_post );	// get post/user/term id, module name, and module object reference
			$og_type = $this->get_og_type( $mod );

			$og_ns = array(
				'og' => 'http://ogp.me/ns#',
				'fb' => 'http://ogp.me/ns/fb#',
			);

			// check if the og_type is known and add it's namespace value
			// example: product, place, website, etc.
			if ( ! empty( $this->p->cf['head']['og_type_ns'][$og_type] ) )
				$og_ns[$og_type] = $this->p->cf['head']['og_type_ns'][$og_type];

			$og_ns = apply_filters( $lca.'_og_ns', $og_ns, $mod );

			if ( SucomUtil::is_amp() ) {
				// nothing to do				
			} else {
				$html_attr = ' '.$html_attr;	// prepare the string for testing

				// find and extract an existing prefix attribute value (if any)
				if ( strpos( $html_attr, ' prefix=' ) &&
					preg_match( '/^(.*) prefix=["\']([^"\']*)["\'](.*)$/', $html_attr, $match ) ) {
						$html_attr = $match[1].$match[3];	// remove the prefix
						$prefix_value = ' '.$match[2];
				} else $prefix_value = '';
	
				foreach ( $og_ns as $name => $url )
					if ( strpos( $prefix_value, ' '.$name.': '.$url ) === false )
						$prefix_value .= ' '.$name.': '.$url;
	
				$html_attr .= ' prefix="'.trim( $prefix_value ).'"';
			}

			return trim( $html_attr );
		}

		public function get_og_type( array $mod ) {

			$lca = $this->p->cf['lca'];

			// an index or static home page should always be 'website'
			if ( $mod['is_home'] ) {
				$og_type = 'website';

			// singular posts / pages are articles by default
			} elseif ( $mod['is_post'] ) {

				// check the post_type for a match with a known open graph type
				if ( ! empty( $mod['post_type'] ) &&
					isset( $this->p->cf['head']['og_type_ns'][$mod['post_type']] ) ) {
						$og_type = $mod['post_type'];
				} else {
					$og_type = empty( $this->p->options['og_post_type'] ) ?	// just in case
						'article' : $this->p->options['og_post_type'];
				}

			// default for everything else is 'website'
			} else {
				$og_type = 'website';
			}

			return apply_filters( $lca.'_og_type', $og_type, $mod );
		}

		public function get_array( array $mod, array $mt_og, $crawler_name = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( $crawler_name === false ) {
				$crawler_name = SucomUtil::get_crawler_name();
			}

			$lca = $this->p->cf['lca'];
			$pdir = $this->p->avail['*']['p_dir'];
			$aop = $this->p->check->aop( $lca, true, $pdir );
			$max = $this->p->util->get_max_nums( $mod );
			$post_id = $mod['is_post'] ? $mod['id'] : false;
			$check_dupes = true;
			$preview_count = 0;

			$mt_og = apply_filters( $lca.'_og_seed', $mt_og, $mod );

			if ( ! empty( $mt_og ) &&
				$this->p->debug->enabled ) {
				$this->p->debug->log( $lca.'_og_seed filter returned:' );
				$this->p->debug->log( $mt_og );
			}

			if ( ! isset( $mt_og['fb:admins'] ) && ! empty( $this->p->options['fb_admins'] ) ) {
				foreach ( explode( ',', $this->p->options['fb_admins'] ) as $fb_admin ) {
					$mt_og['fb:admins'][] = trim( $fb_admin );
				}
			}

			if ( ! isset( $mt_og['fb:app_id'] ) ) {
				$mt_og['fb:app_id'] = $this->p->options['fb_app_id'];
			}

			if ( ! isset( $mt_og['og:url'] ) ) {
				$mt_og['og:url'] = $this->p->util->get_sharing_url( $mod );
			}

			// define the type after the url
			if ( ! isset( $mt_og['og:type'] ) ) {
				$mt_og['og:type'] = $this->get_og_type( $mod );
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:type already defined = '.$mt_og['og:type'] );
			}

			if ( ! isset( $mt_og['og:locale'] ) ) {
				$mt_og['og:locale'] = $this->get_fb_locale( $this->p->options, $mod );	// localized
			}

			if ( ! isset( $mt_og['og:site_name'] ) ) {
				$mt_og['og:site_name'] = SucomUtil::get_site_name( $this->p->options, $mod );	// localized
			}

			if ( ! isset( $mt_og['og:title'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting title for og:title meta tag' );
				}
				$mt_og['og:title'] = $this->p->page->get_title( $this->p->options['og_title_len'], '...', $mod );
			}

			if ( ! isset( $mt_og['og:description'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting description for og:description meta tag' );
				}
				$mt_og['og:description'] = $this->p->page->get_description( $this->p->options['og_desc_len'],
					'...', $mod, true, $this->p->options['og_desc_hashtags'], true, 'og_desc' );
			}

			/*
			 * Get all videos.
			 *
			 * Call before getting all images to find / use preview images.
			 */
			if ( ! isset( $mt_og['og:video'] ) && $aop ) {
				if ( empty( $max['og_vid_max'] ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'videos disabled: maximum videos = 0' );
					}
				} else {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'getting videos for og:video meta tag' );
					}
					$mt_og['og:video'] = $this->get_all_videos( $max['og_vid_max'], $mod, $check_dupes, 'og' );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking for video preview images' );
					}
					if ( ! empty( $mt_og['og:video'] ) && is_array( $mt_og['og:video'] ) ) {

						foreach ( $mt_og['og:video'] as $num => $single_video ) {

							$image_url = SucomUtil::get_mt_media_url( $single_video, 'og:image' );

							/*
							 * Check preview images for duplicates since the same videos may be available in
							 * different formats (application/x-shockwave-flash and text/html for example).
							 */
							if ( $image_url && $this->p->util->is_uniq_url( $image_url, 'preview' ) ) {
								$mt_og['og:video'][$num]['og:video:has_image'] = true;
								$preview_count++;
							} else {
								$mt_og['og:video'][$num]['og:video:has_image'] = false;
							}
						}

						if ( $preview_count > 0 ) {
							$max['og_img_max'] -= $preview_count;
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $preview_count.' video preview images found '.
									'(og_img_max adjusted to '.$max['og_img_max'].')' );
							}
						} elseif ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'no video preview images found' );
						}
					}
				}
			}

			/*
			 * Get all images.
			 */
			if ( ! isset( $mt_og['og:image'] ) ) {
				if ( empty( $max['og_img_max'] ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'images disabled: maximum images = 0' );
					}
				} else {
					$img_sizes = array( 'og' => $lca.'-opengraph' );

					foreach ( $img_sizes as $md_pre => $size_name ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting images for '.$md_pre.' ('.$size_name.')' );
						}

						// the size_name is used as a context for duplicate checks
						$mt_og[$md_pre.':image'] = $this->get_all_images( $max['og_img_max'],
							$size_name, $mod, $check_dupes, $md_pre );

						// if there's no image, and no video preview, then add the default image for singular (aka post) webpages
						if ( empty( $mt_og[$md_pre.':image'] ) && ! $preview_count && $mod['is_post'] ) {
							$mt_og[$md_pre.':image'] = $this->p->media->get_default_images( $max['og_img_max'],
								$size_name, $check_dupes );
						}
					}
				}
			}

			/*
			 * Pre-define some basic open graph meta tags for this og:type. If the meta tag
			 * has an associated meta option name, then read it's value from the meta options.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'checking og_type_mt array for known meta tags and md options' );
			}
			if ( isset( $this->p->cf['head']['og_type_mt'][$mt_og['og:type']] ) ) {	// check if og:type is in config

				// optimize and call get_options() only once
				// returns an empty string if no meta found
				$md_opts = empty( $mod['obj'] ) ? array() : (array) $mod['obj']->get_options( $mod['id'] );

				foreach( $this->p->cf['head']['og_type_mt'][$mt_og['og:type']] as $mt_name => $md_idx ) {

					if ( $md_idx && isset( $md_opts[$md_idx] ) && $md_opts[$md_idx] !== '' ) {	// use custom value if available
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $mt_og['og:type'].' meta tag '.$mt_name.' value from option = '.$md_opts[$md_idx] );
						}
						$mt_og[$mt_name] = $md_opts[$md_idx];
					} elseif ( isset( $mt_og[$mt_name] ) ) {	// if the meta tag has not already been set
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $mt_og['og:type'].' meta tag '.$mt_name.' original value kept = '.$mt_og[$mt_name] );
						}
					} else {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $mt_og['og:type'].' meta tag '.$mt_name.' pre-defined as null' );
						}
						$mt_og[$mt_name] = null;	// use null so isset() returns false
					}
				}

				if ( isset( $mt_og['product:price:amount'] ) ) {
					if ( is_numeric( $mt_og['product:price:amount'] ) ) {	// allow for price of 0
						if ( empty( $mt_og['product:price:currency'] ) ) {
							$mt_og['product:price:currency'] = $this->p->options['plugin_def_currency'];
						}
					} else {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'product price amount must be numeric' );
						}
						unset( $mt_og['product:price:amount'] );
						unset( $mt_og['product:price:currency'] );
					}
				}
			}

			/*
			 * If the module is a post object, define the author, publishing date, etc.
			 * These values may still be used by other filters, and if the og:type is
			 * not an article, the meta tags will be sanitized at the end of
			 * WpssoHead::get_head_array().
			 */
			if ( $mod['is_post'] && $post_id ) {

				if ( ! isset( $mt_og['og:updated_time'] ) ) {
					$mt_og['og:updated_time'] = trim( get_post_modified_time( 'c', true, $post_id ) );	// $gmt = true
				}

				if ( ! isset( $mt_og['article:author'] ) ) {

					if ( $mod['is_post'] && isset( $this->p->m['util']['user'] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting names / urls for article:author meta tags' );
						}

						$user_mod =& $this->p->m['util']['user'];

						if ( $mod['post_author'] ) {
							$mt_og['article:author'] = $user_mod->get_og_profile_urls( $mod['post_author'], $crawler_name );
							$mt_og['article:author:name'] = $user_mod->get_author_meta( $mod['post_author'],
								$this->p->options['fb_author_name'] );
						} else {
							$mt_og['article:author'] = array();
						}

						if ( ! empty( $mod['post_coauthors'] ) ) {
							$mt_og['article:author'] = array_merge( $mt_og['article:author'],
								$user_mod->get_og_profile_urls( $mod['post_coauthors'], $crawler_name ) );
						}
					}
				}

				if ( ! isset( $mt_og['article:publisher'] ) ) {
					$mt_og['article:publisher'] = SucomUtil::get_key_value( 'fb_publisher_url', $this->p->options, $mod );
				}

				if ( ! isset( $mt_og['article:tag'] ) ) {
					$mt_og['article:tag'] = $this->p->page->get_tags( $post_id );
				}

				if ( ! isset( $mt_og['article:section'] ) ) {
					$mt_og['article:section'] = $this->p->page->get_article_section( $post_id );
				}

				if ( ! isset( $mt_og['article:published_time'] ) ) {
					$mt_og['article:published_time'] = trim( get_post_time( 'c', true, $post_id ) );	// $gmt = true
				}

				if ( ! isset( $mt_og['article:modified_time'] ) ) {
					$mt_og['article:modified_time'] = trim( get_post_modified_time( 'c', true, $post_id ) );	// $gmt = true
				}
			}

			return (array) apply_filters( $lca.'_og', $mt_og, $mod );
		}

		/*
		 * Unset mis-matched og_type meta tags using the 'og_type_mt' array as a reference.
		 * For example, remove all 'article' meta tags if the og_type is 'website'. Removing
		 * only known meta tags (using the 'og_type_mt' array as a reference) protects
		 * internal meta tags that may be used later by WpssoHead::extract_head_info().
		 * For example, the schema:type:id and p:image meta tags.
		 *
		 * The 'og_content_map' array is also checked for Schema values that need to be
		 * swapped for simpler Open Graph meta tag values.
		 */
		public function sanitize_array( array $mod, array $mt_og ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( empty( $mt_og['og:type'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og:type is empty and required for sanitation' );
				}
				return $mt_og;
			}

			foreach ( $this->p->cf['head']['og_type_mt'] as $og_type => $mt_names ) {
				foreach ( $mt_names as $mt_name => $md_idx ) {
					if ( isset( $mt_og[$mt_name] ) ) {
						if (  $og_type !== $mt_og['og:type'] ) {	// mis-matched meta tag for this og:type
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'removing extra meta tag '.$mt_name );
							}
							unset( $mt_og[$mt_name] );
						} elseif ( isset( $this->p->cf['head']['og_content_map'][$mt_name][$mt_og[$mt_name]] ) ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'mapping content value for '.$mt_name );
							}
							$mt_og[$mt_name] = $this->p->cf['head']['og_content_map'][$mt_name][$mt_og[$mt_name]];
						}
					}
				}
			}

			return $mt_og;
		}

		public function get_all_videos( $num = 0, array $mod, $check_dupes = true, $md_pre = 'og', $force_prev = false ) {

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
			$pdir = $this->p->avail['*']['p_dir'];
			$aop = $this->p->check->aop( $lca, true, $pdir );
			$use_prev = $this->p->options['og_vid_prev_img'];		// default option value is true/false
			$num_diff = SucomUtil::count_diff( $og_ret, $num );
			$this->p->util->clear_uniq_urls( 'video' );			// clear cache for 'video' context

			/*
			 * Get video and preview enable/disable option from the post/term/user meta.
			 */
			if ( $aop && ! empty( $mod['obj'] ) ) {

				// get_options() returns null if an index key is not found
				if ( ( $mod_prev = $mod['obj']->get_options( $mod['id'], 'og_vid_prev_img' ) ) !== null ) {

					$use_prev = $mod_prev;	// use true/false/1/0 value from the custom option

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting use_prev to '.( empty( $use_prev ) ?
							'false' : 'true' ).' from meta data' );
					}
				}

				$og_ret = array_merge( $og_ret, $mod['obj']->get_og_videos( $num_diff, $mod['id'], $check_dupes, $md_pre ) );
			}

			$num_diff = SucomUtil::count_diff( $og_ret, $num );

			/*
			 * Optionally get more videos from the post content.
			 */
			if ( $mod['is_post'] && ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$og_ret = array_merge( $og_ret, $this->p->media->get_content_videos( $num_diff, $mod, $check_dupes ) );
			}

			$this->p->util->slice_max( $og_ret, $num );

			/*
			 * Optionally remove the image meta tags (aka video preview).
			 */
			if ( empty( $use_prev ) && empty( $force_prev ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'use_prev and force_prev are false - removing video preview images' );
				}

				foreach ( $og_ret as $num => $single_video ) {
					foreach ( SucomUtil::preg_grep_keys( '/^og:image(:.*)?$/', $single_video ) as $k => $v ) {
						unset ( $og_ret[$num][$k] );
					}
					$og_ret[$num]['og:video:has_image'] = false;
				}
			}

			/*
			 * If $md_pre is 'none' (special index keyword), then don't load custom video
			 * title / description. Only the first video is given the custom title and
			 * description (if one was entered). The og:video:title and og:video:description
			 * meta tags are not standard and their values will only appear in Schema markup.
			 */
			if ( $aop && ! empty( $mod['obj'] ) && $md_pre !== 'none' ) {

				foreach ( array(
					'og_vid_title' => 'og:video:title',
					'og_vid_desc' => 'og:video:description',
				) as $idx => $mt_name ) {

					// get_options() returns null if an index key is not found
					$value = $mod['obj']->get_options( $mod['id'], $idx );

					if ( ! empty( $value ) ) {	// must be a non-empty string
						foreach ( $og_ret as $num => $single_video ) {
							$og_ret[$num][$mt_name] = $value;
							break;	// only do the first video
						}
					}
				}
			}

			if ( ! empty( $this->p->options['og_vid_html_type'] ) ) {

				$og_extend = array();

				foreach ( $og_ret as $num => $single_video ) {

					if ( ! empty( $single_video['og:video:embed_url'] ) ) {

						// start with a copy of all og meta tags
						$single_embed = SucomUtil::get_mt_prop_video( 'og', $single_video, false );

						// exclude the facebook applink meta tags
						$single_embed = SucomUtil::preg_grep_keys( '/^og:/', $single_embed );

						if ( strpos( $single_video['og:video:embed_url'], 'https:' ) !== false ) {

							if ( ! empty( $this->p->options['add_meta_property_og:video:secure_url'] ) ) {
								$single_embed['og:video:secure_url'] = $single_video['og:video:embed_url'];
							} else {
								$single_embed['og:video:secure_url'] = '';	// just in case
							}
						}

						$single_embed['og:video:url'] = $single_video['og:video:embed_url'];
						$single_embed['og:video:type'] = 'text/html';

						// embedded videos may not have width / height information
						foreach ( array( 'og:video:width', 'og:video:height' ) as $mt_name ) {
							if ( isset( $single_embed[$mt_name] ) && $single_embed[$mt_name] === '' ) {
								unset( $single_embed[$mt_name] );
							}
						}

						// add application/x-shockwave-flash video first and the text/html video second
						if ( SucomUtil::get_mt_media_url( $single_video, 'og:video', array( ':secure_url', ':url', '' ) ) ) {
							$og_extend[] = $single_video;
						}

						$og_extend[] = $single_embed;

					} else {
						$og_extend[] = $single_video;
					}
				}
				return $og_extend;
			} else {
				return $og_ret;
			}
		}

		public function get_all_images( $num = 0, $size_name = 'thumbnail', array $mod, $check_dupes = true, $md_pre = 'og' ) {

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

					$single_image = $this->p->media->get_attachment_image( $num_diff,
						$size_name, $mod['id'], $check_dupes );

					// exiting early
					if ( empty( $single_image ) ) {
						return array_merge( $og_ret, $this->p->media->get_default_images( $num_diff,
							$size_name, $check_dupes, $force_regen ) );
					} else {
						return array_merge( $og_ret, $single_image );
					}
				}

				// check for custom meta, featured, or attached image(s)
				// allow for empty post ID in order to execute featured / attached image filters for modules
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
					$og_ret = array_merge( $og_ret, $this->p->media->get_post_images( $num_diff,
						$size_name, $mod['id'], $check_dupes, $md_pre ) );
				}

				// check for ngg shortcodes and query vars
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) &&
					$this->p->avail['media']['ngg'] &&
						! empty( $this->p->m['media']['ngg'] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking for ngg shortcodes and query vars' );
					}

					// ngg pre-v2 used query arguments
					$ngg_query_og_ret = array();
					$num_diff = SucomUtil::count_diff( $og_ret, $num );

					if ( version_compare( $this->p->m['media']['ngg']->ngg_version, '2.0.0', '<' ) ) {
						$ngg_query_og_ret = $this->p->m['media']['ngg']->get_query_images( $num_diff,
							$size_name, $mod['id'], $check_dupes );
					}

					// if we found images in the query, skip content shortcodes
					if ( count( $ngg_query_og_ret ) > 0 ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'skipping additional shortcode images: '.
								count( $ngg_query_og_ret ).' image(s) returned' );
						}
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
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking the content text for images' );
					}
					$num_diff = SucomUtil::count_diff( $og_ret, $num );
					$og_ret = array_merge( $og_ret, $this->p->media->get_content_images( $num_diff,
						$size_name, $mod, $check_dupes, $force_regen ) );
				}

			} else {
				// get_og_images() also provides filter hooks for additional image ids and urls
				if ( ! empty( $mod['obj'] ) ) {	// term or user
					$og_ret = array_merge( $og_ret, $mod['obj']->get_og_images( $num_diff,
						$size_name, $mod['id'], $check_dupes, $force_regen, $md_pre ) );
				}

				if ( count( $og_ret ) < 1 && $this->p->util->force_default_image( $mod, 'og' ) ) {
					return array_merge( $og_ret, $this->p->media->get_default_images( $num_diff,
						$size_name, $check_dupes, $force_regen ) );
				}
			}

			$this->p->util->slice_max( $og_ret, $num );

			return $og_ret;
		}

		/*
		 * The returned array can include a varying number of elements, depending on the $request value.
		 */
		public function get_media_info( $size_name, array $request, array $mod, $md_pre = 'og', $mt_pre = 'og', $head = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret = array();
			$lca = $this->p->cf['lca'];
			$pdir = $this->p->avail['*']['p_dir'];
			$aop = $this->p->check->aop( $lca, true, $pdir );
			$og_images = null;
			$og_videos = null;

			if ( empty( $head ) ) {
				foreach ( $request as $key ) {
					switch ( $key ) {
						case 'pid':
						case ( preg_match( '/^(image|img)/', $key ) ? true : false ):
							if ( $og_images === null ) {	// get images only once
								$og_images = $this->get_all_images( 1, $size_name, $mod, false, $md_pre );
							}
							break;
						case ( preg_match( '/^(vid|prev)/', $key ) ? true : false ):
							if ( $og_videos === null && $aop ) {	// get videos only once
								$og_videos = $this->get_all_videos( 1, $mod, false, $md_pre );	// $check_dupes = false
							}
							break;
					}
				}
			} else {
				$og_images = $og_videos = array( $head );
			}

			foreach ( $request as $key ) {
				unset( $mt_name );
				switch ( $key ) {
					case 'pid':
						if ( ! isset( $mt_name ) ) {
							$mt_name = $mt_pre.':image:id';
						}
						// no break - fall through
					case 'image':
					case 'img_url':
						if ( ! isset( $mt_name ) ) {
							$mt_name = $mt_pre.':image';
						}
						// no break - fall through

						if ( $og_videos !== null ) {
							$ret[$key] = $this->get_media_value( $mt_name, $og_videos );
						}

						if ( empty( $ret[$key] ) ) {
							$ret[$key] = $this->get_media_value( $mt_name, $og_images );
						}

						// if there's no image, and no video preview image,
						// then add the default image for singular (aka post) webpages
						if ( empty( $ret[$key] ) && $mod['is_post'] ) {
							$og_images = $this->p->media->get_default_images( 1, $size_name, false );	// $check_dupes = false
							$ret[$key] = $this->get_media_value( $mt_name, $og_images );
						}
						break;
					case 'video':
					case 'vid_url':
						$ret[$key] = $this->get_media_value( $mt_pre.':video', $og_videos );
						break;
					case 'vid_type':
						$ret[$key] = $this->get_media_value( $mt_pre.':video:type', $og_videos );
						break;
					case 'vid_title':
						$ret[$key] = $this->get_media_value( $mt_pre.':video:title', $og_videos );
						break;
					case 'vid_desc':
						$ret[$key] = $this->get_media_value( $mt_pre.':video:description', $og_videos );
						break;
					case 'vid_width':
						$ret[$key] = $this->get_media_value( $mt_pre.':video:width', $og_videos );
						break;
					case 'vid_height':
						$ret[$key] = $this->get_media_value( $mt_pre.':video:height', $og_videos );
						break;
					case 'prev_url':
					case 'preview':
						$ret[$key] = $this->get_media_value( $mt_pre.':video:thumbnail_url', $og_videos );
						break;
					default:
						$ret[$key] = '';
						break;
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $ret );
			}

			return $ret;
		}

		public function get_media_value( $prefix, $mt_og ) {

			if ( empty( $mt_og ) || ! is_array( $mt_og ) ) {
				return '';
			}

			$og_media = reset( $mt_og );	// only search the first media array

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

			foreach ( $mt_search as $key ) {
				if ( ! isset( $og_media[$key] ) ) {
					continue;
				} elseif ( $og_media[$key] === '' || $og_media[$key] === null ) {	// allow for 0
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $og_media[$key].' value is empty (skipped)' );
					}
				} elseif ( $og_media[$key] === WPSSO_UNDEF_INT || $og_media[$key] === (string) WPSSO_UNDEF_INT ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $og_media[$key].' value is '.WPSSO_UNDEF_INT.' (skipped)' );
					}
				} else {
					return $og_media[$key];
				}
			}

			return '';
		}

		// returns an optional and customized locale value for the og:locale meta tag
		// $mixed = 'default' | 'current' | post ID | $mod array
		public function get_fb_locale( array $opts, $mixed = 'current' ) {

			// check for customized locale
			if ( ! empty( $opts ) ) {
				$fb_locale_key = SucomUtil::get_key_locale( 'fb_locale', $opts, $mixed );
				if ( ! empty( $opts[$fb_locale_key] ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'returning '.$fb_locale_key.' value from options: '.$opts[$fb_locale_key] );
					}
					return $opts[$fb_locale_key];
				}
			}

			$locale = SucomUtil::get_locale( $mixed );
			$def_locale = SucomUtil::get_locale( 'default' );
			$fb_pub_lang = SucomUtil::get_pub_lang( 'facebook' );

			// exceptions
			switch ( $locale ) {
				case 'de_DE_formal':
					$locale = 'de_DE';
					break;
			}

			if ( ! empty( $fb_pub_lang[$locale] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning valid facebook locale: '.$locale );
				}
				return $locale;
			} elseif ( ! empty( $fb_pub_lang[$def_locale] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning default locale: '.$def_locale );
				}
				return $def_locale;
			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning fallback locale: en_US' );
				}
				return 'en_US';
			}
		}

	}
}

?>
