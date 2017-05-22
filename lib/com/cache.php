<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomCache' ) ) {

	class SucomCache {

		private $p;
		private $lca = 'sucom';
		private $text_dom = 'sucom';

		public $base_dir = '';
		public $base_url = '/cache/';
		public $default_file_cache_exp = DAY_IN_SECONDS;	// 1 day
		public $default_object_cache_exp = 259200;	// 3 days
		public $curl_connect_timeout = 10;
		public $curl_timeout = 20;
		public $curl_max_redirs = 10;

		private $in_time = array();
		private $transient = array(		// saved on wp shutdown action
			'loaded' => false,
			'expire' => HOUR_IN_SECONDS,
			'ignore_time' => 900,
			'ignore_urls' => array(),
		);

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->cf['lca'] ) ) {
				$this->lca = $this->p->cf['lca'];
				if ( ! empty( $this->p->cf['plugin'][$this->lca]['text_domain'] ) ) {
					$this->text_dom = $this->p->cf['plugin'][$this->lca]['text_domain'];
				}
			}

			$uca = strtoupper( $this->lca );
			$this->base_dir = trailingslashit( constant( $uca.'_CACHEDIR' ) );
			$this->base_url = trailingslashit( constant( $uca.'_CACHEURL' ) );

			add_action( 'shutdown', array( &$this, 'save_transient' ) );
		}

		public function load_transient() {
			if ( $this->transient['loaded'] !== true ) {
				$cache_salt = __CLASS__.'::transient';
				$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
				$ret = get_transient( $cache_id );
				if ( $ret !== false ) {
					$this->transient = $ret;
				}
				$this->transient['loaded'] = true;
			}
		}

		public function save_transient() {
			if ( $this->transient['loaded'] === true ) {
				$cache_salt = __CLASS__.'::transient';
				$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
				set_transient( $cache_id, $this->transient, $this->transient['expire'] );
			}
		}

		public function is_ignored_url( $url ) {

			$this->load_transient();

			if ( ! empty( $this->transient['ignore_urls'][$url] ) ) {
				$time_left = $this->transient['ignore_time'] - ( time() - $this->transient['ignore_urls'][$url] );
				if ( $time_left > 0 ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'ignoring url '.$url.' for another '.$time_left.' second(s). ' );
					}
					return true;
				} else {
					unset( $this->transient['ignore_urls'][$url] );
				}
			}
			return false;
		}

		public function add_ignored_url( $url, $http_code ) {

			$this->load_transient();
			$this->transient['ignore_urls'][$url] = time();

			if ( is_admin() ) {

				$errors = array();
				$errors[] = sprintf( __( 'Error connecting to %1$s for caching (HTTP code %2$d).',
					$this->text_dom ), '<a href="'.$url.'" target="_blank">'.$url.'</a>', $http_code );

				if ( $http_code === 301 ) {
					if ( ini_get('safe_mode') || ini_get('open_basedir') ) {
						$errors[] = __( 'PHP "safe_mode" or "open_basedir" is defined &mdash; the PHP cURL library cannot follow URL redirects.',
							$this->text_dom );
					} else {
						$errors[] = sprintf( __( 'The maximum number of URL redirects (%d) may have been exceeded.',
							$this->text_dom ), $this->curl_max_redirs );
					}
				}

				$errors[] = sprintf( __( 'Requests to cache this URL will be ignored for %d second(s).',
					$this->text_dom ), $this->transient['ignore_time'] );

				// combie all strings into one error notice
				$this->p->notice->err( implode( ' ', $errors ) );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'error connecting to '.$url.' for caching (http code '.$http_code.')' );
				$this->p->debug->log( 'requests to cache this URL ignored for '.$this->transient['ignore_time'].' second(s)' );
			}
		}

		public function clear( $url, $url_ext = '' ) {

			$get_url = preg_replace( '/#.*$/', '', $url );	// remove the fragment
			$cache_salt = __CLASS__.'::get(url:'.$get_url.')';
			$cache_id = $this->p->cf['lca']. '_'.md5( $cache_salt );	// add a prefix to the object cache id

			if ( wp_cache_delete( $cache_id, __CLASS__ ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'cleared object cache salt: '.$cache_salt );
				}
			}

			if ( delete_transient( $cache_id ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'cleared transient cache salt: '.$cache_salt );
				}
			}

			$url_path = parse_url( $get_url, PHP_URL_PATH );

			if ( $url_ext === '' ) {
				$url_ext = pathinfo( $url_path, PATHINFO_EXTENSION );
				if ( ! empty( $url_ext ) ) {
					$url_ext = '.'.$url_ext;
				}
			}

			$cache_file = $this->base_dir.md5( $cache_salt ).$url_ext;

			if ( file_exists( $cache_file ) && @unlink( $cache_file ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'clear file cache: '.$cache_file );
				}
			}
		}

		public function in_secs( $url, $dec = 2 ) {
			if ( isset( $this->in_time[$url] ) ) {
				if ( is_bool( $this->in_time[$url] ) ) {
					return $this->in_time[$url];
				} else {
					return sprintf( '%.0'.$dec.'f', $this->in_time[$url] );
				}
			} else {
				return false;
			}
		}

		public function get( $url, $ret_type = 'url', $cache_name = 'file', $cache_exp = false, $curl_userpwd = false, $url_ext = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$uca = strtoupper( $this->p->cf['lca'] );
			$failure = $ret_type === 'url' ? $url : false;
			$file_cache_exp = $cache_exp === false ? $this->default_file_cache_exp : $cache_exp;
			$this->in_time[$url] = false;	// default value for failure

			if ( ! extension_loaded( 'curl' ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: curl library is missing' );
				}
				if ( is_admin() ) {
					$this->p->notice->err( __( 'PHP cURL library is missing &mdash; contact your hosting provider to have the cURL library installed.',
						$this->text_dom ) );
				}
				return $failure;
			} elseif ( SucomUtil::get_const( $uca.'_PHP_CURL_DISABLE' ) ) { {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: curl has been disabled' );
				}
				return $failure;
			} elseif ( empty( $file_cache_exp ) && $cache_name === 'file' ) {	// nothing to do
				return $failure;
			}

			$get_url = preg_replace( '/#.*$/', '', $url );	// remove the fragment
			$url_path = parse_url( $get_url, PHP_URL_PATH );

			if ( $url_ext === '' ) {
				$url_ext = pathinfo( $url_path, PATHINFO_EXTENSION );
				if ( ! empty( $url_ext ) ) {
					$url_ext = '.'.$url_ext;
				}
			}

			$url_frag = parse_url( $url, PHP_URL_FRAGMENT );

			if ( ! empty( $url_frag ) ) {
				$url_frag = '#'.$url_frag;
			}

			$cache_salt = __CLASS__.'::get(url:'.$get_url.')';	// SucomCache::get()
			$cache_file = $this->base_dir.md5( $cache_salt ).$url_ext;
			$cache_url = $this->base_url.md5( $cache_salt ).$url_ext.$url_frag;
			$cache_data = false;

			// return immediately if the cache contains what we need
			switch ( $ret_type ) {

				case 'raw':

					$cache_data = $this->get_cache_data( $cache_salt, $cache_name, $url_ext, $cache_exp );

					if ( $cache_data !== false ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'cached data found: returning '.strlen( $cache_data ).' chars' );
						}
						$this->in_time[$url] = true;	// signal return is from cache
						return $cache_data;
					}

					break;

				case 'url':
				case 'filepath':

					if ( file_exists( $cache_file ) ) {
						if ( filemtime( $cache_file ) > time() - $file_cache_exp ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'cached file found: returning '.$ret_type.' '.
									( $ret_type === 'url' ? $cache_url : $cache_file ) );
							}
							$this->in_time[$url] = true;	// signal return is from cache
							return $ret_type === 'url' ?
								$cache_url : $cache_file;
						} elseif ( @unlink( $cache_file ) ) {	// remove expired file
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'removed expired cache file '.$cache_file );
							}
						} else {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'error removing cache file '.$cache_file );
							}
							if ( is_admin() ) {
								$this->p->notice->err( sprintf( __( 'Error removing cache file %s.',
									$this->text_dom ), $cache_file ) );
							}
						}
					}

					break;

				default:

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cache return type for '.$ret_type.' is unknown' );
					}

					return $failure;

					break;
			}

			if ( $this->is_ignored_url( $get_url ) ) {
				return $failure;
			}

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $get_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $this->curl_timeout );

			// define and disable the "Expect: 100-continue" header
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );

			if ( ini_get('safe_mode') || ini_get('open_basedir') ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'PHP safe_mode or open_basedir defined, cannot use CURLOPT_FOLLOWLOCATION' );
				}
			} else {
				curl_setopt( $ch, CURLOPT_MAXREDIRS, $this->curl_max_redirs );
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
			}

			if ( defined( $uca.'_PHP_CURL_USERAGENT' ) ) {
				curl_setopt( $ch, CURLOPT_USERAGENT, constant( $uca.'_PHP_CURL_USERAGENT' ) );
			}

			if ( defined( $uca.'_PHP_CURL_PROXY' ) ) {
				curl_setopt( $ch, CURLOPT_PROXY, constant( $uca.'_PHP_CURL_PROXY' ) );
			}

			if ( defined( $uca.'_PHP_CURL_PROXYUSERPWD' ) ) {
				curl_setopt( $ch, CURLOPT_PROXYUSERPWD, constant( $uca.'_PHP_CURL_PROXYUSERPWD' ) );
			}

			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );

			if ( defined( $uca.'_PHP_CURL_CAINFO' ) ) {
				curl_setopt( $ch, CURLOPT_CAINFO, constant( $uca.'_PHP_CURL_CAINFO' ) );
			}

			if ( $curl_userpwd !== false ) {
				curl_setopt( $ch, CURLOPT_USERPWD, $curl_userpwd );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'curl: fetching '.$get_url );
			}

			$start_time = microtime( true );
			$cache_data = curl_exec( $ch );
			$total_time = microtime( true ) - $start_time;
			$http_code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$ssl_verify = curl_getinfo( $ch, CURLINFO_SSL_VERIFYRESULT );

			curl_close( $ch );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'curl: http return code = '.$http_code );
				$this->p->debug->log( 'curl: ssl verify result = '.$ssl_verify );
			}

			if ( $http_code == 200 ) {

				$this->in_time[$url] = $total_time;

				if ( empty( $cache_data ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cache data returned is empty' );
					}
				} elseif ( $this->save_cache_data( $cache_salt, $cache_data, $cache_name, $url_ext, $cache_exp ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cache data sucessfully saved' );
					}
				}

				switch ( $ret_type ) {
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
						return $failure;	// just in case
						break;
				}
			} else {
				$this->add_ignored_url( $get_url, $http_code );
			}

			return $failure;
		}

		// returns false on failure
		protected function get_cache_data( $cache_salt, $cache_name = 'file', $url_ext = '', $cache_exp = false ) {

			$cache_data = false;
			$lca = $this->p->cf['lca'];

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $cache_name.' cache salt '.$cache_salt );
			}

			switch ( $cache_name ) {

				case 'wp_cache':

					$cache_id = $lca.'_'.md5( $cache_salt );	// add a prefix to the object cache id
					$cache_data = wp_cache_get( $cache_id, __CLASS__ );

					break;

				case 'transient':

					$cache_id = $lca.'_'.md5( $cache_salt );	// add a prefix to the object cache id
					$cache_data = get_transient( $cache_id );

					break;

				case 'file':

					$cache_id = md5( $cache_salt );		// no lca prefix on filenames
					$cache_file = $this->base_dir.$cache_id.$url_ext;
					$file_cache_exp = $cache_exp === false ? $this->default_file_cache_exp : $cache_exp;

					if ( ! file_exists( $cache_file ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $cache_file.' does not exist' );
						}
					} elseif ( ! is_readable( $cache_file ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $cache_file.' is not readable' );
						}
						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Cache file %s is not readable.',
								$this->text_dom ), $cache_file ) );
						}
					} elseif ( filemtime( $cache_file ) < time() - $file_cache_exp ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $cache_file.' is expired' );
						}
					} elseif ( ! $fh = @fopen( $cache_file, 'rb' ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'failed to open file '.$cache_file.' for reading' );
						}
						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Failed to open cache file %s for reading.',
								$this->text_dom ), $cache_file ) );
						}
					} else {
						$cache_data = fread( $fh, filesize( $cache_file ) );
						fclose( $fh );
					}

					break;

				default:

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'unknown cache name: '.$cache_name );
					}

					break;
			}

			if ( $this->p->debug->enabled && $cache_data !== false ) {
				$this->p->debug->log( 'cache data retrieved from '.$cache_name );
			}

			return $cache_data;	// return data or empty string
		}

		protected function save_cache_data( $cache_salt, &$cache_data = '', $cache_name = 'file', $url_ext = '', $cache_exp = false ) {

			$data_saved = false;
			$lca = $this->p->cf['lca'];

			if ( empty( $cache_data ) ) {
				return $data_saved;
			}

			// defining file_cache_exp is not required when saving files
			$object_cache_exp = $cache_exp === false ? $this->default_object_cache_exp : $cache_exp;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $cache_name.' cache salt '.$cache_salt );
			}

			switch ( $cache_name ) {

				case 'wp_cache':

					$cache_id = $lca.'_'.md5( $cache_salt );	// add a prefix to the object cache id
					wp_cache_set( $cache_id, $cache_data, __CLASS__, $object_cache_exp );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cache data saved to '.$cache_name.' '.
							$cache_id.' ('.$object_cache_exp.' seconds)' );
					}

					$data_saved = true;	// success

					break;

				case 'transient':

					$cache_id = $lca.'_'.md5( $cache_salt );	// add a prefix to the object cache id
					set_transient( $cache_id, $cache_data, $object_cache_exp );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'cache data saved to '.$cache_name.' '.
							$cache_id.' ('.$object_cache_exp.' seconds)' );
					}

					$data_saved = true;	// success

					break;

				case 'file':

					$cache_id = md5( $cache_salt );
					$cache_file = $this->base_dir.$cache_id.$url_ext;

					if ( ! is_dir( $this->base_dir ) ) {
						mkdir( $this->base_dir );
					}

					if ( ! is_writable( $this->base_dir ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $this->base_dir.' is not writable.' );
						}
						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Cache folder %s is not writable.',
								$this->text_dom ), $this->base_dir ) );
						}
					} elseif ( ! $fh = @fopen( $cache_file, 'wb' ) ) {
						if ( is_admin() ) {
							$this->p->notice->err( sprintf( __( 'Failed to open cache file %s for writing.',
								$this->text_dom ), $cache_file ) );
						}
					} elseif ( fwrite( $fh, $cache_data ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'cache data saved to '.$cache_file );
						}
						fclose( $fh );
						$data_saved = true;	// success
					}

					break;

				default:

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'unknown cache name: '.$cache_name );
					}

					break;
			}

			return $data_saved;	// return true or false
		}
	}
}

?>
