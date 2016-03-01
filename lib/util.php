<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoUtil' ) && class_exists( 'SucomUtil' ) ) {

	class WpssoUtil extends SucomUtil {

		protected $inline_vars = array(
			'%%post_id%%',
			'%%request_url%%',
			'%%sharing_url%%',
			'%%short_url%%',
		);
		protected $inline_vals = array();

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! empty( $this->p->options['plugin_'.$this->p->cf['lca'].'_tid'] ) )
				$this->add_plugin_filters( $this, array( 
					'installed_version' => 2, 
					'ua_plugin' => 2,
				), 10, 'sucom' );

			add_action( 'wp', array( &$this, 'add_plugin_image_sizes' ), -100 );	// runs everytime a posts query is triggered from a url
			add_action( 'current_screen', array( &$this, 'add_plugin_image_sizes' ), -100 );
			add_action( 'wp_scheduled_delete', array( &$this, 'delete_expired_db_transients' ) );
			add_action( 'wp_scheduled_delete', array( &$this, 'delete_expired_file_cache' ) );

			// the "current_screen" action hook is not called when editing / saving an image
			// hook the "image_editor_save_pre" filter as to add image sizes for that attachment / post
			add_filter( 'image_save_pre', array( &$this, 'image_editor_save_pre_image_sizes' ), -100, 2 );	// filter deprecated in wp 3.5
			add_filter( 'image_editor_save_pre', array( &$this, 'image_editor_save_pre_image_sizes' ), -100, 2 );
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

			foreach ( $hooks as $name => $val ) {
				if ( ! is_string( $name ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $name.' => '.$val.' '.$type.
							' skipped: filter name must be a string' );
					continue;
				}
				/*
				 * example:
				 * 	'json_data_http_schema_org_item_type' => 8
				 */
				if ( is_int( $val ) ) {
					$arg_nums = $val;
					$hook_name = SucomUtil::sanitize_hookname( $lca.'_'.$name );
					$method_name = SucomUtil::sanitize_hookname( $type.'_'.$name );

					call_user_func( 'add_'.$type, $hook_name, 
						array( &$class, $method_name ), $prio, $arg_nums );

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'added '.$method_name.' (method) '.$type, 3 );
				/*
				 * example:
				 * 	'add_schema_meta_array' => '__return_false'
				 */
				} elseif ( is_string( $val ) ) {
					$arg_nums = 1;
					$hook_name = SucomUtil::sanitize_hookname( $lca.'_'.$name );
					$function_name = SucomUtil::sanitize_hookname( $val );

					call_user_func( 'add_'.$type, $hook_name, 
						$function_name, $prio, $arg_nums );

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'added '.$function_name.' (function) '.$type, 3 );
				/*
				 * example:
				 * 	'json_data_http_schema_org_article' => array(
				 *		'json_data_http_schema_org_article' => 8,
				 *		'json_data_http_schema_org_newsarticle' => 8,
				 *		'json_data_http_schema_org_techarticle' => 8,
				 *	)
				 */
				} elseif ( is_array( $val ) ) {
					$method_name = SucomUtil::sanitize_hookname( $type.'_'.$name );
					foreach ( $val as $hook_name => $arg_nums ) {
						$hook_name = SucomUtil::sanitize_hookname( $lca.'_'.$hook_name );

						call_user_func( 'add_'.$type, $hook_name, 
							array( &$class, $method_name ), $prio, $arg_nums );

						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'added '.$method_name.' (method) '.$type.' to '.$hook_name, 3 );
					}
				}
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

		public function image_editor_save_pre_image_sizes( $image, $post_id ) {
			$this->add_plugin_image_sizes( $post_id, array(), true, 'post' );
			return $image;
		}

		// can be called directly and from the "wp" and "current_screen" actions
		// this method does not return a value, so do not use as a filter
		public function add_plugin_image_sizes( $wp_obj = false, $sizes = array(), $filter = true, $mod_name = false ) {
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
			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'define image sizes' );	// begin timer

			if ( $filter === true ) {
				$sizes = apply_filters( $this->p->cf['lca'].'_plugin_image_sizes',
					$sizes, $wp_obj, $mod_name, SucomUtil::crawler_name() );
			}

			$id = false;
			$use_post = false;
			$meta_opts = array();

			list( $id, $mod_name, $mod_obj ) = $this->get_object_id_mod( $use_post, $wp_obj, $mod_name );

			if ( empty( $mod_name ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'module is unknown' );
			} elseif ( empty( $id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'object id is unknown' );
			} else {
				// custom filters may use image sizes, so don't filter/cache the meta options
				$meta_opts = $this->get_mod_options( $mod_name, $id, false, array( 'filter_options' => false ) );
			}

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
						$size_info[$key] = empty( $size_info[$key] ) ?
							false : true;
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
							$size_info, $id, $mod_name );

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
			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'define image sizes' );	// end timer
		}

		public function add_ptns_to_opts( &$opts = array(), $prefixes, $default = 1 ) {
			if ( ! is_array( $prefixes ) )
				$prefixes = array( $prefixes => $default );

			foreach ( $prefixes as $opt_pre => $def_val ) {
				foreach ( $this->get_post_types() as $post_type ) {
					$idx = $opt_pre.'_'.$post_type->name;
					if ( ! isset( $opts[$idx] ) )
						$opts[$idx] = $def_val;
				}
			}
			return $opts;
		}

		public function get_post_types( $output = 'objects' ) {
			return apply_filters( $this->p->cf['lca'].'_post_types', 
				get_post_types( array( 'public' => true ), $output ), $output );
		}

		public function clear_all_cache( $ext_cache = true ) {
			wp_cache_flush();					// clear non-database transients as well

			$lca = $this->p->cf['lca'];
			$short = $this->p->cf['plugin'][$lca]['short'];
			$del_files = $this->p->util->delete_expired_file_cache( true );
			$del_transients = $this->p->util->delete_expired_db_transients( true );

			$this->p->notice->inf( sprintf( __( '%s cached files, transient cache, and the WordPress object cache have been cleared.',
				'wpsso' ), $short ), true );

			if ( $ext_cache ) {
				$other_cache_msg = __( '%s has been cleared as well.', 'wpsso' );

				if ( function_exists( 'w3tc_pgcache_flush' ) ) {	// w3 total cache
					w3tc_pgcache_flush();
					w3tc_objectcache_flush();
					$this->p->notice->inf( sprintf( $other_cache_msg, 'W3 Total Cache' ), true );
				}

				if ( function_exists( 'wp_cache_clear_cache' ) ) {	// wp super cache
					wp_cache_clear_cache();
					$this->p->notice->inf( sprintf( $other_cache_msg, 'WP Super Cache' ), true );
				}

				if ( isset( $GLOBALS['comet_cache'] ) ) {		// comet cache
					$GLOBALS['comet_cache']->wipe_cache();
					$this->p->notice->inf( sprintf( $other_cache_msg, 'Comet Cache' ), true );
				} elseif ( isset( $GLOBALS['zencache'] ) ) {		// zencache
					$GLOBALS['zencache']->wipe_cache();
					$this->p->notice->inf( sprintf( $other_cache_msg, 'ZenCache' ), true );
				}
			}

			return $del_files + $del_transients;
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

					// transients persist from one page load to another
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
							'lang:'.$lang.'_id:'.$post_id.'_mod:post_column:'.$lca.'_og_desc',
						),
					);
					$transients = apply_filters( $lca.'_post_cache_transients', 
						$transients, $post_id, $lang, $sharing_url );

					// wp objects are only available for the duration of a single page load
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
				$this->p->notice->err( sprintf( __( 'Error reading the %s topic list file.', 'wpsso' ), WPSSO_TOPICS_LIST ) );
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
		public function get_mod_options( $mod_name, $id = false, $idx = false, $atts = array() ) {

			if ( empty( $id ) || 
				! isset( $this->p->m['util'][$mod_name] ) )
					return null;

			// return the whole options array
			if ( $idx === false ) {
				$ret = $this->p->m['util'][$mod_name]->get_options( $id, $idx, $atts );

			// return the first matching index value
			} else {
				if ( ! is_array( $idx ) )
					$idx = array( $idx );
				else $idx = array_unique( $idx );	// just in case

				foreach ( $idx as $key ) {
					if ( $key === 'none' )		// special index keyword
						return null;
					elseif ( empty( $key ) )
						continue;
					// get_options() returns null if key index is missing
					elseif ( ( $ret = $this->p->m['util'][$mod_name]->get_options( $id, $key, $atts ) ) !== null );
						break;
				}
			}

			if ( $ret !== null ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'custom '.$mod_name.' '.
						( $idx === false ? 'options' : ( is_array( $idx ) ? implode( ', ', $idx ) : $idx ) ).' = '.
						( is_array( $ret ) ? print_r( $ret, true ) : '"'.$ret.'"' ) );
				}
			}
			return $ret;
		}

		public function sanitize_option_value( $key, $val, $def_val, $network = false, $mod_name = false ) {

			// remove localization for more generic match
			if ( preg_match( '/(#.*|:[0-9]+)$/', $key ) > 0 )
				$key = preg_replace( '/(#.*|:[0-9]+)$/', '', $key );

			// hooked by the sharing class
			$option_type = apply_filters( $this->p->cf['lca'].'_option_type', false, $key, $network, $mod_name );

			// pre-filter most values to remove html
			switch ( $option_type ) {
				case 'html':	// leave html and css / javascript code blocks as-is
				case 'code':
					$val = stripslashes( $val );
					break;
				default:
					$val = stripslashes( $val );
					$val = wp_filter_nohtml_kses( $val );
					$val = SucomUtil::encode_emoji( htmlentities( $val, 
						ENT_QUOTES, get_bloginfo( 'charset' ), false ) );	// double_encode = false
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
							$this->p->notice->err( sprintf( __( 'The value of option \'%s\' must be a URL - resetting the option to its default value.', 'wpsso' ), $key ), true );
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
				case 'img_width':	// image height, subject to minimum value (typically, at least 200px)
				case 'img_height':	// image height, subject to minimum value (typically, at least 200px)

					if ( $option_type == 'img_width' )
						$min_int = $this->p->cf['head']['min']['og_img_width'];
					elseif ( $option_type == 'img_height' )
						$min_int = $this->p->cf['head']['min']['og_img_height'];
					else $min_int = 1;

					// custom meta options are allowed to be empty
					if ( $val === '' && $mod_name !== false )
						break;
					elseif ( ! is_numeric( $val ) || $val < $min_int ) {
						$this->p->notice->err( sprintf( __( 'The value of option \'%s\' must be equal to or greather than %s - resetting the option to its default value.', 'wpsso' ), $key, $min_int ), true );
						$val = $def_val;
					} else $val = (int) $val;		// cast as integer

					break;

				// must be blank or numeric
				case 'blank_num':
					if ( $val !== '' ) {
						if ( ! is_numeric( $val ) ) {
							$this->p->notice->err( sprintf( __( 'The value of option \'%s\' must be numeric - resetting the option to its default value.', 'wpsso' ), $key ), true );
							$val = $def_val;
						} else $val = (int) $val;	// cast as integer
					}
					break;

				// must be numeric
				case 'numeric':
					if ( ! is_numeric( $val ) ) {
						$this->p->notice->err( sprintf( __( 'The value of option \'%s\' must be numeric - resetting the option to its default value.', 'wpsso' ), $key ), true );
						$val = $def_val;
					} else $val = (int) $val;		// cast as integer
					break;

				// empty of alpha-numeric uppercase (hyphens are allowed as well)
				case 'auth_id':
					$val = trim( $val );
					if ( $val !== '' && preg_match( '/[^A-Z0-9\-]/', $val ) ) {
						$this->p->notice->err( sprintf( __( '\'%1$s\' is not an acceptable value for option \'%2$s\' - resetting the option to its default value.', 'wpsso' ), $val, $key ), true );
						$val = $def_val;
					} else $val = (string) $val;		// cast as string
					break;

				// empty or alpha-numeric (upper or lower case), plus underscores
				case 'api_key':
					$val = trim( $val );
					if ( $val !== '' && preg_match( '/[^a-zA-Z0-9_]/', $val ) ) {
						$this->p->notice->err( sprintf( __( 'The value of option \'%s\' must be alpha-numeric - resetting the option to its default value.', 'wpsso' ), $key ), true );
						$val = $def_val;
					} else $val = (string) $val;		// cast as string
					break;

				// text strings that can be blank
				case 'ok_blank':
					if ( $val !== '' )
						$val = trim( $val );
					break;

				// text strings that can be blank (line breaks are removed)
				case 'desc':
				case 'one_line':
					if ( $val !== '' )
						$val = trim( preg_replace( '/[\s\n\r]+/s', ' ', $val ) );
					break;

				// empty string or must include at least one HTML tag
				case 'html':
					if ( $val !== '' ) {
						$val = trim( $val );
						if ( ! preg_match( '/<.*>/', $val ) ) {
							$this->p->notice->err( sprintf( __( 'The value of option \'%s\' must be HTML code - resetting the option to its default value.', 'wpsso' ), $key ), true );
							$val = $def_val;
						}
					}
					break;

				// options that cannot be blank
				case 'code':
				case 'not_blank':
					if ( $val === '' ) {
						$this->p->notice->err( sprintf( __( 'The value of option \'%s\' cannot be empty - resetting the option to its default value.', 'wpsso' ), $key ), true );
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
		public function get_head_meta( $request, $query = '/html/head/meta', $remove_self = false ) {

			if ( empty( $query ) )
				return false;

			if ( strpos( $request, '<' ) !== false )	// check for HTML content
				$html = $request;
			elseif ( strpos( $request, '://' ) !== false && 
				( $html = $this->p->cache->get( $request, 'raw', 'transient' ) ) === false )
					return false;
			else return false;

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
					$m_atts = array();		// put all attributes in a single array

					foreach ( $m->attributes as $a )
						$m_atts[$a->name] = $a->value;

					if ( isset( $m->textContent ) )
						$m_atts['textContent'] = $m->textContent;

					$ret[$m->tagName][] = $m_atts;
				}
			} else {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'DOMDocument PHP class is missing' );
				if ( is_admin() )
					$this->p->notice->err( __( 'The DOMDocument PHP class is missing - unable to read head meta from HTML. Please contact your hosting provider to install the missing DOMDocument PHP class.', 'wpsso' ), true );
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

		public function get_default_author_id( $opt_pre = 'og' ) {
			$lca = $this->p->cf['lca'];
			$id = isset( $this->p->options[$opt_pre.'_def_author_id'] ) ? 
				$this->p->options[$opt_pre.'_def_author_id'] : null;
			return apply_filters( $lca.'_'.$opt_pre.'_default_author_id', $id );
		}

		// returns an author id if the default author is forced
		public function force_default_author( $use_post = false, $opt_pre = 'og' ) {
			$id = $this->get_default_author_id( $opt_pre );		// applies the author id filter
			return $id && $this->force_default( $use_post, $opt_pre, 'author' ) ?
				$id : false;
		}

		// returns true if the default image is forced
		public function force_default_image( $use_post = false, $opt_pre = 'og' ) {
			return $this->force_default( $use_post, $opt_pre, 'img' );
		}

		// returns true if the default video is forced
		public function force_default_video( $use_post = false, $opt_pre = 'og' ) {
			return $this->force_default( $use_post, $opt_pre, 'vid' );
		}

		public function force_default( $use_post = false, $opt_pre = 'og', $type ) {

			$lca = $this->p->cf['lca'];
			$def = array();

			foreach ( array( 'id', 'url', 'on_index', 'on_search' ) as $key )
				$def[$key] = apply_filters( $lca.'_'.$opt_pre.'_default_'.$type.'_'.$key, 
					( isset( $this->p->options[$opt_pre.'_def_'.$type.'_'.$key] ) ? 
						$this->p->options[$opt_pre.'_def_'.$type.'_'.$key] : null ) );

			// save some time
			if ( empty( $def['id'] ) && 
				empty( $def['url'] ) )
					$ret = false;

			// check for singular pages first
			elseif ( SucomUtil::is_post_page( $use_post ) )
				$ret = false;

			elseif ( ! empty( $def['on_index'] ) &&
				( is_home() || ( is_archive() && ! is_admin() && ! SucomUtil::is_author_page() ) ) )
					$ret = true;

			elseif ( ! empty( $def['on_search'] ) &&
				is_search() )
					$ret = true;

			else $ret = false;

			$ret = apply_filters( $this->p->cf['lca'].'_force_default_'.$type, $ret, $use_post, $opt_pre );

			if ( $ret && $this->p->debug->enabled )
				$this->p->debug->log( 'default '.$type.' is forced' );

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
					if ( ( $ts_version = SucomUtil::get_option_key( WPSSO_TS_NAME, $lca.'_'.$type.'_version' ) ) !== null &&
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
	
		// deprecated 2015/12/09
		public function push_add_to_options( &$opts, $arr = array(), $def = 1 ) {
			return $opts;
		}

		public function get_inline_vars() {
			return $this->inline_vars;
		}

		public function get_inline_vals( $use_post = false, &$post_obj = false, &$atts = array() ) {

			if ( ! is_object( $post_obj ) ) {
				if ( ( $post_obj = $this->get_post_object( $use_post ) ) === false ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: invalid object type' );
					return array();
				}
			}
			$post_id = empty( $post_obj->ID ) || 
				empty( $post_obj->post_type ) ? 
					0 : $post_obj->ID;

			if ( isset( $atts['url'] ) )
				$sharing_url = $atts['url'];
			else $sharing_url = $this->get_sharing_url( $use_post, 
				( isset( $atts['add_page'] ) ?
					$atts['add_page'] : true ),
				( isset( $atts['source_id'] ) ?
					$atts['source_id'] : false ) );

			if ( is_admin() )
				$request_url = $sharing_url;
			else $request_url = self::get_prot().'://'.
				$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

			$short_url = empty( $atts['short_url'] ) ?
				apply_filters( $this->p->cf['lca'].'_shorten_url',
					$sharing_url, $this->p->options['plugin_shortener'] ) : $atts['short_url'];

			$this->inline_vals = array(
				$post_id,		// %%post_id%%
				$request_url,		// %%request_url%%
				$sharing_url,		// %%sharing_url%%
				$short_url,		// %%short_url%%
			);

			return $this->inline_vals;
		}

		// allow the variables and values array to be extended
		// $ext must be an associative array with key/value pairs to be replaced
		public function replace_inline_vars( $text, $use_post = false, $post_obj = false, $atts = array(), $ext = array() ) {

			if ( strpos( $text, '%%' ) === false ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: no inline vars' );
				return $text;
			}

			$vars = $this->inline_vars;
			$vals = $this->get_inline_vals( $use_post, $post_obj, $atts );

			if ( ! empty( $ext ) && 
				self::is_assoc( $ext ) ) {

				foreach ( $ext as $key => $str ) {
					$vars[] = '%%'.$key.'%%';
					$vals[] = $str;
				}
			}

			return str_replace( $vars, $vals, $text );
		}

		public function add_image_url_sizes( $keys, array &$arr ) {

			if ( SucomUtil::get_const( 'WPSSO_GETIMGSIZE_DISABLE' ) )
				return $arr;

			if ( ! is_array( $keys ) )
				$keys = array( $keys );

			foreach ( $keys as $pre ) {
				if ( ! empty( $arr[$pre] ) &&
					strpos( $arr[$pre], '://' ) !== false ) {

					list( $arr[$pre.':width'], $arr[$pre.':height'],
						$image_type, $image_attr ) = @getimagesize( $arr[$pre] );

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'getimagesize() for '.$arr[$pre].' returned '.
							$arr[$pre.':width'].'x'.$arr[$pre.':height'] );
				} else {
					foreach ( array( 'width', 'height' ) as $wh )
						if ( isset( $arr[$pre.':'.$wh] ) )
							$arr[$pre.':'.$wh] = -1;
				}
			}

			return $arr;
		}

		// accepts json script or json array
		public function json_format( $json, $options = JSON_UNESCAPED_SLASHES, $depth = 512 ) {

			$json_format = false;

			if ( is_admin() || $this->p->debug->enabled ) {
				if ( function_exists( 'phpversion' ) && phpversion() >= 5.4 )
					$options = $options|JSON_PRETTY_PRINT;
				else $json_format = true;
			}

			if ( ! is_string( $json ) )
				$json = SucomUtil::json_encode_array( $json, $options, $depth );

			if ( $json_format ) {
				$classname = WpssoConfig::load_lib( false, 'ext/json-format', 'suextjsonformat' );
				if ( $classname !== false && class_exists( $classname ) )
					$json = SuextJsonFormat::get( $json, $options, $depth );
			}

			return $json;
		}

		// $id can be a numeric id or a WP class object
		public function get_object_id_mod( $use_post = false, $id = false, $mod_name = false ) {

			// check for a recognized object
			if ( is_object( $id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'object is '.get_class( $id ) );
				switch ( get_class( $id ) ) {
					case 'WP_Post':
						$mod_name = 'post';
						$id = $id->ID;
						break;
					case 'WP_Term':
						$mod_name = 'taxonomy';
						$id = $id->term_id;
						break;
					case 'WP_User':
						$mod_name = 'user';
						$id = $id->ID;
						break;
					default:
						$id = false;
						break;
				}
			}

			if ( empty( $mod_name ) ) {
				if ( SucomUtil::is_post_page( $use_post ) )
					$mod_name = 'post';
				elseif ( SucomUtil::is_term_page() )
					$mod_name = 'taxonomy';
				elseif ( SucomUtil::is_author_page() )
					$mod_name = 'user';
				else $mod_name = false;
			}

			if ( ! empty( $mod_name ) ) {
				if ( empty( $id ) ) {
					if ( $mod_name === 'post' )
						$id = $this->get_post_object( $use_post, 'id' );
					elseif ( $mod_name === 'taxonomy' )
						$id = $this->get_term_object( 'id' );
					elseif ( $mod_name === 'user' )
						$id = $this->get_author_object( 'id' );
					else $id = false;
				}

				if ( isset( $this->p->m['util'][$mod_name] ) )
					$mod_obj =& $this->p->m['util'][$mod_name];
				else $mod_obj = false;

			} else $mod_obj = false;

			return array( $id, $mod_name, $mod_obj );
		}

		public function is_maxed( &$arr, $num = 0 ) {
			if ( ! is_array( $arr ) ) 
				return false;
			if ( $num > 0 && count( $arr ) >= $num ) 
				return true;
			return false;
		}

		public function push_max( &$dst, &$src, $num = 0 ) {
			if ( ! is_array( $dst ) || 
				! is_array( $src ) ) 
					return false;

			// if the array is not empty, or contains some non-empty values, then push it
			if ( ! empty( $src ) && 
				array_filter( $src ) ) 
					array_push( $dst, $src );

			return $this->slice_max( $dst, $num );	// returns true or false
		}

		public function slice_max( &$arr, $num = 0 ) {
			if ( ! is_array( $arr ) )
				return false;

			$has = count( $arr );

			if ( $num > 0 ) {
				if ( $has == $num ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'max values reached ('.$has.' == '.$num.')' );
					return true;
				} elseif ( $has > $num ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'max values reached ('.$has.' > '.$num.') - slicing array' );
					$arr = array_slice( $arr, 0, $num );
					return true;
				}
			}

			return false;
		}

		// if $id is 0 or false, return values from the plugin settings 
		public function get_max_nums( $id = false, $mod_name = false ) {

			$max = array();

			foreach ( array( 'og_vid_max', 'og_img_max' ) as $max_name ) {

				if ( ! empty( $id ) && 
					isset( $this->p->m['util'][$mod_name] ) )
						$num_meta = $this->p->m['util'][$mod_name]->get_options( $id, $max_name );
				else $num_meta = null;	// default value returned by get_options() if index key is missing

				// quick sanitation of returned value
				if ( is_numeric( $num_meta ) && $num_meta >= 0 ) {
					$max[$max_name] = $num_meta;
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'found custom meta '.$max_name.' = '.$num_meta );
				} else $max[$max_name] = $this->p->options[$max_name];	// fallback to options
			}

			return $max;
		}
	}
}

?>
