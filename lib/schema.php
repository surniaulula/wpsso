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

if ( ! class_exists( 'WpssoSchemaGraph' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/schema-graph.php';
}

if ( ! class_exists( 'WpssoSchemaSingle' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/schema-single.php';
}

if ( ! class_exists( 'WpssoSchema' ) ) {

	class WpssoSchema {

		private $p;				// Wpsso class object.
		private $noscript;			// WpssoSchemaNoScript class object.

		private $types_cache = array();		// Schema types array cache.

		private static $units_cache = null;	// Schema unicodes array cache.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Instantiate the WpssoSchemaNoScript class object.
			 */
			if ( ! class_exists( 'WpssoSchemaNoScript' ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/schema-noscript.php';
			}

			$this->noscript = new WpssoSchemaNoScript( $plugin );

			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes'   => 1,
				'sanitize_md_defaults' => 2,
				'sanitize_md_options'  => 2,
			), $prio = 5 );

			add_action( 'wp_ajax_' . $this->p->lca . '_schema_type_og_type', array( $this, 'ajax_schema_type_og_type' ) );
		}

		public function filter_plugin_image_sizes( $sizes ) {

			$sizes[ 'schema_1_1' ] = array(		// Option prefix.
				'name'         => 'schema-1-1',
				'label_transl' => _x( 'Schema 1:1 (Google)', 'option label', 'wpsso' ),
			);

			$sizes[ 'schema_4_3' ] = array(		// Option prefix.
				'name'         => 'schema-4-3',
				'label_transl' => _x( 'Schema 4:3 (Google)', 'option label', 'wpsso' ),
			);

			$sizes[ 'schema_16_9' ] = array(	// Option prefix.
				'name'         => 'schema-16-9',
				'label_transl' => _x( 'Schema 16:9 (Google)', 'option label', 'wpsso' ),
			);

			$sizes[ 'thumb' ] = array(		// Option prefix.
				'name'         => 'thumbnail',
				'label_transl' => _x( 'Schema Thumbnail', 'option label', 'wpsso' ),
			);

			return $sizes;
		}

		public function filter_sanitize_md_defaults( $md_defs, $mod ) {

			return $this->filter_sanitize_md_options( $md_defs, $mod );
		}

		public function filter_sanitize_md_options( $md_opts, $mod ) {

			if ( ! empty( $mod[ 'is_post' ] ) ) {

			 	self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_condition', $enum_key = 'item_condition', $val_suffix = 'Condition' );

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_avail', $enum_key = 'item_availability' );
			}

			return $md_opts;
		}

		/**
		 * https://schema.org/WebSite for Google
		 */
		public function filter_json_data_https_schema_org_website( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = self::get_schema_type_context( 'https://schema.org/WebSite', array(
				'url' => SucomUtil::get_site_url( $this->p->options, $mod ),
			) );

			foreach ( array(
				'name'          => SucomUtil::get_site_name( $this->p->options, $mod ),
				'alternateName' => SucomUtil::get_site_name_alt( $this->p->options, $mod ),
				'description'   => SucomUtil::get_site_description( $this->p->options, $mod ),
			) as $key => $value ) {

				if ( ! empty( $value ) ) {

					$json_ret[ $key ] = $value;
				}
			}

			/**
			 * Potential Action (SearchAction, OrderAction, etc.)
			 *
			 * The 'wpsso_json_prop_https_schema_org_potentialaction' filter may already be applied by the WPSSO JSON
			 * add-on, so do not re-apply it here.
			 *
			 * Hook the 'wpsso_json_ld_search_url' filter and return false if you wish to disable / skip the Potential
			 * Action property.
			 */
			$search_url = SucomUtil::esc_url_encode( get_bloginfo( 'url' ) ) . '?s={search_term_string}';

			$search_url = apply_filters( $this->p->lca . '_json_ld_search_url', $search_url );

			if ( ! empty( $search_url ) ) {

				$json_ret[ 'potentialAction' ][] = self::get_schema_type_context( 'https://schema.org/SearchAction', array(
					'target'      => $search_url,
					'query-input' => 'required name=search_term_string',
				) );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'skipping search action: search url is empty' );
			}

			
			return self::return_data_from_filter( $json_data, $json_ret, $is_main );
		}

		/**
		 * https://schema.org/Organization social markup for Google
		 */
		public function filter_json_data_https_schema_org_organization( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$org_id = 'none';

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$org_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_organization_org_id', $filter_opts = true, $pad_opts = true );
			}

			if ( null === $org_id || 'none' === $org_id ) {	// Allow for $org_id = 0.

				if ( $mod[ 'is_home' ] ) {	// Static or index page.

					$org_id = 'site';

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: organization id is null or "none"' );
					}

					return $json_data;
				}
			}

			/**
			 * Possibly inherit the schema type.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'possibly inherit the schema type' );

				$this->p->debug->log_arr( '$json_data', $json_data );
			}

			$json_ret = self::get_data_context( $json_data );	// Returns array() if no schema type found.

		 	/**
			 * $org_id can be 'none', 'site', or a number (including 0).
			 *
		 	 * $org_logo_key can be 'org_logo_url' or 'org_banner_url' (600x60px image) for Articles.
			 *
			 * Do not provide localized option names - the method will fetch the localized values.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding data for organization id = ' . $org_id );
			}

			WpssoSchemaSingle::add_organization_data( $json_ret, $mod, $org_id, $org_logo_key = 'org_logo_url', $list_element = false );

			return self::return_data_from_filter( $json_data, $json_ret, $is_main );
		}

		/**
		 * https://schema.org/Person social markup for Google
		 */
		public function filter_json_data_https_schema_org_person( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$user_id = 'none';

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$user_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_person_id', $filter_opts = true, $pad_opts = true );
			}

			if ( empty( $user_id ) || 'none' === $user_id ) {

				if ( $mod[ 'is_home' ] ) {	// Static or index page.

					$user_id = $this->p->options[ 'site_pub_person_id' ];	// 'none' by default.

				} elseif ( $mod[ 'is_user' ] ) {

					$user_id = $mod[ 'id' ];	// Could be false.

				} else {

					$user_id = 'none';
				}

				if ( empty( $user_id ) || 'none' === $user_id ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: user id is empty or "none"' );
					}

					return $json_data;
				}
			}

			/**
			 * Possibly inherit the schema type.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'possibly inherit the schema type' );

				$this->p->debug->log_arr( '$json_data', $json_data );
			}

			$json_ret = self::get_data_context( $json_data );	// Returns array() if no schema type found.

		 	/**
			 * $user_id can be 'none' or a number (including 0).
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding data for person id = ' . $user_id );
			}

			WpssoSchemaSingle::add_person_data( $json_ret, $mod, $user_id, $list_element = false );

			/**
			 * Override author's website url and use the og url instead.
			 */
			if ( $mod[ 'is_home' ] ) {

				$json_ret[ 'url' ] = $mt_og[ 'og:url' ];
			}

			return self::return_data_from_filter( $json_data, $json_ret, $is_main );
		}

		public function has_json_data_filter( array $mod, $type_url = '' ) {

			$filter_name = $this->get_json_data_filter( $mod, $type_url );

			return empty( $filter_name ) ? false : has_filter( $filter_name );
		}

		public function get_json_data_filter( array $mod, $type_url = '' ) {

			if ( empty( $type_url ) ) {

				$type_url = $this->get_mod_schema_type( $mod );
			}

			return $this->p->lca . '_json_data_' . SucomUtil::sanitize_hookname( $type_url );
		}

		/**
		 * Called by WpssoHead::get_head_array().
		 *
		 * Pass $mt_og by reference to assign values to the schema:type internal meta tags.
		 */
		public function get_array( array $mod, array &$mt_og = array() ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'build schema array' );	// Begin timer.
			}

			$page_type_id  = $mt_og[ 'schema:type:id' ]  = $this->get_mod_schema_type( $mod, $get_id = true );	// Example: article.tech.
			$page_type_url = $mt_og[ 'schema:type:url' ] = $this->get_schema_type_url( $page_type_id );	// Example: https://schema.org/TechArticle.

			list(
				$mt_og[ 'schema:type:context' ],
				$mt_og[ 'schema:type:name' ],
			) = self::get_schema_type_url_parts( $page_type_url );		// Example: https://schema.org, TechArticle.

			$page_type_ids   = array();
			$page_type_added = array();	// Prevent duplicate schema types.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'head schema type id is ' . $page_type_id . ' (' . $page_type_url . ')' );
			}

			/**
			 * Include Schema Organization or Person, and WebSite markup on the home page.
			 */
			if ( $mod[ 'is_home' ] ) {	// Static or index home page.

				switch ( $this->p->options[ 'site_pub_schema_type' ] ) {

					case 'organization':

						$site_org_type_id = $this->p->options[ 'site_org_schema_type' ];	// Organization or a sub-type of organization.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'organization schema type id is ' . $site_org_type_id );
						}

						$page_type_ids[ $site_org_type_id ] = true;

						break;

					case 'person':

						$page_type_ids[ 'person' ] = true;

						break;
				}

				$page_type_ids[ 'website' ] = true;
			}

			/**
			 * Could be an organization, website, or person, so include last to re-enable (if disabled by default).
			 */
			if ( ! empty( $page_type_url ) ) {

				$page_type_ids[ $page_type_id ] = true;
			}

			/**
			 * Array (
			 *	[product]      => true
			 *	[website]      => true
			 *	[organization] => true
			 *	[person]       => false
			 * )
			 *
			 * Hooked by the WpssoBcFilters->filter_json_array_schema_page_type_ids() filter at add its
			 * 'breadcrumb.list' type ID.
			 */
			$page_type_ids = apply_filters( $this->p->lca . '_json_array_schema_page_type_ids', $page_type_ids, $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$page_type_ids', $page_type_ids );
			}

			/**
			 * Start a new @graph array.
			 */
			WpssoSchemaGraph::reset_data();

			foreach ( $page_type_ids as $type_id => $is_enabled ) {

				if ( ! $is_enabled ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping schema type id "' . $type_id . '" (disabled)' );
					}

					continue;

				} elseif ( ! empty( $page_type_added[ $type_id ] ) ) {	// Prevent duplicate schema types.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping schema type id "' . $type_id . '" (previously added)' );
					}

					continue;

				} else {
					$page_type_added[ $type_id ] = true;	// Prevent adding duplicate schema types.
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'schema type id ' . $type_id );	// Begin timer.
				}

				if ( $type_id === $page_type_id ) {	// This is the main entity.

					$is_main = true;

				} else {

					$is_main = false;	// Default for all other types.
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema main entity is ' . ( $is_main ? 'true' : 'false' ) . ' for ' . $type_id );
				}

				$json_data = $this->get_json_data( $mod, $mt_og, $type_id, $is_main );

				/**
				 * The $json_data array will almost always be a single associative array, but the breadcrumblist
				 * filter may return an array of associative arrays.
				 */
				if ( isset( $json_data[ 0 ] ) && ! SucomUtil::is_assoc( $json_data ) ) {	// Multiple json arrays returned.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'multiple json data arrays returned' );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'single json data array returned' );
					}

					$json_data = array( $json_data );	// Single json script returned.
				}

				/**
				 * Add the json data to the @graph array.
				 */
				foreach ( $json_data as $single_graph ) {

					if ( empty( $single_graph ) || ! is_array( $single_graph ) ) {	// Just in case.

						continue;
					}

					if ( empty( $single_graph[ '@type' ] ) ) {

						$type_url = $this->get_schema_type_url( $type_id );

						$single_graph = self::get_schema_type_context( $type_url, $single_graph );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'added @type property is ' . $single_graph[ '@type' ] );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'existing @type property is ' . print_r( $single_graph[ '@type' ], true ) );
					}

					$single_graph = apply_filters( $this->p->lca . '_json_data_graph_element',
						$single_graph, $mod, $mt_og, $page_type_id, $is_main );

					WpssoSchemaGraph::add_data( $single_graph );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'schema type id ' . $type_id );	// End timer.
				}
			}

			/**
			 * Get the @graph json array and start a new @graph array.
			 */
			$graph_type_url = WpssoSchemaGraph::get_type_url();
			$graph_json     = WpssoSchemaGraph::get_json_reset_data();
			$filter_name    = $this->p->lca . '_json_prop_' . SucomUtil::sanitize_hookname( $graph_type_url );
			$graph_json     = apply_filters( $filter_name, $graph_json, $mod, $mt_og );

			$schema_scripts  = array();

			if ( ! empty( $graph_json[ '@graph' ] ) ) {	// Just in case.

				$graph_json = WpssoSchemaGraph::optimize_json( $graph_json );

				$schema_scripts[][] = '<script type="application/ld+json">' .
					$this->p->util->json_format( $graph_json ) . '</script>' . "\n";
			}

			unset( $graph_json );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'build schema array' );	// End timer.
			}

			$schema_scripts = apply_filters( $this->p->lca . '_schema_scripts', $schema_scripts, $mod, $mt_og );

			return $schema_scripts;
		}

		/**
		 * Get the JSON-LD data array.
		 */
		public function get_json_data( array $mod, array &$mt_og, $page_type_id = false, $is_main = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $page_type_id ) ) {

				$page_type_id = $this->get_mod_schema_type( $mod, $get_id = true );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'page type ID is ' . $page_type_id );
				}
			}

			/**
			 * Returns an array of type ids with gparents, parents, child (in that order).
			 */
			$child_family_urls = array();

			foreach ( $this->get_schema_type_child_family( $page_type_id ) as $type_id ) {
				$child_family_urls[] = $this->get_schema_type_url( $type_id );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$child_family_urls', $child_family_urls );
			}

			$json_data = null;

			foreach ( $child_family_urls as $num => $type_url ) {

				$type_hookname      = SucomUtil::sanitize_hookname( $type_url );
				$data_filter_name   = $this->p->lca . '_json_data_' . $type_hookname;
				$valid_filter_name  = $this->p->lca . '_json_data_validate_' . $type_hookname;
				$method_filter_name = 'filter_json_data_' . $type_hookname;

				/**
				 * Add website, organization, and person markup to home page.
				 */
				if ( false !== has_filter( $data_filter_name ) ) {
				
					$json_data = apply_filters( $data_filter_name, $json_data, $mod, $mt_og, $page_type_id, $is_main );

					if ( false !== has_filter( $valid_filter_name ) ) {

						$json_data = apply_filters( $valid_filter_name, $json_data, $mod, $mt_og, $page_type_id, $is_main );
					}

				} elseif ( $mod[ 'is_home' ] && method_exists( $this, $method_filter_name ) ) {

					/**
					 * $is_main is always false for method.
					 */
					$json_data = call_user_func( array( $this, $method_filter_name ), $json_data, $mod, $mt_og, $page_type_id, false );
				}
			}

			if ( isset( $json_data[ 0 ] ) && SucomUtil::is_non_assoc( $json_data ) ) {	// Multiple json arrays returned.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'multiple json data arrays returned' );
				}

			} else {
				self::update_data_id( $json_data, $page_type_id );
			}

			return $json_data;
		}

		public function get_json_data_home_website() {

			$mod = WpssoWpMeta::get_mod_home();

			$mt_og = array();

			$json_data = $this->get_json_data( $mod, $mt_og, $type_id = 'website', $is_main = false );

			return $json_data;
		}

		public function get_mod_json_data( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $mod[ 'name' ] ) || empty( $mod[ 'id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no module object name or object id' );
				}

				return false;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting schema type for ' . $mod[ 'name' ] . ' ID ' . $mod[ 'id' ] );
			}

			$page_type_id = $this->get_mod_schema_type( $mod, $get_id = true );

			if ( empty( $page_type_id ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: page type id is empty' );
				}

				return false;

			} elseif ( 'none' === $page_type_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: page type id is "none"' );
				}

				return false;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'page type ID is ' . $page_type_id );
			}

			$ref_url = $this->p->util->maybe_set_ref( null, $mod, __( 'adding schema', 'wpsso' ) );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting open graph meta tag array' );
			}

			$mt_og = $this->p->og->get_array( $mod, $size_names = 'schema' );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting schema json-ld markup array' );
			}

			$json_data = $this->get_json_data( $mod, $mt_og, $page_type_id, $is_main = true );

			$this->p->util->maybe_unset_ref( $ref_url );

			return $json_data;
		}

		/**
		 * Return the schema type URL by default.
		 * 
		 * Use $get_id = true to return the schema type ID instead of the URL.
		 */
		public function get_mod_schema_type( array $mod, $get_id = false, $use_mod_opts = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();	// Cache for single page load.

			/**
			 * Optimize and cache post/term/user schema type values.
			 */
			if ( ! empty( $mod[ 'name' ] ) && ! empty( $mod[ 'id' ] ) ) {

				if ( isset( $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ][ $get_id ][ $use_mod_opts ] ) ) {

					$value =& $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ][ $get_id ][ $use_mod_opts ];

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'returning local cache value "' . $value . '"' );
					}

					return $value;

				} elseif ( is_object( $mod[ 'obj' ] ) && $use_mod_opts ) {	// Check for a column schema_type value in wp_cache.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'checking for value from column wp_cache' );
					}

					$value = $mod[ 'obj' ]->get_column_wp_cache( $mod, $this->p->lca . '_schema_type' );	// Returns empty string if no value found.

					if ( ! empty( $value ) ) {

						if ( ! $get_id && $value !== 'none' ) {	// Return the schema type url instead.

							$schema_types = $this->get_schema_types_array( $flatten = true );

							if ( ! empty( $schema_types[ $value ] ) ) {

								$value = $schema_types[ $value ];

							} else {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'columns wp_cache value "' . $value . '" not in schema types' );
								}

								$value = '';
							}
						}

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'returning column wp_cache value "' . $value . '"' );
						}

						return $local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ][ $get_id ][ $use_mod_opts ] = $value;
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no value found in local cache or column wp_cache' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'skipping cache check: mod name and/or id value is empty' );
			}

			$default_key  = apply_filters( $this->p->lca . '_schema_type_for_default', 'webpage', $mod );
			$schema_types = $this->get_schema_types_array( $flatten = true );
			$type_id      = null;

			/**
			 * Get custom schema type from post, term, or user meta.
			 */
			if ( $use_mod_opts ) {

				if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

					$type_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_type' );	// Returns null if an index key is not found.

					if ( empty( $type_id ) ) {	// Must be a non-empty string.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom type id from meta is empty' );
						}

					} elseif ( $type_id === 'none' ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom type id is disabled with value "none"' );
						}

					} elseif ( empty( $schema_types[ $type_id ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'custom type id "' . $type_id . '" not in schema types' );
						}

						$type_id = null;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'custom type id "' . $type_id . '" from ' . $mod[ 'name' ] . ' meta' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping custom type id - mod object is empty' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'skipping custom type id - use_mod_opts is false' );
			}

			if ( empty( $type_id ) ) {

				$is_custom = false;

			} else {

				$is_custom = true;
			}

			if ( empty( $type_id ) ) {	// If no custom schema type, then use the default settings.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'using plugin settings to determine schema type' );
				}

				if ( $mod[ 'is_home' ] ) {	// Static or index page.

					if ( $mod[ 'is_home_page' ] ) {

						$type_id = $this->get_schema_type_id_for_name( 'home_page' );

						$type_id = apply_filters( $this->p->lca . '_schema_type_for_home_page', $type_id, $mod );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'using schema type id "' . $type_id . '" for home page' );
						}

					} else {

						$type_id = $this->get_schema_type_id_for_name( 'home_posts' );

						$type_id = apply_filters( $this->p->lca . '_schema_type_for_home_posts', $type_id, $mod );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'using schema type id "' . $type_id . '" for home posts' );
						}
					}

				} elseif ( $mod[ 'is_post' ] ) {

					if ( ! empty( $mod[ 'post_type' ] ) ) {

						if ( $mod[ 'is_post_type_archive' ] ) {

							$$type_id = $this->get_schema_type_id_for_name( 'post_archive' );

							$type_id = apply_filters( $this->p->lca . '_schema_type_for_post_type_archive_page', $type_id, $mod );

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'using schema type id "' . $type_id . '" for post_type_archive page' );
							}

						} elseif ( isset( $this->p->options[ 'schema_type_for_' . $mod[ 'post_type' ] ] ) ) {

							$type_id = $this->get_schema_type_id_for_name( $mod[ 'post_type' ] );

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'using schema type id "' . $type_id . '" from post type option value' );
							}

						} elseif ( ! empty( $schema_types[ $mod[ 'post_type' ] ] ) ) {

							$type_id = $mod[ 'post_type' ];

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'using schema type id "' . $type_id . '" from post type name' );
							}

						} else {	// Unknown post type.

							$type_id = $this->get_schema_type_id_for_name( 'page' );

							$type_id = apply_filters( $this->p->lca . '_schema_type_for_post_type_unknown_type', $type_id, $mod );

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'using "page" schema type for unknown post type ' . $mod[ 'post_type' ] );
							}
						}

					} else {	// Post objects without a post_type property.

						$type_id = $this->get_schema_type_id_for_name( 'page' );

						$type_id = apply_filters( $this->p->lca . '_schema_type_for_post_type_empty_type', $type_id, $mod );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'using "page" schema type for empty post type' );
						}
					}

				} elseif ( $mod[ 'is_term' ] ) {

					if ( ! empty( $mod[ 'tax_slug' ] ) ) {

						$type_id = $this->get_schema_type_id_for_name( 'tax_' . $mod[ 'tax_slug' ] );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'using schema type id "' . $type_id . '" from term option value' );
						}
					}

					if ( empty( $type_id ) ) {	// Just in case.

						$type_id = $this->get_schema_type_id_for_name( 'archive_page' );
					}

				} elseif ( $mod[ 'is_user' ] ) {

					$type_id = $this->get_schema_type_id_for_name( 'user_page' );

				} elseif ( is_search() ) {

					$type_id = $this->get_schema_type_id_for_name( 'search_page' );

				} elseif ( SucomUtil::is_archive_page() ) {	// Just in case.

					$type_id = $this->get_schema_type_id_for_name( 'archive_page' );

				} else {	// Everything else.

					$type_id = $default_key;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'using default schema type id "' . $default_key . '"' );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'schema type id before filter is "' . $type_id . '"' );
			}

			$type_id = apply_filters( $this->p->lca . '_schema_type_id', $type_id, $mod, $is_custom );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'schema type id after filter is "' . $type_id . '"' );
			}

			$get_value = false;

			if ( empty( $type_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: schema type id is empty' );
				}

			} elseif ( $type_id === 'none' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: schema type id is disabled' );
				}

			} elseif ( ! isset( $schema_types[ $type_id ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: schema type id "' . $type_id . '" is unknown' );
				}

			} elseif ( ! $get_id ) {	// False by default.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning schema type url "' . $schema_types[ $type_id ] . '"' );
				}

				$get_value = $schema_types[ $type_id ];

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning schema type id "' . $type_id . '"' );
				}

				$get_value = $type_id;
			}

			/**
			 * Optimize and cache post/term/user schema type values.
			 */
			if ( ! empty( $mod[ 'name' ] ) && ! empty( $mod[ 'id' ] ) ) {

				$local_cache[ $mod[ 'name' ] ][ $mod[ 'id' ] ][ $get_id ][ $use_mod_opts ] = $get_value;
			}

			return $get_value;
		}

		/**
		 * $context is 'settings', 'business', 'organization', 'place', or 'meta'.
		 *
		 * $mod is provided when get_schema_types_select() is called from a post, term, or user metabox.
		 */
		public function get_schema_types_select( $context = null, $schema_types = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( is_array( $context ) ) {	// Backwards compatibility.

				$schema_types = $context;

				$context = null;
			}

			if ( ! is_array( $schema_types ) ) {

				$schema_types = $this->get_schema_types_array( $flatten = false );
			}

			$schema_types = SucomUtil::array_flatten( $schema_types );

			if ( defined( 'SORT_STRING' ) ) {	// Just in case.

				ksort( $schema_types, SORT_STRING );

			} else {

				ksort( $schema_types );
			}

			/**
			 * $schema_types = Array (
			 *	[accommodation] => https://schema.org/Accommodation
			 *	.
			 *	.
			 *	.
			 *	[zoo] => https://schema.org/Zoo
			 * );
			 */
			$schema_types = (array) apply_filters( $this->p->lca . '_schema_types_select', $schema_types, $context );

			$select = array();

			foreach ( $schema_types as $type_id => $type_url ) {

				$type_url = preg_replace( '/^.*\/\//', '', $type_url );

				$select[ $type_id ] = $type_id . ' | ' . $type_url;
			}

			return $select;
		}

		/**
		 * By default, returns a one-dimensional (flat) array of schema types, otherwise returns a multi-dimensional array
		 * of all schema types, including cross-references for sub-types with multiple parent types.
		 *
		 * $read_cache is false when called from the WpssoOptionsUpgrade::options() method.
		 */
		public function get_schema_types_array( $flatten = true, $read_cache = true ) {

			if ( false === $read_cache ) {

				$this->types_cache[ 'filtered' ]  = null;
				$this->types_cache[ 'flattened' ] = null;
				$this->types_cache[ 'parents' ]   = null;
			}

			if ( ! isset( $this->types_cache[ 'filtered' ] ) ) {

				$cache_md5_pre  = $this->p->lca . '_t_';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre );	// Default is month in seconds.

				if ( $cache_exp_secs > 0 ) {

					$cache_salt = __METHOD__;
					$cache_id   = $cache_md5_pre . md5( $cache_salt );

					if ( false === $read_cache ) {

						$this->types_cache = get_transient( $cache_id );	// Returns false when not found.

						if ( ! empty( $this->types_cache ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'using schema types array from transient ' . $cache_id );
							}
						}
					}
				}

				if ( ! isset( $this->types_cache[ 'filtered' ] ) ) {	// Maybe from transient cache - re-check if filtered.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'create schema types array' );	// Begin timer.
					}

					/**
					 * Filtered array.
					 */
					$this->types_cache[ 'filtered' ] = (array) apply_filters( $this->p->lca . '_schema_types', $this->p->cf[ 'head' ][ 'schema_type' ] );

					/**
					 * Flattened array (before adding cross-references).
					 */
					$this->types_cache[ 'flattened' ] = SucomUtil::array_flatten( $this->types_cache[ 'filtered' ] );

					/**
					 * Adding cross-references to filtered array.
					 */
					$this->add_schema_type_xrefs( $this->types_cache[ 'filtered' ] );

					/**
					 * Parents array.
					 */
					$this->types_cache[ 'parents' ] = SucomUtil::get_array_parents( $this->types_cache[ 'filtered' ] );

					if ( $cache_exp_secs > 0 ) {

						set_transient( $cache_id, $this->types_cache, $cache_exp_secs );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'schema types array saved to transient cache for ' . $cache_exp_secs . ' seconds' );
						}
					}

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'create schema types array' );	// End timer.
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema types array already filtered' );
				}
			}

			if ( $flatten ) {

				return $this->types_cache[ 'flattened' ];
			}

			return $this->types_cache[ 'filtered' ];
		}

		/**
		 * Returns an array of schema type ids with gparent, parent, child (in that order).
		 */
		public function get_schema_type_child_family( $child_id, $use_cache = true, &$child_family = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $use_cache ) {

				$cache_md5_pre  = $this->p->lca . '_t_';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre );	// Default is month in seconds.

				if ( $cache_exp_secs > 0 ) {

					$cache_salt   = __METHOD__ . '(child_id:' . $child_id . ')';
					$cache_id     = $cache_md5_pre . md5( $cache_salt );
					$child_family = get_transient( $cache_id );	// Returns false when not found.

					if ( is_array( $child_family ) ) {
						return $child_family;
					}
				}
			}

			$schema_types = $this->get_schema_types_array( $flatten = true );	// Defines the 'parents' array.

			if ( isset( $this->types_cache[ 'parents' ][ $child_id ] ) ) {

				foreach( $this->types_cache[ 'parents' ][ $child_id ] as $parent_id ) {

					if ( $parent_id !== $child_id )	{		// Prevent infinite loops.

						$this->get_schema_type_child_family( $parent_id, $child_use_cache = false, $child_family );
					}
				}
			}

			$child_family[] = $child_id;	// Add child after parents.

			$child_family = array_unique( $child_family );

			if ( $use_cache ) {

				if ( $cache_exp_secs > 0 ) {

					set_transient( $cache_id, $child_family, $cache_exp_secs );
				}
			}

			return $child_family;
		}

		/**
		 * Returns an array of schema type ids with child, parent, gparent (in that order).
		 */
		public function get_schema_type_children( $type_id, $use_cache = true, &$children = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting children for type id ' . $type_id );
			}

			if ( $use_cache ) {

				$cache_md5_pre  = $this->p->lca . '_t_';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre );	// Default is month in seconds.

				if ( $cache_exp_secs > 0 ) {

					$cache_salt = __METHOD__ . '(type_id:' . $type_id . ')';
					$cache_id   = $cache_md5_pre . md5( $cache_salt );
					$children   = get_transient( $cache_id );	// Returns false when not found.

					if ( is_array( $children ) ) {

						return $children;
					}
				}
			}

			$children[] = $type_id;	// Add children before parents.

			$schema_types = $this->get_schema_types_array( $flatten = true );	// Defines the 'parents' array.

			foreach ( $this->types_cache[ 'parents' ] as $child_id => $parent_ids ) {

				foreach( $parent_ids as $parent_id ) {

					if ( $parent_id === $type_id ) {

						$this->get_schema_type_children( $child_id, $child_use_cache = false, $children );
					}
				}
			}

			$children = array_unique( $children );

			if ( $use_cache ) {

				if ( $cache_exp_secs > 0 ) {

					set_transient( $cache_id, $children, $cache_exp_secs );
				}
			}

			return $children;
		}

		public static function get_schema_type_context( $type_url, array $json_data = array() ) {

			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $type_url, $match ) ) {

				$context_value = $match[1];
				$type_value    = $match[2];

				/**
				 * Check for schema extension (example: https://health-lifesci.schema.org).
				 *
				 * $context_value = array(
				 *	"https://schema.org",
				 *	array(
				 *		"health-lifesci" => "https://health-lifesci.schema.org",
				 *	),
				 * );
				 *
				 */
				if ( preg_match( '/^(.+:\/\/)([^\.]+)\.([^\.]+\.[^\.]+)$/', $context_value, $ext ) ) {

					$context_value = array( 
						$ext[1] . $ext[3],
						array(
							$ext[2] => $ext[0],
						)
					);
				}

				$json_head = array(
					'@id'      => null,
					'@context' => null,
					'@type'    => null,
				);

				$json_values = array(
					'@context' => $context_value,
					'@type'    => $type_value,
				);

				$json_data = array_merge(
					$json_head,	// Keep @id, @context, and @type top-most.
					$json_data,
					$json_values
				);

				if ( empty( $json_data[ '@id' ] ) ) {

					unset( $json_data[ '@id' ] );
				}
			}

			return $json_data;
		}

		public function get_schema_type_id_for_name( $type_name, $default_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'type_name'  => $type_name,
					'default_id' => $default_id,
				) );
			}

			if ( empty( $type_name ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: schema type name is empty' );
				}

				return $default_id;	// Just in case.
			}

			$schema_types = $this->get_schema_types_array( $flatten = true );

			$type_id = isset( $this->p->options[ 'schema_type_for_' . $type_name ] ) ?	// Just in case.
				$this->p->options[ 'schema_type_for_' . $type_name ] : $default_id;

			if ( empty( $type_id ) || $type_id === 'none' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema type id for ' . $type_name . ' is empty or disabled' );
				}

				$type_id = $default_id;

			} elseif ( empty( $schema_types[ $type_id ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema type id "' . $type_id . '" for ' . $type_name . ' not in schema types' );
				}

				$type_id = $default_id;

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'schema type id for ' . $type_name . ' is ' . $type_id );
			}

			return $type_id;
		}

		/**
		 * Check if the Schema type requires a specific hard-coded Open Graph type.
		 *
		 * For example, a Schema place sub-type would return 'place' for the Open Graph type.
		 *
		 * Returns false or an Open Graph type string.
		 */
		public function get_schema_type_og_type( $type_id ) {

			static $local_cache = array();	// Cache for single page load.

			if ( isset( $local_cache[ $type_id ] ) ) {

				return $local_cache[ $type_id ];
			}

			/**
			 * Hard-code the Open Graph type based on the Schema type.
			 */
			foreach ( $this->p->cf[ 'head' ][ 'og_type_by_schema_type' ] as $parent_id => $og_type ) {

				if ( $this->is_schema_type_child( $type_id, $parent_id ) ) {

					return $local_cache[ $type_id ] = $og_type;
				}
			}

			return $local_cache[ $type_id ] = false;
		}

		public function ajax_schema_type_og_type() {

			if ( ! SucomUtil::get_const( 'DOING_AJAX' ) ) {

				return;

			} elseif ( SucomUtil::get_const( 'DOING_AUTOSAVE' ) ) {

				die( -1 );
			}

			check_ajax_referer( WPSSO_NONCE_NAME, '_ajax_nonce', true );

			$schema_type = sanitize_text_field( filter_input( INPUT_POST, 'schema_type' ) );

			if ( $og_type = $this->get_schema_type_og_type( $schema_type ) ) {

				die( $og_type );

			} else {

				die( -1 );
			}
		}

		/**
		 * Javascript classes to hide/show table rows by the selected schema type value.
		 */
		public static function get_schema_type_row_class( $name = 'schema_type' ) {

			static $local_cache = null;

			if ( isset( $local_cache[ $name ] ) ) {

				return $local_cache[ $name ];
			}

			$wpsso =& Wpsso::get_instance();

			$cache_md5_pre  = $wpsso->lca . '_t_';
			$cache_exp_secs = $wpsso->util->get_cache_exp_secs( $cache_md5_pre );	// Default is month in seconds.

			if ( $cache_exp_secs > 0 ) {

				$cache_salt  = __METHOD__;
				$cache_id    = $cache_md5_pre . md5( $cache_salt );
				$local_cache = get_transient( $cache_id );	// Returns false when not found.

				if ( isset( $local_cache[ $name ] ) ) {

					return $local_cache[ $name ];
				}
			}

			if ( ! is_array( $local_cache ) ) {

				$local_cache = array();
			}

			$class_type_ids = array();

			switch ( $name ) {

				case 'schema_review_item_type':

					$class_type_ids = array(
						'book'           => 'book',
						'creative_work'  => 'creative.work',
						'movie'          => 'movie',
						'product'        => 'product',
						'software_app'   => 'software.application',
					);

					break;

				case 'schema_type':

					$class_type_ids = array(
						'book'           => 'book',
						'book_audio'     => 'book.audio',
						'creative_work'  => 'creative.work',
						'course'         => 'course',
						'event'          => 'event',
						'faq'            => 'webpage.faq',
						'how_to'         => 'how.to',
						'job_posting'    => 'job.posting',
						'local_business' => 'local.business',
						'movie'          => 'movie',
						'organization'   => 'organization',
						'person'         => 'person',
						'product'        => 'product',
						'qa'             => 'webpage.qa',
						'question'       => 'question',
						'recipe'         => 'recipe',
						'review'         => 'review',
						'review_claim'   => 'review.claim',
						'software_app'   => 'software.application',
					);

					break;
			}

			foreach ( $class_type_ids as $class_name => $type_id ) {

				switch ( $type_id ) {

					case 'how.to':

						$exclude_match = '/^recipe$/';

						break;

					default:

						$exclude_match = '';

						break;
				}

				$local_cache[ $name ][ $class_name ] = $wpsso->schema->get_children_css_class( $type_id,
					$class_prefix = 'hide_' . $name, $exclude_match );
			}

			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $local_cache, $cache_exp_secs );
			}

			return $local_cache[ $name ];
		}

		/**
		 * Get the full schema type url from the array key.
		 */
		public function get_schema_type_url( $type_id, $default_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting schema url for ' . $type_id );
			}

			$schema_types = $this->get_schema_types_array( $flatten = true );

			if ( 'none' !== $type_id && isset( $schema_types[ $type_id ] ) ) {

				return $schema_types[ $type_id ];

			} elseif ( false !== $default_id && isset( $schema_types[ $default_id ] ) ) {

				return $schema_types[ $default_id ];
			}

			return false;
		}

		/**
		 * Returns an array of schema type IDs for a given type URL.
		 */
		public function get_schema_type_url_ids( $type_url ) {

			$type_ids = array();

			$schema_types = $this->get_schema_types_array( $flatten = true );

			foreach ( $schema_types as $id => $url ) {

				if ( $url === $type_url ) {

					$type_ids[] = $id;
				}
			}

			return $type_ids;
		}

		/**
		 * Returns the first schema type ID for a given type URL.
		 */
		public function get_schema_type_url_id( $type_url, $default_id = false ) {

			$schema_types = $this->get_schema_types_array( $flatten = true );

			foreach ( $schema_types as $id => $url ) {

				if ( $url === $type_url ) {

					return $id;
				}
			}

			return $default_id;
		}

		public static function get_schema_type_url_parts( $type_url ) {

			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $type_url, $match ) ) {

				return array( $match[1], $match[2] );

			} else {

				return array( null, null );	// Return two elements.
			}
		}

		public function get_children_css_class( $type_id, $class_prefix = 'hide_schema_type', $exclude_match = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $class_prefix ) ) {

				$css_classes  = '';
				$class_prefix = '';

			} else {

				$css_classes  = $class_prefix;
				$class_prefix = SucomUtil::sanitize_hookname( $class_prefix ) . '_';
			}

			foreach ( $this->get_schema_type_children( $type_id ) as $child ) {

				if ( ! empty( $exclude_match ) ) {

					if ( preg_match( $exclude_match, $child ) ) {

						continue;
					}
				}

				$css_classes .= ' ' . $class_prefix . SucomUtil::sanitize_hookname( $child );
			}

			$css_classes = trim( $css_classes );

			return $css_classes;
		}

		public function is_schema_type_child( $child_id, $member_id ) {

			static $local_cache = array();		// Cache for single page load.

			if ( isset( $local_cache[ $child_id ][ $member_id ] ) ) {

				return $local_cache[ $child_id ][ $member_id ];
			}

			if ( $child_id === $member_id ) {	// Optimize and check for obvious.

				$is_child = true;

			} else {

				$child_family = $this->get_schema_type_child_family( $child_id );

				$is_child = in_array( $member_id, $child_family ) ? true : false;
			}

			return $local_cache[ $child_id ][ $member_id ] = $is_child;
		}

		public function count_schema_type_children( $type_id ) {

			$children = $this->get_schema_type_children( $type_id );

			return count( $children );
		}

		public static function get_data_context( $json_data ) {

			if ( false !== ( $type_url = self::get_data_type_url( $json_data ) ) ) {

				return self::get_schema_type_context( $type_url );
			}

			return array();
		}

		/**
		 * json_data can be null, so don't cast an array on the input argument. 
		 *
		 * The @context value can be an array if the schema type is an extension.
		 *
		 * @context = array(
		 *	"https://schema.org",
		 *	array(
		 *		"health-lifesci" => "https://health-lifesci.schema.org",
		 *	)
		 * )
		 */
		public static function get_data_type_url( $json_data ) {

			$type_url = false;

			if ( empty( $json_data[ '@type' ] ) ) {

				return false;	// Stop here.

			} elseif ( is_array( $json_data[ '@type' ] ) ) {

				$json_data[ '@type' ] = reset( $json_data[ '@type' ] );	// Use first @type element.

				$type_url = self::get_data_type_url( $json_data );

			} elseif ( strpos( $json_data[ '@type' ], '://' ) ) {	// @type is a complete url

				$type_url = $json_data[ '@type' ];

			} elseif ( ! empty(  $json_data[ '@context' ] ) ) {	// Just in case.

				if ( is_array( $json_data[ '@context' ] ) ) {	// Get the extension url.

					$context_url = self::get_context_extension_url( $json_data[ '@context' ] );

					if ( ! empty( $context_url ) ) {	// Just in case.

						$type_url = trailingslashit( $context_url ) . $json_data[ '@type' ];
					}

				} elseif ( is_string( $json_data[ '@context' ] ) ) {

					$type_url = trailingslashit( $json_data[ '@context' ] ) . $json_data[ '@type' ];
				}
			}

			$type_url = set_url_scheme( $type_url, 'https' );	// Just in case.

			return $type_url;
		}

		public static function get_context_extension_url( array $json_data ) {

			$type_url = false;
			$ext_data = array_reverse( $json_data );	// Read the array bottom-up.

			foreach ( $ext_data as $val ) {

				if ( is_array( $val ) ) {		// If it's an extension array, drill down and return that value.

					return self::get_context_extension_url( $val );

				} elseif ( is_string( $val ) ) {	// Set a backup value in case there is no extension array.

					$type_url = $val;
				}
			}

			return false;
		}

		/**
		 * Get the site organization array.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_organization( $mixed = 'current' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$social_accounts = apply_filters( $wpsso->lca . '_social_accounts', $wpsso->cf[ 'form' ][ 'social_accounts' ] );

			$org_sameas = array();

			foreach ( $social_accounts as $social_key => $social_label ) {

				$url = SucomUtil::get_key_value( $social_key, $wpsso->options, $mixed );	// Localized value.

				if ( empty( $url ) ) {

					continue;

				} elseif ( $social_key === 'tc_site' ) {	// Convert twitter name to url.

					$url = 'https://twitter.com/' . preg_replace( '/^@/', '', $url );
				}

				if ( false !== filter_var( $url, FILTER_VALIDATE_URL ) ) {

					$org_sameas[] = $url;
				}
			}

			/**
			 * Logo and banner image dimensions are localized as well.
			 *
			 * Example: 'site_org_logo_url:width#fr_FR'.
			 */
			$org_opts = array(
				'org_url'               => SucomUtil::get_site_url( $wpsso->options, $mixed ),
				'org_name'              => SucomUtil::get_site_name( $wpsso->options, $mixed ),
				'org_name_alt'          => SucomUtil::get_site_name_alt( $wpsso->options, $mixed ),
				'org_desc'              => SucomUtil::get_site_description( $wpsso->options, $mixed ),
				'org_logo_url'          => SucomUtil::get_key_value( 'site_org_logo_url', $wpsso->options, $mixed ),
				'org_logo_url:width'    => SucomUtil::get_key_value( 'site_org_logo_url:width', $wpsso->options, $mixed ),
				'org_logo_url:height'   => SucomUtil::get_key_value( 'site_org_logo_url:height', $wpsso->options, $mixed ),
				'org_banner_url'        => SucomUtil::get_key_value( 'site_org_banner_url', $wpsso->options, $mixed ),
				'org_banner_url:width'  => SucomUtil::get_key_value( 'site_org_banner_url:width', $wpsso->options, $mixed ),
				'org_banner_url:height' => SucomUtil::get_key_value( 'site_org_banner_url:height', $wpsso->options, $mixed ),
				'org_schema_type'       => $wpsso->options[ 'site_org_schema_type' ],
				'org_place_id'          => $wpsso->options[ 'site_org_place_id' ],
				'org_sameas'            => $org_sameas,
			);

			return $org_opts;
		}

		public static function add_aggregate_offer_data( &$json_data, array $mod, array $mt_offers ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$aggregate_added  = 0;
			$aggregate_prices = array();
			$aggregate_offers = array();
			$aggregate_common = array();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'adding ' . count( $mt_offers ) . ' offers as aggregateoffer' );
			}

			foreach ( $mt_offers as $offer_num => $mt_offer ) {

				if ( ! is_array( $mt_offer ) ) {	// Just in case.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping offer #' . $offer_num . ': not an array' );
					}

					continue;
				}

				$single_offer = WpssoSchemaSingle::get_offer_data( $mod, $mt_offer );

				if ( false === $single_offer ) {

					continue;
				}

				/**
				 * Trust the offer image size and add it directly.
				 */
				if ( ! empty( $mt_offer[ 'og:image' ] ) ) {

					WpssoSchema::add_images_data_mt( $single_offer[ 'image' ], $mt_offer[ 'og:image' ] );
				}

				/**
				 * Make sure we have a price currency value.
				 */
				$price_currency = isset( $single_offer[ 'priceCurrency' ] ) ?
					$single_offer[ 'priceCurrency' ] : $wpsso->options[ 'plugin_def_currency' ];

				/**
				 * Keep track of the lowest and highest price by currency.
				 */
				if ( isset( $single_offer[ 'price' ] ) ) {	// Just in case.

					if ( ! isset( $aggregate_prices[ $price_currency ][ 'low' ] )
						|| $aggregate_prices[ $price_currency ][ 'low' ] > $single_offer[ 'price' ] ) {		// Save lower price.

						$aggregate_prices[ $price_currency ][ 'low' ] = $single_offer[ 'price' ];
					}

					if ( ! isset( $aggregate_prices[ $price_currency ][ 'high' ] )
						|| $aggregate_prices[ $price_currency ][ 'high' ] < $single_offer[ 'price' ] ) {	// Save higher price.

						$aggregate_prices[ $price_currency ][ 'high' ] = $single_offer[ 'price' ];
					}
				}

				/**
				 * Save common properties (by currency) to include in the AggregateOffer markup.
				 */
				if ( $offer_num === 0 ) {

					foreach ( preg_grep( '/^[^@]/', array_keys( $single_offer ) ) as $key ) {

						$aggregate_common[ $price_currency ][ $key ] = $single_offer[ $key ];
					}

				} elseif ( ! empty( $aggregate_common[ $price_currency ] ) ) {

					foreach ( $aggregate_common[ $price_currency ] as $key => $val ) {

						if ( ! isset( $single_offer[ $key ] ) ) {

							unset( $aggregate_common[ $price_currency ][ $key ] );

						} elseif ( $val !== $single_offer[ $key ] ) {

							unset( $aggregate_common[ $price_currency ][ $key ] );
						}
					}
				}

				/**
				 * Add the complete offer.
				 */
				$aggregate_offers[ $price_currency ][] = self::get_schema_type_context( 'https://schema.org/Offer', $single_offer );
			}

			/**
			 * Add aggregate offers grouped by currency.
			 */
			foreach ( $aggregate_offers as $price_currency => $currency_offers ) {

				if ( ( $offer_count = count( $currency_offers ) ) > 0 ) {

					$offer_group = array();

					/**
					 * Maybe set the 'lowPrice' and 'highPrice' properties.
					 */
					foreach ( array( 'low', 'high' ) as $mark ) {

						if ( isset( $aggregate_prices[ $price_currency ][ $mark ] ) ) {

							$offer_group[ $mark . 'Price' ] = $aggregate_prices[ $price_currency ][ $mark ];
						}
					}

					$offer_group[ 'priceCurrency' ] = $price_currency;

					if ( ! empty( $aggregate_common[ $price_currency ] ) ) {

						foreach ( $aggregate_common[ $price_currency ] as $key => $val ) {

							$offer_group[ $key ] = $val;
						}
					}

					$offer_group[ 'offerCount' ] = $offer_count;
					$offer_group[ 'offers' ]     = $currency_offers;

					$json_data[ 'offers' ][] = self::get_schema_type_context( 'https://schema.org/AggregateOffer', $offer_group );

					$aggregate_added++;
				}
			}

			return $aggregate_added;
		}

		/**
		 * $user_id is optional and takes precedence over the $mod post_author value.
		 */
		public static function add_author_coauthor_data( &$json_data, $mod, $user_id = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$authors_added   = 0;
			$coauthors_added = 0;

			if ( empty( $user_id ) && isset( $mod[ 'post_author' ] ) ) {

				$user_id = $mod[ 'post_author' ];
			}

			if ( empty( $user_id ) || 'none' === $user_id ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: empty user_id / post_author' );
				}

				return 0;
			}

			/**
			 * Single author.
			 */
			$authors_added += WpssoSchemaSingle::add_person_data( $json_data[ 'author' ], $mod, $user_id, $list_element = false );

			/**
			 * List of contributors / co-authors.
			 */
			if ( ! empty( $mod[ 'post_coauthors' ] ) ) {

				foreach ( $mod[ 'post_coauthors' ] as $author_id ) {

					$coauthors_added += WpssoSchemaSingle::add_person_data( $json_data[ 'contributor' ], $mod, $author_id, $list_element = true );
				}
			}

			return $authors_added + $coauthors_added;	// Return count of authors and coauthors added.
		}

		public static function add_comment_list_data( &$json_data, $mod ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$comments_added = 0;

			if ( ! $mod[ 'is_post' ] || ! $mod[ 'id' ] || ! comments_open( $mod[ 'id' ] ) ) {

				return $comments_added;
			}

			$json_data[ 'commentCount' ] = (int) get_comments_number( $mod[ 'id' ] );

			/**
			 * Only get parent comments. The add_comment_data() method will recurse and add the children.
			 */
			$comments = get_comments( array(
				'post_id' => $mod[ 'id' ],
				'status'  => 'approve',
				'parent'  => 0,					// Don't get replies.
				'order'   => 'DESC',
				'number'  => get_option( 'page_comments' ),	// Limit number of comments.
			) );

			if ( is_array( $comments ) ) {

				foreach( $comments as $num => $cmt ) {

					$comments_added += WpssoSchemaSingle::add_comment_data( $json_data[ 'comment' ], $mod, $cmt->comment_ID );
				}
			}

			return $comments_added;	// Return count of comments added.
		}

		/**
		 * Pass a single or two dimension image array in $mt_images.
		 */
		public static function add_images_data_mt( &$json_data, &$mt_images, $media_pre = 'og:image' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$images_added = 0;

			if ( isset( $mt_images[ 0 ] ) && is_array( $mt_images[ 0 ] ) ) {	// 2 dimensional array.

				foreach ( $mt_images as $mt_single_image ) {

					$images_added += WpssoSchemaSingle::add_image_data_mt( $json_data, $mt_single_image, $media_pre, $list_element = true );
				}

			} elseif ( is_array( $mt_images ) ) {

				$images_added += WpssoSchemaSingle::add_image_data_mt( $json_data, $mt_images, $media_pre, $list_element = true );
			}

			return $images_added;	// Return count of images added.
		}

		/**
		 * Called by WpssoJsonProHeadItemList.
		 */
		public static function add_itemlist_data( array &$json_data, array $mod, array $mt_og, $page_type_id, $is_main, $ppp = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$prop_name = 'itemListElement';

			$item_count = isset( $json_data[ $prop_name ] ) ? count( $json_data[ $prop_name ] ) : 0;

			/**
			 * Set the page number and the posts per page values.
			 */
			global $wpsso_paged;

			$wpsso_paged = 1;

			$ppp = self::get_posts_per_page( $mod, $page_type_id, $is_main, $ppp );

			$posts_args = array(
				'has_password'   => false,
				'order'          => 'DESC',
				'orderby'        => 'date',
				'paged'          => $wpsso_paged,
				'post_status'    => 'publish',
				'post_type'      => 'any',		// Return post, page, or any custom post type.
				'posts_per_page' => $ppp,
			);

			/**
			 * Filter to allow changing of the 'orderby' and 'order' values.
			 */
			$posts_args = apply_filters( $wpsso->lca . '_json_itemlist_posts_args', $posts_args, $mod );

			switch ( $posts_args[ 'order' ] ) {

				case 'ASC':

					$json_data[ 'itemListOrder' ] = 'https://schema.org/ItemListOrderAscending';

					break;

				case 'DESC':

					$json_data[ 'itemListOrder' ] = 'https://schema.org/ItemListOrderDescending';

					break;

				default:

					$json_data[ 'itemListOrder' ] = 'https://schema.org/ItemListUnordered';

					break;
			}

			/**
			 * Get the mod array for all posts.
			 */
			$page_posts_mods = self::get_page_posts_mods( $mod, $page_type_id, $is_main, $ppp, $wpsso_paged, $posts_args );

			if ( empty( $page_posts_mods ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: page_posts_mods array is empty' );
				}

				unset( $wpsso_paged );	// Unset the forced page number.

				return $item_count;
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'page_posts_mods array has ' . count( $page_posts_mods ) . ' elements' );
			}

			if ( empty( $json_data[ $prop_name ] ) ) {

				$json_data[ $prop_name ] = array();

			} elseif ( ! is_array( $json_data[ $prop_name ] ) ) {	// Convert single value to an array.

				$json_data[ $prop_name ] = array( $json_data[ $prop_name ] );
			}

			foreach ( $page_posts_mods as $post_mod ) {

				$item_count++;

				$post_sharing_url = $wpsso->util->get_sharing_url( $post_mod );

				$post_json_data = self::get_schema_type_context( 'https://schema.org/ListItem', array(
					'position' => $item_count,
					'url'      => $post_sharing_url,
				) );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding post id ' . $post_mod[ 'id' ] . ' to ' . $prop_name . ' as #' . $item_count );
				}

				$json_data[ $prop_name ][] = $post_json_data;	// Add the post data.

				if ( $item_count >= $ppp ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'stopping here: maximum posts per page of ' . $ppp . ' reached' );
					}

					break;	// Stop here.
				}
			}

			$filter_name = SucomUtil::sanitize_hookname( $wpsso->lca . '_json_prop_https_schema_org_' . $prop_name );

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'applying ' . $filter_name . ' filters' );
			}

			$json_data[ $prop_name ] = (array) apply_filters( $filter_name, $json_data[ $prop_name ], $mod, $mt_og, $page_type_id, $is_main );

			return $item_count;
		}

		/**
		 * $size_names can be null, a string, or an array.
		 */
		public static function add_media_data( &$json_data, $mod, $mt_og, $size_names = 'schema', $add_video = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Property:
			 *	image as https://schema.org/ImageObject
			 */
			$img_added  = 0;
			$max_nums   = $wpsso->util->get_max_nums( $mod, 'schema' );
			$mt_images  = $wpsso->og->get_all_images( $max_nums[ 'schema_img_max' ], $size_names, $mod, $check_dupes = true, $md_pre = 'schema' );

			if ( ! empty( $mt_images ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding images to json data' );
				}

				$img_added = self::add_images_data_mt( $json_data[ 'image' ], $mt_images );
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( $img_added . ' images added' );
			}

			/**
			 * Property:
			 *	video as https://schema.org/VideoObject
			 *
			 * Allow the video property to be skipped -- some schema types (organization, for example) do not include a video property.
			 */
			if ( $add_video ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding all video(s)' );
				}

				$vid_added = 0;

				if ( ! empty( $mt_og[ 'og:video' ] ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'adding videos to json data' );
					}

					$vid_added = self::add_videos_data_mt( $json_data[ 'video' ], $mt_og[ 'og:video' ], 'og:video' );
				}

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $vid_added . ' videos added' );
				}

			} elseif ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'skipping videos: add_video argument is false' );
			}

			/**
			 * Redefine mainEntityOfPage property for Attachment pages.
			 *
			 * If this is an attachment page, and the post mime_type is a known media type (image, video, or audio),
			 * then set the first media array element mainEntityOfPage to the page url, and set the page
			 * mainEntityOfPage property to false (so it doesn't get defined later).
			 */
			$main_prop = $mod[ 'is_post' ] && $mod[ 'post_type' ] === 'attachment' ? preg_replace( '/\/.*$/', '', $mod[ 'post_mime' ] ) : '';

			$main_prop = apply_filters( $wpsso->lca . '_json_media_main_prop', $main_prop, $mod );

			if ( ! empty( $main_prop ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' ' . $main_prop . ' property is main entity' );
				}

				if ( ! empty( $json_data[ $main_prop ] ) && is_array( $json_data[ $main_prop ] ) ) {

					reset( $json_data[ $main_prop ] );

					$media_key = key( $json_data[ $main_prop ] );	// Media array key should be '0'.

					if ( ! isset( $json_data[ $main_prop ][ $media_key ][ 'mainEntityOfPage' ] ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'mainEntityOfPage for ' . $main_prop . ' key ' . $media_key . ' = ' . $mt_og[ 'og:url' ] );
						}

						$json_data[ $main_prop ][ $media_key ][ 'mainEntityOfPage' ] = $mt_og[ 'og:url' ];

					} elseif ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'mainEntityOfPage for ' . $main_prop . ' key ' . $media_key . ' already defined' );
					}

					$json_data[ 'mainEntityOfPage' ] = false;
				}
			}
		}

		/**
		 * Called by Blog, CollectionPage, ProfilePage, and SearchResultsPage.
		 *
		 * Examples:
		 *
		 *	$prop_name_type_ids = array( 'mentions' => false )
		 *	$prop_name_type_ids = array( 'blogPosting' => 'blog.posting' )
		 */
		public static function add_posts_data( array &$json_data, array $mod, array $mt_og, $page_type_id, $is_main, $ppp = false, array $prop_name_type_ids ) {

			static $added_page_type_ids = array();

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$added_count = 0;	// Initialize the total posts added counter.

			/**
			 * Sanity checks.
			 */
			if ( empty( $page_type_id ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: page_type_id is empty' );
				}

				return $added_count;

			} elseif ( empty( $prop_name_type_ids ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: prop_name_type_ids is empty' );
				}

				return $added_count;
			}

			/**
			 * Prevent recursion - i.e. webpage.collection in webpage.collection, etc.
			 */
			if ( isset( $added_page_type_ids[ $page_type_id ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: preventing recursion of page_type_id ' . $page_type_id );
				}

				return $added_count;

			} else {

				$added_page_type_ids[ $page_type_id ] = true;
			}

			/**
			 * Begin timer.
			 */
			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark( 'adding posts data' );	// Begin timer.
			}

			/**
			 * Set the page number and the posts per page values.
			 */
			global $wpsso_paged;

			$wpsso_paged = 1;

			$ppp = self::get_posts_per_page( $mod, $page_type_id, $is_main, $ppp );

			/**
			 * Get the mod array for all posts.
			 */
			$page_posts_mods = self::get_page_posts_mods( $mod, $page_type_id, $is_main, $ppp, $wpsso_paged );

			if ( empty( $page_posts_mods ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: page_posts_mods array is empty' );

					$wpsso->debug->mark( 'adding posts data' );	// End timer.
				}

				unset( $wpsso_paged );	// Unset the forced page number.

				return $added_count;
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'page_posts_mods array has ' . count( $page_posts_mods ) . ' elements' );
			}

			/**
			 * Set the Schema properties.
			 */
			foreach ( $prop_name_type_ids as $prop_name => $prop_type_ids ) {

				if ( empty( $prop_type_ids ) ) {		// False or empty array - allow any schema type.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'any schema type is allowed for prop_name ' . $prop_name );
					}

					$prop_type_ids = array( 'any' );

				} elseif ( is_string( $prop_type_ids ) ) {	// Convert value to an array.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'only schema type ' . $prop_type_ids . ' allowed for prop_name ' . $prop_name );
					}

					$prop_type_ids = array( $prop_type_ids );

				} elseif ( ! is_array( $prop_type_ids ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping prop_name ' . $prop_name . ': value must be false, string, or array of schema types' );
					}

					continue;
				}

				if ( empty( $json_data[ $prop_name ] ) ) {

					$json_data[ $prop_name ] = array();

				} elseif ( ! is_array( $json_data[ $prop_name ] ) ) {	// Convert single value to an array.

					$json_data[ $prop_name ] = array( $json_data[ $prop_name ] );
				}

				$prop_count = count( $json_data[ $prop_name ] );	// Initialize the posts per property name counter.

				foreach ( $page_posts_mods as $post_mod ) {

					$post_type_id = $wpsso->schema->get_mod_schema_type( $post_mod, $get_id = true );

					$add_post_data = false;

					foreach ( $prop_type_ids as $family_member_id ) {

						if ( $family_member_id === 'any' ) {

							if ( $wpsso->debug->enabled ) {

								$wpsso->debug->log( 'accepting post id ' . $post_mod[ 'id' ] . ': any schema type is allowed' );
							}

							$add_post_data = true;

							break;	// One positive match is enough.
						}

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'checking if schema type ' . $post_type_id . ' is child of ' . $family_member_id );
						}

						$mod_is_child = $wpsso->schema->is_schema_type_child( $post_type_id, $family_member_id );

						if ( $mod_is_child ) {

							if ( $wpsso->debug->enabled ) {

								$wpsso->debug->log( 'accepting post id ' . $post_mod[ 'id' ] . ': ' .
									$post_type_id . ' is child of ' . $family_member_id );
							}

							$add_post_data = true;

							break;	// One positive match is enough.

						} elseif ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'post id ' . $post_mod[ 'id' ] . ' schema type ' .
								$post_type_id . ' not a child of ' . $family_member_id );
						}
					}

					if ( ! $add_post_data ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping post id ' . $post_mod[ 'id' ] . ' for prop_name ' . $prop_name );
						}

						continue;
					}

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'getting single mod data for post id ' . $post_mod[ 'id' ] );
					}

					$post_json_data = $wpsso->schema->get_mod_json_data( $post_mod );

					if ( empty( $post_json_data ) ) {	// Prevent null assignment.

						$wpsso->debug->log( 'single mod data for post id ' . $post_mod[ 'id' ] . ' is empty' );

						continue;	// Get the next post mod.
					}

					$added_count++;

					$prop_count++;

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'adding post id ' . $post_mod[ 'id' ] . ' to ' . $prop_name . ' as #' . $prop_count );
					}

					$json_data[ $prop_name ][] = $post_json_data;	// Add the post data.

					if ( $prop_count >= $ppp ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'stopping here: maximum posts per page of ' . $ppp . ' reached' );
						}

						break;	// Stop here.
					}
				}

				$filter_name = SucomUtil::sanitize_hookname( $wpsso->lca . '_json_prop_https_schema_org_' . $prop_name );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'applying ' . $filter_name . ' filters' );
				}

				$json_data[ $prop_name ] = (array) apply_filters( $filter_name, $json_data[ $prop_name ], $mod, $mt_og, $page_type_id, $is_main );
			}

			unset( $wpsso_paged );

			unset( $added_page_type_ids[ $page_type_id ] );

			/**
			 * End timer.
			 */
			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark( 'adding posts data' );	// End timer.
			}

			return $added_count;
		}

		/**
		 * Provide a single or two-dimension video array in $mt_videos.
		 */
		public static function add_videos_data_mt( &$json_data, $mt_videos, $media_pre = 'og:video' ) {

			$videos_added = 0;

			if ( isset( $mt_videos[ 0 ] ) && is_array( $mt_videos[ 0 ] ) ) {	// 2 dimensional array.

				foreach ( $mt_videos as $mt_single_video ) {

					$videos_added += WpssoSchemaSingle::add_video_data_mt( $json_data, $mt_single_video, $media_pre, $list_element = true );
				}

			} elseif ( is_array( $mt_videos ) ) {

				$videos_added += WpssoSchemaSingle::add_video_data_mt( $json_data, $mt_videos, $media_pre, $list_element = true );
			}

			return $videos_added;	// return count of videos added
		}

		/**
		 * Called by WpssoJsonProHeadQAPage.
		 *
		 * $json_data may be a null property, so do not force the array type on this method argument.
		 */
		public static function add_page_links( &$json_data, array $mod, array $mt_og, $page_type_id, $is_main, $ppp = false ) {

			$wpsso =& Wpsso::get_instance();

			$links_count = 0;

			/**
			 * Set the page number and the posts per page values.
			 */
			global $wpsso_paged;

			$wpsso_paged = 1;

			$ppp = is_numeric( $ppp ) ? $ppp : 200;	// Just in case.

			/**
			 * Get the mod array for all posts.
			 */
			$page_posts_mods = self::get_page_posts_mods( $mod, $page_type_id, $is_main, $ppp, $wpsso_paged );

			if ( empty( $page_posts_mods ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: page_posts_mods array is empty' );
				}

				unset( $wpsso_paged );	// Unset the forced page number.

				return $links_count;
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'page_posts_mods array has ' . count( $page_posts_mods ) . ' elements' );
			}

			foreach ( $page_posts_mods as $post_mod ) {

				$links_count++;

				$post_sharing_url = $wpsso->util->get_sharing_url( $post_mod );

				$json_data[] = $post_sharing_url;

				if ( $links_count >= $ppp ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'stopping here: maximum posts per page of ' . $ppp . ' reached' );
					}

					break;	// Stop here.
				}
			}

			return $links_count;
		}

		public static function add_person_names_data( &$json_data, $prop_name = '', array $assoc, $key_name = '' ) {

			if ( ! empty( $prop_name ) && ! empty( $key_name ) ) {

				foreach ( SucomUtil::preg_grep_keys( '/^' . $key_name .'_[0-9]+$/', $assoc ) as $value ) {

					if ( ! empty( $value ) ) {

						$json_data[ $prop_name ][] = self::get_schema_type_context( 'https://schema.org/Person', array(
							'name' => $value,
						) );
					}
				}
			}
		}

		/**
		 * Modifies the $json_data directly (by reference) and does not return a value.
		 *
		 * Do not type-cast the $json_data argument as it may be false or an array.
		 */
		public static function organization_to_localbusiness( &$json_data ) {

			if ( ! is_array( $json_data ) ) {	// Just in case.

				return;
			}

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Promote all location information up.
			 */
			if ( isset( $json_data[ 'location' ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'promoting location property array' );
				}

				$prop_added = self::add_data_itemprop_from_assoc( $json_data, $json_data[ 'location' ], 
					array_keys( $json_data[ 'location' ] ), $overwrite = false );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'promoted ' . $prop_added . ' location keys' );
				}

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'removing the location property' );
				}

				unset( $json_data[ 'location' ] );

			} elseif ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'no location property to promote' );
			}

			/**
			 * Google requires a local business to have an image.
			 *
			 * Check last as the location may have had an image that was promoted.
			 */
			if ( isset( $json_data[ 'logo' ] ) && empty( $json_data[ 'image' ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding logo from organization markup' );
				}

				$json_data[ 'image' ][] = $json_data[ 'logo' ];

			} elseif ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'logo is missing from organization markup' );
			}
		}

		/**
		 * Return any 3rd party and custom post options for a given option type.
		 * 
		 * function wpsso_get_post_event_options( $post_id, $event_id = false ) {
		 *
		 * 	WpssoSchema::get_post_type_options( $post_id, 'event', $event_id );
		 * }
		 */
		public static function get_post_type_options( $post_id, $type, $type_id = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			if ( empty( $post_id ) ) {		// Just in case.

				return false;

			} elseif ( empty( $type ) ) {	// Just in case.

				return false;

			} elseif ( ! empty( $wpsso->post ) ) {	// Just in case.

				$mod = $wpsso->post->get_mod( $post_id );

			} else {

				return false;
			}

			$type_opts = apply_filters( $wpsso->lca . '_get_' . $type . '_options', false, $mod, $type_id );

			if ( ! empty( $type_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'get_' . $type . '_options filters returned', $type_opts );
				}
			}

			$type_opts = WpssoUtil::complete_type_options( $type_opts, $mod, array( $type => 'schema_' . $type ) );

			return $type_opts;
		}

		/**
		 * Get dates from the meta data options and add ISO formatted dates to the array (passed by reference).
		 */
		public static function add_mod_opts_date_iso( array $mod, &$opts, array $opts_md_pre ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			foreach ( $opts_md_pre as $opt_pre => $md_pre ) {

				$date_iso = self::get_mod_date_iso( $mod, $md_pre );

				if ( ! is_array( $opts ) ) {	// Just in case.

					$opts = array();
				}

				$opts[ $opt_pre . '_iso' ] = $date_iso;
			}
		}

		public static function get_mod_date_iso( array $mod, $md_pre ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			if ( ! is_string( $md_pre ) ) {	// Just in case.

				return '';
			}

			$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

			return self::get_opts_date_iso( $md_opts, $md_pre );
		}

		public static function get_opts_date_iso( array $opts, $md_pre ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			if ( ! is_string( $md_pre ) ) {	// Just in case.

				return '';
			}

			$md_date     = empty( $opts[ $md_pre . '_date' ] ) || $opts[ $md_pre . '_date' ] === 'none' ? '' : $opts[ $md_pre . '_date' ];
			$md_time     = empty( $opts[ $md_pre . '_time' ] ) || $opts[ $md_pre . '_time' ] === 'none' ? '' : $opts[ $md_pre . '_time' ];
			$md_timezone = empty( $opts[ $md_pre . '_timezone' ] ) || $opts[ $md_pre . '_timezone' ] === 'none' ? '' : $opts[ $md_pre . '_timezone' ];

			if ( empty( $md_date ) && empty( $md_time ) ) {		// No date or time.

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: ' . $md_pre . ' date and time are empty' );
				}

				return '';	// Nothing to do.

			}

			if ( ! empty( $md_date ) && empty( $md_time ) ) {	// Date with no time.

				$md_time = '00:00';

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $md_pre . ' time is empty: using time ' . $md_time );
				}

			}

			if ( empty( $md_date ) && ! empty( $md_time ) ) {	// Time with no date.

				$md_date = gmdate( 'Y-m-d', time() );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $md_pre . ' date is empty: using date ' . $md_date );
				}
			}

			if ( empty( $md_timezone ) ) {				// No timezone.

				$md_timezone = get_option( 'timezone_string' );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $md_pre . ' timezone is empty: using timezone ' . $md_timezone );
				}
			}

			$date_obj = date_create( $md_date . ' ' . $md_time . ' ' . $md_timezone );

			return date_format( $date_obj, 'c' );
		}

		/**
		 * Example $names array:
		 *
		 * array(
		 * 	'prepTime'  => 'schema_recipe_prep',
		 * 	'cookTime'  => 'schema_recipe_cook',
		 * 	'totalTime' => 'schema_recipe_total',
		 * );
		 */
		public static function add_data_time_from_assoc( array &$json_data, array $assoc, array $names ) {

			foreach ( $names as $prop_name => $key_name ) {

				$t = array();

				foreach ( array( 'days', 'hours', 'mins', 'secs' ) as $time_incr ) {
					$t[ $time_incr ] = empty( $assoc[ $key_name . '_' . $time_incr ] ) ?	// 0 or empty string.
						0 : (int) $assoc[ $key_name . '_' . $time_incr ];		// Define as 0 by default.
				}

				if ( $t[ 'days' ] . $t[ 'hours' ] . $t[ 'mins' ] . $t[ 'secs' ] > 0 ) {

					$json_data[ $prop_name ] = 'P' . $t[ 'days' ] . 'DT' . $t[ 'hours' ] . 'H' . $t[ 'mins' ] . 'M' . $t[ 'secs' ] . 'S';
				}
			}
		}

		/**
		 * QuantitativeValue (width, height, length, depth, weight).
		 *
		 * unitCodes from http://wiki.goodrelations-vocabulary.org/Documentation/UN/CEFACT_Common_Codes.
		 *
		 * Example $names array:
		 *
		 * array(
		 * 	'depth'        => 'product:depth:value',
		 * 	'fluid_volume' => 'product:fluid_volume:value',
		 * 	'height'       => 'product:height:value',
		 * 	'length'       => 'product:length:value',
		 * 	'size'         => 'product:size',
		 * 	'weight'       => 'product:weight:value',
		 * 	'width'        => 'product:width:value',
		 * );
		 */
		public static function get_data_unit_from_assoc( array $assoc, array $names ) {

			$json_data = array();

			self::add_data_unit_from_assoc( $json_data, $assoc, $names );

			return empty( $json_data ) ? false : $json_data;
		}

		public static function add_data_unit_from_assoc( array &$json_data, array $assoc, array $names ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			if ( null === self::$units_cache ) {

				self::$units_cache = apply_filters( $wpsso->lca . '_schema_units', $wpsso->cf[ 'head' ][ 'schema_units' ] );
			}

			if ( ! is_array( self::$units_cache ) ) {	// Just in case.

				return;
			}

			foreach ( $names as $key => $key_name ) {

				/**
				 * Make sure the property name we need (width, height, weight, etc.) is configured.
				 */
				if ( empty( self::$units_cache[ $key ] ) || ! is_array( self::$units_cache[ $key ] ) ) {

					continue;
				}

				/**
				 * Exclude empty string values.
				 */
				if ( ! isset( $assoc[ $key_name ] ) || $assoc[ $key_name ] === '' ) {

					continue;
				}

				/**
				 * Example array:
				 *
				 *	self::$units_cache[ 'depth' ] = array(
				 *		'depth' => array(
				 *			'@context' => 'https://schema.org',
				 *			'@type'    => 'QuantitativeValue',
				 *			'name'     => 'Depth',
				 *			'unitText' => 'cm',
				 *			'unitCode' => 'CMT',
				 *		),
				 *	),
				 */
				foreach ( self::$units_cache[ $key ] as $prop_name => $prop_data ) {

					$prop_data[ 'value' ] = $assoc[ $key_name ];

					$json_data[ $prop_name ][] = $prop_data;
				}
			}
		}

		/**
		 * Deprecated on 2019/08/01.
		 */
		public static function get_data_unitcode_text( $key ) {

			return self::get_data_unit_text( $key );
		}

		/**
		 * Returns a https://schema.org/unitText value (for example, 'cm', 'ml', 'kg', etc.).
		 */
		public static function get_data_unit_text( $key ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			static $local_cache = array();

			if ( isset( $local_cache[ $key ] ) ) {

				return $local_cache[ $key ];
			}

			if ( null === self::$units_cache ) {

				self::$units_cache = apply_filters( $wpsso->lca . '_schema_units', $wpsso->cf[ 'head' ][ 'schema_units' ] );
			}

			if ( empty( self::$units_cache[ $key ] ) || ! is_array( self::$units_cache[ $key ] ) ) {

				return $local_cache[ $key ] = '';
			}

			/**
			 * Example array:
			 *
			 *	self::$units_cache[ 'depth' ] = array(
			 *		'depth' => array(
			 *			'@context' => 'https://schema.org',
			 *			'@type'    => 'QuantitativeValue',
			 *			'name'     => 'Depth',
			 *			'unitText' => 'cm',
			 *			'unitCode' => 'CMT',
			 *		),
			 *	),
			 */
			foreach ( self::$units_cache[ $key ] as $prop_name => $prop_data ) {

				if ( isset( $prop_data[ 'unitText' ] ) ) {	// Return the first match.

					return $local_cache[ $key ] = $prop_data[ 'unitText' ];
				}
			}

			return $local_cache[ $key ] = '';
		}

		/**
		 * Returns the number of Schema properties added to $json_data.
		 *
		 * Example usage:
		 *
		 *	WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array(
		 *		'datePublished' => 'article:published_time',
		 *		'dateModified'  => 'article:modified_time',
		 *	) );
		 *
		 *	WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $org_opts, array(
		 *		'url'           => 'org_url',
		 *		'name'          => 'org_name',
		 *		'alternateName' => 'org_name_alt',
		 *		'description'   => 'org_desc',
		 *		'email'         => 'org_email',
		 *		'telephone'     => 'org_phone',
		 *	) );
		 *
		 */
		public static function add_data_itemprop_from_assoc( array &$json_data, array $assoc, array $names, $overwrite = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$is_assoc = SucomUtil::is_assoc( $names );

			$prop_added = 0;

			foreach ( $names as $prop_name => $key_name ) {

				if ( ! $is_assoc ) {

					$prop_name = $key_name;
				}

				if ( self::is_valid_key( $assoc, $key_name ) ) {	// Not null, an empty string, or 'none'.

					if ( isset( $json_data[ $prop_name ] ) && empty( $overwrite ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ': itemprop exists and overwrite is false' );
						}

						continue;

					}

					if ( is_string( $assoc[ $key_name ] ) && false !== filter_var( $assoc[ $key_name ], FILTER_VALIDATE_URL ) ) {

						$json_data[ $prop_name ] = SucomUtil::esc_url_encode( $assoc[ $key_name ] );

					} else {

						$json_data[ $prop_name ] = $assoc[ $key_name ];
					}

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'assigned ' . $key_name . ' value to itemprop ' . $prop_name . ' = ' . 
							print_r( $json_data[ $prop_name ], true ) );
					}

					$prop_added++;
				}
			}

			return $prop_added;
		}

		/**
		 * Since WPSSO Core v8.0.0.
		 *
		 * Checks both the array key and its value. The array key must exist, and its value cannot be null, an empty
		 * string, the 'none' string, and if the key is a width or height, the value cannot be -1.
		 */
		public static function is_valid_key( $assoc, $key ) {

			if ( ! isset( $assoc[ $key ] ) ) {

				return false;

			} elseif ( ! self::is_valid_val( $assoc[ $key ] ) ) {	// Not null, an empty string, or 'none'.

				return false;

			} elseif ( 'width' === $key || 'height' === $key ) {
		
				if ( WPSSO_UNDEF === $assoc[ $key ] ) {	// Invalid width or height.

					return false;
				}
			}

			return true;
		}

		/**
		 * Since WPSSO Core v8.0.0.
		 *
		 * The value cannot be null, an empty string, or the 'none' string.
		 */
		public static function is_valid_val( $val ) {

			if ( null === $val ) {	// Null value is not valid.

				return false;

			} elseif ( '' === $val ) {	// Empty string.

				return false;

			} elseif ( 'none' === $val ) {	// Disabled option.

				return false;
			}

			return true;
		}

		/**
		 * Since WPSSO Core v7.7.0.
		 */
		public static function move_data_itemprop_from_assoc( array &$json_data, array &$assoc, array $names, $overwrite = true ) {
			
			$prop_added = self::add_data_itemprop_from_assoc( $json_data, $assoc, $names, $overwrite );

			foreach ( $names as $prop_name => $key_name ) {

				unset( $assoc[ $key_name ] );
			}

			return $prop_added;
		}

		/**
		 * Example usage:
		 *
		 *	$offer = WpssoSchema::get_data_itemprop_from_assoc( $mt_offer, array( 
		 *		'url'             => 'product:url',
		 *		'name'            => 'product:title',
		 *		'description'     => 'product:description',
		 *		'mpn'             => 'product:mfr_part_no',
		 *		'availability'    => 'product:availability',
		 *		'itemCondition'   => 'product:condition',
		 *		'price'           => 'product:price:amount',
		 *		'priceCurrency'   => 'product:price:currency',
		 *		'priceValidUntil' => 'product:sale_price_dates:end',
		 *	) );
		 */
		public static function get_data_itemprop_from_assoc( array $assoc, array $names, $exclude = array( '' ) ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$json_data = array();

			foreach ( $names as $prop_name => $key_name ) {

				if ( isset( $assoc[ $key_name ] ) && ! in_array( $assoc[ $key_name ], $exclude, $strict = true ) ) {

					$json_data[ $prop_name ] = $assoc[ $key_name ];

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'assigned ' . $key_name . ' value to itemprop ' . 
							$prop_name . ' = ' . print_r( $json_data[ $prop_name ], true ) );
					}
				}
			}

			return empty( $json_data ) ? false : $json_data;
		}

		public static function check_required( &$json_data, array $mod, $prop_names = array( 'image' ) ) {

			$wpsso =& Wpsso::get_instance();

			/**
			 * Check only published posts or other non-post objects.
			 */
			if ( $mod[ 'id' ] && ( ! $mod[ 'is_post' ] || 'publish' === $mod[ 'post_status' ] ) ) {

				$ref_url = $wpsso->util->maybe_set_ref( null, $mod, __( 'checking meta tags', 'wpsso' ) );

				foreach ( $prop_names as $prop_name ) {

					if ( empty( $json_data[ $prop_name ] ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( $prop_name . ' property value is empty and required' );
						}

						if ( $wpsso->notice->is_admin_pre_notices() ) {

							$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-schema-' . $prop_name;

							$error_msg = $wpsso->msgs->get( 'notice-missing-schema-' . $prop_name );

							$wpsso->notice->err( $error_msg, null, $notice_key );
						}
					}
				}

				$wpsso->util->maybe_unset_ref( $ref_url );
			}
		}

		/**
		 * Deprecated on 2020/08/14.
		 */
		public static function check_category_prop_value( &$json_data ) {
		}

		/**
		 * Convert a numeric category ID to its Google product type string.
		 */
		public static function check_prop_value_category( &$json_data, $prop_name = 'category' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking category property value' );
			}

			if ( ! empty( $json_data[ $prop_name ] ) ) {

				/**
				 * Numeric category IDs are expected to be Google product type IDs.
				 *
				 * See https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt.
				 */
				if ( is_numeric( $json_data[ $prop_name ] ) ) {

					$cat_id = $json_data[ $prop_name ];

					$categories = $wpsso->util->get_google_product_categories();

					if ( isset( $categories[ $cat_id ] ) ) {

						$json_data[ $prop_name ] = $categories[ $cat_id ];

					} else {

						unset( $json_data[ $prop_name ] );
					}
				}
			}
		}

		/**
		 * Deprecated on 2020/08/14.
		 */
		public static function check_gtin_prop_value( &$json_data ) {
		}

		/**
		 * If we have a GTIN number, try to improve the assigned property name.
		 * 
		 * Pass $json_data by reference to modify the array directly.
		 *
		 * A similar method exists as WpssoOpenGraph::check_mt_value_gtin().
		 */
		public static function check_prop_value_gtin( &$json_data, $prop_name = 'gtin' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking ' . $prop_name . ' property value' );
			}

			if ( ! empty( $json_data[ $prop_name ] ) ) {

				/**
				 * The value may come from a custom field, so trim it, just in case.
				 */
				$json_data[ $prop_name ] = trim( $json_data[ $prop_name ] );

				$gtin_len = strlen( $json_data[ $prop_name ] );

				switch ( $gtin_len ) {

					case 14:
					case 13:
					case 12:
					case 8:

						if ( empty( $json_data[ $prop_name . $gtin_len ] ) ) {

							$json_data[ $prop_name . $gtin_len ] = $json_data[ $prop_name ];
						}

						break;
				}
			}
		}

		/**
		 * Deprecated on 2020/08/14.
		 */
		public static function check_sameas_prop_values( &$json_data ) {
		}

		/**
		 * Sanitize the sameAs array - make sure URLs are valid and remove any duplicates.
		 */
		public static function check_prop_value_sameas( &$json_data, $prop_name = 'sameAs' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking ' . $prop_name . ' property value' );
			}

			if ( ! empty( $json_data[ $prop_name ] ) ) {

				if ( ! is_array( $json_data[ $prop_name ] ) ) {	// Just in case.

					$json_data[ $prop_name ] = array( $json_data[ $prop_name ] );
				}

				$added_urls = array();

				foreach ( $json_data[ $prop_name ] as $num => $url ) {

					if ( empty( $url ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ' url #' . $num . ': value is empty' );
						}

					} elseif ( isset( $json_data[ 'url' ] ) && $json_data[ 'url' ] === $url ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ' url #' . $num . ': value is "url" property (' . $url . ')' );
						}

					} elseif ( isset( $added_urls[ $url ] ) ) {	// Already added.

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ' url #' . $num . ': value already added (' . $url . ')' );
						}

					} elseif ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ' url #' . $num . ': value is not valid (' . $url . ')' );
						}

					} else {	// Mark the url as already added and get the next url.

						$added_urls[ $url ] = true;

						continue;	// Get the next url.
					}

					unset( $json_data[ $prop_name ][ $num ] );	// Remove the duplicate / invalid url.
				}

				$json_data[ $prop_name ] = array_values( $json_data[ $prop_name ] );	// Reindex / renumber the array.
			}
		}

		/**
		 * Deprecated on 2020/08/14.
		 */
		public static function check_itemprop_content_map( &$json_data, $prop_name, $map_name ) {
		}

		/**
		 * Example usage:
		 *
		 *	WpssoSchema::check_prop_value_enumeration( $offer, 'availability', 'item_availability' );
		 *
		 *	WpssoSchema::check_prop_value_enumeration( $offer, 'itemCondition', 'item_condition', 'Condition' );
		 */
		public static function check_prop_value_enumeration( &$json_data, $prop_name, $enum_key, $val_suffix = '' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking ' . $prop_name . ' property value' );
			}

			if ( empty( $json_data[ $prop_name ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $prop_name . ' property value is empty' );
				}

			} elseif ( 'none' === $json_data[ $prop_name ] ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $prop_name . ' property value is none' );
				}

			} elseif ( empty( $wpsso->cf[ 'form' ][ $enum_key ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $enum_key . ' enumeration key is unknown' );
				}

			} else {

				$enum_select = $wpsso->cf[ 'form' ][ $enum_key ];

				$prop_val = $json_data[ $prop_name ];

				if ( ! isset( $enum_select[ $prop_val ] ) ) {

					if ( isset( $conditions[ 'https://schema.org/' . $prop_val ] ) ) {

						$json_data[ $prop_name ] = 'https://schema.org/' . $prop_val;

					} elseif ( $val_suffix && isset( $conditions[ 'https://schema.org/' . $prop_val . $val_suffix ] ) ) {

						$json_data[ $prop_name ] = 'https://schema.org/' . $prop_val . $val_suffix;

					} else {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'invalid ' . $prop_name . ' property value "' . $prop_val . '"' );
						}
					
						unset( $json_data[ $prop_name ] );
					}
				}
			}
		}

		/**
		 * Returns false on error.
		 */
		public static function update_data_id( &$json_data, $type_id, $type_url = false, $hash_url = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log_args( array( 
					'type_id'  => $type_id,
					'type_url' => $type_url,
				) );
			}

			if ( empty( $type_id ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: $type_id value is empty and required' );
				}

				return false;
			}

			static $id_anchor = null;
			static $id_delim  = null;

			if ( null === $id_anchor || null === $id_delim ) {	// Optimize and call just once.

				$id_anchor = self::get_id_anchor();
				$id_delim  = self::get_id_delim();
			}

			if ( $wpsso->debug->enabled ) {

				if ( empty( $json_data[ '@id' ] ) ) {

					$wpsso->debug->log( '@id property is empty' );

				} else {

					$wpsso->debug->log( '@id property is ' . $json_data[ '@id' ] );
				}
			}

			/**
			 * If $type_id is a URL, then use it as-is.
			 */
			if ( false !== filter_var( $type_id, FILTER_VALIDATE_URL ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'provided type_id is a valid url' );
				}

				unset( $json_data[ '@id' ] );	// Just in case.

				$json_data = array( '@id' => $type_id ) + $json_data;		// Make @id the first value in the array.

			} elseif ( empty( $type_url ) && empty( $json_data[ 'url' ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: type_url and json_data url are empty' );
				}

				return false;

			} else {

				if ( ! empty( $type_url ) ) {

					$id_url = $type_url;

				} elseif ( ! empty( $json_data[ '@id' ] ) ) {

					$id_url = $json_data[ '@id' ];

				} else {
					$id_url = $json_data[ 'url' ];
				}

				/**
				 * Maybe remove an anchor ID from the begining of the type ID string.
				 */
				$type_id = preg_replace( '/^' . preg_quote( $id_anchor, '/' ) . '/', '', $type_id );

				/**
				 * Check if we already have an anchor ID in the URL.
				 */
				if ( false === strpos( $id_url, $id_anchor ) ) {

					$id_url .= $id_anchor;
				}

				/**
				 * Check if we already have the type ID in the URL.
				 */
				if ( false === strpos( $id_url, $id_anchor . $type_id ) ) {

					$id_url .= $type_id;
				}

				unset( $json_data[ '@id' ] );	// Just in case.

				$json_data = array( '@id' => $id_url ) + $json_data;	// Make @id the first value in the array.
			}

			/**
			 * Possibly hash the '@id' URL to hide a WordPress login username (as one example). Since Google reads the
			 * '@id' value as a URL, use a leading slash to create the same path for the same '@id' URLs between
			 * different Schema JSON-LD scripts (ie. not relative to the current webpage). For example:
			 *
			 *	"@id": "http://adm.surniaulula.com/author/manovotny/#sso/person"
			 *	"@id": "/06d3730efc83058f497d3d44f2f364e3#sso/person"
			 */
			if ( $hash_url ) {

				if ( preg_match( '/^(.*:\/\/.*)(' . preg_quote( $id_anchor, '/' ) . '.*)?$/U', $json_data[ '@id' ], $matches ) ) {

					$md5_url = '/' . md5( $matches[ 1 ] ) . $matches[ 2 ];

					$json_data[ '@id' ] = str_replace( $matches[ 0 ], $md5_url, $json_data[ '@id' ] );
				}
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'new @id property is ' . $json_data[ '@id' ] );
			}

			return true;
		}

		/**
		 * Sanitation used by filters to return their data.
		 */
		public static function return_data_from_filter( $json_data, $merge_data, $is_main = false ) {

			if ( ! $is_main || ! empty( $merge_data[ 'mainEntity' ] ) ) {

				unset( $json_data[ 'mainEntity' ] );
				unset( $json_data[ 'mainEntityOfPage' ] );

			} else {

				if ( ! isset( $merge_data[ 'mainEntityOfPage' ] ) ) {

					if ( ! empty( $merge_data[ 'url' ] ) ) {

						/**
						 * Remove any URL fragment from the main entity URL. The 'mainEntityOfPage' value
						 * can be empty and will be removed by WpssoSchemaGraph::optimize_json().
						 */
						$merge_data[ 'mainEntityOfPage' ] = preg_replace( '/#.*$/', '', $merge_data[ 'url' ] );
					}
				}
			}

			if ( empty( $merge_data ) ) {	// Just in case - nothing to merge.

				return $json_data;

			} elseif ( null === $json_data ) {	// Just in case - nothing to merge.

				return $merge_data;

			} elseif ( is_array( $json_data ) ) {

				$json_head = array(
					'@id'              => null,
					'@context'         => null,
					'@type'            => null,
					'mainEntityOfPage' => null,
				);

				$json_data = array_merge( $json_head, $json_data, $merge_data );

				foreach ( $json_head as $prop_name => $prop_val ) {

					if ( empty( $json_data[ $prop_name ] ) ) {

						unset( $json_data[ $prop_name ] );
					}
				}

				return $json_data;

			} else {
				return $json_data;
			}
		}

		public static function get_id_anchor() {

			return '#sso/';
		}

		public static function get_id_delim() {

			return '/';
		}

		/**
		 * Add cross-references for schema sub-type arrays that exist under more than one type.
		 *
		 * For example, Thing > Place > LocalBusiness also exists under Thing > Organization > LocalBusiness.
		 */
		private function add_schema_type_xrefs( &$schema_types ) {

			$thing =& $schema_types[ 'thing' ];	// Quick ref variable for the 'thing' array.

			/**
			 * Thing > Intangible > Enumeration
			 */
			$thing[ 'intangible' ][ 'enumeration' ][ 'specialty' ][ 'medical.specialty' ] =&
				$thing[ 'intangible' ][ 'enumeration' ][ 'medical.enumeration' ][ 'medical.specialty' ];

			$thing[ 'intangible' ][ 'service' ][ 'service.financial.product' ][ 'payment.card' ] =&
				$thing[ 'intangible' ][ 'enumeration' ][ 'payment.method' ][ 'payment.card' ];

			/**
			 * Thing > Organization > Local Business
			 */
			$thing[ 'organization' ][ 'local.business' ] =& $thing[ 'place' ][ 'local.business' ];
		}

		private static function get_page_posts_mods( array $mod, $page_type_id, $is_main, $ppp, $wpsso_paged, array $posts_args = array() ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$page_posts_mods = array();

			if ( $is_main ) {

				if ( $mod[ 'is_home_posts' ] ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'main query is home posts (archive = true)' );
					}

					$is_archive = true;

				} elseif ( $mod[ 'is_post_type_archive' ] ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'main query is post type archive (archive = true)' );
					}

					$is_archive = true;

				} elseif ( $mod[ 'is_term' ] ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'main query is term (archive = true)' );
					}

					$is_archive = true;

				} elseif ( $mod[ 'is_user' ] ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'main query is user (archive = true)' );
					}

					$is_archive = true;

				} elseif ( ! is_object( $mod[ 'obj' ] ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'object is false (archive = true)' );
					}

					$is_archive = true;

				} else {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'is main is true (archive = false)' );
					}

					$is_archive = false;
				}

			} else {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'is main is false (archive = false)' );
				}

				$is_archive = false;
			}

			$posts_args = array_merge( array(
				'has_password'   => false,
				'order'          => 'DESC',
				'orderby'        => 'date',
				'paged'          => $wpsso_paged,
				'post_status'    => 'publish',
				'post_type'      => 'any',		// Post, page, or custom post type.
				'posts_per_page' => $ppp,
			), $posts_args );

			if ( $is_archive ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'using query loop to get post mods' );
				}

				/**
				 * Setup the query for archive pages in the back-end.
				 */
				$use_query = SucomUtilWP::doing_frontend() ? true : false;

				$use_query = apply_filters( $wpsso->lca . '_page_posts_use_query', $use_query, $mod );

				if ( ! $use_query ) {

					if ( $mod[ 'is_post_type_archive' ] ) {

						$posts_args[ 'post_type' ] = $mod[ 'post_type' ];
					
					} elseif ( $mod[ 'is_user' ] ) {

						$posts_args[ 'post_type' ] = 'post';
					}

					global $wp_query;

					$saved_wp_query = $wp_query;

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'setting the $wp_query variable' );
					}

					$wp_query = new WP_Query( $posts_args );

					if ( $mod[ 'is_home_posts' ] ) {

						$wp_query->is_home = true;
					}

				} else {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'keeping existing $wp_query variable' );
					}
				}

				$have_num = 0;

				if ( have_posts() ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'looping through have_posts() results' );
					}

					while ( have_posts() ) {

						$have_num++;

						the_post();	// Defines the $post global.

						global $post;

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'getting mod for post id ' . $post->ID );
						}

						$page_posts_mods[] = $wpsso->post->get_mod( $post->ID );

						if ( $have_num >= $ppp ) {

							break;	// Stop here.
						}
					}

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'retrieved ' . $have_num . ' post mods' );
					}

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'rewinding posts query' );
					}

					rewind_posts();

				} elseif ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'no posts to add' );
				}

				/**
				 * Restore the original WP_Query.
				 */
				if ( ! $use_query ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'restoring the $wp_query variable' );
					}

					$wp_query = $saved_wp_query;
				}

			} elseif ( is_object( $mod[ 'obj' ] ) && method_exists( $mod[ 'obj' ], 'get_posts_mods' ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'using module object to get post mods' );
				}

				$page_posts_mods = $mod[ 'obj' ]->get_posts_mods( $mod, $ppp, $wpsso_paged, $posts_args );

			} else {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'no source to get post mods' );
				}
			}

			$page_posts_mods = apply_filters( $wpsso->lca . '_json_page_posts_mods', $page_posts_mods, $mod, $page_type_id, $is_main );

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'returning ' . count( $page_posts_mods ) . ' post mods' );
			}

			return $page_posts_mods;
		}

		private static function get_posts_per_page( $mod, $page_type_id, $is_main, $ppp = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( ! is_numeric( $ppp ) ) {	// Get the default if no argument provided.

				$ppp = get_option( 'posts_per_page' );
			}

			$ppp = (int) apply_filters( $wpsso->lca . '_posts_per_page', $ppp, $mod, $page_type_id, $is_main );

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'posts_per_page after filter is ' . $ppp );
			}

			return $ppp;
		}
	}
}
