<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

/**
 * Make sure the WpssoSubmenuAdvanced class exists.
 */
if ( ! class_exists( 'WpssoSubmenuAddons' ) ) {
	require_once WPSSO_PLUGINDIR . 'lib/submenu/addons.php';
}

if ( ! class_exists( 'WpssoPluginSsoAddons' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoPluginSsoAddons extends WpssoSubmenuAddons {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;
		}
	}
}
