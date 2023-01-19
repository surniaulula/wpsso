<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2022 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomAbstractAddOn' ) ) {

	require_once dirname( __FILE__ ) . '/com/add-on.php';	// SucomAbstractAddOn class.
}

if ( ! class_exists( 'WpssoAbstractAddOn' ) ) {

	abstract class WpssoAbstractAddOn extends SucomAbstractAddOn {

		protected $p;	// Wpsso class object.

		protected $ext   = '';		// Add-on lowercase classname, for example: 'wpssoum'.
		protected $p_ext = '';		// Add-on lowercase acronym, for example: 'um'.
		protected $cf    = array();	// Add-on config array, for example: WpssoUmConfig::$cf.

		public $reg;	// Add-on register class object, for example: WpssoUmRegister.

		public function __construct( $plugin_file, $classname ) {

			require_once dirname( $plugin_file ) . '/lib/config.php';

			$this->ext = strtolower( $classname );

			$this->p_ext = str_replace( 'wpsso', '', $this->ext );

			/*
			 * Note that dynamic class names are available since PHP v5.3.
			 *
			 * See https://www.php.net/manual/en/language.namespaces.dynamic.php.
			 */
			$config_class = $classname . 'Config';

			$register_class = $classname . 'Register';

			$config_class::set_constants( $plugin_file );

			$config_class::require_libs( $plugin_file );	// Includes the register.php class library.

			$this->cf =& $config_class::$cf;

			$this->reg = new $register_class();	// Activate, deactivate, uninstall hooks.

			$this->add_hooks();
		}

		protected function add_hooks( $prio = 10 ) {

			/*
			 * WPSSO filter hooks.
			 */
			add_filter( 'wpsso_get_config', array( $this, 'get_config' ), $prio, 1 );

			add_filter( 'wpsso_get_avail', array( $this, 'get_avail' ), $prio, 1 );

			/*
			 * WPSSO action hooks.
			 */
			foreach ( array(
				'init_textdomain'    => 0,
				'init_objects'       => 0,
				'init_objects_std'   => 0,
				'init_objects_pro'   => 0,
				'init_check_options' => 0,
			) as $method => $args ) {

				if ( method_exists( $this, $method ) ) {

					add_action( 'wpsso_' . $method, array( $this, $method ), $prio, $args );
				}
			}

			/*
			 * The SucomAbstractAddOn->init_plugin_notices() method adds toolbar notices for any missing requirements.
			 */
			add_action( 'wpsso_init_plugin', array( $this, 'init_plugin_notices' ), $prio, 0 );

			/*
			 * If SucomAbstractAddOn->init_plugin_notices() is not executed, then show any missing requirements using
			 * the standard WordPress admin notices action.
			 */
			add_action( 'all_admin_notices', array( $this, 'show_admin_notices' ), $prio, 0 );
		}
	}
}
