<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoLoader' ) ) {

	class WpssoLoader {

		private $p;

		public function __construct( &$plugin, $activate = false ) {
			$this->p =& $plugin;
			$this->modules();
		}

		private function modules() {

			if ( is_admin() ) {
				// save time on known admin pages we don't modify
				switch ( basename( $_SERVER['PHP_SELF'] ) ) {
					case 'index.php':		// Dashboard
					case 'upload.php':		// Media
					case 'edit-comments.php':	// Comments
					case 'themes.php':		// Appearance
					case 'plugins.php':		// Plugins
					case 'tools.php':		// Tools
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'no modules required for current page' );
						return;
				}
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'load modules' );	// begin timer

			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				$type = $this->p->is_avail['aop'] &&
						$this->p->is_avail['util']['um'] &&
							$this->p->check->aop( $lca, true, -1 ) === -1 ?
								'pro' : 'gpl';

				if ( ! isset( $info['lib'][$type] ) )
					continue;

				foreach ( $info['lib'][$type] as $sub => $lib ) {
					// the admin sub-folder gets loaded only in the back-end
					if ( $sub === 'admin' && ! is_admin() )
						continue;

					foreach ( $lib as $id => $name ) {
						if ( $this->p->is_avail[$sub][$id] ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'loading '.$lca.' '.$type.'/'.$sub.'/'.$id.' ('.$name.')' );
							$classname = apply_filters( $lca.'_load_lib', false, "$type/$sub/$id" );
							if ( $classname !== false && class_exists( $classname ) ) {
								if ( $lca === $this->p->cf['lca'] )
									$this->p->m[$sub][$id] = new $classname( $this->p );
								else $this->p->m_ext[$lca][$sub][$id] = new $classname( $this->p );
							}
						}
					}
				}
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'load modules' );	// end timer
		}
	}
}

?>
