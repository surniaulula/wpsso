<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
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

			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;	// lowercase acronyn for plugin or extension
		}

		protected function add_plugin_hooks() {
			$this->p->util->add_plugin_actions( $this, array(
				'form_content_metaboxes_dashboard' => 1,	// show four-column metaboxes
			) );
			$this->p->util->add_plugin_filters( $this, array(
				'action_buttons' => 1,
			) );
		}

		// called by the extended WpssoAdmin class
		protected function add_meta_boxes() {

			$col = 0;
			$ids = array( 
				'help_support' => _x( 'Help and Support', 'metabox title', 'wpsso' ),
				'rate_review' => _x( 'Ratings are Awesome!', 'metabox title', 'wpsso' ),
				'version_info' => _x( 'Version Information', 'metabox title', 'wpsso' ), 
				'status_gpl' => _x( 'Free / Standard Features', 'metabox title', 'wpsso' ),
				'status_pro' => _x( 'Pro / Additional Features', 'metabox title', 'wpsso' ),
			);

			foreach ( $ids as $id => $name ) {
				$col = $col >= $this->max_cols ? 1 : $col + 1;
				$pos_id = 'dashboard_col_'.$col;	// ids must use underscores instead of hyphens to order metaboxes
				$prio = 'default';
				$args = array( 'id' => $id, 'name' => $name );

				add_meta_box( $this->pagehook.'_'.$id, $name,
					array( &$this, 'show_metabox_'.$id ),
						$this->pagehook, $pos_id, $prio, $args );

				add_filter( 'postbox_classes_'.$this->pagehook.'_'.$this->pagehook.'_'.$id,
					array( &$this, 'add_class_postbox_dashboard' ) );
			}
		}

		public function filter_action_buttons( $action_buttons ) {
			unset( $action_buttons[0] );
			return $action_buttons;
		}

		public function add_class_postbox_dashboard( $classes ) {
			$classes[] = 'postbox-dashboard';
			return $classes;
		}

		// show two-column metaboxes for sharing buttons
		public function action_form_content_metaboxes_dashboard( $pagehook ) {
			foreach ( range( 1, $this->max_cols ) as $col ) {
				// ids must use underscores instead of hyphens to order metaboxes
				echo '<div id="dashboard_col_'.$col.'" class="max_cols_'.$this->max_cols.' dashboard_col">';
				do_meta_boxes( $pagehook, 'dashboard_col_'.$col, null );
				echo '</div><!-- #dashboard_col_'.$col.' -->'."\n";
			}
			echo '<div style="clear:both;"></div>'."\n";
		}
	}
}

?>
