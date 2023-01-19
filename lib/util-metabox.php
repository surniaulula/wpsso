<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoUtilMetabox' ) ) {

	class WpssoUtilMetabox {

		private $p;	// Wpsso class object.

		/*
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

			$doing_ajax    = SucomUtilWP::doing_ajax();
			$tab_keys      = array_keys( $tabs );
			$default_tab   = '_' . reset( $tab_keys );	// Must start with an underscore.
			$mb_tabs_class = 'sucom-metabox-tabs';
			$mb_tabs_id    = '';
			$tablink_class = 'sucom-tablink';
			$tabset_class  = 'sucom-tabset';

			if ( ! empty( $metabox_id ) ) {

				$metabox_id    = '_' . trim( $metabox_id, '_' );	// Must start with an underscore.
				$mb_tabs_id    = $mb_tabs_class . $metabox_id;
				$mb_tabs_class .= ' ' . $mb_tabs_id;
			}

			$filter_name = SucomUtil::sanitize_hookname( 'wpsso_metabox_tabs_layout' . $metabox_id );

			$tabs_layout = empty( $args[ 'layout' ] ) ? 'horizontal' : $args[ 'layout' ];
			$tabs_layout = apply_filters( $filter_name, $tabs_layout );
			$tabs_layout = empty( $tabs_layout ) ? 'horizontal' : $tabs_layout;	// Allow the filter to return false.

			$mb_tabs_class = SucomUtil::sanitize_css_class( $mb_tabs_class . ' ' . $tabs_layout );
			$mb_tabs_id    = SucomUtil::sanitize_css_id( $mb_tabs_id );

			$metabox_html = "\n";
			$metabox_html .= '<div class="' . $mb_tabs_class . '"' . ( $mb_tabs_id ? ' id="' . $mb_tabs_id . '"' : '' ) . '>' . "\n";
			$metabox_html .= '<ul class="' . $mb_tabs_class . '">';

			/*
			 * Add the settings tab list.
			 */
			$tab_num = 0;

			foreach ( $tabs as $tab => $title_transl ) {

				$tab_num++;

				$href_key_class = $tabset_class . $metabox_id . '-tab_' . $tab;
				$icon_key_class = $tablink_class . ' ' . $tablink_class . $metabox_id . ' ' . $tablink_class . '-icon ' . $tablink_class . '-href_' . $tab;
				$link_key_class = $tablink_class . ' ' . $tablink_class . $metabox_id . ' ' . $tablink_class . '-text ' . $tablink_class . '-href_' . $tab;

				$metabox_html .= '<li' . "\n" . 'class="tab_space' . ( $tab_num === 1 ? ' start_tabs' : '' ) . '"></li>';
				$metabox_html .= '<li' . "\n" . 'class="' . $href_key_class . '">';
				$metabox_html .= '<a class="' . $icon_key_class . '" href="#' . $href_key_class . '"></a>';
				$metabox_html .= '<a class="' . $link_key_class . '" href="#' . $href_key_class . '">' . $title_transl . '</a>';
				$metabox_html .= '</li>';	// Do not add a newline.
			}

			$metabox_html .= '<li' . "\n" . 'class="tab_space end_tabs"></li>';
			$metabox_html .= '</ul><!-- .' . $mb_tabs_class . ' -->' . "\n\n";

			/*
			 * Add the settings table for each tab.
			 */
			foreach ( $tabs as $tab => $title_transl ) {

				$href_key_class = $tabset_class . $metabox_id . '-tab_' . $tab;

				$metabox_html .= $this->get_table( $table_rows[ $tab ], $href_key_class,
					( empty( $metabox_id ) ? '' : $tabset_class . $metabox_id ),
						$tabset_class, $title_transl );
			}

			$metabox_html .= '</div><!-- .' . $mb_tabs_class . ' -->' . "\n";

			if ( $doing_ajax ) {

				$metabox_html .= '<!-- adding tabs javascript for ajax call -->' . "\n";
				$metabox_html .= '<script>';
				$metabox_html .= 'sucomTabs( \'' . $metabox_id . '\', \'' . $default_tab . '\' );';
				$metabox_html .= '</script>' . "\n";

			} else {

				$metabox_html .= '<!-- adding tabs javascript for page load -->' . "\n";
				$metabox_html .= '<script>';
				$metabox_html .= 'jQuery( document ).on( \'ready\', function(){ ';
				$metabox_html .= 'sucomTabs( \'' . $metabox_id . '\', \'' . $default_tab . '\' );';
				$metabox_html .= '});';
				$metabox_html .= '</script>' . "\n";
			}

			return $metabox_html;
		}

		public function do_table( $table_rows, $href_key_class = '', $tabset_mb_class = '', $tabset_class = 'sucom-no_tabset', $title_transl = '' ) {

			echo $this->get_table( $table_rows, $href_key_class, $tabset_mb_class, $tabset_class, $title_transl );
		}

		public function get_table( $table_rows, $href_key_class = '', $tabset_mb_class = '', $tabset_class = 'sucom-no_tabset', $title_transl = '' ) {

			$metabox_html = '';

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				return $metabox_html;
			}

			$total_rows     = count( $table_rows );
			$count_rows     = 0;
			$hidden_opts    = 0;
			$hidden_rows    = 0;
			$user_show_opts = WpssoUser::show_opts();

			foreach ( $table_rows as $key => $row ) {

				if ( empty( $row ) ) {	// Just in case.

					continue;
				}

				/*
				 * Default row class and id attribute values.
				 */
				$tr = array(
					'class' => 'sucom-alt' .
						( $count_rows % 2 ) .
						( $count_rows === 0 ? ' first-row' : '' ) .
						( $count_rows === ( $total_rows - 1 ) ? ' last-row' : '' ),
					'id' => ( is_int( $key ) ? '' : 'tr_' . $key )
				);

				/*
				 * If we don't already have a table row tag, then add one.
				 */
				if ( strpos( $row, '<tr ' ) === false ) {

					$row = '<tr class="' . $tr[ 'class' ] . '"' . ( empty( $tr[ 'id' ] ) ? '' : ' id="' . $tr[ 'id' ] . '"' ) . '>' . $row;

				} else {

					foreach ( $tr as $att => $val ) {

						if ( empty( $tr[ $att ] ) ) {

							continue;
						}

						/*
						 * If we're here, then we have a table row tag already.
						 *
						 * Count the number of rows and options that are hidden.
						 */
						if ( $att === 'class' && ! empty( $user_show_opts ) ) {

							if ( $matched = preg_match( '/<tr [^>]*class="[^"]*hide(_row)?_in_' . $user_show_opts . '[" ]/', $row, $m ) > 0 ) {

								if ( ! isset( $m[ 1 ] ) ) {

									$hidden_opts += preg_match_all( '/(<th|<tr[^>]*><td)/', $row, $all_matches );
								}

								$hidden_rows += $matched;
							}
						}

						/*
						 * Add the attribute value.
						 */
						$row = preg_replace( '/(<tr [^>]*' . $att . '=")([^"]*)(")/', '$1$2 ' . $tr[ $att ] . '$3', $row, -1, $cnt );

						/*
						 * If one hasn't been added, then add both the attribute and its value.
						 */
						if ( $cnt < 1 ) {

							$row = preg_replace( '/(<tr )/', '$1' . $att . '="' . $tr[ $att ] . '" ', $row, -1, $cnt );
						}
					}
				}

				/*
				 * Add a closing table row tag if we don't already have one.
				 */
				if ( strpos( $row, '</tr>' ) === false ) {

					$row .= '</tr>' . "\n";
				}

				/*
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

			$div_class = ( empty( $user_show_opts ) ? '' : 'sucom-show_' . $user_show_opts ) .
				( empty( $tabset_class ) ? '' : ' ' . $tabset_class ) .
				( empty( $tabset_mb_class ) ? '' : ' ' . $tabset_mb_class ) .
				( empty( $href_key_class ) ? '' : ' ' . $href_key_class );

			$table_class = 'sucom-settings ' . $this->p->id .
				( empty( $href_key_class ) ? '' : ' ' . $href_key_class ) .
				( $hidden_rows > 0 && $hidden_rows === $count_rows ? ' hide_in_' . $user_show_opts : '' );

			$metabox_html .= '<div class="' . $div_class . '">' . "\n";
			$metabox_html .= $title_transl ? '<h3 class="sucom-metabox-tab_title">' . $title_transl . '</h3>' . "\n" : '';
			$metabox_html .= '<table class="' . $table_class . '">' . "\n";

			foreach ( $table_rows as $row ) {

				$metabox_html .= $row;
			}

			$metabox_html .= '</table><!-- .' . $table_class . ' --> ' . "\n";
			$metabox_html .= '</div><!-- .' . $div_class . ' -->' . "\n\n";

			$user_show_opts_label = $this->p->cf[ 'form' ][ 'show_options' ][ $user_show_opts ];

			if ( $hidden_opts > 0 ) {

				$metabox_html .= '<div class="hidden_opts_msg ' . $tabset_class . '-msg ' . $tabset_mb_class . '-msg ' . $href_key_class . '-msg">' .
					sprintf( _x( '%1$d additional options not shown in "%2$s" view', 'option comment', 'wpsso' ),
						$hidden_opts, _x( $user_show_opts_label, 'option value', 'wpsso' ) ) .
					' (<a href="javascript:void(0);" onClick="sucomViewUnhideRows( \'' . $href_key_class . '\', \'' . $user_show_opts .
						'\' );">' . _x( 'show these options now', 'option comment', 'wpsso' ) . '</a>)</div>' . "\n";

			} elseif ( $hidden_rows > 0 ) {

				$metabox_html .= '<div class="hidden_opts_msg ' . $tabset_class . '-msg ' . $tabset_mb_class . '-msg ' . $href_key_class . '-msg">' .
					sprintf( _x( '%1$d additional rows not shown in "%2$s" view', 'option comment', 'wpsso' ),
						$hidden_rows, _x( $user_show_opts_label, 'option value', 'wpsso' ) ) .
					' (<a href="javascript:void(0);" onClick="sucomViewUnhideRows( \'' . $href_key_class . '\', \'' . $user_show_opts .
						'\', \'hide_row_in\' );">' . _x( 'show these rows now', 'option comment', 'wpsso' ) . '</a>)</div>' . "\n";
			}

			return $metabox_html;
		}
	}
}
