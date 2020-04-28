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
 * Description: Make sure your content looks great on all social and search sites - no matter how your URLs are crawled, shared, re-shared, posted or embedded.
 * Requires PHP: 5.6
 * Requires At Least: 4.2
 * Tested Up To: 5.4
 * WC Tested Up To: 4.0.1
 * Version: 7.2.0
 *
 * Version Numbering: {major}.{minor}.{bugfix}[-{stage}.{level}]
 *
 *      {major}         Major structural code changes / re-writes or incompatible API changes.
 *      {minor}         New functionality was added or improved in a backwards-compatible manner.
 *      {bugfix}        Backwards-compatible bug fixes or small improvements.
 *      {stage}.{level} Pre-production release: dev < a (alpha) < b (beta) < rc (release candidate).
 *
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'Wpsso' ) ) {

	class Wpsso {

		/**
		 * Wpsso plugin class object variable.
		 */
		public $p;		// Wpsso.

		/**
		 * Library class object variables.
		 */
		public $admin;		// WpssoAdmin (admin menus and settings page loader).
		public $cache;		// SucomCache (object and file caching).
		public $check;		// WpssoCheck.
		public $conflict;	// WpssoConflict (admin plugin conflict checks).
		public $debug;		// SucomDebug or SucomNoDebug.
		public $head;		// WpssoHead.
		public $loader;		// WpssoLoader.
		public $media;		// WpssoMedia (images, videos, etc.).
		public $msgs;		// WpssoMessages (admin tooltip messages).
		public $notice;		// SucomNotice or SucomNoNotice.
		public $opt;		// WpssoOptions.
		public $page;		// WpssoPage (page title, desc, etc.).
		public $post;		// WpssoPost.
		public $reg;		// WpssoRegister.
		public $script;		// WpssoScript (admin jquery tooltips).
		public $style;		// WpssoStyle (admin styles).
		public $term;		// WpssoTerm.
		public $user;		// WpssoUser.
		public $util;		// WpssoUtil (extends SucomUtil).

		/**
		 * Library class object variables for meta tags and markup.
		 */
		public $link_rel;	// WpssoLinkRel.
		public $meta_item;	// WpssoMetaItem.
		public $meta_name;	// WpssoMetaName.
		public $oembed;		// WpssoOembed.
		public $og;		// WpssoOpenGraph.
		public $pinterest;	// WpssoPinterest.
		public $schema;		// WpssoSchema.
		public $tc;		// WpssoTwitterCard.

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

			$this->cf = WpssoConfig::get_config( $apply_filters = false );

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
			add_action( 'init', array( $this, 'init_hooks' ), WPSSO_INIT_HOOKS_PRIORITY );			// Runs at init 11.
			add_action( 'init', array( $this, 'init_shortcodes' ), WPSSO_INIT_SHORTCODES_PRIORITY );	// Runs at init 11.
			add_action( 'init', array( $this, 'init_plugin' ), WPSSO_INIT_PLUGIN_PRIORITY );		// Runs at init 12.

			/**
			 * The 'wpsso_init_textdomain' action is run after the $check, $avail, and $debug properties are defined.
			 *
			 * Hooks the 'override_textdomain_mofile' filter (if debug is enabled) to use the local translation files
			 * instead of those from wordpress.org.
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
		 * Runs at init priority -10 and called by activate_plugin() as well.
		 */
		public function set_config( $activate = false ) {

			$this->cf = WpssoConfig::get_config( $apply_filters = true );
		}

		/**
		 * Runs at init 1.
		 */
		public function register_widgets() {

			$classnames = $this->get_lib_classnames( 'widget' );	// Always returns an array.

			foreach ( $classnames as $id => $classname ) {
				register_widget( $classname );
			}
		}

		/**
		 * Runs at init priority 9. Called by activate_plugin() as well.
		 */
		public function set_options( $activate = false ) {

			if ( $activate && defined( 'WPSSO_RESET_ON_ACTIVATE' ) && WPSSO_RESET_ON_ACTIVATE ) {

				delete_option( WPSSO_OPTIONS_NAME );

				$this->options = false;

			} else {
				$this->options = get_option( WPSSO_OPTIONS_NAME );
			}

			if ( ! is_array( $this->options ) ) {

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
		 * Runs at init priority 10. Called by activate_plugin() as well.
		 */
		public function set_objects( $activate = false ) {

			$is_admin   = is_admin() ? true : false;	// Only check once.
			$network    = is_multisite() ? true : false;
			$doing_cron = defined( 'DOING_CRON' ) ? DOING_CRON : false;
			$debug_log  = false;
			$debug_html = false;

			if ( defined( 'WPSSO_DEBUG_LOG' ) && WPSSO_DEBUG_LOG ) {
				$debug_log = true;
			} elseif ( $is_admin && defined( 'WPSSO_ADMIN_DEBUG_LOG' ) && WPSSO_ADMIN_DEBUG_LOG ) {
				$debug_log = true;
			}

			if ( ! empty( $this->options[ 'plugin_debug' ] ) ) {
				$debug_html = true;
			} elseif ( defined( 'WPSSO_DEBUG_HTML' ) && WPSSO_DEBUG_HTML ) {
				$debug_html = true;
			} elseif ( $is_admin && defined( 'WPSSO_ADMIN_DEBUG_HTML' ) && WPSSO_ADMIN_DEBUG_HTML ) {
				$debug_html = true;
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
			 *	$filters
			 *	$msgs
			 *	$admin
			 */
			$this->check = new WpssoCheck( $this );

			$this->avail = $this->check->get_avail();		// Uses $this->options for availability checks.

			/**
			 * Make sure a debug object is always available.
			 */
			if ( $debug_log || $debug_html || ( defined( 'WPSSO_LOAD_DEBUG' ) && WPSSO_LOAD_DEBUG ) ) {

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
			do_action( 'wpsso_init_textdomain', $this->debug->enabled );

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

			do_action( 'wpsso_init_options', $activate );

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init options do action' );	// End timer.
			}

			$this->filters = new WpssoFilters( $this );
			$this->script  = new WpssoScript( $this );
			$this->style   = new WpssoStyle( $this );

			if ( $is_admin ) {

				require_once WPSSO_PLUGINDIR . 'lib/admin.php';
				require_once WPSSO_PLUGINDIR . 'lib/conflict.php';
				require_once WPSSO_PLUGINDIR . 'lib/messages.php';
				require_once WPSSO_PLUGINDIR . 'lib/com/form.php';
				require_once WPSSO_PLUGINDIR . 'lib/ext/parse-readme.php';

				$this->admin    = new WpssoAdmin( $this );	// Admin menus and settings page loader.
				$this->conflict = new WpssoConflict( $this );	// Admin plugin conflict checks.
				$this->msgs     = new WpssoMessages( $this );	// Admin tooltip messages.
			}

			/**
			 * Setup resource classes:
			 *
			 *	$media
			 *	$page
			 *	$post
			 *	$term
			 *	$user
			 */
			$this->media = new WpssoMedia( $this );
			$this->page  = new WpssoPage( $this );
			$this->post  = new WpssoPost( $this );			// Extends WpssoWpMeta.
			$this->term  = new WpssoTerm( $this );			// Extends WpssoWpMeta.
			$this->user  = new WpssoUser( $this );			// Extends WpssoWpMeta.

			/**
			 * Deprecated on 2019/05/06.
			 *
			 * Maintain backwards compatibility for older add-ons.
			 */
			$this->m[ 'util' ][ 'post' ] =& $this->post;
			$this->m[ 'util' ][ 'term' ] =& $this->term;
			$this->m[ 'util' ][ 'user' ] =& $this->user;

			/**
			 * Setup classe for meta tags and Schema markup:
			 *
			 *	$head
			 *	$link_rel
			 *	$meta_item
			 *	$meta_name
			 *	$og
			 *	$pinterest
			 *	$schema
			 *	$tc
			 *	$loader
			 */
			$this->head      = new WpssoHead( $this );
			$this->link_rel  = new WpssoLinkRel( $this );		// Link relation tags.
			$this->meta_item = new WpssoMetaItem( $this );		// Meta itemprop tags.
			$this->meta_name = new WpssoMetaName( $this );		// Meta name tags.
			$this->oembed    = new WpssoOembed( $this );		// Oembed response data.
			$this->og        = new WpssoOpenGraph( $this );		// Open Graph meta tags.
			$this->pinterest = new WpssoPinterest( $this );		// Pinterest image markup.
			$this->schema    = new WpssoSchema( $this );		// Schema json scripts.
			$this->tc        = new WpssoTwitterCard( $this );	// Twitter Card meta tags.

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
			do_action( 'wpsso_init_objects', $activate );

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

			$this->options = $this->opt->check_options( WPSSO_OPTIONS_NAME, $this->options, $network, $activate );

			if ( $this->debug->enabled ) {
				$this->debug->log( 'options array len is ' . SucomUtil::serialized_len( $this->options ) . ' bytes' );
			}

			if ( $network ) {

				if ( ! empty( $this->site_options[ '__reload_defaults' ] ) ) {

					$this->site_options = $this->opt->get_site_defaults();
				}

				$this->site_options = $this->opt->check_options( WPSSO_SITE_OPTIONS_NAME, $this->site_options, $network, $activate );

				if ( $this->debug->enabled ) {
					$this->debug->log( 'site options array len is ' . SucomUtil::serialized_len( $this->options ) . ' bytes' );
				}
			}

			/**
			 * Init option checks.
			 */
			do_action( 'wpsso_init_check_options' );

			/**
			 * Issue reminder notices and disable some caching when the plugin's debug mode is enabled.
			 */
			if ( $this->debug->enabled ) {

				$notice_key   = 'debug-mode-is-active';
				$notice_msg   = '';
				$dismiss_time = 12 * HOUR_IN_SECONDS;

				$info = $this->cf[ 'plugin' ][ 'wpsso' ];

				if ( $this->debug->is_enabled( 'log' ) ) {

					$this->debug->log( 'WP debug log mode is active' );

					if ( $is_admin ) {

						$notice_key .= '-with-debug-log';
						$notice_msg .= __( 'WP debug logging mode is active &mdash; debug messages are being sent to the WordPress debug log.',
							'wpsso' ) . ' ';
					}
				}

				if ( $this->debug->is_enabled( 'html' ) ) {

					$this->debug->log( 'HTML debug mode is active' );

					if ( $is_admin ) {

						$notice_key .= '-with-html-comments';
						$notice_msg .= __( 'HTML debug mode is active &mdash; debug messages are being added to webpages as hidden HTML comments.',
							'wpsso' ) . ' ';
					}
				}

				if ( $this->debug->enabled ) {

					if ( ! empty( $notice_msg ) ) {

						// translators: %s is the short plugin name.
						$notice_msg .= sprintf( __( 'Debug mode disables some %s caching features, which degrades performance slightly.',
							'wpsso' ), $info[ 'short' ] ) . ' ' . __( 'Please disable debug mode when debugging is complete.', 'wpsso' );

						$this->notice->warn( $notice_msg, null, $notice_key, $dismiss_time );
					}

					$this->util->disable_cache_filters();
				}
			}

			if ( $this->debug->enabled ) {
				$this->debug->log( 'done setting objects' );
			}
		}

		/**
		 * Runs at init priority 11.
		 */
		public function init_hooks() {

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init hooks' );	// Begin timer.
			}

			foreach ( array( 'filters' ) as $type_dir ) {

				$classnames = $this->get_lib_classnames( $type_dir );	// Always returns an array.

				foreach ( $classnames as $id => $classname ) {
					new $classname( $this );	// Variable assignment is not required.
				}
			}

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init hooks' );	// End timer.
			}
		}

		/**
		 * Runs at init priority 11.
		 */
		public function init_shortcodes() {

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init shortcodes' );	// Begin timer.
			}

			$classnames = $this->get_lib_classnames( 'shortcode' );	// Always returns an array.

			foreach ( $classnames as $id => $classname ) {
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
			 * All WPSSO objects are instantiated and configured.
			 */
			do_action( 'wpsso_init_plugin' );

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init plugin do action' );	// End timer.
			}

			if ( $this->debug->enabled ) {
				$this->debug->mark( 'init plugin' );	// End timer.
			}
		}

		/**
		 * Runs at wpsso_init_textdomain priority -10.
		 *
		 * The 'wpsso_init_textdomain' action is run after the $check, $avail, and $debug properties are defined.
		 *
		 * Hooks the 'override_textdomain_mofile' filter (if debug is enabled) to use the local translation files
		 * instead of those from wordpress.org.
		 */
		public static function init_textdomain( $debug_enabled = false ) {

			static $loaded = null;

			if ( null !== $loaded ) {
				return;
			}

			$loaded = true;

			if ( $debug_enabled ) {
				add_filter( 'load_textdomain_mofile', array( self::get_instance(), 'override_textdomain_mofile' ), 10, 3 );
			}

			load_plugin_textdomain( 'wpsso', false, 'wpsso/languages/' );
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

		/**
		 * Only runs when debug is enabled.
		 */
		public function override_textdomain_mofile( $wp_mofile, $domain ) {

			if ( 0 === strpos( $domain, 'wpsso' ) ) {	// Optimize.

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
