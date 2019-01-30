<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoGplAdminPost' ) ) {

	class WpssoGplAdminPost {

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'post_edit_rows' => 4,
			) );
		}

		public function filter_post_edit_rows( $table_rows, $form, $head, $mod ) {

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

			$sharing_url   = $this->p->util->get_sharing_url( $mod, $add_page = false );
			$canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = false );

			$og_title_max_len    = $this->p->options[ 'og_title_max_len' ];
			$og_desc_max_len     = $this->p->options[ 'og_desc_max_len' ];
			$seo_desc_max_len    = $this->p->options[ 'seo_desc_max_len' ];
			$tc_desc_max_len     = $this->p->options[ 'tc_desc_max_len' ];
			$schema_desc_max_len = $this->p->options[ 'schema_desc_max_len' ];
			$schema_desc_md_key  = array( 'seo_desc', 'og_desc' );

			$def_og_type     = $this->p->og->get_mod_og_type( $mod, $get_type_ns = false, $use_mod_opts = false );
			$def_art_section = $this->p->page->get_article_section( $mod[ 'id' ], $allow_none = true, $use_mod_opts = false );
			$def_og_title    = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, 'none' );
			$def_og_desc     = $this->p->page->get_description( $og_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags, $do_encode, 'none' );
			$def_seo_desc    = $add_meta_name_desc ? $this->p->page->get_description( $seo_desc_max_len, $dots, $mod, $read_cache, $no_hashtags ) : '';
			$def_tc_desc     = $this->p->page->get_description( $tc_desc_max_len, $dots, $mod, $read_cache );
			$def_schema_desc = $this->p->page->get_description( $schema_desc_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, $schema_desc_md_key );

			if ( empty( $this->p->cf[ 'plugin' ][ 'wpssojson' ][ 'version' ] ) ) {

				$json_info       = $this->p->cf[ 'plugin' ][ 'wpssojson' ];
				$json_addon_link = $this->p->util->get_admin_url( 'addons#wpssojson', $json_info[ 'name' ] );
				$json_msg_transl = '<p class="status-msg smaller">' . 
					sprintf( __( 'Activate the %s add-on for additional Schema markup features and options.',
						'wpsso' ), $json_addon_link ) . '</p>';

			} else {
				$json_msg_transl = '';
			}

			$seo_msg_transl = __( 'This option is disabled (the "%1$s" head tag is disabled or an SEO plugin was detected).', 'wpsso' );

			$table_rows[] = '<td colspan="2">' . 
				$this->p->msgs->get( 'pro-about-msg-post-text', array( 'post_type' => $mod[ 'post_type' ] ) ) . 
				$this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$form_rows = array(
				'og_type' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_type' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Open Graph Type', 'option label', 'wpsso' ),
					'tooltip'  => 'post-og_type',
					'content'  => $form->get_select( 'og_type', $og_types, '', '', true, $def_og_type, $def_og_type, 'on_change_unhide_rows' ) .
						$this->p->msgs->get( 'pro-select-msg' ),
				),
				'og_art_section' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Article Topic', 'option label', 'wpsso' ),
					'tooltip'  => 'post-og_art_section',
					'content'  => $form->get_select( 'og_art_section', $art_topics, '', '', false, $def_art_section, $def_art_section ) .
						$this->p->msgs->get( 'pro-select-msg' ),
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
					'tooltip'       => 'post-og_desc',
					'content'       => $form->get_no_textarea_value( $def_og_desc, '', '', $og_desc_max_len ),
				),
				'seo_desc' => array(
					'no_auto_draft' => true,
					'tr_class'      => ( $add_meta_name_desc ? '' : 'hide_in_basic' ), // Always hide if head tag is disabled.
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Search Description', 'option label', 'wpsso' ),
					'tooltip'       => 'meta-seo_desc',
					'content'       => $form->get_no_textarea_value( $def_seo_desc, '', '', $seo_desc_max_len ) .
						( $add_meta_name_desc ? '' : '<p class="status-msg smaller">' . 
							sprintf( $seo_msg_transl, 'meta name description' ) . '</p>' ),
				),
				'tc_desc' => array(
					'no_auto_draft' => true,
					'th_class'      => 'medium',
					'td_class'      => 'blank',
					'label'         => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
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
						( $add_link_rel_canon ? '' : '<p class="status-msg smaller">' . 
							sprintf( $seo_msg_transl, 'link rel canonical' ) . '</p>' ),
				),
				'product_avail' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Availability', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_avail',
					'content'  => $form->get_no_select( 'product_avail', $this->p->cf[ 'form' ][ 'item_availability' ] ),
				),
				'product_brand' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Brand', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_brand',
					'content'  => $form->get_no_input( 'product_brand', '', '', $placeholder = true ),
				),
				'product_color' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Color', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_color',
					'content'  => $form->get_no_input( 'product_color', '', '', $placeholder = true ),
				),
				'product_condition' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Condition', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_condition',
					'content'  => $form->get_no_select( 'product_condition', $this->p->cf[ 'form' ][ 'item_condition' ] ),
				),
				'product_material' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Material', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_material',
					'content'  => $form->get_no_input( 'product_material', '', '', $placeholder = true ),
				),
				'product_sku' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product SKU', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_sku',
					'content'  => $form->get_no_input( 'product_sku', '', '', $placeholder = true ),
				),
				'product_ean' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product EAN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_ean',
					'content'  => $form->get_no_input( 'product_ean', '', '', $placeholder = true ),
				),
				'product_gtin8' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-8', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin8',
					'content'  => $form->get_no_input( 'product_gtin8', '', '', $placeholder = true ),
				),
				'product_gtin12' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-12', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin12',
					'content'  => $form->get_no_input( 'product_gtin12', '', '', $placeholder = true ),
				),
				'product_gtin13' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-13', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin13',
					'content'  => $form->get_no_input( 'product_gtin13', '', '', $placeholder = true ),
				),
				'product_gtin14' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-14', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin14',
					'content'  => $form->get_no_input( 'product_gtin14', '', '', $placeholder = true ),
				),
				'product_isbn' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_isbn',
					'content'  => $form->get_no_input( 'product_isbn', '', '', $placeholder = true ),
				),
				'product_price' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Price', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_price',
					'content'  => $form->get_no_input( 'product_price', '', '', $placeholder = true ) . ' ' .
						$form->get_no_select( 'product_currency', SucomUtil::get_currency_abbrev(), 'currency' ),
				),
				'product_size' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Size', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_size',
					'content'  => $form->get_no_input( 'product_size', '', '', $placeholder = true ),
				),
				'product_gender' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Target Gender', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gender',
					'content'  => $form->get_no_select( 'product_gender', $this->p->cf[ 'form' ][ 'audience_gender' ] ),
				),
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
					'content'       => $form->get_no_textarea_value( $def_schema_desc, '', '', $schema_desc_max_len ) . $json_msg_transl,
				),
			);

			$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to update this value.',
				'wpsso' ), SucomUtil::titleize( $mod[ 'post_type' ] ) );

			return $form->get_md_form_rows( $table_rows, $form_rows, $head, $mod, $auto_draft_msg );
		}
	}
}
