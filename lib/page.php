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
				if ( has_excerpt( $mod['id'] ) ) {
					$quote_text = get_the_excerpt( $mod['id'] );
				} else {
					$quote_text = get_post_field( 'post_content', $mod['id'] );
				}
			}

			/**
			 * Remove shortcodes, etc., but don't strip html tags.
			 */
			$quote_text = $this->p->util->cleanup_html_tags( $quote_text, false );

			return apply_filters( $this->p->lca . '_quote', $quote_text, $mod );
		}

		/**
		 * $type = 'title' | 'excerpt' | 'both'
		 * $mod = true | false | post_id | array
		 * $md_idx = true | false | string | array
		 */
		public function get_caption( $type = 'title', $max_len = 200, $mod = true, $r_cache = true, $add_htags = true, $do_encode = true, $md_idx = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'type'      => $type,
					'max_len'   => $max_len,
					'mod'       => $mod,
					'r_cache'   => $r_cache,
					'add_htags' => $add_htags,	// true/false/numeric
					'do_encode' => $do_encode,
					'md_idx'    => $md_idx,
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
			$sep = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );

			if ( false === $md_idx ) {	// false would return the complete meta array

				$md_idx = '';
				$md_idx_title = '';
				$md_idx_desc = '';

			} elseif ( true === $md_idx ) {	// true signals the use of the standard / fallback value

				switch ( $type ) {
					case 'title':
						$md_idx = 'og_title';
						$md_idx_title = 'og_title';
						$md_idx_desc = 'og_desc';
						break;
					case 'excerpt':
						$md_idx = 'og_desc';
						$md_idx_title = 'og_title';
						$md_idx_desc = 'og_desc';
						break;
					case 'both':
						$md_idx = 'og_caption';
						$md_idx_title = 'og_title';
						$md_idx_desc = 'og_desc';
						break;
				}

			} else {	// $md_idx could be a string or array

				$md_idx_title = $md_idx;
				$md_idx_desc = $md_idx;
			}

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {

				$cap_text = $mod['obj'] ? $mod['obj']->get_options_multi( $mod['id'], $md_idx ) : null;

				// maybe add hashtags to a post caption
				if ( $mod['is_post'] ) {
					if ( ! empty( $cap_text ) && ! empty( $add_htags ) && ! preg_match( '/( #[a-z0-9\-]+)+$/U', $cap_text ) ) {
						$hashtags = $this->get_hashtags( $mod['id'], $add_htags );
						if ( ! empty( $hashtags ) ) {
							$adj_max_len = $max_len - strlen( $hashtags ) - 1;
							$cap_text = $this->p->util->limit_text_length( $cap_text, $adj_max_len, '...', false ) . ' ' . $hashtags;
						}
					}
				}
				if ( $this->p->debug->enabled ) {
					if ( empty( $cap_text ) ) {
						$this->p->debug->log( 'no custom caption found for md_idx' );
					} else {
						$this->p->debug->log( 'custom caption = "' . $cap_text . '"' );
					}
				}
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom caption skipped: no md_idx value' );
			}

			if ( empty( $cap_text ) ) {

				// request all values un-encoded, then encode once we have the complete caption text
				switch ( $type ) {
					case 'title':
						$cap_text = $this->get_title( $max_len, '...', $mod, $r_cache, $add_htags, false, $md_idx_title );
						break;

					case 'excerpt':
						$cap_text = $this->get_description( $max_len, '...', $mod, $r_cache, $add_htags, false, $md_idx_desc );
						break;

					case 'both':
						// get the title first
						$cap_text = $this->get_title( 0, '', $mod, $r_cache, false, false, $md_idx_title );	// $add_htags = false

						// add a separator between title and description
						if ( ! empty( $cap_text ) ) {
							$cap_text .= ' ';
						}

						if ( ! empty( $sep ) ) {
							$cap_text .= $sep . ' ';
						}

						// reduce the requested $max_len by the title text length we already have
						$adj_max_len = $max_len - strlen( $cap_text );

						$cap_text .= $this->get_description( $adj_max_len, '...', $mod, $r_cache, $add_htags, false, $md_idx_desc );

						break;
				}
			}

			if ( true === $do_encode ) {
				$cap_text = SucomUtil::encode_html_emoji( $cap_text );
			} else {	// Just in case.
				$cap_text = html_entity_decode( SucomUtil::decode_utf8( $cap_text ), ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			return apply_filters( $this->p->lca . '_caption', $cap_text, $mod, $add_htags, $md_idx );
		}

		/**
		 * $mod = true | false | post_id | array
		 * $md_idx = true | false | string | array
		 */
		public function get_title( $max_len = 70, $dots = '', $mod = false, $r_cache = true, $add_htags = false, $do_encode = true, $md_idx = 'og_title', $sep = null ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'max_len'   => $max_len,
					'dots'      => $dots,
					'mod'       => $mod,
					'r_cache'   => $r_cache,
					'add_htags' => $add_htags,	// true/false/numeric
					'do_encode' => $do_encode,
					'md_idx'    => $md_idx,
					'sep'       => $sep,
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

			if ( false === $md_idx ) {		// false would return the complete meta array
				$md_idx = '';
			} elseif ( true === $md_idx ) {		// true signals use of the standard / fallback value
				$md_idx = array( 'og_title' );
			} elseif ( ! is_array( $md_idx ) ) {	// use fallback by default - get_options_multi() will do array_uniq()
				$md_idx = array( $md_idx, 'og_title' );
			}

			$title_text = false;
			$hashtags = '';
			$paged_suffix = '';
			$filter_title = empty( $this->p->options['plugin_filter_title'] ) ? false : true;
			$filter_title = apply_filters( $this->p->lca . '_filter_title', $filter_title, $mod );

			if ( null === $sep ) {
				$sep = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			/**
			 * Setup filters to save and restore original / pre-filtered title value.
			 */
			if ( ! $filter_title ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'protecting filter value for wp_title' );
				}
				SucomUtil::protect_filter_value( 'wp_title' );
			}

			/**
			 * Skip if no metadata index / key name.
			 */
			if ( ! empty( $md_idx ) ) {

				$title_text = is_object( $mod['obj'] ) ? $mod['obj']->get_options_multi( $mod['id'], $md_idx ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $title_text ) ) {
						$this->p->debug->log( 'no custom title found for md_idx' );
					} else {
						$this->p->debug->log( 'custom title = "' . $title_text . '"' );
					}
				}
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom title skipped: no md_idx value' );
			}

			/**
			 * Get seed if no custom meta title.
			 */
			if ( empty( $title_text ) ) {

				$title_text = apply_filters( $this->p->lca . '_title_seed', '', $mod, $add_htags, $md_idx, $sep );

				if ( ! empty( $title_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'title seed = "' . $title_text . '"' );
					}
				}
			}

			/**
			 * Check for hashtags in meta or seed title, remove and then add again after shorten.
			 */
			if ( preg_match( '/(.*)(( #[a-z0-9\-]+)+)$/U', $title_text, $match ) ) {

				$title_text = $match[1];
				$hashtags = trim( $match[2] );

			} elseif ( $mod['is_post'] ) {

				if ( ! empty( $add_htags ) && ! empty( $this->p->options['og_desc_hashtags'] ) ) {
					$hashtags = $this->get_hashtags( $mod['id'], $add_htags );	// $add_htags = true | false | numeric
				}
			}

			if ( $hashtags && $this->p->debug->enabled ) {
				$this->p->debug->log( 'hashtags found = "' . $hashtags . '"' );
			}

			/**
			 * Construct a title of our own.
			 */
			if ( empty( $title_text ) ) {

				if ( $mod['is_post'] ) {

					if ( empty( $mod['id'] ) && ! empty( $mod['post_type'] ) && is_post_type_archive() ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting the title for post type ' . $mod['post_type'] );
						}

						$post_type_obj = get_post_type_object( $mod['post_type'] );

						if ( ! empty( $post_type_obj->labels->menu_name ) ) {
							$title_text = sprintf( __( '%s Archive', 'wpsso' ), $post_type_obj->labels->menu_name );
						} elseif ( ! empty( $post_type_obj->name ) ) {
							$title_text = sprintf( __( '%s Archive', 'wpsso' ), $post_type_obj->name );
						}

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'before post_archive_title filter = ' . $title_text );
						}
	
						$title_text = apply_filters( $this->p->lca . '_post_archive_title', $title_text, $mod );

					} else {

						$title_text = html_entity_decode( get_the_title( $mod['id'] ) ) . ' ';

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $mod['name'] . ' id ' . $mod['id'] . ' get_the_title() = "' . $title_text . '"' );
						}
					}

					if ( ! empty( $sep ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'adding separator "' . $sep . '" to title string' );
						}

						$title_text .= $sep . ' ';
					}

					$title_text = $this->p->util->safe_apply_filters( array( 'wp_title', $title_text, $sep, 'right' ), $mod );

				} elseif ( $mod['is_term'] ) {

					$term_obj = SucomUtil::get_term_object( $mod['id'], $mod['tax_slug'] );

					if ( SucomUtil::is_category_page( $mod['id'] ) ) {

						/**
						 * Includes parent names in title string if the $sep is not empty.
						 */
						$title_text = $this->get_category_title( $term_obj, '', $sep );

					} elseif ( isset( $term_obj->name ) ) {

						$title_text = apply_filters( 'wp_title', $term_obj->name . ' ' . $sep . ' ', $sep, 'right' );

					} elseif ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'name property missing in term object' );
					}

				} elseif ( $mod['is_user'] ) {

					$user_obj = SucomUtil::get_user_object( $mod['id'] );

					$title_text = apply_filters( 'wp_title', $user_obj->display_name . ' ' . $sep . ' ', $sep, 'right' );

					$title_text = apply_filters( $this->p->lca . '_user_object_title', $title_text, $user_obj );

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
						$title_text = __( 'No Title', 'wpsso' );	// just in case
					}
				}
			}

			if ( ! $filter_title ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'modified / ignored wp_title value: ' . SucomUtil::get_modified_filter_value( 'wp_title' ) );
				}
			}

			$title_text = $this->p->util->cleanup_html_tags( $title_text );	// strip html tags before removing separator

			if ( ! empty( $sep ) ) {
				$title_text = preg_replace( '/ *' . preg_quote( $sep, '/' ) . ' *$/', '', $title_text );	// trim excess separator
			}

			/**
			 * Apply title filter before adjusting it's length.
			 */
			$title_text = apply_filters( $this->p->lca . '_title_pre_limit', $title_text );

			/**
			 * Check title against string length limits.
			 */
			if ( $max_len > 0 ) {

				if ( $this->p->avail['seo']['*'] === false ) {	// apply seo-like title modifications
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

				if ( ! empty( $add_htags ) && ! empty( $hashtags ) ) {
					$max_len = $max_len - strlen( $hashtags ) - 1;
				}

				$title_text = $this->p->util->limit_text_length( $title_text, $max_len, $dots, false );	// $cleanup_html = false
			}

			if ( ! empty( $paged_suffix ) ) {
				$title_text .= ' ' . $paged_suffix;
			}

			if ( ! empty( $add_htags ) && ! empty( $hashtags ) ) {
				$title_text .= ' ' . $hashtags;
			}

			if ( true === $do_encode ) {
				foreach ( array( 'title_text', 'sep' ) as $var ) {	// loop through variables
					$$var = SucomUtil::encode_html_emoji( $$var );
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'before title filter = "' . $title_text . '"' );
			}

			return apply_filters( $this->p->lca . '_title', $title_text, $mod, $add_htags, $md_idx, $sep );
		}

		/**
		 * $mod = true | false | post_id | array
		 * $md_idx = true | false | string | array
		 */
		public function get_description( $max_len = 160, $dots = '...', $mod = false, $r_cache = true, $add_htags = true, $do_encode = true, $md_idx = 'og_desc' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'render description' );	// begin timer

				$this->p->debug->log_args( array(
					'max_len'   => $max_len,
					'dots'      => $dots,
					'mod'       => $mod,
					'r_cache'   => $r_cache,
					'add_htags' => $add_htags, 	// true | false | numeric
					'do_encode' => $do_encode,
					'md_idx'    => $md_idx,
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

			if ( false === $md_idx ) {		// false would return the complete meta array
				$md_idx = '';
			} elseif ( true === $md_idx ) {		// true signals use of the standard / fallback value
				$md_idx = array( 'og_desc' );
			} elseif ( ! is_array( $md_idx ) ) {	// use fallback by default - get_options_multi() will do array_uniq()
				$md_idx = array( $md_idx, 'og_desc' );
			}

			$desc_text = false;
			$hashtags  = '';

			/**
			 * Skip if no metadata index / key name.
			 */
			if ( ! empty( $md_idx ) ) {

				$desc_text = is_object( $mod['obj'] ) ? $mod['obj']->get_options_multi( $mod['id'], $md_idx ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $desc_text ) ) {
						$this->p->debug->log( 'no custom description found for md_idx' );
					} else {
						$this->p->debug->log( 'custom description = "' . $desc_text . '"' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom description skipped: no md_idx value' );
			}

			/**
			 * Get seed if no custom meta description.
			 */
			if ( empty( $desc_text ) ) {

				$desc_text = apply_filters( $this->p->lca . '_description_seed', '', $mod, $add_htags, $md_idx );

				if ( ! empty( $desc_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'description seed = "' . $desc_text . '"' );
					}
				}
			}

			/**
			 * Check for hashtags in meta or seed desc, remove and then add again after shorten.
			 */
			if ( preg_match( '/^(.*)(( *#[a-z][a-z0-9\-]+)+)$/U', $desc_text, $match ) ) {
				$desc_text = $match[1];
				$hashtags = trim( $match[2] );
			} elseif ( $mod['is_post'] ) {
				if ( ! empty( $add_htags ) && ! empty( $this->p->options['og_desc_hashtags'] ) ) {
					$hashtags = $this->get_hashtags( $mod['id'], $add_htags );
				}
			}

			if ( $hashtags && $this->p->debug->enabled ) {
				$this->p->debug->log( 'hashtags found = "' . $hashtags . '"' );
			}

			/**
			 * If there's no custom description, and no pre-seed, then go ahead and generate the description value.
			 */
			if ( empty( $desc_text ) ) {

				if ( $mod['is_post'] ) {

					if ( empty( $mod['id'] ) && ! empty( $mod['post_type'] ) && is_post_type_archive() ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting the description for post type ' . $mod['post_type'] );
						}

						$post_type_obj = get_post_type_object( $mod['post_type'] );

						if ( ! empty( $post_type_obj->description ) ) {
							$desc_text = $post_type_obj->description;
						} else {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'post type ' . $mod['post_type'] . ' description is empty - using title value' );
							}
							if ( ! empty( $post_type_obj->labels->menu_name ) ) {
								$desc_text = sprintf( __( '%s Archive', 'wpsso' ), $post_type_obj->labels->menu_name );
							} elseif ( ! empty( $post_type_obj->name ) ) {
								$desc_text = sprintf( __( '%s Archive', 'wpsso' ), $post_type_obj->name );
							}
						}

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'before post_archive_description filter = ' . $desc_text );
						}
	
						$desc_text = apply_filters( $this->p->lca . '_post_archive_description', $desc_text, $mod );

					} else {

						$desc_text = $this->get_the_excerpt( $mod );

						/**
						 * If there's no excerpt, then fallback to the content.
						 */
						if ( empty( $desc_text ) ) {
	
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'getting the content for post ID ' . $mod['id'] );
							}
	
							$desc_text = $this->get_the_content( $mod, $r_cache, $md_idx );
	
							/**
							 * Ignore everything before the first paragraph if true.
							 */
							if ( $this->p->options['plugin_p_strip'] ) {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'removing all text before the first paragraph' );
								}

								$desc_text = preg_replace( '/^.*<p>/Ui', '', $desc_text );
							}
						}

						/**
						 * Fallback to the image alt value.
						 */
						if ( empty( $desc_text ) ) {
							if ( $mod['post_type'] === 'attachment' && strpos( $mod['post_mime'], 'image/' ) === 0 ) {
								$desc_text = get_post_meta( $mod['id'], '_wp_attachment_image_alt', true );
							}
						}
					}

				} elseif ( $mod['is_term'] ) {

					if ( SucomUtil::is_tag_page( $mod['id'] ) ) {
						if ( ! $desc_text = tag_description( $mod['id'] ) ) {
							$term_obj = get_tag( $mod['id'] );
							if ( ! empty( $term_obj->name ) ) {
								$desc_text = sprintf( __( 'Tagged with %s', 'wpsso' ), $term_obj->name );
							}
						}
					} elseif ( SucomUtil::is_category_page( $mod['id'] ) ) {
						if ( ! $desc_text = category_description( $mod['id'] ) ) {
							$desc_text = sprintf( __( '%s Category', 'wpsso' ), get_cat_name( $mod['id'] ) );
						}
					} else { 	// other taxonomies

						$term_obj = SucomUtil::get_term_object( $mod['id'], $mod['tax_slug'] );

						if ( ! empty( $term_obj->description ) ) {
							$desc_text = $term_obj->description;
						} elseif ( ! empty( $term_obj->name ) ) {
							$desc_text = sprintf( __( '%s Archive', 'wpsso' ), $term_obj->name );
						}
					}

				} elseif ( $mod['is_user'] ) {

					$user_obj = SucomUtil::get_user_object( $mod['id'] );

					if ( ! empty( $user_obj->description ) ) {
						$desc_text = $user_obj->description;
					} elseif ( ! empty( $user_obj->display_name ) ) {
						$desc_text = sprintf( __( 'Authored by %s', 'wpsso' ), $user_obj->display_name );
					}

					$desc_text = apply_filters( $this->p->lca . '_user_object_description', $desc_text, $user_obj );

				} elseif ( is_day() ) {

					$desc_text = sprintf( __( 'Daily Archive for %s', 'wpsso' ), get_the_date() );

				} elseif ( is_month() ) {

					$desc_text = sprintf( __( 'Monthly Archive for %s', 'wpsso' ), get_the_date('F Y') );

				} elseif ( is_year() ) {

					$desc_text = sprintf( __( 'Yearly Archive for %s', 'wpsso' ), get_the_date('Y') );

				} elseif ( SucomUtil::is_archive_page() ) {	// Just in case.

					$desc_text = apply_filters( $this->p->lca . '_archive_page_description', __( 'Archive Page', 'wpsso' ), $mod );
				}
			}

			/**
			 * Descriptions comprised entirely of html content will be empty after running cleanup_html_tags(),
			 * so remove the html before falling back to a generic description.
			 */
			$strlen_pre_cleanup = $this->p->debug->enabled ? strlen( $desc_text ) : 0;

			$desc_text = $this->p->util->cleanup_html_tags( $desc_text, true, $this->p->options['plugin_use_img_alt'] );

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

				if ( $mod['post_status'] === 'auto-draft' ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'post_status is auto-draft: using empty description' );
					}
				} elseif ( $mod['post_type'] === 'attachment' ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'post_type is attachment: using empty description' );
					}
				} elseif ( $desc_text = SucomUtil::get_site_description( $this->p->options, $mod ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'fallback SucomUtil::get_site_description() = "' . $desc_text . '"' );
					}
				} else {
					$desc_text = __( 'No Description', 'wpsso' );	// just in case
				}
			}

			if ( $max_len > 0 ) {

				$desc_text = apply_filters( $this->p->lca . '_description_pre_limit', $desc_text );

				if ( ! empty( $add_htags ) && ! empty( $hashtags ) ) {
					$max_len = $max_len - strlen( $hashtags ) - 1;
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'description strlen before limit length ' . strlen( $desc_text ) . ' (limiting to ' . $max_len . ' chars)' );
				}

				$desc_text = $this->p->util->limit_text_length( $desc_text, $max_len, $dots, false );	// $cleanup_html = false

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipped the description text length limit' );
			}

			if ( ! empty( $add_htags ) && ! empty( $hashtags ) ) {
				$desc_text .= ' ' . $hashtags;
			}

			if ( true === $do_encode ) {
				$desc_text = SucomUtil::encode_html_emoji( $desc_text );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'render description' );	// end timer
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'before description filter = "' . $desc_text . '"' );
			}

			return apply_filters( $this->p->lca . '_description', $desc_text, $mod, $add_htags, $md_idx );
		}

		public function get_the_excerpt( array $mod ) {

			$excerpt_text = '';

			// use the excerpt, if we have one
			if ( $mod['is_post'] && has_excerpt( $mod['id'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting the excerpt for post id ' . $mod['id'] );
				}

				$excerpt_text = get_post_field( 'post_excerpt', $mod['id'] );

				static $filter_excerpt = null;

				if ( null === $filter_excerpt ) {
					$filter_excerpt = empty( $this->p->options['plugin_filter_excerpt'] ) ? false : true;
					$filter_excerpt = apply_filters( $this->p->lca . '_filter_excerpt', $filter_excerpt, $mod );
				}

				if ( $filter_excerpt ) {
					$excerpt_text = $this->p->util->safe_apply_filters( array( 'get_the_excerpt', $excerpt_text ), $mod );
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipped the WordPress get_the_excerpt filters' );
				}
			}

			return $excerpt_text;
		}

		public function get_the_content( array $mod, $r_cache = true, $md_idx = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'mod'     => $mod,
					'r_cache' => $r_cache,
					'md_idx'  => $md_idx,
				) );
			}

			$sharing_url = $this->p->util->get_sharing_url( $mod );
			$filter_content = empty( $this->p->options['plugin_filter_content'] ) ? false : true;
			$filter_content = apply_filters( $this->p->lca . '_filter_content', $filter_content, $mod );

			static $cache_exp_secs = null;	// filter the cache expiration value only once

			$cache_md5_pre = $this->p->lca . '_c_';

			if ( ! isset( $cache_exp_secs ) ) {	// filter cache expiration if not already set
				$cache_exp_filter = $this->p->cf['wp']['wp_cache'][$cache_md5_pre]['filter'];
				$cache_opt_key = $this->p->cf['wp']['wp_cache'][$cache_md5_pre]['opt_key'];
				$cache_exp_secs = (int) apply_filters( $cache_exp_filter, $this->p->options[$cache_opt_key] );
			}

			/************************
			 * Retrieve the Content *
			 ************************/

			$cache_salt  = __METHOD__ . '(' . SucomUtil::get_mod_salt( $mod, $sharing_url ) . ')';
			$cache_id    = $cache_md5_pre . md5( $cache_salt );
			$cache_index = 'locale:' . SucomUtil::get_locale( $mod ) . '_filter:' . ( $filter_content ? 'true' : 'false' );
			$cache_index = SucomUtil::get_query_salt( $cache_index );	// add $wp_query args
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

				if ( $r_cache ) {

					$cache_array = wp_cache_get( $cache_id, __METHOD__ );

					if ( isset( $cache_array[$cache_index] ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'exiting early: cache index found in wp_cache' );
						}
						return $cache_array[$cache_index];
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

			$cache_array[$cache_index] = false;		// initialize the cache element
			$content_text =& $cache_array[$cache_index];	// reference the cache element
			$content_text = apply_filters( $this->p->lca . '_content_seed', '', $mod, $r_cache, $md_idx );

			if ( $content_text === false ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'content seed is false' );
				}

			} elseif ( ! empty( $content_text ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'content seed is "' . $content_text . '"' );
				}

			} elseif ( $mod['is_post'] ) {

				$content_text = get_post_field( 'post_content', $mod['id'] );

				if ( empty( $content_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: no post_content for post id ' . $mod['id'] );
					}
					return false;
				}
			}

			// save content length (for comparison) before making changes
			$strlen_before_filters = strlen( $content_text );

			// remove singlepics, which we detect and use before-hand
			$content_text = preg_replace( '/\[singlepic[^\]]+\]/', '', $content_text, -1, $count );

			if ( $count > 0 ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $count . ' [singlepic] shortcode(s) removed from content' );
				}
			}

			if ( $filter_content ) {

				$max_time = SucomUtil::get_const( 'WPSSO_CONTENT_FILTERS_MAX_TIME', 0.75 );
				$hook_bfo = SucomUtil::get_const( 'WPSSO_CONTENT_BLOCK_FILTER_OUTPUT', true );

				$content_text = $this->p->util->safe_apply_filters( array( 'the_content', $content_text ), $mod, $max_time, $hook_bfo );

				/**
				 * Cleanup for NextGEN Gallery pre-v2 album shortcode.
				 */
				unset ( $GLOBALS['subalbum'] );
				unset ( $GLOBALS['nggShowGallery'] );

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'the_content filters skipped (shortcodes not expanded)' );
			}

			$content_text = preg_replace( '/[\s\n\r]+/s', ' ', $content_text );		// put everything on one line
			$content_text = preg_replace( '/^.*<!--' . $this->p->lca . '-content-->(.*)<!--\/' . 
				$this->p->lca . '-content-->.*$/', '$1', $content_text );

			/**
			 * Remove "Google+" link and text.
			 */
			if ( strpos( $content_text, '>Google+<' ) !== false ) {
				$content_text = preg_replace( '/<a +rel="author" +href="" +style="display:none;">Google\+<\/a>/', ' ', $content_text );
			}

			if ( strpos( $content_text, '<p class="wp-caption-text">' ) !== false ) {

				$caption_prefix = isset( $this->p->options['plugin_p_cap_prefix'] ) ?
					$this->p->options['plugin_p_cap_prefix'] : 'Caption:';

				if ( ! empty( $caption_prefix ) ) {
					$content_text = preg_replace( '/<p class="wp-caption-text">/', '${0}' . $caption_prefix . ' ', $content_text );
				}
			}

			if ( strpos( $content_text, ']]>' ) !== false ) {
				$content_text = str_replace( ']]>', ']]&gt;', $content_text );
			}

			$strlen_after_filters = strlen( $content_text );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'content strlen before ' . $strlen_before_filters . ' and after changes / filters ' . $strlen_after_filters );
			}

			/**
			 * Apply filters before caching.
			 */
			$content_text = apply_filters( $this->p->lca . '_content', $content_text, $mod, $r_cache, $md_idx );

			if ( $cache_exp_secs > 0 ) {

				wp_cache_add_non_persistent_groups( array( __METHOD__ ) );	// Only some caching plugins support this feature.
				wp_cache_set( $cache_id, $cache_array, __METHOD__, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'content array saved to wp_cache for ' . $cache_exp_secs . ' seconds');
				}
			}

			return $content_text;
		}

		public function get_article_section( $post_id, $allow_none = false, $use_mod_opts = true ) {

			$section = '';

			/**
			 * Get custom article section from post meta.
			 */
			if ( $use_mod_opts ) {
				if ( ! empty( $post_id ) ) {
					$section = $this->p->m['util']['post']->get_options( $post_id, 'og_art_section' );	// Returns null if index key not found.
				}
			}

			if ( ! empty( $section ) ) {	// Must be a non-empty string.
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'found custom meta article section = ' . $section );
				}
			} else {
				$section = $this->p->options['og_art_section'];
			}

			if ( ! $allow_none ) {
				if ( $section === 'none' ) {
					$section = '';
				}
			}

			return apply_filters( $this->p->lca . '_article_section', $section, $post_id );
		}

		public function get_hashtags( $post_id, $add_hashtags = true ) {

			if ( empty( $add_hashtags ) ) {	// check for false or 0
				return;
			} elseif ( is_numeric( $add_hashtags ) ) {
				$max_hashtags = $add_hashtags;
			} elseif ( ! empty( $this->p->options['og_desc_hashtags'] ) ) {
				$max_hashtags = $this->p->options['og_desc_hashtags'];
			} else {
				return;
			}

			$hashtags = apply_filters( $this->p->lca . '_hashtags_seed', '', $post_id, $add_hashtags );

			if ( ! empty( $hashtags ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'hashtags seed = "' . $hashtags . '"' );
				}
			} else {
				$tags = array_slice( $this->get_tags( $post_id ), 0, $max_hashtags );
				if ( ! empty( $tags ) ) {
					// remove special character incompatible with Twitter
					$hashtags = SucomUtil::array_to_hashtags( $tags );
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'hashtags (max ' . $max_hashtags . ') = "' . $hashtags . '"' );
					}
				}
			}

			return apply_filters( $this->p->lca . '_hashtags', $hashtags, $post_id, $add_hashtags );
		}

		public function get_tags( $post_id ) {

			$tags = apply_filters( $this->p->lca . '_tags_seed', array(), $post_id );

			if ( ! empty( $tags ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'tags seed = "' . implode( ',', $tags ) . '"' );
				}

			} else {

				if ( is_singular() || ! empty( $post_id ) ) {
					$tags = $this->get_wp_tags( $post_id );
				} elseif ( is_search() ) {
					$tags = preg_split( '/ *, */', get_search_query( false ) );
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'raw tags = "' . implode( ', ', $tags ) . '"' );
				}

				$tags = array_unique( array_map( array( 'SucomUtil', 'sanitize_tag' ), $tags ) );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'sanitized tags = "' . implode( ', ', $tags ) . '"' );
				}
			}

			return apply_filters( $this->p->lca . '_tags', $tags, $post_id );
		}

		public function get_wp_tags( $post_id ) {

			$tags = apply_filters( $this->p->lca . '_wp_tags_seed', array(), $post_id );

			if ( ! empty( $tags ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'wp tags seed = "' . implode( ',', $tags ) . '"' );
				}

			} else {

				foreach ( wp_get_post_tags( $post_id, array( 'fields' => 'names') ) as $tag_name ) {
					$tags[] = $tag_name;
				}
			}

			return apply_filters( $this->p->lca . '_wp_tags', $tags, $post_id );
		}

		public function get_category_title( $term_id = 0, $tax_slug = '', $sep = null ) {

			$title_text = '';

			if ( is_object( $term_id ) ) {
				$term_obj = $term_id;
			} else {
				$term_obj = SucomUtil::get_term_object( $term_id, $tax_slug );
			}

			if ( null === $sep ) {
				$sep = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			if ( isset( $term_obj->name ) ) {

				$title_text = $term_obj->name . ' ';

				if ( ! empty( $sep ) ) {
					$title_text .= $sep . ' ';	// default value
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'name property missing in term object' );
			}

			if ( ! empty( $sep ) ) {

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

			return apply_filters( 'wp_title', $title_text, $sep, 'right' );
		}
	}
}
