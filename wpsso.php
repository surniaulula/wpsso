<?php
/*
 * Plugin Name: WordPress Social Sharing Optimization (WPSSO)
 * Plugin Slug: wpsso
 * Text Domain: wpsso
 * Domain Path: /languages
 * Plugin URI: https://surniaulula.com/extend/plugins/wpsso/
 * Assets URI: https://surniaulula.github.io/wpsso/assets/
 * Author: JS Morisset
 * Author URI: https://surniaulula.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Description: Automatically create complete and accurate meta tags and Schema markup for Social Sharing Optimization (SSO) and SEO.
 * Requires At Least: 3.7
 * Tested Up To: 4.7.1
 * Version: 3.39.1-1
 * 
 * Version Numbering Scheme: {major}.{minor}.{bugfix}-{stage}{level}
 *
 *	{major}		Major code changes / re-writes or significant feature changes.
 *	{minor}		New features / options were added or improved.
 *	{bugfix}	Bugfixes or minor improvements.
 *	{stage}{level}	dev < a (alpha) < b (beta) < rc (release candidate) < # (production).
 *
 * See PHP's version_compare() documentation at http://php.net/manual/en/function.version-compare.php.
 * 
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'Wpsso' ) ) {

	class Wpsso {
		/*
		 * Class Object Variables
		 */
		public $p;			// Wpsso
		public $admin;			// WpssoAdmin (admin menus and page loader)
		public $cache;			// SucomCache (object and file caching)
		public $check;			// WpssoCheck
		public $debug;			// SucomDebug or SucomNoDebug
		public $head;			// WpssoHead
		public $loader;			// WpssoLoader
		public $media;			// WpssoMedia (images, videos, etc.)
		public $msgs;			// WpssoMessages (admin tooltip messages)
		public $notice;			// SucomNotice or SucomNoNotice
		public $og;			// WpssoOpenGraph
		public $weibo;			// WpssoWeibo
		public $tc;			// WpssoTwitterCard
		public $opt;			// WpssoOptions
		public $reg;			// WpssoRegister
		public $script;			// SucomScript (admin jquery tooltips)
		public $style;			// SucomStyle (admin styles)
		public $util;			// WpssoUtil (extends SucomUtil)
		public $webpage;		// SucomWebpage (title, desc, etc., plus shortcodes)

		/*
		 * Reference Variables (config, options, modules, etc.)
		 */
		public $m = array();		// plugin modules
		public $m_ext = array();	// plugin extension modules
		public $cf = array();		// config array defined in construct method
		public $is_avail = array();	// assoc array for other plugin checks
		public $options = array();	// individual blog/site options
		public $site_options = array();	// multisite options

		private static $instance;

		/*
		 * Wpsso Constructor
		 */
		public function __construct() {

			require_once( dirname( __FILE__ ).'/lib/config.php' );
			$this->cf = WpssoConfig::get_config();			// unfiltered - $cf['*'] array is not available yet
			WpssoConfig::set_constants( __FILE__ );
			WpssoConfig::require_libs( __FILE__ );			// includes the register.php class library
			$this->reg = new WpssoRegister( $this );		// activate, deactivate, uninstall hooks

			add_action( 'init', array( &$this, 'set_config' ), -10 );
			add_action( 'init', array( &$this, 'init_plugin' ), WPSSO_INIT_PRIORITY );
			add_action( 'widgets_init', array( &$this, 'init_widgets' ), 10 );
		}

		public static function &get_instance() {
			if ( ! isset( self::$instance ) )
				self::$instance = new self;
			return self::$instance;
		}

		// runs at init priority -10
		public function set_config() {
			$this->cf = WpssoConfig::get_config( false, true );	// apply filters - define the $cf['*'] array
		}

		// runs at init priority 12 (by default)
		public function init_plugin() {

			$this->set_objects();				// define the class object variables

			if ( $this->debug->enabled )
				$this->debug->mark( 'plugin initialization' );

			if ( $this->debug->enabled ) {
				foreach ( array( 'wp_head', 'wp_footer', 'admin_head', 'admin_footer' ) as $action ) {
					foreach ( array( -9999, 9999 ) as $prio ) {
						add_action( $action, create_function( '', 'echo "<!-- wpsso '.
							$action.' action hook priority '.$prio.' mark -->\n";' ), $prio );
						add_action( $action, array( &$this, 'show_debug_html' ), $prio );
					}
				}
			}

			if ( $this->debug->enabled )
				$this->debug->log( 'running init_plugin action' );
			do_action( 'wpsso_init_plugin' );

			if ( $this->debug->enabled )
				$this->debug->mark( 'plugin initialization' );
		}

		public function show_debug_html() { 
			if ( $this->debug->enabled )
				$this->debug->show_html();
		}

		public function init_widgets() {
			$opts = get_option( WPSSO_OPTIONS_NAME );
			if ( ! empty( $opts['plugin_widgets'] ) ) {
				foreach ( $this->cf['plugin'] as $lca => $info ) {
					if ( isset( $info['lib']['widget'] ) && is_array( $info['lib']['widget'] ) ) {
						foreach ( $info['lib']['widget'] as $id => $name ) {
							$classname = apply_filters( $lca.'_load_lib', false, 'widget/'.$id );
							if ( $classname !== false && class_exists( $classname ) )
								register_widget( $classname );
						}
					}
				}
			}
		}

		// called by activate_plugin() as well
		public function set_objects( $activate = false ) {
			/*
			 * basic plugin setup (settings, check, debug, notices, utils)
			 */
			$this->set_options();	// filter and define the $this->options and $this->site_options properties
			$this->check = new WpssoCheck( $this );
			$this->is_avail = $this->check->get_avail();		// uses $this->options in checks

			// configure the debug class
			$html_debug = ! empty( $this->options['plugin_debug'] ) || 
				( defined( 'WPSSO_HTML_DEBUG' ) && WPSSO_HTML_DEBUG ) ? true : false;
			$wp_debug = defined( 'WPSSO_WP_DEBUG' ) && WPSSO_WP_DEBUG ? true : false;

			if ( ( $html_debug || $wp_debug ) &&			// only load debug class if one or more debug options enabled
				( $classname = WpssoConfig::load_lib( false, 'com/debug', 'SucomDebug' ) ) ) {
				$this->debug = new $classname( $this, array( 'html' => $html_debug, 'wp' => $wp_debug ) );
				if ( $this->debug->enabled ) {
					$this->debug->log( 'debug enabled on '.date( 'c' ) );
					$this->debug->log( $this->check->get_ext_list() );
				}
			} else $this->debug = new SucomNoDebug();			// make sure debug property is always available

			if ( $activate === true && $this->debug->enabled )
				$this->debug->log( 'method called for plugin activation' );

			if ( is_admin() && 					// only load notice class in the admin interface
				( $classname = WpssoConfig::load_lib( false, 'com/notice', 'SucomNotice' ) ) )
					$this->notice = new $classname( $this );
			else $this->notice = new SucomNoNotice();		// make sure notice property is always available

			$this->util = new WpssoUtil( $this );			// extends SucomUtil
			$this->opt = new WpssoOptions( $this );
			$this->cache = new SucomCache( $this );			// object and file caching
			$this->style = new SucomStyle( $this );			// admin styles
			$this->script = new SucomScript( $this );		// admin jquery tooltips
			$this->webpage = new SucomWebpage( $this );		// title, desc, etc., plus shortcodes
			$this->media = new WpssoMedia( $this );			// images, videos, etc.
			$this->filters = new WpssoFilters( $this );		// integration filters

			$this->head = new WpssoHead( $this );
			$this->og = new WpssoOpenGraph( $this );
			$this->weibo = new WpssoWeibo( $this );
			$this->tc = new WpssoTwitterCard( $this );
			$this->schema = new WpssoSchema( $this );

			if ( is_admin() ) {
				$this->msgs = new WpssoMessages( $this );	// admin tooltip messages
				$this->admin = new WpssoAdmin( $this );		// admin menus and page loader
			}

			$this->loader = new WpssoLoader( $this, $activate );	// module loader

			if ( $this->debug->enabled )
				$this->debug->mark( 'init objects action' );
			do_action( 'wpsso_init_objects', $activate );
			if ( $this->debug->enabled )
				$this->debug->mark( 'init objects action' );

			/*
			 * check and create the default options array
			 * execute after all objects have been defines, so hooks into 'wpsso_get_defaults' are available
			 */
			if ( is_multisite() && ( ! is_array( $this->site_options ) || empty( $this->site_options ) ) ) {
				if ( $this->debug->enabled )
					$this->debug->log( 'setting site_options to site_defaults' );
				$this->site_options = $this->opt->get_site_defaults();
			}

			/*
			 * end here when called for plugin activation (the init_plugin() hook handles the rest)
			 */
			if ( $activate == true || ( 
				! empty( $_GET['action'] ) && $_GET['action'] == 'activate-plugin' &&
				! empty( $_GET['plugin'] ) && $_GET['plugin'] == WPSSO_PLUGINBASE ) ) {
				if ( $this->debug->enabled )
					$this->debug->log( 'exiting early: init_plugin hook will follow' );
				return;
			}

			/*
			 * check and upgrade options if necessary
			 */
			if ( $this->debug->enabled )
				$this->debug->log( 'checking options' );
			$this->options = $this->opt->check_options( WPSSO_OPTIONS_NAME, $this->options );

			if ( is_multisite() ) {
				if ( $this->debug->enabled )
					$this->debug->log( 'checking site_options' );
				$this->site_options = $this->opt->check_options( WPSSO_SITE_OPTIONS_NAME, $this->site_options, true );
			}

			if ( $this->debug->enabled ) {
				if ( $this->debug->is_enabled( 'wp' ) ) {
					$this->debug->log( 'WP debug log mode is active' );
					$this->notice->warn( __( 'WP debug log mode is active &mdash; debug messages are being sent to the WordPress debug log.', 'wpsso' ) );
				} elseif ( $this->debug->is_enabled( 'html' ) ) {
					$this->debug->log( 'HTML debug mode is active' );
					$this->notice->warn( __( 'HTML debug mode is active &mdash; debug messages are being added to webpages as hidden HTML comments.', 'wpsso' ) );
				}
				$this->util->add_plugin_filters( $this, array( 
					'cache_expire_head_array' => '__return_zero',
					'cache_expire_setup_html' => '__return_zero',
				) );
			}
		}

		public function set_options() {
			$this->options = get_option( WPSSO_OPTIONS_NAME );

			// look for alternate options name
			if ( ! is_array( $this->options ) ) {
				if ( defined( 'WPSSO_OPTIONS_NAME_ALT' ) && WPSSO_OPTIONS_NAME_ALT ) {
					$this->options = get_option( WPSSO_OPTIONS_NAME_ALT );
					if ( is_array( $this->options ) ) {
						// auto-creates options with autoload = yes
						update_option( WPSSO_OPTIONS_NAME, $this->options );
						delete_option( WPSSO_OPTIONS_NAME_ALT );
					}
				}
			}

			if ( ! is_array( $this->options ) )
				$this->options = array();

			unset( $this->options['options_filtered'] );	// just in case

			$this->options = apply_filters( 'wpsso_get_options', $this->options );

			if ( is_multisite() ) {
				$this->site_options = get_site_option( WPSSO_SITE_OPTIONS_NAME );

				// look for alternate site options name
				if ( ! is_array( $this->site_options ) ) {
					if ( defined( 'WPSSO_SITE_OPTIONS_NAME_ALT' ) && WPSSO_SITE_OPTIONS_NAME_ALT ) {
						$this->site_options = get_site_option( WPSSO_SITE_OPTIONS_NAME_ALT );
						if ( is_array( $this->site_options ) ) {
							update_site_option( WPSSO_SITE_OPTIONS_NAME, $this->site_options );
							delete_site_option( WPSSO_SITE_OPTIONS_NAME_ALT );
						}
					}
				}

				if ( ! is_array( $this->site_options ) )
					$this->site_options = array();

				unset( $this->site_options['options_filtered'] );	// just in case

				$this->site_options = apply_filters( 'wpsso_get_site_options', $this->site_options );

				// if multisite options are found, check for overwrite of site specific options
				if ( is_array( $this->options ) && is_array( $this->site_options ) ) {
					$blog_id = get_current_blog_id();	// since wp 3.1
					$defined_constants = get_defined_constants( true );	// $categorize = true
					foreach ( $this->site_options as $key => $val ) {
						if ( strpos( $key, ':use' ) !== false )
							continue;
						if ( isset( $this->site_options[$key.':use'] ) ) {
							switch ( $this->site_options[$key.':use'] ) {
								case'force':
									$this->options[$key.':is'] = 'disabled';
									$this->options[$key] = $this->site_options[$key];
									break;
								case 'empty':
									if ( empty( $this->options[$key] ) )
										$this->options[$key] = $this->site_options[$key];
									break;
							}
						}
						$constant_name = 'WPSSO_ID_'.$blog_id.'_OPT_'.strtoupper( $key );
						if ( isset( $defined_constants['user'][$constant_name] ) )
							$this->options[$key] = $defined_constants['user'][$constant_name];
					}
				}
			}
		}
	}

        global $wpsso;
	$wpsso =& Wpsso::get_instance();
}

?>
