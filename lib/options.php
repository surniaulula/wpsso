<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoOptions' ) ) {

	class WpssoOptions {

		protected $p;		// Wpsso class object.
		protected $upg;		// WpssoOptionsUpgrade class object.

		protected static $allow_cache = false;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'option_type' => 2
			), $prio = -100 );

			$this->p->util->add_plugin_filters( $this, array(
				'init_objects' => 0
			), $prio = 10000 );
		}

		public function filter_option_type( $type, $base_key ) {

			if ( ! empty( $type ) ) {
				return $type;
			}

			switch ( $base_key ) {

				/**
				 * The "use" value should be 'default', 'empty', or 'force'.
				 */
				case ( preg_match( '/:use$/', $base_key ) ? true : false ):

					return 'not_blank';

					break;

				/**
				 * Optimize and check for add meta tags options first.
				 */
				case ( 0 === strpos( $base_key, 'add_' ) ? true : false ):
				case ( 0 === strpos( $base_key, 'plugin_filter_' ) ? true : false ):

					return 'checkbox';

					break;

				/**
				 * Empty string or must include at least one HTML tag.
				 */
				case 'og_vid_embed':

					return 'html';

					break;

				/**
				 * A regular expression.
				 */
				case ( preg_match( '/_preg$/', $base_key ) ? true : false ):

					return 'preg';

					break;

				/**
				 * JS and CSS code (cannot be blank).
				 */
				case ( false !== strpos( $base_key, '_js_' ) ? true : false ):
				case ( false !== strpos( $base_key, '_css_' ) ? true : false ):
				case ( preg_match( '/(_css|_js|_html)$/', $base_key ) ? true : false ):

					return 'code';

					break;

				/**
				 * Gravity View field IDs.
				 */
				case 'gv_id_title':	// Title Field ID.
				case 'gv_id_desc':	// Description Field ID.
				case 'gv_id_img':	// Post Image Field ID.

					return 'blank_int';

					break;

				/**
				 * Cast as integer (zero and -1 is ok).
				 */
				case 'og_img_max':
				case 'og_vid_max':
				case 'og_desc_hashtags': 
				case 'schema_img_max':
				case 'schema_vid_max':
				case ( preg_match( '/_(cache_exp|caption_hashtags|filter_prio)$/', $base_key ) ? true : false ):
				case ( preg_match( '/_(img|logo|banner)_url(:width|:height)$/', $base_key ) ? true : false ):

					return 'integer';

					break;

				/**
				 * Numeric options that must be positive (1 or more).
				 */
				case 'plugin_upscale_img_max':
				case 'plugin_min_shorten':
				case ( preg_match( '/_(len|warn)$/', $base_key ) ? true : false ):

					return 'pos_int';

					break;

				/**
				 * Must be numeric (blank and zero are ok).
				 */
				case 'product_depth_value':
				case 'product_gtin14':
				case 'product_gtin13':
				case 'product_gtin12':
				case 'product_gtin8':
				case 'product_gtin':
				case 'product_height_value':
				case 'product_isbn':
				case 'product_length_value':
				case 'product_price':
				case 'product_volume_value':
				case 'product_weight_value':
				case 'product_width_value':

					return 'blank_num';

					break;

				/**
				 * Empty string or an image ID.
				 */
				case 'og_def_img_id':
				case 'og_img_id':
				case 'schema_img_id':
				case 'tc_lrg_img_id':
				case 'tc_sum_img_id':

					return 'img_id';

				/**
				 * Image width, subject to minimum value (typically, at least 200px).
				 */
				case ( preg_match( '/_img_width$/', $base_key ) ? true : false ):

					return 'img_width';

					break;

				/**
				 * Image height, subject to minimum value (typically, at least 200px).
				 */
				case ( preg_match( '/_img_height$/', $base_key ) ? true : false ):

					return 'img_height';

					break;

				/**
				 * Must be texturized.
				 */
				case 'og_title_sep':

					return 'textured';

					break;

				/**
				 * Empty or alpha-numeric uppercase (hyphens are allowed as well).
				 */
				case ( preg_match( '/_tid$/', $base_key ) ? true : false ):

					return 'auth_id';

					break;

				/**
				 * Empty or alpha-numeric (upper or lower case), plus underscores.
				 */
				case 'fb_app_id':
				case 'fb_app_secret':
				case 'g_site_verify':	// Google Website Verification ID.
				case 'p_site_verify':	// Pinterest Website Verification ID.
				case ( preg_match( '/_api_key$/', $base_key ) ? true : false ):

					return 'api_key';

					break;

				/**
				 * Text strings that can be blank (line breaks are removed).
				 */
				case 'site_name':
				case 'site_name_alt':
				case 'site_desc':
				case 'og_def_article_section':
				case 'og_def_product_category':
				case 'og_title':
				case 'og_desc':
				case 'seo_desc':
				case 'schema_desc':
				case 'tc_desc':
				case 'pin_desc':
				case 'article_section':
				case 'product_category':
				case 'product_brand':
				case 'product_color':
				case 'product_currency':
				case 'product_mfr_part_no':		// Product MPN.
				case 'product_retailer_part_no':	// Product SKU.
				case 'product_size':
				case 'plugin_col_title_width':
				case 'plugin_col_title_width_max':
				case 'plugin_col_def_width':
				case 'plugin_col_def_width_max':
				case 'plugin_head_attr_filter_name':
				case 'plugin_html_attr_filter_name':
				case 'plugin_img_alt_prefix':		// Content Image Alt Prefix.
				case 'plugin_p_cap_prefix':		// WP Caption Text Prefix.
				case 'plugin_bitly_access_token':	// Bitly Generic Access Token.
				case 'plugin_bitly_domain':		// Bitly Short Domain (Optional).
				case 'plugin_bitly_group_name':		// Bitly Group Name (Optional).
				case 'plugin_dlmyapp_api_key':
				case 'plugin_owly_api_key':
				case 'plugin_yourls_username':
				case 'plugin_yourls_password':
				case 'plugin_yourls_token':
				case ( 0 === strpos( $base_key, 'plugin_cf_' ) ? true : false ):		// Value is the name of a meta key.
				case ( 0 === strpos( $base_key, 'plugin_attr_product_' ) ? true : false ):	// Value is the name of a product attribute.

					return 'one_line';

					break;

				/**
				 * Options that cannot be blank.
				 */
				case 'site_org_schema_type':		// Example: 'organization' or a sub-type.
				case 'site_place_id':			// Example: 'none' or place ID.
				case 'og_author_field':
				case 'og_def_img_id_pre': 		// Example: 'wp' or 'ngg' media library name.
				case 'og_img_id_pre': 			// Example: 'wp' or 'ngg' media library name.
				case 'plugin_shortener':		// Example: 'none' or name of shortener
				case 'plugin_col_def_width':
				case 'plugin_col_def_width_max':
				case 'plugin_col_title_width':
				case 'plugin_col_title_width_max':
				case 'plugin_no_title_text':		// No Title Text.
				case 'plugin_no_desc_text':		// No Description Text.
				case 'product_avail':			// Select option with 'none' as default.
				case 'product_condition':		// Select option with 'none' as default.
				case 'product_target_gender':		// Select option with 'none' as default.
				case ( false !== strpos( $base_key, '_crop_x' ) ? true : false ):
				case ( false !== strpos( $base_key, '_crop_y' ) ? true : false ):
				case ( false !== strpos( $base_key, '_type_for_' ) ? true : false ):
				case ( preg_match( '/^(plugin|wp)_cm_[a-z]+_(name|label)$/', $base_key ) ? true : false ):

					return 'not_blank';

					break;

				/**
				 * twitter-style usernames (prepend with an at).
				 */
				case 'tc_site':

					return 'at_name';

					break;

				/**
				 * Strip leading urls off facebook usernames.
				 */
				case 'fb_admins':

					return 'url_base';

					break;

				/**
				 * Empty string or a URL.
				 *
				 * Option key exceptions:
				 *
				 *	'add_meta_property_og:image:secure_url' = 1
				 *	'add_meta_property_og:video:secure_url' = 1
				 *	'add_meta_itemprop_url'                 = 1
				 *	'plugin_cf_img_url'                     = '_format_image_url'
				 *	'plugin_cf_vid_url'                     = '_format_video_url'
				 *	'plugin_cf_review_item_image_url'       = ''
				 */
				case 'site_url':
				case 'sharing_url':
				case 'canonical_url':
				case 'fb_page_url':
				case 'og_def_img_url':
				case 'og_img_url':
				case 'og_vid_url':
				case 'p_publisher_url':
				case 'plugin_yourls_api_url':
				case 'schema_addl_type_url':
				case 'schema_banner_url':
				case 'schema_img_url':
				case 'schema_logo_url':
				case 'schema_sameas_url':
				case 'tc_lrg_img_url':
				case 'tc_sum_img_url':
				case ( strpos( $base_key, '_url' ) && isset( $this->p->cf[ 'form' ][ 'social_accounts' ][ $base_key ] ) ? true : false ):

					return 'url';

					break;

				/**
				 * CSS color code.
				 */
				case ( false !== strpos( $base_key, '_color_' ) ? true : false ):

					return 'color';

					break;
			}

			return $type;
		}

		public function filter_init_objects() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'setting allow_cache to true' );
			}

			self::$allow_cache = true;
		}

		public function get_defaults( $opt_key = false, $force_filter = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'opt_key'      => $opt_key, 
					'force_filter' => $force_filter, 
				) );
			}

			$defs =& $this->p->cf[ 'opt' ][ 'defaults' ];	// Shortcut variable.

			if ( $force_filter || ! self::$allow_cache || empty( $defs[ 'options_filtered' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'get_defaults filters' );	// Begin timer.
				}

				/**
				 * Add defaults using a key prefix array and post type names.
				 */
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding defaults derived from post type names' );
				}

				$this->p->util->add_post_type_names( $defs, array(
					'plugin_add_to'   => 1,			// Add Document SSO Metabox.
					'og_type_for'     => 'article',
					'schema_type_for' => 'webpage',
				) );

				/**
				 * Add defaults using a key prefix array and term names.
				 */
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding defaults derived from term names' );
				}

				$this->p->util->add_taxonomy_names( $defs, array(
					'plugin_add_to_tax'   => 1,		// Add Document SSO Metabox.
					'og_type_for_tax'     => 'website',
					'schema_type_for_tax' => 'item.list',
				) );

				/**
				 * Translate contact method field labels for current language.
				 */
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'translating plugin contact field labels' );
				}

				SucomUtil::transl_key_values( '/^plugin_(cm_.*_label|.*_prefix)$/', $defs, 'wpsso' );

				/**
				 * Define the default Facebook locale and current locale values.
				 */
				$defs[ 'fb_locale' ] = $this->p->og->get_fb_locale( array(), 'default' );

				if ( ( $locale_key = SucomUtil::get_key_locale( 'fb_locale' ) ) !== 'fb_locale' ) {
					$defs[ $locale_key ] = $this->p->og->get_fb_locale( array(), 'current' );
				}

				/**
				 * Maybe use a custom value from the SSO > Advanced settings page.
				 */
				$defs[ 'og_author_field' ]  = $this->p->options[ 'plugin_cm_fb_name' ];

				/**
				 * Read Yoast SEO social meta if the Yoast SEO plugin is active, or Yoast SEO settings are found.
				 */
				$defs[ 'plugin_wpseo_social_meta' ] = ! empty( $this->p->avail[ 'seo' ][ 'wpseo' ] ) || get_option( 'wpseo' ) ? 1 : 0;

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {
					if ( ! empty( $info[ 'update_auth' ] ) && $info[ 'update_auth' ]!== 'none' ) {	// Just in case.
						$defs[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ] ] = '';
					}
				}

				/**
				 * Check for default values from network admin settings.
				 */
				if ( is_multisite() && is_array( $this->p->site_options ) ) {

					foreach ( $this->p->site_options as $site_opt_key => $site_opt_val ) {

						if ( isset( $defs[ $site_opt_key ] ) && isset( $this->p->site_options[ $site_opt_key . ':use' ] ) ) {

							if ( $this->p->site_options[ $site_opt_key . ':use' ] === 'default' ) {
								$defs[ $site_opt_key ] = $this->p->site_options[ $site_opt_key ];
							}
						}
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'applying get_defaults filters' );
				}

				if ( self::$allow_cache ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting options_filtered to true' );
					}

					$defs[ 'options_filtered' ] = true;	// Set before calling filter to prevent recursion.

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'options_filtered value unchanged' );
				}

				$defs = apply_filters( $this->p->lca . '_get_defaults', $defs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'get_defaults filters' );	// End timer.
				}
			}

			if ( false !== $opt_key ) {

				if ( isset( $defs[ $opt_key ] ) ) {
					return $defs[ $opt_key ];
				} else {
					return null;
				}

			} else {
				return $defs;
			}
		}

		public function get_site_defaults( $opt_key = false, $force_filter = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'opt_key'      => $opt_key, 
					'force_filter' => $force_filter, 
				) );
			}

			$defs =& $this->p->cf[ 'opt' ][ 'site_defaults' ];	// Shortcut variable.

			if ( $force_filter || ! self::$allow_cache || empty( $defs[ 'options_filtered' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'get_site_defaults filters' );	// Begin timer.
				}

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {
					if ( ! empty( $info[ 'update_auth' ] ) && $info[ 'update_auth' ]!== 'none' ) {	// Just in case.
						$defs[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ]] = '';
						$defs[ 'plugin_' . $ext . '_' . $info[ 'update_auth' ] . ':use' ] = 'default';
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'applying get_site_defaults filters' );
				}

				if ( self::$allow_cache ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting options_filtered to true' );
					}
					$defs[ 'options_filtered' ] = true;
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'options_filtered value unchanged' );
				}

				$defs = apply_filters( $this->p->lca . '_get_site_defaults', $defs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'get_site_defaults filters' );	// End timer.
				}
			}

			if ( false !== $opt_key ) {
				if ( isset( $defs[ $opt_key ] ) ) {
					return $defs[ $opt_key ];
				} else {
					return null;
				}
			} else {
				return $defs;
			}
		}

		public function check_options( $options_name, &$opts = array(), $network = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'checking options' );	// Begin timer.
			}

			if ( is_array( $opts ) && ! empty( $opts ) ) {	// Just in case.

				$is_new_options  = empty( $opts[ 'options_version' ] ) ? true : false;	// Example: -wpsso512pro-wpssoum3gpl
				$current_version = $is_new_options ? 0 : $opts[ 'options_version' ];	// Example: -wpsso512pro-wpssoum3gpl
				$latest_version  = $this->p->cf[ 'opt' ][ 'version' ];
				$options_changed = $current_version === $latest_version ? false : true;
				$version_changed = false;
				$def_opts        = null;	// Optimize and only get array when needed.

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

				if ( ! $is_new_options ) {

					foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

						if ( isset( $info[ 'version' ] ) ) {

							if ( ! isset( $opts[ 'plugin_' . $ext . '_version' ] ) ||
								$opts[ 'plugin_' . $ext . '_version' ] !== $info[ 'version' ] ) {

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
						$this->p->debug->log( $options_name . ' current v' . $current_version .
							' different than latest v' . $latest_version );
					}

					if ( ! is_object( $this->upg ) ) {

						require_once WPSSO_PLUGINDIR . 'lib/upgrade.php';

						$this->upg = new WpssoOptionsUpgrade( $this->p );
					}

					if ( null === $def_opts ) {	// Only get default options once.
						if ( $network ) {
							$def_opts = $this->get_site_defaults();
						} else {
							$def_opts = $this->get_defaults();
						}
					}

					$opts = $this->upg->options( $options_name, $opts, $def_opts, $network );
				}

				/**
				 * Adjust / cleanup options.
				 */
				if ( ! $network ) {

					if ( ! $is_new_options && $version_changed ) {
					
						if ( empty( $opts[ 'plugin_wpsso_tid' ] ) && ! $this->p->check->pp( 'wpsso', $li = false ) ) {

							// translators: %s is the option key name.
							$notice_msg = __( 'Non-standard value found for the "%s" option - resetting the option to its default value.', 'wpsso' );

							if ( null === $def_opts ) {	// Only get default options once.
								$def_opts = $this->get_defaults();
							}

							$advanced_opts = SucomUtil::preg_grep_keys( '/^plugin_/', $def_opts );
							$advanced_opts = SucomUtil::preg_grep_keys( '/^plugin_.*_tid$/', $advanced_opts, $invert = true );

							foreach ( array(
								'plugin_clean_on_uninstall',
								'plugin_debug',
								'plugin_show_opts',
								'plugin_notice_system',
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
				 * If an SEO plugin is detected, adjust some related SEO options.
				 */
				if ( ! $network ) {

					if ( ! empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'seo plugin found - checking meta tag options' );
						}

						foreach ( array(
							'add_link_rel_canonical'    => 0,
							'add_meta_name_description' => 0,
							'add_meta_name_robots'      => 0,
						) as $opt_key => $def_val ) {

							$def_val = (int) apply_filters( $this->p->lca . '_' . $opt_key, $def_val );

							$opts[ $opt_key . ':is' ] = 'disabled';	// Prevent changes in settings page.

							if ( $opts[ $opt_key ] !== $def_val ) {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'setting ' . $opt_key . ' to ' . $def_val );
								}

								$opts[ $opt_key ] = $def_val;

								$options_changed = true;	// Save the options.
							}
						}
					}
				}

				/**
				 * Add options using a key prefix array and post type names.
				 */
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding options derived from post type names' );
				}

				$this->p->util->add_post_type_names( $opts, array(
					'plugin_add_to'   => 1,			// Add Document SSO Metabox.
					'og_type_for'     => 'article',
					'schema_type_for' => 'webpage',
				) );

				/**
				 * Add options using a key prefix array and term names.
				 */
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding options derived from term names' );
				}

				$this->p->util->add_taxonomy_names( $opts, array(
					'plugin_add_to_tax'   => 1,		// Add Document SSO Metabox.
					'og_type_for_tax'     => 'website',
					'schema_type_for_tax' => 'item.list',
				) );

				/**
				 * Enable or disable the toolbar notification system dynamically.
				 */
				if ( SucomUtil::get_const( 'WPSSO_TOOLBAR_NOTICES' ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'WPSSO_TOOLBAR_NOTICES constant is defined' );
					}

					$opts[ 'plugin_notice_system' ]    = 'toolbar_notices';
					$opts[ 'plugin_notice_system:is' ] = 'disabled';	// Prevent changes in settings page.
				}

				/**
				 * Note that generator meta tags are required for plugin support.
				 *
				 * If you disable the generator meta tags, requests for plugin support will be denied.
				 */
				if ( ! $network ) {

					if ( SucomUtil::get_const( 'WPSSO_META_GENERATOR_DISABLE' ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'WPSSO_META_GENERATOR_DISABLE constant is defined' );
						}

						$opts[ 'add_meta_name_generator' ]    = SucomUtil::get_const( 'WPSSO_META_GENERATOR_DISABLE' ) ? 0 : 1;
						$opts[ 'add_meta_name_generator:is' ] = 'disabled';	// Prevent changes in settings page.
					}
				}

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
				 * Save options and show reminders.
				 */
				if ( $options_changed || $version_changed ) {

					if ( ! $is_new_options ) {

						if ( null === $def_opts ) {	// Only get default options once.
							if ( $network ) {
								$def_opts = $this->get_site_defaults();
							} else {
								$def_opts = $this->get_defaults();
							}
						}

						/**
						 * Sanitation also updates image width/height info.
						 */
						$opts = $this->sanitize( $opts, $def_opts, $network );
					}

					$this->save_options( $options_name, $opts, $network, $options_changed );
				}

			} else {	// $opts is empty or not an array.

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

					$error_msg .= ' ' . sprintf( __( 'The plugin settings have been returned to their default values &mdash; <a href="%s">please review and save the new settings</a>.', 'wpsso' ), $admin_url );

					$this->p->notice->err( $error_msg );
				}

				$opts = $network ? $this->get_site_defaults() : $this->get_defaults();
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'checking options' );	// End timer.
			}

			return $opts;
		}

		/**
		 * Sanitize and validate options, including both the plugin options and custom meta options arrays.
		 */
		public function sanitize( $opts = array(), $def_opts = array(), $network = false, $mod = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Make sure we have something to work with.
			 */
			if ( empty( $def_opts ) || ! is_array( $def_opts ) ) {
				return $opts;
			}

			/**
			 * Add any missing options from the defaults, unless sanitizing for a module.
			 */
			if ( false === $mod ) {
				foreach ( $def_opts as $opt_key => $def_val ) {
					if ( ! empty( $opt_key ) && ! isset( $opts[ $opt_key ] ) ) {
						$opts[ $opt_key ] = $def_val;
					}
				}
			}

			/**
			 * Sort the options to re-order 0, 1, 10, 2 as 0, 1, 2, 10.
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

				if ( preg_match( '/:is$/', $base_key ) ) {

					unset( $opts[ $opt_key ] );

					continue;
				}

				/**
				 * Multi-options and localized options will default to an empty string.
				 */
				$def_val = isset( $def_opts[ $opt_key ] ) ? $def_opts[ $opt_key ] : '';

				$opts[ $opt_key ] = $this->check_value( $opt_key, $base_key, $opt_val, $def_val, $network, $mod );
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

				$img_ratio  = $img_width >= $img_height ? $img_width / $img_height : $img_height / $img_width;
				$img_ratio  = number_format( $img_ratio, 3, '.', '' );

				foreach ( array( 'limit', 'limit_max' ) as $limit_type ) {

					if ( ! isset( $this->p->cf[ 'head' ][ $limit_type ][ $opt_pre . '_img_ratio' ] ) ) {
						continue;
					}

					$error_msg   = false;
					$limit_ratio = number_format( $this->p->cf[ 'head' ][ $limit_type ][ $opt_pre . '_img_ratio' ], 3, '.', '' );

					switch ( $limit_type ) {

						case 'limit':
				
							$opts[ $opt_pre . '_img_crop' ]    = 1;
							$opts[ $opt_pre . '_img_crop:is' ] = 'disabled';	// Prevent changes in settings page.

							if ( $img_ratio !== $limit_ratio ) {
								$error_msg = sprintf( __( 'Option keys "%1$s" (%2$d) and "%3$s" (%4$d) have an aspect ratio of %5$s:1, which not equal to the required image ratio of %6$s:1.', 'wpsso' ), $opt_pre . '_img_width', $img_width, $opt_pre . '_img_height', $img_height, $img_ratio, $limit_ratio );
							}

							break;

						case 'limit_max':
					
							if ( $img_crop && $img_ratio >= $limit_ratio ) {
								$error_msg = sprintf( __( 'Option keys "%1$s" (%2$d) and "%3$s" (%4$d) have an aspect ratio of %5$s:1, which is equal to / or greater than the maximum image ratio of %6$s:1.', 'wpsso' ), $opt_pre . '_img_width', $img_width, $opt_pre . '_img_height', $img_height, $img_ratio, $limit_ratio );
							}

							break;
					}

					if ( $error_msg ) {

						$error_msg .= ' ' . __( 'These options have been reset to their default values.', 'wpsso' );

						$this->p->notice->err( $error_msg );

						$opts[ $opt_pre . '_img_width' ]  = $def_opts[ $opt_pre . '_img_width' ];
						$opts[ $opt_pre . '_img_height' ] = $def_opts[ $opt_pre . '_img_height' ];
						$opts[ $opt_pre . '_img_crop' ]   = $def_opts[ $opt_pre . '_img_crop' ];
					}
				}
			}

			/**
			 * If there's no image ID, then reset the image ID library prefix to its default value. If an image ID is
			 * used, then remove the image url (only one option can be defined). Use isset() to check for array keys
			 * since this method is also called to sanitize meta options.
			 */
			foreach ( array( 'og_def' ) as $opt_pre ) {

				if ( empty( $opts[ $opt_pre . '_img_id' ] ) ) {

					if ( isset( $def_opts[ $opt_pre . '_img_id_pre' ] ) ) {	// Just in case.
						$opts[ $opt_pre . '_img_id_pre' ] = $def_opts[ $opt_pre . '_img_id_pre' ];
					}

				} elseif ( isset( $opts[ $opt_pre . '_img_url' ] ) ) {

					$opts[ $opt_pre . '_img_url' ] = '';
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

					$this->p->notice->err( sprintf( __( 'The Facebook App ID must be numeric and 32 characters or less in length &mdash; the value of "%s" is not valid.', 'wpsso' ), $opts[ 'fb_app_id' ] ) );
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

			return $opts;
		}

		/**
		 * Save both options and site options.
		 */
		public function save_options( $options_name, &$opts, $network = false, $options_changed = false ) {

			/**
			 * Make sure we have something to work with.
			 */
			if ( empty( $opts ) || ! is_array( $opts ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: options variable is empty and/or not array' );
				}

				return false;
			}

			$is_new_options  = empty( $opts[ 'options_version' ] ) ? true : false;	// Example: -wpsso512pro-wpssoum3gpl
			$current_version = $is_new_options ? 0 : $opts[ 'options_version' ];	// Example: -wpsso512pro-wpssoum3gpl
			$latest_version  = $this->p->cf[ 'opt' ][ 'version' ];

			/**
			 * $doing_upgrade added in WPSSO Core v4.4.0.
			 */
			$doing_upgrade = ! $is_new_options && ! $options_changed && $current_version === $latest_version ? false : true;

			$opts = apply_filters( $this->p->lca . '_save_options', $opts, $options_name, $network, $doing_upgrade );

			/**
			 * Save the plugin version and options version.
			 */
			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( isset( $info[ 'version' ] ) ) {
					$opts[ 'plugin_' . $ext . '_version' ] = $info[ 'version' ];
				}

				if ( isset( $info[ 'opt_version' ] ) ) {
					$opts[ 'plugin_' . $ext . '_opt_version' ] = $info[ 'opt_version' ];
				}
			}

			$opts[ 'options_version' ] = $latest_version;	// Mark the new options array as current.

			if ( $network ) {

				if ( $saved = update_site_option( $options_name, $opts ) ) {	// Auto-creates options with autoload no.
					$this->p->site_options = $opts;				// Update the current plugin options array.
				}

			} else {

				if ( $saved = update_option( $options_name, $opts ) ) {		// Auto-creates options with autoload yes.
					$this->p->options = $opts;				// Update the current plugin options array.
				}
			}

			if ( true === $saved ) {

				if ( $doing_upgrade ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $options_name . ' settings have been upgraded and saved' );
					}

					if ( is_admin() ) {

						$this->p->notice->upd( '<strong>' . __( 'Plugin settings have been upgraded and saved.', 'wpsso' ) . '</strong> ' .
							__( 'A background task will begin shortly to clear all caches.', 'wpsso' ) );

						$this->p->util->schedule_clear_all_cache( $user_id = get_current_user_id(), $clear_other = true );
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

		public static function can_cache() {

			return self::$allow_cache;
		}

		/**
		 * Update the width / height of remote image urls.
		 */
		private function refresh_image_url_sizes( array &$opts ) {

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
			 */
			$this->p->util->add_image_url_size( $opts, $img_url_keys );	// $opts passed by reference.

			$this->check_banner_image_size( $opts );
		}

		private function check_banner_image_size( $opts ) {

			/**
			 * Skip if notices have already been shown.
			 */
			if ( ! $this->p->notice->is_admin_pre_notices() ) {
				return;
			}

			$size_name          = false;	// Only check banner urls - skip any banner image id options.
			$opt_img_pre        = 'schema_banner';
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
			$og_single_image = $this->p->media->get_opts_single_image( $opts, $size_name, $opt_img_pre );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_arr( '$og_single_image', $og_single_image );
			}

			$og_single_image_url = SucomUtil::get_mt_media_url( $og_single_image );

			if ( ! empty( $og_single_image_url ) ) {

				$image_href    = '<a href="' . $og_single_image_url . '">' . $og_single_image_url . '</a>';
				$image_dims    = $og_single_image[ 'og:image:width' ] . 'x' . $og_single_image[ 'og:image:height' ] . 'px';
				$required_dims = '600x60px';

				if ( $image_dims !== $required_dims ) {

					if ( $image_dims === '-1x-1px' ) {

						$error_msg = sprintf( __( 'The %1$s image dimensions cannot be determined.',
							'wpsso' ), $settings_page_link ) . ' ';

						$error_msg .= sprintf( __( 'Please make sure this site can access the image URL at %1$s using the PHP getimagesize() function.',
							'wpsso' ), $image_href );

					} else {

						$error_msg = sprintf( __( 'The %1$s image dimensions are %2$s and must be exactly %3$s.',
							'wpsso' ), $settings_page_link, $image_dims, $required_dims ) . ' ';

						$error_msg .= sprintf( __( 'Please correct the banner image at %s.',
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
			 * Hooked by several add-ons.
			 */
			$option_type = apply_filters( $this->p->lca . '_option_type', false, $base_key, $network, $mod );

			/**
			 * Translate error messages only once.
			 */
			static $error_messages = null;

			if ( null === $error_messages ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'translating error messages' );
				}

				$error_messages = array(
					'api_key'   => __( 'The value of option "%s" must be alpha-numeric - resetting this option to its default value.', 'wpsso' ),
					'blank_num' => __( 'The value of option "%s" must be blank or numeric - resetting this option to its default value.', 'wpsso' ),
					'color'     => __( 'The value of option "%s" must be a CSS color code - resetting this option to its default value.', 'wpsso' ),
					'csv_urls'  => __( 'The value of option "%s" must be a comma-delimited list of URL(s) - resetting this option to its default value.', 'wpsso' ),
					'date'      => __( 'The value of option "%s" must be a yyyy-mm-dd date - resetting this option to its default value.', 'wpsso' ),
					'html'      => __( 'The value of option "%s" must be HTML code - resetting this option to its default value.', 'wpsso' ),
					'img_id'    => __( 'The value of option "%s" must be an image ID - resetting this option to its default value.', 'wpsso' ),
					'not_blank' => __( 'The value of option "%s" cannot be an empty string - resetting this option to its default value.', 'wpsso' ),
					'numeric'   => __( 'The value of option "%s" must be numeric - resetting this option to its default value.', 'wpsso' ),
					'pos_num'   => __( 'The value of option "%1$s" must be equal to or greather than %2$s - resetting this option to its default value.', 'wpsso' ),
					'time'      => __( 'The value of option "%s" must be a hh:mm time - resetting this option to its default value.', 'wpsso' ),
					'url'       => __( 'The value of option "%s" must be a URL - resetting this option to its default value.', 'wpsso' ),
				);
			}

			/**
			 * Pre-filter most values to remove html.
			 */
			switch ( $option_type ) {

				case 'ignore':

					return $opt_val;	// Stop here.

					break;

				case 'html':		// Leave html, css, and javascript code blocks as-is.
				case 'code':		// Code values cannot be blank.
				case 'preg':

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

			if ( strpos( $option_type, 'fnum' ) === 0 ) {

				$num_prec = substr( $option_type, 4 );

				$option_type = 'fnum';
			}

			switch ( $option_type ) {

				/**
				 * Empty or alpha-numeric (upper or lower case), plus underscores.
				 */
				case 'api_key':

					$opt_val = trim( $opt_val );	// Removed extra spaces from copy-paste.

					if ( '' !== $opt_val && preg_match( '/[^a-zA-Z0-9_\-]/', $opt_val ) ) {

						$this->p->notice->err( sprintf( $error_messages[ 'api_key' ], $opt_key ) );

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
				 * Must be blank or integer / numeric.
				 */
				case 'blank_int':

					$ret_int = true;

					// No break.

				case 'blank_num':

					if ( '' === $opt_val ) {

						$ret_int = false;

					} elseif ( ! is_numeric( $opt_val ) ) {

						$this->p->notice->err( sprintf( $error_messages[ 'blank_num' ], $opt_key ) );

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

					if ( '' === $opt_val && '' !== $def_val ) {

						$this->p->notice->err( sprintf( $error_messages[ 'not_blank' ], $opt_key ) );

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

							if ( filter_var( $part, FILTER_VALIDATE_URL ) === false ) {

								$this->p->notice->err( sprintf( $error_messages[ 'csv_urls' ], $opt_key ) );

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
				 * Must be a floating-point number. The decimal precision defined before the switch() statement.
				 */
				case 'fnum':

					$ret_fnum = true;

					if ( ! is_numeric( $opt_val ) ) {

						$this->p->notice->err( sprintf( $error_messages[ 'numeric' ], $opt_key ) );

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

							$this->p->notice->err( sprintf( $error_messages[ 'html' ], $opt_key ) );

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

							$this->p->notice->err( sprintf( $error_messages[ 'img_id' ], $opt_key ) );

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

						$this->p->notice->err( sprintf( $error_messages[ 'numeric' ], $opt_key ) );

						$opt_val = $def_val;
					}

					break;

				/**
				 * Integer / numeric options that must be 1 or more (not zero).
				 */
				case 'pos_int':
				case 'img_width':	// Image height, subject to minimum value (typically, at least 200px).
				case 'img_height':	// Image height, subject to minimum value (typically, at least 200px).

					$ret_int = true;

					// No break.

				case 'pos_num':

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

						$this->p->notice->err( sprintf( $error_messages[ 'pos_num' ], $opt_key, $min_int ) );

						$opt_val = $def_val;
					}

					break;

				case 'color':
				case 'date':
				case 'time':

					$opt_val = trim( $opt_val );

					if ( 'color' === $option_type ) {

						$fmt = '/^#[a-fA-f0-9]{6,6}$/';				// Color as #000000.

					} elseif ( 'date' === $option_type ) {

						$fmt = '/^[0-9]{4,4}-[0-9]{2,2}-[0-9]{2,2}$/';		// Date as yyyy-mm-dd.

					} elseif ( 'time' === $option_type ) {

						$fmt = '/^[0-9]{2,2}:[0-9]{2,2}(:[0-9]{2,2})?$/';	// Time as hh:mm or hh:mm:ss.
					}

					if ( '' !== $opt_val && 'none' !== $opt_val && $fmt && ! preg_match( $fmt, $opt_val ) ) {

						$this->p->notice->err( sprintf( $error_messages[ $option_type ], $opt_key ) );

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
				 * Text strings that can be blank (line breaks are removed).
				 */
				case 'preg':
				case 'desc':
				case 'one_line':

					if ( '' !== $opt_val ) {
						$opt_val = trim( preg_replace( '/[\s\n\r]+/s', ' ', $opt_val ) );
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
				 */
				case 'url':

					if ( '' !== $opt_val ) {

						$opt_val = SucomUtil::decode_html( $opt_val );	// Just in case.

						if ( filter_var( $opt_val, FILTER_VALIDATE_URL ) === false ) {

							$this->p->notice->err( sprintf( $error_messages[ 'url' ], $opt_key ) );

							$opt_val = $def_val;
						}
					}

					break;

				/**
				 * Strip leading urls off facebook usernames.
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
	}
}
