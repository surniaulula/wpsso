<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeMovie' ) ) {

	class WpssoJsonTypeMovie {

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

			/**
			 * Maybe remove values related to the WordPress post object.
			 */
			unset( $json_data[ 'author' ] );
			unset( $json_data[ 'contributor' ] );
			unset( $json_data[ 'dateCreated' ] );
			unset( $json_data[ 'datePublished' ] );
			unset( $json_data[ 'dateModified' ] );

			$json_ret = array();
			$md_opts  = array();

			SucomUtil::add_type_opts_md_pad( $md_opts, $mod );

			/**
			 * Movie Release Date, Time, Timezone.
			 *
			 * Add the movie released (aka created) date, if one is available.
			 */
			if ( $date = WpssoSchema::get_opts_date_iso( $md_opts, 'schema_movie_released' ) ) {

				$json_ret[ 'dateCreated' ] = $date;
			}

			/**
			 * See https://schema.org/duration.
			 */
			WpssoSchema::add_data_time_from_assoc( $json_ret, $md_opts, array(
				'duration' => 'schema_movie_duration',	// Option prefix for days, hours, mins, secs.
			) );

			/**
			 * See https://schema.org/actor.
			 */
			WpssoSchema::add_person_names_data( $json_ret, 'actor', $md_opts, 'schema_movie_actor_person_name' );

			/**
			 * See https://schema.org/director.
			 */
			WpssoSchema::add_person_names_data( $json_ret, 'director', $md_opts, 'schema_movie_director_person_name' );

			/**
			 * See https://schema.org/productionCompany.
			 */
			if ( WpssoSchema::is_valid_key( $md_opts, 'schema_movie_prodco_org_id' ) ) {	// Not null, an empty string, or 'none'.

				$md_val = $md_opts[ 'schema_movie_prodco_org_id' ];

				$org_logo_key = 'org_logo_url';

				WpssoSchemaSingle::add_organization_data( $json_ret[ 'productionCompany' ], $mod, $md_val, $org_logo_key, $list_element = true );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
