<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomForm' ) ) {

	class SucomForm {

		private $p;
		private $lca;
		private $opts_name       = null;
		private $menu_ext        = null;		// lca or ext lowercase acronym
		private $text_domain     = false;		// lca or ext text domain
		private $def_text_domain = false;	// lca text domain (fallback)

		public $options  = array();
		public $defaults = array();

		public function __construct( &$plugin, $opts_name, &$opts, &$def_opts, $menu_ext = '' ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log( 'form options name is ' . $opts_name );
			}

			$this->lca        = $this->p->lca;
			$this->opts_name  =& $opts_name;
			$this->options    =& $opts;
			$this->defaults   =& $def_opts;
			$this->menu_ext   = empty( $menu_ext ) ? $this->lca : $menu_ext;	// lca or ext lowercase acronym

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
			return isset( $this->p->cf[ 'plugin' ][$maybe_ext]['text_domain'] ) ?
				$this->p->cf[ 'plugin' ][$maybe_ext]['text_domain'] : $maybe_ext;
		}

		public function get_value_transl( $value ) {

			if ( $this->text_domain ) {	// Just in case.

				$value_transl = _x( $value, 'option value', $this->text_domain );	// lca or ext text domain

				if ( $value === $value_transl && $this->text_domain !== $this->def_text_domain ) {
					$value_transl = _x( $value, 'option value', $this->def_text_domain );	// lca text domain
				}

				return $value_transl;

			} elseif ( $this->def_text_domain ) {
				return _x( $value, 'option value', $this->def_text_domain );	// lca text domain
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
				$value = $this->options[$name];
			}

			$html = $is_checkbox ? $this->get_hidden( 'is_checkbox_' . $name, 1, false ) : '';
			$html .= '<input type="hidden" name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" ' .
				'value="' . esc_attr( $value ) . '" />' . "\n";

			return $html;
		}

		/**
		 * Checkbox input field.
		 */
		public function get_checkbox( $name, $css_class = '', $css_id = '', $disabled = false, $force = null, $group = null ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$disabled = true;
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
				( $disabled ? ' ' . $this->get_value_transl( '(option disabled)' ) : '' );

			$input_id = empty( $css_id ) ? 'checkbox_' . $name : 'checkbox_' . $css_id;

			$html = ( $disabled ? '' : $this->get_hidden( 'is_checkbox_' . $name, 1, false ) ) .
				'<input type="checkbox"' .
				( $disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" value="1"' ) .
				( empty( $group ) ? '' : ' data-group="' . esc_attr( $group ) . '"' ) .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				' id="' . esc_attr( $input_id ) . '"' . $input_checked . ' title="' . $title_transl . '" />';

			return $html;
		}

		public function get_no_checkbox( $name, $css_class = '', $css_id = '', $force = null, $group = null ) {

			return $this->get_checkbox( $name, $css_class, $css_id, $disabled = true, $force, $group );
		}

		public function get_no_checkbox_options( $name, $opts, $css_class = '', $css_id = '', $group = null ) {

			$force = empty( $opts[$name] ) ? 0 : 1;

			return $this->get_checkbox( $name, $css_class, $css_id, $disabled = true, $force, $group );
		}

		public function get_no_checkbox_comment( $name, $comment = '' ) {

			return $this->get_checkbox( $name, '', '', $disabled = true, null ) . ( empty( $comment ) ? '' : ' ' . $comment );
		}

		public function get_td_no_checkbox( $name, $comment = '', $narrow = false ) {
			return '<td class="'.( $narrow ? 'checkbox ' : '' ) . 'blank">' . $this->get_no_checkbox_comment( $name, $comment ) . '</td>';
		}

		/**
		 * Creates a vertical list (by default) of checkboxes. The $name_prefix is 
		 * combined with the $values array names to create the checbox option name.
		 */
		public function get_checklist( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $is_assoc = null, $disabled = false ) {

			if ( empty( $name_prefix ) || ! is_array( $values ) ) {
				return;
			}

			if ( $this->get_options( $name_prefix . ':is' ) === 'disabled' ) {
				$disabled = true;
			}

			if ( null === $is_assoc ) {
				$is_assoc = SucomUtil::is_assoc( $values );
			}

			$input_id = empty( $css_id ) ? 'checklist_' . $name_prefix : 'checklist_' . $css_id;

			/**
			 * Use the "input_vertical_list" class to align the checbox input vertically.
			 */
			$html = '<div '.( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) . ' id="' . esc_attr( $input_id ) . '">' . "\n";

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
					$input_disabled = $disabled;
				}

				if ( $this->text_domain ) {
					$label_transl = $this->get_value_transl( $label );
				}

				if ( $this->in_options( $input_name ) ) {
					$input_checked = checked( $this->options[ $input_name ], 1, false );
				} elseif ( $this->in_defaults( $input_name ) ) {
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

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc, $disabled = true );
		}

		public function get_checklist_post_types( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $disabled = false ) {

			foreach ( $this->p->util->get_post_types( 'objects' ) as $pt ) {
				$values[$pt->name] = $pt->label.( empty( $pt->description ) ? '' : ' (' . $pt->description . ')' );
			}

			asort( $values );	// Sort by label.

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc = true, $disabled );
		}

		public function get_no_checklist_post_types( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '' ) {

			return $this->get_checklist_post_types( $name_prefix, $values, $css_class, $css_id, $disabled = true );
		}

		/**
		 * Radio input field.
		 */
		public function get_radio( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $disabled = false ) {

			if ( empty( $name ) || ! is_array( $values ) ) {
				return;
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$disabled = true;
			}

			if ( null === $is_assoc ) {
				$is_assoc = SucomUtil::is_assoc( $values );
			}

			$input_id = empty( $css_id ) ? 'radio_' . $name : 'radio_' . $css_id;

			/**
			 * Use the "input_vertical_list" class to align the radio input buttons vertically.
			 */
			$html = '<div '.( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) . ' id="' . esc_attr( $input_id ) . '">' . "\n";

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
					( $disabled ? ' disabled="disabled"' : $attr_name_value ) .
					( $this->in_options( $name ) ? checked( $this->options[$name], $val, false ) : '' ) .
					( $this->in_defaults( $name ) ? ' title="default is ' . $values[ $this->defaults[ $name ] ] . '"' : '' ) .
					'/>&nbsp;' . $label_transl . '&nbsp;&nbsp;</span>' . "\n";
			}

			$html .= '</div>' . "\n";

			return $html;
		}

		public function get_no_radio( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null ) {

			return $this->get_radio( $name, $values, $css_class, $css_id, $is_assoc, $disabled = true );
		}

		/**
		 * Select drop-down field.
		 */
		public function get_select( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$disabled = false, $selected = false, $event_name = false ) {

			if ( empty( $name ) ) {
				return;
			}

			$key    = SucomUtil::sanitize_key( $name );	// Just in case.
			$values = apply_filters( $this->lca . '_form_select_' . $key, $values );

			if ( ! is_array( $values ) ) {
				return;
			}

			if ( null === $is_assoc ) {
				$is_assoc  = SucomUtil::is_assoc( $values );
			}

			if ( is_string( $disabled ) ) {
				$fixed_value = $disabled;
				$disabled    = false;
			} else {
				$fixed_value = false;
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$disabled = true;
			}

			$html        = '';
			$tr_id       = empty( $css_id ) ? 'tr_' . $name : 'tr_' . $css_id;
			$input_id    = empty( $css_id ) ? 'select_' . $name : 'select_' . $css_id;
			$in_options  = $this->in_options( $name );	// Optimize and call only once.
			$in_defaults = $this->in_defaults( $name );	// Optimize and call only once.

			if ( is_string( $event_name ) ) {

				switch ( $event_name ) {

					case 'on_change_redirect':

						$redirect_url = add_query_arg( array( $name => '%%' . $name . '%%' ),
							SucomUtil::get_prot() . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] );

						$html .= '<script type="text/javascript">' .
							'jQuery( function(){ jQuery( "#' . esc_js( $input_id ) . '" ).change( function(){ ' . 
								'sucomSelectChangeRedirect( "' . esc_js( $name ) . '",' . 
									'this.value,"' . SucomUtil::esc_url_encode( $redirect_url ) . '" ); }); });</script>' . "\n";

						break;

					case 'on_show_unhide_rows':

						static $show_hide_event_added = null;

						if ( $show_hide_event_added === null ) {	// Only add once.

							$show_hide_event_added = true;

							$html .= '
								<script type="text/javascript">
									jQuery.each( [ "show", "hide" ], function( i, ev ){
										var el = jQuery.fn[ ev ];
										jQuery.fn[ ev ] = function(){
											if ( jQuery( this ).is( "tr" ) ) {
												var css_class = jQuery( this ).attr( "class" );
												if ( css_class && css_class.indexOf( "hide_" ) == 0 ) {
													this.trigger( ev );
												}
											}
											return el.apply( this, arguments );
										};
									});
								</script>';
						}

						// No break.

					case 'on_change_unhide_rows':

						$html .= "\n" . '<script type="text/javascript">' .
							'jQuery( function(){ jQuery( "#' . esc_js( $input_id ) . '" ).change( function(){ ' . 
								'sucomSelectChangeUnhideRows( "hide_' . esc_js( $name ) . '",' .
									'"hide_' . esc_js( $name ) . '_" + this.value ); }); });</script>' . "\n";

						/**
						 * If we have an option selected, unhide those rows.
						 */
						if ( false !== $selected ) {

							$unhide_value = false;

							if ( true === $selected ) {

								if ( $in_options ) {
									$unhide_value = $this->options[ $name ];
								} elseif ( $in_defaults ) {
									$unhide_value = $this->defaults[ $name ];
								}

							} else {
								$unhide_value = $selected;
							}

							if ( false !== $unhide_value ) {	// Just in case.

								if ( $event_name === 'on_show_unhide_rows' ) {

									$html .= '<script type="text/javascript">' . 
										'jQuery( "#' . esc_js( $tr_id ) . '" ).on( "show", function(){ ' . 
											'sucomSelectChangeUnhideRows( "hide_' . esc_js( $name ) . '", ' .
												'"hide_' . esc_js( $name . '_' . $unhide_value ) . '" ); });' .
									'</script>' . "\n";

								} else {

									$html .= '<script type="text/javascript">' . 
										'jQuery( document ).ready( function(){ ' . 
											'sucomSelectChangeUnhideRows( "hide_' . esc_js( $name ) . '", ' .
												'"hide_' . esc_js( $name . '_' . $unhide_value ) . '" ); });' . 
									'</script>' . "\n";
								}
							}
						}

						break;
				}
			}

			$select_options_count = 0;
			$select_options_added = 0;
			$select_options_html  = '';

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

				if ( $in_defaults && $option_value === (string) $this->defaults[ $name ] ) {
					$label_transl .= ' ' . $this->get_value_transl( '(default)' );
				}

				if ( ! is_bool( $selected ) ) {
					$is_selected_html = selected( $selected, $option_value, false );
				} elseif ( $in_options ) {
					$is_selected_html = selected( $this->options[ $name ], $option_value, false );
				} elseif ( $in_defaults ) {
					$is_selected_html = selected( $this->defaults[ $name ], $option_value, false );
				} else {
					$is_selected_html = '';
				}

				$select_options_count++;

				/**
				 * For disabled selects, only include the first and/or selected option.
				 */
				if ( ! $disabled || $select_options_count === 1 || $is_selected_html ) {

					if ( false !== $fixed_value ) {
						$option_value = $fixed_value;
					}

					$select_options_html .= '<option value="' . esc_attr( $option_value ) . '"' . $is_selected_html . '>';
					$select_options_html .= $label_transl;
					$select_options_html .= '</option>' . "\n";

					$select_options_added++; 
				}
			}

			$html .= '<select' . ( $disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' );
			$html .= ( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .' id="' . esc_attr( $input_id ) . '">' . "\n";
			$html .= $select_options_html;
			$html .= '<!-- ' . $select_options_added . ' select options added -->' . "\n";
			$html .= '</select>' . "\n";

			return $html;
		}

		/**
		 * Add 'none' as the first array element. Always converts the array to associative.
		 */
		public function get_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$disabled = false, $selected = false, $event_name = false ) {

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

			$values = array( 'none' => '[None]' ) + $values;

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc = true, $disabled, $selected, $event_name );
		}

		public function get_no_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$selected = false, $event_name = false ) {

			return $this->get_select_none( $name, $values, $css_class, $css_id, $is_assoc, $disabled = true, $selected, $event_name );
		}

		public function get_no_select( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $selected = false, $event_name = false ) {
		
			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc, $disabled = true, $selected, $event_name );
		}

		public function get_no_select_options( $name, $opts, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $event_name = false ) {
		
			$selected = isset( $opts[$name] ) ? $opts[$name] : true;

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc, $disabled = true, $selected, $event_name );
		}

		public function get_select_time( $name, $css_class = '', $css_id = '', $disabled = false, $selected = false, $step_mins = 30 ) {

			/**
			 * Set 'none' as the default value is no default is defined.
			 */
			if ( ! empty( $name ) && ! isset( $this->defaults[ $name ] ) ) {
				$this->defaults[ $name ] = 'none';
			}

			$start_secs  = 0;
			$end_secs    = DAY_IN_SECONDS;
			$step_secs   = 60 * $step_mins;
			$time_format = '';

			$values    = array( 'none' => '[None]' ) + SucomUtil::get_hours_range( $start_secs, $end_secs, $step_secs, $time_format );
			$css_class = trim( 'hour_mins ' . $css_class );

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc = true, $disabled, $selected );
		}

		public function get_no_select_time( $name, $css_class = '', $css_id = '', $selected = false, $step_mins = 30 ) {
		
			return $this->get_select_time( $name, $css_class, $css_id, $disabled = true, $selected, $step_mins );
		}

		public function get_select_timezone( $name, $css_class = '', $css_id = '', $disabled = false, $selected = false ) {

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

			return $this->get_select( $name, $timezones, $css_class, $css_id, false, $disabled, $selected, false );
		}

		public function get_no_select_timezone( $name, $css_class = '', $css_id = '', $selected = false ) {

			return $this->get_select_timezone( $name, $css_class, $css_id, $disabled = true, $selected );
		}

		public function get_select_country( $name, $css_class = '', $css_id = '', $disabled = false, $selected = false ) {

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
				if ( empty( $this->options[$name] ) || ( $this->options[$name] !== 'none' && strlen( $this->options[$name] ) !== 2 ) ) {
					$selected = $this->defaults[ $name ];
				}
			}

			$values = array( 'none' => '[None]' ) + SucomUtil::get_alpha2_countries();

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc = true, $disabled, $selected );
		}

		public function get_no_select_country( $name, $css_class = '', $css_id = '', $selected = false ) {

			return $this->get_select_country( $name, $css_class, $css_id, $disabled = true, $selected );
		}

		public function get_no_select_country_options( $name, $opts, $css_class = '', $css_id = '' ) {

			$selected = isset( $opts[$name] ) ? $opts[$name] : false;

			return $this->get_select_country( $name, $css_class, $css_id, $disabled = true, $selected );
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
					$html .= selected( $this->options[$name], $size_name, false );
				}

				$html .= '>';
				$html .= esc_html( $size_name . ' [ ' . $size['width'] . 'x' . $size['height'] . ( $size['crop'] ? ' cropped' : '' ) . ' ]' );

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
		public function get_input( $name, $css_class = '', $css_id = '', $len = 0, $placeholder = '', $disabled = false, $tabindex = 0 ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			if ( $disabled || $this->get_options( $name . ':is' ) === 'disabled' ) {
				return $this->get_no_input( $name, $css_class, $css_id, $placeholder );
			}

			$html = '';
			$value = $this->in_options( $name ) ? $this->options[$name] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );

			if ( ! is_array( $len ) ) {
				$len = array( 'max' => $len );
			}

			if ( ! empty( $len['max'] ) ) {

				if ( empty( $css_id ) ) {
					$css_id = $name;
				}

				$html .= $this->get_text_length_js( 'text_' . $css_id );
			}

			$html .= '<input type="text" name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? ' id="text_' . esc_attr( $name ) . '"' : ' id="text_' . esc_attr( $css_id ) . '"' ) .
				( empty( $tabindex ) ? '' : ' tabindex="' . esc_attr( $tabindex ) . '"' ) .
				( empty( $len['max'] ) ? '' : ' maxLength="' . esc_attr( $len['max'] ) . '"' ) .
				( empty( $len['warn'] ) ? '' : ' warnLength="' . esc_attr( $len['warn'] ) . '"' ) .
				( $this->get_placeholder_events( 'input', $placeholder ) ) . ' value="' . esc_attr( $value ) . '" />' . "\n" .
				( empty( $len['max'] ) ? '' : ' <div id="text_' . esc_attr( $css_id ) . '-lenMsg"></div>' ) . "\n";

			return $html;
		}

		public function get_no_input( $name = '', $css_class = '', $css_id = '', $placeholder = '' ) {

			$html        = '';
			$value       = $this->in_options( $name ) ? $this->options[$name] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );

			if ( ! empty( $name ) ) {
				$html .= $this->get_hidden( $name );
			}

			$html .= $this->get_no_input_value( $value, $css_class, $css_id, $placeholder );

			return $html;
		}

		public function get_no_input_options( $name, $opts, $css_class = '', $css_id = '', $placeholder = '' ) {

			$value = isset( $opts[$name] ) ? $opts[$name] : '';

			return $this->get_no_input_value( $value, $css_class, $css_id, $placeholder );
		}

		public function get_no_input_value( $value = '', $css_class = '', $css_id = '', $placeholder = '', $max_input = 1 ) {

			$html        = '';
			$end_num     = $max_input > 0 ? $max_input - 1 : 0;
			$input_class = empty( $css_class ) ? '' : $css_class;
			$input_id    = empty( $css_id ) ? '' : $css_id;

			foreach ( range( 0, $end_num, 1 ) as $key_num ) {

				if ( $max_input > 1 ) {
					$input_id = empty( $css_id ) ? '' : $css_id . '_' . $key_num;
					$html .= '<div class="wrap_multi">' . "\n";
				}

				$html .= '<input type="text" disabled="disabled"' .
					( empty( $input_class ) ? '' : ' class="' . esc_attr( $input_class ) . '"' ) .
					( empty( $input_id ) ? '' : ' id="text_' . esc_attr( $input_id ) . '"' ) .
					( $placeholder === '' ? '' : ' placeholder="' . esc_attr( $placeholder ) . '"' ) .
					' value="' . esc_attr( $value ) . '" />' . "\n";

				if ( $max_input > 1 ) {
					$html .= '</div>' . "\n";
				}
			}

			return $html;
		}

		public function get_input_color( $name = '', $css_class = '', $css_id = '', $disabled = false ) {

			if ( empty( $name ) ) {

				$value    = '';
				$disabled = true;

			} else {

				$value = $this->in_options( $name ) ? $this->options[$name] : '';

				if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
					$disabled = true;
				}
			}

			return '<input type="text"' .
				( $disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' ) .
				( empty( $css_class ) ? ' class="colorpicker"' : ' class="colorpicker ' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? ' id="text_' . esc_attr( $name ) . '"' : ' id="text_' . esc_attr( $css_id ) . '"' ) .
				' placeholder="#000000" value="' . esc_attr( $value ) . '" />';
		}

		public function get_input_date( $name = '', $css_class = '', $css_id = '', $min_date = '', $max_date = '', $disabled = false ) {

			if ( empty( $name ) ) {

				$value    = '';
				$disabled = true;

			} else {

				$value = $this->in_options( $name ) ? $this->options[$name] : '';

				if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
					$disabled = true;
				}
			}

			return '<input type="text"' .
				( $disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' ) .
				( empty( $css_class ) ? ' class="datepicker"' : ' class="datepicker ' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? ' id="text_' . esc_attr( $name ) . '"' : ' id="text_' . esc_attr( $css_id ) . '"' ) .
				( empty( $min_date ) ? '' : ' min="' . esc_attr( $min_date ) . '"' ) .
				( empty( $max_date ) ? '' : ' max="' . esc_attr( $max_date ) . '"' ) .
				' placeholder="yyyy-mm-dd" value="' . esc_attr( $value ) . '" />';
		}

		public function get_no_input_date( $name = '' ) {

			return $this->get_input_date( $name, '', '', '', '', $disabled = true );
		}

		public function get_no_input_date_options( $name, $opts ) {

			$value = isset( $opts[$name] ) ? $opts[$name] : '';

			return $this->get_no_input_value( $value, 'datepicker', '', 'yyyy-mm-dd' );
		}

		public function get_input_image_upload( $opt_prefix, $placeholder = '', $disabled = false ) {

			$opt_suffix  = '';
			$default_lib = 'wp';
			$media_libs  = array( 'wp' => 'Media Library' );
			$data        = array();

			if ( preg_match( '/^(.*)(_[0-9]+)$/', $opt_prefix, $matches ) ) {
				$opt_prefix = $matches[1];
				$opt_suffix = $matches[2];	// mutiple numbered option
			}

			if ( true === $this->p->avail['media']['ngg'] ) {
				$media_libs['ngg'] = 'NextGEN Gallery';
			}

			if ( strpos( $placeholder, 'ngg-' ) === 0 ) {
				$default_lib = 'ngg';
				$placeholder = preg_replace( '/^ngg-/', '', $placeholder );
			}

			$input_id = $this->get_input( $opt_prefix . '_id' . $opt_suffix, 'short', '', 0, $placeholder, $disabled );

			/**
			 * Disable the select option if only 1 media lib.
			 */
			$select_disabled = count( $media_libs ) <= 1 ? true : $disabled;

			$select_lib = $this->get_select( $opt_prefix . '_id_pre' . $opt_suffix, $media_libs, '', '', true, $select_disabled, $default_lib );

			/**
			 * The css id is used to set image values and disable the image url.
			 */
			if ( ( empty( $this->options[ $opt_prefix . '_id_pre' . $opt_suffix ] ) ||
				$this->options[ $opt_prefix . '_id_pre' . $opt_suffix ] === 'wp' ) && 
					! empty( $this->options[ $opt_prefix . '_id' . $opt_suffix ] ) ) {

				$data['pid'] = $this->options[ $opt_prefix . '_id' . $opt_suffix ];

			} elseif ( $default_lib === 'wp' && ! empty( $placeholder ) ) {

				$data['pid'] = $placeholder;
			}

			$button_upload = function_exists( 'wp_enqueue_media' ) ? $this->get_button(
				'Select or Upload Image',		// $value
				'sucom_image_upload_button button',	// $css_class
				$opt_prefix . $opt_suffix,		// $css_id
				'',					// $url
				false,					// $newtab
				$disabled,				// $disabled
				$data					// $data
			) : '';

			return '<div class="img_upload">' . $input_id . '&nbsp;in&nbsp;' . $select_lib . '&nbsp;' . $button_upload . '</div>';
		}

		public function get_no_input_image_upload( $opt_prefix, $placeholder = '' ) {

			return $this->get_input_image_upload( $opt_prefix, $placeholder, $disabled = true );
		}

		public function get_input_image_dimensions( $name, $use_opts = false, $narrow = false, $disabled = false ) {

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
						$css_class = 'medium', $css_id = '', $is_assoc = true, $disabled );
				}

				$crop_area_select .= '</div>';
			}

			return $this->get_input( $name . '_width', 'short width', '', 0, $placeholder_width, $disabled ) . 'x' .
				$this->get_input( $name . '_height', 'short height', '', 0, $placeholder_height, $disabled ) .
					'px crop ' . $this->get_checkbox( $name . '_crop', '', '', $disabled ) . $crop_area_select;
		}

		public function get_no_input_image_dimensions( $name, $use_opts = false, $narrow = false ) {

			return $this->get_input_image_dimensions( $name, $use_opts, $narrow, $disabled = true );
		}

		public function get_input_image_url( $opt_prefix, $url = '' ) {

			$opt_suffix = '';

			if ( preg_match( '/^(.*)(_[0-9]+)$/', $opt_prefix, $matches ) ) {
				$opt_prefix = $matches[1];
				$opt_suffix = $matches[2];
			}

			if ( empty( $this->options[ $opt_prefix . '_id' . $opt_suffix ] ) ) {
				$placeholder = SucomUtil::esc_url_encode( $url );
				$disabled    = false;
			} else {
				$placeholder = '';
				$disabled    = true;
			}

			return $this->get_input( $opt_prefix . '_url' . $opt_suffix, 'wide', '', 0, $placeholder, $disabled );
		}

		public function get_input_video_dimensions( $name, $media_info = array(), $disabled = false ) {

			$placeholder_width = '';
			$placeholder_height = '';

			if ( ! empty( $media_info ) && is_array( $media_info ) ) {
				$placeholder_width  = empty( $media_info['vid_width'] ) ? '' : $media_info['vid_width'];
				$placeholder_height = empty( $media_info['vid_height'] ) ? '' : $media_info['vid_height'];
			}

			return $this->get_input( $name . '_width', 'short width', '', 0, $placeholder_width, $disabled ) . 'x' .
				$this->get_input( $name . '_height', 'short height', '', 0, $placeholder_height, $disabled ) . 'px';
		}

		public function get_no_input_video_dimensions( $name, $media_info = array() ) {

			return $this->get_input_video_dimensions( $name, $media_info, $disabled = true );
		}

		public function get_input_video_url( $opt_prefix, $url = '' ) {

			/**
			 * Disable if we have a custom video embed.
			 */
			$disabled = empty( $this->options[$opt_prefix . '_embed'] ) ? false : true;

			return $this->get_input( $opt_prefix . '_url', 'wide', '', 0, SucomUtil::esc_url_encode( $url ), $disabled );
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

		public function get_input_multi( $name, $css_class = '', $css_id = '', $start_num = 0, $max_input = 90, $show_first = 5, $disabled = false ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num    = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$prev_num      = $key_num > 0 ? $key_num - 1 : 0;
				$next_num      = $key_num + 1;
				$opt_key       = $name . '_' . $key_num;
				$opt_disabled  = $disabled || $this->get_options( $opt_key . ':is' ) === 'disabled' ? true : false;
				$input_class   = empty( $css_class ) ? 'multi' : 'multi ' . $css_class;
				$input_id      = empty( $css_id ) ? $name . '_' . $key_num : $css_id . '_' . $key_num;
				$input_id_prev = empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num;
				$input_id_next = empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num;
				$input_value   = $this->in_options( $opt_key ) ? $this->options[ $opt_key ] : '';
				$display       = empty( $one_more ) && $key_num >= $show_first ? false : true;

				$html .= '<div class="wrap_multi" id="wrap_' . esc_attr( $input_id ) . '"' .
					( $display ? '' : ' style="display:none;"' ) . '>' . "\n";

				if ( $disabled && $key_num >= $show_first && empty( $display ) ) {

					continue;

				} elseif ( $opt_disabled ) {

					$html .= $this->get_no_input( $opt_key, $input_class, $input_id );	// adds 'text_' to the id value

				} else {

					$html .= '<input type="text"' .
						' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"' .
						' class="' . esc_attr( $input_class ) . '"' .
						' id="text_' . esc_attr( $input_id ) . '"' .
						' value="' . esc_attr( $input_value ) . '"' .
						' onFocus="if ( jQuery(\'input#text_' . $input_id_prev . '\').val().length ) { '.
							'jQuery(\'div#wrap_' . esc_attr( $input_id_next ) . '\').show(); }" />';
				}

				$one_more = empty( $input_value ) ? false : true;

				$html .= '</div>' . "\n";
			}

			return $html;
		}

		public function get_no_input_multi( $name, $css_class = '', $css_id = '', $start_num = 0, $max_input = 90, $show_first = 5 ) {

			return $this->get_input_multi( $name, $css_class, $css_id, $start_num, $max_input, $show_first, $disabled = true );
		}

		public function get_date_time_iso( $name_prefix = '', $disabled = false ) {

			return $this->get_input_date( $name_prefix . '_date', '', '', '', '', $disabled ) . ' ' .
				$this->get_value_transl( 'at' ) . ' ' .
				$this->get_select_time( $name_prefix . '_time', '', '', $disabled, false, 30 ) . ' ' .
				$this->get_value_transl( 'tz' ) . ' ' .
				$this->get_select_timezone( $name_prefix . '_timezone', '', '', $disabled, false );
		}

		public function get_no_date_time_iso( $name_prefix = '' ) {

			return $this->get_date_time_iso( $name_prefix, $disabled = true );
		}

		public function get_mixed_multi( $mixed, $css_class, $css_id, $start_num = 0, $max_input = 10, $show_first = 2, $disabled = false ) {

			if ( empty( $mixed ) ) {
				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $max_input ? $max_input : $show_first;
			$end_num    = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$prev_num     = $key_num > 0 ? $key_num - 1 : 0;
				$next_num     = $key_num + 1;
				$wrap_id      = $css_id . '_' . $key_num;
				$wrap_id_prev = $css_id . '_' . $prev_num;
				$wrap_id_next = $css_id . '_' . $next_num;
				$display      = empty( $one_more ) && $key_num >= $show_first ? false : true;

				$html .= '<div class="wrap_multi" id="wrap_' . esc_attr( $wrap_id ) . '"' .
					( $display ? '' : ' style="display:none;"' ) . '>' . "\n";

				foreach ( $mixed as $name => $atts ) {

					$opt_key      = $name . '_' . $key_num;
					$opt_disabled = $disabled || $this->get_options( $opt_key . ':is' ) === 'disabled' ? true : false;
					$in_options   = $this->in_options( $opt_key );	// optimize and call only once
					$in_defaults  = $this->in_defaults( $opt_key );	// optimize and call only once
					$input_title  = empty( $atts['input_title'] ) ? '' : $atts['input_title'];
					$input_class  = empty( $atts['input_class'] ) ? 'multi' : 'multi ' . $atts['input_class'];
					$input_id     = empty( $atts['input_id'] ) ? $name . '_' . $key_num : $atts['input_id'] . '_' . $key_num;
	
					if ( $disabled && $key_num >= $show_first && empty( $display ) ) {
						continue;
					}
	
					if ( ! empty( $atts['input_label'] ) ) {
						$html .= '<p style="display:inline">' . $atts['input_label'] . '</p> ';
					}

					if ( isset( $atts['input_type'] ) ) {

						switch ( $atts['input_type'] ) {

							case 'text':

								$input_value = $in_options ? $this->options[ $opt_key ] : '';

								if ( $opt_disabled ) {
									$html .= $this->get_no_input( $opt_key, $input_class, $input_id );
								} else {
									$html .= '<input type="text"' .
										' name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"' .
										' title="' . esc_attr( $input_title ) . '"' .
										' class="' . esc_attr( $input_class ) . '"' .
										' id="text_' . esc_attr( $input_id ) . '"' .
										' value="' . esc_attr( $input_value ) . '"' .
										' onFocus="jQuery(\'div#wrap_' . esc_attr( $wrap_id_next ) . '\').show();" />' . "\n";
								}

								$one_more = empty( $input_value ) ? false : true;

								break;

							case 'select':

								if ( $opt_disabled ) {
									$html .= '<select disabled="disabled"';
								} else {
									$html .= '<select name="' . esc_attr( $this->opts_name . '[' . $opt_key . ']' ) . '"';
								}

									
								$html .= ' title="' . esc_attr( $input_title ) . '"' .
									' class="' . esc_attr( $input_class ) . '"' .
									' id="select_' . esc_attr( $input_id ) . '"' .
									' onFocus="jQuery(\'div#wrap_' . esc_attr( $wrap_id_next ) . '\').show();">' . "\n";

								$select_options = empty( $atts['select_options'] ) || 
									! is_array( $atts['select_options'] ) ?
										array() : $atts['select_options'];

								$select_selected = empty( $atts['select_selected'] ) ? null : $atts['select_selected'];
								$select_default   = empty( $atts['select_default'] ) ? null : $atts['select_default'];

								$is_assoc = SucomUtil::is_assoc( $select_options );

								$select_options_count = 0;
								$select_options_added = 0;

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

									$select_options_count++; 

									/**
									 * For disabled selects, only include the first and/or selected option.
									 */
									if ( ! $opt_disabled || $select_options_count === 1 || $is_selected_html ) {

										$html .= '<option value="' . esc_attr( $option_value ) . '"' . $is_selected_html . '>';
										$html .= $label_transl;
										$html .= '</option>' . "\n";

										$select_options_added++; 
									}
								}
								
								$html .= '<!-- ' . $select_options_added . ' select options added -->' . "\n";
								$html .= '</select>' . "\n";

								break;
						}
					}
				}

				$html .= '</div>' . "\n";
			}

			return $html;
		}

		public function get_no_mixed_multi( $mixed, $css_class, $css_id, $start_num = 0, $max_input = 10, $show_first = 2 ) {

			return $this->get_mixed_multi( $mixed, $css_class, $css_id, $start_num, $max_input, $show_first, $disabled = true );
		}

		public function get_image_dimensions_text( $name, $use_opts = false ) {

			if ( ! empty( $this->options[$name . '_width'] ) && ! empty( $this->options[$name . '_height'] ) ) {

				return $this->options[$name . '_width'] . 'x' . $this->options[$name . '_height'] . 'px' .
					( $this->options[$name . '_crop'] ? ' cropped' : '' );

			} elseif ( true === $use_opts ) {

				$def_width  = empty( $this->p->options[$name . '_width'] ) ? '' : $this->p->options[$name . '_width'];
				$def_height = empty( $this->p->options[$name . '_height'] ) ? '' : $this->p->options[$name . '_height'];
				$def_crop   = empty( $this->p->options[$name . '_crop'] ) ? false : true;

				if ( ! empty( $def_width ) && ! empty( $def_height ) ) {
					return $def_width . 'x' . $def_height . 'px' . ( $def_crop ? ' cropped' : '' );
				}
			}

			return;
		}

		public function get_video_dimensions_text( $name, $media_info ) {

			if ( ! empty( $this->options[$name . '_width'] ) && ! empty( $this->options[$name . '_height'] ) ) {

				return $this->options[$name . '_width'] . 'x' . $this->options[$name . '_height'];

			} elseif ( ! empty( $media_info ) && is_array( $media_info ) ) {

				$def_width  = empty( $media_info['vid_width'] ) ? '' : $media_info['vid_width'];
				$def_height = empty( $media_info['vid_height'] ) ? '' : $media_info['vid_height'];

				if ( ! empty( $def_width ) && ! empty( $def_height ) ) {
					return $def_width . 'x' . $def_height;
				}
			}

			return '';
		}

		public function get_textarea( $name, $css_class = '', $css_id = '', $len = 0, $placeholder = '', $disabled = false ) {

			if ( empty( $name ) ) {
				return;	// Just in case.
			}

			if ( $this->get_options( $name . ':is' ) === 'disabled' ) {
				$disabled = true;
			}

			$html        = '';
			$value       = $this->in_options( $name ) ? $this->options[$name] : '';
			$placeholder = $this->get_placeholder_sanitized( $name, $placeholder );

			if ( ! is_array( $len ) ) {
				$len = array( 'max' => $len );
			}

			if ( ! empty( $len['max'] ) ) {

				if ( empty( $css_id ) ) {
					$css_id = $name;
				}

				$html .= $this->get_text_length_js( 'textarea_' . $css_id );
			}

			$html .= '<textarea ' .
				( $disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"' ) .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? ' id="textarea_' . esc_attr( $name ) . '"' : ' id="textarea_' . esc_attr( $css_id ) . '"' ) .
				( empty( $len['max'] ) || $disabled ? '' : ' maxLength="' . esc_attr( $len['max'] ) . '"' ) .
				( empty( $len['warn'] ) || $disabled ? '' : ' warnLength="' . esc_attr( $len['warn'] ) . '"' ) .
				( empty( $len['max'] ) && empty( $len['rows'] ) ? '' : ( empty( $len['rows'] ) ?
					' rows="'.( round( $len['max'] / 100 ) + 1 ) . '"' : ' rows="' . $len['rows'] . '"' ) ) .
				( $this->get_placeholder_events( 'textarea', $placeholder ) ) . '>' . esc_attr( $value ) . '</textarea>' .
				( empty( $len['max'] ) || $disabled ? '' : ' <div id="textarea_' . esc_attr( $css_id ) . '-lenMsg"></div>' );

			return $html;
		}

		public function get_no_textarea( $name, $css_class = '', $css_id = '', $len = 0, $placeholder = '' ) {

			return $this->get_textarea( $name, $css_class, $css_id, $len, $placeholder, $disabled = true );
		}

		public function get_no_textarea_options( $name, $opts, $css_class = '', $css_id = '', $len = 0, $placeholder = '' ) {

			$value = isset( $opts[$name] ) ? $opts[$name] : '';

			return $this->get_no_textarea_value( $value, $css_class, $css_id, $len, $placeholder );
		}

		public function get_no_textarea_value( $value = '', $css_class = '', $css_id = '', $len = 0, $placeholder = '' ) {
			return '<textarea disabled="disabled"' .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? '' : ' id="textarea_' . esc_attr( $css_id ) . '"' ) .
				( empty( $len ) ? '' : ' rows="'.( round( $len / 100 ) + 1 ) . '"' ) .
				'>' . esc_attr( $value ) . '</textarea>';
		}

		public function get_button( $value, $css_class = '', $css_id = '', $url = '', $newtab = false, $disabled = false, $data = array() ) {

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
				( $disabled ? ' disabled="disabled"' : '' ) .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? '' : ' id="button_' . esc_attr( $css_id ) . '"' ) .
				( empty( $url ) || $disabled ? '' : $on_click ) .
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

			return empty( $css_id ) ? '' : '<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery(\'#' . esc_js( $css_id ) . '\').focus(function(){ sucomTextLen(\'' . esc_js( $css_id ) . '\'); });
					jQuery(\'#' . esc_js( $css_id ) . '\').keyup(function(){ sucomTextLen(\'' . esc_js( $css_id ) . '\'); });
				});</script>';
		}

		private function get_placeholder_sanitized( $name, $placeholder ) {

			if ( empty( $name ) ) {
				return $placeholder;	// Just in case.
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
				$placeholder = '';	// must be a string
			}

			return $placeholder;
		}

		private function get_placeholder_events( $type = 'input', $placeholder ) {

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

		public function get_md_form_rows( array $table_rows, array $form_rows, array $head = array(), array $mod = array(), $auto_draft_msg = null ) {
		
			if ( null === $auto_draft_msg ) {
				$auto_draft_msg = 'Save a draft version or publish to update this value.';
			}

			foreach ( $form_rows as $key => $val ) {

				if ( empty( $val ) ) {

					$table_rows[ $key ] = '';	// Placeholder.

					continue;
				}

				if ( ! empty( $val[ 'table_row' ] ) ) {

					$table_rows[ $key ] = $val[ 'table_row' ];

					continue;
				}

				$is_auto_draft = false;

				if ( isset( $mod[ 'post_status' ] ) ) {

					/**
					 * Do not show the option if the post status is empty or auto-draft.
					 */
					if ( ! empty( $val['no_auto_draft'] ) ) {

						if ( empty( $mod[ 'post_status' ] ) || $mod[ 'post_status' ] === 'auto-draft' ) {

							$is_auto_draft = true;

							$val['td_class'] = empty( $val['td_class'] ) ? 'blank' : $val['td_class'] . ' blank';
						}
					}
				}

				if ( empty( $val['label'] ) ) {	// Just in case.
					$val['label'] = '';
				}

				if ( ! empty( $val['header'] ) ) {

					$table_rows[ $key ] = empty( $val['tr_class'] ) ? '' : '<tr class="' . $val['tr_class'] . '">' . "\n";

					$table_rows[ $key ] .= '<td colspan="2"' . ( ! empty( $val['td_class'] ) ? ' class="' . $val['td_class'] . '"' : '' ) . '>';
					
					$table_rows[ $key ] .= '<' . $val['header'] . '>' . $val['label'] . '</' . $val['header'] . '>';
					
					$table_rows[ $key ] .= '</td>' . "\n";

				} else {

					$table_rows[ $key ] = empty( $val['tr_class'] ) ? '' : '<tr class="' . $val['tr_class'] . '">' . "\n";

					$table_rows[ $key ] .= $this->get_th_html( $val['label'], 
						( empty( $val['th_class'] ) ? '' : $val['th_class'] ),
						( empty( $val['tooltip'] ) ? '' : $val['tooltip'] ) ) . "\n";

					$table_rows[ $key ] .= '<td'.( empty( $val['td_class'] ) ? '' : ' class="' . $val['td_class'] . '"' ) . '>';

					$table_rows[ $key ] .= $is_auto_draft ? '<em>' . $auto_draft_msg . '</em>' : 
						( empty( $val['content'] ) ? '' : $val['content'] );
							
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

			if ( isset( $atts['is_locale'] ) ) {
				$label .= ' <span style="font-weight:normal;">(' . SucomUtil::get_locale() . ')</span>';
			}

			return '<th' .
				( empty( $atts['th_colspan'] ) ? '' : ' colspan="' . $atts['th_colspan'] . '"' ) .
				( empty( $atts['th_rowspan'] ) ? '' : ' rowspan="' . $atts['th_rowspan'] . '"' ) .
				( empty( $css_class ) ? '' : ' class="' . $css_class . '"' ) .
				( empty( $css_id ) ? '' : ' id="th_' . $css_id . '"' ) . '><p>' . $label .
				( empty( $tooltip_text ) ? '' : $tooltip_text ) . '</p></th>';
		}

		public function get_tr_hide( $in_view = 'basic', $opt_keys = array() ) {

			$css_class = self::get_css_class_hide( $in_view, $opt_keys );

			return empty( $css_class ) ? '' : '<tr class="' . $css_class . '">';
		}

		public function get_css_class_hide_img_dim( $in_view = 'basic', $opt_prefix ) {

			foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $opt_key ) {
				$opt_keys[] = $opt_prefix . '_' . $opt_key;
			}

			return self::get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide_vid_dim( $in_view = 'basic', $opt_prefix ) {

			foreach ( array( 'width', 'height' ) as $opt_key ) {
				$opt_keys[] = $opt_prefix . '_' . $opt_key;
			}

			return self::get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide_prefix( $in_view = 'basic', $opt_prefix ) {

			$opt_keys = SucomUtil::get_opts_begin( $opt_prefix, $this->options );

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
