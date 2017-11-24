<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomNoNotice' ) ) {

	class SucomNoNotice {
		public $enabled = false;
		public function __construct() {}
		public function nag() {}
		public function err() {}
		public function warn() {}
		public function upd() {}
		public function inf() {}
		public function log() {}
		public function trunc_key() {}
		public function trunc_all() {}
		public function trunc() {}
		public function set_ref() {}
		public function unset_ref() {}
		public function get_ref() {}
		public function get_ref_url_html() {}
		public function is_ref_url() {}
		public function is_admin_pre_notices() { return false; }
		public function can_dismiss() {}
		public function is_dismissed() {}
		public function reload_user_notices() {}
		public function ajax_dismiss_notice() {}
		public function admin_footer_script() {}
		public function hook_admin_notices() {}
		public function show_admin_notices() {}
		public function save_user_notices() {}
	}
}

?>
