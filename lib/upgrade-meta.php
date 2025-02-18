<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoUpgradeMeta' ) ) {

	class WpssoUpgradeMeta {

		private $p;	// Wpsso class object.

		private static $rename_md_options = array(
			'wpsso' => array(
				499 => array(
					'link_desc' => 'seo_desc',
					'meta_desc' => 'seo_desc',
				),
				503 => array(
					'schema_recipe_calories' => 'schema_recipe_nutri_cal',
				),
				514 => array(
					'rp_img_id'     => 'pin_img_id',
					'rp_img_id_pre' => 'pin_img_id_lib',
					'rp_img_width'  => '',
					'rp_img_height' => '',
					'rp_img_crop'   => '',
					'rp_img_crop_x' => '',
					'rp_img_crop_y' => '',
					'rp_img_url'    => 'pin_img_url',
				),
				537 => array(
					'schema_add_type_url' => 'schema_addl_type_url_0',
				),
				569 => array(
					'schema_add_type_url' => 'schema_addl_type_url',	// Option modifiers are preserved.
				),
				615 => array(
					'org_type' => 'org_schema_type',
				),
				628 => array(
					'product_gender' => 'product_target_gender',
				),
				649 => array(
					'product_ean' => 'product_gtin13',
				),

				/*
				 * The custom width, height, and crop options were removed in preference for attachment specific
				 * options (ie. 'attach_img_crop_x' and 'attach_img_crop_y').
				 */
				660 => array(
					'og_img_width'              => '',
					'og_img_height'             => '',
					'og_img_crop'               => '',
					'og_img_crop_x'             => '',
					'og_img_crop_y'             => '',
					'schema_article_img_width'  => '',
					'schema_article_img_height' => '',
					'schema_article_img_crop'   => '',
					'schema_article_img_crop_x' => '',
					'schema_article_img_crop_y' => '',
					'schema_img_width'          => '',
					'schema_img_height'         => '',
					'schema_img_crop'           => '',
					'schema_img_crop_x'         => '',
					'schema_img_crop_y'         => '',
					'tc_sum_img_width'          => '',
					'tc_sum_img_height'         => '',
					'tc_sum_img_crop'           => '',
					'tc_sum_img_crop_x'         => '',
					'tc_sum_img_crop_y'         => '',
					'tc_lrg_img_width'          => '',
					'tc_lrg_img_height'         => '',
					'tc_lrg_img_crop'           => '',
					'tc_lrg_img_crop_x'         => '',
					'tc_lrg_img_crop_y'         => '',
					'thumb_img_width'           => '',
					'thumb_img_height'          => '',
					'thumb_img_crop'            => '',
					'thumb_img_crop_x'          => '',
					'thumb_img_crop_y'          => '',
				),
				692 => array(
					'product_mpn' => 'product_mfr_part_no',
					'product_sku' => 'product_retailer_part_no',
				),
				696 => array(
					'og_art_section' => 'schema_article_section',
				),
				701 => array(
					'article_topic' => 'schema_article_section',
				),
				725 => array(
					'product_volume_value' => 'product_fluid_volume_value',
				),
				786 => array(
					'og_img_id_pre'     => 'og_img_id_lib',
					'p_img_id_pre'      => 'pin_img_id_lib',
					'tc_lrg_img_id_pre' => 'tc_lrg_img_id_lib',
					'tc_sum_img_id_pre' => 'tc_sum_img_id_lib',
					'schema_img_id_pre' => 'schema_img_id_lib',
				),
				812 => array(
					'sharing_url' => '',
				),
				815 => array(
					'p_img_id'     => 'pin_img_id',
					'p_img_id_lib' => 'pin_img_id_lib',
					'p_img_url'    => 'pin_img_url',
				),
				829 => array(
					'book_isbn' => 'schema_book_isbn',
				),
				920 => array(	// Renamed for WPSSO Core v13.5.0.
					'article_section' => 'schema_article_section',
					'reading_mins'    => 'schema_reading_mins',
				),
				935 => array(	// Renamed for WPSSO Core v14.0.0.
					'product_adult_oriented'    => 'product_adult_type',
					'product_depth_value'       => 'product_length_value',
					'product_size_type'         => 'product_size_group_0',
					'schema_review_rating_from' => 'schema_review_rating_min',
					'schema_review_rating_to'   => 'schema_review_rating_max',
				),
				936 => array(	// Renamed for WPSSO Core v14.2.0.
					'product_size_group' => 'product_size_group_0',
				),
				944 => array(	// Renamed for WPSSO Core v14.4.0.
					'schema_keywords' => 'schema_keywords_csv',
				),
				979 => array(
					'schema_howto_step_css_id'               => 'schema_howto_step_anchor_id',
					'schema_howto_step_anchor'               => 'schema_howto_step_anchor_id',
					'schema_howto_step_container_id'         => 'schema_howto_step_anchor_id',
					'schema_recipe_instruction_css_id'       => 'schema_recipe_instruction_anchor_id',
					'schema_recipe_instruction_anchor'       => 'schema_recipe_instruction_anchor_id',
					'schema_recipe_instruction_container_id' => 'schema_recipe_instruction_anchor_id',
				),
			),
		);

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function md_options( array $md_opts, array $mod ) {

			/*
			 * Get the current options version number for checks to follow.
			 */
			$prev_version = $this->p->opt->get_version( $md_opts, 'wpsso' );	// Returns 'opt_version'.

			/*
			 * Maybe renamed some option keys.
			 */
			$version_keys = apply_filters( 'wpsso_rename_md_options_keys', self::$rename_md_options, $mod );

			$md_opts = $this->p->util->rename_options_by_ext( $md_opts, $version_keys );

			/*
			 * Check for schema type IDs that need to be renamed.
			 */
			$schema_type_keys_preg = '/^(schema_type|place_schema_type|plm_place_schema_type)(_[0-9]+)?$/';

			foreach ( SucomUtil::preg_grep_keys( $schema_type_keys_preg, $md_opts ) as $md_key => $md_val ) {

				if ( ! empty( $this->p->cf[ 'head' ][ 'schema_renamed' ][ $md_val ] ) ) {

					$md_opts[ $md_key ] = $this->p->cf[ 'head' ][ 'schema_renamed' ][ $md_val ];
				}
			}

			/*
			 * Import and delete deprecated robots post metadata.
			 */
			if ( $prev_version > 0 && $prev_version <= 759 ) {

				foreach ( array(
					'noarchive',
					'nofollow',
					'noimageindex',
					'noindex',
					'nosnippet',
					'notranslate',
				) as $directive_key ) {

					$opt_key  = 'robots_' . $directive_key;
					$meta_key = '_wpsso_' . $directive_key;

					$directive_value = $mod[ 'obj' ]::get_meta( $mod[ 'id' ], $meta_key, $single = true );	// Use static method from child.

					if ( '' !== $directive_value ) {

						$md_opts[ $opt_key ] = (int) $directive_value;

						$mod[ 'obj' ]::delete_meta( $mod[ 'id' ], $meta_key );	// Use static method from child.
					}
				}
			}

			if ( $prev_version > 0 && $prev_version <= 902 ) {

				/*
				 * If there is a multilingual plugin available, trust the plugin and ignore any previous /
				 * inherited custom language value.
				 */
				if ( $this->p->avail[ 'lang' ][ 'any' ] ) {

					unset( $md_opts[ 'schema_lang' ] );
				}
			}

			if ( $prev_version > 0 && $prev_version <= 917 ) {

				if ( ! empty( $md_opts[ 'product_target_gender' ] ) ) {

					$md_opts[ 'product_target_gender' ] = strtolower( $md_opts[ 'product_target_gender' ] );
				}
			}

			$md_opts = apply_filters( 'wpsso_upgraded_md_options', $md_opts, $mod );

			/*
			 * Add plugin and add-on versions (ie. 'checksum', 'opt_checksum', and 'opt_versions').
			 */
			$this->p->opt->add_versions_checksum( $md_opts );	// $md_opts must be an array.

			return $md_opts;
		}
	}
}
