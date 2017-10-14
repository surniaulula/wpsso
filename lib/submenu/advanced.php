<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
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

		// called by the extended WpssoAdmin class
		protected function add_meta_boxes() {

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
			/*
			 * Translate contact method field labels for current language.
			 */
			SucomUtil::transl_key_values( '/^plugin_(cm_.*_label|.*_prefix)$/', $this->p->options, 'wpsso' );

			$metabox_id = 'plugin';
			$table_rows = array();

			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_'.$metabox_id.'_tabs', array(
				'settings' => _x( 'Plugin Settings', 'metabox tab', 'wpsso' ),
				'content' => _x( 'Content and Filters', 'metabox tab', 'wpsso' ),
				'integration' => _x( 'Integration', 'metabox tab', 'wpsso' ),
				'custom_meta' => _x( 'Custom Meta', 'metabox tab', 'wpsso' ),
				'cache' => _x( 'Cache Settings', 'metabox tab', 'wpsso' ),
				'apikeys' => _x( 'Service APIs', 'metabox tab', 'wpsso' ),
			) );

			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox_id, $key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox_id.'_'.$key.'_rows',
						array(), $this->form, false ) );	// $network = false
			}

			$this->p->util->do_metabox_tabs( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_contact_fields() {

			$metabox_id = 'cm';
			$table_rows = array();
			$info_msg = $this->p->msgs->get( 'info-'.$metabox_id );

			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_'.$metabox_id.'_tabs', array(
				'custom' => _x( 'Custom Contacts', 'metabox tab', 'wpsso' ),
				'builtin' => _x( 'Built-In Contacts', 'metabox tab', 'wpsso' ),
			) );

			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox_id, $key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox_id.'_'.$key.'_rows',
						array(), $this->form, false ) );	// $network = false
			}

			$this->p->util->do_table_rows( array( '<td>'.$info_msg.'</td>' ), 'metabox-'.$metabox_id.'-info' );
			$this->p->util->do_metabox_tabs( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_taglist() {
			$metabox_id = 'taglist';
			$tabs = apply_filters( $this->p->cf['lca'].'_advanced_'.$metabox_id.'_tabs', array(
				'fb' => _x( 'Facebook', 'metabox tab', 'wpsso' ),
				'og' => _x( 'Open Graph', 'metabox tab', 'wpsso' ),
				'twitter' => _x( 'Twitter', 'metabox tab', 'wpsso' ),
				'schema' => _x( 'Schema', 'metabox tab', 'wpsso' ),
				'other' => _x( 'SEO / Other', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox_id, $key ),
					apply_filters( $this->p->cf['lca'].'_'.$metabox_id.'_'.$key.'_rows',
						array(), $this->form, false ) );	// $network = false
			}
			$this->p->util->do_table_rows( array( '<td>'.$this->p->msgs->get( 'info-'.$metabox_id ).'</td>' ),
				'metabox-'.$metabox_id.'-info' );
			$this->p->util->do_metabox_tabs( $metabox_id, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox_id, $key ) {
			$table_rows = array();
			switch ( $metabox_id.'-'.$key ) {
				case 'plugin-settings':

					$this->add_essential_advanced_table_rows( $table_rows );

					break;
			}
			return $table_rows;
		}
	}
}

?>
