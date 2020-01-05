<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
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

					$this->tb_notices = $this->p->notice->get_notice_system();

					/**
					 * Add jQuery to update the toolbar menu item counter and container on page load.
					 */
					if ( ! empty( $this->tb_notices ) ) {

						add_action( 'admin_footer', array( $this, 'add_update_tb_notices_script' ), 1000 );
					}

					add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ), -1000 );
					add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), -1000 );
				}
			}
		}

		public function admin_enqueue_scripts( $hook_name ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'hook name = '.$hook_name );
				$this->p->debug->log( 'screen base = '.SucomUtil::get_screen_base() );
			}

			/**
			 * Do not use minified JS if the DEV constant is defined.
			 */
			$doing_dev = SucomUtil::get_const( 'WPSSO_DEV' );
			$file_ext  = $doing_dev ? 'js' : 'min.js';
			$version   = WpssoConfig::get_version();

			/**
			 * See http://qtip2.com/download.
			 */
			wp_register_script( 'jquery-qtip', 
				WPSSO_URLPATH . 'js/ext/jquery-qtip.' . $file_ext, 
					array( 'jquery' ), $this->p->cf[ 'jquery-qtip' ][ 'version' ], true );

			wp_register_script( 'sucom-settings-page', 
				WPSSO_URLPATH . 'js/com/jquery-settings-page.' . $file_ext, 
					array( 'jquery' ), $version, true );

			wp_register_script( 'sucom-metabox', 
				WPSSO_URLPATH . 'js/com/jquery-metabox.' . $file_ext, 
					array( 'jquery' ), $version, true );

			wp_register_script( 'sucom-tooltips', 
				WPSSO_URLPATH . 'js/com/jquery-tooltips.' . $file_ext, 
					array( 'jquery' ), $version, true );

			wp_register_script( 'sucom-admin-media', 
				WPSSO_URLPATH . 'js/com/jquery-admin-media.' . $file_ext, 
					array( 'jquery', 'jquery-ui-core' ), $version, true );

			/**
			 * Only load scripts where we need them.
			 */
			switch ( $hook_name ) {

				/**
				 * Addons and license settings page.
				 */
				case ( preg_match( '/_page_' . $this->p->lca . '-.*(addons|licenses)/', $hook_name ) ? true : false ) :

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing scripts for addons and licenses page' );
					}

					add_thickbox();	// Required for the plugin details box.

					wp_enqueue_script( 'plugin-install' );	// Required for the plugin details box.

					// No break.

				/**
				 * Any settings page. Also matches the profile_page and users_page hooks.
				 */
				case ( false !== strpos( $hook_name, '_page_' . $this->p->lca . '-' ) ? true : false ):

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing scripts for settings page' );
					}

					wp_enqueue_script( 'sucom-settings-page' );

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
						$this->p->debug->log( 'enqueuing scripts for editing page' );
					}

					wp_enqueue_script( 'jquery-ui-datepicker' );
					wp_enqueue_script( 'jquery-qtip' );
					wp_enqueue_script( 'sucom-metabox' );
					wp_enqueue_script( 'sucom-tooltips' );
					wp_enqueue_script( 'wp-color-picker' );

					wp_localize_script( 'sucom-metabox', 'sucomMetaboxL10n',
						$this->get_metabox_script_data() );

					if ( function_exists( 'wp_enqueue_media' ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'wp_enqueue_media() function is available' );
						}

						if ( SucomUtil::is_post_page( false ) &&
							( $post_id = SucomUtil::get_post_object( false, 'id' ) ) > 0 ) {

							wp_enqueue_media( array( 'post' => $post_id ) );
						} else {
							wp_enqueue_media();
						}

						wp_enqueue_script( 'sucom-admin-media' );

						wp_localize_script( 'sucom-admin-media', 'sucomMediaL10n',
							$this->get_admin_media_script_data() );

					} elseif ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'wp_enqueue_media() function not found' );
					}

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

			$this->add_admin_page_script( $hook_name, WPSSO_URLPATH, $file_ext, $version );
		}

		/**
		 * Start localized variable names with an underscore.
		 */
		public function get_metabox_script_data() {

			return array(
				'_min_len_msg' => __( '{0} of {1} characters minimum', 'wpsso' ),
				'_req_len_msg' => __( '{0} of {1} characters required', 'wpsso' ),
				'_max_len_msg' => __( '{0} of {1} characters maximum', 'wpsso' ),
				'_len_msg'     => __( '{0} characters', 'wpsso' ),
			);
		}

		/**
		 * Always start localized variable names with an underscore.
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

			/**
			 * Do not use minified JS if the DEV constant is defined.
			 */
			$doing_dev = SucomUtil::get_const( 'WPSSO_DEV' );
			$file_ext  = $doing_dev ? 'js' : 'min.js';
			$version   = WpssoConfig::get_version();

			wp_enqueue_script( 'sucom-block-editor-admin', 
				WPSSO_URLPATH . 'js/block-editor-admin.' . $file_ext, 
					array( 'wp-data', 'wp-editor', 'wp-edit-post' ), $version, false );

			wp_localize_script( 'sucom-block-editor-admin', 'sucomBlockEditorL10n',
				$this->get_block_editor_admin_script_data() );
		}

		/**
		 * Always start localized variable names with an underscore.
		 */
		public function get_block_editor_admin_script_data() {

			$metabox_id   = $this->p->cf[ 'meta' ][ 'id' ];
			$container_id = $this->p->lca . '_metabox_' . $metabox_id . '_inside';

			$no_notices_text = sprintf( __( 'No %s notifications.', 'wpsso' ), $this->p->cf[ 'menu' ][ 'title' ] );
			$no_notices_html = '<div class="ab-item ab-empty-item">' . $no_notices_text . '</div>';

			$option_labels = array( 'robots'   => _x( 'Robots', 'option label', 'wpsso' ) );
			$container_ids = array( $container_id );

			$option_labels = apply_filters( $this->p->lca . '_block_editor_admin_option_labels', $option_labels );

			/**
			 * Each metabox ID is sanitized by the jQuery wpssoUpdateMetabox() function.
			 */
			$container_ids = apply_filters( $this->p->lca . '_block_editor_admin_container_ids', $container_ids );

			return array(
				'_ajax_nonce'      => wp_create_nonce( WPSSO_NONCE_NAME ),
				'_tb_notices'      => $this->tb_notices,	// Maybe null, true, false, or array.
				'_no_notices_html' => $no_notices_html,
				'_option_labels'   => $option_labels,
				'_container_ids'   => $container_ids,
			);
		}

		/**
		 * Hooked to the WordPress 'admin_footer' action.
		 */
		public function add_update_tb_notices_script() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Exit early if this is a block editor page - the notices will be retrieved using an ajax call on page
			 * load and post save.
			 */
			if ( SucomUtilWP::doing_block_editor() ) {

				if ( ! empty( $this->p->debug->enabled ) ) {
					$this->p->debug->log( 'exiting early: doing block editor' );
				}

				echo '<!-- ' . __METHOD__ . ' exiting early: block editor will update toolbar notices -->' . "\n\n";

				return;
			}

			/**
			 * Just in case - no use getting notices if there's nothing to get.
			 *
			 * Example $this->tb_notices = array( 'err', 'warn', 'inf' ).
			 */
			if ( empty( $this->tb_notices ) || ! is_array( $this->tb_notices ) ) {

				if ( ! empty( $this->p->debug->enabled ) ) {
					$this->p->debug->log( 'exiting early: no toolbar notice types defined' );
				}

				return;
			}

			$no_notices_text = sprintf( __( 'No %s notifications.', 'wpsso' ), $this->p->cf[ 'menu' ][ 'title' ] );
			$no_notices_html = '<div class="ab-item ab-empty-item">' . $no_notices_text . '</div>';

			/**
			 * A wpssoUpdateToolbar() function will exist in block editor pages (see js/block-editor-admin.js), but not
			 * in other admin pages, like settings pages for example. If the function does not exist, then create the
			 * wpssoUpdateToolbar() function and call it when the document is loaded (aka ready).
			 */
			?>
			<script type="text/javascript">

				if ( typeof wpssoUpdateToolbar !== "function" ) {

					/**
					 * Make sure to run this script last, so WordPress does not move notices out of the toolbar.
					 */
					jQuery( document ).ready( function() {
						jQuery( window ).load( function() {
							wpssoUpdateToolbar();
						});
					});

					function wpssoUpdateToolbar( updateNoticeHtml ) {

						updateNoticeHtml = typeof updateNoticeHtml !== 'undefined' ? updateNoticeHtml : true;

						var ajaxNoticesData = {
							action: 'wpsso_get_notices_json',
							context: 'toolbar_notices',
							_ajax_nonce: '<?php echo wp_create_nonce( WPSSO_NONCE_NAME ); ?>',
							_notice_types: '<?php echo implode( ',', $this->tb_notices ); ?>',
						}
	
						jQuery.getJSON( ajaxurl, ajaxNoticesData, function( data ) {
	
							var noticeHtml       = '';
							var noticeStatus     = '';
							var noticeTotalCount = 0;
							var noticeTypeCount  = {};
							var noNoticesHtml    = '<?php echo $no_notices_html; ?>';
	
							jQuery.each( data, function( noticeType ) {
	
								jQuery.each( data[ noticeType ], function( noticeKey ) {
	
									noticeHtml += data[ noticeType ][ noticeKey ][ 'msg_html' ];
	
									noticeTypeCount[ noticeType ] = ++noticeTypeCount[ noticeType ] || 1;
	
									noticeTotalCount++;
								} );
							} );
	
							jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).removeClass( 'have-notices-error' );
							jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).removeClass( 'have-notices-warning' );
							jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).removeClass( 'have-notices-info' );
							jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).removeClass( 'have-notices-success' );

							if ( updateNoticeHtml ) {

								if ( noticeHtml ) {

									jQuery( '#wp-admin-bar-wpsso-toolbar-notices-container' ).html( noticeHtml );
									jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).addClass( 'have-notices' );

								} else {

									jQuery( '#wp-admin-bar-wpsso-toolbar-notices-container' ).html( noNoticesHtml );
									jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).removeClass( 'have-notices' );
								}
							}
	
							jQuery( '#wpsso-toolbar-notices-count' ).html( noticeTotalCount );
	
							if ( noticeTotalCount ) {
	
								var noticeStatus = '';
	
								if ( noticeTypeCount[ 'err' ] ) {
									noticeStatus = 'error';
								} else if ( noticeTypeCount[ 'warn' ] ) {
									noticeStatus = 'warning';
								} else if ( noticeTypeCount[ 'inf' ] ) {
									noticeStatus = 'info';
								} else if ( noticeTypeCount[ 'upd' ] ) {
									noticeStatus = 'success';
								}
	
								jQuery( '#wp-admin-bar-wpsso-toolbar-notices' ).addClass( 'have-notices-' + noticeStatus );
							}
						} );
					}
				}
			</script>
			<?php
		}

		/**
		 * Add jQuery to correctly follow the Install / Update link when clicked (WordPress bug). Also adds the parent URL
		 * and settings page title as query arguments, which are then used by WpssoAdmin class filters to return the user
		 * back to the settings page after installing / activating / updating the plugin.
		 */
		private function add_plugin_install_iframe_script( $hook_name ) {	// $hook_name = plugin-install.php

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			wp_enqueue_script( 'plugin-install' );	// Required for the plugin details box.

			/**
			 * Fix the update / install button to load the href when clicked.
			 */
			$custom_script_js = '
jQuery( document ).ready( function(){

	jQuery( "body#plugin-information.iframe a[id$=_from_iframe]" ).on( "click", function(){

		if ( window.top.location.href.indexOf( "page=' . $this->p->lca . '-" ) ) {

			var plugin_url        = jQuery( this ).attr( "href" );
			var pageref_url_arg   = "&' . $this->p->lca . '_pageref_url=" + encodeURIComponent( window.top.location.href );
			var pageref_title_arg = "&' . $this->p->lca . '_pageref_title=" + encodeURIComponent( jQuery( "h1", window.parent.document ).text() );

			window.top.location.href = plugin_url + pageref_url_arg + pageref_title_arg;
		}
	});
});';

			if ( function_exists( 'wp_add_inline_script' ) ) {	// Since WP v4.5.0.
				wp_add_inline_script( 'plugin-install', $custom_script_js );
			} else {
				echo '<script type="text/javascript">' . $custom_script_js . '</script>';
			}
		}

		private function add_admin_page_script( $hook_name, $plugin_urlpath, $file_ext, $version ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			wp_register_script( 'sucom-admin-page', 
				WPSSO_URLPATH . 'js/com/jquery-admin-page.' . $file_ext, 
					array( 'jquery' ), $version, true );


			wp_enqueue_script( 'sucom-admin-page' );

			wp_enqueue_script( 'jquery' );	// Required for dismissible notices.
		}
	}
}
