<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplAdminGeneral' ) ) {

	class WpssoGplAdminGeneral {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'og_author_rows' => 2,
				'og_videos_rows' => 2,
			) );
		}

		public function filter_og_author_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';
		
			$rows[] = $this->p->util->get_th( 'Use Author Gravatar Image', null, 'og_author_gravatar' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			return $rows;
		}

		public function filter_og_videos_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.
				'<p>Video discovery and integration modules are provided with the Pro version.</p>'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';
		
			$rows[] = $this->p->util->get_th( 'Max Videos to Include', null, 'og_vid_max' ).
			'<td class="blank">'.$this->p->options['og_vid_max'].'</td>';
	
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Use HTTPS for Video API Calls', null, 'og_vid_https' ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_vid_https' ).'</td>';

			$rows[] = $this->p->util->get_th( 'Include Video Preview Image(s)', null, 'og_vid_prev_img' ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_vid_prev_img' ).
			'&nbsp;&nbsp;video preview images (when available) are included first</td>';

			$rows[] = $this->p->util->get_th( 'Include Embed text/html Type', null, 'og_vid_html_type' ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_vid_html_type' ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Default / Fallback Video URL', null, 'og_def_vid_url' ).
			'<td class="blank">'.$this->p->options['og_def_vid_url'].'</td>';
	
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Force Default Video on Indexes', null, 'og_def_vid_on_index' ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_def_vid_on_index' ).'</td>';
	
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Force Default Video on Author Index', null, 'og_def_vid_on_author' ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_def_vid_on_author' ).'</td>';
	
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Force Default Video on Search Results', null, 'og_def_vid_on_search' ).
			'<td class="blank">'.$form->get_no_checkbox( 'og_def_vid_on_search' ).'</td>';

			return $rows;
		}
	}
}

?>
