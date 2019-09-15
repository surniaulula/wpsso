<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSchemaNoScript' ) ) {

	class WpssoSchemaNoScript {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( apply_filters( $this->p->lca . '_add_schema_noscript_array', true ) ) {

				$this->p->util->add_plugin_filters( $this, array( 
					'json_scripts' => 3,
				), $prio = 1000 );
			}
		}

		public function filter_json_scripts( array $json_scripts, array $mod, array $mt_og ) {

			if ( ! apply_filters( $this->p->lca . '_add_schema_noscript_aggregaterating', true ) ) {
				return $json_scripts;
			}

			if ( empty( $mt_og[ 'og:type' ] ) ) {	// Just in case.
				return $mt_og;
			}

			$og_type_id = $mt_og[ 'og:type' ];

			/**
			 * Aggregate rating needs at least one rating or review count.
			 */
			if ( empty( $mt_og[ $og_type_id . ':rating:average' ] ) ||
				( empty( $mt_og[ $og_type_id . ':rating:count' ] ) && empty( $mt_og[ $og_type_id . ':review:count' ] ) ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: rating average and/or counts are empty' );
				}

				return $json_scripts;
			}

			$item_props = array();

			if ( ! empty( $mt_og[ $og_type_id . ':rating:average' ] ) ) {
				$item_props[] = $this->p->head->get_single_mt( 'meta', 'itemprop', 'aggregaterating.ratingValue',
					$mt_og[ $og_type_id . ':rating:average' ], '', $mod );
			}

			if ( ! empty( $mt_og[ $og_type_id . ':rating:count' ] ) ) {
				$item_props[] = $this->p->head->get_single_mt( 'meta', 'itemprop', 'aggregaterating.ratingCount',
					$mt_og[ $og_type_id . ':rating:count' ], '', $mod );
			}

			if ( ! empty( $mt_og[ $og_type_id . ':rating:worst' ] ) ) {
				$item_props[] = $this->p->head->get_single_mt( 'meta', 'itemprop', 'aggregaterating.worstRating',
					$mt_og[ $og_type_id . ':rating:worst' ], '', $mod );
			}

			if ( ! empty( $mt_og[ $og_type_id . ':rating:best' ] ) ) {
				$item_props[] = $this->p->head->get_single_mt( 'meta', 'itemprop', 'aggregaterating.bestRating',
					$mt_og[ $og_type_id . ':rating:best' ], '', $mod );
			}

			if ( ! empty( $mt_og[ $og_type_id . ':review:count' ] ) ) {
				$item_props[] = $this->p->head->get_single_mt( 'meta', 'itemprop', 'aggregaterating.reviewCount',
					$mt_og[ $og_type_id . ':review:count' ], '', $mod );
			}

			$json_scripts[] = array( '<noscript itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">' . "\n" );

			foreach ( $item_props as $arr ) {
				$json_scripts[] = reset( $arr );
			}

			$json_scripts[] = array( '</noscript>' . "\n" );

			return $json_scripts;
		}
	}
}
