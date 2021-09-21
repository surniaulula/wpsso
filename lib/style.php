<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoStyle' ) ) {

	class WpssoStyle {

		private $p;	// Wpsso class object.

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
			$this->version   = WpssoConfig::get_version() . ( $this->doing_dev ? gmdate( '-ymd-His' ) : '' );

			if ( is_admin() ) {

				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), WPSSO_ADMIN_SCRIPTS_PRIORITY );
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
			wp_register_style( 'jquery-ui.js', 'https://ajax.googleapis.com/ajax/libs/jqueryui/' . 
				$this->p->cf[ 'jquery-ui' ][ 'version' ] . '/themes/smoothness/jquery-ui.css',
					$deps = array(), $this->p->cf[ 'jquery-ui' ][ 'version' ] );

			/**
			 * Register styles for option help popup.
			 * 
			 * See http://qtip2.com/download.
			 */
			wp_register_style( 'jquery-qtip.js', WPSSO_URLPATH . 'css/ext/jquery-qtip.' . $this->file_ext,
				$deps = array(), $this->p->cf[ 'jquery-qtip' ][ 'version' ] );

			/**
			 * Register styles for settings pages.
			 */
			wp_register_style( 'sucom-settings-page', WPSSO_URLPATH . 'css/com/settings-page.' . $this->file_ext,
				$deps = array(), $this->version );

			/**
			 * Register styles for settings tables.
			 */
			wp_register_style( 'sucom-settings-table', WPSSO_URLPATH . 'css/com/settings-table.' . $this->file_ext,
				$deps = array(), $this->version );

			/**
			 * Register styles for metabox tabs.
			 */
			wp_register_style( 'sucom-metabox-tabs', WPSSO_URLPATH . 'css/com/metabox-tabs.' . $this->file_ext,
				$deps = array( 'wp-color-picker' ), $this->version );

			/**
			 * Only load stylesheets we need.
			 */
			switch ( $hook_name ) {

				/**
				 * Addons and license settings page.
				 */
				case ( preg_match( '/_page_wpsso-.*(addons|licenses)/', $hook_name ) ? true : false ):

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'enqueuing styles for addons and licenses page' );
					}

					add_filter( 'admin_body_class', array( $this, 'add_plugins_body_class' ) );

					// no break

				/**
				 * Any settings page. Also matches the profile_page and users_page hooks.
				 */
				case ( false !== strpos( $hook_name, '_page_wpsso-' ) ? true : false ):

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'enqueuing styles for settings page' );
					}

					wp_enqueue_style( 'sucom-settings-page' );

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

			$this->admin_register_page_styles( $hook_name );

			$this->admin_enqueue_page_styles( $hook_name );
		}

		public function add_plugins_body_class( $classes ) {

			$classes .= ' plugins-php';

			return $classes;
		}

		private function admin_register_page_styles( $hook_name ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$filter_name   = 'wpsso_admin_page_style_css';
			$filter_ids    = SucomUtilWP::get_filter_hook_ids( $filter_name );
			$sortable_cols = WpssoWpMeta::get_sortable_columns();	// Uses a static cache.

			$cache_md5_pre    = 'wpsso_';
			$cache_exp_filter = 'wpsso_cache_expire_admin_css';
			$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, DAY_IN_SECONDS );

			$cache_salt = __METHOD__ . '(';
			$cache_salt .= '_version:' . $this->version;
			$cache_salt .= '_filters:' . implode( ',', array_keys( $filter_ids ) );
			$cache_salt .= '_columns:' . implode( ',', array_keys( $sortable_cols ) );
			$cache_salt .= ')';
			$cache_id   = $cache_md5_pre . md5( $cache_salt );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'enqueueing style sucom-admin-page' );
			}

			wp_register_style( 'sucom-admin-page', WPSSO_URLPATH . 'css/com/admin-page.' . $this->file_ext,
				$deps = array(), $this->version );

			if ( $this->use_cache ) {

				if ( $custom_style_css = get_transient( $cache_id ) ) {	// not empty

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding sucom-admin-page inline style from cache' );
					}

					wp_add_inline_style( 'sucom-admin-page', $custom_style_css );	// Since WP v3.3.0.

					return;
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'create and minify admin page style' );	// Begin timer.
			}

			$metabox_id = $this->p->cf[ 'meta' ][ 'id' ];
			$menu       = 'wpsso-' . key( $this->p->cf[ '*' ][ 'lib' ][ 'submenu' ] );
			$sitemenu   = 'wpsso-' . key( $this->p->cf[ '*' ][ 'lib' ][ 'sitesubmenu' ] );

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
				#wpadminbar #wp-toolbar #wpsso-toolbar-notices-icon.ab-icon::before {
					content:"' . $this->p->cf[ 'notice' ][ 'icon-code' ] . '";
					font-family:'. $this->p->cf[ 'notice' ][ 'icon-font' ] . ';
				}
			';

			foreach ( $this->p->cf[ 'notice' ][ 'css-class' ] as $css_class => $css_props ) {

				foreach ( $css_props as $prop_name => $prop_value ) {

					$custom_style_css .= '.wpsso-notice.' . $css_class . '{' . $prop_name . ':' . $prop_value . ';}' . "\n";
				}
			}

			/**
			 * Admin nag notices.
			 */
			$custom_style_css .= '
				.wpsso-notice.update-nag .notice-message {
					padding:15px 30px;
				}
				.wpsso-notice.update-nag .notice-message p,
				.wpsso-notice.update-nag .notice-message ul,
				.wpsso-notice.update-nag .notice-message ol {
					margin:15px 0;
				}
				.wpsso-notice.update-nag .notice-message ul li {
					list-style-type:square;
				}
				.wpsso-notice.update-nag .notice-message ol li {
					list-style-type:decimal;
				}
				.wpsso-notice.update-nag .notice-message li {
					margin:5px 0 5px 60px;
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
					font-family:'. $this->p->cf[ 'menu' ][ 'icon-font' ] . ';
				}
				#adminmenu #toplevel_page_' . $menu . ' ul > li > a,
				#adminmenu #toplevel_page_' . $sitemenu . ' ul > li > a {
					padding:6px 8px;	/* Default is 6px 12px. */
				}
				#adminmenu ul.wp-submenu div.wpsso-menu-item {
					display:table-cell;
				}
				#adminmenu ul.wp-submenu div.wpsso-menu-item.dashicons-before {
					max-width:1.3em;
					padding-right:6px;
				}
				#adminmenu ul.wp-submenu div.wpsso-menu-item.dashicons-before::before {
					font-size:1.3em;
					text-align:left;
					opacity:0.5;
					filter:alpha(opacity=50);	/* IE8 and earlier. */
				}
				#adminmenu ul.wp-submenu div.wpsso-menu-item.menu-item-label {
					width:100%;
				}
				#adminmenu ul.wp-submenu div.wpsso-menu-item.wpsso-setup,
				#adminmenu ul.wp-submenu div.wpsso-menu-item.top-last-submenu-page.with-add-ons {
					padding-bottom:12px;
					border-bottom:1px solid;
				}
			';

			/**
			 * Settings pages.
			 */
			$custom_style_css .= '
				#profile-page.wrap #your-profile #wpsso_' . $metabox_id . '.postbox h3:first-of-type {
					margin:0;
				}
				#wpsso_' . $metabox_id . '.postbox {
					min-width:455px;	/* The default WordPress postbox minimum width is 255px. */
				}
				#wpsso_' . $metabox_id . ' .inside {
					padding:0;
					margin:0;
				}
				.wpsso-rate-heart {
					color:red;
					font-size:1.5em;
					vertical-align:top;
				}
				.wpsso-rate-heart::before {
					content:"\2665";	/* Heart. */
				}
			';

			/**
			 * List table columns.
			 */
			foreach ( array(
				'.column-title' => 'width:30%; max-width:15vw;',
				'.column-name'  => 'width:30%; max-width:15vw;',
				''              => 'width:15%; max-width:15vw;',
			) as $css_class => $width_css ) {

				$custom_style_css .= "\n@media screen and ( min-width:783px ) {\n";	// Open code block.

				switch ( $css_class ) {

					case '':	// Only apply to posts and pages.

						$custom_style_css .= "\ttable.wp-list-table.posts > thead > tr > th,\n";
						$custom_style_css .= "\ttable.wp-list-table.posts > tbody > tr > td,\n";
						$custom_style_css .= "\ttable.wp-list-table.pages > thead > tr > th,\n";
						$custom_style_css .= "\ttable.wp-list-table.pages > tbody > tr > td {\n";	// Open code block.

						break;

					default:	// Apply to every WP List Table.

						$custom_style_css .= "\t" . 'table.wp-list-table.posts > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table.posts > tbody > tr > td' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table.pages > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table.pages > tbody > tr > td' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table > thead > tr > th' . $css_class . ",\n";
						$custom_style_css .= "\t" . 'table.wp-list-table > tbody > tr > td' . $css_class . " {\n";	// Open code block.

						break;
				}

				$custom_style_css .= "\t\t" . $width_css . "\n";
				$custom_style_css .= "\t}\n";	// Close code block.
				$custom_style_css .= "}\n";	// Close code block.
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
				table.wp-list-table > thead > tr > th.column-taxonomy-page_tag,
				table.wp-list-table > tbody > tr > td.column-taxonomy-page_tag,
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
				table.wp-list-table.tags > tbody > tr > td.column-description {		/* Taxonomy table */
					width:25%;
				}
				table.wp-list-table.users > thead > tr > th,
				table.wp-list-table.users > tbody > tr > td {			/* Users table. */
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
				table.wp-list-table > tbody > tr > td.column-posts.num {	/* Counter. */
					width:80px;
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
				table.wp-list-table > thead > tr > th.column-job_position,
				table.wp-list-table > tbody > tr > td.column-job_position {	/* WP Job Manager. */
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
				table.wp-list-table > thead > tr > th.column-seotitle,
				table.wp-list-table > tbody > tr > td.column-seotitle,
				table.wp-list-table > thead > tr > th.column-seodesc,
				table.wp-list-table > tbody > tr > td.column-seodesc {		/* All in One SEO. */
					width:20%;
				}
				table.wp-list-table > thead > tr > th.column-shortcode,
				table.wp-list-table > tbody > tr > td.column-shortcode {
					width:25%;
				}
				table.wp-list-table > thead > tr > th.column-term-id,
				table.wp-list-table > tbody > tr > td.column-term-id {
					width:40px;
				}
				table.wp-list-table > thead > tr > th.column-thumb,
				table.wp-list-table > tbody > tr > td.column-thumb {		/* WooCommerce Brands */
					width:60px;
				}
				table.wp-list-table > thead > tr > th.column-template,
				table.wp-list-table > tbody > tr > td.column-template {
				        width:10%;
				}
			';

			if ( isset( $sortable_cols[ 'schema_type' ][ 'width' ] ) ) {

				$custom_style_css .= '
					.column-wpsso_schema_type {
						width:' . $sortable_cols[ 'schema_type' ][ 'width' ] . ';
						max-width:' . $sortable_cols[ 'schema_type' ][ 'width' ] . ';
						white-space:nowrap;
						overflow:hidden;
					}
				';
			}

			if ( isset( $sortable_cols[ 'og_type' ][ 'width' ] ) ) {

				$custom_style_css .= '
					.column-wpsso_og_type {
						width:' . $sortable_cols[ 'og_type' ][ 'width' ] . ' !important;
						max-width:' . $sortable_cols[ 'og_type' ][ 'width' ] . ' !important;
						white-space:nowrap;
						overflow:hidden;
					}
				';
			}

			if ( isset( $sortable_cols[ 'og_img' ][ 'width' ] ) ) {

				$custom_style_css .= '
					.column-wpsso_og_img {
						width:' . $sortable_cols[ 'og_img' ][ 'width' ] . ' !important;
						max-width:' . $sortable_cols[ 'og_img' ][ 'width' ] . ' !important;
					}
				';

				if ( isset( $sortable_cols[ 'og_img' ][ 'height' ] ) ) {	// Just in case.

					$custom_style_css .= '
						.column-wpsso_og_img div.wp-thumb-bg-img {
							max-width:' . $sortable_cols[ 'og_img' ][ 'width' ] . ' !important;
							height:' . $sortable_cols[ 'og_img' ][ 'height' ] . ';
							min-height:' . $sortable_cols[ 'og_img' ][ 'height' ] . ';
							background-size:' . $sortable_cols[ 'og_img' ][ 'width' ] . ' auto;
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
				.column-wpsso_og_desc {
					overflow:hidden;
				}

				td.column-wpsso_schema_type,
				td.column-wpsso_og_type,
				td.column-wpsso_og_desc {
					direction:ltr;
					font-family:"Helvetica";
					text-align:left;
					word-wrap:break-word;
				}

				@media screen and ( max-width:1295px ) {

					th.column-wpsso_og_desc,
					td.column-wpsso_og_desc {
						display:none;
					}
				}
			';

			foreach ( $sortable_cols as $col_name => $col_info ) {

				if ( isset( $col_info[ 'width' ] ) ) {

					$custom_style_css .= '
						table.wp-list-table > thead > tr > th.column-wpsso_' . $col_name . ',
						table.wp-list-table > tbody > tr > td.column-wpsso_' . $col_name . ' {
							width:' . $col_info[ 'width' ] . ' !important;
							min-width:' . $col_info[ 'width' ] . ' !important;
						}
					';
				}
			}

			$custom_style_css = apply_filters( $filter_name, $custom_style_css );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'saving sucom-admin-page inline style to cache' );
			}

			if ( $this->use_cache ) {

				if ( method_exists( 'SucomUtil', 'minify_css' ) ) {

					$custom_style_css = SucomUtil::minify_css( $custom_style_css, $ext = 'wpsso' );
				}

				set_transient( $cache_id, $custom_style_css, $cache_exp_secs );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding sucom-admin-page inline style' );
			}

			wp_add_inline_style( 'sucom-admin-page', $custom_style_css );	// Since WP v3.3.0.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'create and minify admin page style' );	// End timer.
			}
		}

		/**
		 * Since WPSSO Core v8.5.1.
		 *
		 * This method is run a second time by the 'admin_enqueue_scripts' action with a priority of PHP_INT_MAX to make
		 * sure another plugin (like Squirrly SEO) has not cleared our admin page styles from the queue.
		 */
		public function admin_enqueue_page_styles( $hook_name ) {

			$style_handles = array( 'sucom-admin-page' );

			static $enqueued = null;	// Default value for first execution.

			if ( ! $enqueued ) {	// Re-check the $wp_styles queue at priority PHP_INT_MAX.

				add_action( 'admin_enqueue_scripts', array( $this, __FUNCTION__ ), PHP_INT_MAX );
			}

			global $wp_styles;

			foreach ( $style_handles as $handle ) {

				if ( ! $enqueued || ! isset( $wp_styles->queue ) || ! in_array( $handle, $wp_styles->queue ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'enqueueing style ' . $handle );
					}

					wp_enqueue_style( $handle );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( $handle . ' style already enqueued' );
				}
			}

			$enqueued = true;	// Signal that we've already run once.
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

					echo '<style type="text/css">' . "\n";

					/**
					 * Banner image array keys are 'low' and 'high'.
					 */
					if ( ! empty( $banners[ 'low' ] ) ) {

						echo '#plugin-information #plugin-information-title.with-banner { '.
							'background-image: url( ' . esc_url( $banners[ 'low' ] ) . ' ); }' . "\n";
					}

					if ( ! empty( $banners[ 'high' ] ) ) {

						echo '@media (-webkit-min-device-pixel-ratio: 1.5), (min-resolution: 144dpi) { ' .
							'#plugin-information #plugin-information-title.with-banner { ' .
							'background-image: url( ' . esc_url( $banners[ 'high' ] ) . ' ); } }' . "\n";
					}

					echo '</style>' . "\n";
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
