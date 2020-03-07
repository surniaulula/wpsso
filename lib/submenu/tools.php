<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuTools' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuTools extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;
		}

		protected function add_plugin_hooks() {

			$min_int = SucomUtil::get_min_int();

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows'  => 1,
			), $min_int );
		}

		protected function show_form_content() {

			/**
			 * Add a form to support side metabox open / close functions.
			 */
			$menu_hookname = SucomUtil::sanitize_hookname( $this->menu_id );

			echo '<form name="' . $this->p->lca . '" ' .
				'id="' . $this->p->lca . '_setting_form_' . $menu_hookname . '" ' .
				'action="options.php" method="post">' . "\n";

			settings_fields( $this->p->lca . '_setting' );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

			echo '</form>', "\n";

			echo '<div id="tools-content">' . "\n";

			echo $this->get_form_buttons();

			$using_external_cache = wp_using_ext_object_cache();

			if ( ! $using_external_cache && $this->p->options[ 'plugin_shortener' ] !== 'none' ) {

				$settings_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_cache',
					_x( 'Refresh Short URLs on Clear Cache', 'option label', 'wpsso' ) );

				echo '<p class="status-msg smaller left">* ';

				if ( empty( $this->p->options[ 'plugin_clear_short_urls' ] ) ) {
					echo sprintf( __( '%1$s option is unchecked - shortened URLs cache will be preserved.', 'wpsso' ), $settings_page_link );
				} else {
					echo sprintf( __( '%1$s option is checked - shortened URLs cache will be cleared.', 'wpsso' ), $settings_page_link );
				}

				echo '</p>';
			}

			echo '</div><!-- #wpsso_tools -->' . "\n";
		}

		public function filter_form_button_rows( $form_button_rows ) {

			$change_show_next_key     = SucomUtil::next_key( WpssoUser::show_opts(), $this->p->cf[ 'form' ][ 'show_options' ] );
			$change_show_name_transl  = _x( $this->p->cf[ 'form' ][ 'show_options' ][ $change_show_next_key ], 'option value', 'wpsso' );
			$change_show_label_transl = sprintf( _x( 'Change to "%s" View', 'submit button', 'wpsso' ), $change_show_name_transl );

			$using_external_cache = wp_using_ext_object_cache();

			$clear_cache_label_transl        = _x( 'Clear All Caches', 'submit button', 'wpsso' );
			$clear_short_label_transl        = _x( 'Clear All Caches and Short URLs', 'submit button', 'wpsso' );
			$delete_cache_files_label_transl = _x( 'Delete All Files in Cache Folder', 'submit button', 'wpsso' );
			$delete_transients_label_transl  = _x( 'Delete All Database Transients', 'submit button', 'wpsso' );
			$refresh_cache_label_transl      = _x( 'Refresh Transient Cache', 'submit button', 'wpsso' );
			$export_settings_label_transl    = _x( 'Export Plugin and Add-on Settings', 'submit button', 'wpsso' );
			$import_settings_label_transl    = _x( 'Import Plugin and Add-on Settings', 'submit button', 'wpsso' );

			if ( ! $using_external_cache && $this->p->options[ 'plugin_shortener' ] !== 'none' ) {
				$clear_cache_label_transl .= ' *';
			}

			$form_button_rows = array(
				array(
					'clear_all_cache'                => $clear_cache_label_transl,		// Clear All Caches.
					'clear_all_cache_and_short_urls' => null,				// Clear All Caches and Short URLs.
					'delete_all_cache_files'         => $delete_cache_files_label_transl,	// Delete All Cache Files.
					'delete_all_db_transients'       => null,				// Delete All Database Transients.
					'refresh_all_cache'              => $refresh_cache_label_transl,	// Refresh Transient Cache.
				),
				array(
					'export_plugin_settings_json' => $export_settings_label_transl,
					'import_plugin_settings_json' => array(
						'html' => '
							<form enctype="multipart/form-data" action="' . $this->p->util->get_admin_url() . '" method="post">' .
							wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME ) . '
							<input type="hidden" name="' .$this->p->lca . '-action" value="import_plugin_settings_json" />
							<input type="submit" class="button-secondary button-alt" value="' . $import_settings_label_transl . '"
								style="display:inline-block;" />
							<input type="file" name="file" accept="application/x-gzip" />
							</form>
						',
					),
				),
				array(
					// 'Reload Default Image Sizes' button added by the WpssoSubmenuImageSizes class.
				),
				array(
					'change_show_options&show-opts=' . $change_show_next_key => $change_show_label_transl,
					'reset_user_dismissed_notices' => _x( 'Reset User Dismissed Notices', 'submit button', 'wpsso' ),
					'reset_user_metabox_layout'    => _x( 'Reset User Metabox Layout', 'submit button', 'wpsso' ),
				),
			);

			if ( ! $using_external_cache ) {

				$form_button_rows[ 0 ][ 'delete_all_db_transients' ] = $delete_transients_label_transl;

				if ( $this->p->options[ 'plugin_shortener' ] !== 'none' ) {

					if ( empty( $this->p->options[ 'plugin_clear_short_urls' ] ) ) {

						$form_button_rows[ 0 ][ 'clear_all_cache_and_short_urls' ] = $clear_short_label_transl;
					}
				}
			}

			return $form_button_rows;
		}
	}
}
