<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSubmenuEssential' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuEssential extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_general',
				_x( 'General Settings', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_general' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_advanced',
				_x( 'Advanced Settings', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_advanced' ), $this->pagehook, 'normal' );

			// issues a warning notice if the default image size is too small
			if ( ! SucomUtil::get_const( 'WPSSO_CHECK_DEFAULT_IMAGE' ) )
				$og_image = $this->p->media->get_default_image( 1, $this->p->cf['lca'].'-opengraph', false );
		}

		public function show_metabox_general() {
			$metabox = $this->menu_id;
			$key = 'general';
			$rows[$key] = array_merge( $this->get_rows( $metabox, $key ),
				apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows',
					array(), $this->form, false ) );        // $network = false
			$this->p->util->do_table_rows( $rows[$key], 'metabox-'.$metabox.'-'.$key );
		}

		public function show_metabox_advanced() {
			$metabox = $this->menu_id;
			$key = 'advanced';
			$rows[$key] = array_merge( $this->get_rows( $metabox, $key ),
				apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows',
					array(), $this->form, false ) );        // $network = false
			$this->p->util->do_table_rows( $rows[$key], 'metabox-'.$metabox.'-'.$key );
		}

		protected function get_rows( $metabox, $key ) {
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'essential-general':

					$rows[] = '<td></td><td class="subsection top"><h4>'.
						_x( 'Site Information', 'metabox title', 'wpsso' ).'</h4></td>';

					$rows[] = $this->p->util->get_th( _x( 'Default Article Topic',
						'option label', 'wpsso' ), null, 'og_art_section' ).
					'<td>'.$this->form->get_select( 'og_art_section', $this->p->util->get_topics() ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'Site Name',
						'option label', 'wpsso' ), null, 'og_site_name', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_locale_key( 'og_site_name' ), 
						null, null, null, get_bloginfo( 'name', 'display' ) ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'Site Description',
						'option label', 'wpsso' ), null, 'og_site_description', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_textarea( SucomUtil::get_locale_key( 'og_site_description' ), 
						null, null, null, get_bloginfo( 'description', 'display' ) ).'</td>';

					$rows[] = '<td></td><td class="subsection"><h4>'.
						_x( 'Facebook / Open Graph', 'metabox title', 'wpsso' ).'</h4></td>';

					$rows[] = $this->p->util->get_th( _x( 'Facebook Business Page URL',
						'option label', 'wpsso' ), null, 'fb_publisher_url' ).
					'<td>'.$this->form->get_input( 'fb_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'Facebook Application ID',
						'option label', 'wpsso' ), null, 'fb_app_id' ).
					'<td>'.$this->form->get_input( 'fb_app_id' ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'or Facebook Admin Username(s)',
						'option label', 'wpsso' ), null, 'fb_admins' ).
					'<td>'.$this->form->get_input( 'fb_admins' ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'Default Content Language',
						'option label', 'wpsso' ), null, 'fb_lang' ).
					'<td>'.$this->form->get_select( 'fb_lang', SucomUtil::get_pub_lang( 'facebook' ) ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'Default / Fallback Image ID',
						'option label', 'wpsso' ), null, 'og_def_img_id' ).
					'<td>'.$this->form->get_image_upload_input( 'og_def_img' ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'or Default / Fallback Image URL',
						'option label', 'wpsso' ), null, 'og_def_img_url' ).
					'<td>'.$this->form->get_image_url_input( 'og_def_img' ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'Open Graph Image Dimensions',
						'option label', 'wpsso' ), null, 'og_img_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'og_img', false, false ).'</td>';

					$rows[] = '<td></td><td class="subsection"><h4>'.
						_x( 'Google / Schema', 'metabox title', 'wpsso' ).'</h4></td>';

					$rows[] = $this->p->util->get_th( _x( 'Google+ Business Page URL',
						'option label', 'wpsso' ), null, 'google_publisher_url' ).
					'<td>'.$this->form->get_input( 'seo_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->get_th( _x( 'Website / Business Logo URL',
						'option label', 'wpsso' ), null, 'google_schema_logo_url' ).
					'<td>'.$this->form->get_input( 'schema_logo_url', 'wide' ).'</td>';

					$rows[] = '<td></td><td class="subsection"><h4>'.
						_x( 'Pinterest', 'metabox title', 'wpsso' ).'</h4></td>';

					$rows[] = $this->p->util->get_th( _x( 'Pinterest Company Page URL',
						'option label', 'wpsso' ), null, 'rp_publisher_url'  ).
					'<td>'.$this->form->get_input( 'rp_publisher_url', 'wide' ).'</td>';

					if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) ) {
						$rows[] = $this->p->util->get_th( _x( 'Rich Pin Image Dimensions',
							'option label', 'wpsso' ), null, 'rp_img_dimensions' ).
						'<td>'.$this->form->get_image_dimensions_input( 'rp_img' ).'</td>';
					}

					$rows[] = '<td></td><td class="subsection"><h4>'.
						_x( 'Twitter', 'metabox title', 'wpsso' ).'</h4></td>';

					$rows[] = $this->p->util->get_th( _x( 'Twitter Business @username',
						'option label', 'wpsso' ), null, 'tc_site' ).
					'<td>'.$this->form->get_input( 'tc_site' ).'</td>';

					break;

				case 'essential-advanced':

					$rows['plugin_preserve'] = $this->p->util->get_th( _x( 'Preserve Settings on Uninstall',
						'option label', 'wpsso' ), null, 'plugin_preserve' ).
					'<td>'.$this->form->get_checkbox( 'plugin_preserve' ).'</td>';

					$rows['plugin_debug'] = $this->p->util->get_th( _x( 'Add Hidden Debug Messages', 
						'option label', 'wpsso' ), null, 'plugin_debug' ).
					'<td>'.( SucomUtil::get_const( 'WPSSO_HTML_DEBUG' ) ? 
						$this->form->get_no_checkbox( 'plugin_debug' ).' WPSSO_HTML_DEBUG constant enabled' :
						$this->form->get_checkbox( 'plugin_debug' ) ).'</td>';

					break;
			}
			return $rows;
		}
	}
}

?>
