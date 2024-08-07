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

if ( ! class_exists( 'WpssoConflict' ) ) {

	class WpssoConflict {

		private $p;		// Wpsso class object.
		private $seo = null;	// WpssoConflictSeo class object.

		/*
		 * Instantiated by Wpsso->set_objects() when is_admin() is true.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) {

				if ( ! SucomUtilWP::doing_block_editor() ) {

					add_action( 'admin_head', array( $this, 'conflict_checks' ), -1000 );
				}
			}
		}

		public function conflict_checks() {

			$this->conflict_check_addon();
			$this->conflict_check_db();
			$this->conflict_check_php();
			$this->conflict_check_vc();
			$this->conflict_check_wp();

			require_once WPSSO_PLUGINDIR . 'lib/conflict-seo.php';

			$this->seo = new WpssoConflictSeo( $this->p );

			$this->seo->conflict_checks();
		}

		private function conflict_check_addon() {

			if ( ! empty( $this->p->avail[ 'p_ext' ][ 'ipm' ] ) ) {

				$pkg_info    = $this->p->util->get_pkg_info();	// Uses a local cache.
				$plugins_url = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( $blog_id = null, 'plugins.php' );
				$plugins_url = add_query_arg( array( 's' => 'wpsso-inherit-parent-meta' ), $plugins_url );
				$addon_name  = __( 'WPSSO Inherit Parent Metadata', 'plugin name', 'wpsso' );

				$notice_msg = sprintf( __( 'The %1$s add-on is deprecated.', 'wpsso' ), $addon_name ) . ' ';

				$notice_msg .= sprintf( __( 'All features of the %1$s add-on were integrated into the %2$s plugin.', 'wpsso' ),
					$addon_name, $pkg_info[ 'wpsso' ][ 'name' ] ) . ' ';

				$notice_msg .= sprintf( __( '<a href="%1$s">Please deactivate and delete the %2$s add-on</a>.', 'wpsso' ),
					$plugins_url, $addon_name ) . ' ';

				$notice_key = 'deactivate-wpsso-inherit-parent-meta';

				$this->p->notice->err( $notice_msg, null, $notice_key );
			}

			if ( ! empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

				$pkg_info    = $this->p->util->get_pkg_info();	// Uses a local cache.
				$plugins_url = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( $blog_id = null, 'plugins.php' );
				$plugins_url = add_query_arg( array( 's' => 'wpsso-schema-json-ld' ), $plugins_url );
				$addon_name  = __( 'WPSSO Schema JSON-LD Markup', 'plugin name', 'wpsso' );

				if ( ! empty( $pkg_info[ 'wpssojson' ][ 'pp' ] ) && empty( $pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

					$notice_msg = sprintf( __( 'The %1$s add-on is deprecated since October 2021.', 'wpsso' ), $addon_name ) . ' ';

					$notice_msg .= sprintf( __( 'All features of the %1$s add-on were integrated into the %2$s and %3$s plugins at that time.', 'wpsso' ),
						$addon_name, $pkg_info[ 'wpsso' ][ 'name_std' ], $pkg_info[ 'wpsso' ][ 'name_pro' ] ) . ' ';

					$notice_msg .= sprintf( __( 'The add-on may continue to function as intended, but is no longer actively maintained or tested for compatibility with the %1$s plugin.', 'wpsso' ), $pkg_info[ 'wpsso' ][ 'name' ] ) . ' ';

					$notice_msg .= sprintf( __( '<a href="%1$s">We suggest deactivating the %2$s add-on at your earliest convenience</a>.', 'wpsso' ),
						$plugins_url, $addon_name ) . ' ';

					$notice_key = 'deactivate-wpsso-schema-json-ld-pro';

					$this->p->notice->warn( $notice_msg, null, $notice_key, 3 * MONTH_IN_SECONDS );

				} else {

					$notice_msg = sprintf( __( 'The %1$s add-on is deprecated.', 'wpsso' ), $addon_name ) . ' ';

					$notice_msg .= sprintf( __( 'All features of the %1$s add-on were integrated into the %2$s plugin.', 'wpsso' ),
						$addon_name, $pkg_info[ 'wpsso' ][ 'name' ] ) . ' ';

					$notice_msg .= sprintf( __( '<a href="%1$s">Please deactivate and delete the %2$s add-on</a>.', 'wpsso' ),
						$plugins_url, $addon_name ) . ' ';

					$notice_key = 'deactivate-wpsso-schema-json-ld';

					$this->p->notice->err( $notice_msg, null, $notice_key );
				}
			}

			if ( ! empty( $this->p->avail[ 'p_ext' ][ 'org' ] ) ) {

				$pkg_info    = $this->p->util->get_pkg_info();	// Uses a local cache.
				$plugins_url = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( $blog_id = null, 'plugins.php' );
				$plugins_url = add_query_arg( array( 's' => 'wpsso+organization+markup' ), $plugins_url );
				$addon_name  = __( 'WPSSO Organization Markup', 'plugin name', 'wpsso' );
				$repl_name   = $pkg_info[ 'wpssoopm' ][ 'name' ];
				$repl_link   = empty( $this->p->avail[ 'p_ext' ][ 'opm' ] ) ? $this->p->util->get_admin_url( 'addons#wpssoopm', $repl_name ) : $repl_name;

				$notice_msg = sprintf( __( 'The %1$s add-on is deprecated and replaced by the %2$s add-on.', 'wpsso' ),
					$addon_name, $repl_link ) . ' ';

				if ( empty( $this->p->avail[ 'p_ext' ][ 'opm' ] ) ) {

					$notice_msg .= sprintf( __( 'After installing and activating %1$s, <a href="%2$s">please deactivate and delete the %3$s add-on</a>.',
						'wpsso' ), $repl_link, $plugins_url, $addon_name ) . ' ';

				} else {

					$notice_msg .= sprintf( __( '<a href="%1$s">Please deactivate and delete the %2$s add-on</a>.', 'wpsso' ),
						$plugins_url, $addon_name ) . ' ';
				}

				$notice_key = 'deactivate-wpsso-organization';

				$this->p->notice->err( $notice_msg, null, $notice_key );
			}

			if ( ! empty( $this->p->avail[ 'p_ext' ][ 'plm' ] ) ) {

				$pkg_info    = $this->p->util->get_pkg_info();	// Uses a local cache.
				$plugins_url = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( $blog_id = null, 'plugins.php' );
				$plugins_url = add_query_arg( array( 's' => 'wpsso-plm' ), $plugins_url );
				$addon_name  = __( 'WPSSO Place and Local SEO Markup', 'plugin name', 'wpsso' );
				$repl_name   = $pkg_info[ 'wpssoopm' ][ 'name' ];
				$repl_link   = empty( $this->p->avail[ 'p_ext' ][ 'opm' ] ) ? $this->p->util->get_admin_url( 'addons#wpssoopm', $repl_name ) : $repl_name;

				$notice_msg = sprintf( __( 'The %1$s add-on is deprecated and replaced by the %2$s add-on.', 'wpsso' ),
					$addon_name, $repl_link ) . ' ';

				if ( empty( $this->p->avail[ 'p_ext' ][ 'opm' ] ) ) {

					$notice_msg .= sprintf( __( 'After installing and activating %1$s, <a href="%2$s">please deactivate and delete the %3$s add-on</a>.',
						'wpsso' ), $repl_link, $plugins_url, $addon_name ) . ' ';

				} else {

					$notice_msg .= sprintf( __( '<a href="%1$s">Please deactivate and delete the %2$s add-on</a>.', 'wpsso' ),
						$plugins_url, $addon_name ) . ' ';
				}

				$notice_key = 'deactivate-wpsso-plm';

				$this->p->notice->err( $notice_msg, null, $notice_key );
			}

			/* if ( ! empty( $this->p->avail[ 'p_ext' ][ 'um' ] ) ) {

				$um_info   = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
				$min_version = WpssoConfig::$cf[ 'um' ][ 'min_version' ];

				if ( version_compare( $um_info[ 'version' ], $min_version, '<' ) ) {
						
					$this->p->notice->err( $this->p->msgs->get( 'notice-um-version-required' ) );
				}
			} */
		}

		private function conflict_check_db() {

			global $wpdb;

			/*
			 * See https://dev.mysql.com/doc/refman/8.0/en/program-variables.html.
			 *
			 * See https://dev.mysql.com/doc/refman/8.0/en/packet-too-large.html.
			 */
			$result = $wpdb->get_results( 'SELECT @@global.max_allowed_packet', ARRAY_A );

			if ( isset( $result[ 0 ][ '@@global.max_allowed_packet' ] ) ) {	// Just in case.

				$max_allowed = $result[ 0 ][ '@@global.max_allowed_packet' ];

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'db max_allowed_packet value is "' . $max_allowed . '"' );
				}

				$min_bytes = 1 * 1024 * 1024;	// 1MB in bytes.
				$def_bytes = 16 * 1024 * 1024;	// 16MB in bytes.

				if ( $max_allowed < $min_bytes ) {

					$notice_msg = sprintf( __( 'Your database is configured for a "%1$s" size of %2$d bytes, which is less than the recommended minimum value of %3$d bytes (a common default value is %4$d bytes).', 'wpsso' ), 'max_allowed_packet', $max_allowed, $min_bytes, $def_bytes ) . ' ';

					$notice_msg .= sprintf( __( 'Please contact your hosting provider and have the "%1$s" database option adjusted to a larger and safer value.', 'wpsso' ), 'max_allowed_packet' ) . ' ';

					$notice_msg .= sprintf( __( 'See the %1$s sections %2$s and %3$s for more information on this database option.', 'wpsso' ), 'MySQL 8.0 Reference Manual', '<a href="https://dev.mysql.com/doc/refman/8.0/en/program-variables.html">Using Options to Set Program Variables</a>', '<a href="https://dev.mysql.com/doc/refman/8.0/en/packet-too-large.html">Packet Too Large</a>', 'max_allowed_packet' ) . ' ';

					$notice_key = 'db-max-allowed-packet-too-small';

					$this->p->notice->err( $notice_msg, null, $notice_key );
				}
			}
		}

		private function conflict_check_php() {

			/*
			 * Load WP class libraries to avoid triggering a bug in EWWW when applying the 'wp_image_editors' filter.
			 */
			require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

			$implementations = apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

			$php_extensions = $this->p->cf[ 'php' ][ 'extensions' ];

			$error_pre = sprintf( __( '%s error:', 'wpsso' ), __METHOD__ );

			foreach ( $php_extensions as $php_ext => $php_info ) {

				/*
				 * Skip image extensions for WordPress image editors that are not used.
				 */
				if ( ! empty( $php_info[ 'wp_image_editor' ][ 'class' ] ) ) {

					if ( ! in_array( $php_info[ 'wp_image_editor' ][ 'class' ], $implementations ) ) {

						continue;
					}
				}

				$notice_msg = '';	// Clear any previous error message.
				$notice_key = '';	// Clear any previous error message key.

				/*
				 * Check for the extension first, then maybe check for its functions.
				 */
				if ( ! extension_loaded( $php_ext ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'php ' . $php_ext . ' extension module is not loaded' );
					}

					/*
					 * If this is a WordPress image editing extension, add information about the WordPress
					 * image editing class.
					 */
					if ( ! empty( $php_info[ 'wp_image_editor' ][ 'class' ] ) ) {

						/*
						 * If we have a WordPress reference URL for this image editing class, link the
						 * image editor class name.
						 */
						if ( ! empty( $php_info[ 'wp_image_editor' ][ 'url' ] ) ) {

							$editor_class = '<a href="' . $php_info[ 'wp_image_editor' ][ 'url' ] . '">' .
								$php_info[ 'wp_image_editor' ][ 'class' ] . '</a>';

						} else {
							$editor_class = $php_info[ 'wp_image_editor' ][ 'class' ];
						}

						$notice_msg .= sprintf( __( 'WordPress is configured to use the %1$s image editing class but the <a href="%2$s">PHP %3$s extension module</a> is not loaded:', 'wpsso' ), $editor_class, $php_info[ 'url' ], $php_info[ 'label' ] ) . ' ';

					} else {

						$notice_msg .= sprintf( __( 'The <a href="%1$s">PHP %2$s extension module</a> is not loaded:', 'wpsso' ),
							$php_info[ 'url' ], $php_info[ 'label' ] ) . ' ';
					}

					/*
					 * Add additional / mode specific information about this check for the hosting provider.
					 */
					$notice_msg .= sprintf( __( 'The <a href="%1$s">PHP %2$s function</a> for "%3$s" returned false.', 'wpsso' ),
						__( 'https://secure.php.net/manual/en/function.extension-loaded.php', 'wpsso' ),
							'<code>extension_loaded()</code>', $php_ext ) . ' ';


					/*
					 * If we are checking for the ImageMagick PHP extension, make sure the user knows the
					 * difference between the OS package and the PHP extension.
					 */
					if ( $php_ext === 'imagick' ) {

						$notice_msg .= sprintf( __( 'Note that the ImageMagick application and the PHP "%1$s" extension are two different products - this error is for the PHP "%1$s" extension, not the ImageMagick application.', 'wpsso' ), $php_ext ) . ' ';
					}

					$notice_msg .= sprintf( __( 'Please contact your hosting provider to have the missing PHP "%1$s" extension installed and enabled.', 'wpsso' ), $php_ext );

					$notice_key .= 'php-extension-' . $php_ext . '-not-loaded';

				/*
				 * If the PHP extension is loaded, then maybe check to make sure the extension is complete. ;-)
				 */
				} elseif ( ! empty( $php_info[ 'classes' ] ) && is_array( $php_info[ 'classes' ] ) ) {

					foreach ( $php_info[ 'classes' ] as $class_name ) {

						if ( ! class_exists( $class_name ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'php ' . $class_name . ' class is missing' );
							}

							$notice_msg .= sprintf( __( 'The <a href="%1$s">PHP %2$s extension module</a> is loaded but the %3$s class is missing.', 'wpsso' ), $php_info[ 'url' ], $php_info[ 'label' ], '<code>' . $class_name . '</code>' ) . ' ';

							$notice_msg .= __( 'Please contact your hosting provider to have the missing PHP class installed.', 'wpsso' );

							$notice_key .= 'php-class-' . $class_name . '-missing';
						}
					}

				} elseif ( ! empty( $php_info[ 'functions' ] ) && is_array( $php_info[ 'functions' ] ) ) {

					foreach ( $php_info[ 'functions' ] as $function_name ) {

						if ( ! function_exists( $function_name ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'php ' . $function_name . '() function is missing' );
							}

							$notice_msg .= sprintf( __( 'The <a href="%1$s">PHP %2$s extension module</a> is loaded but the %3$s function is missing.', 'wpsso' ), $php_info[ 'url' ], $php_info[ 'label' ], '<code>' . $function_name . '()</code>' ) . ' ';

							$notice_msg .= __( 'Please contact your hosting provider to have the missing PHP function installed.', 'wpsso' );

							$notice_key .= 'php-function-' . $function_name . '-missing';
						}
					}
				}

				if ( ! empty( $notice_msg ) ) {

					$this->p->notice->err( $notice_msg, null, $notice_key );

					SucomUtil::safe_error_log( $error_pre . ' ' . $notice_msg, $strip_html = true );
				}
			}
		}

		private function conflict_check_vc() {

			if ( defined( 'WPB_VC_VERSION' ) ) {

				/*
				 * Although no specific entry was added in the WPBakery changelog, it has been reported that this
				 * bug is now fixed in the current WPBakery version (6.1.0).
				 *
				 * https://kb.wpbakery.com/docs/preface/release-notes/
				 */
				$min_version = '6.1.0';

				if ( version_compare( WPB_VC_VERSION, $min_version, '<' ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'visual composer version with event bug detected' );
					}

					$blog_post_url = 'https://surniaulula.com/2018/apps/wordpress/plugins/wpbakery/wpbakery-visual-composer-bug-in-change-handler/';

					$notice_msg = __( 'An issue with WPBakery Visual Composer has been detected.', 'wpsso' ) . ' ';

					$notice_msg .= sprintf( __( 'WPBakery Visual Composer version %s and older are known to have a bug in their jQuery event handling code.', 'wpsso' ), $min_version ) . ' ';

					$notice_msg .= __( 'To avoid jQuery crashing on show / hide jQuery events, please update your version of WPBakery Visual Composer immediately.', 'wpsso' );

					$notice_key = 'wpb-vc-version-event-bug-' . $min_version;

					$this->p->notice->err( $notice_msg, null, $notice_key );
				}
			}
		}

		private function conflict_check_wp() {

			$is_public   = get_option( 'blog_public' );
			$is_prod_env = 'production' === wp_get_environment_type() ? true : false;	// Since WP v5.5.

			if ( ! $is_public && $is_prod_env ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'wp blog_public option is disabled' );
				}

				$settings_url = get_admin_url( $blog_id = null, 'options-reading.php' );

				$notice_msg = sprintf( __( 'The WordPress <a href="%s">Search Engine Visibility</a> option is set to discourage search engines from indexing this site.', 'wpsso' ), $settings_url ) . ' ';

				$notice_msg .= __( 'This is not compatible with the purpose of optimizing your content for social and search engines - please uncheck the option to allow search engines and social sites to access your site.', 'wpsso' );

				$notice_key   = 'wp-search-engine-visibility-disabled';
				$dismiss_time = MONTH_IN_SECONDS;

				$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time );
			}
		}
	}
}
