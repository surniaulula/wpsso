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

if ( ! class_exists( 'WpssoIntegRecipeWpRecipeMaker' ) ) {

	class WpssoIntegRecipeWpRecipeMaker {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'cf_md_index'       => 1,
				'get_post_options'  => 3,
				'save_post_options' => 3,
			) );

			add_filter( 'wprm_recipe_metadata', '__return_empty_array', PHP_INT_MAX );
		}

		/*
		 * Clear the 'plugin_cf_recipe_ingredients' and 'plugin_cf_recipe_instructions' values.
		 */
		public function filter_cf_md_index( $cf_md_index ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			foreach ( array(
				'plugin_cf_recipe_ingredients',
				'plugin_cf_recipe_instructions',
			) as $cf_key ) {

				if ( isset( $cf_md_index[ $cf_key ] ) ) {	// Just in case.

					$cf_md_index[ $cf_key ] = '';		// Disable the $md_key value.
				}
			}

			return $cf_md_index;
		}

		public function filter_get_post_options( array $md_opts, $post_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $recipe_id = $this->get_recipe_id( $post_id ) ) {

				return $md_opts;
			}

			/*
			 * Remove old ingredient and instruction lists.
			 */
			$md_opts = SucomUtil::preg_grep_keys( '/^schema_recipe_(ingredient|instruction)_[0-9]+$/', $md_opts, $invert = true );

			$recipe_opts = self::get_recipe_options( $post_id, $recipe_id );

			foreach ( $recipe_opts as $key => $val ) {

				$md_opts[ $key ]               = $val;
				$md_opts[ $key . ':disabled' ] = true;
			}

			return $md_opts;
		}

		public function filter_save_post_options( array $md_opts, $post_id, array $mod ) {

			if ( ! $recipe_id = $this->get_recipe_id( $post_id ) ) {

				return $md_opts;
			}

			$recipe_opts = self::get_recipe_options( $post_id, $recipe_id );

			foreach ( $recipe_opts as $key => $val ) {

				unset( $md_opts[ $key ] );
			}

			return $md_opts;
		}

		/*
		 * Returns option names suitable for custom post meta (includes a "schema_" prefix).
		 */
		public static function get_recipe_options( $post_id, $recipe_id ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

			if ( ! is_object( $recipe ) ) {	// Just in case.

				return array();
			}

			$data = $recipe->get_data();

			$opts = array(
				'schema_type'      => $wpsso->options[ 'schema_type_for_recipe' ],
				'schema_recipe_id' => $recipe_id,
			);

			/*
			 * Cuisine
			 */
			$cuisines = $recipe->tags( 'cuisine' );

			if ( count( $cuisines ) > 0 ) {

				$opts[ 'schema_recipe_cuisine' ] = implode( $glue = ', ', wp_list_pluck( $cuisines, 'name' ) );
			}

			/*
			 * Course
			 */
			$courses = $recipe->tags( 'course' );

			if ( count( $courses ) > 0 ) {

				$opts[ 'schema_recipe_course' ] = implode( $glue = ', ', wp_list_pluck( $courses, 'name' ) );
			}

			/*
			 * Yield
			 */
			if ( $recipe->servings() ) {

				$opts[ 'schema_recipe_yield' ] = trim( $recipe->servings() . ' ' . $recipe->servings_unit() );
			}

			/*
			 * Times
			 */
			foreach( array(
				'schema_recipe_prep',
				'schema_recipe_cook',
				'schema_recipe_total',
			) as $opt_pre ) {

				foreach ( array( 'days', 'hours', 'mins', 'secs' ) as $unit ) {	// Set the baseline to 0.

					$opts[ $opt_pre . '_' . $unit ] = 0;
				}
			}

			$opts[ 'schema_recipe_prep_mins' ]  = $recipe->prep_time();
			$opts[ 'schema_recipe_total_mins' ] = $recipe->total_time();
			$opts[ 'schema_recipe_cook_mins' ]  = $recipe->cook_time();

			/*
			 * Ingredients
			 */
			if ( isset( $data[ 'ingredients' ] ) && is_array( $data[ 'ingredients' ] ) ) {

				$ingredients = array();	// Start with a fresh array.

				foreach( $data[ 'ingredients' ] as $group ) {

					if ( isset( $group[ 'name' ] ) ) {

						$group_name = trim( $group[ 'name' ] );
					}

					if ( isset( $group[ 'ingredients' ] ) && is_array( $group[ 'ingredients' ] ) ) {

						foreach( $group[ 'ingredients' ] as $arr ) {

							$md_val = '';

							if ( $group_name ) {

								$md_val .= '[' . $group_name . '] ';
							}

							$md_val .= $arr[ 'amount' ] . ' ' . $arr[ 'unit' ] . ' ' . $arr[ 'name' ];

							if ( ( $notes = trim( $arr[ 'notes' ] ) ) !== '' ) {

								$md_val .= ' (' . $notes . ')';
							}

							$ingredients[] = trim( $md_val );
						}
					}
				}

				foreach ( $ingredients as $num => $md_val ) {	// Start at 0.

					$opts[ 'schema_recipe_ingredient_' . $num ] = $md_val;
				}

				unset ( $ingredients );
			}

			/*
			 * Instructions
			 */
			if ( isset( $data[ 'instructions' ] ) && is_array( $data[ 'instructions' ] ) ) {

				$instructions = array();	// Start with a fresh array.

				foreach( $data[ 'instructions' ] as $group ) {

					if ( ! empty( $group[ 'name' ] ) ) {

						$instructions[] = array(
							'section'    => 1,
							'name'       => $group[ 'name' ],
							'text'       => null,
							'img_id'     => null,
						);
					}

					if ( isset( $group[ 'instructions' ] ) && is_array( $group[ 'instructions' ] ) ) {

						foreach( $group[ 'instructions' ] as $arr ) {

							$instructions[] = array(
								'section'    => 0,
								'name'       => empty( $arr[ 'name' ] ) ? null : $arr[ 'name' ],
								'text'       => empty( $arr[ 'text' ] ) ? null : trim( SucomUtil::strip_html( $arr[ 'text' ] ) ),
								'img_id'     => empty( $arr[ 'image' ] ) ? null : $arr[ 'image' ],
							);
						}
					}
				}

				foreach ( $instructions as $num => $arr ) {	// Start at 0.

					$opts[ 'schema_recipe_instruction_section_' . $num ]    = $arr[ 'section' ];
					$opts[ 'schema_recipe_instruction_' . $num ]            = $arr[ 'name' ];
					$opts[ 'schema_recipe_instruction_text_' . $num ]       = $arr[ 'text' ];
					$opts[ 'schema_recipe_instruction_img_id_' . $num ]     = $arr[ 'img_id' ];
				}

				unset ( $instructions );
			}

			/*
			 * Nutrition Information
			 */
			if ( isset( $data[ 'nutrition' ] ) && is_array( $data[ 'nutrition' ] ) ) {

				foreach ( array(
					'schema_recipe_nutri_cal'       => 'calories',
					'schema_recipe_nutri_prot'      => 'protein',
					'schema_recipe_nutri_fib'       => 'fiber',
					'schema_recipe_nutri_carb'      => 'carbohydrates',
					'schema_recipe_nutri_sugar'     => 'sugar',
					'schema_recipe_nutri_sod'       => 'sodium',
					'schema_recipe_nutri_fat'       => 'fat',
					'schema_recipe_nutri_sat_fat'   => 'saturated_fat',
					'schema_recipe_nutri_trans_fat' => 'trans_fat',
					'schema_recipe_nutri_chol'      => 'cholesterol',
				) as $opt_pre => $nutri_key ) {

					if ( isset( $data[ 'nutrition' ][ $nutri_key ] ) ) {

						$opts[ $opt_pre ] = trim( $data[ 'nutrition' ][ $nutri_key ] );
					}
				}

				$nutri_unsat_fat = 0;

				$opts[ 'schema_recipe_nutri_unsat_fat' ] = '';

				foreach ( array(
					'monounsaturated_fat',
					'polyunsaturated_fat',
				) as $nutri_fat_key ) {

					if ( isset( $data[ 'nutrition' ][ $nutri_fat_key ] ) && trim( $data[ 'nutrition' ][ $nutri_fat_key ] ) !== '' ) {

						$nutri_unsat_fat += $data[ 'nutrition' ][ $nutri_fat_key ];

						$opts[ 'schema_recipe_nutri_unsat_fat' ] = $nutri_unsat_fat;
					}
				}

				$serv_size = '';

				foreach ( array( 'serving_size', 'serving_unit' ) as $serv_key ) {

					if ( isset( $data[ 'nutrition' ][ $serv_key ] ) && trim( $data[ 'nutrition' ][ $serv_key ] ) !== '' ) {

						$serv_size .= trim( ' ' . $data[ 'nutrition' ][ $serv_key ] );

						$opts[ 'schema_recipe_nutri_serv' ] = $serv_size;
					}
				}
			}

			return $opts;
		}

		public function get_recipe_id( $post_id ) {

			static $ids_cache = array();	// Cache for $post_id => $recipe_id.

			if ( isset( $ids_cache[ $post_id ] ) ) {

				return $ids_cache[ $post_id ];

			}

			$post_type = get_post_type( $post_id );

			if ( 'attachment' === $post_type || WPRM_POST_TYPE === $post_type ) { // Skip attachments and recipe objects.

				$recipe_id = false;

			} else {

				$post_obj      = get_post( $post_id );
				$shortcode_ids = (array) WPRM_Recipe_Manager::get_recipe_ids_from_content( $post_obj->post_content );
				$recipe_id     = reset( $shortcode_ids );

				if ( empty( $recipe_id ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'post_id ' . $post_id . ' recipe not found in content' );
					}

					$recipe_id = false;
				}
			}

			return $ids_cache[ $post_id ] = $recipe_id;
		}
	}
}
