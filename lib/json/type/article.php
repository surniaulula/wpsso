<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeArticle' ) ) {

	class WpssoJsonTypeArticle {

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
				'json_data_https_schema_org_article' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_article( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			/**
			 * See https://schema.org/articleSection.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array(
				'articleSection' => 'article:section',
			) );

			if ( ! empty( $mt_og[ 'article:reading_mins' ] ) ) {

				$json_ret[ 'timeRequired' ] = 'PT' . $mt_og[ 'article:reading_mins' ] . 'M';
			}

			/**
			 * See https://schema.org/articleBody.
			 */
			if ( isset( $json_data[ 'text' ] ) ) {

				$json_ret[ 'articleBody' ] = $json_data[ 'text' ];

				unset( $json_data[ 'text' ] );
			}

			/**
			 * See https://schema.org/speakable.
			 */
			if ( ! empty( $this->p->options[ 'plugin_speakable_css_csv' ] ) ) {

				$speakable_css_sel = SucomUtil::explode_csv( $this->p->options[ 'plugin_speakable_css_csv' ] );

				if ( ! empty( $speakable_css_sel ) ) {

					$json_ret[ 'speakable' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/SpeakableSpecification', array(
						'cssSelector' => $speakable_css_sel ) );
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
