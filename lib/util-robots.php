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

			$directives = self::get_default_directives();

			/**
			 * Maybe get post, term, and user meta.
			 */
			if ( is_object( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

				$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

			} else {

				$md_opts = array();
			}

			foreach ( $directives as $directive_key => $directive_value ) {

				$opt_key = str_replace( '-', '_', 'robots_' . $directive_key );	// Convert dashes to underscores.

				/**
				 * Maybe use a custom directive value for this webpage.
				 */
				if ( isset( $md_opts[ $opt_key ] ) ) {

					self::set_directive( $directive_key, $md_opts[ $opt_key ], $directives );

				/**
				 * Maybe read a default value from the plugin settings.
				 */
				} elseif ( isset( $this->p->options[ $opt_key ] ) ) {

					self::set_directive( $directive_key, $this->p->options[ $opt_key ], $directives );
				}
			}

			/**
			 * Sanity check - make sure inverse directives are removed.
			 */
			self::sanitize_directives( $directives );

			return $directives;
		}

		public function is_noindex( $mod_name, $mod_id ) {

			if ( $mod_name && $mod_id && isset( $this->p->$mod_name ) ) {	// Just in case.

				$md_opts = $this->p->$mod_name->get_options( $mod_id );

				if ( isset( $md_opts[ 'robots_noindex' ] ) ) {

					return $md_opts[ 'robots_noindex' ] ? true : false;
				}
			}

			$directives = self::get_default_directives();

			return $directives[ 'noindex' ] ? true : false;
		}
	}
}
