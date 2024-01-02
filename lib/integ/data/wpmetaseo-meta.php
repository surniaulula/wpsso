<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
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
 * Import WP Meta SEO metadata.
 */
if ( ! class_exists( 'WpssoIntegDataWpmetaseoMeta' ) ) {

	class WpssoIntegDataWpmetaseoMeta extends WpssoIntegDataAbstractSeoMeta {

		protected $plugin_avail_key = 'wpmetaseo';

		protected $opt_meta_keys = array(
			'post' => array(
				'seo_title'       => '_metaseo_metatitle',
				'seo_desc'        => '_metaseo_metadesc',
				'og_title'        => '_metaseo_metaopengraph-title',
				'og_desc'         => '_metaseo_metaopengraph-desc',
				'og_img_url'      => '_metaseo_metaopengraph-image',
				'schema_title'    => '_metaseo_metadtitle',
				'schema_desc'     => '_metaseo_metadesc',
				'tc_title'        => '_metaseo_metatwitter-title',
				'tc_desc'         => '_metaseo_metatwitter-desc',
				'tc_sum_img_url'  => '_metaseo_metatwitter-image',
				'tc_lrg_img_url'  => '_metaseo_metatwitter-image',
			),
			'term' => array(
				'seo_title'       => 'wpms_category_metatitle',
				'seo_desc'        => 'wpms_category_metadesc',
				'schema_title'    => 'wpms_category_metatitle',
				'schema_desc'     => 'wpms_category_metadesc',
			),
			'user' => array(
			),
		);
	}
}
