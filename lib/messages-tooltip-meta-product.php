<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

/*
 * Since WPSSO Core v13.5.0.
 */
if ( ! class_exists( 'WpssoMessagesTooltipMetaProduct' ) ) {

	/*
	 * Instantiated by WpssoMessagesTooltipMeta->get() only when needed.
	 */
	class WpssoMessagesTooltipMetaProduct extends WpssoMessages {

		public function get( $msg_key = false, $info = array() ) {

			$this->maybe_set_properties();

			$text = '';

			switch ( $msg_key ) {

				/*
				 * Document SSO > Edit Schema tab.
				 */
				case 'tooltip-meta-product_award':	// Product Awards.

					$text = __( 'One or more awards this product has won.', 'wpsso' );

				 	break;

				case ( 0 === strpos( $msg_key, 'tooltip-meta-product_' ) ? true : false ):

					$tp_frags = $this->get_tooltip_fragments( preg_replace( '/^tooltip-meta-/', '', $msg_key ) );	// Uses a local cache.

					if ( ! empty( $tp_frags ) ) {	// Just in case.

						$option_link = '';

						if ( ! empty( $tp_frags[ 'opt_menu_id' ] ) && ! empty( $tp_frags[ 'opt_label' ] ) ) {

							$option_link = $this->p->util->get_admin_url( $tp_frags[ 'opt_menu_id' ], $tp_frags[ 'opt_label' ] );

							// translators: %1$s is a lower case item name, for example 'product Google category'.
							$text = sprintf( __( 'A custom value for the %1$s, which may be different than the %2$s option value.', 'wpsso' ), $tp_frags[ 'name' ], $option_link ) . ' ';

							$text .= sprintf( __( 'Select "[None]" to exclude the %s from Schema markup and meta tags.', 'wpsso' ), $tp_frags[ 'name' ] ) . ' ';

						} else {

							// translators: %s is a singular item reference, for example 'a product size group'.
							$text = sprintf( __( 'A custom value for the %s can be provided for the main product.', 'wpsso' ), $tp_frags[ 'name' ] ) . ' ';

							$text .= __( 'If product variations are available, the information from each variation may supersede this value in Schema product offers.', 'wpsso' ) . ' ';

							// translators: %s is the option label.
							$text .= sprintf( __( 'The <strong>%s</strong> option may be read-only if a custom field or e-commerce plugin is the authoritative source for this value.', 'wpsso' ), $tp_frags[ 'label' ] ) . ' ';

							$text .= __( 'In this case, you should update the product information in the custom field or e-commerce plugin to update this value.', 'wpsso' ) . ' ';
						}

						if ( ! empty( $tp_frags[ 'about' ] ) ) {

							// translators: %1$s is a webpage URL and %2$s is a singular item reference, for example 'a product size'.
							$text .= sprintf( __( '<a href="%1$s">See this webpage for more information about choosing %2$s value</a>.', 'wpsso' ), $tp_frags[ 'about' ], $tp_frags[ 'desc' ] ) . ' ';
						}

						if ( ! empty( $tp_frags[ 'inherit' ] ) ) {

							$text .= sprintf( __( 'Note that the %s value is automatically inherited as a default value by any child pages (like WooCommerce product variations, for example).', 'wpsso' ), $tp_frags[ 'name' ] ) . ' ';
						}
					}

				 	break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta_product', $text, $msg_key, $info );

					break;

			}	// End of 'tooltip-meta-schema' switch.

			return $text;
		}
	}
}
