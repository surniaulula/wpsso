<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomCache' ) ) {

	class SucomCache {

		private $p;
		private $lca          = 'sucom';
		private $uca          = 'SUCOM';
		private $text_domain  = 'sucom';
		private $label_transl = '';

		public $base_dir = '';
		public $base_url = '/cache/';

		public $default_file_cache_exp   = DAY_IN_SECONDS;
		public $default_object_cache_exp = DAY_IN_SECONDS;

		public $curl_connect_timeout     = 10;	// The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
		public $curl_timeout             = 15;	// The maximum number of seconds to allow cURL functions to execute. 
		public $curl_max_redirs          = 10;	// The maximum amount of HTTP redirections to follow.

		private $url_get_mtimes = array();
		private $ignored_urls   = array(	// Saved on wp shutdown action.
			'transient_expires' => DAY_IN_SECONDS,
			'transient_loaded'  => false,
			'ignore_secs'       => 1800,	// 30 mins.
			'ignore_urls'       => array(),
		);

		public function __construct( $plugin = null, $lca = null, $text_domain = null, $label_transl = null ) {

			if ( ! class_exists( 'SucomUtil' ) ) {	// Just in case.

				require_once trailingslashit( dirname( __FILE__ ) ) . 'util.php';
			}

			$this->set_config( $plugin, $lca, $text_domain, $label_transl );

			add_action( 'shutdown', array( $this, 'save_ignored_urls' ) );
		}

		/**
		 * Set property values for text domain, notice label, etc.
		 */
		private function set_config( $plugin = null, $lca = null, $text_domain = null, $label_transl = null ) {

			if ( $plugin !== null ) {

				$this->p =& $plugin;

				if ( ! empty( $this->p->debug->enabled ) ) {

					$this->p->debug->mark();
				}
			}

			if ( $lca !== null ) {

				$this->lca = $lca;

			} elseif ( ! empty( $this->p->lca ) ) {

				$this->lca = $this->p->lca;
			}

			$this->uca = strtoupper( $this->lca );

			if ( $text_domain !== null ) {

				$this->text_domain = $text_domain;

			} elseif ( ! empty( $this->p->cf[ 'plugin' ][ $this->lca ][ 'text_domain' ] ) ) {

				$this->text_domain = $this->p->cf[ 'plugin' ][ $this->lca ][ 'text_domain' ];
			}

			if ( $label_transl !== null ) {

				$this->label_transl = $label_transl;	// argument is already translated

			} elseif ( ! empty( $this->p->cf[ 'menu' ][ 'title' ] ) ) {

				$this->label_transl = sprintf( __( '%s Notice', $this->text_domain ),
					_x( $this->p->cf[ 'menu' ][ 'title' ], 'menu title', $this->text_domain ) );

			} else {

				$this->label_transl = __( 'Notice', $this->text_domain );
			}

			$this->base_dir = trailingslashit( constant( $this->uca . '_CACHEDIR' ) );
			$this->base_url = trailingslashit( constant( $this->uca . '_CACHEURL' ) );
		}

		public function maybe_load_ignored_urls() {

			if ( ! $this->ignored_urls[ 'transient_loaded' ] ) {

				$cache_md5_pre = $this->lca . '_';
				$cache_salt    = __CLASS__ . '::ignored_urls';
				$cache_id      = $cache_md5_pre . md5( $cache_salt );
				$cache_ret     = get_transient( $cache_id );

				if ( false !== $cache_ret ) {

					$this->ignored_urls = $cache_ret;
				}

				$this->ignored_urls[ 'transient_loaded' ] = true;
			}
		}

		public function save_ignored_urls() {

			if ( $this->ignored_urls[ 'transient_loaded' ] ) {

				$cache_md5_pre = $this->lca . '_';
				$cache_salt    = __CLASS__ . '::ignored_urls';
				$cache_id      = $cache_md5_pre . md5( $cache_salt );

				set_transient( $cache_id, $this->ignored_urls, $this->ignored_urls[ 'transient_expires' ] );
			}
		}

		/**
		 * Returns false or time in seconds.
		 */
		public function is_ignored_url( $url_nofrag ) {

			$this->maybe_load_ignored_urls();

			if ( isset( $this->ignored_urls[ 'ignore_urls' ][ $url_nofrag ] ) ) {

				$time_diff = time() - $this->ignored_urls[ 'ignore_urls' ][ $url_nofrag ];
				$time_left = $this->ignored_urls[ 'ignore_secs' ] - $time_diff;

				if ( $time_left > 0 ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'requests to retrieve and cache ' . $url_nofrag . ' are ignored for another ' . $time_left . ' second(s)' );
					}

					return $time_left;

				}

				unset( $this->ignored_urls[ 'ignore_urls' ][ $url_nofrag ] );
			}

			return false;
		}

		public function add_ignored_url( $url_nofrag, $http_code = 0, $mtime_total = null ) {

			$this->maybe_load_ignored_urls();

			$this->ignored_urls[ 'ignore_urls' ][ $url_nofrag ] = time();

			if ( is_admin() ) {

				$errors = array();

				if ( $http_code ) {

					if ( $mtime_total ) {

						$errors[] = sprintf( __( 'Error retrieving %1$s for caching (HTTP code %2$d after %3$.03f seconds).',
							$this->text_domain ), '<a href="' . $url_nofrag . '">' . $url_nofrag . '</a>', $http_code, $mtime_total );

					} else {

						$errors[] = sprintf( __( 'Error retrieving %1$s for caching (HTTP code %2$d).',
							$this->text_domain ), '<a href="' . $url_nofrag . '">' . $url_nofrag . '</a>', $http_code );
					}

				} else {

					if ( $mtime_total ) {

						$errors[] = sprintf( __( 'Error retrieving %1$s for caching (after %2$.03f seconds).',
							$this->text_domain ), '<a href="' . $url_nofrag . '">' . $url_nofrag . '</a>', $mtime_total );

					} else {

						$errors[] = sprintf( __( 'Error retrieving %1$s for caching.',
							$this->text_domain ), '<a href="' . $url_nofrag . '">' . $url_nofrag . '</a>' );
					}
				}

				if ( 301 === $http_code || 302 === $http_code ) {

					/**
					 * PHP safe mode is an attempt to solve the shared-server security problem. It is
					 * architecturally incorrect to try to solve this problem at the PHP level, but since the
					 * alternatives at the web server and OS levels aren't very realistic, many people,
					 * especially ISP's, use safe mode for now.
					 *
					 * This feature has been DEPRECATED as of PHP 5.3 and REMOVED as of PHP 5.4.
					 */
					if ( ini_get( 'safe_mode' ) ) {

						$errors[] = sprintf( __( 'The PHP "%s" setting is enabled &mdash; the PHP cURL library cannot follow URL redirects.',
							$this->text_domain ), 'safe_mode' );

						$errors[] = sprintf( __( 'The "%s" setting is deprecated since PHP version 5.3 and removed from PHP since version 5.4.',
							$this->text_domain ), 'safe_mode' );

						$errors[] = __( 'Please contact your hosting provider to have this setting disabled or install a newer version of PHP.',
							$this->text_domain );

					/**
					 * open_basedir can be used to limit the files that can be accessed by PHP to the specified
					 * directory-tree, including the file itself. When a script tries to access the filesystem,
					 * for example using include, or fopen(), the location of the file is checked. When the
					 * file is outside the specified directory-tree, PHP will refuse to access it.
					 */
					} elseif ( ini_get( 'open_basedir' ) ) {

						$errors[] = sprintf( __( 'The PHP "%s" setting is enabled &mdash; the PHP cURL library cannot follow URL redirects.',
							$this->text_domain ), 'open_basedir' );

						$errors[] = __( 'Please contact your hosting provider to have this setting disabled.',
							$this->text_domain );

					} else {

						$errors[] = sprintf( __( 'The maximum number of URL redirects (%d) may have been exceeded.',
							$this->text_domain ), $this->curl_max_redirs );
					}
				}

				$errors[] = sprintf( __( 'Additional requests to retrieve and cache this URL will be ignored for another %d second(s).',
					$this->text_domain ), $this->ignored_urls[ 'ignore_secs' ] );

				/**
				 * Combine all strings into one error notice.
				 */
				$this->p->notice->err( $errors );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'error connecting to ' . $url_nofrag . ' for caching (http code ' . $http_code . ')' );

				$this->is_ignored_url( $url_nofrag );	// Returns false or time in seconds.
			}
		}

		public function clear_ignored_url( $url_nofrag ) {

			$this->maybe_load_ignored_urls();

			if ( isset( $this->ignored_urls[ 'ignore_urls' ][ $url_nofrag ] ) ) {

				unset( $this->ignored_urls[ 'ignore_urls' ][ $url_nofrag ] );
			
				return true;
			}

			return false;
		}

		public function clear( $url, $cache_ext = '' ) {

			$url_nofrag    = preg_replace( '/#.*$/', '', $url );	// Remove the fragment.
			$url_path      = parse_url( $url_nofrag, PHP_URL_PATH );
			$cache_md5_pre = $this->lca . ( $cache_ext ? $cache_ext : '_' );
			$cache_salt    = __CLASS__ . '::get(url:' . $url_nofrag . ')';
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			$this->clear_ignored_url( $url_nofrag );

			if ( wp_cache_delete( $cache_id, __CLASS__ ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'cleared object cache salt: ' . $cache_salt );
				}
			}

			if ( delete_transient( $cache_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'cleared transient cache salt: ' . $cache_salt );
				}
			}

			if ( $this->base_dir ) {	// Just in case.

				if ( ! $cache_ext ) {	// Default is an empty string.

					$cache_ext = pathinfo( $url_path, PATHINFO_EXTENSION );

					if ( $cache_ext ) {

						$cache_ext = '.' . $cache_ext;
					}
				}

				$file_name  = md5( $cache_salt ) . $cache_ext;
				$cache_file = $this->base_dir . $file_name;

				if ( file_exists( $cache_file ) ) {

					if ( @unlink( $cache_file ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'cleared local cache file: ' . $cache_file );
						}
					}
				}
			}
		}

		public function get_url_mtime( $url, $precision = 3 ) {

			if ( isset( $this->url_get_mtimes[ $url ] ) ) {

				if ( is_bool( $this->url_get_mtimes[ $url ] ) ) {

					return $this->url_get_mtimes[ $url ];

				} else {

					return sprintf( '%.0' . $precision . 'f', $this->url_get_mtimes[ $url ] );
				}

			} else {

				return false;
			}
		}

		/**
		 * Get image size for remote URL and cache for 300 seconds (5 minutes) by default.
		 *
		 * If $exp_secs is null, then use the default expiration time.
		 *
		 * If $exp_secs is false, then get but do not save the data.
		 */
		public function get_image_size( $image_url, $exp_secs = 300, $curl_opts = array(), $error_handler = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$file_path = $this->get( $image_url, 'file_path', 'file', $exp_secs, '', $curl_opts );

			if ( ! empty( $file_path ) ) {	// False on error.

				if ( file_exists( $file_path ) ) {

					if ( null !== $error_handler ) {

						$previous_error_handler = set_error_handler( $error_handler );
					}

					$image_size = getimagesize( $file_path );

					if ( null !== $error_handler ) {

						restore_error_handler();
					}

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log_arr( 'getimagesize', $image_size );
					}

					return $image_size;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( $file_path.' does not exist' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returned image file path is empty' );
			}

			return false;
		}

		public function get_data_url( $cache_salt, $cache_data, $exp_secs = null, $cache_ext = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$file_name  = md5( $cache_salt ) . $cache_ext;
			$cache_file = $this->base_dir . $file_name;
			$cache_url  = $this->base_url . $file_name;

			if ( file_exists( $cache_file ) ) {

				$file_mod_time = filemtime( $cache_file );
				$file_exp_secs = null === $exp_secs ? $this->default_file_cache_exp : $exp_secs;

				if ( false !== $exp_secs && $file_mod_time > time() - $file_exp_secs ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'cached file found: returning url ' . $cache_url );
					}

					$cache_url = add_query_arg( 'mtime', $file_mod_time, $cache_url );

					return $cache_url;

				} elseif ( @unlink( $cache_file ) ) {	// Remove expired file.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'removed expired cache file ' . $cache_file );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'error removing cache file ' . $cache_file );
					}

					$error_pre = sprintf( '%s error:', __METHOD__ );

					$error_msg = sprintf( __( 'Error removing cache file %s.', $this->text_domain ), $cache_file );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'cached file not found: creating ' . $cache_url );
				}
			}

			if ( $this->save_cache_data( $cache_salt, $cache_data, $cache_type = 'file', $exp_secs, $cache_ext ) ) {

				if ( file_exists( $cache_file ) ) {

					$file_mod_time = filemtime( $cache_file );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'cache data sucessfully saved' );
					}

					$cache_url = add_query_arg( 'mtime', $file_mod_time, $cache_url );

					return $cache_url;
				}
			}

			return false;
		}

		/**
		 * If $exp_secs is null, then use the default expiration time.
		 *
		 * If $exp_secs is false, then get but do not save the data.
		 *
		 * If $http_success is empty, save the data regardless of the HTTP return code.
		 */
		public function get( $url, $format = 'url', $cache_type = 'file', $exp_secs = null, $cache_ext = '', 
			array $curl_opts = array(), array $http_success = array( 200 ) ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$failure = $format === 'url' ? $url : false;

			$this->url_get_mtimes[ $url ] = false;	// Default value for failure.

			if ( ! extension_loaded( 'curl' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: curl library is missing' );
				}

				$error_pre = sprintf( '%s error:', __METHOD__ );

				$error_msg = __( 'PHP cURL library missing &mdash; contact your hosting provider to have the cURL library installed.', $this->text_domain );

				$this->p->notice->err( $error_msg );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

				return $failure;

			} elseif ( SucomUtil::get_const( $this->uca . '_PHP_CURL_DISABLE' ) ) { {

				if ( $this->p->debug->enabled )

					$this->p->debug->log( 'exiting early: curl has been disabled' );
				}

				return $failure;
			}

			$url_nofrag = preg_replace( '/#.*$/', '', $url );	// Remove the URL fragment.
			$url_path   = parse_url( $url_nofrag, PHP_URL_PATH );

			if ( ! $cache_ext ) {	// Default is an empty string.

				$cache_ext = pathinfo( $url_path, PATHINFO_EXTENSION );

				if ( $cache_ext ) {

					$cache_ext = '.' . $cache_ext;
				}
			}

			$url_fragment = parse_url( $url, PHP_URL_FRAGMENT );

			if ( ! empty( $url_fragment ) ) {

				$url_fragment = '#' . $url_fragment;
			}

			$cache_salt = __CLASS__ . '::get(url:' . $url_nofrag . ')';
			$cache_file = $this->base_dir . md5( $cache_salt ) . $cache_ext;
			$cache_url  = $this->base_url . md5( $cache_salt ) . $cache_ext . $url_fragment;
			$cache_data = false;

			/**
			 * Return immediately if the cache contains what we need.
			 */
			switch ( $format ) {

				case 'raw':

					$cache_data = $this->get_cache_data( $cache_salt, $cache_type, $exp_secs, $cache_ext );

					if ( false !== $cache_data ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'cached data found: returning ' . strlen( $cache_data ) . ' chars' );
						}

						$this->url_get_mtimes[ $url ] = true;	// Signal return is from cache.

						return $cache_data;
					}

					break;

				case 'url':
				case 'file_path':

					if ( file_exists( $cache_file ) ) {

						$file_mod_time = filemtime( $cache_file );
						$file_exp_secs = null === $exp_secs ? $this->default_file_cache_exp : $exp_secs;

						if ( false !== $exp_secs && $file_mod_time > time() - $file_exp_secs ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'cached file found: returning ' . $format . ' ' . 
									( $format === 'url' ? $cache_url : $cache_file ) );
							}

							$this->url_get_mtimes[ $url ] = true;	// Signal that return is from cache.

							if ( 'url' === $format ) {

								$cache_url = add_query_arg( 'mtime', $file_mod_time, $cache_url );

								return $cache_url;

							} else {
								return $cache_file;
							}

						} elseif ( @unlink( $cache_file ) ) {	// Remove expired file.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'removed expired cache file ' . $cache_file );
							}

						} else {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'error removing cache file ' . $cache_file );
							}

							$error_pre = sprintf( '%s error:', __METHOD__ );

							$error_msg = sprintf( __( 'Error removing cache file %s.', $this->text_domain ), $cache_file );

							$this->p->notice->err( $error_msg );

							SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
						}
					}

					break;

				default:

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'cache return type for ' . $format . ' is unknown' );
					}

					return $failure;

					break;
			}

			if ( $this->is_ignored_url( $url_nofrag ) ) {	// Returns false or time in seconds.

				return $failure;
			}

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url_nofrag );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout );	// 10 seconds (default is 300 seconds).
			curl_setopt( $ch, CURLOPT_TIMEOUT, $this->curl_timeout );			// 15 seconds (default is no timeout).

			/**
			 * When negotiating a TLS or SSL connection, the server sends a certificate indicating its identity. Curl
			 * verifies whether the certificate is authentic, i.e. that you can trust that the server is who the
			 * certificate says it is. This trust is based on a chain of digital signatures, rooted in certification
			 * authority (CA) certificates you supply. curl uses a default bundle of CA certificates (the path for that
			 * is determined at build time) and you can specify alternate certificates with the CURLOPT_CAINFO option
			 * or the CURLOPT_CAPATH option. 
			 *
			 * See https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYPEER.html
			 */
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );

			/**
			 * When CURLOPT_SSL_VERIFYHOST is 2, that certificate must indicate that the server is the server to which
			 * you meant to connect, or the connection fails. Simply put, it means it has to have the same name in the
			 * certificate as is in the URL you operate against.
			 *
			 * See https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html
			 */
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );

			/**
			 * Define and disable the "Expect: 100-continue" header.
			 */
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );

			if ( ! ini_get( 'safe_mode' ) && ! ini_get( 'open_basedir' ) ) {

				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
				curl_setopt( $ch, CURLOPT_MAXREDIRS, $this->curl_max_redirs );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'curl: PHP safe_mode or open_basedir defined - cannot use CURLOPT_FOLLOWLOCATION' );
			}

			/**
			 * Define cURL options from values defined as constants.
			 */
			$allowed_curl_const = array(
				'CAINFO',
				'PROXY',
				'PROXYUSERPWD',
				'USERAGENT',
				'CONNECTTIMEOUT',
			);

			foreach ( $allowed_curl_const as $const_suffix ) {

				$uca_const_name  = $this->uca . '_PHP_CURL_' . $const_suffix;
				$curl_const_name = 'CURLOPT_' . $const_suffix;

				if ( defined( $uca_const_name ) ) {

					curl_setopt( $ch, constant( $curl_const_name ), constant( $uca_const_name ) );
				}
			}

			/**
			 * Define cURL options provided by the $curl_opts method arguent (empty by default). Example:
			 *
			 *	$curl_opts = array(
			 *		'CURLOPT_USERAGENT' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
			 *	);
			 *
			 */
			if ( ! empty( $curl_opts ) ) {

				foreach ( $curl_opts as $opt_key => $opt_val ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'curl: setting custom curl option ' . $opt_key . ' = ' . $opt_val );
					}

					curl_setopt( $ch, constant( $opt_key ), $opt_val );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'curl: fetching ' . $url_nofrag );
			}

			$mtime_start = microtime( true );
			$cache_data  = curl_exec( $ch );
			$mtime_total = microtime( true ) - $mtime_start;
			$http_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$ssl_verify  = curl_getinfo( $ch, CURLINFO_SSL_VERIFYRESULT );

			curl_close( $ch );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'curl: execution time = ' . $mtime_total );

				$this->p->debug->log( 'curl: http return code = ' . $http_code );

				$this->p->debug->log( 'curl: ssl verify result = ' . $ssl_verify );
			}

			if ( empty( $http_success ) || in_array( $http_code, $http_success ) ) {

				$this->url_get_mtimes[ $url ] = $mtime_total;

				if ( empty( $cache_data ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'cache data returned is empty' );
					}

				} elseif ( false !== $exp_secs ) {	// Optimize and check first.

					if ( $this->save_cache_data( $cache_salt, $cache_data, $cache_type, $exp_secs, $cache_ext ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'cache data sucessfully saved' );
						}
					}
				}

				switch ( $format ) {

					case 'raw':

						return $cache_data;

						break;

					case 'url':

						return $cache_url;

						break;

					case 'file_path':

						return $cache_file;

						break;

					default:

						return $failure;	// Just in case.

						break;
				}

			} else {

				$this->add_ignored_url( $url_nofrag, $http_code, $mtime_total );
			}

			return $failure;
		}

		/**
		 * If $exp_secs is null, then use the default expiration time.
		 *
		 * If $exp_secs is false, then get but do not save the data.
		 */
		private function get_cache_data( $cache_salt, $cache_type = 'file', $exp_secs = null, $cache_ext = '' ) {

			$cache_data = false;

			if ( false === $exp_secs ) {

				return $cache_data;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $cache_type . ' cache salt ' . $cache_salt );
			}

			$cache_md5_pre = $this->lca . ( $cache_ext ? $cache_ext : '_' );
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			switch ( $cache_type ) {

				case 'wp_cache':

					$cache_data = wp_cache_get( $cache_id, __CLASS__ );

					break;

				case 'transient':

					$cache_data = get_transient( $cache_id );

					break;

				case 'file':

					$file_name     = md5( $cache_salt ) . $cache_ext;
					$cache_file    = $this->base_dir . $file_name;
					$file_exp_secs = null === $exp_secs ? $this->default_file_cache_exp : $exp_secs;

					if ( ! file_exists( $cache_file ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'cache file ' . $cache_file . ' does not exist' );
						}

					} elseif ( ! is_readable( $cache_file ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'cache file ' . $cache_file . ' is not readable' );
						}

						$error_pre = sprintf( '%s error:', __METHOD__ );

						$error_msg = sprintf( __( 'Cache file %s is not readable.', $this->text_domain ), $cache_file );

						$this->p->notice->err( $error_msg );

						SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

					} elseif ( filemtime( $cache_file ) < time() - $file_exp_secs ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $cache_file . ' is expired' );
						}

					} elseif ( ! $fh = @fopen( $cache_file, 'rb' ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'failed to open the cache file ' . $cache_file . ' for reading' );
						}

						$error_pre = sprintf( '%s error:', __METHOD__ );

						$error_msg = sprintf( __( 'Failed to open the cache file %s for reading.', $this->text_domain ), $cache_file );

						$this->p->notice->err( $error_msg );

						SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

					} else {

						$cache_data = fread( $fh, filesize( $cache_file ) );

						fclose( $fh );
					}

					break;

				default:

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'unknown cache type: ' . $cache_type );
					}

					break;
			}

			if ( $this->p->debug->enabled && false !== $cache_data ) {

				$this->p->debug->log( 'cache data retrieved from ' . $cache_type );
			}

			return $cache_data;	// return data or empty string
		}

		/**
		 * If $exp_secs is null, then use the default expiration time.
		 *
		 * If $exp_secs is false, then get but do not save the data.
		 */
		private function save_cache_data( $cache_salt, &$cache_data = '', $cache_type = 'file', $exp_secs = null, $cache_ext = '' ) {

			$data_saved = false;

			if ( empty( $cache_data ) || false === $exp_secs ) {

				return $data_saved;
			}

			$obj_exp_secs = null === $exp_secs ? $this->default_object_cache_exp : $exp_secs;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $cache_type . ' cache salt ' . $cache_salt );
			}

			$cache_md5_pre = $this->lca . ( $cache_ext ? $cache_ext : '_' );
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

			switch ( $cache_type ) {

				case 'wp_cache':

					wp_cache_set( $cache_id, $cache_data, __CLASS__, $obj_exp_secs );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'cache data saved to ' . $cache_type . ' ' .
							$cache_id . ' (' . $obj_exp_secs . ' seconds)' );
					}

					$data_saved = true;	// Success.

					break;

				case 'transient':

					set_transient( $cache_id, $cache_data, $obj_exp_secs );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'cache data saved to ' . $cache_type . ' ' .
							$cache_id . ' (' . $obj_exp_secs . ' seconds)' );
					}

					$data_saved = true;	// Success.

					break;

				case 'file':

					$file_name  = md5( $cache_salt ) . $cache_ext;
					$cache_file = $this->base_dir . $file_name;

					if ( ! is_dir( $this->base_dir ) ) {

						@mkdir( $this->base_dir );
					}

					if ( ! is_dir( $this->base_dir ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'failed to create the ' . $this->base_dir . ' cache folder.' );
						}

						$error_pre = sprintf( '%s error:', __METHOD__ );

						$error_msg = sprintf( __( 'Failed to create the %s cache folder.', $this->text_domain ), $this->base_dir );

						$this->p->notice->err( $error_msg );

						SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

					} elseif ( ! is_writable( $this->base_dir ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'cache folder ' . $this->base_dir . ' is not writable' );
						}

						$error_pre = sprintf( '%s error:', __METHOD__ );

						$error_msg = sprintf( __( 'Cache folder %s is not writable.', $this->text_domain ), $this->base_dir );

						$this->p->notice->err( $error_msg );

						SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

					} elseif ( ! $fh = @fopen( $cache_file, 'wb' ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'failed to open the cache file ' . $cache_file . ' for writing' );
						}

						$error_pre = sprintf( '%s error:', __METHOD__ );

						$error_msg = sprintf( __( 'Failed to open the cache file %s for writing.', $this->text_domain ), $cache_file );

						$this->p->notice->err( $error_msg );

						SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

					} elseif ( fwrite( $fh, $cache_data ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'data saved to cache file ' . $cache_file );
						}

						fclose( $fh );

						$data_saved = true;	// Success.

					} else {	// Error writing to cache file.

						fclose( $fh );

						@unlink( $cache_file );	// Just in case.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'failed writing data to cache file ' . $cache_file );
						}

						$error_pre = sprintf( '%s error:', __METHOD__ );

						$error_msg = sprintf( __( 'Failed writing data to cache file %s.', $this->text_domain ), $cache_file );

						$this->p->notice->err( $error_msg );

						SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
					}

					break;

				default:

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'unknown cache type: ' . $cache_type );
					}

					break;
			}

			return $data_saved;	// Return true or false.
		}
	}
}
