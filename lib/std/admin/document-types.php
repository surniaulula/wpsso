<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoStdAdminDocumentTypes' ) ) {

	class WpssoStdAdminDocumentTypes {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'document_types_og_types_rows'     => 2,
				'document_types_schema_types_rows' => 2,
			) );
		}

		public function filter_document_types_og_types_rows( array $table_rows, $form ) {

			$og_types = $this->p->og->get_og_types_select();

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			/**
			 * Open Graph Type.
			 */
			foreach ( array( 
				'home_page'    => _x( 'Open Graph Type for Page Homepage', 'option label', 'wpsso' ),
				'home_posts'   => _x( 'Open Graph Type for Posts Homepage', 'option label', 'wpsso' ),
				'user_page'    => _x( 'Open Graph Type for User Profile', 'option label', 'wpsso' ),
				'search_page'  => _x( 'Open Graph Type for Search Results', 'option label', 'wpsso' ),
				'archive_page' => _x( 'Open Graph Type for Other Archive', 'option label', 'wpsso' ),
			) as $type_name => $th_label ) {

				$opt_key = 'og_type_for_' . $type_name;	// Hard-coded value - no sanitation required.

				$table_rows[ $opt_key ] = $form->get_tr_hide( 'basic', $opt_key ) .
					$form->get_th_html( $th_label, $css_class = '', $opt_key ) . 
					'<td class="blank">' . $form->get_no_select( $opt_key, $og_types, $css_class = 'og_type' ) . '</td>';
			}

			/**
			 * Open Graph Type by Post Type.
			 */
			$type_select = '';
			$post_types  = SucomUtilWP::get_post_types( $output = 'objects' );

			foreach ( $post_types as $obj ) {

				$opt_key = SucomUtil::sanitize_hookname( 'og_type_for_' . $obj->name );

				$obj_label = SucomUtilWP::get_object_label( $obj );

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $og_types, $css_class = 'og_type' ) . ' ' .
					sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $obj_label ) . '</p>' . "\n";
			}

			$opt_key = 'og_type_for_post_archive';	// Hard-coded value - no sanitation required.

			$type_select .= '<p>' . $form->get_no_select( $opt_key, $og_types, $css_class = 'og_type' ) . ' ' .
				sprintf( _x( 'for %s', 'option comment', 'wpsso' ), _x( 'Post Type Archive Page', 'option comment', 'wpsso' ) ) . '</p>' . "\n";

			$table_rows[ 'og_type_for_ptn' ] = '' .
				$form->get_th_html( _x( 'Open Graph Type by Post Type', 'option label', 'wpsso' ), $css_class = '', $css_id = 'og_type_for_ptn' ) .
				'<td class="blank">' . $type_select . '</td>';

			/**
			 * Open Graph Type by Taxonomy.
			 */
			$type_select = '';
			$type_keys   = array();
			$taxonomies  = SucomUtilWP::get_taxonomies( $output = 'objects' );

			foreach ( $taxonomies as $obj ) {

				$type_keys[] = $opt_key = SucomUtil::sanitize_hookname( 'og_type_for_tax_' . $obj->name );

				$obj_label = SucomUtilWP::get_object_label( $obj );

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $og_types, $css_class = 'og_type' ) . ' ' .
					sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $obj_label ) . '</p>' . "\n";
			}

			$table_rows[ 'og_type_for_ttn' ] = $form->get_tr_hide( 'basic', $type_keys ) .
				$form->get_th_html( _x( 'Open Graph Type by Taxonomy', 'option label', 'wpsso' ), $css_class = '', $css_id = 'og_type_for_ttn' ) .
				'<td class="blank">' . $type_select . '</td>';

			return $table_rows;
		}

		public function filter_document_types_schema_types_rows( array $table_rows, $form ) {

			$schema_exp_secs = $this->p->util->get_cache_exp_secs( 'wpsso_t_' );	// Default is month in seconds.

			$schema_types = $this->p->schema->get_schema_types_select( $context = 'settings' );

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>';

			/**
			 * Schema Type.
			 */
			foreach ( array( 
				'home_page'    => _x( 'Schema Type for Page Homepage', 'option label', 'wpsso' ),
				'home_posts'   => _x( 'Schema Type for Posts Homepage', 'option label', 'wpsso' ),
				'user_page'    => _x( 'Schema Type for User Profile', 'option label', 'wpsso' ),
				'search_page'  => _x( 'Schema Type for Search Results', 'option label', 'wpsso' ),
				'archive_page' => _x( 'Schema Type for Other Archive', 'option label', 'wpsso' ),
			) as $type_name => $th_label ) {

				$opt_key = 'schema_type_for_' . $type_name;	// Hard-coded value - no sanitation required.

				$table_rows[ $opt_key ] = $form->get_tr_hide( 'basic', $opt_key ) . 
					$form->get_th_html( $th_label, $css_class = '', $opt_key ) . 
					'<td class="blank">' . $form->get_no_select( $opt_key, $schema_types, $css_class = 'schema_type', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
							$event_args = array(
								'json_var'  => 'schema_types',
								'exp_secs'  => $schema_exp_secs,	// Create and read from a javascript URL.
								'is_transl' => true,			// No label translation required.
								'is_sorted' => true,			// No label sorting required.
							)
						) .
					'</td>';
			}

			/**
			 * Schema Type by Post Type.
			 */
			$type_select = '';
			$post_types  = SucomUtilWP::get_post_types( $output = 'objects' );

			foreach ( $post_types as $obj ) {

				$opt_key = SucomUtil::sanitize_hookname( 'schema_type_for_' . $obj->name );

				$obj_label = SucomUtilWP::get_object_label( $obj );

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $schema_types, $css_class = 'schema_type', $css_id = '',
					$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
						$event_args = array(
							'json_var'  => 'schema_types',
							'exp_secs'  => $schema_exp_secs,	// Create and read from a javascript URL.
							'is_transl' => true,			// No label translation required.
							'is_sorted' => true,			// No label sorting required.
						)
					) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $obj_label ) . '</p>' . "\n";
			}

			$opt_key = 'schema_type_for_post_archive';	// Hard-coded value - no sanitation required.

			$type_select .= '<p>' . $form->get_no_select( $opt_key, $schema_types, $css_class = 'schema_type', $css_id = '',
				$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
					$event_args = array(
						'json_var'  => 'schema_types',
						'exp_secs'  => $schema_exp_secs,	// Create and read from a javascript URL.
						'is_transl' => true,			// No label translation required.
						'is_sorted' => true,			// No label sorting required.
					)
				) . ' ' .
				sprintf( _x( 'for %s', 'option comment', 'wpsso' ), _x( 'Post Type Archive Page', 'option comment', 'wpsso' ) ) .
				'</p>' . "\n";

			$table_rows[ 'schema_type_for_ptn' ] = '' .
				$form->get_th_html( _x( 'Schema Type by Post Type', 'option label', 'wpsso' ), $css_class = '', $css_id = 'schema_type_for_ptn' ) .
				'<td class="blank">' . $type_select . '</td>';

			/**
			 * Schema Type by Taxonomy.
			 */
			$type_select = '';
			$type_keys   = array();
			$taxonomies  = SucomUtilWP::get_taxonomies( $output = 'objects' );

			foreach ( $taxonomies as $obj ) {

				$type_keys[] = $opt_key = SucomUtil::sanitize_hookname( 'schema_type_for_tax_' . $obj->name );

				$obj_label = SucomUtilWP::get_object_label( $obj );

				$type_select .= '<p>' . $form->get_no_select( $opt_key, $schema_types, $css_class = 'schema_type', $css_id = '',
					$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
						$event_args = array(
							'json_var'  => 'schema_types',
							'exp_secs'  => $schema_exp_secs,	// Create and read from a javascript URL.
							'is_transl' => true,			// No label translation required.
							'is_sorted' => true,			// No label sorting required.
						)
					) . ' ' . sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $obj_label ) . '</p>' . "\n";
			}

			$table_rows[ 'schema_type_for_ttn' ] = $form->get_tr_hide( 'basic', $type_keys ) .
				$form->get_th_html( _x( 'Schema Type by Taxonomy', 'option label', 'wpsso' ), $css_id = '', $css_class = 'schema_type_for_ttn' ) .
				'<td class="blank">' . $type_select . '</td>';

			return $table_rows;
		}
	}
}
