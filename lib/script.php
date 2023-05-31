<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoScript' ) ) {

	class WpssoScript {

		private $p;	// Wpsso class object.

		private $doing_dev  = false;
		private $file_ext   = 'min.js';
		private $version    = '';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->doing_dev = SucomUtil::get_const( 'WPSSO_DEV' );
			$this->file_ext  = $this->doing_dev ? 'js' : 'min.js';
			$this->version   = WpssoConfig::get_version() . ( $this->doing_dev ? gmdate( '-ymd-His' ) : '' );

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( is_admin() ) {
			
				$this->on_async_upload_update_toolbar_script();

				if ( ! $doing_ajax ) {

					add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ), WPSSO_BLOCK_ASSETS_PRIORITY );

					add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), WPSSO_ADMIN_SCRIPTS_PRIORITY );

					add_action( 'admin_head', array( $this, 'on_load_update_toolbar_script' ) );
				}
			}
		}

		public function enqueue_block_editor_assets() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( SucomUtil::is_post_page() ) {

				$src = WPSSO_URLPATH . 'js/jquery-block-editor.' . $this->file_ext;

				/*
				 * The 'wp-editor' dependency should not be enqueued together with the new widgets block editor.
				 */
				$deps = array( 'wp-data', 'wp-editor', 'wp-edit-post', 'sucom-admin-page' );

				/*
				 * The 'wpsso-block-editor' script, with its 'wp-edit-post' dependency, must be loaded in the
				 * footer to work around a bug in the NextGEN Gallery featured image picker. If the script is
				 * loaded in the header, with a dependency on 'wp-edit-post', the NextGEN Gallery featured image
				 * picker does not load.
				 */
				$in_footer = true;

				wp_register_script( 'wpsso-block-editor', $src, $deps, $this->version, $in_footer );

				wp_enqueue_script( 'wpsso-block-editor' );
			}
		}

		public function admin_enqueue_scripts( $hook_name ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'hook name = ' . $hook_name );
				$this->p->debug->log( 'screen base = ' . SucomUtil::get_screen_base() );
			}

			/*
			 * See http://qtip2.com/download.
			 */
			wp_register_script( 'jquery-qtip', WPSSO_URLPATH . 'js/ext/jquery-qtip.' . $this->file_ext,
				$deps = array( 'jquery' ), $this->p->cf[ 'jquery-qtip' ][ 'version' ], $in_footer = true );

			/*
			 * Provides sucomInitAdminMedia().
			 */
			wp_register_script( 'sucom-admin-media', WPSSO_URLPATH . 'js/com/jquery-admin-media.' . $this->file_ext,
				$deps = array( 'jquery', 'jquery-ui-core' ), $this->version, $in_footer = true );

			wp_localize_script( 'sucom-admin-media', 'sucomAdminMediaL10n', $this->get_admin_media_script_data() );

			/*
			 * Provides sucomInitMetabox().
			 */
			wp_register_script( 'sucom-metabox', WPSSO_URLPATH . 'js/com/jquery-metabox.' . $this->file_ext,
				$deps = array( 'jquery', 'jquery-ui-datepicker', 'wp-color-picker', 'sucom-admin-page' ),
					$this->version, $in_footer = true );

			/*
			 * Provides sucomInitToolTips().
			 */
			wp_register_script( 'sucom-tooltips', WPSSO_URLPATH . 'js/com/jquery-tooltips.' . $this->file_ext,
				$deps = array( 'jquery', 'jquery-qtip' ), $this->version, $in_footer = true );

			/*
			 * Provides wpssoInitMetabox().
			 */
			wp_register_script( 'wpsso-metabox', WPSSO_URLPATH . 'js/jquery-metabox.' . $this->file_ext,
				$deps = array( 'sucom-metabox' ), $this->version, $in_footer = true );

			/*
			 * Only load scripts where we need them.
			 */
			switch ( $hook_name ) {

				/*
				 * Addons and license settings page.
				 */
				case ( preg_match( '/_page_wpsso-.*(addons|licenses)/', $hook_name ) ? true : false ) :

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'enqueuing scripts for addons and licenses page' );
					}

					add_thickbox();	// Required for the plugin details box.

					wp_enqueue_script( 'plugin-install' );	// Required for the plugin details box.

					// No break.

				/*
				 * Any settings page. Also matches the profile_page and users_page hooks.
				 */
				case ( false !== strpos( $hook_name, '_page_wpsso-' ) ? true : false ):

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'enqueuing scripts for settings page' );
					}

					wp_enqueue_script( 'sucom-settings-page' );

					// No break.

				/*
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

						$this->p->debug->log( 'enqueuing scripts for editing page' );
					}

					wp_enqueue_script( 'sucom-tooltips' );

					wp_enqueue_script( 'wpsso-metabox' );

					if ( function_exists( 'wp_enqueue_media' ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'wp_enqueue_media() function is available' );
						}

						if ( SucomUtil::is_post_page( false ) && ( $post_id = SucomUtil::get_post_object( false, 'id' ) ) > 0 ) {

							wp_enqueue_media( array( 'post' => $post_id ) );

						} else {

							wp_enqueue_media();
						}

						wp_enqueue_script( 'sucom-admin-media' );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wp_enqueue_media() function not found' );
					}

					do_action( 'wpsso_admin_enqueue_scripts_editing_page', $hook_name, $this->file_ext );

					break;	// Stop here.

				case 'plugin-install.php':

					if ( isset( $_GET[ 'plugin' ] ) ) {

						$plugin_slug = $_GET[ 'plugin' ];

						if ( isset( $this->p->cf[ '*' ][ 'slug' ][ $plugin_slug ] ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'enqueuing scripts for plugin install page' );
							}

							$this->add_plugin_install_iframe_script( $hook_name );
						}
					}

					break;
			}

			$this->admin_register_page_scripts( $hook_name );

			$this->admin_enqueue_page_scripts( $hook_name );
		}

		/*
		 * Add jQuery to update the toolbar menu item on page load.
		 *
		 * Hooked to the WordPress 'admin_footer' action.
		 */
		public function on_load_update_toolbar_script() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$toolbar_notice_types = $this->p->notice->get_toolbar_types();

			if ( empty( $toolbar_notice_types ) || ! is_array( $toolbar_notice_types ) ) {

				if ( ! empty( $this->p->debug->enabled ) ) {

					$this->p->debug->log( 'exiting early: no toolbar notice types defined' );
				}

				return;
			}

			/*
			 * Exit early if this is a block editor page.
			 *
			 * Notices will be retrieved using an ajax call during editor page load and post save.
			 */
			if ( SucomUtilWP::doing_block_editor() ) {

				if ( ! empty( $this->p->debug->enabled ) ) {

					$this->p->debug->log( 'exiting early: doing block editor is true' );
				}

				return;
			}

			if ( ! empty( $this->p->debug->enabled ) ) {

				$this->p->debug->log( 'doing block editor is false' );
			}

			/*
			 * jQuery() or jQuery( document ).on( 'ready' ) executes when HTML-Document is loaded and DOM is ready.
			 *
			 * jQuery( window ).on( 'load' ) executes when page is fully loaded, including all frames, objects and images.
			 *
			 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
			 */
			$admin_l10n = $this->p->cf[ 'plugin' ][ 'wpsso' ][ 'admin_l10n' ];

			echo '<script>';
			echo 'jQuery( window ).on( \'load\', function(){';
			echo ' if ( \'function\' === typeof sucomToolbarNotices ) {';
			echo '  sucomToolbarNotices( \'wpsso\', \'' . $admin_l10n . '\' );';
			echo ' }';
			echo '});';
			echo '</script>' . "\n";
		}

		public function on_async_upload_update_toolbar_script() {

			if ( 'async-upload' === basename( $_SERVER[ 'PHP_SELF' ], '.php' ) ) {

				if ( isset( $_REQUEST[ 'attachment_id' ] ) && (int) $_REQUEST[ 'attachment_id' ] && ! empty( $_REQUEST['fetch'] ) ) {

					$admin_l10n = $this->p->cf[ 'plugin' ][ 'wpsso' ][ 'admin_l10n' ];

					echo '<script>';
					echo 'if ( \'function\' === typeof sucomToolbarNotices ) {';
					echo ' sucomToolbarNotices( \'wpsso\', \'' . $admin_l10n . '\' );';
					echo '}';
					echo '</script>' . "\n";
				}
			}
		}

		/*
		 * Add jQuery to correctly follow the Install / Update link when clicked (WordPress bug). Also adds the parent URL
		 * and settings page title as query arguments, which are then used by WpssoAdmin class filters to return the user
		 * back to the settings page after installing / activating / updating the plugin.
		 */
		private function add_plugin_install_iframe_script( $hook_name ) {	// $hook_name = plugin-install.php

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			wp_enqueue_script( 'plugin-install' );	// Required for the plugin details box.

			/*
			 * Fix the update / install button to load the href when clicked.
			 *
			 * jQuery() or jQuery( document ).on( 'ready' ) executes when HTML-Document is loaded and DOM is ready.
			 */
			$custom_script_js = <<<EOF
jQuery( function(){

	jQuery( 'body#plugin-information.iframe a[id$=_from_iframe]' ).on( 'click', function(){

		if ( window.top.location.href.indexOf( 'page=wpsso-' ) ){

			var plugin_url        = jQuery( this ).attr( 'href' );
			var pageref_url_arg   = '&wpsso_pageref_url=' + encodeURIComponent( window.top.location.href );
			var pageref_title_arg = '&wpsso_pageref_title=' + encodeURIComponent( jQuery( 'h1', window.parent.document ).text() );

			window.top.location.href = plugin_url + pageref_url_arg + pageref_title_arg;
		}
	});
});
EOF;

			if ( function_exists( 'wp_add_inline_script' ) ) {	// Since WP v4.5.0.

				wp_add_inline_script( 'plugin-install', $custom_script_js );

			} else {

				/*
				 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
				 */
				echo '<script>' . "\n" . $custom_script_js . '</script>' . "\n";
			}
		}

		/*
		 * sucomAdminMediaL10n array for the 'sucom-admin-media' script located in js/com/jquery-admin-media.js.
		 */
		public function get_admin_media_script_data() {

			/*
			 * var sucomAdminMediaL10n array.
			 */
			return array(
				'_select_image' => __( 'Select Image', 'wpsso' ),
			);
		}

		public function admin_register_page_scripts( $hook_name ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'registering script sucom-admin-page' );
			}

			$admin_l10n = $this->p->cf[ 'plugin' ][ 'wpsso' ][ 'admin_l10n' ];

			// The version number should match the version in js/com/jquery-admin-page.js.
			wp_register_script( 'sucom-admin-page', WPSSO_URLPATH . 'js/com/jquery-admin-page.' . $this->file_ext,
				$deps = array( 'jquery' ), '20230531', $in_footer = true );

			wp_localize_script( 'sucom-admin-page', $admin_l10n, $this->get_admin_page_script_data() );
		}

		/*
		 * Since WPSSO Core v8.5.1.
		 *
		 * This method is run a second time by the 'admin_enqueue_scripts' action with a priority of PHP_INT_MAX to make
		 * sure another plugin (like Squirrly SEO) has not cleared our admin page scripts from the queue.
		 */
		public function admin_enqueue_page_scripts( $hook_name ) {

			$script_handles = array( 'sucom-admin-page', 'jquery' );

			static $is_enqueued = null;	// Default value for first execution.

			if ( ! $is_enqueued ) {	// Re-check the $wp_scripts queue at priority PHP_INT_MAX.

				add_action( 'admin_enqueue_scripts', array( $this, __FUNCTION__ ), PHP_INT_MAX );
			}

			global $wp_scripts;

			foreach ( $script_handles as $handle ) {

				if ( ! $is_enqueued || ! isset( $wp_scripts->queue ) || ! in_array( $handle, $wp_scripts->queue ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'enqueueing script ' . $handle );
					}

					wp_enqueue_script( $handle );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( $handle . ' script already enqueued' );
				}
			}

			$is_enqueued = true;	// Signal that we've already run once.
		}

		/*
		 * The config array for the 'sucom-admin-page' script located in js/com/jquery-admin-page.js.
		 */
		public function get_admin_page_script_data() {

			$admin_l10n = $this->p->cf[ 'plugin' ][ 'wpsso' ][ 'admin_l10n' ];

			$option_labels = apply_filters( 'wpsso_admin_page_script_data_option_labels', array(
				'schema_type' => _x( 'Schema Type', 'option label', 'wpsso' ),
			) );

			/*
			 * noticeTime = -1 to skip automatically showing notifications.
			 * noticeTime = 0 to automatically show notifications until click or hover.
			 * noticeTime = milliseconds to automatically show notifications.
			 */
			$toolbar_notice_timeout = array(
				'err'  => 2000,
				'warn' => 2000,
				'inf'  => 1500,
				'upd'  => 1000,
			);

			$toolbar_notice_types = $this->p->notice->get_toolbar_types();
			$notice_text_uniqid   = 'wpsso_' . uniqid();	// CSS id of hidden notice text container.
			$no_notices_transl    = sprintf( __( 'No %s notifications.', 'wpsso' ), $this->p->cf[ 'menu' ][ 'title' ] );
			$no_notices_html      = '<div class="ab-item ab-empty-item">' . $no_notices_transl . '</div>';
			$copy_notices_transl  = __( 'Copy notifications to clipboard.', 'wpsso' );

			/*
			 * Add an 'inline' class to toolbar notices to prevent WordPress from moving the notice.
			 *
			 * See wordpress/wp-admin/js/common.js:1083
			 */
			$copy_notices_html   = '<div class="wpsso-notice notice notice-alt inline notice-copy" style="display:block !important;">' .
				'<div class="notice-message">' .
				'<a href="" onClick="return sucomCopyById( \'' . $notice_text_uniqid . '\', \'' . $admin_l10n . '\' );">' . $copy_notices_transl . '</a>' .
				'</div><!-- .notice-message -->' .
				'</div><!-- .notice-copy -->';

			/*
			 * The config array.
			 */
			$metabox_id = $this->p->cf[ 'meta' ][ 'id' ];

			return array(
				'_ajax_nonce'   => wp_create_nonce( WPSSO_NONCE_NAME ),
				'_ajax_actions' => array(
					'schema_type_og_type'  => 'wpsso_schema_type_og_type',
					'get_notices_json'     => 'wpsso_get_notices_json',
					'get_validate_submenu' => 'wpsso_get_validate_submenu',
					'metabox_postboxes'    => array(
						'wpsso_' . $metabox_id => 'wpsso_get_metabox_postbox_id_' . $metabox_id . '_inside',
					),
				),
				'_option_labels'          => $option_labels,
				'_toolbar_notice_timeout' => $toolbar_notice_timeout,
				'_toolbar_notice_types'   => $toolbar_notice_types,	// Maybe null, true, false, or array.
				'_notice_text_id'         => $notice_text_uniqid,	// CSS id of hidden notice text container.
				'_no_notices_html'        => $no_notices_html,
				'_copy_notices_html'      => $copy_notices_html,
				'_copy_clipboard_transl'  => __( 'Copied to clipboard.', 'wpsso' ),
				'_linked_to_transl'       => __( 'Value linked to {0} option', 'wpsso' ),
				'_count_msgs_transl'      => array(
					'error' => sprintf( __( 'There are {0} important error messages under the %s notification icon.', 'wpsso' ),
						$this->p->cf[ 'notice' ][ 'title' ] ),
				),
				'_min_len_transl' => __( '{0} of {1} characters minimum', 'wpsso' ),
				'_req_len_transl' => __( '{0} of {1} characters recommended', 'wpsso' ),
				'_max_len_transl' => __( '{0} of {1} characters maximum', 'wpsso' ),
				'_len_transl'     => __( '{0} characters', 'wpsso' ),
			);
		}
	}
}
