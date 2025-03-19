<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegUserCoAuthors' ) ) {

	class WpssoIntegUserCoAuthors {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'get_post_mod'          => 2,
				'get_other_user_images' => 5,
				'get_other_user_meta'   => 2,
				'get_author_meta'       => array(
					'get_author_meta'    => 4,
					'get_author_website' => 4,
				),
				'check_post_head'  => 3,
				'description_seed' => 4,
			) );

			$this->p->util->add_plugin_filters( $this, array(
				'get_user_object' => 2,
			), $prio = 50, $ext = 'sucom' );	// Note the 'sucom' filter prefix.

			add_filter( 'coauthors_guest_author_fields', array( $this, 'add_contact_methods' ), 20, 2 );
		}

		public function filter_get_post_mod( $mod, $mod_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$coauthors = get_coauthors( $mod_id );

			if ( empty( $coauthors ) || ! is_array( $coauthors ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no coauthors found for post ID ' . $mod_id );
				}

				return $mod;
			}

			/*
			 * Make sure the first (top) author listed is the post / page author.
			 */
			$author = reset( $coauthors );

			if ( ! empty( $author->ID ) && (int) $author->ID !== $mod[ 'post_author' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'setting author id ' . $author->ID . ' as primary author' );
				}

				$mod[ 'post_author' ] = (int) $author->ID;
			}

			foreach ( $coauthors as $author ) {

				if ( (int) $author->ID === $mod[ 'post_author' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping coauthor id ' . $author->ID . ' (primary author)' );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding coauthor id ' . $author->ID );
					}

					$mod[ 'post_coauthors' ][] = (int) $author->ID;
				}
			}

			return $mod;
		}

		/*
		 * Hooked to 'sucom_get_user_object'.
		 */
		public function filter_get_user_object( $user_obj, $user_id ) {

			global $coauthors_plus;

			if ( ! is_object( $user_obj ) && $user_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting object for coauthor id ' . $user_id );
				}

				$user_obj = $coauthors_plus->get_coauthor_by( 'id', $user_id );
			}

			return $user_obj;
		}

		public function filter_get_other_user_images( $mt_ret, $num, $size_names, $user_id, $md_pre ) {

			if ( 'guest-author' === get_post_type( $user_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'guest author / post ID ' . $user_id . ' images' );	// Begin timer.
				}

				$mt_ret = array_merge( $mt_ret, $this->p->media->get_post_images( $num, $size_names, $user_id, $md_pre ) );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'guest author / post ID ' . $user_id . ' images' );	// End timer.
				}
			}

			return $mt_ret;
		}

		/*
		 * Coauthor guest user meta is saved as a custom post type.
		 */
		public function filter_get_other_user_meta( $opts, $user_id ) {

			if ( 'guest-author' === get_post_type( $user_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'guest author / post ID ' . $user_id . ' meta' );	// Begin timer.
				}

				$mod = $this->p->post->get_mod( $user_id );

				$opts = $mod[ 'obj' ]->get_options( $user_id, $md_key = false, $filter_opts = false );	// Returns an empty string if no meta found.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->mark( 'guest author / post ID ' . $user_id . ' meta' );	// End timer.
				}
			}

			return $opts;
		}

		public function filter_get_author_meta( $value, $user_id, $field_id, $is_user ) {

			/*
			 * Abort if user_id is a valid WordPress user.
			 */
			if ( $is_user ) {

				return $value;
			}

			/*
			 * StdClass Object (
			 *	[ID]             => 2606
			 *	[display_name]   => Mr. John Doe
			 *	[first_name]     => John
			 *	[last_name]      => Doe
			 *	[user_login]     => mr-john-doe
			 *	[user_email]     => johndoe@someplace.com
			 *	[linked_account] =>
			 *	[website]        => http://guest_website.com
			 *	[aim]            =>
			 *	[yahooim]        =>
			 *	[jabber]         =>
			 *	[description]    => Some Bio info for John Doe.
			 *	[user_nicename]  => mr-john-doe
			 *	[type]           => guest-author
			 * )
			 */
			$user_obj = $this->filter_get_user_object( false, $user_id );

			if ( isset( $user_obj->ID ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'user_id ' . $user_id . ' coauthor object found' );
				}

			} else return $value;

			switch ( $field_id ) {

				case 'fullname':

					return ( isset( $user_obj->first_name ) ? trim( $user_obj->first_name ) : '' ) . ' ' .
						( isset( $user_obj->last_name ) ? trim( $user_obj->last_name ) : '' );

				case 'url':

					return isset( $user_obj->website ) ? trim( $user_obj->website ) : '';

				default:

					return isset( $user_obj->$field_id ) ? trim( $user_obj->$field_id ) : '';
			}

			return $value;
		}

		/*
		 * Don't check guest author custom post types (the permalink is not accessible).
		 */
		public function filter_check_post_head( $enabled, $post_id, $post_obj ) {

			if ( $enabled && isset( $post_obj->post_type ) && $post_obj->post_type === 'guest-author' ) {

				return false;
			}

			return $enabled;
		}

		/*
		 * Guest author custom post types don't have content - return the description author meta instead.
		 */
		public function filter_description_seed( $desc_text, $mod, $num_hashtags, $md_key ) {

			if ( $mod[ 'is_post' ] && $mod[ 'post_type' ] === 'guest-author' ) {

				$desc_text = $this->filter_get_author_meta( $desc_text, $mod[ 'id' ], 'description', false );
			}

			return $desc_text;
		}

		public function add_contact_methods( $fields = array(), $groups = null ) {

			/*
			 * Use the same check as the coauthors plugin.
			 */
			if ( ! in_array( 'contact-info', $groups ) && 'all' !== $groups[0] ) {

				return $fields;
			}

			/*
			 * Unset built-in contact fields and/or update their labels.
			 */
			if ( ! empty( $this->p->cf[ 'wp' ][ 'cm_names' ] ) && is_array( $this->p->cf[ 'wp' ][ 'cm_names' ] ) ) {

				foreach ( $fields as $num => $cm ) {

					if ( ! isset( $cm[ 'key' ] ) || ! isset( $cm[ 'group' ] ) || $cm[ 'group' ] !== 'contact-info' ) {

						continue;
					}

					/*
					 * Adjust for wp / coauthors key differences.
					 */
					switch ( $cm[ 'key' ] ) {

						case 'yahooim':

							$cm_opt = 'wp_cm_yim_';

							break;

						default:

							$cm_opt = 'wp_cm_' . $cm[ 'key' ] . '_';

							break;
					}

					if ( isset( $this->p->options[ $cm_opt . 'enabled' ] ) ) {

						if ( ! empty( $this->p->options[ $cm_opt . 'enabled' ] ) ) {

							if ( ! empty( $this->p->options[ $cm_opt . 'label' ] ) ) {
								$fields[ $num ][ 'label' ] = $this->p->options[ $cm_opt . 'label' ];
							}

						} else unset( $fields[ $num ] );
					}
				}
			}

			/*
			 * Loop through each social website option prefix.
			 */
			foreach ( $this->p->cf[ 'opt' ][ 'cm_prefix' ] as $cm_id => $opt_pre ) {

				$cm_enabled_key = 'plugin_cm_' . $opt_pre . '_enabled';
				$cm_name_key    = 'plugin_cm_' . $opt_pre . '_name';
				$cm_label_key   = 'plugin_cm_' . $opt_pre . '_label';

				if ( ! empty( $this->p->options[ $cm_enabled_key ] ) && ! empty( $this->p->options[ $cm_name_key ] ) ) {

					$cm_label_value = SucomUtilOptions::get_key_value( $cm_label_key, $this->p->options );

					if ( ! empty( $cm_label_value ) ) {

						$fields[] = array(
							'key'   => $this->p->options[ $cm_name_key ],
							'label' => $cm_label_value,
							'group' => 'contact-info',
						);
					}
				}
			}

			return $fields;
		}
	}
}
