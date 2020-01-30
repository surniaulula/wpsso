<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
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

			$p_img_desc_disabled    = empty( $this->p->options[ 'p_add_img_html' ] ) ? true : false;
			$seo_desc_disabled      = empty( $this->p->options[ 'add_meta_name_description' ] ) ? true : false;
			$canonical_url_disabled = empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? true : false;

			$p_img_desc_msg    = $p_img_desc_disabled ? $this->p->msgs->p_img_desc_disabled() : '';
			$seo_desc_msg      = $seo_desc_disabled ? $this->p->msgs->seo_option_disabled( 'meta name description' ) : '';
			$canonical_url_msg = $canonical_url_disabled ? $this->p->msgs->seo_option_disabled( 'link rel canonical' ) : '';

			/**
			 * Select option arrays.
			 */
			$og_types           = $this->p->og->get_og_types_select( $add_none = true );
			$article_topics     = $this->p->util->get_article_topics();
			$product_categories = $this->p->util->get_product_categories();
			$currencies         = SucomUtil::get_currency_abbrev();
			$schema_types       = $this->p->schema->get_schema_types_select( null, $add_none = false );

			/**
			 * Maximum option lengths.
			 */
			$og_title_max_len    = $this->p->options[ 'og_title_max_len' ];
			$og_desc_max_len     = $this->p->options[ 'og_desc_max_len' ];
			$p_img_desc_max_len  = $this->p->options[ 'p_img_desc_max_len' ];
			$tc_desc_max_len     = $this->p->options[ 'tc_desc_max_len' ];
			$seo_desc_max_len    = $this->p->options[ 'seo_desc_max_len' ];		// Max. Description Meta Tag Length.

			/**
			 * Default option values.
			 */
			$def_og_title      = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, 'none' );
			$def_og_desc       = $this->p->page->get_description( $og_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags, $do_encode, 'none' );
			$def_p_img_desc    = $p_img_desc_disabled ? '' : $this->p->page->get_description( $p_img_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags );
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
					'td_class' => 'blank',
					'label'    => _x( 'Preferred Cropping', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_crop_area',
					'content'  => $form->get_no_input_image_crop_area( 'attach_img', $add_none = true ),
				) : array(),
				'og_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Open Graph Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_type',
					'content'  => $form->get_select( 'og_type', $og_types,
						$css_class = '', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = true, $event_names = array( 'on_change_unhide_rows' ) ),
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
					'content'       => $form->get_no_input_value( $def_og_title, $css_class = 'wide', $css_id = '', $og_title_max_len ),
				),
				'og_desc' => array(
					'no_auto_draft' => true,
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Default Description', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-og_desc',
					'content'       => $form->get_no_textarea_value( $def_og_desc, $css_class = '', $css_id = '', $og_desc_max_len ),
				),
				'p_img_desc' => array(
					'no_auto_draft' => true,
					'tr_class'      => $p_img_desc_disabled ? 'hide_in_basic': '',
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Pinterest Image Description', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-p_img_desc',
					'content'       => $form->get_no_textarea_value( $def_p_img_desc, $css_class = '', $css_id = '', $p_img_desc_max_len ) . ' ' .
						$p_img_desc_msg,
				),
				'tc_desc' => array(
					'no_auto_draft' => true,
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-tc_desc',
					'content'       => $form->get_no_textarea_value( $def_tc_desc, $css_class = '', $css_id = '', $tc_desc_max_len ),
				),
				'seo_desc' => array(
					'no_auto_draft' => true,
					'tr_class'      => $seo_desc_disabled ? 'hide_in_basic' : '',
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Search Description', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-seo_desc',
					'content'       => $form->get_no_textarea_value( $def_seo_desc, $css_class = '', $css_id = '', $seo_desc_max_len ) . ' ' .
						$seo_desc_msg,
				),
				'sharing_url' => array(
					'no_auto_draft' => $mod[ 'post_type' ] === 'attachment' ? false : true,
					'tr_class'      => $form->get_css_class_hide( 'basic', 'sharing_url' ),
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Sharing URL', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-sharing_url',
					'content'       => $form->get_no_input_value( $def_sharing_url, $css_class = 'wide' ),
				),
				'canonical_url' => array(
					'no_auto_draft' => $mod[ 'post_type' ] === 'attachment' ? false : true,
					'tr_class'      => $canonical_url_disabled ? 'hide_in_basic' : $form->get_css_class_hide( 'basic', 'canonical_url' ),
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Canonical URL', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-canonical_url',
					'content'       => $form->get_no_input_value( $def_canonical_url, $css_class = 'wide' ) . ' ' .
						$canonical_url_msg,
				),

				/**
				 * Open Graph Article type.
				 */
				'subsection_og_art' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'header'   => 'h5',
					'label'    => _x( 'Article Information', 'metabox title', 'wpsso' )
				),
				'og_article_topic' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Article Topic', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-article_topic',
					'content'  => $form->get_no_select( 'article_topic', $article_topics, $css_class = '', $css_id = '', $is_assoc = true ),
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
				/* 'og_product_category' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'label'    => _x( 'Product Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_category',
					'content'  => $form->get_no_select( 'product_category', $product_categories, $css_class = 'wide', $css_id = '', $is_assoc = true ),
				), */
				'og_product_brand' => array(		// Open Graph meta tag product:brand.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Brand', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_brand',
					'content'  => $form->get_no_input( 'product_brand', $css_class = '', $css_id = '', $placeholder = true ),
				),
				'og_product_price' => array(		// Open Graph meta tags product:price:amount and product:price:currency.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Price', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_price',
					'content'  => $form->get_no_input( 'product_price', $css_class = 'price', $css_id = '', $placeholder = true ) . ' ' .
						$form->get_no_select( 'product_currency', $currencies, $css_class = 'currency' ),
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
					'content'  => $form->get_no_input( 'product_size', $css_class = '', $css_id = '', $placeholder = true ),
				),
				'og_product_weight_value' => array(	// Open Graph meta tag product:weight:value.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Weight', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_weight_value',
					'content'  => $form->get_no_input( 'product_weight_value', $css_class = '', $css_id = '', $placeholder = true ) .
						WpssoAdmin::get_option_unit_comment( 'product_weight_value' ),
				),
				'og_product_retailer_part_no' => array(	// Open Graph meta tag product:retailer_part_no.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product SKU', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_retailer_part_no',
					'content'  => $form->get_no_input( 'product_retailer_part_no', $css_class = '', $css_id = '', $placeholder = true ),
				),
				'og_product_mfr_part_no' => array(	// Open Graph meta tag product:mfr_part_no.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product MPN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_mfr_part_no',
					'content'  => $form->get_no_input( 'product_mfr_part_no', $css_class = '', $css_id = '', $placeholder = true ),
				),
				'og_product_isbn' => array(		// Open Graph meta tag product:isbn.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_isbn',
					'content'  => $form->get_no_input( 'product_isbn', $css_class = '', $css_id = '', $placeholder = true ),
				),

				/**
				 * All Schema Types
				 */
				'subsection_schema' => array(
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Structured Data / Schema Markup', 'metabox title', 'wpsso' )
				),

				/**
				 * Do not use the 'on_focus_load_json' event name for the Schema Type select (the WPSSO JSON add-on
				 * will remove the event json array and the Schema Types array will appear empty).
				 */
				'schema_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_type',
					'content'  => $form->get_select( 'schema_type', $schema_types,
						$css_class = 'schema_type', $css_id = '', $is_assoc = true, $is_disabled = false, $selected = true ),
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

			if ( SucomUtil::is_auto_draft( $mod ) ) {

				$table_rows[] = '<td>' .
					'<blockquote class="status-info">' .
						'<p class="centered">' . 
							sprintf( __( 'Save a draft version or publish the %s to display these options.', 'wpsso' ),
								SucomUtil::titleize( $mod[ 'post_type' ] ) ) .
						'</p>' .
					'</blockquote>' .
				'</td>';

				return $table_rows;	// Stop here.
			}

			$media_info = $this->p->og->get_media_info( $this->p->lca . '-opengraph',
				array( 'pid', 'img_url' ), $mod, $md_pre = 'none', $mt_pre = 'og' );

			$form_rows = array(
				'info-priority-media' => array(
					'table_row' => empty( $media_info[ 'pid' ] ) ?
						'' : '<td colspan="2">' . $this->p->msgs->get( 'info-priority-media' ) . '</td>',
				),
				'pro-feature-msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>',
				),
				'subsection_opengraph' => array(
					'td_class' => 'subsection top',
					'header'   => 'h4',
					'label'    => _x( 'Facebook / Open Graph / Default Media', 'metabox title', 'wpsso' ),
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
					'tooltip'  => 'og_img_max',	// Use tooltip message from settings.
					'content'  => $form->get_no_select( 'og_img_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				) : '',	// Placeholder if not a post module.
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
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Include Preview Images', 'option label', 'wpsso' ),
					'tooltip'  => 'og_vid_prev_img',	// Use tooltip message from settings.
					'content'  => $form->get_no_checkbox( 'og_vid_prev_img' ) . ' <em>' .
						_x( 'note that video preview images are included first',
							'option comment', 'wpsso' ) . '</em>',
				),
				'og_vid_max' => $mod[ 'is_post' ] ? array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Videos', 'option label', 'wpsso' ),
					'tooltip'  => 'og_vid_max',	// Use tooltip message from settings.
					'content'  => $form->get_no_select( 'og_vid_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				) : '',	// Placeholder if not a post module.
				'og_vid_dimensions' => array(
					'tr_class' => $form->get_css_class_hide_vid_dim( 'basic', 'og_vid' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Size', 'option label', 'wpsso' ),
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
	
				/**
				 * Hide unless a custom twitter card image exists.
				 */
				$tc_row_class = $form->in_options( '/^' . $tc_pre . '_img_/', true ) ? '' : 'hide_in_basic';

				$form_rows[ 'subsection_tc' ] = array(
					'tr_class' => $tc_row_class,
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => $card_label,
				);

				$form_rows[ $tc_pre . '_img_id' ] = array(
					'tr_class' => $tc_row_class,
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-' . $tc_pre . '_img_id',
					'content'  => $form->get_no_input_image_upload( $tc_pre . '_img', $media_info[ 'pid' ], true ),
				);

				$form_rows[ $tc_pre . '_img_url' ] = array(
					'tr_class' => $tc_row_class,
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
	
			$schema_row_class = $form->in_options( '/^schema_img_/', true ) ? '' : 'hide_in_basic';	// Hide unless a custom schema image exists.

			$form_rows[ 'subsection_schema' ] = array(
				'tr_class' => $schema_row_class,
				'td_class' => 'subsection', 'header' => 'h4',
				'label'    => _x( 'Structured Data / Schema Markup / Pinterest', 'metabox title', 'wpsso' )
			);

			if ( $mod[ 'is_post' ] ) {
				$form_rows[ 'schema_img_max' ] = array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'schema_img_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_img_max',	// Use tooltip message from settings.
					'content'  => $form->get_no_select( 'schema_img_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				);
			}

			$form_rows[ 'schema_img_id' ] = array(
				'tr_class' => $schema_row_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-schema_img_id',
				'content'  => $form->get_no_input_image_upload( 'schema_img', $media_info[ 'pid' ], true ),
			);

			$form_rows[ 'schema_img_url' ] = array(
				'tr_class' => $schema_row_class,
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
