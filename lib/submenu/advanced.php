<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSubmenuAdvanced' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuAdvanced extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;	// lowercase acronyn for plugin or extension
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
				'integration' => _x( 'Integration', 'metabox tab', 'wpsso' ),
				'social' => _x( 'Custom Meta', 'metabox tab', 'wpsso' ),
				'cache' => _x( 'Cache Settings', 'metabox tab', 'wpsso' ),
				'apikeys' => _x( 'Service APIs', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows',
						array(), $this->form, false ) );	// $network = false
			}
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		public function show_metabox_contact_fields() {
			$metabox = 'cm';
			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_'.$metabox.'_tabs', array(
				'custom' => _x( 'Custom Contacts', 'metabox tab', 'wpsso' ),
				'builtin' => _x( 'Built-In Contacts', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows',
						array(), $this->form, false ) );	// $network = false
			}
			$this->p->util->do_table_rows( array( '<td>'.$this->p->msgs->get( 'info-'.$metabox ).'</td>' ),
				'metabox-'.$metabox.'-info' );
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		public function show_metabox_taglist() {
			$metabox = 'taglist';
			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_'.$metabox.'_tabs', array(
				'fb' => _x( 'Facebook', 'metabox tab', 'wpsso' ),
				'og' => _x( 'Open Graph', 'metabox tab', 'wpsso' ),
				'twitter' => _x( 'Twitter', 'metabox tab', 'wpsso' ),
				'schema' => _x( 'Schema', 'metabox tab', 'wpsso' ),
				'other' => _x( 'SEO / Other', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows',
						array(), $this->form, false ) );	// $network = false
			}
			$this->p->util->do_table_rows( array( '<td>'.$this->p->msgs->get( 'info-'.$metabox ).'</td>' ),
				'metabox-'.$metabox.'-info' );
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox, $key ) {
			$table_rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'plugin-settings':

					$this->add_essential_advanced_table_rows( $table_rows, $this->form );

					break;
			}
			return $table_rows;
		}
	}
}

?>
