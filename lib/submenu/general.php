<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
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

			$metabox_id      = 'opengraph';
			$metabox_title   = _x( 'All Social WebSites / Open Graph', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_opengraph' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );

			$metabox_id      = 'publishers';
			$metabox_title   = _x( 'Specific WebSites and Publishers', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_publishers' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );

			/**
			 * Issues a warning notice if the default image size is too small,
			 * unless the WPSSO_CHECK_DEFAULT_IMAGE constant has been defined as false.
			 */
			if ( false !== SucomUtil::get_const( 'WPSSO_CHECK_DEFAULT_IMAGE' ) ) {
				$this->p->media->get_default_images( 1, $this->p->lca . '-opengraph', false );
			}
		}

		public function show_metabox_opengraph() {

			$metabox_id = 'og';

			$tabs = apply_filters( $this->p->lca . '_general_' . $metabox_id . '_tabs', array(
				'site'    => _x( 'Site Information', 'metabox tab', 'wpsso' ),
				'content' => _x( 'Titles / Descriptions', 'metabox tab', 'wpsso' ),
				'author'  => _x( 'Authorship', 'metabox tab', 'wpsso' ),
				'images'  => _x( 'Images', 'metabox tab', 'wpsso' ),
				'videos'  => _x( 'Videos', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {
				$table_rows[$tab_key] = apply_filters( $this->p->lca . '_' . $metabox_id . '_' . $tab_key . '_rows',
					$this->get_table_rows( $metabox_id, $tab_key ), $this->form );
			}

			$this->p->util->do_metabox_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_publishers() {

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

			$this->p->util->do_metabox_tabbed( $metabox_id, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();
			$user_contacts = $this->p->m[ 'util' ][ 'user' ]->get_form_contact_fields();

			switch ( $metabox_id . '-' . $tab_key ) {

				case 'og-site':

					$table_rows['site_name'] = '' . 
					$this->form->get_th_html( _x( 'WebSite Name', 'option label', 'wpsso' ), '', 'site_name',
						array( 'is_locale' => true ) ) . 
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'site_name', $this->p->options ),
						'long_name', '', 0, get_bloginfo( 'name', 'display' ) ) . '</td>';

					$table_rows['site_desc'] = '' . 
					$this->form->get_th_html( _x( 'WebSite Description', 'option label', 'wpsso' ), '', 'site_desc',
						array( 'is_locale' => true ) ) . 
					'<td>' . $this->form->get_textarea( SucomUtil::get_key_locale( 'site_desc', $this->p->options ),
						'', '', 0, get_bloginfo( 'description', 'display' ) ) . '</td>';

					$table_rows['og_art_section'] = '' . 
					$this->form->get_th_html( _x( 'Default Article Topic', 'option label', 'wpsso' ), '', 'og_art_section' ) . 
					'<td>' . $this->form->get_select( 'og_art_section', $this->p->util->get_article_topics() ) . '</td>';

					/**
					 * Hide all options in basic view by default.
					 */
					$this->add_og_types_table_rows( $table_rows, array(
						'og_type_for_home_index'   => 'basic',
						'og_type_for_home_page'    => 'basic',
						'og_type_for_user_page'    => 'basic',
						'og_type_for_search_page'  => 'basic',
						'og_type_for_archive_page' => 'basic',
						'og_type_for_ptn'          => 'basic',
						'og_type_for_ttn'          => 'basic',
					) );

					break;

				case 'og-content':

					$table_rows['og_title_sep'] = '' . 
					$this->form->get_th_html( _x( 'Title Separator', 'option label', 'wpsso' ), '', 'og_title_sep' ) . 
					'<td>' . $this->form->get_input( 'og_title_sep', 'short' ) . '</td>';

					$table_rows['og_title_max_len'] = '' . 
					$this->form->get_th_html( _x( 'Maximum Title Length', 'option label', 'wpsso' ), '', 'og_title_max_len' ) . 
					'<td>' . 
						$this->form->get_input( 'og_title_max_len', 'short' ) . ' ' . 
						_x( 'characters or less (hard limit), and warn at', 'option comment', 'wpsso' ) . ' ' . 
						$this->form->get_input( 'og_title_warn_len', 'short' ) . ' ' . 
						_x( 'characters (soft limit)', 'option comment', 'wpsso' ) . 
					'</td>';


					$table_rows['og_desc_max_len'] = '' . 
					$this->form->get_th_html( _x( 'Maximum Description Length', 'option label', 'wpsso' ), '', 'og_desc_max_len' ) . 
					'<td>' . 
						$this->form->get_input( 'og_desc_max_len', 'short' ) . ' ' . 
						_x( 'characters or less (hard limit), and warn at', 'option comment', 'wpsso' ) . ' ' . 
						$this->form->get_input( 'og_desc_warn_len', 'short' ) . ' ' . 
						_x( 'characters (soft limit)', 'option comment', 'wpsso' ) . 
					'</td>';

					$table_rows['og_desc_hashtags'] = $this->form->get_tr_hide( 'basic', 'og_desc_hashtags' ) . 
					$this->form->get_th_html( _x( 'Add Hashtags to Descriptions', 'option label', 'wpsso' ), '', 'og_desc_hashtags' ) . 
					'<td>' . $this->form->get_select( 'og_desc_hashtags', range( 0, $this->p->cf['form']['max_hashtags'] ), 'short', '', true ) . ' ' . 
						_x( 'tag names', 'option comment', 'wpsso' ) . '</td>';

					break;

				case 'og-author':

					$table_rows['og_author_field'] = '' . 
					$this->form->get_th_html( _x( 'Author Profile URL Field', 'option label', 'wpsso' ), '', 'og_author_field' ) . 
					'<td>' . $this->form->get_select( 'og_author_field', $user_contacts ) . '</td>';

					break;

				case 'og-images':

					$table_rows['og_img_max'] = $this->form->get_tr_hide( 'basic', 'og_img_max' ) . 
					$this->form->get_th_html( _x( 'Maximum Images to Include', 'option label', 'wpsso' ), '', 'og_img_max' ) . 
					'<td>' . $this->form->get_select( 'og_img_max', range( 0, $this->p->cf['form']['max_media_items'] ), 'short', '', true ) . 
					( empty( $this->form->options['og_vid_prev_img'] ) ? '' : ' <em>' .
						_x( 'video preview images are enabled (and included first)', 'option comment', 'wpsso' ) . '</em>' ) . '</td>';

					$table_rows['og_img'] = '' . 
					$this->form->get_th_html( _x( 'Open Graph Image Dimensions', 'option label', 'wpsso' ), '', 'og_img_dimensions' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'og_img' ) . '</td>';	// $use_opts = false

					$table_rows['og_def_img_id'] = '' . 
					$this->form->get_th_html( _x( 'Default / Fallback Image ID', 'option label', 'wpsso' ), '', 'og_def_img_id' ) . 
					'<td>' . $this->form->get_input_image_upload( 'og_def_img' ) . '</td>';

					$table_rows['og_def_img_url'] = '' . 
					$this->form->get_th_html( _x( 'or Default / Fallback Image URL', 'option label', 'wpsso' ), '', 'og_def_img_url' ) . 
					'<td>' . $this->form->get_input_image_url( 'og_def_img' ) . '</td>';

					break;

				case 'og-videos':

					break;

				case 'pub-facebook':

					$table_rows['fb_publisher_url'] = '' . 
					$this->form->get_th_html( _x( 'Facebook Business Page URL', 'option label', 'wpsso' ), '', 'fb_publisher_url', 
						array( 'is_locale' => true ) ) . 
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'fb_publisher_url', $this->p->options ), 'wide' ) . '</td>';

					$table_rows['fb_app_id'] = '' . 
					$this->form->get_th_html( _x( 'Facebook Application ID', 'option label', 'wpsso' ), '', 'fb_app_id' ) . 
					'<td>' . $this->form->get_input( 'fb_app_id' ) . '</td>';

					$table_rows['fb_admins'] = $this->form->get_tr_hide( 'basic', 'fb_admins' ) . 
					$this->form->get_th_html( _x( 'or Facebook Admin Username(s)', 'option label', 'wpsso' ), '', 'fb_admins' ) . 
					'<td>' . $this->form->get_input( 'fb_admins' ) . '</td>';

					$fb_pub_lang   = SucomUtil::get_pub_lang( 'facebook' );
					$fb_locale_key = SucomUtil::get_key_locale( 'fb_locale', $this->p->options );

					$table_rows['fb_locale'] = $this->form->get_tr_hide( 'basic', $fb_locale_key ) . 
					$this->form->get_th_html( _x( 'Custom Facebook Locale', 'option label', 'wpsso' ), '', 'fb_locale', 
						array( 'is_locale' => true ) ) . 
					'<td>' . $this->form->get_select( $fb_locale_key, $fb_pub_lang ) . '</td>';

					break;

				case 'pub-google':

					$table_rows['seo_publisher_url'] = '' . 
					$this->form->get_th_html( _x( 'Google+ Business Page URL', 'option label', 'wpsso' ), '', 'seo_publisher_url', 
						array( 'is_locale' => true ) ) . 
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'seo_publisher_url', $this->p->options ), 'wide' ) . '</td>';

					$table_rows['seo_desc_max_len'] = $this->form->get_tr_hide( 'basic', 'seo_desc_max_len' ) . 
					$this->form->get_th_html( _x( 'Search / SEO Description Length', 'option label', 'wpsso' ), '', 'seo_desc_max_len' ) . 
					'<td>' . $this->form->get_input( 'seo_desc_max_len', 'short' ) . ' ' .
					_x( 'characters or less', 'option comment', 'wpsso' ) . '</td>';

					$table_rows['seo_author_name'] = $this->form->get_tr_hide( 'basic', 'seo_author_name' ) . 
					$this->form->get_th_html( _x( 'Author / Person Name Format', 'option label', 'wpsso' ), '', 'seo_author_name' ) . 
					'<td>' . $this->form->get_select( 'seo_author_name', $this->p->cf['form']['user_name_fields'] ) . '</td>';

					$table_rows['seo_author_field'] = $this->form->get_tr_hide( 'basic', 'seo_author_field' ) . 
					$this->form->get_th_html( _x( 'Author Link URL Profile Contact', 'option label', 'wpsso' ), '', 'seo_author_field' ) . 
					'<td>' . $this->form->get_select( 'seo_author_field', $user_contacts ) . '</td>';

					$table_rows['subsection_google_schema'] = '<td colspan="2" class="subsection"><h4>' . 
					_x( 'Structured Data / Schema Markup', 'metabox title', 'wpsso' ) . '</h4></td>';

					if ( $this->p->schema->is_noscript_enabled() ) {
						$table_rows['schema_add_noscript'] = $this->form->get_tr_hide( 'basic', 'schema_add_noscript' ) . 
						$this->form->get_th_html( _x( 'Meta Property Containers', 'option label', 'wpsso' ), '', 'schema_add_noscript' ) . 
						'<td>' . $this->form->get_checkbox( 'schema_add_noscript' ) . '</td>';
					}

					$this->add_schema_knowledge_graph_table_rows( $table_rows );

					$this->add_schema_item_props_table_rows( $table_rows );

					/**
					 * Hide all options in basic view by default.
					 */
					$this->add_schema_item_types_table_rows( $table_rows, array(
						'schema_type_for_home_index'   => 'basic',
						'schema_type_for_home_page'    => 'basic',
						'schema_type_for_user_page'    => 'basic',
						'schema_type_for_search_page'  => 'basic',
						'schema_type_for_archive_page' => 'basic',
						'schema_type_for_ptn'          => 'basic',
						'schema_type_for_ttn'          => 'basic',
					) );

					break;

				case 'pub-pinterest':

					$table_rows['p_publisher_url'] = '' . 
					$this->form->get_th_html( _x( 'Pinterest Company Page URL', 'option label', 'wpsso' ), '', 'p_publisher_url', 
						array( 'is_locale' => true ) ) . 
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'p_publisher_url', $this->p->options ), 'wide' ) . '</td>';

					$table_rows['p_dom_verify'] = $this->form->get_tr_hide( 'basic', 'p_dom_verify' ) . 
					$this->form->get_th_html( _x( 'Pinterest Verification ID', 'option label', 'wpsso' ), '', 'p_dom_verify' ) . 
					'<td>' . $this->form->get_input( 'p_dom_verify', 'api_key' ) . '</td>';

					$table_rows['p_add_nopin_header_img_tag'] = '' . 
					$this->form->get_th_html( _x( 'Add "nopin" to Site Header Image', 'option label', 'wpsso' ), '', 'p_add_nopin_header_img_tag' ) . 
					'<td>' . $this->form->get_checkbox( 'p_add_nopin_header_img_tag' ) . '</td>';

					$table_rows['p_add_nopin_media_img_tag'] = '' . 
					$this->form->get_th_html( _x( 'Add "nopin" to WordPress Media', 'option label', 'wpsso' ), '', 'p_add_nopin_media_img_tag' ) . 
					'<td>' . $this->form->get_checkbox( 'p_add_nopin_media_img_tag' ) . '</td>';

					$table_rows['p_add_img_html'] = '' . 
					$this->form->get_th_html( _x( 'Add Hidden Image for Pin It Button', 'option label', 'wpsso' ), '', 'p_add_img_html' ) . 
					'<td>' . $this->form->get_checkbox( 'p_add_img_html' ) . '</td>';

					break;

				case 'pub-twitter':

					$tc_types = array(
						'summary' => _x( 'Summary', 'option value', 'wpsso' ),
						'summary_large_image' => _x( 'Summary Large Image', 'option value', 'wpsso' ),
					);

					$table_rows['tc_site'] = '' . 
					$this->form->get_th_html( _x( 'Twitter Business @username', 'option label', 'wpsso' ), '', 'tc_site', 
						array( 'is_locale' => true ) ) . 
					'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'tc_site', $this->p->options ) ) . '</td>';

					$table_rows['tc_desc_max_len'] = $this->form->get_tr_hide( 'basic', 'tc_desc_max_len' ) . 
					$this->form->get_th_html( _x( 'Maximum Description Length', 'option label', 'wpsso' ), '', 'tc_desc_max_len' ) . 
					'<td>' . $this->form->get_input( 'tc_desc_max_len', 'short' ) . ' ' . 
					_x( 'characters or less', 'option comment', 'wpsso' ) . '</td>';

					$table_rows['tc_type_singular'] = $this->form->get_tr_hide( 'basic', 'tc_type_post' ) . 
					$this->form->get_th_html( _x( 'Twitter Card for Post / Page Image', 'option label', 'wpsso' ), '', 'tc_type_post' ) . 
					'<td>' . $this->form->get_select( 'tc_type_post', $tc_types ) . '</td>';

					$table_rows['tc_type_default'] = $this->form->get_tr_hide( 'basic', 'tc_type_default' ) . 
					$this->form->get_th_html( _x( 'Twitter Card Type by Default', 'option label', 'wpsso' ), '', 'tc_type_default' ) . 
					'<td>' . $this->form->get_select( 'tc_type_default', $tc_types ) . '</td>';

					$table_rows['tc_sum_img'] = '' . 
					$this->form->get_th_html( _x( '<em>Summary</em> Card Image Dimensions', 'option label', 'wpsso' ), '', 'tc_sum_img_dimensions' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'tc_sum_img' ) . '</td>';	// $use_opts = false

					$table_rows['tc_lrg_img'] = '' . 
					$this->form->get_th_html( _x( '<em>Large Image</em> Card Img Dimensions', 'option label', 'wpsso' ), '', 'tc_lrg_img_dimensions' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'tc_lrg_img' ) . '</td>';	// $use_opts = false

					break;

				case 'pub-other_social':

					$social_accounts = apply_filters( $this->p->lca . '_social_accounts', $this->p->cf['form']['social_accounts'] );

					asort( $social_accounts );	// Sort by translated label and maintain key association.

					foreach ( $social_accounts as $social_key => $label ) {

						/**
						 * Skip options shown in previous tabs.
						 */
						switch ( $social_key ) {

							case 'fb_publisher_url':	// Facebook
							case 'p_publisher_url':		// Pinterest
							case 'seo_publisher_url':	// Google
							case 'tc_site':			// Twitter

								continue 2;
						}

						$table_rows[$social_key] = '' . 
						$this->form->get_th_html( _x( $label, 'option value', 'wpsso' ), 'nowrap', $social_key, 
							array( 'is_locale' => true ) ) . 
						'<td>' . $this->form->get_input( SucomUtil::get_key_locale( $social_key, $this->p->options ),
							( strpos( $social_key, '_url' ) ? 'wide' : '' ) ) . '</td>';
					}

					break;
			}

			return $table_rows;
		}
	}
}
