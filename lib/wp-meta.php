<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoWpMeta' ) ) {

	class WpssoWpMeta {

		protected $p;
		protected $form;
		protected $opts = array();	// Cache for options.
		protected $defs = array();	// Cache for default values.

		protected static $head_meta_tags = false;
		protected static $head_meta_info = array();
		protected static $last_column_id = null;	// Cache id of the last column request in list table.
		protected static $last_column_array = array();	// Array of column values for last column requested.

		protected static $rename_md_options_keys = array(
			'wpsso' => array(
				499 => array(
					'link_desc' => 'seo_desc',
					'meta_desc' => 'seo_desc',
				),
				503 => array(
					'schema_recipe_calories' => 'schema_recipe_nutri_cal',
				),
				514 => array(
					'rp_img_id'     => 'schema_img_id',
					'rp_img_id_pre' => 'schema_img_id_pre',
					'rp_img_width'  => 'schema_img_width',
					'rp_img_height' => 'schema_img_height',
					'rp_img_crop'   => 'schema_img_crop',
					'rp_img_crop_x' => 'schema_img_crop_x',
					'rp_img_crop_y' => 'schema_img_crop_y',
					'rp_img_url'    => 'schema_img_url',
				),
				520 => array(
					'p_img_id'     => 'schema_img_id',
					'p_img_id_pre' => 'schema_img_id_pre',
					'p_img_width'  => 'schema_img_width',
					'p_img_height' => 'schema_img_height',
					'p_img_crop'   => 'schema_img_crop',
					'p_img_crop_x' => 'schema_img_crop_x',
					'p_img_crop_y' => 'schema_img_crop_y',
					'p_img_url'    => 'schema_img_url',
				),
				537 => array(
					'schema_add_type_url' => 'schema_addl_type_url_0',
				),
				569 => array(
					'schema_add_type_url' => 'schema_addl_type_url',	// Option modifiers are preserved.
				),
				615 => array(
					'org_type' => 'org_schema_type',
				),
			),
		);

		public static $mod_defaults = array(

			/**
			 * Common elements.
			 */
			'id'   => 0,			// Post, term, or user ID.
			'name' => false,		// Module name ('post', 'term', or 'user').
			'obj'  => false,		// Module object.

			/**
			 * Post elements.
			 */
			'use_post'             => false,
			'is_post'              => false,	// Is post module.
			'is_post_type_archive' => false,	// Post is an archive.
			'is_home'              => false,	// Home page (index or static)
			'is_home_page'         => false,	// Static front page.
			'is_home_index'        => false,	// Static posts page or home index.
			'post_slug'            => false,	// Post name (aka slug).
			'post_type'            => false,	// Post type name.
			'post_mime'            => false,	// Post mime type (ie. image/jpg).
			'post_status'          => false,	// Post status name.
			'post_author'          => false,	// Post author id.
			'post_coauthors'       => array(),

			/**
			 * Term elements.
			 */
			'is_term'  => false,		// Is term module.
			'tax_slug' => '',		// Empty string by default.

			/**
			 * User elements.
			 */
			'is_user' => false,		// Is user module.
		);

		public function __construct() {
		}

		public function get_mod( $mod_id ) {
			return $this->must_be_extended( __METHOD__, self::$mod_defaults );
		}

		public function get_posts_ids( array $mod, $ppp = false, $paged = false, array $posts_args = array() ) {
			return $this->must_be_extended( __METHOD__, array() );	// Return an empty array.
		}

		public function get_posts_mods( array $mod, $ppp = false, $paged = false, array $posts_args = array() ) {

			$posts_mods = array();

			foreach ( $this->get_posts_ids( $mod, $ppp, $paged, $posts_args ) as $post_id ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting mod for post object ID ' . $post_id );
				}

				$posts_mods[] = $this->p->m[ 'util' ][ 'post' ]->get_mod( $post_id );
			}

			return $posts_mods;
		}

		protected function add_actions() {
			return $this->must_be_extended( __METHOD__ );
		}

		public function add_meta_boxes() {
			return $this->must_be_extended( __METHOD__ );
		}

		public function ajax_metabox_custom_meta() {
			return $this->must_be_extended( __METHOD__ );
		}

		public function show_metabox_custom_meta( $obj ) {
			return $this->must_be_extended( __METHOD__ );
		}

		public function get_metabox_custom_meta( $obj ) {
			return $this->must_be_extended( __METHOD__ );
		}

		/**
		 * Does this page have a post/term/user SSO metabox?
		 *
		 * If this is a post/term/user editing page, and the SSO metabox is shown, then the 
		 * WpssoWpMeta::$head_meta_tags variable will be an array *and* include the head meta
		 * tags array.
		 */
		public static function is_meta_page() {

			if ( ! empty( WpssoWpMeta::$head_meta_tags ) ) {
				return true;
			}

			return false;
		}

		protected function get_custom_meta_tabs( $metabox_id, array &$mod ) {

			switch ( $metabox_id ) {

				case $this->p->cf[ 'meta' ][ 'id' ]:

					$tabs = array(
						'edit'     => _x( 'Customize', 'metabox tab', 'wpsso' ),
						'media'    => _x( 'Priority Media', 'metabox tab', 'wpsso' ),
						'preview'  => _x( 'Preview', 'metabox tab', 'wpsso' ),
						'head'     => _x( 'Head', 'metabox tab', 'wpsso' ),
						'validate' => _x( 'Validate', 'metabox tab', 'wpsso' ),
					);

					break;

				default:

					$tabs = array();	// Just in case.

					break;
			}

			return apply_filters( $this->p->lca . '_' . $mod[ 'name' ] . '_custom_meta_tabs', $tabs, $mod, $metabox_id );
		}

		protected function get_table_rows( $metabox_id, $tab_key, $head_info, $mod ) {

			$table_rows = array();

			switch ( $tab_key ) {

				case 'preview':

					$table_rows = $this->get_rows_preview_tab( $this->form, $head_info, $mod );

					break;

				case 'head':	

					$table_rows = $this->get_rows_head_tab( $this->form, $head_info, $mod );

					break; 

				case 'validate':

					$table_rows = $this->get_rows_validate_tab( $this->form, $head_info, $mod );

					break; 

			}

			return $table_rows;
		}

		public function get_rows_preview_tab( $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows  = array();
			$prev_width  = 600;
			$prev_height = 315;
			$force_regen = $this->p->util->is_force_regen( $mod, 'og' ) ? '?force_regen=' . time() : '';
			$media_url   = SucomUtil::get_mt_media_url( $head_info ) . $force_regen;

			$have_sizes = ( isset( $head_info[ 'og:image:width' ] ) && $head_info[ 'og:image:width' ] > 0 && 
				isset( $head_info[ 'og:image:height' ] ) && $head_info[ 'og:image:height' ] > 0 ) ? true : false;

			$is_sufficient = true === $have_sizes && $head_info[ 'og:image:width' ] >= $prev_width && 
				$head_info[ 'og:image:height' ] >= $prev_height ? true : false;

			if ( ! empty( $media_url ) ) {

				if ( true === $have_sizes ) {

					$image_preview_html = '<div class="preview_img" style=" background-size:' . 
						( true === $is_sufficient ? 'cover' : $head_info[ 'og:image:width' ] . ' ' . $head_info[ 'og:image:height' ] ) . 
							'; background-image:url(' . $media_url . ');" />' . 
						( true === $is_sufficient ? '' : '<p>' . sprintf( _x( 'Image Dimensions Smaller<br/>than Suggested Minimum<br/>of %s',
							'preview image error', 'wpsso' ), $prev_width . 'x' . $prev_height . 'px' ) . '</p>' ) . '</div>';
				} else {

					$image_preview_html = '<div class="preview_img" style="background-image:url(' . $media_url . ');" /><p>' . 
						_x( 'Image Dimensions Unknown<br/>or Not Available', 'preview image error', 'wpsso' ) . '</p></div>';
				}
				
			} else {
				$image_preview_html = '<div class="preview_img"><p>' . 
					_x( 'No Open Graph Image Found', 'preview image error', 'wpsso' ) . '</p></div>';
			}

			if ( isset( $mod[ 'post_status' ] ) && $mod[ 'post_status' ] === 'auto-draft' ) {

				$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to update this value.',
					'wpsso' ), SucomUtil::titleize( $mod[ 'post_type' ] ) );

				$table_rows[] = '' . 
				$form->get_th_html( _x( 'Sharing URL', 'option label', 'wpsso' ), 'medium' ) .
				'<td class="blank"><em>' . $auto_draft_msg . '</em></td>';
	
				$table_rows[] = '<tr class="hide_in_basic">' .
				$form->get_th_html( _x( 'Canonical URL', 'option label', 'wpsso' ), 'medium' ) .
				'<td class="blank"><em>' . $auto_draft_msg . '</em></td>';
	
				$table_rows[] = '<tr class="hide_in_basic">' .
				$form->get_th_html( _x( 'Shortlink URL', 'option label', 'wpsso' ), 'medium' ) .
				'<td class="blank"><em>' . $auto_draft_msg . '</em></td>';
	
			} else {

				$sharing_url   = $this->p->util->get_sharing_url( $mod, false );     // $add_page is false.
				$canonical_url = $this->p->util->get_canonical_url( $mod, false ); // $add_page is false.

				if ( $mod[ 'is_post' ] ) {
					$shortlink_url = SucomUtilWP::wp_get_shortlink( $mod[ 'id' ], 'post' );	// $context is post.
				} else {
					$shortlink_url = apply_filters( $this->p->lca . '_get_short_url', $sharing_url,
						$this->p->options[ 'plugin_shortener' ], $mod );
				}

				$table_rows[] = '' . 
				$form->get_th_html( _x( 'Sharing URL', 'option label', 'wpsso' ), 'medium' ) . 
				'<td>' . $form->get_input_copy_clipboard( $sharing_url ) . '</td>';

				$table_rows[] = ( $sharing_url === $canonical_url ? '<tr class="hide_in_basic">' : '' ) . 
				$form->get_th_html( _x( 'Canonical URL', 'option label', 'wpsso' ), 'medium' ) . 
				'<td>' . $form->get_input_copy_clipboard( $canonical_url ) . '</td>';
			
				$table_rows[] = ( empty( $this->p->options[ 'plugin_shortener' ] ) || 
					$this->p->options[ 'plugin_shortener' ] === 'none' ||
						$sharing_url === $shortlink_url ? '<tr class="hide_in_basic">' : '' ) . 
				$form->get_th_html( _x( 'Shortlink URL', 'option label', 'wpsso' ), 'medium' ) . 
				'<td>' . $form->get_input_copy_clipboard( $shortlink_url ) . '</td>';
			}

			$table_rows[ 'subsection_og_example' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'Facebook / Open Graph Example', 'option label', 'wpsso' ) . '</h4></td>';

			$table_rows[] = '<td colspan="2" style="background-color:#e9eaed;border:1px dotted #e0e0e0;">
				<div class="preview_box_border">
					<div class="preview_box">
						' . $image_preview_html . '
						<div class="preview_txt">
							<div class="preview_title">' . 
								( empty( $head_info[ 'og:title' ] ) ? 'No Title' : $head_info[ 'og:title' ] ) . 
							'</div><!-- .preview_title -->
							<div class="preview_desc">' . 
								( empty( $head_info[ 'og:description' ] ) ? 'No Description' : $head_info[ 'og:description' ] ) . 
							'</div><!-- .preview_desc -->
							<div class="preview_by">' . 
								$_SERVER[ 'SERVER_NAME' ] . 
								( empty( $this->p->options[ 'add_meta_property_article:author' ] ) ||
									empty( $head_info[ 'article:author:name' ] ) ?
										'' : ' | By ' . $head_info[ 'article:author:name' ] ) . 
							'</div><!-- .preview_by -->
						</div><!-- .preview_txt -->
					</div><!-- .preview_box -->
				</div><!-- .preview_box_border -->
			</td>';

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-social-preview' ) . '</td>';

			return $table_rows;
		}

		public function get_rows_head_tab( &$form, &$head_info, &$mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows   = array();
			$script_class = '';

			if ( ! is_array( WpssoWpMeta::$head_meta_tags ) ) {	// Just in case.
				return $table_rows;
			}

			foreach ( WpssoWpMeta::$head_meta_tags as $parts ) {

				if ( count( $parts ) === 1 ) {

					if ( strpos( $parts[0], '<script ' ) === 0 ) {
						$script_class = 'script';
					} elseif ( strpos( $parts[0], '<noscript ' ) === 0 ) {
						$script_class = 'noscript';
					}

					$table_rows[] = '<td colspan="5" class="html ' . 
						$script_class . '"><pre>' . esc_html( $parts[0] ) . '</pre></td>';

					if ( $script_class === 'script' || strpos( $parts[0], '</noscript>' ) === 0 ) {
						$script_class = '';
					}

				} elseif ( isset( $parts[5] ) ) {

					/**
					 * Skip meta tags with reserved values but display empty values.
					 */
					if ( $parts[5] === WPSSO_UNDEF || $parts[5] === (string) WPSSO_UNDEF ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $parts[3] . ' value is ' . WPSSO_UNDEF . ' (skipped)' );
						}

						continue;
					}

					if ( $parts[1] === 'meta' && $parts[2] === 'itemprop' && strpos( $parts[3], '.' ) !== 0 ) {
						$match_name = preg_replace( '/^.*\./', '', $parts[3] );
					} else {
						$match_name = $parts[3];
					}

					$opt_name    = strtolower( 'add_' . $parts[1] . '_' . $parts[2] . '_' . $parts[3] );
					$opt_exists  = isset( $this->p->options[ $opt_name ] ) ? true : false;
					$opt_enabled = empty( $this->p->options[ $opt_name ] ) ? false : true;

					$tr_class = empty( $script_class ) ? '' : ' ' . $script_class;

					/**
					 * If there's no HTML to include in the webpage head section,
					 * then mark the meta tag as disabled and hide it in basic view.
					 */
					if ( empty( $parts[ 0 ] ) ) {
						$tr_class .= ' is_disabled hide_row_in_basic';
					} else {
						$tr_class .= ' is_enabled';
					}

					/**
					 * The meta tag is enabled, but its value is empty (and not 0).
					 */
					if ( $opt_enabled && isset( $parts[ 5 ] ) && empty( $parts[ 5 ] ) && ! is_numeric( $parts[ 5 ] ) ) {
						$tr_class .= ' is_empty';
					}

					/**
					 * The meta tag is "standard" if an option exists to enable / disable
					 * the meta tag, otherwise it's a meta tag meant for internal use.
					 */
					$tr_class .= $opt_exists ? ' is_standard' : ' is_internal';

					$table_rows[] = '<tr class="' . trim( $tr_class ) . '">' .
						'<th class="xshort">' . $parts[1] . '</th>' . 
						'<th class="xshort">' . $parts[2] . '</th>' . 
						'<td class="">' . ( empty( $parts[6] ) ? '' : '<!-- ' . $parts[6] . ' -->' ) . $match_name . '</td>' . 
						'<th class="xshort">' . $parts[4] . '</th>' . 
						'<td class="wide">' . ( strpos( $parts[5], 'http' ) === 0 ? 
							'<a href="' . $parts[5] . '">' . $parts[5] . '</a>' : $parts[5] ) . '</td>';
				}
			}

			return $table_rows;
		}

		public function get_rows_validate_tab( &$form, &$head_info, &$mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$sharing_url = $this->p->util->get_sharing_url( $mod, false );	// $add_page = false

			$sharing_url_encoded = urlencode( $sharing_url );

			$buttons = array(
				'facebook' => array(
					'title' => _x( 'Facebook Open Graph Object Debugger', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Open Graph', 'submit button', 'wpsso' ),
					'url'   => 'https://developers.facebook.com/tools/debug/og/object?q=' . $sharing_url_encoded,
				),
				'linkedin' => array(
					'title' => _x( 'LinkedIn Post Inspector', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Metadata', 'submit button', 'wpsso' ),
					'url'   => 'https://www.linkedin.com/post-inspector/inspect/' . $sharing_url_encoded,
				),
				'google' => array(
					'title' => _x( 'Google Structured Data Testing Tool', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Structured Data', 'submit button', 'wpsso' ),
					'url'   => 'https://search.google.com/structured-data/testing-tool/u/0/#url=' . $sharing_url_encoded,
				),
				'pinterest' => array(
					'title' => _x( 'Pinterest Rich Pins Validator', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Rich Pins', 'submit button', 'wpsso' ),
					'url'   => 'https://developers.pinterest.com/tools/url-debugger/?link=' . $sharing_url_encoded,
				),
				'twitter' => array(
					'title' => _x( 'Twitter Card Validator', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Twitter Card', 'submit button', 'wpsso' ),
					'url'   => 'https://cards-dev.twitter.com/validator',
					'msg'   => $this->p->msgs->get( 'info-meta-validate-twitter' ) . $form->get_input_copy_clipboard( $sharing_url ),
				),
				'w3c' => array(
					'title' => _x( 'W3C Markup Validation', 'option label', 'wpsso' ),
					'label' => _x( 'Validate HTML Markup', 'submit button', 'wpsso' ),
					'url'   => 'https://validator.w3.org/nu/?doc=' . $sharing_url_encoded,
				),
				'amp' => array(
					'title' => $mod[ 'is_post' ] ? _x( 'The AMP Validator', 'option label', 'wpsso' ) : '',
					'label' => $mod[ 'is_post' ] ? _x( 'Validate AMP Markup', 'submit button', 'wpsso' ) : '',
					'url'   => $mod[ 'is_post' ] && function_exists( 'amp_get_permalink' ) ?
						'https://validator.ampproject.org/#url=' . urlencode( amp_get_permalink( $mod[ 'id' ] ) ) : '',
				),
			);

			$table_rows = array();

			foreach ( $buttons as $key => $btn ) {

				if ( ! empty( $btn[ 'title' ] ) ) {	// The amp validator title will be empty for non-post objects.

					$table_rows[ 'validate_' . $key ] = $form->get_th_html( $btn[ 'title' ], 'medium' ) . 
					'<td class="validate">' . ( isset( $btn[ 'msg' ] ) ? $btn[ 'msg' ] :
						$this->p->msgs->get( 'info-meta-validate-' . $key ) ). '</td>' . 
					'<td class="validate">' . $form->get_button( $btn[ 'label' ], 'button-secondary', '', $btn[ 'url' ],
						$newtab = true, ( $btn[ 'url' ] ? false : true ) ) . '</td>';
				}
			}

			return $table_rows;
		}

		/**
		 * Return a specific option from the custom social settings meta with fallback for 
		 * multiple option keys. If $md_key is an array, then get the first non-empty option 
		 * from the options array. This is an easy way to provide a fallback value for the 
		 * first array key. Use 'none' as a key name to skip this fallback behavior.
		 *
		 * Example: get_options_multi( $id, array( 'p_desc', 'og_desc' ) );
		 */
		public function get_options_multi( $mod_id, $md_key = false, $filter_opts = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'mod_id'      => $mod_id, 
					'md_key'      => $md_key, 
					'filter_opts' => $filter_opts, 
				) );
			}

			if ( empty( $mod_id ) ) {
				return null;
			}

			if ( false === $md_key ) {					// Return the whole options array.

				$md_val = $this->get_options( $mod_id, $md_key, $filter_opts );

			} elseif ( true === $md_key ) {					// True is not valid for a custom meta key.

				$md_val = null;

			} else {							// Return the first matching index value.

				if ( is_array( $md_key ) ) {
					$check_md_keys = array_unique( $md_key );	// Prevent duplicate key values.
				} else {
					$check_md_keys = array( $md_key );		// Convert a string to an array.
				}

				foreach ( $check_md_keys as $md_key ) {

					if ( 'none' === $md_key ) {			// Special index keyword - stop here.

						return null;

					} elseif ( empty( $md_key ) ) {			// Skip empty array keys.

						continue;

					} elseif ( is_array( $md_key ) ) {		// An array of arrays is not valid.

						continue;

					} else {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting id ' . $mod_id . ' option ' . $md_key . ' value' );
						}

						if ( ( $md_val = $this->get_options( $mod_id, $md_key, $filter_opts ) ) !== null ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'option ' . $md_key . ' value found (not null)' );
							}

							break;				// Stop after first match.
						}
					}
				}
			}

			if ( $md_val !== null ) {

				if ( $this->p->debug->enabled ) {

					$mod = $this->get_mod( $mod_id );

					$this->p->debug->log( 'custom ' . $mod[ 'name' ] . ' ' . ( false === $md_key ? 'options' : 
						( is_array( $md_key ) ? implode( ', ', $md_key ) : $md_key ) ) . ' = ' . 
						( is_array( $md_val ) ? print_r( $md_val, true ) : '"' . $md_val . '"' ) );
				}
			}

			return $md_val;
		}

		public function get_options( $mod_id, $md_key = false, $filter_opts = true, $def_fallback = false ) {

			$ret_val = array();

			if ( false !== $md_key ) {
				if ( $def_fallback ) {
					$ret_val = $this->get_defaults( $mod_id, $md_key );
				} else {
					$ret_val = null;
				}
			}

			return $this->must_be_extended( __METHOD__, $ret_val );
		}

		public function get_defaults( $mod_id, $md_key = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Maybe initialize the cache.
			 */
			if ( ! isset( $this->defs[ $mod_id ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'initializing the default options cache array' );
				}

				$this->defs[ $mod_id ] = array();
			}

			$md_defs =& $this->defs[ $mod_id ];	// Shortcut variable name.

			if ( ! WpssoOptions::can_cache() || empty( $md_defs[ 'options_filtered' ] ) ) {

				$mod = $this->get_mod( $mod_id );

				$opts =& $this->p->options;		// Shortcut variable name.

				$og_type = $this->p->og->get_mod_og_type( $mod, false, false );	// $ret_og_ns is false, $use_mod_opts is false

				$md_defs = array(
					'options_filtered' => '',
					'options_version'  => '',

					/**
					 * Customize Tab.
					 */
					'og_type'        => $og_type,
					'og_art_section' => isset( $opts[ 'og_art_section' ] ) ? $opts[ 'og_art_section' ] : 'none',
					'og_title'       => '',
					'og_desc'        => '',
					'seo_desc'       => '',
					'tc_desc'        => '',
					'sharing_url'    => '',
					'canonical_url'  => '',
					'schema_desc'    => '',

					/**
					 * Open Graph - Product Information.
					 */
					'product_avail'     => 'none',
					'product_brand'     => '',
					'product_color'     => '',
					'product_condition' => 'none',
					'product_material'  => '',
					'product_sku'       => '',
					'product_ean'       => '',
					'product_gtin8'     => '',
					'product_gtin12'    => '',
					'product_gtin13'    => '',
					'product_gtin14'    => '',
					'product_isbn'      => '',
					'product_price'     => '0.00',
					'product_currency'  => empty( $opts[ 'plugin_def_currency' ] ) ? 'USD' : $opts[ 'plugin_def_currency' ],
					'product_size'      => '',
					'product_gender'    => 'none',

					/**
					 * Open Graph - Priority Image.
					 */
					'og_img_max'    => isset( $opts[ 'og_img_max' ] ) ? (int) $opts[ 'og_img_max' ] : 1,	// Cast as integer.
					'og_img_width'  => isset( $opts[ 'og_img_width' ] ) ? $opts[ 'og_img_width' ] : '',
					'og_img_height' => isset( $opts[ 'og_img_height' ] ) ? $opts[ 'og_img_height' ] : '',
					'og_img_crop'   => empty( $opts[ 'og_img_crop' ] ) ? 0 : 1,
					'og_img_crop_x' => empty( $opts[ 'og_img_crop_x' ] ) ? 'center' : $opts[ 'og_img_crop_x' ],
					'og_img_crop_y' => empty( $opts[ 'og_img_crop_y' ] ) ? 'center' : $opts[ 'og_img_crop_y' ],
					'og_img_id'     => '',
					'og_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'og_img_url'    => '',

					/**
					 * Open Graph - Priority Video.
					 */
					'og_vid_prev_img' => empty( $opts[ 'og_vid_prev_img' ] ) ? 0 : 1,
					'og_vid_max'      => isset( $opts[ 'og_vid_max' ] ) ? (int) $opts[ 'og_vid_max' ] : 1,	// Cast as integer.
					'og_vid_width'    => '',	// Custom value for first video.
					'og_vid_height'   => '',	// Custom value for first video.
					'og_vid_embed'    => '',
					'og_vid_url'      => '',
					'og_vid_title'    => '',	// Custom value for first video.
					'og_vid_desc'     => '',	// Custom value for first video.

					/**
					 * Twitter Card.
					 */
					'tc_lrg_img_width'  => isset( $opts[ 'tc_lrg_img_width' ] ) ? $opts[ 'tc_lrg_img_width' ] : '',
					'tc_lrg_img_height' => isset( $opts[ 'tc_lrg_img_height' ] ) ? $opts[ 'tc_lrg_img_height' ] : '',
					'tc_lrg_img_crop'   => empty( $opts[ 'tc_lrg_img_crop' ] ) ? 0 : 1,
					'tc_lrg_img_crop_x' => empty( $opts[ 'tc_lrg_img_crop_x' ] ) ? 'center' : $opts[ 'tc_lrg_img_crop_x' ],
					'tc_lrg_img_crop_y' => empty( $opts[ 'tc_lrg_img_crop_y' ] ) ? 'center' : $opts[ 'tc_lrg_img_crop_y' ],
					'tc_lrg_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'tc_lrg_img_url'    => '',
					'tc_sum_img_width'  => isset( $opts[ 'tc_sum_img_width' ] ) ? $opts[ 'tc_sum_img_width' ] : '',
					'tc_sum_img_height' => isset( $opts[ 'tc_sum_img_height' ] ) ? $opts[ 'tc_sum_img_height' ] : '',
					'tc_sum_img_crop'   => empty( $opts[ 'tc_sum_img_crop' ] ) ? 0 : 1,
					'tc_sum_img_crop_x' => empty( $opts[ 'tc_sum_img_crop_x' ] ) ? 'center' : $opts[ 'tc_sum_img_crop_x' ],
					'tc_sum_img_crop_y' => empty( $opts[ 'tc_sum_img_crop_y' ] ) ? 'center' : $opts[ 'tc_sum_img_crop_y' ],
					'tc_sum_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'tc_sum_img_url'    => '',

					/**
					 * Structured Data / Schema Markup / Pinterest.
					 */
					'schema_img_max'    => isset( $opts[ 'schema_img_max' ] ) ? (int) $opts[ 'schema_img_max' ] : 1,	// Cast as integer.
					'schema_img_width'  => isset( $opts[ 'schema_img_width' ] ) ? $opts[ 'schema_img_width' ] : '',
					'schema_img_height' => isset( $opts[ 'schema_img_height' ] ) ? $opts[ 'schema_img_height' ] : '',
					'schema_img_crop'   => empty( $opts[ 'schema_img_crop' ] ) ? 0 : 1,
					'schema_img_crop_x' => empty( $opts[ 'schema_img_crop_x' ] ) ? 'center' : $opts[ 'schema_img_crop_x' ],
					'schema_img_crop_y' => empty( $opts[ 'schema_img_crop_y' ] ) ? 'center' : $opts[ 'schema_img_crop_y' ],
					'schema_img_id'     => '',
					'schema_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'schema_img_url'    => '',

					/**
					 * Gravity View (Side Metabox).
					 */
					'gv_id_title' => 0,	// Title Field ID
					'gv_id_desc'  => 0,	// Description Field ID
					'gv_id_img'   => 0,	// Post Image Field ID
				);

				if ( WpssoOptions::can_cache() ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting options_filtered to true' );
					}

					$md_defs[ 'options_filtered' ] = true;	// Set before calling filter to prevent recursion.

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'options_filtered value unchanged' );
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'applying get_md_defaults filters' );
				}

				$md_defs = apply_filters( $this->p->lca . '_get_md_defaults', $md_defs, $mod );

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'get_md_defaults filter skipped' );
			}

			if ( false !== $md_key ) {

				if ( isset( $md_defs[ $md_key ] ) ) {
					return $md_defs[ $md_key ];
				} else {
					return null;
				}

			} else {
				return $md_defs;
			}
		}

		public function save_options( $mod_id, $rel_id = false ) {
			return $this->must_be_extended( __METHOD__ );
		}

		public function user_can_edit( $mod_id, $rel_id = false ) {
			return $this->must_be_extended( __METHOD__, false );	// return false by default
		}

		public function clear_cache( $mod_id, $rel_id = false ) {
			return $this->must_be_extended( __METHOD__ );
		}

		protected function clear_mod_cache_types( array $mod, array $cache_types = array(), $sharing_url = false ) {

			if ( false === $sharing_url ) {
				$sharing_url = $this->p->util->get_sharing_url( $mod );
			}

			$mod_salt = SucomUtil::get_mod_salt( $mod, $sharing_url );

			$cache_types[ 'transient' ][] = array(
				'id'   => $this->p->lca . '_h_' . md5( 'WpssoHead::get_head_array(' . $mod_salt . ')' ),
				'pre'  => $this->p->lca . '_h_',
				'salt' => 'WpssoHead::get_head_array(' . $mod_salt . ')',
			);

			$cache_types[ 'transient' ][] = array(
				'id'   => $this->p->lca . '_j_' . md5( 'WpssoSchema::get_mod_cache_data(' . $mod_salt . ')' ),
				'pre'  => $this->p->lca . '_j_',
				'salt' => 'WpssoSchema::get_mod_cache_data(' . $mod_salt . ')',
			);

			$cache_types[ 'wp_cache' ][] = array(
				'id'   => $this->p->lca . '_c_' . md5( 'WpssoPage::get_the_content(' . $mod_salt . ')' ),
				'pre'  => $this->p->lca . '_c_',
				'salt' => 'WpssoPage::get_the_content(' . $mod_salt . ')',
			);

			$deleted_count = 0;
			$deleted_ids = array();

			foreach ( $cache_types as $type_name => $type_keys ) {

				$filter_name = $this->p->lca . '_' . $mod[ 'name' ] . '_cache_' . $type_name . '_keys';
				$type_keys = (array) apply_filters( $filter_name, $type_keys, $mod, $sharing_url, $mod_salt );

				foreach ( $type_keys as $mixed ) {

					if ( is_array( $mixed ) && isset( $mixed[ 'id' ] ) ) {

						$cache_id = $mixed[ 'id' ];

						$cache_key = '';
						$cache_key .= isset( $mixed[ 'pre' ] ) ? $mixed[ 'pre' ] : '';
						$cache_key .= isset( $mixed[ 'salt' ] ) ? $mixed[ 'salt' ] : '';
						$cache_key = trim( $cache_key );

						if ( empty( $cache_key ) ) {
							$cache_key = $cache_id;
						}

					} else {
						$cache_id = $cache_key = $mixed;
					}

					if ( isset( $deleted_ids[ $type_name ][ $cache_id ] ) ) {	// skip duplicate cache ids
						continue;
					}

					switch ( $type_name ) {

						case 'transient':

							$ret = delete_transient( $cache_id );

							break;

						case 'wp_cache':

							$ret = wp_cache_delete( $cache_id );

							break;

						default:

							$ret = false;

							break;
					}

					if ( $ret ) {
						$deleted_count++;
					}

					$deleted_ids[ $type_name ][ $cache_key ] = $ret;
				}
			}

			return $deleted_count;
		}

		public function delete_options( $mod_id, $rel_id = false ) {
			return $this->must_be_extended( __METHOD__, $mod_id );
		}

		protected function not_implemented( $method, $ret = true ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $method . ' not implemented in this version', get_class( $this ) );	// log the extended class name
			}
			return $ret;
		}

		protected function must_be_extended( $method, $ret = true ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $method . ' must be extended', get_class( $this ) );	// log the extended class name
			}
			return $ret;
		}

		protected function verify_submit_nonce() {
			if ( empty( $_POST ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'empty POST for submit' );
				}
				return false;
			} elseif ( empty( $_POST[ WPSSO_NONCE_NAME ] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'submit POST missing nonce token' );
				}
				return false;
			} elseif ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'submit nonce token validation failed' );
				}
				if ( is_admin() ) {
					$this->p->notice->err( __( 'Nonce token validation failed for the submitted form (update ignored).',
						'wpsso' ) );
				}
				return false;
			} else {
				return true;
			}
		}

		protected function get_submit_opts( $mod_id ) {

			$mod     = $this->get_mod( $mod_id );
			$md_defs = $this->get_defaults( $mod[ 'id' ] );
			$md_prev = $this->get_options( $mod[ 'id' ] );

			/**
			 * Remove plugin version strings.
			 */
			$md_unset_keys = array( 'options_filtered', 'options_version' );

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {
				if ( isset( $info[ 'opt_version' ] ) ) {
					$md_unset_keys[] = 'plugin_' . $ext . '_opt_version';
				}
			}

			foreach ( $md_unset_keys as $md_key ) {
				unset( $md_defs[ $md_key ], $md_prev[ $md_key ] );
			}

			/**
			 * Merge and sanitize the new options.
			 */
			$md_opts = empty( $_POST[ WPSSO_META_NAME ] ) ? array() : $_POST[ WPSSO_META_NAME ];
			$md_opts = SucomUtil::restore_checkboxes( $md_opts );
			$md_opts = array_merge( $md_prev, $md_opts );	// Update the previous options array.
			$md_opts = $this->p->opt->sanitize( $md_opts, $md_defs, $network = false, $mod );

			/**
			 * Check image size options (id, prefix, width, height, crop, etc.).
			 */
			foreach ( array( 'og', 'tc_sum', 'tc_lrg', 'schema' ) as $md_pre ) {

				/**
				 * If there's no image ID, then remove the image ID library prefix.
				 * If an image ID is being used, then remove the image url (only one can be defined).
				 */
				if ( empty( $md_opts[ $md_pre . '_img_id' ] ) ) {
					unset( $md_opts[ $md_pre . '_img_id_pre' ] );
				} else {
					unset( $md_opts[ $md_pre . '_img_url' ] );
				}

				$force_regen = false;

				foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $md_suffix ) {

					/**
					 * If the option value is the same as the default value, then unset it.
					 */
					if ( isset( $md_opts[ $md_pre . '_img_' . $md_suffix ] ) &&
						isset( $md_defs[ $md_pre . '_img_' . $md_suffix ] ) &&
						$md_opts[ $md_pre . '_img_' . $md_suffix ] === $md_defs[ $md_pre . '_img_' . $md_suffix ] ) {

						unset( $md_opts[ $md_pre . '_img_' . $md_suffix ] );
					}

					$check_current = isset( $md_opts[ $md_pre . '_img_' . $md_suffix ] ) ?
						$md_opts[ $md_pre . '_img_' . $md_suffix ] : '';

					$check_previous = isset( $md_prev[ $md_pre . '_img_' . $md_suffix ] ) ?
						$md_prev[ $md_pre . '_img_' . $md_suffix ] : '';

					if ( $check_current !== $check_previous ) {
						$force_regen = true;
					}
				}

				if ( false !== $force_regen ) {
					$this->p->util->set_force_regen( $mod, $md_pre );
				}
			}

			/**
			 * Remove "use plugin settings", or "same as default" option values, or empty strings.
			 */
			foreach ( $md_opts as $md_key => $md_val ) {

				/**
				 * Use strict comparison to manage conversion (don't allow string to integer conversion, for example).
				 */
				if ( $md_val === '' || $md_val === WPSSO_UNDEF || $md_val === (string) WPSSO_UNDEF || 
					( isset( $md_defs[ $md_key ] ) && ( $md_val === $md_defs[ $md_key ] || $md_val === (string) $md_defs[ $md_key ] ) ) ) {

					unset( $md_opts[ $md_key ] );
				}
			}

			/**
			 * Re-number multi options (example: schema type url, recipe ingredient, recipe instruction, etc.).
			 */
			foreach ( $this->p->cf[ 'opt' ][ 'cf_md_multi' ] as $md_multi => $is_multi ) {

				if ( ! $is_multi ) {	// Just in case.
					continue;
				}

				$md_seq = array();	// Start with a fresh array.

				$md_orig = SucomUtil::preg_grep_keys( '/^' . $md_multi . '_[0-9]+$/', $md_opts );

				foreach ( $md_orig as $md_key => $md_val ) {

					unset( $md_opts[ $md_key ] );

					if ( $md_val !== '' ) {
						$md_seq[] = $md_val;
					}
				}

				foreach ( $md_seq as $num => $md_val ) {	// Start at 0.
					$md_opts[ $md_multi . '_' . $num ] = $md_val;
				}
			}

			/**
			 * Mark the new options as current.
			 */
			if ( ! empty( $md_opts ) ) {

				$md_opts[ 'options_version' ] = $this->p->cf[ 'opt' ][ 'version' ];

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {
					if ( isset( $info[ 'opt_version' ] ) ) {
						$md_opts[ 'plugin_' . $ext . '_opt_version' ] = $info[ 'opt_version' ];
					}
				}
			}

			return $md_opts;
		}

		/**
		 * Return sortable column keys and their query sort info.
		 */
		public static function get_sortable_columns( $col_key = false ) { 

			static $sort_cols = null;

			if ( null === $sort_cols ) {

				$wpsso =& Wpsso::get_instance();

				$sort_cols = (array) apply_filters( $wpsso->lca . '_get_sortable_columns', $wpsso->cf[ 'edit' ][ 'columns' ] );
			}

			if ( false !== $col_key ) {
				if ( isset( $sort_cols[ $col_key ] ) ) {
					return $sort_cols[ $col_key ];
				} else {
					return null;
				}
			} else {
				return $sort_cols;
			}
		}

		public static function get_column_meta_keys() { 

			$meta_keys = array();
			$sort_cols = self::get_sortable_columns();

			foreach ( $sort_cols as $col_key => $col_info ) {
				if ( ! empty( $col_info[ 'meta_key' ] ) ) {
					$meta_keys[ $col_key ] = $col_info[ 'meta_key' ];
				}
			}

			return $meta_keys;
		}

		public static function get_column_headers() { 

			$headers   = array();
			$sort_cols = self::get_sortable_columns();

			foreach ( $sort_cols as $col_key => $col_info ) {
				if ( ! empty( $col_info[ 'header' ] ) ) {
					$headers[ $col_key ] = _x( $col_info[ 'header' ], 'column header', 'wpsso' );
				}
			}

			return $headers;
		}

		public function get_column_wp_cache( array $mod, $column_name ) {

			$value = '';

			if ( ! empty( $mod[ 'id' ] ) && strpos( $column_name, $this->p->lca . '_' ) === 0 ) {	// Just in case.

				$col_key = str_replace( $this->p->lca . '_', '', $column_name );

				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {

					if ( isset( $col_info[ 'meta_key' ] ) ) {	// Just in case.

						$meta_cache = wp_cache_get( $mod[ 'id' ], $mod[ 'name' ] . '_meta' );

						if ( ! $meta_cache ) {
							$meta_cache = update_meta_cache( $mod[ 'name' ], array( $mod[ 'id' ] ) );
							$meta_cache = $meta_cache[ $mod[ 'id' ] ];
						}

						if ( isset( $meta_cache[ $col_info[ 'meta_key' ] ] ) ) {
							$value = (string) maybe_unserialize( $meta_cache[ $col_info[ 'meta_key' ] ][ 0 ] );
						}
					}
				}
			}

			return $value;
		}

		public function get_column_content( $value, $column_name, $id ) {

			return $this->must_be_extended( __METHOD__, $value );
		}

		public function get_meta_cache_value( $id, $meta_key, $none = '' ) {

			return $this->must_be_extended( __METHOD__, $none );
		}

		public function update_sortable_meta( $obj_id, $col_key, $content ) { 

			return $this->must_be_extended( __METHOD__ );
		}

		public function add_sortable_columns( $columns ) { 

			foreach ( self::get_sortable_columns() as $col_key => $col_info ) {
				if ( ! empty( $col_info[ 'orderby' ] ) ) {
					$columns[ $this->p->lca . '_' . $col_key ] = $this->p->lca . '_' . $col_key;
				}
			}

			return $columns;
		}

		public function set_column_orderby( $query ) { 

			$col_name = $query->get( 'orderby' );

			if ( is_string( $col_name ) && strpos( $col_name, $this->p->lca . '_' ) === 0 ) {

				$col_key = str_replace( $this->p->lca . '_', '', $col_name );

				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {

					foreach ( array( 'meta_key', 'orderby' ) as $set_name ) {

						if ( ! empty( $col_info[ $set_name ] ) ) {
							$query->set( $set_name, $col_info[ $set_name ] );
						}
					}
				}
			}
		}

		public function add_mod_column_headings( $columns, $mod_name = '' ) { 

			if ( ! empty( $mod_name ) ) {

				foreach ( self::get_column_headers() as $col_key => $col_header ) {

					if ( ! empty( $this->p->options[ 'plugin_' . $col_key . '_col_' . $mod_name] ) ) {

						$columns[ $this->p->lca . '_' . $col_key ] = $col_header;

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'adding ' . $this->p->lca . '_' . $col_key . ' column' );
						}
					}
				}
			}

			return $columns;
		}

		public function get_og_img_column_html( $head_info, $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			$media_html  = false;
			$force_regen = $this->p->util->is_force_regen( $mod, $md_pre );	// false by default

			if ( ! empty( $head_info[ $mt_pre . ':image:id' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting thumbnail for image id ' . $head_info[ $mt_pre . ':image:id' ] );
				}

				$mt_single_image = array();

				$this->p->media->add_mt_single_image_src( $mt_single_image, $head_info[ $mt_pre . ':image:id' ], 'thumbnail', false, $force_regen );

				if ( ! empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {	// Just in case.
					$head_info =& $mt_single_image;
				}
			}

			$media_url = SucomUtil::get_mt_media_url( $head_info );
			
			if ( $force_regen ) {
				$media_url = add_query_arg( 'force_regen', time(), $media_url );
			}

			if ( ! empty( $media_url ) ) {
				$media_html = '<div class="preview_img" style="background-image:url(' . $media_url . ');"></div>';
			}

			return $media_html;
		}

		public function get_pid_thumb_img_html( $pid, $mod, $md_pre = 'og' ) {

			if ( empty( $pid ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: image id is empty' );
				}
				return '';
			}

			$media_html  = false;
			$force_regen = $this->p->util->is_force_regen( $mod, $md_pre );	// False by default.

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'getting thumbnail for image id ' . $pid );
			}

			$mt_single_image = array();

			$this->p->media->add_mt_single_image_src( $mt_single_image, $pid, 'thumbnail', false, $force_regen );

			$media_url = SucomUtil::get_mt_media_url( $mt_single_image );

			if ( $force_regen ) {
				$media_url = add_query_arg( 'force_regen', time(), $media_url );
			}

			if ( ! empty( $media_url ) ) {
				$media_html = '<img src="' . $media_url . '">';
			}

			return $media_html;
		}

		public function get_og_images( $num, $size_name, $mod_id, $check_dupes = true, $force_regen = false, $md_pre = 'og' ) {
			return $this->must_be_extended( __METHOD__, array() );
		}

		public function get_md_images( $num, $size_name, array $mod, $check_dupes = true, $force_regen = false, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'num'         => $num,
					'size_name'   => $size_name,
					'mod'         => $mod,
					'check_dupes' => $check_dupes,
					'force_regen' => $force_regen,
					'md_pre'      => $md_pre,
					'mt_pre'      => $mt_pre,
				), get_class( $this ) );
			}

			$mt_ret = array();

			if ( empty( $mod[ 'id' ] ) ) {
				return $mt_ret;
			}

			/**
			 * Unless $md_pre is 'none' allways fallback to the 'og' custom meta.
			 */
			foreach( array_unique( array( $md_pre, 'og' ) ) as $opt_pre ) {

				if ( $opt_pre === 'none' ) {	// special index keyword
					break;
				} elseif ( empty( $opt_pre ) ) {	// skip empty md_pre values
					continue;
				}

				/**
				 * Get an empty image meta tag array.
				 */
				$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

				/**
				 * Get the image id, library prefix, and/or url values.
				 */
				$pid = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_id' );
				$pre = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_id_pre' );	// Default library prefix.
				$url = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url' );

				if ( $pid > 0 ) {

					$pid = $pre === 'ngg' ? 'ngg-' . $pid : $pid;

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'using custom ' . $opt_pre . ' image id = "' . $pid . '"',
							get_class( $this ) );	// log extended class name
					}

					$this->p->media->add_mt_single_image_src( $mt_single_image, $pid, $size_name, $check_dupes, $force_regen, $mt_pre );
				}

				if ( empty( $mt_single_image[ $mt_pre . ':image:url' ] ) && ! empty( $url ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'using custom ' . $opt_pre . ' image url = "' . $url . '"',
							get_class( $this ) );	// log extended class name
					}

					$img_width  = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url:width' );
					$img_height = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url:height' );

					$mt_single_image[ $mt_pre . ':image:url' ]    = $url;
					$mt_single_image[ $mt_pre . ':image:width' ]  = $img_width > 0 ? $img_width : WPSSO_UNDEF;
					$mt_single_image[ $mt_pre . ':image:height' ] = $img_height > 0 ? $img_height : WPSSO_UNDEF;
				}

				if ( ! empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {
					if ( $this->p->util->push_max( $mt_ret, $mt_single_image, $num ) ) {
						return $mt_ret;
					}
				}
			}

			foreach ( apply_filters( $this->p->lca . '_' . $mod[ 'name' ] . '_image_ids', array(), $size_name, $mod[ 'id' ], $mod ) as $pid ) {

				if ( $pid > 0 ) {	// Quick sanity check.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'adding image pid: ' . $pid );
					}

					$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

					$this->p->media->add_mt_single_image_src( $mt_single_image, $pid, $size_name, $check_dupes, $force_regen, $mt_pre );

					if ( ! empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {
						if ( $this->p->util->push_max( $mt_ret, $mt_single_image, $num ) ) {
							return $mt_ret;
						}
					}
				}
			}

			foreach ( apply_filters( $this->p->lca . '_' . $mod[ 'name' ] . '_image_urls', array(), $size_name, $mod[ 'id' ], $mod ) as $url ) {

				if ( false !== strpos( $url, '://' ) ) {	// Quick sanity check.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'adding image url: ' . $url );
					}

					$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

					$mt_single_image[ $mt_pre . ':image:url' ] = $url;

					/**
					 * Add correct image sizes for the image URL using getimagesize().
					 */
					$this->p->util->add_image_url_size( $mt_single_image, $mt_pre . ':image' );

					if ( ! empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {
						if ( $this->p->util->push_max( $mt_ret, $mt_single_image, $num ) ) {
							return $mt_ret;
						}
					}
				}
			}

			return $mt_ret;
		}

		public function get_og_videos( $num = 0, $mod_id, $check_dupes = false, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'num'         => $num,
					'mod_id'      => $mod_id,
					'check_dupes' => $check_dupes,
					'md_pre'      => $md_pre,
					'mt_pre'      => $mt_pre,
				), get_class( $this ) );
			}

			$mod       = $this->get_mod( $mod_id );	// required for get_content_videos()
			$og_ret    = array();
			$og_videos = array();

			if ( empty( $mod_id ) ) {
				return $og_ret;
			}

			foreach( array_unique( array( $md_pre, 'og' ) ) as $opt_pre ) {

				$embed_html = $this->get_options( $mod_id, $opt_pre . '_vid_embed' );
				$video_url  = $this->get_options( $mod_id, $opt_pre . '_vid_url' );

				/**
				 * Retrieve one or more videos from the embed HTML code . 
				 */
				if ( ! empty( $embed_html ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'fetching video(s) from custom ' . $opt_pre . ' embed code',
							get_class( $this ) );	// log extended class name
					}

					$og_ret = array_merge( $og_ret, $this->p->media->get_content_videos( $num, $mod, $check_dupes, $embed_html ) );
				}

				if ( ! empty( $video_url ) && ( $check_dupes == false || $this->p->util->is_uniq_url( $video_url ) ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'fetching video from custom ' . $opt_pre . ' url ' . $video_url, get_class( $this ) );
					}

					$args = array(
						'url'      => $video_url,
						'width'    => WPSSO_UNDEF,
						'height'   => WPSSO_UNDEF,
						'type'     => '',
						'prev_url' => '',
						'post_id'  => null,
						'api'      => '',
					);

					$og_videos = $this->p->media->get_video_details( $args, $check_dupes, true );

					if ( $this->p->util->push_max( $og_ret, $og_videos, $num ) )  {
						return $og_ret;
					}
				}
			}

			return $og_ret;
		}

		public function get_og_type_reviews( $mod_id, $og_type = 'product', $rating_meta = 'rating' ) {
			return $this->must_be_extended( __METHOD__, array() );	// return an empty array
		}

		public function get_og_review_mt( $comment_obj, $og_type = 'product', $rating_meta = 'rating' ) {
			return $this->must_be_extended( __METHOD__, array() );	// return an empty array
		}

		/**
		 * $wp_meta can be a post / term / user meta array or empty / false.
		 */
		protected function get_custom_fields( array $md_opts, $wp_meta = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( empty( $wp_meta ) || ! is_array( $wp_meta ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'wp_meta provided is empty or not an array' );
				}
				return $md_opts;
			}

			$charset = get_bloginfo( 'charset' );	// Required for html_entity_decode().

			/* Example config:
			 *
			 *	'cf_md_key' => array(
			 *		'plugin_cf_addl_type_urls'      => 'schema_addl_type_url',
			 *		'plugin_cf_howto_steps'         => 'schema_howto_step',
			 *		'plugin_cf_howto_supplies'      => 'schema_howto_supply',
			 *		'plugin_cf_howto_tools'         => 'schema_howto_tool',
			 *		'plugin_cf_img_url'             => 'og_img_url',
			 *		'plugin_cf_product_avail'       => 'product_avail',
			 *		'plugin_cf_product_brand'       => 'product_brand',
			 *		'plugin_cf_product_color'       => 'product_color',
			 *		'plugin_cf_product_condition'   => 'product_condition',
			 *		'plugin_cf_product_material'    => 'product_material',
			 *		'plugin_cf_product_sku'         => 'product_sku',
			 *		'plugin_cf_product_price'       => 'product_price',
			 *		'plugin_cf_product_currency'    => 'product_currency',
			 *		'plugin_cf_product_size'        => 'product_size',
			 *		'plugin_cf_product_gender'      => 'product_gender',
			 *		'plugin_cf_recipe_ingredients'  => 'schema_recipe_ingredient',
			 *		'plugin_cf_recipe_instructions' => 'schema_recipe_instruction',
			 *		'plugin_cf_sameas_urls'         => 'schema_sameas_url',
			 *		'plugin_cf_vid_embed'           => 'og_vid_embed',
			 *		'plugin_cf_vid_url'             => 'og_vid_url',
			 *	),
			 */
			$cf_md_keys = (array) apply_filters( $this->p->lca . '_cf_md_keys', $this->p->cf[ 'opt' ][ 'cf_md_key' ] );

			foreach ( $cf_md_keys as $cf_key => $md_key ) {

				if ( empty( $md_key ) ) {	// Custom fields can be disabled by filters.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'custom field ' . $cf_key . ' index is disabled' );
					}

					continue;

				} elseif ( empty( $this->p->options[ $cf_key ] ) ) {	// Check that a custom field meta key has been defined.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'custom field ' . $cf_key . ' option is empty' );
					}

					continue;
				}

				$meta_key = $this->p->options[ $cf_key ];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'custom field ' . $cf_key . ' option has meta key ' . $meta_key );
				}

				if ( ! isset( $wp_meta[ $meta_key ][ 0 ] ) ) {	// If the array element is not set, then skip it.
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $meta_key . ' meta key element 0 not found in wp_meta' );
					}
					continue;
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $meta_key . ' meta key found for ' . $md_key . ' option' );
				}

				$mixed = maybe_unserialize( $wp_meta[ $meta_key ][ 0 ] );	// Could be a string or an array.

				$values = array();

				/**
				 * Decode the string or each array element.
				 */
				if ( is_array( $mixed ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $meta_key . ' is array of ' . count( $mixed ) . ' values (decoding each value)' );
					}
					foreach ( $mixed as $val ) {
						if ( is_array( $val ) ) {
							$val = SucomUtil::array_implode( $val );
						}
						$values[] = trim( html_entity_decode( SucomUtil::decode_utf8( $val ), ENT_QUOTES, $charset ) );
					}
				} else {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'decoding ' . $meta_key . ' as string of ' . strlen( $mixed ) . ' chars' );
					}
					$values[] = trim( html_entity_decode( SucomUtil::decode_utf8( $mixed ), ENT_QUOTES, $charset ) );
				}

				/**
				 * Check if the value should be split into numeric option increments.
				 */
				if ( empty( $this->p->cf[ 'opt' ][ 'cf_md_multi' ][ $md_key ] ) ) {

					$is_multi = false;

				} else {

					if ( ! is_array( $mixed ) ) {

						$values = array_map( 'trim', explode( PHP_EOL, reset( $values ) ) );	// Explode first element into an array.

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'exploded ' . $meta_key . ' into array of ' . count( $values ) . ' elements' );
						}
					}

					$is_multi = true;	// Increment the option name.
				}

				/**
				 * Increment the option name starting at 0.
				 */
				if ( $is_multi ) {

					/**
					 * Remove any old values from the options array.
					 */
					$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_key . '_[0-9]+$/', $md_opts, true );	// $invert is true.

					foreach ( $values as $num => $val ) {
						$md_opts[ $md_key . '_' . $num ] = $val;
						$md_opts[ $md_key . '_' . $num . ':is' ] = 'disabled';
					}

				} else {

					$md_opts[ $md_key ] = reset( $values );	// Get first element of $values array.

					$md_opts[ $md_key . ':is' ] = 'disabled';
				}
			}

			return $md_opts;
		}

		public function get_sharing_shortlink( $shortlink = false, $post_id = 0, $context = 'post', $allow_slugs = true ) {
			return $this->must_be_extended( __METHOD__, '' );
		}

		public function maybe_restore_shortlink( $shortlink = false, $post_id = 0, $context = 'post', $allow_slugs = true ) {
			return $this->must_be_extended( __METHOD__, '' );
		}

		public static function get_public_post_ids() {
			return $this->must_be_extended( __METHOD__, array() );
		}

		public static function get_public_term_ids( $tax_name = null ) {
			return $this->must_be_extended( __METHOD__, array() );
		}

		public static function get_public_user_ids() {
			return $this->must_be_extended( __METHOD__, array() );
		}
	}
}
