<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSubmenuEssential' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuEssential extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;
		}

		// called by the extended WpssoAdmin class
		protected function add_meta_boxes() {

			$this->maybe_show_language_notice();

			add_meta_box( $this->pagehook.'_general',
				_x( 'Essential General Settings', 'metabox title', 'wpsso' ),
					array( $this, 'show_metabox_general' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_advanced',
				_x( 'Optional Advanced Settings', 'metabox title', 'wpsso' ),
					array( $this, 'show_metabox_advanced' ), $this->pagehook, 'normal' );

			/**
			 * Issues a warning notice if the default image size is too small.
			 * Unless the WPSSO_CHECK_DEFAULT_IMAGE constant has been defined as false.
			 */
			if ( SucomUtil::get_const( 'WPSSO_CHECK_DEFAULT_IMAGE' ) !== false ) {
				$this->p->media->get_default_images( 1, $this->p->lca.'-opengraph', false );
			}
		}

		public function show_metabox_general() {

			$metabox_id = 'essential';

			$tabs = apply_filters( $this->p->lca.'_essential_general_tabs', array(
				'general' => _x( 'Site Information', 'metabox tab', 'wpsso' ),
				'facebook' => _x( 'Facebook / Open Graph', 'metabox tab', 'wpsso' ),
				'google' => _x( 'Google / Schema', 'metabox tab', 'wpsso' ),
				'pinterest' => _x( 'Pinterest', 'metabox tab', 'wpsso' ),
				'twitter' => _x( 'Twitter', 'metabox tab', 'wpsso' ),
			) );

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {
				$table_rows[$tab_key] = apply_filters( $this->p->lca.'_'.$metabox_id.'_'.$tab_key.'_rows',
					$this->get_table_rows( $metabox_id, $tab_key ), $this->form );
			}

			$this->p->util->do_metabox_tabbed( $metabox_id, $tabs, $table_rows );
		}

		public function show_metabox_advanced() {

			$metabox_id = 'optional';
			$tab_key    = 'advanced';

			$this->p->util->do_metabox_table( apply_filters( $this->p->lca.'_'.$metabox_id.'_'.$tab_key.'_rows',
				$this->get_table_rows( $metabox_id, $tab_key ), $this->form, false ), 'metabox-'.$metabox_id.'-'.$tab_key );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			switch ( $metabox_id.'-'.$tab_key ) {

				case 'essential-general':

					$table_rows['site_name'] = $this->form->get_th_html( _x( 'WebSite Name',
						'option label', 'wpsso' ), null, 'site_name', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'site_name', $this->p->options ),
						null, null, null, get_bloginfo( 'name', 'display' ) ).'</td>';

					$table_rows['site_desc'] = $this->form->get_th_html( _x( 'WebSite Description',
						'option label', 'wpsso' ), null, 'site_desc', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_textarea( SucomUtil::get_key_locale( 'site_desc', $this->p->options ),
						null, null, null, get_bloginfo( 'description', 'display' ) ).'</td>';

					$table_rows['og_art_section'] = $this->form->get_th_html( _x( 'Default Article Topic',
						'option label', 'wpsso' ), null, 'og_art_section' ).
					'<td>'.$this->form->get_select( 'og_art_section', $this->p->util->get_article_topics() ).'</td>';

					break;

				case 'essential-facebook':

					$table_rows['fb_publisher_url'] = $this->form->get_th_html( _x( 'Facebook Business Page URL',
						'option label', 'wpsso' ), null, 'fb_publisher_url', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'fb_publisher_url', $this->p->options ), 'wide' ).'</td>';

					$table_rows['fb_app_id'] = $this->form->get_th_html( _x( 'Facebook Application ID',
						'option label', 'wpsso' ), null, 'fb_app_id' ).
					'<td>'.$this->form->get_input( 'fb_app_id' ).'</td>';

					$table_rows['og_def_img_id'] = $this->form->get_th_html( _x( 'Default / Fallback Image ID',
						'option label', 'wpsso' ), null, 'og_def_img_id' ).
					'<td>'.$this->form->get_input_image_upload( 'og_def_img' ).'</td>';

					$table_rows['og_def_img_url'] = $this->form->get_th_html( _x( 'or Default / Fallback Image URL',
						'option label', 'wpsso' ), null, 'og_def_img_url' ).
					'<td>'.$this->form->get_input_image_url( 'og_def_img' ).'</td>';

					break;

				case 'essential-google':

					$table_rows['seo_publisher_url'] = $this->form->get_th_html( _x( 'Google+ Business Page URL',
						'option label', 'wpsso' ), null, 'seo_publisher_url', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'seo_publisher_url', $this->p->options ), 'wide' ).'</td>';

					$this->add_schema_knowledge_graph_table_rows( $table_rows );

					$table_rows['schema_logo_url'] = $this->form->get_th_html(
						'<a href="https://developers.google.com/structured-data/customize/logos">'.
						_x( 'Organization Logo URL', 'option label', 'wpsso' ).'</a>',
							'', 'schema_logo_url', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'schema_logo_url', $this->p->options ), 'wide' ).'</td>';

					$table_rows['schema_banner_url'] = $this->form->get_th_html( _x( 'Organization Banner URL',
						'option label', 'wpsso' ), '', 'schema_banner_url', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'schema_banner_url', $this->p->options ), 'wide' ).'</td>';

					break;

				case 'essential-pinterest':

					$table_rows['p_publisher_url'] = $this->form->get_th_html( _x( 'Pinterest Company Page URL',
						'option label', 'wpsso' ), null, 'p_publisher_url', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'p_publisher_url', $this->p->options ), 'wide' ).'</td>';

					break;

				case 'essential-twitter':

					$table_rows['tc_site'] = $this->form->get_th_html( _x( 'Twitter Business @username',
						'option label', 'wpsso' ), null, 'tc_site', array( 'is_locale' => true ) ).
					'<td>'.$this->form->get_input( SucomUtil::get_key_locale( 'tc_site', $this->p->options ) ).'</td>';

					break;

				case 'optional-advanced':

					$this->add_optional_advanced_table_rows( $table_rows );

					/**
					 * Don't show these options in the Essential settings page.
					 */
					unset ( $table_rows['plugin_debug'] );
					unset ( $table_rows['plugin_hide_pro'] );

					break;
			}

			return $table_rows;
		}
	}
}
