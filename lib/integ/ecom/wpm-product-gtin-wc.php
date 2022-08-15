<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegEcomWpmProductGtinWc' ) ) {

	class WpssoIntegEcomWpmProductGtinWc {

		private $p;	// Wpsso class object.

		private static $meta_name  = '_wpm_gtin_code';
		private static $prop_names = array(
			'gtin'   => 'product_gtin',
			'gtin8'  => 'product_gtin8',
			'gtin12' => 'product_gtin12',
			'gtin13' => 'product_gtin13',
			'gtin14' => 'product_gtin14',
			'isbn'   => 'product_isbn',
			'mpn'    => 'product_mfr_part_no',
		);

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->avail[ 'p_ext' ][ 'wcmd' ] ) ) {

				return;
			}

			$prop_name = get_option( 'wpm_pgw_structured_data_field', $default = 'gtin' );

			/**
			 * The $prop_name value may change, so remove and then re-add the custom field.
			 */
			foreach ( self::$prop_names as $name => $opt_suffix ) {

				if ( isset( $this->p->options[ 'plugin_cf_' . $opt_suffix ] ) &&	// Just in case.
					$this->p->options[ 'plugin_cf_' . $opt_suffix ] === self::$meta_name ) {

					$this->p->options[ 'plugin_cf_' . $opt_suffix ] = '';
				}

				$this->p->options[ 'plugin_attr_' . $opt_suffix ]               = '';
				$this->p->options[ 'plugin_attr_' . $opt_suffix . ':disabled' ] = true;
			}

			if ( isset( self::$prop_names[ $prop_name ] ) ) {

				$opt_suffix = self::$prop_names[ $prop_name ];

				$this->p->options[ 'plugin_cf_' . $opt_suffix ]               = self::$meta_name;
				$this->p->options[ 'plugin_cf_' . $opt_suffix . ':disabled' ] = true;
			}
		}
	}
}
