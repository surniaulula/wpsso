<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplUtilPost' ) && class_exists( 'WpssoPost' ) ) {

	class WpssoGplUtilPost extends WpssoPost {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			$this->add_actions();
		}

		/*
		 * The Free version does not have any code to get / save meta data, nor
		 * does it have any video API modules, so optimize and disable some methods 
		 * that wouldn't return anything anyway. ;-)
		 */
		public function get_options_multi( $mod_id, $idx = false, $filter_options = true ) {
			return $this->not_implemented( __METHOD__, null );
		}

		public function get_md_image( $num, $size_name, array $mod, $check_dupes = true, $force_regen = false, $md_pre = 'og', $mt_pre = 'og' ) {
			return $this->not_implemented( __METHOD__, array() );
		}

		public function get_og_image( $num, $size_name, $mod_id, $check_dupes = true, $force_regen = false, $md_pre = 'og' ) {
			return $this->not_implemented( __METHOD__, array() );
		}

		public function get_og_video( $num, $mod_id, $check_dupes = false, $md_pre = 'og', $mt_pre = 'og' ) {
			return $this->not_implemented( __METHOD__, array() );
		}
	}
}

?>
