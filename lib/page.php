<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoPage' ) ) {

	class WpssoPage {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public function get_quote( array $mod ) {

			$quote_text = apply_filters( $this->p->lca.'_quote_seed', '', $mod );

			if ( ! empty( $quote_text ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'quote seed = "'.$quote_text.'"' );
				}
			} else {
				if ( has_excerpt( $mod['id'] ) ) {
					$quote_text = get_the_excerpt( $mod['id'] );
				} else {
					$quote_text = get_post_field( 'post_content', $mod['id'] );
				}
			}

			// remove shortcodes, etc., but don't strip html tags
			$quote_text = $this->p->util->cleanup_html_tags( $quote_text, false );

			return apply_filters( $this->p->lca.'_quote', $quote_text, $mod );
		}

		// $type = title | excerpt | both
		// $mod = true | false | post_id | $mod array
		public function get_caption( $type = 'title', $textlen = 200, $mod = true, $read_cache = true,
			$add_hashtags = true, $do_encode = true, $md_idx = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'type' => $type,
					'textlen' => $textlen,
					'mod' => $mod,
					'read_cache' => $read_cache,
					'add_hashtags' => $add_hashtags,	// true/false/numeric
					'do_encode' => $do_encode,
					'md_idx' => $md_idx,
				) );
			}

			// $mod is preferred but not required
			// $mod = true | false | post_id | $mod array
			if ( ! is_array( $mod ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}
				$mod = $this->p->util->get_page_mod( $mod );
			}

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
				$caption = $mod['obj'] ? $mod['obj']->get_options_multi( $mod['id'], $md_idx ) : null;

				// maybe add hashtags to a post caption
				if ( $mod['is_post'] ) {
					if ( ! empty( $caption ) && ! empty( $add_hashtags ) && ! preg_match( '/( #[a-z0-9\-]+)+$/U', $caption ) ) {
						$hashtags = $this->get_hashtags( $mod['id'], $add_hashtags );
						if ( ! empty( $hashtags ) ) {
							$caption = $this->p->util->limit_text_length( $caption,
								$textlen - strlen( $hashtags ) - 1, '...', false ).	// $cleanup_html = false
									' '.$hashtags;
						}
					}
				}
				if ( $this->p->debug->enabled ) {
					if ( empty( $caption ) ) {
						$this->p->debug->log( 'no custom caption found for '.$md_idx );
					} else {
						$this->p->debug->log( 'custom caption = "'.$caption.'"' );
					}
				}
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom caption skipped: no md_idx value' );
			}

			if ( empty( $caption ) ) {

				if ( ! empty( $md_idx ) ) {
					$md_prefix = preg_replace( '/_(title|desc|caption)$/', '', $md_idx );
					$md_title = $md_prefix.'_title';
					$md_desc = $md_prefix.'_desc';
				} else {
					$md_title = $md_desc = $md_idx;
				}

				// request all values un-encoded, then encode once we have the complete caption text
				switch ( $type ) {
					case 'title':
						$caption = $this->get_title( $textlen,
							'...', $mod, $read_cache, $add_hashtags, false, $md_title );
						break;

					case 'excerpt':
						$caption = $this->get_description( $textlen,
							'...', $mod, $read_cache, $add_hashtags, false, $md_desc );
						break;

					case 'both':
						// get the title first
						$caption = $this->get_title( 0,
							'', $mod, $read_cache, false, false, $md_title );	// $add_hashtags = false

						// add a separator between title and description
						if ( ! empty( $caption ) ) {
							$caption .= ' '.$separator.' ';
						}

						// reduce the requested $textlen by the title text length we already have
						$caption .= $this->get_description( $textlen - strlen( $caption ),
							'...', $mod, $read_cache, $add_hashtags, false, $md_desc );
						break;
				}
			}

			if ( $do_encode === true ) {
				$caption = SucomUtil::encode_html_emoji( $caption );
			} else {	// just in case
				$caption = html_entity_decode( SucomUtil::decode_utf8( $caption ),
					ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			return apply_filters( $this->p->cf['lca'].'_caption', $caption, $mod, $add_hashtags, $md_idx );
		}

		// $mod = true | false | post_id | $mod array
		public function get_title( $textlen = 70, $trailing = '', $mod = false, $read_cache = true,
			$add_hashtags = false, $do_encode = true, $md_idx = 'og_title' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'textlen' => $textlen,
					'trailing' => $trailing,
					'mod' => $mod,
					'read_cache' => $read_cache,
					'add_hashtags' => $add_hashtags,	// true/false/numeric
					'do_encode' => $do_encode,
					'md_idx' => $md_idx,
				) );
			}

			// $mod is preferred but not required
			// $mod = true | false | post_id | $mod array
			if ( ! is_array( $mod ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}
				$mod = $this->p->util->get_page_mod( $mod );
			}

			$lca = $this->p->cf['lca'];
			$title_text = false;
			$hashtags = '';
			$paged_suffix = '';
			$separator = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );
			$filter_title = empty( $this->p->options['plugin_filter_title'] ) ? false : true;
			$filter_title = apply_filters( $lca.'_filter_title', $filter_title, $mod );

			// setup filters to save and restore original / pre-filtered title value
			if ( ! $filter_title ) {
				SucomUtil::protect_filter_value( 'wp_title' );
			}

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {

				$title_text = is_object( $mod['obj'] ) ?
					$mod['obj']->get_options_multi( $mod['id'], array( $md_idx, 'og_title' ) ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $title_text ) ) {
						$this->p->debug->log( 'no custom title found for '.$md_idx );
					} else {
						$this->p->debug->log( 'custom title = "'.$title_text.'"' );
					}
				}
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom title skipped: no md_idx value' );
			}

			// get seed if no custom meta title
			if ( empty( $title_text ) ) {
				$title_text = apply_filters( $lca.'_title_seed', '', $mod, $add_hashtags, $md_idx, $separator );
				if ( ! empty( $title_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'title seed = "'.$title_text.'"' );
					}
				}
			}

			// check for hashtags in meta or seed title, remove and then add again after shorten
			if ( preg_match( '/(.*)(( #[a-z0-9\-]+)+)$/U', $title_text, $match ) ) {
				$title_text = $match[1];
				$hashtags = trim( $match[2] );

			} elseif ( $mod['is_post'] ) {
				if ( ! empty( $add_hashtags ) && ! empty( $this->p->options['og_desc_hashtags'] ) ) {
					$hashtags = $this->get_hashtags( $mod['id'], $add_hashtags );	// $add_hashtags = true | false | numeric
				}
			}

			if ( $hashtags && $this->p->debug->enabled ) {
				$this->p->debug->log( 'hashtags found = "'.$hashtags.'"' );
			}

			// construct a title of our own
			if ( empty( $title_text ) ) {

				if ( $mod['is_post'] ) {

					$title_text = get_the_title( $mod['id'] ).' '.$separator.' ';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'post id '.$mod['id'].' get_the_title() = "'.$title_text.'"' );
					}

					$title_text = $this->p->util->safe_apply_filters( array( 'wp_title', $title_text, $separator, 'right' ), $mod );

				} elseif ( $mod['is_term'] ) {

					$term_obj = SucomUtil::get_term_object( $mod['id'], $mod['tax_slug'] );

					if ( SucomUtil::is_category_page( $mod['id'] ) ) {
						$title_text = $this->get_category_title( $term_obj, '', $separator );	// includes parents in title string
					} elseif ( isset( $term_obj->name ) ) {
						$title_text = apply_filters( 'wp_title', $term_obj->name.' '.$separator.' ', $separator, 'right' );
					} elseif ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'name property missing in term object' );
					}

				} elseif ( $mod['is_user'] ) {

					$user_obj = SucomUtil::get_user_object( $mod['id'] );

					$title_text = apply_filters( 'wp_title', $user_obj->display_name.' '.$separator.' ', $separator, 'right' );
					$title_text = apply_filters( $lca.'_user_object_title', $title_text, $user_obj );

				} else {
					$title_text = wp_title( $separator, false, 'right' );
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'default wp_title() = "'.$title_text.'"' );
					}
				}

				// just in case
				if ( empty( $title_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'fallback get_bloginfo() = "'.$title_text.'"' );
					}
					if ( ! ( $title_text = get_bloginfo( 'name', 'display' ) ) ) {
						$title_text = 'No Title';	// just in case
					}
				}
			}

			$title_text = $this->p->util->cleanup_html_tags( $title_text );	// strip html tags before removing separator

			if ( ! empty( $separator ) ) {
				$title_text = preg_replace( '/ *'.preg_quote( $separator, '/' ).' *$/', '', $title_text );	// trim excess separator
			}

			// apply title filter before adjusting it's length
			$title_text = apply_filters( $lca.'_title_pre_limit', $title_text );

			// check title against string length limits
			if ( $textlen > 0 ) {
				// seo-like title modifications
				if ( $this->p->avail['seo']['*'] === false ) {
					global $wpsso_paged;
					if ( is_numeric( $wpsso_paged ) ) {
						$paged = $wpsso_paged;
					} else {
						$paged = get_query_var( 'paged' );
					}
					if ( $paged > 1 ) {
						if ( ! empty( $separator ) ) {
							$paged_suffix .= $separator.' ';
						}
						$paged_suffix .= sprintf( 'Page %s', $paged );
						$textlen = $textlen - strlen( $paged_suffix ) - 1;
					}
				}
				if ( ! empty( $add_hashtags ) && ! empty( $hashtags ) ) {
					$textlen = $textlen - strlen( $hashtags ) - 1;
				}

				$title_text = $this->p->util->limit_text_length( $title_text, $textlen, $trailing, false );	// $cleanup_html = false
			}

			if ( ! empty( $paged_suffix ) ) {
				$title_text .= ' '.$paged_suffix;
			}

			if ( ! empty( $add_hashtags ) && ! empty( $hashtags ) ) {
				$title_text .= ' '.$hashtags;
			}

			if ( $do_encode === true ) {
				foreach ( array( 'title_text', 'separator' ) as $var ) {	// loop through variables
					$$var = SucomUtil::encode_html_emoji( $$var );
				}
			}

			return apply_filters( $lca.'_title', $title_text, $mod, $add_hashtags, $md_idx, $separator );
		}

		// $mod = true | false | post_id | $mod array
		public function get_description( $textlen = 156, $trailing = '...', $mod = false, $read_cache = true,
			$add_hashtags = true, $do_encode = true, $md_idx = 'og_desc' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'render description' );	// begin timer

				$this->p->debug->log_args( array(
					'textlen' => $textlen,
					'trailing' => $trailing,
					'mod' => $mod,
					'read_cache' => $read_cache,
					'add_hashtags' => $add_hashtags, 	// true | false | numeric
					'do_encode' => $do_encode,
					'md_idx' => $md_idx,
				) );
			}

			// $mod is preferred but not required
			// $mod = true | false | post_id | $mod array
			if ( ! is_array( $mod ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'optional call to get_page_mod()' );
				}
				$mod = $this->p->util->get_page_mod( $mod );
			}

			$lca = $this->p->cf['lca'];
			$desc_text = false;
			$hashtags = '';

			// skip if no metadata index / key name
			if ( ! empty( $md_idx ) ) {

				// fallback to og_desc value
				$desc_text = is_object( $mod['obj'] ) ?
					$mod['obj']->get_options_multi( $mod['id'], 
						array( $md_idx, 'og_desc' ) ) : null;

				if ( $this->p->debug->enabled ) {
					if ( empty( $desc_text ) ) {
						$this->p->debug->log( 'no custom description found for '.$md_idx );
					} else {
						$this->p->debug->log( 'custom description = "'.$desc_text.'"' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'custom description skipped: no md_idx value' );
			}

			// get seed if no custom meta description
			if ( empty( $desc_text ) ) {
				$desc_text = apply_filters( $lca.'_description_seed', '', $mod, $add_hashtags, $md_idx );
				if ( ! empty( $desc_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'description seed = "'.$desc_text.'"' );
					}
				}
			}

			// check for hashtags in meta or seed desc, remove and then add again after shorten
			if ( preg_match( '/^(.*)(( *#[a-z][a-z0-9\-]+)+)$/U', $desc_text, $match ) ) {
				$desc_text = $match[1];
				$hashtags = trim( $match[2] );
			} elseif ( $mod['is_post'] ) {
				if ( ! empty( $add_hashtags ) && ! empty( $this->p->options['og_desc_hashtags'] ) ) {
					$hashtags = $this->get_hashtags( $mod['id'], $add_hashtags );
				}
			}

			if ( $hashtags && $this->p->debug->enabled ) {
				$this->p->debug->log( 'hashtags found = "'.$hashtags.'"' );
			}

			// if there's no custom description, and no pre-seed,
			// then go ahead and generate the description value
			if ( empty( $desc_text ) ) {

				if ( $mod['is_post'] ) {

					$desc_text = $this->get_the_excerpt( $mod );

					// if there's no excerpt, then fallback to the content
					if ( empty( $desc_text ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'getting the content for post ID '.$mod['id'] );
						}

						$desc_text = $this->get_the_content( $mod, $read_cache, $md_idx );

						// ignore everything before the first paragraph if true
						if ( $this->p->options['plugin_p_strip'] ) {
							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'removing all text before the first paragraph' );
							}
							$desc_text = preg_replace( '/^.*?<p>/i', '', $desc_text );	// question mark makes regex un-greedy
						}
					}

					// fallback to the image alt value
					if ( empty( $desc_text) ) {
						if ( $mod['post_type'] === 'attachment' && strpos( $mod['post_mime'], 'image/' ) === 0 ) {
							$desc_text = get_post_meta( $mod['id'], '_wp_attachment_image_alt', true );
						}
					}

				} elseif ( $mod['is_term'] ) {
					if ( SucomUtil::is_tag_page( $mod['id'] ) ) {
						if ( ! $desc_text = tag_description( $mod['id'] ) ) {
							$term_obj = get_tag( $mod['id'] );
							if ( ! empty( $term_obj->name ) ) {
								$desc_text = sprintf( 'Tagged with %s', $term_obj->name );
							}
						}
					} elseif ( SucomUtil::is_category_page( $mod['id'] ) ) {
						if ( ! $desc_text = category_description( $mod['id'] ) ) {
							$desc_text = sprintf( '%s Category', get_cat_name( $mod['id'] ) );
						}
					} else { 	// other taxonomies
						$term_obj = SucomUtil::get_term_object( $mod['id'], $mod['tax_slug'] );

						if ( ! empty( $term_obj->description ) ) {
							$desc_text = $term_obj->description;
						} elseif ( ! empty( $term_obj->name ) ) {
							$desc_text = $term_obj->name.' Archives';
						}
					}
				} elseif ( $mod['is_user'] ) {
					$user_obj = SucomUtil::get_user_object( $mod['id'] );

					if ( ! empty( $user_obj->description ) ) {
						$desc_text = $user_obj->description;
					} elseif ( ! empty( $user_obj->display_name ) ) {
						$desc_text = sprintf( 'Authored by %s', $user_obj->display_name );
					}

					$desc_text = apply_filters( $lca.'_user_object_description', $desc_text, $user_obj );

				} elseif ( is_day() ) {
					$desc_text = sprintf( 'Daily Archives for %s', get_the_date() );
				} elseif ( is_month() ) {
					$desc_text = sprintf( 'Monthly Archives for %s', get_the_date('F Y') );
				} elseif ( is_year() ) {
					$desc_text = sprintf( 'Yearly Archives for %s', get_the_date('Y') );
				} elseif ( SucomUtil::is_archive_page() ) {	// just in case
					$desc_text = sprintf( 'Archive Page' );
				}
			}

			// if there's still no description, then fallback to a generic version
			if ( empty( $desc_text ) ) {
				if ( $mod['post_status'] === 'auto-draft' ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'post_status is auto-draft: using empty description' );
					}
				} elseif ( ! ( $desc_text = SucomUtil::get_site_description( $this->p->options, $mod ) ) ) {
					$desc_text = 'No Description';	// just in case
				}
			}

			$strlen_pre_cleanup = $this->p->debug->enabled ? strlen( $desc_text ) : 0;
			$desc_text = $this->p->util->cleanup_html_tags( $desc_text, true, $this->p->options['plugin_use_img_alt'] );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'description strlen before html cleanup '.
					$strlen_pre_cleanup.' and after '.strlen( $desc_text ) );
			}

			if ( $textlen > 0 ) {
				$desc_text = apply_filters( $this->p->cf['lca'].'_description_pre_limit', $desc_text );

				if ( ! empty( $add_hashtags ) && ! empty( $hashtags ) ) {
					$textlen = $textlen - strlen( $hashtags ) - 1;
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'description strlen before limit length '.
						strlen( $desc_text ).' (limiting to '.$textlen.' chars)' );
				}

				$desc_text = $this->p->util->limit_text_length( $desc_text, $textlen, $trailing, false );	// $cleanup_html = false

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'description limit text length skipped' );
			}

			if ( ! empty( $add_hashtags ) && ! empty( $hashtags ) ) {
				$desc_text .= ' '.$hashtags;
			}

			if ( $do_encode === true ) {
				$desc_text = SucomUtil::encode_html_emoji( $desc_text );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( 'render description' );	// end timer
			}

			return apply_filters( $lca.'_description', $desc_text, $mod, $add_hashtags, $md_idx );
		}

		public function get_the_excerpt( array $mod ) {

			$excerpt_text = '';

			// use the excerpt, if we have one
			if ( $mod['is_post'] && has_excerpt( $mod['id'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'getting the excerpt for post id '.$mod['id'] );
				}

				$excerpt_text = get_post_field( 'post_excerpt', $mod['id'] );

				static $filter_excerpt = null;

				if ( $filter_excerpt === null ) {
					$filter_excerpt = empty( $this->p->options['plugin_filter_excerpt'] ) ? false : true;
					$filter_excerpt = apply_filters( $this->p->lca.'_filter_excerpt', $filter_excerpt, $mod );
				}

				if ( $filter_excerpt ) {
					$excerpt_text = $this->p->util->safe_apply_filters( array( 'get_the_excerpt', $excerpt_text ), $mod );
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'skipped the WordPress get_the_excerpt filters' );
				}
			}

			return $excerpt_text;
		}

		// deprecated on 2017/11/19
		public function get_content( array $mod, $read_cache = true, $md_idx = '' ) {
			return $this->get_the_content( $mod, $read_cache, $md_idx );
		}

		public function get_the_content( array $mod, $read_cache = true, $md_idx = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'mod' => $mod,
					'read_cache' => $read_cache,
					'md_idx' => $md_idx,
				) );
			}

			$lca = $this->p->cf['lca'];
			$sharing_url = $this->p->util->get_sharing_url( $mod );
			$content_array = array();
			$cache_index = 0;	// redefined if $cache_exp_secs > 0

			$filter_content = empty( $this->p->options['plugin_filter_content'] ) ? false : true;
			$filter_content = apply_filters( $lca.'_filter_content', $filter_content, $mod );

			/*
			 * Note that cache_id is a unique identifier for the cached data and should be 45 characters or
			 * less in length. If using a site transient, it should be 40 characters or less in length.
			 */
			static $cache_exp_secs = null;	// filter the cache expiration value only once
			$cache_md5_pre = $lca.'_c_';
			if ( ! isset( $cache_exp_secs ) ) {	// filter cache expiration if not already set
				$cache_exp_filter = $this->p->cf['wp']['wp_cache'][$cache_md5_pre]['filter'];
				$cache_opt_key = $this->p->cf['wp']['wp_cache'][$cache_md5_pre]['opt_key'];
				$cache_exp_secs = (int) apply_filters( $cache_exp_filter, $this->p->options[$cache_opt_key] );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'sharing url = '.$sharing_url );
				$this->p->debug->log( 'filter content = '.( $filter_content ? 'true' : 'false' ) );
				$this->p->debug->log( 'wp_cache expire = '.$cache_exp_secs );
			}

			/************************
			 * Retrieve the Content *
			 ************************/

			if ( $cache_exp_secs > 0 ) {

				$cache_salt = __METHOD__.'('.SucomUtil::get_mod_salt( $mod, $sharing_url ).')';
				$cache_id = $cache_md5_pre.md5( $cache_salt );
				$cache_index = 'locale:'.SucomUtil::get_locale( $mod ).'_filter:'.( $filter_content ? 'true' : 'false' );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'wp_cache salt = '.$cache_salt );
					$this->p->debug->log( 'wp_cache index = '.$cache_index );
				}

				if ( $read_cache ) {
					$content_array = wp_cache_get( $cache_id, __METHOD__ );
					if ( isset( $content_array[$cache_index] ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'content index found in array from wp_cache '.$cache_id );
						}
						return $content_array[$cache_index];
					} else {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'content index not in array from wp_cache '.$cache_id );
						}
						if ( ! is_array( $content_array ) ) {	// just in case
							$content_array = array();
						}
					}
				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'read cache for content is false' );
				}
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'content array wp_cache is disabled' );
			}

			$content_array[$cache_index] = false;
			$content_text =& $content_array[$cache_index];
			$content_text = apply_filters( $lca.'_content_seed', '', $mod, $read_cache, $md_idx );

			if ( ! empty( $content_text ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'content seed is "'.$content_text.'"' );
				}
			} elseif ( $mod['is_post'] ) {

				$content_text = get_post_field( 'post_content', $mod['id'] );

				if ( empty( $content_text ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: no post_content for post id '.$mod['id'] );
					}
					return false;
				}
			}

			/***********************
			 * Modify The Content  *
			 ***********************/

			// save content length (for comparison) before making changes
			$strlen_before_filters = strlen( $content_text );

			// remove singlepics, which we detect and use before-hand
			$content_text = preg_replace( '/\[singlepic[^\]]+\]/', '', $content_text, -1, $count );

			if ( $count > 0 ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( $count.' [singlepic] shortcode(s) removed from content' );
				}
			}

			if ( $filter_content ) {

				$content_text = $this->p->util->safe_apply_filters( array( 'the_content', $content_text ),
					$mod, WPSSO_CONTENT_FILTERS_MAX_TIME, WPSSO_CONTENT_BLOCK_FILTER_OUTPUT );

				/*
				 * Cleanup for NextGEN Gallery pre-v2 album shortcode.
				 */
				unset ( $GLOBALS['subalbum'] );
				unset ( $GLOBALS['nggShowGallery'] );

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'the_content filters skipped (shortcodes not expanded)' );
			}

			$content_text = preg_replace( '/[\s\n\r]+/s', ' ', $content_text );		// put everything on one line
			$content_text = preg_replace( '/^.*<!--'.$lca.'-content-->(.*)<!--\/'.
				$lca.'-content-->.*$/', '$1', $content_text );

			// remove "Google+" link and text
			if ( strpos( $content_text, '>Google+<' ) !== false ) {
				$content_text = preg_replace( '/<a +rel="author" +href="" +style="display:none;">Google\+<\/a>/', ' ', $content_text );
			}

			if ( strpos( $content_text, '<p class="wp-caption-text">' ) !== false ) {
				$caption_prefix = isset( $this->p->options['plugin_p_cap_prefix'] ) ?
					$this->p->options['plugin_p_cap_prefix'] : 'Caption:';
				if ( ! empty( $caption_prefix ) ) {
					$content_text = preg_replace( '/<p class="wp-caption-text">/', '${0}'.$caption_prefix.' ', $content_text );
				}
			}

			if ( strpos( $content_text, ']]>' ) !== false ) {
				$content_text = str_replace( ']]>', ']]&gt;', $content_text );
			}

			$strlen_after_filters = strlen( $content_text );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'content strlen before '.$strlen_before_filters.' and after changes / filters '.$strlen_after_filters );
			}

			// apply filters before caching
			$content_array[$cache_index] = apply_filters( $lca.'_content', $content_text, $mod, $read_cache, $md_idx );

			if ( $cache_exp_secs > 0 ) {
				wp_cache_add_non_persistent_groups( array( __METHOD__ ) );	// only some caching plugins support this feature
				wp_cache_set( $cache_id, $content_array, __METHOD__, $cache_exp_secs );
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'content array saved to wp_cache for '.$cache_exp_secs.' seconds');
				}
			}

			return $content_array[$cache_index];
		}

		public function get_article_section( $post_id ) {
			$section = '';

			if ( ! empty( $post_id ) ) {
				// get_options() returns null if an index key is not found
				$section = $this->p->m['util']['post']->get_options( $post_id, 'og_art_section' );
			}

			if ( ! empty( $section ) ) {	// must be a non-empty string
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'found custom meta article section = '.$section );
				}
			} else {
				$section = $this->p->options['og_art_section'];
			}

			if ( $section === 'none' ) {
				$section = '';
			}

			return apply_filters( $this->p->cf['lca'].'_article_section', $section, $post_id );
		}

		public function get_hashtags( $post_id, $add_hashtags = true ) {

			if ( empty( $add_hashtags ) ) {	// check for false or 0
				return;
			} elseif ( is_numeric( $add_hashtags ) ) {
				$max_hashtags = $add_hashtags;
			} elseif ( ! empty( $this->p->options['og_desc_hashtags'] ) ) {
				$max_hashtags = $this->p->options['og_desc_hashtags'];
			} else {
				return;
			}

			$hashtags = apply_filters( $this->p->cf['lca'].'_hashtags_seed', '', $post_id, $add_hashtags );
			if ( ! empty( $hashtags ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'hashtags seed = "'.$hashtags.'"' );
				}
			} else {
				$tags = array_slice( $this->get_tags( $post_id ), 0, $max_hashtags );
				if ( ! empty( $tags ) ) {
					// remove special character incompatible with Twitter
					$hashtags = SucomUtil::array_to_hashtags( $tags );
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'hashtags (max '.$max_hashtags.') = "'.$hashtags.'"' );
					}
				}
			}

			return apply_filters( $this->p->cf['lca'].'_hashtags', $hashtags, $post_id, $add_hashtags );
		}

		public function get_tags( $post_id ) {

			$tags = apply_filters( $this->p->cf['lca'].'_tags_seed', array(), $post_id );
			if ( ! empty( $tags ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'tags seed = "'.implode( ',', $tags ).'"' );
				}
			} else {
				if ( is_singular() || ! empty( $post_id ) ) {
					$tags = $this->get_wp_tags( $post_id );
					if ( isset( $this->p->m['media']['ngg'] ) &&
						$this->p->options['og_ngg_tags'] &&
							$this->p->avail['*']['featured'] &&
								has_post_thumbnail( $post_id ) ) {

						$pid = get_post_thumbnail_id( $post_id );

						// featured images from ngg pre-v2 had 'ngg-' prefix
						if ( is_string( $pid ) && substr( $pid, 0, 4 ) == 'ngg-' ) {
							$tags = array_merge( $tags, $this->p->m['media']['ngg']->get_tags( $pid ) );
						}
					}
				} elseif ( is_search() ) {
					$tags = preg_split( '/ *, */', get_search_query( false ) );
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'raw tags = "'.implode( ', ', $tags ).'"' );
				}
				$tags = array_unique( array_map( array( 'SucomUtil', 'sanitize_tag' ), $tags ) );
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'sanitized tags = "'.implode( ', ', $tags ).'"' );
				}
			}

			return apply_filters( $this->p->cf['lca'].'_tags', $tags, $post_id );
		}

		public function get_wp_tags( $post_id ) {

			$tags = apply_filters( $this->p->cf['lca'].'_wp_tags_seed', array(), $post_id );
			if ( ! empty( $tags ) ) {
				$this->p->debug->log( 'wp tags seed = "'.implode( ',', $tags ).'"' );
			} else {
				$post_ids = array ( $post_id );	// array of one

				// add the parent tags if option is enabled
				if ( $this->p->options['og_page_parent_tags'] && is_page( $post_id ) ) {
					$post_ids = array_merge( $post_ids, get_post_ancestors( $post_id ) );
				}
				foreach ( $post_ids as $id ) {
					if ( $this->p->options['og_page_title_tag'] && is_page( $id ) ) {
						$tags[] = SucomUtil::sanitize_tag( get_the_title( $id ) );
					}
					foreach ( wp_get_post_tags( $id, array( 'fields' => 'names') ) as $tag_name ) {
						$tags[] = $tag_name;
					}
				}
			}
			return apply_filters( $this->p->cf['lca'].'_wp_tags', $tags, $post_id );
		}

		public function get_category_title( $term_id = 0, $tax_slug = '', $separator = false ) {

			$title_text = '';

			if ( is_object( $term_id ) ) {
				$term_obj = $term_id;
			} else {
				$term_obj = SucomUtil::get_term_object( $term_id, $tax_slug );
			}

			if ( $separator === false ) {
				$separator = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			if ( isset( $term_obj->name ) ) {
				$title_text = $term_obj->name.' Archives '.$separator.' ';	// default value
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'name property missing in term object' );
			}

			$cat = get_category( $term_obj->term_id );

			if ( ! empty( $cat->category_parent ) ) {

				$cat_parents = get_category_parents( $term_obj->term_id, false, ' '.$separator.' ', false );

				if ( is_wp_error( $cat_parents ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'get_category_parents error: '.$cat_parents->get_error_message() );
					}
				} else {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'get_category_parents() = "'.$cat_parents.'"' );
					}
					if ( ! empty( $cat_parents ) ) {
						$title_text = $cat_parents;
					}
				}
			}

			return apply_filters( 'wp_title', $title_text, $separator, 'right' );
		}
	}
}

?>
