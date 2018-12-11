<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSubmenuDashboard' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuDashboard extends WpssoAdmin {

		private $max_cols = 3;

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

			$this->p->util->add_plugin_actions( $this, array(
				'form_content_metaboxes_dashboard' => 1,	// Show four-column metaboxes.
			) );
		}

		/**
		 * Remove all submit / action buttons from the Dashboard page.
		 */
		public function filter_form_button_rows( $form_button_rows ) {

			return array();
		}

		public function action_form_content_metaboxes_dashboard( $pagehook ) {

			foreach ( range( 1, $this->max_cols ) as $dashboard_col ) {

				/**
				 * CSS id values must use underscores instead of hyphens to order the metaboxes.
				 */
				echo '<div id="dashboard_col_' . $dashboard_col . '" class="max_cols_' . $this->max_cols . ' dashboard_col">';

				do_meta_boxes( $pagehook, 'dashboard_col_' . $dashboard_col, null );

				echo '</div><!-- #dashboard_col_' . $dashboard_col . ' -->' . "\n";
			}

			echo '<div style="clear:both;"></div>' . "\n";
		}

		protected function add_meta_boxes() {

			$dashboard_col = 0;

			$using_external_cache = wp_using_ext_object_cache();

			/**
			 * Don't include the 'cache_status' metabox if we're using an external object cache.
			 */
			if ( $using_external_cache ) {

				$metabox_ids = array( 

					/**
					 * First row.
					 */
					'rate_review'  => _x( 'Ratings are Awesome!', 'metabox title', 'wpsso' ),
					'help_support' => _x( 'Help and Support', 'metabox title', 'wpsso' ),
					'version_info' => _x( 'Version Information', 'metabox title', 'wpsso' ), 

					/**
					 * Second row.
					 */
					'status_gpl'   => _x( 'Standard Features', 'metabox title', 'wpsso' ),
					'status_pro'   => _x( 'Additional Features (Pro version)', 'metabox title', 'wpsso' ),
				);

			} else {

				$metabox_ids = array( 

					/**
					 * First row.
					 */
					'cache_status' => _x( 'Cache Status', 'metabox title', 'wpsso' ), 
					'rate_review'  => _x( 'Ratings are Awesome!', 'metabox title', 'wpsso' ),
					'help_support' => _x( 'Help and Support', 'metabox title', 'wpsso' ),

					/**
					 * Second row.
					 */
					'status_gpl'   => _x( 'Standard Features', 'metabox title', 'wpsso' ),
					'status_pro'   => _x( 'Additional Features (Pro version)', 'metabox title', 'wpsso' ),
					'version_info' => _x( 'Version Information', 'metabox title', 'wpsso' ), 
				);
			}

			foreach ( $metabox_ids as $metabox_id => $metabox_title ) {

				$dashboard_col   = $dashboard_col >= $this->max_cols ? 1 : $dashboard_col + 1;
				$metabox_screen  = $this->pagehook;
				$metabox_context = 'dashboard_col_' . $dashboard_col;	// Use underscores (not hyphens) to order metaboxes.
				$metabox_prio    = 'default';
				$callback_args   = array(	// Second argument passed to the callback function / method.
				);

				add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
					array( $this, 'show_metabox_' . $metabox_id ), $metabox_screen,
						$metabox_context, $metabox_prio, $callback_args );

				add_filter( 'postbox_classes_' . $this->pagehook . '_' . $this->pagehook . '_' . $metabox_id,
					array( $this, 'add_class_postbox_dashboard' ) );
			}
		}

		public function add_class_postbox_dashboard( $classes ) {

			global $wp_current_filter;

			$filter_name  = end( $wp_current_filter );
			$postbox_name = preg_replace( '/^.*-(' . $this->menu_id . '_.*)$/u', '$1', $filter_name );

			$classes[] = 'postbox-' . $this->menu_id;
			$classes[] = 'postbox-' . $postbox_name;

			return $classes;
		}
	}
}
