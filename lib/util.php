<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoUtil' ) && class_exists( 'SucomUtil' ) ) {

	class WpssoUtil extends SucomUtil {

		private $size_labels = array();	// reference array for image size labels
		private $urls_found = array();	// array to detect duplicate images, etc.

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->add_actions();
		}

		protected function add_actions() {
			// add default image sizes from plugin settings
			// add_plugin_image_sizes() is also called from WpssoPostmeta::set_header_tags() to set image sizes for the post id
			add_action( 'wp', array( &$this, 'add_plugin_image_sizes' ), -100 );	// runs everytime a posts query is triggered from an url
			add_action( 'admin_init', array( &$this, 'add_plugin_image_sizes' ), -100 );

			add_action( 'wp_scheduled_delete', array( &$this, 'delete_expired_transients' ) );
			add_action( 'wp_scheduled_delete', array( &$this, 'delete_expired_file_cache' ) );
		}

		// called from several class __construct() methods to hook their filters
		public function add_plugin_filters( &$class, $filters, $prio = 10, $lca = '' ) {
			$lca = $lca === '' ? $this->p->cf['lca'] : $lca;
			$debug_enabled = $this->p->debug->is_on();	// optimize debug logging in loops
			foreach ( $filters as $name => $num ) {
				$filter = $lca.'_'.$name;
				$method = 'filter_'.$name;
				add_filter( $filter, array( &$class, $method ), $prio, $num );
				if ( $debug_enabled )
					$this->p->debug->log( 'filter for '.$filter.' added', 2 );
			}
		}

		public function get_image_size_label( $size_name ) {	// wpsso-opengraph
			if ( ! empty( $this->size_labels[$size_name] ) )
				return $this->size_labels[$size_name];
			else return $size_name;
		}

		// called directly (with or without a post ID), or from the 'wp' action ($post_id will be an object)
		public function add_plugin_image_sizes( $post_id = false, $sizes = array(), $filter = true ) {
			/*
			 * allow various plugin extensions to provide their image names, labels, etc.
			 * the first dimension array key is the option name prefix by default
			 * you can also include the width, height, crop, crop_x, and crop_y values
			 *
			 *	Array (
			 *		[rp_img] => Array (
			 *			[name] => richpin
			 *			[label] => Rich Pin Image Dimensions
			 *		) 
			 *		[og_img] => Array (
			 *			[name] => opengraph
			 *			[label] => Open Graph Image Dimensions
			 *		)
			 *	)
			 */
			if ( $filter === true )
				$sizes = apply_filters( $this->p->cf['lca'].'_plugin_image_sizes', $sizes, $post_id );
			$meta_opts = array();
			$debug_enabled = $this->p->debug->is_on();	// optimize debug logging in loops

			// allow custom post meta to override the image size options
			// get the post meta if we can determine a post_id
			if ( isset( $this->p->mods['util']['postmeta'] ) ) {
				// $post_id may be false, or an object
				if ( ! is_numeric( $post_id ) && is_singular() ) {
					$obj = $this->get_post_object();
					$post_id = empty( $obj->ID ) || 
						empty( $obj->post_type ) ? 0 : $obj->ID;
				}
				// on non-singular pages, $post_id may be an object here
				if ( is_numeric( $post_id ) && $post_id > 0 ) {
					if ( $debug_enabled )
						$this->p->debug->log( 'reading custom meta for post id '.$post_id );
					$meta_opts = $this->p->mods['util']['postmeta']->get_options( $post_id );
				} 
			}

			foreach( $sizes as $opt_prefix => $size_info ) {
				if ( ! is_array( $size_info ) ) {
					$save_name = empty( $size_info ) ? $opt_prefix : $size_info;
					$size_info = array( 'name' => $save_name, 'label' => $save_name );
				} elseif ( ! empty( $size_info['prefix'] ) )					// allow for alternate option prefix
					$opt_prefix = $size_info['prefix'];

				foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $key ) {
					if ( isset( $size_info[$key] ) )					// prefer existing info from filters
						continue;
					elseif ( isset( $meta_opts[$opt_prefix.'_'.$key] ) )			// use post meta if available
						$size_info[$key] = $meta_opts[$opt_prefix.'_'.$key];
					elseif ( isset( $this->p->options[$opt_prefix.'_'.$key] ) )		// current plugin settings
						$size_info[$key] = $this->p->options[$opt_prefix.'_'.$key];
					else {
						if ( ! isset( $def_opts ) )					// only read once if necessary
							$def_opts = $this->p->opt->get_defaults();
						$size_info[$key] = $def_opts[$opt_prefix.'_'.$key];		// fallback to default value
					}

					if ( $key === 'crop' )							// make sure crop is true or false
						$size_info[$key] = empty( $size_info[$key] ) ? false : true;
				}
				if ( $size_info['width'] > 0 && $size_info['height'] > 0 ) {
					// preserve compatibility with older wordpress versions, use true or false when possible
					if ( $size_info['crop'] === true && 
						( $size_info['crop_x'] !== 'center' || $size_info['crop_y'] !== 'center' ) ) {

						global $wp_version;
						if ( ! version_compare( $wp_version, 3.9, '<' ) )
							$size_info['crop'] = array( $size_info['crop_x'], $size_info['crop_y'] );
					}
					// allow custom function hooks to make changes
					if ( $filter === true )
						$size_info = apply_filters( $this->p->cf['lca'].'_size_info_'.$size_info['name'], 
							$size_info, $post_id );

					// a lookup array for image size labels, used in image size error messages
					$this->size_labels[$this->p->cf['lca'].'-'.$size_info['name']] = $size_info['label'];

					add_image_size( $this->p->cf['lca'].'-'.$size_info['name'], 
						$size_info['width'], $size_info['height'], $size_info['crop'] );

					if ( $debug_enabled )
						$this->p->debug->log( 'image size '.$this->p->cf['lca'].'-'.$size_info['name'].' '.
							$size_info['width'].'x'.$size_info['height'].
							( empty( $size_info['crop'] ) ? '' : ' crop '.
								$size_info['crop_x'].'/'.$size_info['crop_y'] ).' added' );
				}
			}
		}

		public function push_add_to_options( &$opts = array(), $add_to_prefixes = array( 'plugin' => 'backend' ) ) {
			foreach ( $add_to_prefixes as $opt_prefix => $type ) {
				foreach ( $this->get_post_types( $type ) as $post_type ) {
					$option_name = $opt_prefix.'_add_to_'.$post_type->name;
					$filter_name = $this->p->cf['lca'].'_add_to_options_'.$post_type->name;
					if ( ! isset( $opts[$option_name] ) )
						$opts[$option_name] = apply_filters( $filter_name, 1 );
				}
			}
			return $opts;
		}

		public function get_post_types( $type = 'frontend', $output = 'objects' ) {
			switch ( $type ) {
				case 'frontend':
					$post_types = get_post_types( array( 'public' => true ), $output );
					break;
				case 'backend':
					$post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), $output );
					break;
				default:
					$post_types = array();
					break;
			}
			return apply_filters( $this->p->cf['lca'].'_post_types', $post_types, $type, $output );
		}

		public function flush_post_cache( $post_id ) {
			switch ( get_post_status( $post_id ) ) {
				case 'draft':
				case 'pending':
				case 'future':
				case 'private':
				case 'publish':
					$lang = SucomUtil::get_locale();
					$cache_type = 'object cache';
					$sharing_url = $this->p->util->get_sharing_url( $post_id );
					$permalink_no_meta = add_query_arg( array( 'WPSSO_META_TAGS_DISABLE' => 1 ), get_permalink( $post_id ) );
	
					$transients = array(
						'SucomCache::get' => array(
							'url:'.$permalink_no_meta,
						),
						'WpssoHead::get_header_array' => array( 
							'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url,
							'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url.'_crawler:pinterest',
						),
					);
					$transients = apply_filters( $this->p->cf['lca'].'_post_cache_transients', 
						$transients, $post_id, $lang, $sharing_url );
	
					$objects = array(
						'SucomWebpage::get_content' => array(
							'lang:'.$lang.'_post:'.$post_id.'_filtered',
							'lang:'.$lang.'_post:'.$post_id.'_unfiltered',
						),
						'SucomWebpage::get_hashtags' => array(
							'lang:'.$lang.'_post:'.$post_id,
						),
					);
					$objects = apply_filters( $this->p->cf['lca'].'_post_cache_objects', 
						$objects, $post_id, $lang, $sharing_url );
	
					$deleted = $this->flush_cache_objects( $transients, $objects );
					if ( ! empty( $this->p->options['plugin_cache_info'] ) && $deleted > 0 )
						$this->p->notice->inf( $deleted.' items removed from the WordPress object and transient caches.', true );

					if ( function_exists( 'w3tc_pgcache_flush_post' ) )	// w3 total cache
						w3tc_pgcache_flush_post( $post_id );
					elseif ( function_exists( 'wp_cache_post_change' ) )	// wp super cache
						wp_cache_post_change( $post_id );

					break;
			}
		}

		public function flush_cache_objects( &$transients = array(), &$objects = array() ) {
			$deleted = 0;
			$debug_enabled = $this->p->debug->is_on();	// optimize debug logging in loops
			foreach ( $transients as $group => $arr ) {
				foreach ( $arr as $val ) {
					if ( ! empty( $val ) ) {
						$cache_salt = $group.'('.$val.')';
						$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
						if ( delete_transient( $cache_id ) ) {
							if ( $debug_enabled )
								$this->p->debug->log( 'flushed transient cache salt: '. $cache_salt );
							$deleted++;
						}
					}
				}
			}
			foreach ( $objects as $group => $arr ) {
				foreach ( $arr as $val ) {
					if ( ! empty( $val ) ) {
						$cache_salt = $group.'('.$val.')';
						$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
						if ( wp_cache_delete( $cache_id, $group ) ) {
							if ( $debug_enabled )
								$this->p->debug->log( 'flushed object cache salt: '. $cache_salt );
							$deleted++;
						}
					}
				}
			}
			return $deleted;
		}

		public function get_topics() {
			if ( $this->p->is_avail['cache']['transient'] ) {
				$cache_salt = __METHOD__.'('.WPSSO_TOPICS_LIST.')';
				$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
				$cache_type = 'object cache';
				$this->p->debug->log( $cache_type.': transient salt '.$cache_salt );
				$topics = get_transient( $cache_id );
				if ( is_array( $topics ) ) {
					$this->p->debug->log( $cache_type.': topics array retrieved from transient '.$cache_id );
					return $topics;
				}
			}
			if ( ( $topics = file( WPSSO_TOPICS_LIST, 
				FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) ) === false ) {
				$this->p->notice->err( 'Error reading <u>'.WPSSO_TOPICS_LIST.'</u>.' );
				return $topics;
			}
			$topics = apply_filters( $this->p->cf['lca'].'_topics', $topics );
			natsort( $topics );
			$topics = array_merge( array( 'none' ), $topics );	// after sorting the array, put 'none' first

			if ( ! empty( $cache_id ) ) {
				set_transient( $cache_id, $topics, $this->p->cache->object_expire );
				$this->p->debug->log( $cache_type.': topics array saved to transient '.
					$cache_id.' ('.$this->p->cache->object_expire.' seconds)');
			}
			return $topics;
		}

		public function sanitize_option_value( $key, $val, $def_val, $opts_type = false ) {
			$option_type = apply_filters( $this->p->cf['lca'].'_option_type', false, $key, $opts_type );
			$reset_msg = __( 'resetting the option to its default value.', WPSSO_TEXTDOM );

			// pre-filter most values to remove html
			switch ( $option_type ) {
				case 'html':	// leave html and css / javascript code blocks as-is
				case 'code':
					break;
				default:
					$val = stripslashes( $val );
					$val = wp_filter_nohtml_kses( $val );
					$val = htmlentities( $val, ENT_QUOTES, get_bloginfo( 'charset' ), false );	// double_encode = false
					break;
			}

			switch ( $option_type ) {
				case 'at_name':		// twitter-style usernames (prepend with an at)
					if ( $val !== '' ) {
						$val = substr( preg_replace( '/[^a-zA-Z0-9_]/', '', $val ), 0, 15 );
						if ( ! empty( $val ) ) 
							$val = '@'.$val;
					}
					break;
				case 'url_base':	// strip leading urls off facebook usernames
					if ( $val !== '' ) {
						$val = $this->cleanup_html_tags( $val );
						$val = preg_replace( '/(http|https):\/\/[^\/]*?\//', '', $val );
					}
					break;
				case 'url':		// must be a url
					if ( $val !== '' ) {
						$val = $this->cleanup_html_tags( $val );
						if ( strpos( $val, '//' ) === false ) {
							$this->p->notice->err( 'The value of option \''.$key.'\' must be a URL'.' - '.$reset_msg, true );
							$val = $def_val;
						}
					}
					break;
				case 'numeric':		// must be numeric (blank or zero is ok)
					if ( $val !== '' && ! is_numeric( $val ) ) {
						$this->p->notice->err( 'The value of option \''.$key.'\' must be numeric'.' - '.$reset_msg, true );
						$val = $def_val;
					}
					break;
				case 'pos_num':		// integer options that must be 1 or more (not zero)
				case 'img_dim':		// image dimensions, subject to minimum value (typically, at least 200px)
					if ( $option_type == 'img_dim' )
						$min_int = empty( $this->p->cf['head']['min_img_dim'] ) ? 
							200 : $this->p->cf['head']['min_img_dim'];
					else $min_int = 1;

					if ( $val === '' && $opts_type !== false )	// custom options allowed to have blanks
						break;
					elseif ( ! is_numeric( $val ) || $val < $min_int ) {
						$this->p->notice->err( 'The value of option \''.$key.'\' must be greater or equal to '.$min_int.' - '.$reset_msg, true );
						$val = $def_val;
					}
					break;
				case 'textured':	// must be texturized 
					if ( $val !== '' )
						$val = trim( wptexturize( ' '.$val.' ' ) );
					break;
				case 'anu_case':	// must be alpha-numeric uppercase (hyphens and periods allowed as well)
					$val = trim( $val );
					if ( $val !== '' && preg_match( '/[^A-Z0-9\-\.]/', $val ) ) {
						$this->p->notice->err( '\''.$val.'\' is not an accepted value for option \''.$key.'\''.' - '.$reset_msg, true );
						$val = $def_val;
					}
					break;
				case 'ok_blank':	// text strings that can be blank
				case 'html':
					if ( $val !== '' )
						$val = trim( $val );
					break;
				case 'not_blank':	// options that cannot be blank
				case 'code':
					if ( $val === '' ) {
						$this->p->notice->err( 'The value of option \''.$key.'\' cannot be empty'.' - '.$reset_msg, true );
						$val = $def_val;
					}
					break;
				case 'checkbox':	// everything else is a 1 of 0 checkbox option 
				default:
					if ( $def_val === 0 || $def_val === 1 )	// make sure the default option is also a 1 or 0, just in case
						$val = empty( $val ) ? 0 : 1;
					break;
			}
			return $val;
		}

		// query examples:
		//	/html/head/link|/html/head/meta
		//	/html/head/meta[starts-with(@property, 'og:video:')]
		public function get_head_meta( $url, $query = '/html/head/meta', $include_self = true ) {
			if ( empty( $query ) )
				return false;
			if ( ( $html = $this->p->cache->get( $url, 'raw', 'transient' ) ) === false )
				return false;
			if ( $include_self !== true &&
				strpos( $html, '<!-- '.$this->p->cf['lca'].' meta tags begin -->' ) !== false )
					$html = preg_replace( '/<!-- '.$this->p->cf['lca'].' meta tags begin -->.*<!-- '.
						$this->p->cf['lca'].' meta tags end -->/ms', '', $html );
			$doc = new DomDocument();		// since PHP v4.1.0
			@$doc->loadHTML( $html );		// suppress parsing errors
			$xpath = new DOMXPath( $doc );
			$metas = $xpath->query( $query );
			$ret = array();
			foreach ( $metas as $m ) {
				$attrs = array();		// put all attributes in a single array
				foreach ( $m->attributes as $a )
					$attrs[$a->name] = $a->value;
				$ret[$m->tagName][] = $attrs;
			}
			return $ret;
		}
	}
}

?>
