<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
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

			/**
			 * Hook the first available filter name (example: 'language_attributes').
			 */
			foreach ( array( 'plugin_html_attr_filter', 'plugin_head_attr_filter' ) as $opt_prefix ) {

				if ( ! empty( $this->p->options[$opt_prefix . '_name'] ) && $this->p->options[$opt_prefix . '_name'] !== 'none' ) {

					$wp_filter_name = $this->p->options[$opt_prefix . '_name'];

					add_filter( $wp_filter_name, array( $this, 'add_ogpns_attributes' ),
						 ( isset( $this->p->options[$opt_prefix . '_prio'] ) ?
						 	(int) $this->p->options[$opt_prefix . '_prio'] : 100 ), 1 );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'added add_ogpns_attributes filter for ' . $wp_filter_name );
					}

					break;	// Stop here.

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipping add_ogpns_attributes for ' . $opt_prefix . ' - filter name is empty or disabled' );
				}
			}
		}

		public function filter_plugin_image_sizes( $sizes ) {

			$sizes['og_img'] = array( 		// options prefix
				'name'  => 'opengraph',		// wpsso-opengraph
				'label' => _x( 'Facebook / Open Graph', 'image size label', 'wpsso' ),
			);

			return $sizes;
		}

		public function add_ogpns_attributes( $html_attr ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array (
					'html_attr' => $html_attr,
				) );
			}

			$use_post = apply_filters( $this->p->lca . '_use_post', false );	// Used by woocommerce with is_shop().

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'required call to get_page_mod()' );
			}

			$mod = $this->p->util->get_page_mod( $use_post );

			$type_id = $this->get_mod_og_type( $mod );

			$og_ns = array(
				'og' => 'http://ogp.me/ns#',
				'fb' => 'http://ogp.me/ns/fb#',
			);

			/**
			 * Check that the og_type is known and add it's namespace value.
			 *
			 * Example: article, place, product, website, etc.
			 */
			if ( ! empty( $this->p->cf['head']['og_type_ns'][$type_id] ) ) {
				$og_ns[$type_id] = $this->p->cf['head']['og_type_ns'][$type_id];
			}

			$og_ns = apply_filters( $this->p->lca . '_og_ns', $og_ns, $mod );

			if ( SucomUtil::is_amp() ) {

				/**
				 * Nothing to do.
				 */

			} else {

				$html_attr = ' ' . $html_attr;	// Prepare the string for testing.

				/**
				 * Find and remove an existing prefix attribute value.
				 */
				if ( strpos( $html_attr, 'prefix=' ) ) {
					
					/**
				 	 * s = A dot metacharacter in the pattern matches all characters, including newlines.
					 *
					 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
					 */
					if ( preg_match( '/^(.*)\sprefix=["\']([^"\']*)["\'](.*)$/s', $html_attr, $match ) ) {
						$html_attr    = $match[1] . $match[3];	// Remove the prefix.
					}
				}

				$prefix_value = '';
	
				foreach ( $og_ns as $name => $url ) {
					if ( strpos( $prefix_value, ' ' . $name . ': ' . $url ) === false ) {
						$prefix_value .= ' ' . $name . ': ' . $url;
					}
				}
	
				$html_attr .= ' prefix="' . trim( $prefix_value ) . '"';
			}

			return trim( $html_attr );
		}

		/**
		 * Returns the open graph type id or namespace value.
		 *
		 * Example: article, product, place, etc.
		 */
		public function get_mod_og_type( array $mod, $get_type_ns = false, $use_mod_opts = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			static $local_cache = array();

			/**
			 * Optimize and cache post/term/user og type values.
			 */
			if ( ! empty( $mod[ 'name' ] ) && ! empty( $mod[ 'id' ] ) ) {

				if ( isset( $local_cache[$mod[ 'name' ]][$mod[ 'id' ]][$get_type_ns][$use_mod_opts] ) ) {

					$value =& $local_cache[$mod[ 'name' ]][$mod[ 'id' ]][$get_type_ns][$use_mod_opts];

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'returning local cache value "' . $value . '"' );
					}

					return $value;

				} elseif ( is_object( $mod[ 'obj' ] ) && $use_mod_opts ) {	// Check for a column og_type value in wp_cache.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking for value from column wp_cache' );
					}

					$value = $mod[ 'obj' ]->get_column_wp_cache( $mod, $this->p->lca . '_og_type' );	// Returns empty string if no value found.

					if ( ! empty( $value ) ) {

						if ( $get_type_ns && $value !== 'none' ) {	// Return the og type namespace instead.

							$og_type_ns  = $this->p->cf['head']['og_type_ns'];

							if ( ! empty( $og_type_ns[$value] ) ) {

								$value = $og_type_ns[$value];

							} else {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'columns wp_cache value "' . $value . '" not in og type ns' );
								}

								$value = '';
							}
						}

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'returning column wp_cache value "' . $value . '"' );
						}

						return $local_cache[$mod[ 'name' ]][$mod[ 'id' ]][$get_type_ns][$use_mod_opts] = $value;
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'no value found in local cache or column wp_cache' );
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipping cache check: mod name and/or id value is empty' );
			}

			$default_key = apply_filters( $this->p->lca . '_og_type_for_default', 'website', $mod );
			$og_type_ns  = $this->p->cf['head']['og_type_ns'];
			$type_id     = null;

			/**
			 * Get custom open graph type from post, term, or user meta.
			 */
			if ( $use_mod_opts ) {

				if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

					$type_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'og_type' );	// Returns null if index key not found.

					if ( empty( $type_id ) ) {	// Must be a non-empty string.

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'custom type id from meta is empty' );
						}

					} elseif ( $type_id === 'none' ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'custom type id is disabled with value none' );
						}

					} elseif ( empty( $og_type_ns[ $type_id ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'custom type id "' . $type_id . '" not in og types' );
						}

						$type_id = null;

					} elseif ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'custom type id "' . $type_id . '" from ' . $mod[ 'name' ] . ' meta' );
					}

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipping custom type id - mod object is empty' );
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipping custom type id - use_mod_opts is false' );
			}

			if ( empty( $type_id ) ) {
				$is_custom = false;
			} else {
				$is_custom = true;
			}

			if ( empty( $type_id ) ) {	// If no custom of type, then use the default settings.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'using plugin settings to determine og type' );
				}

				if ( $mod['is_home'] ) {	// Static or index page.
	
					$type_id = $default_key;
	
					if ( $mod['is_home_page'] ) {
	
						$type_id = apply_filters( $this->p->lca . '_og_type_for_home_page',
							$this->get_og_type_id_for_name( 'home_page' ), $mod );
	
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'using og type id "' . $type_id . '" for home page' );
						}
	
					} else {
	
						$type_id = apply_filters( $this->p->lca . '_og_type_for_home_index',
							$this->get_og_type_id_for_name( 'home_index' ), $mod );
	
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'using og type id "' . $type_id . '" for home index' );
						}
					}
	
				} elseif ( $mod[ 'is_post' ] ) {
	
					if ( ! empty( $mod[ 'post_type' ] ) ) {
	
						if ( $mod[ 'is_post_type_archive' ] ) {
	
							$type_id = apply_filters( $this->p->lca . '_og_type_for_post_type_archive_page',
								$this->get_og_type_id_for_name( 'post_archive' ), $mod );

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'using og type id "' . $type_id . '" for post_type_archive page' );
							}

						} elseif ( isset( $this->p->options['og_type_for_' . $mod[ 'post_type' ]] ) ) {
	
							$type_id = $this->get_og_type_id_for_name( $mod[ 'post_type' ] );

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'using og type id "' . $type_id . '" from post type option value' );
							}

						} elseif ( ! empty( $og_type_ns[ $mod[ 'post_type' ] ] ) ) {

							$type_id = $mod[ 'post_type' ];

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'using og type id "' . $type_id . '" from post type name' );
							}

						} else {	// Unknown post type.

							$type_id = apply_filters( $this->p->lca . '_og_type_for_post_type_unknown_type', 
								$this->get_og_type_id_for_name( 'page' ), $mod );

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'using "page" og type for unknown post type ' . $mod[ 'post_type' ] );
							}
						}

					} else {	// Post objects without a post_type property.

						$type_id = apply_filters( $this->p->lca . '_og_type_for_post_type_empty_type', 
							$this->get_og_type_id_for_name( 'page' ), $mod );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'using "page" og type for empty post type' );
						}
					}
	
				} elseif ( $mod[ 'is_term' ] ) {

					if ( ! empty( $mod[ 'tax_slug' ] ) ) {

						$type_id = $this->get_og_type_id_for_name( 'tax_' . $mod[ 'tax_slug' ] );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'using og type id "' . $type_id . '" from term taxonomy option value' );
						}
					}

					if ( empty( $type_id ) ) {	// Just in case.
						$type_id = $this->get_og_type_id_for_name( 'archive_page' );
					}

				} elseif ( $mod[ 'is_user' ] ) {

					$type_id = $this->get_og_type_id_for_name( 'user_page' );

				} elseif ( SucomUtil::is_archive_page() ) {	// Just in case.

					$type_id = $this->get_og_type_id_for_name( 'archive_page' );

				} elseif ( is_search() ) {

					$type_id = $this->get_og_type_id_for_name( 'search_page' );

				} else {	// Everything else.

					$type_id = $default_key;

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'using default og type id "' . $default_key . '"' );
					}
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og type id before filter is "' . $type_id . '"' );
			}

			$type_id = apply_filters( $this->p->lca . '_og_type', $type_id, $mod, $is_custom );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og type id after filter is "' . $type_id . '"' );
			}

			$get_value = false;

			if ( empty( $type_id ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning false: og type id is empty' );
				}

			} elseif ( $type_id === 'none' ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning false: og type id is disabled' );
				}

			} elseif ( ! isset( $og_type_ns[ $type_id ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning false: og type id "' . $type_id . '" is unknown' );
				}

			} elseif ( $get_type_ns ) {	// False by default.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning og type namespace "' . $og_type_ns[$type_id] . '"' );
				}

				$get_value = $og_type_ns[$type_id];

			} else {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning og type id "' . $type_id . '"' );
				}

				$get_value = $type_id;
			}

			/**
			 * Optimize and cache post/term/user og type values.
			 */
			if ( ! empty( $mod[ 'name' ] ) && ! empty( $mod[ 'id' ] ) ) {
				$local_cache[$mod[ 'name' ]][$mod[ 'id' ]][$get_type_ns][$use_mod_opts] = $get_value;
			}

			return $get_value;
		}

		public function get_array( array $mod, array $mt_og, $crawler_name = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( false === $crawler_name ) {
				$crawler_name = SucomUtil::get_crawler_name();
			}

			$has_pdir    = $this->p->avail[ '*' ][ 'p_dir' ];
			$has_pp      = $this->p->check->pp( $this->p->lca, true, $has_pdir );
			$max_nums    = $this->p->util->get_max_nums( $mod );
			$post_id     = $mod[ 'is_post' ] ? $mod[ 'id' ] : false;
			$check_dupes = true;
			$prev_count  = 0;
			$mt_og       = apply_filters( $this->p->lca . '_og_seed', $mt_og, $mod );

			if ( ! empty( $mt_og ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $this->p->lca . '_og_seed filter returned:' );
					$this->p->debug->log( $mt_og );
				}
			}

			/**
			 * Facebook admins meta tag.
			 */
			if ( ! isset( $mt_og['fb:admins'] ) ) {
				if ( ! empty( $this->p->options['fb_admins'] ) ) {
					foreach ( explode( ',', $this->p->options['fb_admins'] ) as $fb_admin ) {
						$mt_og['fb:admins'][] = trim( $fb_admin );
					}
				}
			}

			/**
			 * Facebook app id meta tag.
			 */
			if ( ! isset( $mt_og['fb:app_id'] ) ) {
				$mt_og['fb:app_id'] = $this->p->options['fb_app_id'];
			}

			/**
			 * Type id meta tag.
			 */
			if ( ! isset( $mt_og['og:type'] ) ) {
				$mt_og['og:type'] = $this->get_mod_og_type( $mod );
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:type already defined = ' . $mt_og['og:type'] );
			}

			/**
			 * URL meta tag.
			 */
			if ( ! isset( $mt_og['og:url'] ) ) {
				$mt_og['og:url'] = $this->p->util->get_sharing_url( $mod );
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:url already defined = ' . $mt_og['og:url'] );
			}

			/**
			 * Locale meta tag.
			 */
			if ( ! isset( $mt_og['og:locale'] ) ) {
				$mt_og['og:locale'] = $this->get_fb_locale( $this->p->options, $mod );
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:locale already defined = ' . $mt_og['og:locale'] );
			}

			/**
			 * Site name meta tag.
			 */
			if ( ! isset( $mt_og['og:site_name'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting site name for og:site_name meta tag' );
				}
				$mt_og['og:site_name'] = SucomUtil::get_site_name( $this->p->options, $mod );	// localized
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:site_name already defined = ' . $mt_og['og:site_name'] );
			}

			/**
			 * Title meta tag.
			 */
			if ( ! isset( $mt_og['og:title'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting title for og:title meta tag' );
				}

				$mt_og['og:title'] = $this->p->page->get_title( $this->p->options['og_title_max_len'], '...', $mod );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og:title value = ' . $mt_og['og:title'] );
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:title already defined = ' . $mt_og['og:title'] );
			}

			/**
			 * Description meta tag.
			 */
			if ( ! isset( $mt_og['og:description'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting description for og:description meta tag' );
				}

				$mt_og['og:description'] = $this->p->page->get_description( $this->p->options['og_desc_max_len'], '...', $mod,
					$read_cache = true, $this->p->options['og_desc_hashtags'] );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og:description value = ' . $mt_og['og:description'] );
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:description already defined = ' . $mt_og['og:description'] );
			}

			/**
			 * Updated date / time meta tag.
			 */
			if ( ! isset( $mt_og['og:updated_time'] ) ) {
				if ( $mod[ 'is_post' ] && $post_id ) {
					$mt_og['og:updated_time'] = trim( get_post_modified_time( 'c', true, $post_id ) );	// $gmt is true.
				}
			}

			/**
			 * Get all videos.
			 *
			 * Call before getting all images to find / use preview images.
			 */
			if ( ! isset( $mt_og['og:video'] ) && $has_pp ) {

				if ( empty( $max_nums['og_vid_max'] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'videos disabled: maximum videos = 0' );
					}

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'getting videos for og:video meta tag' );
					}

					$mt_og[ 'og:video' ] = $this->get_all_videos( $max_nums['og_vid_max'], $mod, $check_dupes, 'og' );

					if ( empty( $mt_og['og:video'] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'og:video is empty - unsetting og:video meta tag' );
						}

						unset( $mt_og[ 'og:video' ] );

					} elseif ( is_array( $mt_og[ 'og:video' ] ) ) {	// Just in case.

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'checking for video preview images' );
						}

						foreach ( $mt_og['og:video'] as $num => $og_single_video ) {

							$image_url = SucomUtil::get_mt_media_url( $og_single_video, $mt_media_pre = 'og:image' );

							/**
							 * Check preview images for duplicates since the same videos may be available in
							 * different formats (application/x-shockwave-flash and text/html for example).
							 */
							if ( $image_url && $this->p->util->is_uniq_url( $image_url, 'preview' ) ) {

								$mt_og[ 'og:video' ][ $num ]['og:video:has_image'] = true;

								$prev_count++;

							} else {
								$mt_og[ 'og:video' ][ $num ][ 'og:video:has_image' ] = false;
							}
						}

						if ( $prev_count > 0 ) {

							$max_nums['og_img_max'] -= $prev_count;

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $prev_count . ' video preview images found ' . 
									'(og_img_max adjusted to ' . $max_nums['og_img_max'] . ')' );
							}

						} elseif ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'no video preview images found' );
						}
					}
				}
			}

			/**
			 * Get all images.
			 */
			if ( ! isset( $mt_og['og:image'] ) ) {

				if ( empty( $max_nums['og_img_max'] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'images disabled: maximum images = 0' );
					}

				} else {

					$img_sizes = array( 'og' => $this->p->lca . '-opengraph' );

					foreach ( $img_sizes as $md_pre => $size_name ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting images for ' . $md_pre . ' (' . $size_name . ')' );
						}

						/**
						 * The size_name is used as a context for duplicate checks.
						 */
						$mt_og[ $md_pre . ':image' ] = $this->get_all_images( $max_nums['og_img_max'], $size_name, $mod, $check_dupes, $md_pre );

						/**
						 * If there's no image, and no video preview, then add the default image for singular (aka post) webpages.
						 */
						if ( empty( $mt_og[$md_pre . ':image'] ) && ! $prev_count && $mod[ 'is_post' ] ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'getting default image for ' . $md_pre . ' (' . $size_name . ')' );
							}

							$mt_og[$md_pre . ':image'] = $this->p->media->get_default_images( $max_nums['og_img_max'], $size_name, $check_dupes );
						}
					}
				}
			}

			/**
			 * Pre-define some basic open graph meta tags for this og:type. If the meta tag
			 * has an associated meta option name, then read it's value from the meta options.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'checking og_type_mt array for known meta tags and md options' );
			}

			$type_id = $mt_og['og:type'];

			if ( isset( $this->p->cf['head'][ 'og_type_mt' ][$type_id] ) ) {	// Check if og:type is in config.

				$og_type_mt_md = $this->p->cf['head'][ 'og_type_mt' ][$type_id];

				/**
				 * Optimize and call get_options() only once. Returns an empty string if no meta found.
				 */
				$md_opts = empty( $mod[ 'obj' ] ) ? array() : (array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				foreach ( $og_type_mt_md as $mt_name => $md_key ) {

					/**
					 * Use a custom value if one is available - ignore empty strings and 'none'.
					 */
					if ( $md_key && isset( $md_opts[$md_key] ) && $md_opts[$md_key] !== '' ) {

						if ( $md_opts[$md_key] === 'none' ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $md_key . ' option is "none" - unsetting ' . $mt_name . ' meta tag' );
							}

							unset( $mt_og[$mt_name] );

						} else {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $type_id . ' meta tag ' . $mt_name . ' from option = ' . $md_opts[$md_key] );
							}

							$mt_og[$mt_name] = $md_opts[$md_key];
						}

					} elseif ( isset( $mt_og[$mt_name] ) ) {	// if the meta tag has not already been set

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $type_id . ' meta tag ' . $mt_name . ' value kept = ' . $mt_og[$mt_name] );
						}

					} else {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $type_id . ' meta tag ' . $mt_name . ' defined as null' );
						}

						$mt_og[$mt_name] = null;	// use null so isset() returns false
					}
				}

				/**
				 * Include variations (aka product offers) if available.
				 */
				if ( ! empty( $mt_og['product:offers'] ) && is_array( $mt_og['product:offers'] ) ) {

					foreach ( $mt_og['product:offers'] as $num => $offer ) {

						foreach( $offer as $mt_name => $mt_value ) {

							if ( isset( $this->p->cf['head']['og_type_array']['product'][$mt_name] ) ) {

								$mt_og['product'][ $num ][$mt_name] = $mt_value;

								if ( isset( $mt_og[$mt_name] ) ) {
									unset ( $mt_og[$mt_name] );
								}
							}
						}
					}
				
				} elseif ( isset( $mt_og['product:price:amount'] ) ) {

					if ( is_numeric( $mt_og['product:price:amount'] ) ) {	// Allow for price of 0.

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

			/**
			 * If the module is a post object, define the author, publishing date, etc.
			 * These values may still be used by other filters, and if the og:type is
			 * not an article, the meta tags will be sanitized at the end of
			 * WpssoHead::get_head_array().
			 */
			if ( $mod[ 'is_post' ] && $post_id ) {

				if ( ! isset( $mt_og['article:author'] ) ) {

					if ( $mod[ 'is_post' ] && isset( $this->p->m[ 'util' ][ 'user' ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting names / urls for article:author meta tags' );
						}

						$user_mod =& $this->p->m[ 'util' ][ 'user' ];

						if ( $mod[ 'post_author' ] ) {

							$mt_og['article:author'] = $user_mod->get_og_profile_urls( $mod[ 'post_author' ], $crawler_name );

							$mt_og['article:author:name'] = $user_mod->get_author_meta( $mod[ 'post_author' ], $this->p->options['seo_author_name'] );

						} else {
							$mt_og['article:author'] = array();
						}

						if ( ! empty( $mod['post_coauthors'] ) ) {

							$og_profile_urls = $user_mod->get_og_profile_urls( $mod['post_coauthors'], $crawler_name );

							$mt_og['article:author'] = array_merge( $mt_og['article:author'], $og_profile_urls );
						}
					}
				}

				if ( ! isset( $mt_og['article:publisher'] ) ) {
					$mt_og['article:publisher'] = SucomUtil::get_key_value( 'fb_publisher_url', $this->p->options, $mod );
				}

				if ( ! isset( $mt_og['article:tag'] ) ) {
					$mt_og['article:tag'] = $this->p->page->get_tag_names( $mod );
				}

				if ( ! isset( $mt_og['article:section'] ) ) {
					$mt_og['article:section'] = $this->p->page->get_article_section( $post_id );
				}

				if ( ! isset( $mt_og['article:published_time'] ) ) {
					if ( $mod[ 'post_status' ] === 'publish' ) {	// Must be published to have publish time.
						$mt_og['article:published_time'] = trim( get_post_time( 'c', $gmt = true, $post_id ) );
					}
				}

				if ( ! isset( $mt_og['article:modified_time'] ) ) {
					$mt_og['article:modified_time'] = trim( get_post_modified_time( 'c', $gmt = true, $post_id ) );
				}

				/**
				 * Unset optional meta tags if empty.
				 */
				foreach ( array( 'article:modified_time', 'article:expiration_time' ) as $optional_mt_name ) {
					if ( empty( $mt_og[ $optional_mt_name ] ) ) {
						unset( $mt_og[ $optional_mt_name ] );
					}
				}
			}

			return (array) apply_filters( $this->p->lca . '_og', $mt_og, $mod );
		}

		public function get_og_type_id_for_name( $type_name, $default_id = null ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'type_name'  => $type_name,
					'default_id' => $default_id,
				) );
			}

			if ( empty( $type_name ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: og type name is empty' );
				}
				return $default_id;	// Just in case.
			}

			$og_type_ns = $this->p->cf['head']['og_type_ns'];

			$type_id = isset( $this->p->options['og_type_for_' . $type_name] ) ?	// Just in case.
				$this->p->options['og_type_for_' . $type_name] : $default_id;

			if ( empty( $type_id ) || $type_id === 'none' ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og type id for ' . $type_name . ' is empty or disabled' );
				}

				$type_id = $default_id;

			} elseif ( empty( $og_type_ns[$type_id] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og type id "' . $type_id . '" for ' . $type_name . ' not in og type ns' );
				}

				$type_id = $default_id;

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og type id for ' . $type_name . ' is ' . $type_id );
			}

			return $type_id;
		}

		public function get_og_types_select( $add_none = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Use only supported (aka compat) Open Graph types.
			 */
			$og_type_ns = $this->p->cf['head']['og_type_ns_compat'];

			$select = array();

			foreach ( $og_type_ns as $type_id => $type_ns ) {

				$type_ns = preg_replace( '/(^.*\/\/|#$)/', '', $type_ns );

				$select[ $type_id ] = $type_id . ' | ' . $type_ns;
			}

			if ( defined( 'SORT_STRING' ) ) {	// Just in case.
				asort( $select, SORT_STRING );
			} else {
				asort( $select );
			}

			if ( $add_none ) {
				return array_merge( array( 'none' => '[None]' ), $select );
			} else {
				return $select;
			}
		}

		public function get_all_videos( $num = 0, array $mod, $check_dupes = true, $md_pre = 'og', $force_prev = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'num'         => $num,
					'mod'         => $mod,
					'check_dupes' => $check_dupes,
					'md_pre'      => $md_pre,
					'force_prev'  => $force_prev,
				) );
			}

			$og_ret   = array();
			$has_pdir = $this->p->avail[ '*' ][ 'p_dir' ];
			$has_pp   = $this->p->check->pp( $this->p->lca, true, $has_pdir );
			$use_prev = $this->p->options['og_vid_prev_img'];		// default option value is true/false
			$num_diff = SucomUtil::count_diff( $og_ret, $num );

			$this->p->util->clear_uniq_urls( array( 'video', 'content_video', 'video_details' ) );

			/**
			 * Get video information and preview enable/disable option from the post/term/user meta.
			 */
			if ( $has_pp && ! empty( $mod[ 'obj' ] ) ) {

				/**
				 * Note that get_options() returns null if an index key is not found.
				 */
				if ( ( $mod_prev = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'og_vid_prev_img' ) ) !== null ) {

					$use_prev = $mod_prev;	// use true/false/1/0 value from the custom option

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting use_prev to '.( empty( $use_prev ) ? 'false' : 'true' ).' from meta data' );
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'checking for custom videos in ' . $mod[ 'name' ] . ' options' );
				}

				$og_ret = array_merge( $og_ret, $mod[ 'obj' ]->get_og_videos( $num_diff, $mod[ 'id' ], $check_dupes, $md_pre ) );
			}

			$num_diff = SucomUtil::count_diff( $og_ret, $num );

			/**
			 * Optionally get more videos from the post content.
			 */
			if ( $mod[ 'is_post' ] && ! $this->p->util->is_maxed( $og_ret, $num ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'checking for additional videos in the post content' );
				}

				$og_ret = array_merge( $og_ret, $this->p->media->get_content_videos( $num_diff, $mod, $check_dupes ) );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( count( $og_ret ) . ' videos found in the post content' );
				}
			}

			$this->p->util->slice_max( $og_ret, $num );

			/**
			 * Optionally remove the image meta tags (aka video preview).
			 */
			if ( empty( $use_prev ) && empty( $force_prev ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'use_prev and force_prev are false - removing video preview images' );
				}

				foreach ( $og_ret as $num => $og_single_video ) {

					foreach ( SucomUtil::preg_grep_keys( '/^og:image(:.*)?$/', $og_single_video ) as $k => $v ) {
						unset ( $og_ret[$num][$k] );
					}

					$og_ret[$num]['og:video:has_image'] = false;
				}
			}

			/**
			 * Get custom video information from post/term/user meta data for FIRST video.
			 *
			 * If $md_pre is 'none' (special index keyword), then don't load any custom video information.
			 * The og:video:title and og:video:description meta tags are not standard and their values will
			 * only appear in Schema markup.
			 */
			if ( $has_pp && ! empty( $mod[ 'obj' ] ) && $md_pre !== 'none' ) {

				foreach ( $og_ret as $num => $og_single_video ) {

					foreach ( array(
						'og_vid_width'  => 'og:video:width',
						'og_vid_height' => 'og:video:height',
						'og_vid_title'  => 'og:video:title',
						'og_vid_desc'   => 'og:video:description',
					) as $md_key => $mt_name ) {
	
						/**
						 * Note that get_options() returns null if an index key is not found.
						 */
						$value = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $md_key );
	
						if ( ! empty( $value ) ) {	// Must be a non-empty string.
							$og_ret[$num][$mt_name] = $value;
						}
					}

					break;	// Only do the first video.
				}
			}

			if ( ! empty( $this->p->options['og_vid_html_type'] ) ) {

				$og_extend = array();

				foreach ( $og_ret as $num => $og_single_video ) {

					if ( ! empty( $og_single_video['og:video:embed_url'] ) ) {

						/**
						 * Start with a fresh copy of all og meta tags.
						 */
						$og_single_embed = SucomUtil::get_mt_video_seed( 'og', $og_single_video, false );

						/**
						 * Exclude the facebook applink meta tags.
						 */
						$og_single_embed = SucomUtil::preg_grep_keys( '/^og:/', $og_single_embed );

						unset( $og_single_embed['og:video:secure_url'] );	// Just in case.

						$og_single_embed['og:video:url']  = $og_single_video['og:video:embed_url'];
						$og_single_embed['og:video:type'] = 'text/html';

						/**
						 * Embedded videos may not have width / height information defined.
						 */
						foreach ( array( 'og:video:width', 'og:video:height' ) as $mt_name ) {
							if ( isset( $og_single_embed[$mt_name] ) && $og_single_embed[$mt_name] === '' ) {
								unset( $og_single_embed[$mt_name] );
							}
						}

						/**
						 * Add application/x-shockwave-flash video first and the text/html video second.
						 */
						if ( SucomUtil::get_mt_media_url( $og_single_video, $mt_media_pre = 'og:video',
							$mt_suffixes = array( ':secure_url', ':url', '' ) ) ) {

							$og_extend[] = $og_single_video;
						}

						$og_extend[] = $og_single_embed;

					} else {
						$og_extend[] = $og_single_video;
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning ' . count( $og_extend ) . ' videos' );
				}

				return $og_extend;

			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning ' . count( $og_ret ) . ' videos' );
				}

				return $og_ret;
			}
		}

		public function get_all_images( $num = 0, $size_name = 'thumbnail', array $mod, $check_dupes = true, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'num'         => $num,
					'size_name'   => $size_name,
					'mod'         => $mod,
					'check_dupes' => $check_dupes,
					'md_pre'      => $md_pre,
				) );
			}

			$og_ret      = array();
			$num_diff    = SucomUtil::count_diff( $og_ret, $num );
			$force_regen = $this->p->util->is_force_regen( $mod, $md_pre );	// false by default

			$this->p->util->clear_uniq_urls( $size_name );			// clear cache for $size_name context

			if ( $mod[ 'is_post' ] ) {

				if ( $mod[ 'post_type' ] === 'attachment' && wp_attachment_is_image( $mod[ 'id' ] ) ) {

					$og_single_image = $this->p->media->get_attachment_image( $num_diff, $size_name, $mod[ 'id' ], $check_dupes );

					if ( empty( $og_single_image ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'exiting early: no attachment image - returning default image' );
						}

						return array_merge( $og_ret, $this->p->media->get_default_images( $num_diff,
							$size_name, $check_dupes, $force_regen ) );

					} else {
						return array_merge( $og_ret, $og_single_image );
					}
				}

				/**
				 * Check for custom meta, featured, or attached image(s).
				 * Allow for empty post id in order to execute featured / attached image filters for modules.
				 */
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
					$og_ret = array_merge( $og_ret, $this->p->media->get_post_images( $num_diff,
						$size_name, $mod[ 'id' ], $check_dupes, $md_pre ) );
				}

				/**
				 * Check for NGG query variables and shortcodes.
				 */
				if ( ! empty( $this->p->m['media']['ngg'] ) && ! $this->p->util->is_maxed( $og_ret, $num ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking for NGG query variables and shortcodes' );
					}

					$num_diff = SucomUtil::count_diff( $og_ret, $num );

					$ngg_obj =& $this->p->m['media']['ngg'];

					$query_images = $ngg_obj->get_query_og_images( $num_diff, $size_name, $mod[ 'id' ], $check_dupes );

					if ( count( $query_images ) > 0 ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'skipping NGG shortcode check - '.count( $query_images ).' query image(s) returned' );
						}

						$og_ret = array_merge( $og_ret, $query_images );

					} elseif ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {

						$num_diff = SucomUtil::count_diff( $og_ret, $num );

						$shortcode_images = $ngg_obj->get_shortcode_og_images( $num_diff, $size_name, $mod[ 'id' ], $check_dupes );

						if ( ! empty( $shortcode_images ) ) {
							$og_ret = array_merge( $og_ret, $shortcode_images );
						}
					}

				}

				/**
				 * If we haven't reached the limit of images yet, keep going and check the content text.
				 */
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking the content text for images' );
					}

					$num_diff       = SucomUtil::count_diff( $og_ret, $num );
					$content_images = $this->p->media->get_content_images( $num_diff, $size_name, $mod, $check_dupes, $force_regen );

					if ( ! empty( $content_images ) ) {
						$og_ret = array_merge( $og_ret, $content_images );
					}
				}

			} else {

				/**
				 * get_og_images() also provides filter hooks for additional image ids and urls.
				 */
				if ( ! empty( $mod[ 'obj' ] ) ) {	// Term or user.

					$og_images = $mod[ 'obj' ]->get_og_images( $num_diff, $size_name, $mod[ 'id' ], $check_dupes, $force_regen, $md_pre );

					if ( ! empty( $og_images ) ) {
						$og_ret = array_merge( $og_ret, $og_images );
					}
				}

				if ( empty( $og_ret ) ) {
					$og_ret = array_merge( $og_ret, $this->p->media->get_default_images( $num_diff,
						$size_name, $check_dupes, $force_regen ) );

				}
			}

			$this->p->util->slice_max( $og_ret, $num );

			return $og_ret;
		}

		/**
		 * The returned array can include a varying number of elements, depending on the $request value.
		 */
		public function get_media_info( $size_name, array $request, array $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret       = array();
			$has_pdir  = $this->p->avail[ '*' ][ 'p_dir' ];
			$has_pp    = $this->p->check->pp( $this->p->lca, true, $has_pdir );
			$og_images = null;
			$og_videos = null;

			foreach ( $request as $key ) {

				switch ( $key ) {

					case 'pid':
					case ( preg_match( '/^(image|img)/', $key ) ? true : false ):

						if ( null === $og_images ) {	// Get images only once.
							$og_images = $this->get_all_images( 1, $size_name, $mod, false, $md_pre );
						}

						break;

					case ( preg_match( '/^(vid|prev)/', $key ) ? true : false ):

						if ( null === $og_videos && $has_pp ) {	// Get videos only once.
							$og_videos = $this->get_all_videos( 1, $mod, false, $md_pre );	// $check_dupes is false.
						}

						break;
				}
			}

			foreach ( $request as $key ) {

				switch ( $key ) {

					case 'pid':

						if ( ! isset( $get_mt_name ) ) {
							$get_mt_name = $mt_pre . ':image:id';
						}

						// no break - fall through

					case 'image':
					case 'img_url':

						if ( ! isset( $get_mt_name ) ) {
							$get_mt_name = $mt_pre . ':image';
						}

						// no break - fall through

						if ( $og_videos !== null ) {
							$ret[ $key ] = $this->get_media_value( $og_videos, $get_mt_name );
						}

						if ( empty( $ret[ $key ] ) ) {
							$ret[ $key ] = $this->get_media_value( $og_images, $get_mt_name );
						}

						/**
						 * If there's no image, and no video preview image, then add
						 * the default image for singular (aka post) webpages.
						 */
						if ( empty( $ret[ $key ] ) && $mod[ 'is_post' ] ) {

							$og_images = $this->p->media->get_default_images( 1, $size_name, $check_dupes = false );

							$ret[ $key ] = $this->get_media_value( $og_images, $get_mt_name );
						}

						break;

					case 'img_alt':

						$ret[ $key ] = $this->get_media_value( $og_images, $mt_pre.':image:alt' );

						break;

					case 'video':
					case 'vid_url':

						$ret[ $key ] = $this->get_media_value( $og_videos, $mt_pre.':video' );

						break;

					case 'vid_type':

						$ret[ $key ] = $this->get_media_value( $og_videos, $mt_pre.':video:type' );

						break;

					case 'vid_title':

						$ret[ $key ] = $this->get_media_value( $og_videos, $mt_pre.':video:title' );

						break;

					case 'vid_desc':

						$ret[ $key ] = $this->get_media_value( $og_videos, $mt_pre.':video:description' );

						break;

					case 'vid_width':

						$ret[ $key ] = $this->get_media_value( $og_videos, $mt_pre.':video:width' );

						break;

					case 'vid_height':

						$ret[ $key ] = $this->get_media_value( $og_videos, $mt_pre.':video:height' );

						break;

					case 'prev_url':
					case 'preview':

						$ret[ $key ] = $this->get_media_value( $og_videos, $mt_pre.':video:thumbnail_url' );

						break;

					default:

						$ret[ $key ] = '';

						break;
				}

				unset( $get_mt_name );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $ret );
			}

			return $ret;
		}

		public function get_media_value( $mt_og, $mt_media_pre ) {

			if ( empty( $mt_og ) || ! is_array( $mt_og ) ) {
				return '';
			}

			$og_media = reset( $mt_og );	// only search the first media array

			switch ( $mt_media_pre ) {

				/**
				 * If we're asking for an image or video url, then search all three values sequentially.
				 */
				case ( preg_match( '/:(image|video)(:secure_url|:url)?$/', $mt_media_pre ) ? true : false ):

					$mt_search = array(
						$mt_media_pre . ':secure_url',	// og:image:secure_url
						$mt_media_pre . ':url',		// og:image:url
						$mt_media_pre,			// og:image
					);

					break;

				/**
				 * Otherwise, only search for that specific meta tag name.
				 */
				default:

					$mt_search = array( $mt_media_pre );

					break;
			}

			foreach ( $mt_search as $key ) {

				if ( ! isset( $og_media[ $key ] ) ) {

					continue;

				} elseif ( $og_media[ $key ] === '' || $og_media[ $key ] === null ) {	// Allow for 0.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $og_media[ $key ].' value is empty (skipped)' );
					}

				} elseif ( $og_media[ $key ] === WPSSO_UNDEF || $og_media[ $key ] === (string) WPSSO_UNDEF ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $og_media[ $key ].' value is '.WPSSO_UNDEF.' (skipped)' );
					}

				} else {
					return $og_media[ $key ];
				}
			}

			return '';
		}

		/**
		 * Returns an optional and customized locale value for the og:locale meta tag.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public function get_fb_locale( array $opts, $mixed = 'current' ) {

			/**
			 * Check for customized locale.
			 */
			if ( ! empty( $opts ) ) {

				$fb_locale_key = SucomUtil::get_key_locale( 'fb_locale', $opts, $mixed );

				if ( ! empty( $opts[ $fb_locale_key ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'returning "' . $opts[ $fb_locale_key ] . '" locale for "' . $fb_locale_key . '" option key' );
					}

					return $opts[ $fb_locale_key ];
				}
			}

			/**
			 * Get the locale requested in $mixed.
			 *
			 * $mixed = 'default' | 'current' | post ID | $mod array
			 */
			$locale = SucomUtil::get_locale( $mixed );

			if ( empty( $locale ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: locale value is empty' );
				}

				return $locale;
			}

			/**
			 * Fix known exceptions.
			 */
			switch ( $locale ) {

				case 'de_DE_formal':

					$locale = 'de_DE';

					break;
			}

			/**
			 * Return the Facebook equivalent for this WordPress locale.
			 */
			$fb_pub_lang = SucomUtil::get_pub_lang( 'facebook' );

			if ( ! empty( $fb_pub_lang[ $locale ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning valid facebook locale "'.$locale.'"' );
				}

				return $locale;

			}
			
			/**
			 * Fallback to the default WordPress locale.
			 */
			$def_locale  = SucomUtil::get_locale( 'default' );

			if ( ! empty( $fb_pub_lang[ $def_locale ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning default locale "'.$def_locale.'"' );
				}

				return $def_locale;

			}

			/**
			 * Fallback to en_US.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'returning fallback locale "en_US"' );
			}

			return 'en_US';
		}

		/**
		 * Unset mis-matched og_type meta tags using the 'og_type_mt' array as a reference.
		 * For example, remove all 'article' meta tags if the og_type is 'website'. Removing
		 * only known meta tags (using the 'og_type_mt' array as a reference) protects
		 * internal meta tags that may be used later by WpssoHead::extract_head_info().
		 * For example, the schema:type:id and p:image meta tags.
		 *
		 * The 'og_content_map' array is also checked for Schema values that need to be
		 * swapped for simpler Open Graph meta tag values.
		 *
		 * Called by WpssoHead::get_head_array() before merging all meta tag arrays.
		 */
		public function sanitize_array( array $mod, array $mt_og, $og_type = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( empty( $og_type ) ) {

				if ( empty( $mt_og['og:type'] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'og:type is empty and required for sanitation' );
					}

					return $mt_og;
				}

				$og_type = $mt_og['og:type'];
			}

			if ( ! empty( $mt_og[ $og_type ] ) && is_array( $mt_og[ $og_type ] ) ) {

				foreach ( $mt_og[ $og_type ] as $num => $mt_arr ) {
					$mt_og[ $og_type ][ $num ] = $this->sanitize_array( $mod, $mt_arr, $og_type );
				}
			}

			foreach ( $this->p->cf[ 'head' ][ 'og_type_mt' ] as $type_id => $og_type_mt_md ) {

				foreach ( $og_type_mt_md as $mt_name => $md_key ) {

					if ( isset( $mt_og[ $mt_name ] ) ) {

						if (  $type_id !== $og_type ) {	// Mis-matched meta tag for this og:type

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'removing extra meta tag ' . $mt_name );
							}

							unset( $mt_og[$mt_name] );

						} elseif ( isset( $this->p->cf['head']['og_content_map'][$mt_name][$mt_og[$mt_name]] ) ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'mapping content value for ' . $mt_name );
							}

							$mt_og[$mt_name] = $this->p->cf['head']['og_content_map'][$mt_name][$mt_og[$mt_name]];
						}
					}
				}
			}

			return $mt_og;
		}
	}
}
