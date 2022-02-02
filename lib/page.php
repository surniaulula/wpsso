<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoPage' ) ) {

	/**
	 * This class provides methods for the WebPage document.
	 *
	 * The use of "Page" in the WpssoPage classname refers to the WebPage document, not WordPress Pages.
	 *
	 * For methods related to WordPress Posts, Pages, and custom post types (which are all post objects), see the WpssoPost class.
	 */
	class WpssoPage {	// Aka WpssoWebPage.

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$show_validate = empty( $this->p->options[ 'plugin_show_validate_toolbar' ] ) ? false : true;

			$show_validate = (bool) apply_filters( 'wpsso_show_validate_toolbar', $show_validate );

			if ( $show_validate ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding validators toolbar' );
				}

				add_action( 'admin_bar_menu', array( $this, 'add_validate_toolbar' ), WPSSO_TB_VALIDATE_MENU_ORDER, 1 );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'validators toolbar is disabled' );
			}

			add_action( 'pre_get_document_title', array( $this, 'pre_get_document_title' ), 1000 );	// Since WP v4.4.
		}

		/**
		 * This method is hooked to the 'admin_bar_menu' action and receives a reference to the $wp_admin_bar variable.
		 *
		 * WpssoPost->ajax_get_validate_submenu() also calls this method directly, supplying the post ID in $use_post.
		 */
		public function add_validate_toolbar( &$wp_admin_bar, $use_post = false ) {

			if ( ! $user_id = get_current_user_id() ) {	// Just in case.

				return;
			}

			$use_post = apply_filters( 'wpsso_use_post', $use_post );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
			}

			$mod = $this->p->page->get_mod( $use_post );	// Get post/term/user ID, module name, and module object reference.

			/**
			 * We do not want to validate settings pages in the back-end, so validators are only provided for known
			 * modules (post, term, and user). If we're on the front-end, validating the current webpage URL is fine.
			 */
			$validators = $this->p->util->get_validators( $mod, $form = null );

			if ( ! empty( $validators ) ) {

				$parent_id  = 'wpsso-validate';
				$menu_icon  = '<span class="ab-icon dashicons-code-standards"></span>';
				$menu_title = _x( 'Validators', 'toolbar menu title', 'wpsso' );
				$menu_items = array();

				foreach ( $validators as $key => $el ) {

					if ( empty( $el[ 'type' ] ) ) {

						continue;
					}

					$menu_items[] = array(
						'id'     => $parent_id . '-' . $key,
						'title'  => $el[ 'type' ],
						'parent' => $parent_id,
						'href'   => $el[ 'url' ],
						'group'  => false,
						'meta'   => array(
							'class'  => empty( $el[ 'url' ] ) ? 'disabled' : '',
							'target' => '_blank',
							'title'  => $el[ 'title' ],
						),
					);
				}

				$wp_admin_bar->add_node( array(
					'id'     => $parent_id,
					'title'  => $menu_icon . $menu_title,
					'parent' => false,
					'href'   => false,
					'group'  => false,
					'meta'   => array(
						'html' => '<style type="text/css">#wp-admin-bar-wpsso-validate .disabled { opacity:0.5; filter:alpha(opacity=50); }</style>',
					),
				) );

				foreach ( $menu_items as $menu_item ) {

					$wp_admin_bar->add_node( $menu_item );
				}

				return $parent_id;
			}

			return false;
		}

		/**
		 * Filters the WordPress document title before it is generated.
		 */
		public function pre_get_document_title( $title = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$title_type = empty( $this->p->options[ 'plugin_document_title' ] ) ? 'wp_title' : $this->p->options[ 'plugin_document_title' ];

			if ( 'wp_title' === $title_type ) {	// Nothing to do.

				return $title;
			}

			$use_post = apply_filters( 'wpsso_use_post', false );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
			}

			$mod = $this->p->page->get_mod( $use_post );

			switch ( $title_type ) {

				case 'og_title':

					$title_max_len = $this->p->options[ 'og_title_max_len' ];

					$title = $this->p->page->get_title( $title_max_len, '...', $mod );

					break;

				case 'schema_title':

					$title = $this->p->page->get_title( $title_max_len = 0, $dots = '', $mod,
						$read_cache = true, $add_hashtags = false, $do_encode = true,
							$md_key = 'schema_title' );

					break;

				case 'schema_title_alt':

					$title_max_len = $this->p->options[ 'og_title_max_len' ];

					$title = $this->p->page->get_title( $title_max_len, $dots = '...', $mod,
						$read_cache = true, $add_hashtags = false, $do_encode = true,
							$md_key = 'schema_title_alt' );

					break;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning ' . $title_type . ' = ' . $title );
			}

			return $title;
		}

		/**
		 * Determine and return the post/user/term module array.
		 *
		 * If any custom modifications are required to the WP_Query 'query_vars', they should be done before the 'wp_head'
		 * action is triggered. The WpssoHead->show_head() method calls WpssoPage->get_mod() to determine the current
		 * WordPress object (comment, post, term, or user), if any, and saves the 'query_vars' value for WordPress archive
		 * queries.
		 */
		public function get_mod( $use_post = false, $mod = false, $wp_obj = false ) {

			if ( ! is_array( $mod ) ) {

				$mod = array();

			} elseif ( isset( $mod[ 'obj' ] ) && is_object( $mod[ 'obj' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: module object is already defined' );
				}

				return $mod;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'use_post is ' . SucomUtil::get_use_post_string( $use_post ) );
			}

			/**
			 * Check for known WP objects and set the object module name and its object ID.
			 */
			if ( is_object( $wp_obj ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'wp_obj argument is ' . get_class( $wp_obj ) . ' object' );
				}

				switch ( get_class( $wp_obj ) ) {

					case 'WP_Comment':

						$mod[ 'name' ] = 'comment';

						$mod[ 'id' ] = $wp_obj->ID;

						break;

					case 'WP_Post':

						$mod[ 'name' ] = 'post';

						$mod[ 'id' ] = $wp_obj->ID;

						break;

					case 'WP_Term':

						$mod[ 'name' ] = 'term';

						$mod[ 'id' ] = $wp_obj->term_id;

						break;

					case 'WP_User':

						$mod[ 'name' ] = 'user';

						$mod[ 'id' ] = $wp_obj->ID;

						break;
				}
			}

			if ( empty( $mod[ 'name' ] ) ) {

				if ( SucomUtil::is_post_page( $use_post ) ) {	// $use_post = true | false | post ID.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'is_post_page is true' );
					}

					$mod[ 'name' ] = 'post';

				} elseif ( SucomUtil::is_term_page() ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'is_term_page is true' );
					}

					$mod[ 'name' ] = 'term';

				} elseif ( SucomUtil::is_user_page() ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'is_user_page is true' );
					}

					$mod[ 'name' ] = 'user';

				} else {

					$mod[ 'name' ] = false;
				}
			}

			if ( empty( $mod[ 'id' ] ) ) {

				switch ( $mod[ 'name' ] ) {

					case 'post':

						$mod[ 'id' ] = SucomUtil::get_post_object( $use_post, 'id' );	// $use_post = true | false | post_id

						break;

					case 'term':

						$mod[ 'id' ] = SucomUtil::get_term_object( false, '', 'id' );

						break;

					case 'user':

						$mod[ 'id' ] = SucomUtil::get_user_object( false, 'id' );

						break;

					default:

						$mod[ 'id' ] = false;

						break;
				}
			}

			if ( ! empty( $mod[ 'name' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting $mod array from ' . $mod[ 'name' ] . ' module object' );
				}
			}

			switch ( $mod[ 'name' ] ) {

				case 'comment':

					$mod = $this->p->comment->get_mod( $mod[ 'id' ] );

					break;

				case 'post':

					$mod = $this->p->post->get_mod( $mod[ 'id' ] );

					break;

				case 'term':

					$mod = $this->p->term->get_mod( $mod[ 'id' ] );

					break;

				case 'user':

					$mod = $this->p->user->get_mod( $mod[ 'id' ] );

					break;

				default:

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'module object is unknown: merging $mod defaults' );
					}

					$mod = array_merge( WpssoAbstractWpMeta::get_mod_defaults(), $mod );

					break;
			}

			/**
			 * WpssoPage elements.
			 */
			global $wp_query;

			$mod[ 'query_vars' ] = $wp_query->query_vars;

			if ( empty( $mod[ 'paged' ] ) ) {	// False by default.

				if ( ! empty( $mod[ 'query_vars' ][ 'page' ] ) ) {

					$mod[ 'paged' ] = $mod[ 'query_vars' ][ 'page' ];

				} elseif ( ! empty( $mod[ 'query_vars' ][ 'paged' ] ) ) {

					$mod[ 'paged' ] = $mod[ 'query_vars' ][ 'paged' ];

				} else {

					$mod[ 'paged' ] = 1;
				}
			}

			/**
			 * The 'paged_total' can be pre-defined by WpssoPost->get_mod() for posts with content (ie. singular) and
			 * paging in their content.
			 */
			if ( empty( $mod[ 'paged_total' ] ) ) {	// False by default.

				$mod[ 'paged_total' ] = empty( $wp_query->max_num_pages ) ? 1 : $wp_query->max_num_pages;
			}

			if ( $mod[ 'paged' ] > $mod[ 'paged_total' ] ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'paged greater than paged_total - adjusting paged value' );
				}

				$mod[ 'paged' ] = $mod[ 'paged_total' ];
			}

			if ( empty( $mod[ 'comment_paged' ] ) ) {	// False by default.

				$mod[ 'comment_paged' ] = empty( $mod[ 'query_vars' ][ 'cpage' ] ) ? 1 : $mod[ 'query_vars' ][ 'cpage' ];
			}

			$mod[ 'use_post' ] = $use_post;

			if ( empty( $mod[ 'name' ] ) ) {	// Not a comment, post, term, or user object.

				if ( is_home() ) {

					$mod[ 'is_home' ] = true;	// Home page (static or blog archive).

					$mod[ 'is_home_posts' ] = true;		// Static posts page or blog archive page.

				} elseif ( is_404() ) {

					$mod[ 'is_404' ] = true;

				} elseif ( is_search() ) {

					$mod[ 'is_search' ] = true;

				} elseif ( is_archive() ) {

					$mod[ 'is_archive' ] = true;

					if ( is_date() ) {

						$mod[ 'is_date' ] = true;

						if ( is_year() ) {

							$mod[ 'is_year' ] = true;

						} elseif ( is_month() ) {

							$mod[ 'is_month' ] = true;

						} elseif ( is_day() ) {

							$mod[ 'is_day' ] = true;
						}
					}
				}

			} elseif ( empty( $mod[ 'id' ] ) ) {

				switch ( $mod[ 'name' ] ) {

					case 'post':

						/**
						 * If the query is a post with an ID of 0, then it may be a post type archive page
						 * that was setup incorrectly (ie. a post object without an ID or slug).
						 */
						if ( ! empty( $mod[ 'query_vars' ][ 'post_type' ] ) ) {

							$mod[ 'post_type' ] = $mod[ 'query_vars' ][ 'post_type' ];

							$post_type_obj = get_post_type_object( $mod[ 'post_type' ] );

							if ( is_object( $post_type_obj ) ) {	// Just in case.

								if ( isset( $post_type_obj->labels->name ) ) {

									$mod[ 'post_type_label_plural' ] = $post_type_obj->labels->name;
								}

								if ( isset( $post_type_obj->labels->singular_name ) ) {

									$mod[ 'post_type_label_single' ] = $post_type_obj->labels->singular_name;
								}

								if ( isset( $post_type_obj->public ) ) {

									$mod[ 'is_public' ] = $post_type_obj->public ? true : false;
								}

								if ( is_post_type_archive() ) {

									$mod[ 'is_post_type_archive' ] = true;

									$mod[ 'is_archive' ] = $mod[ 'is_post_type_archive' ];
								}
							}
						}

						break;
				}
			}


			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mod', $mod );
			}

			return $mod;
		}

		public function get_posts_mods( array $mod, array $extra_args = array() ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$page_posts_mods = array();

			if ( ! empty( $mod[ 'query_vars' ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'using WP_Query query_vars to get post mods' );
				}

				global $wp_query;

				$saved_wp_query = $wp_query;

				$wp_query = new WP_Query( $mod[ 'query_vars' ] );

				if ( $mod[ 'is_home_posts' ] ) {	// Static posts page or blog archive page.

					$wp_query->is_home = true;
				}

				if ( have_posts() ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'looping through posts' );
					}

					$have_num = 0;

					while ( have_posts() ) {

						$have_num++;

						the_post();	// Defines the $post global.

						global $post;

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'getting mod for post ID ' . $post->ID );
						}

						$page_posts_mods[] = $wpsso->post->get_mod( $post->ID );
					}

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'retrieved ' . $have_num . ' post mods' );
					}

					rewind_posts();

				} elseif ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'no posts to add' );
				}

				$wp_query = $saved_wp_query;

			} elseif ( is_object( $mod[ 'obj' ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'using module object to get post mods' );
				}

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( '$extra_args', $extra_args );
				}

				$page_posts_mods = $mod[ 'obj' ]->get_posts_mods( $mod, $extra_args );

			} else {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'no source to get post mods' );
				}
			}

			$page_posts_mods = apply_filters( 'wpsso_json_page_posts_mods', $page_posts_mods, $mod );

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'returning ' . count( $page_posts_mods ) . ' post mods' );
			}

			return $page_posts_mods;
		}

		public function get_quote( array $mod ) {

			$quote_text = apply_filters( 'wpsso_quote_seed', '', $mod );

			if ( ! empty( $quote_text ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'quote seed = ' . $quote_text );
				}

			} else {

				if ( has_excerpt( $mod[ 'id' ] ) ) {

					$quote_text = get_the_excerpt( $mod[ 'id' ] );	// Applies the 'get_the_excerpt' filter.

				} else {

					$quote_text = get_post_field( 'post_content', $mod[ 'id' ] );
				}
			}

			/**
			 * Remove shortcodes, etc., but don't strip html tags.
			 */
			$quote_text = $this->p->util->cleanup_html_tags( $quote_text, $strip_tags = false );

			return apply_filters( 'wpsso_quote', $quote_text, $mod );
		}

		/**
		 * $type = 'title' | 'excerpt' | 'both'
		 *
		 * $mod = true | false | post_id | array
		 *
		 * $md_key = true | false | string | array
		 *
		 * See WpssoRrssbFiltersEdit->filter_post_edit_share_rows().
		 */
		public function get_caption( $type = 'title', $max_len = 200, $mod = true, $read_cache = true,
			$add_hashtags = true, $do_encode = true, $md_key = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'type'         => $type,
					'max_len'      => $max_len,
					'mod'          => $mod,
					'read_cache'   => $read_cache,
					'add_hashtags' => $add_hashtags,	// True, false, or numeric.
					'do_encode'    => $do_encode,
					'md_key'       => $md_key,
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			$cap_text = '';

			$title_sep = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );

			if ( false === $md_key ) {	// False would return the complete meta array.

				$md_key       = '';
				$md_key_title = '';
				$md_key_desc  = '';

			} elseif ( true === $md_key ) {	// True signals the use of the standard / fallback value.

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

			} else {	// $md_key can be a string or array.

				$md_key_title = $md_key;
				$md_key_desc  = $md_key;
			}

			/**
			 * Check for custom caption if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && 'none' !== $md_key ) {	// $md_key can be a string or array.

				if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

					$cap_text = $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key );

					/**
					 * Extract custom hashtags, or get hashtags if $add_hashtags is true or numeric.
					 */
					list( $cap_text, $hashtags ) = $this->get_text_and_hashtags( $cap_text, $mod, $add_hashtags );

					if ( ! empty( $cap_text ) ) {

						if ( $max_len > 0 ) {

							$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

							$cap_text = $this->p->util->limit_text_length( $cap_text, $adj_max_len, '...', false );
						}

						$cap_text = trim( $cap_text . ' ' . $hashtags );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom caption = ' . $cap_text );
						}
					}
				}
			}

			$is_custom = empty( $cap_text ) ? false : true;

			/**
			 * If there's no custom caption text, then go ahead and generate the caption text value.
			 */
			if ( ! $is_custom ) {

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
						$cap_text = $this->get_title( 0, '', $mod, $read_cache, false, false, $md_key_title, $title_sep );

						/**
						 * Reduce the requested $max_len by the caption length we already have.
						 */
						$cap_len = strlen( trim( $cap_text . ' ' . $title_sep ) . ' ' );

						$adj_max_len = $max_len - $cap_len;

						$cap_desc = $this->get_description( $adj_max_len, '...', $mod, $read_cache, $add_hashtags, false, $md_key_desc );

						SucomUtilWP::add_title_value( $cap_text, $title_sep, $cap_desc );

						break;
				}
			}

			if ( true === $do_encode ) {

				$cap_text = SucomUtil::encode_html_emoji( $cap_text );	// Does not double-encode.

			} else {	// Just in case.

				$cap_text = html_entity_decode( SucomUtil::decode_utf8( $cap_text ), ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			return apply_filters( 'wpsso_caption', $cap_text, $mod, $add_hashtags, $md_key );
		}

		/**
		 * $mod = true | false | post_id | array
		 *
		 * $md_key = true | false | string | array
		 *
		 * $md_key can be a metadata options key, or an array of keys in order of preference (ie. from more specific to
		 * less specific). Example $md_key = array( 'seo_title', 'og_title' ).
		 *
		 * Use $title_sep = false to avoid adding parent names in the term title.
		 *
		 * Note that WpssoUtilInline->replace_variables() is applied to the final title text.
		 *
		 * Note that the WPSSO BC add-on uses $title_sep = false to avoid prefixing term parents in the term titles.
		 *
		 * See WpssoBcBreadcrumb->add_itemlist_data().
		 */
		public function get_title( $max_len = 70, $dots = '', $mod = false, $read_cache = true,
			$add_hashtags = false, $do_encode = true, $md_key = 'og_title', $title_sep = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'max_len'      => $max_len,
					'dots'         => $dots,
					'mod'          => $mod,
					'read_cache'   => $read_cache,
					'add_hashtags' => $add_hashtags,	// True, false, or numeric.
					'do_encode'    => $do_encode,
					'md_key'       => $md_key,
					'title_sep'    => $title_sep,
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			if ( false === $md_key ) {	// False would return the complete meta array.

				$md_key = '';

			} elseif ( true === $md_key ) {	// True signals use of the standard / fallback value.

				$md_key = array( 'og_title' );

			} elseif ( ! is_array( $md_key ) ) {	// Use fallback by default - get_options_multi() will do array_uniq().

				$md_key = array( $md_key, 'og_title' );

				$md_key = array_unique( $md_key );	// Just in case.
			}

			if ( null === $title_sep ) {	// Can be false.

				$title_sep = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			$title_text = '';

			/**
			 * Check for custom title if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && 'none' !== $md_key ) {	// $md_key can be a string or array.

				if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

					$title_text = $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key );

					if ( ! empty( $title_text ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom title = ' . $title_text );
						}
					}
				}
			}

			$is_custom = empty( $title_text ) ? false : true;

			/**
			 * Get seed if no custom meta title.
			 */
			if ( ! $is_custom ) {

				$title_text = apply_filters( 'wpsso_title_seed', '', $mod, $add_hashtags, $md_key, $title_sep );

				if ( ! empty( $title_text ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'title seed = ' . $title_text );
					}
				}
			}

			/**
			 * Extract custom hashtags, or get hashtags if $add_hashtags is true or numeric.
			 */
			list( $title_text, $hashtags ) = $this->get_text_and_hashtags( $title_text, $mod, $add_hashtags );

			/**
			 * If there's no custom title, and no pre-seed, then go ahead and generate the title value.
			 */
			if ( ! $is_custom && empty( $desc_text ) ) {

				$title_text = $this->get_the_title( $mod, $title_sep );
			}

			/**
			 * Replace any inline variables in the string.
			 */
			if ( false !== strpos( $title_text, '%%' ) ) {

				$title_text = $this->p->util->inline->replace_variables( $title_text, $mod, $atts = array( 'title_sep' => $title_sep ) );
			}

			/**
			 * Maybe add the page number.
			 */
			$pagesuffix = '';

			if ( $mod[ 'paged' ] > 1 ) {

				$pagesuffix .= trim( $title_sep . ' ' . sprintf( __( 'Page %s', 'wpsso' ), $mod[ 'paged' ] ) );
			}

			/**
			 * Check title against string length limits.
			 */
			if ( $max_len > 0 ) {

				$adj_max_len = empty( $pagesuffix ) ? $max_len : $max_len - strlen( $pagesuffix ) - 1;

				$adj_max_len = empty( $hashtags ) ? $adj_max_len : $adj_max_len - strlen( $hashtags ) - 1;

				$title_text = $this->p->util->limit_text_length( $title_text, $adj_max_len, $dots, $cleanup_html = false );
			}

			if ( ! empty( $pagesuffix ) ) {

				$title_text = trim( $title_text . ' ' . $pagesuffix );
			}

			if ( ! empty( $hashtags ) ) {

				$title_text = trim( $title_text . ' ' . $hashtags );
			}

			if ( $do_encode ) {

				foreach ( array( 'title_text', 'title_sep' ) as $var ) {	// Loop through variables.

					$$var = SucomUtil::encode_html_emoji( $$var );	// Does not double-encode.
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'title before filter = ' . $title_text );
			}

			$title_text = apply_filters( 'wpsso_title', $title_text, $mod, $add_hashtags, $md_key, $title_sep, $is_custom );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'title after filter = ' . $title_text );
			}

			return $title_text;
		}

		/**
		 * $mod = true | false | post_id | array.
		 *
		 * $md_key = true | false | string | array.
		 *
		 * $md_key can be a metadata options key, or an array of keys in order of preference (ie. from more specific to
		 * less specific). Example $md_key = array( 'seo_desc', 'og_desc' ).
		 *
		 * Note that WpssoUtilInline->replace_variables() is applied to the final description text.
		 */
		public function get_description( $max_len = 160, $dots = '...', $mod = false, $read_cache = true,
			$add_hashtags = true, $do_encode = true, $md_key = array( 'og_desc' ) ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'max_len'      => $max_len,
					'dots'         => $dots,
					'mod'          => $mod,
					'read_cache'   => $read_cache,
					'add_hashtags' => $add_hashtags, 	// True, false, or numeric.
					'do_encode'    => $do_encode,
					'md_key'       => $md_key,
				) );
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * 
			 * $mod = true | false | post_id | $mod array.
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			if ( false === $md_key ) {	// False would return the complete meta array.

				$md_key = array();

			} elseif ( true === $md_key ) {	// True signals use of the standard / fallback value.

				$md_key = array( 'og_desc' );

			} elseif ( ! is_array( $md_key ) ) {	// Use fallback by default - get_options_multi() will do array_uniq().

				$md_key = array( $md_key, 'og_desc' );

				$md_key = array_unique( $md_key );	// Just in case.
			}

			$desc_text = '';

			/**
			 * Check for custom description if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && 'none' !== $md_key ) {	// $md_key can be a string or array.

				if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

					$desc_text = $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key );

					if ( ! empty( $desc_text ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom description = ' . $desc_text );
						}
					}
				}
			}

			$is_custom = empty( $desc_text ) ? false : true;

			/**
			 * Get seed if no custom meta description.
			 */
			if ( ! $is_custom ) {

				$desc_text = apply_filters( 'wpsso_description_seed', '', $mod, $add_hashtags, $md_key );

				if ( ! empty( $desc_text ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'description seed = ' . $desc_text );
					}
				}
			}

			/**
			 * Extract custom hashtags, or get hashtags if $add_hashtags is true or numeric.
			 */
			list( $desc_text, $hashtags ) = $this->get_text_and_hashtags( $desc_text, $mod, $add_hashtags );

			/**
			 * If there's no custom description, and no pre-seed, then go ahead and generate the description value.
			 */
			if ( ! $is_custom && empty( $desc_text ) ) {

				/**
				 * Similar module type logic can be found in the following methods:
				 *
				 * See WpssoOpenGraph->get_mod_og_type().
				 * See WpssoPage->get_description().
				 * See WpssoPage->get_the_title().
				 * See WpssoSchema->get_mod_schema_type().
				 * See WpssoUtil->get_canonical_url().
				 */
				if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

					$desc_text = SucomUtil::get_site_description( $this->p->options );

				} elseif ( $mod[ 'is_comment' ] ) {

					if ( $mod[ 'id' ] ) {	// Just in case.

						$desc_text = get_comment_excerpt( $mod[ 'id' ] );
					}

				} elseif ( $mod[ 'is_post' ] ) {

					if ( $mod[ 'post_type' ] ) {	// Just in case.

						if ( $mod[ 'is_post_type_archive' ] ) {	// The post ID may be 0.

							$desc_text = $this->p->opt->get_text( 'plugin_pta_' . $mod[ 'post_type' ] . '_desc' );

							if ( empty( $desc_text ) ) {	// Just in case.

								$post_type_obj = get_post_type_object( $mod[ 'post_type' ] );

								if ( ! empty( $post_type_obj->description ) ) {

									$desc_text = $post_type_obj->description;
								}
							}

						} elseif ( $mod[ 'id' ] ) {	// Just in case.

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
								 * Ignore everything before the first paragraph.
								 */
								if ( ! empty( $desc_text ) ) {

									if ( $this->p->debug->enabled ) {

										$this->p->debug->log( 'removing text before the first paragraph' );
									}

									/**
									 * U = Inverts the "greediness" of quantifiers so that they are not greedy by default.
									 * i = Letters in the pattern match both upper and lower case letters. 
									 *
									 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
									 */
									$desc_text = preg_replace( '/^.*<p[^>]*>/Usi', '', $desc_text );
								}
							}

							/**
							 * Fallback to the image alt value.
							 */
							if ( empty( $desc_text ) ) {

								if ( $mod[ 'is_attachment' ] && strpos( $mod[ 'post_mime' ], 'image/' ) === 0 ) {

									if ( $this->p->debug->enabled ) {

										$this->p->debug->log( 'falling back to attachment image alt text' );
									}

									$desc_text = get_post_meta( $mod[ 'id' ], '_wp_attachment_image_alt', true );
								}
							}

						} elseif ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no post id' );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no post type' );
					}

				} elseif ( $mod[ 'is_term' ] ) {

					if ( $mod[ 'id' ] ) {	// Just in case.

						$term_obj = get_term( $mod[ 'id' ], $mod[ 'tax_slug' ] );

						if ( isset( $term_obj->description ) ) {

							$desc_text = $term_obj->description;
						}
					}

				} elseif ( $mod[ 'is_user' ] ) {

					if ( $mod[ 'id' ] ) {	// Just in case.

						$user_obj = SucomUtil::get_user_object( $mod[ 'id' ] );

						if ( isset( $user_obj->description ) ) {

							$desc_text = $user_obj->description;
						}
					}

				} elseif ( $mod[ 'is_search' ] ) {

					$desc_text = $this->p->opt->get_text( 'plugin_search_page_desc' );

				} elseif ( $mod[ 'is_archive' ] ) {

					if ( $mod[ 'is_date' ] ) {

						if ( $mod[ 'is_year' ] ) {

							$desc_text = $this->p->opt->get_text( 'plugin_year_page_desc' );

						} elseif ( $mod[ 'is_month' ] ) {

							$desc_text = $this->p->opt->get_text( 'plugin_month_page_desc' );

						} elseif ( $mod[ 'is_day' ] ) {

							$desc_text = $this->p->opt->get_text( 'plugin_day_page_desc' );
						}
					}
				}
			}

			/**
			 * Descriptions comprised entirely of HTML content will be empty after running cleanup_html_tags(), so
			 * remove the HTML tags before maybe falling back to the generic description.
			 */
			$desc_text = $this->p->util->cleanup_html_tags( $desc_text, $strip_tags = true, $use_img_alt = true );

			/**
			 * If there's still no description, then fallback to a generic version.
			 */
			if ( empty( $desc_text ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'description is empty - using generic description text' );
				}

				$desc_text = $this->p->opt->get_text( 'plugin_no_desc_text' );	// No Description Text.
			}

			/**
			 * Replace any inline variables in the string.
			 */
			if ( false !== strpos( $desc_text, '%%' ) ) {

				$desc_text = $this->p->util->inline->replace_variables( $desc_text, $mod );
			}

			/**
			 * Check description against string length limits.
			 */
			if ( $max_len > 0 ) {

				$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

				$desc_text = $this->p->util->limit_text_length( $desc_text, $adj_max_len, $dots, $cleanup_html = false );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'skipped the description length limit' );
			}

			if ( ! empty( $hashtags ) ) {

				$desc_text = trim( $desc_text . ' ' . $hashtags );	// Trim in case text is empty.
			}

			if ( $do_encode ) {

				$desc_text = SucomUtil::encode_html_emoji( $desc_text );	// Does not double-encode.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'description before filter = ' . $desc_text );
			}

			$desc_text = apply_filters( 'wpsso_description', $desc_text, $mod, $add_hashtags, $md_key, $is_custom );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'description after filter = ' . $desc_text );
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
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			$text = $this->get_the_text( $mod, $read_cache, $md_key );

			/**
			 * Extract custom hashtags, or get hashtags if $add_hashtags is true or numeric.
			 */
			list( $text, $hashtags ) = $this->get_text_and_hashtags( $text, $mod, $add_hashtags );

			if ( $max_len > 0 ) {

				$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

				$text = $this->p->util->limit_text_length( $text, $adj_max_len, $dots, $cleanup_html = false );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'skipped the text length limit' );
			}

			if ( ! empty( $hashtags ) ) {

				$text = trim( $text . ' ' . $hashtags );	// Trim in case text is empty.
			}

			if ( $do_encode ) {

				$text = SucomUtil::encode_html_emoji( $text );	// Does not double-encode.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'text before filter = ' . $text );
			}

			$text = apply_filters( 'wpsso_text', $text, $mod, $add_hashtags, $md_key );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'text after filter = ' . $text );
			}

			return $text;
		}

		/**
		 * Use $title_sep = false to avoid adding parent names in the term title.
		 *
		 * Note that WpssoUtilInline->replace_variables() is applied in WpssoPage->get_title(), not in this method.
		 *
		 * See WpssoUtilInline->get_defaults().
		 * See WpssoBcBreadcrumb->add_itemlist_data().
		 */
		public function get_the_title( array $mod, $title_sep = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( null === $title_sep ) {	// Can be false.

				$title_sep = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			$title_text = '';

			/**
			 * Similar module type logic can be found in the following methods:
			 *
			 * See WpssoOpenGraph->get_mod_og_type().
			 * See WpssoPage->get_description().
			 * See WpssoPage->get_the_title().
			 * See WpssoSchema->get_mod_schema_type().
			 * See WpssoUtil->get_canonical_url().
			 */
			if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

				$title_text = SucomUtil::get_site_name( $this->p->options );

			} elseif ( $mod[ 'is_comment' ] ) {

				if ( $mod[ 'id' ] ) {	// Just in case.

					$title_text = sprintf( __( 'Comment from %1$s on %2$s', 'wpsso' ),
						get_comment_author( $mod[ 'id' ] ), get_comment_date( $fmt = '', $mod[ 'id' ] ) );
				}

			} elseif ( $mod[ 'is_post' ] ) {

				if ( $mod[ 'post_type' ] ) {	// Just in case.

					if ( $mod[ 'is_post_type_archive' ] ) {	// The post ID may be 0.

						$title_text = $this->p->opt->get_text( 'plugin_pta_' . $mod[ 'post_type' ] . '_title' );

						if ( empty( $title_text ) ) {	// Just in case.

							$post_type_obj = get_post_type_object( $mod[ 'post_type' ] );

							if ( isset( $post_type_obj->label ) ) {	// Just in case.

								$title_text = $post_type_obj->label;
							}
						}

					} elseif ( $mod[ 'id' ] ) {

						/**
						 * The get_the_title() function does not apply the 'wp_title' filter.
						 *
						 * See https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/post-template.php#L117.
						 */
						$title_text = html_entity_decode( get_the_title( $mod[ 'id' ] ) );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no post id' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no post type' );
				}

			} elseif ( $mod[ 'is_term' ] ) {

				if ( $mod[ 'id' ] ) {	// Just in case.

					$term_obj = get_term( $mod[ 'id' ], $mod[ 'tax_slug' ] );

					/**
					 * Includes parent names in the term title if the $title_sep value is not empty.
					 *
					 * Use $title_sep = false to avoid adding parent names in the term title.
					 */
					$title_text = $this->get_term_title( $term_obj, $title_sep );
				}

			} elseif ( $mod[ 'is_user' ] ) {

				if ( $mod[ 'id' ] ) {	// Just in case.

					$user_obj = SucomUtil::get_user_object( $mod[ 'id' ] );

					if ( isset( $user_obj->display_name ) ) {	// Just in case.

						$title_text = $user_obj->display_name;
					}
				}

			} elseif ( $mod[ 'is_search' ] ) {

				$title_text = $this->p->opt->get_text( 'plugin_search_page_title' );

			} elseif ( $mod[ 'is_archive' ] ) {

				if ( $mod[ 'is_date' ] ) {

					if ( $mod[ 'is_year' ] ) {

						$title_text = $this->p->opt->get_text( 'plugin_year_page_title' );

					} elseif ( $mod[ 'is_month' ] ) {

						$title_text = $this->p->opt->get_text( 'plugin_month_page_title' );

					} elseif ( $mod[ 'is_day' ] ) {

						$title_text = $this->p->opt->get_text( 'plugin_day_page_title' );
					}
				}
			}

			/**
			 * Titles comprised entirely of HTML content will be empty after running cleanup_html_tags(), so remove the
			 * HTML tags before maybe falling back to the generic title.
			 */
			$title_text = $this->p->util->cleanup_html_tags( $title_text, $strip_tags = true, $use_img_alt = true );

			if ( empty( $title_text ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'title is empty - using generic title text' );
				}

				$title_text = $this->p->opt->get_text( 'plugin_no_title_text' );	// No Title Text.
			}

			/**
			 * Trim excess separator.
			 */
			if ( ! empty( $title_sep ) ) {

				$title_text = preg_replace( '/ *' . preg_quote( $title_sep, '/' ) . ' *$/', '', $title_text );
			}

			/**
			 * Apply the filter.
			 */
			$title_text = apply_filters( 'wpsso_the_title', $title_text, $mod, $title_sep );

			return $title_text;
		}

		public function get_the_excerpt( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$excerpt_text = '';

			if ( $mod[ 'is_post' ] ) {	// Only post objects have excerpts.

				if ( has_excerpt( $mod[ 'id' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting the excerpt for post ID ' . $mod[ 'id' ] );
					}

					$excerpt_text = get_post_field( 'post_excerpt', $mod[ 'id' ] );

					$filter_excerpt = empty( $this->p->options[ 'plugin_filter_excerpt' ] ) ? false : true;

					if ( $filter_excerpt ) {

						/**
						 * The $post_obj argument was added to 'get_the_excerpt' in WordPress v4.5.
						 */
						$post_obj = SucomUtil::get_post_object( $mod[ 'id' ] );

						$excerpt_text = $this->p->util->safe_apply_filters( array( 'get_the_excerpt', $excerpt_text, $post_obj ), $mod );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipped the WordPress get_the_excerpt filters' );
					}
				}
			}

			$excerpt_text = apply_filters( 'wpsso_the_excerpt', $excerpt_text, $mod );

			return $excerpt_text;
		}

		/**
		 * Returns an empty or formatted string (number with minutes).
		 */
		public function get_reading_time( array $mod ) {

			$reading_mins = $this->get_reading_mins( $mod );

			return $reading_mins ? sprintf( _n( '%s minute', '%s minutes', $reading_mins, 'wpsso' ), $reading_mins ) : '';
		}

		public function get_reading_mins( array $mod ) {

			$content = $this->get_the_content( $mod );

			$words_per_min = WPSSO_READING_WORDS_PER_MIN;

			$reading_mins = null;

			if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

				$reading_mins = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'reading_mins' );
			}

			if ( null === $reading_mins ) {	// Default value or no custom value.

				$reading_mins = SucomUtil::get_text_reading_mins( $content, $words_per_min );
			}

			return $reading_mins;
		}

		public function get_the_content( array $mod, $read_cache = true, $md_key = '', $flatten = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'mod'        => $mod,
					'read_cache' => $read_cache,
					'md_key'     => $md_key,
				) );
			}

			$filter_content = empty( $this->p->options[ 'plugin_filter_content' ] ) ? false : true;
			$canonical_url  = $this->p->util->get_canonical_url( $mod );
			$cache_md5_pre  = 'wpsso_c_';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'wp_cache' );
			$cache_salt     = __METHOD__ . '(' . SucomUtil::get_mod_salt( $mod, $canonical_url ) . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );
			$cache_index    = 'locale:' . SucomUtil::get_locale( $mod ) . '_filter:' . ( $filter_content ? 'true' : 'false' );
			$cache_array    = array();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'canonical url = ' . $canonical_url );
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

						$content =& $cache_array[ $cache_index ];

						/**
						 * Maybe put everything on one line but do not cache the re-formatted content.
						 */
						return $flatten ? preg_replace( '/[\s\r\n]+/s', ' ', $content ) : $content;

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

			$cache_array[ $cache_index ] = false;		// Initialize the cache element.

			$content =& $cache_array[ $cache_index ];	// Reference the cache element.

			/**
			 * Apply the seed filter.
			 *
			 * Return false to prevent the 'post_content' from being used.
			 *
			 * See WpssoProEcomEdd->filter_the_content_seed().
			 * See WpssoProEcomWoocommerce->filter_the_content_seed().
			 * See WpssoProSocialBuddypress->filter_the_content_seed().
			 */
			$content = apply_filters( 'wpsso_the_content_seed', '', $mod, $read_cache, $md_key );

			if ( false === $content ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'content seed is false' );
				}

			} elseif ( ! empty( $content ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'content seed = ' . $content );
				}

			} elseif ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

				$content = get_post_field( 'post_content', $mod[ 'id' ] );
			}

			/**
			 * Remove singlepics, which we detect and use before-hand.
			 */
			$content = preg_replace( '/\[singlepic[^\]]+\]/', '', $content, -1, $count );

			if ( $count > 0 ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $count . ' singlepic shortcode(s) removed from content' );
				}
			}

			/**
			 * Maybe apply 'the_content' filter to expand shortcodes and blocks.
			 */
			if ( $filter_content ) {

				$use_bfo = SucomUtil::get_const( 'WPSSO_CONTENT_BLOCK_FILTER_OUTPUT', true );

				$mtime_max = SucomUtil::get_const( 'WPSSO_CONTENT_FILTERS_MAX_TIME', 1.00 );

				$content = $this->p->util->safe_apply_filters( array( 'the_content', $content ), $mod, $mtime_max, $use_bfo );

				/**
				 * Cleanup for NextGEN Gallery pre-v2 album shortcode.
				 */
				unset ( $GLOBALS[ 'subalbum' ] );

				unset ( $GLOBALS[ 'nggShowGallery' ] );

			/**
			 * Maybe apply the 'do_blocks' filters.
			 */
			} elseif ( function_exists( 'do_blocks' ) ) {	// Since WP v5.0.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'calling do_blocks to filter the content text.' );
				}

				$content = do_blocks( $content );

				/**
				 * When the content filter is disabled, fallback and apply our own shortcode filter.
				 */
				if ( false !== strpos( $content, '[' ) ) {

					$content = apply_filters( 'wpsso_do_shortcode', $content );
				}
			}

			/**
			 * Maybe use only a certain part of the content.
			 */
			if ( false !== strpos( $content, 'wpsso-content' ) ) {

				$content = preg_replace( '/^.*<!-- *wpsso-content *-->(.*)<!--\/wpsso-content *-->.*$/Us', '$1', $content );
			}

			/**
			 * Maybe remove text between ignore markers.
			 */
			if ( false !== strpos( $content, 'wpsso-ignore' ) ) {

				$content = preg_replace( '/<!-- *wpsso-ignore *-->.*<!-- *\/wpsso-ignore *-->/Us', ' ', $content );
			}

			/**
			 * Remove "Google+" link and text.
			 */
			if ( false !== strpos( $content, '>Google+<' ) ) {

				$content = preg_replace( '/<a +rel="author" +href="" +style="display:none;">Google\+<\/a>/', ' ', $content );
			}

			/**
			 * Prefix caption text.
			 */
			if ( false !== strpos( $content, '<p class="wp-caption-text">' ) ) {

				$caption_prefix = $this->p->opt->get_text( 'plugin_p_cap_prefix' );

				if ( ! empty( $caption_prefix ) ) {

					$content = preg_replace( '/<p class="wp-caption-text">/', '${0}' . $caption_prefix . ' ', $content );
				}
			}

			/**
			 * Apply the filter.
			 */
			$content = apply_filters( 'wpsso_the_content', $content, $mod, $read_cache, $md_key );

			/**
			 * Save content to non-persistant cache.
			 */
			if ( $cache_exp_secs > 0 ) {

				/**
				 * Adds a group or set of groups to the list of non-persistent groups.
				 *
				 * Note that only a few caching plugins support this feature.
				 */
				wp_cache_add_non_persistent_groups( array( __METHOD__ ) );

				wp_cache_set( $cache_id, $cache_array, __METHOD__, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'content array saved to wp_cache for ' . $cache_exp_secs . ' seconds');
				}
			}

			/**
			 * Maybe put everything on one line but do not cache the re-formatted content.
			 */
			return $flatten ? preg_replace( '/[\s\r\n]+/s', ' ', $content ) : $content;
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
			if ( ! empty( $md_key ) && 'none' !== $md_key ) {	// $md_key can be a string or array.

				if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

					$text = $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key );

					if ( ! empty( $text ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom text = ' . $text );
						}
					}
				}
			}

			$is_custom = empty( $text ) ? false : true;

			/**
			 * If there's no custom text, then go ahead and generate the text value.
			 */
			if ( ! $is_custom ) {

				$text = $this->get_the_content( $mod, $read_cache, $md_key );

				$text = preg_replace( '/<!\[CDATA\[.*\]\]>/Us', '', $text );

				$text = preg_replace( '/<pre[^>]*>.*<\/pre>/Us', '', $text );

				$text = $this->p->util->cleanup_html_tags( $text, $strip_tags = true, $use_img_alt = true );
			}

			return $text;
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
			if ( ! empty( $md_key ) && 'none' !== $md_key ) {	// $md_key can be a string or array.

				if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

					$keywords = $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key );

					if ( ! empty( $keywords ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom keywords = ' . $keywords );
						}
					}
				}
			}

			$is_custom = empty( $keywords ) ? false : true;

			/**
			 * If there's no custom keywords, then go ahead and generate the keywords value.
			 */
			if ( ! $is_custom ) {

				$tags = $this->get_tag_names( $mod );

				if ( ! empty( $tags ) ) {

					$keywords = SucomUtil::array_to_keywords( $tags );	// Returns a comma delimited text string.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'keywords = ' . $keywords );
					}
				}
			}

			return $keywords;
		}

		/**
		 * Extract custom hashtags, or get hashtags if $add_hashtags is true or numeric.
		 */
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

				$this->p->debug->log( 'hashtags = ' . $hashtags );
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
			if ( empty( $add_hashtags ) ) {	// False or 0.

				return '';

			} elseif ( is_numeric( $add_hashtags ) ) {	// Return a specific number of hashtags.

				$max_hashtags = $add_hashtags;

			} elseif ( ! empty( $this->p->options[ 'og_desc_hashtags' ] ) ) {	// Return the default number of hashtags.

				$max_hashtags = $this->p->options[ 'og_desc_hashtags' ];

			} else {	// Just in case.

				return '';
			}

			$hashtags = apply_filters( 'wpsso_hashtags_seed', '', $mod, $add_hashtags );

			if ( ! empty( $hashtags ) ) {	// Seed hashtags returned.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'hashtags seed = ' . $hashtags );
				}

			} else {

				$tags = $this->get_tag_names( $mod );

				$tags = array_slice( $tags, 0, $max_hashtags );

				if ( ! empty( $tags ) ) {

					$hashtags = SucomUtil::array_to_hashtags( $tags );	// Remove special characters incompatible with Twitter.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'hashtags (max ' . $max_hashtags . ') = ' . $hashtags );
					}
				}
			}

			return apply_filters( 'wpsso_hashtags', $hashtags, $mod, $add_hashtags );
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

			/**
			 * The 'wpsso_tag_names_seed' filter is hooked by the WpssoProEcomEdd, WpssoProEcomWoocommerce, and
			 * WpssoProForumBbpress classes.
			 */
			$tags = apply_filters( 'wpsso_tag_names_seed', array(), $mod );

			if ( ! empty( $tags ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'tags seed = ' . implode( ',', $tags ) );
				}

			} else {

				if ( $mod[ 'is_post' ] ) {

					if ( 'post' === $mod[ 'post_type' ] ) {

						$taxonomy = 'post_tag';

					} elseif ( 'page' === $mod[ 'post_type' ] && ! empty( $this->p->options[ 'plugin_page_tags' ] ) ) {

						$taxonomy = SucomUtil::get_const( 'WPSSO_PAGE_TAG_TAXONOMY' );

					} else {

						$taxonomy = '';
					}

					$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $mod[ 'post_type' ] . '_tag_taxonomy' );

					$taxonomy = apply_filters( $filter_name, $taxonomy, $mod );

					if ( ! empty( $taxonomy ) ) {

						$tags = wp_get_post_terms( $mod[ 'id' ], $taxonomy, $args = array( 'fields' => 'names' ) );
					}
				}

				$tags = array_unique( $tags );
			}

			$tags = apply_filters( 'wpsso_tag_names', $tags, $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'tags', $tags );
			}

			return $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ] = $tags;
		}

		/**
		 * Includes parent names in the term title if $title_sep is not false.
		 */
		public function get_term_title( $term_id = 0, $title_sep = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$term_obj = false;

			$title_text = '';

			if ( is_object( $term_id ) ) {

				if ( is_wp_error( $term_id ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: term object is WP_Error' );
					}

					return $title_text;
				}

				$term_obj = $term_id;

				$term_id = $term_obj->term_id;

			} elseif ( is_numeric( $term_id ) ) {

				$mod = $this->p->term->get_mod( $term_id );

				$term_obj = get_term( $mod[ 'id' ], $mod[ 'tax_slug' ] );

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: term_id is not an object or numeric' );
				}

				return $title_text;
			}

			if ( null === $title_sep ) {

				$title_sep = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			if ( ! empty( $term_obj->name ) ) {	// Just in case.

				$title_text = $term_obj->name . ' ';

				if ( ! empty( $title_sep ) ) {

					$title_text .= $title_sep . ' ';	// Default behavior.
				}
			}

			if ( ! empty( $title_sep ) ) {	// Just in case.

				if ( ! empty( $term_obj->parent ) ) {

					$term_parents = get_term_parents_list( $term_obj->term_id, $term_obj->taxonomy, $args = array(
						'format'    => 'name',
						'separator' => ' ' . $title_sep . ' ',
						'link'      => false,
						'inclusive' => true,
					) );

					if ( is_wp_error( $term_parents ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'get_term_parents_list error: ' . $term_parents->get_error_message() );
						}

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'get_term_parents_list = ' . $term_parents );
						}

						if ( ! empty( $term_parents ) ) {

							$title_text = $term_parents;
						}
					}
				}

				/**
				 * Trim excess separator.
				 */
				$title_text = preg_replace( '/ *' . preg_quote( $title_sep, '/' ) . ' *$/', '', $title_text );
			}

			return $title_text;
		}
	}
}
