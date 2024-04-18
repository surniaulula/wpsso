<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeProfilePage' ) ) {

	class WpssoJsonTypeProfilePage {

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
				'json_data_https_schema_org_profilepage' => 5,
			) );
		}

		/*
		 * See https://developers.google.cn/search/docs/appearance/structured-data/profile-page.
		 */
		public function filter_json_data_https_schema_org_profilepage( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Maybe add a "mentions" property with any post schema type.
			 */
			$prop_type_ids = empty( $this->p->options[ 'schema_def_profile_page_mentions_prop' ] ) ? array() : array( 'mentions' => false );

			WpssoSchema::add_posts_data( $json_data, $mod, $mt_og, $page_type_id, $is_main, $prop_type_ids );

			/*
			 * Add the Schema Person as the 'mainEntity'.
			 */
			$user_id = 'none';

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$user_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_profile_person_id', $filter_opts = true, $merge_defs = true );
			}

			if ( empty( $user_id ) || 'none' === $user_id ) {

				if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

					$user_id = $this->p->options[ 'site_pub_person_id' ];	// 'none' by default.

				} elseif ( $mod[ 'is_user' ] ) {

					$user_id = $mod[ 'id' ];	// Could be false.

				} else $user_id = 'none';

				if ( empty( $user_id ) || 'none' === $user_id ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: user ID is empty or "none"' );
					}

					return $json_data;
				}
			}

		 	/*
			 * $user_id can be 'none' or a number (including 0).
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding data for person id = ' . $user_id );
			}

			unset( $json_data[ 'mainEntityOfPage' ] );

			$json_data[ 'mainEntity' ] = null;

			WpssoSchemaSingle::add_person_data( $json_data[ 'mainEntity' ], $mod, $user_id, $list_element = false );

			return $json_data;
		}
	}
}
