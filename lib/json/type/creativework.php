<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeCreativeWork' ) ) {

	class WpssoJsonTypeCreativeWork {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_creativework' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_creativework( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			/*
			 * See https://schema.org/text.
			 */
			if ( ! empty( $this->p->options[ 'schema_def_add_text_prop' ] ) ) {

				$json_ret[ 'text' ] = $this->p->page->get_text( $mod, $md_key = 'schema_text', $max_len = 'schema_text' );
			}

			/*
			 * See https://schema.org/image as https://schema.org/ImageObject.
			 * See https://schema.org/video as https://schema.org/VideoObject.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding image and video properties for creativework' );
			}

			WpssoSchema::add_media_data( $json_ret, $mod, $mt_og, $size_names = 'schema', $add_video = true );

			/*
			 * See https://schema.org/provider.
			 * See https://schema.org/publisher.
			 */
			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$is_article_child = $this->p->schema->is_schema_type_child( $page_type_id, 'article' );

				/*
				 * The meta data key is unique, but the Schema property name may be repeated to add more than one
				 * value to a property array.
				 */
				foreach ( array(
					'schema_pub_org_id'     => 'publisher',	// Publisher Org.
					'schema_pub_person_id'  => 'publisher',	// Publisher Person.
					'schema_prov_org_id'    => 'provider',	// Provider Org.
					'schema_prov_person_id' => 'provider',	// Provider Person.
					'schema_fund_org_id'    => 'funder',	// Funder Org.
					'schema_fund_person_id' => 'funder',	// Funder Person.
				) as $md_key => $prop_name ) {

					$md_val = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $md_key, $filter_opts = true, $merge_defs = true );

					if ( WpssoSchema::is_valid_val( $md_val ) ) {	// Not null, an empty string, or 'none'.

						if ( strpos( $md_key, '_org_id' ) ) {

							$org_logo_key = 'org_logo_url';

							if ( 'publisher' === $prop_name ) {

								if ( $is_article_child ) {

									$org_logo_key = 'org_banner_url';
								}
							}

							WpssoSchemaSingle::add_organization_data( $json_ret[ $prop_name ], $mod, $md_val, $org_logo_key, $list_el = true );

						} elseif ( strpos( $md_key, '_person_id' ) ) {

							WpssoSchemaSingle::add_person_data( $json_ret[ $prop_name ], $mod, $md_val, $list_el = true );
						}
					}
				}
			}

			/*
			 * See https://schema.org/isPartOf.
			 */
			$json_ret[ 'isPartOf' ] = array();

			if ( ! empty( $mod[ 'obj' ] ) )	{	// Just in case.

				$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( is_array( $md_opts ) ) {	// Just in case.

					$values = SucomUtil::preg_grep_keys( '/^schema_ispartof_url_([0-9]+)$/', $md_opts, $invert = false, $replace = '$1' );

					foreach ( $values as $num => $url ) {

						if ( empty( $md_opts[ 'schema_ispartof_type_' . $num ] ) ) {

							$type_url = 'https://schema.org/CreativeWork';

						} else $type_url = $this->p->schema->get_schema_type_url( $md_opts[ 'schema_ispartof_type_' . $num ] );

						$json_ret[ 'isPartOf' ][] = WpssoSchema::get_schema_type_context( $type_url, array( 'url' => $url ) );
					}
				}
			}

			$filter_name = 'wpsso_json_prop_https_schema_org_ispartof';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
			}

			$json_ret[ 'isPartOf' ] = apply_filters( $filter_name, $json_ret[ 'isPartOf' ], $mod, $mt_og, $page_type_id, $is_main );

			/*
			 * See https://schema.org/headline.
			 */
			$json_ret[ 'headline' ] = $this->p->page->get_title( $mod, $md_key = 'schema_headline', $max_len = 'schema_headline' );

			/*
			 * See https://schema.org/keywords.
			 */
			$json_ret[ 'keywords' ] = $this->p->page->get_keywords_csv( $mod, $md_key = 'schema_keywords_csv' );

			/*
			 * See https://schema.org/copyrightYear.
			 * See https://schema.org/license.
			 * See https://schema.org/isFamilyFriendly.
			 * See https://schema.org/inLanguage.
			 */
			if ( ! empty( $mod[ 'obj' ] ) ) {

				/*
				 * The meta data key is unique, but the Schema property name may be repeated to add more than one
				 * value to a property array.
				 */
				foreach ( array(
					'schema_copyright_year'  => 'copyrightYear',
					'schema_license_url'     => 'license',
					'schema_family_friendly' => 'isFamilyFriendly',
					'schema_lang'            => 'inLanguage',
				) as $md_key => $prop_name ) {

					$md_val = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $md_key, $filter_opts = true, $merge_defs = true );

					if ( WpssoSchema::is_valid_val( $md_val ) ) {	// Not null, an empty string, or 'none'.

						switch ( $prop_name ) {

							case 'isFamilyFriendly':	// Must be a true or false boolean value.

								$md_val = empty( $md_val ) ? false : true;

								break;
						}

						$json_ret[ $prop_name ] = $md_val;
					}
				}
			}

			/*
			 * See https://schema.org/dateCreated.
			 * See https://schema.org/datePublished.
			 * See https://schema.org/dateModified.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array(
				'dateCreated'   => 'article:published_time',
				'datePublished' => 'article:published_time',
				'dateModified'  => 'article:modified_time',
			) );

			/*
			 * See https://schema.org/author as https://schema.org/Person.
			 * See https://schema.org/contributor as https://schema.org/Person.
			 */
			WpssoSchema::add_author_coauthors_data( $json_ret, $mod );

			/*
			 * See https://schema.org/thumbnailURL.
			 */
			$json_ret[ 'thumbnailUrl' ] = $this->p->media->get_thumbnail_url( $size_names = 'wpsso-thumbnail', $mod, $md_pre = array( 'schema', 'og' ) );

			/*
			 * See https://schema.org/award.
			 * See https://schema.org/citation.
			 *
			 * There is very little information available from Google about the expected JSON markup structure for
			 * citations - the only information available is from the the Google's Dataset type documentation.
			 *
			 * See https://developers.google.com/search/docs/appearance/structured-data/dataset.
			 */
			foreach ( array(
				'schema_award'    => 'award',
				'schema_citation' => 'citation',
			) as $md_key => $prop_name ) {

				$json_ret[ $prop_name ] = array();

				if ( ! empty( $mod[ 'obj' ] ) ) {

					$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

					if ( is_array( $md_opts ) ) {	// Just in case.

						$values = SucomUtil::preg_grep_keys( '/^' . $md_key . '_([0-9]+)$/', $md_opts, $invert = false, $replace = '$1' );

						foreach ( $values as $num => $text ) {

							$json_ret[ $prop_name ][] = $text;
						}
					}
				}

				$filter_name = 'wpsso_json_prop_https_schema_org_' . $prop_name;

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
				}

				$json_ret[ $prop_name ] = apply_filters( $filter_name, $json_ret[ $prop_name ], $mod, $mt_og, $page_type_id, $is_main );
			}

			/*
			 * See https://schema.org/comment as https://schema.org/Comment.
			 * See https://schema.org/commentCount.
			 */
			WpssoSchema::add_comments_data( $json_ret, $mod );

			/*
			 * Check for required CreativeWork properties.
			 */
			WpssoSchema::check_required_props( $json_ret, $mod, array( 'image' ), $page_type_id );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
