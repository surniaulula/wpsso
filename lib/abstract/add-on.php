<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2014-2023 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomAbstractAddOn' ) ) {

	require_once dirname( __FILE__ ) . '/com/add-on.php';	// SucomAbstractAddOn class.
}

if ( ! class_exists( 'WpssoAbstractAddOn' ) ) {

	abstract class WpssoAbstractAddOn extends SucomAbstractAddOn {

		protected $p;			// Wpsso class object.
		protected $ext   = '';		// Add-on lowercase classname, for example: 'wpssoum'.
		protected $p_ext = '';		// Add-on lowercase acronym, for example: 'um'.
		protected $cf    = array();	// Add-on config array, for example: WpssoUmConfig::$cf.

		public $reg;	// Add-on register class object, for example: WpssoUmRegister.

		public function __construct( $plugin_file, $classname ) {

			$plugin_dir     = trailingslashit( dirname( $plugin_file ) );
			$config_class   = $classname . 'Config';
			$register_class = $classname . 'Register';
			$this->ext      = strtolower( $classname );
			$this->p_ext    = str_replace( 'wpsso', '', $this->ext );

			require_once $plugin_dir . '/lib/config.php';

			$config_class::set_constants( $plugin_file );
			$config_class::require_libs( $plugin_file );	// Includes the register.php class library.

			$this->cf =& $config_class::$cf;

			$this->reg = new $register_class();	// Activate, deactivate, uninstall hooks.

			$this->add_hooks( $plugin_file );
		}

		protected function add_hooks( $plugin_file ) {

			/*
			 * WPSSO filter hooks.
			 */
			add_filter( 'wpsso_get_config', array( $this, 'get_config' ), 10, 1 );

			add_filter( 'wpsso_get_avail', array( $this, 'get_avail' ), 10, 1 );

			/*
			 * WPSSO action hooks.
			 */
			foreach ( array(
				'init_textdomain'    => 0,
				'init_objects'       => 0,
				'init_objects_std'   => 0,
				'init_objects_pro'   => 0,
				'init_check_options' => 0,
			) as $method => $args_num ) {

				if ( method_exists( $this, $method ) ) {

					add_action( 'wpsso_' . $method, array( $this, $method ), 10, $args_num );
				}
			}

			/*
			 * The SucomAbstractAddOn->init_plugin_notices() method adds toolbar notices for any missing requirements.
			 */
			add_action( 'wpsso_init_plugin', array( $this, 'init_plugin_notices' ), 10, 0 );

			/*
			 * If SucomAbstractAddOn->init_plugin_notices() is not executed, then show any missing requirements using
			 * the standard WordPress admin notices action.
			 */
			add_action( 'all_admin_notices', array( $this, 'show_admin_notices' ), 10, 0 );

			/*
			 * Declare compatibility with WooCommerce HPOS.
			 *
			 * See https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book.
			 */
			add_action( 'before_woocommerce_init', function () use ( $plugin_file ) { 

				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {

					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $plugin_file, true );
				}
			}, 10, 0 );
		}
	}
}
