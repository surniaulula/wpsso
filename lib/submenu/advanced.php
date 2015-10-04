<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSubmenuAdvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuAdvanced extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_plugin', _x( 'Advanced Settings', 
				'normal metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_plugin' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_contact_fields', _x( 'Contact Field Names and Labels', 
				'normal metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_contact_fields' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_taglist', _x( 'Header Tags List', 
				'normal metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_taglist' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_plugin() {
			$metabox = 'plugin';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'settings' => _x( 'Plugin Settings', 'normal metabox tab', 'wpsso' ),
				'content' => _x( 'Content and Filters', 'normal metabox tab', 'wpsso' ),
				'social' => _x( 'Social Settings Metabox', 'normal metabox tab', 'wpsso' ),
				'integration' => _x( 'Theme Integration', 'normal metabox tab', 'wpsso' ),
				'cache' => _x( 'File and Object Cache', 'normal metabox tab', 'wpsso' ),
				'apikeys' => _x( 'Service API Keys', 'normal metabox tab', 'wpsso' ),
			) );
			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, false ) );	// $network = false
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		public function show_metabox_contact_fields() {
			$metabox = 'cm';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'custom' => _x( 'Custom Contacts', 'normal metabox tab', 'wpsso' ),
				'builtin' => _x( 'Built-In Contacts', 'normal metabox tab', 'wpsso' ),
			) );
			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, false ) );	// $network = false
			$this->p->util->do_table_rows( 
				array( '<td>'.$this->p->msgs->get( 'info-'.$metabox ).'</td>' ),
				'metabox-'.$metabox.'-info'
			);
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		public function show_metabox_taglist() {
			$metabox = 'taglist';
			$this->p->util->do_table_rows( 
				array( '<td>'.$this->p->msgs->get( 'info-'.$metabox ).'</td>' ),
				'metabox-'.$metabox.'-info'
			);
			$this->p->util->do_table_rows( apply_filters(
				$this->p->cf['lca'].'_'.$metabox.'_tags_rows', array(), $this->form ),
				'metabox-'.$metabox.'-tags'
			);
		}

		protected function get_rows( $metabox, $key ) {
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'plugin-settings':

					$rows['plugin_debug'] = $this->p->util->get_th( __( 'Add Hidden Debug Messages', 
						'wpsso' ), null, 'plugin_debug' ).
					'<td>'.( defined( 'WPSSO_HTML_DEBUG' ) && WPSSO_HTML_DEBUG ? 
						$this->form->get_no_checkbox( 'plugin_debug' ).' WPSSO_HTML_DEBUG constant enabled' :
						$this->form->get_checkbox( 'plugin_debug' ) ).'</td>';

					$rows['plugin_preserve'] = $this->p->util->get_th( __( 'Preserve Settings on Uninstall',
						'wpsso' ), 'highlight', 'plugin_preserve' ).
					'<td>'.$this->form->get_checkbox( 'plugin_preserve' ).'</td>';

					break;
			}
			return $rows;
		}
	}
}

?>
