<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoMessagesTooltipSite' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltip->get() only when needed.
	 */
	class WpssoMessagesTooltipSite extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-site_name':	// WebSite Name.

					$text = sprintf( __( 'The website name is used for the Facebook / Open Graph and Pinterest Rich Pin %s meta tag.', 'wpsso' ), '<code>og:site_name</code>' ) . ' ';

					break;

				case 'tooltip-site_name_alt':	// WebSite Alternate Name.

					$text = __( 'An optional alternate name for your website that you want Google to consider.', 'wpsso' );

					break;

				case 'tooltip-site_desc':	// WebSite URL.

					$text = __( 'The website description is used for the WordPress blog (non-static) front page.', 'wpsso' );

					break;

				case 'tooltip-site_org_logo_url':	// Organization Logo URL.

					$text = __( 'A URL for this organization\'s logo image that Google can show in its search results and <em>Knowledge Graph</em>.', 'wpsso' );

					break;

				case 'tooltip-site_org_banner_url':	// Organization Banner URL.

					$text = __( 'A URL for this organization\'s banner image - <strong>measuring exactly 600x60px</strong> - that Google News can show for Schema Article type content from this publisher.', 'wpsso' );

					break;

				case 'tooltip-site_org_schema_type':	// Organization Schema Type.

					$text = __( 'Google does not recognize all Schema Organization sub-types as valid organizations.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The default Schema type ID for the WebSite organization is "%s".', 'wpsso' ), 'organization' ) . ' ';

					$text .= sprintf( __( 'You should not change this default value unless you are confident that Google will recognize your preferred Schema Organization sub-type as a valid organization.', 'wpsso' ), 'organization' ) . ' ';

					$text .= sprintf( __( 'To select a different organization type ID for the WebSite, define the %s constant with your preferred type ID (note that this is a Schema type ID, not a Schema type URL).', 'wpsso' ), '<code>WPSSO_SCHEMA_ORGANIZATION_TYPE_ID</code>' );

					break;

				case 'tooltip-site_org_place_id':	// Organization Location.

					if ( isset( $this->p->cf[ 'plugin' ][ 'wpssoplm' ] ) ) {

						$plm_info = $this->p->cf[ 'plugin' ][ 'wpssoplm' ];

						$plm_addon_link = $this->p->util->get_admin_url( 'addons#wpssoplm', $plm_info[ 'short' ] );

						$text = sprintf( __( 'Select an optional location for this organization (requires the %s add-on).',
							'wpsso' ), $plm_addon_link );
					}

					break;

				case 'tooltip-site_pub_person_id':	// WebSite Publisher Person.

					$text = __( 'Select a user for the Schema Person publisher markup.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The available Person list includes all users in the %1$s or %2$s roles.', 'wpsso' ),
						_x( 'Administrator', 'user role', 'wpsso' ), _x( 'Editor', 'user role', 'wpsso' ) );

					break;

				case 'tooltip-site_pub_schema_type':	// WebSite Publisher Type.

					$text .= __( 'Select a Schema type for the publisher of content for this website.', 'wpsso' ) . ' ';

					$text .= __( 'Traditionally, the Schema Organization type is selected for business websites, where-as the Schema Person type is selected for personal websites.', 'wpsso' );

					break;

				case 'tooltip-site-use':

					$text = __( 'Individual sites/blogs may use this value as a default (when the plugin is first activated), if the current site/blog option value is blank, or force every site/blog to use this specific value.', 'wpsso' );

					break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_site', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-site' switch.

			return $text;
		}
	}
}
