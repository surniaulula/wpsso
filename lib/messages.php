<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
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

		protected $pkg_info        = array();
		protected $p_name          = '';
		protected $p_name_pro      = '';
		protected $pkg_pro_transl  = '';
		protected $pkg_std_transl  = '';
		protected $fb_prefs_transl = '';

		private $info    = null;	// WpssoMessagesInfo class object.
		private $tooltip = null;	// WpssoMessagesTooltip class object.

		/*
		 * Instantiated by Wpsso->set_objects() when is_admin() is true.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function get( $get_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$msg_key = sanitize_title_with_dashes( $get_key );

			/*
			 * Set a default text string, if one is provided.
			 */
			$text = '';

			if ( is_string( $info ) ) {

				$text = $info;

				$info = array( 'text' => $text );

			} elseif ( isset( $info[ 'text' ] ) ) {

				$text = $info[ 'text' ];
			}

			/*
			 * Set a lowercase acronym.
			 *
			 * Example plugin IDs: wpsso, wpssoum, etc.
			 */
			$plugin_id = $info[ 'plugin_id' ] = isset( $info[ 'plugin_id' ] ) ? $info[ 'plugin_id' ] : $this->p->id;

			/*
			 * Get the array of plugin URLs (download, purchase, etc.).
			 */
			$url = isset( $this->p->cf[ 'plugin' ][ $plugin_id ][ 'url' ] ) ? $this->p->cf[ 'plugin' ][ $plugin_id ][ 'url' ] : array();

			/*
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

						$info[ $info_key . '_pro' ] = $this->p->util->get_pkg_name( $info[ $info_key ], $this->pkg_pro_transl );
					}
				}
			}

			/*
			 * Tooltips.
			 */
			if ( 0 === strpos( $msg_key, 'tooltip-' ) ) {

				/*
				 * Instantiate WpssoMessagesTooltip when needed.
				 */
				if ( null === $this->tooltip ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-tooltip.php';

					$this->tooltip = new WpssoMessagesTooltip( $this->p );
				}

				$text = $this->tooltip->get( $msg_key, $info );

			/*
			 * Informational messages.
			 */
			} elseif ( 0 === strpos( $msg_key, 'info-' ) ) {

				/*
				 * Instantiate WpssoMessagesInfo when needed.
				 */
				if ( null === $this->info ) {

					require_once WPSSO_PLUGINDIR . 'lib/messages-info.php';

					$this->info = new WpssoMessagesInfo( $this->p );
				}

				$text = $this->info->get( $msg_key, $info );

			/*
			 * Misc pro messages
			 */
			} elseif ( 0 === strpos( $msg_key, 'pro-' ) ) {

				switch ( $msg_key ) {

					case 'pro-feature-msg':

						$text = '<p class="pro-feature-msg">';

						$text .= empty( $url[ 'purchase' ] ) ? '' : '<a href="' . $url[ 'purchase' ] . '">';

						$text .= sprintf( __( 'Upgrade to the %s edition and get the following features.', 'wpsso' ), $info[ 'short_pro' ] );

						$text .= empty( $url[ 'purchase' ] ) ? '' : '</a>';

						$text .= '</p>';

						break;

					case 'pro-ecom-product-msg':

						if ( empty( $this->p->avail[ 'ecom' ][ 'any' ] ) ) {	// No e-commerce plugin active.

							$text = '';

						} elseif ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {	// WooCommerce plugin is active.

							if ( 'product' === $info[ 'mod' ][ 'post_type' ] ) {	// WooCommerce product editing page.

								// translators: Please ignore - translation uses a different text domain.
								$wc_mb_name = '<strong>' . __( 'Product data', 'woocommerce' ) . '</strong>';

								$text = '<p class="pro-feature-msg">';

								$text .= sprintf( __( 'Read-only product options show values imported from the WooCommerce %s metabox for the main product.', 'wpsso' ), $wc_mb_name ) . ' ';

								$text .= sprintf( __( 'You can edit product information in the WooCommerce %s metabox to update these values.', 'wpsso' ), $wc_mb_name ) . ' ';

								if ( $this->p->util->wc->is_mod_variable( $info[ 'mod' ] ) ) {

									$text .= __( 'This is a variable product - information from product variations may supersede these values in Schema product offers.', 'wpsso' ) . ' ';
								}

								$text .= '</p>';
							}

						} else {	// Another e-commerce plugin is active.

							$text = '<p class="pro-feature-msg">';

							$text .= __( 'An e-commerce plugin is active &ndash; read-only product information fields may show values imported from the e-commerce plugin.', 'wpsso' );

							$text .= '</p>';
						}

						break;

					case 'pro-purchase-link':

						if ( empty( $info[ 'ext' ] ) ) {	// Nothing to do.

							break;
						}

						if ( $this->pkg_info[ $info[ 'ext' ] ][ 'pp' ] ) {

							$text = _x( 'Get More Licenses', 'plugin action link', 'wpsso' );

						} elseif ( $info[ 'ext' ] === $plugin_id ) {

							$text = sprintf( _x( 'Purchase %s Plugin', 'plugin action link', 'wpsso' ), $this->pkg_pro_transl );

						} else {

							$text = sprintf( _x( 'Purchase %s Add-on', 'plugin action link', 'wpsso' ), $this->pkg_pro_transl );
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

			/*
			 * Misc notice messages.
			 */
			} elseif ( 0 === strpos( $msg_key, 'notice-' ) ) {

				switch ( $msg_key ) {

					case 'notice-check-img-dims-disabled':

						$opt_label = _x( 'Image Dimension Checks', 'option label', 'wpsso' );
						$opt_link  = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', $opt_label );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %s advanced option is currently disabled.', 'wpsso' ), $opt_link ) . '</b> ';

						$text = __( 'Users may upload small images to the Media Library without knowing that WordPress creates (or tries to create) several different image sizes from the uploaded originals.', 'wpsso' ) . ' ';

						$text .= __( 'Uploading small images to the Media Library means that WordPress cannot create image sizes that are larger than the uploaded image, and <strong>WordPress will provide images that are too small for larger image sizes</strong>.', 'wpsso' ) . ' ';

						$text .= __( 'Providing social sites and search engines with correctly sized images is highly recommended, so this option should be enabled if possible, to double-check the dimension of images provided by WordPress.', 'wpsso' ) . ' ';

						$text .= '</p>';

						break;

					case 'notice-content-filters-disabled':

						$opt_label = _x( 'Use Filtered Content', 'option label', 'wpsso' );
						$opt_link  = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', $opt_label );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %s advanced option is currently disabled.', 'wpsso' ), $opt_link ) . '</b> ';

						$text .= sprintf( __( 'The use of WordPress content filters allows %s to fully render your content text for meta tag descriptions and detect additional images and/or embedded videos provided by shortcodes.', 'wpsso' ), $this->p_name );

						$text .= '</p> <p>';

						$text .= '<b>' . __( 'Many themes and plugins have badly coded content filters, so this option is disabled by default.', 'wpsso' ) . '</b> ';

						$text .= __( 'If you use shortcodes in your content text, this option should be enabled - IF YOU EXPERIENCE WEBPAGE LAYOUT OR PERFORMANCE ISSUES AFTER ENABLING THIS OPTION, disable the option or determine which theme or plugin is filtering the content incorrectly and report the problem to its author(s).', 'wpsso' );

						$text .= '</p>';

						break;

					case 'notice-image-rejected':

						$mb_title     = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
						$media_tab    = _x( 'Edit Media', 'metabox tab', 'wpsso' );
						$is_meta_page = WpssoAbstractWpMeta::is_meta_page();

						$text = '<!-- show-once -->';

						$text .= ' <p>';

						$text .= __( 'Note that correct image sizes are required for several markup standards, including Google Rich Results.', 'wpsso' ) . ' ';

						$text .= __( 'Correct image sizes also improve click through rates by presenting your content at its best on social sites and in search results.', 'wpsso' ) . ' ';

						$text .= __( 'Consider replacing the original image with a higher resolution version.', 'wpsso' ) . ' ';

						if ( $is_meta_page ) {

							$text .= sprintf( __( 'A larger image can also be uploaded and/or selected in the %1$s metabox under the %2$s tab.', 'wpsso' ), $mb_title, $media_tab ) . ' ';
						}

						$text .= sprintf( __( 'See <a href="%s">Why shouldn\'t I upload small images to the media library?</a> for more information on how WordPress uses high resolution images and automatically creates smaller images from them.', 'wpsso' ), 'https://wpsso.com/docs/plugins/wpsso/faqs/why-shouldnt-i-upload-small-images-to-the-media-library/' ). ' ';

						$text .= '</p>';

						/*
						 * WpssoMedia->is_image_within_config_limits() sets 'show_adjust_img_opts' = false
						 * for images with an aspect ratio that exceeds the hard-coded config limits.
						 */
						if ( ! isset( $info[ 'show_adjust_img_opts' ] ) || ! empty( $info[ 'show_adjust_img_opts' ] ) ) {

							if ( current_user_can( 'manage_options' ) ) {

								$image_dim_opt_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Image Dimension Checks', 'option label', 'wpsso' ) );

								$attached_img_opt_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Consider Attached Images', 'option label', 'wpsso' ) );

								$content_img_opt_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Consider Content Images', 'option label', 'wpsso' ) );

								$upscale_opt_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Upscale Media Library Images', 'option label', 'wpsso' ) );

								$percent_opt_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration',
									_x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ) );

								$image_sizes_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_image_sizes',
									_x( 'Image Sizes', 'lib file description', 'wpsso' ) );

								$text .= ' <p><strong>';

								$text .= __( 'Actions available to resolve this issue, in order of preference:', 'wpsso' );

								$text .= '</strong></p>';

								$text .= '<ul>';

								$text .= ' <li>' . __( 'Replace the uploaded full size image with a higher resolution version.',
									'wpsso' ) . '</li>';

								if ( $is_meta_page ) {

									$text .= ' <li>' . sprintf( __( 'Select a higher resolution image under the %1$s &gt; %2$s tab.',
										'wpsso' ), $mb_title, $media_tab ) . '</li>';
								}

								if ( empty( $this->p->options[ 'plugin_attached_images' ] ) ) {
									
									$text .= ' <li>' . sprintf( __( 'Enable the %s option to use attached images (recommended).',
										'wpsso' ), $attached_img_opt_link ) . '</li>';
								}

								if ( empty( $this->p->options[ 'plugin_content_images' ] ) ) {
									
									$text .= ' <li>' . sprintf( __( 'Enable the %s option to search the content text for more images.',
										'wpsso' ), $content_img_opt_link ) . '</li>';

								} else $text .= ' <li>' . sprintf( __( 'Disable the %s option if uploaded content images are too small.',
										'wpsso' ), $content_img_opt_link ) . '</li>';

								if ( empty( $this->p->options[ 'plugin_upscale_images' ] ) ) {

									$text .= ' <li>' . sprintf( __( 'Enable the %s option.', 'wpsso' ), $upscale_opt_link ) . '</li>';

								} else $text .= ' <li>' . sprintf( __( 'Increase the %s option value.', 'wpsso' ), $percent_opt_link ) . '</li>';

								/*
								 * Note that WpssoMedia->is_image_within_config_limits() sets
								 * 'show_adjust_img_size_opts' to false for images that are too
								 * small for the hard-coded limits. It would be pointless to update
								 * the image size dimensions or disable the image dimension checks
								 * in this case.
								 */
								if ( ! isset( $info[ 'show_adjust_img_size_opts' ] ) || ! empty( $info[ 'show_adjust_img_size_opts' ] ) ) {

									$text .= ' <li>' . sprintf( __( 'Reduce the image size dimensions in the %s settings page.',
										'wpsso' ), $image_sizes_tab_link ) . '</li>';

									if ( ! empty( $this->p->options[ 'plugin_check_img_dims' ] ) ) {

										$text .= ' <li>' . sprintf( __( 'Disable the %s option (not recommended).',
											'wpsso' ), $image_dim_opt_link ) . '</li>';
									}
								}

								$text .= '</ul>';
							}
						}

						$text .= '<!-- /show-once -->';

						break;

					case 'notice-missing-og-description':
					case 'notice-missing-og-image':

						$mb_title  = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
						$prop_name = str_replace( 'notice-missing-og-', '', $get_key );	// Use $get_key for mixed case.

						$text = sprintf( __( 'An Open Graph %1$s meta tag could not be generated from this content or its custom %2$s metabox options.',
							'wpsso' ), $prop_name, $mb_title ) . ' ';

						$text .= sprintf( __( 'Facebook requires at least one %1$s meta tag to render shared content correctly.',
							'wpsso' ), $prop_name );

						break;

					case 'notice-missing-schema-image':
					case 'notice-missing-schema-itemlistelement':

						$mb_title  = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
						$prop_name = str_replace( 'notice-missing-schema-', '', $get_key );	// Use $get_key for mixed case.

						$text = sprintf( __( 'A Schema %1$s property could not be generated from this content or its custom %2$s metabox options.',
							'wpsso' ), $prop_name, $mb_title ) . ' ';

						if ( empty( $info[ 'type_name' ] ) ) {

							$text .= sprintf( __( 'Google requires at least one %1$s property for this Schema type.',
								'wpsso' ), $prop_name ) . ' ';

						} else {

							$text .= sprintf( __( 'Google requires at least one %1$s property for the Schema %2$s type.',
								'wpsso' ), $prop_name, $info[ 'type_name' ] ) . ' ';
						}

						break;

					case 'notice-pro-not-installed':

						$licenses_page_text = _x( 'Premium Licenses', 'lib file description', 'wpsso' );
						$licenses_page_link = $this->p->util->get_admin_url( 'licenses', $licenses_page_text );

						$text = sprintf( __( 'An Authentication ID for %1$s has been entered in the %2$s settings page but the plugin has not been installed yet.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $licenses_page_link, $this->pkg_pro_transl ) . ' ';

						$text .= sprintf( __( 'You can install and activate the %3$s plugin from the %2$s settings page.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $licenses_page_link, $this->pkg_pro_transl ) . ' ';

						break;

					case 'notice-pro-not-updated':

						$licenses_page_text = _x( 'Premium Licenses', 'lib file description', 'wpsso' );
						$licenses_page_link = $this->p->util->get_admin_url( 'licenses', $licenses_page_text );

						$text = sprintf( __( 'An Authentication ID for %1$s has been entered in the %2$s settings page but the %3$s version has not been installed yet.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $licenses_page_link, $this->pkg_pro_transl ) . ' ';

						$text .= sprintf( __( 'Don\'t forget to update the plugin to install the latest %3$s version.', 'wpsso' ), '<b>' . $info[ 'name' ] . '</b>', $licenses_page_link, $this->pkg_pro_transl ) . ' ';

						break;

					/*
					 * Example $info = array(
					 *	'svc_title_transl' => _x( 'Shopper Approved (Ratings and Reviews)', 'metabox title', 'wpsso' ),
					 * );
					 */
					case 'notice-ratings-reviews-wc-enabled':

						$opt_label        = _x( 'Ratings and Reviews Service', 'option label', 'wpsso' );
						$opt_link         = $this->p->util->get_admin_url( 'advanced#sucom-tabset_services-tab_ratings_reviews', $opt_label );
						$wc_settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=wc-settings&tab=products' );
						$svc_label_transl = empty( $info[ 'svc_title_transl' ] ) ?
							_x( 'ratings and reviews', 'tooltip fragment', 'wpsso' ) : $info[ 'svc_title_transl' ];

						$text = sprintf( __( 'WooCommerce product reviews are not compatible with the selected %s service API.', 'wpsso' ),
							$svc_label_transl ) . ' ';

						$text .= sprintf( __( 'Please choose another %1$s or <a href="%2$s">disable product reviews in WooCommerce</a>.',
							'wpsso' ), $opt_link, $wc_settings_url ) . ' ';

						break;

					case 'notice-recommend-version':

						$text = sprintf( __( 'You are using %1$s version %2$s - <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.', 'wpsso' ), $info[ 'app_label' ], $info[ 'app_version' ], $info[ 'version_url' ] ) . ' ';

						$text .= sprintf( __( 'Please update to the latest %1$s version (or at least version %2$s).', 'wpsso' ), $info[ 'app_label' ], $info[ 'rec_version' ] ) . ' ';

						break;

					case 'notice-um-activate-add-on':
					case 'notice-um-add-on-required':

						$um_info      = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
						$um_info_name = _x( $um_info[ 'name' ], 'plugin name', 'wpsso' );

						$addons_page_text = _x( 'Plugin Add-ons', 'lib file description', 'wpsso' );
						$addons_page_link = $this->p->util->get_admin_url( 'addons#wpssoum', $addons_page_text );

						$licenses_page_text = _x( 'Premium Licenses', 'lib file description', 'wpsso' );
						$licenses_page_link = $this->p->util->get_admin_url( 'licenses', $licenses_page_text );

						$search_url = get_admin_url( $blog_id = null, 'plugins.php' );
						$search_url = add_query_arg( array( 's' => $um_info[ 'slug' ] ), $search_url );

						$text .= '<b>' . sprintf( __( 'An Authentication ID has been entered in the %1$s settings page, but the %2$s add-on is not active.', 'wpsso' ), $licenses_page_link, $um_info_name ) . '</b> ';

						$text .= sprintf( __( 'The %1$s add-on is required to enable %2$s features and get %2$s updates.', 'wpsso' ), $um_info_name, $this->pkg_pro_transl ) . ' ';

						if ( 'notice-um-add-on-required' === $msg_key ) {

							$text .= sprintf( __( 'You can install and activate the %1$s add-on from the %2$s settings page.', 'wpsso' ), $um_info_name, $addons_page_link ) . ' ';

						} else {

							$text .= sprintf( __( 'You can activate the %1$s add-on from <a href="%2$s">the WordPress Plugins page</a>.', 'wpsso' ), $um_info_name, $search_url ) . ' ';
						}

						$text .= sprintf( __( 'Once the %1$s add-on is active, %2$s updates may be available for the %3$s plugin.', 'wpsso' ), $um_info_name, $this->pkg_pro_transl, $this->p_name_pro ) . ' ';

						break;

					case 'notice-um-update-required':
					case 'notice-um-update-recommended':

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
						$text .= sprintf( __( 'If an update for the %1$s add-on is not available under the WordPress %2$s page, use the <em>%3$s</em> button in the %4$s settings page to force an immediate refresh of the plugin update information.', 'wpsso' ), $um_info_name, $wp_updates_page_link, $um_check_updates, $tools_page_link ) . ' ';

						break;

					case 'notice-wc-attributes-available':

						$attr_md_index = WpssoConfig::get_attr_md_index();
						$suggest_names = array();

						foreach ( $attr_md_index as $opt_attr_key => $md_key ) {

							if ( empty( $md_key ) ) continue;

							$attr_name_transl = SucomUtilOptions::get_key_value( $opt_attr_key, $this->p->options );	// Translated name.

							if ( empty( $attr_name_transl ) ) continue;

							$suggest_names[] = $attr_name_transl;
						}

						if ( ! empty( $suggest_names ) ) {	// Just in case.

							$suggest_list  = implode( $glue = ', ', $suggest_names );
							$attr_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_metadata-tab_product_attrs',
								_x( 'SSO', 'menu title', 'wpsso' ) . ' &gt; ' .
								_x( 'Advanced Settings', 'lib file description', 'wpsso' ) . ' &gt; ' .
								_x( 'Attributes and Metadata', 'metabox title', 'wpsso' ) . ' &gt; ' .
								_x( 'Product Attributes', 'metabox tab', 'wpsso' ) );

							$text .= sprintf( __( '%1$s can read additional WooCommerce product attributes: %2$s.',
								'wpsso' ), $this->p_name, '<strong>' . $suggest_list . '</strong>' ) . ' ';

							$text .= sprintf( __( 'You can view and modify the complete list of supported product attributes under the %s tab.',
								'wpsso' ), $attr_tab_link );
						}

						break;

					case 'notice-wc-inherit-featured-disabled':

						$opt_label = _x( 'Inherit Featured Image', 'option label', 'wpsso' );
						$opt_link  = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', $opt_label );

						$text = '<p class="top">';

						$text .= '<b>' . sprintf( __( 'The %s advanced option is currently disabled.', 'wpsso' ), $opt_link ) . '</b> ';

						$text .= __( 'WooCommerce product variations are children of their product page.', 'wpsso' ) . ' ';

						$text .= __( 'Unless you have an image selected for each product variation, we recommend enabling this option.', 'wpsso' ) . ' ';

						$text .= '</p>';

						break;

					case 'notice-wp-config-php-variable-home':

						$const_html   = '<code>WP_HOME</code>';
						$cfg_php_html = '<code>wp-config.php</code>';

						$text = sprintf( __( 'The %1$s constant definition in your %2$s file contains a variable.', 'wpsso' ), $const_html, $cfg_php_html ) . ' ';

						$text .= sprintf( __( 'WordPress uses the %s constant to provide a single unique canonical URL for each webpage and Media Library content.', 'wpsso' ), $const_html ) . ' ';

						$text .= sprintf( __( 'A changing %s value will create different canonical URLs in your webpages, leading to duplicate content penalties from Google, incorrect social share counts, possible broken media links, mixed content issues, and SSL certificate errors.', 'wpsso' ), $const_html ) . ' ';

						$text .= sprintf( __( 'Please update your %1$s file and provide a fixed, non-variable value for the %2$s constant.', 'wpsso' ), $cfg_php_html, $const_html );

						break;

					default:

						$text = apply_filters( 'wpsso_messages_notice', $text, $msg_key, $info );

						break;
				}

			/*
			 * Misc sidebox messages
			 */
			} elseif ( 0 === strpos( $msg_key, 'column-' ) ) {

				$mb_title        = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
				$li_support_text = __( 'Premium plugin support.', 'wpsso' );
				$li_support_link = empty( $info[ 'url' ][ 'support' ] ) ? '' :
					'<li><strong><a href="' . $info[ 'url' ][ 'support' ] . '">' . $li_support_text . '</a></strong></li>';

				switch ( $msg_key ) {

					case 'column-purchase-wpsso':

						$advanced_page_url = $this->p->util->get_admin_url( 'advanced' );

						$text = '<p><strong>' . sprintf( __( 'The %s plugin includes:', 'wpsso' ), $info[ 'name_pro' ] ) . '</strong></p>';

						$text .= '<ul>';

						$text .= $li_support_link;

						$text .= '<li>' . sprintf( __( '<strong><a href="%s">Customize advanced settings</a></strong>, including image sizes, video services, shortening services, default types, contact fields, product attributes, custom fields, and more.', 'wpsso' ), $advanced_page_url ) . '</li>';

						$text .= '<li>' . __( '<strong>Get video details from video hosting platforms</strong> (Facebook, Gravatar, SlideShare, Soundcloud, Vimeo, Wistia, Youtube).', 'wpsso' ) . '</li>';

						$text .= '<li>' . __( '<strong>Get short URLs from shortening services</strong> (Bitly, DLMY.App, Ow.ly, TinyURL, YOURLS).', 'wpsso' ) . '</li>';

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

			if ( ! empty( $text ) ) {

				if ( 0 === strpos( $msg_key, 'tooltip-' ) && empty( $info[ 'no-tooltip' ] ) ) {

					$tooltip_class = $this->p->cf[ 'form' ][ 'tooltip_class' ];
					$tooltip_icon  = '<span class="' . $tooltip_class . '-icon"></span>';

					if ( false === strpos( $text, '<span class="' . $tooltip_class . '"' ) ) {	// Only add the tooltip wrapper once.

						$text = '<span class="' . $tooltip_class . '" data-help="' . esc_attr( $text ) . '">' . $tooltip_icon . '</span>';
					}
				}
			}

			return $text;
		}

		protected function get_tooltip_fragments( $msg_key = false ) {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'addl_type_urls' => array(
						'label'  => _x( 'Microdata Type URLs', 'option label', 'wpsso' ),
						'name'   => _x( 'Microdata type URLs', 'tooltip fragment', 'wpsso' ),
						'desc'   => _x( 'additional microdata type URLs', 'tooltip fragment', 'wpsso' ),
						'about'  => __( 'https://schema.org/additionalType', 'wpsso' ),
						'filter' => 'wpsso_json_prop_https_schema_org_additionaltype',
					),
					'article_section' => array(
						'label'   => _x( 'Article Section', 'option label', 'wpsso' ),
						'name'    => _x( 'article section', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'an article section', 'tooltip fragment', 'wpsso' ),
						'about'   => __( 'https://schema.org/articleSection', 'wpsso' ),
						'inherit' => true,
					),
					'book_isbn' => array(
						'label' => _x( 'Book ISBN', 'option label', 'wpsso' ),
						'name'  => _x( 'book ISBN', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'an ISBN code (aka International Standard Book Number)', 'tooltip fragment', 'wpsso' ),
					),
					'howto_steps' => array(
						'label' => _x( 'How-To Steps', 'option label', 'wpsso' ),
						'name'  => _x( 'how-to steps', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'how-to steps', 'tooltip fragment', 'wpsso' ),
					),
					'howto_supplies' => array(
						'label' => _x( 'How-To Supplies', 'option label', 'wpsso' ),
						'name'  => _x( 'how-to supplies', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'how-to supplies', 'tooltip fragment', 'wpsso' ),
					),
					'howto_tools' => array(
						'label' => _x( 'How-To Tools', 'option label', 'wpsso' ),
						'name'  => _x( 'how-to tools', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'how-to tools', 'tooltip fragment', 'wpsso' ),
					),
					'img_url' => array(
						'label' => _x( 'Image URL', 'option label', 'wpsso' ),
						'name'  => _x( 'image URL', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'an image URL', 'tooltip fragment', 'wpsso' ),
					),
					'product_adult_type' => array(
						'label'   => _x( 'Product Adult Type', 'option label', 'wpsso' ),
						'name'    => _x( 'product adult type', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product adult type', 'tooltip fragment', 'wpsso' ),
						'about'   => __( 'https://support.google.com/merchants/answer/6324508', 'wpsso' ),
						'values'  => WpssoSchema::get_enumeration_examples( $enum_key = 'adult_type', $val_prefix = '', $val_suffix = 'Consideration' ),
						'inherit' => true,
					),
					'product_age_group' => array(
						'label'   => _x( 'Product Age Group', 'option label', 'wpsso' ),
						'name'    => _x( 'product age group', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product age group', 'tooltip fragment', 'wpsso' ),
						'about'   => __( 'https://support.google.com/merchants/answer/6324463', 'wpsso' ),
						'values'  => WpssoSchema::get_enumeration_examples( $enum_key = 'age_group' ),
						'inherit' => true,
					),
					'product_avail' => array(
						'label'  => _x( 'Product Availability', 'option label', 'wpsso' ),
						'name'   => _x( 'product availability', 'tooltip fragment', 'wpsso' ),
						'desc'   => _x( 'a product availability', 'tooltip fragment', 'wpsso' ),
						'about'  => __( 'https://support.google.com/merchants/answer/6324448', 'wpsso' ),
						'values' => WpssoSchema::get_enumeration_examples( $enum_key = 'item_availability' ),
					),
					'product_brand' => array(
						'label'   => _x( 'Product Brand', 'option label', 'wpsso' ),
						'name'    => _x( 'product brand', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product brand', 'tooltip fragment', 'wpsso' ),
						'about'   => __( 'https://support.google.com/merchants/answer/6324351', 'wpsso' ),
						'inherit' => true,
					),
					'product_category' => array(	// Product Google Category.
						'label'       => _x( 'Product Google Category', 'option label', 'wpsso' ),
						'name'        => _x( 'product Google category', 'tooltip fragment', 'wpsso' ),
						'desc'        => _x( 'a product Google category', 'tooltip fragment', 'wpsso' ),
						'about'       => __( 'https://support.google.com/merchants/answer/6324436', 'wpsso' ),
						'opt_label'   => _x( 'Default Product Google Category', 'option label', 'wpsso' ),
						'opt_menu_id' => 'advanced#sucom-tabset_schema_defs-tab_product',
						'inherit'     => true,
					),
					'product_color' => array(
						'label' => _x( 'Product Color', 'option label', 'wpsso' ),
						'name'  => _x( 'product color', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product color', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324487', 'wpsso' ),
					),
					'product_condition' => array(
						'label'  => _x( 'Product Condition', 'option label', 'wpsso' ),
						'name'   => _x( 'product condition', 'tooltip fragment', 'wpsso' ),
						'desc'   => _x( 'a product condition', 'tooltip fragment', 'wpsso' ),
						'about'  => __( 'https://support.google.com/merchants/answer/6324469', 'wpsso' ),
						'values' => WpssoSchema::get_enumeration_examples( $enum_key = 'item_condition', $val_prefix = '', $val_suffix = 'Condition' ),
					),
					'product_currency' => array(
						'label' => _x( 'Product Currency', 'option label', 'wpsso' ),
						'name'  => _x( 'product currency', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product currency', 'tooltip fragment', 'wpsso' ),
					),
					'product_energy_efficiency' => array(
						'label'   => _x( 'Product Energy Rating', 'option label', 'wpsso' ),
						'name'    => _x( 'product energy efficiency rating', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product energy efficiency rating', 'tooltip fragment', 'wpsso' ),
						'about'   => 'https://support.google.com/merchants/answer/7562785',
						'values'  => WpssoSchema::get_enumeration_examples( $enum_key = 'energy_efficiency', $val_prefix = 'EUEnergyEfficiencyCategory' ),
						'inherit' => true,
					),
					'product_energy_efficiency_min_max' => array(
						'label'   => _x( 'Product Energy Rating Range', 'option label', 'wpsso' ),
						'name'    => _x( 'product energy efficiency rating minimum and maximum', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product energy efficiency rating range', 'tooltip fragment', 'wpsso' ),
						'about'   => 'https://support.google.com/merchants/answer/7562785',
						'values'  => WpssoSchema::get_enumeration_examples( $enum_key = 'energy_efficiency', $val_prefix = 'EUEnergyEfficiencyCategory' ),
						'inherit' => true,
					),
					'product_fluid_volume_value' => array(
						'label' => _x( 'Product Fluid Volume', 'option label', 'wpsso' ),
						'name'  => _x( 'product fluid volume', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product fluid volume', 'tooltip fragment', 'wpsso' ),
					),
					'product_gtin14' => array(
						'label' => _x( 'Product GTIN-14', 'option label', 'wpsso' ),
						'name'  => _x( 'product GTIN-14', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-14 code (aka ITF-14)', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324461', 'wpsso' ),
					),
					'product_gtin13' => array(
						'label' => _x( 'Product GTIN-13 (EAN)', 'option label', 'wpsso' ),
						'name'  => _x( 'product GTIN-13 (EAN)', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-13 code (aka 13-digit ISBN codes or EAN/UCC-13)', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324461', 'wpsso' ),
					),
					'product_gtin12' => array(
						'label' => _x( 'Product GTIN-12 (UPC)', 'option label', 'wpsso' ),
						'name'  => _x( 'product GTIN-12 (UPC)', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-12 code (12-digit GS1 identification key composed of a UPC company prefix, item reference, and check digit)', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324461', 'wpsso' ),
					),
					'product_gtin8' => array(
						'label' => _x( 'Product GTIN-8', 'option label', 'wpsso' ),
						'name'  => _x( 'product GTIN-8', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product GTIN-8 code (aka EAN/UCC-8 or 8-digit EAN)', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324461', 'wpsso' ),
					),
					'product_gtin' => array(
						'label' => _x( 'Product GTIN', 'option label', 'wpsso' ),
						'name'  => _x( 'product GTIN', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product GTIN code (GTIN-8, GTIN-12/UPC, GTIN-13/EAN, or GTIN-14)', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324461', 'wpsso' ),
					),
					'product_height_value' => array(
						'label' => _x( 'Product Net Height', 'option label', 'wpsso' ),
						'name'  => _x( 'product net height', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product net height, as opposed to a shipping or packaged height used for shipping cost calculations', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/11018531', 'wpsso' ),
					),
					'product_isbn' => array(
						'label' => _x( 'Product ISBN', 'option label', 'wpsso' ),
						'name'  => _x( 'product ISBN', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'an ISBN code (aka International Standard Book Number)', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324461', 'wpsso' ),
					),
					'product_length_value' => array(
						'label' => _x( 'Product Net Len. / Depth', 'option label', 'wpsso' ),
						'name'  => _x( 'product net length or depth', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product net length or depth, as opposed to a shipping or packaged length used for shipping cost calculations', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/11018531', 'wpsso' ),
					),
					'product_material' => array(
						'label' => _x( 'Product Material', 'option label', 'wpsso' ),
						'name'  => _x( 'product material', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product material', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324410', 'wpsso' ),
					),
					'product_mfr_part_no' => array(
						'label' => _x( 'Product MPN', 'option label', 'wpsso' ),
						'name'  => _x( 'product MPN', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a Manufacturer Part Number (MPN)', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324482', 'wpsso' ),
					),
					'product_min_advert_price' => array(
						'label'   => _x( 'Product Min Advert Price', 'option label', 'wpsso' ),
						'name'    => _x( 'product minimum advertised price', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a Minimum Advertised Price (MAP)', 'tooltip fragment', 'wpsso' ),
						'inherit' => true,
					),
					'product_mrp' => array(	// Product Return Policy.
						'label'       => _x( 'Product Return Policy', 'option label', 'wpsso' ),
						'name'        => _x( 'product return policy', 'tooltip fragment', 'wpsso' ),
						'desc'        => _x( 'a product return policy', 'tooltip fragment', 'wpsso' ),
						'opt_label'   => _x( 'Default Product Return Policy', 'option label', 'wpsso' ),
						'opt_menu_id' => 'advanced#sucom-tabset_schema_defs-tab_product',
						'inherit'     => true,
					),
					'product_pattern' => array(
						'label' => _x( 'Product Pattern', 'option label', 'wpsso' ),
						'name'  => _x( 'product pattern', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product pattern', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324483', 'wpsso' ),
					),
					'product_price' => array(
						'label' => _x( 'Product Price', 'option label', 'wpsso' ),
						'name'  => _x( 'product price', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product price', 'tooltip fragment', 'wpsso' ),
					),
					'product_price_type' => array(
						'label'   => _x( 'Product Price Type', 'option label', 'wpsso' ),
						'name'    => _x( 'product price type', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product price type', 'tooltip fragment', 'wpsso' ),
						'values'  => WpssoSchema::get_enumeration_examples( $enum_key = 'price_type' ),
						'inherit' => true,
					),
					'product_retailer_part_no' => array(
						'label' => _x( 'Product SKU', 'option label', 'wpsso' ),
						'name'  => _x( 'product SKU', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a Stock-Keeping Unit (SKU)', 'tooltip fragment', 'wpsso' ),
					),
					'product_shipping_height_value' => array(
						'label' => _x( 'Product Shipping Height', 'option label', 'wpsso' ),
						'name'  => _x( 'product shipping height', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product shipping or packaged height used for shipping cost calculations', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324498', 'wpsso' ),
					),
					'product_shipping_length_value' => array(
						'label' => _x( 'Product Shipping Length', 'option label', 'wpsso' ),
						'name'  => _x( 'product shipping length', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product shipping or packaged length used for shipping cost calculations', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324498', 'wpsso' ),
					),
					'product_shipping_weight_value' => array(
						'label' => _x( 'Product Shipping Weight', 'option label', 'wpsso' ),
						'name'  => _x( 'product shipping weight', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product shipping or packaged weight used for shipping cost calculations', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324503', 'wpsso' ),
					),
					'product_shipping_width_value' => array(
						'label' => _x( 'Product Shipping Width', 'option label', 'wpsso' ),
						'name'  => _x( 'product shipping width', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product shipping or packaged width used for shipping cost calculations', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324498', 'wpsso' ),
					),
					'product_size' => array(
						'label' => _x( 'Product Size', 'option label', 'wpsso' ),
						'name'  => _x( 'product size', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product size', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/6324492', 'wpsso' ),
					),
					'product_size_group' => array(
						'label'   => _x( 'Product Size Group', 'option label', 'wpsso' ),
						'name'    => _x( 'product size group', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product size group', 'tooltip fragment', 'wpsso' ),
						'about'   => __( 'https://support.google.com/merchants/answer/6324497', 'wpsso' ),
						'values'  => WpssoSchema::get_enumeration_examples( $enum_key = 'size_group', $val_prefix = 'WearableSizeGroup' ),
						'inherit' => true,
					),
					'product_size_system' => array(
						'label'   => _x( 'Product Size System', 'option label', 'wpsso' ),
						'name'    => _x( 'product size system', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product size system', 'tooltip fragment', 'wpsso' ),
						'about'   => __( 'https://support.google.com/merchants/answer/6324502', 'wpsso' ),
						'values'  => WpssoSchema::get_enumeration_examples( $enum_key = 'size_system', $val_prefix = 'WearableSizeSystem' ),
						'inherit' => true,
					),
					'product_target_gender' => array(
						'label'   => _x( 'Product Target Gender', 'option label', 'wpsso' ),
						'name'    => _x( 'product target gender', 'tooltip fragment', 'wpsso' ),
						'desc'    => _x( 'a product target gender', 'tooltip fragment', 'wpsso' ),
						'about'   => __( 'https://support.google.com/merchants/answer/6324479', 'wpsso' ),
						'values'  => WpssoSchema::get_enumeration_examples( $enum_key = 'target_gender' ),
						'inherit' => true,
					),
					'product_weight_value' => array(
						'label' => _x( 'Product Net Weight', 'option label', 'wpsso' ),
						'name'  => _x( 'product net weight', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product net weight, as opposed to a shipping or packaged weight used for shipping cost calculations', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/11018531', 'wpsso' ),
					),
					'product_width_value' => array(
						'label' => _x( 'Product Net Width', 'option label', 'wpsso' ),
						'name'  => _x( 'product net width', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a product net width, as opposed to a shipping or packaged width used for shipping cost calculations', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://support.google.com/merchants/answer/11018531', 'wpsso' ),
					),
					'review_item_name' => array(
						'label' => _x( 'Review Subject Name', 'option label', 'wpsso' ),
						'name'  => _x( 'review subject name', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a Schema Review subject (aka item reviewed) name', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://developers.google.com/search/docs/appearance/structured-data/review-snippet#review-properties', 'wpsso' ),
					),
					'review_item_desc' => array(
						'label' => _x( 'Review Subject Description', 'option label', 'wpsso' ),
						'name'  => _x( 'review subject description', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a Schema Review subject (aka item reviewed) description', 'tooltip fragment', 'wpsso' ),
					),
					'review_rating' => array(
						'label' => _x( 'Review Rating', 'option label', 'wpsso' ),
						'name'  => _x( 'review rating', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a Schema Review rating', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://schema.org/Review', 'wpsso' ),
					),
					'review_rating_alt_name' => array(
						'label' => _x( 'Rating Alt Name', 'option label', 'wpsso' ),
						'name'  => _x( 'rating alternate name', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a Schema Review rating alternate name', 'tooltip fragment', 'wpsso' ),
						'about' => __( 'https://developers.google.com/search/docs/appearance/structured-data/factcheck#rating', 'wpsso' ),
					),
					'recipe_ingredients' => array(
						'label' => _x( 'Recipe Ingredients', 'option label', 'wpsso' ),
						'name'  => _x( 'recipe ingredients', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'recipe ingredients', 'tooltip fragment', 'wpsso' ),
					),
					'recipe_instructions' => array(
						'label' => _x( 'Recipe Instructions', 'option label', 'wpsso' ),
						'name'  => _x( 'recipe instructions', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'recipe instructions', 'tooltip fragment', 'wpsso' ),
					),
					'sameas_urls' => array(
						'label'  => _x( 'Same-As URLs', 'option label', 'wpsso' ),
						'name'   => _x( 'same-as URLs', 'tooltip fragment', 'wpsso' ),
						'desc'   => _x( 'additional same-as URLs', 'tooltip fragment', 'wpsso' ),
						'filter' => 'wpsso_json_prop_https_schema_org_sameas',
					),
					'vid_embed' => array(
						'label' => _x( 'Video Embed HTML', 'option label', 'wpsso' ),
						'name'  => _x( 'video embed HTML', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'video embed HTML code (not a URL)', 'tooltip fragment', 'wpsso' ),
					),
					'vid_url' => array(
						'label' => _x( 'Video URL', 'option label', 'wpsso' ),
						'name'  => _x( 'video URL', 'tooltip fragment', 'wpsso' ),
						'desc'  => _x( 'a video URL (not HTML code)', 'tooltip fragment', 'wpsso' ),
					),
				);

				$cf_md_index = WpssoConfig::get_cf_md_index();	// Uses a local cache.

				foreach ( $cf_md_index as $opt_cf_key => $md_key ) {

					if ( ! empty( $md_key ) ) {	// Just in case.

						if ( ! empty( $this->p->options[ $opt_cf_key ] ) ) {	// A custom field name is defined.

							$cache_key = preg_replace( '/^plugin_cf_/', '', $opt_cf_key );

							if ( ! empty( $local_cache[ $cache_key ] ) ) {

								$cf_key = $this->p->options[ $opt_cf_key ];	// Example: '_format_video_url'.

								$local_cache[ $cache_key ][ 'import_cf' ] = SucomUtil::sanitize_hookname( 'wpsso_import_cf_' . $cf_key );
							}
						}
					}
				}
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
				_x( 'enabled', 'option value', 'wpsso' ) :
				_x( 'disabled', 'option value', 'wpsso' );

			return $def_checked;
		}

		public function get_def_img_dims( $opt_pre ) {

			$defs = $this->p->opt->get_defaults();

			$img_width   = empty( $defs[ $opt_pre . '_img_width' ] ) ? 0 : $defs[ $opt_pre . '_img_width' ];
			$img_height  = empty( $defs[ $opt_pre . '_img_height' ] ) ? 0 : $defs[ $opt_pre . '_img_height' ];
			$img_cropped = empty( $defs[ $opt_pre . '_img_crop' ] ) ? _x( 'uncropped', 'option value', 'wpsso' ) : _x( 'cropped', 'option value', 'wpsso' );

			return $img_width . 'x' . $img_height . 'px ' . $img_cropped;
		}

		public function get_schema_disabled_rows( $table_rows = array(), $col_span = 1 ) {

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				$table_rows = array();
			}

			$html = '<p class="status-msg">' . __( 'Schema markup is disabled.', 'wpsso' ) . '</p>';

			$html .= '<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>';

			$table_rows[ 'schema_disabled' ] = '<tr><td align="center" colspan="' . $col_span . '">' . $html . '</td></tr>';

			return $table_rows;
		}

		public function get_wp_sitemaps_disabled_rows( $table_rows = array() ) {

			if ( ! is_array( $table_rows ) ) {	// Just in case.

				$table_rows = array();
			}

			$table_rows[ 'wp_sitemaps_disabled' ] = '<tr><td align="center">' .
				$this->wp_sitemaps_disabled() .
				'<p class="status-msg">' . __( 'No options available.', 'wpsso' ) . '</p>' .
				'</td></tr>';


			return $table_rows;
		}

		/*
		 * Define and translate certain strings only once.
		 */
		protected function maybe_set_properties() {

			if ( empty( $this->pkg_info ) ) {

				if ( ! empty( $this->p->util ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'setting properties' );
					}

					$this->pkg_info        = $this->p->util->get_pkg_info();	// Uses a local cache.
					$this->p_name          = $this->pkg_info[ 'wpsso' ][ 'name' ];
					$this->p_name_pro      = $this->pkg_info[ 'wpsso' ][ 'name_pro' ];
					$this->pkg_pro_transl  = _x( $this->p->cf[ 'packages' ][ 'pro' ], 'package name', 'wpsso' );
					$this->pkg_std_transl  = _x( $this->p->cf[ 'packages' ][ 'std' ], 'package name', 'wpsso' );
					$this->fb_prefs_transl = sprintf( __( 'Facebook prefers images of %1$s cropped (for Retina and high-PPI displays), %2$s cropped as a recommended minimum, and ignores images smaller than %3$s.', 'wpsso' ), '1200x628px', '600x314px', '200x200px' );
				}
			}
		}

		/*
		 * Used by the Advanced Settings page for the "Webpage Title Tag" option.
		 *
		 * WpssoMessages->maybe_doc_title_disabled() returns a message if:
		 *
		 *	- An SEO plugin is active.
		 *	- The theme does not support the 'title-tag' feature.
		 *	- The WPSSO_TITLE_TAG_DISABLE constant is true.
		 */
		public function maybe_doc_title_disabled() {

			$html = '';

			/*
			 * WpssoUtil->is_canonical_disabled() returns true if:
			 *
			 *	- An SEO plugin is active.
			 *	- The theme does not support the 'title-tag' feature.
			 *	- The WPSSO_TITLE_TAG_DISABLE constant is true.
			 *	- The 'plugin_title_tag' option is not 'seo_title'.
			 */
			if ( $this->p->util->is_seo_title_disabled() ) {

				if ( ! empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

					$html = __( 'Modifications disabled (SEO plugin detected).', 'wpsso' );

				} elseif ( ! current_theme_supports( 'title-tag' ) ) {

					$title_tag_url = __( 'https://codex.wordpress.org/Title_Tag', 'wpsso' );

					$html = sprintf( __( 'No theme support for <a href="%s">WordPress Title Tag</a>.', 'wpsso' ), $title_tag_url );

				} elseif ( SucomUtil::get_const( 'WPSSO_TITLE_TAG_DISABLE' ) ) {

					$html = sprintf( __( 'Modifications disabled (%s constant is true).', 'wpsso' ), 'WPSSO_TITLE_TAG_DISABLE' );
				}

				if ( ! empty( $html ) ) {

					$html = '<p class="status-msg smaller long_name">' . $html . '</p>';
				}
			}

			return $html;
		}

		/*
		 * WpssoMessages->maybe_seo_title_disabled() returns a message if:
		 *
		 *	- An SEO plugin is active.
		 *	- The theme does not support the 'title-tag' feature.
		 *	- The WPSSO_TITLE_TAG_DISABLE constant is true.
		 *	- The 'plugin_title_tag' option is not 'seo_title'.
		 *
		 * See WpssoEditGeneral->filter_mb_sso_edit_general_rows().
		 */
		public function maybe_seo_title_disabled() {

			$html = '';

			/*
			 * WpssoUtil->is_canonical_disabled() returns true if:
			 *
			 *	- An SEO plugin is active.
			 *	- The theme does not support the 'title-tag' feature.
			 *	- The WPSSO_TITLE_TAG_DISABLE constant is true.
			 *	- The 'plugin_title_tag' option is not 'seo_title'.
			 */
			if ( $this->p->util->is_seo_title_disabled() ) {

				if ( ! empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

					$html = __( 'Modifications disabled (SEO plugin detected).', 'wpsso' );

				} elseif ( ! current_theme_supports( 'title-tag' ) ) {

					$html = __( 'Modifications disabled (no theme support).', 'wpsso' );

				} elseif ( SucomUtil::get_const( 'WPSSO_TITLE_TAG_DISABLE' ) ) {

					$html = sprintf( __( 'Modifications disabled (%s constant is true).', 'wpsso' ), 'WPSSO_TITLE_TAG_DISABLE' );

				} elseif ( 'seo_title' !== $this->p->options[ 'plugin_title_tag' ] ) {

					$opt_key          = 'seo_title';
					$opt_val_transl   = _x( $this->p->cf[ 'form' ][ 'document_title' ][ $opt_key ], 'option value', 'wpsso' );
					$opt_label_transl = _x( 'Webpage Title Tag', 'option label', 'wpsso' );
					$opt_link         = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration', $opt_label_transl );

					$html = sprintf( __( 'Modifications disabled (%1$s option not "%2$s").', 'wpsso' ), $opt_link, $opt_val_transl );
				}

				if ( $html ) {

					$html = '<p class="status-msg smaller">' . $html . '</p>';
				}
			}

			return $html;
		}

		/*
		 * Returns a message if an SEO plugin is active or the meta tag is disabled.
		 *
		 * See WpssoEditGeneral->filter_mb_sso_edit_general_rows().
		 * See WpssoEditVisibility->filter_mb_sso_edit_visibility_rows().
		 * See WpssoEditVisibility->filter_mb_sso_edit_visibility_robots_rows().
		 */
		public function maybe_seo_tag_disabled( $mt_name ) {

			$opt_key = SucomUtil::sanitize_input_name( 'add_' . $mt_name );

			if ( empty( $this->p->options[ $opt_key ] ) ) {	// Option does not exist or is unchecked.

				if ( ! empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {	// An SEO plugin is active.

					$msg = __( 'Modifications disabled (SEO plugin detected).', 'wpsso' );

				} else $msg = sprintf( __( 'Modifications disabled (<code>%s</code> tag disabled).', 'wpsso' ), $mt_name );

				return '<p class="status-msg smaller">' . $msg . '</p>';
			}

			return '';
		}

		/*
		 * See WpssoMessagesTooltipMeta->get().
		 * See WpssoMessagesTooltip->get().
		 */
		public function maybe_add_seo_tag_disabled_link( $mt_name ) {

			$opt_key = SucomUtil::sanitize_input_name( 'add_' . $mt_name );

			if ( empty( $this->p->options[ $opt_key ] ) ) {	// Option does not exist or is unchecked.

				$seo_other_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other',
					_x( 'SSO', 'menu title', 'wpsso' ) . ' &gt; ' .
					_x( 'Advanced Settings', 'lib file description', 'wpsso' ) . ' &gt; ' .
					_x( 'HTML Tags', 'metabox title', 'wpsso' ) . ' &gt; ' .
					_x( 'SEO / Other', 'metabox tab', 'wpsso' ) );

				return ' ' . sprintf( __( 'Note that the <code>%s</code> HTML tag is currently disabled.', 'wpsso' ), $mt_name ) . ' ' .
					sprintf( __( 'You can re-enable this option under the %s tab.', 'wpsso' ), $seo_other_tab_link );
			}

			return '';
		}

		/*
		 * If an add-on is not active, return a short message that this add-on is recommended.
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

			$ext_name_link = $this->p->util->get_admin_url( 'addons#' . $ext, $this->p->cf[ 'plugin' ][ $ext ][ 'name' ] );

			return ' ' . sprintf( _x( 'Activating the %s add-on is recommended for this option.', 'wpsso' ), $ext_name_link );
		}

		/*
		 * Pinterest disabled.
		 *
		 * $extra_css_class can be empty, 'left', or 'inline'.
		 */
		public function maybe_pin_img_disabled( $extra_css_class = '' ) {

			return $this->p->util->is_pin_img_disabled() ? $this->pin_img_disabled( $extra_css_class ) : '';
		}

		/*
		 * Used by the General Settings page.
		 */
		public function maybe_preview_images_first() {

			return empty( $this->form->options[ 'og_vid_prev_img' ] ) ? '' :
				' ' . _x( 'video preview images are enabled (and included first)', 'option comment', 'wpsso' );
		}

		public function maybe_schema_disabled() {

			return $this->p->util->is_schema_disabled() ? '<p class="status-msg smaller">' . __( 'Schema markup is disabled.', 'wpsso' ) . '</p>' : '';
		}

		public function pin_img_disabled( $extra_css_class = '' ) {

			$opt_label = _x( 'Add Hidden Image for Pinterest', 'option label', 'wpsso' );
			$opt_link  = $this->p->util->get_admin_url( 'general#sucom-tabset_social_search-tab_pinterest', $opt_label );

			// translators: %s is the option name, linked to its settings page.
			$text = sprintf( __( 'Modifications disabled (%s option is disabled).', 'wpsso' ), $opt_link );

			return '<p class="status-msg smaller disabled ' . $extra_css_class . '">' . $text . '</p>';
		}

		public function preview_images_are_first() {

			return ' (' . _x( 'video preview images are included first', 'option comment', 'wpsso' ) . ')';
		}

		public function pro_feature( $ext ) {

			list( $ext, $p_ext ) = $this->ext_p_ext( $ext );

			return empty( $ext ) ? '' : $this->get( 'pro-feature-msg', array( 'plugin_id' => $ext ) );
		}

		public function pro_feature_video_api() {

			$this->maybe_set_properties();

			$short_pro = $this->pkg_info[ $this->p->id ][ 'short_pro' ];

			$html = '<p class="pro-feature-msg">';

			$html .= sprintf( __( 'Video service API modules are provided with the %1$s edition.', 'wpsso' ), $short_pro );

			$html .= '</p>';

			return $html;
		}

		public function pro_feature_video_found_notice( $svc_transl, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $svc_transl . ' video URL found but no video API modules' );
			}

			$this->maybe_set_properties();

			$canonical_url = $this->p->util->maybe_set_ref( $canonical_url = null, $mod, __( 'adding video markup', 'wpsso' ) );

			$short_pro = $this->pkg_info[ $this->p->id ][ 'short_pro' ];

			$notice_msg = sprintf( __( 'A %1$s video was found but details about this video (title, description, preview image, upload date, duration, width, height, encoding, etc.) could not be retrieved.', 'wpsso' ), $svc_transl ) . ' ';

			$notice_msg .= sprintf( __( 'Video service API modules are provided with the %1$s edition.', 'wpsso' ), $short_pro );

			$notice_key = 'pro-feature-video-found-notice-' . $svc_transl;

			$this->p->notice->warn( $notice_msg );

			$this->p->util->maybe_unset_ref( $canonical_url );
		}

		/*
		 * Use SucomUtilWP::sitemaps_disabled() as a test before calling this method.
		 */
		public function wp_sitemaps_disabled( $is_notice = false ) {

			$html        = '';
			$is_public   = get_option( 'blog_public' );
			$is_prod_env = 'production' === wp_get_environment_type() ? true : false;	// Since WP v5.5.

			if ( ! $is_prod_env && $is_notice ) {	// Only show notice for production sites.

				return $html;
			}

			$html .= '<p class="status-msg">';
			$html .= __( 'The WordPress sitemaps functionality is disabled.', 'wpsso' );
			$html .= '</p>';

			if ( ! $is_public ) {

				$settings_url = get_admin_url( $blog_id = null, 'options-reading.php' );

				$html .= '<p class="status-msg">';
				$html .= sprintf( __( 'The WordPress <a href="%s">Search Engine Visibility</a> option is set to discourage search engines from indexing this site.', 'wpsso' ), $settings_url );
				$html .= '</p>';
			}

			/*
			 * Check if a theme or another plugin has disabled the Wordpress sitemaps functionality.
			 */
			if ( ! apply_filters( 'wp_sitemaps_enabled', true ) ) {

				$html .= '<p class="status-msg">';
				$html .= __( 'A theme or plugin is returning <code>false</code> for the \'wp_sitemaps_enabled\' filter.', 'wpsso' );
				$html .= '</p>';
			}

			return $is_notice ? preg_replace( '/(<p>|<p[^>]+>|<\/p>)/i', ' ', $html ) : $html;
		}

		/*
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
