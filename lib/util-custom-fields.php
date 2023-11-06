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

if ( ! class_exists( 'WpssoUtilCustomFields' ) ) {

	class WpssoUtilCustomFields {

		private $p;	// Wpsso class object.
		private $u;	// WpssoUtil class object.

		/*
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

		/*
		 * The 'import_custom_fields' filter is executed before the 'wpsso_get_md_options' and 'wpsso_get_post_options'
		 * filters, custom field values may get overwritten by these filters.
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

			$charset       = get_bloginfo( $show = 'charset', $filter = 'raw' );
			$cf_md_index   = WpssoConfig::get_cf_md_index();	// Uses a local cache.
			$md_keys_multi = WpssoConfig::get_md_keys_multi();	// Uses a local cache.

			foreach ( $cf_md_index as $opt_cf_key => $md_key ) {

				if ( empty( $md_key ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'custom field ' . $opt_cf_key . ' key is disabled' );
					}

					continue;
				}

				/*
				 * Check to see if we have an alternate $cf_key value for WooCommerce variations.
				 */
				if ( isset( $alt_opts[ $opt_cf_key ] ) ) {

					if ( empty( $alt_opts[ $opt_cf_key ] ) ) {	// Just in case.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'meta keys ' . $opt_cf_key . ' value is empty' );
						}

						continue;
					}

					$cf_key = $alt_opts[ $opt_cf_key ];	// Example: 'hwp_var_gtin'.

				} else {

					if ( empty( $this->p->options[ $opt_cf_key ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom field ' . $opt_cf_key . ' option is empty' );
						}

						continue;
					}

					$cf_key = $this->p->options[ $opt_cf_key ];	// Example: '_format_video_url'.
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using custom field ' . $cf_key . ' key for ' . $md_key . ' option' );
				}

				/*
				 * WordPress offers metadata in array element 0.
				 */
				$cf_val = null;

				if ( isset( $wp_meta[ $cf_key ][ 0 ] ) ) {

					$cf_val = maybe_unserialize( $wp_meta[ $cf_key ][ 0 ] );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no element 0 in ' . $cf_key . ' array' );
				}

				/*
				 * Apply filters for the custom field (value is null if the custom field does not exist).
				 */
				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_import_cf_' . $cf_key );
				$cf_val      = apply_filters( $filter_name, $cf_val, $mod, $wp_meta );

				/*
				 * Decode and trim the string or each array element.
				 */
				$values = array();

				if ( is_array( $cf_val ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $cf_key . ' is an array of ' . count( $cf_val ) . ' elements (decoding each value)' );
					}

					foreach ( $cf_val as $val ) {	// Decode and trim each array element.

						if ( is_array( $val ) ) {	// Just in case - flatten multi-dimensional arrays.

							$val = SucomUtil::array_implode( $val );
						}

						$values[] = trim( html_entity_decode( SucomUtil::decode_utf8( $val ), ENT_QUOTES, $charset ) );
					}

				} elseif ( null !== $cf_val ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'decoding ' . $cf_key . ' as string of ' . strlen( $cf_val ) . ' chars' );
					}

					$values[] = trim( html_entity_decode( SucomUtil::decode_utf8( $cf_val ), ENT_QUOTES, $charset ) );
				}

				/*
				 * Check if the value(s) should be split into multiple numeric options.
				 */
				if ( ! empty( $values ) ) {

					if ( ! empty( $md_keys_multi[ $md_key ] ) ) {

						/*
						 * If $cf_val was not an array, then $values[ 0 ] will be a string - split that string into an array.
						 */
						if ( ! is_array( $cf_val ) ) {

							$values = array_map( 'trim', explode( PHP_EOL, reset( $values ) ) );

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'exploded ' . $cf_key . ' into array of ' . count( $values ) . ' elements' );
							}
						}

						$this->u->maybe_renum_md_key( $md_opts, $md_key, $values, $is_disabled = true );

					} else {

						$md_opts[ $md_key ] = reset( $values );

						$md_opts[ $md_key . ':disabled' ] = true;

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'option ' . $md_key . ' = ' . print_r( $md_opts[ $md_key ], true ) );
						}

						/*
						 * If this is a '_value' option, add the '_units' option.
						 */
						$this->u->maybe_add_md_key_units( $md_opts, $md_key );

						/*
						 * If this is an '_img_url' option, add the image dimensions and unset the '_img_id' option.
						 */
						$this->u->maybe_add_img_url_size( $md_opts, $md_key );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'importing custom fields' );	// End timer.
			}

			return $md_opts;
		}
	}
}
