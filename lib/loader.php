<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'SucomPlugin' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/com/plugin.php';
}

if ( ! class_exists( 'WpssoLoader' ) ) {

	class WpssoLoader {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by Wpsso->set_objects() at init priority 10
		 */
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

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'loading integ modules for ' . $ext );
				}

				$this->maybe_load_ext_mods( $ext, $sub = 'integ' );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'loading integ modules' );	// End timer.
			}
		}

		private function load_dist() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'loading dist modules' );	// Begin timer.
			}

			$um_check = $this->p->check->is_um_gt_min();	// Uses a local cache.

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$GLOBALS[ $ext . '_dist' ] = '';
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$sub = $GLOBALS[ $ext . '_dist' ] = 'std' === $GLOBALS[ $ext . '_dist' ] || empty( $info[ 'update_auth' ] ) || ! $um_check ? 'std' :
					( 'pro' === $GLOBALS[ $ext . '_dist' ] || 1 === $this->p->check->pp( $ext, true, WPSSO_UNDEF, true, -1 ) ? 'pro' : 'std' );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'loading dist modules for ' . $ext . ' from ' . $sub );
				}

				$this->maybe_load_ext_mods( $ext, $sub );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'loading dist modules' );	// End timer.
			}
		}

		private function maybe_load_ext_mods( $ext, $sub ) {

			if ( empty( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $sub ] ) ||
				! is_array( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $sub ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no modules found for ' . $ext . '/lib/' . $sub );
				}

				return;
			}

			$ext_base = $this->p->cf[ 'plugin' ][ $ext ][ 'base' ];

			if ( ! SucomPlugin::is_plugin_active( $ext_base ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $ext_base . ' plugin not active or not found' );
				}

				return;
			}

			unset( $ext_base );

			foreach ( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $sub ] as $type => $libs ) {

				$log_prefix = 'loading ' . $ext . '/lib/' . $sub . '/' . $type . ': ';

				foreach ( $libs as $id => $label ) {

					$log_prefix = 'loading ' . $ext . '/lib/' . $sub . '/' . $type . '/' . $id . ': ';

					if ( ! empty( $this->p->avail[ $type ][ $id ] ) ) {

						$lib_path = $sub . '/' . $type . '/' . $id;

						$classname = apply_filters( $ext . '_load_lib', false, $lib_path );

						if ( is_string( $classname ) ) {

							if ( 'none' === $classname ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( $log_prefix . 'skipped' );
								}

								continue;

							} elseif ( class_exists( $classname ) ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( $log_prefix . 'instantiating ' . $classname );
								}

								new $classname( $this->p );

							} else {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( $log_prefix . 'class "' . $classname . '" is missing' );
								}

								if ( is_admin() && is_object( $this->p->notice ) ) {

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

							if ( is_admin() && is_object( $this->p->notice ) ) {

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

		public static function load_plugin_std( $plugin, $sub, $id ) {

			$GLOBALS[ 'wpsso_dist' ] = 'std';

			$classname = apply_filters( 'wpsso_load_lib', false, 'std/' . $sub . '/' . $id );

			if ( is_string( $classname ) && class_exists( $classname ) ) new $classname( $plugin );
		}
	}
}
