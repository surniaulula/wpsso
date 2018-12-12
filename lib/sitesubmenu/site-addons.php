<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSiteSubmenuSiteAddons' ) && class_exists( 'WpssoAdmin' ) ) {

	/**
	 * Please note that this settings page also requires enqueuing special scripts and styles
	 * for the plugin details / install thickbox link. See the WpssoScript and WpssoStyle
	 * classes for more info.
	 */
	class WpssoSiteSubmenuSiteAddons extends WpssoAdmin {

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
				'form_button_rows' => 1,
			) );
		}

		/**
		 * Remove all submit / action buttons from the Add-ons page.
		 */
		public function filter_form_button_rows( $form_button_rows ) {

			return array();
		}

		protected function set_form_object( $menu_ext ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'setting site form object for '.$menu_ext );
			}

			$def_site_opts = $this->p->opt->get_site_defaults();

			$this->form = new SucomForm( $this->p, WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $def_site_opts, $menu_ext );
		}

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$short_name = $this->p->cf[ 'plugin' ][ $this->p->lca ][ 'short' ];

			$metabox_id      = 'addons';
			$metabox_title   = sprintf( _x( 'Add-ons for %s', 'metabox title', 'wpsso' ), $short_name );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_addons' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );

			/**
			 * Add a class to set a minimum width for the network postboxes.
			 */
			add_filter( 'postbox_classes_' . $this->pagehook . '_' . $this->pagehook . '_addons',
				array( $this, 'add_class_postbox_network' ) );
		}

		public function show_metabox_addons() {

			$this->addons_metabox_content( $network = true );
		}
	}
}
