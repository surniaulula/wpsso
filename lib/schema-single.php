<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoSchemaSingle' ) ) {

	class WpssoSchemaSingle {

		public static function add_book_data( &$json_data, array $mod, $book_id = false, $def_type_id = 'book', $list_element = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Maybe get options from integration modules.
			 */
			$book_opts = apply_filters( 'wpsso_get_book_options', false, $mod, $book_id );

			if ( ! empty( $book_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'get_book_options', $book_opts );
				}
			}

			/**
			 * Add metadata defaults and custom values to the $book_opts array.
			 *
			 * Automatically renames 'schema_book_*' options from the Document SSO metabox to 'book_*'.
			 */
			SucomUtil::add_type_opts_md_pad( $book_opts, $mod, array( 'book' => 'schema_book' ) );

			if ( empty( $book_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: no book options' );
				}

				return 0;
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $book_type_id, $book_type_url ) = self::get_type_id_url_list( $json_data, $book_opts,
				$opt_key = 'book_type', $def_type_id, $list_element );

			/**
			 * Maybe remove values related to the WordPress post object.
			 */
			unset( $json_data[ 'author' ] );
			unset( $json_data[ 'contributor' ] );
			unset( $json_data[ 'dateCreated' ] );
			unset( $json_data[ 'datePublished' ] );
			unset( $json_data[ 'dateModified' ] );

			/**
			 * Begin Schema book markup creation.
			 */
			$json_ret = WpssoSchema::get_schema_type_context( $book_type_url );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $book_opts, array(
				'isbn'          => 'book_isbn',
				'bookFormat'    => 'book_format',
				'bookEdition'   => 'book_edition',
				'numberOfPages' => 'book_pages',
			) );

			/**
			 * The author type value should be either 'organization' or 'person'.
			 */
			if ( WpssoSchema::is_valid_key( $book_opts, 'book_author_type' ) ) {	// Not null, an empty string, or 'none'.

				$author_type_url = $wpsso->schema->get_schema_type_url( $book_opts[ 'book_author_type' ] );

				$json_ret[ 'author' ] = WpssoSchema::get_schema_type_context( $author_type_url );

				WpssoSchema::add_data_itemprop_from_assoc( $json_ret[ 'author' ], $book_opts, array(
					'name' => 'book_author_name',
				) );

				if ( ! empty( $book_opts[ 'book_author_url' ] ) ) {

					$json_ret[ 'author' ][ 'sameAs' ][] = SucomUtil::esc_url_encode( $book_opts[ 'book_author_url' ] );
				}
			}

			/**
			 * Book Published Date, Time, Timezone.
			 *
			 * Add the creative work published date, if one is available.
			 */
			if ( $date = WpssoSchema::get_opts_date_iso( $book_opts, 'book_pub' ) ) {

				$json_ret[ 'datePublished' ] = $date;
			}

			/**
			 * Book Created Date, Time, Timezone.
			 *
			 * Add the creative work created date, if one is available.
			 */
			if ( $date = WpssoSchema::get_opts_date_iso( $book_opts, 'book_created' ) ) {

				$json_ret[ 'dateCreated' ] = $date;
			}

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;		// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return 1;	// Return count of books added.
		}

		public static function add_comment_data( &$json_data, array $post_mod, $comment_id, $list_element = true ) {

			$wpsso =& Wpsso::get_instance();

			$comments_added = 0;

			if ( empty( $comment_id ) ) {	// Just in case.

				return $comments_added;
			}

			$comment_mod = $wpsso->comment->get_mod( $comment_id );

			if ( ! $comment_mod[ 'is_comment' ] || ! $comment_mod[ 'id' ] ) {

				return $comments_added;
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			if ( ! $list_element && false !== ( $comment_type_url = WpssoSchema::get_data_type_url( $json_data ) ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'using inherited schema type url = ' . $comment_type_url );
				}

			} else {

				$comment_type_url = 'https://schema.org/Comment';
			}

			$json_ret = WpssoSchema::get_schema_type_context( $comment_type_url, array(
				'url'         => $wpsso->util->get_canonical_url( $comment_mod ),
				'name'        => $wpsso->page->get_title( $comment_mod, $md_key = 'schema_title', $max_len = 'schema_title' ),
				'description' => $wpsso->page->get_description( $comment_mod, $md_key = 'schema_desc', $max_len = 'schema_desc' ),
				'text'        => $wpsso->page->get_text( $comment_mod, $md_key = 'schema_text', $max_len = 'schema_text' ),
				'dateCreated' => $comment_mod[ 'comment_time' ],
				'author'      => WpssoSchema::get_schema_type_context( 'https://schema.org/Person', array(
					'url'  => $comment_mod[ 'comment_author_url' ],
					'name' => $comment_mod[ 'comment_author_name' ],
				) ),
			) );

			/**
			 * Property:
			 *      image as https://schema.org/ImageObject
			 *      video as https://schema.org/VideoObject
			 */
			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'adding image and video properties for comment' );
			}

			$mt_comment = $wpsso->og->get_array( $comment_mod, $size_names = 'schema', $md_pre = array( 'schema', 'og') );

			WpssoSchema::add_media_data( $json_ret, $comment_mod, $mt_comment, $size_names = 'schema', $add_video = true );

			$comments_added++;

			/**
			 * Add post comment replies.
			 */
			$replies_added = self::add_comment_reply_data( $json_ret[ 'comment' ], $post_mod, $comment_id );

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;	// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return $comments_added;	// Return count of comments added.
		}

		public static function add_comment_reply_data( &$json_data, $post_mod, $comment_id ) {

			$wpsso =& Wpsso::get_instance();

			$replies_added = 0;

			$replies = get_comments( array(
				'post_id' => $post_mod[ 'id' ],
				'status'  => 'approve',
				'parent'  => $comment_id,	// Get only the replies for this comment.
				'order'   => 'DESC',
				'number'  => get_option( 'page_comments' ),	// Limit the number of comments.
			) );

			if ( is_array( $replies ) ) {

				foreach( $replies as $num => $comment_obj ) {

					$comments_added = self::add_comment_data( $json_data, $post_mod, $comment_obj->comment_ID, $comment_list_el = true );

					if ( $comments_added ) {

						$replies_added += $comments_added;
					}
				}
			}

			return $replies_added;	// Return count of replies added.
		}

		public static function add_event_data( &$json_data, array $mod, $event_id = false, $list_element = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Maybe get options from integration modules.
			 */
			$event_opts = apply_filters( 'wpsso_get_event_options', false, $mod, $event_id );

			if ( ! empty( $event_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'get_event_options', $event_opts );
				}
			}

			/**
			 * Add metadata defaults and custom values to the $event_opts array.
			 *
			 * Automatically renames 'schema_event_*' options from the Document SSO metabox to 'event_*'.
			 */
			SucomUtil::add_type_opts_md_pad( $event_opts, $mod, array( 'event' => 'schema_event' ) );

			if ( empty( $event_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: no event options' );
				}

				return 0;
			}

			/**
			 * Add ISO formatted date options.
			 */
			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking for custom event start/end date and time' );
			}

			/**
			 * Get dates from the meta data options and add ISO formatted dates to the array (passed by reference).
			 *
			 * {event option name} => {meta data option name}.
			 */
			WpssoSchema::add_mod_opts_date_iso( $mod, $event_opts, array(
				'event_start_date'        => 'schema_event_start',		// Prefix for date, time, timezone, iso.
				'event_end_date'          => 'schema_event_end',		// Prefix for date, time, timezone, iso.
				'event_previous_date'     => 'schema_event_previous',		// Prefix for date, time, timezone, iso.
				'event_offers_start_date' => 'schema_event_offers_start',	// Prefix for date, time, timezone, iso.
				'event_offers_end_date'   => 'schema_event_offers_end',		// Prefix for date, time, timezone, iso.
			) );

			/**
			 * Add event offers.
			 */
			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking for custom event offers' );
			}

			$have_offers         = false;
			$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );
			$def_canonical_url   = $wpsso->util->get_canonical_url( $mod );

			foreach ( range( 0, $metadata_offers_max - 1, 1 ) as $key_num ) {

				$offer_opts = apply_filters( 'wpsso_get_event_offer_options', false, $mod, $event_id, $key_num );

				if ( ! empty( $offer_opts ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log_arr( 'get_event_offer_options', $offer_opts );
					}
				}

				if ( ! is_array( $offer_opts ) ) {

					$offer_opts = array();

					foreach ( array(
						'offer_name'           => 'schema_event_offer_name',
						'offer_url'            => 'schema_event_offer_url',
						'offer_price'          => 'schema_event_offer_price',
						'offer_price_currency' => 'schema_event_offer_currency',
						'offer_availability'   => 'schema_event_offer_avail',
					) as $opt_key => $md_pre ) {

						$offer_opts[ $opt_key ] = $mod[ 'obj' ]->get_options( $mod[ 'id' ], $md_pre . '_' . $key_num );
					}
				}

				/**
				 * Must have at least an offer name and price.
				 */
				if ( isset( $offer_opts[ 'offer_name' ] ) && isset( $offer_opts[ 'offer_price' ] ) ) {

					if ( ! isset( $event_opts[ 'offer_url' ] ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'setting offer_url to ' . $def_canonical_url );
						}

						$offer_opts[ 'offer_url' ] = $def_canonical_url;
					}

					if ( ! isset( $offer_opts[ 'offer_valid_from_date' ] ) ) {

						if ( ! empty( $event_opts[ 'event_offers_start_date_iso' ] ) ) {

							if ( $wpsso->debug->enabled ) {

								$wpsso->debug->log( 'setting offer_valid_from_date to ' . $event_opts[ 'event_offers_start_date_iso' ] );
							}

							$offer_opts[ 'offer_valid_from_date' ] = $event_opts[ 'event_offers_start_date_iso' ];

						} elseif ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'event option event_offers_start_date_iso is empty' );
						}
					}

					if ( ! isset( $offer_opts[ 'offer_valid_to_date' ] ) ) {

						if ( ! empty( $event_opts[ 'event_offers_end_date_iso' ] ) ) {

							if ( $wpsso->debug->enabled ) {

								$wpsso->debug->log( 'setting offer_valid_to_date to ' . $event_opts[ 'event_offers_end_date_iso' ] );
							}

							$offer_opts[ 'offer_valid_to_date' ] = $event_opts[ 'event_offers_end_date_iso' ];

						} elseif ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'event option event_offers_end_date_iso is empty' );
						}
					}

					if ( false === $have_offers ) {

						$have_offers = true;

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'custom event offer found - creating new offers array' );
						}

						$event_opts[ 'event_offers' ] = array();	// Clear offers returned by filter.
					}

					$event_opts[ 'event_offers' ][] = $offer_opts;
				}
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $event_type_id, $event_type_url ) = self::get_type_id_url_list( $json_data, $event_opts,
				$opt_key = 'event_type', $def_type_id = 'event', $list_element );

			/**
			 * Begin Schema event markup creation.
			 */
			$json_ret = WpssoSchema::get_schema_type_context( $event_type_url );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $event_opts, array(
				'inLanguage'          => 'event_lang',
				'eventAttendanceMode' => 'event_attendance',
				'eventStatus'         => 'event_status',
				'previousStartDate'   => 'event_previous_date_iso',
				'startDate'           => 'event_start_date_iso',
				'endDate'             => 'event_end_date_iso',
			) );

			/**
			 * Events with a previous start date must have rescheduled as their status.
			 *
			 * Rescheduled events, without a previous start date, is an invalid combination.
			 */
			if ( ! empty( $json_ret[ 'previousStartDate' ] ) ) {

				$json_ret[ 'eventStatus' ] = 'https://schema.org/EventRescheduled';

			} elseif ( isset( $json_ret[ 'eventStatus' ] ) && 'https://schema.org/EventRescheduled' === $json_ret[ 'eventStatus' ] ) {

				$json_ret[ 'eventStatus' ] = 'https://schema.org/EventScheduled';
			}

			/**
			 * Add place, organization, and person data.
			 *
			 * Use $opt_pre => $prop_name association as the property name may be repeated (ie. non-unique).
			 */
			foreach ( array(
				'event_online_url'          => 'location',
				'event_location_id'         => 'location',
				'event_organizer_org_id'    => 'organizer',
				'event_organizer_person_id' => 'organizer',
				'event_performer_org_id'    => 'performer',
				'event_performer_person_id' => 'performer',
			) as $opt_pre => $prop_name ) {

				foreach ( SucomUtil::preg_grep_keys( '/^' . $opt_pre . '(_[0-9]+)?$/', $event_opts ) as $opt_key => $id ) {

					/**
					 * Check that the id value is not true, false, null, or 'none'.
					 */
					if ( ! SucomUtil::is_valid_option_id( $id ) ) {

						continue;
					}

					switch ( $opt_pre ) {

						case 'event_online_url':

							$json_ret[ $prop_name ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/VirtualLocation',
								array( 'url' => $event_opts[ $opt_pre ] ) );

							break;

						case 'event_location_id':

							self::add_place_data( $json_ret[ $prop_name ], $mod, $id, $place_list_el = true );

							break;

						case 'event_organizer_org_id':
						case 'event_performer_org_id':

							self::add_organization_data( $json_ret[ $prop_name ], $mod, $id, 'org_logo_url', $org_list_el = true );

							break;

						case 'event_organizer_person_id':
						case 'event_performer_person_id':

							self::add_person_data( $json_ret[ $prop_name ], $mod, $id, $person_list_el = true );

							break;
					}
				}
			}

			if ( ! empty( $event_opts[ 'event_offers' ] ) && is_array( $event_opts[ 'event_offers' ] ) ) {

				foreach ( $event_opts[ 'event_offers' ] as $event_offer ) {

					if ( ! is_array( $event_offer ) ) {	// Just in case.

						continue;
					}

					if ( false !== ( $offer = WpssoSchema::get_data_itemprop_from_assoc( $event_offer, array(
						'name'          => 'offer_name',
						'url'           => 'offer_url',
						'price'         => 'offer_price',
						'priceCurrency' => 'offer_price_currency',
						'availability'  => 'offer_availability',	// In stock, Out of stock, Pre-order, etc.
						'validFrom'     => 'offer_valid_from_date',
						'validThrough'  => 'offer_valid_to_date',
					) ) ) ) {

						/**
						 * Add the offer.
						 */
						$json_ret[ 'offers' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/Offer', $offer );
					}
				}
			}

			/**
			 * Filter the single Event data.
			 */
			$json_ret = apply_filters( 'wpsso_json_data_single_event', $json_ret, $mod, $event_id );

			/**
			 * Update the @id string based on $json_ret[ 'url' ], $event_type_id, and $event_id values.
			 */
			WpssoSchema::update_data_id( $json_ret, array( $event_type_id, $event_id ) );

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;		// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return 1;	// Return count of events added.
		}

		/**
		 * This method converts an 'og:image' array into Schema ImageObject data.
		 *
		 * Pass a single dimension image array in $mt_single.
		 */
		public static function add_image_data_mt( &$json_data, $mt_single, $media_pre = 'og:image', $list_element = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			if ( empty( $mt_single ) || ! is_array( $mt_single ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: options array is empty or not an array' );
				}

				return 0;	// Return count of images added.
			}

			$image_url = SucomUtil::get_first_mt_media_url( $mt_single, $media_pre );

			if ( empty( $image_url ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: ' . $media_pre . ' URL values are empty' );
				}

				return 0;	// Return count of images added.
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $image_type_id, $image_type_url ) = self::get_type_id_url_list( $json_data, $type_opts = false,
				$opt_key = 'image_type', $def_type_id = 'image.object', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $image_type_url, array(
				'url' => SucomUtil::esc_url_encode( $image_url ),
			) );

			/**
			 * Maybe add an 'identifier' value based on the size name and image ID.
			 */
			if ( ! empty( $mt_single[ $media_pre . ':id' ] ) ) {

				$json_ret[ 'identifier' ] = $mt_single[ $media_pre . ':id' ];

				if ( ! empty( $mt_single[ $media_pre . ':size_name' ] ) ) {

					$json_ret[ 'identifier' ] .= '-' . $mt_single[ $media_pre . ':size_name' ];
				}
			}

			/**
			 * If we have an ID, and it's numeric (so exclude NGG v1 image IDs), check the WordPress Media Library for
			 * a title and description.
			 */
			if ( ! empty( $mt_single[ $media_pre . ':id' ] ) && is_numeric( $mt_single[ $media_pre . ':id' ] ) ) {

				$post_id = $mt_single[ $media_pre . ':id' ];

				$mod = $wpsso->post->get_mod( $post_id );

				/**
				 * Get the image title.
				 */
				$json_ret[ 'name' ] = $wpsso->page->get_title( $mod, $md_key = 'schema_title', $max_len = 'schema_title' );

				/**
				 * Get the image alternate title, if one has been defined in the custom post meta.
				 */
				$json_ret[ 'alternateName' ] = $wpsso->page->get_title( $mod, $md_key = 'schema_title_alt', $max_len = 'schema_title_alt' );

				if ( $json_ret[ 'name' ] === $json_ret[ 'alternateName' ] ) {	// Prevent duplicate values.

					unset( $json_ret[ 'alternateName' ] );
				}

				/**
				 * Use the image "Alternative Text" for the 'alternativeHeadline' property.
				 */
				$json_ret[ 'alternativeHeadline' ] = get_post_meta( $mod[ 'id' ], '_wp_attachment_image_alt', true );

				if ( $json_ret[ 'name' ] === $json_ret[ 'alternativeHeadline' ] ) {	// Prevent duplicate values.

					unset( $json_ret[ 'alternativeHeadline' ] );
				}

				/**
				 * Get the image caption (aka excerpt of the post object).
				 */
				$json_ret[ 'caption' ] = $wpsso->page->get_the_excerpt( $mod );

				$json_ret[ 'description' ] = $wpsso->page->get_description( $mod, $md_key = 'schema_desc', $max_len = 'schema_desc' );

				/**
				 * Set the 'encodingFormat' property to the image mime type.
				 */
				$json_ret[ 'encodingFormat' ] = get_post_mime_type( $mod[ 'id' ] );

				/**
				 * Set the 'uploadDate' property to the image attachment publish time.
				 */
				$json_ret[ 'uploadDate' ] = trim( get_post_time( 'c', $gmt = true, $mod[ 'id' ] ) );
			}

			if ( ! empty( $mt_single[ $media_pre . ':alt' ] ) ) {

				$json_ret[ 'alternativeHeadline' ] = $mt_single[ $media_pre . ':alt' ];
			}

			if ( ! empty( $mt_single[ $media_pre . ':tag' ] ) ) {

				if ( is_array( $mt_single[ $media_pre . ':tag' ] ) ) {

					$json_ret[ 'keywords' ] = implode( $glue = ', ', $mt_single[ $media_pre . ':tag' ] );

				} else {

					$json_ret[ 'keywords' ] = $mt_single[ $media_pre . ':tag' ];
				}
			}

			/**
			 * Add width and height as QuantitativeValue.
			 */
			WpssoSchema::add_data_unit_from_assoc( $json_ret, $mt_single, array(
				'width_px'  => $media_pre . ':width',
				'height_px' => $media_pre . ':height',
			) );

			/**
			 * Update the @id string based on $json_ret[ 'url' ] and $image_type_id.
			 */
			if ( ! empty( $mt_single[ $media_pre . ':id' ] ) ) {

				WpssoSchema::update_data_id( $json_ret, $image_type_id );
			}

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;		// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return 1;	// Return count of images added.
		}

		public static function add_job_data( &$json_data, array $mod, $job_id = false, $list_element = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Maybe get options from integration modules.
			 */
			$job_opts = apply_filters( 'wpsso_get_job_options', false, $mod, $job_id );

			if ( ! empty( $job_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'get_job_options', $job_opts );
				}
			}

			/**
			 * Add metadata defaults and custom values to the $job_opts array.
			 *
			 * Automatically renames 'schema_job_*' options from the Document SSO metabox to 'job_*'.
			 */
			SucomUtil::add_type_opts_md_pad( $job_opts, $mod, array( 'job' => 'schema_job' ) );

			if ( empty( $job_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: no job options' );
				}

				return 0;
			}

			if ( empty( $job_opts[ 'job_title' ] ) ) {

				$job_opts[ 'job_title' ] = $wpsso->page->get_title( $mod, $md_key = 'schema_job_title', $max_len = 'schema_title' );
			}

			/**
			 * Add ISO formatted date options.
			 */
			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'checking for custom job expire date and time' );
			}

			/**
			 * Get dates from the meta data options and add ISO formatted dates to the array (passed by reference).
			 *
			 * {job option} => {meta data option} (ie. the option name from the document SSO metabox).
			 */
			WpssoSchema::add_mod_opts_date_iso( $mod, $job_opts, $opts_md_pre = array(
				'job_expire' => 'schema_job_expire',	// Prefix for date, time, timezone, iso.
			) );

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $job_type_id, $job_type_url ) = self::get_type_id_url_list( $json_data,
				$job_opts, $opt_key = 'job_type', $def_type_id = 'job.posting', $list_element );

			/**
			 * Begin Schema job markup creation.
			 */
			$json_ret = WpssoSchema::get_schema_type_context( $job_type_url );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $job_opts, array(
				'title'           => 'job_title',
				'validThrough'    => 'job_expire_iso',
				'jobLocationType' => 'job_location_type',
			) );

			if ( isset( $job_opts[ 'job_salary' ] ) && is_numeric( $job_opts[ 'job_salary' ] ) ) {	// Allow for 0.

				$json_ret[ 'baseSalary' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/MonetaryAmount' );

				WpssoSchema::add_data_itemprop_from_assoc( $json_ret[ 'baseSalary' ], $job_opts, array(
					'currency' => 'job_salary_currency',
				) );

				$json_ret[ 'baseSalary' ][ 'value' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/QuantitativeValue' );

				WpssoSchema::add_data_itemprop_from_assoc( $json_ret[ 'baseSalary' ][ 'value' ], $job_opts, array(
					'value'    => 'job_salary',
					'unitText' => 'job_salary_period',
				) );
			}

			/**
			 * Allow for a preformatted employment types array.
			 */
			if ( ! empty( $job_opts[ 'job_empl_types' ] ) && is_array( $job_opts[ 'job_empl_types' ] ) ) {

				$json_ret[ 'employmentType' ] = $job_opts[ 'job_empl_types' ];
			}

			/**
			 * Add employment type options (value must be non-empty).
			 */
			foreach ( $wpsso->cf[ 'form' ][ 'employment_type' ] as $empl_type => $label ) {

				if ( 'none' !== $empl_type && ! empty( $job_opts[ 'job_empl_type_' . $empl_type ] ) ) {

					$json_ret[ 'employmentType' ][] = $empl_type;
				}
			}

			/**
			 * Add place, organization, and person data.
			 *
			 * Use $opt_pre => $prop_name association as the property name may be repeated (ie. non-unique).
			 */
			foreach ( array(
				'job_hiring_org_id' => 'hiringOrganization',
				'job_location_id'   => 'jobLocation',
			) as $opt_pre => $prop_name ) {

				foreach ( SucomUtil::preg_grep_keys( '/^' . $opt_pre . '(_[0-9]+)?$/', $job_opts ) as $opt_key => $id ) {

					/**
					 * Check that the id value is not true, false, null, or 'none'.
					 */
					if ( ! SucomUtil::is_valid_option_id( $id ) ) {

						continue;
					}

					switch ( $opt_pre ) {

						case 'job_hiring_org_id':

							self::add_organization_data( $json_ret[ $prop_name ], $mod, $id, 'org_logo_url', $org_list_el = true );

							break;

						case 'job_location_id':

							self::add_place_data( $json_ret[ $prop_name ], $mod, $id, $place_list_el = true );

							break;
					}
				}
			}

			/**
			 * Filter the single Job data.
			 */
			$json_ret = apply_filters( 'wpsso_json_data_single_job', $json_ret, $mod, $job_id );

			/**
			 * Update the @id string based on $json_ret[ 'url' ], $job_type_id, and $job_id values.
			 */
			WpssoSchema::update_data_id( $json_ret, array( $job_type_id, $job_id ) );

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;		// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return 1;	// Return count of jobs added.
		}

		/**
		 * $org_id can be 'none', 'site', or a number (including 0).
		 *
		 * $org_logo_key can be empty, 'org_logo_url', or 'org_banner_url' for Articles.
		 *
		 * Do not provide localized option names - the method will fetch the localized values.
		 */
		public static function add_organization_data( &$json_data, $mod, $org_id = 'site', $org_logo_key = 'org_logo_url', $list_element = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Check that the id is not true, false, null, or 'none'.
			 */
			if ( ! SucomUtil::is_valid_option_id( $org_id ) ) {

				return 0;
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'adding organization data for org id "' . $org_id . '"' );
			}

			/**
			 * Returned organization option values can change depending on the locale, but the option key names should NOT be localized.
			 *
			 * Example: 'org_banner_url' is a valid option key, but 'org_banner_url#fr_FR' is not.
			 */
			$org_opts = apply_filters( 'wpsso_get_organization_options', false, $mod, $org_id );

			if ( ! empty( $org_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'get_organization_options', $org_opts );
				}

			/**
			 * Fallback to using organization data from the WordPress site organization.
			 */
			} elseif ( 'site' === $org_id ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'getting site organization options array' );
				}

				$org_opts = WpssoSchema::get_site_organization( $mod ); // Returns localized values (not the key names).

			} else {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: unknown org_id ' . $org_id );
				}

				return 0;
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $org_type_id, $org_type_url ) = self::get_type_id_url_list( $json_data,
				$org_opts, $opt_key = 'org_schema_type', $def_type_id = 'organization', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $org_type_url );

			/**
			 * Set the reference values for admin notices.
			 */
			if ( is_admin() ) {

				$canonical_url = $wpsso->util->get_canonical_url( $mod );

				if ( 'site' === $org_id ) {

					$wpsso->util->maybe_set_ref( $canonical_url, $mod, __( 'adding schema organization', 'wpsso' ) );

				} else {

					$wpsso->util->maybe_set_ref( $canonical_url, $mod, sprintf( __( 'adding schema organization ID %s', 'wpsso' ), $org_id ) );
				}
			}

			/**
			 * Add schema properties from the organization options.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $org_opts, array(
				'url'           => 'org_url',
				'name'          => 'org_name',
				'alternateName' => 'org_name_alt',
				'description'   => 'org_desc',
				'email'         => 'org_email',
				'telephone'     => 'org_phone',
			) );

			/**
			 * Organization images.
			 */
			if ( ! empty( $org_opts[ 'org_img_id' ] ) || ! empty( $org_opts[ 'org_img_url' ] ) ) {

				/**
				 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
				 */
				$mt_images = $wpsso->media->get_mt_opts_images( $org_opts, $size_names = 'schema', $img_pre = 'org_img' );

				WpssoSchema::add_images_data_mt( $json_ret[ 'image' ], $mt_images );
			}

			if ( ! empty( $org_opts[ 'org_images' ] ) ) {

				WpssoSchema::add_images_data_mt( $json_ret[ 'image' ], $org_opts[ 'org_images' ] );
			}

			/**
			 * Google requires at least one image so fallback to using the Organization logo.
			 */
			if ( empty( $json_ret[ 'image' ] ) ) {

				$org_image_key = 'org_logo_url';

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding image from ' . $org_image_key . ' option' );
				}

				if ( ! empty( $org_opts[ $org_image_key ] ) ) {

					self::add_image_data_mt( $json_ret[ 'image' ], $org_opts, $org_image_key );
				}
			}

			/**
			 * Organization logo.
			 *
			 * $org_logo_key can be empty, 'org_logo_url', or 'org_banner_url' for Articles.
			 */
			if ( ! empty( $org_logo_key ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding logo from ' . $org_logo_key . ' option' );
				}

				if ( ! empty( $org_opts[ $org_logo_key ] ) ) {

					self::add_image_data_mt( $json_ret[ 'logo' ], $org_opts, $org_logo_key, $image_list_el = false );
				}

				if ( ! $mod[ 'is_post' ] || 'publish' === $mod[ 'post_status' ] ) {

					if ( ! empty( $json_ret[ 'name' ] ) && empty( $json_ret[ 'logo' ] ) ) {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'organization ' . $org_logo_key . ' image is missing and required' );
						}

						/**
						 * Add notice only if the admin notices have not already been shown.
						 */
						if ( $wpsso->notice->is_admin_pre_notices() ) {

							// translators: %1$s is the organization name, %2$s the Schema type URL.
							$logo_missing_msg   = __( 'An organization logo image is missing and required for the "%1$s" organization Schema %2$s markup.', 'wpsso' );

							// translators: %1$s is the organization name, %2$s the Schema type URL.
							$banner_missing_msg = __( 'An organization banner image is missing and required for the "%1$s" organization Schema %2$s markup.', 'wpsso' );

							// translators: %1$s is the organization name, %2$s is 'site' (translated) or 'ID #'.
							$org_settings_msg   = __( 'Please enter the missing image URL in the "%1$s" %2$s organization settings.', 'wpsso' );

							if ( 'org_logo_url' === $org_logo_key ) {

								$notice_msg = sprintf( $logo_missing_msg, $json_ret[ 'name' ],
									'<a href="' . $org_type_url . '">'. $org_type_url . '</a>' );

							} elseif ( 'org_banner_url' === $org_logo_key ) {

								$notice_msg = sprintf( $banner_missing_msg, $json_ret[ 'name' ],
									'<a href="' . $org_type_url . '">'. $org_type_url . '</a>' );

							} else {

								$notice_msg = '';
							}

							if ( $notice_msg ) {

								/**
								 * WebSite organization.
								 */
								if ( 'site' === $org_id ) {

									$settings_page_url = $wpsso->util->get_admin_url( 'essential' );
									$org_id_transl     = __( 'site', 'wpsso' );

									$notice_msg .= ' ';
									$notice_msg .= '<a href="' . $settings_page_url . '">';
									$notice_msg .= sprintf( $org_settings_msg, $json_ret[ 'name' ], $org_id_transl );
									$notice_msg .= '</a>';

								/**
								 * WPSSO Organization and Place add-on organization ID.
								 */
								} elseif ( 0 === strpos( $org_id, 'org-' ) && ! empty( $wpsso->avail[ 'p_ext' ][ 'opm' ] ) ) {

									$post_id       = substr( $org_id, 4 );
									$org_page_link = get_edit_post_link( $post_id );
									$org_id_transl = sprintf( __( 'ID #%s', 'wpsso' ), $post_id );

									$notice_msg .= ' ';
									$notice_msg .= $org_page_link ? '<a href="' . $org_page_link . '">' : '';
									$notice_msg .= sprintf( $org_settings_msg, $json_ret[ 'name' ], $org_id_transl );
									$notice_msg .= $org_page_link ? '</a>' : '';

								} else {

									$org_id_transl = sprintf( __( 'ID #%s', 'wpsso' ), $org_id );

									$notice_msg .= ' ';
									$notice_msg .= sprintf( $org_settings_msg, $json_ret[ 'name' ], $org_id_transl );
								}

								$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-schema-' . $org_logo_key;

								$wpsso->notice->err( $notice_msg, null, $notice_key );
							}
						}
					}
				}
			}

			/**
			 * Place / location properties.
			 */
			if ( isset( $org_opts[ 'org_place_id' ] ) ) {

				/**
				 * Check that the id is not true, false, null, or 'none'.
				 */
				if ( SucomUtil::is_valid_option_id( $org_opts[ 'org_place_id' ] ) ) {

					/**
					 * Check for a custom place id that might have precedence.
					 *
					 * 'schema_place_id' can be 'none', 'custom', or numeric (including 0).
					 */
					if ( ! empty( $mod[ 'obj' ] ) ) {

						$place_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_place_id' );

					} else {

						$place_id = null;
					}

					if ( null === $place_id ) {

						$place_id = $org_opts[ 'org_place_id' ];

					} else {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'overriding org_place_id ' . $org_opts[ 'org_place_id' ] . ' with schema_place_id ' . $place_id );
						}
					}

					self::add_place_data( $json_ret[ 'location' ], $mod, $place_id, $place_list_el = false );
				}
			}

			/**
			 * Google's knowledge graph.
			 */
			$org_opts[ 'org_sameas' ] = isset( $org_opts[ 'org_sameas' ] ) ? $org_opts[ 'org_sameas' ] : array();

			$org_opts[ 'org_sameas' ] = apply_filters( 'wpsso_json_data_single_organization_sameas', $org_opts[ 'org_sameas' ], $mod, $org_id );

			if ( ! empty( $org_opts[ 'org_sameas' ] ) && is_array( $org_opts[ 'org_sameas' ] ) ) {	// Just in case.

				foreach ( $org_opts[ 'org_sameas' ] as $url ) {

					if ( ! empty( $url ) ) {	// Just in case.

						$json_ret[ 'sameAs' ][] = SucomUtil::esc_url_encode( $url );
					}
				}
			}

			/**
			 * If the organization is a local business, then convert the organization markup to local business.
			 */
			if ( ! empty( $org_type_id ) ) {	// Just in case.

				if ( 'organization' !== $org_type_id ) {

					if ( $wpsso->schema->is_schema_type_child( $org_type_id, 'local.business' ) ) {

						WpssoSchema::organization_to_localbusiness( $json_ret );
					}
				}
			}

			/**
			 * Filter the single Organization data.
			 */
			$json_ret = apply_filters( 'wpsso_json_data_single_organization', $json_ret, $mod, $org_id );

			/**
			 * Update the @id string based on $json_ret[ 'url' ], $org_type_id, $org_id, and $org_logo_key values.
			 */
			WpssoSchema::update_data_id( $json_ret, array( $org_type_id, $org_id, $org_logo_key ) );

			/**
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {

				$wpsso->util->maybe_unset_ref( $canonical_url );
			}

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;		// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return 1;	// Return count of organizations added.
		}

		/**
		 * A $person_id argument is required.
		 */
		public static function add_person_data( &$json_data, $mod, $person_id, $list_element = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Maybe get options from integration modules (example: WpssoProEventTheEventsCalendar).
			 */
			$person_opts = apply_filters( 'wpsso_get_person_options', false, $mod, $person_id );

			if ( ! empty( $person_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'get_person_options', $person_opts );
				}
			}

			/**
			 * Fallback to using person data from the WordPress user profile.
			 */
			$canonical_url = '';

			if ( empty( $person_opts ) ) {

				if ( empty( $person_id ) || 'none' === $person_id ) {	// 0, an empty string, or a 'none' string.

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'exiting early: no person_id' );
					}

					return 0;
				}

				static $local_cache_person_opts = array();
				static $local_cache_person_urls = array();

				if ( ! isset( $local_cache_person_opts[ $person_id ] ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log( 'getting user module for person_id ' . $person_id );
					}

					$user_mod = $wpsso->user->get_mod( $person_id );

					$local_cache_person_urls[ $person_id ] = $wpsso->util->get_canonical_url( $user_mod );

					/**
					 * Set the reference values for admin notices.
					 */
					if ( is_admin() ) {

						$wpsso->util->maybe_set_ref( $local_cache_person_urls[ $person_id ], $user_mod, __( 'adding schema person', 'wpsso' ) );
					}

					$user_sameas = array();

					foreach ( WpssoUser::get_user_id_contact_methods( $person_id ) as $cm_id => $cm_label ) {

						$url = $user_mod[ 'obj' ]->get_author_meta( $person_id, $cm_id );

						if ( empty( $url ) ) {

							continue;

						} elseif ( $cm_id === $wpsso->options[ 'plugin_cm_twitter_name' ] ) {	// Convert twitter name to url.

							$url = 'https://twitter.com/' . SucomUtil::sanitize_twitter_name( $url, $add_at = false );
						}

						if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {

							if ( $wpsso->debug->enabled ) {

								$wpsso->debug->log( 'skipping ' . $cm_id . ': url "' . $url . '" is invalid' );
							}

						} else {

							$user_sameas[] = $url;
						}
					}

					$local_cache_person_opts[ $person_id ] = array(
						'person_type'       => 'person',
						'person_url'        => $user_mod[ 'obj' ]->get_author_website( $person_id, 'url' ),	// Returns a single URL string.
						'person_name'       => $user_mod[ 'obj' ]->get_author_meta( $person_id, 'display_name' ),
						'person_first_name' => $user_mod[ 'obj' ]->get_author_meta( $person_id, 'first_name' ),
						'person_last_name'  => $user_mod[ 'obj' ]->get_author_meta( $person_id, 'last_name' ),
						'person_addl_name'  => $user_mod[ 'obj' ]->get_author_meta( $person_id, 'additional_name' ),
						'person_prefix'     => $user_mod[ 'obj' ]->get_author_meta( $person_id, 'honorific_prefix' ),
						'person_suffix'     => $user_mod[ 'obj' ]->get_author_meta( $person_id, 'honorific_suffix' ),
						'person_job_title'  => $user_mod[ 'obj' ]->get_author_meta( $person_id, 'job_title' ),
						'person_desc'       => $wpsso->page->get_description( $user_mod, $md_key = 'schema_desc', $max_len = 'schema_desc' ),
						'person_images'     => $wpsso->media->get_all_images( $num = 1, $size_names = 'schema', $user_mod,
							$md_pre = array( 'schema', 'og' ) ),
						'person_sameas'     => $user_sameas,
					);

					/**
					 * Restore previous reference values for admin notices.
					 */
					if ( is_admin() ) {

						$wpsso->util->maybe_unset_ref( $local_cache_person_urls[ $person_id ] );
					}

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log_arr( 'local_cache_person_opts', $local_cache_person_opts[ $person_id ] );
					}
				}

				$person_opts = $local_cache_person_opts[ $person_id ];

				$canonical_url = $local_cache_person_urls[ $person_id ];
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $person_type_id, $person_type_url ) = self::get_type_id_url_list( $json_data, $person_opts,
				$opt_key = 'person_type', $def_type_id = 'person', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $person_type_url );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $person_opts, array(
				'url'             => 'person_url',
				'name'            => 'person_name',
				'givenName'       => 'person_first_name',
				'familyName'      => 'person_last_name',
				'additionalName'  => 'person_addl_name',
				'honorificPrefix' => 'person_prefix',
				'honorificSuffix' => 'person_suffix',
				'description'     => 'person_desc',
				'jobTitle'        => 'person_job_title',
				'email'           => 'person_email',
				'telephone'       => 'person_phone',
			) );

			/**
			 * Person images.
			 */
			if ( ! empty( $person_opts[ 'person_img_id' ] ) || ! empty( $person_opts[ 'person_img_url' ] ) ) {

				/**
				 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
				 */
				$mt_images = $wpsso->media->get_mt_opts_images( $person_opts, $size_names = 'schema', $img_pre = 'person_img' );

				WpssoSchema::add_images_data_mt( $json_ret[ 'image' ], $mt_images );

			}

			if ( ! empty( $person_opts[ 'person_images' ] ) ) {

				WpssoSchema::add_images_data_mt( $json_ret[ 'image' ], $person_opts[ 'person_images' ] );
			}

			/**
			 * Google's knowledge graph.
			 */
			$person_opts[ 'person_sameas' ] = isset( $person_opts[ 'person_sameas' ] ) ? $person_opts[ 'person_sameas' ] : array();

			$person_opts[ 'person_sameas' ] = apply_filters( 'wpsso_json_data_single_person_sameas', $person_opts[ 'person_sameas' ], $mod, $person_id );

			if ( ! empty( $person_opts[ 'person_sameas' ] ) && is_array( $person_opts[ 'person_sameas' ] ) ) {	// Just in case.

				foreach ( $person_opts[ 'person_sameas' ] as $url ) {

					if ( ! empty( $url ) ) {	// Just in case.

						$json_ret[ 'sameAs' ][] = SucomUtil::esc_url_encode( $url );
					}
				}
			}

			/**
			 * Filter the single Person data.
			 */
			$json_ret = apply_filters( 'wpsso_json_data_single_person', $json_ret, $mod, $person_id );

			/**
			 * Update the '@id' string based on the $canonical_url and the $person_type_id.
			 *
			 * Encode the URL part of the '@id' string to hide the WordPress login username.
			 */
			WpssoSchema::update_data_id( $json_ret, $person_type_id, $canonical_url, $hash_url = true );

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;		// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return 1;	// Return count of persons added.
		}

		public static function add_place_data( &$json_data, $mod, $place_id = false, $list_element = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Check that the id is not true, false, null, or 'none'.
			 */
			if ( ! SucomUtil::is_valid_option_id( $place_id ) ) {

				return 0;
			}

			/**
			 * Maybe get options from integration modules.
			 */
			$place_opts = apply_filters( 'wpsso_get_place_options', false, $mod, $place_id );

			if ( ! empty( $place_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'get_place_options', $place_opts );
				}
			}

			/**
			 * Add metadata defaults and custom values to the $place_opts array.
			 *
			 * Skip the SucomUtil::add_type_opts_md_pad() method as we do not have any default or custom 'schema_place'
			 * options in the Document SSO metabox:
			 *
			 * 	SucomUtil::add_type_opts_md_pad( $place_opts, $mod, array( 'place' => 'schema_place' ) );
			 */

			if ( empty( $place_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: no place options' );
				}

				return 0;
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $place_type_id, $place_type_url ) = self::get_type_id_url_list( $json_data, $place_opts,
				$opt_key = 'place_schema_type', $def_type_id = 'place', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $place_type_url );

			/**
			 * Set reference values for admin notices.
			 */
			if ( is_admin() ) {

				$canonical_url = $wpsso->util->get_canonical_url( $mod );

				$wpsso->util->maybe_set_ref( $canonical_url, $mod, __( 'adding schema place', 'wpsso' ) );
			}

			/**
			 * Add schema properties from the place options.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $place_opts, array(
				'url'                => 'place_url',
				'name'               => 'place_name',
				'alternateName'      => 'place_name_alt',
				'description'        => 'place_desc',
				'telephone'          => 'place_phone',
			) );

			/**
			 * Property:
			 *	address as https://schema.org/PostalAddress
			 */
			$postal_address = array();

			if ( WpssoSchema::add_data_itemprop_from_assoc( $postal_address, $place_opts, array(
				'name'                => 'place_name',
				'streetAddress'       => 'place_street_address',
				'postOfficeBoxNumber' => 'place_po_box_number',
				'addressLocality'     => 'place_city',
				'addressRegion'       => 'place_region',
				'postalCode'          => 'place_postal_code',
				'addressCountry'      => 'place_country',	// Alpha2 country code.
			) ) ) {

				$json_ret[ 'address' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/PostalAddress', $postal_address );
			}

			/**
			 * Property:
			 *	geo as https://schema.org/GeoCoordinates
			 */
			$geo = array();

			if ( WpssoSchema::add_data_itemprop_from_assoc( $geo, $place_opts, array(
				'elevation' => 'place_altitude',
				'latitude'  => 'place_latitude',
				'longitude' => 'place_longitude',
			) ) ) {

				$json_ret[ 'geo' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/GeoCoordinates', $geo );
			}

			/**
			 * Property:
			 *	openingHoursSpecification as https://schema.org/OpeningHoursSpecification
			 */
			if ( $opening_hours_spec = self::get_opening_hours_data( $place_opts, $opt_prefix = 'place' ) ) {

				$json_ret[ 'openingHoursSpecification' ] = $opening_hours_spec;
			}

			if ( ! empty( $place_opts[ 'place_schema_type' ] ) && $place_opts[ 'place_schema_type' ] !== 'none' ) {

				/**
				 * LocalBusiness schema type properties.
				 */
				if ( $wpsso->schema->is_schema_type_child( $place_opts[ 'place_schema_type' ], 'local.business' ) ) {

					WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $place_opts, array(
						'currenciesAccepted' => 'place_currencies_accepted',
						'paymentAccepted'    => 'place_payment_accepted',
						'priceRange'         => 'place_price_range',
					) );

					if ( ! empty( $place_opts[ 'place_latitude' ] ) &&
						! empty( $place_opts[ 'place_longitude' ] ) &&
							! empty( $place_opts[ 'place_service_radius' ] ) ) {

						$json_ret[ 'areaServed' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/GeoShape', array(
							'circle' => $place_opts[ 'place_latitude' ] . ' ' .
								$place_opts[ 'place_longitude' ] . ' ' .
								$place_opts[ 'place_service_radius' ]
						) );
					}

					/**
					 * FoodEstablishment schema type properties.
					 */
					if ( $wpsso->schema->is_schema_type_child( $place_opts[ 'place_schema_type' ], 'food.establishment' ) ) {

						foreach ( array(
							'acceptsReservations' => 'place_accept_res',
							'hasMenu'             => 'place_menu_url',
							'servesCuisine'       => 'place_cuisine',
						) as $prop_name => $opt_key ) {

							if ( 'place_accept_res' === $opt_key ) {

								$json_ret[ $prop_name ] = empty( $place_opts[ $opt_key ] ) ? 'false' : 'true';

							} elseif ( isset( $place_opts[ $opt_key ] ) ) {

								$json_ret[ $prop_name ] = $place_opts[ $opt_key ];
							}
						}

						if ( ! empty( $place_opts[ 'place_order_urls' ] ) ) {

							foreach ( SucomUtil::explode_csv( $place_opts[ 'place_order_urls' ] ) as $order_url ) {

								if ( empty( $order_url ) ) {	// Just in case.

									continue;
								}

								$json_ret[ 'potentialAction' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/OrderAction',
									array( 'target' => $order_url ) );
							}
						}
					}
				}
			}

			/**
			 * Place images.
			 */
			if ( ! empty( $place_opts[ 'place_img_id' ] ) || ! empty( $place_opts[ 'place_img_url' ] ) ) {

				/**
				 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
				 */
				$mt_images = $wpsso->media->get_mt_opts_images( $place_opts, $size_names = 'schema', $img_pre = 'place_img' );

				WpssoSchema::add_images_data_mt( $json_ret[ 'image' ], $mt_images );
			}

			if ( ! empty( $place_opts[ 'place_images' ] ) ) {

				WpssoSchema::add_images_data_mt( $json_ret[ 'image' ], $place_opts[ 'place_images' ] );
			}

			/**
			 * Filter the single Place data.
			 */
			$json_ret = apply_filters( 'wpsso_json_data_single_place', $json_ret, $mod, $place_id );

			/**
			 * Update the @id string based on $json_ret[ 'url' ], $place_type_id, and $place_id values.
			 */
			WpssoSchema::update_data_id( $json_ret, array( $place_type_id, $place_id ), $data_url = '' );

			/**
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {

				$wpsso->util->maybe_unset_ref( $canonical_url );
			}

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;		// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return 1;	// Return count of places added.
		}

		/**
		 * Pass a single dimension video array in $mt_single.
		 *
		 * Example $mt_single array:
		 *
		 *	Array (
		 *		[og:video:title]       => An Example Title
		 *		[og:video:description] => An example description...
		 *		[og:video:secure_url]  => https://vimeo.com/moogaloop.swf?clip_id=150575335&autoplay=1
		 *		[og:video:url]         => http://vimeo.com/moogaloop.swf?clip_id=150575335&autoplay=1
		 *		[og:video:type]        => application/x-shockwave-flash
		 *		[og:video:width]       => 1280
		 *		[og:video:height]      => 544
		 *		[og:video:embed_url]   => https://player.vimeo.com/video/150575335?autoplay=1
		 *		[og:video:has_image]   => 1
		 *		[og:image:secure_url]  => https://i.vimeocdn.com/video/550095036_1280.jpg
		 *		[og:image:url]         => http://i.vimeocdn.com/video/550095036_1280.jpg
		 *		[og:image:url]         =>
		 *		[og:image:width]       => 1280
		 *		[og:image:height]      => 544
		 *	)
		 */
		public static function add_video_data_mt( &$json_data, $mt_single, $media_pre = 'og:video', $list_element = true ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			if ( empty( $mt_single ) || ! is_array( $mt_single ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: options array is empty or not an array' );
				}

				return 0;	// Return count of videos added.
			}

			$media_url = SucomUtil::get_first_mt_media_url( $mt_single, $media_pre );

			if ( empty( $media_url ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: ' . $media_pre . ' URL values are empty' );
				}

				return 0;	// Return count of videos added.
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $video_type_id, $video_type_url ) = self::get_type_id_url_list( $json_data,
				$type_opts = false, $opt_key = false, $def_type_id = 'video.object', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $video_type_url, array(
				'url' => SucomUtil::esc_url_encode( $media_url ),
			) );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_single, array(
				'name'           => $media_pre . ':title',
				'description'    => $media_pre . ':description',
				'embedUrl'       => $media_pre . ':embed_url',
				'contentUrl'     => $media_pre . ':stream_url',
				'encodingFormat' => $media_pre . ':type',	// Mime type.
				'duration'       => $media_pre . ':duration',
				'uploadDate'     => $media_pre . ':upload_date',
				'thumbnailUrl'   => $media_pre . ':thumbnail_url',
			) );

			/**
			 * Add width and height as QuantitativeValue.
			 */
			WpssoSchema::add_data_unit_from_assoc( $json_ret, $mt_single, array(
				'width_px'  => $media_pre . ':width',
				'height_px' => $media_pre . ':height',
			) );

			if ( ! empty( $mt_single[ $media_pre . ':has_image' ] ) ) {

				self::add_image_data_mt( $json_ret[ 'thumbnail' ], $mt_single, null, $image_list_el = false );
			}

			if ( ! empty( $mt_single[ $media_pre . ':tag' ] ) ) {

				if ( is_array( $mt_single[ $media_pre . ':tag' ] ) ) {

					$json_ret[ 'keywords' ] = implode( $glue = ', ', $mt_single[ $media_pre . ':tag' ] );

				} else {

					$json_ret[ 'keywords' ] = $mt_single[ $media_pre . ':tag' ];
				}
			}

			/**
			 * Update the @id string based on $json_ret[ 'url' ] and $video_type_id.
			 */
			WpssoSchema::update_data_id( $json_ret, $video_type_id );

			if ( empty( $list_element ) ) {		// Add a single item.

				$json_data = $json_ret;

			} elseif ( is_array( $json_data ) ) {	// Just in case.

				if ( SucomUtil::is_assoc( $json_data ) ) {	// Converting from associative to array element.

					$json_data = array( $json_data );
				}

				$json_data[] = $json_ret;		// Add an item to the list.

			} else {

				$json_data = array( $json_ret );	// Add an item to the list.
			}

			return 1;	// Return count of videos added.
		}

		/**
		 * Note that $mt_offer could be the $mt_og array with minimal product meta tags.
		 */
		public static function get_offer_data( array $mod, array $mt_offer ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log_arr( 'mt_offer', $mt_offer );
			}

			$offer = WpssoSchema::get_schema_type_context( 'https://schema.org/Offer' );

			/**
			 * Note that 'og:url' may be provided instead of 'product:url'.
			 *
			 * Note that there is no Schema 'ean' property.
			 *
			 * Note that there is no Schema 'size' property.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $offer, $mt_offer, array(
				'url'                   => 'product:url',
				'name'                  => 'product:title',
				'description'           => 'product:description',
				'category'              => 'product:category',		// Product category ID from Google product taxonomy.
				'sku'                   => 'product:retailer_part_no',	// Product SKU.
				'mpn'                   => 'product:mfr_part_no',	// Product MPN.
				'gtin14'                => 'product:gtin14',		// Valid for both products and offers.
				'gtin13'                => 'product:gtin13',		// Valid for both products and offers.
				'gtin12'                => 'product:gtin12',		// Valid for both products and offers.
				'gtin8'                 => 'product:gtin8',		// Valid for both products and offers.
				'gtin'                  => 'product:gtin',		// Valid for both products and offers.
				'availability'          => 'product:availability',	// Only valid for offers.
				'itemCondition'         => 'product:condition',		// Valid for both products and offers.
				'hasAdultConsideration' => 'product:adult_type',	// Valid for both products and offers.
				'priceValidUntil'       => 'product:sale_price_dates:end',
			) );

			/**
			 * Fallback to the 'og:url' value, if one is available.
			 */
			if ( empty( $offer[ 'url' ] ) && ! empty( $mt_offer[ 'og:url' ] ) ) {

				$offer[ 'url' ] = $mt_offer[ 'og:url' ];
			}

			if ( false === $offer ) {	// Just in case.

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: missing basic product meta tags' );
				}

				return false;
			}

			/**
			 * Convert a numeric category ID to its Google category string.
			 */
			WpssoSchema::check_prop_value_category( $offer );

			WpssoSchema::check_prop_value_gtin( $offer );

			/**
			 * Prevents a missing property warning from the Google validator.
			 *
			 * By default, define normal product prices (not on sale) as valid for 1 year.
			 *
			 * Uses a static cache for all offers to allow for a common value in AggregateOffer markup.
			 */
			if ( empty( $offer[ 'priceValidUntil' ] ) ) {

				static $price_valid_until = null;

				if ( null === $price_valid_until ) {

					/**
					 * Skip if WPSSO_SCHEMA_PRODUCT_VALID_MAX_TIME = 0 or false.
					 */
					if ( $valid_max_time = SucomUtil::get_const( 'WPSSO_SCHEMA_PRODUCT_VALID_MAX_TIME' ) ) {

						$price_valid_until = gmdate( 'c', time() + $valid_max_time );

					} else {

						$price_valid_until = false;	// Check only once.
					}
				}

				if ( $price_valid_until ) {

					$offer[ 'priceValidUntil' ] = $price_valid_until;
				}
			}

			/**
			 * Schema 'priceSpecification' property.
			 */
			$price_spec = WpssoSchema::get_data_itemprop_from_assoc( $mt_offer, array(
				'priceType'             => 'product:price_type',
				'price'                 => 'product:price:amount',
				'priceCurrency'         => 'product:price:currency',
				'validFrom'             => 'product:sale_price_dates:start',
				'validThrough'          => 'product:sale_price_dates:end',
				'valueAddedTaxIncluded' => 'product:price:vat_included',
			) );

			if ( false !== $price_spec ) {

				/**
				 * Make sure we have a price currency.
				 */
				if ( empty( $price_spec[ 'priceCurrency' ] ) ) {

					$price_spec[ 'priceCurrency' ] = $wpsso->options[ 'og_def_currency' ];
				}

				/**
				 * See http://wiki.goodrelations-vocabulary.org/Documentation/UN/CEFACT_Common_Codes.
				 */
				$quantity = WpssoSchema::get_data_itemprop_from_assoc( $mt_offer, array(
					'value'    => 'product:eligible_quantity:value',
					'minValue' => 'product:eligible_quantity:min_value',
					'maxValue' => 'product:eligible_quantity:max_value',
					'unitCode' => 'product:eligible_quantity:unit_code',
					'unitText' => 'product:eligible_quantity:unit_text',
				) );

				if ( false !== $quantity ) {

					if ( ! isset( $quantity[ 'value' ] ) ) {

						if ( isset( $quantity[ 'minValue' ] ) && isset( $quantity[ 'maxValue' ] ) &&
							$quantity[ 'minValue' ] === $quantity[ 'maxValue' ] ) {

							$quantity[ 'value' ] = $quantity[ 'minValue' ];

							unset( $quantity[ 'minValue' ], $quantity[ 'maxValue' ] );
						}
					}

					$price_spec[ 'eligibleQuantity' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/QuantitativeValue', $quantity );
				}

				$offer[ 'priceSpecification' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/UnitPriceSpecification', $price_spec );
			}

			/**
			 * Schema 'shippingDetails' property.
			 */
			if ( empty( $mt_offer[ 'product:shipping_offers' ] ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'product shipping offers is empty' );
				}

			} elseif ( is_array( $mt_offer[ 'product:shipping_offers' ] ) ) {

				foreach ( $mt_offer[ 'product:shipping_offers' ] as $opt_num => $shipping_opts ) {

					if ( ! is_array( $shipping_opts ) ) {	// Just in case.

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'skipping shipping #' . $opt_num . ': not an array' );
						}

						continue;
					}

					$shipping_details = self::get_shipping_offer_data( $mod, $shipping_opts, $offer[ 'url' ] );

					if ( false === $shipping_details ) {

						continue;
					}

					$offer[ 'shippingDetails' ][] = $shipping_details;
				}

			} else {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'product shipping offers is not an array' );
				}
			}

			/**
			 * Add the seller organization data.
			 */
			self::add_organization_data( $offer[ 'seller' ], $mod, $org_id = 'site', $org_logo_key = 'org_logo_url', $org_list_el = false );

			/**
			 * Maybe add images to the $mt_og or $mt_offer array.
			 */
			if ( ! empty( $mt_offer[ 'product:retailer_item_id' ] ) && is_numeric( $mt_offer[ 'product:retailer_item_id' ] ) ) {	// Just in case.

				$post_id = $mt_offer[ 'product:retailer_item_id' ];

				$offer_mod = $wpsso->post->get_mod( $post_id );

				WpssoSchema::add_media_data( $offer, $offer_mod, $mt_offer, $size_names = 'schema', $add_video = false );
			}

			/**
			 * Filter the single Offer data.
			 */
			$offer = apply_filters( 'wpsso_json_data_single_offer', $offer, $mod );

			return $offer;
		}

		/**
		 * Returns OfferShippingDetails with shippingDestination and shippingRate properties.
		 *
		 * See https://developers.google.com/search/docs/data-types/product#shipping-details-best-practices.
		 */
		public static function get_shipping_offer_data( array $mod, array $shipping_opts, $offer_url = '' ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$shipping_offer = WpssoSchema::get_schema_type_context( 'https://schema.org/OfferShippingDetails' );

			/**
			 * An @id property is added at the end of this method, from the combination of the 'shipping_id' and
			 * $offer_url values.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $shipping_offer, $shipping_opts, array(
				'name' => 'shipping_name',
			) );

			if ( isset( $shipping_opts[ 'shipping_destinations' ] ) ) {

				/**
				 * Each destination options array can include an array of countries, a single country, or a single
				 * country and state - all with postal code limits, if any were found above.
				 *
				 * See https://developers.google.com/search/docs/data-types/product#shipping-details-best-practices.
				 */
				$dest_keys = array(
					'country_code' => 'addressCountry',	// Can be a string or an array.
					'region_code'  => 'addressRegion',
					'postal_code'  => 'postalCode',		// Can be a string or an array.
				);

				foreach ( $shipping_opts[ 'shipping_destinations' ] as $dest_num => $dest_opts ) {

					$defined_region = array();

					/**
					 * For each option key, assign its value to the associated Schema property name.
					 *
					 * If the option key is a postal code array, then check each value for a wildcard or range.
					 * If a wildcard or range is found, then assign its value to the postalCodeRange property
					 * instead.
					 */
					foreach ( $dest_opts as $opt_key => $val ) {

						if ( ! isset( $dest_keys[ $opt_key ] ) ) {	// Make sure we have a Schema property name.

							continue;
						}

						$prop_name = $dest_keys[ $opt_key ];

						/**
						 * Check for wildcards and ranges in postal codes.
						 */
						if ( 'postal_code' === $opt_key ) {

							if ( ! is_array( $val ) ) {	// Just in case.

								$val = array( $val );
							}

							foreach ( $val as $num => $postal_code ) {

								/**
								 * Note that wildcards and ranges cannot be mixed, and ranges only
								 * work with postal codes that are numeric (ie. US zip codes).
								 *
								 * Example:
								 *
								 *	H*
								 *	96200...96600
								 */
								if ( preg_match( '/^(.+)\*+$/', $postal_code, $matches ) ||
									preg_match( '/^(.+)\.\.\.(.+)$/', $postal_code, $matches ) ) {

									$postal_code_range = WpssoSchema::get_schema_type_context(
										'https://schema.org/PostalCodeRangeSpecification',
										array( 'postalCodeBegin' => $matches[ 1 ] )
									);

									if ( isset( $matches[ 2 ] ) ) {

										$postal_code_range[ 'postalCodeEnd' ] = $matches[ 2 ];
									}

									$defined_region[ 'postalCodeRange' ][] = $postal_code_range;

								} elseif ( ! empty( $postal_code ) ) {

									$defined_region[ 'postalCode' ][] = $postal_code;
								}
							}

						} else {

							$defined_region[ $prop_name ] = $val;
						}
					}

					if ( ! empty( $defined_region ) ) {

						if  ( ! empty( $dest_opts[ 'destination_id' ] ) ) {

							if  ( ! empty( $dest_opts[ 'destination_rel' ] ) ) {

								WpssoSchema::update_data_id( $defined_region, $dest_opts[ 'destination_id' ], $dest_opts[ 'destination_rel' ] );

							} else {

								WpssoSchema::update_data_id( $defined_region, $dest_opts[ 'destination_id' ], $offer_url );
							}
						}

						$shipping_offer[ 'shippingDestination' ][] = WpssoSchema::get_schema_type_context(
							'https://schema.org/DefinedRegion', $defined_region );
					}
				}
			}

			if ( isset( $shipping_opts[ 'shipping_rate' ] ) ) {

				/**
				 * See https://developers.google.com/search/docs/data-types/product#shipping-details-best-practices.
				 */
				$shipping_rate_keys = array(
					'shipping_rate_name'     => 'name',
					'shipping_rate_cost'     => 'value',
					'shipping_rate_currency' => 'currency',
				);

				$shipping_rate = array();

				foreach ( $shipping_opts[ 'shipping_rate' ] as $opt_key => $val ) {

					if ( isset( $shipping_rate_keys[ $opt_key ] ) ) {

						$shipping_rate[ $shipping_rate_keys[ $opt_key ] ] = $val;
					}
				}

				if ( ! empty( $shipping_rate ) ) {

					$shipping_offer[ 'shippingRate' ] = WpssoSchema::get_schema_type_context(
						'https://schema.org/MonetaryAmount', $shipping_rate );
				}
			}

			/**
			 * Example $shipping_opts[ 'delivery_time' ] = Array (
			 * 	[shipdept_rel] => http://adm.surniaulula.com/produit/a-variable-product/
			 * 	[shipdept_timezone] => America/Vancouver
			 * 	[shipdept_midday_close] => 12:00
			 * 	[shipdept_midday_open] => 13:00
			 * 	[shipdept_cutoff] => 16:00
			 * 	[shipdept_day_sunday_open] => none
			 * 	[shipdept_day_sunday_close] => none
			 * 	[shipdept_day_monday_open] => 09:00
			 * 	[shipdept_day_monday_close] => 17:00
			 * 	[shipdept_day_tuesday_open] => 09:00
			 * 	[shipdept_day_tuesday_close] => 17:00
			 * 	[shipdept_day_wednesday_open] => 09:00
			 * 	[shipdept_day_wednesday_close] => 17:00
			 * 	[shipdept_day_thursday_open] => 09:00
			 * 	[shipdept_day_thursday_close] => 17:00
			 * 	[shipdept_day_friday_open] => 09:00
			 * 	[shipdept_day_friday_close] => 17:00
			 * 	[shipdept_day_saturday_open] => none
			 * 	[shipdept_day_saturday_close] => none
			 * 	[shipdept_day_publicholidays_open] => 09:00
			 * 	[shipdept_day_publicholidays_close] => 12:00
			 *  	[handling_rel] => http://adm.surniaulula.com/produit/a-variable-product/
			 * 	[handling_maximum] => 1.5
			 * 	[handling_unit_code] => DAY
			 * 	[handling_unit_text] => d
			 * 	[handling_name] => Days
			 * 	[transit_rel] => http://adm.surniaulula.com/produit/a-variable-product/
			 * 	[transit_minimum] => 5
			 * 	[transit_maximum] => 7
			 * 	[transit_unit_code] => DAY
			 * 	[transit_unit_text] => d
			 * 	[transit_name] => Days
			 * )
			 */
			if ( isset( $shipping_opts[ 'delivery_time' ] ) ) {

				$delivery_opts =& $shipping_opts[ 'delivery_time' ];

				/**
				 * See https://schema.org/ShippingDeliveryTime.
				 */
				$delivery_time = array();

				/**
				 * Property:
				 *	businessDays as https://schema.org/OpeningHoursSpecification
				 */
				if ( $opening_hours_spec = self::get_opening_hours_data( $delivery_opts, $opt_prefix = 'shipdept' ) ) {

					$delivery_time[ 'businessDays' ] = $opening_hours_spec;
				}

				$cutoff_tz = SucomUtil::get_opts_hm_tz( $delivery_opts, $key_hm = 'shipdept_cutoff', $key_tz = 'shipdept_timezone' );

				if ( ! empty( $cutoff_tz ) ) {

					$delivery_time[ 'cutoffTime' ] = $cutoff_tz;
				}

				foreach ( array(
					'handling' => 'handlingTime',
					'transit'  => 'transitTime'
				) as $delivery_opt_pre => $delivery_prop_name ) {

					$quant_id = 'qv';
					$quantity = array();

					/**
					 * See http://wiki.goodrelations-vocabulary.org/Documentation/UN/CEFACT_Common_Codes.
					 */
					foreach( array(
						$delivery_opt_pre . '_name'      => 'name',
						$delivery_opt_pre . '_minimum'   => 'minValue',
						$delivery_opt_pre . '_maximum'   => 'maxValue',
						$delivery_opt_pre . '_unit_code' => 'unitCode',
						$delivery_opt_pre . '_unit_text' => 'unitText',
					) as $opt_key => $quant_prop_name ) {

						if ( isset( $delivery_opts[ $opt_key ] ) ) {

							/**
							 * Skip the name and unit text for the quantity @id value.
							 */
							if ( 'name' !== $quant_prop_name && 'unitText' !== $quant_prop_name ) {

								$quant_id .= '-' . $delivery_opts[ $opt_key ];
							}

							$quantity[ $quant_prop_name ] = $delivery_opts[ $opt_key ];

						} else {

							$quant_id .= '--';
						}
					}

					if ( ! empty( $quantity ) ) {

						if ( ! isset( $quantity[ 'value' ] ) ) {

							if ( isset( $quantity[ 'minValue' ] ) && isset( $quantity[ 'maxValue' ] ) &&
								$quantity[ 'minValue' ] === $quantity[ 'maxValue' ] ) {

								$quantity[ 'value' ] = $quantity[ 'minValue' ];

								unset( $quantity[ 'minValue' ], $quantity[ 'maxValue' ] );
							}
						}

						if ( ! empty( $quantity[ 'unitCode' ] ) ) {

							$quant_id = strtolower( $quant_id );

							$quant_rel = empty( $delivery_opts[ $delivery_opt_pre . '_rel' ] ) ?
								$offer_url : $delivery_opts[ $delivery_opt_pre . '_rel' ];

							WpssoSchema::update_data_id( $quantity, $quant_id, $quant_rel );
						}

						$delivery_time[ $delivery_prop_name ] = WpssoSchema::get_schema_type_context(
							'https://schema.org/QuantitativeValue', $quantity );
					}
				}

				if ( ! empty( $delivery_time ) ) {

					$shipping_offer[ 'deliveryTime' ] = WpssoSchema::get_schema_type_context(
						'https://schema.org/ShippingDeliveryTime', $delivery_time );
				}
			}

			if  ( ! empty( $shipping_opts[ 'shipping_id' ] ) ) {

				if  ( ! empty( $shipping_opts[ 'shipping_rel' ] ) ) {

					WpssoSchema::update_data_id( $shipping_offer, $shipping_opts[ 'shipping_id' ], $shipping_opts[ 'shipping_rel' ] );

				} else {

					WpssoSchema::update_data_id( $shipping_offer, $shipping_opts[ 'shipping_id' ], $offer_url );
				}
			}

			/**
			 * Filter the single Shipping Offer data.
			 */
			$shipping_offer = apply_filters( 'wpsso_json_data_single_shipping_offer', $shipping_offer, $mod );

			return $shipping_offer;
		}

		/**
		 * Returns an array or false if there are no open/close hours.
		 *
		 * Example $opts = Array (
		 * 	[shipdept_rel] => http://adm.surniaulula.com/produit/a-variable-product/
		 * 	[shipdept_timezone] => America/Vancouver
		 * 	[shipdept_midday_close] => 12:00
		 * 	[shipdept_midday_open] => 13:00
		 * 	[shipdept_cutoff] => 16:00
		 * 	[shipdept_day_sunday_open] => none
		 * 	[shipdept_day_sunday_close] => none
		 * 	[shipdept_day_monday_open] => 09:00
		 * 	[shipdept_day_monday_close] => 17:00
		 * 	[shipdept_day_tuesday_open] => 09:00
		 * 	[shipdept_day_tuesday_close] => 17:00
		 * 	[shipdept_day_wednesday_open] => 09:00
		 * 	[shipdept_day_wednesday_close] => 17:00
		 * 	[shipdept_day_thursday_open] => 09:00
		 * 	[shipdept_day_thursday_close] => 17:00
		 * 	[shipdept_day_friday_open] => 09:00
		 * 	[shipdept_day_friday_close] => 17:00
		 * 	[shipdept_day_saturday_open] => none
		 * 	[shipdept_day_saturday_close] => none
		 * 	[shipdept_day_publicholidays_open] => 09:00
		 * 	[shipdept_day_publicholidays_close] => 12:00
		 * )
		 */
		public static function get_opening_hours_data( array $opts, $opt_prefix = 'place' ) {

			$wpsso =& Wpsso::get_instance();

			$hours_rel          = isset( $opts[ $opt_prefix . '_rel' ] ) ? $opts[ $opt_prefix . '_rel' ] : '';
			$business_weekdays  = $wpsso->cf[ 'form' ][ 'weekdays' ];
			$opening_hours_spec = array();

			foreach ( $business_weekdays as $day_name => $day_label ) {

				/**
				 * Returns an empty array or an associative array of open => close hours, including a timezone offset.
				 *
				 * $open_close = Array (
				 *	[08:00-07:00] => 17:00-07:00
				 * )
				 *
				 * -07:00 is a timezone offset.
				 */
				$open_close = SucomUtil::get_opts_open_close_hm_tz(
					$opts,
					$opt_prefix . '_day_' . $day_name . '_open',
					$opt_prefix . '_midday_close',
					$opt_prefix . '_midday_open',
					$opt_prefix . '_day_' . $day_name . '_close',
					$opt_prefix . '_timezone'
				);

				if ( ! empty( $open_close ) ) {

					foreach ( $open_close as $open => $close ) {

						$weekday_spec = array(
							'@context'  => 'https://schema.org',
							'@type'     => 'OpeningHoursSpecification',
							'dayOfWeek' => $day_label,
							'opens'     => $open,
							'closes'    => $close,
						);

						foreach ( array(
							'validFrom'    => $opt_prefix . '_season_from_date',
							'validThrough' => $opt_prefix . '_season_to_date',
						) as $prop_name => $opt_key ) {

							if ( isset( $opts[ $opt_key ] ) && '' !== $opts[ $opt_key ] ) {

								$weekday_spec[ $prop_name ] = $opts[ $opt_key ];
							}
						}

						$hours_id = array( 'hours', md5( json_encode( $weekday_spec ) ) );

						WpssoSchema::update_data_id( $weekday_spec, $hours_id, $hours_rel );

						$opening_hours_spec[] = $weekday_spec;
					}
				}
			}

			return empty( $opening_hours_spec ) ? false : $opening_hours_spec;
		}

		/**
		 * If not adding a list element, then inherit the existing schema type url (if one exists).
		 */
		private static function get_type_id_url_list( $json_data, $type_opts, $opt_key, $def_type_id, $list_element = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$single_type_id   = false;
			$single_type_url  = false;
			$single_type_from = 'inherited';

			if ( ! $list_element ) {

				$single_type_url = WpssoSchema::get_data_type_url( $json_data );
			}

			if ( ! $single_type_url ) {

				/**
				 * $type_opts may be false, null, or an array.
				 */
				if ( empty( $type_opts[ $opt_key ] ) || 'none' === $type_opts[ $opt_key ] ) {

					$single_type_id   = $def_type_id;
					$single_type_url  = $wpsso->schema->get_schema_type_url( $def_type_id );
					$single_type_from = 'default';

				} else {

					$single_type_id   = $type_opts[ $opt_key ];
					$single_type_url  = $wpsso->schema->get_schema_type_url( $single_type_id, $def_type_id );
					$single_type_from = 'options';
				}

			} else {

				$single_type_id = $wpsso->schema->get_schema_type_url_id( $single_type_url, $def_type_id );
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'using ' . $single_type_from . ' single type url: ' . $single_type_url );
			}

			return array( $single_type_id, $single_type_url );
		}
	}
}
