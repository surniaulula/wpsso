<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSubmenuEssential' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuEssential extends WpssoAdmin {

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
			add_meta_box( $this->pagehook.'_general',
				_x( 'Essential General Settings', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_general' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_advanced',
				_x( 'Essential Advanced Settings', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_advanced' ), $this->pagehook, 'normal' );

			// issues a warning notice if the default image size is too small
			// unless the WPSSO_CHECK_DEFAULT_IMAGE constant has been defined as false
			if ( SucomUtil::get_const( 'WPSSO_CHECK_DEFAULT_IMAGE' ) !== false )
				$og_image = $this->p->media->get_default_image( 1, $this->p->cf['lca'].'-opengraph', false );
		}

		public function show_metabox_general() {
			$metabox = $this->menu_id;
			$key = 'general';
			$this->p->util->do_table_rows( apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows',
				$this->get_table_rows( $metabox, $key ), $this->form, false ), 'metabox-'.$metabox.'-'.$key );
		}

		public function show_metabox_advanced() {
			$metabox = $this->menu_id;
			$key = 'advanced';
			$this->p->util->do_table_rows( apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows',
				$this->get_table_rows( $metabox, $key ), $this->form, false ), 'metabox-'.$metabox.'-'.$key );
		}

		protected function get_table_rows( $metabox, $key ) {
			$table_rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'essential-general':

					$table_rows['subsection_site_information'] = '<td></td><td class="subsection top"><h4>'.
						_x( 'Site Information', 'metabox title', 'wpsso' ).'</h4></td>';

					$table_rows['og_art_section'] = $this->form->get_th_html( _x( 'Default Article Topic',
						'option label', 'wpsso' ), null, 'og_art_section' ).
					'<td>'.$this->form->get_select( 'og_art_section', $this->p->util->get_article_topics() ).'</td>';

					$table_rows['og_site_name'] = $this->form->get_th_html( _x( 'Website Name',
						'option label', 'wpsso' ), null, 'og_site_name', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'og_site_name', $this->p->options ),
						null, null, null, get_bloginfo( 'name', 'display' ) ).'</td>';

					$table_rows['og_site_description'] = $this->form->get_th_html( _x( 'Website Description',
						'option label', 'wpsso' ), null, 'og_site_description', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_textarea( SucomUtil::get_key_locale( 'og_site_description', $this->p->options ),
						null, null, null, get_bloginfo( 'description', 'display' ) ).'</td>';

					$table_rows['subsection_opengraph'] = '<td></td><td class="subsection"><h4>'.
						_x( 'Facebook / Open Graph', 'metabox title', 'wpsso' ).'</h4></td>';

					$table_rows['fb_publisher_url'] = $this->form->get_th_html( _x( 'Facebook Business Page URL',
						'option label', 'wpsso' ), null, 'fb_publisher_url', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'fb_publisher_url', $this->p->options ), 'wide' ).'</td>';

					$table_rows['fb_app_id'] = $this->form->get_th_html( _x( 'Facebook Application ID',
						'option label', 'wpsso' ), null, 'fb_app_id' ).
					'<td>'.$this->form->get_input( 'fb_app_id' ).'</td>';

					$table_rows['fb_admins'] = $this->form->get_th_html( _x( 'or Facebook Admin Username(s)',
						'option label', 'wpsso' ), null, 'fb_admins' ).
					'<td>'.$this->form->get_input( 'fb_admins' ).'</td>';

					$table_rows['og_def_img_id'] = $this->form->get_th_html( _x( 'Default / Fallback Image ID',
						'option label', 'wpsso' ), null, 'og_def_img_id' ).
					'<td>'.$this->form->get_image_upload_input( 'og_def_img' ).'</td>';

					$table_rows['og_def_img_url'] = $this->form->get_th_html( _x( 'or Default / Fallback Image URL',
						'option label', 'wpsso' ), null, 'og_def_img_url' ).
					'<td>'.$this->form->get_image_url_input( 'og_def_img' ).'</td>';

					$table_rows['subsection_google_schema'] = '<td></td><td class="subsection"><h4>'.
						_x( 'Google / Schema', 'metabox title', 'wpsso' ).'</h4></td>';

					$table_rows['seo_publisher_url'] = $this->form->get_th_html( _x( 'Google+ Business Page URL',
						'option label', 'wpsso' ), null, 'seo_publisher_url', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'seo_publisher_url', $this->p->options ), 'wide' ).'</td>';

					$users = SucomUtil::get_user_select( array( 'editor', 'administrator' ) );

					$table_rows['schema_social_json'] = $this->form->get_th_html( _x( 'Google Knowledge Graph',
						'option label', 'wpsso' ), null, 'schema_social_json' ).
					'<td>'.
					'<p>'.$this->form->get_checkbox( 'schema_website_json' ).' '.
						sprintf( __( 'Include <a href="%s">Website Information</a> for Google Search',
							'wpsso' ), 'https://developers.google.com/structured-data/site-name' ).'</p>'.
					'<p>'.$this->form->get_checkbox( 'schema_organization_json' ).' '.
						sprintf( __( 'Include <a href="%s">Organization Social Profile</a>',
							'wpsso' ), 'https://developers.google.com/structured-data/customize/social-profiles' ).'</p>'.
					'<p>'.$this->form->get_checkbox( 'schema_person_json' ).' '.
						sprintf( __( 'Include <a href="%s">Person Social Profile</a> for Site Owner',
							'wpsso' ), 'https://developers.google.com/structured-data/customize/social-profiles' ).' '.
								$this->form->get_select( 'schema_person_id', $users, null, null, true ).'</p>'.
					'</td>';

					$table_rows['schema_logo_url'] = $this->form->get_th_html( 
						'<a href="https://developers.google.com/structured-data/customize/logos" target="_blank">'.
						_x( 'Organization Logo Image URL', 'option label', 'wpsso' ).'</a>', null, 'schema_logo_url' ).
					'<td>'.$this->form->get_input( 'schema_logo_url', 'wide' ).'</td>';

					$table_rows['subsection_pinterest'] = '<td></td><td class="subsection"><h4>'.
						_x( 'Pinterest', 'metabox title', 'wpsso' ).'</h4></td>';

					$table_rows['rp_publisher_url'] = $this->form->get_th_html( _x( 'Pinterest Company Page URL',
						'option label', 'wpsso' ), null, 'rp_publisher_url', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'rp_publisher_url', $this->p->options ), 'wide' ).'</td>';

					$table_rows['subsection_twitter'] = '<td></td><td class="subsection"><h4>'.
						_x( 'Twitter', 'metabox title', 'wpsso' ).'</h4></td>';

					$table_rows['tc_site'] = $this->form->get_th_html( _x( 'Twitter Business @username',
						'option label', 'wpsso' ), null, 'tc_site', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'tc_site', $this->p->options ) ).'</td>';

					break;

				case 'essential-advanced':

					$table_rows['plugin_clear_on_save'] = $this->form->get_th_html( _x( 'Clear Cache(s) on Save Settings',
						'option label', 'wpsso' ), null, 'plugin_clear_on_save' ).
					'<td>'.$this->form->get_checkbox( 'plugin_clear_on_save' ).'</td>';

					$table_rows['plugin_preserve'] = $this->form->get_th_html( _x( 'Preserve Settings on Uninstall',
						'option label', 'wpsso' ), null, 'plugin_preserve' ).
					'<td>'.$this->form->get_checkbox( 'plugin_preserve' ).'</td>';

					break;
			}
			return $table_rows;
		}
	}
}

?>
