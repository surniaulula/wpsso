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
		
			$rows[] = $this->p->util->get_th( _x( 'Include Author Gravatar Image',
				'option label', 'wpsso' ), null, 'og_author_gravatar' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			return $rows;
		}

		public function filter_og_videos_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.
				'<p>'.__( 'Video discovery and integration modules are provided with the Pro version.',
					'wpsso' ).'</p>'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';
		
			$rows[] = $this->p->util->get_th( _x( 'Maximum Videos to Include',
				'option label', 'wpsso' ), null, 'og_vid_max' ).
			'<td class="blank">'.$this->p->options['og_vid_max'].'</td>';
	
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( _x( 'Use HTTPS for Video API Calls',
				'option label', 'wpsso' ), null, 'og_vid_https' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			$rows[] = $this->p->util->get_th( _x( 'Include Video Preview Image(s)',
				'option label', 'wpsso' ), null, 'og_vid_prev_img' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" />'.
			' '._x( 'video preview images are included first',
				'option comment', 'wpsso' ).'</td>';

			$rows[] = $this->p->util->get_th( _x( 'Include Embed text/html Type',
				'option label', 'wpsso' ), null, 'og_vid_html_type' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			$rows[] = $this->p->util->get_th( _x( 'Force Autoplay when Possible',
				'option label', 'wpsso' ), null, 'og_vid_autoplay' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( _x( 'Default / Fallback Video URL',
				'option label', 'wpsso' ), null, 'og_def_vid_url' ).
			'<td class="blank">'.$this->p->options['og_def_vid_url'].'</td>';
	
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( _x( 'Use Default Video on Indexes',
				'option label', 'wpsso' ), null, 'og_def_vid_on_index' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';
	
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( _x( 'Use Default Video on Search Results',
				'option label', 'wpsso' ), null, 'og_def_vid_on_search' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			return $rows;
		}
	}
}

?>
