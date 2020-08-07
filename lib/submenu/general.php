<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuGeneral' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuGeneral extends WpssoAdmin {

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

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$this->maybe_show_language_notice();

			$metabox_id      = 'og';
			$metabox_title   = _x( 'General Settings', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_' . $metabox_id ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );

			$metabox_id      = 'social_and_search';
			$metabox_title   = _x( 'Social and Search Sites', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_' . $metabox_id ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );

			$this->p->media->get_default_images( 1, $this->p->lca . '-opengraph', $check_dupes = false );
		}

		public function show_metabox_og() {

			$metabox_id = 'og';

			$tabs = apply_filters( $this->p->lca . '_general_' . $metabox_id . '_tabs', array(
				'site'    => _x( 'Site Information', 'metabox tab', 'wpsso' ),
				'content' => _x( 'Content and Text', 'metabox tab', 'wpsso' ),
				'author'  => _x( 'Authorship', 'metabox tab', 'wpsso' ),
				'images'  => _x( 'Images', 'metabox tab', 'wpsso' ),
				'videos'  => _x( 'Videos', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = $this->p->lca . '_' . $metabox_id . '_' . $tab_key . '_rows';

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $this->get_table_rows( $metabox_id, $tab_key ), $this->form );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_social_and_search() {

			$metabox_id = 'pub';

			$tabs = apply_filters( $this->p->lca . '_general_' . $metabox_id . '_tabs', array(
				'facebook'     => _x( 'Facebook', 'metabox tab', 'wpsso' ),
				'google'       => _x( 'Google', 'metabox tab', 'wpsso' ),
				'pinterest'    => _x( 'Pinterest', 'metabox tab', 'wpsso' ),
				'twitter'      => _x( 'Twitter', 'metabox tab', 'wpsso' ),
				'other_social' => _x( 'Other Sites', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {
				
				$filter_name = $this->p->lca . '_' . $metabox_id . '_' . $tab_key . '_rows';

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $this->get_table_rows( $metabox_id, $tab_key ), $this->form );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			$user_contacts = $this->p->user->get_form_contact_fields();

			switch ( $metabox_id . '-' . $tab_key ) {

				case 'og-site':

					$def_site_name = get_bloginfo( 'name' );
					$def_site_desc = get_bloginfo( 'description' );

					$select_exp_secs    = $this->p->util->get_cache_exp_secs( $this->p->lca . '_f_' );	// Default is month in seconds.
					$article_sections   = $this->p->util->get_article_sections();
					$product_categories = $this->p->util->get_google_product_categories();

					$table_rows[ 'site_name' ] = '' . 
					$this->form->get_th_html_locale( _x( 'WebSite Name', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'site_name' ) . 
					'<td>' . $this->form->get_input_locale( 'site_name', $css_class = 'long_name', $css_id = '',
						$len = 0, $def_site_name ) . '</td>';

					$table_rows[ 'site_desc' ] = '' . 
					$this->form->get_th_html_locale( _x( 'WebSite Description', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'site_desc' ) . 
					'<td>' . $this->form->get_textarea_locale( 'site_desc', $css_class = '', $css_id = '',
						$len = 0, $def_site_desc ) . '</td>';

					$table_rows[ 'og_def_article_section' ] = '' . 
					$this->form->get_th_html( _x( 'Default Article Section', 'option label', 'wpsso' ), 
						$css_class = '', $css_id = 'og_def_article_section' ) . 
					'<td>' .
					$this->form->get_select( 'og_def_article_section', $article_sections, $css_class = '', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
							$event_args = array(
								'json_var'  => 'article_sections',
								'exp_secs'  => $select_exp_secs,	// Create and read from a javascript URL.
								'is_transl' => true,			// No label translation required.
								'is_sorted' => true,			// No label sorting required.
							)
						) .
					'</td>';

					$table_rows[ 'og_def_product_category' ] = '' . 
					$this->form->get_th_html( _x( 'Default Product Category', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_def_product_category' ) . 
					'<td>' .
					$this->form->get_select( 'og_def_product_category', $product_categories, $css_class = 'wide', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
							$event_args = array(
								'json_var'  => 'product_categories',
								'exp_secs'  => $select_exp_secs,	// Create and read from a javascript URL.
								'is_transl' => true,			// No label translation required.
								'is_sorted' => true,			// No label sorting required.
							)
						) .
					'</td>';

					break;

				case 'og-content':

					$table_rows[ 'og_title_sep' ] = '' . 
					$this->form->get_th_html( _x( 'Title Separator', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_title_sep' ) . 
					'<td>' . $this->form->get_input( 'og_title_sep', 'short' ) . '</td>';

					$table_rows[ 'og_title_max_len' ] = '' . 
					$this->form->get_th_html( _x( 'Maximum Title Length', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_title_max_len' ) . 
					'<td>' . 
					$this->form->get_input( 'og_title_max_len', $css_class = 'chars' ) . ' ' . 
					_x( 'characters or less (hard limit), and warn at', 'option comment', 'wpsso' ) . ' ' . 
					$this->form->get_input( 'og_title_warn_len', $css_class = 'chars' ) . ' ' . 
					_x( 'characters (soft limit)', 'option comment', 'wpsso' ) . 
					'</td>';

					$table_rows[ 'og_desc_max_len' ] = '' . 
					$this->form->get_th_html( _x( 'Maximum Description Length', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_desc_max_len' ) . 
					'<td>' . 
					$this->form->get_input( 'og_desc_max_len', $css_class = 'chars' ) . ' ' . 
					_x( 'characters or less (hard limit), and warn at', 'option comment', 'wpsso' ) . ' ' . 
					$this->form->get_input( 'og_desc_warn_len', $css_class = 'chars' ) . ' ' . 
					_x( 'characters (soft limit)', 'option comment', 'wpsso' ) . 
					'</td>';

					$table_rows[ 'og_desc_hashtags' ] = $this->form->get_tr_hide( 'basic', 'og_desc_hashtags' ) . 
					$this->form->get_th_html( _x( 'Add Hashtags to Descriptions', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_desc_hashtags' ) . 
					'<td>' . $this->form->get_select( 'og_desc_hashtags', range( 0, $this->p->cf[ 'form' ][ 'max_hashtags' ] ), 'short', '', true ) . ' ' . 
						_x( 'tag names', 'option comment', 'wpsso' ) . '</td>';

					break;

				case 'og-author':

					$table_rows[ 'og_author_field' ] = '' . 
					$this->form->get_th_html( _x( 'Author Profile URL Field', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_author_field' ) . 
					'<td>' . $this->form->get_select( 'og_author_field', $user_contacts ) . '</td>';

					break;

				case 'og-images':

					$table_rows[ 'og_img_max' ] = $this->form->get_tr_hide( 'basic', 'og_img_max' ) . 
					$this->form->get_th_html( _x( 'Maximum Images to Include', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_img_max' ) . 
					'<td>' .
					$this->form->get_select( 'og_img_max', range( 0, $max_media_items ), 'short', '', true ) . 
					$this->p->msgs->maybe_preview_images_first() .
					'</td>';

					$table_rows[ 'og_img' ] = $this->form->get_tr_hide_img_dim( 'basic', 'og_img' ). 
					$this->form->get_th_html( _x( 'Open Graph Image Size', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'og_img' ) . '</td>';

					$table_rows[ 'og_def_img_id' ] = '' . 
					$this->form->get_th_html( _x( 'Default / Fallback Image ID', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_def_img_id' ) . 
					'<td>' . $this->form->get_input_image_upload( 'og_def_img' ) . '</td>';

					$table_rows[ 'og_def_img_url' ] = '' . 
					$this->form->get_th_html( _x( 'or Default / Fallback Image URL', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'og_def_img_url' ) . 
					'<td>' . $this->form->get_input_image_url( 'og_def_img' ) . '</td>';

					break;

				case 'og-videos':

					break;

				case 'pub-facebook':

					$table_rows[ 'fb_publisher_url' ] = '' . 
					$this->form->get_th_html_locale( _x( 'Facebook Business Page URL', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'fb_publisher_url' ) . 
					'<td>' . $this->form->get_input_locale( 'fb_publisher_url', $css_class = 'wide' ) . '</td>';

					$table_rows[ 'fb_app_id' ] = '' . 
					$this->form->get_th_html( _x( 'Facebook Application ID', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'fb_app_id' ) . 
					'<td>' . $this->form->get_input( 'fb_app_id', $css_class = 'is_required' ) . '</td>';

					$table_rows[ 'fb_admins' ] = $this->form->get_tr_hide( 'basic', 'fb_admins' ) . 
					$this->form->get_th_html( _x( 'or Facebook Admin Username(s)', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'fb_admins' ) . 
					'<td>' . $this->form->get_input( 'fb_admins' ) . '</td>';

					$table_rows[ 'fb_locale' ] = $this->form->get_tr_hide( 'basic', 'fb_locale' ) . 
					$this->form->get_th_html_locale( _x( 'Alternate Facebook Locale', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'fb_locale' ) . 
					'<td>' . $this->form->get_select_locale( 'fb_locale', SucomUtil::get_pub_lang( 'facebook' ) ) . '</td>';

					break;

				case 'pub-google':

					if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

						return $this->p->msgs->get_schema_disabled_rows( $table_rows, $col_span = 1 );
					}

					$table_rows[ 'g_site_verify' ] = '' .
					$this->form->get_th_html( _x( 'Google Website Verification ID', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'g_site_verify' ) . 
					'<td>' . $this->form->get_input( 'g_site_verify', 'api_key' ) . '</td>';

					$this->add_schema_item_props_table_rows( $table_rows, $this->form );

					$table_rows[ 'seo_desc_max_len' ] = $this->form->get_tr_hide( 'basic', 'seo_desc_max_len' ) . 
					$this->form->get_th_html( _x( 'Max. Description Meta Tag Length', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'seo_desc_max_len' ) . 
					'<td>' . $this->form->get_input( 'seo_desc_max_len', $css_class = 'chars' ) . ' ' .
					_x( 'characters or less', 'option comment', 'wpsso' ) . '</td>';

					break;

				case 'pub-pinterest':

					$table_rows[ 'p_site_verify' ] = '' .
					$this->form->get_th_html( _x( 'Pinterest Website Verification ID', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'p_site_verify' ) . 
					'<td>' . $this->form->get_input( 'p_site_verify', 'api_key' ) . '</td>';

					$table_rows[ 'p_publisher_url' ] = '' . 
					$this->form->get_th_html_locale( _x( 'Pinterest Company Page URL', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'p_publisher_url' ) . 
					'<td>' . $this->form->get_input_locale( 'p_publisher_url', $css_class = 'wide' ) . '</td>';

					$table_rows[ 'p_add_nopin_header_img_tag' ] = $this->form->get_tr_hide( 'basic', 'p_add_nopin_header_img_tag' ) . 
					$this->form->get_th_html( _x( 'Add "nopin" to Site Header Image', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'p_add_nopin_header_img_tag' ) . 
					'<td>' . $this->form->get_checkbox( 'p_add_nopin_header_img_tag' ) .
					' <em>' . _x( 'recommended', 'option comment', 'wpsso' ) . '</em></td>';

					$table_rows[ 'p_add_nopin_media_img_tag' ] = '' . 
					$this->form->get_th_html( _x( 'Add "nopin" to WordPress Media', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'p_add_nopin_media_img_tag' ) . 
					'<td>' . $this->form->get_checkbox( 'p_add_nopin_media_img_tag' ) .
					' <em>' . _x( 'recommended', 'option comment', 'wpsso' ) . '</em></td>';

					$table_rows[ 'p_add_img_html' ] = '' . 
					$this->form->get_th_html( _x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'p_add_img_html' ) . 
					'<td>' . $this->form->get_checkbox( 'p_add_img_html' ) .
					' <em>' . _x( 'recommended (adds a hidden image in the content)', 'option comment', 'wpsso' ) . '</em></td>';

					$table_rows[ 'p_img_desc_max_len' ] = $this->form->get_tr_hide( 'basic', 'p_img_desc_max_len' ) . 
					$this->form->get_th_html( _x( 'Maximum Image Description Length', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'p_img_desc_max_len' ) . 
					'<td>' .
					$this->form->get_input( 'p_img_desc_max_len', $css_class = 'chars' ) . ' ' . 
					_x( 'characters or less (hard limit), and warn at', 'option comment', 'wpsso' ) . ' ' . 
					$this->form->get_input( 'p_img_desc_warn_len', $css_class = 'chars' ) . ' ' . 
					_x( 'characters (soft limit)', 'option comment', 'wpsso' ) . 
					'</td>';

					break;

				case 'pub-twitter':

					$tc_types = array(
						'summary'             => _x( 'Summary', 'option value', 'wpsso' ),
						'summary_large_image' => _x( 'Summary Large Image', 'option value', 'wpsso' ),
					);

					$table_rows[ 'tc_site' ] = '' . 
					$this->form->get_th_html_locale( _x( 'Twitter Business @username', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'tc_site' ) . 
					'<td>' . $this->form->get_input_locale( 'tc_site' ) . '</td>';

					$table_rows[ 'tc_desc_max_len' ] = $this->form->get_tr_hide( 'basic', 'tc_desc_max_len' ) . 
					$this->form->get_th_html( _x( 'Maximum Description Length', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'tc_desc_max_len' ) . 
					'<td>' . $this->form->get_input( 'tc_desc_max_len', $css_class = 'chars' ) . ' ' . 
					_x( 'characters or less', 'option comment', 'wpsso' ) . '</td>';

					$table_rows[ 'tc_type_singular' ] = $this->form->get_tr_hide( 'basic', 'tc_type_singular' ) . 
					$this->form->get_th_html( _x( 'Twitter Card for Post / Page Image', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'tc_type_singular' ) . 
					'<td>' . $this->form->get_select( 'tc_type_singular', $tc_types ) . '</td>';

					$table_rows[ 'tc_type_default' ] = $this->form->get_tr_hide( 'basic', 'tc_type_default' ) . 
					$this->form->get_th_html( _x( 'Twitter Card Type by Default', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'tc_type_default' ) . 
					'<td>' . $this->form->get_select( 'tc_type_default', $tc_types ) . '</td>';

					$table_rows[ 'tc_sum_img' ] = $this->form->get_tr_hide_img_dim( 'basic', 'tc_sum_img' ) . 
					$this->form->get_th_html( _x( 'Summary Card Image Size', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'tc_sum_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'tc_sum_img' ) . '</td>';

					$table_rows[ 'tc_lrg_img' ] = $this->form->get_tr_hide_img_dim( 'basic', 'tc_lrg_img' ) . 
					$this->form->get_th_html( _x( 'Large Image Summary Card Img Size', 'option label', 'wpsso' ),
						$css_class = '', $css_id = 'tc_lrg_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'tc_lrg_img' ) . '</td>';

					break;

				case 'pub-other_social':

					$social_accounts = apply_filters( $this->p->lca . '_social_accounts', $this->p->cf[ 'form' ][ 'social_accounts' ] );

					asort( $social_accounts );	// Sort by translated label and maintain key association.

					foreach ( $social_accounts as $social_key => $label ) {

						/**
						 * Skip options shown in previous tabs.
						 */
						switch ( $social_key ) {

							case 'fb_publisher_url':	// Facebook
							case 'p_publisher_url':		// Pinterest
							case 'tc_site':			// Twitter

								continue 2;
						}

						$table_rows[ $social_key ] = '' . 
						$this->form->get_th_html_locale( _x( $label, 'option value', 'wpsso' ), $css_class = 'nowrap', $social_key ) . 
						'<td>' . $this->form->get_input_locale( $social_key, strpos( $social_key, '_url' ) ? 'wide' : '' ) . '</td>';
					}

					break;
			}

			return $table_rows;
		}
	}
}
