<?php
/*
 * Plugin Name: WordPress Social Sharing Optimization (WPSSO)
 * Plugin Slug: wpsso
 * Text Domain: wpsso
 * Domain Path: /languages
 * Plugin URI: http://surniaulula.com/extend/plugins/wpsso/
 * Author: JS Morisset
 * Author URI: http://surniaulula.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: Fast, light-weight, full-featured plugin for great looking shares on all social sites - no matter how your content is shared or re-shared!
 * Requires At Least: 3.1
 * Tested Up To: 4.5.3
 * Version: 3.33.1-1
 * 
 * Version Numbers: {major}.{minor}.{bugfix}-{stage}{level}
 *
 *	{major}		Major code changes and/or significant feature changes.
 *	{minor}		New features added and/or improvements included.
 *	{bugfix}	Bugfixes and/or very minor improvements.
 *	{stage}{level}	dev# (development), rc# (release candidate), # (production release)
 * 
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
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
		public $debug;			// SucomDebug or WpssoNoDebug
		public $head;			// WpssoHead
		public $loader;			// WpssoLoader
		public $media;			// WpssoMedia (images, videos, etc.)
		public $msgs;			// WpssoMessages (admin tooltip messages)
		public $notice;			// SucomNotice or WpssoNoNotice
		public $og;			// WpssoOpengraph
		public $tc;			// WpssoTwittercard
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

		private static $instance = null;

		public static function &get_instance() {
			if ( self::$instance === null )
				self::$instance = new self;
			return self::$instance;
		}

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

		// runs at init priority -10
		public function set_config() {
			$this->cf = WpssoConfig::get_config( false, true );	// apply filters - define the $cf['*'] array
		}

		// runs at init priority 1
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

		// runs at init priority 12 (by default)
		public function init_plugin() {
			if ( ! empty( $_SERVER['WPSSO_DISABLE'] ) ) 
				return;

			$this->set_objects();				// define the class object variables

			if ( $this->debug->enabled ) {
				foreach ( array( 'wp_head', 'wp_footer', 'admin_head', 'admin_footer' ) as $action ) {
					foreach ( array( -9999, 9999 ) as $prio ) {
						add_action( $action, create_function( '', 'echo "<!-- wpsso '.
							$action.' action hook priority '.$prio.' mark -->\n";' ), $prio );
						add_action( $action, array( &$this, 'show_debug_html' ), $prio );
					}
				}
			}
			do_action( 'wpsso_init_plugin' );
		}

		public function show_debug_html() { 
			if ( $this->debug->enabled )
				$this->debug->show_html();
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

			// only load the debug class if one or more debug options are enabled
			if ( ( $html_debug || $wp_debug ) && 
				( $classname = WpssoConfig::load_lib( false, 'com/debug', 'SucomDebug' ) ) )
					$this->debug = new $classname( $this, array( 'html' => $html_debug, 'wp' => $wp_debug ) );
			else $this->debug = new WpssoNoDebug();

			if ( $activate === true &&
				$this->debug->enabled )
					$this->debug->log( 'method called for plugin activation' );

			// only load the notification class in the admin interface
			if ( is_admin() &&
				( $classname = WpssoConfig::load_lib( false, 'com/notice', 'SucomNotice' ) ) )
					$this->notice = new $classname( $this );
			else $this->notice = new WpssoNoNotice();

			$this->util = new WpssoUtil( $this );
			$this->opt = new WpssoOptions( $this );
			$this->cache = new SucomCache( $this );			// object and file caching
			$this->style = new SucomStyle( $this );			// admin styles
			$this->script = new SucomScript( $this );		// admin jquery tooltips
			$this->webpage = new SucomWebpage( $this );		// title, desc, etc., plus shortcodes
			$this->media = new WpssoMedia( $this );			// images, videos, etc.
			$this->head = new WpssoHead( $this );
			$this->og = new WpssoOpengraph( $this );
			$this->tc = new WpssoTwittercard( $this );
			$this->schema = new WpssoSchema( $this );

			if ( is_admin() ) {
				$this->msgs = new WpssoMessages( $this );	// admin tooltip messages
				$this->admin = new WpssoAdmin( $this );		// admin menus and page loader
			}

			$this->loader = new WpssoLoader( $this, $activate );	// module loader

			do_action( 'wpsso_init_objects', $activate );

			/*
			 * check and create the default options array
			 * execute after all objects have been defines, so hooks into 'wpsso_get_defaults' are available
			 */
			if ( is_multisite() && ( ! is_array( $this->site_options ) || empty( $this->site_options ) ) )
				$this->site_options = $this->opt->get_site_defaults();

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
			$this->options = $this->opt->check_options( WPSSO_OPTIONS_NAME, $this->options );

			if ( is_multisite() )
				$this->site_options = $this->opt->check_options( WPSSO_SITE_OPTIONS_NAME, 
					$this->site_options, true );

			/*
			 * configure class properties based on plugin settings
			 */
			$this->cache->default_object_expire = $this->options['plugin_object_cache_exp'];

			$this->cache->default_file_expire = ( $this->check->aop() ? 
				( $this->debug->is_enabled( 'wp' ) ? 
					WPSSO_DEBUG_FILE_EXP : 
					$this->options['plugin_file_cache_exp'] ) : 0 );

			$this->is_avail['cache']['file'] = $this->cache->default_file_expire > 0 ? true : false;

			// disable the transient cache if html debug mode is on
			if ( $this->debug->is_enabled( 'html' ) === true ) {

				$this->is_avail['cache']['transient'] = defined( 'WPSSO_TRANSIENT_CACHE_DISABLE' ) && 
					! WPSSO_TRANSIENT_CACHE_DISABLE ? true : false;

				if ( $this->debug->enabled )
					$this->debug->log( 'html debug mode is active: transient cache use '.
						( $this->is_avail['cache']['transient'] ? 'could not be' : 'is' ).' disabled' );

				if ( is_admin() )
					// text_domain is already loaded by the NgfbAdmin class construct
					$this->notice->inf( ( $this->is_avail['cache']['transient'] ?
						__( 'HTML debug mode is active (transient cache could NOT be disabled).', 'nextgen-facebook' ) :
						__( 'HTML debug mode is active (transient cache use is disabled).', 'nextgen-facebook' ) ).' '.
						__( 'Informational debug messages are being added as hidden HTML comments.', 'wpsso' ) );
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
					$current_blog_id = function_exists( 'get_current_blog_id' ) ? 
						get_current_blog_id() : false;
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
						// check for constant over-rides
						if ( $current_blog_id !== false ) {
							$constant_name = 'WPSSO_OPTIONS_'.$current_blog_id.'_'.strtoupper( $key );
							if ( defined( $constant_name ) )
								$this->options[$key] = constant( $constant_name );
						}
					}
				}
			}
		}
	}

        global $wpsso;
	$wpsso =& Wpsso::get_instance();
}

if ( ! class_exists( 'WpssoNoDebug' ) ) {
	class WpssoNoDebug {
		public $enabled = false;
		public function mark() { return; }
		public function args() { return; }
		public function log() { return; }
		public function show_html() { return; }
		public function get_html() { return; }
		public function is_enabled() { return false; }
	}
}

if ( ! class_exists( 'WpssoNoNotice' ) ) {
	class WpssoNoNotice {
		public function nag() { return; }
		public function inf() { return; }
		public function err() { return; }
		public function log() { return; }
		public function trunc() { return; }
	}
}

?>
