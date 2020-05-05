<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {
	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoConflict' ) ) {

	class WpssoConflict {

		private $p;

		/**
		 * Instantiated by Wpsso->set_objects() when is_admin() is true.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! SucomUtil::get_const( 'DOING_AJAX' ) ) {

				if ( ! SucomUtilWP::doing_block_editor() ) {

					add_action( 'admin_head', array( $this, 'conflict_checks' ), -1000 );
				}
			}
		}

		public function conflict_checks() {

			$this->conflict_check_db();
			$this->conflict_check_php();
			$this->conflict_check_seo();
			$this->conflict_check_vc();
			$this->conflict_check_wp();
		}

		private function conflict_check_db() {

			global $wpdb;

			$db_query = 'SHOW VARIABLES LIKE "%s";';
			$db_args  = array( 'max_allowed_packet' );

			$db_query = $wpdb->prepare( $db_query, $db_args );

			/**
			 * OBJECT_K returns an associative array of objects.
			 */
			$result = $wpdb->get_results( $db_query, OBJECT_K );

			/**
			 * https://dev.mysql.com/doc/refman/8.0/en/program-variables.html
			 * https://dev.mysql.com/doc/refman/8.0/en/packet-too-large.html
			 */
			if ( isset( $result[ 'max_allowed_packet' ]->Value ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'db max_allowed_packet value is "' . $result[ 'max_allowed_packet' ]->Value . '"' );
				}

				$min_bytes = 1 * 1024 * 1024;	// 1MB in bytes.
				$def_bytes = 16 * 1024 * 1024;	// 16MB in bytes.

				if ( $result[ 'max_allowed_packet' ]->Value < $min_bytes ) {

					$notice_msg = sprintf( __( 'Your database is configured for a "%1$s" size of %2$d bytes, which is less than the recommended minimum value of %3$d bytes (a common default value is %4$d bytes).', 'wpsso' ), 'max_allowed_packet', $result[ 'max_allowed_packet' ]->Value, $min_bytes, $def_bytes ) . ' ';

					$notice_msg .= sprintf( __( 'Please contact your hosting provider and have the "%1$s" database option adjusted to a larger and safer value.', 'wpsso' ), 'max_allowed_packet' ) . ' ';

					$notice_msg .= sprintf( __( 'See the %1$s sections %2$s and %3$s for more information on this database option.', 'wpsso' ), 'MySQL 8.0 Reference Manual', '<a href="https://dev.mysql.com/doc/refman/8.0/en/program-variables.html">Using Options to Set Program Variables</a>', '<a href="https://dev.mysql.com/doc/refman/8.0/en/packet-too-large.html">Packet Too Large</a>', 'max_allowed_packet' ) . ' ';

					$notice_key = 'db-max-allowed-packet-too-small';

					$this->p->notice->err( $notice_msg, null, $notice_key );
				}
			}
		}

		private function conflict_check_php() {

			/**
			 * Load the WP class libraries to avoid triggering a known bug in EWWW when applying the 'wp_image_editors'
			 * filter.
			 */
			require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

			$implementations = apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

			$php_extensions = $this->p->cf[ 'php' ][ 'extensions' ];

			$error_pre = sprintf( __( '%s error:', 'wpsso' ), __METHOD__ );

			foreach ( $php_extensions as $php_ext => $php_info ) {

				/**
				 * Skip image extensions for WordPress image editors that are not used.
				 */
				if ( ! empty( $php_info[ 'wp_image_editor' ][ 'class' ] ) ) {

					if ( ! in_array( $php_info[ 'wp_image_editor' ][ 'class' ], $implementations ) ) {

						continue;
					}
				}

				$notice_msg = '';	// Clear any previous error message.

				/**
				 * Check for the extension first, then maybe check for its functions.
				 */
				if ( ! extension_loaded( $php_ext ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'php ' . $php_ext . ' extension module is not loaded' );
					}

					/**
					 * If this is a WordPress image editing extension, add information about the WordPress
					 * image editing class.
					 */
					if ( ! empty( $php_info[ 'wp_image_editor' ][ 'class' ] ) ) {

						/**
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

					/**
					 * Add additional / mode specific information about this check for the hosting provider.
					 */
					$notice_msg .= sprintf( __( 'The <a href="%1$s">PHP %2$s function</a> for "%3$s" returned false.', 'wpsso' ),
						__( 'https://secure.php.net/manual/en/function.extension-loaded.php', 'wpsso' ),
							'<code>extension_loaded()</code>', $php_ext ) . ' ';


					/**
					 * If we are checking for the ImageMagick PHP extension, make sure the user knows the
					 * difference between the OS package and the PHP extension.
					 */
					if ( $php_ext === 'imagick' ) {

						$notice_msg .= sprintf( __( 'Note that the ImageMagick application and the PHP "%1$s" extension are two different products &mdash; this error is for the PHP "%1$s" extension, not the ImageMagick application.', 'wpsso' ), $php_ext ) . ' ';
					}

					$notice_msg .= sprintf( __( 'Please contact your hosting provider to have the missing PHP "%1$s" extension installed and enabled.', 'wpsso' ), $php_ext );

				/**
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
						}
					}
				}

				if ( ! empty( $notice_msg ) ) {

					$this->p->notice->err( $notice_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $notice_msg, $strip_html = true );
				}
			}
		}

		private function conflict_check_seo() {

			$notice_pre =  __( 'Plugin conflict detected', 'wpsso' ) . ' &mdash; ';

			$log_pre = 'seo plugin conflict detected - ';

			/**
			 * All in One SEO Pack
			 */
			if ( $this->p->avail[ 'seo' ][ 'aioseop' ] ) {

				$opts = get_option( 'aioseop_options' );

				if ( ! empty( $opts[ 'modules' ][ 'aiosp_feature_manager_options' ][ 'aiosp_feature_manager_enable_opengraph' ] ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl = '<strong>' . __( 'Social Meta', 'all-in-one-seo-pack' ) . '</strong>';

					$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=all-in-one-seo-pack%2Fmodules%2Faioseop_feature_manager.php' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'All in One SEO', 'all-in-one-seo-pack' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Feature Manager', 'all-in-one-seo-pack' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'aioseop social meta feature is enabled' );
					}

					$this->p->notice->err( $notice_pre . sprintf( __( 'please deactivate the %1$s feature in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

				if ( ! empty( $opts[ 'aiosp_schema_markup' ] ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl = '<strong>' . __( 'Use Schema.org Markup', 'all-in-one-seo-pack' ) . '</strong>';

					$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=all-in-one-seo-pack%2Faioseop_class.php' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'All in One SEO', 'all-in-one-seo-pack' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'General Settings', 'all-in-one-seo-pack' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'General Settings', 'all-in-one-seo-pack' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'aioseop schema markup option is checked' );
					}

					$this->p->notice->err( $notice_pre . sprintf( __( 'please uncheck the %1$s option in the %2$s metabox.',
						'wpsso' ), $label_transl, $settings_link ) );
				}
			}

			/**
			 * SEO Ultimate
			 */
			if ( $this->p->avail[ 'seo' ][ 'seou' ] ) {

				$opts = get_option( 'seo_ultimate' );

				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=seo' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'SEO Ultimate', 'seo-ultimate' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Modules', 'seo-ultimate' ) . '</a>';

				if ( ! empty( $opts[ 'modules' ] ) && is_array( $opts[ 'modules' ] ) ) {

					if ( array_key_exists( 'opengraph', $opts[ 'modules' ] ) && $opts[ 'modules' ][ 'opengraph' ] !== -10 ) {

						// translators: Please ignore - translation uses a different text domain.
						$label_transl = '<strong>' . __( 'Open Graph Integrator', 'seo-ultimate' ) . '</strong>';

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'seo ultimate opengraph module is enabled' );
						}

						$this->p->notice->err( $notice_pre . sprintf( __( 'please disable the %1$s module in the %2$s settings.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}
			}

			/**
			 * Squirrly SEO
			 */
			if ( $this->p->avail[ 'seo' ][ 'sq' ] ) {

				$opts = json_decode( get_option( 'sq_options' ), $assoc = true );

				/**
				 * Squirrly SEO > SEO Settings > Social Media > Social Media Options Metabox
				 */
				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=sq_seo#socials' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Squirrly', 'squirrly-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'SEO Settings', 'squirrly-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Media', 'squirrly-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Media Options', 'squirrly-seo' ) . '</a>';

				foreach ( array(
					'sq_auto_facebook' => '"<strong>' . __( 'Add the Social Open Graph protocol so that your Facebook shares look good.',
						'wpsso' ) . '</strong>"',
					'sq_auto_twitter' => '"<strong>' . __( 'Add the Twitter card in your tweets.',
						'wpsso' ) . '</strong>"',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'squirrly seo ' . $opt_key . ' option is enabled' );
						}

						$this->p->notice->err( $notice_pre . sprintf( __( 'please disable the %1$s option in the %2$s metabox.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}

				/**
				 * Squirrly SEO > SEO Settings > SEO Settings > Let Squirrly SEO Optimize This Blog Metabox
				 */
				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=sq_seo#seo' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Squirrly', 'squirrly-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'SEO Settings', 'squirrly-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'SEO Settings', 'squirrly-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Let Squirrly SEO Optimize This Blog', 'squirrly-seo' ) . '</a>';

				foreach ( array(
					'sq_auto_jsonld' => '"<strong>' . __( 'adds the Json-LD metas for Semantic SEO', 'wpsso' ) . '</strong>"',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'squirrly seo ' . $opt_key . ' option is enabled' );
						}

						$this->p->notice->err( $notice_pre . sprintf( __( 'please disable the %1$s option in the %2$s metabox.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}
			}

			/**
			 * The SEO Framework
			 */
			if ( $this->p->avail[ 'seo' ][ 'autodescription' ] ) {

				$tsf = the_seo_framework();

				$opts = $tsf->get_all_options();

				/**
				 * The SEO Framework > Social Meta Settings Metabox
				 */
				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=theseoframework-settings' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'The SEO Framework', 'autodescription' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Meta Settings', 'autodescription' ) . '</a>';

				// translators: Please ignore - translation uses a different text domain.
				$posts_i18n = __( 'Posts', 'autodescription' );

				foreach ( array(
					// translators: Please ignore - translation uses a different text domain.
					'og_tags'       => '<strong>' . __( 'Output Open Graph meta tags?', 'autodescription' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'facebook_tags' => '<strong>' . __( 'Output Facebook meta tags?', 'autodescription' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'twitter_tags'  => '<strong>' . __( 'Output Twitter meta tags?', 'autodescription' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'post_publish_time' => '<strong>' . sprintf( __( 'Add %1$s to %2$s?', 'autodescription' ),
						'article:published_time', $posts_i18n ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'post_modify_time' => '<strong>' . sprintf( __( 'Add %1$s to %2$s?', 'autodescription' ),
						'article:modified_time', $posts_i18n ) . '</strong>',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'autodescription ' . $opt_key . ' option is checked' );
						}

						$this->p->notice->err( $notice_pre . sprintf( __( 'please uncheck the %1$s option in the %2$s metabox.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}

				/**
				 * The SEO Framework > Schema Settings Metabox
				 */
				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'The SEO Framework', 'autodescription' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Schema Settings', 'autodescription' ) . '</a>';

				if ( ! empty( $opts[ 'knowledge_output' ] ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl = '<strong>' . __( 'Output Authorized Presence?', 'autodescription' ) . '</strong>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'autodescription knowledge_output option is checked' );
					}

					$this->p->notice->err( $notice_pre . sprintf( __( 'please uncheck the %1$s option in the %2$s metabox.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

			}

			/**
			 * WP Meta SEO
			 */
			if ( $this->p->avail[ 'seo' ][ 'wpmetaseo' ] ) {

				$opts = get_option( '_metaseo_settings' );

				/**
				 * WP Meta SEO > Settings > Global
				 */
				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=metaseo_settings' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'WP Meta SEO', 'wp-meta-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Settings', 'wp-meta-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Global', 'wp-meta-seo' ) . '</a>';

				foreach ( array(
					// translators: Please ignore - translation uses a different text domain.
					'metaseo_showfacebook' => '<strong>' . __( 'Facebook profile URL', 'wp-meta-seo' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'metaseo_showfbappid'  => '<strong>' . __( 'Facebook App ID', 'wp-meta-seo' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'metaseo_showtwitter'  => '<strong>' . __( 'Twitter Username', 'wp-meta-seo' ) . '</strong>',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'wpmetaseo ' . $opt_key . ' option is not empty' );
						}

						$this->p->notice->err( $notice_pre . sprintf( __( 'please remove the %1$s option value in the %2$s settings.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}

				if ( ! empty( $opts[ 'metaseo_showsocial' ] ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl = '<strong>' . __( 'Social sharing block', 'wp-meta-seo' ) . '</strong>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpmetaseo metaseo_showsocial option is enabled' );
					}

					$this->p->notice->err( $notice_pre . sprintf( __( 'please disable the %1$s option in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}
			}

			/**
			 * Yoast SEO
			 */
			if ( $this->p->avail[ 'seo' ][ 'wpseo' ] ) {

				$opts = get_option( 'wpseo_social' );

				/**
				 * Yoast SEO > Social > Accounts Tab
				 */
				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_social#top#accounts' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Accounts', 'wordpress-seo' ) . '</a>';

				foreach ( array(
					// translators: Please ignore - translation uses a different text domain.
					'facebook_site'   => '<strong>' . __( 'Facebook Page URL', 'wordpress-seo' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'twitter_site'    => '<strong>' . __( 'Twitter Username', 'wordpress-seo' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'instagram_url'   => '<strong>' . __( 'Instagram URL', 'wordpress-seo' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'linkedin_url'    => '<strong>' . __( 'LinkedIn URL', 'wordpress-seo' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'myspace_url'     => '<strong>' . __( 'MySpace URL', 'wordpress-seo' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'pinterest_url'   => '<strong>' . __( 'Pinterest URL', 'wordpress-seo' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'youtube_url'     => '<strong>' . __( 'YouTube URL', 'wordpress-seo' ) . '</strong>',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'wpseo ' . $opt_key . ' option is not empty' );
						}

						$this->p->notice->err( $notice_pre . sprintf( __( 'please remove the %1$s option value in the %2$s settings.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}

				/**
				 * Yoast SEO > Social > Faceboook Tab
				 */
				if ( ! empty( $opts[ 'opengraph' ] ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl = '<strong>' . __( 'Add Open Graph meta data', 'wordpress-seo' ) . '</strong>';

					$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_social#top#facebook' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Facebook', 'wordpress-seo' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpseo opengraph option is enabled' );
					}

					$this->p->notice->err( $notice_pre . sprintf( __( 'please disable the %1$s option in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

				if ( ! empty( $opts[ 'fbadminapp' ] ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl = '<strong>' . __( 'Facebook App ID', 'wordpress-seo' ) . '</strong>';

					$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_social#top#facebook' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Facebook', 'wordpress-seo' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpseo fbadminapp option is not empty' );
					}

					$this->p->notice->err( $notice_pre . sprintf( __( 'please remove the %1$s option value in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

				/**
				 * Yoast SEO > Social > Twitter Tab
				 */
				if ( ! empty( $opts[ 'twitter' ] ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl = '<strong>' . __( 'Add Twitter Card meta data', 'wordpress-seo' ) . '</strong>';

					$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_social#top#twitterbox' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Twitter', 'wordpress-seo' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpseo twitter option is enabled' );
					}

					$this->p->notice->err( $notice_pre . sprintf( __( 'please disable the %1$s option in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}
			}

			/**
			 * Yoast SEO for WooCommerce
			 */
			if ( $this->p->avail[ 'seo' ][ 'wpseo-wc' ] ) {

				$ext = 'wpssojson';
				$pkg = $this->p->admin->plugin_pkg_info();

				$wpseo_wc_label = 'Yoast SEO: WooCommerce';

				if ( ! empty( $pkg[ $this->p->lca ][ 'pp' ] ) && ! empty( $pkg[ $ext ][ 'pp' ] ) ) {

					$plugins_url = is_multisite() ? network_admin_url( 'plugins.php', null ) :
						get_admin_url( $blog_id = null, 'plugins.php' );

					$plugins_url = add_query_arg( array( 's' => 'yoast seo' ), $plugins_url );

					$notice_msg = sprintf( __( 'The combination of %1$s and its %2$s add-on provide much better Schema markup for WooCommerce products than the %3$s plugin.', 'wpsso' ), $pkg[ $this->p->lca ][ 'short_pro' ], $pkg[ $ext ][ 'short_pro' ], $wpseo_wc_label ) . ' ';

					$notice_msg .= sprintf( __( 'There is absolutely no advantage in continuing to use the %1$s plugin.', 'wpsso' ), $wpseo_wc_label ) . ' ';

					$notice_msg .= sprintf( __( 'To avoid adding incorrect and confusing Schema markup in your webpages, <a href="%1$s">please deactivate the %2$s plugin immediately</a>.' ), $plugins_url, $wpseo_wc_label );

					$notice_key = 'deactivate-wpseo-woocommerce';

					$this->p->notice->err( $notice_msg, null, $notice_key );
				}
			}

		}

		private function conflict_check_vc() {

			if ( defined( 'WPB_VC_VERSION' ) ) {

				/**
				 * Although no specific entry was added in the WPBakery changelog, it has been reported that this
				 * bug is now fixed in the current WPBakery version (6.1.0).
				 *
				 * https://kb.wpbakery.com/docs/preface/release-notes/
				 */
				$wpb_vc_version_event_bug = '6.0.5';

				if ( version_compare( WPB_VC_VERSION, $wpb_vc_version_event_bug, '<=' ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'visual composer version with event bug detected' );
					}

					$blog_post_url = 'https://surniaulula.com/2018/apps/wordpress/plugins/wpbakery/wpbakery-visual-composer-bug-in-change-handler/';

					$notice_msg = __( 'An issue with WPBakery Visual Composer has been detected.', 'wpsso' ) . ' ';

					$notice_msg .= sprintf( __( 'WPBakery Visual Composer version %s and older are known to have a bug in their jQuery event handling code.', 'wpsso' ), $wpb_vc_version_event_bug ) . ' ';

					$notice_msg .= __( 'To avoid jQuery crashing on show / hide jQuery events, please update your version of WPBakery Visual Composer immediately.', 'wpsso' );

					$notice_key = 'wpb-vc-version-event-bug-' . $wpb_vc_version_event_bug;

					$this->p->notice->err( $notice_msg, null, $notice_key );
				}
			}
		}

		private function conflict_check_wp() {

			if ( ! get_option( 'blog_public' ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'wp blog_public option is disabled' );
				}

				$settings_url = get_admin_url( $blog_id = null, 'options-reading.php' );

				$notice_msg = sprintf( __( 'The WordPress <a href="%s">Search Engine Visibility</a> option is set to discourage search engine and social sites from indexing this site. This is not compatible with the purpose of sharing content on social sites &mdash; please uncheck the option to allow search engines and social sites to access your content.', 'wpsso' ), $settings_url );

				$notice_key = 'wp-search-engine-visibility-disabled';

				$dismiss_time = YEAR_IN_SECONDS;

				$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time );
			}
		}
	}
}
