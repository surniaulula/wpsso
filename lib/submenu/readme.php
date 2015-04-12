<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSubmenuReadme' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuReadme extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_readme', 'Read Me', array( &$this, 'show_metabox_readme' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_readme() {
			$metabox = 'readme';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'description' => 'Description',
				'faq' => 'FAQ',
				'notes' => 'Other Notes',
				'changelog' => 'Changelog' ) );
			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', array(), $this->form ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}
		
		protected function get_rows( $metabox, $key ) {
			$lca = $this->p->cf['lca'];
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'readme-description':
					$rows[] = '<td>'.( empty( $this->p->admin->readme_info[$lca]['sections']['description'] ) ? 
						'Content not Available' : $this->p->admin->readme_info[$lca]['sections']['description'] ).'</td>';
					break;

				case 'readme-faq':
					$rows[] = '<td>'.( empty( $this->p->admin->readme_info[$lca]['sections']['frequently_asked_questions'] ) ?
						'Content not Available' : $this->p->admin->readme_info[$lca]['sections']['frequently_asked_questions'] ).'</td>';
					break;

				case 'readme-notes':
					$rows[] = '<td>'.( empty( $this->p->admin->readme_info[$lca]['remaining_content'] ) ?
						'Content not Available' : $this->p->admin->readme_info[$lca]['remaining_content'] ).'</td>';
					break;

				case 'readme-changelog':
					$rows[] = '<td>'.( empty( $this->p->admin->readme_info[$lca]['sections']['changelog'] ) ?
						'Content not Available' : $this->p->admin->readme_info[$lca]['sections']['changelog'] ).'</td>';
					break;
			}
			return $rows;
		}
	}
}

?>
