<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2017-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegJobSimpleJobBoard' ) ) {

	class WpssoIntegJobSimpleJobBoard {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'get_md_defaults'  => 2,
				'get_post_options' => 3,
				'get_job_options'  => 3,
			) );
		}

		public function filter_get_md_defaults( array $md_defs, array $mod ) {

			if ( ! $mod[ 'is_post' ] || $mod[ 'post_type' ] !== 'jobpost' ) {

				return $md_defs;
			}

			self::add_schema_job_defaults( $md_defs, $mod[ 'id' ] );

			return $md_defs;
		}

		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $mod[ 'is_post' ] || $mod[ 'post_type' ] !== 'jobpost' ) {

				return $md_opts;
			}

			$job_opts = array();

			self::add_schema_job_defaults( $job_opts, $post_id );	// Add defaults to empty array.

			foreach ( $job_opts as $key => $val ) {	// Hard-code defaults and disable the option.

				$md_opts[ $key ]               = $val;
				$md_opts[ $key . ':disabled' ] = true;
			}

			return $md_opts;
		}

		public function filter_get_job_options( $opts, $mod, $job_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $mod[ 'is_post' ] || $mod[ 'post_type' ] !== 'jobpost' ) {

				return $opts;
			}

			$job_opts = self::get_schema_job_options( $mod[ 'id' ] );

			$job_opts = SucomUtil::preg_grep_keys( '/^schema_(job_.*)$/', $job_opts, $invert = false, $replace = '$1' );

			return $job_opts;
		}

		private static function get_schema_job_options( $post_id ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$job_opts = array();

			self::add_schema_job_defaults( $job_opts, $post_id );

			return $job_opts;
		}

		private static function add_schema_job_defaults( array &$job_opts, $post_id ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			static $local_cache = array();

			if ( ! isset( $local_cache[ $post_id ] ) ) {	// Only create the defaults array once.

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'creating a new defaults static array' );
				}

				$local_cache[ $post_id ] = array();

				/*
				 * Get the default schema type (job.posting by default).
				 */
				$local_cache[ $post_id ][ 'schema_type' ] = $wpsso->options[ 'schema_type_for_jobpost' ];

				$job_type_terms = wp_get_object_terms( $post_id, 'jobpost_job_type' );

				if ( ! empty( $job_type_terms ) ) {

					foreach ( $job_type_terms as $term_obj ) {

						if ( ! empty( $term_obj->name ) ) {	// Just in case.

				 			/*
							 * Google approved values (case sensitive):
							 *
							 * 	FULL_TIME
							 *	PART_TIME
							 *	CONTRACTOR
							 *	TEMPORARY
							 *	INTERN
							 *	VOLUNTEER
							 *	PER_DIEM
							 *	OTHER
							 */
							$empl_type = SucomUtil::sanitize_hookname( $term_obj->name );	// Sanitize with underscores.

							$empl_type = strtoupper( $empl_type );

							$local_cache[ $post_id ][ 'schema_job_empl_type_' . $empl_type ] = 1;	// Checkbox value is 0 or 1.
						}
					}
				}

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'defaults static array', $local_cache[ $post_id ] );
				}

			} elseif ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'using the cached defaults static array' );
			}

			if ( ! empty( $local_cache[ $post_id ] ) ) {

				$job_opts = array_merge( $job_opts, $local_cache[ $post_id ] );
			}
		}
	}
}
