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

			// add various function test results top-most in the debug log
			// hook into wpsso_is_functions to extend the default array of function names
			if ( $this->p->debug->enabled ) {
				$is_functions = array( 
					'is_ajax',
					'is_archive',
					'is_attachment',
					'is_author',
					'is_category',
					'is_front_page',
					'is_home',
					'is_multisite',
					'is_page',
					'is_search',
					'is_single',
					'is_singular',
					'is_ssl',
					'is_tag',
					'is_tax',
					//'is_term',	// deprecated since wp 3.0
					/*
					 * e-commerce / woocommerce functions
					 */
					'is_account_page',
					'is_cart',
					'is_checkout',
					'is_checkout_pay_page',
					'is_product',
					'is_product_category',
					'is_product_tag',
					'is_shop',
				);
				$is_functions = apply_filters( $lca.'_is_functions', $is_functions );
				foreach ( $is_functions as $function ) 
					if ( function_exists( $function ) && $function() )
						$this->p->debug->log( $function.'() = true' );
			}

			if ( $this->p->is_avail['metatags'] )
				echo $this->get_header_html( apply_filters( $lca.'_header_use_post', false ) );
			else echo "\n<!-- ".$lca." meta tags disabled -->\n";

			// include additional information when debug mode is on
			if ( $this->p->debug->enabled ) {
				$defined_constants = get_defined_constants( true );
				$defined_constants['user']['WPSSO_NONCE'] = '********';
				$this->p->debug->show_html( SucomUtil::preg_grep_keys( '/^WPSSO_/', $defined_constants['user'] ), 'wpsso constants' );

				$opts = $this->p->options;
				foreach ( $opts as $key => $val ) {
					switch ( true ) {
						case ( strpos( $key, '_css_' ) !== false ):
						case ( strpos( $key, '_js_' ) !== false ):
						case ( preg_match( '/_(key|tid)$/', $key ) ):
							$opts[$key] = '********';
					}
				}
				$this->p->debug->show_html( print_r( $this->p->is_avail, true ), 'available features' );
				$this->p->debug->show_html( print_r( WpssoUtil::active_plugins(), true ), 'active plugins' );
				$this->p->debug->show_html( null, 'debug log' );
				$this->p->debug->show_html( $opts, 'wpsso settings' );

				// on singular webpages, show the custom social settings
				if ( is_singular() && ( $obj = $this->p->util->get_post_object() ) !== false ) {

					$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ? 0 : $obj->ID;

					if ( ! empty( $post_id ) && isset( $this->p->mods['util']['postmeta'] ) ) {
						$meta_opts = $this->p->mods['util']['postmeta']->get_options( $post_id );
						$this->p->debug->show_html( $meta_opts, 'wpsso post meta options for post id '.$post_id );
					}
				}
			}	// end of debug information
		}

		public function extract_post_info( &$header_tags, &$post_info = array() ) {
			$vid = array();
			$img = array();
			foreach ( $header_tags as $tag ) {
				if ( ! isset( $tag[2] ) )
					continue;
				switch ( $tag[2] ) {
					case 'name':
						if ( isset( $tag[3] ) && 
							$tag[3] === 'author' )
								$post_info['author'] = $tag[5];
						break;
					case 'property':
						if ( isset( $tag[3] ) && 
							strpos( $tag[3], 'og:' ) === 0 &&
							preg_match( '/^og:(description|title|type)$/', $tag[3], $key ) )
								$post_info[$key[0]] = $tag[5];
						elseif ( isset( $tag[6] ) && 
							$tag[6] === 'og:video:1' &&
							strpos( $tag[3], 'og:image' ) === 0 )
								$vid[$tag[3]] = $tag[5];
						elseif ( isset( $tag[6] ) && 
							$tag[6] === 'og:image:1' &&
							strpos( $tag[3], 'og:image' ) === 0 )
								$img[$tag[3]] = $tag[5];
						break;
				}
			}
			if ( ! empty( $vid ) )	// video meta tags have precedence
				$post_info['og_image'] = $vid;
			else $post_info['og_image'] = $img;

			return $post_info;
		}

		public function get_header_html( $use_post = false, $read_cache = true, &$meta_og = array() ) {
			$comment = $this->p->cf['lca'].' meta tags';
			$html = "\n\n".'<!-- '.$comment.' begin --><meta name="comment" content="'.$comment.' begin"/>'."\n";
			foreach ( $this->get_header_array( $use_post, $read_cache, $meta_og ) as $meta )
				if ( ! empty( $meta[0] ) )	// first element of the array should be a complete html tag
					$html .= $meta[0];
			$html .= '<meta name="comment" content="'.$comment.' end"/><!-- '.$comment.' end -->'."\n\n";
			return $html;
		}

		public function get_header_array( $use_post = false, $read_cache = true, &$meta_og = array() ) {
			$lca = $this->p->cf['lca'];
			$short_aop = $this->p->cf['plugin'][$lca]['short'].
				( $this->p->is_avail['aop'] ? ' Pro' : '' );

			$obj = $this->p->util->get_post_object( $use_post );

			$post_id = empty( $obj->ID ) || empty( $obj->post_type ) || 
				( ! is_singular() && $use_post === false ) ? 0 : $obj->ID;

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'use_post/post_id values: '.( $use_post === false ? 'false' : 
					( $use_post === true ? 'true' : $use_post ) ).'/'.$post_id );

			$sharing_url = $this->p->util->get_sharing_url( $use_post );
			$author_id = false;

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

			} elseif ( is_author() || ( is_admin() && 
				( $screen = get_current_screen() ) && 
				( $screen->id === 'user-edit' || $screen->id === 'profile' ) ) ) {

				$author_id = $this->p->util->get_author_object( 'id' );

			} elseif ( ( ! ( is_singular() || $use_post !== false ) && ! is_search() && 
				! empty( $this->p->options['seo_def_author_on_index'] ) && 
				! empty( $this->p->options['seo_def_author_id'] ) ) || ( is_search() && 
				! empty( $this->p->options['seo_def_author_on_search'] ) && 
				! empty( $this->p->options['seo_def_author_id'] ) ) )
					$author_id = $this->p->options['seo_def_author_id'];

			if ( $this->p->debug->enabled && $author_id !== false )
				$this->p->debug->log( 'author_id value: '.$author_id );

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
			if ( isset( $this->p->options['seo_author_name'] ) && 
				$this->p->options['seo_author_name'] !== 'none' )
					$meta_name['author'] = $this->p->mods['util']['user']->get_author_name( $author_id, 
						$this->p->options['seo_author_name'] );

			$meta_name['description'] = $this->p->webpage->get_description( $this->p->options['seo_desc_len'], 
				'...', $use_post, true, false, true, 'seo_desc' );	// add_hashtags = false

			if ( ! empty( $this->p->options['rp_dom_verify'] ) )
				$meta_name['p:domain_verify'] = $this->p->options['rp_dom_verify'];

			$meta_name = apply_filters( $lca.'_meta_name', $meta_name, $use_post, $obj );

			/**
			 * Link relation tags
			 */
			$link_rel = array();

			if ( ! empty( $author_id ) )
				$link_rel['author'] = $this->p->mods['util']['user']->get_author_website_url( $author_id, 
					$this->p->options['seo_author_field'] );

			if ( ! empty( $this->p->options['seo_publisher_url'] ) )
				$link_rel['publisher'] = $this->p->options['seo_publisher_url'];

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
						( $this->p->check->aop() ? 'L' : 
							( $this->p->is_avail['aop'] ? 'U' : 'G' ) ), '', $use_post ),
				$this->get_tag_array( 'link', 'rel', $link_rel, $use_post ),
				$this->get_tag_array( 'meta', 'name', $meta_name, $use_post ),
				$this->get_tag_array( 'meta', 'property', $meta_og, $use_post ),
				$this->get_tag_array( 'meta', 'itemprop', $meta_schema, $use_post ),
				SucomUtil::a2aa( $this->p->schema->get_json_array( $author_id ) )
			);

			/**
			 * Save the header array to the WordPress transient cache
			 */
			if ( apply_filters( $lca.'_header_set_cache', $this->p->is_avail['cache']['transient'] ) ) {
				set_transient( $cache_id, $header_array, $this->p->cache->object_expire );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $cache_type.': header array saved to transient '.
						$cache_id.' ('.$this->p->cache->object_expire.' seconds)');
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
			foreach ( $tag_array as $f_name => $f_val ) {					// 1st-dimension array (associative)
				if ( is_array( $f_val ) ) {
					foreach ( $f_val as $s_num => $s_val ) {			// 2nd-dimension array
						if ( SucomUtil::is_assoc( $s_val ) ) {
							ksort( $s_val );
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
			if ( $tag === 'meta' && $type === 'property' && 
				( $name === 'og:image' || $name === 'og:video' ) && 
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
			
			$html_tag = '';
			if ( empty( $this->p->options['add_'.$tag.'_'.$type.'_'.$name] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $log_pre.' is disabled (skipped)' );
			} else $html_tag = $html_prefix.'<'.$tag.' '.$type.'="'.$name.'" '.$attr.'="'.$value.'"/>'."\n";
			
			$ret[] = array( $html_tag, $tag, $type, $name, $attr, $value, $comment );

			return $ret;
		}
	}
}

?>
