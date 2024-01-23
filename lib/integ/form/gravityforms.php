<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegFormGravityforms' ) ) {

	class WpssoIntegFormGravityforms {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( is_admin() ) {

				add_action( 'gform_noconflict_styles', array( $this, 'cleanup_gform_noconflict_styles' ) );
				add_action( 'gform_noconflict_scripts', array( $this, 'cleanup_gform_noconflict_scripts' ) );
			}
		}

		public function cleanup_gform_noconflict_styles( $styles ) {

			return array_merge( $styles, array(
				'jquery-ui.js',
				'jquery-qtip.js',
				'sucom-metabox-tabs',
				'sucom-settings-page',
				'sucom-settings-table',
				'wp-color-picker',
				'wpsso-admin-page',
			) );
		}

		public function cleanup_gform_noconflict_scripts( $scripts ) {

			return array_merge( $scripts, array(
				'jquery-ui-datepicker',
				'jquery-qtip',
				'sucom-admin-media',
				'sucom-admin-page',
				'sucom-metabox',
				'sucom-settings-page',
				'sucom-tooltips',
				'wp-color-picker',
				'wpsso-metabox',
				'wpsso-block-editor',
			) );
		}
	}
}
