<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoUtilInline' ) ) {

	class WpssoUtilInline {

		private $p;	// Wpsso class object.
		private $u;	// WpssoUtil class object.

		/*
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin, &$util ) {

			$this->p =& $plugin;
			$this->u =& $util;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		/*
		 * Replace inline variables in the subject text.
		 *
		 * $atts can be an associative array with additional information ('canonical_url', 'canonical_short_url', 'add_page', etc.).
		 *
		 * See WpssoHead->add_mt_singles().
		 * See WpssoPage->get_title().
		 * See WpssoPage->get_description().
		 */
		public function replace_variables( $subject, $mod = false, array $atts = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( false === strpos( $subject, '%%' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no inline variables in subject' );
				}

				return $subject;
			}

			/*
			 * The $mod array argument is preferred but not required.
			 *
			 * $mod = true | false | post_id | $mod array
			 */
			if ( ! is_array( $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'optional call to WpssoPage->get_mod()' );
				}

				$mod = $this->p->page->get_mod( $mod );
			}

			/*
			 * Use a callback to get the inline variable values we need, as we need them.
			 *
			 * See https://www.php.net/manual/en/function.preg-replace-callback.php.
			 */
			$callback = function( $matches ) use ( $mod, $atts ) {

				return $this->replace_callback( $matches, $mod, $atts );
			};

			static $depth = 0;

			$max_depth = WPSSO_INLINE_VARS_MAX_DEPTH;

			while ( ++$depth <= $max_depth && false !== strpos( $subject, '%%' ) ) {

				$subject = preg_replace_callback( '/%%([^%]+)%%/', $callback, $subject );
			}

			$depth = 0;

			return $subject;
		}

		private function replace_callback( array $matches, array $mod, array $atts ) {

			$varname = $matches[ 1 ];
			$ret_val = '';
			$url_enc = empty( $atts[ 'rawurlencode' ] ) ? false : true;

			/*
			 * Some inline variables and values may be passed in the $atts array, so check this array first.
			 */
			if ( isset( $atts[ $varname ] ) ) {

				return $url_enc ? rawurlencode( $atts[ $varname ] ) : $atts[ $varname ];
			}

			/*
			 * Use a local cache for values that will not change for this page load.
			 */
			static $local_cache = null;

			if ( null === $local_cache ) {

				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );
				$charset     = get_bloginfo( $show = 'charset', $filter = 'raw' );

				$local_cache = array(
					'def_title_sep' => html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, $charset ),
					'def_ellipsis'  => html_entity_decode( $this->p->options[ 'og_ellipsis' ], ENT_QUOTES, $charset ),
					'date_format'   => $date_format,
					'time_format'   => $time_format,
					'currentdate'   => date_i18n( $date_format ),
					'currenttime'   => date_i18n( $time_format ),
					'currentday'    => date_i18n( 'j' ),
					'currentmonth'  => date_i18n( 'F' ),
					'currentyear'   => date_i18n( 'Y' ),
				);
			}

			if ( isset( $local_cache[ $varname ] ) ) {

				return $url_enc ? rawurlencode( $local_cache[ $varname ] ) : $local_cache[ $varname ];
			}

			/*
			 * Detect and prevent recursion, just in case.
			 */
			static $local_is_recursion = array();

			if ( ! empty( $local_is_recursion[ $varname ] ) ) {	// Recursion detected.

				return $ret_val;	// Stop here.
			}

			$local_is_recursion[ $varname ] = true;	// Prevent recursion.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting the %%' . $varname . '%% value' );
			}

			$add_page  = isset( $atts[ 'add_page' ] ) ? $atts[ 'add_page' ] : true;
			$title_sep = isset( $atts[ 'title_sep' ] ) ? $atts[ 'title_sep' ] : $local_cache[ 'def_title_sep' ];

			if ( 0 === strpos( $varname, 'post' ) ) {

				if ( $mod[ 'is_post' ] ) {	// Just in case.

					switch ( $varname ) {

						case 'post_date':

							if ( ! empty( $mod[ 'post_time' ] ) ) {

								$ret_val = mysql2date( $local_cache[ 'date_format' ], $mod[ 'post_time' ] );
							}

							break;

						case 'post_modified':
						case 'post_modified_date':

							if ( ! empty( $mod[ 'post_modified_time' ] ) ) {

								$ret_val = mysql2date( $local_cache[ 'date_format' ], $mod[ 'post_modified_time' ] );
							}

							break;

						case 'post_description':	// Used by AIOSEOP.

							$ret_val = $this->p->page->get_the_description( $mod );

							break;

						case 'post_title':	// Used by AIOSEOP.

							$ret_val = $this->p->page->get_the_title( $mod, $title_sep );

							break;
					}
				}

			} elseif ( 0 === strpos( $varname, 'term' ) ) {

				if ( $mod[ 'is_term' ] ) {	// Just in case.

					switch ( $varname ) {

						case 'term':
						case 'term_name':

							$term_obj = $this->p->term->get_mod_wp_object( $mod );

							$ret_val = $term_obj->name;

							break;

						case 'term_description':	// Used by AIOSEOP and Yoast SEO.

							$ret_val = $this->p->page->get_the_description( $mod );

							break;

						case 'term_tax_single':

							$ret_val = $mod[ 'tax_label_single' ];

							break;

						case 'term_tax_single_lower':

							$ret_val = mb_strtolower( $mod[ 'tax_label_single' ] );

							break;

						case 'term_hierarchy':	// Used by Yoast SEO.

							/*
							 * Includes parent names in the term title if the $title_sep value is not empty.
							 *
							 * Use $title_sep = false to avoid adding term parent names in the term title.
							 */
							$term_obj = $this->p->term->get_mod_wp_object( $mod );

							$ret_val = $this->p->page->get_term_title( $term_obj, $title_sep = null );

							break;

						case 'term_title':	// Used by Yoast SEO.

							/*
							 * Includes parent names in the term title if the $title_sep value is not empty.
							 *
							 * Use $title_sep = false to avoid adding term parent names in the term title.
							 */
							$term_obj = $this->p->term->get_mod_wp_object( $mod );

							$ret_val = $this->p->page->get_term_title( $term_obj, $title_sep = false );

							break;
					}
				}

			} elseif ( 0 === strpos( $varname, 'cf_' ) ) {

				if ( $meta_key = substr( $varname, 3 ) ) {	// Just in case.

					if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

						$ret_val = $mod[ 'obj' ]->get_meta( $mod[ 'id' ], $meta_key, $single = true );

						if ( is_array( $ret_val ) ) {	// Just in case.

							$ret_val = implode( $glue = ', ', $ret_val );
						}
					}
				}

			} else {

				switch ( $varname ) {

					case 'org_url':		// Used by Rank Math.
					case 'site_url':

						$ret_val = SucomUtil::get_home_url( $this->p->options, $mod );

						break;

					case 'canonical_url':

						$ret_val = $this->u->get_canonical_url( $mod, $add_page );

						break;

					case 'canonical_short_url':

						$ret_val = $this->u->get_canonical_short_url( $mod, $add_page );

						break;

					case 'description':

						$ret_val = $this->p->page->get_the_description( $mod );

						break;

					case 'sharing_url':

						/*
						 * The $atts array may contain 'utm_medium', 'utm_source', 'utm_campaign', 'utm_content', and 'utm_term'.
						 */
						$ret_val = $this->u->get_sharing_url( $mod, $add_page, $atts );

						break;

					case 'sharing_short_url':
					case 'short_url':	// Used by older WPSSO RRSSB templates.

						/*
						 * The $atts array may contain 'utm_medium', 'utm_source', 'utm_campaign', 'utm_content', and 'utm_term'.
						 */
				 		$ret_val = $this->u->get_sharing_short_url( $mod, $add_page, $atts );

						break;

					case 'request_url':

						if ( is_admin() ) {

							$ret_val = $this->u->get_canonical_url( $mod, $add_page );

						} else {

							$ret_val = SucomUtil::get_url( $remove_ignored_args = true );	// Uses a local cache.

							$ret_val = apply_filters( 'wpsso_server_request_url', $ret_val );
						}

						break;

					case 'org_name':	// Used by Rank Math.
					case 'sitename':
					case 'sitetitle':	// Used by SEOPress.

						$ret_val = SucomUtil::get_site_name( $this->p->options, $mod );

						break;

					case 'sitealtname':

						$ret_val = SucomUtil::get_site_name_alt( $this->p->options, $mod );

						break;

					case 'sitedesc':
					case 'tagline':		// Used by SEOPress.

						$ret_val = SucomUtil::get_site_description( $this->p->options, $mod );

						break;

					case 'sep':
					case 'separator_sa':	// Used by AIOSEOP.

						$ret_val = $title_sep;

						break;

					case 'ellipsis':

						$ret_val = $local_cache[ 'def_ellipsis' ];

						break;

					case 'title':

						$ret_val = $this->p->page->get_the_title( $mod, $title_sep );

						break;

					case 'parent_title':

						if ( $mod[ 'is_post' ] && $mod[ 'post_parent' ] ) {	// Just in case.

							$parent_mod = $this->p->post->get_mod( $mod[ 'post_parent' ] );

							$ret_val = $this->p->page->get_the_title( $parent_mod, $title_sep );
						}

						break;

					case 'author':
					case 'author_name':
					case 'name':		// Used by Yoast SEO.

						/*
						 * Returns the display name for a comment author, post author, or user module.
						 */
						$ret_val = WpssoUser::get_author_name( $mod );

						break;

					case 'page':

						$page_num = $this->u->get_page_number( $mod, $add_page );

						if ( $page_num > 1 ) {

							$page_transl = __( 'Page %1$d of %2$d', 'wpsso' );

							$ret_val = $title_sep . ' ' . sprintf( $page_transl, $page_num, $mod[ 'paged_total' ] );
						}

						break;

					case 'pagename':

						if ( isset( $mod[ 'query_vars' ][ 'pagename' ] ) ) {

							$ret_val = $mod[ 'query_vars' ][ 'pagename' ];
						}

						break;

					case 'pagenumber':

						$ret_val = $this->u->get_page_number( $mod, $add_page );

						break;

					case 'pagetotal':

						$ret_val = $mod[ 'paged_total' ];

						break;

					case 'date':		// Used by Yoast SEO.

						if ( ! empty( $mod[ 'post_time' ] ) ) {

							$ret_val = mysql2date( $local_cache[ 'date_format' ], $mod[ 'post_time' ] );
						}

						break;

					case 'modified':	// Used by Yoast SEO.

						if ( ! empty( $mod[ 'post_modified_time' ] ) ) {

							$ret_val = mysql2date( $local_cache[ 'date_format' ], $mod[ 'post_modified_time' ] );
						}

						break;

					case 'excerpt':

						if ( $mod[ 'is_post' ] ) {	// Just in case.

							$ret_val = $this->p->page->get_the_excerpt( $mod );

							if ( empty( $ret_val ) ) {

								$ret_val = wp_trim_excerpt( '', $mod[ 'id' ] );
							}
						}

						break;

					case 'excerpt_only':

						if ( $mod[ 'is_post' ] ) {	// Just in case.

							$ret_val = $this->p->page->get_the_excerpt( $mod );
						}

						break;

					case 'comment_author':

						$ret_val = $mod[ 'comment_author_name' ];

						break;

					case 'comment_date':

						if ( ! empty( $mod[ 'comment_time' ] ) ) {

							$ret_val = mysql2date( $local_cache[ 'date_format' ], $mod[ 'comment_time' ] );
						}

						break;

					case 'query_search':
					case 'search_keywords':	// Used by SEOPress.
					case 'search_query':	// Used by Rank Math.
					case 'searchphrase':	// Used by Yoast SEO.

						if ( isset( $mod[ 'query_vars' ][ 's' ] ) ) {

							$ret_val = $mod[ 'query_vars' ][ 's' ];
						}

						break;

					case 'query_year':

						if ( isset( $mod[ 'query_vars' ][ 'year' ] ) ) {

							$ret_val = $mod[ 'query_vars' ][ 'year' ];

						} elseif ( ! empty( $mod[ 'query_vars' ][ 'm' ] ) ) {

							$ret_val = substr( $mod[ 'query_vars' ][ 'm' ], 0, 4 );
						}

						break;

					case 'query_month':
					case 'query_monthnum':

						if ( isset( $mod[ 'query_vars' ][ 'monthnum' ] ) ) {

							$ret_val = $mod[ 'query_vars' ][ 'monthnum' ];

						} elseif ( ! empty( $mod[ 'query_vars' ][ 'm' ] ) ) {

							$ret_val = substr( $mod[ 'query_vars' ][ 'm' ], 4, 2 );
						}

						if ( 'query_month' === $varname ) {	// Convert month number to month name.

							global $wp_locale;

							$ret_val = $ret_val ? $wp_locale->get_month( $ret_val ) : '';
						}

						break;

					case 'query_day':

						if ( isset( $mod[ 'query_vars' ][ 'day' ] ) ) {

							$ret_val = $mod[ 'query_vars' ][ 'day' ];
						}

						break;
				}
			}

			unset( $local_is_recursion[ $varname ] );	// Done preventing recursion.

			return $url_enc ? rawurlencode( $ret_val ) : $ret_val;
		}
	}
}
