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

if ( ! class_exists( 'SucomUtil' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/com/util.php';	// Loads the SucomUtilWP class.
}

if ( ! class_exists( 'WpssoUtil' ) ) {

	class WpssoUtil extends SucomUtil {

		private $p;	// Wpsso class object.

		private $cache_size_labels = array();	// Array for image size labels.
		private $cache_size_opts   = array();	// Array for image size option prefix.
		private $cache_uniq_urls   = array();	// Array to detect duplicate image URLs.

		private $is_functions = array(
			'is_admin',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_category',
			'is_customize_preview',
			'is_front_page',
			'is_home',
			'is_multisite',
			'is_page',
			'is_post_type_archive',
			'is_preview',
			'is_rtl',
			'is_search',
			'is_single',
			'is_singular',
			'is_ssl',
			'is_tag',
			'is_tax',

			/*
			 * Common e-commerce functions.
			 */
			'is_account_page',
			'is_cart',
			'is_checkout',
			'is_checkout_pay_page',
			'is_product',
			'is_product_category',
			'is_product_tag',
			'is_shop',

			/*
			 * Other functions.
			 */
			'is_amp_endpoint',
			'is_sitemap',
			'is_sitemap_stylesheet',
			'wp_using_ext_object_cache',
		);

		public $blocks;		// WpssoUtilBlocks.
		public $cache;		// WpssoUtilCache.
		public $cf;		// WpssoUtilCustomFields.
		public $inline;		// WpssoUtilInline.
		public $metabox;	// WpssoUtilMetabox.
		public $reg;		// WpssoUtilReg.
		public $robots;		// WpssoUtilRobots.
		public $units;		// WpssoUtilUnits.
		public $wc;		// WpssoUtilWooCommerce.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->set_util_instances( $plugin );

			$this->add_plugin_actions( $this, array(
				'scheduled_task_started' => 1,
				'show_admin_notices'     => 1,
			), $prio = -1000 );

			/*
			 * Log the locale change and clear the Sucom::get_locale() cache.
			 */
			add_action( 'change_locale', array( $this, 'wp_locale_changed' ), -100, 1 );
			add_action( 'switch_locale', array( $this, 'wp_locale_switched' ), -100, 1 );
			add_action( 'restore_previous_locale', array( $this, 'wp_locale_restored' ), -100, 2 );

			/*
			 * Add our image sizes on the front-end, back-end, AJAX calls, and REST API calls.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding WpssoUtil->add_plugin_image_sizes() action hooks' );
			}

			add_action( 'wp', array( $this, 'add_plugin_image_sizes' ), -100 );		// Front-end compatibility.
			add_action( 'admin_init', array( $this, 'add_plugin_image_sizes' ), -100 );	// Back-end + AJAX compatibility.
			add_action( 'rest_api_init', array( $this, 'add_plugin_image_sizes' ), -100 );	// REST API compatibility.
		}

		public function set_util_instances( &$plugin ) {

			/*
			 * Instantiate WpssoUtilBlocks.
			 */
			if ( ! class_exists( 'WpssoUtilBlocks' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-blocks.php';
			}

			$this->blocks = new WpssoUtilBlocks( $plugin, $this );

			/*
			 * Instantiate WpssoUtilCache.
			 */
			if ( ! class_exists( 'WpssoUtilCache' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-cache.php';
			}

			$this->cache = new WpssoUtilCache( $plugin, $this );

			/*
			 * Instantiate WpssoUtilCustomFields.
			 */
			if ( ! class_exists( 'WpssoUtilCustomFields' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-custom-fields.php';
			}

			$this->cf = new WpssoUtilCustomFields( $plugin, $this );

			/*
			 * Instantiate WpssoUtilInline.
			 */
			if ( ! class_exists( 'WpssoUtilInline' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-inline.php';
			}

			$this->inline = new WpssoUtilInline( $plugin, $this );

			/*
			 * Instantiate WpssoUtilMetabox.
			 */
			if ( ! class_exists( 'WpssoUtilMetabox' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-metabox.php';
			}

			$this->metabox = new WpssoUtilMetabox( $plugin );

			/*
			 * Instantiate WpssoUtilReg.
			 */
			if ( ! class_exists( 'WpssoUtilReg' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-reg.php';
			}

			$this->reg = new WpssoUtilReg( $plugin );

			/*
			 * Instantiate WpssoUtilReg.
			 */
			if ( ! class_exists( 'WpssoUtilRobots' ) ) { // Since WPSSO Core v6.13.1.

				require_once WPSSO_PLUGINDIR . 'lib/util-robots.php';
			}

			$this->robots = new WpssoUtilRobots( $plugin );

			/*
			 * Instantiate WpssoUtilUnits.
			 */
			if ( ! class_exists( 'WpssoUtilUnits' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/util-units.php';
			}

			$this->units = new WpssoUtilUnits( $plugin );

			/*
			 * Instantiate WpssoUtilWooCommerce.
			 */
			if ( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) {

				if ( ! class_exists( 'WpssoUtilWooCommerce' ) ) {

					require_once WPSSO_PLUGINDIR . 'lib/util-woocommerce.php';
				}

				$this->wc = new WpssoUtilWooCommerce( $plugin );
			}
		}

		/*
		 * Add plugin image sizes before starting a background task, like refreshing the plugin cache.
		 */
		public function action_scheduled_task_started( $user_id ) {

			$this->add_plugin_image_sizes();
		}

		public function action_show_admin_notices( $user_id ) {
			
			if ( $task_name = $this->cache->doing_task() ) {

				$task_name_transl = _x( $task_name, 'task name', 'wpsso' );
				$notice_msg       = sprintf( __( 'A background task to %s is currently running.', 'wpsso' ), $task_name_transl );
				$notice_key       = $task_name . '-task-running';
				
				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}
		}

		/*
		 * Since WPSSO Core v11.7.2.
		 *
		 * Monitor the WordPress 'change_locale' action for locale changes.
		 *
		 * Log the locale change and clear the Sucom::get_locale() cache.
		 */
		public function wp_locale_changed( $locale ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'wp locale changed to ' . $locale );
			}

			SucomUtil::clear_locale_cache();
		}

		public function wp_locale_switched( $locale ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'wp locale switched to ' . $locale );
			}
		}

		public function wp_locale_restored( $locale, $previous_locale ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'wp locale restored to ' . $locale . ' from ' . $previous_locale );
			}
		}

		/*
		 * Can be called directly and from the "wp", "rest_api_init", and "current_screen" actions.
		 *
		 * This method does not return a value, so do not use it as a filter. ;-)
		 */
		public function add_plugin_image_sizes() {

			/*
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

				$this->p->debug->mark( 'define image sizes' );	// Begin timer.
			}

			/*
			 * Get default options only once.
			 */
			static $defs = null;

			$image_sizes = apply_filters( 'wpsso_plugin_image_sizes', array() );

			foreach( $image_sizes as $opt_prefix => $size_info ) {

				if ( ! is_array( $size_info ) ) {	// Just in case.

					continue;
				}

				foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $opt_suffix ) {

					/*
					 * Value provided by filters.
					 */
					if ( isset( $size_info[ $opt_suffix ] ) ) {

						continue;

					/*
					 * Plugin settings.
					 */
					} elseif ( isset( $this->p->options[ $opt_prefix . '_img_' . $opt_suffix ] ) ) {

						$size_info[ $opt_suffix ] = $this->p->options[ $opt_prefix . '_img_' . $opt_suffix ];

					/*
					 * Default settings.
					 */
					} else {

						if ( null === $defs ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'getting default option values' );
							}

							$defs = $this->p->opt->get_defaults();
						}

						if ( isset( $defs[ $opt_prefix . '_img_' . $opt_suffix ] ) ) {	// Just in case.

							$size_info[ $opt_suffix ] = $defs[ $opt_prefix . '_img_' . $opt_suffix ];

						} else {

							$size_info[ $opt_suffix ] = null;
						}
					}
				}

				if ( empty( $size_info[ 'crop' ] ) ) {

					$size_info[ 'crop' ] = false;

				} else {

					$size_info[ 'crop' ] = true;

					$new_crop = array( 'center', 'center' );

					foreach ( array( 'crop_x', 'crop_y' ) as $crop_key => $opt_suffix ) {

						if ( ! empty( $size_info[ $opt_suffix ] ) && $size_info[ $opt_suffix ] !== 'none' ) {

							$new_crop[ $crop_key ] = $size_info[ $opt_suffix ];
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

					$this->cache_size_labels[ 'wpsso-' . $size_info[ 'name' ] ] = $size_label;

					$this->cache_size_opts[ 'wpsso-' . $size_info[ 'name' ] ] = $opt_prefix;

					/*
					 * Add the image size.
					 */
					add_image_size( 'wpsso-' . $size_info[ 'name' ], $size_info[ 'width' ], $size_info[ 'height' ], $size_info[ 'crop' ] );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'added image size wpsso-' . $size_info[ 'name' ] . ' ' .
							$size_info[ 'width' ] . 'x' . $size_info[ 'height' ] .  ' ' . ( empty( $size_info[ 'crop' ] ) ?
								'uncropped' : 'cropped ' . $size_info[ 'crop_x' ] . '/' . $size_info[ 'crop_y' ] ) );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'define image sizes' );	// End timer.
			}
		}

		public function count_cron_jobs() {

			$count = 0;

			$cron_jobs = get_option( 'cron' );

			if ( is_array( $cron_jobs ) ) {

				foreach ( $cron_jobs as $cron_id => $cron_el ) {

					if ( is_array( $cron_el ) ) {

						foreach ( $cron_el as $sched_id => $sched_el ) {

							$count++;
						}
					}
				}
			}

			return $count;
		}

		/*
		 * Get the width, height and crop value for all image sizes.
		 *
		 * Returns an associative array with the image size name as the array key.
		 */
		public function get_image_sizes( $attachment_id = false ) {

			$image_sizes = array();

			foreach ( get_intermediate_image_sizes() as $size_name ) {

				$image_sizes[ $size_name ] = $this->get_size_info( $size_name, $attachment_id );
			}

			return $image_sizes;
		}

		/*
		 * Since WPSSO Core v14.5.0.
		 */
		public function is_size_cropped( $size_name = 'thumbnail', $attachment_id = false ) {

			$size_info = $this->get_size_info( $size_name, $attachment_id );

			return empty( $size_info[ 'is_cropped' ] ) ? false : true;
		}

		/*
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

			/*
			 * Standardize to true, false, or non-empty array.
			 */
			if ( empty( $crop ) ) {	// 0, false, null, or empty array.

				$crop = false;

			} elseif ( ! is_array( $crop ) ) {	// 1, or true.

				$crop = true;
			}

			/*
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

			/*
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

		/*
		 * Example $size_name = 'wpsso-opengraph' returns 'Open Graph' pre-translated.
		 */
		public function get_image_size_label( $size_name ) {

			if ( ! empty( $this->cache_size_labels[ $size_name ] ) ) {

				return $this->cache_size_labels[ $size_name ];

			}

			return $size_name;
		}

		/*
		 * Example $size_name = 'wpsso-opengraph' returns 'og'.
		 */
		public function get_image_size_opt( $size_name ) {

			if ( isset( $this->cache_size_opts[ $size_name ] ) ) {

				return $this->cache_size_opts[ $size_name ];

			}

			return '';
		}

		/*
		 * See WpssoMedia->get_all_images().
		 * See WpssoMedia->get_mt_pid_images().
		 * See WpssoUtil->clear_uniq_urls().
		 * See WpssoSscShortcodeSchema->do_shortcode().
		 */
		public function get_image_size_names( $mixed = null, $sanitize = true ) {

			$size_names = array_keys( $this->cache_size_labels );

			if ( null === $mixed ) {

				return $size_names;

			} elseif ( is_array( $mixed ) ) {

				return $sanitize ? array_intersect( $size_names, $mixed ) : $mixed;	// Sanitize and return.

			} elseif ( is_string( $mixed ) ) {

				switch ( $mixed ) {

					case 'opengraph':
					case 'pinterest':
					case 'thumbnail':

						return array( 'wpsso-' . $mixed );

					case 'schema':

						return array(
							'wpsso-schema-1x1',
							'wpsso-schema-4x3',
							'wpsso-schema-16x9',
						);

					default:

						return $sanitize ? array_intersect( $size_names, array( $mixed ) ) : array( $mixed );
				}
			}

			return array();
		}

		/*
		 * If this is an '_img_url' option, add the image dimensions and unset the '_img_id' option.
		 */
		public function maybe_add_img_url_size( array &$opts, $opt_key ) {

			/*
			 * Only process option keys with '_img_url' in their name.
			 */
			if ( false === strpos( $opt_key, '_img_url' ) ) {

				return;
			}

			$this->add_image_url_size( $opts, $opt_key );

			$count          = null;
			$img_id_key     = str_replace( '_img_url', '_img_id', $opt_key, $count );	// Image ID key.
			$img_id_lib_key = str_replace( '_img_url', '_img_id_lib', $opt_key );		// Image ID media library prefix key.

			if ( $count ) {	// Just in case.

				unset( $opts[ $img_id_key ] );
				unset( $opts[ $img_id_lib_key ] );
			}
		}

		/*
		 * If this is a '_value' option, add the '_units' option.
		 */
		public function maybe_add_md_key_units( array &$md_opts, $md_key ) {

			if ( false !== strpos( $md_key, '_value' ) ) {

				$count = null;

				$md_units_key = preg_replace( '/_value$/', '_units', $md_key, $limit = -1, $count );

				if ( $count ) {	// Just in case.

					$md_opts[ $md_units_key ] = WpssoUtilUnits::get_mixed_text( $md_units_key );

					$md_opts[ $md_units_key . ':disabled' ] = true;
				}
			}
		}

		public function maybe_renum_md_key( array &$md_opts, $md_key, array $values ) {

			/*
			 * Remove any old values from the options array.
			 */
			$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_key . '_[0-9]+$/', $md_opts, $invert = true );

			/*
			 * Renumber the options starting from 0.
			 */
			foreach ( $values as $num => $val ) {

				$md_num_key = $md_key . '_' . $num;

				$md_opts[ $md_num_key ] = $val;

				$md_opts[ $md_num_key . ':disabled' ] = true;

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'option ' . $md_num_key . ' = ' . print_r( $md_opts[ $md_num_key ], true ) );
				}
			}
		}

		/*
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

		/*
		 * Always returns an array.
		 *
		 * Note that WebP is only supported since PHP v7.1.
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

					$this->p->debug->log( 'exiting early: image URL is empty' );
				}

				return $local_cache[ $image_url ] = $def_image_info;	// Stop here.

			} elseif ( false === filter_var( $image_url, FILTER_VALIDATE_URL ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: invalid image url "' . $image_url . '"' );
				}

				return $local_cache[ $image_url ] = $def_image_info;	// Stop here.
			}

			$cache_md5_pre  = 'wpsso_i_';	// Transient prefix for image URL info.
			$cache_exp_secs = $this->get_cache_exp_secs( $cache_md5_pre );

			if ( $cache_exp_secs > 0 ) {

				/*
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

					/*
					 * Optimize and save the transient cache value to local cache.
					 */
					return $local_cache[ $image_url ] = $image_info;
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'transient cache for image info is disabled' );
			}

			$mtime_start = microtime( $get_float = true );

			/*
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
			$mtime_total = microtime( $get_float = true ) - $mtime_start;
			$mtime_max   = WPSSO_PHP_GETIMGSIZE_MAX_TIME;

			if ( $mtime_total > $mtime_max ) {

				$func_name   = 'getimagesize()';
				$error_pre   = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$.3f secs', 'wpsso' ), $mtime_max );
				$notice_msg  = sprintf( __( 'Slow PHP function detected - %1$s took %2$.3f secs for %3$s (%4$s).',
					'wpsso' ), '<code>' . $func_name . '</code>', $mtime_total, $image_url, $rec_max_msg );

				self::safe_error_log( $error_pre . ' ' . $notice_msg, $strip_html = true );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( sprintf( 'slow PHP function detected - %1$s took %2$.3f secs for %3$s',
						$func_name, $mtime_total, $image_url ) );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->warn( $notice_msg );
				}
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

		/*
		 * Called by several class __construct() methods to hook their filters.
		 */
		public function add_plugin_filters( $class, $filters, $prio = 10, $ext = '' ) {

			$this->add_plugin_hooks( $type = 'filter', $class, $filters, $prio, $ext );
		}

		/*
		 * Called by several class __construct() methods to hook their actions.
		 */
		public function add_plugin_actions( $class, $actions, $prio = 10, $ext = '' ) {

			$this->add_plugin_hooks( $type = 'action', $class, $actions, $prio, $ext );
		}

		/*
		 * $type = 'filter' or 'action'.
		 */
		private function add_plugin_hooks( $type, $class, $hook_list, $prio, $ext = '' ) {

			$ext = $ext === '' ? $this->p->id : $ext;

			foreach ( $hook_list as $name => $val ) {

				if ( ! is_string( $name ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $name . ' => ' . $val . ' ' . $type . ' skipped: filter name must be a string' );
					}

					continue;
				}

				/*
				 * Example:
				 *
				 * 	'json_data_https_schema_org_website' => 5
				 */
				if ( is_int( $val ) ) {

					$arg_nums    = $val;
					$hook_name   = self::sanitize_hookname( $ext . '_' . $name );
					$method_name = self::sanitize_hookname( $type . '_' . $name );

					if ( is_callable( array( &$class, $method_name ) ) ) {

						call_user_func( 'add_' . $type, $hook_name, array( &$class, $method_name ), $prio, $arg_nums );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'added ' . $method_name . ' method ' . $type, $class_seq = 3 );
						}

					} else {

						$error_pre = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );

						self::safe_error_log( $error_pre . ' ' . $method_name . ' method ' . $type . ' is not callable' );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $method_name . ' method ' . $type . ' is not callable' );
						}
					}

				/*
				 * Example:
				 *
				 * 	'wpsso_filter_hook_name' => '__return_false'
				 */
				} elseif ( is_string( $val ) ) {

					$arg_nums      = 1;
					$hook_name     = self::sanitize_hookname( $ext . '_' . $name );
					$function_name = self::sanitize_hookname( $val );

					if ( is_callable( $function_name ) ) {

						call_user_func( 'add_' . $type, $hook_name, $function_name, $prio, $arg_nums );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'added ' . $function_name . ' function ' . $type . ' for ' . $hook_name, $class_seq = 3 );
						}

					} else {

						$error_pre = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );

						self::safe_error_log( $error_pre . ' ' . $function_name . ' function ' . $type . ' for ' . $hook_name . ' is not callable');

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $function_name . ' function ' . $type . ' for ' . $hook_name . ' is not callable' );
						}
					}

				/*
				 * Example:
				 *
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

		/*
		 * Add options using a key prefix array and post type names.
		 */
		public function add_post_type_names( array &$opts, array $opt_prefixes, $args = null ) {

			foreach ( $opt_prefixes as $opt_prefix => $def_val ) {

				$names = SucomUtil::get_post_types( $output = 'names', $sort = false, $args );

				foreach ( $names as $opt_suffix ) {

					$opt_key = $opt_prefix . '_' . $opt_suffix;

					if ( ! isset( $opts[ $opt_key ] ) ) {

						$opts[ $opt_key ] = $def_val;
					}
				}
			}

			return $opts;
		}

		/*
		 * Add options using a key prefix array and post type archive names.
		 *
		 * Note that 'has_archive' = 1 will not match post types registered with a string in 'has_archive'.
		 *
		 * Use 'has_archive' = true to include the WooCommerce product archive page (ie. 'has_archive' = 'shop').
		 */
		public function add_post_type_archive_names( array &$opts, array $opt_prefixes ) {

			$args = array( 'has_archive' => true );

			return $this->add_post_type_names( $opts, $opt_prefixes, $args );
		}

		/*
		 * Add options using a key prefix array and taxonomy names.
		 */
		public function add_taxonomy_names( array &$opts, array $opt_prefixes ) {

			foreach ( $opt_prefixes as $opt_prefix => $def_val ) {

				$names = self::get_taxonomies( $output = 'names' );

				foreach ( $names as $opt_suffix ) {

					$opt_key = $opt_prefix . '_' . $opt_suffix;

					if ( ! isset( $opts[ $opt_key ] ) ) {

						$opts[ $opt_key ] = $def_val;
					}
				}
			}

			return $opts;
		}

		public function get_pkg_info() {

			static $pkg_info = array();

			if ( ! empty( $pkg_info ) ) {

				return $pkg_info;
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'name' ] ) ) {	// Just in case.

					continue;
				}

				$pkg_info[ $ext ] = array();

				$ext_pdir        = $this->p->check->pp( $ext, $li = false );
				$ext_auth_id     = $this->p->check->get_ext_auth_id( $ext );
				$ext_pp          = $ext_auth_id && $this->p->check->pp( $ext, $li = true, WPSSO_UNDEF ) === WPSSO_UNDEF ? true : false;
				$ext_stat        = ( $ext_pp ? 'L' : ( $ext_pdir ? 'U' : 'S' ) ) . ( $ext_auth_id ? '*' : '' );
				$ext_name_transl = _x( $info[ 'name' ], 'plugin name', 'wpsso' );
				$pkg_pro_transl  = _x( $this->p->cf[ 'packages' ][ 'pro' ], 'package name', 'wpsso' );
				$pkg_std_transl  = _x( $this->p->cf[ 'packages' ][ 'std' ], 'package name', 'wpsso' );

				$pkg_info[ $ext ][ 'pdir' ]      = $ext_pdir;
				$pkg_info[ $ext ][ 'pp' ]        = $ext_pp;
				$pkg_info[ $ext ][ 'pkg' ]       = $ext_pp ? $pkg_pro_transl : $pkg_std_transl;
				$pkg_info[ $ext ][ 'short' ]     = $info[ 'short' ];
				$pkg_info[ $ext ][ 'short_pkg' ] = $info[ 'short' ] . ' ' . $pkg_info[ $ext ][ 'pkg' ];
				$pkg_info[ $ext ][ 'short_pro' ] = $info[ 'short' ] . ' ' . $pkg_pro_transl;
				$pkg_info[ $ext ][ 'short_std' ] = $info[ 'short' ] . ' ' . $pkg_std_transl;
				$pkg_info[ $ext ][ 'gen' ]       = $info[ 'short' ] . ( isset( $info[ 'version' ] ) ? ' ' . $info[ 'version' ] . '/' . $ext_stat : '' );
				$pkg_info[ $ext ][ 'name' ]      = $ext_name_transl;
				$pkg_info[ $ext ][ 'name_pkg' ]  = SucomUtil::get_dist_name( $ext_name_transl, $pkg_info[ $ext ][ 'pkg' ] );
				$pkg_info[ $ext ][ 'name_pro' ]  = SucomUtil::get_dist_name( $ext_name_transl, $pkg_pro_transl );
				$pkg_info[ $ext ][ 'name_std' ]  = SucomUtil::get_dist_name( $ext_name_transl, $pkg_std_transl );
			}

			return $pkg_info;
		}

		public function get_form_cache( $name, $add_none = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			$filter_key  = self::sanitize_key( $name );
			$filter_name = 'wpsso_form_cache_' . $filter_key;

			if ( ! isset( $local_cache[ $filter_key ] ) ) {

				$local_cache[ $filter_key ] = array();	// Initialize a default value.

				switch ( $filter_key ) {

					case 'half_hours':

						$local_cache[ $filter_key ] = self::get_hours_range( $start_secs = 0, $end_secs = DAY_IN_SECONDS,
							$step_secs = 60 * 30, $label_format = 'H:i' );

						break;

					case 'quarter_hours':

						$local_cache[ $filter_key ] = self::get_hours_range( $start_secs = 0, $end_secs = DAY_IN_SECONDS,
							$step_secs = 60 * 15, $label_format = 'H:i' );

						break;

					case 'all_types':

						$local_cache[ $filter_key ] = $this->p->schema->get_schema_types_array( $flatten = false );

						break;

					case 'business_types':

						$this->get_form_cache( 'all_types', false );	// Sets $local_cache[ 'all_types' ].

						$local_cache[ $filter_key ] =& $local_cache[ 'all_types' ][ 'thing' ][ 'place' ][ 'local.business' ];

						break;

					case 'business_types_select':

						$this->get_form_cache( 'business_types', false );	// Sets $local_cache[ 'business_types' ].

						$local_cache[ $filter_key ] = $this->p->schema->get_schema_types_select( $local_cache[ 'business_types' ] );

						break;

					case 'org_types':

						$this->get_form_cache( 'all_types', false );	// Sets $local_cache[ 'all_types' ].

						$local_cache[ $filter_key ] =& $local_cache[ 'all_types' ][ 'thing' ][ 'organization' ];

						break;

					case 'org_types_select':

						$this->get_form_cache( 'org_types', false );	// Sets $local_cache[ 'org_types' ].

						$local_cache[ $filter_key ] = $this->p->schema->get_schema_types_select( $local_cache[ 'org_types' ] );

						break;

					case 'org_names':

						$local_cache[ $filter_key ] = array( 'site' => $this->p->cf[ 'form' ][ 'org_select' ][ 'site' ] );

						break;

					case 'person_names':

						$local_cache[ $filter_key ] = WpssoUser::get_persons_names();

						break;

					case 'place_types':

						$this->get_form_cache( 'all_types', false );	// Sets $local_cache[ 'all_types' ].

						$local_cache[ $filter_key ] =& $local_cache[ 'all_types' ][ 'thing' ][ 'place' ];

						break;

					case 'place_types_select':

						$this->get_form_cache( 'place_types', false );	// Sets $local_cache[ 'place_types' ].

						$local_cache[ $filter_key ] = $this->p->schema->get_schema_types_select( $local_cache[ 'place_types' ] );

						break;

					case 'place_names_custom':
					case 'place_names':
					default:

						break;
				}

				$local_cache[ $filter_key ] = apply_filters( $filter_name, $local_cache[ $filter_key ] );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'using existing form cache entry for ' . $filter_key );
			}

			if ( isset( $local_cache[ $filter_key ][ 'none' ] ) ) {	// Just in case.

				unset( $local_cache[ $filter_key ][ 'none' ] );
			}

			if ( $add_none ) {

				return array( 'none' => '[None]' ) + $local_cache[ $filter_key ];
			}

			return $local_cache[ $filter_key ];
		}

		/*
		 * Returns an associative array, with 'none' as the first element.
		 */
		public function get_article_sections() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$sections = array();

			if ( ! defined( 'WPSSO_ARTICLE_SECTIONS_LIST' ) || empty( WPSSO_ARTICLE_SECTIONS_LIST ) ) {

				return $sections;
			}

			$text_list_file = self::get_file_path_locale( WPSSO_ARTICLE_SECTIONS_LIST );
			$cache_md5_pre  = 'wpsso_f_';
			$cache_exp_secs = MONTH_IN_SECONDS;
			$cache_salt     = __METHOD__ . '(' . $text_list_file . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			if ( $cache_exp_secs > 0 ) {


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

				$error_pre  = sprintf( '%s error:', __METHOD__ );
				$notice_msg = sprintf( __( 'Error reading the %s file for the article sections list.', 'wpsso' ), $text_list_file );

				self::safe_error_log( $error_pre . ' ' . $notice_msg );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error reading %s article sections list file' );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->err( $notice_msg );
				}

				return array();
			}

			$sections = array();

			foreach ( $raw_sections as $num => $section_name ) {

				if ( 0 === strpos( $section_name, '#' ) ) {	// Skip comment lines.

					continue;
				}

				$sections[ $section_name ] = $section_name;

				unset( $sections[ $num ] );	// Save memory and unset as we go.
			}

			unset( $raw_sections );

			$sections = apply_filters( 'wpsso_article_sections', $sections );

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

		/*
		 * Format the product category list from https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt.
		 *
		 * Returns an associative array, with 'none' as the first element.
		 */
		public function get_google_product_categories() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$categories = array();

			if ( ! defined( 'WPSSO_PRODUCT_CATEGORIES_LIST' ) || empty( WPSSO_PRODUCT_CATEGORIES_LIST ) ) {

				return $categories;
			}

			$text_list_file = self::get_file_path_locale( WPSSO_PRODUCT_CATEGORIES_LIST );
			$cache_md5_pre  = 'wpsso_f_';
			$cache_exp_secs = MONTH_IN_SECONDS;
			$cache_salt     = __METHOD__ . '(' . $text_list_file . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			if ( $cache_exp_secs > 0 ) {

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

				$error_pre  = sprintf( '%s error:', __METHOD__ );
				$notice_msg = sprintf( __( 'Error reading the %s file for the product categories list.', 'wpsso' ), $text_list_file );

				self::safe_error_log( $error_pre . ' ' . $notice_msg );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error reading %s product categories list file' );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->err( $notice_msg );
				}

				return array();
			}

			$categories = array();

			foreach ( $raw_categories as $num => $category_id_name ) {

				if ( 0 === strpos( $category_id_name, '#' ) ) {	// Skip comment lines.

					continue;
				}

				if ( preg_match( '/^([0-9]+) - (.*)$/', $category_id_name, $match ) ) {

					$categories[ $match[ 1 ] ] = $match[ 2 ];
				}

				unset( $raw_categories[ $num ] );	// Save memory and unset as we go.
			}

			unset( $raw_categories );

			$categories = apply_filters( 'wpsso_google_product_categories', $categories );

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

		/*
		 * Query argument examples:
		 *
		 * 	/html/head/link|/html/head/meta
		 * 	/html/head/link[@rel="canonical"]
		 * 	/html/head/meta[starts-with(@property, "og:video:")]
		 */
		public function get_html_head_meta( $request, $query = '/html/head/meta', $libxml_errors = false, array $curl_opts = array(), $throttle_secs = 0 ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$is_admin       = is_admin();	// Optimize and call once.
			$cache_format   = 'raw';
			$cache_type     = 'transient';
			$cache_exp_secs = 300;
			$cache_pre_ext  = '';

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

			} elseif ( false === filter_var( $request, FILTER_VALIDATE_URL ) ) {	// Request is an invalid url.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: request argument is not html or a valid url' );
				}

				if ( $is_admin ) {

					$this->p->notice->err( sprintf( __( 'The <code>%1$s</code> request argument is not HTML or a valid URL.',
						'wpsso' ), __METHOD__ . '()' ) );
				}

				return false;

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting HTML for ' . $request );
				}

				$html = $this->p->cache->get( $request, $cache_format, $cache_type, $cache_exp_secs, $cache_pre_ext, $curl_opts, $throttle_secs );

				if ( empty( $html ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: error getting HTML from ' . $request );
					}

					if ( $is_admin ) {

						$this->p->notice->err( sprintf( __( 'Error getting HTML from <a href="%1$s">%1$s</a>.', 'wpsso' ), $request ) );
					}

					return false;
				}
			}

			if ( function_exists( 'mb_convert_encoding' ) ) {	// Just in case.

				$html = mb_convert_encoding( $html, $to_encoding = 'HTML-ENTITIES', $from_encoding = 'UTF-8' );
			}

			/*
			 * U = Invert greediness of quantifiers, so they are NOT greedy by default, but become greedy if followed by ?.
			 * m = The "^" and "$" constructs match newlines and the complete subject string.
			 * s = A dot metacharacter in the pattern matches all characters, including newlines.
			 */
			$html = preg_replace( '/<!--.*-->/Ums', '', $html );

			if ( empty( $html ) ) {	// Returned html for url is empty.

				if ( $request ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: html for ' . $request . ' is empty' );
					}

					if ( $is_admin ) {

						$this->p->notice->err( sprintf( __( 'Webpage retrieved from <a href="%1$s">%1$s</a> is empty.', 'wpsso' ), $request ) );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: submitted html is empty' );
					}
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

					/*
					 * libXMLError {
					 *	public int $level;
					 *	public int $code;
					 *	public int $column;
					 *	public string $message;
					 *	public string $file;
					 *	public int $line;
					 * }
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

			$xpath  = new DOMXPath( $doc );
			$metas  = $xpath->query( $query );
			$mt_ret = array();

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

			$html           = '';
			$cache_format   = 'raw';
			$cache_type     = 'transient';
			$cache_exp_secs = 300;

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

			} elseif ( false === filter_var( $request, FILTER_VALIDATE_URL ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: request argument is not html or valid url' );
				}

				return false;

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting HTML for ' . $request );
				}

				$html = $this->p->cache->get( $request, $cache_format, $cache_type, $cache_exp_secs );

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
				$this->p->notice->err( sprintf( __( 'The PHP %s class is missing &ndash; contact your hosting provider to have the missing class installed.', 'wpsso' ), '<code>' . $class . '</code>' ) );
			}
		}

		public function php_function_missing( $function, $method = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $function . ' PHP function is missing' );
			}

			if ( is_admin() ) {

				// translators: %1$s is the function name.
				$this->p->notice->err( sprintf( __( 'The PHP %s function is missing &ndash; contact your hosting provider to have the missing function installed.', 'wpsso' ), '<code>' . $function . '</code>' ) );
			}
		}

		/*
		 * Used by WpssoHead->show_head() and WpssoAdminHead->__construct().
		 */
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

			foreach ( apply_filters( 'wpsso_is_functions', $this->is_functions )  as $function ) {

				if ( function_exists( $function ) ) {

					$mtime_start = microtime( $get_float = true );

					$function_ret = $function();

					$mtime_total = microtime( $get_float = true ) - $mtime_start;

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

		/*
		 * Some themes and plugins have been known to hook the WordPress 'get_shortlink' filter and return an empty URL to
		 * disable the WordPress shortlink meta tag. This breaks the WordPress wp_get_shortlink() function and is a
		 * violation of the WordPress theme guidelines.
		 *
		 * This method calls the WordPress wp_get_shortlink() function, and if an empty string is returned, calls an
		 * unfiltered version of the same function.
		 *
		 * $context = 'blog', 'post' (default), 'media', or 'query'
		 */
		public function get_shortlink( $mod, $context = 'post', $allow_slugs = true ) {

			/*
			 * The $mod array argument is preferred but not required.
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			$shortlink = '';

			if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {	// Just in case.

				$shortlink = wp_get_shortlink( $mod[ 'id' ], $context, $allow_slugs );	// Since WP v3.0.

				if ( empty( $shortlink ) || ! is_string( $shortlink) || false === filter_var( $shortlink, FILTER_VALIDATE_URL ) ) {

					$shortlink = SucomUtilWP::raw_wp_get_shortlink( $mod[ 'id' ], $context, $allow_slugs );
				}

				$shortlink =  $this->get_url_paged( $shortlink, $mod, $add_page = true );
			}

			return $shortlink;
		}

		/*
		 * Shorten URL using the selected shortening service.
		 */
		public function shorten_url( $long_url, $mod = false ) {

			$shortener = isset( $this->p->options[ 'plugin_shortener' ] ) ? $this->p->options[ 'plugin_shortener' ] : 'none';

			if ( ! empty( $shortener ) && 'none' !== $shortener ) {

				$short_url = apply_filters( 'wpsso_get_short_url', $long_url, $shortener, $mod );

				if ( false !== filter_var( $short_url, FILTER_VALIDATE_URL ) ) {	// Make sure the returned URL is valid.

					return $short_url;
				}
			}

			return $long_url;
		}

		public function json_format( array $data, $options = 0, $depth = 32 ) {

			if ( 0 === $options ) {

				$options = JSON_UNESCAPED_SLASHES;
			}

			if ( $this->is_json_pretty() ) {

				$options = $options|JSON_PRETTY_PRINT;
			}

			return wp_json_encode( $data, $options, $depth );
		}

		/*
		 * Get the sitemaps alternates array.
		 *
		 * Example:
		 *
		 * $alternates = array(
		 * 	array(
		 *		'href' => 'https://example.com/en/page-1/',
		 * 		'hreflang' => 'en_US',
		 * 	),
		 * 	array(
		 *		'href' => 'https://example.com/fr/page-1/',
		 * 		'hreflang' => 'fr_FR',
		 * 	),
		 * 	array(
		 *		'href' => 'https://example.com/es/page-1/',
		 * 		'hreflang' => 'es_ES',
		 * 	),
		 * );
		 */
		public function get_sitemaps_alternates( array $mod ) {

			$alternates = (array) apply_filters( 'wpsso_sitemaps_alternates', array(), $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'alternates', $alternates );
			}

			return $alternates;
		}

		public function get_sitemaps_images( array $mod ) {

			$sitemaps_images = array();

			$mt_images = $this->p->media->get_all_images( $num = 1, $size_names = 'schema', $mod, $md_pre = array( 'schema', 'og' ) );

			foreach ( $mt_images as $mt_single_image ) {

				if ( $image_url = SucomUtil::get_first_og_image_url( $mt_single_image ) ) {

					$sitemaps_images[] = array(
						'image:loc' => $image_url,
					);
				}
			}

			return $sitemaps_images;
		}

		/*
		 * Shorten the canonical URL using the selected shortening service.
		 */
		public function get_canonical_short_url( $mod = false, $add_page = true ) {

			$url = $this->get_canonical_url( $mod, $add_page );

			return $this->shorten_url( $url, $mod );
		}

		/*
		 * The $mod array argument is preferred but not required.
		 *
		 * $mod = true | false | post_id | $mod array
		 */
		public function get_canonical_url( $mod = false, $add_page = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'mod'      => $mod,
					'add_page' => $add_page,
				) );
			}

			/*
			 * The $mod array argument is preferred but not required.
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			/*
			 * Optimize and return the URL from local cache if possible.
			 */
			static $local_cache = array();

			$url        = null;
			$is_custom  = false;
			$cache_salt = false;

			if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

				/*
				 * Note that SucomUtil::get_mod_salt() does not include the page number or locale.
				 */
				$cache_salt = self::get_mod_salt( $mod ) . '_add:' . (string) $add_page;

				if ( isset( $local_cache[ $cache_salt ] ) ) {

					return $local_cache[ $cache_salt ];
				}

				$url = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'canonical_url' );	// Returns null if an index key is not found.

				if ( ! empty( $url ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'custom canonical url = ' . $url );
					}

					$is_custom = true;
				}
			}

			if ( ! $is_custom ) {	// No custom canonical url from the post, term, or user meta.

				/*
				 * Similar module type logic can be found in the following methods:
				 *
				 * See WpssoOpenGraph->get_mod_og_type().
				 * See WpssoPage->get_description().
				 * See WpssoPage->get_the_title().
				 * See WpssoSchema->get_mod_schema_type().
				 * See WpssoUtil->get_canonical_url().
				 */
				if ( $mod[ 'is_home' ] ) {

					$url = self::get_home_url( $this->p->options, $mod );

					$url = apply_filters( 'wpsso_home_url', $url, $mod );

				} elseif ( $mod[ 'is_comment' ] ) {

					if ( $mod[ 'id' ] ) {	// Just in case.

						$url = get_comment_link( $mod[ 'id' ] );

						$url = $this->is_string_url( $url, 'comment link' );	// Check for WP_Error.
					}

					$url = apply_filters( 'wpsso_comment_url', $url, $mod );

				} elseif ( $mod[ 'is_post' ] ) {

					if ( $mod[ 'post_type' ] ) {	// Just in case.

						if ( $mod[ 'is_post_type_archive' ] ) {	// The post ID may be 0.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'post type is archive' );
							}

							$url = get_post_type_archive_link( $mod[ 'post_type' ] );

							$url = $this->is_string_url( $url, 'post type archive link' );	// Check for WP_Error.

						} elseif ( $mod[ 'id' ] ) {	// Just in case.

							/*
							 * Get the canonical URL of the published post.
							 */
							if ( 'publish' !== $mod[ 'post_status' ] ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'post status is not published' );
								}

								if ( $mod[ 'wp_obj' ] ) {	// Just in case.

									$mod[ 'wp_obj' ]->post_status = 'publish';

									if ( empty( $mod[ 'wp_obj' ]->post_name ) ) {

										$mod[ 'wp_obj' ]->post_name = sanitize_title( $mod[ 'wp_obj' ]->post_title );
									}

									$url = get_permalink( $mod[ 'wp_obj' ] );
								}
							}

							if ( empty( $url ) ) {	// Just in case.

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'getting permalink for post id ' . $mod[ 'id' ] );
								}

								$url = get_permalink( $mod[ 'id' ] );
							}

							$url = $this->is_string_url( $url, 'post permalink' );	// Check for WP_Error.

						} elseif ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'no post id' );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no post type' );
					}

					$url = apply_filters( 'wpsso_post_url', $url, $mod );

				} elseif ( $mod[ 'is_term' ] ) {

					if ( $mod[ 'id' ] ) {	// Just in case.

						$url = get_term_link( $mod[ 'id' ], $mod[ 'tax_slug' ] );

						$url = $this->is_string_url( $url, 'term link' );	// Check for WP_Error.
					}

					$url = apply_filters( 'wpsso_term_url', $url, $mod );

				} elseif ( $mod[ 'is_user' ] ) {

					if ( $mod[ 'id' ] ) {	// Just in case.

						$url = get_author_posts_url( $mod[ 'id' ] );

						$url = $this->is_string_url( $url, 'author posts url' );	// Check for WP_Error.
					}

					$url = apply_filters( 'wpsso_user_url', $url, $mod );

				} elseif ( $mod[ 'is_search' ] ) {

					$url = get_search_link( $mod[ 'query_vars' ][ 's' ] );

					$url = $this->is_string_url( $url, 'search link' );	// Check for WP_Error.

					$url = apply_filters( 'wpsso_search_url', $url, $mod );

				} elseif ( $mod[ 'is_archive' ] ) {

					if ( $mod[ 'is_date' ] ) {

						if ( $mod[ 'is_year' ] ) {

							$url = get_year_link( $mod[ 'query_vars' ][ 'year' ] );

							$url = $this->is_string_url( $url, 'year link' );	// Check for WP_Error.

						} elseif ( $mod[ 'is_month' ] ) {

							$url = get_month_link( $mod[ 'query_vars' ][ 'year' ], $mod[ 'query_vars' ][ 'monthnum' ] );

							$url = $this->is_string_url( $url, 'month link' );	// Check for WP_Error.

						} elseif ( $mod[ 'is_day' ] ) {

							$url = get_day_link( $mod[ 'query_vars' ][ 'year' ], $mod[ 'query_vars' ][ 'monthnum' ], $mod[ 'query_vars' ][ 'day' ] );

							$url = $this->is_string_url( $url, 'day link' );	// Check for WP_Error.
						}
					}

					$url = apply_filters( 'wpsso_archive_page_url', $url, $mod );
				}

				/*
				 * Use the current URL as a fallback for themes and plugins that create public content and don't use the
				 * standard WordPress functions / variables and/or are not properly integrated with WordPress (ie. they do
				 * not use custom post types, taxonomies, terms, etc.).
				 */
				if ( empty ( $url ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'falling back to server request url' );
					}

					$url = self::get_url( $remove_ignored_args = true );	// Uses a local cache.

					$url = apply_filters( 'wpsso_server_request_url', $url );
				}
			}

			/*
			 * Maybe enforce the FORCE_SSL constant.
			 */
			if ( strpos( $url, '://' ) ) {	// Only check URLs with a protocol.

				if ( self::get_const( 'FORCE_SSL' ) && ! self::is_https( $url ) ) {

					$url = set_url_scheme( $url, 'https' );
				}
			}

			$url = $this->get_url_paged( $url, $mod, $add_page );

			$url = apply_filters( 'wpsso_canonical_url', $url, $mod, $add_page, $is_custom );

			if ( ! empty( $cache_salt ) ) {

				$local_cache[ $cache_salt ] = $url;
			}

			return $url;
		}

		public function get_oembed_url( $mod = false, $format = 'json' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$url = '';

			/*
			 * The $mod array argument is preferred but not required.
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			if ( function_exists( 'get_oembed_endpoint_url' ) ) {	// Since WP v4.4.

				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {	// Just in case.

					$url = $this->get_canonical_url( $mod );

					$url = get_oembed_endpoint_url( $url, $format );	// Since WP v4.4.
				}
			}

			return apply_filters( 'wpsso_oembed_url', $url, $mod, $format );
		}

		public function get_oembed_data( $mod = false, $width = '600' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$data = false;

			/*
			 * The $mod array argument is preferred but not required.
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			if ( function_exists( 'get_oembed_response_data' ) ) {	// Since WP v4.4.

				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {	// Just in case.

					$data = get_oembed_response_data( $mod[ 'id' ], $width );		// Returns false on error.
				}
			}

			return apply_filters( 'wpsso_oembed_data', $data, $mod, $width );
		}

		/*
		 * The $mod array argument is preferred but not required.
		 *
		 * $mod = true | false | post_id | $mod array
		 */
		public function get_redirect_url( $mixed, $mod_id = null ) {

			$mod = false;

			if ( ! empty( $mixed[ 'obj' ] ) ) {

				$mod =& $mixed;

			} elseif ( is_string( $mixed ) && isset( $this->p->$mixed ) && $mod_id ) {	// Just in case.

				$mod = $this->p->$mixed->get_mod( $mod_id );

			} elseif ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mixed );
			}

			$url        = null;
			$is_custom  = false;

			if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

				$url = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'redirect_url' );	// Returns null if an index key is not found.

				if ( ! empty( $url ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'custom redirect url = ' . $url );
					}

					$is_custom  = true;
				}
			}

			$url = apply_filters( 'wpsso_redirect_url', $url, $mod, $is_custom );

			return $url;
		}

		public function get_sharing_url( $mod = false, $add_page = true, $atts = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'mod'      => $mod,
					'add_page' => $add_page,
					'atts'     => $atts,
				) );
			}

			$url = $this->get_canonical_url( $mod, $add_page );

			$utm = array();

			foreach ( array(
				'utm_medium',
				'utm_source',
				'utm_campaign',
				'utm_content',
				'utm_term',
			) as $q ) {

				/*
				 * Ignore 0, false, null, and '' (empty string) values.
				 */
				$utm[ $q ] = empty( $atts[ $q ] ) ? false : $atts[ $q ];
			}

			$utm = apply_filters( 'wpsso_sharing_utm_args', $utm, $mod );

			/*
			 * To add UTM tracking query arguments we need at least 'utm_source', 'utm_medium', and 'utm_campaign'.
			 */
			if ( ! empty( $utm[ 'utm_source' ] ) && ! empty( $utm[ 'utm_medium' ] ) && ! empty( $utm[ 'utm_campaign' ] ) ) {

				$url = add_query_arg( array(
					'utm_medium'   => $utm[ 'utm_medium' ],		// Example: 'social'.
					'utm_source'   => $utm[ 'utm_source' ],		// Example: 'facebook'.
					'utm_campaign' => $utm[ 'utm_campaign' ],	// Example: 'book-launch'
					'utm_content'  => $utm[ 'utm_content' ],	// Example: 'wpsso-rrssb-content-bottom'
					'utm_term'     => $utm[ 'utm_term' ],
				), $url );
			}

			return apply_filters( 'wpsso_sharing_url', $url, $mod, $add_page );
		}

		/*
		 * Shorten the sharing URL using the selected shortening service.
		 */
		public function get_sharing_short_url( $mod = false, $add_page = true, $atts = array() ) {

			$url = $this->get_sharing_url( $mod, $add_page, $atts );

			return $this->shorten_url( $url, $mod );
		}

		public function get_url_paged( $url, array $mod, $add_page = true ) {

			if ( empty( $url ) || empty( $add_page ) ) {	// Just in case.

				return $url;	// Nothing to do.
			}

			global $wp_rewrite;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'pagination base = ' . $wp_rewrite->pagination_base );
			}

			$using_permalinks = $wp_rewrite->using_permalinks();
			$have_query_args  = false === strpos( $url, '?' ) ? false : true;
			$page_number      = $this->get_page_number( $mod, $add_page );

			if ( $mod[ 'is_archive' ] || $mod[ 'is_home_posts' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'is archive or home posts page' );
				}

				if ( $page_number > 1 ) {

					if ( ! $using_permalinks || $have_query_args ) {

						$url = add_query_arg( 'paged', $page_number, $url );

					} else {

						$url = user_trailingslashit( trailingslashit( $url ) . trailingslashit( $wp_rewrite->pagination_base ) . $page_number );
					}
				}

			} elseif ( ! $using_permalinks || $have_query_args ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'not using permalinks or have query args' );
				}

				/*
				 * Note that the singular page query argument is named 'page' not 'paged'.
				 */
				if ( $page_number > 1 ) {

					$url = add_query_arg( 'page', $page_number, $url );
				}

				if ( $mod[ 'comment_paged' ] > 1 ) {

					$url = add_query_arg( 'cpage', $mod[ 'comment_paged' ], $url );
				}

			} else {

				if ( $page_number > 1 ) {

					$url = user_trailingslashit( trailingslashit( $url ) . $page_number );
				}

				if ( $mod[ 'comment_paged' ] > 1 ) {

					$url = user_trailingslashit( trailingslashit( $url ) . $wp_rewrite->comments_pagination_base . '-' . $mod[ 'comment_paged' ] );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'get url paged = ' . $url );
			}

			return $url;
		}

		/*
		 * See WpssoUtil->get_url_paged().
		 * See WpssoUtilInline->get_defaults().
		 */
		public function get_page_number( array $mod, $add_page = true ) {

			$page_number = 1;

			if ( is_numeric( $add_page ) ) {

				$page_number = $add_page > 1 ? $add_page : 1;

			} else {

				$page_number = $mod[ 'paged' ];	// False or a number.
			}

			return $page_number;
		}

		/*
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

			/*
			 * The user ID is different than the current / effective user ID, so check if the user locale is different
			 * to the current WordPress locale and load the user locale if required.
			 */
			$current_locale = get_locale();	// Use the WordPress locale.
			$user_locale    = get_user_meta( $user_id, 'locale', $single = true );

			$this->maybe_load_textdomain( $current_locale, $user_locale, $plugin_slug = 'wpsso' );

			return $user_id;
		}

		public function maybe_load_textdomain( $current_locale, $new_locale, $plugin_slug ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $new_locale ][ $plugin_slug ] ) ) {

				return $local_cache[ $new_locale ][ $plugin_slug ];
			}

			if ( $new_locale && $new_locale !== $current_locale ) {

				$rel_lang_path    = $plugin_slug . '/languages/';
				$mofile           = $plugin_slug . '-' . $new_locale . '.mo';
				$wp_mopath        = defined( 'WP_LANG_DIR' ) ? WP_LANG_DIR . '/plugins/' . $mofile : false;
				$mu_plugin_mopath = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR . '/' . $rel_lang_path . $mofile : false;
				$plugin_mopath    = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR . '/' . $rel_lang_path . $mofile : false;

				/*
				 * Try to load from the WordPress languages directory first.
				 */
				if ( ( $wp_mopath && load_textdomain( $plugin_slug, $wp_mopath ) ) ||
					( $mu_plugin_mopath && load_textdomain( $plugin_slug, $mu_plugin_mopath ) ) ||
					( $plugin_mopath && load_textdomain( $plugin_slug, $plugin_mopath ) ) ) {

					$this->p->notice->set_label_transl();	// Update the notice label.

					return $local_cache[ $new_locale ][ $plugin_slug ] = true;
				}
			}

			return $local_cache[ $new_locale ][ $plugin_slug ] = false;
		}

		/*
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

				$base = self::get_url( $remove_ignored_args = true );	// Uses a local cache.

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

		public function is_canonical_disabled() {

			return empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? true : false;
		}

		/*
		 * Since WPSSO Core v12.0.0.
		 */
		public function is_redirect_enabled() {

			return $this->is_redirect_disabled() ? false : true;
		}

		public function is_redirect_disabled() {

			return apply_filters( 'wpsso_redirect_disabled', false );
		}

		public function is_title_tag_disabled() {

			if ( SucomUtil::get_const( 'WPSSO_TITLE_TAG_DISABLE' ) ) {

				return true;
			}

			return current_theme_supports( 'title-tag' ) ? false : true;
		}

		public function is_seo_title_disabled() {

			if ( $this->is_title_tag_disabled() ) {

				return true;

			} elseif ( 'seo_title' !== $this->p->options[ 'plugin_title_tag' ] ) {

				return true;
			}

			return false;
		}

		public function is_seo_desc_disabled() {

			return empty( $this->p->options[ 'add_meta_name_description' ] ) ? true : false;
		}

		public function is_pin_img_disabled() {

			return empty( $this->p->options[ 'pin_add_img_html' ] ) ? true : false;
		}

		public function is_robots_disabled() {

			return $this->robots->is_disabled();
		}

		public function is_schema_disabled() {

			return isset( $this->p->avail[ 'p' ][ 'schema' ] ) && empty( $this->p->avail[ 'p' ][ 'schema' ] ) ? true : false;
		}

		public function is_sitemaps_disabled() {

			return apply_filters( 'wp_sitemaps_enabled', true ) ? false : true;
		}

		public function is_json_pretty() {

			if ( $this->p->debug->enabled ) {	// Pretty JSON when debug is enabled.

				return true;
			}

			$is_pretty = empty( $this->p->options[ 'plugin_schema_json_min' ] ) ? true : false;

			$is_pretty = (bool) apply_filters( 'wpsso_json_pretty_print', $is_pretty );

			return $is_pretty ? true : false;
		}

		/*
		 * Note that WebP is only supported since PHP v7.1.
		 */
		public function is_image_url( $image_url ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
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

			if ( empty( $image_info[ 2 ] ) ) {	// Make sure we have an image type integer.

				return false;
			}

			return true;
		}

		public function is_string_url( $url, $context ) {

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

		public function clear_uniq_urls( $image_sizes = 'default', $mod = false ) {

			if ( ! is_array( $image_sizes ) ) {

				$image_sizes = $this->get_image_size_names( $image_sizes, $sanitize = false );	// Always returns an array.
			}

			$cleared  = 0;
			$mod_salt = SucomUtil::get_mod_salt( $mod );	// Does not include the page number or locale.

			foreach ( $image_sizes as $num => $uniq_context ) {

				$uniq_context = preg_replace( '/-[0-9]+x[0-9]+$/', '', $uniq_context );	// Change 'wpsso-schema-1x1' to 'wpsso-schema'.

				$image_sizes[ $num ] = $mod_salt ? $mod_salt . '_' . $uniq_context : $uniq_context;
			}

			foreach ( array_unique( $image_sizes ) as $uniq_context ) {

				if ( isset( $this->cache_uniq_urls[ $uniq_context ] ) ) {

					$cleared += count( $this->cache_uniq_urls[ $uniq_context ] );
				}

				$this->cache_uniq_urls[ $uniq_context ] = array();

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'cleared uniq url cache for context ' . $uniq_context );
				}
			}

			return $cleared;
		}

		public function is_dupe_url( $url, $image_sizes = 'default', $mod = false ) {

			return $this->is_uniq_url( $url, $image_sizes, $mod ) ? false : true;
		}

		public function is_uniq_url( $url, $image_sizes = 'default', $mod = false ) {

			if ( empty( $url ) ) {

				return false;
			}

			$url = $this->fix_relative_url( $url );	// Just in case.

			if ( ! is_array( $image_sizes ) ) {

				$image_sizes = array( $image_sizes );
			}

			$is_uniq  = true;
			$mod_salt = SucomUtil::get_mod_salt( $mod );	// Does not include the page number or locale.

			foreach ( $image_sizes as $num => $uniq_context ) {

				$uniq_context = preg_replace( '/-[0-9]+x[0-9]+$/', '', $uniq_context );	// Change 'wpsso-schema-1x1' to 'wpsso-schema'.

				$image_sizes[ $num ] = $mod_salt ? $mod_salt . '_' . $uniq_context : $uniq_context;
			}

			foreach ( array_unique( $image_sizes ) as $uniq_context ) {

				if ( isset( $this->cache_uniq_urls[ $uniq_context ][ $url ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'duplicate url found for context ' . $uniq_context . ': ' . $url );
					}

					$is_uniq = false;

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'unique url saved for context ' . $uniq_context . ': ' . $url );
					}

					$this->cache_uniq_urls[ $uniq_context ][ $url ] = true;
				}
			}

			return $is_uniq;
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

		/*
		 * Get maximum media values from custom meta or plugin settings.
		 */
		public function get_max_nums( array $mod, $opt_prefix = 'og' ) {

			$max_nums = array();

			$max_opt_keys = array( $opt_prefix . '_vid_max', $opt_prefix . '_img_max' );

			foreach ( $max_opt_keys as $opt_key ) {

				if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

					$max_val = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $opt_key );	// Returns null if an index key is not found.

				} else {

					$max_val = null;	// Default value if index key is missing.
				}

				/*
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

			/*
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

			/*
			 * Prevent recursive loops - the global variable is defined before applying the filters.
			 */
			if ( ! empty( $GLOBALS[ 'wpsso_doing_filter_' . $filter_name ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: global variable wpsso_doing_filter_' . $filter_name . ' is true' );
				}

				return $filter_value;
			}

			/*
			 * Hooked by some modules to perform actions before/after filtering the content.
			 */
			do_action( 'wpsso_pre_apply_filters_text', $filter_name );

			/*
			 * Load the Block Filter Output (BFO) filters to block and show an error for incorrectly coded filters.
			 */
			if ( $use_bfo ) {

				$classname = apply_filters( 'wpsso_load_lib', false, 'com/bfo', 'SucomBFO' );

				if ( is_string( $classname ) && class_exists( $classname ) ) {

					$bfo_obj = new $classname( $this->p );

					$bfo_obj->add_start_hooks( array( $filter_name ) );
				}
			}

			/*
			 * Save the original post object, in case some filters modify the global $post.
			 */
			global $post, $wp_query;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'saving the original post object ' . ( isset( $post->ID ) ?
					'ID ' . $post->ID : '(no post ID)' ) );
			}

			$post_pre_filter     = $post;		// Save the original global post object.
			$wp_query_pre_filter = $wp_query;	// Save the original global wp_query.

			/*
			 * Make sure the $post object is correct before filtering.
			 */
			if ( ! empty( $mod[ 'is_post' ] ) ) {

				if ( ! empty( $mod[ 'id' ] ) ) {	// Just in case.

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

			/*
			 * Prevent recursive loops and signal to other methods that the content filter is being applied to create a
			 * description text - this avoids the addition of unnecessary HTML which will be removed anyway (social
			 * sharing buttons, for example).
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'setting global wpsso_doing_filter_' . $filter_name );
			}

			$GLOBALS[ 'wpsso_doing_filter_' . $filter_name ] = true;	// Prevent recursive loops.

			/*
			 * Apply the filters.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'applying WordPress ' . $filter_name . ' filters' );	// Begin timer.
			}

			$mtime_start  = microtime( $get_float = true );
			$filter_value = call_user_func_array( 'apply_filters', $args );
			$mtime_total  = microtime( $get_float = true ) - $mtime_start;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'applying WordPress ' . $filter_name . ' filters' );	// End timer.
			}

			/*
			 * Unset the recursive loop check.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'unsetting global wpsso_doing_filter_' . $filter_name );
			}

			unset( $GLOBALS[ 'wpsso_doing_filter_' . $filter_name ] );	// Un-prevent recursive loops.

			/*
			 * Issue warning for slow filter performance.
			 */
			if ( $mtime_max > 0 && $mtime_total > $mtime_max ) {

				$is_wp_filter = false;

				switch ( $filter_name ) {

					case 'get_the_excerpt':
					case 'the_content':
					case 'the_excerpt':
					case 'wp_title':

						$is_wp_filter = true;

						break;
				}

				$error_pre   = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$.3f secs', 'wpsso' ), $mtime_max );
				$notice_msg  = sprintf( __( 'Slow filter hook(s) detected - WordPress took %1$.3f secs to execute the "%2$s" filter (%3$s).',
					'wpsso' ), $mtime_total, $filter_name, $rec_max_msg );

				self::safe_error_log( $error_pre . ' ' . $notice_msg );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( sprintf( 'slow filter hook(s) detected - WordPress took %1$.3f secs to execute the "%2$s" filter',
						$mtime_total, $filter_name ) );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {

					/*
					 * If this is a known WordPress filter, show a different and more complete notification message.
					 */
					if ( $is_wp_filter ) {

						$filter_api_link = sprintf( '<a href="https://codex.wordpress.org/Plugin_API/Filter_Reference/%1$s">%1$s</a>', $filter_name );
						$qm_plugin_link  = '<a href="https://wordpress.org/plugins/query-monitor/">Query Monitor</a>';
						$option_label    = _x( 'Disable Cache for Debugging', 'option label', 'wpsso' );
						$option_link     = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_settings', $option_label );

						$notice_msg = sprintf( __( 'Slow filter hook(s) detected - the WordPress %1$s filter took %2$.3f seconds to execute.', 'wpsso' ), $filter_api_link, $mtime_total ) . ' ';

						$notice_msg .= sprintf( __( 'This is longer than the recommended maximum of %1$.3f seconds and may affect page load time.', 'wpsso' ), $mtime_max ) . ' ';

						$notice_msg .= sprintf( __( 'You should consider reviewing active plugin and theme functions hooked into the WordPress %1$s filter for slow and/or sub-optimal PHP code.', 'wpsso' ), $filter_api_link ) . ' ';

						$notice_msg .= sprintf( __( 'Activating the %1$s plugin and enabling the the %2$s option (to apply the filter consistently) may provide more information on the specific hooks or PHP code affecting performance.', 'wpsso' ), $qm_plugin_link, $option_link );
					}

					$notice_key = 'slow-filter-hooks-detected-' . $filter_name;

					$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = DAY_IN_SECONDS );
				}
			}

			/*
			 * Restore the original post object.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'restoring the original post object ' . ( isset( $post_pre_filter->ID ) ?
					'ID ' . $post_pre_filter->ID : '(no post ID)' ) );
			}

			$post     = $post_pre_filter;		// Restore the original global post object.
			$wp_query = $wp_query_pre_filter;	// Restore the original global wp_query.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'restoring post data for template functions' );
			}

			setup_postdata( $post );

			/*
			 * Remove the Block Filter Output (BFO) filters.
			 */
			if ( $use_bfo ) {

				$bfo_obj->remove_all_hooks( array( $filter_name ) );
			}

			/*
			 * Hooked by some modules to perform actions before/after filtering the content.
			 */
			do_action( 'wpsso_after_apply_filters_text', $filter_name );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning filtered value' );
			}

			return $filter_value;
		}

		public function get_admin_url( $menu_id = '', $link_text = '', $menu_lib = '' ) {

			$hash        = '';
			$query       = '';
			$admin_url   = '';
			$current_url = $_SERVER[ 'REQUEST_URI' ];

			/*
			 * $menu_id may start with a hash or query, so parse before checking its value.
			 */
			if ( false !== strpos( $menu_id, '#' ) ) {

				list( $menu_id, $hash ) = explode( '#', $menu_id );
			}

			if ( false !== strpos( $menu_id, '?' ) ) {

				list( $menu_id, $query ) = explode( '?', $menu_id );
			}

			if ( empty( $menu_id ) ) {

				if ( preg_match( '/^.*\?page=wpsso-([^&]*).*$/', $current_url, $match ) ) {

					$menu_id = $match[ 1 ];

				} else {

					$menu_id = key( $this->p->cf[ '*' ][ 'lib' ][ 'submenu' ] );	// Default to first submenu.
				}
			}

			/*
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

			$parent_slug = $this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'page' ] . '?page=wpsso-' . $menu_id;

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

				/*
				 * If we have anchor text, force a page reload for the anchor, in case we're on the same page.
				 *
				 * Use the same random query for all admin URLs during a single page load so the SucomNotice "show
				 * once" feature will work correctly.
				 */
				static $rand_arg = null;

				if ( null === $rand_arg ) {

					$rand_arg = rand( 100000, 999999 );
				}

				$admin_url .= '&' . $rand_arg;

				$admin_url .= '#' . $hash;
			}

			if ( empty( $link_text ) ) {

				return $admin_url;

			}

			$html = '<a href="' . $admin_url . '">' . $link_text . '</a>';

			return $html;
		}

		/*
		 * Rename options array keys, preserving the option modifiers (ie. '_[0-9]', ':disabled', ':use', and '#.*').
		 */
		public function rename_options_by_ext( array $opts, array $version_keys ) {

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! isset( $version_keys[ $ext ] ) ) {	// Nothing to do.

					continue;

				} elseif ( ! is_array( $version_keys[ $ext ] ) ) {	// Must be an array of option keys.

					continue;

				} elseif ( ! isset( $info[ 'opt_version' ] ) ) {	// Nothing to compare to.

					continue;

				} elseif ( empty( $opts[ 'opt_versions' ][ $ext ] ) ) {	// No previous options.

					continue;
				}

				$prev_version = $opts[ 'opt_versions' ][ $ext ];

				foreach ( $version_keys[ $ext ] as $max_version => $opt_keys ) {

					if ( is_numeric( $max_version ) && is_array( $opt_keys ) && $prev_version <= $max_version ) {

						$opts = $this->rename_options_keys( $opts, $opt_keys );

						$opts[ 'opt_versions' ][ $ext ] = $info[ 'opt_version' ];	// Mark as current.
					}
				}
			}

			return $opts;
		}

		public function rename_options_keys( array $opts, array $opt_keys ) {

			foreach ( $opt_keys as $old_key => $new_key ) {

				if ( empty( $old_key ) ) { // Just in case.

					continue;
				}

				$old_key_preg = '/^' . $old_key . '(:disabled|:use|#.*|_[0-9]+)?$/';

				foreach ( preg_grep( $old_key_preg, array_keys( $opts ) ) as $old_key_matched ) {

					if ( ! empty( $new_key ) ) { // Can be empty to remove the option.

						$new_key_matched = preg_replace( $old_key_preg, $new_key . '$1', $old_key_matched );

						$opts[ $new_key_matched ] = $opts[ $old_key_matched ];	// Preserve the old option value.
					}

					unset( $opts[ $old_key_matched ] );
				}
			}

			return $opts;
		}

		/*
		 * limit_text_length() uses PHP's multibyte functions (mb_strlen and mb_substr) for UTF8.
		 */
		public function limit_text_length( $text, $maxlen = 300, $trailing = '', $cleanup_html = true ) {

			static $charset = null;

			if ( null === $charset ) {

				$charset = get_bloginfo( $show = 'charset', $filter = 'raw' );
			}

			if ( $cleanup_html ) {

				$text = $this->cleanup_html_tags( $text );	// Remove any remaining html tags.
			}

			$text = html_entity_decode( self::decode_utf8( $text ), ENT_QUOTES, $charset );

			if ( $maxlen > 0 && function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {

				if ( mb_strlen( $trailing ) > $maxlen ) {	// Just in case.

					$trailing = mb_substr( $trailing, 0, $maxlen );	// Trim the trailing string, if too long.
				}

				if ( mb_strlen( $text ) > $maxlen ) {

					$adj_max_len = $maxlen - mb_strlen( $trailing );

					$text = mb_substr( $text, 0, $adj_max_len );
					$text = trim( preg_replace( '/[^ ]*$/', '', $text ) );	// Remove trailing bits of words.
					$text = preg_replace( '/[,\.]*$/', '', $text );		// Remove trailing puntuation.

				} else {

					$trailing = '';	// Truncate trailing string if text is less than maxlen.
				}

				$text = $text . $trailing;	// Trim and add trailing string (if provided).
			}

			$text = preg_replace( '/&nbsp;/', ' ', $text );	// Just in case.

			return $text;
		}

		public function cleanup_html_tags( $text, $strip_tags = true, $use_img_alt = false ) {

			$text = self::strip_shortcodes( $text );					// Remove any remaining shortcodes.
			$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );				// Put everything on one line.
			$text = preg_replace( '/<\?.*\?'.'>/U', ' ', $text );				// Remove php.
			$text = preg_replace( '/<script\b[^>]*>(.*)<\/script>/Ui', ' ', $text );	// Remove javascript.
			$text = preg_replace( '/<style\b[^>]*>(.*)<\/style>/Ui', ' ', $text );		// Remove inline stylesheets.

			/*
			 * Maybe remove text between ignore markers.
			 */
			if ( false !== strpos( $text, 'wpsso-ignore' ) ) {

				$text = preg_replace( '/<!-- *wpsso-ignore *-->.*<!-- *\/wpsso-ignore *-->/U', ' ', $text );
			}

			/*
			 * Similar to SucomUtil::strip_html(), but includes image alt tags.
			 */
			if ( $strip_tags ) {

				/*
				 * Add missing dot to buttons, headers, lists, etc.
				 */
				$text = preg_replace( '/([\w])<\/(button|dt|h[0-9]+|li|th)>/i', '$1. ', $text );

				/*
				 * Replace list and paragraph tags with a space.
				 */
				$text = preg_replace( '/(<li[^>]*>|<p[^>]*>|<\/p>)/i', ' ', $text );

				/*
				 * Remove remaining html tags.
				 */
				$text_stripped = trim( strip_tags( $text ) );

				/*
				 * Possibly use img alt strings if no text.
				 */
				if ( '' === $text_stripped && $use_img_alt && false !== strpos( $text, '<img ' ) ) {

					$alt_text   = '';
					$alt_prefix = $this->p->opt->get_text( 'plugin_img_alt_prefix' );

					if ( preg_match_all( '/<img [^>]*alt=["\']([^"\'>]*)["\']/Ui', $text, $all_matches, PREG_PATTERN_ORDER ) ) {

						foreach ( $all_matches[ 1 ] as $alt ) {

							$alt = trim( $alt );

							if ( ! empty( $alt ) ) {

								$alt = empty( $alt_prefix ) ? $alt : $alt_prefix . ' ' . $alt;

								/*
								 * Maybe add a period after the image alt text.
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

			/*
			 * Replace 1+ spaces to a single space.
			 */
			$text = preg_replace( '/(\xC2\xA0|\s)+/s', ' ', $text );

			return trim( $text );
		}

		public function get_validators( array $mod, $form = null ) {

			/*
			 * We do not want to validate settings pages in the back-end, so validators are only provided for known
			 * modules (comment, post, term, and user). If we're on the front-end, validating the current webpage URL
			 * is fine.
			 */
			if ( is_admin() ) {

				if ( empty( $mod[ 'obj' ] ) ) {

					return array();
				}
			}

			$can_crawl_url = true;
			$canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = true );

			if ( empty( $canonical_url ) ) {

				$can_crawl_url = false;

			} elseif ( ! $mod[ 'is_public' ] ) {

				$can_crawl_url = false;

			} elseif ( $mod[ 'is_post' ] ) {

				if ( 'publish' !== $mod[ 'post_status' ] ) {

					$can_crawl_url = false;
				}
			}

			$have_amp          = $mod[ 'is_post' ] && $mod[ 'id' ] && function_exists( 'amp_get_permalink' ) ? true : false;
			$have_schema       = $this->p->avail[ 'p' ][ 'schema' ] ? true : false;
			$amp_url_enc       = $have_amp ? urlencode( amp_get_permalink( $mod[ 'id' ] ) ) : '';
			$canonical_url_enc = urlencode( $canonical_url );

			$validators = array(
				'amp' => array(
					'title' => _x( 'The AMP Project Validator', 'option label', 'wpsso' ),
					'type'  => _x( 'AMP Markup', 'validator type', 'wpsso' ) . ( $have_amp ? '' : ' *' ),
					'url'   => $have_amp ? 'https://validator.ampproject.org/#url=' . $amp_url_enc : '',
				),
				'facebook-debugger' => array(
					'title' => _x( 'Facebook Sharing Debugger', 'option label', 'wpsso' ),
					'type'  => _x( 'Open Graph', 'validator type', 'wpsso' ),
					'url'   => 'https://developers.facebook.com/tools/debug/?q=' . $canonical_url_enc,
				),
				'facebook-microdata' => array(
					'title' => _x( 'Facebook Microdata Debug Tool', 'option label', 'wpsso' ),
					'type'  => _x( 'Microdata', 'validator type', 'wpsso' ),
					'url'   => 'https://business.facebook.com/ads/microdata/debug?url=' . $canonical_url_enc,
				),
				'google-page-speed' => array(
					'title' => _x( 'Google PageSpeed Insights', 'option label', 'wpsso' ),
					'type'  => _x( 'PageSpeed', 'validator type', 'wpsso' ),
					'url'   => 'https://pagespeed.web.dev/report?url=' . $canonical_url_enc,
				),
				'google-rich-results' => array(
					'title' => _x( 'Google Rich Results Test', 'option label', 'wpsso' ),
					'type'  => _x( 'Rich Results', 'validator type', 'wpsso' ) . ( $have_schema ? '' : ' **' ),
					'url'   => $have_schema ? 'https://search.google.com/test/rich-results?url=' . $canonical_url_enc : '',
				),
				'linkedin' => array(
					'title' => _x( 'LinkedIn Post Inspector', 'option label', 'wpsso' ),
					'type'  => _x( 'oEmbed Data', 'validator type', 'wpsso' ),
					'url'   => 'https://www.linkedin.com/post-inspector/inspect/' . $canonical_url_enc,
				),
				'pinterest' => array(
					'title' => _x( 'Pinterest Rich Pins Validator', 'option label', 'wpsso' ),
					'type'  => _x( 'Rich Pins', 'validator type', 'wpsso' ),
					'url'   => 'https://developers.pinterest.com/tools/url-debugger/?link=' . $canonical_url_enc,
				),
				'schema-markup-validator' => array(
					'title' => _x( 'Schema Markup Validator', 'option label', 'wpsso' ),
					'type'  => _x( 'Schema Markup', 'validator type', 'wpsso' ) . ( $have_schema ? '' : ' *' ),
					'url'   => $have_schema ? 'https://validator.schema.org/#url=' . $canonical_url_enc : '',
				),
				'twitter' => array(
					'title'     => _x( 'Twitter Card Validator', 'option label', 'wpsso' ),
					'type'      => _x( 'Twitter Card', 'validator type', 'wpsso' ),
					'url'       => is_object( $form ) ? 'https://cards-dev.twitter.com/validator' : '',
					'extra_msg' => is_object( $form ) ? $form->get_no_input_clipboard( $canonical_url ) : '',
				),
				'w3c' => array(
					'title' => _x( 'W3C Markup Validator', 'option label', 'wpsso' ),
					'type'  => _x( 'HTML Markup', 'validator type', 'wpsso' ),
					'url'   => 'https://validator.w3.org/nu/?doc=' . $canonical_url_enc,
				),
			);

			if ( ! $can_crawl_url ) {

				foreach ( $validators as $key => $arr ) {

					$validators[ $key ][ 'url' ] = '';
				}
			}

			return $validators;
		}

		/*
		 * Maybe set the notice reference URL and translated message.
		 *
		 * Example messages, depending on the $mod array:
		 *
		 *	adding schema organization
		 *	adding schema organization for this page
		 *	adding schema organization for page ID 123
		 */
		public function maybe_set_ref( $canonical_url = null, $mod = false, $msg_transl = '' ) {

			static $is_admin = null;

			if ( null === $is_admin ) {

				$is_admin = is_admin();
			}

			if ( ! $is_admin ) {

				return false;
			}

			if ( empty( $canonical_url ) ) {

				$canonical_url = $this->get_canonical_url( $mod );
			}

			if ( empty( $msg_transl ) ) {

				return $this->p->notice->set_ref( $canonical_url, $mod );
			}

			if ( empty( $mod[ 'id' ] ) ) {

				return $this->p->notice->set_ref( $canonical_url, $mod, $msg_transl );
			}

			if ( $mod[ 'is_post' ] && $mod[ 'post_type_label_single' ] ) {

				$name_transl = mb_strtolower( $mod[ 'post_type_label_single' ] );

			} elseif ( $mod[ 'is_term' ] && $mod[ 'tax_label_single' ] ) {

				$name_transl = mb_strtolower( $mod[ 'tax_label_single' ] );

			} else {

				$name_transl = $mod[ 'name_transl' ];	// Translated module name.
			}

			if ( self::is_mod_current_screen( $mod ) ) {

				// translators: %1$s is an action message, %2$s is the module or post type name.
				$msg_transl = sprintf( __( '%1$s for this %2$s', 'wpsso' ), $msg_transl, $name_transl );

				/*
				 * Exclude the $mod array to avoid adding an 'Edit' link to the notice message.
				 */
				return $this->p->notice->set_ref( $canonical_url, false, $msg_transl );

			}

			// translators: %1$s is an action message, %2$s is the module or post type name and %3$d is the object ID.
			$msg_transl = sprintf( __( '%1$s for %2$s ID %3$d', 'wpsso' ), $msg_transl, $name_transl, $mod[ 'id' ] );

			return $this->p->notice->set_ref( $canonical_url, $mod, $msg_transl );
		}

		public function maybe_unset_ref( $canonical_url ) {

			static $is_admin = null;

			if ( null === $is_admin ) {

				$is_admin = is_admin();
			}

			if ( ! $is_admin ) {

				return false;
			}

			return $this->p->notice->unset_ref( $canonical_url );
		}

		/*
		 * See WpssoCmcfXml->get().
		 * See WpssoGmfXml->get().
		 * See WpssoAdmin->registered_setting_sanitation().
		 * See WpssoAdmin->show_metabox_cache_status().
		 * See WpssoHead->get_head_array().
		 * See WpssoMessagesTooltipPlugin->get().
		 * See WpssoPage->get_the_content().
		 * See WpssoProMediaFacebook->filter_video_details().
		 * See WpssoProMediaSlideshare->filter_video_details().
		 * See WpssoProMediaVimeo->filter_video_details().
		 * See WpssoProMediaWistia->filter_video_details().
		 * See WpssoProReviewShopperApproved->filter_og().
		 * See WpssoProReviewStamped->filter_og().
		 * See WpssoProUtilShorten->get_short_url().
		 * See WpssoSchema->get_schema_types_array().
		 * See WpssoSchema->get_schema_type_child_family().
		 * See WpssoSchema->get_schema_type_children().
		 * See WpssoSchema->get_schema_type_row_class().
		 * See WpssoUtil->get_image_url_info().
		 */
		public function get_cache_exp_secs( $cache_md5_pre, $cache_type = 'transient', $mod = false ) {

			$cache_exp_secs = 0;	// No caching by default.

			if ( ! empty( $this->p->cf[ 'wp' ][ $cache_type ][ $cache_md5_pre ] ) ) {

				$cache_info =& $this->p->cf[ 'wp' ][ $cache_type ][ $cache_md5_pre ];	// Shortcut variable.

				if ( isset( $cache_info[ 'value' ] ) ) {

					$cache_exp_secs = $cache_info[ 'value' ];
				}

				if ( is_array( $mod ) ) {

					if ( ! empty( $cache_info[ 'conditional_values' ] ) ) {

						foreach ( $cache_info[ 'conditional_values' ] as $cond => $val ) {

							/*
							 * If one of these $mod array conditions is true, then use the associated value.
							 */
							if ( ! empty( $mod[ $cond ] ) ) {

								$cache_exp_secs = $val;

								break;	// Stop here.
							}
						}
					}
				}

				/*
				 * Example filter names:
				 *
				 *	'wpsso_cache_expire_api_response' ( DAY_IN_SECONDS )
				 *	'wpsso_cache_expire_head_markup' ( MONTH_IN_SECONDS )
				 *	'wpsso_cache_expire_image_info' ( DAY_IN_SECONDS )
				 *	'wpsso_cache_expire_schema_types' ( MONTH_IN_SECONDS )
				 *	'wpsso_cache_expire_short_url' ( YEAR_IN_SECONDS )
				 *	'wpsso_cache_expire_the_content' ( HOUR_IN_SECONDS )
				 */
				if ( ! empty( $cache_info[ 'filter' ] ) ) {

					$cache_exp_secs = (int) apply_filters( $cache_info[ 'filter' ], $cache_exp_secs, $cache_type, $mod );
				}
			}

			return $cache_exp_secs;
		}

		public function is_task_running( $user_id, $task_name, $cache_exp_secs, $cache_id ) {

			$running_task_name = get_transient( $cache_id );

			if ( false !== $running_task_name ) {

				if ( $user_id ) {

					$task_name_transl = _x( $task_name, 'task name', 'wpsso' );
					$notice_msg       = sprintf( __( 'Aborting task to %s - another background task is running.', 'wpsso' ), $task_name_transl );
					$notice_key       = $running_task_name . '-task-running';

					$this->p->notice->warn( $notice_msg, $user_id, $notice_key );
				}

				return true;
			}

			set_transient( $cache_id, $task_name, $cache_exp_secs );

			return false;
		}

		public function set_task_limit( $user_id, $task_name, $cache_exp_secs ) {

			$ret = set_time_limit( $cache_exp_secs );

			if ( ! $ret ) {

				$human_time       = human_time_diff( 0, $cache_exp_secs );
				$task_name_transl = _x( $task_name, 'task name', 'wpsso' );
				$notice_msg = sprintf( __( 'The PHP %1$s function failed to set a maximum execution time of %2$s to %3$s.', 'wpsso' ),
					'<code>set_time_limit()</code>', $human_time, $task_name_transl );
				$notice_key = $task_name . '-task-set-time-limit-error';

				$this->p->notice->err( $notice_msg, $user_id, $notice_key );
			}

			return $ret;
		}

		/*
		 * Returns for example "#sso-post-123", #sso-term-123-tax-faq-category with a $mod array or "#sso-" without.
		 *
		 * Called by WpssoFaqShortcodeFaq->do_shortcode().
		 * Called by WpssoFaqShortcodeQuestion->do_shortcode().
		 * Called by WpssoJsonTypeThing->filter_json_data_https_schema_org_thing().
		 */
		public static function get_fragment_anchor( $mod = null ) {

			return '#sso-' . ( $mod ? self::get_mod_anchor( $mod ) : '' );
		}
	}
}
