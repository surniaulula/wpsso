<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomUtil' ) ) {

	class SucomUtil {

		private static $crawler_name = false;
		private $urls_found = array();	// array to detect duplicate images, etc.
		private $inline_vars = array(
			'%%post_id%%',
			'%%request_url%%',
			'%%sharing_url%%',
		);
		private $inline_vals = array();

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
		}

		public function get_inline_vars() {
			return $inline_vars;
		}

		public function get_inline_vals( $use_post = false, $obj = false ) {

			if ( ! is_object( $obj ) ) {
				if ( ( $obj = $this->get_post_object( $use_post ) ) === false ) {
					$this->p->debug->log( 'exiting early: invalid object type' );
					return $str;
				}
				$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ? 0 : $obj->ID;
			} else $post_id = $obj->ID;

			$sharing_url = $this->get_sharing_url( $use_post );

			if ( is_admin() )
				$request_url = $sharing_url;
			else $request_url = ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' ).
				$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

			$this->inline_vals = array(
				$post_id,		// %%post_id%%
				$request_url,		// %%request_url%%
				$sharing_url,		// %%sharing_url%%
			);

			return $this->inline_vals;
		}

		public function replace_inline_vars( $str, $use_post = false, $obj = false ) {
			return str_replace( $this->inline_vars, $this->get_inline_vals( $use_post, $obj ), $str );
		}

		public static function a2aa( $a ) {
			$aa = array();
			foreach ( $a as $i )
				$aa[][] = $i;
			return $aa;
		}

		public static function crawler_name( $id = '' ) {
			if ( self::$crawler_name === false ) {	// optimize perf - only check once
				$str = $_SERVER['HTTP_USER_AGENT'];
				switch ( true ) {
					// "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)"
					case ( strpos( $str, 'facebookexternalhit/' ) === 0 ):
						self::$crawler_name = 'facebook';
						break;
	
					// "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
					case ( strpos( $str, 'compatible; Googlebot/' ) !== false ):
						self::$crawler_name = 'google';
						break;
	
					// "Pinterest/0.1 +http://pinterest.com/"
					case ( strpos( $str, 'Pinterest/' ) === 0 ):
						self::$crawler_name = 'pinterest';
						break;
	
					// "Twitterbot/1.0"
					case ( strpos( $str, 'Twitterbot/' ) === 0 ):
						self::$crawler_name = 'twitter';
						break;
	
					// "W3C_Validator/1.3 http://validator.w3.org/services"
					case ( strpos( $str, 'W3C_Validator/' ) === 0 ):
						self::$crawler_name = 'w3c';
						break;
				}
			}	
			if ( ! empty( $id ) )
				return $id === self::$crawler_name ? true : false;
			else return self::$crawler_name;
		}

		public static function next_key( $needle, $arr, $cycle = true ) {
			$keys = array_keys( $arr );
			$pos = array_search( $needle, $keys );
			if ( $pos !== false ) {
				if ( isset( $keys[ $pos + 1 ] ) )
					return $keys[ $pos + 1 ];
				elseif ( $cycle === true )
					return $keys[0];
			}
			return false;
		}

		public static function is_assoc( $arr ) {
			if ( ! is_array( $arr ) ) 
				return false;
			return is_numeric( implode( array_keys( $arr ) ) ) ? false : true;
		}

		// argument can also be a numeric post ID to return the language of that post
		public static function get_locale( $get = 'current' ) {
			switch ( true ) {
				case ( $get === 'default' ):
					$lang = ( defined( 'WPLANG' ) && WPLANG ) ? WPLANG : 'en_US';
					break;
				default:
					$lang = get_locale();
					break;
			}
			return apply_filters( 'sucom_locale', $lang, $get );
		}

		// localize the options array key
		public static function get_locale_key( $key, &$opts = false, $get = 'current' ) {
			$default = self::get_locale( 'default' );
			$new_key = self::get_locale( $get ) !== $default ?
				$key.'#'.self::get_locale( $get ) : $key;

			// fallback if the locale option key does not exist in the array
			if ( is_array( $opts ) && $new_key !== $key ) {
				if ( ! array_key_exists( $new_key, $opts ) )
					return $key;	// may contain an empty value - that's ok
			}
			return $new_key;
		}

		public static function preg_grep_keys( $preg, &$arr, $invert = false, $replace = false ) {
			if ( ! is_array( $arr ) ) 
				return false;
			$invert = $invert == false ? 
				null : PREG_GREP_INVERT;
			$match = preg_grep( $preg, array_keys( $arr ), $invert );
			$found = array();
			foreach ( $match as $key ) {
				if ( $replace !== false ) {
					$fixed = preg_replace( $preg, $replace, $key );
					$found[$fixed] = $arr[$key]; 
				} else $found[$key] = $arr[$key]; 
			}
			return $found;
		}

		public static function rename_keys( &$opts = array(), &$keys = array() ) {
			// move old option values to new option names
			foreach ( $keys as $old => $new )
				// rename if the old array key exists, but not the new one (we don't want to overwrite current values)
				if ( ! empty( $old ) && ! empty( $new ) && 
					array_key_exists( $old, $opts ) && 
					! array_key_exists( $new, $opts ) ) {

					$opts[$new] = $opts[$old];
					unset( $opts[$old] );
				}
			return $opts;
		}

		public static function restore_checkboxes( &$opts ) {
			// unchecked checkboxes are not provided, so re-create them here based on hidden values
			$checkbox = self::preg_grep_keys( '/^is_checkbox_/', $opts, false, '' );
			foreach ( $checkbox as $key => $val ) {
				if ( ! array_key_exists( $key, $opts ) )
					$opts[$key] = 0;	// add missing checkbox as empty
				unset ( $opts['is_checkbox_'.$key] );
			}
			return $opts;
		}

		public function reset_urls_found() {
			$this->urls_found = array();
			return;
		}

		public function get_urls_found() {
			return $this->urls_found;
		}

		public function is_uniq_url( $url = '' ) {
			if ( empty( $url ) ) 
				return false;

			// complete the url with a protocol name
			if ( strpos( $url, '//' ) === 0 )
				$url = empty( $_SERVER['HTTPS'] ) ? 'http:'.$url : 'https:'.$url;

			if ( ! preg_match( '/[a-z]+:\/\//i', $url ) )
				$this->p->debug->log( 'incomplete url given: '.$url );

			if ( empty( $this->urls_found[$url] ) ) {
				$this->urls_found[$url] = 1;
				return true;
			} else {
				$this->p->debug->log( 'duplicate url rejected: '.$url ); 
				return false;
			}
		}

		public function get_author_object() {
			if ( is_author() ) {
				return get_query_var( 'author_name' ) ? 
					get_userdata( get_query_var( 'author' ) ) : 
					get_user_by( 'slug', get_query_var( 'author_name' ) );
			} elseif ( is_admin() ) {
				$author_id = empty( $_GET['user_id'] ) ? get_current_user_id() : $_GET['user_id'];
				return get_userdata( $author_id );
			} else return false;
		}

		// on archives and taxonomies, this will return the first post object
		public function get_post_object( $use_post = false ) {
			$obj = false;
			if ( $use_post === false ) {
				$obj = get_queried_object();

				// fallback to $post if object is empty / invalid
				if ( empty( $obj->ID ) || empty( $obj->post_type ) ) {
					global $post; 
					$obj = $post;
				}
			} elseif ( $use_post === true ) {
				global $post; 
				$obj = $post;
			} elseif ( is_numeric( $use_post ) ) 
				$obj = get_post( $use_post );

			$obj = apply_filters( $this->p->cf['lca'].'_get_post_object', $obj, $use_post );

			if ( $obj === false || ! is_object( $obj ) )
				return false;
			else return $obj;
		}

		// "use_post = false" when used for open graph meta tags and buttons in widget,
		// true when buttons are added to individual posts on an index webpage
		public function get_sharing_url( $use_post = false, $add_page = true, $source_id = false ) {
			$url = false;
			if ( is_singular() || $use_post !== false ) {
				if ( ( $obj = $this->get_post_object( $use_post ) ) === false )
					return $url;
				$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ? 0 : $obj->ID;
				if ( ! empty( $post_id ) ) {
					if ( isset( $this->p->mods['util']['postmeta'] ) )
						$url = $this->p->mods['util']['postmeta']->get_options( $post_id, 'sharing_url' );
					if ( ! empty( $url ) ) 
						$this->p->debug->log( 'custom postmeta sharing_url = '.$url );
					else $url = get_permalink( $post_id );

					if ( $add_page && get_query_var( 'page' ) > 1 ) {
						global $wp_rewrite;
						$numpages = substr_count( $obj->post_content, '<!--nextpage-->' ) + 1;
						if ( $numpages && get_query_var( 'page' ) <= $numpages ) {
							if ( ! $wp_rewrite->using_permalinks() || strpos( $url, '?' ) !== false )
								$url = add_query_arg( 'page', get_query_var( 'page' ), $url );
							else $url = user_trailingslashit( trailingslashit( $url ).get_query_var( 'page' ) );
						}
					}
				}
				$url = apply_filters( $this->p->cf['lca'].'_post_url', $url, $post_id, $use_post, $add_page, $source_id );
			} else {
				if ( is_search() )
					$url = get_search_link();
				elseif ( is_front_page() )
					$url = apply_filters( $this->p->cf['lca'].'_home_url', home_url( '/' ) );
				elseif ( $this->is_posts_page() )
					$url = get_permalink( get_option( 'page_for_posts' ) );
				elseif ( is_tax() || is_tag() || is_category() ) {
					$term = get_queried_object();
					$url = get_term_link( $term, $term->taxonomy );
					$url = apply_filters( $this->p->cf['lca'].'_term_url', $url, $term );
				}
				elseif ( function_exists( 'get_post_type_archive_link' ) && is_post_type_archive() )
					$url = get_post_type_archive_link( get_query_var( 'post_type' ) );
				elseif ( is_author() || ( is_admin() && ( $screen = get_current_screen() ) && ( $screen->id === 'user-edit' || $screen->id === 'profile' ) ) ) {
					$author = $this->get_author_object();
					if ( ! empty( $author->ID ) ) {
						if ( isset( $this->p->mods['util']['user'] ) )
							$url = $this->p->mods['util']['user']->get_options( $author->ID, 'sharing_url' );
						if ( ! empty( $url ) ) 
							$this->p->debug->log( 'custom user sharing_url = '.$url );
						else $url = get_author_posts_url( $author->ID );
					}
				} elseif ( is_archive() ) {
					if ( is_date() ) {
						if ( is_day() )
							$url = get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
						elseif ( is_month() )
							$url = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
						elseif ( is_year() )
							$url = get_year_link( get_query_var( 'year' ) );
					}
				}
				if ( ! empty( $url ) && $add_page && get_query_var( 'paged' ) > 1 ) {
					global $wp_rewrite;
					if ( ! $wp_rewrite->using_permalinks() )
						$url = add_query_arg( 'paged', get_query_var( 'paged' ), $url );
					else {
						if ( is_front_page() ) {
							$base = $GLOBALS['wp_rewrite']->using_index_permalinks() ? 'index.php/' : '/';
							$url = home_url( $base );
						}
						$url = user_trailingslashit( trailingslashit( $url ).trailingslashit( $wp_rewrite->pagination_base ).get_query_var( 'paged' ) );
					}
				}
			}

			// fallback for themes and plugins that don't use the standard wordpress functions/variables
			if ( empty ( $url ) ) {
				$url = empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://';
				$url .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
				// strip out tracking query arguments by facebook, google, etc.
				$url = preg_replace( '/([\?&])(fb_action_ids|fb_action_types|fb_source|fb_aggregation_id|utm_source|utm_medium|utm_campaign|utm_term|gclid|pk_campaign|pk_kwd)=[^&]*&?/i', '$1', $url );
			}

			return apply_filters( $this->p->cf['lca'].'_sharing_url', $url, $use_post, $add_page, $source_id );
		}

		public function is_posts_page() {
			return ( is_home() && 'page' == get_option( 'show_on_front' ) );
		}

		public function get_cache_url( $url, $url_ext = '' ) {

			// make sure the cache expiration is greater than 0 hours
			if ( empty( $this->p->cache->file_expire ) ) 
				return $url;

			// facebook javascript does not work when hosted locally
			if ( preg_match( '/:\/\/connect.facebook.net/', $url ) ) 
				return $url;

			return ( apply_filters( $this->p->cf['lca'].'_rewrite_url',
				$this->p->cache->get( $url, 'url', 'file', false, false, $url_ext ) ) );
		}

		public function fix_relative_url( $url = '' ) {
			if ( ! empty( $url ) && strpos( $url, '://' ) === false ) {
				$this->p->debug->log( 'relative url found = '.$url );
				$prot = empty( $_SERVER['HTTPS'] ) ? 'http:' : 'https:';
				if ( strpos( $url, '//' ) === 0 )
					$url = $prot.$url;
				elseif ( strpos( $url, '/' ) === 0 ) 
					$url = home_url( $url );
				else {
					$base = $prot.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
					if ( strpos( $base, '?' ) !== false ) {
						$base_parts = explode( '?', $base );
						$base = reset( $base_parts );
					}
					$url = trailingslashit( $base, false ).$url;
				}
				$this->p->debug->log( 'relative url fixed = '.$url );
			}
			return $url;
		}
	
		public static function encode_utf8( $decoded ) {
			if ( ! mb_detect_encoding( $decoded, 'UTF-8') == 'UTF-8' )
				$encoded = utf8_encode( $decoded );
			else $encoded = $decoded;
			return $encoded;
		}

		public static function decode_utf8( $encoded ) {
			// if we don't have something to decode, return immediately
			if ( strpos( $encoded, '&#' ) === false )
				return $encoded;

			// convert certain entities manually to something non-standard
			$encoded = preg_replace( '/&#8230;/', '...', $encoded );

			// if mb_decode_numericentity is not available, return the string un-converted
			if ( ! function_exists( 'mb_decode_numericentity' ) )
				return $encoded;

			$decoded = preg_replace( '/&#\d{2,5};/ue', 'self::decode_utf8_entity( \'$0\' )', $encoded );

			return $decoded;
		}

		public static function decode_utf8_entity( $entity ) {
			$convmap = array( 0x0, 0x10000, 0, 0xfffff );
			return mb_decode_numericentity( $entity, $convmap, 'UTF-8' );
		}

		public function limit_text_length( $text, $textlen = 300, $trailing = '', $cleanup = true ) {
			$charset = get_bloginfo( 'charset' );
			if ( $cleanup === true )
				$text = $this->cleanup_html_tags( $text );				// remove any remaining html tags
			else $text = html_entity_decode( self::decode_utf8( $text ), ENT_QUOTES, $charset );
			if ( $textlen > 0 ) {
				if ( strlen( $trailing ) > $textlen )
					$trailing = substr( $trailing, 0, $textlen );			// trim the trailing string, if too long
				if ( strlen( $text ) > $textlen ) {
					$text = substr( $text, 0, $textlen - strlen( $trailing ) );
					$text = trim( preg_replace( '/[^ ]*$/', '', $text ) );		// remove trailing bits of words
					$text = preg_replace( '/[,\.]*$/', '', $text );			// remove trailing puntuation
				} else $trailing = '';							// truncate trailing string if text is shorter than limit
				$text = $text.$trailing;						// trim and add trailing string (if provided)
			}
			$text = htmlentities( $text, ENT_QUOTES, $charset, false );			// double_encode = false
			$text = preg_replace( '/&nbsp;/', ' ', $text);					// just in case
			return $text;
		}

		public function cleanup_html_tags( $text, $strip_tags = true, $use_alt = false ) {
			$alt_text = '';
			$text = strip_shortcodes( $text );						// remove any remaining shortcodes
			$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
			$text = preg_replace( '/[\r\n\t ]+/s', ' ', $text );				// put everything on one line
			$text = preg_replace( '/<\?.*\?>/i', ' ', $text);				// remove php
			$text = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/i', ' ', $text);		// remove javascript
			$text = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/i', ' ', $text);		// remove inline stylesheets
			$text = preg_replace( '/<!--'.$this->p->cf['lca'].'-ignore-->(.*?)<!--\/'.
				$this->p->cf['lca'].'-ignore-->/i', ' ', $text);			// remove text between comment strings
			if ( $strip_tags ) {
				$text = preg_replace( '/<\/p>/i', ' ', $text);				// replace end of paragraph with a space
				$text_stripped = trim( strip_tags( $text ) );				// remove remaining html tags
				if ( $text_stripped === '' && $use_alt ) {				// possibly use img alt strings if no text
					if ( strpos( $text, '<img ' ) !== false &&
						preg_match_all( '/<img [^>]*alt=["\']([^"\'>]*)["\']/U', 
							$text, $matches, PREG_PATTERN_ORDER ) ) {
						foreach ( $matches[1] as $alt ) {
							$alt = 'Image: '.trim( $alt );
							$alt_text .= ( strpos( $alt, '.' ) + 1 ) === strlen( $alt ) ? $alt.' ' : $alt.'. ';
						}
						$this->p->debug->log( 'img alt text: '.$alt_text );
					}
					$text = $alt_text;
				} else $text = $text_stripped;
			}
			$text = preg_replace( '/(\xC2\xA0|\s)+/s', ' ', $text );	// convert space-like chars to a single space
			return trim( $text );
		}

		public function get_remote_content( $url = '', $file = '', $version = '', $expire_secs = 86400 ) {
			$content = false;
			$get_remote = empty( $url ) ? false : true;

			if ( $this->p->is_avail['cache']['transient'] ) {
				$cache_salt = __METHOD__.'(url:'.$url.'_file:'.$file.'_version:'.$version.')';
				$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
				$cache_type = 'object cache';
				$content = get_transient( $cache_id );
				if ( $content !== false )
					return $content;	// no need to save, return now
			} else $get_remote = false;

			if ( $get_remote === true && $expire_secs > 0 ) {
				$content = $this->p->cache->get( $url, 'raw', 'file', $expire_secs );
				if ( empty( $content ) )
					$get_remote = false;
			} else $get_remote = false;

			if ( $get_remote === false && ! empty( $file ) && $fh = @fopen( $file, 'rb' ) ) {
				$content = fread( $fh, filesize( $file ) );
				fclose( $fh );
			}

			if ( $this->p->is_avail['cache']['transient'] )
				set_transient( $cache_id, $content, $this->p->cache->object_expire );

			return $content;
		}

		public function parse_readme( $lca, $expire_secs = 86400 ) {
			$this->p->debug->args( array( 'lca' => $lca, 'expire_secs' => $expire_secs ) );
			$plugin_info = array();
			if ( ! defined( strtoupper( $lca ).'_PLUGINDIR' ) ) {
				$this->p->debug->log( strtoupper( $lca ).'_PLUGINDIR is undefined and required for readme.txt path' );
				return $plugin_info;
			}
			$readme_txt = constant( strtoupper( $lca ).'_PLUGINDIR' ).'readme.txt';
			$readme_url = isset( $this->p->cf['plugin'][$lca]['url']['readme'] ) ? 
				$this->p->cf['plugin'][$lca]['url']['readme'] : '';
			$get_remote = empty( $readme_url ) ? false : true;	// fetch readme from wordpress.org by default
			$content = '';

			if ( $this->p->is_avail['cache']['transient'] ) {
				$cache_salt = __METHOD__.'(url:'.$readme_url.'_txt:'.$readme_txt.')';
				$cache_id = $lca.'_'.md5( $cache_salt );
				$cache_type = 'object cache';
				$this->p->debug->log( $cache_type.': transient salt '.$cache_salt );
				$plugin_info = get_transient( $cache_id );
				if ( is_array( $plugin_info ) ) {
					$this->p->debug->log( $cache_type.': plugin_info retrieved from transient '.$cache_id );
					return $plugin_info;
				}
			} else $get_remote = false;	// use local if transient cache is disabled

			// get remote readme.txt file
			if ( $get_remote === true && $expire_secs > 0 )
				$content = $this->p->cache->get( $readme_url, 'raw', 'file', $expire_secs );

			// fallback to local readme.txt file
			if ( empty( $content ) && $fh = @fopen( $readme_txt, 'rb' ) ) {
				$get_remote = false;
				$content = fread( $fh, filesize( $readme_txt ) );
				fclose( $fh );
			}

			if ( ! empty( $content ) ) {
				$parser = new SuextParseReadme( $this->p->debug );
				$plugin_info = $parser->parse_readme_contents( $content );

				// remove possibly inaccurate information from local file
				if ( $get_remote !== true ) {
					foreach ( array( 'stable_tag', 'upgrade_notice' ) as $key )
						if ( array_key_exists( $key, $plugin_info ) )
							unset( $plugin_info[$key] );
				}
			}

			// save the parsed readme (aka $plugin_info) to the transient cache
			if ( $this->p->is_avail['cache']['transient'] ) {
				set_transient( $cache_id, $plugin_info, $this->p->cache->object_expire );
				$this->p->debug->log( $cache_type.': plugin_info saved to transient '.$cache_id.' ('.$this->p->cache->object_expire.' seconds)');
			}
			return $plugin_info;
		}

		public function get_admin_url( $submenu = '', $link_text = '', $lca = '' ) {
			$query = '';
			$hash = '';
			$url = '';
			$lca = empty( $lca ) ? $this->p->cf['lca'] : $lca;

			if ( strpos( $submenu, '#' ) !== false )
				list( $submenu, $hash ) = explode( '#', $submenu );
			if ( strpos( $submenu, '?' ) !== false )
				list( $submenu, $query ) = explode( '?', $submenu );

			if ( $submenu == '' ) {
				$current = $_SERVER['REQUEST_URI'];
				if ( preg_match( '/^.*\?page='.$lca.'-([^&]*).*$/', $current, $match ) )
					$submenu = $match[1];
				else $submenu = key( $this->p->cf['*']['lib']['submenu'] );
			}

			if ( array_key_exists( $submenu, $this->p->cf['*']['lib']['setting'] ) ) {
				$page = 'options-general.php?page='.$lca.'-'.$submenu;
				$url = admin_url( $page );
			} elseif ( array_key_exists( $submenu, $this->p->cf['*']['lib']['submenu'] ) ) {
				$page = 'admin.php?page='.$lca.'-'.$submenu;
				$url = admin_url( $page );
			} elseif ( array_key_exists( $submenu, $this->p->cf['*']['lib']['sitesubmenu'] ) ) {
				$page = 'admin.php?page='.$lca.'-'.$submenu;
				$url = network_admin_url( $page );
			}

			if ( ! empty( $query ) ) 
				$url .= '&'.$query;

			if ( ! empty( $hash ) ) 
				$url .= '#'.$hash;

			if ( empty( $link_text ) ) 
				return $url;
			else return '<a href="'.$url.'">'.$link_text.'</a>';
		}

		public function delete_expired_transients( $all = false ) { 
			global $wpdb, $_wp_using_ext_object_cache;
			if ( $_wp_using_ext_object_cache && 
				$all === false ) 
					return; 
			$deleted = 0;
			$time = isset ( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : time() ; 
			$dbquery = 'SELECT option_name FROM '.$wpdb->options.' WHERE option_name LIKE \'_transient_timeout_'.$this->p->cf['lca'].'_%\'';
			$dbquery .= $all === true ? ';' : ' AND option_value < '.$time.';'; 
			$expired = $wpdb->get_col( $dbquery ); 
			foreach( $expired as $transient ) { 
				$key = str_replace('_transient_timeout_', '', $transient);
				delete_transient( $key );
				$deleted++;
			}
			return $deleted;
		}

		public function delete_expired_file_cache( $all = false ) {
			$deleted = 0;
			$time = isset ( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : time() ; 
			$time = empty( $this->p->options['plugin_file_cache_hrs'] ) ? 
				$time : $time - ( $this->p->options['plugin_file_cache_hrs'] * 60 * 60 );
			$cachedir = constant( $this->p->cf['uca'].'_CACHEDIR' );
			if ( ! $dh = @opendir( $cachedir ) )
				$this->p->notice->err( 'Failed to open directory '.$cachedir.' for reading.', true );
			else {
				while ( $fn = @readdir( $dh ) ) {
					$filepath = $cachedir.$fn;
					if ( ! preg_match( '/^(\..*|index\.php)$/', $fn ) && is_file( $filepath ) && 
						( $all === true || filemtime( $filepath ) < $time ) ) {
						if ( ! @unlink( $filepath ) ) 
							$this->p->notice->err( 'Error removing file '.$filepath, true );
						else $deleted++;
					}
				}
				closedir( $dh );
			}
			return $deleted;
		}

		public function get_max_nums( $post_id ) {
			$this->p->debug->args( array( 'post_id' => $post_id ) );
			$og_max = array();
			foreach ( array( 'og_vid_max', 'og_img_max' ) as $max_name ) {
				$num_meta = false;
				if ( ! empty( $post_id ) && 
					array_key_exists( 'postmeta', $this->p->mods['util'] ) )
						$num_meta = $this->p->mods['util']['postmeta']->get_options( $post_id, $max_name );
				if ( $num_meta !== false ) {
					$og_max[$max_name] = $num_meta;
					$this->p->debug->log( 'found custom meta '.$max_name.' = '.$num_meta );
				} else $og_max[$max_name] = $this->p->options[$max_name];
			}
			return $og_max;
		}

		public function push_max( &$dst, &$src, $num = 0 ) {
			if ( ! is_array( $dst ) || ! is_array( $src ) ) return false;
			// if the array is not empty, or contains some non-empty values, then push it
			if ( ! empty( $src ) && array_filter( $src ) ) array_push( $dst, $src );
			return $this->slice_max( $dst, $num );	// returns true or false
		}

		public function slice_max( &$arr, $num = 0 ) {
			if ( ! is_array( $arr ) ) return false;
			$has = count( $arr );
			if ( $num > 0 ) {
				if ( $has == $num ) {
					$this->p->debug->log( 'max values reached ('.$has.' == '.$num.')' );
					return true;
				} elseif ( $has > $num ) {
					$this->p->debug->log( 'max values reached ('.$has.' > '.$num.') - slicing array' );
					$arr = array_slice( $arr, 0, $num );
					return true;
				}
			}
			return false;
		}

		public function is_maxed( &$arr, $num = 0 ) {
			if ( ! is_array( $arr ) ) return false;
			if ( $num > 0 && count( $arr ) >= $num ) return true;
			return false;
		}

		// table header with optional tooltip text
		public function th( $title = '', $class = '', $id = '', $atts = null ) {
			if ( ! empty( $this->p->msgs ) ) {
				if ( empty( $id ) ) 
					$tooltip_idx = 'tooltip-'.$title;
				else $tooltip_idx = 'tooltip-'.$id;
				$tooltip_text = $this->p->msgs->get( $tooltip_idx, $atts );	// text is esc_attr()
			}
			if ( is_array( $atts ) && ! empty( $atts['is_locale'] ) )
				$title .= ' <span style="font-weight:normal;">('.self::get_locale().')</span>';
			return '<th'.
				( empty( $atts['colspan'] ) ? '' : ' colspan="'.$atts['colspan'].'"' ).
				( empty( $class ) ? '' : ' class="'.$class.'"' ).
				( empty( $id ) ? '' : ' id="th_'.$id.'"' ).'><p>'.$title.
				( empty( $tooltip_text ) ? '' : $tooltip_text ).'</p></th>';
		}

		public function do_tabs( $metabox = '', $tabs = array(), $tab_rows = array(), $args = array() ) {
			$metabox = empty( $metabox ) ? '' : '_'.$metabox;	// must start with an underscore
			$tab_keys = array_keys( $tabs );
			$default_tab = '_'.reset( $tab_keys );			// must start with an underscore

			$class_metabox_tabs = 'sucom-metabox-tabs'.
				( empty( $metabox ) ? '' : ' sucom-metabox-tabs'.$metabox );
			$class_link = 'sucom-tablink'.
				( empty( $metabox ) ? '' : ' sucom-tablink'.$metabox );
			$class_tabset = 'sucom-tabset';

			extract( array_merge( array(
				'scroll_to' => '',
			), $args ) );

			echo '<script type="text/javascript">jQuery(document).ready(function(){ 
				sucomTabs(\''.$metabox.'\', \''.$default_tab.'\', \''.$scroll_to.'\'); });</script>
			<div class="'.$class_metabox_tabs.'">

			<ul class="'.$class_metabox_tabs.'">';
			foreach ( $tabs as $tab => $title ) {
				$href_key = $class_tabset.$metabox.'-tab_'.$tab;
				echo '<li class="'.$href_key.'"><a class="'.$class_link.'" 
					href="#'.$href_key.'">'.$title.'</a></li>';
			}
			echo '</ul>';

			foreach ( $tabs as $tab => $title ) {
				$href_key = $class_tabset.$metabox.'-tab_'.$tab;
				// use call_user_func() instead of $classname::show_opts() for PHP 5.2
				$show_opts = call_user_func( array(  $this->p->cf['lca'].'user', 'show_opts' ) );
				echo '<div class="display_'.$show_opts.' '.$class_tabset.
					( empty( $metabox ) ? '' : ' '.$class_tabset.$metabox ).' '.$href_key.'">';
				echo '<table class="sucom-setting">';
				if ( ! empty( $tab_rows[$tab] ) && is_array( $tab_rows[$tab] ) )
					foreach ( $tab_rows[$tab] as $num => $row ) 
						echo '<tr class="alt'.( $num % 2 ).'">'.$row.'</tr>';
				echo '</table>';
				echo '</div>';
			}
			echo '</div>';
		}

		public function get_tweet_max_len( $long_url, $opt_prefix = 'twitter' ) {
			$service = isset( $this->p->options['twitter_shortener'] ) ? $this->p->options['twitter_shortener'] : '';
			$short_url = apply_filters( $this->p->cf['lca'].'_shorten_url', $long_url, $service );
			$len_adj = strpos( $short_url, 'https:' ) === false ? 1 : 2;

			if ( $short_url < $this->p->options['plugin_min_shorten'] )
				$max_len = $this->p->options[$opt_prefix.'_cap_len'] - strlen( $short_url ) - $len_adj;
			else $max_len = $this->p->options[$opt_prefix.'_cap_len'] - $this->p->options['plugin_min_shorten'] - $len_adj;

			if ( ! empty( $this->p->options['tc_site'] ) && 
				! empty( $this->p->options[$opt_prefix.'_via'] ) )
					$max_len = $max_len - strlen( preg_replace( '/^@/', '', 
						$this->p->options['tc_site'] ) ) - 5;	// 5 for 'via' word and 2 spaces

			return $max_len;
		}

		public function get_source_id( $src_name, &$atts = array() ) {
			global $post;
			$use_post = array_key_exists( 'use_post', $atts ) ? $atts['use_post'] : true;
			$source_id = $src_name.( empty( $atts['css_id'] ) ? 
				'' : '-'.preg_replace( '/^'.$this->p->cf['lca'].'-/','', $atts['css_id'] ) );
			if ( $use_post == true && ! empty( $post ) ) 
				$source_id = $source_id.'-post-'.$post->ID;
			return $source_id;
		}

		public static function array_merge_recursive_distinct( array &$array1, array &$array2 ) {
			$merged = $array1; 
			foreach ( $array2 as $key => &$value ) {
				if ( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) )
					$merged[$key] = self::array_merge_recursive_distinct( $merged[$key], $value ); 
				else $merged[$key] = $value;
			} 
			return $merged;
		}

		public static function get_pub_lang( $pub = '' ) {
			$ret = array();
			switch ( $pub ) {
				case 'fb' :
				case 'facebook' :
					$ret = array(
						'af_ZA' => 'Afrikaans',
						'sq_AL' => 'Albanian',
						'ar_AR' => 'Arabic',
						'hy_AM' => 'Armenian',
						'az_AZ' => 'Azerbaijani',
						'eu_ES' => 'Basque',
						'be_BY' => 'Belarusian',
						'bn_IN' => 'Bengali',
						'bs_BA' => 'Bosnian',
						'bg_BG' => 'Bulgarian',
						'ca_ES' => 'Catalan',
						'zh_HK' => 'Chinese (Hong Kong)',
						'zh_CN' => 'Chinese (Simplified)',
						'zh_TW' => 'Chinese (Traditional)',
						'hr_HR' => 'Croatian',
						'cs_CZ' => 'Czech',
						'da_DK' => 'Danish',
						'nl_NL' => 'Dutch',
						'en_GB' => 'English (UK)',
						'en_PI' => 'English (Pirate)',
						'en_UD' => 'English (Upside Down)',
						'en_US' => 'English (US)',
						'eo_EO' => 'Esperanto',
						'et_EE' => 'Estonian',
						'fo_FO' => 'Faroese',
						'tl_PH' => 'Filipino',
						'fi_FI' => 'Finnish',
						'fr_CA' => 'French (Canada)',
						'fr_FR' => 'French (France)',
						'fy_NL' => 'Frisian',
						'gl_ES' => 'Galician',
						'ka_GE' => 'Georgian',
						'de_DE' => 'German',
						'el_GR' => 'Greek',
						'he_IL' => 'Hebrew',
						'hi_IN' => 'Hindi',
						'hu_HU' => 'Hungarian',
						'is_IS' => 'Icelandic',
						'id_ID' => 'Indonesian',
						'ga_IE' => 'Irish',
						'it_IT' => 'Italian',
						'ja_JP' => 'Japanese',
						'km_KH' => 'Khmer',
						'ko_KR' => 'Korean',
						'ku_TR' => 'Kurdish',
						'la_VA' => 'Latin',
						'lv_LV' => 'Latvian',
						'fb_LT' => 'Leet Speak',
						'lt_LT' => 'Lithuanian',
						'mk_MK' => 'Macedonian',
						'ms_MY' => 'Malay',
						'ml_IN' => 'Malayalam',
						'ne_NP' => 'Nepali',
						'nb_NO' => 'Norwegian (Bokmal)',
						'nn_NO' => 'Norwegian (Nynorsk)',
						'ps_AF' => 'Pashto',
						'fa_IR' => 'Persian',
						'pl_PL' => 'Polish',
						'pt_BR' => 'Portuguese (Brazil)',
						'pt_PT' => 'Portuguese (Portugal)',
						'pa_IN' => 'Punjabi',
						'ro_RO' => 'Romanian',
						'ru_RU' => 'Russian',
						'sk_SK' => 'Slovak',
						'sl_SI' => 'Slovenian',
						'es_LA' => 'Spanish',
						'es_ES' => 'Spanish (Spain)',
						'sr_RS' => 'Serbian',
						'sw_KE' => 'Swahili',
						'sv_SE' => 'Swedish',
						'ta_IN' => 'Tamil',
						'te_IN' => 'Telugu',
						'th_TH' => 'Thai',
						'tr_TR' => 'Turkish',
						'uk_UA' => 'Ukrainian',
						'vi_VN' => 'Vietnamese',
						'cy_GB' => 'Welsh',
					);
					break;
				case 'gplus' :
				case 'google' :
					$ret = array(
						'af'	=> 'Afrikaans',
						'am'	=> 'Amharic',
						'ar'	=> 'Arabic',
						'eu'	=> 'Basque',
						'bn'	=> 'Bengali',
						'bg'	=> 'Bulgarian',
						'ca'	=> 'Catalan',
						'zh-HK'	=> 'Chinese (Hong Kong)',
						'zh-CN'	=> 'Chinese (Simplified)',
						'zh-TW'	=> 'Chinese (Traditional)',
						'hr'	=> 'Croatian',
						'cs'	=> 'Czech',
						'da'	=> 'Danish',
						'nl'	=> 'Dutch',
						'en-GB'	=> 'English (UK)',
						'en-US'	=> 'English (US)',
						'et'	=> 'Estonian',
						'fil'	=> 'Filipino',
						'fi'	=> 'Finnish',
						'fr'	=> 'French',
						'fr-CA'	=> 'French (Canadian)',
						'gl'	=> 'Galician',
						'de'	=> 'German',
						'el'	=> 'Greek',
						'gu'	=> 'Gujarati',
						'iw'	=> 'Hebrew',
						'hi'	=> 'Hindi',
						'hu'	=> 'Hungarian',
						'is'	=> 'Icelandic',
						'id'	=> 'Indonesian',
						'it'	=> 'Italian',
						'ja'	=> 'Japanese',
						'kn'	=> 'Kannada',
						'ko'	=> 'Korean',
						'lv'	=> 'Latvian',
						'lt'	=> 'Lithuanian',
						'ms'	=> 'Malay',
						'ml'	=> 'Malayalam',
						'mr'	=> 'Marathi',
						'no'	=> 'Norwegian',
						'fa'	=> 'Persian',
						'pl'	=> 'Polish',
						'pt-BR'	=> 'Portuguese (Brazil)',
						'pt-PT'	=> 'Portuguese (Portugal)',
						'ro'	=> 'Romanian',
						'ru'	=> 'Russian',
						'sr'	=> 'Serbian',
						'sk'	=> 'Slovak',
						'sl'	=> 'Slovenian',
						'es'	=> 'Spanish',
						'es-419'	=> 'Spanish (Latin America)',
						'sw'	=> 'Swahili',
						'sv'	=> 'Swedish',
						'ta'	=> 'Tamil',
						'te'	=> 'Telugu',
						'th'	=> 'Thai',
						'tr'	=> 'Turkish',
						'uk'	=> 'Ukrainian',
						'ur'	=> 'Urdu',
						'vi'	=> 'Vietnamese',
						'zu'	=> 'Zulu',
					);
					break;
				case 'twitter' :
					$ret = array(
						'ar'	=> 'Arabic',
						'ca'	=> 'Catalan',
						'cs'	=> 'Czech',
						'da'	=> 'Danish',
						'de'	=> 'German',
						'el'	=> 'Greek',
						'en'	=> 'English',
						'en-gb'	=> 'English UK',
						'es'	=> 'Spanish',
						'eu'	=> 'Basque',
						'fa'	=> 'Farsi',
						'fi'	=> 'Finnish',
						'fil'	=> 'Filipino',
						'fr'	=> 'French',
						'gl'	=> 'Galician',
						'he'	=> 'Hebrew',
						'hi'	=> 'Hindi',
						'hu'	=> 'Hungarian',
						'id'	=> 'Indonesian',
						'it'	=> 'Italian',
						'ja'	=> 'Japanese',
						'ko'	=> 'Korean',
						'msa'	=> 'Malay',
						'nl'	=> 'Dutch',
						'no'	=> 'Norwegian',
						'pl'	=> 'Polish',
						'pt'	=> 'Portuguese',
						'ro'	=> 'Romanian',
						'ru'	=> 'Russian',
						'sv'	=> 'Swedish',
						'th'	=> 'Thai',
						'tr'	=> 'Turkish',
						'uk'	=> 'Ukrainian',
						'ur'	=> 'Urdu',
						'xx-lc'	=> 'Lolcat',
						'zh-tw'	=> 'Traditional Chinese',
						'zh-cn'	=> 'Simplified Chinese',
	
					);
					break;
			}
			asort( $ret );
			return $ret;
		}
	}
}

?>
