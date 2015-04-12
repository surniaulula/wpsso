<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSettingContactfields' ) && class_exists( 'WpssoSubmenuAdvanced' ) ) {

	class WpssoSettingContactfields extends WpssoSubmenuAdvanced {

		public function __construct( &$plugin, $id, $name ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_contact_fields', 'Profile Contact Fields', 
				array( &$this, 'show_metabox_contact_fields' ), $this->pagehook, 'normal' );
		}

	}
}

?>
