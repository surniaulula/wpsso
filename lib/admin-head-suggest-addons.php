<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoAdminHeadSuggestAddons' ) ) {

	class WpssoAdminHeadSuggestAddons {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by WpssoAdminHeadSuggest->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;
		}

		public function suggest( $suggest_max = 2 ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'suggest_max' => $suggest_max,
				) );
			}

			$suggested = 0;

			if ( ! current_user_can( 'install_plugins' ) ) return $suggested;

			/*
			 * Suggest update manager, woocommerce, sitemaps addons, in that order.
			 */
			foreach ( array( 'update_manager', 'woocommerce', 'sitemaps' ) as $suffix ) {

				$methodname = 'suggest_addons_' . $suffix;
				$suggested  = $suggested + $this->$methodname( $suggest_max - $suggested );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $methodname . ' suggested = ' . $suggested );
				}

				if ( $suggested >= $suggest_max ) break;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'return suggested = ' . $suggested );
			}

			return $suggested;
		}

		private function suggest_addons_update_manager( $suggest_max ) {

			$pkg_info  = $this->p->util->get_pkg_info();	// Uses a local cache.
			$um_info   = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
			$have_tid  = false;
			$suggested = 0;

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $ext_info ) {

				if ( empty( $ext_info[ 'base' ] ) ) continue;	// Just in case.

				if ( empty( $this->p->options[ 'plugin_' . $ext . '_tid' ] ) ) continue;

				$have_tid = true;	// Found at least one plugin with an auth id.

				if ( empty( $um_info[ 'version' ] ) ) break;	// Update manager is not active.

				if ( ! empty( $pkg_info[ $ext ][ 'pdir' ] ) ) continue;	// Already updated to Pro.

				if ( SucomPlugin::is_plugin_installed( $ext_info[ 'base' ] ) ) {

					$this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-updated', array( 'plugin_id' => $ext ) ) );

				} else $this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-installed', array( 'plugin_id' => $ext ) ) );

				if ( ++$suggested >= $suggest_max ) return $suggested;
			}

			if ( $have_tid ) {	// One or more auth ids have been found.

				if ( ! empty( $um_info[ 'version' ] ) ) {	// Update manager is active.

					$rec_version = WpssoConfig::$cf[ 'um' ][ 'rec_version' ];
					$min_version = WpssoConfig::$cf[ 'um' ][ 'min_version' ];

					if ( version_compare( $um_info[ 'version' ], $rec_version, '<' ) ) {

						if ( version_compare( $um_info[ 'version' ], $min_version, '<' ) ) {

							$this->p->notice->err( $this->p->msgs->get( 'notice-um-update-required' ) );

						} else $this->p->notice->warn( $this->p->msgs->get( 'notice-um-update-recommended' ) );

						if ( ++$suggested >= $suggest_max ) return $suggested;
					}

				} else {

					if ( SucomPlugin::is_plugin_installed( $um_info[ 'base' ] ) ) {	// Update manager is installed.

						$this->p->notice->warn( $this->p->msgs->get( 'notice-um-activate-add-on' ) );

					} else $this->p->notice->warn( $this->p->msgs->get( 'notice-um-add-on-required' ) );	// Update manager is not installed.

					if ( ++$suggested >= $suggest_max ) return $suggested;
				}
			}

			return $suggested;
		}

		private function suggest_addons_woocommerce( $suggest_max ) {

			$suggested = 0;

			if ( empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) return $suggested;	// WooCommerce is not active.

			if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) return $suggested;	// Schema markup is disabled.

			// translators: Please ignore - translation uses a different text domain.
			$ecom_plugin_name = __( 'WooCommerce', 'woocommerce' );

			foreach ( array( 'wcmd', 'wcsdt', 'gmf', 'mrp', 'cmcf' ) as $p_ext ) {

				$ext        = 'wpsso' . $p_ext;
				$notice_key = 'suggest-' . $ext. '-for-woocommerce';

				if ( ! empty( $this->p->avail[ 'p_ext' ][ $p_ext ] ) ) continue;

				if ( ! $this->p->notice->is_admin_pre_notices( $notice_key ) ) continue;

				$notice_msg      = '';
				$action_links    = array();	// Init a new action array for the notice message.
				$ext_info        = $this->p->cf[ 'plugin' ][ $ext ];
				$ext_name_transl = _x( $ext_info[ 'name' ], 'plugin name', 'wpsso' );
				$ext_addons_link = $this->p->util->get_admin_url( 'addons#' . $ext, $ext_name_transl );

				if ( $install_activate_link = $this->get_install_activate_addon_link( $ext ) ) {

					$action_links[] = $install_activate_link;
				}

				switch ( $p_ext ) {

					case 'cmcf':

						$notice_msg = sprintf( __( 'If you advertise or sell items on Facebook and Instagram, the %s add-on can provide a product feed for Meta\'s Commerce Manager.', 'wpsso' ), $ext_name_transl ) . ' ';

						$notice_msg .= sprintf( __( 'You should activate the %s add-on if you don\'t already have a plugin to manage your Commerce Manager product feed XML.', 'wpsso' ), $ext_addons_link ) . ' ';

						break;

					case 'gmf':

						$notice_msg = sprintf( __( 'If you have created an account with Google Merchant Center, the %s add-on can provide a product feed for Google Merchant Center.', 'wpsso' ), $ext_name_transl ) . ' ';

						$notice_msg .= sprintf( __( 'You should activate the %s add-on if you don\'t already have a plugin to manage your Google merchant feed XML.', 'wpsso' ), $ext_addons_link ) . ' ';

						break;

					case 'mrp':

						$notice_msg = __( 'Google suggests including a merchant return policy in Schema Product offers markup.', 'wpsso' ) . ' ';

						$notice_msg .= sprintf( __( 'If you have not created a return policy in Google Merchant Center, you should activate the %s add-on to manage return policies and select a return policy for your product offers.', 'wpsso' ), $ext_addons_link ) . ' ';

						break;

					case 'wcmd':

						if ( ! empty( $this->p->avail[ 'ecom' ][ 'woo-add-gtin' ] ) ||
							! empty( $this->p->avail[ 'ecom' ][ 'wpm-product-gtin-wc' ] ) ) continue 2;	// Get another $ext.

						$notice_msg = __( 'Google requires at least one unique product identifier for Schema Product markup, like the product MPN (Manufacturer Part Number), UPC, EAN, GTIN, or ISBN.', 'wpsso' ) . ' ';

						$notice_msg .= sprintf( __( 'The product SKU (Stock Keeping Unit) from %s is not a unique product identifier.',
							'wpsso' ), $ecom_plugin_name ) . ' ';

						$notice_msg .= sprintf( __( 'You should activate the %s add-on if you don\'t already have a plugin to manage unique product identifiers for WooCommerce.', 'wpsso' ), $ext_addons_link ) . ' ';

						break;

					case 'wcsdt':

						$shipping_continents = WC()->countries->get_shipping_continents();	// Since WC v3.6.0.
						$shipping_countries  = WC()->countries->get_shipping_countries();
						$shipping_enabled    = $shipping_continents || $shipping_countries ? true : false;

						if ( ! $shipping_enabled ) continue 2;	// Get another $ext.

						$notice_msg = sprintf( __( 'Product shipping features are enabled in %1$s but the %2$s add-on is not active.',
							'wpsso' ), $ecom_plugin_name, $ext_name_transl ) . ' ';

						$notice_msg .= __( 'Adding shipping details to your Schema Product markup is important if you offer free or low-cost shipping options, as this will make your products more appealing in Google search results.', 'wpsso' ) . ' ';

						break;
				}

				if ( ! empty( $notice_msg ) ) {

					$notice_msg .= SucomUtil::array_to_list_html( $action_links );

					$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );

					if ( ++$suggested >= $suggest_max ) return $suggested;
				}
			}

			return $suggested;
		}

		private function suggest_addons_sitemaps( $suggest_max ) {

			$suggested  = 0;
			$notice_key = 'suggest-wpssowpsm';

			if ( ! empty( $this->p->avail[ 'p_ext' ][ 'wpsm' ] ) ) return $suggested;	// Already active.

			if ( SucomUtilWP::sitemaps_disabled() ) return $suggested;

			if ( $this->p->util->robots->is_disabled() ) return $suggested;

			if ( ! $this->p->notice->is_admin_pre_notices( $notice_key ) ) return $suggested;

			$action_links = array();	// Init a new action array for the notice message.

			if ( $install_activate_link = $this->get_install_activate_addon_link( 'wpssowpsm' ) ) {

				$action_links[] = $install_activate_link;
			}

			$ext_info        = $this->p->cf[ 'plugin' ][ 'wpssowpsm' ];
			$ext_name_transl = _x( $ext_info[ 'name' ], 'plugin name', 'wpsso' );
			$sitemaps_url    = get_site_url( $blog_id = null, $path = '/wp-sitemap.xml' );

			$notice_msg = sprintf( __( 'The <a href="%1$s">WordPress sitemaps XML</a> is enabled, but the %2$s add-on is not active.',
				'wpsso' ), $sitemaps_url, $ext_name_transl ) . ' ';

			$notice_msg .= __( 'You can activate this add-on to manage post and taxonomy types included in the sitemaps XML, exclude posts, pages, custom post types, taxonomy terms (categories, tags, etc.), and/or user profiles, enhance the sitemaps XML with article modification times, images sitemaps, news sitemaps and more.', 'wpsso' );

			$notice_msg .= SucomUtil::array_to_list_html( $action_links );

			$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );

			return ++$suggested;
		}

		private function get_install_activate_addon_link( $ext ) {

			if ( empty( $this->p->cf[ 'plugin' ][ $ext ][ 'base' ] ) ) {	// Just in case.

				return false;
			}

			$ext_info        = $this->p->cf[ 'plugin' ][ $ext ];
			$ext_name_transl = _x( $ext_info[ 'name' ], 'plugin name', 'wpsso' );

			if ( SucomPlugin::is_plugin_installed( $ext_info[ 'base' ] ) ) {

				$search_url = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( $blog_id = null, 'plugins.php' );
				$search_url = add_query_arg( array( 's' => $ext_info[ 'slug' ] ), $search_url );

				return '<a href="' . $search_url . '">' . sprintf( __( 'Activate the %s add-on.', 'wpsso' ), $ext_name_transl ) . '</a>';

			}

			$addons_url = $this->p->util->get_admin_url( 'addons#' . $ext );

			return '<a href="' . $addons_url . '">' . sprintf( __( 'Install and activate the %s add-on.', 'wpsso' ), $ext_name_transl ) . '</a>';
		}
	}
}
