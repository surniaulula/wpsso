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

if ( ! class_exists( 'WpssoAdminFilters' ) ) {

	/*
	 * Since WPSSO Core v8.5.1.
	 */
	class WpssoAdminFilters {

		private $p;	// Wpsso class object.

		/*
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
					'features_status' => 3,
				), $prio = -10000 );
			}
		}

		/*
		 * Filter for 'wpsso_features_status'.
		 */
		public function filter_features_status( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$features = $this->filter_features_status_basic( $features, $ext, $info );
			$features = $this->filter_features_status_integ( $features, $ext, $info );
			$features = $this->filter_features_status_schema( $features, $ext, $info );
			$features = $this->filter_features_status_seo( $features, $ext, $info );

			return $features;
		}

		private function filter_features_status_basic( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$features[ '(code) Facebook / Open Graph Meta Tags' ] = array(
				'label_transl' => _x( '(code) Facebook / Open Graph Meta Tags', 'lib file description', 'wpsso' ),
				'status'       => class_exists( 'WpssoOpengraph' ) ? 'on' : 'rec',
			);

			$features[ '(code) Pinterest Hidden Image' ] = array(
				'label_transl' => _x( '(code) Pinterest Hidden Image', 'lib file description', 'wpsso' ),
				'label_url'    => $this->p->util->get_admin_url( 'general#sucom-tabset_social_search-tab_pinterest' ),
				'status'       => $this->p->util->is_pin_img_disabled() ? 'off' : 'on',
			);

			$features[ '(code) Schema JSON-LD Markup' ] = array(
				'label_transl' => _x( '(code) Schema JSON-LD Markup', 'lib file description', 'wpsso' ),
				'status'       => $this->p->util->is_schema_disabled() ? 'rec' : 'on',
			);

			$features[ '(code) Twitter Card Meta Tags' ] = array(
				'label_transl' => _x( '(code) Twitter Card Meta Tags', 'lib file description', 'wpsso' ),
				'status'       => class_exists( 'WpssoTwitterCard' ) ? 'on' : 'rec',
			);

			$features[ '(code) oEmbed Response Enhancements' ] = array(
				'label_transl' => _x( '(code) oEmbed Response Enhancements', 'lib file description', 'wpsso' ),
				'status'       => class_exists( 'WpssoOembed' ) && function_exists( 'get_oembed_response_data' ) ? 'on' : 'rec',
			);

			return $features;
		}

		private function filter_features_status_integ( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$integ_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration' );

			/*
			 * SSO > Advanced Settings > Plugin Settings > Integration > Webpage Title Tag.
			 *
			 * WpssoUtil->is_title_tag_disabled() returns true if:
			 *
			 *	- The theme does not support the 'title-tag' feature.
			 *	- The WPSSO_TITLE_TAG_DISABLE constant is true.
			 */
			$features[ '(code) Webpage Title Tag' ] = array(
				'label_transl' => _x( '(code) Webpage Title Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->util->is_title_tag_disabled() ? 'off' : 'on',
			);

			/*
			 * SSO > Advanced Settings > Plugin Settings > Integration > Image Dimension Checks.
			 */
			$features[ '(feature) Image Dimension Checks' ] = array(
				'label_transl' => _x( '(feature) Image Dimension Checks', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_check_img_dims' ] ? 'on' : 'rec',
			);

			/*
			 * SSO > Advanced Settings > Plugin Settings > Integration > Inherit Custom Images.
			 */
			$features[ '(feature) Inherit Custom Images' ] = array(
				'label_transl' => _x( '(feature) Inherit Custom Images', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_inherit_images' ] ? 'on' : 'rec',
			);

			/*
			 * SSO > Advanced Settings > Plugin Settings > Integration > Inherit Featured Image.
			 */
			$features[ '(feature) Inherit Featured Image' ] = array(
				'label_transl' => _x( '(feature) Inherit Featured Image', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_inherit_featured' ] ? 'on' : 'rec',
			);

			/*
			 * SSO > Advanced Settings > Plugin Settings > Integration > Use Filtered Content.
			 */
			$features[ '(feature) Use Filtered Content' ] = array(
				'label_transl' => _x( '(feature) Use Filtered Content', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_filter_content' ] ? 'on' : 'rec',
			);

			/*
			 * SSO > Advanced Settings > Plugin Settings > Integration > Use Filtered Excerpt.
			 */
			$features[ '(feature) Use Filtered Excerpt' ] = array(
				'label_transl' => _x( '(feature) Use Filtered Excerpt', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_filter_excerpt' ] ? 'on' : 'off',
			);

			/*
			 * SSO > Advanced Settings > Plugin Settings > Integration > Upscale Media Library Images.
			 */
			$features[ '(feature) Upscale Media Library Images' ] = array(
				'label_transl' => _x( '(feature) Upscale Media Library Images', 'lib file description', 'wpsso' ),
				'label_url'    => $integ_tab_url,
				'status'       => $this->p->options[ 'plugin_upscale_images' ] ? 'on' : 'off',
			);

			return $features;
		}

		private function filter_features_status_schema( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$google_tab_url = $this->p->util->get_admin_url( 'general#sucom-tabset_social_search-tab_google' );

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
				'label_url'    => $google_tab_url,
				'status'       => $knowl_status,
			);

			return $features;
		}

		private function filter_features_status_seo( $features, $ext, $info ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$seo_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other' );
			$status_off  = 'off';
			$status_on   = 'on';

			if ( empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {	// No active SEO plugin.

				$status_off = 'disabled';
			}

			/*
			 * WpssoUtil->is_seo_desc_disabled() returns true if:
			 *
			 *	- An SEO plugin is active.
			 *	- The 'add_meta_name_description' option is unchecked.
			 */
			$features[ '(code) SEO Meta Description Tag' ] = array(
				'label_transl' => _x( '(code) SEO Meta Description Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $seo_tab_url,
				'status'       => $this->p->util->is_seo_desc_disabled() ? $status_off : $status_on,
			);

			/*
			 * WpssoUtilRobots->is_disabled() returns true if:
			 *
			 *	- An SEO plugin is active.
			 *	- The 'add_meta_name_robots' option is unchecked.
			 *	- The 'wpsso_robots_disabled' filter returns true.
			 */
			$features[ '(code) SEO Robots Meta Tag' ] = array(
				'label_transl' => _x( '(code) SEO Robots Meta Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $seo_tab_url,
				'status'       => $this->p->util->robots->is_disabled() ? $status_off : $status_on,
			);

			/*
			 * WpssoUtil->is_canonical_disabled() returns true if:
			 *
			 *	- An SEO plugin is active.
			 *	- The 'add_link_rel_canonical' option is unchecked.
			 *	- The 'wpsso_add_link_rel_canonical' filter returns false.
			 *	- The 'wpsso_canonical_disabled' filter returns true.
			 */
			$features[ '(code) SEO Link Relation Canonical Tag' ] = array(
				'label_transl' => _x( '(code) SEO Link Relation Canonical Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $seo_tab_url,
				'status'       => $this->p->util->is_canonical_disabled() ? $status_off : $status_on,
			);

			/*
			 * WpssoUtil->is_shortlink_disabled() returns true if:
			 *
			 *	- The 'add_link_rel_shortlink' option is unchecked.
			 *	- The 'wpsso_add_link_rel_shortlink' filter returns false.
			 *	- The 'wpsso_shortlink_disabled' filter returns true.
			 */
			$features[ '(code) SEO Link Relation Shortlink Tag' ] = array(
				'label_transl' => _x( '(code) SEO Link Relation Shortlink Tag', 'lib file description', 'wpsso' ),
				'label_url'    => $seo_tab_url,
				'status'       => $this->p->util->is_shortlink_disabled() ? $status_off : $status_on,
			);

			return $features;
		}
	}
}
