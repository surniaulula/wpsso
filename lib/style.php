<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

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
			$plugin_version = $this->p->cf['plugin'][$lca]['version'];

			// https://developers.google.com/speed/libraries/
			wp_enqueue_style( 'jquery-ui.js',
				'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css',
					array(), '1.11.4' );

			// http://qtip2.com/download
			wp_register_style( 'jquery-qtip.js',
				WPSSO_URLPATH.'css/ext/jquery-qtip.min.css',
					array(), '3.0.3' );

			wp_register_style( 'sucom-setting-pages',
				WPSSO_URLPATH.'css/com/setting-pages.min.css',
					array(), $plugin_version );

			wp_register_style( 'sucom-table-setting',
				WPSSO_URLPATH.'css/com/table-setting.min.css',
					array(), $plugin_version );

			wp_register_style( 'sucom-metabox-tabs',
				WPSSO_URLPATH.'css/com/metabox-tabs.min.css',
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
			echo '<style type="text/css">';
			$sort_cols = WpssoMeta::get_sortable_columns();

			if ( isset( $this->p->cf['menu']['color'] ) ) {
				$menu = 'wpsso-'.key( $this->p->cf['*']['lib']['submenu'] );
				$sitemenu = 'wpsso-'.key( $this->p->cf['*']['lib']['sitesubmenu'] );
				$icon_highlight = defined( 'WPSSO_MENU_ICON_HIGHLIGHT' ) && 
					WPSSO_MENU_ICON_HIGHLIGHT ? true : false;
				if ( $icon_highlight )  {
					echo '
	#adminmenu li.menu-top.toplevel_page_'.$menu.' div.wp-menu-image:before,
	#adminmenu li.menu-top.toplevel_page_'.$sitemenu.' div.wp-menu-image:before,
	#adminmenu li.menu-top.toplevel_page_'.$menu.':hover div.wp-menu-image:before,
	#adminmenu li.menu-top.toplevel_page_'.$sitemenu.':hover div.wp-menu-image:before {
		color:#'.$this->p->cf['menu']['color'].';
	}';
				}
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
	.wp-list-table.media .column-cb,
	.wp-list-table.media .check-column {
		width:2%;
	}
	@media ( max-width:1200px ) {
		.wp-list-table.media .column-cb,
		.wp-list-table.media .check-column {
			width:3%;
		}
	}
	@media ( max-width:782px ) {
		.wp-list-table.media .column-cb,
		.wp-list-table.media .check-column {
			width:6%;
		}
	}
	@media ( max-width:600px ) {
		.wp-list-table.media .column-cb,
		.wp-list-table.media .check-column {
			width:10%;
		}
	}
	.wp-list-table th.column-title,
	.wp-list-table td.column-title {
		width:25%;
	}
	.wp-list-table th.column-author,
	.wp-list-table td.column-author {
		width:10%;
	}
	.wp-list-table th.column-sku,
	.wp-list-table td.column-sku,
	.wp-list-table th.column-is_in_stock,
	.wp-list-table td.column-is_in_stock {
		width:6%;	/* woocommerce */
	}
	.wp-list-table th.column-price,
	.wp-list-table td.column-price {
		width:8%;	/* woocommerce */
	}
	.wp-list-table th.column-categories,
	.wp-list-table td.column-categories,
	.wp-list-table th.column-tags,
	.wp-list-table td.column-tags {
		width:12%;	/* default is 15% */
	}
	.wp-list-table th.column-slug,
	.wp-list-table td.column-slug {
		width:20%;	/* default is 25% */
	}
	.wp-list-table th.column-comments,
	.wp-list-table td.column-comments {
		width:4%;
	}
	.column-wpsso_og_img { 
		width:'.$sort_cols['og_img']['width'].' !important;
		min-width:'.$sort_cols['og_img']['width'].' !important;
		max-width:'.$sort_cols['og_img']['width'].' !important;
	}
	.column-wpsso_og_img .preview_img { 
		width:'.$sort_cols['og_img']['width'].';
		min-width:'.$sort_cols['og_img']['width'].';
		max-width:'.$sort_cols['og_img']['width'].';
		height:'.$sort_cols['og_img']['height'].';
		min-height:'.$sort_cols['og_img']['height'].';
		background-size:'.$sort_cols['og_img']['width'].' auto;
		background-position:center center;
		background-repeat:no-repeat;
		background-position:center middle;
		overflow:hidden;
		margin:0;
		padding:0;
	}
	.column-wpsso_og_desc {
		width:'.$sort_cols['og_desc']['width'].';
		min-width:'.$sort_cols['og_desc']['width'].';
		overflow:hidden;
	}
	.column-wpsso_schema_type {
		width:'.$sort_cols['schema_type']['width'].' !important;
		min-width:'.$sort_cols['schema_type']['width'].' !important;
		max-width:'.$sort_cols['schema_type']['width'].' !important;
		white-space:nowrap;
		overflow:hidden;
	}
	td.column-wpsso_og_desc,
	td.column-wpsso_schema_type {
		direction:ltr;
		font-family:Helvetica;
		text-align:left;
		word-wrap:break-word;
	}
	@media ( max-width:1295px ) {
		th.column-wpsso_og_desc,
		td.column-wpsso_og_desc {
			display:none;
		}
	}
	.wpsso-notice.notice {
		padding:0;
	}
	.wpsso-notice ul {
		margin:5px 0 5px 40px;
		list-style:disc outside none;
	}
	.wpsso-notice.notice-success .notice-label:before,
	.wpsso-notice.notice-info .notice-label:before,
	.wpsso-notice.notice-warning .notice-label:before,
	.wpsso-notice.notice-error .notice-label:before {
		vertical-align:bottom;
		font-family:dashicons;
		font-size:1.2em;
		margin-right:6px;
	}
	.wpsso-notice.notice-success .notice-label:before {
		content: "\f147";	/* yes */
	}
	.wpsso-notice.notice-info .notice-label:before {
		content: "\f537";	/* sticky */
	}
	.wpsso-notice.notice-warning .notice-label:before {
		content: "\f227";	/* flag */
	}
	.wpsso-notice.notice-error .notice-label:before {
		content: "\f488";	/* megaphone */
	}
	.wpsso-notice .notice-label {
		display:table-cell;
		vertical-align:top;
		padding:10px;
		margin:0;
		white-space:nowrap;
		font-weight:bold;
		background:#fcfcfc;
		border-right:1px solid #ddd;
	}
	.wpsso-notice .notice-message {
		display:table-cell;
		vertical-align:top;
		padding:10px 20px;
		margin:0;
		line-height:1.5em;
	}
	.wpsso-notice .notice-message ul li {
		margin-top:3px;
		margin-bottom:3px;
	}
	.wpsso-notice .notice-message a {
		text-decoration:none;
	}
	.wpsso-dismissible .notice-dismiss:before {
		display:inline-block;
		margin-right:2px;
	}
	.wpsso-dismissible .notice-dismiss {
		float:right;
		position:relative;
		padding:10px;
		margin:0;
		top:0;
		right:0;
	}
	.wpsso-dismissible .notice-dismiss-text {
		display:inline-block;
		font-size:12px;
		vertical-align:top;
	}';
			if ( ! empty( $this->p->is_avail['seo']['wpseo'] ) ) {
				echo '
	.wp-list-table th#wpseo-score,
	.wp-list-table th#wpseo-score-readability,
	.wp-list-table th#wpseo_score,
	.wp-list-table th#wpseo_score_readability,
	.wp-list-table th.column-wpseo-score,
	.wp-list-table td.column-wpseo-score,
	.wp-list-table th.column_wpseo_score,
	.wp-list-table td.column_wpseo_score,
	.wp-list-table th.column-wpseo-score-readability,
	.wp-list-table td.column-wpseo-score-readability,
	.wp-list-table th.column-wpseo_score_readability,
	.wp-list-table td.column-wpseo_score_readability {
		width:30px;
	}
	.wp-list-table th.column-wpseo-title,
	.wp-list-table td.column-wpseo-title,
	.wp-list-table th.column-wpseo-metadesc,
	.wp-list-table td.column-wpseo-metadesc {
		width:9%;
	}
	.wp-list-table th.column-wpseo-focuskw,
	.wp-list-table td.column-wpseo-focuskw {
		width:6%;
	}';
			}
			echo '</style>';
		}
	}
}

?>
