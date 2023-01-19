<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

/**
 * Since WPSSO Core v13.5.0.
 */
if ( ! class_exists( 'WpssoMessagesTooltipMetaOrg' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltipMeta->get() only when needed.
	 */
	class WpssoMessagesTooltipMetaOrg extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-meta-org_name':

					$text = __( 'The complete or common name for this organization.', 'wpsso' );

					break;

				case 'tooltip-meta-org_name_alt':

					$text = __( 'An alternate name for this organization that you would like Google to consider.', 'wpsso' );

					break;

				case 'tooltip-meta-org_desc':

					$text = __( 'A description for this organization.', 'wpsso' );

					break;

				case 'tooltip-meta-org_url':

					$text = __( 'The website URL for this organization.', 'wpsso' );

					break;

				case 'tooltip-meta-org_logo_url':

					$text = $this->p->msgs->get( 'tooltip-site_org_logo_url' );

					break;

				case 'tooltip-meta-org_banner_url':

					$text = $this->p->msgs->get( 'tooltip-site_org_banner_url' );

					break;

				case 'tooltip-meta-org_schema_type':

					$text = __( 'You may optionally choose a more accurate Schema type for this organization (default is Organization).', 'wpsso' ) . ' ';

					$text .= __( 'Note that Google does not recognize most Schema Organization sub-types as valid organizations, so do not change this value unless you are certain that your selected Schema Organization sub-type will be recognized as a valid Organization by Google.', 'wpsso' );

					break;

				case 'tooltip-meta-org_place_id':

					$text = __( 'Select an optional place (ie. location) for this organization.', 'wpsso' );

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta_org', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-meta-org' switch.

			return $text;
		}
	}
}
