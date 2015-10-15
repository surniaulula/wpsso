<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSitesubmenuSiteadvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSitesubmenuSiteadvanced extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
		}

		protected function set_form_property() {
			$def_site_opts = $this->p->opt->get_site_defaults();
			$this->form = new SucomForm( $this->p, WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $def_site_opts );
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
			array_push( $classes, 'postbox_network' );
			return $classes;
		}

		public function show_metabox_plugin() {
			$metabox = 'plugin';
			$tabs = apply_filters( $this->p->cf['lca'].'_network_'.$metabox.'_tabs', array( 
				'settings' => _x( 'Plugin Settings', 'metabox tab', 'wpsso' ),
				'cache' => _x( 'File and Object Cache', 'metabox tab', 'wpsso' ),
			) );
			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, true ) );	// $network = true
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		protected function get_rows( $metabox, $key ) {
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'plugin-settings':

					$rows['plugin_debug'] = $this->p->util->get_th( _x( 'Add Hidden Debug Messages',
						'option label', 'wpsso' ), null, 'plugin_debug' ).
					'<td>'.$this->form->get_checkbox( 'plugin_debug' ).'</td>'.
					$this->p->admin->get_site_use( $this->form, true, 'plugin_debug' );

					$rows['plugin_preserve'] = $this->p->util->get_th( _x( 'Preserve Settings on Uninstall',
						'option label', 'wpsso' ), 'highlight', 'plugin_preserve' ).
					'<td>'.$this->form->get_checkbox( 'plugin_preserve' ).'</td>'.
					$this->p->admin->get_site_use( $this->form, true, 'plugin_preserve' );

					break;
			}
			return $rows;
		}
	}
}

?>
