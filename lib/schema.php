<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSchema' ) ) {

	class WpssoSchema {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes' => 1,
			) );

			if ( ! empty( $this->p->options['plugin_head_attr_filter_name'] ) &&
				$this->p->options['plugin_head_attr_filter_name'] !== 'none' ) {

					$prio = empty( $this->p->options['plugin_head_attr_filter_prio'] ) ? 
						100 : $this->p->options['plugin_head_attr_filter_prio'];

					add_filter( $this->p->options['plugin_head_attr_filter_name'], 
						array( &$this, 'add_head_attributes' ), $prio, 1 );

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'add_head_attributes skipped: plugin_head_attr_filter_name option is empty' );
		}

		public function filter_plugin_image_sizes( $sizes ) {
			$sizes['schema_img'] = array(
				'name' => 'schema',
				'label' => 'Schema JSON-LD (same as Facebook / Open Graph)',
				'prefix' => 'og_img'	// use opengraph dimensions
			);
			return $sizes;
		}

		public function add_head_attributes( $head_attr ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$obj = $this->p->util->get_post_object( false );
			$post_id = empty( $obj->ID ) || empty( $obj->post_type ) ? 0 : $obj->ID;
			$schema_types = apply_filters( $this->p->cf['lca'].'_schema_post_types', 
				$this->p->cf['head']['schema_type'] );
			$item_type = $schema_types['website'];		// default value for non-singular webpages

			if ( is_singular() ) {

				if ( ! empty( $obj->post_type ) &&
					isset( $schema_types[$obj->post_type] ) )
						$item_type = $schema_types[$obj->post_type];
				else $item_type = $schema_types['webpage'];

			} elseif ( $this->p->util->force_default_author() &&
				! empty( $this->p->options['og_def_author_id'] ) )
					$item_type = $schema_types['webpage'];

			$item_type = apply_filters( $this->p->cf['lca'].'_schema_item_type', $item_type, $post_id, $obj );

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

		public function get_meta_array( $use_post, &$obj, &$meta_og = array() ) {
			$mt_schema = array();

			if ( ! empty( $this->p->options['add_meta_itemprop_name'] ) ) {
				if ( ! empty( $meta_og['og:title'] ) )
					$mt_schema['name'] = $meta_og['og:title'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_headline'] ) ) {
				if ( ! empty( $meta_og['og:title'] ) &&
					isset( $meta_og['og:type'] ) &&
						$meta_og['og:type'] === 'article' )
							$mt_schema['headline'] = $meta_og['og:title'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_datepublished'] ) ) {
				if ( ! empty( $meta_og['article:published_time'] ) )
					$mt_schema['datepublished'] = $meta_og['article:published_time'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_description'] ) ) {
				$mt_schema['description'] = $this->p->webpage->get_description( $this->p->options['og_desc_len'], 
					'...', $use_post, true, true, true, 'schema_desc' );	// custom meta = schema_desc
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_url'] ) ) {
				if ( ! empty( $meta_og['og:url'] ) )
					$mt_schema['url'] = $meta_og['og:url'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_image'] ) ) {
				if ( ! empty( $meta_og['og:image'] ) ) {
					if ( is_array( $meta_og['og:image'] ) )
						foreach ( $meta_og['og:image'] as $image )
							$mt_schema['image'][] = $image['og:image'];
					else $mt_schema['image'] = $meta_og['og:image'];
				}
			}

			return apply_filters( $this->p->cf['lca'].'_meta_schema', $mt_schema, $use_post, $obj );
		}

		public function get_json_array( $post_id = false, $author_id = false, $size_name = 'thumbnail' ) {
			$json_array = array();

			if ( ! empty( $this->p->options['schema_website_json'] ) &&
				( $json_script = $this->get_website_json_script( $post_id ) ) !== false )
					$json_array[] = $json_script;

			if ( ! empty( $this->p->options['schema_author_json'] ) && ! empty( $author_id ) &&
				( $json_script = $this->p->mods['util']['user']->get_person_json_script( $author_id, $size_name ) ) !== false )
					$json_array[] = $json_script;

			if ( ! empty( $this->p->options['schema_publisher_json'] ) &&
				( $json_script = $this->get_organization_json_script( $size_name ) ) !== false )
					$json_array[] = $json_script;

			return $json_array;	// must be an array
		}

		public function get_website_json_script( $post_id = false ) {
			$home_url = get_bloginfo( 'url' );	// equivalent to get_home_url()
			// pass options array to allow fallback if locale option does not exist
			$site_name = $this->p->og->get_site_name( $post_id );
			$json_script = '<script type="application/ld+json">{
	"@context":"http://schema.org",
	"@type":"WebSite",
	"url":"'.$home_url.'",
	"name":"'.$site_name.'",
	"potentialAction":{
		"@type":"SearchAction",
		"target":"'.$home_url.'?s={search_term_string}",
		"query-input":"required name=search_term_string"
	}
}</script>';
			return $json_script;
		}

		public function get_organization_json_script( $size_name = 'thumbnail') {
			$home_url = get_bloginfo( 'url' );	// equivalent to get_home_url()
			$logo_url = $this->p->options['schema_logo_url'];
			$og_image = $this->p->media->get_default_image( 1, $this->p->cf['lca'].'-schema', false );
			if ( count( $og_image ) > 0 ) {
				$image = reset( $og_image );
				$image_url = $image['og:image'];
			} else $image_url = '';

			$json_script = '<script type="application/ld+json">{
	"@context":"http://schema.org",
	"@type":"Organization",
	"url":"'.$home_url.'",
	"logo":"'.$logo_url.'",
	"image":"'.$image_url.'",
	"sameAs":['."\n";
			foreach ( array(
				'seo_publisher_url',
				'fb_publisher_url',
				'linkedin_publisher_url',
				'tc_site',
			) as $key ) {
				$sameAs = isset( $this->p->options[$key] ) ?
					trim( $this->p->options[$key] ) : '';
				if ( empty( $sameAs ) )
					continue;

				if ( $key === 'tc_site' )
					$sameAs = 'https://twitter.com/'.preg_replace( '/^@/', '', $sameAs );

				if ( strpos( $sameAs, '://' ) !== false )
					$json_script .= "\t\t\"".$sameAs."\",\n";
			}
			return rtrim( $json_script, ",\n" )."\n\t]\n}</script>\n";
		}
	}
}

?>
