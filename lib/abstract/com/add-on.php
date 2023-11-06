<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2023 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomAbstractAddOn' ) ) {

	abstract class SucomAbstractAddOn {

		protected $p;	// Plugin class object.

		protected $ext   = '';		// Add-on lowercase classname, for example: 'wpssoum'.
		protected $p_ext = '';		// Add-on lowercase acronym, for example: 'um'.
		protected $cf    = array();	// Add-on config array, for example: WpssoUmConfig::$cf.

		protected $did_plugin_notices = false;	// True when $this->init_plugin_notices() has run.

		public function __construct() {}

		public function get_ext() {

			return $this->ext;
		}

		public function get_p_ext() {

			return $this->p_ext;
		}

		public function get_config( array $config ) {

			if ( $this->get_missing_requirements() ) {	// Returns false or an array of missing requirements.

				return $config;	// Stop here.
			}

			return SucomUtil::array_merge_recursive_distinct( $config, $this->cf );
		}

		public function get_avail( array $avail ) {

			if ( $this->get_missing_requirements() ) {		// Returns false or an array of missing requirements.

				$avail[ 'p_ext' ][ $this->p_ext ] = false;	// Signal that this extension / add-on is not available.

				return $avail;
			}

			$avail[ 'p_ext' ][ $this->p_ext ] = true;		// Signal that this extension / add-on is available.

			return $avail;
		}

		/*
		 * All WPSSO Core objects are instantiated and configured.
		 */
		public function init_plugin_notices() {

			$is_admin     = is_admin();
			$doing_ajax   = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
			$missing_reqs = $this->get_missing_requirements();	// Returns false or an array of missing requirements.

			$this->did_plugin_notices = true;	// Signal that $this->init_plugin_notices() has run.

			if ( ! $doing_ajax && $missing_reqs ) {

				foreach ( $missing_reqs as $key => $req_info ) {

					if ( ! empty( $req_info[ 'notice' ] ) ) {

						if ( $is_admin ) {

							$this->p->notice->err( $req_info[ 'notice' ] );

							SucomUtil::safe_error_log( __METHOD__ . ' error: ' . $req_info[ 'notice' ], $strip_html = true );
						}

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( strtolower( $req_info[ 'notice' ] ) );
						}
					}
				}
			}
		}

		public function show_admin_notices() {

			if ( $this->did_plugin_notices ) {	// True when $this->init_plugin_notices() has run.

				return;	// Stop here.
			}

			$missing_reqs = $this->get_missing_requirements();	// Returns false or an array of missing requirements.

			if ( $missing_reqs ) {

				foreach ( $missing_reqs as $key => $req_info ) {

					if ( ! empty( $req_info[ 'notice' ] ) ) {

						/*
						 * The 'notice' message is HTML generated from the add-on config (ie. the required
						 * plugin name, version, and link).
						 */
						echo wp_kses_post( '<div class="notice notice-error error"><p>' . $req_info[ 'notice' ] . '</p></div>' );
					}
				}
			}
		}

		/*
		 * Returns false or an array of missing requirements.
		 */
		protected function get_missing_requirements() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			$local_cache = array();	// Also prevents recursion.

			$info = $this->cf[ 'plugin' ][ $this->ext ];

			if ( empty( $info[ 'req' ] ) ) {

				return $local_cache = false;
			}

			$addon_name  = $info[ 'name' ];
			$text_domain = $info[ 'text_domain' ];

			foreach ( $info[ 'req' ] as $key => $req_info ) {

				if ( ! empty( $req_info[ 'home' ] ) ) {

					$req_name = '<a href="' . $req_info[ 'home' ] . '">' . $req_info[ 'name' ] . '</a>';

				} else {

					$req_name = $req_info[ 'name' ];
				}

				/*
				 * Optimize and check for plugin version first, then check for plugin existence.
				 */
				if ( ! empty( $req_info[ 'version_const' ] ) && defined( $req_info[ 'version_const' ] ) ) {

					$req_info[ 'version' ] = constant( $req_info[ 'version_const' ] );

				} elseif ( ! empty( $req_info[ 'version_global' ] ) && isset( $GLOBALS[ $req_info[ 'version_global' ] ] ) ) {

					$req_info[ 'version' ] = $GLOBALS[ $req_info[ 'version_global' ] ];

				} elseif ( ! empty( $req_info[ 'plugin_class' ] ) && ! class_exists( $req_info[ 'plugin_class' ] ) ) {

					$req_info[ 'notice' ] = $this->get_requires_plugin_notice( $info, $req_info );
				}

				/*
				 * A version value from a global variable or constant.
				 */
				if ( ! empty( $req_info[ 'version' ] ) ) {

					if ( ! empty( $req_info[ 'min_version' ] ) ) {

						if ( version_compare( $req_info[ 'version' ], $req_info[ 'min_version' ], '<' ) ) {

							$req_info[ 'notice' ] = $this->get_requires_version_notice( $info, $req_info );
						}
					}
				}

				/*
				 * Possible notice for wordpress version, plugin version, or missing plugin.
				 */
				if ( ! empty( $req_info[ 'notice' ] ) ) {

					$local_cache[ $key ] = $req_info;
				}
			}

			if ( empty( $local_cache ) ) {

				$local_cache = false;
			}

			return $local_cache;
		}

		protected function get_requires_plugin_notice( array $info, array $req_info ) {

			$this->init_textdomain();	// If not already loaded, load the textdomain now.

			$text_domain = $info[ 'text_domain' ];
			$addon_name  = _x( $info[ 'name' ], 'plugin name', $text_domain );
			$req_name    = _x( $req_info[ 'name' ], 'plugin name', $text_domain );
			$req_name    = empty( $req_info[ 'home' ] ) ? $req_name : '<a href="' . $req_info[ 'home' ] . '">' . $req_name . '</a>';
			$notice_html = __( 'The %1$s add-on requires the %2$s plugin.', $text_domain );
			$notice_html = sprintf( $notice_html, $addon_name, $req_name );

			return $notice_html;
		}

		protected function get_requires_version_notice( array $info, array $req_info ) {

			$this->init_textdomain();	// If not already loaded, load the textdomain now.

			$text_domain = $info[ 'text_domain' ];
			$addon_name  = _x( $info[ 'name' ], 'plugin name', $text_domain );
			$req_name    = _x( $req_info[ 'name' ], 'plugin name', $text_domain );
			$req_name    = empty( $req_info[ 'home' ] ) ? $req_name : '<a href="' . $req_info[ 'home' ] . '">' . $req_name . '</a>';
			$notice_html = __( 'The %1$s add-on requires %2$s version %3$s or newer (version %4$s is currently installed).', $text_domain );
			$notice_html = sprintf( $notice_html, $addon_name, $req_name, $req_info[ 'min_version' ], $req_info[ 'version' ] );

			return $notice_html;
		}
	}
}
