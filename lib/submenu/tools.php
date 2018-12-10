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
				'submit_button_rows'  => 1,
			) );

			$this->p->util->add_plugin_actions( $this, array(
				'form_content_footer' => 1,
			) );
		}

		public function filter_submit_button_rows( $submit_button_rows ) {

			$using_external_cache = wp_using_ext_object_cache();

			if ( is_multisite() ) {
				$clear_label_transl = sprintf( _x( 'Clear All Caches for Site %d',
					'submit button', 'wpsso' ), get_current_blog_id() );
			} else {
				$clear_label_transl = _x( 'Clear All Caches', 'submit button', 'wpsso' );
			}

			if ( ! $using_external_cache && $this->p->options['plugin_shortener'] !== 'none' ) {
				$clear_label_transl .= ' [*]';
			}

			$submit_button_rows = array(
				array(
					'clear_all_cache' => $clear_label_transl,
				),
				array(
					'clear_metabox_prefs'     => _x( 'Reset Metabox Layout', 'submit button', 'wpsso' ),
					'clear_dismissed_notices' => _x( 'Reset Dismissed Notices', 'submit button', 'wpsso' ),
				),
			);

			if ( ! $using_external_cache && $this->p->options['plugin_shortener'] !== 'none' ) {
				if ( empty( $this->p->options[ 'plugin_clear_short_urls' ] ) ) {
					$submit_button_rows[ 0 ][ 'clear_all_cache_and_short_urls' ] = _x( 'Clear All Caches and Short URLs', 'submit button', 'wpsso' );
				}

			}

			return $submit_button_rows;
		}

		public function action_form_content_footer( $pagehook ) {

			$using_external_cache = wp_using_ext_object_cache();

			if ( ! $using_external_cache && $this->p->options['plugin_shortener'] !== 'none' ) {

				$settings_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_cache',
					_x( 'Refresh Short URLs on Clear Cache', 'option label', 'wpsso' ) );

				echo '<p><small>[*] ';

				if ( empty( $this->p->options[ 'plugin_clear_short_urls' ] ) ) {
					echo sprintf( __( '%1$s option is unchecked - shortened URL cache will be preserved.', 'wpsso' ), $settings_page_link );
				} else {
					echo sprintf( __( '%1$s option is checked - shortened URL cache will be cleared.', 'wpsso' ), $settings_page_link );
				}

				echo '</small></p>';
			}
		}
	}
}
