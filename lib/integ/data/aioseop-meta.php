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
 * Import All in One SEO Pack Metadata.
 */
if ( ! class_exists( 'WpssoIntegDataAioseopMeta' ) ) {

	class WpssoIntegDataAioseopMeta extends WpssoIntegDataAbstractSeoMeta {

		protected $plugin_avail_key = 'aioseop';

		protected $opt_meta_keys = array(
			'post' => array(
				'og_title'               => '_aioseo_og_title',
				'og_desc'                => '_aioseo_og_description',
				'seo_title'              => '_aioseo_title',
				'seo_desc'               => '_aioseo_description',
				'tc_title'               => '_aioseo_twitter_title',
				'tc_desc'                => '_aioseo_twitter_description',
				'schema_title'           => '_aioseo_title',
				'schema_desc'            => '_aioseo_description',
				'schema_article_section' => '_aioseo_og_article_section',
			),
			'term' => array(),
			'user' => array(),
		);
	}
}
