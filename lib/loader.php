<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoLoader' ) ) {

	class WpssoLoader {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->mod_load();
		}

		private function mod_load( $has_action = false ) {

			if ( is_admin() ) {
				// save time on known admin pages we don't modify
				switch ( basename( $_SERVER['PHP_SELF'] ) ) {
					case 'index.php':		// Dashboard
					case 'edit-comments.php':	// Comments
					case 'themes.php':		// Appearance
					case 'plugins.php':		// Plugins
					case 'tools.php':		// Tools
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'no modules required for current page' );
						}
						return;
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'load modules' );	// begin timer
				if ( $has_action ) {
					$this->p->debug->log( 'loading modules for action '.$has_action );
				}
			}

			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				$type = $this->p->check->aop( $this->p->cf['lca'], true, $this->p->avail['*']['p_dir'] ) &&
					$this->p->check->aop( $ext, true, WPSSO_UNDEF_INT ) === WPSSO_UNDEF_INT ? 'pro' : 'gpl';
				if ( ! isset( $info['lib'][$type] ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $ext.' lib/'.$type.' not defined' );
					}
					continue;
				}
				foreach ( $info['lib'][$type] as $sub => $libs ) {
					$log_pre = 'loading '.$ext.' '.$type.'/'.$sub.': ';
					if ( $sub === 'admin' ) {
						if ( ! is_admin() ) {	// load admin sub-folder only in back-end
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $log_pre.'ignored - not in admin back-end' );
							}
							continue;
						} elseif ( $type === 'gpl' && ! empty( $this->p->options['plugin_hide_pro'] ) ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $log_pre.'ignored - pro features hidden' );
							}
							continue;
						}
					}
					foreach ( $libs as $id_key => $label ) {
						/*
						 * Example:
						 *	'article' => 'Item Type Article',
						 *	'article#news:no_load' => 'Item Type NewsArticle',
						 *	'article#tech:no_load' => 'Item Type TechArticle',
						 */
						list( $id, $stub, $action ) = SucomUtil::get_lib_stub_action( $id_key );
						$log_pre = 'loading '.$ext.' '.$type.'/'.$sub.'/'.$id.': ';
						if ( $this->p->avail[$sub][$id] ) {
							// compare $action from lib id with $has_action method argument
							// this is usually / almost always a false === false comparison
							if ( $action !== $has_action ) {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $log_pre.'ignored for action '.$has_action );
								}
								continue;
							}
							$classname = apply_filters( $ext.'_load_lib', false, "$type/$sub/$id" );
							if ( is_string( $classname ) ) {
								if ( class_exists( $classname ) ) {
									if ( $this->p->debug->enabled )
										$this->p->debug->log( $log_pre.'class '.$classname.' for '.$label );
									if ( $ext === $this->p->cf['lca'] ) {
										if ( ! isset( $this->p->m[$sub][$id] ) ) {
											$this->p->m[$sub][$id] = new $classname( $this->p );
										} elseif ( $this->p->debug->enabled ) {
											$this->p->debug->log( $log_pre.'module already defined' );
										}
									} elseif ( ! isset( $this->p->m_ext[$ext][$sub][$id] ) ) {
										$this->p->m_ext[$ext][$sub][$id] = new $classname( $this->p );
									} elseif ( $this->p->debug->enabled )
										$this->p->debug->log( $log_pre.'extension module already defined' );
								} elseif ( $this->p->debug->enabled ) {
									$this->p->debug->log( $log_pre.'class '.$classname.' does not exist' );
								}
							} elseif ( $this->p->debug->enabled ) {
								$this->p->debug->log( $log_pre.'invalid classname from filter' );
							}
						} elseif ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre.'avail is false' );
						}
					}
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'load modules' );	// end timer
			}
		}
	}
}

?>
