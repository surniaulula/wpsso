<?php
/*
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

if ( ! class_exists( 'WpssoIntegSeoWpMetaSeo' ) ) {

	class WpssoIntegSeoWpMetaSeo {

		private $p;	// Wpsso class object.

		private $hs;
		private $hs_it;
		private $opts = null;

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

			if ( $mod[ 'is_post' ] ) {

				$meta_key = '_metaseo_metatitle';

			} elseif ( $mod[ 'is_term' ] && 'category' === $mod[ 'tax_slug' ] ) {

				$meta_key = 'wpms_' . $mod[ 'tax_slug' ] . '_metatitle';

			} else {

				return $title_text;
			}

			return WpssoAbstractWpMeta::get_mod_meta( $mod, $meta_key, $single = true );
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $mod[ 'is_post' ] ) {

				$meta_key = '_metaseo_metadesc';

			} elseif ( $mod[ 'is_term' ] && 'category' === $mod[ 'tax_slug' ] ) {

				$meta_key = 'wpms_' . $mod[ 'tax_slug' ] . '_metatitle';

			} else {

				return $title_text;
			}

			return WpssoAbstractWpMeta::get_mod_meta( $mod, $meta_key, $single = true );
		}
	}
}
