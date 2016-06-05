<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSchema' ) ) {

	class WpssoSchema {

		protected $p;
		protected $schema_types = null;	// cache for schema_type arrays

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes' => 3,
			), 5 );

			// only filter the head attribute if we have one
			if ( ! empty( $this->p->options['plugin_head_attr_filter_name'] ) &&
				$this->p->options['plugin_head_attr_filter_name'] !== 'none' ) {

				add_action( 'add_head_attributes', array( &$this, 'add_head_attributes' ) );
				$prio = empty( $this->p->options['plugin_head_attr_filter_prio'] ) ? 
					100 : $this->p->options['plugin_head_attr_filter_prio'];
				add_filter( $this->p->options['plugin_head_attr_filter_name'], 
					array( &$this, 'filter_head_attributes' ), $prio, 1 );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'skipped head attributes: filter name option is empty' );
		}

		public function filter_plugin_image_sizes( $sizes, $mod, $crawler_name ) {

			$sizes['schema_img'] = array(		// options prefix
				'name' => 'schema',		// wpsso-schema
				'label' => _x( 'Google / Schema Markup Image',
					'image size label', 'wpsso' ),
			);

			// if the pinterest crawler is detected, use the pinterest image dimensions instead
			if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) )
				if ( $crawler_name === 'pinterest' )
					$sizes['schema_img']['prefix'] = 'rp_img';

			return $sizes;
		}

		public function add_head_attributes() {
			echo apply_filters( $this->p->options['plugin_head_attr_filter_name'], '' );
		}

		public function filter_head_attributes( $head_attr = '' ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! $this->is_head_attributes_enabled() )
				return $head_attr;	// empty string

			$lca = $this->p->cf['lca'];
			$use_post = apply_filters( $lca.'_header_use_post', false );
			$mod = $this->p->util->get_page_mod( $use_post );	// get post/user/term id, module name, and module object reference
			$head_type_url = $this->get_head_item_type( $mod );

			if ( empty( $head_type_url ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema head type value is empty' );
				return $head_attr;
			}

			// fix incorrect itemscope values
			if ( strpos( $head_attr, ' itemscope="itemscope"' ) !== false )
				$head_attr = preg_replace( '/ itemscope="itemscope"/', 
					' itemscope', $head_attr );
			elseif ( strpos( $head_attr, ' itemscope' ) === false )
				$head_attr .= ' itemscope';

			// replace existing itemtype values
			if ( strpos( $head_attr, ' itemtype="' ) !== false )
				$head_attr = preg_replace( '/ itemtype="[^"]+"/',
					' itemtype="'.$head_type_url.'"', $head_attr );
			else $head_attr .= ' itemtype="'.$head_type_url.'"';

			return trim( $head_attr );
		}

		public function is_head_attributes_enabled() {

			if ( $this->p->is_avail['amp_endpoint'] && is_amp_endpoint() ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: amp endpoint' );
				return false;
			}

			// returns false when the wpsso-schema-json-ld extension is active
			if ( ! apply_filters( $this->p->cf['lca'].'_add_schema_head_attributes', true ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema head attributes disabled' );
				return false;
			}

			return true;
		}

		public function get_head_item_type( array &$mod, $return_id = false, $use_mod_opts = true ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$default_key = apply_filters( $lca.'_schema_type_for_default', 'webpage' );
			$schema_types =& $this->get_schema_types( true );
			$type_id = null;

			if ( $use_mod_opts ) {
				if ( ! empty( $mod['obj'] ) ) {	// just in case
					$type_id = $mod['obj']->get_options( $mod['id'], 'schema_type' );

					if ( empty( $type_id ) || $type_id === 'none' ) {
						$type_id = null;
					} elseif ( empty( $schema_types[$type_id] ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'custom type_id '.
								$type_id.' not in schema types' );
						$type_id = null;
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'custom type_id '.
							$type_id.' from '.$mod['name'].' module' );

				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'skipping custom type_id: module object is empty' );
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'skipping custom type_id: use_mod_opts argument is false' );

			if ( empty( $type_id ) )
				$is_md_type = false;
			else $is_md_type = true;

			// if no custom schema type, then use the default settings
			if ( empty( $type_id ) ) {

				if ( $mod['is_home'] )	// static or index page
					$type_id = apply_filters( $this->p->cf['lca'].'_schema_type_for_home_page',
						( empty( $this->p->options['schema_type_for_home_page'] ) ?
							'website' : $this->p->options['schema_type_for_home_page'] ) );

				elseif ( $mod['is_post'] ) {
					if ( ! empty( $mod['post_type'] ) ) {
						if ( isset( $this->p->options['schema_type_for_'.$mod['post_type']] ) ) {

							$type_id = $this->p->options['schema_type_for_'.$mod['post_type']];

							if ( empty( $type_id ) || $type_id === 'none' ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'schema type for post type '.
										$mod['post_type'].' is disabled' );
								$type_id = null;

							} elseif ( empty( $schema_types[$type_id] ) ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'schema type id '.
										$type_id.' not found in schema types array' );
								$type_id = $default_key;

							} elseif ( $this->p->debug->enabled )
								$this->p->debug->log( 'schema type id for post type '.
									$mod['post_type'].' is '.$type_id );

						} elseif ( ! empty( $schema_types[$mod['post_type']] ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'setting schema type id to post type '.
									$mod['post_type'] );
							$type_id = $mod['post_type'];

						// unknown post type
						} else {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'using page schema type: unknown post type '.
									$mod['post_type'] );
							$type_id = apply_filters( $lca.'_schema_type_for_post_type_unknown',
								$this->p->options['schema_type_for_page'] );
						}

					// post objects without a post_type property
					} else {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'using page schema type: empty post type' );
						$type_id = apply_filters( $lca.'_schema_type_for_post_type_empty',
							$this->p->options['schema_type_for_page'] );
					}

				} elseif ( $this->p->util->force_default_author( $mod, 'og' ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'using post schema type: default author is forced' );
					$type_id = apply_filters( $lca.'_schema_type_for_author_forced',
						$this->p->options['schema_type_for_post'] );

				// default value for all other webpages
				} else {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'using default schema type' );
					$type_id = $default_key;
				}
			}

			$type_id = apply_filters( $this->p->cf['lca'].'_schema_head_type', $type_id, $mod, $is_md_type );

			if ( isset( $schema_types[$type_id] ) ) {
				if ( $return_id ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'returning schema type id '.
							$type_id );
					return $type_id;
				} else {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'returning schema type value '.
							$schema_types[$type_id] );
					return $schema_types[$type_id];
				}
			} else {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'returning false: schema type id '.
						$type_id.' not found in schema types array' );
				return false;
			}
		}

		public function get_head_type_context( array &$mod ) {
			return self::get_item_type_context( $this->get_head_item_type( $mod ) );
		}

		public static function get_item_type_context( $type_url, $properties = array() ) {
			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $type_url, $match ) )
				// list content and type array keys first, in case they don't already exist
				return array_merge( array( '@context' => null, '@type' => null ), $properties, 
					array( '@context' => $match[1], '@type' => $match[2] ) );
			else return $properties;
		}

		public static function get_item_type_parts( $type_url ) {
			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $type_url, $match ) )
				return array( $match[1], $match[2] );
			else return array();
		}

		public function &get_schema_types( $flatten = true ) {
			if ( ! isset( $this->schema_types['filtered'] ) ) {
				$lca = $this->p->cf['lca'];
				if ( $this->p->is_avail['cache']['transient'] ) {
					$cache_salt = __METHOD__;
					$cache_id = $lca.'_'.md5( $cache_salt );
					$this->schema_types = get_transient( $cache_id );	// returns false when not found
				}
				if ( ! isset( $this->schema_types['filtered'] ) ) {
					$this->schema_types['filtered'] = (array) apply_filters( $lca.'_schema_types', $this->p->cf['head']['schema_type'] );
					$this->schema_types['flattened'] = SucomUtil::array_flatten( $this->schema_types['filtered'] );
					$this->schema_types['parent_index'] = SucomUtil::array_parent_index( $this->schema_types['filtered'] );
					ksort( $this->schema_types['flattened'] );
					ksort( $this->schema_types['parent_index'] );
					if ( ! empty( $cache_id ) )
						set_transient( $cache_id, $this->schema_types, $this->p->options['plugin_object_cache_exp'] );
				}
			}
			if ( $flatten )
				return $this->schema_types['flattened'];
			else return $this->schema_types['filtered'];
		}

		public function get_schema_types_select( $schema_types = null, $add_none = true ) {
			if ( ! is_array( $schema_types ) )
				$schema_types =& $this->get_schema_types( true );
			else $schema_types = SucomUtil::array_flatten( $schema_types );

			$select = array();

			foreach ( $schema_types as $type_id => $label )
				$select[$type_id] = $type_id.' ('.$label.')';

			if ( defined( 'SORT_NATURAL' ) )
				asort( $select, SORT_NATURAL );
			else asort( $select );

			if ( $add_none )
				return array_merge( array( 'none' => '[None]' ), $select );
			else return $select;
		}

		// get the full schema type url from the array key
		public function get_schema_type_url( $type_id, $default_id = false ) {
			$schema_types =& $this->get_schema_types( true );
			if ( isset( $schema_types[$type_id] ) )
				return $schema_types[$type_id];
			elseif ( is_string( $default_id ) &&
				isset( $schema_types[$default_id] ) )
					return $schema_types[$default_id];
			else return false;
		}

		// returns an array of schema type ids with gparents, parents, child (in that order)
		public function get_schema_type_parents( $child_id ) {
			$return = array();
			$schema_types =& $this->get_schema_types( true );
			$parent_index =& $this->schema_types['parent_index'];	// shortcut
			if ( isset( $parent_index[$child_id] ) ) {
				$parent_id = $parent_index[$child_id];
				if ( isset( $schema_types[$parent_id] ) ) {
					if ( $parent_id !== $child_id )	// prevent infinite loops
						$return = array_merge( $return, $this->get_schema_type_parents( $parent_id ) );
				}
			}
			$return[] = $child_id;	// always add child after parent
			return $return;
		}

		public function count_schema_type_children( $type_id ) {
			$count = 1;
			$schema_types =& $this->get_schema_types( true );
			$parent_index =& $this->schema_types['parent_index'];	// shortcut
			foreach ( $parent_index as $child_id => $parent_id ) {
				if ( $parent_id === $type_id )
					$count += $this->count_schema_type_children( $child_id );
			}
			return $count;
		}

		public function has_json_data_filter( array &$mod, $item_type = '' ) {
			$filter_name = $this->get_json_data_filter( $mod, $item_type );
			return ! empty( $filter_name ) && 
				has_filter( $filter_name ) ? 
					true : false;
		}

		public function get_json_data_filter( array &$mod, $item_type = '' ) {
			if ( empty( $item_type ) )
				$item_type = $this->get_head_item_type( $mod );
			return $this->p->cf['lca'].'_json_data_'.SucomUtil::sanitize_hookname( $item_type );
		}

		/*
		 * JSON-LD Script Array
		 */
		public function get_json_array( $use_post, array &$mod, array &$mt_og, $user_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build json array' );	// begin timer for json array

			$ret = array();
			$lca = $this->p->cf['lca'];
			$type_ids = array();
			$filtered_names = array();	// prevent duplicate branches

			// example: article.tech
			$mt_og['schema:type:id'] = $head_type_id = $this->get_head_item_type( $mod, true );

			// example: http://schema.org/TechArticle
			$mt_og['schema:type:url'] = $head_type_url = $this->get_schema_type_url( $head_type_id );

			// example: http://schema.org, TechArticle
			list( $mt_og['schema:type:context'],
				$mt_og['schema:type:name'] ) = self::get_item_type_parts( $head_type_url );

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'head_type: '.$head_type_url.' ('.$head_type_id.')' );

			// include first
			if ( ! empty( $head_type_url ) )
				$type_ids[$head_type_id] = true;

			// include WebSite, Organization, and/or Person on the home page (static or non-static)
			if ( $mod['is_home'] ) {	// static or index page
				$type_ids['website'] = $this->p->options['schema_website_json'];
				$type_ids['organization'] = $this->p->options['schema_organization_json'];
				$type_ids['person'] = $this->p->options['schema_person_json'];
			}

			/*
			 * Array (
			 *	[restaurant] => 1
			 *	[website] => 1
			 *	[organization] => 1
			 *	[person] => 1
			 * )
			 */
			$type_ids = apply_filters( $lca.'_json_schema_type_ids', $type_ids, $mod );

			foreach ( $type_ids as $top_type_id => $is_enabled ) {

				$json_data = null;
				$top_type_url = $this->get_schema_type_url( $top_type_id );
				$top_filter_name = SucomUtil::sanitize_hookname( $top_type_url );

				if ( ! empty( $filtered_names[$top_filter_name] ) ) {	// prevent duplicate branches
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'skipping '.$top_filter_name.': type or sub-type previously added' );
					continue;
				}

				if ( $this->p->debug->enabled )
					$this->p->debug->mark( $top_type_id.' schema' );	// begin timer for schema json

				$is_main = $mod['obj'] ?
					$mod['obj']->get_options( $mod['id'], 'schema_is_main' ) : null;

				if ( $is_main === null )
					$is_main = $top_type_id === $head_type_id ? true : false;

				$is_main = apply_filters( $lca.'_json_is_main_entity', 
					$is_main, $use_post, $mod, $mt_og, $user_id );

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'is_main_entity: '.( $is_main ? 'true' : 'false' ) );

				// add http_schema_org first as a generic / common data filter
				$parent_urls = array( 'http://schema.org' );

				// returns an array of type ids with gparents, parents, child (in that order)
				foreach ( $this->get_schema_type_parents( $top_type_id ) as $rel_type_id )
					$parent_urls[] = $this->get_schema_type_url( $rel_type_id );

				foreach ( $parent_urls as $rel_type_url ) {
					$rel_filter_name = SucomUtil::sanitize_hookname( $rel_type_url );
					$has_filter = has_filter( $lca.'_json_data_'.$rel_filter_name );
					$filtered_names[$rel_filter_name] = true;	// prevent duplicate branches

					// add website, organization, and person markup to home page
					if ( $mod['is_home'] && ! $has_filter &&
						method_exists( __CLASS__, 'filter_json_data_'.$rel_filter_name ) ) {
	
						if ( $is_enabled ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'calling class method '.$rel_filter_name );
							$json_data = call_user_func( array( __CLASS__, 'filter_json_data_'.$rel_filter_name ),
								$json_data, $use_post, $mod, $mt_og, $user_id, false );	// $is_main = always false for method
						} elseif ( $this->p->debug->enabled )
							$this->p->debug->log( $rel_filter_name.' class method is disabled' );
					} elseif ( $has_filter ) {
						if ( apply_filters( $lca.'_add_json_'.$rel_filter_name, $is_enabled ) ) {
							$json_data = apply_filters( $lca.'_json_data_'.$rel_filter_name,
								$json_data, $use_post, $mod, $mt_og, $user_id, $is_main );
						} elseif ( $this->p->debug->enabled )
							$this->p->debug->log( $rel_filter_name.' filter is disabled' );
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'no filters registered for '.$rel_filter_name );
				}

				if ( ! empty( $json_data ) && is_array( $json_data ) ) {
					// define the context and type properties for methods / filters that
					// may not define them, or re-define them incorrectly
					$json_data = self::get_item_type_context( $top_type_url, $json_data );
					$ret[] = '<script type="application/ld+json">'.
						$this->p->util->json_format( $json_data ).
							'</script>'."\n";
				}

				if ( $this->p->debug->enabled )
					$this->p->debug->mark( $top_type_id.' schema' );	// end timer for schema json
			}

			$ret = SucomUtil::a2aa( $ret );	// convert to array of arrays

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $ret );
				$this->p->debug->mark( 'build json array' );	// end timer for json array
			}

			return $ret;
		}

		/*
		 * http://schema.org/WebSite for Google
		 */
		public function filter_json_data_http_schema_org_website( $json_data, $use_post, $mod, $mt_og, $user_id, $is_main ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'WebSite',
				'url' => $mt_og['og:url'],
				'name' => SucomUtil::get_site_name( $this->p->options, $mod ),
			);

			if ( ! empty( $this->p->options['schema_alt_name'] ) )
				$ret['alternateName'] = $this->p->options['schema_alt_name'];

			$desc = SucomUtil::get_site_description( $this->p->options, $mod );
			if ( ! empty( $desc ) )
				$ret['description'] = $desc;

			if ( $is_main )
				self::add_main_entity_data( $ret, $ret['url'] );

			$search_url = apply_filters( $lca.'_json_ld_search_url',
				get_bloginfo( 'url' ).'?s={search_term_string}' );

			if ( ! empty( $search_url ) ) {
				$ret['potentialAction'] = array(
					'@context' => 'http://schema.org',
					'@type' => 'SearchAction',
					'target' => $search_url,
					'query-input' => 'required name=search_term_string',
				);
			}

			return self::return_data_from_filter( $json_data, $ret );
		}

		/*
		 * http://schema.org/Organization social markup for Google
		 */
		public function filter_json_data_http_schema_org_organization( $json_data, $use_post, $mod, $mt_og, $user_id, $is_main ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$ret = array();

			self::add_single_organization_data( $ret, $mod, 'schema_logo_url', false );	// list_element = false

			if ( $is_main )
				self::add_main_entity_data( $ret, $ret['url'] );

			$social_accounts = apply_filters( $this->p->cf['lca'].'_social_accounts', 
				$this->p->cf['form']['social_accounts'] );
			asort( $social_accounts );	// sort by label and maintain key association
			foreach ( $social_accounts as $key => $label ) {
				$url_locale = SucomUtil::get_locale_opt( $key, $this->p->options, $mod );
				if ( empty( $url_locale ) )
					continue;
				if ( $key === 'tc_site' )
					$url_locale = 'https://twitter.com/'.preg_replace( '/^@/', '', $url_locale );
				if ( strpos( $url_locale, '://' ) )
					$ret['sameAs'][] = esc_url( $url_locale );
			}

			return self::return_data_from_filter( $json_data, $ret );
		}

		/*
		 * http://schema.org/Person social markup for Google
		 */
		public function filter_json_data_http_schema_org_person( $json_data, $use_post, $mod, $mt_og, $user_id, $is_main ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( $mod['is_home'] ) {	// static or index page
				$user_id = $this->p->options['schema_person_id'];
				if ( empty( $user_id ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: no schema_person_id for front page' );
					return $json_data;
				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'using user_id '.$user_id.' for front page' );
			}

			if ( empty( $user_id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: no user_id' );
				return $json_data;
			}

			$lca = $this->p->cf['lca'];
			$ret = array();

			if ( $is_main )
				self::add_main_entity_data( $ret, $ret['url'] );

			self::add_single_person_data( $ret, $user_id, false );	// list_element = false

			// override the author's website url from his profile and use the open graph url instead
			if ( $mod['is_home'] )
				$ret['url'] = $mt_og['og:url'];

			return self::return_data_from_filter( $json_data, $ret );
		}

		// sanitation
		public static function return_data_from_filter( &$json_data, &$ret_data ) {
			return empty( $ret_data ) ? $json_data : 
				( $json_data === null ? $ret_data : 
					( is_array( $json_data ) ? array_merge( $json_data, $ret_data ) : 
						$json_data ) );
		}

		// $logo_key can be 'schema_logo_url' or 'schema_banner_url' (for Articles)
		public static function add_single_organization_data( &$json_data, &$mod, $logo_key = 'schema_logo_url', $list_element = false ) {

			$wpsso =& Wpsso::get_instance();

			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'Organization',
				'url' => esc_url( get_bloginfo( 'url' ) ),
				'name' => SucomUtil::get_site_name( $wpsso->options, $mod ),
			);

			if ( ! empty( $wpsso->options['schema_alt_name'] ) )
				$ret['alternateName'] = $wpsso->options['schema_alt_name'];

			$desc = SucomUtil::get_site_description( $wpsso->options, $mod );
			if ( ! empty( $desc ) )
				$ret['description'] = $desc;

			if ( ! empty( $wpsso->options[$logo_key] ) ) {
				if ( ! self::add_single_image_data( $ret['logo'], $wpsso->options, $logo_key, false ) ) {	// list_element = false
					unset( $ret['logo'] );	// prevent null assignment
				}
			}

			if ( empty( $ret['logo'] ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'organization '.$logo_key.' image is missing and required' );
				if ( is_admin() && ( ! $mod['is_post'] || $mod['post_status'] === 'publish' ) )
					$wpsso->notice->err( $wpsso->msgs->get( 'notice-missing-'.$logo_key ) );
			}

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;

			return 1;
		}

		// $user_id is optional and takes precedence over the $mod post_author value
		public static function add_author_and_coauthor_data( &$json_data, $mod, $user_id = 0 ) {
			$authors_added = 0;
			$coauthors_added = 0;
			$wpsso =& Wpsso::get_instance();

			if ( empty( $user_id ) && 
				isset( $mod['post_author'] ) )
					$user_id = $mod['post_author'];

			if ( empty( $user_id ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty user_id / post_author' );
				return 0;
			}

			// single author
			$authors_added += self::add_single_person_data( $json_data['author'],
				$user_id, false );			// list_element = false

			// list of contributors / co-authors
			if ( isset( $mod['post_coauthors'] ) && is_array( $mod['post_coauthors'] ) )
				foreach ( $mod['post_coauthors'] as $author_id )
					$coauthors_added += self::add_single_person_data( $json_data['contributor'],
						$author_id, true );	// list_element = true

			foreach ( array( 'author', 'contributor' ) as $itemprop ) {
				if ( empty( $json_data[$itemprop] ) ) {
					unset( $json_data[$itemprop] );	// prevent null assignment
				}
			}

			return $authors_added + $coauthors_added;	// return count of authors and coauthors added
		}

		public static function add_single_person_data( &$json_data, $user_id = 0, $list_element = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( empty( $user_id ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty user_id' );
				return 0;
			} elseif ( empty( $wpsso->m['util']['user'] ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty user module' );
				return 0;
			} else $mod = $wpsso->m['util']['user']->get_mod( $user_id );

			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'Person',
			);

			$url = $mod['obj']->get_author_website( $user_id, 'url' );
			if ( strpos( $url, '://' ) !== false )
				$ret['url'] = esc_url( $url );

			$name = $mod['obj']->get_author_meta( $user_id, $wpsso->options['schema_author_name'] );
			if ( ! empty( $name ) )
				$ret['name'] = $name;

			$desc = $mod['obj']->get_options_multi( $user_id, array( 'schema_desc', 'og_desc' ) );
			if ( empty( $desc ) )
				$desc = $mod['obj']->get_author_meta( $user_id, 'description' );
			if ( ! empty( $desc ) )
				$ret['description'] = $desc;

			// get_og_images() also provides filter hooks for additional image ids and urls
			$size_name = $wpsso->cf['lca'].'-schema';
			$og_image = $mod['obj']->get_og_image( 1, $size_name, $user_id, false );	// $check_dupes = false

			if ( ! empty( $og_image ) ) {
				if ( ! self::add_image_list_data( $ret['image'], $og_image, 'og:image' ) ) {
					unset( $ret['image'] );	// prevent null assignment
				}
			}

			foreach ( WpssoUser::get_user_id_contact_methods( $user_id ) as $cm_id => $cm_label ) {
				$url = $mod['obj']->get_author_meta( $user_id, $cm_id );
				if ( empty( $url ) )
					continue;
				if ( $cm_id === $wpsso->options['plugin_cm_twitter_name'] )
					$url = 'https://twitter.com/'.preg_replace( '/^@/', '', $url );
				if ( strpos( $url, '://' ) !== false )
					$ret['sameAs'][] = esc_url( $url );
			}

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;

			return 1;
		}

		// pass a single or two dimension image array in $og_image
		public static function add_image_list_data( &$json_data, &$og_image, $prefix = 'og:image' ) {
			$images_added = 0;

			if ( isset( $og_image[0] ) && is_array( $og_image[0] ) ) {						// 2 dimensional array
				foreach ( $og_image as $image )
					$images_added += self::add_single_image_data( $json_data, $image, $prefix, true );	// list_element = true
			} elseif ( is_array( $og_image ) )
				$images_added += self::add_single_image_data( $json_data, $og_image, $prefix, true );		// list_element = true

			return $images_added;	// return count of images added
		}

		// pass a single dimension image array in $opts
		public static function add_single_image_data( &$json_data, &$opts, $prefix = 'og:image', $list_element = true ) {
			$wpsso =& Wpsso::get_instance();

			if ( empty( $opts ) || ! is_array( $opts ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: options array is empty or not an array' );
				return 0;	// return count of images added
			}

			// get the preferred URL (og:image:secure_url, og:image:url, og:image)
			$media_url = SucomUtil::get_mt_media_url( $prefix, $opts );

			if ( empty( $media_url ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: '.$prefix.' URL values are empty' );
				return 0;	// return count of images added
			}

			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'ImageObject',
				'url' => esc_url( $media_url ),
			);

			self::add_data_itemprop_from_og( $ret, $opts, array(
				'width' => $prefix.':width',
				'height' => $prefix.':height',
			) );

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;	// add an item to the list

			return 1;	// return count of images added
		}

		public static function add_main_entity_data( array &$json_data, $url ) {
			$json_data['mainEntityOfPage'] = array(
				'@context' => 'http://schema.org',
				'@type' => 'WebPage',
				'@id' => $url,
			);
		}

		// deprecated 2016/05/18
		public static function add_data_prop_from_og( array &$json_data, array &$mt_og, array $names ) {
			return self::add_data_itemprop_from_og( $json_data, $mt_og, $names );
		}

		public static function add_data_itemprop_from_og( array &$json_data, array &$mt_og, array $names ) {
			foreach ( $names as $itemprop_name => $og_name )
				if ( ! empty( $mt_og[$og_name] ) )
					$json_data[$itemprop_name] = $mt_og[$og_name];
		}

		public static function get_data_itemprop_from_og( array &$mt_og, array $names ) {
			foreach ( $names as $itemprop_name => $og_name )
				if ( ! empty( $mt_og[$og_name] ) )
					$ret[$itemprop_name] = $mt_og[$og_name];
			return empty( $ret ) ? false : $ret;
		}

		// QuantitativeValue (width, height, length, depth, weight)
		// unitCodes from http://wiki.goodrelations-vocabulary.org/Documentation/UN/CEFACT_Common_Codes
		public static function add_data_quantitative_from_og( array &$json_data, array &$mt_og, array $names ) {
			foreach ( $names as $itemprop_name => $og_name )
				if ( ! empty( $mt_og[$og_name] ) )
					switch ( $itemprop_name ) {
						case 'length':	// QuantitativeValue does not have a length itemprop
							$json_data['additionalProperty'][] = array(
								'@context' => 'http://schema.org',
								'@type' => 'PropertyValue',
								'propertyID' => $itemprop_name,
								'value' => $mt_og[$og_name],
								'unitCode' => 'CMT',
							);
							break;
						default:
							$json_data[$itemprop_name] = array(
								'@context' => 'http://schema.org',
								'@type' => 'QuantitativeValue',
								'value' => $mt_og[$og_name],
								'unitCode' => ( $itemprop_name === 'weight' ? 'KGM' : 'CMT' ),
							);
							break;
					}
		}

		/*
		 * Meta Name Array
		 */
		public function get_meta_array( $use_post, array &$mod, array &$mt_og, $crawler_name ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			// get_meta_array() is disabled when the wpsso-schema-json-ld extension is active
			if ( ! apply_filters( $this->p->cf['lca'].'_add_schema_meta_array', true ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema meta array disabled' );
				return array();	// empty array
			}

			$mt_schema = array();
			$lca = $this->p->cf['lca'];
			$max = $this->p->util->get_max_nums( $mod, 'schema' );
			$size_name = $this->p->cf['lca'].'-schema';
			$head_type_url = $this->get_head_item_type( $mod );

			$this->add_mt_schema_from_og( $mt_schema, $mt_og, array(
				'url' => 'og:url',
				'name' => 'og:title',
			) );

			if ( ! empty( $this->p->options['add_meta_itemprop_description'] ) )
				$mt_schema['description'] = $this->p->webpage->get_description( $this->p->options['schema_desc_len'], 
					'...', $mod, true, true, true, 'schema_desc' );	// $md_idx = schema_desc

			switch ( $head_type_url ) {
				case 'http://schema.org/BlogPosting':
				case 'http://schema.org/WebPage':

					$this->add_mt_schema_from_og( $mt_schema, $mt_og, array(
						'datepublished' => 'article:published_time',
						'datemodified' => 'article:modified_time',
					) );

					// add single image meta tags (no width or height) if noscript containers are disabled
					if ( ! $this->is_noscript_enabled() &&
						! empty( $this->p->options['add_meta_itemprop_image'] ) ) {

						$og_image = $this->p->og->get_all_images( $max['schema_img_max'],
							$size_name, $mod, true, 'schema' );	// $md_pre = 'schema'

						if ( empty( $og_image ) && $mod['is_post'] ) 
							$og_image = $this->p->media->get_default_image( 1, $size_name, true );
	
						foreach ( $og_image as $image )
							$mt_schema['image'][] = SucomUtil::get_mt_media_url( 'og:image', $image );
					}
					break;
			}

			return apply_filters( $this->p->cf['lca'].'_schema_meta_itemprop', $mt_schema, $use_post, $mod );
		}

		public function add_mt_schema_from_og( array &$mt_schema, array &$mt_og, array $names ) {
			foreach ( $names as $mt_name => $og_name )
				if ( ! empty( $this->p->options['add_meta_itemprop_'.$mt_name] )
					&& ! empty( $mt_og[$og_name] ) )
						$mt_schema[$mt_name] = $mt_og[$og_name];
		}

		/*
		 * NoScript Meta Name Array
		 */
		public function get_noscript_array( array &$mod, array &$mt_og, $user_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! self::is_noscript_enabled() )
				return array();	// empty array

			$ret = array();
			$lca = $this->p->cf['lca'];
			$max = $this->p->util->get_max_nums( $mod, 'schema' );
			$size_name = $this->p->cf['lca'].'-schema';
			$head_type_url = $this->get_head_item_type( $mod );

			$og_image = $this->p->og->get_all_images( $max['schema_img_max'], $size_name, $mod, true, 'schema' );	// $md_pre = 'schema'

			if ( empty( $og_image ) && $mod['is_post'] ) 
				$og_image = $this->p->media->get_default_image( 1, $size_name, true );

			foreach ( $og_image as $image )
				$ret = array_merge( $ret, $this->get_single_image_noscript( $mod, $image ) );

			switch ( $head_type_url ) {
				case 'http://schema.org/BlogPosting':
				case 'http://schema.org/WebPage':
					$ret = array_merge( $ret, $this->get_author_list_noscript( $mod ) );
					break;
			}

			return apply_filters( $this->p->cf['lca'].'_schema_noscript_array', $ret, $mod, $mt_og );
		}

		public function is_noscript_enabled() {

			if ( $this->p->is_avail['amp_endpoint'] && is_amp_endpoint() ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: amp endpoint' );
				return false;
			}

			// returns false when the wpsso-schema-json-ld extension is active
			if ( ! apply_filters( $this->p->cf['lca'].'_add_schema_noscript_array',
				( isset( $this->p->options['schema_add_noscript'] ) ?
					$this->p->options['schema_add_noscript'] : true ) ) ) {

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema noscript array disabled' );
				return false;
			}

			return true;
		}

		public function get_single_image_noscript( array &$mod, &$mixed, $prefix = 'og:image' ) {

			$mt_image = array();

			if ( empty( $mixed ) ) {
				return array();

			} elseif ( is_array( $mixed ) ) {
				$media_url = SucomUtil::get_mt_media_url( $prefix, $mixed );

				if ( empty( $media_url ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: '.$prefix.' URL values are empty' );
					return array();
				}

				// defines a two-dimensional array
				$mt_image = array_merge(
					$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.url', $media_url, '', $mod ),
					( empty( $mixed[$prefix.':width'] ) ? array() : $this->p->head->get_single_mt( 'meta',
						'itemprop', 'image.width', $mixed[$prefix.':width'], '', $mod ) ),
					( empty( $mixed[$prefix.':height'] ) ? array() : $this->p->head->get_single_mt( 'meta',
						'itemprop', 'image.height', $mixed[$prefix.':height'], '', $mod ) )
				);

			// defines a two-dimensional array
			} else $mt_image = $this->p->head->get_single_mt( 'meta', 'itemprop', 'image.url', $mixed, '', $mod );

			// make sure we have html for at least one meta tag
			$have_image_html = false;
			foreach ( $mt_image as $num => $img ) {
				if ( ! empty( $img[0] ) ) {
					$have_image_html = true;
					break;
				}
			}

			if ( $have_image_html ) {
				return array_merge(
					array( array( '<noscript itemprop="image" itemscope itemtype="http://schema.org/ImageObject">'."\n" ) ),
					$mt_image,
					array( array( '</noscript>'."\n" ) )
				);
			} else return array();
		}

		public function get_author_list_noscript( array &$mod ) {

			if ( empty( $mod['post_author'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: empty post_author' );
				return array();
			}

			$ret = $this->get_single_author_noscript( $mod, $mod['post_author'], 'author' );

			if ( isset( $mod['post_coauthors'] ) && is_array( $mod['post_coauthors'] ) )
				foreach ( $mod['post_coauthors'] as $author_id )
					$ret = array_merge( $ret, $this->get_single_author_noscript( $mod, $author_id, 'contributor' ) );

			return $ret;
		}

		public function get_single_author_noscript( array &$mod, $author_id = 0, $itemprop = 'author' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->args( array( 
					'mod' => $mod,
					'author_id' => $author_id,
					'itemprop' => $itemprop,
				) );
			}

			$og_ret = array();
			if ( empty( $author_id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: empty author_id' );
				return array();
			} elseif ( empty( $this->p->m['util']['user'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: empty user module' );
				return array();
			} else $mod = $this->p->m['util']['user']->get_mod( $author_id );

			$url = $mod['obj']->get_author_website( $author_id, 'url' );
			$name = $mod['obj']->get_author_meta( $author_id, $this->p->options['schema_author_name'] );
			$desc = $mod['obj']->get_options_multi( $author_id, array( 'schema_desc', 'og_desc' ) );
			if ( empty( $desc ) )
				$desc = $mod['obj']->get_author_meta( $author_id, 'description' );

			$mt_author = array_merge(
				( empty( $url ) ? array() : $this->p->head->get_single_mt( 'meta',
					'itemprop', $itemprop.'.url', $url, '', $mod ) ),
				( empty( $name ) ? array() : $this->p->head->get_single_mt( 'meta',
					'itemprop', $itemprop.'.name', $name, '', $mod ) ),
				( empty( $desc ) ? array() : $this->p->head->get_single_mt( 'meta',
					'itemprop', $itemprop.'.description', $desc, '', $mod ) )
			);

			// optimize by first checking if the meta tag is enabled
			if ( ! empty( $this->p->options['add_meta_itemprop_author.image'] ) ) {

				// get_og_images() also provides filter hooks for additional image ids and urls
				$size_name = $this->p->cf['lca'].'-schema';
				$og_image = $mod['obj']->get_og_image( 1, $size_name, $author_id, false );	// $check_dupes = false
	
				foreach ( $og_image as $image ) {
					$image_url = SucomUtil::get_mt_media_url( 'og:image', $image );
					if ( ! empty( $image_url ) ) {
						$mt_author = array_merge( $mt_author, $this->p->head->get_single_mt( 'meta',
							'itemprop', $itemprop.'.image', $image_url, '', $mod ) );
					}
				}
			}

			// make sure we have html for at least one meta tag
			$have_author_html = false;
			foreach ( $mt_author as $num => $author ) {
				if ( ! empty( $author[0] ) ) {
					$have_author_html = true;
					break;
				}
			}

			if ( $have_author_html ) {
				return array_merge(
					array( array( '<noscript itemprop="'.$itemprop.'" itemscope itemtype="http://schema.org/Person">'."\n" ) ),
					$mt_author,
					array( array( '</noscript>'."\n" ) )
				);
			} else return array();
		}
	}
}

?>
