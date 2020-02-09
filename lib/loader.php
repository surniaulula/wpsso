<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
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

				$this->p->debug->mark( 'load modules' );	// Begin timer.

				if ( $has_action ) {
					$this->p->debug->log( 'loading modules for action ' . $has_action );
				} else {
					$this->p->debug->log( 'no action provided to filter module keys' );
				}
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$type = $this->p->check->pp( $ext, $li = true, WPSSO_UNDEF, $rc = true, -1 ) === 1 ? 'pro' : 'std';

				if ( ! isset( $info[ 'lib' ][ $type ] ) ) {

					if ( 'std' === $type && isset( $info[ 'lib' ][ 'gpl' ] ) ) {

						$type = 'gpl';

					} else {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $ext . ' lib/' . $type . ' not defined' );
						}
	
						continue;
					}
				}

				if ( ! is_array( $info[ 'lib' ][ $type ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $ext . ' lib/' . $type . ' not an array' );
					}

					continue;
				}

				foreach ( $info[ 'lib' ][ $type ] as $sub => $libs ) {

					$log_prefix = 'loading ' . $ext . ' ' . $type . '/' . $sub . ': ';

					/**
					 * Skip loading admin library modules if not in admin back-end.
					 */
					if ( 'admin' === $sub && ! $is_admin ) {
						continue;
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

						/**
						 * Loading of admin library modules in back-end is always allowed.
						 */
						if ( 'admin' === $sub && $is_admin ) {
							$this->p->avail[ $sub ][ $id ] = true;
						}

						if ( ! empty( $this->p->avail[ $sub ][ $id ] ) ) {

							/**
							 * Compare $action from library id with $has_action method argument.
							 * This is usually / almost always a false === false comparison.
							 */
							if ( $action !== $has_action ) {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $log_prefix . 'ignored for action ' . $action );
								}

								continue;
							}

							$lib_path = $type . '/' . $sub . '/' . $id;

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

										if ( ! isset( $this->p->m[ $sub ][ $id ] ) ) {
											$this->p->m[ $sub ][ $id ] = new $classname( $this->p );
										} elseif ( $this->p->debug->enabled ) {
											$this->p->debug->log( $log_prefix . 'library module already defined' );
										}

									/**
									 * Loaded module objects from extensions / add-ons.
									 */
									} elseif ( ! isset( $this->p->m_ext[ $ext ][ $sub ][ $id ] ) ) {
										$this->p->m_ext[ $ext ][ $sub ][ $id ] = new $classname( $this->p );
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

									$this->p->notice->err( $error_msg . ' ' . $suffix_msg );
								}

								SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg . ' ' . $suffix_msg );
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
