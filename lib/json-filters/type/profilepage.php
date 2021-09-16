<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypeProfilePage' ) ) {

	class WpssoJsonFiltersTypeProfilePage {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_profilepage' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_profilepage( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$prop_type_ids = array( 'mentions' => false );	// Allow any post schema type to be added.

			WpssoSchema::add_posts_data( $json_data, $mod, $mt_og, $page_type_id, $is_main, $prop_type_ids );

			return $json_data;
		}
	}
}
