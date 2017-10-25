<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomForm' ) ) {

	class SucomForm {

		private $p;
		private $lca;
		private $options_name = null;
		private $menu_ext = null;		// lca or ext lowercase acronym
		private $text_domain = false;		// lca or ext text domain
		private $default_text_domain = false;	// lca text domain (fallback)

		private static $cache = array();

		public $options = array();
		public $defaults = array();

		public function __construct( &$plugin, $opts_name, &$opts, &$def_opts, $menu_ext = '' ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log( 'form options name is '.$opts_name );
			}

			$this->lca = $this->p->cf['lca'];
			$this->options_name =& $opts_name;
			$this->options =& $opts;
			$this->defaults =& $def_opts;
			$this->menu_ext = empty( $menu_ext ) ? $this->lca : $menu_ext;	// lca or ext lowercase acronym
			$this->set_text_domain( $this->menu_ext );
			$this->set_default_text_domain( $this->lca );
		}

		public function get_options_name() {
			return $this->options_name;
		}

		public function get_menu_ext() {
			return $this->menu_ext;
		}

		public function get_text_domain() {
			return $this->text_domain;
		}

		public function get_default_text_domain() {
			return $this->default_text_domain;
		}

		public function set_text_domain( $maybe_ext ) {
			$this->text_domain = $this->get_plugin_text_domain( $maybe_ext );
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'form text domain set to '.$this->text_domain );
			}
		}

		public function set_default_text_domain( $maybe_ext ) {
			$this->default_text_domain = $this->get_plugin_text_domain( $maybe_ext );
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'form default text domain set to '.$this->default_text_domain );
			}
		}

		public function get_plugin_text_domain( $maybe_ext ) {
			return isset( $this->p->cf['plugin'][$maybe_ext]['text_domain'] ) ?
				$this->p->cf['plugin'][$maybe_ext]['text_domain'] : $maybe_ext;
		}

		public function get_value_transl( $value ) {
			if ( $this->text_domain ) {	// just in case
				$value_transl = _x( $value, 'option value', $this->text_domain );	// lca or ext text domain
				if ( $value === $value_transl && $this->text_domain !== $this->default_text_domain ) {
					$value_transl = _x( $value, 'option value', $this->default_text_domain );	// lca text domain
				}
				return $value_transl;
			} elseif ( $this->default_text_domain ) {
				return _x( $value, 'option value', $this->default_text_domain );	// lca text domain
			}
			return $value;
		}

		public function get_hidden( $name, $value = '', $is_checkbox = false ) {
			if ( empty( $name ) ) {
				return;	// just in case
			}

			// hide the current options value, unless one is given as an argument to the method
			if ( empty( $value ) && $value !== 0 && $this->in_options( $name ) ) {
				$value = $this->options[$name];
			}

			return ( $is_checkbox ? $this->get_hidden( 'is_checkbox_'.$name, 1, false ) : '' ).	// recurse
				'<input type="hidden" name="'.esc_attr( $this->options_name.'['.$name.']' ).'" value="'.esc_attr( $value ).'" />';
		}

		public function get_checkbox( $name, $class = '', $id = '', $disabled = false, $force = null ) {

			if ( empty( $name ) ) {
				return;	// just in case
			}

			if ( $this->get_options( $name.':is' ) === 'disabled' ) {
				$disabled = true;
			}

			if ( $force !== null ) {
				$checked = checked( $force, 1, false );
			} elseif ( $this->in_options( $name ) ) {
				$checked = checked( $this->options[$name], 1, false );
			} elseif ( $this->in_defaults( $name ) ) {
				$checked = checked( $this->defaults[$name], 1, false );
			} else {
				$checked = '';
			}

			$def_is = $this->in_defaults( $name ) && ! empty( $this->defaults[$name] ) ? 'checked' : 'unchecked';

			$title_transl = sprintf( $this->get_value_transl( 'default is %s' ), $this->get_value_transl( 'checked' ) ).
				( $disabled ? ' '.$this->get_value_transl( '(option disabled)' ) : '' );

			$html = ( $disabled ? '' : $this->get_hidden( 'is_checkbox_'.$name, 1, false ) ).
				'<input type="checkbox"'.
				( $disabled ? ' disabled="disabled"' : ' name="'.esc_attr( $this->options_name.'['.$name.']' ).'" value="1"' ).
				( empty( $class ) ? '' : ' class="'.esc_attr( $class ).'"' ).
				( empty( $id ) ? '' : ' id="checkbox_'.esc_attr( $id ).'"' ).
				$checked.' title="'.$title_transl.'" />';

			return $html;
		}

		public function get_no_checkbox( $name, $class = '', $id = '', $force = null ) {
			return $this->get_checkbox( $name, $class, $id, true, $force );
		}

		public function get_nocb_td( $name, $comment = '', $narrow = false ) {
			return '<td class="'.( $narrow ? 'checkbox ' : '' ).'blank">'.
				$this->get_nocb_cmt( $name, $comment ).'</td>';
		}

		public function get_nocb_cmt( $name, $comment = '' ) {
			return $this->get_checkbox( $name, '', '', true, null ).
				( empty( $comment ) ? '' : ' '.$comment );
		}

		public function get_post_type_checkboxes( $name_pre, $class = '', $id = '', $disabled = false, $force = null ) {
			$checkboxes = '';
			foreach ( $this->p->util->get_post_types( 'objects' ) as $pt ) {
				$checkboxes .= '<p>'.$this->get_checkbox( $name_pre.'_'.$pt->name, $class, $id, $disabled, $force ).
					' '.$pt->label.( empty( $pt->description ) ? '' : ' ('.$pt->description.')' ).'</p>';
			}
			return $checkboxes;
		}

		public function get_radio( $name, $values = array(), $class = '', $id = '', $is_assoc = null, $disabled = false ) {

			if ( empty( $name ) || ! is_array( $values ) ) {
				return;
			}

			if ( $this->get_options( $name.':is' ) === 'disabled' ) {
				$disabled = true;
			}

			if ( $is_assoc === null ) {
				$is_assoc = SucomUtil::is_assoc( $values );
			}

			$html = '';

			foreach ( $values as $val => $label ) {

				// if the array is NOT associative (so regular numbered array),
				// then the description is used as the saved value as well
				if ( $is_assoc === false ) {
					$val = $label;
				}

				if ( $this->text_domain ) {
					$label_transl = $this->get_value_transl( $label );
				}

				$html .= '<input type="radio"'.
					( $disabled ? ' disabled="disabled"' : ' name="'.esc_attr( $this->options_name.'['.$name.']' ).'" value="'.esc_attr( $val ).'"' ).
					( empty( $class ) ? '' : ' class="'.esc_attr( $class ).'"' ).
					( empty( $id ) ? '' : ' id="radio_'.esc_attr( $id ).'"' ).
					( $this->in_options( $name ) ? checked( $this->options[$name], $val, false ) : '' ).
					( $this->in_defaults( $name ) ? ' title="default is '.$values[$this->defaults[$name]].'"' : '' ).
					'/> '.$label_transl.'&nbsp;&nbsp;';
			}

			return $html;
		}

		public function get_no_radio( $name, $values = array(), $class = '', $id = '', $is_assoc = null ) {
			return $this->get_radio( $name, $values, $class, $id, $is_assoc, true );
		}

		public function get_select( $name, $values = array(), $class = '', $id = '',
			$is_assoc = null, $disabled = false, $selected = false, $on_change = false ) {

			if ( empty( $name ) ) {
				return;
			}

			$key = SucomUtil::sanitize_key( $name );	// just in case
			$values = apply_filters( $this->lca.'_form_select_'.$key, $values );

			if ( ! is_array( $values ) ) {
				return;
			}

			if ( $this->get_options( $name.':is' ) === 'disabled' ) {
				$disabled = true;
			}

			if ( $is_assoc === null ) {
				$is_assoc = SucomUtil::is_assoc( $values );
			}

			$html = '';
			$select_id = empty( $id ) ? 'select_'.$name : 'select_'.$id;
			$in_options = $this->in_options( $name );	// optimize and call only once
			$in_defaults = $this->in_defaults( $name );	// optimize and call only once

			if ( is_string( $on_change ) ) {
				switch ( $on_change ) {
					case 'redirect':
						$redirect_url = add_query_arg( array( $name => '%%'.$name.'%%' ),
							SucomUtil::get_prot().'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] );
						$html .= '<script type="text/javascript">'.
							'jQuery( function(){ jQuery("#'.esc_js( $select_id ).'").change( function(){ '.
								'sucomSelectChangeRedirect("'.esc_js( $name ).'", '.
									'this.value, "'.esc_url( $redirect_url ).'"); }); });</script>'."\n";
						break;

					case 'unhide_rows':
						$html .= '<script type="text/javascript">'.
							'jQuery( function(){ jQuery("#'.esc_js( $select_id ).'").change( function(){ '.
								'sucomSelectChangeUnhideRows("hide_'.esc_js( $name ).'", '.
									'"hide_'.esc_js( $name ).'_"+this.value); }); });</script>'."\n";

						// if we have an option selected, unhide those rows
						if ( $selected !== false ) {
							if ( $selected === true ) {
								if ( $in_options ) {
									$unhide = $this->options[$name];
								} elseif ( $in_defaults ) {
									$unhide = $this->defaults[$name];
								} else {
									$unhide = false;
								}
							} else {
								$unhide = $selected;
							}
							if ( $unhide !== true ) {	// just in case
								$html .= '<script type="text/javascript">'.
									'jQuery(document).ready( function(){ '.
										'sucomSelectChangeUnhideRows("hide_'.esc_js( $name ).'", '.
											'"hide_'.esc_js( $name.'_'.$unhide ).'"); });</script>'."\n";
							}
						}
						break;
				}
			}

			$html .= '<select '.
				( $disabled ? ' disabled="disabled"' : ' name="'.esc_attr( $this->options_name.'['.$name.']' ).'"' ).
				( empty( $class ) ? '' : ' class="'.esc_attr( $class ).'"' ).' id="'.esc_attr( $select_id ).'"'.'>'."\n";

			$select_options_count = 0;
			$select_options_shown = 0;

			foreach ( $values as $val => $label ) {

				$select_options_count++;

				// if the array is NOT associative (so regular numered array),
				// then the description is used as the saved value as well
				if ( $is_assoc === false ) {
					$val = $label;
				}

				if ( $this->text_domain ) {
					$label_transl = $this->get_value_transl( $label );
				}

				switch ( $name ) {
					case 'og_img_max':
						if ( $label === 0 ) {
							$label_transl .= ' '.$this->get_value_transl( '(no images)' );
						}
						break;
					case 'og_vid_max':
						if ( $label === 0 ) {
							$label_transl .= ' '.$this->get_value_transl( '(no videos)' );
						}
						break;
					default:
						if ( $label === '' || $label === 'none' ) {	// just in case
							$label_transl = $this->get_value_transl( '[None]' );
						}
						break;
				}

				if ( $in_defaults && $val === $this->defaults[$name] ) {
					$label_transl .= ' '.$this->get_value_transl( '(default)' );
				}

				if ( ! is_bool( $selected ) ) {
					$is_selected_html = selected( $selected, $val, false );
				} elseif ( $in_options ) {
					$is_selected_html = selected( $this->options[$name], $val, false );
				} elseif ( $in_defaults ) {
					$is_selected_html = selected( $this->defaults[$name], $val, false );
				} else {
					$is_selected_html = '';
				}

				// for disabled selects, only include the first and/or selected option
				if ( ! $disabled || $select_options_count === 1 || $is_selected_html ) {
					$html .= '<option value="'.esc_attr( $val ).'"'.$is_selected_html.'>'.$label_transl.'</option>'."\n";
					$select_options_shown++; 
				}
			}

			$html .= '<!-- '.$select_options_shown.' select options shown -->'."\n";
			$html .= '</select>'."\n";

			return $html;
		}

		public function get_no_select( $name, $values = array(), $class = '', $id = '', $is_assoc = null, $selected = false, $on_change = false ) {
			return $this->get_select( $name, $values, $class, $id, $is_assoc, true, $selected, $on_change );
		}

		public function get_select_timezone( $name, $class = '', $id = '', $disabled = false, $selected = false ) {
			$class = trim( 'timezone '.$class );
			$timezones = timezone_identifiers_list();
			if ( empty( $this->defaults[$name] ) ) {
				$this->defaults[$name] = get_option( 'timezone_string' );
			}
			return $this->get_select( $name, $timezones, $class, $id, $disabled, $selected );
		}

		public function get_no_select_timezone( $name, $class = '', $id = '', $selected = false ) {
			return $this->get_select_timezone( $name, $class, $id, true, $selected );
		}

		public function get_select_time( $name, $class = '', $id = '', $disabled = false, $selected = false, $step_mins = 30 ) {

			if ( empty( $name ) || ! isset( $this->defaults[$name] ) ) {
				$this->defaults[$name] = 'none';
			}

			$start_secs = 0;
			$end_secs = DAY_IN_SECONDS;
			$step_secs = 60 * $step_mins;
			$time_format = '';

			$times = SucomUtil::get_hours_range( $start_secs, $end_secs, $step_secs, $time_format );
			$class = trim( 'hour_mins '.$class );

			return $this->get_select( $name, array_merge( array( 'none' => '[None]' ), $times ),
				$class, $id, true, $disabled, $selected );
		}

		public function get_no_select_time( $name, $class = '', $id = '', $selected = false ) {
			return $this->get_select_time( $name, $class, $id, true, $selected );
		}

		public function get_select_country( $name, $class = '', $id = '', $disabled = false, $selected = false ) {

			if ( empty( $name ) || ! isset( $this->defaults[$name] ) ) {
				$this->defaults[$name] = 'none';
			}

			// sanity check for possibly older input field values
			if ( $selected === false ) {
				if ( empty( $this->options[$name] ) ||
					( $this->options[$name] !== 'none' && strlen( $this->options[$name] ) !== 2 ) ) {
					$selected = $this->defaults[$name];
				}
			}

			return $this->get_select( $name, array_merge( array( 'none' => '[None]' ),
				SucomUtil::get_alpha2_countries() ), $class, $id, true, $disabled, $selected );
		}

		public function get_no_select_country( $name, $class = '', $id = '', $selected = false ) {
			return $this->get_select_country( $name, $class, $id, true, $selected );
		}

		public function get_select_img_size( $name, $name_preg = '//', $invert = false ) {

			if ( empty( $name ) ) {
				return;	// just in case
			}

			$invert = $invert == false ? null : PREG_GREP_INVERT;
			$size_names = preg_grep( $name_preg, get_intermediate_image_sizes(), $invert );
			natsort( $size_names );

			$html = '<select name="'.esc_attr( $this->options_name.'['.$name.']' ).'">';
			$in_options = $this->in_options( $name );	// optimize and call only once
			$in_defaults = $this->in_defaults( $name );	// optimize and call only once

			foreach ( $size_names as $size_name ) {
				if ( ! is_string( $size_name ) ) {
					continue;
				}

				$size = SucomUtil::get_size_info( $size_name );
				$html .= '<option value="'.esc_attr( $size_name ).'" ';

				if ( $in_options ) {
					$html .= selected( $this->options[$name], $size_name, false );
				}

				$html .= '>'.esc_html( $size_name.' [ '.$size['width'].'x'.$size['height'].
					( $size['crop'] ? ' cropped' : '' ).' ]' );

				if ( $in_defaults && $size_name == $this->defaults[$name] ) {
					$html .= ' '.$this->get_value_transl( '(default)' );
				}

				$html .= '</option>';
			}

			$html .= '</select>';

			return $html;
		}

		public function get_input( $name, $class = '', $id = '', $len = 0, $placeholder = '', $disabled = false, $tabindex = 0 ) {

			if ( empty( $name ) ) {
				return;	// just in case
			}

			if ( $disabled || $this->get_options( $name.':is' ) === 'disabled' ) {
				return $this->get_no_input( $name, $class, $id, $placeholder );
			}

			$html = '';
			$value = $this->in_options( $name ) ? $this->options[$name] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );

			if ( ! is_array( $len ) ) {
				$len = array( 'max' => $len );
			}

			if ( ! empty( $len['max'] ) ) {
				if ( empty( $id ) ) {
					$id = $name;
				}
				$html .= $this->get_text_length_js( 'text_'.$id );
			}

			$html .= '<input type="text" name="'.esc_attr( $this->options_name.'['.$name.']' ).'"'.
				( empty( $class ) ? '' : ' class="'.esc_attr( $class ).'"' ).
				( empty( $id ) ? ' id="text_'.esc_attr( $name ).'"' : ' id="text_'.esc_attr( $id ).'"' ).
				( empty( $tabindex ) ? '' : ' tabindex="'.esc_attr( $tabindex ).'"' ).
				( empty( $len['max'] ) ? '' : ' maxLength="'.esc_attr( $len['max'] ).'"' ).
				( empty( $len['warn'] ) ? '' : ' warnLength="'.esc_attr( $len['warn'] ).'"' ).
				( $this->get_placeholder_events( 'input', $placeholder ) ).' value="'.esc_attr( $value ).'" />'.
				( empty( $len['max'] ) ? '' : ' <div id="text_'.esc_attr( $id ).'-lenMsg"></div>' );

			return $html;
		}

		public function get_mixed_multi( $mixed, $class, $id, $start_num = 0, $max_input = 10, $show_first = 2, $disabled = false ) {

			if ( empty( $mixed ) ) {
				return;	// just in case
			}

			$html = '';
			$display = true;
			$one_more = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$next_num = $key_num + 1;
				$wrap_id = $id.'_'.$key_num;
				$wrap_id_next = $id.'_'.$next_num;
				$display = empty( $one_more ) && $key_num >= $show_first ? false : true;

				$html .= '<div class="wrap_multi" id="wrap_'.esc_attr( $wrap_id ).'"'.
					( $display ? '' : ' style="display:none;"' ).'>'."\n";

				foreach ( $mixed as $name => $atts ) {

					$opt_key = $name.'_'.$key_num;
					$opt_disabled = $disabled || $this->get_options( $opt_key.':is' ) === 'disabled' ? true : false;
					$in_options = $this->in_options( $opt_key );	// optimize and call only once
					$in_defaults = $this->in_defaults( $opt_key );	// optimize and call only once
					$input_title = empty( $atts['input_title'] ) ? '' : $atts['input_title'];
					$input_class = empty( $atts['input_class'] ) ? 'multi' : 'multi '.$atts['input_class'];
					$input_id = empty( $atts['input_id'] ) ? $name.'_'.$key_num : $atts['input_id'].'_'.$key_num;
	
					if ( $disabled && $key_num >= $show_first && empty( $display ) ) {
						continue;
					}
	
					if ( ! empty( $atts['input_label'] ) ) {
						$html .= '<p style="display:inline">'.$atts['input_label'].'</p> ';
					}

					if ( isset( $atts['input_type'] ) ) {

						switch ( $atts['input_type'] ) {

							case 'text':

								$input_value = $in_options ? $this->options[$opt_key] : '';

								if ( $opt_disabled ) {
									$html .= $this->get_no_input( $opt_key, $input_class, $input_id );
								} else {
									$html .= '<input type="text"'.
										' name="'.esc_attr( $this->options_name.'['.$opt_key.']' ).'"'.
										' title="'.esc_attr( $input_title ).'"'.
										' class="'.esc_attr( $input_class ).'"'.
										' id="text_'.esc_attr( $input_id ).'"'.
										' value="'.esc_attr( $input_value ).'"'.
										' onFocus="jQuery(\'div#wrap_'.esc_attr( $wrap_id_next ).'\').show();" />'."\n";
								}

								$one_more = empty( $input_value ) ? false : true;

								break;

							case 'select':

								if ( $opt_disabled ) {
									$html .= '<select disabled="disabled"';
								} else {
									$html .= '<select name="'.esc_attr( $this->options_name.'['.$opt_key.']' ).'"';
								}

									
								$html .= ' title="'.esc_attr( $input_title ).'"'.
									' class="'.esc_attr( $input_class ).'"'.
									' id="select_'.esc_attr( $input_id ).'"'.
									' onFocus="jQuery(\'div#wrap_'.esc_attr( $wrap_id_next ).'\').show();">'."\n";

								$select_options = empty( $atts['select_options'] ) || 
									! is_array( $atts['select_options'] ) ?
										array() : $atts['select_options'];

								$select_selected = empty( $atts['select_selected'] ) ? null : $atts['select_selected'];
								$select_default = empty( $atts['select_default'] ) ? null : $atts['select_default'];
								$is_assoc = SucomUtil::is_assoc( $select_options );
								$select_options_count = 0;
								$select_options_shown = 0;

								foreach ( $select_options as $val => $label ) {

									$select_options_count++; 
									
									// if the array is NOT associative (so regular numered array),
									// then the description is used as the saved value as well
									if ( $is_assoc === false ) {
										$val = $label;
									}

									$label_transl = $this->get_value_transl( $label );

									if ( ( $in_defaults && $val === $this->defaults[$opt_key] ) ||
										( $select_default !== null && $val === $select_default ) ) {
										$label_transl .= ' '.$this->get_value_transl( '(default)' );
									}

									if ( $select_selected !== null ) {
										$is_selected_html = selected( $select_selected, $val, false );
									} elseif ( $in_options ) {
										$is_selected_html = selected( $this->options[$opt_key], $val, false );
									} elseif ( $select_default !== null ) {
										$is_selected_html = selected( $select_default, $val, false );
									} elseif ( $in_defaults ) {
										$is_selected_html = selected( $this->defaults[$opt_key], $val, false );
									} else {
										$is_selected_html = '';
									}

									// for disabled selects, only include the first and/or selected option
									if ( ! $opt_disabled || $select_options_count === 1 || $is_selected_html ) {
										$html .= '<option value="'.esc_attr( $val ).'"'.
											$is_selected_html.'>'.$label_transl.'</option>'."\n";
										$select_options_shown++; 
									}
								}
								
								$html .= '<!-- '.$select_options_shown.' select options shown -->'."\n";
								$html .= '</select>'."\n";

								break;
						}
					}
				}

				$html .= '</div>'."\n";
			}

			return $html;
		}

		public function get_no_mixed_multi( $mixed, $class, $id, $start_num = 0, $max_input = 10, $show_first = 2 ) {
			return $this->get_mixed_multi( $mixed, $class, $id, $start_num, $max_input, $show_first, true );
		}

		public function get_input_multi( $name, $class = '', $id = '', $start_num = 0, $max_input = 90, $show_first = 5, $disabled = false ) {

			if ( empty( $name ) ) {
				return;	// just in case
			}

			$html = '';
			$display = true;
			$one_more = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$next_num = $key_num + 1;
				$opt_key = $name.'_'.$key_num;
				$opt_disabled = $disabled || $this->get_options( $opt_key.':is' ) === 'disabled' ? true : false;
				$input_class = empty( $class ) ? 'multi' : 'multi '.$class;
				$input_id = empty( $id ) ? $name.'_'.$key_num : $id.'_'.$key_num;
				$input_id_next = empty( $id ) ? $name.'_'.$next_num : $id.'_'.$next_num;
				$input_value = $this->in_options( $opt_key ) ? $this->options[$opt_key] : '';
				$display = empty( $one_more ) && $key_num >= $show_first ? false : true;

				$html .= '<div class="wrap_multi" id="wrap_'.esc_attr( $input_id ).'"'.
					( $display ? '' : ' style="display:none;"' ).'>'."\n";

				if ( $disabled && $key_num >= $show_first && empty( $display ) ) {
					continue;
				} elseif ( $opt_disabled ) {
					$html .= $this->get_no_input( $opt_key, $input_class, $input_id );	// adds 'text_' to the id value
				} else {
					$html .= '<input type="text"'.
						' name="'.esc_attr( $this->options_name.'['.$opt_key.']' ).'"'.
						' class="'.esc_attr( $input_class ).'"'.
						' id="text_'.esc_attr( $input_id ).'"'.
						' value="'.esc_attr( $input_value ).'"'.
						' onFocus="jQuery(\'div#wrap_'.esc_attr( $input_id_next ).'\').show();" />'."\n";
				}

				$one_more = empty( $input_value ) ? false : true;
				$html .= '</div>'."\n";
			}

			return $html;
		}

		public function get_no_input_multi( $name, $class = '', $id = '', $start_num = 0, $max_input = 90, $show_first = 5, $disabled = false ) {
			return $this->get_input_multi( $name, $class, $id, $start_num, $max_input, $show_first, true );
		}

		public function get_input_color( $name = '', $class = '', $id = '', $disabled = false ) {

			if ( empty( $name ) ) {
				$value = '';
				$disabled = true;
			} else {
				$value = $this->in_options( $name ) ? $this->options[$name] : '';
				if ( $this->get_options( $name.':is' ) === 'disabled' ) {
					$disabled = true;
				}
			}

			return '<input type="text"'.
				( $disabled ? ' disabled="disabled"' : ' name="'.esc_attr( $this->options_name.'['.$name.']' ).'"' ).
				( empty( $class ) ? ' class="colorpicker"' : ' class="colorpicker '.esc_attr( $class ).'"' ).
				( empty( $id ) ? ' id="text_'.esc_attr( $name ).'"' : ' id="text_'.esc_attr( $id ).'"' ).
				' placeholder="#000000" value="'.esc_attr( $value ).'" />';
		}

		public function get_input_date( $name = '', $class = '', $id = '', $min_date = '', $max_date = '', $disabled = false ) {

			if ( empty( $name ) ) {
				$value = '';
				$disabled = true;
			} else {
				$value = $this->in_options( $name ) ? $this->options[$name] : '';
				if ( $this->get_options( $name.':is' ) === 'disabled' ) {
					$disabled = true;
				}
			}

			return '<input type="text"'.
				( $disabled ? ' disabled="disabled"' : ' name="'.esc_attr( $this->options_name.'['.$name.']' ).'"' ).
				( empty( $class ) ? ' class="datepicker"' : ' class="datepicker '.esc_attr( $class ).'"' ).
				( empty( $id ) ? ' id="text_'.esc_attr( $name ).'"' : ' id="text_'.esc_attr( $id ).'"' ).
				( empty( $min_date ) ? '' : ' min="'.esc_attr( $min_date ).'"' ).
				( empty( $max_date ) ? '' : ' max="'.esc_attr( $max_date ).'"' ).
				' placeholder="yyyy-mm-dd" value="'.esc_attr( $value ).'" />';
		}

		public function get_no_input_date( $name = '' ) {
			return $this->get_input_date( $name, '', '', '', '', true );
		}

		public function get_no_input_date_options( $name, &$opts ) {
			$value = isset( $opts[$name] ) ? $opts[$name] : '';
			return $this->get_no_input_value( $value, 'datepicker', '', 'yyyy-mm-dd' );
		}

		public function get_no_input_options( $name, &$opts, $class = '', $id = '', $placeholder = '' ) {
			$value = isset( $opts[$name] ) ? $opts[$name] : '';
			return $this->get_no_input_value( $value, $class, $id, $placeholder );
		}

		public function get_no_input_value( $value = '', $class = '', $id = '', $placeholder = '', $max_input = 1 ) {

			$html = '';
			$end_num = $max_input > 0 ? $max_input - 1 : 0;
			$input_class = empty( $class ) ? 'multi' : 'multi '.$class;
			$input_id = empty( $id ) ? '' : $id;

			foreach ( range( 0, $end_num, 1 ) as $key_num ) {
				if ( $max_input > 1 ) {
					$input_id = empty( $id ) ? '' : $id.'_'.$key_num;
					$html .= '<div class="wrap_multi">'."\n";
				}

				$html .= '<input type="text" disabled="disabled"'.
					( empty( $input_class ) ? '' : ' class="'.esc_attr( $input_class ).'"' ).
					( empty( $input_id ) ? '' : ' id="text_'.esc_attr( $input_id ).'"' ).
					( $placeholder === '' ? '' : ' placeholder="'.esc_attr( $placeholder ).'"' ).
					' value="'.esc_attr( $value ).'" />'."\n";

				if ( $max_input > 1 ) {
					$html .= '</div>'."\n";
				}
			}

			return $html;
		}

		public function get_no_input( $name = '', $class = '', $id = '', $placeholder = '' ) {
			$html = '';
			$value = $this->in_options( $name ) ? $this->options[$name] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );
			if ( ! empty( $name ) ) {
				$html .= $this->get_hidden( $name );
			}
			$html .= $this->get_no_input_value( $value, $class, $id, $placeholder );
			return $html;
		}

		// deprecated on 2017/09/03
		public function get_image_upload_input( $opt_prefix, $placeholder = '', $disabled = false ) {
			return $this->get_input_image_upload( $opt_prefix, $placeholder, $disabled );
		}

		public function get_input_image_upload( $opt_prefix, $placeholder = '', $disabled = false ) {
			$opt_suffix = '';
			$select_lib = 'wp';
			$media_libs = array( 'wp' => 'Media Library' );

			if ( preg_match( '/^(.*)(_[0-9]+)$/', $opt_prefix, $matches ) ) {
				$opt_prefix = $matches[1];
				$opt_suffix = $matches[2];
			}

			if ( $this->p->avail['media']['ngg'] === true ) {
				$media_libs['ngg'] = 'NextGEN Gallery';
			}

			if ( strpos( $placeholder, 'ngg-' ) === 0 ) {
				$select_lib = 'ngg';
				$placeholder = preg_replace( '/^ngg-/', '', $placeholder );
			}

			$input_id = $this->get_input( $opt_prefix.'_id'.$opt_suffix,
				'short', '', 0, $placeholder, $disabled );

			$select_lib = $this->get_select( $opt_prefix.'_id_pre'.$opt_suffix,
				$media_libs, '', '', true, ( count( $media_libs ) <= 1 ? true : $disabled ),	// disable if only 1 media lib
					$select_lib );

			$button_ul = function_exists( 'wp_enqueue_media' ) ? 
				$this->get_button( 'Select or Upload Image',
					'sucom_image_upload_button button', $opt_prefix.$opt_suffix,	// css id used to set values and disable image url
						'', false, $disabled ) : '';

			return '<div class="img_upload">'.
				$input_id.'&nbsp;in&nbsp;'.
				$select_lib.'&nbsp;'.
				$button_ul.
				'</div>';
		}

		// deprecated on 2017/09/03
		public function get_no_image_upload_input( $opt_prefix, $placeholder = '' ) {
			return $this->get_input_image_upload( $opt_prefix, $placeholder, true );
		}

		public function get_no_input_image_upload( $opt_prefix, $placeholder = '' ) {
			return $this->get_input_image_upload( $opt_prefix, $placeholder, true );
		}

		// deprecated on 2017/09/03
		public function get_image_url_input( $opt_prefix, $url = '' ) {
			return $this->get_input_image_url( $opt_prefix, $url );
		}

		public function get_input_image_url( $opt_prefix, $url = '' ) {
			$opt_suffix = '';

			if ( preg_match( '/^(.*)(_[0-9]+)$/', $opt_prefix, $matches ) ) {
				$opt_prefix = $matches[1];
				$opt_suffix = $matches[2];
			}

			// disable if we have a custom image id
			$disabled = empty( $this->options[$opt_prefix.'_id'.$opt_suffix] ) ? false : true;

			return $this->get_input( $opt_prefix.'_url'.$opt_suffix,
				'wide', '', 0, SucomUtil::esc_url_encode( $url ), $disabled );
		}

		// deprecated on 2017/09/03
		public function get_video_url_input( $opt_prefix, $url = '' ) {
			return $this->get_input_video_url( $opt_prefix, $url );
		}

		public function get_input_video_url( $opt_prefix, $url = '' ) {
			// disable if we have a custom video embed
			$disabled = empty( $this->options[$opt_prefix.'_embed'] ) ? false : true;

			return $this->get_input( $opt_prefix.'_url', 'wide', '', 0,
				SucomUtil::esc_url_encode( $url ), $disabled );
		}

		// deprecated on 2017/09/03
		public function get_image_dimensions_input( $name, $use_opts = false, $narrow = false, $disabled = false ) {
			return $this->get_input_image_dimensions( $name, $use_opts, $narrow, $disabled );
		}

		public function get_input_image_dimensions( $name, $use_opts = false, $narrow = false, $disabled = false ) {

			$def_width = '';
			$def_height = '';
			$crop_area_select = '';

			// $use_opts = true when used for post / user meta forms (to show default values)
			if ( $use_opts === true ) {

				$def_width = empty( $this->p->options[$name.'_width'] ) ?
					'' : $this->p->options[$name.'_width'];

				$def_height = empty( $this->p->options[$name.'_height'] ) ?
					'' : $this->p->options[$name.'_height'];

				foreach ( array( 'crop', 'crop_x', 'crop_y' ) as $key ) {
					if ( ! $this->in_options( $name.'_'.$key ) && $this->in_defaults( $name.'_'.$key ) ) {
						$this->options[$name.'_'.$key] = $this->defaults[$name.'_'.$key];
					}
				}
			}

			// crop area selection is only available since wp v3.9
			global $wp_version;
			if ( version_compare( $wp_version, 3.9, '>=' ) ) {

				$crop_area_select .= $narrow === true ?
					' <div class="img_crop_from is_narrow">' :
					' <div class="img_crop_from">from';

				foreach ( array( 'crop_x', 'crop_y' ) as $key ) {
					$crop_area_select .= ' '.$this->get_select( $name.'_'.$key,
						$this->p->cf['form']['position_'.$key], 'medium', '', true, $disabled );
				}

				$crop_area_select .= '</div>';
			}

			return $this->get_input( $name.'_width', 'short', '', 0, $def_width, $disabled ).'x'.
				$this->get_input( $name.'_height', 'short', '', 0, $def_height, $disabled ).
				'px crop '.$this->get_checkbox( $name.'_crop', '', '', $disabled ).$crop_area_select;
		}

		// deprecated on 2017/09/03
		public function get_no_image_dimensions_input( $name, $use_opts = false, $narrow = false ) {
			return $this->get_input_image_dimensions( $name, $use_opts, $narrow, true );
		}

		public function get_no_input_image_dimensions( $name, $use_opts = false, $narrow = false ) {
			return $this->get_input_image_dimensions( $name, $use_opts, $narrow, true );
		}

		public function get_image_dimensions_text( $name, $use_opts = false ) {

			if ( ! empty( $this->options[$name.'_width'] ) &&
				! empty( $this->options[$name.'_height'] ) ) {

				return $this->options[$name.'_width'].' x '.$this->options[$name.'_height'].
					( $this->options[$name.'_crop'] ? ', cropped' : '' );

			} elseif ( $use_opts === true ) {

				if ( ! empty( $this->p->options[$name.'_width'] ) &&
					! empty( $this->p->options[$name.'_height'] ) ) {

					return $this->p->options[$name.'_width'].' x '.$this->p->options[$name.'_height'].
						( $this->p->options[$name.'_crop'] ? ', cropped' : '' );
				}
			}

			return;
		}

		// deprecated on 2017/09/03
		public function get_copy_input( $value, $class = 'wide', $id = '' ) {
			$this->get_input_copy_clipboard( $value, $class, $id );
		}

		public function get_input_copy_clipboard( $value, $class = 'wide', $id = '' ) {
			if ( empty( $id ) ) {
				$id = uniqid();
			}
			$input = '<input type="text"'.
				( empty( $class ) ? '' : ' class="'.esc_attr( $class ).'"' ).
				( empty( $id ) ? '' : ' id="text_'.esc_attr( $id ).'"' ).
				' value="'.esc_attr( $value ).'" readonly'.
				' onFocus="this.select(); document.execCommand( \'Copy\', false, null );"'.
				' onMouseUp="return false;">';

			if ( ! empty( $id ) ) {
				global $wp_version;
				// dashicons are only available since wp v3.8
				if ( version_compare( $wp_version, 3.8, '>=' ) ) {
					$html = '<div class="clipboard"><div class="copy_button">'.
						'<a class="outline" href="" title="Copy to clipboard"'.
						' onClick="return sucomCopyInputId( \'text_'.esc_js( $id ).'\');">'.
						'<span class="dashicons dashicons-clipboard"></span></a>'.
						'</div><div class="copy_text">'.$input.'</div></div>';
				}
			} else {
				$html = $input;
			}
			return $html;
		}

		public function get_textarea( $name, $class = '', $id = '', $len = 0, $placeholder = '', $disabled = false ) {

			if ( empty( $name ) ) {
				return;	// just in case
			}

			if ( $this->get_options( $name.':is' ) === 'disabled' ) {
				$disabled = true;
			}

			$html = '';
			$value = $this->in_options( $name ) ? $this->options[$name] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );

			if ( ! is_array( $len ) ) {
				$len = array( 'max' => $len );
			}

			if ( ! empty( $len['max'] ) ) {
				if ( empty( $id ) ) {
					$id = $name;
				}
				$html .= $this->get_text_length_js( 'textarea_'.$id );
			}

			$html .= '<textarea '.
				( $disabled ? ' disabled="disabled"' : ' name="'.esc_attr( $this->options_name.'['.$name.']' ).'"' ).
				( empty( $class ) ? '' : ' class="'.esc_attr( $class ).'"' ).
				( empty( $id ) ? ' id="textarea_'.esc_attr( $name ).'"' : ' id="textarea_'.esc_attr( $id ).'"' ).
				( empty( $len['max'] ) || $disabled ? '' : ' maxLength="'.esc_attr( $len['max'] ).'"' ).
				( empty( $len['warn'] ) || $disabled ? '' : ' warnLength="'.esc_attr( $len['warn'] ).'"' ).
				( empty( $len['max'] ) && empty( $len['rows'] ) ? '' : ( empty( $len['rows'] ) ?
					' rows="'.( round( $len['max'] / 100 ) + 1 ).'"' : ' rows="'.$len['rows'].'"' ) ).
				( $this->get_placeholder_events( 'textarea', $placeholder ) ).'>'.esc_attr( $value ).'</textarea>'.
				( empty( $len['max'] ) || $disabled ? '' : ' <div id="textarea_'.esc_attr( $id ).'-lenMsg"></div>' );

			return $html;
		}

		public function get_no_textarea( $name, $class = '', $id = '', $len = 0, $placeholder = '' ) {
			return $this->get_textarea( $name, $class, $id, $len, $placeholder, true );
		}

		public function get_no_textarea_value( $value = '', $class = '', $id = '', $len = 0, $placeholder = '' ) {
			return '<textarea disabled="disabled"'.
				( empty( $class ) ? '' : ' class="'.esc_attr( $class ).'"' ).
				( empty( $id ) ? '' : ' id="textarea_'.esc_attr( $id ).'"' ).
				( empty( $len ) ? '' : ' rows="'.( round( $len / 100 ) + 1 ).'"' ).
				'>'.esc_attr( $value ).'</textarea>';
		}

		public function get_button( $value, $class = '', $id = '', $url = '', $newtab = false, $disabled = false, $data = array() ) {

			$on_click = $newtab === true ?
				' onclick="window.open(\''.esc_url( $url ).'\', \'_blank\');"' :
				' onclick="location.href=\''.esc_url( $url ).'\';"';

			$data_attr = '';
			if ( is_array( $data ) ) {
				foreach ( $data as $key => $val ) {
					$data_attr .= ' data-'.$key.'="'.esc_attr( $val ).'"';
				}
			}

			$html = '<input type="button"'.
				( $disabled ? ' disabled="disabled"' : '' ).
				( empty( $class ) ? '' : ' class="'.esc_attr( $class ).'"' ).
				( empty( $id ) ? '' : ' id="button_'.esc_attr( $id ).'"' ).
				( empty( $url ) || $disabled ? '' : $on_click ).
				' value="'.esc_attr( $value ).'"'.$data_attr.' />';

			return $html;
		}

		public function get_options( $idx = false, $def_val = null ) {
			if ( $idx !== false ) {
				if ( isset( $this->options[$idx] ) ) {
					return $this->options[$idx];
				} else {
					return $def_val;
				}
			} else {
				return $this->options;
			}
		}

		public function in_options( $idx, $is_preg = false ) {
			if ( $is_preg ) {
				if ( ! is_array( $this->options ) ) {
					return false;
				}
				$opts = SucomUtil::preg_grep_keys( $idx, $this->options );
				return ( ! empty( $opts ) ) ? true : false;
			} else {
				return isset( $this->options[$idx] ) ? true : false;
			}
		}

		public function in_defaults( $idx ) {
			return isset( $this->defaults[$idx] ) ? true : false;
		}

		private function get_text_length_js( $id ) {
			return empty( $id ) ?
				'' : '<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery(\'#'.esc_js( $id ).'\').focus(function(){ sucomTextLen(\''.esc_js( $id ).'\'); });
					jQuery(\'#'.esc_js( $id ).'\').keyup(function(){ sucomTextLen(\''.esc_js( $id ).'\'); });
				});</script>';
		}

		private function get_placeholder_sanitized( $name, $placeholder ) {

			if ( empty( $name ) ) {
				return $placeholder;	// just in case
			}

			if ( $placeholder === true ) {	// use default value
				if ( isset( $this->defaults[$name] ) ) {
					$placeholder = $this->defaults[$name];
				}
			}

			if ( $placeholder === true || $placeholder === '' ) {
				if ( ( $pos = strpos( $name, '#' ) ) > 0 ) {
					$key_default = SucomUtil::get_key_locale( substr( $name, 0, $pos ), $this->options, 'default' );
					if ( $name !== $key_default ) {
						if ( isset( $this->options[$key_default] ) ) {
							$placeholder = $this->options[$key_default];
						} elseif ( $placeholder === true && isset( $this->defaults[$key_default] ) ) {
							$placeholder = $this->defaults[$key_default];
						}
					}
				}
			}

			if ( $placeholder === true ) {
				$placeholder = '';	// must be a string
			}

			return $placeholder;
		}

		private function get_placeholder_events( $type = 'input', $placeholder ) {

			if ( $placeholder === '' ) {
				return '';
			}

			$js_if_empty = 'if ( this.value == \'\' ) this.value = \''.esc_js( $placeholder ).'\';';
			$js_if_same = 'if ( this.value == \''.esc_js( $placeholder ).'\' ) this.value = \'\';';

			$html = ' placeholder="'.esc_attr( $placeholder ).'"'.
				' onFocus="'.$js_if_empty.'"'.
				' onBlur="'.$js_if_same.'"';

			if ( $type === 'input' ) {
				$html .= ' onKeyPress="if ( event.keyCode === 13 ){ '.$js_if_same.' }"';
			} elseif ( $type === 'textarea' ) {
				$html .= ' onMouseOut="'.$js_if_same.'"';
			}

			return $html;
		}

		public function get_md_form_rows( &$table_rows, &$form_rows, &$head, &$mod,

			$auto_draft_msg = 'Save a draft version or publish to update this value.' ) {

			foreach ( $form_rows as $key => $val ) {
				if ( empty( $val ) ) {
					$table_rows[$key] = '';	// placeholder
					continue;
				}

				if ( ! empty( $val['no_auto_draft'] ) &&
					( empty( $mod['post_status'] ) || $mod['post_status'] === 'auto-draft' ) ) {
					$is_auto_draft = true;
					$val['td_class'] = empty( $val['td_class'] ) ?
						'blank' : $val['td_class'].' blank';
				} else {
					$is_auto_draft = false;
				}

				if ( ! empty( $val['header'] ) ) {	// example: h4 subsection
					$table_rows[$key] = ( ! empty( $val['tr_class'] ) ? '<tr class="'.$val['tr_class'].'">'."\n" : '' ).
						'<td></td><td'.( ! empty( $val['td_class'] ) ? ' class="'.$val['td_class'].'"' : '' ).
						'><'.$val['header'].'>'.$val['label'].'</'.$val['header'].'></td>'."\n";
				} else {
					$table_rows[$key] = ( ! empty( $val['tr_class'] ) ? '<tr class="'.$val['tr_class'].'">'."\n" : '' ).
						$this->get_th_html( $val['label'], ( ! empty( $val['th_class'] ) ? $val['th_class'] : '' ),
							( ! empty( $val['tooltip'] ) ? $val['tooltip'] : '' ) )."\n".
						'<td'.( ! empty( $val['td_class'] ) ? ' class="'.$val['td_class'].'"' : '' ).'>'.
						( $is_auto_draft ? '<em>'.$auto_draft_msg.'</em>' : ( ! empty( $val['content'] ) ? 
							$val['content'] : '' ) ).'</td>'."\n";
				}
			}

			return $table_rows;
		}

		public function get_th_html( $title = '', $class = '', $css_id = '', $atts = array() ) {

			if ( isset( $this->p->msgs ) ) {
				if ( empty( $css_id ) ) {
					$tooltip_index = 'tooltip-'.$title;
				} else {
					$tooltip_index = 'tooltip-'.$css_id;
				}
				$tooltip_text = $this->p->msgs->get( $tooltip_index, $atts );	// text is esc_attr()
			} else {
				$tooltip_text = '';
			}

			if ( isset( $atts['is_locale'] ) ) {
				$title .= ' <span style="font-weight:normal;">('.SucomUtil::get_locale().')</span>';
			}

			return '<th'.
				( empty( $atts['th_colspan'] ) ? '' : ' colspan="'.$atts['th_colspan'].'"' ).
				( empty( $atts['th_rowspan'] ) ? '' : ' rowspan="'.$atts['th_rowspan'].'"' ).
				( empty( $class ) ? '' : ' class="'.$class.'"' ).
				( empty( $css_id ) ? '' : ' id="th_'.$css_id.'"' ).'><p>'.$title.
				( empty( $tooltip_text ) ? '' : $tooltip_text ).'</p></th>';
		}

		public function get_cache( $name, $add_none = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$key = SucomUtil::sanitize_key( $name );	// just in case

			if ( ! isset( self::$cache[$key] ) ) {
				self::$cache[$key] = null;
			}

			if ( self::$cache[$key] === null ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding new form cache entry for '.$key );
				}

				switch ( $key ) {
					case 'half_hours':
						self::$cache[$key] = SucomUtil::get_hours_range( 0, DAY_IN_SECONDS, 60 * 30, '' );
						break;
					case 'all_types':
						self::$cache[$key] = $this->p->schema->get_schema_types_array( false );	// $flatten = false
						break;
					case 'business_types':
						$this->get_cache( 'all_types' );
						self::$cache[$key] =& self::$cache['all_types']['thing']['place']['local.business'];
						break;
					case 'business_types_select':
						$this->get_cache( 'business_types' );
						self::$cache[$key] = $this->p->schema->get_schema_types_select( self::$cache['business_types'], false );
						break;
					case 'org_types':
						$this->get_cache( 'all_types' );
						self::$cache[$key] =& self::$cache['all_types']['thing']['organization'];
						break;
					case 'org_types_select':
						$this->get_cache( 'org_types' );
						self::$cache[$key] = $this->p->schema->get_schema_types_select( self::$cache['org_types'], false );
						break;
					case 'org_site_names':
						self::$cache[$key] = array( 'site' => '[Website Organization]' );
						// no break;
					default:
						self::$cache[$key] = apply_filters( $this->lca.'_form_cache_'.$key, self::$cache[$key] );
						break;
				}

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'returning existing form cache entry for '.$key );
			}

			if ( isset( self::$cache[$key]['none'] ) ) {
				unset( self::$cache[$key]['none'] );
			}

			if ( $add_none ) {
				$none = array( 'none' => '[None]' );
				if ( is_array( self::$cache[$key] ) ) {
					return $none + self::$cache[$key];
				} else {
					return $none;
				}
			} else {
				return self::$cache[$key];
			}
		}
	}
}

?>
