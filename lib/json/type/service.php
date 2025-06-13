<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeService' ) ) {

	class WpssoJsonTypeService {

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
				'json_data_https_schema_org_service' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_service( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();
			$md_opts  = array();

			WpssoSchema::add_type_opts_md_pad( $md_opts, $mod );

			/*
			 * See https://schema.org/provider.
			 */
			if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

				/*
				 * The meta data key is unique, but the Schema property name may be repeated to add more than one
				 * value to a property array.
				 */
				foreach ( array(
					'schema_serv_prov_org_id'    => 'provider',	// Provider Org.
					'schema_serv_prov_person_id' => 'provider',	// Provider Person.
				) as $md_key => $prop_name ) {

					$md_val = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $md_key, $filter_opts = true, $merge_defs = true );

					if ( WpssoSchema::is_valid_val( $md_val ) ) {	// Not null, an empty string, or 'none'.

						if ( strpos( $md_key, '_org_id' ) ) {

							$org_logo_key = 'org_logo_url';

							WpssoSchemaSingle::add_organization_data( $json_ret[ $prop_name ], $mod, $md_val, $org_logo_key, $list_el = true );

						} elseif ( strpos( $md_key, '_person_id' ) ) {

							WpssoSchemaSingle::add_person_data( $json_ret[ $prop_name ], $mod, $md_val, $list_el = true );
						}
					}
				}
			}

			/*
			 * See https://schema.org/areaServed as https://schema.org/GeoShape.
			 */
			if ( ! empty( $md_opts[ 'schema_serv_latitude' ] ) &&
				! empty( $md_opts[ 'schema_serv_longitude' ] ) &&
					! empty( $md_opts[ 'schema_serv_radius' ] ) ) {

				$json_ret[ 'areaServed' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/GeoShape', array(
					'circle' => $md_opts[ 'schema_serv_latitude' ] . ' ' .
						$md_opts[ 'schema_serv_longitude' ] . ' ' .
						$md_opts[ 'schema_serv_radius' ]
				) );
			}

			/*
			 * See https://schema.org/hasOfferCatalog.
			 */
			WpssoSchema::add_offer_catalogs_data( $json_ret, $mod, $md_opts, $opt_pre = 'schema_serv_offer_catalog', $prop_name = 'hasOfferCatalog' );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
