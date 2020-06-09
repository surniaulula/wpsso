<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdAdminEdit' ) ) {

	class WpssoStdAdminEdit {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'metabox_sso_edit_rows'  => 4,
				'metabox_sso_media_rows' => 4,
			), -10000 );
		}

		public function filter_metabox_sso_edit_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$product_categories = $this->p->util->get_google_product_categories();
			$currencies         = SucomUtil::get_currency_abbrev();

			/**
			 * Metabox form rows.
			 */
			$form_rows = array(

				/**
				 * Open Graph Product type.
				 */
				'subsection_og_product' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Basic Product Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg' => array(
					'tr_class'  => 'hide_og_type hide_og_type_product',
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'og_product_ecom_msg' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'table_row' => ( empty( $this->p->avail[ 'ecom' ][ 'any' ] ) ? '' :
						'<td colspan="2">' . $this->p->msgs->get( 'pro-ecom-product-msg' ) . '</td>' ),
				),
				'og_product_category' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'label'    => _x( 'Product Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_category',
					'content'  => $form->get_no_select( 'product_category', $product_categories, $css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
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
					'content'  => $form->get_no_input( 'product_material', $css_class = '', $css_id = '', $placeholder = true ),
				),
				'og_product_color' => array(		// Open Graph meta tag product:color.
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Color', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_color',
					'content'  => $form->get_no_input( 'product_color', $css_class = '', $css_id = '', $placeholder = true ),
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
					'label'    => _x( 'Schema JSON-LD Markup / Rich Results', 'metabox title', 'wpsso' )
				),
				'wpssojson_addon_msg' => array(
					'table_row' => ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ?
						'<td colspan="2">' . $this->p->msgs->more_schema_options() . '</td>' : '' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_media_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			$form_rows = array(
				'subsection_priority_video' => array(
					'td_class'     => 'subsection',
					'header'       => 'h5',
					'label'        => _x( 'Priority Video Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature_video_api( 'wpsso' ) . '</td>',
				),
				'og_vid_prev_img' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Include Preview Images', 'option label', 'wpsso' ),
					'tooltip'  => 'og_vid_prev_img',	// Use tooltip message from settings.
					'content'  => $form->get_no_checkbox( 'og_vid_prev_img' ) . $this->p->msgs->preview_images_first(),
				),
				'og_vid_max' => $mod[ 'is_post' ] ? array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Videos', 'option label', 'wpsso' ),
					'tooltip'  => 'og_vid_max',	// Use tooltip message from settings.
					'content'  => $form->get_no_select( 'og_vid_max', range( 0, $max_media_items ), $css_class = 'medium' ),
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
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),	// The Standard plugin does not include video modules.
				),
				'og_vid_title' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_title' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Name / Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_title',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),	// The Standard plugin does not include video modules.
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
			 * Pinterest Pin It.
			 */
			$p_img_disabled = empty( $this->p->options[ 'p_add_img_html' ] ) ? true : false;
			$p_img_msg      = $p_img_disabled ? $this->p->msgs->p_img_disabled() : '';

			$media_info = $this->p->og->get_media_info( $this->p->lca . '-pinterest',
				array( 'pid', 'img_url' ), $mod, $md_pre = array( 'schema', 'og' ), $mt_pre = 'og' );

			$row_class = ! $p_img_disabled && $form->in_options( '/^p_img_/', $is_preg = true ) ? '' : 'hide_in_basic';

			$form_rows[ 'subsection_pinterest' ] = array(
				'tr_class' => $row_class,
				'td_class' => 'subsection',
				'header'   => 'h4',
				'label'    => _x( 'Pinterest Pin It', 'metabox title', 'wpsso' )
			);

			$form_rows[ 'p_img_id' ] = array(
				'tr_class' => $row_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-p_img_id',
				'content'  => $form->get_no_input_image_upload( 'p_img', $media_info[ 'pid' ], true ),
			);

			$form_rows[ 'p_img_url' ] = array(
				'tr_class' => $row_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-p_img_url',
				'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], $css_class = 'wide' ) . ' ' . $p_img_msg,
			);

			/**
			 * Twitter Card
			 */
			list( $card_type, $card_label, $size_name, $tc_prefix ) = $this->p->tc->get_card_info( $mod, $head_info );

			/**
			 * App and Player cards do not have a $size_name.
			 *
			 * Only show custom image options for the Summary and Summary Large Image cards. 
			 */
			if ( ! empty( $size_name ) ) {

				$media_info = $this->p->og->get_media_info( $size_name,
					array( 'pid', 'img_url' ), $mod, $md_pre = 'og', $mt_pre = 'og' );
	
				/**
				 * Hide unless a custom twitter card image exists.
				 */
				$row_class = $form->in_options( '/^' . $tc_prefix . '_img_/', $is_preg = true ) ? '' : 'hide_in_basic';

				$form_rows[ 'subsection_tc' ] = array(
					'tr_class' => $row_class,
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => $card_label,
				);

				$form_rows[ $tc_prefix . '_img_id' ] = array(
					'tr_class' => $row_class,
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-' . $tc_prefix . '_img_id',
					'content'  => $form->get_no_input_image_upload( $tc_prefix . '_img', $media_info[ 'pid' ], true ),
				);

				$form_rows[ $tc_prefix . '_img_url' ] = array(
					'tr_class' => $row_class,
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-' . $tc_prefix . '_img_url',
					'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], $css_class = 'wide' ),
				);
			}

			/**
			 * Schema JSON-LD Markup / Rich Results.
			 */
			$row_class = $form->in_options( '/^schema_img_/', $is_preg = true ) ? '' : 'hide_in_basic';

			$form_rows[ 'subsection_schema' ] = array(
				'tr_class' => $row_class,
				'td_class' => 'subsection',
				'header'   => 'h4',
				'label'    => _x( 'Schema JSON-LD Markup / Rich Results', 'metabox title', 'wpsso' )
			);

			if ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {
				$form_rows[ 'wpssojson_addon_msg' ] = array(
					'tr_class'  => $row_class,
					'table_row' => '<td colspan="2">' . $this->p->msgs->more_schema_options() . '</td>',
				);
			}

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
