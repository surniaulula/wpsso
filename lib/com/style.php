<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomStyle' ) ) {

	class SucomStyle {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			if ( is_admin() )
				add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles' ) );
		}

		public function admin_enqueue_styles( $hook ) {
			$lca = $this->p->cf['lca'];
			$url_path = constant( $this->p->cf['uca'].'_URLPATH' );
			$plugin_version = $this->p->cf['plugin'][$lca]['version'];

			wp_enqueue_style( 'jquery-ui.js',
				'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css', array(), '1.11.4' );
			wp_register_style( 'jquery-qtip.js',
				$url_path.'css/ext/jquery-qtip.min.css', array(), '2.2.1' );
			wp_register_style( 'sucom-setting-pages',
				$url_path.'css/com/setting-pages.min.css', array(), $plugin_version );
			wp_register_style( 'sucom-table-setting',
				$url_path.'css/com/table-setting.min.css', array(), $plugin_version );
			wp_register_style( 'sucom-metabox-tabs',
				$url_path.'css/com/metabox-tabs.min.css', array(), $plugin_version );

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'hook name: '.$hook );

			switch ( $hook ) {
				case 'edit-tags.php':
				case 'user-edit.php':
				case 'profile.php':
				case 'post.php':
				case 'post-new.php':
				case 'term.php':
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'calling wp_enqueue_style() for editing page' );

					wp_enqueue_style( 'jquery-ui.js' );
					wp_enqueue_style( 'jquery-qtip.js' );
					wp_enqueue_style( 'sucom-table-setting' );
					wp_enqueue_style( 'sucom-metabox-tabs' );

					break;

				// license settings pages include a "view plugin details" feature
				case ( preg_match( '/_page_'.$lca.'-(site)?licenses/', $hook ) ? true : false ):
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'calling wp_enqueue_style() for '.$lca.' licenses page' );

					add_filter( 'admin_body_class', array( &$this, 'add_plugins_body_class' ) );
					add_thickbox();					// required for the plugin details box

					// no break - continue to enqueue the settings page

				// includes the profile_page and users_page hooks (profile submenu items)
				case ( strpos( $hook, '_page_'.$lca.'-' ) !== false ? true : false ):
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'calling wp_enqueue_style() for '.$lca.' settings page' );

					wp_enqueue_style( 'jquery-ui.js' );
					wp_enqueue_style( 'jquery-qtip.js' );
					wp_enqueue_style( 'sucom-setting-pages' );	// sidebar, buttons, etc.
					wp_enqueue_style( 'sucom-table-setting' );
					wp_enqueue_style( 'sucom-metabox-tabs' );

					break;

				case 'plugin-install.php':				// css for view plugin details thickbox
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'calling wp_enqueue_style() for plugin install page' );

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
			$lca = $this->p->cf['lca'];
			echo '<style type="text/css">';
			if ( isset( $this->p->cf['color'] ) ) {
				$uca = strtoupper( $lca );
				$menu = $lca.'-'.key( $this->p->cf['*']['lib']['submenu'] );
				$sitemenu = $lca.'-'.key( $this->p->cf['*']['lib']['sitesubmenu'] );
				$icon_highlight = ( defined( $uca.'_MENU_ICON_HIGHLIGHT' )  &&
					constant( $uca.'_MENU_ICON_HIGHLIGHT' ) === false ) ?
						false : true;
				if ( $icon_highlight ) 
					echo '
	#adminmenu li.menu-top.toplevel_page_'.$menu.' div.wp-menu-image:before,
	#adminmenu li.menu-top.toplevel_page_'.$sitemenu.' div.wp-menu-image:before,
	#adminmenu li.menu-top.toplevel_page_'.$menu.':hover div.wp-menu-image:before,
	#adminmenu li.menu-top.toplevel_page_'.$sitemenu.':hover div.wp-menu-image:before {
		color:#'.$this->p->cf['color'].';
	}';
			}
			echo '
	#adminmenu ul.wp-submenu div.extension-plugin {
		display:table-cell;
	}
	#adminmenu ul.wp-submenu div.extension-plugin.dashicons-before {
		max-width:1.1em;
		padding-right:5px;
	}
	#adminmenu ul.wp-submenu div.extension-plugin.dashicons-before:before {
		text-align:left;
		font-size:1.1em;
	}
	.column-'.$lca.'_og_img { 
		width:'.$this->p->cf['form']['og_img_col_width'].';
	}
	.column-'.$lca.'_og_img .preview_img { 
		width:'.$this->p->cf['form']['og_img_col_width'].';
		height:'.$this->p->cf['form']['og_img_col_height'].';
		background-size:'.$this->p->cf['form']['og_img_col_width'].' auto;
		background-position:center center;
		background-repeat:no-repeat;
		background-position:center middle;
		overflow:hidden;
		margin:0;
		padding:0;
	}
	td.column-'.$lca.'_og_desc {
		direction:ltr;
		font-family:Helvetica;
		text-alignleftword-wrap:break-word;
	}
	@media ( max-width:1295px ) {
		th.column-'.$lca.'_og_desc,
		td.column-'.$lca.'_og_desc {
			display:none;
		}
	}
	.'.$lca.'-notice.error,
	.'.$lca.'-notice.updated {
		padding:0;
	}
	.'.$lca.'-notice ul {
		margin:5px 0 5px 40px;
		list-style:disc outside none;
	}
	.'.$lca.'-notice .notice-label {
		display:table-cell;
		vertical-align:top;
		padding:10px;
		margin:0;
		white-space:nowrap;
		font-weight:bold;
		background:#fcfcfc;
		border-right:1px solid #ddd;
	}
	.'.$lca.'-notice .notice-message {
		display:table-cell;
		vertical-align:top;
		padding:10px 20px;
		margin:0;
		line-height:1.5em;
	}
	.'.$lca.'-notice .notice-message ul li {
		margin-top:3px;
		margin-bottom:3px;
	}
	.'.$lca.'-notice .notice-message a {
		text-decoration:none;
	}
	.'.$lca.'-dismissible .notice-dismiss:before {
		display:inline-block;
		margin-right:2px;
	}
	.'.$lca.'-dismissible .notice-dismiss {
		float:right;
		position:relative;
		padding:10px;
		margin:0;
		top:0;
		right:0;
	}
	.'.$lca.'-dismissible .notice-dismiss-text {
		display:inline-block;
		font-size:12px;
		vertical-align:top;
	}
</style>';
		}
	}
}

?>
