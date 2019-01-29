<?php
/**
 * From http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
 * Modified to allow native functionality in php version >= 5.4.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SuextJsonFormat' ) ) {

	class SuextJsonFormat {

		public static function get( $json, $options = 0, $depth = 512 ) {

			if ( ! is_string( $json ) ) {

				$php_version = phpversion();

				if ( version_compare( $php_version, '5.5.0',  '>=' ) ) {
					return json_encode( $json, $options|JSON_PRETTY_PRINT, $depth );
				} elseif ( version_compare( $php_version, '5.3.0',  '>=' ) ) {
					return json_encode( $json, $options|JSON_PRETTY_PRINT );
				} else {
					$json = json_encode( $json );
				}
			}

			$result	     = '';
			$pos         = 0;
			$strLen      = strlen( $json );
			$indentStr   = "\t";
			$newLine     = "\n";
			$prevChar    = '';
			$outOfQuotes = true;
			
			for ( $i = 0; $i < $strLen; $i++ ) {

				$copyLen = strcspn( $json, $outOfQuotes ? " \t\r\n\",:[{}]" : "\\\"", $i );

				if ( $copyLen >= 1 ) {

					$copyStr  = substr( $json, $i, $copyLen );
					$prevChar = '';
					$result   .= $copyStr;
					$i        += $copyLen - 1;

					continue;
				}

				$char = substr( $json, $i, 1 );

				if ( ! $outOfQuotes && $prevChar === '\\' ) {

					$result   .= $char;
					$prevChar = '';

					continue;
				}
				
				if ( $char === '"' && $prevChar !== '\\' ) {

					$outOfQuotes = !$outOfQuotes;

				} elseif ( $outOfQuotes && ( $char === '}' || $char === ']' ) ) {

					$result .= $newLine;

					$pos--;

					for ( $j = 0; $j < $pos; $j++ ) {
						$result .= $indentStr;
					}

				} elseif ( $outOfQuotes && false !== strpos( " \t\r\n", $char ) ) {

					continue;
				}

				$result .= $char;

				if ( $outOfQuotes && $char === ':' ) {

					$result .= ' ';

				} elseif ( $outOfQuotes && ( $char === ',' || $char === '{' || $char === '[' ) ) {

					$result .= $newLine;

					if ( $char === '{' || $char === '[' ) {
						$pos++;
					}

					for ( $j = 0; $j < $pos; $j++ ) {
						$result .= $indentStr;
					}
				}

				$prevChar = $char;
			}

			return $result;
		}
	}
}
