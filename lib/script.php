<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoScript' ) ) {

	class WpssoScript {

		private $p;
		private $tb_notices;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! SucomUtil::get_const( 'DOING_AJAX' ) ) {

				if ( is_admin() ) {

					/**
					 * Define which notice types, if any, will be shown in the toolbar menu.
					 * WPSSO_TOOLBAR_NOTICES can be true, false, or an array of notice types to include in the menu.
					 */
					$this->tb_notices = SucomUtil::get_const( 'WPSSO_TOOLBAR_NOTICES', false );

					if ( $this->tb_notices === true ) {
						$this->tb_notices = array( 'err', 'warn', 'inf' );
					}

					if ( empty( $this->tb_notices ) || ! is_array( $this->tb_notices ) ) {	// Quick sanity check.
						$this->tb_notices = false;
					}

					add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ), -1000 );
					add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), -1000 );

					/**
					 * Add jQuery to update the toolbar menu item counter and container on page load.
					 */
					if ( ! empty( $this->tb_notices ) ) {
						add_action( 'admin_footer', array( $this, 'add_update_tb_notices_script' ) );
					}
				}
			}
		}

		public function admin_enqueue_scripts( $hook_name ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'hook name = '.$hook_name );
				$this->p->debug->log( 'screen base = '.SucomUtil::get_screen_base() );
			}

			$js_file_ext = SucomUtil::get_const( 'WPSSO_DEV' ) ? 'js' : 'min.js';
			$plugin_version = WpssoConfig::get_version();

			/**
			 * See http://qtip2.com/download.
			 */
			wp_register_script( 'jquery-qtip', 
				WPSSO_URLPATH . 'js/ext/jquery-qtip.' . $js_file_ext, 
					array( 'jquery' ), $this->p->cf['jquery-qtip']['version'], true );

			wp_register_script( 'sucom-settings-page', 
				WPSSO_URLPATH . 'js/com/jquery-settings-page.' . $js_file_ext, 
					array( 'jquery' ), $plugin_version, true );

			wp_register_script( 'sucom-metabox', 
				WPSSO_URLPATH . 'js/com/jquery-metabox.' . $js_file_ext, 
					array( 'jquery' ), $plugin_version, true );

			wp_register_script( 'sucom-tooltips', 
				WPSSO_URLPATH . 'js/com/jquery-tooltips.' . $js_file_ext, 
					array( 'jquery' ), $plugin_version, true );

			wp_register_script( 'sucom-admin-media', 
				WPSSO_URLPATH . 'js/com/jquery-admin-media.' . $js_file_ext, 
					array( 'jquery', 'jquery-ui-core' ), $plugin_version, true );

			/**
			 * Only load scripts where we need them.
			 */
			switch ( $hook_name ) {

				/**
				 * Addons and license settings page.
				 */
				case ( preg_match( '/_page_' . $this->p->lca . '-(site)?(addons|licenses)/', $hook_name ) ? true : false ) :

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing scripts for addons and licenses page' );
					}

					add_thickbox();	// required for the plugin details box

					wp_enqueue_script( 'plugin-install' );	// required for the plugin details box

					// no break

				/**
				 * Any settings page. Also matches the profile_page and users_page hooks.
				 */
				case ( strpos( $hook_name, '_page_' . $this->p->lca . '-' ) !== false ? true : false ):

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing scripts for settings page' );
					}

					wp_enqueue_script( 'sucom-settings-page' );

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
						$this->p->debug->log( 'enqueuing scripts for editing page' );
					}

					wp_enqueue_script( 'jquery-ui-datepicker' );
					wp_enqueue_script( 'jquery-qtip' );
					wp_enqueue_script( 'sucom-metabox' );
					wp_enqueue_script( 'sucom-tooltips' );
					wp_enqueue_script( 'wp-color-picker' );

					if ( function_exists( 'wp_enqueue_media' ) ) {	// since wp 3.5.0
						if ( SucomUtil::is_post_page( false ) &&
							( $post_id = SucomUtil::get_post_object( false, 'id' ) ) > 0 ) {

							wp_enqueue_media( array( 'post' => $post_id ) );
						} else {
							wp_enqueue_media();
						}

						wp_enqueue_script( 'sucom-admin-media' );

						wp_localize_script( 'sucom-admin-media', 'sucomMediaL10n',
							$this->get_admin_media_script_data() );
					}

					break;	// stop here

				case 'plugin-install.php':

					if ( isset( $_GET['plugin'] ) ) {
						$plugin_slug = $_GET['plugin'];
						if ( isset( $this->p->cf['*']['slug'][$plugin_slug] ) ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'enqueuing scripts for plugin install page' );
							}
							$this->add_plugin_install_iframe_script( $hook_name );
						}
					}

					break;
			}

			wp_enqueue_script( 'jquery' );	// required for dismissible notices
		}

		/**
		 * Start localized variable names with an underscore.
		 */
		public function get_admin_media_script_data() {
			return array(
				'_select_image' => __( 'Select Image', 'wpsso' ),
			);
		}

		public function enqueue_block_editor_assets() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$js_file_ext = SucomUtil::get_const( 'WPSSO_DEV' ) ? 'js' : 'min.js';
			$plugin_version = WpssoConfig::get_version();

			wp_enqueue_script( 'sucom-gutenberg-admin', 
				WPSSO_URLPATH . 'js/gutenberg-admin.' . $js_file_ext, 
					array( 'wp-data' ), $plugin_version, true );

			wp_localize_script( 'sucom-gutenberg-admin', 'sucomGutenbergL10n',
				$this->get_admin_gutenberg_script_data() );
		}

		/**
		 * Start localized variable names with an underscore.
		 */
		public function get_admin_gutenberg_script_data() {

			$no_notices_text = sprintf( __( 'No new %s notifications.', 'wpsso' ), $this->p->cf['menu']['title'] );
			$no_notices_html = '<div class="ab-item ab-empty-item">' . $no_notices_text . '</div>';

			return array(
				'_ajax_nonce' => wp_create_nonce( WPSSO_NONCE_NAME ),
				'_metabox_id' => $this->p->lca . '_metabox_' . $this->p->cf['meta']['id'],
				'_tb_notices' => $this->tb_notices,
				'_no_notices_html' => $no_notices_html,
				'_option_labels' => array(
					'robots' => _x( 'Robots', 'option label', 'wpsso' ),
				),
			);
		}

		public function add_update_tb_notices_script() {

			$doing_block_editor = defined( 'DOING_BLOCK_EDITOR' ) ? DOING_BLOCK_EDITOR : false;

			/**
			 * Exit early if this is a block editor (aka Gutenberg) page.
			 * The notices will be retrieved using an ajax call on page load and post save.
			 */
			if ( $doing_block_editor ) {
				return;
			}

			/**
			 * Just in case - no use getting notices if there's nothing to get.
			 */
			if ( empty( $this->tb_notices ) ) {
				return;
			}

			$no_notices_text = sprintf( __( 'No new %s notifications.', 'wpsso' ), $this->p->cf['menu']['title'] );
			$no_notices_html = '<div class="ab-item ab-empty-item">' . $no_notices_text . '</div>';

			?>
			<script type="text/javascript">
				jQuery( document ).ready( function() {

						// Just in case - make sure the same block editor function does not exist.
						if ( typeof wpssoUpdateToolbar == 'function' ) {
							return;
						}

						var ajaxNoticesData = {
							action: 'wpsso_get_notices_json',
							context: 'toolbar_notices',
							_ajax_nonce: '<?php echo wp_create_nonce( WPSSO_NONCE_NAME ); ?>',
							_notice_types: <?php echo json_encode( $this->tb_notices ); ?>,
						}

						jQuery.getJSON( ajaxurl, ajaxNoticesData, function( data ) {

							var noticeHtml       = '';
							var noticeStatus     = '';
							var noticeTotalCount = 0;
							var noticeTypeCount  = {};
							var noNoticesHtml    = '<?php echo $no_notices_html; ?>';

							jQuery.each( data, function( noticeType ) {

								jQuery.each( data[noticeType], function( noticeKey ) {

									// Skip hidden notices that we can't unhide.
									if ( data[noticeType][noticeKey]['hidden'] && 
										data[noticeType][noticeKey]['no-unhide'] ) {
										return;
									}

									noticeHtml += data[noticeType][noticeKey]['msg_html'];

									if ( ! data[noticeType][noticeKey]['no-count'] ) {
										noticeTotalCount++;
										noticeTypeCount[noticeType] = ++noticeTypeCount[noticeType] || 1;
									}
								} );
							} );

							if ( noticeHtml ) {
								jQuery( '#wp-admin-bar-wpsso-toolbar-notices-container' ).html( noticeHtml );
								jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).addClass( 'have-notices' );
							} else {
								jQuery( '#wp-admin-bar-wpsso-toolbar-notices-container' ).html( noNoticesHtml );
								jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).removeClass( 'have-notices' );
							}

							jQuery( '#wpsso-toolbar-notices-count' ).html( noticeTotalCount );

							if ( noticeTotalCount ) {

								var noticeStatus = '';

								if ( noticeTypeCount['err'] ) {
									noticeStatus = 'error';
								} else if ( noticeTypeCount['warn'] ) {
									noticeStatus = 'warning';
								} else if ( noticeTypeCount['inf'] ) {
									noticeStatus = 'info';
								} else if ( noticeTypeCount['upd'] ) {
									noticeStatus = 'success';
								}

								jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).addClass( 'have-notices-' + noticeStatus );
							}
						} );
					}
				);
			</script>
			<?php
		}

		/**
		 * Add jQuery to correctly follow the Install / Update link when clicked (WordPress bug).
		 * Also adds the parent URL and settings page title as query arguments, which are then
		 * used by WpssoAdmin class filters to return the user back to the settings page after
		 * installing / activating / updating the plugin.
		 */
		private function add_plugin_install_iframe_script( $hook_name ) {	// $hook_name = plugin-install.php

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			wp_enqueue_script( 'plugin-install' );	// required for the plugin details box

			/**
			 * Fix the update / install button to load the href when clicked.
			 */
			$custom_script_js = '
jQuery( document ).ready( function(){
	jQuery( "body#plugin-information.iframe a[id$=_from_iframe]" ).on( "click", function(){
		if ( window.top.location.href.indexOf( "page=' . $this->p->lca . '-" ) ) {
			var plugin_url = jQuery( this ).attr( "href" );
			var pageref_url_arg = "&' . $this->p->lca . '_pageref_url=" + encodeURIComponent( window.top.location.href );
			var pageref_title_arg = "&' . $this->p->lca . '_pageref_title=" + encodeURIComponent( jQuery( "h1", window.parent.document ).text() );
			window.top.location.href = plugin_url + pageref_url_arg + pageref_title_arg;
		}
	});
});';
			wp_add_inline_script( 'plugin-install', $custom_script_js );
		}
	}
}
