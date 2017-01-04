<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSitesubmenuSiteadvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSitesubmenuSiteadvanced extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;
			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		protected function set_form_property( $menu_ext ) {
			$def_site_opts = $this->p->opt->get_site_defaults();
			$this->form = new SucomForm( $this->p, WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $def_site_opts, $menu_ext );
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_plugin',
				_x( 'Network Advanced Settings', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_plugin' ), $this->pagehook, 'normal' );

			// add a class to set a minimum width for the network postboxes
			add_filter( 'postbox_classes_'.$this->pagehook.'_'.$this->pagehook.'_plugin', 
				array( &$this, 'add_class_postbox_network' ) );
		}

		public function add_class_postbox_network( $classes ) {
			$classes[] = 'postbox-network';
			return $classes;
		}

		public function show_metabox_plugin() {
			$metabox = 'plugin';
			$tabs = apply_filters( $this->p->cf['lca'].'_siteadvanced_plugin_tabs', array( 
				'settings' => _x( 'Plugin Settings', 'metabox tab', 'wpsso' ),
				'cache' => _x( 'Cache Settings', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title )
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, true ) );	// $network = true
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox, $key ) {
			$table_rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'plugin-settings':

					$table_rows['plugin_honor_force_ssl'] = $this->form->get_th_html( _x( 'Honor the FORCE_SSL Constant',
						'option label', 'wpsso' ), null, 'plugin_honor_force_ssl' ).
					'<td>'.$this->form->get_checkbox( 'plugin_honor_force_ssl' ).'</td>'.
					$this->p->admin->get_site_use( $this->form, true, 'plugin_honor_force_ssl', true );	// $network = true

					$table_rows['plugin_clear_on_save'] = $this->form->get_th_html( _x( 'Clear Cache(s) on Save Settings',
						'option label', 'wpsso' ), null, 'plugin_clear_on_save' ).
					'<td>'.$this->form->get_checkbox( 'plugin_clear_on_save' ).'</td>'.
					$this->p->admin->get_site_use( $this->form, true, 'plugin_clear_on_save', true );	// $network = true

					$table_rows['plugin_preserve'] = $this->form->get_th_html( _x( 'Preserve Settings on Uninstall',
						'option label', 'wpsso' ), null, 'plugin_preserve' ).
					'<td>'.$this->form->get_checkbox( 'plugin_preserve' ).'</td>'.
					$this->p->admin->get_site_use( $this->form, true, 'plugin_preserve', true );	// $network = true

					$table_rows['plugin_debug'] = $this->form->get_th_html( _x( 'Add Hidden Debug Messages',
						'option label', 'wpsso' ), null, 'plugin_debug' ).
					'<td>'.$this->form->get_checkbox( 'plugin_debug' ).'</td>'.
					$this->p->admin->get_site_use( $this->form, true, 'plugin_debug', true );	// $network = true

					break;
			}
			return $table_rows;
		}
	}
}

?>
