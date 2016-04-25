<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSubmenuAdvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuAdvanced extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;
			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_plugin', 
				_x( 'Advanced Settings', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_plugin' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_contact_fields',
				_x( 'Contact Field Names and Labels', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_contact_fields' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_taglist',
				_x( 'Header Tags List', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_taglist' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_plugin() {
			$metabox = 'plugin';
			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_plugin_tabs', array( 
				'settings' => _x( 'Plugin Settings', 'metabox tab', 'wpsso' ),
				'content' => _x( 'Content and Filters', 'metabox tab', 'wpsso' ),
				'social' => _x( 'Social Settings', 'metabox tab', 'wpsso' ),
				'integration' => _x( 'WP / Theme Integration', 'metabox tab', 'wpsso' ),
				'cache' => _x( 'File and Object Cache', 'metabox tab', 'wpsso' ),
				'apikeys' => _x( 'Service API Keys', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title )
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, false ) );	// $network = false
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		public function show_metabox_contact_fields() {
			$metabox = 'cm';
			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_cm_tabs', array( 
				'custom' => _x( 'Custom Contacts', 'metabox tab', 'wpsso' ),
				'builtin' => _x( 'Built-In Contacts', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title )
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, false ) );	// $network = false
			$this->p->util->do_table_rows( 
				array( '<td>'.$this->p->msgs->get( 'info-'.$metabox ).'</td>' ),
				'metabox-'.$metabox.'-info'
			);
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		public function show_metabox_taglist() {
			$metabox = 'taglist';
			$this->p->util->do_table_rows( 
				array( '<td>'.$this->p->msgs->get( 'info-'.$metabox ).'</td>' ),
				'metabox-'.$metabox.'-info'
			);
			$this->p->util->do_table_rows( 
				apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tags_rows', array(), $this->form ),
				'metabox-'.$metabox.'-tags'
			);
		}

		protected function get_table_rows( $metabox, $key ) {
			$table_rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'plugin-settings':

					$table_rows['plugin_clear_on_save'] = $this->form->get_th_html( _x( 'Clear All Cache(s) on Save Settings',
						'option label', 'wpsso' ), null, 'plugin_clear_on_save' ).
					'<td>'.$this->form->get_checkbox( 'plugin_clear_on_save' ).'</td>';

					$table_rows['plugin_preserve'] = $this->form->get_th_html( _x( 'Preserve Settings on Uninstall',
						'option label', 'wpsso' ), null, 'plugin_preserve' ).
					'<td>'.$this->form->get_checkbox( 'plugin_preserve' ).'</td>';

					$table_rows['plugin_debug'] = $this->form->get_th_html( _x( 'Add Hidden Debug Messages', 
						'option label', 'wpsso' ), null, 'plugin_debug' ).
					'<td>'.( SucomUtil::get_const( 'WPSSO_HTML_DEBUG' ) ? 
						$this->form->get_no_checkbox( 'plugin_debug' ).' <em>WPSSO_HTML_DEBUG constant is true</em>' :
						$this->form->get_checkbox( 'plugin_debug' ) ).'</td>';

					$table_rows['plugin_show_opts'] = $this->form->get_th_html( _x( 'Options to Show by Default',
						'option label', 'wpsso' ), null, 'plugin_show_opts' ).
					'<td>'.$this->form->get_select( 'plugin_show_opts', 
						$this->p->cf['form']['show_options'] ).'</td>';

					break;
			}
			return $table_rows;
		}
	}
}

?>
