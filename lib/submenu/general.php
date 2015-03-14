<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSubmenuGeneral' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuGeneral extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_opengraph', 'Open Graph Settings (All Publishers)', 
				array( &$this, 'show_metabox_opengraph' ), $this->pagehook, 'normal' );
			add_meta_box( $this->pagehook.'_publishers', 'Publisher Specific Settings', 
				array( &$this, 'show_metabox_publishers' ), $this->pagehook, 'normal' );

			// issues a warning notice if the default image size is too small
			$this->p->media->get_default_image( 1, $this->p->cf['lca'].'-opengraph', false );
		}

		public function show_metabox_opengraph() {
			$metabox = 'og';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'general' => 'General',
				'content' => 'Title and Description',
				'images' => 'Images',
				'videos' => 'Videos',
				'author' => 'Authorship' ) );
			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', array(), $this->form ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		public function show_metabox_publishers() {
			$metabox = 'pub';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'facebook' => 'Facebook',
				'google' => 'Google (G+ and Search)',
				'linkedin' => 'LinkedIn',
				'pinterest' => 'Pinterest Rich Pins',
				'twitter' => 'Twitter Cards',
			) );
			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', array(), $this->form ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		protected function get_rows( $metabox, $key ) {
			$rows = array();
			$this->form->user_ids = $this->p->mods['util']['user']->get_display_names();
			$this->form->author_contact_fields = $this->p->mods['util']['user']->get_contact_fields();

			switch ( $metabox.'-'.$key ) {
				case 'og-general':
					$rows[] = $this->p->util->th( 'Default Article Topic', 'highlight', 'og_art_section' ).
					'<td>'.$this->form->get_select( 'og_art_section', $this->p->util->get_topics() ).'</td>';

					$rows[] = $this->p->util->th( 'Site Name', null, 'og_site_name', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_locale_key( 'og_site_name' ), 
						null, null, null, get_bloginfo( 'name', 'display' ) ).'</td>';

					$rows[] = $this->p->util->th( 'Site Description', 'highlight', 'og_site_description', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_textarea( SucomUtil::get_locale_key( 'og_site_description' ), 
						null, null, null, get_bloginfo( 'description', 'display' ) ).'</td>';

					break;

				case 'og-content':
					$rows[] = $this->p->util->th( 'Title Separator', null, 'og_title_sep' ).
					'<td>'.$this->form->get_input( 'og_title_sep', 'short' ).'</td>';

					break;

				case 'og-images':
					$rows[] = $this->p->util->th( 'Max Images to Include', null, 'og_img_max' ).
					'<td>'.$this->form->get_select( 'og_img_max', 
						range( 0, $this->p->cf['form']['max_media_items'] ), 'short', null, true ).'</td>';

					$rows[] = $this->p->util->th( 'Open Graph Image Dimensions', 'highlight', 'og_img_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'og_img', false, false ).'</td>';
	
					$rows[] = $this->p->util->th( 'Default / Fallback Image ID', 'highlight', 'og_def_img_id' ).
					'<td>'.$this->form->get_image_upload_input( 'og_def_img' ).'</td>';
	
					$rows[] = $this->p->util->th( 'Default / Fallback Image URL', null, 'og_def_img_url' ).
					'<td>'.$this->form->get_image_url_input( 'og_def_img' ).'</td>';
	
					break;

				case 'og-videos':
					$rows[] = $this->p->util->th( 'Max Videos to Include', null, 'og_vid_max' ).
					'<td>'.$this->form->get_select( 'og_vid_max', 
						range( 0, $this->p->cf['form']['max_media_items'] ), 'short', null, true ).'</td>';
	
					break;

				case 'pub-facebook':
					$rows[] = $this->p->util->th( 'Publisher <em>Business Page</em> URL', 'highlight', 'fb_publisher_url' ).
					'<td>'.$this->form->get_input( 'fb_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'Facebook Admin(s)', 'highlight', 'fb_admins' ).
					'<td>'.$this->form->get_input( 'fb_admins' ).'</td>';

					$rows[] = $this->p->util->th( 'Facebook Application ID', null, 'fb_app_id' ).
					'<td>'.$this->form->get_input( 'fb_app_id' ).'</td>';

					$rows[] = $this->p->util->th( 'Default Language', null, 'fb_lang' ).
					'<td>'.$this->form->get_select( 'fb_lang', SucomUtil::get_pub_lang( 'facebook' ) ).'</td>';

					break;

				case 'pub-google':
					$rows[] = $this->p->util->th( 'Publisher <em>Business Page</em> URL', 'highlight', 'google_publisher_url' ).
					'<td>'.$this->form->get_input( 'seo_publisher_url', 'wide' ).'</td>';

					break;

				case 'pub-pinterest':
					$rows[] = '<td colspan="2" style="padding-bottom:10px;">'.$this->p->msgs->get( 'info-pub-pinterest' ).'</td>';

					break;
			}
			return $rows;
		}
	}
}

?>
