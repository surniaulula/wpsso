<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
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
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), -1000 );
			}
		}

		public function admin_enqueue_styles( $hook_name ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'hook name = ' . $hook_name );
				$this->p->debug->log( 'screen base = ' . SucomUtil::get_screen_base() );
			}

			/**
			 * Do not use minified CSS if the DEV constant is defined.
			 */
			$doing_dev      = SucomUtil::get_const( 'WPSSO_DEV' );
			$css_file_ext   = $doing_dev ? 'css' : 'min.css';
			$plugin_version = WpssoConfig::get_version();

			/**
			 * See https://developers.google.com/speed/libraries/.
			 */
			wp_register_style( 'jquery-ui.js',
				'https://ajax.googleapis.com/ajax/libs/jqueryui/' . 
					$this->p->cf['jquery-ui'][ 'version' ] . '/themes/smoothness/jquery-ui.css',
						array(), $this->p->cf['jquery-ui'][ 'version' ] );

			/**
			 * See http://qtip2.com/download.
			 */
			wp_register_style( 'jquery-qtip.js',
				WPSSO_URLPATH . 'css/ext/jquery-qtip.' . $css_file_ext,
					array(), $this->p->cf['jquery-qtip'][ 'version' ] );

			wp_register_style( 'sucom-settings-table',
				WPSSO_URLPATH . 'css/com/settings-table.' . $css_file_ext,
					array(), $plugin_version );

			wp_register_style( 'sucom-metabox-tabs',
				WPSSO_URLPATH . 'css/com/metabox-tabs.' . $css_file_ext,
					array(), $plugin_version );

			/**
			 * Only load stylesheets where we need them.
			 */
			switch ( $hook_name ) {

				/**
				 * Addons and license settings page.
				 */
				case ( preg_match( '/_page_' . $this->p->lca . '-(site)?(addons|licenses)/', $hook_name ) ? true : false ):

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing styles for addons and licenses page' );
					}

					add_filter( 'admin_body_class', array( $this, 'add_plugins_body_class' ) );

					// no break

				/**
				 * Any settings page. Also matches the profile_page and users_page hooks.
				 */
				case ( false !== strpos( $hook_name, '_page_' . $this->p->lca . '-' ) ? true : false ):

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing styles for settings page' );
					}

					$this->add_settings_page_style( $hook_name, WPSSO_URLPATH, $css_file_ext, $plugin_version );

					// no break

				/**
				 * Editing page.
				 */
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

					if ( isset( $_GET[ 'plugin' ] ) ) {
						$plugin_slug = $_GET[ 'plugin' ];
						if ( isset( $this->p->cf[ '*' ][ 'slug' ][$plugin_slug] ) ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'enqueuing styles for plugin install page' );
							}
							$this->plugin_install_inline_style( $hook_name, $plugin_slug );
						}
					}

					break;	// stop here
			}

			$this->add_admin_page_style( $hook_name, WPSSO_URLPATH, $css_file_ext, $plugin_version );
		}

		public function add_plugins_body_class( $classes ) {

			$classes .= ' plugins-php';

			return $classes;
		}

		private function add_settings_page_style( $hook_name, $plugin_urlpath, $css_file_ext, $plugin_version ) {

			$cache_md5_pre    = $this->p->lca . '_';
			$cache_exp_filter = $this->p->lca . '_cache_expire_admin_css';
			$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, DAY_IN_SECONDS );
			$cache_salt       = __METHOD__ . '(hook_name:' . $hook_name . '_plugin_urlpath:' . $plugin_urlpath . '_plugin_version:' . $plugin_version . ')';
			$cache_id         = $cache_md5_pre . md5( $cache_salt );

			wp_enqueue_style( 'sucom-settings-page',
				$plugin_urlpath . 'css/com/settings-page.' . $css_file_ext,
					array(), $plugin_version );

			if ( $custom_style_css = get_transient( $cache_id ) ) {	// Not empty.
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'settings page style retrieved from cache' );
				}
				wp_add_inline_style( 'sucom-settings-page', $custom_style_css );
				return;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify settings page style' );	// Begin timer.
			}

			/**
			 * Re-use the notice border colors for the side column and dashboard metaboxes.
			 */
			$custom_style_css .= '
				#poststuff #side-info-column .postbox {
					border:1px solid ' . $this->p->cf['notice']['update-nag']['border-color'] . ';
				}
				#poststuff #side-info-column .postbox h2 {
					border-bottom:1px dotted ' . $this->p->cf['notice']['update-nag']['border-color'] . ';
				}
				#poststuff #side-info-column .postbox.closed h2 {
					border-bottom:1px solid ' . $this->p->cf['notice']['update-nag']['border-color'] . ';
				}
				#poststuff #side-info-column .postbox.closed {
					border-bottom:none;
				}
				#poststuff #side-info-column .postbox .inside td.blank,
				#poststuff .dashboard_col .postbox .inside td.blank {
					color:' . $this->p->cf['notice']['update-nag']['color'] . ';
					border-color:' . $this->p->cf['notice']['update-nag']['border-color'] . ';
					background-color:' . $this->p->cf['notice']['update-nag']['background-color'] . ';
				}
			';

			if ( strpos( $hook_name, '_page_' . $this->p->lca . '-dashboard' ) ) {
				$custom_style_css .= 'div#' . $hook_name . ' div#normal-sortables { min-height:0; }';
			}

			$custom_style_css = apply_filters( $this->p->lca . '_settings_page_custom_style_css',
				$custom_style_css, $hook_name, $plugin_urlpath, $plugin_version );
	
			if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
				$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->p->lca );
			}

			set_transient( $cache_id, $custom_style_css, $cache_exp_secs );

			wp_add_inline_style( 'sucom-settings-page', $custom_style_css );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify settings page style' );	// End timer.
			}
		}

		private function add_admin_page_style( $hook_name, $plugin_urlpath, $css_file_ext, $plugin_version ) {

			$cache_md5_pre    = $this->p->lca . '_';
			$cache_exp_filter = $this->p->lca . '_cache_expire_admin_css';
			$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, DAY_IN_SECONDS );

			$cache_salt       = __METHOD__ . '(';
			$cache_salt       .= 'hook_name:' . $hook_name;
			$cache_salt       .= '_urlpath:' . $plugin_urlpath;
			$cache_salt       .= '_version:' . $plugin_version;
			$cache_salt       .= '_columns:' . implode( ',', SucomUtil::preg_grep_keys( '/^plugin_col_/', $this->p->options ) );
			$cache_salt       .= ')';

			$cache_id         = $cache_md5_pre . md5( $cache_salt );

			/**
			 * Do not use transient cache if the DEV constant is defined.
			 */
			$doing_dev = SucomUtil::get_const( 'WPSSO_DEV' );
			$use_cache = $doing_dev ? false : true;

			wp_enqueue_style( 'sucom-admin-page',
				$plugin_urlpath . 'css/com/admin-page.' . $css_file_ext,
					array(), $plugin_version );

			if ( $use_cache ) {

				if ( $custom_style_css = get_transient( $cache_id ) ) {	// not empty

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'admin page style retrieved from cache' );
					}

					wp_add_inline_style( 'sucom-admin-page', $custom_style_css );

					return;
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify admin page style' );	// begin timer
			}

			$sort_cols  = WpssoWpMeta::get_sortable_columns();
			$metabox_id = $this->p->cf['meta'][ 'id' ];
			$menu       = $this->p->lca . '-' . key( $this->p->cf[ '*' ][ 'lib' ]['submenu'] );
			$sitemenu   = $this->p->lca . '-' . key( $this->p->cf[ '*' ][ 'lib' ]['sitesubmenu'] );

			$custom_style_css = '
				@font-face {
					font-family:"WpssoIcons";
					font-weight:normal;
					font-style:normal;
					src:url("' . $plugin_urlpath . 'fonts/icons.eot?' . $plugin_version . '");
					src:url("' . $plugin_urlpath . 'fonts/icons.eot?' . $plugin_version . '#iefix") format("embedded-opentype"),
						url("' . $plugin_urlpath . 'fonts/icons.woff?' . $plugin_version . '") format("woff"),
						url("' . $plugin_urlpath . 'fonts/icons.ttf?' . $plugin_version . '") format("truetype"),
						url("' . $plugin_urlpath . 'fonts/icons.svg?' . $plugin_version . '#icons") format("svg");
				}
				@font-face {
					font-family:"WpssoStar";
					font-weight:normal;
					font-style:normal;
					src:url("' . $plugin_urlpath . 'fonts/star.eot?' . $plugin_version . '");
					src:url("' . $plugin_urlpath . 'fonts/star.eot?' . $plugin_version . '#iefix") format("embedded-opentype"),
						url("' . $plugin_urlpath . 'fonts/star.woff?' . $plugin_version . '") format("woff"),
						url("' . $plugin_urlpath . 'fonts/star.ttf?' . $plugin_version . '") format("truetype"),
						url("' . $plugin_urlpath . 'fonts/star.svg?' . $plugin_version . '#star") format("svg");
				}
				#wpadminbar #wp-toolbar #' . $this->p->lca . '-toolbar-notices-count {
					display:none;
				}
				#wpadminbar #wp-toolbar #' . $this->p->lca . '-toolbar-notices-icon.ab-icon::before { 
					content:"' . $this->p->cf['menu']['before'] . '";
					font-size:28px;
					font-style:normal;
					top:1px;
				}
				#adminmenu li.menu-top.toplevel_page_' . $menu . ' div.wp-menu-image::before,
				#adminmenu li.menu-top.toplevel_page_' . $sitemenu . ' div.wp-menu-image::before,
				#adminmenu li.menu-top.toplevel_page_' . $menu . ':hover div.wp-menu-image::before,
				#adminmenu li.menu-top.toplevel_page_' . $sitemenu . ':hover div.wp-menu-image::before {
					content:"' . $this->p->cf['menu']['before'] . '";
					font-size:30px;
					font-style:normal;
					display:inline;
					line-height:inherit;
					vertical-align:middle;
				}
				#adminmenu #toplevel_page_' . $menu . ' ul > li > a,
				#adminmenu #toplevel_page_' . $sitemenu . ' ul > li > a {
					padding:6px 8px;	/* default is 6px 12px */
				}
				#adminmenu ul.wp-submenu div.' . $this->p->lca . '-menu-item {
					display:table-cell;
				}
				#adminmenu ul.wp-submenu div.' . $this->p->lca . '-menu-item.dashicons-before {
					max-width:1.2em;
					padding-right:6px;
				}
				#adminmenu ul.wp-submenu div.' . $this->p->lca . '-menu-item.dashicons-before::before {
					font-size:1.2em;
					text-align:left;
					opacity:0.5;
					filter:alpha(opacity=50);	/* ie8 and earlier */
				}
				#adminmenu ul.wp-submenu div.' . $this->p->lca . '-menu-item.menu-item-label {
					width:100%;
				}
				#adminmenu ul.wp-submenu div.' . $this->p->lca . '-menu-item.top-last-submenu-page.with-add-ons {
					padding-bottom:12px;
					border-bottom:1px solid;
				}
				#profile-page.wrap #your-profile #' . $this->p->lca . '_' . $metabox_id . '.postbox h3:first-of-type {
					margin:0;
				}
				#' . $this->p->lca . '_' . $metabox_id . '.postbox { 
					min-width:760px;
				}
				#' . $this->p->lca . '_' . $metabox_id . ' .inside {
					padding:0;
					margin:0;
				}
				.' . $this->p->lca . '-rate-heart {
					color:red;
					font-size:1.5em;
					vertical-align:top;
				}
				.' . $this->p->lca . '-rate-heart::before {
					content:"\2665";	/* heart */
				}
				#post-' . $this->p->lca . '-robots {
					display:table;
				}
				#post-' . $this->p->lca . '-robots-label {
					display:table-cell;
					padding-left:3px;
					vertical-align:top;
				}
				#post-' . $this->p->lca . '-robots-display {
					display:table-cell;
					padding-left:3px;
					vertical-align:top;
				}
				#post-' . $this->p->lca . '-robots-content {
					display:block;
					word-wrap:normal;
					font-weight:bold;
				}
				#post-' . $this->p->lca . '-robots-content a {
					font-weight:normal;
				}
				#post-' . $this->p->lca . '-robots-select {
					display:none;
				}
			';

			/**
			 * List table columns.
			 */
			foreach ( array(
				'.column-title' => 'plugin_col_title_width',
				'.column-name'  => 'plugin_col_title_width',
				''              => 'plugin_col_def_width',
			) as $css_class => $opt_key ) {

				$custom_style_css .= "@media (min-width: 783px) {\n";

				switch ( $css_class ) {

					case '':	// Only apply to posts and pages.

						$custom_style_css .= "table.wp-list-table.posts > thead > tr > th,\n";
						$custom_style_css .= "table.wp-list-table.posts > tbody > tr > td,\n";
						$custom_style_css .= "table.wp-list-table.pages > thead > tr > th,\n";
						$custom_style_css .= "table.wp-list-table.pages > tbody > tr > td {\n";

						break;

					default:	// Apply to every WP List Table.

						$custom_style_css .= 'table.wp-list-table.posts > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= 'table.wp-list-table.posts > tbody > tr > td' . $css_class . ",\n";
						$custom_style_css .= 'table.wp-list-table.pages > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= 'table.wp-list-table.pages > tbody > tr > td' . $css_class . ",\n";
						$custom_style_css .= 'table.wp-list-table > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= 'table.wp-list-table > tbody > tr > td' . $css_class . " {\n";

						break;
				}

				foreach ( array(
					'width'     => '',
					'min-width' => '_min',
					'max-width' => '_max',
				) as $css_name => $opt_suffix ) {

					if ( ! empty( $this->p->options[ $opt_key . $opt_suffix ] ) ) {
						$custom_style_css .= $css_name . ':' . $this->p->options[ $opt_key . $opt_suffix ] . ";\n";
					}
				}

				$custom_style_css .= "}\n";
				$custom_style_css .= "}\n";
			}

			$custom_style_css .= '
				table.wp-list-table > thead > tr > td#cb,
				table.wp-list-table > thead > tr > td.column-cb,
				table.wp-list-table > thead > tr > td.check-column,
				table.wp-list-table > tbody > tr > th.check-column,
				table.wp-list-table > tbody > tr > th.column-cb {
					width:2.2em;
				}
				table.wp-list-table > thead > tr > th.column-author,
				table.wp-list-table > tbody > tr > td.column-author {
					width:15%;
				}
				table.wp-list-table > thead > tr > th.column-categories,
				table.wp-list-table > tbody > tr > td.column-categories {
					width:15%;
				}
				table.wp-list-table > thead > tr > th.column-tags,
				table.wp-list-table > tbody > tr > td.column-tags {
					width:15%;
				}
				table.wp-list-table > thead > tr > th.column-description,
				table.wp-list-table > tbody > tr > td.column-description {
					width:20%;
				}
				table.wp-list-table.plugins > thead > tr > th.column-description,	/* Plugins table */
				table.wp-list-table.plugins > tbody > tr > td.column-description {
					width:75%;
				}
				table.wp-list-table.users > thead > tr > th,	/* Users table */
				table.wp-list-table.users > tbody > tr > td {
					width:15%;
				}
				table.wp-list-table > thead > tr > th.num,
				table.wp-list-table > tbody > tr > td.num,
				table.wp-list-table > thead > tr > th.column-comments,
				table.wp-list-table > tbody > tr > td.column-comments {
					width:40px;
				}
				table.wp-list-table > thead > tr > th.column-date,
				table.wp-list-table > tbody > tr > td.column-date,
				table.wp-list-table > thead > tr > th.column-expirationdate,
				table.wp-list-table > tbody > tr > td.column-expirationdate {
					width:7em;
				}
				table.wp-list-table > thead > tr > th.column-seotitle,	/* All In One SEO */
				table.wp-list-table > tbody > tr > td.column-seotitle,
				table.wp-list-table > thead > tr > th.column-seodesc,
				table.wp-list-table > tbody > tr > td.column-seodesc {
					width:20%;
				}
				table.wp-list-table > thead > tr > th.column-term-id,
				table.wp-list-table > tbody > tr > td.column-term-id {
					width:40px;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-links,	/* Yoast SEO */
				table.wp-list-table > tbody > tr > td.column-wpseo-links,
				table.wp-list-table > thead > tr > th.column-wpseo-linked,
				table.wp-list-table > tbody > tr > td.column-wpseo-linked,
				table.wp-list-table > thead > tr > th.column-wpseo-score,
				table.wp-list-table > tbody > tr > td.column-wpseo-score,
				table.wp-list-table > thead > tr > th.column-wpseo-score-readability,
				table.wp-list-table > tbody > tr > td.column-wpseo-score-readability {
					width:40px;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-title,	/* Yoast SEO */
				table.wp-list-table > tbody > tr > td.column-wpseo-title,
				table.wp-list-table > thead > tr > th.column-wpseo-metadesc,
				table.wp-list-table > tbody > tr > td.column-wpseo-metadesc {
					width:15%;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-focuskw,	/* Yoast SEO */
				table.wp-list-table > tbody > tr > td.column-wpseo-focuskw {
					width:10%;
				}
				table.wp-list-table > thead > tr > th.column-template,
				table.wp-list-table > tbody > tr > td.column-template {
				        width:10%;
				}
				.column-' . $this->p->lca . '_schema_type {
					max-width:' . $sort_cols['schema_type']['width'] . ' !important;
					white-space:nowrap;
					overflow:hidden;
				}
				.column-' . $this->p->lca . '_og_type {
					max-width:' . $sort_cols['og_type']['width'] . ' !important;
					white-space:nowrap;
					overflow:hidden;
				}
				.column-' . $this->p->lca . '_og_img { 
					max-width:' . $sort_cols['og_img']['width'] . ' !important;
				}
				.column-' . $this->p->lca . '_og_img .preview_img { 
					max-width:' . $sort_cols['og_img']['width'] . ' !important;
					height:' . $sort_cols['og_img']['height'] . ';
					min-height:' . $sort_cols['og_img']['height'] . ';
					background-size:' . $sort_cols['og_img']['width'] . ' auto;
					background-repeat:no-repeat;
					background-position:center center;
					overflow:hidden;
					margin:0;
					padding:0;
				}
				.column-' . $this->p->lca . '_og_desc {
					overflow:hidden;
				}
				td.column-' . $this->p->lca . '_schema_type,
				td.column-' . $this->p->lca . '_og_type,
				td.column-' . $this->p->lca . '_og_desc {
					direction:ltr;
					font-family:"Helvetica";
					text-align:left;
					word-wrap:break-word;
				}
				@media ( max-width:1295px ) {
					th.column-' . $this->p->lca . '_og_desc,
					td.column-' . $this->p->lca . '_og_desc {
						display:none;
					}
				}
			';

			foreach ( $sort_cols as $col_name => $col_info ) {
				if ( isset( $col_info['width'] ) ) {
					$custom_style_css .= '
						table.wp-list-table > thead > tr > th.column-' . $this->p->lca . '_' . $col_name . ',
						table.wp-list-table > tbody > tr > td.column-' . $this->p->lca . '_' . $col_name . ' {
							width:' . $col_info['width'] . ' !important;
							min-width:' . $col_info['width'] . ' !important;
						}
					';
				}
			}

			if ( $use_cache ) {

				if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
					$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->p->lca );
				}

				set_transient( $cache_id, $custom_style_css, $cache_exp_secs );
			}

			wp_add_inline_style( 'sucom-admin-page', $custom_style_css );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify admin page style' );	// end timer
			}
		}

		private function plugin_install_inline_style( $hook_name, $plugin_slug = false ) {	// $hook_name = plugin-install.php

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Fix the WordPress banner resolution.
			 */
			if ( false !== $plugin_slug && ! empty( $this->p->cf[ '*' ][ 'slug' ][$plugin_slug] ) ) {

				$ext = $this->p->cf[ '*' ][ 'slug' ][$plugin_slug];

				if ( ! empty( $this->p->cf[ 'plugin' ][$ext]['img']['banners'] ) ) {

					$banners = $this->p->cf[ 'plugin' ][$ext]['img']['banners'];

					if ( ! empty( $banners['low'] ) || ! empty( $banners['high'] ) ) {	// Must have at least one banner.

						$low  = empty( $banners['low'] ) ? $banners['high'] : $banners['low'];
						$high = empty( $banners['high'] ) ? $banners['low'] : $banners['high'];
					
						echo '<style type="text/css">' . "\n";
						echo '#plugin-information #plugin-information-title.with-banner { '.
							'background-image: url( ' . esc_url( $low ) . ' ); }' . "\n";
						echo '@media (-webkit-min-device-pixel-ratio: 1.5), (min-resolution: 144dpi) { ' .
							'#plugin-information #plugin-information-title.with-banner { ' .
							'background-image: url( ' . esc_url( $high ) . ' ); } }' . "\n";
						echo '</style>' . "\n";
					}
				}
			}

			echo '
				<style type="text/css">
					/* Hide the plugin name overlay */
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
