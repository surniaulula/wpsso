<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoSubmenuLicenses' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/submenu/licenses.php';
}

if ( ! class_exists( 'WpssoSiteSubmenuSiteLicenses' ) && class_exists( 'WpssoSubmenuLicenses' ) ) {

	/*
	 * This settings page also requires enqueuing special scripts and styles for the plugin details / install thickbox link.
	 * See the WpssoScript and WpssoStyle classes for more info.
	 */
	class WpssoSiteSubmenuSiteLicenses extends WpssoSubmenuLicenses {

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
	}
}
