<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2023 Jean-Sebastien Morisset (https://wpsso.com/)
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

			/*
			 * See WpssoAbstractWpMeta->get_document_meta_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'metabox_sso_edit_schema_rows'               => 4,
				'metabox_sso_edit_schema_creative_work_rows' => 6,	// Schema CreativeWork.
				'metabox_sso_edit_schema_article_rows'       => 6,	// Schema CreativeWork > Article.
				'metabox_sso_edit_schema_webpage_rows'       => 6,	// Schema CreativeWork > WebPage.
				'metabox_sso_edit_schema_howto_rows'         => 6,	// Schema CreativeWork > HowTo.
				'metabox_sso_edit_schema_recipe_rows'        => 6,	// Schema CreativeWork > HowTo > Recipe.
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_metabox_sso_edit_schema_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->util->is_schema_disabled() ) {

				return $table_rows;
			}

			$def_schema_title     = $this->p->page->get_title( $mod, $md_key = 'seo_title', $max_len = 'schema_title' );
			$def_schema_title_alt = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_title_alt' );
			$def_schema_desc      = $this->p->page->get_description( $mod, $md_key = 'seo_desc', $max_len = 'schema_desc' );
			$addl_type_url_max    = SucomUtil::get_const( 'WPSSO_SCHEMA_ADDL_TYPE_URL_MAX', 5 );
			$sameas_url_max       = SucomUtil::get_const( 'WPSSO_SCHEMA_SAMEAS_URL_MAX', 5 );
			$input_limits         = WpssoConfig::get_input_limits();	// Uses a local cache.
			$schema_tr_class      = WpssoSchema::get_schema_type_row_class( 'schema_type' );
			$select_names         = array(
				'google_prod_cats' => $this->p->util->get_google_product_categories(),
				'mrp'              => $this->p->util->get_form_cache( 'mrp_names', $add_none = true ),
				'org'              => $this->p->util->get_form_cache( 'org_names', $add_none = true ),
				'person'           => $this->p->util->get_form_cache( 'person_names', $add_none = true ),
				'place'            => $this->p->util->get_form_cache( 'place_names', $add_none = true ),
				'place_custom'     => $this->p->util->get_form_cache( 'place_names_custom', $add_none = true ),
				'schema_types'     => $this->p->util->get_form_cache( 'schema_types_select' ),
			);

			$form_rows = array(
				'subsection_schema' => array(
					'td_class' => 'subsection top',
					'header'   => 'h4',
					'label'    => _x( 'Schema Markup and Google Rich Results', 'metabox title', 'wpsso' )
				),
				'info_schema_item_list' => array(
					'tr_class'  => $schema_tr_class[ 'item.list' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-item-list' ) . '</td>',
				),
				'info_schema_question' => array(
					'tr_class'  => $schema_tr_class[ 'question' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-question' ) . '</td>',
				),
				'info_schema_webpage_faq' => array(
					'tr_class'  => $schema_tr_class[ 'webpage.faq' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-webpage-faq' ) . '</td>',
				),
				'info_schema_webpae_qa' => array(
					'tr_class'  => $schema_tr_class[ 'webpage.qa' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-webpage-qa' ) . '</td>',
				),
				'schema_title' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_title',
					'content'  => $form->get_input_dep( 'schema_title', $css_class = 'wide', $css_id = '',
						$input_limits[ 'schema_title' ], $def_schema_title, $is_disabled = false, $dep_id = 'seo_title' ),
				),
				'schema_title_alt' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Alternate Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_title_alt',
					'content'  => $form->get_input_dep( 'schema_title_alt', $css_class = 'wide', $css_id = '',
						$input_limits[ 'schema_title_alt' ], $def_schema_title_alt, $is_disabled = false, $dep_id = 'schema_title' ),
				),
				'schema_desc' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_desc',
					'content'  => $form->get_textarea_dep( 'schema_desc', $css_class = '', $css_id = '',
						$input_limits[ 'schema_desc' ], $def_schema_desc, $is_disabled = false, $dep_id = 'seo_desc' ),
				),
				'schema_addl_type_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Microdata Type URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_addl_type_url',
					'content'  => $form->get_input_multi( 'schema_addl_type_url', $css_class = 'wide', $css_id = '',
						$addl_type_url_max, $show_first = 1 ),
				),
				'schema_sameas_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Same-As URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_sameas_url',
					'content'  => $form->get_input_multi( 'schema_sameas_url', $css_class = 'wide', $css_id = '',
						$sameas_url_max, $show_first = 1 ),
				),
			);

			$table_rows = $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );

			/*
			 * Schema CreativeWork.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_creative_work_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > Article.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_article_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > WebPage.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_webpage_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > Book.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_book_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > HowTo.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_howto_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > HowTo > Recipe.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_recipe_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > Movie.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_movie_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > Review.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_review_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > Software Application.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_software_app_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema CreativeWork > WebPage > QAPage.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_qa_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema Event.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_event_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema Intangible > JobPosting.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_job_posting_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema Organization.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_organization_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema Person.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_person_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema Place.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_place_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			/*
			 * Schema Product.
			 */
			$table_rows = apply_filters( 'wpsso_metabox_sso_edit_schema_product_rows', $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names );

			return $table_rows;
		}

		public function filter_metabox_sso_edit_schema_creative_work_rows( $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names ) {

			$def_schema_headline     = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_headline' );
			$def_schema_text         = $this->p->page->get_text( $mod, $md_key = '', $max_len = 'schema_text' );
			$def_schema_keywords_csv = $this->p->page->get_keywords_csv( $mod, $md_key = '' );
			$schema_lang_disabled    = $this->p->avail[ 'lang' ][ 'any' ] ? true : false;
			$ispartof_url_max        = SucomUtil::get_const( 'WPSSO_SCHEMA_ISPARTOF_URL_MAX', 20 );
			$citations_max           = SucomUtil::get_const( 'WPSSO_SCHEMA_CITATIONS_MAX', 5 );
			$input_limits            = WpssoConfig::get_input_limits();	// Uses a local cache.

			$form_rows = array(
				'subsection_schema_creative_work' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Creative Work Information', 'metabox title', 'wpsso' )
				),
				'schema_headline' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Headline', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_headline',
					'content'  => $form->get_input_dep( 'schema_headline', $css_class = 'wide', $css_id = '',
						$input_limits[ 'schema_headline' ], $def_schema_headline, $is_disabled = false, $dep_id = 'schema_title' ),
				),
				'schema_text' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Full Text', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_text',
					'content'  => $form->get_textarea( 'schema_text', $css_class = 'full_text', $css_id = '', $max_len = 0, $def_schema_text ),
				),
				'schema_keywords_csv' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Keywords', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_keywords_csv',
					'content'  => $form->get_input( 'schema_keywords_csv', $css_class = 'wide', $css_id = '', $max_len = 0, $def_schema_keywords_csv ),
				),
				'schema_lang' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Language', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_lang',
					'content'  => $form->get_select( 'schema_lang', SucomUtil::get_available_locales(), $css_class = 'locale', $css_id = '',
						$is_assoc = false, $schema_lang_disabled ),
				),
				'schema_family_friendly' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Family Friendly', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_family_friendly',
					'content'  => $form->get_select_none( 'schema_family_friendly',
						$this->p->cf[ 'form' ][ 'yes_no' ], $css_class = 'yes-no', $css_id = '', $is_assoc = true ),
				),
				'schema_copyright_year' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Copyright Year', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_copyright_year',
					'content'  => $form->get_input( 'schema_copyright_year', $css_class = 'year', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_license_url' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'License URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_license_url',
					'content'  => $form->get_input( 'schema_license_url', $css_class = 'wide' ),
				),
				'schema_pub_org_id' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Publisher Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_pub_org_id',
					'content'  => $form->get_select( 'schema_pub_org_id', $select_names[ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_pub_person_id' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Publisher Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_pub_person_id',
					'content'  => $form->get_select( 'schema_pub_person_id', $select_names[ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_prov_org_id' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Provider Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_prov_org_id',
					'content'  => $form->get_select( 'schema_prov_org_id', $select_names[ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_prov_person_id' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Provider Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_prov_person_id',
					'content'  => $form->get_select( 'schema_prov_person_id', $select_names[ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_fund_org_id' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Funder Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_fund_org_id',
					'content'  => $form->get_select( 'schema_fund_org_id', $select_names[ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_fund_person_id' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Funder Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_fund_person_id',
					'content'  => $form->get_select( 'schema_fund_person_id', $select_names[ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_ispartof_url' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Is Part of URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_ispartof_url',
					'content'  => $form->get_input_multi( 'schema_ispartof_url', $css_class = 'wide', $css_id = '',
						$ispartof_url_max, $show_first = 1 ),
				),

				/*
				 * See https://schema.org/citation.
				 *
				 * There is very little information available from Google about the expected JSON markup structure
				 * for citations - the only information available is from the the Google's Dataset type
				 * documentation.
				 *
				 * See https://developers.google.com/search/docs/appearance/structured-data/dataset.
				 */
				'schema_citation' => array(
					'tr_class' => $schema_tr_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Reference Citations', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_citation',
					'content'  => $form->get_textarea_multi( 'schema_citation', $css_class = 'wide', $css_id = '',
						$max_len = 0, $citations_max, $show_first = 1 ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_article_rows( $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names ) {

			$article_sections = $this->p->util->get_article_sections();
			$def_reading_mins = $this->p->page->get_reading_mins( $mod );

			$form_rows = array(
				'subsection_schema_article' => array(
					'tr_class' => $schema_tr_class[ 'article' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Article Information', 'metabox title', 'wpsso' )
				),
				'schema_article_section' => array(
					'tr_class' => $schema_tr_class[ 'article' ],
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
					'tr_class' => $schema_tr_class[ 'article' ],
					'th_class' => 'medium',
					'label'    => _x( 'Est. Reading Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_reading_mins',
					'content'  => $form->get_input( 'schema_reading_mins', $css_class = 'xshort', $css_id = '', 0, $def_reading_mins ) . ' ' .
						__( 'minute(s)', 'wpsso' ),
				),

			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Since WPSSO Core v13.10.0.
		 */
		public function filter_metabox_sso_edit_schema_webpage_rows( $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names ) {

			$reviewed_by_max = SucomUtil::get_const( 'WPSSO_SCHEMA_WEBPAGE_REVIEWED_BY_MAX', 5 );

			$form_rows = array(
				'subsection_schema_webpage' => array(
					'tr_class' => $schema_tr_class[ 'webpage' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'WebPage Information', 'metabox title', 'wpsso' )
				),
				'schema_webpage_reviewed_by_org_id' => array(
					'tr_class' => $schema_tr_class[ 'webpage' ],
					'th_class' => 'medium',
					'label'    => _x( 'Reviewed By Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_webpage_reviewed_by_org_id',
					'content'  => $form->get_select_multi( 'schema_webpage_reviewed_by_org_id', $select_names[ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $reviewed_by_max, $show_first = 1,
							$is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_webpage_reviewed_by_person_id' => array(
					'tr_class' => $schema_tr_class[ 'webpage' ],
					'th_class' => 'medium',
					'label'    => _x( 'Reviewed By Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_webpage_reviewed_by_person_id',
					'content'  => $form->get_select_multi( 'schema_webpage_reviewed_by_person_id', $select_names[ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $reviewed_by_max, $show_first = 1,
							$is_disabled = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_webpage_reviewed_last' => array(
					'tr_class' => $schema_tr_class[ 'webpage' ],
					'th_class' => 'medium',
					'label'    => _x( 'Reviewed Last', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_webpage_reviewed_last',
					'content'  => $form->get_date_time_tz( 'schema_webpage_reviewed_last' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}


		public function filter_metabox_sso_edit_schema_howto_rows( $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names ) {

			$howto_steps_max     = SucomUtil::get_const( 'WPSSO_SCHEMA_HOWTO_STEPS_MAX', 40 );
			$howto_supplies_max  = SucomUtil::get_const( 'WPSSO_SCHEMA_HOWTO_SUPPLIES_MAX', 30 );
			$howto_tools_max     = SucomUtil::get_const( 'WPSSO_SCHEMA_HOWTO_TOOLS_MAX', 20 );

			$form_rows = array(
				'subsection_schema_howto' => array(
					'tr_class' => $schema_tr_class[ 'howto' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'How-To Information', 'metabox title', 'wpsso' )
				),
				'schema_howto_yield' => array(
					'tr_class' => $schema_tr_class[ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'How-To Makes', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_yield',
					'content'  => $form->get_input( 'schema_howto_yield', $css_class = 'wide' ),
				),
				'schema_howto_prep_time' => array(
					'tr_class' => $schema_tr_class[ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'Preparation Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_prep_time',
					'content'  => $form->get_input_time_dhms( 'schema_howto_prep' ),
				),
				'schema_howto_total_time' => array(
					'tr_class' => $schema_tr_class[ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'Total Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_total_time',
					'content'  => $form->get_input_time_dhms( 'schema_howto_total' ),
				),
				'schema_howto_supplies' => array(
					'tr_class' => $schema_tr_class[ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'How-To Supplies', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_supplies',
					'content'  => $form->get_input_multi( 'schema_howto_supply', $css_class = 'wide', $css_id = '',
						$howto_supplies_max, $show_first = 5 ),
				),
				'schema_howto_tools' => array(
					'tr_class' => $schema_tr_class[ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'How-To Tools', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_tools',
					'content'  => $form->get_input_multi( 'schema_howto_tool', $css_class = 'wide', $css_id = '',
						$howto_tools_max, $show_first = 5 ),
				),
				'schema_howto_steps' => array(
					'tr_class' => $schema_tr_class[ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'How-To Steps', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_steps',
					'content'  => $form->get_mixed_multi( array(
						'schema_howto_step_section' => array(
							'input_type'    => 'radio',
							'input_class'   => 'howto_step_section',
							'input_content' => _x( 'How-To %1$s step or %2$s section of steps:', 'option label', 'wpsso' ),
							'input_values'  => array( 0, 1 ),
							'input_default' => 0,
						),
						'schema_howto_step' => array(
							'input_label' => _x( 'Name', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'wide howto_step_name',
						),
						'schema_howto_step_text' => array(
							'input_label' => _x( 'Description', 'option label', 'wpsso' ),
							'input_type'  => 'textarea',
							'input_class' => 'wide howto_step_text',
						),
						'schema_howto_step_anchor_id' => array(
							'input_label' => _x( 'Anchor ID', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'anchor howto_step_id',
						),
						'schema_howto_step_img' => array(
							'input_label' => _x( 'Image ID', 'option label', 'wpsso' ),
							'input_type'  => 'image',
							'input_class' => 'howto_step_img',
						),
					), $css_class = '', $css_id = 'schema_howto_steps', $howto_steps_max, $show_first = 2 ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_recipe_rows( $table_rows, $form, $head_info, $mod, $schema_tr_class, $select_names ) {

			$recipe_ingr_max = SucomUtil::get_const( 'WPSSO_SCHEMA_RECIPE_INGREDIENTS_MAX', 40 );
			$recipe_inst_max = SucomUtil::get_const( 'WPSSO_SCHEMA_RECIPE_INSTRUCTIONS_MAX', 40 );

			$form_rows = array(
				'subsection_schema_recipe' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Recipe Information', 'metabox title', 'wpsso' )
				),
				'schema_recipe_cuisine' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Cuisine', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_cuisine',
					'content'  => $form->get_input( 'schema_recipe_cuisine', $css_class = 'wide' ),
				),
				'schema_recipe_course' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Course', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_course',
					'content'  => $form->get_input( 'schema_recipe_course', $css_class = 'wide' ),
				),
				'schema_recipe_yield' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Makes', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_yield',
					'content'  => $form->get_input( 'schema_recipe_yield', $css_class = 'wide' ),
				),
				'schema_recipe_cook_method' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Cooking Method', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_cook_method',
					'content'  => $form->get_input( 'schema_recipe_cook_method', $css_class = 'wide' ),
				),
				'schema_recipe_prep_time' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Preparation Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_prep_time',
					'content'  => $form->get_input_time_dhms( 'schema_recipe_prep' ),
				),
				'schema_recipe_cook_time' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Cooking Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_cook_time',
					'content'  => $form->get_input_time_dhms( 'schema_recipe_cook' ),
				),
				'schema_recipe_total_time' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Total Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_total_time',
					'content'  => $form->get_input_time_dhms( 'schema_recipe_total' ),
				),
				'schema_recipe_ingredients' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Ingredients', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_ingredients',
					'content'  => $form->get_input_multi( 'schema_recipe_ingredient', $css_class = 'wide', $css_id = '',
						$recipe_ingr_max, $show_first = 5 ),
				),
				'schema_recipe_instructions' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Instructions', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_instructions',
					'content'  => $form->get_mixed_multi( array(
						'schema_recipe_instruction_section' => array(
							'input_type'    => 'radio',
							'input_class'   => 'recipe_instruction_section',
							'input_content' => _x( 'Recipe %1$s instruction or %2$s section of instructions:', 'option label', 'wpsso' ),
							'input_values'  => array( 0, 1 ),
							'input_default' => 0,
						),
						'schema_recipe_instruction' => array(
							'input_label' => _x( 'Name', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'wide recipe_instruction_name',
						),
						'schema_recipe_instruction_text' => array(
							'input_label' => _x( 'Description', 'option label', 'wpsso' ),
							'input_type'  => 'textarea',
							'input_class' => 'wide recipe_instruction_text',
						),
						'schema_recipe_instruction_id' => array(
							'input_label' => _x( 'Anchor ID', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'anchor recipe_instruction_id',
						),
						'schema_recipe_instruction_img' => array(
							'input_label' => _x( 'Image ID', 'option label', 'wpsso' ),
							'input_type'  => 'image',
							'input_class' => 'recipe_instruction_img',
						),
					), $css_class = '', $css_id = 'schema_recipe_instructions', $recipe_inst_max, $show_first = 2 ),
				),

				/*
				 * Recipe Nutrition Information.
				 */
				'subsection_schema_recipe_nutrition' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Recipe Nutrition Information', 'metabox title', 'wpsso' )
				),
				'schema_recipe_nutri_serv' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Serving Size', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_serv',
					'content'  => $form->get_input( 'schema_recipe_nutri_serv', $css_class = 'wide' ),
				),
				'schema_recipe_nutri_cal' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Calories', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_cal',
					'content'  => $form->get_input( 'schema_recipe_nutri_cal', 'medium' ) . ' ' .
					_x( 'calories', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_prot' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Protein', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_prot',
					'content'  => $form->get_input( 'schema_recipe_nutri_prot', 'medium' ) . ' ' .
						_x( 'grams of protein', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_fib' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Fiber', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_fib',
					'content'  => $form->get_input( 'schema_recipe_nutri_fib', 'medium' ) . ' ' .
						_x( 'grams of fiber', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_carb' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Carbohydrates', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_carb',
					'content'  => $form->get_input( 'schema_recipe_nutri_carb', 'medium' ) . ' ' .
						_x( 'grams of carbohydrates', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_sugar' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Sugar', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sugar',
					'content'  => $form->get_input( 'schema_recipe_nutri_sugar', 'medium' ) . ' ' .
						_x( 'grams of sugar', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_sod' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Sodium', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sod',
					'content'  => $form->get_input( 'schema_recipe_nutri_sod', 'medium' ) . ' ' .
						_x( 'milligrams of sodium', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_fat' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Fat', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_fat',
					'content'  => $form->get_input( 'schema_recipe_nutri_fat', 'medium' ) . ' ' .
						_x( 'grams of fat', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_sat_fat' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Saturated Fat', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sat_fat',
					'content'  => $form->get_input( 'schema_recipe_nutri_sat_fat', 'medium' ) . ' ' .
						_x( 'grams of saturated fat', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_unsat_fat' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Unsaturated Fat', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_unsat_fat',
					'content'  => $form->get_input( 'schema_recipe_nutri_unsat_fat', 'medium' ) . ' ' .
						_x( 'grams of unsaturated fat', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_trans_fat' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Trans Fat', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_trans_fat',
					'content'  => $form->get_input( 'schema_recipe_nutri_trans_fat', 'medium' ) . ' ' .
						_x( 'grams of trans fat', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_chol' => array(
					'tr_class' => $schema_tr_class[ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Cholesterol', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_chol',
					'content'  => $form->get_input( 'schema_recipe_nutri_chol', 'medium' ) . ' ' .
						_x( 'milligrams of cholesterol', 'option comment', 'wpsso' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
