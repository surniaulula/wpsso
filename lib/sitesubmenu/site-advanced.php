<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSiteSubmenuSiteAdvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSiteSubmenuSiteAdvanced extends WpssoAdmin {

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

			/*
			 * Since WPSSO Core v14.4.0.
			 */
			$pkg_info = $this->p->util->get_pkg_info();     // Uses a local cache.

			$this->pp = $pkg_info[ 'wpsso' ][ 'pp' ];
		}

		protected function set_form_object( $menu_ext ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'setting site form object for ' . $menu_ext );
			}

			$def_site_opts = $this->p->opt->get_site_defaults();

			$this->form = new SucomForm( $this->p, WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $def_site_opts, $menu_ext );
		}

		/*
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$metabox_id      = 'plugin';
			$metabox_title   = _x( 'Network Advanced Settings', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_plugin' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );

			/*
			 * Add a class to set a minimum width for the network postboxes.
			 */
			add_filter( 'postbox_classes_' . $this->pagehook . '_' . $this->pagehook . '_plugin', array( $this, 'add_class_postbox_network' ) );
		}

		public function show_metabox_plugin() {

			$metabox_id = 'plugin';

			$tabs = apply_filters( 'wpsso_site_advanced_' . $metabox_id . '_tabs', array(
				'settings'    => _x( 'Plugin Admin', 'metabox tab', 'wpsso' ),
				'integration' => _x( 'Integration', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_' . $metabox_id . '_' . $tab_key . '_rows' );

				$table_rows[ $tab_key ] = $this->get_table_rows( $metabox_id, $tab_key );

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $network = true, $this->pp );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			switch ( $metabox_id . '-' . $tab_key ) {

				case 'plugin-settings':

					$this->add_advanced_plugin_settings_table_rows( $table_rows, $this->form, $network = true );

					break;
			}

			return $table_rows;
		}
	}
}
