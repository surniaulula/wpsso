<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonPropHasPart' ) ) {

	class WpssoJsonPropHasPart {

		private $p;	// Wpsso class object.

		private static $meta_name  = '_wpsso_json_haspart';
		private static $meta_saved = false;

		/*
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_thing'        => 5,
				'json_data_https_schema_org_creativework' => 5,
			), $prio = 10000 );

			/*
			 * Comment json scripts saved in the self::$meta_name metadata array.
			 *
			 * See wordpress/wp-includes/default-filters.php:
			 *
			 * add_filter( 'the_content', 'do_shortcode', 11 );	// After wpautop().
			 */
			add_filter( 'the_content', array( $this, 'maybe_comment_json_scripts' ), 12 );	// After do_shortcode().

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'added maybe_comment_json_scripts filter hook for the_content' );
			}
		}

		/*
		 * Cleanup self::$meta_name here in case the Schema type has changed from CreativeWork to something else.
		 */
		public function filter_json_data_https_schema_org_thing( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( self::$meta_saved ) {

				if ( $is_main ) {

					if ( $mod[ 'is_post' ] ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'deleting ' . self::$meta_name . ' metadata for post id ' . $mod[ 'id' ] );
						}

						delete_metadata( 'post', $mod[ 'id' ], self::$meta_name );
					}
				}
			}

			return $json_data;
		}

		public function filter_json_data_https_schema_org_creativework( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$prop_data = array( 'hasPart' => array() );

			return $this->filter_json_data_post_content_json_ld_scripts( $json_data, $prop_data, $mod, $mt_og, $page_type_id, $is_main );
		}

		private function filter_json_data_post_content_json_ld_scripts( $json_data, $prop_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $is_main ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: json data is not the main entity' );
				}

				return $json_data;

			} elseif ( ! $mod[ 'is_post' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no content to check for module type ' . $mod[ 'name' ] );
				}

				return $json_data;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting the content for post id ' . $mod[ 'id' ] );
			}

			$content = $this->p->page->get_the_content( $mod, $flatten = false );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting json scripts from the content' );
			}

			$scripts_data = SucomUtil::get_json_scripts( $content, $do_decode = true );	// Return the decoded json data.

			if ( empty( $scripts_data ) ) {	// Nothing to do.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no json scripts found in the content' );
				}

				return $json_data;
			}

			$json_ret = array();

			/*
			 * Save existing properties in $json_data so we can filter them and add new ones.
			 */
			foreach ( $prop_data as $prop_name => $prop_values ) {

				if ( isset( $json_data[ $prop_name ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'saving ' . $prop_name . ' property value(s)' );
					}

					if ( isset( $json_data[ $prop_name ][ 0 ] ) ) {	// Has an array of types.

						$prop_data[ $prop_name ] = $json_data[ $prop_name ];

					} elseif ( ! empty( $json_data[ $prop_name ] ) ) {

						$prop_data[ $prop_name ][] = $json_data[ $prop_name ];	// Markup for a single type.
					}

					unset( $json_data[ $prop_name ] );
				}
			}

			$added_script_ids = array();

			foreach ( $scripts_data as $single_id => $single_data ) {

				if ( is_array( $single_data ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding json scripts data for ' . $single_id );
					}

					$this->maybe_add_single_data( $added_script_ids, $prop_data, $single_id, $single_data, $page_type_id );

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipped ' . $single_id . ': single data is not an array' );

						$this->p->debug->log( $single_data );
					}
				}
			}

			if ( ! empty( $added_script_ids ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'added_script_ids', $added_script_ids );
				}

				self::$meta_saved = true;

				update_metadata( 'post', $mod[ 'id' ], self::$meta_name, $added_script_ids );
			}

			foreach ( $prop_data as $prop_name => $prop_values ) {

				$filter_name = 'wpsso_json_prop_https_schema_org_' . strtolower( $prop_name );

				$prop_values = apply_filters( $filter_name, $prop_values, $mod, $mt_og, $page_type_id, $is_main );

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

		/*
		 * Recurse for each @graph element, including nested @graph elements (ie. @graph within another @graph).
		 */
		private function maybe_add_single_data( array &$added_script_ids, array &$prop_data, $single_id, array $single_data, $page_type_id, $def_context = null ) {

			if ( null === $def_context ) {

				$def_context = empty( $single_data[ '@context' ] ) ? 'https://schema.org' : $single_data[ '@context' ];
			}

			if ( isset( $single_data[ 0 ] ) ) {

				foreach ( $single_data as $array_key => $array_data ) {

					$this->maybe_add_single_data( $added_script_ids, $prop_data, $single_id, $array_data, $page_type_id, $def_context );
				}

			} elseif ( isset( $single_data[ '@graph' ] ) ) {

				foreach ( $single_data[ '@graph' ] as $graph_key => $graph_data ) {

					if ( '@context' === $graph_key ) {	// Nested @graph.

						$def_context = $graph_data;

						continue;

					} elseif ( '@graph' === $graph_key ) {

						$this->maybe_add_single_data( $added_script_ids, $prop_data, $single_id, $graph_data, $page_type_id, $def_context );

					} elseif ( is_numeric( $graph_key ) ) {

						$this->maybe_add_single_data( $added_script_ids, $prop_data, $single_id, $graph_data, $page_type_id, $def_context );
					}
				}

			} else {

				if ( empty( $single_data[ '@context' ] ) ) {	// Just in case.

					$single_data[ '@context' ] = $def_context;
				}

				$type_url = WpssoSchema::get_data_type_url( $single_data );

				$type_ids = $this->p->schema->get_schema_type_url_ids( $type_url );

				foreach ( $type_ids as $child_id ) {

					if ( isset( $prop_data[ 'hasPart' ] ) ) {

						/*
						 * The hasPart property value must be a Schema CreativeWork type or sub-type.
						 */
						if ( $this->p->schema->is_schema_type_child( $child_id, 'creative.work' ) ) {

							$prop_data[ 'hasPart' ][] = WpssoSchema::get_schema_type_context( $type_url, $single_data );

							$added_script_ids[ $single_id ] = 'hasPart';

							break;	// Child id is valid - no need to check the other child ids.
						}
					}
				}
			}
		}

		/*
		 * Comment json scripts saved in the self::$meta_name metadata array.
		 */
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

					$this->p->debug->log( 'exiting early: no global post object' );
				}

				$failure = '<!-- no global post object to comment json scripts -->' . "\n";

				return $failure . $content;
			}

			$added_script_ids = get_metadata( 'post', $post_id, self::$meta_name, $single = true );

			if ( empty( $added_script_ids ) || ! is_array( $added_script_ids ) ) {	// Nothing to do.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no json scripts to comment in the content' );
				}

				$failure = '<!-- no json scripts to comment in the content -->' . "\n";

				return $failure . $content;

			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'added_script_ids', $added_script_ids );
			}

			$json_scripts = SucomUtil::get_json_scripts( $content, $do_decode = false );

			if ( empty( $json_scripts ) ) {	// Nothing to do.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no json scripts found in the content' );
				}

				$failure = '<!-- no json scripts found in the content -->' . "\n";

				return $failure . $content;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'json_scripts', $json_scripts );
			}

			foreach ( $json_scripts as $single_id => $single_json ) {

				if ( empty( $added_script_ids[ $single_id ] ) ) {

					$failure = '<!-- json script ' . $single_id . ' found but not added -->' . "\n";

					$content = $failure . $content;

					continue;
				}

				$prop_name = $added_script_ids[ $single_id ];

				$replace = '<!-- json script ' . $single_id . ' added to ' . $prop_name . ' and commented -->';

				$count  = null;

				if ( 0 === strpos( $single_id, 'id:' ) ) {

					$css_id = preg_quote( substr( $single_id, strlen( 'id:' ) ), '/' );

					$content = preg_replace( '/<script\b[^>]*id=["\']' . $css_id . '["\'][^>]*>.+<\/script>/Uis', $replace, $content, $limit = -1, $count );

				} elseif ( 0 === strpos( $single_id, 'md5:' ) ) {

					$content = str_replace( $single_json, $replace, $content, $count );
				}

				if ( $count ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'json script ' . $single_id . ' successfully commented' );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'json script ' . $single_id . ' not found in the content' );
					}

					$failure = '<!-- json script ' . $single_id . ' added to ' . $prop_name . ' but not found in the content -->' . "\n";

					$content = $failure . $content;
				}
			}

			return $content;
		}
	}
}
