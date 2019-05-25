<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoNoScript' ) ) {

	class WpssoNoScript {

		protected $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public static function is_enabled( $crawler_name = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {
				$wpsso->debug->mark();
			}

			if ( SucomUtil::is_amp() ) {

				if ( $wpsso->debug->enabled ) {
					$wpsso->debug->log( 'noscript disabled for amp endpoint' );
				}

				return false;
			}

			if ( false === $crawler_name ) {

				if ( is_admin() ) {
					$crawler_name = 'none';
				} else {
					$crawler_name = SucomUtil::get_crawler_name();
				}
			}

			$is_enabled = empty( $wpsso->options[ 'schema_add_noscript' ] ) ? false : true;

			/**
			 * Always returns false when the WPSSO JSON add-on is active.
			 */
			if ( apply_filters( $wpsso->lca . '_add_schema_noscript_array', $is_enabled, $crawler_name ) ) {

				return true;

			} else {

				if ( $wpsso->debug->enabled ) {
					$wpsso->debug->log( 'noscript is disabled for crawler "' . $crawler_name . '"' );
				}

				return false;
			}
		}

		public function get_array( array &$mod, array &$mt_og, $crawler_name ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! self::is_enabled( $crawler_name ) ) {
				return array();	// Empty array.
			}

			$ret           = array();
			$max_nums      = $this->p->util->get_max_nums( $mod, 'schema' );
			$page_type_id  = $this->p->schema->get_mod_schema_type( $mod, $get_schema_id = true );
			$page_type_url = $this->p->schema->get_schema_type_url( $page_type_id );
			$size_name     = $this->p->lca . '-schema';
			$og_type_id    = $mt_og[ 'og:type' ];

			switch ( $page_type_url ) {

				case 'https://schema.org/BlogPosting':

					$size_name = $this->p->lca . '-schema-article';

					// No break - get the webpage author list as well.

				case 'https://schema.org/WebPage':

					$ret = array_merge( $ret, $this->get_author_list( $mod ) );

					break;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'getting images for ' . $page_type_url );
			}

			$og_images = $this->p->og->get_all_images( $max_nums[ 'schema_img_max' ], $size_name, $mod, true, $md_pre = 'schema' );

			if ( empty( $og_images ) && $mod[ 'is_post' ] ) {
				$og_images = $this->p->media->get_default_images( 1, $size_name, true );
			}

			foreach ( $og_images as $og_single_image ) {
				$ret = array_merge( $ret, $this->get_single_image( $mod, $og_single_image ) );
			}

			if ( ! empty( $mt_og[ $og_type_id . ':rating:average' ] ) ) {
				$ret = array_merge( $ret, $this->get_aggregate_rating( $mod, $og_type_id, $mt_og ) );
			}

			return (array) apply_filters( $this->p->lca . '_schema_noscript_array', $ret, $mod, $mt_og, $page_type_id );
		}

		public function get_single_image( array &$mod, &$mixed, $mt_image_pre = 'og:image' ) {

			$mt_image = array();

			if ( empty( $mixed ) ) {

				return array();

			} elseif ( is_array( $mixed ) ) {

				$image_url = SucomUtil::get_mt_media_url( $mixed, $mt_image_pre );

				if ( empty( $image_url ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: ' . $mt_image_pre . ' url values are empty' );
					}
					return array();
				}

				/**
				 * Defines a two-dimensional array.
				 */
				$mt_image = array_merge(
					$this->p->head->get_single_mt( 'link', 'itemprop', 'image.url', $image_url, '', $mod ),			// Link itemprop.
					( empty( $mixed[ $mt_image_pre . ':width' ] ) ? array() : $this->p->head->get_single_mt( 'meta',	// Meta itemprop.
						'itemprop', 'image.width', $mixed[ $mt_image_pre . ':width' ], '', $mod ) ),
					( empty( $mixed[ $mt_image_pre . ':height' ] ) ? array() : $this->p->head->get_single_mt( 'meta',	// Meta itemprop.
						'itemprop', 'image.height', $mixed[ $mt_image_pre . ':height' ], '', $mod ) )
				);

			} else {

				/**
				 * Defines a two-dimensional array.
				 */
				$mt_image = $this->p->head->get_single_mt( 'link', 'itemprop', 'image.url', $mixed, '', $mod );	// Link itemprop.
			}

			/**
			 * Make sure we have html for at least one meta tag.
			 */
			$have_image_html = false;

			foreach ( $mt_image as $num => $img ) {

				if ( ! empty( $img[0] ) ) {

					$have_image_html = true;

					break;
				}
			}

			if ( $have_image_html ) {

				return array_merge(
					array( array( '<noscript itemprop="image" itemscope itemtype="https://schema.org/ImageObject">' . "\n" ) ),
					$mt_image,
					array( array( '</noscript>' . "\n" ) )
				);

			} else {
				return array();
			}
		}

		public function get_aggregate_rating( array &$mod, $og_type_id, array $mt_og ) {

			/**
			 * Aggregate rating needs at least one rating or review count.
			 */
			if ( empty( $mt_og[ $og_type_id . ':rating:average' ] ) ||
				( empty( $mt_og[ $og_type_id . ':rating:count' ] ) && empty( $mt_og[ $og_type_id . ':review:count' ] ) ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: rating average and/or counts are empty' );
				}

				return array();
			}

			return array_merge(
				array( array( '<noscript itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">' . "\n" ) ),
				( empty( $mt_og[ $og_type_id . ':rating:average' ] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop',
						'aggregaterating.ratingValue', $mt_og[ $og_type_id . ':rating:average' ], '', $mod ) ),
				( empty( $mt_og[ $og_type_id . ':rating:count' ] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop',
						'aggregaterating.ratingCount', $mt_og[ $og_type_id . ':rating:count' ], '', $mod ) ),
				( empty( $mt_og[ $og_type_id . ':rating:worst' ] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop',
						'aggregaterating.worstRating', $mt_og[ $og_type_id . ':rating:worst' ], '', $mod ) ),
				( empty( $mt_og[ $og_type_id . ':rating:best' ] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop',
						'aggregaterating.bestRating', $mt_og[ $og_type_id . ':rating:best' ], '', $mod ) ),
				( empty( $mt_og[ $og_type_id . ':review:count' ] ) ? 
					array() : $this->p->head->get_single_mt( 'meta', 'itemprop', 
						'aggregaterating.reviewCount', $mt_og[ $og_type_id . ':review:count' ], '', $mod ) ),
				array( array( '</noscript>' . "\n" ) )
			);
		}

		public function get_author_list( array &$mod ) {

			if ( empty( $mod[ 'post_author' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: empty post_author' );
				}

				return array();
			}

			$ret = $this->get_single_author( $mod, $mod[ 'post_author' ], 'author' );

			if ( ! empty( $mod[ 'post_coauthors' ] ) ) {
				foreach ( $mod[ 'post_coauthors' ] as $author_id ) {
					$ret = array_merge( $ret, $this->get_single_author( $mod, $author_id, 'contributor' ) );
				}
			}

			return $ret;
		}

		public function get_single_author( array &$mod, $author_id = 0, $prop_name = 'author' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'author_id' => $author_id,
					'prop_name' => $prop_name,
				) );
			}

			$og_ret = array();

			$size_name = $this->p->lca . '-schema';

			if ( empty( $author_id ) || $author_id === 'none' ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: empty author_id' );
				}

				return array();
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'getting user_mod for author id ' . $author_id );
			}

			$user_mod = $this->p->user->get_mod( $author_id );

			/**
			 * Set the reference values for admin notices.
			 */
			if ( is_admin() ) {

				$sharing_url = $this->p->util->get_sharing_url( $user_mod );
	
				$this->p->notice->set_ref( $sharing_url, $user_mod,
					sprintf( __( 'adding schema noscript for author user ID %1$s', 'wpsso' ), $author_id ) );
			}

			$author_url  = $user_mod[ 'obj' ]->get_author_website( $author_id, 'url' );
			$author_name = $user_mod[ 'obj' ]->get_author_meta( $author_id, $this->p->options[ 'seo_author_name' ] );
			$author_desc = $user_mod[ 'obj' ]->get_options_multi( $author_id, $md_key = array( 'schema_desc', 'seo_desc', 'og_desc' ) );

			if ( empty( $author_desc ) ) {
				$author_desc = $user_mod[ 'obj' ]->get_author_meta( $author_id, 'description' );
			}

			$mt_author = array_merge(
				( empty( $author_url ) ? array() : $this->p->head->get_single_mt( 'link',	// Link itemprop.
					'itemprop', $prop_name . '.url', $author_url, '', $user_mod ) ),
				( empty( $author_name ) ? array() : $this->p->head->get_single_mt( 'meta',	// Meta itemprop.
					'itemprop', $prop_name . '.name', $author_name, '', $user_mod ) ),
				( empty( $author_desc ) ? array() : $this->p->head->get_single_mt( 'meta',	// Meta itemprop.
					'itemprop', $prop_name . '.description', $author_desc, '', $user_mod ) )
			);

			/**
			 * Optimize by first checking if the head tag is enabled.
			 */
			if ( ! empty( $this->p->options[ 'add_link_itemprop_author.image' ] ) ) {

				/**
				 * get_og_images() also provides filter hooks for additional image ids and urls.
				 */
				$og_images = $user_mod[ 'obj' ]->get_og_images( 1, $size_name, $author_id, false );	// $check_dupes is false.
	
				foreach ( $og_images as $og_single_image ) {

					$image_url = SucomUtil::get_mt_media_url( $og_single_image );

					if ( ! empty( $image_url ) ) {
						$mt_author = array_merge( $mt_author, $this->p->head->get_single_mt( 'link',
							'itemprop', $prop_name . '.image', $image_url, '', $user_mod ) );
					}
				}
			}

			/**
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {
				$this->p->notice->unset_ref( $sharing_url );
			}

			/**
			 * Make sure we have html for at least one meta tag.
			 */
			$have_author_html = false;

			foreach ( $mt_author as $num => $author ) {
				if ( ! empty( $author[0] ) ) {
					$have_author_html = true;
					break;
				}
			}

			if ( $have_author_html ) {
				return array_merge(
					array( array( '<noscript itemprop="' . $prop_name . '" itemscope itemtype="https://schema.org/Person">' . "\n" ) ),
					$mt_author,
					array( array( '</noscript>' . "\n" ) )
				);
			} else {
				return array();
			}
		}
	}
}
