<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

/**
 * WordPress metadata class, extended by the WpssoPost, WpssoTerm, and WpssoUser classes.
 */
if ( ! class_exists( 'WpssoWpMeta' ) ) {

	class WpssoWpMeta {

		protected $p;
		protected $form;

		/**
		 * The WpssoPost, WpssoTerm, and WpssoUser->load_meta_page() methods define the $head_tags and $head_info static
		 * variables.
		 */
		protected static $head_tags         = false;	// Must be false by default.
		protected static $head_info         = array();
		protected static $last_column_id    = null;	// Cache id of the last column request in list table.
		protected static $last_column_array = array();	// Array of column values for last column requested.
		protected static $cache_short_url   = null;
		protected static $cache_shortlinks  = array();

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
					'rp_img_width'  => '',
					'rp_img_height' => '',
					'rp_img_crop'   => '',
					'rp_img_crop_x' => '',
					'rp_img_crop_y' => '',
					'rp_img_url'    => 'schema_img_url',
				),
				520 => array(
					'p_img_id'     => 'schema_img_id',
					'p_img_id_pre' => 'schema_img_id_pre',
					'p_img_width'  => '',
					'p_img_height' => '',
					'p_img_crop'   => '',
					'p_img_crop_x' => '',
					'p_img_crop_y' => '',
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
				628 => array(
					'product_gender' => 'product_target_gender',
				),
				649 => array(
					'product_ean' => 'product_gtin13',
				),
				659 => array(
					'thumb_img_width'           => '',
					'thumb_img_height'          => '',
					'thumb_img_crop'            => '',
					'schema_article_img_width'  => '',
					'schema_article_img_height' => '',
					'schema_article_img_crop'   => '',
					'schema_img_width'          => '',
					'schema_img_height'         => '',
					'schema_img_crop'           => '',
					'og_img_width'              => '',
					'og_img_height'             => '',
					'og_img_crop'               => '',
					'tc_sum_img_width'          => '',
					'tc_sum_img_height'         => '',
					'tc_sum_img_crop'           => '',
					'tc_lrg_img_width'          => '',
					'tc_lrg_img_height'         => '',
					'tc_lrg_img_crop'           => '',
				),
				660 => array(
					'thumb_img_crop_x'          => '',
					'thumb_img_crop_y'          => '',
					'schema_article_img_crop_x' => '',
					'schema_article_img_crop_y' => '',
					'schema_img_crop_x'         => '',
					'schema_img_crop_y'         => '',
					'og_img_crop_x'             => '',
					'og_img_crop_y'             => '',
					'tc_sum_img_crop_x'         => '',
					'tc_sum_img_crop_y'         => '',
					'tc_lrg_img_crop_x'         => '',
					'tc_lrg_img_crop_y'         => '',
				),
				692 => array(
					'product_mpn' => 'product_mfr_part_no',
					'product_sku' => 'product_retailer_part_no',
				),
				696 => array(
					'og_art_section' => 'article_section',
				),
				701 => array(
					'article_topic' => 'article_section',
				),
			),
		);

		public static $mod_defaults = array(

			/**
			 * Common elements.
			 */
			'id'        => 0,		// Post, term, or user ID.
			'name'      => false,		// Module name ('post', 'term', or 'user').
			'obj'       => false,		// Module object.
			'is_public' => true,		// Module object is public.

			/**
			 * Post elements.
			 */
			'use_post'             => false,
			'is_post'              => false,	// Is post module.
			'is_post_type_archive' => false,	// Post is an archive.
			'is_home'              => false,	// Home page (index or static)
			'is_home_page'         => false,	// Static front page.
			'is_home_posts'        => false,	// Static posts page or latest posts.
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

			return $this->must_be_extended( __METHOD__ );
		}

		/**
		 * Add WordPress action and filters hooks.
		 */
		public function add_wp_hooks() {

			return $this->must_be_extended( __METHOD__ );
		}

		/**
		 * Get the $mod object for a post, term, or user ID.
		 */
		public function get_mod( $mod_id ) {

			return $this->must_be_extended( __METHOD__, self::$mod_defaults );
		}

		/**
		 * Option handling methods:
		 *
		 *	get_defaults()
		 *	get_options()
		 *	save_options()
		 *	delete_options()
		 */
		public function get_defaults( $mod_id, $md_key = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'mod_id'      => $mod_id, 
					'md_key'       => $md_key, 
				) );
			}

			static $local_cache = array();

			$class = get_called_class();

			if ( __CLASS__ === $class ) {	// Just in case.
				return $this->must_be_extended( __METHOD__, array() );
			}

			$cache_id = 'id:' . $mod_id;

			/**
			 * Maybe initialize the cache.
			 */
			if ( ! isset( $local_cache[ $cache_id ] ) ) {
				$local_cache[ $cache_id ] = array();
			}

			$md_defs =& $local_cache[ $cache_id ];	// Shortcut variable name.

			if ( ! WpssoOptions::can_cache() || empty( $md_defs[ 'options_filtered' ] ) ) {

				$mod = $this->get_mod( $mod_id );

				$opts =& $this->p->options;		// Shortcut variable name.

				$def_og_type = $this->p->og->get_mod_og_type( $mod, $get_ns = false, $use_mod_opts = false );

				$def_schema_type = $this->p->schema->get_mod_schema_type( $mod, $get_id = true, $use_mod_opts = false );

				$md_defs = array(
					'options_filtered'  => '',
					'options_version'   => '',
					'attach_img_crop_x' => 'none',
					'attach_img_crop_y' => 'none',

					/**
					 * Customize Tab.
					 */
					'og_type'          => $def_og_type,
					'og_title'         => '',
					'og_desc'          => '',
					'seo_desc'         => '',
					'tc_desc'          => '',
					'sharing_url'      => '',
					'canonical_url'    => '',
					'article_section'  => isset( $opts[ 'og_def_article_section' ] ) ? $opts[ 'og_def_article_section' ] : 'none',
					'product_category' => isset( $opts[ 'og_def_product_category' ] ) ? $opts[ 'og_def_product_category' ] : 'none',
					'schema_type'      => $def_schema_type,

					/**
					 * Open Graph - Product Information.
					 */
					'product_avail'            => 'none',
					'product_brand'            => '',
					'product_color'            => '',
					'product_condition'        => 'none',
					'product_currency'         => empty( $opts[ 'plugin_def_currency' ] ) ? 'USD' : $opts[ 'plugin_def_currency' ],
					'product_isbn'             => '',
					'product_material'         => '',
					'product_mfr_part_no'      => '',	// Product MPN.
					'product_price'            => '0.00',
					'product_retailer_part_no' => '',	// Product SKU.
					'product_size'             => '',
					'product_target_gender'    => 'none',

					/**
					 * Open Graph - Priority Image.
					 */
					'og_img_max'    => isset( $opts[ 'og_img_max' ] ) ? (int) $opts[ 'og_img_max' ] : 1,	// 1 by default.
					'og_img_id'     => '',
					'og_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'og_img_url'    => '',

					/**
					 * Open Graph - Priority Video.
					 */
					'og_vid_max'      => isset( $opts[ 'og_vid_max' ] ) ? (int) $opts[ 'og_vid_max' ] : 1,	// 1 by default.
					'og_vid_autoplay' => empty( $opts[ 'og_vid_autoplay' ] ) ? 0 : 1,	// Enabled by default.
					'og_vid_prev_img' => empty( $opts[ 'og_vid_prev_img' ] ) ? 0 : 1,	// Enabled by default.
					'og_vid_width'    => '',	// Custom value for first video.
					'og_vid_height'   => '',	// Custom value for first video.
					'og_vid_embed'    => '',
					'og_vid_url'      => '',
					'og_vid_title'    => '',	// Custom value for first video.
					'og_vid_desc'     => '',	// Custom value for first video.

					/**
					 * Twitter Card.
					 */
					'tc_lrg_img_id'     => '',
					'tc_lrg_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'tc_lrg_img_url'    => '',
					'tc_sum_img_id'     => '',
					'tc_sum_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'tc_sum_img_url'    => '',

					/**
					 * Schema JSON-LD Markup / Rich Results.
					 */
					'schema_img_max'    => isset( $opts[ 'schema_img_max' ] ) ? (int) $opts[ 'schema_img_max' ] : 1,	// 1 by default.
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

		public function get_options( $mod_id, $md_key = false, $filter_opts = true, $pad_opts = false ) {

			if ( false === $md_key ) {
				$ret_val = array();
			} else {
				$ret_val = null;
			}

			return $this->must_be_extended( __METHOD__, $ret_val );
		}

		protected function upgrade_options( array &$md_opts ) {

			if ( ! empty( $md_opts ) && ( empty( $md_opts[ 'options_version' ] ) || 
				$md_opts[ 'options_version' ] !== $this->p->cf[ 'opt' ][ 'version' ] ) ) {

				$rename_filter_name = $this->p->lca . '_rename_md_options_keys';

				$rename_options_keys = apply_filters( $rename_filter_name, self::$rename_md_options_keys );

				$this->p->util->rename_opts_by_ext( $md_opts, $rename_options_keys );

				/**
				 * Check for schema type IDs that need to be renamed.
				 */
				$keys_preg = 'schema_type|plm_place_schema_type';

				foreach ( SucomUtil::preg_grep_keys( '/^(' . $keys_preg . ')(_[0-9]+)?$/', $md_opts ) as $key => $val ) {
					if ( ! empty( $this->p->cf[ 'head' ][ 'schema_renamed' ][ $val ] ) ) {
						$md_opts[ $key ] = $this->p->cf[ 'head' ][ 'schema_renamed' ][ $val ];
					}
				}

				/**
				 * Mark options as current.
				 */
				$md_opts[ 'options_version' ] = $this->p->cf[ 'opt' ][ 'version' ];

				return true;
			}

			return false;
		}

		/**
		 * Do not pass $md_opts by reference as the options array may get padded with default values.
		 */
		protected function return_options( $mod_id, array $md_opts, $md_key = false, $pad_opts = false ) {

			if ( $pad_opts ) {

				if ( empty( $md_opts[ 'options_padded' ] ) ) {

					$def_opts = $this->get_defaults( $mod_id );

					if ( is_array( $def_opts ) ) {	// Just in case.

						foreach ( $def_opts as $key => $val ) {

							if ( ! isset( $md_opts[ $key ] ) && $val !== '' ) {
								$md_opts[ $key ] = $def_opts[ $key ];
							}
						}
					}

					$md_opts[ 'options_padded' ] = true;
				}
			}

			if ( false !== $md_key ) {

				if ( ! isset( $md_opts[ $md_key ] ) || $md_opts[ $md_key ] === '' ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'returning null value: ' . $md_key . ' not set or empty string' );
					}

					return null;
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning meta value: ' . $md_key . ' = ' . $md_opts[ $md_key ] );
				}

				return $md_opts[ $md_key ];
			}

			return $md_opts;
		}

		public function save_options( $mod_id, $rel_id = false ) {

			return $this->must_be_extended( __METHOD__ );
		}

		public function delete_options( $mod_id, $rel_id = false ) {

			return $this->must_be_extended( __METHOD__, $mod_id );
		}

		/**
		 * Get all publicly accessible post, term, or user IDs.
		 */
		public static function get_public_ids() {

			return array();
		}

		public function get_posts_ids( array $mod, $ppp = null, $paged = null, array $posts_args = array() ) {

			return $this->must_be_extended( __METHOD__, array() );	// Return an empty array.
		}

		public function get_posts_mods( array $mod, $ppp = null, $paged = null, array $posts_args = array() ) {

			$posts_mods = array();

			foreach ( $this->get_posts_ids( $mod, $ppp, $paged, $posts_args ) as $post_id ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting mod for post object ID ' . $post_id );
				}

				/**
				 * Get the post module array.
				 */
				$posts_mods[] = $this->p->post->get_mod( $post_id );
			}

			return $posts_mods;
		}

		public function add_meta_boxes() {

			return $this->must_be_extended( __METHOD__ );
		}

		public function ajax_metabox_document_meta() {

			return $this->must_be_extended( __METHOD__ );
		}

		public function show_metabox_document_meta( $obj ) {

			return $this->must_be_extended( __METHOD__ );
		}

		public function get_metabox_document_meta( $obj ) {

			return $this->must_be_extended( __METHOD__ );
		}

		public function get_metabox_javascript( $container_id ) {

			$container_id = empty( $container_id ) ? '' : '#' . $container_id;

			$metabox_html = '';

			if ( SucomUtil::get_const( 'DOING_AJAX' ) ) {

				$metabox_html .= '<!-- metabox javascript for ajax call -->' . "\n";

				$metabox_html .= '<script type="text/javascript">sucomInitMetabox( "' . $container_id . '", true );</script>' . "\n";
			}

			return $metabox_html;
		}

		/**
		 * Does this page have a Document SSO metabox?
		 *
		 * If this is a post/term/user editing page, then the WpssoWpMeta::$head_tags variable will be an array.
		 */
		public static function is_meta_page() {

			if ( is_array( WpssoWpMeta::$head_tags ) ) {
				return true;
			}

			return false;
		}

		public static function get_head_tags() {

			return WpssoWpMeta::$head_tags;
		}

		protected function get_document_meta_tabs( $metabox_id, array $mod ) {

			$tabs = array();

			switch ( $metabox_id ) {

				case $this->p->cf[ 'meta' ][ 'id' ]:	// 'sso' metabox ID.

					if ( $mod[ 'is_public' ] ) {	// Since WPSSO Core v7.0.0.

						$tabs[ 'edit' ]     = _x( 'Customize', 'metabox tab', 'wpsso' );
						$tabs[ 'media' ]    = _x( 'Priority Media', 'metabox tab', 'wpsso' );
						$tabs[ 'preview' ]  = _x( 'Preview', 'metabox tab', 'wpsso' );
						$tabs[ 'oembed' ]   = _x( 'oEmbed', 'metabox tab', 'wpsso' );
						$tabs[ 'head' ]     = _x( 'Head Markup', 'metabox tab', 'wpsso' );
						$tabs[ 'validate' ] = _x( 'Validate', 'metabox tab', 'wpsso' );

					} else {

						$tabs[ 'edit' ]     = _x( 'Customize', 'metabox tab', 'wpsso' );
						$tabs[ 'media' ]    = _x( 'Priority Media', 'metabox tab', 'wpsso' );
					}

					break;
			}

			/**
			 * Exclude the 'Priority Media' tab from attachment editing pages.
			 */
			if ( $mod[ 'post_type' ] === 'attachment' ) {
				unset( $tabs[ 'media' ] );
			}

			/**
			 * Exclude the 'oEmbed' tab from non-post editing pages.
			 */
			if ( ! function_exists( 'get_oembed_response_data' ) ||	! $mod[ 'is_post' ] || ! $mod[ 'id' ] ) {
				unset( $tabs[ 'oembed' ] );
			}

			return apply_filters( $this->p->lca . '_' . $mod[ 'name' ] . '_document_meta_tabs', $tabs, $mod, $metabox_id );
		}

		protected function get_table_rows( $metabox_id, $tab_key, array $head_info, array $mod ) {

			$table_rows  = array();

			/**
			 * Call the following methods:
			 *
			 *	get_table_rows_sso_edit_tab()
			 *	get_table_rows_sso_media_tab()
			 *	get_table_rows_sso_preview_tab()
			 *	get_table_rows_sso_oembed_tab()
			 *	get_table_rows_sso_head_tab()
			 *	get_table_rows_sso_validate_tab()
			 */
			$method_name = 'get_table_rows_' . $metabox_id . '_' . $tab_key . '_tab';

			if ( method_exists( $this, $method_name ) ) {
				$table_rows = call_user_func( array( $this, $method_name ), $this->form, $head_info, $mod );
			}

			return $table_rows;
		}

		public function get_table_rows_sso_edit_tab( $form, $head_info, $mod ) {

			$table_rows = array();

			$dots           = '...';
			$read_cache     = true;
			$no_hashtags    = false;
			$maybe_hashtags = true;
			$do_encode      = true;

			$p_img_disabled         = empty( $this->p->options[ 'p_add_img_html' ] ) ? true : false;
			$seo_desc_disabled      = empty( $this->p->options[ 'add_meta_name_description' ] ) ? true : false;
			$canonical_url_disabled = empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? true : false;

			$p_img_msg         = $p_img_disabled ? $this->p->msgs->p_img_disabled() : '';
			$seo_desc_msg      = $seo_desc_disabled ? $this->p->msgs->seo_option_disabled( 'meta name description' ) : '';
			$canonical_url_msg = $canonical_url_disabled ? $this->p->msgs->seo_option_disabled( 'link rel canonical' ) : '';

			/**
			 * Select option arrays.
			 */
			$select_exp_secs = $this->p->util->get_cache_exp_secs( $this->p->lca . '_f_' );	// Default is month in seconds.
			$schema_exp_secs = $this->p->util->get_cache_exp_secs( $this->p->lca . '_t_' );	// Default is month in seconds.

			$og_types         = $this->p->og->get_og_types_select();
			$schema_types     = $this->p->schema->get_schema_types_select();
			$article_sections = $this->p->util->get_article_sections();

			/**
			 * Maximum option lengths.
			 */
			$og_title_max_len    = $this->p->options[ 'og_title_max_len' ];
			$og_title_warn_len   = $this->p->options[ 'og_title_warn_len' ];
			$og_desc_max_len     = $this->p->options[ 'og_desc_max_len' ];
			$og_desc_warn_len    = $this->p->options[ 'og_desc_warn_len' ];
			$p_img_desc_max_len  = $this->p->options[ 'p_img_desc_max_len' ];
			$p_img_desc_warn_len = $this->p->options[ 'p_img_desc_warn_len' ];
			$tc_desc_max_len     = $this->p->options[ 'tc_desc_max_len' ];
			$seo_desc_max_len    = $this->p->options[ 'seo_desc_max_len' ];		// Max. Description Meta Tag Length.

			/**
			 * Default option values.
			 */
			$def_og_title      = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, 'none' );
			$def_og_desc       = $this->p->page->get_description( $og_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags, $do_encode, 'none' );
			$def_p_img_desc    = $p_img_disabled ? '' : $this->p->page->get_description( $p_img_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags );
			$def_tc_desc       = $this->p->page->get_description( $tc_desc_max_len, $dots, $mod, $read_cache );
			$def_seo_desc      = $seo_desc_disabled ? '' : $this->p->page->get_description( $seo_desc_max_len, $dots, $mod, $read_cache, $no_hashtags );
			$def_sharing_url   = $this->p->util->get_sharing_url( $mod, $add_page = false );
			$def_canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = false );

			/**
			 * Metabox form rows.
			 */
			$form_rows = array(
				'attach_img_crop' => $mod[ 'post_type' ] === 'attachment' && wp_attachment_is_image( $mod[ 'id' ] ) ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Preferred Cropping', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_crop_area',
					'content'  => $form->get_input_image_crop_area( 'attach_img', $add_none = true ),
				) : array(),
				'og_schema_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_schema_type',
					'content'  => $form->get_select( 'schema_type', $schema_types, $css_class = 'schema_type', $css_id = 'og_schema_type',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json', 'on_change_unhide_rows' ),
							$event_args = array(
								'json_var'  => 'schema_types',
								'exp_secs'  => $schema_exp_secs,
								'is_transl' => true,	// No label translation required.
								'is_sorted' => true,	// No label sorting required.
							)
						),
				),
				'og_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Open Graph Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_type',
					'content'  => $form->get_select( 'og_type', $og_types, $css_class = 'og_type', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = true, $event_names = array( 'on_change_unhide_rows' ) ),
				),
				'og_title' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Default Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_title',
					'content'  => $form->get_input( 'og_title', $css_class = 'wide', $css_id = '',
						array( 'max' => $og_title_max_len, 'warn' => $og_title_warn_len ), $def_og_title ),
				),
				'og_desc' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Default Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_desc',
					'content'  => $form->get_textarea( 'og_desc', $css_class = '', $css_id = '', 
						array( 'max' => $og_desc_max_len, 'warn' => $og_desc_warn_len ), $def_og_desc ),
				),
				'p_img_desc' => array(
					'tr_class' => $p_img_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Pinterest Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-p_img_desc',
					'content'  => $form->get_textarea( 'p_img_desc', $css_class = '', $css_id = '',
						array( 'max' => $p_img_desc_max_len, 'warn' => $p_img_desc_warn_len ),
							$def_p_img_desc, $p_img_disabled ) . ' ' . $p_img_msg,
				),
				'tc_desc' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-tc_desc',
					'content'  => $form->get_textarea( 'tc_desc', $css_class = '', $css_id = '',
						$tc_desc_max_len, $def_tc_desc ),
				),
				'seo_desc' => array(
					'tr_class' => $seo_desc_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Search Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-seo_desc',
					'content'  => $form->get_textarea( 'seo_desc', $css_class = '', $css_id = '',
						$seo_desc_max_len, $def_seo_desc, $seo_desc_disabled ) . ' ' . $seo_desc_msg,
				),
				'sharing_url' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'sharing_url' ),
					'th_class' => 'medium',
					'label'    => _x( 'Sharing URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-sharing_url',
					'content'  => $form->get_input( 'sharing_url', $css_class = 'wide', $css_id = '',
						$max_len = 0, $def_sharing_url ),
				),
				'canonical_url' => array(
					'tr_class' => $canonical_url_disabled ? 'hide_in_basic' : $form->get_css_class_hide( 'basic', 'canonical_url' ),
					'th_class' => 'medium',
					'label'    => _x( 'Canonical URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-canonical_url',
					'content'  => $form->get_input( 'canonical_url', $css_class = 'wide', $css_id = '',
						$max_len = 0, $def_canonical_url, $canonical_url_disabled ) . ' ' . $canonical_url_msg,
				),

				/**
				 * Open Graph Article type.
				 */
				'subsection_og_article' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Article Information', 'metabox title', 'wpsso' )
				),
				'og_article_section' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'th_class' => 'medium',
					'label'    => _x( 'Article Section', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-article_section',
					'content'  => $form->get_select( 'article_section', $article_sections, $css_class = 'article_section', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
							$event_args = array(
								'json_var'  => 'article_sections',
								'exp_secs'  => $select_exp_secs,
								'is_transl' => true,	// No label translation required.
								'is_sorted' => true,	// No label sorting required.
							)
						),
				),
			);

			return $form->get_md_form_rows( array(), $form_rows, $head_info, $mod );
		}

		public function get_table_rows_sso_media_tab( $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			$size_name = $this->p->lca . '-opengraph';

			$media_info = $this->p->og->get_media_info( $size_name, array( 'pid', 'img_url' ), $mod, $md_pre = 'none', $mt_pre = 'og' );

			/**
			 * Metabox form rows.
			 */
			$form_rows = array(
				'info-priority-media' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-priority-media' ) . '</td>',
				),
				'subsection_opengraph' => array(
					'td_class' => 'subsection top',
					'header'   => 'h4',
					'label'    => _x( 'Facebook / Open Graph and Default Media', 'metabox title', 'wpsso' ),
				),
				'subsection_priority_image' => array(
					'td_class' => 'subsection top',
					'header'   => 'h5',
					'label'    => _x( 'Priority Image Information', 'metabox title', 'wpsso' )
				),
				'og_img_max' => $mod[ 'is_post' ] ? array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_img_max' ),
					'th_class' => 'medium',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'og_img_max',		// Use tooltip message from settings.
					'content'  => $form->get_select( 'og_img_max', range( 0, $max_media_items ), $css_class = 'medium' ),
				) : '',	// Placeholder if not a post module.
				'og_img_id' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_id',
					'content'  => $form->get_input_image_upload( 'og_img', $media_info[ 'pid' ] ),
				),
				'og_img_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_url',
					'content'  => $form->get_input_image_url( 'og_img', $media_info[ 'img_url' ] ),
				),
			);

			/**
			 * Additional sections and sub-sections added by the 'wpsso_meta_media_rows' filter:
			 *
			 * 	Facebook / Open Graph and Default Media
			 *
			 * 		Priority Video Information
			 *
			 * 	Pinterest Pin It
			 *
			 * 	Twitter Card
			 *
			 * 	Schema JSON-LD Markup / Rich Results
			 */

			return $form->get_md_form_rows( array(), $form_rows, $head_info, $mod );
		}

		public function get_table_rows_sso_preview_tab( $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows       = array();
			$og_prev_width    = 600;
			$og_prev_height   = 315;
			$og_prev_img_html = '';
			$media_url        = SucomUtil::get_mt_media_url( $head_info );
			$sharing_url      = $this->p->util->get_sharing_url( $mod, $add_page = false );
			$canonical_url    = $this->p->util->get_canonical_url( $mod, $add_page = false );

			if ( $mod[ 'is_post' ] ) {
				$shortlink_url = SucomUtilWP::wp_get_shortlink( $mod[ 'id' ], $context = 'post' );
			} else {
				$shortlink_url = apply_filters( $this->p->lca . '_get_short_url', $sharing_url, $this->p->options[ 'plugin_shortener' ], $mod );
			}

			$have_sizes = isset( $head_info[ 'og:image:width' ] ) && $head_info[ 'og:image:width' ] > 0 && 
				isset( $head_info[ 'og:image:height' ] ) && $head_info[ 'og:image:height' ] > 0 ? true : false;

			$is_sufficient = true === $have_sizes && $head_info[ 'og:image:width' ] >= $og_prev_width && 
				$head_info[ 'og:image:height' ] >= $og_prev_height ? true : false;

			if ( ! empty( $media_url ) ) {

				if ( $have_sizes ) {

					$og_prev_img_html .= '<div class="preview_img" style=" background-size:' ;

					if ( $is_sufficient ) {
						$og_prev_img_html .= 'cover';
					} else {
						$og_prev_img_html .= $head_info[ 'og:image:width' ] . 'px ' . $head_info[ 'og:image:height' ] . 'px';
					}

					$og_prev_img_html .= '; background-image:url(' . $media_url . ');" />';

					if ( ! $is_sufficient ) {
						$og_prev_img_html .= '<p>' . sprintf( _x( 'Image Size Smaller<br/>than Suggested Minimum<br/>of %s',
							'preview image error', 'wpsso' ), $og_prev_width . 'x' . $og_prev_height . 'px' ) . '</p>';
					}

					$og_prev_img_html .= '</div>';

				} else {

					$og_prev_img_html .= '<div class="preview_img" style="background-image:url(' . $media_url . ');" />';
					$og_prev_img_html .= '<p>' . _x( 'Image Size Unknown<br/>or Not Available', 'preview image error', 'wpsso' ) . '</p>';
					$og_prev_img_html .= '</div>';
				}

			} else {

				$og_prev_img_html .= '<div class="preview_img">';
				$og_prev_img_html .= '<p>' . _x( 'No Open Graph Image Found', 'preview image error', 'wpsso' ) . '</p>';
				$og_prev_img_html .= '</div>';
			}

			$table_rows[] = '' . 
			$form->get_th_html( _x( 'Sharing URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $sharing_url ) . '</td>';

			$table_rows[] = ( $sharing_url === $canonical_url ? '<tr class="hide_in_basic">' : '' ) . 
			$form->get_th_html( _x( 'Canonical URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $canonical_url ) . '</td>';

			$table_rows[] = ( empty( $this->p->options[ 'plugin_shortener' ] ) || 
				$this->p->options[ 'plugin_shortener' ] === 'none' ||
					$sharing_url === $shortlink_url ? '<tr class="hide_in_basic">' : '' ) . 
			$form->get_th_html( _x( 'Shortlink URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $shortlink_url ) . '</td>';

			$table_rows[ 'subsection_og_example' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'Facebook / Open Graph Example', 'option label', 'wpsso' ) . '</h4></td>';

			$table_rows[] = '' .
			'<td colspan="2" class="preview_container">
				<div class="preview_box_border">
					<div class="preview_box">
						' . $og_prev_img_html . '
						<div class="preview_txt">
							<div class="preview_title">' . ( empty( $head_info[ 'og:title' ] ) ?
								_x( 'No Title', 'default title', 'wpsso' ) : $head_info[ 'og:title' ] ) . 
							'</div><!-- .preview_title -->
							<div class="preview_desc">' . ( empty( $head_info[ 'og:description' ] ) ?
								_x( 'No Description.', 'default description', 'wpsso' ) : $head_info[ 'og:description' ] ) . 
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
			</td><!-- .preview_container -->';

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-social-preview' ) . '</td>';

			return $table_rows;
		}

		public function get_table_rows_sso_oembed_tab( $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows     = array();
			$oembed_data    = false;
			$oembed_html    = '';
			$oembed_width   = 600;

			$json_url    = $this->p->util->get_oembed_url( $mod, 'json' );
			$xml_url     = $this->p->util->get_oembed_url( $mod, 'xml' );
			$oembed_data = $this->p->util->get_oembed_data( $mod, $oembed_width );

			$table_rows[] = $form->get_th_html( _x( 'oEmbed JSON URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $json_url ) . '</td>';

			$table_rows[] = $form->get_th_html( _x( 'oEmbed XML URL', 'option label', 'wpsso' ), 'medium' ) . 
			'<td>' . SucomForm::get_no_input_clipboard( $xml_url ) . '</td>';

			$table_rows[ 'subsection_oembed_data' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'oEmbed Data', 'option label', 'wpsso' ) . '</h4></td>';

			if ( ! empty( $oembed_data ) && is_array( $oembed_data ) ) {

				foreach( $oembed_data as $key => $val ) {

					if ( 'html' === $key ) {

						$oembed_html = $val;

						$val = __( '(see below)', 'wpsso' );
					}

					$table_rows[] = '<th class="short">' . esc_html( $key ) . '</th>' .
						'<td class="wide">' . SucomUtil::maybe_link_url( esc_html( $val ) ) . '</td>';
				}

			} else {
				$table_rows[] = '<td colspan="2"><p class="status-msg">' . __( 'No oEmbed data found.', 'wpsso' ) . '</p></td>';
			}

			$table_rows[ 'subsection_oembed_html' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'oEmbed HTML', 'option label', 'wpsso' ) . '</h4></td>';

			if ( ! empty( $oembed_html ) ) {

				$table_rows[] = '<td colspan="2" class="oembed_container">' . $oembed_html . '</td><!-- .oembed_container -->';

				$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-oembed-html' ) . '</td>';

			} else {
				$table_rows[] = '<td colspan="2"><p class="status-msg">' . __( 'No oEmbed HTML found.', 'wpsso' ) . '</p></td>';
			}

			return $table_rows;
		}

		public function get_table_rows_sso_head_tab( $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows = array();

			if ( ! is_array( WpssoWpMeta::$head_tags ) ) {	// Just in case.

				return $table_rows;
			}

			$script_class = '';

			foreach ( WpssoWpMeta::$head_tags as $parts ) {

				if ( 1 === count( $parts ) ) {

					if ( 0 === strpos( $parts[0], '<script ' ) ) {
						$script_class = 'script';
					} elseif ( 0 === strpos( $parts[0], '<noscript ' ) ) {
						$script_class = 'noscript';
					}

					$table_rows[] = '<td colspan="5" class="html ' . $script_class . '"><pre>' . esc_html( $parts[0] ) . '</pre></td>';

					if ( 'script' === $script_class || 0 === strpos( $parts[0], '</noscript>' ) ) {
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
						'<td class="wide">' . SucomUtil::maybe_link_url( $parts[5] ) . '</td>';
				}
			}

			return $table_rows;
		}

		public function get_table_rows_sso_validate_tab( $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$sharing_url = $this->p->util->get_sharing_url( $mod, $add_page = false );

			$sharing_url_encoded = urlencode( $sharing_url );

			$have_schema = empty( $this->p->avail[ 'p' ][ 'schema' ] ) || empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ?  false : true;

			$have_amp = function_exists( 'amp_get_permalink' ) ? true : false;

			$amp_url_encoded = $have_amp ? urlencode( amp_get_permalink( $mod[ 'id' ] ) ) : '';

			$buttons = array(
				'facebook-debugger' => array(
					'title' => _x( 'Facebook Sharing Debugger', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Open Graph', 'submit button', 'wpsso' ),
					'url'   => 'https://developers.facebook.com/tools/debug/?q=' . $sharing_url_encoded,
				),
				'facebook-microdata' => array(
					'title' => _x( 'Facebook Microdata Debug Tool', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Microdata', 'submit button', 'wpsso' ),
					'url'   => 'https://business.facebook.com/ads/microdata/debug',
					'msg'   => $this->p->msgs->get( 'info-meta-validate-facebook-microdata' ) .
						SucomForm::get_no_input_clipboard( $sharing_url ),
				),
				'google-page-speed' => array(
					'title' => _x( 'Google PageSpeed Insights', 'option label', 'wpsso' ),
					'label' => _x( 'Validate PageSpeed', 'submit button', 'wpsso' ),
					'url'   => 'https://developers.google.com/speed/pagespeed/insights/?url=' . $sharing_url_encoded,
				),
				'google-testing-tool' => array(
					'title' => _x( 'Google Structured Data Test', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Structured Data', 'submit button', 'wpsso' ) . ( $have_schema ? '' : ' *' ),
					'url'   => $have_schema ? 'https://search.google.com/structured-data/testing-tool/u/0/#url=' . $sharing_url_encoded : '',
				),
				'google-rich-results' => array(
					'title' => _x( 'Google Rich Results Test', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Rich Results', 'submit button', 'wpsso' ) . ( $have_schema ? '' : ' *' ),
					'url'   => $have_schema ? 'https://search.google.com/test/rich-results?url=' . $sharing_url_encoded : '',
				),
				'linkedin' => array(
					'title' => _x( 'LinkedIn Post Inspector', 'option label', 'wpsso' ),
					'label' => _x( 'Validate Metadata', 'submit button', 'wpsso' ),
					'url'   => 'https://www.linkedin.com/post-inspector/inspect/' . $sharing_url_encoded,
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
					'msg'   => $this->p->msgs->get( 'info-meta-validate-twitter' ) .
						SucomForm::get_no_input_clipboard( $sharing_url ),
				),
				'amp' => array(
					'title' => $mod[ 'is_post' ] ? _x( 'The AMP Project Validator', 'option label', 'wpsso' ) : '',
					'label' => $mod[ 'is_post' ] ? _x( 'Validate AMP Markup', 'submit button', 'wpsso' ) . ( $have_amp ? '' : ' **' ) : '',
					'url'   => $mod[ 'is_post' ] && $have_amp ? 'https://validator.ampproject.org/#url=' . $amp_url_encoded : '',
				),
				'w3c' => array(
					'title' => _x( 'W3C Markup Validation', 'option label', 'wpsso' ),
					'label' => _x( 'Validate HTML Markup', 'submit button', 'wpsso' ),
					'url'   => 'https://validator.w3.org/nu/?doc=' . $sharing_url_encoded,
				),
			);

			$table_rows = array();

			foreach ( $buttons as $key => $b ) {

				if ( ! empty( $b[ 'title' ] ) ) {

					$table_rows[ 'validate_' . $key ] = $form->get_th_html( $b[ 'title' ], 'medium' );

					$table_rows[ 'validate_' . $key ] .= '<td class="validate">' . 
						( isset( $b[ 'msg' ] ) ? $b[ 'msg' ] : $this->p->msgs->get( 'info-meta-validate-' . $key ) ) .
							'</td>';

					$table_rows[ 'validate_' . $key ] .= '<td class="validate">' .
						$form->get_button( $b[ 'label' ], 'button-secondary', '', $b[ 'url' ], $newtab = true, ( $b[ 'url' ] ? false : true ) ) .
							'</td>';
				}
			}

			$table_rows[ 'validate_info' ] = '<td class="validate" colspan="3">' . $this->p->msgs->get( 'info-meta-validate-info' ) . '</td>';

			return $table_rows;
		}

		public function get_head_info( $obj_id, $read_cache = true ) {

			static $local_cache = array();

			$class = get_called_class();

			if ( __CLASS__ === $class ) {	// Just in case.
				return $this->must_be_extended( __METHOD__, array() );
			}

			$cache_id = 'id:' . $obj_id;

			if ( isset( $local_cache[ $cache_id ] ) ) {
				return $local_cache[ $cache_id ];
			}

			$mod = $this->get_mod( $obj_id );

			$local_head_tags = $this->p->head->get_head_array( $use_post = false, $mod, $read_cache );

			$local_head_info = $this->p->head->extract_head_info( $mod, $local_head_tags );

			return $local_cache[ $cache_id ] = $local_head_info;
		}

		/**
		 * Return a specific option from the custom social settings meta with fallback for multiple option keys. If $md_key
		 * is an array, then get the first non-empty option from the options array. This is an easy way to provide a
		 * fallback value for the first array key. Use 'none' as a key name to skip this fallback behavior.
		 *
		 * Example: get_options_multi( $id, array( 'seo_desc', 'og_desc' ) );
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

		public function user_can_edit( $mod_id, $rel_id = false ) {

			return $this->must_be_extended( __METHOD__, false );	// Return false by default.
		}

		public function clear_cache( $mod_id, $rel_id = false ) {

			return $this->must_be_extended( __METHOD__ );
		}

		protected function clear_mod_cache( array $mod, array $cache_types = array(), $sharing_url = false ) {

			if ( false === $sharing_url ) {
				$sharing_url = $this->p->util->get_sharing_url( $mod );
			}

			$mod_salt = SucomUtil::get_mod_salt( $mod, $sharing_url );

			$cache_types[ 'transient' ][] = array(
				'id'   => $this->p->lca . '_h_' . md5( 'WpssoHead::get_head_array(' . $mod_salt . ')' ),
				'pre'  => $this->p->lca . '_h_',
				'salt' => 'WpssoHead::get_head_array(' . $mod_salt . ')',
			);

			$cache_types[ 'wp_cache' ][] = array(
				'id'   => $this->p->lca . '_c_' . md5( 'WpssoPage::get_the_content(' . $mod_salt . ')' ),
				'pre'  => $this->p->lca . '_c_',
				'salt' => 'WpssoPage::get_the_content(' . $mod_salt . ')',
			);

			$deleted_count = 0;

			$deleted_ids = array();

			foreach ( $cache_types as $type_name => $type_keys ) {

				/**
				 * $filter_name example: 'wpsso_post_cache_transient_keys'.
				 */
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

		protected function verify_submit_nonce() {

			if ( empty( $_POST ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'empty POST for submit' );
				}

				return false;

			}

			if ( empty( $_POST[ WPSSO_NONCE_NAME ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'submit POST missing nonce token' );
				}

				return false;

			}

			if ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'submit nonce token validation failed' );
				}

				if ( is_admin() ) {
					$this->p->notice->err( __( 'Nonce token validation failed for the submitted form (update ignored).',
						'wpsso' ) );
				}

				return false;

			}

			return true;
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
			foreach ( array( 'og', 'p', 'schema', 'tc_lrg', 'tc_sum' ) as $md_pre ) {

				/**
				 * If there's no image ID, then remove the image ID library prefix.
				 *
				 * If an image ID is being used, then remove the image url (only one can be defined).
				 */
				if ( empty( $md_opts[ $md_pre . '_img_id' ] ) ) {
					unset( $md_opts[ $md_pre . '_img_id_pre' ] );
				} else {
					unset( $md_opts[ $md_pre . '_img_url' ] );
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

				if ( empty( $is_multi ) ) {	// True, false, or array.
					continue;
				}

				/**
				 * Get multi option values indexed only by their number.
				 */
				$md_multi_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi . '_([0-9]+)$/', $md_opts, $invert = false, $replace = '$1' );

				$md_renum_opts = array();	// Start with a fresh array.

				$renum = 0;	// Start a new index at 0.

				foreach ( $md_multi_opts as $md_num => $md_val ) {

					if ( $md_val !== '' ) {	// Only save non-empty values.
						$md_renum_opts[ $md_multi . '_' . $renum ] = $md_val;
					}

					/**
					 * Check if there are linked options, and if so, re-number those options as well.
					 */
					if ( is_array( $is_multi ) ) {
						foreach ( $is_multi as $md_multi_linked ) {
							if ( isset( $md_opts[ $md_multi_linked . '_' . $md_num ] ) ) {	// Just in case.
								$md_renum_opts[ $md_multi_linked . '_' . $renum ] = $md_opts[ $md_multi_linked . '_' . $md_num ];
							}
						}
					}

					$renum++;	// Increment the new index number.
				}

				/**
				 * Remove any existing multi options, including any linked options.
				 */
				$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi . '_([0-9]+)$/', $md_opts, $invert = true );

				if ( is_array( $is_multi ) ) {
					foreach ( $is_multi as $md_multi_linked ) {
						$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi_linked . '_([0-9]+)$/', $md_opts, $invert = true );
					}
				}

				/**
				 * Save the re-numbered options.
				 */
				foreach ( $md_renum_opts as $md_key => $md_val ) {
					$md_opts[ $md_key ] = $md_val;
				}

				unset( $md_renum_opts );
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

				if ( $wpsso->debug->enabled ) {
					$wpsso->debug->log_arr( '$sort_cols', $sort_cols );
				}
			}

			if ( false !== $col_key ) {

				if ( isset( $sort_cols[ $col_key ] ) ) {
					return $sort_cols[ $col_key ];
				}

				return null;
				
			}

			return $sort_cols;
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

					/**
					 * Check if the column is enabled globally for the post, term, or user edit list.
					 */
					if ( ! empty( $this->p->options[ 'plugin_' . $col_key . '_col_' . $mod_name] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'adding ' . $this->p->lca . '_' . $col_key . ' column' );
						}

						$columns[ $this->p->lca . '_' . $col_key ] = $col_header;
					}
				}
			}

			return $columns;
		}

		public function get_pid_thumb_img_html( $pid, $mod, $md_pre = 'og' ) {

			if ( empty( $pid ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: image id is empty' );
				}

				return '';
			}

			$media_html  = false;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'getting thumbnail for image id ' . $pid );
			}

			$mt_single_image = array();

			$this->p->media->add_mt_single_image_src( $mt_single_image, $pid, $size_name = 'thumbnail', $check_dupes = false );

			$media_url = SucomUtil::get_mt_media_url( $mt_single_image );

			if ( ! empty( $media_url ) ) {
				$media_html = '<img src="' . $media_url . '">';
			}

			return $media_html;
		}

		/**
		 * Note that $md_pre can be a text string or array of prefixes.
		 */
		public function get_md_images( $num, $size_name, array $mod, $check_dupes = true, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'num'         => $num,
					'size_name'   => $size_name,
					'mod'         => $mod,
					'check_dupes' => $check_dupes,
					'md_pre'      => $md_pre,
					'mt_pre'      => $mt_pre,
				), get_class( $this ) );
			}

			$mt_ret = array();

			if ( empty( $mod[ 'id' ] ) ) {
				return $mt_ret;
			}

			if ( is_array( $md_pre ) ) {
				$md_pre_unique = array_merge( $md_pre, array( 'og' ) );
			} else {
				$md_pre_unique = array( $md_pre, 'og' );
			}

			$md_pre_unique = array_unique( $md_pre_unique );

			foreach( $md_pre_unique as $opt_pre ) {

				if ( $opt_pre === 'none' ) {		// Special index keyword.
					break;
				} elseif ( empty( $opt_pre ) ) {	// Skip empty md_pre values.
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

					$this->p->media->add_mt_single_image_src( $mt_single_image, $pid, $size_name, $check_dupes, $mt_pre );
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

				/**
				 * Stop here if we had a custom image ID or URL.
				 */
				if ( $pid || $url ) {
					break;
				}
			}

			foreach ( apply_filters( $this->p->lca . '_' . $mod[ 'name' ] . '_image_ids', array(), $size_name, $mod[ 'id' ], $mod ) as $pid ) {

				if ( $pid > 0 ) {	// Quick sanity check.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'adding image pid: ' . $pid );
					}

					$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

					$this->p->media->add_mt_single_image_src( $mt_single_image, $pid, $size_name, $check_dupes, $mt_pre );

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

		protected function must_be_extended( $method, $ret = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $method . ' must be extended', get_class( $this ) );	// Log the extended class name.
			}

			return $ret;
		}

		/**
		 * Note that $md_pre can be a text string or array of prefixes.
		 */
		public function get_og_images( $num, $size_name, $mod_id, $check_dupes = true, $md_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mod = $this->get_mod( $mod_id );

			return $this->get_md_images( $num, $size_name, $mod, $check_dupes, $md_pre, 'og' );
		}

		/**
		 * Note that $md_pre can be a text string or array of prefixes.
		 */
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

			$mod       = $this->get_mod( $mod_id );	// Required for get_content_videos().
			$og_ret    = array();
			$og_videos = array();

			if ( empty( $mod_id ) ) {
				return $og_ret;
			}

			if ( is_array( $md_pre ) ) {
				$md_pre_unique = array_merge( $md_pre, array( 'og' ) );
			} else {
				$md_pre_unique = array( $md_pre, 'og' );
			}

			$md_pre_unique = array_unique( $md_pre_unique );

			foreach( $md_pre_unique as $opt_pre ) {

				$embed_html = $this->get_options( $mod_id, $opt_pre . '_vid_embed' );
				$video_url  = $this->get_options( $mod_id, $opt_pre . '_vid_url' );

				/**
				 * Retrieve one or more videos from the embed HTML code. 
				 */
				if ( ! empty( $embed_html ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'fetching video(s) from custom ' . $opt_pre . ' embed code',
							get_class( $this ) );	// Log extended class name.
					}

					$og_ret = array_merge( $og_ret, $this->p->media->get_content_videos( $num, $mod, $check_dupes, $embed_html ) );
				}

				if ( ! empty( $video_url ) && ( ! $check_dupes || $this->p->util->is_uniq_url( $video_url ) ) ) {

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

		public function get_og_img_column_html( $head_info, $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			$media_html  = false;

			if ( ! empty( $head_info[ $mt_pre . ':image:id' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting thumbnail for image id ' . $head_info[ $mt_pre . ':image:id' ] );
				}

				$mt_single_image = array();

				/**
				 * Get the smaller thumbnail image as a preview image.
				 */
				$this->p->media->add_mt_single_image_src( $mt_single_image, $head_info[ $mt_pre . ':image:id' ],
					$size_name = 'thumbnail', $check_dupes = false );

				if ( ! empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {	// Just in case.
					$head_info =& $mt_single_image;
				}
			}

			$media_url = SucomUtil::get_mt_media_url( $head_info );

			if ( ! empty( $media_url ) ) {
				$media_html = '<div class="preview_img" style="background-image:url(' . $media_url . ');"></div>';
			}

			return $media_html;
		}

		public function get_og_type_reviews( $mod_id, $og_type = 'product', $rating_meta = 'rating', $worst_rating = 1, $best_rating = 5 ) {

			return $this->must_be_extended( __METHOD__, array() );	// Return an empty array.
		}

		public function get_og_review_mt( $comment_obj, $og_type = 'product', $rating_meta = 'rating', $worst_rating = 1, $best_rating = 5 ) {

			return $this->must_be_extended( __METHOD__, array() );	// Return an empty array.
		}

		/**
		 * WpssoPost class specific methods.
		 */
		public function get_sharing_shortlink( $shortlink = false, $mod_id = 0, $context = 'post', $allow_slugs = true ) {

			return $this->must_be_extended( __METHOD__, '' );
		}

		public function maybe_restore_shortlink( $shortlink = false, $mod_id = 0, $context = 'post', $allow_slugs = true ) {

			return $this->must_be_extended( __METHOD__, '' );
		}

		/**
		 * WpssoUser class specific methods.
		 */
		public function get_authors_websites( $user_ids, $field_id = 'url' ) {

			return $this->must_be_extended( __METHOD__, array() );
		}

		public function get_author_website( $user_id, $field_id = 'url' ) {

			return $this->must_be_extended( __METHOD__, '' );
		}
	}
}
