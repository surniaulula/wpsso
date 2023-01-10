<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuAdvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuAdvanced extends WpssoAdmin {

		private $pp = null;

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;

			/**
			 * Since WPSSO Core v14.4.0.
			 */
			$pkg_info = $this->p->util->get_pkg_info();     // Uses a local cache.

			$this->pp = $pkg_info[ 'wpsso' ][ 'pp' ];
		}

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$this->maybe_show_language_notice();

			foreach ( array(
				'plugin'         => _x( 'Plugin Settings', 'metabox title', 'wpsso' ),
				'services'       => _x( 'Service APIs', 'metabox title', 'wpsso' ),
				'doc_types'      => _x( 'Document Types', 'metabox title', 'wpsso' ),
				'schema_props'   => _x( 'Schema Defaults', 'metabox title', 'wpsso' ),
				'metadata'       => _x( 'Attributes and Metadata', 'metabox title', 'wpsso' ),
				'user_about'     => _x( 'About the User', 'metabox title', 'wpsso' ),
				'contact_fields' => _x( 'Contact Fields', 'metabox title', 'wpsso' ),
				'head_tags'      => _x( 'HTML Tags', 'metabox title', 'wpsso' ),
			) as $metabox_id => $metabox_title ) {

				$metabox_screen  = $this->pagehook;
				$metabox_context = 'normal';
				$metabox_prio    = 'default';
				$callback_args   = array(	// Second argument passed to the callback function / method.
					'page_id'    => $this->menu_id,
					'metabox_id' => $metabox_id,
				);

				if ( method_exists( $this, 'show_metabox_' . $metabox_id ) ) {

					add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
						array( $this, 'show_metabox_' . $metabox_id ), $metabox_screen,
							$metabox_context, $metabox_prio, $callback_args );
				} else {

					add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
						array( $this, 'show_metabox_table' ), $metabox_screen,
							$metabox_context, $metabox_prio, $callback_args );
				}
			}
		}

		public function show_metabox_plugin() {

			$metabox_id = 'plugin';
			$table_rows = array();

			$tabs = apply_filters( 'wpsso_advanced_' . $metabox_id . '_tabs', array(
				'settings'     => _x( 'Plugin Admin', 'metabox tab', 'wpsso' ),
				'integration'  => _x( 'Integration', 'metabox tab', 'wpsso' ),
				'default_text' => _x( 'Default Text', 'metabox tab', 'wpsso' ),
				'image_sizes'  => _x( 'Image Sizes', 'metabox tab', 'wpsso' ),
				'interface'    => _x( 'Interface', 'metabox tab', 'wpsso' ),
			) );

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false, $this->pp );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_services() {

			$metabox_id = 'services';
			$table_rows = array();

			$tabs = apply_filters( 'wpsso_advanced_' . $metabox_id . '_tabs', array(
				'media'           => _x( 'Media Services', 'metabox tab', 'wpsso' ),
				'shortening'      => _x( 'Shortening Services', 'metabox tab', 'wpsso' ),
				'ratings_reviews' => _x( 'Ratings and Reviews', 'metabox tab', 'wpsso' ),
			) );

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false, $this->pp );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_doc_types() {

			$metabox_id = 'doc_types';
			$table_rows = array();

			$tabs = apply_filters( 'wpsso_advanced_' . $metabox_id . '_tabs', array(
				'og_types'     => _x( 'Open Graph', 'metabox tab', 'wpsso' ),
				'schema_types' => _x( 'Schema', 'metabox tab', 'wpsso' ),
			) );

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false, $this->pp );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_schema_props() {

			$metabox_id = 'schema_props';

			$tabs = apply_filters( 'wpsso_advanced_' . $metabox_id . '_tabs', array(
				'article'       => _x( 'Article', 'metabox tab', 'wpsso' ),
				'book'          => _x( 'Book', 'metabox tab', 'wpsso' ),
				'creative_work' => _x( 'Creative Work', 'metabox tab', 'wpsso' ),
				'event'         => _x( 'Event', 'metabox tab', 'wpsso' ),
				'job_posting'   => _x( 'Job Posting', 'metabox tab', 'wpsso' ),
				'product'       => _x( 'Product', 'metabox tab', 'wpsso' ),
				'review'        => _x( 'Review', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				if ( isset( $this->p->avail[ 'p' ][ 'schema' ] ) && empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

					$table_rows[ $tab_key ] = $this->p->msgs->get_schema_disabled_rows( $table_rows[ $tab_key ] );

				} else {

					$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

					$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

					$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false, $this->pp );
				}
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_contact_fields() {

			/**
			 * Translate contact method field labels for current language.
			 */
			SucomUtil::transl_key_values( '/^plugin_(cm_.*_label|.*_prefix)$/', $this->p->options, 'wpsso' );

			$metabox_id = 'cm';
			$table_rows = array();
			$info_msg   = $this->p->msgs->get( 'info-' . $metabox_id );

			$tabs = apply_filters( 'wpsso_advanced_' . $metabox_id . '_tabs', array(
				'custom_contacts'  => _x( 'Custom Contacts', 'metabox tab', 'wpsso' ),
				'default_contacts' => _x( 'Default Contacts', 'metabox tab', 'wpsso' ),
			) );

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false, $this->pp );
			}

			$this->p->util->metabox->do_table( array( '<td>' . $info_msg . '</td>' ), $class_href_key = 'metabox-info metabox-' . $metabox_id . '-info' );

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_metadata() {

			$metabox_id = 'metadata';
			$table_rows = array();

			$tabs = apply_filters( 'wpsso_advanced_' . $metabox_id . '_tabs', array(
				'product_attrs' => _x( 'Product Attributes', 'metabox tab', 'wpsso' ),
				'custom_fields' => _x( 'Custom Fields', 'metabox tab', 'wpsso' ),
			) );

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false, $this->pp );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_head_tags() {

			$metabox_id = 'head_tags';
			$table_rows = array();
			$info_msg   = $this->p->msgs->get( 'info-' . $metabox_id );

			$tabs = apply_filters( 'wpsso_advanced_' . $metabox_id . '_tabs', array(
				'facebook'   => _x( 'Facebook', 'metabox tab', 'wpsso' ),
				'open_graph' => _x( 'Open Graph', 'metabox tab', 'wpsso' ),
				'twitter'    => _x( 'Twitter', 'metabox tab', 'wpsso' ),
				'seo_other'  => _x( 'SEO and Others', 'metabox tab', 'wpsso' ),
			) );

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = false, $this->pp );
			}

			$this->p->util->metabox->do_table( array( '<td>' . $info_msg . '</td>' ), $class_href_key = 'metabox-info metabox-' . $metabox_id . '-info' );

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			switch ( $metabox_id . '-' . $tab_key ) {

				case 'plugin-settings':

					$this->add_advanced_plugin_settings_table_rows( $table_rows, $this->form );

					break;
			}

			return $table_rows;
		}
	}
}
