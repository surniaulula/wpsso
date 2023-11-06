<?php

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'Michelf\MarkdownExtra' ) ) {

	require_once dirname( __FILE__ ) . '/markdown/MarkdownExtra.inc.php';
}

if ( ! function_exists( 'suext_markdown' ) ) {

	function suext_markdown( $text ) {

		return SuextMarkdown::transform( $text );
	}
}

if ( ! class_exists( 'SuextMarkdown' ) ) {

	class SuextMarkdown {

		public static function transform( $text ) {

			return Michelf\MarkdownExtra::defaultTransform( $text );
		}
	}
}
