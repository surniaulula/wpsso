<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
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

		public function filter_user_header_rows( $rows, $form, $post_info ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Default Title', 'medium', 'user-og_title', $post_info ). 
			'<td class="blank">'.$this->p->webpage->get_title( $this->p->options['og_title_len'], '...', 
				false ).'</td>';	// use_post = false
		
			$rows[] = $this->p->util->th( 'Default (Facebook / Open Graph, LinkedIn, 
				Pinterest Rich Pin) Description', 'medium', 'user-og_desc', $post_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['og_desc_len'], '...', 
				false ).'</td>';	// use_post = false
	
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'Google+ / Schema Description', 'medium', 'user-schema_desc', $post_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['schema_desc_len'], '...', 
				false ).'</td>';	// use_post = false
	
			$rows[] = $this->p->util->th( 'Google Search / SEO Description', 'medium', 'user-seo_desc', $post_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['seo_desc_len'], '...', 
				false, true, false ).'</td>';	// use_post = false, and no hashtags

			$rows[] = $this->p->util->th( 'Twitter Card Description', 'medium', 'user-tc_desc', $post_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['tc_desc_len'], '...', 
				false ).'</td>';	// use_post = false

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'Sharing URL', 'medium', 'postmeta-sharing_url', $post_info ).
			'<td class="blank">'.$this->p->util->get_sharing_url( false ).'</td>';	// use_post = false

			return $rows;
		}

		public function filter_user_media_rows( $rows, $form, $post_info ) {

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = '<td colspan="2" class="subsection"><h4 
				style="margin-top:0;">All Social Websites (Open Graph)</h4></td>';

			$rows[] = $this->p->util->th( 'Image ID', 'medium', 'postmeta-og_img_id', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = $this->p->util->th( 'or Image URL', 'medium', 'postmeta-og_img_url', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = $this->p->util->th( 'Video Embed HTML', 'medium', 'postmeta-og_vid_embed', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = $this->p->util->th( 'or Video URL', 'medium', 'postmeta-og_vid_url', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			'<td colspan="2" class="subsection"><h4>Pinterest (Rich Pin)</h4></td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'Image ID', 'medium', 'postmeta-rp_img_id', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'or Image URL', 'medium', 'postmeta-rp_img_url', $post_info ).
			'<td class="blank">&nbsp;</td>';

			return $rows;
		}
	}
}

?>
