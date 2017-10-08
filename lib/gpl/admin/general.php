<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoGplAdminGeneral' ) ) {

	class WpssoGplAdminGeneral {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'og_author_rows' => 2,
				'og_videos_rows' => 2,
			) );
		}

		public function filter_og_author_rows( $table_rows, $form ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$table_rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$table_rows[] = $form->get_th_html( _x( 'Include Author Gravatar Image',
				'option label', 'wpsso' ), null, 'og_author_gravatar' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			return $table_rows;
		}

		public function filter_og_videos_rows( $table_rows, $form ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$table_rows[] = '<td colspan="2" align="center">'.
				'<p>'.__( 'Video discovery and integration modules are provided with the Pro version.',
					'wpsso' ).'</p>'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$table_rows['og_vid_max'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Maximum Videos to Include',
				'option label', 'wpsso' ), null, 'og_vid_max' ).
			'<td class="blank">'.$this->p->options['og_vid_max'].'</td>';

			$table_rows['og_vid_https'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Use HTTPS for Video API Requests',
				'option label', 'wpsso' ), null, 'og_vid_https' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /> <em>'.
				sprintf( _x( 'uses %s', 'option comment', 'wpsso' ),
					str_replace( WPSSO_PLUGINDIR, WPSSO_PLUGINSLUG.'/', WPSSO_PHP_CURL_CAINFO ) ).'</em></td>';

			$table_rows['og_vid_prev_img'] = $form->get_th_html( _x( 'Include Video Preview Images',
				'option label', 'wpsso' ), null, 'og_vid_prev_img' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" />'.' <em>'.
				_x( 'video preview images are included first',
					'option comment', 'wpsso' ).'</em></td>';

			$table_rows['og_vid_html_type'] = $form->get_th_html( _x( 'Include text/html Type Meta Tags',
				'option label', 'wpsso' ), null, 'og_vid_html_type' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			$table_rows['og_vid_autoplay'] = $form->get_th_html( _x( 'Force Autoplay when Possible',
				'option label', 'wpsso' ), null, 'og_vid_autoplay' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			/*
			 * Include option from the advanced settings page.
			 */
			$table_rows['plugin_embedded_media'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Check for Embedded Media',
				'option label', 'wpsso' ), '', 'plugin_embedded_media' ).
			'<td class="blank">'.
			'<p>'.$form->get_nocb_cmt( 'plugin_facebook_api' ).' '.
				_x( 'Facebook Videos', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_slideshare_api' ).' '.
				_x( 'Slideshare Presentations', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_soundcloud_api' ).' '.
				_x( 'Soundcloud Tracks', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_vimeo_api' ).' '.
				_x( 'Vimeo Videos', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_wistia_api' ).' '.
				_x( 'Wistia Videos', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_youtube_api' ).' '.
				_x( 'YouTube Videos and Playlists', 'option value', 'wpsso' ).'</p>'.
			'</td>';

			return $table_rows;
		}
	}
}

?>
