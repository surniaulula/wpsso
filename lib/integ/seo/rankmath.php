<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

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
		}

		public function filter_primary_term_id( $primary_term_id, $mod, $tax_slug, $is_custom ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $is_custom ) {

				if ( $mod[ 'id' ] ) {

					if ( $mod[ 'is_post' ] ) {

						if ( $ret = get_post_meta( $mod[ 'id' ], $meta_key = 'rank_math_primary_category', $single = true ) ) {

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

			$title_text = $this->maybe_convert_vars( $mod, $title_text );

			return $title_text;
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$desc_text =  WpssoAbstractWpMeta::get_mod_meta( $mod, $meta_key = 'rank_math_description', $single = true );

			$desc_text = $this->maybe_convert_vars( $mod, $desc_text );

			return $desc_text;
		}

		public function filter_post_url( $url, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$canonical = '';

			if ( $mod[ 'id' ] ) {

				$canonical = get_post_meta( $mod[ 'id' ], $meta_key = 'rank_math_canonical_url', $single = true );
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

				$canonical = get_term_meta( $mod[ 'id' ], $meta_key = 'rank_math_canonical_url', $single = true );
			}

			if ( ! empty( $canonical ) ) {

				return $canonical;
			}

			return $url;
		}

		private function maybe_convert_vars( array $mod, $text ) {

			if ( false !== strpos( $text, '%' ) ) {

				$text = preg_replace( '/%+([^%]+)%+/', '%%$1%%', $text );	// Convert inline variable names.
			}

			return $text;
		}
	}
}
