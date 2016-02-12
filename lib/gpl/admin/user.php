<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplAdminUser' ) ) {

	class WpssoGplAdminUser {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'user_header_rows' => 3,
				'user_media_rows' => 3,
			) );
		}

		public function filter_user_header_rows( $rows, $form, $head_info ) {

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows['og_title'] = $this->p->util->get_th( _x( 'Default Title',
				'option label', 'wpsso' ), 'medium', 'meta-og_title', $head_info ). 
			'<td class="blank">'.$this->p->webpage->get_title( $this->p->options['og_title_len'],
				'...', false, true, false, true, 'none' ).'</td>';	// $use_post = false, $md_idx = 'none'
		
			$rows['og_desc'] = $this->p->util->get_th( _x( 'Default (Facebook / Open Graph, LinkedIn, Pinterest Rich Pin) Description',
				'option label', 'wpsso' ), 'medium', 'meta-og_desc', $head_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['og_desc_len'],
				'...', false, true, true, true, 'none' ).'</td>';	// $use_post = false, $md_idx = 'none'
	
			$rows['schema_desc'] = $this->p->util->get_th( _x( 'Google / Schema Description',
				'option label', 'wpsso' ), 'medium', 'meta-schema_desc', $head_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['schema_desc_len'],
				'...', false ).'</td>';					// $use_post = false
	
			$disable_seo_desc = $this->p->options['add_meta_name_description'] ? false : true;
			$rows['seo_desc'] = ( $disable_seo_desc ? '<tr class="hide_in_basic">' : '' ).
			$this->p->util->get_th( _x( 'Google Search / SEO Description',
				'option label', 'wpsso' ), 'medium', 'meta-seo_desc', $head_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['seo_desc_len'],
				'...', false, true, false ).'</td>';				// $use_post = false, $add_hashtags = false

			$rows['tc_desc'] = $this->p->util->get_th( _x( 'Twitter Card Description',
				'option label', 'wpsso' ), 'medium', 'meta-tc_desc', $head_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['tc_desc_len'],
				'...', false ).'</td>';						// $use_post = false

			$rows['sharing_url'] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( _x( 'Sharing URL',
				'option label', 'wpsso' ), 'medium', 'meta-sharing_url', $head_info ).
			'<td class="blank">'.$this->p->util->get_sharing_url( false ).'</td>';	// $use_post = false

			return $rows;
		}

		public function filter_user_media_rows( $rows, $form, $head_info ) {

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = '<td></td><td class="subsection top"><h4>'.
				_x( 'All Social Websites / Open Graph',
					'metabox title', 'wpsso' ).'</h4></td>';

			$rows['og_img_dimensions'] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( _x( 'Image Dimensions',
				'option label', 'wpsso' ), 'medium', 'og_img_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'og_img', true ).'</td>';

			$rows['og_img_id'] = $this->p->util->get_th( _x( 'Image ID',
				'option label', 'wpsso' ), 'medium', 'meta-og_img_id', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows['og_img_url'] = $this->p->util->get_th( _x( 'or an Image URL',
				'option label', 'wpsso' ), 'medium', 'meta-og_img_url', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows['og_vid_embed'] = $this->p->util->get_th( _x( 'Video Embed HTML',
				'option label', 'wpsso' ), 'medium', 'meta-og_vid_embed', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows['og_vid_embed'] = $this->p->util->get_th( _x( 'or a Video URL',
				'option label', 'wpsso' ), 'medium', 'meta-og_vid_url', $head_info ).
			'<td class="blank">&nbsp;</td>';

			$rows['og_vid_prev_img'] = $this->p->util->get_th( _x( 'Include Preview Image(s)',
				'option label', 'wpsso' ), 'medium', 'meta-og_vid_prev_img', $head_info ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_vid_prev_img' ).'</td>';

			if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) ) {

				$rows[] = '<tr class="hide_in_basic">'.
				'<td></td><td class="subsection"><h4>'.
					_x( 'Pinterest / Rich Pin',
						'metabox title', 'wpsso' ).'</h4></td>';
	
				$rows['rp_img_dimensions'] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( _x( 'Image Dimensions',
					'option label', 'wpsso' ), 'medium', 'rp_img_dimensions' ).
				'<td class="blank">'.$form->get_image_dimensions_text( 'rp_img', true ).'</td>';
	
				$rows['rp_img_id'] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( _x( 'Image ID',
					'option label', 'wpsso' ), 'medium', 'meta-rp_img_id', $head_info ).
				'<td class="blank">&nbsp;</td>';
	
				$rows['rp_img_url'] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( _x( 'or an Image URL',
					'option label', 'wpsso' ), 'medium', 'meta-rp_img_url', $head_info ).
				'<td class="blank">&nbsp;</td>';
			}

			return $rows;
		}
	}
}

?>
