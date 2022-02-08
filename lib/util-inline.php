<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
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
		 * $extras can be an associative array with key/value pairs to be replaced.
		 *
		 * See WpssoHead->add_mt_singles().
		 * See WpssoPage->get_title().
		 * See WpssoPage->get_description().
		 */
		public function replace_variables( $subject, $mod = false, array $atts = array(), array $extras = array() ) {

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

			/**
			 * Get the default search => replace associative array.
			 */
			$vars = $this->get_defaults( $mod, $atts );

			/**
			 * Maybe add extra search => replace values.
			 */
			if ( ! empty( $extras ) && SucomUtil::is_assoc( $extras ) ) {

				$vars = array_merge( $vars, $extras );
			}

			/**
			 * Encode all values for use as URL query arguments.
			 */
			if ( ! empty( $atts[ 'rawurlencode' ] ) ) {

				foreach ( $vars as $match => $val ) {

					$vars[ $match ] = rawurlencode( $val );
				}
			}

			/**
			 * Create the str_replace() arguments.
			 */
			$search  = array();
			$replace = array();

			foreach ( $vars as $match => $val ) {

				$search[]  = '%%' . $match . '%%';
				$replace[] = $val;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$atts', $atts );
				$this->p->debug->log_arr( '$extras', $extras );
				$this->p->debug->log_arr( '$vars', $vars );
			}

			unset( $atts, $extras, $vars );

			return str_replace( $search, $replace, $subject );
		}

		/**
		 * Since WPSSO Core v10.0.0.
		 */
		public function get_defaults( array $mod, array $atts ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_prevent_recursion = false;

			if ( $local_prevent_recursion ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: recursion detected' );
				}

				return array();
			}

			$local_prevent_recursion = true;

			$date_format         = get_option( 'date_format' );
			$time_format         = get_option( 'time_format' );
			$add_page            = isset( $atts[ 'add_page' ] ) ? $atts[ 'add_page' ] : true;
			$page_number         = $this->u->get_page_number( $mod, $add_page );
			$page_total          = $mod[ 'paged_total' ];
			$def_title_sep       = html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			$sep                 = isset( $atts[ 'title_sep' ] ) ? $atts[ 'title_sep' ] : $def_title_sep;
			$canonical_url       = empty( $atts[ 'canonical_url' ] ) ? $this->u->get_canonical_url( $mod, $add_page ) : $atts[ 'canonical_url' ];
			$canonical_short_url = empty( $atts[ 'canonical_short_url' ] ) ? $this->u->get_canonical_short_url( $mod, $add_page ) : $atts[ 'canonical_short_url' ];
			$sharing_url         = empty( $atts[ 'sharing_url' ] ) ? $this->u->get_sharing_url( $mod, $add_page, $atts ) : $atts[ 'sharing_url' ];
			$sharing_short_url   = empty( $atts[ 'sharing_short_url' ] ) ? $this->u->get_sharing_short_url( $mod, $add_page, $atts ) : $atts[ 'sharing_short_url' ];
			$author_name         = empty( $atts[ 'author_name' ] ) ? WpssoUser::get_author_name( $mod ) : empty( $atts[ 'author_name' ] );

			/**
			 * When possible, try and provide the same variable names as Yoast SEO.
			 *
			 * See https://yoast.com/help/list-available-snippet-variables-yoast-seo/.
			 * See wordpress/wp-content/plugins/wordpress-seo/inc/class-wpseo-replace-vars.php.
			 */
			$ret = array(
				'canonical_url'       => $canonical_url,
				'canonical_short_url' => $canonical_short_url,
				'sharing_url'         => $sharing_url,
				'sharing_short_url'   => $sharing_short_url,
				'short_url'           => '',	// Placeholder.
				'request_url'         => is_admin() ? $canonical_url : SucomUtil::get_url( $remove_tracking = true ),
				'sitename'            => SucomUtil::get_site_name( $this->p->options, $mod ),
				'sitealtname'         => SucomUtil::get_site_name_alt( $this->p->options, $mod ),
				'sitedesc'            => SucomUtil::get_site_description( $this->p->options, $mod ),
				'sep'                 => $sep,
				'title'               => $this->p->page->get_the_title( $mod, $sep ),
				'page'                => sprintf( $sep . ' ' . __( 'Page %1$d of %2$d', 'wpsso' ), $page_number, $page_total ),
				'pagename'            => isset( $mod[ 'query_vars' ][ 'pagename' ] ) ? $mod[ 'query_vars' ][ 'pagename' ] : '',
				'pagenumber'          => $page_number,
				'pagetotal'           => $page_total,
				'name'                => $author_name,
				'comment_author'      => empty( $mod[ 'comment_author_name' ] ) ? '' : $mod[ 'comment_author_name' ],
				'comment_date'        => empty( $mod[ 'comment_time' ] ) ? '' : mysql2date( $date_format, $mod[ 'comment_time' ] ),
				'post_date'           => empty( $mod[ 'post_time' ] ) ? '' : mysql2date( $date_format, $mod[ 'post_time' ] ),
				'post_modified'       => empty( $mod[ 'post_modified_time' ] ) ? '' : mysql2date( $date_format, $mod[ 'post_modified_time' ] ),
				'query_search'        => isset( $mod[ 'query_vars' ][ 's' ] ) ? $mod[ 'query_vars' ][ 's' ] : '',
				'query_year'          => isset( $mod[ 'query_vars' ][ 'year' ] ) ? $mod[ 'query_vars' ][ 'year' ] : '',
				'query_month'         => '',	// Placeholder.
				'query_monthnum'      => isset( $mod[ 'query_vars' ][ 'monthnum' ] ) ? $mod[ 'query_vars' ][ 'monthnum' ] : '',
				'query_day'           => isset( $mod[ 'query_vars' ][ 'day' ] ) ? $mod[ 'query_vars' ][ 'day' ] : '',
				'searchphrase'        => '',	// Placeholder.
			);

			/**
			 * See https://developer.wordpress.org/reference/functions/single_month_title/.
			 */
			if ( empty( $ret[ 'query_year' ] ) && empty( $ret[ 'query_monthnum' ] ) ) {

				if ( ! empty( $mod[ 'query_vars' ][ 'm' ] ) ) {

					$ret[ 'query_year' ]     = substr( $mod[ 'query_vars' ][ 'm' ], 0, 4 );
					$ret[ 'query_monthnum' ] = substr( $mod[ 'query_vars' ][ 'm' ], 4, 2 );
				}
			}

			global $wp_locale;

			$ret[ 'query_month' ] = $ret[ 'query_monthnum' ] ? $wp_locale->get_month( $ret[ 'query_monthnum' ] ) : '';

			/**
			 * Compatibility for Yoast SEO.
			 */
			$ret[ 'searchphrase' ] = $ret[ 'query_search' ];

			/**
			 * Compatibility for old WPSSO RRSSB templates.
			 */
			$ret[ 'short_url' ] = $ret[ 'sharing_short_url' ];

			$local_prevent_recursion = false;

			return $ret;
		}

		/**
		 * Deprecated since 2022/01/30.
		 */
		public function get_variables() {

			_deprecated_function( __METHOD__ . '()', '2022/01/30', $replacement = '' );	// Deprecation message.

			return array();
		}

		/**
		 * Deprecated since 2022/01/30.
		 */
		public function get_values( $mod = false, $atts = array() ) {

			_deprecated_function( __METHOD__ . '()', '2022/01/30', $replacement = '' );	// Deprecation message.

			return array();
		}
	}
}
