<?php

// Use this file if you cannot use class autoloading. It will include all the
// files needed for the MarkdownInterface interface.
//
// Take a look at the PSR-0-compatible class autoloading implementation
// in the Readme.php file if you want a simple autoloader setup.

if ( ! interface_exists( 'Michelf\MarkdownInterface' ) ) {

	require_once dirname(__FILE__) . '/MarkdownInterface.php';
}
