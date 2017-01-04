<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoOptions' ) ) {

	class WpssoOptions {

		protected $p;			// Wpsso class object
		protected $upg;			// WpssoOptionsUpgrade class object
		protected static $allow_cache = false;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$this->p->util->add_plugin_filters( $this, array( 'option_type' => 2 ), -100 );
			$this->p->util->add_plugin_filters( $this, array( 'init_objects' => 0 ), 9000 );

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'running init_options action' );
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
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'get_defaults filter' );	// start

				$defs = $this->p->util->add_ptns_to_opts( $defs, 
					array( 'plugin_add_to' => 1, 'schema_type_for' => 'webpage' ) );

				$defs['seo_author_field'] = empty( $this->p->options['plugin_cm_gp_name'] ) ? 
					$defs['plugin_cm_gp_name'] : $this->p->options['plugin_cm_gp_name'];

				$defs['og_author_field'] = empty( $this->p->options['plugin_cm_fb_name'] ) ? 
					$defs['plugin_cm_fb_name'] : $this->p->options['plugin_cm_fb_name'];

				// define the Facebook locale value for the default and current locales
				$defs['fb_locale'] = SucomUtil::get_fb_locale( array(), 'default' );
				if ( ( $fb_locale_key = SucomUtil::get_key_locale( 'fb_locale' ) ) !== 'fb_locale' )
					$defs[$fb_locale_key] = SucomUtil::get_fb_locale( array(), 'current' );

				// read Yoast SEO social meta if plugin is active or 'wpseo' settings found
				$defs['plugin_wpseo_social_meta'] = $this->p->is_avail['seo']['wpseo'] || 
					get_option( 'wpseo' ) ? 1 : 0;

				// check for default values from network admin settings
				if ( is_multisite() && is_array( $this->p->site_options ) ) {
					foreach ( $this->p->site_options as $key => $val ) {
						if ( isset( $defs[$key] ) && isset( $this->p->site_options[$key.':use'] ) ) {
							if ( $this->p->site_options[$key.':use'] === 'default' )
								$defs[$key] = $this->p->site_options[$key];
						}
					}
				}

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'applying get_defaults filter' );

				$defs = apply_filters( $lca.'_get_defaults', $defs );

				if ( self::$allow_cache ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'setting options_filtered to true' );
					$defs['options_filtered'] = true;
				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'options_filtered value unchanged' );

				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'get_defaults filter' );	// end

			}

			if ( $idx !== false ) {
				if ( isset( $defs[$idx] ) )
					return $defs[$idx];
				else return null;
			} else return $defs;
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
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'get_site_defaults filter' );	// start
				$defs = apply_filters( $lca.'_get_site_defaults', $defs );
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'get_site_defaults filter' );	// end

				if ( self::$allow_cache ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'setting options_filtered to true' );
					$defs['options_filtered'] = true;
				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'options_filtered value unchanged' );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'get_site_defaults filter skipped' );

			if ( $idx !== false ) {
				if ( isset( $defs[$idx] ) )
					return $defs[$idx];
				else return null;
			} else return $defs;
		}

		public function check_options( $options_name, &$opts = array(), $network = false ) {

			if ( ! empty( $opts ) && is_array( $opts ) ) {

				$lca = $this->p->cf['lca'];
				$has_diff_version = false;
				$has_diff_options = false;

				// check for a new plugin and/or extension version
				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( empty( $info['version'] ) )
						continue;

					$key = 'plugin_'.$ext.'_version';

					if ( empty( $opts[$key] ) || 
						version_compare( $opts[$key], $info['version'], '!=' ) ) {

						WpssoUtil::save_time( $ext, $info['version'], 'update' );
						$opts[$key] = $info['version'];
						$has_diff_version = true;
					}
					unset( $key );
				}

				// check for an upgrade to the options array
				if ( empty( $opts['options_version'] ) || 
					$opts['options_version'] !== $this->p->cf['opt']['version'] )
						$has_diff_options = true;

				// upgrade the options array if necessary (renamed or removed keys)
				if ( $has_diff_options ) {

					if ( $this->p->debug->enabled )
						$this->p->debug->log( $options_name.' v'.$this->p->cf['opt']['version'].
							' different than saved v'.( empty( $opts['options_version'] ) ?
								0 : $opts['options_version'] ) );

					if ( ! is_object( $this->upg ) ) {
						require_once( WPSSO_PLUGINDIR.'lib/upgrade.php' );
						$this->upg = new WpssoOptionsUpgrade( $this->p );
					}

					$def_opts = $network === false ? 
						$this->get_defaults() : 
						$this->get_site_defaults();

					$opts = $this->upg->options( $options_name, $opts, $def_opts, $network );
				}

				// adjust some options based on external factors
				if ( ! $network ) {
					if ( $this->p->check->aop( $lca, false, $this->p->is_avail['aop'] ) ) {
						foreach ( array(
							'plugin_hide_pro' => 0,
						) as $idx => $def_val ) {
							if ( $opts[$idx] === $def_val )
								continue;
							$opts[$idx] = $def_val;
							$has_diff_options = true;	// save the options
						}
					} elseif ( empty( $opts['plugin_'.$lca.'_tid'] ) ) {
						foreach ( array(
							'plugin_filter_title' => 0,
							'plugin_filter_content' => 0,
							'plugin_filter_excerpt' => 0,
							'plugin_check_head' => 1,
							'plugin_upscale_images' => 0,
						) as $idx => $def_val ) {
							if ( $opts[$idx] === $def_val )
								continue;
							if ( is_admin() )
								$this->p->notice->warn( sprintf( __( 'Non-standard value found for "%s" option - resetting to default value.',
									'wpsso' ), $idx ) );
							$opts[$idx] = $def_val;
							$has_diff_options = true;	// save the options
						}
					}

					/*
					 * If an SEO plugin is detected, adjust some related SEO options.
					 */
					if ( $this->p->is_avail['seo']['*'] ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'seo plugin found - checking meta tag options' );
						foreach ( array(
							'add_meta_name_canonical' => 0,
							'add_meta_name_description' => 0,
						) as $idx => $def_val ) {
							$def_val = (int) apply_filters( $lca.'_'.$idx, $def_val );
							$opts[$idx.':is'] = 'disabled';
							if ( $opts[$idx] === $def_val ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( $idx.' already set to '.$def_val );
								continue;
							}
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'setting '.$idx.' to '.$def_val );
							$opts[$idx] = $def_val;
							$has_diff_options = true;	// save the options
						}
					}

					/*
 					 * Generator meta tags are required for plugin support. If you disable the
					 * generator meta tags, but requests for plugin support will be denied.
					 */
					$opts['add_meta_name_generator'] = SucomUtil::get_const( 'WPSSO_META_GENERATOR_DISABLE' ) ? 0 : 1;
				}

				// save options and issue possibly issue reminders
				if ( $has_diff_version || $has_diff_options ) {

					$this->save_options( $options_name, $opts, $network );

					if ( is_admin() ) {
						$def_opts = $network === false ? 
							$this->get_defaults() : 
							$this->get_site_defaults();

						if ( empty( $opts['plugin_filter_content'] ) )
							$this->p->notice->warn( $this->p->msgs->get( 'notice-content-filters-disabled' ), 
								true, 'notice-content-filters-disabled', true );

						if ( ! empty( $this->p->options['plugin_head_attr_filter_name'] ) &&
							$this->p->options['plugin_head_attr_filter_name'] === 'head_attributes' )
								$this->p->admin->check_tmpl_head_attributes();
					}
				}

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'adding options derived from post type names' );

				$opts = $this->p->util->add_ptns_to_opts( $opts,
					array( 'plugin_add_to' => 1, 'schema_type_for' => 'webpage' ) );

				return $opts;

			// $opts should be an array and not empty
			} else {
				if ( $opts === false )
					$err_msg = sprintf( __( 'WordPress could not find an entry for %s in the options table.',
						'wpsso' ), $options_name );
				elseif ( ! is_array( $opts ) )
					$err_msg = sprintf( __( 'WordPress returned a non-array value when reading %s from the options table.',
						'wpsso' ), $options_name );
				elseif ( empty( $opts ) )
					$err_msg = sprintf( __( 'WordPress returned an empty array when reading %s from the options table.',
						'wpsso' ), $options_name );
				else $err_msg = sprintf( __( 'WordPress returned an unknown condition when reading %s from the options table.',
					'wpsso' ), $options_name );

				if ( $this->p->debug->enabled )
					$this->p->debug->log( $err_msg );

				if ( is_admin() ) {
					if ( $network === false )
						$url = $this->p->util->get_admin_url( 'general' );
					else $url = $this->p->util->get_admin_url( 'network' );

					$this->p->notice->err( $err_msg.' '.sprintf( __( 'The plugin settings have been returned to their default values &mdash; <a href="%s">please review and save the new settings</a>.', 'wpsso' ), $url ) );
				}

				return $network === false ?	// return the default options
					$this->get_defaults() : 
					$this->get_site_defaults();
			}
		}

		// sanitize and validate options
		public function sanitize( $opts = array(), $def_opts = array(), $network = false, &$mod = false ) {

			// make sure we have something to work with
			if ( empty( $def_opts ) || ! is_array( $def_opts ) )
				return $opts;

			// add any missing options from the defaults, unless
			// sanitizing for a module (default values will be
			// removed anyway)
			if ( $mod === false ) {
				foreach ( $def_opts as $key => $def_val )
					if ( ! empty( $key ) && ! isset( $opts[$key] ) )
						$opts[$key] = $def_val;
			}

			// sanitize values
			foreach ( $opts as $key => $val ) {
				if ( preg_match( '/:is$/', $key ) )	// don't save option states
					unset( $opts[$key] );
				elseif ( ! empty( $key ) ) {
					$def_val = isset( $def_opts[$key] ) ? $def_opts[$key] : '';	// just in case
					$opts[$key] = $this->p->util->sanitize_option_value( $key, $val, $def_val, $network, $mod );
				}
			}

			/* Adjust Dependent Options
			 *
			 * All options (site and meta as well) are sanitized
			 * here, so use always isset() or array_key_exists() on
			 * all tests to make sure additional / unnecessary
			 * options are not created in post meta.
			 */
			foreach ( array( 'og', 'rp', 'schema' ) as $md_pre ) {
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
			if ( ! empty( $opts['og_def_img_id'] ) &&
				! empty( $opts['og_def_img_url'] ) )
					$opts['og_def_img_url'] = '';

			// og_desc_len must be at least 156 chars (defined in config)
			if ( isset( $opts['og_desc_len'] ) && 
				$opts['og_desc_len'] < $this->p->cf['head']['limit_min']['og_desc_len'] ) 
					$opts['og_desc_len'] = $this->p->cf['head']['limit_min']['og_desc_len'];

			if ( $mod === false ) {
				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( empty( $info['update_auth'] ) )
						continue;
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

				if ( ! empty( $opts['fb_app_id'] ) && 
					( ! is_numeric( $opts['fb_app_id'] ) || strlen( $opts['fb_app_id'] ) > 32 ) )
						$this->p->notice->err( sprintf( __( 'The Facebook App ID must be numeric and 32 characters or less in length &mdash; the value of "%s" is not valid.', 'wpsso' ), $opts['fb_app_id'] ) );

				if ( $network === false ) {
					if ( empty( $this->p->options['plugin_check_head'] ) )
						delete_option( $this->p->cf['lca'].'_post_head_count' );
				}
			}

			// get / remove dimensions for remote image urls
			$img_url_keys = preg_grep( '/_(img|logo|banner)_url$/', array_keys( $opts ) );
			$this->p->util->add_image_url_size( $img_url_keys, $opts );

			return $opts;
		}

		// save both options and site options
		public function save_options( $options_name, &$opts, $network = false ) {

			// make sure we have something to work with
			if ( empty( $opts ) || ! is_array( $opts ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: options variable is empty and/or not array' );
				return $opts;
			}

			// save the old version string to compare
			$prev_opts_version = empty( $opts['options_version'] ) ?
				0 : $opts['options_version'];

			// mark the new options as current
			$opts['options_version'] = $this->p->cf['opt']['version'];

			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( isset( $info['version'] ) )
					$opts['plugin_'.$ext.'_version'] = $info['version'];
				if ( isset( $info['opt_version'] ) )
					$opts['plugin_'.$ext.'_opt_version'] = $info['opt_version'];
			}

			$opts = apply_filters( $this->p->cf['lca'].'_save_options', $opts, $options_name, $network );

			if ( $options_name == WPSSO_SITE_OPTIONS_NAME )
				$saved = update_site_option( $options_name, $opts );	// auto-creates options with autoload = no
			else $saved = update_option( $options_name, $opts );		// auto-creates options with autoload = yes

			if ( $saved === true ) {
				if ( $prev_opts_version !== $opts['options_version'] ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'upgraded '.$options_name.' settings have been saved' );
					if ( is_admin() )
						$this->p->notice->inf( sprintf( __( 'Plugin settings (%s) have been upgraded and saved.',
							'wpsso' ), $options_name ), true, __FUNCTION__.'_upgraded', true );
				}
			} else {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'failed to save the upgraded '.$options_name.' settings' );
				return false;
			}

			return true;
		}

		public function filter_option_type( $type, $key ) {

			if ( ! empty( $type ) )
				return $type;

			switch ( $key ) {
				// optimize and check for add meta tags options now
				case ( strpos( $key, 'add_' ) === 0 ? true : false ):
					return 'checkbox';
					break;
				// empty string or must include at least one HTML tag
				case 'og_vid_embed':
					return 'html';
					break;
				// js and css
				case ( strpos( $key, '_js_' ) === false ? false : true ):
				case ( strpos( $key, '_css_' ) === false ? false : true ):
				case ( preg_match( '/(_css|_js|_html)$/', $key ) ? true : false ):
					return 'code';
					break;
				// twitter-style usernames (prepend with an at)
				case 'tc_site':
					return 'at_name';
					break;
				// strip leading urls off facebook usernames
				case 'fb_admins':
					return 'url_base';
					break;
				// must be a url
				case 'sharing_url':
				case 'fb_page_url':
				case 'og_img_url':
				case 'og_vid_url':
				case 'og_def_img_url':
				case 'rp_img_url':
				case 'schema_logo_url':
				case 'schema_banner_url':
				case 'plugin_yourls_api_url':
				case ( strpos( $key, '_url' ) && 
					isset( $this->p->cf['form']['social_accounts'][$key] ) ? true : false ):
					return 'url';
					break;
				// must be numeric (blank and zero are ok)
				case 'og_def_img_id':
				case 'og_img_id':
				case 'rp_img_id':
					return 'blank_num';
					break;
				// must be numeric (zero and -1 is ok)
				case 'schema_img_max':
				case 'og_img_max':
				case 'og_vid_max':
				case 'og_desc_hashtags': 
				case 'plugin_content_img_max':
				case 'plugin_content_vid_max':
				case ( preg_match( '/_(cache_exp|filter_prio)$/', $key ) ? true : false ):
					return 'numeric';
					break;
				// integer options that must be positive (1 or more)
				case 'plugin_upscale_img_max':
				case 'plugin_min_shorten':
				case ( preg_match( '/_(len|warn)$/', $key ) ? true : false ):
					return 'pos_num';
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
				case 'rp_dom_verify':
				case ( preg_match( '/_api_key$/', $key ) ? true : false ):
					return 'api_key';
					break;
				// text strings that can be blank (line breaks are removed)
				case 'og_art_section':
				case 'og_title':
				case 'og_desc':
				case 'og_site_name':
				case 'og_site_description':
				case 'schema_desc':
				case 'seo_desc':
				case 'tc_desc':
				case 'pin_desc':
				case 'plugin_img_alt_prefix':
				case 'plugin_p_cap_prefix':
				case 'plugin_cf_img_url':		// name of meta key
				case 'plugin_cf_vid_url':		// name of meta key
				case 'plugin_cf_vid_embed':		// name of meta key
				case 'plugin_cf_recipe_ingredients':	// name of meta key
				case 'plugin_bitly_login':
				case 'plugin_yourls_username':
				case 'plugin_yourls_password':
				case 'plugin_yourls_token':
				case ( strpos( $key, '_filter_name' ) === false ? false : true ):
					return 'one_line';
					break;
				// options that cannot be blank
				case 'og_author_field':
				case 'seo_author_field':
				case 'og_def_img_id_pre': 
				case 'og_img_id_pre': 
				case 'rp_img_id_pre': 
				case 'rp_author_name':
				case 'plugin_shortener':	// none or name of shortener
				case ( strpos( $key, '_crop_x' ) === false ? false : true ):
				case ( strpos( $key, '_crop_y' ) === false ? false : true ):
				case ( preg_match( '/^(plugin|wp)_cm_[a-z]+_(name|label)$/', $key ) ? true : false ):
				case ( preg_match( '/:use$/', $key ) ? true : false ):
					return 'not_blank';
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
