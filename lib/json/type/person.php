<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypePerson' ) ) {

	class WpssoJsonTypePerson {

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
				'json_data_https_schema_org_person' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_person( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$user_id = 'none';

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$user_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_person_id', $filter_opts = true, $merge_defs = true );
			}

			if ( empty( $user_id ) || 'none' === $user_id ) {

				if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

					$user_id = $this->p->options[ 'site_pub_person_id' ];	// 'none' by default.

				} elseif ( $mod[ 'is_user' ] ) {

					$user_id = $mod[ 'id' ];	// Could be false.

				} else {

					$user_id = 'none';
				}

				if ( empty( $user_id ) || 'none' === $user_id ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: user ID is empty or "none"' );
					}

					return $json_data;
				}
			}

			/*
			 * Possibly inherit the schema type.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'possibly inherit the schema type' );

				$this->p->debug->log_arr( 'json_data', $json_data );
			}

			$json_ret = WpssoSchema::get_data_context( $json_data );	// Returns array() if no schema type found.

		 	/*
			 * $user_id can be 'none' or a number (including 0).
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding data for person id = ' . $user_id );
			}

			WpssoSchemaSingle::add_person_data( $json_ret, $mod, $user_id, $list_element = false );

			/*
			 * Override author's website url and use the og url instead.
			 */
			if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

				$json_ret[ 'url' ] = SucomUtil::get_home_url( $this->p->options, $mod );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
