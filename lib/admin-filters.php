<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoAdminFilters' ) ) {

	/**
	 * Since WPSSO Core v8.5.1.
	 */
	class WpssoAdminFilters {

		private $p;

		/**
		 * Instantiated by WpssoAdmin->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$doing_ajax = SucomUtil::get_const( 'DOING_AJAX' );

			if ( ! $doing_ajax ) {

				$this->p->util->add_plugin_filters( $this, array( 
					'status_pro_features' => 3,
					'status_std_features' => 3,
				), $prio = -10000 );
			}
		}

		/**
		 * Filter for 'wpsso_status_pro_features'.
		 */
		public function filter_status_pro_features( $features, $ext, $info ) {

			$td_class        = self::$pkg[ $ext ][ 'pp' ] ? '' : 'blank';
			$status_on       = self::$pkg[ $ext ][ 'pp' ] ? 'on' : 'rec';
			$apikeys_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_apikeys' );
			$content_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_content' );

			$features[ '(api) Shopper Approved API' ][ 'label_url' ] = $apikeys_tab_url;

			$features[ '(feature) URL Shortening Service' ][ 'label_url' ] = $apikeys_tab_url;

			$features[ '(feature) Use Filtered (aka SEO) Title' ] = array(
				'td_class'     => $td_class,
				'label_transl' => _x( '(feature) Use Filtered (aka SEO) Title', 'lib file description', 'wpsso' ),
				'label_url'    => $content_tab_url,
				'status'       => $this->p->options[ 'plugin_filter_title' ] ? $status_on : 'off',
			);

			$features[ '(feature) Use WordPress Content Filters' ] = array(
				'td_class'     => $td_class,
				'label_transl' => _x( '(feature) Use WordPress Content Filters', 'lib file description', 'wpsso' ),
				'label_url'    => $content_tab_url,
				'status'       => $this->p->options[ 'plugin_filter_content' ] ? $status_on : 'off',
			);

			$features[ '(feature) Use WordPress Excerpt Filters' ] = array(
				'td_class'     => $td_class,
				'label_transl' => _x( '(feature) Use WordPress Excerpt Filters', 'lib file description', 'wpsso' ),
				'label_url'    => $content_tab_url,
				'status'       => $this->p->options[ 'plugin_filter_excerpt' ] ? $status_on : 'off',
			);

			foreach ( $this->p->cf[ 'form' ][ 'shorteners' ] as $svc_id => $name ) {

				if ( 'none' === $svc_id ) {

					continue;
				}

				$name_transl = _x( $name, 'option value', 'wpsso' );

				$label_transl = sprintf( _x( '(api) %s Shortener API', 'lib file description', 'wpsso' ), $name_transl );

				$status = 'off';

				if ( isset( $this->p->m[ 'util' ][ 'shorten' ] ) ) {	// URL shortening service is enabled.

					if ( $svc_id === $this->p->options[ 'plugin_shortener' ] ) {	// Shortener API service ID is selected.

						$status = 'rec';

						if ( $this->p->m[ 'util' ][ 'shorten' ]->get_svc_instance( $svc_id ) ) {	// False or object.

							$status = 'on';
						}
					}
				}

				$features[ '(api) ' . $name . ' Shortener API' ] = array(
					'td_class'     => $td_class,
					'label_transl' => $label_transl,
					'label_url'    => $apikeys_tab_url,
					'status'       => $status,
				);
			}

			return $features;
		}

		/**
		 * Filter for 'wpsso_status_std_features'.
		 */
		public function filter_status_std_features( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$features[ '(code) Facebook / Open Graph Meta Tags' ] = array(
				'label_transl' => _x( '(code) Facebook / Open Graph Meta Tags', 'lib file description', 'wpsso' ),
				'status'       => class_exists( $this->p->lca . 'opengraph' ) ? 'on' : 'rec',
			);

			$features[ '(code) Knowledge Graph Organization Markup' ] = array(
				'label_transl' => _x( '(code) Knowledge Graph Organization Markup', 'lib file description', 'wpsso' ),
				'status'       => 'organization' === $this->p->options[ 'site_pub_schema_type' ] ? 'on' : 'off',
			);

			$features[ '(code) Knowledge Graph Person Markup' ] = array(
				'label_transl' => _x( '(code) Knowledge Graph Person Markup', 'lib file description', 'wpsso' ),
				'status'       => 'person' === $this->p->options[ 'site_pub_schema_type' ] ? 'on' : 'off',
			);

			$features[ '(code) Knowledge Graph WebSite Markup' ] = array(
				'label_transl' => _x( '(code) Knowledge Graph WebSite Markup', 'lib file description', 'wpsso' ),
				'status'       => 'on',
			);

			$features[ '(code) Twitter Card Meta Tags' ] = array(
				'label_transl' => _x( '(code) Twitter Card Meta Tags', 'lib file description', 'wpsso' ),
				'status'       => class_exists( $this->p->lca . 'twittercard' ) ? 'on' : 'rec',
			);

			return $features;
		}
	}
}
