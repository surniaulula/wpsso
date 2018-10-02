<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSitesubmenuSiteadvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSitesubmenuSiteadvanced extends WpssoAdmin {

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

		protected function set_form_object( $menu_ext ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'setting site form object for '.$menu_ext );
			}

			$def_site_opts = $this->p->opt->get_site_defaults();

			$this->form = new SucomForm( $this->p, WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $def_site_opts, $menu_ext );
		}

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			add_meta_box( $this->pagehook.'_plugin',
				_x( 'Network Advanced Settings', 'metabox title', 'wpsso' ),
					array( $this, 'show_metabox_plugin' ), $this->pagehook, 'normal' );

			/**
			 * Add a class to set a minimum width for the network postboxes.
			 */
			add_filter( 'postbox_classes_' . $this->pagehook . '_' . $this->pagehook . '_plugin',
				array( $this, 'add_class_postbox_network' ) );
		}

		public function show_metabox_plugin() {

			$metabox_id = 'plugin';

			$tabs = apply_filters( $this->p->cf['lca'].'_siteadvanced_plugin_tabs', array(
				'settings' => _x( 'Plugin Settings', 'metabox tab', 'wpsso' ),
				'cache' => _x( 'Cache Settings', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {
				$table_rows[$tab_key] = array_merge( $this->get_table_rows( $metabox_id, $tab_key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox_id.'_'.$tab_key.'_rows',
						array(), $this->form, true ) );	// $network = true
			}

			$this->p->util->do_metabox_tabbed( $metabox_id, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			switch ( $metabox_id.'-'.$tab_key ) {

				case 'plugin-settings':

					$this->add_optional_advanced_table_rows( $table_rows, true );	// $network = true

					break;
			}

			return $table_rows;
		}
	}
}
