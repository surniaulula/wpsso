<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuAddons' ) && class_exists( 'WpssoAdmin' ) ) {

	/*
	 * This settings page requires enqueuing special scripts and styles for the plugin details / install thickbox link.
	 *
	 * See the WpssoScript and WpssoStyle classes for more info.
	 */
	class WpssoSubmenuAddons extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;

			$this->menu_metaboxes = array(
				'addons' => _x( 'Free Plugin Add-ons', 'metabox title', 'wpsso' ),
			);
		}

		/*
		 * Remove all action buttons.
		 */
		protected function add_form_buttons( &$form_button_rows ) {
			
			$form_button_rows = array();
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_addons( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$ext_sorted = WpssoConfig::get_ext_sorted();

			unset( $ext_sorted[ $this->p->id ] );

			$tabindex  = 0;
			$ext_num   = 0;
			$ext_total = count( $ext_sorted );
			$pkg_info  = $this->p->util->get_pkg_info();	// Uses a local cache.
			$charset   = get_bloginfo( $show = 'charset', $filter = 'raw' );
			$icon_px   = 100;

			echo '<table class="sucom-settings wpsso addons-metabox" style="padding-bottom:10px">' . "\n";

			foreach ( $ext_sorted as $ext => $info ) {

				if ( empty( $info[ 'name' ] ) ) {

					continue;
				}

				$ext_num++;

				$ext_links       = $this->get_ext_action_links( $ext, $info, $tabindex );
				$ext_name_html   = '<h4>' .
					htmlentities( $pkg_info[ $ext ][ 'name' ], ENT_QUOTES, $charset, $double_encode = false ) . ' (' .
					htmlentities( $pkg_info[ $ext ][ 'short' ], ENT_QUOTES, $charset, $double_encode = false ) . ')' .
					'</h4>';
				$ext_desc_transl = _x( $info[ 'desc' ], 'plugin description', 'wpsso' );
				$ext_desc_html   = '<p>' . htmlentities( $ext_desc_transl, ENT_QUOTES, $charset, $double_encode = false ) . '</p>';

				$table_rows = array();

				/*
				 * Plugin name, description and links.
				 */
				$table_rows[ 'plugin_name' ] = '<td class="ext-info-plugin-name" id="ext-info-plugin-name-' . $ext . '">' .
					'<a class="ext-anchor" id="' . $ext . '"></a>' . $ext_name_html . $ext_desc_html .
					( empty( $ext_links ) ? '' : '<div class="row-actions visible">' . implode( $glue = ' | ', $ext_links ) . '</div>' ) .
					'</td>';

				/*
				 * Plugin separator.
				 */
				if ( $ext_num < $ext_total ) {

					$table_rows[ 'dotted_line' ] = '<td class="ext-info-plugin-separator"></td>';

				} else {

					$table_rows[] = '<td></td>';
				}

				/*
				 * Show the plugin icon and table rows.
				 */
				foreach ( $table_rows as $key => $row ) {

					echo '<tr>';

					if ( $key === 'plugin_name' ) {

						$span_rows   = count( $table_rows );
						$icon_col_px = $icon_px + 30;
						$icon_style  = 'width:' . $icon_col_px . 'px; min-width:' . $icon_col_px . 'px; max-width:' . $icon_col_px . 'px;';

						echo '<td class="ext-info-plugin-icon" id="ext-info-plugin-icon-' . $ext . '" ' .
							'rowspan="' . $span_rows . '" style="' . $icon_style . '" >';
						echo $this->get_ext_img_icon( $ext, $icon_px );
						echo '</td>';
					}

					echo $row . '</tr>' . "\n";
				}
			}

			echo '</table>' . "\n";
		}
	}
}
