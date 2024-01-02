<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomNoDebug' ) ) {

	class SucomNoDebug {

		public $enabled = false;

		public function __construct() {}
		public function is_enabled() { return false; }
		public function enable() {}
		public function disable() {}
		public function log_args() {}
		public function log_arr() {}
		public function log() {}
		public function mark() {}
		public function mark_caller() {}
		public function mark_diff() {}
		public function show_html() {}
		public function get_html() { return ''; }
	}
}
