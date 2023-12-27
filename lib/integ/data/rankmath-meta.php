<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoIntegDataAbstractSeoMeta' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/integ/data/abstract/seo-meta.php';
}

/*
 * Import Rank Math SEO metadata.
 */
if ( ! class_exists( 'WpssoIntegDataRankmathMeta' ) ) {

	class WpssoIntegDataRankmathMeta extends WpssoIntegDataAbstractSeoMeta {

		protected $plugin_avail_key = 'rankmath';

		protected $opt_meta_keys = array(
			'post' => array(
				'primary_term_id' => 'rank_math_primary_category',
				'seo_title'       => 'rank_math_title',
				'seo_desc'        => 'rank_math_description',
				'og_title'        => 'rank_math_facebook_title',
				'og_desc'         => 'rank_math_facebook_description',
				'og_img_id'       => 'rank_math_facebook_image_id',
				'og_img_url'      => 'rank_math_facebook_image',
				'schema_title'    => 'rank_math_snippet_title',
				'schema_desc'     => 'rank_math_snippet_desc',
				'tc_title'        => 'rank_math_twitter_title',
				'tc_desc'         => 'rank_math_twitter_description',
				'tc_sum_img_id'   => 'rank_math_twitter_image_id',
				'tc_sum_img_url'  => 'rank_math_twitter_image',
				'tc_lrg_img_id'   => 'rank_math_twitter_image_id',
				'tc_lrg_img_url'  => 'rank_math_twitter_image',
				'canonical_url'   => 'rank_math_canonical_url',
			),
			'term' => array(
				'og_title'        => 'rank_math_facebook_title',
				'og_desc'         => 'rank_math_facebook_description',
				'og_img_id'       => 'rank_math_facebook_image_id',
				'og_img_url'      => 'rank_math_facebook_image',
				'seo_desc'        => 'rank_math_description',
				'schema_title'    => 'rank_math_snippet_title',
				'schema_desc'     => 'rank_math_snippet_desc',
				'tc_title'        => 'rank_math_twitter_title',
				'tc_desc'         => 'rank_math_twitter_description',
				'tc_sum_img_id'   => 'rank_math_twitter_image_id',
				'tc_sum_img_url'  => 'rank_math_twitter_image',
				'tc_lrg_img_id'   => 'rank_math_twitter_image_id',
				'tc_lrg_img_url'  => 'rank_math_twitter_image',
				'canonical_url'   => 'rank_math_canonical_url',
			),
			'user' => array(
				'og_title'        => 'rank_math_facebook_title',
				'og_desc'         => 'rank_math_facebook_description',
				'og_img_id'       => 'rank_math_facebook_image_id',
				'og_img_url'      => 'rank_math_facebook_image',
				'seo_desc'        => 'rank_math_description',
				'schema_title'    => 'rank_math_snippet_title',
				'schema_desc'     => 'rank_math_snippet_desc',
				'tc_title'        => 'rank_math_twitter_title',
				'tc_desc'         => 'rank_math_twitter_description',
				'tc_sum_img_id'   => 'rank_math_twitter_image_id',
				'tc_sum_img_url'  => 'rank_math_twitter_image',
				'tc_lrg_img_id'   => 'rank_math_twitter_image_id',
				'tc_lrg_img_url'  => 'rank_math_twitter_image',
				'canonical_url'   => 'rank_math_canonical_url',
			),
		);

		protected function maybe_convert_vars( $value, array $mod ) {

			if ( false !== strpos( $value, '%' ) ) {

				$value = preg_replace( '/%+([^%]+)%+/', '%%$1%%', $value );	// Convert inline variable names.

				$value = $this->p->util->inline->replace_variables( $value, $mod );
			}

			return $value;
		}
	}
}
