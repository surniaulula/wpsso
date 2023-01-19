<?php
/*
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegEventTheEventsCalendar' ) ) {

	class WpssoIntegEventTheEventsCalendar {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'form_select_schema_event_location_id'         => 1,
				'form_select_schema_event_organizer_person_id' => 1,
				'get_md_defaults'                              => 2,
				'get_post_options'                             => 3,
				'get_event_options'                            => 3,
				'get_person_options'                           => 3,
				'get_place_options'                            => 3,
			) );

			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				add_filter( 'tribe_json_ld_event_data', '__return_empty_array', PHP_INT_MAX );
			}
		}

		public function filter_form_select_schema_event_location_id( $values ) {

			foreach ( tribe_get_venues() as $post_obj ) {

				$values[ 'tribe_venue-' . $post_obj->ID ] = sprintf( __( 'TEC: %s', 'option value', 'wpsso' ), $post_obj->post_title );
			}

			return $values;
		}

		public function filter_form_select_schema_event_organizer_person_id( $values ) {

			foreach ( tribe_get_organizers() as $post_obj ) {

				$values[ 'tribe_organizer-' . $post_obj->ID ] = sprintf( _x( 'TEC: %s', 'option value', 'wpsso' ), $post_obj->post_title );
			}

			return $values;
		}

		public function filter_get_md_defaults( array $md_defs, array $mod ) {

			if ( ! $mod[ 'is_post' ] || $mod[ 'post_type' ] !== 'tribe_events' ) {

				return $md_defs;
			}

			if ( $venue_id = tribe_get_venue_id( $mod[ 'id' ] ) ) {

				$md_defs[ 'schema_event_location_id' ] = 'tribe_venue-' . $venue_id;
			}

			if ( $person_id = tribe_get_organizer_id( $mod[ 'id' ] ) ) {

				$md_defs[ 'schema_event_organizer_person_id' ] = 'tribe_organizer-' . $person_id;
			}

			return $md_defs;
		}

		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			if ( $mod[ 'post_type' ] !== 'tribe_events' ) {

				return $md_opts;
			}

			if ( ! class_exists( 'Tribe__Events__Timezones' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: Tribe__Events__Timezones class missing' );
				}

				return $md_opts;
			}

			$default_timezone      = date_default_timezone_get();
			$event_timezone        = Tribe__Events__Timezones::get_event_timezone_string( $post_id );
			$event_currency_symbol = tribe_get_event_meta( $post_id, '_EventCurrencySymbol', true );
			$event_currency_abbrev = SucomUtil::get_currency_symbol_abbrev( $event_currency_symbol, $this->p->options[ 'og_def_currency' ] );
			$event_start_ts        = Tribe__Events__Timezones::event_start_timestamp( $post_id, 'UTC' );
			$event_end_ts          = Tribe__Events__Timezones::event_end_timestamp( $post_id, 'UTC' );

			if ( empty( $default_timezone ) ) {	// Just in case.

				$default_timezone = 'UTC';
			}

			if ( empty( $event_timezone ) ) {	// Just in case.

				$event_timezone = 'UTC';
			}

			date_default_timezone_set( $event_timezone );	// Set reference timezone for the event.

			if ( date( 'H:i:s', $event_end_ts ) === '23:59:59' ) {

				$event_end_ts++;	// Next day at midnight.
			}

			$event_opts[ 'schema_event_start_date' ]       = date( 'Y-m-d', $event_start_ts );
			$event_opts[ 'schema_event_start_time' ]       = date( 'H:i', $event_start_ts );
			$event_opts[ 'schema_event_start_timezone' ]   = $event_timezone;
			$event_opts[ 'schema_event_end_date' ]         = date( 'Y-m-d', $event_end_ts );
			$event_opts[ 'schema_event_end_time' ]         = date( 'H:i', $event_end_ts );
			$event_opts[ 'schema_event_end_timezone' ]     = $event_timezone;
			$event_opts[ 'schema_event_offer_name_0' ]     = get_the_title( $post_id );
			$event_opts[ 'schema_event_offer_url_0' ]      = tribe_get_event_link( $post_id, false );
			$event_opts[ 'schema_event_offer_price_0' ]    = tribe_get_cost( $post_id );
			$event_opts[ 'schema_event_offer_currency_0' ] = $event_currency_abbrev;
			$event_opts[ 'schema_event_offer_avail_0' ]    = 'https://schema.org/InStock';

			if ( empty( $event_opts[ 'schema_event_offer_price_0' ] ) ) {	// Just in case.

				$event_opts[ 'schema_event_offer_price_0' ] = 0;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'event_opts', $event_opts );
			}

			foreach ( $event_opts as $key => $value ) {

				$md_opts[ $key ]               = $value;
				$md_opts[ $key . ':disabled' ] = true;
			}

			date_default_timezone_set( $default_timezone );	// Restore the original timezone.

			return $md_opts;
		}

		public function filter_get_event_options( $event_opts, $mod, $event_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( false !== $event_opts ) {	// first come, first served

				return $event_opts;

			} elseif ( preg_match( '/^tribe_events-([0-9]+)$/', $event_id, $match ) ) {	// specific event

				$mod = $this->p->post->get_mod( $match[ 1 ] );

			} elseif ( $mod[ 'post_type' ] !== 'tribe_events' ) {	// current post

				return $event_opts;

			} elseif ( ! class_exists( 'Tribe__Events__Timezones' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: Tribe__Events__Timezones class missing' );
				}

				return $event_opts;

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting event details for mod id ' . $mod[ 'id' ] );
			}

			$post_id               = $mod[ 'id' ];
			$default_timezone      = date_default_timezone_get();
			$event_timezone        = Tribe__Events__Timezones::get_event_timezone_string( $post_id );
			$event_currency_symbol = tribe_get_event_meta( $post_id, '_EventCurrencySymbol', true );
			$event_currency_abbrev = SucomUtil::get_currency_symbol_abbrev( $event_currency_symbol, $this->p->options[ 'og_def_currency' ] );
			$event_start_ts        = Tribe__Events__Timezones::event_start_timestamp( $post_id, 'UTC' );
			$event_end_ts          = Tribe__Events__Timezones::event_end_timestamp( $post_id, 'UTC' );

			if ( empty( $default_timezone ) ) {	// Just in case.

				$default_timezone = 'UTC';
			}

			if ( empty( $event_timezone ) ) {	// Just in case.

				$event_timezone = 'UTC';
			}

			date_default_timezone_set( $event_timezone );	// Set reference timezone for the event.

			$event_opts = array(
				'event_type'                => $this->p->post->get_options( $post_id, 'schema_type' ),
				'event_start_date'          => date( 'Y-m-d', $event_start_ts ),
				'event_start_time'          => date( 'H:i', $event_start_ts ),
				'event_start_timezone'      => $event_timezone,
				'event_end_date'            => date( 'Y-m-d', $event_end_ts ),
				'event_end_time'            => date( 'H:i', $event_end_ts ),
				'event_end_timezone'        => $event_timezone,
				'event_offers'              => array(	// Array of arrays.
					array(
						'offer_name'           => get_the_title( $post_id ),
						'offer_url'            => tribe_get_event_link( $post_id, false ),
						'offer_price'          => tribe_get_cost( $post_id ),
						'offer_price_currency' => $event_currency_abbrev,
						'offer_avail'          => 'https://schema.org/InStock',
					),
				),
			);

			if ( empty( $event_opts[ 'event_offers' ][ 0 ][ 'offer_price' ] ) ) {	// Just in case.

				$event_opts[ 'event_offers' ][ 0 ][ 'offer_price' ] = 0;
			}

			date_default_timezone_set( $default_timezone );	// Restore the original timezone.

			return $event_opts;
		}

		public function filter_get_person_options( $person_opts, $mod, $person_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( false !== $person_opts ) {

				return $person_opts;

			} elseif ( preg_match( '/^tribe_organizer-([0-9]+)$/', $person_id, $match ) ) {

				$mod = $this->p->post->get_mod( $match[ 1 ] );

			} elseif ( $mod[ 'post_type' ] !== 'tribe_organization' ) {

				return $person_opts;
			}

			$post_id    = $mod[ 'id' ];
			$person_url = get_permalink( $post_id );

			/*
			 * Set reference values for admin notices.
			 */
			if ( is_admin() ) {

				$this->p->util->maybe_set_ref( $person_url, $mod, __( 'getting TEC options', 'wpsso' ) );
			}

			$person_desc = $this->p->page->get_description( $mod, $md_key = 'schema_desc', $max_len = 'schema_desc' );

			$person_opts = array(
				'person_type'   => 'person',
				'person_url'    => $person_url,
				'person_name'   => tribe_get_organizer( $post_id ),
				'person_desc'   => $person_desc,
				'person_email'  => tribe_get_organizer_email( $post_id ),
				'person_phone'  => tribe_get_organizer_phone( $post_id ),
				'person_images' => $this->p->media->get_all_images( $num = 1, $size_names = 'schema', $mod, $md_pre = array( 'schema', 'og' ) ),
			);

			/*
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {

				$this->p->util->maybe_unset_ref( $person_url );
			}

			return $person_opts;
		}

		public function filter_get_place_options( $place_opts, $mod, $place_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( false !== $place_opts ) {	// First come, first served.

				return $place_opts;

			} elseif ( preg_match( '/^tribe_venue-([0-9]+)$/', $place_id, $match ) ) {

				$mod = $this->p->post->get_mod( $match[ 1 ] );

			} elseif ( 'tribe_venue' !== $mod[ 'post_type' ] ) {	// Current post.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post_type is not a tribe_venue' );
				}

				return $place_opts;
			}

			$post_id   = $mod[ 'id' ];
			$place_url = get_permalink( $post_id );

			/*
			 * Set reference values for admin notices.
			 */
			if ( is_admin() ) {

				$this->p->util->maybe_set_ref( $place_url, $mod, __( 'getting TEC options', 'wpsso' ) );
			}

			$place_desc = $this->p->page->get_description( $mod, $md_key = 'schema_desc', $max_len = 'schema_desc' );

			$place_opts = array(
				'place_url'            => $place_url,
				'place_name'           => tribe_get_venue( $post_id ),
				'place_desc'           => $place_desc,
				'place_street_address' => tribe_get_address( $post_id ),
				'place_city'           => tribe_get_city( $post_id ),
				'place_region'         => tribe_get_region( $post_id ),
				'place_postal_code'    => tribe_get_zip( $post_id ),
				'place_country'        => tribe_get_country( $post_id ),
				'place_phone'          => tribe_get_phone( $post_id ),
				'place_images'         => $this->p->media->get_all_images( $num = 1, $size_names = 'schema', $mod, $md_pre = array( 'schema', 'og' ) ),
			);

			$coords = tribe_get_coordinates( $mod[ 'id' ] );

			foreach ( array( 'place_latitude' => 'lat', 'place_longitude' => 'lng' ) as $opt_key => $coord_key ) {

				if ( isset( $coords[ $coord_key ] ) ) {

					$place_opts[ $opt_key ] = $coords[ $coord_key ];
				}
			}

			/*
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {

				$this->p->util->maybe_unset_ref( $place_url );
			}

			return $place_opts;
		}
	}
}
