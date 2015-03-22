<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomStyle' ) ) {

	class SucomStyle {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();

			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles' ) );
			}
		}

		public function admin_enqueue_styles( $hook ) {
			$lca = $this->p->cf['lca'];
			$url_path = constant( $this->p->cf['uca'].'_URLPATH' );
			$plugin_version = $this->p->cf['plugin'][$lca]['version'];

			wp_register_style( 'jquery-qtip.js',
				$url_path.'css/ext/jquery-qtip.min.css', array(), '2.2.1' );
			wp_register_style( 'sucom-setting-pages',
				$url_path.'css/com/setting-pages.min.css', array(), $plugin_version );
			wp_register_style( 'sucom-table-setting',
				$url_path.'css/com/table-setting.min.css', array(), $plugin_version );
			wp_register_style( 'sucom-metabox-tabs',
				$url_path.'css/com/metabox-tabs.min.css', array(), $plugin_version );

			switch ( $hook ) {
				case 'user-edit.php':
				case 'profile.php':
				case 'post.php':
				case 'post-new.php':
					wp_enqueue_style( 'jquery-qtip.js' );
					wp_enqueue_style( 'sucom-table-setting' );
					wp_enqueue_style( 'sucom-metabox-tabs' );
					break;
				case ( preg_match( '/_page_'.$lca.'-(site)?licenses/', $hook ) ? true : false ) :
					add_filter( 'admin_body_class', array( &$this, 'add_plugins_body_class' ) );
					add_thickbox();		// required to view plugin details box
					// no break
				case ( preg_match( '/_page_'.$lca.'-/', $hook ) ? true : false ) :
					wp_enqueue_style( 'jquery-qtip.js' );
					wp_enqueue_style( 'sucom-setting-pages' );
					wp_enqueue_style( 'sucom-table-setting' );
					wp_enqueue_style( 'sucom-metabox-tabs' );
					break;
				case 'plugin-install.php':	// view plugin details thickbox
					$this->thickbox_inline_styles( $hook );
					break;
			}
			$this->admin_inline_styles( $hook );
		}

		public function add_plugins_body_class( $classes ) {
			$classes .= ' plugins-php';
			return $classes;
		}

		private function thickbox_inline_styles( $hook ) {
			echo '
<style type="text/css">
	body#plugin-information #section-description img {
		float:left;
		margin:20px 20px 20px 0;
		max-width:100%;
	}
	body#plugin-information #section-other_notes h3 {
		clear:none;
		display:none;
</style>';
		}

		private function admin_inline_styles( $hook ) {
			if ( isset( $this->p->cf['color'] ) ) {
				$lca = $this->p->cf['lca'];
				$menu = $lca.'-'.key( $this->p->cf['*']['lib']['submenu'] );
				$sitemenu = $lca.'-'.key( $this->p->cf['*']['lib']['sitesubmenu'] );
				echo '
<style type="text/css">
	li.menu-top.toplevel_page_'.$menu.' div.wp-menu-image:before,
	li.menu-top.toplevel_page_'.$sitemenu.' div.wp-menu-image:before {
		color:#'.$this->p->cf['color'].' !important;
	}
</style>';
			}
		}
	}
}

?>
