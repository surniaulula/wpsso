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

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes' => 1,
				'json_http_schema_org_website' => 6,
				'json_http_schema_org_organization' => 6,
			) );

			// only hook the head attribute filter if we have one
			if ( ! empty( $this->p->options['plugin_head_attr_filter_name'] ) &&
				$this->p->options['plugin_head_attr_filter_name'] !== 'none' ) {

				add_action( 'add_head_attributes', array( &$this, 'add_head_attributes' ) );

				$prio = empty( $this->p->options['plugin_head_attr_filter_prio'] ) ? 
					100 : $this->p->options['plugin_head_attr_filter_prio'];

				add_filter( $this->p->options['plugin_head_attr_filter_name'], 
					array( &$this, 'filter_head_attributes' ), $prio, 1 );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'head attributes filter skipped: plugin_head_attr_filter_name option is empty' );
		}

		public function filter_plugin_image_sizes( $sizes ) {
			$sizes['schema_img'] = array(
				'name' => 'schema',
				'label' => _x( 'Schema JSON-LD (same as Facebook / Open Graph)',
					'image size label', 'wpsso' ),
				'prefix' => 'og_img'	// use opengraph dimensions
			);
			return $sizes;
		}

		public function add_head_attributes() {
			echo apply_filters( $this->p->options['plugin_head_attr_filter_name'], '' );
		}

		public function filter_head_attributes( $head_attr = '' ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$item_type = $this->get_head_item_type();

			if ( ! empty( $item_type ) ) {

				// backwards compatibility
				if ( strpos( $item_type, '://' ) === false )
					$item_type = 'http://schema.org/'.$item_type;

				// fix incorrect itemscope values
				if ( strpos( $head_attr, ' itemscope="itemscope"' ) !== false )
					$head_attr = preg_replace( '/ itemscope="itemscope"/', 
						' itemscope', $head_attr );
				elseif ( strpos( $head_attr, ' itemscope' ) === false )
					$head_attr .= ' itemscope';

				// replace existing itemtype values
				if ( strpos( $head_attr, ' itemtype="' ) !== false )
					$head_attr = preg_replace( '/ itemtype="[^"]+"/',
						' itemtype="'.$item_type.'"', $head_attr );
				else $head_attr .= ' itemtype="'.$item_type.'"';

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'schema item_type value is empty' );

			return trim( $head_attr );
		}

		public function get_head_item_type( $use_post = false, $obj = false ) {

			if ( ! is_object( $obj ) )
				$obj = $this->p->util->get_post_object( $use_post );
			$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ||
				! SucomUtil::is_post_page( $use_post ) ? 0 : $obj->ID;

			$schema_types = apply_filters( $this->p->cf['lca'].'_schema_post_types', 
				$this->p->cf['head']['schema_type'] );

			$item_type = $schema_types['website'];		// default value for non-singular webpages

			if ( is_singular() ) {
				if ( ! empty( $obj->post_type ) &&
					! empty( $this->p->options['schema_type_for_'.$obj->post_type] ) ) {

					$type_name = $this->p->options['schema_type_for_'.$obj->post_type];
					if ( isset( $schema_types[$type_name] ) )
						$item_type = $schema_types[$type_name];
					else $item_type = $schema_types['webpage'];

				} else $item_type = $schema_types['webpage'];

			} elseif ( $this->p->util->force_default_author() &&
				! empty( $this->p->options['og_def_author_id'] ) )
					$item_type = $schema_types['webpage'];

			return apply_filters( $this->p->cf['lca'].'_schema_item_type',
				$item_type, $post_id, $obj );
		}

		public function get_meta_array( $use_post, &$obj, &$mt_og = array() ) {
			$mt_schema = array();

			if ( ! empty( $this->p->options['add_meta_itemprop_name'] ) ) {
				if ( ! empty( $mt_og['og:title'] ) )
					$mt_schema['name'] = $mt_og['og:title'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_url'] ) ) {
				if ( ! empty( $mt_og['og:url'] ) )
					$mt_schema['url'] = $mt_og['og:url'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_datepublished'] ) ) {
				if ( ! empty( $mt_og['article:published_time'] ) )
					$mt_schema['datepublished'] = $mt_og['article:published_time'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_datemodified'] ) ) {
				if ( ! empty( $mt_og['article:modified_time'] ) )
					$mt_schema['datemodified'] = $mt_og['article:modified_time'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_description'] ) ) {
				$mt_schema['description'] = $this->p->webpage->get_description( $this->p->options['schema_desc_len'], 
					'...', $use_post, true, true, true, 'schema_desc' );	// custom meta = schema_desc
			}

			if ( empty( $this->p->options['schema_add_noscript'] ) ) {
				if ( ! empty( $this->p->options['add_meta_itemprop_image'] ) ) {
					if ( ! empty( $mt_og['og:image'] ) ) {
						if ( is_array( $mt_og['og:image'] ) )
							foreach ( $mt_og['og:image'] as $image )
								$mt_schema['image'][] = $image['og:image'];
						else $mt_schema['image'] = $mt_og['og:image'];
					}
				}
			}

			return apply_filters( $this->p->cf['lca'].'_meta_schema', $mt_schema, $use_post, $obj );
		}

		public function get_noscript_array( $use_post, &$obj, &$mt_og, $post_id, $author_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( empty( $this->p->options['schema_add_noscript'] ) ||
				empty( $mt_og['og:type'] ) )
					return array();

			$ret = array();
			$og_type = $mt_og['og:type'];	// used to get product:rating:* values

			// only include author meta tags for appropriate schema types 
			if ( ! empty( $author_id ) ) {
				switch ( $this->get_head_item_type( $use_post, $obj ) ) {
					case 'http://schema.org/Article':
					case 'http://schema.org/Blog':
					case 'http://schema.org/Review':
					case 'http://schema.org/WebPage':
					case 'http://schema.org/WebSite':
						$author_website_url = get_the_author_meta( 'url', $author_id );
						$ret = array_merge( $ret,
							array( array( '<noscript itemprop="author" itemscope itemtype="http://schema.org/Person">'."\n" ) ),
							$this->p->head->get_single_mt( 'meta', 'itemprop', 'author.name', 
								$this->p->mods['util']['user']->get_author_name( $author_id,
									$this->p->options['schema_author_name'] ), '', $use_post ),
							( strpos( $author_website_url, '://' ) === false ? array() :
								$this->p->head->get_single_mt( 'meta', 'itemprop', 'url', 
									$author_website_url, '', $use_post ) ),
							array( array( '</noscript>'."\n" ) )
						);
						break;
					default:
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'author meta tags skipped for '.$og_type.' schema type' );
						break;
				}
			}

			if ( ! empty( $mt_og['og:image'] ) ) {
				if ( is_array( $mt_og['og:image'] ) )
					foreach ( $mt_og['og:image'] as $image )
						$ret = array_merge( $ret, $this->get_noscript_single_image( $use_post, $image ) );
				else $ret = array_merge( $ret, $this->get_noscript_single_image( $use_post, $mt_og['og:image'] ) );
			}

			if ( ! empty( $mt_og[$og_type.':rating:average'] ) &&
				( ! empty( $mt_og[$og_type.':rating:count'] ) || 
					! empty( $mt_og[$og_type.':review:count'] ) ) ) {

				$ret = array_merge( $ret,
					array( array( '<noscript itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">'."\n" ) ),
					$this->p->head->get_single_mt( 'meta', 'itemprop', 'ratingvalue', 
						$mt_og[$og_type.':rating:average'], '', $use_post ),
					( empty( $mt_og[$og_type.':rating:count'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'ratingcount', 
							$mt_og[$og_type.':rating:count'], '', $use_post ) ),
					( empty( $mt_og[$og_type.':rating:worst'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'worstrating', 
							$mt_og[$og_type.':rating:worst'], '', $use_post ) ),
					( empty( $mt_og[$og_type.':rating:best'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'bestrating', 
							$mt_og[$og_type.':rating:best'], '', $use_post ) ),
					( empty( $mt_og[$og_type.':review:count'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'reviewcount', 
							$mt_og[$og_type.':review:count'], '', $use_post ) ),
					array( array( '</noscript>'."\n" ) )
				);
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->log( $ret );
			return $ret;
		}

		public function get_json_array( $use_post, &$obj, &$mt_og, $post_id, $author_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build json array' );	// begin timer

			$ret = array();

			foreach ( array_unique( array(
				'http://schema.org/WebSite',
				'http://schema.org/Person',
				'http://schema.org/Organization',
				$this->get_head_item_type( $use_post, $obj )
			) ) as $type ) {
				$filter_name = $this->p->cf['lca'].'_json_'.
					SucomUtil::sanitize_hookname( $type );
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'filter '.$filter_name );
				if ( ( $json = apply_filters( $filter_name, false,
					$use_post, $obj, $mt_og, $post_id, $author_id ) ) !== false )
						$ret[] = "<script type=\"application/ld+json\">\n".
							$json."</script>\n";
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'filter '.$filter_name );
			}

			$ret = SucomUtil::a2aa( $ret );	// convert to array or arrays

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $ret );
				$this->p->debug->mark( 'build json array' );	// end timer
			}

			return $ret;
		}

		public function filter_json_http_schema_org_website( $json, $use_post, $obj, $mt_og, $post_id, $author_id ) {
			if ( empty( $this->p->options['schema_website_json'] ) )
				return $json;
			else return '{
	"@context":"http://schema.org",
	"@type":"WebSite",
	"name":"'.$this->p->og->get_site_name( $post_id ).'",
	"url":"'.esc_url( get_bloginfo( 'url' ) ).'",
	"potentialAction":{
		"@type":"SearchAction",
		"target":"'.esc_url( get_bloginfo( 'url' ) ).'?s={search_term_string}",
		"query-input":"required name=search_term_string"
	}'."\n}\n";
		}

		public function filter_json_http_schema_org_organization( $json, $use_post, $obj, $mt_og, $post_id, $author_id ) {
			if ( empty( $this->p->options['schema_publisher_json'] ) )
				return $json;

			$json = '{
	"@context":"http://schema.org",
	"@type":"Organization",
	"name":"'.$this->p->og->get_site_name( $post_id ).'",
	"url":"'.esc_url( get_bloginfo( 'url' ) )."\",\n".
	$this->get_json_single_image( 'logo', $this->p->options, 'schema_logo_url' ).
	"\t\"sameAs\":[\n";
			$url_list = '';
			foreach ( array( 'seo_publisher_url', 'fb_publisher_url',
				'linkedin_publisher_url', 'tc_site' ) as $key ) {
				$url = isset( $this->p->options[$key] ) ?
					trim( $this->p->options[$key] ) : '';
				if ( empty( $url ) )
					continue;
				if ( $key === 'tc_site' )
					$url = 'https://twitter.com/'.preg_replace( '/^@/', '', $url );
				if ( strpos( $url, '://' ) !== false )
					$url_list .= "\t\t\"".esc_url( $url )."\",\n";
			}
			return $json.rtrim( $url_list, ",\n" )."\n\t]\n}\n";
		}

		// pass a two dimension array in $arr
		public function get_json_image_list( $type = 'image', &$arr, $key_prefix = 'og:image', $json = '' ) {
			foreach ( $arr as $image )
				$json .= $this->get_json_single_image( $type, $image, $key_prefix );
			return $json;
		}

		// pass a single dimension array in $arr
		public function get_json_single_image( $type = 'image', &$arr, $key_prefix = 'og:image', $json = '' ) {
			if ( empty( $arr ) )
				return $json;
			elseif ( is_array( $arr ) ) {
				if ( empty( $arr[$key_prefix] ) &&
					empty( $arr[$key_prefix.':secure_url'] ) )
						return $json;
				else $json .= "\t\"".$type."\":{\n\t\t\"@type\":\"ImageObject\",\n".
					trim( "\t\t\"url\":\"".
						esc_url( ( ! empty( $arr[$key_prefix.':secure_url'] ) ?
							$arr[$key_prefix.':secure_url'] : $arr[$key_prefix] ) ).'",'."\n".
						( ! empty( $arr[$key_prefix.':width'] ) && $arr[$key_prefix.':width'] > 0 ?
							"\t\t\"width\":\"".$arr[$key_prefix.':width'].'",'."\n" : '' ).
						( ! empty( $arr[$key_prefix.':height'] ) && $arr[$key_prefix.':height'] > 0 ?
							"\t\t\"height\":\"".$arr[$key_prefix.':height'].'",'."\n" : '' ),
						",\n" )."\n\t},\n";
			};
			return $json;
		}

		// pass a single dimension array in $arr
		public function get_noscript_single_image( $use_post, &$arr, $key_prefix = 'og:image' ) {
			if ( empty( $arr ) )
				return array();
			elseif ( is_array( $arr ) )
				if ( empty( $arr[$key_prefix] ) &&
					empty( $arr[$key_prefix.':secure_url'] ) )
						return array();
				else return array_merge(
					array( array( '<noscript itemprop="image" itemscope itemtype="http://schema.org/ImageObject">'."\n" ) ),
					$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.url', 
						( ! empty( $arr[$key_prefix.':secure_url'] ) ?
							$arr[$key_prefix.':secure_url'] : $arr[$key_prefix] ), '', $use_post ),
					( empty( $arr[$key_prefix.':width'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.width',
							$arr[$key_prefix.':width'], '', $use_post ) ),
					( empty( $arr[$key_prefix.':height'] ) ? array() :
						$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.height',
							$arr[$key_prefix.':height'], '', $use_post ) ),
					array( array( '</noscript>'."\n" ) )
				);
			else return array_merge(
				array( array( '<noscript itemprop="image" itemscope itemtype="http://schema.org/ImageObject">'."\n" ) ),
				$this->p->head->get_single_mt( 'meta', 'itemprop', 'image.url', $arr, '', $use_post ),
				array( array( '</noscript>'."\n" ) )
			);
		}
	}
}

?>
