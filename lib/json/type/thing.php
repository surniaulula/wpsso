<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeThing' ) ) {

	class WpssoJsonTypeThing {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_thing' => 5,
			) );
		}

		/*
		 * Common filter for all Schema types.
		 *
		 * Adds the url, name, description, and if true, the main entity property.
		 *
		 * Does not add images, videos, author or organization markup since this will depend on the Schema type (Article,
		 * Product, Place, etc.).
		 */
		public function filter_json_data_https_schema_org_thing( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$page_type_url = $this->p->schema->get_schema_type_url( $page_type_id );
			$json_ret      = WpssoSchema::get_schema_type_context( $page_type_url );

			/*
			 * See https://schema.org/additionalType.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting additional types' );
			}

			$json_ret[ 'additionalType' ] = array();

			if ( $mod[ 'obj' ] && $mod[ 'id' ] ) {

				$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( is_array( $md_opts ) ) {	// Just in case.

					foreach ( SucomUtil::preg_grep_keys( '/^schema_addl_type_url_[0-9]+$/', $md_opts ) as $url ) {

						if ( false !== filter_var( $url, FILTER_VALIDATE_URL ) ) {	// Just in case.

							$json_ret[ 'additionalType' ][] = $url;
						}
					}
				}
			}

			$filter_name = 'wpsso_json_prop_https_schema_org_additionaltype';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			$json_ret[ 'additionalType' ] = apply_filters( $filter_name, $json_ret[ 'additionalType' ], $mod, $mt_og, $page_type_id, $is_main );

			/*
			 * See https://schema.org/url.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting url (fragment anchor or canonical url)' );
			}

			if ( empty( $mod[ 'is_public' ] ) ) {	// Since WPSSO Core v7.0.0.

				$json_ret[ 'url' ] = WpssoUtil::get_fragment_anchor( $mod );	// Since WPSSO Core v7.0.0.

			} else {

				$json_ret[ 'url' ] = $this->p->util->get_canonical_url( $mod );
			}

			/*
			 * See https://schema.org/sameAs.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting same as' );
			}

			$json_ret[ 'sameAs' ] = array();

			if ( ! empty( $mod[ 'is_public' ] ) ) {	// Since WPSSO Core v7.0.0.

				if ( ! empty( $mt_og[ 'og:url' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'sameAs og URL = ' . $mt_og[ 'og:url' ] );
					}

					$json_ret[ 'sameAs' ][] = $mt_og[ 'og:url' ];
				}

				/*
				 * Add the post shortlink, but only if the link rel shortlink tag is enabled.
				 */
				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

					/*
					 * WpssoUtil->is_shortlink_disabled() returns true if:
					 *
					 *	- The 'add_link_rel_shortlink' option is unchecked.
					 *	- The 'wpsso_add_link_rel_shortlink' filter returns false.
					 *	- The 'wpsso_shortlink_disabled' filter returns true.
					 */
					if ( ! $this->p->util->is_shortlink_disabled() ) {

						if ( $shortlink = $this->p->util->get_shortlink( $mod, $context = 'post' ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'sameAs shortlink URL = ' . $shortlink );
							}

							$json_ret[ 'sameAs' ][] = $shortlink;
						}
					}
				}

				/*
				 * Add the shortened URL for posts (which may be different to the shortlink), terms, and users.
				 */
				if ( ! empty( $mt_og[ 'og:url' ] ) ) {	// Just in case.

					/*
					 * Shorten URL using the selected shortening service.
					 */
					$short_url = $this->p->util->shorten_url( $mt_og[ 'og:url' ], $mod );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'sameAs short URL = ' . $short_url );
					}

					if ( $short_url !== $mt_og[ 'og:url' ] ) {	// Just in case.

						$json_ret[ 'sameAs' ][] = $short_url;
					}
				}
			}

			/*
			 * Get additional sameAs URLs from the post/term/user custom meta.
			 */
			if ( $mod[ 'obj' ] && $mod[ 'id' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting options for sameAs custom URLs' );
				}

				$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( is_array( $md_opts ) ) {	// Just in case

					foreach ( SucomUtil::preg_grep_keys( '/^schema_sameas_url_[0-9]+$/', $md_opts ) as $url ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'sameAs custom URL = ' . $url );
						}

						$json_ret[ 'sameAs' ][] = SucomUtil::esc_url_encode( $url );
					}
				}
			}

			$filter_name = 'wpsso_json_prop_https_schema_org_sameas';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			$json_ret[ 'sameAs' ] = apply_filters( $filter_name, $json_ret[ 'sameAs' ], $mod, $mt_og, $page_type_id, $is_main );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'calling check_prop_value_sameas()' );
			}

			WpssoSchema::check_prop_value_sameas( $json_ret );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'sameAs', $json_ret[ 'sameAs' ] );
			}

			/*
			 * See https://schema.org/name.
			 * See https://schema.org/alternateName.
			 */
			$json_ret[ 'name' ] = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_title' );

			$json_ret[ 'alternateName' ] = $this->p->page->get_title( $mod, $md_key = 'schema_title_alt', $max_len = 'schema_title_alt' );

			if ( $json_ret[ 'name' ] === $json_ret[ 'alternateName' ] ) {	// Prevent duplicate values.

				unset( $json_ret[ 'alternateName' ] );
			}

			/*
			 * See https://schema.org/description.
			 */
			$json_ret[ 'description' ] = $this->p->page->get_description( $mod, $md_key = 'schema_desc', $max_len = 'schema_desc' );

			/*
			 * See https://schema.org/potentialAction.
			 */
			$json_ret[ 'potentialAction' ] = array();

			$filter_name = 'wpsso_json_prop_https_schema_org_potentialaction';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			$json_ret[ 'potentialAction' ] = apply_filters( $filter_name, $json_ret[ 'potentialAction' ], $mod, $mt_og, $page_type_id, $is_main );

			/*
			 * Get additional Schema properties from the optional post content shortcode.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'checking for schema shortcodes' );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
