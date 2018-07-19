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

			$dots      = '...';
			$r_cache   = true;
			$do_encode = true;
			$og_types  = $this->p->og->get_og_types_select( true ); // $add_none is true.

			/**
			 * The 'add_link_rel_canonical' and 'add_meta_name_description' options will be empty if an SEO plugin is detected.
			 */
			$add_link_rel_canon = empty( $this->p->options['add_link_rel_canonical'] ) ? false : true;
			$add_meta_name_desc = empty( $this->p->options['add_meta_name_description'] ) ? false : true;
			$add_meta_name_desc = apply_filters( $this->p->lca.'_add_meta_name_description', $add_meta_name_desc, $mod );

			$sharing_url   = $this->p->util->get_sharing_url( $mod, false );	// $add_page is false.
			$canonical_url = $this->p->util->get_canonical_url( $mod, false );	// $add_page is false.

			$og_title_max_len    = $this->p->options['og_title_len'];
			$og_desc_max_len     = $this->p->options['og_desc_len'];
			$schema_desc_max_len = $this->p->options['schema_desc_len'];
			$seo_desc_max_len    = $this->p->options['seo_desc_len'];
			$tc_desc_max_len     = $this->p->options['tc_desc_len'];

			$def_og_title    = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $r_cache, false, $do_encode, 'none' );
			$def_og_desc     = $this->p->page->get_description( $og_desc_max_len, $dots, $mod, $r_cache, true, $do_encode, 'none' );
			$def_seo_desc    = $add_meta_name_desc ? $this->p->page->get_description( $seo_desc_max_len, $dots, $mod, $r_cache, false ) : '';
			$def_tc_desc     = $this->p->page->get_description( $tc_desc_max_len, $dots, $mod, $r_cache );
			$def_schema_desc = $this->p->page->get_description( $schema_desc_max_len, $dots, $mod, $r_cache, false, $do_encode, array( 'seo_desc', 'og_desc' ) );

			if ( empty( $this->p->cf['plugin']['wpssojson']['version'] ) ) {
				$json_info = $this->p->cf['plugin']['wpssojson'];
				$json_msg_transl = '<p class="status-msg smaller">'.
					sprintf( __( 'Activate the %s add-on for additional Schema markup features and options.',
						'wpsso' ), '<a href="'.$json_info['url']['home'].'">'.$json_info['short'].'</a>' ).'</p>';
			} else {
				$json_msg_transl = '';
			}

			$seo_msg_transl = __( 'This option is disabled (the "%1$s" head tag is disabled or an SEO plugin was detected).', 'wpsso' );

			$table_rows[] = '<td colspan="2">' . 
				$this->p->msgs->get( 'pro-about-msg-post-text', array( 'post_type' => $mod['post_type'] ) ) . 
				$this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$form_rows = array(
				'og_type' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_type' ),
					'label' => _x( 'Open Graph Type', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'post-og_type', 'td_class' => 'blank',
					'content' => $form->get_select( 'og_type', $og_types,
						'og_type', '', true, false, true, 'unhide_rows' ) .
							$this->p->msgs->get( 'pro-select-msg' ),
				),
				'og_art_section' => array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'label' => _x( 'Article Topic', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'post-og_art_section', 'td_class' => 'blank',
					'content' => $form->get_select( 'og_art_section', $this->p->util->get_article_topics() ) .
						$this->p->msgs->get( 'pro-select-msg' ),
				),
				'og_title' => array(
					'no_auto_draft' => true,
					'label' => _x( 'Default Title', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-og_title', 'td_class' => 'blank',
					'content' => $form->get_no_input_value( $def_og_title, 'wide' ),
				),
				'og_desc' => array(
					'no_auto_draft' => true,
					'label' => _x( 'Default Description', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'post-og_desc', 'td_class' => 'blank',
					'content' => $form->get_no_textarea_value( $def_og_desc, '', '', $og_desc_max_len ),
				),
				'seo_desc' => array(
					'no_auto_draft' => true,
					'tr_class' => ( $add_meta_name_desc ? '' : 'hide_in_basic' ), // Always hide if head tag is disabled.
					'label' => _x( 'Search Description', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-seo_desc', 'td_class' => 'blank',
					'content' => $form->get_no_textarea_value( $def_seo_desc, '', '', $seo_desc_max_len ) .
						( $add_meta_name_desc ? '' : '<p class="status-msg smaller">'.
							sprintf( $seo_msg_transl, 'meta name description' ).'</p>' ),
				),
				'tc_desc' => array(
					'no_auto_draft' => true,
					'label' => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-tc_desc', 'td_class' => 'blank',
					'content' => $form->get_no_textarea_value( $def_tc_desc, '', '', $tc_desc_max_len ),
				),
				'sharing_url' => array(
					'no_auto_draft' => ( $mod['post_type'] === 'attachment' ? false : true ),
					'tr_class' => $form->get_css_class_hide( 'basic', 'sharing_url' ),
					'label' => _x( 'Sharing URL', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-sharing_url', 'td_class' => 'blank',
					'content' => $form->get_no_input_value( $sharing_url, 'wide' ),
				),
				'canonical_url' => array(
					'no_auto_draft' => ( $mod['post_type'] === 'attachment' ? false : true ),
					'tr_class' => ( $add_link_rel_canon ? $form->get_css_class_hide( 'basic', 'canonical_url' ) : 'hide_in_basic' ),
					'label' => _x( 'Canonical URL', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-canonical_url', 'td_class' => 'blank',
					'content' => $form->get_no_input_value( $canonical_url, 'wide' ) .
						( $add_link_rel_canon ? '' : '<p class="status-msg smaller">'.
							sprintf( $seo_msg_transl, 'link rel canonical' ).'</p>' ),
				),
				'product_avail' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'label' => _x( 'Product Availability', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_avail', 'td_class' => 'blank',
					'content' => $form->get_no_select( 'product_avail',
						$this->p->cf['form']['item_availability'] ),
				),
				'product_brand' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'label' => _x( 'Product Brand', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_brand', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_brand', '', '', true ),	// $placeholder is true for default value.
				),
				'product_color' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'label' => _x( 'Product Color', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_color', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_color', '', '', true ),	// $placeholder is true for default value.
				),
				'product_condition' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'label' => _x( 'Product Condition', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_condition', 'td_class' => 'blank',
					'content' => $form->get_no_select( 'product_condition', $this->p->cf['form']['item_condition'] ),
				),
				'product_material' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'label' => _x( 'Product Material', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_material', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_material', '', '', true ),	// $placeholder is true for default value.
				),
				'product_price' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'label' => _x( 'Product Price', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_price', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_price', '', '', true ).' '.	// $placeholder is true for default value.
						$form->get_no_select( 'product_currency', SucomUtil::get_currency_abbrev(), 'currency' ),
				),
				'product_size' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'label' => _x( 'Product Size', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_size', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_size', '', '', true ),	// $placeholder is true for default value.
				),
				'product_gender' => array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'label' => _x( 'Product Target Gender', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_gender', 'td_class' => 'blank',
					'content' => $form->get_no_select( 'product_gender', $this->p->cf['form']['audience_gender'] ),
				),
				'subsection_schema' => array(
					'td_class' => 'subsection', 'header' => 'h4',
					'label' => _x( 'Structured Data / Schema Markup', 'metabox title', 'wpsso' )
				),
				'schema_desc' => array(
					'no_auto_draft' => true,
					'label' => _x( 'Schema Description', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-schema_desc', 'td_class' => 'blank',
					'content' => $form->get_no_textarea_value( $def_schema_desc, '', '', $schema_desc_max_len ).
						$json_msg_transl,
				),
			);

			$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to update this value.',
				'wpsso' ), SucomUtil::titleize( $mod['post_type'] ) );

			return $form->get_md_form_rows( $table_rows, $form_rows, $head, $mod, $auto_draft_msg );
		}
	}
}
