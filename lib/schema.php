<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
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

			if ( $this->is_head_attributes_enabled() ) {
				add_action( 'add_head_attributes', array( &$this, 'add_head_attributes' ) );
				add_filter( $this->p->options['plugin_head_attr_filter_name'], array( &$this, 'filter_head_attributes' ),
					( empty( $this->p->options['plugin_head_attr_filter_prio'] ) ? 
						100 : $this->p->options['plugin_head_attr_filter_prio'] ), 1 );
			}
		}

		public function filter_plugin_image_sizes( $sizes, $mod, $crawler_name ) {

			$sizes['schema_img'] = array(		// options prefix
				'name' => 'schema',		// wpsso-schema
				'label' => _x( 'Google / Schema Image',
					'image size label', 'wpsso' ),
			);

			$sizes['schema_img_article'] = array(		// options prefix
				'name' => 'schema-article',		// wpsso-schema-article
				'label' => _x( 'Google / Schema Image',
					'image size label', 'wpsso' ),
				'prefix' => 'schema_img',
			);

			if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) ) {
				if ( $crawler_name === 'pinterest' )
					$sizes['schema_img']['prefix'] = 'rp_img';
			}

			return $sizes;
		}

		public function add_head_attributes() {
			if ( ! empty( $this->p->options['plugin_head_attr_filter_name'] ) ) {	// just in case
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'calling filter '.$this->p->options['plugin_head_attr_filter_name'] );
				echo apply_filters( $this->p->options['plugin_head_attr_filter_name'], '' );
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'plugin_head_attr_filter_name is empty' );
		}

		public function filter_head_attributes( $head_attr = '' ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! $this->is_head_attributes_enabled() )
				return $head_attr;

			$lca = $this->p->cf['lca'];
			$use_post = apply_filters( $lca.'_head_use_post', false );	// used by woocommerce with is_shop()
			$mod = $this->p->util->get_page_mod( $use_post );
			$page_type_id = $this->get_mod_schema_type( $mod, true );	// $get_id = true
			$page_type_url = $this->get_schema_type_url( $page_type_id );

			if ( empty( $page_type_url ) ) {
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
					' itemtype="'.$page_type_url.'"', $head_attr );
			else $head_attr .= ' itemtype="'.$page_type_url.'"';

			$head_attr = trim( $head_attr );

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'returning head attributes: '.$head_attr );

			return $head_attr;
		}

		public function is_head_attributes_enabled() {

			if ( empty( $this->p->options['plugin_head_attr_filter_name'] ) ||
				$this->p->options['plugin_head_attr_filter_name'] === 'none' ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'head attributes disabled for empty option name' );
				return false;
			}

			if ( $this->p->is_avail['amp_endpoint'] && is_amp_endpoint() ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'head attributes disabled for amp endpoint' );
				return false;
			}

			// returns false when the wpsso-schema-json-ld extension is active
			if ( ! apply_filters( $this->p->cf['lca'].'_add_schema_head_attributes', true ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'head attributes disabled by filter' );
				return false;
			}

			return true;
		}

		public function get_mod_schema_type( array &$mod, $get_id = false, $use_mod_opts = true ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$default_key = apply_filters( $lca.'_schema_type_for_default', 'webpage' );
			$schema_types =& $this->get_schema_types_array( true );	// $flatten = true
			$type_id = null;

			/*
			 * Custom Schema Type from Post, Term, or User Meta
			 */
			if ( $use_mod_opts ) {
				if ( ! empty( $mod['obj'] ) ) {	// just in case
					$type_id = $mod['obj']->get_options( $mod['id'], 'schema_type' );

					if ( empty( $type_id ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'custom type_id is empty' );
					} elseif ( $type_id === 'none' ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'custom type_id is disabled with value none' );
					} elseif ( empty( $schema_types[$type_id] ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'custom type_id '.$type_id.' not in schema types' );
						$type_id = null;
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'custom type_id '.$type_id.' from '.$mod['name'].' module' );

				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'custom type_id module object is empty' );
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'custom type_id use_mod_opts argument is false' );

			if ( empty( $type_id ) )
				$is_md_type = false;
			else $is_md_type = true;

			if ( empty( $type_id ) ) {	// if no custom schema type, then use the default settings

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'using plugin settings to determine schema type' );

				if ( $mod['is_home'] ) {	// static or index page
					if ( $mod['is_home_page'] ) {
						$type_id = apply_filters( $lca.'_schema_type_for_home_page',
							$this->get_schema_type_id_for_name( 'home_page' ) );
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'using schema type id '.$type_id.' for home page' );
					} else {
						$type_id = apply_filters( $lca.'_schema_type_for_home_index',
							$this->get_schema_type_id_for_name( 'home_index' ) );
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'using schema type id '.$type_id.' for home index' );
					}
				} elseif ( $mod['is_post'] ) {
					if ( ! empty( $mod['post_type'] ) ) {
						if ( isset( $this->p->options['schema_type_for_'.$mod['post_type']] ) ) {
							$type_id = $this->get_schema_type_id_for_name( $mod['post_type'] );
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'using schema type id '.$type_id.' from option value' );

						} elseif ( ! empty( $schema_types[$mod['post_type']] ) ) {
							$type_id = $mod['post_type'];
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'using schema type id '.$type_id.' from post type name' );

						} else {	// unknown post type
							$type_id = apply_filters( $lca.'_schema_type_for_post_type_unknown', 
								$this->get_schema_type_id_for_name( 'page' ) );
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'using page schema type for unknown post type '.$mod['post_type'] );
						}
					} else {	// post objects without a post_type property
						$type_id = apply_filters( $lca.'_schema_type_for_post_type_empty', 
							$this->get_schema_type_id_for_name( 'page' ) );
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'using page schema type for empty post type' );
					}

				} elseif ( $mod['is_term'] ) {
					$type_id = $this->get_schema_type_id_for_name( 'archive_page' );	// uses archive page schema

				} elseif ( $mod['is_user'] ) {
					$type_id = $this->get_schema_type_id_for_name( 'user_page' );

				} elseif ( SucomUtil::is_archive_page() ) {				// just in case
					$type_id = $this->get_schema_type_id_for_name( 'archive_page' );

				} elseif ( is_search() ) {
					$type_id = $this->get_schema_type_id_for_name( 'search_page' );

				} else {	// everything else
					$type_id = $default_key;
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'using default schema type id '.$default_key );
				}
			}

			$type_id = apply_filters( $this->p->cf['lca'].'_schema_type_id', $type_id, $mod, $is_md_type );

			if ( empty( $type_id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'returning false: schema type id is empty' );
				return false;
			} elseif ( $type_id === 'none' ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'returning false: schema type id is disabled' );
				return false;
			} elseif ( ! isset( $schema_types[$type_id] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'returning false: schema type id '.$type_id.' is unknown' );
				return false;
			} else {
				if ( $get_id ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'returning schema type id '.$type_id );
					return $type_id;
				} else {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'returning schema type value '.$schema_types[$type_id] );
					return $schema_types[$type_id];
				}
			}
		}

		public function &get_schema_types_array( $flatten = true ) {

			if ( ! isset( $this->schema_types['filtered'] ) ) {	// check class property cache

				$lca = $this->p->cf['lca'];
				$cache_exp = (int) apply_filters( $lca.'_cache_expire_schema_types',
					$this->p->options['plugin_types_cache_exp'] );

				$cache_salt = __METHOD__;
				$cache_id = $lca.'_'.md5( $cache_salt );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'transient cache salt '.$cache_salt );

				if ( $cache_exp > 0 ) {
					$this->schema_types = get_transient( $cache_id );	// returns false when not found
					if ( ! empty( $this->schema_types ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'using schema type arrays from transient '.$cache_id );
					}
				}

				if ( ! isset( $this->schema_types['filtered'] ) ) {	// from transient cache or not, check if filtered

					if ( $this->p->debug->enabled )
						$this->p->debug->mark( 'create schema type arrays' );

					$this->schema_types['filtered'] = (array) apply_filters( $lca.'_schema_types', $this->p->cf['head']['schema_type'] );
					$this->schema_types['flattened'] = SucomUtil::array_flatten( $this->schema_types['filtered'] );
					$this->schema_types['parent_index'] = SucomUtil::array_parent_index( $this->schema_types['filtered'] );
					ksort( $this->schema_types['flattened'] );
					ksort( $this->schema_types['parent_index'] );

					if ( $cache_exp > 0 ) {
						set_transient( $cache_id, $this->schema_types, $cache_exp );
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'schema type arrays saved to transient '.
								$cache_id.' ('.$cache_exp.' seconds)');
					}

					if ( $this->p->debug->enabled )
						$this->p->debug->mark( 'create schema type arrays' );

				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'schema type arrays already filtered' );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'using schema type arrays from class property cache' );

			if ( $flatten )
				return $this->schema_types['flattened'];
			else return $this->schema_types['filtered'];
		}

		public function get_schema_types_select( $schema_types = null, $add_none = true ) {
			if ( ! is_array( $schema_types ) )
				$schema_types =& $this->get_schema_types_array( true );	// $flatten = true
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
			$schema_types =& $this->get_schema_types_array( true );	// $flatten = true
			if ( isset( $schema_types[$type_id] ) )
				return $schema_types[$type_id];
			elseif ( is_string( $default_id ) &&
				isset( $schema_types[$default_id] ) )
					return $schema_types[$default_id];
			else return false;
		}

		// returns an array of schema type ids with gparent, parent, child (in that order)
		public function get_schema_type_parents( $child_id, &$parents = array() ) {
			$schema_types =& $this->get_schema_types_array( true );	// $flatten = true
			if ( isset( $this->schema_types['parent_index'][$child_id] ) ) {
				$parent_id = $this->schema_types['parent_index'][$child_id];
				if ( isset( $schema_types[$parent_id] ) ) {
					if ( $parent_id !== $child_id )	{	// prevent infinite loops
						$this->get_schema_type_parents( $parent_id, $parents );
					}
				}
			}
			$parents[] = $child_id;	// add children after parents
			return $parents;
		}

		// returns an array of schema type ids with child, parent, gparent (in that order)
		public function get_schema_type_children( $type_id, &$children = array() ) {
			$children[] = $type_id;	// add children before parents
			$schema_types =& $this->get_schema_types_array( true );	// $flatten = true
			foreach ( $this->schema_types['parent_index'] as $child_id => $parent_id ) {
				if ( $parent_id === $type_id ) {
					$this->get_schema_type_children( $child_id, $children );
				}
			}
			return $children;
		}

		public static function get_schema_type_context( $type_url, $properties = array() ) {
			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $type_url, $match ) )
				// list content and type array keys first, in case they don't already exist
				return array_merge( array( '@context' => null, '@type' => null ), $properties, 
					array( '@context' => $match[1], '@type' => $match[2] ) );
			else return $properties;
		}

		public static function get_schema_type_parts( $type_url ) {
			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $type_url, $match ) )
				return array( $match[1], $match[2] );
			else return array( null, null );	// return two elements
		}

		private function get_schema_type_id_for_name( $type_name, $def_name = null ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'type_name' => $type_name,
					'def_name' => $def_name,
				) );
			}

			if ( empty( $type_name ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema type name is empty' );
				return $def_name;	// just in case
			}

			$schema_types =& $this->get_schema_types_array( true );	// $flatten = true
			$type_id = isset( $this->p->options['schema_type_for_'.$type_name] ) ?	// just in case
				$this->p->options['schema_type_for_'.$type_name] : $def_name;

			if ( empty( $type_id ) || $type_id === 'none' ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'schema type id for '.$type_name.' is empty or disabled' );
				$type_id = $def_name;
			} elseif ( empty( $schema_types[$type_id] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'schema type id '.$type_id.' for '.$type_name.' not in schema types' );
				$type_id = $default_key;
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'schema type id for '.$type_name.' is '.$type_id );

			return $type_id;
		}

		public function get_schema_type_css_classes( $type_id ) {
			$css_classes = '';
			foreach ( $this->get_schema_type_children( $type_id ) as $child )
				$css_classes .= ' schema_type_'.preg_replace( '/[:\/\-\.]+/', '_', $child );
			return trim( $css_classes );
		}

		public function is_schema_type_child_of( $child_id, $parent_id ) {
			$parents = $this->get_schema_type_parents( $child_id );
			return in_array( $parent_id, $parents ) ? true : false;
		}

		public function count_schema_type_children( $type_id ) {
			return count( $this->get_schema_type_children( $type_id ) );
		}

		public function has_json_data_filter( array &$mod, $type_url = '' ) {
			$filter_name = $this->get_json_data_filter( $mod, $type_url );
			return ! empty( $filter_name ) && 
				has_filter( $filter_name ) ? 
					true : false;
		}

		public function get_json_data_filter( array &$mod, $type_url = '' ) {
			if ( empty( $type_url ) )
				$type_url = $this->get_mod_schema_type( $mod );
			return $this->p->cf['lca'].'_json_data_'.SucomUtil::sanitize_hookname( $type_url );
		}

		/*
		 * JSON-LD Script Array
		 *
		 * $mt_og must be passed by reference to assign the schema:type internal meta tags.
		 */
		public function get_json_array( array &$mod, array &$mt_og, $crawler_name ) {

			// pinterest does not (currently) read json markup
			switch ( $crawler_name ) {
				case 'pinterest':
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: '.$crawler_name.' crawler detected' );
					return array();
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build json array' );	// begin timer for json array

			$ret = array();
			$lca = $this->p->cf['lca'];
			$page_type_id = $mt_og['schema:type:id'] = $this->get_mod_schema_type( $mod, true );		// example: article.tech
			$page_type_url = $mt_og['schema:type:url'] = $this->get_schema_type_url( $page_type_id );	// example: https://schema.org/TechArticle

			list(
				$mt_og['schema:type:context'],
				$mt_og['schema:type:name'],
			) = self::get_schema_type_parts( $page_type_url );		// example: https://schema.org, TechArticle

			$page_type_ids = array();
			$page_type_added = array();					// prevent duplicate top-level schema types

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'head schema type is '.$page_type_url.' ('.$page_type_id.')' );

			// include first
			if ( ! empty( $page_type_url ) )
				$page_type_ids[$page_type_id] = true;

			// also include WebSite, Organization, and/or Person on the home page
			if ( $mod['is_home'] ) {	// static or archive page
				$page_type_ids['website'] = $this->p->options['schema_website_json'];
				$page_type_ids['organization'] = $this->p->options['schema_organization_json'];
				$page_type_ids['person'] = $this->p->options['schema_person_json'];
			}

			/*
			 * Array (
			 *	[product] => true
			 *	[website] => true
			 *	[organization] => true
			 *	[person] => false
			 * )
			 */
			$page_type_ids = apply_filters( $lca.'_json_array_schema_page_type_ids', $page_type_ids, $mod );

			foreach ( $page_type_ids as $type_id => $is_enabled ) {

				if ( ! $is_enabled ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'skipping schema type_id '.$type_id.' (disabled)' );
					continue;
				} elseif ( ! empty( $page_type_added[$type_id] ) ) {	// prevent duplicate top-level schema types
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'skipping schema type_id '.$type_id.' (previously added)' );
					continue;
				} else $page_type_added[$type_id] = true;

				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'schema type_id '.$type_id );	// begin timer

				$is_main = method_exists( $mod['obj'], 'get_options' ) ?	// just in case
					$mod['obj']->get_options( $mod['id'], 'schema_is_main' ) : null;

				if ( $is_main === null )
					$is_main = $type_id === $page_type_id ? true : false;

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'is_main entity is '.
						( $is_main ? 'true' : 'false' ) );

				$json_data = $this->get_json_data( $mod, $mt_og, $type_id, $is_main );

				if ( ! empty( $json_data ) && is_array( $json_data ) ) {
					// define the context and type properties for methods / filters 
					// that may not define them or re-defines them incorrectly
					$type_url = $this->get_schema_type_url( $type_id );
					$json_data = self::get_schema_type_context( $type_url, $json_data );

					// format the json data array as a json-ld script
					$ret[] = '<script type="application/ld+json">'.
						$this->p->util->json_format( $json_data ).'</script>'."\n";
				}

				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'schema type_id '.$type_id );	// end timer
			}

			$ret = SucomUtil::a2aa( $ret );	// convert to array of arrays

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $ret );
				$this->p->debug->mark( 'build json array' );	// end timer for json array
			}

			return $ret;
		}

		/*
		 * JSON-LD Data Array
		 */
		public function get_json_data( array &$mod, array &$mt_og, $page_type_id = false, $is_main = false ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( $page_type_id === false )
				$page_type_id = $this->get_mod_schema_type( $mod, true );	// $get_id = true

			$lca = $this->p->cf['lca'];
			$json_data = null;
			$page_type_url = $this->get_schema_type_url( $page_type_id );
			$filter_name = SucomUtil::sanitize_hookname( $page_type_url );
			$parent_urls = array();

			// returns an array of type ids with gparents, parents, child (in that order)
			foreach ( $this->get_schema_type_parents( $page_type_id ) as $type_id )
				$parent_urls[] = $this->get_schema_type_url( $type_id );

			if ( $this->p->debug->enabled )
				$this->p->debug->log_arr( 'schema type_id '.$page_type_id.' parent_urls', $parent_urls );

			foreach ( $parent_urls as $type_url ) {
				$rel_filter_name = SucomUtil::sanitize_hookname( $type_url );
				$has_json_data_filter = has_filter( $lca.'_json_data_'.$rel_filter_name );	// check only once

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'rel_filter_name '.$rel_filter_name );

				// add website, organization, and person markup to home page
				if ( $mod['is_home'] && ! $has_json_data_filter && 
					method_exists( __CLASS__, 'filter_json_data_'.$rel_filter_name ) ) {
					$json_data = call_user_func( array( __CLASS__, 'filter_json_data_'.$rel_filter_name ),
						$json_data, $mod, $mt_og, $page_type_id, false );	// $is_main = always false for method

				} elseif ( $has_json_data_filter ) {
					$json_data = apply_filters( $lca.'_json_data_'.$rel_filter_name,
						$json_data, $mod, $mt_og, $page_type_id, $is_main );

				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'no filters registered for '.$rel_filter_name );
			}

			return $json_data;
		}

		/*
		 * https://schema.org/WebSite for Google
		 */
		public function filter_json_data_https_schema_org_website( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$ret = array(
				'@context' => 'https://schema.org',
				'@type' => 'WebSite',
				'url' => $mt_og['og:url'],
			);

			if ( $name = SucomUtil::get_site_name( $this->p->options, $mod ) )
				$ret['name'] = $name;

			if ( $alt_name = SucomUtil::get_locale_opt( 'schema_alt_name', $this->p->options, $mod ) )
				$ret['alternateName'] = $alt_name;

			if ( $desc = SucomUtil::get_site_description( $this->p->options, $mod ) )
				$ret['description'] = $desc;

			/*
			 * Potential Action (SearchAction, OrderAction, etc.)
			 */
			$action_data = array();

			if ( $search_url = apply_filters( $lca.'_json_ld_search_url', get_bloginfo( 'url' ).'?s={search_term_string}' ) ) {
				$action_data[] = array(
					'@context' => 'https://schema.org',
					'@type' => 'SearchAction',
					'target' => $search_url,
					'query-input' => 'required name=search_term_string',
				);
			}

			$action_data = (array) apply_filters( $lca.'_json_prop_https_schema_org_potentialaction',
				$action_data, $mod, $mt_og, $page_type_id, $is_main );

			if ( ! empty( $action_data ) )
				$ret['potentialAction'] = $action_data;

			return self::return_data_from_filter( $json_data, $ret, $is_main );
		}

		/*
		 * https://schema.org/Organization social markup for Google
		 */
		public function filter_json_data_https_schema_org_organization( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$ret = array();

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'adding single organization data for site' );
			self::add_single_organization_data( $ret, $mod, 'site', 'org_logo_url', false );	// list_element = false

			return self::return_data_from_filter( $json_data, $ret, $is_main );
		}

		/*
		 * https://schema.org/Person social markup for Google
		 */
		public function filter_json_data_https_schema_org_person( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( $mod['is_home'] ) {	// static or index page
				if ( empty( $this->p->options['schema_person_id'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'exiting early: schema_person_id disabled for home page' );
					return $json_data;	// exit early
				} else {
					$user_id = $this->p->options['schema_person_id'];
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'user_id for home page is '.$user_id );
				}
			} elseif ( isset( $mod['is_user'] ) ) {
				$user_id = $mod['id'];
			} else $user_id = false;

			if ( empty( $user_id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: empty user_id' );
				return $json_data;
			}

			$ret = array();

			self::add_single_person_data( $ret, $mod, $user_id, false );	// list_element = false

			// override author's website url and use the open graph url instead
			if ( $mod['is_home'] )
				$ret['url'] = $mt_og['og:url'];

			return self::return_data_from_filter( $json_data, $ret, $is_main );
		}

		// sanitation
		public static function return_data_from_filter( &$json_data, &$ret_data, $is_main = false ) {
			/*
			 * Property:
			 *	mainEntityOfPage as https://schema.org/WebPage
			 */
			if ( $is_main && ! empty( $ret_data['url'] ) )
				$ret_data['mainEntityOfPage'] = $ret_data['url'];

			return empty( $ret_data ) ? $json_data : 
				( $json_data === null ? $ret_data : 
					( is_array( $json_data ) ? array_merge( $json_data, $ret_data ) : 
						$json_data ) );
		}

		// $logo_key can be 'org_logo_url' or 'org_banner_url' (600x60px image) for Articles
		// $org_id can be null, false, 'none', 'site', or number (including 0) -- null and false are the same as 'site'
		public static function add_single_organization_data( &$json_data, &$mod, $org_id = false, $logo_key = 'org_logo_url', $list_element = false ) {

			if ( $org_id === 'none' )
				return 0;

			$wpsso =& Wpsso::get_instance();
			$opts = apply_filters( $wpsso->cf['lca'].'_get_organization_options', false, $mod, $org_id );

			if ( empty( $opts ) ) {	// $opts could be false or empty array
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'adding default organization options for '.$org_id );

				$org_sameas = array();
				foreach ( apply_filters( $wpsso->cf['lca'].'_social_accounts', 
					$wpsso->cf['form']['social_accounts'] ) as $key => $label ) {

					$url = SucomUtil::get_locale_opt( $key, $wpsso->options, $mod );
					if ( empty( $url ) )
						continue;
					if ( $key === 'tc_site' )
						$url = 'https://twitter.com/'.preg_replace( '/^@/', '', $url );
					if ( strpos( $url, '://' ) !== false )
						$org_sameas[] = $url;
				}

				$opts = array(
					'org_type' => 'organization',
					'org_url' => get_bloginfo( 'url' ),
					'org_name' => SucomUtil::get_site_name( $wpsso->options, $mod ),
					'org_desc' => SucomUtil::get_site_description( $wpsso->options, $mod ),
					'org_logo_url' => $wpsso->options['schema_logo_url'],
					'org_banner_url' => $wpsso->options['schema_banner_url'],
					'org_place_id' => 'none',
					'org_sameas' => $org_sameas,
				);
			} elseif ( $wpsso->debug->enabled )
				$wpsso->debug->log( 'have custom organization options for '.$org_id );

			$org_type_id = empty( $opts['org_type'] ) ? 'organization' : $opts['org_type'];
			$org_type_url = $wpsso->schema->get_schema_type_url( $org_type_id, 'organization' );
			$ret = self::get_schema_type_context( $org_type_url );

			// add schema properties from the organization options
			self::add_data_itemprop_from_assoc( $ret, $opts, array(
				'url' => 'org_url',
				'name' => 'org_name',
				'alternateName' => 'org_alt_name',
				'description' => 'org_desc',
				'email' => 'org_email',
				'telephone' => 'org_phone',
			) );

			/*
			 * Organization Logo
			 *
			 * $logo_key can be false, 'org_logo_url' (default), or 'org_banner_url' (600x60px image) for Articles
			 */
			if ( ! empty( $logo_key ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'adding image from '.$logo_key.' option' );
				if ( ! empty( $opts[$logo_key] ) ) {
					if ( ! self::add_single_image_data( $ret['logo'], $opts, $logo_key, false ) )	// list_element = false
						unset( $ret['logo'] );	// prevent null assignment
				}
				if ( empty( $ret['logo'] ) ) {
					if ( $wpsso->debug->enabled )
						$wpsso->debug->log( 'organization '.$logo_key.' image is missing and required' );
					if ( is_admin() && ( ! $mod['is_post'] || $mod['post_status'] === 'publish' ) ) {
						switch ( $logo_key ) {
							case 'org_logo_url':
								$wpsso->notice->err( sprintf( __( 'The "%1$s" Organization Logo Image is missing and required for the Schema %2$s markup.', 'wpsso' ), $ret['name'], $org_type_url ) );
								break;
							case 'org_banner_url':
								$wpsso->notice->err( sprintf( __( 'The "%1$s" Organization Banner (600x60) is missing and required for the Schema %2$s markup.', 'wpsso' ), $ret['name'], $org_type_url ) );
								break;
						}
					}
				}
			}

			/*
			 * Location
			 */
			if ( isset( $opts['org_place_id'] ) && $opts['org_place_id'] !== 'none' ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'adding single place data for '.$opts['org_place_id'] );
				if ( ! self::add_single_place_data( $ret['location'], $mod, $opts['org_place_id'], false ) )	// list_element = false
					unset( $ret['location'] );	// prevent null assignment
			}

			/*
			 * Google Knowledge Graph
			 */
			if ( ! empty( $opts['org_sameas'] ) && is_array( $opts['org_sameas'] ) ) {
				foreach ( $opts['org_sameas'] as $url )
					if ( ! empty( $url ) )	// just in case
						$ret['sameAs'][] = esc_url( $url );
			}

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;

			return 1;
		}

		// $place_id can be null, false, 'none', 'site', or number (including 0) -- null and false are the same as 'site'
		public static function add_single_place_data( &$json_data, &$mod, $place_id = false, $list_element = false ) {

			if ( $place_id === 'none' )
				return 0;

			$wpsso =& Wpsso::get_instance();
			$opts = apply_filters( $wpsso->cf['lca'].'_get_place_options', false, $mod, $place_id );

			if ( empty( $opts ) ) {	// $opts could be false or empty array
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty place options' );
				return 0;
			}

			// local business is a sub-type of place
			// use the local business schema type if we have one
			$place_type_id = empty( $opts['place_business_type'] ) || 
				$opts['place_business_type'] === 'none' ? 'place' : $opts['place_business_type'];
			$place_type_url = $wpsso->schema->get_schema_type_url( $place_type_id, 'place' );
			$ret = self::get_schema_type_context( $place_type_url );

			$address = array();
			$geo = array();
			$opening_hours = array();

			// add schema properties from the place options
			self::add_data_itemprop_from_assoc( $ret, $opts, array(
				'url' => 'place_url',
				'name' => 'place_name',
				'alternateName' => 'place_alt_name',
				'description' => 'place_desc',
				'telephone' => 'place_phone',
			) );

			/*
			 * Property:
			 *	address as https://schema.org/PostalAddress
			 */
			if ( self::add_data_itemprop_from_assoc( $address, $opts, array(
				'name' => 'place_name', 
				'streetAddress' => 'place_streetaddr', 
				'postOfficeBoxNumber' => 'place_po_box_number', 
				'addressLocality' => 'place_city',
				'addressRegion' => 'place_state',
				'postalCode' => 'place_zipcode',
				'addressCountry' => 'place_country',
			) ) ) $ret['address'] = self::get_schema_type_context( 'https://schema.org/PostalAddress', $address );

			/*
			 * Property:
			 *	geo as https://schema.org/GeoCoordinates
			 */
			if ( self::add_data_itemprop_from_assoc( $geo, $opts, array(
				'elevation' => 'place_altitude', 
				'latitude' => 'place_latitude',
				'longitude' => 'place_longitude',
			) ) ) $ret['geo'] = self::get_schema_type_context( 'https://schema.org/GeoCoordinates', $geo );

			/*
			 * Property:
			 *	openingHoursSpecification as https://schema.org/OpeningHoursSpecification
			 */
			foreach ( $wpsso->cf['form']['weekdays'] as $day => $label ) {
				if ( ! empty( $opts['place_day_'.$day] ) ) {
					$dayofweek = array(
						'@context' => 'https://schema.org',
						'@type' => 'OpeningHoursSpecification',
						'dayOfWeek' => $label,
					);
					foreach ( array(
						'opens' => 'place_'.$day.'_open',
						'closes' => 'place_'.$day.'_close',
						'validFrom' => 'place_season_from_date',
						'validThrough' => 'place_season_to_date',
					) as $prop_name => $key ) {
						if ( isset( $opts[$key] ) )
							$dayofweek[$prop_name] = $opts[$key];
					}
					$opening_hours[] = $dayofweek;
				}
			}

			if ( ! empty( $opening_hours ) )
				$ret['openingHoursSpecification'] = $opening_hours;

			/*
			 * FoodEstablishment schema type properties
			 */
			if ( ! empty( $opts['place_business_type'] ) &&
				$opts['place_business_type'] !== 'none' ) {

				if ( $wpsso->schema->is_schema_type_child_of( $opts['place_business_type'], 'food.establishment' ) ) {
					foreach ( array(
						'acceptsReservations' => 'place_accept_res',
						'menu' => 'place_menu_url',
					) as $prop_name => $key ) {
						if ( $key === 'place_accept_res' )
							$ret[$prop_name] = empty( $opts[$key] ) ? 'false' : 'true';
						elseif ( isset( $opts[$key] ) )
							$ret[$prop_name] = $opts[$key];
					}
				}
			}

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;

			return 1;
		}

		public static function add_single_event_data( &$json_data, &$mod, $event_id = false, $list_element = false ) {

			if ( $event_id === 'none' )
				return 0;

			$wpsso =& Wpsso::get_instance();
			$opts = apply_filters( $wpsso->cf['lca'].'_get_event_options', false, $mod, $event_id );

			if ( empty( $opts ) ) {	// $opts could be false or empty array
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty event options' );
				return 0;
			}

			$event_type_id = empty( $opts['event_type'] ) ? 'event' : $opts['event_type'];
			$event_type_url = $wpsso->schema->get_schema_type_url( $event_type_id, 'event' );
			$ret = self::get_schema_type_context( $event_type_url );

			self::add_data_itemprop_from_assoc( $ret, $opts, array(
				'startDate' => 'event_start_date',
				'endDate' => 'event_end_date',
			) );

			if ( ! empty( $opts['event_organizer_person_id'] ) ) {
				if ( ! self::add_single_person_data( $ret['organizer'], $mod, $opts['event_organizer_person_id'], false ) ) 	// $list_element = false
					unset( $ret['organizer'] );	// prevent null assignment
			}

			if ( ! empty( $opts['event_place_id'] ) ) {
				if ( ! self::add_single_place_data( $ret['location'], $mod, $opts['event_place_id'], false ) )	// $list_element = false
					unset( $ret['location'] );	// prevent null assignment
			}

			if ( is_array( $opts['event_offers'] ) ) {
				foreach ( $opts['event_offers'] as $event_offer ) {

					// setup the offer with basic itemprops
					if ( is_array( $event_offer ) &&	// just in case
						( $offer = self::get_data_itemprop_from_assoc( $event_offer, array( 
							'price' => 'offer_price',
							'priceCurrency' => 'offer_price_currency',
							'availability' => 'offer_availability',
					) ) ) !== false ) {

						// add the complete offer
						$ret['offers'][] = self::get_schema_type_context( 'https://schema.org/Offer', $offer );
					}
				}
			}

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;

			return 1;
		}

		// $user_id is optional and takes precedence over the $mod post_author value
		public static function add_author_coauthor_data( &$json_data, $mod, $user_id = false ) {

			$wpsso =& Wpsso::get_instance();
			if ( $wpsso->debug->enabled )
				$wpsso->debug->mark();

			$authors_added = 0;
			$coauthors_added = 0;

			if ( empty( $user_id ) && 
				isset( $mod['post_author'] ) )
					$user_id = $mod['post_author'];

			if ( empty( $user_id ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty user_id / post_author' );
				return 0;
			}

			// single author
			$authors_added += self::add_single_person_data( $json_data['author'], $mod, $user_id, false );	// list_element = false

			// list of contributors / co-authors
			if ( ! empty( $mod['post_coauthors'] ) )
				foreach ( $mod['post_coauthors'] as $author_id )
					$coauthors_added += self::add_single_person_data( $json_data['contributor'], $mod, $author_id, true );	// list_element = true

			foreach ( array( 'author', 'contributor' ) as $itemprop )
				if ( empty( $json_data[$itemprop] ) )
					unset( $json_data[$itemprop] );	// prevent null assignment

			return $authors_added + $coauthors_added;	// return count of authors and coauthors added
		}

		// $user_id is required here
		public static function add_single_person_data( &$json_data, &$mod, $user_id, $list_element = true ) {

			if ( $user_id === 'none' )
				return 0;

			$wpsso =& Wpsso::get_instance();
			$opts = apply_filters( $wpsso->cf['lca'].'_get_person_options', false, $mod, $user_id );

			if ( empty( $opts ) ) {	// $opts could be false or empty array

				if ( empty( $user_id ) ) {
					if ( $wpsso->debug->enabled )
						$wpsso->debug->log( 'exiting early: empty user_id' );
					return 0;
				} elseif ( empty( $wpsso->m['util']['user'] ) ) {
					if ( $wpsso->debug->enabled )
						$wpsso->debug->log( 'exiting early: empty user module' );
					return 0;
				} else {
					if ( $wpsso->debug->enabled )
						$wpsso->debug->log( 'getting user module for user_id '.$user_id );
					$user_mod = $wpsso->m['util']['user']->get_mod( $user_id );
				}

				$user_desc = $user_mod['obj']->get_options_multi( $user_id, array( 'schema_desc', 'og_desc' ) );
				if ( empty( $user_desc ) )
					$user_desc = $user_mod['obj']->get_author_meta( $user_id, 'description' );

				$user_sameas = array();
				foreach ( WpssoUser::get_user_id_contact_methods( $user_id ) as $cm_id => $cm_label ) {
					$url = $user_mod['obj']->get_author_meta( $user_id, $cm_id );
					if ( empty( $url ) )
						continue;
					if ( $cm_id === $wpsso->options['plugin_cm_twitter_name'] )
						$url = 'https://twitter.com/'.preg_replace( '/^@/', '', $url );
					if ( strpos( $url, '://' ) !== false )
						$user_sameas[] = $url;
				}

				$opts = array(
					'person_type' => 'person',
					'person_url' => $user_mod['obj']->get_author_website( $user_id, 'url' ),
					'person_name' => $user_mod['obj']->get_author_meta( $user_id, $wpsso->options['schema_author_name'] ),
					'person_desc' => $user_desc,
					'person_og_image' => $user_mod['obj']->get_og_image( 1, $wpsso->cf['lca'].'-schema', $user_id, false ),
					'person_sameas' => $user_sameas,
				);
			}

			if ( $wpsso->debug->enabled )
				$wpsso->debug->log_arr( 'person options', $opts );

			$person_type_id = empty( $opts['person_type'] ) ? 'person' : $opts['person_type'];	// person or patient
			$person_type_url = $wpsso->schema->get_schema_type_url( $person_type_id, 'person' );
			$ret = self::get_schema_type_context( $person_type_url );

			self::add_data_itemprop_from_assoc( $ret, $opts, array(
				'url' => 'person_url',
				'name' => 'person_name',
				'description' => 'person_desc',
				'email' => 'person_email',
				'telephone' => 'person_phone',
			) );

			/*
			 * Images
			 */
			if ( ! empty( $opts['person_og_image'] ) ) {
				if ( ! self::add_image_list_data( $ret['image'], $opts['person_og_image'], 'og:image' ) )
					unset( $ret['image'] );	// prevent null assignment
			}

			/*
			 * Google Knowledge Graph
			 */
			if ( ! empty( $opts['person_sameas'] ) &&
				is_array( $opts['person_sameas'] ) )
					foreach ( $opts['person_sameas'] as $url )
						$ret['sameAs'][] = esc_url( $url );

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

			$media_url = SucomUtil::get_mt_media_url( $opts, $prefix );

			if ( empty( $media_url ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: '.$prefix.' URL values are empty' );
				return 0;	// return count of images added
			}

			$ret = array(
				'@context' => 'https://schema.org',
				'@type' => 'ImageObject',
				'url' => esc_url( $media_url ),
			);

			foreach ( array( 'width', 'height' ) as $prop )
				if ( isset( $opts[$prefix.':'.$prop] ) &&
					$opts[$prefix.':'.$prop] > 0 )	// just in case
						$ret[$prop] = $opts[$prefix.':'.$prop];

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;	// add an item to the list

			return 1;	// return count of images added
		}

		public static function add_data_itemprop_from_assoc( array &$json_data, array &$assoc, array $names ) {
			$itemprop_added = 0;
			foreach ( $names as $itemprop_name => $key_name ) {
				if ( isset( $assoc[$key_name] ) && $assoc[$key_name] !== '' ) {	// exclude empty strings
					if ( is_string( $assoc[$key_name] ) &&
						strpos( $assoc[$key_name], '://' ) )
							$json_data[$itemprop_name] = esc_url( $assoc[$key_name] );
					else $json_data[$itemprop_name] = $assoc[$key_name];
					$itemprop_added++;
				}
			}
			return $itemprop_added;
		}

		public static function get_data_itemprop_from_assoc( array &$assoc, array $names ) {
			foreach ( $names as $itemprop_name => $key_name ) {
				if ( isset( $assoc[$key_name] ) && $assoc[$key_name] !== '' ) {	// exclude empty strings
					$ret[$itemprop_name] = $assoc[$key_name];
				}
			}
			return empty( $ret ) ? false : $ret;
		}

		// QuantitativeValue (width, height, length, depth, weight)
		// unitCodes from http://wiki.goodrelations-vocabulary.org/Documentation/UN/CEFACT_Common_Codes
		public static function add_data_quant_from_assoc( array &$json_data, array &$assoc, array $names ) {
			foreach ( $names as $itemprop_name => $key_name ) {
				if ( isset( $assoc[$key_name] ) && $assoc[$key_name] !== '' ) {	// exclude empty strings
					switch ( $itemprop_name ) {
						case 'length':	// QuantitativeValue does not have a length itemprop
							$json_data['additionalProperty'][] = array(
								'@context' => 'https://schema.org',
								'@type' => 'PropertyValue',
								'propertyID' => $itemprop_name,
								'value' => $assoc[$key_name],
								'unitCode' => 'CMT',
							);
							break;
						default:
							$json_data[$itemprop_name] = array(
								'@context' => 'https://schema.org',
								'@type' => 'QuantitativeValue',
								'value' => $assoc[$key_name],
								'unitCode' => ( $itemprop_name === 'weight' ? 'KGM' : 'CMT' ),
							);
							break;
					}
				}
			}
		}

		/*
		 * Meta Name Array
		 */
		public function get_meta_array( array &$mod, array &$mt_og, $crawler_name ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			// returns false when the wpsso-schema-json-ld extension is active
			if ( ! apply_filters( $this->p->cf['lca'].'_add_schema_meta_array', true ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema meta array disabled' );
				return array();	// empty array
			}

			$mt_schema = array();
			$lca = $this->p->cf['lca'];
			$max = $this->p->util->get_max_nums( $mod, 'schema' );
			$page_type_id = $this->get_mod_schema_type( $mod, true );	// $get_id = true
			$page_type_url = $this->get_schema_type_url( $page_type_id );
			$size_name = $this->p->cf['lca'].'-schema';

			$this->add_mt_schema_from_og( $mt_schema, $mt_og, array(
				'url' => 'og:url',
				'name' => 'og:title',
			) );

			if ( ! empty( $this->p->options['add_meta_itemprop_description'] ) )
				$mt_schema['description'] = $this->p->webpage->get_description( $this->p->options['schema_desc_len'], '...', $mod, true,
					false, true, 'schema_desc' );	// $add_hashtags = false, $encode = true, $md_idx = schema_desc

			switch ( $page_type_url ) {
				case 'https://schema.org/BlogPosting':
					$size_name = $this->p->cf['lca'].'-schema-article';
					// no break - add date published and modified

				case 'https://schema.org/WebPage':
					$this->add_mt_schema_from_og( $mt_schema, $mt_og, array(
						'datePublished' => 'article:published_time',
						'dateModified' => 'article:modified_time',
					) );
					break;
			}


			if ( $crawler_name === 'pinterest' ) {	// prevents pinterest from showing duplicate images
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'skipping images: prevent duplicates for '.$crawler_name.' crawler' );
			} elseif ( $this->is_noscript_enabled( $crawler_name ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'skipping images: noscript is enabled for '.$crawler_name );
			} elseif ( empty( $this->p->options['add_meta_itemprop_image'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'skipping images: meta itemprop image is disabled' );
			} else {	// add single image meta tags (no width or height)
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'getting images for '.$page_type_url );

				$og_image = $this->p->og->get_all_images( $max['schema_img_max'],
					$size_name, $mod, true, 'schema' );	// $md_pre = 'schema'

				if ( empty( $og_image ) && $mod['is_post'] ) 
					$og_image = $this->p->media->get_default_image( 1, $size_name, true );

				foreach ( $og_image as $image )
					$mt_schema['image'][] = SucomUtil::get_mt_media_url( $image, 'og:image' );
			}

			return apply_filters( $lca.'_schema_meta_itemprop', $mt_schema, $mod, $mt_og, $page_type_id );
		}

		public function add_mt_schema_from_og( array &$mt_schema, array &$assoc, array $names ) {
			foreach ( $names as $itemprop_name => $key_name )
				if ( ! empty( $this->p->options['add_meta_itemprop_'.$itemprop_name] )
					&& ! empty( $assoc[$key_name] ) )
						$mt_schema[$itemprop_name] = $assoc[$key_name];
		}

		/*
		 * NoScript Meta Name Array
		 */
		public function get_noscript_array( array &$mod, array &$mt_og, $crawler_name ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! self::is_noscript_enabled( $crawler_name ) )
				return array();	// empty array

			$ret = array();
			$lca = $this->p->cf['lca'];
			$max = $this->p->util->get_max_nums( $mod, 'schema' );
			$page_type_id = $this->get_mod_schema_type( $mod, true );	// $get_id = true
			$page_type_url = $this->get_schema_type_url( $page_type_id );
			$size_name = $this->p->cf['lca'].'-schema';
			$og_type = $mt_og['og:type'];

			switch ( $page_type_url ) {
				case 'https://schema.org/BlogPosting':
					$size_name = $this->p->cf['lca'].'-schema-article';
					// no break - get the webpage author list as well

				case 'https://schema.org/WebPage':
					$ret = array_merge( $ret, $this->get_author_list_noscript( $mod ) );
					break;
			}

			if ( $crawler_name === 'pinterest' ) {	// prevents pinterest from showing duplicate images
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'skipping images: prevent duplicates for '.$crawler_name.' crawler' );
			} else {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'getting images for '.$page_type_url );
	
				$og_image = $this->p->og->get_all_images( $max['schema_img_max'], 
					$size_name, $mod, true, 'schema' );	// $md_pre = 'schema'
	
				if ( empty( $og_image ) && $mod['is_post'] ) 
					$og_image = $this->p->media->get_default_image( 1, $size_name, true );
	
				foreach ( $og_image as $image )
					$ret = array_merge( $ret, $this->get_single_image_noscript( $mod, $image ) );
			}

			// example: product:rating:average
			if ( isset( $mt_og[$og_type.':rating:average'] ) )
				$ret = array_merge( $ret, $this->get_aggregate_rating_noscript( $mod, $og_type, $mt_og ) );

			return apply_filters( $this->p->cf['lca'].'_schema_noscript_array', $ret, $mod, $mt_og, $page_type_id );
		}

		public function is_noscript_enabled( $crawler_name = false ) {

			if ( $this->p->is_avail['amp_endpoint'] && is_amp_endpoint() ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'noscript disabled for amp endpoint' );
				return false;
			}

			if ( $crawler_name === false )
				$crawler_name = SucomUtil::crawler_name();

			$is_enabled = empty( $this->p->options['schema_add_noscript'] ) ? false : true;

			// returns false when the wpsso-schema-json-ld extension is active
			if ( ! apply_filters( $this->p->cf['lca'].'_add_schema_noscript_array', $is_enabled, $crawler_name ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'noscript disabled by option or filter for '.$crawler_name );
				return false;
			}

			return true;
		}

		public function get_single_image_noscript( array &$mod, &$mixed, $prefix = 'og:image' ) {

			$mt_image = array();

			if ( empty( $mixed ) ) {
				return array();

			} elseif ( is_array( $mixed ) ) {
				$media_url = SucomUtil::get_mt_media_url( $mixed, $prefix );

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
					array( array( '<noscript itemprop="image" itemscope itemtype="https://schema.org/ImageObject">'."\n" ) ),
					$mt_image,
					array( array( '</noscript>'."\n" ) )
				);
			} else return array();
		}

		public function get_aggregate_rating_noscript( array &$mod, $og_type, array $mt_og ) {

			// aggregate rating needs at least one rating or review count
			if ( ! isset( $mt_og[$og_type.':rating:average'] ) ||
				( ! isset( $mt_og[$og_type.':rating:count'] ) && 
					! isset( $mt_og[$og_type.':review:count'] ) ) ) {

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: missing rating or review count' );
				return array();
			}

			return array_merge(
				array( array( '<noscript itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">'."\n" ) ),
				$this->p->head->get_single_mt( 'meta', 'itemprop',
					'aggregaterating.ratingValue', $mt_og[$og_type.':rating:average'], '', $mod ),
				( empty( $mt_og[$og_type.':rating:count'] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop',
						'aggregaterating.ratingCount', $mt_og[$og_type.':rating:count'], '', $mod ) ),
				( empty( $mt_og[$og_type.':rating:worst'] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop',
						'aggregaterating.worstRating', $mt_og[$og_type.':rating:worst'], '', $mod ) ),
				( empty( $mt_og[$og_type.':rating:best'] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop',
						'aggregaterating.bestRating', $mt_og[$og_type.':rating:best'], '', $mod ) ),
				( empty( $mt_og[$og_type.':review:count'] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop', 
						'aggregaterating.reviewCount', $mt_og[$og_type.':review:count'], '', $mod ) ),
				array( array( '</noscript>'."\n" ) )
			);
		}

		public function get_author_list_noscript( array &$mod ) {

			if ( empty( $mod['post_author'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: empty post_author' );
				return array();
			}

			$ret = $this->get_single_author_noscript( $mod, $mod['post_author'], 'author' );

			if ( ! empty( $mod['post_coauthors'] ) )
				foreach ( $mod['post_coauthors'] as $author_id )
					$ret = array_merge( $ret, $this->get_single_author_noscript( $mod, $author_id, 'contributor' ) );

			return $ret;
		}

		public function get_single_author_noscript( array &$mod, $author_id = 0, $itemprop = 'author' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
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
			} else {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'getting user_mod for author id '.$author_id );
				$user_mod = $this->p->m['util']['user']->get_mod( $author_id );
			}

			$url = $user_mod['obj']->get_author_website( $author_id, 'url' );
			$name = $user_mod['obj']->get_author_meta( $author_id, $this->p->options['schema_author_name'] );
			$desc = $user_mod['obj']->get_options_multi( $author_id, array( 'schema_desc', 'og_desc' ) );

			if ( empty( $desc ) )
				$desc = $user_mod['obj']->get_author_meta( $author_id, 'description' );

			$mt_author = array_merge(
				( empty( $url ) ? array() : $this->p->head->get_single_mt( 'meta',
					'itemprop', $itemprop.'.url', $url, '', $user_mod ) ),
				( empty( $name ) ? array() : $this->p->head->get_single_mt( 'meta',
					'itemprop', $itemprop.'.name', $name, '', $user_mod ) ),
				( empty( $desc ) ? array() : $this->p->head->get_single_mt( 'meta',
					'itemprop', $itemprop.'.description', $desc, '', $user_mod ) )
			);

			// optimize by first checking if the meta tag is enabled
			if ( ! empty( $this->p->options['add_meta_itemprop_author.image'] ) ) {

				// get_og_images() also provides filter hooks for additional image ids and urls
				$size_name = $this->p->cf['lca'].'-schema';
				$og_image = $user_mod['obj']->get_og_image( 1, $size_name, $author_id, false );	// $check_dupes = false
	
				foreach ( $og_image as $image ) {
					$image_url = SucomUtil::get_mt_media_url( $image, 'og:image' );
					if ( ! empty( $image_url ) ) {
						$mt_author = array_merge( $mt_author, $this->p->head->get_single_mt( 'meta',
							'itemprop', $itemprop.'.image', $image_url, '', $user_mod ) );
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
					array( array( '<noscript itemprop="'.$itemprop.'" itemscope itemtype="https://schema.org/Person">'."\n" ) ),
					$mt_author,
					array( array( '</noscript>'."\n" ) )
				);
			} else return array();
		}
	}
}

?>
