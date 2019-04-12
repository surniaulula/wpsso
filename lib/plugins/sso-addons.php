<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSubmenuAddons' ) ) {
	require_once WPSSO_PLUGINDIR . 'lib/submenu/addons.php';
}

if ( ! class_exists( 'WpssoPluginsSsoAddons' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoPluginsSsoAddons extends WpssoSubmenuAddons {
	}
}
