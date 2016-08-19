<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomNoDebug' ) ) {

	class SucomNoDebug {
		public $enabled = false;
		public function __construct() {}
		public function mark() {}
		public function log_args() {}
		public function log_arr() {}
		public function log() {}
		public function show_html() {}
		public function get_html() { return ''; }
		public function switch_on() { return false; }
		public function switch_off() { return false; }
		public function is_enabled() { return false; }
		public static function pretty_array() { return ''; }
		public static function get_hooks() { return array(); }
	}
}

?>
