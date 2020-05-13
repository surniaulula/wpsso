<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {
	die( 'Do. Or do not. There is no try.' );
}

/**
 * The distribution module loader (ie. lib/std/ or lib/pro/).
 */
if ( ! class_exists( 'WpssoLoader' ) ) {

	class WpssoLoader {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->load_dist_modules();
		}

		private function load_dist_modules() {

			$is_admin = is_admin();

			if ( $is_admin ) {

				/**
				 * Save time on known admin pages we don't modify.
				 */
				switch ( basename( $_SERVER[ 'PHP_SELF' ] ) ) {

					case 'themes.php':		// Appearance

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'no modules required for current page' );
						}

						return;
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'load distribution modules' );	// Begin timer.
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$type_dir = $this->p->check->pp( $ext, true, WPSSO_UNDEF, true, -1 ) === 1 ? 'pro' : 'std';

				if ( ! isset( $info[ 'lib' ][ $type_dir ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $ext . ' lib/' . $type_dir . ' not defined' );
					}

					continue;

				} elseif ( ! is_array( $info[ 'lib' ][ $type_dir ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $ext . ' lib/' . $type_dir . ' not an array' );
					}

					continue;
				}

				foreach ( $info[ 'lib' ][ $type_dir ] as $sub_dir => $libs ) {

					/**
					 * Skip loading admin library modules if not in admin back-end.
					 */
					if ( 'admin' === $sub_dir && ! $is_admin ) {
						continue;
					}

					$log_prefix = 'loading ' . $ext . ' ' . $type_dir . '/' . $sub_dir . ': ';

					foreach ( $libs as $id => $label ) {

						$log_prefix = 'loading ' . $ext . ' ' . $type_dir . '/' . $sub_dir . '/' . $id . ': ';

						/**
						 * Loading of admin library modules in back-end is always allowed.
						 */
						if ( 'admin' === $sub_dir && $is_admin ) {
							$this->p->avail[ $sub_dir ][ $id ] = true;
						}

						/**
						 * Check if the dependent resource (active plugin or enabled option) is available.
						 */
						if ( ! empty( $this->p->avail[ $sub_dir ][ $id ] ) ) {

							$lib_path = $type_dir . '/' . $sub_dir . '/' . $id;

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

										if ( ! isset( $this->p->m[ $sub_dir ][ $id ] ) ) {

											$this->p->m[ $sub_dir ][ $id ] = new $classname( $this->p );

										} elseif ( $this->p->debug->enabled ) {
											$this->p->debug->log( $log_prefix . 'library module already defined' );
										}

									/**
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

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'load distribution modules' );	// End timer.
			}
		}
	}
}
