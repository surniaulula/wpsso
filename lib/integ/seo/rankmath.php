<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

/*
 * Integration module for the Rank Math SEO plugin.
 *
 * See https://wordpress.org/plugins/seo-by-rank-math/.
 */
if ( ! class_exists( 'WpssoIntegSeoRankmath' ) ) {

	class WpssoIntegSeoRankmath {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'primary_term_id'  => 4,
				'primary_terms'    => 3,
				'title_seed'       => 5,
				'description_seed' => 4,
				'post_url'         => 2,
				'term_url'         => 2,
			), 100 );

			if ( is_admin() ) {

				$this->p->util->add_plugin_filters( $this, array( 'admin_page_style_css' => 1 ), 100 );

			} else {

				add_action( 'rank_math/head', array( $this, 'cleanup_rankmath_actions' ), -2000, 0 );

				add_filter( 'rank_math/json_ld', array( $this, 'cleanup_rankmath_json_ld' ), PHP_INT_MAX, 1 );
			}
		}

		public function filter_primary_term_id( $primary_term_id, $mod, $tax_slug, $is_custom ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $is_custom ) {

				if ( $mod[ 'id' ] ) {

					if ( $mod[ 'is_post' ] ) {

						if ( $ret = get_metadata( 'post', $mod[ 'id' ], $meta_key = 'rank_math_primary_category', $single = true ) ) {

							return $ret;
						}
					}
				}
			}

			return $primary_term_id;
		}

		public function filter_primary_terms( $primary_terms, $mod, $tax_slug ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $primary_term_id = $this->filter_primary_term_id( false, $mod, $tax_slug, $is_custom = false ) ) {

				if ( empty( $primary_terms[ $primary_term_id ] ) ) {

					$term_obj = get_term( $primary_term_id );

					if ( isset( $term_obj->term_id ) ) {	// Just in case.

						$primary_terms[ $term_obj->term_id ] = $term_obj->name;
					}
				}
			}

			return $primary_terms;
		}

		public function filter_title_seed( $title_text, $mod, $num_hashtags, $md_key, $title_sep ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$title_text = WpssoAbstractWpMeta::get_mod_meta( $mod, $meta_key = 'rank_math_title', $single = true );

			if ( empty( $title_text ) ) {

				$title_text = ( new class { use \RankMath\Helpers\Api; } )::get_settings( 'titles.pt_' . $mod[ 'post_type' ] . '_default_snippet_name' );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'default_snippet_name = ' . $title_text );
				}
			}

			$title_text = $this->maybe_convert_vars( $title_text, $mod );

			return $title_text;
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$desc_text = WpssoAbstractWpMeta::get_mod_meta( $mod, $meta_key = 'rank_math_description', $single = true );

			if ( empty( $desc_text ) ) {

				$desc_text = ( new class { use \RankMath\Helpers\Api; } )::get_settings( 'titles.pt_' . $mod[ 'post_type' ] . '_default_snippet_desc' );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'default_snippet_desc = ' . $desc_text );
				}
			}

			$desc_text = $this->maybe_convert_vars( $desc_text, $mod );

			return $desc_text;
		}

		public function filter_post_url( $url, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$canonical = '';

			if ( $mod[ 'id' ] ) {

				$canonical = get_metadata( 'post', $mod[ 'id' ], $meta_key = 'rank_math_canonical_url', $single = true );
			}

			if ( ! empty( $canonical ) ) {

				return $canonical;
			}

			return $url;
		}

		public function filter_term_url( $url, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$canonical = '';

			if ( $mod[ 'id' ] ) {

				$canonical = get_metadata( 'term', $mod[ 'id' ], $meta_key = 'rank_math_canonical_url', $single = true );
			}

			if ( ! empty( $canonical ) ) {

				return $canonical;
			}

			return $url;
		}

		private function maybe_convert_vars( $value, array $mod ) {

			if ( false !== strpos( $value, '%' ) ) {

				$value = preg_replace( '/%+([^%]+)%+/', '%%$1%%', $value );	// Convert inline variable names.

				$value = $this->p->util->inline->replace_variables( $value, $mod );
			}

			return $value;
		}

		/*
		 * Fix Rank Math CSS on back-end pages.
		 */
		public function filter_admin_page_style_css( $custom_style_css ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Fix the width of Rank Math list table columns.
			 */
			$custom_style_css .= '
				table.wp-list-table > thead > tr > th.column-rank_math_seo_details,
				table.wp-list-table > tbody > tr > td.column-rank_math_seo_details {
					width:170px;
				}
			';

			/*
			 * The "Social" metabox tab and its options cannot be disabled, so hide them instead.
			 */
			$custom_style_css .= '
				.rank-math-tabs > div > a[href="#setting-panel-social"] { display: none; }
				.rank-math-tabs-content .setting-panel-social { display: none; }
			';

			/*
			 * The "Schema" metabox tab and its options cannot be disabled, so hide them instead.
			 */
			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				$custom_style_css .= '
					.rank-math-tabs > div > a[href="#setting-panel-richsnippet"] { display: none; }
					.rank-math-tabs-content .setting-panel-richsnippet { display: none; }
				';
			}

			return $custom_style_css;
		}

		/*
		 * Disable Rank Math Facebook and X (Twitter) meta tags.
		 */
		public function cleanup_rankmath_actions() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Disable Rank Math social meta tags.
			 */
			remove_all_actions( 'rank_math/opengraph/facebook' );
			remove_all_actions( 'rank_math/opengraph/slack' );
			remove_all_actions( 'rank_math/opengraph/twitter' );
		}

		/*
		 * Disable Rank Math Schema markup.
		 */
		public function cleanup_rankmath_json_ld( $data ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Remove everything except for the BreadcrumbList markup.
			 *
			 * The WPSSO BC add-on removes the BreadcrumbList markup.
			 */
			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				return SucomUtil::preg_grep_keys( '/^BreadcrumbList$/', $data );
			}

			return $data;
		}
	}
}
