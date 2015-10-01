<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplAdminPost' ) ) {

	class WpssoGplAdminPost {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'post_header_rows' => 3,
				'post_media_rows' => 3,
			) );
		}

		public function filter_post_header_rows( $rows, $form, $head_info ) {
			$post_status = get_post_status( $head_info['post_id'] );
			$post_type = get_post_type( $head_info['post_id'] );

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->get_th( __( 'Article Topic',
				'wpsso' ), 'medium', 'post-og_art_section', $head_info ).
			'<td class="blank">'.$this->p->options['og_art_section'].'</td>';

			if ( $post_status == 'auto-draft' )
				$rows[] = $this->p->util->get_th( __( 'Default Title',
					'wpsso' ), 'medium', 'meta-og_title', $head_info ). 
				'<td class="blank"><em>Save a draft version or publish the '.
					$head_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = $this->p->util->get_th( __( 'Default Title',
					'wpsso' ), 'medium', 'meta-og_title', $head_info ). 
				'<td class="blank">'.$this->p->webpage->get_title( $this->p->options['og_title_len'],
					'...', true, true, false, true, 'none' ).'</td>';	// $use_post = true, $md_idx = 'none'
		
			if ( $post_status == 'auto-draft' )
				$rows[] = $this->p->util->get_th( __( 'Default (Facebook / Open Graph, LinkedIn, Pinterest Rich Pin) Description',
					'wpsso' ), 'medium', 'post-og_desc', $head_info ).
				'<td class="blank"><em>Save a draft version or publish the '.
					$head_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = $this->p->util->get_th( __( 'Default (Facebook / Open Graph, LinkedIn, Pinterest Rich Pin) Description',
					'wpsso' ), 'medium', 'post-og_desc', $head_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['og_desc_len'],
					'...', true, true, true, true, 'none' ).'</td>';	// $use_post = true, $md_idx = 'none'
	
			if ( $post_status == 'auto-draft' )
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( __( 'Google+ / Schema Description',
					'wpsso' ), 'medium', 'meta-schema_desc', $head_info ).
				'<td class="blank"><em>Save a draft version or publish the '.
					$head_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( __( 'Google+ / Schema Description',
					'wpsso' ), 'medium', 'meta-schema_desc', $head_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['schema_desc_len'], 
					'...', true ).'</td>';
	
			if ( $post_status == 'auto-draft' )
				$rows[] = $this->p->util->get_th( __( 'Google Search / SEO Description',
					'wpsso' ), 'medium', 'meta-seo_desc', $head_info ).
				'<td class="blank"><em>Save a draft version or publish the '.
					$head_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = $this->p->util->get_th( __( 'Google Search / SEO Description',
					'wpsso' ), 'medium', 'meta-seo_desc', $head_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['seo_desc_len'], 
					'...', true, true, false ).'</td>';			// $add_hashtags = false

			if ( $post_status == 'auto-draft' )
				$rows[] = $this->p->util->get_th( __( 'Twitter Card Description',
					'wpsso' ), 'medium', 'meta-tc_desc', $head_info ).
				'<td class="blank"><em>Save a draft version or publish the '.
					$head_info['ptn'].' to update and display this value.</em></td>';
			else
				$rows[] = $this->p->util->get_th( __( 'Twitter Card Description',
					'wpsso' ), 'medium', 'meta-tc_desc', $head_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['tc_desc_len'],
					'...', true ).'</td>';

			if ( $post_status == 'publish' || $post_type == 'attachment' )
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( __( 'Sharing URL',
					'wpsso' ), 'medium', 'meta-sharing_url', $head_info ).
				'<td class="blank">'.$this->p->util->get_sharing_url( true ).'</td>';
			else
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( __( 'Sharing URL',
					'wpsso' ), 'medium', 'meta-sharing_url', $head_info ).
				'<td class="blank"><em>The Sharing URL permalink will be available when the '.
					$head_info['ptn'].' is published.</em></td>';

			return $rows;
		}

		public function filter_post_media_rows( $rows, $form, $head_info ) {

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = '<td colspan="2" class="subsection"><h4 
				style="margin-top:0;">'.__( 'All Social Websites / Open Graph',
					'wpsso' ).'</h4></td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( __( 'Image Dimensions',
				'wpsso' ), 'medium', 'og_img_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'og_img', true ).'</td>';

			$rows[] = $this->p->util->get_th( __( 'Image ID',
				'wpsso' ), 'medium', 'meta-og_img_id', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = $this->p->util->get_th( __( 'or Image URL',
				'wpsso' ), 'medium', 'meta-og_img_url', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( __( 'Maximum Images',
				'wpsso' ), 'medium', 'meta-og_img_max', $head_info ).
			'<td class="blank">'.$this->p->options['og_img_max'].'</td>';

			$rows[] = $this->p->util->get_th( __( 'Video Embed HTML',
				'wpsso' ), 'medium', 'meta-og_vid_embed', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = $this->p->util->get_th( __( 'or Video URL',
				'wpsso' ), 'medium', 'meta-og_vid_url', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( __( 'Maximum Videos',
				'wpsso' ), 'medium', 'meta-og_vid_max', $head_info ).
			'<td class="blank">'.$this->p->options['og_vid_max'].'</td>';

			$rows[] = $this->p->util->get_th( __( 'Include Preview Image(s)',
				'wpsso' ), 'medium', 'meta-og_vid_prev_img', $head_info ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_vid_prev_img' ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			'<td colspan="2" class="subsection"><h4>'.__( 'Pinterest (Rich Pin)',
				'wpsso' ).'</h4></td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( __( 'Image Dimensions',
				'wpsso' ), 'medium', 'rp_img_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'rp_img', true ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( __( 'Image ID',
				'wpsso' ), 'medium', 'meta-rp_img_id', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( __( 'or Image URL',
				'wpsso' ), 'medium', 'meta-rp_img_url', $head_info ).
			'<td class="blank">&nbsp;</td>';

			return $rows;
		}
	}
}

?>
