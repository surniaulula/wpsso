<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoHead' ) ) {

	class WpssoHead {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'head_cache_salt' => 1,		// modify the cache salt for certain crawlers
			) );
			add_action( 'wp_head', array( &$this, 'add_header' ), WPSSO_HEAD_PRIORITY );
			add_action( 'amp_post_template_head', array( $this, 'add_header' ), WPSSO_HEAD_PRIORITY );
		}

		public function filter_head_cache_salt( $salt ) {

			if ( $this->p->is_avail['amp_endpoint'] && is_amp_endpoint() )
				$salt .= '_amp:true';

			$crawler_name = SucomUtil::crawler_name();
			switch ( $crawler_name ) {
				case 'pinterest':
					$salt .= '_crawler:'.$crawler_name;
					break;
			}

			return $salt;
		}

		// called by wp_head action
		public function add_header() {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$use_post = apply_filters( $lca.'_header_use_post', false );	// used by woocommerce with is_shop()
			$mod = $this->p->util->get_page_mod( $use_post );		// get post/user/term id, module name, and module object reference
			$read_cache = true;
			$mt_og = array();

			if ( $this->p->debug->enabled )
				$this->p->util->log_is_functions();

			if ( $this->p->is_avail['mt'] )
				echo $this->get_header_html( $use_post, $mod, $read_cache, $mt_og );
			else echo "\n<!-- ".$lca." meta tags disabled -->\n";

			// include additional information when debug mode is on
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'end of get_header_html' );

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
						case ( preg_match( '/_(html|key|secret|tid)$/', $key ) ? true : false ):
							$opts[$key] = '[removed]';
							break;
					}
				}
				$this->p->debug->show_html( $opts, 'wpsso settings' );

			}	// end of debug information
		}

		// extract certain key fields for reference and sanity checks
		public function extract_head_info( &$head_mt, &$head_info = array() ) {

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

						if ( ! isset( $head_info[$mt[3]] ) )
							$head_info[$mt[3]] = $mt[5];
						break;

					case ( preg_match( '/^property-((og|pinterest):(image|video))(:secure_url|:url)?$/',
						$mt_match, $m ) ? true : false ):

						if ( ! empty( $mt[5] ) )
							$has_media[$m[1]] = true;		// optimize media loop
						break;
				}
			}

			// save first image and video information
			// assumes array key order defined by SucomUtil::get_mt_prop_image() 
			// and SucomUtil::get_mt_prop_video()
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

			return $head_info;
		}

		public function get_header_html( $use_post = false, &$mod = false, $read_cache = true, array &$mt_og ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $use_post );	// get post/user/term id, module name, and module object reference
			$cmt_begin = $lca.' meta tags begin';
			$cmt_end = $lca.' meta tags end';
			$crawler_name = SucomUtil::crawler_name();

			// extra begin/end meta tag for duplicate meta tags check
			$html = "\n\n".'<!-- '.$cmt_begin.' -->'."\n".
				'<!-- generated on '.date( 'c' ).' for '.$crawler_name.' -->'."\n";
			if ( ! empty( $this->p->options['plugin_check_head'] ) )
				$html .= '<meta name="'.$lca.':mark" content="'.$cmt_begin.'"/>'."\n";

			// first element of returned array is the html tag
			$indent = "";
			foreach ( $this->get_header_array( $use_post, $mod, $read_cache, $mt_og ) as $mt ) {
				if ( ! empty( $mt[0] ) ) {
					if ( $indent && 
						strpos( $mt[0], '</noscript' ) === 0 )
							$indent = "";
					$html .= $indent.$mt[0];
					if ( strpos( $mt[0], '<noscript' ) === 0 )
						$indent = "\t";
				}
			}

			// extra begin/end meta tag for duplicate meta tags check
			if ( ! empty( $this->p->options['plugin_check_head'] ) )
				$html .= '<meta name="'.$lca.':mark" content="'.$cmt_end.'"/>'."\n";
			$html .= '<!-- '.$cmt_end.' -->'."\n\n";

			return $html;
		}

		public function get_header_array( $use_post = false, &$mod = false, $read_cache = true, &$mt_og = array() ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build header array' );	// begin timer

			$lca = $this->p->cf['lca'];
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $use_post );	// get post/user/term id, module name, and module object reference
			$author_id = false;
			$sharing_url = $this->p->util->get_sharing_url( $mod );
			$header_array = array();

			if ( $this->p->is_avail['cache']['transient'] ) {

				// head_cache_salt filter may add amp true/false and/or crawler name
				$cache_salt = __METHOD__.'('.apply_filters( $lca.'_head_cache_salt',
					SucomUtil::get_mod_salt( $mod ).'_url:'.$sharing_url ).')';
				$cache_id = $lca.'_'.md5( $cache_salt );
				$cache_type = 'object cache';

				if ( $this->p->debug->enabled )
					$this->p->debug->log( $cache_type.': transient salt '.$cache_salt );

				if ( apply_filters( $lca.'_header_read_cache', $read_cache ) ) {
					$header_array = get_transient( $cache_id );
					if ( $header_array !== false ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $cache_type.': header array from transient '.$cache_id );
						return $header_array;	// stop here
					}
				}
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'skipped checking for transient cache object' );

			/*
			 * Define an author_id, if one is available
			 */
			if ( $mod['is_post'] ) {
				if ( $mod['post_author'] )
					$author_id = $mod['post_author'];
				elseif ( $def_author_id = $this->p->util->get_default_author_id( 'seo' ) )
					$author_id = $def_author_id;
			} elseif ( $mod['is_user'] )
				$author_id = $mod['id'];
			elseif ( $def_author_id = $this->p->util->force_default_author( $mod, 'seo' ) )
				$author_id = $def_author_id;

			if ( $this->p->debug->enabled && $author_id !== false )
				$this->p->debug->log( 'author_id is '.$author_id );

			$crawler_name = SucomUtil::crawler_name();
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'crawler_name is '.$crawler_name );

			/*
			 * Open Graph
			 */
			$mt_og = $this->p->og->get_array( $use_post, $mod, $mt_og, $crawler_name );

			/*
			 * Twitter Cards
			 */
			$mt_tc = $this->p->tc->get_array( $use_post, $mod, $mt_og, $crawler_name );

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
				$mt_name['canonical'] = $sharing_url;

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
				if ( ! empty( $author_id ) &&
					is_object( $this->p->m['util']['user'] ) )	// just in case
						$link_rel['author'] = $this->p->m['util']['user']->get_author_website( $author_id, 
							$this->p->options['seo_author_field'] );
			}

			if ( ! empty( $this->p->options['add_link_rel_publisher'] ) ) {
				if ( ! empty( $this->p->options['seo_publisher_url'] ) )
					$link_rel['publisher'] = $this->p->options['seo_publisher_url'];
			}

			$link_rel = apply_filters( $lca.'_link_rel', $link_rel, $use_post, $mod );

			/*
			 * Schema meta tags
			 */
			$mt_schema = $this->p->schema->get_meta_array( $use_post, $mod, $mt_og, $crawler_name );

			/*
			 * JSON-LD script array - execute before merge to set some internal $mt_og meta tags
			 */
			$mt_json_array = $this->p->schema->get_json_array( $use_post, $mod, $mt_og, $author_id );

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
			$mt_gen = array();

			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( empty( $info['version'] ) )	// only active extensions
					continue;
				$ins = $this->p->check->aop( $ext, false );
				$mt_gen['generator'][] = $info['short'].( $ins ? ' Pro' : '' ).
					' '.$info['version'].'/'.( $this->p->check->aop( $ext,
						true, $this->p->is_avail['aop'] ) ?
							'L' : ( $ins ? 'U' : 'G' ) );
			}

			/*
			 * Combine and return all meta tags
			 */
			$header_array = array_merge(
				$this->get_mt_array( 'meta', 'name', $mt_gen, $mod ),
				$this->get_mt_array( 'link', 'rel', $link_rel, $mod ),
				$this->get_mt_array( 'meta', 'property', $mt_og, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_tc, $mod ),
				$this->get_mt_array( 'meta', 'itemprop', $mt_schema, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_name, $mod ),		// seo description is last
				$this->p->schema->get_noscript_array( $mod, $mt_og, $author_id ),
				$mt_json_array
			);

			/*
			 * Save the header array to the WordPress transient cache
			 */
			if ( apply_filters( $lca.'_header_set_cache', $this->p->is_avail['cache']['transient'] ) ) {
				set_transient( $cache_id, $header_array, $this->p->options['plugin_object_cache_exp'] );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $cache_type.': header array saved to transient '.
						$cache_id.' ('.$this->p->options['plugin_object_cache_exp'].' seconds)');
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build header array' );	// end timer

			return $header_array;
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
					case ( strpos( $name, 'og:' ) === 0 ||
						strpos( $name, 'article:' ) === 0 ? true : false ):
						// $type is already property
						break;
					case ( strpos( $name, ':' ) === false ? true : false ):
					// schema is an internal set of meta tags
					case ( strpos( $name, 'twitter:' ) === 0 ||
						strpos( $name, 'schema:' ) === 0 ? true : false ):
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
				case 'og:image':
				case 'og:image:url':
				case 'og:video':
				case 'og:video:url':
					// add secure_url for open graph images and videos
					if ( strpos( $value, 'https:' ) === 0 ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $log_prefix.' adding secure_url for '.$value );
						$ret[] = array( '', $tag, $type, preg_replace( '/:url$/', '', $name ).':secure_url',
							$attr, $value, $cmt );
						$value = preg_replace( '/^https:/', 'http:', $value );
					}
					break;
				case 'og:image:secure_url':
				case 'og:video:secure_url':
					if ( strpos( $value, 'https:' ) !== 0 ) {
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
						case 'og:image':
						case 'og:image:url':
						case 'og:image:secure_url':
						case 'og:video':
						case 'og:video:url':
						case 'og:video:secure_url':
						case 'og:video:embed_url':
						case 'place:business:menu_url':
						case 'twitter:image':
						case 'twitter:player':
						case 'canonical':
						case 'menu':
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

					if ( ! empty( $this->p->options['add_'.$parts[1].'_'.$parts[2].'_'.$parts[3]] ) ) {
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
