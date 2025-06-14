<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoMessagesTooltipMetaService' ) ) {

	/*
	 * Instantiated by WpssoMessagesTooltipMeta->get() only when needed.
	 */
	class WpssoMessagesTooltipMetaService extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-meta-service_name':	// Service Name.

					$text = __( 'A name for this service (required).', 'wpsso' );

					break;

				case 'tooltip-meta-service_name_alt':	// Service Alternate Name.

					$text = __( 'An alternate name for this service.', 'wpsso' );

					break;

				case 'tooltip-meta-service_desc':	// Service Description.

					$text = __( 'A description for this service.', 'wpsso' );

					break;

				case 'tooltip-meta-service_schema_type':	// Service Schema Type.

					$text = __( 'You may choose a more accurate Schema type for this service (default is Service).', 'wpsso' ) . ' ';

					break;

				case 'tooltip-meta-service_prov_org_id':	// Provider Org.
				case 'tooltip-meta-service_prov_person_id':	// Provider Person.
			
					$text = __( 'A service provider, service operator, or service performer.', 'wpsso' );

		 			break;
		
				case 'tooltip-meta-service_latitude':	// Service Latitude.

					$text = __( 'The numeric decimal degrees latitude for this service.', 'wpsso' ) . ' ';

					$text .= __( 'You may use a service like <a href="http://www.gps-coordinates.net/">Google Maps GPS Coordinates</a> (as an example), to find the approximate GPS coordinates of a street address.', 'wpsso' );

					break;

				case 'tooltip-meta-service_longitude':	// Service Longitude.

					$text = __( 'The numeric decimal degrees longitude for this service.', 'wpsso' ) . ' ';

					$text .= __( 'You may use a service like <a href="http://www.gps-coordinates.net/">Google Maps GPS Coordinates</a> (as an example), to find the approximate GPS coordinates of a street address.', 'wpsso' );

					break;
				
				case 'tooltip-meta-service_radius':	// Service Radius.

					$text = __( 'The geographic area where a service is provided, in meters around a set of latitude and longitude coordinates.', 'wpsso' );

					break;
				
				case 'tooltip-meta-service_offer_catalogs':	// Offer Catalogs.

					$text = __( 'A list of offer catalogs for this service, including the catalog name, description and URL.', 'wpsso' );

				 	break;
			}

			return $text;
		}
	}
}
