<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

/*
 * Integration module for the The SEO Framework plugin.
 *
 * See https://wordpress.org/plugins/autodescription/.
 */
if ( ! class_exists( 'WpssoIntegSeoSeoframework' ) ) {

	class WpssoIntegSeoSeoframework {

		private $p;	// Wpsso class object.

		private $is_admin = false;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->is_admin = is_admin();

			if ( $this->is_admin ) {

				$this->p->util->add_plugin_filters( $this, array(
					'features_status_integ_data_seoframework_meta' => 1,
				), 100 );
			}

			$this->p->util->add_plugin_filters( $this, array(
				'redirect_disabled' => '__return_true',
				'primary_term_id'   => 4,
				'title_seed'        => 5,
				'description_seed'  => 4,
				'post_url'          => 2,
				'redirect_url'      => 3,
			), 100 );

			add_filter( 'the_seo_framework_current_object_id', array( $this, 'current_object_id' ), 10, 1 );
			add_filter( 'the_seo_framework_json_search_output', '__return_false', PHP_INT_MAX );
			add_filter( 'the_seo_framework_ldjson_plugin_detected', '__return_false', PHP_INT_MAX );
			add_filter( 'the_seo_framework_ldjson_scripts', '__return_empty_string', PHP_INT_MAX );
		}

		public function filter_features_status_integ_data_seoframework_meta( $features_status ) {

			return 'off' === $features_status ? 'rec' : $features_status;
		}

		public function filter_primary_term_id( $primary_term_id, $mod, $tax_slug, $is_custom ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $is_custom ) {

				if ( $mod[ 'id' ] ) {

					if ( $mod[ 'is_post' ] ) {

						if ( $this->is_admin ) {

							SucomUtil::maybe_load_post( $mod[ 'id' ] );	// Maybe re-define the $post global.
						}

						$tsf = the_seo_framework();

						if ( $ret = $tsf->get_primary_term_id( $mod[ 'id' ], $tax_slug ) ) {

							return $ret;
						}
					}
				}
			}

			return $primary_term_id;
		}

		public function filter_title_seed( $title_text, $mod, $num_hashtags, $md_key, $title_sep ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] && $mod[ 'id' ] && $this->is_admin ) {

				SucomUtil::maybe_load_post( $mod[ 'id' ] );	// Maybe re-define the $post global.
			}

			/*
			 * The SEO Framework can only provide titles and descriptions by ID for posts and terms.
			 *
			 * Important note:
			 *
			 * 	$tsf->get_title() uses '$social = true' as a third argument.
			 *
			 * 	$tsf->get_description() uses 'social => true' in the $args array.
			 */
			$args = false;

			switch ( $mod[ 'name' ] ) {

				case 'post':

					$args = array(
						'id'  => $mod[ 'id' ],
						'pta' => $mod[ 'is_post_type_archive' ] ? $mod[ 'post_type' ] : '',
					);

					break;

				case 'term':

					$args = array(
						'id'       => $mod[ 'id' ],
						'taxonomy' => $mod[ 'tax_slug' ],
					);

					break;
			}

			if ( ! empty( $args ) ) {

				$tsf = the_seo_framework();

				$title_text = $tsf->get_title( $args, $escape = false, $social = true );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'seo framework title: ' . $title_text );
				}
			}

			return $title_text;
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] && $this->is_admin ) {

				SucomUtil::maybe_load_post( $mod[ 'id' ] );	// Maybe re-define the $post global.
			}

			/*
			 * The SEO Framework can only provide titles and descriptions by ID for posts and terms.
			 *
			 * Important note:
			 *
			 * 	$tsf->get_title() uses '$social = true' as a third argument.
			 *
			 * 	$tsf->get_description() uses 'social => true' in the $args array.
			 */
			$args = false;

			switch ( $mod[ 'name' ] ) {

				case 'post':

					$args = array(
						'id'     => $mod[ 'id' ],
						'pta'    => $mod[ 'is_post_type_archive' ] ? $mod[ 'post_type' ] : '',
						'social' => true,
					);

					break;

				case 'term':

					$args = array(
						'id'       => $mod[ 'id' ],
						'taxonomy' => $mod[ 'tax_slug' ],
						'social'   => true,
					);

					break;
			}

			if ( ! empty( $args ) ) {

				$tsf = the_seo_framework();

				$desc_text = $tsf->get_description( $args, $escape = false );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'seo framework description: ' . $desc_text );
				}
			}

			return $desc_text;
		}

		public function filter_post_url( $url, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] && $this->is_admin ) {

				SucomUtil::maybe_load_post( $mod[ 'id' ] );	// Maybe re-define the $post global.
			}

			$tsf = the_seo_framework();

			$args = array(
				'id'       => $mod[ 'id' ],
				'taxonomy' => $mod[ 'is_term' ] ? $mod[ 'tax_slug' ] : '',
				'pta'      => $mod[ 'is_post_type_archive' ] ? $mod[ 'post_type' ] : '',
			);

			$url = $tsf->get_canonical_url( $args );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'seo framework post_url: ' . $url );
			}

			return $url;
		}

		public function filter_redirect_url( $url, $mod, $is_custom ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] && $this->is_admin ) {

				SucomUtil::maybe_load_post( $mod[ 'id' ] );	// Maybe re-define the $post global.
			}

			$tsf = the_seo_framework();

			$args = array(
				'id'       => $mod[ 'id' ],
				'taxonomy' => $mod[ 'is_term' ] ? $mod[ 'tax_slug' ] : '',
				'pta'      => $mod[ 'is_post_type_archive' ] ? $mod[ 'post_type' ] : '',
			);

			$url = $tsf->get_redirect_url( $args );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'seo framework post_url: ' . $url );
			}

			return $url;
		}

		public function current_object_id( $obj_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $obj_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'required call to WpssoPage->get_mod()' );
				}

				/*
				 * Note that in_the_loop() can be true in both archive and singular pages.
				 */
				$use_post = apply_filters( 'wpsso_use_post', in_the_loop() ? true : false );

				$mod = $this->p->page->get_mod( $use_post );

				$obj_id = $mod[ 'id' ];
			}

			return $obj_id;
		}
	}
}
