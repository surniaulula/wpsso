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

if ( ! class_exists( 'WpssoOptions' ) ) {

	class WpssoOptions {

		private $p;		// Wpsso class object.
		private $upg;		// WpssoOptionsUpgrade class object.
		private $filters;	// WpssoOptionsFilters class object.

		private static $cache_allowed = false;

		/*
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

		/*
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

			static $local_cache = array();

			$is_cache_allowed = self::is_cache_allowed();

			if ( empty( $local_cache ) || ! $is_cache_allowed ) {

				$local_cache = $this->p->cf[ 'opt' ][ 'defaults' ];
			}

			if ( $force_filter || empty( $local_cache[ 'opt_filtered' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'get_defaults filters' );	// Begin timer.
				}

				/*
				 * Set before calling filters to prevent recursion.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'setting opt_filtered to 1' );
				}

				$local_cache[ 'opt_filtered' ] = 1;

				/*
				 * If there is a plugin auth method configured, make sure the option key exists.
				 */
				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( ! empty( $info[ 'update_auth' ] ) && 'none' !== $info[ 'update_auth' ] ) {	// Just in case.

						$local_cache[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ] ] = '';
					}
				}

				/*
				 * Complete the options array for custom post types and taxonomies.
				 */
				$this->add_custom_post_tax_options( $local_cache );

				/*
				 * Define the default organization or person ID for Knowledge Graph markup in the home page.
				 */
				switch ( $this->p->options[ 'site_pub_schema_type' ] ) {

					case 'person':

						$local_cache[ 'schema_def_pub_org_id' ]    = 'none';
						$local_cache[ 'schema_def_pub_person_id' ] = $this->p->options[ 'site_pub_person_id' ];

						break;

					case 'organization':

						$local_cache[ 'schema_def_pub_org_id' ]    = 'site';
						$local_cache[ 'schema_def_pub_person_id' ] = 'none';

						break;
				}

				/*
				 * Update default place options based on the open graph defaults.
				 */
				$local_cache[ 'schema_def_place_country' ]  = $this->p->options[ 'og_def_country' ];
				$local_cache[ 'schema_def_place_timezone' ] = $this->p->options[ 'og_def_timezone' ];

				/*
				 * Get translated strings for variable based options.
				 */
				$this->set_default_text( $local_cache, 'plugin_title_part_site' );	// Title Tag Site Prefix / Suffix.
				$this->set_default_text( $local_cache, 'plugin_title_part_tagline' );	// Title Tag Tagline Prefix / Suffix.
				$this->set_default_text( $local_cache, 'plugin_img_alt_prefix' );	// Content Image Alt Prefix.
				$this->set_default_text( $local_cache, 'plugin_p_cap_prefix' );		// WP Caption Text Prefix.
				$this->set_default_text( $local_cache, 'plugin_comment_title' );	// Comment Title.
				$this->set_default_text( $local_cache, 'plugin_comment_reply_title' );	// Reply Comment Title.
				$this->set_default_text( $local_cache, 'plugin_comment_review_title' );	// Review Comment Title.
				$this->set_default_text( $local_cache, 'plugin_product_var_title' );	// Product Variation Title.
				$this->set_default_text( $local_cache, 'plugin_404_page_title' );	// 404 Page Title.
				$this->set_default_text( $local_cache, 'plugin_404_page_desc' );	// 404 Page Description.
				$this->set_default_text( $local_cache, 'plugin_no_title_text' );	// No Title Text.
				$this->set_default_text( $local_cache, 'plugin_no_desc_text' );		// No Description Text.
				$this->set_default_text( $local_cache, 'plugin_term_page_title' );	// Term Archive Title.
				$this->set_default_text( $local_cache, 'plugin_term_page_desc' );	// Term Archive Description.
				$this->set_default_text( $local_cache, 'plugin_author_page_title' );	// Author Archive Title.
				$this->set_default_text( $local_cache, 'plugin_author_page_desc' );	// Author Archive Description.
				$this->set_default_text( $local_cache, 'plugin_search_page_title' );	// Search Results Title.
				$this->set_default_text( $local_cache, 'plugin_search_page_desc' );	// Search Results Description.
				$this->set_default_text( $local_cache, 'plugin_year_page_title' );	// Year Archive Title.
				$this->set_default_text( $local_cache, 'plugin_year_page_desc' );	// Year Archive Description.
				$this->set_default_text( $local_cache, 'plugin_month_page_title' );	// Month Archive Title.
				$this->set_default_text( $local_cache, 'plugin_month_page_desc' );	// Month Archive Description.
				$this->set_default_text( $local_cache, 'plugin_day_page_title' );	// Day Archive Title.
				$this->set_default_text( $local_cache, 'plugin_day_page_desc' );	// Day Archive Description.

				/*
				 * Translate contact method field labels for current language.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'translating plugin contact field labels' );
				}

				SucomUtil::transl_key_values( '/^plugin_(cm_.*_label|.*_prefix)$/', $local_cache, 'wpsso' );

				/*
				 * Define the default Facebook locale and current locale values.
				 */
				$local_cache[ 'fb_locale' ] = $this->p->og->get_fb_locale( $mixed = 'default', $use_opts = false );

				$current_fb_locale_key = SucomUtil::get_key_locale( 'fb_locale' );

				if ( 'fb_locale' !== $current_fb_locale_key ) {

					$local_cache[ $current_fb_locale_key ] = $this->p->og->get_fb_locale( $mixed = 'current', $use_opts = false );
				}

				/*
				 * Import metadata and block attributes from known SEO plugins.
				 */
				foreach ( array(
					'aioseop'      => 'aioseo_options',			// All in One SEO Pack.
					'rankmath'     => 'rank-math-options-general',		// Rank Math SEO.
					'seoframework' => 'autodescription-site-settings',	// The SEO Framework.
					'wpmetaseo'    => 'wpmetaseo',				// WP Meta SEO.
					'wpseo'        => 'wpseo',				// Yoast SEO.
				) as $avail_key => $option_key ) {

					if ( ! empty( $this->p->avail[ 'seo' ][ $avail_key ] ) ||
						( empty( $this->p->avail[ 'seo' ][ 'any' ] ) && get_option( $option_key ) ) ) {

						foreach ( array( 'meta', 'blocks' ) as $import_type ) {

							/*
							 * Option key examples:
							 *
							 * 'plugin_import_aioseop_meta'
							 * 'plugin_import_rankmath_meta'
							 * 'plugin_import_seoframework_meta'
							 * 'plugin_import_wpmetaseo_meta'
							 * 'plugin_import_wpseo_meta'
							 * 'plugin_import_wpseo_blocks'
							 */
							$key = 'plugin_import_' . $avail_key . '_' . $import_type;

							if ( isset( $local_cache[ $key ] ) ) {	// Make sure the option exists.

								$local_cache[ $key ] = 1;
							}
						}
					}
				}

				/*
				 * Check for default values from network admin settings.
				 */
				if ( is_multisite() && is_array( $this->p->site_options ) ) {

					foreach ( $this->p->site_options as $site_opt_key => $site_opt_val ) {

						if ( isset( $local_cache[ $site_opt_key ] ) && isset( $this->p->site_options[ $site_opt_key . ':use' ] ) ) {

							if ( 'default' === $this->p->site_options[ $site_opt_key . ':use' ] ) {

								$local_cache[ $site_opt_key ] = $this->p->site_options[ $site_opt_key ];
							}
						}
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying get_defaults filters' );
				}

				$local_cache = apply_filters( 'wpsso_get_defaults', $local_cache );

				if ( ! $is_cache_allowed ) {

					unset( $local_cache[ 'opt_filtered' ] );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'get_defaults filters' );	// End timer.
				}
			}

			if ( false !== $opt_key ) {

				if ( isset( $local_cache[ $opt_key ] ) ) {

					return $local_cache[ $opt_key ];
				}

				return null;
			}

			return $local_cache;
		}

		public function get_site_defaults( $opt_key = false, $force_filter = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'opt_key'      => $opt_key,
					'force_filter' => $force_filter,
				) );
			}

			static $local_cache = array();

			$is_cache_allowed = self::is_cache_allowed();

			if ( empty( $local_cache ) || ! $is_cache_allowed ) {

				/*
				 * Automatically include all advanced plugin options.
				 */
				$local_cache = SucomUtil::preg_grep_keys( '/^plugin_/', $this->p->cf[ 'opt' ][ 'defaults' ] );

				/*
				 * Add a "Site Use" for each option.
				 */
				foreach ( $local_cache as $key => $val ) {

					if ( false === strpos( $key, ':' ) ) {	// Just in case.

						$local_cache[ $key . ':use' ] = 'default';
					}
				}

				$local_cache = array_merge( $local_cache, $this->p->cf[ 'opt' ][ 'site_defaults' ] );
			}

			if ( $force_filter || empty( $local_cache[ 'opt_filtered' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'get_site_defaults filters' );	// Begin timer.
				}

				/*
				 * Set before calling filters to prevent recursion.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'setting opt_filtered to 1' );
				}

				$local_cache[ 'opt_filtered' ] = 1;

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( ! empty( $info[ 'update_auth' ] ) && $info[ 'update_auth' ]!== 'none' ) {	// Just in case.

						$local_cache[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ] ] = '';

						$local_cache[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ] . ':use' ] = 'default';
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying get_site_defaults filters' );
				}

				$local_cache = apply_filters( 'wpsso_get_site_defaults', $local_cache );

				if ( ! $is_cache_allowed ) {

					unset( $local_cache[ 'opt_filtered' ] );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'get_site_defaults filters' );	// End timer.
				}
			}

			if ( false !== $opt_key ) {

				if ( isset( $local_cache[ $opt_key ] ) ) {

					return $local_cache[ $opt_key ];
				}

				return null;
			}

			return $local_cache;
		}

		/*
		 * Returns a checked, fixed, and/or upgraded options array.
		 */
		public function check_options( $options_name, array $opts, $network = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'checking options' );	// Begin timer.
			}

			$defs          = null;	// Optimize and only get the defaults array when needed.
			$fixed         = array();
			$save_changes  = false;
			$is_plugin_upg = $this->is_plugin_upgrading( $opts );	// Existing plugin versions have changed.
			$is_option_upg = $this->is_upgrade_required( $opts );	// Existing option versions have changed.

			/*
			 * Upgrade the options array if necessary (rename or remove keys).
			 */
			if ( $is_option_upg ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'upgrading the ' . $options_name . ' settings' );
				}

				if ( ! is_object( $this->upg ) ) {

					require_once WPSSO_PLUGINDIR . 'lib/upgrade.php';

					$this->upg = new WpssoOptionsUpgrade( $this->p );
				}

				$defs = $network ? $this->get_site_defaults() : $this->get_defaults();

				$opts = $this->upg->options( $options_name, $opts, $defs, $network );
			}

			/*
			 * Complete the options array for any custom post types and/or custom taxonomies.
			 */
			$this->add_custom_post_tax_options( $opts );

			/*
			 * Note that generator meta tags are required for plugin support.
			 *
			 * If you disable the generator meta tags, requests for plugin support will be denied.
			 */
			$fixed[ 'add_meta_name_generator' ] = SucomUtil::get_const( 'WPSSO_META_GENERATOR_DISABLE' ) ? 0 : 1;

			/*
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

			$fixed[ 'site_org_schema_type' ] = $site_org_type_id;

			/*
			 * Include VAT in Product Prices.
			 *
			 * Allow the WPSSO_PRODUCT_PRICE_INCLUDE_VAT constant to override the 'plugin_product_include_vat' value.
			 */
			if ( defined( 'WPSSO_PRODUCT_PRICE_INCLUDE_VAT' ) ) {

				$fixed[ 'plugin_product_include_vat' ] = WPSSO_PRODUCT_PRICE_INCLUDE_VAT ? 1 : 0;
			}

			/*
			 * Adjust / cleanup non-network options.
			 */
			if ( ! $network ) {

				/*
				 * Adjust SEO options.
				 */
				if ( empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {	// No SEO plugin active.

					if ( empty( $opts[ 'plugin_wpsso_tid' ] ) ) {

						$fixed[ 'add_link_rel_canonical' ]    = 1;
						$fixed[ 'add_meta_name_description' ] = 1;
						$fixed[ 'add_meta_name_robots' ]      = 1;
						$fixed[ 'plugin_title_tag' ]          = 'seo_title';
					}

				} else {	// An SEO plugin is active.

					$fixed[ 'add_link_rel_canonical' ]    = 0;
					$fixed[ 'add_meta_name_description' ] = 0;
					$fixed[ 'add_meta_name_robots' ]      = 0;
					$fixed[ 'plugin_title_tag' ]          = 'wp_title';

					foreach ( array(
						'aioseop',	// All in One SEO Pack.
						'rankmath',	// Rank Math SEO.
						'seoframework',	// The SEO Framework.
						'wpseo',	// Yoast SEO.
					) as $avail_key ) {

						/*
						 * Disable the metadata import of all others (ie. non-active SEO plugins).
						 */
						if ( empty( $this->p->avail[ 'seo' ][ $avail_key ] ) ) {

							foreach ( array( 'meta', 'blocks' ) as $import_type ) {

								$key = 'plugin_import_' . $avail_key . '_' . $import_type;

								if ( isset( $opts[ $key ] ) ) { // Make sure the option exists.

									$fixed[ $key ] = 0;
								}
							}
						}
					}
				}

				/*
				 * Fixed value / unchangeable options.
				 */
				foreach ( array( 'og:image', 'og:video' ) as $mt_name ) {

					$fixed[ 'add_meta_property_' . $mt_name . ':secure_url' ] = 0;	// Always unchecked.
					$fixed[ 'add_meta_property_' . $mt_name . ':url' ]        = 0;	// Always unchecked.
					$fixed[ 'add_meta_property_' . $mt_name ]                 = 1;	// Always checked (canonical URL).
				}

				/*
				 * Check for website verification IDs and enable/disable meta tags as required.
				 */
				foreach ( WpssoConfig::$cf[ 'opt' ][ 'site_verify_meta_names' ] as $site_verify => $meta_name ) {

					$fixed[ 'add_meta_name_' . $meta_name ] = empty( $opts[ $site_verify ] ) ? 0 : 1;
				}

				/*
				 * Check for incompatible options between plugin versions.
				 */
				if ( $is_plugin_upg ) {

					if ( empty( $opts[ 'plugin_wpsso_tid' ] ) ) {

						if ( null === $defs ) {	// Optimize and only get the defaults array when needed.

							$defs = $network ? $this->get_site_defaults() : $this->get_defaults();
						}

						$adv_include = array(
							'add_.*',
							'og_type_for_.*',
							'plugin_.*',
							'schema_def_.*',
							'schema_type_for_.*',
							'.*_img_(width|height|crop|crop_x|crop_y)',
						);

						$adv_exclude = array(
							'plugin_clean_on_uninstall',
							'plugin_load_mofiles',
							'plugin_cache_disable',
							'plugin_debug_html',
							'plugin_.*_tid',
						);

						$adv_check = SucomUtil::preg_grep_keys( '/^(' . implode( '|', $adv_include ) . ')$/', $defs );
						$adv_check = SucomUtil::preg_grep_keys( '/^(' . implode( '|', $adv_exclude ) . ')$/', $adv_check, $invert = true );

						foreach ( $fixed as $key => $val ) {

							unset( $adv_check[ $key ] );
						}

						foreach ( $adv_check as $key => $val ) {

							if ( ! isset( $opts[ $key ] ) || $opts[ $key ] !== $val ) {

								$opts[ $key ] = $val;

								$save_changes = true;	// Save the options.
							}
						}
					}
				}
			}

			/*
			 * Check if options need to be changed and saved.
			 *
			 * Disable these options as they would get changed back anyway.
			 */
			foreach ( $fixed as $key => $val ) {

				$opts[ $key . ':disabled' ] = true;

				if ( ! isset( $opts[ $key ] ) || $opts[ $key ] !== $val ) {

					$opts[ $key ] = $val;

					$save_changes = true;	// Save the options.
				}
			}

			/*
			 * Save options and show reminders.
			 */
			if ( $save_changes || $is_plugin_upg || $is_option_upg ) {

				$this->save_options( $options_name, $opts, $network );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'checking options' );	// End timer.
			}

			return $opts;
		}

		/*
		 * Sanitize and validate options, including both the plugin options and custom meta options arrays.
		 *
		 * Called by WpssoAdmin->registered_setting_sanitation().
		 * Called by WpssoAdmin->save_site_options().
		 * Called by WpssoAbstractWpMeta->get_submit_opts().
		 */
		public function sanitize( $opts = array(), $defs = array(), $network = false, $mod = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Add any missing options from the defaults, unless sanitizing for a module, in which case we do not
			 * complete the options array.
			 */
			if ( empty( $mod[ 'name' ] ) ) {

				if ( ! empty( $defs ) && is_array( $defs ) ) {	// Just in case.

					$opts = array_merge( $defs, $opts );	// Complete the array with default options.
				}
			}

			/*
			 * Sort the options to re-order 0, 1, 10, 2 suffixes as 0, 1, 2, 10.
			 */
			ksort( $opts, SORT_FLAG_CASE | SORT_NATURAL );

			/*
			 * Sanitize values.
			 */
			unset( $opts[ 'opt_filtered' ] );	// Just in case.

			foreach ( $opts as $opt_key => $opt_val ) {

				if ( empty( $opt_key ) ) {	// Just in case.

					continue;

				/*
				 * Maybe save the disabled option, but don't save the ':disabled' qualifier.
				 */
				} elseif ( false !== strpos( $opt_key, ':disabled' ) ) {

					unset( $opts[ $opt_key ] );

					continue;

				/*
				 * Ignore all other controller qualifiers (ie. ':use', ':width', ':height', etc.).
				 */
				} elseif ( strpos( $opt_key, ':' ) ) {

					continue;

				/*
				 * Ignore localized options with an empty string value and no default.
				 */
				} elseif ( strpos( $opt_key, '#' ) && ! isset( $defs[ $opt_key ] ) && '' === $opt_val ) {

					unset( $opts[ $opt_key ] );

					continue;
				}

				/*
				 * Match the base option name without option numbers and localization.
				 */
				$base_key = preg_replace( '/(_[0-9]+)?([#].*)?$/', '', $opt_key );

				/*
				 * Multi-options and localized options default to an empty string.
				 */
				$def_val = isset( $defs[ $opt_key ] ) ? $defs[ $opt_key ] : '';

				$opts[ $opt_key ] = $this->check_value( $opt_key, $base_key, $opt_val, $def_val, $network, $mod );
			}

			/*
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

				/*
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

				/*
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

					$notice_msg  = false;
					$limit_ratio = number_format( $this->p->cf[ 'head' ][ $limit_type ][ $opt_pre . '_img_ratio' ], 3, '.', '' );

					switch ( $limit_type ) {

						case 'limit':

							$opts[ $opt_pre . '_img_crop' ]          = 1;
							$opts[ $opt_pre . '_img_crop:disabled' ] = true;	// Prevent changes in settings page.

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

			if ( false === $mod ) {

				/*
				 * Check the Facebook App ID value.
				 */
				if ( ! empty( $opts[ 'fb_app_id' ] ) && ( ! is_numeric( $opts[ 'fb_app_id' ] ) || strlen( $opts[ 'fb_app_id' ] ) > 32 ) ) {

					$this->p->notice->err( sprintf( __( 'The Facebook App ID must be numeric and 32 characters or less in length - the value of "%s" is not valid.', 'wpsso' ), $opts[ 'fb_app_id' ] ) );
				}

				/*
				 * If the plugin_check_head option is disabled, then delete the check counter.
				 */
				if ( ! $network ) {

					if ( empty( $this->p->options[ 'plugin_check_head' ] ) ) {

						delete_option( WPSSO_POST_CHECK_COUNT_NAME );
					}
				}
			}

			/*
			 * Skip refreshing the image URL dimensions if saving network options.
			 */
			if ( ! $network ) {

				$this->refresh_image_url_sizes( $opts );	// $opts passed by reference.

				if ( empty( $mod[ 'name' ] ) ) {	// Only check when saving the plugin settings.

					$this->check_site_org_image_sizes( $opts );
				}
			}

			/*
			 * The options array should not contain any numeric keys.
			 */
			SucomUtil::unset_numeric_keys( $opts );

			return $opts;
		}

		/*
		 * Save both options and site options.
		 *
		 * Called by WpssoAdmin->load_setting_page() for the 'reload_default_image_sizes' action.
		 * Called by WpssoAdmin->import_plugin_settings_json().
		 */
		public function save_options( $options_name, array $opts, $network = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Make sure we have something to work with.
			 */
			if ( empty( $opts ) || ! is_array( $opts ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: options variable is empty and/or not array' );
				}

				return false;
			}

			$is_option_upg = $this->is_upgrade_required( $opts );	// Existing option versions have changed.

			/*
			 * $is_option_upg is true when the option versions, not the plugin versions, have changed.
			 */
			$opts = (array) apply_filters( 'wpsso_save_setting_options', $opts, $network, $is_option_upg );

			/*
			 * Add plugin and add-on option versions (ie. 'checksum', 'opt_checksum', and 'opt_versions').
			 */
			$this->p->opt->add_versions( $opts );	// Note that $opts must be an array.

			/*
			 * Since WPSSO Core v8.5.1.
			 *
			 * Don't save the disabled option status.
			 *
			 * Example: add_meta_name_robots:disabled = true
			 */
			foreach ( preg_grep( '/:disabled$/', array_keys( $opts ) ) as $key ) {

				unset( $opts[ $key ] );
			}

			if ( $network && $saved = update_site_option( $options_name, $opts ) ) {	// Auto-creates options with autoload no.

				$this->p->site_options = $opts;						// Update the current plugin options array.

			} elseif ( $saved = update_option( $options_name, $opts ) ) {			// Auto-creates options with autoload yes.

				$this->p->options = $opts;						// Update the current plugin options array.
			}

			if ( $saved ) {

				if ( $is_option_upg ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $options_name . ' settings have been upgraded and saved' );
					}

					if ( is_admin() ) {

						$user_id    = get_current_user_id();
						$notice_msg = '<strong>' . __( 'Plugin settings have been upgraded and saved.', 'wpsso' ) . '</strong> ';
						$notice_key = 'settings-upgraded-and-saved';

						$this->p->notice->upd( $notice_msg, $user_id, $notice_key );

						$this->p->util->cache->schedule_refresh( $user_id );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( $options_name . ' settings have been saved silently' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'wordpress failed to save the ' . $options_name . ' settings' );
			}

			return $saved;
		}

		public function get_version( array $opts, $ext ) {

			if ( isset( $opts[ 'opt_versions' ][ $ext ] ) ) {	// Check for current version key first.

				return (int) $opts[ 'opt_versions' ][ $ext ];

			} elseif ( isset( $opts[ 'plugin_' . $ext . '_opt_version' ] ) ) {	// Deprecated options version key.

				return (int) $opts[ 'plugin_' . $ext . '_opt_version' ];
			}

			return 0;
		}

		/*
		 * Returns true or false.
		 */
		public function set_version( array &$opts, $ext, $version = 0 ) {

			if ( $ext ) {

				if ( $version > 0 ) {

					if ( ! isset( $opts[ 'opt_versions' ] ) || ! is_array( $opts[ 'opt_versions' ] ) ) {

						$opts[ 'opt_versions' ] = array();
					}

					$opts[ 'opt_versions' ][ $ext ] = $version;

				} else {

					unset( $opts[ 'opt_versions' ][ $ext ] );
				}

				if ( isset( $opts[ 'plugin_' . $ext . '_opt_version' ] ) ) {	// Deprecated options version key.

					unset( $opts[ 'plugin_' . $ext . '_opt_version' ] );
				}

				return true;
			}

			return false;
		}

		/*
		 * Returns true or false.
		 */
		public function is_new_options( array $opts ) {

			return empty( $opts[ 'opt_checksum' ] ) && empty( $opts[ 'options_version' ] ) ? true : false;
		}

		/*
		 * Returns true or false, false for a new options array ('checksum' is an empty string by default).
		 */
		public function is_plugin_upgrading( array $opts ) {

			$cf_checksum = md5( $this->p->cf[ '*' ][ 'version' ] );

			$prev_checksum = '';

			if ( isset( $opts[ 'checksum' ] ) ) {	// Empty string by default.

				$prev_checksum = $opts[ 'checksum' ];
			}

			return $prev_checksum && $prev_checksum !== $cf_checksum ? true : false;
		}

		/*
		 * Returns true or false, false for a new options array ('opt_checksum' is an empty string by default).
		 */
		public function is_upgrade_required( array $opts ) {

			$cf_checksum = md5( $this->p->cf[ 'opt' ][ 'version' ] );

			$prev_checksum = '';

			if ( isset( $opts[ 'opt_checksum' ] ) ) {	// Empty string by default.

				$prev_checksum = $opts[ 'opt_checksum' ];

			} elseif ( isset( $opts[ 'options_version' ] ) ) {	// Deprecated options checksum key.

				$prev_checksum = md5( $opts[ 'options_version' ] );
			}

			return $prev_checksum && $prev_checksum !== $cf_checksum ? true : false;
		}

		/*
		 * Add plugin and add-on option versions (ie. 'checksum', 'opt_checksum', and 'opt_versions').
		 */
		public function add_versions( array &$opts ) {	// Pass by reference is OK.

			$opts[ 'checksum' ] = md5( $this->p->cf[ '*' ][ 'version' ] );
			$opts[ 'opt_checksum' ]    = md5( $this->p->cf[ 'opt' ][ 'version' ] );

			if ( isset( $opts[ 'options_version' ] ) ) {	// Deprecated options checksum key.

				unset( $opts[ 'options_version' ] );
			}

			if ( ! isset( $opts[ 'opt_versions' ] ) || ! is_array( $opts[ 'opt_versions' ] ) ) {

				$opts[ 'opt_versions' ] = array();
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( isset( $info[ 'opt_version' ] ) ) {	// Just in case.

					$opts[ 'opt_versions' ][ $ext ] = $info[ 'opt_version' ];

					if ( isset( $opts[ 'plugin_' . $ext . '_opt_version' ] ) ) {	// Deprecated options version key.

						unset( $opts[ 'plugin_' . $ext . '_opt_version' ] );
					}
				}
			}
		}

		/*
		 * Complete the options array for any custom post types and/or custom taxonomies.
		 *
		 * This method should be called after themes and plugins have registered their custom post types and taxonomies.
		 *
		 * Called by WpssoOptions->get_defaults().
		 */
		private function add_custom_post_tax_options( array &$opts ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$col_headers = WpssoAbstractWpMeta::get_column_headers();

			/*
			 * Add options using a key prefix array and post type names.
			 */
			$opt_prefixes = array(
				'og_type_for'                => 'article',	// Advanced Settings > Document Types > Open Graph > Type by Post Type.
				'plugin_add_to'              => 1,		// Advanced Settings > Plugin Settings > Interface > Show Document SSO Metabox.
				'plugin_ratings_reviews_for' => 0,		// Advanced Settings > Service APIs > Ratings and Reviews > Get Reviews for Post Type.
				'schema_type_for'            => 'webpage',	// Advanced Settings > Document Types > Schema > Type by Post Type.
			);

			foreach ( $col_headers as $col_key => $col_header ) {

				$opt_prefix = 'plugin_' . $col_key . '_col';

				/*
				 * Show the Open Graph Image column for post types by default.
				 */
				$def_val = 'og_img' === $col_key ? 1 : 0;

				$opt_prefixes[ $opt_prefix ] = $def_val;
			}

			/*
			 * See WpssoAmFiltersOptions->filter_add_custom_post_type_options().
			 * See WpssoBcFiltersOptions->filter_add_custom_post_type_options().
			 */
			$opt_prefixes = apply_filters( 'wpsso_add_custom_post_type_options', $opt_prefixes );

			$this->p->util->add_post_type_names( $opts, $opt_prefixes );

			/*
			 * Add options using a key prefix array and post type archive names.
			 */
			$opt_prefixes = array(
				'og_type_for_pta'     => 'website',	// Advanced Settings > Document Types > Open Graph > Type by Post Type Archive.
				'schema_type_for_pta' => 'item.list',	// Advanced Settings > Document Types > Schema > Type by Post Type Archive.
			);

			$opt_prefixes = apply_filters( 'wpsso_add_custom_post_type_archive_options', $opt_prefixes );

			$this->p->util->add_post_type_archive_names( $opts, $opt_prefixes );

			/*
			 * Add options using a key prefix array and term names.
			 */
			$opt_prefixes = array(
				'og_type_for_tax'     => 'website',	// Advanced Settings > Document Types > Open Graph > Type by Taxonomy.
				'plugin_add_to_tax'   => 1,		// Advanced Settings > Plugin Settings > Interface > Show Document SSO Metabox.
				'schema_type_for_tax' => 'item.list',	// Advanced Settings > Document Types > Schema > Type by Taxonomy.
			);

			foreach ( $col_headers as $col_key => $col_header ) {

				$opt_prefix = 'plugin_' . $col_key . '_col_tax';

				/*
				 * Show the Open Graph Image and Description columns for taxonomies by default.
				 */
				$def_val = 'og_img' === $col_key || 'og_desc' === $col_key ? 1 : 0;

				$opt_prefixes[ $opt_prefix ] = $def_val;
			}

			/*
			 * See WpssoBcFiltersOptions->filter_add_custom_taxonomy_options().
			 */
			$opt_prefixes = apply_filters( 'wpsso_add_custom_taxonomy_options', $opt_prefixes );

			$this->p->util->add_taxonomy_names( $opts, $opt_prefixes );
		}

		/*
		 * Update the width / height of remote image URLs.
		 */
		private function refresh_image_url_sizes( array &$opts ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Remove custom field names to exclude 'plugin_cf_img_url' and 'plugin_cf_vid_url'.
			 */
			$img_url_keys = preg_grep( '/^plugin_cf_/', array_keys( $opts ), PREG_GREP_INVERT );

			/*
			 * Allow for multi-option keys, like 'place_img_url_1'.
			 */
			$img_url_keys = preg_grep( '/_(img|logo|banner)_url(_[0-9]+)?(#[a-zA-Z_]+)?$/', $img_url_keys );

			/*
			 * Add correct image sizes for the image URL using getimagesize().
			 *
			 * Note that PHP v7.1 or better is required to get the image size of WebP images.
			 */
			$this->p->util->add_image_url_size( $opts, $img_url_keys );
		}

		private function check_site_org_image_sizes( array $opts ) {

			/*
			 * Skip if notices have already been shown.
			 */
			if ( ! $this->p->notice->is_admin_pre_notices() ) {

				return;
			}

			/*
			 * Skip if the WebSite Publisher Type is not an organization.
			 */
			if ( empty( $opts[ 'site_pub_schema_type' ] ) || 'organization' !== $opts[ 'site_pub_schema_type' ] ) {

				return;
			}

			/*
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
			foreach ( array ( 'site_org_logo', 'site_org_banner' ) as $img_pre ) {

				$mt_single_image = $this->p->media->get_mt_img_pre_url( $opts, $img_pre );
				$first_image_url = SucomUtil::get_first_mt_media_url( $mt_single_image );

				if ( 'site_org_logo' === $img_pre ) {

					$option_label = _x( 'Organization Logo URL', 'option label', 'wpsso' );
					$option_link  = $this->p->util->get_admin_url( 'essential', $option_label );

				} elseif ( 'site_org_banner' === $img_pre ) {

					$option_label  = _x( 'Organization Banner URL', 'option label', 'wpsso' );
					$option_link   = $this->p->util->get_admin_url( 'essential', $option_label );
				}

				if ( empty( $first_image_url ) ) {

					// translators: %s is a link to the option label.
					$notice_msg = sprintf( __( 'The %s image is missing and required.', 'wpsso' ), $option_link ) . ' ';

					$this->p->notice->err( $notice_msg );

				} else {

					$image_href   = '<a href="' . $first_image_url . '">' . $first_image_url . '</a>';
					$image_width  = $mt_single_image[ 'og:image:width' ];
					$image_height = $mt_single_image[ 'og:image:height' ];
					$image_dims   = $image_width . 'x' . $image_height . 'px';
					$notice_key   = 'invalid-image-dimensions-' . $image_dims . '-' . $first_image_url;

					if ( '-1x-1px' === $image_dims ) {

						// translators: %s is a link to the option label.
						$notice_msg = sprintf( __( 'The %s image dimensions cannot be determined.', 'wpsso' ), $option_link ) . ' ';

						// translators: %s is the image URL.
						$notice_msg .= sprintf( __( 'Please make sure this site can access %s using the PHP getimagesize() function.',
							'wpsso' ), $image_href );

						$this->p->notice->err( $notice_msg, null, $notice_key );

					} elseif ( 'site_org_logo' === $img_pre ) {

						$min_width    = $this->p->cf[ 'head' ][ 'limit_min' ][ 'org_logo_width' ];
						$min_height   = $this->p->cf[ 'head' ][ 'limit_min' ][ 'org_logo_height' ];
						$minimum_dims = $min_width . 'x' . $min_height . 'px';

						if ( $image_width < $min_width || $image_height < $min_height ) {

							// translators: %1$s is a link to the option label.
							$notice_msg = sprintf( __( 'The %1$s image dimensions are %2$s and must be greater than %3$s.',
								'wpsso' ), $option_link, $image_dims, $minimum_dims ) . ' ';

							// translators: %s is the image URL.
							$notice_msg .= sprintf( __( 'Please correct the %s logo image or select a different logo image.',
								'wpsso' ), $image_href );

							$this->p->notice->err( $notice_msg, null, $notice_key );
						}

					} elseif ( 'site_org_banner' === $img_pre ) {

						$min_width     = $this->p->cf[ 'head' ][ 'limit' ][ 'org_banner_width' ];
						$min_height    = $this->p->cf[ 'head' ][ 'limit' ][ 'org_banner_height' ];
						$required_dims = $min_width . 'x' . $min_height . 'px';

						if ( $image_dims !== $required_dims ) {

							// translators: %1$s is a link to the option label.
							$notice_msg = sprintf( __( 'The %1$s image dimensions are %2$s and must be exactly %3$s.',
								'wpsso' ), $option_link, $image_dims, $required_dims ) . ' ';

							// translators: %s is the image URL.
							$notice_msg .= sprintf( __( 'Please correct the %s banner image or select a different banner image.',
								'wpsso' ), $image_href );

							$this->p->notice->err( $notice_msg, null, $notice_key );
						}
					}
				}
			}
		}

		private function check_value( $opt_key, $base_key, $opt_val, $def_val, $network, $mod ) {

			if ( is_array( $opt_val ) ) {

				return $opt_val;
			}

			/*
			 * Hooked by WpssoOptions->filter_option_type() and several add-ons.
			 */
			$option_type = apply_filters( 'wpsso_option_type', $option_type = false, $base_key, $network, $mod );

			/*
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

			/*
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

			/*
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

				/*
				 * Empty or alpha-numeric (upper or lower case), plus underscores and hypens.
				 */
				case 'api_key':

					$opt_val = trim( $opt_val );	// Removed extra spaces from copy-paste.

					if ( '' !== $opt_val && preg_match( '/[^a-zA-Z0-9_\-]/', $opt_val ) ) {

						$this->p->notice->err( sprintf( $errors_transl[ 'api_key' ], $opt_key ) );

						$opt_val = $def_val;
					}

					break;

				/*
				 * Twitter-style usernames (prepend with an @ character).
				 */
				case 'at_name':

					if ( '' !== $opt_val ) {

						$opt_val = SucomUtil::sanitize_twitter_name( $opt_val, $add_at = true );
					}

					break;

				/*
				 * Empty or alpha-numeric uppercase (hyphens are allowed as well). Silently convert illegal
				 * characters to single hyphens and trim excess.
				 */
				case 'auth_id':

					$opt_val = trim( preg_replace( '/[^A-Z0-9\-]+/', '-', $opt_val ), '-' );

					$opt_val = preg_replace( '/^ID-/', '', $opt_val );	// Just in case.

					break;

				/*
				 * Applies sanitize_title_with_dashes().
				 */
				case 'dashed':

					$opt_val = trim( sanitize_title_with_dashes( $opt_val ) );

					break;

				/*
				 * Must be blank or integer / numeric.
				 */
				case 'blank_int':

					$ret_int = true;

					// No break.

				case 'blank_num':

					$opt_val = trim( $opt_val );	// Just in case.

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

				/*
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

						$opt_val = implode( $glue = ', ', SucomUtil::explode_csv( $opt_val ) );
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

						$opt_val = implode( $glue = ', ', $parts );
					}

					break;

				/*
				 * Text strings that can be blank (line breaks are removed).
				 */
				case 'desc':
				case 'one_line':
				case 'preg':	// A regular expression.

					if ( '' !== $opt_val ) {

						$opt_val = trim( preg_replace( '/[\s\n\r]+/s', ' ', $opt_val ) );
					}

					break;

				/*
				 * Must be a floating-point number. The decimal precision defined before the switch() statement.
				 */
				case 'fnum':

					$ret_fnum = true;

					if ( ! is_numeric( $opt_val ) ) {

						$this->p->notice->err( sprintf( $errors_transl[ 'numeric' ], $opt_key ) );

						$opt_val = $def_val;
					}

					break;

				/*
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

				/*
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

				/*
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

				/*
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

					/*
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

				/*
				 * Text strings that can be blank.
				 */
				case 'ok_blank':

					if ( '' !== $opt_val ) {

						$opt_val = trim( $opt_val );
					}

					break;

				/*
				 * Must be empty or texturized.
				 */
				case 'textured':

					if ( '' !== $opt_val ) {

						$opt_val = trim( wptexturize( ' ' . $opt_val . ' ' ) );
					}

					break;

				/*
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

				/*
				 * Strip leading URLs off facebook usernames.
				 */
				case 'url_base':

					if ( '' !== $opt_val ) {

						$opt_val = preg_replace( '/(http|https):\/\/[^\/]*?\//', '', $opt_val );
					}

					break;

				/*
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

		/*
		 * Deprecated on 2021/09/15.
		 */
		public static function can_cache() {

			_deprecated_function( __METHOD__ . '()', '2020/07/07', $replacement = 'WpssoOptions::is_cache_allowed()' );	// Deprecation message.

			return self::is_cache_allowed();
		}

		public function set_default_text( array &$defs, $opt_key ) {

			if ( $opt_key && is_string( $opt_key ) ) {	// Just in case.

				$text = $this->get_text( $opt_key, $use_opts = false );

				$opt_key_locale = SucomUtil::get_key_locale( $opt_key, $defs, 'current' );

				$defs[ $opt_key_locale ] = $text;

				if ( ! isset( $this->p->options[ $opt_key_locale ] ) ) {	// Just in case.

					$this->p->options[ $opt_key_locale ] = $text;
				}
			}
		}

		/*
		 * Returns an option value or null.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public function get_text( $opt_key, $use_opts = true, $mixed = 'current' ) {

			$text = null;

			if ( $use_opts && ! empty( $this->p->options ) ) {

				$text = SucomUtil::get_key_value( $opt_key, $this->p->options, $mixed  );	// Returns null if option key does not exist.
			}

			if ( null === $text ) {	// Fallback to default text from current locale.

				switch ( $opt_key ) {

					case 'plugin_title_part_site':		// Title Tag Site Prefix / Suffix.

						return _x( '%%sitename%%', 'option value', 'wpsso' );

					case 'plugin_title_part_tagline':	// Title Tag Tagline Prefix / Suffix.

						return _x( '%%sitedesc%%', 'option value', 'wpsso' );

					case 'plugin_img_alt_prefix':		// Content Image Alt Prefix.

						return _x( 'Image:', 'option value', 'wpsso' );

					case 'plugin_p_cap_prefix':		// WP Caption Text Prefix.

						return _x( 'Caption:', 'option value', 'wpsso' );

					case 'plugin_comment_title':		// Comment Title.

						return _x( 'Comment by %%comment_author%% on %%comment_date%%', 'option value', 'wpsso' );

					case 'plugin_comment_reply_title':	// Reply Comment Title.

						return _x( 'Reply by %%comment_author%% on %%comment_date%%', 'option value', 'wpsso' );

					case 'plugin_comment_review_title':	// Review Comment Title.

						return _x( 'Review by %%comment_author%% on %%comment_date%%', 'option value', 'wpsso' );

					case 'plugin_product_var_title':	// Product Variation Title.

						return _x( '%%var_title%% %%sep%% %%var_attrs%%', 'option value', 'wpsso' );

					case 'plugin_404_page_title':		// 404 Page Title.

						return _x( 'Page Not Found', 'option value', 'wpsso' );

					case 'plugin_404_page_desc':		// 404 Page Description.

						return _x( 'Page "%%pagename%%" not found.', 'option value', 'wpsso' );

					case 'plugin_no_title_text':		// No Title Text.

						return _x( 'No Title', 'option value', 'wpsso' );

					case 'plugin_no_desc_text':		// No Description Text.

						return _x( 'No description.', 'option value', 'wpsso' );

					case 'plugin_term_page_title':		// Term Archive Title.

						return _x( '%%term_hierarchy%%', 'option value', 'wpsso' );

					case 'plugin_term_page_desc':		// Term Archive Description.

						return _x( '%%term_tax_single%% archive page for %%term_name%%.', 'option value', 'wpsso' );

					case 'plugin_author_page_title':	// Author Archive Title.

						return _x( '%%author_name%%, Author at %%sitename%%', 'option value', 'wpsso' );

					case 'plugin_author_page_desc':		// Author Archive Description.

						return _x( 'Author archive page for %%author_name%%.', 'option value', 'wpsso' );

					case 'plugin_search_page_title':	// Search Results Title.

						return _x( 'Search Results %%sep%% %%query_search%%', 'option value', 'wpsso' );

					case 'plugin_search_page_desc':		// Search Results Description.

						return _x( 'Search results for &#8220;%%query_search%%&#8221;.', 'option value', 'wpsso' );

					case 'plugin_year_page_title':		// Year Archive Title.

						return _x( '%%query_year%%', 'option value', 'wpsso' );

					case 'plugin_year_page_desc':		// Year Archive Description.

						return _x( 'Yearly archive for %%query_year%%.', 'option value', 'wpsso' );

					case 'plugin_month_page_title':		// Month Archive Title.

						return _x( '%%query_month%% %%query_year%%', 'option value', 'wpsso' );

					case 'plugin_month_page_desc':		// Month Archive Description.

						return _x( 'Monthly archive for %%query_month%%, %%query_year%%.', 'option value', 'wpsso' );

					case 'plugin_day_page_title':		// Day Archive Title.

						return _x( '%%query_month%% %%query_day%%, %%query_year%%', 'option value', 'wpsso' );

					case 'plugin_day_page_desc':		// Day Archive Description.

						return _x( 'Daily archive for %%query_month%% %%query_day%%, %%query_year%%.', 'option value', 'wpsso' );

					default:

						return apply_filters( 'wpsso_get_text_default_options_key', null, $opt_key );
				}
			}

			return $text;
		}
	}
}
