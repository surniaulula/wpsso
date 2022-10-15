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

if ( ! class_exists( 'WpssoEditSchema' ) ) {

	class WpssoEditSchema {

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
				'metabox_sso_edit_schema_rows'               => 4,
				'metabox_sso_edit_schema_creative_work_rows' => 6,	// Schema CreativeWork.
				'metabox_sso_edit_schema_article_rows'       => 5,	// Schema CreativeWork > Article.
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_metabox_sso_edit_schema_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->util->is_schema_disabled() ) {

				return $table_rows;
			}

			$limits               = WpssoConfig::get_input_limits();	// Uses a local cache.
			$type_row_class       = WpssoSchema::get_schema_type_row_class( 'schema_type' );
			$def_schema_title     = $this->p->page->get_title( $mod, $md_key = 'seo_title', $max_len = 'schema_title' );
			$def_schema_title_alt = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_title_alt' );
			$def_schema_desc      = $this->p->page->get_description( $mod, $md_key = 'seo_desc', $max_len = 'schema_desc' );
			$addl_type_url_max    = SucomUtil::get_const( 'WPSSO_SCHEMA_ADDL_TYPE_URL_MAX', 5 );
			$sameas_url_max       = SucomUtil::get_const( 'WPSSO_SCHEMA_SAMEAS_URL_MAX', 5 );

			$names = array(
				'org'          => $this->p->util->get_form_cache( 'org_names', $add_none = true ),
				'person'       => $this->p->util->get_form_cache( 'person_names', $add_none = true ),
				'place'        => $this->p->util->get_form_cache( 'place_names', $add_none = true ),
				'place_custom' => $this->p->util->get_form_cache( 'place_names_custom', $add_none = true ),
			);

			$form_rows = array(
				'subsection_schema' => array(
					'td_class' => 'subsection top',
					'header'   => 'h4',
					'label'    => _x( 'Schema Markup and Google Rich Results', 'metabox title', 'wpsso' )
				),
				'info_schema_faq' => array(
					'tr_class'  => $type_row_class[ 'faq' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-faq' ) . '</td>',
				),
				'info_schema_qa' => array(
					'tr_class'  => $type_row_class[ 'qa' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-qa' ) . '</td>',
				),
				'info_schema_question' => array(
					'tr_class'  => $type_row_class[ 'question' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-question' ) . '</td>',
				),
				'schema_title' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_title',
					'content'  => $form->get_input_dep( 'schema_title', $css_class = 'wide', $css_id = '',
						$limits[ 'schema_title' ], $def_schema_title, $is_disabled = false, $dep_id = 'seo_title' ),
				),
				'schema_title_alt' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Alternate Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_title_alt',
					'content'  => $form->get_input_dep( 'schema_title_alt', $css_class = 'wide', $css_id = '',
						$limits[ 'schema_title_alt' ], $def_schema_title_alt, $is_disabled = false, $dep_id = 'schema_title' ),
				),
				'schema_desc' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_desc',
					'content'  => $form->get_textarea_dep( 'schema_desc', $css_class = '', $css_id = '',
						$limits[ 'schema_desc' ], $def_schema_desc, $is_disabled = false, $dep_id = 'seo_desc' ),
				),
				'schema_addl_type_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Microdata Type URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_addl_type_url',
					'content'  => $form->get_input_multi( 'schema_addl_type_url', $css_class = 'wide', '',
						$start_num = 0, $addl_type_url_max, $show_first = 1 ),
				),
				'schema_sameas_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Same-As URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_sameas_url',
					'content'  => $form->get_input_multi( 'schema_sameas_url', $css_class = 'wide', '',
						$start_num = 0, $sameas_url_max, $show_first = 1 ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );

			/**
			 * Schema CreativeWork.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_creative_work_rows', $table_rows, $form, $head_info, $mod, $type_row_class, $names );

			/**
			 * Schema CreativeWork > Article.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_article_rows', $table_rows, $form, $head_info, $mod, $type_row_class );

			/**
			 * Schema CreativeWork > Book.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_book_rows', $table_rows, $form, $head_info, $mod, $type_row_class );

			/**
			 * Schema CreativeWork > HowTo.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_howto_rows', $table_rows, $form, $head_info, $mod, $type_row_class );

			/**
			 * Schema CreativeWork > HowTo > Recipe.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_recipe_rows', $table_rows, $form, $head_info, $mod, $type_row_class );

			/**
			 * Schema CreativeWork > Movie.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_movie_rows', $table_rows, $form, $head_info, $mod, $type_row_class, $names );

			/**
			 * Schema CreativeWork > Review.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_review_rows', $table_rows, $form, $head_info, $mod, $type_row_class );

			/**
			 * Schema CreativeWork > Software Application.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_software_app_rows', $table_rows, $form, $head_info, $mod, $type_row_class );

			/**
			 * Schema CreativeWork > WebPage > QAPage.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_qa_rows', $table_rows, $form, $head_info, $mod, $type_row_class );

			/**
			 * Schema Event.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_event_rows', $table_rows, $form, $head_info, $mod, $type_row_class, $names );

			/**
			 * Schema Intangible > JobPosting.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_job_posting_rows', $table_rows, $form, $head_info, $mod, $type_row_class, $names );

			/**
			 * Schema Organization.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_organization_rows', $table_rows, $form, $head_info, $mod, $type_row_class, $names );

			/**
			 * Schema Person.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_person_rows', $table_rows, $form, $head_info, $mod, $type_row_class, $names );

			/**
			 * Schema Place.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_place_rows', $table_rows, $form, $head_info, $mod, $type_row_class, $names );

			/**
			 * Schema Product.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_product_rows', $table_rows, $form, $head_info, $mod, $type_row_class );

			return $table_rows;
		}

		public function filter_metabox_sso_edit_schema_creative_work_rows( $table_rows, $form, $head_info, $mod, $type_row_class, $names ) {

			$limits               = WpssoConfig::get_input_limits();	// Uses a local cache.
			$def_schema_headline  = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_headline' );
			$def_schema_text      = $this->p->page->get_text( $mod, $md_key = '', $max_len = 'schema_text' );
			$def_schema_keywords  = $this->p->page->get_keywords( $mod, $md_key = '' );
			$schema_lang_disabled = $this->p->avail[ 'lang' ][ 'any' ] ? true : false;
			$ispartof_url_max     = SucomUtil::get_const( 'WPSSO_SCHEMA_ISPARTOF_URL_MAX', 20 );

			$form_rows = array(
				'subsection_schema_creative_work' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Creative Work Information', 'metabox title', 'wpsso' )
				),
				'schema_ispartof_url' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Is Part of URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_ispartof_url',
					'content'  => $form->get_input_multi( 'schema_ispartof_url', $css_class = 'wide', '',
						$start_num = 0, $ispartof_url_max, $show_first = 1 ),
				),
				'schema_headline' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Headline', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_headline',
					'content'  => $form->get_input_dep( 'schema_headline', $css_class = 'wide', $css_id = '',
						$limits[ 'schema_headline' ], $def_schema_headline, $is_disabled = false, $dep_id = 'schema_title' ),
				),
				'schema_text' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Full Text', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_text',
					'content'  => $form->get_textarea( 'schema_text', $css_class = 'full_text', $css_id = '', $max_len = 0, $def_schema_text ),
				),
				'schema_keywords' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Keywords', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_keywords',
					'content'  => $form->get_input( 'schema_keywords', $css_class = 'wide', $css_id = '', $max_len = 0, $def_schema_keywords ),
				),
				'schema_lang' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Language', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_lang',
					'content'  => $form->get_select( 'schema_lang', SucomUtil::get_available_locales(), $css_class = 'locale', $css_id = '',
						$is_assoc = false, $schema_lang_disabled ),
				),
				'schema_family_friendly' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Family Friendly', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_family_friendly',
					'content'  => $form->get_select_none( 'schema_family_friendly',
						$this->p->cf[ 'form' ][ 'yes_no' ], $css_class = 'yes-no', $css_id = '', $is_assoc = true ),
				),
				'schema_copyright_year' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Copyright Year', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_copyright_year',
					'content'  => $form->get_input( 'schema_copyright_year', $css_class = 'year', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_license_url' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'License URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_license_url',
					'content'  => $form->get_input( 'schema_license_url', $css_class = 'wide' ),
				),
				'schema_pub_org_id' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Publisher Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_pub_org_id',
					'content'  => $form->get_select( 'schema_pub_org_id', $names[ 'org' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_pub_person_id' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Publisher Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_pub_person_id',
					'content'  => $form->get_select( 'schema_pub_person_id', $names[ 'person' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = 'person_names' ),
				),
				'schema_prov_org_id' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Prov. Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_prov_org_id',
					'content'  => $form->get_select( 'schema_prov_org_id', $names[ 'org' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_prov_person_id' => array(
					'tr_class' => $type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Prov. Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_prov_person_id',
					'content'  => $form->get_select( 'schema_prov_person_id', $names[ 'person' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = 'person_names' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_article_rows( $table_rows, $form, $head_info, $mod, $type_row_class ) {

			$article_sections = $this->p->util->get_article_sections();
			$def_reading_mins = $this->p->page->get_reading_mins( $mod );

			$form_rows = array(
				'subsection_schema_article' => array(
					'tr_class' => $type_row_class[ 'article' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Article Information', 'metabox title', 'wpsso' )
				),
				'schema_article_section' => array(
					'tr_class' => $type_row_class[ 'article' ],
					'th_class' => 'medium',
					'label'    => _x( 'Article Section', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_article_section',
					'content'  => $form->get_select( 'schema_article_section', $article_sections, $css_class = 'article_section', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false,
							$event_names = array( 'on_focus_load_json' ),
								$event_args = array(
									'json_var'  => 'article_sections',
									'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
									'is_transl' => true,					// No label translation required.
									'is_sorted' => true,					// No label sorting required.
								)
						),
				),
				'schema_reading_mins' => array(
					'tr_class' => $type_row_class[ 'article' ],
					'th_class' => 'medium',
					'label'    => _x( 'Est. Reading Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_reading_mins',
					'content'  => $form->get_input( 'schema_reading_mins', $css_class = 'xshort', $css_id = '', 0, $def_reading_mins ) . ' ' .
						__( 'minute(s)', 'wpsso' ),
				),

			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
