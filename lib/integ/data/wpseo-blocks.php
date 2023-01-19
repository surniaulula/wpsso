<?php
/*
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

/*
 * Import Yoast SEO Block Attrs.
 */
if ( ! class_exists( 'WpssoIntegDataWpseoBlocks' ) ) {

	class WpssoIntegDataWpseoBlocks {

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'import_block_attrs_yoast_how_to_block' => 2,
			) );
		}

		public function filter_import_block_attrs_yoast_how_to_block( $md_opts, $attrs ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $attrs ) ) {	// Nothing to do.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'block attrs array is empty' );
				}

				return $md_opts;
			}

			$ret[ 'schema_type' ] = 'howto';

			if ( ! empty( $attrs[ 'hasDuration' ] ) ) {

				foreach ( array(
					'days'    => 'schema_howto_prep_days',
					'hours'   => 'schema_howto_prep_hours',
					'minutes' => 'schema_howto_prep_mins',
					'seconds' => 'schema_howto_prep_secs',
				) as $key => $opt_key ) {

					$ret[ $opt_key ] = isset( $attrs[ $key ] ) ? $attrs[ $key ] : 0;
				}
			}

			if ( ! empty( $attrs[ 'jsonDescription' ] ) ) {

				$ret[ 'schema_desc' ] = SucomUtil::strip_html( $attrs[ 'jsonDescription' ] );
			}

			if ( ! empty( $attrs[ 'steps' ] ) && is_array( $attrs[ 'steps' ] ) ) {

				$md_opts = SucomUtil::preg_grep_keys( '/^schema_howto_step/', $md_opts, $invert = true );

				foreach ( $attrs[ 'steps' ] as $num => $step ) {

					$ret[ 'schema_howto_step_section_' . $num ] = 0;
					$ret[ 'schema_howto_step_' . $num ]         = SucomUtil::strip_html( $step[ 'jsonName' ] );
					$ret[ 'schema_howto_step_text_' . $num ]    = SucomUtil::strip_html( $step[ 'jsonText' ] );

					if ( ! empty( $step[ 'jsonImageSrc' ] ) ) {

						$image_url = $step[ 'jsonImageSrc' ];
						$image_id  = attachment_url_to_postid( $image_url );

						$ret[ 'schema_howto_step_img_id_' . $num ]  = $image_id;
					}
				}

				$num++;
			}

			foreach ( $ret as $opt_key => $val ) {

				$md_opts[ $opt_key . ':disabled' ] = true;
				$md_opts[ $opt_key ]               = $val;
			}

			return $md_opts;
		}
	}
}
