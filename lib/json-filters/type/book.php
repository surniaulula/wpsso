<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypeBook' ) ) {

	class WpssoJsonFiltersTypeBook {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$max_int = SucomUtil::get_max_int();

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_book' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_book( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			$og_type = $mt_og[ 'og:type' ];

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array(
				'isbn' => $og_type . ':isbn',
			) );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
