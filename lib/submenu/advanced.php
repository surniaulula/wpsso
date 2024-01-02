<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuAdvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuAdvanced extends WpssoAdmin {

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
				'plugin'         => _x( 'Plugin Settings', 'metabox title', 'wpsso' ),
				'services'       => _x( 'Service APIs', 'metabox title', 'wpsso' ),
				'doc_types'      => _x( 'Document Types', 'metabox title', 'wpsso' ),
				'schema_defs'    => _x( 'Schema Defaults', 'metabox title', 'wpsso' ),
				'metadata'       => _x( 'Attributes and Metadata', 'metabox title', 'wpsso' ),
				'user_about'     => _x( 'About the User', 'metabox title', 'wpsso' ),
				'contact_fields' => _x( 'Contact Fields', 'metabox title', 'wpsso' ),
				'head_tags'      => _x( 'HTML Tags', 'metabox title', 'wpsso' ),
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

			$callback_args[ 'select_names' ] = array(
				'article_sections' => $this->p->util->get_article_sections(),
				'google_prod_cats' => $this->p->util->get_google_product_categories(),
				'mrp'              => $this->p->util->get_form_cache( 'mrp_names', $add_none = true ),
				'og_types'         => $this->p->util->get_form_cache( 'og_types_select' ),
				'org'              => $this->p->util->get_form_cache( 'org_names', $add_none = true ),
				'person'           => $this->p->util->get_form_cache( 'person_names', $add_none = true ),
				'place'            => $this->p->util->get_form_cache( 'place_names', $add_none = true ),
				'place_custom'     => $this->p->util->get_form_cache( 'place_names_custom', $add_none = true ),
				'place_types'      => $this->p->util->get_form_cache( 'place_types_select' ),
				'schema_types'     => $this->p->util->get_form_cache( 'schema_types_select' ),
			);

			parent::add_settings_page_metaboxes( $callback_args );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_plugin( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$tabs = array(
				'settings'     => _x( 'Plugin Admin', 'metabox tab', 'wpsso' ),
				'integration'  => _x( 'Integration', 'metabox tab', 'wpsso' ),
				'default_text' => _x( 'Default Text', 'metabox tab', 'wpsso' ),
				'image_sizes'  => _x( 'Image Sizes', 'metabox tab', 'wpsso' ),
				'interface'    => _x( 'Interface', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_services( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$tabs = array(
				'media'           => _x( 'Media Services', 'metabox tab', 'wpsso' ),
				'shortening'      => _x( 'Shortening Services', 'metabox tab', 'wpsso' ),
				'ratings_reviews' => _x( 'Ratings and Reviews', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_doc_types( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$tabs = array(
				'og_types'     => _x( 'Open Graph', 'metabox tab', 'wpsso' ),
				'schema_types' => _x( 'Schema', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_schema_defs( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$tabs = array(
				'article'       => _x( 'Article', 'metabox tab', 'wpsso' ),
				'book'          => _x( 'Book', 'metabox tab', 'wpsso' ),
				'creative_work' => _x( 'Creative Work', 'metabox tab', 'wpsso' ),
				'event'         => _x( 'Event', 'metabox tab', 'wpsso' ),
				'job_posting'   => _x( 'Job Posting', 'metabox tab', 'wpsso' ),
				'place'         => _x( 'Place', 'metabox tab', 'wpsso' ),
				'product'       => _x( 'Product', 'metabox tab', 'wpsso' ),
				'review'        => _x( 'Review', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_contact_fields( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$args       = isset( $mb[ 'args' ] ) ? $mb[ 'args' ] : array();
			$metabox_id = isset( $args[ 'metabox_id' ] ) ? $args[ 'metabox_id' ] : '';

			/*
			 * Translate contact method field labels for current language.
			 */
			SucomUtil::transl_key_values( '/^plugin_(cm_.*_label|.*_prefix)$/', $this->p->options, 'wpsso' );

			$info_msg = $this->p->msgs->get( 'info-' . $metabox_id );

			$this->p->util->metabox->do_table( array( '<td>' . $info_msg . '</td>' ), $class_href_key = 'metabox-info metabox-' . $metabox_id . '-info' );

			$tabs = array(
				'default_cm' => _x( 'Default Contacts', 'metabox tab', 'wpsso' ),
				'custom_cm'  => _x( 'Custom Contacts', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_metadata( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$tabs = array(
				'product_attrs' => _x( 'Product Attributes', 'metabox tab', 'wpsso' ),
				'custom_fields' => _x( 'Custom Fields', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_head_tags( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$metabox_id = isset( $mb[ 'args' ][ 'metabox_id' ] ) ? $mb[ 'args' ][ 'metabox_id' ] : '';

			$info_msg = $this->p->msgs->get( 'info-' . $metabox_id );

			$this->p->util->metabox->do_table( array( '<td>' . $info_msg . '</td>' ), $class_href_key = 'metabox-info metabox-' . $metabox_id . '-info' );

			$tabs = array(
				'facebook'   => _x( 'Facebook', 'metabox tab', 'wpsso' ),
				'open_graph' => _x( 'Open Graph', 'metabox tab', 'wpsso' ),
				'twitter'    => _x( 'Twitter', 'metabox tab', 'wpsso' ),
				'seo_other'  => _x( 'SEO / Other', 'metabox tab', 'wpsso' ),
			);

			$this->show_metabox_tabbed( $obj, $mb, $tabs );
		}

		protected function get_table_rows( $page_id, $metabox_id, $tab_key = '', $args = array() ) {

			$table_rows = array();
			$match_rows = trim( $page_id . '-' . $metabox_id . '-' . $tab_key, '-' );

			switch ( $match_rows ) {

				case 'advanced-plugin-settings':
				case 'site-advanced-plugin-settings':

					$cache_val    = $this->p->get_const_status( 'CACHE_DISABLE' ) ? 1 : 0;
					$cache_status = $this->p->get_const_status_transl( 'CACHE_DISABLE' );

					$debug_val    = $this->p->get_const_status( 'DEBUG_HTML' ) ? 1 : 0;
					$debug_status = $this->p->get_const_status_transl( 'DEBUG_HTML' );

					$table_rows[ 'plugin_clean_on_uninstall' ] = '' .
						$this->form->get_th_html( _x( 'Remove Settings on Uninstall', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'plugin_clean_on_uninstall' ) .
						'<td>' . $this->form->get_checkbox( 'plugin_clean_on_uninstall' ) . '</td>' .
						self::get_option_site_use( 'plugin_clean_on_uninstall', $this->form, $args[ 'network' ] );

					$table_rows[ 'plugin_schema_json_min' ] = '' .
						$this->form->get_th_html( _x( 'Minimize Schema JSON-LD', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'plugin_schema_json_min' ) .
						'<td>' . $this->form->get_checkbox( 'plugin_schema_json_min' ) . '</td>' .
						self::get_option_site_use( 'plugin_schema_json_min', $this->form, $args[ 'network' ] );

					$table_rows[ 'plugin_load_mofiles' ] = '' .
						$this->form->get_th_html( _x( 'Use Local Plugin Translations', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'plugin_load_mofiles' ) .
						'<td>' . $this->form->get_checkbox( 'plugin_load_mofiles' ) . '</td>' .
						self::get_option_site_use( 'plugin_load_mofiles', $this->form, $args[ 'network' ] );

					$table_rows[ 'plugin_debug_html' ] = '' .
						$this->form->get_th_html( _x( 'Add HTML Debug Messages', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'plugin_debug_html' ) .
						'<td>' . ( ! $args[ 'network' ] && $debug_status ?
						$this->form->get_hidden( 'plugin_debug_html', 0 ) .	// Uncheck if a constant is defined.
						$this->form->get_no_checkbox( 'plugin_debug_html', $css_class = '', $css_id = '', $debug_val ) . ' ' . $debug_status :
						$this->form->get_checkbox( 'plugin_debug_html' ) ) . '</td>' .
						self::get_option_site_use( 'plugin_debug_html', $this->form, $args[ 'network' ] );

					$table_rows[ 'plugin_cache_disable' ] = '' .
						$this->form->get_th_html( _x( 'Disable Cache for Debugging', 'option label', 'wpsso' ),
							$css_class = '', $css_id = 'plugin_cache_disable' ) .
						'<td>' . ( ! $args[ 'network' ] && $cache_status ?
						$this->form->get_hidden( 'plugin_cache_disable', 0 ) .	// Uncheck if a constant is defined.
						$this->form->get_no_checkbox( 'plugin_cache_disable', $css_class = '', $css_id = '', $cache_val ) . ' ' . $cache_status :
						$this->form->get_checkbox( 'plugin_cache_disable' ) ) . '</td>' .
						self::get_option_site_use( 'plugin_cache_disable', $this->form, $args[ 'network' ] );

					break;
			}

			return $table_rows;
		}
	}
}
