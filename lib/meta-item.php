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

if ( ! class_exists( 'WpssoMetaItem' ) ) {

	class WpssoMetaItem {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * If Schema markup is disabled, do not add the itemtype to the <head> HTML tag.
			 */
			if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema markup is disabled' );
				}

			} else {

				/**
				 * Template action hook.
				 */
				add_action( 'add_head_attributes', array( $this, 'add_head_attributes' ), -1000 );

				$filter_name = SucomUtil::get_const( 'WPSSO_HEAD_ATTR_FILTER_NAME', 'head_attributes' );
				$filter_prio = SucomUtil::get_const( 'WPSSO_HEAD_ATTR_FILTER_PRIO', 1000 );

				if ( empty( $filter_name ) || 'none' === $filter_name ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipped filter_head_attributes - filter name is empty or disabled' );
					}

				} else {

					add_filter( $filter_name, array( $this, 'filter_head_attributes' ), $filter_prio, 1 );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'added filter_head_attributes filter for ' . $filter_name );
					}
				}
			}
		}

		/**
		 * Template action hook.
		 */
		public function add_head_attributes() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Hooked by the WPSSO JSON add-on to disable the Schema head attributes.
			 */
			if ( apply_filters( $this->p->lca . '_add_schema_head_attributes', true ) ) {

				$filter_name = SucomUtil::get_const( 'WPSSO_HEAD_ATTR_FILTER_NAME', 'head_attributes' );

				if ( empty( $filter_name ) || 'none' === $filter_name ) {

					// Nothing to do.

				} else {

					echo apply_filters( $filter_name, '' );
				}
			}
		}

		public function filter_head_attributes( $head_attr = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$use_post = apply_filters( $this->p->lca . '_use_post', false );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'required call to get_page_mod()' );
			}

			$mod           = $this->p->util->get_page_mod( $use_post );
			$page_type_id  = $this->p->schema->get_mod_schema_type( $mod, $get_id = true );
			$page_type_url = $this->p->schema->get_schema_type_url( $page_type_id );

			if ( empty( $page_type_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: schema head type value is empty' );
				}

				return $head_attr;
			}

			/**
			 * Fix incorrect itemscope values
			 */
			if ( false !== strpos( $head_attr, 'itemscope="itemscope"' ) ) {

				$head_attr = preg_replace( '/ *itemscope="itemscope"/', ' itemscope', $head_attr );

			} elseif ( false === strpos( $head_attr, 'itemscope' ) ) {

				$head_attr .= ' itemscope';
			}

			/**
			 * Replace existing itemtype values.
			 */
			if ( false !== strpos( $head_attr, 'itemtype="' ) ) {

				$head_attr = preg_replace( '/ *itemtype="[^"]+"/', ' itemtype="' . $page_type_url . '"', $head_attr );

			} else {

				$head_attr .= ' itemtype="' . $page_type_url . '"';
			}

			$head_attr = trim( $head_attr );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning head attributes "' . $head_attr . '"' );
			}

			return $head_attr;
		}

		public function get_array( array $mod, array $mt_og = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Returns false when the wpsso-schema-json-ld add-on is active.
			 */
			if ( ! apply_filters( $this->p->lca . '_add_schema_meta_array', true ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: schema meta array disabled' );
				}

				return array();	// Empty array.
			}

			$mt_item       = array();
			$page_type_id  = $this->p->schema->get_mod_schema_type( $mod, $get_id = true );
			$page_type_url = $this->p->schema->get_schema_type_url( $page_type_id );

			/**
			 * Property:
			 *	url
			 */
			if ( empty( $this->p->options[ 'add_link_itemprop_url' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping url: link itemprop url is disabled' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting url (fragment anchor or canonical url)' );
				}

				if ( empty( $mod[ 'is_public' ] ) ) {				// Since WPSSO Core v7.0.0.

					$mt_item[ 'url' ] = WpssoUtil::get_fragment_anchor( $mod );	// Since WPSSO Core v7.0.0.

				} else {

					$mt_item[ 'url' ] = $this->p->util->get_canonical_url( $mod );
				}
			}

			/**
			 * Property:
			 *	name
			 */
			if ( empty( $this->p->options[ 'add_meta_itemprop_name' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping name: meta itemprop name is disabled' );
				}

			} else {

				$mt_item[ 'name' ] = $this->p->page->get_title( 0, '', $mod, $read_cache = true,
					$add_hashtags = false, $do_encode = true, $md_key = 'schema_title' );
			}

			/**
			 * Property:
			 *	alternatename
			 */
			if ( empty( $this->p->options[ 'add_meta_itemprop_alternatename' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping alternatename: meta itemprop alternatename is disabled' );
				}

			} else {

				$title_max_len = $this->p->options[ 'og_title_max_len' ];

				$mt_item[ 'alternatename' ] = $this->p->page->get_title( $title_max_len, $dots = '...', $mod,
					$read_cache = true, $add_hashtags = false, $do_encode = true,
						$md_key = 'schema_title_alt' );

				if ( isset( $mt_item[ 'name' ] ) ) {

					if ( $mt_item[ 'name' ] === $mt_item[ 'alternatename' ] ) {	// Prevent duplicate values.

						unset( $mt_item[ 'alternatename' ] );
					}
				}
			}

			/**
			 * Property:
			 *	description
			 */
			if ( empty( $this->p->options[ 'add_meta_itemprop_description' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping description: meta itemprop description is disabled' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting schema description with fallback schema_desc, seo_desc, og_desc' );
				}

				$mt_item[ 'description' ] = $this->p->page->get_description( $this->p->options[ 'schema_desc_max_len' ],
					$dots = '...', $mod, $read_cache = true, $add_hashtags = false, $do_encode = true,
						$md_key = array( 'schema_desc', 'seo_desc', 'og_desc' ) );
			}

			/**
			 * Property:
			 *	image
			 */
			if ( empty( $this->p->options[ 'add_link_itemprop_image' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping images: link itemprop image is disabled' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting images for ' . $page_type_url );
				}

				$max_nums = $this->p->util->get_max_nums( $mod, 'schema' );

				$mt_images = $this->p->og->get_all_images( $max_nums[ 'schema_img_max' ], $size_names = 'schema', $mod, true, $md_pre = 'schema' );

				foreach ( $mt_images as $mt_single_image ) {

					$mt_item[ 'image' ][] = SucomUtil::get_first_mt_media_url( $mt_single_image );
				}
			}

			/**
			 * Property:
			 *	thumbnailurl
			 */
			if ( empty( $this->p->options[ 'add_link_itemprop_thumbnailurl' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping thumbnail: link itemprop thumbnailurl is disabled' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting thumbnailurl for ' . $page_type_url );
				}

				$mt_item[ 'thumbnailurl' ] = $this->p->og->get_thumbnail_url( $this->p->lca . '-thumbnail', $mod, $md_pre = 'schema' );

				if ( empty( $mt_item[ 'thumbnailurl' ] ) ) {

					unset( $mt_item[ 'thumbnailurl' ] );
				}
			}

			return (array) apply_filters( $this->p->lca . '_schema_meta_itemprop', $mt_item, $mod, $mt_og, $page_type_id );
		}
	}
}
