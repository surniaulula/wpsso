<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdAdminEdit' ) ) {

	class WpssoStdAdminEdit {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'mb_sso_edit_media_prio_video_rows' => 4,
			) );
		}

		public function filter_mb_sso_edit_media_prio_video_rows( $table_rows, $form, $head_info, $mod ) {

			$form_rows = array(
				'subsection_priority_video' => array(
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Priority Video Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_video_api' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature_video_api() . '</td>',
				),
				'og_vid_embed' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Embed HTML', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_embed',
					'content'  => $form->get_no_textarea_value( $value = '' ),
				),
				'og_vid_url' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'or a Video URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_url',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'subsection_priority_video_info' => array(
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Video Information from Video API', 'metabox title', 'wpsso' )
				),
				'og_vid_title' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_title',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'og_vid_desc' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_desc',
					'content'  => $form->get_no_textarea_value( '' ),
				),
				'og_vid_stream_url' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Stream URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_stream_url',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'og_vid_dimensions' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Video Dimensions', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_dimensions',
					'content'  => $form->get_no_input_video_dimensions( 'og_vid' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
