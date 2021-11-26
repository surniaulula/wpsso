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

if ( ! class_exists( 'WpssoConflictSeo' ) ) {

	class WpssoConflictSeo {

		private $p;	// Wpsso class object.

		private $log_pre    = '';
		private $notice_pre = '';

		/**
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

			$this->log_pre = 'seo plugin conflict detected - ';

			$this->notice_pre =  __( 'Plugin conflict detected:', 'wpsso' ) . ' ';

			$this->conflict_check_aioseop();	// All in One SEO Pack.
			$this->conflict_check_seoframework();	// The SEO Framework.
			$this->conflict_check_seopress();	// SEOPress.
			$this->conflict_check_seoultimate();	// SEO Ultimate.
			$this->conflict_check_squirrlyseo();	// Squirrly SEO.
			$this->conflict_check_wpmetaseo();	// WP Meta SEO.
			$this->conflict_check_wpseo();		// Yoast SEO.
			$this->conflict_check_wpseo_wc();	// Yoast WooCommerce SEO.
		}

		/**
		 * All in One SEO Pack.
		 */
		private function conflict_check_aioseop() {

			if ( empty( $this->p->avail[ 'seo' ][ 'aioseop' ] ) ) {

				return;
			}

			/**
			 * Check for minimum supported version.
			 */
			$min_version = '4.0.16';

			if ( ! defined( 'AIOSEO_VERSION' ) || version_compare( AIOSEO_VERSION, $min_version, '<' ) ) {

				$notice_msg_transl = __( 'The %1$s plugin is too old - please update the %1$s plugin to version %2$s or newer.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, __( 'All in One SEO', 'all-in-one-seo-pack' ), $min_version ) );

				return;
			}

			/**
			 * Check for Open Graph.
			 */
			if ( aioseo()->options->social->facebook->general->enable ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Enable Open Graph Markup', 'all-in-one-seo-pack' ) . '</strong>';

				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=aioseo-social-networks#/facebook' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'All in One SEO', 'all-in-one-seo-pack' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Networks', 'all-in-one-seo-pack' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Facebook', 'all-in-one-seo-pack' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'aioseop open graph markup is enabled' );
				}

				$notice_msg_transl = __( 'Please uncheck the %1$s option in the %2$s settings.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}

			/**
			 * Check for Twitter.
			 */
			if ( aioseo()->options->social->twitter->general->enable ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Enable Twitter Card', 'all-in-one-seo-pack' ) . '</strong>';

				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=aioseo-social-networks#/twitter' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'All in One SEO', 'all-in-one-seo-pack' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Networks', 'all-in-one-seo-pack' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Twitter', 'all-in-one-seo-pack' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'aioseop twitter card is enabled' );
				}

				$notice_msg_transl = __( 'Please uncheck the %1$s option in the %2$s settings.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}
		}

		/**
		 * The SEO Framework.
		 */
		private function conflict_check_seoframework() {

			if ( empty( $this->p->avail[ 'seo' ][ 'seoframework' ] ) ) {

				return;
			}

			$tsf = the_seo_framework();

			$opts = $tsf->get_all_options();

			/**
			 * Check for Open Graph and Twitter Cards.
			 */
			$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=theseoframework-settings' );

			$settings_link = '<a href="' . $settings_url . '">' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'The SEO Framework', 'autodescription' ) . ' &gt; ' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'Social Meta Settings', 'autodescription' ) . '</a>';

			// translators: Please ignore - translation uses a different text domain.
			$posts_i18n = __( 'posts', 'autodescription' );

			foreach ( array(
				// translators: Please ignore - translation uses a different text domain.
				'og_tags'           => '<strong>' . __( 'Output Open Graph meta tags?', 'autodescription' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'facebook_tags'     => '<strong>' . __( 'Output Facebook meta tags?', 'autodescription' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'twitter_tags'      => '<strong>' . __( 'Output Twitter meta tags?', 'autodescription' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'oembed_scripts'    => '<strong>' . __( 'Output oEmbed scripts?', 'autodescription' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'post_publish_time' => '<strong>' . sprintf( __( 'Add %1$s to %2$s?', 'autodescription' ),
					'article:published_time', $posts_i18n ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'post_modify_time'  => '<strong>' . sprintf( __( 'Add %1$s to %2$s?', 'autodescription' ),
					'article:modified_time', $posts_i18n ) . '</strong>',
			) as $opt_key => $label_transl ) {

				if ( ! empty( $opts[ $opt_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $this->log_pre . 'seoframework ' . $opt_key . ' option is checked' );
					}

					$notice_msg_transl = __( 'Please uncheck the %1$s option in the %2$s metabox.', 'wpsso' );

					$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
				}
			}

			/**
			 * Check for Knowledge Graph.
			 */
			if ( ! empty( $opts[ 'knowledge_output' ] ) ) {

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'The SEO Framework', 'autodescription' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Schema Settings', 'autodescription' ) . '</a>';

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Output Authorized Presence?', 'autodescription' ) . '</strong>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'seoframework knowledge_output option is checked' );
				}

				$notice_msg_transl = __( 'Please uncheck the %1$s option in the %2$s metabox.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}
		}

		/**
		 * SEOPress.
		 */
		private function conflict_check_seopress() {

			if ( empty( $this->p->avail[ 'seo' ][ 'seopress' ] ) ) {

				return;
			}

			$opts = get_option( 'seopress_toggle' );

			/**
			 * Check for Social Networks module.
			 */
			if ( ! empty( $opts[ 'toggle-social' ] ) ) {

				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=seopress-option' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'SEO', 'wp-seopress' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Social Networks', 'wp-seopress' ) . '</a>';

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Social Networks', 'wp-seopress' ) . '</strong>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'seopress social networks module is enabled' );
				}

				$notice_msg_transl = __( 'Please disable the %1$s module in the %2$s settings.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}
		}

		/**
		 * SEO Ultimate.
		 */
		private function conflict_check_seoultimate() {

			if ( empty( $this->p->avail[ 'seo' ][ 'seoultimate' ] ) ) {

				return;
			}

			$opts = get_option( 'seo_ultimate' );

			if ( ! empty( $opts[ 'modules' ] ) && is_array( $opts[ 'modules' ] ) ) {

				/**
				 * Check for Open Graph.
				 */
				if ( array_key_exists( 'opengraph', $opts[ 'modules' ] ) && $opts[ 'modules' ][ 'opengraph' ] !== -10 ) {

					$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=seo' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'SEO Ultimate', 'seo-ultimate' ) . ' &gt; ' .
						// translators: Please ignore - translation uses a different text domain.
						__( 'Modules', 'seo-ultimate' ) . '</a>';

					// translators: Please ignore - translation uses a different text domain.
					$label_transl = '<strong>' . __( 'Open Graph Integrator', 'seo-ultimate' ) . '</strong>';

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $this->log_pre . 'seo ultimate opengraph module is enabled' );
					}

					$notice_msg_transl = __( 'Please disable the %1$s module in the %2$s settings.', 'wpsso' );

					$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
				}
			}
		}

		/**
		 * Squirrly SEO.
		 */
		private function conflict_check_squirrlyseo() {

			if ( empty( $this->p->avail[ 'seo' ][ 'squirrlyseo' ] ) ) {

				return;
			}

			$opts = json_decode( get_option( 'sq_options' ), $assoc = true );

			/**
			 * Check for Open Graph and Twitter Cards.
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

					$notice_msg_transl = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );

					$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
				}
			}

			/**
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

					$notice_msg_transl = __( 'Please disable the %1$s option in the %2$s metabox.', 'wpsso' );

					$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
				}
			}
		}

		/**
		 * WP Meta SEO.
		 */
		private function conflict_check_wpmetaseo() {

			if ( empty( $this->p->avail[ 'seo' ][ 'wpmetaseo' ] ) ) {

				return;
			}

			$opts = get_option( '_metaseo_settings' );

			if ( empty( $opts ) ) {	// Plugin settings not yet saved.

				if ( function_exists( 'wpmsGetDefaultSettings' ) ) {

					$opts = wpmsGetDefaultSettings();
				}
			}

			/**
			 * Check for Open Graph and Twitter Cards.
			 */
			$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=metaseo_settings#social' );

			$settings_link = '<a href="' . $settings_url . '">' .
				// translators: Please ignore - translation uses a different text domain.
				__( 'WP Meta SEO', 'wp-meta-seo' ) . ' &gt; ' .
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

					$notice_msg_transl = __( 'Please remove the %1$s option value in the %2$s settings.', 'wpsso' );

					$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
				}
			}

			if ( ! empty( $opts[ 'metaseo_showsocial' ] ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Social sharing block', 'wp-meta-seo' ) . '</strong>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'wpmetaseo metaseo_showsocial option is enabled' );
				}

				$notice_msg_transl = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}
		}

		/**
		 * Yoast SEO.
		 */
		private function conflict_check_wpseo() {

			if ( empty( $this->p->avail[ 'seo' ][ 'wpseo' ] ) ) {

				return;
			}

			/**
			 * Check for minimum supported version.
			 */
			$min_version = '14.0';

			if ( ! defined( 'WPSEO_VERSION' ) || version_compare( WPSEO_VERSION, $min_version, '<' ) ) {

				$notice_msg_transl = __( 'The %1$s plugin is too old - please update the %1$s plugin to version %2$s or newer.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, __( 'Yoast SEO', 'wordpress-seo' ), $min_version ) );

				return;
			}

			$opts = get_option( 'wpseo' );

			$opts_social = get_option( 'wpseo_social' );

			/**
			 * Check for Social page URLs.
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
				'facebook_site' => '<strong>' . __( 'Facebook Page URL', 'wordpress-seo' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'twitter_site'  => '<strong>' . __( 'Twitter Username', 'wordpress-seo' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'instagram_url' => '<strong>' . __( 'Instagram URL', 'wordpress-seo' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'linkedin_url'  => '<strong>' . __( 'LinkedIn URL', 'wordpress-seo' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'myspace_url'   => '<strong>' . __( 'MySpace URL', 'wordpress-seo' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'pinterest_url' => '<strong>' . __( 'Pinterest URL', 'wordpress-seo' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'youtube_url'   => '<strong>' . __( 'YouTube URL', 'wordpress-seo' ) . '</strong>',
				// translators: Please ignore - translation uses a different text domain.
				'wikipedia_url' => '<strong>' . __( 'Wikipedia URL', 'wordpress-seo' ) . '</strong>',
			) as $opt_key => $label_transl ) {

				if ( ! empty( $opts_social[ $opt_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $this->log_pre . 'wpseo ' . $opt_key . ' option is not empty' );
					}

					$notice_msg_transl = __( 'Please remove the %1$s option value in the %2$s settings.', 'wpsso' );

					$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
				}
			}

			/**
			 * Check for Facebook App ID.
			 */
			if ( ! empty( $opts_social[ 'fbadminapp' ] ) ) {

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

					$this->p->debug->log( $this->log_pre . 'wpseo fbadminapp option is not empty' );
				}

				$notice_msg_transl = __( 'Please remove the %1$s option value in the %2$s settings.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}

			/**
			 * Check for Open Graph.
			 */
			if ( ! empty( $opts_social[ 'opengraph' ] ) ) {

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

					$this->p->debug->log( $this->log_pre . 'wpseo opengraph option is enabled' );
				}

				$notice_msg_transl = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}

			/**
			 * Check for Twitter Cards.
			 */
			if ( ! empty( $opts_social[ 'twitter' ] ) ) {

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

					$this->p->debug->log( $this->log_pre . 'wpseo twitter option is enabled' );
				}

				$notice_msg_transl = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}

			/**
			 * Check for Slack.
			 */
			if ( ! empty( $opts[ 'enable_enhanced_slack_sharing' ] ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$label_transl = '<strong>' . __( 'Enhanced Slack sharing', 'wordpress-seo' ) . '</strong>';

				$settings_url = get_admin_url( $blog_id = null, 'admin.php?page=wpseo_dashboard#top#features' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'General', 'wordpress-seo' ) . ' &gt; ' .
					// translators: Please ignore - translation uses a different text domain.
					__( 'Features', 'wordpress-seo' ) . '</a>';

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $this->log_pre . 'wpseo slack option is enabled' );
				}

				$notice_msg_transl = __( 'Please disable the %1$s option in the %2$s settings.', 'wpsso' );

				$this->p->notice->err( $this->notice_pre . sprintf( $notice_msg_transl, $label_transl, $settings_link ) );
			}
		}

		/**
		 * Yoast WooCommerce SEO.
		 */
		private function conflict_check_wpseo_wc() {

			if ( empty( $this->p->avail[ 'seo' ][ 'wpseo-wc' ] ) ) {

				return;

			} elseif ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				return;
			}

			$pkg_info = $this->p->admin->get_pkg_info();	// Returns an array from cache.

			$wpseo_wc_label = 'Yoast WooCommerce SEO';

			if ( ! empty( $pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

				$plugins_url = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( $blog_id = null, 'plugins.php' );
				$plugins_url = add_query_arg( array( 's' => 'yoast seo' ), $plugins_url );

				$notice_msg = sprintf( __( 'The %1$s plugin provides much better Schema markup for WooCommerce products than the %2$s plugin.', 'wpsso' ), $pkg_info[ 'wpsso' ][ 'short_pro' ], $wpseo_wc_label ) . ' ';

				$notice_msg .= sprintf( __( 'There is absolutely no advantage in continuing to use the %1$s plugin.', 'wpsso' ), $wpseo_wc_label ) . ' ';

				$notice_msg .= sprintf( __( 'To avoid adding incorrect and confusing Schema markup to your webpages, <a href="%1$s">please deactivate the %2$s plugin immediately</a>.' ), $plugins_url, $wpseo_wc_label );

				$notice_key = 'deactivate-wpseo-woocommerce';

				$this->p->notice->err( $notice_msg, null, $notice_key );
			}
		}
	}
}
