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
		protected $schema_types = null;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes' => 4,
			), 5 );

			// only filter the head attribute if we have one
			if ( ! empty( $this->p->options['plugin_head_attr_filter_name'] ) &&
				$this->p->options['plugin_head_attr_filter_name'] !== 'none' ) {

				add_action( 'add_head_attributes', 
					array( &$this, 'add_head_attributes' ) );

				$prio = empty( $this->p->options['plugin_head_attr_filter_prio'] ) ? 
					100 : $this->p->options['plugin_head_attr_filter_prio'];

				add_filter( $this->p->options['plugin_head_attr_filter_name'], 
					array( &$this, 'filter_head_attributes' ), $prio, 1 );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'head attributes filter skipped: plugin_head_attr_filter_name option is empty' );
		}

		public function filter_plugin_image_sizes( $sizes, $wp_obj, $mod_name, $crawler_name ) {

			$sizes['schema_img'] = array(
				'name' => 'schema',
				'label' => _x( 'Google / Schema Image',
					'image size label', 'wpsso' ),
			);

			if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) &&
				$crawler_name === 'pinterest' )
					$sizes['schema_img']['prefix'] = 'rp_img';

			return $sizes;
		}

		public function add_head_attributes() {
			echo apply_filters( $this->p->options['plugin_head_attr_filter_name'], '' );
		}

		public function filter_head_attributes( $head_attr = '' ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];

			// filter_head_attributes() is disabled when the wpsso-schema-json-ld extension is active
			if ( ! apply_filters( $lca.'_add_schema_head_attributes', true ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema head attributes disabled' );
				return $head_attr;
			}

			$head_type = $this->get_head_item_type();
			if ( empty( $head_type ) ) {
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
					' itemtype="'.$head_type.'"', $head_attr );
			else $head_attr .= ' itemtype="'.$head_type.'"';

			return trim( $head_attr );
		}

		public function get_head_item_type( $use_post = false, $post_obj = false, $ret_key = false, $use_mod = true ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$schema_types = $this->get_schema_types();
			$default_key = apply_filters( $this->p->cf['lca'].'_schema_type_for_default', 'webpage' );
			$type_key = null;

			list( $id, $mod_name, $mod_obj ) = $this->p->util->get_object_id_mod( $use_post );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'id is '.$id );
				$this->p->debug->log( 'mod_name is '.$mod_name );
			}
	
			if ( $use_mod ) {
				if ( ! empty( $id ) && ! empty( $mod_name ) ) {
					$type_key = $this->p->util->get_mod_options( $mod_name, $id, 'schema_type' );
	
					if ( empty( $type_key ) || $type_key === 'none' ) {
						$type_key = null;

					} elseif ( empty( $schema_types[$type_key] ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'custom type key '.$type_key.' not in schema types' );
						$type_key = null;
	
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'custom type key '.$type_key.' from module '.$mod_name );
				}
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'ignoring module option for custom type key' );

			if ( empty( $type_key ) ) {

				if ( is_front_page() ) {
					$type_key = apply_filters( $this->p->cf['lca'].'_schema_type_for_home_page',
						( empty( $this->p->options['schema_type_for_home_page'] ) ?
							'website' : $this->p->options['schema_type_for_home_page'] ) );

				// possible values are post, taxonomy, and user
				} elseif ( $mod_name === 'post' ) {

					if ( ! is_object( $post_obj ) )
						$post_obj = $this->p->util->get_post_object( $id );
	
					if ( ! empty( $post_obj->post_type ) ) {
						if ( isset( $this->p->options['schema_type_for_'.$post_obj->post_type] ) ) {
							$type_key = $this->p->options['schema_type_for_'.$post_obj->post_type];
	
							if ( empty( $type_key ) || $type_key === 'none' ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'schema type for '.$post_obj->post_type.' post type is disabled' );
								$type_key = null;
	
							} elseif ( empty( $schema_types[$type_key] ) ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'schema type key '.$type_key.' not found in schema types' );
								$type_key = $default_key;
							}
	
						} elseif ( ! empty( $schema_types[$post_obj->post_type] ) ) {
							$type_key = $post_obj->post_type;

						// unknown post type
						} else $type_key = $default_key;

					// post objects without a post_type property
					} else $type_key = $default_key;

				} elseif ( $this->p->util->force_default_author( $use_post, 'og' ) ) {
					$type_key = $default_key;

				// default value for all other webpages
				} else $type_key = $default_key;
			}

			$type_key = apply_filters( $this->p->cf['lca'].'_schema_head_type', $type_key, $use_post, $post_obj );

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'schema type key is "'.$type_key.'"' );

			if ( isset( $schema_types[$type_key] ) ) {
				if ( $ret_key !== false )	// return the type key, not its associated value
					return $type_key;
				else return $schema_types[$type_key];
			} else return false;
		}

		public function get_head_type_context( $use_post = false, $post_obj = false ) {
			return self::get_item_type_context( $this->get_head_item_type( $use_post, $post_obj ) );
		}

		public static function get_item_type_context( $item_type, $properties = array() ) {
			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $item_type, $match ) )
				return array_merge( array(
					'@context' => $match[1],
					'@type' => $match[2],
				), $properties );
			else return array();
		}

		public function get_schema_types() {
			if ( $this->schema_types === null )
				$this->schema_types = (array) apply_filters( $this->p->cf['lca'].'_schema_types', 
					$this->p->cf['head']['schema_type'] );
			return $this->schema_types;
		}

		public function get_schema_types_select() {
			$schema_types = $this->get_schema_types();
			$select = array( 'none' => '[none]' );
			foreach ( $schema_types as $key => $label )
				$select[$key] = $key.' ('.$label.')';
			return $select;
		}

		// $item_type examples: http_schema_org_webpage or http://schema.org/WebPage
		public function has_json_data_filter( $item_type = '', $use_post = false, $post_obj = false ) {
			$filter_name = $this->get_json_data_filter( $item_type, $use_post, $post_obj );
			return ! empty( $filter_name ) && 
				has_filter( $filter_name ) ? 
					true : false;
		}

		public function get_json_data_filter( $item_type = '', $use_post = false, $post_obj = false ) {
			if ( empty( $item_type ) )
				$item_type = $this->get_head_item_type( $use_post, $post_obj );
			return $this->p->cf['lca'].'_json_data_'.SucomUtil::sanitize_hookname( $item_type );
		}

		/*
		 * JSON-LD Script Array
		 */
		public function get_json_array( $use_post, &$post_obj, &$mt_og, $post_id, $user_id ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build json array' );	// begin timer for json array

			$ret = array();
			$lca = $this->p->cf['lca'];
			$head_type = $this->get_head_item_type( $use_post, $post_obj );
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'schema item type is '.$head_type );

			list( $id, $mod_name, $mod_obj ) = $this->p->util->get_object_id_mod( $use_post );

			// include WebSite, Organization, and/or Person on the home page
			if ( is_front_page() )
				$item_types = array(
					'http://schema.org/WebSite' => $this->p->options['schema_website_json'],
					'http://schema.org/Organization' => $this->p->options['schema_organization_json'],
					'http://schema.org/Person' => $this->p->options['schema_person_json'],
				);
			else $item_types = array();

			if ( ! empty( $head_type ) && 
				! isset( $item_types[$head_type] ) )
					$item_types[$head_type] = true;

			foreach ( $item_types as $item_type => $is_enabled ) {

				$json_data = null;
				$type_filter_name = SucomUtil::sanitize_hookname( $item_type );

				if ( $this->p->debug->enabled )
					$this->p->debug->mark( $type_filter_name );	// begin timer for json array

				if ( ! empty( $id ) && ! empty( $mod_name ) )
					// returns null if index key is not set in the options array
					$is_main = $this->p->util->get_mod_options( $mod_name, $id, 'schema_is_main' );
				else $is_main = null;

				if ( $is_main === null ) {
					if ( $item_type === $head_type )
						$is_main = true;
					else $is_main = false;
				}

				$is_main = apply_filters( $lca.'_json_is_main_entity', 
					$is_main, $use_post, $post_obj, $mt_og, $post_id, $user_id, $head_type );

				// include WebSite, Organization, and/or Person on the home page
				// if there isn't a hook for that filter (from WPSSO JSON, for example)
				if ( is_front_page() && 
					method_exists( __CLASS__, 'filter_json_data_'.$type_filter_name ) && 
						! has_filter( $lca.'_json_data_'.$type_filter_name ) ) {

					if ( $is_enabled )
						$json_data = call_user_func( array( __CLASS__, 'filter_json_data_'.$type_filter_name ),
							$json_data, $use_post, $post_obj, $mt_og, $post_id, $user_id,
								$head_type, false );	// $is_main = false when called directly

				// call http_schema_org_item_type as a generic / common data filter first
				} else foreach ( array( 'http_schema_org_item_type', $type_filter_name ) as $filter_name ) {

					if ( has_filter( $lca.'_json_data_'.$filter_name ) ) {
						if ( apply_filters( $lca.'_add_json_'.$filter_name, $is_enabled ) )
							$json_data = apply_filters( $lca.'_json_data_'.$filter_name,
								$json_data, $use_post, $post_obj, $mt_og, $post_id, $user_id,
									$head_type, $is_main );
						elseif ( $this->p->debug->enabled )
							$this->p->debug->log( $filter_name.' filter is disabled' );
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'no filters registered for '.$filter_name );
				}

				if ( ! empty( $json_data ) && is_array( $json_data ) )
					$ret[] = "<script type=\"application/ld+json\">".
						$this->p->util->json_format( $json_data )."</script>\n";

				if ( $this->p->debug->enabled )
					$this->p->debug->mark( $type_filter_name );	// end timer for json array
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
		public function filter_json_data_http_schema_org_website( $json_data, 
			$use_post, $post_obj, $mt_og, $post_id, $user_id, $head_type, $is_main ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'WebSite',
				'url' => $mt_og['og:url'],
				'name' => $this->p->og->get_site_name( $post_id ),
			);

			if ( ! empty( $this->p->options['schema_alt_name'] ) )
				$ret['alternateName'] = $this->p->options['schema_alt_name'];

			$desc = $this->p->og->get_site_desc( $post_id );
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
		public function filter_json_data_http_schema_org_organization( $json_data, 
			$use_post, $post_obj, $mt_og, $post_id, $user_id, $head_type, $is_main ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$ret = array();

			self::add_single_organization_data( $ret, $post_id );	// list_element = false

			if ( $is_main )
				self::add_main_entity_data( $ret, $ret['url'] );

			if ( is_front_page() ) {
				// add the sameAs social profile links
				foreach ( array(
					'seo_publisher_url',
					'fb_publisher_url',
					'linkedin_publisher_url',
					'tc_site',
				) as $key ) {
					$url = isset( $this->p->options[$key] ) ?
						trim( $this->p->options[$key] ) : '';
					if ( empty( $url ) )
						continue;
					if ( $key === 'tc_site' )
						$url = 'https://twitter.com/'.preg_replace( '/^@/', '', $url );
					if ( strpos( $url, '://' ) !== false )
						$ret['sameAs'][] = esc_url( $url );
				}
			}
	
			return self::return_data_from_filter( $json_data, $ret );
		}

		/*
		 * http://schema.org/Person social markup for Google
		 */
		public function filter_json_data_http_schema_org_person( $json_data, 
			$use_post, $post_obj, $mt_og, $post_id, $user_id, $head_type, $is_main ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( is_front_page() ) {
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
			self::add_single_person_data( $ret, $user_id, false );	// list_element = false

			if ( is_front_page() || $is_main ) {

				// override the author's website url from his profile
				// and use the open graph url instead
				$ret['url'] = $mt_og['og:url'];

				if ( $is_main )
					self::add_main_entity_data( $ret, $ret['url'] );

				if ( is_front_page() ) {
					// add the sameAs social profile links
					foreach ( WpssoUser::get_user_id_contact_methods( $user_id ) as $cm_id => $cm_label ) {
						$url = trim( get_the_author_meta( $cm_id, $user_id ) );
						if ( empty( $url ) )
							continue;
						if ( $cm_id === $this->p->options['plugin_cm_twitter_name'] )
							$url = 'https://twitter.com/'.preg_replace( '/^@/', '', $url );
						if ( strpos( $url, '://' ) !== false )
							$ret['sameAs'][] = esc_url( $url );
					}
				}
			}

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
		public static function add_single_organization_data( &$json_data, $post_id, $logo_key = 'schema_logo_url', $list_element = false ) {

			$wpsso = Wpsso::get_instance();

			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'Organization',
				'url' => esc_url( get_bloginfo( 'url' ) ),
				'name' => $wpsso->og->get_site_name( $post_id ),
			);

			$desc = $wpsso->og->get_site_desc( $post_id );
			if ( ! empty( $desc ) )
				$ret['description'] = $desc;

			if ( ! empty( $wpsso->options[$logo_key] ) )
				if ( ! self::add_single_image_data( $ret['logo'], $wpsso->options, $logo_key, false ) );	// list_element = false
					unset( $ret['logo'] );

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;

			return true;
		}

		public static function add_single_person_data( &$json_data, $user_id, $list_element = true ) {

			$wpsso = Wpsso::get_instance();

			if ( empty( $user_id ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty user_id' );
				return false;
			}

			if ( empty( $wpsso->m['util']['user'] ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty user module' );
				return false;
			} else $mod_obj =& $wpsso->m['util']['user'];

			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'Person',
			);

			$url = get_the_author_meta( 'url', $user_id );
			if ( strpos( $url, '://' ) !== false )
				$ret['url'] = esc_url( $url );

			$name = $mod_obj->get_author_name( $user_id, $wpsso->options['schema_author_name'] );
			if ( ! empty( $name ) )
				$ret['name'] = $name;

			$desc = $wpsso->util->get_mod_options( 'user', $user_id, array( 'schema_desc', 'og_desc' ) );
			if ( empty( $desc ) )
				$desc = get_the_author_meta( 'description', $user_id );
			if ( ! empty( $desc ) )
				$ret['description'] = $desc;

			// get_og_images() also provides filter hooks for additional image ids and urls
			$size_name = $wpsso->cf['lca'].'-schema';
			$og_image = $mod_obj->get_og_image( 1, $size_name, $user_id, false );

			if ( ! empty( $og_image ) )
				self::add_image_list_data( $ret['image'], $og_image, 'og:image' );

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;

			return true;
		}

		// pass a single or two dimension image array in $og_image
		public static function add_image_list_data( &$json_data, &$og_image, $opt_pre = 'og:image' ) {

			if ( isset( $og_image[0] ) && is_array( $og_image[0] ) ) {				// 2 dimensional array
				foreach ( $og_image as $image )
					self::add_single_image_data( $json_data, $image, $opt_pre, true );	// list_element = true

			} elseif ( is_array( $og_image ) )
				self::add_single_image_data( $json_data, $og_image, $opt_pre, true );		// list_element = true
		}

		// pass a single dimension image array in $opts
		public static function add_single_image_data( &$json_data, &$opts, $opt_pre = 'og:image', $list_element = true ) {

			$wpsso = Wpsso::get_instance();

			if ( empty( $opts ) || ! is_array( $opts ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: options array is empty or not an array' );
				return false;
			}

			if ( empty( $opts[$opt_pre] ) && empty( $opts[$opt_pre.':secure_url'] ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: '.$opt_pre.' and '.
						$opt_pre.':secure_url values are empty' );
				return false;
			}

			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'ImageObject',
				'url' => esc_url( empty( $opts[$opt_pre.':secure_url'] ) ?	// prefer secure_url if available
					$opts[$opt_pre] :
					$opts[$opt_pre.':secure_url']
				),
			);

			self::add_data_prop_from_og( $ret, $opts, array(
				'width' => $opt_pre.':width',
				'height' => $opt_pre.':height',
			) );

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;	// add an item to the list

			return true;
		}

		public static function add_main_entity_data( array &$json_data, $url ) {
			$json_data['mainEntityOfPage'] = array(
				'@context' => 'http://schema.org',
				'@type' => 'WebPage',
				'@id' => $url,
			);
		}

		public static function add_data_prop_from_og( array &$json_data, array &$mt_og, array $names ) {
			foreach ( $names as $mt_name => $og_name )
				if ( ! empty( $mt_og[$og_name] ) )
					$json_data[$mt_name] = $mt_og[$og_name];
		}

		/*
		 * Meta Name Array
		 */
		public function get_meta_array( $use_post, &$post_obj, &$mt_og = array(), $crawler_name = 'unknown' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$ret = array();
			$add_meta = apply_filters( $lca.'_add_schema_meta_array', true );

			// get_meta_array() is disabled when the wpsso-schema-json-ld extension is active
			if ( ! $add_meta ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema meta array disabled' );
				return $ret;
			}

			if ( ! is_object( $post_obj ) )
				$post_obj = $this->p->util->get_post_object( $use_post );
			$post_id = empty( $post_obj->ID ) || empty( $post_obj->post_type ) ||
				! SucomUtil::is_post_page( $use_post ) ? 0 : $post_obj->ID;

			$head_type = $this->get_head_item_type( $use_post, $post_obj );

			$this->add_mt_schema_from_og( $ret, $mt_og, array(
				'url' => 'og:url',
				'name' => 'og:title',
			) );

			if ( ! empty( $this->p->options['add_meta_itemprop_description'] ) )
				$ret['description'] = $this->p->webpage->get_description( $this->p->options['schema_desc_len'], 
					'...', $use_post, true, true, true, 'schema_desc' );	// custom meta = schema_desc

			switch ( $head_type ) {
				case 'http://schema.org/BlogPosting':
				case 'http://schema.org/WebPage':

					$this->add_mt_schema_from_og( $ret, $mt_og, array(
						'datepublished' => 'article:published_time',
						'datemodified' => 'article:modified_time',
					) );

					if ( ! empty( $this->p->options['add_meta_itemprop_image'] ) ) {

						$size_name = $this->p->cf['lca'].'-schema';
						$og_image = $this->p->og->get_all_images( 1, $size_name, $post_id, true, 'schema' );

						if ( empty( $og_image ) && 
							SucomUtil::is_post_page( $use_post ) )
								$og_image = $this->p->media->get_default_image( 1, $size_name, true );

						if ( ! empty( $og_image ) ) {
							$image = reset( $og_image );
							$ret['image'] = $image['og:image'];
						}
					}

					break;
			}

			return apply_filters( $this->p->cf['lca'].'_meta_schema', $ret, $use_post, $post_obj );
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
		public function get_noscript_array( $use_post, &$post_obj, &$mt_og, $post_id, $user_id ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$ret = array();
			$lca = $this->p->cf['lca'];
			$add_noscript = apply_filters( $lca.'_add_schema_meta_array',
				( isset( $this->p->options['schema_add_noscript'] ) ?
					$this->p->options['schema_add_noscript'] : true ) );

			// get_meta_array() is disabled when the wpsso-schema-json-ld extension is active
			if ( ! $add_noscript ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema noscript array disabled' );
				return $ret;
			}

			if ( ! empty( $mt_og['og:image'] ) ) {
				if ( is_array( $mt_og['og:image'] ) )
					foreach ( $mt_og['og:image'] as $image )
						$ret = array_merge( $ret, $this->get_single_image_noscript( $use_post, $image ) );
				else $ret = array_merge( $ret, $this->get_single_image_noscript( $use_post, $mt_og['og:image'] ) );
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->log( $ret );
			return $ret;
		}

		// pass a single dimension array in $opts
		public function get_single_image_noscript( $use_post, &$opts, $opt_pre = 'og:image' ) {

			$mt_image = array();
			$have_image_html = false;

			if ( empty( $opts ) )
				return array();

			elseif ( is_array( $opts ) )

				if ( empty( $opts[$opt_pre] ) &&
					empty( $opts[$opt_pre.':secure_url'] ) )
						return array();

				// defines a two-dimensional array
				else $mt_image = array_merge(
					$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.url', 
						( ! empty( $opts[$opt_pre.':secure_url'] ) ?
							$opts[$opt_pre.':secure_url'] : $opts[$opt_pre] ), '', $use_post ),
					( empty( $opts[$opt_pre.':width'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.width',
							$opts[$opt_pre.':width'], '', $use_post ) ),
					( empty( $opts[$opt_pre.':height'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.height',
							$opts[$opt_pre.':height'], '', $use_post ) )
				);

			// defines a two-dimensional array
			else $mt_image = $this->p->head->get_single_mt( 'meta', 'itemprop', 'image.url', $opts, '', $use_post );

			// make sure we have html for at least one meta tag
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
	}
}

?>
