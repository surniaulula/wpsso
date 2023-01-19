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

if ( ! class_exists( 'WpssoIntegSeoWpseo' ) ) {

	class WpssoIntegSeoWpseo {

		private $p;	// Wpsso class object.

		private $wpseo_opts = array();

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! method_exists( 'WPSEO_Options', 'get_all' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exitinge early: WPSEO_Options::get_all not found' );
				}

				if ( is_admin() ) {

					// translators: %1$s is the class::method name.
					$this->p->notice->err( sprintf( __( 'The Yoast SEO <code>%1$s</code> method is missing &ndash; if you are using an older version of Yoast SEO, please update now.', 'wpsso' ), 'WPSEO_Options::get_all()' ) );
				}

				return;
			}

			$this->wpseo_opts = WPSEO_Options::get_all();

			$this->p->util->add_plugin_filters( $this, array(
				'robots_is_noindex' => 2,
				'primary_term_id'   => 4,
				'title_seed'        => 5,
				'description_seed'  => 4,
				'post_url'          => 2,
				'term_url'          => 2,
			), 100 );
		}

		public function filter_robots_is_noindex( $value, $mod ) {

			$meta_val = null;

			if ( $mod[ 'id' ] ) {

				if ( $mod[ 'is_post' ] ) {

					$meta_val = $this->get_post_meta_value( $mod[ 'id' ], $meta_key = 'meta-robots-noindex' );

					$meta_val = $meta_val ? true : false;	// 1 or 0.

				} elseif ( $mod[ 'is_term' ] ) {

					$meta_val = $this->get_term_meta_value( $mod[ 'id' ], $meta_key = 'noindex' );

					$meta_val = 'noindex' === $meta_val ? true : false;

				} elseif ( $mod[ 'is_user' ] ) {

					$meta_val = $this->get_user_meta_value( $mod[ 'id' ], $meta_key = 'noindex_author' );

					$meta_val = 'on' === $meta_val ? true : false;
				}
			}

			return null === $meta_val ? $value : $meta_val;
		}

		public function filter_primary_term_id( $primary_term_id, $mod, $tax_slug, $is_custom ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$meta_val = null;

			if ( ! $is_custom ) {

				if ( $mod[ 'id' ] ) {

					if ( $mod[ 'is_post' ] ) {

						$meta_val = $this->get_post_meta_value( $mod[ 'id' ], $meta_key = 'primary_category' );
					}
				}
			}

			return $meta_val ? $meta_val : $primary_term_id;
		}

		public function filter_title_seed( $title_text, $mod, $num_hashtags, $md_key, $title_sep ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$meta_val = '';

			if ( $mod[ 'id' ] ) {

				if ( $mod[ 'is_post' ] ) {

					$meta_val = $this->get_post_meta_value( $mod[ 'id' ], $meta_key = 'title' );

				} elseif ( $mod[ 'is_term' ] ) {

					$meta_val = $this->get_term_meta_value( $mod[ 'id' ], $meta_key = 'title' );

				} elseif ( $mod[ 'is_user' ] ) {

					$meta_val = $this->get_user_meta_value( $mod[ 'id' ], $meta_key = 'title' );
				}
			}

			return $meta_val ? $meta_val : $title_text;
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$meta_val = '';

			if ( $mod[ 'id' ] ) {

				if ( $mod[ 'is_post' ] ) {

					$meta_val = $this->get_post_meta_value( $mod[ 'id' ], $meta_key = 'metadesc' );

				} elseif ( $mod[ 'is_term' ] ) {

					$meta_val = $this->get_term_meta_value( $mod[ 'id' ], $meta_key = 'metadesc' );

				} elseif ( $mod[ 'is_user' ] ) {

					$meta_val = $this->get_user_meta_value( $mod[ 'id' ], $meta_key = 'metadesc' );
				}
			}

			return $meta_val ? $meta_val : $desc_text;
		}

		public function filter_post_url( $url, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$meta_val = '';

			if ( $mod[ 'id' ] ) {

				if ( class_exists( 'WPSEO_Meta' ) && method_exists( 'WPSEO_Meta', 'get_value' ) ) {

					$meta_val = WPSEO_Meta::get_value( 'canonical', $mod[ 'id' ] );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'WPSEO_Meta::get_value not found' );
				}
			}

			return $meta_val ? $meta_val : $url;
		}

		public function filter_term_url( $url, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$meta_val = '';

			if ( $mod[ 'id' ] ) {

				if ( class_exists( 'WPSEO_Taxonomy_Meta' ) && method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ) {

					$meta_val = WPSEO_Taxonomy_Meta::get_term_meta( $mod[ 'id' ], $mod[ 'tax_slug' ], 'canonical' );

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'WPSEO_Taxonomy_Meta::get_term_meta not found' );
				}
			}

			return $meta_val ? $meta_val : $url;
		}

		private function get_post_meta_value( $post_id, $meta_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$meta_val = '';
			$post_obj = SucomUtil::get_post_object( $post_id );

			if ( empty( $post_obj->ID ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post object id is empty' );
				}

				return $meta_val;

			} elseif ( empty( $post_obj->post_type ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post object post_type is empty' );
				}

				return $meta_val;
			}

			if ( class_exists( 'WPSEO_Meta' ) && method_exists( 'WPSEO_Meta', 'get_value' ) ) {

				if ( $meta_val = WPSEO_Meta::get_value( $meta_key, $post_obj->ID ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'WPSEO_Meta::get_value ' . $meta_key . ' = ' . $meta_val );
					}
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'WPSEO_Meta::get_value not found' );
			}

			if ( empty( $meta_val ) ) {	// Fallback to the value from the Yoast SEO settings.

				$opts_key = $meta_key . '-' . $post_obj->post_type;

				if ( empty( $this->wpseo_opts[ $opts_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wpseo options ' . $opts_key . ' is empty' );
					}

				} else {

					$meta_val = $this->wpseo_opts[ $opts_key ];

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wpseo options ' . $opts_key . ' = ' . $meta_val );
					}
				}
			}

			return $meta_val;
		}

		private function get_term_meta_value( $term_id, $meta_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$meta_val = '';
			$term_obj = SucomUtil::get_term_object( $term_id );

			if ( empty( $term_obj->term_id ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: term object id is empty' );
				}

				return $meta_val;

			} elseif ( empty( $term_obj->taxonomy ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: term object taxonomy is empty' );
				}

				return $meta_val;
			}

			if ( class_exists( 'WPSEO_Taxonomy_Meta' ) && method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ) {

				if ( $meta_val = WPSEO_Taxonomy_Meta::get_term_meta( $term_obj, $term_obj->taxonomy, $meta_key ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'WPSEO_Taxonomy_Meta::get_term_meta ' . $meta_key . ' = ' . $meta_val );
					}
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'WPSEO_Taxonomy_Meta::get_term_meta not found' );
			}

			if ( empty( $meta_val ) ) {	// Fallback to the value from the Yoast SEO settings.

				$opts_key = $meta_key . '-tax-' . $term_obj->taxonomy;

				if ( empty( $this->wpseo_opts[ $opts_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wpseo options ' . $opts_key . ' is empty' );
					}

				} else {

					$meta_val = $this->wpseo_opts[ $opts_key ];

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wpseo options ' . $opts_key . ' = ' . $meta_val );
					}
				}
			}

			return $meta_val;
		}

		private function get_user_meta_value( $user_id, $meta_key ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$meta_val = '';
			$user_obj = SucomUtil::get_user_object( $user_id );

			if ( empty( $user_obj->ID ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: user object id is empty' );
				}

				return $meta_val;
			}

			if ( $meta_val = get_the_author_meta( 'wpseo_' . $meta_key, $user_obj->ID ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'get_the_author_meta wpseo_' . $meta_key . ' = ' . $meta_val );
				}
			}

			if ( empty( $meta_val ) ) {	// Fallback to the value from the Yoast SEO settings.

				$opts_key = $meta_key . '-author-wpseo';

				if ( empty( $this->wpseo_opts[ $opts_key ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wpseo options ' . $opts_key . ' is empty' );
					}

				} else {

					$meta_val = $this->wpseo_opts[ $opts_key ];

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'wpseo options ' . $opts_key . ' = ' . $meta_val );
					}
				}
			}

			return $meta_val;
		}
	}
}
