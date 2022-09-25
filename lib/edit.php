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

if ( ! class_exists( 'WpssoEdit' ) ) {

	class WpssoEdit {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Document SSO metabox tabs are defined in WpssoAbstractWpMeta->get_document_meta_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array( 
				'metabox_sso_edit_general_rows'           => 4,
				'metabox_sso_edit_media_rows'             => 4,
				'metabox_sso_edit_media_prio_image_rows'  => 5,
				'metabox_sso_edit_media_twitter_rows'     => 5,
				'metabox_sso_edit_media_schema_rows'      => 5,
				'metabox_sso_edit_media_pinterest_rows'   => 5,
				'metabox_sso_edit_visibility_rows'        => 4,
				'metabox_sso_edit_visibility_robots_rows' => 4,
				'metabox_sso_prev_social_rows'            => 4,
				'metabox_sso_prev_oembed_rows'            => 4,
				'metabox_sso_validators_rows'             => 4,
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_metabox_sso_edit_general_rows( $table_rows, $form, $head_info, $mod ) {

			$limits           = WpssoConfig::get_input_limits();	// Uses a local cache.
			$og_types         = $this->p->og->get_og_types_select();
			$schema_types     = $this->p->schema->get_schema_types_select();
			$primary_terms    = $this->p->post->get_primary_terms( $mod, $tax_slug = 'category', $output = 'names' );
			$article_sections = $this->p->util->get_article_sections();

			/**
			 * Default option values.
			 */
			$def_seo_title = $this->p->page->get_title( $mod, $md_key = '', $max_len = 'seo_title' );
			$def_og_title  = $this->p->page->get_title( $mod, $md_key = 'seo_title', $max_len = 'og_title' );
			$def_tc_title  = $this->p->page->get_title( $mod, $md_key = 'og_title', $max_len = 'tc_title' );

			$def_seo_desc     = $this->p->page->get_description( $mod, $md_key = '', $max_len = 'seo_desc' );
			$def_og_desc      = $this->p->page->get_description( $mod, $md_key = 'seo_desc', $max_len = 'og_desc' );
			$def_pin_img_desc = $this->p->page->get_description( $mod, $md_key = 'og_desc', $max_len = 'pin_img_desc' );
			$def_tc_desc      = $this->p->page->get_description( $mod, $md_key = 'og_desc', $max_len = 'tc_desc' );
			$def_reading_mins = $this->p->page->get_reading_mins( $mod );

			/**
			 * Check for disabled options.
			 */
			$seo_title_disabled = $this->p->util->is_seo_title_disabled();
			$seo_desc_disabled  = $this->p->util->is_seo_desc_disabled();
			$pin_img_disabled   = $this->p->util->is_pin_img_disabled();

			$seo_title_msg = $this->p->msgs->maybe_seo_title_disabled();
			$seo_desc_msg  = $this->p->msgs->maybe_seo_tag_disabled( 'meta name description' );
			$pin_img_msg   = $this->p->msgs->maybe_pin_img_disabled();

			/**
			 * Metabox form rows.
			 */
			$form_rows = array(
				'attach_img_crop' => $mod[ 'is_attachment' ] && wp_attachment_is_image( $mod[ 'id' ] ) ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Crop Area', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_crop_area',
					'content'  => $form->get_input_image_crop_area( 'attach_img', $add_none = true ),
				) : array(),
				'og_schema_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_schema_type',
					'content'  => $form->get_select( 'schema_type', $schema_types, $css_class = 'schema_type', $css_id = 'og_schema_type',
						$is_assoc = true, $is_disabled = false, $selected = false,
							$event_names = array( 'on_focus_load_json', 'on_change_unhide_rows' ),
								$event_args = array(
									'json_var'  => 'schema_types',
									'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
									'is_transl' => true,					// No label translation required.
									'is_sorted' => true,					// No label sorting required.
								)
						),
				),
				'og_type' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Open Graph Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_type',
					'content'  => $form->get_select( 'og_type', $og_types, $css_class = 'og_type', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = true,
							$event_names = array( 'on_change_unhide_rows' ) ),
				),
				'og_article_section' => $mod[ 'is_public' ] ? array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'th_class' => 'medium',
					'label'    => _x( 'Article Section', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_article_section',
					'content'  => $form->get_select( 'article_section', $article_sections, $css_class = 'article_section', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false,
							$event_names = array( 'on_focus_load_json' ),
								$event_args = array(
									'json_var'  => 'article_sections',
									'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
									'is_transl' => true,					// No label translation required.
									'is_sorted' => true,					// No label sorting required.
								)
						),
				) : '',
				'og_reading_mins' => $mod[ 'is_public' ] ? array(
					'tr_class' => 'hide_og_type hide_og_type_article',
					'th_class' => 'medium',
					'label'    => _x( 'Est. Reading Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_reading_mins',
					'content'  => $form->get_input( 'reading_mins', $css_class = 'xshort', $css_id = '', 0, $def_reading_mins ) . ' ' .
						__( 'minute(s)', 'wpsso' ),
				) : '',
				'primary_term_id' => ! empty( $primary_terms ) ? array(	// Show the option if we have post category terms.
					'th_class' => 'medium',
					'label'    => _x( 'Primary Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-primary_term_id',
					'content'  => $form->get_select( 'primary_term_id', $primary_terms,
						$css_class = 'primary_term_id', $css_id = '', $is_assoc = true ),
				) : '',
				'seo_title' => $mod[ 'is_public' ] ? array(
					'tr_class' => $seo_title_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'SEO Title Tag', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-seo_title',
					'content'  => $form->get_input( 'seo_title', $css_class = 'wide', $css_id = '',
						$limits[ 'seo_title' ], $def_seo_title, $seo_title_disabled ) . ' ' . $seo_title_msg,
				) : '',
				'seo_desc' => $mod[ 'is_public' ] ? array(
					'tr_class' => $seo_desc_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'SEO Meta Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-seo_desc',
					'content'  => $form->get_textarea( 'seo_desc', $css_class = '', $css_id = '',
						$limits[ 'seo_desc' ], $def_seo_desc, $seo_desc_disabled ) . ' ' . $seo_desc_msg,
				) : '',
				'og_title' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Social Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_title',
					'content'  => $form->get_input_dep( 'og_title', $css_class = 'wide', $css_id = '',
						$limits[ 'og_title' ], $def_og_title, $is_disabled = false, $dep_id = 'seo_title' ),
				) : '',
				'og_desc' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Social Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_desc',
					'content'  => $form->get_textarea_dep( 'og_desc', $css_class = '', $css_id = '',
						$limits[ 'og_desc' ], $def_og_desc, $is_disabled = false, $dep_id = 'seo_desc' ),
				) : '',
				'pin_img_desc' => $mod[ 'is_public' ] ? array(
					'tr_class' => $pin_img_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Pinterest Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-pin_img_desc',
					'content'  => $form->get_textarea_dep( 'pin_img_desc', $css_class = '', $css_id = '',
						$limits[ 'pin_img_desc' ], $def_pin_img_desc, $pin_img_disabled, $dep_id = 'og_desc' ) . $pin_img_msg,
				) : '',
				'tc_title' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Twitter Card Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-tc_title',
					'content'  => $form->get_input_dep( 'tc_title', $css_class = 'wide', $css_id = '',
						$limits[ 'tc_title' ], $def_tc_title, $is_disabled = false, $dep_id = 'og_title' ),
				) : '',
				'tc_desc' => $mod[ 'is_public' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-tc_desc',
					'content'  => $form->get_textarea_dep( 'tc_desc', $css_class = '', $css_id = '',
						$limits[ 'tc_desc' ], $def_tc_desc, $is_disabled = false, $dep_id = 'og_desc' ),
				) : '',
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_media_rows( $table_rows, $form, $head_info, $mod ) {

			$canonical_url   = $this->p->util->get_canonical_url( $mod );
			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			$form_rows = array(
				'info_priority_media' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-priority-media' ) . '</td>',
				),
				'og_img_max' => $mod[ 'is_post' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'og_img_max',	// Use tooltip message from settings.
					'content'  => $form->get_select( 'og_img_max', range( 0, $max_media_items ), $css_class = 'medium' ),
				) : '',
				'og_vid_max' => $mod[ 'is_post' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Maximum Videos', 'option label', 'wpsso' ),
					'tooltip'  => 'og_vid_max',	// Use the tooltip from plugin settings.
					'content'  => $form->get_select( 'og_vid_max', range( 0, $max_media_items ), $css_class = 'medium' ),
				) : '',
				'og_vid_prev_img' => $mod[ 'is_post' ] ? array(
					'th_class' => 'medium',
					'label'    => _x( 'Include Video Previews', 'option label', 'wpsso' ),
					'tooltip'  => 'og_vid_prev_img',	// Use the tooltip from plugin settings.
					'content'  => $form->get_checkbox( 'og_vid_prev_img' ) . $this->p->msgs->preview_images_are_first(),
				) : '',
				'subsection_opengraph' => array(
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Priority Media', 'metabox title', 'wpsso' ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );

			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_media_prio_image_rows', $table_rows, $form, $head_info, $mod, $canonical_url );

			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_media_prio_video_rows', $table_rows, $form, $head_info, $mod, $canonical_url );

			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_media_schema_rows', $table_rows, $form, $head_info, $mod, $canonical_url );

			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_media_pinterest_rows', $table_rows, $form, $head_info, $mod, $canonical_url );

			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_media_twitter_rows', $table_rows, $form, $head_info, $mod, $canonical_url );

			return $table_rows;
		}

		public function filter_metabox_sso_edit_media_prio_image_rows( $table_rows, $form, $head_info, $mod, $canonical_url ) {

			$this->p->util->maybe_set_ref( $canonical_url, $mod, __( 'getting open graph image', 'wpsso' ) );

			$size_name     = 'wpsso-opengraph';
			$media_request = array( 'pid' );
			$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = 'none' );

			$this->p->util->maybe_unset_ref( $canonical_url );

			$form_rows = array(
				'subsection_priority_image' => array(
					'td_class' => 'subsection top',
					'header'   => 'h5',
					'label'    => _x( 'Priority Image Information', 'metabox title', 'wpsso' )
				),
				'og_img_id' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_id',
					'content'  => $form->get_input_image_upload( 'og_img', $media_info[ 'pid' ] ),
				),
				'og_img_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_url',
					'content'  => $form->get_input_image_url( 'og_img' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_media_schema_rows( $table_rows, $form, $head_info, $mod, $canonical_url ) {

			if ( ! $mod[ 'is_public' ] ) {

				return $table_rows;
			}

			$schema_disabled = $this->p->util->is_schema_disabled();
			$schema_msg      = $this->p->msgs->maybe_schema_disabled();
			$media_info      = array( 'pid' => '' );

			if ( ! $schema_disabled ) {

				$this->p->util->maybe_set_ref( $canonical_url, $mod, __( 'getting schema 1:1 image', 'wpsso' ) );

				$size_name     = 'wpsso-schema-1x1';
				$media_request = array( 'pid' );
				$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = 'og' );

				$this->p->util->maybe_unset_ref( $canonical_url );
			}

			$form_rows = array(
				'subsection_schema' => array(
					'tr_class' => $schema_disabled ? 'hide_in_basic' : '',
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Schema Markup and Google Rich Results', 'metabox title', 'wpsso' )
				),
				'schema_img_id' => array(
					'tr_class' => $schema_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_img_id',
					'content'  => $form->get_input_image_upload( 'schema_img', $media_info[ 'pid' ], $schema_disabled ),
				),
				'schema_img_url' => array(
					'tr_class' => $schema_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_img_url',
					'content'  => $form->get_input_image_url( 'schema_img', '', $schema_disabled ) . $schema_msg,
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/**
		 * Pinterest Pin It.
		 */
		public function filter_metabox_sso_edit_media_pinterest_rows( $table_rows, $form, $head_info, $mod, $canonical_url ) {

			if ( ! $mod[ 'is_public' ] ) {

				return $table_rows;
			}

			$pin_img_disabled = $this->p->util->is_pin_img_disabled();
			$pin_img_msg      = $this->p->msgs->maybe_pin_img_disabled();
			$media_info       = array( 'pid' => '' );

			if ( ! $pin_img_disabled ) {

				$this->p->util->maybe_set_ref( $canonical_url, $mod, __( 'getting pinterest image', 'wpsso' ) );

				$size_name     = 'wpsso-pinterest';
				$media_request = array( 'pid' );
				$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = array( 'schema', 'og' ) );

				$this->p->util->maybe_unset_ref( $canonical_url );
			}

			$form_rows = array(
				'subsection_pinterest' => array(
					'tr_class' => $pin_img_disabled ? 'hide_in_basic' : '',
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Pinterest Pin It', 'metabox title', 'wpsso' ),
				),
				'pin_img_id' => array(
					'tr_class' => $pin_img_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-pin_img_id',
					'content'  => $form->get_input_image_upload( 'pin_img', $media_info[ 'pid' ], $pin_img_disabled ),
				),
				'pin_img_url' => array(
					'tr_class' => $pin_img_disabled ? 'hide_in_basic' : '',
					'th_class' => 'medium',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-pin_img_url',
					'content'  => $form->get_input_image_url( 'pin_img', '', $pin_img_disabled ) . $pin_img_msg,
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/**
		 * Twitter Card
		 *
		 * App and Player cards do not have a $size_name.
		 *
		 * Only show custom image options for the Summary and Summary Large Image cards. 
		 */
		public function filter_metabox_sso_edit_media_twitter_rows( $table_rows, $form, $head_info, $mod, $canonical_url ) {

			if ( ! $mod[ 'is_public' ] ) {

				return $table_rows;
			}

			list( $card_type, $card_label, $size_name, $tc_prefix ) = $this->p->tc->get_card_info( $mod, $head_info );

			if ( ! empty( $card_label ) ) {

				$form_rows[ 'subsection_tc' ] = array(
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => $card_label,
				);

				if ( empty( $size_name ) ) {

					$form_rows[ 'subsection_tc_msg' ] = array(
						'table_row' => '<td colspan="2"><p class="status-msg">' .
							sprintf( __( 'No priority media options for the %s.', 'wpsso' ),
								$card_label ) . '</p></td>',
					);

				} else {

					$this->p->util->maybe_set_ref( $canonical_url, $mod, __( 'getting twitter card image', 'wpsso' ) );

					$media_request = array( 'pid' );
					$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = 'og' );

					$this->p->util->maybe_unset_ref( $canonical_url );

					$form_rows[ $tc_prefix . '_img_id' ] = array(
						'th_class' => 'medium',
						'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
						'tooltip'  => 'meta-' . $tc_prefix . '_img_id',
						'content'  => $form->get_input_image_upload( $tc_prefix . '_img', $media_info[ 'pid' ] ),
					);

					$form_rows[ $tc_prefix . '_img_url' ] = array(
						'th_class' => 'medium',
						'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
						'tooltip'  => 'meta-' . $tc_prefix . '_img_url',
						'content'  => $form->get_input_image_url( $tc_prefix . '_img' ),
					);
				}
			}

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );

			return $table_rows;
		}

		public function filter_metabox_sso_edit_visibility_rows( $table_rows, $form, $head_info, $mod ) {

			$canonical_url_disabled = $this->p->util->is_canonical_disabled();
			$canonical_url_msg      = $this->p->msgs->maybe_seo_tag_disabled( 'link rel canonical' );
			$def_canonical_url      = $this->p->util->get_canonical_url( $mod, $add_page = false );

			$redir_disabled   = $this->p->util->is_redirect_disabled();
			$def_redirect_url = $this->p->util->get_redirect_url( $mod );

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
						$max_len = 0, $def_redirect_url, $redir_disabled ),
				) : '',
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );

			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_visibility_robots_rows', $table_rows, $form, $head_info, $mod );

			return $table_rows;
		}

		/**
		 * See https://developers.google.com/search/reference/robots_meta_tag.
		 */
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
						$len = 0, $holder = true, $robots_disabled ) . ' ' . _x( 'characters or less', 'option comment', 'wpsso' ) .
							' (' . _x( '-1 for no limit', 'option comment', 'wpsso' ) . ')',
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
						$len = 0, $holder = true, $robots_disabled ) . _x( 'seconds', 'option comment', 'wpsso' ) .
							' (' . _x( '-1 for no limit', 'option comment', 'wpsso' ) . ')',
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/**
		 * Preview Social tab content.
		 */
		public function filter_metabox_sso_prev_social_rows( $table_rows, $form, $head_info, $mod ) {

			$og_prev_width    = 600;
			$og_prev_height   = 315;
			$og_prev_img_html = '';

			$image_url     = SucomUtil::get_first_mt_media_url( $head_info );
			$canonical_url = $this->p->util->get_canonical_url( $mod, $add_page = false );

			if ( $mod[ 'is_post' ] ) {

				$shortlink_url = SucomUtilWP::wp_get_shortlink( $mod[ 'id' ], $context = 'post' );

			} else {

				$shortlink_url = $this->p->util->shorten_url( $canonical_url, $mod );
			}

			$have_sizes = isset( $head_info[ 'og:image:width' ] ) && $head_info[ 'og:image:width' ] > 0 && 
				isset( $head_info[ 'og:image:height' ] ) && $head_info[ 'og:image:height' ] > 0 ? true : false;

			$is_sufficient = true === $have_sizes && $head_info[ 'og:image:width' ] >= $og_prev_width && 
				$head_info[ 'og:image:height' ] >= $og_prev_height ? true : false;

			if ( ! empty( $image_url ) ) {

				if ( $have_sizes ) {

					$og_prev_img_html .= '<div class="fb_preview_img" style=" background-size:' ;

					if ( $is_sufficient ) {

						$og_prev_img_html .= 'cover';

					} else {

						$og_prev_img_html .= $head_info[ 'og:image:width' ] . 'px ' . $head_info[ 'og:image:height' ] . 'px';
					}

					$og_prev_img_html .= '; background-image:url(' . $image_url . ');" />';

					if ( ! $is_sufficient ) {

						$og_prev_img_html .= '<p>' . sprintf( _x( 'Image Size Smaller<br/>than Suggested Minimum<br/>of %s',
							'preview image error', 'wpsso' ), $og_prev_width . 'x' . $og_prev_height . 'px' ) . '</p>';
					}

					$og_prev_img_html .= '</div>';

				} else {

					$og_prev_img_html .= '<div class="fb_preview_img" style="background-image:url(' . $image_url . ');" />';
					$og_prev_img_html .= '<p>' . _x( 'Image Size Unknown<br/>or Not Available', 'preview image error', 'wpsso' ) . '</p>';
					$og_prev_img_html .= '</div>';
				}

			} else {

				$og_prev_img_html .= '<div class="fb_preview_img">';
				$og_prev_img_html .= '<p>' . _x( 'No Open Graph Image Found', 'preview image error', 'wpsso' ) . '</p>';
				$og_prev_img_html .= '</div>';
			}

			$table_rows[ 'prev_canonical_url' ] = '' . 
				$form->get_th_html( _x( 'Canonical URL', 'option label', 'wpsso' ), $css_class = 'medium nowrap' ) . 
				'<td>' . $form->get_no_input_clipboard( $canonical_url ) . '</td>';

			$table_rows[ 'prev_shortlink_url' ] = '' .
				$form->get_th_html( _x( 'Shortlink URL', 'option label', 'wpsso' ), $css_class = 'medium nowrap' ) . 
				'<td>' . $form->get_no_input_clipboard( $shortlink_url ) . '</td>';

			$table_rows[ 'subsection_prev_og' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'Facebook / Open Graph Example', 'metabox title', 'wpsso' ) . '</h4></td>';

			$table_rows[ 'prev_og' ] = '' .
				'<td colspan="2" class="fb_preview_container">
					<div class="fb_preview_box_border">
						<div class="fb_preview_box">
							' . $og_prev_img_html . '
							<div class="fb_preview_text">
								<div class="fb_preview_title">' .
								( empty( $head_info[ 'og:title' ] ) ? '' : $head_info[ 'og:title' ] ) . 
								'</div><!-- .fb_preview_title -->
								<div class="fb_preview_desc">' .
								( empty( $head_info[ 'og:description' ] ) ? '' : $head_info[ 'og:description' ] ) . 
								'</div><!-- .fb_preview_desc -->
								<div class="fb_preview_by">' . 
									$_SERVER[ 'SERVER_NAME' ] . 
									( empty( $this->p->options[ 'add_meta_property_article:author' ] ) ||
										empty( $head_info[ 'article:author:name' ] ) ?
											'' : ' | By ' . $head_info[ 'article:author:name' ] ) . 
								'</div><!-- .fb_preview_by -->
							</div><!-- .fb_preview_text -->
						</div><!-- .fb_preview_box -->
					</div><!-- .fb_preview_box_border -->
				</td><!-- .fb_preview_container -->';

			$table_rows[ 'prev_og_footer' ] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-social-preview' ) . '</td>';

			return $table_rows;
		}

		/**
		 * Preview oEmbed tab content.
		 */
		public function filter_metabox_sso_prev_oembed_rows( $table_rows, $form, $head_info, $mod ) {

			$json_url     = $this->p->util->get_oembed_url( $mod, 'json' );
			$xml_url      = $this->p->util->get_oembed_url( $mod, 'xml' );
			$oembed_data  = $this->p->util->get_oembed_data( $mod, $oembed_width = 600 );

			$table_rows[ 'oembed_json_url' ] = '' .
				$form->get_th_html( _x( 'oEmbed JSON URL', 'option label', 'wpsso' ), $css_class = 'medium' ) . 
				'<td>' . $form->get_no_input_clipboard( $json_url ) . '</td>';

			$table_rows[ 'oembed_xml_url' ] = '' .
				$form->get_th_html( _x( 'oEmbed XML URL', 'option label', 'wpsso' ), $css_class = 'medium' ) . 
				'<td>' . $form->get_no_input_clipboard( $xml_url ) . '</td>';

			$table_rows[ 'subsection_oembed_html' ] = '<td colspan="2" class="subsection"><h4>' . 
				_x( 'oEmbed HTML', 'metabox title', 'wpsso' ) . '</h4></td>';

			if ( ! empty( $oembed_data[ 'html' ] ) ) {

				$table_rows[ 'oembed_html' ] = '<td colspan="2" class="oembed_container">' . $oembed_data[ 'html' ] . '</td><!-- .oembed_container -->';

				$table_rows[ 'oembed_footer' ] = '<td colspan="2">' . $this->p->msgs->get( 'info-meta-oembed-footer' ) . '</td>';

			} else {

				$table_rows[ 'no_oembed_html' ] = '<td colspan="2"><p class="status-msg">' . __( 'No oEmbed HTML found.', 'wpsso' ) . '</p></td>';
			}

			return $table_rows;
		}

		public function filter_metabox_sso_validators_rows( $table_rows, $form, $head_info, $mod ) {

			$validators = $this->p->util->get_validators( $mod, $form );

			foreach ( $validators as $key => $el ) {

				if ( empty( $el[ 'type' ] ) ) {

					continue;
				}

				$extra_msg    = isset( $el[ 'extra_msg' ] ) ? $el[ 'extra_msg' ] : '';
				$button_label = sprintf( _x( 'Validate %s', 'submit button', 'wpsso' ), $el[ 'type' ] );
				$is_disabled  = empty( $el[ 'url' ] ) ? true : false;

				$table_rows[ 'validate_' . $key ] = '' .
					$form->get_th_html( $el[ 'title' ], $css_class = 'medium' ) .
					'<td class="validate">' . $this->p->msgs->get( 'info-meta-validate-' . $key ) . $extra_msg . '</td>' .
					'<td class="validate">' . $form->get_button( $button_label, $css_class = 'button-secondary', $css_id = '',
						$el[ 'url' ], $newtab = true, $is_disabled ) . '</td>';
			}

			$table_rows[ 'validate_info' ] = '<td class="validate" colspan="3">' . $this->p->msgs->get( 'info-meta-validate-footer' ) . '</td>';

			return $table_rows;
		}
	}
}
