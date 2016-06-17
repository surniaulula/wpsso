<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoCheck' ) ) {

	class WpssoCheck {

		private $p;
		private $active_plugins = array();	// active site and network plugins

		private static $c = array();
		private static $extend_checks = array(
			'seo' => array(
				'seou' => 'SEO Ultimate',
			),
			'util' => array(
				'um' => 'Pro Update Manager',
			),
		);

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->active_plugins = SucomUtil::active_plugins();

			if ( is_admin() ) {
				// cleanup incorrect Yoast SEO notifications
				if ( isset( $this->active_plugins['wordpress-seo/wp-seo.php'] ) )
					add_action( 'admin_init', array( $this, 'cleanup_wpseo_notifications' ), 15 );
			} else {
				// disable jetPack open graph meta tags
				if ( isset( $this->active_plugins['jetpack/jetpack.php'] ) ) {
					add_filter( 'jetpack_enable_opengraph', '__return_false', 100 );
					add_filter( 'jetpack_enable_open_graph', '__return_false', 100 );
					add_filter( 'jetpack_disable_twitter_cards', '__return_true', 100 );
				}
				// disable Yoast SEO social meta tags
				if ( isset( $this->active_plugins['wordpress-seo/wp-seo.php'] ) )
					add_action( 'template_redirect', array( $this, 'cleanup_wpseo_filters' ), 1000 );
				// disable NextGEN Facebook meta tags
				if ( isset( $this->active_plugins['nextgen-facebook/nextgen-facebook.php'] ) ) {
					if ( ! defined( 'NGFB_META_TAGS_DISABLE' ) )
						define( 'NGFB_META_TAGS_DISABLE', true );
				}
			}
		}

		// cleanup incorrect Yoast SEO notifications
		public function cleanup_wpseo_notifications() {
			if ( method_exists( 'Yoast_Notification_Center', 'remove_notification' ) ) {	// since wpseo v3.3
				$lca = $this->p->cf['lca'];
				$base = $this->p->cf['plugin'][$lca]['base'];
				$notif_center = Yoast_Notification_Center::get();
				if ( $notif_obj = $notif_center->get_notification_by_id( 'wpseo-conflict-'.md5( $base ) ) )
					$notif_center->remove_notification( $notif_obj, true );
			}
		}

		// disable Yoast SEO social meta tags
		public function cleanup_wpseo_filters() {

			if ( isset( $GLOBALS['wpseo_og'] ) && is_object( $GLOBALS['wpseo_og'] ) && 
				( $prio = has_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ) ) ) !== false )
					$ret = remove_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ), $prio );

			if ( class_exists( 'WPSEO_Twitter' ) &&
				( $prio = has_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ) ) ) !== false )
					$ret = remove_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ), $prio );

			if ( class_exists( 'WPSEO_GooglePlus' ) && 
				( $prio = has_action( 'wpseo_head', array( 'WPSEO_GooglePlus', 'get_instance' ) ) ) !== false )
					$ret = remove_action( 'wpseo_head', array( 'WPSEO_GooglePlus', 'get_instance' ), $prio );

			if ( ! empty( $this->p->options['seo_publisher_url'] ) && isset( WPSEO_Frontend::$instance ) &&
				 ( $prio = has_action( 'wpseo_head', array( WPSEO_Frontend::$instance, 'publisher' ) ) ) )
					$ret = remove_action( 'wpseo_head', array( WPSEO_Frontend::$instance, 'publisher' ), $prio );

			if ( ! empty( $this->p->options['schema_website_json'] ) )
				add_filter( 'wpseo_json_ld_output', '__return_empty_array', 99 );
		}

		private function get_avail_check( $key ) {
			switch ( $key ) {
				case 'aop':
					$ret = ! SucomUtil::get_const( 'WPSSO_PRO_MODULE_DISABLE' ) &&
					is_dir( WPSSO_PLUGINDIR.'lib/pro/' ) ? true : false;
					break;
				case 'mt':
					$ret = ! SucomUtil::get_const( 'WPSSO_META_TAGS_DISABLE' ) &&
					empty( $_SERVER['WPSSO_META_TAGS_DISABLE'] ) &&
					empty( $_GET['WPSSO_META_TAGS_DISABLE'] ) ? true : false;	// allow meta tags to be disabled with query argument
					break;
				default:
					$ret = false;
					break;
			}
			return $ret;
		}

		public function get_avail() {
			$ret = array();
			$is_admin = is_admin();

			$ret['post_thumbnail'] = function_exists( 'has_post_thumbnail' ) ? true : false;
			$ret['amp_endpoint'] = function_exists( 'is_amp_endpoint' ) ? true : false;

			foreach ( array( 'aop', 'mt' ) as $key )
				$ret[$key] = $this->get_avail_check( $key );

			foreach ( $this->p->cf['cache'] as $name => $val ) {
				$constant_name = 'WPSSO_'.strtoupper( $name ).'_CACHE_DISABLE';
				$ret['cache'][$name] = defined( $constant_name ) &&
					constant( $constant_name ) ? false : true;
			}

			foreach ( SucomUtil::array_merge_recursive_distinct( $this->p->cf['*']['lib']['pro'], 
				self::$extend_checks ) as $sub => $lib ) {

				$ret[$sub] = array();
				$ret[$sub]['*'] = false;
				foreach ( $lib as $id => $name ) {
					$chk = array();
					$ret[$sub][$id] = false;	// default value
					switch ( $sub.'-'.$id ) {
						/*
						 * 3rd Party Plugins
						 */
						case 'ecom-edd':
							$chk['class'] = 'Easy_Digital_Downloads';
							$chk['plugin'] = 'easy-digital-downloads/easy-digital-downloads.php';
							break;
						case 'ecom-marketpress':
							$chk['class'] = 'MarketPress';
							$chk['plugin'] = 'wordpress-ecommerce/marketpress.php';
							break;
						case 'ecom-woocommerce':
							$chk['class'] = 'Woocommerce';
							$chk['plugin'] = 'woocommerce/woocommerce.php';
							break;
						case 'ecom-wpecommerce':
							$chk['class'] = 'WP_eCommerce';
							$chk['plugin'] = 'wp-e-commerce/wp-shopping-cart.php';
							break;
						case 'ecom-yotpowc':	// yotpo-social-reviews-for-woocommerce
							$chk['function'] = 'wc_yotpo_init';
							break;
						case 'event-tribe_events':
							$chk['plugin'] = 'the-events-calendar/the-events-calendar.php';
							break;
						case 'forum-bbpress':
							$chk['class'] = 'bbPress';
							$chk['plugin'] = 'bbpress/bbpress.php';
							break;
						case 'lang-polylang':
							$chk['class'] = 'Polylang';
							$chk['plugin'] = 'polylang/polylang.php';
							break;
						case 'media-ngg':
							$chk['class'] = 'nggdb';	// C_NextGEN_Bootstrap
							$chk['plugin'] = 'nextgen-gallery/nggallery.php';
							break;
						case 'media-rtmedia':
							$chk['plugin'] = 'buddypress-media/index.php';
							break;
						case 'seo-aioseop':
							$chk['class'] = 'All_in_One_SEO_Pack';
							break;
						case 'seo-autodescription':
							$chk['function'] = 'the_seo_framework';
							$chk['plugin'] = 'autodescription/autodescription.php';
							break;
						case 'seo-headspace2':
							$chk['class'] = 'HeadSpace_Plugin';
							$chk['plugin'] = 'headspace2/headspace.php';
							break;
						case 'seo-seou':
							$chk['class'] = 'SEO_Ultimate';
							$chk['plugin'] = 'seo-ultimate/seo-ultimate.php';
							break;
						case 'seo-wpseo':
							$chk['function'] = 'wpseo_init';
							$chk['plugin'] = 'wordpress-seo/wp-seo.php';
							break;
						case 'social-buddypress':
							$chk['class'] = 'BuddyPress';
							$chk['plugin'] = 'buddypress/bp-loader.php';
							break;
						/*
						 * Pro Version Features / Options
						 */
						case 'media-gravatar':
							$chk['optval'] = 'plugin_gravatar_api';
							break;
						case 'media-slideshare':
							$chk['optval'] = 'plugin_slideshare_api';
							break;
						case 'media-upscale':
							$chk['optval'] = 'plugin_upscale_images';
							break;
						case 'media-vimeo':
							$chk['optval'] = 'plugin_vimeo_api';
							break;
						case 'media-wistia':
							$chk['optval'] = 'plugin_wistia_api';
							break;
						case 'media-youtube':
							$chk['optval'] = 'plugin_youtube_api';
							break;
						case 'admin-general':
						case 'admin-advanced':
							// only load on the settings pages
							if ( $is_admin ) {
								$page = basename( $_SERVER['PHP_SELF'] );
								if ( $page === 'admin.php' || $page === 'options-general.php' )
									$ret[$sub]['*'] = $ret[$sub][$id] = true;
							}
							break;
						case 'admin-post':
						case 'admin-meta':
							if ( $is_admin )
								$ret[$sub]['*'] = $ret[$sub][$id] = true;
							break;
						case 'util-checkimgdims':
							$chk['optval'] = 'plugin_check_img_dims';
							break;
						case 'util-coauthors':
							$chk['plugin'] = 'co-authors-plus/co-authors-plus.php';
							break;
						case 'util-post':
						case 'util-term':
						case 'util-user':
							$ret[$sub]['*'] = $ret[$sub][$id] = true;
							break;
						case 'util-language':
							$chk['optval'] = 'plugin_filter_lang';
							break;
						case 'util-restapi':
							$chk['plugin'] = 'rest-api/plugin.php';
							break;
						case 'util-shorten':
							$chk['optval'] = 'plugin_shortener';
							break;
						case 'util-um':
							$chk['plugin'] = 'wpsso-um/wpsso-um.php';
							break;
					}
					if ( ! empty( $chk ) ) {
						if ( isset( $chk['plugin'] ) || isset( $chk['class'] ) || isset( $chk['function'] ) ) {
							if ( ( ! empty( $chk['plugin'] ) && isset( $this->active_plugins[$chk['plugin']] ) ) ||
								( ! empty( $chk['class'] ) && class_exists( $chk['class'] ) ) ||
								( ! empty( $chk['function'] ) && function_exists( $chk['function'] ) ) ) {

								// check if an option value is also required
								if ( isset( $chk['optval'] ) ) {
									if ( $this->has_optval( $chk['optval'] ) )
										$ret[$sub]['*'] = $ret[$sub][$id] = true;
								} else $ret[$sub]['*'] = $ret[$sub][$id] = true;
							}
						} if ( isset( $chk['optval'] ) ) {
							if ( $this->has_optval( $chk['optval'] ) )
								$ret[$sub]['*'] = $ret[$sub][$id] = true;
						}
					}
				}
			}
			return apply_filters( $this->p->cf['lca'].'_get_avail', $ret );
		}

		private function has_optval( $opt_name ) { 
			if ( ! empty( $opt_name ) && 
				! empty( $this->p->options[$opt_name] ) && 
					$this->p->options[$opt_name] !== 'none' )
						return true;
		}

		public function is_aop( $lca = '' ) { 
			return $this->aop( $lca, true, $this->get_avail_check( 'aop' ) );
		}

		public function aop( $lca = '', $lic = true, $rv = true ) {
			$lca = empty( $lca ) ? 
				$this->p->cf['lca'] : $lca;
			$kn = $lca.'-'.$lic.'-'.$rv;
			if ( isset( self::$c[$kn] ) )
				return self::$c[$kn];
			$uca = strtoupper( $lca );
			if ( defined( $uca.'_PLUGINDIR' ) )
				$pdir = constant( $uca.'_PLUGINDIR' );
			elseif ( isset( $this->p->cf['plugin'][$lca]['slug'] ) ) {
				$slug = $this->p->cf['plugin'][$lca]['slug'];
				if ( ! defined ( 'WPMU_PLUGIN_DIR' ) || 
					! is_dir( $pdir = WPMU_PLUGIN_DIR.'/'.$slug.'/' ) ) {
					if ( ! defined ( 'WP_PLUGIN_DIR' ) || 
						! is_dir( $pdir = WP_PLUGIN_DIR.'/'.$slug.'/' ) )
							return self::$c[$kn] = false;
				}
			} else return self::$c[$kn] = false;
			$on = 'plugin_'.$lca.'_tid';
			$ins = is_dir( $pdir.'lib/pro/' ) ? $rv : false;
			return self::$c[$kn] = $lic === true ? 
				( ( ! empty( $this->p->options[$on] ) && 
					$ins && class_exists( 'SucomUpdate' ) &&
						( $uerr = SucomUpdate::get_umsg( $lca ) ? 	// use get_umsg() for backwards compat
							false : $ins ) ) ? $uerr : false ) : $ins;
		}
	}
}

?>
