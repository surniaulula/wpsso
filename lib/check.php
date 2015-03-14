<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoCheck' ) ) {

	class WpssoCheck {

		private $p;
		private $active_plugins = array();
		private $network_plugins = array();
		private static $mac = array(
			'seo' => array(
				'seou' => 'SEO Ultimate',
			),
		);

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( is_object( $this->p->debug ) && 
				method_exists( $this->p->debug, 'mark' ) )
					$this->p->debug->mark();

			$this->active_plugins = get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$this->network_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
				if ( ! empty( $this->network_plugins ) )
					$this->active_plugins = array_merge( $this->active_plugins, $this->network_plugins );
			}

			// disable jetPack open graph meta tags
			if ( class_exists( 'JetPack' ) || in_array( 'jetpack/jetpack.php', $this->active_plugins ) ) {
				add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );	// deprecated, but correct filter is checked too early
				add_filter( 'jetpack_enable_open_graph', '__return_false', 99 );
				add_filter( 'jetpack_disable_twitter_cards', '__return_true', 99 );
			}

			// disable WordPress SEO opengraph, twitter, publisher, and author meta tags
			if ( function_exists( 'wpseo_init' ) || in_array( 'wordpress-seo/wp-seo.php', $this->active_plugins ) ) {

				global $wpseo_og;
				if ( is_object( $wpseo_og ) && ( $prio = has_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ) ) ) )
					$ret = remove_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ), $prio );

				if ( ! empty( $this->p->options['tc_enable'] ) && $this->aop() ) {
					global $wpseo_twitter;
					if ( is_object( $wpseo_twitter ) && ( $prio = has_action( 'wpseo_head', array( $wpseo_twitter, 'twitter' ) ) ) )
						$ret = remove_action( 'wpseo_head', array( $wpseo_twitter, 'twitter' ), $prio );
				}

				if ( ! empty( $this->p->options['seo_publisher_url'] ) ) {
					global $wpseo_front;
					if ( is_object( $wpseo_front ) && ( $prio = has_action( 'wpseo_head', array( $wpseo_front, 'publisher' ) ) ) )
						$ret = remove_action( 'wpseo_head', array( $wpseo_front, 'publisher' ), $prio );
				}

				if ( ! empty( $this->p->options['seo_def_author_id'] ) &&
					! empty( $this->p->options['seo_def_author_on_index'] ) ) {
					global $wpseo_front;
					if ( is_object( $wpseo_front ) && ( $prio = has_action( 'wpseo_head', array( $wpseo_front, 'author' ) ) ) )
						$ret = remove_action( 'wpseo_head', array( $wpseo_front, 'author' ), $prio );
				}
			}

			// disable the ngfb meta tags
			if ( class_exists( 'Ngfb' ) || 
				in_array( 'nextgen-facebook/nextgen-facebook.php', $this->active_plugins ) )
					if ( ! defined( 'NGFB_META_TAGS_DISABLE' ) )
						define( 'NGFB_META_TAGS_DISABLE', true );

			do_action( $this->p->cf['lca'].'_init_check', $this->active_plugins );
		}

		public function get_active() {
			return $this->active_plugins;
		}

		private function get_avail_check( $key ) {
			switch ( $key ) {
				case 'aop':
					return ( ! defined( 'WPSSO_PRO_MODULE_DISABLE' ) ||
					( defined( 'WPSSO_PRO_MODULE_DISABLE' ) && ! WPSSO_PRO_MODULE_DISABLE ) ) &&
					file_exists( WPSSO_PLUGINDIR.'lib/pro/head/twittercard.php' ) ? true : false;
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
			$ret['mbdecnum'] = function_exists( 'mb_decode_numericentity' ) ? true : false;
			$ret['postthumb'] = function_exists( 'has_post_thumbnail' ) ? true : false;
			$ret['metatags'] = $this->get_avail_check( 'mt' );
			$ret['aop'] = $this->get_avail_check( 'aop' );

			foreach ( $this->p->cf['cache'] as $name => $val ) {
				$constant_name = 'WPSSO_'.strtoupper( $name ).'_CACHE_DISABLE';
				$ret['cache'][$name] = defined( $constant_name ) &&
					constant( $constant_name ) ? false : true;
			}

			foreach ( SucomUtil::array_merge_recursive_distinct( $this->p->cf['*']['lib']['pro'], self::$mac ) as $sub => $lib ) {
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
						case 'admin-postmeta':
						case 'admin-user':
						case 'util-postmeta':
						case 'util-user':
							$ret[$sub]['*'] = $ret[$sub][$id] = true;
							break;
						case 'util-language':
							$chk['optval'] = 'plugin_filter_lang';
							break;
					}
					if ( ( ! empty( $chk['function'] ) && function_exists( $chk['function'] ) ) || 
						( ! empty( $chk['class'] ) && class_exists( $chk['class'] ) ) ||
						( ! empty( $chk['plugin'] ) && in_array( $chk['plugin'], $this->active_plugins ) ) ||
						( ! empty( $chk['optval'] ) && 
							! empty( $this->p->options[$chk['optval']] ) && 
							$this->p->options[$chk['optval']] !== 'none' ) )
								$ret[$sub]['*'] = $ret[$sub][$id] = true;
				}
			}
			return apply_filters( $this->p->cf['lca'].'_get_avail', $ret );
		}

		// called from WpssoAdmin
		public function conflict_warnings() {

			if ( ! is_admin() ) 
				return;

			$lca = $this->p->cf['lca'];
			$short = $this->p->cf['plugin'][$lca]['short'];
			$short_pro = $short.' Pro';
			$purchase_url = $this->p->cf['plugin'][$lca]['url']['purchase'];
			$conflict_log_prefix =  __( 'plugin conflict detected', WPSSO_TEXTDOM ) . ' - ';
			$conflict_err_prefix =  __( 'Plugin conflict detected', WPSSO_TEXTDOM ) . ' - ';

			// PHP
			if ( empty( $this->p->is_avail['mbdecnum'] ) ) {
				$this->p->debug->log( 'mb_decode_numericentity() function missing (required to decode UTF8 entities)' );
				$this->p->notice->err( sprintf( 
					__( 'The <code><a href="%s" target="_blank">mb_decode_numericentity()</a></code> function (available since PHP v4.0.6) is missing.', WPSSO_TEXTDOM ),
					__( 'http://php.net/manual/en/function.mb-decode-numericentity.php', WPSSO_TEXTDOM ) ).' '.
					__( 'This function is required to decode UTF8 entities.', WPSSO_TEXTDOM ).' '.
					__( 'Please update your PHP installation (install \'php-mbstring\' on most Linux distros).', WPSSO_TEXTDOM ) );
			}
			if ( empty( $this->p->is_avail['curl'] ) ) {
				if ( ! empty( $this->p->options['plugin_file_cache_hrs'] ) ) {
					$this->p->debug->log( 'file caching is enabled but curl function is missing' );
					$this->p->notice->err( sprintf( __( 'File caching has been enabled, but PHP\'s <a href="%s" target="_blank">Client URL Library</a> '.
						'(cURL) is missing.', WPSSO_TEXTDOM ), 'http://ca3.php.net/curl' ).' '.
						 __( 'Please contact your hosting provider to install the missing library.', WPSSO_TEXTDOM ) );
				}
			}

			// Yoast WordPress SEO
			if ( $this->p->is_avail['seo']['wpseo'] === true ) {
				$opts = get_option( 'wpseo_social' );
				if ( ! empty( $opts['opengraph'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'wpseo opengraph meta data option is enabled' );
					$this->p->notice->err( $conflict_err_prefix.
						sprintf( __( 'Please uncheck the \'<em>Add Open Graph meta data</em>\' Facebook option in the '.
							'<a href="%s">Yoast WordPress SEO: Social</a> settings.', WPSSO_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=wpseo_social' ) ) );
				}
				if ( ! empty( $this->p->options['tc_enable'] ) && $this->aop() && ! empty( $opts['twitter'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'wpseo twitter meta data option is enabled' );
					$this->p->notice->err( $conflict_err_prefix.
						sprintf( __( 'Please uncheck the \'<em>Add Twitter Card meta data</em>\' Twitter option in the '.
							'<a href="%s">Yoast WordPress SEO: Social</a> settings.', WPSSO_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=wpseo_social' ) ) );
				}
				if ( ! empty( $opts['googleplus'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'wpseo googleplus meta data option is enabled' );
					$this->p->notice->err( $conflict_err_prefix.
						sprintf( __( 'Please uncheck the \'<em>Add Google+ specific post meta data</em>\' Google+ option in the '.
							'<a href="%s">Yoast WordPress SEO: Social</a> settings.', WPSSO_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=wpseo_social' ) ) );
				}
				if ( ! empty( $this->p->options['seo_publisher_url'] ) && ! empty( $opts['plus-publisher'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'wpseo google plus publisher option is defined' );
					$this->p->notice->err( $conflict_err_prefix.
						sprintf( __( 'Please remove the \'<em>Google Publisher Page</em>\' value entered in the '.
							'<a href="%s">Yoast WordPress SEO: Social</a> settings.', WPSSO_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=wpseo_social' ) ) );
				}
			}

			// SEO Ultimate
			if ( $this->p->is_avail['seo']['seou'] === true ) {
				$opts = get_option( 'seo_ultimate' );
				if ( ! empty( $opts['modules'] ) && is_array( $opts['modules'] ) ) {
					if ( array_key_exists( 'opengraph', $opts['modules'] ) && $opts['modules']['opengraph'] !== -10 ) {
						$this->p->debug->log( $conflict_log_prefix.'seo ultimate opengraph module is enabled' );
						$this->p->notice->err( $conflict_err_prefix.
							sprintf( __( 'Please disable the \'<em>Open Graph Integrator</em>\' module in the '.
								'<a href="%s">SEO Ultimate plugin Module Manager</a>.', WPSSO_TEXTDOM ), 
								get_admin_url( null, 'admin.php?page=seo' ) ) );
					}
				}
			}

			// All in One SEO Pack
			if ( $this->p->is_avail['seo']['aioseop'] === true ) {
				$opts = get_option( 'aioseop_options' );
				if ( ! empty( $opts['modules']['aiosp_feature_manager_options']['aiosp_feature_manager_enable_opengraph'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'aioseop social meta fetaure is enabled' );
					$this->p->notice->err( $conflict_err_prefix.
						sprintf( __( 'Please deactivate the \'<em>Social Meta</em>\' feature in the '.
							'<a href="%s">All in One SEO Pack Feature Manager</a>.', WPSSO_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=all-in-one-seo-pack/aioseop_feature_manager.php' ) ) );
				}
				if ( array_key_exists( 'aiosp_google_disable_profile', $opts ) && empty( $opts['aiosp_google_disable_profile'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'aioseop google plus profile is enabled' );
					$this->p->notice->err( $conflict_err_prefix.
						sprintf( __( 'Please check the \'<em>Disable Google Plus Profile</em>\' option in the '.
							'<a href="%s">All in One SEO Pack Plugin Options</a>.', WPSSO_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=all-in-one-seo-pack/aioseop_class.php' ) ) );
				}
			}

			// JetPack Photon
			if ( $this->p->is_avail['media']['photon'] === true && ! $this->aop() ) {
				$this->p->debug->log( $conflict_log_prefix.'jetpack photon is enabled' );
				$this->p->notice->err( $conflict_err_prefix.'<strong>'.
					 __( 'JetPack Photon cripples the WordPress image size functions.', WPSSO_TEXTDOM ).'</strong> '.
					sprintf( __( 'Please <a href="%s">disable JetPack Photon</a> or disable the %s Free version plugin.', WPSSO_TEXTDOM ),
						get_admin_url( null, 'admin.php?page=jetpack' ), $short ).' '.
					sprintf( __( 'You may also upgrade to the <a href="%s">%s version</a>, which includes a <a href="%s">module for JetPack Photon</a>.', WPSSO_TEXTDOM ), 
						$purchase_url, $short_pro, 'http://surniaulula.com/codex/plugins/iwpsso/notes/modules/jetpack-photon/' ) );
			}

			/*
			 * Other Conflicting Plugins
			 */

			// NextGEN Facebook (NGFB)
			if ( class_exists( 'Ngfb' ) || 
				in_array( 'nextgen-facebook/nextgen-facebook.php', $this->active_plugins ) ) {

                                $this->p->debug->log( $conflict_log_prefix.'NGFB plugin is active' );
                                $this->p->notice->err( $conflict_err_prefix. 
					sprintf( __( 'Please <a href="%s">deactivate the NextGEN Facebook (NGFB) plugin</a> to prevent duplicate and conflicting features.', WPSSO_TEXTDOM ), 
						get_admin_url( null, 'plugins.php?s=nextgen-facebook/nextgen-facebook.php' ) ) );
                        }

			// WooCommerce
			if ( class_exists( 'Woocommerce' ) && ! $this->aop() && ! empty( $this->p->options['plugin_filter_content'] ) ) {
				$this->p->debug->log( $conflict_log_prefix.'woocommerce shortcode support not available in the admin interface' );
				$this->p->notice->err( $conflict_err_prefix.'<strong>'.
					__( 'WooCommerce does not include shortcode support in the admin interface.', WPSSO_TEXTDOM ).'</strong> '.
					sprintf( __( 'Please uncheck the \'<em>Apply Content Filters</em>\' option on the <a href="%s">%s Advanced settings page</a>.', WPSSO_TEXTDOM ),  
						$this->p->util->get_admin_url( 'advanced' ), $this->p->cf['menu'] ).' '.
					sprintf( __( 'You may also upgrade to the <a href="%s">%s version</a>, which includes a <a href="%s">module for WooCommerce</a>.', WPSSO_TEXTDOM ), 
						$purchase_url, $short_pro, 'http://surniaulula.com/codex/plugins/wpsso/notes/modules/woocommerce/' ) );
			}

			// WooCommerce ShareYourCart Extension
			if ( class_exists( 'ShareYourCartWooCommerce' ) ) {
				$opts = get_option( 'woocommerce_shareyourcart_settings' );
				if ( ! empty( $opts['enabled'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'woocommerce shareyourcart extension is enabled' );
					$this->p->notice->err( $conflict_err_prefix.
						__( 'The WooCommerce ShareYourCart Extension does not provide an option to turn off its Open Graph meta tags.', WPSSO_TEXTDOM ).' '.
						sprintf( __( 'Please disable the extension on the <a href="%s">ShareYourCart Integration Tab</a>.', WPSSO_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=woocommerce&tab=integration&section=shareyourcart' ) ) );
				}
			}

			// Facebook
  			if ( class_exists( 'Facebook_Loader' ) ) {
                                $this->p->debug->log( $conflict_log_prefix.'facebook plugin is active' );
                                $this->p->notice->err( $conflict_err_prefix. 
					sprintf( __( 'Please <a href="%s">deactivate the Facebook plugin</a> to prevent duplicate Open Graph meta tags in your webpage headers.', WPSSO_TEXTDOM ), 
						get_admin_url( null, 'plugins.php?s=facebook/facebook.php' ) ) );
                        }

			// AddThis Social Bookmarking Widget
			if ( defined( 'ADDTHIS_INIT' ) && ADDTHIS_INIT && 
				( ! empty( $this->p->options['plugin_filter_content'] ) || ! empty( $this->p->options['plugin_filter_excerpt'] ) ) ) {
				$this->p->debug->log( $conflict_log_prefix.'addthis has broken excerpt / content filters' );
				$this->p->notice->err( $conflict_err_prefix. 
					__( 'The AddThis Social Bookmarking Widget has incorrectly coded content and excerpt filters.', WPSSO_TEXTDOM ).' '.
					sprintf( __( 'Please uncheck the \'<em>Apply Content and Excerpt Filters</em>\' options on the <a href="%s">%s Advanced settings page</a>.', WPSSO_TEXTDOM ),  
						$this->p->util->get_admin_url( 'advanced' ), $this->p->cf['menu'] ) ).' '.
					__( 'Disabling content filters will prevent shortcodes from being expanded, which may lead to incorrect / incomplete description meta tags.', WPSSO_TEXTDOM );
			}

			// Slick Social Share Buttons
			if ( class_exists( 'dc_jqslicksocial_buttons' ) ) {
				$opts = get_option( 'dcssb_options' );
				if ( empty( $opts['disable_opengraph'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'slick social share buttons opengraph is enabled' );
					$this->p->notice->err( $conflict_err_prefix.
						sprintf( __( 'Please check the \'<em>Disable Opengraph</em>\' option on the <a href="%s">Slick Social Share Buttons</a>.', WPSSO_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=slick-social-share-buttons' ) ) );
				}
			}
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
	}
}

?>
