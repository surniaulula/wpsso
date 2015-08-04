<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoHead' ) ) {

	class WpssoHead {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'head_cache_salt' => 2,		// modify the cache salt for certain crawlers
			) );
			add_action( 'wp_head', array( &$this, 'add_header' ), WPSSO_HEAD_PRIORITY );
		}

		public function filter_head_cache_salt( $salt, $use_post = false ) {
			switch ( SucomUtil::crawler_name() ) {
				case 'pinterest':
					$salt .= '_crawler:'.SucomUtil::crawler_name();
					break;
			}
			return $salt;
		}

		// called by wp_head action
		public function add_header() {
			$lca = $this->p->cf['lca'];

			if ( $this->p->debug->enabled )
				$this->p->util->log_is_functions();

			if ( $this->p->is_avail['metatags'] )
				echo $this->get_header_html( apply_filters( $lca.'_header_use_post', false ) );
			else echo "\n<!-- ".$lca." meta tags disabled -->\n";

			// include additional information when debug mode is on
			if ( $this->p->debug->enabled ) {

				// show debug log
				$this->p->debug->show_html( null, 'debug log' );

				// show constants
				$defined_constants = get_defined_constants( true );
				$defined_constants['user']['WPSSO_NONCE'] = '********';
				if ( is_multisite() )
					$this->p->debug->show_html( SucomUtil::preg_grep_keys( '/^(MULTISITE|^SUBDOMAIN_INSTALL|.*_SITE)$/', 
						$defined_constants['user'] ), 'multisite constants' );
				$this->p->debug->show_html( SucomUtil::preg_grep_keys( '/^WPSSO_/',
					$defined_constants['user'] ), 'wpsso constants' );

				// show active plugins
				$this->p->debug->show_html( print_r( WpssoUtil::active_plugins(), true ), 'active plugins' );

				// show available modules
				$this->p->debug->show_html( print_r( $this->p->is_avail, true ), 'available features' );

				// show all plugin options
				$opts = $this->p->options;
				foreach ( $opts as $key => $val ) {
					switch ( true ) {
						case ( strpos( $key, '_html' ) !== false ):
						case ( strpos( $key, '_css_' ) !== false ):
						case ( strpos( $key, '_js_' ) !== false ):
						case ( preg_match( '/_(key|tid)$/', $key ) ):
							$opts[$key] = '********';
					}
				}
				$this->p->debug->show_html( $opts, 'wpsso settings' );

			}	// end of debug information
		}

		// extract certain key fields for reference and sanity checks
		public function extract_head_info( &$head_meta_tags, &$head_info = array() ) {
			foreach ( $head_meta_tags as $tag ) {
				if ( ! isset( $tag[2] ) || 
					! isset( $tag[3] ) )
						continue;
				// any time we're outside an og:image block, set $first_image to false
				if ( strpos( $tag[3], 'og:image' ) !== 0 )
					$first_image = false;
				switch ( $tag[2].'-'.$tag[3] ) {
					case 'property-og:image':
					case 'property-og:image:secure_url':
						if ( $first_image === false &&
							( isset( $head_info['og:image'] ) || 
								isset( $head_info['og:image:secure_url'] ) ) )
									continue;
						else {
							$head_info[$tag[3]] = $tag[5];	// save the meta tag value
							$first_image = true;
						}
						break;
					case 'property-og:image:width':
					case 'property-og:image:height':
						if ( $first_image === true )
							$head_info[$tag[3]] = $tag[5];	// save the meta tag value
						break;
					case 'name-author':
					case 'property-og:description':
					case 'property-og:title':
					case 'property-og:type':
						if ( ! isset( $head_info[$tag[3]] ) )
							$head_info[$tag[3]] = $tag[5];	// save the meta tag value
						break;
				}
			}
			return $head_info;
		}

		public function get_header_html( $use_post = false, $read_cache = true, &$meta_og = array() ) {
			$cmt = $this->p->cf['lca'].' meta tags ';
			$html = "\n\n".'<!-- '.$cmt.'begin -->'."\n";
			if ( ! empty( $this->p->options['plugin_check_head'] ) )
				$html .= '<meta name="'.$this->p->cf['lca'].':comment" content="'.$cmt.'begin"/>'."\n";

			foreach ( $this->get_header_array( $use_post, $read_cache, $meta_og ) as $meta )
				if ( ! empty( $meta[0] ) )	// first element of the array should be a complete html tag
					$html .= $meta[0];

			if ( ! empty( $this->p->options['plugin_check_head'] ) )
				$html .= '<meta name="'.$this->p->cf['lca'].':comment" content="'.$cmt.'end"/>'."\n";
			$html .= '<!-- '.$cmt.'end -->'."\n\n";

			return $html;
		}

		public function get_header_array( $use_post = false, $read_cache = true, &$meta_og = array() ) {
			$lca = $this->p->cf['lca'];
			$short_aop = $this->p->cf['plugin'][$lca]['short'].
				( $this->p->is_avail['aop'] ? ' Pro' : '' );

			$obj = $this->p->util->get_post_object( $use_post );
			$post_id = empty( $obj->ID ) || empty( $obj->post_type ) || 
				( ! is_singular() && $use_post === false ) ? 0 : $obj->ID;
			$sharing_url = $this->p->util->get_sharing_url( $use_post );
			$author_id = false;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'use_post: '.( $use_post === false ? 'false' : ( $use_post === true ? 'true' : $use_post ) ) );
				$this->p->debug->log( 'post_id: '.$post_id );
				$this->p->debug->log( 'obj post_type: '.( empty( $obj->post_type ) ? '' : $obj->post_type ) );
				$this->p->debug->log( 'sharing url: '.$sharing_url );
			}

			$header_array = array();
			if ( $this->p->is_avail['cache']['transient'] ) {
				$cache_salt = __METHOD__.'('.apply_filters( $lca.'_head_cache_salt', 
					'lang:'.SucomUtil::get_locale().'_post:'.$post_id.'_url:'.$sharing_url, $use_post ).')';
				$cache_id = $lca.'_'.md5( $cache_salt );
				$cache_type = 'object cache';
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $cache_type.': transient salt '.$cache_salt );
				if ( apply_filters( $lca.'_header_read_cache', $read_cache ) ) {
					$header_array = get_transient( $cache_id );
					if ( $header_array !== false ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $cache_type.': header array retrieved from transient '.$cache_id );
						return $header_array;
					}
				}
			}

			/**
			 * Define an author_id, if one is available
			 */
			if ( is_singular() || $use_post !== false ) {
				if ( ! empty( $obj->post_author ) )
					$author_id = $obj->post_author;
				elseif ( ! empty( $this->p->options['seo_def_author_id'] ) )
					$author_id = $this->p->options['seo_def_author_id'];

			} elseif ( SucomUtil::is_author_page() ) {
				$author_id = $this->p->util->get_author_object( 'id' );

			} elseif ( $this->p->util->force_default_author( $use_post, 'seo' ) )
				$author_id = $this->p->options['seo_def_author_id'];

			if ( $this->p->debug->enabled && $author_id !== false )
				$this->p->debug->log( 'author_id: '.$author_id );

			/**
			 * Open Graph, Twitter Card
			 *
			 * The Twitter Card meta tags are added by the 
			 * WpssoHeadTwittercard class using an 'wpsso_og' filter hook.
			 */
			$meta_og = $this->p->og->get_array( $meta_og, $use_post, $obj );

			/**
			 * Name / SEO meta tags
			 */
			$meta_name = array();
			if ( ! empty( $this->p->options['add_meta_name_author'] ) ) {
				if ( isset( $this->p->options['seo_author_name'] ) && 
					$this->p->options['seo_author_name'] !== 'none' )
						$meta_name['author'] = $this->p->mods['util']['user']->get_author_name( $author_id, 
							$this->p->options['seo_author_name'] );
			}

			if ( ! empty( $this->p->options['add_meta_name_canonical'] ) )
				$meta_name['canonical'] = $sharing_url;

			if ( ! empty( $this->p->options['add_meta_name_description'] ) )
				$meta_name['description'] = $this->p->webpage->get_description( $this->p->options['seo_desc_len'], 
					'...', $use_post, true, false, true, 'seo_desc' );	// add_hashtags = false

			if ( ! empty( $this->p->options['add_meta_name_p:domain_verify'] ) ) {
				if ( ! empty( $this->p->options['rp_dom_verify'] ) )
					$meta_name['p:domain_verify'] = $this->p->options['rp_dom_verify'];
			}

			$meta_name = apply_filters( $lca.'_meta_name', $meta_name, $use_post, $obj );

			/**
			 * Link relation tags
			 */
			$link_rel = array();

			if ( ! empty( $this->p->options['add_link_rel_author'] ) ) {
				if ( ! empty( $author_id ) )
					$link_rel['author'] = $this->p->mods['util']['user']->get_author_website_url( $author_id, 
						$this->p->options['seo_author_field'] );
			}

			if ( ! empty( $this->p->options['add_link_rel_publisher'] ) ) {
				if ( ! empty( $this->p->options['seo_publisher_url'] ) )
					$link_rel['publisher'] = $this->p->options['seo_publisher_url'];
			}

			$link_rel = apply_filters( $lca.'_link_rel', $link_rel, $use_post, $obj );

			/**
			 * Schema meta tags
			 */
			$meta_schema = $this->p->schema->get_meta_array( $use_post, $obj, $meta_og );

			/**
			 * Combine and return all meta tags
			 */
			$comment = $this->p->cf['lca'].' meta tags';
			$header_array = array_merge(
				$this->get_single_tag( 'meta', 'name', 'generator',
					$short_aop.' '.$this->p->cf['plugin'][$lca]['version'].
					( $this->p->check->aop() ? 'L' : ( $this->p->is_avail['aop'] ? 'U' : 'G' ) ).
					( $this->p->is_avail['util']['um'] ? ' +' : ' -' ).'UM', '', $use_post ),
				$this->get_tag_array( 'link', 'rel', $link_rel, $use_post ),
				$this->get_tag_array( 'meta', 'property', $meta_og, $use_post ),
				$this->get_tag_array( 'meta', 'itemprop', $meta_schema, $use_post ),
				$this->get_tag_array( 'meta', 'name', $meta_name, $use_post ),		// seo description is last
				SucomUtil::a2aa( $this->p->schema->get_json_array( $post_id, $author_id,
					$this->p->cf['lca'].'-schema' ) )
			);

			/**
			 * Save the header array to the WordPress transient cache
			 */
			if ( apply_filters( $lca.'_header_set_cache', $this->p->is_avail['cache']['transient'] ) ) {
				set_transient( $cache_id, $header_array, $this->p->options['plugin_object_cache_exp'] );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $cache_type.': header array saved to transient '.
						$cache_id.' ('.$this->p->options['plugin_object_cache_exp'].' seconds)');
			}
			return $header_array;
		}

		/**
		 * Loops through the arrays (1 to 3 dimensions) and calls get_single_tag() for each
		 */
		private function get_tag_array( $tag = 'meta', $type = 'property', $tag_array, $use_post = false ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( count( $tag_array ).' '.$tag.' '.$type.' to process' );
				$this->p->debug->log( $tag_array );
			}
			$ret = array();
			if ( empty( $tag_array ) )
				return $ret;
			elseif ( ! is_array( $tag_array ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: tag_array argument is not an array' );
				return $ret;
			}
			foreach ( $tag_array as $f_name => $f_val ) {					// 1st-dimension array (associative)
				if ( is_array( $f_val ) ) {
					foreach ( $f_val as $s_num => $s_val ) {			// 2nd-dimension array
						if ( SucomUtil::is_assoc( $s_val ) ) {
							foreach ( $s_val as $t_name => $t_val )		// 3rd-dimension array (associative)
								$ret = array_merge( $ret, $this->get_single_tag( $tag, $type, 
									$t_name, $t_val, $f_name.':'.( $s_num + 1 ), $use_post ) );
						} else $ret = array_merge( $ret, $this->get_single_tag( $tag, $type, 
							$f_name, $s_val, $f_name.':'.( $s_num + 1 ), $use_post ) );
					}
				} else $ret = array_merge( $ret, $this->get_single_tag( $tag, $type, 
					$f_name, $f_val, '', $use_post ) );
			}
			return $ret;
		}

		private function get_single_tag( $tag = 'meta', $type = 'property', $name, $value = '', $comment = '', $use_post = false ) {

			// known exceptions for the 'property' $type
			if ( $tag === 'meta' && $type === 'property' && 
				( strpos( $name, 'twitter:' ) === 0 || strpos( $name, ':' ) === false ) )
					$type = 'name';

			$ret = array();
			$attr = $tag === 'link' ? 'href' : 'content';
			$log_pre = $tag.' '.$type.' '.$name;

			if ( $value === '' || $value === null ) {	// allow for 0
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_pre.' value is empty (skipped)' );
				return $ret;

			} elseif ( $value === -1 ) {	// -1 is reserved, meaning use the defaults - exclude, just in case
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_pre.' value is -1 (skipped)' );
				return $ret;

			} elseif ( is_array( $value ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_pre.' value is an array (skipped)' );
				return $ret;

			} elseif ( is_object( $value ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_pre.' value is an object (skipped)' );
				return $ret;
			}

			if ( strpos( $value, '%%' ) )
				$value = $this->p->util->replace_inline_vars( $value, $use_post );

			$charset = get_bloginfo( 'charset' );
			$value = htmlentities( $value, ENT_QUOTES, $charset, false );	// double_encode = false
			if ( $this->p->debug->enabled )
				$this->p->debug->log( $log_pre.' = "'.$value.'"' );
			$html_prefix = empty( $comment ) ? '' : '<!-- '.$comment.' -->';

			// add an additional secure_url meta tag for open graph images and videos
			if ( $tag === 'meta' && 
				$type === 'property' && 
				( $name === 'og:image' || $name === 'og:video:url' ) && 
				strpos( $value, 'https:' ) === 0 ) {

				$html_tag = '';
				$secure_url = $value;
				$value = preg_replace( '/^https:/', 'http:', $value );

				if ( empty( $this->p->options['add_'.$tag.'_'.$type.'_'.$name.':secure_url'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.':secure_url is disabled (skipped)' );
				} else $html_tag = $html_prefix.'<'.$tag.' '.$type.'="'.$name.':secure_url" '.$attr.'="'.$secure_url.'"/>'."\n";

				$ret[] = array( $html_tag, $tag, $type, $name.':secure_url', $attr, $secure_url, $comment );
			}
			
			$filter_name = $this->p->cf['lca'].'_'.$tag.'_'.$type.'_'.$name.'_'.$attr;
			$value = apply_filters( $filter_name, $value, $use_post );

			if ( ! empty( $this->p->options['add_'.$tag.'_'.$type.'_'.$name] ) ) {
				$html_tag = $html_prefix.'<'.$tag.' '.$type.'="'.$name.'" '.$attr.'="'.$value.'"/>'."\n";
			} else {
				$html_tag = '';
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_pre.' is disabled (skipped)' );
			}
			$ret[] = array( $html_tag, $tag, $type, $name, $attr, $value, $comment );

			return $ret;
		}
	}
}

?>
