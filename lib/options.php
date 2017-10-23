<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoOptions' ) ) {

	class WpssoOptions {

		protected $p;				// Wpsso class object
		protected $upg;				// WpssoOptionsUpgrade class object

		protected static $allow_cache = false;
		protected static $error_messages = null;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array( 'option_type' => 2 ), -100 );
			$this->p->util->add_plugin_filters( $this, array( 'init_objects' => 0 ), 9000 );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'running init_options action' );
			}

			do_action( $this->p->cf['lca'].'_init_options' );
		}

		public function get_defaults( $idx = false, $force_filter = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'idx' => $idx, 
					'force_filter' => $force_filter, 
				) );
			}

			$lca = $this->p->cf['lca'];
			$defs =& $this->p->cf['opt']['defaults'];	// shortcut

			if ( $force_filter || ! self::$allow_cache || empty( $defs['options_filtered'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'get_defaults filter' );	// start timer
				}

				/*
				 * Add options using a key prefix array and post type names.
				 */
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding options derived from post type names' );
				}
				$defs = $this->p->util->add_ptns_to_opts( $defs, array(
					'plugin_add_to' => 1,
					'schema_type_for' => 'webpage',
				) );

				/*
				 * Translate contact method field labels for current language.
				 */
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'translating plugin contact field labels' );
				}
				SucomUtil::transl_key_values( '/^plugin_(cm_.*_label|.*_prefix)$/', $defs, 'wpsso' );

				/*
				 * Define the default Facebook locale and current locale values.
				 */
				$defs['fb_locale'] = $this->p->og->get_fb_locale( array(), 'default' );
				if ( ( $locale_key = SucomUtil::get_key_locale( 'fb_locale' ) ) !== 'fb_locale' ) {
					$defs[$locale_key] = $this->p->og->get_fb_locale( array(), 'current' );
				}

				$defs['seo_author_field'] = $this->p->options['plugin_cm_gp_name'];	// reset to possible custom value
				$defs['og_author_field'] = $this->p->options['plugin_cm_fb_name'];	// reset to possible custom value
				$defs['plugin_wpseo_social_meta'] = $this->p->avail['seo']['wpseo'] || get_option( 'wpseo' ) ? 1 : 0;

				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( ! empty( $info['update_auth'] ) && 
						$info['update_auth']!== 'none' ) {	// just in case
						$defs['plugin_'.$ext.'_'.$info['update_auth']] = '';
					}
				}

				// check for default values from network admin settings
				if ( is_multisite() && is_array( $this->p->site_options ) ) {
					foreach ( $this->p->site_options as $key => $val ) {
						if ( isset( $defs[$key] ) && isset( $this->p->site_options[$key.':use'] ) ) {
							if ( $this->p->site_options[$key.':use'] === 'default' ) {
								$defs[$key] = $this->p->site_options[$key];
							}
						}
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'applying get_defaults filter' );
				}

				if ( self::$allow_cache ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting options_filtered to true' );
					}
					$defs['options_filtered'] = true;	// set before calling filter to prevent recursion
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'options_filtered value unchanged' );
				}

				$defs = apply_filters( $lca.'_get_defaults', $defs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'get_defaults filter' );	// end timer
				}
			}

			if ( $idx !== false ) {
				if ( isset( $defs[$idx] ) ) {
					return $defs[$idx];
				} else {
					return null;
				}
			} else {
				return $defs;
			}
		}

		public function get_site_defaults( $idx = false, $force_filter = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'idx' => $idx, 
					'force_filter' => $force_filter, 
				) );
			}

			$lca = $this->p->cf['lca'];
			$defs =& $this->p->cf['opt']['site_defaults'];	// shortcut

			if ( $force_filter || ! self::$allow_cache || empty( $defs['options_filtered'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'get_site_defaults filter' );	// start timer
				}

				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( ! empty( $info['update_auth'] ) && 
						$info['update_auth']!== 'none' ) {	// just in case
						$defs['plugin_'.$ext.'_'.$info['update_auth']] = '';
						$defs['plugin_'.$ext.'_'.$info['update_auth'].':use'] = 'default';
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'applying get_site_defaults filter' );
				}

				if ( self::$allow_cache ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting options_filtered to true' );
					}
					$defs['options_filtered'] = true;
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'options_filtered value unchanged' );
				}

				$defs = apply_filters( $lca.'_get_site_defaults', $defs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->mark( 'get_site_defaults filter' );	// end timer
				}
			}

			if ( $idx !== false ) {
				if ( isset( $defs[$idx] ) ) {
					return $defs[$idx];
				} else {
					return null;
				}
			} else return $defs;
		}

		public function check_options( $options_name, &$opts = array(), $network = false, $activate = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'checking options' );	// begin timer
			}

			if ( is_array( $opts ) && ! empty( $opts ) ) {	// just in case

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'options are a valid array' );
				}

				$lca = $this->p->cf['lca'];
				$has_diff_version = false;
				$has_diff_options = false;
				$has_new_options = empty( $opts['options_version'] ) ? true : false;
				$def_opts = null;	// optimize and only get array when needed

				/*
				 * Check for a new plugin and/or extension versions.
				 */
				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( empty( $info['version'] ) ) {
						continue;
					}
					$key = 'plugin_'.$ext.'_version';
					if ( empty( $opts[$key] ) || version_compare( $opts[$key], $info['version'], '!=' ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $ext.' '.$opts[$key].' version update found' );
						}
						WpssoUtil::save_time( $ext, $info['version'], 'update' );
						$opts[$key] = $info['version'];
						$has_diff_version = true;
					}
					unset( $key );
				}

				/*
				 * Upgrade the options array if necessary (renamed or remove keys).
				 */
				if ( ! $has_new_options && $opts['options_version'] !== $this->p->cf['opt']['version'] ) {

					$has_diff_options = true;

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $options_name.' v'.$this->p->cf['opt']['version'].
							' different than saved v'.( empty( $opts['options_version'] ) ?
								0 : $opts['options_version'] ) );
					}

					if ( ! is_object( $this->upg ) ) {
						require_once WPSSO_PLUGINDIR.'lib/upgrade.php';
						$this->upg = new WpssoOptionsUpgrade( $this->p );
					}

					if ( $def_opts === null ) {	// only get default options once
						if ( $network ) {
							$def_opts = $this->get_site_defaults();
						} else {
							$def_opts = $this->get_defaults();
						}
					}

					$opts = $this->upg->options( $options_name, $opts, $def_opts, $network );
				}

				/*
				 * Adjust / cleanup site options.
				 */
				if ( ! $network ) {
					if ( $this->p->check->aop( $lca, false, $this->p->avail['*']['p_dir'] ) ) {
						foreach ( array( 'plugin_hide_pro' => 0 ) as $idx => $def_val ) {
							if ( $opts[$idx] === $def_val ) {
								continue;
							}
							$opts[$idx] = $def_val;
							$has_diff_options = true;	// save the options
						}
					} elseif ( ! $has_new_options && $has_diff_version && empty( $opts['plugin_'.$lca.'_tid'] ) ) {
						if ( $def_opts === null ) {	// only get default options once
							$def_opts = $this->get_defaults();
						}
						$adv_opts = SucomUtil::preg_grep_keys( '/^plugin_/', $def_opts );
						foreach ( array(
							'plugin_preserve',
							'plugin_debug',
							'plugin_hide_pro',
							'plugin_show_opts',
							'plugin_shortcodes',
							'plugin_widgets',
						) as $free_opt_key ) {
							unset( $adv_opts[$free_opt_key] );
						}
						$warn_msg = __( 'Non-standard value found for "%s" option - resetting to default value.',
							'wpsso' );
						foreach ( $adv_opts as $idx => $def_val ) {
							if ( isset( $opts[$idx] ) ) {
								if ( $opts[$idx] === $def_val ) {
									continue;
								}
								if ( is_admin() ) {
									$this->p->notice->warn( sprintf( $warn_msg, $idx ) );
								}
							}
							$opts[$idx] = $def_val;
							$has_diff_options = true;	// save the options
						}
					}
					/*
					 * If an SEO plugin is detected, adjust some related SEO options.
					 */
					if ( ! empty( $this->p->avail['seo']['*'] ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'seo plugin found - checking meta tag options' );
						}
						foreach ( array(
							'add_link_rel_canonical' => 0,
							'add_meta_name_description' => 0,
						) as $idx => $def_val ) {
							$def_val = (int) apply_filters( $lca.'_'.$idx, $def_val );
							$opts[$idx.':is'] = 'disabled';
							if ( $opts[$idx] === $def_val ) {
								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( $idx.' already set to '.$def_val );
								}
								continue;
							}
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'setting '.$idx.' to '.$def_val );
							}
							$opts[$idx] = $def_val;
							$has_diff_options = true;	// save the options
						}
					}
					/*
					 * Please note that generator meta tags are required for plugin support. If you 
					 * disable the generator meta tags, requests for plugin support will be denied.
					 */
					$opts['add_meta_name_generator'] = SucomUtil::get_const( 'WPSSO_META_GENERATOR_DISABLE' ) ? 0 : 1;
				}

				/*
				 * Save options and show reminders.
				 */
				if ( $has_diff_version || $has_diff_options ) {
					if ( ! $has_new_options ) {
						if ( $def_opts === null ) {	// only get default options once
							if ( $network ) {
								$def_opts = $this->get_site_defaults();
							} else {
								$def_opts = $this->get_defaults();
							}
						}
						$opts = $this->sanitize( $opts, $def_opts, $network );
					}
					$this->save_options( $options_name, $opts, $network, $has_diff_options );
				}

				/*
				 * Add options using a key prefix array and post type names.
				 */
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding options derived from post type names' );
				}
				$opts = $this->p->util->add_ptns_to_opts( $opts, array(
					'plugin_add_to' => 1,
					'schema_type_for' => 'webpage',
				) );

			} else {	// $opts is empty or not an array

				if ( $opts === false ) {
					$err_msg = sprintf( __( 'WordPress could not find an entry for %s in the options table.', 'wpsso' ), $options_name );
				} elseif ( ! is_array( $opts ) ) {
					$err_msg = sprintf( __( 'WordPress returned a non-array value when reading %s from the options table.', 'wpsso' ), $options_name );
				} elseif ( empty( $opts ) ) {
					$err_msg = sprintf( __( 'WordPress returned an empty array when reading %s from the options table.', 'wpsso' ), $options_name );
				} else {
					$err_msg = sprintf( __( 'WordPress returned an unknown condition when reading %s from the options table.', 'wpsso' ), $options_name );
				}
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $err_msg );
				}
				if ( is_admin() ) {
					if ( $network ) {
						$admin_url = $this->p->util->get_admin_url( 'network' );
					} else {
						$admin_url = $this->p->util->get_admin_url( 'general' );
					}
					$this->p->notice->err( $err_msg.' '.sprintf( __( 'The plugin settings have been returned to their default values &mdash; <a href="%s">please review and save the new settings</a>.', 'wpsso' ), $admin_url ) );
				}

				$opts = $network ? $this->get_site_defaults() : $this->get_defaults();
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'checking options' );	// end timer
			}

			return $opts;
		}

		// sanitize and validate options
		public function sanitize( $opts = array(), $def_opts = array(), $network = false, $mod = false ) {

			// make sure we have something to work with
			if ( empty( $def_opts ) || ! is_array( $def_opts ) ) {
				return $opts;
			}

			// add any missing options from the defaults, unless sanitizing for a module 
			// (default values will be removed anyway)
			if ( $mod === false ) {
				foreach ( $def_opts as $key => $def_val ) {
					if ( ! empty( $key ) && ! isset( $opts[$key] ) ) {
						$opts[$key] = $def_val;
					}
				}
			}

			// sanitize values
			foreach ( $opts as $key => $val ) {
				if ( preg_match( '/:is$/', $key ) ) {	// don't save option states
					unset( $opts[$key] );
				} elseif ( ! empty( $key ) ) {
					$def_val = isset( $def_opts[$key] ) ? $def_opts[$key] : '';	// just in case
					$opts[$key] = $this->check_value( $key, $val, $def_val, $network, $mod );
				}
			}

			/*
			 * Adjust Dependent Options
			 *
			 * All options (site and meta as well) are sanitized here, so always use 
			 * isset() or array_key_exists() on all tests to make sure additional / 
			 * unnecessary options are not created in post meta.
			 */
			foreach ( array( 'og', 'schema' ) as $md_pre ) {
				if ( ! empty( $opts[$md_pre.'_img_width'] ) &&
					! empty( $opts[$md_pre.'_img_height'] ) &&
					! empty( $opts[$md_pre.'_img_crop'] ) ) {	// check cropped image ratio

					$img_width = $opts[$md_pre.'_img_width'];
					$img_height = $opts[$md_pre.'_img_height'];

					$img_ratio = $img_width >= $img_height ?
						$img_width / $img_height :
						$img_height / $img_width;

					$max_ratio = isset( $this->p->cf['head']['limit_max'][$md_pre.'_img_ratio'] ) ?
						$this->p->cf['head']['limit_max'][$md_pre.'_img_ratio'] :
						$this->p->cf['head']['limit_max']['og_img_ratio'];

					if ( $img_ratio >= $max_ratio ) {
						$this->p->notice->err( sprintf( __( 'The values for \'%1$s\' and  \'%2$s\' have an aspect ratio that is equal to / or greater than %3$s:1 &mdash; resetting these options to their default values.', 'wpsso' ), $md_pre.'_img_width', $md_pre.'_img_height', $max_ratio ) );

						$opts[$md_pre.'_img_width'] = $def_opts[$md_pre.'_img_width'];
						$opts[$md_pre.'_img_height'] = $def_opts[$md_pre.'_img_height'];
						$opts[$md_pre.'_img_crop'] = $def_opts[$md_pre.'_img_crop'];
					}
				}
			}

			// if an image id is being used, remove the image url (only one can be defined)
			if ( ! empty( $opts['og_def_img_id'] ) && ! empty( $opts['og_def_img_url'] ) ) {
				$opts['og_def_img_url'] = '';
			}

			// og_desc_len must be at least 156 chars (defined in config)
			if ( isset( $opts['og_desc_len'] ) && 
				$opts['og_desc_len'] < $this->p->cf['head']['limit_min']['og_desc_len'] )  {
				$opts['og_desc_len'] = $this->p->cf['head']['limit_min']['og_desc_len'];
			}

			// remove the SEO description if a known SEO plugin is active
			if ( isset( $opts['seo_desc'] ) && ! empty( $this->p->avail['seo']['*'] ) ) {
				unset( $opts['seo_desc'] );
			}

			if ( $mod === false ) {
				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( empty( $info['update_auth'] ) ) {
						continue;
					}
					$opt_name = 'plugin_'.$ext.'_'.$info['update_auth'];
					if ( isset( $opts[$opt_name] ) &&
						isset( $this->p->options[$opt_name] ) &&
							$opts[$opt_name] !== $this->p->options[$opt_name] ) {
						// auth id has changed
						$this->p->options[$opt_name] = $opts[$opt_name];
						foreach ( array( 'err', 'inf', 'time' ) as $key ) {
							delete_option( $ext.'_u'.$key );
							delete_option( $ext.'_uapi2'.$key );
						}
					}
				}

				// if there's no google api key, then disable the shortening service
				if ( isset( $opts['plugin_google_api_key'] ) &&
					empty( $opts['plugin_google_api_key'] ) ) {
					$opts['plugin_google_shorten'] = 0;
					$opts['plugin_google_shorten:is'] = 'disabled';
				}

				if ( ! empty( $opts['fb_app_id'] ) && ( ! is_numeric( $opts['fb_app_id'] ) || strlen( $opts['fb_app_id'] ) > 32 ) ) {
					$this->p->notice->err( sprintf( __( 'The Facebook App ID must be numeric and 32 characters or less in length &mdash; the value of "%s" is not valid.', 'wpsso' ), $opts['fb_app_id'] ) );
				}

				if ( ! $network ) {
					if ( empty( $this->p->options['plugin_check_head'] ) ) {
						delete_option( WPSSO_POST_CHECK_NAME );
					}
				}
			}

			// get / remove dimensions for remote image urls
			// allow for multi-options like place_addr_img_url_1
			$img_url_keys = preg_grep( '/_(img|logo|banner)_url(_[0-9]+)?$/', array_keys( $opts ) );
			$this->p->util->add_image_url_size( $img_url_keys, $opts );

			return $opts;
		}

		public function check_value( $key, $val, $def_val, $network = false, &$mod = false ) {

			static $error_messages = null;

			if ( is_array( $val ) ) {	// just in case
				return $val;	// stop here
			}

			// remove multiples, localization, and status for more generic match
			$option_key = preg_replace( '/(_[0-9]+)?(#.*|:[0-9]+)?$/', '', $key );

			// hooked by several extensions
			$option_type = apply_filters( $this->p->cf['lca'].'_option_type', false, $option_key, $network, $mod );

			// translate error messages only once
			if ( $error_messages === null ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'translating error messages' );
				}
				$error_messages = array(
					'url' => __( 'The value of option \'%s\' must be a URL - resetting option to default value.',
						'wpsso' ),
					'csv_urls' => __( 'The value of option \'%s\' must be a comma-delimited list of URL(s) - resetting option to default value.',
						'wpsso' ),
					'numeric' => __( 'The value of option \'%s\' must be numeric - resetting option to default value.',
						'wpsso' ),
					'pos_num' => __( 'The value of option \'%1$s\' must be equal to or greather than %2$s - resetting option to default value.',
						'wpsso' ),
					'blank_num' => __( 'The value of option \'%s\' must be blank or numeric - resetting option to default value.',
						'wpsso' ),
					'api_key' => __( 'The value of option \'%s\' must be alpha-numeric - resetting option to default value.',
						'wpsso' ),
					'color' => __( 'The value of option \'%s\' must be a CSS color code - resetting option to default value.',
						'wpsso' ),
					'date' => __( 'The value of option \'%s\' must be a yyyy-mm-dd date - resetting option to default value.',
						'wpsso' ),
					'time' => __( 'The value of option \'%s\' must be a hh:mm time - resetting option to default value.',
						'wpsso' ),
					'html' => __( 'The value of option \'%s\' must be HTML code - resetting option to default value.',
						'wpsso' ),
					'not_blank' => __( 'The value of option \'%s\' cannot be an empty string - resetting option to default value.',
						'wpsso' ),
				);
			}

			// pre-filter most values to remove html
			switch ( $option_type ) {
				case 'ignore':
					return $val;	// stop here
					break;
				case 'html':		// leave html, css, and javascript code blocks as-is
				case 'code':
					break;
				default:
					$val = wp_filter_nohtml_kses( $val );	// strips all the HTML in the content
					$val = stripslashes( $val );	// strip slashes added by wp_filter_nohtml_kses()
					break;
			}

			// optional cast on return
			$cast_int = false;

			switch ( $option_type ) {
				// must be empty or texturized 
				case 'textured':
					if ( $val !== '' ) {
						$val = trim( wptexturize( ' '.$val.' ' ) );
					}
					break;

				// must be empty or a url
				case 'url':
					if ( $val !== '' ) {
						if ( filter_var( $val, FILTER_VALIDATE_URL ) === false ) {
							$this->p->notice->err( sprintf( $error_messages[$option_type], $key ) );
							$val = $def_val;
						}
					}
					break;

				// strip leading urls off facebook usernames
				case 'url_base':
					if ( $val !== '' ) {
						$val = preg_replace( '/(http|https):\/\/[^\/]*?\//', '', $val );
					}
					break;

				case 'csv_blank':
					if ( $val !== '' ) {
						$val = implode( ', ', SucomUtil::explode_csv( $val ) );
					}
					break;

				case 'csv_urls':
					if ( $val !== '' ) {
						$parts = array();
						foreach ( SucomUtil::explode_csv( $val ) as $part ) {
							if ( filter_var( $part, FILTER_VALIDATE_URL ) === false ) {
								$this->p->notice->err( sprintf( $error_messages[$option_type], $key ) );
								$val = $def_val;
								break;
							} else {
								$parts[] = $part;
							}
						}
						$val = implode( ', ', $parts );
					}
					break;

				// twitter-style usernames (prepend with an @ character)
				case 'at_name':
					if ( $val !== '' ) {
						$val = SucomUtil::get_at_name( $val );
					}
					break;

				// must be integer / numeric
				case 'int':
				case 'integer':
					$cast_int = true;
					// no break
				case 'numeric':
					if ( ! is_numeric( $val ) ) {
						$this->p->notice->err( sprintf( $error_messages['numeric'], $key ) );
						$val = $def_val;
					}
					break;

				// integer / numeric options that must be 1 or more (not zero)
				case 'pos_int':
				case 'img_width':	// image height, subject to minimum value (typically, at least 200px)
				case 'img_height':	// image height, subject to minimum value (typically, at least 200px)
					$cast_int = true;
					// no break
				case 'pos_num':
					if ( $option_type === 'img_width' ) {
						$min_int = $this->p->cf['head']['limit_min']['og_img_width'];
					} elseif ( $option_type === 'img_height' ) {
						$min_int = $this->p->cf['head']['limit_min']['og_img_height'];
					} else {
						$min_int = 1;
					}
					if ( ! empty( $mod['name'] ) && $val === '' ) {	// custom meta options can be empty
						$cast_int = false;
					} elseif ( ! is_numeric( $val ) || $val < $min_int ) {
						$this->p->notice->err( sprintf( $error_messages['pos_num'], $key, $min_int ) );
						$val = $def_val;
					}
					break;

				// must be blank or integer / numeric
				case 'blank_int':
					$cast_int = true;
					// no break
				case 'blank_num':
					if ( $val === '' ) {
						$cast_int = false;
					} else {
						if ( ! is_numeric( $val ) ) {
							$this->p->notice->err( sprintf( $error_messages['blank_num'], $key ) );
							$val = $def_val;
							if ( $val === '' ) {
								$cast_int = false;
							}
						}
					}
					break;

				// empty or alpha-numeric uppercase (hyphens are allowed as well)
				case 'auth_id':
					// silently convert illegal characters to single hyphens and trim excess
					$val = trim( preg_replace( '/[^A-Z0-9\-]+/', '-', $val ), '-' );
					break;

				// empty or alpha-numeric (upper or lower case), plus underscores
				case 'api_key':
					$val = trim( $val );
					if ( $val !== '' && preg_match( '/[^a-zA-Z0-9_]/', $val ) ) {
						$this->p->notice->err( sprintf( $error_messages[$option_type], $key ) );
						$val = $def_val;
					}
					break;

				case 'color':
				case 'date':
				case 'time':
					$val = trim( $val );
					if ( $option_type === 'color' ) {
						$fmt = '/^#[a-fA-f0-9]{6,6}$/';	// color #000000
					} elseif ( $option_type === 'date' ) {
						$fmt = '/^[0-9]{4,4}-[0-9]{2,2}-[0-9]{2,2}$/';	// date yyyy-mm-dd
					} elseif ( $option_type === 'time' ) {
						$fmt = '/^[0-9]{2,2}:[0-9]{2,2}(:[0-9]{2,2})?$/';	// time hh:mm or hh:mm:ss
					}
					if ( $val !== '' && $fmt && ! preg_match( $fmt, $val ) ) {
						$this->p->notice->err( sprintf( $error_messages[$option_type], $key ) );
						$val = $def_val;
					}
					break;

				// text strings that can be blank
				case 'ok_blank':
					if ( $val !== '' ) {
						$val = trim( $val );
					}
					break;

				// text strings that can be blank (line breaks are removed)
				case 'desc':
				case 'one_line':
					if ( $val !== '' ) {
						$val = trim( preg_replace( '/[\s\n\r]+/s', ' ', $val ) );
					}
					break;

				// empty string or must include at least one HTML tag
				case 'html':
					if ( $val !== '' ) {
						$val = trim( $val );
						if ( ! preg_match( '/<.*>/', $val ) ) {
							$this->p->notice->err( sprintf( $error_messages['html'], $key ) );
							$val = $def_val;
						}
					}
					break;

				// options that cannot be blank (aka empty string)
				case 'code':
				case 'not_blank':
					if ( $val === '' && $def_val !== '' ) {
						$this->p->notice->err( sprintf( $error_messages['not_blank'], $key ) );
						error_log( $def_val );
						$val = $def_val;
					}
					break;

				// everything else is a 1 or 0 checkbox option 
				case 'checkbox':
				default:
					if ( $def_val === 0 || $def_val === 1 ) {	// make sure the default option is also a 1 or 0, just in case
						$val = empty( $val ) ? 0 : 1;
					}
					break;
			}

			if ( $cast_int ) {
				return (int) $val;
			} else {
				return $val;
			}
		}

		// save both options and site options
		public function save_options( $options_name, &$opts, $network = false, $has_diff_options = false ) {

			// make sure we have something to work with
			if ( empty( $opts ) || ! is_array( $opts ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: options variable is empty and/or not array' );
				}
				return $opts;
			}

			$has_new_options = empty( $opts['options_version'] ) ? true : false;
			$prev_version = $has_new_options ? '' : $opts['options_version'];	// save the old version string to compare

			// save the plugin version and options version for each extension
			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( isset( $info['version'] ) ) {
					$opts['plugin_'.$ext.'_version'] = $info['version'];
				}
				if ( isset( $info['opt_version'] ) ) {
					$opts['plugin_'.$ext.'_opt_version'] = $info['opt_version'];
				}
			}

			$opts['options_version'] = $this->p->cf['opt']['version'];	// mark the new options array as current

			$opts = apply_filters( $this->p->cf['lca'].'_save_options', $opts, $options_name, $network );

			if ( $network ) {
				$saved = update_site_option( $options_name, $opts );	// auto-creates options with autoload = no
			} else {
				$saved = update_option( $options_name, $opts );		// auto-creates options with autoload = yes
			}

			if ( $saved === true ) {
				// silently save options on activate
				if ( ! $has_new_options && ( $has_diff_options || $prev_version !== $opts['options_version'] ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $options_name.' settings have been updated and saved' );
					}
					if ( is_admin() ) {
						$dis_key = $options_name.'_settings_updated_and_saved';
						$this->p->notice->inf( sprintf( __( 'Plugin settings (%s) have been updated and saved.',	// blue status w pin
							'wpsso' ), $options_name ), true, $dis_key, true );	// can be dismissed
					}
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( $options_name.' settings have been saved silently' );
				}
			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'failed to save the updated '.$options_name.' settings' );
				}
				return false;
			}

			return true;
		}

		public function filter_option_type( $type, $key ) {
			if ( ! empty( $type ) ) {
				return $type;
			}
			switch ( $key ) {
				// use value should be default / empty / force
				case ( preg_match( '/:use$/', $key ) ? true : false ):
					return 'not_blank';
					break;
				// optimize and check for add meta tags options first
				case ( strpos( $key, 'add_' ) === 0 ? true : false ):
				case ( strpos( $key, 'plugin_filter_' ) === 0 ? true : false ):
					return 'checkbox';
					break;
				// empty string or must include at least one HTML tag
				case 'og_vid_embed':
					return 'html';
					break;
				// js and css
				case ( strpos( $key, '_js_' ) !== false ? true : false ):
				case ( strpos( $key, '_css_' ) !== false ? true : false ):
				case ( preg_match( '/(_css|_js|_html)$/', $key ) ? true : false ):
					return 'code';
					break;
				case 'gv_id_title':
				case 'gv_id_desc':
				case 'gv_id_img':
					return 'blank_int';
					break;
				// cast as integer (zero and -1 is ok)
				case 'schema_img_max':
				case 'og_img_max':
				case 'og_vid_max':
				case 'og_desc_hashtags': 
				case ( preg_match( '/_(cache_exp|filter_prio)$/', $key ) ? true : false ):
					return 'integer';
					break;
				// numeric options that must be positive (1 or more)
				case 'plugin_upscale_img_max':
				case 'plugin_min_shorten':
				case ( preg_match( '/_(len|warn)$/', $key ) ? true : false ):
					return 'pos_int';
					break;
				// must be numeric (blank and zero are ok)
				case 'og_def_img_id':
				case 'og_img_id':
				case 'p_img_id':
				case 'product_price':
					return 'blank_num';
					break;
				// image width, subject to minimum value (typically, at least 200px)
				case ( preg_match( '/_img_width$/', $key ) ? true : false ):
				case ( preg_match( '/^tc_[a-z]+_width$/', $key ) ? true : false ):
					return 'img_width';
					break;
				// image height, subject to minimum value (typically, at least 200px)
				case ( preg_match( '/_img_height$/', $key ) ? true : false ):
				case ( preg_match( '/^tc_[a-z]+_height$/', $key ) ? true : false ):
					return 'img_height';
					break;
				// must be texturized 
				case 'og_title_sep':
					return 'textured';
					break;
				// empty of alpha-numeric uppercase (hyphens are allowed as well)
				case ( preg_match( '/_tid$/', $key ) ? true : false ):
					return 'auth_id';
					break;
				// empty or alpha-numeric (upper or lower case), plus underscores
				case 'fb_app_id':
				case 'fb_app_secret':
				case 'p_dom_verify':
				case ( preg_match( '/_api_key$/', $key ) ? true : false ):
					return 'api_key';
					break;
				// text strings that can be blank (line breaks are removed)
				case 'site_name':
				case 'site_desc':
				case 'og_art_section':
				case 'og_title':
				case 'og_desc':
				case 'seo_desc':
				case 'schema_desc':
				case 'tc_desc':
				case 'pin_desc':
				case 'product_brand':
				case 'product_color':
				case 'product_currency':
				case 'product_size':
				case 'plugin_img_alt_prefix':
				case 'plugin_p_cap_prefix':
				case 'plugin_bitly_login':
				case 'plugin_yourls_username':
				case 'plugin_yourls_password':
				case 'plugin_yourls_token':
				case ( strpos( $key, 'plugin_cf_' ) === 0 ? true : false ):	// value is name of meta key
				case ( strpos( $key, '_filter_name' ) !== false ? true : false ):
					return 'one_line';
					break;
				// options that cannot be blank
				case 'site_org_type':
				case 'site_place_id':
				case 'og_author_field':
				case 'seo_author_field':
				case 'og_def_img_id_pre': 
				case 'og_img_id_pre': 
				case 'p_img_id_pre': 
				case 'p_author_name':
				case 'plugin_shortener':		// none or name of shortener
				case 'product_avail':
				case 'product_condition':
				case ( strpos( $key, '_crop_x' ) !== false ? true : false ):
				case ( strpos( $key, '_crop_y' ) !== false ? true : false ):
				case ( preg_match( '/^(plugin|wp)_cm_[a-z]+_(name|label)$/', $key ) ? true : false ):
					return 'not_blank';
					break;
				// twitter-style usernames (prepend with an at)
				case 'tc_site':
					return 'at_name';
					break;
				// strip leading urls off facebook usernames
				case 'fb_admins':
					return 'url_base';
					break;
				/*
				 * Must be a URL.
				 *
				 * Exceptions:
				 *	'add_meta_property_og:image:secure_url' = 1
				 *	'add_meta_property_og:video:secure_url' = 1
				 *	'add_meta_itemprop_url' = 1
				 *	'plugin_cf_img_url' = '_format_image_url'
				 *	'plugin_cf_vid_url' = '_format_video_url'
				 *	'plugin_cf_review_item_image_url' = ''
				 */
				case 'site_url':
				case 'sharing_url':
				case 'fb_page_url':
				case 'og_img_url':
				case 'og_vid_url':
				case 'og_def_img_url':
				case 'p_img_url':
				case 'schema_logo_url':
				case 'schema_banner_url':
				case 'schema_add_type_url':
				case 'plugin_yourls_api_url':
				case ( strpos( $key, '_url' ) && isset( $this->p->cf['form']['social_accounts'][$key] ) ? true : false ):
					return 'url';
					break;
				// css color code
				case ( strpos( $key, '_color_' ) !== false ? true : false ):
					return 'color';
					break;
			}
			return $type;
		}

		public function filter_init_objects() {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log( 'setting allow_cache to true' );
			}
			self::$allow_cache = true;
		}

		public static function can_cache() {
			return self::$allow_cache;
		}
	}
}

?>
