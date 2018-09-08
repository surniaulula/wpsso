<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomNoNotice' ) ) {

	class SucomNoNotice {

		public $enabled = false;

		public function __construct() {}

		public function nag() {}
		public function upd() {}
		public function inf() {}
		public function err() {}
		public function warn() {}
		public function log() {}

		public function trunc_key() {}		// Depecated on 2018/09/08.
		public function truncate_key() {}
		public function trunc_all() {}		// Depecated on 2018/09/08.
		public function trunc() {}		// Depecated on 2018/09/08.
		public function truncate() {}

		public function set_ref() {}
		public function unset_ref() {}
		public function get_ref() {}
		public function get_ref_url_html() {}

		public function is_ref_url() {}
		public function is_admin_pre_notices() { return false; }
		public function is_dismissed() {}

		public function can_dismiss() {}
		public function hook_admin_notices() {}
		public function show_admin_notices() {}
		public function admin_footer_script() {}
		public function ajax_dismiss_notice() { die( -1 ); }
		public function ajax_get_notices_json() { die( -1 ); }
		public function shutdown_notice_cache() {}
	}
}
