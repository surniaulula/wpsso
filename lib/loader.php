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

		private function modules( $has_action = false ) {

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

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'load modules' );	// begin timer
				if ( $has_action )
					$this->p->debug->log( 'loading module only for action: '.$has_action );
			}

			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				$type = $this->p->check->aop( $this->p->cf['lca'], true, $this->p->is_avail['aop'] ) &&
						$this->p->check->aop( $ext, true, -1 ) === -1 ?
							'pro' : 'gpl';
				if ( ! isset( $info['lib'][$type] ) )
					continue;
				foreach ( $info['lib'][$type] as $sub => $libs ) {
					if ( $sub === 'admin' && ! is_admin() )	// load admin sub-folder only in back-end
						continue;
					foreach ( $libs as $id_key => $label ) {
						/* 
						 * Example:
						 *	'article' => 'Item Type Article',
						 *	'article#news:no_load' => 'Item Type NewsArticle',
						 *	'article#tech:no_load' => 'Item Type TechArticle',
						 */
						list( $id, $stub, $action ) = SucomUtil::get_lib_stub_action( $id_key );

						if ( $this->p->is_avail[$sub][$id] ) {

							// compare $action from lib id with $has_action method argument
							// this is usually / almost always a false === false comparison
							if ( $action !== $has_action ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'ignoring '.$ext.' '.$type.'/'.$sub.'/'.$id_key );
								continue;
							}

							$classname = apply_filters( $ext.'_load_lib', false, "$type/$sub/$id" );

							if ( is_string( $classname ) ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'loading '.$ext.' '.$type.'/'.$sub.'/'.$id_key.': '.$label );
								if ( class_exists( $classname ) ) {
									if ( $ext === $this->p->cf['lca'] ) {
										if ( ! isset( $this->p->m[$sub][$id] ) )
											$this->p->m[$sub][$id] = new $classname( $this->p );
										elseif ( $this->p->debug->enabled )
											$this->p->debug->log( 'module ['.$sub.']['.$id.'] already defined' );
									} elseif ( ! isset( $this->p->m_ext[$ext][$sub][$id] ) ) {
										$this->p->m_ext[$ext][$sub][$id] = new $classname( $this->p );
									} elseif ( $this->p->debug->enabled )
										$this->p->debug->log( 'module ['.$ext.']['.$sub.']['.$id.'] already defined' );
								} elseif ( $this->p->debug->enabled )
									$this->p->debug->log( 'classname '.$classname.' does not exist' );
							} elseif ( $this->p->debug->enabled )
								$this->p->debug->log( 'no classname for '.$ext.' '.$type.'/'.$sub.'/'.$id_key );
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
