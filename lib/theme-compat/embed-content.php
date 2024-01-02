<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 *
 * When an existing post is embedded in an iframe, this template is used to create the content output.
 *
 * Required by the WpssoOembed->template_part_embed() method.
 *
 * See wordpress/wp-includes/theme-compat/embed-content.php.
 */

$embed_class     = esc_attr( implode( ' ', get_post_class( 'wpsso-embed wp-embed' ) ) );
$thumbnail_url   = apply_filters( 'embed_thumbnail_url', '' );
$thumbnail_id    = 0;
$thumbnail_shape = 'rectangular';
$thumbnail_img   = '';
$permalink       = get_permalink();

if ( $thumbnail_url ) {

	$thumbnail_shape = apply_filters( 'embed_thumbnail_url_image_shape', $thumbnail_shape, $thumbnail_url );

} else {

	if ( has_post_thumbnail() ) {

		$thumbnail_id = get_post_thumbnail_id();
	}

	if ( 'attachment' === get_post_type() && wp_attachment_is( 'image' ) ) {

		$thumbnail_id = get_the_ID();
	}

	/*
	 * Filters the thumbnail image ID for use in the embed template.
	 *
	 * @since 4.9.0
	 *
	 * @param int $thumbnail_id Attachment ID.
	 */
	$thumbnail_id = apply_filters( 'embed_thumbnail_id', $thumbnail_id );

	if ( $thumbnail_id ) {

		$aspect_ratio = 1;
		$measurements = array( 1, 1 );
		$image_size   = 'full';	// Fallback.
		$img_meta     = wp_get_attachment_metadata( $thumbnail_id );	// Returns a WP_Error object on failure.

		if ( is_array( $img_meta ) ) {

			if ( ! empty( $img_meta[ 'sizes' ] ) ) {

				foreach ( $img_meta[ 'sizes' ] as $size => $data ) {

					if ( $data[ 'height' ] > 0 && $data[ 'width' ] / $data[ 'height' ] > $aspect_ratio ) {

						$aspect_ratio = $data[ 'width' ] / $data[ 'height' ];
						$measurements = array( $data[ 'width' ], $data[ 'height' ] );
						$image_size   = $size;
					}
				}
			}
		}

		/*
		 * Filters the thumbnail image size for use in the embed template.
		 *
		 * @since 4.4.0
		 * @since 4.5.0 Added `$thumbnail_id` parameter.
		 *
		 * @param string $image_size   Thumbnail image size.
		 * @param int    $thumbnail_id Attachment ID.
		 */
		$image_size = apply_filters( 'embed_thumbnail_image_size', $image_size, $thumbnail_id );

		$thumbnail_shape = $measurements[ 0 ] / $measurements[ 1 ] >= 1.75 ? 'rectangular' : 'square';

		/*
		 * Filters the thumbnail shape for use in the embed template.
		 *
		 * Rectangular images are shown above the title while square images
		 * are shown next to the content.
		 *
		 * @since 4.4.0
		 * @since 4.5.0 Added `$thumbnail_id` parameter.
		 *
		 * @param string $thumbnail_shape Thumbnail image shape. Either 'rectangular' or 'square'.
		 * @param int    $thumbnail_id    Attachment ID.
		 */
		$thumbnail_shape = apply_filters( 'embed_thumbnail_image_shape', $thumbnail_shape, $thumbnail_id );
	}
}

if ( $thumbnail_url ) {

	$loading = wp_lazy_loading_enabled( 'img', 'wp_get_attachment_image' ) ? 'loading="lazy"' : '';

	$thumbnail_img = '<img src="' . $thumbnail_url . '" ' . $loading . '>' ;

} elseif ( $thumbnail_id ) {

	$thumbnail_img = wp_get_attachment_image( $thumbnail_id, $image_size );
}

echo '<div class="' . $embed_class . '">' . "\n";

if ( $thumbnail_img && 'rectangular' === $thumbnail_shape ) {

	echo '<div class="wp-embed-featured-image rectangular">';

	echo '<a href="' . $permalink . '" target="_top">' . $thumbnail_img . '</a>';

	echo '</div><!-- .wp-embed-featured-image.rectangular -->' . "\n";
}

echo '<p class="wp-embed-heading">';

echo '<a href="' . $permalink . '" target="_top">';

the_title();

echo ' </a>';

echo '</p><!-- .wp-embed-heading -->' . "\n";

if ( $thumbnail_img && 'square' === $thumbnail_shape ) {

	echo '<div class="wp-embed-featured-image square">';

	echo '<a href="' . $permalink . '" target="_top">' . $thumbnail_img . '</a>';

	echo '</div><!-- .wp-embed-featured-image.square -->' . "\n";
}

echo '<div class="wp-embed-excerpt">';

the_excerpt_embed();

echo '</div><!-- wp-embed-excerpt -->' . "\n";

/*
 * Prints additional content after the embed excerpt.
 *
 * @since 4.4.0
 */
do_action( 'embed_content' );

echo '<div class="wp-embed-footer">';

the_embed_site_title();

echo '<div class="wp-embed-meta">';

/*
 * Prints additional meta content in the embed template.
 *
 * @since 4.4.0
 */
do_action( 'embed_content_meta' );

echo '</div><!-- .wp-embed-meta -->' . "\n";

echo '</div><!-- .wp-embed-footer -->' . "\n";

echo '</div><!-- .wpsso-embed .wp-embed -->' . "\n";
