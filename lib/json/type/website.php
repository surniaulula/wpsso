<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeWebsite' ) ) {

	class WpssoJsonTypeWebsite {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_website' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_website( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = WpssoSchema::get_schema_type_context( 'https://schema.org/WebSite', array(
				'url' => SucomUtilWP::get_home_url( $this->p->options, $mod ),
			) );

			foreach ( array(
				'name'          => SucomUtilWP::get_site_name( $this->p->options, $mod ),
				'alternateName' => SucomUtilWP::get_site_name_alt( $this->p->options, $mod ),
				'description'   => SucomUtilWP::get_site_description( $this->p->options, $mod ),
			) as $key => $value ) {

				if ( ! empty( $value ) ) {

					$json_ret[ $key ] = $value;
				}
			}

			/*
			 * Potential Action (SearchAction, OrderAction, etc.) for Google's Sitelinks search box.
			 *
			 * Hook the 'wpsso_json_ld_search_url' filter and return false if you wish to disable / skip the Potential
			 * Action property.
			 *
			 * The 'wpsso_json_prop_https_schema_org_potentialaction' filter may already be applied by the JSON data
			 * filters, so do not re-apply it here.
			 *
			 * Note: Add this markup only to the homepage (aka WebSite markup), not to any other pages.
			 *
			 * See https://developers.google.com/search/docs/appearance/structured-data/sitelinks-searchbox.
			 */
			$search_url = SucomUtil::esc_url_encode( get_bloginfo( 'url' ) ) . '?s={search_term_string}';

			$search_url = apply_filters( 'wpsso_json_ld_search_url', $search_url );

			if ( ! empty( $search_url ) ) {

				$json_ret[ 'potentialAction' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/SearchAction', array(
					'target'      => $search_url,
					'query-input' => 'required name=search_term_string',
				) );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'skipping search action: search url is empty' );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
