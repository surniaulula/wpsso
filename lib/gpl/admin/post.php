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

			$og_type = isset( $head['og:type'] ) ? $head['og:type'] : 'website';

			$og_title_max_len    = $this->p->options['og_title_len'];
			$og_desc_max_len     = $this->p->options['og_desc_len'];
			$schema_desc_max_len = $this->p->options['schema_desc_len'];
			$tc_desc_max_len     = $this->p->options['tc_desc_len'];

			$def_og_title    = $this->p->page->get_title( $og_title_max_len, '...', $mod, true, false, true, 'none' );
			$def_og_desc     = $this->p->page->get_description( $og_desc_max_len, '...', $mod, true, true, true, 'none' );
			$def_schema_desc = $this->p->page->get_description( $schema_desc_max_len, '...', $mod );
			$def_seo_desc    = $this->p->page->get_description( $seo_desc_max_len, '...', $mod, true, false );
			$def_tc_desc     = $this->p->page->get_description( $tc_desc_max_len, '...', $mod );

			if ( empty( $this->p->cf['plugin']['wpssojson']['version'] ) ) {
				$json_info = $this->p->cf['plugin']['wpssojson'];
				$schema_desc_msg = '<p class="status-msg smaller">'.
					sprintf( __( 'Activate the %s extension for additional Schema markup features and options.',
						'wpsso' ), '<a href="'.$json_info['url']['home'].'">'.$json_info['short'].'</a>' ).'</p>';
			} else {
				$schema_desc_msg = '';
			}

			$table_rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-about-msg-post-text' ).
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$form_rows = array(
				'og_art_section' => array(
					'tr_class' => ( $og_type === 'article' ? '' : 'hide_in_basic' ), // always hide if og:type is not an article
					'label' => _x( 'Article Topic', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'post-og_art_section', 'td_class' => 'blank',
					'content' => $form->get_no_select( 'og_art_section', $this->p->util->get_article_topics(), '', '', false ),
				),
				'og_title' => array(
					'label' => _x( 'Default Title', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-og_title', 'td_class' => 'blank',
					'no_auto_draft' => true,
					'content' => $form->get_no_input_value( $def_og_title, 'wide' ),
				),
				'og_desc' => array(
					'label' => _x( 'Default Description (Facebook / Open Graph, LinkedIn, Pinterest Rich Pin)', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'post-og_desc', 'td_class' => 'blank',
					'no_auto_draft' => true,
					'content' => $form->get_no_textarea_value( $def_og_desc, '', '', $og_desc_max_len ),
				),
				'seo_desc' => array(
					'tr_class' => ( $this->p->options['add_meta_name_description'] ? '' : 'hide_in_basic' ), // always hide if head tag is disabled
					'label' => _x( 'Google Search / SEO Description', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-seo_desc', 'td_class' => 'blank',
					'no_auto_draft' => true,
					'content' => $form->get_no_textarea_value( $def_seo_desc, '', '', $seo_desc_max_len ),
				),
				'tc_desc' => array(
					'label' => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-tc_desc', 'td_class' => 'blank',
					'no_auto_draft' => true,
					'content' => $form->get_no_textarea_value( $def_tc_desc, '', '', $tc_desc_max_len ),
				),
				'sharing_url' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'sharing_url' ),
					'label' => _x( 'Sharing URL', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-sharing_url', 'td_class' => 'blank',
					'no_auto_draft' => ( $mod['post_type'] === 'attachment' ? false : true ),
					'content' => $form->get_no_input_value( $this->p->util->get_sharing_url( $mod, false ), 'wide' ), // $add_page = false
				),
				'canonical_url' => array(
					'tr_class' => ( $this->p->options['add_link_rel_canonical'] ?                      // maybe hide if head tag is enabled
						$form->get_css_class_hide( 'basic', 'canonical_url' ) : 'hide_in_basic' ), // always hide if head tag is disabled
					'label' => _x( 'Canonical URL', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-canonical_url', 'td_class' => 'blank',
					'no_auto_draft' => ( $mod['post_type'] === 'attachment' ? false : true ),
					'content' => $form->get_no_input_value( $this->p->util->get_canonical_url( $mod, false ), 'wide' ), // $add_page = false
				),
			);

			if ( $og_type === 'product' ) {
				$form_rows['product_avail'] = array(
					'label' => _x( 'Product Availability', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_avail', 'td_class' => 'blank',
					'content' => $form->get_no_select( 'product_avail',
						$this->p->cf['form']['item_availability'] ),
				);
				$form_rows['product_brand'] = array(
					'label' => _x( 'Product Brand', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_brand', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_brand', '', '', true ),	// $placeholder = true for default value
				);
				$form_rows['product_color'] = array(
					'label' => _x( 'Product Color', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_color', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_color', '', '', true ),	// $placeholder = true for default value
				);
				$form_rows['product_condition'] = array(
					'label' => _x( 'Product Condition', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_condition', 'td_class' => 'blank',
					'content' => $form->get_no_select( 'product_condition',
						$this->p->cf['form']['item_condition'] ),
				);
				$form_rows['product_material'] = array(
					'label' => _x( 'Product Material', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_material', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_material', '', '', true ),	// $placeholder = true for default value
				);
				// product price and currency
				$form_rows['product_price'] = array(
					'label' => _x( 'Product Price', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_price', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_price', '', '', true ).' '.	// $placeholder = true for default value
						$form->get_no_select( 'product_currency', SucomUtil::get_currency_abbrev(), 'currency' ),
				);
				$form_rows['product_size'] = array(
					'label' => _x( 'Product Size', 'option label', 'wpsso' ),
					'th_class' => 'medium', 'tooltip' => 'meta-product_size', 'td_class' => 'blank',
					'content' => $form->get_no_input( 'product_size', '', '', true ),	// $placeholder = true for default value
				);
			}

			$form_rows['subsection_schema'] = array(
				'td_class' => 'subsection', 'header' => 'h4',
				'label' => _x( 'Structured Data / Schema Markup', 'metabox title', 'wpsso' )
			);
			$form_rows['schema_desc'] = array(
				'label' => _x( 'Schema Description', 'option label', 'wpsso' ),
				'th_class' => 'medium', 'tooltip' => 'meta-schema_desc', 'td_class' => 'blank',
				'no_auto_draft' => true,
				'content' => $form->get_no_textarea_value( $def_schema_desc, '', '', $schema_desc_max_len ).$schema_desc_msg,
			);

			$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to update this value.',
				'wpsso' ), SucomUtil::titleize( $mod['post_type'] ) );

			return $form->get_md_form_rows( $table_rows, $form_rows, $head, $mod, $auto_draft_msg );
		}
	}
}

