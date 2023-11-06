<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoLoader' ) ) {

	class WpssoLoader {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->load_integ();

			$this->load_dist();
		}

		private function load_integ() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'loading integ modules' );	// Begin timer.
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$mod_dir = 'integ';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'loading integ modules for ' . $ext );
				}

				$this->load_ext_mods( $ext, $mod_dir );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'loading integ modules' );	// End timer.
			}
		}

		private function load_dist() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'loading dist modules' );	// Begin timer.
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$mod_dir = ! empty( $info[ 'update_auth' ] ) && 1 === $this->p->check->pp( $ext, true, WPSSO_UNDEF, true, -1 ) ? 'pro' : 'std';

				$GLOBALS[ $ext . '_pkg_' . $mod_dir ] = true;

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'loading dist modules for ' . $ext . ' from ' . $mod_dir );
				}

				$this->load_ext_mods( $ext, $mod_dir );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'loading dist modules' );	// End timer.
			}
		}

		private function load_ext_mods( $ext, $mod_dir ) {

			if ( empty( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $mod_dir ] ) ) {	// Just in case.

				return;

			} elseif ( ! is_array( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $mod_dir ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $ext . ' lib/' . $mod_dir . ' not an array' );
				}

				return;
			}

			$is_admin = is_admin();

			foreach ( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $mod_dir ] as $sub_dir => $libs ) {

				$log_prefix = 'loading ' . $ext . ' ' . $mod_dir . '/' . $sub_dir . ': ';

				foreach ( $libs as $id => $label ) {

					$log_prefix = 'loading ' . $ext . ' ' . $mod_dir . '/' . $sub_dir . '/' . $id . ': ';

					/*
					 * Check if the resource (active plugin or enabled option) is available.
					 */
					if ( ! empty( $this->p->avail[ $sub_dir ][ $id ] ) ) {

						$lib_path = $mod_dir . '/' . $sub_dir . '/' . $id;

						$classname = apply_filters( $ext . '_load_lib', false, $lib_path );

						if ( is_string( $classname ) ) {

							if ( 'none' === $classname ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( $log_prefix . 'skipped' );
								}

								continue;

							} elseif ( class_exists( $classname ) ) {

								/*
								 * Loaded module objects from core plugin.
								 */
								if ( $ext === $this->p->id ) {

									if ( $this->p->debug->enabled ) {

										$this->p->debug->log( $log_prefix . 'new library module for ' . $classname );
									}

									if ( ! isset( $this->p->m[ $sub_dir ][ $id ] ) ) {

										$this->p->m[ $sub_dir ][ $id ] = new $classname( $this->p );

									} elseif ( $this->p->debug->enabled ) {

										$this->p->debug->log( $log_prefix . 'library module already defined' );
									}

								/*
								 * Loaded module objects from extensions / add-ons.
								 */
								} elseif ( ! isset( $this->p->m_ext[ $ext ][ $sub_dir ][ $id ] ) ) {

									$this->p->m_ext[ $ext ][ $sub_dir ][ $id ] = new $classname( $this->p );

								} elseif ( $this->p->debug->enabled ) {

									$this->p->debug->log( $log_prefix . 'library ext module already defined' );
								}

							} else {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( $log_prefix . 'library class "' . $classname . '" is missing' );
								}

								if ( $is_admin && is_object( $this->p->notice ) ) {

									// translators: %1$s is the PHP library path, %2$s is the PHP library class name
									$this->p->notice->err( sprintf( __( 'Error loading %1$s: Library class "%2$s" is missing.',
										'wpsso' ), $lib_path, $classname ) );
								}

								$error_pre = sprintf( __( '%s error:', 'wpsso' ), __METHOD__ );

								$error_msg = sprintf( __( 'Library class "%s" is missing.', 'wpsso' ), $classname );

								SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
							}

						} else {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( $log_prefix . 'library class name cannot be determined' );
							}

							$error_pre  = sprintf( __( '%s error:', 'wpsso' ), __METHOD__ );

							$error_msg  = sprintf( __( 'Library class name for "%s" cannot be determined.', 'wpsso' ), $lib_path ) . ' ' .
								__( 'The installed plugin is incomplete or the web server cannot access the required library file.',
									'wpsso' );

							if ( $is_admin && is_object( $this->p->notice ) ) {

								$this->p->notice->err( $error_msg );
							}

							SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( $log_prefix . 'avail is false' );
					}
				}
			}
		}
	}
}
