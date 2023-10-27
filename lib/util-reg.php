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

if ( ! class_exists( 'WpssoUtilReg' ) ) {

	class WpssoUtilReg {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		/*
		 * Perform a quick sanity check and return the timestamp array.
		 *
		 * Used by WpssoAdminHead->single_notice_review() and WpssoAdminHead->single_notice_upsell().
		 */
		public function get_ext_reg() {

			$ext_reg = get_option( WPSSO_REG_TS_NAME, array() );

			$have_changes = false;

			/*
			 * Make sure that all known add-ons have been registered.
			 */
			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// Not active.

					continue;
				}

				foreach ( array( 'update', 'install', 'activate' ) as $event ) {

					$update_event = false;

					if ( empty( $ext_reg[ $ext . '_' . $event . '_time' ] ) ) {

						$update_event = true;

					} elseif ( $event === 'update' ) {

						if ( empty( $ext_reg[ $ext . '_' . $event . '_version' ] ) ) {

							$update_event = true;

						} elseif ( $ext_reg[ $ext . '_' . $event . '_version' ] !== $info[ 'version' ] ) {

							$update_event = true;
						}
					}

					if ( $update_event ) {

						$have_changes = true;

						self::update_ext_event_time( $ext, $info[ 'version' ], $event );
					}
				}
			}

			if ( $have_changes ) {

				$ext_reg = get_option( WPSSO_REG_TS_NAME, array() );
			}

			return $ext_reg;
		}

		/*
		 * Called by all add-ons from their activate_plugin() method.
		 */
		public static function update_ext_version( $ext, $version ) {

			self::update_ext_event_time( $ext, $version, 'update', $version );

			self::update_ext_event_time( $ext, $version, 'install', $protect = true );	// Do not overwrite existing value.

			self::update_ext_event_time( $ext, $version, 'activate' );
		}

		/*
		 * Returns the event timestamp, or false if the event has not been registered.
		 *
		 * Used by WpssoRegister->activate_plugin() to determine if this is a new install (to add user roles).
		 */
		public static function get_ext_event_time( $ext, $event ) {

			$ext_reg = get_option( WPSSO_REG_TS_NAME, array() );

			if ( ! empty( $ext_reg[ $ext . '_' . $event . '_time' ] ) ) {

				return $ext_reg[ $ext . '_' . $event . '_time' ];
			}

			return false;
		}

		/*
		 * $protect = true | false | version
		 */
		private static function update_ext_event_time( $ext, $version, $event, $protect = false ) {

			if ( ! is_bool( $protect ) ) {	// Version string.

				if ( ! empty( $protect ) ) {

					$event_version = SucomUtilWP::get_options_key( WPSSO_REG_TS_NAME, $ext . '_' . $event . '_version' );

					if ( $event_version === $protect ) {

						$protect = true;

					} else $protect = false;

				} else $protect = true;	// Just in case.
			}

			if ( ! empty( $version ) ) {

				SucomUtilWP::update_options_key( WPSSO_REG_TS_NAME, $ext . '_' . $event . '_version', $version, $protect );
			}

			SucomUtilWP::update_options_key( WPSSO_REG_TS_NAME, $ext . '_' . $event . '_time', time(), $protect );
		}

		/*
		 * Deprecated on 2023/10/23.
		 */
		public static function add_site_options_key( $options_name, $key, $value ) {

			_deprecated_function( __METHOD__ . '()', '2023/10/23', $replacement = 'SucomUtilWP::add_site_options_key()' );	// Deprecation message.

			return SucomUtilWP::add_site_options_key( $options_name, $key, $value );
		}

		/*
		 * Deprecated on 2023/10/23.
		 */
		public static function add_options_key( $options_name, $key, $value ) {

			_deprecated_function( __METHOD__ . '()', '2023/10/23', $replacement = 'SucomUtilWP::add_options_key()' );	// Deprecation message.

			return SucomUtilWP::add_options_key( $options_name, $key, $value );
		}

		/*
		 * Deprecated on 2023/10/23.
		 */
		public static function update_site_options_key( $options_name, $key, $value, $protect = false ) {

			_deprecated_function( __METHOD__ . '()', '2023/10/23', $replacement = 'SucomUtilWP::update_site_options_key()' );	// Deprecation message.

			return SucomUtilWP::update_site_options_key( $options_name, $key, $value, $protect );
		}

		/*
		 * Deprecated on 2023/10/23.
		 */
		public static function update_options_key( $options_name, $key, $value, $protect = false, $site = false ) {

			_deprecated_function( __METHOD__ . '()', '2023/10/23', $replacement = 'SucomUtilWP::update_options_key()' );	// Deprecation message.

			return SucomUtilWP::update_options_key( $options_name, $key, $value, $protect, $site );
		}

		/*
		 * Deprecated on 2023/10/23.
		 */
		public static function get_site_options_key( $options_name, $key ) {

			_deprecated_function( __METHOD__ . '()', '2023/10/23', $replacement = 'SucomUtilWP::get_site_options_key()' );	// Deprecation message.

			return SucomUtilWP::get_site_options_key( $options_name, $key );
		}

		/*
		 * Deprecated on 2023/10/23.
		 */
		public static function get_options_key( $options_name, $key, $site = false ) {

			_deprecated_function( __METHOD__ . '()', '2023/10/23', $replacement = 'SucomUtilWP::get_options_key()' );	// Deprecation message.

			return SucomUtilWP::get_options_key( $options_name, $key, $site );
		}

		/*
		 * Deprecated on 2023/10/23.
		 */
		public static function delete_site_options_key( $options_name, $key ) {

			_deprecated_function( __METHOD__ . '()', '2023/10/23', $replacement = 'SucomUtilWP::delete_site_options_key()' );	// Deprecation message.

			return SucomUtilWP::delete_site_options_key( $options_name, $key );
		}

		/*
		 * Deprecated on 2023/10/23.
		 */
		public static function delete_options_key( $options_name, $key, $site = false ) {

			_deprecated_function( __METHOD__ . '()', '2023/10/23', $replacement = 'SucomUtilWP::delete_options_key()' );	// Deprecation message.

			return SucomUtilWP::delete_options_key( $options_name, $key, $site );
		}
	}
}
