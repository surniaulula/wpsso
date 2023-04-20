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
 * Integration module for the WP Meta SEO plugin.
 *
 * See https://wordpress.org/plugins/wp-meta-seo/.
 */
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

			} elseif ( $mod[ 'is_term' ] ) {

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

			} elseif ( $mod[ 'is_term' ] ) {

				$meta_key = 'wpms_' . $mod[ 'tax_slug' ] . '_metatitle';

			} else {

				return $title_text;
			}

			return WpssoAbstractWpMeta::get_mod_meta( $mod, $meta_key, $single = true );
		}
	}
}
