<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoPage' ) ) {

	class WpssoPage {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public function get_quote( array $mod ) {

			$quote_text = apply_filters( $this->p->lca . '_quote_seed', '', $mod );

			if ( ! empty( $quote_text ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'quote seed = "' . $quote_text . '"' );
				}
			} else {
				if ( has_excerpt( $mod[ 'id' ] ) ) {
					$quote_text = get_the_excerpt( $mod[ 'id' ] );
				} else {
					$quote_text = get_post_field( 'post_content', $mod[ 'id' ] );
				}
			}

			/**
			 * Remove shortcodes, etc., but don't strip html tags.
			 */
			$quote_text = $this->p->util->cleanup_html_tags( $quote_text, false );

			return apply_filters( $this->p->lca . '_quote', $quote_text, $mod );
		}

		/**
		 * $type   = 'title' | 'excerpt' | 'both'
		 * $mod    = true | false | post_id | array
		 * $md_key = true | false | string | array
		 */
		public function get_caption( $type = 'title', $max_len = 200, $mod = true, $read_cache = true,
			$add_hashtags = true, $do_encode = true, $md_key = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'type'         => $type,
					'max_len'      => $max_len,
					'mod'          => $mod,
					'read_cache'   => $read_cache,
					'add_hashtags' => $add_hashtags,	// true/false/numeric
					'do_encode'    => $do_encode,
					'md_key'       => $md_key,
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->p->util->get_page_mod( $mod );
			}

			$cap_text = false;

			$sep = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );

			if ( false === $md_key ) {	// false would return the complete meta array

				$md_key       = '';
				$md_key_title = '';
				$md_key_desc  = '';

			} elseif ( true === $md_key ) {	// true signals the use of the standard / fallback value

				switch ( $type ) {

					case 'title':

						$md_key       = 'og_title';
						$md_key_title = 'og_title';
						$md_key_desc  = 'og_desc';

						break;

					case 'excerpt':

						$md_key       = 'og_desc';
						$md_key_title = 'og_title';
						$md_key_desc  = 'og_desc';

						break;

					case 'both':

						$md_key       = 'og_caption';
						$md_key_title = 'og_title';
						$md_key_desc  = 'og_desc';

						break;
				}

			} else {	// $md_key could be a string or array

				$md_key_title = $md_key;
				$md_key_desc  = $md_key;
			}

			/**
			 * Check for custom caption if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && $md_key !== 'none' ) {

				$cap_text = $mod[ 'obj' ] ? $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key ) : null;

				list( $cap_text, $hashtags ) = $this->get_text_and_hashtags( $cap_text, $mod, false );

				if ( ! empty( $cap_text ) ) {

					if ( $max_len > 0 ) {

						$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'caption strlen before limit length ' . strlen( $cap_text ) .
								' (limiting to ' . $adj_max_len . ' chars)' );
						}

						$cap_text = $this->p->util->limit_text_length( $cap_text, $adj_max_len, '...', false );
					}
				}

				if ( ! empty( $hashtags ) ) {
					$cap_text = trim( $cap_text . ' ' . $hashtags );	// Trim in case text is empty.
				}

				if ( $this->p->debug->enabled ) {
					if ( empty( $cap_text ) ) {
						$this->p->debug->log( 'no custom caption found for md_key' );
					} else {
						$this->p->debug->log( 'custom caption = "' . $cap_text . '"' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom caption skipped: no md_key value' );
			}

			if ( empty( $cap_text ) ) {

				/**
				 * Request all values un-encoded, then encode once we have the complete caption text.
				 */
				switch ( $type ) {

					case 'title':

						$cap_text = $this->get_title( $max_len, '...', $mod, $read_cache, $add_hashtags, false, $md_key_title );

						break;

					case 'excerpt':

						$cap_text = $this->get_description( $max_len, '...', $mod, $read_cache, $add_hashtags, false, $md_key_desc );

						break;

					case 'both':

						/**
						 * Get the title first.
						 */
						$cap_text = $this->get_title( 0, '', $mod, $read_cache, false, false, $md_key_title );

						/**
						 * Add a separator between title and description.
						 */
						if ( ! empty( $cap_text ) ) {
							$cap_text .= ' ';
						}

						if ( ! empty( $sep ) ) {
							$cap_text .= $sep . ' ';
						}

						/**
						 * Reduce the requested $max_len by the caption length we already have.
						 */
						$adj_max_len = $max_len - strlen( $cap_text );

						$cap_text .= $this->get_description( $adj_max_len, '...', $mod, $read_cache, $add_hashtags, false, $md_key_desc );

						break;
				}
			}

			if ( true === $do_encode ) {
				$cap_text = SucomUtil::encode_html_emoji( $cap_text );
			} else {	// Just in case.
				$cap_text = html_entity_decode( SucomUtil::decode_utf8( $cap_text ), ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			return apply_filters( $this->p->lca . '_caption', $cap_text, $mod, $add_hashtags, $md_key );
		}

		/**
		 * $mod    = true | false | post_id | array
		 * $md_key = true | false | string | array
		 */
		public function get_title( $max_len = 70, $dots = '', $mod = false, $read_cache = true,
			$add_hashtags = false, $do_encode = true, $md_key = 'og_title', $sep = null ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'max_len'      => $max_len,
					'dots'         => $dots,
					'mod'          => $mod,
					'read_cache'   => $read_cache,
					'add_hashtags' => $add_hashtags,	// true/false/numeric
					'do_encode'    => $do_encode,
					'md_key'       => $md_key,
					'sep'          => $sep,
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->p->util->get_page_mod( $mod );
			}

			if ( false === $md_key ) {		// False would return the complete meta array.

				$md_key = '';

			} elseif ( true === $md_key ) {		// True signals use of the standard / fallback value.

				$md_key = array( 'og_title' );

			} elseif ( ! is_array( $md_key ) ) {	// Use fallback by default - get_options_multi() will do array_uniq().

				$md_key = array( $md_key, 'og_title' );
			}

			$title_text   = false;
			$paged_suffix = '';
			$filter_title = empty( $this->p->options[ 'plugin_filter_title' ] ) ? false : true;
			$filter_title = apply_filters( $this->p->lca . '_can_filter_title', $filter_title, $mod );

			if ( null === $sep ) {
				$sep = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			/**
			 * Setup filters to save and restore original / pre-filtered title value.
			 */
			if ( ! $filter_title ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'protecting filter value for wp_title' );
				}

				SucomUtil::protect_filter_value( 'wp_title', $auto_unprotect = true );
			}

			/**
			 * Check for custom title if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && $md_key !== 'none' ) {

				$title_text = is_object( $mod[ 'obj' ] ) ? $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $title_text ) ) {
						$this->p->debug->log( 'no custom title found for md_key' );
					} else {
						$this->p->debug->log( 'custom title = "' . $title_text . '"' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom title skipped: no md_key value' );
			}

			/**
			 * Get seed if no custom meta title.
			 */
			if ( empty( $title_text ) ) {

				$title_text = apply_filters( $this->p->lca . '_title_seed', '', $mod, $add_hashtags, $md_key, $sep );

				if ( ! empty( $title_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'title seed = "' . $title_text . '"' );
					}
				}
			}

			/**
			 * Check for hashtags in meta or seed title, remove and then add again after shorten.
			 */
			list( $title_text, $hashtags ) = $this->get_text_and_hashtags( $title_text, $mod, $add_hashtags );

			/**
			 * Construct a title of our own.
			 */
			if ( empty( $title_text ) ) {

				if ( $mod[ 'is_post' ] ) {

					if ( $mod[ 'is_post_type_archive' ] ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting the title for post type ' . $mod[ 'post_type' ] );
						}

						$post_type_obj = get_post_type_object( $mod[ 'post_type' ] );

						if ( ! empty( $post_type_obj->labels->menu_name ) ) {
							$title_text = sprintf( _x( '%s Archive', 'default title', 'wpsso' ), $post_type_obj->labels->menu_name );
						} elseif ( ! empty( $post_type_obj->name ) ) {
							$title_text = sprintf( _x( '%s Archive', 'default title', 'wpsso' ), $post_type_obj->name );
						}

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'before post_archive_title filter = ' . $title_text );
						}
	
						$title_text = apply_filters( $this->p->lca . '_post_archive_title', $title_text, $mod, $post_type_obj );

					} else {

						$title_text = html_entity_decode( get_the_title( $mod[ 'id' ] ) ) . ' ';

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' get_the_title() = "' . $title_text . '"' );
						}
					}

					if ( ! empty( $sep ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'adding separator "' . $sep . '" to title string' );
						}

						$title_text .= $sep . ' ';
					}

					$title_text = $this->p->util->safe_apply_filters( array( 'wp_title', $title_text, $sep, 'right' ), $mod );

				} elseif ( $mod[ 'is_term' ] ) {

					$term_obj = get_term( $mod[ 'id' ], $mod[ 'tax_slug' ] );

					if ( SucomUtil::is_category_page( $mod[ 'id' ] ) ) {

						$title_text = $this->get_category_title( $term_obj, $sep, $mod );

					} elseif ( isset( $term_obj->name ) ) {

						$title_text = $term_obj->name . ' ' . $sep . ' ';
						$title_text = $this->p->util->safe_apply_filters( array( 'wp_title', $title_text, $sep, 'right' ), $mod );

					} elseif ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'name property missing in term object' );
					}

					$title_text = apply_filters( $this->p->lca . '_term_archive_title', $title_text, $mod, $term_obj );

				} elseif ( $mod[ 'is_user' ] ) {

					$user_obj   = SucomUtil::get_user_object( $mod[ 'id' ] );
					$title_text = $user_obj->display_name . ' ' . $sep . ' ';
					$title_text = $this->p->util->safe_apply_filters( array( 'wp_title', $title_text, $sep, 'right' ), $mod );
					$title_text = apply_filters( $this->p->lca . '_user_archive_title', $title_text, $mod, $user_obj );

				} else {

					$title_text = wp_title( $sep, false, 'right' );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'default wp_title() = "' . $title_text . '"' );
					}

					$title_text = apply_filters( $this->p->lca . '_wp_title', $title_text, $mod );
				}

				if ( empty( $title_text ) ) {
					if ( $title_text = get_bloginfo( 'name', 'display' ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'fallback get_bloginfo() = "' . $title_text . '"' );
						}
					} else {
						$title_text = _x( 'No Title', 'default title', 'wpsso' );	// Just in case.
					}
				}
			}

			if ( ! $filter_title ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'ignored modified wp_title value: ' . SucomUtil::get_modified_filter_value( 'wp_title' ) );
				}
			}

			/**
			 * Strip html tags before removing separator.
			 */
			$title_text = $this->p->util->cleanup_html_tags( $title_text );

			/**
			 * Trim excess separator.
			 */
			if ( ! empty( $sep ) ) {
				$title_text = preg_replace( '/ *' . preg_quote( $sep, '/' ) . ' *$/', '', $title_text );
			}

			/**
			 * Apply a title filter before adjusting it's length.
			 */
			$title_text = apply_filters( $this->p->lca . '_title_pre_limit', $title_text );

			/**
			 * Check title against string length limits.
			 */
			if ( $max_len > 0 ) {

				/**
				 * Apply seo-like title modifications.
				 */
				if ( $this->p->avail[ 'seo' ][ 'any' ] === false ) {

					global $wpsso_paged;

					if ( is_numeric( $wpsso_paged ) ) {
						$paged = $wpsso_paged;
					} else {
						$paged = get_query_var( 'paged' );
					}

					if ( $paged > 1 ) {

						if ( ! empty( $sep ) ) {
							$paged_suffix .= $sep . ' ';
						}

						$paged_suffix .= sprintf( 'Page %s', $paged );

						$max_len = $max_len - strlen( $paged_suffix ) - 1;
					}
				}

				$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'title strlen before limit length ' . strlen( $title_text ) . ' (limiting to ' . $adj_max_len . ' chars)' );
				}

				$title_text = $this->p->util->limit_text_length( $title_text, $adj_max_len, $dots, $cleanup_html = false );
			}

			if ( ! empty( $paged_suffix ) ) {
				$title_text .= ' ' . $paged_suffix;
			}

			if ( ! empty( $add_hashtags ) && ! empty( $hashtags ) ) {
				$title_text = trim( $title_text . ' ' . $hashtags );	// Trim in case text is empty.
			}

			if ( true === $do_encode ) {
				foreach ( array( 'title_text', 'sep' ) as $var ) {	// Loop through variables.
					$$var = SucomUtil::encode_html_emoji( $$var );
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'before title filter = "' . $title_text . '"' );
			}

			return apply_filters( $this->p->lca . '_title', $title_text, $mod, $add_hashtags, $md_key, $sep );
		}

		/**
		 * $mod    = true | false | post_id | array
		 * $md_key = true | false | string | array
		 */
		public function get_description( $max_len = 160, $dots = '...', $mod = false, $read_cache = true,
			$add_hashtags = true, $do_encode = true, $md_key = 'og_desc' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'render description' );	// Begin timer.

				$this->p->debug->log_args( array(
					'max_len'      => $max_len,
					'dots'         => $dots,
					'mod'          => $mod,
					'read_cache'   => $read_cache,
					'add_hashtags' => $add_hashtags, 	// true | false | numeric
					'do_encode'    => $do_encode,
					'md_key'       => $md_key,
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->p->util->get_page_mod( $mod );
			}

			if ( false === $md_key ) {		// False would return the complete meta array.

				$md_key = '';

			} elseif ( true === $md_key ) {		// True signals use of the standard / fallback value.

				$md_key = array( 'og_desc' );

			} elseif ( ! is_array( $md_key ) ) {	// Use fallback by default - get_options_multi() will do array_uniq().

				$md_key = array( $md_key, 'og_desc' );
			}

			$desc_text = false;

			/**
			 * Check for custom description if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && $md_key !== 'none' ) {

				$desc_text = is_object( $mod[ 'obj' ] ) ? $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $desc_text ) ) {
						$this->p->debug->log( 'no custom description found for md_key' );
					} else {
						$this->p->debug->log( 'custom description = "' . $desc_text . '"' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom description skipped: no md_key value' );
			}

			/**
			 * Get seed if no custom meta description.
			 */
			if ( empty( $desc_text ) ) {

				$desc_text = apply_filters( $this->p->lca . '_description_seed', '', $mod, $add_hashtags, $md_key );

				if ( ! empty( $desc_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'description seed = "' . $desc_text . '"' );
					}
				}
			}

			/**
			 * Check for hashtags in meta or seed desc, remove and then add again after shorten.
			 */
			list( $desc_text, $hashtags ) = $this->get_text_and_hashtags( $desc_text, $mod, $add_hashtags );

			/**
			 * If there's no custom description, and no pre-seed, then go ahead and generate the description value.
			 */
			if ( empty( $desc_text ) ) {

				if ( $mod[ 'is_post' ] ) {

					if ( $mod[ 'is_post_type_archive' ] ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting the description for post type ' . $mod[ 'post_type' ] );
						}

						$post_type_obj = get_post_type_object( $mod[ 'post_type' ] );

						if ( ! empty( $post_type_obj->description ) ) {

							$desc_text = $post_type_obj->description;

						} else {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'post type ' . $mod[ 'post_type' ] . ' description is empty - using title value' );
							}

							if ( ! empty( $post_type_obj->labels->menu_name ) ) {
								$desc_text = sprintf( _x( 'Archive for %s.', 'default description', 'wpsso' ), $post_type_obj->labels->menu_name );
							} elseif ( ! empty( $post_type_obj->name ) ) {
								$desc_text = sprintf( _x( 'Archive for %s.', 'default description', 'wpsso' ), $post_type_obj->name );
							}
						}

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'before post_archive_description filter = ' . $desc_text );
						}
	
						$desc_text = apply_filters( $this->p->lca . '_post_archive_description', $desc_text, $mod, $post_type_obj );

					} else {

						$desc_text = $this->get_the_excerpt( $mod );

						/**
						 * If there's no excerpt, then fallback to the content.
						 */
						if ( empty( $desc_text ) ) {
	
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'getting the content for post ID ' . $mod[ 'id' ] );
							}
	
							$desc_text = $this->get_the_content( $mod, $read_cache, $md_key );
	
							/**
							 * Ignore everything before the first paragraph if true.
							 */
							if ( $this->p->options[ 'plugin_p_strip' ] ) {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'removing all text before the first paragraph' );
								}

								/**
								 * i = Letters in the pattern match both upper and lower case letters. 
								 * U = Inverts the "greediness" of quantifiers so that they are not greedy by default.
								 *
								 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
								 */
								$desc_text = preg_replace( '/^.*<p>/iU', '', $desc_text );
							}
						}

						/**
						 * Fallback to the image alt value.
						 */
						if ( empty( $desc_text ) ) {
							if ( $mod[ 'post_type' ] === 'attachment' && strpos( $mod[ 'post_mime' ], 'image/' ) === 0 ) {
								$desc_text = get_post_meta( $mod[ 'id' ], '_wp_attachment_image_alt', true );
							}
						}
					}

				} elseif ( $mod[ 'is_term' ] ) {

					$term_obj = get_term( $mod[ 'id' ], $mod[ 'tax_slug' ] );

					/**
					 * Tag archive page.
					 */
					if ( SucomUtil::is_tag_page( $mod[ 'id' ] ) ) {

						if ( ! $desc_text = tag_description( $mod[ 'id' ] ) ) {
							if ( ! empty( $term_obj->name ) ) {
								$desc_text = sprintf( _x( 'Tag archive for %s.', 'default description', 'wpsso' ), $term_obj->name );
							}
						}

						$desc_text = apply_filters( $this->p->lca . '_tag_archive_description', $desc_text, $mod, $term_obj );

					/**
					 * Category archive page.
					 */
					} elseif ( SucomUtil::is_category_page( $mod[ 'id' ] ) ) {

						/**
						 * Includes parent names in the category title if the $sep value is not empty.
						 */
						if ( ! $desc_text = category_description( $mod[ 'id' ] ) ) {
							$desc_text = sprintf( _x( 'Category archive for %s.', 'default description', 'wpsso' ), get_cat_name( $mod[ 'id' ] ) );
						}

						$desc_text = apply_filters( $this->p->lca . '_category_archive_description', $desc_text, $mod, $term_obj );

					/**
					 * Other archive page.
					 */
					} else {

						if ( ! empty( $term_obj->description ) ) {
							$desc_text = $term_obj->description;
						} elseif ( ! empty( $term_obj->name ) ) {
							$desc_text = sprintf( _x( 'Archive for %s.', 'default description', 'wpsso' ), $term_obj->name );
						}
					}

					$desc_text = apply_filters( $this->p->lca . '_term_archive_description', $desc_text, $mod, $term_obj );

				} elseif ( $mod[ 'is_user' ] ) {

					$user_obj = SucomUtil::get_user_object( $mod[ 'id' ] );

					if ( ! empty( $user_obj->description ) ) {

						$desc_text = $user_obj->description;

					} elseif ( ! empty( $user_obj->display_name ) ) {

						$desc_text = sprintf( _x( 'Authored by %s.', 'default description', 'wpsso' ), $user_obj->display_name );
					}

					$desc_text = apply_filters( $this->p->lca . '_user_archive_description', $desc_text, $mod, $user_obj );

				} elseif ( is_day() ) {

					$desc_text = sprintf( _x( 'Daily archive for %s.', 'default description', 'wpsso' ), get_the_date() );
					$desc_text = apply_filters( $this->p->lca . '_daily_archive_description', $desc_text, $mod );

				} elseif ( is_month() ) {

					$desc_text = sprintf( _x( 'Monthly archive for %s.', 'default description', 'wpsso' ), get_the_date('F Y') );
					$desc_text = apply_filters( $this->p->lca . '_monthly_archive_description', $desc_text, $mod );

				} elseif ( is_year() ) {

					$desc_text = sprintf( _x( 'Yearly archive for %s.', 'default description', 'wpsso' ), get_the_date('Y') );
					$desc_text = apply_filters( $this->p->lca . '_yearly_archive_description', $desc_text, $mod );

				} elseif ( SucomUtil::is_archive_page() ) {	// Just in case.

					$desc_text = _x( 'Archive page.', 'default description', 'wpsso' );
					$desc_text = apply_filters( $this->p->lca . '_archive_page_description', $desc_text, $mod );
				}
			}

			/**
			 * Descriptions comprised entirely of html content will be empty after running cleanup_html_tags(),
			 * so remove the html before falling back to a generic description.
			 */
			$strlen_pre_cleanup = $this->p->debug->enabled ? strlen( $desc_text ) : 0;

			$desc_text = $this->p->util->cleanup_html_tags( $desc_text, true, $this->p->options[ 'plugin_use_img_alt' ] );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'description strlen before html cleanup ' . $strlen_pre_cleanup . ' and after ' . strlen( $desc_text ) );
			}

			/**
			 * If there's still no description, then fallback to a generic version.
			 */
			if ( empty( $desc_text ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'description is empty - falling back to generic description' );
				}

				if ( $mod[ 'post_status' ] === 'auto-draft' ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'post_status is auto-draft: using empty description' );
					}
				} elseif ( $mod[ 'post_type' ] === 'attachment' ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'post_type is attachment: using empty description' );
					}
				} elseif ( $desc_text = SucomUtil::get_site_description( $this->p->options, $mod ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'fallback SucomUtil::get_site_description() = "' . $desc_text . '"' );
					}
				} else {
					$desc_text = _x( 'No Description.', 'default description', 'wpsso' );	// Just in case.
				}
			}

			/**
			 * Apply a description filter before adjusting it's length.
			 */
			$desc_text = apply_filters( $this->p->lca . '_description_pre_limit', $desc_text );

			/**
			 * Check description against string length limits.
			 */
			if ( $max_len > 0 ) {

				$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'description strlen before limit length ' . strlen( $desc_text ) . ' (limiting to ' . $adj_max_len . ' chars)' );
				}

				$desc_text = $this->p->util->limit_text_length( $desc_text, $adj_max_len, $dots, $cleanup_html = false );

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipped the description length limit' );
			}

			if ( ! empty( $add_hashtags ) && ! empty( $hashtags ) ) {
				$desc_text = trim( $desc_text . ' ' . $hashtags );	// Trim in case text is empty.
			}

			if ( $do_encode ) {
				$desc_text = SucomUtil::encode_html_emoji( $desc_text );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'before description filter = "' . $desc_text . '"' );
			}

			$desc_text = apply_filters( $this->p->lca . '_description', $desc_text, $mod, $add_hashtags, $md_key );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'render description' );	// End timer.
			}

			return $desc_text;
		}

		public function get_text( $max_len = 0, $dots = '...', $mod = false, $read_cache = true,
			$add_hashtags = false, $do_encode = true, $md_key = 'schema_text' ) {

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

				$mod = $this->p->util->get_page_mod( $mod );
			}

			$text = $this->get_the_text( $mod, $read_cache, $md_key );

			/**
			 * Check for hashtags in meta or seed text, remove and then add again after shorten.
			 */
			list( $text, $hashtags ) = $this->get_text_and_hashtags( $text, $mod, $add_hashtags );

			if ( $max_len > 0 ) {

				$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'text strlen before limit length ' . strlen( $text ) . ' (limiting to ' . $adj_max_len . ' chars)' );
				}

				$text = $this->p->util->limit_text_length( $text, $adj_max_len, $dots, $cleanup_html = false );

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipped the text length limit' );
			}

			if ( ! empty( $add_hashtags ) && ! empty( $hashtags ) ) {
				$text = trim( $text . ' ' . $hashtags );	// Trim in case text is empty.
			}

			if ( $do_encode ) {
				$text = SucomUtil::encode_html_emoji( $text );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'before text filter = "' . $text . '"' );
			}

			return apply_filters( $this->p->lca . '_text', $text, $mod, $add_hashtags, $md_key );
		}

		public function get_the_excerpt( array $mod ) {

			$excerpt_text = '';

			/**
			 * Use the excerpt, if we have one.
			 */
			if ( $mod[ 'is_post' ] && has_excerpt( $mod[ 'id' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting the excerpt for post id ' . $mod[ 'id' ] );
				}

				$excerpt_text = get_post_field( 'post_excerpt', $mod[ 'id' ] );

				static $filter_excerpt = null;

				if ( null === $filter_excerpt ) {
					$filter_excerpt = empty( $this->p->options[ 'plugin_filter_excerpt' ] ) ? false : true;
					$filter_excerpt = apply_filters( $this->p->lca . '_can_filter_the_excerpt', $filter_excerpt, $mod );
				}

				if ( $filter_excerpt ) {
					$excerpt_text = $this->p->util->safe_apply_filters( array( 'get_the_excerpt', $excerpt_text ), $mod );
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipped the WordPress get_the_excerpt filters' );
				}
			}

			return $excerpt_text;
		}

		public function get_the_content( array $mod, $read_cache = true, $md_key = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'mod'        => $mod,
					'read_cache' => $read_cache,
					'md_key'     => $md_key,
				) );
			}

			$sharing_url    = $this->p->util->get_sharing_url( $mod );
			$filter_content = empty( $this->p->options[ 'plugin_filter_content' ] ) ? false : true;
			$filter_content = apply_filters( $this->p->lca . '_can_filter_the_content', $filter_content, $mod );

			static $cache_exp_secs = null;	// filter the cache expiration value only once

			$cache_md5_pre = $this->p->lca . '_c_';

			if ( ! isset( $cache_exp_secs ) ) {	// filter cache expiration if not already set
				$cache_exp_filter = $this->p->cf[ 'wp' ][ 'wp_cache' ][ $cache_md5_pre ][ 'filter' ];
				$cache_opt_key    = $this->p->cf[ 'wp' ][ 'wp_cache' ][ $cache_md5_pre ][ 'opt_key' ];
				$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, $this->p->options[ $cache_opt_key ] );
			}

			/************************
			 * Retrieve the Content *
			 ************************/

			$cache_salt  = __METHOD__ . '(' . SucomUtil::get_mod_salt( $mod, $sharing_url ) . ')';
			$cache_id    = $cache_md5_pre . md5( $cache_salt );
			$cache_index = 'locale:' . SucomUtil::get_locale( $mod ) . '_filter:' . ( $filter_content ? 'true' : 'false' );
			$cache_array = array();

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'sharing url = ' . $sharing_url );
				$this->p->debug->log( 'filter content = ' . ( $filter_content ? 'true' : 'false' ) );
				$this->p->debug->log( 'wp cache expire = ' . $cache_exp_secs );
				$this->p->debug->log( 'wp cache salt = ' . $cache_salt );
				$this->p->debug->log( 'wp cache id = ' . $cache_id );
				$this->p->debug->log( 'wp cache index = ' . $cache_index );
			}

			if ( $cache_exp_secs > 0 ) {

				if ( $read_cache ) {

					$cache_array = wp_cache_get( $cache_id, __METHOD__ );

					if ( isset( $cache_array[ $cache_index ] ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'exiting early: cache index found in wp_cache' );
						}
						return $cache_array[ $cache_index ];
					} else {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'cache index not in wp_cache' );
						}
						if ( ! is_array( $cache_array ) ) {
							$cache_array = array();
						}
					}
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'read cache for content is false' );
				}
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'content array wp_cache is disabled' );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'initializing new wp cache element' );
			}

			$cache_array[ $cache_index ] = false;	// Initialize the cache element.

			$content =& $cache_array[ $cache_index ];	// Reference the cache element.
			$content = apply_filters( $this->p->lca . '_the_content_seed', '', $mod, $read_cache, $md_key );

			if ( $content === false ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'content seed is false' );
				}

			} elseif ( ! empty( $content ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'content seed is "' . $content . '"' );
				}

			} elseif ( $mod[ 'is_post' ] ) {

				$content = get_post_field( 'post_content', $mod[ 'id' ] );

				if ( empty( $content ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: no post_content for post id ' . $mod[ 'id' ] );
					}

					return false;
				}
			}

			/**
			 * Save content length (for comparison) before making changes.
			 */
			$strlen_before_filters = strlen( $content );

			/**
			 * Remove singlepics, which we detect and use before-hand.
			 */
			$content = preg_replace( '/\[singlepic[^\]]+\]/', '', $content, -1, $count );

			if ( $count > 0 ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $count . ' [singlepic] shortcode(s) removed from content' );
				}
			}

			/**
			 * Maybe apply 'the_content' filter to expand shortcodes and blocks.
			 */
			if ( $filter_content ) {

				$hook_bfo  = SucomUtil::get_const( 'WPSSO_CONTENT_BLOCK_FILTER_OUTPUT', true );
				$mtime_max = SucomUtil::get_const( 'WPSSO_CONTENT_FILTERS_MAX_TIME', 1.50 );

				$content = $this->p->util->safe_apply_filters( array( 'the_content', $content ), $mod, $mtime_max, $hook_bfo );

				/**
				 * Cleanup for NextGEN Gallery pre-v2 album shortcode.
				 */
				unset ( $GLOBALS[ 'subalbum' ] );
				unset ( $GLOBALS[ 'nggShowGallery' ] );

			/**
			 * Maybe apply the 'do_blocks' filters.
			 */
			} else {
			
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'the_content filters skipped (shortcodes not expanded)' );
				}

				if ( function_exists( 'do_blocks' ) ) {	// Since WP v5.0.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'calling do_blocks to filter the content text.' );
					}

					$content = do_blocks( $content );
				}
			}

			$content = preg_replace( '/[\s\n\r]+/s', ' ', $content );		// put everything on one line
			$content = preg_replace( '/^.*<!--' . $this->p->lca . '-content-->(.*)<!--\/' . 
				$this->p->lca . '-content-->.*$/', '$1', $content );

			/**
			 * Remove "Google+" link and text.
			 */
			if ( false !== strpos( $content, '>Google+<' ) ) {
				$content = preg_replace( '/<a +rel="author" +href="" +style="display:none;">Google\+<\/a>/', ' ', $content );
			}

			if ( false !== strpos( $content, '<p class="wp-caption-text">' ) ) {

				$caption_prefix = isset( $this->p->options[ 'plugin_p_cap_prefix' ] ) ?
					$this->p->options[ 'plugin_p_cap_prefix' ] : 'Caption:';

				if ( ! empty( $caption_prefix ) ) {
					$content = preg_replace( '/<p class="wp-caption-text">/', '${0}' . $caption_prefix . ' ', $content );
				}
			}

			if ( false !== strpos( $content, ']]>' ) ) {
				$content = str_replace( ']]>', ']]&gt;', $content );
			}

			$strlen_after_filters = strlen( $content );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'content strlen before ' . $strlen_before_filters . ' and after changes / filters ' . $strlen_after_filters );
			}

			/**
			 * Apply filters before caching.
			 */
			$content = apply_filters( $this->p->lca . '_the_content', $content, $mod, $read_cache, $md_key );

			if ( $cache_exp_secs > 0 ) {

				wp_cache_add_non_persistent_groups( array( __METHOD__ ) );	// Only some caching plugins support this feature.

				wp_cache_set( $cache_id, $cache_array, __METHOD__, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'content array saved to wp_cache for ' . $cache_exp_secs . ' seconds');
				}
			}

			return $content;
		}

		/**
		 * Returns the content text, stripped of all HTML tags.
		 */
		public function get_the_text( array $mod, $read_cache = true, $md_key = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Check for custom text if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && $md_key !== 'none' ) {

				$text = is_object( $mod[ 'obj' ] ) ? $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $text ) ) {
						$this->p->debug->log( 'no custom text found for md_key' );
					} else {
						$this->p->debug->log( 'custom text = "' . $text . '"' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom text skipped: no md_key value' );
			}

			/**
			 * If there's no custom text, then go ahead and generate the text value.
			 */
			if ( empty( $text ) ) {
				$text = $this->get_the_content( $mod, $read_cache, $md_key );
				$text = preg_replace( '/<pre[^>]*>.*<\/pre>/Ums', '', $text );
				$text = $this->p->util->cleanup_html_tags( $text, true, $this->p->options[ 'plugin_use_img_alt' ] );
			}

			return $text;
		}

		public function get_article_section( $post_id, $allow_none = false, $use_mod_opts = true ) {

			$section = '';

			/**
			 * Get custom article section from post meta.
			 */
			if ( $use_mod_opts ) {
				if ( ! empty( $post_id ) ) {
					$section = $this->p->m[ 'util' ][ 'post' ]->get_options( $post_id, 'og_art_section' );	// Returns null if index key not found.
				}
			}

			if ( ! empty( $section ) ) {	// Must be a non-empty string.
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'found custom meta article section = ' . $section );
				}
			} else {
				$section = $this->p->options[ 'og_art_section' ];
			}

			if ( ! $allow_none ) {
				if ( $section === 'none' ) {
					$section = '';
				}
			}

			return apply_filters( $this->p->lca . '_article_section', $section, $post_id );
		}

		/**
		 * Returns a comma delimited text string of keywords (ie. post tag names).
		 */
		public function get_keywords( array $mod, $read_cache = true, $md_key = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$keywords = '';

			/**
			 * Check for custom keywords if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && $md_key !== 'none' ) {

				$keywords = is_object( $mod[ 'obj' ] ) ? $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $keywords ) ) {
						$this->p->debug->log( 'no custom keywords found for md_key' );
					} else {
						$this->p->debug->log( 'custom keywords = "' . $keywords . '"' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom keywords skipped: no md_key value' );
			}

			/**
			 * If there's no custom keywords, then go ahead and generate the keywords value.
			 */
			if ( empty( $keywords ) ) {

				$tags = $this->get_tag_names( $mod );

				if ( ! empty( $tags ) ) {

					$keywords = SucomUtil::array_to_keywords( $tags );	// Returns a comma delimited text string.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'keywords = "' . $keywords . '"' );
					}
				}
			}

			return $keywords;
		}

		public function get_text_and_hashtags( $text, array $mod, $add_hashtags = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$hashtags = '';

			/**
			 * U = Inverts the "greediness" of quantifiers so that they are not greedy by default.
			 *
			 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
			 */
			if ( preg_match( '/^(.*)(( *#[a-z][a-z0-9\-]+)+)$/U', $text, $match ) ) {

				$text     = $match[1];
				$hashtags = trim( $match[2] );

			} elseif ( $add_hashtags ) {

				$hashtags = $this->get_hashtags( $mod, $add_hashtags );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'hashtags found = "' . $hashtags . '"' );
			}

			return array( $text, $hashtags );
		}

		/**
		 * Returns a space delimited text string of hashtags.
		 */
		public function get_hashtags( array $mod, $add_hashtags = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Determine the maximum number of hashtags to return.
			 */
			if ( empty( $add_hashtags ) ) {	// Check for false or 0.

				return '';

			} elseif ( is_numeric( $add_hashtags ) ) {	// Return a specific number of hashtags.

				$max_hashtags = $add_hashtags;

			} elseif ( ! empty( $this->p->options[ 'og_desc_hashtags' ] ) ) {	// Return the default number of hashtags.

				$max_hashtags = $this->p->options[ 'og_desc_hashtags' ];

			} else {	// Just in case.
				return '';
			}

			$hashtags = apply_filters( $this->p->lca . '_hashtags_seed', '', $mod, $add_hashtags );

			if ( ! empty( $hashtags ) ) {	// Seed hashtags returned.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'hashtags seed = "' . $hashtags . '"' );
				}

			} else {

				$tags = $this->get_tag_names( $mod );

				$tags = array_slice( $tags, 0, $max_hashtags );

				if ( ! empty( $tags ) ) {

					/**
					 * Remove special character incompatible with Twitter.
					 */
					$hashtags = SucomUtil::array_to_hashtags( $tags );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'hashtags (max ' . $max_hashtags . ') = "' . $hashtags . '"' );
					}
				}
			}

			return apply_filters( $this->p->lca . '_hashtags', $hashtags, $mod, $add_hashtags );
		}

		/**
		 * Returns an array of post tags.
		 */
		public function get_tag_names( array $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			static $local_cache = array();

			if ( isset( $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: returning tags from static cache' );
				}

				return $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ];
			}

			$tags = apply_filters( $this->p->lca . '_tag_names_seed', array(), $mod );

			if ( ! empty( $tags ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'tags seed = "' . implode( ',', $tags ) . '"' );
				}

			} else {

				if ( $mod[ 'is_post' ] ) {
					foreach ( wp_get_post_tags( $mod[ 'id' ] ) as $tag_obj ) {
						if ( ! empty( $tag_obj->name ) ) {
							$tags[] = $tag_obj->name;
						}
					}
				}
				
				$tags = array_unique( $tags );
			}

			$tags = $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ] = apply_filters( $this->p->lca . '_tag_names', $tags, $mod );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_arr( 'tags', $tags );
			}

			return $tags;
		}

		/**
		 * Includes parent names in the category title if the $sep value is not empty.
		 */
		public function get_category_title( $term_id = 0, $sep = null, $mod = false ) {

			$title_text = '';

			if ( is_object( $term_id ) ) {
				$term_obj = $term_id;
				$term_id  = $term_obj->term_id;
			} else {
				$term_obj = get_category( $term_id );
			}

			if ( null === $sep ) {
				$sep = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			if ( ! is_array( $mod ) ) {
				$mod = $this->p->m[ 'util' ][ 'term' ]->get_mod( $term_id );
			}

			if ( isset( $term_obj->name ) ) {

				$title_text = $term_obj->name . ' ';

				if ( ! empty( $sep ) ) {
					$title_text .= $sep . ' ';	// Default behavior.
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'name property missing in term object' );
			}

			if ( ! empty( $sep ) ) {	// Just in case.

				$cat = get_category( $term_obj->term_id );

				if ( ! empty( $cat->category_parent ) ) {

					$cat_parents = get_category_parents( $term_obj->term_id, false, ' ' . $sep . ' ', false );
	
					if ( is_wp_error( $cat_parents ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'get_category_parents error: ' . $cat_parents->get_error_message() );
						}

					} else {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'get_category_parents() = "' . $cat_parents . '"' );
						}

						if ( ! empty( $cat_parents ) ) {
							$title_text = $cat_parents;
						}
					}
				}
			}

			$title_text = $this->p->util->safe_apply_filters( array( 'wp_title', $title_text, $sep, 'right' ), $mod );

			return $title_text;
		}
	}
}
