<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoOpenGraph' ) ) {

	class WpssoOpenGraph {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$max_int = SucomUtil::get_max_int();

			$this->p->util->add_plugin_filters( $this, array(
				'plugin_image_sizes' => 1,
			) );

			$this->p->util->add_plugin_filters( $this, array(
				'get_post_options'  => 3,
				'save_post_options' => 4,
			), $max_int );

			/**
			 * Hook the first available filter name (example: 'language_attributes').
			 */
			foreach ( array( 'plugin_html_attr_filter', 'plugin_head_attr_filter' ) as $opt_pre ) {

				if ( ! empty( $this->p->options[ $opt_pre . '_name' ] ) && $this->p->options[ $opt_pre . '_name' ] !== 'none' ) {

					$wp_filter_name = $this->p->options[ $opt_pre . '_name' ];

					add_filter( $wp_filter_name, array( $this, 'add_ogpns_attributes' ),
						 ( isset( $this->p->options[ $opt_pre . '_prio' ] ) ?
						 	(int) $this->p->options[ $opt_pre . '_prio' ] : 100 ), 1 );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'added add_ogpns_attributes filter for ' . $wp_filter_name );
					}

					break;	// Stop here.

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipping add_ogpns_attributes for ' . $opt_pre . ' - filter name is empty or disabled' );
				}
			}
		}

		public function filter_plugin_image_sizes( $sizes ) {

			$sizes[ 'og' ] = array(	// Option prefix.
				'name'  => 'opengraph',
				'label' => _x( 'Open Graph Image', 'image size label', 'wpsso' ),
			);

			return $sizes;
		}

		public function filter_get_post_options( $md_opts, $post_id, $mod ) {

			$this->update_post_md_opts( $md_opts, $post_id, $mod );	// Modifies the $md_opts array.

			return $md_opts;
		}

		public function filter_save_post_options( $md_opts, $post_id, $rel_id, $mod ) {

			$this->update_post_md_opts( $md_opts, $post_id, $mod );	// Modifies the $md_opts array.

			return $md_opts;
		}

		public function add_ogpns_attributes( $html_attr ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array (
					'html_attr' => $html_attr,
				) );
			}

			$use_post = apply_filters( $this->p->lca . '_use_post', false );

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
			if ( ! empty( $this->p->cf[ 'head' ][ 'og_type_ns' ][ $type_id ] ) ) {
				$og_ns[ $type_id ] = $this->p->cf[ 'head' ][ 'og_type_ns' ][ $type_id ];
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
		public function get_mod_og_type( array $mod, $get_ns = false, $use_mod_opts = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			static $local_cache = array();

			$cache_salt = false;

			/**
			 * Optimize and cache post/term/user og type values.
			 */
			if ( ! empty( $mod[ 'name' ] ) && ! empty( $mod[ 'id' ] ) ) {

				$cache_salt = SucomUtil::get_mod_salt( $mod ) . '_ns:' . (string) $get_ns . '_opts:' . (string) $use_mod_opts;

				if ( isset( $local_cache[ $cache_salt ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'returning local cache value "' . $local_cache[ $cache_salt ] . '"' );
					}

					return $local_cache[ $cache_salt ];

				} elseif ( is_object( $mod[ 'obj' ] ) && $use_mod_opts ) {	// Check for a column og_type value in wp_cache.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking for value from column wp_cache' );
					}

					$value = $mod[ 'obj' ]->get_column_wp_cache( $mod, $this->p->lca . '_og_type' );	// Returns empty string if no value found.

					if ( ! empty( $value ) ) {

						if ( $get_ns && $value !== 'none' ) {	// Return the og type namespace instead.

							$og_type_ns  = $this->p->cf[ 'head' ][ 'og_type_ns' ];

							if ( ! empty( $og_type_ns[ $value ] ) ) {

								$value = $og_type_ns[ $value ];

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

						return $local_cache[ $cache_salt ] = $value;
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'no value found in local cache or column wp_cache' );
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipping cache check: mod name and/or id value is empty' );
			}

			$default_key = apply_filters( $this->p->lca . '_og_type_for_default', 'website', $mod );
			$og_type_ns  = $this->p->cf[ 'head' ][ 'og_type_ns' ];
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

				if ( $mod[ 'is_home' ] ) {	// Static or index page.

					$type_id = $default_key;

					if ( $mod[ 'is_home_page' ] ) {

						$type_id = $this->get_og_type_id_for_name( 'home_page' );

						$type_id = apply_filters( $this->p->lca . '_og_type_for_home_page', $type_id, $mod );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'using og type id "' . $type_id . '" for home page' );
						}

					} else {

						$type_id = $this->get_og_type_id_for_name( 'home_posts' );

						$type_id = apply_filters( $this->p->lca . '_og_type_for_home_posts', $type_id, $mod );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'using og type id "' . $type_id . '" for home posts' );
						}
					}

				} elseif ( $mod[ 'is_post' ] ) {

					if ( ! empty( $mod[ 'post_type' ] ) ) {

						if ( $mod[ 'is_post_type_archive' ] ) {

							$type_id = $this->get_og_type_id_for_name( 'post_archive' );

							$type_id = apply_filters( $this->p->lca . '_og_type_for_post_type_archive_page', $type_id, $mod );

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'using og type id "' . $type_id . '" for post_type_archive page' );
							}

						} elseif ( isset( $this->p->options[ 'og_type_for_' . $mod[ 'post_type' ] ] ) ) {

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

							$type_id = $this->get_og_type_id_for_name( 'page' );

							$type_id = apply_filters( $this->p->lca . '_og_type_for_post_type_unknown_type', $type_id, $mod );

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'using "page" og type for unknown post type ' . $mod[ 'post_type' ] );
							}
						}

					} else {	// Post objects without a post_type property.

						$type_id = $this->get_og_type_id_for_name( 'page' );

						$type_id = apply_filters( $this->p->lca . '_og_type_for_post_type_empty_type', $type_id, $mod );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'using "page" og type for empty post type' );
						}
					}

				} elseif ( $mod[ 'is_term' ] ) {

					if ( ! empty( $mod[ 'tax_slug' ] ) ) {

						$type_id = $this->get_og_type_id_for_name( 'tax_' . $mod[ 'tax_slug' ] );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'using og type id "' . $type_id . '" from term option value' );
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

			} elseif ( $get_ns ) {	// False by default.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning og type namespace "' . $og_type_ns[ $type_id ] . '"' );
				}

				$get_value = $og_type_ns[ $type_id ];

			} else {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning og type id "' . $type_id . '"' );
				}

				$get_value = $type_id;
			}

			/**
			 * Optimize and cache post/term/user og type values.
			 */
			if ( $cache_salt ) {
				$local_cache[ $cache_salt ] = $get_value;
			}

			return $get_value;
		}

		public function get_array( array $mod, $size_name = null ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * The 'wpsso_og_seed' filter is hooked by the Premium e-commerce modules, for example, to provide product
			 * meta tags.
			 */
			$mt_og    = apply_filters( $this->p->lca . '_og_seed', array(), $mod );
			$max_nums = $this->p->util->get_max_nums( $mod );
			$has_pp   = $this->p->check->pp();

			if ( empty( $size_name ) ) {
				$size_name = $this->p->lca . '-opengraph';
			}

			if ( ! empty( $mt_og ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $this->p->lca . '_og_seed filter returned:' );
					$this->p->debug->log( $mt_og );
				}
			}

			/**
			 * Facebook admins meta tag.
			 */
			if ( ! isset( $mt_og[ 'fb:admins' ] ) ) {

				if ( ! empty( $this->p->options[ 'fb_admins' ] ) ) {

					foreach ( explode( ',', $this->p->options[ 'fb_admins' ] ) as $fb_admin ) {
						$mt_og[ 'fb:admins' ][] = trim( $fb_admin );
					}
				}
			}

			/**
			 * Facebook app id meta tag.
			 */
			if ( ! isset( $mt_og[ 'fb:app_id' ] ) ) {
				$mt_og[ 'fb:app_id' ] = $this->p->options[ 'fb_app_id' ];
			}

			/**
			 * Type id meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:type' ] ) ) {
				$mt_og[ 'og:type' ] = $this->get_mod_og_type( $mod );
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:type already defined = ' . $mt_og[ 'og:type' ] );
			}

			/**
			 * URL meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:url' ] ) ) {
				$mt_og[ 'og:url' ] = $this->p->util->get_sharing_url( $mod );
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:url already defined = ' . $mt_og[ 'og:url' ] );
			}

			/**
			 * Locale meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:locale' ] ) ) {
				$mt_og[ 'og:locale' ] = $this->get_fb_locale( $this->p->options, $mod );
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:locale already defined = ' . $mt_og[ 'og:locale' ] );
			}

			/**
			 * Site name meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:site_name' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting site name for og:site_name meta tag' );
				}

				$mt_og[ 'og:site_name' ] = SucomUtil::get_site_name( $this->p->options, $mod );	// localized

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:site_name already defined = ' . $mt_og[ 'og:site_name' ] );
			}

			/**
			 * Title meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:title' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting title for og:title meta tag' );
				}

				$mt_og[ 'og:title' ] = $this->p->page->get_title( $this->p->options[ 'og_title_max_len' ], '...', $mod );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og:title value = ' . $mt_og[ 'og:title' ] );
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:title already defined = ' . $mt_og[ 'og:title' ] );
			}

			/**
			 * Description meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:description' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting description for og:description meta tag' );
				}

				$mt_og[ 'og:description' ] = $this->p->page->get_description( $this->p->options[ 'og_desc_max_len' ],
					'...', $mod, $read_cache = true, $this->p->options[ 'og_desc_hashtags' ] );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og:description value = ' . $mt_og[ 'og:description' ] );
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og:description already defined = ' . $mt_og[ 'og:description' ] );
			}

			/**
			 * Updated date / time meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:updated_time' ] ) ) {
				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {
					$mt_og[ 'og:updated_time' ] = trim( get_post_modified_time( 'c', true, $mod[ 'id' ] ) );	// $gmt is true.
				}
			}

			/**
			 * Get all videos.
			 *
			 * Call before getting all images to find / use preview images.
			 */
			if ( ! isset( $mt_og[ 'og:video' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting videos for og:video meta tag' );
				}

				if ( ! $has_pp ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'no video modules available' );
					}

				} elseif ( $max_nums[ 'og_vid_max' ] > 0 ) {

					$mt_og[ 'og:video' ] = $this->get_all_videos( $max_nums[ 'og_vid_max' ], $mod );

					if ( empty( $mt_og[ 'og:video' ] ) ) {

						unset( $mt_og[ 'og:video' ] );

					} else {

						/**
						 * The following get_all_images() method call will include any video preview
						 * images, so remove them here to avoid duplicate image meta tags.
						 */
						foreach ( $mt_og[ 'og:video' ] as &$og_single_video ) {
							$og_single_video = SucomUtil::preg_grep_keys( '/^og:image/', $og_single_video, $invert = true );
						}
					}

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'videos disabled: maximum videos is 0 or less' );
					}
				}
			}

			/**
			 * Get all images.
			 */
			if ( ! isset( $mt_og[ 'og:image' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting images for og:image meta tag' );
				}

				if ( $max_nums[ 'og_img_max' ] > 0 ) {

					$mt_og[ 'og:image' ] = $this->get_all_images( $max_nums[ 'og_img_max' ], $size_name, $mod );

					if ( empty( $mt_og[ 'og:image' ] ) ) {
						unset( $mt_og[ 'og:video' ] );
					}

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'skipped getting images: maximum images is 0 or less' );
					}
				}
			}

			/**
			 * Pre-define some basic open graph meta tags for this og:type. If the meta tag has an associated meta
			 * option name, then read it's value from the meta options.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'checking og_type_mt array for known meta tags and md options' );
			}

			$type_id = $mt_og[ 'og:type' ];

			if ( isset( $this->p->cf[ 'head' ][ 'og_type_mt' ][ $type_id ] ) ) {	// Check if og:type is in config.

				/**
				 * Optimize and call get_options() only once. Returns an empty string if no meta found.
				 */
				$md_opts = empty( $mod[ 'obj' ] ) ? array() : (array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				/**
				 * Add post/term/user meta data to the Open Graph meta tags.
				 */
				$this->add_og_type_mt_md( $type_id, $mt_og, $md_opts );

				/**
				 * If we have a GTIN number, try to improve the assigned property name.
				 */
				self::check_gtin_mt_value( $mt_og );

				/**
				 * Include variations (aka product offers) if available.
				 */
				if ( ! empty( $mt_og[ 'product:offers' ] ) && is_array( $mt_og[ 'product:offers' ] ) ) {

					foreach ( $mt_og[ 'product:offers' ] as $num => $offer ) {

						foreach( $offer as $mt_name => $mt_value ) {

							if ( isset( $this->p->cf[ 'head' ][ 'og_type_array' ][ 'product' ][ $mt_name ] ) ) {

								$mt_og[ 'product' ][ $num ][ $mt_name ] = $mt_value;

								if ( isset( $mt_og[ $mt_name ] ) ) {
									unset ( $mt_og[ $mt_name ] );
								}
							}
						}

						/**
						 * If we have a GTIN number, try to improve the assigned property name.
						 */
						self::check_gtin_mt_value( $offer );
					}

				} elseif ( isset( $mt_og[ 'product:price:amount' ] ) ) {

					if ( is_numeric( $mt_og[ 'product:price:amount' ] ) ) {	// Allow for price of 0.

						if ( empty( $mt_og[ 'product:price:currency' ] ) ) {
							$mt_og[ 'product:price:currency' ] = $this->p->options[ 'plugin_def_currency' ];
						}

					} else {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'product price amount must be numeric' );
						}

						unset( $mt_og[ 'product:price:amount' ] );
						unset( $mt_og[ 'product:price:currency' ] );
					}
				}
			}

			/**
			 * If the module is a post object, define the author, publishing date, etc. These values may still be used
			 * by other filters, and if the og:type is not an article, the meta tags will be sanitized at the end of
			 * WpssoHead::get_head_array().
			 */
			if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

				if ( ! isset( $mt_og[ 'article:author' ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'getting names / urls for article:author meta tags' );
					}

					if ( $mod[ 'post_author' ] ) {

						/**
						 * Non-standard / internal meta tag used for display purposes.
						 */
						$mt_og[ 'article:author:name' ] = $this->p->user->get_author_meta( $mod[ 'post_author' ],
							$this->p->options[ 'seo_author_name' ] );

						/**
						 * An array of author URLs.
						 */
						$mt_og[ 'article:author' ] = $this->p->user->get_authors_websites( $mod[ 'post_author' ],
							$this->p->options[ 'og_author_field' ] );

					} else {
						$mt_og[ 'article:author' ] = array();
					}

					/**
					 * Add co-author URLs if available.
					 */
					if ( ! empty( $mod[ 'post_coauthors' ] ) ) {

						$og_profile_urls = $this->p->user->get_authors_websites( $mod[ 'post_coauthors' ],
							$this->p->options[ 'og_author_field' ] );

						$mt_og[ 'article:author' ] = array_merge( $mt_og[ 'article:author' ], $og_profile_urls );
					}
				}

				if ( ! isset( $mt_og[ 'article:publisher' ] ) ) {
					$mt_og[ 'article:publisher' ] = SucomUtil::get_key_value( 'fb_publisher_url', $this->p->options, $mod );
				}

				if ( ! isset( $mt_og[ 'article:tag' ] ) ) {
					$mt_og[ 'article:tag' ] = $this->p->page->get_tag_names( $mod );
				}

				if ( ! isset( $mt_og[ 'article:published_time' ] ) ) {
					if ( $mod[ 'post_status' ] === 'publish' ) {	// Must be published to have publish time.
						$mt_og[ 'article:published_time' ] = trim( get_post_time( 'c', $gmt = true, $mod[ 'id' ] ) );
					}
				}

				if ( ! isset( $mt_og[ 'article:modified_time' ] ) ) {
					$mt_og[ 'article:modified_time' ] = trim( get_post_modified_time( 'c', $gmt = true, $mod[ 'id' ] ) );
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

			$og_type_ns = $this->p->cf[ 'head' ][ 'og_type_ns' ];

			$type_id = isset( $this->p->options[ 'og_type_for_' . $type_name] ) ?	// Just in case.
				$this->p->options[ 'og_type_for_' . $type_name] : $default_id;

			if ( empty( $type_id ) || $type_id === 'none' ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og type id for ' . $type_name . ' is empty or disabled' );
				}

				$type_id = $default_id;

			} elseif ( empty( $og_type_ns[ $type_id ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'og type id "' . $type_id . '" for ' . $type_name . ' not in og type ns' );
				}

				$type_id = $default_id;

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'og type id for ' . $type_name . ' is ' . $type_id );
			}

			return $type_id;
		}

		public function get_og_types_select() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Use only supported (aka compat) Open Graph types.
			 */
			$og_type_ns = $this->p->cf[ 'head' ][ 'og_type_ns_compat' ];

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

			return $select;
		}

		public function get_all_previews( $num = 0, array $mod, $check_dupes = true, $md_pre = 'og', $force_prev = false ) {

			/**
			 * The get_all_videos() method uses the 'og_vid_max' argument as part of its caching salt, so re-use the
			 * original number to get all possible videos (from its cache), then maybe limit the number of preview
			 * images if necessary.
			 */
			$max_nums  = $this->p->util->get_max_nums( $mod );
			$og_videos = $this->get_all_videos( $max_nums[ 'og_vid_max' ], $mod, $check_dupes, $md_pre, $force_prev );
			$og_images = array();

			$this->p->util->clear_uniq_urls( array( 'preview' ) );

			foreach ( $og_videos as $og_single_video ) {

				$image_url = SucomUtil::get_mt_media_url( $og_single_video, $mt_media_pre = 'og:image' );

				/**
				 * Check preview images for duplicates since the same videos may be available in different formats
				 * (application/x-shockwave-flash and text/html for example).
				 */
				if ( $image_url ) {

					if ( ! $check_dupes || $this->p->util->is_uniq_url( $image_url, 'preview' ) ) {

						$og_single_image = SucomUtil::preg_grep_keys( '/^og:image/', $og_single_video );

						if ( $this->p->util->push_max( $og_images, $og_single_image, $num ) ) {
							return $og_images;
						}
					}
				}
			}

			return $og_images;
		}

		public function get_all_videos( $num = 0, array $mod, $check_dupes = true, $md_pre = 'og', $force_prev = false ) {

			$cache_args = array(
				'num'         => $num,
				'mod'         => $mod,
				'check_dupes' => $check_dupes,
				'md_pre'      => $md_pre,
				'force_prev'  => $force_prev,
			);

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'get all open graph videos' );	// Begin timer.

				$this->p->debug->log_args( $cache_args );
			}

			static $local_cache = array();

			$cache_salt = SucomUtil::pretty_array( $cache_args, $flatten = true );

			if ( isset( $local_cache[ $cache_salt ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning video data from local cache' );
				}

				return $local_cache[ $cache_salt ];

			} else {

				$local_cache[ $cache_salt ] = array();

				$og_ret =& $local_cache[ $cache_salt ];
			}

			$use_prev = $this->p->options[ 'og_vid_prev_img' ];
			$num_diff = SucomUtil::count_diff( $og_ret, $num );
			$has_pp   = $this->p->check->pp();

			if ( ! $has_pp ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'no video modules available' );
				}

				return $og_ret;
			}

			$this->p->util->clear_uniq_urls( array( 'video', 'content_video', 'video_details' ) );

			/**
			 * Get video information and preview enable/disable option from the post/term/user meta.
			 */
			if ( ! empty( $mod[ 'obj' ] ) ) {

				/**
				 * Note that get_options() returns null if an index key is not found.
				 */
				if ( ( $mod_prev = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'og_vid_prev_img' ) ) !== null ) {

					$use_prev = $mod_prev;	// Use true/false/1/0 value from the custom option.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting use_prev to ' . ( empty( $use_prev ) ? 'false' : 'true' ) . ' from meta data' );
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'checking for custom videos in ' . $mod[ 'name' ] . ' options' );	// Begin timer.
				}

				/**
				 * get_og_videos() converts the $md_pre value to an array and always checks for 'og' metadata as a fallback.
				 */
				$og_ret = array_merge( $og_ret, $mod[ 'obj' ]->get_og_videos( $num_diff, $mod[ 'id' ], $check_dupes, $md_pre ) );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'checking for custom videos in ' . $mod[ 'name' ] . ' options' );	// End timer.
				}

			}

			$num_diff = SucomUtil::count_diff( $og_ret, $num );

			/**
			 * Optionally get more videos from the post content.
			 */
			if ( $mod[ 'is_post' ] && ! $this->p->util->is_maxed( $og_ret, $num ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'checking for additional videos in the post content' );	// Begin timer.
				}

				$og_ret = array_merge( $og_ret, $this->p->media->get_content_videos( $num_diff, $mod, $check_dupes ) );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'checking for additional videos in the post content' );	// End timer.
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

				foreach ( $og_ret as &$og_single_video ) {

					$og_single_video = SucomUtil::preg_grep_keys( '/^og:image/', $og_single_video, $invert = true );

					$og_ret[ $num ][ 'og:video:has_image' ] = false;
				}
			}

			/**
			 * Get custom video information from post/term/user meta data for the FIRST video.
			 *
			 * If $md_pre is 'none' (special index keyword), then don't load any custom video information. The
			 * og:video:title and og:video:description meta tags are not standard and their values will only appear in
			 * Schema markup.
			 */
			if ( ! empty( $mod[ 'obj' ] ) && $md_pre !== 'none' ) {

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
							$og_ret[ $num ][ $mt_name ] = $value;
						}
					}

					break;	// Only do the first video.
				}
			}

			$og_extend = array();

			foreach ( $og_ret as $num => $og_single_video ) {

				if ( ! empty( $og_single_video[ 'og:video:embed_url' ] ) ) {

					/**
					 * Start with a fresh copy of all og meta tags.
					 */
					$og_single_embed = SucomUtil::get_mt_video_seed( 'og', $og_single_video, false );

					/**
					 * Use only og meta tags, excluding the facebook applink meta tags.
					 */
					$og_single_embed = SucomUtil::preg_grep_keys( '/^og:/', $og_single_embed );

					unset( $og_single_embed[ 'og:video:secure_url' ] );	// Just in case.

					$og_single_embed[ 'og:video:url' ]  = $og_single_video[ 'og:video:embed_url' ];
					$og_single_embed[ 'og:video:type' ] = 'text/html';

					/**
					 * Embedded videos may not have width / height information defined.
					 */
					foreach ( array( 'og:video:width', 'og:video:height' ) as $mt_name ) {
						if ( isset( $og_single_embed[ $mt_name ] ) && $og_single_embed[ $mt_name ] === '' ) {
							unset( $og_single_embed[ $mt_name ] );
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

				$this->p->debug->mark( 'get all open graph videos' );	// End timer.
			}

			return $og_extend;
		}

		public function get_thumbnail_url( $size_name = 'thumbnail', array $mod, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$og_images = $this->get_all_images( $num = 1, $size_name, $mod, $check_dupes = true, $md_pre );

			return SucomUtil::get_mt_media_url( $og_images, $mt_media_pre = 'og:image' );
		}

		/**
		 * Note that the size_name is used to check for duplicates.
		 */
		public function get_all_images( $num = 0, $size_name = 'thumbnail', array $mod, $check_dupes = true, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'get all open graph images' );	// Begin timer.

				$this->p->debug->log_args( array(
					'num'         => $num,
					'size_name'   => $size_name,
					'mod'         => $mod,
					'check_dupes' => $check_dupes,
					'md_pre'      => $md_pre,
				) );
			}

			$og_ret   = array();
			$num_diff = SucomUtil::count_diff( $og_ret, $num );

			$this->p->util->clear_uniq_urls( $size_name );	// Clear cache for $size_name context.

			$preview_images = $this->get_all_previews( $num_diff, $mod );

			if ( ! empty( $preview_images ) ) {
				$og_ret = array_merge( $og_ret, $preview_images );
			}

			if ( $mod[ 'is_post' ] ) {

				if ( $mod[ 'post_type' ] === 'attachment' && wp_attachment_is_image( $mod[ 'id' ] ) ) {

					$og_single_image = $this->p->media->get_attachment_image( $num_diff, $size_name, $mod[ 'id' ], $check_dupes );

					if ( empty( $og_single_image ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'exiting early: no attachment image' );
						}

						return $og_ret;

					} else {
						return array_merge( $og_ret, $og_single_image );
					}
				}

				/**
				 * Check for custom meta, featured, or attached image(s).
				 *
				 * Allow for empty post id in order to execute featured / attached image filters for modules.
				 */
				if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {

					$post_images = $this->p->media->get_post_images( $num_diff, $size_name, $mod[ 'id' ], $check_dupes, $md_pre );

					if ( ! empty( $post_images ) ) {
						$og_ret = array_merge( $og_ret, $post_images );
					}
				}

				/**
				 * Check for NGG query variables and shortcodes.
				 */
				if ( ! empty( $this->p->m[ 'media' ][ 'ngg' ] ) && ! $this->p->util->is_maxed( $og_ret, $num ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'checking for NGG query variables and shortcodes' );
					}

					$num_diff = SucomUtil::count_diff( $og_ret, $num );

					$ngg_obj =& $this->p->m[ 'media' ][ 'ngg' ];

					$query_images = $ngg_obj->get_query_og_images( $num_diff, $size_name, $mod[ 'id' ], $check_dupes );

					if ( count( $query_images ) > 0 ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'skipping NGG shortcode check - ' . count( $query_images ) . ' query image(s) returned' );
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

					$num_diff = SucomUtil::count_diff( $og_ret, $num );

					$content_images = $this->p->media->get_content_images( $num_diff, $size_name, $mod, $check_dupes );

					if ( ! empty( $content_images ) ) {
						$og_ret = array_merge( $og_ret, $content_images );
					}
				}

			} else {

				/**
				 * get_og_images() also provides filter hooks for additional image ids and urls.
				 */
				if ( ! empty( $mod[ 'obj' ] ) ) {	// Term or user.

					$og_images = $mod[ 'obj' ]->get_og_images( $num_diff, $size_name, $mod[ 'id' ], $check_dupes, $md_pre );

					if ( ! empty( $og_images ) ) {
						$og_ret = array_merge( $og_ret, $og_images );
					}
				}
			}

			if ( empty( $og_ret ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'no image(s) found - getting the default image' );
				}

				$default_images = $this->p->media->get_default_images( 1, $size_name, $check_dupes );

				if ( ! empty( $default_images ) ) {
					$og_ret = array_merge( $og_ret, $default_images );
				}
			}

			$this->p->util->slice_max( $og_ret, $num );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning ' . count( $og_ret ) . ' images' );

				$this->p->debug->mark( 'get all open graph images' );	// End timer.
			}

			return $og_ret;
		}

		/**
		 * The returned array can include a varying number of elements, depending on the $request value.
		 * 
		 * $md_pre may be 'none' when getting Open Graph option defaults (and not their custom values).
		 */
		public function get_media_info( $size_name, array $request, array $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$media_info = array();
			$og_images  = null;
			$og_videos  = null;

			foreach ( $request as $key ) {

				switch ( $key ) {

					case 'pid':
					case ( preg_match( '/^(image|img)/', $key ) ? true : false ):

						/**
						 * Get images only once.
						 */
						if ( null === $og_images ) {

							/**
							 * Optimize and make sure the image size exists first, just in case.
							 */
							$size_info = $this->p->util->get_size_info( $size_name );

							if ( empty( $size_info[ 'width' ] ) && empty( $size_info[ 'height' ] ) ) {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'missing size information for ' . $size_name );
								}

								$og_images = array();

							} else {

								$og_images = $this->get_all_images( $num = 1, $size_name, $mod, $check_dupes = true, $md_pre );
							}
						}

						break;

					case ( preg_match( '/^(vid|prev)/', $key ) ? true : false ):

						/**
						 * Get videos only once.
						 */
						if ( null === $og_videos ) {

							/**
							 * $md_pre may be 'none' when getting Open Graph option defaults (and not
							 * their custom values).
							 */
							$og_videos = $this->get_all_videos( 1, $mod, $check_dupes = true, $md_pre );
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
							$media_info[ $key ] = $this->get_media_value( $og_videos, $get_mt_name );
						}

						if ( empty( $media_info[ $key ] ) ) {
							$media_info[ $key ] = $this->get_media_value( $og_images, $get_mt_name );
						}

						break;

					case 'img_alt':

						$media_info[ $key ] = $this->get_media_value( $og_images, $mt_pre . ':image:alt' );

						break;

					case 'video':
					case 'vid_url':

						$media_info[ $key ] = $this->get_media_value( $og_videos, $mt_pre . ':video' );

						break;

					case 'vid_type':

						$media_info[ $key ] = $this->get_media_value( $og_videos, $mt_pre . ':video:type' );

						break;

					case 'vid_title':

						$media_info[ $key ] = $this->get_media_value( $og_videos, $mt_pre . ':video:title' );

						break;

					case 'vid_desc':

						$media_info[ $key ] = $this->get_media_value( $og_videos, $mt_pre . ':video:description' );

						break;

					case 'vid_width':

						$media_info[ $key ] = $this->get_media_value( $og_videos, $mt_pre . ':video:width' );

						break;

					case 'vid_height':

						$media_info[ $key ] = $this->get_media_value( $og_videos, $mt_pre . ':video:height' );

						break;

					case 'vid_prev':
					case 'prev_url':
					case 'preview':

						$media_info[ $key ] = $this->get_media_value( $og_videos, $mt_pre . ':video:thumbnail_url' );

						break;

					default:

						$media_info[ $key ] = '';

						break;
				}

				unset( $get_mt_name );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $media_info );
			}

			return $media_info;
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
						$this->p->debug->log( $og_media[ $key ] . ' value is empty (skipped)' );
					}

				} elseif ( $og_media[ $key ] === WPSSO_UNDEF || $og_media[ $key ] === (string) WPSSO_UNDEF ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $og_media[ $key ] . ' value is ' . WPSSO_UNDEF . ' (skipped)' );
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
					$this->p->debug->log( 'returning valid facebook locale "' . $locale . '"' );
				}

				return $locale;

			}

			/**
			 * Fallback to the default WordPress locale.
			 */
			$def_locale  = SucomUtil::get_locale( 'default' );

			if ( ! empty( $fb_pub_lang[ $def_locale ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning default locale "' . $def_locale . '"' );
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
		 * Unset mis-matched og_type meta tags using the 'og_type_mt' array as a reference. For example, remove all
		 * 'article' meta tags if the og_type is 'website'. Removing only known meta tags (using the 'og_type_mt' array as
		 * a reference) protects internal meta tags that may be used later by WpssoHead->extract_head_info(). For example,
		 * the schema:type:id and p:image meta tags.
		 *
		 * The 'og_content_map' array is also checked for Schema values that need to be swapped for simpler Open Graph meta
		 * tag values.
		 *
		 * Called by WpssoHead::get_head_array() before merging all meta tag arrays.
		 */
		public function sanitize_array( array $mod, array $mt_og, $og_type = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( empty( $og_type ) ) {

				if ( empty( $mt_og[ 'og:type' ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'og:type is empty and required for sanitation' );
					}

					return $mt_og;
				}

				$og_type = $mt_og[ 'og:type' ];
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

							unset( $mt_og[ $mt_name ] );

						} elseif ( isset( $this->p->cf[ 'head' ][ 'og_content_map' ][ $mt_name ][ $mt_og[ $mt_name ] ] ) ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'mapping content value for ' . $mt_name );
							}

							$mt_og[ $mt_name ] = $this->p->cf[ 'head' ][ 'og_content_map' ][ $mt_name ][ $mt_og[ $mt_name ] ];
						}
					}
				}
			}

			return $mt_og;
		}

		/**
		 * Add post/term/user meta data to the Open Graph meta tags.
		 */
		public function add_og_type_mt_md( $type_id, array &$mt_og, array $md_opts ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( empty( $this->p->cf[ 'head' ][ 'og_type_mt' ][ $type_id ] ) ) {	// Just in case.
				return;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'loading og_type_mt array for type id ' . $type_id );
			}

			/**
			 * Example $og_type_mt_md array:
			 *
			 *	'product' => array(
			 *		'product:age_group'               => '',
			 *		'product:availability'            => 'product_avail',
			 *		'product:brand'                   => 'product_brand',
			 *		'product:category'                => 'product_category',
			 *		'product:color'                   => 'product_color',
			 *		'product:condition'               => 'product_condition',
			 *		'product:depth:value'             => 'product_depth_value',
			 *		'product:depth:units'             => '',
			 *		'product:ean'                     => 'product_gtin13',
			 *		'product:expiration_time'         => '',
			 *		'product:gtin14'                  => 'product_gtin14',
			 *		'product:gtin13'                  => 'product_gtin13',
			 *		'product:gtin12'                  => 'product_gtin12',
			 *		'product:gtin8'                   => 'product_gtin8',
			 *		'product:gtin'                    => 'product_gtin',
			 *		'product:height:value'            => 'product_height_value',
			 *		'product:height:units'            => '',
			 *		'product:is_product_shareable'    => '',
			 *		'product:isbn'                    => 'product_isbn',
			 *		'product:length:value'            => 'product_length_value',
			 *		'product:length:units'            => '',
			 *		'product:material'                => 'product_material',
			 *		'product:mfr_part_no'             => 'product_mfr_part_no',
			 *		'product:original_price:amount'   => '',
			 *		'product:original_price:currency' => '',
			 *		'product:pattern'                 => '',
			 *		'product:plural_title'            => '',
			 *		'product:pretax_price:amount'     => '',
			 *		'product:pretax_price:currency'   => '',
			 *		'product:price:amount'            => 'product_price',
			 *		'product:price:currency'          => 'product_currency',
			 *		'product:product_link'            => '',
			 *		'product:purchase_limit'          => '',
			 *		'product:retailer'                => '',
			 *		'product:retailer_category'       => '',
			 *		'product:retailer_item_id'        => '',
			 *		'product:retailer_part_no'        => 'product_retailer_part_no',
			 *		'product:retailer_title'          => '',
			 *		'product:sale_price:amount'       => '',
			 *		'product:sale_price:currency'     => '',
			 *		'product:sale_price_dates:start'  => '',
			 *		'product:sale_price_dates:end'    => '',
			 *		'product:shipping_cost:amount'    => '',
			 *		'product:shipping_cost:currency'  => '',
			 *		'product:shipping_weight:value'   => '',
			 *		'product:shipping_weight:units'   => '',
			 *		'product:size'                    => 'product_size',
			 *		'product:target_gender'           => 'product_target_gender',
			 *		'product:upc'                     => 'product_gtin12',
			 *		'product:volume:value'            => 'product_volume_value',
			 *		'product:volume:units'            => '',
			 *		'product:weight:value'            => 'product_weight_value',
			 *		'product:weight:units'            => '',
			 *		'product:width:value'             => 'product_width_value',
			 *		'product:width:units'             => '',
			 *	)
			 */
			$og_type_mt_md = $this->p->cf[ 'head' ][ 'og_type_mt' ][ $type_id ];

			foreach ( $og_type_mt_md as $mt_name => $md_key ) {

				/**
				 * Use a custom value if one is available - ignore empty strings and 'none'.
				 */
				if ( ! empty( $md_key ) && isset( $md_opts[ $md_key ] ) && $md_opts[ $md_key ] !== '' ) {

					if ( $md_opts[ $md_key ] === 'none' ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'unsetting ' . $mt_name . ': ' . $md_key . ' metadata is "none"' );
						}

						unset( $mt_og[ $mt_name ] );

					/**
					 * Check for meta data and meta tags that require a unit value.
					 *
					 * Example: 
					 *
					 *	'product:depth:value'  => 'product_depth_value',
					 *	'product:height:value' => 'product_height_value',
					 *	'product:length:value' => 'product_length_value',
					 *	'product:volume:value' => 'product_volume_value',
					 *	'product:weight:value' => 'product_weight_value',
					 *	'product:width:value'  => 'product_width_value',
					 */
					} elseif ( preg_match( '/^.*_([^_]+)_value$/', $md_key, $unit_match ) &&
						preg_match( '/^(.*):value$/', $mt_name, $mt_match ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $mt_name . ' from metadata = ' . $md_opts[ $md_key ] );
						}

						$mt_og[ $mt_name ] = $md_opts[ $md_key ];

						$mt_units = $mt_match[ 1 ] . ':units';

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'checking for ' . $mt_units . ' unit text' );
						}

						if ( isset( $og_type_mt_md[ $mt_units ] ) ) {

							if ( $unit_text = WpssoSchema::get_data_unit_text( $unit_match[ 1 ] ) ) {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $mt_units . ' from unit text = ' . $unit_text );
								}

								$mt_og[ $mt_units ] = $unit_text;
							}
						}

					/**
					 * Do not define units by themselves - define units when we define the value.
					 */
					} elseif ( preg_match( '/_units$/', $md_key ) ) {

						continue;	// Get the next meta data key.

					} else {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $mt_name . ' from metadata = ' . $md_opts[ $md_key ] );
						}

						$mt_og[ $mt_name ] = $md_opts[ $md_key ];
					}

				} elseif ( isset( $mt_og[ $mt_name ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $mt_name . ' value kept = ' . $mt_og[ $mt_name ] );
					}

				} elseif ( isset( $this->p->options[ 'og_def_' . $md_key ] ) ) {

					if ( $this->p->options[ 'og_def_' . $md_key ] !== 'none' ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $mt_name . ' from options default = ' . $this->p->options[ 'og_def_' . $md_key ] );
						}

						$mt_og[ $mt_name ] = $this->p->options[ 'og_def_' . $md_key ];
					}

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $mt_name . ' = null' );
					}

					$mt_og[ $mt_name ] = null;	// Use null so isset() returns false.
				}
			}
		}

		/**
		 * If we have a GTIN number, try to improve the assigned property name. Pass $mt_og by reference to modify the
		 * array directly. A similar method exists as WpssoSchema::check_gtin_prop_value().
		 */
		public static function check_gtin_mt_value( &$mt_og, $prefix = 'product' ) {	// Pass by reference is OK.

			if ( ! empty( $mt_og[ $prefix . ':gtin' ] ) ) {

				/**
				 * The value may come from a custom field, so trim it, just in case.
				 */
				$mt_og[ $prefix . ':gtin' ] = trim( $mt_og[ $prefix . ':gtin' ] );

				$gtin_len = strlen( $mt_og[ $prefix . ':gtin' ] );

				switch ( $gtin_len ) {

					case 13:

						if ( empty( $mt_og[ $prefix . ':ean' ] ) ) {
							$mt_og[ $prefix . ':ean' ] = $mt_og[ $prefix . ':gtin' ];
						}

						break;

					case 12:

						if ( empty( $mt_og[ $prefix . ':upc' ] ) ) {
							$mt_og[ $prefix . ':upc' ] = $mt_og[ $prefix . ':gtin' ];
						}

						break;
				}
			}
		}

		private function update_post_md_opts( &$md_opts, $post_id, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Check if the post type or Schema type requires a specific hard-coded Open Graph type.
			 */
			if ( empty( $md_opts[ 'og_type:is' ] ) ) {

				if ( $og_type = $this->p->post->get_post_type_og_type( $mod ) ) {

					$md_opts[ 'og_type' ]    = $og_type;
					$md_opts[ 'og_type:is' ] = 'disabled';

				} else {

					if ( isset( $md_opts[ 'schema_type' ] ) ) {
						$type_id = $md_opts[ 'schema_type' ];
					} else {
						$type_id = $this->p->schema->get_mod_schema_type( $mod, $get_id = true, $use_mod_opts = false );
					}

					if ( $og_type = $this->p->schema->get_schema_type_og_type( $type_id ) ) {

						$md_opts[ 'og_type' ]    = $og_type;
						$md_opts[ 'og_type:is' ] = 'disabled';
					}
				}
			}

			return $md_opts;
		}
	}
}
