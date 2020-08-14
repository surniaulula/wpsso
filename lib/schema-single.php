<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoSchemaSingle' ) ) {

	class WpssoSchemaSingle {

		/**
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
			list( $image_type_id, $image_type_url ) = self::get_type_id_url( $json_data,
				$type_opts = false, $opt_key = 'image_type', $def_type_id = 'image.object', $list_element );

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
				$json_ret[ 'name' ] = $wpsso->page->get_title( 0, '', $mod, true, false, true, 'schema_title', false );

				/**
				 * Get the image alternate title, if one has been defined in the custom post meta.
				 */
				$title_max_len = $wpsso->options[ 'og_title_max_len' ];

				$json_ret[ 'alternateName' ] = $wpsso->page->get_title( $title_max_len, '...', $mod, true, false, true, 'schema_title_alt' );

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

				/**
				 * If we don't have a caption, then provide a short description.
				 *
				 * If we have a caption, then add the complete image description.
				 */
				if ( empty( $json_ret[ 'caption' ] ) ) {

					$json_ret[ 'description' ] = $wpsso->page->get_description( $wpsso->options[ 'schema_desc_max_len' ],
						$dots = '...', $mod, $read_cache = true, $add_hashtags = false, $do_encode = true,
							$md_key = array( 'schema_desc', 'seo_desc', 'og_desc' ) );

				} else {

					$json_ret[ 'description' ] = $wpsso->page->get_the_text( $mod, $read_cache = true,
						$md_key = array( 'schema_desc', 'seo_desc', 'og_desc' ) );
				}

				/**
				 * Set the 'fileFormat' property to the image mime type.
				 */
				$json_ret[ 'fileFormat' ] = get_post_mime_type( $mod[ 'id' ] );

				/**
				 * Set the 'uploadDate' property to the image attachment publish time.
				 */
				$json_ret[ 'uploadDate' ] = trim( get_post_time( 'c', $gmt = true, $mod[ 'id' ] ) );
			}

			foreach ( array( 'width', 'height' ) as $prop_name ) {

				if ( isset( $mt_single[ $media_pre . ':' . $prop_name ] ) && $mt_single[ $media_pre . ':' . $prop_name ] > 0 ) {	// Just in case.

					$json_ret[ $prop_name ] = $mt_single[ $media_pre . ':' . $prop_name ];
				}
			}

			if ( ! empty( $mt_single[ $media_pre . ':tag' ] ) ) {

				if ( is_array( $mt_single[ $media_pre . ':tag' ] ) ) {

					$json_ret[ 'keywords' ] = implode( ', ', $mt_single[ $media_pre . ':tag' ] );

				} else {

					$json_ret[ 'keywords' ] = $mt_single[ $media_pre . ':tag' ];
				}
			}

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
			list( $video_type_id, $video_type_url ) = self::get_type_id_url( $json_data,
				$type_opts = false, $opt_key = false, $def_type_id = 'video.object', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $video_type_url, array(
				'url' => SucomUtil::esc_url_encode( $media_url ),
			) );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_single, array(
				'name'         => $media_pre . ':title',
				'description'  => $media_pre . ':description',
				'fileFormat'   => $media_pre . ':type',	// Mime type.
				'width'        => $media_pre . ':width',
				'height'       => $media_pre . ':height',
				'duration'     => $media_pre . ':duration',
				'uploadDate'   => $media_pre . ':upload_date',
				'thumbnailUrl' => $media_pre . ':thumbnail_url',
				'embedUrl'     => $media_pre . ':embed_url',
				'contentUrl'   => $media_pre . ':stream_url',
			) );

			if ( ! empty( $mt_single[ $media_pre . ':has_image' ] ) ) {

				self::add_image_data_mt( $json_ret[ 'thumbnail' ], $mt_single, null, false );	// $list_element is false.
			}

			if ( ! empty( $mt_single[ $media_pre . ':tag' ] ) ) {

				if ( is_array( $mt_single[ $media_pre . ':tag' ] ) ) {

					$json_ret[ 'keywords' ] = implode( ', ', $mt_single[ $media_pre . ':tag' ] );

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

		public static function add_comment_data( &$json_data, array $mod, $comment_id, $list_element = true ) {

			$wpsso =& Wpsso::get_instance();

			$comments_added = 0;

			if ( $comment_id && $cmt = get_comment( $comment_id ) ) {	// Just in case.

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
					'url'         => get_comment_link( $cmt->comment_ID ),
					'dateCreated' => mysql2date( 'c', $cmt->comment_date_gmt ),
					'description' => get_comment_excerpt( $cmt->comment_ID ),
					'author'      => WpssoSchema::get_schema_type_context( 'https://schema.org/Person', array(
						'name' => $cmt->comment_author,
					) ),
				) );

				$comments_added++;

				$replies_added = self::add_comment_reply_data( $json_ret[ 'comment' ], $mod, $cmt->comment_ID );

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
			}

			return $comments_added;	// Return count of comments added.
		}

		public static function add_comment_reply_data( &$json_data, $mod, $comment_id ) {

			$wpsso =& Wpsso::get_instance();

			$replies_added = 0;

			$replies = get_comments( array(
				'post_id' => $mod[ 'id' ],
				'status'  => 'approve',
				'parent'  => $comment_id,	// Get only the replies for this comment.
				'order'   => 'DESC',
				'number'  => get_option( 'page_comments' ),	// Limit the number of comments.
			) );

			if ( is_array( $replies ) ) {

				foreach( $replies as $num => $reply ) {

					$comments_added = WpssoSchemaSingle::add_comment_data( $json_data, $mod, $reply->comment_ID, $comment_list_el = true );

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
			$event_opts = apply_filters( $wpsso->lca . '_get_event_options', false, $mod, $event_id );

			$event_opts = SucomUtil::complete_type_options( $event_opts, $mod, array( 'event' => 'schema_event' ) );

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

			$have_offers = false;

			$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );

			$def_sharing_url = $wpsso->util->get_sharing_url( $mod );

			foreach ( range( 0, $metadata_offers_max - 1, 1 ) as $key_num ) {

				$offer_opts = apply_filters( $wpsso->lca . '_get_event_offer_options', false, $mod, $event_id, $key_num );

				if ( ! empty( $offer_opts ) ) {

					if ( $wpsso->debug->enabled ) {

						$wpsso->debug->log_arr( 'get_event_offer_options filters returned', $offer_opts );
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

							$wpsso->debug->log( 'setting offer_url to ' . $def_sharing_url );
						}

						$offer_opts[ 'offer_url' ] = $def_sharing_url;
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
			list( $event_type_id, $event_type_url ) = self::get_type_id_url( $json_data,
				$event_opts, $opt_key = 'event_type', $def_type_id = 'event', $list_element );

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

							$json_ret[ 'location' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/VirtualLocation', array(
								'url' => $event_opts[ $opt_pre ],
							) );

							break;

						case 'event_location_id':

							WpssoSchemaSingle::add_place_data( $json_ret[ $prop_name ], $mod, $id, $place_list_el = true );

							break;

						case 'event_organizer_org_id':
						case 'event_performer_org_id':

							WpssoSchemaSingle::add_organization_data( $json_ret[ $prop_name ], $mod, $id, 'org_logo_url', $org_list_el = true );

							break;

						case 'event_organizer_person_id':
						case 'event_performer_person_id':

							WpssoSchemaSingle::add_person_data( $json_ret[ $prop_name ], $mod, $id, $person_list_el = true );

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

			$json_ret = apply_filters( $wpsso->lca . '_json_data_single_event', $json_ret, $mod, $event_id );

			/**
			 * Update the @id string based on $json_ret[ 'url' ], $event_type_id, and $event_id values.
			 */
			WpssoSchema::update_data_id( $json_ret, $event_type_id . '/' . $event_id );

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

		public static function add_job_data( &$json_data, array $mod, $job_id = false, $list_element = false ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Begin creation of $job_opts array.
			 *
			 * Maybe get options from integration modules.
			 */
			$job_opts = apply_filters( $wpsso->lca . '_get_job_options', false, $mod, $job_id );

			$job_opts = WpssoUtil::complete_type_options( $job_opts, $mod, array( 'job' => 'schema_job' ) );

			if ( empty( $job_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: no job options' );
				}

				return 0;
			}

			if ( empty( $job_opts[ 'job_title' ] ) ) {

				$job_opts[ 'job_title' ] = $wpsso->page->get_title( 0, '', $mod, true, false, true, 'schema_title', false );
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
			 * {job option name} => {meta data option name}.
			 */
			WpssoSchema::add_mod_opts_date_iso( $mod, $job_opts, $opts_md_pre = array(
				'job_expire' => 'schema_job_expire',	// Prefix for date, time, timezone, iso.
			) );

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $job_type_id, $job_type_url ) = self::get_type_id_url( $json_data,
				$job_opts, $opt_key = 'job_type', $def_type_id = 'job.posting', $list_element );

			/**
			 * Begin Schema job markup creation.
			 */
			$json_ret = WpssoSchema::get_schema_type_context( $job_type_url );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $job_opts, array(
				'title'        => 'job_title',
				'validThrough' => 'job_expire_iso',
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
			 * Add single employment type options (value must be non-empty).
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^job_empl_type_(.*)(:is)?$/U', $job_opts, false, '$1' ) as $empl_type => $checked ) {

				if ( ! empty( $checked ) ) {

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

							WpssoSchemaSingle::add_organization_data( $json_ret[ $prop_name ], $mod, $id, 'org_logo_url', $org_list_el = true );

							break;

						case 'job_location_id':

							WpssoSchemaSingle::add_place_data( $json_ret[ $prop_name ], $mod, $id, $place_list_el = true );

							break;
					}
				}
			}

			$json_ret = apply_filters( $wpsso->lca . '_json_data_single_job', $json_ret, $mod, $job_id );

			/**
			 * Update the @id string based on $json_ret[ 'url' ], $job_type_id, and $job_id values.
			 */
			WpssoSchema::update_data_id( $json_ret, $job_type_id . '/' . $job_id );

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
		 * Note that $mt_offer could be the $mt_og array with minimal product meta tags.
		 */
		public static function get_offer_data( array $mod, array $mt_offer ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			/**
			 * Note that 'og:url' may be provided instead of 'product:url'.
			 *
			 * Note that there is no Schema 'ean' property.
			 *
			 * Note that there is no Schema 'size' property.
			 */
			$offer = WpssoSchema::get_data_itemprop_from_assoc( $mt_offer, array( 
				'url'             => 'product:url',
				'name'            => 'product:title',
				'description'     => 'product:description',
				'category'        => 'product:category',
				'sku'             => 'product:retailer_part_no',	// Product SKU.
				'mpn'             => 'product:mfr_part_no',		// Product MPN.
				'gtin14'          => 'product:gtin14',			// Valid for both products and offers.
				'gtin13'          => 'product:gtin13',			// Valid for both products and offers.
				'gtin12'          => 'product:gtin12',			// Valid for both products and offers.
				'gtin8'           => 'product:gtin8',			// Valid for both products and offers.
				'gtin'            => 'product:gtin',			// Valid for both products and offers.
				'availability'    => 'product:availability',		// Only valid for offers.
				'itemCondition'   => 'product:condition',
				'price'           => 'product:price:amount',
				'priceCurrency'   => 'product:price:currency',
				'priceValidUntil' => 'product:sale_price_dates:end',
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
			 * Convert a numeric category ID to its Google product type string.
			 */
			WpssoSchema::check_prop_value_category( $offer );

			WpssoSchema::check_prop_value_gtin( $offer );

			/**
			 * Prevents a missing property warning from the Google validator.
			 */
			if ( empty( $offer[ 'priceValidUntil' ] ) ) {

				/**
				 * By default, define normal product prices (not on sale) as valid for 1 year.
				 */
				$valid_max_time  = SucomUtil::get_const( 'WPSSO_SCHEMA_PRODUCT_VALID_MAX_TIME', MONTH_IN_SECONDS );

				/**
				 * Only define once for all offers to allow for (maybe) a common value in the AggregateOffer markup.
				 */
				static $price_valid_until = null;

				if ( null === $price_valid_until ) {

					$price_valid_until = gmdate( 'c', time() + $valid_max_time );
				}

				$offer[ 'priceValidUntil' ] = $price_valid_until;
			}

			$quantity = WpssoSchema::get_data_itemprop_from_assoc( $mt_offer, array( 
				'value'    => 'product:quantity:value',
				'minValue' => 'product:quantity:minimum',
				'maxValue' => 'product:quantity:maximum',
				'unitCode' => 'product:quantity:unit_code',
				'unitText' => 'product:quantity:unit_text',
			) );

			if ( false !== $quantity ) {

				$offer[ 'eligibleQuantity' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/QuantitativeValue', $quantity );
			}

			$price_spec = WpssoSchema::get_data_itemprop_from_assoc( $mt_offer, array( 
				'price'                 => 'product:price:amount',
				'priceCurrency'         => 'product:price:currency',
				'validFrom'             => 'product:sale_price_dates:start',
				'validThrough'          => 'product:sale_price_dates:end',
				'valueAddedTaxIncluded' => 'product:price:vat_included',
			) );

			if ( false !== $price_spec ) {

				if ( isset( $offer[ 'eligibleQuantity' ] ) ) {

					$price_spec[ 'eligibleQuantity' ] = $offer[ 'eligibleQuantity' ];
				}

				$offer[ 'priceSpecification' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/PriceSpecification', $price_spec );
			}

			/**
			 * Add the seller organization data.
			 */
			self::add_organization_data( $offer[ 'seller' ], $mod, 'site', 'org_logo_url', false );

			return WpssoSchema::get_schema_type_context( 'https://schema.org/Offer', $offer );
		}

		/**
		 * $org_id can be 'none', 'site', or a number (including 0).
		 *
		 * $org_logo_key can be 'org_logo_url' or 'org_banner_url' (600x60px image) for Articles.
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
			$org_opts = apply_filters( $wpsso->lca . '_get_organization_options', false, $mod, $org_id );

			if ( empty( $org_opts ) ) {

				if ( 'site' === $org_id ) {

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
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $org_type_id, $org_type_url ) = self::get_type_id_url( $json_data,
				$org_opts, $opt_key = 'org_schema_type', $def_type_id = 'organization', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $org_type_url );

			/**
			 * Set the reference values for admin notices.
			 */
			if ( is_admin() ) {

				$sharing_url = $wpsso->util->get_sharing_url( $mod );

				$wpsso->notice->set_ref( $sharing_url, $mod, __( 'adding schema organization', 'wpsso' ) );
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
			 * Organization image.
			 *
			 * Google requires a local business to have an image.
			 */
			$org_image_key = 'org_logo_url';

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'adding image from ' . $org_image_key . ' option' );
			}

			if ( ! empty( $org_opts[ $org_image_key ] ) ) {

				self::add_image_data_mt( $json_ret[ 'image' ], $org_opts, $org_image_key );
			}

			/**
			 * Organization logo.
			 *
			 * $org_logo_key can be false, 'org_logo_url' (default), or 'org_banner_url' (600x60px image) for Articles.
			 */
			if ( ! empty( $org_logo_key ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'adding logo from ' . $org_logo_key . ' option' );
				}

				if ( ! empty( $org_opts[ $org_logo_key ] ) ) {

					self::add_image_data_mt( $json_ret[ 'logo' ], $org_opts, $org_logo_key, false );	// $list_element is false.
				}

				if ( ! $mod[ 'is_post' ] || $mod[ 'post_status' ] === 'publish' ) {

					if ( empty( $json_ret[ 'logo' ] ) ) {

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
							$banner_missing_msg = __( 'An organization banner image (600x60px) is missing and required for the "%1$s" organization Schema %2$s markup.', 'wpsso' );

							// translators: %1$s is the organization name, %2$s is 'site' (translated) or 'ID #'.
							$org_settings_msg   = __( 'Please enter the missing image URL in the "%1$s" %2$s organization settings.', 'wpsso' );

							if ( 'org_logo_url' === $org_logo_key ) {

								$notice_msg = sprintf( $logo_missing_msg, $json_ret[ 'name' ], $org_type_url );

							} elseif ( 'org_banner_url' === $org_logo_key ) {

								$notice_msg = sprintf( $banner_missing_msg, $json_ret[ 'name' ], $org_type_url );

							} else {

								$notice_msg = '';
							}

							if ( $notice_msg ) {

								if ( 'site' === $org_id ) {

									$general_page_link = $wpsso->util->get_admin_url( 'essential#sucom-tabset_essential-tab_google' );

									// translators: site, as in the "site organization settings".
									$org_id_transl = __( 'site', 'wpsso' );

									$notice_msg .= ' <a href="' . $general_page_link . '">';
									$notice_msg .= sprintf( $org_settings_msg, $json_ret[ 'name' ], $org_id_transl );
									$notice_msg .= '</a>';

								} elseif ( ! empty( $wpsso->avail[ 'p_ext' ][ 'org' ] ) ) {

									$org_page_link = $wpsso->util->get_admin_url( 'org-general#sucom-tabset_org-tab_other_organizations' );

									$notice_msg .= ' <a href="' . $org_page_link . '">';
									$notice_msg .= sprintf( $org_settings_msg, $json_ret[ 'name' ], 'ID #' . $org_id );
									$notice_msg .= '</a>';

								} else {

									$notice_msg .= ' ';
									$notice_msg .= sprintf( $org_settings_msg, $json_ret[ 'name' ], 'ID #' . $org_id );
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
					 * 'plm_place_id' can be 'none', 'custom', or numeric (including 0).
					 */
					if ( ! empty( $mod[ 'obj' ] ) ) {

						$place_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'plm_place_id' );

					} else {

						$place_id = null;
					}

					if ( null === $place_id ) {

						$place_id = $org_opts[ 'org_place_id' ];

					} else {

						if ( $wpsso->debug->enabled ) {

							$wpsso->debug->log( 'overriding org_place_id ' . $org_opts[ 'org_place_id' ] . ' with plm_place_id ' . $place_id );
						}
					}

					self::add_place_data( $json_ret[ 'location' ], $mod, $place_id, false );	// $list_element is false.
				}
			}

			/**
			 * Google's knowledge graph.
			 */
			$org_opts[ 'org_sameas' ] = isset( $org_opts[ 'org_sameas' ] ) ? $org_opts[ 'org_sameas' ] : array();

			$org_opts[ 'org_sameas' ] = apply_filters( $wpsso->lca . '_json_data_single_organization_sameas', $org_opts[ 'org_sameas' ], $mod, $org_id );

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
			if ( ! empty( $org_type_id ) && $org_type_id !== 'organization' && 
				$wpsso->schema->is_schema_type_child( $org_type_id, 'local.business' ) ) {

				WpssoSchema::organization_to_localbusiness( $json_ret );
			}

			$json_ret = apply_filters( $wpsso->lca . '_json_data_single_organization', $json_ret, $mod, $org_id );

			/**
			 * Update the @id string based on $json_ret[ 'url' ], $org_type_id, and $org_id values.
			 */
			WpssoSchema::update_data_id( $json_ret, $org_type_id . '/' . $org_id . '/' . $org_logo_key );

			/**
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {

				$wpsso->notice->unset_ref( $sharing_url );
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
			$person_opts = apply_filters( $wpsso->lca . '_get_person_options', false, $mod, $person_id );
			$sharing_url = '';

			if ( empty( $person_opts ) ) {

				if ( empty( $person_id ) || $person_id === 'none' ) {

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

					$local_cache_person_urls[ $person_id ] = $wpsso->util->get_sharing_url( $user_mod );

					/**
					 * Set the reference values for admin notices.
					 */
					if ( is_admin() ) {

						$wpsso->notice->set_ref( $local_cache_person_urls[ $person_id ], $user_mod,
							sprintf( __( 'adding schema for person user ID %1$s', 'wpsso' ), $person_id ) );
					}

					$user_desc = $user_mod[ 'obj' ]->get_options_multi( $person_id, $md_key = array( 'schema_desc', 'seo_desc', 'og_desc' ) );

					if ( empty( $user_desc ) ) {

						$user_desc = $user_mod[ 'obj' ]->get_author_meta( $person_id, 'description' );
					}

					/**
					 * Remove shortcodes, strip html, etc.
					 */
					$user_desc = $wpsso->util->cleanup_html_tags( $user_desc );
	
					$user_sameas = array();
	
					foreach ( WpssoUser::get_user_id_contact_methods( $person_id ) as $cm_id => $cm_label ) {
	
						$url = $user_mod[ 'obj' ]->get_author_meta( $person_id, $cm_id );
	
						if ( empty( $url ) ) {
	
							continue;
	
						} elseif ( $cm_id === $wpsso->options[ 'plugin_cm_twitter_name' ] ) {	// Convert twitter name to url.
	
							$url = 'https://twitter.com/' . preg_replace( '/^@/', '', $url );
						}
	
						if ( false !== filter_var( $url, FILTER_VALIDATE_URL ) ) {
	
							$user_sameas[] = $url;
						}
					}
	
					$local_cache_person_opts[ $person_id ] = array(
						'person_type'      => 'person',
						'person_url'       => $user_mod[ 'obj' ]->get_author_website( $person_id, 'url' ),	// Returns a single URL string.
						'person_name'      => $user_mod[ 'obj' ]->get_author_meta( $person_id, $wpsso->options[ 'seo_author_name' ] ),
						'person_desc'      => $user_desc,
						'person_job_title' => $user_mod[ 'obj' ]->get_options( $person_id, 'schema_person_job_title' ),
						'person_og_image'  => $user_mod[ 'obj' ]->get_og_images( $num = 1, $size_names = 'schema', $person_id, false ),
						'person_sameas'    => $user_sameas,
					);

					/**
					 * Restore previous reference values for admin notices.
					 */
					if ( is_admin() ) {
	
						$wpsso->notice->unset_ref( $local_cache_person_urls[ $person_id ] );
					}
				}

				$person_opts = $local_cache_person_opts[ $person_id ];
				$sharing_url = $local_cache_person_urls[ $person_id ];
			}

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log_arr( 'person options', $person_opts );
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $person_type_id, $person_type_url ) = self::get_type_id_url( $json_data,
				$person_opts, $opt_key = 'person_type', $def_type_id = 'person', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $person_type_url );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $person_opts, array(
				'url'         => 'person_url',
				'name'        => 'person_name',
				'description' => 'person_desc',
				'jobTitle'    => 'person_job_title',
				'email'       => 'person_email',
				'telephone'   => 'person_phone',
			) );

			/**
			 * Images.
			 */
			if ( ! empty( $person_opts[ 'person_og_image' ] ) ) {

				WpssoSchema::add_images_data_mt( $json_ret[ 'image' ], $person_opts[ 'person_og_image' ] );
			}

			/**
			 * Google's knowledge graph.
			 */
			$person_opts[ 'person_sameas' ] = isset( $person_opts[ 'person_sameas' ] ) ? $person_opts[ 'person_sameas' ] : array();

			$person_opts[ 'person_sameas' ] = apply_filters( $wpsso->lca . '_json_data_single_person_sameas', $person_opts[ 'person_sameas' ], $mod, $person_id );

			if ( ! empty( $person_opts[ 'person_sameas' ] ) && is_array( $person_opts[ 'person_sameas' ] ) ) {	// Just in case.

				foreach ( $person_opts[ 'person_sameas' ] as $url ) {

					if ( ! empty( $url ) ) {	// Just in case.

						$json_ret[ 'sameAs' ][] = SucomUtil::esc_url_encode( $url );
					}
				}
			}

			$json_ret = apply_filters( $wpsso->lca . '_json_data_single_person', $json_ret, $mod, $person_id );

			/**
			 * Update the '@id' string based on the $sharing_url and the $person_type_id. Encode the URL part of the
			 * '@id' string to hide the WordPress login username.
			 */
			WpssoSchema::update_data_id( $json_ret, $person_type_id, $sharing_url, $hash_url = true );

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
			$place_opts = apply_filters( $wpsso->lca . '_get_place_options', false, $mod, $place_id );

			$place_opts = WpssoUtil::complete_type_options( $place_opts, $mod, array( 'place' => 'schema_place' ) );

			if ( empty( $place_opts ) ) {

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log( 'exiting early: no place options' );
				}

				return 0;
			}

			/**
			 * If not adding a list element, inherit the existing schema type url (if one exists).
			 */
			list( $place_type_id, $place_type_url ) = self::get_type_id_url( $json_data,
				$place_opts, $opt_key = 'place_schema_type', $def_type_id = 'place', $list_element );

			$json_ret = WpssoSchema::get_schema_type_context( $place_type_url );

			/**
			 * Set reference values for admin notices.
			 */
			if ( is_admin() ) {

				$sharing_url = $wpsso->util->get_sharing_url( $mod );

				$wpsso->notice->set_ref( $sharing_url, $mod, __( 'adding schema place', 'wpsso' ) );
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
				'currenciesAccepted' => 'place_currencies_accepted',
				'paymentAccepted'    => 'place_payment_accepted',
				'priceRange'         => 'place_price_range',
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
				'addressRegion'       => 'place_state',
				'postalCode'          => 'place_zipcode',
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
			$opening_spec = array();

			foreach ( $wpsso->cf[ 'form' ][ 'weekdays' ] as $weekday => $label ) {

				if ( ! empty( $place_opts[ 'place_day_' . $weekday ] ) ) {

					$open_close = SucomUtil::get_open_close(
						$place_opts,
						'place_day_' . $weekday . '_open',
						'place_midday_close',
						'place_midday_open',
						'place_day_' . $weekday . '_close'
					);

					foreach ( $open_close as $open => $close ) {

						$weekday_spec = array(
							'@context'  => 'https://schema.org',
							'@type'     => 'OpeningHoursSpecification',
							'dayOfWeek' => $label,
							'opens'     => $open,
							'closes'    => $close,
						);

						foreach ( array(
							'validFrom'    => 'place_season_from_date',
							'validThrough' => 'place_season_to_date',
						) as $prop_name => $opt_key ) {

							if ( isset( $place_opts[ $opt_key ] ) && $place_opts[ $opt_key ] !== '' ) {

								$weekday_spec[ $prop_name ] = $place_opts[ $opt_key ];
							}
						}

						$opening_spec[] = $weekday_spec;
					}
				}
			}

			if ( ! empty( $opening_spec ) ) {

				$json_ret[ 'openingHoursSpecification' ] = $opening_spec;
			}

			/**
			 * FoodEstablishment schema type properties
			 */
			if ( ! empty( $place_opts[ 'place_schema_type' ] ) && $place_opts[ 'place_schema_type' ] !== 'none' ) {

				if ( $wpsso->schema->is_schema_type_child( $place_opts[ 'place_schema_type' ], 'food.establishment' ) ) {

					foreach ( array(
						'acceptsReservations' => 'place_accept_res',
						'hasMenu'             => 'place_menu_url',
						'servesCuisine'       => 'place_cuisine',
					) as $prop_name => $opt_key ) {

						if ( $opt_key === 'place_accept_res' ) {

							$json_ret[ $prop_name ] = empty( $place_opts[ $opt_key ] ) ? 'false' : 'true';

						} elseif ( isset( $place_opts[ $opt_key ] ) ) {

							$json_ret[ $prop_name ] = $place_opts[ $opt_key ];
						}
					}
				}
			}

			if ( ! empty( $place_opts[ 'place_order_urls' ] ) ) {

				foreach ( SucomUtil::explode_csv( $place_opts[ 'place_order_urls' ] ) as $order_url ) {

					if ( ! empty( $order_url ) ) {	// Just in case.

						$json_ret[ 'potentialAction' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/OrderAction', array(
							'target'   => $order_url,
						) );
					}
				}
			}

			/**
			 * Image.
			 */
			if ( ! empty( $place_opts[ 'place_img_id' ] ) || ! empty( $place_opts[ 'place_img_url' ] ) ) {

				$mt_images = $wpsso->media->get_mt_opts_images( $place_opts, $size_names = 'schema', $img_pre = 'place_img' );

				WpssoSchema::add_images_data_mt( $json_ret[ 'image' ], $mt_images );
			}

			$json_ret = apply_filters( $wpsso->lca . '_json_data_single_place', $json_ret, $mod, $place_id );

			/**
			 * Update the @id string based on $json_ret[ 'url' ], $place_type_id, and $place_id values.
			 */
			WpssoSchema::update_data_id( $json_ret, $place_type_id . '/' . $place_id );

			/**
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {
				$wpsso->notice->unset_ref( $sharing_url );
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
		 * If not adding a list element, then inherit the existing schema type url (if one exists).
		 */
		public static function get_type_id_url( $json_data, $type_opts, $opt_key, $def_type_id, $list_element = false ) {

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
				if ( empty( $type_opts[ $opt_key ] ) || $type_opts[ $opt_key ] === 'none' ) {

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
