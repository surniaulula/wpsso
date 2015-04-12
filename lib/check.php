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

		private static $extend_checks = array(
			'seo' => array(
				'seou' => 'SEO Ultimate',
			),
			'util' => array(
				'um' => 'Update Manager',
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
	
				// disable WordPress SEO opengraph, twitter, publisher, and author meta tags
				if ( function_exists( 'wpseo_init' ) || isset( $this->active_plugins['wordpress-seo/wp-seo.php'] ) ) {
					global $wpseo_og;
					if ( is_object( $wpseo_og ) && 
						( $prio = has_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ) ) ) )
							$ret = remove_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ), $prio );
					if ( ! empty( $this->p->options['tc_enable'] ) && $this->aop() ) {
						global $wpseo_twitter;
						if ( is_object( $wpseo_twitter ) && 
							( $prio = has_action( 'wpseo_head', array( $wpseo_twitter, 'twitter' ) ) ) )
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
				}

				if ( class_exists( 'Ngfb' ) || isset( $this->active_plugins['nextgen-facebook/nextgen-facebook.php'] ) ) {
					if ( ! defined( 'NGFB_META_TAGS_DISABLE' ) )
						define( 'NGFB_META_TAGS_DISABLE', true );
				}
			}
			do_action( $this->p->cf['lca'].'_init_check' );
		}

		private function get_avail_check( $key ) {
			switch ( $key ) {
				case 'aop':
					return ( ! defined( 'WPSSO_PRO_MODULE_DISABLE' ) ||
					( defined( 'WPSSO_PRO_MODULE_DISABLE' ) && ! WPSSO_PRO_MODULE_DISABLE ) ) &&
					file_exists( WPSSO_PLUGINDIR.'lib/pro/' ) ? true : false;
					break;
				case 'mt':
				case 'metatags':
					return ( ! defined( 'WPSSO_META_TAGS_DISABLE' ) || 
					( defined( 'WPSSO_META_TAGS_DISABLE' ) && ! WPSSO_META_TAGS_DISABLE ) ) &&
					empty( $_SERVER['WPSSO_META_TAGS_DISABLE'] ) &&
					empty( $_GET['WPSSO_META_TAGS_DISABLE'] ) ? true : false;	// allow meta tags to be disabled with query argument
					break;
			}
		}

		public function get_avail() {
			$ret = array();

			$ret['curl'] = function_exists( 'curl_init' ) ? true : false;
			$ret['postthumb'] = function_exists( 'has_post_thumbnail' ) ? true : false;
			$ret['metatags'] = $this->get_avail_check( 'mt' );
			$ret['aop'] = $this->get_avail_check( 'aop' );

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
						case 'head-twittercard':
							$chk['optval'] = 'tc_enable';
							break;
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
						case 'admin-postmeta':
						case 'admin-user':
						case 'util-postmeta':
						case 'util-user':
							$ret[$sub]['*'] = $ret[$sub][$id] = true;
							break;
						case 'util-language':
							$chk['optval'] = 'plugin_filter_lang';
							break;
						case 'util-um':
							$chk['class'] = 'WpssoUm';
							$chk['plugin'] = 'wpsso-um/wpsso-um.php';
							break;
					}
					if ( ( ! empty( $chk['function'] ) && function_exists( $chk['function'] ) ) || 
						( ! empty( $chk['class'] ) && class_exists( $chk['class'] ) ) ||
						( ! empty( $chk['plugin'] ) && isset( $this->active_plugins[$chk['plugin']] ) ) ||
						( ! empty( $chk['optval'] ) && 
							! empty( $this->p->options[$chk['optval']] ) && 
							$this->p->options[$chk['optval']] !== 'none' ) )
								$ret[$sub]['*'] = $ret[$sub][$id] = true;
				}
			}
			return apply_filters( $this->p->cf['lca'].'_get_avail', $ret );
		}

		public function is_aop( $lca = '' ) { 
			return $this->aop( $lca );
		}

		public function aop( $lca = '', $active = true ) {
			$lca = empty( $lca ) ? 
				$this->p->cf['lca'] : $lca;
			$uca = strtoupper( $lca );
			$available = isset( $this->p->is_avail['aop'] ) ? 
				$this->p->is_avail['aop'] : $this->get_avail_check( 'aop' );
			$installed = ( $available && defined( $uca.'_PLUGINDIR' ) &&
				is_dir( constant( $uca.'_PLUGINDIR' ).'lib/pro/' ) ) ? true : false;
			return $active === true ? ( ( ! empty( $this->p->options['plugin_'.$lca.'_tid'] ) && 
				$installed && class_exists( 'SucomUpdate' ) &&
					( $umsg = SucomUpdate::get_umsg( $lca ) ? 
						false : $installed ) ) ? 
							$umsg : false ) : $installed;
		}

		public function conflict_warnings() {
			if ( ! is_admin() ) 
				return;

			$lca = $this->p->cf['lca'];
			$short = $this->p->cf['plugin'][$lca]['short'];
			$short_pro = $short.' Pro';
			$purchase_url = $this->p->cf['plugin'][$lca]['url']['purchase'];
			$log_pre =  __( 'plugin conflict detected', WPSSO_TEXTDOM ) . ' - ';
			$err_pre =  __( 'Plugin conflict detected', WPSSO_TEXTDOM ) . ' - ';

			// PHP
			if ( empty( $this->p->is_avail['curl'] ) ) {
				if ( ! empty( $this->p->options['plugin_file_cache_hrs'] ) ) {
					$this->p->debug->log( 'file caching is enabled but curl function is missing' );
					$this->p->notice->err( sprintf( __( 'File caching has been enabled, but PHP\'s <a href="%s" target="_blank">Client URL Library</a> (cURL) is missing.', WPSSO_TEXTDOM ), 'http://ca3.php.net/curl' ).' '.__( 'Please contact your hosting provider to have the missing library installed.', WPSSO_TEXTDOM ) );
				}
			}

			// WordPress SEO by Yoast
			if ( $this->p->is_avail['seo']['wpseo'] === true ) {
				$opts = get_option( 'wpseo_social' );
				if ( ! empty( $opts['opengraph'] ) ) {
					$this->p->debug->log( $log_pre.'wpseo opengraph meta data option is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'Please uncheck the \'<em>Add Open Graph meta data</em>\' Facebook option in the <a href="%s">WordPress SEO by Yoast: Social</a> settings.', WPSSO_TEXTDOM ), get_admin_url( null, 'admin.php?page=wpseo_social#top#facebook' ) ) );
				}
				if ( ! empty( $this->p->options['tc_enable'] ) && $this->aop() && ! empty( $opts['twitter'] ) ) {
					$this->p->debug->log( $log_pre.'wpseo twitter meta data option is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'Please uncheck the \'<em>Add Twitter card meta data</em>\' Twitter option in the <a href="%s">WordPress SEO by Yoast: Social</a> settings.', WPSSO_TEXTDOM ), get_admin_url( null, 'admin.php?page=wpseo_social#top#twitterbox' ) ) );
				}
				if ( ! empty( $opts['googleplus'] ) ) {
					$this->p->debug->log( $log_pre.'wpseo googleplus meta data option is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'Please uncheck the \'<em>Add Google+ specific post meta data</em>\' Google+ option in the <a href="%s">WordPress SEO by Yoast: Social</a> settings.', WPSSO_TEXTDOM ), get_admin_url( null, 'admin.php?page=wpseo_social#top#google' ) ) );
				}
				if ( ! empty( $opts['plus-publisher'] ) ) {
					$this->p->debug->log( $log_pre.'wpseo google plus publisher option is defined' );
					$this->p->notice->err( $err_pre.sprintf( __( 'Please remove the \'<em>Google Publisher Page</em>\' value entered in the <a href="%s">WordPress SEO by Yoast: Social</a> settings.', WPSSO_TEXTDOM ), get_admin_url( null, 'admin.php?page=wpseo_social#top#google' ) ) );
				}

				// remove false error messages from WordPress SEO notifications
				if ( ( $wpseo_notif = get_transient( Yoast_Notification_Center::TRANSIENT_KEY ) ) !== false ) {
					$lca = $this->p->cf['lca'];
					$plugin_name = $this->p->cf['plugin'][$lca]['name'];
					$wpseo_notif = json_decode( $wpseo_notif );
					if ( ! empty( $wpseo_notif ) ) {
						foreach ( $wpseo_notif as $num => $msgs ) {
							if ( $msgs->type == 'error' && strpos( $msgs->message, ': '.$plugin_name ) !== false ) {
								unset( $wpseo_notif[$num] );
								set_transient( Yoast_Notification_Center::TRANSIENT_KEY,
									json_encode( $wpseo_notif ) );
							}
						}
					}
				}
			}

			// SEO Ultimate
			if ( $this->p->is_avail['seo']['seou'] === true ) {
				$opts = get_option( 'seo_ultimate' );
				if ( ! empty( $opts['modules'] ) && is_array( $opts['modules'] ) ) {
					if ( array_key_exists( 'opengraph', $opts['modules'] ) && $opts['modules']['opengraph'] !== -10 ) {
						$this->p->debug->log( $log_pre.'seo ultimate opengraph module is enabled' );
						$this->p->notice->err( $err_pre.sprintf( __( 'Please disable the \'<em>Open Graph Integrator</em>\' module in the <a href="%s">SEO Ultimate plugin Module Manager</a>.', WPSSO_TEXTDOM ), get_admin_url( null, 'admin.php?page=seo' ) ) );
					}
				}
			}

			// All in One SEO Pack
			if ( $this->p->is_avail['seo']['aioseop'] === true ) {
				$opts = get_option( 'aioseop_options' );
				if ( ! empty( $opts['modules']['aiosp_feature_manager_options']['aiosp_feature_manager_enable_opengraph'] ) ) {
					$this->p->debug->log( $log_pre.'aioseop social meta fetaure is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'Please deactivate the \'<em>Social Meta</em>\' feature in the <a href="%s">All in One SEO Pack Feature Manager</a>.', WPSSO_TEXTDOM ), get_admin_url( null, 'admin.php?page=all-in-one-seo-pack/aioseop_feature_manager.php' ) ) );
				}
				if ( array_key_exists( 'aiosp_google_disable_profile', $opts ) && empty( $opts['aiosp_google_disable_profile'] ) ) {
					$this->p->debug->log( $log_pre.'aioseop google plus profile is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'Please check the \'<em>Disable Google Plus Profile</em>\' option in the <a href="%s">All in One SEO Pack Plugin Options</a>.', WPSSO_TEXTDOM ), get_admin_url( null, 'admin.php?page=all-in-one-seo-pack/aioseop_class.php' ) ) );
				}
			}

			// JetPack Photon
			if ( $this->p->is_avail['media']['photon'] === true && ! $this->aop() ) {
				$this->p->debug->log( $log_pre.'jetpack photon is enabled' );
				$this->p->notice->err( $err_pre.'<strong>'. __( 'JetPack Photon cripples the WordPress image size functions.', WPSSO_TEXTDOM ).'</strong> '.sprintf( __( 'Please <a href="%s">disable JetPack Photon</a> or disable the %s Free version plugin.', WPSSO_TEXTDOM ), get_admin_url( null, 'admin.php?page=jetpack' ), $short ).' '.sprintf( __( 'You may also upgrade to the <a href="%s">%s version</a>, which includes a <a href="%s">module for JetPack Photon</a>.', WPSSO_TEXTDOM ), $purchase_url, $short_pro, 'http://surniaulula.com/codex/plugins/wpsso/notes/modules/jetpack-photon/' ) );
			}

			/*
			 * Other Conflicting Plugins
			 */

			// NextGEN Facebook (NGFB)
			if ( class_exists( 'Ngfb' ) ) {
                                $this->p->debug->log( $log_pre.'NGFB plugin is active' );
                                $this->p->notice->err( $err_pre.sprintf( __( 'Please <a href="%s">deactivate the NextGEN Facebook (NGFB) plugin</a> to prevent duplicate and conflicting features.', WPSSO_TEXTDOM ), get_admin_url( null, 'plugins.php?s=nextgen-facebook/nextgen-facebook.php' ) ) );
                        }

			// WooCommerce
			if ( class_exists( 'Woocommerce' ) && ! $this->aop() && ! empty( $this->p->options['plugin_filter_content'] ) ) {
				$this->p->debug->log( $log_pre.'woocommerce shortcode support not available in the admin interface' );
				$this->p->notice->err( $err_pre.'<strong>'.__( 'WooCommerce does not include shortcode support in the admin interface.', WPSSO_TEXTDOM ).'</strong> '.sprintf( __( 'Please uncheck the \'<em>Apply Content Filters</em>\' option on the <a href="%s">%s Advanced settings page</a>.', WPSSO_TEXTDOM ), $this->p->util->get_admin_url( 'advanced' ), $this->p->cf['menu'] ).' '.sprintf( __( 'You may also upgrade to the <a href="%s">%s version</a>, which includes a <a href="%s">module for WooCommerce</a>.', WPSSO_TEXTDOM ), $purchase_url, $short_pro, 'http://surniaulula.com/codex/plugins/wpsso/notes/modules/woocommerce/' ) );
			}

			// Facebook
  			if ( class_exists( 'Facebook_Loader' ) ) {
                                $this->p->debug->log( $log_pre.'facebook plugin is active' );
                                $this->p->notice->err( $err_pre.sprintf( __( 'Please <a href="%s">deactivate the Facebook plugin</a> to prevent duplicate Open Graph meta tags in your webpage headers.', WPSSO_TEXTDOM ), get_admin_url( null, 'plugins.php?s=facebook/facebook.php' ) ) );
                        }
		}

		public function activated_plugin_check( $plugin, $network_activation ) {
			if ( ! is_admin() ) 
				return;

			if ( WpssoUtil::active_plugins( 'wordpress-seo/wp-seo.php' ) ) {
				// remove false error messages from WordPress SEO
				if ( ( $wpseo_notif = get_transient( Yoast_Notification_Center::TRANSIENT_KEY ) ) !== false ) {
					$lca = $this->p->cf['lca'];
					$plugin_name = $this->p->cf['plugin'][$lca]['name'];
					$wpseo_notif = json_decode( $wpseo_notif );
					if ( ! empty( $wpseo_notif ) ) {
						foreach ( $wpseo_notif as $num => $msgs ) {
							if ( $msgs->type == 'error' && strpos( $msgs->message, ': '.$plugin_name ) !== false ) {
								unset( $wpseo_notif[$num] );
								set_transient( Yoast_Notification_Center::TRANSIENT_KEY,
									json_encode( $wpseo_notif ) );
							}
						}
					}
				}
			}
		}
	}
}

?>
