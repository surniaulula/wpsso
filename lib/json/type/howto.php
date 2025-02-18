<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeHowTo' ) ) {

	class WpssoJsonTypeHowTo {

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
				'json_data_https_schema_org_howto' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_howto( $json_data, $mod, $mt_og, $page_type_id, $is_main  ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $page_type_id === 'recipe' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: page_type_id is recipe (avoiding conflicting properties)' );
				}

				return $json_data;
			}

			$json_ret = array();
			$md_opts  = array();

			WpssoSchema::add_type_opts_md_pad( $md_opts, $mod );

			/*
			 * See https://schema.org/yield.
			 */
			if ( ! empty( $md_opts[ 'schema_howto_yield' ] ) ) {

				$json_ret[ 'yield' ] = (string) $md_opts[ 'schema_howto_yield' ];
			}

			/*
			 * See https://schema.org/prepTime.
			 * See https://schema.org/totalTime.
			 */
			WpssoSchema::add_data_time_from_assoc( $json_ret, $md_opts, array(
				'prepTime'  => 'schema_howto_prep',
				'totalTime' => 'schema_howto_total',
			) );

			/*
			 * See https://schema.org/step.
			 */
			WpssoSchema::add_howto_steps_data( $json_ret, $mod, $md_opts, $opt_pre = 'schema_howto_step', $prop_name = 'step' );

			/*
			 * See https://schema.org/supply.
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^schema_howto_supply_[0-9]+$/', $md_opts ) as $md_key => $md_val ) {

				$json_ret[ 'supply' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/HowToSupply', array(
					'name' => $md_val,
				) );
			}

			/*
			 * See https://schema.org/tool.
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^schema_howto_tool_[0-9]+$/', $md_opts ) as $md_key => $md_val ) {

				$json_ret[ 'tool' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/HowToTool', array(
					'name' => $md_val,
				) );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
