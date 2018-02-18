<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
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
				} else {
					$this->p->debug->log( 'no action provided to filter module keys' );
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

					$log_prefix = 'loading '.$ext.' '.$type.'/'.$sub.': ';

					if ( $sub === 'admin' ) {
						if ( ! is_admin() ) {	// load admin sub-folder only in back-end
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $log_prefix.'ignored - not in admin back-end' );
							}
							continue;
						} elseif ( $type === 'gpl' && ! empty( $this->p->options['plugin_hide_pro'] ) ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $log_prefix.'ignored - pro features hidden' );
							}
							continue;
						}
					}

					foreach ( $libs as $lib_name => $lib_label ) {

						/**
						 * Example:
						 *	'article' => 'Item Type Article',
						 *	'article#news:no_load' => 'Item Type NewsArticle',
						 *	'article#tech:no_load' => 'Item Type TechArticle',
						 */
						list( $id, $stub, $action ) = SucomUtil::get_lib_stub_action( $lib_name );

						$log_prefix = 'loading '.$ext.' '.$type.'/'.$sub.'/'.$id.': ';

						if ( $this->p->avail[$sub][$id] ) {

							/**
							 * Compare $action from library id with $has_action method argument.
							 * This is usually / almost always a false === false comparison.
							 */
							if ( $action !== $has_action ) {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $log_prefix.'ignored for action '.$has_action );
								}
								continue;
							}

							$lib_path = $type.'/'.$sub.'/'.$id;
							$classname = apply_filters( $ext.'_load_lib', false, $lib_path );

							if ( is_string( $classname ) ) {

								if ( class_exists( $classname ) ) {

									if ( $ext === $this->p->cf['lca'] ) {
										if ( $this->p->debug->enabled ) {
											$this->p->debug->log( $log_prefix.'new library module for '.$classname );
										}
										if ( ! isset( $this->p->m[$sub][$id] ) ) {
											$this->p->m[$sub][$id] = new $classname( $this->p );
										} elseif ( $this->p->debug->enabled ) {
											$this->p->debug->log( $log_prefix.'library module already defined' );
										}
									} elseif ( ! isset( $this->p->m_ext[$ext][$sub][$id] ) ) {
										$this->p->m_ext[$ext][$sub][$id] = new $classname( $this->p );
									} elseif ( $this->p->debug->enabled ) {
										$this->p->debug->log( $log_prefix.'library ext module already defined' );
									}

								} else {

									// translators: %s is the short plugin name, %2$s is the PHP class name
									trigger_error( sprintf( __( '%1$s error: library class "%2$s" is missing.', 'wpsso' ),
										$info['short'], $classname ), E_USER_ERROR );

									if ( $this->p->debug->enabled ) {
										$this->p->debug->log( $log_prefix.'library class '.$classname.' is missing' );
									}
								}
							} else {

								// translators: %s is the short plugin name, %2$s is the PHP library file path
								trigger_error( sprintf( __( '%1$s error: "%2$s" library file not found.', 'wpsso' ),
									$info['short'], $lib_path ), E_USER_ERROR );

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $log_prefix.$lib_path.' library file not found' );
								}
							}

						} elseif ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_prefix.'avail is false' );
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
