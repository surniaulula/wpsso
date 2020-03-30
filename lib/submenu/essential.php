<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuEssential' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuEssential extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;
		}

		protected function add_plugin_hooks() {

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows' => 1,
			) );
		}

		/**
		 * Remove the "Change to View" button from the settings page.
		 */
		public function filter_form_button_rows( $form_button_rows ) {

			if ( isset( $form_button_rows[ 0 ] ) ) {
				$form_button_rows[ 0 ] = SucomUtil::preg_grep_keys( '/^change_show_options/', $form_button_rows[ 0 ], $invert = true );
			}

			return $form_button_rows;
		}

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$this->maybe_show_language_notice();

			$metabox_id      = 'general';
			$metabox_title   = _x( 'Essential General Settings', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_general' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );

			$this->p->media->get_default_images( 1, $this->p->lca . '-opengraph', $check_dupes = false );
		}

		public function show_metabox_general() {

			$metabox_id = 'essential';

			$tabs = apply_filters( $this->p->lca . '_essential_general_tabs', array(
				'site'      => _x( 'Site Information', 'metabox tab', 'wpsso' ),
				'facebook'  => _x( 'Facebook', 'metabox tab', 'wpsso' ),
				'google'    => _x( 'Google', 'metabox tab', 'wpsso' ),
				'pinterest' => _x( 'Pinterest', 'metabox tab', 'wpsso' ),
				'twitter'   => _x( 'Twitter', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = $this->p->lca . '_' . $metabox_id . '_' . $tab_key . '_rows';

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $this->get_table_rows( $metabox_id, $tab_key ), $this->form );
			}

			$this->p->util->do_metabox_tabbed( $metabox_id, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$atts_locale = array( 'is_locale' => true );

			$table_rows = array();

			switch ( $metabox_id . '-' . $tab_key ) {

				case 'essential-site':

					$select_exp_secs  = $this->p->util->get_cache_exp_secs( $this->p->lca . '_f_' );	// Default is month in seconds.
					$article_sections = $this->p->util->get_article_sections();

					$table_rows[ 'site_name' ] = '' . 
					$this->form->get_th_html( _x( 'WebSite Name', 'option label', 'wpsso' ), '', 'site_name', $atts_locale ) . 
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'site_name', $this->p->options ),
						'long_name', '', 0, get_bloginfo( 'name', 'display' ) ) . '</td>';

					$table_rows[ 'site_desc' ] = '' . 
					$this->form->get_th_html( _x( 'WebSite Description', 'option label', 'wpsso' ), '', 'site_desc', $atts_locale ) .
					'<td>' . $this->form->get_textarea( SucomUtil::get_key_locale( 'site_desc', $this->p->options ),
						'', '', 0, get_bloginfo( 'description', 'display' ) ) . '</td>';

					$table_rows[ 'og_def_article_section' ] = '' . 
					$this->form->get_th_html( _x( 'Default Article Section', 'option label', 'wpsso' ), '', 'og_def_article_section' ) . 
					'<td>' .
					$this->form->get_select( 'og_def_article_section', $article_sections, $css_class = '', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
							$event_args = array(
								'json_var'  => 'article_sections',
								'exp_secs'  => $select_exp_secs,
								'is_transl' => true,	// No label translation required.
								'is_sorted' => true,	// No label sorting required.
							)
						) .
					'</td>';

					break;

				case 'essential-facebook':

					$table_rows[ 'fb_publisher_url' ] = '' . 
					$this->form->get_th_html( _x( 'Facebook Business Page URL', 'option label', 'wpsso' ), '', 'fb_publisher_url', $atts_locale ) .
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'fb_publisher_url', $this->p->options ), 'wide' ) . '</td>';

					$table_rows[ 'fb_app_id' ] = '' . 
					$this->form->get_th_html( _x( 'Facebook Application ID', 'option label', 'wpsso' ), '', 'fb_app_id' ) . 
					'<td>' . $this->form->get_input( 'fb_app_id', $css_class = 'is_required' ) . '</td>';

					$table_rows[ 'og_def_img_id' ] = '' . 
					$this->form->get_th_html( _x( 'Default / Fallback Image ID', 'option label', 'wpsso' ), '', 'og_def_img_id' ) . 
					'<td>' . $this->form->get_input_image_upload( 'og_def_img' ) . '</td>';

					$table_rows[ 'og_def_img_url' ] = '' . 
					$this->form->get_th_html( _x( 'or Default / Fallback Image URL', 'option label', 'wpsso' ), '', 'og_def_img_url' ) . 
					'<td>' . $this->form->get_input_image_url( 'og_def_img' ) . '</td>';

					break;

				case 'essential-google':

					if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

						return $this->p->msgs->get_schema_disabled_rows( $table_rows, $col_span = 1 );
					}

					$table_rows[ 'schema_logo_url' ] = '' . 
					$this->form->get_th_html( '<a href="https://developers.google.com/structured-data/customize/logos">' . 
					_x( 'Organization Logo URL', 'option label', 'wpsso' ) . '</a>', $css_class = '', $css_id = 'schema_logo_url', $atts_locale ) .
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'schema_logo_url', $this->p->options ),
						$css_class = 'wide is_required' ) . '</td>';

					$table_rows[ 'schema_banner_url' ] = '' . 
					$this->form->get_th_html( '<a href="https://developers.google.com/search/docs/data-types/article#logo-guidelines">' .
					_x( 'Organization Banner URL', 'option label', 'wpsso' ) . '</a>', $css_class = '', $css_id = 'schema_banner_url', $atts_locale ) .
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'schema_banner_url', $this->p->options ),
						$css_class = 'wide is_required' ) . '</td>';

					break;

				case 'essential-pinterest':

					$table_rows[ 'p_publisher_url' ] = '' . 
					$this->form->get_th_html( _x( 'Pinterest Company Page URL', 'option label', 'wpsso' ), '', 'p_publisher_url', $atts_locale ) .
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'p_publisher_url', $this->p->options ), 'wide' ) . '</td>';

					$table_rows[ 'p_add_nopin_media_img_tag' ] = '' . 
					$this->form->get_th_html( _x( 'Add "nopin" to WordPress Media', 'option label', 'wpsso' ), '', 'p_add_nopin_media_img_tag' ) . 
					'<td>' . $this->form->get_checkbox( 'p_add_nopin_media_img_tag' ) .
					' <em>' . _x( 'recommended', 'option comment', 'wpsso' ) . '</em></td>';

					$table_rows[ 'p_add_img_html' ] = '' . 
					$this->form->get_th_html( _x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' ), '', 'p_add_img_html' ) . 
					'<td>' . $this->form->get_checkbox( 'p_add_img_html' ) .
					' <em>' . _x( 'recommended (adds a hidden image in the content)', 'option comment', 'wpsso' ) . '</em></td>';

					break;

				case 'essential-twitter':

					$table_rows[ 'tc_site' ] = '' . 
					$this->form->get_th_html( _x( 'Twitter Business @username', 'option label', 'wpsso' ), '', 'tc_site', $atts_locale ) .
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'tc_site', $this->p->options ) ) . '</td>';

					break;
			}

			return $table_rows;
		}
	}
}
