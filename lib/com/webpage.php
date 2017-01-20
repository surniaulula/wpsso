<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
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

		public function get_quote( array &$mod ) {

			$quote = apply_filters( $this->p->cf['lca'].'_quote_seed', '', $mod );

			if ( $quote != '' ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'quote seed = "'.$quote.'"' );
			} else {
				if ( has_excerpt( $mod['id'] ) ) 
					$quote = get_the_excerpt( $mod['id'] );
				else $quote = get_post_field( 'post_content', $mod['id'] );
			}

			// remove shortcodes, etc., but don't strip html tags
			$quote = $this->p->util->cleanup_html_tags( $quote, false );

			return apply_filters( $this->p->cf['lca'].'_quote', $quote, $mod );
		}

		// $type = title | excerpt | both
		// $mod = true | false | post_id | $mod array
		public function get_caption( $type = 'title', $textlen = 200, $mod = true, $use_cache = true,
			$add_hashtags = true, $encode = true, $md_idx = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'type' => $type, 
					'textlen' => $textlen, 
					'mod' => $mod, 
					'use_cache' => $use_cache, 
					'add_hashtags' => $add_hashtags,	// true/false/numeric
					'encode' => $encode,
					'md_idx' => $md_idx,
				) );
			}

			// $mod = true | false | post_id | $mod array
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $mod );

			$caption = false;
			$separator = html_entity_decode( $this->p->options['og_title_sep'],
				ENT_QUOTES, get_bloginfo( 'charset' ) );

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

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {
				$caption = $mod['obj'] ?
					$mod['obj']->get_options_multi( $mod['id'], $md_idx ) : null;

				// maybe add hashtags to a post caption
				if ( $mod['is_post'] ) {
					if ( ! empty( $caption ) && ! empty( $add_hashtags ) && ! preg_match( '/( #[a-z0-9\-]+)+$/U', $caption ) ) {
						$hashtags = $this->get_hashtags( $mod['id'], $add_hashtags );
						if ( ! empty( $hashtags ) ) 
							$caption = $this->p->util->limit_text_length( $caption, 
								$textlen - strlen( $hashtags ) - 1, '...', false ).	// $cleanup_html = false
									' '.$hashtags;
					}
				}
				if ( $this->p->debug->enabled ) {
					if ( empty( $caption ) )
						$this->p->debug->log( 'no custom caption found for '.$md_idx );
					else $this->p->debug->log( 'custom caption = "'.$caption.'"' );
				}
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'custom caption skipped: no md_idx value' );

			if ( empty( $caption ) ) {

				if ( ! empty( $md_idx ) ) {
					$md_prefix = preg_replace( '/_(title|desc|caption)$/', '', $md_idx );
					$md_title = $md_prefix.'_title';
					$md_desc = $md_prefix.'_desc';
				} else $md_title = $md_desc = $md_idx;

				// request all values un-encoded, then encode once we have the complete caption text
				switch ( $type ) {
					case 'title':
						$caption = $this->get_title( $textlen, '...', $mod, $use_cache, $add_hashtags, false, $md_title );
						break;

					case 'excerpt':
						$caption = $this->get_description( $textlen, '...', $mod, $use_cache, $add_hashtags, false, $md_desc );
						break;

					case 'both':
						// get the title first
						$caption = $this->get_title( 0, '', $mod, $use_cache, false, false, $md_title );	// $add_hashtags = false

						// add a separator between title and description
						if ( ! empty( $caption ) )
							$caption .= ' '.$separator.' ';

						// reduce the requested $textlen by the title text length we already have
						$caption .= $this->get_description( $textlen - strlen( $caption ),
							'...', $mod, $use_cache, $add_hashtags, false, $md_desc );
						break;
				}
			}

			if ( $encode === true ) {
				$caption = SucomUtil::encode_emoji( htmlentities( $caption, 
					ENT_QUOTES, get_bloginfo( 'charset' ), false ) );	// double_encode = false
			} else {	// just in case
				$caption = html_entity_decode( SucomUtil::decode_utf8( $caption ), 
					ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			return apply_filters( $this->p->cf['lca'].'_caption', $caption, $mod, $add_hashtags, $md_idx );
		}

		// $mod = true | false | post_id | $mod array
		public function get_title( $textlen = 70, $trailing = '', $mod = false, $use_cache = true,
			$add_hashtags = false, $encode = true, $md_idx = 'og_title' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'textlen' => $textlen, 
					'trailing' => $trailing, 
					'mod' => $mod, 
					'use_cache' => $use_cache, 
					'add_hashtags' => $add_hashtags,	// true/false/numeric
					'encode' => $encode,
					'md_idx' => $md_idx,
				) );
			}

			// $mod = true | false | post_id | $mod array
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $mod );

			$title = false;
			$hashtags = '';
			$paged_suffix = '';
			$separator = html_entity_decode( $this->p->options['og_title_sep'], 
				ENT_QUOTES, get_bloginfo( 'charset' ) );

			// setup filters to save and restore original / pre-filtered title value
			if ( empty( $this->p->options['plugin_filter_title'] ) )
				SucomUtil::protect_filter_start( 'wp_title' );

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {
				$title = $mod['obj'] ?
					$mod['obj']->get_options_multi( $mod['id'], ( $mod['is_post'] ? 
						array( $md_idx, 'og_title' ) : $md_idx ) ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $title ) )
						$this->p->debug->log( 'no custom title found for '.$md_idx );
					else $this->p->debug->log( 'custom title = "'.$title.'"' );
				}
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'custom title skipped: no md_idx value' );

			// get seed if no custom meta title
			if ( empty( $title ) ) {
				$title = apply_filters( $this->p->cf['lca'].'_title_seed', '', $mod, $add_hashtags, $md_idx, $separator );
				if ( ! empty( $title ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'title seed = "'.$title.'"' );
				}
			}

			// check for hashtags in meta or seed title, remove and then add again after shorten
			if ( preg_match( '/(.*)(( #[a-z0-9\-]+)+)$/U', $title, $match ) ) {
				$title = $match[1];
				$hashtags = trim( $match[2] );

			} elseif ( $mod['is_post'] ) {
				if ( ! empty( $add_hashtags ) && 
					! empty( $this->p->options['og_desc_hashtags'] ) )
						$hashtags = $this->get_hashtags( $mod['id'], $add_hashtags );	// $add_hashtags = true | false | numeric
			}

			if ( $hashtags && $this->p->debug->enabled )
				$this->p->debug->log( 'hashtags found = "'.$hashtags.'"' );

			// construct a title of our own
			if ( empty( $title ) ) {

				if ( $mod['is_post'] ) {
					if ( is_singular() ) {
						$title = wp_title( $separator, false, 'right' );
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'is_singular wp_title() = "'.$title.'"' );
					} elseif ( ! empty( $mod['id'] ) ) {
						$title = apply_filters( 'wp_title', get_the_title( $mod['id'] ).
							' '.$separator.' ', $separator, 'right' );
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'post ID get_the_title() = "'.$title.'"' );
					}

				// if we're using filtered titles, and an seo plugin is available,
				// the use the wordpress title (provided by the seo plugin)
				} elseif ( $this->p->options['plugin_filter_title'] &&
					$this->p->is_avail['seo']['*'] ) {

					$title = wp_title( $separator, false, 'right' );	// on right for compatibility with aioseo
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'seo wp_title() = "'.$title.'"' );

				} elseif ( $mod['is_term'] ) {
					$term_obj = SucomUtil::get_term_object( $mod['id'], $mod['tax_slug'] );
					if ( SucomUtil::is_category_page() )
						$title = $this->get_category_title( $term_obj, '', $separator );	// includes parents in title string
					elseif ( isset( $term_obj->name ) )
						$title = apply_filters( 'wp_title', $term_obj->name.
							' '.$separator.' ', $separator, 'right' );
					elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'name property missing in term object' );

				} elseif ( $mod['is_user'] ) { 
					$user_obj = SucomUtil::get_user_object( $mod['id'] );
					$title = apply_filters( 'wp_title', $user_obj->display_name.
						' '.$separator.' ', $separator, 'right' );
					$title = apply_filters( $this->p->cf['lca'].'_user_object_title', $title, $user_obj );

				} else {	// is_archive() and everything else
					$title = wp_title( $separator, false, 'right' );
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'default wp_title() = "'.$title.'"' );
				}

				// just in case
				if ( empty( $title ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'fallback get_bloginfo() = "'.$title.'"' );
					if ( ! ( $title = get_bloginfo( 'name', 'display' ) ) )
						$title = 'No Title';	// just in case
				}
			}

			if ( empty( $this->p->options['plugin_filter_title'] ) )
				SucomUtil::protect_filter_stop( 'wp_title' );

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

				$title = $this->p->util->limit_text_length( $title, $textlen, $trailing, false );	// $cleanup_html = false
			}

			if ( ! empty( $paged_suffix ) ) 
				$title .= ' '.$paged_suffix;

			if ( ! empty( $add_hashtags ) && 
				! empty( $hashtags ) ) 
					$title .= ' '.$hashtags;

			if ( $encode === true ) {
				foreach ( array( 'title', 'separator' ) as $var ) {
					$$var = SucomUtil::encode_emoji( htmlentities( $$var, 
						ENT_QUOTES, get_bloginfo( 'charset' ), false ) );	// double_encode = false
				}
			}

			return apply_filters( $this->p->cf['lca'].'_title', $title, $mod, $add_hashtags, $md_idx, $separator );
		}

		// $mod = true | false | post_id | $mod array
		public function get_description( $textlen = 156, $trailing = '...', $mod = false, $use_cache = true,
			$add_hashtags = true, $encode = true, $md_idx = 'og_desc' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'render description' );	// begin timer

				$this->p->debug->log_args( array( 
					'textlen' => $textlen, 
					'trailing' => $trailing, 
					'mod' => $mod, 
					'use_cache' => $use_cache, 
					'add_hashtags' => $add_hashtags, 	// true | false | numeric
					'encode' => $encode,
					'md_idx' => $md_idx,
				) );
			}

			// $mod = true | false | post_id | $mod array
			if ( ! is_array( $mod ) )
				$mod = $this->p->util->get_page_mod( $mod );

			$desc = false;
			$hashtags = '';

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {
				// fallback to og_desc value
				$desc = is_object( $mod['obj'] ) ?
					$mod['obj']->get_options_multi( $mod['id'], array( $md_idx, 'og_desc' ) ) : null;
				if ( $this->p->debug->enabled ) {
					if ( empty( $desc ) )
						$this->p->debug->log( 'no custom description found for '.$md_idx );
					else $this->p->debug->log( 'custom description = "'.$desc.'"' );
				}
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'custom description skipped: no md_idx value' );

			// get seed if no custom meta description
			if ( empty( $desc ) ) {
				$desc = apply_filters( $this->p->cf['lca'].'_description_seed', '', $mod, $add_hashtags, $md_idx );
				if ( ! empty( $desc ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'description seed = "'.$desc.'"' );
				}
			}

			// check for hashtags in meta or seed desc, remove and then add again after shorten
			if ( preg_match( '/^(.*)(( *#[a-z][a-z0-9\-]+)+)$/U', $desc, $match ) ) {
				$desc = $match[1];
				$hashtags = trim( $match[2] );

			} elseif ( $mod['is_post'] ) {
				if ( ! empty( $add_hashtags ) && 
					! empty( $this->p->options['og_desc_hashtags'] ) )
						$hashtags = $this->get_hashtags( $mod['id'], $add_hashtags );
			}

			if ( $hashtags && $this->p->debug->enabled )
				$this->p->debug->log( 'hashtags found = "'.$hashtags.'"' );

			// if there's no custom description, and no pre-seed, 
			// then go ahead and generate the description value
			if ( empty( $desc ) ) {

				if ( $mod['is_post'] ) {

					// use the excerpt, if we have one
					if ( has_excerpt( $mod['id'] ) ) {
						$desc = get_post_field( 'post_excerpt', $mod['id'] );

						if ( ! empty( $this->p->options['plugin_filter_excerpt'] ) ) {
							$filter_has_changes = apply_filters( $this->p->cf['lca'].'_text_filter_has_changes_before', false, 'get_the_excerpt' );

							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'applying the WordPress get_the_excerpt filters' );

							$desc = apply_filters( 'get_the_excerpt', $desc );

							if ( $filter_has_changes )
								apply_filters( $this->p->cf['lca'].'_text_filter_has_changes_after', false, 'get_the_excerpt' );

						} elseif ( $this->p->debug->enabled )
							$this->p->debug->log( 'skipped the WordPress get_the_excerpt filters' );

					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'fetching content: no post_excerpt for post ID '.$mod['id'] );

					// if there's no excerpt, then fallback to the content
					if ( empty( $desc ) )
						$desc = $this->get_content( $mod, $use_cache, $md_idx );

					// ignore everything before the first paragraph if true
					if ( $this->p->options['plugin_p_strip'] ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'removing all text before the first paragraph' );
						$desc = preg_replace( '/^.*?<p>/i', '', $desc );	// question mark makes regex un-greedy
					}

				} elseif ( $mod['is_term'] ) {

					if ( is_tag() ) {
						if ( ! $desc = tag_description() )
							$desc = sprintf( 'Tagged with %s', single_tag_title( '', false ) );

					} elseif ( is_category() ) { 
						if ( ! $desc = category_description() )
							$desc = sprintf( '%s Category', single_cat_title( '', false ) ); 

					} else { 	// other taxonomies
						$term_obj = SucomUtil::get_term_object( $mod['id'], $mod['tax_slug'] );

						if ( ! empty( $term_obj->description ) )
							$desc = $term_obj->description;
						elseif ( ! empty( $term_obj->name ) )
							$desc = $term_obj->name.' Archives';
					}

				} elseif ( $mod['is_user'] ) { 
					$user_obj = SucomUtil::get_user_object( $mod['id'] );

					if ( ! empty( $user_obj->description ) )
						$desc = $user_obj->description;
					elseif ( ! empty( $user_obj->display_name ) )
						$desc = sprintf( 'Authored by %s', $user_obj->display_name );

					$desc = apply_filters( $this->p->cf['lca'].'_user_object_description', $desc, $user_obj );

				} elseif ( is_day() ) 
					$desc = sprintf( 'Daily Archives for %s', get_the_date() );
				elseif ( is_month() ) 
					$desc = sprintf( 'Monthly Archives for %s', get_the_date('F Y') );
				elseif ( is_year() ) 
					$desc = sprintf( 'Yearly Archives for %s', get_the_date('Y') );
				elseif ( SucomUtil::is_archive_page() )	// just in case
					$desc = sprintf( 'Archive Page' );
			}

			// if there's still no description, then fallback to a generic version
			if ( empty( $desc ) ) {
				if ( $mod['post_status'] === 'auto-draft' ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'post_status is auto-draft: using empty description' );
				} elseif ( ! ( $desc = SucomUtil::get_site_description( $this->p->options, $mod ) ) )
					$desc = 'No Description';	// just in case
			}

			$strlen_before_cleanup = $this->p->debug->enabled ? strlen( $desc ) : 0;
			$desc = $this->p->util->cleanup_html_tags( $desc, true, $this->p->options['plugin_use_img_alt'] );
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'description strlen before html cleanup '.$strlen_before_cleanup.' and after '.strlen( $desc ) );
			$desc = apply_filters( $this->p->cf['lca'].'_description_pre_limit', $desc );

			if ( $textlen > 0 ) {
				if ( ! empty( $add_hashtags ) && 
					! empty( $hashtags ) ) 
						$textlen = $textlen - strlen( $hashtags ) -1;
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'description strlen before limit length '.strlen( $desc ).' (limiting to '.$textlen.' chars)' );
				$desc = $this->p->util->limit_text_length( $desc, $textlen, $trailing, false );	// $cleanup_html = false
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'description limit text length skipped' );

			if ( ! empty( $add_hashtags ) && 
				! empty( $hashtags ) ) 
					$desc .= ' '.$hashtags;

			if ( $encode === true ) {
				$desc = SucomUtil::encode_emoji( htmlentities( $desc, 
					ENT_QUOTES, get_bloginfo( 'charset' ), false ) );	// double_encode = false
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'render description' );	// end timer

			return apply_filters( $this->p->cf['lca'].'_description', $desc, $mod, $add_hashtags, $md_idx );
		}

		public function get_content( array $mod, $use_cache = true, $md_idx = '' ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'mod' => $mod, 
					'use_cache' => $use_cache,
					'md_idx' => $md_idx,
				) );
			}

			$lca = $this->p->cf['lca'];
			$sharing_url = $this->p->util->get_sharing_url( $mod );
			$filter_content = empty( $this->p->options['plugin_filter_content'] ) ? false : true;
			$content_array = array();
			$content_index = 'locale:'.SucomUtil::get_locale( $mod ).'_filter:'.( $filter_content ? 'true' : 'false' );
			$cache_salt = __METHOD__.'('.SucomUtil::get_mod_salt( $mod, $sharing_url ).')';
			$cache_id = $lca.'_'.md5( $cache_salt );
			$cache_exp = (int) apply_filters( $lca.'_cache_expire_content_text',
				$this->p->options['plugin_content_cache_exp'] );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'sharing url = '.$sharing_url );
				$this->p->debug->log( 'content index = '.$content_index );
				$this->p->debug->log( 'wp_cache expire = '.$cache_exp );
				$this->p->debug->log( 'wp_cache salt = '.$cache_salt );
			}

			/*
			 * Retrieve the content
			 */

			if ( $cache_exp > 0 && $use_cache ) {
				$content_array = wp_cache_get( $cache_id, __METHOD__ );
				if ( isset( $content_array[$content_index] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'content index found in array from wp_cache '.$cache_id );
					return $content_array[$content_index];
				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'content index not in array from wp_cache '.$cache_id );
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'content array wp_cache is disabled' );

			$content_array[$content_index] = false;
			$content_text =& $content_array[$content_index];
			$content_text = apply_filters( $lca.'_content_seed', '', $mod, $use_cache, $md_idx );

			if ( ! empty( $content_text ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'content seed is "'.$content_text.'"' );
			} elseif ( $mod['is_post'] ) {
				$content_text = get_post_field( 'post_content', $mod['id'] );
				if ( empty( $content_text ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: no post_content for post id '.$mod['id'] );
					return false;
				}
			}

			/*
			 * Modify the content
			 */

			// save content length (for comparison) before making changes
			$strlen_before_filters = strlen( $content_text );

			// remove singlepics, which we detect and use before-hand 
			$content_text = preg_replace( '/\[singlepic[^\]]+\]/', '', $content_text, -1, $count );
			if ( $count > 0 ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( $count.' [singlepic] shortcode(s) removed from content' );
			}

			if ( $filter_content ) {
				$filter_has_changes = apply_filters( $lca.'_text_filter_has_changes_before', false, 'the_content' );

				// remove all of our shortcodes
				if ( isset( $this->p->cf['*']['lib']['shortcode'] ) && 
					is_array( $this->p->cf['*']['lib']['shortcode'] ) )
						foreach ( $this->p->cf['*']['lib']['shortcode'] as $id => $name )
							if ( array_key_exists( $id, $this->shortcode ) && 
								is_object( $this->shortcode[$id] ) )
									$this->shortcode[$id]->remove();

				global $post;
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'saving the original $post object' );
				$post_saved = $post;	// save the original GLOBAL post object

				// WordPress oEmbed needs a $post ID, so make sure we have one
				// see shortcode() in WP_Embed class (wp-includes/class-wp-embed.php)
				if ( empty( $post->ID ) && $mod['is_post'] ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'post id property empty: re-setting post object from mod id '.$mod['id'] );
					$post = SucomUtil::get_post_object( $mod['id'] );	// redefine $post global
				}

				// apply the content filters
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'applying wordpress the_content filters' );

				$content_text = apply_filters( 'the_content', $content_text );

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'restoring the original post object' );
				$post = $post_saved;	// restore the original GLOBAL post object

				// cleanup for NGG pre-v2 album shortcode
				unset ( $GLOBALS['subalbum'] );
				unset ( $GLOBALS['nggShowGallery'] );

				if ( $filter_has_changes )
					apply_filters( $lca.'_text_filter_has_changes_after', false, 'the_content' );

				// add our shortcodes back
				if ( isset( $this->p->cf['*']['lib']['shortcode'] ) && 
					is_array( $this->p->cf['*']['lib']['shortcode'] ) )
						foreach ( $this->p->cf['*']['lib']['shortcode'] as $id => $name )
							if ( array_key_exists( $id, $this->shortcode ) && 
								is_object( $this->shortcode[$id] ) )
									$this->shortcode[$id]->add();

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'the_content filters skipped (shortcodes not expanded)' );

			$content_text = preg_replace( '/[\s\n\r]+/s', ' ', $content_text );		// put everything on one line
			$content_text = preg_replace( '/^.*<!--'.$lca.'-content-->(.*)<!--\/'.
				$lca.'-content-->.*$/', '$1', $content_text );

			// remove "Google+" link and text
			if ( strpos( $content_text, '>Google+<' ) !== false )
				$content_text = preg_replace( '/<a +rel="author" +href="" +style="display:none;">Google\+<\/a>/', ' ', $content_text );

			if ( strpos( $content_text, '<p class="wp-caption-text">' ) !== false ) {
				$caption_prefix = isset( $this->p->options['plugin_p_cap_prefix'] ) ?
					$this->p->options['plugin_p_cap_prefix'] : 'Caption:';
				if ( ! empty( $caption_prefix ) )
					$content_text = preg_replace( '/<p class="wp-caption-text">/', '${0}'.$caption_prefix.' ', $content_text );
			}

			if ( strpos( $content_text, ']]>' ) !== false )
				$content_text = str_replace( ']]>', ']]&gt;', $content_text );

			$strlen_after_filters = strlen( $content_text );
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'content strlen before '.$strlen_before_filters.' and after changes / filters '.$strlen_after_filters );

			// apply filters before caching
			$content_array[$content_index] = apply_filters( $lca.'_content', $content_text, $mod, $use_cache, $md_idx );

			if ( $cache_exp > 0 ) {
				wp_cache_add_non_persistent_groups( array( __METHOD__ ) );	// only some caching plugins support this feature
				wp_cache_set( $cache_id, $content_array, __METHOD__, $cache_exp );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'content array saved to wp_cache '.$cache_id.' ('.$cache_exp.' seconds)');
			}

			return $content_array[$content_index];
		}

		public function get_article_section( $post_id ) {
			$section = '';
			if ( ! empty( $post_id ) )
				$section = $this->p->m['util']['post']->get_options( $post_id, 'og_art_section' );

			if ( ! empty( $section ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'found custom meta article section = '.$section );
			} else $section = $this->p->options['og_art_section'];

			if ( $section === 'none' )
				$section = '';

			return apply_filters( $this->p->cf['lca'].'_article_section', $section, $post_id );
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
					if ( isset( $this->p->m['media']['ngg'] ) && 
						$this->p->options['og_ngg_tags'] && 
							$this->p->is_avail['post_thumbnail'] && 
								has_post_thumbnail( $post_id ) ) {

						$pid = get_post_thumbnail_id( $post_id );

						// featured images from ngg pre-v2 had 'ngg-' prefix
						if ( is_string( $pid ) && substr( $pid, 0, 4 ) == 'ngg-' )
							$tags = array_merge( $tags, $this->p->m['media']['ngg']->get_tags( $pid ) );
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

		public function get_category_title( $term_id = 0, $tax_slug = '', $separator = false ) {
			if ( is_object( $term_id ) )
				$term_obj = $term_id;
			else $term_obj = SucomUtil::get_term_object( $term_id, $tax_slug );

			if ( $separator === false )
				$separator = html_entity_decode( $this->p->options['og_title_sep'], 
					ENT_QUOTES, get_bloginfo( 'charset' ) );

			if ( isset( $term_obj->name ) )
				$title = $term_obj->name.' Archives '.$separator.' ';	// default value
			elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'name property missing in term object' );

			$cat = get_category( $term_obj->term_id );
			if ( ! empty( $cat->category_parent ) ) {
				$cat_parents = get_category_parents( $term_obj->term_id, false, ' '.$separator.' ', false );
				if ( is_wp_error( $cat_parents ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'get_category_parents error: '.$cat_parents->get_error_message() );
				} else {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'get_category_parents() = "'.$cat_parents.'"' );
					if ( ! empty( $cat_parents ) )
						$title = $cat_parents;
				}
			}

			return apply_filters( 'wp_title', $title, $separator, 'right' );
		}
	}
}

?>
