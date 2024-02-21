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

if ( ! class_exists( 'WpssoMessagesTooltipSite' ) ) {

	/*
	 * Instantiated by WpssoMessagesTooltip->get() only when needed.
	 */
	class WpssoMessagesTooltipSite extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-site_name':	// Site Name.

					$text = __( 'The website name used in meta tags and Schema markup.', 'wpsso' );

					break;

				case 'tooltip-site_name_alt':	// Site Alternate Name.

					$text = __( 'An optional alternate name that you want Google to consider for your website.', 'wpsso' );

					break;

				case 'tooltip-site_desc':	// Site Description.

					$text = __( 'A short description for the home page tagline and the blog (non-static) front page description.', 'wpsso' );

					break;

				case 'tooltip-site_home_url':	// Site Home URL.

					$text = __( 'The website home URL used in meta tags and Schema markup.', 'wpsso' );

					break;

				case 'tooltip-site_pub_schema_type':	// Site Publisher Type.

					$text .= __( 'Select a Schema type for the publisher of content for this website.', 'wpsso' ) . ' ';

					$text .= __( 'Traditionally, the Schema Organization type is selected for business websites, where-as the Schema Person type is selected for personal websites.', 'wpsso' );

					break;

				case 'tooltip-site_pub_person_id':	// Site Publisher Person.

					$text = __( 'Select a user for the Schema Person publisher markup.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'The available Person list includes all users in the %1$s or %2$s roles.', 'wpsso' ),
						_x( 'Administrator', 'user role', 'wpsso' ), _x( 'Editor', 'user role', 'wpsso' ) );

					break;

				/*
				 * This help text is also used for the WPSSO OPM add-on 'tooltip-meta-org_logo_url' help text.
				 */
				case 'tooltip-site_org_logo_url':	// Organization Logo URL.

					$min_width    = $this->p->cf[ 'head' ][ 'limit_min' ][ 'org_logo_width' ];
					$min_height   = $this->p->cf[ 'head' ][ 'limit_min' ][ 'org_logo_height' ];
					$minimum_dims = $min_width . 'x' . $min_height . 'px';

					$text = __( 'A URL for this organization\'s logo image that Google can show in search results and its Knowledge Graph.', 'wpsso' );

					// translators: %s is 600x60px.
					$text .= sprintf( __( 'The image must be at least %s for Google, but preferably 1200x1200px or more.', 'wpsso' ), $minimum_dims );

					break;

				/*
				 * This help text is also used for the WPSSO OPM add-on 'tooltip-meta-org_banner_url' help text.
				 */
				case 'tooltip-site_org_banner_url':	// Organization Banner URL.

					$min_width     = $this->p->cf[ 'head' ][ 'limit' ][ 'org_banner_width' ];
					$min_height    = $this->p->cf[ 'head' ][ 'limit' ][ 'org_banner_height' ];
					$required_dims = $min_width . 'x' . $min_height . 'px';

					// translators: %s is 600x60px.
					$text = sprintf( __( 'A URL for this organization\'s banner image <strong>measuring exactly %s</strong>, that Google News can show for Schema Article type content from this publisher.', 'wpsso' ), $required_dims );

					break;

				case 'tooltip-site_org_place_id':	// Organization Location.

					$text = __( 'Select an optional location for this organization.', 'wpsso' );

					$text .= $this->maybe_ext_required( 'opm' );

					break;

				case 'tooltip-site_org_schema_type':	// Organization Schema Type.

					$text .= __( 'The site organization must be Schema Organization.', 'wpsso' );

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
