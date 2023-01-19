<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
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

		/*
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		/*
		 * See https://developers.google.com/search/reference/robots_meta_tag.
		 *
		 * Called by WpssoMetaName->maybe_disable_noindex(), and WpssoMetaName->get_array().
		 */
		public function get_content( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

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

		/*
		 * Explode a directive string into an associative array.
		 *
		 * Example $content:
		 *
		 *	follow, index, max-snippet:-1, max-image-preview:large, max-video-preview:-1
		 *
		 * Example $directives:
		 *
		 *	Array (
		 * 		[follow] =>
		 *		[index] =>
		 *		[max-snippet] => -1
		 *		[max-image-preview] => large
		 *		[max-video-preview] => -1
		 * 	)
		 *
		 * See https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag.
		 */
		public function get_content_directives( $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$directives = array();

			foreach ( explode( $separator = ', ', $content ) as $el ) {

				if ( false !== strpos( $el, ':' ) ) {

					list( $key, $val ) = explode( $separator = ':', $el );

				} else {

					list( $key, $val ) = array( $el, '' );
				}

				$directives[ $key ] = $val;
			}

			return $directives;
		}

		public function get_directives( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$directives = self::get_default_directives();
			$md_opts    = array();

			if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

				$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );
			}

			foreach ( $directives as $directive_key => $default_value ) {

				$value = null;

				$opt_key = str_replace( '-', '_', 'robots_' . $directive_key );	// Convert dashes to underscores.

				if ( isset( $md_opts[ $opt_key ] ) ) {

					$value = $md_opts[ $opt_key ];
				}

				/*
				 * Fallback to a default value.
				 */
				if ( null === $value ) {

					/*
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

			/*
			 * Sanity check - make sure inverse directives are removed.
			 */
			self::sanitize_directives( $directives );

			return apply_filters( 'wpsso_robots_directives', $directives, $mod );
		}

		/*
		 * $mixed can be a $mod array, or the name of a module (ie. 'post', 'term', etc.).
		 */
		public function is_noimageindex( $mixed, $mod_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->is_nokey( 'noimageindex', $mixed, $mod_id );
		}

		/*
		 * $mixed can be a $mod array, or the name of a module (ie. 'post', 'term', etc.).
		 */
		public function is_noindex( $mixed, $mod_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->is_nokey( 'noindex', $mixed, $mod_id );
		}

		/*
		 * $mixed can be a $mod array, or the name of a module (ie. 'post', 'term', etc.).
		 */
		private function is_nokey( $key, $mixed, $mod_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mod = false;
			$key = SucomUtil::sanitize_key( $key );	// Just in case.

			if ( ! empty( $mixed[ 'obj' ] ) ) {

				$mod =& $mixed;

			} elseif ( is_string( $mixed ) && isset( $this->p->$mixed ) && $mod_id ) {	// Just in case.

				$mod = $this->p->$mixed->get_mod( $mod_id );
			}

			$is_nokey  = null;
			$is_custom = false;

			if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

				$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( isset( $md_opts[ 'robots_' . $key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'robots ' . $key . ' for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' is true' );
					}

					$is_nokey  = $md_opts[ 'robots_' . $key ] ? true : false;
					$is_custom = true;
				}
			}

			if ( null === $is_nokey ) {	// No custom options found.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting default directives' );
				}

				$directives = self::get_default_directives();
				$is_nokey   = $directives[ $key ] ? true : false;
			}

			$filter_name = SucomUtil::sanitize_hookname( 'wpsso_robots_is_' . $key );	// Just in case.
			$is_nokey    = apply_filters( $filter_name, $is_nokey, $mod, $is_custom );

			return $is_nokey;
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
