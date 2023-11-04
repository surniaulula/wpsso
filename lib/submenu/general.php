<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
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

			$this->menu_metaboxes = array(
				'open_graph'    => _x( 'General Settings', 'metabox title', 'wpsso' ),
				'social_search' => _x( 'Social and Search Sites', 'metabox title', 'wpsso' ),
				'social_pages'  => _x( 'Social Pages and Accounts', 'metabox title', 'wpsso' ),
			);
		}

		/*
		 * Add metaboxes for this settings page.
		 *
		 * See WpssoAdmin->load_settings_page().
		 */
		protected function add_settings_page_metaboxes( $callback_args = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->maybe_show_language_notice();

			$this->p->media->get_default_images( $size_name = 'wpsso-opengraph' );

			parent::add_settings_page_metaboxes( $callback_args );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_open_graph( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$tabs = array(
				'site'     => _x( 'Site Information', 'metabox tab', 'wpsso' ),
				'loc_defs' => _x( 'Location Defaults', 'metabox tab', 'wpsso' ),
				'content'  => _x( 'Titles / Descriptions', 'metabox tab', 'wpsso' ),
				'images'   => _x( 'Images', 'metabox tab', 'wpsso' ),
				'videos'   => _x( 'Videos', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_social_search( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$tabs = array(
				'facebook'    => _x( 'Facebook', 'metabox tab', 'wpsso' ),
				'google'      => _x( 'Google', 'metabox tab', 'wpsso' ),
				'pinterest'   => _x( 'Pinterest', 'metabox tab', 'wpsso' ),
				'twitter'     => _x( 'Twitter', 'metabox tab', 'wpsso' ),
				'other_sites' => _x( 'Other Sites', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		protected function get_table_rows( $page_id, $metabox_id, $tab_key = '', $args = array() ) {

			$table_rows = array();
			$match_rows = trim( $page_id . '-' . $metabox_id . '-' . $tab_key, '-' );

			switch ( $match_rows ) {

				case 'general-open_graph-site':

					$def_site_name = SucomUtil::get_site_name();
					$def_site_desc = SucomUtil::get_site_description();
					$def_home_url  = SucomUtil::get_home_url();	// Returns the home page URL with a trailing slash.
					$org_types     = $this->p->util->get_form_cache( 'org_types_select', $add_none = false );
					$place_names   = $this->p->util->get_form_cache( 'place_names', $add_none = true );

					$table_rows[ 'site_name' ] = '' .
						$this->form->get_th_html_locale( _x( 'Site Name', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_name' ) .
						'<td>' . $this->form->get_input_locale( 'site_name', $css_class = 'long_name', $css_id = '',
							$len = 0, $def_site_name ) . '</td>';

					$table_rows[ 'site_name_alt' ] = '' .
						$this->form->get_th_html_locale( _x( 'Site Alternate Name', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_name_alt' ) .
						'<td>' . $this->form->get_input_locale( 'site_name_alt', $css_class = 'long_name' ) . '</td>';

					$table_rows[ 'site_desc' ] = '' .
						$this->form->get_th_html_locale( _x( 'Site Description', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_desc' ) .
						'<td>' . $this->form->get_input_locale( 'site_desc', $css_class = 'wide', $css_id = '',
							$len = 0, $def_site_desc ) . '</td>';

					$table_rows[ 'site_home_url' ] = $this->form->get_tr_hide( $in_view = 'basic', 'site_home_url' ) .
						$this->form->get_th_html_locale( _x( 'Site Home URL', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'site_home_url' ) .
						'<td>' . $this->form->get_input_locale( 'site_home_url', $css_class = 'wide', $css_id = '',
							$len = 0, $def_home_url ) . '</td>';

					if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

						$tr_on_change_organization_html = $this->form->get_tr_on_change( 'site_pub_schema_type', 'organization' );

						$this->add_table_rows_schema_publisher_type( $table_rows, $this->form );	// Also used in the Essential Settings page.

						$table_rows[ 'site_org_place_id' ] = $tr_on_change_organization_html .
							$this->form->get_th_html( _x( 'Organization Location', 'option label', 'wpsso' ),
								$css_class = '', $css_id = 'site_org_place_id' ) .
							'<td>' . $this->form->get_select( 'site_org_place_id', $place_names,
								$css_class = 'wide', $css_id = '', $is_assoc = true ) . '</td>';

						$table_rows[ 'site_org_schema_type' ] = $tr_on_change_organization_html .
							$this->form->get_th_html( _x( 'Organization Schema Type', 'option label', 'wpsso' ),
								$css_class = '', $css_id = 'site_org_schema_type' ) .
							'<td>' . $this->form->get_select( 'site_org_schema_type', $org_types,
								$css_class = 'schema_type', $css_id = '', $is_assoc = true, $is_disabled = false,
									$selected = false, $event_names = array( 'on_focus_load_json' ),
										$event_args = array( 'json_var' => 'org_types' ) ) . '</td>';
					}

					break;

				case 'general-open_graph-loc_defs':

					$dimension_units  = WpssoUtilUnits::get_dimension_units();
					$fl_volume_units  = WpssoUtilUnits::get_fluid_volume_units();
					$weight_units     = WpssoUtilUnits::get_weight_units();

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
						'<td>' . $this->form->get_select( 'og_def_dimension_units', $dimension_units,
							$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ) . '</td>';

					$table_rows[ 'og_def_weight_units' ] = '' .
						$this->form->get_th_html( _x( 'Default Weight Units', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_weight_units' ) .
						'<td>' . $this->form->get_select( 'og_def_weight_units', $weight_units,
							$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ) . '</td>';

					$table_rows[ 'og_def_fluid_volume_units' ] = '' .
						$this->form->get_th_html( _x( 'Default Fluid Volume Units', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'og_def_fluid_volume_units' ) .
						'<td>' . $this->form->get_select( 'og_def_fluid_volume_units', $fl_volume_units,
							$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ) . '</td>';

					break;

				case 'general-open_graph-content':

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

				case 'general-open_graph-images':

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

				case 'general-open_graph-videos':

					break;

				case 'general-social_search-facebook':

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

				case 'general-social_search-google':

					$table_rows[ 'g_site_verify' ] = '' .
						$this->form->get_th_html( _x( 'Google Website Verification ID', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'g_site_verify' ) .
						'<td>' . $this->form->get_input( 'g_site_verify', $css_class = 'api_key' ) . '</td>';

					/*
					 * Robots settings.
					 *
					 * WpssoUtilRobots->is_disabled() returns true if:
					 *
					 *	- An SEO plugin is active.
					 *	- The 'add_meta_name_robots' option is unchecked.
					 *	- The 'wpsso_robots_disabled' filter returns true.
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

				case 'general-social_search-pinterest':

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

				case 'general-social_search-twitter':

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

				case 'general-social_search-other_sites':

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
