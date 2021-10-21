<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoOptions' ) ) {

	class WpssoOptions {

		private $p;	// Wpsso class object.
		private $upg;	// WpssoOptionsUpgrade class object.
		private $filters;

		private $cache_defaults      = array();	// Default options cache.
		private $cache_site_defaults = array();	// Default site options cache.

		private static $cache_allowed = false;

		/**
		 * Instantiated by Wpsso->set_objects().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			require_once WPSSO_PLUGINDIR . 'lib/options-filters.php';

			$this->filters = new WpssoOptionsFilters( $plugin );

			$this->p->util->add_plugin_actions( $this, array(
				'init_objects' => 0,
			), $prio = 1000 );
		}

		/**
		 * This action is called by Wpsso->set_objects() to initialize additional class objects.
		 */
		public function action_init_objects() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'setting cache_allowed to true' );
			}

			self::$cache_allowed = true;
		}

		public static function is_cache_allowed() {

			return self::$cache_allowed;
		}

		public function get_defaults( $opt_key = false, $force_filter = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'opt_key'      => $opt_key,
					'force_filter' => $force_filter,
				) );
			}

			if ( empty( $this->cache_defaults ) || empty( self::$cache_allowed ) ) {

				$this->cache_defaults = $this->p->cf[ 'opt' ][ 'defaults' ];
			}

			if ( $force_filter || empty( $this->cache_defaults[ 'options_filtered' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'get_defaults filters' );	// Begin timer.
				}

				/**
				 * Complete the options array for any custom post types and/or custom taxonomies.
				 */
				$this->add_post_type_taxonomy_name_options( $this->cache_defaults );

				/**
				 * Translate contact method field labels for current language.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'translating plugin contact field labels' );
				}

				SucomUtil::transl_key_values( '/^plugin_(cm_.*_label|.*_prefix)$/', $this->cache_defaults, 'wpsso' );

				/**
				 * Define the default Facebook locale and current locale values.
				 */
				$this->cache_defaults[ 'fb_locale' ] = $this->p->og->get_fb_locale( array(), 'default' );

				if ( ( $locale_key = SucomUtil::get_key_locale( 'fb_locale' ) ) !== 'fb_locale' ) {

					$this->cache_defaults[ $locale_key ] = $this->p->og->get_fb_locale( array(), 'current' );
				}

				/**
				 * Maybe use a custom value from the SSO > Advanced settings page.
				 */
				$this->cache_defaults[ 'fb_author_field' ] = $this->p->options[ 'plugin_cm_fb_name' ];

				/**
				 * Maybe import Yoast SEO social meta.
				 *
				 * Enabled by default if the Yoast SEO plugin is active, or if no SEO plugin is active but Yoast
				 * SEO settings are found in the database.
				 */
				if ( ! empty( $this->p->avail[ 'seo' ][ 'wpseo' ] ) ) {	// Yoast SEO is active.

					$this->cache_defaults[ 'plugin_wpseo_social_meta' ] = 1;

				} elseif ( empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {	// No other SEO plugin is active.

					if ( get_option( 'wpseo' ) ) {	// Yoast SEO was once active.

						$this->cache_defaults[ 'plugin_wpseo_social_meta' ] = 1;
					}
				}

				/**
				 * Define the default organization or person ID for Knowledge Graph markup in the home page.
				 */
				switch ( $this->p->options[ 'site_pub_schema_type' ] ) {

					case 'person':

						$this->cache_defaults[ 'schema_def_pub_org_id' ]    = 'none';
						$this->cache_defaults[ 'schema_def_pub_person_id' ] = $this->p->options[ 'site_pub_person_id' ];

						break;

					case 'organization':

						$this->cache_defaults[ 'schema_def_pub_org_id' ]    = 'site';
						$this->cache_defaults[ 'schema_def_pub_person_id' ] = 'none';

						break;
				}

				/**
				 * If there's an update authentication method available, make sure the option key for its value
				 * exists.
				 */
				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( ! empty( $info[ 'update_auth' ] ) && 'none' !== $info[ 'update_auth' ] ) {	// Just in case.

						$this->cache_defaults[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ] ] = '';
					}
				}

				/**
				 * Check for default values from network admin settings.
				 */
				if ( is_multisite() && is_array( $this->p->site_options ) ) {

					foreach ( $this->p->site_options as $site_opt_key => $site_opt_val ) {

						if ( isset( $this->cache_defaults[ $site_opt_key ] ) && isset( $this->p->site_options[ $site_opt_key . ':use' ] ) ) {

							if ( $this->p->site_options[ $site_opt_key . ':use' ] === 'default' ) {

								$this->cache_defaults[ $site_opt_key ] = $this->p->site_options[ $site_opt_key ];
							}
						}
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying get_defaults filters' );
				}

				$this->cache_defaults[ 'options_filtered' ] = 1;	// Set before calling filter to prevent recursion.

				$this->cache_defaults = apply_filters( 'wpsso_get_defaults', $this->cache_defaults );

				if ( empty( self::$cache_allowed ) ) {

					$this->cache_defaults[ 'options_filtered' ] = 0;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'get_defaults filters' );	// End timer.
				}
			}

			if ( false !== $opt_key ) {

				if ( isset( $this->cache_defaults[ $opt_key ] ) ) {

					return $this->cache_defaults[ $opt_key ];
				}

				return null;
			}

			return $this->cache_defaults;
		}

		public function get_site_defaults( $opt_key = false, $force_filter = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'opt_key'      => $opt_key,
					'force_filter' => $force_filter,
				) );
			}

			if ( empty( $this->cache_site_defaults ) || empty( self::$cache_allowed ) ) {

				/**
				 * Automatically include all advanced plugin options. 
				 */
				$this->cache_site_defaults = SucomUtil::preg_grep_keys( '/^plugin_/', $this->p->cf[ 'opt' ][ 'defaults' ] );

				/**
				 * Add a default "Site Use" value.
				 */
				foreach ( $this->cache_site_defaults as $key => $val ) {

					if ( false === strpos( $key, ':' ) ) {	// Just in case.

						$this->cache_site_defaults[ $key . ':use' ] = 'default';
					}
				}

				$this->cache_site_defaults = array_merge( $this->cache_site_defaults, $this->p->cf[ 'opt' ][ 'site_defaults' ] );
			}

			if ( $force_filter || empty( $this->cache_site_defaults[ 'options_filtered' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'get_site_defaults filters' );	// Begin timer.
				}

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( ! empty( $info[ 'update_auth' ] ) && $info[ 'update_auth' ]!== 'none' ) {	// Just in case.

						$this->cache_site_defaults[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ] ] = '';

						$this->cache_site_defaults[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ] . ':use' ] = 'default';
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying get_site_defaults filters' );
				}

				$this->cache_site_defaults[ 'options_filtered' ] = 1;	// Set before calling filter to prevent recursion.

				$this->cache_site_defaults = apply_filters( 'wpsso_get_site_defaults', $this->cache_site_defaults );

				if ( empty( self::$cache_allowed ) ) {

					$this->cache_site_defaults[ 'options_filtered' ] = 0;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'get_site_defaults filters' );	// End timer.
				}
			}

			if ( false !== $opt_key ) {

				if ( isset( $this->cache_site_defaults[ $opt_key ] ) ) {

					return $this->cache_site_defaults[ $opt_key ];
				}

				return null;
			}

			return $this->cache_site_defaults;
		}

		/**
		 * Returns a checked, fixed, and/or upgraded options array.
		 */
		public function check_options( $options_name, $opts = array(), $network = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'checking options' );	// Begin timer.
			}

			/**
			 * Options should always be an array and not empty.
			 */
			if ( empty( $opts ) || ! is_array( $opts ) ) {

				if ( false === $opts ) {

					$error_msg = sprintf( __( 'WordPress could not find an entry for %s in the options table.', 'wpsso' ), $options_name );

				} elseif ( ! is_array( $opts ) ) {

					$error_msg = sprintf( __( 'WordPress returned a non-array value when reading %s from the options table.', 'wpsso' ), $options_name );

				} elseif ( empty( $opts ) ) {

					$error_msg = sprintf( __( 'WordPress returned an empty array when reading %s from the options table.', 'wpsso' ), $options_name );

				} else {

					$error_msg = sprintf( __( 'WordPress returned an unknown condition when reading %s from the options table.', 'wpsso' ), $options_name );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $error_msg );
				}

				if ( is_admin() ) {

					if ( $network ) {

						$admin_url = $this->p->util->get_admin_url( 'network' );

					} else {

						$admin_url = $this->p->util->get_admin_url( 'general' );
					}

					$error_msg .= ' ' . sprintf( __( 'The plugin settings have been returned to their default values - <a href="%s">please review and save the new settings</a>.', 'wpsso' ), $admin_url );

					$this->p->notice->err( $error_msg );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'checking options' );	// End timer.
				}

				return $network ? $this->get_site_defaults() : $this->get_defaults();
			}

			$is_new_options  = empty( $opts[ 'options_version' ] ) ? true : false;	// Example: -wpsso512pro-wpssoum3gpl
			$current_version = $is_new_options ? 0 : $opts[ 'options_version' ];	// Example: -wpsso512pro-wpssoum3gpl
			$latest_version  = $this->p->cf[ 'opt' ][ 'version' ];
			$options_changed = $current_version !== $latest_version ? true : false;
			$version_changed = false;
			$defs            = null;	// Optimize and only get array when needed.

			if ( ! $is_new_options ) {

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( isset( $info[ 'version' ] ) ) {

						if ( ! isset( $opts[ 'plugin_' . $ext . '_version' ] ) || $opts[ 'plugin_' . $ext . '_version' ] !== $info[ 'version' ] ) {

							$version_changed = true;
						}
					}
				}
			}

			/**
			 * Upgrade the options array if necessary (renamed or remove keys).
			 */
			if ( ! $is_new_options && $options_changed ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $options_name . ' current v' . $current_version . ' different than latest v' . $latest_version );
				}

				if ( null === $defs ) {	// Only get default options once.

					if ( $network ) {

						$defs = $this->get_site_defaults();

					} else {

						$defs = $this->get_defaults();
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'upgrading the ' . $options_name . ' settings' );
				}

				if ( ! is_object( $this->upg ) ) {

					require_once WPSSO_PLUGINDIR . 'lib/upgrade.php';

					$this->upg = new WpssoOptionsUpgrade( $this->p );
				}

				$opts = $this->upg->options( $options_name, $opts, $defs, $network );
			}

			/**
			 * Adjust / cleanup non-network options.
			 */
			if ( ! $network ) {

				/**
				 * Adjust SEO plugin related options.
				 */
				$seo_opts = array();

				if ( empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {	// An SEO plugin is not active.

					if ( empty( $opts[ 'plugin_wpsso_tid' ] ) ) {

						$seo_opts = array(
							'add_link_rel_canonical'    => 0,
							'add_meta_name_description' => 1,
							'add_meta_name_robots'      => 1,
						);
					}

				} else {	// An SEO plugin is active (Yoast SEO, or any other).

					$seo_opts = array(
						'add_link_rel_canonical'    => 0,
						'add_meta_name_description' => 0,
						'add_meta_name_robots'      => 0,
					);

					/**
					 * An SEO plugin is active, but it's not the Yoast SEO plugin, so skip importing old Yoast
					 * SEO post/term/user metadata.
					 */
					if ( empty( $this->p->avail[ 'seo' ][ 'wpseo' ] ) ) {

						$seo_opts[ 'plugin_wpseo_social_meta' ] = 0;
					}
				}

				foreach ( $seo_opts as $opt_key => $def_val ) {

					$opts[ $opt_key . ':is' ] = 'disabled';	// Prevent changes in settings page.

					if ( $opts[ $opt_key ] !== $def_val ) {

						$opts[ $opt_key ] = $def_val;

						$options_changed = true;	// Save the options.
					}
				}

				/**
				 * Hard-code fixed options.
				 */
				foreach ( array( 'og:image', 'og:video' ) as $mt_name ) {

					$opts[ 'add_meta_property_' . $mt_name . ':secure_url' ]    = 0;		// Always unchecked.
					$opts[ 'add_meta_property_' . $mt_name . ':secure_url:is' ] = 'disabled';	// Prevent changes in settings page.
					$opts[ 'add_meta_property_' . $mt_name . ':url' ]           = 0;		// Always unchecked.
					$opts[ 'add_meta_property_' . $mt_name . ':url:is' ]        = 'disabled';	// Prevent changes in settings page.
					$opts[ 'add_meta_property_' . $mt_name ]                    = 1;		// Always checked (canonical URL).
					$opts[ 'add_meta_property_' . $mt_name . ':is' ]            = 'disabled';	// Prevent changes in settings page.
				}

				/**
				 * Check for website verification IDs and enable/disable meta tags as required.
				 */
				foreach ( WpssoConfig::$cf[ 'opt' ][ 'site_verify_meta_names' ] as $site_verify => $meta_name ) {

					$opts[ 'add_meta_name_' . $meta_name ]         = empty( $opts[ $site_verify ] ) ? 0 : 1;
					$opts[ 'add_meta_name_' . $meta_name . ':is' ] = 'disabled';
				}

				/**
				 * Check for incompatible options between versions.
				 */
				if ( ! $is_new_options && $version_changed ) {

					if ( empty( $opts[ 'plugin_wpsso_tid' ] ) ) {

						// translators: %s is the option key name.
						$notice_msg = __( 'Non-standard value found for the "%s" option - resetting the option to its default value.', 'wpsso' );

						if ( null === $defs ) {	// Only get default options once.

							$defs = $this->get_defaults();
						}

						$advanced_preg = '/^(plugin_.*|add_meta_(property_|name_twitter:).*|.*_img_(width|height|crop|crop_x|crop_y)|schema_def_.*)$/';
						$advanced_opts = SucomUtil::preg_grep_keys( $advanced_preg, $defs );
						$advanced_opts = SucomUtil::preg_grep_keys( '/^plugin_.*_tid$/', $advanced_opts, $invert = true );

						foreach ( array(
							'plugin_clean_on_uninstall',
							'plugin_cache_disable',
							'plugin_debug_html',
							'plugin_load_mofiles',
						) as $opt_key ) {

							unset( $advanced_opts[ $opt_key ] );
						}

						foreach ( $advanced_opts as $opt_key => $def_val ) {

							if ( isset( $opts[ $opt_key ] ) ) {

								if ( $opts[ $opt_key ] === $def_val ) {

									continue;
								}

								if ( is_admin() ) {

									$this->p->notice->warn( sprintf( $notice_msg, $opt_key ) );
								}
							}

							$opts[ $opt_key ] = $def_val;

							$options_changed = true;	// Save the options.
						}
					}
				}
			}

			/**
			 * Complete the options array for any custom post types and/or custom taxonomies.
			 */
			$this->add_post_type_taxonomy_name_options( $opts );

			/**
			 * Note that generator meta tags are required for plugin support. If you disable the generator meta
			 * tags, requests for plugin support will be denied.
			 */
			if ( SucomUtil::get_const( 'WPSSO_META_GENERATOR_DISABLE' ) ) {

				$opts[ 'add_meta_name_generator' ] = 0;

			} else {

				$opts[ 'add_meta_name_generator' ] = 1;
			}

			$opts[ 'add_meta_name_generator:is' ] = 'disabled';

			/**
			 * Google does not recognize all Schema Organization sub-types as valid organization and publisher
			 * types. The WebSite organization type ID should be "organization" unless you are confident that
			 * Google will recognize your preferred Schema Organization sub-type as a valid organization. To
			 * select a different organization type ID for your WebSite, define the
			 * WPSSO_SCHEMA_ORGANIZATION_TYPE_ID constant with your preferred type ID (not the Schema type
			 * URL).
			 */
			$site_org_type_id = SucomUtil::get_const( 'WPSSO_SCHEMA_ORGANIZATION_TYPE_ID', 'organization' );

			if ( ! preg_match( '/^[a-z\.]+$/', $site_org_type_id ) ) {	// Quick sanitation to allow only valid IDs.

				$site_org_type_id = 'organization';
			}

			$opts[ 'site_org_schema_type' ]    = $site_org_type_id;
			$opts[ 'site_org_schema_type:is' ] = 'disabled';

			/**
			 * Include VAT in Product Prices.
			 *
			 * Allow the WPSSO_PRODUCT_PRICE_INCLUDE_VAT constant to override the 'plugin_product_include_vat' value.
			 */
			if ( defined( 'WPSSO_PRODUCT_PRICE_INCLUDE_VAT' ) ) {

				$opts[ 'plugin_product_include_vat' ]    = WPSSO_PRODUCT_PRICE_INCLUDE_VAT ? 1 : 0;
				$opts[ 'plugin_product_include_vat:is' ] = 'disabled';
			}

			/**
			 * Save options and show reminders.
			 */
			if ( $options_changed || $version_changed ) {

				$this->save_options( $options_name, $opts, $network );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'checking options' );	// End timer.
			}

			return $opts;
		}

		/**
		 * Sanitize and validate options, including both the plugin options and custom meta options arrays.
		 */
		public function sanitize( $opts = array(), $defs = array(), $network = false, $mod = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Make sure we have something to work with.
			 */
			if ( empty( $defs ) || ! is_array( $defs ) ) {

				return $opts;
			}

			/**
			 * Add any missing options from the defaults, unless sanitizing for a module.
			 */
			if ( empty( $mod[ 'name' ] ) ) {

				foreach ( $defs as $opt_key => $def_val ) {

					if ( ! empty( $opt_key ) && ! isset( $opts[ $opt_key ] ) ) {

						$opts[ $opt_key ] = $def_val;
					}
				}
			}

			/**
			 * Sort the options to re-order 0, 1, 10, 2 suffixes as 0, 1, 2, 10.
			 */
			SucomUtil::natksort( $opts );

			/**
			 * Sanitize values.
			 */
			foreach ( $opts as $opt_key => $opt_val ) {

				if ( empty( $opt_key ) ) {

					continue;
				}

				/**
				 * Remove multiples, localization, and status for more generic match.
				 */
				$base_key = preg_replace( '/(_[0-9]+)?(#.*|:[0-9]+)?$/', '', $opt_key );

				/**
				 * Multi-options and localized options will default to an empty string.
				 */
				$def_val = isset( $defs[ $opt_key ] ) ? $defs[ $opt_key ] : '';

				/**
				 * Ignore option qualifiers.
				 */
				if ( preg_match( '/:is$/', $base_key ) ) {

					unset( $opts[ $opt_key ] );

				/**
				 * Ignore localized options with an empty string value and no default.
				 */
				} elseif ( strpos( $opt_key, '#' ) && ! isset( $defs[ $opt_key ] ) && '' === $opt_val ) {

					unset( $opts[ $opt_key ] );

				} else {

					$opts[ $opt_key ] = $this->check_value( $opt_key, $base_key, $opt_val, $def_val, $network, $mod );
				}
			}

			/**
			 * Adjust Dependent Options
			 *
			 * All options (site and meta as well) are sanitized here, so always use isset() or array_key_exists() on
			 * all tests to make sure additional / unnecessary options are not created in post meta.
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^(.*)_img_width$/', $opts, $invert = false, $replace = '$1' ) as $opt_pre => $img_width ) {

				if ( ! isset( $opts[ $opt_pre . '_img_height' ] ) ) {	// Just in case;

					continue;
				}

				$img_height = $opts[ $opt_pre . '_img_height' ];
				$img_crop   = isset( $opts[ $opt_pre . '_img_crop' ] ) ? $opts[ $opt_pre . '_img_crop' ] : 0;

				/**
				 * If sanitizing for a module, load any missing width / height values.
				 */
				if ( false !== $mod ) {

					if ( empty( $img_width ) && isset( $this->p->options[ $opt_pre . '_img_width' ] ) ) {

						$img_width = $this->p->options[ $opt_pre . '_img_width' ];
					}

					if ( empty( $img_height ) && isset( $this->p->options[ $opt_pre . '_img_height' ] ) ) {

						$img_height = $this->p->options[ $opt_pre . '_img_height' ];
					}
				}

				/**
				 * Both width and height are required to calculate and check the aspect ratio.
				 */
				if ( empty( $img_width ) || empty( $img_height ) ) {

					continue;
				}

				$img_ratio = $img_width >= $img_height ? $img_width / $img_height : $img_height / $img_width;
				$img_ratio = number_format( $img_ratio, 3, '.', '' );

				foreach ( array( 'limit', 'limit_max' ) as $limit_type ) {

					if ( ! isset( $this->p->cf[ 'head' ][ $limit_type ][ $opt_pre . '_img_ratio' ] ) ) {

						continue;
					}

					$notice_msg = false;

					$limit_ratio = number_format( $this->p->cf[ 'head' ][ $limit_type ][ $opt_pre . '_img_ratio' ], 3, '.', '' );

					switch ( $limit_type ) {

						case 'limit':

							$opts[ $opt_pre . '_img_crop' ]    = 1;
							$opts[ $opt_pre . '_img_crop:is' ] = 'disabled';	// Prevent changes in settings page.

							if ( $img_ratio !== $limit_ratio ) {

								$notice_msg = sprintf( __( 'Option keys "%1$s" (%2$d) and "%3$s" (%4$d) have an aspect ratio of %5$s:1, which not equal to the required image ratio of %6$s:1.', 'wpsso' ), $opt_pre . '_img_width', $img_width, $opt_pre . '_img_height', $img_height, $img_ratio, $limit_ratio );
							}

							break;

						case 'limit_max':

							if ( $img_crop && $img_ratio >= $limit_ratio ) {

								$notice_msg = sprintf( __( 'Option keys "%1$s" (%2$d) and "%3$s" (%4$d) have an aspect ratio of %5$s:1, which is equal to / or greater than the maximum image ratio of %6$s:1.', 'wpsso' ), $opt_pre . '_img_width', $img_width, $opt_pre . '_img_height', $img_height, $img_ratio, $limit_ratio );
							}

							break;
					}

					if ( $notice_msg ) {

						$notice_msg .= ' ' . __( 'These options have been reset to their default values.', 'wpsso' );

						$this->p->notice->err( $notice_msg );

						$opts[ $opt_pre . '_img_width' ]  = $defs[ $opt_pre . '_img_width' ];
						$opts[ $opt_pre . '_img_height' ] = $defs[ $opt_pre . '_img_height' ];
						$opts[ $opt_pre . '_img_crop' ]   = $defs[ $opt_pre . '_img_crop' ];
					}
				}
			}

			/**
			 * og_desc_max_len must be at least 160 chars (defined in config).
			 */
			if ( isset( $opts[ 'og_desc_max_len' ] ) && $opts[ 'og_desc_max_len' ] < $this->p->cf[ 'head' ][ 'limit_min' ][ 'og_desc_len' ] )  {

				$opts[ 'og_desc_max_len' ] = $this->p->cf[ 'head' ][ 'limit_min' ][ 'og_desc_len' ];
			}

			/**
			 * Remove the SEO description if a known SEO plugin is active.
			 */
			if ( isset( $opts[ 'seo_desc' ] ) && ! empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

				unset( $opts[ 'seo_desc' ] );
			}

			if ( false === $mod ) {

				/**
				 * Check the Facebook App ID value.
				 */
				if ( ! empty( $opts[ 'fb_app_id' ] ) && ( ! is_numeric( $opts[ 'fb_app_id' ] ) || strlen( $opts[ 'fb_app_id' ] ) > 32 ) ) {

					$this->p->notice->err( sprintf( __( 'The Facebook App ID must be numeric and 32 characters or less in length - the value of "%s" is not valid.', 'wpsso' ), $opts[ 'fb_app_id' ] ) );
				}

				/**
				 * If the plugin_check_head option is disabled, then delete the check counter.
				 */
				if ( ! $network ) {

					if ( empty( $this->p->options[ 'plugin_check_head' ] ) ) {

						delete_option( WPSSO_POST_CHECK_COUNT_NAME );
					}
				}
			}

			/**
			 * Skip refreshing the image URL dimensions if saving network options.
			 */
			if ( ! $network ) {

				$this->refresh_image_url_sizes( $opts );	// $opts passed by reference.
			}

			/**
			 * The options array should not contain any numeric keys.
			 */
			SucomUtil::unset_numeric_keys( $opts );

			return $opts;
		}

		/**
		 * Save both options and site options.
		 */
		public function save_options( $options_name, array $opts, $network = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Make sure we have something to work with.
			 */
			if ( empty( $opts ) || ! is_array( $opts ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: options variable is empty and/or not array' );
				}

				return false;
			}

			/**
			 * $upgrading will be true when the options version, not the plugin version, is being upgraded.
			 */
			$is_new_options  = empty( $opts[ 'options_version' ] ) ? true : false;	// Example: -wpsso512pro-wpssoum3gpl
			$current_version = $is_new_options ? 0 : $opts[ 'options_version' ];	// Example: -wpsso512pro-wpssoum3gpl
			$latest_version  = $this->p->cf[ 'opt' ][ 'version' ];
			$upgrading       = $is_new_options || $current_version !== $latest_version ? true : false;

			$opts = apply_filters( 'wpsso_save_setting_options', $opts, $network, $upgrading );

			/**
			 * Save plugin version and option version.
			 */
			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( isset( $info[ 'version' ] ) ) {

					$version_key = 'plugin_' . $ext . '_version';

					$opts[ $version_key ] = $info[ 'version' ];
				}

				if ( isset( $info[ 'opt_version' ] ) ) {

					$opt_version_key = 'plugin_' . $ext . '_opt_version';

					$opts[ $opt_version_key ] = $info[ 'opt_version' ];
				}
			}

			$opts[ 'options_version' ] = $latest_version;	// Mark the new options array as current.

			/**
			 * Since WPSSO Core v8.5.1.
			 *
			 * Avoid saving the disabled status.
			 *
			 * Example: add_meta_name_robots:is = 'disabled'
			 */
			foreach ( preg_grep( '/:is$/', array_keys( $opts ) ) as $key ) {

				if ( 'disabled' === $opts[ $key ] ) {

					unset( $opts[ $key ] );
				}
			}

			if ( $network ) {

				if ( $saved = update_site_option( $options_name, $opts ) ) {	// Auto-creates options with autoload no.

					$this->p->site_options = $opts;				// Update the current plugin options array.
				}

			} else {

				if ( $saved = update_option( $options_name, $opts ) ) {		// Auto-creates options with autoload yes.

					$this->p->options = $opts;				// Update the current plugin options array.
				}
			}

			if ( $saved ) {

				if ( $upgrading ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $options_name . ' settings have been upgraded and saved' );
					}

					if ( is_admin() ) {

						$user_id = get_current_user_id();

						$this->p->util->cache->schedule_clear( $user_id );

						$notice_msg = '<strong>' . __( 'Plugin settings have been upgraded and saved.', 'wpsso' ) . '</strong> ';

						$notice_msg .= __( 'A background task will begin shortly to clear all caches.', 'wpsso' );

						$notice_key = 'task-will-begin-to-clear-all-caches';	// Common key to prevent duplicate clear all caches messages.

						$this->p->notice->upd( $notice_msg, $user_id, $notice_key );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( $options_name . ' settings have been saved silently' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'wordpress failed to save the ' . $options_name . ' settings' );
				}
			}

			return $saved;
		}

		/**
		 * Complete the options array for any custom post types and/or custom taxonomies.
		 *
		 * Called by $this->get_defaults() and $this->check_options();
		 */
		private function add_post_type_taxonomy_name_options( array &$opts ) {	// Pass by reference is OK.

			/**
			 * Add options using a key prefix array and post type names.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding options derived from post type names' );
			}

			$this->p->util->add_post_type_names( $opts, array(
				'og_type_for'                => 'article',	// Advanced Settings > Document Types > Open Graph > Type by Post Type.
				'plugin_add_to'              => 1,		// Advanced Settings > Plugin Settings > Interface > Show Document SSO Metabox.
				'plugin_ratings_reviews_for' => 0,		// Advanced Settings > Service APIs > Ratings and Reviews > Get Reviews for Post Type.
				'plugin_sitemaps_for'        => 1,		// Advanced Settings > WordPress Sitemaps > Post Types > Include Post Type.
				'schema_type_for'            => 'webpage',	// Advanced Settings > Document Types > Schema > Type by Post Type.
			) );

			/**
			 * Add options using a key prefix array and term names.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding options derived from term names' );
			}

			$this->p->util->add_taxonomy_names( $opts, array(
				'og_type_for_tax'     => 'website',	// Advanced Settings > Document Types > Open Graph > Type by Taxonomy.
				'plugin_add_to_tax'   => 1,		// Advanced Settings > Plugin Settings > Interface > Show Document SSO Metabox.
				'schema_type_for_tax' => 'item.list',	// Advanced Settings > Document Types > Schema > Type by Taxonomy.
			) );
		}

		/**
		 * Update the width / height of remote image URLs.
		 */
		private function refresh_image_url_sizes( array &$opts ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Remove all custom field names first, to exclude 'plugin_cf_img_url' and 'plugin_cf_vid_url'.
			 */
			$img_url_keys = preg_grep( '/^plugin_cf_/', array_keys( $opts ), PREG_GREP_INVERT );

			/**
			 * Allow for multi-option keys, like 'place_img_url_1'.
			 */
			$img_url_keys = preg_grep( '/_(img|logo|banner)_url(_[0-9]+)?(#[a-zA-Z_]+)?$/', $img_url_keys );

			/**
			 * Add correct image sizes for the image URL using getimagesize().
			 *
			 * Note that PHP v7.1 or better is required to get the image size of WebP images.
			 */
			$this->p->util->add_image_url_size( $opts, $img_url_keys );

			$this->check_banner_image_size( $opts, $img_pre = 'site_org_banner' );
		}

		private function check_banner_image_size( $opts, $img_pre = 'site_org_banner' ) {

			/**
			 * Skip if notices have already been shown.
			 */
			if ( ! $this->p->notice->is_admin_pre_notices() ) {

				return;
			}

			$settings_page_link = $this->p->util->get_admin_url( 'essential#sucom-tabset_essential-tab_google',
				_x( 'Organization Banner URL', 'option label', 'wpsso' ) );

			/**
			 * Returns an image array:
			 *
			 * array(
			 *	'og:image:url'       => null,
			 *	'og:image:width'     => null,
			 *	'og:image:height'    => null,
			 *	'og:image:cropped'   => null,
			 *	'og:image:id'        => null,
			 *	'og:image:alt'       => null,
			 *	'og:image:size_name' => null,
			 * );
			 */
			$mt_single_image = $this->p->media->get_mt_img_pre_url( $opts, $img_pre );

			$image_url = SucomUtil::get_first_mt_media_url( $mt_single_image );

			if ( ! empty( $image_url ) ) {

				$image_href    = '<a href="' . $image_url . '">' . $image_url . '</a>';
				$image_dims    = $mt_single_image[ 'og:image:width' ] . 'x' . $mt_single_image[ 'og:image:height' ] . 'px';
				$required_dims = '600x60px';

				if ( $image_dims !== $required_dims ) {

					if ( '-1x-1px' === $image_dims ) {

						$error_msg = sprintf( __( 'The %s image dimensions cannot be determined.',
							'wpsso' ), $settings_page_link ) . ' ';

						$error_msg .= sprintf( __( 'Please make sure this site can access %s using the PHP getimagesize() function.',
							'wpsso' ), $image_href );

					} else {

						$error_msg = sprintf( __( 'The %1$s image dimensions are %2$s and must be exactly %3$s.',
							'wpsso' ), $settings_page_link, $image_dims, $required_dims ) . ' ';

						$error_msg .= sprintf( __( 'Please correct the %s banner image.',
							'wpsso' ), $image_href );
					}

					$this->p->notice->err( $error_msg );
				}
			}
		}

		private function check_value( $opt_key, $base_key, $opt_val, $def_val, $network, $mod ) {

			if ( is_array( $opt_val ) ) {

				return $opt_val;
			}

			/**
			 * Hooked by WpssoOptions->filter_option_type() and several add-ons.
			 */
			$option_type = apply_filters( 'wpsso_option_type', $option_type = false, $base_key, $network, $mod );

			/**
			 * Translate error messages only once.
			 */
			static $errors_transl = null;

			if ( null === $errors_transl ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'translating error messages' );
				}

				$errors_transl = array(
					'api_key'   => __( 'The value of option "%s" must be alpha-numeric - resetting this option to its default value.', 'wpsso' ),
					'blank_num' => __( 'The value of option "%s" must be blank or numeric - resetting this option to its default value.', 'wpsso' ),
					'color'     => __( 'The value of option "%s" must be a CSS color code - resetting this option to its default value.', 'wpsso' ),
					'csv_urls'  => __( 'The value of option "%s" must be a comma-delimited list of URL(s) - resetting this option to its default value.', 'wpsso' ),
					'date'      => __( 'The value of option "%s" must be a yyyy-mm-dd date - resetting this option to its default value.', 'wpsso' ),
					'html'      => __( 'The value of option "%s" must be HTML code - resetting this option to its default value.', 'wpsso' ),
					'img_id'    => __( 'The value of option "%s" must be an image ID - resetting this option to its default value.', 'wpsso' ),
					'img_url'   => __( 'The value of option "%s" must be a valid image URL - resetting this option to its default value.', 'wpsso' ),
					'not_blank' => __( 'The value of option "%s" cannot be an empty string - resetting this option to its default value.', 'wpsso' ),
					'numeric'   => __( 'The value of option "%s" must be numeric - resetting this option to its default value.', 'wpsso' ),
					'pos_num'   => __( 'The value of option "%1$s" must be equal to or greather than %2$s - resetting this option to its default value.', 'wpsso' ),
					'time'      => __( 'The value of option "%s" must be a hh:mm time - resetting this option to its default value.', 'wpsso' ),
					'url'       => __( 'The value of option "%s" must be a valid URL - resetting this option to its default value.', 'wpsso' ),
				);
			}

			/**
			 * Pre-filter most values to remove html.
			 */
			switch ( $option_type ) {

				case 'ignore':

					return $opt_val;	// Stop here.

					break;

				case 'html':	// Leave html, css, and javascript code blocks as-is.
				case 'code':	// Code values cannot be blank.
				case 'preg':	// A regular expression.

					$opt_val = preg_replace( '/[\r]+/', '', $opt_val );

					break;

				default:

					$opt_val = wp_filter_nohtml_kses( $opt_val );	// Strips all the HTML in the content.
					$opt_val = stripslashes( $opt_val );		// Strip slashes added by wp_filter_nohtml_kses().

					break;
			}

			/**
			 * Optional cast on return.
			 */
			$ret_int  = false;
			$ret_fnum = false;
			$num_prec = 0;

			if ( 0 === strpos( $option_type, 'fnum' ) ) {

				$num_prec = substr( $option_type, 4 );

				$option_type = 'fnum';
			}

			switch ( $option_type ) {

				/**
				 * Empty or alpha-numeric (upper or lower case), plus underscores and hypens.
				 */
				case 'api_key':

					$opt_val = trim( $opt_val );	// Removed extra spaces from copy-paste.

					if ( '' !== $opt_val && preg_match( '/[^a-zA-Z0-9_\-]/', $opt_val ) ) {

						$this->p->notice->err( sprintf( $errors_transl[ 'api_key' ], $opt_key ) );

						$opt_val = $def_val;
					}

					break;

				/**
				 * Twitter-style usernames (prepend with an @ character).
				 */
				case 'at_name':

					if ( '' !== $opt_val ) {

						$opt_val = SucomUtil::get_at_name( $opt_val );
					}

					break;

				/**
				 * Empty or alpha-numeric uppercase (hyphens are allowed as well). Silently convert illegal
				 * characters to single hyphens and trim excess.
				 */
				case 'auth_id':

					$opt_val = trim( preg_replace( '/[^A-Z0-9\-]+/', '-', $opt_val ), '-' );

					$opt_val = preg_replace( '/^ID-/', '', $opt_val );	// Just in case.

					break;

				/**
				 * Applies sanitize_title_with_dashes().
				 */
				case 'dashed':

					$opt_val = trim( sanitize_title_with_dashes( $opt_val ) );

					break;

				/**
				 * Must be blank or integer / numeric.
				 */
				case 'blank_int':

					$ret_int = true;

					// No break.

				case 'blank_num':

					if ( '' === $opt_val ) {

						$ret_int = false;

					} elseif ( ! is_numeric( $opt_val ) ) {

						$this->p->notice->err( sprintf( $errors_transl[ 'blank_num' ], $opt_key ) );

						$opt_val = $def_val;

						if ( '' === $opt_val ) {

							$ret_int = false;
						}
					}

					break;

				/**
				 * Options that cannot be blank (aka empty string).
				 */
				case 'code':
				case 'not_blank':
				case 'not_blank_quiet':

					if ( '' === $opt_val && '' !== $def_val ) {

						if ( false === strpos( $option_type, '_quiet' ) ) {

							$this->p->notice->err( sprintf( $errors_transl[ 'not_blank' ], $opt_key ) );
						}

						$opt_val = $def_val;
					}

					break;

				case 'csv_blank':

					if ( '' !== $opt_val ) {

						$opt_val = implode( ', ', SucomUtil::explode_csv( $opt_val ) );
					}

					break;

				case 'csv_urls':

					if ( '' !== $opt_val ) {

						$parts = array();

						foreach ( SucomUtil::explode_csv( $opt_val ) as $part ) {

							$part = SucomUtil::decode_html( $part );	// Just in case.

							if ( false === filter_var( $part, FILTER_VALIDATE_URL ) ) {

								$this->p->notice->err( sprintf( $errors_transl[ 'csv_urls' ], $opt_key ) );

								$opt_val = $def_val;

								break;

							} else {
								$parts[] = $part;
							}
						}

						$opt_val = implode( ', ', $parts );
					}

					break;

				/**
				 * Text strings that can be blank (line breaks are removed).
				 */
				case 'desc':
				case 'one_line':
				case 'preg':	// A regular expression.

					if ( '' !== $opt_val ) {

						$opt_val = trim( preg_replace( '/[\s\n\r]+/s', ' ', $opt_val ) );
					}

					break;

				/**
				 * Must be a floating-point number. The decimal precision defined before the switch() statement.
				 */
				case 'fnum':

					$ret_fnum = true;

					if ( ! is_numeric( $opt_val ) ) {

						$this->p->notice->err( sprintf( $errors_transl[ 'numeric' ], $opt_key ) );

						$opt_val = $def_val;
					}

					break;

				/**
				 * Empty string or must include at least one HTML tag.
				 */
				case 'html':

					if ( '' !== $opt_val ) {

						$opt_val = trim( $opt_val );

						if ( ! preg_match( '/<.*>/', $opt_val ) ) {

							$this->p->notice->err( sprintf( $errors_transl[ 'html' ], $opt_key ) );

							$opt_val = $def_val;
						}
					}

					break;

				/**
				 * Empty string or an image ID.
				 */
				case 'img_id':

					if ( '' !== $opt_val ) {

						if ( ! preg_match( '/^[0-9]+$/', $opt_val ) ) {

							$this->p->notice->err( sprintf( $errors_transl[ 'img_id' ], $opt_key ) );

							$opt_val = $def_val;
						}
					}

					break;

				/**
				 * Must be integer / numeric.
				 */
				case 'int':
				case 'integer':

					$ret_int = true;

					// No break.

				case 'numeric':

					if ( ! is_numeric( $opt_val ) ) {

						$this->p->notice->err( sprintf( $errors_transl[ 'numeric' ], $opt_key ) );

						$opt_val = $def_val;
					}

					break;

				/**
				 * Integer / numeric options that must be 1 or more (not zero).
				 */
				case 'img_height':	// Image height, subject to minimum value (typically, at least 200px).
				case 'img_width':	// Image height, subject to minimum value (typically, at least 200px).
				case 'pos_int':
				case 'pos_integer':

					$ret_int = true;

					// No break.

				case 'pos_num':
				case 'pos_number':

					/**
					 * Check for a hard-coded minimum value (for example, 200 for "og_img_width").
					 */
					if ( isset( $this->p->cf[ 'head' ][ 'limit_min' ][ $base_key ] ) ) {

						$min_int = $this->p->cf[ 'head' ][ 'limit_min' ][ $base_key ];

					} else {

						$min_int = 1;
					}

					if ( ! empty( $mod[ 'name' ] ) && '' === $opt_val ) {	// Custom meta options can be empty.

						$ret_int = false;

					} elseif ( ! is_numeric( $opt_val ) || $opt_val < $min_int ) {

						$this->p->notice->err( sprintf( $errors_transl[ 'pos_num' ], $opt_key, $min_int ) );

						$opt_val = $def_val;
					}

					break;

				case 'color':
				case 'date':	// Empty or 'none' string, or date as yyyy-mm-dd.
				case 'time':	// Empty or 'none' string, or time as hh:mm or hh:mm:ss.

					$fmt = false;

					$opt_val = trim( $opt_val );

					if ( 'color' === $option_type ) {

						$fmt = '/^#[a-fA-f0-9]{6,6}$/';				// Color as #000000.

					} elseif ( 'date' === $option_type ) {

						$fmt = '/^[0-9]{4,4}-[0-9]{2,2}-[0-9]{2,2}$/';		// Date as yyyy-mm-dd.

					} elseif ( 'time' === $option_type ) {

						$fmt = '/^[0-9]{2,2}:[0-9]{2,2}(:[0-9]{2,2})?$/';	// Time as hh:mm or hh:mm:ss.
					}

					if ( '' !== $opt_val && 'none' !== $opt_val && $fmt && ! preg_match( $fmt, $opt_val ) ) {

						$this->p->notice->err( sprintf( $errors_transl[ $option_type ], $opt_key ) );

						$opt_val = $def_val;
					}

					break;

				/**
				 * Text strings that can be blank.
				 */
				case 'ok_blank':

					if ( '' !== $opt_val ) {

						$opt_val = trim( $opt_val );
					}

					break;

				/**
				 * Must be empty or texturized.
				 */
				case 'textured':

					if ( '' !== $opt_val ) {

						$opt_val = trim( wptexturize( ' ' . $opt_val . ' ' ) );
					}

					break;

				/**
				 * Empty string or a URL.
				 *
				 * Note that WebP is only supported since PHP v7.1.
				 */
				case 'img_url':

					if ( '' !== $opt_val ) {

						if ( ! $this->p->util->is_image_url( $opt_val ) ) {

							$this->p->notice->err( sprintf( $errors_transl[ 'img_url' ], $opt_key ) );

							$opt_val = $def_val;
						}
					}

				case 'url':

					if ( '' !== $opt_val ) {

						$opt_val = SucomUtil::decode_html( $opt_val );	// Just in case.

						if ( false === filter_var( $opt_val, FILTER_VALIDATE_URL ) ) {

							$this->p->notice->err( sprintf( $errors_transl[ 'url' ], $opt_key ) );

							$opt_val = $def_val;
						}
					}

					break;

				/**
				 * Strip leading URLs off facebook usernames.
				 */
				case 'url_base':

					if ( '' !== $opt_val ) {

						$opt_val = preg_replace( '/(http|https):\/\/[^\/]*?\//', '', $opt_val );
					}

					break;

				/**
				 * Everything else is a 1 or 0 checkbox option.
				 */
				case 'checkbox':
				default:

					if ( $def_val === 0 || $def_val === 1 ) {	// Make sure the default option is also a 1 or 0, just in case.

						$opt_val = empty( $opt_val ) ? 0 : 1;
					}

					break;
			}

			if ( $ret_int ) {

				$opt_val = intval( $opt_val );

			} elseif ( $ret_fnum ) {

				$opt_val = sprintf( '%.' . $num_prec . 'f', $opt_val );
			}

			return $opt_val;
		}

		/**
		 * Deprecated on 2021/09/15.
		 */
		public static function can_cache() {

			_deprecated_function( __METHOD__ . '()', '2020/07/07', $replacement = 'WpssoOptions::is_cache_allowed()' );	// Deprecation message.

			return self::is_cache_allowed();
		}
	}
}
