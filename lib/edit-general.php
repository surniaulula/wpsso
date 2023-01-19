<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoEditGeneral' ) ) {

	class WpssoEditGeneral {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * See WpssoAbstractWpMeta->get_document_meta_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'metabox_sso_edit_general_rows' => 4,
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_metabox_sso_edit_general_rows( $table_rows, $form, $head_info, $mod ) {

			$limits           = WpssoConfig::get_input_limits();	// Uses a local cache.
			$og_types         = $this->p->og->get_og_types_select();
			$schema_types     = $this->p->schema->get_schema_types_select();
			$primary_terms    = $this->p->post->get_primary_terms( $mod, $tax_slug = 'category', $output = 'names' );

			/*
			 * Default option values.
			 */
			$def_seo_title = $this->p->page->get_title( $mod, $md_key = '', $max_len = 'seo_title' );
			$def_og_title  = $this->p->page->get_title( $mod, $md_key = 'seo_title', $max_len = 'og_title' );
			$def_tc_title  = $this->p->page->get_title( $mod, $md_key = 'og_title', $max_len = 'tc_title' );

			$def_seo_desc     = $this->p->page->get_description( $mod, $md_key = '', $max_len = 'seo_desc' );
			$def_og_desc      = $this->p->page->get_description( $mod, $md_key = 'seo_desc', $max_len = 'og_desc' );
			$def_pin_img_desc = $this->p->page->get_description( $mod, $md_key = 'og_desc', $max_len = 'pin_img_desc' );
			$def_tc_desc      = $this->p->page->get_description( $mod, $md_key = 'og_desc', $max_len = 'tc_desc' );

			/*
			 * Check for disabled options.
			 */
			$seo_title_msg = $this->p->msgs->maybe_seo_title_disabled();
			$seo_desc_msg  = $this->p->msgs->maybe_seo_tag_disabled( 'meta name description' );
			$pin_img_msg   = $this->p->msgs->maybe_pin_img_disabled();

			$seo_title_disabled = $seo_title_msg ? true : false;
			$seo_desc_disabled  = $seo_desc_msg ? true : false;
			$pin_img_disabled   = $pin_img_msg ? true : false;

			/*
			 * Metabox form rows.
			 */
			$form_rows = array(
				'attach_img_crop' => $mod[ 'is_attachment' ] && wp_attachment_is_image( $mod[ 'id' ] ) ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Crop Area', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_crop_area',
					'content'  => $form->get_input_image_crop_area( 'attach_img', $add_none = true ),
				) : array(),
				'og_schema_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_schema_type',
					'content'  => $form->get_select( 'schema_type', $schema_types, $css_class = 'schema_type', $css_id = 'og_schema_type',
						$is_assoc = true, $is_disabled = false, $selected = false,
							$event_names = array( 'on_focus_load_json', 'on_change_unhide_rows' ),
								$event_args = array(
									'json_var'  => 'schema_types',
									'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
									'is_transl' => true,					// No label translation required.
									'is_sorted' => true,					// No label sorting required.
								)
						),
				),
				'og_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Open Graph Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_type',
					'content'  => $form->get_select( 'og_type', $og_types, $css_class = 'og_type', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = true,
							$event_names = array( 'on_change_unhide_rows' ) ),
				),
				'primary_term_id' => ! empty( $primary_terms ) ? array(	// Show the option if we have post category terms.
					'th_class' => 'medium',
					'label'    => _x( 'Primary Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-primary_term_id',
					'content'  => $form->get_select( 'primary_term_id', $primary_terms,
						$css_class = 'primary_term_id', $css_id = '', $is_assoc = true ),
				) : '',
				'seo_title' => $mod[ 'is_public' ] ? array(
					'tr_class' => $seo_title_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'SEO Title Tag', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-seo_title',
					'content'  => $form->get_input( 'seo_title', $css_class = 'wide', $css_id = '',
						$limits[ 'seo_title' ], $def_seo_title, $seo_title_disabled ) . ' ' . $seo_title_msg,
				) : '',
				'seo_desc' => $mod[ 'is_public' ] ? array(
					'tr_class' => $seo_desc_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'SEO Meta Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-seo_desc',
					'content'  => $form->get_textarea( 'seo_desc', $css_class = '', $css_id = '',
						$limits[ 'seo_desc' ], $def_seo_desc, $seo_desc_disabled ) . ' ' . $seo_desc_msg,
				) : '',
				'og_title' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Social Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_title',
					'content'  => $form->get_input_dep( 'og_title', $css_class = 'wide', $css_id = '',
						$limits[ 'og_title' ], $def_og_title, $is_disabled = false, $dep_id = 'seo_title' ),
				) : '',
				'og_desc' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Social Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_desc',
					'content'  => $form->get_textarea_dep( 'og_desc', $css_class = '', $css_id = '',
						$limits[ 'og_desc' ], $def_og_desc, $is_disabled = false, $dep_id = 'seo_desc' ),
				) : '',
				'pin_img_desc' => $mod[ 'is_public' ] ? array(
					'tr_class' => $pin_img_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Pinterest Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-pin_img_desc',
					'content'  => $form->get_textarea_dep( 'pin_img_desc', $css_class = '', $css_id = '',
						$limits[ 'pin_img_desc' ], $def_pin_img_desc, $pin_img_disabled, $dep_id = 'og_desc' ) . $pin_img_msg,
				) : '',
				'tc_title' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Twitter Card Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-tc_title',
					'content'  => $form->get_input_dep( 'tc_title', $css_class = 'wide', $css_id = '',
						$limits[ 'tc_title' ], $def_tc_title, $is_disabled = false, $dep_id = 'og_title' ),
				) : '',
				'tc_desc' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-tc_desc',
					'content'  => $form->get_textarea_dep( 'tc_desc', $css_class = '', $css_id = '',
						$limits[ 'tc_desc' ], $def_tc_desc, $is_disabled = false, $dep_id = 'og_desc' ),
				) : '',
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
