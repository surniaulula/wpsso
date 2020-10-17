<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
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
					'schema_scripts' => 3,
				), $prio = 1000 );
			}
		}

		public function filter_schema_scripts( array $schema_scripts, array $mod, array $mt_og ) {

			if ( ! apply_filters( $this->p->lca . '_add_schema_noscript_aggregaterating', true ) ) {

				return $schema_scripts;
			}

			if ( empty( $mt_og[ 'og:type' ] ) ) {	// Just in case.

				return $mt_og;
			}

			$og_type = $mt_og[ 'og:type' ];

			/**
			 * Aggregate rating needs at least one rating or review count.
			 */
			if ( empty( $mt_og[ $og_type . ':rating:average' ] ) ||
				( empty( $mt_og[ $og_type . ':rating:count' ] ) && empty( $mt_og[ $og_type . ':review:count' ] ) ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: rating average and/or counts are empty' );
				}

				return $schema_scripts;
			}

			$schema_scripts[] = array( '<noscript itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">' . "\n" );

			if ( ! empty( $mt_og[ $og_type . ':rating:average' ] ) ) {

				$this->p->head->add_mt_singles( $schema_scripts, 'meta', 'itemprop', 'aggregaterating.ratingvalue', $mt_og[ $og_type . ':rating:average' ] );
			}

			if ( ! empty( $mt_og[ $og_type . ':rating:count' ] ) ) {

				$this->p->head->add_mt_singles( $schema_scripts, 'meta', 'itemprop', 'aggregaterating.ratingcount', $mt_og[ $og_type . ':rating:count' ] );
			}

			if ( ! empty( $mt_og[ $og_type . ':rating:worst' ] ) ) {

				$this->p->head->add_mt_singles( $schema_scripts, 'meta', 'itemprop', 'aggregaterating.worstrating', $mt_og[ $og_type . ':rating:worst' ] );
			}

			if ( ! empty( $mt_og[ $og_type . ':rating:best' ] ) ) {

				$this->p->head->add_mt_singles( $schema_scripts, 'meta', 'itemprop', 'aggregaterating.bestrating', $mt_og[ $og_type . ':rating:best' ] );
			}

			if ( ! empty( $mt_og[ $og_type . ':review:count' ] ) ) {

				$this->p->head->add_mt_singles( $schema_scripts, 'meta', 'itemprop', 'aggregaterating.reviewcount', $mt_og[ $og_type . ':review:count' ] );
			}

			$schema_scripts[] = array( '</noscript>' . "\n" );

			return $schema_scripts;
		}
	}
}
