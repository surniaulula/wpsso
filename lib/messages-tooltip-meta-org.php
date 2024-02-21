<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

/*
 * Since WPSSO Core v13.5.0.
 */
if ( ! class_exists( 'WpssoMessagesTooltipMetaOrg' ) ) {

	/*
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

				case 'tooltip-meta-org_place_id':

					$text = __( 'Select an optional place (ie. location) for this organization.', 'wpsso' );

					break;

				case 'tooltip-meta-org_schema_type':	// Organization Schema Type.

					$text = __( 'You may optionally choose a more accurate Schema type for this organization (default is Organization).', 'wpsso' ) . ' ';

					$text .= __( 'Note that Google considers Schema Organization sub-types, that are also Schema Place sub-types, as places and not organizations.', 'wpsso' ) . ' ';

					$text .= __( 'For this reason, the Schema Organization list does not include any Schema Place sub-types.', 'wpsso' ) . ' ';

					break;

				case 'tooltip-meta-org_pub_principles_url':	// Publishing Principles URL.

					$text .= __( 'A document describing the editorial principles of an Organization that relate to their activities as a publisher.', 'wpsso' );

					break;

				case 'tooltip-meta-org_corrections_policy_url':	// Corrections Policy URL.

					$text .= __( 'A statement describing (in news media, the newsroom\'s) disclosure and correction policy for errors.', 'wpsso' );

					break;

				case 'tooltip-meta-org_diversity_policy_url':	// Diversity Policy URL.

					$text .= __( 'A statement describing (in news media, the newsroom\'s) diversity policy on both staffing and sources.', 'wpsso' );

					break;

				case 'tooltip-meta-org_ethics_policy_url':	// Ethics Policy URL.

					$text .= __( 'A statement describing the personal, organizational, and corporate standards of behavior expected by the organization.', 'wpsso' );

					break;

				case 'tooltip-meta-org_fact_check_policy_url':	// Fact Checking Policy URL.

					$text .= __( 'A statement describing verification and fact-checking processes for a news media organization or other fact-checking organization.', 'wpsso' );

					break;

				case 'tooltip-meta-org_feedback_policy_url':	// Feedback Policy URL.

					$text = __( 'A statement about public engagement activities (for news media, the newsroom\'s), including involving the public - digitally or otherwise - in coverage decisions, reporting and activities after publication.', 'wpsso' );

					break;

				case 'tooltip-meta-org_award':			// Organization Awards.

					$text = __( 'One or more awards this organization has won.', 'wpsso' );

					break;

				/*
				 * News Media Organization section.
				 */
				case 'tooltip-meta-org_masthead_url':		// Masthead Page URL.

					$text .= __( 'A link to the masthead page or a page listing top editorial management.', 'wpsso' );

					break;

				case 'tooltip-meta-org_coverage_policy_url':	// Coverage Priorities Policy URL.

					$text .= __( 'A statement on coverage priorities, including any public agenda or stance on issues.', 'wpsso' );

					break;

				case 'tooltip-meta-org_no_bylines_policy_url':	// No Bylines Policy URL.

					$text .= __( 'A statement about policy on use of unnamed sources and the decision process required.', 'wpsso' );

					break;

				case 'tooltip-meta-org_sources_policy_url':	// Unnamed Sources Policy URL.

					$text .= __( 'A statement about policy on use of unnamed sources and the decision process required.', 'wpsso' );

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta_org', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-meta-org' switch.

			return $text;
		}
	}
}
