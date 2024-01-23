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
 * Integration module for the Yoast SEO plugin.
 *
 * See https://wordpress.org/plugins/wordpress-seo/.
 */
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

			if ( is_admin() ) {

				$this->p->util->add_plugin_filters( $this, array(
					'features_status_integ_data_wpseo_blocks' => 1,
					'features_status_integ_data_wpseo_meta'   => 1,
					'admin_page_style_css'                    => 1,
				), 100 );

				add_action( 'admin_init', array( $this, 'cleanup_wpseo_notifications' ), 15 );

			} else {

				add_filter( 'wpseo_frontend_presenters', array( $this, 'cleanup_wpseo_frontend_presenters' ), 1000, 1 );
				add_filter( 'wpseo_schema_graph', array( $this, 'cleanup_wpseo_schema_graph' ), 1000, 2 );
			}
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
			$post_obj = SucomUtilWP::get_post_object( $post_id );

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
			$term_obj = SucomUtilWP::get_term_object( $term_id );

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
			$user_obj = SucomUtilWP::get_user_object( $user_id );

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

		/*
		 * Fix Yoast SEO CSS on back-end pages.
		 */
		public function filter_admin_page_style_css( $custom_style_css ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Fix the width of Yoast SEO list table columns.
			 */
			$custom_style_css .= '
				table.wp-list-table > thead > tr > th.column-wpseo-links,
				table.wp-list-table > tbody > tr > td.column-wpseo-links,
				table.wp-list-table > thead > tr > th.column-wpseo-linked,
				table.wp-list-table > tbody > tr > td.column-wpseo-linked,
				table.wp-list-table > thead > tr > th.column-wpseo-score,
				table.wp-list-table > tbody > tr > td.column-wpseo-score,
				table.wp-list-table > thead > tr > th.column-wpseo-score-readability,
				table.wp-list-table > tbody > tr > td.column-wpseo-score-readability {
					width:40px;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-title,
				table.wp-list-table > tbody > tr > td.column-wpseo-title,
				table.wp-list-table > thead > tr > th.column-wpseo-metadesc,
				table.wp-list-table > tbody > tr > td.column-wpseo-metadesc {
					width:20%;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-focuskw,
				table.wp-list-table > tbody > tr > td.column-wpseo-focuskw {
					width:8em;	/* Leave room for the sort arrow. */
				}
			';

			/*
			 * The "Schema" metabox tab and its options cannot be disabled, so hide them instead.
			 */
			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				$custom_style_css .= '
					#wpseo-meta-tab-schema { display: none; }
					#wpseo-meta-section-schema { display: none; }
				';
			}

			return $custom_style_css;
		}

		public function filter_features_status_integ_data_wpseo_blocks( $features_status ) {

			return 'off' === $features_status ? 'rec' : $features_status;
		}

		public function filter_features_status_integ_data_wpseo_meta( $features_status ) {

			return 'off' === $features_status ? 'rec' : $features_status;
		}

		/*
		 * Cleanup incorrect Yoast SEO notifications.
		 */
		public function cleanup_wpseo_notifications() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Yoast SEO only checks for a conflict with WPSSO if the Open Graph option is enabled.
			 */
			if ( method_exists( 'WPSEO_Options', 'get' ) ) {

				if ( ! WPSEO_Options::get( 'opengraph' ) ) {

					return;
				}
			}

			if ( class_exists( 'Yoast_Notification_Center' ) ) {

				$info = $this->p->cf[ 'plugin' ][ $this->p->id ];
				$name = $this->p->cf[ 'plugin' ][ $this->p->id ][ 'name' ];

				if ( method_exists( 'Yoast_Notification_Center', 'get_notification_by_id' ) ) {

					$notif_id     = 'wpseo-conflict-' . md5( $info[ 'base' ] );
					$notif_msg    = '<style type="text/css">#' . $notif_id . '{display:none;}</style>';	// Hide our empty notification.
					$notif_center = Yoast_Notification_Center::get();
					$notif_obj    = $notif_center->get_notification_by_id( $notif_id );

					if ( empty( $notif_obj ) ) {

						return;
					}

					/*
					 * Note that Yoast_Notification::render() wraps the notification message with
					 * '<div class="yoast-alert"></div>'.
					 */
					if ( method_exists( 'Yoast_Notification', 'render' ) ) {

						$notif_html = $notif_obj->render();

					} else $notif_html = $notif_obj->message;

					if ( false === strpos( $notif_html, $notif_msg ) ) {

						update_metadata( 'user', get_current_user_id(), $notif_obj->get_dismissal_key(), 'seen' );

						$notif_obj = new Yoast_Notification( $notif_msg, array( 'id' => $notif_id ) );

						$notif_center->add_notification( $notif_obj );
					}

				} elseif ( defined( 'Yoast_Notification_Center::TRANSIENT_KEY' ) ) {

					if ( false !== ( $wpseo_notif = get_transient( Yoast_Notification_Center::TRANSIENT_KEY ) ) ) {

						$wpseo_notif = json_decode( $wpseo_notif, $assoc = false );

						if ( ! empty( $wpseo_notif ) ) {

							foreach ( $wpseo_notif as $num => $notif_msgs ) {

								if ( isset( $notif_msgs->options->type ) && $notif_msgs->options->type == 'error' ) {

									if ( false !== strpos( $notif_msgs->message, $name ) ) {

										unset( $wpseo_notif[ $num ] );

										set_transient( Yoast_Notification_Center::TRANSIENT_KEY, wp_json_encode( $wpseo_notif ) );
									}
								}
							}
                                        	}
					}
				}
			}
		}

		/*
		 * Since Yoast SEO v14.0.
		 *
		 * Disable Yoast SEO social meta tags.
		 *
		 * Yoast SEO provides two arguments to this filter, but older versions only provided one.
		 */
		public function cleanup_wpseo_frontend_presenters( $presenters ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$remove = array( 'Open_Graph', 'Slack', 'Twitter', 'WooCommerce' );

			$remove_preg = '/(' . implode( '|', $remove ) . ')/';

			foreach ( $presenters as $num => $obj ) {

				$class_name = get_class( $obj );

				if ( preg_match( $remove_preg, $class_name ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'removing presenter: ' . $class_name );
					}

					unset( $presenters[ $num ] );

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping presenter: ' . $class_name );
					}
				}
			}

			return $presenters;
		}

		public function cleanup_wpseo_schema_graph( $graph, $context ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				/*
				 * Remove everything except for the BreadcrumbList markup.
				 *
				 * The WPSSO BC add-on removes the BreadcrumbList markup.
				 */
				foreach ( $graph as $num => $piece ) {

					if ( ! empty( $piece[ '@type' ] ) ) {

						if ( 'BreadcrumbList' === $piece[ '@type' ] ) {	// Keep breadcrumbs.

							continue;
						}

					}

					unset( $graph[ $num ] );	// Remove everything else.
				}
			}

			return array_values( $graph );
		}
	}
}
