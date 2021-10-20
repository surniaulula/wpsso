<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersPropHasPart' ) ) {

	class WpssoJsonFiltersPropHasPart {

		private $p;	// Wpsso class object.

		private static $meta_key = '_wpsso_json_haspart';

		/**
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * The Schema 'hasPart' property is only valid for the CreativeWork type.
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'content_html_script_application_ld_json'         => 2,
				'json_data_https_schema_org_creativework_haspart' => array(
					'json_data_https_schema_org_creativework' => 5,
				),
			), $prio = 10000 );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'added maybe_comment_json_scripts filter hook for the_content' );
			}

			add_filter( 'the_content', array( $this, 'maybe_comment_json_scripts' ), PHP_INT_MAX );
		}

		public function filter_content_html_script_application_ld_json( $html, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_data = $this->p->schema->get_mod_json_data( $mod );	// Can return false.

			if ( ! empty( $json_data ) ) {

				$html .= '<script type="application/ld+json">' . $this->p->util->json_format( $json_data ) . '</script>' . "\n";
			}

			return $html;
		}

		/**
		 * Check the post content and add any schema json scripts found as 'hasPart' in the $json_data.
		 */
		public function filter_json_data_https_schema_org_creativework_haspart( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $is_main ) {

				return $json_data;
			}

			$json_ret = array();

			$data_props = array(
				'hasPart'  => array(),
			);

			/**
			 * Move any existing properties in $json_data (from shortcodes, for example) so we can filter them and add
			 * new ones.
			 */
			foreach ( $data_props as $prop_name => $prop_values ) {

				if ( isset( $json_data[ $prop_name ] ) ) {

					if ( isset( $json_data[ $prop_name ][0] ) ) {	// Has an array of types.

						$data_props[ $prop_name ] = $json_data[ $prop_name ];

					} elseif ( ! empty( $json_data[ $prop_name ] ) ) {

						$data_props[ $prop_name ][] = $json_data[ $prop_name ];	// Markup for a single type.
					}

					unset( $json_data[ $prop_name ] );
				}
			}

			if ( $mod[ 'is_post' ] ) {

				$content = $this->p->page->get_the_content( $mod, $read_cache = true, $md_key = '', $flatten = false );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting json scripts from the content' );
				}

				$scripts_data = SucomUtil::get_json_scripts( $content, $do_decode = true );	// Return the decoded json data.

				if ( empty( $scripts_data ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no json scripts found in the content' );
					}

				} else {

					$md5_added = array();	// Initialize an empty md5 array.

					foreach ( $scripts_data as $single_md5 => $single_data ) {

						if ( is_array( $single_data ) ) {	// Just in case.

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log_arr( 'adding single data for $single_md5 ' . $single_md5, $single_data );
							}

							$this->maybe_add_single_data( $md5_added, $data_props, $single_md5, $single_data );

						} else {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipped ' . $single_md5 . ': single data is not an array' );
								$this->p->debug->log( $single_data );
							}
						}
					}

					if ( empty( $md5_added ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'deleting ' . self::$meta_key . ' post meta' );
						}

						delete_post_meta( $mod[ 'id' ], self::$meta_key );

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log_arr( 'saving $md5_added to ' . self::$meta_key . ' post meta', $md5_added );
						}

						update_post_meta( $mod[ 'id' ], self::$meta_key, $md5_added );
					}
				}
			}

			foreach ( $data_props as $prop_name => $prop_values ) {

				$filter_name = 'wpsso_json_prop_https_schema_org_' . strtolower( $prop_name );

				$prop_values = (array) apply_filters( $filter_name, $prop_values, $mod, $mt_og, $page_type_id, $is_main );

				if ( isset( $prop_values[ 0 ] ) ) {

					foreach ( $prop_values as $array_key => &$array_data ) {

						if ( ! empty( $array_data[ 'mainEntityOfPage' ] ) && ! empty( $json_data[ 'url' ] ) &&
							$array_data[ 'mainEntityOfPage' ] === $json_data[ 'url' ] ) {

							unset( $array_data[ 'mainEntityOfPage' ] );
						}
					}
				}

				if ( ! empty( $prop_values ) ) {

					$json_ret[ $prop_name ] = $prop_values;
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}

		/**
		 * This method will recurse for each @graph element, including nested @graph elements (ie. @graph within another
		 * @graph).
		 */
		private function maybe_add_single_data( array &$md5_added, array &$data_props, $single_md5, array $single_data, $default_context = null ) {

			if ( null === $default_context ) {

				$default_context = empty( $single_data[ '@context' ] ) ? 'https://schema.org' : $single_data[ '@context' ];
			}

			if ( isset( $single_data[ 0 ] ) ) {

				foreach ( $single_data as $array_key => $array_data ) {

					$this->maybe_add_single_data( $md5_added, $data_props, $single_md5, $array_data, $default_context );
				}

			} elseif ( isset( $single_data[ '@graph' ] ) ) {

				foreach ( $single_data[ '@graph' ] as $graph_key => $graph_data ) {

					if ( '@context' === $graph_key ) {	// Nested @graph.

						$default_context = $graph_data;

						continue;

					} elseif ( '@graph' === $graph_key ) {

						$this->maybe_add_single_data( $md5_added, $data_props, $single_md5, $graph_data, $default_context );

					} elseif ( is_numeric( $graph_key ) ) {

						$this->maybe_add_single_data( $md5_added, $data_props, $single_md5, $graph_data, $default_context );
					}
				}

			} else {

				if ( empty( $single_data[ '@context' ] ) ) {	// Just in case.

					$single_data[ '@context' ] = $default_context;
				}

				$type_url = WpssoSchema::get_data_type_url( $single_data );

				$type_ids = $this->p->schema->get_schema_type_url_ids( $type_url );

				foreach ( $type_ids as $child_id ) {

					if ( $this->p->schema->is_schema_type_child( $child_id, 'creative.work' ) ) {

						$data_props[ 'hasPart' ][] = WpssoSchema::get_schema_type_context( $type_url, $single_data );

						$md5_added[ $single_md5 ] = true;

						break;
					}
				}
			}
		}

		public function maybe_comment_json_scripts( $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $GLOBALS[ 'wpsso_doing_filter_the_content' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: wpsso_doing_filter_the_content is true' );
				}

				return $content;
			}

			if ( ! empty( $GLOBALS[ 'post' ]->ID ) ) {

				$post_id = $GLOBALS[ 'post' ]->ID;

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no global post object id' );
				}

				return $content;
			}

			$md5_added = get_post_meta( $post_id, self::$meta_key, $single = true );

			if ( empty( $md5_added ) || ! is_array( $md5_added ) ) {	// Nothing to do.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no json scripts added' );
				}

				return $content;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$md5_added', $md5_added );
			}

			/**
			 * Removes HTML comments from the content, and returns any "application/ld+json" encoded arrays:
			 *
			 *	<script type="application/ld+json">{}</script>
			 */
			$json_scripts = SucomUtil::get_json_scripts( $content, $do_decode = false );

			if ( empty( $json_scripts ) ) {	// Nothing to do.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no json scripts found in the content' );
				}

				return $content;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$json_scripts', $json_scripts );
			}

			foreach ( $json_scripts as $single_md5 => $single_json ) {

				if ( empty( $md5_added[ $single_md5 ] ) ) {

					continue;
				}

				if ( $this->p->debug->enabled ) {

					/**
					 * Firefox does not allow double-dashes inside comment blocks.
					 */
					$single_json_encoded = str_replace( '--', '&hyphen;&hyphen;', $single_json );

					$single_json_encoded = '<!-- ' . $single_json_encoded . ' -->' . "\n";

				} else {
					$single_json_encoded = '';
				}

				$success = "\n" . '<!-- json script ' . $single_md5 . ' added to Schema hasPart and commented -->' . "\n";
				$failure = "\n" . '<!-- json script ' . $single_md5 . ' added to Schema hasPart but not found in content -->' . "\n";

				$content = str_replace( $single_json, $success . $single_json_encoded, $content, $count );

				if ( $count ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'json script ' . $single_md5 . ' successfully commented' );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'json script ' . $single_md5 . ' not found in content' );
					}

					$content = $failure . $single_json_encoded . $content;
				}
			}

			return $content;
		}
	}
}
