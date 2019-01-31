<?php
/**
 * Plugin Name: WPSSO Core [Main Plugin]
 * Plugin Slug: wpsso
 * Text Domain: wpsso
 * Domain Path: /languages
 * Plugin URI: https://wpsso.com/extend/plugins/wpsso/
 * Assets URI: https://surniaulula.github.io/wpsso/assets/
 * Author: JS Morisset
 * Author URI: https://wpsso.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Description: WPSSO Core makes sure your content looks great on all social and search sites - no matter how it's crawled, shared, re-shared, posted, or embedded!
 * Tagline: Makes sure your content looks great on all social and search sites - no matter how it's crawled, shared, re-shared, posted, or embedded!
 * Requires PHP: 5.5
 * Requires At Least: 3.8
 * Tested Up To: 5.0
 * WC Tested Up To: 3.5
 * Version: 4.22.0
 *
 * Version Numbering: {major}.{minor}.{bugfix}[-{stage}.{level}]
 *
 *      {major}         Major structural code changes / re-writes or incompatible API changes.
 *      {minor}         New functionality was added or improved in a backwards-compatible manner.
 *      {bugfix}        Backwards-compatible bug fixes or small improvements.
 *      {stage}.{level} Pre-production release: dev < a (alpha) < b (beta) < rc (release candidate).
 *
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'Wpsso' ) ) {

	class Wpsso {

		/**
		 * Wpsso plugin class object variable.
		 */
		public $p;		// Wpsso

		/**
		 * Library class object variables.
		 */
		public $admin;		// WpssoAdmin (admin menus and page loader)
		public $cache;		// SucomCache (object and file caching)
		public $check;		// WpssoCheck
		public $debug;		// SucomDebug or SucomNoDebug
		public $head;		// WpssoHead
		public $loader;		// WpssoLoader
		public $media;		// WpssoMedia (images, videos, etc.)
		public $msgs;		// WpssoMessages (admin tooltip messages)
		public $notice;		// SucomNotice or SucomNoNotice
		public $opt;		// WpssoOptions
		public $page;		// WpssoPage (page title, desc, etc.)
		public $reg;		// WpssoRegister
		public $script;		// WpssoScript (admin jquery tooltips)
		public $style;		// WpssoStyle (admin styles)
		public $util;		// WpssoUtil (extends SucomUtil)

		/**
		 * Library class object variables for meta tags and markup.
		 */
		public $link_rel;	// WpssoLinkRel
		public $meta_name;	// WpssoMetaName
		public $og;		// WpssoOpenGraph
		public $schema;		// WpssoSchema
		public $tc;		// WpssoTwitterCard
		public $weibo;		// WpssoWeibo

		/**
		 * Reference variables (config, options, modules, etc.).
		 */
		public $lca          = 'wpsso';	// Main plugin lowercase acronym.
		public $m            = array();	// Loaded module objects from core plugin.
		public $m_ext        = array();	// Loaded module objects from extensions / add-ons.
		public $cf           = array();	// Config array defined in construct method.
		public $avail        = array();	// Assoc array for other plugin checks.
		public $options      = array();	// Individual blog/site options.
		public $site_options = array();	// Multisite options.
		public $sc           = array();	// Shortcodes.

		private static $instance;

		/**
		 * Wpsso constructor.
		 */
		public function __construct() {

			$plugin_dir = trailingslashit( dirname( __FILE__ ) );

			require_once $plugin_dir . 'lib/config.php';

			$this->cf = WpssoConfig::get_config( $opt_key = false, $apply_filters = false );

			WpssoConfig::set_constants( __FILE__ );
			WpssoConfig::require_libs( __FILE__ );			// Includes the register.php class library.

			$this->reg = new WpssoRegister( $this );		// Activate, deactivate, uninstall hooks.

			add_action( 'init', array( $this, 'set_config' ), -10 );				// Runs at init -10 (before widgets_init).
			add_action( 'widgets_init', array( $this, 'init_widgets' ), 10 );			// Runs at init 1.
			add_action( 'init', array( $this, 'set_options' ), WPSSO_INIT_PRIORITY - 3 );		// Runs at init 9 by default.
			add_action( 'init', array( $this, 'set_objects' ), WPSSO_INIT_PRIORITY - 2 );		// Runs at init 10 by default.
			add_action( 'init', array( $this, 'init_shortcodes' ), WPSSO_INIT_PRIORITY - 1 );	// Runs at init 11 by default.
			add_action( 'init', array( $this, 'init_plugin' ), WPSSO_INIT_PRIORITY );		// Runs at init 12 by default.

			/**
			 * The 'wpsso_init_textdomain' action is run after the debug property is defined.
			 * Hooks the 'override_textdomain_mofile' filter (if debug is enabled) to use
			 * local translation files instead of those from wordpress.org.
			 */
			add_action( 'wpsso_init_textdomain', array( __CLASS__, 'init_textdomain' ), -10, 1 );
		}

		public static function &get_instance() {

			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Runs at init priority -10. Called by activate_plugin() as well.
		 */
		public function set_config( $activate = false ) {

			$this->cf = WpssoConfig::get_config( $opt_key = false, $apply_filters = true );
		}

		/**
		 * Runs at init 1.
		 */
		public function init_widgets() {

			foreach ( $this->cf[ 'plugin' ] as $ext => $info ) {

				if ( isset( $info[ 'lib' ][ 'widget' ] ) && is_array( $info[ 'lib' ][ 'widget' ] ) ) {

					foreach ( $info[ 'lib' ][ 'widget' ] as $id => $name ) {

						$classname = apply_filters( $ext . '_load_lib', false, 'widget/' . $id );

						if ( false !== $classname && class_exists( $classname ) ) {
							register_widget( $classname );	// name of a class that extends WP_Widget
						}
					}
				}
			}
		}

		/**
		 * Runs at init priority 9 by default. Called by activate_plugin() as well.
		 */
		public function set_options( $activate = false ) {

			if ( $activate && defined( 'WPSSO_RESET_ON_ACTIVATE' ) && WPSSO_RESET_ON_ACTIVATE ) {

				delete_option( WPSSO_OPTIONS_NAME );

				$this->options = false;

				error_log( 'WPSSO_RESET_ON_ACTIVATE constant is true - reloading default settings for plugin activation' );

			} else {

				$this->options = get_option( WPSSO_OPTIONS_NAME );

				/**
				 * Look for alternate options name.
				 */
				if ( ! is_array( $this->options ) ) {

					if ( defined( 'WPSSO_OPTIONS_NAME_ALT' ) && WPSSO_OPTIONS_NAME_ALT ) {

						$this->options = get_option( WPSSO_OPTIONS_NAME_ALT );

						if ( is_array( $this->options ) ) {

							update_option( WPSSO_OPTIONS_NAME, $this->options );	// Auto-creates with autoload yes.
							delete_option( WPSSO_OPTIONS_NAME_ALT );

							$this->options[ 'options_from_name_alt' ] = WPSSO_OPTIONS_NAME_ALT;
						}
					}
				}
			}

			if ( ! is_array( $this->options ) ) {

				if ( isset( $this->cf[ 'opt' ][ 'defaults' ] ) ) {	// just in case.
					$this->options = $this->cf[ 'opt' ][ 'defaults' ];
				} else {
					$this->options = array();
				}

				$this->options[ 'options_from_defaults' ] = true;
			}

			if ( is_multisite() ) {

				$this->site_options = get_site_option( WPSSO_SITE_OPTIONS_NAME );

				/**
				 * Look for alternate site options name.
				 */
				if ( ! is_array( $this->site_options ) ) {

					if ( defined( 'WPSSO_SITE_OPTIONS_NAME_ALT' ) && WPSSO_SITE_OPTIONS_NAME_ALT ) {

						$this->site_options = get_site_option( WPSSO_SITE_OPTIONS_NAME_ALT );

						if ( is_array( $this->site_options ) ) {

							update_site_option( WPSSO_SITE_OPTIONS_NAME, $this->site_options );
							delete_site_option( WPSSO_SITE_OPTIONS_NAME_ALT );

							$this->site_options[ 'options_from_name_alt' ] = WPSSO_SITE_OPTIONS_NAME_ALT;
						}
					}
				}

				if ( ! is_array( $this->site_options ) ) {

					if ( isset( $this->cf[ 'opt' ][ 'site_defaults' ] ) ) {	// Just in case.
						$this->site_options = $this->cf[ 'opt' ][ 'site_defaults' ];
					} else {
						$this->site_options = array();
					}

					$this->site_options[ 'options_from_defaults' ] = true;
				}

				/**
				 * If multisite options are found, check for overwrite of site specific options.
				 */
				if ( is_array( $this->options ) && is_array( $this->site_options ) ) {

					$blog_id = get_current_blog_id();	// Since wp 3.1.

					$defined_constants = get_defined_constants( true );	// $categorize is true.

					foreach ( $this->site_options as $key => $val ) {

						if ( false !== strpos( $key, ':use' ) ) {
							continue;
						}

						if ( isset( $this->site_options[ $key . ':use' ] ) ) {

							switch ( $this->site_options[ $key . ':use' ] ) {

								case'force':

									$this->options[ $key . ':is' ] = 'disabled';

									$this->options[ $key ] = $this->site_options[ $key ];

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
						}
					}
				}
			}
		}

		/**
		 * Runs at init priority 10 by default. Called by activate_plugin() as well.
		 */
		public function set_objects( $activate = false ) {

			$is_admin   = is_admin() ? true : false;
			$network    = is_multisite() ? true : false;
			$doing_cron = defined( 'DOING_CRON' ) ? DOING_CRON : false;

			$this->check = new WpssoCheck( $this );
			$this->avail = $this->check->get_avail();	// Uses $this->options for availability checks.

			/**
			 * Configure the debug class.
			 */
			if ( defined( 'WPSSO_DEBUG_LOG' ) && WPSSO_DEBUG_LOG ) {
				$debug_log = true;
			} elseif ( $is_admin && defined( 'WPSSO_ADMIN_DEBUG_LOG' ) && WPSSO_ADMIN_DEBUG_LOG ) {
				$debug_log = true;
			} else {
				$debug_log = false;
			}

			if ( ! empty( $this->options[ 'plugin_debug' ] ) ) {
				$debug_html = true;
			} elseif ( defined( 'WPSSO_DEBUG_HTML' ) && WPSSO_DEBUG_HTML ) {
				$debug_html = true;
			} elseif ( $is_admin && defined( 'WPSSO_ADMIN_DEBUG_HTML' ) && WPSSO_ADMIN_DEBUG_HTML ) {
				$debug_html = true;
			} else {
				$debug_html = false;
			}

			if ( $debug_html || $debug_log ) {

				require_once WPSSO_PLUGINDIR . 'lib/com/debug.php';

				$this->debug = new SucomDebug( $this, array( 'html' => $debug_html, 'log' => $debug_log ) );

				if ( $this->debug->enabled ) {

					global $wp_version;

					$this->debug->log( 'debug enabled on ' . date( 'c' ) );
					$this->debug->log( 'PHP version ' . phpversion() );
					$this->debug->log( 'WP version ' . $wp_version );
					$this->debug->log( $this->check->get_ext_list() );
				}

			} else {
				$this->debug = new SucomNoDebug();	// make sure debug property is always available
			}

			do_action( 'wpsso_init_textdomain', $this->debug->enabled );

			if ( $is_admin || $doing_cron ) {

				require_once WPSSO_PLUGINDIR . 'lib/com/notice.php';

				$this->notice = new SucomNotice( $this );
			} else {
				$this->notice = new SucomNoNotice();		// Make sure the notice property is always available.
			}

			$this->util    = new WpssoUtil( $this );		// Extends SucomUtil.
			$this->opt     = new WpssoOptions( $this );
			$this->cache   = new SucomCache( $this );		// Object and file caching.
			$this->style   = new WpssoStyle( $this );		// Admin styles.
			$this->script  = new WpssoScript( $this );		// Admin jquery tooltips.
			$this->page    = new WpssoPage( $this );		// Webpage title, desc, etc.
			$this->media   = new WpssoMedia( $this );		// Images, videos, etc.
			$this->filters = new WpssoFilters( $this );		// Integration filters
			$this->head    = new WpssoHead( $this );

			/**
			 * Meta tags and json-ld markup.
			 */
			$this->link_rel  = new WpssoLinkRel( $this );		// Link relation tags.
			$this->meta_name = new WpssoMetaName( $this );		// Meta name tags.
			$this->og        = new WpssoOpenGraph( $this );		// Open Graph meta tags.
			$this->weibo     = new WpssoWeibo( $this );		// Weibo meta tags.
			$this->tc        = new WpssoTwitterCard( $this );	// Twitter Card meta tags.
			$this->schema    = new WpssoSchema( $this );		// Schema meta tags and json markup.

			if ( $is_admin ) {
				$this->msgs  = new WpssoMessages( $this );	// admin tooltip messages
				$this->admin = new WpssoAdmin( $this );		// admin menus and page loader
			}

			$this->loader = new WpssoLoader( $this );		// module loader

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'do init objects action' );	// Begin timer.
			}

			do_action( 'wpsso_init_objects', $activate );

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'do init objects action' );	// End timer.
			}

			/**
			 * set_options() may have loaded the static defaults for new or missing options.
			 * After all objects have been loaded, and all filter / action hooks registered,
			 * check to see if the options need to be reloaded from the filtered defaults.
			 */
			if ( ! empty( $this->options[ 'options_from_name_alt' ] ) ) {

				$this->notice->upd( sprintf( __( 'Database option table row "%1$s" for plugin settings was found and converted to "%2$s".',
					'wpsso' ), $this->options[ 'options_from_name_alt' ], WPSSO_OPTIONS_NAME ) );

				unset( $this->options[ 'options_from_name_alt' ] );

			} elseif ( ! empty( $this->options[ 'options_from_defaults' ] ) ) {

				$this->options = $this->opt->get_defaults();
			}

			$this->options = $this->opt->check_options( WPSSO_OPTIONS_NAME, $this->options, $network, $activate );

			if ( $this->debug->enabled ) {
				$this->debug->log( 'options array len is ' . SucomUtil::serialized_len( $this->options ) . ' bytes' );
			}

			if ( $network ) {

				if ( ! empty( $this->options[ 'options_from_name_alt' ] ) ) {

					$this->notice->upd( sprintf( __( 'Database site option table row "%1$s" for plugin settings was found and converted to "%2$s".',
						'wpsso' ), $this->site_options[ 'options_from_name_alt' ], WPSSO_SITE_OPTIONS_NAME ) );

					unset( $this->options[ 'options_from_name_alt' ] );

				} elseif ( ! empty( $this->site_options[ 'options_from_defaults' ] ) ) {

					$this->site_options = $this->opt->get_site_defaults();
				}

				$this->site_options = $this->opt->check_options( WPSSO_SITE_OPTIONS_NAME, $this->site_options, $network, $activate );

				if ( $this->debug->enabled ) {
					$this->debug->log( 'site options array len is ' . SucomUtil::serialized_len( $this->options ) . ' bytes' );
				}
			}

			/**
			 * Issue reminder notices and disable some caching when the plugin's debug mode is enabled.
			 */
			if ( $this->debug->enabled ) {

				$warn_msg     = '';
				$info         = $this->cf[ 'plugin' ][ 'wpsso' ];
				$notice_key   = 'debug-mode-is-active';
				$dismiss_time = HOUR_IN_SECONDS * 3;

				if ( $this->debug->is_enabled( 'log' ) ) {

					$this->debug->log( 'WP debug log mode is active' );

					if ( $is_admin ) {
						$notice_key .= '-with-debug-log';
						$warn_msg   .= __( 'WP debug logging mode is active &mdash; debug messages are being sent to the WordPress debug log.',
							'wpsso' ) . ' ';
					}
				}

				if ( $this->debug->is_enabled( 'html' ) ) {

					if ( SucomUtil::get_crawler_name() !== 'none' ) {
						$this->debug->enable( 'html', false );	// disable HTML debug messages for crawlers
					}
				}

				if ( $this->debug->is_enabled( 'html' ) ) {

					$this->debug->log( 'HTML debug mode is active' );

					if ( $is_admin ) {
						$notice_key .= '-with-html-comments';
						$warn_msg   .= __( 'HTML debug mode is active &mdash; debug messages are being added to webpages as hidden HTML comments.',
							'wpsso' ) . ' ';
					}
				}

				if ( $this->debug->enabled ) {

					if ( ! empty( $warn_msg ) ) {

						// translators: %s is the short plugin name
						$warn_msg .= sprintf( __( 'Debug mode disables some %s caching features, which degrades performance slightly.',
							'wpsso' ), $info[ 'short' ] ) . ' ' . __( 'Please disable debug mode when debugging is complete.', 'wpsso' );

						$this->notice->warn( $warn_msg, null, $notice_key, $dismiss_time );
					}

					$this->util->disable_cache_filters();
				}
			}

			if ( $this->debug->enabled ) {
				$this->debug->log( 'done setting objects' );
			}
		}

		/**
		 * Runs at init priority 11 by default.
		 */
		public function init_shortcodes() {

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init shortcodes' );	// Begin timer.
			}

			foreach ( $this->cf[ 'plugin' ] as $ext => $info ) {

				if ( isset( $info[ 'lib' ][ 'shortcode' ] ) && is_array( $info[ 'lib' ][ 'shortcode' ] ) ) {

					foreach ( $info[ 'lib' ][ 'shortcode' ] as $id => $name ) {

						$classname = apply_filters( $ext . '_load_lib', false, 'shortcode/' . $id );

						if ( false !== $classname && class_exists( $classname ) ) {
							$this->sc[ $id ] = new $classname( $this );
						}
					}
				}
			}

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init shortcodes' );	// End timer.
			}
		}

		/**
		 * Runs at init priority 12 by default.
		 */
		public function init_plugin() {

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init plugin' );	// Begin timer.
			}

			if ( $this->debug->enabled ) {

				$min_int = SucomUtil::get_min_int();
				$max_int = SucomUtil::get_max_int();

				/**
				 * Show a comment marker at the top / bottom of the head and footer sections.
				 */
				foreach ( array( 'wp_head', 'wp_footer', 'admin_head', 'admin_footer' ) as $action ) {

					/**
					 * PHP v5.3+ is required for "function() use () {}" syntax.
					 */
					if ( version_compare( phpversion(), '5.3.0', '<' ) ) {
						break;
					}

					foreach ( array( $min_int, $max_int ) as $prio ) {

						$show_action_prio_func = function() use ( $action, $prio ) {
							echo "\n" . '<!-- wpsso ' . $action . ' action hook priority ' . $prio . ' mark -->' . "\n\n";
						};

						add_action( $action, $show_action_prio_func, $prio );
						add_action( $action, array( $this, 'show_debug' ), $prio + 1 );
					}
				}

				/**
				 * Show the plugin settings at the end, just before the footer marker. 
				 */
				foreach ( array( 'wp_footer', 'admin_footer' ) as $action ) {
					foreach ( array( $max_int - 1 ) as $prio ) {
						add_action( $action, array( $this, 'show_config' ), $prio );
					}
				}
			}

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'do init plugin action' );	// Begin timer.
			}

			do_action( 'wpsso_init_plugin' );

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'do init plugin action' );	// End timer.
			}

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init plugin' );	// End timer.
			}
		}

		/**
		 * Runs at wpsso_init_textdomain priority -10.
		 */
		public static function init_textdomain( $debug_enabled ) {

			if ( $debug_enabled ) {
				add_filter( 'load_textdomain_mofile', array( self::get_instance(), 'override_textdomain_mofile' ), 10, 3 );
			}

			load_plugin_textdomain( 'wpsso', false, 'wpsso/languages/' );
		}

		/**
		 * Only runs when debug is enabled.
		 */
		public function override_textdomain_mofile( $wp_mofile, $domain ) {

			if ( strpos( $domain, 'wpsso' ) === 0 ) {	// Optimize.

				foreach ( $this->cf[ 'plugin' ] as $ext => $info ) {

					if ( $info[ 'slug' ] === $domain ) {

						$constant_name = strtoupper( $ext ) . '_PLUGINDIR';

						if ( defined( $constant_name ) && $plugin_dir = constant( $constant_name ) ) {

							$plugin_mofile = $plugin_dir . 'languages/' . basename( $wp_mofile );

							if ( $plugin_mofile !== $wp_mofile && is_readable( $plugin_mofile ) ) {

								global $l10n;

								unset( $l10n[ $domain ] );	// Prevent merging.

								return $plugin_mofile;
							}
						}

						break;	// Stop here.
					}
				}
			}

			return $wp_mofile;
		}

		/**
		 * Only runs when debug is enabled.
		 */
		public function show_debug() {
			$this->debug->show_html( null, 'debug log' );
		}

		/**
		 * Only runs when debug is enabled.
		 */
		public function show_config() {

			if ( ! $this->debug->enabled ) {	// Just in case.
				return;
			}

			/**
			 * Show constants.
			 */
			$defined_constants = get_defined_constants( true );
			$defined_constants[ 'user' ][ 'WPSSO_NONCE_NAME' ] = '********';

			if ( is_multisite() ) {
				$this->debug->show_html( SucomUtil::preg_grep_keys( '/^(MULTISITE|^SUBDOMAIN_INSTALL|.*_SITE)$/',
					$defined_constants[ 'user' ] ), 'multisite constants' );
			}
			$this->debug->show_html( SucomUtil::preg_grep_keys( '/^WPSSO_/', $defined_constants[ 'user' ] ), 'wpsso constants' );

			/**
			 * Show active plugins.
			 */
			$this->debug->show_html( print_r( SucomPlugin::get_active_plugins( $use_cache = true ), true ), 'active plugins' );

			/**
			 * Show available modules.
			 */
			$this->debug->show_html( print_r( $this->avail, true ), 'available features' );

			/**
			 * Show all plugin options.
			 */
			$opts = $this->options;

			foreach ( $opts as $key => $val ) {

				switch ( $key ) {

					case ( false !== strpos( $key, '_js_' ) ? true : false ):
					case ( false !== strpos( $key, '_css_' ) ? true : false ):
					case ( preg_match( '/(_css|_js|_html)$/', $key ) ? true : false ):
					case ( preg_match( '/_(key|secret|tid|token)$/', $key ) ? true : false ):

						$opts[ $key ] = '[removed]';

						break;
				}
			}

			$this->debug->show_html( $opts, 'wpsso settings' );
		}
	}

	global $wpsso;

	$wpsso =& Wpsso::get_instance();
}
