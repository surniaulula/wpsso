<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuDocumentTypes' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuDocumentTypes extends WpssoAdmin {

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

			/**
			 * Open Graph Types metabox.
			 */
			$metabox_id      = 'og_types';
			$metabox_title   = _x( 'Open Graph Types', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args = array(
				'page_id'    => SucomUtil::sanitize_hookname( $this->menu_id ),
				'metabox_id' => $metabox_id,
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title, array( $this, 'show_metabox_table' ),
				$metabox_screen, $metabox_context, $metabox_prio, $callback_args );

			/**
			 * Schema Types metabox.
			 */
			$metabox_id      = 'schema_types';
			$metabox_title   = _x( 'Schema Types', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(
				'page_id'    => SucomUtil::sanitize_hookname( $this->menu_id ),
				'metabox_id' => $metabox_id,
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title, array( $this, 'show_metabox_table' ),
				$metabox_screen, $metabox_context, $metabox_prio, $callback_args );
		}

		protected function get_table_rows( $page_id, $metabox_id ) {

			$table_rows = array();

			switch ( $page_id . '-' . $metabox_id ) {

				/**
				 * Document Types metabox.
				 */
				case 'document_types-og_types':

					$this->add_og_types_table_rows( $table_rows, $this->form );

					break;

				case 'document_types-schema_types':

					$this->add_schema_item_types_table_rows( $table_rows, $this->form );

					break;
			}

			return $table_rows;
		}
	}
}
