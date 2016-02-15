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
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_image_sizes' => 4,
				'data_http_schema_org_website' => 5,
				'data_http_schema_org_organization' => 5,
				'data_http_schema_org_person' => 6,
			), 5 );

			// only hook the head attribute filter if we have one
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

		public function filter_plugin_image_sizes( $sizes, $obj, $mod, $crawler_name ) {
			$opt_pre = 'og_img';			// use opengraph dimensions

			if ( ! SucomUtil::get_const( 'WPSSO_RICH_PIN_DISABLE' ) ) {
				if ( $crawler_name === 'pinterest' )
					$opt_pre = 'rp_img';	// use pinterest dimensions
			}

			$sizes['schema_img'] = array(
				'name' => 'schema',
				'label' => _x( 'Schema JSON-LD (same as Facebook / Open Graph)',
					'image size label', 'wpsso' ),
				'prefix' => $opt_pre
			);

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

			if ( ! empty( $head_type ) ) {

				// backwards compatibility
				if ( strpos( $head_type, '://' ) === false )
					$head_type = 'http://schema.org/'.$head_type;

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

			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'schema item_type value is empty' );

			return trim( $head_attr );
		}

		public function get_head_item_type( $use_post = false, $obj = false ) {

			if ( ! is_object( $obj ) )
				$obj = $this->p->util->get_post_object( $use_post );

			$schema_types = apply_filters( $this->p->cf['lca'].'_schema_post_types', 
				$this->p->cf['head']['schema_type'] );

			$head_type = $schema_types['website'];	// default value for non-singular webpages

			if ( SucomUtil::is_post_page( $use_post ) ) {
				if ( ! empty( $obj->post_type ) &&
					! empty( $this->p->options['schema_type_for_'.$obj->post_type] ) ) {

					$ptn = $this->p->options['schema_type_for_'.$obj->post_type];
					if ( isset( $schema_types[$ptn] ) )
						$head_type = $schema_types[$ptn];
					else $head_type = $schema_types['webpage'];

				} else $head_type = $schema_types['webpage'];

			} elseif ( $this->p->util->force_default_author() &&
				! empty( $this->p->options['og_def_author_id'] ) )
					$head_type = $schema_types['webpage'];

			return apply_filters( $this->p->cf['lca'].'_schema_item_type', $head_type, $use_post, $obj );
		}

		public function get_head_type_context( $use_post = false, $obj = false ) {
			return self::get_item_type_context( $this->get_head_item_type( $use_post, $obj ) );
		}

		public static function get_item_type_context( $item_type, $properties = array() ) {
			if ( preg_match( '/^(.+:\/\/.+)\/([^\/]+)$/', $item_type, $match ) )
				return array_merge( array(
					'@context' => $match[1],
					'@type' => $match[2],
				), $properties );
			else return array();
		}

		public function get_json_array( $use_post, &$obj, &$mt_og, $post_id, $author_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'build json array' );	// begin timer for json array

			$ret = array();
			$lca = $this->p->cf['lca'];
			$head_type = $this->get_head_item_type( $use_post, $obj );
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'schema item type: '.$head_type );

			foreach ( array(
				'http://schema.org/WebSite' => $this->p->options['schema_website_json'],
				'http://schema.org/Organization' => $this->p->options['schema_publisher_json'],
				'http://schema.org/Person' => $this->p->options['schema_author_json'],
				$head_type => true,
			) as $item_type => $enable ) {

				$data = false;
				$item_type_hook = SucomUtil::sanitize_hookname( $item_type );
				$generic_item_hook = 'http_schema_org_item_type';

				// filter the webpage item type through a generic / common filter first
				if ( $item_type === $head_type ) {
					if ( apply_filters( $lca.'_add_'.$generic_item_hook, true ) )
						$data = apply_filters( $lca.'_data_'.$generic_item_hook,
							$data, $use_post, $obj, $mt_og, $post_id, $author_id, $head_type );
					elseif ( $this->p->debug->enabled )
						$this->p->debug->log( $generic_item_hook.' is disabled' );
				}

				if ( apply_filters( $lca.'_add_'.$item_type_hook, $enable ) )
					$data = apply_filters( $lca.'_data_'.$item_type_hook,
						$data, $use_post, $obj, $mt_og, $post_id, $author_id, $head_type );
				elseif ( $this->p->debug->enabled )
					$this->p->debug->log( $item_type_hook.' is disabled' );

				if ( ! empty( $data ) )
					$ret[] = "<script type=\"application/ld+json\">".
						$this->p->util->json_format( $data )."</script>\n";
			}

			$ret = SucomUtil::a2aa( $ret );	// convert to array of arrays

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $ret );
				$this->p->debug->mark( 'build json array' );	// end timer for json array
			}

			return $ret;
		}

		public function filter_data_http_schema_org_website( $data, $use_post, $obj, $mt_og, $post_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$data = array(
				'@context' => 'http://schema.org',
				'@type' => 'WebSite',
				'url' => esc_url( get_bloginfo( 'url' ) ),
				'name' => $this->p->og->get_site_name( $post_id ),
			);

			if ( ! empty( $this->p->options['schema_alt_name'] ) )
				$data['alternateName'] = $this->p->options['schema_alt_name'];

			$search_url = apply_filters( $lca.'_json_ld_search_url',
				get_bloginfo( 'url' ).'?s={search_term_string}' );

			if ( ! empty( $search_url ) )
				$data['potentialAction'] = array(
					'@context' => 'http://schema.org',
					'@type' => 'SearchAction',
					'target' => $search_url,
					'query-input' => 'required name=search_term_string',
				);

			return $data;
		}

		public function filter_data_http_schema_org_organization( $data, $use_post, $obj, $mt_og, $post_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$data = array(
				'@context' => 'http://schema.org',
				'@type' => 'Organization',
				'url' => esc_url( get_bloginfo( 'url' ) ),
				'name' => $this->p->og->get_site_name( $post_id ),
			);

			if ( ! empty( $this->p->options['schema_logo_url'] ) )
				self::add_single_image_data( $data['logo'],
					$this->p->options, 'schema_logo_url', false );	// list_element = false

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
					$data['sameAs'][] = esc_url( $url );
			}

			return $data;
		}

		public function filter_data_http_schema_org_person( $data, $use_post, $obj, $mt_og, $post_id, $author_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( empty( $author_id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: empty author_id' );
				return $data;
			}

			$lca = $this->p->cf['lca'];
			$data = array();
			WpssoSchema::add_single_person_data( $data, $author_id, false );	// list_element = false

			foreach ( WpssoUser::get_user_id_contact_methods( $author_id ) as $cm_id => $cm_label ) {
				$url = trim( get_the_author_meta( $cm_id, $author_id ) );
				if ( empty( $url ) )
					continue;
				if ( $cm_id === $this->p->options['plugin_cm_twitter_name'] )
					$url = 'https://twitter.com/'.preg_replace( '/^@/', '', $url );
				if ( strpos( $url, '://' ) !== false )
					$data['sameAs'][] = esc_url( $url );
			}

			return $data;
		}

		public static function add_single_person_data( &$data, $author_id, $list_element = true ) {

			$wpsso = Wpsso::get_instance();

			if ( empty( $author_id ) ) {
				if ( $wpsso->debug->enabled )
					$wpsso->debug->log( 'exiting early: empty author_id' );
				return false;
			}

			$person_data = array(
				'@context' => 'http://schema.org',
				'@type' => 'Person',
				'url' => '',
				'name' => $wpsso->m['util']['user']->get_author_name( $author_id,
					$wpsso->options['schema_author_name'] ),
			);

			$person_url = get_the_author_meta( 'url', $author_id );
			if ( strpos( $person_url, '://' ) !== false )
				$person_data['url'] = esc_url( $person_url );

			$size_name = $wpsso->cf['lca'].'-schema';
			$og_image = $wpsso->m['util']['user']->get_og_image( 1, $size_name, $author_id, false );
			if ( ! empty( $og_image ) )
				WpssoSchema::add_image_list_data( $person_data['image'], $og_image, 'og:image' );

			if ( empty( $list_element ) )
				$data = $person_data;
			else $data[] = $person_data;

			return true;
		}

		// pass a single or two dimension image array in $og_image
		public static function add_image_list_data( &$data, &$og_image, $opt_pre = 'og:image' ) {
			if ( isset( $og_image[0] ) && is_array( $og_image[0] ) )			// 2 dimensional array
				foreach ( $og_image as $image )
					self::add_single_image_data( $data, $image, $opt_pre, true );	// list_element = true
			elseif ( is_array( $og_image ) )
				self::add_single_image_data( $data, $og_image, $opt_pre, true );	// list_element = true
		}

		// pass a single dimension image array in $opts
		public static function add_single_image_data( &$data, &$opts, $opt_pre = 'og:image', $list_element = true ) {

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
			
			$image_data = array(
				'@context' => 'http://schema.org',
				'@type' => 'ImageObject',
				'url' => esc_url( empty( $opts[$opt_pre.':secure_url'] ) ?
					$opts[$opt_pre] : $opts[$opt_pre.':secure_url'] ),
			);
			foreach ( array ( 'width', 'height' ) as $wh )
				if ( isset( $opts[$opt_pre.':'.$wh] ) && 
					$opts[$opt_pre.':'.$wh] > 0 )
						$image_data[$wh] = $opts[$opt_pre.':'.$wh];
			if ( empty( $list_element ) )
				$data = $image_data;
			else $data[] = $image_data;	// add an item to the list

			return true;
		}

		public function get_meta_array( $use_post, &$obj, &$mt_og = array(), $crawler_name = 'unknown' ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$mt_schema = array();
			$lca = $this->p->cf['lca'];

			// get_meta_array() is disabled when the wpsso-schema-json-ld extension is active
			if ( ! apply_filters( $lca.'_add_schema_meta_array', true ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: schema meta array disabled' );
				return $mt_schema;
			}

			$head_type = $this->get_head_item_type();

			if ( ! empty( $this->p->options['add_meta_itemprop_url'] ) ) {
				if ( ! empty( $mt_og['og:url'] ) )
					$mt_schema['url'] = $mt_og['og:url'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_name'] ) ) {
				if ( ! empty( $mt_og['og:title'] ) )
					$mt_schema['name'] = $mt_og['og:title'];
			}

			if ( ! empty( $this->p->options['add_meta_itemprop_description'] ) ) {
				$mt_schema['description'] = $this->p->webpage->get_description( $this->p->options['schema_desc_len'], 
					'...', $use_post, true, true, true, 'schema_desc' );	// custom meta = schema_desc
			}

			switch ( $head_type ) {
				case 'http://schema.org/Blog':
				case 'http://schema.org/WebPage':

					if ( ! empty( $this->p->options['add_meta_itemprop_datepublished'] ) ) {
						if ( ! empty( $mt_og['article:published_time'] ) )
							$mt_schema['datepublished'] = $mt_og['article:published_time'];
					}

					if ( ! empty( $this->p->options['add_meta_itemprop_datemodified'] ) ) {
						if ( ! empty( $mt_og['article:modified_time'] ) )
							$mt_schema['datemodified'] = $mt_og['article:modified_time'];
					}

					if ( ! empty( $this->p->options['add_meta_itemprop_image'] ) ) {
						if ( ! empty( $mt_og['og:image'] ) ) {
							if ( is_array( $mt_og['og:image'] ) )
								foreach ( $mt_og['og:image'] as $image )
									$mt_schema['image'][] = $image['og:image'];
							else $mt_schema['image'] = $mt_og['og:image'];
						}
					}

					break;
			}

			return apply_filters( $this->p->cf['lca'].'_meta_schema', $mt_schema, $use_post, $obj );
		}
	}
}

?>
