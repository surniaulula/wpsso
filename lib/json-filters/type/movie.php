<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypeMovie' ) ) {

	class WpssoJsonFiltersTypeMovie {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_movie' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_movie( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				/**
				 * Merge defaults to get a complete meta options array.
				 */
				$md_opts = SucomUtil::get_opts_begin( 'schema_movie_', array_merge( 
					(array) $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] ),
					(array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] )	// Returns empty string if no meta found.
				) );

			} else {
				$md_opts = array();
			}

			/**
			 * Property:
			 * 	duration
			 */
			WpssoSchema::add_data_time_from_assoc( $json_ret, $md_opts, array(
				'duration' => 'schema_movie_duration',	// Option prefix for days, hours, mins, secs.
			) );

			/**
			 * Property:
			 * 	actor (supersedes actors)
			 */
			WpssoSchema::add_person_names_data( $json_ret, 'actor', $md_opts, 'schema_movie_actor_person_name' );

			/**
			 * Property:
			 * 	director
			 */
			WpssoSchema::add_person_names_data( $json_ret, 'director', $md_opts, 'schema_movie_director_person_name' );

			/**
			 * Property:
			 * 	productionCompany
			 */
			if ( WpssoSchema::is_valid_key( $md_opts, 'schema_movie_prodco_org_id' ) ) {	// Not null, an empty string, or 'none'.

				$md_val = $md_opts[ 'schema_movie_prodco_org_id' ];

				WpssoSchemaSingle::add_organization_data( $json_ret[ 'productionCompany' ], $mod, $md_val, 'org_logo_url', $list_element = true );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
