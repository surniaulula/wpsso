<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeWebpage' ) ) {

	class WpssoJsonTypeWebpage {

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
				'json_data_https_schema_org_webpage' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_webpage( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			$filter_name = 'wpsso_json_prop_https_schema_org_breadcrumb';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			$crumb_data = apply_filters( $filter_name, array(), $mod, $mt_og, $page_type_id, $is_main );

			if ( ! empty( $crumb_data ) ) {

				$json_ret[ 'breadcrumb' ] = $crumb_data;
			}

			if ( ! empty( $json_data[ 'image' ][ 0 ] ) ) {

				if ( ! empty( $json_data[ 'image' ][ 0 ][ '@id' ] ) ) {

					$json_ret[ 'primaryImageOfPage' ] = array( '@id' => $json_data[ 'image' ][ 0 ][ '@id' ] );

				} else {

					$json_ret[ 'primaryImageOfPage' ] = $json_data[ 'image' ][ 0 ];
				}
			}

			$json_ret[ 'potentialAction' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/ReadAction', array(
				'target' => $json_data[ 'url' ],
			) );

			/*
			 * Since WPSSO Core v13.10.0.
			 *
			 * Add reviewed by organizations and persons.
			 *
			 * See https://schema.org/reviewedBy.
			 */
			if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

				$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				foreach ( array(
					'schema_webpage_reviewed_by_org_id'    => 'reviewedBy',
					'schema_webpage_reviewed_by_person_id' => 'reviewedBy',
				) as $opt_pre => $prop_name ) {

					foreach ( SucomUtil::preg_grep_keys( '/^' . $opt_pre . '(_[0-9]+)?$/', $md_opts ) as $opt_key => $id ) {

						/*
						 * Check that the option value is not true, false, null, empty string, or 'none'.
						 */
						if ( ! SucomUtil::is_valid_option_value( $id ) ) {

							continue;
						}

						switch ( $opt_pre ) {

							case 'schema_webpage_reviewed_by_org_id':

								WpssoSchemaSingle::add_organization_data( $json_ret[ $prop_name ], $mod, $id,
									$org_logo_key = 'org_logo_url', $list_element = true );

								break;

							case 'schema_webpage_reviewed_by_person_id':

								WpssoSchemaSingle::add_person_data( $json_ret[ $prop_name ], $mod, $id, $list_element = true );

								break;
						}
					}
				}
			}

			/*
			 * See https://schema.org/lastReviewed.
			 */
			if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

				if ( $date = WpssoSchema::get_opts_date_iso( $md_opts, 'schema_webpage_reviewed_last' ) ) {

					$json_ret[ 'lastReviewed' ] = $date;
				}
			}

			/*
			 * See https://schema.org/speakable.
			 */
			if ( ! empty( $this->p->options[ 'plugin_speakable_css_csv' ] ) ) {

				$speakable_css_sel = SucomUtil::explode_csv( $this->p->options[ 'plugin_speakable_css_csv' ] );

				if ( ! empty( $speakable_css_sel ) ) {

					$json_ret[ 'speakable' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/SpeakableSpecification', array(
						'cssSelector' => $speakable_css_sel ) );
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
