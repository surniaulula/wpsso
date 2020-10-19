<?php

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'Michelf\Markdown' ) ) {

	require_once dirname( __FILE__ ) . '/markdown/Markdown.inc.php';
}

if ( ! function_exists( 'suext_markdown' ) ) {

	function suext_markdown( $text ) {

		return Michelf\Markdown::defaultTransform( $text );
	}
}

if ( ! class_exists( 'SuextMarkdownParser' ) ) {

	class SuextMarkdownParser {

		function __construct() {}

		function transform( $text ) {
		
			return Michelf\Markdown::defaultTransform( $text );
		}
	}
}
