<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuSocialAccounts' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuSocialAccounts extends WpssoAdmin {

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
			$metabox_title   = _x( 'WebSite Social Pages and Accounts', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_social_accounts' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );
		}

		public function show_metabox_social_accounts() {

			$metabox_id = $this->menu_id;

			$filter_name = SucomUtil::sanitize_hookname( $this->p->lca . '_' . $metabox_id . '_general_rows' );

			$table_rows = array_merge(
				$this->get_table_rows( $metabox_id, 'general' ),
				apply_filters( $filter_name, array(), $this->form )
			);

			$this->p->util->metabox->do_table( $table_rows, 'metabox-' . $metabox_id );
		}

		protected function get_table_rows( $metabox_id, $tab_key ) {

			$table_rows = array();

			switch ( $metabox_id . '-' . $tab_key ) {

				case 'social-accounts-general':

					$social_accounts = apply_filters( $this->p->lca . '_social_accounts', $this->p->cf[ 'form' ][ 'social_accounts' ] );

					asort( $social_accounts );	// Sort by label (after translation) and maintain key association.

					foreach ( $social_accounts as $social_key => $label ) {

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
