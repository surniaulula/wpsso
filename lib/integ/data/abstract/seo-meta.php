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

			$this->cache_imported_meta[ 'post' ] = array();

			$md_opts = $this->filter_get_post_options( $md_opts, $post_id, $mod );

			if ( ! empty( $this->cache_imported_meta[ 'post' ] ) ) {

				foreach( $this->cache_imported_meta[ 'post' ] as $meta_key => $bool ) {

					delete_post_meta( $post_id, $meta_key );
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

				if ( ! empty( $this->opt_meta_keys[ 'post' ][ $opt_key ] ) ) {

					$meta_key = $this->opt_meta_keys[ 'post' ][ $opt_key ];

					// Skip options that have a custom value. An empty string and 'none' are not custom values.
					if ( isset( $md_opts[ $opt_key ] ) && '' !== $md_opts[ $opt_key ] && 'none' !== $md_opts[ $opt_key ] ) {

						continue;

					} elseif ( $this->add_mod_post_meta( $mod, $md_opts, $opt_key, $meta_key ) ) {

						$this->cache_imported_meta[ 'post' ][ $meta_key ] = true;
					}
				}
			}

			return $md_opts;
		}

		protected function add_mod_post_meta( array $mod, &$md_opts, $opt_key, $meta_key ) {

			if ( empty( $meta_key ) ) {	// Just in case.

				return false;
			}

			$meta_value = (string) get_post_meta( $mod[ 'id' ], $meta_key, $single = true );

			if ( '' === $meta_value ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'post meta ' . $meta_key . ' is empty' );
				}

				return false;
			}

			$meta_value = $this->maybe_convert_value( $mod, $opt_key, $meta_key, $meta_value );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding ' . $opt_key . ' from post meta ' . $meta_key );
			}

			$md_opts[ $opt_key ] = $meta_value;

			return true;
		}

		public function filter_save_term_options( array $md_opts, $term_id, array $mod ) {

			$this->cache_imported_meta[ 'term' ] = array();

			$md_opts = $this->filter_get_term_options( $md_opts, $term_id, $mod );

			if ( ! empty( $this->cache_imported_meta[ 'term' ] ) ) {

				foreach( $this->cache_imported_meta[ 'term' ] as $meta_key => $bool ) {

					WpssoTerm::delete_term_meta( $term_id, $meta_key );
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

					// Skip options that have a custom value. An empty string and 'none' are not custom values.
					if ( isset( $md_opts[ $opt_key ] ) && '' !== $md_opts[ $opt_key ] && 'none' !== $md_opts[ $opt_key ] ) {

						continue;

					} elseif ( $this->add_mod_term_meta( $mod, $md_opts, $opt_key, $meta_key ) ) {

						$this->cache_imported_meta[ 'term' ][ $meta_key ] = true;
					}
				}
			}

			return $md_opts;
		}

		protected function add_mod_term_meta( array $mod, &$md_opts, $opt_key, $meta_key, $term_opts = null ) {

			if ( empty( $meta_key ) ) {	// Just in case.

				return false;
			}

			if ( null === $term_opts ) {

				$meta_value = (string) WpssoTerm::get_term_meta( $mod[ 'id' ], $meta_key, $single = true );

			} else {

				$meta_value = isset( $term_opts[ $meta_key ] ) ? (string) $term_opts[ $meta_key ] : '';
			}

			if ( '' === $meta_value ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'term meta ' . $meta_key . ' is empty' );
				}

				return false;
			}

			$meta_value = $this->maybe_convert_value( $mod, $opt_key, $meta_key, $meta_value );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding ' . $opt_key . ' from term meta ' . $meta_key );
			}

			$md_opts[ $opt_key ] = $meta_value;

			return true;
		}

		public function filter_save_user_options( array $md_opts, $user_id, array $mod ) {

			$this->cache_imported_meta[ 'user' ] = array();

			$md_opts = $this->filter_get_user_options( $md_opts, $user_id, $mod );

			if ( ! empty( $this->cache_imported_meta[ 'user' ] ) ) {

				foreach( $this->cache_imported_meta[ 'user' ] as $meta_key => $bool ) {

					delete_user_meta( $user_id, $meta_key );
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

					// Skip options that have a custom value. An empty string and 'none' are not custom values.
					if ( isset( $md_opts[ $opt_key ] ) && '' !== $md_opts[ $opt_key ] && 'none' !== $md_opts[ $opt_key ] ) {

						continue;

					} elseif ( $this->add_mod_user_meta( $mod, $md_opts, $opt_key, $meta_key ) ) {

						$this->cache_imported_meta[ 'user' ][ $meta_key ] = true;
					}
				}
			}

			return $md_opts;
		}

		protected function add_mod_user_meta( array $mod, &$md_opts, $opt_key, $meta_key ) {

			if ( empty( $meta_key ) ) {	// Just in case.

				return false;
			}

			$meta_value = (string) get_user_meta( $mod[ 'id' ], $meta_key, $single = true );

			if ( '' === $meta_value ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'user meta ' . $meta_key . ' is empty' );
				}

				return false;
			}

			$meta_value = $this->maybe_convert_value( $mod, $opt_key, $meta_key, $meta_value );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding ' . $opt_key . ' from user meta ' . $meta_key );
			}

			$md_opts[ $opt_key ] = $meta_value;

			return true;
		}

		protected function maybe_convert_value( array $mod, $opt_key, $meta_key, $meta_value ) {

			$meta_value = $this->maybe_convert_vars( $mod, $meta_value );

			switch ( $opt_key ) {

				case 'robots_noarchive':
				case 'robots_nofollow':
				case 'robots_noindex':

					if ( '1' === $meta_value || 'on' === $meta_value ) {

						return 1;

					} elseif ( '-1' === $meta_value || 'off' === $meta_value ) {

						return 0;

					} elseif ( 'robots_noindex' === $opt_key && 'noindex' === $meta_value ) {

						return 1;

					} elseif ( 'robots_noindex' === $opt_key && 'index' === $meta_value ) {

						return 0;
					}

					break;
			}

			return $meta_value;
		}

		protected function maybe_convert_vars( array $mod, $text ) {

			return $text;
		}
	}
}
