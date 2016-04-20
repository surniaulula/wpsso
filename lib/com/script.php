<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
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

		public function admin_enqueue_scripts( $hook ) {
			$lca = $this->p->cf['lca'];
			$url_path = constant( $this->p->cf['uca'].'_URLPATH' );
			$plugin_version = $this->p->cf['plugin'][$lca]['version'];

			wp_register_script( 'jquery-qtip', 
				$url_path.'js/ext/jquery-qtip.min.js', 
					array( 'jquery' ), '2.2.1', true );
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

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'hook name: '.$hook );

			// don't load our javascript where we don't need it
			switch ( $hook ) {
				// license settings pages include a "view plugin details" feature
				case ( preg_match( '/_page_'.$lca.'-(site)?licenses/', $hook ) ? true : false ) :

					wp_enqueue_script( 'plugin-install' );		// required for the plugin details box

					// no break - continue to enqueue the settings page

				case 'edit-tags.php':
				case 'user-edit.php':
				case 'profile.php':
				case 'post.php':
				case 'post-new.php':
				case 'term.php':
				// includes the profile_page and users_page hooks (profile submenu items)
				case ( strpos( $hook, '_page_'.$lca.'-' ) !== false ? true : false ):

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
