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
