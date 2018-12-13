<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
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

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows'  => 1,
			), SucomUtil::get_min_int() );
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

			if ( ! $using_external_cache && $this->p->options['plugin_shortener'] !== 'none' ) {

				$settings_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_cache',
					_x( 'Refresh Short URLs on Clear Cache', 'option label', 'wpsso' ) );

				echo '<p><small>[*] ';

				if ( empty( $this->p->options[ 'plugin_clear_short_urls' ] ) ) {
					echo sprintf( __( '%1$s option is unchecked - shortened URLs cache will be preserved.', 'wpsso' ), $settings_page_link );
				} else {
					echo sprintf( __( '%1$s option is checked - shortened URLs cache will be cleared.', 'wpsso' ), $settings_page_link );
				}

				echo '</small></p>';
			}

			echo '</div><!-- #wpsso_tools -->' . "\n";
		}

		public function filter_form_button_rows( $form_button_rows ) {

			$using_external_cache = wp_using_ext_object_cache();

			if ( is_multisite() ) {
				$clear_label_transl = sprintf( _x( 'Clear All Caches for Site ID %d',
					'submit button', 'wpsso' ), get_current_blog_id() );
				$export_label_transl = sprintf( _x( 'Export Settings for Site ID %d',
					'submit button', 'wpsso' ), get_current_blog_id() );
				$import_label_transl = sprintf( _x( 'Import Settings for Site ID %d',
					'submit button', 'wpsso' ), get_current_blog_id() );
			} else {
				$clear_label_transl = _x( 'Clear All Caches', 'submit button', 'wpsso' );
				$export_label_transl = _x( 'Export Plugin and Add-on Settings', 'submit button', 'wpsso' );
				$import_label_transl = _x( 'Import Plugin and Add-on Settings', 'submit button', 'wpsso' );
			}

			if ( ! $using_external_cache && $this->p->options['plugin_shortener'] !== 'none' ) {
				$clear_label_transl .= ' [*]';
			}

			$form_button_rows = array(
				array(
					'clear_all_cache'                => $clear_label_transl,
					'clear_all_cache_and_short_urls' => null,
				),
				array(
					'export_plugin_settings_json' => $export_label_transl,
					'import_plugin_settings_json' => array(
						'html' => '
							<form enctype="multipart/form-data" action="' . $this->p->util->get_admin_url() . '" method="post">' .
							wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME ) . '
							<input type="hidden" name="' .$this->p->lca . '-action" value="import_plugin_settings_json" />
							<input type="submit" class="button-secondary button-alt" value="' . $import_label_transl . '"
								style="display:inline-block;" />
							<input type="file" name="file" accept="application/x-gzip" />
							</form>
						',
					),
				),
				array(
					// 'Reload Default Image Sizes' button added by the WpssoSettingImageDimensions class.
				),
				array(
					'reset_user_dismissed_notices' => _x( 'Reset User Dismissed Notices', 'submit button', 'wpsso' ),
					'reset_user_metabox_layout'    => _x( 'Reset User Metabox Layout', 'submit button', 'wpsso' ),
				),
			);

			if ( ! $using_external_cache && $this->p->options['plugin_shortener'] !== 'none' ) {
				if ( empty( $this->p->options[ 'plugin_clear_short_urls' ] ) ) {
					$form_button_rows[ 0 ][ 'clear_all_cache_and_short_urls' ] = _x( 'Clear All Caches and Short URLs',
						'submit button', 'wpsso' );
				}

			}

			return $form_button_rows;
		}
	}
}
