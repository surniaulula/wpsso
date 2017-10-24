<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoPost' ) ) {

	/*
	 * This class is extended by gpl/util/post.php or pro/util/post.php
	 * and the class object is created as $this->p->m['util']['post'].
	 */
	class WpssoPost extends WpssoMeta {

		public function __construct() {
		}

		protected function add_actions() {

			if ( is_admin() ) {
				if ( ! empty( $_GET ) || basename( $_SERVER['PHP_SELF'] ) === 'post-new.php' ) {
					// load_meta_page() priorities: 100 post, 200 user, 300 term
					// sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties
					add_action( 'current_screen', array( &$this, 'load_meta_page' ), 100, 1 );
					add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ) );
				}

				add_action( 'save_post', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'save_post', array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );
				add_action( 'edit_attachment', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'edit_attachment', array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );
			}

			// add the columns when doing AJAX as well to allow Quick Edit to add the required columns
			if ( is_admin() || SucomUtil::get_const( 'DOING_AJAX' ) ) {

				// only use public post types (to avoid menu items, product variations, etc.)
				$ptns = $this->p->util->get_post_types( 'names' );

				if ( is_array( $ptns ) ) {
					foreach ( $ptns as $ptn ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'adding column filters for post type '.$ptn );
						}

						// https://codex.wordpress.org/Plugin_API/Filter_Reference/manage_$post_type_posts_columns
						add_filter( 'manage_'.$ptn.'_posts_columns',
							array( &$this, 'add_post_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );

						add_filter( 'manage_edit-'.$ptn.'_sortable_columns',
							array( &$this, 'add_sortable_columns' ), 10, 1 );

						// https://codex.wordpress.org/Plugin_API/Action_Reference/manage_$post_type_posts_custom_column
						add_action( 'manage_'.$ptn.'_posts_custom_column',
							array( &$this, 'show_column_content' ), 10, 2 );
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding column filters for media library' );
				}

				add_filter( 'manage_media_columns', array( &$this, 'add_media_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );
				add_filter( 'manage_upload_sortable_columns', array( &$this, 'add_sortable_columns' ), 10, 1 );
				add_action( 'manage_media_custom_column', array( &$this, 'show_column_content' ), 10, 2 );

				/*
				 * The 'parse_query' action is hooked ONCE in the WpssoPost class
				 * to set the column orderby for post, term, and user edit tables.
				 */
				add_action( 'parse_query', array( &$this, 'set_column_orderby' ), 10, 1 );
				add_action( 'get_post_metadata', array( &$this, 'check_sortable_metadata' ), 10, 4 );
			}


			if ( ! empty( $this->p->options['plugin_shortlink'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding get_shortlink filter' );
				}
				// filters the shortlink for a post
				add_filter( 'get_shortlink', array( &$this, 'get_shortlink' ), 9000, 4 );
			}

			if ( ! empty( $this->p->options['plugin_clear_for_comment'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding clear cache for comment actions' );
				}
				// fires when a comment is inserted into the database
				add_action ( 'comment_post', array( &$this, 'clear_cache_for_new_comment' ), 10, 2 );
	
				// fires before transitioning a comment's status from one to another
				add_action ( 'wp_set_comment_status', array( &$this, 'clear_cache_for_comment_status' ), 10, 2 );
			}
		}

		public function get_mod( $mod_id ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mod = WpssoMeta::$mod_defaults;
			$mod['id'] = (int) $mod_id;
			$mod['name'] = 'post';
			$mod['obj'] =& $this;
			/*
			 * Post
			 */
			$mod['is_post'] = true;
			$mod['is_home_page'] = SucomUtil::is_home_page( $mod_id );
			$mod['is_home_index'] = $mod['is_home_page'] ? false : SucomUtil::is_home_index( $mod_id );
			$mod['is_home'] = $mod['is_home_page'] || $mod['is_home_index'] ? true : false;
			$mod['post_type'] = get_post_type( $mod_id );					// post type name
			$mod['post_status'] = get_post_status( $mod_id );				// post status name
			$mod['post_author'] = (int) get_post_field( 'post_author', $mod_id );		// post author id

			// hooked by the 'coauthors' pro module
			return apply_filters( $this->p->cf['lca'].'_get_post_mod', $mod, $mod_id );
		}

		public function get_posts( array $mod, $posts_per_page = false, $paged = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$lca = $this->p->cf['lca'];

			if ( $posts_per_page === false ) {
				$posts_per_page = apply_filters( $lca.'_posts_per_page', get_option( 'posts_per_page' ), $mod );
			}

			if ( $paged === false ) {
				$paged = get_query_var( 'paged' );
			}

			if ( ! $paged > 1 ) {
				$paged = 1;
			}

			$posts = get_posts( array(
				'posts_per_page' => $posts_per_page,
				'paged' => $paged,
				'post_status' => 'publish',
				'post_type' => 'any',
				'post_parent' => $mod['id'],
				'child_of' => $mod['id'],	// only include direct children
				'has_password' => false,	// since wp 3.9
			) );

			return $posts;
		}

		// filters the shortlink for a post
		public function get_shortlink( $shortlink, $post_id, $context, $allow_slugs ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'shortlink' => $shortlink, 
					'post_id' => $post_id, 
					'context' => $context, 
					'allow_slugs' => $allow_slugs, 
				) );
			}

			// check if a shortening service has been defined
			if ( empty( $this->p->options['plugin_shortener'] ) || 
				$this->p->options['plugin_shortener'] === 'none' ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: no shortening service defined' );
				}
				return $shortlink;	// return original shortlink
			}

			$mod = $this->get_mod( $post_id );

			if ( empty( $mod['post_type'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_type is empty' );
				}
				return $shortlink;	// return original shortlink
			} elseif ( empty( $mod['post_status'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is empty' );
				}
				return $shortlink;	// return original shortlink
			} elseif ( $mod['post_status'] === 'auto-draft' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is auto-draft' );
				}
				return $shortlink;	// return original shortlink
			}

			$long_url = $this->p->util->get_sharing_url( $mod, false );	// $add_page = false
			$short_url = apply_filters( $this->p->cf['lca'].'_get_short_url',
				$long_url, $this->p->options['plugin_shortener'] );

			if ( $long_url === $short_url ) {	// shortened failed
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: short URL ('.$short_url.') returned is identical to long URL.' );
				}
				return $shortlink;	// return original shortlink
			} elseif ( filter_var( $short_url, FILTER_VALIDATE_URL ) === false ) {	// invalid url
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: invalid short URL ('.$short_url.') returned by filter' );
				}
				return $shortlink;	// return original shortlink
			}

			return $short_url;	// success - return short url
		}

		public function add_post_column_headings( $columns ) { 
			return $this->add_mod_column_headings( $columns, 'post' );
		}

		public function add_media_column_headings( $columns ) { 
			return $this->add_mod_column_headings( $columns, 'media' );
		}

		public function show_column_content( $column_name, $post_id ) {
			echo $this->get_column_content( '', $column_name, $post_id );
		}

		public function get_column_content( $value, $column_name, $post_id ) {
			$lca = $this->p->cf['lca'];
			if ( ! empty( $post_id ) ) {	// just in case
				$col_idx = str_replace( $lca.'_', '', $column_name );
				if ( ( $col_info = self::get_sortable_columns( $col_idx ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// just in case
						$value = (string) get_post_meta( $post_id, $col_info['meta_key'], true );	// $single = true
						if ( $value === 'none' ) {
							$value = '';
						}
					}
				}
			}
			return $value;
		}

		public function update_sortable_meta( $post_id, $col_idx, $content ) { 
			if ( ! empty( $post_id ) ) {	// just in case
				if ( ( $col_info = self::get_sortable_columns( $col_idx ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// just in case
						update_post_meta( $post_id, $col_info['meta_key'], $content );
					}
				}
			}
		}

		public function check_sortable_metadata( $value, $post_id, $meta_key, $single ) {

			$lca = $this->p->cf['lca'];
			static $do_once = array();

			if ( strpos( $meta_key, '_'.$lca.'_head_info_' ) !== 0 ) {	// example: _wpsso_head_info_og_img_thumb
				return $value;	// return null
			}

			if ( isset( $do_once[$post_id][$meta_key] ) ) {
				return $value;	// return null
			} else {
				$do_once[$post_id][$meta_key] = true;	// prevent recursion
			}

			if ( get_post_meta( $post_id, $meta_key, true ) === '' ) {	// returns empty string if meta not found
				$mod = $this->get_mod( $post_id );
				$head_meta_tags = $this->p->head->get_head_array( $post_id, $mod, true );	// $read_cache = true
				$head_meta_info = $this->p->head->extract_head_info( $mod, $head_meta_tags );
			}

			return $value;	// return null
		}

		// hooked into the current_screen action
		// sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties
		public function load_meta_page( $screen = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			// all meta modules set this property, so use it to optimize code execution
			if ( WpssoMeta::$head_meta_tags !== false || ! isset( $screen->id ) ) {
				return;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'screen id: '.$screen->id );
			}

			switch ( $screen->id ) {
				case 'upload':
				case ( strpos( $screen->id, 'edit-' ) === 0 ? true : false ):	// posts list table
					return;
			}

			$post_obj = SucomUtil::get_post_object( true );
			$post_id = empty( $post_obj->ID ) ? 0 : $post_obj->ID;

			// make sure we have at least a post type and status
			if ( ! is_object( $post_obj ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_obj is not an object' );
				}
				return;
			} elseif ( empty( $post_obj->post_type ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_type is empty' );
				}
				return;
			} elseif ( empty( $post_obj->post_status ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is empty' );
				}
				return;
			}

			$lca = $this->p->cf['lca'];
			$mod = $this->get_mod( $post_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = '.get_option( 'home' ) );
				$this->p->debug->log( 'locale default = '.SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = '.SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = '.SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );
			}

			if ( $post_obj->post_status === 'auto-draft' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'head meta skipped: post_status is auto-draft' );
				}
				WpssoMeta::$head_meta_tags = array();
			} else {
				$add_metabox = empty( $this->p->options['plugin_add_to_'.$post_obj->post_type] ) ? false : true;

				if ( apply_filters( $lca.'_add_metabox_post', $add_metabox, $post_id, $post_obj->post_type ) ) {

					// hooked by woocommerce module to load front-end libraries and start a session
					do_action( $lca.'_admin_post_head', $mod, $screen->id );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting head_meta_info static property' );
					}

					// $read_cache is false to generate notices etc.
					WpssoMeta::$head_meta_tags = $this->p->head->get_head_array( $post_id, $mod, false );
					WpssoMeta::$head_meta_info = $this->p->head->extract_head_info( $mod, WpssoMeta::$head_meta_tags );

					if ( $post_obj->post_status === 'publish' ) {

						// check for missing open graph image and description values
						foreach ( array( 'image', 'description' ) as $mt_suffix ) {
							if ( empty( WpssoMeta::$head_meta_info['og:'.$mt_suffix] ) ) {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'og:'.$mt_suffix.' meta tag is value empty and required' );
								}
								if ( $this->p->notice->is_admin_pre_notices() ) {	// skip if notices already shown
									$this->p->notice->err( $this->p->msgs->get( 'notice-missing-og-'.$mt_suffix ) );
								}
							}
						}

						// check duplicates only when the post is available publicly and we have a valid permalink
						if ( current_user_can( 'manage_options' ) ) {
							if ( apply_filters( $lca.'_check_post_head', 
								$this->p->options['plugin_check_head'], $post_id, $post_obj ) ) {

								$this->check_post_head_duplicates( $post_id, $post_obj );
							}
						}
					}
				}
			} 

			$action_query = $lca.'-action';
			if ( ! empty( $_GET[$action_query] ) ) {
				$action_name = SucomUtil::sanitize_hookname( $_GET[$action_query] );
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'found action query: '.$action_name );
				}
				if ( empty( $_GET[ WPSSO_NONCE_NAME ] ) ) {	// WPSSO_NONCE_NAME is an md5() string
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'nonce token query field missing' );
					}
				} elseif ( ! wp_verify_nonce( $_GET[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {
					$this->p->notice->err( sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".',
						'wpsso' ), 'post', $action_name ) );
				} else {
					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );
					switch ( $action_name ) {
						default: 
							do_action( $lca.'_load_meta_page_post_'.$action_name, $post_id, $post_obj );
							break;
					}
				}
			}
		}

		public function check_post_head_duplicates( $post_id = true, $post_obj = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$lca = $this->p->cf['lca'];

			if ( empty( $this->p->options['plugin_check_head'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: plugin_check_head option is disabled');
				}
				return;
			}

			if ( ! apply_filters( $lca.'_add_meta_name_'.$lca.':mark', true ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: '.$lca.':mark meta tags are disabled');
				}
				return;
			}

			if ( ! is_object( $post_obj ) && ( $post_obj = SucomUtil::get_post_object( $post_id ) ) === false ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: unable to get the post object');
				}
				return;
			}

			// in case post_id is true/false
			if ( ! is_numeric( $post_id ) ) {
				$post_id = $post_obj->ID;
				if ( ! is_numeric( $post_id ) ) {	// just in case
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: non-numeric post ID in post object');
					}
					return;
				}
			}

			// only check publicly available posts
			if ( ! isset( $post_obj->post_status ) || $post_obj->post_status !== 'publish' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status \''.$post_obj->post_status.'\' not published');
				}
				return;
			}

			// only check public post types (to avoid menu items, product variations, etc.)
			$ptns = $this->p->util->get_post_types( 'names' );

			if ( empty( $post_obj->post_type ) || ! in_array( $post_obj->post_type, $ptns ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_type \''.$post_obj->post_type.'\' not public' );
				}
				return;
			}

			$exec_count = $this->p->debug->enabled ? 0 : (int) get_option( WPSSO_POST_CHECK_NAME );		// cast to change false to 0
			$max_count = (int) SucomUtil::get_const( 'WPSSO_CHECK_HEADER_COUNT', 10 );

			if ( $exec_count >= $max_count ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: exec_count of '.$exec_count.' exceeds max_count of '.$max_count );
				}
				return;
			}

			$charset = get_bloginfo( 'charset' );
			$shortlink = wp_get_shortlink( $post_id, 'post' );	// $context = post
			$shortlink_encoded = SucomUtil::encode_emoji( htmlentities( urldecode( $shortlink ), ENT_QUOTES, $charset, false ) );	// double_encode = false

			if ( filter_var( $shortlink, FILTER_VALIDATE_URL ) === false ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: wp_get_shortlink() returned an invalid URL ('.$shortlink.') for post ID '.$post_id );
				}
				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'The WordPress wp_get_shortlink() function returned an invalid URL (%1$s) for post ID %2$s.',
						'wpsso' ), '<a href="'.$shortlink.'">'.$shortlink_encoded.'</a>', $post_id ) );
				}
				return;
			}

			$check_opts = SucomUtil::preg_grep_keys( '/^add_/', $this->p->options, false, '' );
			$conflicts_found = 0;
			$conflicts_msg = __( 'Possible conflict detected &mdash; your theme or another plugin is adding %1$s to the head section of this webpage.',
				'wpsso' );

			if ( is_admin() ) {
				$this->p->notice->inf( sprintf( __( 'Checking %1$s for duplicate meta tags', 'wpsso' ), 
					'<a href="'.$shortlink.'">'.$shortlink_encoded.'</a>' ).'...' );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'checking '.$shortlink.' head meta for duplicates' );
			}

			if ( SucomUtil::get_const( 'WPSSO_DUPE_CHECK_CLEAR_SHORTLINK' ) ) {
				$this->p->cache->clear( $shortlink );	// clear cache before fetching shortlink url
			}

			$html = $this->p->cache->get( $shortlink, 'raw', 'transient' );
			$in_secs = $this->p->cache->in_secs( $shortlink );
			$warning_secs = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_WARNING_SECS', 2.5 );
			$timeout_secs = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_TIMEOUT_SECS', 3.0 );

			if ( $in_secs === true ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched '.$shortlink.' from transient cache' );
				}
			} elseif ( $in_secs === false ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched '.$shortlink.' returned a failure' );
				}
			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched '.$shortlink.' in '.$in_secs.' secs' );
				}
				if ( $in_secs > $warning_secs ) {
					$this->p->notice->warn(
						sprintf( __( 'Retrieving the HTML document for %1$s took %2$s seconds.',
							'wpsso' ), '<a href="'.$shortlink.'">'.$shortlink_encoded.'</a>', $in_secs ).' '.
						sprintf( __( 'This exceeds the recommended limit of %1$s seconds (crawlers often time-out after %2$s seconds).',
							'wpsso' ), $warning_secs, $timeout_secs ).' '.
						__( 'Please consider improving the speed of your site.',
							'wpsso' ).' '.
						__( 'As an added benefit, a faster site will also improve ranking in search results.',
							'wpsso' ).' ;-)'
					);
				}
			}

			if ( empty( $html ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'error retrieving webpage from '.$shortlink );
				}
				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'Error retrieving webpage from <a href="%1$s">%1$s</a>.',
						'wpsso' ), $shortlink ) );
				}
				return;	// stop here
			} elseif ( stripos( $html, '<html' ) === false ) {	// webpage must have an <html> tag
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( '<html> tag not found in '.$shortlink );
				}
				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'An &lt;html&gt; tag was not found in <a href="%1$s">%1$s</a>.',
						'wpsso' ), $shortlink ) );
				}
				return;	// stop here
			} elseif ( stripos( $html, '<meta ' ) === false ) {	// webpage must have one or more <meta/> tags
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( '<meta /> tag not found in '.$shortlink );
				}
				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'A &lt;meta /&gt; tag was not found in <a href="%1$s">%1$s</a>.',
						'wpsso' ), $shortlink ) );
				}
				return;	// stop here
			} elseif ( strpos( $html, $lca.' meta tags begin' ) === false ) {	// webpage should include our own meta tags
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $lca.' meta tag section not found in '.$shortlink );
				}
				if ( is_admin() ) {
					$short = $this->p->cf['plugin'][$lca]['short'];
					$this->p->notice->err( sprintf( __( 'A %2$s meta tag section was not found in <a href="%1$s">%1$s</a> &mdash; perhaps a webpage caching plugin or service needs to be refreshed?', 'wpsso' ), $shortlink, $short ) );
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'removing '.$lca.' meta tag section' );
			}

			$html = preg_replace( $this->p->head->get_mt_mark( 'preg' ), '', $html, -1, $mark_count );

			if ( ! $mark_count ) {
				if ( is_admin() ) {
					$short = $this->p->cf['plugin'][$lca]['short'];
					$this->p->notice->err( sprintf( __( 'The PHP preg_replace() function failed to remove the %1$s meta tag section &mdash; this could be an indication of a problem with PHP\'s PCRE library or a webpage filter corrupting the %1$s meta tags.', 'wpsso' ), $short ) );
					return false;
				}
			}

			$metas = $this->p->util->get_head_meta( $html, '/html/head/link|/html/head/meta', true );	// returns false on error

			if ( is_array( $metas ) ) {
				if ( empty( $metas ) ) {	// no link or meta tags found
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'error parsing head meta for '.$shortlink );
					}
					if ( is_admin() ) {
						$pin_tab_url = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_pinterest' );
						$this->p->notice->err( sprintf( __( 'An error occured parsing the head meta tags from <a href="%1$s">%1$s</a>.', 'wpsso' ), $shortlink ).' '.sprintf( __( 'The webpage may contain serious HTML syntax errors &mdash; please review the <a href="%1$s">W3C Markup Validation Service</a> results and correct any errors.', 'wpsso' ), $shortlink ).' '.sprintf( __( 'You may safely ignore any "nopin" attribute errors (suggested), or disable the "nopin" attribute under the <a href="%s">Pinterest settings tab</a>.', 'wpsso' ), $pin_tab_url ) );
					}
				} else {
					foreach( array(
						'link' => array( 'rel' ),
						'meta' => array( 'name', 'property', 'itemprop' ),
					) as $tag => $types ) {
						if ( isset( $metas[$tag] ) ) {
							foreach( $metas[$tag] as $meta ) {
								foreach( $types as $type ) {
									if ( isset( $meta[$type] ) && $meta[$type] !== 'generator' && 
										! empty( $check_opts[$tag.'_'.$type.'_'.$meta[$type]] ) ) {
										$conflicts_found++;
										$this->p->notice->err( sprintf( $conflicts_msg,
											'<code>'.$tag.' '.$type.'="'.$meta[$type].'"</code>' ) );
									}
								}
							}
						}
					}
					if ( ! $conflicts_found ) {
						update_option( WPSSO_POST_CHECK_NAME, ++$exec_count, false );	// autoload = false
						if ( $this->p->debug->enabled ) {
							$this->p->notice->inf( __( 'Awesome! No duplicate meta tags found. :-)', 'wpsso' ) );
						} else {
							$this->p->notice->inf( __( 'Awesome! No duplicate meta tags found. :-)', 'wpsso' ).' '.
								sprintf( __( '%s more checks to go...', 'wpsso' ), $max_count - $exec_count ) );
						}
					}
				}
			}
		}

		public function add_meta_boxes() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ( $post_obj = SucomUtil::get_post_object( true ) ) === false || empty( $post_obj->post_type ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: object without post type' );
				}
				return;
			} else {
				$post_id = empty( $post_obj->ID ) ? 0 : $post_obj->ID;
			}

			if ( ( $post_obj->post_type === 'page' && ! current_user_can( 'edit_page', $post_id ) ) || 
				! current_user_can( 'edit_post', $post_id ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'insufficient privileges to add metabox for '.$post_obj->post_type.' ID '.$post_id );
				}
				return;
			}

			$lca = $this->p->cf['lca'];
			$metabox_id = $this->p->cf['meta']['id'];
			$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );
			$add_metabox = empty( $this->p->options[ 'plugin_add_to_'.$post_obj->post_type ] ) ? false : true;

			if ( apply_filters( $lca.'_add_metabox_post', $add_metabox, $post_id, $post_obj->post_type ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding metabox '.$metabox_id );
				}
				add_meta_box( $lca.'_'.$metabox_id, $metabox_title,
					array( &$this, 'show_metabox_custom_meta' ),
						$post_obj->post_type, 'normal', 'low' );

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'skipped metabox '.$metabox_id.' for post type '.$post_obj->post_type );
			}
		}

		public function show_metabox_custom_meta( $post_obj ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log( 'post id = '.( empty( $post_obj->ID ) ? 0 : $post_obj->ID ) );
				$this->p->debug->log( 'post type = '.( empty( $post_obj->post_type ) ? 'empty' : $post_obj->post_type ) );
				$this->p->debug->log( 'post status = '.( empty( $post_obj->post_status ) ? 'empty' : $post_obj->post_status ) );
			}

			$lca = $this->p->cf['lca'];
			$metabox_id = $this->p->cf['meta']['id'];
			$mod = $this->get_mod( $post_obj->ID );
			$tabs = $this->get_custom_meta_tabs( $metabox_id, $mod );
			$opts = $this->get_options( $post_obj->ID );
			$def_opts = $this->get_defaults( $post_obj->ID );
			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $lca );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( $metabox_id.' table rows' );	// start timer

			$table_rows = array();
			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox_id, $key, WpssoMeta::$head_meta_info, $mod ), 
					apply_filters( $lca.'_'.$mod['name'].'_'.$key.'_rows', array(), $this->form, WpssoMeta::$head_meta_info, $mod ) );
			}
			$this->p->util->do_metabox_tabs( $metabox_id, $tabs, $table_rows );

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( $metabox_id.' table rows' );	// end timer
		}

		protected function get_table_rows( &$metabox_id, &$key, &$head, &$mod ) {

			$is_auto_draft = empty( $mod['post_status'] ) || 
				$mod['post_status'] === 'auto-draft' ? true : false;
			$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to update this value.',
				'wpsso' ), SucomUtil::titleize( $mod['post_type'] ) );

			$table_rows = array();
			switch ( $key ) {
				case 'preview':
					$table_rows = $this->get_rows_social_preview( $this->form, $head, $mod );
					break;

				case 'tags':	
					if ( $is_auto_draft ) {
						$table_rows[] = '<td><blockquote class="status-info"><p class="centered">'.
							$auto_draft_msg.'</p></blockquote></td>';
					} else {
						$table_rows = $this->get_rows_head_tags( $this->form, $head, $mod );
					}
					break; 

				case 'validate':
					if ( $is_auto_draft ) {
						$table_rows[] = '<td><blockquote class="status-info"><p class="centered">'.
							$auto_draft_msg.'</p></blockquote></td>';
					} else {
						$table_rows = $this->get_rows_validate( $this->form, $head, $mod );
					}
					break; 
			}
			return $table_rows;
		}

		public function clear_cache_for_new_comment( $comment_id, $comment_approved ) {
			if ( $comment_id && $comment_approved === 1 ) {
				if ( ( $comment = get_comment( $comment_id ) ) && $comment->comment_post_ID ) {
					$post_id = $comment->comment_post_ID;
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'clearing post_id '.$post_id.' cache for comment_id '.$comment_id );
					}
					$this->clear_cache( $post_id );
				}
			}
		}

		public function clear_cache_for_comment_status( $comment_id, $comment_status ) {
			if ( $comment_id ) {	// just in case
				if ( ( $comment = get_comment( $comment_id ) ) && $comment->comment_post_ID ) {
					$post_id = $comment->comment_post_ID;
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'clearing post_id '.$post_id.' cache for comment_id '.$comment_id );
					}
					$this->clear_cache( $post_id );
				}
			}
		}

		public function clear_cache( $post_id, $rel_id = false ) {
			switch ( get_post_status( $post_id ) ) {
				case 'draft':
				case 'pending':
				case 'future':
				case 'private':
				case 'publish':
					$lca = $this->p->cf['lca'];
					$mod = $this->get_mod( $post_id );
					$sharing_url = $this->p->util->get_sharing_url( $mod );
					$cache_salt = SucomUtil::get_mod_salt( $mod, $sharing_url );
					$permalink = get_permalink( $post_id );
					$shortlink = wp_get_shortlink( $post_id, 'post' );	// $context = post

					$transients = array(
						'WpssoHead::get_head_array' => array( $cache_salt ),
						'SucomCache::get' => array( 'url:'.$permalink, 'url:'.$shortlink ),
					);
					$transients = apply_filters( $lca.'_post_cache_transients', $transients, $mod, $sharing_url );

					$wp_objects = array( 'WpssoPage::get_content' => array( $cache_salt ) );
					$wp_objects = apply_filters( $lca.'_post_cache_objects', $wp_objects, $mod, $sharing_url );

					$deleted = $this->p->util->clear_cache_objects( $transients, $wp_objects );

					if ( ! empty( $this->p->options['plugin_show_purge_count'] ) && $deleted > 0 ) {
						$this->p->notice->inf( $deleted.' items removed from the WordPress object and transient caches.', 
							true, __FUNCTION__.'_show_purge_count', true );	// can be dismissed
					}

					if ( function_exists( 'w3tc_pgcache_flush_post' ) ) {	// w3 total cache
						w3tc_pgcache_flush_post( $post_id );
					}

					if ( function_exists( 'wp_cache_post_change' ) ) {	// wp super cache
						wp_cache_post_change( $post_id );
					}

					break;
			}

			return $post_id;
		}

		public function get_og_type_reviews( $post_id, $og_type = 'product', $rating_meta = 'rating' ) {

			$ret = array();

			if ( empty( $post_id ) ) {
				return $ret;
			}

			$comments = get_comments( array(
				'post_id' => $post_id,
				'status' => 'approve',
				'parent' => 0,	// don't get replies
				'order' => 'DESC',
				'number' => get_option( 'page_comments' ),	// limit number of comments
			) );

			if ( is_array( $comments ) ) {
				foreach( $comments as $num => $comment_obj ) {
					$og_review = $this->get_og_review_mt( $comment_obj, $og_type, $rating_meta );
					if ( ! empty( $og_review ) ) {	// just in case
						$ret[] = $og_review;
					}
				}
			}

			return $ret;
		}

		public function get_og_review_mt( $comment_obj, $og_type = 'product', $rating_meta = 'rating' ) {

			$ret = array();
			$rating_value = (float) get_comment_meta( $comment_obj->comment_ID, $rating_meta, true );

			$ret[$og_type.':review:id'] = $comment_obj->comment_ID;
			$ret[$og_type.':review:url'] = get_comment_link( $comment_obj->comment_ID );
			$ret[$og_type.':review:author:id'] = $comment_obj->user_id;	// author ID if registered (0 otherwise)
			$ret[$og_type.':review:author:name'] = $comment_obj->comment_author;	// author display name
			$ret[$og_type.':review:created_time'] = mysql2date( 'c', $comment_obj->comment_date_gmt );
			$ret[$og_type.':review:excerpt'] = get_comment_excerpt( $comment_obj->comment_ID );

			// rating values must be larger than 0 to include rating info
			if ( $rating_value > 0 ) {
				$ret[$og_type.':review:rating:value'] = $rating_value;
				$ret[$og_type.':review:rating:worst'] = 1;
				$ret[$og_type.':review:rating:best'] = 5;
			}

			return $ret;
		}
	}
}

?>
