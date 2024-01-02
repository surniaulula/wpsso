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

if ( ! class_exists( 'WpssoUtilBlocks' ) ) {

	class WpssoUtilBlocks {

		private $p;	// Wpsso class object.
		private $u;	// WpssoUtil class object.

		/*
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin, &$util ) {

			$this->p =& $plugin;
			$this->u =& $util;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->u->add_plugin_filters( $this, array(
				'import_content_blocks' => 2,
			) );
		}

		public function filter_import_content_blocks( array $md_opts, $content = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( function_exists( 'is_sitemap' ) && is_sitemap() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping importing content blocks for sitemap' );
				}

				return $md_opts;
			}

			if ( empty( $content ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: content is empty' );
				}

				return $md_opts;
			}

			if ( ! function_exists( 'parse_blocks' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: parse_blocks function not found' );
				}

				return $md_opts;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'importing content blocks' );	// Begin timer.
			}

			$blocks = parse_blocks( $content );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'blocks', $blocks );
			}

			foreach ( $blocks as $block ) {

				if ( empty( $block[ 'blockName' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'block name is empty' );
					}

					continue;

				} elseif ( empty( $block[ 'attrs' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'block attrs is empty' );
					}

					continue;
				}

				/*
				 * Example filter name: wpsso_import_block_attrs_yoast_how_to_block
				 */
				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_import_block_attrs_' . $block[ 'blockName' ] );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filter ' . $filter_name );
				}

				$md_opts = apply_filters( $filter_name, $md_opts, $block[ 'attrs' ] );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'importing content blocks' );	// End timer.
			}

			return $md_opts;
		}
	}
}
