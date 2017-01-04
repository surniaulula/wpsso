<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
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
				_x( 'Head Tags List', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_taglist' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_plugin() {
			$metabox = 'plugin';
			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_'.$metabox.'_tabs', array( 
				'settings' => _x( 'Plugin Settings', 'metabox tab', 'wpsso' ),
				'content' => _x( 'Content and Filters', 'metabox tab', 'wpsso' ),
				'integration' => _x( 'WP / Theme Integration', 'metabox tab', 'wpsso' ),
				'social' => _x( 'Social / Custom Meta', 'metabox tab', 'wpsso' ),
				'cache' => _x( 'Cache Settings', 'metabox tab', 'wpsso' ),
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
			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_'.$metabox.'_tabs', array( 
				'custom' => _x( 'Custom Contacts', 'metabox tab', 'wpsso' ),
				'builtin' => _x( 'Built-In Contacts', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title )
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, false ) );	// $network = false
			$this->p->util->do_table_rows( array( '<td>'.$this->p->msgs->get( 'info-'.$metabox ).'</td>' ),
				'metabox-'.$metabox.'-info' );
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		public function show_metabox_taglist() {
			$metabox = 'taglist';
			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_'.$metabox.'_tabs', array( 
				'og' => _x( 'Open Graph', 'metabox tab', 'wpsso' ),
				'schema' => _x( 'Schema', 'metabox tab', 'wpsso' ),
				'twitter' => _x( 'Twitter', 'metabox tab', 'wpsso' ),
				'other' => _x( 'SEO / Other', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title )
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, false ) );	// $network = false
			$this->p->util->do_table_rows( array( '<td>'.$this->p->msgs->get( 'info-'.$metabox ).'</td>' ),
				'metabox-'.$metabox.'-info' );
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox, $key ) {
			$table_rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'plugin-settings':

					$table_rows['plugin_honor_force_ssl'] = $this->form->get_th_html( _x( 'Honor the FORCE_SSL Constant',
						'option label', 'wpsso' ), null, 'plugin_honor_force_ssl' ).
					'<td>'.$this->form->get_checkbox( 'plugin_honor_force_ssl' ).'</td>';

					$table_rows['plugin_clear_on_save'] = $this->form->get_th_html( _x( 'Clear Cache(s) on Save Settings',
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

					if ( ! $this->p->check->aop( $this->p->cf['lca'], true, $this->p->is_avail['aop'] ) )
						$table_rows['plugin_hide_pro'] = $this->form->get_th_html( _x( 'Hide All Pro Version Options',
							'option label', 'wpsso' ), null, 'plugin_hide_pro' ).
						'<td>'.$this->form->get_checkbox( 'plugin_hide_pro' ).'</td>';
					else $this->form->get_hidden( 'plugin_hide_pro',  0, true );

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
