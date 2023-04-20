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
 * Import The SEO Framework Metadata.
 */
if ( ! class_exists( 'WpssoIntegDataSeoframeworkMeta' ) ) {

	class WpssoIntegDataSeoframeworkMeta extends WpssoIntegDataAbstractSeoMeta {

		protected $plugin_avail_key = 'seoframework';

		protected $opt_meta_keys = array(
			'post' => array(
				'primary_term_id'  => '_primary_term_category',
				'seo_title'        => '_genesis_title',
				'seo_desc'         => '_genesis_description',
				'og_title'         => '_open_graph_title',
				'og_desc'          => '_open_graph_description',
				'og_img_id'        => '_social_image_id',
				'og_img_url'       => '_social_image_url',
				'schema_title'     => '_genesis_title',
				'schema_desc'      => '_genesis_description',
				'tc_title'         => '_twitter_title',
				'tc_desc'          => '_twitter_description',
				'canonical_url'    => '_genesis_canonical_uri',
				'redirect_url'     => 'redirect',
				'robots_noarchive' => '_genesis_noarchive',
				'robots_nofollow'  => '_genesis_nofollow',
				'robots_noindex'   => '_genesis_noindex',
			),
			'term' => array(
				'seo_title'        => 'doctitle',
				'seo_desc'         => 'description',
				'og_title'         => 'og_title',
				'og_desc'          => 'og_description',
				'og_img_id'        => 'social_image_id',
				'og_img_url'       => 'social_image_url',
				'schema_title'     => 'doctitle',
				'schema_desc'      => 'description',
				'tc_title'         => 'tw_title',
				'tc_desc'          => 'tw_description',
				'canonical_url'    => 'canonical',
				'redirect_url'     => 'redirect',
				'robots_noarchive' => 'noarchive',
				'robots_nofollow'  => 'nofollow',
				'robots_noindex'   => 'noindex',
			),
			'user' => array(),
		);

		public function filter_save_term_options( array $md_opts, $term_id, array $mod ) {

			$this->cache_imported_meta[ 'term' ] = array();

			$md_opts = $this->filter_get_term_options( $md_opts, $term_id, $mod );

			if ( ! empty( $this->cache_imported_meta[ 'term' ] ) ) {

				$term_opts = get_term_meta( $term_id, 'autodescription-term-settings', $single = true );

				foreach( $this->cache_imported_meta[ 'term' ] as $meta_key => $bool ) {

					unset( $term_opts[ $meta_key ] );
				}

				update_term_meta( $term_id, 'autodescription-term-settings', $term_opts );
			}

			return $md_opts;
		}

		public function filter_get_term_options( array $md_opts, $term_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$term_opts = get_term_meta( $term_id, 'autodescription-term-settings', $single = true );

			if ( empty( $term_opts ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: autodescription term meta is empty' );
				}

				return $md_opts;
			}

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
