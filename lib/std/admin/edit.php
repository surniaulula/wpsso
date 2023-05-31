<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
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
				'metabox_sso_edit_media_prio_video_rows' => 4,
			) );

			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				/*
				 * Compatibility check for the WPSSO JSON Premium add-on.
				 */
				if ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) || ! $this->p->check->pp( 'wpssojson' ) ) {

					$this->p->util->add_plugin_filters( $this, array(
						'metabox_sso_edit_schema_book_rows'          => 5,	// Schema CreativeWork > Book.
						'metabox_sso_edit_schema_movie_rows'         => 6,	// Schema CreativeWork > Movie.
						'metabox_sso_edit_schema_review_rows'        => 5,	// Schema CreativeWork > Review.
						'metabox_sso_edit_schema_software_app_rows'  => 5,	// Schema CreativeWork > Software Application.
						'metabox_sso_edit_schema_qa_rows'            => 5,	// Schema CreativeWork > WebPage > QAPage.
						'metabox_sso_edit_schema_event_rows'         => 6,	// Schema Event.
						'metabox_sso_edit_schema_job_posting_rows'   => 6,	// Schema Intangible > JobPosting.
						'metabox_sso_edit_schema_organization_rows'  => 6,	// Schema Organization.
						'metabox_sso_edit_schema_person_rows'        => 6,	// Schema Person.
						'metabox_sso_edit_schema_place_rows'         => 6,	// Schema Place.
						'metabox_sso_edit_schema_product_rows'       => 5,	// Schema Product.
					) );
				}
			}
		}

		public function filter_metabox_sso_edit_media_prio_video_rows( $table_rows, $form, $head_info, $mod ) {

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
					'tooltip'  => 'meta-vid_stream_url',
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

		public function filter_metabox_sso_edit_schema_book_rows( $table_rows, $form, $head_info, $mod, $type_row_class ) {

			$form_rows = array(
				'subsection_schema_book' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Book Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_book' => array(
					'tr_class'  => $type_row_class[ 'book' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_book_author_type' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Book Author Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_author_type',
					'content'  => $form->get_no_select( 'schema_book_author_type', $this->p->cf[ 'form' ][ 'author_types' ] ),
				),
				'schema_book_author_name' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Book Author Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_author_name',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_book_author_url' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Book Author URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_author_url',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_book_pub' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Book Published Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_pub',
					'content'  => $form->get_no_date_time_tz( 'schema_book_pub' ),
				),
				'schema_book_created' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Book Created Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_created',
					'content'  => $form->get_no_date_time_tz( 'schema_book_created' ),
				),
				'schema_book_edition' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Book Edition', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_edition',
					'content'  => $form->get_no_input_value( '' ),
				),
				'schema_book_format' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Book Format', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_format',
					'content'  => $form->get_no_select( 'schema_book_format', $this->p->cf[ 'form' ][ 'book_format' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_book_pages' => array(
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Number of Pages', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_pages',
					'content'  => $form->get_no_input_value( '', $css_class = 'short' ),
				),
				'schema_book_isbn' => array(		// Open Graph meta tag book:isbn.
					'tr_class' => $type_row_class[ 'book' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Book ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_isbn',
					'content'  => $form->get_no_input( 'schema_book_isbn', $css_class = '', $css_id = '', $holder = true ),
				),

				/*
				 * Schema CreativeWork > Book > Audiobook.
				 */
				'subsection_schema_audiobook' => array(
					'tr_class' => $type_row_class[ 'book_audio' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Audiobook Information', 'metabox title', 'wpsso' )
				),
				'schema_book_audio_duration_time' => array(
					'tr_class' => $type_row_class[ 'book_audio' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Audiobook Duration', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_book_audio_duration_time',
					'content'  => $form->get_no_input_time_dhms(),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_movie_rows( $table_rows, $form, $head_info, $mod, $type_row_class, $names ) {

			$movie_actors_max    = SucomUtil::get_const( 'WPSSO_SCHEMA_MOVIE_ACTORS_MAX', 15 );
			$movie_directors_max = SucomUtil::get_const( 'WPSSO_SCHEMA_MOVIE_DIRECTORS_MAX', 5 );

			$form_rows = array(
				'subsection_schema_movie' => array(
					'tr_class' => $type_row_class[ 'movie' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Movie Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_movie' => array(
					'tr_class'  => $type_row_class[ 'movie' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_movie_actor_person_names' => array(
					'tr_class' => $type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Movie Cast Names', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_actor_person_names',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'long_name', $css_id = '', '', $repeat = 5 ),
				),
				'schema_movie_director_person_names' => array(
					'tr_class' => $type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Movie Director Names', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_director_person_names',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'long_name', $css_id = '', '', $repeat = 2 ),
				),
				'schema_movie_prodco_org_id' => array(
					'tr_class' => $type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Production Company', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_prodco_org_id',
					'content'  => $form->get_no_select( 'schema_movie_prodco_org_id', $names[ 'org' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_movie_released' => array(
					'tr_class' => $type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Movie Release Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_released',
					'content'  => $form->get_no_date_time_tz( 'schema_movie_released' ),
				),
				'schema_movie_duration_time' => array(
					'tr_class' => $type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Movie Runtime', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_movie_duration_time',
					'content'  => $form->get_no_input_time_dhms(),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_review_rows( $table_rows, $form, $head_info, $mod, $type_row_class ) {

			$currencies          = SucomUtil::get_currency_abbrev();
			$schema_types        = $this->p->schema->get_schema_types_select();
			$item_type_row_class = WpssoSchema::get_schema_type_row_class( 'schema_review_item_type' );
			$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );
			$movie_actors_max    = SucomUtil::get_const( 'WPSSO_SCHEMA_MOVIE_ACTORS_MAX', 15 );
			$movie_directors_max = SucomUtil::get_const( 'WPSSO_SCHEMA_MOVIE_DIRECTORS_MAX', 5 );
			$sameas_url_max      = SucomUtil::get_const( 'WPSSO_SCHEMA_SAMEAS_URL_MAX', 5 );

			$form_rows = array(
				'subsection_schema_review' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Review Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_review' => array(
					'tr_class'  => $type_row_class[ 'review' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_review_rating' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Review Rating', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_rating',
					'content'  => '' .
						$form->get_no_input_value( $form->defaults[ 'schema_review_rating' ],
							$css_class = 'rating' ) . ' ' .
						_x( 'from', 'option comment', 'wpsso' ) . ' ' .
						$form->get_no_input_value( $form->defaults[ 'schema_review_rating_min' ],
							$css_class = 'rating' ) . ' ' .
						_x( 'to', 'option comment', 'wpsso' ) . ' ' .
						$form->get_no_input_value( $form->defaults[ 'schema_review_rating_max' ],
							$css_class = 'rating' ),
				),
				'schema_review_rating_alt_name' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Rating Value Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_rating_alt_name',
					'content'  => $form->get_no_input_value(),
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
					'tr_class' => $type_row_class[ 'review' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Review Subject Information', 'metabox title', 'wpsso' )
				),
				'schema_review_item_name' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_name',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_review_item_desc' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_desc',
					'content'  => $form->get_no_textarea_value( '' ),
				),
				'schema_review_item_img_id' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Image ID', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_img_id',
					'content'  => $form->get_no_input_image_upload( 'schema_review_item_img' ),
				),
				'schema_review_item_img_url' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_img_url',
					'content'  => $form->get_no_input_value( $value = '' ),
				),
				'schema_review_item_type' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Schema Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_type',
					'content'  => $form->get_no_select( 'schema_review_item_type', $schema_types,
						$css_class = 'schema_type', $css_id = '', $is_assoc = true,
							$selected = false, $event_names = array( 'on_focus_load_json', 'on_show_unhide_rows' ),
								$event_args = array(
									'json_var'  => 'schema_types',
									'exp_secs'  => WPSSO_CACHE_SELECT_JSON_EXP_SECS,	// Create and read from a javascript URL.
									'is_transl' => true,					// No label translation required.
									'is_sorted' => true,					// No label sorting required.
								) ),
				),
				'schema_review_item_url' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Webpage URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_url',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_review_item_sameas_url' => array(
					'tr_class' => $type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Same-As URLs', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_sameas_url',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide', $css_id = '', '', $repeat = 2 ),
				),

				/*
				 * Schema Review Subject: Creative Work.
				 */
				'schema_review_item_cw_author_type' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Author Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_type',
					'content'  => $form->get_no_select( 'schema_review_item_cw_author_type', $this->p->cf[ 'form' ][ 'author_types' ] ),
				),
				'schema_review_item_cw_author_name' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Author Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_name',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_review_item_cw_author_url' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Author URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_url',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_review_item_cw_pub' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Published Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_pub',
					'content'  => $form->get_no_date_time_tz( 'schema_review_item_cw_pub' ),
				),
				'schema_review_item_cw_created' => array(
					'tr_class' => 'hide_schema_type ' . $item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Created Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_item_cw_created',
					'content'  => $form->get_no_date_time_tz( 'schema_review_item_cw_created' ),
				),

				/*
				 * Schema Creative Work > Review > Claim Review.
				 */
				'subsection_schema_claim_review' => array(
					'tr_class' => $type_row_class[ 'review_claim' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Claim Review Information', 'metabox title', 'wpsso' )
				),
				'schema_review_claim_reviewed' => array(
					'tr_class' => $type_row_class[ 'review_claim' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Short Summary of Claim', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_claim_reviewed',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_review_claim_first_url' => array(
					'tr_class' => $type_row_class[ 'review_claim' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'First Appearance URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_review_claim_first_url',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_software_app_rows( $table_rows, $form, $head_info, $mod, $type_row_class ) {

			$form_rows = array(
				'subsection_schema_software_app' => array(
					'tr_class' => $type_row_class[ 'software_app' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Software App Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_software_app' => array(
					'tr_class'  => $type_row_class[ 'software_app' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_software_app_os' => array(
					'tr_class' => $type_row_class[ 'software_app' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Operating System', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_software_app_os',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_software_app_cat' => array(
					'tr_class' => $type_row_class[ 'software_app' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Application Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_software_app_cat',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_qa_rows( $table_rows, $form, $head_info, $mod, $type_row_class ) {

			$form_rows = array(
				'subsection_schema_qa' => array(
					'tr_class' => $type_row_class[ 'qa' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema QA Page Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_qa' => array(
					'tr_class'  => $type_row_class[ 'qa' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_qa_desc' => array(
					'tr_class' => $type_row_class[ 'qa' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'QA Heading', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_qa_desc',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_event_rows( $table_rows, $form, $head_info, $mod, $type_row_class, $names ) {

			$currencies          = SucomUtil::get_currency_abbrev();
			$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );

			$form_rows = array(
				'subsection_schema_event' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Event Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_event' => array(
					'tr_class'  => $type_row_class[ 'event' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_event_lang' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Language', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_lang',
					'content'  => $form->get_no_select( 'schema_event_lang', SucomUtil::get_available_locales(), 'locale' ),
				),
				'schema_event_attendance' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Attendance', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_attendance',
					'content'  => $form->get_no_select( 'schema_event_attendance', $this->p->cf[ 'form' ][ 'event_attendance' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_event_online_url' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Online URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_online_url',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'wide' ),
				),
				'schema_event_location_id' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Physical Venue', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_location_id',
					'content'  => $form->get_no_select( 'schema_event_location_id', $names[ 'place' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_event_organizer_org_id' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Organizer Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_organizer_org_id',
					'content'  => $form->get_no_select( 'schema_event_organizer_org_id', $names[ 'org' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_event_organizer_person_id' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Organizer Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_organizer_person_id',
					'content'  => $form->get_no_select( 'schema_event_organizer_person_id', $names[ 'person' ],
						$css_class = 'long_name' ),
				),
				'schema_event_performer_org_id' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Performer Org.', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_performer_org_id',
					'content'  => $form->get_no_select( 'schema_event_performer_org_id', $names[ 'org' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_event_performer_person_id' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Performer Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_performer_person_id',
					'content'  => $form->get_no_select( 'schema_event_performer_person_id', $names[ 'person' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_event_status' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Status', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_status',
					'content'  => $form->get_no_select( 'schema_event_status', $this->p->cf[ 'form' ][ 'event_status' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_event_start' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Start', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_start',
					'content'  => $form->get_no_date_time_tz( 'schema_event_start' ),
				),
				'schema_event_end' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event End', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_end',
					'content'  => $form->get_no_date_time_tz( 'schema_event_end' ),
				),
				'schema_event_previous' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Previous Start', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_previous',
					'content'  => $form->get_no_date_time_tz( 'schema_event_previous' ),
				),
				'schema_event_offers_start' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Offers Start', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_offers_start',
					'content'  => $form->get_no_date_time_tz( 'schema_event_offers_start' ),
				),
				'schema_event_offers_end' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Offers End', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_offers_end',
					'content'  => $form->get_no_date_time_tz( 'schema_event_offers_end' ),
				),
				'schema_event_offers' => array(
					'tr_class' => $type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Offers', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_event_offers',
					'content'  => $form->get_no_mixed_multi( array(
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
						),
						'schema_event_offer_avail' => array(
							'input_title'    => _x( 'Event Offer Availability', 'option label', 'wpsso' ),
							'input_type'     => 'select',
							'input_class'    => 'stock',
							'select_options' => $this->p->cf[ 'form' ][ 'item_availability' ],
							'select_default' => 'https://schema.org/InStock',
						),
					), $css_class = 'single_line', $css_id = 'schema_event_offer', $repeat = 2 ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_job_posting_rows( $table_rows, $form, $head_info, $mod, $type_row_class, $names ) {

			$currencies       = SucomUtil::get_currency_abbrev();
			$def_schema_title = $this->p->page->get_title( $mod, $md_key = 'seo_title', $max_len = 'schema_title' );

			$form_rows = array(
				'subsection_schema_job_posting' => array(
					'tr_class' => $type_row_class[ 'job_posting' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Job Posting Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_job_posting' => array(
					'tr_class'  => $type_row_class[ 'job_posting' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_job_title' => array(
					'tr_class' => $type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Job Title', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_title',
					'content'  => $form->get_no_input_value( $def_schema_title, $css_class = 'wide' ),
				),
				'schema_job_hiring_org_id' => array(
					'tr_class' => $type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Hiring Organization', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_hiring_org_id',
					'content'  => $form->get_no_select( 'schema_job_hiring_org_id', $names[ 'org' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_job_location_id' => array(
					'tr_class' => $type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Job Location', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_location_id',
					'content'  => $form->get_no_select( 'schema_job_location_id', $names[ 'place' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_job_location_type' => array(
					'tr_class' => $type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Job Location Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_location_type',
					'content'  => $form->get_no_select( 'schema_job_location_type', $this->p->cf[ 'form' ][ 'job_location_type' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_job_salary' => array(
					'tr_class' => $type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Base Salary', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_salary',
					'content'  => $form->get_no_input_value( $value = '', $css_class = 'medium' ) . ' ' .
						$form->get_no_select( 'schema_job_salary_currency', $currencies, $css_class = 'currency' ) . ' ' .
						_x( 'per', 'option comment', 'wpsso' ) . ' ' .
						$form->get_no_select( 'schema_job_salary_period', $this->p->cf[ 'form' ][ 'time_text' ], 'short' ),
				),
				'schema_job_empl_type' => array(
					'tr_class' => $type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Employment Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_empl_type',
					'content'  => $form->get_no_checklist( 'schema_job_empl_type', $this->p->cf[ 'form' ][ 'employment_type' ] ),
				),
				'schema_job_expire' => array(
					'tr_class' => $type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Job Posting Expires', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_job_expire',
					'content'  => $form->get_no_date_time_tz( 'schema_job_expire' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_organization_rows( $table_rows, $form, $head_info, $mod, $type_row_class, $names ) {

			$form_rows = array(
				'subsection_schema_organization' => array(
					'tr_class' => $type_row_class[ 'organization' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Organization Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_organization' => array(
					'tr_class'  => $type_row_class[ 'organization' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_organization_id' => array(
					'tr_class' => $type_row_class[ 'organization' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Select an Organization', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_organization_id',
					'content'  => $form->get_no_select( 'schema_organization_id', $names[ 'org' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_person_rows( $table_rows, $form, $head_info, $mod, $type_row_class, $names ) {

			$form_rows = array(
				'subsection_schema_person' => array(
					'tr_class' => $type_row_class[ 'person' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Person Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_person' => array(
					'tr_class'  => $type_row_class[ 'person' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_person_id' => array(
					'tr_class' => $type_row_class[ 'person' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Select a Person', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_person_id',
					'content'  => $form->get_no_select( 'schema_person_id', $names[ 'person' ],
						$css_class = 'long_name' ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_place_rows( $table_rows, $form, $head_info, $mod, $type_row_class, $names ) {

			$form_rows = array(
				'subsection_schema_place' => array(
					'tr_class' => $type_row_class[ 'place' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Place Information', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_place' => array(
					'tr_class'  => $type_row_class[ 'place' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_place_id' => array(
					'tr_class' => $type_row_class[ 'place' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Select a Place', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-schema_place_id',
					'content'  => $form->get_no_select( 'schema_place_id', $names[ 'place_custom' ],
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_edit_schema_product_rows( $table_rows, $form, $head_info, $mod, $type_row_class ) {

			$currencies         = SucomUtil::get_currency_abbrev();
			$product_categories = $this->p->util->get_google_product_categories();
			$dimension_units    = WpssoUtilUnits::get_dimension_units();
			$fluid_volume_units = WpssoUtilUnits::get_fluid_volume_units();
			$weight_units       = WpssoUtilUnits::get_weight_units();

			/*
			 * Note that unlike most schema option names, product options start with 'product_' and not 'schema_'.
			 */
			$form_rows = array(
				'subsection_schema_product' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Schema Product Information (Main Product)', 'metabox title', 'wpsso' )
				),
				'pro_feature_msg_schema_product' => array(
					'tr_class'  => $type_row_class[ 'product' ],
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpsso' ) . '</td>',
				),
				'schema_product_category' => array(	// Product Google Category ID.
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'label'    => _x( 'Product Google Category', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_category',
					'content'  => $form->get_no_select( 'product_category', $product_categories, $css_class = 'wide', $css_id = '', $is_assoc = true ),
				),
				'schema_product_brand' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Brand', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_brand',
					'content'  => $form->get_no_input( 'product_brand', $css_class = 'wide', $css_id = '', $holder = true ),
				),
				'schema_product_price' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Price', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_price',
					'content'  => $form->get_no_input( 'product_price', $css_class = 'price', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_currency', $currencies, $css_class = 'currency' ),
				),
				'schema_product_price_type' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Price Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_price_type',
					'content'  => $form->get_no_select( 'product_price_type', $this->p->cf[ 'form' ][ 'price_type' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_product_min_advert_price' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Min Advert Price', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_min_advert_price',
					'content'  => $form->get_no_input( 'product_min_advert_price', $css_class = 'price', $css_id = '', $holder = true ),
				),
				'schema_product_avail' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Availability', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_avail',
					'content'  => $form->get_no_select( 'product_avail', $this->p->cf[ 'form' ][ 'item_availability' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_product_condition' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Condition', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_condition',
					'content'  => $form->get_no_select( 'product_condition', $this->p->cf[ 'form' ][ 'item_condition' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_product_energy_efficiency' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Energy Rating', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_energy_efficiency',
					'content'  => '' .
						$form->get_no_select( 'product_energy_efficiency', $this->p->cf[ 'form' ][ 'energy_efficiency' ],
							$css_class = 'energy_efficiency', $css_id = '', $is_assoc = 'sorted' ) . ' ' .
						_x( 'from', 'option comment', 'wpsso' ) . ' ' .
						$form->get_no_select( 'product_energy_efficiency_min', $this->p->cf[ 'form' ][ 'energy_efficiency' ],
							$css_class = 'energy_efficiency', $css_id = '', $is_assoc = 'sorted' ) . ' ' .
						_x( 'to', 'option comment', 'wpsso' ) . ' ' .
						$form->get_no_select( 'product_energy_efficiency_max', $this->p->cf[ 'form' ][ 'energy_efficiency' ],
							$css_class = 'energy_efficiency', $css_id = '', $is_assoc = 'sorted' ),

				),
				'schema_product_material' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Material', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_material',
					'content'  => $form->get_no_input( 'product_material', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_pattern' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Pattern', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_pattern',
					'content'  => $form->get_no_input( 'product_pattern', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_color' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Color', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_color',
					'content'  => $form->get_no_input( 'product_color', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_target_gender' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Target Gender', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_target_gender',
					'content'  => $form->get_no_select( 'product_target_gender', $this->p->cf[ 'form' ][ 'target_gender' ],
						$css_class = 'gender', $css_id = '', $is_assoc = true ),
				),
				'schema_product_size' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Size', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_size',
					'content'  => $form->get_no_input( 'product_size', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_size_group' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Size Group', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_size_group',
					'content'  => '' .
						$form->get_no_select( 'product_size_group_0', $this->p->cf[ 'form' ][ 'size_group' ],
							$css_class = 'size_group', $css_id = '', $is_assoc = true ) . ' ' .
						$form->get_no_select( 'product_size_group_1', $this->p->cf[ 'form' ][ 'size_group' ],
							$css_class = 'size_group', $css_id = '', $is_assoc = true ),
				),
				'schema_product_size_system' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Size System', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_size_system',
					'content'  => $form->get_no_select( 'product_size_system', $this->p->cf[ 'form' ][ 'size_system' ] ),
				),
				'schema_product_age_group' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Age Group', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_age_group',
					'content'  => $form->get_no_select( 'product_age_group', $this->p->cf[ 'form' ][ 'age_group' ] ),
				),
				'schema_product_adult_type' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Adult Type', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_adult_type',
					'content'  => $form->get_no_select( 'product_adult_type', $this->p->cf[ 'form' ][ 'adult_type' ] ),
				),
				'schema_product_length_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Net Len. / Depth', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_length_value',
					'content'  => $form->get_no_input( 'product_length_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_length_units', $dimension_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_width_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Net Width', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_width_value',
					'content'  => $form->get_no_input( 'product_width_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_width_units', $dimension_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_height_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Net Height', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_height_value',
					'content'  => $form->get_no_input( 'product_height_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_height_units', $dimension_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_weight_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Net Weight', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_weight_value',
					'content'  => $form->get_no_input( 'product_weight_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_weight_units', $weight_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_fluid_volume_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Fluid Volume', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_fluid_volume_value',
					'content'  => $form->get_no_input( 'product_fluid_volume_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_fluid_volume_units', $fluid_volume_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_shipping_length_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Shipping Length', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_shipping_length_value',
					'content'  => $form->get_no_input( 'product_shipping_length_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_shipping_length_units', $dimension_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_shipping_width_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Shipping Width', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_shipping_width_value',
					'content'  => $form->get_no_input( 'product_shipping_width_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_shipping_width_units', $dimension_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_shipping_height_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Shipping Height', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_shipping_height_value',
					'content'  => $form->get_no_input( 'product_shipping_height_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_shipping_height_units', $dimension_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_shipping_weight_value' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Shipping Weight', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_shipping_weight_value',
					'content'  => $form->get_no_input( 'product_shipping_weight_value', $css_class = 'unit_value', $css_id = '', $holder = true ) . ' ' .
						$form->get_no_select( 'product_shipping_weight_units', $weight_units, $css_class = 'unit_text', $css_id = '', $is_assoc = 'sorted' ),
				),
				'schema_product_retailer_part_no' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product SKU', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_retailer_part_no',
					'content'  => $form->get_no_input( 'product_retailer_part_no', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_mfr_part_no' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product MPN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_mfr_part_no',
					'content'  => $form->get_no_input( 'product_mfr_part_no', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_gtin14' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-14', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin14',
					'content'  => $form->get_no_input( 'product_gtin14', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_gtin13' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-13 (EAN)', 'option label', 'wpsso' ),	// aka Product EAN.
					'tooltip'  => 'meta-product_gtin13',
					'content'  => $form->get_no_input( 'product_gtin13', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_gtin12' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-12 (UPC)', 'option label', 'wpsso' ),	// aka Product UPC.
					'tooltip'  => 'meta-product_gtin12',
					'content'  => $form->get_no_input( 'product_gtin12', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_gtin8' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-8', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin8',
					'content'  => $form->get_no_input( 'product_gtin8', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_gtin' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_gtin',
					'content'  => $form->get_no_input( 'product_gtin', $css_class = '', $css_id = '', $holder = true ),
				),
				'schema_product_isbn' => array(
					'tr_class' => $type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product ISBN', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-product_isbn',
					'content'  => $form->get_no_input( 'product_isbn', $css_class = '', $css_id = '', $holder = true ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
