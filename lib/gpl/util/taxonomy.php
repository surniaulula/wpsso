<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplUtilTaxonomy' ) && class_exists( 'WpssoTaxonomy' ) ) {

	class WpssoGplUtilTaxonomy extends WpssoTaxonomy {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			$this->add_actions();
		}

		public function get_meta_image( $num = 0, $size_name = 'thumbnail', $id,
			$check_dupes = true, $force_regen = false, $md_pre = 'og', $mt_pre = 'og' ) {
			return $this->not_implemented( __METHOD__, array() );
		}

		public function get_og_video( $num = 0, $id, $check_dupes = false, $md_pre = 'og', $mt_pre = 'og' ) {
			return $this->not_implemented( __METHOD__, array() );
		}
	}
}

?>
