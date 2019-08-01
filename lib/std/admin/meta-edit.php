<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoStdAdminMetaEdit' ) ) {

	class WpssoStdAdminMetaEdit {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'meta_edit_rows' => array(
					'post_edit_rows' => 4,
					'term_edit_rows' => 4,
					'user_edit_rows' => 4,
				),
				'meta_media_rows' => array(
					'post_media_rows' => 4,
					'term_media_rows' => 4,
					'user_media_rows' => 4,
				),
			) );
		}

		public function filter_meta_edit_rows( $table_rows, $form, $head, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$dots           = '...';
			$read_cache     = true;
			$no_hashtags    = false;
			$maybe_hashtags = true;
			$do_encode      = true;
			$og_types       = $this->p->og->get_og_types_select( $add_none = true );
			$art_topics     = $this->p->util->get_article_topics();

			/**
			 * The 'add_link_rel_canonical' and 'add_meta_name_description' options will be empty if an SEO plugin is detected.
			 */
			$add_link_rel_canon = empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? false : true;
			$add_meta_name_desc = empty( $this->p->options[ 'add_meta_name_description' ] ) ? false : true;
			$add_meta_name_desc = apply_filters( $this->p->lca . '_add_meta_name_description', $add_meta_name_desc, $mod );

			/**
			 * Maximum option lengths.
			 */
			$og_title_max_len    = $this->p->options[ 'og_title_max_len' ];
			$og_desc_max_len     = $this->p->options[ 'og_desc_max_len' ];
			$seo_desc_max_len    = $this->p->options[ 'seo_desc_max_len' ];
			$tc_desc_max_len     = $this->p->options[ 'tc_desc_max_len' ];
			$schema_desc_max_len = $this->p->options[ 'schema_desc_max_len' ];
			$schema_desc_md_key  = array( 'seo_desc', 'og_desc' );

			/**
			 * Default option values.
			 */
			$def_og_type     = $this->p->og->get_mod_og_type( $mod, $get_type_ns = false, $use_mod_opts = false );
			$def_art_section = $this->p->page->get_article_section( $mod[ 'id' ], $allow_none = true, $use_mod_opts = false );
			$def_og_title    = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, 'none' );
			$def_og_desc     = $this->p->page->get_description( $og_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags, $do_encode, 'none' );
			$def_seo_desc    = $add_meta_name_desc ? $this->p->page->get_description( $seo_desc_max_len, $dots, $mod, $read_cache, $no_hashtags ) : '';
			$def_tc_desc     = $this->p->page->get_description( $tc_desc_max_len, $dots, $mod, $read_cache );
			$def_schema_desc = $this->p->page->get_description( $schema_desc_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, $schema_desc_md_key );

			/**
			 * Current option values.
			 */
			$sharing_url   = $this->p->util->get_sharing_url( $mod, $add_page = false );
			$canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = false );

			/**
			 * Metabox form rows.
			 */
			$form_rows = array(
				'og_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Open Graph Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_type',
					'content'  => $form->get_select( 'og_type',
						$og_types, '', '', true, false, true, 'on_change_unhide_rows' ),
				),
				'pro-feature-msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>',
				),
				'og_title' => array(
					'no_auto_draft' => true,
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Default Title', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-og_title',
					'content'       => $form->get_no_input_value( $def_og_title, 'wide' ),
				),
				'og_desc' => array(
					'no_auto_draft' => true,
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Default Description', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-og_desc',
					'content'       => $form->get_no_textarea_value( $def_og_desc, '', '', $og_desc_max_len ),
				),
				'seo_desc' => array(
					'no_auto_draft' => true,
					'tr_class'      => ( $add_meta_name_desc ? '' : 'hide_in_basic' ),
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Search Description', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-seo_desc',
					'content'       => $form->get_no_textarea_value( $def_seo_desc, '', '', $seo_desc_max_len ) .
						( $add_meta_name_desc ? '' : $this->p->msgs->seo_option_disabled( 'meta name description' ) ),
				),
				'tc_desc' => array(
					'no_auto_draft' => true,
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Twitter Card Desc', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-tc_desc',
					'content'       => $form->get_no_textarea_value( $def_tc_desc, '', '', $tc_desc_max_len ),
				),
				'sharing_url' => array(
					'no_auto_draft' => ( $mod[ 'post_type' ] === 'attachment' ? false : true ),
					'tr_class'      => $form->get_css_class_hide( 'basic', 'sharing_url' ),
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Sharing URL', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-sharing_url',
					'content'       => $form->get_no_input_value( $sharing_url, 'wide' ),
				),
				'canonical_url' => array(
					'no_auto_draft' => ( $mod[ 'post_type' ] === 'attachment' ? false : true ),
					'tr_class'      => ( $add_link_rel_canon ? $form->get_css_class_hide( 'basic', 'canonical_url' ) : 'hide_in_basic' ),
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Canonical URL', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-canonical_url',
					'content'       => $form->get_no_input_value( $canonical_url, 'wide' ) .
						( $add_link_rel_canon ? '' : $this->p->msgs->seo_option_disabled( 'link rel canonical' ) ),
				),

				/**
				 * Open Graph Article type.
				 */
				'subsection_og_art' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'header'   => 'h5',
					'label'    => _x( 'Article Information', 'metabox title', 'wpsso' )
				),
				'og_art_section' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Article Topic', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_art_section',
					'content'  => $form->get_select( 'og_art_section', $art_topics,
						'', '', false, $def_art_section, $def_art_section ),
				),

				/**
				 * Open Graph Product type.
				 */
				'subsection_og_product' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'header'   => 'h5',
					'label'    => _x( 'Product Information', 'metabox title', 'wpsso' )
				),
				'og_product_ecom_msg' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'table_row' => ( empty( $this->p->avail[ 'ecom' ][ 'any' ] ) ? '' :
						'<td colspan="2">' . $this->p->msgs->get( 'pro-ecom-product-msg' ) . '</td>' ),
				),
				'og_product_brand' => array(		// Open Graph meta tag product:brand.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Brand', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_brand',
					'content'  => $form->get_no_input( 'product_brand', '', '', $placeholder = true ),
				),
				'og_product_avail' => array(		// Open Graph meta tag product:availability.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Availability', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_avail',
					'content'  => $form->get_no_select( 'product_avail', $this->p->cf[ 'form' ][ 'item_availability' ],
						$css_class = '', $css_id = '', $is_assoc = true, $selected = true ),
				),
				'og_product_price' => array(		// Open Graph meta tags product:price:amount and product:price:currency.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Price', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_price',
					'content'  => $form->get_no_input( 'product_price', '', '', $placeholder = true ) . ' ' .
						$form->get_no_select( 'product_currency', SucomUtil::get_currency_abbrev(),
							$css_class = 'currency', $css_id = '', $is_assoc = true ),
				),
				'og_product_condition' => array(		// Open Graph meta tag product:condition.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Condition', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_condition',
					'content'  => $form->get_no_select( 'product_condition', $this->p->cf[ 'form' ][ 'item_condition' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'og_product_material' => array(		// Open Graph meta tag product:material.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Material', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_material',
					'content'  => $form->get_no_input( 'product_material', '', '', $placeholder = true ),
				),
				'og_product_color' => array(		// Open Graph meta tag product:color.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Color', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_color',
					'content'  => $form->get_no_input( 'product_color', '', '', $placeholder = true ),
				),
				'og_product_target_gender' => array(	// Open Graph meta tag product:target_gender.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Target Gender', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_target_gender',
					'content'  => $form->get_no_select( 'product_target_gender', $this->p->cf[ 'form' ][ 'audience_gender' ],
						$css_class = 'gender', $css_id = '', $is_assoc = true ),
				),
				'og_product_size' => array(		// Open Graph meta tag product:size.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Size', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_size',
					'content'  => $form->get_no_input( 'product_size', '', '', $placeholder = true ),
				),
				'og_product_weight_value' => array(	// Open Graph meta tag product:weight:value.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Weight', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_weight_value',
					'content'  => $form->get_no_input( 'product_weight_value', '', '', $placeholder = true ) .
						WpssoAdmin::get_option_unit_comment( 'product_weight_value' ),
				),
				'og_product_sku' => array(			// Open Graph meta tag product:retailer_item_id.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product SKU', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_sku',
					'content'  => $form->get_no_input( 'product_sku', '', '', $placeholder = true ),
				),
				'og_product_mpn' => array(			// Open Graph meta tag product:mfr_part_no.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product MPN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_mpn',
					'content'  => $form->get_no_input( 'product_mpn', '', '', $placeholder = true ),
				),
				'og_product_isbn' => array(		// Open Graph meta tag product:isbn.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_isbn',
					'content'  => $form->get_no_input( 'product_isbn', '', '', $placeholder = true ),
				),

				/**
				 * All Schema Types
				 */
				'subsection_schema' => array(
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Structured Data / Schema Markup', 'metabox title', 'wpsso' )
				),
				'schema_desc' => array(
					'no_auto_draft' => true,
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Description', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-schema_desc',
					'content'       => $form->get_no_textarea_value( $def_schema_desc, '', '', $schema_desc_max_len ),
				),
				'wpssojson_addon_msg' => array(
					'table_row' => ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ?
						'<td colspan="2">' . $this->p->msgs->more_schema_options() . '</td>' : '' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head, $mod );
		}

		public function filter_meta_media_rows( $table_rows, $form, $head, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] && ( empty( $mod[ 'post_status' ] ) || $mod[ 'post_status' ] === 'auto-draft' ) ) {

				$table_rows[] = '<td>' .
					'<blockquote class="status-info">' .
						'<p class="centered">' . 
							sprintf( __( 'Save a draft version or publish the %s to display these options.', 'wpsso' ),
								SucomUtil::titleize( $mod[ 'post_type' ] ) ) .
						'</p>' .
					'<blockquote>' .
				'</td>';

				return $table_rows;	// abort
			}

			$media_info = $this->p->og->get_media_info( $this->p->lca . '-opengraph',
				array( 'pid', 'img_url' ), $mod, $md_pre = 'none', $mt_pre = 'og' );

			$form_rows = array(
				'pro-feature-msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>',
				),
				'subsection_opengraph' => array(
					'td_class' => 'subsection top',
					'header'   => 'h4',
					'label'    => _x( 'All Social WebSites / Open Graph', 'metabox title', 'wpsso' ),
				),
				'subsection_priority_image' => array(
					'td_class' => 'subsection top',
					'header'   => 'h5',
					'label'    => _x( 'Priority Image Information', 'metabox title', 'wpsso' ),
				),
				'og_img_max' => $mod[ 'is_post' ] ? array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_img_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_max',
					'content'  => $form->get_no_select( 'og_img_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				) : '',	// Placeholder if not a post module.
				'og_img_dimensions' => array(
					'tr_class' => $form->get_css_class_hide_img_dim( 'basic', 'og_img' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Image Dimensions', 'option label', 'wpsso' ),
					'tooltip'  => 'og_img_dimensions',
					'content'  => $form->get_no_input_image_dimensions( 'og_img', true ),	// $use_opts is true.
				),
				'og_img_id' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_id',
					'content'  => $form->get_no_input_image_upload( 'og_img', $media_info[ 'pid' ], true ),
				),
				'og_img_url' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_url',
					'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], 'wide' ),
				),
				'subsection_priority_video' => array(
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Priority Video Information', 'metabox title', 'wpsso' )
				),
				'og_vid_prev_img' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_prev_img' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Include Preview Images', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_prev_img',
					'content'  => $form->get_no_checkbox( 'og_vid_prev_img' ),
				),
				'og_vid_max' => $mod[ 'is_post' ] ? array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Videos', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_max',
					'content'  => $form->get_no_select( 'og_vid_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				) : '',	// Placeholder if not a post module.
				'og_vid_dimensions' => array(
					'tr_class' => $form->get_css_class_hide_vid_dim( 'basic', 'og_vid' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Dimensions', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_dimensions',
					'content'  => $form->get_no_input_video_dimensions( 'og_vid' ),
				),
				'og_vid_embed' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Embed HTML', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_embed',
					'content'  => $form->get_no_textarea_value( '' ),	// The Standard plugin does not include video modules.
				),
				'og_vid_url' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'or a Video URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_url',
					'content'  => $form->get_no_input_value( '', 'wide' ),	// The Standard plugin does not include video modules.
				),
				'og_vid_title' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_title' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Name / Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_title',
					'content'  => $form->get_no_input_value( '', 'wide' ),	// The Standard plugin does not include video modules.
				),
				'og_vid_desc' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_desc' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_desc',
					'content'  => $form->get_no_textarea_value( '' ),	// The Standard plugin does not include video modules.
				),
			);

			/**
			 * Twitter Card
			 */
			list( $card_type, $card_label, $size_name, $tc_pre ) = $this->p->tc->get_card_info( $mod, $head );

			if ( ! empty( $size_name ) ) {

				$media_info = $this->p->og->get_media_info( $size_name,
					array( 'pid', 'img_url' ), $mod, $md_pre = 'og', $mt_pre = 'og' );
	
				$tc_tr_class = $form->in_options( '/^' . $tc_pre . '_img_/', true ) ? '' : 'hide_in_basic';	// Hide unless a custom twitter card image exists.

				$form_rows[ 'subsection_tc' ] = array(
					'tr_class' => $tc_tr_class,
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => $card_label,
				);

				$form_rows[ $tc_pre . '_img_dimensions' ] = array(
					'tr_class' => $form->get_css_class_hide_img_dim( 'basic', $tc_pre . '_img' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Image Dimensions', 'option label', 'wpsso' ),
					'tooltip'  => $tc_pre . '_img_dimensions',
					'content'  => $form->get_no_input_image_dimensions( $tc_pre . '_img', true ),	// $use_opts is true.
				);

				$form_rows[ $tc_pre . '_img_id' ] = array(
					'tr_class' => $tc_tr_class,
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-' . $tc_pre . '_img_id',
					'content'  => $form->get_no_input_image_upload( $tc_pre . '_img', $media_info[ 'pid' ], true ),
				);

				$form_rows[ $tc_pre . '_img_url' ] = array(
					'tr_class' => $tc_tr_class,
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-' . $tc_pre . '_img_url',
					'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], 'wide' ),
				);
			}

			/**
			 * Structured Data / Schema Markup / Pinterest
			 */
			$media_info = $this->p->og->get_media_info( $this->p->lca . '-schema',
				array( 'pid', 'img_url' ), $mod, $md_pre = 'og', $mt_pre = 'og' );
	
			$schema_tr_class = $form->in_options( '/^schema_img_/', true ) ? '' : 'hide_in_basic';	// Hide unless a custom schema image exists.

			$form_rows[ 'subsection_schema' ] = array(
				'tr_class' => $schema_tr_class,
				'td_class' => 'subsection', 'header' => 'h4',
				'label'    => _x( 'Structured Data / Schema Markup / Pinterest', 'metabox title', 'wpsso' )
			);

			if ( $mod[ 'is_post' ] ) {
				$form_rows[ 'schema_img_max' ] = array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'schema_img_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_img_max',
					'content'  => $form->get_no_select( 'schema_img_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				);
			}

			$form_rows[ 'schema_img_dimensions' ] = array(
				'tr_class' => $form->get_css_class_hide_img_dim( 'basic', 'schema_img' ),
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image Dimensions', 'option label', 'wpsso' ),
				'tooltip'  => 'schema_img_dimensions',
				'content'  => $form->get_no_input_image_dimensions( 'schema_img', true ),	// $use_opts is true.
			);

			$form_rows[ 'schema_img_id' ] = array(
				'tr_class' => $schema_tr_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-schema_img_id',
				'content'  => $form->get_no_input_image_upload( 'schema_img', $media_info[ 'pid' ], true ),
			);

			$form_rows[ 'schema_img_url' ] = array(
				'tr_class' => $schema_tr_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-schema_img_url',
				'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], 'wide' ),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head, $mod );
		}
	}
}
