<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoEditValidators' ) ) {

	class WpssoEditValidators {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * See WpssoAbstractWpMeta->get_document_meta_tabs().
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'metabox_sso_validators_rows' => 4,
			), PHP_INT_MIN );	// Run before any add-on filters.
		}

		public function filter_metabox_sso_validators_rows( $table_rows, $form, $head_info, $mod ) {

			$validators = $this->p->util->get_validators( $mod, $form );

			foreach ( $validators as $key => $el ) {

				if ( empty( $el[ 'type' ] ) ) {

					continue;
				}

				$extra_msg    = isset( $el[ 'extra_msg' ] ) ? $el[ 'extra_msg' ] : '';
				$button_label = sprintf( _x( 'Validate %s', 'submit button', 'wpsso' ), $el[ 'type' ] );
				$is_disabled  = empty( $el[ 'url' ] ) ? true : false;

				$table_rows[ 'validate_' . $key ] = '' .
					$form->get_th_html( $el[ 'title' ], $css_class = 'medium' ) .
					'<td class="validate">' . $this->p->msgs->get( 'info-meta-validate-' . $key ) . $extra_msg . '</td>' .
					'<td class="validate">' . $form->get_button( $button_label, $css_class = 'button-secondary', $css_id = '',
						$el[ 'url' ], $newtab = true, $is_disabled ) . '</td>';
			}

			$table_rows[ 'validate_info' ] = '<td class="validate" colspan="3">' . $this->p->msgs->get( 'info-meta-validate-footer' ) . '</td>';

			return $table_rows;
		}
	}
}
