<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoUtilCustomFields' ) ) {

	class WpssoUtilCustomFields {

		private $p;	// Wpsso class object.
		private $u;	// WpssoUtil class object.

		/**
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin, &$util ) {

			$this->p =& $plugin;
			$this->u =& $util;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->u->add_plugin_filters( $this, array(
				'import_custom_fields' => 4,
			) );
		}

		/**
		 * The 'import_custom_fields' filter is executed before the 'wpsso_get_md_options' and 'wpsso_get_post_options'
		 * filters, so values retrieved from custom fields may get overwritten by later filters.
		 *
		 * The 'import_custom_fields' filter is also executed before the 'wpsso_get_md_defaults' and
		 * 'wpsso_get_post_defaults' filters, so submitted form values that are identical can be removed.
		 */
		public function filter_import_custom_fields( array $md_opts, array $mod, $wp_meta = array(), $alt_opts = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( function_exists( 'is_sitemap' ) && is_sitemap() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping importing custom fields for sitemap' );
				}

				return $md_opts;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'importing custom fields' );	// Begin timer.

				if ( ! empty( $alt_opts ) ) {

					$this->p->debug->log_arr( 'alt_opts', $alt_opts );
				}
			}

			$charset     = get_bloginfo( $show = 'charset', $filter = 'raw' );
			$cf_md_index = WpssoConfig::get_cf_md_index();	// Uses a local cache.
			$cf_md_multi = WpssoConfig::get_cf_md_multi();	// Uses a local cache.

			foreach ( $cf_md_index as $opt_cf_key => $md_key ) {

				if ( empty( $md_key ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'custom field ' . $opt_cf_key . ' key is disabled' );
					}

					continue;
				}

				/**
				 * Check to see if we have an alternate $wp_meta_key value for WooCommerce variations.
				 */
				if ( isset( $alt_opts[ $opt_cf_key ] ) ) {

					if ( empty( $alt_opts[ $opt_cf_key ] ) ) {	// Just in case.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'meta keys ' . $opt_cf_key . ' value is empty' );
						}

						continue;
					}

					$wp_meta_key = $alt_opts[ $opt_cf_key ];	// Example: 'hwp_var_gtin'.

				} else {

					if ( empty( $this->p->options[ $opt_cf_key ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom field ' . $opt_cf_key . ' option is empty' );
						}

						continue;
					}

					$wp_meta_key = $this->p->options[ $opt_cf_key ];	// Example: '_format_video_url'.
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using custom field ' . $wp_meta_key . ' key for ' . $md_key . ' option' );
				}

				/**
				 * WordPress offers metadata in array element 0.
				 */
				if ( isset( $wp_meta[ $wp_meta_key ][ 0 ] ) ) {

					$mixed = maybe_unserialize( $wp_meta[ $wp_meta_key ][ 0 ] );

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no element 0 in ' . $wp_meta_key . ' array' );
					}

					$mixed = '';
				}

				$values = array();

				/**
				 * If $mixed is an array, then decode each array element.
				 */
				if ( is_array( $mixed ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $wp_meta_key . ' is an array of ' . count( $mixed ) . ' elements (decoding each value)' );
					}

					foreach ( $mixed as $val ) {

						if ( is_array( $val ) ) {

							$val = SucomUtil::array_implode( $val );
						}

						$values[] = trim( html_entity_decode( SucomUtil::decode_utf8( $val ), ENT_QUOTES, $charset ) );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'decoding ' . $wp_meta_key . ' as string of ' . strlen( $mixed ) . ' chars' );
					}

					$values[] = trim( html_entity_decode( SucomUtil::decode_utf8( $mixed ), ENT_QUOTES, $charset ) );
				}

				/**
				 * Check if the value(s) should be split into multiple numeric options.
				 */
				if ( empty( $cf_md_multi[ $md_key ] ) ) {

					$md_opts[ $md_key ] = reset( $values );

					$md_opts[ $md_key . ':disabled' ] = true;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'option ' . $md_key . ' = ' . print_r( $md_opts[ $md_key ], true ) );
					}

					/**
					 * If this is a '_value' option, add the '_units' option.
					 */
					$this->p->util->maybe_add_md_key_units( $md_opts, $md_key );

					/**
					 * If this is an '_img_url' option, add the image dimensions and unset the '_img_id' option.
					 */
					$this->p->util->maybe_add_img_url_size( $md_opts, $md_key );

				} else {

					if ( ! is_array( $mixed ) ) {

						/**
						 * Explode the first element into an array.
						 */
						$values = array_map( 'trim', explode( PHP_EOL, reset( $values ) ) );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'exploded ' . $wp_meta_key . ' into array of ' . count( $values ) . ' elements' );
						}
					}

					$this->p->util->maybe_renum_md_key( $md_opts, $md_key, $values );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'importing custom fields' );	// End timer.
			}

			return $md_opts;
		}
	}
}
