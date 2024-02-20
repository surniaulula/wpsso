<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoEditMedia' ) ) {

	class WpssoEditMedia {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * See WpssoAbstractWpMeta->get_document_sso_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'mb_sso_edit_media_rows'            => 4,
				'mb_sso_edit_media_prio_image_rows' => 5,
				'mb_sso_edit_media_twitter_rows'    => 5,
				'mb_sso_edit_media_schema_rows'     => 5,
				'mb_sso_edit_media_pinterest_rows'  => 5,
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_mb_sso_edit_media_rows( $table_rows, $form, $head_info, $mod ) {

			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			$args = array(
				'canonical_url' => $this->p->util->get_canonical_url( $mod ),
			);

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

			foreach( array(
				'wpsso_mb_sso_edit_media_prio_image_rows',
				'wpsso_mb_sso_edit_media_prio_video_rows',
				'wpsso_mb_sso_edit_media_og_rows',
				'wpsso_mb_sso_edit_media_twitter_rows',
				'wpsso_mb_sso_edit_media_schema_rows',
				'wpsso_mb_sso_edit_media_pinterest_rows',
			) as $filter_name ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
				}

				$table_rows = apply_filters( $filter_name, $table_rows, $form, $head_info, $mod, $args );
			}

			return $table_rows;
		}

		public function filter_mb_sso_edit_media_prio_image_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$this->p->util->maybe_set_ref( $args[ 'canonical_url' ], $mod, __( 'getting open graph image', 'wpsso' ) );

			$size_name     = 'wpsso-opengraph';
			$media_request = array( 'pid' );
			$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = 'none' );

			$this->p->util->maybe_unset_ref( $args[ 'canonical_url' ] );

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

		/*
		 * X (Twitter) Card
		 *
		 * App and Player cards do not have a $size_name.
		 *
		 * Only show custom image options for the Summary and Summary Large Image cards.
		 */
		public function filter_mb_sso_edit_media_twitter_rows( $table_rows, $form, $head_info, $mod, $args ) {

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

					$this->p->util->maybe_set_ref( $args[ 'canonical_url' ], $mod, __( 'getting twitter card image', 'wpsso' ) );

					$media_request = array( 'pid' );
					$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = 'og' );

					$this->p->util->maybe_unset_ref( $args[ 'canonical_url' ] );

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

				$table_rows = $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
			}

			return $table_rows;
		}

		/*
		 * Schema.
		 */
		public function filter_mb_sso_edit_media_schema_rows( $table_rows, $form, $head_info, $mod, $args ) {

			if ( ! $mod[ 'is_public' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'exiting early: object is not public' );
				}

				return $table_rows;

			} elseif ( $this->p->util->is_schema_disabled() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'exiting early: schema markup is disabled' );
				}

				return $table_rows;
			}

			$this->p->util->maybe_set_ref( $args[ 'canonical_url' ], $mod, __( 'getting schema 1:1 image', 'wpsso' ) );

			$size_name     = 'wpsso-schema-1x1';
			$media_request = array( 'pid' );
			$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = 'og' );

			$this->p->util->maybe_unset_ref( $args[ 'canonical_url' ] );

			$form_rows = array(
				'subsection_schema' => array(
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Schema Markup and Google Rich Results', 'metabox title', 'wpsso' )
				),
				'schema_img_id' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_img_id',
					'content'  => $form->get_input_image_upload( 'schema_img', $media_info[ 'pid' ] ),
				),
				'schema_img_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_img_url',
					'content'  => $form->get_input_image_url( 'schema_img', '' ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );

			return $table_rows;
		}

		/*
		 * Pinterest Pin It.
		 */
		public function filter_mb_sso_edit_media_pinterest_rows( $table_rows, $form, $head_info, $mod, $args ) {

			if ( ! $mod[ 'is_public' ] ) {

				return $table_rows;
			}

			$pin_img_msg      = $this->p->msgs->maybe_pin_img_disabled();
			$pin_img_disabled = $pin_img_msg? true : false;
			$media_info       = array( 'pid' => '' );

			if ( ! $pin_img_disabled ) {

				$this->p->util->maybe_set_ref( $args[ 'canonical_url' ], $mod, __( 'getting pinterest image', 'wpsso' ) );

				$size_name     = 'wpsso-pinterest';
				$media_request = array( 'pid' );
				$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = array( 'schema', 'og' ) );

				$this->p->util->maybe_unset_ref( $args[ 'canonical_url' ] );
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
	}
}
