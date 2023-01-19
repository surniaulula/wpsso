<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegLangPolylang' ) ) {

	class WpssoIntegLangPolylang {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'home_url'            => 2,
				'sitemaps_alternates' => 2,
			) );

			$this->p->util->add_plugin_filters( $this, array(
				'available_feed_locale_names' => 1,
				'get_locale'                  => 2,
			), $prio = 200, $ext = 'sucom' );
		}

		public function filter_home_url( $url, $mod ) {

			if ( function_exists( 'pll_home_url' ) ) {	// Just in case.

				return pll_home_url();
			}

			return $url;
		}

		public function filter_sitemaps_alternates( $alternates, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] ) {

				$translations = pll_get_post_translations( $mod[ 'id' ] );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'translations', $translations );
				}

				foreach ( $translations as $lang_code => $post_id )  {

					$transl_mod = $this->p->post->get_mod( $post_id );

					$alternates[] = array(
						'href'     => $this->p->util->get_canonical_url( $transl_mod ),
						'hreflang' => $this->p->schema->get_lang( $transl_mod ),
					);
				}

			} elseif ( $mod[ 'is_term' ] ) {

				$translations = pll_get_term_translations( $mod[ 'id' ] );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'translations', $translations );
				}

				foreach ( $translations as $lang_code => $term_id )  {

					$transl_mod = $this->p->term->get_mod( $term_id );

					$alternates[] = array(
						'href'     => $this->p->util->get_canonical_url( $transl_mod ),
						'hreflang' => $this->p->schema->get_lang( $transl_mod ),
					);
				}
			}

			return $alternates;
		}

		public function filter_available_feed_locale_names( $locale_names ) {

			$languages = pll_languages_list( array( 'fields' => array() ) );

			$locale_names = array();

			foreach ( $languages as $lang_obj ) {

				$locale_names[ $lang_obj->locale ] = $lang_obj->name;
			}

			return $locale_names;
		}

		/**
		 * Argument can also be a numeric post ID, to return the language of that post.
		 */
		public function filter_get_locale( $wp_locale, $mixed = 'current' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'wp locale = ' . $wp_locale );
			}

			$pll_locale = false;

			switch ( true ) {

				case ( is_array( $mixed ) ):

					if ( $mixed[ 'id' ] > 0 ) {	// Just in case.

						if ( $mixed[ 'is_post' ] ) {

							$pll_locale = $this->get_post_language( $mixed[ 'id' ] );

						} elseif ( $mixed[ 'is_term' ] ) {

							$pll_locale = $this->get_term_language( $mixed[ 'id' ] );
						}

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'pll_locale for ' . $mixed[ 'name' ] . ' id ' . $mixed[ 'id' ] . ' = ' . $pll_locale );
						}
					}

					break;

				case ( is_numeric( $mixed ) ):

					if ( $mixed > 0 ) {	// Just in case.

						$pll_locale = $this->get_post_language( $mixed );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'pll_locale for post ID ' . $mixed . ' = ' . $pll_locale );
						}
					}

					break;

				case ( 'default' === $mixed ):

					if ( function_exists( 'pll_default_language' ) ) {

						$pll_locale = pll_default_language( 'locale' );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'pll_locale for default = ' . $pll_locale );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'pll_default_language function not found' );
					}

					break;

				case ( 'current' === $mixed ):

					if ( function_exists( 'pll_current_language' ) ) {

						$pll_locale = pll_current_language( 'locale' );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'pll_locale for current = ' . $pll_locale );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'pll_current_language function not found' );
					}

					break;
			}

			return $pll_locale ? $pll_locale : $wp_locale;
		}

		private function get_post_language( $post_id ) {

			$pll_locale = '';

			if ( function_exists( 'pll_get_post_language' ) ) {	// Since PLL v1.5.4.

				$pll_locale = pll_get_post_language( $post_id, 'locale' );

			} else {

				global $polylang;

				if ( isset( $polylang->model->post ) ) {

					$pll_lang = $polylang->model->post->get_language( $post_id );

				} elseif ( isset( $polylang->model ) ) {

					$pll_lang = $polylang->model->get_post_language( $post_id );
				}

				if ( ! empty( $pll_lang ) ) {

					$pll_locale = $pll_lang->locale;
				}
			}

			return $pll_locale;
		}

		private function get_term_language( $term_id ) {

			$pll_locale = '';

			if ( function_exists( 'pll_get_term_language' ) ) {	// Since PLL v1.5.4.

				$pll_locale = pll_get_term_language( $term_id, 'locale' );

			} else {

				global $polylang;

				if ( isset( $polylang->model->term ) ) {

					$pll_lang = $polylang->model->term->get_language( $term_id );

				} elseif ( isset( $polylang->model ) ) {

					$pll_lang = $polylang->model->get_term_language( $term_id );
				}

				if ( ! empty( $pll_lang ) ) {

					$pll_locale = $pll_lang->locale;
				}
			}

			return $pll_locale;
		}
	}
}
