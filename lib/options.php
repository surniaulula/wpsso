<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoOptions' ) ) {

	class WpssoOptions {

		private $upg;
		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			$this->p->util->add_plugin_filters( $this, array( 
				'option_type' => 2,	// identify option type for sanitation
			) );
			do_action( $this->p->cf['lca'].'_init_options' );
		}

		public function get_defaults( $idx = false, $force_filter = false ) {

			if ( ! isset( $this->p->cf['opt']['defaults']['options_filtered'] ) ||
				$this->p->cf['opt']['defaults']['options_filtered'] !== true ||
				$force_filter === true ) {

				$lca = $this->p->cf['lca'];
				$this->p->cf['opt']['defaults'] = $this->p->util->add_ptns_to_opts( $this->p->cf['opt']['defaults'], 
					array( 'plugin_add_to' => 1, 'schema_type_for' => 'webpage' ) );

				$this->p->cf['opt']['defaults']['seo_author_field'] = empty( $this->p->options['plugin_cm_gp_name'] ) ? 
					$this->p->cf['opt']['defaults']['plugin_cm_gp_name'] : $this->p->options['plugin_cm_gp_name'];

				$this->p->cf['opt']['defaults']['og_author_field'] = empty( $this->p->options['plugin_cm_fb_name'] ) ? 
					$this->p->cf['opt']['defaults']['plugin_cm_fb_name'] : $this->p->options['plugin_cm_fb_name'];
	
				// check for default values from network admin settings
				if ( is_multisite() && is_array( $this->p->site_options ) ) {
					foreach ( $this->p->site_options as $key => $val ) {
						if ( array_key_exists( $key, $this->p->cf['opt']['defaults'] ) && 
							array_key_exists( $key.':use', $this->p->site_options ) ) {
	
							if ( $this->p->site_options[$key.':use'] === 'default' )
								$this->p->cf['opt']['defaults'][$key] = $this->p->site_options[$key];
						}
					}
				}

				$this->p->cf['opt']['defaults'] = apply_filters( $lca.'_get_defaults', 
					$this->p->cf['opt']['defaults'] );

				$this->p->cf['opt']['defaults']['options_filtered'] = true;
			}

			if ( $idx !== false ) 
				if ( isset( $this->p->cf['opt']['defaults'][$idx] ) )
					return $this->p->cf['opt']['defaults'][$idx];
				else return false;
			else return $this->p->cf['opt']['defaults'];
		}

		public function get_site_defaults( $idx = false, $force_filter = false ) {

			if ( ! isset( $this->p->cf['opt']['site_defaults']['options_filtered'] ) ||
				$this->p->cf['opt']['site_defaults']['options_filtered'] !== true ||
				$force_filter === true ) {

				$lca = $this->p->cf['lca'];
				$this->p->cf['opt']['site_defaults'] = apply_filters( $lca.'_get_site_defaults', 
					$this->p->cf['opt']['site_defaults'] );

				$this->p->cf['opt']['site_defaults']['options_filtered'] = true;
			}

			if ( $idx !== false ) {
				if ( isset( $this->p->cf['opt']['site_defaults'][$idx] ) )
					return $this->p->cf['opt']['site_defaults'][$idx];
				else return false;
			} else return $this->p->cf['opt']['site_defaults'];
		}

		public function check_options( $options_name, &$opts = array(), $network = false ) {

			if ( ! empty( $opts ) && is_array( $opts ) ) {

				$has_diff_version = false;
				$has_diff_options = false;

				// check for a new plugin and/or extension version
				foreach ( $this->p->cf['plugin'] as $lca => $info ) {

					if ( empty( $info['version'] ) )
						continue;

					$key = 'plugin_'.$lca.'_version';

					if ( empty( $opts[$key] ) || 
						version_compare( $opts[$key], $info['version'], '!=' ) ) {

						WpssoUtil::save_time( $lca, $info['version'], 'update' );
						$opts[$key] = $info['version'];
						$has_diff_version = true;
					}
				}

				// check for an upgrade to the options array
				if ( empty( $opts['options_version'] ) || 
					$opts['options_version'] !== $this->p->cf['opt']['version'] )
						$has_diff_options = true;

				// upgrade the options array if necessary (renamed or removed keys)
				if ( $has_diff_options ) {

					if ( $this->p->debug->enabled )
						$this->p->debug->log( $options_name.' v'.$this->p->cf['opt']['version'].
							' different than saved v'.$opts['options_version'] );

					if ( ! is_object( $this->upg ) ) {
						require_once( WPSSO_PLUGINDIR.'lib/upgrade.php' );
						$this->upg = new WpssoOptionsUpgrade( $this->p );
					}

					$opts = $this->upg->options( $options_name, $opts, $this->get_defaults(), $network );
				}

				// adjust some options based on external factors
				if ( ! $network ) {

					if ( ! $this->p->check->aop( $this->p->cf['lca'], 
						true, $this->p->is_avail['aop'] ) )
							$opts['plugin_filter_content'] = 0;

					// if an seo plugin is found, disable the canonical and description meta tags
					if ( $this->p->is_avail['seo']['*'] ) {
						foreach ( array( 'canonical', 'description' ) as $name ) {
							$opts['add_meta_name_'.$name] = 0;
							$opts['add_meta_name_'.$name.':is'] = 'disabled';
						}
					} 

					$opts['add_meta_name_generator'] = defined( 'WPSSO_META_GENERATOR_DISABLE' ) && 
						WPSSO_META_GENERATOR_DISABLE ? 0 : 1;
				}

				// save options and issue possibly issue reminders
				if ( $has_diff_version || $has_diff_options ) {

					$this->save_options( $options_name, $opts, $network );

					if ( is_admin() ) {
						if ( empty( $opts['plugin_object_cache_exp'] ) ||
							$opts['plugin_object_cache_exp'] < $this->get_defaults( 'plugin_object_cache_exp' ) ) {
							if ( $this->p->check->aop( $this->p->cf['lca'], true, $this->p->is_avail['aop'] ) )
								$this->p->notice->inf( $this->p->msgs->get( 'notice-object-cache-exp' ), true );
							else $opts['plugin_object_cache_exp'] = $this->get_defaults( 'plugin_object_cache_exp' );
						}

						if ( empty( $opts['plugin_filter_content'] ) )
							$this->p->notice->inf( $this->p->msgs->get( 'notice-content-filters-disabled' ), 
								true, true, 'notice-content-filters-disabled', true );

						if ( ! empty( $this->p->options['plugin_head_attr_filter_name'] ) &&
							$this->p->options['plugin_head_attr_filter_name'] === 'head_attributes' )
								$this->p->admin->check_tmpl_head_elements();
					}
				}

				// add missing options for all post types
				$opts = $this->p->util->add_ptns_to_opts( $opts,
					array( 'plugin_add_to' => 1, 'schema_type_for' => 'webpage' ) );

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

				if ( $network === false )
					$opts = $this->get_defaults();
				else $opts = $this->get_site_defaults();
			
				if ( is_admin() ) {
					if ( $network === false )
						$url = $this->p->util->get_admin_url( 'general' );
					else $url = $this->p->util->get_admin_url( 'network' );

					$this->p->notice->err( $err_msg.
						sprintf( __( 'The plugin settings have been returned to their default values &mdash; <a href="%s">please review and save the new settings</a>.', 'wpsso' ), $url ) );
				}
			}

			return $opts;
		}

		// sanitize and validate options
		public function sanitize( $opts = array(), $def_opts = array(), $network = false, $mod = false ) {

			// make sure we have something to work with
			if ( empty( $def_opts ) || ! is_array( $def_opts ) )
				return $opts;

			// add any missing options from the default options
			foreach ( $def_opts as $key => $def_val )
				if ( ! empty( $key ) && ! array_key_exists( $key, $opts ) )
					$opts[$key] = $def_val;

			// sanitize values
			foreach ( $opts as $key => $val ) {
				if ( preg_match( '/:is$/', $key ) )	// don't save option states
					unset( $opts[$key] );
				elseif ( ! empty( $key ) ) {
					$def_val = array_key_exists( $key, $def_opts ) ? $def_opts[$key] : '';
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
			foreach ( array( 'og', 'rp' ) as $md_pre ) {
				if ( ! empty( $opts[$md_pre.'_img_width'] ) &&
					! empty( $opts[$md_pre.'_img_height'] ) &&
					! empty( $opts[$md_pre.'_img_crop'] ) ) {

					$img_width = $opts[$md_pre.'_img_width'];
					$img_height = $opts[$md_pre.'_img_height'];
					$ratio = $img_width >= $img_height ? $img_width / $img_height : $img_height / $img_width;
					if ( $ratio >= $this->p->cf['head']['max_img_ratio'] ) {
						$this->p->notice->err( 'The values for \''.$md_pre.'_img_width\' and  \''.$md_pre.'_img_height\' have an aspect ratio that is equal to / or greater than '.$this->p->cf['head']['max_img_ratio'].':1 &mdash; resetting these options to their default values.', true );
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

			// if there's no google api key, then disable the shortening service
			if ( isset( $opts['plugin_google_api_key'] ) &&
				empty( $opts['plugin_google_api_key'] ) ) {
				$opts['plugin_google_shorten'] = 0;
				$opts['plugin_google_shorten:is'] = 'disabled';
			}

			// og_desc_len must be at least 156 chars (defined in config)
			if ( isset( $opts['og_desc_len'] ) && 
				$opts['og_desc_len'] < $this->p->cf['head']['min_desc_len'] ) 
					$opts['og_desc_len'] = $this->p->cf['head']['min_desc_len'];

			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				if ( ! empty( $info['update_auth'] ) ) {
					$opt_name = 'plugin_'.$lca.'_'.$info['update_auth'];
					if ( isset( $opts[$opt_name] ) &&
						isset( $this->p->options[$opt_name] ) &&
						$opts[$opt_name] !== $this->p->options[$opt_name] ) {

						$this->p->options[$opt_name] = $opts[$opt_name];
						delete_option( $lca.'_umsg' );
						delete_option( $lca.'_utime' );
					}
				}
			}

			if ( ! empty( $opts['fb_app_id'] ) && 
				( ! is_numeric( $opts['fb_app_id'] ) || strlen( $opts['fb_app_id'] ) > 32 ) )
					$this->p->notice->err( sprintf( __( 'The Facebook App ID must be numeric and 32 characters or less in length &mdash; the value of "%s" is not valid.', 'wpsso' ), $opts['fb_app_id'] ), true );

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
			// mark the new options as current
			$prev_opts_version = $opts['options_version'];
			$opts['options_version'] = $this->p->cf['opt']['version'];

			foreach ( $this->p->cf['plugin'] as $lca => $info )
				if ( ! empty( $info['version'] ) )
					$opts['plugin_'.$lca.'_version'] = $info['version'];

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
							'wpsso' ), $options_name ), true );
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
				case 'og_vid_embed':
					return 'html';
					break;
				// js and css
				case ( strpos( $key, '_js_' ) === false ? false : true ):
				case ( strpos( $key, '_css_' ) === false ? false : true ):
				case ( preg_match( '/^[a-z]+_html$/', $key ) ? true : false ):
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
				case 'seo_publisher_url':
				case 'fb_page_url':
				case 'fb_publisher_url':
				case 'schema_logo_url':
				case 'og_def_img_url':
				case 'og_img_url':
					return 'url';
					break;
				// must be numeric (blank or zero is ok)
				case 'fb_app_id':
				case 'og_img_id':
				case 'og_def_img_id':
				case 'og_def_author_id':
				case 'seo_def_author_id':
					return 'blank_num';
					break;
				case 'og_img_max':
				case 'og_vid_max':
				case 'og_desc_hashtags': 
				case 'plugin_file_cache_exp':
				case ( strpos( $key, '_filter_prio' ) === false ? false : true ):
					return 'numeric';
					break;
				// integer options that must be 1 or more (not zero)
				case 'plugin_object_cache_exp':
				case 'plugin_min_shorten':
				case ( preg_match( '/_len$/', $key ) ? true : false ):
					return 'pos_num';
					break;
				// image dimensions, subject to minimum value (typically, at least 200px)
				case ( preg_match( '/_img_(width|height)$/', $key ) ? true : false ):
				case ( preg_match( '/^tc_[a-z]+_(width|height)$/', $key ) ? true : false ):
					return 'img_dim';
					break;
				// must be texturized 
				case 'og_title_sep':
					return 'textured';
					break;
				// must be alpha-numeric (upper or lower case)
				case 'fb_app_secret':
				case 'rp_dom_verify':
				case ( preg_match( '/_api_key$/', $key ) ? true : false ):
					return 'api_key';
					break;
				// must be alpha-numeric uppercase (hyphens allowed as well)
				case ( preg_match( '/_tid$/', $key ) ? true : false ):
					return 'auth_id';
					break;
				// text strings that can be blank
				case 'og_art_section':
				case 'og_title':
				case 'og_desc':
				case 'og_site_name':
				case 'og_site_description':
				case 'schema_desc':
				case 'seo_desc':
				case 'tc_desc':
				case 'plugin_img_alt_prefix':
				case 'plugin_p_cap_prefix':
				case 'plugin_cf_vid_url':
				case 'plugin_cf_vid_embed':
				case 'plugin_bitly_login':
				case ( strpos( $key, '_filter_name' ) === false ? false : true ):
					return 'ok_blank';
					break;
				// options that cannot be blank
				case 'seo_author_field':
				case 'og_def_img_id_pre': 
				case 'og_img_id_pre': 
				case 'og_author_field':
				case 'rp_author_name':
				case 'fb_lang': 
				case 'plugin_shortener':	// none or name of shortener
				case ( preg_match( '/_tid:use$/', $key ) ? true : false ):
				case ( preg_match( '/^(plugin|wp)_cm_[a-z]+_(name|label)$/', $key ) ? true : false ):
					return 'not_blank';
					break;
			}
			return $type;
		}
	}
}

?>
