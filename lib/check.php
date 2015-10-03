<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoCheck' ) ) {

	class WpssoCheck {

		private $p;
		private $active_plugins = array();

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

			if ( is_object( $this->p->debug ) && 
				method_exists( $this->p->debug, 'mark' ) )
					$this->p->debug->mark();

			$this->active_plugins = WpssoUtil::active_plugins();

			if ( ! is_admin() ) {
				// disable jetPack open graph meta tags
				if ( class_exists( 'JetPack' ) || isset( $this->active_plugins['jetpack/jetpack.php'] ) ) {
					add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
					add_filter( 'jetpack_enable_open_graph', '__return_false', 99 );
					add_filter( 'jetpack_disable_twitter_cards', '__return_true', 99 );
				}
	
				// disable Yoast SEO opengraph, twitter, publisher, and author meta tags
				if ( function_exists( 'wpseo_init' ) || isset( $this->active_plugins['wordpress-seo/wp-seo.php'] ) ) {
					global $wpseo_og;
					if ( is_object( $wpseo_og ) && 
						( $prio = has_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ) ) ) ) {
							$ret = remove_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ), $prio );
					}
					global $wpseo_twitter;
					if ( is_object( $wpseo_twitter ) && 
						( $prio = has_action( 'wpseo_head', array( $wpseo_twitter, 'twitter' ) ) ) ) {
							$ret = remove_action( 'wpseo_head', array( $wpseo_twitter, 'twitter' ), $prio );
					}
					if ( ! empty( $this->p->options['seo_publisher_url'] ) ) {
						global $wpseo_front;
						if ( is_object( $wpseo_front ) && 
							( $prio = has_action( 'wpseo_head', array( $wpseo_front, 'publisher' ) ) ) )
								$ret = remove_action( 'wpseo_head', array( $wpseo_front, 'publisher' ), $prio );
					}
					if ( ! empty( $this->p->options['seo_def_author_id'] ) &&
						! empty( $this->p->options['seo_def_author_on_index'] ) ) {
						global $wpseo_front;
						if ( is_object( $wpseo_front ) && 
							( $prio = has_action( 'wpseo_head', array( $wpseo_front, 'author' ) ) ) )
								$ret = remove_action( 'wpseo_head', array( $wpseo_front, 'author' ), $prio );
					}
					if ( ! empty( $this->p->options['schema_website_json'] ) ) {
						add_filter( 'wpseo_json_ld_output', '__return_empty_array', 99 );
					}
				}

				if ( class_exists( 'Ngfb' ) || isset( $this->active_plugins['nextgen-facebook/nextgen-facebook.php'] ) ) {
					if ( ! defined( 'NGFB_META_TAGS_DISABLE' ) )
						define( 'NGFB_META_TAGS_DISABLE', true );
				}
			}
			do_action( $this->p->cf['lca'].'_init_check', $this->active_plugins );
		}

		private function get_avail_check( $key ) {
			switch ( $key ) {
				case 'aop':
					return ( ! defined( 'WPSSO_PRO_MODULE_DISABLE' ) ||
					( defined( 'WPSSO_PRO_MODULE_DISABLE' ) && ! WPSSO_PRO_MODULE_DISABLE ) ) &&
					is_dir( WPSSO_PLUGINDIR.'lib/pro/' ) ? true : false;
					break;
				case 'mt':
				case 'metatags':
					return ( ! defined( 'WPSSO_META_TAGS_DISABLE' ) || 
					( defined( 'WPSSO_META_TAGS_DISABLE' ) && ! WPSSO_META_TAGS_DISABLE ) ) &&
					empty( $_SERVER['WPSSO_META_TAGS_DISABLE'] ) &&
					empty( $_GET['WPSSO_META_TAGS_DISABLE'] ) ? true : false;	// allow meta tags to be disabled with query argument
					break;
				default:
					return false;
					break;
			}
		}

		public function get_avail() {
			$ret = array();

			$ret['curl'] = function_exists( 'curl_init' ) ? true : false;
			$ret['postthumb'] = function_exists( 'has_post_thumbnail' ) ? true : false;
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
						case 'media-photon':
							if ( class_exists( 'Jetpack' ) && 
								method_exists( 'Jetpack', 'get_active_modules' ) && 
								in_array( 'photon', Jetpack::get_active_modules() ) )
									$ret[$sub]['*'] = $ret[$sub][$id] = true;
							break;
						case 'seo-aioseop':
							$chk['class'] = 'All_in_One_SEO_Pack';
							$chk['plugin'] = 'all-in-one-seo-pack/all-in-one-seo-pack.php';
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
						case 'admin-image-dimensions':
						case 'admin-post':
						case 'admin-taxonomy':
						case 'admin-user':
						case 'util-post':
						case 'util-taxonomy':
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
							$chk['class'] = 'WpssoUm';
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

		public function aop( $lca = '', $li = true, $rv = true ) {
			$lca = empty( $lca ) ? 
				$this->p->cf['lca'] : $lca;
			$kn = $lca.'-'.$li.'-'.$rv;
			if ( isset( self::$c[$kn] ) )
				return self::$c[$kn];
			$on = 'plugin_'.$lca.'_tid';
			$uca = strtoupper( $lca );
			$ins = ( defined( $uca.'_PLUGINDIR' ) &&
				is_dir( constant( $uca.'_PLUGINDIR' ).'lib/pro/' ) ) ? $rv : false;
			return self::$c[$kn] = $li === true ? 
				( ( ! empty( $this->p->options[$on] ) && 
					$ins && class_exists( 'SucomUpdate' ) &&
						( $um = SucomUpdate::get_umsg( $lca ) ? 
							false : $ins ) ) ? $um : false ) : $ins;
		}
	}
}

?>
