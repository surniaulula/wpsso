<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomUtil' ) ) {

	class SucomUtil {

		protected $p;
		protected $uniq_urls = array();		// array to detect duplicate images, etc.
		protected $size_labels = array();	// reference array for image size labels
		protected $inline_vars = array(
			'%%post_id%%',
			'%%request_url%%',
			'%%sharing_url%%',
			'%%short_url%%',
		);
		protected $inline_vals = array();

		protected static $plugins_idx = array();	// hash of active site and network plugins
		protected static $site_plugins = array();
		protected static $network_plugins = array();
		protected static $crawler_name = null;		// saved crawler name from user-agent
		protected static $is = array();			// saved return values for is_post/term/author_page() checks

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
		}

		public static function get_const( $const ) {
			if ( defined( $const ) )
				return constant( $const );
			else return false;
		}

		// returns false or the admin screen id text string
		public static function get_screen_id( $screen = false ) {
			if ( $screen === false &&
				function_exists( 'get_current_screen' ) )
					$screen = get_current_screen();
			if ( isset( $screen->id ) )
				return $screen->id;
			return false;
		}

		public static function sanitize_hookname( $name ) {
			$name = preg_replace( '/[:\/\-\.]+/', '_', $name );
			return self::sanitize_key( $name );
		}

		public static function sanitize_classname( $name ) {
			$name = preg_replace( '/[:\/\-\.]+/', '', $name );
			return self::sanitize_key( $name );
		}

		public static function sanitize_tag( $tag ) {
			$tag = sanitize_title_with_dashes( $tag, '', 'display' );
			$tag = urldecode( $tag );
			return $tag;
		}

		public static function sanitize_hashtags( $tags = array() ) {
			// truncate tags that start with a number (not allowed)
			return preg_replace( array( '/^[0-9].*/', '/[ \[\]#!\$\?\\\\\/\*\+\.\-\^]/', '/^.+/' ), 
				array( '', '', '#$0' ), $tags );
		}

		public static function array_to_hashtags( $tags = array() ) {
			// array_filter() removes empty array values
			return trim( implode( ' ', array_filter( self::sanitize_hashtags( $tags ) ) ) );
		}

		public static function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
		}

		public function get_inline_vars() {
			return $inline_vars;
		}

		public function get_inline_vals( $use_post = false, &$obj = false, &$atts = array() ) {

			if ( ! is_object( $obj ) ) {
				if ( ( $obj = $this->get_post_object( $use_post ) ) === false ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: invalid object type' );
					return array();
				}
			}
			$post_id = empty( $obj->ID ) || 
				empty( $obj->post_type ) ? 
					0 : $obj->ID;

			if ( isset( $atts['url'] ) )
				$sharing_url = $atts['url'];
			else $sharing_url = $this->get_sharing_url( $use_post, 
				( isset( $atts['add_page'] ) ?
					$atts['add_page'] : true ),
				( isset( $atts['source_id'] ) ?
					$atts['source_id'] : false ) );

			if ( is_admin() )
				$request_url = $sharing_url;
			else $request_url = ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' ).
				$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

			$short_url = empty( $atts['short_url'] ) ?
				apply_filters( $this->p->cf['lca'].'_shorten_url',
					$sharing_url, $this->p->options['plugin_shortener'] ) : $atts['short_url'];

			$this->inline_vals = array(
				$post_id,		// %%post_id%%
				$request_url,		// %%request_url%%
				$sharing_url,		// %%sharing_url%%
				$short_url,		// %%short_url%%
			);

			return $this->inline_vals;
		}

		// allow the variables and values array to be extended
		// $ext must be an associative array with key/value pairs to be replaced
		public function replace_inline_vars( $text, $use_post = false, $obj = false, $atts = array(), $ext = array() ) {
			$vars = $this->inline_vars;
			$vals = $this->get_inline_vals( $use_post, $obj, $atts );
			if ( ! empty( $ext ) && self::is_assoc( $ext ) ) {
				foreach ( $ext as $key => $str ) {
					$vars[] = '%%'.$key.'%%';
					$vals[] = $str;
				}
			}
			return str_replace( $vars, $vals, $text );
		}

		public static function active_plugins( $idx = false ) {
			// create list only once
			if ( empty( self::$plugins_idx ) ) {
				$all_plugins = self::$site_plugins = get_option( 'active_plugins', array() );
				if ( is_multisite() ) {
					self::$network_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
					if ( ! empty( self::$network_plugins ) )
						$all_plugins = array_merge( self::$site_plugins, self::$network_plugins );
				}
				foreach ( $all_plugins as $base )
					self::$plugins_idx[$base] = true;
			}
			if ( $idx !== false ) {
				if ( isset( self::$plugins_idx[$idx] ) )
					return self::$plugins_idx[$idx];
				else return false;
			} else return self::$plugins_idx;
		}

		public static function add_site_option_key( $name, $key, $value ) {
			return self::update_option_key( $name, $key, $value, true, true );
		}

		public static function update_site_option_key( $name, $key, $value, $protect = false ) {
			return self::update_option_key( $name, $key, $value, $protect, true );
		}

		// only creates new keys - does not update existing keys
		public static function add_option_key( $name, $key, $value ) {
			return self::update_option_key( $name, $key, $value, true, false );	// $protect = true
		}

		public static function update_option_key( $name, $key, $value, $protect = false, $site = false ) {
			if ( $site === true )
				$opts = get_site_option( $name, array() );
			else $opts = get_option( $name, array() );
			if ( $protect === true && 
				isset( $opts[$key] ) )
					return false;
			$opts[$key] = $value;
			if ( $site === true )
				return update_site_option( $name, $opts );
			else return update_option( $name, $opts );
		}

		public static function get_option_key( $name, $key, $site = false ) {
			if ( $site === true )
				$opts = get_site_option( $name, array() );
			else $opts = get_option( $name, array() );
			if ( isset( $opts[$key] ) )
				return $opts[$key];
			else return false;
		}

		public static function a2aa( $a ) {
			$aa = array();
			foreach ( $a as $i )
				$aa[][] = $i;
			return $aa;
		}

		public static function crawler_name( $id = '' ) {
			// optimize perf - only check once
			if ( self::$crawler_name === null ) {
				$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ?
					strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
				switch ( true ) {
					// "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)"
					case ( strpos( $ua, 'facebookexternalhit/' ) === 0 ):
						self::$crawler_name = 'facebook';
						break;
	
					// "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
					case ( strpos( $ua, 'compatible; googlebot/' ) !== false ):
						self::$crawler_name = 'google';
						break;
	
					// "Pinterest/0.1 +http://pinterest.com/"
					case ( strpos( $ua, 'pinterest/' ) === 0 ):
						self::$crawler_name = 'pinterest';
						break;
	
					// "Twitterbot/1.0"
					case ( strpos( $ua, 'twitterbot/' ) === 0 ):
						self::$crawler_name = 'twitter';
						break;
	
					// "W3C_Validator/1.3 http://validator.w3.org/services"
					case ( strpos( $ua, 'w3c_validator/' ) === 0 ):
						self::$crawler_name = 'w3c';
						break;
					default:
						self::$crawler_name = 'unknown';
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

		// pre-define the array key order for the list() construct (which assigns elements from right to left)
		public static function meta_image_tags( $mt_pre = 'og' ) {
			return array(
				$mt_pre.':image' => '',
				$mt_pre.':image:width' => '',
				$mt_pre.':image:height' => '',
				$mt_pre.':image:cropped' => '',
				$mt_pre.':image:id' => '',
			);
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
			foreach ( $keys as $old => $new ) {
				if ( empty( $old ) )
					continue;
				elseif ( isset( $opts[$old] ) ) {
					if ( ! empty( $new ) && 
						! isset( $opts[$new] ) )
							$opts[$new] = $opts[$old];
					unset( $opts[$old] );
				}
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

		public function is_uniq_url( $url = '', $context = 'default' ) {

			if ( empty( $url ) ) 
				return false;

			// complete the url with a protocol name
			if ( strpos( $url, '//' ) === 0 )
				$url = empty( $_SERVER['HTTPS'] ) ?
					'http:'.$url : 'https:'.$url;

			if ( $this->p->debug->enabled && 
				strpos( $url, '://' ) === false )
					$this->p->debug->log( 'incomplete url given for context ('.$context.'): '.$url );

			if ( ! isset( $this->uniq_urls[$context][$url] ) ) {
				$this->uniq_urls[$context][$url] = 1;
				return true;
			} else {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'duplicate url rejected for context ('.$context.'): '.$url ); 
				return false;
			}
		}

		public static function is_post_page( $use_post = false, $cache = true ) {
			if ( $cache &&
				isset( self::$is['post_page'][$use_post] ) )
					return self::$is['post_page'][$use_post];
			$ret = false;
			if ( is_singular() || $use_post )
				$ret = true;
			elseif ( is_admin() ) {
				$screen_id = self::get_screen_id();
				// exclude post/page/media editing lists
				if ( $screen_id === 'upload' ||
					strpos( $screen_id, 'edit-' ) === 0 )
						$ret = false;
				elseif ( self::get_req_val( 'post_ID', 'POST' ) !== '' ||
					self::get_req_val( 'post', 'GET' ) !== '' )
						$ret = true;
				elseif ( basename( $_SERVER['PHP_SELF'] ) === 'post-new.php' )
					$ret = true;
			}
			$ret = apply_filters( 'sucom_is_post_page', $ret, $use_post );
			if ( $cache )
				self::$is['post_page'][$use_post] = $ret;
			return $ret;
		}

		// on archives and taxonomies, this will return the first post object
		public function get_post_object( $use_post = false, $ret = 'object' ) {
			$obj = false;
			if ( $use_post === false ) {
				$obj = get_queried_object();
				if ( $obj === null && is_admin() ) {
					if ( ( $id = self::get_req_val( 'post_ID', 'POST' ) ) !== '' ||
						( $id = self::get_req_val( 'post', 'GET' ) ) !== '' )
							$obj = get_post( $id );
				}
				// fallback to $post if object is empty / invalid
				if ( ( empty( $obj->ID ) || empty( $obj->post_type ) ) &&
					isset( $GLOBALS['post'] ) )
						$obj = $GLOBALS['post'];
			} elseif ( $use_post === true && 
				isset( $GLOBALS['post'] ) )
					$obj = $GLOBALS['post'];
			elseif ( is_numeric( $use_post ) ) 
				$obj = get_post( $use_post );

			$obj = apply_filters( 'sucom_get_post_object', $obj, $use_post );

			switch ( $ret ) {
				case 'id':
				case 'ID':
					return isset( $obj->ID ) ? 
						$obj->ID : false;
					break;
				default:
					if ( $obj === false || ! is_object( $obj ) )
						return false;
					else return $obj;
					break;
			}
		}

		public static function is_term_page( $cache = true ) {
			if ( $cache &&
				isset( self::$is['term_page'] ) )
					return self::$is['term_page'];
			$ret = false;
			if ( is_tax() || is_category() || is_tag() )
				$ret = true;
			elseif ( is_admin() ) {
				if ( self::get_req_val( 'taxonomy' ) !== '' && 
					self::get_req_val( 'tag_ID' ) !== '' )
						$ret = true;
			}
			$ret = apply_filters( 'sucom_is_term_page', $ret );
			if ( $cache )
				self::$is['term_page'] = $ret;
			return $ret;
		}

		public static function is_category_page() {
			if ( isset( self::$is['category_page'] ) )
				return self::$is['category_page'];
			$ret = false;
			if ( is_category() )
				$ret = true;
			elseif ( is_admin() ) {
				if ( self::is_term_page() &&
					self::get_req_val( 'taxonomy' ) === 'category' )
						$ret = true;
			}
			return self::$is['category_page'] = apply_filters( 'sucom_is_category_page', $ret );
		}

		public function get_term_object( $ret = 'object' ) {
			$obj = false;	// return false by default
			if ( apply_filters( 'sucom_is_term_page', is_tax() ) || is_tag() || is_category() ) {
				$obj = apply_filters( 'sucom_get_term_object', get_queried_object() );
			} elseif ( is_admin() ) {
				if ( ( $tax_slug = self::get_req_val( 'taxonomy' ) ) !== '' &&
					( $term_id = self::get_req_val( 'tag_ID' ) ) !== '' )
						$obj = get_term_by( 'id', $term_id, $tax_slug, OBJECT, 'raw' );
			} else return false;

			switch ( $ret ) {
				case 'term_id':
				case 'id':
				case 'ID':
					return isset( $obj->term_id ) ? 
						$obj->term_id : false;
					break;
				default:
					return $obj;
					break;
			}
		}

		public static function is_author_page( $cache = true ) {
			if ( $cache &&
				isset( self::$is['author_page'] ) )
					return self::$is['author_page'];
			$ret = false;
			if ( is_author() ) {
				$ret = true;
			} elseif ( is_admin() ) {
				$screen_id = self::get_screen_id();
				if ( $screen_id !== false ) {
					switch ( $screen_id ) {
						case 'profile':
						case 'user-edit':
						case ( strpos( $screen_id, 'profile_page_' ) === 0 ? true : false ):
						case ( strpos( $screen_id, 'users_page_' ) === 0 ? true : false ):
							$ret = true;
							break;
					}
				}
				if ( $ret === false ) {
					if ( self::get_req_val( 'user_id' ) !== '' )
						$ret = true;
					elseif ( basename( $_SERVER['PHP_SELF'] ) === 'profile.php' )
						$ret = true;
				}
			}
			$ret = apply_filters( 'sucom_is_author_page', $ret );
			if ( $cache )
				self::$is['author_page'] = $ret;
			return $ret;
		}

		public function get_author_object( $ret = 'object' ) {
			$obj = false;
			if ( apply_filters( 'sucom_is_author_page', is_author() ) ) {
				$obj = apply_filters( 'sucom_get_author_object', ( get_query_var( 'author_name' ) ? 
					get_user_by( 'slug', get_query_var( 'author_name' ) ) : 
					get_userdata( get_query_var( 'author' ) ) ) );
			} elseif ( is_admin() ) {
				if ( ( $author_id = self::get_req_val( 'user_id' ) ) === '' )
					$author_id = get_current_user_id();
				$obj = get_userdata( $author_id );
			} else return false;

			switch ( $ret ) {
				case 'id':
				case 'ID':
					return isset( $obj->ID ) ? 
						$obj->ID : false;
					break;
				default:
					return $obj;
					break;
			}
		}

		public static function is_product_page( $use_post = false, $obj = false ) {
			if ( isset( self::$is['product_page'] ) )
				return self::$is['product_page'];
			$ret = false;
			if ( function_exists( 'is_product' ) && 
				is_product() )
					$ret = true;
			elseif ( is_object( $obj ) || is_admin() ) {
				if ( ! is_object( $obj ) && 
					! empty( $use_post ) )
						$obj = get_post( $use_post );
				if ( isset( $obj->post_type ) &&
					$obj->post_type === 'product' )
						$ret = true;
			}
			return self::$is['product_page'] = apply_filters( 'sucom_is_product_page', $ret, $use_post, $obj );
		}

		public static function is_product_category() {
			if ( isset( self::$is['product_category'] ) )
				return self::$is['product_category'];
			$ret = false;
			if ( function_exists( 'is_product_category' ) && 
				is_product_category() )
					$ret = true;
			elseif ( is_admin() ) {
				if ( SucomUtil::get_req_val( 'taxonomy' ) === 'product_cat' &&
					SucomUtil::get_req_val( 'post_type' ) === 'product' )
						$ret = true;
			}
			return self::$is['product_category'] = apply_filters( 'sucom_is_product_category', $ret );
		}

		public static function is_product_tag() {
			if ( isset( self::$is['product_tag'] ) )
				return self::$is['product_tag'];
			$ret = false;
			if ( function_exists( 'is_product_tag' ) && 
				is_product_tag() )
					$ret = true;
			elseif ( is_admin() ) {
				if ( SucomUtil::get_req_val( 'taxonomy' ) === 'product_tag' &&
					SucomUtil::get_req_val( 'post_type' ) === 'product' )
						$ret = true;
			}
			return self::$is['product_tag'] = apply_filters( 'sucom_is_product_tag', $ret );
		}

		public static function get_req_val( $key, $method = 'ANY' ) {
			if ( $method === 'ANY' )
				$method = $_SERVER['REQUEST_METHOD'];
			switch( $method ) {
				case 'POST':
					if ( isset( $_POST[$key] ) )
						return sanitize_text_field( $_POST[$key] );
					break;
				case 'GET':
					if ( isset( $_GET[$key] ) )
						return sanitize_text_field( $_GET[$key] );
					break;
			}
			return '';
		}

		// "use_post = false" when used for open graph meta tags and buttons in widget,
		// true when buttons are added to individual posts on an index webpage
		public function get_sharing_url( $use_post = false, $add_page = true, $src_id = false ) {
			$url = false;
			if ( is_singular() || $use_post !== false ) {
				if ( ( $obj = $this->get_post_object( $use_post ) ) === false )
					return $url;
				$post_id = empty( $obj->ID ) || 
					empty( $obj->post_type ) ? 0 : $obj->ID;
				if ( ! empty( $post_id ) ) {
					if ( isset( $this->p->mods['util']['post'] ) )
						$url = $this->p->mods['util']['post']->get_options( $post_id, 'sharing_url' );
					if ( ! empty( $url ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'custom post sharing_url = '.$url );
					} else $url = get_permalink( $post_id );

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
				$url = apply_filters( $this->p->cf['lca'].'_post_url', $url, $post_id, $use_post, $add_page, $src_id );
			} else {
				if ( is_search() ) {
					$url = get_search_link();

				} elseif ( is_front_page() ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'home_url(/) = '.home_url( '/' ) );
					$url = apply_filters( $this->p->cf['lca'].'_home_url', home_url( '/' ) );

				} elseif ( is_home() && 'page' === get_option( 'show_on_front' ) ) {
					$url = get_permalink( get_option( 'page_for_posts' ) );

				} elseif ( self::is_term_page() ) {
					$term = $this->get_term_object();
					if ( ! empty( $term->term_id ) ) {
						if ( isset( $this->p->mods['util']['taxonomy'] ) )
							$url = $this->p->mods['util']['taxonomy']->get_options( $term->term_id, 'sharing_url' );
						if ( ! empty( $url ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'custom taxonomy sharing_url = '.$url );
						} else $url = get_term_link( $term, $term->taxonomy );
					} 
					$url = apply_filters( $this->p->cf['lca'].'_term_url', $url, $term );

				} elseif ( self::is_author_page() ) {
					$author = $this->get_author_object();
					if ( ! empty( $author->ID ) ) {
						if ( isset( $this->p->mods['util']['user'] ) )
							$url = $this->p->mods['util']['user']->get_options( $author->ID, 'sharing_url' );
						if ( ! empty( $url ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'custom user sharing_url = '.$url );
						} else $url = get_author_posts_url( $author->ID );
					}
					$url = apply_filters( $this->p->cf['lca'].'_author_url', $url, $author );

				} elseif ( function_exists( 'get_post_type_archive_link' ) && is_post_type_archive() ) {
					$url = get_post_type_archive_link( get_query_var( 'post_type' ) );

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
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'home_url('.$base.') = '.home_url( $base ) );
							$url = home_url( $base );
						}
						$url = user_trailingslashit( trailingslashit( $url ).
							trailingslashit( $wp_rewrite->pagination_base ).get_query_var( 'paged' ) );
					}
				}
			}

			// fallback for themes and plugins that don't use the standard wordpress functions/variables
			if ( empty ( $url ) ) {
				$url = ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' ).
					$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
				// strip out tracking query arguments by facebook, google, etc.
				$url = preg_replace( '/([\?&])(fb_action_ids|fb_action_types|fb_source|fb_aggregation_id|utm_source|utm_medium|utm_campaign|utm_term|gclid|pk_campaign|pk_kwd)=[^&]*&?/i', '$1', $url );
			}

			return apply_filters( $this->p->cf['lca'].'_sharing_url', $url, $use_post, $add_page, $src_id );
		}

		public function fix_relative_url( $url = '' ) {
			if ( ! empty( $url ) && strpos( $url, '://' ) === false ) {
				if ( $this->p->debug->enabled )
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
				if ( $this->p->debug->enabled )
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

		// limit_text_length() uses PHP's multibyte functions (mb_strlen and mb_substr)
		public function limit_text_length( $text, $maxlen = 300, $trailing = '', $cleanup = true ) {
			$charset = get_bloginfo( 'charset' );
			if ( $cleanup === true )
				$text = $this->cleanup_html_tags( $text );				// remove any remaining html tags
			else $text = html_entity_decode( self::decode_utf8( $text ), ENT_QUOTES, $charset );
			if ( $maxlen > 0 ) {
				if ( mb_strlen( $trailing ) > $maxlen )
					$trailing = mb_substr( $trailing, 0, $maxlen );			// trim the trailing string, if too long
				if ( mb_strlen( $text ) > $maxlen ) {
					$text = mb_substr( $text, 0, $maxlen - mb_strlen( $trailing ) );
					$text = trim( preg_replace( '/[^ ]*$/', '', $text ) );		// remove trailing bits of words
					$text = preg_replace( '/[,\.]*$/', '', $text );			// remove trailing puntuation
				} else $trailing = '';							// truncate trailing string if text is less than maxlen
				$text = $text.$trailing;						// trim and add trailing string (if provided)
			}
			//$text = htmlentities( $text, ENT_QUOTES, $charset, false );
			$text = preg_replace( '/&nbsp;/', ' ', $text);					// just in case
			return $text;
		}

		public function cleanup_html_tags( $text, $strip_tags = true, $use_alt = false ) {
			$alt_text = '';
			$alt_prefix = isset( $this->p->options['plugin_img_alt_prefix'] ) ?
				$this->p->options['plugin_img_alt_prefix'] : 'Image:';
			$text = strip_shortcodes( $text );						// remove any remaining shortcodes
			//$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );	// leave it encoded
			$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );				// put everything on one line
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
							$alt = trim( $alt );
							if ( ! empty( $alt ) ) {
								$alt = empty( $alt_prefix ) ? 
									$alt : $alt_prefix.' '.$alt;

								// add a period after the image alt text if missing
								$alt_text .= ( strpos( $alt, '.' ) + 1 ) === strlen( $alt ) ? 
									$alt.' ' : $alt.'. ';
							}
						}
						if ( $this->p->debug->enabled )
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
				set_transient( $cache_id, $content, $this->p->options['plugin_object_cache_exp'] );

			return $content;
		}

		public function parse_readme( $lca, $expire_secs = 86400 ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->args( array( 
					'lca' => $lca,
					'expire_secs' => $expire_secs,
				) );
			}
			$plugin_info = array();
			if ( ! defined( strtoupper( $lca ).'_PLUGINDIR' ) ) {
				if ( $this->p->debug->enabled )
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
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $cache_type.': transient salt '.$cache_salt );
				$plugin_info = get_transient( $cache_id );
				if ( is_array( $plugin_info ) ) {
					if ( $this->p->debug->enabled )
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
				set_transient( $cache_id, $plugin_info, $this->p->options['plugin_object_cache_exp'] );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $cache_type.': plugin_info saved to transient '.
						$cache_id.' ('.$this->p->options['plugin_object_cache_exp'].' seconds)');
			}
			return $plugin_info;
		}

		public function get_admin_url( $menu_id = '', $link_text = '', $menu_lib = '' ) {
			$hash = '';
			$query = '';
			$admin_url = '';
			$lca = $this->p->cf['lca'];

			// $menu_id may start with a hash or query, so parse before checking its value
			if ( strpos( $menu_id, '#' ) !== false )
				list( $menu_id, $hash ) = explode( '#', $menu_id );

			if ( strpos( $menu_id, '?' ) !== false )
				list( $menu_id, $query ) = explode( '?', $menu_id );

			if ( empty( $menu_id ) ) {
				$current = $_SERVER['REQUEST_URI'];
				if ( preg_match( '/^.*\?page='.$lca.'-([^&]*).*$/', $current, $match ) )
					$menu_id = $match[1];
				else $menu_id = key( $this->p->cf['*']['lib']['submenu'] );	// default to first submenu
			}

			// find the menu_lib value for this menu_id
			if ( empty( $menu_lib ) ) {
				foreach ( $this->p->cf['*']['lib'] as $menu_lib => $menu ) {
					if ( isset( $menu[$menu_id] ) )
						break;
					else $menu_lib = '';
				}
			}

			if ( empty( $menu_lib ) ||
				empty( $this->p->cf['wp']['admin'][$menu_lib]['page'] ) )
					return;

			$parent_slug = $this->p->cf['wp']['admin'][$menu_lib]['page'].'?page='.$lca.'-'.$menu_id;

			switch ( $menu_lib ) {
				case 'sitesubmenu':
					$admin_url = network_admin_url( $parent_slug );
					break;
				default:
					$admin_url = admin_url( $parent_slug );
					break;
			}

			if ( ! empty( $query ) ) 
				$admin_url .= '&'.$query;

			if ( ! empty( $hash ) ) 
				$admin_url .= '#'.$hash;

			if ( empty( $link_text ) ) 
				return $admin_url;
			else return '<a href="'.$admin_url.'">'.$link_text.'</a>';
		}

		public function delete_expired_db_transients( $all = false ) { 
			global $wpdb;
			$deleted = 0;
			$time = isset ( $_SERVER['REQUEST_TIME'] ) ?
				(int) $_SERVER['REQUEST_TIME'] : time() ; 
			$dbquery = 'SELECT option_name FROM '.$wpdb->options.
				' WHERE option_name LIKE \'_transient_timeout_'.$this->p->cf['lca'].'_%\'';
			$dbquery .= $all === true ? ';' : ' AND option_value < '.$time.';';
			$expired = $wpdb->get_col( $dbquery ); 
			foreach( $expired as $transient ) { 
				$key = str_replace( '_transient_timeout_', '', $transient );
				if ( delete_transient( $key ) )
					$deleted++;
			}
			return $deleted;
		}

		public function delete_expired_file_cache( $all = false ) {
			$deleted = 0;
			$time = isset ( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : time() ; 
			$time = empty( $this->p->options['plugin_file_cache_exp'] ) ? 
				$time : $time - $this->p->options['plugin_file_cache_exp'];
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

		// if id 0 then returns values from the plugin settings 
		public function get_max_nums( $id, $mod = false ) {
			$og_max = array();
			foreach ( array( 'og_vid_max', 'og_img_max' ) as $max_name ) {
				$num_meta = false;
				if ( ! empty( $id ) && 
					isset( $this->p->mods['util'][$mod] ) )
						$num_meta = $this->p->mods['util'][$mod]->get_options( $id, $max_name );
				// quick sanitation of returned value
				if ( $num_meta === false || $num_meta === '' || $num_meta < 0 ) {
					$og_max[$max_name] = $this->p->options[$max_name];
				} else {
					$og_max[$max_name] = $num_meta;
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'found custom meta '.$max_name.' = '.$num_meta );
				}
			}
			return $og_max;
		}

		public function push_max( &$dst, &$src, $num = 0 ) {
			if ( ! is_array( $dst ) || 
				! is_array( $src ) ) 
					return false;
			// if the array is not empty, or contains some non-empty values, then push it
			if ( ! empty( $src ) && 
				array_filter( $src ) ) 
					array_push( $dst, $src );
			return $this->slice_max( $dst, $num );	// returns true or false
		}

		public function slice_max( &$arr, $num = 0 ) {
			if ( ! is_array( $arr ) )
				return false;
			$has = count( $arr );
			if ( $num > 0 ) {
				if ( $has == $num ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'max values reached ('.$has.' == '.$num.')' );
					return true;
				} elseif ( $has > $num ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'max values reached ('.$has.' > '.$num.') - slicing array' );
					$arr = array_slice( $arr, 0, $num );
					return true;
				}
			}
			return false;
		}

		public function is_maxed( &$arr, $num = 0 ) {
			if ( ! is_array( $arr ) ) 
				return false;
			if ( $num > 0 && count( $arr ) >= $num ) 
				return true;
			return false;
		}

		// table header with optional tooltip text
		public function get_th( $title = '', $class = '', $id = '', $atts = array() ) {

			if ( isset( $this->p->msgs ) ) {
				if ( empty( $id ) ) 
					$tooltip_idx = 'tooltip-'.$title;
				else $tooltip_idx = 'tooltip-'.$id;
				$tooltip_text = $this->p->msgs->get( $tooltip_idx, $atts );	// text is esc_attr()
			} else $tooltip_text = '';

			if ( isset( $atts['is_locale'] ) )
				$title .= ' <span style="font-weight:normal;">('.self::get_locale().')</span>';

			return '<th'.
				( empty( $atts['colspan'] ) ? '' : ' colspan="'.$atts['colspan'].'"' ).
				( empty( $atts['rowspan'] ) ? '' : ' rowspan="'.$atts['rowspan'].'"' ).
				( empty( $class ) ? '' : ' class="'.$class.'"' ).
				( empty( $id ) ? '' : ' id="th_'.$id.'"' ).'><p>'.$title.
				( empty( $tooltip_text ) ? '' : $tooltip_text ).'</p></th>';
		}

		// tab titles in the array should already be translated:
		//
		// $tabs = array(
		//		'header' => _x( 'Descriptions', 'metabox tab', 'nextgen-facebook' ),
		//		'media' => _x( 'Priority Media', 'metabox tab', 'nextgen-facebook' ),
		//		'preview' => _x( 'Social Preview', 'metabox tab', 'nextgen-facebook' ),
		//		'tags' => _x( 'Head Tags', 'metabox tab', 'nextgen-facebook' ),
		//		'validate' => _x( 'Validate', 'metabox tab', 'nextgen-facebook' ),
		// );
		//
		public function do_tabs( $metabox = '', $tabs = array(), $table_rows = array(), $args = array() ) {
			$tab_keys = array_keys( $tabs );
			$default_tab = '_'.reset( $tab_keys );		// must start with an underscore
			$class_metabox_tabs = 'sucom-metabox-tabs';
			$class_link = 'sucom-tablink';
			$class_tabset = 'sucom-tabset';

			if ( ! empty( $metabox ) ) {
				$metabox = '_'.$metabox;		// must start with an underscore
				$class_metabox_tabs .= ' '.$class_metabox_tabs.$metabox;
				$class_link .= ' '.$class_link.$metabox;
			}

			// allow a css ID to be passed as a query argument
			extract( array_merge( array(
				'scroll_to' => isset( $_GET['scroll_to'] ) ? 
					'#'.SucomUtil::sanitize_key( $_GET['scroll_to'] ) : '',
			), $args ) );

			echo "\n".'<script type="text/javascript">jQuery(document).ready(function(){ '.
				'sucomTabs(\''.$metabox.'\', \''.$default_tab.'\', \''.$scroll_to.'\'); });</script>'."\n";
			echo '<div class="'.$class_metabox_tabs.'">'."\n";
			echo '<ul class="'.$class_metabox_tabs.'">'."\n";
			foreach ( $tabs as $tab => $title ) {
				$class_href_key = $class_tabset.$metabox.'-tab_'.$tab;
				echo '<div class="tab_left">&nbsp;</div><li class="'.
					$class_href_key.'"><a class="'.$class_link.'" href="#'.
					$class_href_key.'">'.$title.'</a></li>'."\n";
			}
			echo '</ul><!-- .'.$class_metabox_tabs.' -->'."\n";

			foreach ( $tabs as $tab => $title ) {
				$class_href_key = $class_tabset.$metabox.'-tab_'.$tab;
				$this->do_table_rows( 
					$table_rows[$tab], 
					$class_href_key,
					( empty( $metabox ) ? '' : $class_tabset.$metabox ),
					$class_tabset
				);
			}
			echo '</div><!-- .'.$class_metabox_tabs.' -->'."\n\n";
		}

		public function do_table_rows( $table_rows, $class_href_key = '', $class_tabset_mb = '', $class_tabset = '' ) {
			// just in case
			if ( empty( $table_rows ) || ! is_array( $table_rows ) )
				return;

			$lca = empty( $this->p->cf['lca'] ) ? 
				'sucom' : $this->p->cf['lca'];
			$total_rows = count( $table_rows );
			$count_rows = 0;
			$hidden_opts = 0;
			$hidden_rows = 0;

			// use call_user_func() instead of $classname::show_opts() for PHP 5.2
			$show_opts = class_exists( $lca.'user' ) ? call_user_func( array( $lca.'user', 'show_opts' ) ) : 'basic';

			foreach ( $table_rows as $key => $row ) {
				// default row class and id attribute values
				$tr = array(
					'class' => 'sucom_alt'.( $count_rows % 2 ).
						( $count_rows === 0 ? ' first_row' : '' ).
						( $count_rows === ( $total_rows - 1 ) ? ' last_row' : '' ),
					'id' => ( is_int( $key ) ? '' : 'tr_'.$key )
				);

				// if we don't already have a table row tag, then add one
				if ( strpos( $row, '<tr ' ) === false )
					$row = '<tr class="'.$tr['class'].'"'.
						( empty( $tr['id'] ) ? '' : ' id="'.$tr['id'].'"' ).'>'.$row;
				else {
					foreach ( $tr as $att => $val ) {
						if ( empty( $tr[$att] ) )
							continue;

						// if we're here, then we have a table row tag already
						// count the number of rows and options that are hidden
						if ( $att === 'class' && ! empty( $show_opts ) && 
							( $matched = preg_match( '/<tr [^>]*class="[^"]*hide_in_'.$show_opts.'[" ]/', $row ) > 0 ) ) {
							$hidden_opts += preg_match_all( '/(<th|<tr[^>]*><td)/', $row, $matches );
							$hidden_rows += $matched;
						}

						// add the attribute value
						$row = preg_replace( '/(<tr [^>]*'.$att.'=")([^"]*)(")/', '$1$2 '.$tr[$att].'$3', $row, -1, $cnt );

						// if one hasn't been added, then add both the attribute and its value
						if ( $cnt < 1 )
							$row = preg_replace( '/(<tr )/', '$1'.$att.'="'.$tr[$att].'" ', $row, -1, $cnt );
					}
				}

				// add a closing table row tag if we don't already have one
				if ( strpos( $row, '</tr>' ) === false )
					$row .= '</tr>'."\n";

				// update the table row array element with the new value
				$table_rows[$key] = $row;

				$count_rows++;
			}

			echo '<div class="'.
				( empty( $show_opts ) ? '' : 'sucom-show_'.$show_opts ).
				( empty( $class_tabset ) ? '' : ' '.$class_tabset ).
				( empty( $class_tabset_mb ) ? '' : ' '.$class_tabset_mb ).
				( empty( $class_href_key ) ? '' : ' '.$class_href_key ).
			'">'."\n";

			echo '<table class="sucom-setting '.$lca.
				( empty( $class_href_key ) ? '' : ' '.$class_href_key ).
				( $hidden_rows === $count_rows ? ' hide_in_'.$show_opts : '' ).
			'">'."\n";

			foreach ( $table_rows as $row )
				echo $row;

			echo '</table>'."\n";
			echo '</div>'."\n";

			if ( $hidden_opts > 0 ) {
				$show_opts_label = $this->p->cf['form']['show_options'][$show_opts];
				echo '<div class="hidden_opts_msg '.
					$class_tabset.'-msg '.
					$class_tabset_mb.'-msg '.
					$class_href_key.'-msg">'.
					$hidden_opts.' more options not shown in '.
					$show_opts_label.' view (<a href="javascript:void(0);" onClick="sucomUnhideRows( \''.
					$class_href_key.'\', \''.$show_opts.'\' );">unhide these options</a>)</div>'."\n";
			}
		}

		public function get_source_id( $src_name, &$atts = array() ) {
			global $post;
			$use_post = isset( $atts['use_post'] ) ?
				$atts['use_post'] : true;
			$src_id = $src_name.( empty( $atts['css_id'] ) ? 
				'' : '-'.preg_replace( '/^'.$this->p->cf['lca'].'-/','', $atts['css_id'] ) );
			if ( $use_post == true && isset( $post->ID ) ) 
				$src_id = $src_id.'-post-'.$post->ID;
			return $src_id;
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

		public static function get_stripped_php ( $file ) {
			$ret = '';
			if ( file_exists( $file ) ) {
				$php = file_get_contents( $file );
				$comments = array(T_COMMENT); 
				if ( defined( 'T_DOC_COMMENT' ) )
					$comments[] = T_DOC_COMMENT;	// php 5
				if ( defined( 'T_ML_COMMENT' ) )
					$comments[] = T_ML_COMMENT;	// php 4
				$tokens = token_get_all( $php );
				foreach ( $tokens as $token ) {
					if ( is_array( $token ) ) {
						if ( in_array( $token[0], $comments ) )
							continue; 
						$token = $token[1];
					}
					$ret .= $token;
				}
			} else $ret = false;
			return $ret;
		}

		public static function esc_url_encode( $url ) {
			$allowed = array( '!', '*', '\'', '(', ')', ';', ':', '@', '&', '=',
				'+', '$', ',', '/', '?', '%', '#', '[', ']' );
			$replace = array( '%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D',
				'%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D' );
			return str_replace( $replace, $allowed, urlencode( esc_url( $url ) ) );
		}
	}
}

?>
