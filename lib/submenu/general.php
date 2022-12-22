<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
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

			$this->p->media->get_default_images( $size_name = 'wpsso-opengraph' );

			/**
			 * General Settings metabox.
			 */
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

			/**
			 * Social and Search Sites metabox.
			 */
			$metabox_id      = 'pub';
			$metabox_title   = _x( 'Social and Search Sites', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_' . $metabox_id ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );

			/**
			 * Social and Search Sites metabox.
			 */
			$metabox_id      = 'social_pages';
			$metabox_title   = _x( 'Social Pages and Accounts', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'page_id'    => $this->menu_id,
				'metabox_id' => $metabox_id,
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_table' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );
		}

		public function show_metabox_og() {

			$metabox_id = 'og';

			$tabs = apply_filters( 'wpsso_general_' . $metabox_id . '_tabs', array(
				'site'    => _x( 'Site Information', 'metabox tab', 'wpsso' ),
				'content' => _x( 'Titles and Descriptions', 'metabox tab', 'wpsso' ),
				'images'  => _x( 'Images', 'metabox tab', 'wpsso' ),
				'videos'  => _x( 'Videos', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_pub() {

			$metabox_id = 'pub';

			$tabs = apply_filters( 'wpsso_general_' . $metabox_id . '_tabs', array(
				'facebook'    => _x( 'Facebook', 'metabox tab', 'wpsso' ),
				'google'      => _x( 'Google and Schema', 'metabox tab', 'wpsso' ),
				'pinterest'   => _x( 'Pinterest', 'metabox tab', 'wpsso' ),
				'twitter'     => _x( 'Twitter', 'metabox tab', 'wpsso' ),
				'other_sites' => _x( 'Other Sites', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			switch ( $metabox_id . '-' . $tab_key ) {

				case 'og-site':

					$def_site_name = get_bloginfo( 'name' );
					$def_site_desc = get_bloginfo( 'description' );
					$def_home_url  = SucomUtil::get_home_url();	// Returns the home page URL with a trailing slash.

					$table_rows[ 'site_name' ] = '' .
						$this->form->get_th_html_locale( _x( 'WebSite Name', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_name' ) .
						'<td>' . $this->form->get_input_locale( 'site_name', $css_class = 'long_name', $css_id = '',
							$len = 0, $def_site_name ) . '</td>';

					$table_rows[ 'site_name_alt' ] = $this->form->get_tr_hide( $in_view = 'basic', 'site_name_alt' ) .
						$this->form->get_th_html_locale( _x( 'WebSite Alternate Name', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_name_alt' ) .
						'<td>' . $this->form->get_input_locale( 'site_name_alt', $css_class = 'long_name' ) . '</td>';

					$table_rows[ 'site_desc' ] = '' .
						$this->form->get_th_html_locale( _x( 'WebSite Description', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_desc' ) .
						'<td>' . $this->form->get_input_locale( 'site_desc', $css_class = 'wide', $css_id = '',
							$len = 0, $def_site_desc ) . '</td>';

					$table_rows[ 'site_home_url' ] = $this->form->get_tr_hide( $in_view = 'basic', 'site_home_url' ) .
						$this->form->get_th_html_locale( _x( 'WebSite Home URL', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_home_url' ) .
						'<td>' . $this->form->get_input_locale( 'site_home_url', $css_class = 'wide', $css_id = '',
							$len = 0, $def_home_url ) . '</td>';

					$table_rows[ 'og_def_country' ] = '' .
						$this->form->get_th_html( _x( 'Default Country', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_country' ) .
						'<td>' . $this->form->get_select_country( 'og_def_country' ) . '</td>';

					$table_rows[ 'og_def_timezone' ] = '' .
						$this->form->get_th_html( _x( 'Default Timezone', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_timezone' ) .
						'<td>' . $this->form->get_select_timezone( 'og_def_timezone' ) . '</td>';

					$table_rows[ 'og_def_currency' ] = '' .
						$this->form->get_th_html( _x( 'Default Currency', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_currency' ) .
						'<td>' . $this->form->get_select( 'og_def_currency', SucomUtil::get_currencies() ) . '</td>';

					$table_rows[ 'og_def_dimension_units' ] = '' .
						$this->form->get_th_html( _x( 'Default Dimension Units', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_dimension_units' ) .
						'<td>' . $this->form->get_select( 'og_def_dimension_units', WpssoUtilUnits::get_dimension_units(),
							$css_class = 'units', $css_id = '', $is_assoc = 'sorted' ) . '</td>';

					$table_rows[ 'og_def_fluid_volume_units' ] = '' .
						$this->form->get_th_html( _x( 'Default Fluid Volume Units', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_fluid_volume_units' ) .
						'<td>' . $this->form->get_select( 'og_def_fluid_volume_units', WpssoUtilUnits::get_fluid_volume_units(),
							$css_class = 'units', $css_id = '', $is_assoc = 'sorted' ) . '</td>';

					$table_rows[ 'og_def_weight_units' ] = '' .
						$this->form->get_th_html( _x( 'Default Weight Units', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_weight_units' ) .
						'<td>' . $this->form->get_select( 'og_def_weight_units', WpssoUtilUnits::get_weight_units(),
							$css_class = 'units', $css_id = '', $is_assoc = 'sorted' ) . '</td>';

					break;

				case 'og-content':

					$table_rows[ 'og_title_sep' ] = '' .
						$this->form->get_th_html( _x( 'Title Separator', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_title_sep' ) .
						'<td>' . $this->form->get_input( 'og_title_sep', 'xshort' ) . '</td>';

					$table_rows[ 'og_ellipsis' ] = '' .
						$this->form->get_th_html( _x( 'Truncated Text Ellipsis', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_ellipsis' ) .
						'<td>' . $this->form->get_input( 'og_ellipsis', 'xshort' ) . '</td>';

					$table_rows[ 'og_desc_hashtags' ] = '' .
						$this->form->get_th_html( _x( 'Description Hashtags', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_desc_hashtags' ) .
						'<td>' . $this->form->get_select( 'og_desc_hashtags', range( 0, $this->p->cf[ 'form' ][ 'max_hashtags' ] ),
							$css_class = 'short', $css_id = '', $is_assoc = true ) . ' ' .
						_x( 'tag names', 'option comment', 'wpsso' ) . '</td>';

					break;

				case 'og-images':

					$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

					$table_rows[ 'og_img_max' ] = $this->form->get_tr_hide( $in_view = 'basic', 'og_img_max' ) .
						$this->form->get_th_html( _x( 'Maximum Images to Include', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_img_max' ) .
						'<td>' . $this->form->get_select( 'og_img_max', range( 0, $max_media_items ),
							$css_class = 'short', $css_id = '', $is_assoc = true ) .
								$this->p->msgs->maybe_preview_images_first() . '</td>';

					$table_rows[ 'og_def_img_id' ] = '' .
						$this->form->get_th_html_locale( _x( 'Default Image ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_img_id' ) .
						'<td>' . $this->form->get_input_image_upload( 'og_def_img' ) . '</td>';

					$table_rows[ 'og_def_img_url' ] = '' .
						$this->form->get_th_html_locale( _x( 'or Default Image URL', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_img_url' ) .
						'<td>' . $this->form->get_input_image_url( 'og_def_img' ) . '</td>';

					break;

				case 'og-videos':

					break;

				case 'pub-facebook':

					$user_contacts = $this->p->user->get_form_contact_fields();

					$table_rows[ 'fb_site_verify' ] = '' .
						$this->form->get_th_html( _x( 'Facebook Domain Verification ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'fb_site_verify' ) .
						'<td>' . $this->form->get_input( 'fb_site_verify', $css_class = 'api_key' ) . '</td>';

					$table_rows[ 'fb_locale' ] = '' .
						$this->form->get_th_html_locale( _x( 'Facebook Locale', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'fb_locale' ) .
						'<td>' . $this->form->get_select_locale( 'fb_locale', SucomUtil::get_publisher_languages( 'facebook' ) ) . '</td>';

					$table_rows[ 'fb_app_id' ] = $this->form->get_tr_hide( $in_view = 'basic', 'fb_app_id' ) .
						$this->form->get_th_html( _x( 'Facebook Application ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'fb_app_id' ) .
						'<td>' . $this->form->get_input( 'fb_app_id', $css_class = 'api_key' ) . '</td>';

					break;

				case 'pub-google':

					if ( isset( $this->p->avail[ 'p' ][ 'schema' ] ) && empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

						return $this->p->msgs->get_schema_disabled_rows( $table_rows );
					}

					$org_types_select = $this->p->util->get_form_cache( 'org_types_select', $add_none = false );
					$place_names      = $this->p->util->get_form_cache( 'place_names', $add_none = true );

					/**
					 * Google and Schema settings.
					 */
					$table_rows[ 'g_site_verify' ] = '' .
						$this->form->get_th_html( _x( 'Google Website Verification ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'g_site_verify' ) .
						'<td>' . $this->form->get_input( 'g_site_verify', $css_class = 'api_key' ) . '</td>';

					/**
					 * Schema settings.
					 */
					$this->add_schema_publisher_type_table_rows( $table_rows, $this->form );	// Also used in the Essential Settings page.

					$table_rows[ 'site_org_schema_type' ] = $this->form->get_tr_on_change( 'site_pub_schema_type', 'organization' ) .
						$this->form->get_th_html( _x( 'Organization Schema Type', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_org_schema_type' ) .
						'<td>' . $this->form->get_select( 'site_org_schema_type', $org_types_select, $css_class = 'schema_type', $css_id = '',
							$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'schema_org_types' ) ) . '</td>';

					$table_rows[ 'site_org_place_id' ] = $this->form->get_tr_on_change( 'site_pub_schema_type', 'organization' ) .
						$this->form->get_th_html( _x( 'Organization Location', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_org_place_id' ) .
						'<td>' . $this->form->get_select( 'site_org_place_id', $place_names, $css_class = 'long_name', $css_id = '',
							$is_assoc = true ) . '</td>';

					$table_rows[ 'schema_aggr_offers' ] = $this->form->get_tr_hide( $in_view = 'basic', 'schema_aggr_offers' ) .
						$this->form->get_th_html( _x( 'Aggregate Offers by Currency', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'schema_aggr_offers' ) .
						'<td>' . $this->form->get_checkbox( 'schema_aggr_offers' ) . ' ' .
						sprintf( _x( '(not compatible with <a href="%s">price drop appearance</a>)', 'option comment', 'wpsso' ),
							'https://developers.google.com/search/docs/data-types/product#price-drop' ) . '</td>';

					$table_rows[ 'schema_add_text_prop' ] = $this->form->get_tr_hide( $in_view = 'basic', 'schema_add_text_prop' ) .
						$this->form->get_th_html( _x( 'Add Text / Article Body Properties', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'schema_add_text_prop' ) .
						'<td>' . $this->form->get_checkbox( 'schema_add_text_prop' ) . '</td>';

					/**
					 * Robots settings.
					 */
					$robots_disabled = $this->p->util->robots->is_disabled();

					$table_rows[ 'robots_max_snippet' ] = $this->form->get_tr_hide( $in_view = 'basic', 'robots_max_snippet' ) .
						$this->form->get_th_html( _x( 'Robots Snippet Max. Length', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'robots_max_snippet' ) .
						'<td>' . $this->form->get_input( 'robots_max_snippet',
							$css_class = 'chars', $css_id = '', $len = 0, $holder = false, $robots_disabled ) . ' ' .
						_x( 'characters or less', 'option comment', 'wpsso' ) . ' ' .
						_x( '(-1 for no limit)', 'option comment', 'wpsso' ) . '</td>';

					$table_rows[ 'robots_max_image_preview' ] = $this->form->get_tr_hide( $in_view = 'basic', 'robots_max_image_preview' ) .
						$this->form->get_th_html( _x( 'Robots Image Preview Size', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'robots_max_image_preview' ) .
						'<td>' . $this->form->get_select( 'robots_max_image_preview', $this->p->cf[ 'form' ][ 'robots_max_image_preview' ],
							$css_class = '', $css_id = '', $is_assoc = true, $robots_disabled ) . '</td>';

					$table_rows[ 'robots_max_video_preview' ] = $this->form->get_tr_hide( $in_view = 'basic', 'robots_max_video_preview' ) .
						$this->form->get_th_html( _x( 'Robots Video Max. Previews', 'option label', 'wpsso' ),
							$css_class = 'medium', $css_id = 'robots_max_video_preview' ) .
						'<td>' . $this->form->get_input( 'robots_max_video_preview',
							$css_class = 'chars', $css_id = '', $len = 0, $holder = false, $robots_disabled ) .
						_x( 'seconds', 'option comment', 'wpsso' ) . ' ' .
						_x( '(-1 for no limit)', 'option comment', 'wpsso' ) . '</td>';

					break;

				case 'pub-pinterest':

					$table_rows[ 'pin_site_verify' ] = '' .
						$this->form->get_th_html( _x( 'Pinterest Website Verification ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'pin_site_verify' ) .
						'<td>' . $this->form->get_input( 'pin_site_verify', 'api_key' ) . '</td>';

					$table_rows[ 'pin_add_nopin_header_img_tag' ] = $this->form->get_tr_hide( $in_view = 'basic', 'pin_add_nopin_header_img_tag' ) .
						$this->form->get_th_html( _x( 'Add "nopin" to Site Header Image', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'pin_add_nopin_header_img_tag' ) .
						'<td>' . $this->form->get_checkbox( 'pin_add_nopin_header_img_tag' ) .' ' .
						_x( '(recommended)', 'option comment', 'wpsso' ) . '</td>';

					$table_rows[ 'pin_add_nopin_media_img_tag' ] = '' .
						$this->form->get_th_html( _x( 'Add Pinterest "nopin" to Images', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'pin_add_nopin_media_img_tag' ) .
						'<td>' . $this->form->get_checkbox( 'pin_add_nopin_media_img_tag' ) . ' ' .
						_x( '(recommended)', 'option comment', 'wpsso' ) . '</td>';

					$table_rows[ 'pin_add_img_html' ] = '' .
						$this->form->get_th_html( _x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'pin_add_img_html' ) .
						'<td>' . $this->form->get_checkbox( 'pin_add_img_html' ) . ' ' .
						_x( '(recommended)', 'option comment', 'wpsso' ) . '</td>';

					break;

				case 'pub-twitter':

					$tc_types = array(
						'summary'             => _x( 'Summary', 'option value', 'wpsso' ),
						'summary_large_image' => _x( 'Summary Large Image', 'option value', 'wpsso' ),
					);

					$table_rows[ 'tc_type_singular' ] = '' .
						$this->form->get_th_html( _x( 'Twitter Card for Singular with Image', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'tc_type_singular' ) .
						'<td>' . $this->form->get_select( 'tc_type_singular', $tc_types ) . '</td>';

					$table_rows[ 'tc_type_default' ] = '' .
						$this->form->get_th_html( _x( 'Twitter Card Type by Default', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'tc_type_default' ) .
						'<td>' . $this->form->get_select( 'tc_type_default', $tc_types ) . '</td>';

					break;

				case 'pub-other_sites':

					$table_rows[ 'ahrefs_site_verify' ] = '' .
						$this->form->get_th_html( _x( 'Ahrefs Website Verification ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'ahrefs_site_verify' ) .
						'<td>' . $this->form->get_input( 'ahrefs_site_verify', 'api_key' ) . '</td>';

					$table_rows[ 'baidu_site_verify' ] = '' .
						$this->form->get_th_html( _x( 'Baidu Website Verification ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'baidu_site_verify' ) .
						'<td>' . $this->form->get_input( 'baidu_site_verify', 'api_key' ) . '</td>';

					$table_rows[ 'bing_site_verify' ] = '' .
						$this->form->get_th_html( _x( 'Bing Website Verification ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'bing_site_verify' ) .
						'<td>' . $this->form->get_input( 'bing_site_verify', 'api_key' ) . '</td>';

					$table_rows[ 'yandex_site_verify' ] = '' .
						$this->form->get_th_html( _x( 'Yandex Website Verification ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'yandex_site_verify' ) .
						'<td>' . $this->form->get_input( 'yandex_site_verify', 'api_key' ) . '</td>';

					break;

				case 'general-social_pages':

					foreach ( WpssoConfig::get_social_accounts() as $social_key => $label ) {

						switch ( $social_key ) {

							case 'fb_publisher_url':
							case 'instagram_publisher_url':
							case 'linkedin_publisher_url':
							case 'pin_publisher_url':
							case 'tiktok_publisher_url':
							case 'tc_site':
							case 'yt_publisher_url':

								$tr_hide = '';

								break;

							default:

								$tr_hide = $this->form->get_tr_hide( $in_view = 'basic', 'social_key' );

								break;
						}

						$input_class = strpos( $social_key, '_url' ) ? 'wide' : '';

						$table_rows[ $social_key ] = $tr_hide .
							$this->form->get_th_html_locale( _x( $label, 'option value', 'wpsso' ),
								$css_class = 'nowrap', $social_key ) .
							'<td>' . $this->form->get_input_locale( $social_key, $input_class ) . '</td>';
					}

					break;
			}

			return $table_rows;
		}
	}
}
