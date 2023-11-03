<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSiteSubmenuSiteLicenses' ) && class_exists( 'WpssoAdmin' ) ) {

	/*
	 * This settings page also requires enqueuing special scripts and styles for the plugin details / install thickbox link.
	 * See the WpssoScript and WpssoStyle classes for more info.
	 */
	class WpssoSiteSubmenuSiteLicenses extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;

			$this->menu_metaboxes = array(
				'licenses' => _x( 'Plugin and Add-on Licenses', 'metabox title', 'wpsso' ),
			);
		}

		/*
		 * See WpssoAdmin->get_form_object().
		 */
		protected function set_form_object( $menu_ext ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'setting site form object for '.$menu_ext );
			}

			$def_site_opts = $this->p->opt->get_site_defaults();

			$this->form = new SucomForm( $this->p, WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $def_site_opts, $menu_ext );
		}

		/*
		 * See WpssoAdmin->add_meta_boxes().
		 */
		public function show_metabox_licenses( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->licenses_metabox_content( $network = true );
		}
	}
}
