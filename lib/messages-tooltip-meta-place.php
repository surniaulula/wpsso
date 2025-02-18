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

if ( ! class_exists( 'WpssoMessagesTooltipMetaPlace' ) ) {

	/*
	 * Instantiated by WpssoMessagesTooltipMeta->get() only when needed.
	 */
	class WpssoMessagesTooltipMetaPlace extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-meta-place_name':

					$text = __( 'A name for this place (required).', 'wpsso' ) . ' ';

					$text .= __( 'The place name may appear in WordPress editing pages and in the Schema Place "name" property.', 'wpsso' );

					break;

				case 'tooltip-meta-place_name_alt':

					$text = __( 'An alternate name for this place.', 'wpsso' ) . ' ';

					$text .= __( 'The place alternate name may appear in the Schema Place "alternateName" property.', 'wpsso' );

					break;

				case 'tooltip-meta-place_desc':

					$text = __( 'A description for this place.', 'wpsso' ) . ' ';

					$text .= __( 'The place description may appear in the Schema Place "description" property.', 'wpsso' );

					break;

				case 'tooltip-meta-place_schema_type':	// Place Schema Type.

					$text = __( 'You may optionally choose a more accurate Schema type for this place (default is LocalBusiness).', 'wpsso' ) . ' ';

					break;

				case 'tooltip-meta-place_is_default':	// Place Is Default.

					$text = __( 'You may choose this place as the default event venue, job location, etc.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-meta-place_street_address':	// Street Address.

					$text = __( 'An optional street address for this place.', 'wpsso' );

					break;

				case 'tooltip-meta-place_po_box_number':	// P.O. Box Number.

					$text = __( 'An optional post office box number for this place.', 'wpsso' );

					break;

				case 'tooltip-meta-place_city':	// City.

					$text = __( 'An optional city name for this place.', 'wpsso' );

					break;

				case 'tooltip-meta-place_region':	// State / Province.

					$text = __( 'An optional state or province name for this place.', 'wpsso' );

					break;

				case 'tooltip-meta-place_postal_code':	// Zip / Postal Code.

					$text = __( 'An optional postal or zip code for this place.', 'wpsso' );

					break;

				case 'tooltip-meta-place_country':	// Country.

					$text = __( 'An optional country for this place.', 'wpsso' );

					break;

				case 'tooltip-meta-place_phone':	// Telephone.

					$text = __( 'An optional telephone number for this place.', 'wpsso' );

					break;
				case 'tooltip-meta-place_latitude':	// Place Latitude.

					$text = __( 'The numeric decimal degrees latitude for this place (required).', 'wpsso' ) . ' ';

					$text .= __( 'You may use a service like <a href="http://www.gps-coordinates.net/">Google Maps GPS Coordinates</a> (as an example), to find the approximate GPS coordinates of a street address.', 'wpsso' );

					break;

				case 'tooltip-meta-place_longitude':	// Place Longitude.

					$text = __( 'The numeric decimal degrees longitude for this place (required).', 'wpsso' ) . ' ';

					$text .= __( 'You may use a service like <a href="http://www.gps-coordinates.net/">Google Maps GPS Coordinates</a> (as an example), to find the approximate GPS coordinates of a street address.', 'wpsso' );

					break;

				case 'tooltip-meta-place_altitude':	// Place Altitude.

					$text = __( 'An optional numeric altitude (in meters above sea level) for this place.', 'wpsso' );

					break;

				case 'tooltip-meta-place_img_id':	// Place Image ID.

					$text = __( 'An image of this place (ie. an image of the business storefront or location).', 'wpsso' ) . ' ';

					$text .= __( 'The image you select should show the physical location of this place (ie. of the latitude and longitude entered).', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a place image URL is entered.', 'wpsso' ) . '</em>';

					break;

				case 'tooltip-meta-place_img_url':	// or Place Image URL.

					$text = __( 'You can enter a place image URL (including the http/https prefix) instead of selecting an image ID.', 'wpsso' ) . ' ';

					$text .= __( 'The image URL option allows you to choose an image outside of the WordPress Media Library and/or a smaller logo style image.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a place image ID is selected.', 'wpsso' ) . '</em>';

					break;

				case 'tooltip-meta-place_timezone':	// Place Timezone.

					$text = __( 'A timezone for the open and close hours of this place.', 'wpsso' ) . ' ';

					$text .= __( 'The default timezone value is provided by WordPress.', 'wpsso' );

					break;

				case 'tooltip-meta-place_days':		// Open Days / Hours.

					$text = __( 'Select the days and hours that this place is open.', 'wpsso' );

					break;

				case 'tooltip-meta-place_season_dates':	// Seasonal Dates.

					$text = __( 'If this place is open seasonally, select the open and close dates of the season.', 'wpsso' );

					break;

				case 'tooltip-meta-place_service_radius':

					$text = __( 'The geographic area where a service is provided, in meters around the location.', 'wpsso' );

					break;

				case 'tooltip-meta-place_currencies_accepted':

					$text = sprintf( __( 'A comma-delimited list of <a href="%1$s">ISO 4217 currency codes</a> accepted by the local business (example: %2$s).', 'wpsso' ), 'https://en.wikipedia.org/wiki/ISO_4217', 'USD, CAD' );

					break;

				case 'tooltip-meta-place_payment_accepted':

					$text = __( 'A comma-delimited list of payment options accepted by the local business (example: Cash, Credit Card).', 'wpsso' );

					break;

				case 'tooltip-meta-place_price_range':

					$text = __( 'The relative price of goods or services provided by the local business (example: $, $$, $$$, or $$$$).', 'wpsso' );

					break;

				case 'tooltip-meta-place_accept_res':

					$text = __( 'This food establishment accepts reservations.', 'wpsso' );

					break;

				case 'tooltip-meta-place_menu_url':

					$text = __( 'The menu URL for this food establishment.', 'wpsso' );

					break;

				case 'tooltip-meta-place_cuisine':

					$text = __( 'The cuisine served by this food establishment.', 'wpsso' );

					break;

				case 'tooltip-meta-place_order_urls':

					$text = __( 'A comma-delimited list of website and mobile app URLs to order products.', 'wpsso' ) . ' ';

					$text .= __( 'These order action URL(s) will be used in the Schema potentialAction property.', 'wpsso' );

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta_place', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-meta-place' switch.

			return $text;
		}
	}
}
