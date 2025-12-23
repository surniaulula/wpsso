<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegDataAbstractSeoMeta' ) ) {

	abstract class WpssoIntegDataAbstractSeoMeta {

		protected $p;	// Wpsso class object.

		protected $plugin_avail_key = '';

		protected $import_opt_keys = array();

		protected $opt_keys_active_plugin = array(
			'og_title'               => true,
			'og_desc'                => true,
			'og_img_id'              => true,
			'og_img_url'             => true,
			'tc_title'               => true,
			'tc_desc'                => true,
			'tc_sum_img_id'          => true,
			'tc_sum_img_url'         => true,
			'tc_lrg_img_id'          => true,
			'tc_lrg_img_url'         => true,
			'schema_article_section' => true,
		);

		protected $opt_keys_inactive_plugin = array(
			'primary_term_id'     => true,
			'seo_title'           => true,
			'seo_desc'            => true,
			'schema_title'        => true,
			'schema_title_bc'     => true,
			'schema_desc'         => true,
			'schema_reading_mins' => true,
			'canonical_url'       => true,
			'redirect_url'        => true,
			'robots_noarchive'    => true,
			'robots_nofollow'     => true,
			'robots_noindex'      => true,
		);

		protected $opt_meta_keys = array(
			'post' => array(),
			'term' => array(),
			'user' => array(),
		);

		protected $cache_imported_meta = array();

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( $this->plugin_avail_key ) {	// Just in case.

				if ( empty( $this->p->avail[ 'seo' ][ $this->plugin_avail_key ] ) ) {	// SEO plugin is not active.

					$this->import_opt_keys = array_merge( $this->opt_keys_active_plugin, $this->opt_keys_inactive_plugin );

				} else {

					$this->import_opt_keys = $this->opt_keys_active_plugin;	// SEO plugin is active.
				}

				$this->p->util->add_plugin_filters( $this, array(
					'save_post_options' => 3,
					'save_term_options' => 3,
					'save_user_options' => 3,
					'get_post_options'  => 3,
					'get_term_options'  => 3,
					'get_user_options'  => 3,
				) );
			}
		}

		public function filter_save_post_options( array $md_opts, $post_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->cache_imported_meta[ 'post' ] = array();

			$md_opts = $this->filter_get_post_options( $md_opts, $post_id, $mod );

			if ( ! empty( $this->cache_imported_meta[ 'post' ] ) ) {

				foreach( $this->cache_imported_meta[ 'post' ] as $meta_key => $bool ) {

					delete_metadata( 'post', $post_id, $meta_key );
				}

				unset( $this->cache_imported_meta[ 'post' ] );
			}

			return $md_opts;
		}

		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			foreach ( $this->import_opt_keys as $opt_key => $bool ) {

				if ( ! empty( $this->opt_meta_keys[ 'post' ][ $opt_key ] ) ) {	// Boolean is true.

					/*
					 * Example:
					 *
					 * 	$opt_key = 'primary_term_id'
					 * 	$meta_key = '_yoast_wpseo_primary_%%post_primary_tax_slug%%'
					 */
					$meta_key = $this->opt_meta_keys[ 'post' ][ $opt_key ];

					$meta_key = $this->p->util->inline->replace_variables( $meta_key, $mod );

					/*
					 * Skip options that have a custom value. An empty string and 'none' are not custom values.
					 */
					if ( isset( $md_opts[ $opt_key ] ) && '' !== $md_opts[ $opt_key ] && 'none' !== $md_opts[ $opt_key ] ) {

						continue;	// Keep custom options value.

					} elseif ( $this->add_mod_post_meta( $mod, $md_opts, $opt_key, $meta_key ) ) {

						$this->cache_imported_meta[ 'post' ][ $meta_key ] = true;
					}
				}
			}

			return $md_opts;
		}

		protected function add_mod_post_meta( array $mod, &$md_opts, $opt_key, $meta_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $meta_key ) ) {	// Just in case.

				return false;
			}

			$value = (string) get_metadata( 'post', $mod[ 'id' ], $meta_key, $single = true );

			if ( '' === $value ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'post meta ' . $meta_key . ' is empty' );
				}

				return false;
			}

			$value = $this->maybe_convert_value( $mod, $opt_key, $meta_key, $value );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding ' . $opt_key . ' from post meta ' . $meta_key );
			}

			$md_opts[ $opt_key ] = $value;

			return true;
		}

		public function filter_save_term_options( array $md_opts, $term_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->cache_imported_meta[ 'term' ] = array();

			$md_opts = $this->filter_get_term_options( $md_opts, $term_id, $mod );

			if ( ! empty( $this->cache_imported_meta[ 'term' ] ) ) {

				foreach( $this->cache_imported_meta[ 'term' ] as $meta_key => $bool ) {

					delete_metadata( 'term', $term_id, $meta_key );
				}

				unset( $this->cache_imported_meta[ 'term' ] );
			}

			return $md_opts;
		}

		public function filter_get_term_options( array $md_opts, $term_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			foreach ( $this->import_opt_keys as $opt_key => $bool ) {

				if ( ! empty( $this->opt_meta_keys[ 'term' ][ $opt_key ] ) ) {

					$meta_key = $this->opt_meta_keys[ 'term' ][ $opt_key ];

					/*
					 * Skip options that have a custom value. An empty string and 'none' are not custom values.
					 */
					if ( isset( $md_opts[ $opt_key ] ) && '' !== $md_opts[ $opt_key ] && 'none' !== $md_opts[ $opt_key ] ) {

						continue;	// Keep custom options value.

					} elseif ( $this->add_mod_term_meta( $mod, $md_opts, $opt_key, $meta_key ) ) {

						$this->cache_imported_meta[ 'term' ][ $meta_key ] = true;
					}
				}
			}

			return $md_opts;
		}

		protected function add_mod_term_meta( array $mod, &$md_opts, $opt_key, $meta_key, $term_opts = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $meta_key ) ) {	// Just in case.

				return false;
			}

			if ( null === $term_opts ) {

				$value = (string) get_metadata( 'term', $mod[ 'id' ], $meta_key, $single = true );

			} else {

				$value = isset( $term_opts[ $meta_key ] ) ? (string) $term_opts[ $meta_key ] : '';
			}

			if ( '' === $value ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'term meta ' . $meta_key . ' is empty' );
				}

				return false;
			}

			$value = $this->maybe_convert_value( $mod, $opt_key, $meta_key, $value );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding ' . $opt_key . ' from term meta ' . $meta_key );
			}

			$md_opts[ $opt_key ] = $value;

			return true;
		}

		public function filter_save_user_options( array $md_opts, $user_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->cache_imported_meta[ 'user' ] = array();

			$md_opts = $this->filter_get_user_options( $md_opts, $user_id, $mod );

			if ( ! empty( $this->cache_imported_meta[ 'user' ] ) ) {

				foreach( $this->cache_imported_meta[ 'user' ] as $meta_key => $bool ) {

					delete_metadata( 'user', $user_id, $meta_key );
				}

				unset( $this->cache_imported_meta[ 'user' ] );
			}

			return $md_opts;
		}

		public function filter_get_user_options( array $md_opts, $user_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			foreach ( $this->import_opt_keys as $opt_key => $bool ) {

				if ( ! empty( $this->opt_meta_keys[ 'user' ][ $opt_key ] ) ) {

					$meta_key = $this->opt_meta_keys[ 'user' ][ $opt_key ];

					/*
					 * Skip options that have a custom value. An empty string and 'none' are not custom values.
					 */
					if ( isset( $md_opts[ $opt_key ] ) && '' !== $md_opts[ $opt_key ] && 'none' !== $md_opts[ $opt_key ] ) {

						continue;	// Keep custom options value.

					} elseif ( $this->add_mod_user_meta( $mod, $md_opts, $opt_key, $meta_key ) ) {

						$this->cache_imported_meta[ 'user' ][ $meta_key ] = true;
					}
				}
			}

			return $md_opts;
		}

		protected function add_mod_user_meta( array $mod, &$md_opts, $opt_key, $meta_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $meta_key ) ) {	// Just in case.

				return false;
			}

			$value = (string) get_metadata( 'user', $mod[ 'id' ], $meta_key, $single = true );

			if ( '' === $value ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'user meta ' . $meta_key . ' is empty' );
				}

				return false;
			}

			$value = $this->maybe_convert_value( $mod, $opt_key, $meta_key, $value );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding ' . $opt_key . ' from user meta ' . $meta_key );
			}

			$md_opts[ $opt_key ] = $value;

			return true;
		}

		protected function maybe_convert_value( array $mod, $opt_key, $meta_key, $value ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$value = $this->maybe_convert_vars( $value, $mod );

			switch ( $opt_key ) {

				case 'robots_noarchive':
				case 'robots_nofollow':
				case 'robots_noindex':

					if ( '1' === $value || 'on' === $value ) {

						return 1;

					} elseif ( '-1' === $value || 'off' === $value ) {

						return 0;

					} elseif ( 'robots_noindex' === $opt_key && 'noindex' === $value ) {

						return 1;

					} elseif ( 'robots_noindex' === $opt_key && 'index' === $value ) {

						return 0;
					}

					break;
			}

			return $value;
		}

		protected function maybe_convert_vars( $value, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $value;
		}
	}
}
