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

		public $using_db_cache = true;

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;

			$this->using_db_cache = wp_using_ext_object_cache() ? false : true;
		}

		/**
		 * Called by WpssoAdmin->load_setting_page() after the 'wpsso-action' query is handled.
		 *
		 * Add settings page filter and action hooks.
		 */
		protected function add_plugin_hooks() {

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows'  => 1,
			) );
		}

		protected function show_post_body_setting_form() {

			$role_label = _x( 'Person', 'user role', 'wpsso' );

			echo '<div id="tools-content">' . "\n";

			echo $this->get_form_buttons();

			/**
			 * Add a note about shortened URLs being preserved or cleared, depending on the 'plugin_clear_short_urls'
			 * option value.
			 */
			if ( $this->using_db_cache ) {
			
				if ( $this->p->options[ 'plugin_shortener' ] !== 'none' ) {

					$settings_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_cache',
						_x( 'Refresh Short URLs on Clear Cache', 'option label', 'wpsso' ) );

					echo '<p class="status-msg smaller left">';
					
					echo '* ';

					if ( empty( $this->p->options[ 'plugin_clear_short_urls' ] ) ) {
						echo sprintf( __( '%1$s option is unchecked - shortened URLs cache will be preserved.', 'wpsso' ), $settings_page_link );
					} else {
						echo sprintf( __( '%1$s option is checked - shortened URLs cache will be cleared.', 'wpsso' ), $settings_page_link );
					}

					echo '</p>';
				}
			}

			echo '<p class="status-msg smaller left">';
			echo '** ';
			echo sprintf( __( 'Members of the %s role may be hand selected for certain Schema properties (content creators are administrators, editors, authors or contributors).', 'wpsso' ), $role_label );
			echo '</p>';

			echo '</div><!-- #tools-content -->' . "\n";
		}

		public function filter_form_button_rows( $form_button_rows ) {

			$role_label = _x( 'Person', 'user role', 'wpsso' );

			$change_show_next_key     = SucomUtil::next_key( WpssoUser::show_opts(), $this->p->cf[ 'form' ][ 'show_options' ] );
			$change_show_name_transl  = _x( $this->p->cf[ 'form' ][ 'show_options' ][ $change_show_next_key ], 'option value', 'wpsso' );
			$change_show_label_transl = sprintf( _x( 'Change to "%s" View', 'submit button', 'wpsso' ), $change_show_name_transl );

			$clear_cache_label_transl       = _x( 'Clear All Caches', 'submit button', 'wpsso' );
			$clear_short_label_transl       = _x( 'Clear All Caches and Short URLs', 'submit button', 'wpsso' );
			$clear_cache_dir_label_transl   = _x( 'Clear All Files in Cache Folder', 'submit button', 'wpsso' );
			$clear_transients_label_transl  = _x( 'Clear All Database Transients', 'submit button', 'wpsso' );
			$refresh_cache_label_transl     = _x( 'Refresh Transient Cache', 'submit button', 'wpsso' );
			$add_persons_label_transl       = sprintf( _x( 'Add %s Role to Content Creators', 'submit button', 'wpsso' ), $role_label );
			$remove_persons_label_transl    = sprintf( _x( 'Remove %s Role from All Users', 'submit button', 'wpsso' ), $role_label );
			$export_settings_label_transl   = _x( 'Export Plugin and Add-on Settings', 'submit button', 'wpsso' );
			$import_settings_label_transl   = _x( 'Import Plugin and Add-on Settings', 'submit button', 'wpsso' );

			/**
			 * Add a note about shortened URLs being preserved or cleared, depending on the 'plugin_clear_short_urls'
			 * option value.
			 */
			if ( $this->using_db_cache ) {
			
				if ( $this->p->options[ 'plugin_shortener' ] !== 'none' ) {

					$clear_cache_label_transl .= ' *';
				}
			}

			$add_persons_label_transl .= ' **';

			$form_button_rows = array(
				array(
					'clear_cache'                => $clear_cache_label_transl,	// Clear All Caches.
					'clear_cache_and_short_urls' => null,				// Clear All Caches and Short URLs.
					'clear_cache_dir'            => $clear_cache_dir_label_transl,	// Clear All Cache Files.
					'clear_db_transients'        => null,				// Clear All Database Transients.
					'refresh_cache'              => $refresh_cache_label_transl,	// Refresh Transient Cache.
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
					'add_persons'    => $add_persons_label_transl,
					'remove_persons' => $remove_persons_label_transl,
					// 'Reload Default Image Sizes' button added by the WpssoSubmenuImageSizes class.
				),
				array(
					'change_show_options&show-opts=' . $change_show_next_key => $change_show_label_transl,
					'reset_user_dismissed_notices' => _x( 'Reset Dismissed Notices', 'submit button', 'wpsso' ),
					'reset_user_metabox_layout'    => _x( 'Reset Metabox Layout', 'submit button', 'wpsso' ),
				),
			);

			if ( $this->using_db_cache ) {

				/**
				 * Clear All Database Transients.
				 */
				$form_button_rows[ 0 ][ 'clear_db_transients' ] = $clear_transients_label_transl;

				/**
				 * Clear All Caches and Short URLs.
				 */
				if ( $this->p->options[ 'plugin_shortener' ] !== 'none' ) {

					if ( empty( $this->p->options[ 'plugin_clear_short_urls' ] ) ) {

						$form_button_rows[ 0 ][ 'clear_cache_and_short_urls' ] = $clear_short_label_transl;
					}
				}
			}

			return $form_button_rows;
		}
	}
}
