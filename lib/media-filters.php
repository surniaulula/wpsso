<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoMediaFilters' ) ) {

	/*
	 * Since WPSSO Core v13.2.0.
	 */
	class WpssoMediaFilters {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by WpssoMedia->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Filters for the "Image Dimension Checks" option.
			 */
			if ( $this->p->options[ 'plugin_check_img_dims' ] ) {

				$this->p->util->add_plugin_filters( $this, array(
					'attached_accept_img_dims' => 6,
					'content_accept_img_dims'  => 6,
				) );
			}
		}

		/*
		 * $size_name must be a string.
		 */
		public function filter_attached_accept_img_dims( $accept, $img_url, $img_width, $img_height, $size_name, $pid ) {

			/*
			 * Don't re-check already rejected images.
			 */
			if ( ! $accept ) {	// Value is false.

				return false;
			}

			if ( 0 !== strpos( $size_name, 'wpsso-' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . $size_name . ' not a wpsso custom image size' );
				}

				return $accept;
			}

			$size_info       = $this->p->util->get_size_info( $size_name, $pid );
			$is_sufficient_w = $img_width >= $size_info[ 'width' ] ? true : false;
			$is_sufficient_h = $img_height >= $size_info[ 'height' ] ? true : false;

			if ( ( ! $size_info[ 'is_cropped' ] && ( $is_sufficient_w || $is_sufficient_h ) ) ||
				( $size_info[ 'is_cropped' ] && ( $is_sufficient_w && $is_sufficient_h ) ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'image ID ' . $pid . ' dimensions (' . $img_width . 'x' . $img_height . ') are sufficient' );
				}

				return true;	// Image dimensions are sufficient.
			}

			$img_meta = wp_get_attachment_metadata( $pid );	// Returns a WP_Error object on failure.

			if ( is_array( $img_meta ) && isset( $img_meta[ 'width' ] ) && isset( $img_meta[ 'height' ] ) &&
				$img_meta[ 'width' ] < $size_info[ 'width' ] && $img_meta[ 'height' ] < $size_info[ 'height' ] ) {

				$img_dims        = $img_meta[ 'width' ] . 'x' . $img_meta[ 'height' ];
				$img_dims_transl = $img_dims . ' (' . __( 'full size original', 'wpsso' ) . ')';

			} else {

				$img_dims        = $img_width . 'x' . $img_height;
				$img_dims_transl = $img_dims;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'image ID ' . $pid . ' rejected - ' . $img_dims .
					' too small for the ' . $size_name . ' (' . $size_info[ 'dims_transl' ] . ') image size' );
			}

			/*
			 * Add notice only if the admin notices have not already been shown.
			 *
			 * An is_admin() test is required to make sure the WpssoMessages class is available.
			 */
			if ( $this->p->notice->is_admin_pre_notices() ) {

				$img_lib      = __( 'Media Library', 'wpsso' );
				$img_edit_url = get_edit_post_link( $pid );
				$img_title    = get_the_title( $pid );
				$img_label    = sprintf( __( 'image ID %1$s (%2$s)', 'wpsso' ), $pid, $img_title );
				$img_label    = empty( $img_edit_url ) ? $img_label : '<a href="' . $img_edit_url . '">' . $img_label . '</a>';

				$notice_msg = sprintf( __( '%1$s %2$s ignored - the resulting resized image of %3$s is too small for the required %4$s image dimensions.',
					'wpsso' ), $img_lib, $img_label, $img_dims_transl, '<b>' . $size_info[ 'label_transl' ] . '</b> (' . $size_info[ 'dims_transl' ] . ')' ) .
						' ' . $this->p->msgs->get( 'notice-image-rejected' );

				$notice_key = 'wp_' . $pid . '_' . $img_dims . '_' . $size_name . '_' . $size_info[ 'dims_transl' ] . '_rejected';

				$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
			}

			return false;
		}

		/*
		 * $size_name must be a string.
		 */
		public function filter_content_accept_img_dims( $accept, $og_image, $size_name, $attr_name, $content_passed ) {

			/*
			 * Don't re-check already rejected images.
			 */
			if ( ! $accept ) {	// Value is false.

				return false;
			}

			if ( 0 !== strpos( $size_name, 'wpsso-' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . $size_name . ' not a wpsso custom image size' );
				}

				return $accept;
			}

			$size_info       = $this->p->util->get_size_info( $size_name );
			$is_sufficient_w = $og_image[ 'og:image:width' ] >= $size_info[ 'width' ] ? true : false;
			$is_sufficient_h = $og_image[ 'og:image:height' ] >= $size_info[ 'height' ] ? true : false;
			$image_url       = SucomUtil::get_first_mt_media_url( $og_image );

			if ( ( $attr_name == 'src' && ! $size_info[ 'is_cropped' ] && ( $is_sufficient_w || $is_sufficient_h ) ) ||
				( $attr_name == 'src' && $size_info[ 'is_cropped' ] && ( $is_sufficient_w && $is_sufficient_h ) ) ||
					$attr_name == 'data-share-src' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $image_url . ' dimensions (' . $og_image[ 'og:image:width' ] . 'x' .
						$og_image[ 'og:image:height' ] . ') are sufficient' );
				}

				return true;	// Image dimensions are sufficient.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'content image rejected: width / height missing or too small for ' . $size_name );
			}

			/*
			 * Add notice only if the admin notices have not already been shown.
			 */
			if ( $this->p->notice->is_admin_pre_notices() ) {

				$notice_msg = sprintf( __( 'Image %1$s in content ignored - the image width and height is too small for the required %2$s image dimensions.',
					'wpsso' ), $image_url, '<b>' . $size_info[ 'label_transl' ] . '</b> (' . $size_info[ 'dims_transl' ] . ')' );

				$notice_msg .= $content_passed ? '' : ' ' . sprintf( __( '%1$s includes an additional \'data-wp-pid\' attribute for WordPress Media Library images - if this image was selected from the Media Library before %1$s was activated, try removing and adding the image back to your content.',
					'wpsso' ), $this->p->cf[ 'plugin' ][ $this->p->id ][ 'short' ] );

				$notice_key = 'content_' . $image_url . '_' . $size_name . '_rejected';

				$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
			}

			return false;
		}
	}
}
