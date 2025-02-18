<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegLangQtranslateXt' ) ) {

	class WpssoIntegLangQtranslateXt {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			global $wp_version;

			$wp_min_version = '6.2';

			if ( version_compare( $wp_version, $wp_min_version, '<' ) ) {

				$notice_msg = sprintf( __( 'The qTranslate-XT integration module requires %1$s version %2$s or better.',
					'wpsso' ), 'WordPress', $wp_min_version ) . ' ';

				$notice_msg .= sprintf( __( 'Please update to the latest %1$s version (or at least version %2$s).',
					'wpsso' ), 'WordPress', $wp_min_version ) . ' ';

				$notice_key = 'notice-recommend-version-wp-' . $wp_min_version;

				$this->p->notice->err( $notice_msg, $user_id = null, $notice_key );
			}

			/*
			 * Include front-end functions like qtranxf_translate_post().
			 */
			require_once QTRANSLATE_DIR . '/src/frontend.php';

			add_action( 'change_locale', array( $this, 'locale_changed' ), -1000, 1 );

			$this->p->util->add_plugin_filters( $this, array(
				'convert_text' => array(
					'product_title' => 1,
				),
				'convert_url' => array(
					'canonical_url' => 1,
					'oembed_url'    => 1,
					'product_url'   => 1,
				),
				'post_public_ids_posts_args'       => 1,
				'post_public_ids_suppress_filters' => '__return_false',
				'the_content_filter_content'       => '__return_true',
				'the_excerpt_filter_excerpt'       => '__return_true',
			) );

			$this->p->util->add_plugin_filters( $this, array(
				'convert_url' => array(
					'get_home_url' => 1,
				),
				'available_locales' => 1,
				'get_post_object'   => 2,
			), $prio = 100, $ext = 'sucom' );
		}

		/*
		 * The qtranxf_localeForCurrentLanguage() function uses a static cache variable and does not recognize locale
		 * changes. If the locale changes, then we must to unhook the 'qtranxf_localeForCurrentLanguage' filter to get the
		 * the new locale from get_locale().
		 */
		public function locale_changed( $locale ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			remove_filter( 'locale', 'qtranxf_localeForCurrentLanguage', 99 );
		}

		public function filter_convert_text( $text ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$locale = SucomUtilWP::get_locale();

			if ( $lang = qtranxf_match_language_locale( $locale ) ) {

				$text = qtranxf_use_language( $lang, $text, $show_available = false, $show_empty = false );
			}

			return $text;
		}

		public function filter_convert_url( $url ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$locale = SucomUtilWP::get_locale();

			if ( $lang = qtranxf_match_language_locale( $locale ) ) {

				$url = qtranxf_get_url_for_language( $url, $lang, $showLanguage = true );
			}

			return $url;
		}

		public function filter_post_public_ids_posts_args( $posts_args ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			global $q_config;

			if ( ! empty( $q_config[ 'hide_untranslated' ] ) ) {

				$locale = SucomUtilWP::get_locale();

				if ( $lang = qtranxf_match_language_locale( $locale ) ) {

					$posts_args[ 's' ]              = '[:' . $lang . ']';
					$posts_args[ 'search_columns' ] = array( 'post_title', 'post_excerpt', 'post_content' );	// Since WP v6.2.
				}
			}

			return $posts_args;
		}

		/*
		 * Remove locales that are not configured.
		 */
		public function filter_available_locales( $locales ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			foreach ( $locales as $num => $locale ) {

				if ( ! $lang = qtranxf_match_language_locale( $locale ) ) {

					unset( $locales[ $num ] );
				}
			}

			return $locales;
		}

		public function filter_get_post_object( $post_object, $use_post ) {

			if ( is_object( $post_object ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( get_class( $post_object ) . ' object use_post = ' . SucomUtil::get_use_post_string( $use_post ) );
				}

				if ( $post_object instanceof WP_Post ) {

					$locale = SucomUtilWP::get_locale();

					if ( $lang = qtranxf_match_language_locale( $locale ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'calling qtranxf_translate_post() for ' .$lang . ' language' );
						}

						qtranxf_translate_post( $post_object, $lang );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping qtranxf_translate_post() - no language for ' . $locale . ' locale' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping qtranxf_translate_post() - object not an instance of WP_Post' );
				}
			}

			return $post_object;
		}
	}
}
