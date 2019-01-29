<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
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

		public $default_file_cache_exp   = DAY_IN_SECONDS;	// 1 day.
		public $default_object_cache_exp = 259200;		// 3 days.
		public $curl_connect_timeout     = 5;			// The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
		public $curl_timeout             = 15;			// The maximum number of seconds to allow cURL functions to execute. 
		public $curl_max_redirs          = 10;			// The maximum amount of HTTP redirections to follow.

		private $url_mtimes = array();
		private $transient  = array(				// Saved on wp shutdown action.
			'loaded'      => false,
			'expire'      => HOUR_IN_SECONDS,
			'ignore_time' => 900,
			'ignore_urls' => array(),
		);

		public function __construct( $plugin = null, $lca = null, $text_domain = null, $label_transl = null ) {

			$this->set_config( $plugin, $lca, $text_domain, $label_transl );

			add_action( 'shutdown', array( $this, 'save_transient' ) );
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
			} elseif ( ! empty( $this->p->cf[ 'plugin' ][$this->lca]['text_domain'] ) ) {
				$this->text_domain = $this->p->cf[ 'plugin' ][$this->lca]['text_domain'];
			}

			if ( $label_transl !== null ) {
				$this->label_transl = $label_transl;	// argument is already translated
			} elseif ( ! empty( $this->p->cf['menu']['title'] ) ) {
				$this->label_transl = sprintf( __( '%s Notice', $this->text_domain ),
					_x( $this->p->cf['menu']['title'], 'menu title', $this->text_domain ) );
			} else {
				$this->label_transl = __( 'Notice', $this->text_domain );
			}

			$this->base_dir = trailingslashit( constant( $this->uca . '_CACHEDIR' ) );
			$this->base_url = trailingslashit( constant( $this->uca . '_CACHEURL' ) );
		}

		public function load_transient() {

			if ( true !== $this->transient['loaded'] ) {

				$cache_md5_pre = $this->lca . '_';
				$cache_salt    = __CLASS__ . '::transient';
				$cache_id      = $cache_md5_pre . md5( $cache_salt );
				$cache_ret     = get_transient( $cache_id );

				if ( false !== $cache_ret ) {
					$this->transient = $cache_ret;
				}

				$this->transient['loaded'] = true;
			}
		}

		public function save_transient() {

			if ( true === $this->transient['loaded'] ) {

				$cache_md5_pre = $this->lca . '_';
				$cache_salt    = __CLASS__ . '::transient';
				$cache_id      = $cache_md5_pre . md5( $cache_salt );

				set_transient( $cache_id, $this->transient, $this->transient['expire'] );
			}
		}

		public function is_ignored_url( $url ) {

			$this->load_transient();

			if ( ! empty( $this->transient[ 'ignore_urls' ][ $url ] ) ) {

				$time_left = $this->transient[ 'ignore_time' ] - ( time() - $this->transient[ 'ignore_urls' ][ $url ] );

				if ( $time_left > 0 ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'ignoring url ' . $url . ' for another ' . $time_left . ' second(s)' );
					}

					return true;

				} else {
					unset( $this->transient[ 'ignore_urls' ][ $url ] );
				}
			}

			return false;
		}

		public function add_ignored_url( $url, $http_code ) {

			$this->load_transient();

			$this->transient[ 'ignore_urls' ][ $url ] = time();

			if ( is_admin() ) {

				$errors = array();

				$errors[] = sprintf( __( 'Error connecting to %1$s for caching (HTTP code %2$d).',
					$this->text_domain ), '<a href="' . $url . '">' . $url . '</a>', $http_code );

				if ( $http_code === 301 || $http_code === 302 ) {

					/**
					 * PHP safe mode is an attempt to solve the shared-server security problem. It is architecturally incorrect
					 * to try to solve this problem at the PHP level, but since the alternatives at the web server and OS levels
					 * aren't very realistic, many people, especially ISP's, use safe mode for now.
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
					 * open_basedir can be used to limit the files that can be accessed by PHP to the specified directory-tree,
					 * including the file itself. When a script tries to access the filesystem, for example using include, or
					 * fopen(), the location of the file is checked. When the file is outside the specified directory-tree, PHP
					 * will refuse to access it.
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

				$errors[] = sprintf( __( 'Requests to cache this URL will be ignored for %d second(s).',
					$this->text_domain ), $this->transient[ 'ignore_time' ] );

				/**
				 * Combine all strings into one error notice.
				 */
				$this->p->notice->err( implode( ' ', $errors ) );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'error connecting to ' . $url . ' for caching (http code ' . $http_code . ')' );
				$this->p->debug->log( 'requests to cache this URL ignored for ' . $this->transient[ 'ignore_time' ] . ' second(s)' );
			}
		}

		public function clear( $url, $file_ext = '' ) {

			$url_nofrag    = preg_replace( '/#.*$/', '', $url );	// Remove the fragment.
			$url_path      = parse_url( $url_nofrag, PHP_URL_PATH );
			$cache_md5_pre = $this->lca . '_';
			$cache_salt    = __CLASS__ . '::get(url:' . $url_nofrag . ')';
			$cache_id      = $cache_md5_pre . md5( $cache_salt );

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

			if ( ! empty( $this->base_dir ) ) {	// Just in case.

				if ( '' === $file_ext ) {

					$file_ext = pathinfo( $url_path, PATHINFO_EXTENSION );

					if ( ! empty( $file_ext ) ) {
						$file_ext = '.' . $file_ext;
					}
				}

				$cache_file = $this->base_dir . md5( $cache_salt ) . $file_ext;

				if ( file_exists( $cache_file ) && @unlink( $cache_file ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cleared local cache file: ' . $cache_file );
					}
				}
			}
		}

		public function get_url_mtime( $url, $precision = 3 ) {

			if ( isset( $this->url_mtimes[$url] ) ) {
				if ( is_bool( $this->url_mtimes[$url] ) ) {
					return $this->url_mtimes[$url];
				} else {
					return sprintf( '%.0' . $precision . 'f', $this->url_mtimes[$url] );
				}
			} else {
				return false;
			}
		}

		/**
		 * Get image size for remote URL and cache for 300 seconds (5 minutes) by default.
		 *
		 * If $exp_secs is null, then use the default expiration time.
		 * If $exp_secs is false, then get but do not save the data.
		 */
		public function get_image_size( $image_url, $exp_secs = 300, array $curl_opts = array(), $error_handler = null ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$filepath = $this->get( $image_url, 'filepath', 'file', $exp_secs, '', $curl_opts );

			if ( ! empty( $filepath ) ) {	// False on error.

				if ( file_exists( $filepath ) ) {

					if ( null !== $error_handler ) {
						$previous_error_handler = set_error_handler( $error_handler );
					}

					$image_size = getimagesize( $filepath );

					if ( null !== $error_handler ) {
						restore_error_handler();
					}

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log_arr( 'getimagesize', $image_size );
					}

					return $image_size;

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( $filepath.' does not exist' );
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'returned image filepath is empty' );
			}

			return false;
		}

		/**
		 * If $exp_secs is null, then use the default expiration time.
		 * If $exp_secs is false, then get but do not save the data.
		 */
		public function get( $url, $format = 'url', $cache_type = 'file', $exp_secs = null, $file_ext = '', array $curl_opts = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$failure = $format === 'url' ? $url : false;

			$this->url_mtimes[ $url ] = false;	// default value for failure

			if ( ! extension_loaded( 'curl' ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: curl library is missing' );
				}

				if ( is_admin() ) {
					$this->p->notice->err( __( 'PHP cURL library is missing &mdash; contact your hosting provider to have the cURL library installed.',
						$this->text_domain ) );
				}

				return $failure;

			} elseif ( SucomUtil::get_const( $this->uca . '_PHP_CURL_DISABLE' ) ) { {

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: curl has been disabled' );
				}

				return $failure;
			}

			$url_nofrag = preg_replace( '/#.*$/', '', $url );	// remove the fragment
			$url_path   = parse_url( $url_nofrag, PHP_URL_PATH );

			if ( $file_ext === '' ) {

				$file_ext = pathinfo( $url_path, PATHINFO_EXTENSION );

				if ( ! empty( $file_ext ) ) {
					$file_ext = '.' . $file_ext;
				}
			}

			$url_fragment = parse_url( $url, PHP_URL_FRAGMENT );

			if ( ! empty( $url_fragment ) ) {
				$url_fragment = '#' . $url_fragment;
			}

			$cache_salt = __CLASS__ . '::get(url:' . $url_nofrag . ')';	// SucomCache::get()
			$cache_file = $this->base_dir . md5( $cache_salt ) . $file_ext;
			$cache_url  = $this->base_url . md5( $cache_salt ) . $file_ext . $url_fragment;
			$cache_data = false;

			/**
			 * Return immediately if the cache contains what we need.
			 */
			switch ( $format ) {

				case 'raw':

					$cache_data = $this->get_cache_data( $cache_salt, $cache_type, $exp_secs, $file_ext );

					if ( false !== $cache_data ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'cached data found: returning ' . strlen( $cache_data ) . ' chars' );
						}

						$this->url_mtimes[ $url ] = true;	// Signal return is from cache.

						return $cache_data;
					}

					break;

				case 'url':
				case 'filepath':

					if ( file_exists( $cache_file ) ) {

						$file_exp_secs = null === $exp_secs ? $this->default_file_cache_exp : $exp_secs;

						if ( false !== $exp_secs && filemtime( $cache_file ) > time() - $file_exp_secs ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'cached file found: returning ' . $format . ' ' . 
									( $format === 'url' ? $cache_url : $cache_file ) );
							}

							$this->url_mtimes[ $url ] = true;	// signal return is from cache

							return $format === 'url' ? $cache_url : $cache_file;

						} elseif ( @unlink( $cache_file ) ) {	// Remove expired file.

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'removed expired cache file ' . $cache_file );
							}

						} else {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'error removing cache file ' . $cache_file );
							}

							if ( is_admin() ) {
								$this->p->notice->err( sprintf( __( 'Error removing cache file %s.',
									$this->text_domain ), $cache_file ) );
							}
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

			if ( $this->is_ignored_url( $url_nofrag ) ) {
				return $failure;
			}

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url_nofrag );

			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout );

			curl_setopt( $ch, CURLOPT_TIMEOUT, $this->curl_timeout );

			/**
			 * When negotiating a TLS or SSL connection, the server sends a
			 * certificate indicating its identity. Curl verifies whether the
			 * certificate is authentic, i.e. that you can trust that the server is
			 * who the certificate says it is. This trust is based on a chain of
			 * digital signatures, rooted in certification authority (CA)
			 * certificates you supply. curl uses a default bundle of CA
			 * certificates (the path for that is determined at build time) and you
			 * can specify alternate certificates with the CURLOPT_CAINFO option or
			 * the CURLOPT_CAPATH option. 
			 *
			 * See https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYPEER.html
			 */
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );

			/**
			 * When CURLOPT_SSL_VERIFYHOST is 2, that certificate must indicate
			 * that the server is the server to which you meant to connect, or the
			 * connection fails. Simply put, it means it has to have the same name
			 * in the certificate as is in the URL you operate against.
			 *
			 * See https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html
			 */
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );

			/**
			 * Define and disable the "Expect: 100-continue" header.
			 */
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );

			if ( ! ini_get('safe_mode') && ! ini_get('open_basedir') ) {

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
				'USERAGENT',
				'PROXY',
				'PROXYUSERPWD',
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
				$this->p->debug->log( 'curl: http return code = ' . $http_code );
				$this->p->debug->log( 'curl: ssl verify result = ' . $ssl_verify );
			}

			if ( $http_code == 200 ) {

				$this->url_mtimes[ $url ] = $mtime_total;

				if ( empty( $cache_data ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cache data returned is empty' );
					}

				} elseif ( false !== $exp_secs ) {	// Optimize and check first.
				
					if ( $this->save_cache_data( $cache_salt, $cache_data, $cache_type, $exp_secs, $file_ext ) ) {

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

					case 'filepath':

						return $cache_file;

						break;

					default:

						return $failure;	// Just in case.

						break;
				}

			} else {
				$this->add_ignored_url( $url_nofrag, $http_code );
			}

			return $failure;
		}

		/**
		 * If $exp_secs is null, then use the default expiration time.
		 * If $exp_secs is false, then get but do not save the data.
		 */
		private function get_cache_data( $cache_salt, $cache_type = 'file', $exp_secs = null, $file_ext = '' ) {

			$cache_data = false;

			if ( false === $exp_secs ) {
				return $cache_data;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $cache_type . ' cache salt ' . $cache_salt );
			}

			switch ( $cache_type ) {

				case 'wp_cache':

					$cache_id   = $this->lca . '_' . md5( $cache_salt );	// Add a prefix to the object cache id.
					$cache_data = wp_cache_get( $cache_id, __CLASS__ );

					break;

				case 'transient':

					$cache_id   = $this->lca . '_' . md5( $cache_salt );	// Add a prefix to the object cache id.
					$cache_data = get_transient( $cache_id );

					break;

				case 'file':

					$cache_id   = md5( $cache_salt );			// No lca prefix on filenames.
					$cache_file = $this->base_dir . $cache_id . $file_ext;

					$file_exp_secs = null === $exp_secs ? $this->default_file_cache_exp : $exp_secs;

					if ( ! file_exists( $cache_file ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'cache file ' . $cache_file . ' does not exist' );
						}

					} elseif ( ! is_readable( $cache_file ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'cache file ' . $cache_file . ' is not readable' );
						}

						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Cache file %s is not readable.',
								$this->text_domain ), $cache_file ) );
						}

					} elseif ( filemtime( $cache_file ) < time() - $file_exp_secs ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $cache_file . ' is expired' );
						}

					} elseif ( ! $fh = @fopen( $cache_file, 'rb' ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'failed to open the cache file ' . $cache_file . ' for reading' );
						}

						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Failed to open the cache file %s for reading.',
								$this->text_domain ), $cache_file ) );
						}

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
		 * If $exp_secs is false, then get but do not save the data.
		 */
		private function save_cache_data( $cache_salt, &$cache_data = '', $cache_type = 'file', $exp_secs = null, $file_ext = '' ) {

			$data_saved = false;

			if ( empty( $cache_data ) || false === $exp_secs ) {
				return $data_saved;
			}

			$obj_exp_secs = null === $exp_secs ? $this->default_object_cache_exp : $exp_secs;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $cache_type . ' cache salt ' . $cache_salt );
			}

			switch ( $cache_type ) {

				case 'wp_cache':

					$cache_id = $this->lca . '_' . md5( $cache_salt );	// Add a prefix to the object cache id.

					wp_cache_set( $cache_id, $cache_data, __CLASS__, $obj_exp_secs );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cache data saved to ' . $cache_type . ' ' .
							$cache_id . ' (' . $obj_exp_secs . ' seconds)' );
					}

					$data_saved = true;	// Success.

					break;

				case 'transient':

					$cache_id = $this->lca . '_' . md5( $cache_salt );	// add a prefix to the object cache id

					set_transient( $cache_id, $cache_data, $obj_exp_secs );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cache data saved to ' . $cache_type . ' ' .
							$cache_id . ' (' . $obj_exp_secs . ' seconds)' );
					}

					$data_saved = true;	// Success.

					break;

				case 'file':

					$cache_id   = md5( $cache_salt );
					$cache_file = $this->base_dir . $cache_id . $file_ext;

					if ( ! is_dir( $this->base_dir ) ) {
						@mkdir( $this->base_dir );
					}

					if ( ! is_dir( $this->base_dir ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'failed to create the ' . $this->base_dir . ' cache folder.' );
						}

						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Failed to create the %s cache folder.',
								$this->text_domain ), $this->base_dir ) );
						}

					} elseif ( ! is_writable( $this->base_dir ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'cache folder ' . $this->base_dir . ' is not writable' );
						}

						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Cache folder %s is not writable.',
								$this->text_domain ), $this->base_dir ) );
						}

					} elseif ( ! $fh = @fopen( $cache_file, 'wb' ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'failed to open the cache file ' . $cache_file . ' for writing' );
						}

						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Failed to open the cache file %s for writing.',
								$this->text_domain ), $cache_file ) );
						}

					} elseif ( fwrite( $fh, $cache_data ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'data saved to cache file ' . $cache_file );
						}

						fclose( $fh );

						$data_saved = true;	// Success.
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
