<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypePlace' ) ) {

	class WpssoJsonTypePlace {

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
				'json_data_https_schema_org_place' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_place( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			/*
			 * See https://schema.org/image as https://schema.org/ImageObject.
			 * See https://schema.org/subjectOf as https://schema.org/VideoObject.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding image and subjectOf video properties for place' );
			}

			WpssoSchema::add_media_data( $json_ret, $mod, $mt_og, $size_names = 'schema', $add_video = 'subjectOf' );

			/*
			 * Skip reading place meta tags if not main schema type or if there are no place meta tags.
			 */
			$read_mt_place = false;

			if ( preg_grep( '/^(place:|og:(altitude|latitude|longitude))/', array_keys( $mt_og ) ) ) {

				if ( $is_main ) {

					$read_mt_place = true;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipped reading place meta tags (not main schema type)' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no place meta tags found' );
			}

			/*
			 * See https://schema.org/telephone.
			 */
			if ( $read_mt_place ) {

				WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array(
					'telephone' => 'place:telephone',
				) );
			}

			/*
			 * See https://schema.org/address as https://schema.org/PostalAddress.
			 *
			 * <meta property="place:street_address" content="1234 Some Road"/>
			 * <meta property="place:po_box_number" content=""/>
			 * <meta property="place:locality" content="In A City"/>
			 * <meta property="place:region" content="State Name"/>
			 * <meta property="place:postal_code" content="123456789"/>
			 * <meta property="place:country_name" content="USA"/>
			 */
			if ( $read_mt_place ) {

				$postal_address = array();

				if ( WpssoSchema::add_data_itemprop_from_assoc( $postal_address, $mt_og, array(
					'name'                => 'place:name',
					'streetAddress'       => 'place:street_address',
					'postOfficeBoxNumber' => 'place:po_box_number',
					'addressLocality'     => 'place:locality',
					'addressRegion'       => 'place:region',
					'postalCode'          => 'place:postal_code',
					'addressCountry'      => 'place:country_name',
				) ) ) {

					$json_ret[ 'address' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/PostalAddress', $postal_address );
				}
			}

			/*
			 * See https://schema.org/geo as https://schema.org/GeoCoordinates.
			 *
			 * <meta property="place:location:altitude" content="2,200"/>
			 * <meta property="place:location:latitude" content="45"/>
			 * <meta property="place:location:longitude" content="-73"/>
			 * <meta property="og:altitude" content="2,200"/>
			 * <meta property="og:latitude" content="45"/>
			 * <meta property="og:longitude" content="-73"/>
			 */
			if ( $read_mt_place ) {

				$geo_coords = array();

				if ( WpssoSchema::add_data_itemprop_from_assoc( $geo_coords, $mt_og, array(
					'latitude'  => 'place:location:latitude',
					'longitude' => 'place:location:longitude',
					'elevation' => 'place:location:altitude',
				) ) ) {

					$json_ret[ 'geo' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/GeoCoordinates', $geo_coords );
				}
			}

			/*
			 * See https://schema.org/openingHoursSpecification as https://schema.org/OpeningHoursSpecification.
			 */
			if ( $read_mt_place ) {

				$replace = array(
					'/^place:opening_hours:/' => 'place_',
					'/:/'                     => '_',
				);

				$place_opts = SucomUtil::preg_grep_keys( '/^place:opening_hours:/', $mt_og, $invert = false, $replace );

				if ( ! empty( $place_opts ) ) {

					if ( ! empty( $mt_og[ 'og:url' ] ) ) {

						$place_opts[ 'place_rel' ] = $mt_og[ 'og:url' ];
					}

					if ( $opening_hours_spec = WpssoSchemaSingle::get_opening_hours_data( $place_opts, $opt_prefix = 'place' ) ) {

						$json_ret[ 'openingHoursSpecification' ] = $opening_hours_spec;
					}
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
