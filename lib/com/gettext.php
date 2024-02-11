<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2024 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomGetText' ) ) {

	class SucomGetText {

		/*
		 * Translate HTML headers, paragraphs, list items, and blockquotes.
		 */
		public static function get_html_transl( $html, $text_domain ) {

			$gettext = self::parse_html( $html, $text_domain );

			foreach ( $gettext as $match => $arr ) {

				$transl = _x( $arr[ 'text' ], $arr[ 'context' ], $arr[ 'text_domain' ] );

				$html = str_replace( $match, $arr[ 'begin' ] . $transl . $arr[ 'end' ], $html );
			}

			return $html;
		}

		public static function show_html_php( $html, $text_domain ) {

			$gettext = self::parse_html( $html, $text_domain );

			foreach ( $gettext as $match => $arr ) {

				$arr[ 'text' ] = str_replace( '\'', '\\\'', $arr[ 'text' ] );

				echo sprintf( '_x( \'%s\', \'%s\', \'%s\' );', $arr[ 'text' ], $arr[ 'context' ], $arr[ 'text_domain' ] ) . "\n";
			}
		}

		public static function show_lib_php( $mixed, $context, $text_domain ) {

			if ( is_array( $mixed ) ) {

				foreach ( $mixed as $key => $val ) {

					if ( 'admin' === $key ) {

						continue;
					}

					self::show_lib_php( $val, $context, $text_domain );
				}

				return;

			} elseif ( is_numeric( $mixed ) ) {	// Number.

				return;

			} elseif ( empty( $mixed ) ) {	// Empty.

				return;

			} elseif ( 0 === strpos( $mixed, '/' ) ) {	// Regular expression.

				return;

			} elseif ( false !== filter_var( $mixed, FILTER_VALIDATE_URL ) ) {	// URL is valid.

				return;
			}

			$mixed = str_replace( '\'', '\\\'', $mixed );

			echo sprintf( '_x( \'%s\', \'%s\', \'%s\' );', $mixed, $context, $text_domain ) . "\n";

			/*
			 * Include values without their comment / qualifier (for example, 'Adult (13 years old or more)').
			 */
			if ( 'option value' === $context ) {

				if ( false !== ( $pos = strpos( $mixed, '(' ) ) ) {

					$mixed = trim( substr( $mixed, 0, $pos ) );

					echo sprintf( '_x( \'%s\', \'%s\', \'%s\' );', $mixed, $context, $text_domain ) . "\n";
				}

				if ( 0 === strpos( $mixed, '[' ) ) {

					$mixed = trim( $mixed, '[]' );

					echo sprintf( '_x( \'%s\', \'%s\', \'%s\' );', $mixed, $context, $text_domain ) . "\n";
				}
			}
		}

		private static function parse_html( $html, $text_domain ) {

			$parsed = array();

			foreach ( array(
				'/(<h[0-9][^>]*>)(.*)(<\/h[0-9]>)/Uis'         => 'html header',
				'/(<p>|<p [^>]*>)(.*)(<\/p>)/Uis'              => 'html paragraph',	// Get paragraphs before list items.
				'/(<li[^>]*>)(.*)(<\/li>)/Uis'                 => 'html list item',
				'/(<blockquote[^>]*>)(.*)(<\/blockquote>)/Uis' => 'html blockquote',
			) as $pattern => $context ) {

				if ( preg_match_all( $pattern, $html, $all_matches, PREG_SET_ORDER ) ) {

					foreach ( $all_matches as $num => $matches ) {

						list( $match, $begin, $text, $end ) = $matches;

						$html = str_replace( $match, '', $html );	// Do not match again.

						$text = trim( $text );	// Just in case.

						if ( '' === $text ) {	// Ignore HTML tags with no content.

							continue;
						}

						$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );	// Put everything on one line.

						$parsed[ $match ] = array(
							'begin'       => $begin,
							'text'        => $text,
							'end'         => $end,
							'context'     => $context,
							'text_domain' => $text_domain,
						);
					}
				}
			}

			return $parsed;
		}
	}
}
