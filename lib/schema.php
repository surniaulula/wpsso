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
				'label' => _x( 'Google / Schema Image',
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
			$head_type = $this->get_head_item_type( $mod );

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

			if ( $this->schema_types === null )
				$this->set_schema_types_property();

			$lca = $this->p->cf['lca'];
			$default_key = apply_filters( $lca.'_schema_type_for_default', 'webpage' );
			$type_id = null;

			if ( $use_mod_opts ) {
				if ( ! empty( $mod['obj'] ) ) {	// just in case
					$type_id = $mod['obj']->get_options( $mod['id'], 'schema_type' );

					if ( empty( $type_id ) || $type_id === 'none' ) {
						$type_id = null;
					} elseif ( empty( $this->schema_types[$type_id] ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'custom type id '.
								$type_id.' not in schema types property' );
						$type_id = null;
					} elseif ( $this->p->debug->enabled )
						$this->p->debug->log( 'custom type id '.
							$type_id.' from module '.$mod['name'] );

				} elseif ( $this->p->debug->enabled )
					$this->p->debug->log( 'incomplete module: class object is empty' );
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'skipping custom type id check: use_mod_opts argument is false' );

			if ( empty( $type_id ) ) {
				if ( is_front_page() )
					$type_id = apply_filters( $this->p->cf['lca'].'_schema_type_for_home_page',
						( empty( $this->p->options['schema_type_for_home_page'] ) ?
							'website' : $this->p->options['schema_type_for_home_page'] ) );

				elseif ( $mod['is_post'] ) {
					$post_obj = $this->p->util->get_post_object( $mod['id'] );

					if ( ! empty( $post_obj->post_type ) ) {
						if ( isset( $this->p->options['schema_type_for_'.$post_obj->post_type] ) ) {

							$type_id = $this->p->options['schema_type_for_'.$post_obj->post_type];

							if ( empty( $type_id ) || $type_id === 'none' ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'schema type for post type '.
										$post_obj->post_type.' is disabled' );
								$type_id = null;

							} elseif ( empty( $this->schema_types[$type_id] ) ) {
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'schema type id '.
										$type_id.' not found in schema types array' );
								$type_id = $default_key;

							} elseif ( $this->p->debug->enabled )
								$this->p->debug->log( 'schema type id for post type '.
									$post_obj->post_type.' is '.$type_id );

						} elseif ( ! empty( $this->schema_types[$post_obj->post_type] ) ) {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'setting schema type id to post type '.
									$post_obj->post_type );
							$type_id = $post_obj->post_type;

						// unknown post type
						} else {
							if ( $this->p->debug->enabled )
								$this->p->debug->log( 'using page schema type: unknown post type '.
									$post_obj->post_type );
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

			$type_id = apply_filters( $this->p->cf['lca'].'_schema_head_type', $type_id, $mod );

			if ( isset( $this->schema_types[$type_id] ) ) {
				if ( $return_id ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'returning schema type id '.
							$type_id );
					return $type_id;
				} else {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'returning schema type value '.
							$this->schema_types[$type_id] );
					return $this->schema_types[$type_id];
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

		// get the full Schema type URL from the array key
		// example: returns 'http://schema.org/TechArticle' for 'article.tech'
		public function get_item_type_value( $type_id ) {
			if ( $this->schema_types === null )
				$this->set_schema_types_property();
			if ( isset( $this->schema_types[$type_id] ) )
				return $this->schema_types[$type_id];
			else return false;
		}

		public static function get_item_type_context( $item_type, $properties = array() ) {
			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $item_type, $match ) )
				return array_merge( array(
					'@context' => $match[1],
					'@type' => $match[2],
				), $properties );
			else return array();
		}

		public static function get_item_type_parts( $item_type ) {
			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $item_type, $match ) )
				return array( $match[1], $match[2] );
			else return array();
		}

		public function get_schema_types() {
			if ( $this->schema_types === null )
				$this->set_schema_types_property();
			return $this->schema_types;
		}

		public function set_schema_types_property() {
			if ( $this->schema_types === null )
				$this->schema_types = (array) apply_filters( $this->p->cf['lca'].'_schema_types', 
					$this->p->cf['head']['schema_type'] );
		}

		public function get_schema_types_select() {
			if ( $this->schema_types === null )
				$this->set_schema_types_property();
			$select = array( 'none' => '[None]' );
			foreach ( $this->schema_types as $type_id => $label )
				$select[$type_id] = $type_id.' ('.$label.')';
			return $select;
		}

		// $item_type examples: http_schema_org_webpage or http://schema.org/WebPage
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
			$post_obj = false;

			// example: article.tech
			$mt_og['schema:type:id'] = $this->get_head_item_type( $mod, true );	// return the type id

			// example: http://schema.org/TechArticle
			$mt_og['schema:type:url'] = $head_type = $this->get_item_type_value( $mt_og['schema:type:id'] );

			// example: http://schema.org, TechArticle
			list( $mt_og['schema:type:context'], $mt_og['schema:type:name'] ) = self::get_item_type_parts( $head_type );

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'schema item type is '.$head_type );

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

				if ( $mod['obj'] )
					$is_main = $mod['obj']->get_options( $mod['id'], 'schema_is_main' );
				else $is_main = null;

				if ( $is_main === null ) {
					if ( $item_type === $head_type )
						$is_main = true;
					else $is_main = false;
				}

				$is_main = apply_filters( $lca.'_json_is_main_entity', 
					$is_main, $use_post, $mod, $mt_og, $user_id, $head_type );

				// include WebSite, Organization, and/or Person on the home page
				// if there isn't a hook for that filter (from WPSSO JSON, for example)
				if ( is_front_page() && 
					method_exists( __CLASS__, 'filter_json_data_'.$type_filter_name ) && 
						! has_filter( $lca.'_json_data_'.$type_filter_name ) ) {

					if ( $is_enabled )
						$json_data = call_user_func( array( __CLASS__, 'filter_json_data_'.$type_filter_name ),
							$json_data, $use_post, $mod, $mt_og, $user_id,
								$head_type, false );	// $is_main = false when called directly

				// call http_schema_org_item_type as a generic / common data filter first
				} else foreach ( array( 'http_schema_org_item_type', $type_filter_name ) as $filter_name ) {

					if ( has_filter( $lca.'_json_data_'.$filter_name ) ) {
						if ( apply_filters( $lca.'_add_json_'.$filter_name, $is_enabled ) )
							$json_data = apply_filters( $lca.'_json_data_'.$filter_name,
								$json_data, $use_post, $mod, $mt_og, $user_id,
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
		public function filter_json_data_http_schema_org_website( $json_data, $use_post, $mod, $mt_og, $user_id, $head_type, $is_main ) {

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
		public function filter_json_data_http_schema_org_organization( $json_data, $use_post, $mod, $mt_og, $user_id, $head_type, $is_main ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$ret = array();

			self::add_single_organization_data( $ret, $mod, 'schema_logo_url', false );	// list_element = false

			if ( $is_main )
				self::add_main_entity_data( $ret, $ret['url'] );

			if ( is_front_page() ) {
				// add the sameAs social profile links
				foreach ( array(
					'fb_publisher_url',
					'seo_publisher_url',
					'rp_publisher_url',
					'instgram_publisher_url',
					'linkedin_publisher_url',
					'myspace_publisher_url',
					'tc_site',
				) as $key ) {
					$url_locale = SucomUtil::get_locale_opt( $key, $this->p->options, $mod );
					if ( empty( $url_locale ) )
						continue;
					if ( $key === 'tc_site' )
						$url_locale = 'https://twitter.com/'.
							preg_replace( '/^@/', '', $url_locale );
					if ( strpos( $url_locale, '://' ) !== false )
						$ret['sameAs'][] = esc_url( $url_locale );
				}
			}

			return self::return_data_from_filter( $json_data, $ret );
		}

		/*
		 * http://schema.org/Person social markup for Google
		 */
		public function filter_json_data_http_schema_org_person( $json_data, $use_post, $mod, $mt_og, $user_id, $head_type, $is_main ) {

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

			if ( ! empty( $wpsso->options[$logo_key] ) )
				if ( ! self::add_single_image_data( $ret['logo'], $wpsso->options, $logo_key, false ) )	// list_element = false
					unset( $ret['logo'] );	// prevent null assignment

			if ( empty( $list_element ) )
				$json_data = $ret;
			else $json_data[] = $ret;

			return 1;
		}

		public static function add_single_person_data( &$json_data, $user_id = 0, $list_element = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( empty( $user_id ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty user_id' );
				return 0;
			}

			// get the user module
			if ( empty( $wpsso->m['util']['user'] ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty user module' );
				return 0;
			} else $mod = $wpsso->m['util']['user']->get_mod( $user_id );

			$ret = array(
				'@context' => 'http://schema.org',
				'@type' => 'Person',
			);

			$url = get_the_author_meta( 'url', $mod['id'] );

			if ( strpos( $url, '://' ) !== false )
				$ret['url'] = esc_url( $url );

			$name = $mod['obj'] ?
				$mod['obj']->get_author_name( $mod['id'], $wpsso->options['schema_author_name'] ) : null;

			if ( ! empty( $name ) )
				$ret['name'] = $name;

			$desc = $mod['obj'] ?
				$mod['obj']->get_options_multi( $mod['id'], 
					array( 'schema_desc', 'og_desc' ) ) : null;

			if ( empty( $desc ) )
				$desc = get_the_author_meta( 'description', $mod['id'] );
			if ( ! empty( $desc ) )
				$ret['description'] = $desc;

			// get_og_images() also provides filter hooks for additional image ids and urls
			$size_name = $wpsso->cf['lca'].'-schema';
			$og_image = $mod['obj']->get_og_image( 1, $size_name, $mod['id'], false );

			if ( ! empty( $og_image ) )
				if ( ! self::add_image_list_data( $ret['image'], $og_image, 'og:image' ) );
					unset( $ret['image'] );	// prevent null assignment

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

			self::add_data_prop_from_og( $ret, $opts, array(
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

		public static function add_data_prop_from_og( array &$json_data, array &$mt_og, array $names ) {
			foreach ( $names as $mt_name => $og_name )
				if ( ! empty( $mt_og[$og_name] ) )
					$json_data[$mt_name] = $mt_og[$og_name];
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
			$head_type = $this->get_head_item_type( $mod );

			$this->add_mt_schema_from_og( $mt_schema, $mt_og, array(
				'url' => 'og:url',
				'name' => 'og:title',
			) );

			if ( ! empty( $this->p->options['add_meta_itemprop_description'] ) )
				$mt_schema['description'] = $this->p->webpage->get_description( $this->p->options['schema_desc_len'], 
					'...', $mod, true, true, true, 'schema_desc' );	// custom meta = schema_desc

			switch ( $head_type ) {
				case 'http://schema.org/BlogPosting':
				case 'http://schema.org/WebPage':

					$this->add_mt_schema_from_og( $mt_schema, $mt_og, array(
						'datepublished' => 'article:published_time',
						'datemodified' => 'article:modified_time',
					) );

					if ( ! $this->is_noscript_enabled() &&
						! empty( $this->p->options['add_meta_itemprop_image'] ) ) {

						$size_name = $this->p->cf['lca'].'-schema';
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
		public function get_noscript_array( $use_post, array &$mod, array &$mt_og, $user_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! self::is_noscript_enabled() )
				return array();	// empty array

			$ret = array();
			$lca = $this->p->cf['lca'];
			$max = $this->p->util->get_max_nums( $mod, 'schema' );
			$size_name = $this->p->cf['lca'].'-schema';
			$og_image = $this->p->og->get_all_images( $max['schema_img_max'], $size_name, $mod, true, 'schema' );	// $md_pre = 'schema'

			if ( empty( $og_image ) && $mod['is_post'] ) 
				$og_image = $this->p->media->get_default_image( 1, $size_name, true );

			foreach ( $og_image as $image )
				$ret = array_merge( $ret, $this->get_single_image_noscript( $use_post, $image ) );

			if ( $this->p->debug->enabled )
				$this->p->debug->log( $ret );

			return $ret;
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

		// pass a single dimension array in $opts
		public function get_single_image_noscript( $use_post, &$mixed, $prefix = 'og:image' ) {

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
					$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.url', $media_url, '', $use_post ),
					( empty( $mixed[$prefix.':width'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.width',
							$mixed[$prefix.':width'], '', $use_post ) ),
					( empty( $mixed[$prefix.':height'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.height',
							$mixed[$prefix.':height'], '', $use_post ) )
				);

			// defines a two-dimensional array
			} else $mt_image = $this->p->head->get_single_mt( 'meta', 'itemprop', 'image.url', $mixed, '', $use_post );

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
	}
}

?>
