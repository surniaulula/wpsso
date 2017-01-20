<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoMeta' ) ) {

	class WpssoMeta {

		protected $p;
		protected $form;
		protected $opts = array();	// cache for options
		protected $defs = array();	// cache for default values

		protected static $head_meta_tags = false;
		protected static $head_meta_info = array();
		protected static $last_column_id = null;	// cache_id of the last column request in list table
		protected static $last_column_array = array();	// array of column values for last column requested 

		public static $mod_array = array(
			'id' => 0,
			'name' => false,
			'obj' => false,
			'use_post' => false,
			/*
			 * Post
			 */
			'is_post' => false,		// is post module
			'is_home' => false,		// home page (archive or static)
			'is_home_page' => false,	// static home page (have post id)
			'is_home_index' => false,	// blog index page (archive)
			'post_type' => false,
			'post_status' => false,
			'post_author' => false,
			'post_coauthors' => array(),
			/*
			 * Term
			 */
			'is_term' => false,		// is term module
			'tax_slug' => '',		// empty string by default
			/*
			 * User
			 */
			'is_user' => false,		// is user module
		);

		public function __construct() {
		}

		public function get_mod( $mod_id ) {
			return $this->must_be_extended( __METHOD__, self::$mod_array );
		}

		public function get_posts( array $mod, $posts_per_page = false, $paged = false ) {
			return $this->must_be_extended( __METHOD__, $array() );	// return empty array
		}

		public function get_posts_mods( array $mod, $posts_per_page = false, $paged = false ) {
			$ret = array();
			foreach ( $this->get_posts( $mod, $posts_per_page, $paged ) as $post ) {
				if ( ! empty( $post->ID ) )	// just in case
					$ret[] = $this->p->m['util']['post']->get_mod( $post->ID );
			}
			return $ret;
		}

		protected function add_actions() {
			return $this->must_be_extended( __METHOD__ );
		}

		public function add_metaboxes() {
			return $this->must_be_extended( __METHOD__ );
		}

		public function show_metabox_social_settings( $obj ) {
			return $this->must_be_extended( __METHOD__ );
		}

		protected function get_social_tabs( $metabox, array &$mod ) {
			switch ( $metabox ) {
				case 'social_settings':
					$tabs = array(
						'text' => _x( 'Edit Text', 'metabox tab', 'wpsso' ),
						'media' => _x( 'Select Media', 'metabox tab', 'wpsso' ),
						'preview' => _x( 'Preview', 'metabox tab', 'wpsso' ),
						'tags' => _x( 'Head Tags', 'metabox tab', 'wpsso' ),
						'validate' => _x( 'Validate', 'metabox tab', 'wpsso' ),
					);
					// keep it clean and remove non-functional form tabs (if required)
					if ( ! empty( $this->p->options['plugin_hide_pro'] ) )
						foreach ( array( 'text', 'media' ) as $key )
							SucomUtil::move_to_end( $tabs, $key );
					break;
				default:
					$tabs = array();	// just in case
					break;
			}
			return apply_filters( $this->p->cf['lca'].'_'.$mod['name'].'_'.$metabox.'_tabs', $tabs, $mod );
		}

		protected function get_table_rows( &$metabox, &$key, &$head_info, &$mod ) {
			$table_rows = array();
			switch ( $key ) {
				case 'preview':
					$table_rows = $this->get_rows_social_preview( $this->form, $head_info, $mod );
					break;

				case 'tags':	
					$table_rows = $this->get_rows_head_tags( $this->form, $head_info, $mod );
					break; 

				case 'validate':
					$table_rows = $this->get_rows_validate( $this->form, $head_info, $mod );
					break; 

			}
			return $table_rows;
		}

		public function get_rows_social_preview( $form, $head_info, $mod ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$table_rows = array();
			$prev_width = 600;
			$prev_height = 315;
			$div_style = 'width:'.$prev_width.'px; height:'.$prev_height.'px;';
			$refresh_cache = $this->p->util->is_force_regen( $mod, 'og' ) ? '?force_regen='.time() : '';
			$media_url = SucomUtil::get_mt_media_url( $head_info, 'og:image' ).$refresh_cache;

			$have_sizes = ( ! empty( $head_info['og:image:width'] ) && $head_info['og:image:width'] > 0 && 
					! empty( $head_info['og:image:height'] ) && $head_info['og:image:height'] > 0 ) ? true : false;

			$is_sufficient = ( $have_sizes === true && 
				$head_info['og:image:width'] >= $prev_width && 
				$head_info['og:image:height'] >= $prev_height ) ? true : false;

			if ( ! empty( $media_url ) ) {
				if ( $have_sizes === true ) {
					$image_preview_html = '<div class="preview_img" style="'.$div_style.' 
					background-size:'.( $is_sufficient === true ? 
						'cover' : $head_info['og:image:width'].' '.$head_info['og:image:height'] ).'; 
					background-image:url('.$media_url.');" />'.( $is_sufficient === true ? 
						'' : '<p>'.sprintf( _x( 'Image Dimensions Smaller<br/>than Suggested Minimum<br/>of %s',
							'preview image error', 'wpsso' ),
								$prev_width.'x'.$prev_height.'px' ).'</p>' ).'</div>';
				} else {
					$image_preview_html = '<div class="preview_img" style="'.$div_style.' 
					background-image:url('.$media_url.');" /><p>'.
					_x( 'Image Dimensions Unknown<br/>or Not Available',
						'preview image error', 'wpsso' ).'</p></div>';
				}
			} else $image_preview_html = '<div class="preview_img" style="'.$div_style.'"><p>'.
				_x( 'No Open Graph Image Found', 'preview image error', 'wpsso' ).'</p></div>';

			if ( isset( $mod['post_status'] ) &&
				$mod['post_status'] === 'auto-draft' ) {

				$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to update this value.',
					'wpsso' ), SucomUtil::titleize( $mod['post_type'] ) );

				$table_rows[] = $form->get_th_html( _x( 'Sharing URL',
					'option label', 'wpsso' ), 'medium' ).
				'<td class="blank"><em>'.$auto_draft_msg.'</em></td>';
	
				$table_rows[] = $form->get_th_html( _x( 'Shortened URL',
					'option label', 'wpsso' ), 'medium' ).
				'<td class="blank"><em>'.$auto_draft_msg.'</em></td>';
	
			} else {
				$long_url = $this->p->util->get_sharing_url( $mod, false );	// $add_page = false

				if ( $mod['is_post'] )
					$short_url = wp_get_shortlink( $mod['id'], 'post' );
				else $short_url = apply_filters( $this->p->cf['lca'].'_shorten_url',
					$long_url, $this->p->options['plugin_shortener'] );

				$table_rows[] = $form->get_th_html( _x( 'Sharing URL',
					'option label', 'wpsso' ), 'medium' ).
				'<td>'.$form->get_copy_input( $long_url ).'</td>';

				$table_rows[] = $form->get_th_html( _x( 'Shortened URL',
					'option label', 'wpsso' ), 'medium' ).
				'<td>'.$form->get_copy_input( $short_url ).'</td>';
			}

			$table_rows[] = $form->get_th_html( _x( 'Open Graph Example',
				'option label', 'wpsso' ), 'medium' ).
			'<td rowspan="2" style="background-color:#e9eaed;border:1px dotted #e0e0e0;">
			<div class="preview_box" style="width:'.( $prev_width + 40 ).'px;">
				<div class="preview_box" style="width:'.$prev_width.'px;">
					'.$image_preview_html.'
					<div class="preview_txt">
						<div class="preview_title">'.( empty( $head_info['og:title'] ) ?
							'No Title' : $head_info['og:title'] ).'</div>
						<div class="preview_desc">'.( empty( $head_info['og:description'] ) ?
							'No Description' : $head_info['og:description'] ).'</div>
						<div class="preview_by">'.( $_SERVER['SERVER_NAME'].
							( empty( $head_info['article:author:name'] ) ?
								'' : ' | By '.$head_info['article:author:name'] ) ).'</div>
					</div>
				</div>
			</div></td>';

			$table_rows[] = '<th class="medium textinfo" id="info-meta-social-preview">'.
				$this->p->msgs->get( 'info-meta-social-preview' ).'</th>';

			return $table_rows;
		}

		public function get_rows_head_tags( &$form, &$head_info, &$mod ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$table_rows = array();
			$script_class = '';

			if ( ! is_array( WpssoMeta::$head_meta_tags ) )	// just in case
				return $table_rows;

			foreach ( WpssoMeta::$head_meta_tags as $parts ) {
				if ( count( $parts ) === 1 ) {
					if ( strpos( $parts[0], '<script ' ) === 0 )
						$script_class = 'script';
					elseif ( strpos( $parts[0], '<noscript ' ) === 0 )
						$script_class = 'noscript';
					$table_rows[] = '<td colspan="5" class="html '.$script_class.'"><pre>'.
						esc_html( $parts[0] ).'</pre></td>';
					if ( $script_class === 'script' ||
						strpos( $parts[0], '</noscript>' ) === 0 )
							$script_class = '';

				// do not show product offers
				} elseif ( isset( $parts[3] ) && strpos( $parts[3], 'product:offer:' ) === 0 ) {
					continue;

				} elseif ( isset( $parts[5] ) && $parts[5] !== -1 ) {

					if ( $parts[1] === 'meta' && 
						$parts[2] === 'itemprop' && 
							strpos( $parts[3], '.' ) !== 0 )
								$match_name = preg_replace( '/^.*\./', '', $parts[3] );
					else $match_name = $parts[3];

					$opt_name = 'add_'.$parts[1].'_'.$parts[2].'_'.$parts[3];

					$tr_class = ( empty( $script_class ) ?
							'' : ' '.$script_class ).
						( empty( $parts[0] ) ?
							' is_disabled' : ' is_enabled' ).
						( empty( $parts[5] ) && 
							! empty( $this->p->options[$opt_name] ) ?
								' is_empty' : '' ).
						( isset( $this->p->options[$opt_name] ) ?
							' is_standard' : ' is_internal hide_row_in_basic' ).'">';

					$table_rows[] = '<tr class="'.trim( $tr_class ).
					'<th class="xshort">'.$parts[1].'</th>'.
					'<th class="xshort">'.$parts[2].'</th>'.
					'<td class="">'.( empty( $parts[6] ) ? 
						'' : '<!-- '.$parts[6].' -->' ).$match_name.'</td>'.
					'<th class="xshort">'.$parts[4].'</th>'.
					'<td class="wide">'.( strpos( $parts[5], 'http' ) === 0 ? 
						'<a href="'.$parts[5].'">'.$parts[5].'</a>' : $parts[5] ).'</td>';
				}
			}

			return $table_rows;
		}

		public function get_rows_validate( &$form, &$head_info, &$mod ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$table_rows = array();
			$sharing_url = $this->p->util->get_sharing_url( $mod, false );	// $add_page = false
			$sharing_url_encoded = urlencode( $sharing_url );

			$amp_url = $mod['is_post'] && 
				$this->p->is_avail['amp_endpoint'] && 
				function_exists( 'amp_get_permalink' ) ?
					'https://validator.ampproject.org/#url='.urlencode( amp_get_permalink( $mod['id'] ) ) : '';

			$bing_url = 'https://www.bing.com/webmaster/diagnostics/markup/validator?url='.$sharing_url_encoded;
			$facebook_url = 'https://developers.facebook.com/tools/debug/og/object?q='.$sharing_url_encoded;
			$google_url = 'https://search.google.com/structured-data/testing-tool/u/0/#url='.$sharing_url_encoded;
			$pinterest_url = 'https://developers.pinterest.com/tools/url-debugger/?link='.$sharing_url_encoded;
			$twitter_url = 'https://cards-dev.twitter.com/validator';
			$w3c_url = 'https://validator.w3.org/nu/?doc='.$sharing_url_encoded;

			// Facebook
			$table_rows[] = $form->get_th_html( _x( 'Facebook Debugger', 'option label', 'wpsso' ), 'medium' ).'<td class="validate"><p>'.__( 'Facebook and most social websites read Open Graph meta tags.', 'wpsso' ).' '.__( 'The Facebook debugger allows you to refresh Facebook\'s cache, while also validating the Open Graph meta tag values.', 'wpsso' ).' '.__( 'The Facebook debugger remains the most stable and reliable method to verify Open Graph meta tags.', 'wpsso' ).'</p><p><i>'.__( 'You may have to click the "Fetch new scrape information" button a few times to refresh Facebook\'s cache.', 'wpsso' ).'</i></p></td><td class="validate">'.$form->get_button( _x( 'Validate Open Graph', 'submit button', 'wpsso' ), 'button-secondary', null, $facebook_url, true ).'</td>';

			// Google
			$table_rows[] = $form->get_th_html( _x( 'Google Structured Data Testing Tool', 'option label', 'wpsso' ), 'medium' ).'<td class="validate"><p>'.__( 'Verify that Google can correctly parse your structured data markup (meta tags, Schema, Microdata, and JSON-LD markup) for Google Search and Google+.', 'wpsso' ).'</p></td><td class="validate">'.$form->get_button( _x( 'Validate Data Markup', 'submit button', 'wpsso' ), 'button-secondary', null, $google_url, true ).'</td>';

			// Pinterest
			$table_rows[] = $form->get_th_html( _x( 'Pinterest Rich Pin Validator', 'option label', 'wpsso' ), 'medium' ).'<td class="validate"><p>'.__( 'Validate the Open Graph / Rich Pin meta tags and apply to have them shown on Pinterest zoomed pins.', 'wpsso' ).'</p></td><td class="validate">'.$form->get_button( _x( 'Validate Rich Pins', 'submit button', 'wpsso' ), 'button-secondary', null, $pinterest_url, true ).'</td>';

			// Twitter
			$table_rows[] = $form->get_th_html( _x( 'Twitter Card Validator', 'option label', 'wpsso' ), 'medium' ).'<td class="validate"><p>'.__( 'The Twitter Card Validator does not accept query arguments &ndash; copy-paste the following sharing URL into the validation input field.', 'wpsso' ).'</p><p>'.$form->get_copy_input( $sharing_url ).'</p></td><td class="validate">'.$form->get_button( _x( 'Validate Twitter Card', 'submit button', 'wpsso' ), 'button-secondary', null, $twitter_url, true ).'</td>';

			// W3C
			$table_rows[] = $form->get_th_html( _x( 'W3C Markup Validation', 'option label', 'wpsso' ), 'medium' ).'<td class="validate"><p>'.__( 'Validate the HTML syntax and HTML 5 conformance of your meta tags and theme templates markup.', 'wpsso' ).'</p>'.( empty( $this->p->options['schema_add_noscript'] ) ? '' : '<p><i>'.sprintf( __( 'When the %1$s option is enabled, the W3C validator will show errors for itemprop attributes in meta elements. You may ignore these errors or disable the %1$s option.', 'wpsso' ), $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_google', 'Meta Property Containers' ) ).'</i></p>' ).'</td><td class="validate">'.$form->get_button( _x( 'Validate HTML Markup', 'submit button', 'wpsso' ), 'button-secondary', null, $w3c_url, true ).'</td>';

			// AMP
			if ( $mod['is_post'] ) {
				$table_rows[] = $form->get_th_html( _x( 'The AMP Validator', 'option label', 'wpsso' ), 'medium' ).'<td class="validate"><p>'.__( 'Validate the HTML syntax and HTML AMP conformance of your meta tags and AMP templates markup.', 'wpsso' ).'</p>'.( $this->p->is_avail['amp_endpoint'] ? '' : '<p><i>'.sprintf( __( 'The <a href="%s">AMP plugin by Automattic</a> is required to validate AMP formatted webpages.', 'wpsso' ), 'https://wordpress.org/plugins/amp/' ).'</i></p>' ).'</td><td class="validate">'.$form->get_button( _x( 'Validate AMP Markup', 'submit button', 'wpsso' ), 'button-secondary', null, $amp_url, true, ( $this->p->is_avail['amp_endpoint'] ? false : true ) ).'</td>';
			}

			return $table_rows;
		}

		/*
		 * Return a specific option from the custom social settings meta with fallback for 
		 * multiple option keys. If $idx is an array, then get the first non-empty option 
		 * from the options array. This is an easy way to provide a fallback value for the 
		 * first array key. Use 'none' as a key name to skip this fallback behavior.
		 *
		 * Example: get_options_multi( $id, array( 'rp_desc', 'og_desc' ) );
		 */
		public function get_options_multi( $mod_id, $idx = false, $filter_options = true ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'mod_id' => $mod_id, 
					'idx' => $idx, 
					'filter_options' => $filter_options, 
				) );
			}

			if ( empty( $mod_id ) )
				return null;

			// return the whole options array
			if ( $idx === false )
				$ret = $this->get_options( $mod_id, $idx, $filter_options );

			// return the first matching index value
			else {
				if ( ! is_array( $idx ) )		// convert a string to an array
					$idx = array( $idx );
				else $idx = array_unique( $idx );	// prevent duplicate idx values

				foreach ( $idx as $key ) {
					if ( $key === 'none' )		// special index keyword
						return null;
					elseif ( empty( $key ) )	// just in case
						continue;
					elseif ( ( $ret = $this->get_options( $mod_id, $key, $filter_options ) ) !== null )
						break;			// stop if/when we have an option
				}
			}

			if ( $ret !== null ) {
				if ( $this->p->debug->enabled ) {
					$mod = $this->get_mod( $mod_id );
					$this->p->debug->log( 'custom '.$mod['name'].' '.
						( $idx === false ? 'options' : ( is_array( $idx ) ? 
							implode( ', ', $idx ) : $idx ) ).' = '.
						( is_array( $ret ) ? print_r( $ret, true ) : '"'.$ret.'"' ) );
				}
			}

			return $ret;
		}

		public function get_options( $mod_id, $idx = false, $filter_options = true ) {
			return $this->must_be_extended( __METHOD__, ( $idx === false ? false : null ) );
		}

		public function get_defaults( $mod_id, $idx = false ) {

			if ( ! isset( $this->defs[$mod_id] ) )
				$this->defs[$mod_id] = array();

			$defs =& $this->defs[$mod_id];		// shortcut
			$opts =& $this->p->options;		// shortcut

			if ( ! WpssoOptions::can_cache() || empty( $defs['options_filtered'] ) ) {

				$defs = array(
					'options_filtered' => '',
					'options_version' => '',
					'og_art_section' => -1,
					'og_title' => '',
					'og_desc' => '',
					'seo_desc' => '',
					'tc_desc' => '',
					'pin_desc' => '',
					'schema_desc' => '',
					'sharing_url' => '',
					'og_img_width' => '',
					'og_img_height' => '',
					'og_img_crop' => ( empty( $opts['og_img_crop'] ) ? 0 : 1 ),
					'og_img_crop_x' => ( empty( $opts['og_img_crop_x'] ) ? 'center' : $opts['og_img_crop_x'] ),
					'og_img_crop_y' => ( empty( $opts['og_img_crop_y'] ) ? 'center' : $opts['og_img_crop_y'] ),
					'og_img_id' => '',
					'og_img_id_pre' => ( empty( $opts['og_def_img_id_pre'] ) ? '' : $opts['og_def_img_id_pre'] ),
					'og_img_url' => '',
					'og_img_max' => -1,
					'og_vid_url' => '',
					'og_vid_embed' => '',
					'og_vid_title' => '',
					'og_vid_desc' => '',
					'og_vid_max' => -1,
					'og_vid_prev_img' => ( empty( $opts['og_vid_prev_img'] ) ? 0 : 1 ),
					'rp_img_width' => '',
					'rp_img_height' => '',
					'rp_img_crop' => ( empty( $opts['rp_img_crop'] ) ? 0 : 1 ),
					'rp_img_crop_x' => ( empty( $opts['rp_img_crop_x'] ) ? 'center' : $opts['rp_img_crop_x'] ),
					'rp_img_crop_y' => ( empty( $opts['rp_img_crop_y'] ) ? 'center' : $opts['rp_img_crop_y'] ),
					'rp_img_id' => '',
					'rp_img_id_pre' => ( empty( $opts['og_def_img_id_pre'] ) ? '' : $opts['og_def_img_id_pre'] ),
					'rp_img_url' => '',
					'schema_img_width' => '',
					'schema_img_height' => '',
					'schema_img_crop' => ( empty( $opts['schema_img_crop'] ) ? 0 : 1 ),
					'schema_img_crop_x' => ( empty( $opts['schema_img_crop_x'] ) ? 'center' : $opts['schema_img_crop_x'] ),
					'schema_img_crop_y' => ( empty( $opts['schema_img_crop_y'] ) ? 'center' : $opts['schema_img_crop_y'] ),
					'schema_img_id' => '',
					'schema_img_id_pre' => ( empty( $opts['og_def_img_id_pre'] ) ? '' : $opts['og_def_img_id_pre'] ),
					'schema_img_url' => '',
					'schema_img_max' => -1,
				);

				$defs = apply_filters( $this->p->cf['lca'].'_get_md_defaults', $defs, $this->get_mod( $mod_id ) );

				if ( WpssoOptions::can_cache() ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'setting options_filtered to true' );
					$defs['options_filtered'] = true;
				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'options_filtered value unchanged' );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'get_defaults filter skipped' );

			if ( $idx !== false ) {
				if ( isset( $defs[$idx] ) )
					return $defs[$idx];
				else return null;
			} else return $defs;
		}

		public function save_options( $mod_id, $rel_id = false ) {
			return $this->must_be_extended( __METHOD__, $mod_id );
		}

		public function clear_cache( $mod_id, $rel_id = false ) {
			// nothing to do
			return $mod_id;
		}

		public function delete_options( $mod_id, $rel_id = false ) {
			return $this->must_be_extended( __METHOD__, $mod_id );
		}

		protected function not_implemented( $method, $ret = true ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->log( $method.' not implemented in this version',
					get_class( $this ) );	// log the extended class name
			return $ret;
		}

		protected function must_be_extended( $method, $ret = true ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->log( $method.' must be extended',
					get_class( $this ) );	// log the extended class name
			return $ret;
		}

		protected function verify_submit_nonce() {
			if ( empty( $_POST ) ) {

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'empty POST for submit' );
				return false;

			} elseif ( empty( $_POST[ WPSSO_NONCE ] ) ) {

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'submit POST missing nonce token' );
				return false;

			} elseif ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE ], WpssoAdmin::get_nonce() ) ) {

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'submit nonce token validation failed' );
				if ( is_admin() )
					$this->p->notice->err( __( 'Nonce token validation failed for the submitted form (update ignored).',
						'wpsso' ) );
				return false;

			} else return true;
		}

		protected function get_submit_opts( $mod_id ) {

			$mod = $this->get_mod( $mod_id );
			$defs = $this->get_defaults( $mod['id'] );
			$prev = $this->get_options( $mod['id'] );

			/*
			 * Remove version strings
			 */
			$unset_keys = array( 'options_filtered', 'options_version' );

			foreach ( $this->p->cf['plugin'] as $ext => $info )
				if ( isset( $info['opt_version'] ) )
					$unset_keys[] = 'plugin_'.$ext.'_opt_version';

			foreach ( $unset_keys as $key )
				unset( $defs[$key], $prev[$key] );

			/*
			 * Merge and sanitize the new options
			 */
			$opts = empty( $_POST[ WPSSO_META_NAME ] ) ?			// make sure we have an array
				array() : $_POST[ WPSSO_META_NAME ];
			$opts = SucomUtil::restore_checkboxes( $opts );
			$opts = array_merge( $prev, $opts );				// update the previous options array
			$opts = $this->p->opt->sanitize( $opts, $defs, false, $mod );	// $network = false

			/*
			 * Image size options (id, prefix, width, height, crop, etc.)
			 */
			foreach ( array( 'rp', 'og' ) as $md_pre ) {
				if ( empty( $opts[$md_pre.'_img_id'] ) )
					unset( $opts[$md_pre.'_img_id_pre'] );

				$force_regen = false;
				foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $key ) {
					// if option is the same as the default, then unset it
					if ( isset( $opts[$md_pre.'_img_'.$key] ) &&
						isset( $defs[$md_pre.'_img_'.$key] ) &&
							$opts[$md_pre.'_img_'.$key] === $defs[$md_pre.'_img_'.$key] )
								unset( $opts[$md_pre.'_img_'.$key] );

					$check_current = isset( $opts[$md_pre.'_img_'.$key] ) ?
						$opts[$md_pre.'_img_'.$key] : '';
					$check_previous = isset( $prev[$md_pre.'_img_'.$key] ) ?
						$prev[$md_pre.'_img_'.$key] : '';
					if ( $check_current !== $check_previous ) {
						$force_regen = true;
					}
				}
				if ( $force_regen !== false )
					$this->p->util->set_force_regen( $mod, $md_pre );
			}

			/*
			 * Remove "use plugin settings" (numeric or string -1), or "same as default" option values
			 */
			foreach ( $opts as $key => $def_val ) {
				if ( $opts[$key] === -1 || $opts[$key] === '-1' ||
					( isset( $defs[$key] ) && $opts[$key] === $defs[$key] ) )
						unset( $opts[$key] );
			}

			/*
			 * Mark the new options as current
			 */
			if ( ! empty( $opts ) ) {
				$opts['options_version'] = $this->p->cf['opt']['version'];
				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( isset( $info['opt_version'] ) )
						$opts['plugin_'.$ext.'_opt_version'] = $info['opt_version'];
				}
			}

			return $opts;
		}

		// return column heading keys and translated label names
		public function get_column_headings() { 
			return array( 
				'schema_type' => sprintf( _x( '%s Schema',
					'column title', 'wpsso' ),
						$this->p->cf['menu_label'] ),
				'og_img' => sprintf( _x( '%s Img',
					'column title', 'wpsso' ),
						$this->p->cf['menu_label'] ),
				'og_desc' => sprintf( _x( '%s Desc',
					'column title', 'wpsso' ),
						$this->p->cf['menu_label'] ),
			);
		}

		// return sortable column keys and their query sort info
		public function get_sortable_columns( $idx = false ) { 
			$lca = $this->p->cf['lca'];
			$sortable = array( 
				'schema_type' => array(
					'meta_key' => '_'.$lca.'_head_info_schema_type',
					'orderby' => 'meta_value',
				),
				'og_img' => array(
					'meta_key' => '_'.$lca.'_head_info_og_img_thumb',
					'orderby' => false,	// do not offer column sorting
				),
				'og_desc' => array(
					'meta_key' => '_'.$lca.'_head_info_og_desc',
					'orderby' => false,	// do not offer column sorting
				),
			);
			if ( $idx !== false ) {
				if ( isset( $sortable[$idx] ) )
					return $sortable[$idx];
				else return null;
			} else return $sortable;
		}

		public function update_sortable_meta( $obj_id, $column_key, $content ) { 
			return $this->must_be_extended( __METHOD__ );
		}

		public function add_sortable_columns( $columns ) { 
			$lca = $this->p->cf['lca'];
			foreach ( $this->get_sortable_columns() as $column_key => $sort_cols ) {
				if ( ! empty( $sort_cols['orderby'] ) ) {
					$columns[$lca.'_'.$column_key] = $lca.'_'.$column_key;
				}
			}
			return $columns;
		}

		public function set_column_orderby( $query ) { 
			$lca = $this->p->cf['lca'];
			$column_name = $query->get( 'orderby' );
			if ( $column_name && strpos( $column_name, $lca.'_' ) === 0 ) {
				$column_key = str_replace( $lca.'_', '', $column_name );
				if ( ( $sort_cols = $this->get_sortable_columns( $column_key ) ) !== null ) {
					foreach ( array( 'meta_key', 'orderby' ) as $set_name ) {
						if ( ! empty( $sort_cols[$set_name] ) ) {
							$query->set( $set_name, $sort_cols[$set_name] );
						}
					}
				}
			}
		}

		public function add_mod_column_headings( $columns, $mod_name = '' ) { 
			if ( ! empty( $mod_name ) ) {
				foreach ( $this->get_column_headings() as $column_key => $label ) {
					if ( ! empty( $this->p->options['plugin_'.$column_key.'_col_'.$mod_name] ) )
						$columns[$this->p->cf['lca'].'_'.$column_key] = $label;
				}
			}
			return $columns;
		}

		public function get_og_img_column_html( $head_info, $mod ) {
			$value = false;
			$force_regen = $this->p->util->is_force_regen( $mod, 'og' );	// false by default

			if ( isset( $head_info['og:image:id'] ) && 
				$head_info['og:image:id'] > 0 ) {

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'getting thumbnail for image id '.$head_info['og:image:id'] );

				list(
					$og_img_thumb['og:image'],
					$og_img_thumb['og:image:width'],
					$og_img_thumb['og:image:height'],
					$og_img_thumb['og:image:cropped'],
					$og_img_thumb['og:image:id']
				) = $this->p->media->get_attachment_image_src( $head_info['og:image:id'], 'thumbnail', false, $force_regen );
				if ( ! empty( $og_img_thumb['og:image'] ) )	// just in case
					$head_info =& $og_img_thumb;
			}

			$refresh_cache = $force_regen ? '?force_regen='.time() : '';
			$media_url = SucomUtil::get_mt_media_url( $head_info, 'og:image' ).$refresh_cache;

			if ( ! empty( $media_url ) )
				$value = '<div class="preview_img" style="background-image:url('.$media_url.');"></div>';

			return $value;
		}

		public function get_og_image( $num, $size_name, $mod_id, $check_dupes = true, $force_regen = false, $md_pre = 'og' ) {
			return $this->must_be_extended( __METHOD__, array() );
		}

		public function get_md_image( $num, $size_name, array $mod, $check_dupes = true, $force_regen = false, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'num' => $num,
					'size_name' => $size_name,
					'mod' => $mod,
					'check_dupes' => $check_dupes,
					'force_regen' => $force_regen,
					'md_pre' => $md_pre,
					'mt_pre' => $mt_pre,
				), get_class( $this ) );
			}

			$meta_ret = array();

			if ( empty( $mod['id'] ) )
				return $meta_ret;

			// unless $md_pre is 'none' allways fallback to the 'og' custom meta
			foreach( array_unique( array( $md_pre, 'og' ) ) as $prefix ) {

				if ( $prefix === 'none' )	// special index keyword
					break;
				elseif ( empty( $prefix ) )	// skip empty md_pre values
					continue;

				$meta_image = SucomUtil::get_mt_prop_image( $mt_pre );

				// get the image id, library prefix, and/or url values
				$pid = $this->get_options( $mod['id'], $prefix.'_img_id' );
				$pre = $this->get_options( $mod['id'], $prefix.'_img_id_pre' );
				$url = $this->get_options( $mod['id'], $prefix.'_img_url' );

				if ( $pid > 0 ) {
					$pid = $pre === 'ngg' ? 'ngg-'.$pid : $pid;

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'using custom '.$prefix.' image id = "'.$pid.'"',
							get_class( $this ) );	// log extended class name

					list( 
						$meta_image[$mt_pre.':image'],
						$meta_image[$mt_pre.':image:width'],
						$meta_image[$mt_pre.':image:height'],
						$meta_image[$mt_pre.':image:cropped'],
						$meta_image[$mt_pre.':image:id']
					) = $this->p->media->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );
				}

				if ( empty( $meta_image[$mt_pre.':image'] ) && ! empty( $url ) ) {

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'using custom '.$prefix.' image url = "'.$url.'"',
							get_class( $this ) );	// log extended class name

					$width = $this->get_options( $mod['id'], $prefix.'_img_url:width' );
					$height = $this->get_options( $mod['id'], $prefix.'_img_url:height' );

					list(
						$meta_image[$mt_pre.':image'],
						$meta_image[$mt_pre.':image:width'],
						$meta_image[$mt_pre.':image:height']
					) = array(
						$url,
						( $width > 0 ? $width : -1 ), 
						( $height > 0 ? $height : -1 )
					);
				}

				if ( ! empty( $meta_image[$mt_pre.':image'] ) &&
					$this->p->util->push_max( $meta_ret, $meta_image, $num ) )
						return $meta_ret;
			}

			foreach ( apply_filters( $this->p->cf['lca'].'_'.$mod['name'].'_image_ids', array(), $size_name, $mod['id'] ) as $pid ) {
				if ( $pid > 0 ) {	// quick sanity check
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'adding image pid: '.$pid );

					$meta_image = SucomUtil::get_mt_prop_image( $mt_pre );

					list( 
						$meta_image[$mt_pre.':image'],
						$meta_image[$mt_pre.':image:width'],
						$meta_image[$mt_pre.':image:height'],
						$meta_image[$mt_pre.':image:cropped'],
						$meta_image[$mt_pre.':image:id']
					) = $this->p->media->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );

					if ( ! empty( $meta_image[$mt_pre.':image'] ) &&
						$this->p->util->push_max( $meta_ret, $meta_image, $num ) )
							return $meta_ret;
				}
			}

			foreach ( apply_filters( $this->p->cf['lca'].'_'.$mod['name'].'_image_urls', array(), $size_name, $mod['id'] ) as $url ) {
				if ( strpos( $url, '://' ) !== false ) {	// quick sanity check

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'adding image url: '.$url );

					$meta_image = SucomUtil::get_mt_prop_image( $mt_pre );
					$meta_image[$mt_pre.':image'] = $url;
					$this->p->util->add_image_url_size( $mt_pre.':image', $meta_image );

					if ( ! empty( $meta_image[$mt_pre.':image'] ) &&
						$this->p->util->push_max( $meta_ret, $meta_image, $num ) )
							return $meta_ret;
				}
			}

			return $meta_ret;
		}

		public function get_og_video( $num = 0, $mod_id, $check_dupes = false, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'num' => $num,
					'mod_id' => $mod_id,
					'check_dupes' => $check_dupes,
					'md_pre' => $md_pre,
					'mt_pre' => $mt_pre,
				), get_class( $this ) );
			}

			$mod = $this->get_mod( $mod_id );	// required for get_content_videos()
			$og_ret = array();
			$og_video = array();

			if ( empty( $mod_id ) )
				return $og_ret;

			foreach( array_unique( array( $md_pre, 'og' ) ) as $prefix ) {

				$html = $this->get_options( $mod_id, $prefix.'_vid_embed' );
				$url = $this->get_options( $mod_id, $prefix.'_vid_url' );

				if ( ! empty( $html ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'fetching video(s) from custom '.$prefix.' embed code',
							get_class( $this ) );	// log extended class name
					$og_video = $this->p->media->get_content_videos( $num, $mod, $check_dupes, $html );
					if ( ! empty( $og_video ) )
						return array_merge( $og_ret, $og_video );
				}

				if ( ! empty( $url ) && ( $check_dupes == false || $this->p->util->is_uniq_url( $url ) ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'fetching video from custom '.$prefix.' url '.$url,
							get_class( $this ) );	// log extended class name
					$og_video = $this->p->media->get_video_info( $url, 0, 0, $check_dupes, true );	// $fallback = true
					if ( $this->p->util->push_max( $og_ret, $og_video, $num ) ) 
						return $og_ret;
				}
			}
			return $og_ret;
		}

		public function get_og_video_preview_image( $mod, $check_dupes = false, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'mod' => $mod,
					'check_dupes' => $check_dupes,
					'md_pre' => $md_pre,
				), get_class( $this ) );
			}

			$og_image = array();

			// fallback to value from general plugin settings
			if ( ( $use_prev_img = $this->get_options( $mod['id'], 'og_vid_prev_img' ) ) === null )
				$use_prev_img = $this->p->options['og_vid_prev_img'];

			// get video preview images if allowed
			if ( ! empty( $use_prev_img ) ) {

				// assumes the first video will have a preview image
				$og_video = $this->p->og->get_all_videos( 1, $mod, $check_dupes, $md_pre );

				if ( ! empty( $og_video ) && is_array( $og_video ) ) {
					foreach ( $og_video as $video ) {
						if ( ! empty( $video['og:image'] ) ) {
							$og_image[] = $video;
							break;
						}
					}
				}
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'use_prev_img is 0 - skipping retrieval of video preview image' );

			return $og_image;
		}

		protected function get_custom_fields( $md_opts, $all_meta ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! is_array( $all_meta ) || empty( $all_meta ) )
				return $md_opts;

			$charset = get_bloginfo( 'charset' );	// required for html_entity_decode()

			foreach ( array( 
				'plugin_cf_img_url' => 'og_img_url',
				'plugin_cf_vid_url' => 'og_vid_url',
				'plugin_cf_vid_embed' => 'og_vid_embed',
				'plugin_cf_recipe_ingredients' => 'schema_recipe_ingredient',
			) as $cf_opt_name => $meta_opt_name ) {

				// check that a custom field name has been defined
				if ( ! empty( $this->p->options[$cf_opt_name] ) )
					$md_name = $this->p->options[$cf_opt_name];
				else continue;

				// empty or not, if the array element is set, use it
				if ( isset( $all_meta[$md_name][0] ) )
					$mixed =& $all_meta[$md_name][0];
				else continue;

				if ( $this->p->debug->enabled )
					$this->p->debug->log( $md_name.' custom field found for '.$meta_opt_name.' option' );

				$values = array();

				// decode strings and array elements
				if ( is_array( $mixed ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $md_name.' is array of '.count( $mixed ).' values (decoding each value)' );
					foreach ( $mixed as $value )
						$values[] = html_entity_decode( SucomUtil::decode_utf8( $value ), ENT_QUOTES, $charset );
				} else {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'decoding '.$md_name.' as string of '.strlen( $mixed ).' chars' );
					$values[] = html_entity_decode( SucomUtil::decode_utf8( $mixed ), ENT_QUOTES, $charset );
				}

				switch ( $meta_opt_name ) {
					case 'schema_recipe_ingredient':
						// explode text ingredient list into array
						if ( ! is_array( $mixed ) ) {
							$values = array_map( 'trim', 
								explode( PHP_EOL, reset( $values ) ) );
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'exploded '.$md_name.' into array of '.count( $values ).' elements' );
						}
						$is_multi = true;		// increment the option name
						break;
					default:
						$is_multi = false;
						break;
				}

				// increment the option name, starting with 0
				if ( $is_multi ) {
					// remove any old values from the options array
					$md_opts = SucomUtil::preg_grep_keys( '/^'.$meta_opt_name.'_[0-9]+$/', $md_opts, true );	// $invert = true

					foreach ( $values as $num => $value ) {
						$md_opts[$meta_opt_name.'_'.$num] = $value;
						$md_opts[$meta_opt_name.'_'.$num.':is'] = 'disabled';
					}
				} else {
					$md_opts[$meta_opt_name] = reset( $values );	// get first element of $values array
					$md_opts[$meta_opt_name.':is'] = 'disabled';
				}
			}

			return $md_opts;
		}
	}
}

?>
