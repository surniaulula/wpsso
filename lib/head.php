<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoHead' ) ) {

	class WpssoHead {

		private $p;
		private static $dnc_const = array(
			'DONOTCACHEPAGE' => true,	// wp super cache and w3tc
			'COMET_CACHE_ALLOWED' => false,	// comet cache
		);

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			add_action( 'wp_head', array( &$this, 'add_head' ), WPSSO_HEAD_PRIORITY );
			add_action( 'amp_post_template_head', array( &$this, 'add_head' ), WPSSO_HEAD_PRIORITY );

			if ( ! empty( $this->p->options['add_link_rel_shortlink'] ) )
				remove_action( 'wp_head', 'wp_shortlink_wp_head' );

			// disable page caching for customized meta tags (same URL, different meta tags)
			if ( strpos( $this->get_head_cache_index(), 'crawler:none' ) === false ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'setting do-not-cache constants' );
				WpssoConfig::set_variable_constants( self::$dnc_const );	// set "do not cache" constants
			}
		}

		// $mixed = 'default' | 'current' | post ID | $mod array
		public function get_head_cache_index( $mixed = 'current', $sharing_url = false ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$crawler_name = SucomUtil::crawler_name();
			$head_index = 'locale:'.SucomUtil::get_locale( $mixed );

			if ( $sharing_url !== false )
				$head_index .= '_url:'.$sharing_url;

			if ( $this->p->is_avail['amp_endpoint'] && is_amp_endpoint() )
				$head_index .= '_amp:true';

			switch ( $crawler_name ) {
				case 'pinterest':	// pinterest gets different image sizes and does not read json markup
					$head_index .= '_crawler:'.$crawler_name;
					break;
				default:
					$head_index .= '_crawler:none';
					break;
			}

			return ltrim( $head_index, '_' );
		}

		// called by wp_head action
		public function add_head() {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$use_post = apply_filters( $lca.'_head_use_post', false );	// used by woocommerce with is_shop()
			$mod = $this->p->util->get_page_mod( $use_post );		// get post/user/term id, module name, and module object reference
			$read_cache = true;
			$mt_og = array();

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = '.get_option( 'home' ) );
				$this->p->debug->log( 'locale default = '.SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = '.SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = '.SucomUtil::get_locale( $mod ) );
				$this->p->util->log_is_functions();
			}

			$add_head_html = apply_filters( $lca.'_add_head_html', $this->p->is_avail['head'], $mod );
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'is_avail head = '.( $this->p->is_avail['head'] ? 'true' : 'false' ) );
				$this->p->debug->log( 'add_head_html = '.( $add_head_html ? 'true' : 'false' ) );
			}

			if ( $add_head_html )
				echo $this->get_head_html( $use_post, $mod, $read_cache, $mt_og );
			else echo "\n<!-- ".$lca." head html is disabled -->\n";

			// include additional information when debug mode is on
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'end of get_head_html' );

				// show debug log
				$this->p->debug->show_html( null, 'debug log' );

				// show constants
				$defined_constants = get_defined_constants( true );
				$defined_constants['user']['WPSSO_NONCE'] = '********';
				if ( is_multisite() )
					$this->p->debug->show_html( SucomUtil::preg_grep_keys( '/^(MULTISITE|^SUBDOMAIN_INSTALL|.*_SITE)$/', 
						$defined_constants['user'] ), 'multisite constants' );
				$this->p->debug->show_html( SucomUtil::preg_grep_keys( '/^WPSSO_/',
					$defined_constants['user'] ), 'wpsso constants' );

				// show active plugins
				$this->p->debug->show_html( print_r( WpssoUtil::active_plugins(), true ), 'active plugins' );

				// show available modules
				$this->p->debug->show_html( print_r( $this->p->is_avail, true ), 'available features' );

				// show all plugin options
				$opts = $this->p->options;
				foreach ( $opts as $key => $val ) {
					switch ( $key ) {
						case ( strpos( $key, '_js_' ) !== false ? true : false ):
						case ( strpos( $key, '_css_' ) !== false ? true : false ):
						case ( preg_match( '/_(html|key|secret|tid|token)$/', $key ) ? true : false ):
							$opts[$key] = '[removed]';
							break;
					}
				}
				$this->p->debug->show_html( $opts, 'wpsso settings' );

			}	// end of debug information
		}

		// extract certain key fields for display and sanity checks
		public function extract_head_info( array $mod, array $head_mt ) {

			foreach ( $head_mt as $mt ) {
				if ( ! isset( $mt[2] ) || 
					! isset( $mt[3] ) )
						continue;

				$mt_match = $mt[2].'-'.$mt[3];

				switch ( $mt_match ) {
					case 'property-og:type':
					case 'property-og:title':
					case 'property-og:description':
					case 'property-article:author:name':
					case ( strpos( $mt_match, 'name-schema:' ) === 0 ? true : false ):
						if ( ! isset( $head_info[$mt[3]] ) )	// only save the first meta tag value
							$head_info[$mt[3]] = $mt[5];
						break;
					case ( preg_match( '/^property-((og|pinterest):(image|video))(:secure_url|:url)?$/', $mt_match, $m ) ? true : false ):
						if ( ! empty( $mt[5] ) )
							$has_media[$m[1]] = true;	// optimize media loop
						break;
				}
			}

			/*
			 * Save the first image and video information found. Assumes array key order 
			 * defined by SucomUtil::get_mt_prop_image() and SucomUtil::get_mt_prop_video().
			 */
			foreach ( array( 'og:image', 'og:video', 'pinterest:image' ) as $prefix ) {
				if ( empty( $has_media[$prefix] ) )
					continue;

				$is_first = false;

				foreach ( $head_mt as $mt ) {
					if ( ! isset( $mt[2] ) || 
						! isset( $mt[3] ) )
							continue;

					if ( strpos( $mt[3], $prefix ) !== 0 ) {
						$is_first = false;

						// if we already found media, then skip to the next media prefix
						if ( ! empty( $head_info[$prefix] ) )
							continue 2;
						else continue;	// skip meta tags without matching prefix
					}

					$mt_match = $mt[2].'-'.$mt[3];

					switch ( $mt_match ) {
						case ( preg_match( '/^property-'.$prefix.'(:secure_url|:url)?$/', $mt_match, $m ) ? true : false ):
							if ( ! empty( $head_info[$prefix] ) )	// only save the media URL once
								continue 2;			// get the next meta tag
							if ( ! empty( $mt[5] ) ) {
								$head_info[$prefix] = $mt[5];	// save the media URL
								$is_first = true;
							}
							break;
						case ( preg_match( '/^property-'.$prefix.':(width|height|cropped|id|title|description)$/', $mt_match, $m ) ? true : false ):
							if ( $is_first !== true )		// only save for first media found
								continue 2;			// get the next meta tag
							$head_info[$mt[3]] = $mt[5];
							break;
					}
				}
			}

			/*
			 * Save meta tag values for later sorting in edit tables
			 */
			foreach ( $mod['obj']->get_sortable_columns() as $column_key => $sort_cols ) {
				if ( empty( $sort_cols['meta_key'] ) )	// just in case
					continue;
				switch ( $column_key ) {
		 			case 'schema_type':
						$meta_value = isset( $head_info['schema:type:id'] ) ?
							$head_info['schema:type:id'] : 'none';
						break;
					case 'og_img':
						$meta_value = ( $og_img = $mod['obj']->get_og_img_column_html( $head_info, $mod ) ) ?
							$og_img : 'none';
						break;
					case 'og_desc':
						$meta_value = isset( $head_info['og:description'] ) ?
							$head_info['og:description'] : 'none';
						break;
					default:
						$meta_value = 'none';	// just in case
						break;
				}
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'sortable meta for '.$mod['name'].' id '.$mod['id'].' '.$column_key.' = '.$meta_value );
				$mod['obj']->update_sortable_meta( $mod['id'], $column_key, $meta_value );
			}

			return $head_info;
		}

		public function get_head_html( $use_post = false, &$mod = false, $read_cache = true, array &$mt_og ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $use_post );	// get post/user/term id, module name, and module object reference
			$crawler_name = SucomUtil::crawler_name();
			$comment_begin = $lca.' meta tags begin';
			$comment_end = $lca.' meta tags end';

			// extra begin/end meta tag for duplicate meta tags check
			$html = "\n\n".'<!-- '.$comment_begin.' -->'."\n".
				'<!-- added on '.date( 'c' ).( $crawler_name !== 'none' ? 
					' ('.$crawler_name.') ' : ' ' ).'-->'."\n";

			if ( ! empty( $this->p->options['plugin_check_head'] ) )
				$html .= '<meta name="'.$lca.':mark" content="'.$comment_begin.'"/>'."\n";

			// first element of returned array is the html tag
			$indent = 0;
			foreach ( $this->get_head_array( $use_post, $mod, $read_cache, $mt_og ) as $mt ) {
				if ( ! empty( $mt[0] ) ) {
					if ( $indent && strpos( $mt[0], '</noscript' ) === 0 )
						$indent = 0;
					$html .= str_repeat( "\t", 
						(int) $indent ).$mt[0];
					if ( strpos( $mt[0], '<noscript' ) === 0 )
						$indent = 1;
				}
			}

			// extra begin / end meta tag for duplicate meta tags check
			if ( ! empty( $this->p->options['plugin_check_head'] ) )
				$html .= '<meta name="'.$lca.':mark" content="'.$comment_end.'"/>'."\n";

			$html .= '<!-- '.$comment_end.' -->'."\n\n";

			return $html;
		}

		public function get_head_array( $use_post = false, &$mod = false, $read_cache = true, &$mt_og = array() ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build head array' );	// begin timer

			$lca = $this->p->cf['lca'];
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $use_post );	// get post/user/term id, module name, and module object reference
			$sharing_url = $this->p->util->get_sharing_url( $mod );
			$crawler_name = SucomUtil::crawler_name();
			$head_array = array();
			$head_index = $this->get_head_cache_index( $mod, $sharing_url );
			$cache_salt = __METHOD__.'('.SucomUtil::get_mod_salt( $mod, $sharing_url ).')';
			$cache_id = $lca.'_'.md5( $cache_salt );
			$cache_exp = (int) apply_filters( $lca.'_cache_expire_head_array', 
				$this->p->options['plugin_head_cache_exp'] );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'sharing url = '.$sharing_url );
				$this->p->debug->log( 'crawler name = '.$crawler_name );
				$this->p->debug->log( 'head index = '.$head_index );
				$this->p->debug->log( 'transient expire = '.$cache_exp );
				$this->p->debug->log( 'transient salt = '.$cache_salt );
			}

			if ( $cache_exp > 0 ) {
				$head_array = get_transient( $cache_id );
				if ( isset( $head_array[$head_index] ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'head index found in array from transient '.$cache_id );
						$this->p->debug->mark( 'build head array' );	// end timer
					}
					return $head_array[$head_index];	// stop here
				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'head index not in array from transient '.$cache_id );
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'head array transient is disabled' );

			// set the reference url for admin notices
			if ( is_admin() )
				$this->p->notice->set_reference_url( $sharing_url );

			// define the author_id (if one is available)
			if ( $mod['is_post'] ) {
				if ( $mod['post_author'] )
					$author_id = $mod['post_author'];
				else $author_id = false;
			} elseif ( $mod['is_user'] ) {
				$author_id = $mod['id'];
			} else $author_id = false;

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'author id = '.
					( $author_id === false ? 'false' : (int) $author_id ) );

			/*
			 * Open Graph
			 */
			$mt_og = $this->p->og->get_array( $mod, $mt_og, $crawler_name );

			/*
			 * Weibo
			 */
			$mt_weibo = $this->p->weibo->get_array( $mod, $mt_og, $crawler_name );

			/*
			 * Twitter Cards
			 */
			$mt_tc = $this->p->tc->get_array( $mod, $mt_og, $crawler_name );

			/*
			 * Name / SEO meta tags
			 */
			$mt_name = array();
			if ( ! empty( $this->p->options['add_meta_name_author'] ) ) {
				// fallback for authors without a Facebook page URL in their user profile
				if ( empty( $mt_og['article:author'] ) &&
					is_object( $this->p->m['util']['user'] ) )	// just in case
						$mt_name['author'] = $this->p->m['util']['user']->get_author_meta( $author_id,
							$this->p->options['fb_author_name'] );
			}

			if ( ! empty( $this->p->options['add_meta_name_canonical'] ) )
				$mt_name['canonical'] = $this->p->util->get_canonical_url( $mod );

			if ( ! empty( $this->p->options['add_meta_name_description'] ) )
				$mt_name['description'] = $this->p->webpage->get_description( $this->p->options['seo_desc_len'], 
					'...', $mod, true, false, true, 'seo_desc' );	// add_hashtags = false

			if ( ! empty( $this->p->options['add_meta_name_p:domain_verify'] ) ) {
				if ( ! empty( $this->p->options['rp_dom_verify'] ) )
					$mt_name['p:domain_verify'] = $this->p->options['rp_dom_verify'];
			}

			$mt_name = apply_filters( $lca.'_meta_name', $mt_name, $use_post, $mod );

			/*
			 * Link relation tags
			 */
			$link_rel = array();

			if ( ! empty( $this->p->options['add_link_rel_author'] ) ) {
				if ( ! empty( $author_id ) && is_object( $this->p->m['util']['user'] ) )	// just in case
					$link_rel['author'] = $this->p->m['util']['user']->get_author_website( $author_id, 
						$this->p->options['seo_author_field'] );
			}

			if ( ! empty( $this->p->options['add_link_rel_publisher'] ) ) {
				if ( ! empty( $this->p->options['seo_publisher_url'] ) )
					$link_rel['publisher'] = $this->p->options['seo_publisher_url'];
			}

			if ( ! empty( $this->p->options['add_link_rel_shortlink'] ) ) {
				if ( $mod['is_post'] )
					$link_rel['shortlink'] = wp_get_shortlink( $mod['id'], 'post' );
				else $link_rel['shortlink'] = apply_filters( $lca.'_shorten_url',
					$mt_og['og:url'], $this->p->options['plugin_shortener'] );
			}

			$link_rel = apply_filters( $lca.'_link_rel', $link_rel, $use_post, $mod );

			/*
			 * Schema meta tags
			 */
			$mt_schema = $this->p->schema->get_meta_array( $mod, $mt_og, $crawler_name );

			/*
			 * JSON-LD script
			 */
			$mt_json_array = $this->p->schema->get_json_array( $mod, $mt_og, $crawler_name );

			/*
			 * Clean-up open graph meta tags
			 */
			$og_types =& $this->p->cf['head']['og_type_mt'];
			foreach ( $og_types as $og_type => $mt_names ) {
				if ( $og_type === $mt_og['og:type'] )
					continue;
				foreach ( $mt_names as $key ) {
					if ( isset( $mt_og[$key] ) && ! isset( $og_types[$mt_og['og:type']][$key] ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'non-matching '.$mt_og['og:type'].' meta tag - unsetting '.$key );
						unset( $og[$key] );
					}
				}
			}

			/*
			 * Generator meta tags
			 */
			$mt_gen['generator'] = $this->p->check->get_ext_list();

			/*
			 * Combine and return all meta tags
			 */
			$head_array[$head_index] = array_merge(
				$this->get_mt_array( 'meta', 'name', $mt_gen, $mod ),
				$this->get_mt_array( 'link', 'rel', $link_rel, $mod ),
				$this->get_mt_array( 'meta', 'property', $mt_og, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_weibo, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_tc, $mod ),
				$this->get_mt_array( 'meta', 'itemprop', $mt_schema, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_name, $mod ),		// seo description is last
				$this->p->schema->get_noscript_array( $mod, $mt_og, $crawler_name ),
				$mt_json_array
			);

			if ( $cache_exp > 0 ) {
				// update the transient array and keep the original expiration time
				$cache_exp = SucomUtil::update_transient_array( $cache_id, $head_array, $cache_exp );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'head array saved to transient '.$cache_id.' ('.$cache_exp.' seconds)' );
			}

			// reset the reference url for admin notices
			if ( is_admin() )
				$this->p->notice->set_reference_url( null );

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build head array' );	// end timer

			return $head_array[$head_index];
		}

		/*
		 * Loops through the arrays and calls get_single_mt() for each
		 */
		private function get_mt_array( $tag, $type, array &$mt_array, array &$mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( count( $mt_array ).' '.$tag.' '.$type.' to process' );
				$this->p->debug->log( $mt_array );
			}

			if ( empty( $mt_array ) )
				return array();
			elseif ( ! is_array( $mt_array ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: mt_array argument is not an array' );
				return array();
			}

			$singles = array();
			foreach ( $mt_array as $d_name => $d_val ) {	// first dimension array (associative)

				if ( is_array( $d_val ) ) {

					if ( empty( $d_val ) ) {	// allow hooks to modify the value
						$singles[] = $this->get_single_mt( $tag,
							$type, $d_name, null, '', $mod );

					} else foreach ( $d_val as $dd_num => $dd_val ) {	// second dimension array

						if ( SucomUtil::is_assoc( $dd_val ) ) {

							// prevent duplicates - ignore images from text/html video
							if ( isset( $dd_val['og:video:type'] ) && 
								$dd_val['og:video:type'] === 'text/html' ) {

								// skip if text/html video markup is disabled
								if ( empty( $this->p->options['og_vid_html_type'] ) )
									continue;

								unset( $dd_val['og:video:embed_url'] );	// redundant - just in case
								$ignore_images = true;

							} else $ignore_images = false;

							foreach ( $dd_val as $ddd_name => $ddd_val ) {	// third dimension array (associative)

								// prevent duplicates - ignore images from text/html video
								if ( $ignore_images && strpos( $ddd_name, 'og:image' ) !== false )
									continue;

								if ( is_array( $ddd_val ) ) {
									if ( empty( $ddd_val ) ) {
										$singles[] = $this->get_single_mt( $tag,
											$type, $ddd_name, null, '', $mod );
									} else foreach ( $ddd_val as $dddd_num => $dddd_val ) {	// fourth dimension array
										$singles[] = $this->get_single_mt( $tag,
											$type, $ddd_name, $dddd_val, $d_name.':'.
												( $dd_num + 1 ), $mod );
									}
								} else $singles[] = $this->get_single_mt( $tag,
									$type, $ddd_name, $ddd_val, $d_name.':'.
										( $dd_num + 1 ), $mod );
							}
						} else $singles[] = $this->get_single_mt( $tag,
							$type, $d_name, $dd_val, $d_name.':'.
								( $dd_num + 1 ), $mod );
					}
				} else $singles[] = $this->get_single_mt( $tag,
					$type, $d_name, $d_val, '', $mod );
			}

			$merged = array();
			foreach ( $singles as $num => $element ) {
				foreach ( $element as $parts )
					$merged[] = $parts;
				unset ( $singles[$num] );
			}

			return $merged;
		}

		public function get_single_mt( $tag, $type, $name, $value, $cmt, array &$mod ) {

			// check for known exceptions for the 'property' $type
			if ( $tag === 'meta' && $type === 'property' ) {
				switch ( $name ) {
					// optimize by matching known values first
					case ( strpos( $name, 'og:' ) === 0 ? true : false ):
					case ( strpos( $name, 'article:' ) === 0 ? true : false ):
						break;	// $type is already property
					case ( strpos( $name, ':' ) === false ? true : false ):
					case ( strpos( $name, 'twitter:' ) === 0 ? true : false ):
					case ( strpos( $name, 'schema:' ) === 0 ? true : false ):	// internal meta tags
						$type = 'name';
						break;
				}
			}

			$ret = array();
			$attr = $tag === 'link' ? 'href' : 'content';
			$log_prefix = $tag.' '.$type.' '.$name;
			$charset = get_bloginfo( 'charset' );

			if ( is_array( $value ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_prefix.' value is an array (skipped)' );
				return $ret;

			} elseif ( is_object( $value ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_prefix.' value is an object (skipped)' );
				return $ret;
			}

			if ( strpos( $value, '%%' ) )
				$value = $this->p->util->replace_inline_vars( $value, $mod );

			switch ( $name ) {
				case 'og:image:secure_url':
				case 'og:video:secure_url':
					if ( strpos( $value, 'https:' ) !== 0 ) {	// just in case
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $log_prefix.' is not https (skipped)' );
						return $ret;
					}
					break;
			}

			$ret[] = array( '', $tag, $type, $name, $attr, $value, $cmt );

			// $parts = array( $html, $tag, $type, $name, $attr, $value, $cmt );
			foreach ( $ret as $num => $parts ) {

				// filtering of single meta tags can be enabled by defining WPSSO_FILTER_SINGLE_TAGS as true
				if ( SucomUtil::get_const( 'WPSSO_FILTER_SINGLE_TAGS' ) )
					$parts = $this->filter_single_mt( $parts, $mod );

				$log_prefix = $parts[1].' '.$parts[2].' '.$parts[3];

				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_prefix.' = "'.$parts[5].'"' );

				if ( $parts[5] === '' || $parts[5] === null ) {		// allow for 0
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_prefix.' value is empty (skipped)' );

				} elseif ( $parts[5] === -1 || $parts[5] === '-1' ) {	// -1 is reserved
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_prefix.' value is -1 (skipped)' );

				/*
				 * Encode and escape all values, regardless if the meta tag is enabled or not.
				 *
				 * If the meta tag is enabled, HTML will be created and saved in $parts[0].
				 */
				} else {
					if ( $parts[1] === 'meta' && 
						$parts[2] === 'itemprop' && 
							strpos( $parts[3], '.' ) !== 0 )
								$match_name = preg_replace( '/^.*\./', '', $parts[3] );
					else $match_name = $parts[3];

					// boolean values are converted to their string equivalent
					if ( is_bool( $parts[5] ) )
						$parts[5] = $parts[5] ? 'true' : 'false';

					switch ( $match_name ) {
						case 'og:url':
						case 'og:secure_url':
						case 'og:image':
						case 'og:image:url':
						case 'og:image:secure_url':
						case 'og:video':
						case 'og:video:url':
						case 'og:video:secure_url':
						case 'og:video:embed_url':
						case 'place:business:menu_url':
						case 'place:business:order_url':
						case 'twitter:image':
						case 'twitter:player':
						case 'canonical':
						case 'shortlink':
						case 'menu':	// place menu url
						case 'url':
							$parts[5] = SucomUtil::esc_url_encode( $parts[5] );
							break;
						case 'og:title':
						case 'og:description':
						case 'twitter:title':
						case 'twitter:description':
						case 'description':
						case 'name':
							$parts[5] = SucomUtil::encode_emoji( htmlentities( $parts[5],
								ENT_QUOTES, $charset, false ) );	// double_encode = false
						default:
							$parts[5] = htmlentities( $parts[5],
								ENT_QUOTES, $charset, false );		// double_encode = false
							break;
					}

					// convert mixed case itemprop names (for example) to lower case
					$add_key = strtolower( 'add_'.$parts[1].'_'.$parts[2].'_'.$parts[3] );

					if ( ! empty( $this->p->options[$add_key] ) ) {
						$parts[0] = ( empty( $parts[6] ) ? '' : '<!-- '.$parts[6].' -->' ).
							'<'.$parts[1].' '.$parts[2].'="'.$match_name.'" '.$parts[4].'="'.$parts[5].'"/>'."\n";
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( $log_prefix.' is disabled (skipped)' );

					$ret[$num] = $parts;	// save the HTML and encoded value
				}
			}

			return $ret;
		}

		// filtering of single meta tags can be enabled by defining WPSSO_FILTER_SINGLE_TAGS as true
		// $parts = array( $html, $tag, $type, $name, $attr, $value, $cmt );
		private function filter_single_mt( array &$parts, array &$mod ) {
			$log_prefix = $parts[1].' '.$parts[2].' '.$parts[3];
			$filter_name = $this->p->cf['lca'].'_'.$parts[1].'_'.$parts[2].'_'.$parts[3].'_'.$parts[4];
			$new_value = apply_filters( $filter_name, $parts[5], $parts[6], $mod );

			if ( $parts[5] !== $new_value ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_prefix.' (original) = "'.$parts[5].'"' );
				if ( is_array( $new_value ) ) {
					foreach( $new_value as $key => $value ) {
						$this->p->debug->log( $log_prefix.' (filtered:'.$key.') = "'.$value.'"' );
						$parts[6] = $parts[3].':'.( is_numeric( $key ) ? $key + 1 : $key );
						$parts[5] = $value;
					}
				} else {
					$this->p->debug->log( $log_prefix.' (filtered) = "'.$new_value.'"' );
					$parts[5] = $new_value;
				}
			}
			return $parts;
		}
	}
}

?>
