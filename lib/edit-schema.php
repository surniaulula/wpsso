<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2025 Jean-Sebastien Morisset (https://wpsso.com/)
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
			 * See WpssoAbstractWpMeta->get_document_sso_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'mb_sso_edit_schema_rows'                  => 4,
				'mb_sso_edit_schema_creative_work_rows'    => 5,	// Schema CreativeWork.
				'mb_sso_edit_schema_article_rows'          => 5,	// Schema CreativeWork > Article.
				'mb_sso_edit_schema_book_rows'             => 5,	// Schema CreativeWork > Book.
				'mb_sso_edit_schema_howto_rows'            => 5,	// Schema CreativeWork > HowTo.
				'mb_sso_edit_schema_recipe_rows'           => 5,	// Schema CreativeWork > HowTo > Recipe.
				'mb_sso_edit_schema_learningresource_rows' => 5,	// Schema CreativeWork > LearningResource.
				'mb_sso_edit_schema_movie_rows'            => 5,	// Schema CreativeWork > Movie.
				'mb_sso_edit_schema_review_rows'           => 5,	// Schema CreativeWork > Review.
				'mb_sso_edit_schema_software_app_rows'     => 5,	// Schema CreativeWork > Software Application.
				'mb_sso_edit_schema_webpage_rows'          => 5,	// Schema CreativeWork > WebPage.
				'mb_sso_edit_schema_profilepage_rows'      => 5,	// Schema CreativeWork > WebPage > ProfilePage.
				'mb_sso_edit_schema_qa_rows'               => 5,	// Schema CreativeWork > WebPage > QAPage.
				'mb_sso_edit_schema_event_rows'            => 5,	// Schema Event.
				'mb_sso_edit_schema_job_posting_rows'      => 5,	// Schema Intangible > JobPosting.
				'mb_sso_edit_schema_organization_rows'     => 5,	// Schema Organization.
				'mb_sso_edit_schema_person_rows'           => 5,	// Schema Person.
				'mb_sso_edit_schema_place_rows'            => 5,	// Schema Place.
				'mb_sso_edit_schema_product_rows'          => 5,	// Schema Product.
				'mb_sso_edit_schema_service_rows'          => 5,	// Schema Service.
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_mb_sso_edit_schema_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->util->is_schema_disabled() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: schema markup is disabled' );
				}

				return $table_rows;
			}

			$def_schema_title     = $this->p->page->get_title( $mod, $md_key = 'seo_title', $max_len = 'schema_title' );
			$def_schema_title_alt = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_title_alt' );
			$def_schema_desc      = $this->p->page->get_description( $mod, $md_key = 'seo_desc', $max_len = 'schema_desc' );
			$schema_lang_disabled = $this->p->avail[ 'lang' ][ 'any' ] ? true : false;
			$addl_type_url_max    = SucomUtil::get_const( 'WPSSO_SCHEMA_ADDL_TYPE_URL_MAX', 5 );
			$sameas_url_max       = SucomUtil::get_const( 'WPSSO_SCHEMA_SAMEAS_URL_MAX', 5 );
			$input_limits         = WpssoConfig::get_input_limits();	// Uses a local cache.

			$args = array(
				'schema_tr_class' => WpssoSchema::get_schema_type_row_class( 'schema_type' ),
				'select_names'    => array(
					'contact'          => $this->p->util->get_form_cache( 'contact_names', $add_none = true ),
					'google_prod_cats' => $this->p->util->get_google_product_categories(),
					'mrp'              => $this->p->util->get_form_cache( 'mrp_names', $add_none = true ),
					'org'              => $this->p->util->get_form_cache( 'org_names', $add_none = true ),
					'person'           => $this->p->util->get_form_cache( 'person_names', $add_none = true ),
					'place'            => $this->p->util->get_form_cache( 'place_names', $add_none = true ),
					'place_custom'     => $this->p->util->get_form_cache( 'place_names_custom', $add_none = true ),
					'schema_types'     => $this->p->util->get_form_cache( 'schema_types_select' ),
				),
			);

			$form_rows = array(
				'subsection_schema' => array(
					'td_class' => 'subsection top',
					'header'   => 'h4',
					'label'    => _x( 'Schema Markup and Google Rich Results', 'metabox title', 'wpsso' )
				),
				'info_schema_item_list' => array(
					'tr_class'  => $args[ 'schema_tr_class' ][ 'item.list' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-item-list' ) . '</td>',
				),
				'info_schema_question' => array(
					'tr_class'  => $args[ 'schema_tr_class' ][ 'question' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-question' ) . '</td>',
				),
				'info_schema_webpage_faq' => array(
					'tr_class'  => $args[ 'schema_tr_class' ][ 'webpage.faq' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-webpage-faq' ) . '</td>',
				),
				'info_schema_webpae_qa' => array(
					'tr_class'  => $args[ 'schema_tr_class' ][ 'webpage.qa' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->get( 'info-meta-schema-webpage-qa' ) . '</td>',
				),
				'schema_lang' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Language', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_lang',
					'content'  => $form->get_select( 'schema_lang', SucomUtilWP::get_available_locales(), $css_class = 'locale', $css_id = '',
						$is_assoc = false, $schema_lang_disabled ),
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

			foreach( array(
				'wpsso_mb_sso_edit_schema_creative_work_rows',
				'wpsso_mb_sso_edit_schema_article_rows',
				'wpsso_mb_sso_edit_schema_book_rows',
				'wpsso_mb_sso_edit_schema_howto_rows',			// Schema CreativeWork > HowTo.
				'wpsso_mb_sso_edit_schema_recipe_rows',			// Schema CreativeWork > HowTo > Recipe.
				'wpsso_mb_sso_edit_schema_learningresource_rows',	// Schema CreativeWork > LearningResource.
				'wpsso_mb_sso_edit_schema_movie_rows',
				'wpsso_mb_sso_edit_schema_review_rows',
				'wpsso_mb_sso_edit_schema_software_app_rows',
				'wpsso_mb_sso_edit_schema_webpage_rows',
				'wpsso_mb_sso_edit_schema_profilepage_rows',		// Schema CreativeWork > WebPage > ProfilePage.
				'wpsso_mb_sso_edit_schema_qa_rows',
				'wpsso_mb_sso_edit_schema_event_rows',
				'wpsso_mb_sso_edit_schema_job_posting_rows',
				'wpsso_mb_sso_edit_schema_organization_rows',
				'wpsso_mb_sso_edit_schema_person_rows',
				'wpsso_mb_sso_edit_schema_place_rows',
				'wpsso_mb_sso_edit_schema_product_rows',
				'wpsso_mb_sso_edit_schema_service_rows',
			) as $filter_name ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
				}

				$table_rows = apply_filters( $filter_name, $table_rows, $form, $head_info, $mod, $args );
			}

			return $table_rows;
		}

		/*
		 * Schema CreativeWork.
		 */
		public function filter_mb_sso_edit_schema_creative_work_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$def_schema_headline     = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_headline' );
			$def_schema_text         = $this->p->page->get_text( $mod, $md_key = '', $max_len = 'schema_text' );
			$def_schema_keywords_csv = $this->p->page->get_keywords_csv( $mod, $md_key = '' );
			$awards_max              = SucomUtil::get_const( 'WPSSO_SCHEMA_AWARDS_MAX' );
			$citations_max           = SucomUtil::get_const( 'WPSSO_SCHEMA_CITATIONS_MAX' );
			$ispartof_url_max        = SucomUtil::get_const( 'WPSSO_SCHEMA_ISPARTOF_URL_MAX' );
			$input_limits            = WpssoConfig::get_input_limits();	// Uses a local cache.

			$form_rows = array(
				'subsection_schema_creative_work' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Creative Work Information', 'metabox title', 'wpsso' )
				),
				'schema_headline' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Headline', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_headline',
					'content'  => $form->get_input_dep( 'schema_headline', $css_class = 'wide', $css_id = '',
						$input_limits[ 'schema_headline' ], $def_schema_headline, $is_disabled = false, $dep_id = 'schema_title' ),
				),
				'schema_text' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Full Text', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_text',
					'content'  => $form->get_textarea( 'schema_text', $css_class = 'full_text', $css_id = '', $max_len = 0, $def_schema_text ),
				),
				'schema_keywords_csv' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Keywords', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_keywords_csv',
					'content'  => $form->get_input( 'schema_keywords_csv', $css_class = 'wide', $css_id = '', $max_len = 0, $def_schema_keywords_csv ),
				),
				'schema_family_friendly' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Family Friendly', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_family_friendly',
					'content'  => $form->get_select_none( 'schema_family_friendly',
						$this->p->cf[ 'form' ][ 'yes_no' ], $css_class = 'yes-no', $css_id = '', $is_assoc = true ),
				),
				'schema_copyright_year' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Copyright Year', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_copyright_year',
					'content'  => $form->get_input( 'schema_copyright_year', $css_class = 'year', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_license_url' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'License URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_license_url',
					'content'  => $form->get_input( 'schema_license_url', $css_class = 'wide' ),
				),
				'schema_pub_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Publisher Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_pub_org_id',
					'content'  => $form->get_select( 'schema_pub_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_pub_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Publisher Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_pub_person_id',
					'content'  => $form->get_select( 'schema_pub_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_prov_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Provider Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_prov_org_id',
					'content'  => $form->get_select( 'schema_prov_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_prov_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Provider Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_prov_person_id',
					'content'  => $form->get_select( 'schema_prov_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_fund_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Funder Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_fund_org_id',
					'content'  => $form->get_select( 'schema_fund_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_fund_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Funder Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_fund_person_id',
					'content'  => $form->get_select( 'schema_fund_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_ispartof_url' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Is Part of URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_ispartof_url',
					'content'  => $form->get_input_multi( 'schema_ispartof_url', $css_class = 'wide', $css_id = '',
						$ispartof_url_max, $show_first = 1 ),
				),
				'schema_award' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Creative Work Awards', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_award',
					'content'  => $form->get_input_multi( 'schema_award', $css_class = 'wide', $css_id = '',
						$awards_max, $show_first = 1 ),
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
					'tr_class' => $args[ 'schema_tr_class' ][ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Reference Citations', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_citation',
					'content'  => $form->get_textarea_multi( 'schema_citation', $css_class = 'wide', $css_id = '',
						$max_len = 0, $citations_max, $show_first = 1 ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > Article.
		 */
		public function filter_mb_sso_edit_schema_article_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$article_sections = $this->p->util->get_article_sections();
			$def_reading_mins = $this->p->page->get_reading_mins( $mod );

			$form_rows = array(
				'subsection_schema_article' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'article' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Article Information', 'metabox title', 'wpsso' )
				),
				'schema_article_section' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'article' ],
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
					'tr_class' => $args[ 'schema_tr_class' ][ 'article' ],
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
		 * Schema CreativeWork > Book.
		 */
		public function filter_mb_sso_edit_schema_book_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$form_rows = array(
				'subsection_schema_book' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Book Information', 'metabox title', 'wpsso' )
				),
				'schema_book_author_type' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Book Author Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_author_type',
					'content'  => $form->get_select( 'schema_book_author_type', $this->p->cf[ 'form' ][ 'author_types' ] ),
				),
				'schema_book_author_name' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Book Author Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_author_name',
					'content'  => $form->get_input( 'schema_book_author_name', $css_class = 'wide' ),
				),
				'schema_book_author_url' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Book Author URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_author_url',
					'content'  => $form->get_input( 'schema_book_author_url', $css_class = 'wide' ),
				),
				'schema_book_pub' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Book Published Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_pub',
					'content'  => $form->get_date_time_timezone( 'schema_book_pub' ),
				),
				'schema_book_created' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Book Created Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_created',
					'content'  => $form->get_date_time_timezone( 'schema_book_created' ),
				),
				'schema_book_edition' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Book Edition', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_edition',
					'content'  => $form->get_input( 'schema_book_edition' ),
				),
				'schema_book_format' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Book Format', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_format',
					'content'  => $form->get_select( 'schema_book_format', $this->p->cf[ 'form' ][ 'book_format' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_book_pages' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Number of Pages', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_pages',
					'content'  => $form->get_input( 'schema_book_pages', $css_class = 'short' ),
				),
				'schema_book_isbn' => array(		// Open Graph meta tag book:isbn.
					'tr_class' => $args[ 'schema_tr_class' ][ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Book ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_isbn',
					'content'  => $form->get_input( 'schema_book_isbn', $css_class = '', $css_id = '', array( 'min' => 10, 'max' => 13 ) ),
				),

				/*
				 * Schema CreativeWork > Book > Audiobook.
				 */
				'subsection_schema_book_audio' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book.audio' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Audiobook Information', 'metabox title', 'wpsso' )
				),
				'schema_book_audio_duration_time' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'book.audio' ],
					'th_class' => 'medium',
					'label'    => _x( 'Audiobook Duration', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_audio_duration_time',
					'content'  => $form->get_input_time_dhms( 'schema_book_audio_duration' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > HowTo.
		 */
		public function filter_mb_sso_edit_schema_howto_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$howto_steps_max     = SucomUtil::get_const( 'WPSSO_SCHEMA_HOWTO_STEPS_MAX', 40 );
			$howto_supplies_max  = SucomUtil::get_const( 'WPSSO_SCHEMA_HOWTO_SUPPLIES_MAX', 30 );
			$howto_tools_max     = SucomUtil::get_const( 'WPSSO_SCHEMA_HOWTO_TOOLS_MAX', 20 );

			$form_rows = array(
				'subsection_schema_howto' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'howto' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'How-To Information', 'metabox title', 'wpsso' )
				),
				'schema_howto_yield' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'How-To Makes', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_yield',
					'content'  => $form->get_input( 'schema_howto_yield', $css_class = 'wide' ),
				),
				'schema_howto_prep_time' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'Preparation Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_prep_time',
					'content'  => $form->get_input_time_dhms( 'schema_howto_prep' ),
				),
				'schema_howto_total_time' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'Total Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_total_time',
					'content'  => $form->get_input_time_dhms( 'schema_howto_total' ),
				),
				'schema_howto_supplies' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'How-To Supplies', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_supplies',
					'content'  => $form->get_input_multi( 'schema_howto_supply', $css_class = 'wide', $css_id = '',
						$howto_supplies_max, $show_first = 5 ),
				),
				'schema_howto_tools' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'howto' ],
					'th_class' => 'medium',
					'label'    => _x( 'How-To Tools', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_howto_tools',
					'content'  => $form->get_input_multi( 'schema_howto_tool', $css_class = 'wide', $css_id = '',
						$howto_tools_max, $show_first = 5 ),
				),
				'schema_howto_steps' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'howto' ],
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
							'input_class' => 'css_id howto_step_anchor_id',
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

		/*
		 * Schema CreativeWork > HowTo > Recipe.
		 */
		public function filter_mb_sso_edit_schema_recipe_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$recipe_ingr_max = SucomUtil::get_const( 'WPSSO_SCHEMA_RECIPE_INGREDIENTS_MAX', 40 );
			$recipe_inst_max = SucomUtil::get_const( 'WPSSO_SCHEMA_RECIPE_INSTRUCTIONS_MAX', 40 );

			$form_rows = array(
				'subsection_schema_recipe' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Recipe Information', 'metabox title', 'wpsso' )
				),
				'schema_recipe_cuisine' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Cuisine', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_cuisine',
					'content'  => $form->get_input( 'schema_recipe_cuisine', $css_class = 'wide' ),
				),
				'schema_recipe_course' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Course', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_course',
					'content'  => $form->get_input( 'schema_recipe_course', $css_class = 'wide' ),
				),
				'schema_recipe_yield' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Makes', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_yield',
					'content'  => $form->get_input( 'schema_recipe_yield', $css_class = 'wide' ),
				),
				'schema_recipe_cook_method' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Cooking Method', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_cook_method',
					'content'  => $form->get_input( 'schema_recipe_cook_method', $css_class = 'wide' ),
				),
				'schema_recipe_prep_time' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Preparation Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_prep_time',
					'content'  => $form->get_input_time_dhms( 'schema_recipe_prep' ),
				),
				'schema_recipe_cook_time' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Cooking Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_cook_time',
					'content'  => $form->get_input_time_dhms( 'schema_recipe_cook' ),
				),
				'schema_recipe_total_time' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Total Time', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_total_time',
					'content'  => $form->get_input_time_dhms( 'schema_recipe_total' ),
				),
				'schema_recipe_ingredients' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Recipe Ingredients', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_ingredients',
					'content'  => $form->get_input_multi( 'schema_recipe_ingredient', $css_class = 'wide', $css_id = '',
						$recipe_ingr_max, $show_first = 5 ),
				),
				'schema_recipe_instructions' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
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
						'schema_recipe_instruction_anchor_id' => array(
							'input_label' => _x( 'Anchor ID', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'css_id recipe_instruction_anchor_id',
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
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Recipe Nutrition Information', 'metabox title', 'wpsso' )
				),
				'schema_recipe_nutri_serv' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Serving Size', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_serv',
					'content'  => $form->get_input( 'schema_recipe_nutri_serv', $css_class = 'wide' ),
				),
				'schema_recipe_nutri_cal' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Calories', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_cal',
					'content'  => $form->get_input( 'schema_recipe_nutri_cal', 'medium' ) . ' ' .
					_x( 'calories', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_prot' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Protein', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_prot',
					'content'  => $form->get_input( 'schema_recipe_nutri_prot', 'medium' ) . ' ' .
						_x( 'grams of protein', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_fib' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Fiber', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_fib',
					'content'  => $form->get_input( 'schema_recipe_nutri_fib', 'medium' ) . ' ' .
						_x( 'grams of fiber', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_carb' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Carbohydrates', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_carb',
					'content'  => $form->get_input( 'schema_recipe_nutri_carb', 'medium' ) . ' ' .
						_x( 'grams of carbohydrates', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_sugar' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Sugar', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sugar',
					'content'  => $form->get_input( 'schema_recipe_nutri_sugar', 'medium' ) . ' ' .
						_x( 'grams of sugar', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_sod' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Sodium', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sod',
					'content'  => $form->get_input( 'schema_recipe_nutri_sod', 'medium' ) . ' ' .
						_x( 'milligrams of sodium', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_fat' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Fat', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_fat',
					'content'  => $form->get_input( 'schema_recipe_nutri_fat', 'medium' ) . ' ' .
						_x( 'grams of fat', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_sat_fat' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Saturated Fat', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sat_fat',
					'content'  => $form->get_input( 'schema_recipe_nutri_sat_fat', 'medium' ) . ' ' .
						_x( 'grams of saturated fat', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_unsat_fat' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Unsaturated Fat', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_unsat_fat',
					'content'  => $form->get_input( 'schema_recipe_nutri_unsat_fat', 'medium' ) . ' ' .
						_x( 'grams of unsaturated fat', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_trans_fat' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Trans Fat', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_trans_fat',
					'content'  => $form->get_input( 'schema_recipe_nutri_trans_fat', 'medium' ) . ' ' .
						_x( 'grams of trans fat', 'option comment', 'wpsso' ),
				),
				'schema_recipe_nutri_chol' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'recipe' ],
					'th_class' => 'medium',
					'label'    => _x( 'Cholesterol', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_recipe_nutri_chol',
					'content'  => $form->get_input( 'schema_recipe_nutri_chol', 'medium' ) . ' ' .
						_x( 'milligrams of cholesterol', 'option comment', 'wpsso' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > LearningResource.
		 *
		 * See https://developers.google.com/search/docs/appearance/structured-data/learning-video#learning-video-[videoobject,-learningresource].
		 */
		public function filter_mb_sso_edit_schema_learningresource_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$form_rows = array(
				'subsection_schema_learnres' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'learning.resource' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Learning Resource Information', 'metabox title', 'wpsso' )
				),
				'schema_learnres_educational_level' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'learning.resource' ],
					'th_class' => 'medium',
					'label'    => _x( 'Educational Level', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_learnres_educational_level',
					'content'  => $form->get_select_education_level( 'schema_learnres_educational_level', 'wide' ),
				),
				'schema_learnres_resource_type' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'learning.resource' ],
					'th_class' => 'medium',
					'label'    => _x( 'Resource Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_learnres_resource_type',
					'content'  => $form->get_input( 'schema_learnres_resource_type', 'wide' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > Movie.
		 */
		public function filter_mb_sso_edit_schema_movie_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$movie_actors_max    = SucomUtil::get_const( 'WPSSO_SCHEMA_MOVIE_ACTORS_MAX', 15 );
			$movie_directors_max = SucomUtil::get_const( 'WPSSO_SCHEMA_MOVIE_DIRECTORS_MAX', 5 );

			$form_rows = array(
				'subsection_schema_movie' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'movie' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Movie Information', 'metabox title', 'wpsso' )
				),
				'schema_movie_actor_person_names' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'movie' ],
					'th_class' => 'medium',
					'label'    => _x( 'Movie Cast Names', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_actor_person_names',
					'content'  => $form->get_input_multi( 'schema_movie_actor_person_name',	// Singular.
						$css_class = 'long_name', $css_id = '', $movie_actors_max, $show_first = 3 ),
				),
				'schema_movie_director_person_names' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'movie' ],
					'th_class' => 'medium',
					'label'    => _x( 'Movie Director Names', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_director_person_names',
					'content'  => $form->get_input_multi( 'schema_movie_director_person_name',	// Singular.
						$css_class = 'long_name', $css_id = '', $movie_directors_max, $show_first = 1 ),
				),
				'schema_movie_prodco_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'movie' ],
					'th_class' => 'medium',
					'label'    => _x( 'Production Company', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_prodco_org_id',
					'content'  => $form->get_select( 'schema_movie_prodco_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_movie_released' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'movie' ],
					'th_class' => 'medium',
					'label'    => _x( 'Movie Release Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_released',
					'content'  => $form->get_date_time_timezone( 'schema_movie_released' ),
				),
				'schema_movie_duration_time' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'movie' ],
					'th_class' => 'medium',
					'label'    => _x( 'Movie Runtime', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_duration_time',
					'content'  => $form->get_input_time_dhms( 'schema_movie_duration' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > Review.
		 */
		public function filter_mb_sso_edit_schema_review_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$currencies          = SucomUtil::get_currencies_abbrev();
			$item_type_row_class = WpssoSchema::get_schema_type_row_class( 'schema_review_item_type' );
			$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );
			$movie_actors_max    = SucomUtil::get_const( 'WPSSO_SCHEMA_MOVIE_ACTORS_MAX', 15 );
			$movie_directors_max = SucomUtil::get_const( 'WPSSO_SCHEMA_MOVIE_DIRECTORS_MAX', 5 );
			$sameas_url_max      = SucomUtil::get_const( 'WPSSO_SCHEMA_SAMEAS_URL_MAX', 5 );

			$form_rows = array(
				'subsection_schema_review' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Review Information', 'metabox title', 'wpsso' )
				),
				'schema_review_rating' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'Review Rating', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_rating',
					'content'  => '' .
						$form->get_input( 'schema_review_rating', $css_class = 'rating', $css_id = '', $max_len = 0, $holder = true ) .
						' ' . _x( 'from', 'option comment', 'wpsso' ) . ' ' .
						$form->get_input( 'schema_review_rating_min', $css_class = 'rating', $css_id = '', $max_len = 0, $holder = true ) .
						' ' . _x( 'to', 'option comment', 'wpsso' ) . ' ' .
						$form->get_input( 'schema_review_rating_max', $css_class = 'rating', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_review_rating_alt_name' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'Rating Alt Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_rating_alt_name',
					'content'  => $form->get_input( 'schema_review_rating_alt_name' ),
				),

				/*
				 * Schema Review Subject Information.
				 *
				 * Note that although the Schema standard allows the subject of a review to be any Schema type,
				 * Google allows reviews for only a few specific Schema types (and their sub-types):
				 *
				 *	Book
				 *	Course
				 *	Event
				 *	How-to
				 *	Local business
				 *	Movie
				 *	Product
				 *	Recipe
				 *	Software App
				 *
				 * See https://developers.google.com/search/docs/data-types/review-snippet.
				 */
				'subsection_schema_review_item' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Review Subject (aka Item Reviewed) Information', 'metabox title', 'wpsso' )
				),
				'schema_review_item_name' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_name',
					'content'  => $form->get_input( 'schema_review_item_name', $css_class = 'wide' ),
				),
				'schema_review_item_desc' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_desc',
					'content'  => $form->get_textarea( 'schema_review_item_desc' ),
				),
				'schema_review_item_img_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_img_id',
					'content'  => $form->get_input_image_upload( 'schema_review_item_img' ),
				),
				'schema_review_item_img_url' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_img_url',
					'content'  => $form->get_input_image_url( 'schema_review_item_img' ),
				),
				'schema_review_item_url' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Webpage URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_url',
					'content'  => $form->get_input( 'schema_review_item_url', $css_class = 'wide' ),
				),
				'schema_review_item_sameas_url' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Same-As URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_sameas_url',
					'content'  => $form->get_input_multi( 'schema_review_item_sameas_url', $css_class = 'wide', $css_id = '',
						$sameas_url_max, $show_first = 1 ),
				),
				'schema_review_item_type' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_type',
					'content'  => $form->get_select( 'schema_review_item_type', $args[ 'select_names' ][ 'schema_types' ],
						$css_class = 'schema_type', $css_id = '', $is_assoc = true, $is_disabled = false, $selected = false,
							$event_names = array( 'on_focus_load_json', 'on_show_unhide_rows' ),
								$event_args = array(
									'json_var'  => 'schema_types',
									'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
									'is_transl' => true,					// No label translation required.
									'is_sorted' => true,					// No label sorting required.
								) ),
				),

				/*
				 * Schema Review Subject: CreativeWork.
				 */
				'schema_review_item_cw_author_type' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Author Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_type',
					'content'  => $form->get_select( 'schema_review_item_cw_author_type', $this->p->cf[ 'form' ][ 'author_types' ] ),
				),
				'schema_review_item_cw_author_name' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Author Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_name',
					'content'  => $form->get_input( 'schema_review_item_cw_author_name', $css_class = 'wide' ),
				),
				'schema_review_item_cw_author_url' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Author URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_url',
					'content'  => $form->get_input( 'schema_review_item_cw_author_url', $css_class = 'wide' ),
				),
				'schema_review_item_cw_pub' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Published Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_pub',
					'content'  => $form->get_date_time_timezone( 'schema_review_item_cw_pub' ),
				),
				'schema_review_item_cw_created' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative.work' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Created Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_created',
					'content'  => $form->get_date_time_timezone( 'schema_review_item_cw_created' ),
				),

				/*
				 * Schema Review Subject: CreativeWork > Book.
				 */
				'schema_review_item_cw_book_isbn' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'book' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Book ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_book_isbn',
					'content'  => $form->get_input( 'schema_review_item_cw_book_isbn',
						$css_class = '', $css_id = '', $len = array( 'min' => 10, 'max' => 13 ) ),
				),

				/*
				 * Schema Review Subject: CreativeWork > Movie.
				 */
				'schema_review_item_cw_movie_actor_person_names' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'label'    => _x( 'Movie Cast Names', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_movie_actor_person_names',
					'content'  => $form->get_input_multi( 'schema_review_item_cw_movie_actor_person_name',	// Singular.
						$css_class = 'long_name', $css_id = '', $movie_actors_max, $show_first = 3 ),
				),
				'schema_review_item_cw_movie_director_person_names' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'label'    => _x( 'Movie Director Names', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_movie_director_person_names',
					'content'  => $form->get_input_multi( 'schema_review_item_cw_movie_director_person_name',	// Singular.
						$css_class = 'long_name', $css_num = '', $movie_directors_max, $show_first = 1 ),
				),

				/*
				 * Schema Review Subject: CreativeWork > SoftwareApplication.
				 */
				'schema_review_item_software_app_os' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'software.application' ],
					'th_class' => 'medium',
					'label'    => _x( 'Operating System', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_software_app_os',
					'content'  => $form->get_input( 'schema_review_item_software_app_os', $css_class = 'wide' ),
				),
				'schema_review_item_software_app_cat' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'software.application' ],
					'th_class' => 'medium',
					'label'    => _x( 'Application Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_software_app_cat',
					'content'  => $form->get_input( 'schema_review_item_software_app_cat', $css_class = 'wide' ),
				),
				'schema_review_item_software_app_offers' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'software.application' ],
					'th_class' => 'medium',
					'label'    => _x( 'Software App Offers', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_software_app_offers',
					'content'  => $form->get_mixed_multi( array(
						'schema_review_item_software_app_offer_name' => array(
							'input_title' => _x( 'Software App Offer Name', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'offer_name',
						),
						'schema_review_item_software_app_offer_price' => array(
							'input_title' => _x( 'Software App Offer Price', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'price',
						),
						'schema_review_item_software_app_offer_currency' => array(
							'input_title'    => _x( 'Software App Offer Currency', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'currency',
							'select_options' => $currencies,
							'select_default' => $this->p->options[ 'og_def_currency' ],
							'event_names'    => array( 'on_focus_load_json' ),
							'event_args'     => array( 'json_var' => 'currencies' ),
						),
						'schema_review_item_software_app_offer_avail' => array(
							'input_title'    => _x( 'Software App Offer Availability', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'stock',
							'select_options' => $this->p->cf[ 'form' ][ 'item_availability' ],
							'select_default' => 'https://schema.org/InStock',
						),
					), $css_class = 'single_line', $css_id = 'schema_review_item_software_app_offer', $metadata_offers_max, $show_first = 2 ),
				),

				/*
				 * Schema Review Subject: Place.
				 */
				'schema_review_item_place_phone' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'place' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Telephone', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_phone',
					'content'  => $form->get_input( 'schema_review_item_place_phone' ),
				),
				'schema_review_item_place_street_address' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'place' ],
					'th_class' => 'medium',
					'label'    =>_x( 'Subject Street Address', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_street_address',
					'content'  => $form->get_input( 'schema_review_item_place_street_address', 'wide' ),
				),
				'schema_review_item_place_po_box_number' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'place' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject P.O. Box Number', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_po_box_number',
					'content'  => $form->get_input( 'schema_review_item_place_po_box_number' ),
				),
				'schema_review_item_place_city' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'place' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject City / Locality', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_city',
					'content'  => $form->get_input( 'schema_review_item_place_city' ),
				),
				'schema_review_item_place_region' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'place' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject State / Province', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_region',
					'content'  => $form->get_input( 'schema_review_item_place_region' ),
				),
				'schema_review_item_place_postal_code' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'place' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Zip / Postal Code', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_postal_code',
					'content'  => $form->get_input( 'schema_review_item_place_postal_code' ),
				),
				'schema_review_item_place_country' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'place' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Country', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_country',
					'content'  => $form->get_select_country( 'schema_review_item_place_country' ),
				),
				
				/*
				 * Schema Review Subject: Place > LocalBusiness.
				 */
				'schema_review_item_place_price_range' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'local.business' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Price Range', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_price_range',
					'content'  => $form->get_input( 'schema_review_item_place_price_range' ),
				),

				/*
				 * Schema Review Subject: Place > LocalBusiness > FoodEstablishment.
				 */
				'schema_review_item_place_cuisine' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'food.establishment' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Serves Cuisine', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-place_cuisine',
					'content'  => $form->get_input( 'schema_review_item_place_cuisine' ),
				),

				/*
				 * Schema Review Subject: Product.
				 */
				'schema_review_item_product_brand' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Product Brand', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_product_brand',
					'content'  => $form->get_input( 'schema_review_item_product_brand', $css_class = 'wide' ),
				),
				'schema_review_item_product_offers' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Product Offers', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_product_offers',
					'content'  => $form->get_mixed_multi( array(
						'schema_review_item_product_offer_name' => array(
							'input_title' => _x( 'Product Offer Name', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'offer_name',
						),
						'schema_review_item_product_offer_price' => array(
							'input_title' => _x( 'Product Offer Price', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'price',
						),
						'schema_review_item_product_offer_currency' => array(
							'input_title'    => _x( 'Product Offer Currency', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'currency',
							'select_options' => $currencies,
							'select_default' => $this->p->options[ 'og_def_currency' ],
							'event_names'    => array( 'on_focus_load_json' ),
							'event_args'     => array( 'json_var' => 'currencies' ),
						),
						'schema_review_item_product_offer_avail' => array(
							'input_title'    => _x( 'Product Offer Availability', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'stock',
							'select_options' => $this->p->cf[ 'form' ][ 'item_availability' ],
							'select_default' => 'https://schema.org/InStock',
						),
					), $css_class = 'single_line', $css_id = 'schema_review_item_product_offer', $metadata_offers_max, $show_first = 2 ),
				),
				'schema_review_item_product_retailer_part_no' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Product SKU', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_product_retailer_part_no',
					'content'  => $form->get_input( 'schema_review_item_product_retailer_part_no' ),
				),
				'schema_review_item_product_mfr_part_no' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Subject Product MPN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_product_mfr_part_no',
					'content'  => $form->get_input( 'schema_review_item_product_mfr_part_no' ),
				),

				/*
				 * Schema CreativeWork > Review > ClaimReview.
				 */
				'subsection_schema_claim_review' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review.claim' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Claim Review Information', 'metabox title', 'wpsso' )
				),
				'schema_review_claim_reviewed' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review.claim' ],
					'th_class' => 'medium',
					'label'    => _x( 'Short Summary of Claim', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_claim_reviewed',
					'content'  => $form->get_input( 'schema_review_claim_reviewed', $css_class = 'wide' ),
				),
				'schema_review_claim_first_url' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'review.claim' ],
					'th_class' => 'medium',
					'label'    => _x( 'First Appearance URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_claim_first_url',
					'content'  => $form->get_input( 'schema_review_claim_first_url', $css_class = 'wide' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > Software Application.
		 */
		public function filter_mb_sso_edit_schema_software_app_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$form_rows = array(
				'subsection_schema_software_app' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'software.application' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Software App Information', 'metabox title', 'wpsso' )
				),
				'schema_software_app_os' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'software.application' ],
					'th_class' => 'medium',
					'label'    => _x( 'Operating System', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_software_app_os',
					'content'  => $form->get_input( 'schema_software_app_os', $css_class = 'wide' ),
				),
				'schema_software_app_cat' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'software.application' ],
					'th_class' => 'medium',
					'label'    => _x( 'Application Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_software_app_cat',
					'content'  => $form->get_input( 'schema_software_app_cat', $css_class = 'wide' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > WebPage.
		 */
		public function filter_mb_sso_edit_schema_webpage_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$reviewed_by_max = SucomUtil::get_const( 'WPSSO_SCHEMA_WEBPAGE_REVIEWED_BY_MAX', 5 );

			$form_rows = array(
				'subsection_schema_webpage' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'webpage' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'WebPage Information', 'metabox title', 'wpsso' )
				),
				'schema_webpage_reviewed_by_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'webpage' ],
					'th_class' => 'medium',
					'label'    => _x( 'Reviewed By Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_webpage_reviewed_by_org_id',
					'content'  => $form->get_select_multi( 'schema_webpage_reviewed_by_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $reviewed_by_max, $show_first = 1,
							$is_disabled = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_webpage_reviewed_by_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'webpage' ],
					'th_class' => 'medium',
					'label'    => _x( 'Reviewed By Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_webpage_reviewed_by_person_id',
					'content'  => $form->get_select_multi( 'schema_webpage_reviewed_by_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $reviewed_by_max, $show_first = 1,
							$is_disabled = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_webpage_reviewed_last' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'webpage' ],
					'th_class' => 'medium',
					'label'    => _x( 'Reviewed Last', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_webpage_reviewed_last',
					'content'  => $form->get_date_time_timezone( 'schema_webpage_reviewed_last' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > WebPage > ProfilePage.
		 */
		public function filter_mb_sso_edit_schema_profilepage_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$form_rows = array(
				'subsection_schema_profilepage' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'webpage.profile' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'QA Page Information', 'metabox title', 'wpsso' )
				),
				'schema_profile_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'webpage.profile' ],
					'th_class' => 'medium',
					'label'    => _x( 'Select a Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_profile_person_id',
					'content'  => $form->get_select( 'schema_profile_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema CreativeWork > WebPage > QAPage.
		 */
		public function filter_mb_sso_edit_schema_qa_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$form_rows = array(
				'subsection_schema_qa' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'webpage.qa' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'QA Page Information', 'metabox title', 'wpsso' )
				),
				'schema_qa_desc' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'webpage.qa' ],
					'th_class' => 'medium',
					'label'    => _x( 'QA Heading', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_qa_desc',
					'content'  => $form->get_input( 'schema_qa_desc', $css_class = 'wide' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema Event.
		 */
		public function filter_mb_sso_edit_schema_event_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$currencies          = SucomUtil::get_currencies_abbrev();
			$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );

			$form_rows = array(
				'subsection_schema_event' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Event Information', 'metabox title', 'wpsso' )
				),
				'schema_event_lang' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Language', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_lang',
					'content'  => $form->get_select( 'schema_event_lang', SucomUtilWP::get_available_locales(), $css_class = 'locale' ),
				),
				'schema_event_attendance' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Attendance', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_attendance',
					'content'  => $form->get_select( 'schema_event_attendance', $this->p->cf[ 'form' ][ 'event_attendance' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_event_online_url' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Online URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_online_url',
					'content'  => $form->get_input( 'schema_event_online_url', $css_class = 'wide' ),
				),
				'schema_event_location_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Venue', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_location_id',
					'content'  => $form->get_select( 'schema_event_location_id', $args[ 'select_names' ][ 'place' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'place_names' ) ),
				),
				'schema_event_performer_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Performer Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_performer_org_id',
					'content'  => $form->get_select( 'schema_event_performer_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_event_performer_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Performer Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_performer_person_id',
					'content'  => $form->get_select( 'schema_event_performer_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_event_organizer_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Organizer Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_organizer_org_id',
					'content'  => $form->get_select( 'schema_event_organizer_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_event_organizer_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Organizer Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_organizer_person_id',
					'content'  => $form->get_select( 'schema_event_organizer_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_event_fund_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Funder Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_fund_org_id',
					'content'  => $form->get_select( 'schema_event_fund_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_event_fund_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Funder Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_fund_person_id',
					'content'  => $form->get_select( 'schema_event_fund_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_event_status' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Status', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_status',
					'content'  => $form->get_select( 'schema_event_status', $this->p->cf[ 'form' ][ 'event_status' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_event_start' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Start', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_start',
					'content'  => $form->get_date_time_timezone( 'schema_event_start' ),
				),
				'schema_event_end' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event End', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_end',
					'content'  => $form->get_date_time_timezone( 'schema_event_end' ),
				),
				'schema_event_previous' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Previous Start', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_previous',
					'content'  => $form->get_date_time_timezone( 'schema_event_previous' ),
				),
				'schema_event_offers_start' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Offers Start', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_offers_start',
					'content'  => $form->get_date_time_timezone( 'schema_event_offers_start' ),
				),
				'schema_event_offers_end' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Offers End', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_offers_end',
					'content'  => $form->get_date_time_timezone( 'schema_event_offers_end' ),
				),
				'schema_event_offers' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'event' ],
					'th_class' => 'medium',
					'label'    => _x( 'Event Offers', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_offers',
					'content'  => $form->get_mixed_multi( array(
						'schema_event_offer_name' => array(
							'input_title' => _x( 'Event Offer Name', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'offer_name',
						),
						'schema_event_offer_price' => array(
							'input_title' => _x( 'Event Offer Price', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'price',
						),
						'schema_event_offer_currency' => array(
							'input_title'    => _x( 'Event Offer Currency', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'currency',
							'select_options' => $currencies,
							'select_default' => $this->p->options[ 'og_def_currency' ],
							'event_names'    => array( 'on_focus_load_json' ),
							'event_args'     => array( 'json_var' => 'currencies' ),
						),
						'schema_event_offer_avail' => array(
							'input_title'    => _x( 'Event Offer Availability', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'stock',
							'select_options' => $this->p->cf[ 'form' ][ 'item_availability' ],
							'select_default' => 'https://schema.org/InStock',
						),
					), $css_class = 'single_line', $css_id = 'schema_event_offer', $metadata_offers_max, $show_first = 2 ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema Intangible > JobPosting.
		 */
		public function filter_mb_sso_edit_schema_job_posting_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$currencies       = SucomUtil::get_currencies_abbrev();
			$def_schema_title = $this->p->page->get_title( $mod, $md_key = 'seo_title', $max_len = 'schema_title' );

			$form_rows = array(
				'subsection_schema_job_posting' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'job.posting' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Job Posting Information', 'metabox title', 'wpsso' )
				),
				'schema_job_title' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'job.posting' ],
					'th_class' => 'medium',
					'label'    => _x( 'Job Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_title',
					'content'  => $form->get_input_dep( 'schema_job_title', $css_class = 'wide', $css_id = '',
						$max_len = 0, $def_schema_title, $is_disabled = false, $dep_id = 'schema_title' ),
				),
				'schema_job_hiring_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'job.posting' ],
					'th_class' => 'medium',
					'label'    => _x( 'Hiring Organization', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_hiring_org_id',
					'content'  => $form->get_select( 'schema_job_hiring_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_job_location_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'job.posting' ],
					'th_class' => 'medium',
					'label'    => _x( 'Job Location', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_location_id',
					'content'  => $form->get_select( 'schema_job_location_id', $args[ 'select_names' ][ 'place' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'places_names' ) ),
				),
				'schema_job_location_type' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'job.posting' ],
					'th_class' => 'medium',
					'label'    => _x( 'Job Location Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_location_type',
					'content'  => $form->get_select( 'schema_job_location_type', $this->p->cf[ 'form' ][ 'job_location_type' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_job_salary' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'job.posting' ],
					'th_class' => 'medium',
					'label'    => _x( 'Base Salary', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_salary',
					'content'  => $form->get_input( 'schema_job_salary', $css_class = 'medium' ) . ' ' .
						$form->get_select( 'schema_job_salary_currency', $currencies,
							$css_class = 'currency', $css_id = '', $is_assoc = true, $is_disabled = false,
								$selected = false, $event_names = array( 'on_focus_load_json' ),
									$event_args = array( 'json_var' => 'currencies' ) ) . ' ' .
						_x( 'per', 'option comment', 'wpsso' ) . ' ' .
						$form->get_select( 'schema_job_salary_period', $this->p->cf[ 'form' ][ 'time_text' ], 'short' ),
				),
				'schema_job_empl_type' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'job.posting' ],
					'th_class' => 'medium',
					'label'    => _x( 'Employment Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_empl_type',
					'content'  => $form->get_checklist( 'schema_job_empl_type', $this->p->cf[ 'form' ][ 'employment_type' ] ),
				),
				'schema_job_expire' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'job.posting' ],
					'th_class' => 'medium',
					'label'    => _x( 'Job Posting Expires', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_expire',
					'content'  => $form->get_date_time_timezone( 'schema_job_expire' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema Organization.
		 */
		public function filter_mb_sso_edit_schema_organization_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$form_rows = array(
				'subsection_schema_organization' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'organization' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Organization Information', 'metabox title', 'wpsso' )
				),
				'schema_organization_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'organization' ],
					'th_class' => 'medium',
					'label'    => _x( 'Select an Organization', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_organization_id',
					'content'  => $form->get_select( 'schema_organization_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema Person.
		 */
		public function filter_mb_sso_edit_schema_person_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$form_rows = array(
				'subsection_schema_person' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'person' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Person Information', 'metabox title', 'wpsso' )
				),
				'schema_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'person' ],
					'th_class' => 'medium',
					'label'    => _x( 'Select a Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_person_id',
					'content'  => $form->get_select( 'schema_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema Place.
		 */
		public function filter_mb_sso_edit_schema_place_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$form_rows = array(
				'subsection_schema_place' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'place' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Place Information', 'metabox title', 'wpsso' )
				),
				'schema_place_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'place' ],
					'th_class' => 'medium',
					'label'    => _x( 'Select a Place', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_place_id',
					'content'  => $form->get_select( 'schema_place_id', $args[ 'select_names' ][ 'place_custom' ], $css_class = 'wide', $css_id = '',
						$is_assoc = true, $is_disabled = false, $selected = false,
							$event_names = array( 'on_change_unhide_rows' ) ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema Product.
		 */
		public function filter_mb_sso_edit_schema_product_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$awards_max      = SucomUtil::get_const( 'WPSSO_SCHEMA_AWARDS_MAX', 5 );
			$currencies      = SucomUtil::get_currencies_abbrev();
			$dimension_units = WpssoUtilUnits::get_dimension_units();
			$fl_volume_units = WpssoUtilUnits::get_fluid_volume_units();
			$weight_units    = WpssoUtilUnits::get_weight_units();

			/*
			 * Note that unlike most schema option names, product options start with 'product_' and not 'schema_'.
			 */
			$form_rows = array(
				'subsection_schema_product' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Product Information (Main Product)', 'metabox title', 'wpsso' )
				),
				'schema_product_ecom_msg' => array(
					'tr_class'  => $args[ 'schema_tr_class' ][ 'product' ],
					'table_row' => empty( $this->p->avail[ 'ecom' ][ 'any' ] ) ? '' :
						'<td colspan="2">' . $this->p->msgs->get( 'pro-ecom-product-msg', array( 'mod' => $mod ) ) . '</td>',
				),

				/*
				 * See https://developers.google.com/search/docs/appearance/structured-data/product#json-ld_5.
				 */
				'schema_product_mrp' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Return Policy', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_mrp',
					'content'  => $form->get_select( 'product_mrp', $args[ 'select_names' ][ 'mrp' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_product_category' => array(	// Product Google Category ID.
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Google Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_category',
					'content'  => $form->get_select( 'product_category', $args[ 'select_names' ][ 'google_prod_cats' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false, $selected = false,
							$event_names = array( 'on_focus_load_json' ), $event_args = array(
								'json_var'  => 'product_categories',
								'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
								'is_transl' => true,					// No label translation required.
								'is_sorted' => true,					// No label sorting required.
							)
						),
				),
				'schema_product_brand' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Brand', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_brand',
					'content'  => $form->get_input( 'product_brand', $css_class = 'wide', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_product_price' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Price', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_price',
					'content'  => $form->get_input( 'product_price', $css_class = 'price', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
						$form->get_select( 'product_currency', $currencies, $css_class = 'currency', $css_id = '',
							$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'currencies' ) ),
				),
				'schema_product_price_type' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Price Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_price_type',
					'content'  => $form->get_select( 'product_price_type', $this->p->cf[ 'form' ][ 'price_type' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_product_min_advert_price' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Min Advert Price', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_min_advert_price',
					'content'  => $form->get_input( 'product_min_advert_price',
						$css_class = 'price', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_product_avail' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Availability', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_avail',
					'content'  => $form->get_select( 'product_avail', $this->p->cf[ 'form' ][ 'item_availability' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_product_condition' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Condition', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_condition',
					'content'  => $form->get_select( 'product_condition', $this->p->cf[ 'form' ][ 'item_condition' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_product_energy_efficiency' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Energy Rating', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_energy_efficiency',
					'content'  => '' .
						$form->get_select( 'product_energy_efficiency', $this->p->cf[ 'form' ][ 'energy_efficiency' ],
							$css_class = 'energy_efficiency', $css_id = '', $is_assoc = 'sorted' ) . ' ' .
						_x( 'from', 'option comment', 'wpsso' ) . ' ' .
						$form->get_select( 'product_energy_efficiency_min', $this->p->cf[ 'form' ][ 'energy_efficiency' ],
							$css_class = 'energy_efficiency', $css_id = '', $is_assoc = 'sorted' ) . ' ' .
						_x( 'to', 'option comment', 'wpsso' ) . ' ' .
						$form->get_select( 'product_energy_efficiency_max', $this->p->cf[ 'form' ][ 'energy_efficiency' ],
							$css_class = 'energy_efficiency', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_material' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Material', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_material',
					'content'  => $form->get_input( 'product_material', $css_class = '', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_product_pattern' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Pattern', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_pattern',
					'content'  => $form->get_input( 'product_pattern', $css_class = '', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_product_color' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Color', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_color',
					'content'  => $form->get_input( 'product_color', $css_class = '', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_product_target_gender' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Target Gender', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_target_gender',
					'content'  => $form->get_select( 'product_target_gender', $this->p->cf[ 'form' ][ 'target_gender' ],
						$css_class = 'gender', $css_id = '', $is_assoc = true ),
				),
				'schema_product_size' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Size', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_size',
					'content'  => $form->get_input( 'product_size', $css_class = '', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_product_size_group' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Size Group', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_size_group',
					'content'  => '' .
						$form->get_select( 'product_size_group_0', $this->p->cf[ 'form' ][ 'size_group' ],
							$css_class = 'size_group', $css_id = '', $is_assoc = true ) . ' ' .
						$form->get_select( 'product_size_group_1', $this->p->cf[ 'form' ][ 'size_group' ],
							$css_class = 'size_group', $css_id = '', $is_assoc = true ),
				),
				'schema_product_size_system' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Size System', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_size_system',
					'content'  => $form->get_select( 'product_size_system', $this->p->cf[ 'form' ][ 'size_system' ] ),
				),
				'schema_product_age_group' => $mod[ 'is_public' ] ? array(
					'tr_class' => 'hide_og_type hide_og_type_product',
					'th_class' => 'medium',
					'label'    => _x( 'Product Age Group', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_age_group',
					'content'  => $form->get_select( 'product_age_group', $this->p->cf[ 'form' ][ 'age_group' ] ),
				) : '',
				'schema_product_adult_type' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Adult Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_adult_type',
					'content'  => $form->get_select( 'product_adult_type', $this->p->cf[ 'form' ][ 'adult_type' ] ),
				),
				'schema_product_length_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Net Len. / Depth', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_length_value',
					'content'  => $form->get_input( 'product_length_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_length_units', $dimension_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_width_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Net Width', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_width_value',
					'content'  => $form->get_input( 'product_width_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_width_units', $dimension_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_height_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Net Height', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_height_value',
					'content'  => $form->get_input( 'product_height_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_height_units', $dimension_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_weight_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Net Weight', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_weight_value',
					'content'  => $form->get_input( 'product_weight_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_weight_units', $weight_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_fluid_volume_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Fluid Volume', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_fluid_volume_value',
					'content'  => $form->get_input( 'product_fluid_volume_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_fluid_volume_units', $fl_volume_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_shipping_length_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Shipping Length', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_shipping_length_value',
					'content'  => $form->get_input( 'product_shipping_length_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_shipping_length_units', $dimension_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_shipping_width_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Shipping Width', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_shipping_width_value',
					'content'  => $form->get_input( 'product_shipping_width_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_shipping_width_units', $dimension_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_shipping_height_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Shipping Height', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_shipping_height_value',
					'content'  => $form->get_input( 'product_shipping_height_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_shipping_height_units', $dimension_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_shipping_weight_value' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Shipping Weight', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_shipping_weight_value',
					'content'  => $form->get_input( 'product_shipping_weight_value',
						$css_class = 'unit_value', $css_id = '', $max_len = 0, $holder = true ) . ' ' .
							$form->get_select( 'product_shipping_weight_units', $weight_units,
								$css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_retailer_part_no' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product SKU', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_retailer_part_no',
					'content'  => $form->get_input( 'product_retailer_part_no',
						$css_class = '', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_product_mfr_part_no' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product MPN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_mfr_part_no',
					'content'  => $form->get_input( 'product_mfr_part_no',
						$css_class = '', $css_id = '', $max_len = 0, $holder = true ),
				),
				'schema_product_gtin14' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product GTIN-14', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin14',
					'content'  => $form->get_input( 'product_gtin14',
						$css_class = '', $css_id = '', array( 'min' => 14, 'max' => 14 ), $holder = true ),
				),
				'schema_product_gtin13' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product GTIN-13 (EAN)', 'option label', 'wpsso' ),	// aka Product EAN.
					'tooltip'  => 'meta-product_gtin13',
					'content'  => $form->get_input( 'product_gtin13',
						$css_class = '', $css_id = '', array( 'min' => 13, 'max' => 13 ), $holder = true ),
				),
				'schema_product_gtin12' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product GTIN-12 (UPC)', 'option label', 'wpsso' ),	// aka Product UPC.
					'tooltip'  => 'meta-product_gtin12',
					'content'  => $form->get_input( 'product_gtin12',
						$css_class = '', $css_id = '', array( 'min' => 12, 'max' => 12 ), $holder = true ),
				),
				'schema_product_gtin8' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product GTIN-8', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin8',
					'content'  => $form->get_input( 'product_gtin8',
						$css_class = '', $css_id = '', array( 'min' => 8, 'max' => 8 ), $holder = true ),
				),
				'schema_product_gtin' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product GTIN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin',
					'content'  => $form->get_input( 'product_gtin',
						$css_class = '', $css_id = '', array( 'min' => 8, 'max' => 14 ), $holder = true ),
				),
				'schema_product_isbn' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_isbn',
					'content'  => $form->get_input( 'product_isbn',
						$css_class = '', $css_id = '', array( 'min' => 10, 'max' => 13 ), $holder = true ),
				),
				'schema_product_award' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Awards', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_award',
					'content'  => $form->get_input_multi( 'product_award', $css_class = 'wide', $css_id = '',
						$awards_max, $show_first = 1 ),
				),

			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		/*
		 * Schema Service.
		 */
		public function filter_mb_sso_edit_schema_service_rows( $table_rows, $form, $head_info, $mod, $args ) {

			$currencies          = SucomUtil::get_currencies_abbrev();
			$awards_max          = SucomUtil::get_const( 'WPSSO_SCHEMA_AWARDS_MAX', 5 );
			$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );
			$offer_catalogs_max  = SucomUtil::get_const( 'WPSSO_SCHEMA_OFFER_CATALOGS_MAX', 5 );

			$form_rows = array(
				'subsection_schema_service' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Service Information', 'metabox title', 'wpsso' )
				),
				'schema_service_prov_org_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Provider Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_prov_org_id',
					'content'  => $form->get_select( 'schema_service_prov_org_id', $args[ 'select_names' ][ 'org' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'org_names' ) ),
				),
				'schema_service_prov_person_id' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Provider Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_prov_person_id',
					'content'  => $form->get_select( 'schema_service_prov_person_id', $args[ 'select_names' ][ 'person' ],
						$css_class = 'wide', $css_id = '', $is_assoc = true, $is_disabled = false,
							$selected = false, $event_names = array( 'on_focus_load_json' ),
								$event_args = array( 'json_var' => 'person_names' ) ),
				),
				'schema_service_award' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Awards', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_award',
					'content'  => $form->get_input_multi( 'schema_service_award', $css_class = 'wide', $css_id = '',
						$awards_max, $show_first = 1),
				),
				'schema_service_latitude' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Latitude', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_latitude',
					'content'  => $form->get_input( 'schema_service_latitude', $css_class = 'latitude' ) . ' ' .
						_x( 'decimal degrees', 'option comment', 'wpsso' ),
				),
				'schema_service_longitude' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Longitude', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_longitude',
					'content'  => $form->get_input( 'schema_service_longitude', $css_class = 'longitude' ) . ' ' .
						_x( 'decimal degrees', 'option comment', 'wpsso' ),
				),
				'schema_service_radius' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Radius', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_radius',
					'content'  => $form->get_input( 'schema_service_radius', $css_class = 'short' ) . ' ' .
						_x( 'meters from coordinates', 'option comment', 'wpsso' ),
				),
				'schema_service_offers_start' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Offers Start', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_offers_start',
					'content'  => $form->get_date_time_timezone( 'schema_service_offers_start' ),
				),
				'schema_service_offers_end' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Offers End', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_offers_end',
					'content'  => $form->get_date_time_timezone( 'schema_service_offers_end' ),
				),
				'schema_service_offers' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Service Offers', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_offers',
					'content'  => $form->get_mixed_multi( array(
						'schema_service_offer_name' => array(
							'input_title' => _x( 'Service Offer Name', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'offer_name',
						),
						'schema_service_offer_price' => array(
							'input_title' => _x( 'Service Offer Price', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'price',
						),
						'schema_service_offer_currency' => array(
							'input_title'    => _x( 'Service Offer Currency', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'currency',
							'select_options' => $currencies,
							'select_default' => $this->p->options[ 'og_def_currency' ],
							'event_names'    => array( 'on_focus_load_json' ),
							'event_args'     => array( 'json_var' => 'currencies' ),
						),
						'schema_service_offer_avail' => array(
							'input_title'    => _x( 'Service Offer Availability', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'stock',
							'select_options' => $this->p->cf[ 'form' ][ 'item_availability' ],
							'select_default' => 'https://schema.org/InStock',
						),
					), $css_class = 'single_line', $css_id = 'schema_service_offer', $metadata_offers_max, $show_first = 2 ),
				),
				'schema_service_offer_catalogs' => array(
					'tr_class' => $args[ 'schema_tr_class' ][ 'service' ],
					'th_class' => 'medium',
					'label'    => _x( 'Offer Catalogs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_service_offer_catalogs',
					'content'  => $form->get_mixed_multi( array(
						'schema_service_offer_catalog' => array(
							'input_label' => _x( 'Offer Catalog Name', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'wide offer_catalog_name',
						),
						'schema_service_offer_catalog_text' => array(
							'input_label' => _x( 'Offer Catalog Description', 'option label', 'wpsso' ),
							'input_type'  => 'textarea',
							'input_class' => 'wide offer_catalog_text',
						),
						'schema_service_offer_catalog_url' => array(
							'input_label' => _x( 'Offer Catalog URL', 'option label', 'wpsso' ),
							'input_type'  => 'text',
							'input_class' => 'wide offer_catalog_url',
						),
					), $css_class = '', $css_id = 'schema_service_offer_catalogs', $offer_catalogs_max, $show_first = 1 ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
