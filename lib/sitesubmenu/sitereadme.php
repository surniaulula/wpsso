<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSitesubmenuSitereadme' ) && class_exists( 'WpssoSubmenuReadme' ) ) {

	class WpssoSitesubmenuSitereadme extends WpssoSubmenuReadme {
	}
}

?>
