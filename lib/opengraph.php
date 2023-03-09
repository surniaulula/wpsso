<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoOpenGraph' ) ) {

	class WpssoOpenGraph {

		private $p;	// Wpsso class object.
		private $ns;	// WpssoOpenGraphNS class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Instantiate the WpssoOpenGraphNS class object.
			 */
			if ( ! class_exists( 'WpssoOpenGraphNS' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/opengraph-ns.php';
			}

			$this->ns = new WpssoOpenGraphNS( $plugin );

			$this->p->util->add_plugin_filters( $this, array(
				'plugin_image_sizes' => 1,
			) );

			$this->p->util->add_plugin_filters( $this, array(
				'get_post_options' => 3,
			), PHP_INT_MAX );
		}

		public function filter_plugin_image_sizes( array $sizes ) {

			$sizes[ 'og' ] = array(		// Option prefix.
				'name'         => 'opengraph',
				'label_transl' => _x( 'Open Graph (Facebook and oEmbed)', 'option label', 'wpsso' ),
			);

			return $sizes;
		}

		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			if ( is_admin() ) {	// Keep processing on the front-end to a minimum.

				/*
				 * If the Open Graph type isn't already hard-coded (ie. ':disabled'), then using the post type and
				 * the Schema type, check for a possible hard-coded Open Graph type.
				 */
				$md_opts = $this->maybe_update_post_og_type( $md_opts, $post_id, $mod );
			}

			return $md_opts;
		}

		/*
		 * Since WPSSO Core v9.13.0.
		 *
		 * Returns the open graph type id.
		 */
		public function get_mod_og_type_id( array $mod, $use_md_opts = true ) {

			return $this->get_mod_og_type( $mod, $get_id = true, $use_md_opts );
		}

		/*
		 * Since WPSSO Core v9.13.0.
		 *
		 * Returns the open graph namespace.
		 */
		public function get_mod_og_type_ns( array $mod, $use_md_opts = true ) {

			return $this->get_mod_og_type( $mod, $get_id = false, $use_md_opts );
		}

		/*
		 * Since WPSSO Core v4.10.0.
		 *
		 * Returns the open graph type id by default.
		 *
		 * Example: article, product, place, etc.
		 *
		 * Use $get_id = false to return the open graph namespace instead of the ID.
		 */
		public function get_mod_og_type( array $mod, $get_id = true, $use_md_opts = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			$cache_salt = false;

			/*
			 * Archive pages can call this method several times.
			 *
			 * Optimize and cache post/term/user og type values.
			 */
			if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

				/*
				 * Note that SucomUtil::get_mod_salt() does not include the page number or locale.
				 */
				$cache_salt = SucomUtil::get_mod_salt( $mod ) . '_get:' . (string) $get_id . '_use:' . (string) $use_md_opts;

				if ( isset( $local_cache[ $cache_salt ] ) ) {

					return $local_cache[ $cache_salt ];

				}
			}

			$type_id    = null;
			$og_type_ns = $this->p->cf[ 'head' ][ 'og_type_ns' ];

			/*
			 * Maybe get a custom open graph type id from the post, term, or user meta.
			 */
			if ( $use_md_opts ) {

				if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

					$type_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'og_type' );	// Returns null if index key not found.

					if ( empty( $type_id ) || $type_id === 'none' || empty( $og_type_ns[ $type_id ] ) ) {	// Check for an invalid type id.

						$type_id = null;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'custom type id = ' . $type_id );
					}
				}
			}

			$is_custom = empty( $type_id ) ? false : true;

			if ( ! $is_custom ) {	// No custom open graph type id from the post, term, or user meta.

				/*
				 * Similar module type logic can be found in the following methods:
				 *
				 * See WpssoOpenGraph->get_mod_og_type().
				 * See WpssoPage->get_description().
				 * See WpssoPage->get_the_title().
				 * See WpssoSchema->get_mod_schema_type().
				 * See WpssoUtil->get_canonical_url().
				 */
				if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

					if ( $mod[ 'is_home_page' ] ) {	// Static front page (singular post).

						$type_id = $this->get_og_type_id_for( 'home_page' );

					} else {

						$type_id = $this->get_og_type_id_for( 'home_posts' );
					}

				} elseif ( $mod[ 'is_comment' ] ) {

					if ( is_numeric( $mod[ 'comment_rating' ] ) ) {

						$type_id = $this->get_og_type_id_for( 'comment_review' );

					} elseif ( $mod[ 'comment_parent' ] ) {

						$type_id = $this->get_og_type_id_for( 'comment_reply' );

					} else {

						$type_id = $this->get_og_type_id_for( 'comment' );
					}

				} elseif ( $mod[ 'is_post' ] ) {

					if ( $mod[ 'post_type' ] ) {	// Just in case.

						if ( $mod[ 'is_post_type_archive' ] ) {	// The post ID may be 0.

							$type_id = $this->get_og_type_id_for( 'pta_' . $mod[ 'post_type' ] );

							if ( empty( $type_id ) ) {	// Just in case.

								$type_id = $this->get_og_type_id_for( 'archive_page' );
							}

						} else {

							$type_id = $this->get_og_type_id_for( $mod[ 'post_type' ] );

							if ( empty( $type_id ) ) {	// Just in case.

								$type_id = $this->get_og_type_id_for( 'page' );
							}
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no post type' );
					}

				} elseif ( $mod[ 'is_term' ] ) {

					if ( ! empty( $mod[ 'tax_slug' ] ) ) {	// Just in case.

						$type_id = $this->get_og_type_id_for( 'tax_' . $mod[ 'tax_slug' ] );
					}

					if ( empty( $type_id ) ) {	// Just in case.

						$type_id = $this->get_og_type_id_for( 'archive_page' );
					}

				} elseif ( $mod[ 'is_user' ] ) {

					$type_id = $this->get_og_type_id_for( 'user_page' );

				} elseif ( $mod[ 'is_search' ] ) {

					$type_id = $this->get_og_type_id_for( 'search_page' );

				} elseif ( $mod[ 'is_archive' ] ) {

					$type_id = $this->get_og_type_id_for( 'archive_page' );
				}

				if ( empty( $type_id ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'unable to determine og type id (using default)' );
					}

					$type_id = 'website';
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'og type id before filter = ' . $type_id );
			}

			$type_id = apply_filters( 'wpsso_og_type', $type_id, $mod, $is_custom );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'og type id after filter = ' . $type_id );
			}

			$get_value = false;

			if ( empty( $type_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: og type id is empty' );
				}

			} elseif ( 'none' === $type_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: og type id is disabled' );
				}

			} elseif ( ! isset( $og_type_ns[ $type_id ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: og type id ' . $type_id . ' is unknown' );
				}

			} elseif ( ! $get_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning og type id namespace: ' . $og_type_ns[ $type_id ] );
				}

				$get_value = $og_type_ns[ $type_id ];

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning og type id: ' . $type_id );
				}

				$get_value = $type_id;
			}

			/*
			 * Optimize and cache post/term/user og type values.
			 */
			if ( $cache_salt ) {

				$local_cache[ $cache_salt ] = $get_value;
			}

			return $get_value;
		}

		/*
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 *
		 * $size_name is passed as-is to WpssoMedia->get_all_images().
		 */
		public function get_array( array $mod, $size_names = 'opengraph', $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$max_nums = $this->p->util->get_max_nums( $mod );

			/*
			 * 'wpsso_og_seed' is hooked by e-commerce modules to provide product meta tags.
			 */
			$mt_og = (array) apply_filters( 'wpsso_og_seed', SucomUtil::get_mt_og_seed(), $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'og_seed', $mt_og );
			}

			/*
			 * Facebook app id meta tag.
			 */
			if ( ! isset( $mt_og[ 'fb:app_id' ] ) ) {

				$mt_og[ 'fb:app_id' ] = $this->p->options[ 'fb_app_id' ];
			}

			/*
			 * Type id meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:type' ] ) ) {

				$mt_og[ 'og:type' ] = $this->get_mod_og_type_id( $mod );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'og:type already defined = ' . $mt_og[ 'og:type' ] );
			}

			$type_id = $mt_og[ 'og:type' ];

			/*
			 * URL meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:url' ] ) ) {

				$mt_og[ 'og:url' ] = $this->p->util->get_canonical_url( $mod, $add_page = true );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'og:url already defined = ' . $mt_og[ 'og:url' ] );
			}

			/*
			 * Redirect URL meta tag (non-standard / internal meta tag).
			 */
			if ( $this->p->util->is_redirect_enabled() ) {

				$mt_og[ 'og:redirect_url' ] = $this->p->util->get_redirect_url( $mod );
			}

			/*
			 * Locale meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:locale' ] ) ) {

				$mt_og[ 'og:locale' ] = $this->get_fb_locale( $mod );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'og:locale already defined = ' . $mt_og[ 'og:locale' ] );
			}

			/*
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

			/*
			 * Title meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:title' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting title for og:title meta tag' );
				}

				$mt_og[ 'og:title' ] = $this->p->page->get_title( $mod, $md_key = 'og_title', $max_len = 'og_title' );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'og:title value = ' . $mt_og[ 'og:title' ] );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'og:title already defined = ' . $mt_og[ 'og:title' ] );
			}

			/*
			 * Description meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:description' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting description for og:description meta tag' );
				}

				$mt_og[ 'og:description' ] = $this->p->page->get_description( $mod, $md_key = 'og_desc', $max_len = 'og_desc', $num_hashtags = true );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'og:description value = ' . $mt_og[ 'og:description' ] );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'og:description already defined = ' . $mt_og[ 'og:description' ] );
			}

			/*
			 * Updated date / time meta tag.
			 */
			if ( ! isset( $mt_og[ 'og:updated_time' ] ) ) {

				if ( $mod[ 'is_post' ] && $mod[ 'post_modified_time' ] ) {	// ISO 8601 date or false.

					$mt_og[ 'og:updated_time' ] = $mod[ 'post_modified_time' ];
				}
			}

			/*
			 * Get all videos.
			 *
			 * Call before getting all images to find / use preview images.
			 */
			if ( ! isset( $mt_og[ 'og:video' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting videos for og:video meta tag' );
				}

				if ( ! $this->p->check->pp() ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no video modules available' );
					}

				} elseif ( $max_nums[ 'og_vid_max' ] > 0 ) {

					$mt_og[ 'og:video' ] = $this->p->media->get_all_videos( $max_nums[ 'og_vid_max' ], $mod, $md_pre );

					if ( empty( $mt_og[ 'og:video' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no videos returned' );
						}

						unset( $mt_og[ 'og:video' ] );

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'removing video images to avoid duplicates' );
						}

						foreach ( $mt_og[ 'og:video' ] as $key => $mt_single ) {

							if ( ! is_array( $mt_single ) ) {	// Just in case.

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'video ignored: $mt_single is not an array' );
								}

								continue;
							}

							$mt_og[ 'og:video' ][ $key ] = SucomUtil::preg_grep_keys( '/^og:image/', $mt_single, $invert = true );
						}
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'videos disabled: maximum videos is 0 or less' );
					}
				}
			}

			/*
			 * Get all images.
			 */
			if ( ! isset( $mt_og[ 'og:image' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting images for og:image meta tag' );
				}

				if ( $max_nums[ 'og_img_max' ] > 0 ) {

					$mt_og[ 'og:image' ] = $this->p->media->get_all_images( $max_nums[ 'og_img_max' ], $size_names, $mod, $md_pre );

					if ( empty( $mt_og[ 'og:image' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no images returned' );
						}

						unset( $mt_og[ 'og:image' ] );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipped getting images: maximum images is 0 or less' );
					}
				}
			}

			/*
			 * Pre-define some basic open graph meta tags for this og:type. If the meta tag has an associated meta
			 * option name, then read it's value from the meta options.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'checking og_type_mt array for known meta tags and md options' );
			}

			/*
			 * An array of Open Graph types, their meta tags, and their associated metadata keys.
			 */
			if ( isset( $this->p->cf[ 'head' ][ 'og_type_mt' ][ $type_id ] ) ) {	// Check if og:type is known.

				/*
				 * Optimize and call get_options() only once. Returns an empty string if no meta found.
				 */
				if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

					$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				} else $md_opts = array();

				/*
				 * Add post/term/user meta data to the Open Graph meta tags.
				 */
				$this->add_data_og_type_md( $mt_og, $type_id, $md_opts );
			}

			/*
			 * If the module is a post object, define the author, publishing date, etc. These values may still be used
			 * by other non-article filters, and if the og:type is not an article, the meta tags will be sanitized (ie.
			 * non-valid meta tags removed) at the end of WpssoHead->get_head_array().
			 */
			if ( ! isset( $mt_og[ 'article:author' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting names / URLs for article:author meta tags' );
				}

				$mt_og[ 'article:author' ] = array();

				if ( $mod[ 'is_post' ] ) {

					if ( $mod[ 'post_author' ] ) {

						$mt_og[ 'article:author:name' ] = $this->p->user->get_author_meta( $mod[ 'post_author' ], 'display_name' );
						$mt_og[ 'article:author' ]      = $this->p->user->get_authors_websites( $mod[ 'post_author' ], $meta_key = 'facebook' );
					}

					if ( $mod[ 'post_coauthors' ] ) {

						$og_profile_urls = $this->p->user->get_authors_websites( $mod[ 'post_coauthors' ], $meta_key = 'facebook' );

						$mt_og[ 'article:author' ] = array_merge( $mt_og[ 'article:author' ], $og_profile_urls );
					}

				} elseif ( $mod[ 'is_comment' ] ) {

					if ( $mod[ 'comment_author' ] ) {

						$mt_og[ 'article:author:name' ] = $this->p->user->get_author_meta( $mod[ 'comment_author' ], 'display_name' );
						$mt_og[ 'article:author' ]      = $this->p->user->get_authors_websites( $mod[ 'comment_author' ], $meta_key = 'facebook' );

					} elseif ( $mod[ 'comment_author_name' ] ) {

						$mt_og[ 'article:author:name' ] = $mod[ 'comment_author_name' ];
					}
				}
			}

			if ( ! isset( $mt_og[ 'article:publisher' ] ) ) {

				$mt_og[ 'article:publisher' ] = SucomUtil::get_key_value( 'fb_publisher_url', $this->p->options, $mod );
			}

			if ( ! isset( $mt_og[ 'article:tag' ] ) ) {

				$mt_og[ 'article:tag' ] = $this->p->page->get_tag_names( $mod );
			}

			if ( ! isset( $mt_og[ 'article:published_time' ] ) ) {

				if ( $mod[ 'is_post' ] && $mod[ 'post_time' ] ) {	// ISO 8601 date or false.

					switch ( $mod[ 'post_status' ] ) {

						case 'auto-draft':
						case 'draft':
						case 'future':
						case 'inherit':	// Post revision.
						case 'pending':
						case 'trash':

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipping article published time for post status ' .  $mod[ 'post_status' ] );
							}

							break;

						case 'expired':	// Previously published.
						case 'private':
						case 'publish':
						default:	// Any other post status.

							$mt_og[ 'article:published_time' ] = $mod[ 'post_time' ];

							break;
					}

				} elseif ( $mod[ 'is_comment' ] && $mod[ 'comment_time' ] ) {	// ISO 8601 date or false.

					$mt_og[ 'article:published_time' ] = $mod[ 'comment_time' ];
				}
			}

			if ( ! isset( $mt_og[ 'article:modified_time' ] ) ) {

				if ( $mod[ 'is_post' ] && $mod[ 'post_modified_time' ] ) {	// ISO 8601 date or false.

					$mt_og[ 'article:modified_time' ] = $mod[ 'post_modified_time' ];
				}
			}

			if ( ! isset( $mt_og[ 'article:reading_mins' ] ) ) {

				$mt_og[ 'article:reading_mins' ] = $this->p->page->get_reading_mins( $mod );
			}

			if ( ! empty( $this->p->cf[ 'head' ][ 'og_type_ns' ][ $type_id ] ) ) {

				$og_ns = $this->p->cf[ 'head' ][ 'og_type_ns' ][ $type_id ];	// Example: https://ogp.me/ns/product#

				$filter_name = 'wpsso_og_data_' . SucomUtil::sanitize_hookname( $og_ns );

				$mt_og = (array) apply_filters( $filter_name, $mt_og, $mod );
			}

			$mt_og = (array) apply_filters( 'wpsso_og', $mt_og, $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mt_og', $mt_og );
			}

			return $mt_og;
		}

		public function get_og_type_id_for( $opt_suffix, $default_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'opt_suffix' => $opt_suffix,
					'default_id' => $default_id,
				) );
			}

			if ( empty( $opt_suffix ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: opt_suffix is empty' );
				}

				return $default_id;	// Just in case.
			}

			$og_type_ns = $this->p->cf[ 'head' ][ 'og_type_ns' ];
			$opt_key    = 'og_type_for_' . $opt_suffix;
			$type_id    = isset( $this->p->options[ $opt_key ] ) ? $this->p->options[ $opt_key ] : $default_id;

			if ( empty( $type_id ) || $type_id === 'none' ) {

				$type_id = $default_id;

			} elseif ( empty( $og_type_ns[ $type_id ] ) ) {

				$type_id = $default_id;
			}

			return $type_id;
		}

		public function get_og_types_select() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$og_type_ns = $this->p->cf[ 'head' ][ 'og_type_ns_compat' ];

			$select = array();

			foreach ( $og_type_ns as $type_id => $type_ns ) {

				$type_url  = preg_replace( '/(^.*\/\/|#$)/', '', $type_ns );
				$type_name = preg_replace( '/(^.*\/ns\/|#$)/U', '', $type_url );

				switch ( $this->p->options[ 'plugin_og_types_select_format' ] ) {

					case 'id':

						$select[ $type_id ] = $type_id;

						break;

					case 'id_url':

						$select[ $type_id ] = $type_id . ' | ' . $type_url;

						break;

					case 'id_name':

						$select[ $type_id ] = $type_id . ' | ' . $type_name;

						break;

					case 'name_id':

						$select[ $type_id ] = $type_name . ' [' . $type_id . ']';

						break;

					default:

						$select[ $type_id ] = $type_name;

						break;
				}
			}

			if ( defined( 'SORT_STRING' ) ) {	// Just in case.

				asort( $select, SORT_STRING );

			} else {

				asort( $select );
			}

			return $select;
		}

		/*
		 * Returns an optional and customized locale value for the og:locale meta tag.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public function get_fb_locale( $mixed = 'current', $use_opts = true ) {

			/*
			 * Check for customized locale.
			 */
			if ( $use_opts && ! empty( $this->p->options ) ) {

				$fb_locale_key = SucomUtil::get_key_locale( 'fb_locale', $this->p->options, $mixed );

				if ( ! empty( $this->p->options[ $fb_locale_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'returning "' . $this->p->options[ $fb_locale_key ] . '" ' .
							'locale for "' . $fb_locale_key . '" option key' );
					}

					return $this->p->options[ $fb_locale_key ];
				}
			}

			/*
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

			/*
			 * Fix known exceptions.
			 */
			switch ( $locale ) {

				case 'de_DE_formal':

					$locale = 'de_DE';

					break;
			}

			/*
			 * Return the Facebook equivalent for this WordPress locale.
			 */
			$fb_languages = SucomUtil::get_publisher_languages( 'facebook' );

			if ( ! empty( $fb_languages[ $locale ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning facebook locale "' . $locale . '"' );
				}

				return $locale;

			}

			/*
			 * Fallback to the default WordPress locale.
			 */
			$def_locale  = SucomUtil::get_locale( 'default' );

			if ( ! empty( $fb_languages[ $def_locale ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning default locale "' . $def_locale . '"' );
				}

				return $def_locale;

			}

			/*
			 * Fallback to en_US.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning fallback locale "en_US"' );
			}

			return 'en_US';
		}

		/*
		 * Since WPSSO Core v15.0.0.
		 *
		 * See WpssoCmcfActions->check_product_image_urls().
		 * See WpssoCmcfXml->add_product_images().
		 * See WpssoGmfActions->check_product_image_urls().
		 * See WpssoGmfXml->add_product_images().
		 */
		public function get_product_retailer_item_image_urls( array $mt_single, $size_names = 'opengraph', $md_pre = 'og' ) {

			$mt_images = $this->get_product_retailer_item_images( $mt_single, $size_names, $md_pre );

			$image_urls = array();

			if ( is_array( $mt_images ) ) {	// Just in case.

				foreach ( $mt_images as $mt_image ) {

					if ( $image_url = SucomUtil::get_first_og_image_url( $mt_image ) ) {

						$image_urls[] = $image_url;
					}
				}
			}

			return $image_urls;
		}

		/*
		 * Since WPSSO Core v15.0.0.
		 *
		 * WpssoOpenGraph->get_product_retailer_item_image_urls().
		 */
		public function get_product_retailer_item_images( array $mt_single, $size_names = 'opengraph', $md_pre = 'og' ) {

			$mt_images = array();

			if ( isset( $mt_single[ 'og:image' ] ) && is_array( $mt_single[ 'og:image' ] ) ) {	// Nothing to do.

				$mt_images = $mt_single[ 'og:image' ];

			} elseif ( $mod = $this->p->og->get_product_retailer_item_mod( $mt_single ) ) {

				$max_nums = $this->p->util->get_max_nums( $mod, 'og' );

				$mt_images = $this->p->media->get_all_images( $max_nums[ 'og_img_max' ], $size_names, $mod, $md_pre );
			}

			return $mt_images;
		}

		/*
		 * Since WPSSO Core v15.0.0.
		 *
		 * See WpssoCmcfActions->check_product_image_urls().
		 * See WpssoGmfActions->check_product_image_urls().
		 * See WpssoOpenGraph->get_product_retailer_item_images().
		 * See WpssoSchema::add_offers_aggregate_data_mt().
		 * See WpssoSchema::add_offers_data_mt().
		 * See WpssoSchema::add_variants_data_mt().
		 */
		public function get_product_retailer_item_mod( array $mt_single ) {

			if ( ! empty( $mt_single[ 'product:retailer_item_id' ] ) && is_numeric( $mt_single[ 'product:retailer_item_id' ] ) ) {

				$mod = $this->p->post->get_mod( $mt_single[ 'product:retailer_item_id' ] );

				return empty( $mod[ 'id' ] ) ? false : $mod;	// Just in case.
			}

			return false;
		}

		/*
		 * Returns a string.
		 *
		 * Used for the 'product:retailer_category' meta tag value.
		 */
		public function get_product_retailer_category( $mod ) {

			$retailer_categories = $this->get_product_retailer_categories( $mod, $lists_max = 1 );

			return isset( $retailer_categories[ 0 ] ) ? $retailer_categories[ 0 ] : '';
		}

		/*
		 * Returns an array of strings.
		 */
		public function get_product_retailer_categories( $mod, $lists_max = 1 ) {

			/*
			 * Returns an associative array of term IDs and their names or objects.
			 *
			 * If the custom primary or default term ID exists in the post terms array, it will be moved to the top.
			 */
			$post_terms = $this->p->post->get_primary_terms( $mod, $tax_slug = 'category', $output = 'objects' );

			/*
			 * The 'wpsso_primary_tax_slug' filter is hooked by the WooCommerce integration module.
			 */
			$primary_tax_slug = apply_filters( 'wpsso_primary_tax_slug', $tax_slug = 'category', $mod );

			$category_names = array();

			foreach ( $post_terms as $parent_term_obj ) {

				$parent_term_id = $parent_term_obj->term_id;

				$ancestor_ids = get_ancestors( $parent_term_id, $primary_tax_slug, 'taxonomy' );

				if ( empty( $ancestor_ids ) || ! is_array( $ancestor_ids ) ) {

					$ancestor_ids = array( $parent_term_id );	// Just do the parent term.

				} else {

					$ancestor_ids = array_reverse( $ancestor_ids );	// Add ancestors in reverse order.

					$ancestor_ids[] = $parent_term_id;	// Add parent term last.
				}

				foreach ( $ancestor_ids as $term_id ) {

					$term_mod = $this->p->term->get_mod( $term_id );

					/*
					 * Use $title_sep = false to avoid adding term parent names in the term title.
					 *
					 * $md_key = 'schema_title_bc' will use array( 'schema_title_bc', 'schema_title_alt', 'schema_title', 'seo_title' )
					 */
					$category_names[ $parent_term_id ][ $term_id ] = $this->p->page->get_title( $term_mod,
						$md_key = 'schema_title_bc', $max_len = 'schema_title_bc', $title_sep = false );
				}
			}

			if ( $lists_max ) {

				$category_names = array_slice( $category_names, 0, $lists_max );
			}

			$retailer_categories = array();

			foreach ( $category_names as $parent_term_id => $term_ids ) {

				$retailer_categories[] = implode( ' > ', $term_ids );
			}

			return $retailer_categories;
		}

		/*
		 * Called by WpssoHead->get_head_array() before merging all meta tag arrays.
		 *
		 * Unset mis-matched og_type meta tags using the 'og_type_mt' array as a reference. For example, remove all
		 * 'article' meta tags if the og_type is 'website'. Removing only known meta tags (using the 'og_type_mt' array as
		 * a reference) protects internal meta tags that may be used later by WpssoHead->extract_head_info(). For
		 * example, the schema:type:id and p:image meta tags.
		 *
		 * The 'og_content_map' array is also checked for Schema values that need to be swapped for simpler Open Graph meta
		 * tag values.
		 */
		public function sanitize_mt_array( array $mt_og ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Array of meta tags to allow, reject, and map.
			 */
			static $og_allow    = null;
			static $og_reject   = null;
			static $content_map = null;

			if ( null === $og_allow ) {	// Define the static variables once.

				/*
				 * The og:type is only needed when first run, to define the allow, reject, and map arrays.
				 */
				if ( empty( $mt_og[ 'og:type' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: og:type is empty and required for sanitation' );
					}

					return $mt_og;
				}

				$og_type     = $mt_og[ 'og:type' ];
				$og_allow    = array();
				$og_reject   = array();
				$content_map = array();

				/*
				 * An array of Open Graph types, their meta tags, and their associated metadata keys.
				 */
				foreach ( $this->p->cf[ 'head' ][ 'og_type_mt' ] as $type_id => $og_type_mt_md ) {

					/*
					 * An array of meta tags and their associated metadata keys.
					 */
					foreach ( $og_type_mt_md as $mt_name => $md_key ) {

						if ( $og_type === $type_id ) {

							$og_allow[ $mt_name ] = true;	// Mark meta tag as allowed.

							/*
							 * If we have a content map for the meta tag, save the content mapping array.
							 *
							 * 'product:availability' => array(
							 * 	'https://schema.org/BackOrder'           => 'available for order',
							 * 	'https://schema.org/Discontinued'        => 'discontinued',
							 * 	'https://schema.org/InStock'             => 'in stock',
							 * 	'https://schema.org/InStoreOnly'         => 'in stock',
							 * 	'https://schema.org/LimitedAvailability' => 'in stock',
							 * 	'https://schema.org/OnlineOnly'          => 'in stock',
							 * 	'https://schema.org/OutOfStock'          => 'out of stock',
							 * 	'https://schema.org/PreOrder'            => 'available for order',
							 * 	'https://schema.org/PreSale'             => 'available for order',
							 * 	'https://schema.org/SoldOut'             => 'out of stock',
							 * ),
							 */
							if ( ! empty( $this->p->cf[ 'head' ][ 'og_content_map' ][ $mt_name ] ) ) {

								/*
								 * Save the content mapping array.
								 */
								$content_map[ $mt_name ] = $this->p->cf[ 'head' ][ 'og_content_map' ][ $mt_name ];
							}

						} else $og_reject[ $mt_name ] = true;	// Mark meta tag as disallowed.
					}
				}

				foreach ( array( 'product:offers', 'product:variants' ) as $mt_name ) {

					if ( ! empty( $mt_og[ $mt_name ] ) && is_array( $mt_og[ $mt_name ] ) ) {

						foreach ( $mt_og[ $mt_name ] as $num => &$mt_single ) {	// Allow changes to the variation array.

							unset ( $mt_single[ 'product:brand' ] );	// Allow only a single brand value (in the parent product).

							foreach ( $mt_single as $mt_single_name => $mt_single_value ) {

								/*
								 * Avoid duplicate values (like prices) between the main product and its variations.
								 */
								if ( isset( $mt_og[ $mt_single_name ] ) && $mt_single_value === $mt_og[ $mt_single_name ] ) {

									/*
									 * Do not remove the currency if the amount has not been removed.
									 *
									 * Do not remove the units if the value has not been removed.
									 */
									if ( preg_match( '/^(.*:)(currency|units)$/', $mt_single_name, $matches ) ) {

										switch ( $matches[ 2 ] ) {

											case 'currency': $check_mt_name = $matches[ 1 ] . 'amount'; break;
											case 'units':    $check_mt_name = $matches[ 1 ] . 'value';  break;
											default:         $check_mt_name = null;
										}

										if ( $check_mt_name && isset( $mt_og[ $check_mt_name ] ) ) {

											continue;
										}
									}

									unset ( $mt_og[ $mt_single_name ] );
								}
							}
						}
					}
				}
			}

			/*
			 * Check the meta tag names and their values.
			 */
			foreach ( $mt_og as $key => $val ) {

				if ( ! empty( $og_allow[ $key ] ) ) {	// Meta tag is allowed - check it's value.

					/*
					 * If we have a matching value in the content map, then assign the mapped value.
					 *
					 * 'https://schema.org/BackOrder' to 'available for order' for example.
					 */
					if ( isset( $content_map[ $key ][ $val ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'mapping content value for ' . $key );
						}

						$mt_og[ $key ] = $content_map[ $key ][ $val ];
					}

				} elseif ( ! empty( $og_reject[ $key ] ) ) {	// Meta tag is disallowed - remove it.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'removing extra meta tag ' . $key );
					}

					unset( $mt_og[ $key ] );

				} elseif ( is_array( $val ) ) {

					$mt_og[ $key ] = $this->sanitize_mt_array( $val );
				}
			}

			return $mt_og;
		}

		/*
		 * Add post/term/user meta data to the Open Graph meta tags.
		 */
		public function add_data_og_type_md( array &$mt_og, $type_id, array $md_opts ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * An array of Open Graph types, their meta tags, and their associated metadata keys.
			 */
			if ( empty( $this->p->cf[ 'head' ][ 'og_type_mt' ][ $type_id ] ) ) {	// Just in case.

				return;
			}

			$og_type_mt_md = $this->p->cf[ 'head' ][ 'og_type_mt' ][ $type_id ];

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'loading og_type_mt array for type id ' . $type_id );
			}

			$md_keys_multi = WpssoConfig::get_md_keys_multi();	// Uses a local cache.

			foreach ( $og_type_mt_md as $mt_name => $md_key_pre ) {

				if ( empty( $md_key_pre ) ) {	// Most of the metadata keys are empty.

					continue;
				}

				$is_multi   = empty( $md_keys_multi[ $md_key_pre ] ) ? false : true;
				$multi_keys = $is_multi ? array_keys( SucomUtil::preg_grep_keys( '/^' . $md_key_pre . '_[0-9]+$/', $md_opts ) ) : array( $md_key_pre );

				foreach ( $multi_keys as $md_key ) {

					$values = array();

					if ( ! $this->add_data_og_type_md_values( $values, $type_id, $md_opts, $mt_name, $md_key ) ) {

						$def_md_key = $this->get_def_md_key( $md_key );

						if ( isset( $mt_og[ $mt_name ] ) ) {	// Meta tag exists and is not null.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( $mt_name . ' value kept = ' . $mt_og[ $mt_name ] );
							}

						} elseif ( ! empty( $def_md_key ) ) {	// Add default value from the plugin settings.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( $mt_name . ' from options = ' . $this->p->options[ $def_md_key ] );
							}

							$values[ $mt_name ] = $this->p->options[ $def_md_key ];

						} else {				// Add missing meta tag with null value.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( $mt_name . ' = null' );
							}

							$values[ $mt_name ] = null;	// Use null so isset() returns false.
						}
					}

					if ( ! empty( $values ) ) {

						foreach ( $values as $mt_name => $val ) {

							if ( 'none' === $val ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'unsetting ' . $mt_name . ' for value "none"' );
								}

								unset( $mt_og[ $mt_name ] );

							} elseif ( $is_multi ) {

								$mt_og[ $mt_name ][] = $val;

							} else {

								$mt_og[ $mt_name ] = $val;
							}
						}
					}
				}
			}
		}

		/*
		 * Check for a default value from the settings.
		 *
		 * Open Graph defaults have an 'og_def_' prefix, except for 'product_currency'.
		 *
		 * Schema defaults have a 'schema_def_' prefix.
		 */
		public function get_def_md_key( $md_key ) {

			$def_md_key = null;

			if ( 'product_currency' === $md_key ) {

				if ( isset( $this->p->options[ 'og_def_currency' ] ) ) {	// Just in case.

					$def_md_key = 'og_def_currency';
				}

			} elseif ( isset( $this->p->options[ 'og_def_' . $md_key ] ) ) {

				$def_md_key = 'og_def_' . $md_key;

			} elseif ( isset( $this->p->options[ 'schema_def_' . $md_key ] ) ) {

				$def_md_key = 'schema_def_' . $md_key;
			}

			return $def_md_key;
		}

		public function add_data_og_type_md_values( array &$values, $type_id, array $md_opts, $mt_name, $md_key ) {	// Pass by reference is OK.

			/*
			 * Use a custom value if one is available.
			 */
			if ( empty( $md_key ) || ! isset( $md_opts[ $md_key ] ) || '' === $md_opts[ $md_key ] ) {

				return false;
			}

			/*
			 * Check for meta tags that require a unit value and may need to be converted.
			 */
			if ( false !== strpos( $mt_name, ':value' ) && preg_match( '/^(.*):value$/', $mt_name, $mt_match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $mt_name . ' from metadata = ' . $md_opts[ $md_key ] );
				}

				$values[ $mt_name ] = $md_opts[ $md_key ];

				/*
				 * Check if the value meta tag needs a units meta tag.
				 */
				$mt_units_name = $mt_match[ 1 ] . ':units';

				/*
				 * An array of Open Graph types, their meta tags, and their associated metadata keys.
				 */
				$og_type_mt_md = $this->p->cf[ 'head' ][ 'og_type_mt' ][ $type_id ];

				if ( isset( $og_type_mt_md[ $mt_units_name ] ) ) {		// Value needs a units meta tag.

					$md_units_key = $og_type_mt_md[ $mt_units_name ];	// An empty string or metadata options key.

					$values[ $mt_units_name ] = $unit_text = WpssoSchema::get_unit_text( $md_key );

					if ( ! empty( $md_opts[ $md_units_key ] ) ) {		// Custom unit text found.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $mt_units_name . ' from metadata = ' . $md_opts[ $md_units_key ] );
						}

						$values[ $mt_units_name ] = $md_opts[ $md_units_key ];
					}

					/*
					 * Since WPSSO Core v14.0.0.
					 *
					 * Check if we need to convert the value.
					 */
					if ( $values[ $mt_units_name ] !== $unit_text ) {

						$unit_value = WpssoUtilUnits::get_convert( $values[ $mt_name ], $unit_text, $values[ $mt_units_name ] );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'converted ' . $values[ $mt_name ] . ' ' .
								$values[ $mt_units_name ] . ' to ' . $unit_value . ' ' . $unit_text );
						}

						$values[ $mt_name ] = $unit_value;

						$values[ $mt_units_name ] = $unit_text;
					}
				}

			/*
			 * Do not define units by themselves - define the units meta tag when we define the value.
			 */
			} elseif ( false !== strpos( $mt_name, ':units' ) ) {

				return;	// Get the next meta data key.

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $mt_name . ' from metadata = ' . $md_opts[ $md_key ] );
				}

				$values[ $mt_name ] = $md_opts[ $md_key ];
			}

			return true;
		}

		/*
		 * If we have a GTIN number, try to improve the assigned property name.
		 *
		 * Pass $mt_og by reference to modify the array directly.
		 *
		 * A similar method exists as WpssoSchema::check_prop_value_gtin().
		 */
		public static function check_mt_value_gtin( &$mt_og, $mt_pre = 'product' ) {	// Pass by reference is OK.

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking ' . $mt_pre . ' gtin value' );
			}

			if ( ! empty( $mt_og[ $mt_pre . ':gtin' ] ) ) {

				/*
				 * The value may come from a custom field, so trim it, just in case.
				 */
				$mt_og[ $mt_pre . ':gtin' ] = trim( $mt_og[ $mt_pre . ':gtin' ] );

				$gtin_len = strlen( $mt_og[ $mt_pre . ':gtin' ] );

				switch ( $gtin_len ) {

					case 13:

						if ( empty( $mt_og[ $mt_pre . ':ean' ] ) ) {

							$mt_og[ $mt_pre . ':ean' ] = $mt_og[ $mt_pre . ':gtin' ];
						}

						break;

					case 12:

						if ( empty( $mt_og[ $mt_pre . ':upc' ] ) ) {

							$mt_og[ $mt_pre . ':upc' ] = $mt_og[ $mt_pre . ':gtin' ];
						}

						break;
				}
			}
		}

		public static function check_mt_value_price( &$mt_og, $mt_pre = 'product' ) {	// Pass by reference is OK.

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking ' . $mt_pre . ' price value' );
			}

			$def_currency_key  = $mt_pre . ':price:currency';
			$def_currency      = empty( $mt_og[ $def_currency_key ] ) ? $wpsso->options[ 'og_def_currency' ] : $mt_og[ $def_currency_key ];
			$min_adv_price_key = $mt_pre . ':min_advert_price:amount';
			$min_adv_price     = empty( $mt_og[ $min_adv_price_key ] ) ? 0 : $mt_og[ $min_adv_price_key ];

			foreach ( array( 'min_advert_price', 'original_price', 'pretax_price', 'price', 'sale_price', 'shipping_cost' ) as $price_name ) {

				$amount_key   = $mt_pre . ':' . $price_name . ':amount';
				$currency_key = $mt_pre . ':' . $price_name . ':currency';

				if ( isset( $mt_og[ $amount_key ] ) ) {

					if ( is_numeric( $mt_og[ $amount_key ] ) ) {	// Allow for price of 0.

						if ( $min_adv_price && $mt_og[ $amount_key ] < $min_adv_price ) {

							$mt_og[ $mt_pre . ':price_type' ] = 'https://schema.org/MinimumAdvertisedPrice';

							$mt_og[ $amount_key ] = $min_adv_price;
						}

						if ( empty( $mt_og[ $currency_key ] ) ) {

							$mt_og[ $currency_key ] = $def_currency;
						}

					} else {

						if ( ! empty( $mt_og[ $amount_key ] ) ) {	// Non-empty string, array, etc.

							if ( $wpsso->debug->enabled ) {

								$wpsso->debug->log( 'invalid ' . $amount_key . ' value = ' . print_r( $mt_og[ $amount_key ], true ) );
							}
						}

						$mt_og[ $amount_key ]   = null;
						$mt_og[ $currency_key ] = null;
					}
				}
			}
		}

		public static function check_mt_value_energy_efficiency( &$mt_og, $mt_pre = 'product' ) {	// Pass by reference is OK.

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking ' . $mt_pre . ' energy efficiency value' );
			}

			if ( empty( $mt_og[ $mt_pre . ':energy_efficiency:value' ] ) || 'none' === $mt_og[ $mt_pre . ':energy_efficiency:value' ] ) {

				$mt_og[ $mt_pre . ':energy_efficiency:value' ]     = null;
				$mt_og[ $mt_pre . ':energy_efficiency:min_value' ] = null;
				$mt_og[ $mt_pre . ':energy_efficiency:max_value' ] = null;
			}
		}

		/*
		 * If the Open Graph type isn't already hard-coded (ie. ':disabled'), then using the post type and the Schema type,
		 * check for a possible hard-coded Open Graph type.
		 */
		private function maybe_update_post_og_type( array $md_opts, $post_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $md_opts[ 'og_type:disabled' ] ) ) {	// Just in case.

				/*
				 * Check if the post type matches a pre-defined Open Graph type.
				 *
				 * For example, a post type of 'organization' would return 'website' for the Open Graph type.
				 *
				 * Returns false or an Open Graph type string.
				 */
				if ( $og_type = $this->p->post->get_post_type_og_type( $mod ) ) {

					$md_opts[ 'og_type' ] = $og_type;

					$md_opts[ 'og_type:disabled' ] = true;

				} else {

					/*
					 * Use the saved Schema type or get the default Schema type.
					 */
					if ( isset( $md_opts[ 'schema_type' ] ) ) {

						$type_id = $md_opts[ 'schema_type' ];

					} else {

						$type_id = $this->p->schema->get_mod_schema_type_id( $mod, $use_md_opts = false );
					}

					/*
					 * Check if the Schema type matches a pre-defined Open Graph type.
					 */
					if ( $og_type = $this->p->schema->get_schema_type_og_type( $type_id ) ) {

						$md_opts[ 'og_type' ] = $og_type;

						$md_opts[ 'og_type:disabled' ] = true;
					}
				}
			}

			return $md_opts;
		}

		/*
		 * Deprecated on 2022/02/22.
		 */
		public function get_all_previews( $num, array $mod, $check_dupes = true, $md_pre = 'og', $force_prev = false ) {

			_deprecated_function( __METHOD__ . '()', '2022/02/22', $replacement = 'WpssoMedia::get_all_previews()' );	// Deprecation message.

			return $this->p->media->get_all_previews( $num, $mod, $md_pre, $force_prev );
		}

		/*
		 * Deprecated on 2022/02/22.
		 */
		public function get_all_videos( $num, array $mod, $check_dupes = true, $md_pre = 'og', $force_prev = false ) {

			_deprecated_function( __METHOD__ . '()', '2022/02/22', $replacement = 'WpssoMedia::get_all_videos()' );	// Deprecation message.

			return $this->p->media->get_all_videos( $num, $mod, $md_pre, $force_prev );
		}

		/*
		 * Deprecated on 2022/02/22.
		 */
		public function get_all_images( $num, $size_names, array $mod, $check_dupes = true, $md_pre = 'og' ) {

			_deprecated_function( __METHOD__ . '()', '2022/02/22', $replacement = 'WpssoMedia::get_all_images()' );	// Deprecation message.

			return $this->p->media->get_all_images( $num, $size_names, $mod, $md_pre );
		}

		/*
		 * Deprecated on 2022/02/22.
		 */
		public function get_media_info( $size_name, array $request, array $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			_deprecated_function( __METHOD__ . '()', '2022/02/22', $replacement = 'WpssoMedia::get_media_info()' );	// Deprecation message.

			return $this->p->media->get_media_info( $size_name, $request, $mod, $md_pre, $mt_pre );
		}
	}
}
