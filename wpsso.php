<?php
/*
 * Plugin Name: WPSSO Core
 * Plugin Slug: wpsso
 * Text Domain: wpsso
 * Domain Path: /languages
 * Plugin URI: https://wpsso.com/extend/plugins/wpsso/
 * Assets URI: https://surniaulula.github.io/wpsso/assets/
 * Author: JS Morisset
 * Author URI: https://wpsso.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Description: Present your content at its best for social sites and search results, no matter how URLs are shared, reshared, messaged, posted, embedded, or crawled.
 * Requires PHP: 7.4.33
 * Requires At Least: 5.9
 * Tested Up To: 6.9
 * WC Tested Up To: 10.4.2
 * Version: 21.11.0
 *
 * Version Numbering: {major}.{minor}.{bugfix}[-{stage}.{level}]
 *
 *      {major}         Major structural code changes and/or incompatible API changes (ie. breaking changes).
 *      {minor}         New functionality was added or improved in a backwards-compatible manner.
 *      {bugfix}        Backwards-compatible bug fixes or small improvements.
 *      {stage}.{level} Pre-production release: dev < a (alpha) < b (beta) < rc (release candidate).
 *
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'Wpsso' ) ) {

	class Wpsso {

		/*
		 * Library class object variables.
		 */
		public $admin;		// WpssoAdmin (admin menus and settings page loader) class object.
		public $cache;		// SucomCache (object and file caching) class object.
		public $check;		// WpssoCheck class object.
		public $comment;	// WpssoComment class object (extends WpssoAbstractWpMeta).
		public $conflict;	// WpssoConflict (admin plugin conflict checks) class object.
		public $debug;		// SucomDebug or SucomNoDebug class object.
		public $edit;		// WpssoEdit class object.
		public $head;		// WpssoHead class object.
		public $loader;		// WpssoLoader class object.
		public $media;		// WpssoMedia (images, videos, etc.) class object.
		public $msgs;		// WpssoMessages (admin tooltip messages) class object.
		public $notice;		// SucomNotice or SucomNoNotice class object.
		public $opt;		// WpssoOptions class object.
		public $page;		// WpssoPage (page title, desc, etc.) class object.
		public $post;		// WpssoPost class object (extends WpssoAbstractWpMeta).
		public $reg;		// WpssoRegister class object.
		public $script;		// WpssoScript (admin jquery tooltips) class object.
		public $style;		// WpssoStyle (admin styles) class object.
		public $term;		// WpssoTerm class object (extends WpssoAbstractWpMeta).
		public $user;		// WpssoUser class object (extends WpssoAbstractWpMeta).
		public $util;		// WpssoUtil (extends SucomUtil) class object.

		/*
		 * Library class object variables for meta tags and markup.
		 */
		public $link_rel;	// WpssoLinkRel class object.
		public $meta_name;	// WpssoMetaName class object.
		public $oembed;		// WpssoOembed class object.
		public $og;		// WpssoOpenGraph class object.
		public $pinterest;	// WpssoPinterest class object.
		public $schema;		// WpssoSchema class object.
		public $tc;		// WpssoTwitterCard class object.

		/*
		 * Reference variables (config, options, modules, etc.).
		 */
		public $id           = 'wpsso';	// Plugin id.
		public $cf           = array();	// Config array from WpssoConfig::get_config().
		public $avail        = array();	// Third-party plugin checks.
		public $options      = array();	// Blog options array.
		public $site_options = array();	// Multisite options array.
		public $sc           = array();	// Loaded shortcode objects.

		private static $instance = null;	// Wpsso class object.

		/*
		 * Wpsso constructor.
		 */
		public function __construct() {

			$plugin_file = __FILE__;
			$plugin_dir  = trailingslashit( dirname( $plugin_file ) );

			require_once $plugin_dir . 'lib/config.php';

			$this->cf = WpssoConfig::get_config();

			WpssoConfig::set_constants( $plugin_file );

			WpssoConfig::require_libs( $plugin_file );	// Includes the register.php class library.

			$this->reg = new WpssoRegister( $this );	// Activate, deactivate, uninstall hooks.

			/*
			 * The WordPress 'init' action fires after WordPress has finished loading, but before any headers are sent.
			 *
			 * Most of WordPress is loaded at this stage, and the user is authenticated. WordPress continues to load on
			 * the 'init' hook (e.g. widgets), and many plugins instantiate themselves on it for all sorts of reasons
			 * (e.g. they need a user, a taxonomy, etc.).
			 */
			add_action( 'init', array( $this, 'set_config' ), WPSSO_INIT_CONFIG_PRIORITY );			// Runs at init -10.
			add_action( 'widgets_init', array( $this, 'register_widgets' ), 10 );				// Runs at init 1.
			add_action( 'init', array( $this, 'set_options' ), WPSSO_INIT_OPTIONS_PRIORITY );		// Runs at init 9.
			add_action( 'init', array( $this, 'set_objects' ), WPSSO_INIT_OBJECTS_PRIORITY );		// Runs at init 10.
			add_action( 'init', array( $this, 'init_shortcodes' ), WPSSO_INIT_SHORTCODES_PRIORITY );	// Runs at init 11.
			add_action( 'init', array( $this, 'init_plugin' ), WPSSO_INIT_PLUGIN_PRIORITY );		// Runs at init 12.

			/*
			 * The 'wpsso_init_textdomain' action is run after the $check, $avail, and $debug properties have been instantiated.
			 */
			add_action( 'wpsso_init_textdomain', array( $this, 'init_textdomain' ), -1000, 0 );

			/*
			 * The 'change_locale' action also runs the 'wpsso_init_textdomain' action.
			 */
			add_action( 'change_locale', array( $this, 'change_locale' ), -1000, 1 );

			/*
			 * If the "Use Plugin MO Translations" option is enabled, returns the file path to the plugin or add-on mo file.
			 */
			add_filter( 'load_textdomain_mofile', array( $this, 'textdomain_mofile' ), 10, 3 );

			/*
			 * Declare compatibility with WooCommerce features.
			 */
			if ( ! empty( $this->cf[ 'plugin' ][ 'wpsso' ][ 'wc_compat' ] ) ) {

				$wc_compat = $this->cf[ 'plugin' ][ 'wpsso' ][ 'wc_compat' ];

				add_action( 'before_woocommerce_init', function () use ( $plugin_file, $wc_compat ) {

					if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {

						foreach ( $wc_compat as $feature ) {

							\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( $feature, $plugin_file, true );
						}
					}
				}, 10, 0 );

				unset( $wc_compat );
			}

			unset( $plugin_file, $plugin_dir );

		}

		public static function &get_instance() {

			if ( null === self::$instance ) {

				self::$instance = new self;
			}

			return self::$instance;
		}

		/*
		 * Force a refresh of the plugin config.
		 *
		 * Runs at init priority -10 and called by WpssoRegister->activate_plugin() as well.
		 */
		public function set_config( $activate = false ) {

			$this->cf = WpssoConfig::get_config( $read_cache = false );
		}

		/*
		 * Runs at init 1.
		 */
		public function register_widgets() {

			/*
			 * Load lib/widget/* library files.
			 */
			$classnames = $this->get_lib_classnames( 'widget' );	// Always returns an array.

			foreach ( $classnames as $id => $classname ) {

				register_widget( $classname );
			}
		}

		/*
		 * Runs at init priority 9.
		 *
		 * Called by WpssoRegister->activate_plugin() as well.
		 */
		public function set_options( $activate = false ) {

			if ( $activate && defined( 'WPSSO_RESET_ON_ACTIVATE' ) && WPSSO_RESET_ON_ACTIVATE ) {

				delete_option( WPSSO_OPTIONS_NAME );

				$this->options = false;

			} else $this->options = get_option( WPSSO_OPTIONS_NAME );

			if ( ! is_array( $this->options ) ) {

				/*
				 * The set_options() action is run before the set_objects() action, where the WpssoOptions class is
				 * instantiated, so the WpssoOptions->get_defaults() method is not available yet. Load the defaults
				 * directly from the config array and trigget a defaults reload in set_objects().
				 */
				$this->options = $this->cf[ 'opt' ][ 'defaults' ];

				$this->options[ '__reload_defaults' ] = true;
			}

			if ( is_multisite() ) {

				$this->site_options = get_site_option( WPSSO_SITE_OPTIONS_NAME );

				if ( ! is_array( $this->site_options ) ) {

					$this->site_options = $this->cf[ 'opt' ][ 'site_defaults' ];

					$this->site_options[ '__reload_defaults' ] = true;
				}

				if ( is_array( $this->options ) && is_array( $this->site_options ) ) {

					$blog_id = get_current_blog_id();

					$defined_constants = get_defined_constants( $categorize = true );

					foreach ( $this->site_options as $key => $val ) {

						if ( false !== strpos( $key, ':use' ) ) {

							continue;

						} elseif ( isset( $this->site_options[ $key . ':use' ] ) ) {

							switch ( $this->site_options[ $key . ':use' ] ) {

								case 'force':

									$this->options[ $key ]               = $this->site_options[ $key ];
									$this->options[ $key . ':disabled' ] = true;

									break;

								case 'empty':	// Blank string, null, false, or 0.

									if ( empty( $this->options[ $key ] ) ) {

										$this->options[ $key ] = $this->site_options[ $key ];
									}

									break;
							}
						}

						$constant_name = 'WPSSO_ID_' . $blog_id . '_OPT_' . strtoupper( $key );

						if ( isset( $defined_constants[ 'user' ][ $constant_name ] ) ) {

							$this->options[ $key ] = $defined_constants[ 'user' ][ $constant_name ];

							$this->options[ $key . ':disabled' ] = true;
						}
					}
				}
			}
		}

		/*
		 * Runs at init priority 10.
		 *
		 * Called by WpssoRegister->activate_plugin() as well.
		 */
		public function set_objects( $activate = false ) {

			$is_doing = array(
				'admin' => is_admin(),
				'ajax'  => SucomUtilWP::doing_ajax(),
				'cron'  => SucomUtilWP::doing_cron(),
				'dev'   => SucomUtilWP::doing_dev(),
			);

			/*
			 * Check for defined constants (in order):
			 *
			 * WPSSO_CRON_DEBUG_LOG
			 * WPSSO_CRON_DEBUG_HTML
			 * WPSSO_AJAX_DEBUG_LOG
			 * WPSSO_AJAX_DEBUG_HTML
			 * WPSSO_ADMIN_DEBUG_LOG
			 * WPSSO_ADMIN_DEBUG_HTML
			 * WPSSO_DEBUG_LOG
			 * WPSSO_DEBUG_HTML
			 */
			$debug_log  = $this->get_const_status( 'DEBUG_LOG', $is_doing );
			$debug_html = $this->get_const_status( 'DEBUG_HTML', $is_doing );

			$this->check = new WpssoCheck( $this );

			$this->avail = $this->check->get_avail();	// Uses $this->options for availability checks.

			/*
			 * Make sure a debug object variable is always available.
			 */
			if ( null === $debug_html ) {	// Constant not defined.

				$debug_html = empty( $this->options[ 'plugin_debug_html' ] ) ? false : true;
			}

			if ( $debug_log || $debug_html ) {

				require_once WPSSO_PLUGINDIR . 'lib/com/debug.php';	// Only load class when needed.

				$this->debug = new SucomDebug( $this, array(
					'log'  => $debug_log,
					'html' => $debug_html,
				) );

				if ( $this->debug->enabled ) {

					global $wp_version;

					$this->debug->log( __METHOD__ . ' init ' . WPSSO_INIT_OBJECTS_PRIORITY );
					$this->debug->log( 'debug enabled on ' . gmdate( 'c' ) );
					$this->debug->log( 'PHP version ' . phpversion() );
					$this->debug->log( 'WP version ' . $wp_version );
					$this->debug->log( 'WP home URL ' . get_home_url() );
					$this->debug->log( 'WP site URL ' . get_site_url() );
					$this->debug->log_arr( 'generator list', $this->check->get_ext_gen_list() );
				}

			} else $this->debug = new SucomNoDebug();	// Class always loaded in WpssoConfig::require_libs().

			if ( $this->debug->enabled ) {

				$this->debug->mark_diff( 'debug class loaded' );
			}

			/*
			 * The 'wpsso_init_textdomain' action is run after the $check, $avail, and $debug properties have been instantiated.
			 *
			 * The WordPress 'change_locale' action also runs the 'wpsso_init_textdomain' action.
			 */
			do_action( 'wpsso_init_textdomain' );

			/*
			 * Make sure a notice object variable is always available.
			 */
			if ( $is_doing[ 'admin' ] || $is_doing[ 'cron' ] ) {

				require_once WPSSO_PLUGINDIR . 'lib/messages.php';	// Only load class when needed.
				require_once WPSSO_PLUGINDIR . 'lib/com/notice.php';	// Only load class when needed.

				$this->msgs   = new WpssoMessages( $this );	// Admin tooltip messages.
				$this->notice = new SucomNotice( $this );

			} else $this->notice = new SucomNoNotice();	// Class always loaded in WpssoConfig::require_libs().

			if ( $this->debug->enabled ) {

				$this->debug->mark_diff( 'notice class loaded' );
			}

			$this->cache = new SucomCache( $this );
			$this->util  = new WpssoUtil( $this );		// Extends SucomUtil.
			$this->opt   = new WpssoOptions( $this );

			do_action( 'wpsso_init_options' );

			$this->script = new WpssoScript( $this );
			$this->style  = new WpssoStyle( $this );

			/*
			 * Note that when running a cron task, admin is false.
			 */
			if ( $is_doing[ 'admin' ] ) {

				require_once WPSSO_PLUGINDIR . 'lib/admin.php';		// Only load class when needed.
				require_once WPSSO_PLUGINDIR . 'lib/conflict.php';	// Only load class when needed.
				require_once WPSSO_PLUGINDIR . 'lib/edit.php';		// Only load class when needed.
				require_once WPSSO_PLUGINDIR . 'lib/com/form.php';	// Only load class when needed.

				$this->admin    = new WpssoAdmin( $this );	// Admin menus and settings pages.
				$this->conflict = new WpssoConflict( $this );	// Admin plugin conflict checks.
				$this->edit     = new WpssoEdit( $this );	// Admin editing page metabox.
			}

			/*
			 * Setup media and module classes:
			 *
			 *	$comment
			 *	$media
			 *	$page
			 *	$post
			 *	$term
			 *	$user
			 */
			$this->comment = new WpssoComment( $this );		// Extends WpssoAbstractWpMeta.
			$this->media   = new WpssoMedia( $this );
			$this->page    = new WpssoPage( $this );
			$this->post    = new WpssoPost( $this );		// Extends WpssoAbstractWpMeta.
			$this->term    = new WpssoTerm( $this );		// Extends WpssoAbstractWpMeta.
			$this->user    = new WpssoUser( $this );		// Extends WpssoAbstractWpMeta.

			if ( $this->debug->enabled ) {

				$this->debug->mark_diff( 'media and module classes loaded' );
			}

			/*
			 * Setup classe for meta tags and Schema markup:
			 *
			 *	$head
			 *	$link_rel
			 *	$meta_name
			 *	$og
			 *	$pinterest
			 *	$schema
			 *	$tc
			 *	$loader
			 */
			$this->head      = new WpssoHead( $this );
			$this->link_rel  = new WpssoLinkRel( $this );		// Link relation tags.
			$this->meta_name = new WpssoMetaName( $this );		// Meta name tags.
			$this->oembed    = new WpssoOembed( $this );		// Oembed response data.
			$this->og        = new WpssoOpenGraph( $this );		// Open Graph meta tags.
			$this->pinterest = new WpssoPinterest( $this );		// Pinterest image markup.
			$this->schema    = new WpssoSchema( $this );		// Schema json data.
			$this->tc        = new WpssoTwitterCard( $this );	// X (Twitter) Card meta tags.

			if ( $this->debug->enabled ) {

				$this->debug->mark_diff( 'meta tags and schema classes loaded' );
			}

			do_action( 'wpsso_init_objects_preloader' );

			$this->loader = new WpssoLoader( $this );	// Modules loader.

			do_action( 'wpsso_init_objects' );

			if ( $this->debug->enabled ) {

				$this->debug_reminder( $is_doing );

				$this->debug->mark_diff( 'objects defined' );
			}
		}

		/*
		 * Runs at init priority 11.
		 */
		public function init_shortcodes() {

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init shortcodes' );	// Begin timer.
			}

			/*
			 * Load lib/shortcode/* library files.
			 */
			$classnames = $this->get_lib_classnames( 'shortcode' );	// Always returns an array.

			foreach ( $classnames as $id => $classname ) {

				/*
				 * Note that Wpsso->sc[ 'schema' ] is used by WpssoSscFilters->filter_json_data_graph_element().
				 */
				if ( ! isset( $this->sc[ $id ] ) ) {	// Just in case.

					$this->sc[ $id ] = new $classname( $this );

					if ( $this->debug->enabled ) {

						$this->debug->mark_diff( 'added ' . $id . ' shortcode' );
					}
				}
			}

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init shortcodes' );	// Begin timer.
			}
		}

		/*
		 * Runs at init priority 12.
		 */
		public function init_plugin() {

			if ( $this->debug->enabled ) {

				$this->debug_hooks();
			}

			/*
			 * All WPSSO Core objects are instantiated and configured.
			 *
			 * See SucomAbstractAddOn->init_plugin_notices().
			 */
			do_action( 'wpsso_init_plugin' );
		}

		/*
		 * The 'wpsso_init_textdomain' action is run after the $check, $avail, and $debug properties are instantiated.
		 */
		public function init_textdomain() {

			load_plugin_textdomain( 'wpsso', false, 'wpsso/languages/' );
		}

		/*
		 * The 'change_locale' action also runs the 'wpsso_init_textdomain' action.
		 */
		public function change_locale( $locale ) {

			do_action( 'wpsso_init_textdomain' );
		}

		/*
		 * If the "Use Plugin MO Translations" option is enabled, returns the file path to the plugin or add-on mo file.
		 */
		public function textdomain_mofile( $wp_mofile, $domain ) {

			if ( empty( $this->options[ 'plugin_load_mofiles' ] ) ) {	// Nothing to do.

				return $wp_mofile;
			}

			if ( 0 === strpos( $domain, 'wpsso' ) ) {	// Optimize.

				foreach ( $this->cf[ 'plugin' ] as $ext => $info ) {

					if ( empty( $info[ 'slug' ] ) ) {	// Just in case.

						continue;

					} elseif ( $domain === $info[ 'slug' ] ) {

						$languages_mofile = 'languages/' . basename( $wp_mofile );

						if ( $plugin_mofile = WpssoConfig::get_ext_file_path( $ext, $languages_mofile ) ) {

							global $l10n;

							unset( $l10n[ $domain ] );	// Prevent merging.

							return $plugin_mofile;
						}

						break;	// Stop here.
					}
				}
			}

			return $wp_mofile;
		}

		/*
		 * Check for defined constants (in order):
		 *
		 * WPSSO_CRON_DEBUG_LOG
		 * WPSSO_CRON_DEBUG_HTML
		 * WPSSO_AJAX_DEBUG_LOG
		 * WPSSO_AJAX_DEBUG_HTML
		 * WPSSO_ADMIN_DEBUG_LOG
		 * WPSSO_ADMIN_DEBUG_HTML
		 * WPSSO_DEBUG_LOG
		 * WPSSO_DEBUG_HTML
		 *
		 * See Wpsso->get_const_status().
		 * See Wpsso->get_const_status_transl().
		 */
		public function get_const( $const_suffix, $is_doing = null ) {

			if ( ! is_array( $is_doing ) ) {

				$is_doing = array(
					'admin' => is_admin(),
					'ajax'  => SucomUtilWP::doing_ajax(),
					'cron'  => SucomUtilWP::doing_cron(),
					'dev'   => SucomUtilWP::doing_dev(),
				);
			}

			if ( $is_doing[ 'cron' ] && defined( 'WPSSO_CRON_' . $const_suffix ) ) {

				return 'WPSSO_CRON_' . $const_suffix;

			} elseif ( $is_doing[ 'ajax' ] && defined( 'WPSSO_AJAX_' . $const_suffix ) ) {

				return 'WPSSO_AJAX_' . $const_suffix;

			} elseif ( $is_doing[ 'admin' ] && defined( 'WPSSO_ADMIN_' . $const_suffix ) ) {

				return 'WPSSO_ADMIN_' . $const_suffix;

			} elseif ( defined( 'WPSSO_' . $const_suffix ) ) {

				return 'WPSSO_' . $const_suffix;
			}

			return null;	// Constant not defined.
		}

		/*
		 * See Wpsso->set_objects().
		 * See WpssoSubmenuAdvanced->get_table_rows().
		 */
		public function get_const_status( $const_suffix, $is_doing = null ) {

			$const_name = $this->get_const( $const_suffix, $is_doing );	// Returns null if constant not defined.

			return $const_name ? constant( $const_name ) : null;
		}

		/*
		 * See WpssoSubmenuAdvanced->get_table_rows().
		 */
		public function get_const_status_transl( $const_suffix, $is_doing = null ) {

			$const_name = $this->get_const( $const_suffix, $is_doing );	// Returns null if constant not defined.

			if ( $const_name ) {

				if ( constant( $const_name ) ) {

					return sprintf( _x( '(%s constant is true)', 'option comment', 'wpsso' ), $const_name );
				}

				return sprintf( _x( '(%s constant is false)', 'option comment', 'wpsso' ), $const_name );
			}

			return '';
		}

		public function get_lib_classnames( $type_dir ) {

			$classnames = array();

			foreach ( $this->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! isset( $info[ 'lib' ][ $type_dir ] ) ) {

					continue;

				} elseif ( ! is_array( $info[ 'lib' ][ $type_dir ] ) ) {

					continue;
				}

				foreach ( $info[ 'lib' ][ $type_dir ] as $sub_dir => $libs ) {

					if ( is_array( $libs ) ) {

						/*
						 * Skip loading admin library modules if not in admin back-end.
						 */
						if ( 'admin' === $sub_dir && ! is_admin() ) {

							continue;
						}

						foreach ( $libs as $id => $label ) {

							$lib_path = $type_dir . '/' . $sub_dir . '/' . $id;

							$classname = apply_filters( $ext . '_load_lib', false, $lib_path );

							if ( is_string( $classname ) && class_exists( $classname ) ) {

								$classnames[ $sub_dir . '-' . $id ] = $classname;
							}
						}

					} elseif ( is_string( $libs ) ) {

						$id        = $sub_dir;
						$label     = $libs;
						$lib_path  = $type_dir . '/' . $id;
						$classname = apply_filters( $ext . '_load_lib', false, $lib_path );

						if ( is_string( $classname ) && class_exists( $classname ) ) {

							$classnames[ $id ] = $classname;
						}
					}
				}
			}

			return $classnames;
		}

		public function get_options( $opt_key = false, $def_value = null ) {

			if ( false !== $opt_key ) {

				if ( isset( $this->options[ $opt_key ] ) ) {

					return $this->options[ $opt_key ];
				}

				return $def_value;
			}

			return $this->options;
		}

		public function debug_hooks() {

			/*
			 * Show debug messages after the head and footer sections.
			 *
			 * Do not include the "shutdown" action as that would create HTML after the html closing tag.
			 */
			foreach ( array( 'wp_head', 'wp_footer', 'admin_head', 'admin_footer' ) as $action ) {

				add_action( $action, array( $this, 'show_debug' ), PHP_INT_MAX );
			}

			/*
			 * Show plugin settings just after the footer section.
			 */
			foreach ( array( 'wp_footer', 'admin_footer' ) as $action ) {

				add_action( $action, array( $this, 'show_config' ), PHP_INT_MAX );
			}
		}

		/*
		 * Only runs when debug is enabled.
		 */
		public function show_debug() {

			echo "\n\n" . '<!-- wpsso doing action "' . current_action() . '" -->' . "\n\n";

			$this->debug->show_html( null, 'debug log' );
		}

		/*
		 * Only runs when debug is enabled.
		 */
		public function show_config() {

			if ( ! $this->debug->enabled ) {	// Just in case.

				return;
			}

			/*
			 * Show constants.
			 */
			$defined_constants = get_defined_constants( $categorize = true );

			$defined_constants[ 'user' ][ 'WPSSO_NONCE_NAME' ] = '********';

			if ( is_multisite() ) {

				$ms_defined_constants = SucomUtil::preg_grep_keys( '/^(MULTISITE|^SUBDOMAIN_INSTALL|.*_SITE)$/', $defined_constants[ 'user' ] );

				$this->debug->show_html( $ms_defined_constants, 'multisite constants' );
			}

			$wpsso_defined_constants = SucomUtil::preg_grep_keys( '/^WPSSO_/', $defined_constants[ 'user' ] );

			$this->debug->show_html( $wpsso_defined_constants, 'wpsso constants' );

			/*
			 * Show active plugins.
			 */
			$active_plugins = SucomPlugin::get_active_plugins();

			$this->debug->show_html( print_r( $active_plugins, true ), 'active plugins' );

			/*
			 * Show available modules.
			 */
			$this->debug->show_html( print_r( $this->avail, true ), 'available features' );

			/*
			 * Show all plugin options.
			 */
			$opts = $this->options;

			foreach ( $opts as $key => $val ) {

				switch ( $key ) {

					case ( false !== strpos( $key, '_js_' ) ? true : false ):
					case ( false !== strpos( $key, '_css_' ) ? true : false ):
					case ( preg_match( '/(_css|_js|_html)$/', $key ) ? true : false ):
					case ( preg_match( '/_(key|secret|tid|token)$/', $key ) ? true : false ):

						if ( '' !== $opts[ $key ] ) $opts[ $key ] = '[removed]';

						break;
				}
			}

			$this->debug->show_html( $opts, 'wpsso settings' );
		}

		private function debug_reminder( array $is_doing ) {

			if ( $this->debug->is_enabled( 'log' ) ) {

				$this->debug->log( 'WP debug log mode is active' );
			}

			if ( $this->debug->is_enabled( 'html' ) ) {

				$this->debug->log( 'HTML debug mode is active' );
			}

			if ( $is_doing[ 'admin' ] && ! $is_doing[ 'dev' ] ) {

				$info         = $this->cf[ 'plugin' ][ 'wpsso' ];
				$notice_msg   = '';
				$notice_key   = 'debug-mode-is-active';
				$dismiss_time = 12 * HOUR_IN_SECONDS;

				if ( $this->debug->is_enabled( 'log' ) ) {

					$notice_key .= '-with-debug-log';

					$notice_msg .= __( 'WP debug logging mode is active - debug messages are being sent to the WordPress debug log.', 'wpsso' ) . ' ';
				}

				if ( $this->debug->is_enabled( 'html' ) ) {

					$notice_key .= '-with-html-comments';

					$notice_msg .= __( 'HTML debug mode is active - debug messages are being added to webpages as hidden HTML comments.', 'wpsso' ) . ' ';
				}

				/*
				 * WP debug logging and/or HTML debug mode is active.
				 */
				if ( $notice_msg ) {

					$notice_msg .= sprintf( __( 'The %s plugin\'s debug mode generates thousands of messages during page load, which affects website performance.', 'wpsso' ), $info[ 'name' ] ) . ' ';

					$notice_msg .= __( 'Don\'t forget to disable debug mode when debugging is complete.', 'wpsso' );

					$this->notice->warn( $notice_msg, null, $notice_key, $dismiss_time );
				}

				/*
				 * WPSSO_CACHE_DISABLE constant is true or the 'plugin_cache_disable' option is checked.
				 */
				if ( $this->util->cache->is_disabled() ) {

					$notice_msg = sprintf( __( 'The %s plugin\'s cache feature is disabled for debugging, which affects website performance.', 'wpsso' ), $info[ 'name' ] ) . ' ';

					$notice_msg .= __( 'Don\'t forget to re-enable caching when debugging is complete.', 'wpsso' );

					$notice_key = 'plugin-cache-is-disabled';

					$this->notice->warn( $notice_msg, null, $notice_key, $dismiss_time );
				}
			}
		}
	}

	global $wpsso;

	$wpsso =& Wpsso::get_instance();
}
