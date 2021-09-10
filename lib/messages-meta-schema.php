<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoMessagesMetaSchema' ) ) {

	class WpssoMessagesMetaSchema extends WpssoMessages {

		protected $p;	// Wpsso class object.

		/**
		 * Instantiated by WpssoMessagesMeta->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function get( $msg_key = false, $info = array() ) {

			$text = '';

			switch ( $msg_key ) {

				case 'tooltip-meta-schema_img_id':	// Image ID.

					$text = __( 'A customized image ID to include first in the Schema meta tags and JSON-LD markup.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image URL is entered.', 'wpsso' ) . '</em>';

				 	break;

				case 'tooltip-meta-schema_img_url':	// or an Image URL.

					$text = __( 'A customized image URL (instead of an image ID) to include first in the Schema meta tags and JSON-LD markup.', 'wpsso' ) . ' ';

					$text .= '<em>' . __( 'This option is disabled if a custom image ID is selected.', 'wpsso' ) . '</em>';

				 	break;

				default:

					$text = apply_filters( 'wpsso_messages_tooltip_meta_schema', $text, $msg_key, $info );

					break;

			}	// End of tooltip-meta-schema switch.

			return $text;
		}
	}
}
