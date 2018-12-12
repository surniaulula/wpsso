<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSiteSubmenuSiteTools' ) && class_exists( 'WpssoSubmenuTools' ) ) {

	class WpssoSiteSubmenuSiteTools extends WpssoSubmenuTools {
	}
}
