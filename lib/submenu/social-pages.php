<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuSocialPages' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuSocialPages extends WpssoAdmin {

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

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$this->maybe_show_language_notice();

			$metabox_id      = 'social_accounts';
			$metabox_title   = _x( 'Social Pages and Accounts', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'page_id'    => SucomUtil::sanitize_hookname( $this->menu_id ),
				'metabox_id' => $metabox_id,
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title, array( $this, 'show_metabox_table' ),
				$metabox_screen, $metabox_context, $metabox_prio, $callback_args );
		}

		protected function get_table_rows( $page_id, $metabox_id ) {

			$table_rows = array();

			switch ( $page_id . '-' . $metabox_id ) {

				case 'social_pages-social_accounts':

					foreach ( WpssoConfig::get_social_accounts() as $social_key => $label ) {

						$table_rows[ $social_key ] = '' . 
						$this->form->get_th_html_locale( _x( $label, 'option value', 'wpsso' ), $css_class = 'nowrap', $social_key ) . 
						'<td>' . $this->form->get_input_locale( $social_key, strpos( $social_key, '_url' ) ? 'wide' : '' ) . '</td>';
					}

					break;
			}

			return $table_rows;
		}
	}
}
