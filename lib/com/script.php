<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomScript' ) ) {

	class SucomScript {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
		}

		public function admin_enqueue_scripts( $hook_name ) {
			$lca = $this->p->cf['lca'];
			$url_path = constant( strtoupper( $this->p->cf['lca'] ).'_URLPATH' );
			$plugin_version = $this->p->cf['plugin'][$lca]['version'];

			// http://qtip2.com/download
			wp_register_script( 'jquery-qtip', 
				$url_path.'js/ext/jquery-qtip.min.js', 
					array( 'jquery' ), '3.0.3', true );

			wp_register_script( 'sucom-tooltips', 
				$url_path.'js/com/jquery-tooltips.min.js', 
					array( 'jquery' ), $plugin_version, true );

			wp_register_script( 'sucom-metabox', 
				$url_path.'js/com/jquery-metabox.min.js', 
					array( 'jquery' ), $plugin_version, true );

			wp_register_script( 'sucom-admin-media', 
				$url_path.'js/com/jquery-admin-media.min.js', 
					array( 'jquery', 'jquery-ui-core' ), $plugin_version, true );

			wp_enqueue_script( 'jquery' );	// required for dismissible notices

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'hook name = '.$hook_name );
				$this->p->debug->log( 'screen base = '.SucomUtil::get_screen_base() );
			}

			// don't load our javascript where we don't need it
			switch ( $hook_name ) {
				// license settings pages include a "view plugin details" feature
				case ( preg_match( '/_page_'.$lca.'-(site)?licenses/', $hook_name ) ? true : false ) :

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'enqueuing scripts for licenses page' );

					wp_enqueue_script( 'plugin-install' );		// required for the plugin details box

					// no break - continue to enqueue the settings page

				case 'post.php':	// post edit
				case 'post-new.php':	// post edit
				case 'term.php':	// term edit
				case 'edit-tags.php':	// term edit
				case 'user-edit.php':	// user edit
				case 'profile.php':	// user edit
				case ( SucomUtil::is_toplevel_edit( $hook_name ) ):	// required for event espresso plugin
				case ( strpos( $hook_name, '_page_'.$lca.'-' ) !== false ? true : false ):	// profile_page and users_page hooks

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'enqueuing scripts for editing and settings page' );

					if ( function_exists( 'wp_enqueue_media' ) ) {	// since wp 3.5.0
						if ( SucomUtil::is_post_page( false ) &&
							( $post_id = SucomUtil::get_post_object( false, 'id' ) ) > 0 )
								wp_enqueue_media( array( 'post' => $post_id ) );
						else wp_enqueue_media();

						wp_enqueue_script( 'sucom-admin-media' );
					}

					wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );
					wp_enqueue_script( 'jquery-qtip', array( 'jquery' ) );
					wp_enqueue_script( 'sucom-tooltips' );
					wp_enqueue_script( 'sucom-metabox' );

					wp_localize_script( 'sucom-admin-media',
						'sucomMediaL10n', $this->localize_media_script() );

					break;
			}
		}

		public function localize_media_script() {
			$text_domain = $this->p->cf['plugin'][$this->p->cf['lca']]['text_domain'];
			return array( 'choose_image' => __( 'Use Image', $text_domain ) );
		}
	}
}

?>
