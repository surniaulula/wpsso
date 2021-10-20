<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
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

		/**
		 * Called by WpssoAdmin->load_setting_page() after the 'wpsso-action' query is handled.
		 *
		 * Add settings page filter and action hooks.
		 */
		protected function add_plugin_hooks() {

			/**
			 * Make sure this filter runs last as it removes all form buttons.
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows' => 1,	// Filter form buttons for this settings page only.
			), PHP_INT_MAX );

			$this->p->util->add_plugin_actions( $this, array(
				'form_content_metaboxes_dashboard' => 1,
			) );
		}

		/**
		 * Remove all submit / action buttons from the Dashboard page.
		 */
		public function filter_form_button_rows( $form_button_rows ) {

			return array();
		}

		public function action_form_content_metaboxes_dashboard( $pagehook ) {

			/**
			 * This settings page does not have any "normal" metaboxes, so hide that container and set the container
			 * height to 0 to prevent drag-and-drop in that area, just in case.
			 */
			echo '<style type="text/css">div#' . $pagehook . ' div#normal-sortables { display:none; height:0; min-height:0; }</style>';
			echo '<div id="metabox_col_wrap">' . "\n";

			$max_cols = 2;

			foreach ( range( 1, $max_cols ) as $metabox_col ) {

				$class_last = $metabox_col === $max_cols ? ' metabox_col_last' : '';

				/**
				 * CSS id values must use underscores instead of hyphens to order the metaboxes.
				 */
				echo '<div id="metabox_col_' . $metabox_col . '" class="metabox_col max_cols_' . $max_cols . $class_last . '">' . "\n";

				do_meta_boxes( $pagehook, 'metabox_col_' . $metabox_col, null );

				echo '</div><!-- #metabox_col_' . $metabox_col . ' -->' . "\n";
			}

			echo '</div><!-- #metabox_col_wrap -->' . "\n";
			echo '<div style="clear:both;"></div>' . "\n";
		}

		protected function add_meta_boxes() {

			$metabox_ids = array();

			$dist_pro_name = _x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' );
			$dist_std_name = _x( $this->p->cf[ 'dist' ][ 'std' ], 'distribution name', 'wpsso' );

			/**
			 * Don't include the 'cache_status' metabox if we're using an external object cache.
			 */
			$external_cache = wp_using_ext_object_cache();

			if ( ! $external_cache ) {

				$metabox_ids[ 'cache_status' ] = _x( 'Cache Status', 'metabox title', 'wpsso' );
			}

			$metabox_ids[ 'rate_review' ]  = _x( 'Your Rating is Important', 'metabox title', 'wpsso' );
			$metabox_ids[ 'help_support' ] = _x( 'Help and Support', 'metabox title', 'wpsso' );
			$metabox_ids[ 'version_info' ] = _x( 'Version Information', 'metabox title', 'wpsso' );
			$metabox_ids[ 'status_std' ]   = sprintf( _x( '%s Features', 'metabox title', 'wpsso' ), $dist_std_name );
			$metabox_ids[ 'status_pro' ]   = sprintf( _x( '%s Features', 'metabox title', 'wpsso' ), $dist_pro_name );

			$max_cols = 2;

			$metabox_col = 0;

			foreach ( $metabox_ids as $metabox_id => $metabox_title ) {

				$metabox_col     = $metabox_col >= $max_cols ? 1 : $metabox_col + 1;
				$metabox_screen  = $this->pagehook;
				$metabox_context = 'metabox_col_' . $metabox_col;	// Use underscores (not hyphens) to order metaboxes.
				$metabox_prio    = 'default';
				$callback_args   = array(	// Second argument passed to the callback function / method.
				);

				add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
					array( $this, 'show_metabox_' . $metabox_id ), $metabox_screen,
						$metabox_context, $metabox_prio, $callback_args );

				add_filter( 'postbox_classes_' . $this->pagehook . '_' . $this->pagehook . '_' . $metabox_id,
					array( $this, 'add_class_postbox_menu_id' ) );
			}
		}
	}
}
