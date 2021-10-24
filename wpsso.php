<?php
/**
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
 * Description: Present your content at its best in search results and on social sites - no matter how URLs are shared, reshared, messaged, posted, embedded, or crawled.
 * Requires PHP: 7.0
 * Requires At Least: 5.0
 * Tested Up To: 5.8.1
 * WC Tested Up To: 5.8.0
 * Version: 9.3.0
 *
 * Version Numbering: {major}.{minor}.{bugfix}[-{stage}.{level}]
 *
 *      {major}         Major structural code changes / re-writes or incompatible API changes.
 *      {minor}         New functionality was added or improved in a backwards-compatible manner.
 *      {bugfix}        Backwards-compatible bug fixes or small improvements.
 *      {stage}.{level} Pre-production release: dev < a (alpha) < b (beta) < rc (release candidate).
 *
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'Wpsso' ) ) {

	class Wpsso {

		/**
		 * Library class object variables.
		 */
		public $admin;		// WpssoAdmin (admin menus and settings page loader) class object.
		public $cache;		// SucomCache (object and file caching) class object.
		public $check;		// WpssoCheck class object.
		public $comment;	// WpssoComment class object (extends WpssoWpMeta).
		public $compat;		// WpssoCompat (third-party plugin and theme compatibility actions and filters) class object.
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
		public $post;		// WpssoPost class object (extends WpssoWpMeta).
		public $reg;		// WpssoRegister class object.
		public $script;		// WpssoScript (admin jquery tooltips) class object.
		public $style;		// WpssoStyle (admin styles) class object.
		public $term;		// WpssoTerm class object (extends WpssoWpMeta).
		public $user;		// WpssoUser class object (extends WpssoWpMeta).
		public $util;		// WpssoUtil (extends SucomUtil) class object.

		/**
		 * Library class object variables for meta tags and markup.
		 */
		public $link_rel;	// WpssoLinkRel class object.
		public $meta_name;	// WpssoMetaName class object.
		public $oembed;		// WpssoOembed class object.
		public $og;		// WpssoOpenGraph class object.
		public $pinterest;	// WpssoPinterest class object.
		public $schema;		// WpssoSchema class object.
		public $tc;		// WpssoTwitterCard class object.
		public $sitemaps;	// WpssoWpSitemaps class object.

		/**
		 * Reference variables (config, options, modules, etc.).
		 */
		public $lca          = 'wpsso';	// Plugin lowercase acronym (deprecated).
		public $id           = 'wpsso';	// Plugin ID (since WPSSO Core v8.14.0).
		public $m            = array();	// Loaded module objects from core plugin.
		public $m_ext        = array();	// Loaded module objects from extensions / add-ons.
		public $cf           = array();	// Config array defined in construct method.
		public $avail        = array();	// Assoc array for third-party plugin checks.
		public $options      = array();	// Individual blog/site options.
		public $site_options = array();	// Multisite options.
		public $sc           = array();	// Shortcodes.

		private static $instance = null;	// Wpsso class object.

		/**
		 * Wpsso constructor.
		 */
		public function __construct() {

			$plugin_dir = trailingslashit( dirname( __FILE__ ) );

			require_once $plugin_dir . 'lib/config.php';

			$this->cf = WpssoConfig::get_config();

			WpssoConfig::set_constants( __FILE__ );

			WpssoConfig::require_libs( __FILE__ );		// Includes the register.php class library.

			$this->reg = new WpssoRegister( $this );	// Activate, deactivate, uninstall hooks.

			/**
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
			add_action( 'init', array( $this, 'init_json_filters' ), WPSSO_INIT_JSON_FILTERS_PRIORITY );	// Runs at init 11.
			add_action( 'init', array( $this, 'init_shortcodes' ), WPSSO_INIT_SHORTCODES_PRIORITY );	// Runs at init 11.
			add_action( 'init', array( $this, 'init_plugin' ), WPSSO_INIT_PLUGIN_PRIORITY );		// Runs at init 12.

			/**
			 * The 'wpsso_init_textdomain' action is run after the $check, $avail, and $debug properties are defined.
			 */
			add_action( 'wpsso_init_textdomain', array( $this, 'init_textdomain' ), -1000, 0 );

			add_action( 'change_locale', array( $this, 'change_locale' ), -1000, 1 );
		}

		public static function &get_instance() {

			if ( null === self::$instance ) {

				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Force a refresh of the plugin config.
		 *
		 * Runs at init priority -10 and called by WpssoRegister->activate_plugin() as well.
		 */
		public function set_config( $activate = false ) {

			$this->cf = WpssoConfig::get_config( $read_cache = false );
		}

		/**
		 * Runs at init 1.
		 */
		public function register_widgets() {

			/**
			 * Load lib/widget/* library files.
			 */
			$classnames = $this->get_lib_classnames( 'widget' );	// Always returns an array.

			foreach ( $classnames as $id => $classname ) {

				register_widget( $classname );
			}
		}

		/**
		 * Runs at init priority 9. Called by WpssoRegister->activate_plugin() as well.
		 */
		public function set_options( $activate = false ) {

			if ( $activate && defined( 'WPSSO_RESET_ON_ACTIVATE' ) && WPSSO_RESET_ON_ACTIVATE ) {

				delete_option( WPSSO_OPTIONS_NAME );

				$this->options = false;

			} else {

				$this->options = get_option( WPSSO_OPTIONS_NAME );
			}

			if ( ! is_array( $this->options ) ) {

				/**
				 * The set_options() action is run before the set_objects() action, where the WpssoOptions class is
				 * instantiated, so the WpssoOptions->get_defaults() method is not available yet. Load the defaults
				 * directly from the config array.
				 */
				if ( isset( $this->cf[ 'opt' ][ 'defaults' ] ) ) {	// just in case.

					$this->options = $this->cf[ 'opt' ][ 'defaults' ];

				} else {

					$this->options = array();
				}

				$this->options[ '__reload_defaults' ] = true;
			}

			if ( is_multisite() ) {

				$this->site_options = get_site_option( WPSSO_SITE_OPTIONS_NAME );

				if ( ! is_array( $this->site_options ) ) {

					if ( isset( $this->cf[ 'opt' ][ 'site_defaults' ] ) ) {	// Just in case.

						$this->site_options = $this->cf[ 'opt' ][ 'site_defaults' ];

					} else {

						$this->site_options = array();
					}

					$this->site_options[ '__reload_defaults' ] = true;
				}

				/**
				 * If multisite options are found, check for overwrite of site specific options.
				 */
				if ( is_array( $this->options ) && is_array( $this->site_options ) ) {

					$blog_id = get_current_blog_id();

					$defined_constants = get_defined_constants( true );	// $categorize is true.

					foreach ( $this->site_options as $key => $val ) {

						if ( false !== strpos( $key, ':use' ) ) {

							continue;
						}

						if ( isset( $this->site_options[ $key . ':use' ] ) ) {

							switch ( $this->site_options[ $key . ':use' ] ) {

								case'always':
								case'force':

									$this->options[ $key ] = $this->site_options[ $key ];

									$this->options[ $key . ':is' ] = 'disabled';

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
		 * Runs at init priority 10. Called by WpssoRegister->activate_plugin() as well.
		 */
		public function set_objects( $activate = false ) {

			$is_admin   = is_admin() ? true : false;
			$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
			$doing_cron = defined( 'DOING_CRON' ) ? DOING_CRON : false;
			$debug_log  = false;
			$debug_html = false;

			/**
			 * Maybe log debug messages to the WordPress debug.log file.
			 */
			$debug_log = $this->get_const_status_bool( 'DEBUG_LOG' );

			/**
			 * Maybe log debug messages as HTML comments in the webpage.
			 */
			$debug_html = $this->get_const_status_bool( 'DEBUG_HTML' );

			if ( null === $debug_html ) {	// Constant not defined.

				$debug_html = empty( $this->options[ 'plugin_debug_html' ] ) ? false : true;
			}

			/**
			 * Setup core classes:
			 *
			 *	$check
			 *	$avail
			 *	$debug
			 *	$notice
			 *	$cache
			 *	$util
			 *	$opt
			 *	$script
			 *	$style
			 *	$compat
			 *	$msgs
			 *	$admin
			 */
			$this->check = new WpssoCheck( $this );

			$this->avail = $this->check->get_avail();	// Uses $this->options for availability checks.

			/**
			 * Make sure a debug object is always available.
			 */
			if ( $debug_log || $debug_html ) {

				require_once WPSSO_PLUGINDIR . 'lib/com/debug.php';

				$this->debug = new SucomDebug( $this, array(
					'log'  => $debug_log,
					'html' => $debug_html,
				) );

				if ( $this->debug->enabled ) {

					global $wp_version;

					$this->debug->log( 'debug enabled on ' . date( 'c' ) );
					$this->debug->log( 'PHP version ' . phpversion() );
					$this->debug->log( 'WP version ' . $wp_version );

					$this->debug->log_arr( 'generator list', $this->check->get_ext_gen_list() );
				}

			} else {

				$this->debug = new SucomNoDebug();
			}

			/**
			 * The 'wpsso_init_textdomain' action is run after the $check, $avail, and $debug properties are defined.
			 */
			do_action( 'wpsso_init_textdomain' );

			/**
			 * Make sure a notice object is always available.
			 */
			if ( $is_admin || $doing_cron ) {

				require_once WPSSO_PLUGINDIR . 'lib/com/notice.php';

				$this->notice = new SucomNotice( $this );

			} else {

				$this->notice = new SucomNoNotice();
			}

			$this->cache = new SucomCache( $this );
			$this->util  = new WpssoUtil( $this );		// Extends SucomUtil.
			$this->opt   = new WpssoOptions( $this );

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init options do action' );	// Begin timer.
			}

			do_action( 'wpsso_init_options' );

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init options do action' );	// End timer.
			}

			$this->compat = new WpssoCompat( $this );	// Actions and filters for compatibility.
			$this->script = new WpssoScript( $this );
			$this->style  = new WpssoStyle( $this );

			if ( $is_admin ) {

				require_once WPSSO_PLUGINDIR . 'lib/admin.php';
				require_once WPSSO_PLUGINDIR . 'lib/conflict.php';
				require_once WPSSO_PLUGINDIR . 'lib/edit.php';
				require_once WPSSO_PLUGINDIR . 'lib/messages.php';
				require_once WPSSO_PLUGINDIR . 'lib/com/form.php';

				$this->admin    = new WpssoAdmin( $this );	// Admin menus and settings page loader.
				$this->conflict = new WpssoConflict( $this );	// Admin plugin conflict checks.
				$this->edit     = new WpssoEdit( $this );	// Admin editing page metabox table rows.
				$this->msgs     = new WpssoMessages( $this );	// Admin tooltip messages.
			}

			/**
			 * Setup resource classes:
			 *
			 *	$comment
			 *	$media
			 *	$page
			 *	$post
			 *	$term
			 *	$user
			 */
			$this->comment = new WpssoComment( $this );		// Extends WpssoWpMeta.
			$this->media   = new WpssoMedia( $this );
			$this->page    = new WpssoPage( $this );
			$this->post    = new WpssoPost( $this );		// Extends WpssoWpMeta.
			$this->term    = new WpssoTerm( $this );		// Extends WpssoWpMeta.
			$this->user    = new WpssoUser( $this );		// Extends WpssoWpMeta.

			/**
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
			$this->schema    = new WpssoSchema( $this );		// Schema json scripts.
			$this->tc        = new WpssoTwitterCard( $this );	// Twitter Card meta tags.
			$this->sitemaps  = new WpssoWpSitemaps( $this );	// WordPress sitemaps.

			/**
			 * Load distribution modules.
			 */
			$this->loader = new WpssoLoader( $this );		// Module loader.

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init objects do action' );	// Begin timer.
			}

			/**
			 * Init additional class objects.
			 */
			do_action( 'wpsso_init_objects', $is_admin, $doing_ajax, $doing_cron );

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init objects do action' );	// End timer.
			}

			/**
			 * set_options() may have loaded the static defaults for new or missing options. After all objects have
			 * been loaded, and all filter / action hooks registered, check to see if the options need to be reloaded
			 * from the filtered defaults.
			 */
			if ( ! empty( $this->options[ '__reload_defaults' ] ) ) {

				$this->options = $this->opt->get_defaults();
			}

			if ( $this->debug->enabled ) {

				$this->debug->log( 'checking ' . WPSSO_OPTIONS_NAME . ' options' );
			}

			$this->options = $this->opt->check_options( WPSSO_OPTIONS_NAME, $this->options, $network = false, $activate );

			if ( $this->debug->enabled ) {

				$this->debug->log( 'options array len is ' . SucomUtil::serialized_len( $this->options ) . ' bytes' );
			}

			if ( is_multisite() ) {	// Load the site options array.

				if ( ! empty( $this->site_options[ '__reload_defaults' ] ) ) {

					$this->site_options = $this->opt->get_site_defaults();
				}

				if ( $this->debug->enabled ) {

					$this->debug->log( 'checking ' . WPSSO_SITE_OPTIONS_NAME . ' options' );
				}

				$this->site_options = $this->opt->check_options( WPSSO_SITE_OPTIONS_NAME, $this->site_options, $network = true, $activate );

				if ( $this->debug->enabled ) {

					$this->debug->log( 'site options array len is ' . SucomUtil::serialized_len( $this->options ) . ' bytes' );
				}
			}

			/**
			 * Init option checks.
			 */
			do_action( 'wpsso_init_check_options' );

			/**
			 * Show a reminder that debug mode is enabled if the WPSSO_DEV constant is not defined.
			 */
			if ( $this->debug->enabled ) {

				$info         = $this->cf[ 'plugin' ][ 'wpsso' ];
				$doing_dev    = SucomUtil::get_const( 'WPSSO_DEV' );
				$notice_key   = 'debug-mode-is-active';
				$notice_msg   = '';
				$dismiss_time = 12 * HOUR_IN_SECONDS;

				if ( $this->debug->is_enabled( 'log' ) ) {

					$this->debug->log( 'WP debug log mode is active' );

					if ( $is_admin ) {

						$notice_key .= '-with-debug-log';

						$notice_msg .= __( 'WP debug logging mode is active - debug messages are being sent to the WordPress debug log.', 'wpsso' ) . ' ';
					}
				}

				if ( $this->debug->is_enabled( 'html' ) ) {

					$this->debug->log( 'HTML debug mode is active' );

					if ( $is_admin ) {

						$notice_key .= '-with-html-comments';

						$notice_msg .= __( 'HTML debug mode is active - debug messages are being added to webpages as hidden HTML comments.', 'wpsso' ) . ' ';
					}
				}

				if ( ! $doing_dev && ! empty( $notice_msg ) ) {

					// translators: %s is the short plugin name.
					$notice_msg .= sprintf( __( 'Debug mode can generate thousands of runtime messages during page load, which may degrade website performance.', 'wpsso' ), $info[ 'short' ] ) . ' ';

					$notice_msg .= __( 'Don\'t forget to disable debug mode when debugging is complete.', 'wpsso' );

					$this->notice->warn( $notice_msg, null, $notice_key, $dismiss_time );
				}
			}

			if ( $this->debug->enabled ) {

				$this->debug->log( 'done setting objects' );
			}
		}

		/**
		 * Runs at init priority 11.
		 */
		public function init_json_filters() {

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init json filters' );	// Begin timer.
			}

			/**
			 * Load lib/json-filters/* library files.
			 */
			if ( $this->avail[ 'p' ][ 'schema' ] ) {

				$classnames = $this->get_lib_classnames( 'json-filters' );	// Always returns an array.

				foreach ( $classnames as $id => $classname ) {

					new $classname( $this );
				}

			} else {

				if ( $this->debug->enabled ) {

					$this->debug->log( 'schema markup is disabled' );
				}
			}

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init json filters' );	// End timer.
			}
		}

		/**
		 * Runs at init priority 11.
		 */
		public function init_shortcodes() {

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init shortcodes' );	// Begin timer.
			}

			/**
			 * Load lib/shortcode/* library files.
			 */
			$classnames = $this->get_lib_classnames( 'shortcode' );	// Always returns an array.

			foreach ( $classnames as $id => $classname ) {

				/**
				 * Note that the 'schema' shortcode object array element is used by the
				 * WpssoSscFilters->filter_json_data_graph_element() method.
				 */
				$this->sc[ $id ] = new $classname( $this );
			}

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init shortcodes' );	// End timer.
			}
		}

		/**
		 * Runs at init priority 12.
		 */
		public function init_plugin() {

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init plugin' );	// Begin timer.
			}

			$is_admin   = is_admin() ? true : false;	// Only check once.
			$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
			$doing_cron = defined( 'DOING_CRON' ) ? DOING_CRON : false;

			if ( $this->debug->enabled ) {

				$min_int = SucomUtil::get_min_int();
				$max_int = SucomUtil::get_max_int();

				/**
				 * PHP v5.3+ is required for "function() use () {}" syntax.
				 */
				$has_function_use = version_compare( phpversion(), '5.3.0', '>=' ) ? true : false;

				/**
				 * Show a comment marker at the top / bottom of the head and footer sections.
				 */
				foreach ( array( 'wp_head', 'wp_footer', 'admin_head', 'admin_footer' ) as $action ) {

					foreach ( array( $min_int, $max_int ) as $prio ) {

						if ( $has_function_use ) {

							$function = function() use ( $action, $prio ) {
								echo "\n" . '<!-- wpsso ' . $action . ' action hook priority ' .
									$prio . ' mark -->' . "\n\n";
							};

							add_action( $action, $function, $prio );
						}

						add_action( $action, array( $this, 'show_debug' ), $prio );
					}
				}

				/**
				 * Show a comment marker at the top / bottom of the content section.
				 */
				foreach ( array( 'the_content' ) as $filter ) {

					if ( $has_function_use ) {

						/**
						 * Prepend marker.
						 */
						$function = function( $str ) use ( $filter, $min_int ) {
							return "\n\n" . '<!-- wpsso ' . $filter . ' filter hook priority ' .
								$min_int . ' mark -->' . "\n\n" . $str;
						};

						add_filter( $filter, $function, $min_int );

						/**
						 * Append marker.
						 */
						$function = function( $str ) use ( $filter, $max_int ) {
							return $str . "\n\n" . '<!-- wpsso ' . $filter . ' filter hook priority ' .
								$max_int . ' mark -->' . "\n\n";
						};

						add_filter( $filter, $function, $max_int );
					}
				}

				/**
				 * Show the plugin settings just before the footer marker. 
				 */
				foreach ( array( 'wp_footer', 'admin_footer' ) as $action ) {

					add_action( $action, array( $this, 'show_config' ), $max_int );
				}
			}

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init plugin do action' );	// Begin timer.
			}

			/**
			 * All WPSSO Core objects are instantiated and configured.
			 *
			 * $is_admin and $doing_ajax added in WPSSO Core v7.10.0.
			 * $doing_cron added in WPSSO Core v8.8.0.
			 */
			do_action( 'wpsso_init_plugin', $is_admin, $doing_ajax, $doing_cron );

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init plugin do action' );	// End timer.
			}

			if ( $this->debug->enabled ) {

				$this->debug->mark( 'init plugin' );	// End timer.
			}
		}

		public function change_locale( $locale ) {

			do_action( 'wpsso_init_textdomain' );
		}

		/**
		 * Runs at wpsso_init_textdomain priority -1000.
		 *
		 * The 'wpsso_init_textdomain' action is run after the $check, $avail, and $debug properties are defined.
		 *
		 * May also be called via the 'change_locale' action.
		 */
		public function init_textdomain() {

			if ( ! empty( $this->options[ 'plugin_load_mofiles' ] ) ) {

				static $do_once = null;

				if ( null === $do_once ) {

					$do_once = true;

					add_filter( 'load_textdomain_mofile', array( $this, 'override_textdomain_mofile' ), 10, 3 );
				}
			}

			load_plugin_textdomain( 'wpsso', false, 'wpsso/languages/' );
		}

		public function get_const_status_bool( $const_suffix ) {

			return $this->get_const_status( $const_suffix, $transl = false );
		}

		public function get_const_status_transl( $const_suffix ) {

			return $this->get_const_status( $const_suffix, $transl = true );
		}

		private function get_const_status( $const_suffix, $transl ) {

			$const_name = '';

			if ( is_admin() && defined( 'WPSSO_ADMIN_' . $const_suffix ) ) {

				$const_name = 'WPSSO_ADMIN_' . $const_suffix;

			} elseif ( defined( 'WPSSO_' . $const_suffix ) ) {

				$const_name = 'WPSSO_' . $const_suffix;
			}

			if ( $const_name ) {

				$const_val = constant( $const_name ) ? true : false;

				if ( $transl ) {	// Return the translated string value.

					if ( $const_val ) {	// Constant value is true.

						return sprintf( _x( '%s constant is true', 'option comment', 'wpsso' ), $const_name );
					}

					return sprintf( _x( '%s constant is false', 'option comment', 'wpsso' ), $const_name );

				}

				return $const_val;	// Return the boolean value.
			}

			return null;	// Constant not defined.
		}

		public function get_lib_classnames( $type_dir ) {

			$is_admin = is_admin();

			$classnames = array();

			foreach ( $this->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! isset( $info[ 'lib' ][ $type_dir ] ) ) {

					continue;

				} elseif ( ! is_array( $info[ 'lib' ][ $type_dir ] ) ) {

					continue;
				}

				foreach ( $info[ 'lib' ][ $type_dir ] as $sub_dir => $libs ) {

					if ( is_array( $libs ) ) {

						/**
						 * Skip loading admin library modules if not in admin back-end.
						 */
						if ( 'admin' === $sub_dir && ! $is_admin ) {

							continue;
						}

						foreach ( $libs as $id => $label ) {

							$lib_path  = $type_dir . '/' . $sub_dir . '/' . $id;
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

		public function override_textdomain_mofile( $wp_mofile, $domain ) {

			if ( 0 === strpos( $domain, 'wpsso' ) ) {	// Optimize.

				foreach ( $this->cf[ 'plugin' ] as $ext => $info ) {

					if ( $info[ 'slug' ] === $domain ) {

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
			$active_plugins = SucomPlugin::get_active_plugins();

			$this->debug->show_html( print_r( $active_plugins, true ), 'active plugins' );

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
