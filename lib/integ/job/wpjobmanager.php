<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2017-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegJobWpJobManager' ) ) {

	class WpssoIntegJobWpJobManager {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'form_select_schema_job_hiring_org_id' => 1,
				'form_select_schema_job_location_id'   => 1,
				'get_md_defaults'                      => 2,
				'get_post_options'                     => 3,
				'get_job_options'                      => 3,
				'get_organization_options'             => 3,
				'get_place_options'                    => 3,
			), $prio = -1000 );

			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				add_filter( 'wpjm_get_job_listing_structured_data', '__return_empty_array', PHP_INT_MAX );
			}
		}

		public function filter_form_select_schema_job_hiring_org_id( $values ) {

			if ( $post_obj = SucomUtil::get_post_object( $use_post = false, $output = 'object' ) ) {

				if ( $org_name = get_the_company_name( $post_obj ) ) {

					$values[ 'wpjm_org-' . $post_obj->ID ] = sprintf( _x( 'WPJM: %s', 'option value', 'wpsso' ), $org_name );
				}
			}

			return $values;
		}

		public function filter_form_select_schema_job_location_id( $values ) {

			if ( $post_obj = SucomUtil::get_post_object( $use_post = false, $output = 'object' ) ) {

				if ( $place_name = get_the_job_location( $post_obj ) ) {

					$values[ 'wpjm_place-' . $post_obj->ID ] = sprintf( _x( 'WPJM: %s', 'option value', 'wpsso' ), $place_name );
				}
			}

			return $values;
		}

		public function filter_get_md_defaults( array $md_defs, array $mod ) {

			if ( ! $mod[ 'is_post' ] || $mod[ 'post_type' ] !== 'job_listing' ) {

				return $md_defs;
			}

			$post_obj = SucomUtil::get_post_object( $mod[ 'id' ], $output = 'object' );

			if ( $org_name = get_the_company_name( $post_obj ) ) {

				$md_defs[ 'schema_job_hiring_org_id' ] = 'wpjm_org-' . $post_obj->ID;
			}

			if ( $place_name = get_the_job_location( $post_obj ) ) {

				$md_defs[ 'schema_job_location_id' ]   = 'wpjm_place-' . $post_obj->ID;
			}

			self::add_schema_job_defaults( $md_defs, $mod[ 'id' ] );

			return $md_defs;
		}

		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			if ( $mod[ 'post_type' ] !== 'job_listing' ) {

				return $md_opts;
			}

			$job_opts = array();

			self::add_schema_job_defaults( $job_opts, $post_id );	// Add defaults to empty array.

			foreach ( $job_opts as $key => $val ) {	// Hard-code defaults and disable the option.

				$md_opts[ $key ]               = $val;
				$md_opts[ $key . ':disabled' ] = true;
			}

			return $md_opts;
		}

		public function filter_get_job_options( $job_opts, $mod, $job_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'post_type' ] !== 'job_listing' ) {

				return $job_opts;
			}

			$job_opts = self::get_schema_job_options( $mod[ 'id' ] );
			$job_opts = SucomUtil::preg_grep_keys( '/^schema_(job_.*)$/', $job_opts, $invert = false, $replace = '$1' );

			return $job_opts;
		}

		public function filter_get_organization_options( $org_opts, $mod, $org_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( false !== $org_opts ) {	// First come, first served.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: org_opts array already defined' );
				}

				return $org_opts;
			}

			if ( 0 === strpos( $org_id, 'wpjm_org-' ) ) {

				return self::get_organization_id( $org_id, $mod );	// Returns localized values.
			}

			return $org_opts;
		}

		public function filter_get_place_options( $place_opts, $mod, $place_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( false !== $place_opts ) {	// First come, first served.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: place_opts array already defined' );
				}

				return $place_opts;
			}

			if ( 0 === strpos( $place_id, 'wpjm_place-' ) ) {

				return self::get_place_id( $place_id, $mod );	// Returns localized values.
			}

			return $place_opts;
		}

		private static function get_schema_job_options( $post_id ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$post_obj = get_post( $post_id );

			$job_opts = array();

			self::add_schema_job_defaults( $job_opts, $post_id );

			$job_opts[ 'schema_job_title' ]           = wpjm_get_the_job_title( $post_obj );
			$job_opts[ 'schema_job_expire_time' ]     = '00:00';
			$job_opts[ 'schema_job_expire_timezone' ] = get_option( 'timezone_string' );
			$job_opts[ 'schema_job_expire_iso' ]      = date_format( date_create(
				$job_opts[ 'schema_job_expire_date' ] . ' ' .
				$job_opts[ 'schema_job_expire_time' ] . ' ' .
				$job_opts[ 'schema_job_expire_timezone' ]
			), 'c' );

			return $job_opts;
		}

		private static function add_schema_job_defaults( array &$job_opts, $post_id ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			static $local_cache = array();

			if ( ! isset( $local_cache[ $post_id ] ) ) {	// Only create the defaults array once.

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'creating a new defaults static array' );
				}

				$local_cache[ $post_id ] = array();

				/*
				 * Get the default schema type (job.posting by default).
				 */
				$local_cache[ $post_id ][ 'schema_type' ] = $wpsso->options[ 'schema_type_for_job_listing' ];

				$post_obj = get_post( $post_id );

				/*
				 * Add post meta.
				 */
				foreach ( array(
					'schema_job_expire_date' => '_job_expires',
				) as $md_key => $meta_key ) {

					$local_cache[ $post_id ][ $md_key ] = get_post_meta( $post_id, $meta_key, $single = true );
				}

				$job_types = wpjm_get_job_employment_types( $post_obj );

				if ( ! empty( $job_types ) ) {

					foreach ( $job_types as $empl_type ) {

						if ( ! empty( $empl_type ) ) {	// Just in case.

				 			/*
							 * Google approved values (case sensitive):
							 *
							 * 	FULL_TIME
							 *	PART_TIME
							 *	CONTRACTOR
							 *	TEMPORARY
							 *	INTERN
							 *	VOLUNTEER
							 *	PER_DIEM
							 *	OTHER
							 */
							$empl_type = SucomUtil::sanitize_hookname( $empl_type );	// Sanitize with underscores.

							$empl_type = strtoupper( $empl_type );

							$local_cache[ $post_id ][ 'schema_job_empl_type_' . $empl_type] = 1;	// Checkbox value is 0 or 1.
						}
					}
				}

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'defaults static array', $local_cache[ $post_id ] );
				}

			} elseif ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'using the cached defaults static array' );
			}

			if ( ! empty( $local_cache[ $post_id ] ) ) {

				$job_opts = array_merge( $job_opts, $local_cache[ $post_id ] );
			}
		}

		private static function get_organization_id( $org_id, $mod ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$count = null;

			$post_id = str_replace( 'wpjm_org-', '', $org_id, $count );

			if ( ! $count || ! is_numeric( $post_id ) ) {

				return false;
			}

			$post_obj = get_post( $post_id );

			$org_opts = array(
				'org_url'      => get_the_company_website( $post_obj ),
				'org_name'     => get_the_company_name( $post_obj ),
				'org_desc'     => get_the_company_tagline( $post_obj ),
				'org_logo_url' => get_the_company_logo( $post_obj, $size = 'full' ),
				'org_sameas'   => array(),
			);

			$twitter_name = SucomUtil::sanitize_twitter_name( get_the_company_twitter( $post_obj ), $add_at = false );

			if ( ! empty( $twitter_name ) ) {

				$org_opts[ 'org_sameas' ][] = 'https://twitter.com/' . $twitter_name;
			}

			if ( empty( $org_opts ) ) {

				return false;

			}

			return $org_opts;
		}

		private static function get_place_id( $place_id, $mod ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$count = null;

			$post_id = str_replace( 'wpjm_place-', '', $place_id, $count );

			if ( ! $count || ! is_numeric( $post_id ) ) {

				return false;
			}

			$post_obj = get_post( $post_id );

			$place_opts = array(
				'place_name' => get_the_job_location( $post_obj ),
			);

			/*
			 * Possible place option keys:
			 *
			 * 'place_url'
			 * 'place_name'
			 * 'place_name_alt'
			 * 'place_desc'
			 * 'place_phone'
			 * 'place_currencies_accepted'
			 * 'place_payment_accepted'
			 * 'place_price_range'
			 * 'place_street_address'
			 * 'place_po_box_number'
			 * 'place_city'
			 * 'place_region'
			 * 'place_postal_code'
			 * 'place_country'
			 * 'place_altitude'
			 * 'place_latitude'
			 * 'place_longitude'
			 */
			foreach ( array(
				'place_city'      => 'geolocation_city',
				'place_region'    => 'geolocation_state_short',
				'place_country'   => 'geolocation_country_short',	// Alpha2 country code.
				'place_latitude'  => 'geolocation_lat',
				'place_longitude' => 'geolocation_long',
			) as $md_key => $meta_key ) {

				$place_opts[ $md_key ] = get_post_meta( $post_id, $meta_key, $single = true );
			}

			if ( empty( $place_opts ) ) {

				return false;

			}

			return $place_opts;
		}
	}
}
