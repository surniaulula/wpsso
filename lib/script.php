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

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ), -1000 );
			}
		}

		public function admin_enqueue_scripts( $hook_name ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'hook name = '.$hook_name );
				$this->p->debug->log( 'screen base = '.SucomUtil::get_screen_base() );
			}

			$plugin_version = WpssoConfig::get_version();

			// http://qtip2.com/download
			wp_register_script( 'jquery-qtip', 
				WPSSO_URLPATH . 'js/ext/jquery-qtip.min.js', 
					array( 'jquery' ), $this->p->cf['jquery-qtip']['version'], true );

			wp_register_script( 'sucom-settings-page', 
				WPSSO_URLPATH . 'js/com/jquery-settings-page.min.js', 
					array( 'jquery' ), $plugin_version, true );

			wp_register_script( 'sucom-metabox', 
				WPSSO_URLPATH . 'js/com/jquery-metabox.min.js', 
					array( 'jquery' ), $plugin_version, true );

			wp_register_script( 'sucom-tooltips', 
				WPSSO_URLPATH . 'js/com/jquery-tooltips.min.js', 
					array( 'jquery' ), $plugin_version, true );

			wp_register_script( 'sucom-admin-media', 
				WPSSO_URLPATH . 'js/com/jquery-admin-media.min.js', 
					array( 'jquery', 'jquery-ui-core' ), $plugin_version, true );

			/**
			 * Only load JavaScript where we need it.
			 */
			switch ( $hook_name ) {

				case ( preg_match( '/_page_' . $this->p->lca . '-(site)?licenses/', $hook_name ) ? true : false ) :

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing scripts for licenses page' );
					}

					add_thickbox();	// required for the plugin details box

					wp_enqueue_script( 'plugin-install' );	// required for the plugin details box

					// no break

				/**
				 * Matches the profile_page and users_page hooks (profile submenu items).
				 */
				case ( strpos( $hook_name, '_page_' . $this->p->lca . '-' ) !== false ? true : false ):

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'enqueuing scripts for settings page' );
					}

					wp_enqueue_script( 'sucom-settings-page' );

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
							$this->add_iframe_inline_script( $hook_name );
						}
					}

					break;
			}

			wp_enqueue_script( 'jquery' );	// required for dismissible notices
		}

		public function get_admin_media_script_data() {
			return array(
				'choose_image' => __( 'Use Image', 'wpsso' ),
			);
		}

		/**
		 * Add jQuery to correctly follow the Install / Update link when clicked (WordPress bug).
		 * Also adds the parent URL and settings page title as query arguments, which are then
		 * used by WpssoAdmin class filters to return the user back to the settings page after
		 * installing / activating / updating the plugin.
		 */
		private function add_iframe_inline_script( $hook_name ) {	// $hook_name = plugin-install.php

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			wp_enqueue_script( 'plugin-install' );	// required for the plugin details box

			// fix the update/install button to load the href when clicked
			$custom_script_js = '
jQuery(document).ready(function(){
	jQuery("body#plugin-information.iframe a[id$=_from_iframe]").on("click", function(){
		if ( window.top.location.href.indexOf( "page=' . $this->p->lca . '-" ) ) {
			var plugin_url = jQuery( this ).attr( "href" );
			var pageref_url_arg = "&' . $this->p->lca . '_pageref_url=" + encodeURIComponent( window.top.location.href );
			var pageref_title_arg = "&' . $this->p->lca . '_pageref_title=" + encodeURIComponent( jQuery("h1", window.parent.document).text() );
			window.top.location.href = plugin_url + pageref_url_arg + pageref_title_arg;
		}
	});
});';
			wp_add_inline_script( 'plugin-install', $custom_script_js );
		}
	}
}
