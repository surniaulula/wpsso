<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoUtil' ) && class_exists( 'SucomUtil' ) ) {

	class WpssoUtil extends SucomUtil {

		protected $cleared_all_cache = false;
		protected $uniq_urls = array();			// array to detect duplicate images, etc.
		protected $size_labels = array();		// reference array for image size labels
		protected $force_regen = array(
			'cache'     => null,			// cache for returned values
			'transient' => null,			// transient array from/to database
		);

		protected $is_functions = array(
			'is_ajax',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_category',
			'is_front_page',
			'is_home',
			'is_multisite',
			'is_page',
			'is_post_type_archive',
			'is_search',
			'is_single',
			'is_singular',
			'is_ssl',
			'is_tag',
			'is_tax',
			/**
			 * common e-commerce / woocommerce functions
			 */
			'is_account_page',
			'is_cart',
			'is_checkout',
			'is_checkout_pay_page',
			'is_product',
			'is_product_category',
			'is_product_tag',
			'is_shop',
			/**
			 * other functions
			 */
			'is_amp_endpoint',
		);

		protected static $form_cache = array();

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Several actions must be hooked to define our image sizes on the front-end,
			 * back-end, AJAX calls, REST API calls, etc.
			 */
			add_action( 'wp', array( $this, 'add_plugin_image_sizes' ), -100 );		// For front-end.
			add_action( 'admin_init', array( $this, 'add_plugin_image_sizes' ), -100 );	// For back-end + AJAX compatibility.
			add_action( 'rest_api_init', array( $this, 'add_plugin_image_sizes' ), -100 );	// For REST API compatibility.

			add_action( 'wp_scheduled_delete', array( $this, 'delete_expired_db_transients' ) );
			add_action( $this->p->lca . '_refresh_all_cache', array( $this, 'refresh_all_cache' ) );

			/**
			 * The "current_screen" action hook is not called when editing / saving an image.
			 * Hook the "image_editor_save_pre" filter as to add image sizes for that attachment / post.
			 */
			add_filter( 'image_save_pre', array( $this, 'image_editor_save_pre_image_sizes' ), -100, 2 );	// Filter deprecated in wp 3.5.
			add_filter( 'image_editor_save_pre', array( $this, 'image_editor_save_pre_image_sizes' ), -100, 2 );
		}

		/**
		 * Disable transient cache for debug mode. This method is also called for non-WordPress
		 * sharing / canonical URLs with query arguments.
		 */
		public function disable_cache_filters( array $add_filters = array() ) {

			static $do_once = array();

			$default_filters = array(
				'cache_expire_head_array'       => '__return_zero',
				'cache_expire_schema_json_data' => '__return_zero',
				'cache_expire_setup_html'       => '__return_zero',
				'cache_expire_shortcode_html'   => '__return_zero',
				'cache_expire_sharing_buttons'  => '__return_zero',
			);

			$disable_filters = array();

			foreach ( array_merge( $default_filters, $add_filters ) as $filter_name => $callback ) {
				if ( ! isset( $do_once[$filter_name] ) ) {
					$do_once[$filter_name] = true;
					$disable_filters[$filter_name] = $callback;
				}
			}

			if ( ! empty( $disable_filters ) ) {
				$this->add_plugin_filters( $this, $disable_filters );
			}
		}

		/**
		 * Called from several class __construct() methods to hook their filters.
		 */
		public function add_plugin_filters( $class, $filters, $prio = 10, $ext = '' ) {
			$this->add_plugin_hooks( 'filter', $class, $filters, $prio, $ext );
		}

		/**
		 * Called from several class __construct() methods to hook their actions.
		 */
		public function add_plugin_actions( $class, $actions, $prio = 10, $ext = '' ) {
			$this->add_plugin_hooks( 'action', $class, $actions, $prio, $ext );
		}

		protected function add_plugin_hooks( $type, $class, $hook_list, $prio, $ext = '' ) {

			$ext = $ext === '' ? $this->p->lca : $ext;

			foreach ( $hook_list as $name => $val ) {

				if ( ! is_string( $name ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $name . ' => ' . $val . ' ' . $type . ' skipped: filter name must be a string' );
					}
					continue;
				}

				/**
				 * example:
				 * 	'json_data_https_schema_org_website' => 5
				 */
				if ( is_int( $val ) ) {

					$arg_nums    = $val;
					$hook_name   = SucomUtil::sanitize_hookname( $ext . '_' . $name );
					$method_name = SucomUtil::sanitize_hookname( $type . '_' . $name );

					call_user_func( 'add_' . $type, $hook_name, array( &$class, $method_name ), $prio, $arg_nums );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'added ' . $method_name . ' (method) ' . $type, 3 );
					}
				/**
				 * example:
				 * 	'add_schema_meta_array' => '__return_false'
				 */
				} elseif ( is_string( $val ) ) {

					$arg_nums      = 1;
					$hook_name     = SucomUtil::sanitize_hookname( $ext . '_' . $name );
					$function_name = SucomUtil::sanitize_hookname( $val );

					call_user_func( 'add_' . $type, $hook_name, $function_name, $prio, $arg_nums );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'added ' . $function_name . ' (function) ' . $type . ' for ' . $hook_name, 3 );
					}
				/**
				 * example:
				 * 	'json_data_https_schema_org_article' => array(
				 *		'json_data_https_schema_org_article' => 5,
				 *		'json_data_https_schema_org_newsarticle' => 5,
				 *		'json_data_https_schema_org_techarticle' => 5,
				 *	)
				 */
				} elseif ( is_array( $val ) ) {

					$method_name = SucomUtil::sanitize_hookname( $type . '_' . $name );

					foreach ( $val as $hook_name => $arg_nums ) {

						$hook_name = SucomUtil::sanitize_hookname( $ext . '_' . $hook_name );

						call_user_func( 'add_' . $type, $hook_name, array( &$class, $method_name ), $prio, $arg_nums );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'added ' . $method_name . ' (method) ' . $type . ' to ' . $hook_name, 3 );
						}
					}
				}
			}
		}

		/**
		 * $opt_prefixes can be a single key name or an array of key names.
		 * Uses a reference variable to modify the $opts array directly.
		 */
		public function add_image_url_size( array &$opts, $opt_prefixes = 'og:image' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! is_array( $opt_prefixes ) ) {
				$opt_prefixes = array( $opt_prefixes );
			}

			foreach ( $opt_prefixes as $opt_image_pre ) {

				$opt_suffix = '';

				if ( preg_match( '/^(.*)(#.*)$/', $opt_image_pre, $matches ) ) {	// Language.
					$opt_image_pre = $matches[1];
					$opt_suffix    = $matches[2] . $opt_suffix;
				}

				if ( preg_match( '/^(.*)(_[0-9]+)$/', $opt_image_pre, $matches ) ) {	// Multi-option.
					$opt_image_pre = $matches[1];
					$opt_suffix    = $matches[2] . $opt_suffix;
				}

				$media_url = SucomUtil::get_mt_media_url( $opts, $opt_image_pre . $opt_suffix );

				if ( ! empty( $media_url ) ) {

					$image_info = $this->get_image_url_info( $media_url );

					list(
						$opts[$opt_image_pre . ':width' . $opt_suffix],		// Example 'place_addr_img_url:width_1'.
						$opts[$opt_image_pre . ':height' . $opt_suffix],	// Example 'place_addr_img_url:height_1'.
						$image_type,
						$image_attr
					) = $image_info;
				}
			}

			return $opts;
		}

		public function get_image_url_info( $image_url ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			static $local_cache = array(); // Optimize and get image size for a given URL only once.

			if ( isset( $local_cache[$image_url] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: returning image info from static cache' );
				}
				return $local_cache[$image_url];
			}

			$is_disabled    = SucomUtil::get_const( 'WPSSO_PHP_GETIMGSIZE_DISABLE' );
			$def_image_info = array( WPSSO_UNDEF_INT, WPSSO_UNDEF_INT, '', '' );
			$image_info     = false;

			if ( $is_disabled ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: use of getimagesize() is disabled' );
				}

				return $local_cache[$image_url] = $def_image_info;	// stop here

			} elseif ( empty( $image_url ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: image url is empty' );
				}

				return $local_cache[$image_url] = $def_image_info;	// stop here

			} elseif ( filter_var( $image_url, FILTER_VALIDATE_URL ) === false ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: invalid image url = '.$image_url );
				}

				return $local_cache[$image_url] = $def_image_info;	// stop here
			}

			static $cache_exp_secs = null;	// filter the cache expiration value only once

			$cache_md5_pre = $this->p->lca . '_i_';

			if ( ! isset( $cache_exp_secs ) ) {	// filter cache expiration if not already set
				$cache_exp_filter = $this->p->cf['wp']['transient'][$cache_md5_pre]['filter'];
				$cache_opt_key    = $this->p->cf['wp']['transient'][$cache_md5_pre]['opt_key'];
				$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, $this->p->options[$cache_opt_key] );	// 1 * DAY_IN_SECONDS by default
			}

			if ( $cache_exp_secs > 0 ) {

				/**
				 * Note that cache_id is a unique identifier for the cached data and should be 45 characters or
				 * less in length. If using a site transient, it should be 40 characters or less in length.
				 */
				$cache_salt = __METHOD__ . '(url:' . $image_url . ')';
				$cache_id   = $cache_md5_pre . md5( $cache_salt );
	
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'transient cache salt ' . $cache_salt );
				}

				$image_info = get_transient( $cache_id );

				if ( is_array( $image_info ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'returning image info from transient: ' . $image_info[0] . 'x' . $image_info[1] );
					}
					return $local_cache[$image_url] = $image_info;
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'transient cache for image info is disabled' );
			}

			$max_time   = SucomUtil::get_const( 'WPSSO_PHP_GETIMGSIZE_MAX_TIME', 1.50 );
			$start_time = microtime( true );
			$image_info = $this->p->cache->get_image_size( $image_url );	// Wrapper for PHP's getimagesize().
			$total_time = microtime( true ) - $start_time;

			/**
			 * Issue warning for slow getimagesize() request.
			 */
			if ( $max_time > 0 && $total_time > $max_time ) {

				$info = $this->p->cf['plugin'][$this->p->lca];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( sprintf( 'slow PHP function detected - getimagesize() took %1$0.3f secs for %2$s',
						$total_time, $image_url ) );
				}

				// translators: %1$0.3f is a number of seconds
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $max_time );

				// translators: %1$0.3f is a number of seconds, %2$s is an image URL, %3$s is a recommended max
				$error_msg = sprintf( __( 'Slow PHP function detected - getimagesize() took %1$0.3f secs for %2$s (%3$s).',
					'wpsso' ), $total_time, $image_url, $rec_max_msg );

				/**
				 * Show an admin warning notice, if notices not already shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {
					$this->p->notice->warn( $error_msg );
				}

				// translators: %s is the short plugin name
				$error_pre = sprintf( __( '%s warning:', 'wpsso' ), $info['short'] );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			if ( is_array( $image_info ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'PHP getimagesize() image info: '.$image_info[0].'x'.$image_info[1] );
				}
			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'PHP getimagesize() did not return an array - using defaults for cache' );
				}
				$image_info = $def_image_info;
			}

			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $image_info, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'image info saved to transient cache for '.$cache_exp_secs.' seconds' );
				}
			}

			return $local_cache[$image_url] = $image_info;
		}

		public function get_image_size_label( $size_name ) {	// wpsso-opengraph
			if ( ! empty( $this->size_labels[$size_name] ) ) {
				return $this->size_labels[$size_name];
			} else {
				return $size_name;
			}
		}

		public function image_editor_save_pre_image_sizes( $image, $post_id = false ) {

			if ( empty( $post_id ) ) {
				return $image;
			}

			$wp_obj       = false;
			$image_sizes  = array();
			$mod          = $this->p->m['util']['post']->get_mod( $post_id );
			$filter_sizes = true;

			$this->add_plugin_image_sizes( $wp_obj, $image_sizes, $mod, $filter_sizes );

			return $image;
		}

		/**
		 * Can be called directly and from the "wp", "rest_api_init", and "current_screen" actions.
		 * The $wp_obj variable can be false or a WP object (WP_Post, WP_Term, WP_User, WP_REST_Server, etc.).
		 * The $mod variable can be false, and if so, it will be set using get_page_mod().
		 * This method does not return a value, so do not use as a filter. ;-)
		 */
		public function add_plugin_image_sizes( $wp_obj = false, $image_sizes = array(), &$mod = false, $filter_sizes = true ) {

			/**
			 * Allow various plugin add-ons to provide their image names, labels, etc.
			 * The first dimension array key is the option name prefix by default.
			 * You can also include the width, height, crop, crop_x, and crop_y values.
			 *
			 *	Array (
			 *		[og_img] => Array (
			 *			[name] => opengraph
			 *			[label] => Open Graph Image Dimensions
			 *		)
			 *		[p_img] => Array (
			 *			[name] => richpin
			 *			[label] => Rich Pin Image Dimensions
			 *		)
			 *	)
			 */
			if ( $this->p->debug->enabled ) {

				$wp_obj_type = gettype( $wp_obj ) === 'object' ? get_class( $wp_obj ) . ' object' : gettype( $wp_obj );
				$is_ajax_str = defined( 'DOING_AJAX' ) && DOING_AJAX ? 'true' : 'false';

				$this->p->debug->mark( 'define image sizes' );	// Begin timer.

				$this->p->debug->log( '$wp_obj is ' . $wp_obj_type );
				$this->p->debug->log( 'DOING_AJAX is ' . $is_ajax_str );
			}

			$use_post = false;
			$has_pdir = $this->p->avail['*']['p_dir'];
			$has_aop  = $this->p->check->aop( $this->p->lca, true, $has_pdir );

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $use_post, $mod, $wp_obj );
			}

			$md_opts = array();

			if ( true === $filter_sizes ) {
				$image_sizes = apply_filters( $this->p->lca . '_plugin_image_sizes', $image_sizes, $mod, SucomUtil::get_crawler_name() );
			}

			if ( empty( $mod['id'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'module id is unknown' );
				}
			} elseif ( empty( $mod['name'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'module name is unknown' );
				}
			} elseif ( ! empty( $mod['id'] ) && ! empty( $mod['obj'] ) && $has_aop ) {

				/**
				 * Returns an empty string if no meta found.
			 	 * Custom filters may use image sizes, so don't filter/cache the meta options.
				 */
				$md_opts = $mod['obj']->get_options( $mod['id'], false, false );	// $filter_opts is false.
			}

			foreach( $image_sizes as $opt_prefix => $size_info ) {

				if ( ! is_array( $size_info ) ) {

					$save_name = empty( $size_info ) ? $opt_prefix : $size_info;

					$size_info = array(
						'name'  => $save_name,
						'label' => $save_name
					);

				} elseif ( ! empty( $size_info['prefix'] ) ) {	// Allow for alternate option prefix.

					$opt_prefix = $size_info['prefix'];
				}

				foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $key ) {

					if ( isset( $size_info[$key] ) ) {					// Prefer existing info from filters.

						continue;

					} elseif ( isset( $md_opts[$opt_prefix . '_' . $key] ) ) {		// Use post meta if available.

						$size_info[$key] = $md_opts[$opt_prefix . '_' . $key];

					} elseif ( isset( $this->p->options[$opt_prefix . '_' . $key] ) ) {	// Current plugin settings.

						$size_info[$key] = $this->p->options[$opt_prefix . '_' . $key];

					} else {

						if ( ! isset( $def_opts ) ) {					// Only read once if necessary.

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'getting default option values' );
							}

							$def_opts = $this->p->opt->get_defaults();
						}

						$size_info[$key] = $def_opts[$opt_prefix . '_' . $key];		// Fallback to default value.
					}

					if ( $key === 'crop' ) {						// Make sure crop is true or false.
						$size_info[$key] = empty( $size_info[$key] ) ? false : true;
					}
				}

				if ( $size_info['width'] > 0 && $size_info['height'] > 0 ) {

					/**
					 * Preserve compatibility with older WordPress versions, use true or false when possible.
					 */
					if ( true === $size_info['crop'] && ( $size_info['crop_x'] !== 'center' || $size_info['crop_y'] !== 'center' ) ) {

						global $wp_version;

						if ( version_compare( $wp_version, '3.9', '>=' ) ) {
							$size_info['crop'] = array( $size_info['crop_x'], $size_info['crop_y'] );
						}
					}

					/**
					 * Allow custom function hooks to make changes.
					 */
					if ( true === $filter_sizes ) {
						$size_info = apply_filters( $this->p->lca . '_size_info_' . $size_info['name'], $size_info, $mod['id'], $mod['name'] );
					}

					/**
					 * A lookup array for image size labels, used in image size error messages.
					 */
					$this->size_labels[$this->p->lca . '-' . $size_info['name']] = $size_info['label'];

					add_image_size( $this->p->lca . '-' . $size_info['name'], $size_info['width'], $size_info['height'], $size_info['crop'] );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'image size ' . $this->p->lca . '-' . $size_info['name'] . ' ' . 
							$size_info['width'] . 'x' . $size_info['height'] . 
							( empty( $size_info['crop'] ) ? '' : ' crop ' . 
								$size_info['crop_x'] . '/' . $size_info['crop_y'] ) . ' added' );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'define image sizes' );	// End timer.

				$this->p->debug->log_arr( 'get_all_image_sizes', SucomUtil::get_image_sizes() );
			}
		}

		/**
		 * $mod    = true | false | post_id | $mod array 
		 * $md_pre = 'og' | 'og_img' | etc.
		 */
		public function set_force_regen( $mod, $md_pre = 'og', $value = true ) {

			$regen_key = $this->get_force_regen_key( $mod, $md_pre );

			if ( $regen_key !== false ) {

				$cache_md5_pre  = $this->p->lca . '_';
				$cache_exp_secs = 0;	// Never expire.
				$cache_salt     = __CLASS__ . '::force_regen_transient';
				$cache_id       = $cache_md5_pre . md5( $cache_salt );

				if ( $this->force_regen['transient'] === null ) {
					$this->force_regen['transient'] = get_transient( $cache_id );	// Load transient if required.
				}

				if ( $this->force_regen['transient'] === false ) {	// No transient in database.
					$this->force_regen['transient'] = array();
				}

				$this->force_regen['transient'][$regen_key] = $value;

				set_transient( $cache_id, $this->force_regen['transient'], $cache_exp_secs );
			}
		}

		/**
		 * $mod    = true | false | post_id | $mod array 
		 * $md_pre = 'og' | 'og_img' | etc.
		 */
		public function is_force_regen( $mod, $md_pre = 'og' ) {

			$regen_key = $this->get_force_regen_key( $mod, $md_pre );

			if ( $regen_key !== false ) {

				$cache_md5_pre  = $this->p->lca . '_';
				$cache_exp_secs = 0;	// Never expire.
				$cache_salt     = __CLASS__ . '::force_regen_transient';
				$cache_id       = $cache_md5_pre . md5( $cache_salt );

				if ( $this->force_regen['transient'] === null ) {
					$this->force_regen['transient'] = get_transient( $cache_id );	// Load transient if required.
				}

				if ( $this->force_regen['transient'] === false ) {	// No transient in database.
					return false;
				}

				if ( isset( $this->force_regen['cache'][$regen_key] ) )	{ // Previously returned value.
					return $this->force_regen['cache'][$regen_key];
				}

				if ( isset( $this->force_regen['transient'][$regen_key] ) ) {

					$this->force_regen['cache'][$regen_key] = $this->force_regen['transient'][$regen_key];	// Save value.

					unset( $this->force_regen['transient'][$regen_key] );	// Unset the regen key and save transient.

					if ( empty( $this->force_regen['transient'] ) ) {
						delete_transient( $cache_id );
					} else {
						set_transient( $cache_id, $this->force_regen['transient'], $cache_exp_secs );
					}

					return $this->force_regen['cache'][$regen_key];	// Return the cached value.
				}

				return false;	// Not in the cache or transient array.
			}

			return false;
		}

		/**
		 * Get the force regen transient id for set and get methods.
		 *
		 * $mod    = true | false | post_id | $mod array 
		 * $md_pre = 'og' | 'og_img' | etc.
		 */
		public function get_force_regen_key( $mod, $md_pre ) {

			$md_pre = preg_replace( '/_img$/', '', $md_pre );	// Just in case.

			if ( is_numeric( $mod ) && $mod > 0 ) {	// Optimize by skipping get_page_mod().
				return 'post_' . $mod . '_regen_' . $md_pre;
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $mod );
			}

			if ( ! empty( $mod['name'] ) && ! empty( $mod['id'] ) ) {
				return $mod['name'] . '_' . $mod['id'] . '_regen_' . $md_pre;
			} else {
				return false;
			}
		}

		/**
		 * Add options using a key prefix string / array and post type names.
		 */
		public function add_ptns_to_opts( array &$opts, $mixed, $default = 1 ) {

			if ( ! is_array( $mixed ) ) {
				$mixed = array( $mixed => $default );
			}

			foreach ( $mixed as $opt_pre => $def_val ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'checking options for prefix ' . $opt_pre );
				}

				foreach ( $this->get_post_types( 'names' ) as $ptn ) {

					$opt_key = $opt_pre . '_' . $ptn;

					if ( ! isset( $opts[$opt_key] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'adding ' . $opt_key . ' = ' . $def_val );
						}

						$opts[$opt_key] = $def_val;

					} else {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'skipped ' . $opt_key . ' - already set' );
						}
					}
				}
			}

			return $opts;
		}

		/**
		 * Add options using a key prefix string / array and term taxonomy names.
		 */
		public function add_ttns_to_opts( array &$opts, $mixed, $default = 1 ) {

			if ( ! is_array( $mixed ) ) {
				$mixed = array( $mixed => $default );
			}

			foreach ( $mixed as $opt_pre => $def_val ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'checking options for prefix ' . $opt_pre );
				}

				foreach ( $this->get_taxonomies( 'names' ) as $ttn ) {

					$opt_key = $opt_pre . '_' . $ttn;

					if ( ! isset( $opts[$opt_key] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'adding ' . $opt_key . ' = ' . $def_val );
						}

						$opts[$opt_key] = $def_val;

					} else {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'skipped ' . $opt_key . ' - already set' );
						}
					}
				}
			}

			return $opts;
		}

		/**
		 * $output = objects | names
		 */
		public function get_post_types( $output = 'objects', $sorted = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$obj_filter = array( 'public' => 1, 'show_ui' => 1 );
			$ret = array();

			switch ( $output ) {

				/**
				 * Make sure the output name is plural
				 */
				case 'name':
				case 'object':
					$output = $output . 's';
					// no break

				case 'names':
				case 'objects':
					$ret = get_post_types( $obj_filter, $output );
					break;
			}

			if ( $output === 'objects' ) {

				$unsorted = $ret;
				$names = array();
				$ret = array();

				foreach ( $unsorted as $num => $pt ) {
					$ptn = empty( $pt->label ) ? $pt->name : $pt->label;
					$names[$ptn] = $num;
				}

				ksort( $names );

				foreach ( $names as $ptn => $num ) {
					$ret[] = $unsorted[$num];
				}

				unset( $unsorted, $names );
			}

			return apply_filters( $this->p->lca . '_get_post_types', $ret, $output );
		}

		/**
		 * $output = objects | names
		 */
		public function get_taxonomies( $output = 'objects', $sorted = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$obj_filter = array( 'public' => 1, 'show_ui' => 1 );
			$ret = array();

			switch ( $output ) {

				/**
				 * Make sure the output name is plural
				 */
				case 'name':
				case 'object':
					$output = $output . 's';
					// no break

				case 'names':
				case 'objects':
					$ret = get_taxonomies( $obj_filter, $output );
					break;
			}

			if ( $output === 'objects' ) {

				$unsorted = $ret;
				$names = array();
				$ret = array();

				foreach ( $unsorted as $num => $tax ) {
					$ttn = empty( $tax->label ) ? $tax->name : $tax->label;
					$names[$ttn] = $num;
				}

				ksort( $names );

				foreach ( $names as $ttn => $num ) {
					$ret[] = $unsorted[$num];
				}

				unset( $unsorted, $names );
			}

			return apply_filters( $this->p->lca . '_get_taxonomies', $ret, $output );
		}

		public function get_form_cache( $name, $add_none = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$key = SucomUtil::sanitize_key( $name );	// Just in case.

			if ( ! isset( self::$form_cache[$key] ) ) {
				self::$form_cache[$key] = null;		// Create key for default filter.
			}

			if ( self::$form_cache[$key] === null ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding new form cache entry for ' . $key );
				}

				switch ( $key ) {

					case 'half_hours':

						self::$form_cache[$key] = SucomUtil::get_hours_range( 0, DAY_IN_SECONDS, 60 * 30, '' );

						break;

					case 'all_types':

						self::$form_cache[$key] = $this->p->schema->get_schema_types_array( false );	// $flatten = false

						break;

					case 'business_types':

						$this->get_form_cache( 'all_types' );

						self::$form_cache[$key] =& self::$form_cache['all_types']['thing']['place']['local.business'];

						break;

					case 'business_types_select':

						$this->get_form_cache( 'business_types' );

						self::$form_cache[$key] = $this->p->schema->get_schema_types_select( self::$form_cache['business_types'], false );

						break;

					case 'org_types':

						$this->get_form_cache( 'all_types' );

						self::$form_cache[$key] =& self::$form_cache['all_types']['thing']['organization'];

						break;

					case 'org_types_select':

						$this->get_form_cache( 'org_types' );

						self::$form_cache[$key] = $this->p->schema->get_schema_types_select( self::$form_cache['org_types'], false );

						break;

					case 'org_site_names':

						self::$form_cache[$key] = array( 'site' => '[WebSite Organization]' );

						self::$form_cache[$key] = apply_filters( $this->p->lca . '_form_cache_' . $key, self::$form_cache[$key] );

						break;

					case 'person_names':

						self::$form_cache[$key] = WpssoUser::get_person_names();

						break;

					default:

						self::$form_cache[$key] = apply_filters( $this->p->lca . '_form_cache_' . $key, self::$form_cache[$key] );

						break;
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'returning existing form cache entry for ' . $key );
			}

			if ( isset( self::$form_cache[$key]['none'] ) ) {
				unset( self::$form_cache[$key]['none'] );
			}

			if ( $add_none ) {
				$none = array( 'none' => '[None]' );
				if ( is_array( self::$form_cache[$key] ) ) {
					return $none + self::$form_cache[$key];
				} else {
					return $none;
				}
			} else {
				return self::$form_cache[$key];
			}
		}

		public function refresh_all_cache() {

			$mods = array();

			foreach ( WpssoPost::get_public_post_ids() as $post_id ) {
				$mods[] = $this->p->m['util']['post']->get_mod( $post_id );
			}

			foreach ( WpssoTerm::get_public_term_ids() as $term_id ) {
				$mods[] = $this->p->m['util']['term']->get_mod( $term_id );
			}

			foreach ( WpssoUser::get_public_user_ids() as $user_id ) {
				$mods[] = $this->p->m['util']['user']->get_mod( $user_id );
			}

			$wp_obj       = false;
			$image_sizes  = array();
			$filter_sizes = true;

			foreach ( $mods as $mod ) {

				$this->add_plugin_image_sizes( $wp_obj, $image_sizes, $mod, $filter_sizes );

				$head_meta_tags = $this->p->head->get_head_array( false, $mod, true );
				$head_meta_info = $this->p->head->extract_head_info( $mod, $head_meta_tags );
			}
		}

		public function clear_all_cache( $clear_external = true, $clear_short_urls = null, $refresh_cache = null, $dismiss_key = null ) {

			if ( $this->cleared_all_cache ) {	// already run once
				return;
			}

			$this->cleared_all_cache = true;	// prevent running a second time (by an external cache, for example)

			if ( null === $clear_short_urls ) {
				$clear_short_urls = isset( $this->p->options['plugin_clear_short_urls'] ) ?
					$this->p->options['plugin_clear_short_urls'] : false;
			}

			if ( null === $refresh_cache ) {
				$refresh_cache = isset( $this->p->options['plugin_clear_all_refresh'] ) ?
					$this->p->options['plugin_clear_all_refresh'] : false;
			}

			wp_cache_flush();	// clear non-database transients as well

			$deleted_count = 0;

			$deleted_count += $this->delete_all_db_transients( $clear_short_urls );
			$deleted_count += $this->delete_all_cache_files();
			$this->delete_all_column_meta();

			$short = $this->p->cf['plugin'][$this->p->lca]['short'];

			$cleared_msg = sprintf( __( '%s cached files, transient cache, column meta, and WordPress object cache have been cleared.',
				'wpsso' ), $short );

			if ( $clear_external ) {

				$external_msg = ' ' . __( 'The cache for %s has also been cleared.', 'wpsso' );

				if ( function_exists( 'w3tc_pgcache_flush' ) ) {	// w3 total cache
					w3tc_pgcache_flush();
					w3tc_objectcache_flush();
					$cleared_msg .= sprintf( $external_msg, 'W3 Total Cache' );
				}

				if ( function_exists( 'wp_cache_clear_cache' ) ) {	// wp super cache
					wp_cache_clear_cache();
					$cleared_msg .= sprintf( $external_msg, 'WP Super Cache' );
				}

				if ( isset( $GLOBALS['comet_cache'] ) ) {		// comet cache
					$GLOBALS['comet_cache']->wipe_cache();
					$cleared_msg .= sprintf( $external_msg, 'Comet Cache' );
				}
			}

			wp_clear_scheduled_hook( $this->p->lca . '_refresh_all_cache' );

			if ( $refresh_cache ) {

				/**
				 * Will run in the next minute.
				 */
				wp_schedule_single_event( time(), $this->p->lca . '_refresh_all_cache' );

				$cleared_msg .= ' ' . sprintf( __( 'A background task will begin shortly to re-create the %s post, term, and user transient cache objects.',
					'wpsso' ), $short );
			}

			$this->p->notice->inf( $cleared_msg, true, $dismiss_key, true );	// can be dismissed depending on args

			return $deleted_count;
		}

		public function delete_all_db_transients( $clear_short_urls = false ) {

			$only_expired   = false;
			$transient_keys = $this->get_db_transient_keys( $only_expired );
			$deleted_count  = 0;

			foreach( $transient_keys as $cache_id ) {

				/**
				 * Skip / preserve shortened urls by default.
				 */
				if ( ! $clear_short_urls ) {
					$cache_md5_pre = $this->p->lca . '_s_';
					if ( strpos( $cache_id, $cache_md5_pre ) === 0 ) {
						continue;
					}
				}

				if ( delete_transient( $cache_id ) ) {
					$deleted_count++;
				}
			}

			return $deleted_count;
		}

		public function delete_expired_db_transients() {

			$only_expired = true;
			$transient_keys = $this->get_db_transient_keys( $only_expired );
			$deleted_count = 0;

			foreach( $transient_keys as $cache_id ) {
				if ( delete_transient( $cache_id ) ) {
					$deleted_count++;
				}
			}

			return $deleted_count;
		}

		public function get_db_transient_keys( $only_expired = false ) {

			global $wpdb;
			$transient_keys = array();
			$transient_pre = $only_expired ? '_transient_timeout_' : '_transient_';
			$db_query = 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE \'' . $transient_pre . $this->p->lca . '_%\'';

			if ( $only_expired ) {
				$current_time = isset ( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : time() ;
				$db_query .= ' AND option_value < ' . $current_time . ';';	// expiration time older than current time
			} else {
				$db_query .= ';';	// end of query
			}

			$transient_names = $wpdb->get_col( $db_query );

			/**
			 * Remove '_transient_' or '_transient_timeout_' prefix from option name.
			 */
			foreach( $transient_names as $option_name ) {
				$transient_keys[] = str_replace( $transient_pre, '', $option_name );
			}

			return $transient_keys;
		}

		public function delete_all_cache_files() {

			$uca = strtoupper( $this->p->lca );
			$cache_dir = constant( $uca . '_CACHEDIR' );
			$deleted_count = 0;

			if ( ! $dh = @opendir( $cache_dir ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'failed to open the cache folder ' . $cache_dir . ' for reading' );
				}
				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'Failed to open the cache folder %s for reading.',
						'wpsso' ), $cache_dir ) );
				}
			} else {
				while ( $file_name = @readdir( $dh ) ) {
					$cache_file = $cache_dir . $file_name;
					if ( ! preg_match( '/^(\..*|index\.php)$/', $file_name ) && is_file( $cache_file ) ) {
						if ( @unlink( $cache_file ) ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'removed the cache file ' . $cache_file );
							}
							$deleted_count++;
						} else {	
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'failed to remove the cache file '.$cache_file );
							}
							if ( is_admin() ) {
								$this->p->notice->err( sprintf( __( 'Failed to remove the cache file %s.',
									'wpsso' ), $cache_file ) );
							}
						}
					}
				}
				closedir( $dh );
			}

			return $deleted_count;
		}

		public function delete_all_column_meta() {

			$col_meta_keys = WpssoMeta::get_column_meta_keys();

			foreach ( $col_meta_keys as $col_idx => $meta_key ) {
				delete_post_meta_by_key( $meta_key );
			}

			foreach ( WpssoTerm::get_public_term_ids() as $term_id ) {
				foreach ( $col_meta_keys as $col_idx => $meta_key ) {
					WpssoTerm::delete_term_meta( $term_id, $meta_key );
				}
			}

			foreach ( WpssoUser::get_public_user_ids() as $user_id ) {
				foreach ( $col_meta_keys as $col_idx => $meta_key ) {
					delete_user_meta( $user_id, $meta_key );
				}
			}
		}

		public function get_article_topics() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			static $cache_exp_secs = null;	// filter the cache expiration value only once

			$cache_md5_pre = $this->p->lca . '_a_';

			if ( ! isset( $cache_exp_secs ) ) {	// filter cache expiration if not already set
				$cache_exp_filter = $this->p->cf['wp']['transient'][$cache_md5_pre]['filter'];
				$cache_opt_key    = $this->p->cf['wp']['transient'][$cache_md5_pre]['opt_key'];
				$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, $this->p->options[$cache_opt_key] );
			}

			if ( $cache_exp_secs > 0 ) {

				/**
				 * Note that cache_id is a unique identifier for the cached data and should be 45 characters or
				 * less in length. If using a site transient, it should be 40 characters or less in length.
				 */
				$cache_salt = __METHOD__ . '(' . WPSSO_TOPICS_LIST . ')';
				$cache_id   = $cache_md5_pre . md5( $cache_salt );
	
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'transient cache salt ' . $cache_salt );
				}

				$topics = get_transient( $cache_id );

				if ( is_array( $topics ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'article topics retrieved from transient ' . $cache_id );
					}
					return $topics;
				}
			}

			if ( ( $topics = file( WPSSO_TOPICS_LIST, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) ) === false ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'error reading %s article topic list file' );
				}

				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'Error reading %s article topic list file.',
						'wpsso' ), WPSSO_TOPICS_LIST ) );
				}

				return $topics;
			}

			$topics = apply_filters( $this->p->lca . '_article_topics', $topics );

			natsort( $topics );

			$topics = array_merge( array( 'none' ), $topics );	// after sorting the array, put 'none' first

			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $topics, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'article topics saved to transient cache for ' . $cache_exp_secs . ' seconds' );
				}
			}

			return $topics;
		}

		/**
		 * Query argument examples:
		 *
		 * 	/html/head/link|/html/head/meta
		 * 	/html/head/link[@rel="canonical"]
		 * 	/html/head/meta[starts-with(@property, "og:video:")]
		 */
		public function get_head_meta( $request, $query = '/html/head/meta', $libxml_errors = false, array $curl_opts = array() ) {

			if ( empty( $request ) ) {	// just in case

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: the request argument is empty' );
				}

				return false;

			} elseif ( empty( $query ) ) {	// just in case

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: the query argument is empty' );
				}

				return false;

			} elseif ( stripos( $request, '<html' ) !== false ) {	// request contains html

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'using the html submitted as the request argument' );
				}

				$html    = $request;
				$request = false;	// just in case

			} elseif ( filter_var( $request, FILTER_VALIDATE_URL ) === false ) {	// request is an invalid url

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: request argument is not html or a valid url' );
				}

				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'The %1$s request argument is not HTML or a valid URL.',
						'wpsso' ), __FUNCTION__ ) );
				}

				return false;

			} elseif ( ( $html = $this->p->cache->get( $request, 'raw', 'transient', false, '', $curl_opts ) ) === false ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: error caching ' . $request );
				}

				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'Error retrieving webpage from <a href="%1$s">%1$s</a>.',
						'wpsso' ), $request ) );
				}

				return false;

			} elseif ( empty( $html ) ) {	// returned html for url is empty

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: html for ' . $request . ' is empty' );
				}

				if ( is_admin() ) {
					$this->p->notice->err( sprintf( __( 'Webpage retrieved from <a href="%1$s">%1$s</a> is empty.',
						'wpsso' ), $request ) );
				}

				return false;

			} elseif ( ! class_exists( 'DOMDocument' ) ) {

				$this->missing_php_class_error( 'DOMDocument' );

				return false;
			}

			$ret        = array();
			$html       = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );	// convert to UTF8
			$html       = preg_replace( '/<!--.*-->/Uums', '', $html );	// remove all html comments
			$doc        = new DOMDocument();	// since PHP v4.1
			$has_errors = false;

			if ( $libxml_errors ) {

				if ( function_exists( 'libxml_use_internal_errors' ) ) {	// since PHP v5.1

					$libxml_prev_state = libxml_use_internal_errors( true );	// enable user error handling

					if ( ! $doc->loadHTML( $html ) ) {	// loadXML() is too strict for most webpages

						$has_errors = true;

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'loadHTML returned error(s)' );
						}

						foreach ( libxml_get_errors() as $error ) {
							/**
							 *	libXMLError {
							 *		public int $level;
							 *		public int $code;
							 *		public int $column;
							 *		public string $message;
							 *		public string $file;
							 *		public int $line;
							 *	}
							 */
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'libxml error: ' . $error->message );
							}
							if ( is_admin() ) {
								$this->p->notice->err( 'PHP libXML error: ' . $error->message );
							}
						}

						libxml_clear_errors();		// clear any HTML parsing errors

					} elseif ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'loadHTML was successful' );
					}

					libxml_use_internal_errors( $libxml_prev_state );	// restore previous error handling

				} else {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'libxml_use_internal_errors() function is missing' );
					}

					if ( is_admin() ) {
						$func_name = 'simplexml_load_string()';
						$func_url  = __( 'https://secure.php.net/manual/en/function.simplexml-load-string.php', 'wpsso' );

						$error_msg = sprintf( __( 'The <a href="%1$s">PHP %2$s function</a> is not available.', 'wpsso' ),
							$func_url, '<code>' . $func_name . '</code>' ).' ';

						$error_msg .= __( 'Please contact your hosting provider to have the missing PHP function installed.', 'wpsso' );

						$this->p->notice->err( $error_msg );
					}

					@$doc->loadHTML( $html );
				}
			} else {
				@$doc->loadHTML( $html );
			}

			$xpath = new DOMXPath( $doc );
			$metas = $xpath->query( $query );

			foreach ( $metas as $m ) {
				$m_atts = array();		// put all attributes in a single array
				foreach ( $m->attributes as $a ) {
					$m_atts[$a->name] = $a->value;
				}
				if ( isset( $m->textContent ) ) {
					$m_atts['textContent'] = $m->textContent;
				}
				$ret[$m->tagName][] = $m_atts;
			}

			if ( $this->p->debug->enabled ) {
				if ( empty( $ret ) ) {	// empty array
					if ( false === $request ) {	// $request argument is html
						$this->p->debug->log( 'meta tags found in submitted html' );
					} else {
						$this->p->debug->log( 'no meta tags found in ' . $request );
					}
				} else {
					$this->p->debug->log( 'returning array of ' . count( $ret ) . ' meta tags' );
				}
			}

			return $ret;
		}

		public function missing_php_class_error( $classname ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $classname . ' PHP class is missing' );
			}

			if ( is_admin() ) {
				$this->p->notice->err( sprintf( __( 'The %1$s PHP class is missing - please contact your hosting provider to install the missing %1$s PHP class.',
					'wpsso' ), $classname ) );
			}
		}

		public function get_body_html( $request, $remove_script = true ) {
			$html = '';

			if ( strpos( $request, '//' ) === 0 ) {
				$request = self::get_prot() . ':' . $request;
			}

			if ( strpos( $request, '<' ) === 0 ) {	// check for HTML content
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'using html submitted in the request argument' );
				}
				$html = $request;
			} elseif ( empty( $request ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: request argument is empty' );
				}
				return false;
			} elseif ( strpos( $request, 'data:' ) === 0 ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: request argument is inline data' );
				}
				return false;
			} elseif ( filter_var( $request, FILTER_VALIDATE_URL ) === false ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: request argument is not html or valid url' );
				}
				return false;
			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetching body html for ' . $request );
				}
				if ( ( $html = $this->p->cache->get( $request, 'raw', 'transient' ) ) === false ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: error caching ' . $request );
					}
					return false;
				}
			}

			$html = preg_replace( '/^.*<body[^>]*>(.*)<\/body>.*$/Ums', '$1', $html );

			if ( $remove_script ) {
				$html = preg_replace( '/<script[^>]*>.*<\/script>/Ums', '', $html );
			}

			return $html;
		}

		public function log_is_functions() {

			if ( ! $this->p->debug->enabled ) {	// nothing to do
				return;
			}

			$function_info = $this->get_is_functions();

			foreach ( $function_info as $function => $info ) {
				$this->p->debug->log( $info[0] );
			}
		}

		public function get_is_functions() {

			$function_info = array();

			foreach ( apply_filters( $this->p->lca . '_is_functions', $this->is_functions )  as $function ) {

				if ( function_exists( $function ) ) {

					$start_time   = microtime( true );
					$function_ret = $function();
					$total_time   = microtime( true ) - $start_time;

					$function_info[$function] = array(
						sprintf( '%-40s (%f secs)', $function . '() = ' . ( $function_ret ? 'TRUE' : 'false' ), $total_time ),
						$function_ret,
						$total_time,
					);

				} else {
					$function_info[$function] = array( $function . '() not found', null, 0 );
				}
			}

			return $function_info;
		}

		public static function save_all_times( $ext, $version ) {
			self::save_time( $ext, $version, 'update', $version );	// $protect only if same version.
			self::save_time( $ext, $version, 'install', true );	// $protect is true.
			self::save_time( $ext, $version, 'activate' );		// Always update timestamp.
		}

		/**
		 * $protect = true | false | version
		 */
		public static function save_time( $ext, $version, $type, $protect = false ) {

			if ( ! is_bool( $protect ) ) {
				if ( ! empty( $protect ) ) {
					if ( ( $ts_version = self::get_option_key( WPSSO_TS_NAME, $ext . '_' . $type . '_version' ) ) !== null &&
						version_compare( $ts_version, $protect, '==' ) ) {
						$protect = true;
					} else {
						$protect = false;
					}
				} else {
					$protect = true;	// just in case
				}
			}

			if ( ! empty( $version ) ) {
				self::update_option_key( WPSSO_TS_NAME, $ext . '_' . $type . '_version', $version, $protect );
			}

			self::update_option_key( WPSSO_TS_NAME, $ext . '_' . $type . '_time', time(), $protect );
		}

		/**
		 * Get the timestamp array and perform a quick sanity check.
		 */
		public function get_all_times() {

			$has_changed = false;
			$all_times   = get_option( WPSSO_TS_NAME, array() );

			foreach ( $this->p->cf['plugin'] as $ext => $info ) {

				if ( empty( $info['version'] ) ) {
					continue;
				}

				foreach ( array( 'update', 'install', 'activate' ) as $type ) {

					if ( empty( $all_times[$ext . '_' . $type . '_time'] ) ||
						( $type === 'update' && ( empty( $all_times[$ext . '_' . $type . '_version'] ) ||
							version_compare( $all_times[$ext . '_' . $type . '_version'], $info['version'], '!=' ) ) ) ) {

						$has_changed = self::save_time( $ext, $info['version'], $type );
					}
				}
			}

			return false === $has_changed ? $all_times : get_option( WPSSO_TS_NAME, array() );
		}

		/**
		 * Allow the variables and values array to be extended.
		 * $ext must be an associative array with key/value pairs to be replaced.
		 */
		public function replace_inline_vars( $content, $mod = false, $atts = array(), $extra = array() ) {

			if ( strpos( $content, '%%' ) === false ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: no inline vars' );
				}
				return $content;
			}

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $mod );
			}

			$replace_vars = $this->get_inline_vars();
			$replace_vals = $this->get_inline_vals( $mod, $atts );

			if ( ! empty( $extra ) && self::is_assoc( $extra ) ) {
				foreach ( $extra as $match => $replace ) {
					$replace_vars[] = '%%' . $match . '%%';
					$replace_vals[] = $replace;
				}
			}

			ksort( $replace_vars );
			ksort( $replace_vals );

			return str_replace( $replace_vars, $replace_vals, $content );
		}

		public function get_inline_vars() {
			return array(
				'%%request_url%%',
				'%%sharing_url%%',
				'%%short_url%%',
				'%%sitename%%',
				'%%sitealtname%%',
				'%%sitedesc%%',
			);
		}

		public function get_inline_vals( $mod = false, &$atts = array() ) {

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $mod );
			}

			$add_page = isset( $atts['add_page'] ) ? $atts['add_page'] : true;
			$src_id   = isset( $atts['src_id'] ) ? $atts['src_id'] : '';

			if ( empty( $atts['url'] ) ) {
				$sharing_url = $this->get_sharing_url( $mod, $add_page, $src_id );
			} else {
				$sharing_url = $atts['url'];
			}

			if ( is_admin() ) {
				$request_url = $sharing_url;
			} else {
				$request_url = self::get_prot() . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			}

			if ( empty( $atts['short_url'] ) ) {
				$service_key = $this->p->options['plugin_shortener'];
				$short_url   = apply_filters( $this->p->lca . '_get_short_url', $sharing_url, $service_key, $mod, $mod['name'] );
			} else {
				$short_url = $atts['short_url'];
			}

			$sitename    = SucomUtil::get_site_name( $this->p->options, $mod );
			$sitealtname = SucomUtil::get_site_name_alt( $this->p->options, $mod );
			$sitedesc    = SucomUtil::get_site_description( $this->p->options, $mod );

			return array(
				$request_url,		// %%request_url%%
				$sharing_url,		// %%sharing_url%%
				$short_url,		// %%short_url%%
				$sitename,		// %%sitename%%
				$sitealtname,		// %%sitealtname%%
				$sitedesc,		// %%sitedesc%%
			);
		}

		/**
		 * Accepts a json script or json array.
		 */
		public function json_format( $json, $options = 0, $depth = 32 ) {

			$pretty_print = self::get_const( 'WPSSO_JSON_PRETTY_PRINT', false );
			$ext_json_lib = self::get_const( 'WPSSO_EXT_JSON_DISABLE', false ) ? false : true;
			$ext_json_format = false;

			if ( $options === 0 && defined( 'JSON_UNESCAPED_SLASHES' ) ) {
				$options = JSON_UNESCAPED_SLASHES;	// since PHP v5.4
			}

			/**
			 * Decide if the encoded json will be minimized or not.
			 */
			if ( is_admin() || $this->p->debug->enabled || $pretty_print ) {
				if ( defined( 'JSON_PRETTY_PRINT' ) ) {	// since PHP v5.4
					$options = $options|JSON_PRETTY_PRINT;
				} else {
					$ext_json_format = true;	// use SuextJsonFormat for older PHP
				}
			}

			/**
			 * Encode the json.
			 */
			if ( ! is_string( $json ) ) {
				$json = self::json_encode_array( $json, $options, $depth );	// prefers wp_json_encode() to json_encode()
			}

			/**
			 * Use the pretty print external library for older PHP versions.
			 * Define WPSSO_EXT_JSON_DISABLE as true in wp-config.php to prevent external json formatting.
			 */
			if ( $ext_json_lib && $ext_json_format ) {
				$classname = WpssoConfig::load_lib( false, 'ext/json-format', 'suextjsonformat' );
				if ( $classname !== false && class_exists( $classname ) ) {
					$json = SuextJsonFormat::get( $json, $options, $depth );
				}
			}

			return $json;
		}

		/**
		 * Determine and return the post/user/term module array.
		 */
		public function get_page_mod( $use_post = false, $mod = false, $wp_obj = false ) {

			if ( ! is_array( $mod ) ) {
				$mod = array();
			} elseif ( isset( $mod['obj'] ) && is_object( $mod['obj'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: module object is defined' );
				}
				return $mod;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log( 'use_post is ' . self::get_use_post_string( $use_post ) );
			}

			/**
			 * Check for a recognized object.
			 */
			if ( is_object( $wp_obj ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'wp_obj argument is ' . get_class( $wp_obj ) . ' object' );
				}
				switch ( get_class( $wp_obj ) ) {
					case 'WP_Post':
						$mod['name'] = 'post';
						$mod['id'] = $wp_obj->ID;
						break;
					case 'WP_Term':
						$mod['name'] = 'term';
						$mod['id'] = $wp_obj->term_id;
						break;
					case 'WP_User':
						$mod['name'] = 'user';
						$mod['id'] = $wp_obj->ID;
						break;
				}
			}

			/**
			 * We need a module name to get its id and class object.
			 */
			if ( empty( $mod['name'] ) ) {
				if ( self::is_post_page( $use_post ) ) {	// $use_post = true | false | post_id
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'is_post_page is true' );
					}
					$mod['name'] = 'post';
				} elseif ( self::is_term_page() ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'is_term_page is true' );
					}
					$mod['name'] = 'term';
				} elseif ( self::is_user_page() ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'is_user_page is true' );
					}
					$mod['name'] = 'user';
				} else {
					$mod['name'] = false;
				}
			}

			if ( empty( $mod['id'] ) ) {
				if ( $mod['name'] === 'post' ) {
					$mod['id'] = self::get_post_object( $use_post, 'id' );	// $use_post = true | false | post_id
				} elseif ( $mod['name'] === 'term' ) {
					$mod['id'] = self::get_term_object( false, '', 'id' );
				} elseif ( $mod['name'] === 'user' ) {
					$mod['id'] = self::get_user_object( false, 'id' );
				} else {
					$mod['id'] = false;
				}
			}

			if ( isset( $this->p->m['util'][$mod['name']] ) ) {	// make sure we have a complete $mod array
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting $mod array from ' . $mod['name'] . ' module object' );
				}
				$mod = $this->p->m['util'][$mod['name']]->get_mod( $mod['id'] );
			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'object is unknown - merging $mod defaults' );
				}
				$mod = array_merge( WpssoMeta::$mod_defaults, $mod );
			}

			$mod['use_post'] = $use_post;

			/**
			 * The post module defines is_home_page, is_home_index, and is_home.
			 * If we don't have a module, then check if we're on the home index page.
			 */
			if ( $mod['name'] === false ) {
				$mod['is_home_index'] = $mod['is_home'] = is_home();
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_arr( 'mod', $mod );
			}

			return $mod;
		}

		/**
		 * $mod is false when used for open graph meta tags and buttons in widget.
		 * $mod is true when buttons are added to individual posts on an index webpage.
		 */
		public function get_sharing_url( $mod = false, $add_page = true, $src_id = '' ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
			return $this->get_page_url( 'sharing', $mod, $add_page, $src_id );
		}

		public function get_canonical_url( $mod = false, $add_page = true, $src_id = '' ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
			return $this->get_page_url( 'canonical', $mod, $add_page, $src_id );
		}

		private function get_page_url( $type, $mod, $add_page, $src_id ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'type'     => $type,
					'mod'      => $mod,
					'add_page' => $add_page,
					'src_id'   => $src_id,
				) );
			}

			$url = false;

			/**
			 * The $mod array argument is preferred but not required.
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $mod );
			}

			if ( $mod['is_post'] ) {

				if ( ! empty( $mod['id'] ) ) {

					if ( ! empty( $mod['obj'] ) ) {
						$url = $mod['obj']->get_options( $mod['id'], $type . '_url' );	// returns null if an index key is not found
					}

					if ( ! empty( $url ) ) {	// must be a non-empty string
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'custom post ' . $type . '_url = ' . $url );
						}
					} else {
						$url = get_permalink( $mod['id'] );
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'get_permalink url = ' . $url );
						}
						$url = $this->check_url_string( $url, 'post permalink' );
					}

					if ( ! empty( $url ) && $add_page && get_query_var( 'page' ) > 1 ) {
						global $wp_rewrite;
						$post_obj = self::get_post_object( $mod['id'] );
						$numpages = substr_count( $post_obj->post_content, '<!--nextpage-->' ) + 1;

						if ( $numpages && get_query_var( 'page' ) <= $numpages ) {
							if ( ! $wp_rewrite->using_permalinks() || strpos( $url, '?' ) !== false ) {
								$url = add_query_arg( 'page', get_query_var( 'page' ), $url );
							} else {
								$url = user_trailingslashit( trailingslashit( $url ) . get_query_var( 'page' ) );
							}
						}
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'add page query url = ' . $url );
						}
					}
				}

				$url = apply_filters( $this->p->lca . '_post_url', $url, $mod, $add_page, $src_id );

			} else {

				if ( $mod['is_home'] ) {

					if ( get_option( 'show_on_front' ) === 'page' ) {	// show_on_front = posts | page
						$url = $this->check_url_string( get_permalink( get_option( 'page_for_posts' ) ), 'page for posts' );
					} else {
						$url = apply_filters( $this->p->lca . '_home_url', home_url( '/' ), $mod, $add_page, $src_id );

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'home url = ' . $url );
						}
					}

				} elseif ( $mod['is_term'] ) {

					if ( ! empty( $mod['id'] ) ) {

						if ( ! empty( $mod['obj'] ) ) {
							$url = $mod['obj']->get_options( $mod['id'], $type . '_url' );	// returns null if an index key is not found
						}

						if ( ! empty( $url ) ) {	// must be a non-empty string
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'custom term ' . $type . '_url = ' . $url );
							}
						} else {
							$url = $this->check_url_string( get_term_link( $mod['id'], $mod['tax_slug'] ), 'term link' );
						}
					}

					$url = apply_filters( $this->p->lca . '_term_url', $url, $mod, $add_page, $src_id );

				} elseif ( $mod['is_user'] ) {

					if ( ! empty( $mod['id'] ) ) {

						if ( ! empty( $mod['obj'] ) ) {
							$url = $mod['obj']->get_options( $mod['id'], $type . '_url' );	// returns null if an index key is not found
						}

						if ( ! empty( $url ) ) {	// must be a non-empty string
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'custom user ' . $type . '_url = ' . $url );
							}
						} else {
							$url = $this->check_url_string( get_author_posts_url( $mod['id'] ), 'author posts' );
						}
					}

					$url = apply_filters( $this->p->lca . '_user_url', $url, $mod, $add_page, $src_id );

				} elseif ( is_search() ) {

					$url = $this->check_url_string( get_search_link(), 'search link' );
					$url = apply_filters( $this->p->lca . '_search_url', $url, $mod, $add_page, $src_id );

				} elseif ( function_exists( 'get_post_type_archive_link' ) && is_post_type_archive() ) {

					$url = $this->check_url_string( get_post_type_archive_link( get_query_var( 'post_type' ) ), 'post type archive' );

				} elseif ( SucomUtil::is_archive_page() ) {

					if ( is_date() ) {

						if ( is_day() ) {
							$url = $this->check_url_string( get_day_link( get_query_var( 'year' ),
								get_query_var( 'monthnum' ), get_query_var( 'day' ) ), 'day link' );
						} elseif ( is_month() ) {
							$url = $this->check_url_string( get_month_link( get_query_var( 'year' ),
								get_query_var( 'monthnum' ) ), 'month link' );
						} elseif ( is_year() ) {
							$url = $this->check_url_string( get_year_link( get_query_var( 'year' ) ), 'year link' );
						}
					}

					$url = apply_filters( $this->p->lca . '_archive_page_url', $url, $mod, $add_page, $src_id );
				}

				$url = $this->get_url_paged( $url, $mod, $add_page );
			}

			/**
			 * Use the current URL as a fallback for themes and plugins that create public content and
			 * don't use the standard WordPress functions / variables and/or are not properly integrated
			 * with WordPress (don't use custom post types, taxonomies, terms, etc.).
			 */
			if ( empty ( $url ) ) {

				$url = self::get_prot() . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'server request url = ' . $url );
				}

				/**
				 * Strip out tracking query arguments by facebook, google, etc.
				 */
				$url = preg_replace( '/([\?&])(fb_action_ids|fb_action_types|fb_source|fb_aggregation_id|' . 
					'utm_source|utm_medium|utm_campaign|utm_term|gclid|pk_campaign|pk_kwd)=[^&]*&?/i', '$1', $url );

				$url = apply_filters( $this->p->lca . '_server_request_url', $url, $mod, $add_page, $src_id );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'server request url (filtered) = ' . $url );
				}

				/**
				 * Maybe disable transient cache and URL shortening.
				 */
				if ( $src_id === 'head_sharing_url' && strpos( $url, '?' ) !== false ) {
					$disable_cache = true;
				} else {
					$disable_cache = false;
				}

				if ( apply_filters( $this->p->lca . '_server_request_url_disable_cache', $disable_cache, $url, $mod, $add_page, $src_id ) ) {
					$this->disable_cache_filters( array(
						'shorten_url' => '__return_false',
					) );
				}
			}

			/**
			 * Check and possibly enforce the FORCE_SSL constant.
			 */
			if ( ! empty( $this->p->options['plugin_honor_force_ssl'] ) ) {
				if ( SucomUtil::get_const( 'FORCE_SSL' ) && strpos( $url, 'http:' ) === 0 ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'force ssl is enabled - replacing http by https' );
					}
					$url = preg_replace( '/^http:/', 'https:', $url );
				}
			}

			return apply_filters( $this->p->lca . '_' . $type . '_url', $url, $mod, $add_page, $src_id );
		}

		private function get_url_paged( $url, $mod, $add_page ) {

			if ( empty( $url ) || empty( $add_page ) ) {
				return $url;
			}

			global $wpsso_paged;
			if ( is_numeric( $add_page ) ) {
				$paged = $add_page;
			} elseif ( is_numeric( $wpsso_paged ) ) {
				$paged = $wpsso_paged;
			} else {
				$paged = get_query_var( 'paged' );
			}

			if ( $paged > 1 ) {
				global $wp_rewrite;
				if ( ! $wp_rewrite->using_permalinks() ) {
					$url = add_query_arg( 'paged', $paged, $url );
				} else {
					if ( $mod['is_home_page'] ) {	// static home page (have post id)
						$base = $wp_rewrite->using_index_permalinks() ? 'index.php/' : '/';
						$url = home_url( $base );
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'home_url for ' . $base . ' = ' . $url );
						}
					}
					$url = user_trailingslashit( trailingslashit( $url ) . 
						trailingslashit( $wp_rewrite->pagination_base ) . $paged );
				}
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'get url paged = ' . $url );
				}
			}

			return $url;
		}

		private function check_url_string( $url, $context ) {
			if ( is_string( $url ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $context . ' url = ' . $url );
				}
				return $url;	// stop here
			}
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $context . ' url is ' . gettype( $url ) );
				if ( is_wp_error( $url ) ) {
					$this->p->debug->log( $context . ' url error: ' . $url->get_error_message() );
				}
			}
			return false;
		}

		/**
		 * Used by WpssoMedia get_content_images() and get_attachment_image_src().
		 */
		public function fix_relative_url( $url ) {

			if ( empty( $url ) || strpos( $url, '://' ) !== false ) {
				return $url;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'relative url found = ' . $url );
			}

			if ( strpos( $url, '//' ) === 0 ) {

				$url = self::get_prot() . ':' . $url;

			} elseif ( strpos( $url, '/' ) === 0 )  {

				$url = home_url( $url );

			} else {
				$base = self::get_prot() . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

				if ( strpos( $base, '?' ) !== false ) {
					$base_parts = explode( '?', $base );
					$base = reset( $base_parts );
				}

				$url = trailingslashit( $base, false ) . $url;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'relative url fixed = ' . $url );
			}

			return $url;
		}

		public function clear_uniq_urls( $mixed = 'default' ) {
			if ( ! is_array( $mixed ) ) {
				$mixed = array( $mixed );
			}
			$cleared = 0;
			foreach ( $mixed as $context ) {
				if ( isset( $this->uniq_urls[$context] ) ) {
					$cleared += count( $this->uniq_urls[$context] );
				}
				$this->uniq_urls[$context] = array();
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'cleared uniq url cache for context '.$context );
				}
			}
			return $cleared;
		}

		public function is_dupe_url( $url, $context = 'default' ) {
			return $this->is_uniq_url( $url, $context ) ? false : true;
		}

		public function is_uniq_url( $url, $context = 'default' ) {

			if ( empty( $url ) ) {
				return false;
			}

			/**
			 * Complete the url with a protocol name.
			 */
			if ( strpos( $url, '//' ) === 0 ) {
				$url = self::get_prot().'//'.$url;
			}

			if ( $this->p->debug->enabled && strpos( $url, '://' ) === false ) {
				$this->p->debug->log( 'incomplete url given for context '.$context.': '.$url );
			}

			if ( ! isset( $this->uniq_urls[$context][$url] ) ) {
				$this->uniq_urls[$context][$url] = 1;
				return true;
			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'duplicate url rejected for context '.$context.': '.$url );
				}
				return false;
			}
		}

		public function is_maxed( &$arr, $num = 0 ) {

			if ( ! is_array( $arr ) ) {
				return false;
			}

			if ( $num > 0 && count( $arr ) >= $num ) {
				return true;
			}

			return false;
		}

		public function push_max( &$dst, &$src, $num = 0 ) {

			if ( ! is_array( $dst ) || ! is_array( $src ) ) {
				return false;
			}

			/**
			 * If the array is not empty, or contains some non-empty values, then push it.
			 */
			if ( ! empty( $src ) && array_filter( $src ) ) {
				array_push( $dst, $src );
			}

			return $this->slice_max( $dst, $num );	// returns true or false
		}

		public function slice_max( &$arr, $num = 0 ) {

			if ( ! is_array( $arr ) ) {
				return false;
			}

			$has = count( $arr );

			if ( $num > 0 ) {
				if ( $has == $num ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'max values reached ('.$has.' == '.$num.')' );
					}
					return true;
				} elseif ( $has > $num ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'max values reached ('.$has.' > '.$num.') - slicing array' );
					}
					$arr = array_slice( $arr, 0, $num );
					return true;
				}
			}

			return false;
		}

		/**
		 * Get maximum media values from custom meta or plugin settings.
		 */
		public function get_max_nums( array &$mod, $opt_pre = 'og' ) {

			$max_nums = array();
			$opt_keys = array( $opt_pre.'_vid_max', $opt_pre.'_img_max' );

			foreach ( $opt_keys as $max_key ) {

				if ( ! empty( $mod['id'] ) && ! empty( $mod['obj'] ) ) {
					$max_val = $mod['obj']->get_options( $mod['id'], $max_key );	// returns null if an index key is not found
				} else {
					$max_val = null;	// default value if index key is missing
				}

				/**
				 * Quick sanitation of returned value.
				 */
				if ( $max_val !== null & is_numeric( $max_val ) && $max_val >= 0 ) {

					$max_nums[$max_key] = $max_val;

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'found custom meta '.$max_key.' = '.$max_val );
					}

				} else {

					$max_nums[$max_key] = isset( $this->p->options[$max_key] ) ?	// fallback to options
						$this->p->options[$max_key] : 0;
				}
			}

			return $max_nums;
		}

		public function safe_apply_filters( array $args, array $mod, $max_time = 0, $hook_bfo = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Check for required apply_filters() arguments.
			 */
			if ( empty( $args[0] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: filter name missing from parameter array' );
				}
				return '';
			} elseif ( ! isset( $args[1] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: filter value missing from parameter array' );
				}
				return '';
			}

			$filter_name = $args[0];
			$filter_value = $args[1];

			if ( ! has_filter( $filter_name ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: ' . $filter_name . ' has no filter hooks' );
				}
				return $filter_value;
			}

			/**
			 * Prevent recursive loops - the global variable is defined before applying the filters.
			 */
			if ( ! empty( $GLOBALS[$this->p->lca . '_doing_filter_' . $filter_name] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: global variable ' . 
						$this->p->lca . '_doing_filter_' . $filter_name . ' is true' );
				}
				return $filter_value;
			}

			/**
			 * Hooked by some modules, like bbPress and social sharing buttons,
			 * to perform actions before/after filtering the content.
			 */
			do_action( $this->p->lca . '_pre_apply_filters_text', $filter_name );

			/**
			 * Load the Block Filter Output (BFO) filters to block and show an error
			 * for incorrectly coded filters.
			 */
			if ( $hook_bfo ) {

				$classname = apply_filters( $this->p->lca . '_load_lib', false, 'com/bfo', 'SucomBFO' );

				if ( is_string( $classname ) && class_exists( $classname ) ) {
					$bfo_obj = new $classname( $this->p );
					$bfo_obj->add_start_hooks( array( $filter_name ) );
				}
			}

			/**
			 * Save the original post object, in case some filters modify the global $post.
			 */
			global $post, $wp_query;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'saving the original post object ' . ( isset( $post->ID ) ? 'id ' . $post->ID : '(no post id)' ) );
			}

			$post_obj_pre_filter = $post;		// Save the original global post object.
			$wp_query_pre_filter = $wp_query;	// Save the original global wp_query.

			/**
			 * Make sure the $post object is correct before filtering.
			 */
			if ( $mod['is_post'] && $mod['id'] && ( ! isset( $post->ID ) || $mod['id'] !== $post->ID ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'resetting post object from mod id ' . $mod['id'] );
				}

				$post = SucomUtil::get_post_object( $mod['id'] );	// Redefine $post global.

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'post object id matches the post mod id' );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'setting post data for template functions' );
			}

			setup_postdata( $post );

			/**
			 * Prevent recursive loops and signal to other methods that the content filter is being
			 * applied to create a description text - this avoids the addition of unnecessary HTML
			 * which will be removed anyway (social sharing buttons, for example).
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'setting global ' . $this->p->lca . '_doing_filter_' . $filter_name );
			}

			$GLOBALS[$this->p->lca . '_doing_filter_' . $filter_name] = true;	// prevent recursive loops

			/**
			 * Apply the filters.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'applying WordPress ' . $filter_name . ' filters' );	// Begin timer.
			}

			$start_time   = microtime( true );
			$filter_value = call_user_func_array( 'apply_filters', $args );
			$total_time   = microtime( true ) - $start_time;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'applying WordPress ' . $filter_name . ' filters' );	// End timer.
			}

			/**
			 * Unset the recursive loop check.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'unsetting global ' . $this->p->lca . '_doing_filter_' . $filter_name );
			}

			unset( $GLOBALS[$this->p->lca . '_doing_filter_' . $filter_name] );	// un-prevent recursive loops

			/**
			 * Issue warning for slow filter performance.
			 */
			if ( $max_time > 0 && $total_time > $max_time ) {

				switch ( $filter_name ) {
					case 'get_the_excerpt':
					case 'the_content':
					case 'the_excerpt':
					case 'wp_title':
						$is_wp_filter = true;
						break;
					default:
						$is_wp_filter = false;
						break;
				}

				$info = $this->p->cf['plugin'][$this->p->lca];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( sprintf( 'slow filter hook(s) detected - WordPress took %1$0.3f secs to execute the "%2$s" filter',
						$total_time, $filter_name ) );
				}

				// translators: %1$0.3f is a number of seconds
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $max_time );

				// translators: %1$0.3f is a number of seconds, %2$s is a filter name, %3$s is a recommended max
				$error_msg = sprintf( __( 'Slow filter hook(s) detected - WordPress took %1$0.3f secs to execute the "%2$s" filter (%3$s).',
					'wpsso' ), $total_time, $filter_name, $rec_max_msg );

				/**
				 * Show an admin warning notice, if notices not already shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {
					if ( $is_wp_filter ) {
						$filter_api_link    = '<a href="https://codex.wordpress.org/Plugin_API/Filter_Reference/' .
							$filter_name . '">' . $filter_name . '</a>';
						$query_monitor_link = '<a href="https://wordpress.org/plugins/query-monitor/">Query Monitor</a>';
						$dismiss_key        = 'slow-filter-hooks-detected-' . $filter_name;

						$this->p->notice->warn( sprintf( __( 'Slow filter hook(s) detected &mdash; the WordPress %1$s filter took %2$0.3f seconds to execute. This is longer than the recommended maximum of %3$0.3f seconds and may affect page load time. Please consider reviewing 3rd party plugin and theme functions hooked into the WordPress %1$s filter for slow and/or sub-optimal PHP code.', 'wpsso' ), $filter_api_link, $total_time, $max_time ) . ' ' . sprintf( __( 'Activating the %1$s plugin and clearing the %2$s cache (to re-apply the filter) may provide more information on the specific hook(s) or PHP code affecting performance.', 'wpsso' ), $query_monitor_link, $info['short'] ), true, $dismiss_key, WEEK_IN_SECONDS );
					} else {
						$this->p->notice->warn( $error_msg );
					}
				}

				// translators: %s is the short plugin name
				$error_pre = sprintf( __( '%s warning:', 'wpsso' ), $info['short'] );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			/**
			 * Restore the original post object.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'restoring the original post object ' . 
					( isset( $post_obj_pre_filter->ID ) ? 'id ' . $post_obj_pre_filter->ID : '(no post id)' ) );
			}

			$post     = $post_obj_pre_filter;	// Restore the original global post object.
			$wp_query = $wp_query_pre_filter;	// Restore the original global wp_query.

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'restoring post data for template functions' );
			}

			setup_postdata( $post );

			/**
			 * Remove the Block Filter Output (BFO) filters.
			 */
			if ( $hook_bfo ) {
				$bfo_obj->remove_all_hooks( array( $filter_name ) );
			}

			/**
			 * Hooked by some modules, like bbPress and social sharing buttons,
			 * to perform actions before/after filtering the content.
			 */
			do_action( $this->p->lca . '_after_apply_filters_text', $filter_name );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'returning filtered value' );
			}

			return $filter_value;
		}

		public function get_admin_url( $menu_id = '', $link_text = '', $menu_lib = '' ) {

			$hash = '';
			$query = '';
			$admin_url = '';

			/**
			 * $menu_id may start with a hash or query, so parse before checking its value.
			 */
			if ( strpos( $menu_id, '#' ) !== false ) {
				list( $menu_id, $hash ) = explode( '#', $menu_id );
			}

			if ( strpos( $menu_id, '?' ) !== false ) {
				list( $menu_id, $query ) = explode( '?', $menu_id );
			}

			if ( empty( $menu_id ) ) {
				$current = $_SERVER['REQUEST_URI'];
				if ( preg_match( '/^.*\?page='.$this->p->lca.'-([^&]*).*$/', $current, $match ) ) {
					$menu_id = $match[1];
				} else {
					$menu_id = key( $this->p->cf['*']['lib']['submenu'] );	// default to first submenu
				}
			}

			/**
			 * Find the menu_lib value for this menu_id.
			 */
			if ( empty( $menu_lib ) ) {
				foreach ( $this->p->cf['*']['lib'] as $menu_lib => $menu ) {
					if ( isset( $menu[$menu_id] ) ) {
						break;
					} else {
						$menu_lib = '';
					}
				}
			}

			if ( empty( $menu_lib ) || empty( $this->p->cf['wp']['admin'][$menu_lib]['page'] ) ) {
				return;
			}

			$parent_slug = $this->p->cf['wp']['admin'][$menu_lib]['page'].'?page='.$this->p->lca.'-'.$menu_id;

			switch ( $menu_lib ) {
				case 'sitesubmenu':
					$admin_url = network_admin_url( $parent_slug );
					break;
				default:
					$admin_url = admin_url( $parent_slug );
					break;
			}

			if ( ! empty( $query ) ) {
				$admin_url .= '&'.$query;
			}

			if ( ! empty( $hash ) ) {
				$admin_url .= '#'.$hash;
			}

			if ( empty( $link_text ) ) {
				return $admin_url;
			} else {
				return '<a href="'.$admin_url.'">'.$link_text.'</a>';
			}
		}

		/**
		 * Deprecated on 2018/03/31.
		 */
		public function do_metabox_tabs( $metabox_id = '', $tabs = array(), $table_rows = array(), $args = array() ) {
			echo $this->get_metabox_tabbed( $metabox_id, $tabs, $table_rows, $args );
		}

		public function do_metabox_tabbed( $metabox_id = '', $tabs = array(), $table_rows = array(), $args = array() ) {
			echo $this->get_metabox_tabbed( $metabox_id, $tabs, $table_rows, $args );
		}

		public function get_metabox_tabbed( $metabox_id = '', $tabs = array(), $table_rows = array(), $args = array() ) {

			$ret_html = '';
			$tab_keys = array_keys( $tabs );
			$default_tab = '_' . reset( $tab_keys );		// must start with an underscore
			$class_metabox_tabs = 'sucom-metabox-tabs';
			$class_link = 'sucom-tablink';
			$class_tabset = 'sucom-tabset';

			if ( ! empty( $metabox_id ) ) {
				$metabox_id = '_' . $metabox_id;		// must start with an underscore
				$class_metabox_tabs .= ' ' . $class_metabox_tabs . $metabox_id;
				$class_link .= ' ' . $class_link . $metabox_id;
			}

			/**
			 * Allow a css id to be passed as a query argument.
			 */
			extract( array_merge( array( 'scroll_to' => isset( $_GET['scroll_to'] ) ? '#' . self::sanitize_key( $_GET['scroll_to'] ) : '' ), $args ) );

			$ret_html .= "\n" . '<script type="text/javascript">jQuery(document).ready(function(){ ' . 
				'sucomTabs(\'' . $metabox_id . '\', \'' . $default_tab . '\', \'' . $scroll_to . '\'); });</script>' . "\n";

			$ret_html .= '<div class="' . $class_metabox_tabs . '">' . "\n";

			$ret_html .= '<ul class="' . $class_metabox_tabs . '">' . "\n";

			/**
			 * Add the settings tab list.
			 */
			$tab_num = 0;

			foreach ( $tabs as $tab => $title ) {

				$tab_num++;

				$class_href_key = $class_tabset . $metabox_id . '-tab_' . $tab;

				$ret_html .= '<div class="tab_space' . ( $tab_num === 1 ? ' first_tab' : '' ) . '">&nbsp;</div>' .
					'<li class="' . $class_href_key . '"><a class="' . $class_link . '" href="#' . $class_href_key . '">' .
						$title . '</a></li>';	// Do not add newline.
			}

			$ret_html .= '</ul><!-- .' . $class_metabox_tabs . ' -->' . "\n";

			/**
			 * Add the settings table for each tab.
			 */
			foreach ( $tabs as $tab => $title ) {
				$class_href_key = $class_tabset . $metabox_id . '-tab_' . $tab;
				$ret_html .= $this->get_metabox_table( $table_rows[$tab], $class_href_key, 
					( empty( $metabox_id ) ? '' : $class_tabset . $metabox_id ), $class_tabset );
			}

			$ret_html .= '</div><!-- .' . $class_metabox_tabs . ' -->' . "\n\n";

			return $ret_html;
		}

		/**
		 * Deprecated on 2018/03/31.
		 */
		public function do_table_rows( $table_rows, $class_href_key = '', $class_tabset_mb = '', $class_tabset = 'sucom-no_tabset' ) {
			echo $this->get_metabox_table( $table_rows, $class_href_key, $class_tabset_mb, $class_tabset );
		}

		public function do_metabox_table( $table_rows, $class_href_key = '', $class_tabset_mb = '', $class_tabset = 'sucom-no_tabset' ) {
			echo $this->get_metabox_table( $table_rows, $class_href_key, $class_tabset_mb, $class_tabset );
		}

		public function get_metabox_table( $table_rows, $class_href_key = '', $class_tabset_mb = '', $class_tabset = 'sucom-no_tabset' ) {

			$ret_html = '';

			if ( ! is_array( $table_rows ) ) {	// just in case
				return $ret_html;
			}

			$total_rows = count( $table_rows );
			$count_rows = 0;
			$hidden_opts = 0;
			$hidden_rows = 0;

			/**
			 * Use call_user_func() instead of $classname::show_opts() for PHP 5.2.
			 */
			$show_opts = class_exists( $this->p->lca.'user' ) ? call_user_func( array( $this->p->lca.'user', 'show_opts' ) ) : 'basic';

			foreach ( $table_rows as $key => $row ) {

				if ( empty( $row ) ) {	// just in case
					continue;
				}

				/**
				 * Default row class and id attribute values.
				 */
				$tr = array(
					'class' => 'sucom_alt'.( $count_rows % 2 ).
						( $count_rows === 0 ? ' first_row' : '' ).
						( $count_rows === ( $total_rows - 1 ) ? ' last_row' : '' ),
					'id' => ( is_int( $key ) ? '' : 'tr_'.$key )
				);

				/**
				 * If we don't already have a table row tag, then add one.
				 */
				if ( strpos( $row, '<tr ' ) === false ) {
					$row = '<tr class="'.$tr['class'].'"'.( empty( $tr['id'] ) ? '' : ' id="'.$tr['id'].'"' ).'>'.$row;
				} else {
					foreach ( $tr as $att => $val ) {

						if ( empty( $tr[$att] ) ) {
							continue;
						}

						/**
						 * If we're here, then we have a table row tag already.
						 * Count the number of rows and options that are hidden.
						 */
						if ( $att === 'class' && ! empty( $show_opts ) &&
							( $matched = preg_match( '/<tr [^>]*class="[^"]*hide(_row)?_in_'.$show_opts.'[" ]/', $row, $m ) > 0 ) ) {

							if ( ! isset( $m[1] ) ) {
								$hidden_opts += preg_match_all( '/(<th|<tr[^>]*><td)/', $row, $all_matches );
							}

							$hidden_rows += $matched;
						}

						/**
						 * Add the attribute value.
						 */
						$row = preg_replace( '/(<tr [^>]*'.$att.'=")([^"]*)(")/', '$1$2 '.$tr[$att].'$3', $row, -1, $cnt );

						/**
						 * If one hasn't been added, then add both the attribute and its value.
						 */
						if ( $cnt < 1 ) {
							$row = preg_replace( '/(<tr )/', '$1'.$att.'="'.$tr[$att].'" ', $row, -1, $cnt );
						}
					}
				}

				/**
				 * Add a closing table row tag if we don't already have one.
				 */
				if ( strpos( $row, '</tr>' ) === false ) {
					$row .= '</tr>' . "\n";
				}

				/**
				 * Update the table row array element with the new value.
				 */
				$table_rows[$key] = $row;

				$count_rows++;
			}

			if ( $count_rows === 0 ) {
				$table_rows[] = '<tr><td align="center"><p><em>' . __( 'No options available.', 'wpsso' ) . '</em></p></td></tr>';
				$count_rows++;
			}

			$ret_html .= '<div class="'.
				( empty( $show_opts ) ? '' : 'sucom-show_'.$show_opts ).
				( empty( $class_tabset ) ? '' : ' '.$class_tabset ).
				( empty( $class_tabset_mb ) ? '' : ' '.$class_tabset_mb ).
				( empty( $class_href_key ) ? '' : ' '.$class_href_key ).'">' . "\n";

			$ret_html .= '<table class="sucom-settings '.$this->p->lca.
				( empty( $class_href_key ) ? '' : ' '.$class_href_key ).
				( $hidden_rows > 0 && $hidden_rows === $count_rows ?	// if all rows hidden, then hide the whole table
					' hide_in_'.$show_opts : '' ).'">' . "\n";

			foreach ( $table_rows as $row ) {
				$ret_html .= $row;
			}

			$ret_html .= '</table>' . "\n";
			$ret_html .= '</div>' . "\n";

			$show_opts_label = $this->p->cf['form']['show_options'][$show_opts];

			if ( $hidden_opts > 0 ) {

				$ret_html .= '<div class="hidden_opts_msg ' . $class_tabset . '-msg ' . $class_tabset_mb . '-msg ' . $class_href_key . '-msg">' .
					sprintf( _x( '%1$d additional options not shown in "%2$s" view', 'option comment', 'wpsso' ), $hidden_opts,
						_x( $show_opts_label, 'option value', 'wpsso' ) ) .
					' (<a href="javascript:void(0);" onClick="sucomViewUnhideRows( \'' . $class_href_key . '\', \'' . $show_opts . '\' );">' .
						_x( 'unhide these options', 'option comment', 'wpsso' ) . '</a>)</div>' . "\n";

			} elseif ( $hidden_rows > 0 ) {

				$ret_html .= '<div class="hidden_opts_msg ' . $class_tabset . '-msg ' . $class_tabset_mb . '-msg ' . $class_href_key . '-msg">' .
					sprintf( _x( '%1$d additional rows not shown in "%2$s" view', 'option comment', 'wpsso' ), $hidden_rows,
						_x( $show_opts_label, 'option value', 'wpsso' ) ) .
					' (<a href="javascript:void(0);" onClick="sucomViewUnhideRows( \'' . $class_href_key . '\', \'' . $show_opts . '\', \'hide_row_in\' );">' .
						_x( 'unhide these rows', 'option comment', 'wpsso' ) . '</a>)</div>' . "\n";
			}

			return $ret_html;
		}

		/**
		 * Rename settings array keys, preserving the option modifiers (:is|:use|#.*|_[0-9]+).
		 */
		public function rename_opts_by_ext( &$opts, $options_keys ) {
			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( ! isset( $options_keys[$ext] ) || ! is_array( $options_keys[$ext] ) ||
					! isset( $info['opt_version'] ) || empty( $opts['plugin_'.$ext.'_opt_version'] ) ) {
					continue;
				}
				foreach ( $options_keys[$ext] as $max_version => $keys ) {
					if ( is_numeric( $max_version ) && is_array( $keys ) && $opts['plugin_'.$ext.'_opt_version'] <= $max_version ) {
						SucomUtil::rename_keys( $opts, $keys, true );	// rename $modifiers = true
						$opts['plugin_'.$ext.'_opt_version'] = $info['opt_version'];	// mark as current
					}
				}
			}
			$opts['options_version'] = $this->p->cf['opt']['version'];	// mark as current
		}

		/**
		 * limit_text_length() uses PHP's multibyte functions (mb_strlen and mb_substr) for UTF8.
		 */
		public function limit_text_length( $text, $maxlen = 300, $trailing = '', $cleanup_html = true ) {

			if ( true === $cleanup_html ) {
				$text = $this->cleanup_html_tags( $text );				// remove any remaining html tags
			}

			$charset = get_bloginfo( 'charset' );
			$text = html_entity_decode( self::decode_utf8( $text ), ENT_QUOTES, $charset );

			if ( $maxlen > 0 ) {
				if ( mb_strlen( $trailing ) > $maxlen ) {
					$trailing = mb_substr( $trailing, 0, $maxlen );			// trim the trailing string, if too long
				}
				if ( mb_strlen( $text ) > $maxlen ) {
					$text = mb_substr( $text, 0, $maxlen - mb_strlen( $trailing ) );
					$text = trim( preg_replace( '/[^ ]*$/', '', $text ) );		// remove trailing bits of words
					$text = preg_replace( '/[,\.]*$/', '', $text );			// remove trailing puntuation
				} else {
					$trailing = '';							// truncate trailing string if text is less than maxlen
				}
				$text = $text.$trailing;						// trim and add trailing string (if provided)
			}

			$text = preg_replace( '/&nbsp;/', ' ', $text);					// just in case

			return $text;
		}

		public function cleanup_html_tags( $text, $strip_tags = true, $use_img_alt = false ) {

			$alt_text = '';
			$alt_prefix = isset( $this->p->options['plugin_img_alt_prefix'] ) ?
				$this->p->options['plugin_img_alt_prefix'] : 'Image:';

			$text = SucomUtil::strip_shortcodes( $text );					// Remove any remaining shortcodes.
			$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );				// Put everything on one line.
			$text = preg_replace( '/<\?.*\?'.'>/U', ' ', $text );				// Remove php.
			$text = preg_replace( '/<script\b[^>]*>(.*)<\/script>/Ui', ' ', $text );	// Remove javascript.
			$text = preg_replace( '/<style\b[^>]*>(.*)<\/style>/Ui', ' ', $text );		// Remove inline stylesheets.

			$text = preg_replace( '/<!--'.$this->p->lca.'-ignore-->(.*?)' .
				'<!--\/'.$this->p->lca.'-ignore-->/Ui', ' ', $text );			// Remove text between comment strings.

			if ( $strip_tags ) {

				$text = preg_replace( '/<\/p>/i', ' ', $text);				// Replace end of paragraph with a space.
				$text_stripped = trim( strip_tags( $text ) );				// Remove remaining html tags.

				if ( $text_stripped === '' && $use_img_alt ) {				// Possibly use img alt strings if no text.

					if ( strpos( $text, '<img ' ) !== false &&
						preg_match_all( '/<img [^>]*alt=["\']([^"\'>]*)["\']/Ui',
							$text, $all_matches, PREG_PATTERN_ORDER ) ) {

						foreach ( $all_matches[1] as $alt ) {

							$alt = trim( $alt );

							if ( ! empty( $alt ) ) {
							
								$alt = empty( $alt_prefix ) ? $alt : $alt_prefix.' '.$alt;

								/**
								 * Add a period after the image alt text if missing.
								 */
								$alt_text .= ( strpos( $alt, '.' ) + 1 ) === strlen( $alt ) ? $alt.' ' : $alt.'. ';
							}
						}

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'img alt text: '.$alt_text );
						}
					}

					$text = $alt_text;

				} else {
					$text = $text_stripped;
				}
			}

			$text = preg_replace( '/(\xC2\xA0|\s)+/s', ' ', $text );	// replace 1+ spaces to a single space

			return trim( $text );
		}

		/**
		 * Deprecated on 2018/05/08.
		 *
		 * Check that all add-ons are no longer using this method before removing it.
		 */
		public function get_ext_req_msg( $mixed ) {
			return $this->p->admin->get_ext_required_msg( $mixed );
		}

		public function get_robots_content( array $mod ) {

			$content = '';

			if ( $mod['id'] && is_object( $mod['obj'] ) ) {

				foreach ( array(
					'noindex' => 'index',
					'nofollow' => 'follow',
					'noarchive' => '',
					'nosnippet' => '',
				) as $meta_name => $inverse_name ) {

					$meta_key   = '_' . $this->p->lca . '_' . $meta_name;
					$meta_value = $mod['obj']->get_meta_cache_value( $mod['id'], $meta_key );

					if ( ! empty( $meta_value ) ) {
						$content .= $meta_name . ', ';
					} elseif ( ! empty( $inverse_name ) ) {
						$content .= $inverse_name . ', ';
					}
				}
			}

			return apply_filters( $this->p->lca . '_get_robots_content', rtrim( $content, ', ' ), $mod );
		}
	}
}
