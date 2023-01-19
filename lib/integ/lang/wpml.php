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

if ( ! class_exists( 'WpssoIntegLangWpml' ) ) {

	class WpssoIntegLangWpml {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'change_locale', array( $this, 'wp_locale_changed' ), -200, 1 );

			$this->p->util->add_plugin_filters( $this, array(
				'sitemaps_alternates' => 2,
			) );

			$this->p->util->add_plugin_filters( $this, array(
				'available_feed_locale_names' => 1,
				'get_locale'                  => 2,
			), $prio = 1000, $ext = 'sucom' );
		}

		/**
		 * Check that the active WPML language matches the changed WordPress locale.
		 */
		public function wp_locale_changed( $wp_locale ) {

			$wpml_locale = $this->get_active_locale();	// Get the active WPML locale.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'wp_locale = ' . $wp_locale );
				$this->p->debug->log( 'wpml_locale = ' . $wpml_locale );
			}

			if ( $wpml_locale && $wpml_locale !== $wp_locale ) {	// Just in case.

				$active_languages = $this->get_active_languages();	// Uses a local cache.

				if ( is_array( $active_languages ) ) {	// Just in case.

					foreach ( $active_languages as $wpml_code => $lang ) {

						if ( ! empty( $lang[ 'default_locale' ] ) ) {	// Just in case.

							if ( $wp_locale === $lang[ 'default_locale' ] ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'switching to wpml_code = ' . $wpml_code );
								}

								do_action( 'wpml_switch_language', $wpml_code );

								return;	// Stop here.
							}
						}
					}
				}
			}
		}

		public function filter_sitemaps_alternates( $alternates, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$element_type = null;

			if ( $mod[ 'is_post' ] ) {

				$element_type = 'post_' . $mod[ 'post_type' ];

			} elseif ( $mod[ 'is_term' ] ) {

				$element_type = 'tax_' . $mod[ 'tax_slug' ];
			}

			if ( $element_type ) {

				/**
				 * See https://wpml.org/wpml-hook/wpml_element_trid/.
				 */
				$element_trid = apply_filters( 'wpml_element_trid', null, $mod[ 'id' ], $element_type );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'element trid = ', $element_trid );
				}

				/**
				 * See https://wpml.org/wpml-hook/wpml_get_element_translations/.
				 */
				$translations = apply_filters( 'wpml_get_element_translations', null, $element_trid );

				if ( empty( $translations ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'returned element translations is empty' );
					}

				} else {

					$active_code = $this->get_active_code();
					$last_code   = $active_code;

					foreach ( $translations as $wpml_code => $transl_obj )  {

						if ( empty( $transl_obj->element_id ) ) {	// Just in case.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipping translation ' . $wpml_code . ': object element id is empty' );

								$this->p->debug->log_arr( 'transl_obj', $transl_obj );	// Logs the object variables.
							}

							continue;
						}

						if ( $this->p->debug->enabled ) {

							$this->p->debug->mark( 'getting alternate array for ' . $wpml_code );	// Begin timer.

							$this->p->debug->log_arr( 'transl_obj', $transl_obj );	// Logs the object variables.
						}

						if ( $wpml_code !== $last_code ) {	// Just in case.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'switching to wpml_code = ' . $wpml_code );
							}

							do_action( 'wpml_switch_language', $wpml_code );

							$last_code = $wpml_code;
						}

						$transl_mod = $mod[ 'obj' ]->get_mod( $transl_obj->element_id );

						$transl_mod[ 'wpml_code' ] = $wpml_code;	// Optimize.

						$alternates[] = array(
							'href'     => $this->p->util->get_canonical_url( $transl_mod ),
							'hreflang' => $this->p->schema->get_lang( $transl_mod ),
						);

						if ( $this->p->debug->enabled ) {

							$this->p->debug->mark( 'getting alternate array for ' . $wpml_code );	// End timer.
						}
					}

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'switching back to wpml_code = ' . $active_code );
					}

					do_action( 'wpml_switch_language', $active_code );
				}
			}

			return $alternates;
		}

		public function filter_available_feed_locale_names( $locale_names ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$locale_names     = array();
			$active_languages = $this->get_active_languages();	// Uses a local cache.

			if ( is_array( $active_languages ) ) {	// Just in case.

				foreach ( $active_languages as $wpml_code => $lang ) {

					if ( ! empty( $lang[ 'default_locale' ] ) ) {	// Just in case.

						$locale_names[ $lang[ 'default_locale' ] ] = $lang[ 'native_name' ];
					}
				}
			}

			return $locale_names;
		}

		/**
		 * Argument can also be a numeric post ID, to return the language of that post.
		 */
		public function filter_get_locale( $locale, $mixed = 'current' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$wpml_locale = false;

			switch ( true ) {

				case ( is_array( $mixed ) ):	// $mod array.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting wpml_locale for array' );
					}

					if ( isset( $mixed[ 'id' ] ) && $mixed[ 'id' ] > 0 ) {	// Just in case.

						$wpml_locale = $this->get_mod_locale( $mixed );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'wpml_locale ' . $mixed[ 'name' ] . ' id ' . $mixed[ 'id' ] . ' = ' . $wpml_locale );
						}
					}

					break;

				case ( is_numeric( $mixed ) ) :	// Post ID.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting wpml_locale for post id' );
					}

					if ( $mixed > 0 ) {	// Just in case.

						$wpml_locale = $this->get_post_locale( $mixed );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'wpml_locale post id ' . $mixed . ' = ' . $wpml_locale );
						}
					}

					break;

				case ( 'default' === $mixed ):	// Noting to do.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping wpml_locale for default' );
					}

					break;

				case ( 'current' === $mixed ):	// Current / active locale.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting wpml_locale for current' );
					}

					$wpml_locale = $this->get_active_locale();	// Get the active WPML locale.

					break;

				default:	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'unrecognized wpml_locale request = ' . print_r( $mixed, true ) );
					}

					break;
			}

			if ( $wpml_locale ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning wpml_locale = ' . $wpml_locale );
				}

				return $wpml_locale;	// Stop here.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning locale = ' . $locale );
			}

			return $locale;	// No change.
		}

		private function get_active_code() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$active_languages = $this->get_active_languages();	// Uses a local cache.

			if ( is_array( $active_languages ) ) {	// Just in case.

				foreach ( $active_languages as $wpml_code => $lang ) {

					if ( ! empty( $lang[ 'active' ] ) ) {	// Is the current language.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'active wpml_code = ' . $wpml_code );
						}

						return $wpml_code;
					}
				}
			}

			return false;
		}

		private function get_active_languages() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return apply_filters( 'wpml_active_languages', null );
		}

		private function get_active_locale() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$active_languages = $this->get_active_languages();	// Uses a local cache.

			if ( is_array( $active_languages ) ) {	// Just in case.

				foreach ( $active_languages as $wpml_code => $lang ) {

					if ( ! empty( $lang[ 'active' ] ) ) {	// Is the current language.

						if ( ! empty( $lang[ 'default_locale' ] ) ) {	// Just in case.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'wpml_locale current = ' . $lang[ 'default_locale' ] );
							}

							return $lang[ 'default_locale' ];
						}

						return false;
					}
				}
			}

			return false;
		}

		private function get_post_locale( $post_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * See https://wpml.org/wpml-hook/wpml_post_language_details/.
			 */
			$post_details = apply_filters( 'wpml_post_language_details', null, $post_id );

			if ( is_wp_error( $post_details ) ) {

				$error_pre = sprintf( '%s error:', __METHOD__ );
				$error_msg = 'wpml_post_language_details error: ' . $post_details->get_error_message();

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $error_msg );
				}

				$this->p->notice->err( $error_msg );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

			} elseif ( is_array( $post_details ) ) {	// Just in case.

				if ( ! empty( $post_details[ 'locale' ] ) ) {

					return $post_details[ 'locale' ];
				}
			}

			return false;
		}

		private function get_mod_locale( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$wpml_locale = false;
			$wpml_code   = $this->get_mod_code( $mod );

			if ( $wpml_code ) {

				$active_languages = $this->get_active_languages();	// Uses a local cache.

				if ( ! empty( $active_languages[ $wpml_code ][ 'default_locale' ] ) ) {

					return $active_languages[ $wpml_code ][ 'default_locale' ];
				}
			}

			return false;
		}

		private function get_mod_code( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'wpml_code' ] ) {	// Set in filter_sitemaps_alternates().

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'module wpml_code = ' . $mod[ 'wpml_code' ] );
				}

				return $mod[ 'wpml_code' ];
			}

			$args      = null;
			$wpml_code = null;

			if ( $mod[ 'is_post' ] ) {

				$args = array( 'element_id' => $mod[ 'id' ], 'element_type' => $mod[ 'post_type' ] );

			} elseif ( $mod[ 'is_term' ] ) {

				$args = array( 'element_id' => $mod[ 'id' ], 'element_type' => $mod[ 'tax_slug' ] );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'args', $args );
			}

			if ( $args ) {

				/**
				 * See https://wpml.org/wpml-hook/wpml_element_language_code/.
				 */
				$wpml_code = apply_filters( 'wpml_element_language_code', null, $args );

				if ( is_wp_error( $wpml_code ) ) {

					$error_pre = sprintf( '%s error:', __METHOD__ );
					$error_msg = 'wpml_element_language_code error: ' . $wpml_code->get_error_message();

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $error_msg );
					}

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wpml_code = ' . $wpml_code );
					}

					return $wpml_code;
				}
			}

			return false;
		}
	}
}
