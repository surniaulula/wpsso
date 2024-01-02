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

/*
 * Import Yoast SEO block attrs.
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

				$md_opts = SucomUtil::preg_grep_keys( '/^schema_howto_step/', $md_opts, $invert = true );	// Remove any existing howto steps.

				$step_num = 0;	// Start option number at 0.

				foreach ( $attrs[ 'steps' ] as $key => $step ) {

					$step_name = isset( $step[ 'jsonName' ] ) ? SucomUtil::strip_html( $step[ 'jsonName' ] ) : '';
					$step_desc = isset( $step[ 'jsonText' ] ) ? SucomUtil::strip_html( $step[ 'jsonText' ] ) : '';
					$step_img  = isset( $step[ 'jsonImageSrc' ] ) ? SucomUtil::strip_html( $step[ 'jsonImageSrc' ] ) : '';

					$ret[ 'schema_howto_step_section_' . $step_num ] = 0;
					$ret[ 'schema_howto_step_' . $step_num ]         = $step_name;
					$ret[ 'schema_howto_step_text_' . $step_num ]    = $step_desc;

					if ( $step_img ) {

						if ( $image_id  = attachment_url_to_postid( $step_img ) ) {

							$ret[ 'schema_howto_step_img_id_' . $step_num ] = $image_id;
						}
					}

					$step_num++;
				}
			}

			foreach ( $ret as $opt_key => $val ) {

				$md_opts[ $opt_key . ':disabled' ] = true;
				$md_opts[ $opt_key ]               = $val;
			}

			return $md_opts;
		}
	}
}
