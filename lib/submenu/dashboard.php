<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuDashboard' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuDashboard extends WpssoAdmin {

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

			$this->p->util->add_plugin_filters( $this, array( 'form_button_rows' => 1 ), PHP_INT_MAX );

			$this->p->util->add_plugin_actions( $this, array( 'form_content_metaboxes_dashboard' => 1 ) );
		}

		public function filter_form_button_rows( $form_button_rows ) {

			/*
			 * Remove all action buttons from this settings page.
			 */
			return array();
		}

		public function action_form_content_metaboxes_dashboard( $pagehook ) {

			/*
			 * This settings page does not have any "normal" metaboxes, so hide that container and set the container
			 * height to 0 to prevent drag-and-drop in that area, just in case.
			 */
			echo '<style type="text/css">div#' . $pagehook . ' div#normal-sortables { display:none; height:0; min-height:0; }</style>';
			echo '<div id="metabox_col_wrap">' . "\n";

			$max_cols = 2;

			foreach ( range( 1, $max_cols ) as $metabox_col ) {

				$class_last = $metabox_col === $max_cols ? ' metabox_col_last' : '';

				/*
				 * Note that CSS id values must use underscores, instead of hyphens, to sort the metaboxes.
				 */
				echo '<div id="metabox_col_' . $metabox_col . '" class="metabox_col max_cols_' . $max_cols . $class_last . '">' . "\n";

				do_meta_boxes( $pagehook, 'metabox_col_' . $metabox_col, null );

				echo '</div><!-- #metabox_col_' . $metabox_col . ' -->' . "\n";
			}

			echo '</div><!-- #metabox_col_wrap -->' . "\n";
			echo '<div style="clear:both;"></div>' . "\n";
		}

		protected function add_meta_boxes( $callback_args = array() ) {

			$metaboxes = array(
				array(
					'help_support' => _x( 'Get Help and Support', 'metabox title', 'wpsso' ),
					'version_info' => _x( 'Version Information', 'metabox title', 'wpsso' ),
					'cache_status' => wp_using_ext_object_cache() ? false : _x( 'Cache Status', 'metabox title', 'wpsso' ),
				),
				array(
					'features_status' => _x( 'Features Status', 'metabox title', 'wpsso' ),
				),
			);

			foreach ( $metaboxes as $num => $metabox_info ) {

				foreach ( $metabox_info as $metabox_id => $metabox_title ) {

					if ( $metabox_title ) {

						$metabox_col     = $num + 1;
						$metabox_screen  = $this->pagehook;
						$metabox_context = 'metabox_col_' . $metabox_col;	// Use underscores (not hyphens) to order metaboxes.
						$metabox_prio    = 'default';

						$callback_args[ 'page_id' ]       = $this->menu_id;
						$callback_args[ 'metabox_id' ]    = $metabox_id;
						$callback_args[ 'metabox_title' ] = $metabox_title;
						$callback_args[ 'network' ]       = 'sitesubmenu' === $this->menu_lib ? true : false;

						$method_name = method_exists( $this, 'show_metabox_' . $metabox_id ) ?
							'show_metabox_' . $metabox_id : 'show_metabox_table';

						add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title, array( $this, $method_name ),
							$metabox_screen, $metabox_context, $metabox_prio, $callback_args );

						add_filter( 'postbox_classes_' . $this->pagehook . '_' . $this->pagehook . '_' .  $metabox_id,
							array( $this, 'add_class_postbox_menu_id' ) );
					}
				}
			}
		}
	}
}
