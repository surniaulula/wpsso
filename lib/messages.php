<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoMessages' ) ) {

	class WpssoMessages {

		protected $p;	// Wpsso class object.

		protected $pkg_info   = array();
		protected $p_name     = '';
		protected $p_name_pro = '';
		protected $dist_pro   = '';
		protected $dist_std   = '';
		protected $fb_prefs   = '';

		private $info    = null;	// WpssoMessagesInfo class object.
		private $tooltip = null;	// WpssoMessagesTooltip class object.

		/**
		 * Instantiated by Wpsso->set_objects() when is_admin() is true.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->maybe_set_properties();
		}

		/**
		 * Define and translate certain strings only once. 
		 */
		protected function maybe_set_properties() {

			if ( empty( $this->pkg_info ) ) {

				$this->pkg_info   = $this->p->admin->get_pkg_info();	// Returns an array from cache.
				$this->p_name     = $this->pkg_info[ 'wpsso' ][ 'name' ];
				$this->p_name_pro = $this->pkg_info[ 'wpsso' ][ 'name_pro' ];
				$this->dist_pro   = _x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' );
				$this->dist_std   = _x( $this->p->cf[ 'dist' ][ 'std' ], 'distribution name', 'wpsso' );
				$this->fb_prefs   = __( 'Facebook prefers images of 1200x630px cropped (for Retina and high-PPI displays), 600x315px cropped as a recommended minimum, and ignores images smaller than 200x200px.', 'wpsso' );
			}
		}

		public function get( $msg_key = false, $info = array() ) {

			$msg_key = sanitize_title_with_dashes( $msg_key );

			/**
			 * Set a default text string, if one is provided.
			 */
			$text = '';

			if ( is_string( $info ) ) {

				$text = $info;

				$info = array( 'text' => $text );

			} elseif ( isset( $info[ 'text' ] ) ) {

				$text = $info[ 'text' ];
			}

			/**
			 * Set a lowercase acronym.
			 *
			 * Example plugin IDs: wpsso, wpssoum, etc.
			 */
			$info[ 'plugin_id' ] = $plugin_id = isset( $info[ 'plugin_id' ] ) ? $info[ 'plugin_id' ] : $this->p->id;

			/**
			 * Get the array of plugin URLs (download, purchase, etc.).
			 */
			$url = isset( $this->p->cf[ 'plugin' ][ $plugin_id ][ 'url' ] ) ? $this->p->cf[ 'plugin' ][ $plugin_id ][ 'url' ] : array();

			/**
			 * Make sure specific plugin information is available, like 'short', 'short_pro', etc.
			 */
			foreach ( array( 'short', 'name', 'version' ) as $info_key ) {

				if ( ! isset( $info[ $info_key ] ) ) {

					if ( ! isset( $this->p->cf[ 'plugin' ][ $plugin_id ][ $info_key ] ) ) {	// Just in case.

						$info[ $info_key ] = null;

						continue;
					}

					$info[ $info_key ] = $this->p->cf[ 'plugin' ][ $plugin_id ][ $info_key ];
				}

				if ( 'name' === $info_key ) {

					$info[ $info_key ] = _x( $info[ $info_key ], 'plugin name', 'wpsso' );
				}

				if ( 'version' !== $info_key ) {

					if ( ! isset( $info[ $info_key . '_pro' ] ) ) {

						$info[ $info_key . '_pro' ] = SucomUtil::get_dist_name( $info[ $info_key ], $this->dist_pro );
					}
				}
			}

			/**
			 * Tooltips.
			 */
			if ( 0 === strpos( $msg_key, 'tooltip-' ) ) {

				/**
				 * Instantiate WpssoMessagesTooltip only when needed.
				 */
				if ( null === $this->tooltip ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip.php';

					$this->tooltip = new WpssoMessagesTooltip( $this->p );
				}

				$text = $this->tooltip->get( $msg_key, $info );

			/**
			 * Informational messages.
			 */
			} elseif ( 0 === strpos( $msg_key, 'info-' ) ) {

				/**
				 * Instantiate WpssoMessagesInfo only when needed.
				 */
				if ( null === $this->info ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-info.php';

					$this->info = new WpssoMessagesInfo( $this->p );
				}

				$text = $this->info->get( $msg_key, $info );

			/**
			 * Misc pro messages
			 */
			} elseif ( 0 === strpos( $msg_key, 'pro-' ) ) {

				switch ( $msg_key ) {

					case 'pro-feature-msg':

						$text = '<p class="pro-feature-msg">';

						$text .= empty( $url[ 'purchase' ] ) ? '' : '<a href="' . $url[ 'purchase' ] . '">';

						if ( 'wpsso' === $plugin_id ) {

							$text .= sprintf( __( 'Purchase the %s plugin to upgrade and get the following features.', 'wpsso' ),
								$info[ 'short_pro' ] );

						} else {

							$text .= sprintf( __( 'Purchase the %s add-on to upgrade and get the following features.', 'wpsso' ),
								$info[ 'short_pro' ] );
						}

						$text .= empty( $url[ 'purchase' ] ) ? '' : '</a>';

						$text .= '</p>';

						break;

					case 'pro-ecom-product-msg':

						if ( empty( $this->p->avail[ 'ecom' ][ 'any' ] ) ) {	// Just in case.

							$text = '';

						} else {

							if ( ! empty( $this->pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

								if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {

									// translators: Please ignore - translation uses a different text domain.
									$wc_mb_name = '<strong>' . __( 'Product data', 'woocommerce' ) . '</strong>';

									$text = '<p class="pro-feature-msg">';

									$text .= sprintf( __( 'Disabled product information fields show values imported from the WooCommerce %s metabox.', 'wpsso' ), $wc_mb_name ) . '<br/>';

									$text .= sprintf( __( 'You can edit product information in the WooCommerce %s metabox to update these default values.', 'wpsso' ), $wc_mb_name );

									$text .= '</p>';

								} else {

									$text = '<p class="pro-feature-msg">';

									$text .= __( 'An e-commerce plugin is active &ndash; disabled product information fields show values imported from the e-commerce plugin.', 'wpsso' );

									$text .= '</p>';
								}

							} else {

								$text = '<p class="pro-feature-msg">';

								$text .= empty( $url[ 'purchase' ] ) ? '' : '<a href="' . $url[ 'purchase' ] . '">';

								$text .= sprintf( __( 'An e-commerce plugin is active &ndash; product information may be imported by the %s plugin.', 'wpsso' ), $this->p_name_pro );

								$text .= empty( $url[ 'purchase' ] ) ? '' : '</a>';

								$text .= '</p>';
							}
						}

						break;

					case 'pro-purchase-link':

						if ( empty( $info[ 'ext' ] ) ) {	// Nothing to do.

							break;
						}

						if ( $this->pkg_info[ $info[ 'ext' ] ][ 'pp' ] ) {

							$text = _x( 'Get More Licenses', 'plugin action link', 'wpsso' );

						} elseif ( $info[ 'ext' ] === $plugin_id ) {

							$text = sprintf( _x( 'Purchase %s Plugin', 'plugin action link', 'wpsso' ), $this->dist_pro );

						} else {

							$text = sprintf( _x( 'Purchase %s Add-on', 'plugin action link', 'wpsso' ), $this->dist_pro );
						}

						if ( ! empty( $info[ 'url' ] ) ) {

							$text = '<a href="' . $info[ 'url' ] . '"' . ( empty( $info[ 'tabindex' ] ) ? '' :
								' tabindex="' . $info[ 'tabindex' ] . '"' ) . '>' .  $text . '</a>';
						}

						break;

					default:

						$text = apply_filters( 'wpsso_messages_pro', $text, $msg_key, $info );

						break;
				}

			/**
			 * Misc notice messages
			 */
			} elseif ( 0 === strpos( $msg_key, 'notice-' ) ) {

				switch ( $msg_key ) {

					case 'notice-image-rejected':

						$mb_title     = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
						$media_tab    = _x( 'Priority Media', 'metabox tab', 'wpsso' );
						$is_meta_page = WpssoWpMeta::is_meta_page();

						$text = '<!-- show-once -->';

						$text .= ' <p>';

						$text .= __( 'Please note that a correctly sized image improves click through rates by presenting your content at its best on social sites and in search results.', 'wpsso' ) . ' ';

						if ( $is_meta_page ) {

							$text .= sprintf( __( 'A larger image can be uploaded and/or selected in the %1$s metabox under the %2$s tab.', 'wpsso' ), $mb_title, $media_tab );

						} else {

							$text .= __( 'Consider replacing the original image with a higher resolution version.', 'wpsso' ) . ' ';

							$text .= sprintf( __( 'See <a href="%s">Why shouldn\'t I upload small images to the media library?</a> for more information on WordPress image sizes.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/faqs/why-shouldnt-i-upload-small-images-to-the-media-library/' ). ' ';
						}

						$text .= '</p>';

						/**
						 * WpssoMedia->is_image_within_config_limits() sets 'show_adjust_img_opts' = false
						 * for images with an aspect ratio that exceeds the hard-coded config limits.
						 */
						if ( ! isset( $info[ 'show_adjust_img_opts' ] ) || ! empty( $info[ 'show_adjust_img_opts' ] ) ) {

							if ( current_user_can( 'manage_options' ) ) {

								$upscale_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Upscale Media Library Images', 'option label', 'wpsso' ) );

								$percent_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ) );

								$image_dim_option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Enforce Image Dimension Checks', 'option label', 'wpsso' ) );

								$image_sizes_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_image_sizes',
									_x( 'Image Sizes', 'lib file description', 'wpsso' ) );

								$text .= ' <p><strong>';

								$text .= __( 'Additional information shown only to users with Administrative privileges:', 'wpsso' );

								$text .= '</strong></p>';

								$text .= '<ul>';

								$text .= ' <li>' . __( 'Replace the original image with a higher resolution version.', 'wpsso' ) . '</li>';

								if ( $is_meta_page ) {

									$text .= ' <li>' . sprintf( __( 'Select a larger image under the %1$s &gt; %2$s tab.', 'wpsso' ), $mb_title, $media_tab ) . '</li>';
								}

								if ( empty( $this->p->options[ 'plugin_upscale_images' ] ) ) {

									$text .= ' <li>' . sprintf( __( 'Enable the %s option.', 'wpsso' ), $upscale_option_link ) . '</li>';

								} else {

									$text .= ' <li>' . sprintf( __( 'Increase the %s option value.', 'wpsso' ), $percent_option_link ) . '</li>';
								}

								/**
								 * Note that WpssoMedia->is_image_within_config_limits() sets
								 * 'show_adjust_img_size_opts' to false for images that are too
								 * small for the hard-coded config limits.
								 */
								if ( ! isset( $info[ 'show_adjust_img_size_opts' ] ) || ! empty( $info[ 'show_adjust_img_size_opts' ] ) ) {

									$text .= ' <li>' . sprintf( __( 'Update image size dimensions in the %s settings page.', 'wpsso' ), $image_sizes_tab_link ) . '</li>';

									if ( ! empty( $this->p->options[ 'plugin_check_img_dims' ] ) ) {

										$text .= ' <li>' . sprintf( __( 'Disable the %s option (not recommended).', 'wpsso' ), $image_dim_option_link ) . '</li>';
									}
								}

								$text .= '</ul>';
							}
						}

						$text .= '<!-- /show-once -->';

						break;

					case 'notice-missing-og-image':

						$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'An Open Graph image meta tag could not be generated from this webpage content or its custom %s metabox settings. Facebook <em>requires at least one image meta tag</em> to render shared content correctly.', 'wpsso' ), $mb_title );

						break;

					case 'notice-missing-og-description':

						$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'An Open Graph description meta tag could not be generated from this webpage content or its custom %s metabox settings. Facebook <em>requires a description meta tag</em> to render shared content correctly.', 'wpsso' ), $mb_title );

						break;

					case 'notice-missing-schema-image':

						$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

						$text = sprintf( __( 'A Schema "image" property could not be generated from this webpage content or its custom %s metabox settings. Google <em>requires at least one "image" property</em> for this Schema type.', 'wpsso' ), $mb_title );

						break;

					/**
					 * Notice shown when saving settings if the "Use Filtered Content" option is unchecked.
					 */
					case 'notice-content-filters-disabled':

						$option_text = _x( 'Use Filtered Content', 'option label', 'wpsso' );
						$option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', $option_text );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %s advanced option is currently disabled.', 'wpsso' ), $option_link ) . '</b> ';

						$text .= sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions and detect additional images and/or embedded videos provided by shortcodes.', 'wpsso' ), $this->p_name );

						$text .= '</p> <p>';

						$text .= '<b>' . __( 'Many themes and plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ) . '</b> ';

						$text .= __( 'If you use shortcodes in your content text, this option should be enabled - IF YOU EXPERIENCE WEBPAGE LAYOUT OR PERFORMANCE ISSUES AFTER ENABLING THIS OPTION, determine which theme or plugin is filtering the content incorrectly and report the problem to its author(s).', 'wpsso' );

						$text .= '</p>';

						if ( empty( $this->pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

							$text .= '<p>' . sprintf( __( 'Note that the %1$s option is an advanced %2$s feature.', 'wpsso' ), $option_link, $this->p_name_pro ) . '</p>';
						}

						break;

					/**
					 * Notice shown when saving settings if the "Enforce Image Dimension Checks" option is unchecked.
					 */
					case 'notice-check-img-dims-disabled':

						$option_text = _x( 'Enforce Image Dimension Checks', 'option label', 'wpsso' );
						$option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', $option_text );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %s advanced option is currently disabled.', 'wpsso' ), $option_link ) . '</b> ';

						$text .= __( 'Providing social and search sites with perfectly resized images is highly recommended, so this option should be enabled if possible.', 'wpsso' ) . ' ';

						$text .= __( 'Content authors often upload small featured images, without knowing that WordPress creates resized images based on predefined image sizes, so this option is disabled by default.', 'wpsso' ) . ' ';

						$text .= sprintf( __( 'See <a href="%s">Why shouldn\'t I upload small images to the media library?</a> for more information on WordPress image sizes.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/faqs/why-shouldnt-i-upload-small-images-to-the-media-library/' ). ' ';

						$text .= '</p>';

						if ( empty( $this->pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

							$text .= '<p>' . sprintf( __( 'Note that the %1$s option is an advanced %2$s feature.', 'wpsso' ), $option_link, $this->p_name_pro ) . '</p>';
						}

						break;

					case 'notice-ratings-reviews-wc-enabled':

						$option_text = _x( 'Ratings and Reviews Service', 'option label', 'wpsso' );
						$option_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_services-tab_ratings_reviews', $option_text );

						$wc_settings_page_url = get_admin_url( $blog_id = null, 'admin.php?page=wc-settings&tab=products' );

						$text = sprintf( __( 'WooCommerce product reviews are not compatible with the selected %s service API.', 'wpsso' ),
							_x( 'Stamped.io (Ratings and Reviews)', 'metabox title', 'wpsso' ) ) . ' ';

						$text .= sprintf( __( 'Please choose another %1$s or <a href="%2$s">disable the product reviews in WooCommerce</a>.',
							'wpsso' ), $option_link, $wc_settings_page_url ) . ' ';

						break;

					case 'notice-wp-config-php-variable-home':

						$const_html   = '<code>WP_HOME</code>';
						$cfg_php_html = '<code>wp-config.php</code>';

						$text = sprintf( __( 'The %1$s constant definition in your %2$s file contains a variable.', 'wpsso' ), $const_html, $cfg_php_html ) . ' ';

						$text .= sprintf( __( 'WordPress uses the %s constant to provide a single unique canonical URL for each webpage and Media Library content.', 'wpsso' ), $const_html ) . ' ';

						$text .= sprintf( __( 'A changing %s value will create different canonical URLs in your webpages, leading to duplicate content penalties from Google, incorrect social share counts, possible broken media links, mixed content issues, and SSL certificate errors.', 'wpsso' ), $const_html ) . ' ';

						$text .= sprintf( __( 'Please update your %1$s file and provide a fixed, non-variable value for the %2$s constant.', 'wpsso' ), $cfg_php_html, $const_html );

						break;

					case 'notice-pro-not-installed':

						$licenses_page_text = _x( 'Premium Licenses', 'lib file description', 'wpsso' );
						$licenses_page_link = $this->p->util->get_admin_url( 'licenses', $licenses_page_text );

						$text = sprintf( __( 'An Authentication ID for %1$s has been entered in the %2$s settings page, but the plugin has not been installed yet - you can install and activate the %3$s plugin from the %2$s settings page.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $licenses_page_link, $this->dist_pro ) . ' ;-)';

						break;

					case 'notice-pro-not-updated':

						$licenses_page_text = _x( 'Premium Licenses', 'lib file description', 'wpsso' );
						$licenses_page_link = $this->p->util->get_admin_url( 'licenses', $licenses_page_text );

						$text = sprintf( __( 'An Authentication ID for %1$s has been entered in the %2$s settings page, but the %3$s version has not been installed yet - don\'t forget to update the plugin to install the latest %3$s version.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $licenses_page_link, $this->dist_pro ) . ' ;-)';

						break;

					case 'notice-um-add-on-required':
					case 'notice-um-activate-add-on':

						$um_info      = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
						$um_info_name = _x( $um_info[ 'name' ], 'plugin name', 'wpsso' );

						$addons_page_text = _x( 'Complementary Add-ons', 'lib file description', 'wpsso' );
						$addons_page_link = $this->p->util->get_admin_url( 'addons#wpssoum', $addons_page_text );

						$licenses_page_text = _x( 'Premium Licenses', 'lib file description', 'wpsso' );
						$licenses_page_link = $this->p->util->get_admin_url( 'licenses', $licenses_page_text );

						$search_url = get_admin_url( $blog_id = null, 'plugins.php' );
						$search_url = add_query_arg( array( 's' => $um_info[ 'slug' ] ), $search_url );

						$text = '<p>';

						$text .= '<b>' . sprintf( __( 'An Authentication ID has been entered in the %1$s settings page, but the %2$s add-on is not active.', 'wpsso' ), $licenses_page_link, $um_info_name ) . '</b> ';

						$text .= '</p><p>';

						$text .= sprintf( __( 'The %1$s add-on is required to enable %2$s features and get %2$s updates.', 'wpsso' ), $um_info_name, $this->dist_pro ) . ' ';

						$text .= '</p><p>';

						if ( 'notice-um-add-on-required' === $msg_key ) {

							$text .= sprintf( __( 'You can install and activate the %1$s add-on from the %2$s settings page.', 'wpsso' ), $um_info_name, $addons_page_link ) . ' ';

						} else {

							$text .= sprintf( __( 'You can activate the %1$s add-on from <a href="%2$s">the WordPress Plugins page</a>.', 'wpsso' ), $um_info_name, $search_url ) . ' ';
						}

						$text .= '</p><p>';

						$text .= sprintf( __( 'Once the %1$s add-on is active, %2$s updates may be available for the %3$s plugin.', 'wpsso' ), $um_info_name, $this->dist_pro, $this->p_name_pro );

						$text .= '</p>';

						break;

					case 'notice-um-version-recommended':

						$um_info          = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
						$um_info_name     = _x( $um_info[ 'name' ], 'plugin name', 'wpsso' );
						$um_version       = isset( $um_info[ 'version' ] ) ? $um_info[ 'version' ] : 'unknown';
						$um_rec_version   = WpssoConfig::$cf[ 'um' ][ 'rec_version' ];
						$um_check_updates = _x( 'Check for Plugin Updates', 'submit button', 'wpsso' );

						$tools_page_text = _x( 'Tools and Actions', 'lib file description', 'wpsso' );
						$tools_page_link = $this->p->util->get_admin_url( 'tools', $tools_page_text );

						// translators: Please ignore - translation uses a different text domain.
						$wp_updates_page_text = __( 'Dashboard' ) . ' &gt; ' . __( 'Updates' );
						$wp_updates_page_link = '<a href="' . admin_url( 'update-core.php' ) . '">' . $wp_updates_page_text . '</a>';

						$text = sprintf( __( '%1$s version %2$s requires the use of %3$s version %4$s or newer (version %5$s is currently installed).', 'wpsso' ), $this->p_name_pro, $info[ 'version' ], $um_info_name, $um_rec_version, $um_version ) . ' ';

						// translators: %1$s is the WPSSO Update Manager add-on name.
						$text .= sprintf( __( 'If an update for the %1$s add-on is not available under the WordPress %2$s page, use the <em>%3$s</em> button in the %4$s settings page to force an immediate refresh of the plugin update information.', 'wpsso' ), $um_info_name, $wp_updates_page_link, $um_check_updates, $tools_page_link );

						break;

					case 'notice-recommend-version':

						$text = sprintf( __( 'You are using %1$s version %2$s - <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.', 'wpsso' ), $info[ 'app_label' ], $info[ 'app_version' ], $info[ 'version_url' ] ) . ' ';

						$text .= sprintf( __( 'If possible, please update to the latest %1$s stable release (or at least version %2$s).', 'wpsso' ), $info[ 'app_label' ], $info[ 'rec_version' ] );

						break;

					default:

						$text = apply_filters( 'wpsso_messages_notice', $text, $msg_key, $info );

						break;
				}

			/**
			 * Misc sidebox messages
			 */
			} elseif ( 0 === strpos( $msg_key, 'column-' ) ) {

				$mb_title = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );

				$li_support_text = __( 'Premium plugin support.', 'wpsso' );
				$li_support_link = empty( $info[ 'url' ][ 'support' ] ) ? '' :
					'<li><strong><a href="' . $info[ 'url' ][ 'support' ] . '">' . $li_support_text . '</a></strong></li>';

				switch ( $msg_key ) {

					case 'column-purchase-wpsso':

						$advanced_page_url = $this->p->util->get_admin_url( 'advanced' );

						$text = '<p><strong>' . sprintf( __( 'The %s plugin includes:', 'wpsso' ), $info[ 'name_pro' ] ) . '</strong></p>';

						$text .= '<ul>';

						$text .= $li_support_link;

						$text .= '<li>' . sprintf( __( '<strong><a href="%s">Customize advanced settings</a></strong>, including image sizes, cache expiry, video services, shortening services, document types, contact fields, product attributes, custom fields, and more.', 'wpsso' ), $advanced_page_url ) . '</li>';

						$text .= '<li>' . sprintf( __( '<strong>Additional Schema options</strong> in the %s metabox to customize creative works, events, how-tos, job postings, movies, products, recipes, reviews, and more.', 'wpsso' ), $mb_title ) . '</li>';

						$text .= '<li><strong>' . sprintf( __( 'Reads data from %d third-party plugins and service APIs.', 'wpsso' ), 45 ) . '</strong></li>';

						$text .= '</ul>';

						break;

					case 'column-help-support':

						$text = '<p>';

						$text .= sprintf( __( '<strong>Development of %s is driven by user requests</strong> - we welcome all your comments and suggestions.', 'wpsso' ), $info[ 'short' ] ) . ' ;-)';

						$text .= '</p>';

						break;

					case 'column-rate-review':

						$text = '<p><strong>';

						$text .= __( 'Great ratings are an excellent way to ensure the continued development of your favorite plugins.', 'wpsso' ) . ' ';

						$text .= '</strong></p><p>' . "\n";

						$text .= __( 'Without new ratings, plugins and add-ons that you and your site depend on could be discontinued prematurely.', 'wpsso' ) . ' ';

						$text .= '</p><p>';

						$text .= __( 'Don\'t let that happen!', 'wpsso' ) . ' ';

						$text .= __( 'Rate your active plugins today - it only takes a few seconds to rate a plugin!', 'wpsso' ) . ' ';

						$text .= convert_smilies( ';-)' );

						$text .= '</p>' . "\n";

						break;

					default:

						$text = apply_filters( 'wpsso_messages_column', $text, $msg_key, $info );

						break;
				}

			} else {

				$text = apply_filters( 'wpsso_messages', $text, $msg_key, $info );
			}

			if ( ! empty( $info[ 'is_locale' ] ) ) {

				// translators: %s is the wordpress.org URL for the WPSSO User Locale Selector add-on.
				$text .= ' ' . sprintf( __( 'This option is localized - <a href="%s">you may change the WordPress locale</a> to define alternate values for different languages.', 'wpsso' ), 'https://wordpress.org/plugins/wpsso-user-locale/' );
			}

			if ( 0 === strpos( $msg_key, 'tooltip-' ) && ! empty( $text ) ) {

				$text = '<span class="' . $this->p->cf[ 'form' ][ 'tooltip_class' ] . '" data-help="' . esc_attr( $text ) . '">' .
					'<span class="' . $this->p->cf[ 'form' ][ 'tooltip_class' ] . '-icon"></span></span>';
			}

			return $text;
		}

		/**
		 * If an add-on is not active, return a short message that this add-on is required.
		 *
		 * Used by WPSSO Core and several add-ons to get the WPSSO ORG and PLM add-on required message.
		 */
		public function maybe_ext_required( $ext ) {

			list( $ext, $p_ext ) = $this->ext_p_ext( $ext );

			if ( empty( $ext ) ) {							// Just in case.

				return '';

			} elseif ( 'wpsso' === $ext ) {						// The main plugin is not considered an add-on.

				return '';

			} elseif ( ! empty( $this->p->avail[ 'p_ext' ][ $p_ext ] ) ) {		// Add-on is already active.

				return '';

			} elseif ( empty( $this->p->cf[ 'plugin' ][ $ext ][ 'short' ] ) ) {	// Unknown add-on.

				return '';
			}

			// translators: %s is is the short add-on name.
			$text = sprintf( _x( '%s required', 'option comment', 'wpsso' ), $this->p->cf[ 'plugin' ][ $ext ][ 'short' ] );

			$text = $this->p->util->get_admin_url( 'addons#' . $ext, $text );

			return ' <span class="ext-req-msg">' . $text . '</span>';
		}

		/**
		 * Used by the General Settings page.
		 */
		public function maybe_preview_images_first() {

			$text = '';

			if ( ! empty( $this->form->options[ 'og_vid_prev_img' ] ) ) {

				$text .= ' ' . _x( 'note that video preview images are enabled (and included first)', 'option comment', 'wpsso' );
			}

			return $text;
		}

		/**
		 * Used by the Advanced Settings page for the "Webpage Document Title" option.
		 */
		public function maybe_title_tag_disabled() {

			if ( current_theme_supports( 'title-tag' ) ) {

				return '';
			}

			$text = sprintf( __( '<a href="%s">WordPress Title Tag</a> not supported by theme', 'wpsso' ),
				__( 'https://codex.wordpress.org/Title_Tag', 'wpsso' ) );

			return '<span class="option-warning">' . $text . '</span>';
		}

		/**
		 * Deprecated on 2021/09/10.
		 */
		public function preview_images_first() {

			_deprecated_function( __METHOD__ . '()', '2021/09/10', $replacement = __CLASS__ . '::preview_images_are_first()' );	// Deprecation message.

			return $this->preview_images_are_first();
		}

		public function preview_images_are_first() {

			$html = ' ' . _x( 'note that video preview images are included first', 'option comment', 'wpsso' );

			return $html;
		}

		public function pro_feature( $ext ) {

			list( $ext, $p_ext ) = $this->ext_p_ext( $ext );

			if ( ! empty( $ext ) ) {	// Just in case.

				return $this->get( 'pro-feature-msg', array( 'plugin_id' => $ext ) );
			}

			return '';
		}

		public function pro_feature_video_api() {

			$this->maybe_set_properties();

			$short_pro = $this->pkg_info[ $this->p->id ][ 'short_pro' ];

			$html = '<p class="pro-feature-msg">';

			$html .= sprintf( __( 'Video discovery and service API modules are provided with the %s version.', 'wpsso' ), $short_pro );

			$html .= '</p>';

			return $html;
		}

		/**
		 * Head cache disabled.
		 */
		public function head_cache_disabled() {

			$text = __( 'head cache is disabled (caching plugin or service detected).', 'wpsso' );

			return '<span class="option-info">' . $text . '</span>';
		}

		/**
		 * Pinterest disabled.
		 *
		 * $extra_css_class can be empty, 'left', or 'inline'.
		 */
		public function pin_img_disabled( $extra_css_class = '' ) {

			$option_text = _x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' );
			$option_link = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_pinterest', $option_text );

			// translators: %s is the option name, linked to its settings page.
			$text = sprintf( __( 'Modifications disabled (%s option is unchecked).', 'wpsso' ), $option_link );

			return '<p class="status-msg smaller disabled ' . $extra_css_class . '">' . $text . '</p>';
		}

		/**
		 * Robots disabled.
		 */
		public function robots_disabled() {

			$seo_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other' );

			$html = '<p class="status-msg"><a href="' . $seo_tab_url . '">' . __( 'Robots meta tag is disabled.', 'wpsso' ) . '</a></p>';

			$html .= '<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>';

			return $html;
		}

		public function get_robots_disabled_rows( $table_rows = array() ) {

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				$table_rows = array();
			}

			$table_rows[ 'robots_disabled' ] = '<tr><td align="center">' . $this->robots_disabled() . '</td></tr>';

			return $table_rows;
		}

		/**
		 * Schema disabled.
		 */
		public function schema_disabled() {

			$html = '<p class="status-msg">' . __( 'Schema markup is disabled.', 'wpsso' ) . '</p>';

			$html .= '<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>';

			return $html;
		}

		public function get_schema_disabled_rows( $table_rows = array(), $col_span = 1 ) {

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				$table_rows = array();
			}

			$table_rows[ 'schema_disabled' ] = '<tr><td align="center" colspan="' . $col_span . '">' . $this->schema_disabled() . '</td></tr>';

			return $table_rows;
		}

		/**
		 * SEO option disabled.
		 */
		public function seo_option_disabled( $mt_name ) {

			// translators: %s is the meta tag name (aka meta name canonical).
			$text = sprintf( __( 'Modifications disabled (<code>%s</code> tag disabled or SEO plugin detected).', 'wpsso' ), $mt_name );

			return '<p class="status-msg smaller disabled">' . $text . '</p>';
		}

		/**
		 * WordPress sitemaps disabled.
		 */
		public function wp_sitemaps_disabled() {

			$html = '<p class="status-msg">' . __( 'WordPress sitemaps are disabled.', 'wpsso' ) . '</p>';

			$html .= '<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>';

			return $html;
		}

		public function get_wp_sitemaps_disabled_rows( $table_rows = array() ) {

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				$table_rows = array();
			}

			$table_rows[ 'wp_sitemaps_disabled' ] = '<tr><td align="center">' . $this->wp_sitemaps_disabled() . '</td></tr>';

			return $table_rows;
		}

		protected function maybe_html_tag_disabled_text( array $parts ) {

			$text = '';

			if ( empty( $parts[ 2 ] ) ) {	// Check for an incomplete HTML tag parts array.

				return $text;
			}

			$opt_key = strtolower( 'add_' . implode( '_', $parts ) );	// Use same concatenation technique as WpssoHead->add_mt_singles().

			$html_tag = implode( ' ', $parts );	// HTML tag string for display.

			$is_disabled = empty( $this->p->options[ $opt_key ] ) ? true : false;

			if ( $is_disabled ) {

				$seo_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other',
					_x( 'SSO', 'menu title', 'wpsso' ) . ' &gt; ' .
					_x( 'Advanced Settings', 'lib file description', 'wpsso' ) . ' &gt; ' .
					_x( 'HTML Tags', 'metabox title', 'wpsso' ) . ' &gt; ' .
					_x( 'SEO / Other', 'metabox tab', 'wpsso' ) );

				$text .= ' ' . sprintf( __( 'Note that the <code>%s</code> HTML tag is currently disabled.', 'wpsso' ), $html_tag ) . ' ';

				$text .= sprintf( __( 'You can re-enable this option under the %s tab.', 'wpsso' ), $seo_tab_link );
			}

			return $text;
		}

		/**
		 * Returns an array of two elements - the custom field option label and a tooltip fragment.
		 */
		protected function get_cf_tooltip_fragments( $msg_key = false ) {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'addl_type_urls' => array(
						'label' => _x( 'Microdata Type URLs', 'option label', 'wpsso' ),
						'desc'  => _x( 'additional microdata type URLs', 'tooltip fragment', 'wpsso' ),
					),
					'book_isbn' => array(
						'label' => _x( 'Book ISBN', 'option label', 'wpsso' ),
						'desc'  => _x( 'an ISBN code (aka International Standard Book Number)', 'tooltip fragment', 'wpsso' ),
					),
					'howto_steps' => array(
						'label' => _x( 'How-To Steps', 'option label', 'wpsso' ),
						'desc'  => _x( 'how-to steps', 'tooltip fragment', 'wpsso' ),
					),
					'howto_supplies' => array(
						'label' => _x( 'How-To Supplies', 'option label', 'wpsso' ),
						'desc'  => _x( 'how-to supplies', 'tooltip fragment', 'wpsso' ),
					),
					'howto_tools' => array(
						'label' => _x( 'How-To Tools', 'option label', 'wpsso' ),
						'desc'  => _x( 'how-to tools', 'tooltip fragment', 'wpsso' ),
					),
					'img_url' => array(
						'label' => _x( 'Image URL', 'option label', 'wpsso' ),
						'desc'  => _x( 'an image URL', 'tooltip fragment', 'wpsso' ),
					),
					'product_avail' => array(
						'label' => _x( 'Product Availability', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product availability', 'tooltip fragment', 'wpsso' ),
					),
					'product_brand' => array(
						'label' => _x( 'Product Brand', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product brand', 'tooltip fragment', 'wpsso' ),
					),
					'product_category' => array(
						'label' => _x( 'Product Type', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a <a href="%s">Google product type</a>', 'tooltip fragment', 'wpsso' ),
							__( 'https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt', 'wpsso' ) ),
					),
					'product_color' => array(
						'label' => _x( 'Product Color', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product color', 'tooltip fragment', 'wpsso' ),
					),
					'product_condition' => array(
						'label' => _x( 'Product Condition', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product condition', 'tooltip fragment', 'wpsso' ),
					),
					'product_currency' => array(
						'label' => _x( 'Product Currency', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product currency', 'tooltip fragment', 'wpsso' ),
					),
					'product_depth_value' => array(
						'label' => _x( 'Product Depth', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product depth (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'depth' ) ),
					),
					'product_gtin14' => array(
						'label' => _x( 'Product GTIN-14', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-14 code (aka ITF-14)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin13' => array(
						'label' => _x( 'Product GTIN-13 (EAN)', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-13 code (aka 13-digit ISBN codes or EAN/UCC-13)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin12' => array(
						'label' => _x( 'Product GTIN-12 (UPC)', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-12 code (12-digit GS1 identification key composed of a UPC company prefix, item reference, and check digit)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin8' => array(
						'label' => _x( 'Product GTIN-8', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-8 code (aka EAN/UCC-8 or 8-digit EAN)', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin' => array(
						'label' => _x( 'Product GTIN', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product GTIN code (GTIN-8, GTIN-12/UPC, GTIN-13/EAN, or GTIN-14)', 'tooltip fragment', 'wpsso' ),
					),
					'product_height_value' => array(
						'label' => _x( 'Product Height', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product height (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'height' ) ),
					),
					'product_isbn' => array(
						'label' => _x( 'Product ISBN', 'option label', 'wpsso' ),
						'desc'  => _x( 'an ISBN code (aka International Standard Book Number)', 'tooltip fragment', 'wpsso' ),
					),
					'product_length_value' => array(
						'label' => _x( 'Product Length', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product length (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'length' ) ),
					),
					'product_material' => array(
						'label' => _x( 'Product Material', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product material', 'tooltip fragment', 'wpsso' ),
					),
					'product_mfr_part_no' => array(
						'label' => _x( 'Product MPN', 'option label', 'wpsso' ),
						'desc'  => _x( 'a Manufacturer Part Number (MPN)', 'tooltip fragment', 'wpsso' ),
					),
					'product_price' => array(
						'label' => _x( 'Product Price', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product price', 'tooltip fragment', 'wpsso' ),
					),
					'product_retailer_part_no' => array(
						'label' => _x( 'Product SKU', 'option label', 'wpsso' ),
						'desc'  => _x( 'a Stock-Keeping Unit (SKU)', 'tooltip fragment', 'wpsso' ),
					),
					'product_size' => array(
						'label' => _x( 'Product Size', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product size', 'tooltip fragment', 'wpsso' ),
					),
					'product_target_gender' => array(
						'label' => _x( 'Product Target Gender', 'option label', 'wpsso' ),
						'desc'  => _x( 'a product target gender', 'tooltip fragment', 'wpsso' ),
					),
					'product_fluid_volume_value' => array(
						'label' => _x( 'Product Fluid Volume', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product fluid volume (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'fluid_volume' ) ),
					),
					'product_weight_value' => array(
						'label' => _x( 'Product Weight', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product weight (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'weight' ) ),
					),
					'product_width_value' => array(
						'label' => _x( 'Product Width', 'option label', 'wpsso' ),
						'desc'  => sprintf( _x( 'a product width (in %s)', 'tooltip fragment', 'wpsso' ),
							WpssoSchema::get_data_unit_text( 'width' ) ),
					),
					'recipe_ingredients' => array(
						'label' => _x( 'Recipe Ingredients', 'option label', 'wpsso' ),
						'desc'  => _x( 'recipe ingredients', 'tooltip fragment', 'wpsso' ),
					),
					'recipe_instructions' => array(
						'label' => _x( 'Recipe Instructions', 'option label', 'wpsso' ),
						'desc'  => _x( 'recipe instructions', 'tooltip fragment', 'wpsso' ),
					),
					'sameas_urls' => array(
						'label' => _x( 'Same-As URLs', 'option label', 'wpsso' ),
						'desc'  => _x( 'additional Same-As URLs', 'tooltip fragment', 'wpsso' ),
					),
					'vid_embed' => array(
						'label' => _x( 'Video Embed HTML', 'option label', 'wpsso' ),
						'desc'  => _x( 'video embed HTML code (not a URL)', 'tooltip fragment', 'wpsso' ),
					),
					'vid_url' => array(
						'label' => _x( 'Video URL', 'option label', 'wpsso' ),
						'desc'  => _x( 'a video URL (not HTML code)', 'tooltip fragment', 'wpsso' ),
					),
				);
			}

			if ( false !== $local_cache ) {

				if ( isset( $local_cache[ $msg_key ] ) ) {

					return $local_cache[ $msg_key ];
				}

				return null;
			}

			return $local_cache;
		}

		protected function get_def_checked( $opt_key ) {

			$def_checked = $this->p->opt->get_defaults( $opt_key ) ?
				_x( 'checked', 'option value', 'wpsso' ) :
				_x( 'unchecked', 'option value', 'wpsso' );

			return $def_checked;
		}

		protected function get_def_img_dims( $opt_pre ) {

			$def_opts    = $this->p->opt->get_defaults();
			$img_width   = empty( $def_opts[ $opt_pre . '_img_width' ] ) ? 0 : $def_opts[ $opt_pre . '_img_width' ];
			$img_height  = empty( $def_opts[ $opt_pre . '_img_height' ] ) ? 0 : $def_opts[ $opt_pre . '_img_height' ];
			$img_cropped = empty( $def_opts[ $opt_pre . '_img_crop' ] ) ? _x( 'uncropped', 'option value', 'wpsso' ) : _x( 'cropped', 'option value', 'wpsso' );

			return $img_width . 'x' . $img_height . 'px ' . $img_cropped;
		}

		/**
		 * Returns an array of two elements.
		 */
		protected function ext_p_ext( $ext ) {

			if ( is_string( $ext ) ) {

				if ( 0 !== strpos( $ext, $this->p->id ) ) {

					$ext = $this->p->id . $ext;
				}

				$p_ext = substr( $ext, strlen( $this->p->id ) );

			} else {

				$ext = '';

				$p_ext = '';
			}

			return array( $ext, $p_ext );
		}
	}
}
