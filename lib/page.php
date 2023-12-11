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

if ( ! class_exists( 'WpssoPage' ) ) {

	/*
	 * This class provides methods for the WebPage document.
	 *
	 * The use of "Page" in the WpssoPage classname refers to the WebPage document, not WordPress Pages.
	 *
	 * For methods related to WordPress Posts, Pages, and custom post types (which are all post objects), see the WpssoPost class.
	 */
	class WpssoPage {	// Aka WpssoWebPage.

		private $p;		// Wpsso class object.
		private $charset;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->charset = get_bloginfo( $show = 'charset', $filter = 'raw' );

			/*
			 * Maybe add the Validators toolbar menu.
			 */
			$add_toolbar_validate = empty( $this->p->options[ 'plugin_add_toolbar_validate' ] ) ? false : true;

			$add_toolbar_validate = apply_filters( 'wpsso_add_toolbar_validate', $add_toolbar_validate );

			if ( $add_toolbar_validate ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding validators toolbar' );
				}

				add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu_validate' ), WPSSO_TB_VALIDATE_MENU_ORDER, 1 );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'validators toolbar is disabled' );
			}

			/*
			 * Maybe add title tag filters.
			 *
			 * Since WordPress v4.4.
			 *
			 * See wordpress/wp-includes/general-template.php.
			 */
			if ( ! $this->p->util->is_title_tag_disabled() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding title tag filters' );
				}

				add_filter( 'pre_get_document_title', array( $this, 'pre_get_document_title' ), WPSSO_TITLE_TAG_PRIORITY, 1 );
				add_filter( 'document_title_separator', array( $this, 'document_title_separator' ), WPSSO_TITLE_TAG_PRIORITY, 1 );
				add_filter( 'document_title_parts', array( $this, 'document_title_parts' ), WPSSO_TITLE_TAG_PRIORITY, 1 );
				add_filter( 'document_title', array( $this, 'document_title' ), WPSSO_TITLE_TAG_PRIORITY, 1 );
				add_filter( 'get_wp_title_rss', array( $this, 'get_wp_title_rss' ), WPSSO_TITLE_TAG_PRIORITY, 1 );
				add_filter( 'get_bloginfo_rss', array( $this, 'get_bloginfo_rss' ), WPSSO_TITLE_TAG_PRIORITY, 2 );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'title tag is disabled' );
			}
		}

		/*
		 * This method is hooked to the 'admin_bar_menu' action and receives a reference to the $wp_admin_bar variable.
		 *
		 * WpssoPost->ajax_get_validate_submenu() also calls this method directly, supplying the post ID in $post_id.
		 */
		public function add_admin_bar_menu_validate( &$wp_admin_bar, $post_id = false ) {	// Pass by reference OK.

			if ( ! $user_id = get_current_user_id() ) {	// Just in case.

				return;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
			}

			$mod = $this->get_mod( $post_id );

			if ( $mod[ 'is_post' ] ) {

				$capability = 'page' === $mod[ 'post_type' ] ? 'edit_page' : 'edit_post';

			} elseif ( $mod[ 'is_term' ] ) {

				$tax_obj = get_taxonomy( $mod[ 'tax_slug' ] );

				$capability = $tax_obj->cap->edit_terms;

			} elseif ( $mod[ 'is_user' ] ) {

				$capability = 'edit_user';

			} else {	// Validators are only provided for known modules (post, term, and user).

				return false;	// Stop here.
			}

			if ( ! current_user_can( $capability, $mod[ 'id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot ' . $capability . ' for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] );
				}

				return false;	// Stop here.
			}

			/*
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

		/*
		 * Since WordPress v4.4.
		 *
		 * Filters the WordPress document title before it is generated. Returning a non-empty value skips the
		 * 'document_title_separator', 'document_title_parts', and 'document_title' filters.
		 *
		 * See wordpress/wp-includes/general-template.php.
		 */
		public function pre_get_document_title( $pre_title = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'received pre_title value (' . gettype( $pre_title ) . ') = ' . $pre_title );
			}

			/*
			 * If $pre_title is not an empty string, then maybe force an empty string to make sure the
			 * 'document_title_separator', 'document_title_parts', and 'document_title' filters are applied.
			 */
			if ( '' !== $pre_title ) {

				if ( 'wp_title' !== $this->p->options[ 'plugin_title_tag' ] ) {

					$pre_title = '';

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'returning an empty string to use document_title filters' );
					}
				}
			}

			return $pre_title;
		}

		/*
		 * Since WordPress v4.4.
		 *
		 * Filters the separator for the document title.
		 *
		 * See wordpress/wp-includes/general-template.php.
		 */
		public function document_title_separator( $title_sep = '-' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$title_sep = $this->maybe_get_title_sep();	// Returns default title separator (decoded).

			return $title_sep;
		}

		/*
		 * Since WordPress v4.4.
		 *
		 * Filters the parts of the document title.
		 *
		 * 	Array (
		 * 		[title]   => A Title
		 * 		[page]    => Page 2
		 * 		[site]    => Site Name
		 * 		[tagline] =>
		 * 	)
		 *
		 * The final array is imploded into a string using the $title_sep value.
		 *
		 *	$title = implode( ' ' . $title_sep . ' ', array_filter( $title ) );
		 *
		 * See wordpress/wp-includes/general-template.php.
		 */
		public function document_title_parts( $title_parts ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'title_parts (argument)', $title_parts );

				$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
			}

			/*
			 * Note that in_the_loop() can be true in both archive and singular pages.
			 */
			$use_post = apply_filters( 'wpsso_use_post', in_the_loop() ? true : false );

			$mod = $this->get_mod( $use_post );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mod', $mod );

				$this->p->debug->log( 'getting title for ' . $this->p->options[ 'plugin_title_tag' ] );
			}

			if ( 'wp_title' === $this->p->options[ 'plugin_title_tag' ] ) {

				if ( ! empty( $title_parts[ 'site' ] ) && empty( $title_parts[ 'title' ] ) ) {

					$title_parts[ 'tagline' ] = true;	// Add the tagline before returning the array.
				}

			} else {

				$md_key  = $this->p->options[ 'plugin_title_tag' ];
				$max_len = $this->p->options[ 'plugin_title_tag' ];

				/*
				 * Return a decoded title, which may be used in the RSS XML <title> tag.
				 */
				$title_parts[ 'title' ] = $this->get_title( $mod, $md_key, $max_len, $title_sep = null, $num_hashtags = false, $do_encode = false );

				if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

					unset( $title_parts[ 'site' ] );	// The title from WPSSO will be the site name.

					$title_parts[ 'tagline' ] = true;	// Add the tagline before returning the array.
				}

				unset( $title_parts[ 'page' ] );	// The title from WPSSO includes the page number.
			}

			if ( ! empty( $title_parts[ 'site' ] ) ) {

				$title_parts[ 'site' ] = $this->p->opt->get_text( 'plugin_title_part_site' );
			}

			if ( ! empty( $title_parts[ 'tagline' ] ) ) {

				$title_parts[ 'tagline' ] = $this->p->opt->get_text( 'plugin_title_part_tagline' );
			}

			/*
			 * Make sure the parts are ordered properly (just in case).
			 */
			$title_parts = array_merge( array( 'title' => null, 'page' => null, 'site' => null, 'tagline' => null ), $title_parts );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'title_parts (returned)', $title_parts );
			}

			return $title_parts;
		}

		/*
		 * Since WordPress v4.4.
		 *
		 * Filters the document title.
		 *
		 * See wordpress/wp-includes/general-template.php.
		 */
		public function document_title( $title ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'title = ' . $title );
			}

			if ( false !== strpos( $title, '%%' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
				}

				/*
				 * Note that in_the_loop() can be true in both archive and singular pages.
				 */
				$use_post = apply_filters( 'wpsso_use_post', in_the_loop() ? true : false );

				$mod = $this->get_mod( $use_post );

				$title = $this->p->util->inline->replace_variables( $title, $mod );
			}

			return SucomUtil::encode_html_emoji( $title );	// Does not double-encode.
		}

		/*
		 * Since WPSSO Core v15.20.0.
		 *
		 * Filters the string from wp_get_document_title() for get_wp_title_rss() and wp_title_rss() to convert HTML named
		 * entities into numbered entities.
		 *
		 * See wordpress/wp-includes/feed.php:
		 *	return apply_filters( 'get_wp_title_rss', wp_get_document_title(), $deprecated );
		 *
		 * See wordpress/wp-includes/feed-rdf.php:
		 *	<title><?php wp_title_rss(); ?></title>
		 *
		 * See wordpress/wp-includes/feed-rss2.php:
		 *	<title><?php wp_title_rss(); ?></title>
		 *
		 * See wordpress/wp-includes/feed-rss2-comments.php:
		 *	<title><?php printf( ent2ncr( __( 'Comments for %s' ) ), get_wp_title_rss() ); ?></title>
		 *
		 * See wordpress/wp-includes/feed-rss.php:
		 *	<title><?php wp_title_rss(); ?></title>
		 */
		public function get_wp_title_rss( $wp_title ) {

			return ent2ncr( $wp_title );	// Converts named entities into numbered entities.
		}

		/*
		 * Since WPSSO Core v15.20.0.
		 *
		 * Filters the string from get_bloginfo() for get_bloginfo_rss() and bloginfo_rss() to encode special characters
		 * and convert HTML named entities into numbered entities.
		 *
		 * See https://developer.wordpress.org/reference/functions/get_bloginfo/.
		 */
		public function get_bloginfo_rss( $bloginfo, $show ) {

			$bloginfo = SucomUtil::encode_html_emoji( $bloginfo );	// Does not double-encode.

			return ent2ncr( $bloginfo );	// Converts named entities into numbered entities.
		}

		/*
		 * Public method to sanitize arguments or modify values for get_title(), get_description(), etc.
		 *
		 * The $mod array argument is preferred but not required.
		 *
		 * $mod = true | false | post_id | $mod array
		 */
		public function maybe_get_mod( $mod ) {

			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->get_mod( $mod );
			}

			return $mod;
		}

		/*
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

			/*
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

			/*
			 * WpssoPage elements.
			 */
			global $wp_query;

			$mod[ 'query_vars' ] = $wp_query->query_vars;

			if ( empty( $mod[ 'paged' ] ) ) {	// False by default.

				if ( ! empty( $mod[ 'query_vars' ][ 'page' ] ) ) {

					$mod[ 'paged' ] = $mod[ 'query_vars' ][ 'page' ];

				} elseif ( ! empty( $mod[ 'query_vars' ][ 'paged' ] ) ) {

					$mod[ 'paged' ] = $mod[ 'query_vars' ][ 'paged' ];
				}
			}

			/*
			 * Note that 'paged_total' can be pre-defined by WpssoPost->get_mod() for posts with content (ie. singular)
			 * and paging in their content.
			 */
			if ( empty( $mod[ 'paged_total' ] ) ) {	// False by default.

				if ( ! empty( $wp_query->max_num_pages ) ) {

					$mod[ 'paged_total' ] = $wp_query->max_num_pages;
				}
			}

			if ( $mod[ 'paged' ] && $mod[ 'paged_total' ] && $mod[ 'paged' ] > $mod[ 'paged_total' ] ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'paged greater than paged_total - adjusting paged value' );
				}

				$mod[ 'paged' ] = $mod[ 'paged_total' ];
			}

			if ( empty( $mod[ 'comment_paged' ] ) ) {	// False by default.

				if ( ! empty( $mod[ 'query_vars' ][ 'cpage' ] ) ) {

					$mod[ 'comment_paged' ] = $mod[ 'query_vars' ][ 'cpage' ];
				}
			}

			$mod[ 'use_post' ] = $use_post;

			if ( empty( $mod[ 'name' ] ) ) {	// Not a comment, post, term, or user object.

				if ( is_home() ) {

					$mod[ 'is_home' ] = true;	// Home page (static or blog archive).

					$mod[ 'is_home_posts' ] = true;		// Static posts page or blog archive page.

				} elseif ( is_feed() ) {

					$mod[ 'is_feed' ] = true;

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

						/*
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

			return $mod;
		}

		/*
		 * See WpssoFaqShortcodeFaq->do_shortcode().
		 * See WpssoSchema->add_itemlist_data().
		 * See WpssoSchema->add_posts_data().
		 */
		public function get_posts_mods( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$page_posts_mods = array();

			if ( ! empty( $mod[ 'query_vars' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using WP_Query query_vars to get post mods' );
				}

				global $wp_query;

				$saved_wp_query = $wp_query;

				$wp_query = new WP_Query( $mod[ 'query_vars' ] );

				if ( $mod[ 'is_home_posts' ] ) {	// Static posts page or blog archive page.

					$wp_query->is_home = true;
				}

				if ( have_posts() ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'looping through posts' );
					}

					$have_num = 0;

					while ( have_posts() ) {

						$have_num++;

						the_post();	// Defines the $post global.

						global $post;

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'getting mod for post ID ' . $post->ID );
						}

						$page_posts_mods[] = $this->p->post->get_mod( $post->ID );
					}

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'retrieved ' . $have_num . ' post mods' );
					}

					rewind_posts();

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no posts to add' );
				}

				$wp_query = $saved_wp_query;

			} elseif ( ! empty( $mod[ 'obj' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using module object to get post mods' );
				}

				$page_posts_mods = $mod[ 'obj' ]->get_posts_mods( $mod );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no source to get post mods' );
			}

			$page_posts_mods = apply_filters( 'wpsso_page_posts_mods', $page_posts_mods, $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning ' . count( $page_posts_mods ) . ' post mods' );
			}

			return $page_posts_mods;
		}

		/*
		 * $mod = true | false | post_id | array
		 *
		 * $md_key = true | false | string | array
		 *
		 * $caption_type = 'title' | 'excerpt' | 'both'
		 *
		 * See WpssoRrssbFiltersEdit->filter_post_edit_share_rows().
		 */
		public function get_caption( $mod = false, $md_key = null, $caption_type = 'title', $caption_max_len = 0, $num_hashtags = false, $do_encode = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'mod'             => $mod,
					'md_key'          => $md_key,
					'caption_type'    => $caption_type,
					'caption_max_len' => $caption_max_len,
					'num_hashtags'    => $num_hashtags,	// True, false, or numeric.
					'do_encode'       => $do_encode,
				) );
			}

			switch ( $caption_type ) {

				case 'title':

					$md_key = $this->sanitize_md_key( $md_key, $def_key = 'og_title' );	// Returns an array of metadata keys (can be empty).

					break;

				case 'excerpt':

					$md_key = $this->sanitize_md_key( $md_key, $def_key = 'og_desc' );	// Returns an array of metadata keys (can be empty).

					break;

				case 'both':
				default:

					$md_key = $this->sanitize_md_key( $md_key, $def_key = 'og_caption' );	// Returns an array of metadata keys (can be empty).

					break;
			}

			$caption_max_len = $this->sanitize_max_len( $caption_max_len );		// Returns max integer for numeric, string, or array value.
			$mod             = $this->maybe_get_mod( $mod );			// Returns $mod array if not provided.
			$caption_text    = $this->maybe_get_opt_multi( $mod, $md_key );		// Returns null or custom value.
			$is_custom       = empty( $caption_text ) ? false : true;

			/*
			 * If there's no custom caption text, then go ahead and generate the caption text value.
			 */
			if ( empty( $caption_text ) ) {	// No custom value found.

				/*
				 * Request all values un-encoded, then encode once we have the complete caption text.
				 */
				switch ( $caption_type ) {

					case 'title':

						$caption_text = $this->get_title( $mod, $md_key_title = 'og_title', $caption_max_len,
							$title_sep = null, $num_hashtags, $do_encode_title = false );

						break;

					case 'excerpt':

						$caption_text = $this->get_description( $mod, $md_key_desc = 'og_desc', $caption_max_len,
							$num_hashtags, $do_encode_desc = false );

						break;

					case 'both':

						$title_sep = $this->maybe_get_title_sep();	// Returns default title separator (decoded).

						$caption_text = $this->get_title( $mod, $md_key_title = 'og_title', $max_len = 'og_title',
							$title_sep, $num_hashtags_title = false, $do_encode_title = false );

						$caption_text_len = strlen( trim( $caption_text . ' ' . $title_sep ) . ' ' );

						$adj_max_len = $caption_max_len - $caption_text_len;

						$caption_desc = $this->get_description( $mod, $md_key_desc = 'og_desc', $adj_max_len,
							$num_hashtags, $do_encode_desc = false );

						SucomUtil::add_title_part( $caption_text, $title_sep, $caption_desc );

						break;
				}
			}

			if ( true === $do_encode ) {

				$caption_text = SucomUtil::encode_html_emoji( $caption_text );	// Does not double-encode.

			} else {	// Just in case.

				$caption_text = html_entity_decode( SucomUtil::decode_utf8( $caption_text ), ENT_QUOTES, $this->charset );
			}

			return apply_filters( 'wpsso_caption', $caption_text, $mod, $num_hashtags, $md_key );
		}

		/*
		 * $mod = true | false | post_id | array
		 *
		 * $md_key = true | false | string | array
		 *
		 * Use $title_sep = false to avoid adding term parent names in the term title.
		 *
		 * WpssoUtilInline->replace_variables() is applied to the final title text.
		 *
		 * The WPSSO BC add-on uses $title_sep = false to avoid prefixing term parents in the term titles.
		 */
		public function get_title( $mod = false, $md_key = null, $max_len = 0, $title_sep = null, $num_hashtags = false, $do_encode = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'mod'          => $mod,
					'md_key'       => $md_key,
					'max_len'      => $max_len,
					'title_sep'    => $title_sep,
					'num_hashtags' => $num_hashtags,	// True, false, or numeric.
					'do_encode'    => $do_encode,
				) );
			}

			$mod        = $this->maybe_get_mod( $mod );					// Returns $mod array if not provided.
			$md_key     = $this->sanitize_md_key( $md_key, $def_key = 'seo_title' );	// Returns an array of metadata keys (can be empty).
			$max_len    = $this->sanitize_max_len( $max_len );				// Returns max integer for numeric, string, or array value.
			$dots       = $this->maybe_get_ellipsis();					// Returns default ellipsis (decoded).
			$title_sep  = $this->maybe_get_title_sep( $title_sep );				// Returns default title separator (decoded) if not provided.
			$title_text = $this->maybe_get_opt_multi( $mod, $md_key );			// Returns null or custom value.
			$is_custom  = empty( $title_text ) ? false : true;
			$hashtags   = '';

			/*
			 * Get seed if no custom meta title.
			 */
			if ( empty( $title_text ) ) {

				$title_text = apply_filters( 'wpsso_title_seed', '', $mod, $num_hashtags, $md_key, $title_sep );

				if ( ! empty( $title_text ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'title seed = ' . $title_text );
					}
				}
			}

			/*
			 * If there's no custom title, and no pre-seed, then go ahead and generate the title value.
			 *
			 * A custom or pre-seed title is expected to provide any hashtags, so get hashtags only when generating the
			 * title value.
			 */
			if ( empty( $title_text ) ) {

				$title_text = $this->get_the_title( $mod, $title_sep );

				$hashtags = $this->get_hashtags( $mod, $num_hashtags );
			}

			/*
			 * Add the page number if it's greater than 1 and we don't already have a '%%page%%' or '%%pagenumber%%'
			 * inline variable in the title.
			 */
			$page_number_transl = '';

			if ( $mod[ 'paged' ] > 1 ) {

				if ( false === strpos( $title_text, '%%page%%' ) && false === strpos( $title_text, '%%pagenumber%%' ) ) {

					if ( $mod[ 'paged_total' ] > 1 ) {

						$page_number_transl = sprintf( __( 'Page %1$d of %2$d', 'wpsso' ), $mod[ 'paged' ], $mod[ 'paged_total' ] );

					} else {

						$page_number_transl = sprintf( __( 'Page %1$d', 'wpsso' ), $mod[ 'paged' ] );
					}
				}
			}

			/*
			 * Replace inline variables in the string.
			 */
			if ( false !== strpos( $title_text, '%%' ) ) {

				/*
				 * Override the default 'title_sep' value.
				 */
				$title_text = $this->p->util->inline->replace_variables( $title_text, $mod, $atts = array( 'title_sep' => $title_sep ) );
			}

			/*
			 * Titles comprised entirely of HTML content will be empty after running cleanup_html_tags(), so remove the
			 * HTML tags before maybe falling back to the generic title.
			 */
			$title_text = $this->p->util->cleanup_html_tags( $title_text, $strip_tags = true, $use_img_alt = true );

			/*
			 * If there's still no title, then fallback to a generic version.
			 */
			if ( empty( $title_text ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'title is empty - using generic title text' );
				}

				$title_text = $this->p->opt->get_text( 'plugin_no_title_text' );	// No Title Text.
			}

			/*
			 * Check title against string length limits.
			 */
			if ( $max_len > 0 ) {

				/*
				 * If we have a page number, reduce the max length by the separator, page number, and two spaces.
				 */
				$adj_max_len = empty( $page_number_transl ) ? $max_len : $max_len - strlen ( $title_sep ) - strlen( $page_number_transl ) - 2;

				/*
				 * If we have any hashtags, further reduce the max title length by the hashtags and one space.
				 */
				$adj_max_len = empty( $hashtags ) ? $adj_max_len : $adj_max_len - strlen( $hashtags ) - 1;

				$title_text = $this->p->util->limit_text_length( $title_text, $adj_max_len, $dots, $cleanup_html = false );
			}

			/*
			 * Once the description length has been adjusted, we can add the page number and hashtags.
			 */
			if ( ! empty( $page_number_transl ) ) {

				SucomUtil::add_title_part( $title_text, $title_sep, $page_number_transl );
			}

			if ( ! empty( $hashtags ) ) {

				SucomUtil::add_title_part( $title_text, '', $hashtags );
			}

			/*
			 * Maybe return the values encoded (true by default).
			 */
			if ( $do_encode ) {

				$title_text = SucomUtil::encode_html_emoji( $title_text );	// Does not double-encode.

				$title_sep = SucomUtil::encode_html_emoji( $title_sep );	// Does not double-encode.
			}

			return apply_filters( 'wpsso_title', $title_text, $mod, $num_hashtags, $md_key, $title_sep, $is_custom );
		}

		/*
		 * $mod = true | false | post_id | array.
		 *
		 * $md_key = true | false | string | array.
		 *
		 * WpssoUtilInline->replace_variables() is applied to the final description text.
		 */
		public function get_description( $mod = false, $md_key = null, $max_len = 0, $num_hashtags = false, $do_encode = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'mod'          => $mod,
					'md_key'       => $md_key,
					'max_len'      => $max_len,
					'num_hashtags' => $num_hashtags, 	// True, false, or numeric.
					'do_encode'    => $do_encode,
				) );
			}

			$mod       = $this->maybe_get_mod( $mod );				// Returns $mod array if not provided.
			$md_key    = $this->sanitize_md_key( $md_key, $def_key = 'seo_desc' );	// Returns an array of metadata keys (can be empty).
			$max_len   = $this->sanitize_max_len( $max_len );			// Returns max integer for numeric, string, or array value.
			$dots      = $this->maybe_get_ellipsis();				// Returns default ellipsis (decoded).
			$desc_text = $this->maybe_get_opt_multi( $mod, $md_key );		// Returns null or custom value.
			$is_custom = empty( $desc_text ) ? false : true;
			$hashtags  = '';

			/*
			 * Get seed if no custom meta description.
			 */
			if ( empty( $desc_text ) ) {

				$desc_text = apply_filters( 'wpsso_description_seed', '', $mod, $num_hashtags, $md_key );

				if ( ! empty( $desc_text ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'description seed = ' . $desc_text );
					}
				}
			}

			/*
			 * If there's no custom description, and no pre-seed, then go ahead and generate the description value.
			 *
			 * A custom or pre-seed description is expected to provide any hashtags, so get hashtags only when
			 * generating the description value.
			 */
			if ( empty( $desc_text ) ) {

				$desc_text = $this->get_the_description( $mod );

				$hashtags = $this->get_hashtags( $mod, $num_hashtags );
			}

			/*
			 * Replace any inline variables in the string.
			 */
			if ( false !== strpos( $desc_text, '%%' ) ) {

				$desc_text = $this->p->util->inline->replace_variables( $desc_text, $mod );
			}

			/*
			 * Descriptions comprised entirely of HTML content will be empty after running cleanup_html_tags(), so
			 * remove the HTML tags before maybe falling back to the generic description.
			 */
			$desc_text = $this->p->util->cleanup_html_tags( $desc_text, $strip_tags = true, $use_img_alt = true );

			/*
			 * If there's still no description, then fallback to a generic version.
			 */
			if ( empty( $desc_text ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'description is empty - using generic description text' );
				}

				$desc_text = $this->p->opt->get_text( 'plugin_no_desc_text' );	// No Description Text.
			}

			/*
			 * Check description against string length limits.
			 */
			if ( $max_len > 0 ) {

				/*
				 * If we have any hashtags, reduce the max length by the hashtags and one space.
				 */
				$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

				$desc_text = $this->p->util->limit_text_length( $desc_text, $adj_max_len, $dots, $cleanup_html = false );
			}

			/*
			 * Once the description length has been adjusted, we can add the hashtags.
			 */
			if ( ! empty( $hashtags ) ) {

				SucomUtil::add_title_part( $desc_text, '', $hashtags );
			}

			/*
			 * Maybe return the values encoded (true by default).
			 */
			if ( $do_encode ) {

				$desc_text = SucomUtil::encode_html_emoji( $desc_text );	// Does not double-encode.
			}

			return apply_filters( 'wpsso_description', $desc_text, $mod, $num_hashtags, $md_key, $is_custom );
		}

		public function get_text( $mod = false, $md_key = null, $max_len = 0, $num_hashtags = false, $do_encode = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'mod'          => $mod,
					'md_key'       => $md_key,
					'max_len'      => $max_len,
					'num_hashtags' => $num_hashtags, 	// True, false, or numeric.
					'do_encode'    => $do_encode,
				) );
			}

			$mod       = $this->maybe_get_mod( $mod );					// Returns $mod array if not provided.
			$md_key    = $this->sanitize_md_key( $md_key, $def_key = 'schema_text' );	// Returns an array of metadata keys (can be empty).
			$max_len   = $this->sanitize_max_len( $max_len );				// Returns max integer for numeric, string, or array value.
			$dots      = $this->maybe_get_ellipsis();					// Returns default ellipsis (decoded).
			$text      = $this->maybe_get_opt_multi( $mod, $md_key );			// Returns null or custom value.
			$is_custom = empty( $text ) ? false : true;

			/*
			 * If there's no custom text, then go ahead and generate the text value.
			 */
			if ( empty( $title_text ) ) {

				$text = $this->get_the_text( $mod );

				$hashtags = $this->get_hashtags( $mod, $num_hashtags );
			}

			/*
			 * Check text against string length limits.
			 */
			if ( $max_len > 0 ) {

				/*
				 * If we have any hashtags, reduce the max length by the hashtags and one space.
				 */
				$adj_max_len = empty( $hashtags ) ? $max_len : $max_len - strlen( $hashtags ) - 1;

				$text = $this->p->util->limit_text_length( $text, $adj_max_len, $dots, $cleanup_html = false );
			}

			/*
			 * Once the text length has been adjusted, we can add the hashtags.
			 */
			if ( ! empty( $hashtags ) ) {

				SucomUtil::add_title_part( $text, '', $hashtags );
			}

			/*
			 * Maybe return the values encoded (true by default).
			 */
			if ( $do_encode ) {

				$text = SucomUtil::encode_html_emoji( $text );	// Does not double-encode.
			}

			return apply_filters( 'wpsso_text', $text, $mod, $num_hashtags, $md_key, $is_custom );
		}

		/*
		 * Use $title_sep = false to avoid adding term parent names in the term title.
		 *
		 * Note that WpssoUtilInline->replace_variables() is called in the WpssoPage->get_title() method, not in this one,
		 * so this method is safe to call in WpssoUtilInline->replace_variables().
		 *
		 * See WpssoUtilInline->replace_callback().
		 * See WpssoBcBreadcrumb->add_breadcrumblist_data().
		 */
		public function get_the_title( array $mod, $title_sep = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$title_sep  = $this->maybe_get_title_sep( $title_sep );	// Returns default title separator (decoded) if not provided.
			$title_text = '';

			/*
			 * Similar module type logic can be found in the following methods:
			 *
			 * See WpssoOpenGraph->get_mod_og_type().
			 * See WpssoPage->get_the_title().
			 * See WpssoPage->get_the_description().
			 * See WpssoSchema->get_mod_schema_type().
			 * See WpssoUtil->get_canonical_url().
			 */
			if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

				$title_text = SucomUtil::get_site_name( $this->p->options );

			} elseif ( $mod[ 'is_comment' ] ) {

				if ( is_numeric( $mod[ 'comment_rating' ] ) ) {

					$title_text = $this->p->opt->get_text( 'plugin_comment_review_title' );

				} elseif ( $mod[ 'comment_parent' ] ) {

					$title_text = $this->p->opt->get_text( 'plugin_comment_reply_title' );

				} else {

					$title_text = $this->p->opt->get_text( 'plugin_comment_title' );
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

						/*
						 * The get_the_title() function does not apply the 'wp_title' filter.
						 *
						 * See https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/post-template.php#L117.
						 */
						$title_text = get_the_title( $mod[ 'id' ] );

						$title_text = html_entity_decode( $title_text, ENT_QUOTES, $this->charset );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no post id' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no post type' );
				}

			} elseif ( $mod[ 'is_term' ] ) {

				/*
				 * The default 'plugin_term_page_title' value is '%%term_hierarchy%%'.
				 *
				 * If $title_sep is false, then use '%%term_title%%' instead.
				 */
				if ( false === $title_sep ) {

					$title_text = '%%term_title%%';

				} else {

					$title_text = $this->p->opt->get_text( 'plugin_term_page_title' );
				}

			} elseif ( $mod[ 'is_user' ] ) {

				$title_text = $this->p->opt->get_text( 'plugin_author_page_title' );

			} elseif ( $mod[ 'is_feed' ] ) {

				$title_text = $this->p->opt->get_text( 'plugin_feed_title' );

			} elseif ( $mod[ 'is_404' ] ) {

				$title_text = $this->p->opt->get_text( 'plugin_404_page_title' );

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

			return apply_filters( 'wpsso_the_title', $title_text, $mod, $title_sep );
		}

		public function get_the_description( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$desc_text = '';

			/*
			 * Similar module type logic can be found in the following methods:
			 *
			 * See WpssoOpenGraph->get_mod_og_type().
			 * See WpssoPage->get_the_title().
			 * See WpssoPage->get_the_description().
			 * See WpssoSchema->get_mod_schema_type().
			 * See WpssoUtil->get_canonical_url().
			 */
			if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

					$desc_text = $this->get_the_excerpt( $mod );
				}

				if ( empty( $desc_text ) ) {

					$desc_text = SucomUtil::get_site_description( $this->p->options );
				}

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

						/*
						 * If there's no excerpt, then fallback to the content.
						 */
						if ( empty( $desc_text ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'getting the content for post ID ' . $mod[ 'id' ] );
							}

							$desc_text = $this->get_the_content( $mod );

							/*
							 * Ignore everything before the first paragraph.
							 */
							if ( ! empty( $desc_text ) ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'removing text before the first paragraph' );
								}

								/*
								 * U = Inverts the "greediness" of quantifiers so that they are not greedy by default.
								 * i = Letters in the pattern match both upper and lower case letters.
								 *
								 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
								 */
								$desc_text = preg_replace( '/^.*<p[^>]*>/Usi', '', $desc_text );
							}
						}

						/*
						 * Fallback to the image alt value.
						 */
						if ( empty( $desc_text ) ) {

							if ( $mod[ 'is_attachment' ] && 'image' === $mod[ 'post_mime_group' ] ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'falling back to attachment image alt text' );
								}

								$desc_text = get_metadata( 'post', $mod[ 'id' ], '_wp_attachment_image_alt', $single = true );
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

					$term_obj = $this->p->term->get_mod_wp_object( $mod );

					if ( isset( $term_obj->description ) ) {

						$desc_text = $term_obj->description;
					}

					if ( '' === $desc_text ) {

						$desc_text = $this->p->opt->get_text( 'plugin_term_page_desc' );
					}
				}

			} elseif ( $mod[ 'is_user' ] ) {

				if ( $mod[ 'id' ] ) {	// Just in case.

					$user_obj = SucomUtil::get_user_object( $mod[ 'id' ] );

					if ( isset( $user_obj->description ) ) {

						$desc_text = $user_obj->description;
					}

					if ( '' === $desc_text ) {

						$desc_text = $this->p->opt->get_text( 'plugin_author_page_desc' );
					}
				}

			} elseif ( $mod[ 'is_404' ] ) {

				$desc_text = $this->p->opt->get_text( 'plugin_404_page_desc' );

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

			return apply_filters( 'wpsso_the_description', $desc_text, $mod );
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

						$excerpt_text = $this->p->util->safe_apply_filters( array( 'get_the_excerpt', $excerpt_text, $mod[ 'wp_obj' ] ), $mod );
					}
				}
			}

			return apply_filters( 'wpsso_the_excerpt', $excerpt_text, $mod );
		}

		/*
		 * The cache is cleared by WpssoAbstractWpMeta->clear_mod_cache().
		 */
		public function clear_the_content( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Note that SucomUtil::get_mod_salt() does not include the page number or locale.
			 */
			$canonical_url = $this->p->util->get_canonical_url( $mod );
			$cache_md5_pre = 'wpsso_c_';
			$cache_salt    = __CLASS__ . '::the_content(' . SucomUtil::get_mod_salt( $mod, $canonical_url ) . ')';
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'canonical url = ' . $canonical_url );
				$this->p->debug->log( 'wp cache salt = ' . $cache_salt );
				$this->p->debug->log( 'wp cache id = ' . $cache_id );
			}

			wp_cache_delete( $cache_id );

			return;
		}

		/*
		 * The cache is cleared by WpssoAbstractWpMeta->clear_mod_cache().
		 */
		public function get_the_content( array $mod, $flatten = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();	// Log execution time and memory usage.

				$this->p->debug->log_args( array(
					'mod' => $mod,
				) );
			}

			/*
			 * Note that SucomUtil::get_mod_salt() does not include the page number or locale.
			 */
			$filter_content = empty( $this->p->options[ 'plugin_filter_content' ] ) ? false : true;
			$filter_content = apply_filters( 'wpsso_the_content_filter_content', $filter_content );
			$filter_blocks  = apply_filters( 'wpsso_the_content_filter_blocks', true );
			$canonical_url  = $this->p->util->get_canonical_url( $mod );
			$cache_md5_pre  = 'wpsso_c_';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'wp_cache' );
			$cache_salt     = __CLASS__ . '::the_content(' . SucomUtil::get_mod_salt( $mod, $canonical_url ) . ')';
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

				$cache_array = wp_cache_get( $cache_id, __METHOD__ );

				if ( isset( $cache_array[ $cache_index ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: cache index found in wp_cache' );
					}

					$content =& $cache_array[ $cache_index ];

					/*
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
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'initializing new wp cache element' );
			}

			$cache_array[ $cache_index ] = false;		// Initialize the cache element.

			$content =& $cache_array[ $cache_index ];	// Reference the cache element.

			/*
			 * Apply the seed filter.
			 *
			 * Return false to prevent the commen or post from being used.
			 *
			 * See WpssoIntegEcomEdd->filter_the_content_seed().
			 * See WpssoIntegEcomWooCommerce->filter_the_content_seed().
			 */
			$content = apply_filters( 'wpsso_the_content_seed', '', $mod );

			if ( false === $content ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'content seed is false' );
				}

			} elseif ( ! empty( $content ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'content seed = ' . $content );
				}

			} elseif ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

				$content = $mod[ 'wp_obj' ]->post_content;

			} elseif ( $mod[ 'is_comment' ] && $mod[ 'id' ] ) {

				$content = $mod[ 'wp_obj' ]->comment_content;
			}

			/*
			 * Remove singlepics, which we detect and use before-hand.
			 */
			$count = null;

			$content = preg_replace( '/\[singlepic[^\]]+\]/', '', $content, $limit = -1, $count );

			if ( $count ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $count . ' singlepic shortcode(s) removed from content' );
				}
			}

			/*
			 * Maybe apply 'the_content' filter to expand shortcodes and blocks.
			 */
			if ( $filter_content ) {

				$use_bfo   = SucomUtil::get_const( 'WPSSO_CONTENT_BLOCK_FILTER_OUTPUT', true );
				$mtime_max = SucomUtil::get_const( 'WPSSO_CONTENT_FILTERS_MAX_TIME', 1.00 );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'applying the content filters' );	// Begin timer.
				}

				$content = $this->p->util->safe_apply_filters( array( 'the_content', $content ), $mod, $mtime_max, $use_bfo );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'applying the content filters' );	// End timer.
				}

			} else {

				/*
				 * Maybe fallback and apply only the 'do_blocks' filters.
				 */
				if ( $filter_blocks ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'applying the content do blocks' );	// Begin timer.
					}

					$content = do_blocks( $content );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'applying the content do blocks' );	// End timer.
					}
				}

				/*
				 * When the content filter is disabled, fallback and apply our own shortcode filter.
				 */
				if ( false !== strpos( $content, '[' ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'applying the content do shortcode filters' );	// Begin timer.
					}

					$content = apply_filters( 'wpsso_do_shortcode', $content );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'applying the content do shortcode filters' );	// End timer.
					}
				}
			}

			/*
			 * Maybe use only a certain part of the content.
			 */
			if ( false !== strpos( $content, 'wpsso-content' ) ) {

				$content = preg_replace( '/^.*<!-- *wpsso-content *-->(.*)<!--\/wpsso-content *-->.*$/Us', '$1', $content );
			}

			/*
			 * Maybe remove text between ignore markers.
			 */
			if ( false !== strpos( $content, 'wpsso-ignore' ) ) {

				$content = preg_replace( '/<!-- *wpsso-ignore *-->.*<!-- *\/wpsso-ignore *-->/Us', ' ', $content );
			}

			/*
			 * Remove "Google+" link and text.
			 */
			if ( false !== strpos( $content, '>Google+<' ) ) {

				$content = preg_replace( '/<a +rel="author" +href="" +style="display:none;">Google\+<\/a>/', ' ', $content );
			}

			/*
			 * Prefix caption text.
			 */
			if ( false !== strpos( $content, '<p class="wp-caption-text">' ) ) {

				$caption_prefix = $this->p->opt->get_text( 'plugin_p_cap_prefix' );

				if ( ! empty( $caption_prefix ) ) {

					$content = preg_replace( '/<p class="wp-caption-text">/', '${0}' . $caption_prefix . ' ', $content );
				}
			}

			/*
			 * Apply the filter.
			 */
			$content = apply_filters( 'wpsso_the_content', $content, $mod );

			/*
			 * Save content to non-persistant cache.
			 */
			if ( $cache_exp_secs > 0 ) {

				/*
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

			/*
			 * Maybe put everything on one line but do not cache the re-formatted content.
			 */
			return $flatten ? preg_replace( '/[\s\r\n]+/s', ' ', $content ) : $content;
		}

		/*
		 * Returns the content text, stripped of all HTML tags.
		 */
		public function get_the_text( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$text = $this->get_the_content( $mod );

			$text = preg_replace( '/<!\[CDATA\[.*\]\]>/Us', '', $text );

			$text = preg_replace( '/<pre[^>]*>.*<\/pre>/Us', '', $text );

			$text = $this->p->util->cleanup_html_tags( $text, $strip_tags = true, $use_img_alt = true );

			return apply_filters( 'wpsso_the_text', $text, $mod );
		}

		/*
		 * Returns a comma delimited text string of keywords (ie. post tag names).
		 */
		public function get_keywords_csv( array $mod, $md_key = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$keywords  = '';
			$is_custom = false;

			/*
			 * Check for custom keywords if a metadata index key is provided.
			 */
			if ( ! empty( $md_key ) && 'none' !== $md_key ) {	// $md_key can be a string or array.

				if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

					$keywords = $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key );

					if ( ! empty( $keywords ) ) {

						$is_custom = false;

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom keywords = ' . $keywords );
						}
					}
				}
			}

			/*
			 * If there's no custom keywords, then go ahead and generate the keywords value.
			 */
			if ( empty( $keywords ) ) {

				$tags = $this->get_tag_names( $mod );

				if ( ! empty( $tags ) ) {

					$keywords = SucomUtil::array_to_keywords( $tags );	// Returns a comma delimited text string.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'keywords = ' . $keywords );
					}
				}
			}

			return apply_filters( 'wpsso_keywords', $keywords, $mod, $md_key );
		}

		/*
		 * Returns a space delimited text string of hashtags.
		 */
		public function get_hashtags( array $mod, $num_hashtags = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$hashtags = apply_filters( 'wpsso_hashtags_seed', '', $mod, $num_hashtags );

			if ( ! empty( $hashtags ) ) {	// Seed hashtags returned.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'hashtags seed = ' . $hashtags );
				}

			} elseif ( false !== $num_hashtags ) {

				if ( true === $num_hashtags ) {

					$num_hashtags = $this->p->options[ 'og_desc_hashtags' ];
				}

				if ( is_numeric( $num_hashtags ) && $num_hashtags >= 1 ) {

					$tags = $this->get_tag_names( $mod );

					$tags = array_slice( $tags, 0, $num_hashtags );

					if ( ! empty( $tags ) ) {

						$hashtags = SucomUtil::array_to_hashtags( $tags );	// Remove special characters incompatible with Twitter.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'hashtags = ' . $hashtags );
						}
					}
				}
			}

			return apply_filters( 'wpsso_hashtags', $hashtags, $mod, $num_hashtags );
		}

		/*
		 * Returns an array of post tags.
		 */
		public function get_tag_names( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * See WpssoIntegEcomWooCommerce->filter_tag_names_seed().
			 */
			$tags = apply_filters( 'wpsso_tag_names_seed', null, $mod );

			if ( ! is_array( $tags ) ) {

				if ( $mod[ 'is_post' ] ) {

					if ( 'post' === $mod[ 'post_type' ] ) {

						$taxonomy = 'post_tag';

					} elseif ( 'page' === $mod[ 'post_type' ] && ! empty( $this->p->options[ 'plugin_page_tags' ] ) ) {

						$taxonomy = SucomUtil::get_const( 'WPSSO_PAGE_TAG_TAXONOMY' );

					} else $taxonomy = '';

					$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $mod[ 'post_type' ] . '_tag_taxonomy' );

					$taxonomy = apply_filters( $filter_name, $taxonomy, $mod );

					if ( ! empty( $taxonomy ) ) {

						$tags = wp_get_post_terms( $mod[ 'id' ], $taxonomy, $args = array( 'fields' => 'names' ) );
					}

					unset( $filter_name, $taxonomy );
				}
			}

			$tags = is_array( $tags ) ? array_unique( $tags ) : array();

			$tags = apply_filters( 'wpsso_tag_names', $tags, $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'tags', $tags );
			}

			return $tags;
		}

		/*
		 * Includes parent names in the term title by default, unless the $title_sep value is false.
		 *
		 * See WpssoUtilInline->replace_callback().
		 * See WpssoFaqShortcodeFaq->do_shortcode().
		 */
		public function get_term_title( $term_id = 0, $title_sep = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$term_obj   = false;
			$title_sep  = $this->maybe_get_title_sep( $title_sep );	// Returns default title separator (decoded) if not provided.
			$title_text = '';

			if ( is_object( $term_id ) ) {

				if ( ! $term_id instanceof WP_Term ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: object is not WP_Term' );
					}

					return $title_text;
				}

				$term_obj = $term_id;
				$term_id  = $term_obj->term_id;

			} elseif ( is_numeric( $term_id ) ) {

				$mod      = $this->p->term->get_mod( $term_id );
				$term_obj = $this->p->term->get_mod_wp_object( $mod );

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: term_id is not an object or numeric' );
				}

				return $title_text;
			}

			if ( ! empty( $term_obj->name ) ) {

				$title_text = $term_obj->name;
			}

			/*
			 * If we have a title separator and a parent, then redefine the title text with the parent list.
			 */
			if ( ! empty( $title_sep ) ) {

				if ( ! empty( $term_obj->parent ) ) {

					$term_parents = get_term_parents_list( $term_obj->term_id, $term_obj->taxonomy, $args = array(
						'format'    => 'name',			// Use term names or slugs for display.
						'separator' => ' ' . $title_sep . ' ',	// Separator for between the terms.
						'link'      => false,			// Whether to format as a link.
						'inclusive' => true,			// Include the term to get the parents for.
					) );

					if ( $term_parents && ! is_wp_error( $term_parents ) ) {

						/*
						 * Trim excess separator.
						 */
						$title_text = preg_replace( '/ *' . preg_quote( $title_sep, '/' ) . ' *$/', '', $term_parents );
					}
				}
			}

			return apply_filters( 'wpsso_term_title', $title_text, $term_id, $title_sep );
		}

		/*
		 * Returns an empty or formatted string (number with minutes).
		 */
		public function get_reading_time( array $mod ) {

			$reading_mins = $this->get_reading_mins( $mod );

			return $this->fmt_reading_mins( $reading_mins );
		}

		public function get_reading_mins( array $mod ) {

			$content       = $this->get_the_content( $mod );
			$words_per_min = WPSSO_READING_WORDS_PER_MIN;
			$reading_mins  = null;

			if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

				$reading_mins = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_reading_mins' );
			}

			if ( null === $reading_mins ) {	// Default value or no custom value.

				$reading_mins = SucomUtil::get_text_reading_mins( $content, $words_per_min );
			}

			return $reading_mins;
		}

		public function fmt_reading_mins( $reading_mins ) {

			return $reading_mins ? sprintf( _n( '%s minute', '%s minutes', $reading_mins, 'wpsso' ), $reading_mins ) : '';
		}

		/*
		 * Public method to sanitize arguments or modify values for get_title(), get_description(), etc.
		 *
		 * Returns default ellipsis (decoded) if not provided (ie. $ellipsis = null).
		 */
		private function maybe_get_ellipsis( $ellipsis = null ) {

			if ( null === $ellipsis ) {

				$ellipsis = html_entity_decode( $this->p->options[ 'og_ellipsis' ], ENT_QUOTES, $this->charset );
			}

			return $ellipsis;
		}

		/*
		 * Public method to sanitize arguments or modify values for get_title(), get_description(), etc.
		 *
		 * Returns default title separator (decoded) if not provided (ie. $title_sep = null).
		 */
		private function maybe_get_title_sep( $title_sep = null ) {

			if ( null === $title_sep ) {

				$title_sep = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, $this->charset );
			}

			return $title_sep;
		}

		/*
		 * Public method to sanitize arguments or modify values for get_title(), get_description(), etc.
		 */
		private function maybe_get_opt_multi( $mod, $md_key ) {

			if ( ! empty( $md_key ) && 'none' !== $md_key ) {	// Make sure we have something to work with.

				if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

					return $mod[ 'obj' ]->get_options_multi( $mod[ 'id' ], $md_key );
				}
			}

			return null;
		}

		/*
		 * Private method to sanitize arguments or modify values for get_title(), get_description(), etc.
		 *
		 * Returns an array of metadata keys (can be empty).
		 *
		 * $md_key = true | false | string | array
		 */
		private function sanitize_md_key( $md_key, $def_key = '' ) {

			if ( false === $md_key || 'none' === $md_key || '' === $md_key ) {	// Nothing to do.

				return array();

			} elseif ( true === $md_key || null === $md_key || $md_key === $def_key ) {	// Return the default key array.

				return WpssoConfig::get_md_keys_fallback( $def_key );
			}

			if ( ! is_array( $md_key ) ) {

				$md_key = WpssoConfig::get_md_keys_fallback( $md_key );
			}

			foreach ( WpssoConfig::get_md_keys_fallback( $def_key ) as $key ) {

				$md_key[] = $key;
			}

			$md_key = array_unique( $md_key );	// Just in case.

			return $md_key;
		}

		/*
		 * Private method to sanitize arguments or modify values for get_title(), get_description(), etc.
		 *
		 * Return 0 by default.
		 */
		private function sanitize_max_len( $max_len ) {

			if ( is_numeric( $max_len ) ) {

				return (int) $max_len;

			} elseif ( is_array( $max_len ) && isset( $max_len[ 'max' ] ) ) {

				return (int) $max_len[ 'max' ];

			} elseif ( is_string( $max_len ) ) {

				$input_limits = WpssoConfig::get_input_limits( $max_len );	// Uses a local cache.

				if ( ! empty( $input_limits[ 'max' ] ) ) {

					return (int) $input_limits[ 'max' ];
				}
			}

			return 0;
		}

		/*
		 * Deprecated on 2023/01/07.
		 */
		public function get_keywords( array $mod, $md_key = null ) {

			_deprecated_function( __METHOD__ . '()', '2023/01/07', $replacement = 'WpssoPage::get_keywords_csv()' );	// Deprecation message.

			return $this->get_keywords_csv( $mod, $md_key );
		}
	}
}
