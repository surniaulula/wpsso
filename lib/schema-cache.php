<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSchemaCache' ) ) {

	class WpssoSchemaCache {

		protected static $cache_exp_secs = null;

		public function __construct( &$plugin ) {
		}

		/**
		 * Deprecated on 2019/07/01.
		 */
		public static function get_single( array $mod, $mt_og, $page_type_id ) {

			return self::get_mod_json_data( $mod, $mt_og, $page_type_id );
		}

		/**
		 * Return a single cache element (false or json data array).
		 */
		public static function get_mod_json_data( array $mod, $mt_og, $page_type_id ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {
				$wpsso->debug->mark();
			}

			if ( ! is_object( $mod[ 'obj' ] ) || ! $mod[ 'id' ] ) {

				if ( $wpsso->debug->enabled ) {
					$wpsso->debug->log( 'exiting early: $mod has no object or id is empty' );
				}

				return false;
			}

			$cache_index = self::get_mod_index( $mod, $page_type_id );
			$cache_data  = self::get_mod_data( $mod, $cache_index );

			if ( isset( $cache_data[ $cache_index ] ) ) {

				if ( $wpsso->debug->enabled ) {
					$wpsso->debug->log( 'exiting early: returning single "' . $mod[ 'name' ] . '" cache data' );
				}

				return $cache_data[ $cache_index ];	// Stop here.
			}

			/**
			 * Set the reference values for admin notices.
			 */
			if ( is_admin() ) {

				$sharing_url = $wpsso->util->get_sharing_url( $mod );

				if ( $mod[ 'post_type' ] && $mod[ 'id' ] ) {

					$wpsso->notice->set_ref( $sharing_url, $mod,
						sprintf( __( 'adding schema for %1$s ID %2$s', 'wpsso' ), $mod[ 'post_type' ], $mod[ 'id' ] ) );

				} elseif ( $mod[ 'name' ] && $mod[ 'id' ] ) {

					$wpsso->notice->set_ref( $sharing_url, $mod,
						sprintf( __( 'adding schema for %1$s ID %2$s', 'wpsso' ), $mod[ 'name' ], $mod[ 'id' ] ) );
				} else {
					$wpsso->notice->set_ref( $sharing_url, $mod );
				}
			}

			if ( ! is_array( $mt_og ) ) {
				$mt_og = $wpsso->og->get_array( $mod, $mt_og = array() );
			}

			$cache_data[ $cache_index ] = $wpsso->schema->get_json_data( $mod, $mt_og, false, true );

			/**
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {
				$wpsso->notice->unset_ref( $sharing_url );
			}

			self::save_mod_data( $mod, $cache_data );

			return $cache_data[ $cache_index ];
		}

		public static function get_mod_index( $mixed, $page_type_id ) {

			$cache_index = 'page_type_id:' . $page_type_id;

			if ( false !== $mixed ) {
				$cache_index .= '_locale:' . SucomUtil::get_locale( $mixed );
			}

			if ( SucomUtil::is_amp() ) {
				$cache_index .= '_amp:true';
			}

			return $cache_index;
		}

		/**
		 * Returns an associative array of json data. The $cache_index argument is used for 
		 * quality control - making sure the $cache_index json data is an array (if it exists).
		 */
		public static function get_mod_data( $mod, $cache_index ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {
				$wpsso->debug->mark();
			}

			$cache_md5_pre = $wpsso->lca . '_j_';
			$cache_salt    = __CLASS__ . '::mod_data(' . SucomUtil::get_mod_salt( $mod ) . ')';
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			if ( ! isset( self::$cache_exp_secs ) ) {	// Filter cache expiration if not already set.

				$cache_exp_filter = $wpsso->cf[ 'wp' ][ 'transient' ][ $cache_md5_pre ][ 'filter' ];
				$cache_opt_key    = $wpsso->cf[ 'wp' ][ 'transient' ][ $cache_md5_pre ][ 'opt_key' ];

				self::$cache_exp_secs = (int) apply_filters( $cache_exp_filter, $wpsso->options[ $cache_opt_key ] );
			}

			if ( $wpsso->debug->enabled ) {
				$wpsso->debug->log( 'cache expire = ' . self::$cache_exp_secs );
				$wpsso->debug->log( 'cache salt = ' . $cache_salt );
				$wpsso->debug->log( 'cache id = ' . $cache_id );
				$wpsso->debug->log( 'cache index = ' . $cache_index );
			}

			if ( self::$cache_exp_secs > 0 ) {

				$cache_data = SucomUtil::get_transient_array( $cache_id );

				if ( isset( $cache_data[ $cache_index ] ) ) {

					if ( is_array( $cache_data[ $cache_index ] ) ) {	// Just in case.

						if ( $wpsso->debug->enabled ) {
							$wpsso->debug->log( 'cache index data found in array from transient' );
						}

						return $cache_data;	// Stop here.

					} else {

						if ( $wpsso->debug->enabled ) {
							$wpsso->debug->log( 'cache index data not an array (unsetting index)' );
						}

						unset( $cache_data[ $cache_index ] );	// Just in case.

						return $cache_data;	// Stop here.
					}

				} else {

					if ( $wpsso->debug->enabled ) {
						$wpsso->debug->log( 'cache index not in transient' );
					}

					return $cache_data;	// Stop here.
				}

			} else {
			
				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'transient cache is disabled' );

					if ( SucomUtil::delete_transient_array( $cache_id ) ) {
						$wpsso->debug->log( 'deleted transient cache id ' . $cache_id );
					}
				}
			}

			return false;
		}

		public static function save_mod_data( $mod, $cache_data ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {
				$wpsso->debug->mark();
			}

			$cache_md5_pre = $wpsso->lca . '_j_';
			$cache_salt    = __CLASS__ . '::mod_data(' . SucomUtil::get_mod_salt( $mod ) . ')';
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			if ( ! isset( self::$cache_exp_secs ) ) {	// Filter cache expiration if not already set.

				$cache_exp_filter = $wpsso->cf[ 'wp' ][ 'transient' ][ $cache_md5_pre ][ 'filter' ];
				$cache_opt_key    = $wpsso->cf[ 'wp' ][ 'transient' ][ $cache_md5_pre ][ 'opt_key' ];

				self::$cache_exp_secs = (int) apply_filters( $cache_exp_filter, $wpsso->options[ $cache_opt_key ] );
			}

			if ( $wpsso->debug->enabled ) {
				$wpsso->debug->log( 'cache expire = ' . self::$cache_exp_secs );
				$wpsso->debug->log( 'cache salt = ' . $cache_salt );
				$wpsso->debug->log( 'cache id = ' . $cache_id );
			}

			if ( self::$cache_exp_secs > 0 ) {

				$expires_in_secs = SucomUtil::update_transient_array( $cache_id, $cache_data, self::$cache_exp_secs );

				if ( $wpsso->debug->enabled ) {
					$wpsso->debug->log( 'cache data saved to transient cache (expires in ' . $expires_in_secs . ' secs)' );
				}

			} elseif ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'transient cache is disabled' );

				if ( SucomUtil::delete_transient_array( $cache_id ) ) {
					$wpsso->debug->log( 'deleted transient cache id ' . $cache_id );
				}
			}

			return false;
		}

		public static function delete_mod_data( $mod ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {
				$wpsso->debug->mark();
			}

			$cache_md5_pre = $wpsso->lca . '_j_';
			$cache_salt    = __CLASS__ . '::mod_data(' . SucomUtil::get_mod_salt( $mod ) . ')';
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			return SucomUtil::delete_transient_array( $cache_id );
		}
	}
}
