<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {	// Just in case.
	die( 'Incomplete plugin initialization...' );
}

if ( ! class_exists( 'WpssoSubmenuSetup' ) ) {
	require_once WPSSO_PLUGINDIR . 'lib/submenu/setup.php';
}

if ( ! class_exists( 'WpssoSiteSubmenuSiteSetup' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSiteSubmenuSiteSetup extends WpssoSubmenuSetup {
	}
}
