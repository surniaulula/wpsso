<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoGplAdminMeta' ) ) {

	class WpssoGplAdminMeta {

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'meta_edit_rows' => array(
					'user_edit_rows' => 4,
					'term_edit_rows' => 4,
				),
				'meta_media_rows' => array(
					'post_media_rows' => 4,
					'user_media_rows' => 4,
					'term_media_rows' => 4,
				),
			) );
		}

		public function filter_meta_edit_rows( $table_rows, $form, $head, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$dots           = '...';
			$read_cache     = true;
			$no_hashtags    = false;
			$maybe_hashtags = true;
			$do_encode      = true;

			/**
			 * The 'add_link_rel_canonical' and 'add_meta_name_description' options will be empty if an SEO plugin is detected.
			 */
			$add_link_rel_canon = empty( $this->p->options[ 'add_link_rel_canonical' ] ) ? false : true;
			$add_meta_name_desc = empty( $this->p->options[ 'add_meta_name_description' ] ) ? false : true;
			$add_meta_name_desc = apply_filters( $this->p->lca . '_add_meta_name_description', $add_meta_name_desc, $mod );

			$sharing_url   = $this->p->util->get_sharing_url( $mod, false );	// $add_page is false.
			$canonical_url = $this->p->util->get_canonical_url( $mod, false );	// $add_page is false.

			$og_title_max_len    = $this->p->options[ 'og_title_max_len' ];
			$og_desc_max_len     = $this->p->options[ 'og_desc_max_len' ];
			$seo_desc_max_len    = $this->p->options[ 'seo_desc_max_len' ];
			$tc_desc_max_len     = $this->p->options[ 'tc_desc_max_len' ];
			$schema_desc_max_len = $this->p->options[ 'schema_desc_max_len' ];
			$schema_desc_md_key  = array( 'seo_desc', 'og_desc' );

			$def_og_title    = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, 'none' );
			$def_og_desc     = $this->p->page->get_description( $og_desc_max_len, $dots, $mod, $read_cache, $maybe_hashtags, $do_encode, 'none' );
			$def_seo_desc    = $add_meta_name_desc ? $this->p->page->get_description( $seo_desc_max_len, $dots, $mod, $read_cache, $no_hashtags ) : '';
			$def_tc_desc     = $this->p->page->get_description( $tc_desc_max_len, $dots, $mod, $read_cache );
			$def_schema_desc = $this->p->page->get_description( $schema_desc_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, $schema_desc_md_key );

			if ( empty( $this->p->cf[ 'plugin' ][ 'wpssojson' ][ 'version' ] ) ) {

				$json_info       = $this->p->cf[ 'plugin' ][ 'wpssojson' ];
				$json_addon_link = $this->p->util->get_admin_url( 'addons#wpssojson', $json_info[ 'name' ] );
				$json_msg_transl = '<p class="status-msg smaller">' . 
					sprintf( __( 'Activate the %s add-on for additional Schema markup features and options.',
						'wpsso' ), $json_addon_link ) . '</p>';

			} else {
				$json_msg_transl = '';
			}

			$seo_msg_transl = __( 'This option is disabled (the "%1$s" head tag is disabled or an SEO plugin was detected).', 'wpsso' );

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$form_rows = array(
				'og_title' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Default Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_title',
					'content'  => $form->get_no_input_value( $def_og_title, 'wide' ),
				),
				'og_desc' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Default Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_desc',
					'content'  => $form->get_no_textarea_value( $def_og_desc, '', '', $og_desc_max_len ),
				),
				'seo_desc' => array(
					'tr_class' => ( $add_meta_name_desc ? '' : 'hide_in_basic' ), // Always hide if head tag is disabled.
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Search Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-seo_desc',
					'content'  => $form->get_no_textarea_value( $def_seo_desc, '', '', $seo_desc_max_len ) .
						( $add_meta_name_desc ? '' : '<p class="status-msg smaller">' . 
							sprintf( $seo_msg_transl, 'meta name description' ) . '</p>' ),
				),
				'tc_desc' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Twitter Card Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-tc_desc',
					'content'  => $form->get_no_textarea_value( $def_tc_desc, '', '', $tc_desc_max_len ),
				),
				'sharing_url' => array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'sharing_url' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Sharing URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-sharing_url',
					'content'  => $form->get_no_input_value( $sharing_url, 'wide' ),
				),
				'canonical_url' => array(
					'tr_class' => ( $add_link_rel_canon ? $form->get_css_class_hide( 'basic', 'canonical_url' ) : 'hide_in_basic' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Canonical URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-canonical_url',
					'content'  => $form->get_no_input_value( $canonical_url, 'wide' ) .
						( $add_link_rel_canon ? '' : '<p class="status-msg smaller">' . 
							sprintf( $seo_msg_transl, 'link rel canonical' ) . '</p>' ),
				),
				'subsection_schema' => array(
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Structured Data / Schema Markup', 'metabox title', 'wpsso' )
				),
				'schema_desc' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_desc',
					'content'  => $form->get_no_textarea_value( $def_schema_desc, '', '', $schema_desc_max_len ) . $json_msg_transl,
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head, $mod );
		}

		public function filter_meta_media_rows( $table_rows, $form, $head, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] && ( empty( $mod[ 'post_status' ] ) || $mod[ 'post_status' ] === 'auto-draft' ) ) {

				$table_rows[] = '<td><blockquote class="status-info"><p class="centered">' . 
					sprintf( __( 'Save a draft version or publish the %s to display these options.',
						'wpsso' ), SucomUtil::titleize( $mod[ 'post_type' ] ) ) . '</p></td>';

				return $table_rows;	// abort
			}

			$media_info = $this->p->og->get_media_info( $this->p->lca . '-opengraph',
				array( 'pid', 'img_url' ), $mod, $md_pre = 'none', $mt_pre = 'og' );

			$table_rows[] = '<td colspan="2">' .
				( $mod[ 'is_post' ] ? $this->p->msgs->get( 'pro-about-msg-post-media' ) : '' ) .
					$this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$form_rows[ 'subsection_opengraph' ] = array(
				'td_class' => 'subsection top',
				'header'   => 'h4',
				'label'    => _x( 'All Social WebSites / Open Graph', 'metabox title', 'wpsso' )
			);

			$form_rows[ 'subsection_priority_image' ] = array(
				'td_class' => 'subsection top',
				'header'   => 'h5',
				'label'    => _x( 'Priority Image Information', 'metabox title', 'wpsso' )
			);

			if ( $mod[ 'is_post' ] ) {
				$form_rows[ 'og_img_max' ] = array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_img_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_img_max',
					'content'  => $form->get_no_select( 'og_img_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				);
			}

			$form_rows[ 'og_img_dimensions' ] = array(
				'tr_class' => $form->get_css_class_hide_img_dim( 'basic', 'og_img' ),
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image Dimensions', 'option label', 'wpsso' ),
				'tooltip'  => 'og_img_dimensions',
				'content'  => $form->get_no_input_image_dimensions( 'og_img', true ),	// $use_opts is true.
			);

			$form_rows[ 'og_img_id' ] = array(
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-og_img_id',
				'content'  => $form->get_no_input_image_upload( 'og_img', $media_info[ 'pid' ], true ),
			);

			$form_rows[ 'og_img_url' ] = array(
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-og_img_url',
				'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], 'wide' ),
			);

			$form_rows[ 'subsection_priority_video' ] = array(
				'td_class' => 'subsection',
				'header'   => 'h5',
				'label'    => _x( 'Priority Video Information', 'metabox title', 'wpsso' )
			);

			$form_rows[ 'og_vid_prev_img' ] = array(
				'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_prev_img' ),
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Include Preview Images', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-og_vid_prev_img',
				'content'  => $form->get_no_checkbox( 'og_vid_prev_img' ),
			);

			if ( $mod[ 'is_post' ] ) {
				$form_rows[ 'og_vid_max' ] = array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Videos', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_max',
					'content'  => $form->get_no_select( 'og_vid_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				);
			}

			$form_rows[ 'og_vid_dimensions' ] = array(
				'tr_class' => $form->get_css_class_hide_vid_dim( 'basic', 'og_vid' ),
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Video Dimensions', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-og_vid_dimensions',
				'content'  => $form->get_no_input_video_dimensions( 'og_vid' ),
			);

			$form_rows[ 'og_vid_embed' ] = array(
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Video Embed HTML', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-og_vid_embed',
				'content'  => $form->get_no_textarea_value( '' ),	// Free version does not include video modules.
			);

			$form_rows[ 'og_vid_url' ] = array(
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'or a Video URL', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-og_vid_url',
				'content'  => $form->get_no_input_value( '', 'wide' ),	// Free version does not include video modules.
			);

			$form_rows[ 'og_vid_title' ] = array(
				'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_title' ),
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Video Name / Title', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-og_vid_title',
				'content'  => $form->get_no_input_value( '', 'wide' ),	// Free version does not include video modules.
			);

			$form_rows[ 'og_vid_desc' ] = array(
				'tr_class' => $form->get_css_class_hide( 'basic', 'og_vid_desc' ),
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Video Description', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-og_vid_desc',
				'content'  => $form->get_no_textarea_value( '' ),	// Free version does not include video modules.
			);

			/**
			 * Twitter Card
			 */
			list( $card_type, $size_name, $md_pre ) = $this->p->tc->get_card_type_size( $mod );

			$media_info = $this->p->og->get_media_info( $size_name,
				array( 'pid', 'img_url' ), $mod, 'og', 'og' );
	
			$tc_tr_class = $form->in_options( '/^' . $md_pre . '_img_/', true ) ? '' : 'hide_in_basic';	// Hide unless a custom twitter card image exists.

			$form_rows[ 'subsection_tc' ] = array(
				'tr_class' => $tc_tr_class,
				'td_class' => 'subsection',
				'header'   => 'h4',
				'label'    => _x( 'Twitter Card', 'metabox title', 'wpsso' )
			);

			$form_rows[ $md_pre . '_img_dimensions' ] = array(
				'tr_class' => $form->get_css_class_hide_img_dim( 'basic', $md_pre . '_img' ),
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image Dimensions', 'option label', 'wpsso' ),
				'tooltip'  => $md_pre . '_img_dimensions',
				'content'  => $form->get_no_input_image_dimensions( $md_pre . '_img', true ),	// $use_opts is true.
			);

			$form_rows[ $md_pre . '_img_id' ] = array(
				'tr_class' => $tc_tr_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-' . $md_pre . '_img_id',
				'content'  => $form->get_no_input_image_upload( $md_pre . '_img', $media_info[ 'pid' ], true ),
			);

			$form_rows[ $md_pre . '_img_url' ] = array(
				'tr_class' => $tc_tr_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-' . $md_pre . '_img_url',
				'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], 'wide' ),
			);

			/**
			 * Structured Data / Schema Markup / Pinterest
			 */
			$media_info = $this->p->og->get_media_info( $this->p->lca . '-schema',
				array( 'pid', 'img_url' ), $mod, 'og', 'og' );
	
			$schema_tr_class = $form->in_options( '/^schema_img_/', true ) ? '' : 'hide_in_basic';	// Hide unless a custom schema image exists.

			$form_rows[ 'subsection_schema' ] = array(
				'tr_class' => $schema_tr_class,
				'td_class' => 'subsection', 'header' => 'h4',
				'label'    => _x( 'Structured Data / Schema Markup / Pinterest', 'metabox title', 'wpsso' )
			);

			if ( $mod[ 'is_post' ] ) {
				$form_rows[ 'schema_img_max' ] = array(
					'tr_class' => $form->get_css_class_hide( 'basic', 'schema_img_max' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_img_max',
					'content'  => $form->get_no_select( 'schema_img_max',
						range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'medium' ),
				);
			}

			$form_rows[ 'schema_img_dimensions' ] = array(
				'tr_class' => $form->get_css_class_hide_img_dim( 'basic', 'schema_img' ),
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image Dimensions', 'option label', 'wpsso' ),
				'tooltip'  => 'schema_img_dimensions',
				'content'  => $form->get_no_input_image_dimensions( 'schema_img', true ),	// $use_opts is true.
			);

			$form_rows[ 'schema_img_id' ] = array(
				'tr_class' => $schema_tr_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-schema_img_id',
				'content'  => $form->get_no_input_image_upload( 'schema_img', $media_info[ 'pid' ], true ),
			);

			$form_rows[ 'schema_img_url' ] = array(
				'tr_class' => $schema_tr_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-schema_img_url',
				'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], 'wide' ),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head, $mod );
		}
	}
}
