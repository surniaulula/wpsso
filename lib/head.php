<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoHead' ) ) {

	class WpssoHead {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			add_action( 'wp_head', array( $this, 'show_head' ), WPSSO_HEAD_PRIORITY );

			if ( ! empty( $this->p->avail[ 'amp' ][ 'any' ] ) ) {
				add_action( 'amp_post_template_head', array( $this, 'show_head' ), WPSSO_HEAD_PRIORITY );
			}
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
		 * Called by 'wp_head' and 'amp_post_template_head' actions.
		 */
		public function show_head() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( function_exists( 'current_action' ) ) {	// Since WP v3.9.
				$current  = current_action();
			} else {
				$current  = current_filter();
			}

			$use_post = apply_filters( $this->p->lca . '_use_post', false );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'required call to get_page_mod()' );
			}

			$mod = $this->p->util->get_page_mod( $use_post );	// Get post/user/term id, module name, and module object reference.

			$add_head_html = apply_filters( $this->p->lca . '_add_head_html', true, $mod );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = ' . get_option( 'home' ) );
				$this->p->debug->log( 'locale default = ' . SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = ' . SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = ' . SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( 'wp_query salt = ' . SucomUtil::get_query_salt() );
				$this->p->debug->log( 'add_head_html = ' . ( $add_head_html ? 'true' : 'false' ) );
				$this->p->util->log_is_functions();
			}

			if ( $add_head_html ) {
				echo $this->get_head_html( $use_post, $mod, $read_cache = true );
			} else {
				echo "\n" . '<!-- ' . $this->p->lca . ' head html is disabled -->' . "\n";
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
					case ( strpos( $mt_match, 'name-twitter:' ) === 0 ? true : false ):

						if ( ! isset( $head_info[ $mt[ 3 ] ] ) ) {	// Only save the first meta tag value.
							$head_info[ $mt[ 3 ] ] = $mt[ 5 ];
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
			 * Save the first image and video information found. Assumes array key order defined by
			 * SucomUtil::get_mt_image_seed() and SucomUtil::get_mt_video_seed().
			 */
			foreach ( array( 'og:image', 'og:video', 'p:image' ) as $mt_pre ) {

				if ( empty( $has_media[ $mt_pre ] ) ) {
					continue;
				}

				$is_first = false;

				foreach ( $head_mt as $mt ) {

					if ( ! isset( $mt[ 2 ] ) || ! isset( $mt[ 3 ] ) ) {
						continue;
					}

					if ( strpos( $mt[ 3 ], $mt_pre ) !== 0 ) {

						$is_first = false;

						/**
						 * If we already found media, then skip to the next media prefix.
						 */
						if ( ! empty( $head_info[ $mt_pre ] ) ) {
							continue 2;
						} else {
							continue;	// Skip meta tags without matching prefix.
						}
					}

					$mt_match = $mt[ 2 ] . '-' . $mt[ 3 ];

					switch ( $mt_match ) {

						case ( preg_match( '/^property-' . $mt_pre . '(:secure_url|:url)?$/', $mt_match, $m ) ? true : false ):

							if ( ! empty( $head_info[ $mt_pre ] ) ) {	// Only save the media URL once.
								continue 2;				// Get the next meta tag.
							}

							if ( ! empty( $mt[ 5 ] ) ) {

								$head_info[ $mt_pre ] = $mt[ 5 ];	// Save the media URL.

								$is_first = true;
							}

							break;

						case ( preg_match( '/^property-' . $mt_pre . ':(width|height|cropped|id|title|description)$/', $mt_match, $m ) ? true : false ):

							if ( true !== $is_first ) {	// Only save for first media found.
								continue 2;		// Get the next meta tag.
							}

							$head_info[ $mt[ 3 ] ] = $mt[ 5 ];

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

			$mt_mark = '';

			switch ( $type ) {

				case 'begin':
				case 'end':

					$add_meta_name = apply_filters( $this->p->lca . '_add_meta_name_' . $this->p->lca . ':mark',
						( empty( $this->p->options[ 'plugin_check_head' ] ) ? false : true ) );

					$html_comment = '<!-- ' . $this->p->lca . ' meta tags ' . $type . ' -->';

					$mt_name = $add_meta_name ? '<meta name="' . $this->p->lca . ':mark:' . $type . '" ' . 
						'content="' . $this->p->lca . ' meta tags ' . $type . '"/>' . "\n" : '';

					if ( $type === 'begin' ) {
						$mt_mark = $html_comment . "\n" . $mt_name;
					} else {
						$mt_mark = $mt_name . $html_comment . "\n";
					}

					break;

				case 'preg':

					/**
					 * Some HTML optimization plugins/services may remove the double-quotes from the name attribute, 
					 * along with the trailing space and slash characters, so make these optional in the regex.
					 */
					$prefix = '<(!--[\s\n\r]+|meta[\s\n\r]+name="?' . $this->p->lca . ':mark:(begin|end)"?[\s\n\r]+content=")';
					$suffix = '([\s\n\r]+--|"[\s\n\r]*\/?)>';

					$mt_mark = '/' . $prefix . $this->p->lca . ' meta tags begin' . $suffix . '.*' . 
						$prefix . $this->p->lca . ' meta tags end' . $suffix . '/ums';	// Enable utf8 support.

					break;
			}

			return $mt_mark;
		}

		public function get_head_html( $use_post = false, $mod = false, $read_cache = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mtime_start   = microtime( true );
			$indent_num    = 0;
			$home_url      = SucomUtilWP::raw_home_url();
			$info          = $this->p->cf[ 'plugin' ][ $this->p->lca ];
			$short_version = $info[ 'short' ] . ' v' . $info[ 'version' ];

			/**
			 * The $mod array argument is preferred but not required.
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->p->util->get_page_mod( $use_post );
			}

			$html = "\n\n" . '<!-- social and search optimization by ' . $short_version . ' - https://wpsso.com/ -->' . "\n";

			$html .= $this->get_mt_mark( 'begin' );

			foreach ( $this->get_head_array( $use_post, $mod, $read_cache ) as $mt ) {

				if ( ! empty( $mt[0] ) ) {

					if ( $indent_num && strpos( $mt[0], '</noscript' ) === 0 ) {
						$indent_num = 0;
					}

					$html .= str_repeat( "\t", (int) $indent_num ) . $mt[0];

					/**
					 * Indent meta tags within a noscript container.
					 */
					if ( strpos( $mt[0], '<noscript' ) === 0 ) {
						$indent_num = 1;
					}
				}
			}

			$html .= $this->get_mt_mark( 'end' );

			$mtime_total = microtime( true ) - $mtime_start;
			$total_secs  = sprintf( '%f secs', $mtime_total );

			$html .= '<!-- added on ' . date( 'c' ) . ' in ' . $total_secs . ' from ' . $home_url . ' -->' . "\n\n";

			return $html;
		}

		/**
		 * $read_cache is false when called by the post/term/user load_meta_page() method.
		 */
		public function get_head_array( $use_post = false, $mod = false, $read_cache = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'build head array' );	// Begin timer.
			}

			/**
			 * The $mod array argument is preferred but not required.
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

			$is_admin       = is_admin();	// Call the function only once.
			$cache_md5_pre  = $this->p->lca . '_h_';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre );
			$cache_salt     = __METHOD__ . '(' . SucomUtil::get_mod_salt( $mod, $sharing_url ) . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );
			$cache_index    = $this->get_head_cache_index( $mod, $sharing_url );	// Includes locale, url, etc.
			$cache_array    = array();

			if ( is_404() || is_search() ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'setting cache expiration to 0 seconds for 404 or search page' );
				}

				$cache_exp_secs = 0;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'sharing url = ' . $sharing_url );
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
								$this->p->debug->log( 'cache index found in transient cache' );
								$this->p->debug->mark( 'build head array' );	// End timer.
							}

							return $cache_array[ $cache_index ];	// Stop here.

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
				}

			} else {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'head array transient cache is disabled' );
				}

				if ( SucomUtil::delete_transient_array( $cache_id ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'deleted transient cache id ' . $cache_id );
					}
				}
			}

			/**
			 * Set a general reference value for admin notices.
			 */
			$this->p->util->maybe_set_ref( $sharing_url, $mod );

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
			$this->p->util->maybe_set_ref( $sharing_url, $mod, __( 'adding open graph meta tags', 'wpsso' ) );

			$mt_og = $this->p->og->get_array( $mod );

			$this->p->util->maybe_unset_ref( $sharing_url );

			/**
			 * Twitter Cards.
			 */
			$this->p->util->maybe_set_ref( $sharing_url, $mod, __( 'adding twitter card meta tags', 'wpsso' ) );

			$mt_tc = $this->p->tc->get_array( $mod, $mt_og );

			$this->p->util->maybe_unset_ref( $sharing_url );

			/**
			 * Name / SEO meta tags.
			 */
			$this->p->util->maybe_set_ref( $sharing_url, $mod, __( 'adding meta name meta tags', 'wpsso' ) );

			$mt_name = $this->p->meta_name->get_array( $mod, $mt_og, $author_id );

			$this->p->util->maybe_unset_ref( $sharing_url );

			/**
			 * Link relation tags.
			 */
			$this->p->util->maybe_set_ref( $sharing_url, $mod, __( 'adding link relation tags', 'wpsso' ) );

			$link_rel = $this->p->link_rel->get_array( $mod, $mt_og, $author_id, $sharing_url );

			$this->p->util->maybe_unset_ref( $sharing_url );

			if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'schema markup is disabled' );
				}

				$mt_item = array();

				$schema_scripts = array();

			} else {

				/**
				 * Schema itemprop meta tags.
				 */
				$this->p->util->maybe_set_ref( $sharing_url, $mod, __( 'adding schema meta tags', 'wpsso' ) );

				$mt_item = $this->p->meta_item->get_array( $mod, $mt_og );

				$this->p->util->maybe_unset_ref( $sharing_url );

				/**
				 * Schema json scripts.
				 */
				$this->p->util->maybe_set_ref( $sharing_url, $mod, __( 'adding schema json-ld markup', 'wpsso' ) );

				$schema_scripts = $this->p->schema->get_array( $mod, $mt_og );

				$this->p->util->maybe_unset_ref( $sharing_url );
			}

			/**
			 * Generator meta tags.
			 */
			$mt_gen = array( 'generator' => $this->p->check->get_ext_gen_list() );

			/**
			 * Combine and return all meta tags.
			 */
			$mt_og = $this->p->og->sanitize_array( $mod, $mt_og );	// Unset mis-matched og_type meta tags.

			$cache_array[ $cache_index ] = array_merge(
				$this->get_mt_array( 'meta', 'name', $mt_gen, $mod ),
				$this->get_mt_array( 'link', 'rel', $link_rel, $mod ),
				$this->get_mt_array( 'meta', 'property', $mt_og, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_tc, $mod ),
				$this->get_mt_array( 'meta', 'itemprop', $mt_item, $mod ),
				$this->get_mt_array( 'meta', 'name', $mt_name, $mod ),	// SEO description is last.
				$schema_scripts
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
			$this->p->util->maybe_unset_ref( $sharing_url );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'build head array' );	// End timer.
			}

			return $cache_array[ $cache_index ];
		}

		/**
		 * Loops through the arrays and calls get_single_mt() for each.
		 */
		private function get_mt_array( $tag, $type, array $mt_array, array $mod ) {

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
								 * og:video:has_image will be false if there is no preview image,
								 * or the preview image is a duplicate.
								 */
								if ( empty( $dd_val[ 'og:video:has_image' ] ) ) {
									$use_video_image = false;
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

		public function get_single_mt( $tag, $type, $name, $value, $cmt, array $mod ) {

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

			static $last_secure_url = null;
			static $last_url        = null;

			switch ( $name ) {

				case 'og:image:secure_url':
				case 'og:video:secure_url':

					/**
					 * The secure url meta tag should always come first, but just in case it doesn't, make sure
					 * the url value has not already been added.
					 */
					if ( $last_url !== $value ) {	// Just in case.

						if ( $value ) {

							$name_secure_url = $name;
							$name_url        = str_replace( ':secure_url', ':url', $name );
							$name_no_url     = str_replace( ':secure_url', '', $name );

							if ( SucomUtil::is_https( $value ) ) {
								$singles[] = array( '', $tag, $type, $name_secure_url, $attr, $value, $cmt );
							}

							$singles[] = array( '', $tag, $type, $name_url, $attr, $value, $cmt );
							$singles[] = array( '', $tag, $type, $name_no_url, $attr, $value, $cmt );

							$last_secure_url = $value;

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log_arr( 'singles', $singles );
							}
						}
					}

					$last_url = null;

					break;

				case 'og:image:url':
				case 'og:video:url':

					/**
					 * The previous switch block would have set all three meta tags already, so only proceed if
					 * the last secure url (empty or not) is not equal to the current url value (empty or not).
					 */
					if ( $last_secure_url !== $value ) {	// Just in case.

						if ( $value ) {

							$name_secure_url = str_replace( ':url', ':secure_url', $name );
							$name_url        = $name;
							$name_no_url     = str_replace( ':url', '', $name );

							if ( SucomUtil::is_https( $value ) ) {
								$singles[] = array( '', $tag, $type, $name_secure_url, $attr, $value, $cmt );
							}

							$singles[] = array( '', $tag, $type, $name_url, $attr, $value, $cmt );
							$singles[] = array( '', $tag, $type, $name_no_url, $attr, $value, $cmt );

							$last_url = $value;

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log_arr( 'singles', $singles );
							}
						}
					}

					$last_secure_url = null;

					break;

				case 'og:image':
				case 'og:video':

					/**
					 * The previous two switch blocks would have set all three meta tags aready, so only
					 * proceed if we have a value, and it does not match the last secure url (empty or not) or
					 * the last insecure url (empty or not).
					 */
					if ( empty( $value ) || $value === $last_secure_url || $value === $last_url ) {
						break;
					}

					// No break.

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

						$parts[ 5 ] = htmlentities( $parts[ 5 ], ENT_QUOTES, $charset, $double_encode = false );

						break;
				}

				/**
				 * Convert mixed case itemprop names (for example) to lower case to determine the option key value.
				 */
				$opt_name = strtolower( 'add_' . $parts[ 1 ] . '_' . $parts[ 2 ] . '_' . $parts[ 3 ] );

				if ( ! empty( $this->p->options[ $opt_name ] ) ) {

					$parts[0] = ( empty( $parts[ 6 ] ) ? '' : '<!-- ' . $parts[ 6 ] . ' -->' ) . 
						'<' . $parts[ 1 ] . ' ' . $parts[ 2 ] . '="' . $match_name . '" ' . $parts[ 4 ] . '="' . $parts[ 5 ] . '"/>' . "\n";

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( $log_prefix . ' skipped: option is disabled' );
				}

				$singles[ $num ] = $parts;	// Save the HTML and encoded value.
			}

			return $singles;
		}
	}
}
