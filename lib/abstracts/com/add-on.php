<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomAddOn' ) ) {

	abstract class SucomAddOn {

		protected $p;	// Plugin class object.

		protected $ext   = '';
		protected $p_ext = '';
		protected $cf    = array();

		protected $did_plugin_notices = false;

		public function __construct() {}

		/**
		 * Since WPSSO Core v8.11.1.
		 */
		public function get_ext() {

			return $this->ext;	// Defined in WpssoAddon, which extends SucomAddon.
		}

		/**
		 * Since WPSSO Core v8.11.1.
		 */
		public function get_p_ext() {

			return $this->p_ext;	// Defined in WpssoAddon, which extends SucomAddon.
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

		/**
		 * All WPSSO Core objects are instantiated and configured.
		 *
		 * $is_admin and $doing_ajax available since WPSSO Core v7.10.0.
		 * $doing_cron available since WPSSO Core v8.8.0.
		 */
		public function init_plugin_notices( $is_admin = false, $doing_ajax = false, $doing_cron = false ) {

			$missing_reqs = $this->get_missing_requirements();	// Returns false or an array of missing requirements.

			$this->did_plugin_notices = true;

			if ( ! $doing_ajax && $missing_reqs ) {

				$error_pre = sprintf( '%s error:', __METHOD__ );

				foreach ( $missing_reqs as $key => $req_info ) {

					if ( ! empty( $req_info[ 'notice' ] ) ) {

						if ( $is_admin ) {

							$this->p->notice->err( $req_info[ 'notice' ] );

							SucomUtil::safe_error_log( $error_pre . ' ' . $req_info[ 'notice' ], $strip_html = true );
						}

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( strtolower( $req_info[ 'notice' ] ) );
						}
					}
				}

				return;	// Stop here.
			}
		}

		public function show_admin_notices() {

			if ( $this->did_plugin_notices ) {	// Nothing to do.

				return;	// Stop here.
			}

			$missing_reqs = $this->get_missing_requirements();	// Returns false or an array of missing requirements.

			if ( ! $missing_reqs ) {

				return;	// Stop here.
			}

			foreach ( $missing_reqs as $key => $req_info ) {

				if ( ! empty( $req_info[ 'notice' ] ) ) {

					echo '<div class="notice notice-error error"><p>';
					echo $req_info[ 'notice' ];
					echo '</p></div>';
				}
			}
		}

		/**
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

			$addon_name = $info[ 'name' ];

			$text_domain = $info[ 'text_domain' ];

			foreach ( $info[ 'req' ] as $key => $req_info ) {

				if ( ! empty( $req_info[ 'home' ] ) ) {

					$req_name = '<a href="' . $req_info[ 'home' ] . '">' . $req_info[ 'name' ] . '</a>';

				} else {

					$req_name = $req_info[ 'name' ];
				}

				if ( ! empty( $req_info[ 'version_global' ] ) && ! empty( $GLOBALS[ $req_info[ 'version_global' ] ] ) ) {

					$req_info[ 'version' ] = $GLOBALS[ $req_info[ 'version_global' ] ];

				} elseif ( ! empty( $req_info[ 'version_const' ] ) && defined( $req_info[ 'version_const' ] ) ) {

					$req_info[ 'version' ] = constant( $req_info[ 'version_const' ] );

				} elseif ( ! empty( $req_info[ 'plugin_class' ] ) && ! class_exists( $req_info[ 'plugin_class' ] ) ) {

					$this->init_textdomain();	// If not already loaded, load the textdomain now.

					$notice_msg = __( 'The %1$s add-on requires the %2$s plugin &mdash; please activate the missing plugin.',
						$text_domain );

					$req_info[ 'notice' ] = sprintf( $notice_msg, $addon_name, $req_name );

				}

				/**
				 * A version value from a global variable or constant.
				 */
				if ( ! empty( $req_info[ 'version' ] ) ) {

					if ( ! empty( $req_info[ 'min_version' ] ) ) {

						if ( version_compare( $req_info[ 'version' ], $req_info[ 'min_version' ], '<' ) ) {

							$this->init_textdomain();	// If not already loaded, load the textdomain now.

							$notice_msg = __( 'The %1$s add-on requires %2$s version %3$s or newer (version %4$s is currently installed).',
								$text_domain );

							$req_info[ 'notice' ] = sprintf( $notice_msg, $addon_name,
								$req_name, $req_info[ 'min_version' ], $req_info[ 'version' ] );
						}
					}
				}

				/**
				 * Possible notice for an insufficient wordpress or plugin version, or a missing plugin.
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
	}
}
