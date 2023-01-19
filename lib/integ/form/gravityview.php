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

if ( ! class_exists( 'WpssoIntegFormGravityView' ) ) {

	class WpssoIntegFormGravityView {

		private $p;	// Wpsso class object.
		private $form;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( is_admin() ) {

				/*
				 * The 'add_meta_boxes' action fires after all built-in meta boxes have been added.
				 */
				add_action( 'add_meta_boxes', array( $this, 'add_metabox_gravityview_integration' ) );
			}

			$this->p->util->add_plugin_filters( $this, array(
				'post_url'         => 2,
				'title_seed'       => 5,
				'description_seed' => 4,
				'post_image_urls'  => 4,
			) );
		}

		public function add_metabox_gravityview_integration() {

			if ( ! is_admin() ) {	// just in case

				return;
			}

			$metabox_id      = 'gravityview_integration';
			$metabox_title   = _x( 'Single Entry Integration', 'metabox title', 'wpsso' );
			$metabox_screen  = 'gravityview';
			$metabox_context = 'side';
			$metabox_prio    = 'high';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'__block_editor_compatible_meta_box' => true,
			);

			add_meta_box( '_wpsso_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_gravityview_integration' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );
		}

		public function show_metabox_gravityview_integration( $post_obj ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mod  = $this->p->post->get_mod( $post_obj->ID );
			$opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );
			$defs = $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] );

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $defs, $this->p->id );

			echo '<table class="sucom-settings wpsso post-side-metabox">';
			echo '<tr>';
			echo $this->form->get_th_html( _x( 'Title Field ID', 'option label', 'wpsso' ) );
			echo '<td>' . $this->form->get_input( 'gv_id_title', 'short', '', 0, true ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo $this->form->get_th_html( _x( 'Description Field ID', 'option label', 'wpsso' ) );
			echo '<td>' . $this->form->get_input( 'gv_id_desc', 'short', '', 0, true ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo $this->form->get_th_html( _x( 'Post Image Field ID', 'option label', 'wpsso' ) );
			echo '<td>' . $this->form->get_input( 'gv_id_img', 'short', '', 0, true ) . '</td>';
			echo '</tr>';
			echo '</table>';
		}

		public function filter_post_url( $url, $mod ) {

			if ( $entry_id = gravityview_is_single_entry() ) {

				$var_name = \GV\Entry::get_endpoint_name();

				if ( false !== strpos( $url, '?' ) ) {

					return add_query_arg( $var_name, $entry_id, $url );

				}

				return trailingslashit( $url ) . $var_name . '/' . $entry_id . '/';
			}

			return $url;
		}

		public function filter_title_seed( $title_text, $mod, $num_hashtags, $md_key, $title_sep ) {

			if ( $entry_id = gravityview_is_single_entry() ) {

				$opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( ! empty( $opts[ 'gv_id_title' ] ) ) {

					$entry = gravityview_get_entry( $entry_id );

					if ( isset( $entry[ $opts[ 'gv_id_title' ] ] ) ) {

						return $entry[ $opts[ 'gv_id_title' ] ];
					}
				}
			}

			return $title_text;
		}

		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $entry_id = gravityview_is_single_entry() ) {

				$opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( ! empty( $opts[ 'gv_id_desc' ] ) ) {

					$entry = gravityview_get_entry( $entry_id );

					if ( isset( $entry[ $opts[ 'gv_id_desc' ] ] ) ) {

						return $entry[ $opts[ 'gv_id_desc' ] ];
					}
				}
			}

			return $desc_text;
		}

		public function filter_post_image_urls( $urls, $size_name, $post_id, $mod ) {

			if ( $entry_id = gravityview_is_single_entry() ) {

				$opts = $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( ! empty( $opts[ 'gv_id_img' ] ) ) {

					$entry = gravityview_get_entry( $entry_id );

					if ( isset( $entry[ $opts[ 'gv_id_img' ] ] ) ) {

						list( $img_url, $img_title, $img_caption, $img_desc ) = array_pad( explode( '|:|', $entry[ $opts[ 'gv_id_img' ] ] ), 4, false );

						if ( ! empty( $img_url ) ) {

							$urls[] = $img_url;

							return $urls;
						}
					}
				}
			}

			return $urls;
		}
	}
}
