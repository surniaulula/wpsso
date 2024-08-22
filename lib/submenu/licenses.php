<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuLicenses' ) && class_exists( 'WpssoAdmin' ) ) {

	/*
	 * Please note that this settings page also requires enqueuing special scripts and styles for the plugin details / install
	 * thickbox link. See the WpssoScript and WpssoStyle classes for more info.
	 */
	class WpssoSubmenuLicenses extends WpssoAdmin {

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
				'licenses' => _x( 'Plugin and Add-on Licenses', 'metabox title', 'wpsso' ),
			);
		}

		/*
		 * Remove the "Change to View" button from this settings page.
		 */
		protected function add_form_buttons_change_show_options( &$form_button_rows ) {
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_licenses( $obj, $mb ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$args    = isset( $mb[ 'args' ] ) ? $mb[ 'args' ] : array();
			$network = isset( $args[ 'network' ] ) ? $args[ 'network' ] : false;

			$ext_sorted = WpssoConfig::get_ext_sorted();

			foreach ( $ext_sorted as $ext => $info ) {

				if ( empty( $info[ 'update_auth' ] ) ) {	// Only show plugins with Premium packages.

					unset( $ext_sorted[ $ext ] );
				}
			}

			$tabindex  = 0;
			$ext_num   = 0;
			$ext_total = count( $ext_sorted );
			$pkg_info  = $this->p->util->get_pkg_info();	// Uses a local cache.
			$charset   = get_bloginfo( $show = 'charset', $filter = 'raw' );
			$icon_px   = 100;

			echo '<table class="sucom-settings wpsso licenses-metabox" style="padding-bottom:10px">' . "\n";
			echo '<tr><td colspan="3">' . $this->p->msgs->get( 'info-plugin-tid' . ( $network ? '-network' : '' ) ) . '</td></tr>' . "\n";

			foreach ( $ext_sorted as $ext => $info ) {

				$ext_num++;

				$ext_links     = $this->get_ext_action_links( $ext, $info, $tabindex );
				$ext_name_html = '<h4>' . htmlentities( $pkg_info[ $ext ][ 'name_pro' ], ENT_QUOTES, $charset, $double_encode = false ) . '</h4>';
				$placeholder   = strtoupper( $ext . '-PP-0000000000000000' );
				$blog_id       = get_current_blog_id();
				$home_url      = SucomUtilWP::raw_get_home_url();
				$home_path     = preg_replace( '/^[a-z]+:\/\//i', '', $home_url );	// Remove the protocol prefix.
				$table_rows    = array();

				$admin_url = is_multisite() ? network_admin_url( 'site-settings.php?id=' . $blog_id ) : get_admin_url( $blog_id, 'options-general.php' );
				$edit_link = '(<a href="' . $admin_url . '">' . __( 'Edit', 'wpsso' ) . '</a>)';

				/*
				 * Plugin name, description, and action links.
				 */
				$table_rows[ 'plugin_name' ] = '<td colspan="2" class="ext-info-plugin-name" id="ext-info-plugin-name-' . $ext . '">' .
					'<a class="ext-anchor" id="' . $ext . '"></a>' . $ext_name_html .
					( empty( $ext_links ) ? '' : '<div class="row-actions visible">' . implode( $glue = ' | ', $ext_links ) . '</div>' ) .
					'</td>';

				/*
				 * Authentication ID.
				 */
				$table_rows[ 'plugin_tid' ] = '' .
					$this->form->get_th_html( sprintf( _x( '%s Authentication ID', 'option label', 'wpsso' ), $info[ 'short' ] ), 'medium nowrap' ) .
					'<td width="100%">' . $this->form->get_input( 'plugin_' . $ext . '_tid', $css_class = 'tid mono', $css_id = '', $len = 0,
						$placeholder, $is_disabled = false, ++$tabindex ) . '</td>';

				$table_rows[ 'site_use' ] = self::get_option_site_use( 'plugin_' . $ext . '_tid', $this->form, $network );

				/*
				 * License information.
				 */
				$table_rows[ 'home_url' ] = '' .
					'<th class="medium nowrap">' . _x( 'Current Site Address', 'option label', 'wpsso' ) . '</th>' .
					'<td width="100%">' . $home_path . ' ' . $edit_link . '</td>';

				if ( ! empty( $this->p->options[ 'plugin_' . $ext . '_tid' ] ) && class_exists( 'SucomUpdate' ) ) {

					$show_update_opts = array(
						'exp_date' => _x( 'Support and Updates Expire', 'option label', 'wpsso' ),
						'qty_used' => _x( 'License Information', 'option label', 'wpsso' ),
					);

					foreach ( $show_update_opts as $key => $label ) {

						$val = SucomUpdate::get_option( $ext, $key );

						if ( empty( $val ) ) {	// Add an empty row for empty values.

							$val = _x( 'Not available', 'option value', 'wpsso' );

						} elseif ( 'exp_date' === $key ) {

							if ( '0000-00-00 00:00:00' === $val ) {

								$val = _x( 'Never (Nontransferable Lifetime License)', 'option value', 'wpsso' );
							}

						} elseif ( 'qty_used' === $key ) {

							$qty_reg   = SucomUpdate::get_option( $ext, 'qty_reg' );
							$qty_total = SucomUpdate::get_option( $ext, 'qty_total' );

							if ( null !== $qty_reg && null !== $qty_total ) {	// Just in case.

								$val = sprintf( __( '%d of %d site addresses registered', 'wpsso' ), $qty_reg, $qty_total );

							} else $val = sprintf( __( '%s site addresses registered', 'wpsso' ), $val );

							if ( ! empty( $info[ 'url' ][ 'info' ] ) ) {

								$info_url = add_query_arg( array(
									'tid'            => $this->p->options[ 'plugin_' . $ext . '_tid' ],
									'user_direction' => is_rtl() ? 'rtl' : 'ltr',
									'user_locale'    => SucomUtilWP::get_locale(),
									'TB_iframe'      => 'true',
									'width'          => $this->p->cf[ 'wp' ][ 'tb_iframe' ][ 'width' ],
									'height'         => $this->p->cf[ 'wp' ][ 'tb_iframe' ][ 'height' ],
								), $info[ 'url' ][ 'purchase' ] . 'info/' );

								$val = '<a href="' . $info_url . '" class="thickbox">' . $val . '</a>';
							}
						}

						$table_rows[ $key ] = '<th class="medium nowrap">' . $label . '</th><td width="100%">' . $val . '</td>';
					}

				} else $table_rows[] = '<th class="medium nowrap">&nbsp;</th><td width="100%">&nbsp;</td>';

				/*
				 * Plugin separator.
				 */
				if ( $ext_num < $ext_total ) {

					$table_rows[ 'dotted_line' ] = '<td colspan="2" class="ext-info-plugin-separator"></td>';

				} else $table_rows[] = '<td></td>';

				/*
				 * Show the plugin icon and table rows.
				 */
				foreach ( $table_rows as $key => $row ) {

					if ( ! empty( $row ) ) {

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
			}

			echo '</table>' . "\n";
		}
	}
}
