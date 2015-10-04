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
			add_meta_box( $this->pagehook.'_opengraph', _x( 'All Social Websites / Open Graph',
				'normal metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_opengraph' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_publishers', _x( 'Specific Websites and Publishers',
				'normal metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_publishers' ), $this->pagehook, 'normal' );

			// issues a warning notice if the default image size is too small
			$this->p->media->get_default_image( 1, $this->p->cf['lca'].'-opengraph' );
		}

		public function show_metabox_opengraph() {
			$metabox = 'og';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'general' => _x( 'Site Information', 'normal metabox tab', 'wpsso' ),
				'content' => _x( 'Title / Description', 'normal metabox tab', 'wpsso' ),
				'author' => _x( 'Authorship', 'normal metabox tab', 'wpsso' ),
				'images' => _x( 'Images', 'normal metabox tab', 'wpsso' ),
				'videos' => _x( 'Videos', 'normal metabox tab', 'wpsso' ),
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
				'facebook' => _x( 'Facebook', 'normal metabox tab', 'wpsso' ),
				'google' => _x( 'Google / Schema', 'normal metabox tab', 'wpsso' ),
				'pinterest' => _x( 'Pinterest', 'normal metabox tab', 'wpsso' ),
				'twitter' => _x( 'Twitter', 'normal metabox tab', 'wpsso' ),
				'other' => _x( 'Others', 'normal metabox tab', 'wpsso' ),
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

					$rows[] = $this->p->util->get_th( __( 'Default Article Topic',
						'wpsso' ), 'highlight', 'og_art_section' ).
					'<td>'.$this->form->get_select( 'og_art_section', $this->p->util->get_topics() ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Site Name',
						'wpsso' ), null, 'og_site_name', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_locale_key( 'og_site_name' ), 
						null, null, null, get_bloginfo( 'name', 'display' ) ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Site Description',
						'wpsso' ), 'highlight', 'og_site_description', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_textarea( SucomUtil::get_locale_key( 'og_site_description' ), 
						null, null, null, get_bloginfo( 'description', 'display' ) ).'</td>';

					break;

				case 'og-content':

					$rows[] = $this->p->util->get_th( __( 'Title Separator',
						'wpsso' ), null, 'og_title_sep' ).
					'<td>'.$this->form->get_input( 'og_title_sep', 'short' ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Maximum Title Length',
						'wpsso' ), null, 'og_title_len' ).
					'<td>'.$this->form->get_input( 'og_title_len', 'short' ).' characters or less</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Maximum Description Length',
						'wpsso' ), null, 'og_desc_len' ).
					'<td>'.$this->form->get_input( 'og_desc_len', 'short' ).' characters or less</td>';

					$rows[] = $this->p->util->get_th( __( 'Add Hashtags to Descriptions',
						'wpsso' ), null, 'og_desc_hashtags' ).
					'<td>'.$this->form->get_select( 'og_desc_hashtags', 
						range( 0, $this->p->cf['form']['max_hashtags'] ), 'short', null, true ).' tag names</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Add Page Title in Tags / Hashtags',
						'wpsso' ), null, 'og_page_title_tag' ).
					'<td>'.$this->form->get_checkbox( 'og_page_title_tag' ).'</td>';
		
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Add Parent Page Tags / Hashtags',
						'wpsso' ), null, 'og_page_parent_tags' ).
					'<td>'.$this->form->get_checkbox( 'og_page_parent_tags' ).'</td>';

					break;

				case 'og-author':

					$rows[] = $this->p->util->get_th( __( 'Author Profile URL Field',
						'wpsso' ), null, 'og_author_field' ).
					'<td>'.$this->form->get_select( 'og_author_field', $this->form->author_contact_fields ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Fallback to Author Index URL',
						'wpsso' ), null, 'og_author_fallback' ).
					'<td>'.$this->form->get_checkbox( 'og_author_fallback' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Default Author when Missing',
						'wpsso' ), null, 'og_def_author_id' ).
					'<td>'.$this->form->get_select( 'og_def_author_id', $this->form->user_ids, null, null, true ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Force Default Author on Indexes',
						'wpsso' ), null, 'og_def_author_on_index' ).
					'<td>'.$this->form->get_checkbox( 'og_def_author_on_index' ).' defines index / archive webpages as articles</td>';
			
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Default Author on Search Results',
						'wpsso' ), null, 'og_def_author_on_search' ).
					'<td>'.$this->form->get_checkbox( 'og_def_author_on_search' ).' defines search webpages as articles</td>';

					break;

				case 'og-images':

					$rows[] = $this->p->util->get_th( __( 'Max Images to Include',
						'wpsso' ), null, 'og_img_max' ).
					'<td>'.$this->form->get_select( 'og_img_max', 
						range( 0, $this->p->cf['form']['max_media_items'] ), 'short', null, true ).
					( empty( $this->form->options['og_vid_prev_img'] ) ?
						'' : '&nbsp;&nbsp;<em>video preview images are enabled</em> and (when available) are included first' ).
					'</td>';

					$rows[] = $this->p->util->get_th( __( 'Open Graph Image Dimensions',
						'wpsso' ), 'highlight', 'og_img_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'og_img', false, false ).'</td>';
	
					$rows[] = $this->p->util->get_th( __( 'Default / Fallback Image ID',
						'wpsso' ), 'highlight', 'og_def_img_id' ).
					'<td>'.$this->form->get_image_upload_input( 'og_def_img' ).'</td>';
	
					$rows[] = $this->p->util->get_th( __( 'or Default / Fallback Image URL',
						'wpsso' ), null, 'og_def_img_url' ).
					'<td>'.$this->form->get_image_url_input( 'og_def_img' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Force Default Image on Indexes',
						'wpsso' ), null, 'og_def_img_on_index' ).
					'<td>'.$this->form->get_checkbox( 'og_def_img_on_index' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Force Default Image on Search Results',
						'wpsso' ), null, 'og_def_img_on_search' ).
					'<td>'.$this->form->get_checkbox( 'og_def_img_on_search' ).'</td>';
		
					if ( $this->p->is_avail['media']['ngg'] === true ) {
						$rows[] = '<tr class="hide_in_basic">'.
						$this->p->util->get_th( __( 'Add Tags from NGG Featured Image',
							'wpsso' ), null, 'og_ngg_tags' ).
						'<td>'.$this->form->get_checkbox( 'og_ngg_tags' ).'</td>';
					}

					break;

				case 'og-videos':

					break;

				case 'pub-facebook':

					$rows[] = $this->p->util->get_th( __( 'Facebook Business Page URL',
						'wpsso' ), 'highlight', 'fb_publisher_url' ).
					'<td>'.$this->form->get_input( 'fb_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Facebook Admin Username(s)',
						'wpsso' ), 'highlight', 'fb_admins' ).
					'<td>'.$this->form->get_input( 'fb_admins' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Facebook Application ID',
						'wpsso' ), null, 'fb_app_id' ).
					'<td>'.$this->form->get_input( 'fb_app_id' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Default Language',
						'wpsso' ), null, 'fb_lang' ).
					'<td>'.$this->form->get_select( 'fb_lang', SucomUtil::get_pub_lang( 'facebook' ) ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Author Name Format',
						'wpsso' ), 'highlight', 'google_author_name' ).
					'<td>'.$this->form->get_select( 'seo_author_name', $this->p->cf['form']['user_name_fields'] ).'</td>';

					break;

				case 'pub-google':

					$rows[] = $this->p->util->get_th( __( 'Google+ Business Page URL',
						'wpsso' ), 'highlight', 'google_publisher_url' ).
					'<td>'.$this->form->get_input( 'seo_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Schema Website / Business Logo URL',
						'wpsso' ), null, 'google_schema_logo_url' ).
					'<td>'.$this->form->get_input( 'schema_logo_url', 'wide' ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Schema Meta Description Length',
						'wpsso' ), null, 'google_schema_desc_len' ).
					'<td>'.$this->form->get_input( 'schema_desc_len', 'short' ).' characters or less</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Search / SEO Description Length',
						'wpsso' ), null, 'google_seo_desc_len' ).
					'<td>'.$this->form->get_input( 'seo_desc_len', 'short' ).' characters or less</td>';

					$rows[] = $this->p->util->get_th( __( 'Author Link URL Field',
						'wpsso' ), null, 'google_author_field' ).
					'<td>'.$this->form->get_select( 'seo_author_field', $this->form->author_contact_fields ).'</td>';
		
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Default Author when Missing',
						'wpsso' ), null, 'google_def_author_id' ).
					'<td>'.$this->form->get_select( 'seo_def_author_id', $this->form->user_ids, null, null, true ).'</td>';
			
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Force Default Author on Indexes',
						'wpsso' ), null, 'google_def_author_on_index' ).
					'<td>'.$this->form->get_checkbox( 'seo_def_author_on_index' ).'</td>';
			
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Default Author on Search Results',
						'wpsso' ), null, 'google_def_author_on_search' ).
					'<td>'.$this->form->get_checkbox( 'seo_def_author_on_search' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Add Schema Website JSON-LD',
						'wpsso' ), null, 'google_schema_website_json' ).
					'<td>'.$this->form->get_checkbox( 'schema_website_json' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Add Schema Publisher JSON-LD',
						'wpsso' ), null, 'google_schema_publisher_json' ).
					'<td>'.$this->form->get_checkbox( 'schema_publisher_json' ).'</td>';
	
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Add Schema Author JSON-LD',
						'wpsso' ), null, 'google_schema_author_json' ).
					'<td>'.$this->form->get_checkbox( 'schema_author_json' ).'</td>';

					break;

				case 'pub-pinterest':

					$rows[] = '<td colspan="2" style="padding-bottom:10px;">'.
						$this->p->msgs->get( 'info-pub-pinterest' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Pinterest Company Page URL',
						'wpsso' ), null, 'rp_publisher_url'  ).
					'<td>'.$this->form->get_input( 'rp_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Rich Pin Image Dimensions',
						'wpsso' ), 'highlight', 'rp_img_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'rp_img' ).'</td>';
			
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Author Name Format',
						'wpsso' ), null, 'rp_author_name' ).
					'<td>'.$this->form->get_select( 'rp_author_name', $this->p->cf['form']['user_name_fields'] ).'</td>';
		
					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Website Verification ID',
						'wpsso' ), null, 'rp_dom_verify' ).
					'<td>'.$this->form->get_input( 'rp_dom_verify', 'api_key' ).'</td>';
		
					break;

				case 'pub-twitter':

					$rows[] = '<td colspan="2" style="padding-bottom:10px;">'.
						$this->p->msgs->get( 'info-pub-twitter' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'Twitter Business @username',
						'wpsso' ), 'highlight', 'tc_site' ).
					'<td>'.$this->form->get_input( 'tc_site' ).'</td>';

					$rows[] = '<tr class="hide_in_basic">'.
					$this->p->util->get_th( __( 'Maximum Description Length',
						'wpsso' ), null, 'tc_desc_len' ).
					'<td>'.$this->form->get_input( 'tc_desc_len', 'short' ).' characters or less</td>';

					$rows[] = $this->p->util->get_th( __( '<em>Summary</em> Card Image Dimensions',
						'wpsso' ), null, 'tc_sum_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'tc_sum', false, false ).'</td>';

					$rows[] = $this->p->util->get_th( __( '<em>Large Image</em> Card Image Dimensions',
						'wpsso' ), null, 'tc_lrgimg_dimensions' ).
					'<td>'.$this->form->get_image_dimensions_input( 'tc_lrgimg', false, false ).'</td>';

					break;

				case 'pub-other':

					$rows[] = $this->p->util->get_th( __( 'Instagram Business URL',
						'wpsso' ), null, 'instgram_publisher_url' ).
					'<td>'.$this->form->get_input( 'instgram_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'LinkedIn Company Page URL',
						'wpsso' ), null, 'linkedin_publisher_url'  ).
					'<td>'.$this->form->get_input( 'linkedin_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->get_th( __( 'MySpace Business (Brand) URL',
						'wpsso' ), null, 'myspace_publisher_url'  ).
					'<td>'.$this->form->get_input( 'myspace_publisher_url', 'wide' ).'</td>';

					break;
			}
			return $rows;
		}
	}
}

?>
