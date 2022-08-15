<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeBlog' ) ) {

	class WpssoJsonTypeBlog {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_blog' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_blog( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$prop_type_ids = array( 'blogPost' => 'blog.posting' );	// Allow only posts of schema blog.posting type to be added.

			WpssoSchema::add_posts_data( $json_data, $mod, $mt_og, $page_type_id, $is_main, $prop_type_ids );

			return $json_data;
		}
	}
}
