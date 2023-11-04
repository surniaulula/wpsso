<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSiteSubmenuSiteAddons' ) && class_exists( 'WpssoAdmin' ) ) {

	/*
	 * This settings page requires enqueuing special scripts and styles for the plugin details / install thickbox link.
	 *
	 * See the WpssoScript and WpssoStyle classes for more info.
	 */
	class WpssoSiteSubmenuSiteAddons extends WpssoSubmenuAddons {

		/*
		 * See WpssoAdmin->get_form_object().
		 */
		protected function set_form_object( $menu_ext ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'setting site form object for '.$menu_ext );
			}

			$site_defs = $this->p->opt->get_site_defaults();

			$this->form = new SucomForm( $this->p, WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $site_defs, $menu_ext );
		}
	}
}
