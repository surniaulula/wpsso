<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
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
		public function is_admin_pre_notices() { return false; }
	}
}

?>
