<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

$plugin_dir = trailingslashit( dirname( __FILE__ ) );

$plugin_file = $plugin_dir . 'wpsso.php';

require_once $plugin_dir . 'lib/config.php';

WpssoConfig::set_constants( $plugin_file );

WpssoConfig::require_libs( $plugin_file );	// Includes the register.php class library.

WpssoRegister::network_uninstall();
