<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoUtilMetabox' ) ) {

	class WpssoUtilMetabox {

		private $p;

		/**
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function do_tabbed( $metabox_id = '', $tabs = array(), $table_rows = array(), $args = array() ) {

			echo $this->get_tabbed( $metabox_id, $tabs, $table_rows, $args );
		}

		public function get_tabbed( $metabox_id = '', $tabs = array(), $table_rows = array(), $args = array() ) {

			$tab_keys           = array_keys( $tabs );
			$default_tab        = '_' . reset( $tab_keys );		// Must start with an underscore.
			$class_metabox_tabs = 'sucom-metabox-tabs';
			$class_link         = 'sucom-tablink';
			$class_tabset       = 'sucom-tabset';
			$metabox_html       = '';

			if ( ! empty( $metabox_id ) ) {

				$metabox_id = '_' . trim( $metabox_id, '_' );		// Must start with an underscore.

				$class_metabox_tabs .= ' ' . $class_metabox_tabs . $metabox_id;
			}

			extract( array_merge( array(
				'layout'        => 'horizontal',	// 'horizontal', 'vertical', or 'responsive'.
				'is_auto_draft' => false,
				'scroll_to'     => isset( $_GET[ 'scroll_to' ] ) ? '#' . self::sanitize_key( $_GET[ 'scroll_to' ] ) : '',
			), $args ) );

			$class_metabox_tabs .= ' ' . $layout . ( $is_auto_draft ? ' auto-draft' : '' );

			$metabox_html .= "\n" . '<script type="text/javascript">jQuery( document ).ready( function() { ' . 
				'sucomTabs(\'' . $metabox_id . '\', \'' . $default_tab . '\', \'' . $scroll_to . '\'); });</script>' . "\n";

			$metabox_html .= '<div class="' . $class_metabox_tabs . '">' . "\n";

			$metabox_html .= '<ul class="' . $class_metabox_tabs . '">' . "\n";

			/**
			 * Add the settings tab list.
			 */
			$tab_num = 0;

			foreach ( $tabs as $tab => $title_transl ) {

				$tab_num++;

				$class_href_key = $class_tabset . $metabox_id . '-tab_' . $tab;
				$class_icon_key = $class_link . ' ' . $class_link . $metabox_id . ' ' . $class_link . '-icon ' . $class_link . '-href_' . $tab;
				$class_link_key = $class_link . ' ' . $class_link . $metabox_id . ' ' . $class_link . '-text ' . $class_link . '-href_' . $tab;

				$metabox_html .= '<li class="tab_space' . ( $tab_num === 1 ? ' start_tabs' : '' ) . '"></li>';
				$metabox_html .= '<li class="' . $class_href_key . '">';
				$metabox_html .= '<a class="' . $class_icon_key . '" href="#' . $class_href_key . '"></a>';
				$metabox_html .= '<a class="' . $class_link_key . '" href="#' . $class_href_key . '">' . $title_transl . '</a>';
				$metabox_html .= '</li>';	// Do not add a newline.
			}

			$metabox_html .= '<li class="tab_space end_tabs"></li>';
			$metabox_html .= '</ul><!-- .' . $class_metabox_tabs . ' -->' . "\n";

			/**
			 * Add the settings table for each tab.
			 */
			foreach ( $tabs as $tab => $title_transl ) {

				$class_href_key = $class_tabset . $metabox_id . '-tab_' . $tab;

				$metabox_html .= $this->get_table( $table_rows[ $tab ], $class_href_key, 
					( empty( $metabox_id ) ? '' : $class_tabset . $metabox_id ),
						$class_tabset, $title_transl );
			}

			$metabox_html .= '</div><!-- .' . $class_metabox_tabs . ' -->' . "\n";

			return $metabox_html;
		}

		public function do_table( $table_rows, $class_href_key = '', $class_tabset_mb = '', $class_tabset = 'sucom-no_tabset', $title_transl = '' ) {

			echo $this->get_table( $table_rows, $class_href_key, $class_tabset_mb, $class_tabset, $title_transl );
		}

		public function get_table( $table_rows, $class_href_key = '', $class_tabset_mb = '', $class_tabset = 'sucom-no_tabset', $title_transl = '' ) {

			$metabox_html = '';

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				return $metabox_html;
			}

			$total_rows     = count( $table_rows );
			$count_rows     = 0;
			$hidden_opts    = 0;
			$hidden_rows    = 0;
			$user_classname = $this->p->lca . 'user';
			$show_opts      = class_exists( $user_classname ) ? call_user_func( array( $user_classname, 'show_opts' ) ) : 'basic';

			foreach ( $table_rows as $key => $row ) {

				if ( empty( $row ) ) {	// Just in case.

					continue;
				}

				/**
				 * Default row class and id attribute values.
				 */
				$tr = array(
					'class' => 'sucom_alt' . 
						( $count_rows % 2 ) . 
						( $count_rows === 0 ? ' first_row' : '' ) . 
						( $count_rows === ( $total_rows - 1 ) ? ' last_row' : '' ),
					'id' => ( is_int( $key ) ? '' : 'tr_' . $key )
				);

				/**
				 * If we don't already have a table row tag, then add one.
				 */
				if ( strpos( $row, '<tr ' ) === false ) {

					$row = '<tr class="' . $tr[ 'class' ] . '"' . ( empty( $tr[ 'id' ] ) ? '' : ' id="' . $tr[ 'id' ] . '"' ) . '>' . $row;

				} else {

					foreach ( $tr as $att => $val ) {

						if ( empty( $tr[ $att ] ) ) {

							continue;
						}

						/**
						 * If we're here, then we have a table row tag already. Count the number of rows
						 * and options that are hidden.
						 */
						if ( $att === 'class' && ! empty( $show_opts ) &&
							( $matched = preg_match( '/<tr [^>]*class="[^"]*hide(_row)?_in_' . $show_opts . '[" ]/', $row, $m ) > 0 ) ) {

							if ( ! isset( $m[ 1 ] ) ) {

								$hidden_opts += preg_match_all( '/(<th|<tr[^>]*><td)/', $row, $all_matches );
							}

							$hidden_rows += $matched;
						}

						/**
						 * Add the attribute value.
						 */
						$row = preg_replace( '/(<tr [^>]*' . $att . '=")([^"]*)(")/', '$1$2 ' . $tr[ $att ] . '$3', $row, -1, $cnt );

						/**
						 * If one hasn't been added, then add both the attribute and its value.
						 */
						if ( $cnt < 1 ) {

							$row = preg_replace( '/(<tr )/', '$1' . $att . '="' . $tr[ $att ] . '" ', $row, -1, $cnt );
						}
					}
				}

				/**
				 * Add a closing table row tag if we don't already have one.
				 */
				if ( strpos( $row, '</tr>' ) === false ) {

					$row .= '</tr>' . "\n";
				}

				/**
				 * Update the table row array element with the new value.
				 */
				$table_rows[ $key ] = $row;

				$count_rows++;
			}

			if ( 0 === $count_rows ) {
				
				$table_rows[ 'no_options' ] = '<tr><td align="center">' .
					'<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>' .
					'</td></tr>';

				$count_rows++;
			}

			$div_class = ( empty( $show_opts ) ? '' : 'sucom-show_' . $show_opts ) . 
				( empty( $class_tabset ) ? '' : ' ' . $class_tabset ) . 
				( empty( $class_tabset_mb ) ? '' : ' ' . $class_tabset_mb ) . 
				( empty( $class_href_key ) ? '' : ' ' . $class_href_key );

			$table_class = 'sucom-settings ' . $this->p->lca . 
				( empty( $class_href_key ) ? '' : ' ' . $class_href_key ) . 
				( $hidden_rows > 0 && $hidden_rows === $count_rows ? ' hide_in_' . $show_opts : '' );

			$metabox_html .= '<div class="' . $div_class . '">' . "\n";
			$metabox_html .= $title_transl ? '<h3 class="sucom-metabox-tab_title">' . $title_transl . '</h3>' : '';
			$metabox_html .= '<table class="' . $table_class . '">' . "\n";

			foreach ( $table_rows as $row ) {
				$metabox_html .= $row;
			}

			$metabox_html .= '</table><!-- .' . $table_class . ' --> ' . "\n";
			$metabox_html .= '</div><!-- .' . $div_class . ' -->' . "\n";

			$show_opts_label = $this->p->cf[ 'form' ][ 'show_options' ][ $show_opts ];

			if ( $hidden_opts > 0 ) {

				$metabox_html .= '<div class="hidden_opts_msg ' . $class_tabset . '-msg ' . $class_tabset_mb . '-msg ' . $class_href_key . '-msg">' .
					sprintf( _x( '%1$d additional options not shown in "%2$s" view', 'option comment', 'wpsso' ), $hidden_opts,
						_x( $show_opts_label, 'option value', 'wpsso' ) ) .
					' (<a href="javascript:void(0);" onClick="sucomViewUnhideRows( \'' . $class_href_key . '\', \'' . $show_opts . '\' );">' .
						_x( 'show these options now', 'option comment', 'wpsso' ) . '</a>)</div>' . "\n";

			} elseif ( $hidden_rows > 0 ) {

				$metabox_html .= '<div class="hidden_opts_msg ' . $class_tabset . '-msg ' . $class_tabset_mb . '-msg ' . $class_href_key . '-msg">' .
					sprintf( _x( '%1$d additional rows not shown in "%2$s" view', 'option comment', 'wpsso' ), $hidden_rows,
						_x( $show_opts_label, 'option value', 'wpsso' ) ) .
					' (<a href="javascript:void(0);" onClick="sucomViewUnhideRows( \'' . $class_href_key . '\', \'' . $show_opts . '\', \'hide_row_in\' );">' .
						_x( 'show these rows now', 'option comment', 'wpsso' ) . '</a>)</div>' . "\n";
			}

			return $metabox_html;
		}

	}
}
