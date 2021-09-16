<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypePlace' ) ) {

	class WpssoJsonFiltersTypePlace {

		private $p;	// Wpsso class object.

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

			/**
			 * Property:
			 *	image as https://schema.org/ImageObject
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding image property for place (videos disabled)' );
			}

			WpssoSchema::add_media_data( $json_ret, $mod, $mt_og, $size_names = 'schema', $add_video = false );

			/**
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

			/**
			 * Property:
			 *	address as https://schema.org/PostalAddress
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

				foreach ( array(
					'name'                => 'name',
					'streetAddress'       => 'street_address',
					'postOfficeBoxNumber' => 'po_box_number',
					'addressLocality'     => 'locality',
					'addressRegion'       => 'region',
					'postalCode'          => 'postal_code',
					'addressCountry'      => 'country_name',
				) as $prop_name => $mt_suffix ) {

					if ( isset( $mt_og[ 'place:' . $mt_suffix ] ) ) {

						$postal_address[ $prop_name ] = $mt_og[ 'place:' . $mt_suffix ];
					}
				}

				if ( ! empty( $postal_address ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding place address meta tags for postal address' );
					}

					$json_ret[ 'address' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/PostalAddress', $postal_address );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no place address meta tags found for postal address' );
				}
			}

			/**
			 * Property:
			 *	telephone
			 */
			if ( $read_mt_place ) {

				foreach ( array(
					'telephone' => 'telephone',
				) as $prop_name => $og_key ) {

					if ( isset( $mt_og[ 'place:' . $og_key ] ) ) {

						$json_ret[ $prop_name ] = $mt_og[ 'place:' . $og_key ];
					}
				}
			}

			/**
			 * Property:
			 *	geo as https://schema.org/GeoCoordinates
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

				foreach ( array(
					'elevation' => 'altitude',
					'latitude'  => 'latitude',
					'longitude' => 'longitude',
				) as $prop_name => $mt_suffix ) {

					if ( isset( $mt_og[ 'place:location:' . $mt_suffix ] ) ) {	// Prefer the place location meta tags.

						$geo_coords[ $prop_name ] = $mt_og[ 'place:location:' . $mt_suffix ];

					} elseif ( isset( $mt_og[ 'og:' . $mt_suffix ] ) ) {

						$geo_coords[ $prop_name ] = $mt_og[ 'og:' . $mt_suffix ];
					}
				}

				if ( ! empty( $geo_coords ) ) {

					$json_ret[ 'geo' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/GeoCoordinates', $geo_coords );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no place:location meta tags found for geo coordinates' );
				}
			}

			/**
			 * Property:
			 * 	openingHoursSpecification as https://schema.org/OpeningHoursSpecification
			 */
			if ( $read_mt_place ) {

				$replace = array( '/^place:opening_hours:/' => 'place_', '/:/' => '_' );

				$place_opts = SucomUtil::preg_grep_keys( '/^place:opening_hours:/', $mt_og, $invert = false, $replace );

				if ( ! empty( $place_opts ) ) {

					if ( ! empty( $mt_og[ 'og:url' ] ) ) {

						$place_opts[ 'place_rel' ] = $mt_og[ 'og:url' ];
					}

					$opening_hours_spec = WpssoSchemaSingle::get_opening_hours_data( $place_opts, $opt_prefix = 'place' );

					if ( ! empty( $opening_hours_spec ) ) {

						$json_ret[ 'openingHoursSpecification' ] = $opening_hours_spec;
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no place:opening_hours meta tags for opening hours specification' );
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
