<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSubmenuReadme' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuReadme extends WpssoAdmin {

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
			add_meta_box( $this->pagehook.'_readme',
				_x( 'Read Me', 'metabox title', 'wpsso' ),
					array( &$this, 'show_metabox_readme' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_readme() {
			$metabox = 'readme';
			$tabs = apply_filters( $this->p->cf['lca'].'_plugin_readme_tabs', array( 
				'description' => _x( 'Description', 'metabox tab', 'wpsso' ),
				'faq' => _x( 'FAQ', 'metabox tab', 'wpsso' ),
				'notes' => _x( 'Other Notes', 'metabox tab', 'wpsso' ),
				'changelog' => _x( 'Changelog', 'metabox tab', 'wpsso' ),
			) );
			$table_rows = array();
			foreach ( $tabs as $key => $title )
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', array(), $this->form ) );
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		protected function get_table_rows( $metabox, $key ) {
			$lca = $this->p->cf['lca'];
			$table_rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'readme-description':
					$table_rows[] = '<td>'.( empty( self::$readme_info[$lca]['sections']['description'] ) ? 
						'Content not Available' : self::$readme_info[$lca]['sections']['description'] ).'</td>';
					break;

				case 'readme-faq':
					$table_rows[] = '<td>'.( empty( self::$readme_info[$lca]['sections']['frequently_asked_questions'] ) ?
						'Content not Available' : self::$readme_info[$lca]['sections']['frequently_asked_questions'] ).'</td>';
					break;

				case 'readme-notes':
					$table_rows[] = '<td>'.( empty( self::$readme_info[$lca]['remaining_content'] ) ?
						'Content not Available' : self::$readme_info[$lca]['remaining_content'] ).'</td>';
					break;

				case 'readme-changelog':
					$table_rows[] = '<td>'.( empty( self::$readme_info[$lca]['sections']['changelog'] ) ?
						'Content not Available' : self::$readme_info[$lca]['sections']['changelog'] ).'</td>';
					break;
			}
			return $table_rows;
		}
	}
}

?>
