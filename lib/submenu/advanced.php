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
			add_meta_box( $this->pagehook.'_plugin', 'Advanced Settings', 
				array( &$this, 'show_metabox_plugin' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_contact_fields', 'Profile Contact Fields', 
				array( &$this, 'show_metabox_contact_fields' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_taglist', 'Header Tags List', 
				array( &$this, 'show_metabox_taglist' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_plugin() {
			$metabox = 'plugin';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'settings' => 'Plugin Settings',
				'content' => 'Content and Filters',
				'social' => 'Social Settings Metabox',
				'cache' => 'File and Object Cache' ) );
			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', array(), $this->form ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		public function show_metabox_contact_fields() {
			$metabox = 'cm';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'custom' => 'Custom Contacts',
				'builtin' => 'Built-In Contacts' ) );
			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', array(), $this->form ) );
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

					$rows[] = $this->p->util->th( 'Plugin Options to Show by Default', 'highlight', 'plugin_show_opts' ).
					'<td>'.$this->form->get_select( 'plugin_show_opts', $this->p->cf['form']['show_options'] ).'</td>';

					$rows[] = $this->p->util->th( 'Add Hidden Debug Messages', null, 'plugin_debug' ).
					'<td>'.$this->form->get_checkbox( 'plugin_debug' ).'</td>';

					$rows[] = $this->p->util->th( 'Preserve Settings on Uninstall', 'highlight', 'plugin_preserve' ).
					'<td>'.$this->form->get_checkbox( 'plugin_preserve' ).'</td>';

					break;

				case 'plugin-content':

					$rows[] = $this->p->util->th( 'Use Filtered (SEO) Titles', 'highlight', 'plugin_filter_title' ).
					'<td>'.$this->form->get_checkbox( 'plugin_filter_title' ).'</td>';
			
					$rows[] = $this->p->util->th( 'Apply WordPress Content Filters', null, 'plugin_filter_content' ).
					'<td>'.$this->form->get_checkbox( 'plugin_filter_content' ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Apply WordPress Excerpt Filters', null, 'plugin_filter_excerpt' ).
					'<td>'.$this->form->get_checkbox( 'plugin_filter_excerpt' ).'</td>';

					break;
			}
			return $rows;
		}
	}
}

?>
