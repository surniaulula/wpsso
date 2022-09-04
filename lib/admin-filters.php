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

if ( ! class_exists( 'WpssoAdminFilters' ) ) {

	/**
	 * Since WPSSO Core v8.5.1.
	 */
	class WpssoAdminFilters {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by WpssoAdmin->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) {

				$this->p->util->add_plugin_filters( $this, array( 
					'status_std_features' => 3,
				), $prio = -10000 );
			}
		}

		/**
		 * Filter for 'wpsso_status_std_features'.
		 */
		public function filter_status_std_features( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$features = $this->filter_status_std_features_basic( $features, $ext, $info );
			$features = $this->filter_status_std_features_integ( $features, $ext, $info );
			$features = $this->filter_status_std_features_schema( $features, $ext, $info );
			$features = $this->filter_status_std_features_seo( $features, $ext, $info );
			$features = $this->filter_status_std_features_shorten( $features, $ext, $info );

			return $features;
		}

		private function filter_status_std_features_basic( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$features[ '(code) Facebook / Open Graph Meta Tags' ] = array(
				'label_transl' => _x( '(code) Facebook / Open Graph Meta Tags', 'lib file description', 'wpsso' ),
				'status'       => class_exists( 'WpssoOpengraph' ) ? 'on' : 'recommended',
			);

			$features[ '(code) Pinterest Hidden Image' ] = array(
				'label_transl' => _x( '(code) Pinterest Hidden Image', 'lib file description', 'wpsso' ),
				'label_url'    => $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_pinterest' ),
				'status'       => $this->p->util->is_pin_img_disabled() ? 'off' : 'on',
			);

			$features[ '(code) Schema JSON-LD Markup' ] = array(
				'label_transl' => _x( '(code) Schema JSON-LD Markup', 'lib file description', 'wpsso' ),
				'status'       => $this->p->util->is_schema_disabled() ? 'recommended' : 'on',
			);

			$features[ '(code) Twitter Card Meta Tags' ] = array(
				'label_transl' => _x( '(code) Twitter Card Meta Tags', 'lib file description', 'wpsso' ),
				'status'       => class_exists( 'WpssoTwitterCard' ) ? 'on' : 'recommended',
			);

			$features[ '(code) oEmbed Response Enhancements' ] = array(
				'label_transl' => _x( '(code) oEmbed Response Enhancements', 'lib file description', 'wpsso' ),
				'status'       => class_exists( 'WpssoOembed' ) && function_exists( 'get_oembed_response_data' ) ? 'on' : 'recommended',
			);

			return $features;
		}

		private function filter_status_std_features_integ( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$pkg_info      = $this->p->util->get_pkg_info();	// Uses a local cache.
			$integ_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration' );

			foreach ( array( 'integ' ) as $type_dir ) {

				if ( empty( $info[ 'lib' ][ $type_dir ] ) ) {	// Just in case.

					continue;
				}

				foreach ( $info[ 'lib' ][ $type_dir ] as $sub_dir => $libs ) {

					if ( is_array( $libs ) ) {

						foreach ( $libs as $id => $label ) {

							$label_transl = _x( $label, 'lib file description', 'wpsso' );
							$classname    = SucomUtil::sanitize_classname( 'wpsso' . $type_dir . $sub_dir . $id, $allow_underscore = false );

							$features[ $label ][ 'label_transl' ] = $label_transl;
							$features[ $label ][ 'status' ]       = class_exists( $classname ) ? 'on' : 'off';

							if ( 'data' === $sub_dir ) {
							
								$features[ $label ][ 'label_url' ] = $integ_tab_url;
							}
						}
					}
				}
			}

			/**
			 * SSO > Advanced Settings > Plugin Settings > Integration > Webpage Title Tag.
			 */
			$features[ '(code) Webpage Title Tag' ] = array(
				'label_transl' => _x( '(code) Webpage Title Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->util->is_seo_title_disabled() ? 'off' : 'on',
			);

			/**
			 * SSO > Advanced Settings > Plugin Settings > Integration > Image Dimension Checks.
			 */
			$features[ '(feature) Image Dimension Checks' ] = array(
				'label_transl' => _x( '(feature) Image Dimension Checks', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_check_img_dims' ] ? 'on' : 'recommended',
			);

			/**
			 * SSO > Advanced Settings > Plugin Settings > Integration > Inherit Custom Images.
			 */
			$features[ '(feature) Inherit Custom Images' ] = array(
				'label_transl' => _x( '(feature) Inherit Custom Images', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_inherit_custom' ] ? 'on' : 'off',
			);

			/**
			 * SSO > Advanced Settings > Plugin Settings > Integration > Inherit Featured Image.
			 */
			$features[ '(feature) Inherit Featured Image' ] = array(
				'label_transl' => _x( '(feature) Inherit Featured Image', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_inherit_featured' ] ? 'on' : 'off',
			);

			/**
			 * SSO > Advanced Settings > Plugin Settings > Integration > Use Filtered Content.
			 */
			$features[ '(feature) Use Filtered Content' ] = array(
				'label_transl' => _x( '(feature) Use Filtered Content', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_filter_content' ] ? 'on' : 'recommended',
			);

			/**
			 * SSO > Advanced Settings > Plugin Settings > Integration > Use Filtered Excerpt.
			 */
			$features[ '(feature) Use Filtered Excerpt' ] = array(
				'label_transl' => _x( '(feature) Use Filtered Excerpt', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_filter_excerpt' ] ? 'on' : 'off',
			);

			/**
			 * SSO > Advanced Settings > Plugin Settings > Integration > Upscale Media Library Images.
			 */
			$features[ '(feature) Upscale Media Library Images' ] = array(
				'label_transl' => _x( '(feature) Upscale Media Library Images', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_upscale_images' ] ? 'on' : 'off',
			);

			return $features;
		}

		private function filter_status_std_features_schema( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$google_tab_url = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_google' );

			/**
			 * To optimize performance and memory usage, the 'wpsso_init_json_filters' action is run at the start of
			 * WpssoSchema->get_json_data() when the Schema filters are needed. The Wpsso->init_json_filters() action
			 * then unhooks itself from the action, so it can only be run once.
			 */
			do_action( 'wpsso_init_json_filters' );

			foreach ( array( 'json' ) as $type_dir ) {

				if ( empty( $info[ 'lib' ][ $type_dir ] ) ) {	// Just in case.

					continue;
				}

				foreach ( $info[ 'lib' ][ $type_dir ] as $sub_dir => $libs ) {

					if ( is_array( $libs ) ) {

						foreach ( $libs as $id => $label ) {

							$label_transl = _x( $label, 'lib file description', 'wpsso' );

							$classname = SucomUtil::sanitize_classname( 'wpsso' . $type_dir . $sub_dir . $id, $allow_underscore = false );

							if ( preg_match( '/^(.*) \[schema_type:(.+)\]$/', $label_transl, $match ) ) {

								$count = $this->p->schema->count_schema_type_children( $match[ 2 ] );

								$label_transl = $match[ 1 ] . ' ' . sprintf( __( '(%d sub-types)', 'wpsso' ), $count );
							}

							$features[ $label ][ 'label_transl' ] = $label_transl;
							$features[ $label ][ 'status' ]       = class_exists( $classname ) ? 'on' : 'disabled';
						}
					}
				}
			}

			if ( $this->p->avail[ 'p' ][ 'schema' ] ) {

				$org_status    = 'organization' === $this->p->options[ 'site_pub_schema_type' ] ? 'on' : 'off';
				$person_status = 'person' === $this->p->options[ 'site_pub_schema_type' ] ? 'on' : 'off';
				$knowl_status  = 'on';

			} else {

				$org_status    = 'organization' === $this->p->options[ 'site_pub_schema_type' ] ? 'disabled' : 'off';
				$person_status = 'person' === $this->p->options[ 'site_pub_schema_type' ] ? 'disabled' : 'off';
				$knowl_status  = 'disabled';
			}

			$features[ '(code) Knowledge Graph Organization Markup' ] = array(
				'label_transl' => _x( '(code) Knowledge Graph Organization Markup', 'lib file description', 'wpsso' ),
				'label_url'    => $google_tab_url,
				'status'       => $org_status,
			);

			$features[ '(code) Knowledge Graph Person Markup' ] = array(
				'label_transl' => _x( '(code) Knowledge Graph Person Markup', 'lib file description', 'wpsso' ),
				'label_url'    => $google_tab_url,
				'status'       => $person_status,
			);

			$features[ '(code) Knowledge Graph WebSite Markup' ] = array(
				'label_transl' => _x( '(code) Knowledge Graph WebSite Markup', 'lib file description', 'wpsso' ),
				'status'       => $knowl_status,
			);

			return $features;
		}

		private function filter_status_std_features_seo( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$seo_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other' );

			$features[ '(code) SEO Meta Description Tag' ] = array(
				'label_transl' => _x( '(code) SEO Meta Description Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $seo_tab_url,
				'status'       => $this->p->util->is_seo_desc_disabled() ? 'off' : 'on',
			);

			$features[ '(code) SEO Robots Meta Tag' ] = array(
				'label_transl' => _x( '(code) SEO Robots Meta Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $seo_tab_url,
				'status'       => $this->p->util->robots->is_enabled() ? 'on' : 'off',
			);

			$features[ '(code) SEO Link Relation Canonical Tag' ] = array(
				'label_transl' => _x( '(code) SEO Link Relation Canonical Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $seo_tab_url,
				'status'       => $this->p->util->is_canonical_disabled() ? 'off' : 'on',
			);

			$features[ '(code) SEO Link Relation Shortlink Tag' ] = array(
				'label_transl' => _x( '(code) SEO Link Relation Shortlink Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $seo_tab_url,
				'status'       => empty( $this->p->options[ 'add_link_rel_shortlink' ] ) ? 'off' : 'on',
			);

			return $features;
		}

		private function filter_status_std_features_shorten( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$shorten_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_services-tab_shortening' );

			/**
			 * SSO > Advanced Settings > Service APIs > Shortening Services > URL Shortening Service.
			 */
			foreach ( $this->p->cf[ 'form' ][ 'shorteners' ] as $svc_id => $name ) {

				if ( 'none' === $svc_id ) {

					continue;
				}

				$name_transl  = _x( $name, 'option value', 'wpsso' );
				$label_transl = sprintf( _x( '(api) %s Shortener API', 'lib file description', 'wpsso' ), $name_transl );
				$svc_status   = 'off';	// Off unless selected or configured.

				if ( isset( $this->p->m[ 'util' ][ 'shorten' ] ) ) {	// URL shortening service is enabled.

					if ( $svc_id === $this->p->options[ 'plugin_shortener' ] ) {	// Shortener API service ID is selected.

						$svc_status = 'recommended';	// Recommended if selected.

						if ( $this->p->m[ 'util' ][ 'shorten' ]->get_svc_instance( $svc_id ) ) {	// False or object.

							$svc_status = 'on';	// On if configured.
						}
					}
				}

				$features[ '(api) ' . $name . ' Shortener API' ][ 'label_transl' ] = $label_transl;
				$features[ '(api) ' . $name . ' Shortener API' ][ 'label_url' ]    = $shorten_tab_url;
				$features[ '(api) ' . $name . ' Shortener API' ][ 'status' ]       = $svc_status;
			}

			$features[ '(feature) URL Shortening Service' ][ 'label_url' ] = $shorten_tab_url;

			return $features;
		}
	}
}
