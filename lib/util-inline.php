<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
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
		 */
		public function replace_variables( $content, $mod = false, $atts = array(), $extras = array() ) {


			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$atts', $atts );
				$this->p->debug->log_arr( '$extras', $extras );
			}

			if ( false === strpos( $content, '%%' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no inline vars' );
				}

				return $content;
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

			$variables = $this->get_variables();

			$values = $this->get_values( $mod, $atts );

			if ( ! empty( $extras ) && SucomUtil::is_assoc( $extras ) ) {

				foreach ( $extras as $match => $val ) {

					$variables[] = '%%' . $match . '%%';

					$values[] = $val;
				}
			}

			if ( ! empty( $atts[ 'rawurlencode' ] ) ) {

				foreach ( $values as $num => $val ) {

					$values[ $num ] = rawurlencode( $val );
				}
			}

			ksort( $variables );

			ksort( $values );

			return str_replace( $variables, $values, $content );
		}

		public function get_variables() {

			$variables = array(
				'%%canonical_url%%',
				'%%canonical_short_url%%',
				'%%sharing_url%%',
				'%%sharing_short_url%%',
				'%%short_url%%',	// Same as %%sharing_short_url%% (required for old WPSSO RRSSB templates).
				'%%request_url%%',
				'%%sitename%%',
				'%%sitealtname%%',
				'%%sitedesc%%',
				'%%sep%%',
				'%%title%%',
				'%%pagenumber%%',
				'%%pagetotal%%',
				'%%page%%',
			);

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$variables', $variables );
			}

			return $variables;
		}

		public function get_values( $mod = false, $atts = array() ) {

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

			$add_page            = isset( $atts[ 'add_page' ] ) ? $atts[ 'add_page' ] : true;
			$title_sep           = isset( $atts[ 'title_sep' ] ) ? $atts[ 'title_sep' ] :
				html_entity_decode( $this->p->options[ 'og_title_sep' ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			$canonical_url       = empty( $atts[ 'canonical_url' ] ) ? $this->u->get_canonical_url( $mod, $add_page ) : $atts[ 'canonical_url' ];
			$canonical_short_url = empty( $atts[ 'canonical_short_url' ] ) ? $this->u->get_canonical_short_url( $mod, $add_page ) : $atts[ 'canonical_short_url' ];
			$sharing_url         = empty( $atts[ 'sharing_url' ] ) ? $this->u->get_sharing_url( $mod, $add_page, $atts ) : $atts[ 'sharing_url' ];
			$sharing_short_url   = empty( $atts[ 'sharing_short_url' ] ) ? $this->u->get_sharing_short_url( $mod, $add_page, $atts ) : $atts[ 'sharing_short_url' ];
			$request_url         = is_admin() ? $canonical_url : SucomUtil::get_url( $remove_tracking = true );
			$sitename            = SucomUtil::get_site_name( $this->p->options, $mod );
			$sitealtname         = SucomUtil::get_site_name_alt( $this->p->options, $mod );
			$sitedesc            = SucomUtil::get_site_description( $this->p->options, $mod );
			$title               = $this->p->page->get_the_title( $mod, $title_sep );
			$page_number         = $this->u->get_page_number( $mod, $add_page );
			$page_total          = $this->u->get_page_total( $mod );
			$page                = sprintf( $title_sep . ' ' . __( 'Page %1$d of %2$d', 'wpsso' ), $page_number, $page_total );

			$values = array(
				$canonical_url,		// %%canonical_url%%
				$canonical_short_url,	// %%canonical_short_url%%
				$sharing_url,		// %%sharing_url%%
				$sharing_short_url,	// %%sharing_short_url%%
				$sharing_short_url,	// %%short_url%% (required for old WPSSO RRSSB templates).
				$request_url,		// %%request_url%%
				$sitename,		// %%sitename%%
				$sitealtname,		// %%sitealtname%%
				$sitedesc,		// %%sitedesc%%
				$title_sep,		// %%sep%%
				$title,			// %%title%%
				$page_number,		// %%pagenumber%%
				$page_total,		// %%pagetotal%%
				$page,			// %%page%%
			);

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$values', $values );
			}

			return $values;
		}
	}
}
