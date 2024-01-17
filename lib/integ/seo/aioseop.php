<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

/*
 * Integration module for the All in One SEO plugin.
 *
 * See https://wordpress.org/plugins/all-in-one-seo-pack/.
 */
if ( ! class_exists( 'WpssoIntegSeoAioseop' ) ) {

	class WpssoIntegSeoAioseop {

		private $p;	// Wpsso class object.

		private $cache_options;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( is_admin() ) {

				$this->p->util->add_plugin_filters( $this, array(
					'features_status_integ_data_aioseop_meta' => 1,
				), 100 );
			}

			$this->p->util->add_plugin_filters( $this, array(
				'title_seed'       => 5,
				'description_seed' => 4,
			), 100 );
		}

		public function filter_features_status_integ_data_aioseop_meta( $features_status ) {

			return 'off' === $features_status ? 'rec' : $features_status;
		}

		public function filter_title_seed( $title_text, $mod, $num_hashtags, $md_key, $title_sep ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_home_posts' ] ) {	// Static posts page or blog archive page.

				$title_text = aioseo()->meta->title->getHomePageTitle();

			} elseif ( $mod[ 'is_post' ] ) {

				$post_obj = SucomUtilWP::get_post_object( $mod[ 'id' ] );

				$title_text = aioseo()->meta->title->getPostTitle( $post_obj );

			} elseif ( $mod[ 'is_term' ] ) {

				$term_obj = SucomUtilWP::get_term_object( $mod[ 'id' ] );

				$title_text = aioseo()->meta->title->getTermTitle( $term_obj );
			}

			$title_text = $this->maybe_convert_vars( $title_text, $mod );

			return $title_text;
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_home_posts' ] ) {	// Static posts page or blog archive page.

				$desc_text = aioseo()->meta->description->getHomePageDescription();

			} elseif ( $mod[ 'is_post' ] ) {

				$post_obj = SucomUtilWP::get_post_object( $mod[ 'id' ] );

				$desc_text = aioseo()->meta->description->getPostDescription( $post_obj );

			} elseif ( $mod[ 'is_term' ] ) {

				$term_obj = SucomUtilWP::get_term_object( $mod[ 'id' ] );

				$desc_text = aioseo()->meta->description->getTermDescription( $term_obj );
			}

			$desc_text = $this->maybe_convert_vars( $desc_text, $mod );

			return $desc_text;
		}

		private function maybe_convert_vars( $value, array $mod ) {

			if ( false !== strpos( $value, '#' ) ) {

				$value = preg_replace( '/(^| )#([^#]+)( |$)/', '%%$2%%', $value );	// Convert inline variable names.

				$value = $this->p->util->inline->replace_variables( $value, $mod );
			}

			return $value;
		}
	}
}
