<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomForm' ) ) {

	class SucomForm {

		private $p;
		private $lca;
		private $opts_name          = null;
		private $menu_ext           = null;	// Lca or ext lowercase acronym.
		private $text_domain        = false;	// Lca or ext text domain.
		private $def_text_domain    = false;	// Lca text domain (fallback).
		private $show_hide_js_added = false;
		private $json_array_added   = array();

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
		public function get_checkbox( $name, $css_class = '', $css_id = '', $is_disabled = false, $force = null, $group = null ) {

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
			} elseif ( $this->in_defaults( $name ) ) {
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

			return $this->get_checkbox( $name, $css_class, $css_id, $is_disabled = true, $force, $group );
		}

		public function get_no_checkbox_options( $name, array $opts, $css_class = '', $css_id = '', $group = null ) {

			$force = empty( $opts[ $name ] ) ? 0 : 1;

			return $this->get_checkbox( $name, $css_class, $css_id, $is_disabled = true, $force, $group );
		}

		public function get_no_checkbox_comment( $name, $comment = '' ) {

			return $this->get_checkbox( $name, '', '', $is_disabled = true, null ) .
				( empty( $comment ) ? '' : ' ' . $comment );
		}

		public function get_td_no_checkbox( $name, $comment = '', $is_narrow = false ) {

			return '<td class="'.( $is_narrow ? 'checkbox ' : '' ) . 'blank">' . 
				$this->get_no_checkbox_comment( $name, $comment ) . '</td>';
		}

		/**
		 * Creates a vertical list (by default) of checkboxes. The $name_prefix is combined with the $values array names to
		 * create the checbox option name.
		 */
		public function get_checklist( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $is_assoc = null, $is_disabled = false ) {

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
			$html = '<div ' . ( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				' id="' . esc_attr( $input_id ) . '">' . "\n";

			foreach ( $values as $name_suffix => $label ) {

				if ( is_array( $label ) ) {	// Just in case.
					$label = implode( ', ', $label );
				}

				/**
				 * If the array is not associative (so a regular numbered array), then the label / description is
				 * used as the saved value.
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

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc, $is_disabled = true );
		}

		public function get_checklist_post_types( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $is_disabled = false ) {

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
		 *
		 * $is_disabled can be true, false, or an option value for the disabled select.
		 */
		public function get_select( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$is_disabled = false, $selected = false, $event_names = array(), $event_args = null ) {

			if ( empty( $name ) ) {
				return;
			}

			$filter_name = SucomUtil::sanitize_hookname( $this->lca . '_form_select_' . $name );
			$values      = apply_filters( $filter_name, $values );

			if ( ! is_array( $values ) ) {
				return;
			}

			if ( null === $is_assoc ) {
				$is_assoc  = SucomUtil::is_assoc( $values );
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$is_disabled = true;
			}

			if ( is_string( $event_names ) ) {
				$event_names = array( $event_names );
			} elseif ( ! is_array( $event_names ) ) {	// Ignore true, false, null, etc.
				$event_names = array();
			}

			$event_json = false;

			if ( in_array( 'on_focus_load_json', $event_names ) ) {
				if ( ! empty( $event_args ) && is_string( $event_args ) ) {
					$event_json = SucomUtil::sanitize_hookname( $this->lca . '_form_select_' . $event_args . '_json' );
				}
			}

			$html           = '';
			$row_id         = empty( $css_id ) ? 'tr_' . $name : 'tr_' . $css_id;
			$input_id       = empty( $css_id ) ? 'select_' . $name : 'select_' . $css_id;
			$in_options     = $this->in_options( $name );	// Optimize and call only once - returns true or false.
			$in_defaults    = $this->in_defaults( $name );	// Optimize and call only once - returns true or false.
			$selected_value = '';

			$select_opt_count = 0;	// Used to check for first option.
			$select_opt_added = 0;
			$select_opt_html  = '';
			$select_opt_arr   = array();
			$default_value    = '';
			$default_text     = '';

			foreach ( $values as $option_value => $label ) {

				$select_opt_count++;	// Used to check for first option.

				if ( is_array( $label ) ) {	// Just in case.
					$label = implode( ', ', $label );
				}

				/**
				 * If the array is not associative (so a regular numbered array), then the label / description is
				 * used as the saved value.
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
					case 'schema_img_max':

						if ( $label === 0 ) {
							$label_transl .= ' ' . $this->get_value_transl( '(no images)' );
						}

						break;

					case 'og_vid_max':
					case 'schema_vid_max':

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
				 * Save the option value and translated label for the JSON array before adding the "(default)"
				 * suffix.
				 */
				if ( $event_json ) {
					if ( empty( $this->json_array_added[ $event_json ] ) ) {
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

				if ( $is_selected_html || $select_opt_count === 1 ) {
					$selected_value = $option_value;
				}

				/**
				 * For disabled selects or JSON selects, only include the first and selected option(s).
				 */
				if ( ( ! $is_disabled && ! $event_json ) || $is_selected_html || $select_opt_count === 1 ) {

					$select_opt_html .= '<option value="' . esc_attr( $option_value ) . '"' . $is_selected_html . '>';
					$select_opt_html .= $label_transl;
					$select_opt_html .= '</option>' . "\n";

					$select_opt_added++; 
				}
			}

			$html .= "\n" . '<select ';
			$html .= $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= ' id="' . esc_attr( $input_id ) . '"';	// Always has a value.
			$html .= empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"';
			$html .= empty( $default_value ) ? '' : ' data-default-value="' . esc_attr( $default_value ) . '"';
			$html .= empty( $default_text ) ? '' : ' data-default-text="' . esc_attr( $default_text ) . '"';
			$html .= '>' . "\n";
			$html .= $select_opt_html;
			$html .= '<!-- ' . $select_opt_added . ' select options added -->' . "\n";
			$html .= '</select>' . "\n";

			foreach ( $event_names as $event_name ) {

				$html .= '<!-- event name: ' . $event_name . ' -->' . "\n";

				switch ( $event_name ) {

					case 'on_focus_show':

						$html .= '<script type="text/javascript">';
						$html .= 'jQuery( \'select#' . esc_js( $input_id ) . '\' ).on( \'focus\', function(){';
						$html .= 'jQuery(\'' . $event_args . '\').show();';
						$html .= '});';
						$html .= '</script>' . "\n";

						break;

					case 'on_focus_load_json':

						$html .= $this->get_event_load_json_script( $event_json, $select_opt_arr, $input_id );

						break;

					case 'on_focus_get_ajax':

						break;

					case 'on_change_redirect':

						/**
						 * The sucomSelectChangeRedirect() javascript function replaces "%%${name}%%" by the value selected.
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

						$html .= $this->get_show_hide_trigger_script();

						// No break.

					case 'on_change_unhide_rows':

						$html .= '<script type="text/javascript">';
						$html .= 'jQuery( \'select#' . esc_js( $input_id ) . '\' ).on( \'change\', function(){';
						$html .= 'sucomSelectChangeUnhideRows( \'hide_' . esc_js( $name ) . '\', \'hide_' . esc_js( $name ) . '_\' + this.value );';
						$html .= '});';
						$html .= '</script>' . "\n";

						$html .= '<!-- selected value: ' . $selected_value . ' -->' . "\n";

						/**
						 * If we have an option selected, unhide those rows. Test for a non-empty string to
						 * allow for a value of 0.
						 */
						if ( '' !== $selected_value ) {

							$hide_class = 'hide_' . esc_js( $name );
							$show_class = 'hide_' . esc_js( $name . '_' . $selected_value );

							$html .= '<script type="text/javascript">';

							if ( 'on_show_unhide_rows' === $event_name ) {

								$html .= 'jQuery( \'tr#' . esc_js( $row_id ) . '\' ).on( \'show\', function(){';
								$html .= 'sucomSelectChangeUnhideRows( \'' . $hide_class . '\', \'' . $show_class . '\' );';
								$html .= '});';

							} else {

								if ( SucomUtil::get_const( 'DOING_AJAX' ) ) {

									$html .= 'sucomSelectChangeUnhideRows( \'' . $hide_class . '\', \'' . $show_class . '\' );';

								} else {

									/**
									 * Use $(window).load() instead of $(document).ready() for the WordPress block editor.
									 */
									$html .= 'jQuery( window ).load( function(){';
									$html .= 'sucomSelectChangeUnhideRows( \'' . $hide_class . '\', \'' . $show_class . '\' );';
									$html .= '});';
								}
							}

							$html .= '</script>' . "\n";
						}

						break;
				}
			}

			return $html;
		}

		/**
		 * $is_disabled can be true, false, or a text string (ie. "WPSSO PLM required").
		 */
		public function get_select_multi( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$start_num = 0, $max_input = 10, $show_first = 3, $is_disabled = false ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num    = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$display = empty( $one_more ) && $key_num >= $show_first ? false : true;

				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;

				$opt_key      = $name . '_' . $key_num;
				$opt_disabled = $is_disabled || $this->get_options( $opt_key . ':is' ) === 'disabled' ? true : false;

				$input_class   = empty( $css_class ) ? '' : $css_class;
				$input_id      = empty( $css_id ) ? $opt_key : $css_id . '_' . $key_num;
				$input_id_prev = empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num;
				$input_id_next = empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num;
				$input_value   = $this->in_options( $opt_key ) ? $this->options[ $opt_key ] : '';

				if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {
					continue;
				}
				
				$html .= '<div class="multi_container select_multi" id="multi_' . esc_attr( $input_id ) . '"';
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";

				$html .= '<div class="multi_number"><p>' . ( $key_num + 1 ) . '.</p></div>' . "\n";

				$html .= '<div class="multi_input">' . "\n";

				/**
				 * $opt_disabled can be true, false, or an option value for the disabled select.
				 */
				$html .= $this->get_select( $opt_key, $values, $input_class, $input_id, $is_assoc,
					$opt_disabled, $input_value, 'on_focus_show', 'div#multi_' . esc_attr( $input_id_next ) );

				$html .= is_string( $is_disabled ) ? $is_disabled : '';	// Allow for requirement comment.

				$html .= '</div><!-- .multi_input -->' . "\n";

				$html .= '</div><!-- .multi_container -->' . "\n";

				$one_more = $input_value === 'none' || ( empty( $input_value ) && ! is_numeric( $input_value ) ) ? false : true;
			}

			return $html;
		}

		/**
		 * $is_disabled can be true or a text string (ie. "WPSSO PLM required").
		 */
		public function get_no_select_multi( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$repeat = 3, $is_disabled = true ) {

			$is_disabled = empty( $is_disabled ) ? true : $is_disabled;	// Allow for requirement comment.

			return $this->get_select_multi( $name, $values, $css_class, $css_id, $is_assoc,
				$start_num = 0, $repeat, $repeat, $is_disabled );
		}

		/**
		 * Add 'none' as the first array element. Always converts the array to associative.
		 */
		public function get_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$is_disabled = false, $selected = false, $event_names = array(), $event_args = null ) {

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
				$is_disabled, $selected, $event_names, $event_args );
		}

		public function get_no_select( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$selected = false, $event_names = array(), $event_args = null ) {
		
			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true, $selected, $event_names, $event_args );
		}

		public function get_no_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$selected = false, $event_names = array() ) {

			return $this->get_select_none( $name, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true, $selected, $event_names );
		}

		public function get_no_select_options( $name, array $opts, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $event_names = array(), $event_args = null ) {
		
			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : true;

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true, $selected, $event_names, $event_args );
		}

		/**
		 * The "hour-mins" class is always prefixed to the $css_class value.
		 *
		 * By default, the 'none' array elements is not added.
		 */
		public function get_select_time( $name, $css_class = '', $css_id = '',
			$is_disabled = false, $selected = false, $step_mins = 15, $add_none = false ) {

			static $local_cache = array();

			if ( empty( $local_cache[ $step_mins ] ) ) {
				$local_cache[ $step_mins ] = SucomUtil::get_hours_range( $start_secs = 0, $end_secs = DAY_IN_SECONDS,
					$step_secs = 60 * $step_mins, $label_format = 'H:i' );
			}

			$css_class   = trim( 'hour-mins ' . $css_class );
			$event_names = array( 'on_focus_load_json' );
			$event_args  = 'hour_mins_step_' . $step_mins;

			/**
			 * Set 'none' as the default value if no default is defined.
			 */
			if ( $add_none ) {

				$event_args .= '_add_none';

				if ( ! empty( $name ) && ! isset( $this->defaults[ $name ] ) ) {
					$this->defaults[ $name ] = 'none';
				}

				return $this->get_select_none( $name, $local_cache[ $step_mins ], $css_class, $css_id, $is_assoc = true,
					$is_disabled, $selected, $event_names, $event_args );
			}

			return $this->get_select( $name, $local_cache[ $step_mins ], $css_class, $css_id, $is_assoc = true,
				$is_disabled, $selected, $event_names, $event_args );
		}

		public function get_no_select_time( $name, $css_class = '', $css_id = '', $selected = false, $step_mins = 15, $add_none = false ) {
		
			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled = true, $selected, $step_mins, $add_none );
		}

		public function get_no_select_time_options( $name, array $opts, $css_class = '', $css_id = '', $step_mins = 15, $add_none = false ) {
		
			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : true;

			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled = true, $selected, $step_mins, $add_none );
		}

		/**
		 * The "timezone" class is always prefixed to the $css_class value.
		 */
		public function get_select_timezone( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false ) {

			$css_class   = trim( 'timezone ' . $css_class );
			$timezones   = timezone_identifiers_list();
			$event_names = array( 'on_focus_load_json' );
			$event_args  = 'timezones';

			if ( empty( $this->defaults[ $name ] ) ) {

				/**
				 * The timezone string will be empty if a UTC offset, instead of a city, has selected in the
				 * WordPress settings.
				 */
				$this->defaults[ $name ] = get_option( 'timezone_string' );

				if ( empty( $this->defaults[ $name ] ) ) {
					$this->defaults[ $name ] = 'UTC';
				}
			}

			return $this->get_select( $name, $timezones, $css_class, $css_id, $is_assoc = false, $is_disabled, $selected,
				$event_names, $event_args );
		}

		public function get_no_select_timezone( $name, $css_class = '', $css_id = '', $selected = false ) {

			/**
			 * The "timezone" class is always prefixed to the $css_class value.
			 */
			return $this->get_select_timezone( $name, $css_class, $css_id, $is_disabled = true, $selected );
		}

		public function get_select_country( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false ) {

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
					( $this->options[ $name ] !== 'none' && strlen( $this->options[ $name ] ) !== 2 ) ) {

					$selected = $this->defaults[ $name ];
				}
			}

			$values = array( 'none' => 'none' ) + SucomUtil::get_alpha2_countries();

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc = true, $is_disabled, $selected );
		}

		public function get_no_select_country( $name, $css_class = '', $css_id = '', $selected = false ) {

			return $this->get_select_country( $name, $css_class, $css_id, $is_disabled = true, $selected );
		}

		public function get_no_select_country_options( $name, array $opts, $css_class = '', $css_id = '' ) {

			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : false;

			return $this->get_select_country( $name, $css_class, $css_id, $is_disabled = true, $selected );
		}

		/**
		 * Text input field.
		 */
		public function get_input( $name, $css_class = '', $css_id = '', $len = 0, $placeholder = '',
			$is_disabled = false, $tabindex = null, $el_attr = '' ) {

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

				$html .= $this->get_textlen_script( 'text_' . $css_id );
			}

			$html .= '<input type="text" name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"';
			$html .= empty( $css_id ) ? ' id="text_' . esc_attr( $name ) . '"' : ' id="text_' . esc_attr( $css_id ) . '"';
			$html .= is_numeric( $tabindex ) ? '' : ' tabindex="' . esc_attr( $tabindex ) . '"';
			$html .= empty( $el_attr ) ? '' : ' ' . $el_attr;

			foreach ( $len as $key => $val ) {
				$html .= empty( $len[ $key ] ) ? '' : ' ' . $key . 'Length="' . esc_attr( $len[ $key ] ) . '"';
			}

			$html .= $this->get_placeholder_attrs( 'input', $placeholder );
			$html .= ' value="' . esc_attr( $value ) . '" />' . "\n";
			$html .= empty( $len ) ? '' : '<div id="text_' . esc_attr( $css_id ) . '-lenMsg"></div>' . "\n";

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

					$input_class = empty( $css_class ) ? '' : $css_class;
					$input_id    = empty( $css_id ) ? '' : $css_id . '_' . $key_num;

					$html .= '<div class="multi_container">' . "\n";

					$html .= '<div class="multi_number"><p>' . ( $key_num + 1 ) . '.</p></div>' . "\n";

					$html .= '<div class="multi_input">' . "\n";
				}

				$html .= '<input type="text" disabled="disabled"';

				$html .= empty( $input_class ) ? '' : ' class="' . esc_attr( $input_class ) . '"';

				$html .= empty( $input_id ) ? '' : ' id="text_' . esc_attr( $input_id ) . '"';

				/**
				 * Only show a placeholder and value for input field 0.
				 */
				if ( ! $key_num ) {

					if ( $placeholder ) {
						$html .= ' placeholder="' . esc_attr( $placeholder ) . '"';
					}

					$html .= ' value="' . esc_attr( $value ) . '"';
				}

				$html .= '/>' . "\n";

				if ( $max_input > 1 ) {

					$html .= '</div><!-- .multi_input -->' . "\n";

					$html .= '</div><!-- .multi_container -->' . "\n";
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

		public function get_input_date( $name = '', $css_class = '', $css_id = '', $min_date = '', $max_date = '', $is_disabled = false ) {

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

			return $this->get_input_date( $name, $css_class = '', $css_id = '', $min_date = '', $max_date = '', $is_disabled = true );
		}

		public function get_no_input_date_options( $name, $opts ) {

			$value = isset( $opts[ $name ] ) ? $opts[ $name ] : '';

			return $this->get_no_input_value( $value, 'datepicker', '', 'yyyy-mm-dd' );
		}

		public function get_input_image_upload( $name, $placeholder = '', $is_disabled = false, $el_attr = '' ) {

			$key_suffix  = '';
			$default_lib = 'wp';
			$media_libs  = array( 'wp' => 'Media Library' );
			$data        = array();

			if ( preg_match( '/^(.*)(_[0-9]+)$/', $name, $matches ) ) {
				$name       = $matches[1];
				$key_suffix = $matches[2];	// Mutiple numbered option.
			}

			$opt_key        = $name . $key_suffix;
			$opt_key_id     = $name . '_id' . $key_suffix;
			$opt_key_id_pre = $name . '_id_pre' . $key_suffix;

			if ( true === $this->p->avail[ 'media' ][ 'ngg' ] ) {
				$media_libs[ 'ngg' ] = 'NextGEN Gallery';
			}

			$lib_disabled = count( $media_libs ) <= 1 ? true : $is_disabled;

			if ( strpos( $placeholder, 'ngg-' ) === 0 ) {
				$default_lib = 'ngg';
				$placeholder = preg_replace( '/^ngg-/', '', $placeholder );
			}

			$input_pid = $this->get_input( $opt_key_id, 'pid', '', 0, $placeholder, $is_disabled, $tabindex = null, $el_attr );

			$select_lib = $this->get_select( $opt_key_id_pre, $media_libs, '', '', true, $lib_disabled, $default_lib );

			if ( ! empty( $this->options[ $opt_key_id ] ) &&
				( empty( $this->options[ $opt_key_id_pre ] ) ||
					$this->options[ $opt_key_id_pre ] === 'wp' ) ) {

				$data[ 'pid' ] = $this->options[ $opt_key_id ];

			} elseif ( $default_lib === 'wp' && ! empty( $placeholder ) ) {

				$data[ 'pid' ] = $placeholder;
			}

			if ( function_exists( 'wp_enqueue_media' ) ) {
				$upload_button = $this->get_button( 'Select Image',
					'sucom_image_upload_button button', $opt_key,
						'', false, $is_disabled, $data );
			} else {
				$upload_button = '';
			}

			return '<div class="img_upload">' . $input_pid . 'in&nbsp;' . $select_lib . '&nbsp;' . $upload_button . '</div>';
		}

		public function get_no_input_image_upload( $name, $placeholder = '' ) {

			return $this->get_input_image_upload( $name, $placeholder, $is_disabled = true );
		}

		public function get_input_image_dimensions( $name, $is_narrow = false, $is_disabled = false ) {

			$html = $this->get_input( $name . '_width', $css_class = 'short width', $css_id = '',
				$len = 0, $placeholder = '', $is_disabled ) . 'x&nbsp;';

			$html .= $this->get_input( $name . '_height', $css_class = 'short height', $css_id = '',
				$len = 0, $placeholder = '', $is_disabled ) . 'px' . ' ';

			$html .= _x( 'crop', 'option comment', $this->text_domain ) . ' ' .
				$this->get_checkbox( $name . '_crop', '', '', $is_disabled );

			if ( $is_narrow ) {
				$html .= ' <div class="img_crop_from is_narrow">';
			} else {
				$html .= ' <div class="img_crop_from">' . _x( 'from', 'option comment', $this->text_domain ) . ' ';
			}

			$html .= $this->get_input_image_crop_area( $name, $add_none = false, $is_disabled );

			$html .= '</div>';

			return $html;
		}

		public function get_no_input_image_dimensions( $name, $is_narrow = false ) {

			return $this->get_input_image_dimensions( $name, $is_narrow, $is_disabled = true );
		}

		public function get_input_image_crop_area( $name, $add_none = false, $is_disabled = false ) {

			$html = '';

			foreach ( array( 'crop_x', 'crop_y' ) as $key ) {

				$values = $this->p->cf[ 'form' ][ 'position_' . $key ];

				if ( $add_none ) {
					$html .= $this->get_select_none( $name . '_' . $key, $values, $css_class = 'crop-area',
						$css_id = '', $is_assoc = true, $is_disabled );
				} else {
					$html .= $this->get_select( $name . '_' . $key, $values, $css_class = 'crop-area',
						$css_id = '', $is_assoc = true, $is_disabled );
				}
			}

			return $html;
		}

		public function get_no_input_image_crop_area( $name, $add_none = false ) {

			return $this->get_input_image_crop_area( $name, $add_none = false, $is_disabled = true );
		}

		public function get_input_image_url( $name, $url = '' ) {

			$key_suffix = '';

			if ( preg_match( '/^(.*)(_[0-9]+)$/', $name, $matches ) ) {
				$name       = $matches[1];
				$key_suffix = $matches[2];
			}

			$opt_key_id  = $name . '_id' . $key_suffix;
			$opt_key_url = $name . '_url' . $key_suffix;

			if ( empty( $this->options[ $opt_key_id ] ) ) {

				$placeholder = SucomUtil::esc_url_encode( $url );
				$is_disabled = false;

			} else {
				$placeholder = '';
				$is_disabled = true;
			}

			return $this->get_input( $opt_key_url, 'wide', '', 0, $placeholder, $is_disabled );
		}

		public function get_input_video_dimensions( $name, $media_info = array(), $is_disabled = false ) {

			$placeholder_width  = '';
			$placeholder_height = '';

			if ( ! empty( $media_info ) && is_array( $media_info ) ) {
				$placeholder_width  = empty( $media_info[ 'vid_width' ] ) ? '' : $media_info[ 'vid_width' ];
				$placeholder_height = empty( $media_info[ 'vid_height' ] ) ? '' : $media_info[ 'vid_height' ];
			}

			return $this->get_input( $name . '_width', 'short width', '', 0, $placeholder_width, $is_disabled ) . 'x&nbsp;' .
				$this->get_input( $name . '_height', 'short height', '', 0, $placeholder_height, $is_disabled ) . 'px';
		}

		public function get_no_input_video_dimensions( $name, $media_info = array() ) {

			return $this->get_input_video_dimensions( $name, $media_info, $is_disabled = true );
		}

		public function get_input_video_url( $name, $url = '' ) {

			/**
			 * Disable if we have a custom video embed.
			 */
			$is_disabled = empty( $this->options[ $name . '_embed' ] ) ? false : true;

			return $this->get_input( $name . '_url', 'wide', '', 0, SucomUtil::esc_url_encode( $url ), $is_disabled );
		}

		public function get_input_multi( $name, $css_class = '', $css_id = '', $start_num = 0, $max_input = 20, $show_first = 5, $is_disabled = false ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num    = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$display = empty( $one_more ) && $key_num >= $show_first ? false : true;

				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;

				$opt_key      = $name . '_' . $key_num;
				$opt_disabled = $is_disabled || $this->get_options( $opt_key . ':is' ) === 'disabled' ? true : false;

				$input_class   = empty( $css_class ) ? '' : $css_class;
				$input_id      = empty( $css_id ) ? $opt_key : $css_id . '_' . $key_num;
				$input_id_prev = empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num;
				$input_id_next = empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num;
				$input_value   = $this->in_options( $opt_key ) ? $this->options[ $opt_key ] : '';

				if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {
					continue;
				}
				
				$el_attr = 'onFocus="if ( jQuery(\'input#text_' . $input_id_prev . '\').val().length ) { '.
					'jQuery(\'div#multi_' . esc_attr( $input_id_next ) . '\').show(); }"';

				$html .= '<div class="multi_container input_multi" id="multi_' . esc_attr( $input_id ) . '"';
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";

				$html .= '<div class="multi_number"><p>' . ( $key_num + 1 ) . '.</p></div>' . "\n";

				$html .= '<div class="multi_input">' . "\n";

				$html .= '<input type="text"' . ( $opt_disabled ? ' disabled="disabled"' : '' ) .
					' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"' .
					' class="' . esc_attr( $input_class ) . '"' .
					' id="text_' . esc_attr( $input_id ) . '"' .
					' value="' . esc_attr( $input_value ) . '"' .
					' ' . $el_attr . '/>' . "\n";

				$html .= '</div><!-- .multi_input -->' . "\n";

				$html .= '</div><!-- .multi_container -->' . "\n";

				$one_more = empty( $input_value ) && ! is_numeric( $input_value ) ? false : true;

			}

			return $html;
		}

		public function get_no_input_multi( $name, $css_class = '', $css_id = '', $start_num = 0, $max_input = 90, $show_first = 5 ) {

			return $this->get_input_multi( $name, $css_class, $css_id, $start_num, $max_input, $show_first, $is_disabled = true );
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
			 * The "hour-mins" class is always prefixed to the $css_class value.
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

		public function get_mixed_multi( $mixed, $css_class, $css_id, $start_num = 0, $max_input = 10, $show_first = 2, $is_disabled = false ) {

			if ( empty( $mixed ) ) {
				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num    = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$display = empty( $one_more ) && $key_num >= $show_first ? false : true;

				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;

				$multi_class   = trim( 'multi_container mixed_multi ' . $css_class );
				$multi_id      = $css_id . '_' . $key_num;
				$multi_id_prev = $css_id . '_' . $prev_num;
				$multi_id_next = $css_id . '_' . $next_num;

				$el_attr = 'onFocus="jQuery(\'div#multi_' . esc_attr( $multi_id_next ) . '\').show();"';

				$html .= '<div class="' . $multi_class . '" id="multi_' . esc_attr( $multi_id ) . '"';
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";

				$html .= '<div class="multi_number"><p>' . ( $key_num + 1 ) . '.</p></div>' . "\n";

				$html .= '<div class="multi_input">' . "\n";

				foreach ( $mixed as $name => $atts ) {

					$opt_key      = $name . '_' . $key_num;
					$opt_disabled = $is_disabled || $this->get_options( $opt_key . ':is' ) === 'disabled' ? true : false;

					$in_options  = $this->in_options( $opt_key );	// Optimize and call only once.
					$in_defaults = $this->in_defaults( $opt_key );	// Optimize and call only once.

					$input_title   = empty( $atts[ 'input_title' ] ) ? '' : $atts[ 'input_title' ];
					$input_class   = empty( $atts[ 'input_class' ] ) ? '' : $atts[ 'input_class' ];
					$input_id      = empty( $atts[ 'input_id' ] ) ? $opt_key : $atts[ 'input_id' ] . '_' . $key_num;
					$input_content = empty( $atts[ 'input_content' ] ) ? '' : $atts[ 'input_content' ];
					$input_values  = empty( $atts[ 'input_values' ] ) ? array() : $atts[ 'input_values' ];

					if ( ! empty( $atts[ 'event_names' ] ) ) {
						if ( is_array( $atts[ 'event_names' ] ) ) {
							$event_names = $atts[ 'event_names' ];
						} elseif ( is_string( $atts[ 'event_names' ] ) ) {
							$event_names = array( $atts[ 'event_names' ] );
						} elseif ( ! is_array( $event_names ) ) {	// Ignore true, false, null, etc.
							$event_names = array();
						}
					} else {
						$event_names = array();
					}

					$event_args = empty( $atts[ 'event_args' ] ) ? null : $atts[ 'event_args' ];
					$event_json = false;

					if ( in_array( 'on_focus_load_json', $event_names ) ) {
						if ( ! empty( $event_args ) && is_string( $event_args ) ) {
							$event_json = SucomUtil::sanitize_hookname( $this->lca . '_form_select_' . $event_args . '_json' );
						}
					}

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
						$html .= '<p class="multi_label">' . $atts[ 'input_label' ] . ':</p> ';
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
									$html .= '<p';
									$html .= ' class="' . esc_attr( $input_class ) . '"';
									$html .= ' id="' . esc_attr( $input_id ) . '"';
									$html .= ' ' . $el_attr . '>';
									$html .= vsprintf( $atts[ 'input_content' ], $radio_inputs );
									$html .= '</p>' . "\n";
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
									' ' . $el_attr . '/>' . "\n";

								$one_more = empty( $input_value ) && ! is_numeric( $input_value ) ? false : true;

								break;

							case 'textarea':

								$input_value = $in_options ? $this->options[ $opt_key ] : '';

								$html .= '<textarea ' . ( $opt_disabled ? ' disabled="disabled"' : '' ) .
									' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"' .
									' title="' . esc_attr( $input_title ) . '"' .
									' class="' . esc_attr( $input_class ) . '"' .
									' id="textarea_' . esc_attr( $input_id ) . '"' .
									( $this->get_placeholder_attrs( 'textarea', $placeholder ) ) .
									'>' . esc_attr( $input_value ) . '</textarea>' . "\n";

								$one_more = empty( $input_value ) && ! is_numeric( $input_value ) ? false : true;

								break;

							case 'select':

								$select_options = empty( $atts[ 'select_options' ] ) || 
									! is_array( $atts[ 'select_options' ] ) ?
										array() : $atts[ 'select_options' ];

								$select_selected = empty( $atts[ 'select_selected' ] ) ? null : $atts[ 'select_selected' ];
								$select_default  = empty( $atts[ 'select_default' ] ) ? null : $atts[ 'select_default' ];

								$is_assoc = SucomUtil::is_assoc( $select_options );

								$select_opt_count = 0;	// Used to check for first option.
								$select_opt_added = 0;
								$select_opt_html  = '';
								$select_opt_arr   = array();
								$default_value    = '';
								$default_text     = '';

								foreach ( $select_options as $option_value => $label ) {

									if ( is_array( $label ) ) {	// Just in case.
										$label = implode( ', ', $label );
									}

									/**
									 * If the array is not associative (so a regular numbered
									 * array), then the label / description is used as the
									 * saved value.
									 *
									 * Make sure option values are cast as strings for
									 * comparison.
									 */
									if ( $is_assoc ) {
										$option_value = (string) $option_value;
									} else {
										$option_value = (string) $label;
									}

									if ( $this->text_domain ) {
										$label_transl = $this->get_value_transl( $label );
									}

									/**
									 * Save the option value and translated label for the JSON
									 * array before adding the "(default)" suffix.
									 */
									if ( $event_json ) {
										if ( empty( $this->json_array_added[ $event_json ] ) ) {
											$select_opt_arr[ $option_value ] = $label_transl;
										}
									}

									/**
									 * Save the default value and its text so we can add them (as jquery data) to the select.
									 */
									if ( ( $in_defaults && $option_value === (string) $this->defaults[ $opt_key ] ) ||
										( $select_default !== null && $option_value === $select_default ) ) {

										$default_value = $option_value;
										$default_text  = $this->get_value_transl( '(default)' );

										$label_transl .= ' ' . $default_text;
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
									if ( ( ! $opt_disabled && ! $event_json ) || $is_selected_html || $select_opt_count === 1 ) {

										$select_opt_html .= '<option value="' . esc_attr( $option_value ) . '"' . $is_selected_html . '>';
										$select_opt_html .= $label_transl;
										$select_opt_html .= '</option>' . "\n";

										$select_opt_added++; 
									}
								}

								$html .= "\n" . '<select ';
								$html .= $opt_disabled ? ' disabled="disabled"' : '';
								$html .= ' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"';
								$html .= ' title="' . esc_attr( $input_title ) . '"';
								$html .= empty( $input_class ) ? '' : ' class="' . esc_attr( $input_class ) . '"';
								$html .= empty( $input_id ) ? '' : ' id="select_' . esc_attr( $input_id ) . '"';
								$html .= empty( $default_value ) ? '' : ' data-default-value="' . esc_attr( $default_value ) . '"';
								$html .= empty( $default_text ) ? '' : ' data-default-text="' . esc_attr( $default_text ) . '"';
								$html .= ' ' . $el_attr . '>' . "\n";
								$html .= $select_opt_html;
								$html .= '<!-- ' . $select_opt_added . ' select options added -->' . "\n";
								$html .= '</select>' . "\n";

								foreach ( $event_names as $event_name ) { 

									$html .= '<!-- event name: ' . $event_name . ' -->' . "\n";

									switch ( $event_name ) {

										case 'on_focus_load_json':

											$html .= $this->get_event_load_json_script( $event_json,
												$select_opt_arr, 'select_' . $input_id );

											break;
									}
								}

								break;

							case 'image':

								$html .= '<div tabindex="-1" ' . $el_attr . '>' . "\n";
								$html .= $this->get_input_image_upload( $opt_key, $placeholder, $is_disabled, $el_attr );
								$html .= '</div>' . "\n";

								break;
						}
					}
				}

				$html .= '</div><!-- .multi_input -->' . "\n";

				$html .= '</div><!-- .multi_container.mixed_multi -->' . "\n";
			}

			return $html;
		}

		public function get_no_mixed_multi( $mixed, $css_class, $css_id, $start_num = 0, $max_input = 10, $show_first = 2 ) {

			return $this->get_mixed_multi( $mixed, $css_class, $css_id, $start_num, $max_input, $show_first, $is_disabled = true );
		}

		public function get_video_dimensions_text( $name, $media_info ) {

			if ( ! empty( $this->options[ $name . '_width' ] ) && ! empty( $this->options[ $name . '_height' ] ) ) {

				return $this->options[ $name . '_width' ] . 'x' . $this->options[ $name . '_height' ] . 'px';

			} elseif ( ! empty( $media_info ) && is_array( $media_info ) ) {

				$def_width  = empty( $media_info[ 'vid_width' ] ) ? '' : $media_info[ 'vid_width' ];
				$def_height = empty( $media_info[ 'vid_height' ] ) ? '' : $media_info[ 'vid_height' ];

				if ( ! empty( $def_width ) && ! empty( $def_height ) ) {
					return $def_width . 'x' . $def_height . 'px';
				}
			}

			return '';
		}

		public function get_textarea( $name, $css_class = '', $css_id = '', $len = 0, $placeholder = '', $is_disabled = false ) {

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

				$html .= $this->get_textlen_script( 'textarea_' . $css_id );
			}

			$html .= '<textarea ' .
				( $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' ) .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? ' id="textarea_' . esc_attr( $name ) . '"' : ' id="textarea_' . esc_attr( $css_id ) . '"' ) .
				( empty( $len[ 'max' ] ) || $is_disabled ? '' : ' maxLength="' . esc_attr( $len[ 'max' ] ) . '"' ) .
				( empty( $len[ 'warn' ] ) || $is_disabled ? '' : ' warnLength="' . esc_attr( $len[ 'warn' ] ) . '"' ) .
				( empty( $len[ 'max' ] ) && empty( $len[ 'rows' ] ) ? '' : ( empty( $len[ 'rows' ] ) ?
					' rows="'.( round( $len[ 'max' ] / 100 ) + 1 ) . '"' : ' rows="' . $len[ 'rows' ] . '"' ) ) .
				( $this->get_placeholder_attrs( 'textarea', $placeholder ) ) . '>' . esc_attr( $value ) . '</textarea>' .
				( empty( $len[ 'max' ] ) || $is_disabled ? '' : ' <div id="textarea_' . esc_attr( $css_id ) . '-lenMsg"></div>' );

			return $html;
		}

		public function get_no_textarea( $name, $css_class = '', $css_id = '', $len = 0, $placeholder = '' ) {

			return $this->get_textarea( $name, $css_class, $css_id, $len, $placeholder, $is_disabled = true );
		}

		public function get_no_textarea_options( $name, array $opts, $css_class = '', $css_id = '', $len = 0, $placeholder = '' ) {

			$value = isset( $opts[ $name ] ) ? $opts[ $name ] : '';

			return $this->get_no_textarea_value( $value, $css_class, $css_id, $len, $placeholder );
		}

		public function get_no_textarea_value( $value = '', $css_class = '', $css_id = '', $len = 0, $placeholder = '' ) {

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

		public function get_button( $value, $css_class = '', $css_id = '', $url = '', $newtab = false, $is_disabled = false, $data = array() ) {

			if ( true === $newtab ) {
				$on_click = ' onClick="window.open(\'' . SucomUtil::esc_url_encode( $url ) . '\', \'_blank\');"';
			} else {
				$on_click = ' onClick="window.location.href = \'' . SucomUtil::esc_url_encode( $url ) . '\';"';
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

		public function get_md_form_rows( array $table_rows, array $form_rows, array $head = array(), array $mod = array() ) {

			foreach ( $form_rows as $key => $val ) {

				$table_rows[ $key ] = '';

				/**
				 * Placeholder.
				 */
				if ( empty( $val ) ) {
					continue;
				}

				if ( empty( $val[ 'label' ] ) ) {	// Just in case.
					$val[ 'label' ] = '';
				}

				if ( isset( $val[ 'tr_class' ] ) ) {
					$tr = '<tr class="' . $val[ 'tr_class' ] . '">' . "\n";
				} else {
					$tr = '';
				}

				/**
				 * Table cell HTML.
				 */
				if ( isset( $val[ 'table_row' ] ) ) {

					if ( ! empty( $val[ 'table_row' ] ) ) {
						$table_rows[ $key ] .= $tr . $val[ 'table_row' ] . "\n";
					}

					continue;
				}

				/**
				 * Do not show the option if the post status is empty or auto-draft.
				 */
				$is_auto_draft = false;

				if ( ! empty( $val[ 'no_auto_draft' ] ) ) {

					if ( $is_auto_draft = SucomUtil::is_auto_draft( $mod ) ) {

						$val[ 'td_class' ] = empty( $val[ 'td_class' ] ) ? 'blank' : $val[ 'td_class' ] . ' blank';
					}
				}

				$td_class = empty( $val[ 'td_class' ] ) ? '' : ' class="' . $val[ 'td_class' ] . '"';

				if ( ! empty( $val[ 'header' ] ) ) {

					$col_span = ' colspan="' . ( isset( $val[ 'col_span' ] ) ? $val[ 'col_span' ] : 2 ) . '"';

					$table_rows[ $key ] .= $tr . '<td' . $col_span . $td_class . '>';

					$table_rows[ $key ] .= '<' . $val[ 'header' ] . '>' . $val[ 'label' ] . '</' . $val[ 'header' ] . '>';

					$table_rows[ $key ] .= '</td>' . "\n";

				} else {

					$col_span = empty( $val[ 'col_span' ] ) ? '' : ' colspan="' . $val[ 'col_span' ] .'"';

					$table_rows[ $key ] .= $tr . $this->get_th_html( $val[ 'label' ], 
						( empty( $val[ 'th_class' ] ) ? '' : $val[ 'th_class' ] ),
						( empty( $val[ 'tooltip' ] ) ? '' : $val[ 'tooltip' ] )
					) . "\n";

					$table_rows[ $key ] .= '<td' . $col_span . $td_class . '>';

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

		public function get_tr_hide_img_dim( $in_view = 'basic', $name ) {

			$css_class = self::get_css_class_hide_img_dim( $in_view, $name );

			return empty( $css_class ) ? '' : '<tr class="' . $css_class . '">';
		}

		public function get_tr_hide_vid_dim( $in_view = 'basic', $name ) {

			$css_class = self::get_css_class_hide_vid_dim( $in_view, $name );

			return empty( $css_class ) ? '' : '<tr class="' . $css_class . '">';
		}

		public function get_css_class_hide_img_dim( $in_view = 'basic', $name ) {

			foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $opt_key ) {
				$opt_keys[] = $name . '_' . $opt_key;
			}

			return self::get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide_vid_dim( $in_view = 'basic', $name ) {

			foreach ( array( 'width', 'height' ) as $opt_key ) {
				$opt_keys[] = $name . '_' . $opt_key;
			}

			return self::get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide_prefix( $in_view = 'basic', $name ) {

			$opt_keys = SucomUtil::get_opts_begin( $name, $this->options );

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

					if ( $this->options[ $opt_key ] !== '' ) {
						return '';
					}

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

		public static function get_no_input_clipboard( $value, $css_class = 'wide', $css_id = '' ) {

			if ( empty( $css_id ) ) {
				$css_id = uniqid();
			}

			$html = '<input type="text"' .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				' id="text_' . esc_attr( $css_id ) . '"' .
				' value="' . esc_attr( $value ) . '" readonly' .
				' onFocus="this.select(); document.execCommand(\'Copy\',false,null);"' .
				' onMouseUp="return false;">';

			/**
			 * Dashicons are only available since WP v3.8
			 */
			global $wp_version;

			if ( version_compare( $wp_version, '3.8', '>=' ) ) {

				$html = '<div class="no_input_clipboard">' .
					'<div class="copy_button"><a href="" title="Copy to clipboard"' .
					' onClick="return sucomCopyInputId( \'text_' . esc_js( $css_id ) . '\');">' .
					'<span class="dashicons dashicons-clipboard"></span></a></div><!-- .copy_button -->' . "\n" .
					'<div class="copy_text">' . $html . '</div><!-- .copy_text -->' . "\n" .
					'</div><!-- .no_input_clipboard -->' . "\n";
			}

			return $html;
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

		private function get_placeholder_attrs( $type = 'input', $placeholder = '' ) {

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

		private function get_textlen_script( $css_id ) {

			return empty( $css_id ) ? '' : '
<script type="text/javascript">
	jQuery( document ).ready( function() {
		jQuery( \'#' . esc_js( $css_id ) . '\' ).focus( function() { sucomTextLen(\'' . esc_js( $css_id ) . '\'); } );
		jQuery( \'#' . esc_js( $css_id ) . '\' ).keyup( function() { sucomTextLen(\'' . esc_js( $css_id ) . '\'); } );
	});
</script>
';
		}

		private function get_event_load_json_script( $event_json, $select_opt_arr, $input_id ) {

			$html = '';

			if ( empty( $event_json ) || ! is_string( $event_json ) ) {	// Just in case.
				return $html;
			}

			/**
			 * Encode the PHP array to JSON only once per page load.
			 */
			if ( empty( $this->json_array_added[ $event_json ] ) ) {

				$this->json_array_added[ $event_json ] = true;

				$select_opt_json = SucomUtil::json_encode_array( $select_opt_arr );

				$html .= '<!-- adding ' . $event_json . ' json array -->' . "\n";
				$html .= '<script type="text/javascript">' . "\n";
				$html .= 'var ' . $event_json . ' = ' . $select_opt_json . ';' . "\n";
				$html .= '</script>' . "\n";

			} else {

				$html .= '<!-- ' . $event_json . ' json array already added -->' . "\n";
			}

			$input_id_esc = esc_js( $input_id );

			/**
			 * The hover event is also required for Firefox to render the option list correctly.
			 */
			$html .= '<script type="text/javascript">' . "\n";
			$html .= 'jQuery( \'select#' . $input_id_esc . ':not( .json_loaded )\' ).on( \'hover focus\', function(){';
			$html .= 'sucomSelectLoadJson( \'select#' . $input_id_esc . '\', \'' . $event_json . '\' );';
			$html .= '});' . "\n";
			$html .= '</script>' . "\n";

			return $html;
		}

		private function get_show_hide_trigger_script() {

			$html = '';

			if ( $this->show_hide_js_added ) {	// Only add the event script once.
				return $html;
			}

			$this->show_hide_js_added = true;

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

			return $html;
		}
	}
}
