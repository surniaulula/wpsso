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

if ( ! class_exists( 'WpssoMessagesTooltipSchema' ) ) {

	/**
	 * Instantiated by WpssoMessagesTooltip->get() only when needed.
	 */
	class WpssoMessagesTooltipSchema extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-schema_1x1_img_size':	// Schema 1:1 (Google) Image Size.
				case 'tooltip-schema_4x3_img_size':	// Schema 4:3 (Google) Image Size.
				case 'tooltip-schema_16x9_img_size':	// Schema 16:9 (Google) Image Size.

					if ( preg_match( '/^tooltip-(schema_([0-9]+)x([0-9]+))_img_size$/', $msg_key, $matches ) ) {

						$opt_pre      = $matches[ 1 ];
						$ratio_msg    = $matches[ 2 ] . ':' . $matches[ 3 ];
						$def_img_dims = $this->get_def_img_dims( $opt_pre );

						$text = sprintf( __( 'The %1$s image dimensions used for Schema meta tags and JSON-LD markup (the default dimensions are %2$s).', 'wpsso' ), $ratio_msg, $def_img_dims ) . ' ';

						$text .= sprintf( __( 'The minimum image width required by Google is %dpx.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ $opt_pre . '_img_width' ] ). ' ';
					}

					break;

				case 'tooltip-schema_thumb_img_size':	// Schema Thumbnail Image Size.

					$def_img_dims = $this->get_def_img_dims( 'thumb' );

					$text = sprintf( __( 'The image dimensions used for the Schema "%1$s" property and the "%2$s" HTML tag (the default dimensions are %3$s).', 'wpsso' ), 'thumbnailUrl', 'meta name thumbnail', $def_img_dims );

					break;

				case 'tooltip-schema_img_max':		// Schema Max. Images to Include.

					$text = __( 'The maximum number of images to include in the Schema main entity markup for the webpage.', 'wpsso' ) . ' ';

					$text .= __( 'Each image will be included in three different sizes for Google (1:1, 4:3, and 16:9).', 'wpsso' ) . ' ';

					$text .= __( 'If you select "0", then no images will be included (not recommended).', 'wpsso' ) . ' ';

					break;

				case 'tooltip-schema_aggr_offers':		// Aggregate Offers by Currency.

					$text = __( 'Aggregate (ie. group) product offers by currency.', 'wpsso' ) . ' ';

					$text .= sprintf( __( 'Note that to be eligible for <a href="%s">price drop appearance in Google search results</a>, product offers cannot be aggregated.', 'wpsso' ), 'https://developers.google.com/search/docs/data-types/product#price-drop' );

		 			break;

				case 'tooltip-schema_add_text_prop':		// Add Text / Article Body Properties.

					$text = __( 'Add a "text" or "articleBody" property to Schema CreativeWork markup with the complete textual content of the post / page.', 'wpsso' );

				 	break;

				case 'tooltip-schema_text_max_len':		// Text / Article Body Max. Length.

					$text = sprintf( __( 'The maximum length of the Schema CreativeWork "text" or "articleBody" property values (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'schema_text_max_len' ) );

		 			break;

				case 'tooltip-schema_desc_max_len':		// Schema Description Max. Length.

					$text = sprintf( __( 'The maximum length for the Schema description value (the default is %d characters).', 'wpsso' ), $this->p->opt->get_defaults( 'schema_desc_max_len' ) ) . ' ';

					$text .= sprintf( __( 'The maximum length must be at least %d characters or more.', 'wpsso' ), $this->p->cf[ 'head' ][ 'limit_min' ][ 'schema_desc_len' ] );

					break;

				/**
				 * SSO > Advanced Settings > Document Types > Schema tab.
				 */
				case 'tooltip-schema_type_for_home_page':	// Type for Page Homepage.

					$def_type = $this->p->opt->get_defaults( 'schema_type_for_home_page' );

					$text = sprintf( __( 'Select the %s type for a static front page.', 'wpsso' ), 'Schema' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

					break;

				case 'tooltip-schema_type_for_home_posts':	// Type for Posts Homepage.

					$def_type = $this->p->opt->get_defaults( 'schema_type_for_home_posts' );

					$text = sprintf( __( 'Select the %s type for a blog (non-static) front page.', 'wpsso' ), 'Schema' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

					break;

				case 'tooltip-schema_type_for_user_page':	// Type for User / Author.

					$def_type = $this->p->opt->get_defaults( 'schema_type_for_user_page' );

					$text = sprintf( __( 'Select the %s type for user / author pages.', 'wpsso' ), 'Schema' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

					break;

				case 'tooltip-schema_type_for_search_page':	// Type for Search Results.

					$def_type = $this->p->opt->get_defaults( 'schema_type_for_search_page' );

					$text = sprintf( __( 'Select the %s type for search results pages.', 'wpsso' ), 'Schema' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

					break;

				case 'tooltip-schema_type_for_archive_page':	// Type for Other Archive.

					$def_type = $this->p->opt->get_defaults( 'schema_type_for_archive_page' );

					$text = sprintf( __( 'Select the %s type for other archive pages (example: date-based archive pages).', 'wpsso' ), 'Schema' ) . ' ';

					$text .= sprintf( __( 'The default %1$s type is "%2$s".', 'wpsso' ), 'Schema', $def_type  );

					break;

				case 'tooltip-schema_type_for_ptn':	// Type by Post Type.

					$text = sprintf( __( 'Select the %s type for each WordPress post type.', 'wpsso' ), 'Schema' );

					break;

				case 'tooltip-schema_type_for_ttn':	// Type by Taxonomy.

					$text = __( 'Select the Schema type for each WordPress taxonomy.', 'wpsso' );


					break;

				/**
				 * SSO > Advanced Settings > Schema Defaults metabox.
				 */
				case 'tooltip-schema_def_family_friendly':		// Default Family Friendly.

					$text = __( 'Select a default family friendly value for the Schema CreativeWork type and/or its sub-types (Article, BlogPosting, WebPage, etc).', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_pub_org_id':			// Default Publisher Org.

					$text = __( 'Select a default publisher organization for the Schema CreativeWork type and/or its sub-types (Article, BlogPosting, WebPage, etc).', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_pub_person_id':		// Default Publisher Person.

					$text = __( 'Select a default publisher person for the Schema CreativeWork type and/or its sub-types (Article, BlogPosting, WebPage, etc).', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_prov_org_id':			// Default Service Prov. Org.
				case 'tooltip-schema_def_prov_person_id':		// Default Service Prov. Person.

					$text = __( 'Select a default service provider, service operator or service performer (example: "Netflix").', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_event_location_id':		// Default Physical Venue.

					$text = __( 'Select a default venue for the Schema Event type.', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_event_organizer_org_id':	// Default Organizer Org.

					$text = __( 'Select a default organizer (organization) for the Schema Event type.', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_event_organizer_person_id':	// Default Organizer Person.

					$text = __( 'Select a default organizer (person) for the Schema Event type.', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_event_performer_org_id':	// Default Performer Org.

					$text = __( 'Select a default performer (organization) for the Schema Event type.', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_event_performer_person_id':	// Default Performer Person.

					$text = __( 'Select a default performer (person) for the Schema Event type.', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_job_hiring_org_id':		// Default Job Hiring Org.

					$text = __( 'Select a default organization for the Schema JobPosting hiring organization.', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_job_location_id':		// Default Job Location.

					$text = __( 'Select a default location for the Schema JobPosting job location.', 'wpsso' );

				 	break;

				case 'tooltip-schema_def_job_location_type':		// Default Job Location Type.

					$text = sprintf( __( 'Select a default optional Google approved location type (see <a href="%s">Google\'s Job Posting guidelines</a> for more information).', 'wpsso' ), 'https://developers.google.com/search/docs/data-types/job-postings' );

				 	break;

				case 'tooltip-schema_def_review_item_type':		// Default Subject Webpage Type.

					$text = __( 'Select a default Schema type for the Schema Review subject URL.', 'wpsso' );

				 	break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_schema', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-schema' switch.

			return $text;
		}
	}
}
