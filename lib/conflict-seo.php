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

if ( ! class_exists( 'WpssoConflictSeo' ) ) {

	class WpssoConflictSeo {

		private $p;	// Wpsso class object.

		private $log_pre    = '';
		private $notice_pre = '';

		/*
		 * Instantiated by WpssoConflict->conflict_checks().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function conflict_checks() {

			if ( empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

				return;
			}

			$this->log_pre    = 'seo plugin conflict detected - ';
			$this->notice_pre =  __( 'Plugin conflict detected:', 'wpsso' ) . ' ';

			$this->conflict_check_aioseop();	// All in One SEO Pack.
			$this->conflict_check_rankmath();	// Rank Math.
			$this->conflict_check_seoframework();	// The SEO Framework.
			$this->conflict_check_seopress();	// SEOPress.
			$this->conflict_check_seoultimate();	// SEO Ultimate.
			$this->conflict_check_squirrlyseo();	// Squirrly SEO.
			$this->conflict_check_wpmetaseo();	// WP Meta SEO.
			$this->conflict_check_wpseo();		// Yoast SEO.
			$this->conflict_check_wpseo_wc();	// Yoast WooCommerce SEO.
		}

		/*
		 * All in One SEO Pack.
		 */
		private function conflict_check_aioseop() {

			if ( empty( $this->p->avail[ 'seo' ][ 'aioseop' ] ) ) {

				return;
			}

			$plugin_name = __( 'All in One SEO', 'wpsso' );

			/*
			 * Check for minimum supported version.
			 */
			$min_version = '4.0.16';

			if ( ! defined( 'AIOSEO_VERSION' ) || version_compare( AIOSEO_VERSION, $min_version, '<' ) ) {

				$notice_msg = __( 'The %1$s plugin is too old - please update the %1$s plugin to version %2$s or newer.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $plugin_name, $min_version );
				$notice_key = 'aioseo-version-' . AIOSEO_VERSION . '-too-old';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );

				return;
			}

			/*
			 * Check for Open Graph.
			 */
			if ( aioseo()->options->social->facebook->general->enable ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl  = '<strong>' . __( 'Enable Open Graph Markup', 'all-in-one-seo-pack' ) . '</strong>';
				$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=aioseo-social-networks#/facebook' );
				$settings_link = '<a href="' . $settings_url . '">' . $plugin_name . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Networks', 'all-in-one-seo-pack' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Facebook', 'all-in-one-seo-pack' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'aioseop open graph markup is enabled' );
				}

				$notice_msg = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
				$notice_key = 'aioseo-open-graph-markup-enabled';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
			}

			/*
			 * Check for X (Twitter).
			 */
			if ( aioseo()->options->social->twitter->general->enable ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl  = '<strong>' . __( 'Enable Twitter Card', 'all-in-one-seo-pack' ) . '</strong>';
				$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=aioseo-social-networks#/twitter' );
				$settings_link = '<a href="' . $settings_url . '">' . $plugin_name . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Networks', 'all-in-one-seo-pack' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Twitter', 'all-in-one-seo-pack' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'aioseop twitter card is enabled' );
				}

				$notice_msg = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
				$notice_key = 'aioseo-twitter-card-enabled';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
			}
		}

		/*
		 * Rank Math.
		 */
		private function conflict_check_rankmath() {

			if ( empty( $this->p->avail[ 'seo' ][ 'rankmath' ] ) ) {

				return;
			}

			// translators: Please ignore - translation uses a different text domain.
			$plugin_name = __( 'Rank Math', 'rank-math' );

			/*
			 * Check for Schema (Structured Data) module.
			 */
			if ( \RankMath\Helper::is_module_active( 'rich-snippet' ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl  = __( 'Schema (Structured Data)', 'rank-math' );

				$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=rank-math' );

				// translators: Please ignore - translation uses a different text domain.
				$settings_link = '<a href="' . $settings_url . '">' . $plugin_name . ' &gt; ' . __( 'Dashboard', 'rank-math' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'rankmath schema module is enabled' );
				}

				$notice_msg = __( 'Please disable the %1$s module in the %2$s settings.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
				$notice_key = 'rankmath-schema-module-enabled';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
			}
		}

		/*
		 * The SEO Framework.
		 */
		private function conflict_check_seoframework() {

			if ( empty( $this->p->avail[ 'seo' ][ 'seoframework' ] ) ) {

				return;
			}

			$plugin_name = __( 'The SEO Framework', 'wpsso' );

			$tsf = the_seo_framework();

			$opts = $tsf->get_all_options();

			/*
			 * Check for Open Graph and X (Twitter) Cards.
			 */
			$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=theseoframework-settings' );

			$social_gen_link = '<a href="' . $settings_url . '">' . $plugin_name . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Social Meta Settings', 'autodescription' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'General', 'autodescription' ) . '</a>';

			$social_post_link = '<a href="' . $settings_url . '">' . $plugin_name . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Social Meta Settings', 'autodescription' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Post Dates', 'autodescription' ) . '</a>';

			$schema_presence_link = '<a href="' . $settings_url . '">' . $plugin_name . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Schema.org Settings', 'autodescription' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Presence', 'autodescription' ) . '</a>';

			// translators: Please ignore - translation uses a different text domain.
			$posts_i18n = __( 'posts', 'autodescription' );

			/*
			 * The SEO Framework options that must be disabled.
			 */
			foreach ( array(
				$social_gen_link => array(
					// translators: Please ignore - translation uses a different text domain.
					'og_tags'        => '<strong>' . __( 'Output Open Graph meta tags?', 'autodescription' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'facebook_tags'  => '<strong>' . __( 'Output Facebook meta tags?', 'autodescription' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'twitter_tags'   => '<strong>' . __( 'Output Twitter meta tags?', 'autodescription' ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'oembed_scripts' => '<strong>' . __( 'Output oEmbed scripts?', 'autodescription' ) . '</strong>',
				),
				$social_post_link => array(
					// translators: Please ignore - translation uses a different text domain.
					'post_publish_time' => '<strong>' . sprintf( __( 'Add %1$s to %2$s?', 'autodescription' ),
						'article:published_time', $posts_i18n ) . '</strong>',
					// translators: Please ignore - translation uses a different text domain.
					'post_modify_time'  => '<strong>' . sprintf( __( 'Add %1$s to %2$s?', 'autodescription' ),
						'article:modified_time', $posts_i18n ) . '</strong>',
				),
				$schema_presence_link => array(
					// translators: Please ignore - translation uses a different text domain.
					'knowledge_output' => '<strong>' . __( 'Output Authorized Presence?', 'autodescription' ) . '</strong>',
				),
			) as $settings_link => $keys ) {

				foreach ( $keys as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $this->log_pre . 'seoframework ' . $opt_key . ' option is enabled' );
						}

						$notice_msg = __( 'Please disable the %1$s option under the %2$s tab.', 'wpsso' );
						$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
						$notice_key = 'seoframework-' . $opt_key . '-option-disabled';

						$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
					}
				}
			}

			/*
			 * The SEO Framework options that must be enabled.
			 */
			foreach ( array(
				$social_gen_link => array(
					// translators: Please ignore - translation uses a different text domain.
					'social_title_rem_additions' => '<strong>' . __( 'Remove site title from generated social titles?', 'autodescription' ) . '</strong>',
				),
			) as $settings_link => $keys ) {

				foreach ( $keys as $opt_key => $label_transl ) {

					if ( empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $this->log_pre . 'seoframework ' . $opt_key . ' option is disabled' );
						}

						$notice_msg = __( 'Please enable the %1$s option under the %2$s tab.', 'wpsso' );
						$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
						$notice_key = 'seoframework-' . $opt_key . '-option-disabled';

						$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
					}
				}
			}
		}

		/*
		 * SEOPress.
		 */
		private function conflict_check_seopress() {

			if ( empty( $this->p->avail[ 'seo' ][ 'seopress' ] ) ) {

				return;
			}

			$plugin_name = __( 'SEOPress', 'wpsso' );

			$opts = get_option( 'seopress_toggle' );

			/*
			 * Check for Social Networks module.
			 */
			if ( ! empty( $opts[ 'toggle-social' ] ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl  = '<strong>' . __( 'Social Networks', 'wp-seopress' ) . '</strong>';
				$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=seopress-option' );
				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'SEO', 'wp-seopress' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Networks', 'wp-seopress' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'seopress social networks module is enabled' );
				}

				$notice_msg = __( 'Please disable the %1$s module in the %2$s settings.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
				$notice_key = 'seopress-social-networks-module-enabled';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
			}
		}

		/*
		 * SEO Ultimate.
		 */
		private function conflict_check_seoultimate() {

			if ( empty( $this->p->avail[ 'seo' ][ 'seoultimate' ] ) ) {

				return;
			}

			$plugin_name = __( 'SEO Ultimate', 'wpsso' );

			$opts = get_option( 'seo_ultimate' );

			if ( ! empty( $opts[ 'modules' ] ) && is_array( $opts[ 'modules' ] ) ) {

				/*
				 * Check for Open Graph.
				 */
				if ( array_key_exists( 'opengraph', $opts[ 'modules' ] ) && $opts[ 'modules' ][ 'opengraph' ] !== -10 ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl  = '<strong>' . __( 'Open Graph Integrator', 'seo-ultimate' ) . '</strong>';
					$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=seo' );
					$settings_link = '<a href="' . $settings_url . '">' . $plugin_name . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Modules', 'seo-ultimate' ) . '</a>';

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $this->log_pre . 'seo ultimate opengraph module is enabled' );
					}

					$notice_msg = __( 'Please disable the %1$s module in the %2$s settings.', 'wpsso' );
					$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
					$notice_key = 'seo-ultimate-opengraph-module-enabled';

					$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
				}
			}
		}

		/*
		 * Squirrly SEO.
		 */
		private function conflict_check_squirrlyseo() {

			if ( empty( $this->p->avail[ 'seo' ][ 'squirrlyseo' ] ) ) {

				return;
			}

			$plugin_name = __( 'Squirrly SEO', 'wpsso' );

			$opts = json_decode( get_option( 'sq_options' ), $assoc = true );

			/*
			 * Check for Open Graph and X (Twitter) Cards.
			 */
			$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=sq_seosettings&tab=social' );

			$settings_link = '<a href="' . $settings_url . '">' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Squirrly', 'squirrly-seo' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'SEO Settings', 'squirrly-seo' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Social Media', 'squirrly-seo' ) . '</a>';

			foreach ( array(
				// translators: Please ignore - translation uses a different text domain.
				'sq_auto_facebook' => '<strong>' . __( 'Activate Open Graph', 'squirrly-seo' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'sq_auto_twitter'  => '<strong>' . __( 'Activate Twitter Card', 'squirrly-seo' ) . '</strong>',
			) as $opt_key => $label_transl ) {

				if ( ! empty( $opts[ $opt_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $this->log_pre . 'squirrly seo ' . $opt_key . ' option is enabled' );
					}

					$notice_msg = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );
					$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
					$notice_key = 'squirrly-seo-' . $opt_key . '-option-enabled';

					$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
				}
			}

			/*
			 * Check for Knowledge Graph.
			 */
			$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=sq_seosettings&tab=jsonld' );

			$settings_link = '<a href="' . $settings_url . '">' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Squirrly', 'squirrly-seo' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'SEO Settings', 'squirrly-seo' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'JSON LD', 'squirrly-seo' ) . '</a>';

			foreach ( array(
				// translators: Please ignore - translation uses a different text domain.
				'sq_auto_jsonld' => '<strong>' . __( 'Activate JSON-LD', 'squirrly-seo' ) . '</strong>',
			) as $opt_key => $label_transl ) {

				if ( ! empty( $opts[ $opt_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $this->log_pre . 'squirrly seo ' . $opt_key . ' option is enabled' );
					}

					$notice_msg = __( 'Please disable the %1$s option in the %2$s metabox.', 'wpsso' );
					$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
					$notice_key = 'squirrly-seo-' . $opt_key . '-option-enabled';

					$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
				}
			}
		}

		/*
		 * WP Meta SEO.
		 */
		private function conflict_check_wpmetaseo() {

			if ( empty( $this->p->avail[ 'seo' ][ 'wpmetaseo' ] ) ) {

				return;
			}

			$plugin_name = __( 'WP Meta SEO', 'wpsso' );

			$opts = get_option( '_metaseo_settings' );

			if ( empty( $opts ) ) {	// Plugin settings not yet saved.

				if ( function_exists( 'wpmsGetDefaultSettings' ) ) {

					$opts = wpmsGetDefaultSettings();
				}
			}

			/*
			 * Check for Open Graph and X (Twitter) Cards.
			 */
			$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=metaseo_settings#social' );
			$settings_link = '<a href="' . $settings_url . '">' . $plugin_name . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Settings', 'wp-meta-seo' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Social', 'wp-meta-seo' ) . '</a>';

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

						$this->p->debug->log( $this->log_pre . 'wpmetaseo ' . $opt_key . ' option is not empty' );
					}

					$notice_msg = __( 'Please remove the %1$s option value in the %2$s settings.', 'wpsso' );
					$notice_msg= sprintf( $notice_msg, $label_transl, $settings_link );
					$notice_key = 'wpmetaseo-' . $opt_key . '-option-not-empty';

					$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
				}
			}

			if ( ! isset( $opts[ 'metaseo_showsocial' ] ) || ! empty( $opts[ 'metaseo_showsocial' ] ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Social sharing block', 'wp-meta-seo' ) . '</strong>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'wpmetaseo metaseo_showsocial option is enabled' );
				}

				$notice_msg = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
				$notice_key = 'wpmetaseo-showsocial-option-enabled';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
			}
		}

		/*
		 * Yoast SEO.
		 */
		private function conflict_check_wpseo() {

			if ( empty( $this->p->avail[ 'seo' ][ 'wpseo' ] ) ) {

				return;
			}

			$plugin_name = __( 'Yoast SEO', 'wpsso' );

			/*
			 * Check for minimum supported version.
			 */
			$min_version = '14.0';

			if ( ! defined( 'WPSEO_VERSION' ) || version_compare( WPSEO_VERSION, $min_version, '<' ) ) {

				$notice_msg = __( 'The %1$s plugin is too old - please update the %1$s plugin to version %2$s or newer.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $plugin_name, $min_version );
				$notice_key = 'wpseo-version-' . WPSEO_VERSION . '-too-old';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );

				return;
			}

			$opts        = get_option( 'wpseo' );
			$opts_social = get_option( 'wpseo_social' );

			/*
			 * Check for Open Graph.
			 */
			if ( ! empty( $opts_social[ 'opengraph' ] ) ) {

				if ( version_compare( WPSEO_VERSION, 20.0, '>=' ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl  = '<strong>' . __( 'Open Graph data', 'wordpress-seo' ) . '</strong>';
					$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_page_settings#/site-features' );
					$settings_link = '<a href="' . $settings_url . '" onclick="window.location.reload();">' . $plugin_name . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Settings', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Site features', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Social sharing', 'wordpress-seo' ) . '</a>';

				} else {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl  = '<strong>' . __( 'Add Open Graph meta data', 'wordpress-seo' ) . '</strong>';
					$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_social#top#facebook' );
					$settings_link = '<a href="' . $settings_url . '" onclick="window.location.reload();">' . $plugin_name . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Facebook', 'wordpress-seo' ) . '</a>';
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'wpseo opengraph option is enabled' );
				}

				$notice_msg = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
				$notice_key = 'wpseo-opengraph-option-enabled';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
			}

			/*
			 * Check for X (Twitter) Cards.
			 */
			if ( ! empty( $opts_social[ 'twitter' ] ) ) {

				if ( version_compare( WPSEO_VERSION, 20.0, '>=' ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl  = '<strong>' . __( 'Twitter card data', 'wordpress-seo' ) . '</strong>';
					$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_page_settings#/site-features' );
					$settings_link = '<a href="' . $settings_url . '" onclick="window.location.reload();">' . $plugin_name . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Settings', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Site features', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Social sharing', 'wordpress-seo' ) . '</a>';

				} else {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl  = '<strong>' . __( 'Add Twitter Card meta data', 'wordpress-seo' ) . '</strong>';
					$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_social#top#twitterbox' );
					$settings_link = '<a href="' . $settings_url . '" onclick="window.location.reload();">' . $plugin_name . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Twitter', 'wordpress-seo' ) . '</a>';
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'wpseo twitter option is enabled' );
				}

				$notice_msg = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
				$notice_key = 'wpseo-twitter-option-enabled';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
			}

			/*
			 * Check for Slack.
			 */
			if ( ! empty( $opts[ 'enable_enhanced_slack_sharing' ] ) ) {

				if ( version_compare( WPSEO_VERSION, 20.0, '>=' ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl  = '<strong>' . __( 'Slack sharing', 'wordpress-seo' ) . '</strong>';
					$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_page_settings#/site-features' );
					$settings_link = '<a href="' . $settings_url . '" onclick="window.location.reload();">' . $plugin_name . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Settings', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Site features', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Social sharing', 'wordpress-seo' ) . '</a>';

				} else {

					// translators: Please ignore - translation uses a different text domain.
					$label_transl  = '<strong>' . __( 'Enhanced Slack sharing', 'wordpress-seo' ) . '</strong>';
					$settings_url  = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_dashboard#top#features' );
					$settings_link = '<a href="' . $settings_url . '" onclick="window.location.reload();">' . $plugin_name . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'General', 'wordpress-seo' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Features', 'wordpress-seo' ) . '</a>';
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'wpseo slack option is enabled' );
				}

				$notice_msg = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );
				$notice_msg = sprintf( $notice_msg, $label_transl, $settings_link );
				$notice_key = 'wpseo-slack-option-enabled';

				$this->p->notice->err( $this->notice_pre . $notice_msg, null, $notice_key );
			}
		}

		/*
		 * Yoast WooCommerce SEO.
		 */
		private function conflict_check_wpseo_wc() {

			if ( empty( $this->p->avail[ 'seo' ][ 'wpseo-wc' ] ) ) {

				return;

			} elseif ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				return;
			}

			$pkg_info       = $this->p->util->get_pkg_info();	// Uses a local cache.
			$wpseo_wc_label = 'Yoast WooCommerce SEO';
			$plugins_url    = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( $blog_id = null, 'plugins.php' );
			$plugins_url    = add_query_arg( array( 's' => 'yoast seo' ), $plugins_url );

			$notice_msg = sprintf( __( 'The %1$s plugin provides much better Schema markup for WooCommerce products than the %2$s plugin.', 'wpsso' ), $pkg_info[ 'wpsso' ][ 'short' ], $wpseo_wc_label ) . ' ';

			$notice_msg .= sprintf( __( 'There is absolutely no advantage in continuing to use the %1$s plugin.', 'wpsso' ), $wpseo_wc_label ) . ' ';

			$notice_msg .= sprintf( __( 'To avoid adding incorrect and confusing Schema markup to your webpages, <a href="%1$s">please deactivate the %2$s plugin immediately</a>.' ), $plugins_url, $wpseo_wc_label );

			$notice_key = 'deactivate-wpseo-woocommerce';

			$this->p->notice->err( $notice_msg, null, $notice_key );
		}
	}
}
