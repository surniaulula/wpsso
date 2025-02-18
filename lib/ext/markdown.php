<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2017-2025 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'Michelf\MarkdownExtra' ) ) {

	require_once dirname( __FILE__ ) . '/markdown/MarkdownExtra.inc.php';
}

/*
 * Provides the SuextMarkdown::transform() method.
 */
if ( ! class_exists( 'SuextMarkdown' ) ) {

	class SuextMarkdown {

		public static function transform( $text ) {

			return Michelf\MarkdownExtra::defaultTransform( $text );
		}
	}
}

/*
 * Provides the suext_markdown() function.
 */
if ( ! function_exists( 'suext_markdown' ) ) {

	function suext_markdown( $text ) {

		return SuextMarkdown::transform( $text );
	}
}
