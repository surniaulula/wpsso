<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2024-2026 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegUtilDuplicatePost' ) ) {

	class WpssoIntegUtilDuplicatePost {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
			
			/*
			 * Prevent Yoast Duplicate Post from creating duplicate entries in the metadata table.
			 */
			add_filter( 'duplicate_post_excludelist_filter', array( $this, 'duplicate_post_excludelist_filter' ), 10, 1 );
		}

		/*
		 * Prevent Yoast Duplicate Post from creating duplicate entries in the metadata table.
		 */
		public function duplicate_post_excludelist_filter( $exclude ) {

			$exclude[] = '_wpsso_head_info_*';
			$exclude[] = '_wpsso_average_rating';
			$exclude[] = '_wpsso_rating_counts';
			$exclude[] = '_wpsso_review_count';

			return $exclude;
		}
	}
}
