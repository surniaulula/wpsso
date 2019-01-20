<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoHead' ) ) {

	class WpssoHead {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			add_action( 'wp_head', array( $this, 'maybe_disable_rel_canonical' ), -1000 );
			add_action( 'wp_head', array( $this, 'show_head' ), WPSSO_HEAD_PRIORITY );

			/**
			 * AMP.
			 */
			add_action( 'amp_post_template_head', array( $this, 'maybe_disable_rel_canonical' ), -1000 );
			add_action( 'amp_post_template_head', array( $this, 'show_head' ), WPSSO_HEAD_PRIORITY );
		}

		public function add_vary_user_agent_header( $headers ) {

			$headers [ 'Vary' ] = 'User-Agent';

			return $headers;
		}

		/**
		 * Can return an empty string if $mixed and $sharing_rul are false.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public function get_head_cache_index( $mixed = 'current', $sharing_url = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$cache_index = '';

			if ( false !== $mixed ) {
				$cache_index .= '_locale:' . SucomUtil::get_locale( $mixed );
			}

			if ( false !== $sharing_url ) {
				$cache_index .= '_url:' . $sharing_url;
			}

			/**
			 * AMP
			 */
			if ( SucomUtil::is_amp() ) {
				$cache_index .= '_amp:true';
			}

			$cache_index = trim( $cache_index, '_' );	// Cleanup leading underscores.

			$cache_index = apply_filters( $this->p->lca . '_head_cache_index', $cache_index, $mixed, $sharing_url );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'returned cache index is "' . $cache_index . '"' );
			}

			return $cache_index;
		}

		/**
		 * Called by wp_head and amp_post_template_head actions.
		 */
		public function maybe_disable_rel_canonical() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->options[ 'add_link_rel_canonical' ] ) ) {

				$current = current_filter();	// Since wp v2.5, aka current_action() in wp v3.9.

				switch( $current ) {

					case 'wp_head':

						remove_filter( $current, 'rel_canonical' );	// WordPress.

						remove_action( $current, 'amp_frontend_add_canonical' );	// AMP.

						break;

					case 'amp_post_template_head':

						remove_action( $current, 'amp_post_template_add_canonical' );	// AMP.

						break;
				}
			}
		}

		/**
		 * Called by wp_head action.
		 */
		public function show_head() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$use_post = apply_filters( $this->p->lca . '_use_post', false );	// Used by woocommerce with is_shop().

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'required call to get_page_mod()' );
			}

			$mod        = $this->p->util->get_page_mod( $use_post );	// Get post/user/term id, module name, and module object reference.
			$read_cache = true;
			$mt_og      = array();

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = ' . get_option( 'home' ) );
				$this->p->debug->log( 'locale default = ' . SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = ' . SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = ' . SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( 'wp_query salt = ' . SucomUtil::get_query_salt() );
				$this->p->util->log_is_functions();
			}

			$add_head_html = apply_filters( $this->p->lca . '_add_head_html', $this->p->avail[ '*' ][ 'head_html' ], $mod );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'avail head_html = ' . ( $this->p->avail[ '*' ][ 'head_html' ] ? 'true' : 'false' ) );
				$this->p->debug->log( 'add_head_html = ' . ( $add_head_html ? 'true' : 'false' ) );
			}

			if ( $add_head_html ) {
				echo $this->get_head_html( $use_post, $mod, $read_cache, $mt_og );
			} else {
				echo "\n" . '<!-- ' . $this->p->lca . ' head html is disabled -->' . "\n";
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'end of get_head_html' );
			}
		}

		/**
		 * Extract certain key fields for display and sanity checks.
		 */
		public function extract_head_info( array $mod, array $head_mt ) {

			$head_info = array();

			foreach ( $head_mt as $mt ) {

				if ( ! isset( $mt[ 2 ] ) || ! isset( $mt[ 3 ] ) ) {
					continue;
				}

				$mt_match = $mt[ 2 ] . '-' . $mt[ 3 ];

				switch ( $mt_match ) {

					case 'property-og:url':
					case 'property-og:type':
					case 'property-og:title':
					case 'property-og:description':
					case 'property-article:author:name':
					case ( strpos( $mt_match, 'name-schema:' ) === 0 ? true : false ):

						if ( ! isset( $head_info[$mt[ 3 ]] ) ) {	// Only save the first meta tag value.
							$head_info[$mt[ 3 ]] = $mt[ 5 ];
						}

						break;

					case ( preg_match( '/^property-((og|p):(image|video))(:secure_url|:url)?$/', $mt_match, $m ) ? true : false ):

						if ( ! empty( $mt[ 5 ] ) ) {
							$has_media[ $m[ 1 ] ] = true;	// Optimize media loop.
						}

						break;
				}
			}

			/**
			 * Save the first image and video information found. Assumes array key order
			 * defined by SucomUtil::get_mt_image_seed() and SucomUtil::get_mt_video_seed().
			 */
			foreach ( array( 'og:image', 'og:video', 'p:image' ) as $mt_prefix ) {

				if ( empty( $has_media[$mt_prefix] ) ) {
					continue;
				}

				$is_first = false;

				foreach ( $head_mt as $mt ) {

					if ( ! isset( $mt[ 2 ] ) || ! isset( $mt[ 3 ] ) ) {
						continue;
					}

					if ( strpos( $mt[ 3 ], $mt_prefix ) !== 0 ) {

						$is_first = false;

						/**
						 * If we already found media, then skip to the next media prefix.
						 */
						if ( ! empty( $head_info[$mt_prefix] ) ) {
							continue 2;
						} else {
							continue;	// Skip meta tags without matching prefix.
						}
					}

					$mt_match = $mt[ 2 ] . '-' . $mt[ 3 ];

					switch ( $mt_match ) {

						case ( preg_match( '/^property-' . $mt_prefix . '(:secure_url|:url)?$/', $mt_match, $m ) ? true : false ):

							if ( ! empty( $head_info[ $mt_prefix ] ) ) {	// Only save the media URL once.
								continue 2;				// Get the next meta tag.
							}

							if ( ! empty( $mt[ 5 ] ) ) {
								$head_info[ $mt_prefix ] = $mt[ 5 ];	// Save the media URL.
								$is_first = true;
							}

							break;

						case ( preg_match( '/^property-' . $mt_prefix . ':(width|height|cropped|id|title|description)$/', $mt_match, $m ) ? true : false ):

							if ( true !== $is_first ) {	// Only save for first media found.
								continue 2;		// Get the next meta tag.
							}

							$head_info[$mt[ 3 ]] = $mt[ 5 ];

							break;
					}
				}
			}

			/**
			 * Save meta tag values for later sorting in list tables.
			 */
			foreach ( WpssoWpMeta::get_sortable_columns() as $col_key => $col_info ) {
				
				if ( empty( $col_info[ 'meta_key' ] ) || strpos( $col_info[ 'meta_key' ], '_' . $this->p->lca . '_head_info_' ) !== 0 ) {
					continue;
				}

				$meta_value = 'none';

				if ( ! empty( $col_info[ 'mt_name' ]  ) ) {
					if ( $col_info[ 'mt_name' ] === 'og:image' ) {	// Get the image thumbnail HTML.
						if ( $og_img = $mod[ 'obj' ]->get_og_img_column_html( $head_info, $mod ) ) {
							$meta_value = $og_img;
						}
					} elseif ( isset( $head_info[ $col_info[ 'mt_name' ] ] ) ) {
						$meta_value = $head_info[ $col_info[ 'mt_name' ] ];
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'updating meta for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' ' . $col_key . ' = ' . $meta_value );
				}

				$mod[ 'obj' ]->update_sortable_meta( $mod[ 'id' ], $col_key, $meta_value );
			}

			return $head_info;
		}

		public function get_mt_mark( $type ) {

			$ret = '';

			switch ( $type ) {

				case 'begin':
				case 'end':

					$add_meta = apply_filters( $this->p->lca . '_add_meta_name_' . $this->p->lca . ':mark',
						( empty( $this->p->options[ 'plugin_check_head' ] ) ? false : true ) );

					$comment = '<!-- ' . $this->p->lca . ' meta tags ' . $type . ' -->';
					$mt_name = $add_meta ? '<meta name="' . $this->p->lca . ':mark:' . $type . '" ' . 
						'content="' . $this->p->lca . ' meta tags ' . $type . '"/>' . "\n" : '';

					if ( $type === 'begin' ) {
						$ret = "\n\n" . $comment . "\n" . $mt_name;
					} else {
						$ret = $mt_name . $comment . "\n";
					}

					break;

				case 'preg':

					/**
					 * Some HTML optimization plugins/services may remove the double-quotes from the name attribute, 
					 * along with the trailing space and slash characters, so make these optional in the regex.
					 */
					$prefix = '<(!--[\s\n\r]+|meta[\s\n\r]+name="?' . $this->p->lca . ':mark:(begin|end)"?[\s\n\r]+content=")';
					$suffix = '([\s\n\r]+--|"[\s\n\r]*\/?)>';
		
					$ret = '/' . $prefix . $this->p->lca . ' meta tags begin' . $suffix . '.*' . 
						$prefix . $this->p->lca . ' meta tags end' . $suffix . '/ums';	// Enable utf8 support.

					break;
			}

			return $ret;
		}

		public function get_head_html( $use_post = false, &$mod = false, $read_cache = true, array &$mt_og ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}
				$mod = $this->p->util->get_page_mod( $use_post );
			}

			$mtime_start = microtime( true );

			$crawler_name = SucomUtil::get_crawler_name();

			$html = $this->get_mt_mark( 'begin' );

			$indent = 0;

			foreach ( $this->get_head_array( $use_post, $mod, $read_cache, $mt_og ) as $mt ) {

				if ( ! empty( $mt[0] ) ) {

					if ( $indent && strpos( $mt[0], '</noscript' ) === 0 ) {
						$indent = 0;
					}

					$html .= str_repeat( "\t", (int) $indent ) . $mt[0];

					if ( strpos( $mt[0], '<noscript' ) === 0 ) {
						$indent = 1;
					}
				}
			}

			$html .= $this->get_mt_mark( 'end' );

			$mtime_total = microtime( true ) - $mtime_start;

			$html .= '<!-- added on ' . date( 'c' ) . ' in ' . sprintf( '%f secs', $mtime_total ) . 
				( $crawler_name !== 'none' ? ' for ' . $crawler_name : '' ) . 
					' from ' . SucomUtilWP::raw_home_url() . ' -->' . "\n\n";

			return $html;
		}

		/**
		 * $read_cache is false when called by the post/term/user load_meta_page() method.
		 */
		public function get_head_array( $use_post = false, &$mod = false, $read_cache = true, &$mt_og = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'build head array' );	// Begin timer.
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->p->util->get_page_mod( $use_post );
			}

			$sharing_url = $this->p->util->get_sharing_url( $mod, $add_page = true, 'head_sharing_url' );

			if ( empty( $sharing_url ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: get_sharing_url() returned an empty string' );
				}

				return array();
			}

			$is_admin     = is_admin();	// Call the function only once.
			$crawler_name = SucomUtil::get_crawler_name();

			static $cache_exp_secs = null;	// Filter the cache expiration value only once.

			$cache_md5_pre = $this->p->lca . '_h_';

			if ( ! isset( $cache_exp_secs ) ) {	// Filter cache expiration if not already set.
				$cache_exp_filter = $this->p->cf[ 'wp' ][ 'transient' ][$cache_md5_pre][ 'filter' ];
				$cache_opt_key    = $this->p->cf[ 'wp' ][ 'transient' ][$cache_md5_pre][ 'opt_key' ];
				$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, $this->p->options[$cache_opt_key] );
			}

			$cache_salt  = __METHOD__ . '(' . SucomUtil::get_mod_salt( $mod, $sharing_url ) . ')';
			$cache_id    = $cache_md5_pre . md5( $cache_salt );
			$cache_index = $this->get_head_cache_index( $mod, $sharing_url );	// Includes locale, url, $wp_query args, etc.
			$cache_array = array();

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'sharing url = ' . $sharing_url );
				$this->p->debug->log( 'crawler name = ' . $crawler_name );
				$this->p->debug->log( 'cache expire = ' . $cache_exp_secs );
				$this->p->debug->log( 'cache salt = ' . $cache_salt );
				$this->p->debug->log( 'cache id = ' . $cache_id );
				$this->p->debug->log( 'cache index = ' . $cache_index );
			}

			if ( $cache_exp_secs > 0 ) {

				if ( $read_cache ) {	// False when called by the post/term/user load_meta_page() method.

					$cache_array = SucomUtil::get_transient_array( $cache_id );

					if ( isset( $cache_array[ $cache_index ] ) ) {

						if ( is_array( $cache_array[ $cache_index ] ) ) {	// Just in case.

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'exiting early: cache index found in transient cache' );
								$this->p->debug->mark( 'build head array' );	// end timer
							}

							return $cache_array[ $cache_index ];	// stop here

						} else {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'cache index is not an array' );
							}
						}
					} else {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'cache index not in transient cache' );
						}

						if ( ! is_array( $cache_array ) ) {
							$cache_array = array();
						}
					}

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'read cache for head is disabled' );
					}

					/**
					 * Force a refresh of the schema json data cache.
					 */
					WpssoSchema::delete_mod_cache_data( $mod );
				}

			} else {
			
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'head array transient cache is disabled' );

					if ( SucomUtil::delete_transient_array( $cache_id ) ) {
						$this->p->debug->log( 'deleted transient cache id ' . $cache_id );
					}
				}
			}

			/**
			 * Set a general reference value for admin notices.
			 */
			$is_admin ? $this->p->notice->set_ref( $sharing_url, $mod ) : false;

			/**
			 * Define the author_id (if one is available).
			 */
			$author_id = WpssoUser::get_author_id( $mod );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'author_id = ' . ( false === $author_id ? 'false' : $author_id ) );
			}

			/**
			 * Open Graph - define first to pass the mt_og array to other methods.
			 */
			$is_admin ? $this->p->notice->set_ref( $sharing_url, $mod, __( 'adding open graph meta tags', 'wpsso' ) ) : false;
			$mt_og = $this->p->og->get_array( $mod, $mt_og, $crawler_name );
			$is_admin ? $this->p->notice->unset_ref( $sharing_url ) : false;

			/**
			 * Weibo
			 */
			$mt_weibo = $this->p->weibo->get_array( $mod, $mt_og, $crawler_name );

			/**
			 * Twitter Cards
			 */
			$is_admin ? $this->p->notice->set_ref( $sharing_url, $mod, __( 'adding twitter card meta tags', 'wpsso' ) ) : false;
			$mt_tc = $this->p->tc->get_array( $mod, $mt_og, $crawler_name );
			$is_admin ? $this->p->notice->unset_ref( $sharing_url ) : false;

			/**
			 * Name / SEO meta tags
			 */
			$is_admin ? $this->p->notice->set_ref( $sharing_url, $mod, __( 'adding meta name meta tags', 'wpsso' ) ) : false;
			$mt_name = $this->p->meta_name->get_array( $mod, $mt_og, $crawler_name, $author_id );
			$is_admin ? $this->p->notice->unset_ref( $sharing_url ) : false;

			/**
			 * Link relation tags
			 */
			$is_admin ? $this->p->notice->set_ref( $sharing_url, $mod, __( 'adding link relation tags', 'wpsso' ) ) : false;
			$link_rel = $this->p->link_rel->get_array( $mod, $mt_og, $crawler_name, $author_id, $sharing_url );
			$is_admin ? $this->p->notice->unset_ref( $sharing_url ) : false;

			/**
			 * Schema meta tags
			 */
			$is_admin ? $this->p->notice->set_ref( $sharing_url, $mod, __( 'adding schema meta tags', 'wpsso' ) ) : false;
			$mt_schema = $this->p->schema->get_meta_array( $mod, $mt_og, $crawler_name );
			$is_admin ? $this->p->notice->unset_ref( $sharing_url ) : false;

			/**
			 * JSON-LD script
			 */
			$is_admin ? $this->p->notice->set_ref( $sharing_url, $mod, __( 'adding schema json-ld markup', 'wpsso' ) ) : false;
			$mt_json_array = $this->p->schema->get_json_array( $mod, $mt_og, $crawler_name );
			$is_admin ? $this->p->notice->unset_ref( $sharing_url ) : false;

			/**
			 * Generator meta tags
			 */
			$mt_generators[ 'generator' ] = $this->p->check->get_ext_list();

			/**
			 * Combine and return all meta tags
			 */
			$mt_og = $this->p->og->sanitize_array( $mod, $mt_og );	// Unset mis-matched og_type meta tags.

			$cache_array[ $cache_index ] = array_merge(
				$this->get_mt_array( 'meta', 'name', $mt_generators, $mod ),
				$this->get_mt_array( 'link', 'rel', $link_rel, $mod ),
				$this->get_mt_array( 'meta', 'property', $mt_og, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_weibo, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_tc, $mod ),
				$this->get_mt_array( 'meta', 'itemprop', $mt_schema, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_name, $mod ),	// SEO description is last.
				$this->p->schema->get_noscript_array( $mod, $mt_og, $crawler_name ),
				$mt_json_array
			);

			if ( $cache_exp_secs > 0 ) {

				/**
				 * Update the cached array and maintain the existing transient expiration time.
				 */
				$expires_in_secs = SucomUtil::update_transient_array( $cache_id, $cache_array, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'head array saved to transient cache (expires in ' . $expires_in_secs . ' secs)' );
				}
			}

			/**
			 * Unset the general reference value for admin notices.
			 */
			$is_admin ? $this->p->notice->unset_ref( $sharing_url ) : false;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'build head array' );	// End timer.
			}

			return $cache_array[ $cache_index ];
		}

		/**
		 * Loops through the arrays and calls get_single_mt() for each.
		 */
		private function get_mt_array( $tag, $type, array &$mt_array, array &$mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( count( $mt_array ) . ' ' . $tag . ' ' . $type . ' to process' );
				$this->p->debug->log( $mt_array );
			}

			if ( empty( $mt_array ) ) {
				return array();
			} elseif ( ! is_array( $mt_array ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: mt_array argument is not an array' );
				}
				return array();
			}

			$singles = array();

			foreach ( $mt_array as $d_name => $d_val ) {	// First dimension array (associative).

				if ( is_array( $d_val ) ) {

					/**
					 * Skip internal product offer and review arrays.
					 */
					if ( preg_match( '/:(offers|reviews)$/', $d_name ) ) {

						continue;

					/**
					 * Empty array - allow single mt filters a chance to modify the value.
					 */
					} elseif ( empty( $d_val ) ) {

						$singles[] = $this->get_single_mt( $tag, $type, $d_name, '', '', $mod );

					/**
					 * Second dimension array.
					 */
					} else foreach ( $d_val as $dd_num => $dd_val ) {

						if ( SucomUtil::is_assoc( $dd_val ) ) {

							$use_video_image = true;

							if ( isset( $dd_val[ 'og:video:type' ] ) ) {

								/**
								 * og:video:has_image will be false if ithere is no preview 
								 * image, or the preview image is a duplicate.
								 */
								if ( empty( $dd_val[ 'og:video:has_image' ] ) ) {
									$use_video_image = false;
								}

								if ( $dd_val[ 'og:video:type' ] === 'text/html' ) {

									/**
									 * Skip if 'text/html' video markup is disabled.
									 */
									if ( empty( $this->p->options[ 'og_vid_html_type' ] ) ) {
										continue;
									}
								}
							}

							/**
							 * Third dimension array (associative).
							 */
							foreach ( $dd_val as $ddd_name => $ddd_val ) {

								if ( ! $use_video_image && strpos( $ddd_name, 'og:image' ) === 0 ) {
									continue;
								}

								if ( is_array( $ddd_val ) ) {

									if ( empty( $ddd_val ) ) {

										$singles[] = $this->get_single_mt( $tag,
											$type, $ddd_name, '', '', $mod );

									} else foreach ( $ddd_val as $dddd_num => $dddd_val ) {	// Fourth dimension array.

										$singles[] = $this->get_single_mt( $tag,
											$type, $ddd_name, $dddd_val, $d_name . ':' . 
												( $dd_num + 1 ), $mod );
									}

								} else {
									$singles[] = $this->get_single_mt( $tag,
										$type, $ddd_name, $ddd_val, $d_name . ':' . 
											( $dd_num + 1 ), $mod );
								}
							}

						} else {
							$singles[] = $this->get_single_mt( $tag,
								$type, $d_name, $dd_val, $d_name . ':' . 
									( $dd_num + 1 ), $mod );
						}
					}

				} else {
					$singles[] = $this->get_single_mt( $tag,
						$type, $d_name, $d_val, '', $mod );
				}
			}

			$merged = array();

			foreach ( $singles as $num => $element ) {

				foreach ( $element as $parts ) {
					$merged[] = $parts;
				}

				unset ( $singles[ $num ] );
			}

			return $merged;
		}

		public function get_single_mt( $tag, $type, $name, $value, $cmt, array &$mod ) {

			/**
			 * Check for known exceptions for the "property" $type.
			 */
			if ( $tag === 'meta' ) {

				if ( $type === 'property' ) {

					/**
					 * Double-check the name to make sure its an open graph meta tag.
					 */
					switch ( $name ) {

						/**
						 * These names are not open graph meta tag names.
						 */
						case ( strpos( $name, 'twitter:' ) === 0 ? true : false ):
						case ( strpos( $name, 'schema:' ) === 0 ? true : false ):	// Internal meta tags.
						case ( strpos( $name, ':' ) === false ? true : false ):		// No colon in $name.

							$type = 'name';

							break;
					}

				} elseif ( $type === 'itemprop' ) {

					/**
					 * If an "itemprop" contains a url, then make sure it's a "link".
					 */
					if ( $tag !== 'link' && false !== filter_var( $value, FILTER_VALIDATE_URL ) ) {
						$tag = 'link';
					}
				}
			}

			/**
			 * Sanitation check for both "link rel href" and "link itemprop href".
			 * All other meta tags use a "content" attribute name.
			 */
			if ( $tag === 'link' ) {
				$attr = 'href';
			} else {
				$attr = 'content';
			}

			$singles = array();

			$log_prefix = $tag . ' ' . $type . ' ' . $name;

			static $charset = null;

			if ( ! isset( $charset  ) ) {
				$charset = get_bloginfo( 'charset' );
			}

			if ( is_array( $value ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $log_prefix . ' value is an array (skipped)' );
				}

				return $singles;

			} elseif ( is_object( $value ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $log_prefix . ' value is an object (skipped)' );
				}

				return $singles;
			}

			if ( false !== strpos( $value, '%%' ) ) {
				$value = $this->p->util->replace_inline_vars( $value, $mod );
			}

			static $secure_url = null;

			switch ( $name ) {

				case 'og:image:secure_url':
				case 'og:video:secure_url':

					if ( ! empty( $value ) ) {

						if ( SucomUtil::is_https( $value ) ) {	// Only HTTPS.
							$singles[] = array( '', $tag, $type, $name, $attr, $value, $cmt );
						}

						$name_url_suffix = str_replace( ':secure_url', ':url', $name );
						$name_no_suffix  = str_replace( ':secure_url', '', $name );

						$singles[] = array( '', $tag, $type, $name_url_suffix, $attr, $value, $cmt );
						$singles[] = array( '', $tag, $type, $name_no_suffix, $attr, $value, $cmt );
					}

					$secure_url = $value;

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log_arr( 'singles', $singles );
					}

					break;

				case 'og:image:url':
				case 'og:video:url':

					if ( $secure_url !== $value ) {	// Just in case.

						if ( ! empty( $this->p->options[ 'add_meta_property_og:image:secure_url' ] ) ) {

							$name_secure_suffix = str_replace( ':url', ':secure_url', $name );
							$value_secure_url   = set_url_scheme( $value, 'https' );	// Force https.
							$value              = set_url_scheme( $value, 'http' );		// Force HTTP.

							$singles[] = array( '', $tag, $type, $name_secure_suffix, $attr, $value_secure_url, $cmt );
						}

						$name_no_suffix = str_replace( ':url', '', $name );
	
						$singles[] = array( '', $tag, $type, $name, $attr, $value, $cmt );
						$singles[] = array( '', $tag, $type, $name_no_suffix, $attr, $value, $cmt );
					}

					$secure_url = null;

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log_arr( 'singles', $singles );
					}

					break;

				default:

					$singles[] = array( '', $tag, $type, $name, $attr, $value, $cmt );

					break;
			}


			/**
			 * $parts = array( $html, $tag, $type, $name, $attr, $value, $cmt );
			 */
			foreach ( $singles as $num => $parts ) {

				if ( ! isset( $parts[ 6 ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'parts array is incomplete (skipped)' );
						$this->p->debug->log_arr( '$parts', $parts );
					}

					continue;
				}

				/**
				 * Filtering of individual meta tags can be enabled by defining
				 * WPSSO_APPLY_FILTERS_SINGLE_MT as true.
				 */
				if ( SucomUtil::get_const( 'WPSSO_APPLY_FILTERS_SINGLE_MT' ) ) {
					$parts = $this->apply_filters_single_mt( $parts, $mod );
				}

				$log_prefix = $parts[ 1 ] . ' ' . $parts[ 2 ] . ' ' . $parts[ 3 ];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $log_prefix . ' = "' . $parts[ 5 ] . '"' );
				}

				if ( $parts[ 5 ] === '' || $parts[ 5 ] === null ) {	// Allow for 0.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_prefix . ' skipped: value is empty' );
					}

					$singles[ $num ][ 5 ] = '';	// Avoid null values in REST API output.

					continue;

				} elseif ( $parts[ 5 ] === WPSSO_UNDEF || $parts[ 5 ] === (string) WPSSO_UNDEF ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_prefix . ' skipped: value is ' . WPSSO_UNDEF );
					}

					continue;
				}

				/**
				 * Encode and escape all values, regardless if the head tag is enabled or not.
				 * If the head tag is enabled, HTML will be created and saved in $parts[0].
				 */
				if ( $parts[ 2 ] === 'itemprop' && strpos( $parts[ 3 ], '.' ) !== 0 ) {
					$match_name = preg_replace( '/^.*\./', '', $parts[ 3 ] );
				} else {
					$match_name = $parts[ 3 ];
				}

				/**
				 * Boolean values are converted to their string equivalent.
				 */
				if ( is_bool( $parts[ 5 ] ) ) {
					$parts[ 5 ] = $parts[ 5 ] ? 'true' : 'false';
				}

				switch ( $match_name ) {

					/**
					 * Description values that may include emoji.
					 */
					case 'og:title':
					case 'og:description':
					case 'og:image:alt':
					case 'twitter:title':
					case 'twitter:description':
					case 'twitter:image:alt':
					case 'description':
					case 'name':

						$parts[ 5 ] = SucomUtil::encode_html_emoji( $parts[ 5 ] );

						break;

					/**
					 * URL values that must be URL encoded.
					 */
					case 'og:secure_url':
					case 'og:url':
					case 'og:image:secure_url':
					case 'og:image:url':
					case 'og:image':
					case 'og:video:embed_url':
					case 'og:video:secure_url':
					case 'og:video:url':
					case 'og:video':
					case 'place:business:menu_url':
					case 'place:business:order_url':
					case 'twitter:image':
					case 'twitter:player':
					case 'canonical':
					case 'shortlink':
					case 'image':
					case 'hasmenu':	// Food establishment menu url.
					case 'url':

						$parts[ 5 ] = SucomUtil::esc_url_encode( $parts[ 5 ] );

						if ( $parts[ 2 ] === 'itemprop' ) {	// An itemprop URL must be a 'link'.
							$parts[ 1 ] = 'link';
							$parts[ 4 ] = 'href';
						}

						break;

					/**
					 * Allow for mobile app / non-standard protocols.
					 */
					case 'al:android:url':
					case 'al:ios:url':
					case 'al:web:url':
					case 'twitter:app:url:iphone':
					case 'twitter:app:url:ipad':
					case 'twitter:app:url:googleplay':

						$parts[ 5 ] = SucomUtil::esc_url_encode( $parts[ 5 ], $esc_url = false );

						break;

					/**
					 * Encode html entities for everything else.
					 */
					default:

						$parts[ 5 ] = htmlentities( $parts[ 5 ], ENT_QUOTES, $charset, false );	// $double_encode is false.

						break;
				}

				/**
				 * Convert mixed case itemprop names (for example) to lower case to determine the option key value.
				 */
				$opt_name = strtolower( 'add_' . $parts[ 1 ] . '_' . $parts[ 2 ] . '_' . $parts[ 3 ] );

				if ( ! empty( $this->p->options[$opt_name] ) ) {

					$parts[0] = ( empty( $parts[ 6 ] ) ? '' : '<!-- ' . $parts[ 6 ] . ' -->' ) . 
						'<' . $parts[ 1 ] . ' ' . $parts[ 2 ] . '="' . $match_name . '" ' . $parts[ 4 ] . '="' . $parts[ 5 ] . '"/>' . "\n";

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( $log_prefix . ' skipped: option is disabled' );
				}

				$singles[ $num ] = $parts;	// Save the HTML and encoded value.
			}

			return $singles;
		}

		/**
		 * Filtering of single meta tags can be enabled by defining WPSSO_APPLY_FILTERS_SINGLE_MT as true.
		 *
		 * $parts = array( $html, $tag, $type, $name, $attr, $value, $cmt );
		 */
		private function apply_filters_single_mt( array &$parts, array &$mod ) {

			$log_prefix  = $parts[ 1 ] . ' ' . $parts[ 2 ] . ' ' . $parts[ 3 ];
			$filter_name = $this->p->lca . '_' . $parts[ 1 ] . '_' . $parts[ 2 ] . '_' . $parts[ 3 ] . '_' . $parts[ 4 ];
			$new_value   = apply_filters( $filter_name, $parts[ 5 ], $parts[ 6 ], $mod );

			if ( $parts[ 5 ] !== $new_value ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $log_prefix . ' (original) = "' . $parts[ 5 ] . '"' );
				}

				if ( is_array( $new_value ) ) {

					foreach( $new_value as $key => $value ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_prefix . ' (filtered:' . $key . ') = "' . $value . '"' );
						}

						$parts[ 6 ] = $parts[ 3 ] . ':' . ( is_numeric( $key ) ? $key + 1 : $key );
						$parts[ 5 ] = $value;
					}

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_prefix . ' (filtered) = "' . $new_value . '"' );
					}

					$parts[ 5 ] = $new_value;
				}
			}

			return $parts;
		}
	}
}
