<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegUserPublishPressAuthors' ) ) {

	class WpssoIntegUserPublishPressAuthors {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'get_post_mod' => 2,
			) );
		}

		public function filter_get_post_mod( $mod, $mod_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Array (
			 *	[0] => MultipleAuthors\Classes\Objects\Author Object (
			 *		[term_id] => 770
			 *		[term:MultipleAuthors\Classes\Objects\Author:private] =>
			 *		[metaCache:MultipleAuthors\Classes\Objects\Author:private] =>
			 *		[userObject:MultipleAuthors\Classes\Objects\Author:private] =>
			 *		[hasCustomAvatar:MultipleAuthors\Classes\Objects\Author:private] =>
			 *		[customAvatarUrl:MultipleAuthors\Classes\Objects\Author:private] =>
			 *		[avatarUrl:MultipleAuthors\Classes\Objects\Author:private] =>
			 *		[avatarBySize:MultipleAuthors\Classes\Objects\Author:private] => Array () 
			 *	) 
			 * )
			 */
			$post_authors = get_post_authors( $mod_id );

			if ( empty( $post_authors ) || ! is_array( $post_authors ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no coauthors found for post ID ' . $mod_id );
				}

				return $mod;
			}

			foreach ( $post_authors as $author_num => $post_author ) {

				if ( empty( $post_author->user_id ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping coauthor #' . $author_num . ' (user_id is empty)' );
					}

				} elseif ( (int) $post_author->user_id === $mod[ 'post_author' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping coauthor #' . $author_num . ' id ' . $post_author->user_id . ' (already primary author)' );
					}

				/*
				 * Make sure the first (top) author listed is the post / page author.
				 */
				} elseif ( 0 === $author_num ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'setting coauthor #' . $author_num . ' id ' . $post_author->user_id . ' as primary author' );
					}

					$mod[ 'post_author' ] = (int) $post_author->user_id;

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding coauthor #' . $author_num . ' id ' . $post_author->user_id );
					}

					$mod[ 'post_coauthors' ][] = (int) $post_author->user_id;
				}
			}

			return $mod;
		}
	}
}
