<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomAddOn' ) ) {

	require_once dirname( __FILE__ ) . '/com/add-on.php';	// SucomAddOn class.
}

if ( ! class_exists( 'WpssoAddOn' ) ) {

	abstract class WpssoAddOn extends SucomAddOn {

		public $reg;

		protected $ext   = '';
		protected $p_ext = '';
		protected $cf    = array();

		public function __construct( $plugin_file, $classname ) {

			require_once dirname( $plugin_file ) . '/lib/config.php';

			$this->ext = strtolower( $classname );

			$this->p_ext = str_replace( 'wpsso', '', $this->ext );

			/**
			 * Note that dynamic class names are available since PHP v5.3.
			 *
			 * See https://www.php.net/manual/en/language.namespaces.dynamic.php.
			 */
			$config_class = $classname . 'Config';

			$register_class = $classname . 'Register';

			$config_class::set_constants( $plugin_file );

			$config_class::require_libs( $plugin_file );	// Includes the register.php class library.

			$this->cf =& $config_class::$cf;

			$this->reg = new $register_class();		// Activate, deactivate, uninstall hooks.

			$this->add_hooks();
		}

		protected function add_hooks( $prio = 10 ) {

			/**
			 * WPSSO filter hooks.
			 */
			add_filter( 'wpsso_get_config', array( $this, 'get_config' ), $prio, 1 );

			add_filter( 'wpsso_get_avail', array( $this, 'get_avail' ), $prio, 1 );

			/**
			 * WPSSO action hooks.
			 */
			if ( method_exists( $this, 'init_textdomain' ) ) {
			
				add_action( 'wpsso_init_textdomain', array( $this, 'init_textdomain' ), $prio, 0 );
			}

			if ( method_exists( $this, 'init_objects' ) ) {
			
				add_action( 'wpsso_init_objects', array( $this, 'init_objects' ), $prio, 1 );
			}

			if ( method_exists( $this, 'init_check_options' ) ) {	// May exist only in some add-ons.

				add_action( 'wpsso_init_check_options', array( $this, 'init_check_options' ), $prio, 0 );
			}

			/**
			 * The SucomAddon->init_plugin_notices() method adds toolbar notices for any missing requirements.
			 */
			add_action( 'wpsso_init_plugin', array( $this, 'init_plugin_notices' ), $prio, 2 );

			/**
			 * If SucomAddon->init_plugin_notices() is not executed, then show any missing requirements using the
			 * standard WordPress admin notices action.
			 */
			add_action( 'all_admin_notices', array( $this, 'show_admin_notices' ), $prio, 0 );
		}
	}
}
