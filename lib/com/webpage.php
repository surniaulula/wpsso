<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomWebpage' ) ) {

	class SucomWebpage {

		private $p;
		private $shortcode = array();
		private $saved_title = false;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			$this->set_objects();
		}

		private function set_objects() {
			if ( ! empty( $this->p->options['plugin_shortcodes'] ) ) {
				foreach ( $this->p->cf['plugin'] as $lca => $info ) {
					if ( isset( $info['lib']['shortcode'] ) && is_array( $info['lib']['shortcode'] ) ) {
						foreach ( $info['lib']['shortcode'] as $id => $name ) {
							$classname = apply_filters( $lca.'_load_lib', false, 'shortcode/'.$id );
							if ( $classname !== false && class_exists( $classname ) )
								$this->shortcode[$id] = new $classname( $this->p );
						}
					}
				}
			}

			if ( ! empty( $this->p->options['plugin_page_excerpt'] ) )
				add_post_type_support( 'page', array( 'excerpt' ) );

			if ( ! empty( $this->p->options['plugin_page_tags'] ) )
				register_taxonomy_for_object_type( 'post_tag', 'page' );
		}

		// $title is empty for home page, so don't save/restore empty titles
		public function wp_title_save( $title, $separator, $location ) {
			$this->saved_title = trim( $title ) === '' ? false : $title;
			return $title;
		}

		public function wp_title_restore( $title, $separator, $location ) {
			return ( $this->saved_title === false ? $title : $this->saved_title );
		}

		// called from Tumblr class
		public function get_quote() {

			global $post;
			if ( empty( $post ) ) 
				return '';

			$quote = apply_filters( $this->p->cf['lca'].'_quote_seed', '' );

			if ( $quote != '' ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'quote seed = "'.$quote.'"' );
			} else {
				if ( has_excerpt( $post->ID ) ) 
					$quote = get_the_excerpt( $post->ID );
				else $quote = $post->post_content;
			}

			// remove shortcodes, etc., but don't strip html tags
			$quote = $this->p->util->cleanup_html_tags( $quote, false );

			return apply_filters( $this->p->cf['lca'].'_quote', $quote );
		}

		public function get_caption( $type = 'title', $textlen = 200, $use_post = true, $use_cache = true,
			$add_hashtags = true, $encode = true, $md_idx = true, $src_id = '' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->args( array( 
					'type' => $type, 
					'textlen' => $textlen, 
					'use_post' => $use_post, 
					'use_cache' => $use_cache, 
					'add_hashtags' => $add_hashtags,	// true/false/numeric
					'encode' => $encode,
					'md_idx' => $md_idx,
					'src_id' => $src_id,
				) );
			$caption = false;
			$separator = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );

			if ( $md_idx === true ) {
				switch ( $type ) {
					case 'title':
						$md_idx = 'og_title';
						break;
					case 'excerpt':
						$md_idx = 'og_desc';
						break;
					case 'both':
						$md_idx = 'og_caption';
						break;
				}
			}

			if ( is_singular() || $use_post !== false ) {
				if ( ( $obj = $this->p->util->get_post_object( $use_post ) ) === false ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: invalid object type' );
					return $caption;
				}
				$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ? 0 : $obj->ID;
			}

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {
				if ( is_singular() || $use_post !== false ) {
					if ( ! empty( $post_id ) )
						$caption = $this->p->util->get_mod_options( 'post', $post_id, $md_idx );
					if ( ! empty( $caption ) &&
						! empty( $add_hashtags ) && 
							! preg_match( '/( #[a-z0-9\-]+)+$/U', $caption ) ) {
	
						$hashtags = $this->get_hashtags( $post_id, $add_hashtags );
						if ( ! empty( $hashtags ) ) 
							$caption = $this->p->util->limit_text_length( $caption, 
								$textlen - strlen( $hashtags ) - 1, '...', false ).	// don't run cleanup_html_tags()
									' '.$hashtags;
					}
				} elseif ( SucomUtil::is_term_page() ) {
					$term = $this->p->util->get_term_object();
					if ( ! empty( $term->term_id ) )
						$caption = $this->p->util->get_mod_options( 'taxonomy', $term->term_id, $md_idx );
	
				} elseif ( SucomUtil::is_author_page() ) {
					$author = $this->p->util->get_author_object();
					if ( ! empty( $author->ID ) )
						$caption = $this->p->util->get_mod_options( 'user', $author->ID, $md_idx );
				}
			}

			if ( empty( $caption ) ) {
				if ( ! empty( $md_idx ) ) {
					$md_prefix = preg_replace( '/_(title|desc|caption)$/', '', $md_idx );
					$md_title = $md_prefix.'_title';
					$md_desc = $md_prefix.'_desc';
				} else $md_title = $md_desc = $md_idx;

				// request all values un-encoded, then encode once we have the complete caption text
				switch ( $type ) {
					case 'title':
						$caption = $this->get_title( $textlen, '...', $use_post, $use_cache, 
							$add_hashtags, false, $md_title, $src_id );
						break;
					case 'excerpt':
						$caption = $this->get_description( $textlen, '...', $use_post, $use_cache, 
							$add_hashtags, false, $md_desc, $src_id );
						break;
					case 'both':
						$prefix = $this->get_title( 0, '', $use_post, $use_cache, 
							false, false, $md_title, $src_id ).' '.$separator.' ';

						$caption = $prefix.$this->get_description( $textlen - strlen( $prefix ), '...', $use_post, $use_cache, 
							$add_hashtags, false, $md_desc, $src_id );
						break;
				}
			}

			if ( $encode === true )
				$caption = htmlentities( $caption, ENT_QUOTES, get_bloginfo( 'charset' ), false );	// double_encode = false
			else {	// just in case
				$charset = get_bloginfo( 'charset' );
				$caption = html_entity_decode( SucomUtil::decode_utf8( $caption ), ENT_QUOTES, $charset );
			}

			return apply_filters( $this->p->cf['lca'].'_caption', $caption, $use_post, $add_hashtags, $md_idx, $src_id );
		}

		public function get_title( $textlen = 70, $trailing = '', $use_post = false, $use_cache = true,
			$add_hashtags = false, $encode = true, $md_idx = 'og_title', $src_id = '' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->args( array( 
					'textlen' => $textlen, 
					'trailing' => $trailing, 
					'use_post' => $use_post, 
					'use_cache' => $use_cache, 
					'add_hashtags' => $add_hashtags,	// true/false/numeric
					'encode' => $encode,
					'md_idx' => $md_idx,
					'src_id' => $src_id,
				) );
			$title = false;
			$hashtags = '';
			$post_id = 0;
			$paged_suffix = '';
			$separator = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );

			// setup filters to save and restore original / pre-filtered title value
			if ( empty( $this->p->options['plugin_filter_title'] ) ) {
				add_filter( 'wp_title', array( &$this, 'wp_title_save' ), -9000, 3 );
				add_filter( 'wp_title', array( &$this, 'wp_title_restore' ), 9000, 3 );
			}

			if ( is_singular() || $use_post !== false ) {
				if ( ( $obj = $this->p->util->get_post_object( $use_post ) ) === false ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: invalid object type' );
					return $title;
				}
				$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ? 0 : $obj->ID;
			}

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {
				if ( is_singular() || $use_post !== false ) {
					if ( ! empty( $post_id ) )
						$title = $this->p->util->get_mod_options( 'post', $post_id, array( $md_idx, 'og_title' ) );
	
				} elseif ( SucomUtil::is_term_page() ) {
					$term = $this->p->util->get_term_object();
					if ( ! empty( $term->term_id ) )
						$title = $this->p->util->get_mod_options( 'taxonomy', $term->term_id, $md_idx );
	
				} elseif ( SucomUtil::is_author_page() ) {
					$author = $this->p->util->get_author_object();
					if ( ! empty( $author->ID ) )
						$title = $this->p->util->get_mod_options( 'user', $author->ID, $md_idx );
				}
			}
	
			// get seed if no custom meta title
			if ( empty( $title ) ) {
				$title = apply_filters( $this->p->cf['lca'].'_title_seed', '', $use_post, $add_hashtags, $md_idx, $src_id );
				if ( ! empty( $title ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'title seed = "'.$title.'"' );
				}
			}

			// check for hashtags in meta or seed title, remove and then add again after shorten
			if ( preg_match( '/(.*)(( #[a-z0-9\-]+)+)$/U', $title, $match ) ) {
				$title = $match[1];
				$hashtags = trim( $match[2] );
			} elseif ( is_singular() || $use_post !== false ) {
				if ( ! empty( $add_hashtags ) && 
					! empty( $this->p->options['og_desc_hashtags'] ) )
						$hashtags = $this->get_hashtags( $post_id, $add_hashtags );	// add_hashtags = true/false/numeric
			}

			// construct a title of our own
			if ( empty( $title ) ) {
				// $obj and $post_id are defined above, with the same test, so we should be good
				if ( is_singular() || $use_post !== false ) {
					if ( is_singular() ) {
						$title = wp_title( $separator, false, 'right' );
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'is_singular wp_title() = "'.$title.'"' );
					} elseif ( ! empty( $post_id ) ) {
						$title = apply_filters( 'wp_title', get_the_title( $post_id ).' '.$separator.' ', $separator, 'right' );
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'post_id get_the_title() = "'.$title.'"' );
					}

				// by default, use the wordpress title if an seo plugin is available
				} elseif ( $this->p->is_avail['seo']['*'] == true ) {

					// use separator on right for compatibility with aioseo
					$title = wp_title( $separator, false, 'right' );
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'seo wp_title() = "'.$title.'"' );
	
				} elseif ( SucomUtil::is_term_page() ) {
					$term = $this->p->util->get_term_object();
					$title = apply_filters( 'wp_title', $term->name.' '.$separator.' ', $separator, 'right' );

				} elseif ( SucomUtil::is_author_page() ) {
					$author = $this->p->util->get_author_object();
					$title = apply_filters( 'wp_title', $author->display_name.' '.$separator.' ', $separator, 'right' );

				// category title, with category parents
				} elseif ( SucomUtil::is_category_page() ) {
					$title = $this->get_category_title();	// includes parents in title string

				} else {
					/* The title text depends on the query:
					 *	single post = the title of the post 
					 *	date-based archive = the date (e.g., "2006", "2006 - January") 
					 *	category = the name of the category 
					 *	author page = the public name of the user 
					 */
					$title = wp_title( $separator, false, 'right' );
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'default wp_title() = "'.$title.'"' );
				}
	
				// just in case
				if ( empty( $title ) ) {
					$title = get_bloginfo( 'name', 'display' );
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'last resort get_bloginfo() = "'.$title.'"' );
				}
			}

			if ( empty( $this->p->options['plugin_filter_title'] ) ) {
				remove_filter( 'wp_title', array( &$this, 'wp_title_save' ), -9000 );
				remove_filter( 'wp_title', array( &$this, 'wp_title_restore' ), 9000 );
			}

			$title = $this->p->util->cleanup_html_tags( $title );	// strip html tags before removing separator
			if ( ! empty( $separator ) )
				$title = preg_replace( '/ *'.preg_quote( $separator, '/' ).' *$/', '', $title );	// trim excess separator

			// apply title filter before adjusting it's length
			$title = apply_filters( $this->p->cf['lca'].'_title_pre_limit', $title );

			// check title against string length limits
			if ( $textlen > 0 ) {
				// seo-like title modifications
				if ( $this->p->is_avail['seo']['*'] === false ) {
					$paged = get_query_var( 'paged' );
					if ( $paged > 1 ) {
						if ( ! empty( $separator ) )
							$paged_suffix .= $separator.' ';
						$paged_suffix .= sprintf( 'Page %s', $paged );
						$textlen = $textlen - strlen( $paged_suffix ) - 1;
					}
				}
				if ( ! empty( $add_hashtags ) && 
					! empty( $hashtags ) ) 
						$textlen = $textlen - strlen( $hashtags ) - 1;
				$title = $this->p->util->limit_text_length( $title, $textlen, $trailing, false );	// don't run cleanup_html_tags()
			}

			if ( ! empty( $paged_suffix ) ) 
				$title .= ' '.$paged_suffix;

			if ( ! empty( $add_hashtags ) && 
				! empty( $hashtags ) ) 
					$title .= ' '.$hashtags;

			if ( $encode === true )
				$title = htmlentities( $title, ENT_QUOTES, get_bloginfo( 'charset' ), false );	// double_encode = false

			return apply_filters( $this->p->cf['lca'].'_title', $title, $use_post, $add_hashtags, $md_idx, $src_id );
		}

		public function get_description( $textlen = 156, $trailing = '...', $use_post = false, $use_cache = true,
			$add_hashtags = true, $encode = true, $md_idx = 'og_desc', $src_id = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'render description' );	// start timer
				$this->p->debug->args( array( 
					'textlen' => $textlen, 
					'trailing' => $trailing, 
					'use_post' => $use_post, 
					'use_cache' => $use_cache, 
					'add_hashtags' => $add_hashtags, 	// true/false/numeric
					'encode' => $encode,
					'md_idx' => $md_idx,
					'src_id' => $src_id,
				) );
			}
			$desc = false;
			$hashtags = '';
			$post_id = 0;
			$page = ''; 

			if ( is_singular() || $use_post !== false ) {
				if ( ( $obj = $this->p->util->get_post_object( $use_post ) ) === false ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: invalid object type' );
					return $desc;
				}
				$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ? 0 : $obj->ID;
			}

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {
				if ( is_singular() || $use_post !== false ) {
					if ( ! empty( $post_id ) )
						$desc = $this->p->util->get_mod_options( 'post', $post_id, array( $md_idx, 'og_desc' ) );
	
				} elseif ( SucomUtil::is_term_page() ) {
					$term = $this->p->util->get_term_object();
					if ( ! empty( $term->term_id ) )
						$desc = $this->p->util->get_mod_options( 'taxonomy', $term->term_id, $md_idx );
	
				} elseif ( SucomUtil::is_author_page() ) {
					$author = $this->p->util->get_author_object();
					if ( ! empty( $author->ID ) )
						$desc = $this->p->util->get_mod_options( 'user', $author->ID, $md_idx );
				}
				if ( $this->p->debug->enabled ) {
					if ( empty( $desc ) )
						$this->p->debug->log( 'no custom description found' );
					else $this->p->debug->log( 'custom description = "'.$desc.'"' );
				}
			}

			// get seed if no custom meta description
			if ( empty( $desc ) ) {
				$desc = apply_filters( $this->p->cf['lca'].'_description_seed', '', $use_post, $add_hashtags, $md_idx, $src_id );
				if ( ! empty( $desc ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'description seed = "'.$desc.'"' );
				}
			}

			// remove and save trailing hashtags
			if ( preg_match( '/^(.*)(( *#[a-z][a-z0-9\-]+)+)$/U', $desc, $match ) ) {
				$desc = $match[1];
				$hashtags = trim( $match[2] );
			} elseif ( is_singular() || $use_post !== false ) {
				if ( ! empty( $add_hashtags ) && 
					! empty( $this->p->options['og_desc_hashtags'] ) )
						$hashtags = $this->get_hashtags( $post_id, $add_hashtags );
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'hashtags found = "'.$hashtags.'"' );

			// if there's no custom description, and no pre-seed, 
			// then go ahead and generate the description value
			if ( empty( $desc ) ) {
				// $obj and $post_id are defined above, with the same test, so we should be good
				if ( is_singular() || $use_post !== false ) {
					// use the excerpt, if we have one
					if ( has_excerpt( $post_id ) ) {
						$desc = $obj->post_excerpt;
						if ( ! empty( $this->p->options['plugin_filter_excerpt'] ) ) {
							$filter_removed = apply_filters( $this->p->cf['lca'].'_pre_filter_remove',
								false, 'get_the_excerpt' );
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'calling apply_filters(\'get_the_excerpt\')' );
							$desc = apply_filters( 'get_the_excerpt', $desc );
							if ( $filter_removed )
								$filter_added = apply_filters( $this->p->cf['lca'].'_post_filter_add',
									false, 'get_the_excerpt' );
						}
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'no post_excerpt for post_id '.$post_id );

					// if there's no excerpt, then fallback to the content
					if ( empty( $desc ) )
						$desc = $this->get_content( $post_id, $use_post, $use_cache, $md_idx, $src_id );
			
					// ignore everything before the first paragraph if true
					if ( $this->p->options['plugin_p_strip'] ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'removing all text before the first paragraph' );
						$desc = preg_replace( '/^.*?<p>/i', '', $desc );	// question mark makes regex un-greedy
					}
		
				} elseif ( SucomUtil::is_term_page() ) {
					if ( is_tag() ) {
						$desc = tag_description();
						if ( empty( $desc ) )
							$desc = sprintf( 'Tagged with %s', single_tag_title( '', false ) );
					} elseif ( is_category() ) { 
						$desc = category_description();
						if ( empty( $desc ) )
							$desc = sprintf( '%s Category', single_cat_title( '', false ) ); 
					} else { 	// other taxonomies
						$term = $this->p->util->get_term_object();
						if ( ! empty( $term->description ) )
							$desc = $term->description;
						elseif ( ! empty( $term->name ) )
							$desc = $term->name.' Archives';
					}
				} elseif ( SucomUtil::is_author_page() ) { 
					$author = $this->p->util->get_author_object();
					if ( ! empty( $author->description ) )
						$desc = $author->description;
					elseif ( ! empty( $author->display_name ) )
						$desc = sprintf( 'Authored by %s', $author->display_name );
			
				} elseif ( is_day() ) 
					$desc = sprintf( 'Daily Archives for %s', get_the_date() );
				elseif ( is_month() ) 
					$desc = sprintf( 'Monthly Archives for %s', get_the_date('F Y') );
				elseif ( is_year() ) 
					$desc = sprintf( 'Yearly Archives for %s', get_the_date('Y') );
			}

			// if there's still no description, then fallback to a generic version
			if ( empty( $desc ) ) {
				if ( is_admin() && ! empty( $obj->post_status ) && $obj->post_status == 'auto-draft' ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'post_status is auto-draft - using empty description' );
				} else {
					// pass options array to allow fallback if locale option does not exist
					$key = SucomUtil::get_locale_key( 'og_site_description', $this->p->options, $post_id );
					if ( ! empty( $this->p->options[$key] ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'description is empty - custom site description ('.$key.')' );
						$desc = $this->p->options[$key];
					} else {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'description is empty - using blog description' );
						$desc = get_bloginfo( 'description', 'display' );
					}
				}
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'description strlen before html cleanup '.strlen( $desc ) );
			$desc = $this->p->util->cleanup_html_tags( $desc, true, $this->p->options['plugin_use_img_alt'] );
			$desc = apply_filters( $this->p->cf['lca'].'_description_pre_limit', $desc );

			if ( $textlen > 0 ) {
				if ( ! empty( $add_hashtags ) && 
					! empty( $hashtags ) ) 
						$textlen = $textlen - strlen( $hashtags ) -1;
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'description strlen before limit length '.strlen( $desc ).' (limiting to '.$textlen.' chars)' );
				$desc = $this->p->util->limit_text_length( $desc, $textlen, $trailing, false );	// don't run cleanup_html_tags()
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'description limit text length skipped' );

			if ( ! empty( $add_hashtags ) && 
				! empty( $hashtags ) ) 
					$desc .= ' '.$hashtags;

			if ( $encode === true )
				$desc = htmlentities( $desc, ENT_QUOTES, get_bloginfo( 'charset' ), false );	// double_encode = false

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'render description' );	// stop timer

			return apply_filters( $this->p->cf['lca'].'_description', $desc, $use_post, $add_hashtags, $md_idx, $src_id );
		}

		public function get_content( $post_id = 0, $use_post = true, $use_cache = true, $md_idx = null, $src_id = '' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->args( array( 
					'post_id' => $post_id, 
					'use_post' => $use_post, 
					'use_cache' => $use_cache,
					'md_idx' => $md_idx,
					'src_id' => $src_id,
				) );
			$content = false;

			// if $post_id is 0, then pass the $use_post (true/false) value instead
			if ( ( $obj = $this->p->util->get_post_object( ( empty( $post_id ) ? $use_post : $post_id ) ) ) === false ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: invalid object type' );
				return $content;
			}
			$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ? 0 : $obj->ID;
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'using content from object id '.$post_id );

			$filter_content = $this->p->options['plugin_filter_content'];
			$filter_name = $filter_content  ? 'filtered' : 'unfiltered';
			$caption_prefix = isset( $this->p->options['plugin_p_cap_prefix'] ) ?
				$this->p->options['plugin_p_cap_prefix'] : 'Caption:';

			/*
			 * retrieve the content
			 */
			if ( $filter_content == true ) {
				if ( $this->p->is_avail['cache']['object'] ) {
					// if the post id is 0, then add the sharing url to ensure a unique salt string
					$cache_salt = __METHOD__.'(lang:'.SucomUtil::get_locale().'_post:'.$post_id.'_'.$filter_name.
						( empty( $post_id ) ? '_url:'.$this->p->util->get_sharing_url( $use_post, true, $src_id ) : '' ).')';
					$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
					$cache_type = 'object cache';
					if ( $use_cache === true ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $cache_type.': wp_cache salt '.$cache_salt );
						$content = wp_cache_get( $cache_id, __METHOD__ );
						if ( $content !== false ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( $cache_type.': '.$filter_name.
									' content retrieved from wp_cache '.$cache_id );
							return $content;
						}
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'use_cache = false' );
				}
			}

			$content = apply_filters( $this->p->cf['lca'].'_content_seed', '', $post_id, $use_post, $md_idx, $src_id );

			if ( ! empty( $content ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'content seed = "'.$content.'"' );
			} elseif ( ! empty( $obj->post_content ) )
				$content = $obj->post_content;
			elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'exiting early: empty post content' );

			/*
			 * modify the content
			 */
			// save content length (for comparison) before making changes
			$content_strlen_before = strlen( $content );

			// remove singlepics, which we detect and use before-hand 
			$content = preg_replace( '/\[singlepic[^\]]+\]/', '', $content, -1, $count );
			if ( $count > 0 ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $count.' [singlepic] shortcode(s) removed from content' );
			}

			if ( $filter_content == true ) {
				$filter_removed = apply_filters( $this->p->cf['lca'].'_pre_filter_remove',
					false, 'the_content' );

				// remove all of our shortcodes
				if ( isset( $this->p->cf['*']['lib']['shortcode'] ) && 
					is_array( $this->p->cf['*']['lib']['shortcode'] ) )
						foreach ( $this->p->cf['*']['lib']['shortcode'] as $id => $name )
							if ( array_key_exists( $id, $this->shortcode ) && 
								is_object( $this->shortcode[$id] ) )
									$this->shortcode[$id]->remove();

				global $post;
				$saved_post = $post;	// woocommerce can change the $post, so save and restore
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'saving $post and applying the_content filters' );
				$content = apply_filters( 'the_content', $content );
				$post = $saved_post;

				// cleanup for NGG pre-v2 album shortcode
				unset ( $GLOBALS['subalbum'] );
				unset ( $GLOBALS['nggShowGallery'] );

				if ( $filter_removed )
					$filter_added = apply_filters( $this->p->cf['lca'].'_post_filter_add',
						false, 'the_content' );

				// add our shortcodes back
				if ( isset( $this->p->cf['*']['lib']['shortcode'] ) && 
					is_array( $this->p->cf['*']['lib']['shortcode'] ) )
						foreach ( $this->p->cf['*']['lib']['shortcode'] as $id => $name )
							if ( array_key_exists( $id, $this->shortcode ) && 
								is_object( $this->shortcode[$id] ) )
									$this->shortcode[$id]->add();
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'the_content filters skipped' );

			$content = preg_replace( '/[\s\n\r]+/s', ' ', $content );		// put everything on one line
			$content = preg_replace( '/^.*<!--'.$this->p->cf['lca'].'-content-->(.*)<!--\/'.
				$this->p->cf['lca'].'-content-->.*$/', '$1', $content );

			if ( strpos( $content, '>Google+<' ) !== false )
				$content = preg_replace( '/<a +rel="author" +href="" +style="display:none;">Google\+<\/a>/', ' ', $content );

			if ( ! empty( $caption_prefix ) &&
				strpos( $content, '<p class="wp-caption-text">' ) !== false )
					$content = preg_replace( '/<p class="wp-caption-text">/', '${0}'.$caption_prefix.' ', $content );

			if ( strpos( $content, ']]>' ) !== false )
				$content = str_replace( ']]>', ']]&gt;', $content );

			$content_strlen_after = strlen( $content );
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'content strlen before '.$content_strlen_before.' and after changes / filters '.$content_strlen_after );

			// apply filters before caching
			$content = apply_filters( $this->p->cf['lca'].'_content', $content, $post_id, $use_post, $md_idx, $src_id );

			if ( $filter_content == true && ! empty( $cache_id ) ) {
				// only some caching plugins implement this function
				wp_cache_add_non_persistent_groups( array( __METHOD__ ) );
				wp_cache_set( $cache_id, $content, __METHOD__, $this->p->options['plugin_object_cache_exp'] );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $cache_type.': '.$filter_name.' content saved to wp_cache '.
						$cache_id.' ('.$this->p->options['plugin_object_cache_exp'].' seconds)');
			}
			return $content;
		}

		public function get_section( $post_id ) {
			$section = '';
			if ( is_singular() || ! empty( $post_id ) )
				$section = $this->p->mods['util']['post']->get_options( $post_id, 'og_art_section' );

			if ( ! empty( $section ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'found custom meta section = '.$section );
			} else $section = $this->p->options['og_art_section'];

			if ( $section == 'none' )
				$section = '';

			return apply_filters( $this->p->cf['lca'].'_section', $section );
		}

		public function get_hashtags( $post_id, $add_hashtags = true ) {

			if ( empty( $add_hashtags ) )	// check for false or 0
				return;
			elseif ( is_numeric( $add_hashtags ) )
				$max_hashtags = $add_hashtags;
			elseif ( ! empty( $this->p->options['og_desc_hashtags'] ) )
				$max_hashtags = $this->p->options['og_desc_hashtags'];
			else return;

			$hashtags = apply_filters( $this->p->cf['lca'].'_hashtags_seed', '', $post_id, $add_hashtags );
			if ( ! empty( $hashtags ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'hashtags seed = "'.$hashtags.'"' );
			} else {
				$tags = array_slice( $this->get_tags( $post_id ), 0, $max_hashtags );
				if ( ! empty( $tags ) ) {
					// remove special character incompatible with Twitter
					$hashtags = SucomUtil::array_to_hashtags( $tags );
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'hashtags (max '.$max_hashtags.') = "'.$hashtags.'"' );
				}
			}

			return apply_filters( $this->p->cf['lca'].'_hashtags', $hashtags, $post_id, $add_hashtags );
		}

		public function get_tags( $post_id ) {

			$tags = apply_filters( $this->p->cf['lca'].'_tags_seed', array(), $post_id );
			if ( ! empty( $tags ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'tags seed = "'.implode( ',', $tags ).'"' );
			} else {
				if ( is_singular() || ! empty( $post_id ) ) {
					$tags = $this->get_wp_tags( $post_id );

					if ( isset( $this->p->mods['media']['ngg'] ) && 
						$this->p->options['og_ngg_tags'] && 
						$this->p->is_avail['postthumb'] && 
						has_post_thumbnail( $post_id ) ) {

						$pid = get_post_thumbnail_id( $post_id );
						// featured images from ngg pre-v2 had 'ngg-' prefix
						if ( is_string( $pid ) && substr( $pid, 0, 4 ) == 'ngg-' )
							$tags = array_merge( $tags, $this->p->mods['media']['ngg']->get_tags( $pid ) );
					}
				} elseif ( is_search() )
					$tags = preg_split( '/ *, */', get_search_query( false ) );

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'raw tags = "'.implode( ', ', $tags ).'"' );

				$tags = array_unique( array_map( array( 'SucomUtil', 'sanitize_tag' ), $tags ) );

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'sanitized tags = "'.implode( ', ', $tags ).'"' );
			}
			return apply_filters( $this->p->cf['lca'].'_tags', $tags, $post_id );
		}

		public function get_wp_tags( $post_id ) {

			$tags = apply_filters( $this->p->cf['lca'].'_wp_tags_seed', array(), $post_id );

			if ( ! empty( $tags ) )
				$this->p->debug->log( 'wp tags seed = "'.implode( ',', $tags ).'"' );
			else {
				$post_ids = array ( $post_id );	// array of one

				// add the parent tags if option is enabled
				if ( $this->p->options['og_page_parent_tags'] && is_page( $post_id ) )
					$post_ids = array_merge( $post_ids, get_post_ancestors( $post_id ) );
				foreach ( $post_ids as $id ) {
					if ( $this->p->options['og_page_title_tag'] && is_page( $id ) )
						$tags[] = SucomUtil::sanitize_tag( get_the_title( $id ) );
					foreach ( wp_get_post_tags( $id, array( 'fields' => 'names') ) as $tag_name )
						$tags[] = $tag_name;
				}
			}
			return apply_filters( $this->p->cf['lca'].'_wp_tags', $tags, $post_id );
		}

		public function get_category_title( $term = false ) {
			if ( ! is_object( $term ) )
				$term = $this->p->util->get_term_object();

			$separator = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );
			$title = $term->name.' Archives '.$separator.' ';	// default value

			$cat = get_category( $term->term_id );
			if ( ! empty( $cat->category_parent ) ) {
				$cat_parents = get_category_parents( $term->term_id, false, ' '.$separator.' ', false );
				if ( is_wp_error( $cat_parents ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'get_category_parents error: '.$cat_parents->get_error_message() );
				} else {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'get_category_parents() = "'.$cat_parents.'"' );
					if ( ! empty( $cat_parents ) ) {
						$title = $cat_parents;
						$title = preg_replace( '/\.\.\. '.preg_quote( $separator, '/' ).' /', '... ', $title );
					}
				}
			}
			return apply_filters( 'wp_title', $title, $separator, 'right' );
		}
	}
}

?>
