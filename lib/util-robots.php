<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'SucomUtilRobots' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/com/util-robots.php';
}

if ( ! class_exists( 'WpssoUtilRobots' ) ) {

	class WpssoUtilRobots extends SucomUtilRobots {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		/**
		 * See https://developers.google.com/search/reference/robots_meta_tag.
		 */
		public function get_content( array $mod ) {

			$directives = $this->get_directives( $mod );

			$content = '';

			foreach ( $directives as $directive_key => $directive_value ) {

				if ( false === $directive_value ) {		// Nothing to do.

					continue;

				} elseif ( true === $directive_value ) {	// Add the directive.

					$content .= $directive_key . ', ';	// index, follow, etc.

				} else {					// Add the directive and its value.

					$content .= $directive_key . ':' . $directive_value . ', ';
				}
			}

			$content = trim( $content, ', ' );

			return apply_filters( 'wpsso_robots_content', $content, $mod, $directives );
		}

		public function get_directives( array $mod ) {

			$md_opts = array();

			$directives = self::get_default_directives();

			/**
			 * Ignore custom options if the robots meta tag is disabled.
			 */
			if ( $this->is_enabled() ) {

				if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

					$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );
				}
			}

			foreach ( $directives as $directive_key => $default_value ) {

				$value = null;

				$opt_key = str_replace( '-', '_', 'robots_' . $directive_key );	// Convert dashes to underscores.

				if ( isset( $md_opts[ $opt_key ] ) ) {

					$value = $md_opts[ $opt_key ];
				}

				/**
				 * Fallback to a default value.
				 */
				if ( null === $value ) {

					/**
					 * Get the default value from the plugin settings for these options:
					 *
					 *	'robots_max_snippet'       => -1,
					 *	'robots_max_image_preview' => 'large',
					 *	'robots_max_video_preview' => -1,
					 */
					if ( isset( $this->p->options[ $opt_key ] ) ) {

						$value = $this->p->options[ $opt_key ];

					} else {

						$value = $default_value;
					}
				}

				if ( 'noindex' === $directive_key ) {

					$value = apply_filters( 'wpsso_robots_is_noindex', $value, $mod );
				}

				if ( $default_value !== $value ) {

					self::set_directive( $directive_key, $value, $directives );
				}
			}

			/**
			 * Sanity check - make sure inverse directives are removed.
			 */
			self::sanitize_directives( $directives );

			return apply_filters( 'wpsso_robots_directives', $directives, $mod );
		}

		/**
		 * $mixed can be a $mod array, or the name of a module (ie. 'post', 'term', etc.).
		 */
		public function is_noindex( $mixed, $mod_id = null ) {

			$is_noindex = null;

			$mod = false;

			if ( ! empty( $mixed[ 'obj' ] ) ) {

				$mod =& $mixed;

			} elseif ( is_string( $mixed ) && isset( $this->p->$mixed ) && $mod_id ) {	// Just in case.

				$mod = $this->p->$mixed->get_mod( $mod_id );
			}

			/**
			 * Ignore custom options if the robots meta tag is disabled.
			 */
			if ( $this->is_enabled() ) {

				if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

					$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

					if ( isset( $md_opts[ 'robots_noindex' ] ) ) {

						$is_noindex = $md_opts[ 'robots_noindex' ] ? true : false;
					}
				}
			}

			if ( null === $is_noindex ) {	// No custom options found.

				$directives = self::get_default_directives();

				$is_noindex = $directives[ 'noindex' ] ? true : false;
			}

			return apply_filters( 'wpsso_robots_is_noindex', $is_noindex, $mod );
		}

		public function is_disabled() {

			return $this->is_enabled() ? false : true;
		}

		public function is_enabled() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = empty( $this->p->options[ 'add_meta_name_robots' ] ) ? false : true;

				$local_cache = (bool) apply_filters( 'wpsso_robots_is_enabled', $local_cache );
			}

			return $local_cache;
		}
	}
}
