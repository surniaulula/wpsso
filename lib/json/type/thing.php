<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeThing' ) ) {

	class WpssoJsonTypeThing {

		private $p;	// Wpsso class object.

		/**
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

		/**
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

			/**
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

			$json_ret[ 'additionalType' ] = (array) apply_filters( 'wpsso_json_prop_https_schema_org_additionaltype',
				$json_ret[ 'additionalType' ], $mod, $mt_og, $page_type_id, $is_main );

			/**
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

			/**
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

				if ( $mod[ 'is_post' ] && $mod[ 'id' ] ) {

					/**
					 * Add the permalink, which may be different than the shared URL and the canonical URL.
					 */
					$permalink = get_permalink( $mod[ 'id' ] );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'sameAs permalink URL = ' . $permalink );
					}

					$json_ret[ 'sameAs' ][] = $permalink;

					/**
					 * Add the shortlink / short URL, but only if the link rel shortlink tag is enabled.
					 */
					$add_link_rel_shortlink = empty( $this->p->options[ 'add_link_rel_shortlink' ] ) ? false : true;

					if ( apply_filters( 'wpsso_add_link_rel_shortlink', $add_link_rel_shortlink, $mod ) ) {

						$shortlink = wp_get_shortlink( $mod[ 'id' ], 'post' );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'sameAs shortlink URL = ' . $shortlink );
						}

						$json_ret[ 'sameAs' ][] = $shortlink;

						/**
						 * Some themes and plugins have been known to hook the WordPress 'get_shortlink' filter
						 * and return an empty URL to disable the WordPress shortlink meta tag. This breaks the
						 * WordPress wp_get_shortlink() function and is a violation of the WordPress theme
						 * guidelines.
						 *
						 * This method calls the WordPress wp_get_shortlink() function, and if an empty string
						 * is returned, calls an unfiltered version of the same function.
						 *
						 * $context = 'blog', 'post' (default), 'media', or 'query'
						 */
						$raw_shortlink = SucomUtilWP::wp_get_shortlink( $mod[ 'id' ], $context = 'post' );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'sameAs (maybe raw) shortlink URL = ' . $raw_shortlink );
						}

						if ( $shortlink !== $raw_shortlink ) {

							$json_ret[ 'sameAs' ][] = $raw_shortlink;
						}
					}
				}

				/**
				 * Add the shortened URL for posts (which may be different to the shortlink), terms, and users.
				 */
				if ( ! empty( $mt_og[ 'og:url' ] ) ) {	// Just in case.

					/**
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

			/**
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

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying sameAs property filter' );
			}

			$json_ret[ 'sameAs' ] = (array) apply_filters( 'wpsso_json_prop_https_schema_org_sameas',
				$json_ret[ 'sameAs' ], $mod, $mt_og, $page_type_id, $is_main );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'calling check_prop_value_sameas()' );
			}

			WpssoSchema::check_prop_value_sameas( $json_ret );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'sameAs', $json_ret[ 'sameAs' ] );
			}

			/**
			 * See https://schema.org/name.
			 * See https://schema.org/alternateName.
			 */
			$json_ret[ 'name' ] = $this->p->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_title' );

			$json_ret[ 'alternateName' ] = $this->p->page->get_title( $mod, $md_key = 'schema_title_alt', $max_len = 'schema_title_alt' );

			if ( $json_ret[ 'name' ] === $json_ret[ 'alternateName' ] ) {	// Prevent duplicate values.

				unset( $json_ret[ 'alternateName' ] );
			}

			/**
			 * See https://schema.org/description.
			 */
			$json_ret[ 'description' ] = $this->p->page->get_description( $mod, $md_key = 'schema_desc', $max_len = 'schema_desc' );

			/**
			 * See https://schema.org/potentialAction.
			 */
			$json_ret[ 'potentialAction' ] = array();

			$json_ret[ 'potentialAction' ] = (array) apply_filters( 'wpsso_json_prop_https_schema_org_potentialaction',
				$json_ret[ 'potentialAction' ], $mod, $mt_og, $page_type_id, $is_main );

			/**
			 * Get additional Schema properties from the optional post content shortcode.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'checking for schema shortcodes' );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}
