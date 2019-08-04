<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomForm' ) ) {

	class SucomForm {

		private $p;
		private $lca;
		private $opts_name       = null;
		private $menu_ext        = null;	// Lca or ext lowercase acronym.
		private $text_domain     = false;	// Lca or ext text domain.
		private $def_text_domain = false;	// Lca text domain (fallback).

		public $options  = array();
		public $defaults = array();

		public function __construct( &$plugin, $opts_name, &$opts, &$def_opts, $menu_ext = '' ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log( 'form options name is ' . $opts_name );
			}

			$this->lca       = $this->p->lca;
			$this->opts_name =& $opts_name;
			$this->options   =& $opts;
			$this->defaults  =& $def_opts;
			$this->menu_ext  = empty( $menu_ext ) ? $this->lca : $menu_ext;	// Lca or ext lowercase acronym.

			$this->set_text_domain( $this->menu_ext );

			$this->set_default_text_domain( $this->lca );
		}

		public function get_options_name() {

			return $this->opts_name;
		}

		public function get_menu_ext() {

			return $this->menu_ext;
		}

		public function get_text_domain() {

			return $this->text_domain;
		}

		public function get_default_text_domain() {

			return $this->def_text_domain;
		}

		public function set_text_domain( $maybe_ext ) {

			$this->text_domain = $this->get_plugin_text_domain( $maybe_ext );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'form text domain set to ' . $this->text_domain );
			}
		}

		public function set_default_text_domain( $maybe_ext ) {

			$this->def_text_domain = $this->get_plugin_text_domain( $maybe_ext );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'form default text domain set to ' . $this->def_text_domain );
			}
		}

		public function get_plugin_text_domain( $maybe_ext ) {

			return isset( $this->p->cf[ 'plugin' ][ $maybe_ext ][ 'text_domain' ] ) ?
				$this->p->cf[ 'plugin' ][ $maybe_ext ][ 'text_domain' ] : $maybe_ext;
		}

		public function get_value_transl( $value ) {

			if ( $this->text_domain ) {	// Just in case.

				$value_transl = _x( $value, 'option value', $this->text_domain );	// Lca or ext text domain.

				if ( $value === $value_transl && $this->text_domain !== $this->def_text_domain ) {
					$value_transl = _x( $value, 'option value', $this->def_text_domain );	// Lca text domain.
				}

				return $value_transl;

			} elseif ( $this->def_text_domain ) {
				return _x( $value, 'option value', $this->def_text_domain );	// Lca text domain.
			}

			return $value;
		}

		/**
		 * Hidden input field.
		 */
		public function get_hidden( $name, $value = '', $is_checkbox = false ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			if ( empty( $value ) && $value !== 0 && $this->in_options( $name ) ) {
				$value = $this->options[ $name ];
			}

			$html = $is_checkbox ? $this->get_hidden( 'is_checkbox_' . $name, 1, false ) : '';
			$html .= '<input type="hidden" name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" ' .
				'value="' . esc_attr( $value ) . '" />' . "\n";

			return $html;
		}

		/**
		 * Checkbox input field.
		 */
		public function get_checkbox( $name, $css_class = '', $css_id = '',
			$is_disabled = false, $force = null, $group = null ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$is_disabled = true;
			}

			if ( $force !== null ) {
				$input_checked = checked( $force, 1, false );
			} elseif ( $this->in_options( $name ) ) {
				$input_checked = checked( $this->options[ $name ], 1, false );
			} elseif ( $this->in_defaults( $name ) ) {	// Returns true or false.
				$input_checked = checked( $this->defaults[ $name ], 1, false );
			} else {
				$input_checked = '';
			}

			$default_is = $this->in_defaults( $name ) && ! empty( $this->defaults[ $name ] ) ? 'checked' : 'unchecked';

			$title_transl = sprintf( $this->get_value_transl( 'default is %s' ), $this->get_value_transl( $default_is ) ) .
				( $is_disabled ? ' ' . $this->get_value_transl( '(option disabled)' ) : '' );

			$input_id = empty( $css_id ) ? 'checkbox_' . $name : 'checkbox_' . $css_id;

			$html = $is_disabled ? '' : $this->get_hidden( 'is_checkbox_' . $name, 1, false );
			$html .= '<input type="checkbox"';
			$html .= $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" value="1"';
			$html .= empty( $group ) ? '' : ' data-group="' . esc_attr( $group ) . '"';
			$html .= empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"';
			$html .= ' id="' . esc_attr( $input_id ) . '"' . $input_checked . ' title="' . $title_transl . '" />';

			return $html;
		}

		public function get_no_checkbox( $name, $css_class = '', $css_id = '', $force = null, $group = null ) {

			return $this->get_checkbox( $name, $css_class, $css_id,
				$is_disabled = true, $force, $group );
		}

		public function get_no_checkbox_options( $name, array $opts, $css_class = '', $css_id = '', $group = null ) {

			$force = empty( $opts[ $name ] ) ? 0 : 1;

			return $this->get_checkbox( $name, $css_class, $css_id, $is_disabled = true, $force, $group );
		}

		public function get_no_checkbox_comment( $name, $comment = '' ) {

			return $this->get_checkbox( $name, '', '', $is_disabled = true, null ) .
				( empty( $comment ) ? '' : ' ' . $comment );
		}

		public function get_td_no_checkbox( $name, $comment = '', $narrow = false ) {

			return '<td class="'.( $narrow ? 'checkbox ' : '' ) . 'blank">' . $this->get_no_checkbox_comment( $name, $comment ) . '</td>';
		}

		/**
		 * Creates a vertical list (by default) of checkboxes. The $name_prefix is 
		 * combined with the $values array names to create the checbox option name.
		 */
		public function get_checklist( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $is_assoc = null,
			$is_disabled = false ) {

			if ( empty( $name_prefix ) || ! is_array( $values ) ) {
				return;
			}

			if ( $this->get_options( $name_prefix . ':is' ) === 'disabled' ) {
				$is_disabled = true;
			}

			if ( null === $is_assoc ) {
				$is_assoc = SucomUtil::is_assoc( $values );
			}

			$input_id = empty( $css_id ) ? 'checklist_' . $name_prefix : 'checklist_' . $css_id;

			/**
			 * Use the "input_vertical_list" class to align the checbox input vertically.
			 */
			$html = '<div '.( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				' id="' . esc_attr( $input_id ) . '">' . "\n";

			foreach ( $values as $name_suffix => $label ) {

				if ( is_array( $label ) ) {	// Just in case.
					$label = implode( ', ', $label );
				}

				/**
				 * If the array is not associative (so a regular numbered array), 
				 * then the label / description is used as the saved value.
				 */
				if ( $is_assoc ) {
					$input_name = $name_prefix . '_' . $name_suffix;
				} else {
					$input_name = $name_prefix . '_' . $label;
				}

				if ( $this->get_options( $input_name . ':is' ) === 'disabled' ) {
					$input_disabled = true;
				} else {
					$input_disabled = $is_disabled;
				}

				if ( $this->text_domain ) {
					$label_transl = $this->get_value_transl( $label );
				}

				if ( $this->in_options( $input_name ) ) {
					$input_checked = checked( $this->options[ $input_name ], 1, false );
				} elseif ( $this->in_defaults( $input_name ) ) {	// Returns true or false.
					$input_checked = checked( $this->defaults[ $input_name ], 1, false );
				} else {
					$input_checked = '';
				}

				$default_is = $this->in_defaults( $input_name ) && ! empty( $this->defaults[ $input_name ] ) ? 'checked' : 'unchecked';

				$title_transl = sprintf( $this->get_value_transl( 'default is %s' ), $this->get_value_transl( $default_is ) ) .
					( $input_disabled ? ' ' . $this->get_value_transl( '(option disabled)' ) : '' );

				$html .= ( $input_disabled ? '' : $this->get_hidden( 'is_checkbox_' . $input_name, 1, false ) ) .
					'<span><input type="checkbox"' .
					( $input_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '" value="1"' ) .
					$input_checked . ' title="' . $title_transl . '"/>&nbsp;' . $label_transl . '&nbsp;&nbsp;</span>' . "\n";
			}

			$html .= '</div>' . "\n";

			return $html;
		}

		public function get_no_checklist( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $is_assoc = null ) {

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true );
		}

		public function get_checklist_post_types( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '',
			$is_disabled = false ) {

			foreach ( $this->p->util->get_post_types( 'objects' ) as $pt ) {
				$values[ $pt->name ] = $pt->label.( empty( $pt->description ) ? '' : ' (' . $pt->description . ')' );
			}

			asort( $values );	// Sort by label.

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc = true,
				$is_disabled );
		}

		public function get_no_checklist_post_types( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '' ) {

			return $this->get_checklist_post_types( $name_prefix, $values, $css_class, $css_id, $is_disabled = true );
		}

		/**
		 * Radio input field.
		 */
		public function get_radio( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $is_disabled = false ) {

			if ( empty( $name ) || ! is_array( $values ) ) {
				return;
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$is_disabled = true;
			}

			if ( null === $is_assoc ) {
				$is_assoc = SucomUtil::is_assoc( $values );
			}

			$input_id = empty( $css_id ) ? 'radio_' . $name : 'radio_' . $css_id;

			/**
			 * Use the "input_vertical_list" class to align the radio input buttons vertically.
			 */
			$html = '<div ' . ( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				' id="' . esc_attr( $input_id ) . '">' . "\n";

			foreach ( $values as $val => $label ) {

				if ( is_array( $label ) ) {	// Just in case.
					$label = implode( ', ', $label );
				}

				/**
				 * If the array is not associative (so a regular numbered array), 
				 * then the label / description is used as the saved value.
				 */
				if ( ! $is_assoc ) {
					$val = $label;
				}

				if ( $this->text_domain ) {
					$label_transl = $this->get_value_transl( $label );
				}

				$attr_name_value = ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" value="' . esc_attr( $val ) . '"';

				$html .= '<span><input type="radio"' .
					( $is_disabled ? ' disabled="disabled"' : $attr_name_value ) .
					( $this->in_options( $name ) ? checked( $this->options[ $name ], $val, false ) : '' ) .
					( $this->in_defaults( $name ) ? ' title="default is ' . $values[ $this->defaults[ $name ] ] . '"' : '' ) .
					'/>&nbsp;' . $label_transl . '&nbsp;&nbsp;</span>' . "\n";
			}

			$html .= '</div>' . "\n";

			return $html;
		}

		public function get_no_radio( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null ) {

			return $this->get_radio( $name, $values, $css_class, $css_id, $is_assoc, $is_disabled = true );
		}

		/**
		 * Select drop-down field.
		 */
		public function get_select( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$is_disabled = false, $selected = false, $event_name = false, $event_args = null ) {

			if ( empty( $name ) ) {
				return;
			}

			static $do_once_json_array   = array();	// Associative array by $json_key.
			static $do_once_show_hide_js = null;	// Null or true.

			$filter_name   = SucomUtil::sanitize_hookname( $this->lca . '_form_select_' . $name );

			$values = apply_filters( $this->lca . '_form_select_' . $filter_name, $values );

			if ( ! is_array( $values ) ) {
				return;
			}

			if ( null === $is_assoc ) {
				$is_assoc  = SucomUtil::is_assoc( $values );
			}

			if ( is_string( $is_disabled ) ) {
				$disabled_value = $is_disabled;
				$is_disabled    = false;
			} else {
				$disabled_value = false;
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$is_disabled = true;
			}

			/**
			 * We must have an $event_arg string to create the JSON array variable.
			 */
			if ( 'on_focus_load_json' === $event_name ) {
				if ( ! empty( $event_args ) && is_string( $event_args ) ) {
					$json_key = SucomUtil::sanitize_hookname( $this->lca . '_form_select_' . $event_args . '_json' );
				} else {
					$event_name = false;
				}
			}

			$html        = '';
			$tr_id       = empty( $css_id ) ? 'tr_' . $name : 'tr_' . $css_id;
			$input_id    = empty( $css_id ) ? 'select_' . $name : 'select_' . $css_id;
			$in_options  = $this->in_options( $name );	// Optimize and call only once - returns true or false.
			$in_defaults = $this->in_defaults( $name );	// Optimize and call only once - returns true or false.

			$select_opt_count = 0;	// Used to check for first option.
			$select_opt_added = 0;
			$select_opt_html  = '';
			$select_opt_arr   = array();
			$default_value    = '';
			$default_text     = '';

			foreach ( $values as $option_value => $label ) {

				if ( is_array( $label ) ) {	// Just in case.
					$label = implode( ', ', $label );
				}

				/**
				 * If the array is not associative (so a regular numbered array), 
				 * then the label / description is used as the saved value.
				 *
				 * Make sure option values are cast as strings for comparison.
				 */
				if ( $is_assoc ) {
					$option_value = (string) $option_value;
				} else {
					$option_value = (string) $label;
				}


				if ( $this->text_domain ) {
					$label_transl = $this->get_value_transl( $label );
				}

				switch ( $name ) {

					case 'og_img_max':

						if ( $label === 0 ) {
							$label_transl .= ' ' . $this->get_value_transl( '(no images)' );
						}

						break;

					case 'og_vid_max':

						if ( $label === 0 ) {
							$label_transl .= ' ' . $this->get_value_transl( '(no videos)' );
						}

						break;

					default:

						if ( $label === '' || $label === 'none' ) {	// Just in case.
							$label_transl = $this->get_value_transl( '[None]' );
						}

						break;
				}

				/**
				 * Save the option value and translated label for the JSON array before adding the
				 * "(default)" suffix.
				 */
				if ( 'on_focus_load_json' === $event_name ) {
					if ( empty( $do_once_json_array[ $json_key ] ) ) {
						$select_opt_arr[ $option_value ] = $label_transl;
					}
				}

				/**
				 * Save the default value and its text so we can add them (as jquery data) to the select.
				 */
				if ( $in_defaults && $option_value === (string) $this->defaults[ $name ] ) {

					$default_value = $option_value;
					$default_text  = $this->get_value_transl( '(default)' );

					$label_transl  .= ' ' . $default_text;
				}

				/**
				 * Maybe get a selected="selected" string for this option.
				 */
				if ( ! is_bool( $selected ) ) {
					$is_selected_html = selected( $selected, $option_value, false );
				} elseif ( $in_options ) {
					$is_selected_html = selected( $this->options[ $name ], $option_value, false );
				} elseif ( $in_defaults ) {
					$is_selected_html = selected( $this->defaults[ $name ], $option_value, false );
				} else {
					$is_selected_html = '';
				}

				$select_opt_count++;	// Used to check for first option.

				/**
				 * For disabled selects or JSON selects, only include the first and selected option(s).
				 */
				if ( $select_opt_count === 1 || $is_selected_html ||
					( ! $is_disabled && 'on_focus_load_json' !== $event_name ) ) {

					if ( false !== $disabled_value ) {
						$option_value = $disabled_value;
					}

					$select_opt_html .= '<option value="' . esc_attr( $option_value ) . '"' . $is_selected_html . '>';
					$select_opt_html .= $label_transl;
					$select_opt_html .= '</option>' . "\n";

					$select_opt_added++; 
				}
			}

			$html .= '<select id="' . esc_attr( $input_id ) . '"';
			$html .= ( $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' );
			$html .= ( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' );
			$html .= ( empty( $default_value ) ? '' : ' data-default-value="' . esc_attr( $default_value ) . '"' );
			$html .= ( empty( $default_text ) ? '' : ' data-default-text="' . esc_attr( $default_text ) . '"' );
			$html .= '>' . "\n";
			$html .= $select_opt_html;
			$html .= '<!-- ' . $select_opt_added . ' select options added -->' . "\n";
			$html .= '</select>' . "\n";

			if ( is_string( $event_name ) ) {	// Ignore true, false, array, etc.

				switch ( $event_name ) {

					case 'on_focus_load_json':

						/**
						 * Encode the PHP array to JSON only once per page load.
						 */
						if ( empty( $do_once_json_array[ $json_key ] ) ) {

							$do_once_json_array[ $json_key ] = true;

							$select_opt_json = SucomUtil::json_encode_array( $select_opt_arr );

							$html .= '<script type="text/javascript">' . "\n";
							$html .= 'var ' . $json_key . ' = ' . $select_opt_json . ';' . "\n";
							$html .= '</script>' . "\n";
						}

						$input_id_esc = esc_js( $input_id );

						/**
						 * The hover event is also required for Firefox to
						 * render the option list correctly.
						 */
						$html .= '<script type="text/javascript">';
						$html .= 'jQuery( \'select#' . $input_id_esc . ':not( .json_loaded )\' ).on( \'hover focus\', function(){';
						$html .= 'sucomSelectLoadJson( \'select#' . $input_id_esc . '\', \'' . $json_key . '\' );';
						$html .= '});';
						$html .= '</script>' . "\n";

						break;

					case 'on_focus_get_ajax':

						break;

					case 'on_change_redirect':

						/**
						 * The sucomSelectChangeRedirect() javascript function
						 * replaces "%%${name}%%" by the value selected.
						 */
						$redirect_url = add_query_arg( array( $name => '%%' . $name . '%%' ),
							SucomUtil::get_prot() . '://' . $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REQUEST_URI' ] );

						$redirect_url_encoded = SucomUtil::esc_url_encode( $redirect_url );

						$html .= '<script type="text/javascript">';
						$html .= 'jQuery( \'select#' . esc_js( $input_id ) . '\' ).on( \'change\', function(){';
						$html .= 'sucomSelectChangeRedirect( \'' . esc_js( $name ) . '\', this.value, \'' . $redirect_url_encoded . '\' );';
						$html .= '});';
						$html .= '</script>' . "\n";

						break;

					case 'on_show_unhide_rows':

						if ( null === $do_once_show_hide_js ) {

							$do_once_show_hide_js = true;

							$html .= <<<EOF
<script type="text/javascript">
jQuery.each( [ 'show', 'hide' ], function( i, ev ){
	var el = jQuery.fn[ ev ];
	jQuery.fn[ ev ] = function(){
		if ( jQuery( this ).is( 'tr' ) ) {
			var css_class = jQuery( this ).attr( 'class' );
			if ( css_class && css_class.indexOf( 'hide_' ) == 0 ) {
				this.trigger( ev );
			}
		}
		return el.apply( this, arguments );
	};
});
</script>
EOF;
						}

						// No break.

					case 'on_change_unhide_rows':

						$html .= '<script type="text/javascript">';
						$html .= 'jQuery( \'select#' . esc_js( $input_id ) . '\' ).on( \'change\', function(){';
						$html .= 'sucomSelectChangeUnhideRows( \'hide_' . esc_js( $name ) . '\', \'hide_' . esc_js( $name ) . '_\' + this.value );';
						$html .= '});';
						$html .= '</script>' . "\n";

						/**
						 * If we have an option selected, unhide those rows.
						 */
						if ( false !== $selected ) {

							$show_value = false;

							if ( true === $selected ) {

								if ( $in_options ) {
									$show_value = $this->options[ $name ];
								} elseif ( $in_defaults ) {
									$show_value = $this->defaults[ $name ];
								}

							} else {
								$show_value = $selected;
							}

							if ( false !== $show_value ) {	// Just in case.

								$hide_class = 'hide_' . esc_js( $name );
								$show_class = 'hide_' . esc_js( $name . '_' . $show_value );

								if ( $event_name === 'on_show_unhide_rows' ) {

									$html .= '<script type="text/javascript">';
									$html .= 'jQuery( \'tr#' . esc_js( $tr_id ) . '\' ).on( \'show\', function(){';
									$html .= 'sucomSelectChangeUnhideRows( \'' . $hide_class . '\', \'' . $show_class . '\' );';
									$html .= '});';
									$html .= '</script>' . "\n";

								} else {

									$html .= '<script type="text/javascript">';

									if ( SucomUtil::get_const( 'DOING_AJAX' ) ) {

										$html .= 'sucomSelectChangeUnhideRows( \'' . $hide_class . '\', \'' . $show_class . '\' );';

									} else {

										$html .= 'jQuery( window ).load( function(){';
										$html .= 'sucomSelectChangeUnhideRows( \'' . $hide_class . '\', \'' . $show_class . '\' );';
										$html .= '});';
									}

									$html .= '</script>' . "\n";
								}
							}
						}

						break;
				}
			}

			return $html;
		}

		/**
		 * Add 'none' as the first array element. Always converts the array to associative.
		 */
		public function get_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$is_disabled = false, $selected = false, $event_name = false, $event_args = null ) {

			/**
			 * Set 'none' as the default value is no default is defined.
			 */
			if ( ! empty( $name ) && ! isset( $this->defaults[ $name ] ) ) {
				$this->defaults[ $name ] = 'none';
			}

			if ( null === $is_assoc ) {
				$is_assoc  = SucomUtil::is_assoc( $values );
			}

			if ( ! $is_assoc ) {

				$new_values;

				foreach ( $values as $option_value => $label ) {

					if ( is_array( $label ) ) {	// Just in case.
						$label = implode( ', ', $label );
					}

					$new_values[ (string) $label ] = $label;
				}

				$values = $new_values;

				unset( $new_values );
			}

			$values = array( 'none' => 'none' ) + $values;

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc = true,
				$is_disabled, $selected, $event_name, $event_args );
		}

		public function get_no_select( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$selected = false, $event_name = false ) {
		
			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true, $selected, $event_name );
		}

		public function get_no_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$selected = false, $event_name = false ) {

			return $this->get_select_none( $name, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true, $selected, $event_name );
		}

		public function get_no_select_options( $name, array $opts, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $event_name = false, $event_args = null ) {
		
			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : true;

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true, $selected, $event_name, $event_args );
		}

		/**
		 * The "hour_mins" class is always prefixed to the $css_class value.
		 * By default, the 'none' array elements is not added.
		 */
		public function get_select_time( $name, $css_class = '', $css_id = '',
			$is_disabled = false, $selected = false, $step_mins = 15, $add_none = false ) {

			static $local_cache = array();

			if ( empty( $local_cache[ $step_mins ] ) ) {
				$local_cache[ $step_mins ] = SucomUtil::get_hours_range( $start_secs = 0, $end_secs = DAY_IN_SECONDS,
					$step_secs = 60 * $step_mins, $label_format = 'H:i' );
			}

			$css_class  = trim( 'hour_mins ' . $css_class );
			$event_name = 'on_focus_load_json';
			$event_args = 'hour_mins_step_' . $step_mins;

			/**
			 * Set 'none' as the default value if no default is defined.
			 */
			if ( $add_none ) {

				$event_args .= '_add_none';

				if ( ! empty( $name ) && ! isset( $this->defaults[ $name ] ) ) {
					$this->defaults[ $name ] = 'none';
				}

				return $this->get_select_none( $name, $local_cache[ $step_mins ], $css_class, $css_id, $is_assoc = true,
					$is_disabled, $selected, $event_name, $event_args );
			}

			return $this->get_select( $name, $local_cache[ $step_mins ], $css_class, $css_id, $is_assoc = true,
				$is_disabled, $selected, $event_name, $event_args );
		}

		public function get_no_select_time( $name, $css_class = '', $css_id = '',
			$selected = false, $step_mins = 15, $add_none = false ) {
		
			return $this->get_select_time( $name, $css_class, $css_id,
				$is_disabled = true, $selected, $step_mins, $add_none );
		}

		public function get_no_select_time_options( $name, array $opts, $css_class = '', $css_id = '',
			$step_mins = 15, $add_none = false ) {
		
			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : true;

			return $this->get_select_time( $name, $css_class, $css_id,
				$is_disabled = true, $selected, $step_mins, $add_none );
		}

		/**
		 * The "timezone" class is always prefixed to the $css_class value.
		 */
		public function get_select_timezone( $name, $css_class = '', $css_id = '',
			$is_disabled = false, $selected = false ) {

			$css_class = trim( 'timezone ' . $css_class );

			$timezones = timezone_identifiers_list();

			if ( empty( $this->defaults[ $name ] ) ) {

				/**
				 * The timezone string will be empty if a UTC offset, instead
				 * of a city, has selected in the WordPress settings.
				 */
				$this->defaults[ $name ] = get_option( 'timezone_string' );

				if ( empty( $this->defaults[ $name ] ) ) {
					$this->defaults[ $name ] = 'UTC';
				}
			}

			return $this->get_select( $name, $timezones, $css_class, $css_id, $is_assoc = false,
				$is_disabled, $selected );
		}

		public function get_no_select_timezone( $name, $css_class = '', $css_id = '', $selected = false ) {

			/**
			 * The "timezone" class is always prefixed to the $css_class value.
			 */
			return $this->get_select_timezone( $name, $css_class, $css_id,
				$is_disabled = true, $selected );
		}

		public function get_select_country( $name, $css_class = '', $css_id = '',
			$is_disabled = false, $selected = false ) {

			/**
			 * Set 'none' as the default value is no default is defined.
			 */
			if ( ! empty( $name ) && ! isset( $this->defaults[ $name ] ) ) {
				$this->defaults[ $name ] = 'none';
			}

			/**
			 * Sanity check for possibly older input field values.
			 */
			if ( false === $selected ) {
				if ( empty( $this->options[ $name ] ) || 
					( $this->options[ $name ] !== 'none' &&
						strlen( $this->options[ $name ] ) !== 2 ) ) {

					$selected = $this->defaults[ $name ];
				}
			}

			$values = array( 'none' => 'none' ) + SucomUtil::get_alpha2_countries();

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc = true,
				$is_disabled, $selected );
		}

		public function get_no_select_country( $name, $css_class = '', $css_id = '', $selected = false ) {

			return $this->get_select_country( $name, $css_class, $css_id,
				$is_disabled = true, $selected );
		}

		public function get_no_select_country_options( $name, array $opts, $css_class = '', $css_id = '' ) {

			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : false;

			return $this->get_select_country( $name, $css_class, $css_id,
				$is_disabled = true, $selected );
		}

		public function get_select_img_size( $name, $name_preg = '//', $invert = false ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			$invert     = $invert == false ? null : PREG_GREP_INVERT;
			$size_names = preg_grep( $name_preg, get_intermediate_image_sizes(), $invert );

			natsort( $size_names );

			$html        = '<select name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '">';
			$in_options  = $this->in_options( $name );	// optimize and call only once
			$in_defaults = $this->in_defaults( $name );	// optimize and call only once

			foreach ( $size_names as $size_name ) {

				if ( ! is_string( $size_name ) ) {
					continue;
				}

				$size = SucomUtilWP::get_size_info( $size_name );

				$html .= '<option value="' . esc_attr( $size_name ) . '" ';

				if ( $in_options ) {
					$html .= selected( $this->options[ $name ], $size_name, false );
				}

				$html .= '>';
				$html .= esc_html( $size_name . ' [ ' . $size[ 'width' ] . 'x' . $size[ 'height' ] . ( $size[ 'crop' ] ? ' cropped' : '' ) . ' ]' );

				if ( $in_defaults && $size_name === $this->defaults[ $name ] ) {
					$html .= ' ' . $this->get_value_transl( '(default)' );
				}

				$html .= '</option>';
			}

			$html .= '</select>';

			return $html;
		}

		/**
		 * Text input field.
		 */
		public function get_input( $name, $css_class = '', $css_id = '', $len = 0, $placeholder = '',
			$is_disabled = false, $tabindex = 0 ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			if ( $is_disabled || $this->get_options( $name . ':is' ) === 'disabled' ) {
				return $this->get_no_input( $name, $css_class, $css_id, $placeholder );
			}

			$html        = '';
			$value       = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );

			if ( ! is_array( $len ) ) {	// A non-array value defaults to a max length.
				if ( empty( $len ) ) {
					$len = array();
				} else {
					$len = array( 'max' => $len );
				}
			}

			if ( ! empty( $len ) ) {

				if ( empty( $css_id ) ) {
					$css_id = $name;
				}

				$html .= $this->get_text_length_js( 'text_' . $css_id );
			}

			$html .= '<input type="text" name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"';
			$html .= empty( $css_id ) ? ' id="text_' . esc_attr( $name ) . '"' : ' id="text_' . esc_attr( $css_id ) . '"';
			$html .= empty( $tabindex ) ? '' : ' tabindex="' . esc_attr( $tabindex ) . '"';

			foreach ( $len as $key => $val ) {
				$html .= empty( $len[ $key ] ) ? '' : ' ' . $key . 'Length="' . esc_attr( $len[ $key ] ) . '"';
			}

			$html .= $this->get_placeholder_events( 'input', $placeholder );
			$html .= ' value="' . esc_attr( $value ) . '" />' . "\n";
			$html .= empty( $len ) ? '' : ' <div id="text_' . esc_attr( $css_id ) . '-lenMsg"></div>' . "\n";

			return $html;
		}

		public function get_no_input( $name = '', $css_class = '', $css_id = '', $placeholder = '' ) {

			$html        = '';
			$value       = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );

			if ( ! empty( $name ) ) {
				$html .= $this->get_hidden( $name );
			}

			$html .= $this->get_no_input_value( $value, $css_class, $css_id, $placeholder );

			return $html;
		}

		public function get_no_input_options( $name, array $opts, $css_class = '', $css_id = '', $placeholder = '' ) {

			$value = isset( $opts[ $name ] ) ? $opts[ $name ] : '';

			return $this->get_no_input_value( $value, $css_class, $css_id, $placeholder );
		}

		public function get_no_input_value( $value = '', $css_class = '', $css_id = '', $placeholder = '', $max_input = 1 ) {

			$html        = '';
			$end_num     = $max_input > 0 ? $max_input - 1 : 0;
			$input_class = empty( $css_class ) ? '' : $css_class;
			$input_id    = empty( $css_id ) ? '' : $css_id;

			foreach ( range( 0, $end_num, 1 ) as $key_num ) {

				if ( $max_input > 1 ) {

					$input_class = empty( $css_class ) ? 'input_num' : $css_class . ' input_num';
					$input_id    = empty( $css_id ) ? '' : $css_id . '_' . $key_num;

					$html .= '<div class="wrap_multi">' . "\n";
					$html .= '<p class="input_num">' . ( $key_num + 1 ) . '.</p>';
				}

				$html .= '<input type="text" disabled="disabled"' .
					( empty( $input_class ) ? '' : ' class="' . esc_attr( $input_class ) . '"' ) .
					( empty( $input_id ) ? '' : ' id="text_' . esc_attr( $input_id ) . '"' ) .
					( $placeholder === '' || $key_num > 0 ? '' : ' placeholder="' . esc_attr( $placeholder ) . '"' ) .
					' value="' . esc_attr( $value ) . '" />' . "\n";

				if ( $max_input > 1 ) {
					$html .= '</div>' . "\n";
				}
			}

			return $html;
		}

		public function get_input_color( $name = '', $css_class = '', $css_id = '', $is_disabled = false ) {

			if ( empty( $name ) ) {

				$value = '';

				$is_disabled = true;

			} else {

				$value = $this->in_options( $name ) ? $this->options[ $name ] : '';

				if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
					$is_disabled = true;
				}
			}

			return '<input type="text"' .
				( $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' ) .
				( empty( $css_class ) ? ' class="colorpicker"' : ' class="colorpicker ' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? ' id="text_' . esc_attr( $name ) . '"' : ' id="text_' . esc_attr( $css_id ) . '"' ) .
				' placeholder="#000000" value="' . esc_attr( $value ) . '" />';
		}

		public function get_input_date( $name = '', $css_class = '', $css_id = '',
			$min_date = '', $max_date = '', $is_disabled = false ) {

			if ( empty( $name ) ) {

				$value = '';

				$is_disabled = true;

			} else {

				$value = $this->in_options( $name ) ? $this->options[ $name ] : '';

				if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
					$is_disabled = true;
				}
			}

			return '<input type="text"' .
				( $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' ) .
				( empty( $css_class ) ? ' class="datepicker"' : ' class="datepicker ' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? ' id="text_' . esc_attr( $name ) . '"' : ' id="text_' . esc_attr( $css_id ) . '"' ) .
				( empty( $min_date ) ? '' : ' min="' . esc_attr( $min_date ) . '"' ) .
				( empty( $max_date ) ? '' : ' max="' . esc_attr( $max_date ) . '"' ) .
				' placeholder="yyyy-mm-dd" value="' . esc_attr( $value ) . '" />';
		}

		public function get_no_input_date( $name = '' ) {

			return $this->get_input_date( $name, $css_class = '', $css_id = '',
				$min_date = '', $max_date = '', $is_disabled = true );
		}

		public function get_no_input_date_options( $name, $opts ) {

			$value = isset( $opts[ $name ] ) ? $opts[ $name ] : '';

			return $this->get_no_input_value( $value, 'datepicker', '', 'yyyy-mm-dd' );
		}

		public function get_input_image_upload( $opt_pre, $placeholder = '', $is_disabled = false ) {

			$opt_suffix  = '';
			$default_lib = 'wp';
			$media_libs  = array( 'wp' => 'Media Library' );
			$data        = array();

			if ( preg_match( '/^(.*)(_[0-9]+)$/', $opt_pre, $matches ) ) {

				$opt_pre = $matches[1];

				$opt_suffix = $matches[2];	// Mutiple numbered option.
			}

			if ( true === $this->p->avail[ 'media' ][ 'ngg' ] ) {
				$media_libs[ 'ngg' ] = 'NextGEN Gallery';
			}

			if ( strpos( $placeholder, 'ngg-' ) === 0 ) {
				$default_lib = 'ngg';
				$placeholder = preg_replace( '/^ngg-/', '', $placeholder );
			}

			$input_id = $this->get_input( $opt_pre . '_id' . $opt_suffix, 'short', '', 0, $placeholder, $is_disabled );

			/**
			 * Disable the select option if only 1 media lib.
			 */
			$select_disabled = count( $media_libs ) <= 1 ? true : $is_disabled;

			$select_lib = $this->get_select( $opt_pre . '_id_pre' . $opt_suffix, $media_libs, '', '', true, $select_disabled, $default_lib );

			/**
			 * The css id is used to set image values and disable the image url.
			 */
			if ( ( empty( $this->options[ $opt_pre . '_id_pre' . $opt_suffix ] ) ||
				$this->options[ $opt_pre . '_id_pre' . $opt_suffix ] === 'wp' ) && 
					! empty( $this->options[ $opt_pre . '_id' . $opt_suffix ] ) ) {

				$data[ 'pid' ] = $this->options[ $opt_pre . '_id' . $opt_suffix ];

			} elseif ( $default_lib === 'wp' && ! empty( $placeholder ) ) {

				$data[ 'pid' ] = $placeholder;
			}

			$button_upload = function_exists( 'wp_enqueue_media' ) ? $this->get_button(
				'Select or Upload Image',		// $value
				'sucom_image_upload_button button',	// $css_class
				$opt_pre . $opt_suffix,		// $css_id
				'',					// $url
				false,					// $newtab
				$is_disabled,				// $is_disabled
				$data					// $data
			) : '';

			return '<div class="img_upload">' . $input_id . '&nbsp;in&nbsp;' . $select_lib . '&nbsp;' . $button_upload . '</div>';
		}

		public function get_no_input_image_upload( $opt_pre, $placeholder = '' ) {

			return $this->get_input_image_upload( $opt_pre, $placeholder, $is_disabled = true );
		}

		public function get_input_image_dimensions( $name, $use_opts = false, $narrow = false, $is_disabled = false ) {

			$placeholder_width  = '';
			$placeholder_height = '';
			$crop_area_select   = '';

			/**
			 * $use_opts is true when used for post / user meta forms (to show default values).
			 */
			if ( $use_opts ) {

				$placeholder_width  = $this->get_placeholder_sanitized( $name . '_width', true );
				$placeholder_height = $this->get_placeholder_sanitized( $name . '_height', true );

				foreach ( array( 'crop', 'crop_x', 'crop_y' ) as $key ) {
					if ( ! $this->in_options( $name . '_' . $key ) && $this->in_defaults( $name . '_' . $key ) ) {
						$this->options[ $name . '_' . $key ] = $this->defaults[ $name . '_' . $key ];
					}
				}
			}

			/**
			 * Crop area selection is only available since WP v3.9.
			 */
			global $wp_version;

			if ( version_compare( $wp_version, '3.9', '>=' ) ) {

				$crop_area_select .= true === $narrow ?
					' <div class="img_crop_from is_narrow">' :
					' <div class="img_crop_from">from';

				foreach ( array( 'crop_x', 'crop_y' ) as $key ) {
					$crop_area_select .= ' ' . $this->get_select( $name . '_' . $key, $this->p->cf[ 'form' ][ 'position_' . $key ],
						$css_class = 'crop_area', $css_id = '', $is_assoc = true, $is_disabled );
				}

				$crop_area_select .= '</div>';
			}

			return $this->get_input( $name . '_width', 'short width', '', 0, $placeholder_width, $is_disabled ) . 'x' .
				$this->get_input( $name . '_height', 'short height', '', 0, $placeholder_height, $is_disabled ) .
					'px crop ' . $this->get_checkbox( $name . '_crop', '', '', $is_disabled ) . $crop_area_select;
		}

		public function get_no_input_image_dimensions( $name, $use_opts = false, $narrow = false ) {

			return $this->get_input_image_dimensions( $name, $use_opts, $narrow, $is_disabled = true );
		}

		public function get_input_image_url( $opt_pre, $url = '' ) {

			$opt_suffix = '';

			if ( preg_match( '/^(.*)(_[0-9]+)$/', $opt_pre, $matches ) ) {
				$opt_pre = $matches[1];
				$opt_suffix = $matches[2];
			}

			if ( empty( $this->options[ $opt_pre . '_id' . $opt_suffix ] ) ) {
				$placeholder = SucomUtil::esc_url_encode( $url );
				$is_disabled = false;
			} else {
				$placeholder = '';
				$is_disabled = true;
			}

			return $this->get_input( $opt_pre . '_url' . $opt_suffix, 'wide', '', 0, $placeholder, $is_disabled );
		}

		public function get_input_video_dimensions( $name, $media_info = array(), $is_disabled = false ) {

			$placeholder_width  = '';
			$placeholder_height = '';

			if ( ! empty( $media_info ) && is_array( $media_info ) ) {
				$placeholder_width  = empty( $media_info[ 'vid_width' ] ) ? '' : $media_info[ 'vid_width' ];
				$placeholder_height = empty( $media_info[ 'vid_height' ] ) ? '' : $media_info[ 'vid_height' ];
			}

			return $this->get_input( $name . '_width', 'short width', '', 0, $placeholder_width, $is_disabled ) . 'x' .
				$this->get_input( $name . '_height', 'short height', '', 0, $placeholder_height, $is_disabled ) . 'px';
		}

		public function get_no_input_video_dimensions( $name, $media_info = array() ) {

			return $this->get_input_video_dimensions( $name, $media_info, $is_disabled = true );
		}

		public function get_input_video_url( $opt_pre, $url = '' ) {

			/**
			 * Disable if we have a custom video embed.
			 */
			$is_disabled = empty( $this->options[ $opt_pre . '_embed' ] ) ? false : true;

			return $this->get_input( $opt_pre . '_url', 'wide', '', 0, SucomUtil::esc_url_encode( $url ), $is_disabled );
		}

		public function get_input_copy_clipboard( $value, $css_class = 'wide', $css_id = '' ) {

			if ( empty( $css_id ) ) {
				$css_id = uniqid();
			}

			$input = '<input type="text"' .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? '' : ' id="text_' . esc_attr( $css_id ) . '"' ) .
				' value="' . esc_attr( $value ) . '" readonly' .
				' onFocus="this.select(); document.execCommand(\'Copy\',false,null);"' .
				' onMouseUp="return false;">';

			if ( ! empty( $css_id ) ) {

				/**
				 * Dashicons are only available since WP v3.8
				 */
				global $wp_version;

				if ( version_compare( $wp_version, '3.8', '>=' ) ) {
					$html = '<div class="clipboard"><div class="copy_button">' .
						'<a class="outline" href="" title="Copy to clipboard"' .
						' onClick="return sucomCopyInputId( \'text_' . esc_js( $css_id ) . '\');">' .
						'<span class="dashicons dashicons-clipboard"></span></a>' .
						'</div><div class="copy_text">' . $input . '</div></div>';
				}

			} else {
				$html = $input;
			}
			return $html;
		}

		public function get_input_multi( $name, $css_class = '', $css_id = '',
			$start_num = 0, $max_input = 90, $show_first = 5, $is_disabled = false ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num    = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;

				$opt_key      = $name . '_' . $key_num;
				$opt_disabled = $is_disabled || $this->get_options( $opt_key . ':is' ) === 'disabled' ? true : false;

				$input_class   = empty( $css_class ) ? 'multi input_num' : 'multi ' . $css_class . ' input_num';
				$input_id      = empty( $css_id ) ? $opt_key : $css_id . '_' . $key_num;
				$input_id_prev = empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num;
				$input_id_next = empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num;
				$input_value   = $this->in_options( $opt_key ) ? $this->options[ $opt_key ] : '';

				$display = empty( $one_more ) && $key_num >= $show_first ? false : true;

				if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {
					continue;
				}
				
				$html .= '<div class="wrap_multi" id="wrap_' . esc_attr( $input_id ) . '"';
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";

				$html .= '<p class="input_num">' . ( $key_num + 1 ) . '.</p>';

				$html .= '<input type="text"' . ( $opt_disabled ? ' disabled="disabled"' : '' ) .
					' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"' .
					' class="' . esc_attr( $input_class ) . '"' .
					' id="text_' . esc_attr( $input_id ) . '"' .
					' value="' . esc_attr( $input_value ) . '"' .
					' onFocus="if ( jQuery(\'input#text_' . $input_id_prev . '\').val().length ) { '.
						'jQuery(\'div#wrap_' . esc_attr( $input_id_next ) . '\').show(); }" />';

				$html .= '</div>' . "\n";

				$one_more = empty( $input_value ) ? false : true;

			}

			return $html;
		}

		public function get_no_input_multi( $name, $css_class = '', $css_id = '',
			$start_num = 0, $max_input = 90, $show_first = 5 ) {

			return $this->get_input_multi( $name, $css_class, $css_id,
				$start_num, $max_input, $show_first, $is_disabled = true );
		}

		/**
		 * Deprecated on 2019/07/14.
		 */
		public function get_date_time_iso( $name_prefix = '', $is_disabled = false, $step_mins = 15, $add_none = true ) {

			return $this->get_date_time_tz( $name_prefix, $is_disabled, $step_mins, $add_none );
		}

		public function get_date_time_tz( $name_prefix = '', $is_disabled = false, $step_mins = 15, $add_none = true ) {

			$html = $this->get_input_date( $name_prefix . '_date', $css_class = '', $css_id = '',
				$min_date = '', $max_date = '', $is_disabled ) . ' ';

			$html .= $this->get_value_transl( 'at' ) . ' ';

			/**
			 * The "hour_mins" class is always prefixed to the $css_class value.
			 */
			$html .= $this->get_select_time( $name_prefix . '_time', $css_class = '', $css_id = '',
				$is_disabled, $selected = false, $step_mins, $add_none ) . ' ';

			$html .= $this->get_value_transl( 'tz' ) . ' ';

			/**
			 * The "timezone" class is always prefixed to the $css_class value.
			 */
			$html .= $this->get_select_timezone( $name_prefix . '_timezone', $css_class = '', $css_id = '',
				$is_disabled, $selected = false );

			return $html;
		}

		/**
		 * Deprecated on 2019/07/14.
		 */
		public function get_no_date_time_iso( $name_prefix = '' ) {

			return $this->get_date_time_tz( $name_prefix, $is_disabled = true );
		}

		public function get_no_date_time_tz( $name_prefix = '' ) {

			return $this->get_date_time_tz( $name_prefix, $is_disabled = true );
		}

		public function get_mixed_multi( $mixed, $css_class, $css_id,
			$start_num = 0, $max_input = 10, $show_first = 2, $is_disabled = false ) {

			if ( empty( $mixed ) ) {
				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num    = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;

				$wrap_id      = $css_id . '_' . $key_num;
				$wrap_id_prev = $css_id . '_' . $prev_num;
				$wrap_id_next = $css_id . '_' . $next_num;

				$display = empty( $one_more ) && $key_num >= $show_first ? false : true;

				$html .= '<div class="wrap_multi" id="wrap_' . esc_attr( $wrap_id ) . '"';
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";

				$html .= '<p class="input_num">' . ( $key_num + 1 ) . '.</p>';

				foreach ( $mixed as $name => $atts ) {

					$opt_key      = $name . '_' . $key_num;
					$opt_disabled = $is_disabled || $this->get_options( $opt_key . ':is' ) === 'disabled' ? true : false;

					$in_options  = $this->in_options( $opt_key );	// Optimize and call only once.
					$in_defaults = $this->in_defaults( $opt_key );	// Optimize and call only once.

					$input_title   = empty( $atts[ 'input_title' ] ) ? '' : $atts[ 'input_title' ];
					$input_class   = empty( $atts[ 'input_class' ] ) ? 'multi input_num' : 'multi ' . $atts[ 'input_class' ] . ' input_num';
					$input_id      = empty( $atts[ 'input_id' ] ) ? $opt_key : $atts[ 'input_id' ] . '_' . $key_num;
					$input_content = empty( $atts[ 'input_content' ] ) ? '' : $atts[ 'input_content' ];
					$input_values  = empty( $atts[ 'input_values' ] ) ? array() : $atts[ 'input_values' ];

					if ( isset( $atts[ 'placeholder' ] ) ) {
						$placeholder = $this->get_placeholder_sanitized( $opt_key, $atts[ 'placeholder' ] );
					} else {
						$placeholder = '';
					}
	
					if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {
						continue;
					}

					/**
					 * Default paragraph display is an inline-block.
					 */
					if ( ! empty( $atts[ 'input_label' ] ) ) {
						$html .= '<p class="' . esc_attr( $input_class ) . '">' . $atts[ 'input_label' ] . '</p> ';
					}

					if ( isset( $atts[ 'input_type' ] ) ) {

						switch ( $atts[ 'input_type' ] ) {

							case 'radio':

								$radio_inputs = array();

								foreach ( $input_values as $input_value ) {

									if ( $in_options ) {
										$input_checked = checked( $this->options[ $opt_key ], $input_value, false );
									} elseif ( isset( $atts[ 'input_default' ] ) ) {
										$input_checked = checked( $atts[ 'input_default' ], $input_value, false );
									} elseif ( $in_defaults ) {
										$input_checked = checked( $this->defaults[ $opt_key ], $input_value, false );
									} else {
										$input_checked = '';
									}

									$input_name_value = ' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '" ' .
										'value="' . esc_attr( $input_value ) . '"';

									$radio_inputs[] = '<input type="radio"' . ( $opt_disabled ?
										' disabled="disabled"' : $input_name_value ) . $input_checked . '/>';
								}

								if ( ! empty( $radio_inputs ) ) {
									$html .= '<p' .
										' class="' . esc_attr( $input_class ) . '"' .
										' id="' . esc_attr( $input_id ) . '">' .
										vsprintf( $atts[ 'input_content' ], $radio_inputs ) .
										'</p>';
								}

								break;

							case 'text':

								$input_value = $in_options ? $this->options[ $opt_key ] : '';

								$html .= '<input type="text"' . ( $opt_disabled ? ' disabled="disabled"' : '' ) .
									' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"' .
									' title="' . esc_attr( $input_title ) . '"' .
									' class="' . esc_attr( $input_class ) . '"' .
									' id="text_' . esc_attr( $input_id ) . '"' .
									' value="' . esc_attr( $input_value ) . '"' .
									' onFocus="jQuery(\'div#wrap_' . esc_attr( $wrap_id_next ) . '\').show();" />' . "\n";

								$one_more = empty( $input_value ) ? false : true;

								break;

							case 'textarea':

								$input_value = $in_options ? $this->options[ $opt_key ] : '';

								$html .= '<textarea ' . ( $opt_disabled ? ' disabled="disabled"' : '' ) .
									' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"' .
									' title="' . esc_attr( $input_title ) . '"' .
									' class="' . esc_attr( $input_class ) . '"' .
									' id="textarea_' . esc_attr( $input_id ) . '"' .
									( $this->get_placeholder_events( 'textarea', $placeholder ) ) .
									'>' . esc_attr( $input_value ) . '</textarea>';

								break;

							case 'select':

								$html .= '<select ' . ( $opt_disabled ? ' disabled="disabled"' : '' ) .
									' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"' .
									' title="' . esc_attr( $input_title ) . '"' .
									' class="' . esc_attr( $input_class ) . '"' .
									' id="select_' . esc_attr( $input_id ) . '"' .
									' onFocus="jQuery(\'div#wrap_' . esc_attr( $wrap_id_next ) . '\').show();">' . "\n";

								$select_options = empty( $atts[ 'select_options' ] ) || 
									! is_array( $atts[ 'select_options' ] ) ?
										array() : $atts[ 'select_options' ];

								$select_selected = empty( $atts[ 'select_selected' ] ) ? null : $atts[ 'select_selected' ];
								$select_default   = empty( $atts[ 'select_default' ] ) ? null : $atts[ 'select_default' ];

								$is_assoc = SucomUtil::is_assoc( $select_options );

								$select_opt_count = 0;	// Used to check for first option.
								$select_opt_added = 0;

								foreach ( $select_options as $option_value => $label ) {

									if ( is_array( $label ) ) {	// Just in case.
										$label = implode( ', ', $label );
									}

									/**
									 * If the array is not associative (so a regular numbered array), 
									 * then the label / description is used as the saved value.
									 */
									if ( ! $is_assoc ) {
										$option_value = $label;
									}

									$label_transl = $this->get_value_transl( $label );

									if ( ( $in_defaults && $option_value === $this->defaults[ $opt_key ] ) ||
										( $select_default !== null && $option_value === $select_default ) ) {

										$label_transl .= ' ' . $this->get_value_transl( '(default)' );
									}

									if ( $select_selected !== null ) {
										$is_selected_html = selected( $select_selected, $option_value, false );
									} elseif ( $in_options ) {
										$is_selected_html = selected( $this->options[ $opt_key ], $option_value, false );
									} elseif ( $select_default !== null ) {
										$is_selected_html = selected( $select_default, $option_value, false );
									} elseif ( $in_defaults ) {
										$is_selected_html = selected( $this->defaults[ $opt_key ], $option_value, false );
									} else {
										$is_selected_html = '';
									}

									$select_opt_count++; 	// Used to check for first option.

									/**
									 * For disabled selects, only include the first and/or selected option.
									 */
									if ( ! $opt_disabled || $select_opt_count === 1 || $is_selected_html ) {

										$html .= '<option value="' . esc_attr( $option_value ) . '"' . $is_selected_html . '>';
										$html .= $label_transl;
										$html .= '</option>' . "\n";

										$select_opt_added++; 
									}
								}
								
								$html .= '<!-- ' . $select_opt_added . ' select options added -->' . "\n";
								$html .= '</select>' . "\n";

								break;
						}
					}
				}

				$html .= '</div>' . "\n";
			}

			return $html;
		}

		public function get_no_mixed_multi( $mixed, $css_class, $css_id,
			$start_num = 0, $max_input = 10, $show_first = 2 ) {

			return $this->get_mixed_multi( $mixed, $css_class, $css_id,
				$start_num, $max_input, $show_first, $is_disabled = true );
		}

		public function get_image_dimensions_text( $name, $use_opts = false ) {

			if ( ! empty( $this->options[ $name . '_width' ] ) && ! empty( $this->options[ $name . '_height' ] ) ) {

				return $this->options[ $name . '_width' ] . 'x' . $this->options[ $name . '_height' ] . 'px' .
					( $this->options[ $name . '_crop' ] ? ' cropped' : '' );

			} elseif ( true === $use_opts ) {

				$def_width  = empty( $this->p->options[ $name . '_width' ] ) ? '' : $this->p->options[ $name . '_width' ];
				$def_height = empty( $this->p->options[ $name . '_height' ] ) ? '' : $this->p->options[ $name . '_height' ];
				$def_crop   = empty( $this->p->options[ $name . '_crop' ] ) ? false : true;

				if ( ! empty( $def_width ) && ! empty( $def_height ) ) {
					return $def_width . 'x' . $def_height . 'px' . ( $def_crop ? ' cropped' : '' );
				}
			}

			return;
		}

		public function get_video_dimensions_text( $name, $media_info ) {

			if ( ! empty( $this->options[ $name . '_width' ] ) && ! empty( $this->options[ $name . '_height' ] ) ) {

				return $this->options[ $name . '_width' ] . 'x' . $this->options[ $name . '_height' ];

			} elseif ( ! empty( $media_info ) && is_array( $media_info ) ) {

				$def_width  = empty( $media_info[ 'vid_width' ] ) ? '' : $media_info[ 'vid_width' ];
				$def_height = empty( $media_info[ 'vid_height' ] ) ? '' : $media_info[ 'vid_height' ];

				if ( ! empty( $def_width ) && ! empty( $def_height ) ) {
					return $def_width . 'x' . $def_height;
				}
			}

			return '';
		}

		public function get_textarea( $name, $css_class = '', $css_id = '',
			$len = 0, $placeholder = '', $is_disabled = false ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$is_disabled = true;
			}

			$html        = '';
			$value       = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );

			if ( ! is_array( $len ) ) {
				$len = array( 'max' => $len );
			}

			if ( ! empty( $len[ 'max' ] ) ) {

				if ( empty( $css_id ) ) {
					$css_id = $name;
				}

				$html .= $this->get_text_length_js( 'textarea_' . $css_id );
			}

			$html .= '<textarea ' .
				( $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' ) .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? ' id="textarea_' . esc_attr( $name ) . '"' : ' id="textarea_' . esc_attr( $css_id ) . '"' ) .
				( empty( $len[ 'max' ] ) || $is_disabled ? '' : ' maxLength="' . esc_attr( $len[ 'max' ] ) . '"' ) .
				( empty( $len[ 'warn' ] ) || $is_disabled ? '' : ' warnLength="' . esc_attr( $len[ 'warn' ] ) . '"' ) .
				( empty( $len[ 'max' ] ) && empty( $len[ 'rows' ] ) ? '' : ( empty( $len[ 'rows' ] ) ?
					' rows="'.( round( $len[ 'max' ] / 100 ) + 1 ) . '"' : ' rows="' . $len[ 'rows' ] . '"' ) ) .
				( $this->get_placeholder_events( 'textarea', $placeholder ) ) . '>' . esc_attr( $value ) . '</textarea>' .
				( empty( $len[ 'max' ] ) || $is_disabled ? '' : ' <div id="textarea_' . esc_attr( $css_id ) . '-lenMsg"></div>' );

			return $html;
		}

		public function get_no_textarea( $name, $css_class = '', $css_id = '',
			$len = 0, $placeholder = '' ) {

			return $this->get_textarea( $name, $css_class, $css_id, $len, $placeholder, $is_disabled = true );
		}

		public function get_no_textarea_options( $name, array $opts, $css_class = '', $css_id = '',
			$len = 0, $placeholder = '' ) {

			$value = isset( $opts[ $name ] ) ? $opts[ $name ] : '';

			return $this->get_no_textarea_value( $value, $css_class, $css_id, $len, $placeholder );
		}

		public function get_no_textarea_value( $value = '', $css_class = '', $css_id = '',
			$len = 0, $placeholder = '' ) {

			return '<textarea disabled="disabled"' .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? '' : ' id="textarea_' . esc_attr( $css_id ) . '"' ) .
				( empty( $len ) ? '' : ' rows="'.( round( $len / 100 ) + 1 ) . '"' ) .
				'>' . esc_attr( $value ) . '</textarea>';
		}

		public function get_submit( $value, $css_class = 'button-primary', $css_id = '' ) {

			$html = '<input type="submit"';
			$html .= empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"';
			$html .= empty( $css_id ) ? '' : ' id="submit_' . esc_attr( $css_id ) . '"';
			$html .= ' value="' . esc_attr( $value ) . '"/>';

			return $html;
		}

		public function get_button( $value, $css_class = '', $css_id = '', $url = '',
			$newtab = false, $is_disabled = false, $data = array() ) {

			if ( true === $newtab ) {
				$on_click = ' onClick="window.open(\'' . SucomUtil::esc_url_encode( $url ) . '\', \'_blank\');"';
			} else {
				$on_click = ' onClick="location.href=\'' . SucomUtil::esc_url_encode( $url ) . '\';"';
			}

			$data_attr = '';

			if ( ! empty( $data ) && is_array( $data ) ) {
				foreach ( $data as $data_key => $data_value ) {
					$data_attr .= ' data-' . $data_key . '="' . esc_attr( $data_value ) . '"';
				}
			}

			$html = '<input type="button" ' .
				( $is_disabled ? ' disabled="disabled"' : '' ) .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? '' : ' id="button_' . esc_attr( $css_id ) . '"' ) .
				( empty( $url ) || $is_disabled ? '' : $on_click ) .
				' value="' . esc_attr( wp_kses( $value, array() ) ) . '" ' . $data_attr . '/>';

			return $html;
		}

		public function get_options( $opt_key = false, $def_val = null ) {

			if ( false !== $opt_key ) {

				if ( isset( $this->options[ $opt_key ] ) ) {
					return $this->options[ $opt_key ];
				} else {
					return $def_val;
				}

			} else {
				return $this->options;
			}
		}

		public function in_options( $opt_key, $is_preg = false ) {

			if ( $is_preg ) {

				if ( ! is_array( $this->options ) ) {
					return false;
				}

				$opts = SucomUtil::preg_grep_keys( $opt_key, $this->options );

				return ( ! empty( $opts ) ) ? true : false;
			}

			return isset( $this->options[ $opt_key ] ) ? true : false;
		}

		public function in_defaults( $opt_key ) {

			return isset( $this->defaults[ $opt_key ] ) ? true : false;
		}

		private function get_text_length_js( $css_id ) {

			return empty( $css_id ) ? '' : '
				<script type="text/javascript">
					jQuery( document ).ready( function() {
						jQuery( \'#' . esc_js( $css_id ) . '\' ).focus( function() { sucomTextLen(\'' . esc_js( $css_id ) . '\'); } );
						jQuery( \'#' . esc_js( $css_id ) . '\' ).keyup( function() { sucomTextLen(\'' . esc_js( $css_id ) . '\'); } );
					});
				</script>';
		}

		private function get_placeholder_sanitized( $name, $placeholder = '' ) {

			if ( empty( $name ) ) {	// Just in case.
				return $placeholder;
			}

			if ( true === $placeholder ) {	// Use default value.

				if ( isset( $this->defaults[ $name ] ) ) {
					$placeholder = $this->defaults[ $name ];
				}
			}

			if ( true === $placeholder || '' === $placeholder ) {

				if ( ( $pos = strpos( $name, '#' ) ) > 0 ) {

					$key_default = SucomUtil::get_key_locale( substr( $name, 0, $pos ), $this->options, 'default' );

					if ( $name !== $key_default ) {

						if ( isset( $this->options[ $key_default ] ) ) {

							$placeholder = $this->options[ $key_default ];

						} elseif ( true === $placeholder ) {

							if ( isset( $this->defaults[ $key_default ] ) ) {
								$placeholder = $this->defaults[ $key_default ];
							}
						}
					}
				}
			}

			if ( true === $placeholder ) {
				$placeholder = '';	// Must be a string.
			}

			return $placeholder;
		}

		private function get_placeholder_events( $type = 'input', $placeholder = '' ) {

			if ( $placeholder === '' ) {
				return '';
			}

			$js_if_empty = 'if ( this.value == \'\' ) this.value = \'' . esc_js( $placeholder ) . '\';';
			$js_if_same  = 'if ( this.value == \'' . esc_js( $placeholder ) . '\' ) this.value = \'\';';

			$html = ' placeholder="' . esc_attr( $placeholder ) . '"' .
				' onClick="' . $js_if_empty . '"' .
				' onFocus="' . $js_if_empty . '"' .
				' onBlur="' . $js_if_same . '"';

			if ( $type === 'input' ) {
				$html .= ' onKeyPress="if ( event.keyCode === 13 ) { ' . $js_if_same . ' }"';
			}

			$html .= ' onMouseEnter="' . $js_if_empty . '"';
			$html .= ' onMouseLeave="' . $js_if_same . '"';

			return $html;
		}

		public function get_md_form_rows( array $table_rows, array $form_rows, array $head = array(), array $mod = array() ) {
		
			foreach ( $form_rows as $key => $val ) {

				if ( ! isset( $table_rows[ $key ] ) ) {
					$table_rows[ $key ] = '';
				}

				/**
				 * Placeholder.
				 */
				if ( empty( $val ) ) {
					continue;
				}

				/**
				 * Table cell HTML.
				 */
				if ( isset( $val[ 'table_row' ] ) ) {

					if ( ! empty( $val[ 'table_row' ] ) ) {

						$table_rows[ $key ] = empty( $val[ 'tr_class' ] ) ? '' : '<tr class="' . $val[ 'tr_class' ] . '">' . "\n";

						$table_rows[ $key ] .= $val[ 'table_row' ] . "\n";
					}

					continue;
				}

				$is_auto_draft = false;

				/**
				 * Do not show the option if the post status is empty or auto-draft.
				 */
				if ( ! empty( $val[ 'no_auto_draft' ] ) ) {
					if ( $is_auto_draft = SucomUtil::is_auto_draft( $mod ) ) {
						$val[ 'td_class' ] = empty( $val[ 'td_class' ] ) ? 'blank' : $val[ 'td_class' ] . ' blank';
					}
				}

				if ( empty( $val[ 'label' ] ) ) {	// Just in case.
					$val[ 'label' ] = '';
				}

				if ( ! empty( $val[ 'header' ] ) ) {

					$table_rows[ $key ] = empty( $val[ 'tr_class' ] ) ? '' : '<tr class="' . $val[ 'tr_class' ] . '">' . "\n";

					$table_rows[ $key ] .= '<td colspan="2"' . ( ! empty( $val[ 'td_class' ] ) ? ' class="' . $val[ 'td_class' ] . '"' : '' ) . '>';
					
					$table_rows[ $key ] .= '<' . $val[ 'header' ] . '>' . $val[ 'label' ] . '</' . $val[ 'header' ] . '>';
					
					$table_rows[ $key ] .= '</td>' . "\n";

				} else {

					$table_rows[ $key ] = empty( $val[ 'tr_class' ] ) ? '' : '<tr class="' . $val[ 'tr_class' ] . '">' . "\n";

					$table_rows[ $key ] .= $this->get_th_html( $val[ 'label' ], 
						( empty( $val[ 'th_class' ] ) ? '' : $val[ 'th_class' ] ),
						( empty( $val[ 'tooltip' ] ) ? '' : $val[ 'tooltip' ] )
					) . "\n";

					$table_rows[ $key ] .= '<td' . ( empty( $val[ 'td_class' ] ) ? '' : ' class="' . $val[ 'td_class' ] . '"' ) . '>';

					if ( $is_auto_draft ) {
						$table_rows[ $key ] .= '<em>' . __( 'Save a draft version or publish to update this value.', $this->text_domain ) . '</em>';
					} else {
						$table_rows[ $key ] .= empty( $val[ 'content' ] ) ? '' : $val[ 'content' ];
					}
							
					$table_rows[ $key ] .= '</td>' . "\n";
				}
			}

			return $table_rows;
		}

		public function get_th_html( $label = '', $css_class = '', $css_id = '', $atts = array() ) {

			if ( isset( $this->p->msgs ) ) {
				if ( empty( $css_id ) ) {
					$tooltip_index = 'tooltip-' . $label;
				} else {
					$tooltip_index = 'tooltip-' . $css_id;
				}
				$tooltip_text = $this->p->msgs->get( $tooltip_index, $atts );	// Text is esc_attr().
			} else {
				$tooltip_text = '';
			}

			if ( isset( $atts[ 'is_locale' ] ) ) {
				$label .= ' <span style="font-weight:normal;">(' . SucomUtil::get_locale() . ')</span>';
			}

			return '<th' .
				( empty( $atts[ 'th_colspan' ] ) ? '' : ' colspan="' . $atts[ 'th_colspan' ] . '"' ) .
				( empty( $atts[ 'th_rowspan' ] ) ? '' : ' rowspan="' . $atts[ 'th_rowspan' ] . '"' ) .
				( empty( $css_class ) ? '' : ' class="' . $css_class . '"' ) .
				( empty( $css_id ) ? '' : ' id="th_' . $css_id . '"' ) . '><p>' . $label .
				( empty( $tooltip_text ) ? '' : $tooltip_text ) . '</p></th>';
		}

		public function get_tr_hide( $in_view = 'basic', $opt_keys = array() ) {

			$css_class = self::get_css_class_hide( $in_view, $opt_keys );

			return empty( $css_class ) ? '' : '<tr class="' . $css_class . '">';
		}

		public function get_css_class_hide_img_dim( $in_view = 'basic', $opt_pre ) {

			foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $opt_key ) {
				$opt_keys[] = $opt_pre . '_' . $opt_key;
			}

			return self::get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide_vid_dim( $in_view = 'basic', $opt_pre ) {

			foreach ( array( 'width', 'height' ) as $opt_key ) {
				$opt_keys[] = $opt_pre . '_' . $opt_key;
			}

			return self::get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide_prefix( $in_view = 'basic', $opt_pre ) {

			$opt_keys = SucomUtil::get_opts_begin( $opt_pre, $this->options );

			return self::get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide( $in_view = 'basic', $opt_keys = array() ) {

			$css_class = 'hide_in_' . $in_view;

			if ( empty( $opt_keys ) ) {

				return $css_class;

			} elseif ( ! is_array( $opt_keys ) ) {

				$opt_keys = array( $opt_keys );

			} elseif ( SucomUtil::is_assoc( $opt_keys ) ) {

				$opt_keys = array_keys( $opt_keys );
			}

			foreach ( $opt_keys as $opt_key ) {

				if ( strpos( $opt_key, ':is' ) ) {	// Skip option flags.

					continue;
				}

				$def_key = false !== strpos( $opt_key, '#' ) ? preg_replace( '/#.*$/', '', $opt_key ) : $opt_key;

				if ( empty( $def_key ) ) {

					continue;

				} elseif ( ! isset( $this->options[ $opt_key ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'missing options key for ' . $opt_key );
					}

					continue;

				} elseif ( ! isset( $this->defaults[ $def_key ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'missing defaults key for ' . $def_key );
					}

					continue;

				} elseif ( $this->options[ $opt_key ] !== $this->defaults[ $def_key ] ) {

					return '';
				}
			}

			return $css_class;
		}
	}
}
