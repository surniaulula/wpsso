<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoUtil' ) && class_exists( 'SucomUtil' ) ) {

	class WpssoUtil extends SucomUtil {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			if ( ! empty( $this->p->options['plugin_'.$this->p->cf['lca'].'_tid'] ) )
				$this->add_plugin_filters( $this, array( 
					'installed_version' => 2, 
					'ua_plugin' => 2,
				), 10, 'sucom' );
			$this->add_actions();
		}

		protected function add_actions() {
			add_action( 'wp', array( &$this, 'add_plugin_image_sizes' ), -100 );	// runs everytime a posts query is triggered from an url
			add_action( 'admin_init', array( &$this, 'add_plugin_image_sizes' ), -100 );
			add_action( 'wp_scheduled_delete', array( &$this, 'delete_expired_db_transients' ) );
			add_action( 'wp_scheduled_delete', array( &$this, 'delete_expired_file_cache' ) );
		}

		// called from several class __construct() methods to hook their filters
		public function add_plugin_filters( &$class, $filters, $prio = 10, $lca = '' ) {
			$this->add_plugin_hooks( 'filter', $class, $filters, $prio, $lca );
		}

		public function add_plugin_actions( &$class, $actions, $prio = 10, $lca = '' ) {
			$this->add_plugin_hooks( 'action', $class, $actions, $prio, $lca );
		}

		protected function add_plugin_hooks( $type, &$class, &$hooks, &$prio, &$lca ) {
			$lca = $lca === '' ?
				$this->p->cf['lca'] : $lca;
			foreach ( $hooks as $name => $num ) {
				$hook = $lca.'_'.$name;
				$method = $type.'_'.str_replace( array( '/', '-' ), '_', $name );
				call_user_func( 'add_'.$type, $hook, array( &$class, $method ), $prio, $num );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $type.' for '.$hook.' added', 3 );
			}
		}

		public function filter_installed_version( $version, $lca ) {
			if ( ! empty( $this->p->cf['plugin'][$lca]['update_auth'] ) &&
				! $this->p->check->aop( $lca, false ) )
					return '0.'.$version;
			else return $version;
		}

		public function filter_ua_plugin( $plugin, $lca ) {
			if ( ! isset( $this->p->cf['plugin'][$lca] ) )
				return $plugin;
			elseif ( $this->p->check->aop( $lca ) )
				return $plugin.'L';
			elseif ( $this->p->check->aop( $lca, false ) )
				return $plugin.'U';
			else return $plugin.'G';
		}

		public function get_image_size_label( $size_name ) {	// wpsso-opengraph
			if ( ! empty( $this->size_labels[$size_name] ) )
				return $this->size_labels[$size_name];
			else return $size_name;
		}

		// called directly (with or without an id) and from the 'wp' action ($id will be an object)
		public function add_plugin_image_sizes( $id = false, $sizes = array(), $filter = true, $mod = false ) {
			/*
			 * Allow various plugin extensions to provide their image names, labels, etc.
			 * The first dimension array key is the option name prefix by default.
			 * You can also include the width, height, crop, crop_x, and crop_y values.
			 *
			 *	Array (
			 *		[rp_img] => Array (
			 *			[name] => richpin
			 *			[label] => Rich Pin Image Dimensions
			 *		) 
			 *		[og_img] => Array (
			 *			[name] => opengraph
			 *			[label] => Open Graph Image Dimensions
			 *		)
			 *	)
			 */
			if ( $filter === true )
				$sizes = apply_filters( $this->p->cf['lca'].'_plugin_image_sizes', $sizes, $id, $mod );
			$meta_opts = array();

			if ( $mod === false ) {
				if ( SucomUtil::is_post_page( false ) )
					$mod = 'post';
				elseif ( SucomUtil::is_term_page() )
					$mod = 'taxonomy';
				elseif ( SucomUtil::is_author_page() )
					$mod = 'user';
				elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'module type could not be determined' );
			}

			if ( is_object( $id ) ) {
				$obj = $id;	// could be WP_Object or post/term/user object
				$id = false;
				if ( $mod === 'post' )
					$id = empty( $obj->ID ) || empty( $obj->post_type ) ? 
						$this->get_post_object( false, 'id' ) : $obj->ID;
				elseif ( $mod === 'taxonomy' )
					$id = empty( $obj->term_id ) ?
						$this->get_term_object( 'id' ) : $obj->term_id;
				elseif ( $mod === 'user' )
					$id = empty( $obj->ID ) ?
						$this->get_author_object( 'id' ): $obj->ID;
			} elseif ( empty( $id ) ) {
				if ( $mod === 'post' )
					$id = $this->get_post_object( false, 'id' );
				elseif ( $mod === 'taxonomy' )
					$id = $this->get_term_object( 'id' );
				elseif ( $mod === 'user' )
					$id = $this->get_author_object( 'id' );
			}

			if ( empty( $mod ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'no module defined' );
			} elseif ( empty( $id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'no object id defined' );
			} else $meta_opts = $this->get_mod_options( $mod, $id );			// get all metadata options

			foreach( $sizes as $opt_prefix => $size_info ) {

				if ( ! is_array( $size_info ) ) {
					$save_name = empty( $size_info ) ? 
						$opt_prefix : $size_info;
					$size_info = array( 
						'name' => $save_name,
						'label' => $save_name
					);
				} elseif ( ! empty( $size_info['prefix'] ) )				// allow for alternate option prefix
					$opt_prefix = $size_info['prefix'];

				foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $key ) {
					if ( isset( $size_info[$key] ) )				// prefer existing info from filters
						continue;
					elseif ( isset( $meta_opts[$opt_prefix.'_'.$key] ) )		// use post meta if available
						$size_info[$key] = $meta_opts[$opt_prefix.'_'.$key];
					elseif ( isset( $this->p->options[$opt_prefix.'_'.$key] ) )	// current plugin settings
						$size_info[$key] = $this->p->options[$opt_prefix.'_'.$key];
					else {
						if ( ! isset( $def_opts ) )				// only read once if necessary
							$def_opts = $this->p->opt->get_defaults();
						$size_info[$key] = $def_opts[$opt_prefix.'_'.$key];	// fallback to default value
					}
					if ( $key === 'crop' )						// make sure crop is true or false
						$size_info[$key] = empty( $size_info[$key] ) ? false : true;
				}

				if ( $size_info['width'] > 0 && $size_info['height'] > 0 ) {

					// preserve compatibility with older wordpress versions, use true or false when possible
					if ( $size_info['crop'] === true && 
						( $size_info['crop_x'] !== 'center' || $size_info['crop_y'] !== 'center' ) ) {

						global $wp_version;
						if ( ! version_compare( $wp_version, 3.9, '<' ) )
							$size_info['crop'] = array( $size_info['crop_x'], $size_info['crop_y'] );
					}

					// allow custom function hooks to make changes
					if ( $filter === true )
						$size_info = apply_filters( $this->p->cf['lca'].'_size_info_'.$size_info['name'], 
							$size_info, $id, $mod );

					// a lookup array for image size labels, used in image size error messages
					$this->size_labels[$this->p->cf['lca'].'-'.$size_info['name']] = $size_info['label'];

					add_image_size( $this->p->cf['lca'].'-'.$size_info['name'], 
						$size_info['width'], $size_info['height'], $size_info['crop'] );

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'image size '.$this->p->cf['lca'].'-'.$size_info['name'].' '.
							$size_info['width'].'x'.$size_info['height'].
							( empty( $size_info['crop'] ) ? '' : ' crop '.
								$size_info['crop_x'].'/'.$size_info['crop_y'] ).' added' );
				}
			}
		}

		public function push_add_to_options( &$opts = array(), 
			$add_to_prefixes = array( 'plugin' => 'backend' ) ) {

			foreach ( $add_to_prefixes as $opt_prefix => $type ) {
				foreach ( $this->get_post_types( $type ) as $post_type ) {

					$option_name = $opt_prefix.'_add_to_'.$post_type->name;
					$filter_name = $this->p->cf['lca'].'_add_to_options_'.$post_type->name;
					if ( ! isset( $opts[$option_name] ) )
						$opts[$option_name] = apply_filters( $filter_name, 1 );
				}
			}
			return $opts;
		}

		public function get_post_types( $type = 'frontend', $output = 'objects' ) {
			switch ( $type ) {
				case 'frontend':
					$post_types = get_post_types( array( 'public' => true ), $output );
					break;
				case 'backend':
					$post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), $output );
					break;
				default:
					$post_types = array();
					break;
			}
			return apply_filters( $this->p->cf['lca'].'_post_types', $post_types, $type, $output );
		}

		public function clear_all_cache() {

			wp_cache_flush();					// clear non-database transients as well

			$del_files = $this->p->util->delete_expired_file_cache( true );
			$del_transients = $this->p->util->delete_expired_db_transients( true );

			$this->p->notice->inf( $this->p->cf['uca'].' cached files, transient cache,'.
				' and the WordPress object cache have been cleared.', true );

			if ( function_exists( 'w3tc_pgcache_flush' ) ) {	// w3 total cache
				w3tc_pgcache_flush();
				$this->p->notice->inf( __( 'W3 Total Cache has been cleared as well.', 'wpsso' ), true );
			}
			if ( function_exists( 'wp_cache_clear_cache' ) ) {	// wp super cache
				wp_cache_clear_cache();
				$this->p->notice->inf( __( 'WP Super Cache has been cleared as well.', 'wpsso' ), true );
			}
			if ( isset( $GLOBALS['zencache'] ) ) {			// zencache
				$GLOBALS['zencache']->wipe_cache();
				$this->p->notice->inf( __( 'ZenCache has been cleared as well.', 'wpsso' ), true );
			}
		}

		public function clear_post_cache( $post_id ) {
			switch ( get_post_status( $post_id ) ) {
				case 'draft':
				case 'pending':
				case 'future':
				case 'private':
				case 'publish':
					$lca = $this->p->cf['lca'];
					$lang = SucomUtil::get_locale();
					$permalink = get_permalink( $post_id );
					$permalink_no_meta = add_query_arg( array( 'WPSSO_META_TAGS_DISABLE' => 1 ), $permalink );
					$sharing_url = $this->p->util->get_sharing_url( $post_id );
					$transients = array(
						'SucomCache::get' => array(
							'url:'.$permalink,
							'url:'.$permalink_no_meta,
						),
						'WpssoHead::get_header_array' => array( 
							'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url,
							'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url.'_crawler:pinterest',
						),
						'WpssoMeta::get_mod_column_content' => array( 
							'lang:'.$lang.'_id:'.$post_id.'_mod:post_column:'.$lca.'_og_image',
						),
					);
					$transients = apply_filters( $lca.'_post_cache_transients', 
						$transients, $post_id, $lang, $sharing_url );
	
					$objects = array(
						'SucomWebpage::get_content' => array(
							'lang:'.$lang.'_post:'.$post_id.'_filtered',
							'lang:'.$lang.'_post:'.$post_id.'_unfiltered',
						),
						'SucomWebpage::get_hashtags' => array(
							'lang:'.$lang.'_post:'.$post_id,
						),
					);
					$objects = apply_filters( $lca.'_post_cache_objects', 
						$objects, $post_id, $lang, $sharing_url );
	
					$deleted = $this->clear_cache_objects( $transients, $objects );

					if ( ! empty( $this->p->options['plugin_cache_info'] ) && $deleted > 0 )
						$this->p->notice->inf( $deleted.' items removed from the WordPress object and transient caches.', true );

					if ( function_exists( 'w3tc_pgcache_flush_post' ) )	// w3 total cache
						w3tc_pgcache_flush_post( $post_id );

					if ( function_exists( 'wp_cache_post_change' ) )	// wp super cache
						wp_cache_post_change( $post_id );

					break;
			}
		}

		public function clear_cache_objects( &$transients = array(), &$objects = array() ) {
			$deleted = 0;
			foreach ( $transients as $group => $arr ) {
				foreach ( $arr as $val ) {
					if ( ! empty( $val ) ) {
						$cache_salt = $group.'('.$val.')';
						$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
						if ( delete_transient( $cache_id ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'cleared transient cache salt: '.$cache_salt );
							$deleted++;
						}
					}
				}
			}
			foreach ( $objects as $group => $arr ) {
				foreach ( $arr as $val ) {
					if ( ! empty( $val ) ) {
						$cache_salt = $group.'('.$val.')';
						$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
						if ( wp_cache_delete( $cache_id, $group ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'cleared object cache salt: '.$cache_salt );
							$deleted++;
						}
					}
				}
			}
			return $deleted;
		}

		public function get_topics() {
			if ( $this->p->is_avail['cache']['transient'] ) {
				$cache_salt = __METHOD__.'('.WPSSO_TOPICS_LIST.')';
				$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
				$cache_type = 'object cache';
				$this->p->debug->log( $cache_type.': transient salt '.$cache_salt );
				$topics = get_transient( $cache_id );
				if ( is_array( $topics ) ) {
					$this->p->debug->log( $cache_type.': topics array retrieved from transient '.$cache_id );
					return $topics;
				}
			}
			if ( ( $topics = file( WPSSO_TOPICS_LIST, 
				FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) ) === false ) {
				$this->p->notice->err( sprintf( 'Error reading the %s topic list file.', WPSSO_TOPICS_LIST ) );
				return $topics;
			}
			$topics = apply_filters( $this->p->cf['lca'].'_topics', $topics );
			natsort( $topics );
			$topics = array_merge( array( 'none' ), $topics );	// after sorting the array, put 'none' first

			if ( ! empty( $cache_id ) ) {
				set_transient( $cache_id, $topics, $this->p->options['plugin_object_cache_exp'] );
				$this->p->debug->log( $cache_type.': topics array saved to transient '.
					$cache_id.' ('.$this->p->options['plugin_object_cache_exp'].' seconds)');
			}
			return $topics;
		}

		/**
		 * Purpose: returns a specific option from the custom social settings meta
	 	 *
		 * If idx is an array, then get the first non-empty option from the idx array --
		 * this is an easy way to provide a fall-back value for the first array key.
		 *
		 * Example: get_mod_options( 'post', $post_id, array( 'rp_desc', 'og_desc' ) );
		 */
		public function get_mod_options( $mod, $id = false, $idx = false, $attr = array() ) {
			if ( empty( $id ) || 
				! isset( $this->p->mods['util'][$mod] ) )
					return false;
			// return the whole options array
			if ( $idx === false ) {
				$ret = $this->p->mods['util'][$mod]->get_options( $id, $idx, $attr );
			} else {
				if ( ! is_array( $idx ) )
					$idx = array( $idx );
				foreach ( array_unique( $idx ) as $key ) {
					if ( $key === 'none' )		// special keyword
						return false;		// stop here
					if ( empty( $key ) )
						continue;
					$ret = $this->p->mods['util'][$mod]->get_options( $id, $key, $attr );
					if ( ! empty( $ret ) )
						break;
				}
			}
			if ( ! empty( $ret ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'custom '.$mod.' '.
						( $idx === false ? 'options' : ( is_array( $idx ) ? 
							implode( ', ', $idx ) : $idx ) ).' = '.
						( is_array( $ret ) ? print_r( $ret, true ) : '"'.$ret.'"' ) );
				return $ret;	// stop here
			}
			return false;
		}

		public function sanitize_option_value( $key, $val, $def_val, $network = false, $mod = false ) {
			// hooked by the sharing class
			$option_type = apply_filters( $this->p->cf['lca'].'_option_type', false, $key, $network, $mod );

			// pre-filter most values to remove html
			switch ( $option_type ) {
				case 'html':	// leave html and css / javascript code blocks as-is
				case 'code':
					$val = stripslashes( $val );
					break;
				default:
					$val = stripslashes( $val );
					$val = wp_filter_nohtml_kses( $val );
					$val = htmlentities( $val, ENT_QUOTES, get_bloginfo( 'charset' ), false );	// double_encode = false
					break;
			}

			switch ( $option_type ) {
				// must be empty or texturized 
				case 'textured':
					if ( $val !== '' )
						$val = trim( wptexturize( ' '.$val.' ' ) );
					break;
				// must be empty or a url
				case 'url':
					if ( $val !== '' ) {
						$val = $this->cleanup_html_tags( $val );
						if ( strpos( $val, '//' ) === false ) {
							$this->p->notice->err( sprintf( 'The value of option \'%s\' must be a URL - resetting the option to its default value.', $key ), true );
							$val = $def_val;
						}
					}
					break;
				// strip leading urls off facebook usernames
				case 'url_base':
					if ( $val !== '' ) {
						$val = $this->cleanup_html_tags( $val );
						$val = preg_replace( '/(http|https):\/\/[^\/]*?\//', '', $val );
					}
					break;
				// twitter-style usernames (prepend with an @ character)
				case 'at_name':
					if ( $val !== '' ) {
						$val = substr( preg_replace( '/[^a-zA-Z0-9_]/', '', $val ), 0, 15 );
						if ( ! empty( $val ) ) 
							$val = '@'.$val;
					}
					break;
				case 'pos_num':		// integer options that must be 1 or more (not zero)
				case 'img_dim':		// image dimensions, subject to minimum value (typically, at least 200px)
					if ( $option_type == 'img_dim' )
						$min_int = empty( $this->p->cf['head']['min_img_dim'] ) ? 
							200 : $this->p->cf['head']['min_img_dim'];
					else $min_int = 1;

					// custom meta options are allowed to be empty
					if ( $val === '' && $mod !== false )
						break;
					elseif ( ! is_numeric( $val ) || $val < $min_int ) {
						$this->p->notice->err( sprintf( 'The value of option \'%s\' must be greater or equal to %s - resetting the option to its default value.', $key, $min_int ), true );
						$val = $def_val;
					}
					break;
				// must be numeric
				case 'blank_num':
					$passed = ( $val !== '' && 
						! is_numeric( $val ) ) ? false : true;
					// no break;
				case 'numeric':
					$passed = ( ! isset( $passed ) && 
						! is_numeric( $val ) ) ? false : true;

					if ( $passed === false ) {
						$this->p->notice->err( sprintf( 'The value of option \'%s\' must be numeric - resetting the option to its default value.', $key ), true );
						$val = $def_val;
					}
					break;
				// must be alpha-numeric uppercase (hyphens are allowed as well)
				case 'auth_id':
					$val = trim( $val );
					if ( $val !== '' && preg_match( '/[^A-Z0-9\-]/', $val ) ) {
						$this->p->notice->err( sprintf( '\'%s\' is not an acceptable value for option \'%s\' - resetting the option to its default value.', $val, $key ), true );
						$val = $def_val;
					}
					break;
				// blank or alpha-numeric (upper or lower case), plus underscores
				case 'api_key':
					$val = trim( $val );
					if ( $val !== '' && preg_match( '/[^a-zA-Z0-9_]/', $val ) ) {
						$this->p->notice->err( sprintf( 'The value of option \'%s\' must be alpha-numeric - resetting the option to its default value.', $key ), true );
						$val = $def_val;
					}
					break;
				// text strings that can be blank
				case 'ok_blank':
					if ( $val !== '' )
						$val = trim( $val );
					break;
				case 'html':
					if ( $val !== '' ) {
						$val = trim( $val );
						if ( ! preg_match( '/<.*>/', $val ) ) {
							$this->p->notice->err( sprintf( 'The value of option \'%s\' must be HTML code - resetting the option to its default value.', $key ), true );
							$val = $def_val;
						}
					}
					break;
				// options that cannot be blank
				case 'not_blank':
				case 'code':
					if ( $val === '' ) {
						$this->p->notice->err( sprintf( 'The value of option \'%s\' cannot be empty - resetting the option to its default value.', $key ), true );
						$val = $def_val;
					}
					break;
				// everything else is a 1 or 0 checkbox option 
				case 'checkbox':
				default:
					if ( $def_val === 0 || $def_val === 1 )	// make sure the default option is also a 1 or 0, just in case
						$val = empty( $val ) ? 0 : 1;
					break;
			}
			return $val;
		}

		// query examples:
		//	/html/head/link|/html/head/meta
		//	/html/head/meta[starts-with(@property, 'og:video:')]
		public function get_head_meta( $url, $query = '/html/head/meta', $remove_self = false ) {
			if ( empty( $query ) )
				return false;
			if ( ( $html = $this->p->cache->get( $url, 'raw', 'transient' ) ) === false )
				return false;
			$cmt = $this->p->cf['lca'].' meta tags ';
			if ( $remove_self === true && strpos( $html, $cmt.'begin' ) !== false ) {
				$pre = '<(!--[\s\n\r]+|meta[\s\n\r]+name="'.$this->p->cf['lca'].':comment"[\s\n\r]+content=")';
				$post = '([\s\n\r]+--|"[\s\n\r]*\/?)>';	// make space and slash optional for html optimizers
				$html = preg_replace( '/'.$pre.$cmt.'begin'.$post.'.*'.$pre.$cmt.'end'.$post.'/ms',
					'<!-- '.$this->p->cf['lca'].' meta tags removed -->', $html );
			}
			$ret = array();
			if ( class_exists( 'DOMDocument' ) ) {
				$doc = new DOMDocument();		// since PHP v4.1.0
				@$doc->loadHTML( $html );		// suppress parsing errors
				$xpath = new DOMXPath( $doc );
				$metas = $xpath->query( $query );
				foreach ( $metas as $m ) {
					$attrs = array();		// put all attributes in a single array
					foreach ( $m->attributes as $a )
						$attrs[$a->name] = $a->value;
					$ret[$m->tagName][] = $attrs;
				}
			} else {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'DOMDocument PHP class missing' );
				if ( is_admin() )
					$this->p->notice->err( sprintf( 'DOMDocument PHP class missing - unable to read head meta from %s. Please contact your hosting provider to install the missing DOMDocument PHP class.', $url ), true );
			}
			return $ret;
		}

		public function log_is_functions() {
			$is_functions = array( 
				'is_ajax',
				'is_archive',
				'is_attachment',
				'is_author',
				'is_category',
				'is_front_page',
				'is_home',
				'is_multisite',
				'is_page',
				'is_search',
				'is_single',
				'is_singular',
				'is_ssl',
				'is_tag',
				'is_tax',
				/*
				 * e-commerce / woocommerce functions
				 */
				'is_account_page',
				'is_cart',
				'is_checkout',
				'is_checkout_pay_page',
				'is_product',
				'is_product_category',
				'is_product_tag',
				'is_shop',
			);
			$is_functions = apply_filters( $this->p->cf['lca'].'_is_functions', $is_functions );
			foreach ( $is_functions as $function ) 
				if ( function_exists( $function ) && $function() )
					$this->p->debug->log( $function.'() = true' );
		}

		public function force_default_author( $use_post = false, $opt_pre = 'og' ) {
			$ret = null;

			// save some time
			if ( empty( $this->p->options[$opt_pre.'_def_author_id'] ) )
				$ret = false;
			else {
				// check for singular pages first
				if ( $ret === null && SucomUtil::is_post_page( $use_post ) )
					$ret = false;
	
				if ( $ret === null && ! empty( $this->p->options[$opt_pre.'_def_author_on_index'] ) )
					if ( is_home() || ( is_archive() && ! is_admin() && ! SucomUtil::is_author_page() ) )
						$ret = true;
	
				if ( $ret === null && ! empty( $this->p->options[$opt_pre.'_def_author_on_search'] ) )
					if ( is_search() )
						$ret = true;
	
				if ( $ret === null )
					$ret = false;
			}
			$ret = apply_filters( $this->p->cf['lca'].'_force_default_author', $ret, $use_post, $opt_pre );
			if ( $ret === true && $this->p->debug->enabled )
				$this->p->debug->log( 'default author is forced' );
			return $ret;
		}

		public function force_default_image( $use_post = false, $opt_pre = 'og' ) {
			return $this->force_default_media( $use_post, $opt_pre, 'img' );
		}

		public function force_default_video( $use_post = false, $opt_pre = 'og' ) {
			return $this->force_default_media( $use_post, $opt_pre, 'vid' );
		}

		public function force_default_media( $use_post = false, $opt_pre = 'og', $media = 'img' ) {
			$ret = null;

			// make sure we have default media
			if ( empty( $this->p->options[$opt_pre.'_def_'.$media.'_id'] ) &&
				empty( $this->p->options[$opt_pre.'_def_'.$media.'_url'] ) ) {
					$ret = false;
			} else {
				// check for singular pages first
				if ( $ret === null && SucomUtil::is_post_page( $use_post ) ) {
					$ret = false;
				}
				if ( $ret === null && ! empty( $this->p->options[$opt_pre.'_def_'.$media.'_on_index'] ) ) {
					if ( is_home() || ( is_archive() && ! is_admin() && ! SucomUtil::is_author_page() ) )
						$ret = true;
				}	
				if ( $ret === null && ! empty( $this->p->options[$opt_pre.'_def_'.$media.'_on_search'] ) ) {
					if ( is_search() )
						$ret = true;
				}
				if ( $ret === null ) {
					$ret = false;
				}
			}
			$ret = apply_filters( $this->p->cf['lca'].'_force_default_'.$media, $ret );

			if ( $ret === true && $this->p->debug->enabled )
				$this->p->debug->log( 'default '.$media.' is forced' );

			return $ret;
		}

		public function get_cache_file_url( $url, $url_ext = '' ) {

			if ( empty( $this->p->options['plugin_file_cache_exp'] ) ||
				! isset( $this->p->cache->base_dir ) )	// check for cache attribute, just in case
					return $url;

			return ( apply_filters( $this->p->cf['lca'].'_rewrite_url',
				$this->p->cache->get( $url, 'url', 'file', $this->p->options['plugin_file_cache_exp'], false, $url_ext ) ) );
		}

		public function get_tweet_text( $atts = array(), $opt_prefix = 'twitter', $md_pre = 'twitter' ) {

			$prot = empty( $_SERVER['HTTPS'] ) ? 'http:' : 'https:';
			$use_post = isset( $atts['use_post'] ) ? $atts['use_post'] : true;
			$add_hashtags = isset( $atts['add_hashtags'] ) ? $atts['add_hashtags'] : true;
			$src_id = $this->p->util->get_source_id( $opt_prefix, $atts );

			if ( ! isset( $atts['add_page'] ) )
				$atts['add_page'] = true;	// required by get_sharing_url()

			$long_url = empty( $atts['url'] ) ? 
				$this->p->util->get_sharing_url( $use_post, $atts['add_page'], $src_id ) : 
				apply_filters( $this->p->cf['lca'].'_sharing_url',
					$atts['url'], $use_post, $atts['add_page'], $src_id );

			$short_url = empty( $atts['short_url'] ) ?
				apply_filters( $this->p->cf['lca'].'_shorten_url',
					$long_url, $this->p->options['plugin_shortener'] ) : $atts['short_url'];

			$caption_type = empty( $this->p->options[$opt_prefix.'_caption'] ) ?
				'title' : $this->p->options[$opt_prefix.'_caption'];

			if ( isset( $atts['tweet'] ) )
				$tweet_text = $atts['tweet'];
			else {
				$caption_len = $this->get_tweet_max_len( $long_url, $opt_prefix, $short_url );
				$tweet_text = $this->p->webpage->get_caption( 
					$caption_type,		// title, excerpt, both
					$caption_len,		// max caption length 
					$use_post,		// true/false/post_id
					true,			// use_cache
					$add_hashtags, 		// add_hashtags
					false, 			// encode
					$md_pre.'_desc',	// meta data
					$src_id			// 
				);
			}

			return $tweet_text;
		}

		// $opt_prefix could be twitter, buffer, etc.
		public function get_tweet_max_len( $long_url, $opt_prefix = 'twitter', $short_url = '', $service = '' ) {

			$service = empty( $service ) &&
				isset( $this->p->options['plugin_shortener'] ) ? 
					$this->p->options['plugin_shortener'] : $service;

			$short_url = empty( $short_url ) ? 
				apply_filters( $this->p->cf['lca'].'_shorten_url', $long_url, $service ) : $short_url;

			$len_adjust = strpos( $short_url, 'https:' ) === false ? 1 : 2;

			if ( $short_url < $this->p->options['plugin_min_shorten'] )
				$max_len = $this->p->options[$opt_prefix.'_cap_len'] - strlen( $short_url ) - $len_adjust;
			else $max_len = $this->p->options[$opt_prefix.'_cap_len'] - $this->p->options['plugin_min_shorten'] - $len_adjust;

			if ( ! empty( $this->p->options['tc_site'] ) && 
				! empty( $this->p->options[$opt_prefix.'_via'] ) )
					$max_len = $max_len - strlen( preg_replace( '/^@/', '', 
						$this->p->options['tc_site'] ) ) - 5;	// 5 for 'via' word and 2 spaces

			return $max_len;
		}

		public static function save_all_times( $lca, $version ) {
			self::save_time( $lca, $version, 'update', $version );	// $protect only if same version
			self::save_time( $lca, $version, 'install', true );	// $protect = true
			self::save_time( $lca, $version, 'activate' );		// always update timestamp
		}

		// $protect = true/false/version
		public static function save_time( $lca, $version, $type, $protect = false ) {
			if ( ! is_bool( $protect ) ) {
				if ( ! empty( $protect ) ) {
					if ( ( $ts_version = SucomUtil::get_option_key( WPSSO_TS_NAME, $lca.'_'.$type.'_version' ) ) !== false &&
						version_compare( $ts_version, $protect, '==' ) )
							$protect = true;
					else $protect = false;
				} else $protect = true;	// just in case
			}
			if ( ! empty( $version ) )
				SucomUtil::update_option_key( WPSSO_TS_NAME, $lca.'_'.$type.'_version', $version, $protect );
			SucomUtil::update_option_key( WPSSO_TS_NAME, $lca.'_'.$type.'_time', time(), $protect );
		}

		// get the timestamp array and perform a quick sanity check
		public function get_all_times() {
			$has_changed = false;
			$ts = get_option( WPSSO_TS_NAME, array() );
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				if ( empty( $info['version'] ) )
					continue;
				foreach ( array( 'update', 'install', 'activate' ) as $type ) {
					if ( empty( $ts[$lca.'_'.$type.'_time'] ) ||
						( $type === 'update' && ( empty( $ts[$lca.'_'.$type.'_version'] ) || 
							version_compare( $ts[$lca.'_'.$type.'_version'], $info['version'], '!=' ) ) ) )
								$has_changed = self::save_time( $lca, $info['version'], $type );
				}
			}
			return $has_changed === false ?
				$ts : get_option( WPSSO_TS_NAME, array() );
		}
	
		public function get_mod_obj( $id, $mod = 'post' ) {
			$obj = false;
			if ( empty( $id ) || empty( $mod ) ) {
				if ( ! empty( $id ) )
					$mod = 'post';		// default to post if no module name
				elseif ( SucomUtil::is_post_page( false ) )
					$mod = 'post';
				elseif ( SucomUtil::is_term_page() )
					$mod = 'taxonomy';
				elseif ( SucomUtil::is_author_page() )
					$mod = 'user';
			}
			if ( isset( $this->p->mods['util'][$mod] ) ) {
				$obj =& $this->p->mods['util'][$mod];
				if ( empty( $id ) ) {
					switch ( $mod ) {
						case 'post':
							$id = $this->get_post_object( false, 'id' );
							break;
						case 'taxonomy':
							$id = $this->get_term_object( 'id' );
							break;
						case 'author':
							$id = $this->get_author_object( 'id' );
							break;
					}
				}
			}
			return array( $id, $obj );
		}
	}
}

?>
