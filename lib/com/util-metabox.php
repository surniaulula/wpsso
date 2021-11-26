<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomUtil' ) ) {

	require_once dirname( __FILE__ ) . '/util.php';
}

if ( ! class_exists( 'SucomUtilMetabox' ) ) {

	class SucomUtilMetabox {
		
		public static function get_table_metadata( array $metadata, array $skip_keys, $obj, $metabox_id, $key_title = 'Key', $value_title = 'Value' ) {

			$md_filtered = apply_filters( $metabox_id . '_metabox_table_metadata', $metadata, $obj );
			$skip_keys   = apply_filters( $metabox_id . '_metabox_table_skip_keys', $skip_keys, $obj );

			$metabox_html   = self::get_table_metadata_css( $metabox_id );
			$metabox_html   .= '<table><thead><tr><th class="key-column">' . $key_title . '</th>';
			$metabox_html   .= '<th class="value-column">' . $value_title . '</th></tr></thead><tbody>';

			ksort( $md_filtered );

			$row_count = 0;

			foreach( $md_filtered as $key => $el ) {

				foreach ( $skip_keys as $key_preg ) {

					if ( preg_match( $key_preg, $key ) ) {

						continue 2;
					}
				}

				$row_count++;
				$is_added = isset( $metadata[ $key ] ) ? false : true;
				$key_esc  = esc_html( $key );
				$el       = SucomUtil::maybe_unserialize_array( $el );
				$el_esc   = esc_html( var_export( $el, true ) );

				$metabox_html .= $is_added ? '<tr class="added-meta">' : '<tr>';
				$metabox_html .= '<td class="key-column"><div class="key-cell"><pre>' . $key_esc . '</pre></div></td>';
				$metabox_html .= '<td class="value-column"><div class="value-cell"><pre>' . $el_esc . '</pre></div></td>';
				$metabox_html .= '</tr>' . "\n";
			}

			if ( ! $row_count ) {

				$metabox_html .= '<tr>';
				$metabox_html .= '<td class="key-column"><div class="key-cell"><pre>&nbsp;</pre></div></td>';
				$metabox_html .= '<td class="value-column"><div class="value-cell"><pre>&nbsp;</pre></div></td>';
				$metabox_html .= '</tr>' . "\n";
			}

			$metabox_html .= '</tbody></table>';

			return $metabox_html;
		}

		public static function get_table_metadata_css( $metabox_id ) {

			$custom_style_css = '

				div#' . $metabox_id . '.postbox table {
					width:100%;
					max-width:100%;
					text-align:left;
					table-layout:fixed;
				}

				div#' . $metabox_id . '.postbox table .key-column {
					width:30%;
				}

				div#' . $metabox_id . '.postbox table tr.added-meta {
					background-color:#eee;
				}

				div#' . $metabox_id . '.postbox table td {
					padding:10px;
					vertical-align:top;
					border:1px dotted #ccc;
				}

				div#' . $metabox_id . '.postbox table td div {
					overflow-x:auto;
				}

				div#' . $metabox_id . '.postbox table td div pre {
					margin:0;
					padding:0;
				}
			';

			$custom_style_css = SucomUtil::minify_css( $custom_style_css, $metabox_id );

			return '<style type="text/css">' . $custom_style_css . '</style>';
		}
	}
}
