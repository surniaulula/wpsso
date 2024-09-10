<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
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
		private $types_cache    = array();	// Schema types array cache.
		private $init_json_prio = -1000;	// 'wpsso_init_json_filters' action priority.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * To optimize performance and memory usage, the 'wpsso_init_json_filters' action is run at the start of
			 * WpssoSchema->get_json_data(), when the Schema filters are required. The action then unhooks itself so it
			 * can only be run once.
			 */
			$this->p->util->add_plugin_actions( $this, array( 'init_json_filters' => 0 ), $this->init_json_prio );

			$this->p->util->add_plugin_filters( $this, array(
				'plugin_image_sizes'   => 1,
				'sanitize_md_defaults' => 2,
				'sanitize_md_options'  => 2,
			), $prio = 5 );

			add_action( 'wp_ajax_wpsso_schema_type_og_type', array( $this, 'ajax_schema_type_og_type' ) );
		}

		public function action_init_json_filters() {

			$current_action = current_action();

			if ( 'wpsso_init_json_filters' !== $current_action ) {	// Just in case.

				$this->p->debug->log( 'exiting early: current action ' . $current_action . ' is incorrect' );

				return;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'init json filters' );	// Begin timer.
			}

			$classnames = $this->p->get_lib_classnames( 'json' );	// Always returns an array.

			foreach ( $classnames as $id => $classname ) {

				/*
				 * Since WPSSO Core v15.0.0.
				 *
				 * Example $filter_name = 'wpsso_init_json_filter_prop_haspart'.
				 */
				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_init_json_filter_' . $id );

				if ( apply_filters( $filter_name, true ) ) new $classname( $this->p );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'init json filters' );	// End timer.
				
				$this->p->debug->log( 'removing ' . __FILE__ . ' method action' );
			}

			/*
			 * Unhook from the 'wpsso_init_json_filters' action to make sure the Schema filters are only loaded once.
			 */
			remove_action( 'wpsso_init_json_filters', array( $this, __FUNCTION__ ), $this->init_json_prio );
		}

		public function filter_plugin_image_sizes( array $sizes ) {

			$sizes[ 'schema_1x1' ] = array(		// Option prefix.
				'name'         => 'schema-1x1',
				'label_transl' => _x( 'Schema 1:1 (Google Rich Results)', 'option label', 'wpsso' ),
			);

			$sizes[ 'schema_4x3' ] = array(		// Option prefix.
				'name'         => 'schema-4x3',
				'label_transl' => _x( 'Schema 4:3 (Google Rich Results)', 'option label', 'wpsso' ),
			);

			$sizes[ 'schema_16x9' ] = array(	// Option prefix.
				'name'         => 'schema-16x9',
				'label_transl' => _x( 'Schema 16:9 (Google Rich Results)', 'option label', 'wpsso' ),
			);

			$sizes[ 'thumb' ] = array(		// Option prefix.
				'name'         => 'thumbnail',
				'label_transl' => _x( 'Schema Thumbnail', 'option label', 'wpsso' ),
			);

			return $sizes;
		}

		public function filter_sanitize_md_defaults( $md_defs, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->filter_sanitize_md_options( $md_defs, $mod );
		}

		public function filter_sanitize_md_options( $md_opts, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( function_exists( 'is_sitemap' ) && is_sitemap() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping sanitizing md defaults for sitemap' );
				}

				return $md_opts;
			}

			if ( ! empty( $mod[ 'is_post' ] ) ) {

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_adult_type', $enum_key = 'adult_type',
					$val_prefix = '', $val_suffix = 'Consideration' );

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_age_group', $enum_key = 'age_group' );

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_avail', $enum_key = 'item_availability' );

			 	self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_condition', $enum_key = 'item_condition',
					$val_prefix = '', $val_suffix = 'Condition' );

				foreach ( SucomUtil::preg_grep_keys( '/^product_energy_efficiency(_min|_max)?$/', $md_opts ) as $prop_name => $prop_val ) {

			 		self::check_prop_value_enumeration( $md_opts, $prop_name, $enum_key = 'energy_efficiency',
						$val_prefix = 'EUEnergyEfficiencyCategory' );
				}

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_price_type', $enum_key = 'price_type' );

				foreach ( SucomUtil::preg_grep_keys( '/^product_size_group_[0-9]+$/', $md_opts ) as $prop_name => $prop_val ) {

					self::check_prop_value_enumeration( $md_opts, $prop_name, $enum_key = 'size_group',
						$val_prefix = 'WearableSizeGroup' );
				}

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_size_system', $enum_key = 'size_system',
					$val_prefix = 'WearableSizeSystem' );

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'product_target_gender', $enum_key = 'target_gender' );

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'schema_event_attendance', $enum_key = 'event_attendance' );

				self::check_prop_value_enumeration( $md_opts, $prop_name = 'schema_event_status', $enum_key = 'event_status' );

				/*
				 * Check offer availability values and skip any ':disabled' status keys.
				 */
				foreach ( SucomUtil::preg_grep_keys( '/^schema_(.*)_offer_avail[^:]*$/', $md_opts ) as $prop_name => $prop_val ) {

					self::check_prop_value_enumeration( $md_opts, $prop_name, $enum_key = 'item_availability' );
				}
			}

			return $md_opts;
		}

		/*
		 * Returns the language and country code, like "en_US".
		 *
		 * If the $prime_lang argument value is true, then return the 2 character primary language instead, like "en". Note
		 * that some Chinese languages will return a 5 character string instead, like 'zh-cn' or 'zh-tw'.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public function get_schema_lang( $mixed = 'current', $prime_lang = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$schema_lang = SucomUtilWP::get_locale( $mixed );

			/*
			 * If there is a multilingual plugin available, skip any custom language value.
			 */
			if ( empty( $this->p->avail[ 'lang' ][ 'any' ] ) ) {

				if ( ! empty( $mixed[ 'obj' ] ) && $mixed[ 'id' ] ) {

					$schema_type_id = $this->get_mod_schema_type_id( $mixed );

					if ( $this->is_schema_type_child( $schema_type_id, 'creative.work' ) ) {

						$custom_schema_lang = $mixed[ 'obj' ]->get_options( $mixed[ 'id' ], 'schema_lang' );

						/*
						 * Check that the id value is not true, false, null, empty string, or 'none'.
						 */
						if ( SucomUtil::is_valid_option_value( $custom_schema_lang ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'custom schema_lang = ' . $custom_schema_lang );
							}

							$schema_lang = $custom_schema_lang;
						}
					}
				}
			}

			if ( $prime_lang ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting primary language for ' . $schema_lang );
				}

				/*
				 * Exceptions.
				 *
				 * See https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap.
				 */
				switch ( $schema_lang ) {

					case 'zh_CN':	// Simplified Chinese (China).

						$schema_lang = 'zh-cn';

						break;

					case 'zh_HK':	// Traditional Chinese (Hong Kong).
					case 'zh_TW':	// Traditional Chinese (Taiwan).

						$schema_lang = 'zh-tw';

						break;

					default:

						if ( function_exists( 'locale_get_primary_language' ) ) {	// Requires the PHP Intl package.

							$schema_lang = locale_get_primary_language( $schema_lang );

						} else $schema_lang = substr( $schema_lang, 0, 2 );

						break;
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning schema_lang = ' . $schema_lang );
			}

			return $schema_lang;
		}

		/*
		 * Called by WpssoHead->get_head_array().
		 *
		 * Pass $mt_og by reference to assign values to the schema:* internal meta tags.
		 */
		public function get_array( array $mod, array &$mt_og = array() ) {	// Pass by reference is OK.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'build schema array' );	// Begin timer.
			}

			$page_language = $mt_og[ 'schema:language' ] = $this->get_schema_lang( $mod );
			$page_type_id  = $mt_og[ 'schema:type:id' ]  = $this->get_mod_schema_type_id( $mod );		// Example: article.tech.
			$page_type_url = $mt_og[ 'schema:type:url' ] = $this->get_schema_type_url( $page_type_id );	// Example: https://schema.org/TechArticle.

			list(
				$mt_og[ 'schema:type:context' ],
				$mt_og[ 'schema:type:name' ],
				$mt_og[ 'schema:type:path' ],
			) = $this->get_schema_type_url_parts( $page_type_url );

			$page_type_ids   = array();
			$page_type_added = array();	// Prevent duplicate schema types.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'head schema type id is ' . $page_type_id . ' (' . $page_type_url . ')' );
			}

			/*
			 * Include Schema Organization or Person, and Schema WebSite markup on the home page.
			 */
			if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

				switch ( $this->p->options[ 'site_pub_schema_type' ] ) {

					case 'organization':

						$site_org_type_id = $this->p->options[ 'site_org_schema_type' ];	// Organization or a sub-type of organization.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'organization schema type id is ' . $site_org_type_id );
						}

						if ( $page_type_id !== $site_org_type_id && ! $this->is_schema_type_child( $page_type_id, $site_org_type_id ) ) {

							$page_type_ids[ $site_org_type_id ] = true;
						}

						break;

					case 'person':

						$page_type_ids[ 'person' ] = true;

						break;
				}

				$page_type_ids[ 'website' ] = true;
			}

			/*
			 * Could be an organization, website, or person, so include last to reenable (if disabled by default).
			 */
			if ( ! empty( $page_type_url ) ) {

				$page_type_ids[ $page_type_id ] = true;
			}

			/*
			 * Array (
			 *	[product]      => true
			 *	[website]      => true
			 *	[organization] => true
			 *	[person]       => false
			 * )
			 *
			 * Hooked by WpssoBcFilters->filter_json_array_schema_page_type_ids() to add its 'breadcrumb.list' type id.
			 */
			$page_type_ids = apply_filters( 'wpsso_json_array_schema_page_type_ids', $page_type_ids, $mod );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'page_type_ids', $page_type_ids );
			}

			/*
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

				} else $is_main = false;	// Default for all other types.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema main entity is ' . ( $is_main ? 'true' : 'false' ) . ' for ' . $type_id );
				}

				/*
				 * WpssoSchema->get_json_data() returns a two dimensional array of json data unless $single is true.
				 */
				$json_data = $this->get_json_data( $mod, $mt_og, $type_id, $is_main, $single = false );

				/*
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

					$single_graph = apply_filters( 'wpsso_json_data_graph_element', $single_graph, $mod, $mt_og, $page_type_id, $is_main );

					WpssoSchemaGraph::add_data( $single_graph );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'schema type id ' . $type_id );	// End timer.
				}
			}

			/*
			 * Get the @graph json array and start a new @graph array.
			 */
			$graph_type_url = WpssoSchemaGraph::get_type_url();
			$graph_json     = WpssoSchemaGraph::get_json_reset_data();
			$filter_name    = SucomUtil::sanitize_hookname( 'wpsso_json_prop_' . $graph_type_url );
			$graph_json     = apply_filters( $filter_name, $graph_json, $mod, $mt_og );

			$schema_scripts  = array();

			if ( ! empty( $graph_json[ '@graph' ] ) ) {	// Just in case.

				$graph_json = WpssoSchemaGraph::optimize_json( $graph_json );

				$schema_scripts[][] = '<script type="application/ld+json" id="wpsso-schema-graph">' .
					$this->p->util->json_format( $graph_json ) . '</script>' . "\n";
			}

			unset( $graph_json );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'build schema array' );	// End timer.
			}

			$schema_scripts = apply_filters( 'wpsso_schema_scripts', $schema_scripts, $mod, $mt_og );

			return $schema_scripts;
		}

		/*
		 * Get the JSON-LD data array.
		 *
		 * Returns a two dimensional array of json data unless $single is true.
		 */
		public function get_json_data( array $mod, array $mt_og, $page_type_id = false, $is_main = false, $single = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * To optimize performance and memory usage, the 'wpsso_init_json_filters' action is run at the start of
			 * WpssoSchema->get_json_data(), when the Schema filters are required. The action then unhooks itself so it
			 * can only be run once.
			 */
			do_action( 'wpsso_init_json_filters' );

			if ( empty( $page_type_id ) ) {

				$page_type_id = $this->get_mod_schema_type_id( $mod );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'page type id is ' . $page_type_id );
				}
			}

			/*
			 * Returns an array of type ids with gparents, parents, child (in that order).
			 */
			$child_family_urls = array();

			foreach ( $this->get_schema_type_child_family( $page_type_id ) as $type_id ) {

				$child_family_urls[] = $this->get_schema_type_url( $type_id );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'child_family_urls', $child_family_urls );
			}

			$json_data = null;

			foreach ( $child_family_urls as $num => $type_url ) {

				$type_hookname      = SucomUtil::sanitize_hookname( $type_url );
				$data_filter_name   = 'wpsso_json_data_' . $type_hookname;
				$valid_filter_name  = 'wpsso_json_data_validate_' . $type_hookname;
				$method_filter_name = 'filter_json_data_' . $type_hookname;

				/*
				 * Add website, organization, and person markup to home page.
				 */
				if ( false !== has_filter( $data_filter_name ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying filters "' . $data_filter_name . '"' );
					}

					$json_data = apply_filters( $data_filter_name, $json_data, $mod, $mt_og, $page_type_id, $is_main );

					if ( false !== has_filter( $valid_filter_name ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'applying filters "' . $data_filter_name . '"' );
						}

						$json_data = apply_filters( $valid_filter_name, $json_data, $mod, $mt_og, $page_type_id, $is_main );
					}

				/*
				 * Home page (static or blog archive).
				 */
				} elseif ( $mod[ 'is_home' ] && method_exists( $this, $method_filter_name ) ) {

					/*
					 * $is_main is always false for methods.
					 */
					$json_data = call_user_func( array( $this, $method_filter_name ), $json_data, $mod, $mt_og, $page_type_id, false );

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'data filter not found: ' . $data_filter_name );
					}
				}
			}

			if ( empty( $json_data ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'json data is empty' );
				}

				$json_data = array();	// Just in case.

			} elseif ( isset( $json_data[ 0 ] ) && SucomUtil::is_non_assoc( $json_data ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'json data includes ' . count( $json_data ) . ' data arrays' );
				}

			} else {

				self::update_data_id( $json_data, empty( $mod[ 'id' ] ) ? $page_type_id : array( $page_type_id, $mod[ 'id' ] ) );

				$json_data = array( $json_data );
			}

			return $single ? reset( $json_data ) : $json_data;
		}

		public function get_json_data_home_website() {

			$mod = WpssoAbstractWpMeta::get_mod_home();

			$mt_og = array();

			/*
			 * WpssoSchema->get_json_data() returns a two dimensional array of json data unless $single is true.
			 */
			$json_data = $this->get_json_data( $mod, $mt_og, $page_type_id = 'website', $is_main = false, $single = true );

			return $json_data;
		}

		/*
		 * See WpssoFaqShortcodeFaq->do_shortcode().
		 * See WpssoFaqShortcodeQuestion->do_shortcode().
		 */
		public function get_mod_script_type_application_ld_json_html( array $mod, $css_id = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_data = $this->get_mod_json_data( $mod );	// Can return false.

			if ( empty( $json_data ) ) {	// Just in case.

				return '';
			}

			WpssoSchemaGraph::clean_json( $json_data );

			if ( empty( $css_id ) ) {	// Just in case.

				$css_id = 'wpsso-schema-' . md5( serialize( $json_data ) );	// md5() input must be a string.
			}

			return '<script type="application/ld+json" id="' . $css_id . '">' . $this->p->util->json_format( $json_data ) . '</script>' . "\n";
		}

		public function get_mod_json_data( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $mod[ 'name' ] ) || empty( $mod[ 'id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: mod name or id is empty' );
				}

				return false;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting schema type for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] );
			}

			$page_type_id = $this->get_mod_schema_type_id( $mod );

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

				$this->p->debug->log( 'page type id is ' . $page_type_id );
			}

			$ref_url = $this->p->util->maybe_set_ref( null, $mod, __( 'adding schema', 'wpsso' ) );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting open graph meta tag array' );
			}

			$mt_og = $this->p->og->get_array( $mod, $size_names = 'schema', $md_pre = array( 'schema', 'og' ) );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting schema json-ld markup array' );
			}

			/*
			 * WpssoSchema->get_json_data() returns a two dimensional array of json data unless $single is true.
			 */
			$json_data = $this->get_json_data( $mod, $mt_og, $page_type_id, $is_main = true, $single = true );

			$this->p->util->maybe_unset_ref( $ref_url );

			return $json_data;
		}

		/*
		 * Since WPSSO Core v9.1.2.
		 *
		 * Returns the schema type id.
		 */
		public function get_mod_schema_type_id( array $mod, $use_md_opts = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->get_mod_schema_type( $mod, $get_id = true, $use_md_opts );
		}

		/*
		 * Since WPSSO Core v3.37.1.
		 *
		 * Returns the schema type id by default.
		 *
		 * Use $get_id = false to return the schema type URL instead of the ID.
		 */
		public function get_mod_schema_type( array $mod, $get_id = true, $use_md_opts = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			$cache_salt = false;

			/*
			 * Archive pages can call this method several times.
			 *
			 * Optimize and cache post/term/user schema type values.
			 */
			if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {

				/*
				 * Note that the sort order, page number, locale, amp and embed checks are provided by
				 * WpssoHead->get_head_cache_index() and not SucomUtil::get_mod_salt().
				 */
				$cache_salt = SucomUtil::get_mod_salt( $mod ) . '_get:' . (string) $get_id . '_use:' . (string) $use_md_opts;

				if ( isset( $local_cache[ $cache_salt ] ) ) {

					return $local_cache[ $cache_salt ];

				}
			}

			$type_id      = null;
			$schema_types = $this->get_schema_types( $flatten = true );

			/*
			 * Maybe get a custom schema type id from the post, term, or user meta.
			 */
			if ( $use_md_opts ) {

				if ( ! empty( $mod[ 'obj' ] ) && $mod[ 'id' ] ) {	// Just in case.

					$type_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_type' );	// Returns null if an index key is not found.

					if ( empty( $type_id ) || 'none' === $type_id || empty( $schema_types[ $type_id ] ) ) {	// Check for an invalid type id.

						$type_id = null;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'custom schema_type = ' . $type_id );
					}
				}
			}

			$is_custom = empty( $type_id ) ? false : true;

			if ( ! $is_custom ) {	// No custom schema type id from the post, term, or user meta.

				/*
				 * Similar module type logic can be found in the following methods:
				 *
				 * See WpssoOpenGraph->get_mod_og_type().
				 * See WpssoPage->get_description().
				 * See WpssoPage->get_the_title().
				 * See WpssoSchema->get_mod_schema_type().
				 * See WpssoUtil->get_canonical_url().
				 */
				if ( $mod[ 'is_home' ] ) {	// Home page (static or blog archive).

					if ( $mod[ 'is_home_page' ] ) {	// Static front page (singular post).

						$type_id = $this->get_schema_type_id_for( 'home_page' );

					} else {

						$type_id = $this->get_schema_type_id_for( 'home_posts' );
					}

				} elseif ( $mod[ 'is_comment' ] ) {

					if ( is_numeric( $mod[ 'comment_rating' ] ) ) {

						$type_id = $this->get_schema_type_id_for( 'comment_review' );

					} elseif ( $mod[ 'comment_parent' ] ) {

						$type_id = $this->get_schema_type_id_for( 'comment_reply' );

					} else {

						$type_id = $this->get_schema_type_id_for( 'comment' );
					}

				} elseif ( $mod[ 'is_post' ] ) {

					if ( $mod[ 'post_type' ] ) {	// Just in case.

						if ( $mod[ 'is_post_type_archive' ] ) {	// The post ID may be 0.

							$type_id = $this->get_schema_type_id_for( 'pta_' . $mod[ 'post_type' ] );

							if ( empty( $type_id ) ) {	// Just in case.

								$type_id = $this->get_schema_type_id_for( 'archive_page' );
							}

						} else {

							$type_id = $this->get_schema_type_id_for( $mod[ 'post_type' ] );

							if ( empty( $type_id ) ) {	// Just in case.

								$type_id = $this->get_schema_type_id_for( 'page' );
							}
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no post type' );
					}

				} elseif ( $mod[ 'is_term' ] ) {

					if ( ! empty( $mod[ 'tax_slug' ] ) ) {	// Just in case.

						$type_id = $this->get_schema_type_id_for( 'tax_' . $mod[ 'tax_slug' ] );
					}

					if ( empty( $type_id ) ) {	// Just in case.

						$type_id = $this->get_schema_type_id_for( 'archive_page' );
					}

				} elseif ( $mod[ 'is_user' ] ) {

					$type_id = $this->get_schema_type_id_for( 'user_page' );

				} elseif ( $mod[ 'is_search' ] ) {

					$type_id = $this->get_schema_type_id_for( 'search_page' );

				} elseif ( $mod[ 'is_archive' ] ) {

					$type_id = $this->get_schema_type_id_for( 'archive_page' );
				}

				if ( empty( $type_id ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'unable to determine schema type id (using default)' );
					}

					$type_id = 'webpage';
				}
			}

			$type_id = apply_filters( 'wpsso_schema_type', $type_id, $mod, $is_custom );

			$get_value = false;

			if ( empty( $type_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: schema type id is empty' );
				}

			} elseif ( 'none' === $type_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: schema type id is disabled' );
				}

			} elseif ( ! isset( $schema_types[ $type_id ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning false: schema type id ' . $type_id . ' is unknown' );
				}

			} elseif ( ! $get_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning schema type url: ' . $schema_types[ $type_id ] );
				}

				$get_value = $schema_types[ $type_id ];

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning schema type id: ' . $type_id );
				}

				$get_value = $type_id;
			}

			/*
			 * Optimize and cache post/term/user schema type values.
			 */
			if ( $cache_salt ) {

				$local_cache[ $cache_salt ] = $get_value;
			}

			return $get_value;
		}

		/*
		 * Since WPSSO Core v9.1.2.
		 *
		 * Returns the schema type URL.
		 */
		public function get_mod_schema_type_url( array $mod, $use_md_opts = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->get_mod_schema_type( $mod, $get_id = false, $use_md_opts );
		}

		/*
		 * Since WPSSO Core v15.8.0.
		 *
		 * Refresh the Schema types transient cache.
		 */
		public function refresh_schema_types() {

			$this->get_schema_types( $flatten = true, $read_cache = false );

			self::get_schema_type_row_class( $name = 'schema_type', $read_cache = false );

			self::get_schema_type_row_class( $name = 'schema_review_item_type', $read_cache = false );
		}

		/*
		 * Returns a one-dimensional (flat) array of schema types by default, otherwise returns a multi-dimensional array
		 * of all schema types, including cross-references for sub-types with multiple parent types.
		 *
		 * $read_cache is false when called from the WpssoUpgrade::options() method.
		 *
		 * Uses a transient cache object and the $types_cache class property.
		 */
		public function get_schema_types( $flatten = true, $read_cache = true ) {

			if ( ! $read_cache ) {

				$this->types_cache = array();
			}

			if ( ! isset( $this->types_cache[ 'filtered' ] ) ) {

				$cache_md5_pre  = 'wpsso_t_';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'transient' );

				if ( $cache_exp_secs > 0 ) {

					$cache_salt = __METHOD__;
					$cache_id   = $cache_md5_pre . md5( $cache_salt );

					if ( $read_cache ) {

						$this->types_cache = get_transient( $cache_id );	// Returns false when not found.

						if ( ! empty( $this->types_cache ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'using schema types array from transient ' . $cache_id );
							}

						} else $this->types_cache = array();
					}
				}

				if ( ! isset( $this->types_cache[ 'filtered' ] ) ) {	// Maybe from transient cache - re-check if filtered.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->mark( 'create schema types array' );	// Begin timer.
					}

					/*
					 * Filtered array.
					 */
					$this->types_cache[ 'filtered' ] = apply_filters( 'wpsso_schema_types', $this->p->cf[ 'head' ][ 'schema_type' ] );

					/*
					 * Flattened array (before adding cross-references).
					 */
					$this->types_cache[ 'flattened' ] = SucomUtil::array_flatten( $this->types_cache[ 'filtered' ] );

					/*
					 * Adding cross-references to filtered array.
					 */
					$this->add_schema_type_xrefs( $this->types_cache[ 'filtered' ] );

					/*
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

		public function get_schema_types_select( $schema_types = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! is_array( $schema_types ) ) {

				$schema_types = $this->get_schema_types( $flatten = false );
			}

			$schema_types = SucomUtil::array_flatten( $schema_types );

			$select = array();

			foreach ( $schema_types as $type_id => $type_url ) {

				list( $type_context, $type_name, $type_path ) = $this->get_schema_type_url_parts( $type_url );

				switch ( $this->p->options[ 'plugin_schema_types_select_format' ] ) {

					case 'name':	// Options default.

						$select[ $type_id ] = $type_name;

						break;

					case 'name_id':

						$select[ $type_id ] = $type_name . ' [' . $type_id . ']';

						break;

					case 'id':

						$select[ $type_id ] = $type_id;

						break;

					case 'id_url':

						$select[ $type_id ] = $type_id . ' | ' . $type_path;

						break;

					case 'id_name':

						$select[ $type_id ] = $type_id . ' | ' . $type_name;

						break;

					default:

						$select[ $type_id ] = $type_name;

						break;
				}
			}

			if ( defined( 'SORT_STRING' ) ) {	// Just in case.

				asort( $select, SORT_STRING );

			} else {

				asort( $select );
			}

			return $select;
		}

		/*
		 * Returns an array of schema type ids with gparent, parent, child (in that order).
		 *
		 * $use_cache is false when calling get_schema_type_child_family() recursively.
		 */
		public function get_schema_type_child_family( $child_id, $use_cache = true, &$child_family = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $use_cache ) {

				$cache_md5_pre  = 'wpsso_t_';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'transient' );

				if ( $cache_exp_secs > 0 ) {

					$cache_salt   = __METHOD__ . '(child_id:' . $child_id . ')';
					$cache_id     = $cache_md5_pre . md5( $cache_salt );
					$child_family = get_transient( $cache_id );	// Returns false when not found.

					if ( is_array( $child_family ) ) {

						return $child_family;

					} else $child_family = array();
				}
			}

			$schema_types = $this->get_schema_types( $flatten = true );	// Defines the 'parents' array.

			if ( isset( $this->types_cache[ 'parents' ][ $child_id ] ) ) {

				foreach( $this->types_cache[ 'parents' ][ $child_id ] as $parent_id ) {

					if ( $parent_id !== $child_id )	{		// Prevent infinite loops.

						/*
						 * $use_cache is false for recursive calls.
						 */
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

		/*
		 * Returns an array of schema type ids with child, parent, gparent (in that order).
		 *
		 * $use_cache is false when calling get_schema_type_children() recursively.
		 */
		public function get_schema_type_children( $type_id, $use_cache = true, &$children = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting children for type id ' . $type_id );
			}

			if ( $use_cache ) {

				$cache_md5_pre  = 'wpsso_t_';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'transient' );

				if ( $cache_exp_secs > 0 ) {

					$cache_salt = __METHOD__ . '(type_id:' . $type_id . ')';
					$cache_id   = $cache_md5_pre . md5( $cache_salt );
					$children   = get_transient( $cache_id );	// Returns false when not found.

					if ( is_array( $children ) ) {

						return $children;

					} else $children = array();
				}
			}

			$children[] = $type_id;	// Add children before parents.

			$schema_types = $this->get_schema_types( $flatten = true );	// Defines the 'parents' array.

			if ( isset( $this->types_cache[ 'parents' ] ) && is_array( $this->types_cache[ 'parents' ] ) ) {

				foreach ( $this->types_cache[ 'parents' ] as $child_id => $parent_ids ) {

					foreach( $parent_ids as $parent_id ) {

						if ( $parent_id === $type_id ) {

							/*
							 * $use_cache is false for recursive calls.
							 */
							$this->get_schema_type_children( $child_id, $child_use_cache = false, $children );
						}
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

		public static function get_schema_type_context( $type_url, $json_data = array() ) {

			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $type_url, $match ) ) {

				$context_value = $match[ 1 ];
				$type_value    = $match[ 2 ];

				/*
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
						$ext[ 1 ] . $ext[ 3 ],
						array(
							$ext[ 2 ] => $ext[ 0 ],
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

				/*
				 * Include $json_head first to keep @id, @context, and @type top-most.
				 */
				if ( is_array( $json_data ) ) {	// Just in case.

					$json_data = array_merge( $json_head, $json_data, $json_values );

					if ( empty( $json_data[ '@id' ] ) ) {

						unset( $json_data[ '@id' ] );
					}

				} else return $json_values;
			}

			return $json_data;
		}

		public function get_schema_type_id_for( $opt_suffix, $default_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'opt_suffix' => $opt_suffix,
					'default_id' => $default_id,
				) );
			}

			if ( empty( $opt_suffix ) ) {	// Just in case.

				return $default_id;
			}

			$opt_key      = SucomUtil::sanitize_key( 'schema_type_for_' . $opt_suffix );
			$type_id      = isset( $this->p->options[ $opt_key ] ) ? $this->p->options[ $opt_key ] : $default_id;
			$schema_types = $this->get_schema_types( $flatten = true );	// Uses a class variable cache.

			if ( empty( $type_id ) || 'none' === $type_id || empty( $schema_types[ $type_id ] ) ) {

				return $default_id;
			}

			return $type_id;
		}

		public function get_default_schema_type_name_for( $opt_suffix, $default_id = null ) {

			if ( empty( $opt_suffix ) ) {	// Just in case.

				return $default_id;
			}

			$opt_key      = SucomUtil::sanitize_key( 'schema_type_for_' . $opt_suffix );
			$type_id      = $this->p->opt->get_defaults( $opt_key );		// Uses a local cache.
			$schema_types = $this->get_schema_types( $flatten = true );	// Uses a class variable cache.

			if ( empty( $type_id ) || 'none' === $type_id || empty( $schema_types[ $type_id ] ) ) {

				/*
				 * We're returning the Schema type name, so make sure the default schema type id is valid as well.
				 */
				if ( empty( $default_id ) || 'none' === $default_id || empty( $schema_types[ $default_id ] ) ) {

					return $default_id;
				}

				$type_id = $default_id;
			}

			list( $type_context, $type_name, $type_path ) = $this->get_schema_type_url_parts_by_id( $type_id );

			return $type_name;
		}

		/*
		 * Check if the Schema type matches a pre-defined Open Graph type.
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

			/*
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

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) {	// Just in case.

				return;

			} elseif ( SucomUtil::get_const( 'DOING_AUTOSAVE' ) ) {

				die( -1 );
			}

			check_ajax_referer( WPSSO_NONCE_NAME, '_ajax_nonce', $die = true );

			$schema_type = sanitize_text_field( filter_input( INPUT_POST, 'schema_type' ) );

			if ( $og_type = $this->get_schema_type_og_type( $schema_type ) ) {

				die( $og_type );

			} else {

				die( -1 );
			}
		}

		/*
		 * Javascript classes to hide/show table rows by the selected schema type value.
		 *
		 * See WpssoSchema->refresh_schema_types().
		 */
		public static function get_schema_type_row_class( $name = 'schema_type', $read_cache = true ) {

			static $local_cache = null;

			if ( $read_cache ) {

				if ( isset( $local_cache[ $name ] ) ) {

					return $local_cache[ $name ];
				}
			}

			$wpsso =& Wpsso::get_instance();

			$cache_md5_pre  = 'wpsso_t_';
			$cache_exp_secs = $wpsso->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'transient' );

			if ( $cache_exp_secs > 0 ) {

				$opt_version = $wpsso->opt->get_version( $wpsso->options, 'wpsso' );	// Returns 'opt_version'.
				$cache_salt  = __METHOD__ . '(opt_version:' . $opt_version . ')';
				$cache_id    = $cache_md5_pre . md5( $cache_salt );

				if ( $read_cache ) {

					$local_cache = get_transient( $cache_id );	// Returns false when not found.

					if ( isset( $local_cache[ $name ] ) ) {

						return $local_cache[ $name ];
					}
				}
			}

			$local_cache = array();
			$type_ids    = array();

			switch ( $name ) {

				case 'schema_type':

					$type_ids = array(
						'article',
						'book',
						'book.audio',
						'course',
						'creative.work',
						'event',
						'howto',
						'item.list',
						'job.posting',
						'local.business',
						'learning.resource',
						'movie',
						'organization',
						'person',
						'place',
						'product',
						'question',
						'recipe',
						'review',
						'review.claim',
						'software.application',
						'webpage',
						'webpage.faq',
						'webpage.profile',
						'webpage.qa',
					);

					break;

				case 'schema_review_item_type':

					$type_ids = array(
						'book',
						'creative.work',
						'food.establishment',
						'local.business',
						'movie',
						'place',
						'product',
						'software.application',
					);

					break;
			}

			foreach ( $type_ids as $type_id ) {

				switch ( $type_id ) {

					case 'howto':

						$exclude_match = '/^recipe$/';

						break;

					default:

						$exclude_match = '';

						break;
				}

				$local_cache[ $name ][ $type_id ] = $wpsso->schema->get_children_css_class( $type_id, $class_prefix = 'hide_' . $name, $exclude_match );
			}

			unset( $type_ids, $type_id, $exclude_match );

			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $local_cache, $cache_exp_secs );
			}

			return $local_cache[ $name ];
		}

		/*
		 * Get the full schema type url from the array key.
		 */
		public function get_schema_type_url( $type_id, $default_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting schema url for ' . $type_id );
			}

			if ( ! empty( $type_id ) ) {	// Not null, false, 0, or empty string.

				$schema_types = $this->get_schema_types( $flatten = true );

				if ( 'none' !== $type_id && isset( $schema_types[ $type_id ] ) ) {

					return $schema_types[ $type_id ];

				} elseif ( false !== $default_id && isset( $schema_types[ $default_id ] ) ) {

					return $schema_types[ $default_id ];
				}
			}

			return false;
		}

		/*
		 * Returns an array of schema type id for a given type URL.
		 */
		public function get_schema_type_url_ids( $type_url ) {

			$type_ids = array();

			$schema_types = $this->get_schema_types( $flatten = true );

			foreach ( $schema_types as $id => $url ) {

				if ( $url === $type_url ) {

					$type_ids[] = $id;
				}
			}

			return $type_ids;
		}

		/*
		 * Returns the first schema type id for a given type URL.
		 */
		public function get_schema_type_url_id( $type_url, $default_id = false ) {

			$schema_types = $this->get_schema_types( $flatten = true );

			foreach ( $schema_types as $id => $url ) {

				if ( $url === $type_url ) {

					return $id;
				}
			}

			return $default_id;
		}

		/*
		 * Returns the Schema type context and name.
		 *
		 * Example array( 'https://schema.org', 'TechArticle', 'schema.org/TechArticle' ).
		 */
		public function get_schema_type_url_parts( $type_url ) {

			if ( preg_match( '/^(.+:\/\/.+)\/(.+)$/', $type_url, $match ) ) {

				$type_path = preg_replace( '/^.*\/\//', '', $type_url );	// Remove 'https://'.

				return array( $match[ 1 ], $match[ 2 ], $type_path );
			}

			return array( null, null, null );
		}

		public function get_schema_type_url_parts_by_id( $type_id ) {

			$type_url = $this->get_schema_type_url( $type_id );

			return $this->get_schema_type_url_parts( $type_url );
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
				$class_prefix = SucomUtil::sanitize_css_id( $class_prefix ) . '_';
			}

			foreach ( $this->get_schema_type_children( $type_id ) as $child ) {

				if ( ! empty( $exclude_match ) ) {

					if ( preg_match( $exclude_match, $child ) ) {

						continue;
					}
				}

				$css_classes .= ' ' . $class_prefix . SucomUtil::sanitize_css_id( $child );
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

		public function has_json_data_filter( array $mod, $type_url = '' ) {

			$filter_name = $this->get_json_data_filter( $mod, $type_url );

			return empty( $filter_name ) ? false : has_filter( $filter_name );
		}

		public function get_json_data_filter( array $mod, $type_url = '' ) {

			if ( empty( $type_url ) ) {

				$type_url = $this->get_mod_schema_type_url( $mod );
			}

			return 'wpsso_json_data_' . SucomUtil::sanitize_hookname( $type_url );
		}

		/*
		 * Since WPSSO Core v9.2.1.
		 *
		 * Check if Google allows aggregate rarings for this Schema type.
		 */
		public function allow_aggregate_rating( $page_type_id ) {

			foreach ( $this->p->cf[ 'head' ][ 'schema_aggregate_rating_parents' ] as $parent_id ) {

				if ( $this->is_schema_type_child( $page_type_id, $parent_id ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'aggregate rating for schema type ' . $page_type_id . ' is allowed' );
					}

					return true;
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'aggregate rating for schema type ' . $page_type_id . ' not allowed' );
			}

			return false;
		}

		/*
		 * Since WPSSO Core v9.2.1.
		 *
		 * Check if Google allows reviews for this Schema type.
		 */
		public function allow_review( $page_type_id ) {

			foreach ( $this->p->cf[ 'head' ][ 'schema_review_parents' ] as $parent_id ) {

				if ( $this->is_schema_type_child( $page_type_id, $parent_id ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'review for schema type ' . $page_type_id . ' is allowed' );
					}

					return true;
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'review for schema type ' . $page_type_id . ' not allowed' );
			}

			return false;
		}

		/*
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
		public static function get_data_type_id( $json_data, $default_id = false ) {

			$wpsso =& Wpsso::get_instance();

			$type_url = self::get_data_type_url( $json_data );

			return $wpsso->schema->get_schema_type_url_id( $type_url, $default_id );
		}

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

		public static function get_data_context( $json_data ) {

			if ( false !== ( $type_url = self::get_data_type_url( $json_data ) ) ) {

				return self::get_schema_type_context( $type_url );
			}

			return array();
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

		/*
		 * Get the site organization array.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_organization( $mixed = 'current' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$org_opts = array(
				'org_url'         => SucomUtilWP::get_home_url( $wpsso->options, $mixed ),
				'org_name'        => SucomUtilWP::get_site_name( $wpsso->options, $mixed ),
				'org_name_alt'    => SucomUtilWP::get_site_name_alt( $wpsso->options, $mixed ),
				'org_desc'        => SucomUtilWP::get_site_description( $wpsso->options, $mixed ),
				'org_place_id'    => $wpsso->options[ 'site_org_place_id' ],
				'org_schema_type' => $wpsso->options[ 'site_org_schema_type' ],
			);

			/*
			 * Add localized option values.
			 *
			 * Example 'site_org_logo_url:width#fr_FR'.
			 */
			foreach ( array(
				'org_logo_url',
				'org_logo_url:width',
				'org_logo_url:height',
				'org_banner_url',
				'org_banner_url:width',
				'org_banner_url:height',
			) as $opt_key ) {

				$org_opts[ $opt_key ] = SucomUtilOptions::get_key_value( 'site_' . $opt_key, $wpsso->options, $mixed );
			}

			/*
			 * Add sameas option values.
			 */
			$org_opts[ 'org_sameas' ] = array();

			foreach ( WpssoConfig::get_social_accounts() as $social_key => $social_label ) {

				$url = SucomUtilOptions::get_key_value( $social_key, $wpsso->options, $mixed );	// Localized value.

				if ( empty( $url ) ) {	// Nothing to do.

					continue;

				} elseif ( $social_key === 'tc_site' ) {	// Convert X (Twitter) username to a URL.

					$url = 'https://twitter.com/' . preg_replace( '/^@/', '', $url );
				}

				if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {	// Just in case.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping ' . $social_key . ': url "' . $url . '" is invalid' );
					}

				} else {

					$org_opts[ 'org_sameas' ][] = $url;
				}
			}

			return $org_opts;
		}

		public static function add_author_coauthors_data( &$json_data, $mod, $user_id = false ) {

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

					$wpsso->debug->log( 'exiting early: user id is empty or "none"' );
				}

				return 0;	// Return count of authors and coauthors added.
			}

			/*
			 * Single author.
			 */
			$authors_added += WpssoSchemaSingle::add_person_data( $json_data[ 'author' ], $mod, $user_id, $list_el = false );

			/*
			 * List of contributors / co-authors.
			 */
			if ( ! empty( $mod[ 'post_coauthors' ] ) ) {

				foreach ( $mod[ 'post_coauthors' ] as $author_id ) {

					$coauthors_added += WpssoSchemaSingle::add_person_data( $json_data[ 'contributor' ], $mod, $author_id, $list_el = true );
				}
			}

			return $authors_added + $coauthors_added;	// Return count of authors and coauthors added.
		}

		/*
		 * $user_id is optional and takes precedence over the $mod post_author value.
		 */
		public static function add_comments_data( &$json_data, $post_mod ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$comments_added = 0;

			if ( ! $post_mod[ 'is_post' ] || ! $post_mod[ 'id' ] || ! comments_open( $post_mod[ 'id' ] ) ) {

				return $comments_added;
			}

			$json_data[ 'commentCount' ] = (int) get_comments_number( $post_mod[ 'id' ] );

			/*
			 * Get parent comments.
			 *
			 * The WpssoSchemaSingle::add_comment_data() method will recurse and add the replies (ie. children).
			 */
			if ( get_option( 'page_comments' ) ) {	// "Break comments into pages" option is checked.

				$comment_order  = strtoupper( get_option( 'comment_order' ) );
				$comment_paged  = $post_mod[ 'comment_paged' ] ? $post_mod[ 'comment_paged' ] : 1;		// Get the comment page number.
				$comment_number = get_option( 'comments_per_page' );

			} else {

				$comment_order  = 'DESC';
				$comment_paged  = 1;
				$comment_number = SucomUtil::get_const( 'WPSSO_SCHEMA_COMMENTS_MAX' );
			}

			if ( $comment_number ) {	// 0 disables the addition of comments.

				$get_comment_args = array(
					'post_id' => $post_mod[ 'id' ],
					'status'  => 'approve',
					'parent'  => 0,		// Don't get replies.
					'order'   => $comment_order,
					'orderby' => 'comment_date_gmt',
					'paged'   => $comment_paged,
					'number'  => $comment_number,
				);

				$comments = get_comments( $get_comment_args );

				if ( is_array( $comments ) ) {

					foreach( $comments as $num => $comment_obj ) {

						$comments_added += WpssoSchemaSingle::add_comment_data( $json_data[ 'comment' ], $post_mod, $comment_obj->comment_ID );
					}
				}
			}

			return $comments_added;	// Return count of comments added.
		}

		/*
		 * See WpssoJsonTypeHowTo->filter_json_data_https_schema_org_howto().
		 * See WpssoJsonTypeRecipe->filter_json_data_https_schema_org_recipe().
		 */
		public static function add_howto_steps_data( &$json_data, $mod, $md_opts, $opt_pre = 'schema_howto_step', $prop_name = 'step' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$canonical_url = $wpsso->util->get_canonical_url( $mod );

			$steps = SucomUtil::preg_grep_keys( '/^' . $opt_pre . '_([0-9]+)$/', $md_opts, $invert = false, $replace = '$1' );

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log_arr( 'steps', $steps );
			}

			if ( ! empty( $steps ) ) {

				$section_ref = false;
				$section_pos = 1;

				$step_pos = 1;
				$step_idx = 0;

				/*
				 * $md_val is the section/step name.
				 */
				foreach ( $steps as $num => $md_val ) {

					/*
					 * Maybe get a longer text / description value.
					 */
					$step_text = isset( $md_opts[ $opt_pre . '_text_' . $num ] ) ? $md_opts[ $opt_pre . '_text_' . $num ] : $md_val;

					if ( empty( $md_val ) && empty( $step_text ) ) {	// Just in case.

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping step ' . $num . ': step name and text are empty' );
						}
					}

					/*
					 * Get the section or step anchored URL, if an anchor ID has been provided.
					 */
					$step_anchor_id = empty( $md_opts[ $opt_pre . '_anchor_id_' . $num ] ) ? '' : $md_opts[ $opt_pre . '_anchor_id_' . $num ];
					$step_url       = empty( $step_anchor_id ) ? '' : SucomUtil::append_url_fragment( $canonical_url, $step_anchor_id );

					/*
					 * Get images for the section or step.
					 */
					$step_images = array();

					if ( ! empty( $md_opts[ $opt_pre . '_img_id_' . $num ] ) ) {

						/*
						 * Set reference values for admin notices.
						 */
						if ( is_admin() ) {

							$wpsso->notice->set_ref( $canonical_url, $mod, sprintf( __( 'adding schema %s #%d image', 'wpsso' ),
								$prop_name, $num + 1 ) );
						}

						/*
						 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
						 */
						$mt_images = $wpsso->media->get_mt_opts_images( $md_opts, $size_names = 'schema', $opt_pre . '_img', $num );

						self::add_images_data_mt( $step_images, $mt_images );

						/*
						 * Restore previous reference values for admin notices.
						 */
						if ( is_admin() ) {

							$wpsso->notice->unset_ref( $canonical_url );
						}
					}

					/*
					 * Add a How-To Section.
					 */
					if ( ! empty( $md_opts[ $opt_pre . '_section_' . $num ] ) ) {

						$json_data[ $prop_name ][ $step_idx ] = self::get_schema_type_context( 'https://schema.org/HowToSection',
							array(
								'url'             => $step_url,
								'name'            => $md_val,	// Section name.
								'description'     => $step_text,
								'numberOfItems'   => 0,
								'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
								'itemListElement' => array(),
							)
						);

						if ( $step_images ) {

							$json_data[ $prop_name ][ $step_idx ][ 'image' ] = $step_images;
						}

						$section_ref =& $json_data[ $prop_name ][ $step_idx ];

						$section_pos++;

						$step_pos = 1;

						$step_idx++;

					/*
					 * Add a How-To Step.
					 */
					} else {

						$step_arr = self::get_schema_type_context( 'https://schema.org/HowToStep',
							array(
								'position' => $step_pos,
								'url'      => $step_url,
								'name'     => $md_val,	// Step name.
								'text'     => $step_text,
								'image'    => null,
							)
						);

						if ( ! empty( $step_images ) ) {

							$step_arr[ 'image' ] = $step_images;
						}

						/*
						 * If we have a section, add a new step to the section.
						 */
						if ( false !== $section_ref ) {

							$section_ref[ 'itemListElement' ][] = $step_arr;

							$section_ref[ 'numberOfItems' ] = $step_pos;

						} else {

							$json_data[ $prop_name ][ $step_idx ] = $step_arr;

							$step_idx++;
						}

						$step_pos++;
					}
				}
			}
		}

		/*
		 * Pass a single or two dimension image array in $mt_images.
		 *
		 * Calls WpssoSchemaSingle::add_image_data_mt() to add each single image element.
		 */
		public static function add_images_data_mt( &$json_data, $mt_images, $media_pre = 'og:image', $resize = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$img_added = 0;

			if ( empty( $mt_images ) || ! is_array( $mt_images ) ) {

				return $img_added;	// Return count of images added.
			}

			/*
			 * Maybe convert single image array to array of image arrays.
			 */
			if ( ! isset( $mt_images[ 0 ] ) || ! is_array( $mt_images[ 0 ] ) ) {

				$mt_images = array( $mt_images );
			}

			$resized_pids = array();	// Avoid adding the same image ID more than once.

			foreach ( $mt_images as $mt_single_image ) {

				/*
				 * Get the image ID and create a Schema images array.
				 */
				if ( $resize && $pid = SucomUtil::get_first_mt_media_id( $mt_single_image, $media_pre ) ) {

					if ( empty( $resized_pids[ $pid ] ) ) {	// Skip image IDs already added.

						$resized_pids[ $pid ] = true;

						$mt_resized = $wpsso->media->get_mt_pid_images( $pid, $size_names = 'schema', $mt_pid_pre = 'og' );

						/*
						 * Recurse this method, but make sure $resize is false so we don't re-execute this
						 * section of code (creating an infinite loop).
						 */
						$img_added += self::add_images_data_mt( $json_data, $mt_resized, $media_pre, $resize = false );
					}

				} else {	// No resize or no image ID found.

					$img_added += WpssoSchemaSingle::add_image_data_mt( $json_data, $mt_single_image, $media_pre, $list_el = true );
				}
			}

			return $img_added;	// Return count of images added.
		}

		public static function add_item_reviewed_data( &$json_data, $mod, $md_opts ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			if ( self::is_valid_key( $md_opts, 'schema_review_item_type' ) ) {	// Not null, an empty string, or 'none'.

				$type_id = $md_opts[ 'schema_review_item_type' ];

			} else {

				$type_id = 'thing';
			}

			$type_url = $wpsso->schema->get_schema_type_url( $type_id );

			if ( ! $wpsso->schema->allow_review( $type_id ) ) {

				list( $type_context, $type_name, $type_path ) = $wpsso->schema->get_schema_type_url_parts( $type_url );

				$notice_msg = sprintf( __( 'Please note that although the Schema standard allows the subject of a review to be any Schema type, <a href="%1$s">Google does not allow reviews for the Schema %2$s type</a>.', 'wpsso' ), 'https://developers.google.com/search/docs/data-types/review-snippet', $type_name ) . ' ';

				$wpsso->notice->warn( $notice_msg );
			}

			$json_data = self::get_schema_type_context( $type_url, $json_data );

			self::add_data_itemprop_from_assoc( $json_data, $md_opts, array(
				'url'         => 'schema_review_item_url',
				'name'        => 'schema_review_item_name',
				'description' => 'schema_review_item_desc',
			) );

			foreach ( SucomUtil::preg_grep_keys( '/^schema_review_item_sameas_url_[0-9]+$/', $md_opts ) as $url ) {

				$json_data[ 'sameAs' ][] = SucomUtil::esc_url_encode( $url );
			}

			self::check_prop_value_sameas( $json_data );

			/*
			 * Set reference values for admin notices.
			 */
			if ( is_admin() ) {

				$canonical_url = $wpsso->util->get_canonical_url( $mod );

				$wpsso->util->maybe_set_ref( $canonical_url, $mod, __( 'adding reviewed subject image', 'wpsso' ) );
			}

			/*
			 * Add the item images.
			 *
			 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
			 */
			$mt_images = $wpsso->media->get_mt_opts_images( $md_opts, $size_names = 'schema', $img_prefix = 'schema_review_item_img' );

			self::add_images_data_mt( $json_data[ 'image' ], $mt_images );

			if ( empty( $json_data[ 'image' ] ) ) {

				unset( $json_data[ 'image' ] );	// Prevent null assignment.

			} elseif ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( $json_data[ 'image' ] );
			}

			/*
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {

				$wpsso->util->maybe_unset_ref( $canonical_url );
			}

			/*
			 * Item Reviewed: Creative Work
			 */
			if ( $wpsso->schema->is_schema_type_child( $type_id, 'creative.work' ) ) {

				/*
				 * The author type value should be either 'organization' or 'person'.
				 */
				if ( self::is_valid_key( $md_opts, 'schema_review_item_cw_author_type' ) ) {	// Not null, an empty string, or 'none'.

					$author_type_url = $wpsso->schema->get_schema_type_url( $md_opts[ 'schema_review_item_cw_author_type' ] );

					$json_data[ 'author' ] = self::get_schema_type_context( $author_type_url );

					self::add_data_itemprop_from_assoc( $json_data[ 'author' ], $md_opts, array(
						'name' => 'schema_review_item_cw_author_name',
					) );

					if ( ! empty( $md_opts[ 'schema_review_item_cw_author_url' ] ) ) {

						$json_data[ 'author' ][ 'sameAs' ][] = SucomUtil::esc_url_encode( $md_opts[ 'schema_review_item_cw_author_url' ] );
					}
				}

				/*
				 * Subject Published Date.
				 *
				 * Add the creative work published date, if one is available.
				 */
				if ( $date = self::get_opts_date_iso( $md_opts, 'schema_review_item_cw_pub' ) ) {

					$json_data[ 'datePublished' ] = $date;
				}

				/*
				 * Subject Created Date.
				 *
				 * Add the creative work created date, if one is available.
				 */
				if ( $date = self::get_opts_date_iso( $md_opts, 'schema_review_item_cw_created' ) ) {

					$json_data[ 'dateCreated' ] = $date;
				}

				/*
				 * Item Reviewed: Creative Work > Book
				 */
				if ( $wpsso->schema->is_schema_type_child( $type_id, 'book' ) ) {

					self::add_data_itemprop_from_assoc( $json_data, $md_opts, array(
						'isbn' => 'schema_review_item_cw_book_isbn',
					) );

				/*
				 * Item Reviewed: Creative Work > Movie
				 */
				} elseif ( $wpsso->schema->is_schema_type_child( $type_id, 'movie' ) ) {

					/*
					 * Property:
					 * 	actor (supersedes actors)
					 */
					self::add_person_names_data( $json_data, $prop_name = 'actor', $md_opts, 'schema_review_item_cw_movie_actor_person_name' );

					/*
					 * Property:
					 * 	director
					 */
					self::add_person_names_data( $json_data, $prop_name = 'director', $md_opts, 'schema_review_item_cw_movie_director_person_name' );

				/*
				 * Item Reviewed: Creative Work > Software Application
				 */
				} elseif ( $wpsso->schema->is_schema_type_child( $type_id, 'software.application' ) ) {

					self::add_data_itemprop_from_assoc( $json_data, $md_opts, array(
						'applicationCategory'  => 'schema_review_item_software_app_cat',
						'operatingSystem'      => 'schema_review_item_software_app_os',
					) );

					$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );

					foreach ( range( 0, $metadata_offers_max - 1, 1 ) as $key_num ) {

						$offer_opts = SucomUtil::preg_grep_keys( '/^schema_review_item_software_app_(offer_.*)_' . $key_num. '$/',
							$md_opts, $invert = false, $replace = '$1' );

						/*
						 * Must have at least an offer name and price.
						 *
						 * Values cannot be null, an empty string, or 'none'.
						 */
						if ( self::is_valid_key( $offer_opts, 'offer_name' ) && self::is_valid_key( $offer_opts, 'offer_price' ) ) {

							if ( false !== ( $offer = self::get_data_itemprop_from_assoc( $offer_opts, array(
								'name'          => 'offer_name',
								'price'         => 'offer_price',
								'priceCurrency' => 'offer_currency',
								'availability'  => 'offer_avail',	// In stock, Out of stock, Pre-order, etc.
							) ) ) ) {

								/*
								 * Avoid Google validator warnings.
								 */
								$offer[ 'url' ]             = $json_data[ 'url' ];
								$offer[ 'priceValidUntil' ] = gmdate( 'c', time() + MONTH_IN_SECONDS );

								/*
								 * Add the offer.
								 */
								$json_data[ 'offers' ][] = self::get_schema_type_context( 'https://schema.org/Offer', $offer );
							}
						}
					}
				}

			/*
			 * Item Reviewed: Place
			 */
			} elseif ( $wpsso->schema->is_schema_type_child( $type_id, 'place' ) ) {

				/*
				 * Property:
				 *	address as https://schema.org/PostalAddress
				 */
				$postal_address = array();

				if ( self::add_data_itemprop_from_assoc( $postal_address, $md_opts, array(
					'streetAddress'       => 'schema_review_item_place_street_address',
					'postOfficeBoxNumber' => 'schema_review_item_place_po_box_number',
					'addressLocality'     => 'schema_review_item_place_city',
					'addressRegion'       => 'schema_review_item_place_region',
					'postalCode'          => 'schema_review_item_place_postal_code',
					'addressCountry'      => 'schema_review_item_place_country',	// Alpha2 country code.
				) ) ) {

					$json_data[ 'address' ] = self::get_schema_type_context( 'https://schema.org/PostalAddress', $postal_address );
				}

				if ( $wpsso->schema->is_schema_type_child( $type_id, 'local.business' ) ) {

					self::add_data_itemprop_from_assoc( $json_data, $md_opts, array(
						'priceRange' => 'schema_review_item_place_price_range',
					) );

					if ( $wpsso->schema->is_schema_type_child( $type_id, 'food.establishment' ) ) {

						self::add_data_itemprop_from_assoc( $json_data, $md_opts, array(
							'servesCuisine' => 'schema_review_item_place_cuisine',
						) );
					}
				}

			/*
			 * Item Reviewed: Product
			 */
			} elseif ( $wpsso->schema->is_schema_type_child( $type_id, 'product' ) ) {

				self::add_data_itemprop_from_assoc( $json_data, $md_opts, array(
					'sku'  => 'schema_review_item_product_retailer_part_no',
					'mpn'  => 'schema_review_item_product_mfr_part_no',
				) );

				/*
				 * Add the product brand.
				 */
				$single_brand = self::get_data_itemprop_from_assoc( $md_opts, array(
					'name' => 'schema_review_item_product_brand',
				) );

				if ( false !== $single_brand ) {	// Just in case.

					$json_data[ 'brand' ] = self::get_schema_type_context( 'https://schema.org/Brand', $single_brand );
				}

				$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );

				foreach ( range( 0, $metadata_offers_max - 1, 1 ) as $key_num ) {

					$offer_opts = SucomUtil::preg_grep_keys( '/^schema_review_item_product_(offer_.*)_' . $key_num. '$/',
						$md_opts, $invert = false, $replace = '$1' );

					/*
					 * Must have at least an offer name and price.
					 *
					 * Values cannot be null, an empty string, or 'none'.
					 */
					if ( self::is_valid_key( $offer_opts, 'offer_name' ) && self::is_valid_key( $offer_opts, 'offer_price' ) ) {

						if ( false !== ( $offer = self::get_data_itemprop_from_assoc( $offer_opts, array(
							'name'          => 'offer_name',
							'price'         => 'offer_price',
							'priceCurrency' => 'offer_currency',
							'availability'  => 'offer_avail',	// In stock, Out of stock, Pre-order, etc.
						) ) ) ) {

							/*
							 * Add the offer.
							 */
							$json_data[ 'offers' ][] = self::get_schema_type_context( 'https://schema.org/Offer', $offer );
						}
					}
				}
			}
		}

		/*
		 * See WpssoJsonTypeItemList->filter_json_data_https_schema_org_itemlist().
		 */
		public static function add_itemlist_data( &$json_data, array $mod, array $mt_og, $page_type_id, $is_main ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$prop_name = 'itemListElement';

			$item_count = isset( $json_data[ $prop_name ] ) ? count( $json_data[ $prop_name ] ) : 0;

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( $prop_name . ' property provided ' . $item_count . ' elements' );
			}

			if ( empty( $page_type_id ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: page type id is empty and required' );
				}

				return $item_count;
			}

			/*
			 * Create markup specifically for the Schema ItemList type (not its sub-types like breadcrumb.list,
			 * howto.section, howto.step, or offer.catalog).
			 */
			if ( 'item.list' === $page_type_id ) {

				$json_data[ 'itemListOrder' ] = 'https://schema.org/ItemListUnordered';

				if ( isset( $mod[ 'query_vars' ][ 'order' ] ) ) {

					switch ( $mod[ 'query_vars' ][ 'order' ] ) {

						case 'ASC':

							$json_data[ 'itemListOrder' ] = 'https://schema.org/ItemListOrderAscending';

							break;

						case 'DESC':

							$json_data[ 'itemListOrder' ] = 'https://schema.org/ItemListOrderDescending';

							break;
					}
				}

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'calling page_posts_mods()' );
				}

				$page_posts_mods = $wpsso->page->get_posts_mods( $mod );

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

					$post_canonical_url = $wpsso->util->get_canonical_url( $post_mod );

					$post_json_data = self::get_schema_type_context( 'https://schema.org/ListItem', array(
						'position' => $item_count,
						'url'      => $post_canonical_url,
					) );

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'adding post ID ' . $post_mod[ 'id' ] . ' to ' . $prop_name . ' as #' . $item_count );
					}

					$json_data[ $prop_name ][] = $post_json_data;	// Add the post data.
				}

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_json_prop_https_schema_org_' . $prop_name );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'applying filters "' . $filter_name . '"' );
				}

				$json_data[ $prop_name ] = apply_filters( $filter_name, $json_data[ $prop_name ], $mod, $mt_og, $page_type_id, $is_main );

				WpssoSchema::check_required_props( $json_data, $mod, array( $prop_name ), $page_type_id );
			}

			return $item_count;
		}

		/*
		 * $mt_og can be the main webpage open graph array or a product $mt_offer array.
		 *
		 * $size_names can be null, a string, or an array.
		 *
		 * $add_video can be true, false, or a string (property name).
		 */
		public static function add_media_data( &$json_data, $mod, $mt_og, $size_names = 'schema', $add_video = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$img_added = 0;
			$max_nums  = $wpsso->util->get_max_nums( $mod, 'og' );
			$mt_images = $wpsso->media->get_all_images( $max_nums[ 'og_img_max' ], $size_names, $mod, $md_pre = array( 'schema', 'og' ) );

			if ( ! empty( $mt_images ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding images to json data' );
				}

				$img_added = self::add_images_data_mt( $json_data[ 'image' ], $mt_images );
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( $img_added . ' images added' );
			}

			/*
			 * Allow the video property to be skipped - some schema types (organization, for example) do not include a video property.
			 */
			if ( $add_video ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding all video(s)' );
				}

				$vid_added = 0;
				$vid_prop  = is_string( $add_video ) ? $add_video : 'video';

				if ( ! empty( $mt_og[ 'og:video' ] ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'adding videos to json data "' . $vid_prop . '" property' );
					}

					$vid_added = self::add_videos_data_mt( $json_data[ $vid_prop ], $mt_og[ 'og:video' ], 'og:video' );
				}

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $vid_added . ' videos added to "' . $vid_prop . '" property' );
				}

			} elseif ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'skipping videos: add_video argument is false' );
			}

			/*
			 * Redefine mainEntityOfPage property for Attachment pages.
			 *
			 * If this is an attachment page, and the post mime_type is a known media type (image, video, or audio),
			 * then set the first media array element mainEntityOfPage to the page url, and set the page
			 * mainEntityOfPage property to false (so it doesn't get defined later).
			 */
			$main_prop = $mod[ 'is_attachment' ] ? $mod[ 'post_mime_group' ] : '';

			$main_prop = apply_filters( 'wpsso_json_media_main_prop', $main_prop, $mod );

			if ( ! empty( $main_prop ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' "' . $main_prop . '" property is main entity' );
				}

				if ( empty( $json_data[ $main_prop ] ) || ! is_array( $json_data[ $main_prop ] ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' "' . $main_prop . '" property is empty or not an array' );
					}

				} else {

					reset( $json_data[ $main_prop ] );

					$media_key = key( $json_data[ $main_prop ] );	// Media array key should be '0'.

					if ( ! isset( $json_data[ $main_prop ][ $media_key ][ 'mainEntityOfPage' ] ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'mainEntityOfPage for "' . $main_prop . '" key ' . $media_key . ' = ' . $mt_og[ 'og:url' ] );
						}

						$json_data[ $main_prop ][ $media_key ][ 'mainEntityOfPage' ] = $mt_og[ 'og:url' ];

					} elseif ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'mainEntityOfPage for "' . $main_prop . '" key ' . $media_key . ' already defined' );
					}

					$json_data[ 'mainEntityOfPage' ] = false;
				}
			}
		}

		public static function add_offers_aggregate_data_mt( &$json_data, array $mt_offers ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$aggr_added  = 0;
			$aggr_prices = array();
			$aggr_offers = array();
			$aggr_common = array();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'adding ' . count( $mt_offers ) . ' offers as AggregateOffer' );
			}

			foreach ( $mt_offers as $num => $mt_offer ) {

				if ( ! is_array( $mt_offer ) ) {	// Just in case.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping offer #' . $num . ': not an array' );
					}

					continue;
				}

				if ( ! $mod = $wpsso->og->get_product_retailer_item_mod( $mt_offer ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping offer #' . $num . ': invalid retailer item id' );
					}

					continue;
				}

				$single_offer = WpssoSchemaSingle::get_offer_data( $mod, $mt_offer, $def_type_id = 'offer' );

				if ( empty( $single_offer[ 'priceCurrency' ] ) ) {	// Just in case.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping offer #' . $num . ': missing price currency' );
					}

					continue;
				}

				/*
				 * Keep track of the lowest and highest price by currency.
				 */
				$price_currency = $single_offer[ 'priceCurrency' ];	// Shortcut variable.

				if ( isset( $single_offer[ 'price' ] ) ) {	// Just in case.

					if ( ! isset( $aggr_prices[ $price_currency ][ 'lowPrice' ] ) ||
						$aggr_prices[ $price_currency ][ 'lowPrice' ] > $single_offer[ 'price' ] ) {

						$aggr_prices[ $price_currency ][ 'lowPrice' ] = $single_offer[ 'price' ];
					}

					if ( ! isset( $aggr_prices[ $price_currency ][ 'highPrice' ] ) ||
						$aggr_prices[ $price_currency ][ 'highPrice' ] < $single_offer[ 'price' ] ) {

						$aggr_prices[ $price_currency ][ 'highPrice' ] = $single_offer[ 'price' ];
					}
				}

				/*
				 * Save common properties (by currency) to include in the AggregateOffer markup.
				 */
				if ( 0 === $num ) {

					foreach ( preg_grep( '/^[^@]/', array_keys( $single_offer ) ) as $key ) {

						$aggr_common[ $price_currency ][ $key ] = $single_offer[ $key ];
					}

				} elseif ( ! empty( $aggr_common[ $price_currency ] ) ) {

					foreach ( $aggr_common[ $price_currency ] as $key => $val ) {

						if ( ! isset( $single_offer[ $key ] ) ) {

							unset( $aggr_common[ $price_currency ][ $key ] );

						} elseif ( $val !== $single_offer[ $key ] ) {

							unset( $aggr_common[ $price_currency ][ $key ] );
						}
					}
				}

				/*
				 * Add the complete offer.
				 */
				$aggr_offers[ $price_currency ][] = $single_offer;
			}

			/*
			 * Add aggregate offers grouped by currency.
			 */
			foreach ( $aggr_offers as $price_currency => $currency_offers ) {

				if ( ( $offer_count = count( $currency_offers ) ) > 0 ) {

					$offer_group = array();

					/*
					 * Maybe set the 'lowPrice' and 'highPrice' properties.
					 */
					foreach ( array( 'lowPrice', 'highPrice' ) as $price_mark ) {

						if ( isset( $aggr_prices[ $price_currency ][ $price_mark ] ) ) {

							$offer_group[ $price_mark ] = $aggr_prices[ $price_currency ][ $price_mark ];
						}
					}

					$offer_group[ 'priceCurrency' ] = $price_currency;

					if ( ! empty( $aggr_common[ $price_currency ] ) ) {

						foreach ( $aggr_common[ $price_currency ] as $key => $val ) {

							$offer_group[ $key ] = $val;
						}
					}

					$offer_group[ 'offerCount' ] = $offer_count;

					$offer_group[ 'offers' ] = $currency_offers;

					$json_data[ 'offers' ][] = self::get_schema_type_context( 'https://schema.org/AggregateOffer', $offer_group );

					$aggr_added++;
				}
			}

			return $aggr_added;
		}

		public static function add_offers_data_mt( &$json_data, array $mt_offers ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$offers_added  = 0;

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'adding ' . count( $mt_offers ) . ' offers as Offer' );
			}

			foreach ( $mt_offers as $num => $mt_offer ) {

				if ( ! is_array( $mt_offer ) ) {	// Just in case.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping offer #' . $num . ': not an array' );
					}

					continue;
				}

				if ( ! $mod = $wpsso->og->get_product_retailer_item_mod( $mt_offer ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping offer #' . $num . ': invalid retailer item id' );
					}

					continue;
				}

				$single_offer = WpssoSchemaSingle::get_offer_data( $mod, $mt_offer, $def_type_id = 'offer' );

				if ( false === $single_offer ) {

					continue;
				}

				$json_data[ 'offers' ][] = $single_offer;

				$offers_added++;
			}

			return $offers_added;
		}

		/*
		 * Called by the Blog, CollectionPage, ProfilePage, and SearchResultsPage filters.
		 *
		 * Example:
		 *
		 *	$prop_type_ids = array( 'mentions' => false )
		 *	$prop_type_ids = array( 'blogPosting' => 'blog.posting' )
		 *
		 * Do not cast $prop_type_ids as an array to allow for backwards compatibility.
		 */
		public static function add_posts_data( &$json_data, array $mod, array $mt_og, $page_type_id, $is_main, $prop_type_ids ) {

			static $added_page_type_ids = array();

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$added_count = 0;	// Initialize the total posts added counter.

			if ( ! is_array( $prop_type_ids ) && is_array( $deprecated ) ) {

				$prop_type_ids = $deprecated;

				$deprecated = null;
			}

			/*
			 * Sanity checks.
			 */
			if ( empty( $page_type_id ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: page type id is empty' );
				}

				return $added_count;

			} elseif ( empty( $prop_type_ids ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: prop type ids is empty' );
				}

				return $added_count;
			}

			/*
			 * Prevent recursion - i.e. webpage.collection in webpage.collection, etc.
			 */
			if ( isset( $added_page_type_ids[ $page_type_id ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: preventing recursion of page type id ' . $page_type_id );
				}

				return $added_count;

			} else {

				$added_page_type_ids[ $page_type_id ] = true;
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark( 'adding posts data' );	// Begin timer.
			}

			$page_posts_mods = $wpsso->page->get_posts_mods( $mod );

			if ( empty( $page_posts_mods ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: page posts mods array is empty' );

					$wpsso->debug->mark( 'adding posts data' );	// End timer.
				}

				return $added_count;
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'page_posts_mods array has ' . count( $page_posts_mods ) . ' elements' );
			}

			/*
			 * Set the Schema properties.
			 */
			foreach ( $prop_type_ids as $prop_name => $type_ids ) {

				if ( empty( $type_ids ) ) {		// False or empty array - allow any schema type.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'any schema type is allowed for prop_name ' . $prop_name );
					}

					$type_ids = array( 'any' );

				} elseif ( is_string( $type_ids ) ) {	// Convert value to an array.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'only schema type ' . $type_ids . ' allowed for prop_name ' . $prop_name );
					}

					$type_ids = array( $type_ids );

				} elseif ( ! is_array( $type_ids ) ) {

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

					$post_type_id = $wpsso->schema->get_mod_schema_type_id( $post_mod );

					$add_post_data = false;

					foreach ( $type_ids as $family_member_id ) {

						if ( 'any' === $family_member_id ) {

							if ( $wpsso->debug->enabled ) {

								$wpsso->debug->log( 'accepting post ID ' . $post_mod[ 'id' ] . ': any schema type is allowed' );
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

								$wpsso->debug->log( 'accepting post ID ' . $post_mod[ 'id' ] . ': ' .
									$post_type_id . ' is child of ' . $family_member_id );
							}

							$add_post_data = true;

							break;	// One positive match is enough.

						} elseif ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'post ID ' . $post_mod[ 'id' ] . ' schema type ' .
								$post_type_id . ' not a child of ' . $family_member_id );
						}
					}

					if ( ! $add_post_data ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping post ID ' . $post_mod[ 'id' ] . ' for prop_name ' . $prop_name );
						}

						continue;
					}

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'getting single mod data for post ID ' . $post_mod[ 'id' ] );
					}

					$post_json_data = $wpsso->schema->get_mod_json_data( $post_mod );

					if ( empty( $post_json_data ) ) {	// Prevent null assignment.

						$wpsso->debug->log( 'single mod data for post ID ' . $post_mod[ 'id' ] . ' is empty' );

						continue;	// Get the next post mod.
					}

					$added_count++;

					$prop_count++;

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'adding post ID ' . $post_mod[ 'id' ] . ' to ' . $prop_name . ' as #' . $prop_count );
					}

					$json_data[ $prop_name ][] = $post_json_data;	// Add the post data.
				}

				$filter_name = SucomUtil::sanitize_hookname( 'wpsso_json_prop_https_schema_org_' . $prop_name );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'applying filters "' . $filter_name . '"' );
				}

				$json_data[ $prop_name ] = apply_filters( $filter_name, $json_data[ $prop_name ], $mod, $mt_og, $page_type_id, $is_main );
			}

			unset( $added_page_type_ids[ $page_type_id ] );

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark( 'adding posts data' );	// End timer.
			}

			return $added_count;
		}

		public static function add_person_names_data( &$json_data, $prop_name, array $assoc, $assoc_key = '' ) {

			if ( ! empty( $prop_name ) && ! empty( $assoc_key ) ) {

				foreach ( SucomUtil::preg_grep_keys( '/^' . $assoc_key .'_[0-9]+$/', $assoc ) as $value ) {

					if ( ! empty( $value ) ) {

						$json_data[ $prop_name ][] = self::get_schema_type_context( 'https://schema.org/Person', array(
							'name' => $value,
						) );
					}
				}
			}
		}

		/*
		 * See WpssoSchemaSingle->add_product_group_data().
		 */
		public static function add_variants_data_mt( &$json_data, array $mt_variants ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$variants_added  = 0;

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'adding ' . count( $mt_variants ) . ' variants as Product' );
			}

			foreach ( $mt_variants as $num => $mt_variant ) {

				if ( ! is_array( $mt_variant ) ) {	// Just in case.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping variant #' . $num . ': not an array' );
					}

					continue;
				}

				if ( ! $mod = $wpsso->og->get_product_retailer_item_mod( $mt_variant ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'skipping variant #' . $num . ': invalid retailer item id' );
					}

					continue;
				}

				$single_variant = WpssoSchemaSingle::get_product_data( $mod, $mt_variant, $def_type_id = 'product' );

				if ( false === $single_variant ) {

					continue;
				}

				$json_data[ 'hasVariant' ][] = $single_variant;

				$variants_added++;
			}

			return $variants_added;
		}

		/*
		 * Provide a single or two-dimension video array in $mt_videos.
		 */
		public static function add_videos_data_mt( &$json_data, $mt_videos, $media_pre = 'og:video' ) {

			$videos_added = 0;

			if ( isset( $mt_videos[ 0 ] ) && is_array( $mt_videos[ 0 ] ) ) {	// 2 dimensional array.

				foreach ( $mt_videos as $mt_single_video ) {

					$videos_added += WpssoSchemaSingle::add_video_data_mt( $json_data, $mt_single_video, $media_pre, $list_el = true );
				}

			} elseif ( is_array( $mt_videos ) ) {

				$videos_added += WpssoSchemaSingle::add_video_data_mt( $json_data, $mt_videos, $media_pre, $list_el = true );
			}

			return $videos_added;	// return count of videos added
		}

		/*
		 * Return any third-party and custom post options for a given type and type ID.
		 *
		 * See wpsso_get_post_event_options() in lib/functions.php
		 * See wpsso_get_post_job_options() in lib/functions.php
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

			} else return false;

			$filter_name = SucomUtil::sanitize_hookname( 'wpsso_get_' . $type . '_options' );
			$type_opts   = apply_filters( $filter_name, false, $mod, $type_id );

			if ( ! empty( $type_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'get_' . $type . '_options filters returned', $type_opts );
				}
			}

			/*
			 * Add metadata defaults and custom values to the $type_opts array.
			 *
			 * $type_opts can be false, an empty array, or an array of one or more options.
			 */
			self::add_type_opts_md_pad( $type_opts, $mod, array( $type => 'schema_' . $type ) );

			return $type_opts;
		}

		/*
		 * Add metadata defaults and custom values to the $type_opts array.
		 *
		 * $type_opts can be false, an empty array, or an array of one or more options.
		 */
		public static function add_type_opts_md_pad( &$type_opts, array $mod, array $opts_md_pre = array() ) {

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$md_defs = $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] );
				$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( empty( $opts_md_pre ) ) {	// Nothing to rename.

					$type_opts = array_merge( $md_defs, $md_opts );

				} else foreach ( $opts_md_pre as $opt_key => $md_pre ) {

					$md_defs = SucomUtil::preg_grep_keys( '/^' . $md_pre . '_/', $md_defs, $invert = false, $opt_key . '_' );
					$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_pre . '_/', $md_opts, $invert = false, $opt_key . '_' );

					if ( is_array( $type_opts ) ) {

						$type_opts = array_merge( $md_defs, $type_opts, $md_opts );

					} else $type_opts = array_merge( $md_defs, $md_opts );
				}
			}
		}

		/*
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

		/*
		 * See WpssoMedia->get_all_videos().
		 */
		public static function get_mod_date_iso( array $mod, $md_pre, $def_date = null ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			if ( ! is_string( $md_pre ) ) {	// Just in case.

				return '';
			}

			$md_opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

			if ( ! empty( $def_date ) ) {

				$def_opts = array();

				self::add_date_time_timezone_opts( $def_date, $def_opts, $md_pre );

				$md_opts = array_merge( $def_opts, $md_opts );
			}

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

			$md_date     = empty( $opts[ $md_pre . '_date' ] ) || 'none' === $opts[ $md_pre . '_date' ] ? '' : $opts[ $md_pre . '_date' ];
			$md_time     = empty( $opts[ $md_pre . '_time' ] ) || 'none' === $opts[ $md_pre . '_time' ] ? '' : $opts[ $md_pre . '_time' ];
			$md_timezone = empty( $opts[ $md_pre . '_timezone' ] ) || 'none' === $opts[ $md_pre . '_timezone' ] ? '' : $opts[ $md_pre . '_timezone' ];

			if ( empty( $md_date ) ) {		// No date or time.

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: ' . $md_pre . ' date is empty' );
				}

				return '';	// Nothing to do.
			}

			if ( empty( $md_time ) ) {	// Date with no time.

				$md_time = '00:00';

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $md_pre . ' time is empty: using time ' . $md_time );
				}

			}

			if ( empty( $md_timezone ) ) {				// No timezone.

				$md_timezone = $wpsso->get_options( 'og_def_timezone', 'UTC' );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $md_pre . ' timezone is empty: using timezone ' . $md_timezone );
				}
			}

			$date_obj = date_create( $md_date . ' ' . $md_time . ' ' . $md_timezone );

			return date_format( $date_obj, 'c' );
		}

		public static function add_date_time_timezone_opts( $date, array &$opts, $md_pre ) {	// Pass by reference is OK.

			$opts[ $md_pre . '_date' ]     = '';
			$opts[ $md_pre . '_time' ]     = '';
			$opts[ $md_pre . '_timezone' ] = '';

			if ( $date ) {

				$date_obj = date_create( $date );

				if ( $date_obj ) {

					/*
					 * See https://www.php.net/manual/en/datetime.format.php.
					 */
					$opts[ $md_pre . '_date' ]     = date_format( $date_obj, 'Y-m-d' );
					$opts[ $md_pre . '_time' ]     = date_format( $date_obj, 'H:i' );
					$opts[ $md_pre . '_timezone' ] = date_format( $date_obj, 'P' );
				}
			}
		}

		/*
		 * Example $prop_names array:
		 *
		 * array(
		 * 	'prepTime'  => 'schema_recipe_prep',
		 * 	'cookTime'  => 'schema_recipe_cook',
		 * 	'totalTime' => 'schema_recipe_total',
		 * );
		 */
		public static function add_data_time_from_assoc( array &$json_data, array $assoc, array $prop_names ) {

			foreach ( $prop_names as $prop_name => $assoc_key ) {

				$t = array();

				foreach ( array( 'days', 'hours', 'mins', 'secs' ) as $time_incr ) {

					$t[ $time_incr ] = empty( $assoc[ $assoc_key . '_' . $time_incr ] ) ?	// 0 or empty string.
						0 : (int) $assoc[ $assoc_key . '_' . $time_incr ];		// Define as 0 by default.
				}

				if ( $t[ 'days' ] . $t[ 'hours' ] . $t[ 'mins' ] . $t[ 'secs' ] > 0 ) {

					$json_data[ $prop_name ] = 'P' . $t[ 'days' ] . 'DT' . $t[ 'hours' ] . 'H' . $t[ 'mins' ] . 'M' . $t[ 'secs' ] . 'S';
				}
			}
		}

		/*
		 * Returns a https://schema.org/unitText value ('cm', 'ml', or 'kg').
		 */
		public static function get_unit_text( $mixed_key ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $mixed_key ] ) ) {

				return $local_cache[ $mixed_key ];
			}

			$match_key    = null;
			$schema_units = WpssoConfig::get_schema_units();	// Uses a local cache.

			if ( is_array( $schema_units ) ) {	// Just in case.

				if ( isset( $schema_units[ $mixed_key ] ) ) {

					$match_key = $mixed_key;

				} else {

					$mixed_key = str_replace( ':', '_', $mixed_key );	// Fix for meta tag names.
					$unit_keys = array_keys( $schema_units );

					foreach ( $unit_keys as $unit_key ) {

						if ( false !== strpos( $mixed_key, '_' . $unit_key . '_value' ) ||
							false !== strpos( $mixed_key, '_' . $unit_key . '_units' ) ) {

							$match_key = $unit_key;

							break;
						}
					}
				}
			}

			if ( null !== $match_key ) {

				if ( is_array( $schema_units[ $match_key ] ) ) {	// Just in case.

					foreach ( $schema_units[ $match_key ] as $prop_name => $prop_data ) {

						if ( isset( $prop_data[ 'unitText' ] ) ) {	// Return the first match.

							return $local_cache[ $mixed_key ] = $prop_data[ 'unitText' ];
						}
					}
				}
			}

			return $local_cache[ $mixed_key ] = '';
		}

		/*
		 * Examples $key_map array:
		 *
		 * $key_map = array(
		 * 	'length'       => 'product:length:value',
		 * 	'width'        => 'product:width:value',
		 * 	'height'       => 'product:height:value',
		 * 	'weight'       => 'product:weight:value',
		 * 	'fluid_volume' => 'product:fluid_volume:value',
		 * );
		 *
		 * $key_map = array(
		 *	'width_px'  => 'og:image:width',
		 *	'height_px' => 'og:image:height',
		 * );
		 */
		public static function add_data_unit_from_assoc( array &$json_data, array $assoc, array $key_map ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$schema_units = WpssoConfig::get_schema_units();	// Uses a local cache.

			foreach ( $key_map as $unit_key => $assoc_key ) {

				/*
				 * Make sure the property name we need (width, height, weight, etc.) is configured.
				 */
				if ( empty( $schema_units[ $unit_key ] ) || ! is_array( $schema_units[ $unit_key ] ) ) {

					continue;
				}

				/*
				 * Exclude empty string values.
				 */
				if ( isset( $assoc[ $assoc_key ] ) ) {

					$assoc[ $assoc_key ] = trim( $assoc[ $assoc_key ] );	// Just in case.
				}

				if ( ! isset( $assoc[ $assoc_key ] ) || '' === $assoc[ $assoc_key ] ) {

					continue;
				}

				/*
				 * Example array:
				 *
				 *	$schema_units[ 'depth' ] = array(
				 *		'depth' => array(
				 *			'@context' => 'https://schema.org',
				 *			'@type'    => 'QuantitativeValue',
				 *			'name'     => 'Depth',
				 *			'unitText' => 'cm',
				 *			'unitCode' => 'CMT',
				 *		),
				 *	),
				 */
				foreach ( $schema_units[ $unit_key ] as $prop_name => $prop_data ) {

					$quant_id = 'qv-' . $unit_key . '-' . $assoc[ $assoc_key ];	// Example '@id' = '#sso/qv-width-px-1200'.

					self::update_data_id( $prop_data, $quant_id, '/' );

					$prop_data[ 'value' ] = $assoc[ $assoc_key ];

					$json_data[ $prop_name ][] = $prop_data;
				}
			}
		}

		/*
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
		public static function add_data_itemprop_from_assoc( array &$json_data, array $assoc, array $key_map, $overwrite = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$is_assoc = SucomUtil::is_assoc( $key_map );

			$prop_added = 0;

			foreach ( $key_map as $prop_name => $assoc_key ) {

				if ( ! $is_assoc ) {

					$prop_name = $assoc_key;
				}

				if ( self::is_valid_key( $assoc, $assoc_key ) ) {	// Not null, an empty string, or 'none'.

					if ( isset( $json_data[ $prop_name ] ) && empty( $overwrite ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ': itemprop exists and overwrite is false' );
						}

						continue;

					}

					if ( is_string( $assoc[ $assoc_key ] ) && false !== filter_var( $assoc[ $assoc_key ], FILTER_VALIDATE_URL ) ) {

						$json_data[ $prop_name ] = SucomUtil::esc_url_encode( $assoc[ $assoc_key ] );

					} else $json_data[ $prop_name ] = $assoc[ $assoc_key ];

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'assigned ' . $assoc_key . ' value to itemprop ' .
							$prop_name . ' = ' . print_r( $json_data[ $prop_name ], true ) );
					}

					$prop_added++;
				}
			}

			return $prop_added;
		}

		/*
		 * Since WPSSO Core v8.0.0.
		 *
		 * Checks both the array key and its value.
		 *
		 * The array key must exist, and its value cannot be null, an empty string, or 'none'.
		 *
		 * If the key is a width or height, the value cannot be -1.
		 *
		 * See WpssoJsonTypeMovie->filter_json_data_https_schema_org_movie().
		 * See WpssoSchema::add_item_reviewed_data().
		 * See WpssoSchema::add_data_itemprop_from_assoc().
		 * WpssoSchemaSingle->add_book_data().
		 * WpssoSchemaSingle->add_offer_data().
		 * WpssoSchemaSingle->add_product_data().
		 */
		public static function is_valid_key( $assoc, $key ) {

			if ( ! isset( $assoc[ $key ] ) ) {

				return false;

			} elseif ( ! self::is_valid_val( $assoc[ $key ] ) ) {	// Not null, empty string, or 'none'.

				return false;

			} elseif ( 'width' === $key || 'height' === $key ) {

				if ( WPSSO_UNDEF === $assoc[ $key ] ) {	// Invalid width or height.

					return false;
				}
			}

			return true;
		}

		/*
		 * Since WPSSO Core v8.0.0.
		 *
		 * The value cannot be null, an empty string, or the 'none' string.
		 *
		 * WpssoJsonTypeCreativeWork->filter_json_data_https_schema_org_creativework().
		 * See WpssoSchema::is_valid_key().
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

		/*
		 * Since WPSSO Core v7.7.0.
		 *
		 * See WpssoJsonTypeQAPage->filter_json_data_https_schema_org_qapage().
		 */
		public static function move_data_itemprop_from_assoc( array &$json_data, array &$assoc, array $key_map, $overwrite = true ) {

			$prop_added = self::add_data_itemprop_from_assoc( $json_data, $assoc, $key_map, $overwrite );

			foreach ( $key_map as $prop_name => $assoc_key ) {

				unset( $assoc[ $assoc_key ] );
			}

			return $prop_added;
		}

		/*
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
		public static function get_data_itemprop_from_assoc( array $assoc, array $key_map, $exclude = array( '' ) ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$json_data = array();

			foreach ( $key_map as $prop_name => $assoc_key ) {

				if ( isset( $assoc[ $assoc_key ] ) ) {

					if ( ! in_array( $assoc[ $assoc_key ], $exclude, $strict = true ) ) {

						$json_data[ $prop_name ] = $assoc[ $assoc_key ];

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'assigned ' . $assoc_key . ' value to itemprop ' .
								$prop_name . ' = ' . print_r( $json_data[ $prop_name ], true ) );
						}
					}
				}
			}

			return empty( $json_data ) ? false : $json_data;
		}

		/*
		 * Check for missing Schema property values.
		 *
		 * See WpssoAbstractWpMeta->check_head_info().
		 */
		public static function check_required_props( &$json_data, array $mod, $prop_names = array( 'image' ), $type_id = null ) {

			if ( ! $mod[ 'id' ] || ! $mod[ 'is_public' ] ) {

				return;

			} elseif ( $mod[ 'is_post' ] && 'publish' !== $mod[ 'post_status' ] ) {

				return;
			}

			/*
			 * The post, term, or user has an ID, is public, and (in the case of a post) the post status is published.
			 */
			$wpsso =& Wpsso::get_instance();

			$ref_url = $wpsso->util->maybe_set_ref( $canonical_url = null, $mod, __( 'checking schema properties', 'wpsso' ) );

			list( $type_context, $type_name, $type_path ) = $wpsso->schema->get_schema_type_url_parts_by_id( $type_id );

			foreach ( $prop_names as $prop_name ) {

				if ( empty( $json_data[ $prop_name ] ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( $prop_name . ' property value is empty and required' );
					}

					/*
					 * An is_admin() test is required to make sure the WpssoMessages class is available.
					 */
					if ( $wpsso->notice->is_admin_pre_notices() ) {

						$notice_msg = $wpsso->msgs->get( 'notice-missing-schema-' . $prop_name, array( 'type_name' => $type_name ) );
						$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-schema-' . $prop_name;

						if ( ! empty( $notice_msg ) ) {	// Just in case.

							$wpsso->notice->err( $notice_msg, null, $notice_key );
						}
					}
				}
			}

			$wpsso->util->maybe_unset_ref( $ref_url );
		}

		/*
		 * Convert a numeric category ID to its Google category string.
		 */
		public static function check_prop_value_category( &$json_data, $prop_name = 'category' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking category property value' );
			}

			if ( ! empty( $json_data[ $prop_name ] ) ) {

				/*
				 * Category IDs are expected to be numeric Google category IDs.
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

		public static function get_enumeration_examples( $enum_key, $val_prefix = '', $val_suffix = '' ) {

			$wpsso =& Wpsso::get_instance();

			$values = array();

			if ( empty( $wpsso->cf[ 'form' ][ $enum_key ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $enum_key . ' enumeration key is unknown' );
				}

			} else {

				$enumerations = $wpsso->cf[ 'form' ][ $enum_key ];

				unset( $enumerations[ 'none' ] );

				/*
				 * Include values without their comment / qualifier (for example, 'Adult (13 years old or more)').
				 */
				foreach ( $enumerations as $key => $val ) {

					if ( false !== ( $pos = strpos( $val, '(' ) ) ) {

						$enumerations[ $key ] = trim( substr( $val, 0, $pos ) );
					}
				}

				$enums_transl = SucomUtilOptions::get_opts_values_transl( $enumerations, $text_domain = 'wpsso' );

				foreach ( $enumerations as $key => $val ) {

					$values[ $val ] = $val;

					if ( false !== ( $pos = strpos( $key, 'https://schema.org/' ) ) ) {

						$key = substr( $key, $pos + strlen( 'https://schema.org/' ) );
					}

					if ( $val_prefix && false !== ( $pos = strpos( $key, $val_prefix ) ) ) {

						$key = substr( $key, $pos + strlen( $val_prefix ) );
					}

					if ( $val_suffix && false !== ( $pos = strpos( $key, $val_suffix ) ) ) {

						$len_prefix = strlen( $key ) - strlen( $val_suffix );

						if ( $pos === $len_prefix ) {

							$key = substr( $key, 0, $len_prefix );
						}
					}

					$values[ $key ] = $key;
				}

				SucomUtil::natasort( $values );
			}

			return $values;
		}

		/*
		 * See WpssoSchema->filter_sanitize_md_options().
		 */
		public static function check_prop_value_enumeration( &$json_data, $prop_name, $enum_key, $val_prefix = '', $val_suffix = '' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking ' . $prop_name . ' property value' );
			}

			if ( ! isset( $json_data[ $prop_name ] ) || ( empty( $json_data[ $prop_name ] ) && ! is_numeric( $json_data[ $prop_name ] ) ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( $prop_name . ' property value is empty (and not numeric)' );
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

				$enumerations = $wpsso->cf[ 'form' ][ $enum_key ];

				/*
				 * Include values without their comment / qualifier (for example, 'Adult (13 years old or more)').
				 */
				foreach ( $enumerations as $key => $val ) {

					if ( false !== ( $pos = strpos( $val, '(' ) ) ) {

						$enumerations[ $key ] = trim( substr( $val, 0, $pos ) );
					}
				}

				$enums_labels = array_flip( $enumerations );
				$enums_transl = array_flip( SucomUtilOptions::get_opts_values_transl( $enumerations, $text_domain = 'wpsso' ) );
				$prop_val     = $json_data[ $prop_name ];	// Example: 'New' or 'new'.

				if ( ! isset( $enumerations[ $prop_val ] ) ) {

					if ( isset( $enumerations[ $prop_val ] ) ) {

						$json_data[ $prop_name ] = $prop_val;

					} elseif ( isset( $enums_labels[ $prop_val ] ) ) {

						$json_data[ $prop_name ] = $enums_labels[ $prop_val ];

					} elseif ( isset( $enums_transl[ $prop_val ] ) ) {

						$json_data[ $prop_name ] = $enums_transl[ $prop_val ];

					} elseif ( isset( $enumerations[ 'https://schema.org/' . $prop_val ] ) ) {

						$json_data[ $prop_name ] = 'https://schema.org/' . $prop_val;

					} elseif ( isset( $enumerations[ 'https://schema.org/' . $val_prefix . $prop_val . $val_suffix ] ) ) {

						$json_data[ $prop_name ] = 'https://schema.org/' . $val_prefix . $prop_val . $val_suffix;

					} else {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'invalid ' . $prop_name . ' property value "' . $prop_val . '"' );
						}

						unset( $json_data[ $prop_name ] );
					}
				}
			}
		}

		/*
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

				/*
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

		/*
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

							$wpsso->debug->log( 'skipping ' . $prop_name . ' url #' . $num . ': url is empty' );
						}

					} elseif ( isset( $json_data[ 'url' ] ) && $json_data[ 'url' ] === $url ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ' url #' . $num . ': url "' . $url . '" is duplicate of url property' );
						}

					} elseif ( isset( $added_urls[ $url ] ) ) {	// Already added.

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ' url #' . $num . ': url "' . $url . '" is duplicate' );
						}

					} elseif ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping ' . $prop_name . ' url #' . $num . ': url "' . $url . '" is invalid' );
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

		/*
		 * Returns false on error.
		 *
		 * $id_suffix can be a string, or an array.
		 */
		public static function update_data_id( &$json_data, $id_suffix, $data_url = null, $hash_url = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();

				$wpsso->debug->log_args( array(
					'id_suffix' => $id_suffix,
					'data_url'  => $data_url,
					'hash_url'  => $hash_url,
				) );
			}

			static $id_anchor = null;
			static $id_delim  = null;

			if ( null === $id_anchor || null === $id_delim ) {	// Optimize and call just once.

				$id_anchor = self::get_id_anchor();
				$id_delim  = self::get_id_delim();
			}

			if ( is_array( $id_suffix ) ) {

				$id_suffix = implode( $id_delim, $id_suffix );
			}

			$id_suffix = rtrim( $id_suffix, $id_delim );	// Just in case.

			if ( empty( $id_suffix ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: id suffix is empty and required' );
				}

				return false;
			}

			if ( $wpsso->debug->enabled ) {

				if ( empty( $json_data[ '@id' ] ) ) {

					$wpsso->debug->log( 'input @id property is empty' );

				} else $wpsso->debug->log( 'input @id property is ' . $json_data[ '@id' ] );
			}

			/*
			 * If $id_suffix is a URL, then use it as-is.
			 */
			if ( false !== filter_var( $id_suffix, FILTER_VALIDATE_URL ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'provided type_id is a valid url' );
				}

				unset( $json_data[ '@id' ] );	// Just in case.

				$json_data = array( '@id' => $id_suffix ) + $json_data;		// Make @id the first value in the array.

			} else {

				$id_url = '';

				if ( ! empty( $data_url ) ) {

					if ( is_string( $data_url ) ) {	// Just in case.

						$id_url = $data_url;
					}

				} elseif ( ! empty( $json_data[ '@id' ] ) ) {

					$id_url = $json_data[ '@id' ];

				} elseif ( ! empty( $json_data[ 'url' ] ) ) {

					$id_url = $json_data[ 'url' ];
				}

				/*
				 * Maybe remove an anchor ID from the begining of the type id string.
				 */
				if ( 0 === strpos( $id_suffix, $id_anchor ) ) {

					$id_suffix = substr( $id_suffix, strlen( $id_anchor ) - 1 );
				}

				/*
				 * Standardize the $id_suffix string.
				 */
				$id_suffix = preg_replace( '/[-_\. ]+/', '-', $id_suffix );

				/*
				 * Check if we already have an anchor ID in the URL.
				 */
				if ( false === strpos( $id_url, $id_anchor ) ) {

					/*
					 * Remove the URL fragment.
					 *
					 * Example: http://woo.surniaulula.com/product/a-variable-product/#comment-2.
					 */
					if ( false !== ( $pos = strpos( $id_url, '#' ) ) ) {

						$id_url = substr( $id_url, 0, $pos );
					}

					$id_url .= $id_anchor;
				}

				/*
				 * Check if we already have the type id in the URL.
				 */
				if ( false === strpos( $id_url, $id_anchor . $id_suffix ) ) {

					$id_url = trim( $id_url, $id_delim ) . $id_delim . $id_suffix;
				}

				unset( $json_data[ '@id' ] );	// Just in case.

				$json_data = array( '@id' => $id_url ) + $json_data;	// Make @id the first value in the array.
			}

			/*
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

				$wpsso->debug->log( 'returned @id property is ' . $json_data[ '@id' ] );
			}

			return true;
		}

		/*
		 * Sanitation used by filters to return their data.
		 */
		public static function return_data_from_filter( $json_data, $json_ret, $is_main = false ) {

			if ( empty( $json_ret ) || ! is_array( $json_ret ) ) {	// Just in case - nothing to merge.

				return $json_data;
			}

			if ( ! $is_main || ! empty( $json_ret[ 'mainEntity' ] ) ) {

				unset( $json_data[ 'mainEntity' ] );
				unset( $json_data[ 'mainEntityOfPage' ] );

			} elseif ( ! isset( $json_ret[ 'mainEntityOfPage' ] ) ) {	// $is_main = true.

				if ( ! empty( $json_ret[ 'url' ] ) ) {

					/*
					 * Remove any URL fragment from the main entity URL.
					 *
					 * The 'mainEntityOfPage' can be empty and will be removed by WpssoSchemaGraph::optimize_json().
					 */
					$json_ret[ 'mainEntityOfPage' ] = preg_replace( '/#.*$/', '', $json_ret[ 'url' ] );
				}
			}

			$json_data = is_array( $json_data ) ? array_merge( $json_data, $json_ret ) : $json_ret;

			unset( $json_ret );

			return self::return_data_head_first( $json_data );
		}

		public static function return_data_head_first( $json_data ) {

			if ( is_array( $json_data ) ) {	// Just in case.

				$json_head = array(
					'@id'              => null,
					'@context'         => null,
					'@type'            => null,
					'mainEntityOfPage' => null,
				);

				$json_data = array_merge( $json_head, $json_data );

				foreach ( $json_head as $prop_name => $prop_val ) {

					if ( empty( $json_data[ $prop_name ] ) ) {

						unset( $json_data[ $prop_name ] );
					}
				}
			}

			return $json_data;
		}

		/*
		 * See WpssoBcFilters->filter_json_data_https_schema_org_breadcrumblist().
		 * See WpssoSchemaGraph::optimize_json().
		 * See WpssoSchema::update_data_id().
		 */
		public static function get_id_anchor() {

			return '#sso/';
		}

		public static function get_id_delim() {

			return '/';
		}

		/*
		 * Add cross-references for schema sub-type arrays that exist under more than one type.
		 *
		 * For example, Thing > Place > LocalBusiness also exists under Thing > Organization > LocalBusiness.
		 */
		private function add_schema_type_xrefs( &$schema_types ) {

			$thing =& $schema_types[ 'thing' ];	// Quick ref variable for the 'thing' array.

			/*
			 * Thing > Intangible > Enumeration.
			 */
			$thing[ 'intangible' ][ 'enumeration' ][ 'specialty' ][ 'medical.specialty' ] =&
				$thing[ 'intangible' ][ 'enumeration' ][ 'medical.enumeration' ][ 'medical.specialty' ];

			$thing[ 'intangible' ][ 'service' ][ 'service.financial.product' ][ 'payment.card' ] =&
				$thing[ 'intangible' ][ 'enumeration' ][ 'payment.method' ][ 'payment.card' ];

			/*
			 * Thing > Organization > Educational Organization.
			 */
			$thing[ 'organization' ][ 'educational.organization' ] =& $thing[ 'place' ][ 'civic.structure' ][ 'educational.organization' ];

			/*
			 * Thing > Organization > Local Business.
			 */
			$thing[ 'organization' ][ 'local.business' ] =& $thing[ 'place' ][ 'local.business' ];
		}
	}
}
