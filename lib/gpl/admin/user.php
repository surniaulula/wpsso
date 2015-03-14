<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
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
			'<td class="blank">'.$this->p->webpage->get_title( $this->p->options['og_title_len'], '...', false ).'</td>';
		
			$rows[] = $this->p->util->th( 'Default (Facebook / Open Graph, LinkedIn, Pinterest Rich Pin) Description', 'medium', 'user-og_desc', $post_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['og_desc_len'], '...', false ).'</td>';
	
			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Google+ / Schema Description', 'medium', 'user-schema_desc', $post_info ).
				'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['schema_desc_len'], '...', true ).'</td>';
			}
	
			$rows[] = $this->p->util->th( 'Google Search / SEO Description', 'medium', 'user-seo_desc', $post_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['seo_desc_len'], '...', true, true, false ).'</td>';	// no hashtags

			$rows[] = $this->p->util->th( 'Twitter Card Description', 'medium', 'user-tc_desc', $post_info ).
			'<td class="blank">'.$this->p->webpage->get_description( $this->p->options['tc_desc_len'], '...', false ).'</td>';

			if ( WpssoUser::show_opts( 'all' ) ) {

				$rows[] = $this->p->util->th( 'Sharing URL', 'medium', 'postmeta-sharing_url', $post_info ).
				'<td class="blank">'.$this->p->util->get_sharing_url( false ).'</td>';
			}

			return $rows;
		}

		public function filter_user_media_rows( $rows, $form, $post_info ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Image ID', 'medium', 'postmeta-og_img_id', $post_info ).
			'<td class="blank">&nbsp;</td>';

			$rows[] = $this->p->util->th( 'Image URL', 'medium', 'postmeta-og_img_url', $post_info ).
			'<td class="blank">&nbsp;</td>';

			return $rows;
		}
	}
}

?>
