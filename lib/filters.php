<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoFilters' ) ) {

	class WpssoFilters {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( is_admin() ) {
				// cleanup incorrect Yoast SEO notifications
				if ( SucomUtil::active_plugins( 'wordpress-seo/wp-seo.php' ) )
					add_action( 'admin_init', array( $this, 'cleanup_wpseo_notifications' ), 15 );
			} else {
				// disable jetPack open graph meta tags
				if ( SucomUtil::active_plugins( 'jetpack/jetpack.php' ) ) {
					add_filter( 'jetpack_enable_opengraph', '__return_false', 100 );
					add_filter( 'jetpack_enable_open_graph', '__return_false', 100 );
					add_filter( 'jetpack_disable_twitter_cards', '__return_true', 100 );
				}

				// disable Yoast SEO social meta tags
				// execute after add_action( 'template_redirect', 'wpseo_frontend_head_init', 999 );
				if ( SucomUtil::active_plugins( 'wordpress-seo/wp-seo.php' ) )
					add_action( 'template_redirect', array( $this, 'cleanup_wpseo_filters' ), 9000 );
			}

			if ( SucomUtil::get_const( 'WPSSO_READ_WPSEO_META' ) && empty( $this->p->is_avail['seo']['wpseo'] ) ) {
				// use custom method names to add Yoast SEO meta
				$this->p->util->add_plugin_filters( $this, array( 
					'get_post_options_wpseo_meta' => array( 'get_post_options' => 2 ),
					'get_term_options_wpseo_meta' => array( 'get_term_options' => 2 ),
					'get_user_options_wpseo_meta' => array( 'get_user_options' => 2 ),
				) );
			}
		}

		/*
		 * Cleanup incorrect Yoast SEO notifications.
		 */
		public function cleanup_wpseo_notifications() {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( method_exists( 'Yoast_Notification_Center', 'add_notification' ) ) {	// since wpseo v3.3
				$lca = $this->p->cf['lca'];
				$info = $this->p->cf['plugin'][$lca];
				$id = 'wpseo-conflict-'.md5( $info['base'] );
				$msg = '<style>#'.$id.'{display:none;}</style>';
				$notif_center = Yoast_Notification_Center::get();

				if ( ( $notif_obj = $notif_center->get_notification_by_id( $id ) ) && $notif_obj->message !== $msg ) {
					update_user_meta( get_current_user_id(), $notif_obj->get_dismissal_key(), 'seen' );
					$notif_obj = new Yoast_Notification( $msg, array( 'id' => $id ) );
					$notif_center->add_notification( $notif_obj );
				}
			}
		}

		/*
		 * Disable Yoast SEO social meta tags.
		 */
		public function cleanup_wpseo_filters() {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( isset( $GLOBALS['wpseo_og'] ) && is_object( $GLOBALS['wpseo_og'] ) && 
				( $prio = has_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ) ) ) !== false ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'removing wpseo_head action for opengraph' );
				$ret = remove_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ), $prio );
			}

			if ( class_exists( 'WPSEO_Twitter' ) &&
				( $prio = has_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ) ) ) !== false ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'removing wpseo_head action for twitter' );
				$ret = remove_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ), $prio );
			}

			if ( ! empty( $this->p->options['seo_publisher_url'] ) && isset( WPSEO_Frontend::$instance ) &&
				 ( $prio = has_action( 'wpseo_head', array( WPSEO_Frontend::$instance, 'publisher' ) ) ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'removing wpseo_head action for publisher' );
				$ret = remove_action( 'wpseo_head', array( WPSEO_Frontend::$instance, 'publisher' ), $prio );
			}

			if ( ! empty( $this->p->options['schema_website_json'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'disabling wpseo_json_ld_output filter' );
				add_filter( 'wpseo_json_ld_output', '__return_empty_array', 9000 );
			}
		}

		public function filter_get_post_options_wpseo_meta( $opts, $post_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( empty( $opts['og_title'] ) )
				$opts['og_title'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_opengraph-title', true );

			if ( empty( $opts['og_title'] ) )	// fallback to the SEO title
				$opts['og_title'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_title', true );

			if ( empty( $opts['og_desc'] ) )
				$opts['og_desc'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_opengraph-description', true );

			if ( empty( $opts['og_desc'] ) )	// fallback to the SEO description
				$opts['og_desc'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_metadesc', true );

			if ( empty( $opts['og_img_id'] ) && empty( $opts['og_img_url'] ) )
				$opts['og_img_url'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_opengraph-image', true );

			if ( empty( $opts['tc_desc'] ) )
				$opts['tc_desc'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_twitter-description', true );

			if ( empty( $opts['schema_desc'] ) )
				$opts['schema_desc'] = (string) get_post_meta( $post_id,
					'_yoast_wpseo_metadesc', true );

			$opts['seo_desc'] = (string) get_post_meta( $post_id,
				'_yoast_wpseo_metadesc', true );

			return $opts;
		}

		/*
		 * Yoast SEO does not support wordpress term meta (added in wp 4.4).
		 * Read term meta from the 'wpseo_taxonomy_meta' option instead.
		 */
		public function filter_get_term_options_wpseo_meta( $opts, $term_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$term_obj = get_term( $term_id );
			$tax_opts = get_option( 'wpseo_taxonomy_meta' );

			if ( ! isset( $term_obj->taxonomy ) || 
				! isset( $tax_opts[$term_obj->taxonomy][$term_id] ) )
					return $opts;

			$term_opts = $tax_opts[$term_obj->taxonomy][$term_id];

			if ( empty( $opts['og_title'] ) && 
				isset( $term_opts['wpseo_opengraph-title'] ) )
					$opts['og_title'] = (string) $term_opts['wpseo_opengraph-title'];

			if ( empty( $opts['og_title'] ) &&	// fallback to the SEO title
				isset( $term_opts['wpseo_title'] ) )
					$opts['og_title'] = (string) $term_opts['wpseo_title'];

			if ( empty( $opts['og_desc'] ) && 
				isset( $term_opts['wpseo_opengraph-description'] ) )
					$opts['og_desc'] = (string) $term_opts['wpseo_opengraph-description'];

			if ( empty( $opts['og_desc'] ) &&	// fallback to the SEO description
				isset( $term_opts['wpseo_desc'] ) )
					$opts['og_desc'] = (string) $term_opts['wpseo_desc'];

			if ( empty( $opts['og_img_id'] ) && empty( $opts['og_img_url'] ) &&
				isset( $term_opts['wpseo_opengraph-image'] ) )
					$opts['og_img_url'] = (string) $term_opts['wpseo_opengraph-image'];

			if ( empty( $opts['tc_desc'] ) &&
				isset( $term_opts['wpseo_twitter-description'] ) )
					$opts['tc_desc'] = (string) $term_opts['wpseo_twitter-description'];

			if ( empty( $opts['schema_desc'] ) && 
				isset( $term_opts['wpseo_desc'] ) )
					$opts['tc_desc'] = (string) $term_opts['wpseo_desc'];

			if ( isset( $term_opts['wpseo_desc'] ) )
				$opts['seo_desc'] = (string) $term_opts['wpseo_desc'];

			return $opts;
		}

		/*
		 * Yoast SEO does not provide social settings for users.
		 */
		public function filter_get_user_options_wpseo_meta( $opts, $user_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( empty( $opts['og_title'] ) )
				$opts['og_title'] = (string) get_user_meta( $user_id,
					'wpseo_title', true );

			if ( empty( $opts['og_desc'] ) )
				$opts['og_desc'] = (string) get_user_meta( $user_id,
					'wpseo_metadesc', true );

			return $opts;
		}
	}
}

?>
