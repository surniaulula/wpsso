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

if ( ! class_exists( 'WpssoMessagesTooltipMetaContact' ) ) {

	/*
	 * Instantiated by WpssoMessagesTooltipMeta->get() only when needed.
	 */
	class WpssoMessagesTooltipMetaContact extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-meta-contact_name':	// Contact Name.

					$text = __( 'A name for this contact point (required).', 'wpsso' ) . ' ';

					break;

				case 'tooltip-meta-contact_name_alt':	// Contact Alternate Name.

					$text = __( 'An alternate name for this contact point.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-meta-contact_desc':	// Contact Description.

					$text = __( 'A description for this contact point.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-meta-contact_schema_type':	// Contact Schema Type.

					$text = __( 'You may optionally choose a more accurate Schema type for this contact point (default is ContactPoint).', 'wpsso' ) . ' ';

					break;

				case 'tooltip-meta-contact_phone':	// Contact Telephone.

					$text = __( 'An optional telephone number for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_fax':	// Contact Fax.

					$text = __( 'An optional fax number for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_email':	// Contact Email.

					$text = __( 'An optional email address for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_street_address':	// Street Address.

					$text = __( 'An optional street address for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_po_box_number':	// P.O. Box Number.

					$text = __( 'An optional post office box number for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_city':	// City.

					$text = __( 'An optional city name for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_region':	// State / Province.

					$text = __( 'An optional state or province name for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_postal_code':	// Zip / Postal Code.

					$text = __( 'An optional postal or zip code for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_country':	// Country.

					$text = __( 'An optional country for this contact point.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_days':		// Open Days / Hours.

					$text = __( 'Select the days and hours that this contact point is open.', 'wpsso' );

					break;

				case 'tooltip-meta-contact_season_dates':	// Seasonal Dates.

					$text = __( 'If this contact point is open seasonally, select the open and close dates of the season.', 'wpsso' );

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta_contact', $text, $msg_key, $info );

					break;
			}

			return $text;
		}
	}
}
