<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplAdminGeneral' ) ) {

	class WpssoGplAdminGeneral {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'og_content_rows' => 2,
				'og_images_rows' => 2,
				'og_videos_rows' => 2,
				'og_author_rows' => 2,
				'pub_facebook_rows' => 2,
				'pub_google_rows' => 2,
				'pub_linkedin_rows' => 2,
				'pub_pinterest_rows' => 2,
				'pub_twitter_rows' => 2,
			) );
		}

		public function filter_og_content_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Title Length', null, 'og_title_len' ).
				'<td class="blank">'.$this->p->options['og_title_len'].' characters or less</td>';

				$rows[] = $this->p->util->th( 'Description Length', null, 'og_desc_len' ).
				'<td class="blank">'.$this->p->options['og_desc_len'].' characters or less</td>';

				$rows[] = $this->p->util->th( 'Content Starts at 1st Paragraph', null, 'og_desc_strip' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_desc_strip' ).'</td>';

				$rows[] = $this->p->util->th( 'Use Image(s) Alt if No Content', null, 'og_desc_alt' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_desc_alt' ).'</td>';
			}

			$rows[] = $this->p->util->th( 'Hashtags in Description', null, 'og_desc_hashtags' ).
			'<td class="blank">'.$this->p->options['og_desc_hashtags'].' tag names</td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Add Page Title in Tags', null, 'og_page_title_tag' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_page_title_tag' ).'</td>';
		
				$rows[] = $this->p->util->th( 'Add Page Ancestor Tags', null, 'og_page_parent_tags' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_page_parent_tags' ).'</td>';
			}

			return $rows;
		}

		public function filter_og_images_rows( $rows, $form ) {

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

				$rows[] = $this->p->util->th( 'Force Default Image on Indexes', null, 'og_def_img_on_index' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_def_img_on_index' ).'</td>';

				$rows[] = $this->p->util->th( 'Force Default Image on Author Index', null, 'og_def_img_on_author' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_def_img_on_author' ).'</td>';
	
				$rows[] = $this->p->util->th( 'Force Default Image on Search Results', null, 'og_def_img_on_search' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_def_img_on_search' ).'</td>';
	
				if ( $this->p->is_avail['media']['ngg'] === true ) {
					$rows[] = $this->p->util->th( 'Add Tags from NGG Featured Image', null, 'og_ngg_tags' ).
					'<td class="blank">'.$form->get_no_checkbox( 'og_ngg_tags' ).'</td>';
				} else $rows[] = $form->get_hidden( 'og_ngg_tags' );
			}
			return $rows;
		}

		public function filter_og_videos_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Include Video Preview Image', null, 'og_vid_prev_img' ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_vid_prev_img' ).'</td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Default / Fallback Video URL', null, 'og_def_vid_url' ).
				'<td class="blank">'.$form->options['og_def_vid_url'].'</td>';
		
				$rows[] = $this->p->util->th( 'Force Default Video on Indexes', null, 'og_def_vid_on_index' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_def_vid_on_index' ).'</td>';
		
				$rows[] = $this->p->util->th( 'Force Default Video on Author Index', null, 'og_def_vid_on_author' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_def_vid_on_author' ).'</td>';
		
				$rows[] = $this->p->util->th( 'Force Default Video on Search Results', null, 'og_def_vid_on_search' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_def_vid_on_search' ).'</td>';
		
				$rows[] = $this->p->util->th( 'Use HTTPS for Video API Calls', null, 'og_vid_https' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_vid_https' ).'</td>';
			}
			return $rows;
		}

		public function filter_og_author_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Author Profile URL Field', null, 'og_author_field' ).
			'<td class="blank">'.$form->author_contact_fields[$this->p->options['og_author_field']].'</td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Fallback to Author Index URL', null, 'og_author_fallback' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_author_fallback' ).'</td>';

				$rows[] = $this->p->util->th( 'Default Author when Missing', null, 'og_def_author_id' ).
				'<td class="blank">'.$form->user_ids[$this->p->options['og_def_author_id']].'</td>';

				$rows[] = $this->p->util->th( 'Force Default Author on Indexes', null, 'og_def_author_on_index' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_def_author_on_index' ).' defines index webpages as articles</td>';

				$rows[] = $this->p->util->th( 'Default Author on Search Results', null, 'og_def_author_on_search' ).
				'<td class="blank">'.$form->get_no_checkbox( 'og_def_author_on_search' ).' defines search webpages as articles</td>';
			}

			$rows[] = $this->p->util->th( 'Gravatar Images for Authors', null, 'og_author_gravatar' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_gravatar_api' ).'</td>';

			return $rows;
		}

		public function filter_pub_facebook_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Author Name Format', 'highlight', 'google_author_name' ).
			'<td class="blank">'.$this->p->cf['form']['user_name_fields'][$this->p->options['seo_author_name']].'</td>';

			return $rows;
		}

		public function filter_pub_google_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Schema Website / Business Logo URL', null, 'google_schema_logo_url' ).
			'<td class="blank">'.$form->options['schema_logo_url'].'</td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Schema Description Length', null, 'google_schema_desc_len' ).
				'<td class="blank">'.$form->options['schema_desc_len'].' characters or less</td>';

				$rows[] = $this->p->util->th( 'Search / SEO Description Length', null, 'google_seo_desc_len' ).
				'<td class="blank">'.$form->options['seo_desc_len'].' characters or less</td>';
			}

			$rows[] = $this->p->util->th( 'Author Link URL Field', null, 'google_author_field' ).
			'<td class="blank">'.$form->author_contact_fields[$this->p->options['seo_author_field']].'</td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Default Author when Missing', null, 'google_def_author_id' ).
				'<td class="blank">'.$form->user_ids[$this->p->options['seo_def_author_id']].'</td>';
		
				$rows[] = $this->p->util->th( 'Force Default Author on Indexes', null, 'google_def_author_on_index' ).
				'<td class="blank">'.$form->get_no_checkbox( 'seo_def_author_on_index' ).'</td>';
		
				$rows[] = $this->p->util->th( 'Default Author on Search Results', null, 'google_def_author_on_search' ).
				'<td class="blank">'.$form->get_no_checkbox( 'seo_def_author_on_search' ).'</td>';

				$rows[] = $this->p->util->th( 'Add Schema Publisher Social JSON', null, 'google_schema_publisher_json' ).
				'<td class="blank">'.$form->get_no_checkbox( 'schema_publisher_json' ).'</td>';

				$rows[] = $this->p->util->th( 'Add Schema Author Social JSON', null, 'google_schema_author_json' ).
				'<td class="blank">'.$form->get_no_checkbox( 'schema_author_json' ).'</td>';
			}

			return $rows;
		}

		public function filter_pub_linkedin_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Publisher <em>Company Page</em> URL', null, 'linkedin_publisher_url'  ).
			'<td class="blank">'.$this->p->options['linkedin_publisher_url'].'</td>';

			return $rows;
		}

		public function filter_pub_pinterest_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Publisher <em>Company Page</em> URL', null, 'rp_publisher_url'  ).
			'<td class="blank">'.$this->p->options['rp_publisher_url'].'</td>';

			$rows[] = $this->p->util->th( 'Rich Pin Image Dimensions', 'highlight', 'rp_img_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'rp_img' ).'</td>';
	
			$rows[] = $this->p->util->th( 'Author Name Format', null, 'rp_author_name' ).
			'<td class="blank">'.$this->p->cf['form']['user_name_fields'][$this->p->options['rp_author_name']].'</td>';

			return $rows;
		}

		public function filter_pub_twitter_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Enable Twitter Cards', 'highlight', 'tc_enable' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Maximum Description Length', null, 'tc_desc_len' ).
				'<td class="blank">'.$this->p->options['tc_desc_len'].' characters or less</td>';
			}

			$rows[] = $this->p->util->th( 'Website @username to Follow', 'highlight', 'tc_site' ).
			'<td class="blank">'.$this->p->options['tc_site'].'</td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( '<em>Summary</em> Card Image Dimensions', null, 'tc_sum_dimensions' ).
				'<td class="blank">'.$form->get_image_dimensions_text( 'tc_sum' ).'</td>';
	
				$rows[] = $this->p->util->th( '<em>Large Image</em> Card Image Dimensions', null, 'tc_lrgimg_dimensions' ).
				'<td class="blank">'.$form->get_image_dimensions_text( 'tc_lrgimg' ).'</td>';
	
				$rows[] = $this->p->util->th( '<em>Photo</em> Card Image Dimensions', 'highlight', 'tc_photo_dimensions' ).
				'<td class="blank">'.$form->get_image_dimensions_text( 'tc_photo' ).'</td>';
	
				$rows[] = $this->p->util->th( '<em>Gallery</em> Card Minimum Images', null, 'tc_gal_minimum' ).
				'<td class="blank">'.$this->p->options['tc_gal_min'].'</td>';
	
				$rows[] = $this->p->util->th( '<em>Gallery</em> Card Image Dimensions', null, 'tc_gal_dimensions' ).
				'<td class="blank">'.$form->get_image_dimensions_text( 'tc_gal' ).'</td>';
	
				$rows[] = $this->p->util->th( '<em>Product</em> Card Image Dimensions', null, 'tc_prod_dimensions' ).
				'<td class="blank">'.$form->get_image_dimensions_text( 'tc_prod' ).'</td>';
			}

			if ( WpssoUser::show_opts( 'all' ) || $this->p->is_avail['ecom']['*'] === true ) {

				$rows[] = $this->p->util->th( '<em>Product</em> Card Maximum Labels', null, 'tc_prod_labels' ).
				'<td class="blank">'.$this->p->options['tc_prod_labels'].'</td>';
	
				$rows[] = $this->p->util->th( '<em>Product</em> Card Default 2nd Label', null, 'tc_prod_defaults' ).
				'<td class="blank">Label: '.$this->p->options['tc_prod_def_label2'].
				' &nbsp; Value: '.$this->p->options['tc_prod_def_data2'].'</td>';
			}

			return $rows;
		}
	}
}

?>
