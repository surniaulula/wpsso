<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
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

/**
 * Import Yoast SEO Metadata.
 */
if ( ! class_exists( 'WpssoIntegDataWpseoMeta' ) ) {

	class WpssoIntegDataWpseoMeta extends WpssoIntegDataAbstractSeoMeta {

		protected $plugin_avail_key = 'wpseo';

		protected $opt_meta_keys = array(
			'post' => array(
				'primary_term_id'     => '_yoast_wpseo_primary_category',
				'og_title'            => '_yoast_wpseo_opengraph-title',
				'og_desc'             => '_yoast_wpseo_opengraph-description',
				'og_img_id'           => '_yoast_wpseo_opengraph-image-id',
				'og_img_url'          => '_yoast_wpseo_opengraph-image',
				'seo_title'           => '_yoast_wpseo_title',
				'seo_desc'            => '_yoast_wpseo_metadesc',
				'tc_title'            => '_yoast_wpseo_twitter-title',
				'tc_desc'             => '_yoast_wpseo_twitter-description',
				'tc_sum_img_id'       => '_yoast_wpseo_twitter-image-id',
				'tc_sum_img_url'      => '_yoast_wpseo_twitter-image',
				'tc_lrg_img_id'       => '_yoast_wpseo_twitter-image-id',
				'tc_lrg_img_url'      => '_yoast_wpseo_twitter-image',
				'schema_title'        => '_yoast_wpseo_title',
				'schema_title_bc'     => '_yoast_wpseo_bctitle',
				'schema_desc'         => '_yoast_wpseo_metadesc',
				'schema_reading_mins' => '_yoast_wpseo_estimated-reading-time-minutes',
				'canonical_url'       => '_yoast_wpseo_canonical',
				'robots_noindex'      => '_yoast_wpseo_meta-robots-noindex',
			),
			'term' => array(
				'og_title'        => 'wpseo_opengraph-title',
				'og_desc'         => 'wpseo_opengraph-description',
				'og_img_id'       => 'wpseo_opengraph-image-id',
				'og_img_url'      => 'wpseo_opengraph-image',
				'seo_title'       => 'wpseo_title',
				'seo_desc'        => 'wpseo_desc',
				'tc_title'        => 'wpseo_twitter-title',
				'tc_desc'         => 'wpseo_twitter-description',
				'tc_sum_img_id'   => 'wpseo_twitter-image-id',
				'tc_sum_img_url'  => 'wpseo_twitter-image',
				'tc_lrg_img_id'   => 'wpseo_twitter-image-id',
				'tc_lrg_img_url'  => 'wpseo_twitter-image',
				'schema_title'    => 'wpseo_title',
				'schema_title_bc' => 'wpseo_bctitle',
				'schema_desc'     => 'wpseo_desc',
				'canonical_url'   => 'wpseo_canonical',
				'robots_noindex'  => 'wpseo_noindex',
			),
			'user' => array(
				'og_title'       => 'wpseo_title',
				'og_desc'        => 'wpseo_metadesc',
				'seo_title'      => 'wpseo_title',
				'seo_desc'       => 'wpseo_metadesc',
				'robots_noindex' => 'wpseo_noindex_author',
			),
		);

		public function filter_save_term_options( array $md_opts, $term_id, array $mod ) {

			$this->cache_imported_meta[ 'term' ] = array();

			$md_opts = $this->filter_get_term_options( $md_opts, $term_id, $mod );

			if ( ! empty( $this->cache_imported_meta[ 'term' ] ) ) {

				$tax_slug = $mod[ 'tax_slug' ];

				$tax_meta = get_option( 'wpseo_taxonomy_meta' );

				foreach( $this->cache_imported_meta[ 'term' ] as $meta_key => $bool ) {

					unset( $tax_meta[ $tax_slug ][ $term_id ][ $meta_key ] );
				}

				update_option( 'wpseo_taxonomy_meta', $tax_meta );
			}

			return $md_opts;
		}

		public function filter_get_term_options( array $md_opts, $term_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$tax_slug = $mod[ 'tax_slug' ];

			$tax_meta = get_option( 'wpseo_taxonomy_meta' );

			if ( empty( $tax_meta[ $tax_slug ][ $term_id ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . $tax_slug . ' taxonomy meta for term id ' . $term_id . ' is empty' );
				}

				return $md_opts;
			}

			$term_opts = $tax_meta[ $tax_slug ][ $term_id ];

			foreach ( $this->import_opt_keys as $opt_key => $bool ) {

				if ( ! empty( $this->opt_meta_keys[ 'term' ][ $opt_key ] ) ) {

					$meta_key = $this->opt_meta_keys[ 'term' ][ $opt_key ];

					// Skip options that have a custom value. An empty string and 'none' are not custom values.
					if ( isset( $md_opts[ $opt_key ] ) && '' !== $md_opts[ $opt_key ] && 'none' !== $md_opts[ $opt_key ] ) {

						continue;

					} elseif ( $this->add_mod_term_meta( $mod, $md_opts, $opt_key, $meta_key, $term_opts ) ) {

						$this->cache_imported_meta[ 'term' ][ $meta_key ] = true;
					}
				}
			}

			return $md_opts;
		}
	}
}
