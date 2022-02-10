<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
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

		/**
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin, &$util ) {

			$this->p =& $plugin;
			$this->u =& $util;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		/**
		 * Replace default and extra inline variables in the content text.
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

					$this->p->debug->log( 'exiting early: no inline variables in subject string' );
				}

				return $subject;
			}

			/**
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

			$callback = function( $matches ) use ( $mod, $atts ) {
				
				return $this->replace_callback( $matches, $mod, $atts );
			};

			/**
			 * See https://www.php.net/manual/en/function.preg-replace-callback.php.
			 */
			$subject = preg_replace_callback( '/%%([^%]+)%%/', $callback, $subject );

			return $subject;
		}
			
		private function replace_callback( array $matches, array $mod, array $atts ) {

			$var     = $matches[ 1 ];
			$value   = '';
			$url_enc = empty( $atts[ 'rawurlencode' ] ) ? false : true;

			if ( isset( $atts[ $var ] ) ) {

				return $url_enc ? rawurlencode( $atts[ $var ] ) : $atts[ $var ];
			}

			static $local_cache = null;

			if ( null === $local_cache ) {

				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );

				$local_cache = array(
					'default_sep'  => html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) ),
					'date_format'  => $date_format,
					'time_format'  => $time_format,
					'currentdate'  => date_i18n( $date_format ),
					'currenttime'  => date_i18n( $time_format ),
					'currentday'   => date_i18n( 'j' ),
					'currentmonth' => date_i18n( 'F' ),
					'currentyear'  => date_i18n( 'Y' ),
				);
			}

			if ( isset( $local_cache[ $var ] ) ) {

				return $url_enc ? rawurlencode( $local_cache[ $var ] ) : $local_cache[ $var ];
			}

			static $local_is_recursion = false;

			if ( $local_is_recursion ) {
				
				return $value;
			}

			$local_is_recursion = true;	// Prevent recursion.

			$add_page  = isset( $atts[ 'add_page' ] ) ? $atts[ 'add_page' ] : true;
			$title_sep = isset( $atts[ 'title_sep' ] ) ? $atts[ 'title_sep' ] : $local_cache[ 'default_sep' ];

			switch ( $var ) {

				case 'canonical_url':

					$value = $this->u->get_canonical_url( $mod, $add_page );

					break;

				case 'canonical_short_url':
				
					$value = $this->u->get_canonical_short_url( $mod, $add_page );

					break;

				case 'sharing_url':
			
					/**
					 * The $atts array may contain 'utm_medium', 'utm_source', 'utm_campaign', 'utm_content', and 'utm_term'.
					 */
					$value = $this->u->get_sharing_url( $mod, $add_page, $atts );

					break;

				case 'sharing_short_url':
				case 'short_url':	// Compatibility for older WPSSO RRSSB templates.
				
					/**
					 * The $atts array may contain 'utm_medium', 'utm_source', 'utm_campaign', 'utm_content', and 'utm_term'.
					 */
			 		$value = $this->u->get_sharing_short_url( $mod, $add_page, $atts );

					break;

				case 'request_url':
			
					if ( is_admin() ) {

						$value = $this->u->get_canonical_url( $mod, $add_page );

					} else {

						$value = SucomUtil::get_url( $remove_tracking = true );
					}

					break;

				case 'sitename':
				
					$value = SucomUtil::get_site_name( $this->p->options, $mod );

					break;

				case 'sitealtname':
				
					$value = SucomUtil::get_site_name_alt( $this->p->options, $mod );

					break;

				case 'sitedesc':
				
					$value = SucomUtil::get_site_description( $this->p->options, $mod );

					break;

				case 'sep':
				
					$value = $title_sep;

					break;

				case 'title':
				
					$value = $this->p->page->get_the_title( $mod, $title_sep );

					break;

				case 'author':
				case 'name':	// Compatibility with Yoast SEO.
				
					$value = WpssoUser::get_author_name( $mod );

					break;

				case 'page':
				
					$page_num = $this->u->get_page_number( $mod, $add_page );

					if ( $page_num > 1 ) {

						$page_transl = __( 'Page %1$d of %2$d', 'wpsso' );

						$value = $title_sep . ' ' . sprintf( $page_transl, $page_num, $mod[ 'paged_total' ] );
					}

					break;

				case 'pagename':

					if ( isset( $mod[ 'query_vars' ][ 'pagename' ] ) ) {
					
						$value = $mod[ 'query_vars' ][ 'pagename' ];
					}

					break;

				case 'pagenumber':
				
					$value = $this->u->get_page_number( $mod, $add_page );

					break;

				case 'pagetotal':
				
					$value = $mod[ 'paged_total' ];

					break;

				case 'post_date':
				case 'date':	// Compatibility with Yoast SEO.
				
					if ( ! empty( $mod[ 'post_time' ] ) ) {
					
						$value = mysql2date( $local_cache[ 'date_format' ], $mod[ 'post_time' ] );
					}

					break;

				case 'post_modified':
				case 'modified':	// Compatibility with Yoast SEO.
				
					if ( ! empty( $mod[ 'post_modified_time' ] ) ) {
					
						$value = mysql2date( $local_cache[ 'date_format' ], $mod[ 'post_modified_time' ] );
					}

					break;

				case 'comment_author':
				
					$value = $mod[ 'comment_author_name' ];

					break;

				case 'comment_date':
				
					if ( ! empty( $mod[ 'comment_time' ] ) ) {
					
						$value = mysql2date( $local_cache[ 'date_format' ], $mod[ 'comment_time' ] );
					}

					break;

				case 'query_search':
				case 'searchphrase':	// Compatibility with Yoast SEO.
				
					if ( isset( $mod[ 'query_vars' ][ 's' ] ) ) {
					
						$value = $mod[ 'query_vars' ][ 's' ];
					}

					break;

				case 'query_year':
				
					if ( isset( $mod[ 'query_vars' ][ 'year' ] ) ) {
					
						$value = $mod[ 'query_vars' ][ 'year' ];
				
					} elseif ( ! empty( $mod[ 'query_vars' ][ 'm' ] ) ) {

						$value = substr( $mod[ 'query_vars' ][ 'm' ], 0, 4 );
					}

					break;

				case 'query_month':
				case 'query_monthnum':
				
					if ( isset( $mod[ 'query_vars' ][ 'monthnum' ] ) ) {
					
						$value = $mod[ 'query_vars' ][ 'monthnum' ];

					} elseif ( ! empty( $mod[ 'query_vars' ][ 'm' ] ) ) {

						$value = substr( $mod[ 'query_vars' ][ 'm' ], 4, 2 );
					}

					/**
					 * Convert month number to the month name.
					 */
					if ( 'query_month' === $var ) {
	
						$value = $value ? $wp_locale->get_month( $value ) : '';
					}

					break;

				case 'query_day':
				
					if ( isset( $mod[ 'query_vars' ][ 'day' ] ) ) {
					
						$value = $mod[ 'query_vars' ][ 'day' ];
					}

					break;
			}

			$local_is_recursion = false;

			return $url_enc ? rawurlencode( $value ) : $value;
		}
	}
}
