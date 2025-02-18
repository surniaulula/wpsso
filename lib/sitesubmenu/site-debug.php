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

if ( ! class_exists( 'WpssoSubmenuDebug' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/submenu/debug.php';
}

if ( ! class_exists( 'WpssoSiteSubmenuSiteDebug' ) && class_exists( 'WpssoSubmenuDebug' ) ) {

	class WpssoSiteSubmenuSiteDebug extends WpssoSubmenuDebug {
	}
}
