<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
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

			if ( WpssoUser::show_opts( 'all' ) )
				add_meta_box( $this->pagehook.'_taglist', 'Header Tags List', 
					array( &$this, 'show_metabox_taglist' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_plugin() {
			$metabox = 'plugin';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'settings' => 'Plugin Settings',
				'content' => 'Content and Filters',
				'social' => 'Custom Social Settings',
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

			echo '<table class="sucom-setting" style="padding-bottom:0"><tr><td>'.
			$this->p->msgs->get( 'info-'.$metabox ).'</td></tr></table>';
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		public function show_metabox_taglist() {
			$metabox = 'taglist';
			echo '<table class="sucom-setting" style="padding-bottom:0;"><tr><td>'.
			$this->p->msgs->get( 'info-'.$metabox ).
			'</td></tr></table>';
			echo '<table class="sucom-setting" style="margin-bottom:10px;">';
			foreach ( apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tags_rows', array(), $this->form ) as $num => $row ) 
				echo '<tr>'.$row.'</tr>';
			echo '</table>';
		}

		protected function get_rows( $metabox, $key ) {
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'plugin-settings':

					$rows[] = $this->p->util->th( 'Plugin Options to Show by Default', 'highlight', 'plugin_show_opts' ).
					'<td>'.$this->form->get_select( 'plugin_show_opts', $this->p->cf['form']['show_options'] ).'</td>';

					$rows[] = $this->p->util->th( 'Preserve Settings on Uninstall', 'highlight', 'plugin_preserve' ).
					'<td>'.$this->form->get_checkbox( 'plugin_preserve' ).'</td>';

					$rows[] = $this->p->util->th( 'Add Hidden Debug Messages', null, 'plugin_debug' ).
					'<td>'.$this->form->get_checkbox( 'plugin_debug' ).'</td>';

					break;

				case 'plugin-content':

					$rows[] = $this->p->util->th( 'Use Filtered (SEO) Titles', 'highlight', 'plugin_filter_title' ).
					'<td>'.$this->form->get_checkbox( 'plugin_filter_title' ).'</td>';
			
					if ( WpssoUser::show_opts( 'all' ) ) {

						$rows[] = $this->p->util->th( 'Apply Excerpt Filters', null, 'plugin_filter_excerpt' ).
						'<td>'.$this->form->get_checkbox( 'plugin_filter_excerpt' ).'</td>';
					}

					$rows[] = $this->p->util->th( 'Apply Content Filters', null, 'plugin_filter_content' ).
					'<td>'.$this->form->get_checkbox( 'plugin_filter_content' ).'</td>';

					break;
					
				case 'cm-custom' :
					if ( ! $this->p->check->aop() )
						$rows[] = '<td colspan="4" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';
					$rows[] = '<td></td>'.
					$this->p->util->th( 'Show', 'left checkbox' ).
					$this->p->util->th( 'Contact Field Name', 'left medium', 'custom-cm-field-name' ).
					$this->p->util->th( 'Profile Contact Label', 'left wide' );
					$sorted_opt_pre = $this->p->cf['opt']['pre'];
					ksort( $sorted_opt_pre );
					foreach ( $sorted_opt_pre as $id => $pre ) {
						$cm_opt = 'plugin_cm_'.$pre.'_';

						// check for the lib website classname for a nice 'display name'
						$name = empty( $this->p->cf['*']['lib']['website'][$id] ) ? 
							ucfirst( $id ) : $this->p->cf['*']['lib']['website'][$id];
						$name = $name == 'GooglePlus' ? 'Google+' : $name;

						switch ( $id ) {
							case 'facebook':
							case 'gplus':
							case 'twitter':
							case ( WpssoUser::show_opts( 'all' ) ):
								// not all social websites have a contact method field
								if ( array_key_exists( $cm_opt.'enabled', $this->p->options ) ) {
									if ( $this->p->check->aop() ) {
										$rows[] = $this->p->util->th( $name, 'medium' ).
										'<td class="checkbox">'.$this->form->get_checkbox( $cm_opt.'enabled' ).'</td>'.
										'<td>'.$this->form->get_input( $cm_opt.'name', 'medium' ).'</td>'.
										'<td>'.$this->form->get_input( $cm_opt.'label' ).'</td>';
									} else {
										$rows[] = $this->p->util->th( $name, 'medium' ).
										'<td class="blank checkbox">'.$this->form->get_no_checkbox( $cm_opt.'enabled' ).'</td>'.
										'<td class="blank">'.$this->form->get_no_input( $cm_opt.'name', 'medium' ).'</td>'.
										'<td class="blank">'.$this->form->get_no_input( $cm_opt.'label' ).'</td>';
									}
								}
								break;
						}
					
					}
					break;

				case 'cm-builtin' :
					if ( ! $this->p->check->aop() )
						$rows[] = '<td colspan="4" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';
					$rows[] = '<td></td>'.
					$this->p->util->th( 'Show', 'left checkbox' ).
					$this->p->util->th( 'Contact Field Name', 'left medium', 'wp-cm-field-name' ).
					$this->p->util->th( 'Profile Contact Label', 'left wide' );
					$sorted_wp_contact = $this->p->cf['wp']['cm'];
					ksort( $sorted_wp_contact );
					foreach ( $sorted_wp_contact as $id => $name ) {
						$cm_opt = 'wp_cm_'.$id.'_';
						if ( array_key_exists( $cm_opt.'enabled', $this->p->options ) ) {
							if ( $this->p->check->aop() ) {
								$rows[] = $this->p->util->th( $name, 'medium' ).
								'<td class="checkbox">'.$this->form->get_checkbox( $cm_opt.'enabled' ).'</td>'.
								'<td>'.$this->form->get_no_input( $cm_opt.'name', 'medium' ).'</td>'.
								'<td>'.$this->form->get_input( $cm_opt.'label' ).'</td>';
							} else {
								$rows[] = $this->p->util->th( $name, 'medium' ).
								'<td class="blank checkbox">'.$this->form->get_hidden( $cm_opt.'enabled' ).
									$this->form->get_no_checkbox( $cm_opt.'enabled' ).'</td>'.
								'<td>'.$this->form->get_no_input( $cm_opt.'name', 'medium' ).'</td>'.
								'<td class="blank">'.$this->form->get_no_input( $cm_opt.'label' ).'</td>';
							}
						}
					}
					break;
			}
			return $rows;
		}
	}
}

?>
