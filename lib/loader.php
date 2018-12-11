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

				/**
				 * Save time on known admin pages we don't modify.
				 */
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

				$this->p->debug->mark( 'load modules' );	// Begin timer.

				if ( $has_action ) {
					$this->p->debug->log( 'loading modules for action ' . $has_action );
				} else {
					$this->p->debug->log( 'no action provided to filter module keys' );
				}
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$type = $this->p->check->pp( $this->p->lca, true, $this->p->avail[ '*' ][ 'p_dir' ] ) &&
					$this->p->check->pp( $ext, true, WPSSO_UNDEF ) === WPSSO_UNDEF ? 'pro' : 'gpl';

				if ( ! isset( $info[ 'lib' ][ $type ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $ext . ' lib/' . $type . ' not defined' );
					}

					continue;
				}

				foreach ( $info[ 'lib' ][ $type ] as $sub => $libs ) {

					$log_prefix = 'loading ' . $ext . ' ' . $type . '/' . $sub . ': ';

					if ( $sub === 'admin' ) {

						if ( ! is_admin() ) {	// load admin sub-folder only in back-end

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $log_prefix . 'ignored - not in admin back-end' );
							}

							continue;

						} elseif ( $type === 'gpl' && ! empty( $this->p->options[ 'plugin_hide_pro' ] ) ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( $log_prefix . 'ignored - pro features hidden' );
							}

							continue;
						}
					}

					foreach ( $libs as $lib_name => $lib_label ) {

						/**
						 * Example:
						 *	'article'              => 'Schema Type Article',
						 *	'article#news:no_load' => 'Schema Type NewsArticle',
						 *	'article#tech:no_load' => 'Schema Type TechArticle',
						 */
						list( $id, $stub, $action ) = SucomUtil::get_lib_stub_action( $lib_name );

						$log_prefix = 'loading ' . $ext . ' ' . $type . '/' . $sub . '/' . $id . ': ';

						if ( $this->p->avail[$sub][$id] ) {

							/**
							 * Compare $action from library id with $has_action method argument.
							 * This is usually / almost always a false === false comparison.
							 */
							if ( $action !== $has_action ) {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $log_prefix . 'ignored for action ' . $has_action );
								}
								continue;
							}

							$lib_path  = $type . '/' . $sub . '/' . $id;
							$classname = apply_filters( $ext . '_load_lib', false, $lib_path );

							if ( is_string( $classname ) ) {

								if ( class_exists( $classname ) ) {

									/**
									 * Loaded module objects from core plugin.
									 */
									if ( $ext === $this->p->lca ) {

										if ( $this->p->debug->enabled ) {
											$this->p->debug->log( $log_prefix . 'new library module for ' . $classname );
										}

										if ( ! isset( $this->p->m[$sub][$id] ) ) {
											$this->p->m[$sub][$id] = new $classname( $this->p );
										} elseif ( $this->p->debug->enabled ) {
											$this->p->debug->log( $log_prefix . 'library module already defined' );
										}

									/**
									 * Loaded module objects from extensions / add-ons.
									 */
									} elseif ( ! isset( $this->p->m_ext[$ext][$sub][$id] ) ) {
										$this->p->m_ext[$ext][$sub][$id] = new $classname( $this->p );
									} elseif ( $this->p->debug->enabled ) {
										$this->p->debug->log( $log_prefix . 'library ext module already defined' );
									}

								} else {

									if ( $this->p->debug->enabled ) {
										$this->p->debug->log( $log_prefix . 'library class "' . $classname . '" is missing' );
									}

									if ( is_admin() && is_object( $this->p->notice ) ) {

										// translators: %1$s is the PHP library path, %2$s is the PHP library class name
										$this->p->notice->err( sprintf( __( 'Error loading %1$s: Library class "%2$s" is missing.',
											'wpsso' ), $lib_path, $classname ) );
									}

									// translators: %s is the PHP library class name
									$error_msg = sprintf( __( 'Library class "%s" is missing.',
										'wpsso' ), $classname );

									// translators: %s is the short plugin name
									$error_pre = sprintf( __( '%s warning:', 'wpsso' ), $info[ 'short' ] );

									SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
								}

							} else {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $log_prefix . 'library class name is not available' );
								}

								$suffix_msg = __( 'The installed plugin is incomplete or the web server cannot access the library file.',
									'wpsso' );

								if ( is_admin() && is_object( $this->p->notice ) ) {

									// translators: %1$s is the short plugin name, %2$s is the PHP library path
									$this->p->notice->err( sprintf( __( '%1$s library class name for "%2$s" is not available.',
										'wpsso' ), $info[ 'short' ], $lib_path ) . ' ' . $suffix_msg );
								}

								// translators: %s is the PHP library path
								$error_msg = sprintf( __( 'Library class name for "%s" is not available.',
									'wpsso' ), $lib_path ) . ' ' . $suffix_msg;

								// translators: %s is the short plugin name
								$error_pre = sprintf( __( '%s warning:', 'wpsso' ), $info[ 'short' ] );

								SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
							}

						} elseif ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_prefix . 'avail is false' );
						}
					}
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'load modules' );	// End timer.
			}
		}
	}
}
