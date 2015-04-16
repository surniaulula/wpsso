<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplAdminPostmeta' ) ) {

	class WpssoGplAdminPostmeta {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'meta_header_rows' => 3,
				'meta_media_rows' => 3,
				'check_head_options' => 2,
			) );
		}

		public function filter_meta_header_rows( $rows, $form, $post_info ) {
			$post_status = get_post_status( $post_info['id'] );
			$post_type = get_post_type( $post_info['id'] );

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Article Topic', 'medium', 'postmeta-og_art_section', $post_info ).
			'<td class="blank">'.$this->p->options['og_art_section'].'</td>';

			if ( $post_status == 'auto-draft' )
				$rows[] = $this->p->util->th( 'Default Title', 'medium', 'postmeta-og_title', $post_info ). 
				'<td class="blank"><em>Save a draft version or publish the '.
					$post_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = $this->p->util->th( 'Default Title', 'medium', 'postmeta-og_title', $post_info ). 
				'<td class="blank">'.$this->p->webpage->get_title( $this->p->options['og_title_len'], '...', 
					true ).'</td>';	// use_post = true
		
			if ( $post_status == 'auto-draft' )
				$rows[] = $this->p->util->th( 'Default (Facebook / Open Graph, LinkedIn, 
					Pinterest Rich Pin) Description', 'medium', 'postmeta-og_desc', $post_info ).
				'<td class="blank"><em>Save a draft version or publish the '.
					$post_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = $this->p->util->th( 'Default (Facebook / Open Graph, LinkedIn, 
					Pinterest Rich Pin) Description', 'medium', 'postmeta-og_desc', $post_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['og_desc_len'], '...', true ).'</td>';
	
			if ( $post_status == 'auto-draft' )
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->th( 'Google+ / Schema Description', 'medium', 'postmeta-schema_desc', $post_info ).
				'<td class="blank"><em>Save a draft version or publish the '.
					$post_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->th( 'Google+ / Schema Description', 'medium', 'postmeta-schema_desc', $post_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['schema_desc_len'], '...', true ).'</td>';
	
			if ( $post_status == 'auto-draft' )
				$rows[] = $this->p->util->th( 'Google Search / SEO Description', 'medium', 'postmeta-seo_desc', $post_info ).
				'<td class="blank"><em>Save a draft version or publish the '.
					$post_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = $this->p->util->th( 'Google Search / SEO Description', 'medium', 'postmeta-seo_desc', $post_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['seo_desc_len'], '...',
					true, true, false ).'</td>';	// no hashtags

			if ( $post_status == 'auto-draft' )
				$rows[] = $this->p->util->th( 'Twitter Card Description', 'medium', 'postmeta-tc_desc', $post_info ).
				'<td class="blank"><em>Save a draft version or publish the '.
					$post_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = $this->p->util->th( 'Twitter Card Description', 'medium', 'postmeta-tc_desc', $post_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['tc_desc_len'], '...', true ).'</td>';

			if ( $post_status == 'publish' || $post_type == 'attachment' )
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->th( 'Sharing URL', 'medium', 'postmeta-sharing_url', $post_info ).
				'<td class="blank">'.$this->p->util->get_sharing_url( true ).'</td>';
			else
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->th( 'Sharing URL', 'medium', 'postmeta-sharing_url', $post_info ).
				'<td class="blank"><em>The Sharing URL permalink will be available when the '.
					$post_info['ptn'].' is published.</em></td>';

			return $rows;
		}

		public function filter_meta_media_rows( $rows, $form, $post_info ) {

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = '<td colspan="2" class="subsection"><h4 
				style="margin-top:0;">All Social Websites (Open Graph)</h4></td>';

			$rows[] = $this->p->util->th( 'Image Dimensions', 'medium', 'og_img_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'og_img', true ).'</td>';

			$rows[] = $this->p->util->th( 'Image ID', 'medium', 'postmeta-og_img_id', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = $this->p->util->th( 'or Image URL', 'medium', 'postmeta-og_img_url', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'Maximum Images', 'medium', 'postmeta-og_img_max', $post_info ).
			'<td class="blank">'.$this->p->options['og_img_max'].'</td>';

			$rows[] = $this->p->util->th( 'Video Embed HTML', 'medium', 'postmeta-og_vid_embed', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = $this->p->util->th( 'or Video URL', 'medium', 'postmeta-og_vid_url', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'Maximum Videos', 'medium', 'postmeta-og_vid_max', $post_info ).
			'<td class="blank">'.$this->p->options['og_vid_max'].'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			'<td colspan="2" class="subsection"><h4>Pinterest (Rich Pin)</h4></td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'Image Dimensions', 'medium', 'rp_img_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'rp_img', true ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'Image ID', 'medium', 'postmeta-rp_img_id', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'or Image URL', 'medium', 'postmeta-rp_img_url', $post_info ).
			'<td class="blank">&nbsp;</td>';

			return $rows;
		}

		public function filter_check_head_options( $check_opts, $post_id ) {
			foreach ( SucomUtil::preg_grep_keys( '/^meta_name_twitter:/', $check_opts ) as $key => $val )
				unset( $check_opts[$key] );
			return $check_opts;
		}
	}
}

?>
