<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoCheck' ) ) {

	class WpssoCheck {

		private $p;
		private static $c = array();
		private static $extend_lib_checks = array(
			'seo' => array(
				'seou' => 'SEO Ultimate',
				'sq' => 'Squirrly SEO',
			),
			'util' => array(
				'um' => 'Pro Update Manager',
			),
		);

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
		}

		public function get_avail() {
			$ret = array();
			$is_admin = is_admin();

			$ret['post_thumbnail'] = function_exists( 'has_post_thumbnail' ) ? true : false;
			$ret['amp_endpoint'] = function_exists( 'is_amp_endpoint' ) ? true : false;

			foreach ( array( 'aop', 'head' ) as $key )
				$ret[$key] = $this->get_avail_check( $key );

			foreach ( SucomUtil::array_merge_recursive_distinct( $this->p->cf['*']['lib']['pro'], 
				self::$extend_lib_checks ) as $sub => $lib ) {

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
							$chk['plugin'] = 'easy-digital-downloads/easy-digital-downloads.php';
							break;
						case 'ecom-marketpress':
							$chk['plugin'] = 'wordpress-ecommerce/marketpress.php';
							break;
						case 'ecom-woocommerce':
							$chk['plugin'] = 'woocommerce/woocommerce.php';
							break;
						case 'ecom-wpecommerce':
							$chk['plugin'] = 'wp-e-commerce/wp-shopping-cart.php';
							break;
						case 'ecom-yotpowc':	// yotpo-social-reviews-for-woocommerce
							$chk['function'] = 'wc_yotpo_init';
							break;
						case 'event-tribe_events':
							$chk['plugin'] = 'the-events-calendar/the-events-calendar.php';
							break;
						case 'forum-bbpress':
							$chk['plugin'] = 'bbpress/bbpress.php';
							break;
						case 'lang-polylang':
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
							$chk['plugin'] = 'all-in-one-seo-pack/all_in_one_seo_pack.php';
							break;
						case 'seo-autodescription':
							$chk['plugin'] = 'autodescription/autodescription.php';
							break;
						case 'seo-headspace2':
							$chk['plugin'] = 'headspace2/headspace.php';
							break;
						case 'seo-seou':
							$chk['plugin'] = 'seo-ultimate/seo-ultimate.php';
							break;
						case 'seo-sq':
							$chk['plugin'] = 'squirrly-seo/squirrly.php';
							break;
						case 'seo-wpseo':
							$chk['function'] = 'wpseo_init';	// just in case
							$chk['plugin'] = 'wordpress-seo/wp-seo.php';
							break;
						case 'social-buddypress':
							$chk['plugin'] = 'buddypress/bp-loader.php';
							break;
						/*
						 * Pro Version Features / Options
						 */
						case 'media-facebook':
							$chk['optval'] = 'plugin_facebook_api';
							break;
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
						case 'util-wpseo_meta':
							$chk['optval'] = 'plugin_wpseo_social_meta';
							break;
					}
					if ( ! empty( $chk ) ) {
						if ( isset( $chk['plugin'] ) || isset( $chk['class'] ) || isset( $chk['function'] ) ) {
							if ( ( ! empty( $chk['plugin'] ) && SucomUtil::active_plugins( $chk['plugin'] ) ) ||
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

		private function get_avail_check( $key ) {
			switch ( $key ) {
				case 'aop':
					$ret = ! SucomUtil::get_const( 'WPSSO_PRO_MODULE_DISABLE' ) &&
						is_dir( WPSSO_PLUGINDIR.'lib/pro/' ) ? true : false;
					break;
				case 'head':
					$ret = apply_filters( $this->p->cf['lca'].'_is_avail_'.$key, 
						( ! SucomUtil::get_const( 'WPSSO_HEAD_HTML_DISABLE' ) &&
							empty( $_SERVER['WPSSO_HEAD_HTML_DISABLE'] ) &&
								empty( $_GET['WPSSO_HEAD_HTML_DISABLE'] ) ? 
									true : false ) );
					break;
				default:
					$ret = false;	// just in case
					break;
			}
			return $ret;
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
						( $uerr = SucomUpdate::get_umsg( $lca ) ?
							false : $ins ) ) ? $uerr : false ) : $ins;
		}

		public function get_ext_list() {
			$ext_list = array();
			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( empty( $info['version'] ) )	// only active extensions
					continue;
				$ins = $this->aop( $ext, false );
				$ext_list[] = $info['short'].( $ins ? ' Pro' : '' ).' '.$info['version'].'/'.
					( $this->aop( $ext, true, $this->p->is_avail['aop'] ) ? 'L' : 
						( $ins ? 'U' : 'G' ) );
			}
			return $ext_list;
		}

		private function has_optval( $opt_name ) { 
			if ( ! empty( $opt_name ) && 
				! empty( $this->p->options[$opt_name] ) && 
					$this->p->options[$opt_name] !== 'none' )
						return true;
		}
	}
}

?>
