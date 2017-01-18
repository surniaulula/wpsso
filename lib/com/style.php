<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
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

		public function admin_enqueue_styles( $hook_name ) {
			$lca = $this->p->cf['lca'];
			$url_path = constant( strtoupper( $this->p->cf['lca'] ).'_URLPATH' );
			$plugin_version = $this->p->cf['plugin'][$lca]['version'];

			// https://developers.google.com/speed/libraries/
			wp_enqueue_style( 'jquery-ui.js',
				'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css',
					array(), '1.11.4' );

			// http://qtip2.com/download
			wp_register_style( 'jquery-qtip.js',
				$url_path.'css/ext/jquery-qtip.min.css',
					array(), '3.0.3' );

			wp_register_style( 'sucom-setting-pages',
				$url_path.'css/com/setting-pages.min.css',
					array(), $plugin_version );

			wp_register_style( 'sucom-table-setting',
				$url_path.'css/com/table-setting.min.css',
					array(), $plugin_version );

			wp_register_style( 'sucom-metabox-tabs',
				$url_path.'css/com/metabox-tabs.min.css',
					array(), $plugin_version );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'hook name = '.$hook_name );
				$this->p->debug->log( 'screen base = '.SucomUtil::get_screen_base() );
			}

			switch ( $hook_name ) {
				case 'post.php':	// post edit
				case 'post-new.php':	// post edit
				case 'term.php':	// term edit
				case 'edit-tags.php':	// term edit
				case 'user-edit.php':	// user edit
				case 'profile.php':	// user edit
				case ( SucomUtil::is_toplevel_edit( $hook_name ) ):	// required for event espresso plugin

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'enqueuing styles for editing page' );

					wp_enqueue_style( 'jquery-ui.js' );
					wp_enqueue_style( 'jquery-qtip.js' );
					wp_enqueue_style( 'sucom-table-setting' );
					wp_enqueue_style( 'sucom-metabox-tabs' );

					break;

				// license settings pages include a "view plugin details" feature
				case ( preg_match( '/_page_'.$lca.'-(site)?licenses/', $hook_name ) ? true : false ):

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'enqueuing styles for licenses page' );

					add_filter( 'admin_body_class', array( &$this, 'add_plugins_body_class' ) );
					add_thickbox();					// required for the plugin details box

					// no break - continue to enqueue the settings page

				// includes the profile_page and users_page hooks (profile submenu items)
				case ( strpos( $hook_name, '_page_'.$lca.'-' ) !== false ? true : false ):

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'enqueuing styles for settings page' );

					wp_enqueue_style( 'jquery-ui.js' );
					wp_enqueue_style( 'jquery-qtip.js' );
					wp_enqueue_style( 'sucom-setting-pages' );	// sidebar, buttons, etc.
					wp_enqueue_style( 'sucom-table-setting' );
					wp_enqueue_style( 'sucom-metabox-tabs' );

					break;

				case 'plugin-install.php':				// css for view plugin details thickbox

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'enqueuing styles for plugin install page' );

					$this->thickbox_inline_styles( $hook_name );

					break;
			}
			$this->admin_inline_styles( $hook_name );
		}

		public function add_plugins_body_class( $classes ) {
			$classes .= ' plugins-php';
			return $classes;
		}

		private function thickbox_inline_styles( $hook_name ) {
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

		private function admin_inline_styles( $hook_name ) {
			$lca = $this->p->cf['lca'];
			$cf_form =& $this->p->cf['form'];
			echo '<style type="text/css">';
			if ( isset( $this->p->cf['menu_color'] ) ) {
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
		color:#'.$this->p->cf['menu_color'].';
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
	.wp-list-table th.column-categories,
	.wp-list-table td.column-categories {
		width:12%;	/* default is 15% */
	}
	.wp-list-table th.column-tags,
	.wp-list-table td.column-tags {
		width:12%;	/* default is 15% */
	}
	.wp-list-table th.column-slug,
	.wp-list-table td.column-slug {
		width:20%;	/* default is 25% */
	}
	.column-'.$lca.'_og_img { 
		width:'.$cf_form['og_img_col_width'].';
	}
	.column-'.$lca.'_og_img .preview_img { 
		width:'.$cf_form['og_img_col_width'].';
		height:'.$cf_form['og_img_col_height'].';
		min-width:'.$cf_form['og_img_col_width'].';
		min-height:'.$cf_form['og_img_col_height'].';
		background-size:'.$cf_form['og_img_col_width'].' auto;
		background-position:center center;
		background-repeat:no-repeat;
		background-position:center middle;
		overflow:hidden;
		margin:0;
		padding:0;
	}
	.column-'.$lca.'_og_desc {
		width:'.$cf_form['og_desc_col_width'].';
		min-width:'.$cf_form['og_desc_col_width'].';
		overflow:hidden;
	}
	.column-'.$lca.'_schema_type {
		width:'.$cf_form['schema_type_col_width'].';
		min-width:'.$cf_form['schema_type_col_width'].';
		white-space:nowrap;
		overflow:hidden;
	}
	td.column-'.$lca.'_og_desc,
	td.column-'.$lca.'_schema_type {
		direction:ltr;
		font-family:Helvetica;
		text-align:left;
		word-wrap:break-word;
	}
	@media ( max-width:1295px ) {
		th.column-'.$lca.'_og_desc,
		td.column-'.$lca.'_og_desc {
			display:none;
		}
	}
	.'.$lca.'-notice.notice {
		padding:0;
	}
	.'.$lca.'-notice ul {
		margin:5px 0 5px 40px;
		list-style:disc outside none;
	}
	.'.$lca.'-notice.notice-success .notice-label:before,
	.'.$lca.'-notice.notice-info .notice-label:before,
	.'.$lca.'-notice.notice-warning .notice-label:before,
	.'.$lca.'-notice.notice-error .notice-label:before {
		vertical-align:bottom;
		font-family:dashicons;
		font-size:1.2em;
		margin-right:6px;
	}
	.'.$lca.'-notice.notice-success .notice-label:before {
		content: "\f147";	/* yes */
	}
	.'.$lca.'-notice.notice-info .notice-label:before {
		content: "\f537";	/* sticky */
	}
	.'.$lca.'-notice.notice-warning .notice-label:before {
		content: "\f227";	/* flag */
	}
	.'.$lca.'-notice.notice-error .notice-label:before {
		content: "\f488";	/* megaphone */
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
