<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {
	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoStyle' ) ) {

	class WpssoStyle {

		private $p;
		private $doing_dev = false;
		private $use_cache = true;	// Read/save minimized CSS from/to transient cache.
		private $file_ext  = 'min.css';
		private $version   = '';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->doing_dev = SucomUtil::get_const( 'WPSSO_DEV' );
			$this->use_cache = $this->doing_dev ? false : true;	// Read/save minimized CSS from/to transient cache.
			$this->file_ext  = $this->doing_dev ? 'css' : 'min.css';
			$this->version   = WpssoConfig::get_version();

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
			 * See https://developers.google.com/speed/libraries/.
			 */
			wp_register_style( 'jquery-ui.js',
				'https://ajax.googleapis.com/ajax/libs/jqueryui/' . 
					$this->p->cf[ 'jquery-ui' ][ 'version' ] . '/themes/smoothness/jquery-ui.css',
						array(), $this->p->cf[ 'jquery-ui' ][ 'version' ] );

			/**
			 * See http://qtip2.com/download.
			 */
			wp_register_style( 'jquery-qtip.js',
				WPSSO_URLPATH . 'css/ext/jquery-qtip.' . $this->file_ext,
					array(), $this->p->cf[ 'jquery-qtip' ][ 'version' ] );

			wp_register_style( 'sucom-settings-table',
				WPSSO_URLPATH . 'css/com/settings-table.' . $this->file_ext,
					array(), $this->version );

			wp_register_style( 'sucom-metabox-tabs',
				WPSSO_URLPATH . 'css/com/metabox-tabs.' . $this->file_ext,
					array(), $this->version );

			/**
			 * Only load stylesheets where we need them.
			 */
			switch ( $hook_name ) {

				/**
				 * Addons and license settings page.
				 */
				case ( preg_match( '/_page_' . $this->p->lca . '-.*(addons|licenses)/', $hook_name ) ? true : false ):

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

					$this->add_settings_page_style( $hook_name );

					// No break.

				/**
				 * Editing page.
				 */
				case 'post.php':	// Post edit.
				case 'post-new.php':	// Post edit.
				case 'term.php':	// Term edit.
				case 'edit-tags.php':	// Term edit.
				case 'user-edit.php':	// User edit.
				case 'profile.php':	// User edit.
				case ( SucomUtil::is_toplevel_edit( $hook_name ) ):	// Required for event espresso plugin.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing styles for editing page' );
					}

					wp_enqueue_style( 'jquery-ui.js' );
					wp_enqueue_style( 'jquery-qtip.js' );
					wp_enqueue_style( 'sucom-settings-table' );
					wp_enqueue_style( 'sucom-metabox-tabs' );
					wp_enqueue_style( 'wp-color-picker' );

					break;	// Stop here.

				case 'plugin-install.php':

					if ( isset( $_GET[ 'plugin' ] ) ) {

						$plugin_slug = $_GET[ 'plugin' ];

						if ( isset( $this->p->cf[ '*' ][ 'slug' ][ $plugin_slug ] ) ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'enqueuing styles for plugin install page' );
							}

							$this->plugin_install_inline_style( $hook_name, $plugin_slug );
						}
					}

					break;	// Stop here.
			}

			$this->add_admin_page_style( $hook_name );
		}

		public function add_plugins_body_class( $classes ) {

			$classes .= ' plugins-php';

			return $classes;
		}

		private function add_settings_page_style( $hook_name ) {

			$cache_md5_pre    = $this->p->lca . '_';
			$cache_exp_filter = $this->p->lca . '_cache_expire_admin_css';
			$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, DAY_IN_SECONDS );
			$cache_salt       = __METHOD__ . '(hook_name:' . $hook_name . '_urlpath:' . WPSSO_URLPATH . '_version:' . $this->version . ')';
			$cache_id         = $cache_md5_pre . md5( $cache_salt );

			wp_enqueue_style( 'sucom-settings-page',
				WPSSO_URLPATH . 'css/com/settings-page.' . $this->file_ext,
					array(), $this->version );

			if ( $custom_style_css = get_transient( $cache_id ) ) {	// Not empty.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'settings page style retrieved from cache' );
				}

				wp_add_inline_style( 'sucom-settings-page', $custom_style_css );	// Since WP v3.3.0.

				return;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify settings page style' );	// Begin timer.
			}

			$custom_style_css = '';	// Start with an empty string.

			/**
			 * Re-use the notice border colors for the side column and dashboard metaboxes.
			 */
			$color            = $this->p->cf[ 'notice' ][ 'update-nag' ][ 'color' ];
			$border_color     = $this->p->cf[ 'notice' ][ 'update-nag' ][ 'border-color' ];
			$background_color = $this->p->cf[ 'notice' ][ 'update-nag' ][ 'background-color' ];

			$custom_style_css .= '
				#poststuff #side-info-column .postbox {
					border:1px solid ' . $border_color . ';
				}
				#poststuff #side-info-column .postbox h2 {
					border-bottom:1px dotted ' . $border_color . ';
				}
				#poststuff #side-info-column .postbox.closed h2 {
					border-bottom:1px solid ' . $border_color . ';
				}
				#poststuff #side-info-column .postbox.closed {
					border-bottom:none;
				}
				#poststuff #side-info-column .postbox .inside td.blank,
				#poststuff .dashboard_col .postbox .inside td.blank {
					color:' . $color . ';
					border-color:' . $border_color . ';
					background-color:' . $background_color . ';
				}
			';

			if ( strpos( $hook_name, '_page_' . $this->p->lca . '-dashboard' ) ) {
				$custom_style_css .= 'div#' . $hook_name . ' div#normal-sortables { min-height:0; }';
			}

			$custom_style_css = apply_filters( $this->p->lca . '_settings_page_custom_style_css', $custom_style_css );
	
			if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
				$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->p->lca );
			}

			set_transient( $cache_id, $custom_style_css, $cache_exp_secs );

			wp_add_inline_style( 'sucom-settings-page', $custom_style_css );	// Since WP v3.3.0.

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify settings page style' );	// End timer.
			}
		}

		private function add_admin_page_style( $hook_name ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$lca = $this->p->lca;

			$sort_cols = WpssoWpMeta::get_sortable_columns();	// Uses a static cache.

			$cache_md5_pre    = $lca . '_';
			$cache_exp_filter = $lca . '_cache_expire_admin_css';
			$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, DAY_IN_SECONDS );

			$cache_salt = __METHOD__ . '(';
			$cache_salt .= 'hook_name:' . $hook_name;
			$cache_salt .= '_urlpath:' . WPSSO_URLPATH;
			$cache_salt .= '_version:' . $this->version;
			$cache_salt .= '_columns:' . implode( ',', array_keys( $sort_cols ) );
			$cache_salt .= ')';
			$cache_id   = $cache_md5_pre . md5( $cache_salt );

			wp_enqueue_style( 'sucom-admin-page',
				WPSSO_URLPATH . 'css/com/admin-page.' . $this->file_ext,
					array(), $this->version );

			if ( $this->use_cache ) {

				if ( $custom_style_css = get_transient( $cache_id ) ) {	// not empty

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'admin page style retrieved from cache' );
					}

					wp_add_inline_style( 'sucom-admin-page', $custom_style_css );	// Since WP v3.3.0.

					return;
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify admin page style' );	// Begin timer.
			}

			$metabox_id = $this->p->cf[ 'meta' ][ 'id' ];
			$menu       = $lca . '-' . key( $this->p->cf[ '*' ][ 'lib' ][ 'submenu' ] );
			$sitemenu   = $lca . '-' . key( $this->p->cf[ '*' ][ 'lib' ][ 'sitesubmenu' ] );

			/**
			 * Fonts.
			 */
			$custom_style_css = '
				@font-face {
					font-family:"WpssoIcons";
					font-weight:normal;
					font-style:normal;
					src:url("' . WPSSO_URLPATH . 'fonts/icons.eot?' . $this->version . '");
					src:url("' . WPSSO_URLPATH . 'fonts/icons.eot?' . $this->version . '#iefix") format("embedded-opentype"),
						url("' . WPSSO_URLPATH . 'fonts/icons.woff?' . $this->version . '") format("woff"),
						url("' . WPSSO_URLPATH . 'fonts/icons.ttf?' . $this->version . '") format("truetype"),
						url("' . WPSSO_URLPATH . 'fonts/icons.svg?' . $this->version . '#icons") format("svg");
				}
				@font-face {
					font-family:"WpssoStar";
					font-weight:normal;
					font-style:normal;
					src:url("' . WPSSO_URLPATH . 'fonts/star.eot?' . $this->version . '");
					src:url("' . WPSSO_URLPATH . 'fonts/star.eot?' . $this->version . '#iefix") format("embedded-opentype"),
						url("' . WPSSO_URLPATH . 'fonts/star.woff?' . $this->version . '") format("woff"),
						url("' . WPSSO_URLPATH . 'fonts/star.ttf?' . $this->version . '") format("truetype"),
						url("' . WPSSO_URLPATH . 'fonts/star.svg?' . $this->version . '#star") format("svg");
				}
			';

			/**
			 * Admin toolbar notices.
			 */
			$custom_style_css .= '
				#wpadminbar #wp-toolbar #' . $lca . '-toolbar-notices-icon.ab-icon::before { 
					content:"' . $this->p->cf[ 'menu' ][ 'icon-code' ] . '";
					font-family:'. $this->p->cf[ 'menu' ][ 'icon-family' ] . ';
				}
			';

			/**
			 * Admin menu and sub-menu items.
			 */
			$custom_style_css .= '
				#adminmenu li.menu-top.toplevel_page_' . $menu . ' div.wp-menu-image::before,
				#adminmenu li.menu-top.toplevel_page_' . $sitemenu . ' div.wp-menu-image::before,
				#adminmenu li.menu-top.toplevel_page_' . $menu . ':hover div.wp-menu-image::before,
				#adminmenu li.menu-top.toplevel_page_' . $sitemenu . ':hover div.wp-menu-image::before {
					content:"' . $this->p->cf[ 'menu' ][ 'icon-code' ] . '";
					font-family:'. $this->p->cf[ 'menu' ][ 'icon-family' ] . ';
				}
				#adminmenu #toplevel_page_' . $menu . ' ul > li > a,
				#adminmenu #toplevel_page_' . $sitemenu . ' ul > li > a {
					padding:6px 8px;	/* Default is 6px 12px. */
				}
				#adminmenu ul.wp-submenu div.' . $lca . '-menu-item {
					display:table-cell;
				}
				#adminmenu ul.wp-submenu div.' . $lca . '-menu-item.dashicons-before {
					max-width:1.3em;
					padding-right:6px;
				}
				#adminmenu ul.wp-submenu div.' . $lca . '-menu-item.dashicons-before::before {
					font-size:1.3em;
					text-align:left;
					opacity:0.5;
					filter:alpha(opacity=50);	/* IE8 and earlier. */
				}
				#adminmenu ul.wp-submenu div.' . $lca . '-menu-item.menu-item-label {
					width:100%;
				}
				#adminmenu ul.wp-submenu div.' . $lca . '-menu-item.' . $lca . '-essential,
				#adminmenu ul.wp-submenu div.' . $lca . '-menu-item.top-last-submenu-page.with-add-ons {
					padding-bottom:12px;
					border-bottom:1px solid;
				}
			';

			/**
			 * Settings pages.
			 */
			$custom_style_css .= '
				#profile-page.wrap #your-profile #' . $lca . '_' . $metabox_id . '.postbox h3:first-of-type {
					margin:0;
				}
				#' . $lca . '_' . $metabox_id . '.postbox {
					min-width:455px;	/* The default WordPress postbox minimum width is 255px. */
				}
				#' . $lca . '_' . $metabox_id . ' .inside {
					padding:0;
					margin:0;
				}
				.' . $lca . '-rate-heart {
					color:red;
					font-size:1.5em;
					vertical-align:top;
				}
				.' . $lca . '-rate-heart::before {
					content:"\2665";	/* Heart. */
				}
			';

			/**
			 * Post publish robots option.
			 */
			$custom_style_css .= '
				#post-' . $lca . '-robots {
					display:table;
				}
				#post-' . $lca . '-robots-label {
					display:table-cell;
					padding-left:3px;
					vertical-align:top;
				}
				#post-' . $lca . '-robots-display {
					display:table-cell;
					padding-left:3px;
					vertical-align:top;
				}
				#post-' . $lca . '-robots-content {
					display:block;
					word-wrap:normal;
					font-weight:bold;
				}
				#post-' . $lca . '-robots-content a {
					font-weight:normal;
				}
				#post-' . $lca . '-robots-select {
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

				$custom_style_css .= "\n@media screen and ( min-width:783px ) {\n";

				switch ( $css_class ) {

					case '':	// Only apply to posts and pages.

						$custom_style_css .= "\ttable.wp-list-table.posts > thead > tr > th,\n";
						$custom_style_css .= "\ttable.wp-list-table.posts > tbody > tr > td,\n";
						$custom_style_css .= "\ttable.wp-list-table.pages > thead > tr > th,\n";
						$custom_style_css .= "\ttable.wp-list-table.pages > tbody > tr > td {\n";

						break;

					default:	// Apply to every WP List Table.

						$custom_style_css .= "\t" . 'table.wp-list-table.posts > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table.posts > tbody > tr > td' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table.pages > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table.pages > tbody > tr > td' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table > tbody > tr > td' . $css_class . " {\n";

						break;
				}

				foreach ( array(
					'width'     => '',
					'min-width' => '_min',
					'max-width' => '_max',
				) as $css_name => $opt_suffix ) {

					if ( ! empty( $this->p->options[ $opt_key . $opt_suffix ] ) ) {
						$custom_style_css .= "\t\t" . $css_name . ':' . $this->p->options[ $opt_key . $opt_suffix ] . ";\n";
					}
				}

				$custom_style_css .= "\t}\n";
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
				table.wp-list-table > tbody > tr > td.column-categories,
				table.wp-list-table > thead > tr > th.column-product_cat,
				table.wp-list-table > tbody > tr > td.column-product_cat {
					width:20%;
				}
				table.wp-list-table > thead > tr > th.column-tags,
				table.wp-list-table > tbody > tr > td.column-tags,
				table.wp-list-table > thead > tr > th.column-product_tag,
				table.wp-list-table > tbody > tr > td.column-product_tag {
					width:15%;
				}
				table.wp-list-table > thead > tr > th.column-description,
				table.wp-list-table > tbody > tr > td.column-description {
					width:20%;
				}
				table.wp-list-table.plugins > thead > tr > th.column-description,
				table.wp-list-table.plugins > tbody > tr > td.column-description {	/* Plugins table. */
					width:75%;
				}
				table.wp-list-table.tags > thead > tr > th.column-description,
				table.wp-list-table.tags > tbody > tr > td.column-description {	/* Taxonomy table */
					width:25%;
				}
				table.wp-list-table.users > thead > tr > th,	/* Users table. */
				table.wp-list-table.users > tbody > tr > td {
					width:15%;
				}
				table.wp-list-table.users > thead > tr > th.column-email,
				table.wp-list-table.users > tbody > tr > td.column-email {
					width:20%;
				}
				table.wp-list-table > thead > tr > th.num,
				table.wp-list-table > tbody > tr > td.num,
				table.wp-list-table > thead > tr > th.column-comments,
				table.wp-list-table > tbody > tr > td.column-comments {
					width:50px;
				}
				table.wp-list-table > thead > tr > th.column-posts.num,
				table.wp-list-table > tbody > tr > td.column-posts.num {	/* Count text. */
					width:75px;
				}
				table.wp-list-table > thead > tr > th.column-featured,
				table.wp-list-table > tbody > tr > td.column-featured {
					width:20px;
				}
				table.wp-list-table > thead > tr > th.column-sku,
				table.wp-list-table > tbody > tr > td.column-sku,
				table.wp-list-table > thead > tr > th.column-wpm_pgw_code,
				table.wp-list-table > tbody > tr > td.column-wpm_pgw_code {
					width:80px;
				}
				table.wp-list-table > thead > tr > th.column-date,
				table.wp-list-table > tbody > tr > td.column-date,
				table.wp-list-table > thead > tr > th.column-expirationdate,
				table.wp-list-table > tbody > tr > td.column-expirationdate {
					width:7em;
				}
				table.wp-list-table > thead > tr > th.column-job_position,	/* WP Job Manager. */
				table.wp-list-table > tbody > tr > td.column-job_position {
					width:20%;
				}
				table.wp-list-table > thead > tr > th.column-job_listing_type,
				table.wp-list-table > tbody > tr > td.column-job_listing_type {
					width:5em;
				}
				table.wp-list-table > thead > tr > th.column-job_location,
				table.wp-list-table > tbody > tr > td.column-job_location {
					width:10em;
				}
				table.wp-list-table > thead > tr > th.column-job_status,
				table.wp-list-table > tbody > tr > td.column-job_status,
				table.wp-list-table > thead > tr > th.column-featured_job,
				table.wp-list-table > tbody > tr > td.column-featured_job,
				table.wp-list-table > thead > tr > th.column-filled,
				table.wp-list-table > tbody > tr > td.column-filled {
					width:1em;
				}
				table.wp-list-table > thead > tr > th.column-job_posted,
				table.wp-list-table > tbody > tr > td.column-job_posted,
				table.wp-list-table > thead > tr > th.column-job_expires,
				table.wp-list-table > tbody > tr > td.column-job_expires {
					width:7em;
				}
				table.wp-list-table > thead > tr > th.column-job_actions,
				table.wp-list-table > tbody > tr > td.column-job_actions {
					width:7em;
				}
				table.wp-list-table > thead > tr > th.column-seotitle,		/* All In One SEO. */
				table.wp-list-table > tbody > tr > td.column-seotitle,
				table.wp-list-table > thead > tr > th.column-seodesc,
				table.wp-list-table > tbody > tr > td.column-seodesc {
					width:20%;
				}
				table.wp-list-table > thead > tr > th.column-term-id,
				table.wp-list-table > tbody > tr > td.column-term-id {
					width:40px;
				}
				table.wp-list-table > thead > tr > th.column-thumb,		/* WooCommerce Brands */
				table.wp-list-table > tbody > tr > td.column-thumb {
					width:60px;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-links,	/* Yoast SEO. */
				table.wp-list-table > tbody > tr > td.column-wpseo-links,
				table.wp-list-table > thead > tr > th.column-wpseo-linked,
				table.wp-list-table > tbody > tr > td.column-wpseo-linked,
				table.wp-list-table > thead > tr > th.column-wpseo-score,
				table.wp-list-table > tbody > tr > td.column-wpseo-score,
				table.wp-list-table > thead > tr > th.column-wpseo-score-readability,
				table.wp-list-table > tbody > tr > td.column-wpseo-score-readability {
					width:40px;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-title,	/* Yoast SEO. */
				table.wp-list-table > tbody > tr > td.column-wpseo-title,
				table.wp-list-table > thead > tr > th.column-wpseo-metadesc,
				table.wp-list-table > tbody > tr > td.column-wpseo-metadesc {
					width:20%;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-focuskw,	/* Yoast SEO. */
				table.wp-list-table > tbody > tr > td.column-wpseo-focuskw {
					width:8em;	/* Leave room for the sort arrow. */
				}
				table.wp-list-table > thead > tr > th.column-rank_math_seo_details,	/* Rank Math. */
				table.wp-list-table > tbody > tr > td.column-rank_math_seo_details {
					width:170px;
				}
				table.wp-list-table > thead > tr > th.column-template,
				table.wp-list-table > tbody > tr > td.column-template {
				        width:10%;
				}
			';

			if ( isset( $sort_cols[ 'schema_type' ][ 'width' ] ) ) {
				$custom_style_css .= '
					.column-' . $lca . '_schema_type {
						width:' . $sort_cols[ 'schema_type' ][ 'width' ] . ';
						max-width:' . $sort_cols[ 'schema_type' ][ 'width' ] . ';
						white-space:nowrap;
						overflow:hidden;
					}
				';
			}

			if ( isset( $sort_cols[ 'og_type' ][ 'width' ] ) ) {
				$custom_style_css .= '
					.column-' . $lca . '_og_type {
						width:' . $sort_cols[ 'og_type' ][ 'width' ] . ' !important;
						max-width:' . $sort_cols[ 'og_type' ][ 'width' ] . ' !important;
						white-space:nowrap;
						overflow:hidden;
					}
				';
			}

			if ( isset( $sort_cols[ 'og_img' ][ 'width' ] ) ) {
			
				$custom_style_css .= '
					.column-' . $lca . '_og_img { 
						width:' . $sort_cols[ 'og_img' ][ 'width' ] . ' !important;
						max-width:' . $sort_cols[ 'og_img' ][ 'width' ] . ' !important;
					}
				';

				if ( isset( $sort_cols[ 'og_img' ][ 'height' ] ) ) {
					$custom_style_css .= '
						.column-' . $lca . '_og_img .preview_img { 
							max-width:' . $sort_cols[ 'og_img' ][ 'width' ] . ' !important;
							height:' . $sort_cols[ 'og_img' ][ 'height' ] . ';
							min-height:' . $sort_cols[ 'og_img' ][ 'height' ] . ';
							background-size:' . $sort_cols[ 'og_img' ][ 'width' ] . ' auto;
							background-repeat:no-repeat;
							background-position:center center;
							overflow:hidden;
							margin:0;
							padding:0;
						}
					';
				}
			}

			$custom_style_css .= '

				.column-' . $lca . '_og_desc {
					overflow:hidden;
				}

				td.column-' . $lca . '_schema_type,
				td.column-' . $lca . '_og_type,
				td.column-' . $lca . '_og_desc {
					direction:ltr;
					font-family:"Helvetica";
					text-align:left;
					word-wrap:break-word;
				}

				@media screen and ( max-width:1295px ) {

					th.column-' . $lca . '_og_desc,
					td.column-' . $lca . '_og_desc {
						display:none;
					}
				}
			';

			foreach ( $sort_cols as $col_name => $col_info ) {
				if ( isset( $col_info[ 'width' ] ) ) {
					$custom_style_css .= '
						table.wp-list-table > thead > tr > th.column-' . $lca . '_' . $col_name . ',
						table.wp-list-table > tbody > tr > td.column-' . $lca . '_' . $col_name . ' {
							width:' . $col_info[ 'width' ] . ' !important;
							min-width:' . $col_info[ 'width' ] . ' !important;
						}
					';
				}
			}

			if ( $this->use_cache ) {

				if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
					$custom_style_css = SucomUtil::minify_css( $custom_style_css, $lca );
				}

				set_transient( $cache_id, $custom_style_css, $cache_exp_secs );
			}

			wp_add_inline_style( 'sucom-admin-page', $custom_style_css );	// Since WP v3.3.0.

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'create and minify admin page style' );	// End timer.
			}
		}

		private function plugin_install_inline_style( $hook_name, $plugin_slug = false ) {	// $hook_name = plugin-install.php

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Fix the WordPress banner resolution.
			 */
			if ( false !== $plugin_slug && ! empty( $this->p->cf[ '*' ][ 'slug' ][ $plugin_slug ] ) ) {

				$ext = $this->p->cf[ '*' ][ 'slug' ][ $plugin_slug ];

				if ( ! empty( $this->p->cf[ 'plugin' ][ $ext ][ 'assets' ][ 'banners' ] ) ) {

					$banners = $this->p->cf[ 'plugin' ][ $ext ][ 'assets' ][ 'banners' ];

					if ( ! empty( $banners[ 'low' ] ) || ! empty( $banners[ 'high' ] ) ) {	// Must have at least one banner.

						$low  = empty( $banners[ 'low' ] ) ? $banners[ 'high' ] : $banners[ 'low' ];
						$high = empty( $banners[ 'high' ] ) ? $banners[ 'low' ] : $banners[ 'high' ];
					
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

					/* Hide the plugin name overlay. */
					body#plugin-information div#plugin-information-title.with-banner h2 {
						display:none;
					}

					body#plugin-information div#plugin-information-content div#section-holder.wrap {
						clear:none;
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
