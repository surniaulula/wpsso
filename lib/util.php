<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'SucomUtil' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/com/util.php';
}

if ( ! class_exists( 'WpssoUtil' ) ) {

	class WpssoUtil extends SucomUtil {

		private $p;		// Wpsso.

		private $cache_uniq_urls   = array();	// Array to detect duplicate images, etc.
		private $cache_size_labels = array();	// Array for image size labels.
		private $cache_size_opts   = array();	// Array for image size option prefix.

		private $is_functions = array(
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
			 * Common e-commerce / woocommerce functions.
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
			 * Other functions.
			 */
			'is_amp_endpoint',
			'wp_using_ext_object_cache',
		);

		private static $form_cache = array();

		public $cache;		// WpssoUtilCache.
		public $cf;		// WpssoUtilCustomFields.
		public $metabox;	// WpssoUtilMetabox.
		public $reg;		// WpssoUtilReg.
		public $wc;		// WpssoUtilWooCommerce.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->set_util_instances( $plugin );

			$this->add_plugin_filters( $this, array(
				'pub_lang' => 3,
			) );

			$this->add_plugin_actions( $this, array(
				'scheduled_task_started' => 1,
			), $prio = -1000 );

			/**
			 * Several actions must be hooked to define our image sizes on the front-end, back-end, AJAX calls, REST
			 * API calls, etc.
			 */
			add_action( 'wp', array( $this, 'add_plugin_image_sizes' ), -100 );				// Front-end compatibility.

			add_action( 'admin_init', array( $this, 'add_plugin_image_sizes' ), -100 );			// Back-end + AJAX compatibility.

			add_action( 'rest_api_init', array( $this, 'add_plugin_image_sizes' ), -100 );			// REST API compatibility.
		}

		public function set_util_instances( &$plugin ) {

			/**
			 * WpssoUtilCache.
			 */
			if ( ! class_exists( 'WpssoUtilCache' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-cache.php';
			}

			$this->cache = new WpssoUtilCache( $plugin );

			/**
			 * WpssoUtilCustomFields.
			 */
			if ( ! class_exists( 'WpssoUtilCustomFields' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-custom-fields.php';
			}

			$this->cf = new WpssoUtilCustomFields( $plugin, $this );	// Constructor uses $this->add_plugin_filters().

			/**
			 * WpssoUtilMetabox.
			 */
			if ( ! class_exists( 'WpssoUtilMetabox' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-metabox.php';
			}

			$this->metabox = new WpssoUtilMetabox( $plugin );

			/**
			 * WpssoUtilReg.
			 */
			if ( ! class_exists( 'WpssoUtilReg' ) ) { // Since WPSSO Core v6.13.1.

				require_once WPSSO_PLUGINDIR . 'lib/util-reg.php';
			}

			$this->reg = new WpssoUtilReg( $plugin );

			/**
			 * WpssoUtilWooCommerce.
			 */
			if ( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) {

				if ( ! class_exists( 'WpssoUtilWooCommerce' ) ) {

					require_once WPSSO_PLUGINDIR . 'lib/util-woocommerce.php';
				}

				$this->wc = new WpssoUtilWooCommerce( $plugin );
			}
		}

		public function filter_pub_lang( $current_lang, $publisher, $mixed = 'current' ) {

			if ( is_string( $publisher ) ) {	// Example: 'facebook', 'google, 'twitter', etc.

				$pub_lang = self::get_pub_lang( $publisher );

			} elseif ( is_array( $publisher ) ) {

				$pub_lang = $publisher;

			} else {
				return $current_lang;
			}

			/**
			 * Returns the WP language as 'en' or 'en_US'.
			 */
			$locale = $fb_lang = self::get_locale( $mixed );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'get_locale returned: ' . $locale );
			}

			/**
			 * All facebook languages are formatted 'en_US', so correct known two letter locales.
			 */
			if ( strlen( $fb_lang ) == 2 ) {

				switch ( $fb_lang ) {

					case 'el':

						$fb_lang = 'el_GR';

						break;

					default:

						/**
						 * fr to fr_FR, for example.
						 */
						$fb_lang = $fb_lang . '_' . strtoupper( $fb_lang );

						break;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'fb_lang changed to: ' . $fb_lang );
				}
			}

			/**
			 * Check for complete en_US format (facebook).
			 */
			if ( isset( $pub_lang[ $fb_lang ] ) ) {

				$current_lang = $fb_lang;

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'underscore locale found: ' . $current_lang );
				}
			}

			/**
			 * Hyphen instead of underscore (google).
			 */
			if ( ( $locale = preg_replace( '/_/', '-', $locale ) ) && isset( $pub_lang[ $locale ] ) ) {

				$current_lang = $locale;

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'hyphen locale found: ' . $current_lang );
				}
			}

			/**
			 * Lowercase with hyphen (twitter).
			 */
			if ( ( $locale = strtolower( $locale ) ) && isset( $pub_lang[ $locale ] ) ) {

				$current_lang = $locale;

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'lowercase locale found: ' . $current_lang );
				}
			}

			/**
			 * Two-letter lowercase format (google and twitter).
			 */
			if ( ( $locale = preg_replace( '/[_-].*$/', '', $locale ) ) && isset( $pub_lang[ $locale ] ) ) {

				$current_lang = $locale;

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'two-letter locale found: ' . $current_lang );
				}
			}

			return $current_lang;
		}

		public function action_scheduled_task_started( $user_id ) {

			$this->add_plugin_image_sizes();
		}

		/**
		 * Can be called directly and from the "wp", "rest_api_init", and "current_screen" actions. The $wp_obj variable
		 * can be false or a WP object (WP_Post, WP_Term, WP_User, WP_REST_Server, etc.). The $mod variable can be false,
		 * and if so, it will be set using get_page_mod().
		 *
		 * This method does not return a value, so do not use it as a filter. ;-)
		 */
		public function add_plugin_image_sizes( $wp_obj = false, $image_sizes = array(), $filter_sizes = true ) {

			/**
			 * Allow various plugin add-ons to provide their image names, labels, etc. The first dimension array key is
			 * the option name prefix by default. You can also include the width, height, crop, crop_x, and crop_y
			 * values.
			 *
			 *	Array (
			 *		[og] => Array (
			 *			[name] => opengraph
			 *			[label] => Open Graph	// Pre-translated.
			 *		)
			 *	)
			 */
			if ( $this->p->debug->enabled ) {

				$doing_ajax = SucomUtil::get_const( 'DOING_AJAX' );

				$wp_obj_type = gettype( $wp_obj ) === 'object' ? get_class( $wp_obj ) . ' object' : gettype( $wp_obj );

				$this->p->debug->log( 'DOING_AJAX is ' . ( $doing_ajax ? 'true' : 'false' ) );

				$this->p->debug->log( '$wp_obj type is ' . $wp_obj_type );

				$this->p->debug->mark( 'define image sizes' );	// Begin timer.
			}

			/**
			 * Get default options only once.
			 */
			static $def_opts = null;

			if ( $filter_sizes ) {

				$image_sizes = apply_filters( $this->p->lca . '_plugin_image_sizes', $image_sizes );
			}

			foreach( $image_sizes as $opt_pre => $size_info ) {

				if ( ! is_array( $size_info ) ) {	// Just in case.

					continue;
				}

				foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $key ) {

					/**
					 * Value provided by filters.
					 */
					if ( isset( $size_info[ $key ] ) ) {

						continue;

					/**
					 * Plugin settings.
					 */
					} elseif ( isset( $this->p->options[ $opt_pre . '_img_' . $key ] ) ) {

						$size_info[ $key ] = $this->p->options[ $opt_pre . '_img_' . $key ];

					/**
					 * Default settings.
					 */
					} else {

						if ( null === $def_opts ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'getting default option values' );
							}

							$def_opts = $this->p->opt->get_defaults();
						}

						if ( isset( $def_opts[ $opt_pre . '_img_' . $key ] ) ) {	// Just in case.

							$size_info[ $key ] = $def_opts[ $opt_pre . '_img_' . $key ];

						} else {

							$size_info[ $key ] = null;
						}
					}
				}

				if ( empty( $size_info[ 'crop' ] ) ) {

					$size_info[ 'crop' ] = false;

				} else {

					$size_info[ 'crop' ] = true;

					$new_crop = array( 'center', 'center' );

					foreach ( array( 'crop_x', 'crop_y' ) as $crop_key => $key ) {

						if ( ! empty( $size_info[ $key ] ) && $size_info[ $key ] !== 'none' ) {

							$new_crop[ $crop_key ] = $size_info[ $key ];
						}
					}

					if ( $new_crop !== array( 'center', 'center' ) ) {

						$size_info[ 'crop' ] = $new_crop;
					}
				}

				if ( $size_info[ 'width' ] > 0 && $size_info[ 'height' ] > 0 ) {

					if ( isset( $size_info[ 'label_transl' ] ) ) {

						$size_label = $size_info[ 'label_transl' ];

					} elseif ( isset( $size_info[ 'label' ] ) ) {

						$size_label = $size_info[ 'label' ];

					} else {

						$size_label = $size_info[ 'name' ];
					}

					$this->cache_size_labels[ $this->p->lca . '-' . $size_info[ 'name' ] ] = $size_label;

					$this->cache_size_opts[ $this->p->lca . '-' . $size_info[ 'name' ] ] = $opt_pre;

					/**
					 * Add the image size.
					 */
					add_image_size( $this->p->lca . '-' . $size_info[ 'name' ], $size_info[ 'width' ], $size_info[ 'height' ], $size_info[ 'crop' ] );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'added image size ' . $this->p->lca . '-' . $size_info[ 'name' ] . ' ' . 
							$size_info[ 'width' ] . 'x' . $size_info[ 'height' ] .  ' ' . ( empty( $size_info[ 'crop' ] ) ?
								'uncropped' : 'cropped ' . $size_info[ 'crop_x' ] . '/' . $size_info[ 'crop_y' ] ) );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				if ( ! $doing_ajax ) {

					$this->p->debug->log_arr( 'get_image_sizes', $this->get_image_sizes() );
				}

				$this->p->debug->mark( 'define image sizes' );	// End timer.
			}
		}

		/**
		 * Disable transient cache for debug mode. This method is also called for non-WordPress sharing / canonical URLs
		 * with query arguments.
		 */
		public function disable_cache_filters( array $add_filters = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $do_once = array();

			$default_filters = array(
				'cache_expire_head_markup'      => '__return_zero',
				'cache_expire_setup_html'       => '__return_zero',
				'cache_expire_shortcode_html'   => '__return_zero',
				'cache_expire_sharing_buttons'  => '__return_zero',
			);

			$disable_filters = array();

			foreach ( array_merge( $default_filters, $add_filters ) as $filter_name => $callback ) {

				if ( ! isset( $do_once[ $filter_name ] ) ) {

					$do_once[ $filter_name ] = true;

					$disable_filters[ $filter_name ] = $callback;
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

		private function add_plugin_hooks( $type, $class, $hook_list, $prio, $ext = '' ) {

			$ext = $ext === '' ? $this->p->lca : $ext;

			foreach ( $hook_list as $name => $val ) {

				if ( ! is_string( $name ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $name . ' => ' . $val . ' ' . $type . ' skipped: filter name must be a string' );
					}

					continue;
				}

				/**
				 * Example:
				 * 	'json_data_https_schema_org_website' => 5
				 */
				if ( is_int( $val ) ) {

					$arg_nums    = $val;
					$hook_name   = self::sanitize_hookname( $ext . '_' . $name );
					$method_name = self::sanitize_hookname( $type . '_' . $name );

					if ( is_callable( array( &$class, $method_name ) ) ) {

						call_user_func( 'add_' . $type, $hook_name, array( &$class, $method_name ), $prio, $arg_nums );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'added ' . $method_name . ' method ' . $type, 3 );
						}

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $method_name . ' method ' . $type . ' is not callable' );
						}
					}

				/**
				 * Example:
				 * 	'add_schema_meta_array' => '__return_false'
				 */
				} elseif ( is_string( $val ) ) {

					$arg_nums      = 1;
					$hook_name     = self::sanitize_hookname( $ext . '_' . $name );
					$function_name = self::sanitize_hookname( $val );

					if ( is_callable( $function_name ) ) {

						call_user_func( 'add_' . $type, $hook_name, $function_name, $prio, $arg_nums );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'added ' . $function_name . ' function ' . $type . ' for ' . $hook_name, 3 );
						}

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $method_name . ' function ' . $type . ' for ' . $hook_name . ' is not callable' );
						}
					}

				/**
				 * Example:
				 * 	'json_data_https_schema_org_article' => array(
				 *		'json_data_https_schema_org_article'     => 5,
				 *		'json_data_https_schema_org_newsarticle' => 5,
				 *		'json_data_https_schema_org_techarticle' => 5,
				 *	)
				 */
				} elseif ( is_array( $val ) ) {

					$method_name = self::sanitize_hookname( $type . '_' . $name );

					foreach ( $val as $hook_name => $arg_nums ) {

						$hook_name = self::sanitize_hookname( $ext . '_' . $hook_name );

						if ( is_callable( array( &$class, $method_name ) ) ) {

							call_user_func( 'add_' . $type, $hook_name, array( &$class, $method_name ), $prio, $arg_nums );

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'added ' . $method_name . ' method ' . $type . ' for ' . $hook_name, 3 );
							}

						} else {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( $method_name . ' method ' . $type . ' for ' . $hook_name . ' is not callable' );
							}
						}
					}
				}
			}
		}

		/**
		 * If this is an '_img_url' option, add the image size and unset the '_img_id' option.
		 */
		public function maybe_add_img_url_size( array &$opts, $opt_key ) {

			/**
			 * Only process option keys with '_img_url' in their name.
			 */
			if ( false === strpos( $opt_key, '_img_url' ) ) {

				return;
			}

			$this->add_image_url_size( $opts, $opt_key );

			$img_id_key     = str_replace( '_img_url', '_img_id', $opt_key, $count );	// Image ID key.
			$img_id_pre_key = str_replace( '_img_url', '_img_id_pre', $opt_key );		// Image ID media library prefix key.

			if ( $count ) {	// Just in case.

				unset( $opts[ $img_id_key ] );
				unset( $opts[ $img_id_pre_key ] );
			}
		}

		/**
		 * $media_prefixes can be a single key name or an array of key names.
		 *
		 * Uses a reference variable to modify the $opts array directly.
		 */
		public function add_image_url_size( array &$opts, $media_prefixes = 'og:image' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! is_array( $media_prefixes ) ) {

				$media_prefixes = array( $media_prefixes );
			}

			foreach ( $media_prefixes as $media_pre ) {

				$opt_suffix = '';

				if ( preg_match( '/^(.*)(#.*)$/', $media_pre, $matches ) ) {	// Language.

					$media_pre  = $matches[ 1 ];
					$opt_suffix = $matches[ 2 ] . $opt_suffix;
				}

				if ( preg_match( '/^(.*)(_[0-9]+)$/', $media_pre, $matches ) ) {	// Multi-option.

					$media_pre  = $matches[ 1 ];
					$opt_suffix = $matches[ 2 ] . $opt_suffix;
				}

				$media_url = self::get_first_mt_media_url( $opts, $media_pre . $opt_suffix );

				if ( ! empty( $media_url ) ) {

					list(
						$opts[ $media_pre . ':width' . $opt_suffix ],	// Example: place_img_url:width_1.
						$opts[ $media_pre . ':height' . $opt_suffix ],	// Example: place_img_url:height_1.
						$image_type,
						$image_attr
					) = $this->get_image_url_info( $media_url );
				}
			}

			return $opts;
		}

		public function is_image_url( $image_url ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Example $image_info:
			 *
			 * Array (
			 *	[0] => 2048
			 *	[1] => 2048
			 *	[2] => 3
			 *	[3] => width="2048" height="2048"
			 *	[bits] => 8
			 *	[mime] => image/png
			 * )
			 */
			$image_info = $this->get_image_url_info( $image_url );

			if ( empty( $image_info[ 2 ] ) ) {	// Check for the IMAGETYPE_XXX constant integer.

				return false;
			}

			return true;
		}

		/**
		 * Always returns an array.
		 */
		public function get_image_url_info( $image_url ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array(); // Optimize and get image size for a given URL only once.

			if ( isset( $local_cache[ $image_url ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: returning image info from static cache' );
				}

				return $local_cache[ $image_url ];
			}

			$def_image_info = array( WPSSO_UNDEF, WPSSO_UNDEF, '', '' );

			if ( empty( $image_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: image url is empty' );
				}

				return $local_cache[ $image_url ] = $def_image_info;	// Stop here.

			} elseif ( filter_var( $image_url, FILTER_VALIDATE_URL ) === false ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: invalid image url = '.$image_url );
				}

				return $local_cache[ $image_url ] = $def_image_info;	// Stop here.
			}

			$cache_md5_pre  = $this->p->lca . '_i_';
			$cache_exp_secs = $this->get_cache_exp_secs( $cache_md5_pre );	// Default is day in seconds.

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

						$this->p->debug->log( 'exiting early: returning image info from transient' );
					}

					return $local_cache[ $image_url ] = $image_info;
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'transient cache for image info is disabled' );
			}

			$mtime_start = microtime( true );

			/**
			 * Example $image_info:
			 *
			 * Array (
			 *	[0] => 2048
			 *	[1] => 2048
			 *	[2] => 3
			 *	[3] => width="2048" height="2048"
			 *	[bits] => 8
			 *	[mime] => image/png
			 * )
			 */
			$image_info  = $this->p->cache->get_image_size( $image_url, $exp_secs = 300, $curl_opts = array(), $error_handler = 'wpsso_error_handler' );
			$mtime_total = microtime( true ) - $mtime_start;
			$mtime_max   = self::get_const( 'WPSSO_PHP_GETIMGSIZE_MAX_TIME', 1.50 );

			/**
			 * Issue warning for slow getimagesize() request.
			 */
			if ( $mtime_max > 0 && $mtime_total > $mtime_max ) {

				$info = $this->p->cf[ 'plugin' ][ $this->p->lca ];

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( sprintf( 'slow PHP function detected - getimagesize() took %1$0.3f secs for %2$s',
						$mtime_total, $image_url ) );
				}

				$error_pre = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );

				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $mtime_max );

				$error_msg = sprintf( __( 'Slow PHP function detected - getimagesize() took %1$0.3f secs for %2$s (%3$s).', 'wpsso' ),
					$mtime_total, $image_url, $rec_max_msg );

				/**
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->warn( $error_msg );
				}

				self::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			if ( is_array( $image_info ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'PHP getimagesize() image info: ' . $image_info[ 0 ] . 'x' . $image_info[ 1 ] );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'PHP getimagesize() did not return an array - using defaults' );
				}

				$image_info = $def_image_info;
			}

			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $image_info, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'image info saved to transient cache for ' . $cache_exp_secs . ' seconds' );
				}
			}

			return $local_cache[ $image_url ] = $image_info;
		}

		/**
		 * Example $size_name = 'wpsso-opengraph' returns 'Open Graph' pre-translated.
		 */
		public function get_image_size_label( $size_name ) {

			if ( ! empty( $this->cache_size_labels[ $size_name ] ) ) {

				return $this->cache_size_labels[ $size_name ];

			}

			return $size_name;
		}

		/**
		 * Example $size_name = 'wpsso-opengraph' returns 'og'.
		 */
		public function get_image_size_opt( $size_name ) {

			if ( isset( $this->cache_size_opts[ $size_name ] ) ) {

				return $this->cache_size_opts[ $size_name ];

			}

			return '';
		}

		public function get_image_size_names( $mixed = null ) {

			$size_names = array_keys( $this->cache_size_labels );

			if ( null === $mixed ) {

				return $size_names;

			} elseif ( is_array( $mixed ) ) {

				return array_intersect( $size_names, $mixed );	// Sanitize and return.

			} elseif ( is_string( $mixed ) ) {

				switch ( $mixed ) {

					case 'opengraph':
					case 'pinterest':
					case 'thumbnail':

						return array( $this->p->lca . '-' . $mixed );

					case 'schema':

						return array(
							$this->p->lca . '-schema-1-1',
							$this->p->lca . '-schema-4-3',
							$this->p->lca . '-schema-16-9',
						);

					case $this->p->lca . '-schema':			// Deprecated on 2020/08/12.
					case $this->p->lca . '-schema-article':		// Deprecated on 2020/08/12.

						return array( $this->p->lca . '-schema-1-1' );

					case $this->p->lca . '-schema-article-1-1':	// Deprecated on 2020/08/12.

						return array( $this->p->lca . '-schema-1-1' );

					case $this->p->lca . '-schema-article-4-3':	// Deprecated on 2020/08/12.

						return array( $this->p->lca . '-schema-4-3' );

					case $this->p->lca . '-schema-article-16-9':	// Deprecated on 2020/08/12.

						return array( $this->p->lca . '-schema-16-9' );

					default:

						return array_intersect( $size_names, array( $mixed ) );
				}
			}

			return array();
		}

		/**
		 * Get the width, height and crop value for all image sizes. Returns an associative array with the image size name
		 * as the array key value.
		 */
		public function get_image_sizes( $attachment_id = false ) {

			$image_sizes = array();

			foreach ( get_intermediate_image_sizes() as $size_name ) {

				$image_sizes[ $size_name ] = $this->get_size_info( $size_name, $attachment_id );
			}

			return $image_sizes;
		}

		/**
		 * Get the width, height and crop value for a specific image size.
		 */
		public function get_size_info( $size_name = 'thumbnail', $attachment_id = false ) {

			if ( ! is_string( $size_name ) ) {	// Just in case.

				return false;
			}

			static $local_cache = array();

			if ( isset( $local_cache[ $size_name ][ $attachment_id ] ) ) {

				return $local_cache[ $size_name ][ $attachment_id ];
			}

			global $_wp_additional_image_sizes;

			if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'width' ] ) ) {

				$width = intval( $_wp_additional_image_sizes[ $size_name ][ 'width' ] );

			} else {

				$width = get_option( $size_name . '_size_w' );
			}

			if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'height' ] ) ) {

				$height = intval( $_wp_additional_image_sizes[ $size_name ][ 'height' ] );

			} else {

				$height = get_option( $size_name . '_size_h' );
			}

			if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'crop' ] ) ) {

				$crop = $_wp_additional_image_sizes[ $size_name ][ 'crop' ];

			} else {

				$crop = get_option( $size_name . '_crop' );
			}

			/**
			 * Standardize to true, false, or non-empty array.
			 */
			if ( empty( $crop ) ) {	// 0, false, null, or empty array.
				
				$crop = false;

			} elseif ( ! is_array( $crop ) ) {	// 1, or true.

				$crop = true;
			}

			/**
			 * If the image size is cropped, then check the image metadata for a custom crop area.
			 */
			if ( $crop && $attachment_id && is_numeric( $attachment_id ) ) {

				$new_crop = is_array( $crop ) ? $crop : array( 'center', 'center' );

				foreach ( array( 'attach_img_crop_x', 'attach_img_crop_y' ) as $crop_key => $md_key ) {

					$value = $this->p->post->get_options( $attachment_id, $md_key );

					if ( $value && $value !== 'none' ) {		// Custom crop value found.

						$new_crop[ $crop_key ] = $value;	// Adjust the crop value.

						$crop = $new_crop;			// Update the crop array.
					}
				}
			}

			if ( $crop === array( 'center', 'center' ) ) {

				$crop = true;
			}

			$is_cropped = empty( $crop ) ? false : true;

			/**
			 * Crop can be true, false, or an array.
			 */
			return $local_cache[ $size_name ][ $attachment_id ] = array(
				'width'        => $width,
				'height'       => $height,
				'crop'         => $crop,
				'is_cropped'   => $is_cropped,
				'dimensions'   => $width . 'x' . $height . ' ' . ( $is_cropped ? __( 'cropped', 'wpsso' ) : __( 'uncropped', 'wpsso' ) ),
				'label_transl' => $this->get_image_size_label( $size_name ),
				'opt_prefix'   => $this->get_image_size_opt( $size_name ),
			);
		}

		/**
		 * Deprecated on 2020/04/15.
		 */
		public function add_ptns_to_opts( array &$opts, $mixed, $default = 1 ) {

			if ( ! is_array( $mixed ) ) {

				$mixed = array( $mixed => $default );
			}

			return $this->add_post_type_names( $opts, $mixed );
		}

		/**
		 * Add options using a key prefix string / array and post type names.
		 */
		public function add_post_type_names( array &$opts, array $opt_pre_defs ) {

			foreach ( $opt_pre_defs as $opt_pre => $def_val ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'checking options for prefix ' . $opt_pre );
				}

				foreach ( SucomUtilWP::get_post_types( 'names' ) as $name ) {

					$opt_key = $opt_pre . '_' . $name;

					if ( ! isset( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'adding ' . $opt_key . ' = ' . $def_val );
						}

						$opts[ $opt_key ] = $def_val;

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
		 * Deprecated on 2020/06/03.
		 */
		public function get_post_types( $output = 'objects' ) {

			return SucomUtilWP::get_post_types( $output );
		}

		/**
		 * Deprecated on 2020/06/03.
		 */
		public function get_taxonomies( $output = 'objects' ) {

			return SucomUtilWP::get_taxonomies( $output );
		}

		/**
		 * Deprecated on 2020/04/15.
		 */
		public function add_ttns_to_opts( array &$opts, $mixed, $default = 1 ) {

			if ( ! is_array( $mixed ) ) {

				$mixed = array( $mixed => $default );
			}

			return $this->add_taxonomy_names( $opts, $mixed );
		}

		/**
		 * Add options using a key prefix string / array and term names.
		 */
		public function add_taxonomy_names( array &$opts, array $opt_pre_defs ) {

			foreach ( $opt_pre_defs as $opt_pre => $def_val ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'checking options for prefix ' . $opt_pre );
				}

				foreach ( SucomUtilWP::get_taxonomies( 'names' ) as $name ) {

					$opt_key = $opt_pre . '_' . $name;

					if ( ! isset( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'adding ' . $opt_key . ' = ' . $def_val );
						}

						$opts[ $opt_key ] = $def_val;

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipped ' . $opt_key . ' - already set' );
						}
					}
				}
			}

			return $opts;
		}

		public function get_form_cache( $name, $add_none = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$key = self::sanitize_key( $name );	// Just in case.

			if ( ! isset( self::$form_cache[ $key ] ) ) {

				self::$form_cache[ $key ] = null;		// Create key for default filter.
			}

			if ( self::$form_cache[ $key ] === null ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding new form cache entry for ' . $key );
				}

				switch ( $key ) {

					case 'half_hours':

						/**
						 * Returns an array of times without a 'none' value.
						 */
						self::$form_cache[ $key ] = self::get_hours_range( $start_secs = 0, $end_secs = DAY_IN_SECONDS,
							$step_secs = 60 * 30, $label_format = 'H:i' );

						break;

					case 'quarter_hours':

						/**
						 * Returns an array of times without a 'none' value.
						 */
						self::$form_cache[ $key ] = self::get_hours_range( $start_secs = 0, $end_secs = DAY_IN_SECONDS,
							$step_secs = 60 * 15, $label_format = 'H:i' );

						break;

					case 'all_types':

						self::$form_cache[ $key ] = $this->p->schema->get_schema_types_array( $flatten = false );

						break;

					case 'business_types':

						$this->get_form_cache( 'all_types' );

						self::$form_cache[ $key ] =& self::$form_cache[ 'all_types' ][ 'thing' ][ 'place' ][ 'local.business' ];

						break;

					case 'business_types_select':

						$this->get_form_cache( 'business_types' );

						self::$form_cache[ $key ] = $this->p->schema->get_schema_types_select( $context = 'business',
							self::$form_cache[ 'business_types' ] );

						break;

					case 'org_types':

						$this->get_form_cache( 'all_types' );

						self::$form_cache[ $key ] =& self::$form_cache[ 'all_types' ][ 'thing' ][ 'organization' ];

						break;

					case 'org_types_select':

						$this->get_form_cache( 'org_types' );

						self::$form_cache[ $key ] = $this->p->schema->get_schema_types_select( $context = 'organization',
							self::$form_cache[ 'org_types' ] );

						break;

					case 'org_site_names':

						self::$form_cache[ $key ] = array( 'site' => '[WebSite Organization]' );

						self::$form_cache[ $key ] = apply_filters( $this->p->lca . '_form_cache_' . $key, self::$form_cache[ $key ] );

						break;

					case 'person_names':

						/**
						 * $add_none is always false since this method may add a 'none' array element as well.
						 */
						self::$form_cache[ $key ] = WpssoUser::get_person_names( false );

						break;

					case 'place_types':

						$this->get_form_cache( 'all_types' );

						self::$form_cache[ $key ] =& self::$form_cache[ 'all_types' ][ 'thing' ][ 'place' ];

						break;

					case 'place_types_select':

						$this->get_form_cache( 'place_types' );

						self::$form_cache[ $key ] = $this->p->schema->get_schema_types_select( $context = 'place',
							self::$form_cache[ 'place_types' ] );

						break;

					default:

						self::$form_cache[ $key ] = apply_filters( $this->p->lca . '_form_cache_' . $key,
							self::$form_cache[ $key ] );

						break;
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning existing form cache entry for ' . $key );
			}

			if ( isset( self::$form_cache[ $key ][ 'none' ] ) ) {

				unset( self::$form_cache[ $key ][ 'none' ] );
			}

			if ( $add_none ) {

				$none = array( 'none' => '[None]' );

				if ( is_array( self::$form_cache[ $key ] ) ) {

					return $none + self::$form_cache[ $key ];

				} else {

					return $none;
				}

			} else {
				return self::$form_cache[ $key ];
			}
		}

		/**
		 * Deprecated on 2020/05/05.
		 */
		public function schedule_clear_all_cache( $user_id = null, $clear_other = false, $clear_short = null, $refresh = true ) {

			$this->cache->schedule_clear( $user_id, $clear_other, $clear_short, $refresh );
		}

		/**
		 * Deprecated on 2020/05/05.
		 */
		public function delete_all_db_transients( $clear_short = false, $transient_prefix = '' ) {

			return $this->cache->clear_db_transients( $clear_short, $transient_prefix = '' );
		}

		/**
		 * Returns an associative array, with 'none' as the first element.
		 */
		public function get_article_sections() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$cache_md5_pre  = $this->p->lca . '_f_';
			$cache_exp_secs = $this->get_cache_exp_secs( $cache_md5_pre );	// Default is month in seconds.
			$text_list_file = self::get_file_path_locale( WPSSO_ARTICLE_SECTIONS_LIST );

			if ( $cache_exp_secs > 0 ) {

				/**
				 * Note that cache_id is a unique identifier for the cached data and should be 45 characters or
				 * less in length. If using a site transient, it should be 40 characters or less in length.
				 */
				$cache_salt = __METHOD__ . '(' . $text_list_file . ')';
				$cache_id   = $cache_md5_pre . md5( $cache_salt );
	
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'transient cache salt ' . $cache_salt );
				}

				$sections = get_transient( $cache_id );

				if ( is_array( $sections ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'article sections retrieved from transient ' . $cache_id );
					}

					return $sections;
				}
			}

			$raw_sections = file( $text_list_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );	// Returns false on error.

			if ( ! is_array( $raw_sections ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error reading %s article sections list file' );
				}

				if ( is_admin() ) {

					$error_pre = sprintf( '%s error:', __METHOD__ );

					$error_msg = sprintf( __( 'Error reading the %s file for the article sections list.', 'wpsso' ), $text_list_file );

					$this->p->notice->err( $error_msg );

					self::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return array();
			}

			$sections = array();

			foreach ( $raw_sections as $num => $section_name ) {

				/**
				 * Skip comment lines.
				 */
				if ( 0 === strpos( $section_name, '#' ) ) {

					continue;
				}

				$sections[ $section_name ] = $section_name;

				unset( $sections[ $num ] );	// Save memory and unset as we go.
			}

			unset( $raw_sections );

			$sections = apply_filters( $this->p->lca . '_article_sections', $sections );

			asort( $sections, SORT_NATURAL );

			$sections = array( 'none' => '[None]' ) + $sections;	// After sorting the array, put 'none' first.

			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $sections, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'article sections saved to transient cache for ' . $cache_exp_secs . ' seconds' );
				}
			}

			return $sections;
		}

		/**
		 * Format the product category list from https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt.
		 *
		 * Returns an associative array, with 'none' as the first element.
		 */
		public function get_google_product_categories() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$cache_md5_pre  = $this->p->lca . '_f_';
			$cache_exp_secs = $this->get_cache_exp_secs( $cache_md5_pre );	// Default is month in seconds.
			$text_list_file = self::get_file_path_locale( WPSSO_PRODUCT_CATEGORIES_LIST );

			if ( $cache_exp_secs > 0 ) {

				/**
				 * Note that cache_id is a unique identifier for the cached data and should be 45 characters or
				 * less in length. If using a site transient, it should be 40 characters or less in length.
				 */
				$cache_salt = __METHOD__ . '(' . $text_list_file . ')';
				$cache_id   = $cache_md5_pre . md5( $cache_salt );
	
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'transient cache salt ' . $cache_salt );
				}

				$categories = get_transient( $cache_id );

				if ( is_array( $categories ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'product categories retrieved from transient ' . $cache_id );
					}

					return $categories;
				}
			}

			$raw_categories = file( $text_list_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );	// Returns false on error.

			if ( ! is_array( $raw_categories ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error reading %s product categories list file' );
				}

				if ( is_admin() ) {

					$error_pre = sprintf( '%s error:', __METHOD__ );

					$error_msg = sprintf( __( 'Error reading the %s file for the product categories list.', 'wpsso' ), $text_list_file );

					$this->p->notice->err( $error_msg );

					self::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				return array();
			}

			$categories = array();

			foreach ( $raw_categories as $num => $category_id_name ) {

				/**
				 * Skip comment lines.
				 */
				if ( 0 === strpos( $category_id_name, '#' ) ) {

					continue;
				}

				if ( preg_match( '/^([0-9]+) - (.*)$/', $category_id_name, $match ) ) {

					$categories[ $match[ 1 ] ] = $match[ 2 ];
				}

				unset( $raw_categories[ $num ] );	// Save memory and unset as we go.
			}

			unset( $raw_categories );

			$categories = apply_filters( $this->p->lca . '_google_product_categories', $categories );

			asort( $categories, SORT_NATURAL );

			$categories = array( 'none' => '[None]' ) + $categories;	// After sorting the array, put 'none' first.

			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $categories, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product categories saved to transient cache for ' . $cache_exp_secs . ' seconds' );
				}
			}

			return $categories;
		}

		/**
		 * Query argument examples:
		 *
		 * 	/html/head/link|/html/head/meta
		 * 	/html/head/link[@rel="canonical"]
		 * 	/html/head/meta[starts-with(@property, "og:video:")]
		 */
		public function get_html_head_meta( $request, $query = '/html/head/meta', $libxml_errors = false, array $curl_opts = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$is_admin = is_admin();	// Optimize and call once.

			if ( ! function_exists( 'mb_convert_encoding' ) ) {

				$this->php_function_missing( 'mb_convert_encoding()', __METHOD__ );

				return false;
			}

			if ( ! class_exists( 'DOMDocument' ) ) {

				$this->php_class_missing( 'DOMDocument', __METHOD__ );

				return false;
			}

			if ( empty( $request ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: the request argument is empty' );
				}

				return false;

			}
			
			if ( empty( $query ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: the query argument is empty' );
				}

				return false;

			}

			if ( false !== stripos( $request, '<html' ) ) {	// Request contains html.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using the html submitted as the request argument' );
				}

				$html = $request;

				$request = false;	// Just in case.

			} elseif ( filter_var( $request, FILTER_VALIDATE_URL ) === false ) {	// Request is an invalid url.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: request argument is not html or a valid url' );
				}

				if ( $is_admin ) {

					$this->p->notice->err( sprintf( __( 'The %1$s request argument is not HTML or a valid URL.',
						'wpsso' ), __FUNCTION__ ) );
				}

				return false;

			} else {
			
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting HTML for ' . $request );
				}

				$html = $this->p->cache->get( $request, $format = 'raw', $cache_type = 'transient', $exp_secs = 300, $cache_ext = '', $curl_opts );

				if ( ! $html ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: error getting HTML from ' . $request );
					}

					if ( $is_admin ) {

						$this->p->notice->err( sprintf( __( 'Error getting HTML from <a href="%1$s">%1$s</a>.',
							'wpsso' ), $request ) );
					}

					return false;
				}
			}
			
			$html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );	// Convert to UTF8.

			$html = preg_replace( '/<!--.*-->/Uums', '', $html );		// Pattern and subject strings are treated as UTF8.

			if ( empty( $html ) ) {	// Returned html for url is empty.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: html for ' . $request . ' is empty' );
				}

				if ( $is_admin ) {

					$this->p->notice->err( sprintf( __( 'Webpage retrieved from <a href="%1$s">%1$s</a> is empty.',
						'wpsso' ), $request ) );
				}

				return false;

			}
		
			$doc = new DOMDocument();	// Since PHP v4.1.

			if ( function_exists( 'libxml_use_internal_errors' ) ) {	// Since PHP v5.1.

				$libxml_prev_state = libxml_use_internal_errors( true );	// Enable user error handling.

				if ( ! $doc->loadHTML( $html ) ) {	// loadXML() is too strict for most webpages.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'loadHTML returned error(s)' );
					}

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
					foreach ( libxml_get_errors() as $error ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'libxml error: ' . $error->message );
						}

						if ( $libxml_errors ) {

							if ( $is_admin ) {

								$this->p->notice->err( 'PHP libXML error: ' . $error->message );
							}
						}
					}

					libxml_clear_errors();	// Clear any HTML parsing errors.

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'loadHTML was successful' );
				}

				libxml_use_internal_errors( $libxml_prev_state );	// Restore previous error handling.

			} else {

				@$doc->loadHTML( $html );	// Load HTML and ignore errors.
			}

			$xpath   = new DOMXPath( $doc );
			$metas   = $xpath->query( $query );
			$met_ret = array();

			foreach ( $metas as $m ) {

				$m_atts = array();	// Put all attributes in a single array.

				foreach ( $m->attributes as $a ) {

					$m_atts[ $a->name ] = $a->value;
				}

				if ( isset( $m->textContent ) ) {

					$m_atts[ 'textContent' ] = $m->textContent;
				}

				$mt_ret[ $m->tagName ][] = $m_atts;
			}

			if ( $this->p->debug->enabled ) {

				if ( empty( $mt_ret ) ) {	// Empty array.

					if ( false === $request ) {	// $request argument is html

						$this->p->debug->log( 'meta tags found in submitted html' );

					} else {

						$this->p->debug->log( 'no meta tags found in ' . $request );
					}

				} else {
					$this->p->debug->log( 'returning array of ' . count( $mt_ret ) . ' meta tags' );
				}
			}

			return $mt_ret;
		}

		public function get_html_body( $request, $remove_script = true ) {

			$html = '';

			if ( 0 === strpos( $request, '//' ) ) {

				$request = self::get_prot() . ':' . $request;
			}

			if ( 0 === strpos( $request, '<' ) ) {	// Check for HTML content.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using html submitted in the request argument' );
				}

				$html = $request;

			} elseif ( empty( $request ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: request argument is empty' );
				}

				return false;

			} elseif ( 0 === strpos( $request, 'data:' ) ) {

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

					$this->p->debug->log( 'getting HTML for ' . $request );
				}

				$html = $this->p->cache->get( $request, $format = 'raw', $cache_type = 'transient', $exp_secs = 300 );

				if ( ! $html ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: error getting HTML from ' . $request );
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

		public function php_class_missing( $class, $method = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $class . ' PHP class is missing' );
			}

			if ( is_admin() ) {

				// translators: %1$s is the class name.
				$this->p->notice->err( sprintf( __( 'The PHP <code>%1$s</code> class is missing &ndash; contact your hosting provider to have the missing class installed.', 'wpsso' ), $class ) );
			}
		}

		public function php_function_missing( $function, $method = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $function . ' PHP function is missing' );
			}

			if ( is_admin() ) {

				// translators: %1$s is the function name.
				$this->p->notice->err( sprintf( __( 'The PHP <code>%1$s</code> function is missing &ndash; contact your hosting provider to have the missing function installed.', 'wpsso' ), $function ) );
			}
		}

		public function log_is_functions() {

			if ( ! $this->p->debug->enabled ) {	// Nothing to do.

				return;
			}

			$function_info = $this->get_is_functions();

			foreach ( $function_info as $function => $info ) {
				$this->p->debug->log( $info[ 0 ] );
			}
		}

		public function get_is_functions() {

			$function_info = array();

			foreach ( apply_filters( $this->p->lca . '_is_functions', $this->is_functions )  as $function ) {

				if ( function_exists( $function ) ) {

					$mtime_start = microtime( true );

					$function_ret = $function();

					$mtime_total = microtime( true ) - $mtime_start;

					$function_info[ $function ] = array(
						sprintf( '%-40s (%f secs)', $function . '() = ' . ( $function_ret ? 'TRUE' : 'false' ), $mtime_total ),
						$function_ret,
						$mtime_total,
					);

				} else {

					$function_info[ $function ] = array( $function . '() not found', null, 0 );
				}
			}

			return $function_info;
		}

		/**
		 * Deprecated on 2019/11/21.
		 */
		public static function register_ext_version( $ext, $version ) {

			WpssoUtilReg::update_ext_version( $ext, $version );
		}

		/**
		 * Allow the variables and values array to be extended.
		 *
		 * $extra must be an associative array with key/value pairs to be replaced.
		 */
		public function replace_inline_vars( $content, $mod = false, $atts = array(), $extra = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( strpos( $content, '%%' ) === false ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no inline vars' );
				}

				return $content;
			}

			/**
			 * The $mod array argument is preferred but not required.
			 *
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
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $mod );
			}

			$add_page = isset( $atts[ 'add_page' ] ) ? $atts[ 'add_page' ] : true;

			if ( empty( $atts[ 'url' ] ) ) {

				$sharing_url = $this->get_sharing_url( $mod, $add_page );

			} else {

				$sharing_url = $atts[ 'url' ];
			}

			if ( is_admin() ) {

				$request_url = $sharing_url;

			} else {

				$request_url = self::get_prot() . '://' . $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REQUEST_URI' ];
			}

			if ( empty( $atts[ 'short_url' ] ) ) {

				$shortener = $this->p->options[ 'plugin_shortener' ];

				$short_url = apply_filters( $this->p->lca . '_get_short_url', $sharing_url, $shortener, $mod, $is_main = true );
			} else {
				$short_url = $atts[ 'short_url' ];
			}

			$sitename    = self::get_site_name( $this->p->options, $mod );
			$sitealtname = self::get_site_name_alt( $this->p->options, $mod );
			$sitedesc    = self::get_site_description( $this->p->options, $mod );

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

			/**
			 * Allows for better visual cues in the Google validator.
			 */
			$do_pretty = self::get_const( 'WPSSO_JSON_PRETTY_PRINT', true ) || $this->p->debug->enabled ? true : false;

			$use_ext_lib = self::get_const( 'WPSSO_EXT_JSON_DISABLE', false ) ? false : true;

			$have_old_php = false;

			if ( 0 === $options && defined( 'JSON_UNESCAPED_SLASHES' ) ) {

				$options = JSON_UNESCAPED_SLASHES;		// Since PHP v5.4.
			}

			/**
			 * Decide if the encoded json will be minified or not.
			 */
			if ( $do_pretty ) {

				if ( defined( 'JSON_PRETTY_PRINT' ) ) {		// Since PHP v5.4.

					$options = $options|JSON_PRETTY_PRINT;

				} else {

					$have_old_php = true;			// Use SuextJsonFormat for older PHP.
				}
			}

			/**
			 * Encode the json.
			 */
			if ( is_array( $json ) ) {

				$json = self::json_encode_array( $json, $options, $depth );
			}

			/**
			 * After the JSON is encoded, maybe use the pretty print library for older PHP versions.
			 *
			 * Define WPSSO_EXT_JSON_DISABLE as true in wp-config.php to prevent using this library.
			 */
			if ( $have_old_php && $use_ext_lib ) {

				$classname = WpssoConfig::load_lib( false, 'ext/json-format', 'suextjsonformat' );

				if ( false !== $classname && class_exists( $classname ) ) {

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

			} elseif ( isset( $mod[ 'obj' ] ) && is_object( $mod[ 'obj' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: module object is defined' );
				}

				return $mod;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'use_post is ' . self::get_use_post_string( $use_post ) );
			}

			/**
			 * Check for known WP objects and set the object module name and its object ID.
			 */
			if ( is_object( $wp_obj ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'wp_obj argument is ' . get_class( $wp_obj ) . ' object' );
				}

				switch ( get_class( $wp_obj ) ) {

					case 'WP_Post':

						$mod[ 'name' ] = 'post';
						$mod[ 'id' ]   = $wp_obj->ID;

						break;

					case 'WP_Term':

						$mod[ 'name' ] = 'term';
						$mod[ 'id' ]   = $wp_obj->term_id;

						break;

					case 'WP_User':

						$mod[ 'name' ] = 'user';
						$mod[ 'id' ]   = $wp_obj->ID;

						break;
				}
			}

			if ( empty( $mod[ 'name' ] ) ) {

				if ( self::is_post_page( $use_post ) ) {	// $use_post = true | false | post_id

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'is_post_page is true' );
					}

					$mod[ 'name' ] = 'post';

				} elseif ( self::is_term_page() ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'is_term_page is true' );
					}

					$mod[ 'name' ] = 'term';

				} elseif ( self::is_user_page() ) {

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

						$mod[ 'id' ] = self::get_post_object( $use_post, 'id' );	// $use_post = true | false | post_id

						break;

					case 'term':

						$mod[ 'id' ] = self::get_term_object( false, '', 'id' );

						break;

					case 'user':

						$mod[ 'id' ] = self::get_user_object( false, 'id' );

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

					$mod = array_merge( WpssoWpMeta::$mod_defaults, $mod );

					break;
			}

			$mod[ 'use_post' ] = $use_post;

			/**
			 * The post module defines is_home_page, is_home_posts and is_home.
			 *
			 * If we don't have a module, then check if we're on the home posts page.
			 */
			if ( empty( $mod[ 'name' ] ) ) {

				$mod[ 'is_home' ] = $mod[ 'is_home_posts' ] = is_home();

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'is_home and is_home_posts are ' . ( $mod[ 'is_home' ] ? 'true' : 'false' ) );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mod', $mod );
			}

			return $mod;
		}

		public function get_oembed_url( $mod = false, $format = 'json' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$url = '';

			if ( ! function_exists( 'get_oembed_endpoint_url' ) ) {	// Since WP v4.4.

				return $url;
			}

			/**
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $mod );
			}

			if ( $mod[ 'is_post' ] ) {

				if ( ! empty( $mod[ 'id' ] ) ) {

					if ( $mod[ 'post_status' ] !== 'publish' ) {

						$post_obj = self::get_post_object( $mod[ 'id' ], $output = 'object' );

						if ( is_object( $post_obj ) ) {

							if ( ! is_wp_error( $post_obj ) ) {

								$post_obj->post_status = 'publish';

								$post_obj->post_name = $post_obj->post_name ? 
									$post_obj->post_name : sanitize_title( $post_obj->post_title );

								$url = get_permalink( $post_obj );
							}
						}

						if ( empty( $url ) ) {

							$url = get_permalink( $mod[ 'id' ] );
						}

					} else {
						$url = get_permalink( $mod[ 'id' ] );
					}
				}
			}

			if ( ! empty( $url ) ) {

				/**
			 	* Maybe enforce the FORCE_SSL constant.
			 	*/
				if ( self::get_const( 'FORCE_SSL' ) && ! self::is_https( $url ) ) {
	
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'force ssl is enabled - replacing http by https' );
					}

					$url = set_url_scheme( $url, 'https' );
				}

				$url = get_oembed_endpoint_url( $url, $format );	// Since WP v4.4.
			}

			return apply_filters( $this->p->lca . '_oembed_url', $url, $mod, $format );
		}

		public function get_oembed_data( $mod = false, $width = '600' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$data = false;	// Returns false on error.

			if ( ! function_exists( 'get_oembed_response_data' ) ) {	// Since WP v4.4.

				return $data;
			}

			/**
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $mod );
			}

			if ( $mod[ 'is_post' ] ) {

				if ( ! empty( $mod[ 'id' ] ) ) {

					if ( $mod[ 'post_status' ] !== 'publish' ) {

						$post_obj = self::get_post_object( $mod[ 'id' ], $output = 'object' );

						if ( is_object( $post_obj ) ) {

							if ( ! is_wp_error( $post_obj ) ) {

								$post_obj->post_status = 'publish';

								$post_obj->post_name = $post_obj->post_name ? 
									$post_obj->post_name : sanitize_title( $post_obj->post_title );

								$data = get_oembed_response_data( $post_obj, $width );	// Returns false on error.
							}
						}

						if ( empty( $data ) ) {

							$data = get_oembed_response_data( $mod[ 'id' ], $width );	// Returns false on error.
						}

					} else {

						$data = get_oembed_response_data( $mod[ 'id' ], $width );		// Returns false on error.
					}
				}
			}

			return apply_filters( $this->p->lca . '_oembed_data', $data, $mod, $width );
		}

		/**
		 * The $mod array argument is preferred but not required.
		 *
		 * $mod = true | false | post_id | $mod array
		 */
		public function get_canonical_url( $mod = false, $add_page = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $mod[ 'canonical_url' ] ) ) {

				$url = $this->get_page_url( 'canonical', $mod, $add_page );

			} else {

				$url = $mod[ 'canonical_url' ];
			}

			return $url;
		}

		/**
		 * The $mod array argument is preferred but not required.
		 *
		 * $mod = true | false | post_id | $mod array
		 */
		public function get_sharing_url( $mod = false, $add_page = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $mod[ 'sharing_url' ] ) ) {

				$url = $this->get_page_url( 'sharing', $mod, $add_page );

			} else {

				$url = $mod[ 'sharing_url' ];
			}

			return $url;
		}

		/**
		 * The $mod array argument is preferred but not required.
		 *
		 * $mod = true | false | post_id | $mod array
		 */
		private function get_page_url( $type, $mod, $add_page ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'type'     => $type,
					'mod'      => $mod,
					'add_page' => $add_page,
				) );
			}

			$url = false;

			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to get_page_mod()' );
				}

				$mod = $this->get_page_mod( $mod );
			}

			/**
			 * Optimize and return the URL from local cache if possible.
			 */
			static $local_cache = array();

			$cache_salt = false;

			if ( ! empty( $mod[ 'name' ] ) && ! empty( $mod[ 'id' ] ) ) {

				$cache_salt = self::get_mod_salt( $mod ) . '_type:' . (string) $type . '_add_page:' . (string) $add_page;

				if ( ! empty( $local_cache[ $cache_salt ] ) ) {

					return $local_cache[ $cache_salt ];
				}
			}

			if ( $mod[ 'is_post' ] ) {

				if ( $mod[ 'is_post_type_archive' ] ) {

					$url = $this->check_url_string( get_post_type_archive_link( $mod[ 'post_type' ] ), 'post_type_archive' );

				} elseif ( ! empty( $mod[ 'id' ] ) ) {	// Just in case.

					if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

						$url = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $type . '_url' );	// Returns null if an index key is not found.
					}

					if ( ! empty( $url ) ) {	// Must be a non-empty string.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom post ' . $type . '_url = ' . $url );
						}

					} elseif ( $mod[ 'post_status' ] !== 'publish' ) {

						$post_obj = self::get_post_object( $mod[ 'id' ], $output = 'object' );

						if ( is_object( $post_obj ) ) {

							if ( ! is_wp_error( $post_obj ) ) {

								$post_obj->post_status = 'publish';

								if ( empty( $post_obj->post_name ) ) {
								
									$post_obj->post_name = sanitize_title( $post_obj->post_title );
								}

								$url = get_permalink( $post_obj );
							}
						}

						if ( empty( $url ) ) {

							$url = get_permalink( $mod[ 'id' ] );
						}

					} else {

						$url = get_permalink( $mod[ 'id' ] );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'get_permalink url = ' . $url );
						}

						$url = $this->check_url_string( $url, 'post permalink' );
					}

					if ( $add_page && get_query_var( 'page' ) > 1 && ! empty( $url ) ) {

						global $wp_rewrite;

						$post_obj = self::get_post_object( $mod[ 'id' ] );

						$numpages = substr_count( $post_obj->post_content, '<!--nextpage-->' ) + 1;

						if ( $numpages && get_query_var( 'page' ) <= $numpages ) {

							if ( ! $wp_rewrite->using_permalinks() || false !== strpos( $url, '?' ) ) {

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

				$url = apply_filters( $this->p->lca . '_post_url', $url, $mod, $add_page );

			} elseif ( $mod[ 'is_home' ] ) {

				if ( 'page' === get_option( 'show_on_front' ) ) {	// Show_on_front = posts | page.

					$url = $this->check_url_string( get_permalink( get_option( 'page_for_posts' ) ), 'page for posts' );

				} else {

					$url = apply_filters( $this->p->lca . '_home_url', home_url( '/' ), $mod, $add_page );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'home url = ' . $url );
					}
				}

			} elseif ( $mod[ 'is_term' ] ) {

				if ( ! empty( $mod[ 'id' ] ) ) {

					if ( ! empty( $mod[ 'obj' ] ) ) {

						$url = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $type . '_url' );	// Returns null if an index key is not found.
					}

					if ( ! empty( $url ) ) {	// Must be a non-empty string.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom term ' . $type . '_url = ' . $url );
						}

					} else {

						$url = $this->check_url_string( get_term_link( $mod[ 'id' ], $mod[ 'tax_slug' ] ), 'term link' );
					}
				}

				$url = apply_filters( $this->p->lca . '_term_url', $url, $mod, $add_page );

			} elseif ( $mod[ 'is_user' ] ) {

				if ( ! empty( $mod[ 'id' ] ) ) {

					if ( ! empty( $mod[ 'obj' ] ) ) {

						$url = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $type . '_url' );	// Returns null if an index key is not found.
					}

					if ( ! empty( $url ) ) {	// Must be a non-empty string.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom user ' . $type . '_url = ' . $url );
						}

					} else {

						$url = get_author_posts_url( $mod[ 'id' ] );

						$url = $this->check_url_string( $url, 'author posts' );
					}
				}

				$url = apply_filters( $this->p->lca . '_user_url', $url, $mod, $add_page );

			/**
			 * $mod[ 'is_search' ] = true will return the search page URL.
			 *
			 * $mod[ 'is_search' ] = false will skip this section, even if is_search() is true.
			 */
			} elseif ( ! empty( $mod[ 'is_search' ] ) || ( ! isset( $mod[ 'is_search' ] ) && is_search() ) ) {

				$url = $this->check_url_string( get_search_link(), 'search link' );

				$url = apply_filters( $this->p->lca . '_search_url', $url, $mod, $add_page );

			} elseif ( ! empty( $mod[ 'is_archive' ] ) || ( ! isset( $mod[ 'is_archive' ] ) && self::is_archive_page() ) ) {

				if ( ! empty( $mod[ 'is_date' ] ) || ( ! isset( $mod[ 'is_date' ] ) && is_date() ) ) {

					if ( ! empty( $mod[ 'is_year' ] ) || ( ! isset( $mod[ 'is_year' ] ) && is_year() ) ) {
	
						$url = $this->check_url_string( get_year_link( get_query_var( 'year' ) ), 'year link' );

					} elseif ( ! empty( $mod[ 'is_month' ] ) || ( ! isset( $mod[ 'is_month' ] ) && is_month() ) ) {

						$url = $this->check_url_string( get_month_link( get_query_var( 'year' ),
							get_query_var( 'monthnum' ) ), 'month link' );

					} elseif ( ! empty( $mod[ 'is_day' ] ) || ( ! isset( $mod[ 'is_day' ] ) && is_day() ) ) {

						$url = $this->check_url_string( get_day_link( get_query_var( 'year' ),
							get_query_var( 'monthnum' ), get_query_var( 'day' ) ), 'day link' );
					}
				}

				$url = apply_filters( $this->p->lca . '_archive_page_url', $url, $mod, $add_page );

			} else {

				$url = $this->get_url_paged( $url, $mod, $add_page );
			}

			/**
			 * Use the current URL as a fallback for themes and plugins that create public content and don't use the
			 * standard WordPress functions / variables and/or are not properly integrated with WordPress (don't use
			 * custom post types, taxonomies, terms, etc.).
			 */
			if ( empty ( $url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'falling back to request url' );
				}

				$url = $this->get_request_url( $mod, $add_page );
			}

			/**
			 * Maybe enforce the FORCE_SSL constant.
			 */
			if ( strpos( $url, '://' ) ) {	// Only check URLs with a protocol.
				
				if ( self::get_const( 'FORCE_SSL' ) && ! self::is_https( $url ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'force ssl is enabled - replacing http by https' );
					}

					$url = set_url_scheme( $url, 'https' );
				}
			}

			$url = apply_filters( $this->p->lca . '_' . $type . '_url', $url, $mod, $add_page );

			if ( ! empty( $cache_salt ) ) {

				$local_cache[ $cache_salt ] = $url;
			}

			return $url;
		}

		private function get_request_url( $mod, $add_page ) {

			$url = self::get_prot() . '://' . $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REQUEST_URI' ];

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'server request url = ' . $url );
			}

			/**
			 * Remove tracking query arguments used by facebook, google, etc.
			 */
			$url = preg_replace( '/([\?&])(' .
				'fb_action_ids|fb_action_types|fb_source|fb_aggregation_id|' . 
				'utm_source|utm_medium|utm_campaign|utm_term|utm_content|' .
				'gclid|pk_campaign|pk_kwd' .
				')=[^&]*&?/i', '$1', $url );

			$url = apply_filters( $this->p->lca . '_server_request_url', $url, $mod, $add_page );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'server request url (filtered) = ' . $url );
			}

			/**
			 * Disable transient cache and URL shortening if the URL contains a query argument.
			 */
			if ( false !== strpos( $url, '?' ) ) {

				$cache_disabled = true;

			} else {

				$cache_disabled = false;
			}

			if ( apply_filters( $this->p->lca . '_server_request_url_cache_disabled', $cache_disabled, $url, $mod, $add_page ) ) {

				$this->disable_cache_filters( array( 'shorten_url_disabled' => '__return_true' ) );
			}

			return $url;
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

					if ( $mod[ 'is_home_page' ] ) {	// Static home page (have post id).

						$base = $wp_rewrite->using_index_permalinks() ? 'index.php/' : '/';
						$url  = home_url( $base );

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

				return $url;	// Stop here.
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
		 * Called by scheduled tasks to check the user ID value and possibly load a different textdomain language.
		 */
		public function maybe_change_user_id( $user_id ) {

			$current_user_id = get_current_user_id();	// Always returns an integer.

			$user_id = is_numeric( $user_id ) ? (int) $user_id : $current_user_id;	// // User ID can be true, false, null, or a number.

			if ( empty( $user_id ) ) {	// User ID is 0 (cron user, for example).

				return $user_id;

			} elseif ( $user_id === $current_user_id ) {	// Nothing to do.

				return $user_id;
			}

			/**
			 * The user ID is different than the current / effective user ID, so check if the user locale is different
			 * to the current locale and load the user locale if required.
			 */
			$user_locale = get_user_meta( $user_id, 'locale', $single = true );

			$current_locale = get_locale();

			if ( ! empty( $user_locale ) && $user_locale !== $current_locale ) {

				$domain        = 'wpsso';
				$rel_path      = 'wpsso/languages/';
				$mofile        = $domain . '-' . $user_locale . '.mo';
				$wp_mopath     = WP_LANG_DIR . '/plugins/' . $mofile;
				$plugin_mopath = WP_PLUGIN_DIR . '/' . $rel_path . $mofile;

				/**
				 * Try to load from the WordPress languages directory first.
				 */
				if ( ! load_textdomain( $domain, $wp_mopath ) ) {

					load_textdomain( $domain, $plugin_mopath );
				}
			}

			return $user_id;
		}

		/**
		 * Used by WpssoMedia get_content_images() and get_attachment_image_src().
		 */
		public function fix_relative_url( $url ) {

			if ( empty( $url ) || false !== strpos( $url, '://' ) ) {

				return $url;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'relative url found = ' . $url );
			}

			if ( 0 === strpos( $url, '//' ) ) {	// Example: //host.com/dir/file/

				$url = self::get_prot() . ':' . $url;

			} elseif ( 0 === strpos( $url, '/' ) )  {	// Example: /dir/file/

				$url = home_url( $url );

			} else {	// Example: file/

				$base = self::get_prot() . '://' . $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REQUEST_URI' ];

				if ( false !== strpos( $base, '?' ) ) {

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

				if ( isset( $this->cache_uniq_urls[ $context ] ) ) {

					$cleared += count( $this->cache_uniq_urls[ $context ] );
				}

				$this->cache_uniq_urls[ $context ] = array();

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'cleared uniq url cache for context ' . $context );
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

			$url = $this->fix_relative_url( $url );	// Just in case.

			if ( ! isset( $this->cache_uniq_urls[ $context ][ $url ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'uniq url saved for context ' . $context . ': ' . $url );
				}

				return $this->cache_uniq_urls[ $context ][ $url ] = true;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'duplicate url found for context ' . $context . ': ' . $url );
			}

			return false;
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

		public function merge_max( &$dst, &$src, $num = 0 ) {

			if ( ! is_array( $dst ) || ! is_array( $src ) ) {

				return false;
			}

			if ( ! empty( $src ) && array_filter( $src ) ) {

				$dst = array_merge( $dst, $src );
			}

			return $this->slice_max( $dst, $num );	// Returns true or false.
		}

		public function push_max( &$dst, &$src, $num = 0 ) {

			if ( ! is_array( $dst ) || ! is_array( $src ) ) {

				return false;
			}

			if ( ! empty( $src ) && array_filter( $src ) ) {

				array_push( $dst, $src );
			}

			return $this->slice_max( $dst, $num );	// Returns true or false.
		}

		public function slice_max( &$arr, $num = 0 ) {

			if ( ! is_array( $arr ) ) {

				return false;
			}

			$has_count = count( $arr );

			if ( $num > 0 ) {

				if ( $has_count == $num ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'max values reached (' . $has_count . ' == ' . $num . ')' );
					}

					return true;

				} elseif ( $has_count > $num ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'max values reached (' . $has_count . ' > ' . $num . ') - slicing array' );
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
		public function get_max_nums( array $mod, $opt_pre = 'og' ) {

			$max_nums = array();

			$max_opt_keys = array( $opt_pre . '_vid_max', $opt_pre . '_img_max' );

			foreach ( $max_opt_keys as $opt_key ) {

				if ( ! empty( $mod[ 'id' ] ) && ! empty( $mod[ 'obj' ] ) ) {

					$max_val = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $opt_key );	// Returns null if an index key is not found.

				} else {

					$max_val = null;	// Default value if index key is missing.
				}

				/**
				 * Quick sanitation of returned value.
				 */
				if ( $max_val !== null & is_numeric( $max_val ) && $max_val >= 0 ) {

					$max_nums[ $opt_key ] = $max_val;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found custom meta ' . $opt_key . ' = ' . $max_val );
					}

				} else {

					$max_nums[ $opt_key ] = isset( $this->p->options[ $opt_key ] ) ? $this->p->options[ $opt_key ] : 0;
				}
			}

			return $max_nums;
		}

		public function safe_apply_filters( array $args, array $mod = array(), $mtime_max = 0, $use_bfo = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Check for required apply_filters() arguments.
			 */
			if ( empty( $args[ 0 ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: filter name missing from parameter array' );
				}

				return '';

			} elseif ( ! isset( $args[ 1 ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: filter value missing from parameter array' );
				}

				return '';
			}

			$filter_name  = $args[ 0 ];
			$filter_value = $args[ 1 ];

			if ( false === has_filter( $filter_name ) ) {	// Skip if no filters.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . $filter_name . ' has no filter hooks' );
				}

				return $filter_value;
			}

			/**
			 * Prevent recursive loops - the global variable is defined before applying the filters.
			 */
			if ( ! empty( $GLOBALS[ $this->p->lca . '_doing_filter_' . $filter_name ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: global variable ' . 
						$this->p->lca . '_doing_filter_' . $filter_name . ' is true' );
				}

				return $filter_value;
			}

			/**
			 * Hooked by some modules, like bbPress and social sharing buttons, to perform actions before/after
			 * filtering the content.
			 */
			do_action( $this->p->lca . '_pre_apply_filters_text', $filter_name );

			/**
			 * Load the Block Filter Output (BFO) filters to block and show an error for incorrectly coded filters.
			 */
			if ( $use_bfo ) {

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
			if ( ! empty( $mod[ 'is_post' ] ) ) {
			
				if ( ! empty( $mod[ 'id' ] ) ) {

					if ( ! isset( $post->ID ) || $post->ID !== $mod[ 'id' ] ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'resetting post object from mod id ' . $mod[ 'id' ] );
						}
	
						$post = self::get_post_object( $mod[ 'id' ] );	// Redefine the $post global.

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'post object id matches the post mod id' );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'setting post data for template functions' );
			}

			setup_postdata( $post );

			/**
			 * Prevent recursive loops and signal to other methods that the content filter is being applied to create a
			 * description text - this avoids the addition of unnecessary HTML which will be removed anyway (social
			 * sharing buttons, for example).
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'setting global ' . $this->p->lca . '_doing_filter_' . $filter_name );
			}

			$GLOBALS[ $this->p->lca . '_doing_filter_' . $filter_name ] = true;	// Prevent recursive loops.

			/**
			 * Apply the filters.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'applying WordPress ' . $filter_name . ' filters' );	// Begin timer.
			}

			$mtime_start  = microtime( true );
			$filter_value = call_user_func_array( 'apply_filters', $args );
			$mtime_total  = microtime( true ) - $mtime_start;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'applying WordPress ' . $filter_name . ' filters' );	// End timer.
			}

			/**
			 * Unset the recursive loop check.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'unsetting global ' . $this->p->lca . '_doing_filter_' . $filter_name );
			}

			unset( $GLOBALS[ $this->p->lca . '_doing_filter_' . $filter_name ] );	// Un-prevent recursive loops.

			/**
			 * Issue warning for slow filter performance.
			 */
			if ( $mtime_max > 0 && $mtime_total > $mtime_max ) {

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

				$info = $this->p->cf[ 'plugin' ][ $this->p->lca ];

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( sprintf( 'slow filter hook(s) detected - WordPress took %1$0.3f secs to execute the "%2$s" filter',
						$mtime_total, $filter_name ) );
				}

				$error_pre = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );

				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $mtime_max );

				$error_msg = sprintf( __( 'Slow filter hook(s) detected - WordPress took %1$0.3f secs to execute the "%2$s" filter (%3$s).',
					'wpsso' ), $mtime_total, $filter_name, $rec_max_msg );

				/**
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					if ( $is_wp_filter ) {

						$filter_api_link = '<a href="https://codex.wordpress.org/Plugin_API/Filter_Reference/' . $filter_name . '">' .
							$filter_name . '</a>';

						$query_monitor_link = '<a href="https://wordpress.org/plugins/query-monitor/">Query Monitor</a>';

						$notice_msg = sprintf( __( 'Slow filter hook(s) detected &mdash; the WordPress %1$s filter took %2$0.3f seconds to execute. This is longer than the recommended maximum of %3$0.3f seconds and may affect page load time. Please consider reviewing 3rd party plugin and theme functions hooked into the WordPress %1$s filter for slow and/or sub-optimal PHP code.', 'wpsso' ), $filter_api_link, $mtime_total, $mtime_max ) . ' ';
						
						$notice_msg .= sprintf( __( 'Activating the %1$s plugin and clearing the %2$s cache (to re-apply the filter) may provide more information on the specific hook(s) or PHP code affecting performance.', 'wpsso' ), $query_monitor_link, $info[ 'short' ] );
						
						$notice_key = 'slow-filter-hooks-detected-' . $filter_name;

						$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = WEEK_IN_SECONDS );

					} else {

						$this->p->notice->warn( $error_msg );
					}
				}

				self::safe_error_log( $error_pre . ' ' . $error_msg );
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
			if ( $use_bfo ) {

				$bfo_obj->remove_all_hooks( array( $filter_name ) );
			}

			/**
			 * Hooked by some modules, like bbPress and social sharing buttons, to perform actions before/after
			 * filtering the content.
			 */
			do_action( $this->p->lca . '_after_apply_filters_text', $filter_name );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning filtered value' );
			}

			return $filter_value;
		}

		public function get_admin_url( $menu_id = '', $link_text = '', $menu_lib = '' ) {

			$hash      = '';
			$query     = '';
			$admin_url = '';

			/**
			 * $menu_id may start with a hash or query, so parse before checking its value.
			 */
			if ( false !== strpos( $menu_id, '#' ) ) {

				list( $menu_id, $hash ) = explode( '#', $menu_id );
			}

			if ( false !== strpos( $menu_id, '?' ) ) {

				list( $menu_id, $query ) = explode( '?', $menu_id );
			}

			if ( empty( $menu_id ) ) {

				$current = $_SERVER[ 'REQUEST_URI' ];

				if ( preg_match( '/^.*\?page=' . $this->p->lca . '-([^&]*).*$/', $current, $match ) ) {

					$menu_id = $match[ 1 ];

				} else {

					$menu_id = key( $this->p->cf[ '*' ][ 'lib' ][ 'submenu' ] );	// Default to first submenu.
				}
			}

			/**
			 * Find the menu_lib value for this menu_id.
			 */
			if ( empty( $menu_lib ) ) {

				foreach ( $this->p->cf[ '*' ][ 'lib' ] as $menu_lib => $menu ) {

					if ( isset( $menu[ $menu_id ] ) ) {

						break;

					} else {

						$menu_lib = '';
					}
				}
			}

			if ( empty( $menu_lib ) || empty( $this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'page' ] ) ) {

				return;
			}

			$parent_slug = $this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'page' ] . '?page=' . $this->p->lca . '-' . $menu_id;

			switch ( $menu_lib ) {

				case 'sitesubmenu':

					$admin_url = network_admin_url( $parent_slug );

					break;

				default:

					$admin_url = admin_url( $parent_slug );

					break;
			}

			if ( ! empty( $query ) ) {

				$admin_url .= '&' . $query;
			}

			if ( ! empty( $hash ) ) {

				$admin_url .= '#' . $hash;
			}

			if ( empty( $link_text ) ) {

				return $admin_url;

			}

			return '<a href="' . $admin_url . '">' . $link_text . '</a>';
		}

		/**
		 * Deprecated on 2020/07/07.
		 */
		public function do_metabox_tabbed( $metabox_id = '', $tabs = array(), $table_rows = array(), $args = array() ) {

			echo $this->metabox->get_tabbed( $metabox_id, $tabs, $table_rows, $args );
		}

		/**
		 * Deprecated on 2020/07/07.
		 */
		public function do_metabox_table( $table_rows, $class_href_key = '', $class_tabset_mb = '', $class_tabset = 'sucom-no_tabset', $title_transl = '' ) {

			echo $this->metabox->get_table( $table_rows, $class_href_key, $class_tabset_mb, $class_tabset, $title_transl );
		}

		/**
		 * Rename settings array keys, preserving the option modifiers (:is|:use|#.*|_[0-9]+).
		 */
		public function rename_opts_by_ext( &$opts, $options_keys ) {

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! isset( $options_keys[ $ext ] ) || ! is_array( $options_keys[ $ext ] ) ||
					! isset( $info[ 'opt_version' ] ) || empty( $opts[ 'plugin_' . $ext . '_opt_version' ] ) ) {

					continue;
				}

				foreach ( $options_keys[ $ext ] as $max_version => $keys ) {

					if ( is_numeric( $max_version ) && is_array( $keys ) && $opts[ 'plugin_' . $ext . '_opt_version' ] <= $max_version ) {

						self::rename_keys( $opts, $keys, $modifiers = true );

						$opts[ 'plugin_' . $ext . '_opt_version' ] = $info[ 'opt_version' ];	// Mark as current.
					}
				}
			}
		}

		/**
		 * limit_text_length() uses PHP's multibyte functions (mb_strlen and mb_substr) for UTF8.
		 */
		public function limit_text_length( $text, $maxlen = 300, $trailing = '', $cleanup_html = true ) {

			if ( true === $cleanup_html ) {

				$text = $this->cleanup_html_tags( $text );				// Remove any remaining html tags.
			}

			$charset = get_bloginfo( 'charset' );

			$text = html_entity_decode( self::decode_utf8( $text ), ENT_QUOTES, $charset );

			if ( $maxlen > 0 && function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {

				if ( mb_strlen( $trailing ) > $maxlen ) {

					$trailing = mb_substr( $trailing, 0, $maxlen );			// Trim the trailing string, if too long.
				}

				if ( mb_strlen( $text ) > $maxlen ) {

					$text = mb_substr( $text, 0, $maxlen - mb_strlen( $trailing ) );
					$text = trim( preg_replace( '/[^ ]*$/', '', $text ) );		// Remove trailing bits of words.
					$text = preg_replace( '/[,\.]*$/', '', $text );			// Remove trailing puntuation.

				} else {
					$trailing = '';							// Truncate trailing string if text is less than maxlen.
				}

				$text = $text . $trailing;						// Trim and add trailing string (if provided).
			}

			$text = preg_replace( '/&nbsp;/', ' ', $text );					// Just in case.

			return $text;
		}

		public function cleanup_html_tags( $text, $strip_tags = true, $use_img_alt = false ) {

			$alt_text = '';

			$alt_prefix = isset( $this->p->options[ 'plugin_img_alt_prefix' ] ) ?
				$this->p->options[ 'plugin_img_alt_prefix' ] : 'Image:';

			$text = self::strip_shortcodes( $text );					// Remove any remaining shortcodes.
			$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );				// Put everything on one line.
			$text = preg_replace( '/<\?.*\?'.'>/U', ' ', $text );				// Remove php.
			$text = preg_replace( '/<script\b[^>]*>(.*)<\/script>/Ui', ' ', $text );	// Remove javascript.
			$text = preg_replace( '/<style\b[^>]*>(.*)<\/style>/Ui', ' ', $text );		// Remove inline stylesheets.

			/**
			 * Maybe remove text between ignore markers.
			 */
			if ( false !== strpos( $text, $this->p->lca . '-ignore' ) ) {

				$text = preg_replace( '/<!-- *' . $this->p->lca . '-ignore *-->.*<!-- *\/' . $this->p->lca . '-ignore *-->/U', ' ', $text );
			}

			/**
			 * Similar to SucomUtil::strip_html(), but includes image alt tags.
			 */
			if ( $strip_tags ) {

				/**
				 * Add missing dot to buttons, headers, lists, etc.
				 */
				$text = preg_replace( '/([\w])<\/(button|dt|h[0-9]+|li|th)>/i', '$1. ', $text );

				/**
				 * Replace paragraph tags with a space.
				 */
				$text = preg_replace( '/(<p>|<p[^>]+>|<\/p>)/i', ' ', $text );

				/**
				 * Remove remaining html tags.
				 */
				$text_stripped = trim( strip_tags( $text ) );

				/**
				 * Possibly use img alt strings if no text.
				 */
				if ( $text_stripped === '' && $use_img_alt ) {

					if ( false !== strpos( $text, '<img ' ) &&
						preg_match_all( '/<img [^>]*alt=["\']([^"\'>]*)["\']/Ui', $text, $all_matches, PREG_PATTERN_ORDER ) ) {

						foreach ( $all_matches[ 1 ] as $alt ) {

							$alt = trim( $alt );

							if ( ! empty( $alt ) ) {
							
								$alt = empty( $alt_prefix ) ? $alt : $alt_prefix . ' ' . $alt;

								/**
								 * Add a period after the image alt text if missing.
								 */
								$alt_text .= ( strpos( $alt, '.' ) + 1 ) === strlen( $alt ) ? $alt . ' ' : $alt . '. ';
							}
						}

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'img alt text: ' . $alt_text );
						}
					}

					$text = $alt_text;

				} else {
					$text = $text_stripped;
				}
			}

			/**
			 * Replace 1+ spaces to a single space.
			 */
			$text = preg_replace( '/(\xC2\xA0|\s)+/s', ' ', $text );

			return trim( $text );
		}

		/**
		 * See https://developers.google.com/search/reference/robots_meta_tag.
		 */
		public function get_robots_content( array $mod ) {

			$directives = self::get_robots_default_directives();

			if ( $mod[ 'id' ] && is_object( $mod[ 'obj' ] ) ) {

				foreach ( array(
					'noarchive'    => array(),
					'nofollow'     => array( 'follow' => true ),
					'noimageindex' => array( 'max-image-preview' => 'large' ),
					'noindex'      => array( 'index' => true ),
					'nosnippet'    => array( 'max-snippet' => -1, 'max-video-preview' => -1 ),
					'notranslate'  => array(),
				) as $directive => $inverse ) {

					$meta_key = '_' . $this->p->lca . '_' . $directive;

					/**
					 * Returns '0', '1', or empty string.
					 */
					$meta_value = $mod[ 'obj' ]->get_meta_cache_value( $mod[ 'id' ], $meta_key );	// Always returns a string.

					if ( '' !== $meta_value ) {

						$directives[ $directive ] = $meta_value ? true : false;

						foreach ( $inverse as $inverse_directive => $inverse_value ) {

							$directives[ $inverse_directive ] = $meta_value ? false : $inverse_value;
						}
					}
				}
			}

			$content = '';

			foreach ( $directives as $directive => $val ) {

				if ( false === $val ) {

					continue;

				} elseif ( true === $val ) {

					$content .= $directive . ', ';	// Noindex, nofollow, etc.

				} else {

					$content .= $directive . ':' . $val . ', ';	// Max-image-preview, max-video-preview, etc.
				}
			}

			$content = trim( $content, ', ' );

			return apply_filters( $this->p->lca . '_robots_content', $content, $mod, $directives );
		}

		/**
		 * Returns an array of product attribute names, indexed by meta tag name ($sep = ":") or option name ($sep = "_").
		 *
		 * Example $prefix = "product" and $sep = ":" for meta tag names:
		 *
		 * 	Array(
		 *		[product:brand]              => Brand
		 *		[product:color]              => Color
		 *		[product:condition]          => Condition
		 *		[product:gtin14]             => GTIN-14
		 *		[product:gtin14]             => GTIN-13
		 *		[product:gtin14]             => GTIN-12
		 *		[product:gtin8]              => GTIN-8
		 *		[product:material]           => Material
		 *		[product:mfr_part_no]        => MPN
		 *		[product:size]               => Size
		 *		[product:target_gender]      => Gender
		 *		[product:fluid_volume:value] => Volume
		 *	)
		 *
		 * Example $prefix = "product" and $sep = "_" for option names:
		 *
		 * 	Array(
		 *		[product_brand]              => Brand
		 *		[product_color]              => Color
		 *		[product_condition]          => Condition
		 *		[product_gtin14]             => GTIN-14
		 *		[product_gtin14]             => GTIN-13
		 *		[product_gtin14]             => GTIN-12
		 *		[product_gtin8]              => GTIN-8
		 *		[product_material]           => Material
		 *		[product_mfr_part_no]        => MPN
		 *		[product_size]               => Size
		 *		[product_target_gender]      => Gender
		 *		[product_fluid_volume_value] => Volume
		 *	)
		 */
		public function get_product_attr_names( $prefix = 'product', $sep = ':' ) {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array();

				foreach ( $this->p->cf[ 'form' ][ 'attr_labels' ] as $key => $label ) {

					if ( 0 === strpos( $key, 'plugin_attr_product_' ) ) {	// Only use product attributes.

						$attr_name = SucomUtil::get_key_value( $key, $this->p->options );

						if ( empty( $attr_name ) ) {	// Skip attributes that have no associated name.

							continue;
						}

						$key = preg_replace( '/^plugin_attr_product_/', '', $key );

						$local_cache[ $key ] = $attr_name;
					}
				}

				$local_cache = apply_filters( $this->p->lca . '_product_attribute_names', $local_cache );
			}

			/**
			 * No prefix, so no separator required.
			 */
			if ( empty( $prefix ) ) {

				return $local_cache;
			}

			$attr_names = array();

			foreach ( $local_cache as $key => $val ) {

				if ( $sep !== '_' ) {

					$key = preg_replace( '/_(value|units)$/', $sep . '$1', $key );
				}

				$attr_names[ $prefix . $sep . $key ] = $val;
			}

			return $attr_names;
		}

		public function maybe_set_ref( $sharing_url = null, $mod = false, $msg_transl = '' ) {

			static $is_admin = null;

			if ( null === $is_admin ) {

				$is_admin = is_admin();
			}

			if ( ! $is_admin ) {

				return false;
			}

			if ( empty( $sharing_url ) ) {

				$sharing_url = $this->get_sharing_url( $mod );
			}

			if ( empty( $msg_transl ) ) {

				return $this->p->notice->set_ref( $sharing_url, $mod );
			}
			
			if ( empty( $mod[ 'id' ] ) ) {

				return $this->p->notice->set_ref( $sharing_url, $mod, $msg_transl );
			}

			if ( $mod[ 'is_post' ] && $mod[ 'post_type_label' ] ) {

				$name = mb_strtolower( $mod[ 'post_type_label' ] );

			} elseif ( $mod[ 'is_term' ] && $mod[ 'tax_label' ] ) {

				$name = mb_strtolower( $mod[ 'tax_label' ] );
			} else {
				$name = $mod[ 'name_transl' ];
			}

			if ( self::is_mod_current_screen( $mod ) ) {

				// translators: %1$s is an action message, %2$s is the module or post type name.
				$msg_transl = sprintf( __( '%1$s for this %2$s', 'wpsso' ), $msg_transl, $name );

				/**
				 * Exclude the $mod array to avoid adding an 'Edit' link to the notice message.
				 */
				return $this->p->notice->set_ref( $sharing_url, false, $msg_transl );

			}

			// translators: %1$s is an action message, %2$s is the module or post type name and %3$d is the object ID.
			$msg_transl = sprintf( __( '%1$s for %2$s ID %3$d', 'wpsso' ), $msg_transl, $name, $mod[ 'id' ] );
			
			return $this->p->notice->set_ref( $sharing_url, $mod, $msg_transl );
		}

		public function maybe_unset_ref( $sharing_url ) {

			static $is_admin = null;

			if ( null === $is_admin ) {

				$is_admin = is_admin();
			}

			if ( ! $is_admin ) {

				return false;
			}

			return $this->p->notice->unset_ref( $sharing_url );
		}

		public function get_cache_exp_secs( $md5_pre, $cache_type = 'transient', $def_secs = MONTH_IN_SECONDS, $min_secs = 0 ) {

			static $local_cache = array();

			if ( empty( $md5_pre ) || empty( $cache_type ) ) {	// Just in case.

				return $def_secs;

			} elseif ( isset( $local_cache[ $md5_pre ][ $cache_type ] ) ) {

				return $local_cache[ $md5_pre ][ $cache_type ];
			}
			
			if ( ! empty( $this->p->cf[ 'wp' ][ $cache_type ][ $md5_pre ][ 'opt_key' ] ) ) {	// Just in case.

				$opt_key = $this->p->cf[ 'wp' ][ $cache_type ][ $md5_pre ][ 'opt_key' ];

				$exp_secs = isset( $this->p->options[ $opt_key ] ) ? $this->p->options[ $opt_key ] : $def_secs;

			} else {
				$exp_secs = $def_secs;
			}

			if ( ! empty( $this->p->cf[ 'wp' ][ $cache_type ][ $md5_pre ][ 'filter' ] ) ) {	// Just in case.

				$exp_filter = $this->p->cf[ 'wp' ][ $cache_type ][ $md5_pre ][ 'filter' ];

				$exp_secs = (int) apply_filters( $exp_filter, $exp_secs );
			}

			if ( $exp_secs < $min_secs ) {

				$exp_secs = $def_secs;
			}

			return $local_cache[ $md5_pre ][ $cache_type ] = $exp_secs;
		}

		/**
		 * Returns for example "#sso-post-123", #sso-term-123-tax-faq-category with a $mod array or "#sso-" without.
		 *
		 * Called by:
		 *
		 *	WpssoFaqShortcodeFaq->do_shortcode()
		 *	WpssoFaqShortcodeQuestion->do_shortcode()
		 *	WpssoJsonFiltersTypeThing->filter_json_data_https_schema_org_thing()
		 */
		public static function get_fragment_anchor( $mod = null ) {

			return '#sso-' . ( $mod ? self::get_mod_anchor( $mod ) : '' );
		}
	}
}
