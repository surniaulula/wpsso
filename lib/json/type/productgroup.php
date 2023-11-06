<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeProductGroup' ) ) {

	class WpssoJsonTypeProductGroup {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_productgroup' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_productgroup( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			/*
			 * Adds the 'productGroupID', 'hasVariant', and 'variesBy' properties.
			 */
			WpssoSchemaSingle::add_product_group_data( $json_ret, $mod, $mt_og, $page_type_id, $list_element = false );

			/*
			 * Inherit required properties from the product group for Google.
			 */
			if ( ! empty( $json_ret[ 'hasVariant' ] ) ) {	// Just in case.

				$inherit_props = array_keys( $this->p->cf[ 'form' ][ 'inherit_variant_props' ] );

				foreach ( $inherit_props as $prop_name ) {

					if ( ! empty( $json_data[ $prop_name ] ) ) {

						foreach ( $json_ret[ 'hasVariant' ] as &$variant ) {

							if ( empty( $variant[ $prop_name ] ) ) {

								$variant[ $prop_name ] = $json_data[ $prop_name ];
							}
						}
					}
				}

				/*
				 * Remove the product group offers to avoid confusing Google merchant.
				 */
				unset( $json_data[ 'offers' ] );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
