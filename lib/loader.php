<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoLoader' ) ) {

	class WpssoLoader {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$debug_enabled = $this->p->debug->is_on();	// optimize debug logging
			if ( $debug_enabled )
				$this->p->debug->mark( 'load modules' );
			$this->modules();
			if ( $debug_enabled )
				$this->p->debug->mark( 'load modules' );
		}

		private function modules() {
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				$type = $this->p->check->aop( $lca ) ? 'pro' : 'gpl';
				if ( ! isset( $info['lib'][$type] ) )
					continue;
				foreach ( $info['lib'][$type] as $sub => $lib ) {
					if ( $sub === 'admin' && ! is_admin() )
						continue;
					foreach ( $lib as $id => $name ) {
						if ( $this->p->is_avail[$sub][$id] ) {
							$classname = apply_filters( $lca.'_load_lib', false, "$type/$sub/$id" );
							if ( $classname !== false && class_exists( $classname ) ) {
								$this->p->mods[$sub][$id] = new $classname( $this->p );
							}
						}
					}
				}
			}
		}
	}
}

?>
