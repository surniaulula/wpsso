<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
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

/**
 * Import Rank Math SEO Metadata.
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
				'schema_title'    => 'rank_math_title',
				'schema_desc'     => 'rank_math_description',
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
				'schema_title'    => 'rank_math_title',
				'schema_desc'     => 'rank_math_description',
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
				'schema_title'    => 'rank_math_title',
				'schema_desc'     => 'rank_math_description',
				'tc_title'        => 'rank_math_twitter_title',
				'tc_desc'         => 'rank_math_twitter_description',
				'tc_sum_img_id'   => 'rank_math_twitter_image_id',
				'tc_sum_img_url'  => 'rank_math_twitter_image',
				'tc_lrg_img_id'   => 'rank_math_twitter_image_id',
				'tc_lrg_img_url'  => 'rank_math_twitter_image',
				'canonical_url'   => 'rank_math_canonical_url',
			),
		);

		protected function maybe_convert_vars( array $mod, $text ) {

			if ( false !== strpos( $text, '%' ) ) {

				$text = preg_replace( '/%+([^%]+)%+/', '%%$1%%', $text );	// Convert inline variable names.
			}

			return $text;
		}
	}
}
