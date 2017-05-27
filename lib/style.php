<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoStyle' ) ) {

	class WpssoStyle {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles' ) );
			}
		}

		public function admin_enqueue_styles( $hook_name ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'hook name = '.$hook_name );
				$this->p->debug->log( 'screen base = '.SucomUtil::get_screen_base() );
			}

			$lca = $this->p->cf['lca'];
			$plugin_version = WpssoConfig::get_version();

			// https://developers.google.com/speed/libraries/
			wp_register_style( 'jquery-ui.js',
				'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css',
					array(), '1.11.4' );

			// http://qtip2.com/download
			wp_register_style( 'jquery-qtip.js',
				WPSSO_URLPATH.'css/ext/jquery-qtip.min.css',
					array(), '3.0.3' );

			wp_register_style( 'sucom-settings-table',
				WPSSO_URLPATH.'css/com/settings-table.min.css',
					array(), $plugin_version );

			wp_register_style( 'sucom-metabox-tabs',
				WPSSO_URLPATH.'css/com/metabox-tabs.min.css',
					array(), $plugin_version );

			switch ( $hook_name ) {

				case ( preg_match( '/_page_'.$lca.'-(site)?licenses/', $hook_name ) ? true : false ):
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing styles for licenses page' );
					}
					add_filter( 'admin_body_class', array( &$this, 'add_plugins_body_class' ) );

					// no break

				// includes the profile_page and users_page hooks (profile submenu items)
				case ( strpos( $hook_name, '_page_'.$lca.'-' ) !== false ? true : false ):
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing styles for settings page' );
					}
					$this->add_settings_page_style( $hook_name, WPSSO_URLPATH, $plugin_version );

					// no break

				case 'post.php':	// post edit
				case 'post-new.php':	// post edit
				case 'term.php':	// term edit
				case 'edit-tags.php':	// term edit
				case 'user-edit.php':	// user edit
				case 'profile.php':	// user edit
				case ( SucomUtil::is_toplevel_edit( $hook_name ) ):	// required for event espresso plugin
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing styles for editing page' );
					}
					wp_enqueue_style( 'jquery-ui.js' );
					wp_enqueue_style( 'jquery-qtip.js' );
					wp_enqueue_style( 'sucom-settings-table' );
					wp_enqueue_style( 'sucom-metabox-tabs' );
					wp_enqueue_style( 'wp-color-picker' );

					break;	// stop here

				case 'plugin-install.php':
					if ( isset( $_GET['plugin'] ) ) {
						$plugin_slug = $_GET['plugin'];
						if ( isset( $this->p->cf['*']['slug'][$plugin_slug] ) ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'enqueuing styles for plugin install page' );
							}
							$this->plugin_install_inline_style( $hook_name );
						}
					}

					break;	// stop here
			}

			$this->add_admin_page_style( $hook_name, WPSSO_URLPATH, $plugin_version );
		}

		public function add_plugins_body_class( $classes ) {
			$classes .= ' plugins-php';
			return $classes;
		}

		private function add_settings_page_style( $hook_name, $plugin_urlpath, $plugin_version ) {

			$lca = $this->p->cf['lca'];
			$custom_style_css = '';

			wp_enqueue_style( 'sucom-settings-page',
				$plugin_urlpath.'css/com/settings-page.min.css',
					array(), $plugin_version );

			if ( isset( $this->p->cf['menu']['color'] ) ) {
				$custom_style_css .= '
					#poststuff #side-info-column .postbox {
						border:1px solid #'.$this->p->cf['menu']['color'].';
					}
					#poststuff #side-info-column .postbox h2 {
						border-bottom:1px dotted #'.$this->p->cf['menu']['color'].';
					}
					#poststuff #side-info-column .postbox.closed h2 {
						border-bottom:1px solid #'.$this->p->cf['menu']['color'].';
					}
					#poststuff #side-info-column .postbox.closed {
						border-bottom:none;
					}
				';
			}

			if ( strpos( $hook_name, '_page_'.$lca.'-dashboard' ) ) {
				$custom_style_css .= 'div#'.$hook_name.' div#normal-sortables { min-height:0; }';
			}

			$custom_style_css = apply_filters( $lca.'_settings_page_custom_style_css',
				$custom_style_css, $hook_name, $plugin_urlpath, $plugin_version );

			if ( ! empty( $custom_style_css ) ) {
				wp_add_inline_style( 'sucom-settings-page', SucomUtil::minify_css( $custom_style_css, $lca ) );
			}
		}

		private function add_admin_page_style( $hook_name, $plugin_urlpath, $plugin_version ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify admin page style' );	// begin timer
			}

			$lca = $this->p->cf['lca'];
			$sort_cols = WpssoMeta::get_sortable_columns();
			$custom_style_css = '';

			wp_enqueue_style( 'sucom-admin-page',
				$plugin_urlpath.'css/com/admin-page.min.css',
					array(), $plugin_version );

			if ( isset( $this->p->cf['menu']['color'] ) ) {
				$menu = $lca.'-'.key( $this->p->cf['*']['lib']['submenu'] );
				$sitemenu = $lca.'-'.key( $this->p->cf['*']['lib']['sitesubmenu'] );
				$icon_highlight = defined( 'WPSSO_MENU_ICON_HIGHLIGHT' ) && 
					WPSSO_MENU_ICON_HIGHLIGHT ? true : false;

				if ( $icon_highlight )  {
					$custom_style_css .= '
						#adminmenu li.menu-top.toplevel_page_'.$menu.' div.wp-menu-image:before,
						#adminmenu li.menu-top.toplevel_page_'.$sitemenu.' div.wp-menu-image:before,
						#adminmenu li.menu-top.toplevel_page_'.$menu.':hover div.wp-menu-image:before,
						#adminmenu li.menu-top.toplevel_page_'.$sitemenu.':hover div.wp-menu-image:before {
							color:#'.$this->p->cf['menu']['color'].'; }
					';
				}
			}

			$custom_style_css .= '
				@font-face {
					font-family:"Star";
					src:url("'.$plugin_urlpath.'fonts/star.eot");
					src:url("'.$plugin_urlpath.'fonts/star.eot?#iefix") format("embedded-opentype"),
					url("'.$plugin_urlpath.'fonts/star.woff") format("woff"),
					url("'.$plugin_urlpath.'fonts/star.ttf") format("truetype"),
					url("'.$plugin_urlpath.'fonts/star.svg#star") format("svg");
					font-weight:normal;
					font-style:normal;
				}
				#'.$lca.'_social_settings.postbox { 
					min-width:760px;
				}
				.column-'.$lca.'_og_img { 
					width:'.$sort_cols['og_img']['width'].' !important;
					min-width:'.$sort_cols['og_img']['width'].' !important;
					max-width:'.$sort_cols['og_img']['width'].' !important;
				}
				.column-'.$lca.'_og_img .preview_img { 
					width:'.$sort_cols['og_img']['width'].';
					min-width:'.$sort_cols['og_img']['width'].';
					max-width:'.$sort_cols['og_img']['width'].';
					height:'.$sort_cols['og_img']['height'].';
					min-height:'.$sort_cols['og_img']['height'].';
					background-size:'.$sort_cols['og_img']['width'].' auto;
					background-repeat:no-repeat;
					background-position:center center;
					overflow:hidden;
					margin:0;
					padding:0;
				}
				.column-'.$lca.'_schema_type {
					width:'.$sort_cols['schema_type']['width'].' !important;
					min-width:'.$sort_cols['schema_type']['width'].' !important;
					max-width:'.$sort_cols['schema_type']['width'].' !important;
					white-space:nowrap;
					overflow:hidden;
				}
				.column-'.$lca.'_og_desc {
					width:'.$sort_cols['og_desc']['width'].';
					min-width:'.$sort_cols['og_desc']['width'].';
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
					content:"\f147";	/* yes */
				}
				.'.$lca.'-notice.notice-info .notice-label:before {
					content:"\f537";	/* sticky */
				}
				.'.$lca.'-notice.notice-warning .notice-label:before {
					content:"\f227";	/* flag */
				}
				.'.$lca.'-notice.notice-error .notice-label:before {
					content:"\f488";	/* megaphone */
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
				.'.$lca.'-dismissible div.notice-dismiss:before {
					display:inline-block;
					margin-right:2px;
				}
				.'.$lca.'-dismissible div.notice-dismiss {
					float:right;
					position:relative;
					padding:10px;
					margin:0;
					top:0;
					right:0;
				}
				.'.$lca.'-dismissible div.notice-dismiss-text {
					display:inline-block;
					font-size:12px;
					vertical-align:top;
				}
				.'.$lca.'-dismissible div.notice-message p {
					margin:10px 0 10px 0;
				}
				.'.$lca.'-rate-stars {
					font-family:"Star";
					font-size:0.9em;
					width:5.4em;
					height:1em;
					line-height:1;
					position:relative;
					overflow:hidden;
					margin:0 0 1.2em 0;
				}
				.'.$lca.'-rate-stars:before {
					content:"\53\53\53\53\53";
				}
				.'.$lca.'-rate-heart:before {
					color:red;
					font-size:1.1em;
					width:1.1em;
					height:1.1em;
					vertical-align:middle;
				}
			';

			if ( ! empty( $this->p->avail['seo']['wpseo'] ) ) {
				$custom_style_css .= '
					.wp-list-table th.column-title,
					.wp-list-table td.column-title {
					        width:25%;
					}
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
					}
				';
			}

			wp_add_inline_style( 'sucom-admin-page', SucomUtil::minify_css( $custom_style_css, $lca ) );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify admin page style' );	// end timer
			}
		}

		private function plugin_install_inline_style( $hook_name ) {	// $hook_name = plugin-install.php
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
			echo '
				<style type="text/css">
					body#plugin-information div#plugin-information-title.with-banner h2 {
						display:none;
					}
					body#plugin-information #section-description img {
						max-width:100%;
					}
					body#plugin-information #section-description img.readme-icon {
						float:left;
						width:30%;
						min-width:128px;
						max-width:256px;
						margin:0 30px 15px 0;
					}
					body#plugin-information #section-description img.readme-example {
						width:100%;
						min-width:256px;
						max-width:600px;
						margin:30px 0 30px 0;
					}
					body#plugin-information #section-other_notes h3 {
						clear:none;
						display:none;
					}
				</style>
			';
		}
	}
}

?>
