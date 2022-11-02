<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoEditVisibility' ) ) {

	class WpssoEditVisibility {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * See WpssoAbstractWpMeta->get_document_meta_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'metabox_sso_edit_visibility_rows'        => 4,
				'metabox_sso_edit_visibility_robots_rows' => 4,
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_metabox_sso_edit_visibility_rows( $table_rows, $form, $head_info, $mod ) {

			$canonical_url_disabled = $this->p->util->is_canonical_disabled();
			$canonical_url_msg      = $this->p->msgs->maybe_seo_tag_disabled( 'link rel canonical' );
			$def_canonical_url      = $this->p->util->get_canonical_url( $mod, $add_page = false );
			$redir_disabled         = $this->p->util->is_redirect_disabled();

			$form_rows = array(
				'canonical_url' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Canonical URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-canonical_url',
					'content'  => $form->get_input( 'canonical_url', $css_class = 'wide', $css_id = '',
						$max_len = 0, $def_canonical_url, $canonical_url_disabled ) . ' ' . $canonical_url_msg,
				) : '',
				'redirect_url' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( '301 Redirect URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-redirect_url',
					'content'  => $form->get_input( 'redirect_url', $css_class = 'wide', $css_id = '',
						$max_len = 0, $holder = '', $redir_disabled ),
				) : '',
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );

			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_visibility_robots_rows', $table_rows, $form, $head_info, $mod );

			return $table_rows;
		}

		public function filter_metabox_sso_edit_visibility_robots_rows( $table_rows, $form, $head_info, $mod ) {

			$robots_disabled = $this->p->util->robots->is_disabled();
			$robots_msg      = $this->p->msgs->maybe_seo_tag_disabled( 'meta name robots' );

			$form_rows = array(
				'subsection_robots_meta' => array(
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Robots Meta', 'metabox title', 'wpsso' ),
				),
				'robots_disabled' => array(
					'th_class' => 'medium',
					'content'  => $robots_disabled ? $robots_msg : '',
				),
				'robots_noarchive' => array(
					'th_class' => 'medium',
					'label'    => _x( 'No Archive', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-robots_noarchive',
					'content'  => $form->get_checkbox( 'robots_noarchive', $css_class = '', $css_id = '', $robots_disabled ) . ' ' .
						_x( 'do not show a cached link in search results', 'option comment', 'wpsso' ),
				),
				'robots_nofollow' => array(
					'th_class' => 'medium',
					'label'    => _x( 'No Follow', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-robots_nofollow',
					'content'  => $form->get_checkbox( 'robots_nofollow', $css_class = '', $css_id = '', $robots_disabled ) . ' ' .
						_x( 'do not follow links on this webpage', 'option comment', 'wpsso' ),
				),
				'robots_noimageindex' => array(
					'th_class' => 'medium',
					'label'    => _x( 'No Image Index', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-robots_noimageindex',
					'content'  => $form->get_checkbox( 'robots_noimageindex', $css_class = '', $css_id = '', $robots_disabled ) . ' ' .
						_x( 'do not index images on this webpage', 'option comment', 'wpsso' ),
				),
				'robots_noindex' => array(
					'th_class' => 'medium',
					'label'    => _x( 'No Index', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-robots_noindex',
					'content'  => $form->get_checkbox( 'robots_noindex', $css_class = '', $css_id = '', $robots_disabled ) . ' ' .
						_x( 'do not show this webpage in search results', 'option comment', 'wpsso' ),
				),
				'robots_nosnippet' => array(
					'th_class' => 'medium',
					'label'    => _x( 'No Snippet', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-robots_nosnippet',
					'content'  => $form->get_checkbox( 'robots_nosnippet', $css_class = '', $css_id = '', $robots_disabled ) . ' ' .
						_x( 'do not show a text snippet or a video preview in search results', 'option comment', 'wpsso' ),
				),
				'robots_notranslate' => array(
					'th_class' => 'medium',
					'label'    => _x( 'No Translate', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-robots_notranslate',
					'content'  => $form->get_checkbox( 'robots_notranslate', $css_class = '', $css_id = '', $robots_disabled ) . ' ' .
						_x( 'do not offer translation of this webpage in search results', 'option comment', 'wpsso' ),
				),
				'robots_max_snippet' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Snippet Max. Length', 'option label', 'wpsso' ),
					'tooltip'  => 'robots_max_snippet',	// Use the tooltip from plugin settings.
					'content'  => $form->get_input( 'robots_max_snippet', $css_class = 'chars', $css_id = '',
						$len = 0, $holder = true, $robots_disabled ) . ' ' .
						_x( 'characters or less', 'option comment', 'wpsso' ) . ' ' .
						_x( '(-1 for no limit)', 'option comment', 'wpsso' ),
				),
				'robots_max_image_preview' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Image Preview Size', 'option label', 'wpsso' ),
					'tooltip'  => 'robots_max_image_preview',	// Use the tooltip from plugin settings.
					'content'  => $form->get_select( 'robots_max_image_preview', $this->p->cf[ 'form' ][ 'robots_max_image_preview' ],
						$css_class = '', $css_id = '', $is_assoc = true, $robots_disabled ),
				),
				'robots_max_video_preview' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Video Max. Previews', 'option label', 'wpsso' ),
					'tooltip'  => 'robots_max_video_preview',	// Use the tooltip from plugin settings.
					'content'  => $form->get_input( 'robots_max_video_preview', $css_class = 'chars', $css_id = '',
						$len = 0, $holder = true, $robots_disabled ) .
						_x( 'seconds', 'option comment', 'wpsso' ) . ' ' .
						_x( '(-1 for no limit)', 'option comment', 'wpsso' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
