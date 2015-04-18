<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
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
			add_meta_box( $this->pagehook.'_opengraph', 'All Social Websites (Open Graph)',
				array( &$this, 'show_metabox_opengraph' ), $this->pagehook, 'normal' );
			add_meta_box( $this->pagehook.'_publishers', 'Specific Social Websites and Publishers',
				array( &$this, 'show_metabox_publishers' ), $this->pagehook, 'normal' );

			// issues a warning notice if the default image size is too small
			$this->p->media->get_default_image( 1, $this->p->cf['lca'].'-opengraph', false );
		}

		public function show_metabox_opengraph() {
			$metabox = 'og';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'general' => 'Site Information',
				'content' => 'Title / Description',
				'author' => 'Authorship',
				'images' => 'Images',
				'videos' => 'Videos',
			) );
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
				'google' => 'Google',
				'pinterest' => 'Pinterest',
				'twitter' => 'Twitter',
				'other' => 'Others',
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

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Title Length', null, 'og_title_len' ).
					'<td>'.$this->form->get_input( 'og_title_len', 'short' ).' characters or less</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Description Length', null, 'og_desc_len' ).
					'<td>'.$this->form->get_input( 'og_desc_len', 'short' ).' characters or less</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Content Starts at 1st Paragraph', null, 'og_desc_strip' ).
					'<td>'.$this->form->get_checkbox( 'og_desc_strip' ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Use Image Alt if Content is Empty', null, 'og_desc_alt' ).
					'<td>'.$this->form->get_checkbox( 'og_desc_alt' ).'</td>';

					$rows[] = $this->p->util->th( 'Add Hashtags to Descriptions', null, 'og_desc_hashtags' ).
					'<td>'.$this->form->get_select( 'og_desc_hashtags', 
						range( 0, $this->p->cf['form']['max_desc_hashtags'] ), 'short', null, true ).' tag names</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Add Page Title in Tags', null, 'og_page_title_tag' ).
					'<td>'.$this->form->get_checkbox( 'og_page_title_tag' ).'</td>';
		
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Add Page Ancestor Tags', null, 'og_page_parent_tags' ).
					'<td>'.$this->form->get_checkbox( 'og_page_parent_tags' ).'</td>';

					break;

				case 'og-author':

					$rows[] = $this->p->util->th( 'Author Profile URL Field', null, 'og_author_field' ).
					'<td>'.$this->form->get_select( 'og_author_field', $this->form->author_contact_fields ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Fallback to Author Index URL', null, 'og_author_fallback' ).
					'<td>'.$this->form->get_checkbox( 'og_author_fallback' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Default Author when Missing', null, 'og_def_author_id' ).
					'<td>'.$this->form->get_select( 'og_def_author_id', $this->form->user_ids, null, null, true ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Force Default Author on Indexes', null, 'og_def_author_on_index' ).
					'<td>'.$this->form->get_checkbox( 'og_def_author_on_index' ).' defines index / archive webpages as articles</td>';
			
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Default Author on Search Results', null, 'og_def_author_on_search' ).
					'<td>'.$this->form->get_checkbox( 'og_def_author_on_search' ).' defines search webpages as articles</td>';

					break;

				case 'og-images':

					$rows[] = $this->p->util->th( 'Max Images to Include', null, 'og_img_max' ).
					'<td>'.$this->form->get_select( 'og_img_max', 
						range( 0, $this->p->cf['form']['max_media_items'] ), 'short', null, true ).'</td>';

					$rows[] = $this->p->util->th( 'Open Graph Image Dimensions', 'highlight', 'og_img_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'og_img', false, false ).'</td>';
	
					$rows[] = $this->p->util->th( 'Default / Fallback Image ID', 'highlight', 'og_def_img_id' ).
					'<td>'.$this->form->get_image_upload_input( 'og_def_img' ).'</td>';
	
					$rows[] = $this->p->util->th( 'or Default / Fallback Image URL', null, 'og_def_img_url' ).
					'<td>'.$this->form->get_image_url_input( 'og_def_img' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Force Default Image on Indexes', null, 'og_def_img_on_index' ).
					'<td>'.$this->form->get_checkbox( 'og_def_img_on_index' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Force Default Image on Author Index', null, 'og_def_img_on_author' ).
					'<td>'.$this->form->get_checkbox( 'og_def_img_on_author' ).'</td>';
		
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Force Default Image on Search Results', null, 'og_def_img_on_search' ).
					'<td>'.$this->form->get_checkbox( 'og_def_img_on_search' ).'</td>';
		
					if ( $this->p->is_avail['media']['ngg'] === true ) {
						$rows[] = '<tr class="hide_in_basic">'.
						$this->p->util->th( 'Add Tags from NGG Featured Image', null, 'og_ngg_tags' ).
						'<td>'.$this->form->get_checkbox( 'og_ngg_tags' ).'</td>';
					}

					break;

				case 'og-videos':

					break;

				case 'pub-facebook':

					$rows[] = $this->p->util->th( 'Facebook Business Page URL', 'highlight', 'fb_publisher_url' ).
					'<td>'.$this->form->get_input( 'fb_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'Facebook Admin Username(s)', 'highlight', 'fb_admins' ).
					'<td>'.$this->form->get_input( 'fb_admins' ).'</td>';

					$rows[] = $this->p->util->th( 'Facebook Application ID', null, 'fb_app_id' ).
					'<td>'.$this->form->get_input( 'fb_app_id' ).'</td>';

					$rows[] = $this->p->util->th( 'Default Language', null, 'fb_lang' ).
					'<td>'.$this->form->get_select( 'fb_lang', SucomUtil::get_pub_lang( 'facebook' ) ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Author Name Format', 'highlight', 'google_author_name' ).
					'<td>'.$this->form->get_select( 'seo_author_name', $this->p->cf['form']['user_name_fields'] ).'</td>';

					break;

				case 'pub-google':

					$rows[] = $this->p->util->th( 'Google+ Business Page URL', 'highlight', 'google_publisher_url' ).
					'<td>'.$this->form->get_input( 'seo_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'Schema Website / Business Logo URL', null, 'google_schema_logo_url' ).
					'<td>'.$this->form->get_input( 'schema_logo_url', 'wide' ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Schema Meta Description Length', null, 'google_schema_desc_len' ).
					'<td>'.$this->form->get_input( 'schema_desc_len', 'short' ).' characters or less</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Search / SEO Description Length', null, 'google_seo_desc_len' ).
					'<td>'.$this->form->get_input( 'seo_desc_len', 'short' ).' characters or less</td>';

					$rows[] = $this->p->util->th( 'Author Link URL Field', null, 'google_author_field' ).
					'<td>'.$this->form->get_select( 'seo_author_field', $this->form->author_contact_fields ).'</td>';
		
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Default Author when Missing', null, 'google_def_author_id' ).
					'<td>'.$this->form->get_select( 'seo_def_author_id', $this->form->user_ids, null, null, true ).'</td>';
			
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Force Default Author on Indexes', null, 'google_def_author_on_index' ).
					'<td>'.$this->form->get_checkbox( 'seo_def_author_on_index' ).'</td>';
			
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Default Author on Search Results', null, 'google_def_author_on_search' ).
					'<td>'.$this->form->get_checkbox( 'seo_def_author_on_search' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Add Schema Publisher Social JSON', null, 'google_schema_publisher_json' ).
					'<td>'.$this->form->get_checkbox( 'schema_publisher_json' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Add Schema Author Social JSON', null, 'google_schema_author_json' ).
					'<td>'.$this->form->get_checkbox( 'schema_author_json' ).'</td>';

					break;

				case 'pub-pinterest':

					$rows[] = '<td colspan="2" style="padding-bottom:10px;">'.$this->p->msgs->get( 'info-pub-pinterest' ).'</td>';

					$rows[] = $this->p->util->th( 'Pinterest Company Page URL', null, 'rp_publisher_url'  ).
					'<td>'.$this->form->get_input( 'rp_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'Rich Pin Image Dimensions', 'highlight', 'rp_img_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'rp_img' ).'</td>';
			
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Author Name Format', null, 'rp_author_name' ).
					'<td>'.$this->form->get_select( 'rp_author_name', $this->p->cf['form']['user_name_fields'] ).'</td>';
		
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->th( 'Website Verification ID', null, 'rp_dom_verify' ).
					'<td>'.$this->form->get_input( 'rp_dom_verify', 'api_key' ).'</td>';
		
					break;

				case 'pub-twitter':

					$rows[] = $this->p->util->th( 'Twitter Business @username', 'highlight', 'tc_site' ).
					'<td>'.$this->form->get_input( 'tc_site' ).'</td>';

					break;

				case 'pub-other':

					$rows[] = $this->p->util->th( 'Instagram Business URL', null, 'instgram_publisher_url' ).
					'<td>'.$this->form->get_input( 'instgram_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'LinkedIn Company Page URL', null, 'linkedin_publisher_url'  ).
					'<td>'.$this->form->get_input( 'linkedin_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'MySpace Business (Brand) URL', null, 'myspace_publisher_url'  ).
					'<td>'.$this->form->get_input( 'myspace_publisher_url', 'wide' ).'</td>';

					break;
			}
			return $rows;
		}
	}
}

?>
