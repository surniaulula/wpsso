<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegSeoAioseop' ) ) {

	class WpssoIntegSeoAioseop {

		private $p;	// Wpsso class object.

		private $cache_options;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'title_seed'       => 5,
				'description_seed' => 4,
			), 100 );
		}

		public function filter_title_seed( $title_text, $mod, $num_hashtags, $md_key, $title_sep ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_home_posts' ] ) {	// Static posts page or blog archive page.

				$title_text = aioseo()->meta->title->getHomePageTitle();

			} elseif ( $mod[ 'is_post' ] ) {

				$post_obj = SucomUtil::get_post_object( $mod[ 'id' ] );

				$title_text = aioseo()->meta->title->getPostTitle( $post_obj );

			} elseif ( $mod[ 'is_term' ] ) {

				$term_obj = SucomUtil::get_term_object( $mod[ 'id' ] );

				$title_text = aioseo()->meta->title->getTermTitle( $term_obj );
			}

			$title_text = $this->maybe_convert_vars( $mod, $title_text );

			return $title_text;
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_home_posts' ] ) {	// Static posts page or blog archive page.

				$desc_text = aioseo()->meta->description->getHomePageDescription();

			} elseif ( $mod[ 'is_post' ] ) {

				$post_obj = SucomUtil::get_post_object( $mod[ 'id' ] );

				$desc_text = aioseo()->meta->description->getPostDescription( $post_obj );

			} elseif ( $mod[ 'is_term' ] ) {

				$term_obj = SucomUtil::get_term_object( $mod[ 'id' ] );

				$desc_text = aioseo()->meta->description->getTermDescription( $term_obj );
			}

			$desc_text = $this->maybe_convert_vars( $mod, $desc_text );

			return $desc_text;
		}

		private function maybe_convert_vars( array $mod, $text ) {

			if ( false !== strpos( $text, '#' ) ) {

				$text = preg_replace( '/(^| )#([^#]+)( |$)/', '%%$2%%', $text );	// Convert inline variable names.
			}

			return $text;
		}
	}
}
