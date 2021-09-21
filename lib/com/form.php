<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomUtil' ) ) {	// Just in case.

	require_once './util.php';
}

if ( ! class_exists( 'SucomUtilWP' ) ) {	// Just in case.

	require_once './util-wp.php';
}

if ( ! class_exists( 'SucomForm' ) ) {

	class SucomForm {

		private $p;	// Plugin class object.

		private $plugin_id          = null;
		private $opts_name          = null;
		private $menu_ext           = null;	// Lowercase acronyn for plugin or add-on.
		private $text_domain        = false;	// Text domain for plugin or add-on.
		private $def_text_domain    = false;	// Default text domain (fallback).
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

			$this->plugin_id = $this->p->id;
			$this->opts_name =& $opts_name;
			$this->options   =& $opts;
			$this->defaults  =& $def_opts;
			$this->menu_ext  = empty( $menu_ext ) ? $this->plugin_id : $menu_ext;	// Lowercase acronyn for plugin or add-on.

			$this->set_text_domain( $this->menu_ext );

			$this->set_default_text_domain( $this->plugin_id );
		}

		public function get_options_name() {

			return $this->opts_name;
		}

		public function get_options( $opt_key = false, $def_val = null ) {

			if ( false !== $opt_key ) {

				if ( $this->in_options( $opt_key ) ) {

					return $this->options[ $opt_key ];

				}

				return $def_val;
			}

			return $this->options;
		}

		public function get_defaults( $opt_key = false, $def_val = null ) {

			if ( false !== $opt_key ) {

				if ( $this->in_defaults( $opt_key ) ) {

					return $this->defaults[ $opt_key ];

				}

				return $def_val;
			}

			return $this->defaults;
		}

		public function in_options( $opt_key ) {

			if ( isset( $this->options[ $opt_key ] ) ) {

				return true;

			} elseif ( 0 === strpos( $opt_key, '/' ) ) {	// Regular expression.

				if ( ! is_array( $this->options ) ) {	// Just in case.

					return false;
				}

				$opts = SucomUtil::preg_grep_keys( $opt_key, $this->options );

				return empty( $opts ) ? false : true;
			}

			return false;
		}

		public function in_defaults( $opt_key ) {

			if ( isset( $this->defaults[ $opt_key ] ) ) {

				return true;

			} elseif ( false !== strpos( $opt_key, '#' ) ) {	// Localized option name.

				$opt_key_locale = SucomUtil::get_key_locale( $opt_key, $this->defaults, 'default' );

				if ( isset( $this->defaults[ $opt_key_locale ] ) ) {

					$this->defaults[ $opt_key ] = $this->defaults[ $opt_key_locale ];

					return true;
				}
			}

			return false;
		}

		/**
		 * $menu_ext is the lowercase acronyn for the plugin or add-on.
		 */
		public function get_menu_ext() {

			return $this->menu_ext;
		}

		public function get_text_domain() {

			return $this->text_domain;
		}

		public function get_default_text_domain() {

			return $this->def_text_domain;
		}

		/**
		 * $menu_ext is the lowercase acronyn for the plugin or add-on.
		 */
		public function set_text_domain( $menu_ext ) {

			$this->text_domain = $this->get_plugin_text_domain( $menu_ext );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'form text domain set to ' . $this->text_domain );
			}
		}

		/**
		 * $menu_ext is the lowercase acronyn for the plugin or add-on.
		 */
		public function set_default_text_domain( $menu_ext ) {

			$this->def_text_domain = $this->get_plugin_text_domain( $menu_ext );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'form default text domain set to ' . $this->def_text_domain );
			}
		}

		/**
		 * $menu_ext is the lowercase acronyn for the plugin or add-on.
		 */
		public function get_plugin_text_domain( $menu_ext ) {

			return isset( $this->p->cf[ 'plugin' ][ $menu_ext ][ 'text_domain' ] ) ?
				$this->p->cf[ 'plugin' ][ $menu_ext ][ 'text_domain' ] : $menu_ext;
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

		public function get_tr_on_change( $select_id, $select_value ) {

			return '<tr class="hide_' . $select_id . ' hide_' . $select_id . '_' . $select_value . '">';
		}

		public function get_tr_hide( $in_view = 'basic', $option_keys = array() ) {

			$css_class = self::get_css_class_hide( $in_view, $option_keys );

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

		public function get_th_html( $label = '', $css_class = '', $css_id = '', $atts = array() ) {

			$input_class  = SucomUtil::sanitize_css_class( $css_class );
			$input_id     = SucomUtil::sanitize_css_id( $css_id );
			$tooltip_text = '';

			if ( ! empty( $css_id ) ) {

				if ( isset( $this->p->msgs ) ) {	// Just in case.

					$tooltip_text = $this->p->msgs->get( 'tooltip-' . $css_id, $atts );	// Text is esc_attr().
				}
			}

			if ( isset( $atts[ 'is_locale' ] ) ) {

				$label .= ' <span class="option_locale">[' . SucomUtil::get_locale() . ']</span>';
			}

			$html = '<th';
			$html .= empty( $atts[ 'th_colspan' ] ) ? '' : ' colspan="' . $atts[ 'th_colspan' ] . '"';
			$html .= empty( $atts[ 'th_rowspan' ] ) ? '' : ' rowspan="' . $atts[ 'th_rowspan' ] . '"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="th_' . $input_id . '"';	// Already sanitized.
			$html .= '>' . $label . $tooltip_text . '</th>';

			return $html;
		}

		public function get_css_class_hide_img_dim( $in_view = 'basic', $name ) {

			foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $name_suffix ) {

				$option_keys[] = $name . '_' . $name_suffix;
			}

			return self::get_css_class_hide( $in_view, $option_keys );
		}

		public function get_css_class_hide_vid_dim( $in_view = 'basic', $name ) {

			foreach ( array( 'width', 'height' ) as $name_suffix ) {

				$option_keys[] = $name . '_' . $name_suffix;
			}

			return self::get_css_class_hide( $in_view, $option_keys );
		}

		public function get_css_class_hide_prefix( $in_view = 'basic', $name ) {

			$option_keys = SucomUtil::get_opts_begin( $name, $this->options );

			return self::get_css_class_hide( $in_view, $option_keys );
		}

		public function get_css_class_hide( $in_view = 'basic', $option_keys = array() ) {

			$css_class = 'hide_in_' . $in_view;

			if ( empty( $option_keys ) ) {

				return $css_class;

			} elseif ( ! is_array( $option_keys ) ) {

				$option_keys = array( $option_keys );

			} elseif ( SucomUtil::is_assoc( $option_keys ) ) {

				$option_keys = array_keys( $option_keys );
			}

			foreach ( $option_keys as $opt_key ) {

				$opt_key = preg_replace( '/#.*$/', '', $opt_key );	// Just in case.

				if ( empty( $opt_key ) ) {	// Just in case.

					continue;

				} elseif ( strpos( $opt_key, ':is' ) ) {	// Skip option flags.

					continue;
				}

				/**
				 * Example:
				 *
				 *	$opt_key_locale = 'site_name#fr_FR'
				 *	$opt_key        = 'site_name'
				 */
				$opt_key_locale = SucomUtil::get_key_locale( $opt_key, $this->options );

				if ( isset( $this->defaults[ $opt_key_locale ] ) ) {

					if ( isset( $this->options[ $opt_key_locale ] ) ) {

						if ( $this->options[ $opt_key_locale ] !== $this->defaults[ $opt_key_locale ] ) {

							return '';	// Show option.
						}
					}
				}

				if ( $opt_key_locale !== $opt_key ) {

					if ( isset( $this->defaults[ $opt_key ] ) ) {

						if ( isset( $this->options[ $opt_key_locale ] ) ) {

							if ( '' !== $this->options[ $opt_key_locale ] &&
								$this->options[ $opt_key_locale ] !== $this->defaults[ $opt_key ] ) {

								return '';	// Show option.
							}
						}

						if ( isset( $this->options[ $opt_key ] ) ) {

							if ( $this->options[ $opt_key ] !== $this->defaults[ $opt_key ] ) {

								return '';	// Show option.
							}
						}
					}
				}
			}

			return $css_class;	// Hide option.
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

					$tr_html = '<tr class="' . $val[ 'tr_class' ] . '">' . "\n";

				} else {

					$tr_html = '';
				}

				/**
				 * Table cell HTML.
				 */
				if ( isset( $val[ 'table_row' ] ) ) {

					if ( ! empty( $val[ 'table_row' ] ) ) {

						$table_rows[ $key ] .= $tr_html . $val[ 'table_row' ] . "\n";
					}

					continue;
				}

				$td_class = empty( $val[ 'td_class' ] ) ? '' : ' class="' . $val[ 'td_class' ] . '"';

				if ( ! empty( $val[ 'header' ] ) ) {

					$col_span = ' colspan="' . ( isset( $val[ 'col_span' ] ) ? $val[ 'col_span' ] : 2 ) . '"';

					$table_rows[ $key ] .= $tr_html . '<td' . $col_span . $td_class . '>';
					$table_rows[ $key ] .= '<' . $val[ 'header' ];

					if ( ! empty( $val[ 'header_class' ] ) ) {

						$table_rows[ $key ] .= ' class="' . $val[ 'header_class' ] . '"';
					}

					$table_rows[ $key ] .= '>' . $val[ 'label' ] . '</' . $val[ 'header' ] . '>';
					$table_rows[ $key ] .= '</td>' . "\n";

				} else {

					$col_span = empty( $val[ 'col_span' ] ) ? '' : ' colspan="' . $val[ 'col_span' ] .'"';

					$table_rows[ $key ] .= $tr_html . $this->get_th_html( $val[ 'label' ],
						( empty( $val[ 'th_class' ] ) ? '' : $val[ 'th_class' ] ),
						( empty( $val[ 'tooltip' ] ) ? '' : $val[ 'tooltip' ] )
					) . "\n";

					$table_rows[ $key ] .= '<td' . $col_span . $td_class . '>';
					$table_rows[ $key ] .= empty( $val[ 'content' ] ) ? '' : $val[ 'content' ];
					$table_rows[ $key ] .= '</td>' . "\n";
				}
			}

			return $table_rows;
		}

		/**
		 * Hidden input field.
		 */
		public function get_hidden( $name, $value = null ) {

			if ( empty( $name ) ) {

				return;	// Just in case.
			}

			if ( null === $value ) {

				$value = $this->in_options( $name ) ? $this->options[ $name ] : '';
			}

			return '<input type="hidden" name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" value="' . esc_attr( $value ) . '" />' . "\n";
		}

		/**
		 * Checkbox input field.
		 */
		public function get_checkbox( $name, $css_class = '', $css_id = '', $is_disabled = false, $force = null, $group = null ) {

			if ( empty( $name ) ) {

				return;	// Just in case.
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

			$input_class    = SucomUtil::sanitize_css_class( $css_class );
			$input_id       = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );
			$input_disabled = 'disabled' === $this->get_options( $name . ':is' ) ? true : $is_disabled;
			$default_status = $this->in_defaults( $name ) && ! empty( $this->defaults[ $name ] ) ? 'checked' : 'unchecked';
			$title_transl   = sprintf( $this->get_value_transl( 'default is %s' ), $this->get_value_transl( $default_status ) );

			$html = '<input type="checkbox"';
			$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" value="1"';
			$html .= $input_disabled ? ' disabled="disabled"' : '';
			$html .= empty( $group ) ? '' : ' data-group="' . esc_attr( $group ) . '"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="checkbox_' . $input_id . '"';	// Already sanitized.
			$html .= ' title="' . $title_transl . '"';
			$html .= ' ' . $input_checked . '/>';
			$html .= $is_disabled ? '' : $this->get_hidden( 'is_checkbox_' . $name, 1 );

			return $html;
		}

		/**
		 * Creates a vertical list (by default) of checkboxes.
		 *
		 * The $name_prefix is combined with the $values array names to create the checbox option name.
		 */
		public function get_checklist( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $is_assoc = null, $is_disabled = false ) {

			if ( empty( $name_prefix ) || ! is_array( $values ) ) {

				return;
			}

			if ( null === $is_assoc ) {

				$is_assoc = SucomUtil::is_assoc( $values );
			}

			unset( $values[ 'none' ] );	// Just in case - remove 'none' value for selects.

			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name_prefix : $css_id );

			/**
			 * Use the "input_vertical_list" class to align the checbox input vertically.
			 */
			$html = '<div class="' . $input_class . '" id="checklist_' . $input_id . '">' . "\n";

			foreach ( $values as $name_suffix => $label ) {

				if ( is_array( $label ) ) {	// Just in case.

					$label = implode( $glue = ', ', $label );
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

				if ( $this->in_options( $input_name ) ) {

					$input_checked = checked( $this->options[ $input_name ], 1, false );

				} elseif ( $this->in_defaults( $input_name ) ) {	// Returns true or false.

					$input_checked = checked( $this->defaults[ $input_name ], 1, false );

				} else {

					$input_checked = '';
				}

				$input_disabled = 'disabled' === $this->get_options( $input_name . ':is' ) ? true : $is_disabled;
				$default_status = $this->in_defaults( $input_name ) && ! empty( $this->defaults[ $input_name ] ) ? 'checked' : 'unchecked';
				$title_transl   = sprintf( $this->get_value_transl( 'default is %s' ), $this->get_value_transl( $default_status ) );
				$label_transl   = $this->get_value_transl( $label );

				$html .= $is_disabled ? '' : $this->get_hidden( 'is_checkbox_' . $input_name, 1 );
				$html .= '<span><input type="checkbox"';
				$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '" value="1"';
				$html .= $input_disabled ? ' disabled="disabled"' : '';
				$html .= ' title="' . $title_transl . '"';
				$html .= ' ' . $input_checked . '/>';
				$html .= '&nbsp;' . $label_transl . '&nbsp;&nbsp;</span>' . "\n";
			}

			$html .= '</div>' . "\n";

			return $html;
		}

		public function get_checklist_post_types( $name_prefix, $css_class = 'input_vertical_list', $css_id = '', $is_disabled = false ) {

			$label_prefix = _x( 'Post Type', 'option label', $this->text_domain );

			$values = SucomUtilWP::get_post_type_labels( $values = array(), $val_prefix = '', $label_prefix );

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc = true, $is_disabled );
		}

		public function get_checklist_post_tax_user( $name_prefix, $css_class = 'input_vertical_list', $css_id = '', $is_disabled = false ) {

			$values = array();

			$label_prefix = _x( 'Post Type', 'option label', $this->text_domain );

			$values = SucomUtilWP::get_post_type_labels( $values, $val_prefix = '', $label_prefix );

			$label_prefix = _x( 'Taxonomy', 'option label', $this->text_domain );

			$values = SucomUtilWP::get_taxonomy_labels( $values, $val_prefix = 'tax_', $label_prefix );

			$values[ 'user_page' ] = _x( 'User Profiles', 'option label', $this->text_domain );

			asort( $values );	// Sort by label.

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc = true, $is_disabled );
		}

		public function get_date_time_tz( $name_prefix, $is_disabled = false, $step_mins = 15, $add_none = true ) {

			$selected = false;

			$html = $this->get_input_date( $name_prefix . '_date', $css_class = '', $css_id = '', $min_date = '', $max_date = '', $is_disabled ) . ' ';
			$html .= $this->get_value_transl( 'at' ) . ' ';
			$html .= $this->get_select_time( $name_prefix . '_time', $css_class = '', $css_id = '', $is_disabled, $selected, $step_mins, $add_none ) . ' ';
			$html .= $this->get_value_transl( 'tz' ) . ' ';
			$html .= $this->get_select_timezone( $name_prefix . '_timezone', $css_class = '', $css_id = '', $is_disabled, $selected );

			return $html;
		}

		/**
		 * Text input field.
		 */
		public function get_input( $name, $css_class = '', $css_id = '', $len = 0, $holder = '', $is_disabled = false, $tabidx = null, $el_attr = '' ) {

			if ( empty( $name ) ) {

				return;	// Just in case.
			}

			if ( $is_disabled || $this->get_options( $name . ':is' ) === 'disabled' ) {

				return $this->get_no_input( $name, $css_class, $css_id, $holder );
			}

			$html        = '';
			$holder      = $this->get_placeholder_sanitized( $name, $holder );
			$value       = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );

			if ( ! is_array( $len ) ) {	// A non-array value defaults to a max length.

				if ( empty( $len ) ) {

					$len = array();

				} else {

					$len = array( 'max' => $len );
				}
			}

			$html .= '<input type="text" name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="text_' . $input_id . '"';	// Already sanitized.
			$html .= empty( $el_attr ) ? '' : ' ' . trim( $el_attr );
			$html .= is_numeric( $tabidx ) ? '' : ' tabindex="' . esc_attr( $tabidx ) . '"';

			foreach ( $len as $key => $val ) {

				$html .= empty( $len[ $key ] ) ? '' : ' ' . $key . 'Length="' . esc_attr( $len[ $key ] ) . '"';
			}

			$html .= $this->get_placeholder_attrs( $type = 'input', $holder, $name );
			$html .= ' value="' . esc_attr( $value ) . '" />' . "\n";
			$html .= empty( $len ) ? '' : '<div id="text_' . $input_id . '-text-length-message"></div>' . "\n";

			if ( ! empty( $len ) ) {

				$html .= $this->get_textlen_script( 'text_' . $input_id );
			}

			return $html;
		}

		public function get_input_color( $name = '', $css_class = '', $css_id = '', $is_disabled = false ) {

			$input_class = SucomUtil::sanitize_css_class( 'colorpicker ' . $css_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );

			if ( empty( $name ) ) {

				$input_value = '';
				$is_disabled = true;

			} else {

				$input_value = $this->in_options( $name ) ? $this->options[ $name ] : '';

				if ( 'disabled' === $this->get_options( $name . ':is' ) ) {

					$is_disabled = true;
				}
			}

			$html = '<input type="text"';
			$html .= $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';
			$html .= empty( $input_id ) ? '' : ' id="text_' . $input_id . '"';
			$html .= ' placeholder="#000000" value="' . esc_attr( $input_value ) . '"';
			$html .= ' data-default-color="' . esc_attr( $input_value ) . '"';
			$html .= '/>';

			return $html;
		}

		public function get_input_date( $name = '', $css_class = '', $css_id = '', $min_date = '', $max_date = '', $is_disabled = false ) {

			$input_class = SucomUtil::sanitize_css_class( 'datepicker ' . $css_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );

			if ( empty( $name ) ) {

				$input_value = '';
				$is_disabled = true;

			} else {

				$input_value = $this->in_options( $name ) ? $this->options[ $name ] : '';

				if ( $this->get_options( $name . ':is' ) === 'disabled' ) {

					$is_disabled = true;
				}
			}

			$html = '<input type="text"';
			$html .= $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';
			$html .= empty( $input_id ) ? '' : ' id="text_' . $input_id . '"';
			$html .= empty( $min_date ) ? '' : ' min="' . esc_attr( $min_date ) . '"';
			$html .= empty( $max_date ) ? '' : ' max="' . esc_attr( $max_date ) . '"';
			$html .= ' placeholder="yyyy-mm-dd" value="' . esc_attr( $input_value ) . '" />';

			return $html;
		}

		public function get_input_image_crop_area( $name, $add_none = false, $is_disabled = false ) {

			$css_class = 'crop-area';
			$css_id    = '';
			$is_assoc  = true;
			$html      = '';

			foreach ( array( 'crop_x', 'crop_y' ) as $key ) {

				$values = $this->p->cf[ 'form' ][ 'position_' . $key ];

				if ( $add_none ) {

					$html .= $this->get_select_none( $name . '_' . $key, $values, $css_class, $css_id, $is_assoc, $is_disabled );

				} else {

					$html .= $this->get_select( $name . '_' . $key, $values, $css_class, $css_id, $is_assoc, $is_disabled );
				}
			}

			return $html;
		}

		public function get_input_image_dimensions( $name_prefix, $is_disabled = false ) {

			$html = $this->get_input( $name_prefix . '_width', $css_class = 'size width', $css_id = '', $len = 0, $holder = '', $is_disabled ) . 'x';
			$html .= $this->get_input( $name_prefix . '_height', $css_class = 'size height', $css_id = '', $len = 0, $holder = '', $is_disabled ) . 'px' . ' ';
			$html .= _x( 'crop', 'option comment', $this->text_domain ) . ' ' . $this->get_checkbox( $name_prefix . '_crop', '', '', $is_disabled );
			$html .= ' <div class="image_crop_area">' . _x( 'from', 'option comment', $this->text_domain ) . ' ';
			$html .= $this->get_input_image_crop_area( $name_prefix, $add_none = false, $is_disabled );
			$html .= '</div>';

			return $html;
		}

		public function get_input_image_upload( $name_prefix, $img_id_holder = '', $is_disabled = false, $input_name_id_attr = '' ) {

			// translators: Please ignore - translation uses a different text domain.
			$img_libs     = array( 'wp' => __( 'Media Library' ) );
			$selected_lib = false;

			list( $name_prefix, $name_suffix ) = $this->split_name_locale( $name_prefix );

			$input_name_id_locale  = $name_prefix . '_id' . $name_suffix;
			$input_name_lib_locale = $name_prefix . '_id_lib' . $name_suffix;
			$input_name_url_locale = $name_prefix . '_url' . $name_suffix;

			$input_disabled = 'disabled' === $this->get_options( $input_name_id_locale . ':is' ) ? true : $is_disabled;

			$img_id_value  = $this->get_options_locale( $input_name_id_locale );
			$img_lib_value = $this->get_options_locale( $input_name_lib_locale );
			$img_url_value = $this->get_options_locale( $input_name_url_locale );

			$upload_css_class  = 'sucom_image_upload_button button';
			$img_id_css_class  = 'sucom_image_upload_id';
			$img_lib_css_class = 'sucom_image_upload_lib';

			$preview_css_id = SucomUtil::sanitize_css_id( 'preview_' . $input_name_id_locale );
			$upload_css_id  = SucomUtil::sanitize_css_id( 'upload_' . $input_name_id_locale );
			$img_id_css_id  = SucomUtil::sanitize_css_id( $input_name_id_locale );
			$img_lib_css_id = SucomUtil::sanitize_css_id( $input_name_lib_locale );
			$img_url_css_id = SucomUtil::sanitize_css_id( $input_name_url_locale );

			$input_name_id_attr .= 'data-preview-css-id="' . $preview_css_id . '"' .
				' data-img-lib-css-id="select_' . $img_lib_css_id . '"' .
				' data-img-url-css-id="text_' . $img_url_css_id . '"';

			$input_name_lib_attr = 'data-img-id-css-id="text_' . $img_id_css_id . '"' .
				'data-upload-css-id="button_' . $upload_css_id . '"';

			$upload_button_data = array(
				'img-id-css-id'  => 'text_' . $img_id_css_id,
				'img-lib-css-id' => 'select_' . $img_lib_css_id,
			);

			if ( 'wp' === $img_lib_value ) {

				if ( ! empty( $img_id_value ) ) {

					$upload_button_data[ 'wp-img-id' ] = $img_id_value;

				} elseif ( ! empty( $img_id_holder ) ) {

					$upload_button_data[ 'wp-img-id' ] = $img_id_holder;
				}
			}

			if ( 0 === strpos( $img_id_holder, 'ngg-' ) ) {

				$img_id_holder = preg_replace( '/^ngg-/', '', $img_id_holder );

				$selected_lib = 'ngg';

			} elseif ( ! empty( $img_id_holder ) ) {

				$selected_lib = 'wp';
			}

			if ( 'ngg' === $img_lib_value || ! empty( $this->p->avail[ 'media' ][ 'ngg' ] ) ) {

				$img_libs[ 'ngg' ] = 'NextGEN Gallery';
			}

			$img_libs_count    = count( $img_libs );
			$img_libs_disabled = $img_libs_count > 1 ? $input_disabled : true;

			/**
			 * Prevent conflicts by removing the image URL if we have an image ID.
			 *
			 * Disable the image ID option if we have an image URL.
			 */
			if ( ! empty( $img_id_value ) ) {

				unset( $this->options[ $input_name_url_locale ] );
				unset( $this->options[ $input_name_url_locale . ':is' ] );
				unset( $this->options[ $input_name_url_locale . ':width' ] );
				unset( $this->options[ $input_name_url_locale . ':height' ] );

			} elseif ( ! empty( $img_url_value ) ) {

				unset( $this->options[ $input_name_id_locale ] );
				unset( $this->options[ $input_name_lib_locale ] );

				$img_id_holder = '';

				$input_disabled = true;
			}

			if ( ! empty( $img_libs[ 'wp' ] ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$upload_label    = __( 'Select Image' );
				$upload_disabled = function_exists( 'wp_enqueue_media' ) ? $input_disabled : true;	// Just in case.
				$upload_button   = $this->get_button( $upload_label, $upload_css_class, $upload_css_id,
					$url = '', $newtab = false, $upload_disabled, $upload_button_data );

				if ( 1 === $img_libs_count ) {

					$img_lib_css_class .= ' hidden';
				}
			}

			$select_lib = $this->get_select( $input_name_lib_locale, $img_libs, $img_lib_css_class, $img_lib_css_id,
				$is_assoc = true, $img_libs_disabled, $selected_lib, $event_names = array(), $event_args = null,
					$input_name_lib_attr );

			$input_id = $this->get_input( $input_name_id_locale, $img_id_css_class, $img_id_css_id,
				$len = 0, $img_id_holder, $input_disabled, $tabidx = null, $input_name_id_attr );

			$html = '<div class="sucom_image_upload">';
			$html .= $select_lib . ' ';
			$html .= $input_id . ' ';
			$html .= $upload_button . ' ';
			$html .= '</div>';
			$html .= '<div class="sucom_image_upload_preview" id="' . $preview_css_id . '"></div>';

			return $html;
		}

		public function get_input_image_url( $name_prefix, $url = '', $is_disabled = false ) {

			return $this->get_input_media_url( $name_prefix, $media_suffix = 'id', $url, $is_disabled );
		}

		public function get_input_video_dimensions( $name_prefix, $media_info = array(), $is_disabled = false ) {

			$holder_w = '';
			$holder_h = '';
			$html     = '';

			if ( ! empty( $media_info ) && is_array( $media_info ) ) {

				$holder_w = empty( $media_info[ 'vid_width' ] ) ? '' : $media_info[ 'vid_width' ];
				$holder_h = empty( $media_info[ 'vid_height' ] ) ? '' : $media_info[ 'vid_height' ];
			}

			$html = $this->get_input( $name_prefix . '_width', 'size width', '', 0, $holder_w, $is_disabled ) . 'x&nbsp;';
			$html .= $this->get_input( $name_prefix . '_height', 'size height', '', 0, $holder_h, $is_disabled ) . 'px';

			return $html;
		}

		public function get_input_video_url( $name_prefix, $url = '', $is_disabled = false ) {

			return $this->get_input_media_url( $name_prefix, $media_suffix = 'embed', $url, $is_disabled );
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

				$display  = $one_more || $key_num < $show_first ? true : false;
				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;

				$input_name     = $name . '_' . $key_num;
				$input_class    = SucomUtil::sanitize_css_class( $css_class );
				$input_id       = SucomUtil::sanitize_css_id( empty( $css_id ) ? $input_name : $css_id . '_' . $key_num );
				$input_id_prev  = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num );
				$input_id_next  = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num );
				$input_value    = $this->in_options( $input_name ) ? $this->options[ $input_name ] : '';
				$input_disabled = 'disabled' === $this->get_options( $input_name . ':is' ) ? true : $is_disabled;

				if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {

					continue;
				}

				$el_attr = 'onFocus="if ( jQuery(\'input#text_' . $input_id_prev . '\').val().length )' .
					' { jQuery(\'div#multi_' . $input_id_next . '\').show(); }"';

				$html .= '<div';
				$html .= ' class="multi_container input_multi"';
				$html .= ' id="multi_' . $input_id . '"';
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";

				$html .= '<div class="multi_number">' . ( $key_num + 1 ) . '.</div>' . "\n";

				$html .= '<div class="multi_input">' . "\n";

				$html .= '<input type="text"';
				$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"';
				$html .= $input_disabled ? ' disabled="disabled"' : '';
				$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
				$html .= empty( $input_id ) ? '' : ' id="text_' . $input_id . '"';	// Already sanitized.
				$html .= ' value="' . $input_value . '"';
				$html .= ' ' . $el_attr . '/>' . "\n";

				$html .= '</div><!-- .multi_input -->' . "\n";

				$html .= '</div><!-- .multi_container -->' . "\n";

				$one_more = empty( $input_value ) && ! is_numeric( $input_value ) ? false : true;

			}

			return $html;
		}

		/**
		 * Radio input field.
		 */
		public function get_radio( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $is_disabled = false ) {

			if ( empty( $name ) || ! is_array( $values ) ) {

				return;
			}

			if ( null === $is_assoc ) {

				$is_assoc = SucomUtil::is_assoc( $values );
			}

			$input_class    = SucomUtil::sanitize_css_class( $css_class );
			$input_id       = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );
			$input_disabled = 'disabled' === $this->get_options( $name . ':is' ) ? true : $is_disabled;

			/**
			 * Use the "input_vertical_list" class to align the radio input buttons vertically.
			 */
			$html = '<div class="' . $input_class . '" id="radio_' . $input_id . '">' . "\n";	// Already sanitized.

			foreach ( $values as $val => $label ) {

				if ( is_array( $label ) ) {	// Just in case.

					$label = implode( $glue = ', ', $label );
				}

				/**
				 * If the array is not associative (so a regular numbered array), then the label / description is
				 * used as the saved value.
				 */
				if ( ! $is_assoc ) {

					$val = $label;
				}

				$label_transl = $this->get_value_transl( $label );

				$html .= '<span><input type="radio"';
				$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" value="' . esc_attr( $val ) . '"';
				$html .= $input_disabled ? ' disabled="disabled"' : '';
				$html .= $this->in_options( $name ) ? checked( $this->options[ $name ], $val, false ) : '';
				$html .= $this->in_defaults( $name ) ? ' title="default is ' . $values[ $this->defaults[ $name ] ] . '"' : '';
				$html .= '/>&nbsp;' . $label_transl . '&nbsp;&nbsp;</span>';
				$html .= "\n";
			}

			$html .= '</div>' . "\n";

			return $html;
		}

		/**
		 * Select drop-down field.
		 *
		 * $is_disabled can be false or an option value for the disabled select.
		 */
		public function get_select( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $is_disabled = false, $selected = false,
			$event_names = array(), $event_args = null, $el_attr = '' ) {

			if ( empty( $name ) ) {

				return '';
			}

			$filter_name = SucomUtil::sanitize_hookname( $this->plugin_id . '_form_select_' . $name );
			$values      = apply_filters( $filter_name, $values );

			if ( ! is_array( $values ) ) {

				return '';
			}

			if ( null === $is_assoc ) {

				$is_assoc  = SucomUtil::is_assoc( $values );
			}

			if ( is_string( $event_names ) ) {

				$event_names = array( $event_names );

			} elseif ( ! is_array( $event_names ) ) {	// Ignore true, false, null, etc.

				$event_names = array();
			}

			$event_json_var = false;

			if ( in_array( 'on_focus_load_json', $event_names ) ) {

				if ( ! empty( $event_args ) ) {

					if ( is_string( $event_args ) ) {

						$event_json_var = preg_replace( '/:.$/', '', $event_args );
						$event_json_var = SucomUtil::sanitize_hookname( $this->plugin_id . '_form_select_' . $event_json_var . '_json' );

					} elseif ( ! empty( $event_args[ 'json_var' ] ) ) {

						$event_json_var = SucomUtil::sanitize_hookname( $this->plugin_id . '_form_select_' . $event_args[ 'json_var' ] . '_json' );
					}
				}
			}

			$html           = '';
			$row_id         = empty( $css_id ) ? 'tr_' . $name : 'tr_' . $css_id;
			$input_class    = SucomUtil::sanitize_css_class( $css_class );
			$input_id       = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );
			$input_disabled = 'disabled' === $this->get_options( $name . ':is' ) ? true : $is_disabled;
			$in_options     = $this->in_options( $name );	// Optimize and call only once - returns true or false.
			$in_defaults    = $this->in_defaults( $name );	// Optimize and call only once - returns true or false.
			$selected_value = '';

			$select_opt_count = 0;	// Used to check for first option.
			$select_opt_added = 0;
			$select_opt_arr   = array();
			$select_json_arr  = array();
			$default_value    = '';
			$default_text     = '';

			foreach ( $values as $option_value => $label ) {

				$select_opt_count++;	// Used to check for first option.

				if ( is_array( $label ) ) {	// Just in case.

					$label = implode( $glue = ', ', $label );
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

				/**
				 * Don't bother translating the label text if it's already translated (for example, product
				 * categories).
				 */
				if ( empty( $event_args[ 'is_transl' ] ) ) {

					$label_transl = $this->get_value_transl( $label );

				} else {

					$label_transl = $label;
				}

				switch ( $name ) {

					case 'og_img_max':
					case 'schema_img_max':

						if ( 0 === $label ) {

							$label_transl .= ' ' . $this->get_value_transl( '(no images)' );
						}

						break;

					case 'og_vid_max':
					case 'schema_vid_max':

						if ( 0 === $label ) {

							$label_transl .= ' ' . $this->get_value_transl( '(no videos)' );
						}

						break;

					default:

						if ( '' ===  $label || 'none' === $label || '[None]' === $label ) {	// Just in case.

							$label_transl = $this->get_value_transl( '[None]' );
						}

						break;
				}

				/**
				 * Save the option value and translated label for the JSON array before adding the "(default)"
				 * suffix.
				 */
				if ( $event_json_var ) {

					if ( empty( $this->json_array_added[ $event_json_var ] ) ) {

						$select_json_arr[ $option_value ] = $label_transl;
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
				 * Only include the first and selected option(s).
				 */
				if ( ( ! $is_disabled && ! $event_json_var ) || $is_selected_html || $select_opt_count === 1 ) {

					if ( ! isset( $select_opt_arr[ $option_value ] ) ) {

						$select_opt_arr[ $option_value ] = '<option value="' . esc_attr( $option_value ) . '"' .
							$is_selected_html . '>' . $label_transl . '</option>';

						$select_opt_added++;
					}
				}
			}

			if ( empty( $event_args[ 'is_sorted' ] ) ) {

				uasort( $select_opt_arr, array( 'self', 'sort_select_opt_by_label' ) );
			}

			$html .= "\n";
			$html .= '<select ';
			$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= $input_disabled ? ' disabled="disabled"' : '';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="select_' . $input_id . '"';	// Already sanitized.
			$html .= empty( $default_value ) ? '' : ' data-default-value="' . esc_attr( $default_value ) . '"';
			$html .= empty( $default_text ) ? '' : ' data-default-text="' . esc_attr( $default_text ) . '"';
			$html .= empty( $el_attr ) ? '' : ' ' . trim( $el_attr );
			$html .= '>' . "\n";
			$html .= implode( $glue = "\n", $select_opt_arr );
			$html .= '<!-- ' . $select_opt_added . ' select options added -->' . "\n";
			$html .= '</select>' . "\n";

			foreach ( $event_names as $event_name ) {

				$html .= '<!-- event name: ' . $event_name . ' -->' . "\n";

				switch ( $event_name ) {

					case 'on_focus_show':

						/**
						 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
						 */
						$html .= '<script>';
						$html .= 'jQuery( \'#select_' . $input_id . '\' ).on( \'focus\', function(){';
						$html .= 'jQuery( \'' . $event_args . '\' ).show();';
						$html .= '});';
						$html .= '</script>' . "\n";

						break;

					case 'on_focus_load_json':

						$html .= $this->get_event_load_json_script( $event_json_var, $event_args, $select_json_arr, 'select_' . $input_id );

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

						/**
						 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
						 */
						$html .= '<script>';
						$html .= 'jQuery( \'#select_' . $input_id . '\' ).on( \'change\', function(){';
						$html .= 'sucomSelectChangeRedirect( \'' . esc_js( $name ) . '\', this.value, \'' . $redirect_url_encoded . '\' );';
						$html .= '});';
						$html .= '</script>' . "\n";

						break;

					case 'on_show_unhide_rows':

						$html .= $this->get_show_hide_trigger_script();

						// No break.

					case 'on_change_unhide_rows':

						/**
						 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
						 */
						$html .= '<script>';
						$html .= 'jQuery( \'#select_' . $input_id . '\' ).on( \'change\', function(){';
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

							/**
							 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
							 */
							$html .= '<script>';

							if ( 'on_show_unhide_rows' === $event_name ) {

								$html .= 'jQuery( \'tr#' . esc_js( $row_id ) . '\' ).on( \'show\', function(){';
								$html .= 'sucomSelectChangeUnhideRows( \'' . $hide_class . '\', \'' . $show_class . '\' );';
								$html .= '});';

							} else {

								$doing_ajax = SucomUtilWP::doing_ajax();

								if ( $doing_ajax ) {

									$html .= 'sucomSelectChangeUnhideRows( \'' . $hide_class . '\', \'' . $show_class . '\' );';

								} else {

									$html .= 'jQuery( window ).on( \'load\', function(){';
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

				$display  = $one_more || $key_num < $show_first ? true : false;
				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;

				$input_name     = $name . '_' . $key_num;
				$input_class    = empty( $css_class ) ? '' : $css_class;
				$input_id       = SucomUtil::sanitize_css_id( empty( $css_id ) ? $input_name : $css_id . '_' . $key_num );
				$input_id_prev  = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num );
				$input_id_next  = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num );
				$input_value    = $this->in_options( $input_name ) ? $this->options[ $input_name ] : '';

				if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {

					continue;
				}

				$html .= '<div class="multi_container select_multi" id="multi_' . $input_id . '"';
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";

				$html .= '<div class="multi_number">' . ( $key_num + 1 ) . '.</div>' . "\n";

				$html .= '<div class="multi_input">' . "\n";

				/**
				 * $is_disabled can be true, false, or an option value for the disabled select.
				 */
				$html .= $this->get_select( $input_name, $values, $input_class, $input_id, $is_assoc,
					$is_disabled, $input_value, 'on_focus_show', 'div#multi_' . $input_id_next );

				$html .= is_string( $is_disabled ) ? $is_disabled : '';	// Allow for requirement comment.

				$html .= '</div><!-- .multi_input -->' . "\n";

				$html .= '</div><!-- .multi_container -->' . "\n";

				$one_more = 'none' === $input_value || ( empty( $input_value ) && ! is_numeric( $input_value ) ) ? false : true;
			}

			return $html;
		}

		/**
		 * Add 'none' as the first array element. Always converts the array to associative.
		 */
		public function get_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $is_disabled = false,
			$selected = false, $event_names = array(), $event_args = null ) {

			/**
			 * Set 'none' as the default if no default is defined.
			 */
			if ( ! empty( $name ) ) {

				if ( ! $this->in_defaults( $name ) ) {

					$this->defaults[ $name ] = 'none';
				}
			}

			if ( null === $is_assoc ) {

				$is_assoc  = SucomUtil::is_assoc( $values );
			}

			if ( ! $is_assoc ) {

				$new_values = array();

				foreach ( $values as $option_value => $label ) {

					if ( is_array( $label ) ) {	// Just in case.

						$label = implode( $glue = ', ', $label );
					}

					$new_values[ (string) $label ] = $label;
				}

				$values = $new_values;

				unset( $new_values );
			}

			unset( $values[ 'none' ] );	// Just in case.

			$values = array( 'none' => '[None]' ) + $values;

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc = true, $is_disabled, $selected, $event_names, $event_args );
		}

		/**
		 * The "time-hh-mm" class is always prefixed to the $css_class value.
		 *
		 * By default, the 'none' array elements is not added.
		 */
		public function get_select_time( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false, $step_mins = 15, $add_none = false ) {

			static $local_cache = array();

			if ( empty( $local_cache[ $step_mins ] ) ) {

				$local_cache[ $step_mins ] = SucomUtil::get_hours_range( $start_secs = 0, $end_secs = DAY_IN_SECONDS,
					$step_secs = 60 * $step_mins, $label_format = 'H:i' );
			}

			$css_class   = trim( 'time-hh-mm ' . $css_class );
			$event_names = array( 'on_focus_load_json' );
			$event_args  = 'hour_mins_step_' . $step_mins;

			/**
			 * Set 'none' as the default if no default is defined.
			 */
			if ( $add_none ) {

				$event_args .= '_add_none';

				if ( ! empty( $name ) ) {

					if ( ! $this->in_defaults( $name ) ) {

						$this->defaults[ $name ] = 'none';
					}
				}

				return $this->get_select_none( $name, $local_cache[ $step_mins ], $css_class, $css_id, $is_assoc = true,
					$is_disabled, $selected, $event_names, $event_args );
			}

			return $this->get_select( $name, $local_cache[ $step_mins ], $css_class, $css_id, $is_assoc = true,
				$is_disabled, $selected, $event_names, $event_args );
		}

		public function get_select_time_none( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false, $step_mins = 15 ) {

			/**
			 * Set 'none' as the default if no default is defined.
			 */
			if ( ! empty( $name ) ) {

				if ( ! $this->in_defaults( $name ) ) {

					$this->defaults[ $name ] = 'none';
				}
			}

			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled, $selected, $step_mins, $add_none = true );
		}

		/**
		 * The "timezone" class is always prefixed to the $css_class value.
		 */
		public function get_select_timezone( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false ) {

			$css_class = trim( 'timezone ' . $css_class );

			/**
			 * Returns an associative array of timezone strings (ie. 'Africa/Abidjan'), 'UTC', and offsets (ie. '-07:00').
			 */
			$timezones = SucomUtilWP::get_timezones();

			if ( ! empty( $name ) ) {

				if ( ! $this->in_defaults( $name ) ) {

					$this->defaults[ $name ] = SucomUtilWP::get_default_timezone();
				}
			}

			return $this->get_select( $name, $timezones, $css_class, $css_id, $is_assoc = true, $is_disabled, $selected,
				$event_names = array( 'on_focus_load_json' ), $event_args = 'timezones');
		}

		public function get_select_country( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false ) {

			/**
			 * Set 'none' as the default if no default is defined.
			 */
			if ( ! empty( $name ) ) {

				if ( ! $this->in_defaults( $name ) ) {

					$this->defaults[ $name ] = 'none';
				}
			}

			/**
			 * Sanity check for possibly older input field values.
			 */
			if ( false === $selected ) {

				if ( empty( $this->options[ $name ] ) || 
					( 'none' !== $this->options[ $name ] && 
						2 !== strlen( $this->options[ $name ] ) ) ) {

					$selected = $this->defaults[ $name ];
				}
			}

			$values = array( 'none' => '[None]' ) + SucomUtil::get_alpha2_countries();

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc = true, $is_disabled, $selected );
		}

		public function get_submit( $value, $css_class = 'button-primary', $css_id = '' ) {

			$input_class    = SucomUtil::sanitize_css_class( $css_class );
			$input_id       = SucomUtil::sanitize_css_id( $css_id );

			$html = '<input type="submit"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="submit_' . $input_id . '"';	// Already sanitized.
			$html .= ' value="' . esc_attr( $value ) . '"/>';

			return $html;
		}

		public function get_video_dimensions_text( $name_prefix, $media_info ) {

			if ( ! empty( $this->options[ $name_prefix . '_width' ] ) && ! empty( $this->options[ $name_prefix . '_height' ] ) ) {

				return $this->options[ $name_prefix . '_width' ] . 'x' . $this->options[ $name_prefix . '_height' ] . 'px';

			} elseif ( ! empty( $media_info ) && is_array( $media_info ) ) {

				$def_width  = empty( $media_info[ 'vid_width' ] ) ? '' : $media_info[ 'vid_width' ];
				$def_height = empty( $media_info[ 'vid_height' ] ) ? '' : $media_info[ 'vid_height' ];

				if ( ! empty( $def_width ) && ! empty( $def_height ) ) {

					return $def_width . 'x' . $def_height . 'px';
				}
			}

			return '';
		}

		public function get_textarea( $name, $css_class = '', $css_id = '', $len = 0, $holder = '', $is_disabled = false ) {

			if ( empty( $name ) ) {

				return;	// Just in case.
			}

			if ( 'disabled' === $this->get_options( $name . ':is' ) ) {

				$is_disabled = true;
			}

			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );
			$value       = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$holder      = $this->get_placeholder_sanitized( $name, $holder );

			if ( ! is_array( $len ) ) {

				$len = array( 'max' => $len );
			}

			$html = '<textarea ';
			$html .= $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="textarea_' . $input_id . '"';	// Already sanitized.
			$html .= empty( $len[ 'max' ] ) || $is_disabled ? '' : ' maxLength="' . esc_attr( $len[ 'max' ] ) . '"';
			$html .= empty( $len[ 'warn' ] ) || $is_disabled ? '' : ' warnLength="' . esc_attr( $len[ 'warn' ] ) . '"';
			$html .= empty( $len[ 'max' ] ) && empty( $len[ 'rows' ] ) ? '' :
				( empty( $len[ 'rows' ] ) ? ' rows="'.( round( $len[ 'max' ] / 100 ) + 1 ) . '"' : ' rows="' . $len[ 'rows' ] . '"' );
			$html .= $this->get_placeholder_attrs( $type = 'textarea', $holder ) . '>' . esc_attr( $value ) . '</textarea>';
			$html .= empty( $len[ 'max' ] ) || $is_disabled ? '' : ' <div id="textarea_' . $input_id . '-text-length-message"></div>';

			if ( ! empty( $len[ 'max' ] ) ) {

				$html .= $this->get_textlen_script( 'textarea_' . $input_id );
			}

			return $html;
		}

		public function get_button( $value, $css_class = '', $css_id = '', $url = '', $newtab = false, $is_disabled = false, $el_data = array() ) {

			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$input_id    = SucomUtil::sanitize_css_id( $css_id );

			if ( true === $newtab ) {

				$on_click = ' onClick="window.open(\'' . SucomUtil::esc_url_encode( $url ) . '\', \'_blank\');"';

			} else {

				$on_click = ' onClick="window.location.href = \'' . SucomUtil::esc_url_encode( $url ) . '\';"';
			}

			$el_attr = '';

			if ( is_array( $el_data ) ) {

				foreach ( $el_data as $data_key => $data_value ) {

					$el_attr .= ' data-' . $data_key . '="' . esc_attr( $data_value ) . '"';
				}

			} else {

				$el_attr = $el_data;
			}

			$html = '<input type="button" ';
			$html .= $is_disabled ? ' disabled="disabled"' : '';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="button_' . $input_id . '"';	// Already sanitized.
			$html .= empty( $url ) || $is_disabled ? '' : $on_click;
			$html .= empty( $el_attr ) ? '' : ' ' . trim( $el_attr );
			$html .= ' value="' . esc_attr( wp_kses( $value, array() ) ) . '"/>';

			return $html;
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

				$display  = $one_more || $key_num < $show_first ? true : false;
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

				$html .= '<div class="multi_number">' . ( $key_num + 1 ) . '.</div>' . "\n";

				$html .= '<div class="multi_input">' . "\n";

				$one_more = false;	// Return to default.

				$multi_label_num = 0;

				foreach ( $mixed as $name => $atts ) {

					$input_name     = $name . '_' . $key_num;
					$input_title    = empty( $atts[ 'input_title' ] ) ? '' : $atts[ 'input_title' ];
					$input_class    = SucomUtil::sanitize_css_class( empty( $atts[ 'input_class' ] ) ? '' : $atts[ 'input_class' ] );
					$input_id       = SucomUtil::sanitize_css_id( empty( $atts[ 'input_id' ] ) ? $input_name : $atts[ 'input_id' ] . '_' . $key_num );
					$input_content  = empty( $atts[ 'input_content' ] ) ? '' : $atts[ 'input_content' ];
					$input_values   = empty( $atts[ 'input_values' ] ) ? array() : $atts[ 'input_values' ];
					$input_disabled = 'disabled' === $this->get_options( $input_name . ':is' ) ? true : $is_disabled;
					$in_options     = $this->in_options( $input_name );	// Optimize and call only once.
					$in_defaults    = $this->in_defaults( $input_name );	// Optimize and call only once.

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

					$event_json_var = false;

					if ( in_array( 'on_focus_load_json', $event_names ) ) {

						if ( ! empty( $event_args ) ) {

							if ( is_string( $event_args ) ) {

								$event_json_var = preg_replace( '/:.$/', '', $event_args );
								$event_json_var = SucomUtil::sanitize_hookname( $this->plugin_id . '_form_select_' .
									$event_json_var . '_json' );

							} elseif ( ! empty( $event_args[ 'json_var' ] ) ) {

								$event_json_var = SucomUtil::sanitize_hookname( $this->plugin_id . '_form_select_' .
									$event_args[ 'json_var' ] . '_json' );
							}
						}
					}

					if ( isset( $atts[ 'placeholder' ] ) ) {

						$holder = $this->get_placeholder_sanitized( $input_name, $atts[ 'placeholder' ] );

					} else {

						$holder = '';
					}

					if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {

						continue;
					}

					/**
					 * Default paragraph display is an inline-block.
					 */
					if ( ! empty( $atts[ 'input_label' ] ) ) {

						$multi_label_num++;

						$html .= '<p class="multi_label' . ( 1 === $multi_label_num ? ' first_label' : '' ) . '">' .
							$atts[ 'input_label' ] . ':</p> ';
					}

					if ( isset( $atts[ 'input_type' ] ) ) {

						switch ( $atts[ 'input_type' ] ) {

							case 'radio':

								$radio_inputs = array();

								foreach ( $input_values as $input_value ) {

									if ( $in_options ) {

										$input_checked = checked( $this->options[ $input_name ], $input_value, false );

									} elseif ( isset( $atts[ 'input_default' ] ) ) {

										$input_checked = checked( $atts[ 'input_default' ], $input_value, false );

									} elseif ( $in_defaults ) {

										$input_checked = checked( $this->defaults[ $input_name ], $input_value, false );

									} else {

										$input_checked = '';
									}

									$radio_inputs[] = '<input type="radio"' . ( $input_disabled ? ' disabled="disabled"' :
										' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"' .
										' value="' . esc_attr( $input_value ) . '"' ) . $input_checked . '/>';
								}

								if ( ! empty( $radio_inputs ) ) {

									$html .= '<p';
									$html .= ' class="' . $input_class . '"';	// Already sanitized.
									$html .= ' id="' . $input_id . '"';		// Already sanitized.
									$html .= ' ' . $el_attr . '>';
									$html .= vsprintf( $atts[ 'input_content' ], $radio_inputs );
									$html .= '</p>' . "\n";
								}

								break;

							case 'text':

								$input_value = $in_options ? $this->options[ $input_name ] : '';

								$html .= '<input type="text"' . ( $input_disabled ? ' disabled="disabled"' : '' ) .
									' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"' .
									' title="' . esc_attr( $input_title ) . '"' .
									' class="' . $input_class . '"' .	// Already sanitized.
									' id="text_' . $input_id . '"' .	// Already sanitized.
									' value="' . esc_attr( $input_value ) . '"' .
									' ' . $el_attr . '/>' . "\n";

								if ( $input_value || is_numeric( $input_value ) ) {

									$one_more = true;
								}

								break;

							case 'textarea':

								$input_value = $in_options ? $this->options[ $input_name ] : '';

								$html .= '<textarea ' . ( $input_disabled ? ' disabled="disabled"' : '' ) .
									' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"' .
									' title="' . esc_attr( $input_title ) . '"' .
									' class="' . $input_class . '"' .	// Already sanitized.
									' id="textarea_' . $input_id . '"' .	// Already sanitized.
									( $this->get_placeholder_attrs( $type = 'textarea', $holder ) ) .
									'>' . esc_attr( $input_value ) . '</textarea>' . "\n";

								if ( $input_value || is_numeric( $input_value ) ) {

									$one_more = true;
								}

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
								$select_opt_arr   = array();
								$select_json_arr  = array();
								$default_value    = '';
								$default_text     = '';

								foreach ( $select_options as $option_value => $label ) {

									if ( is_array( $label ) ) {	// Just in case.

										$label = implode( $glue = ', ', $label );
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

									/**
									 * Don't bother translating the label text if it's already
									 * translated (for example, product categories).
									 */
									if ( empty( $event_args[ 'is_transl' ] ) ) {

										$label_transl = $this->get_value_transl( $label );

									} else {

										$label_transl = $label;
									}

									/**
									 * Save the option value and translated label for the JSON
									 * array before adding the "(default)" suffix.
									 */
									if ( $event_json_var ) {

										if ( empty( $this->json_array_added[ $event_json_var ] ) ) {

											$select_json_arr[ $option_value ] = $label_transl;
										}
									}

									/**
									 * Save the default value and its text so we can add them (as jquery data) to the select.
									 */
									if ( ( $in_defaults && $option_value === (string) $this->defaults[ $input_name ] ) ||
										( null !== $select_default && $option_value === $select_default ) ) {

										$default_value = $option_value;
										$default_text  = $this->get_value_transl( '(default)' );

										$label_transl .= ' ' . $default_text;
									}

									if ( $select_selected !== null ) {

										$is_selected_html = selected( $select_selected, $option_value, false );

									} elseif ( $in_options ) {

										$is_selected_html = selected( $this->options[ $input_name ], $option_value, false );

									} elseif ( $select_default !== null ) {

										$is_selected_html = selected( $select_default, $option_value, false );

									} elseif ( $in_defaults ) {

										$is_selected_html = selected( $this->defaults[ $input_name ], $option_value, false );

									} else {

										$is_selected_html = '';
									}

									$select_opt_count++; 	// Used to check for first option.

									/**
									 * For disabled selects, only include the first and/or selected option.
									 */
									if ( ( ! $is_disabled && ! $event_json_var ) || $is_selected_html || $select_opt_count === 1 ) {

										if ( ! isset( $select_opt_arr[ $option_value ] ) ) {

											$select_opt_arr[ $option_value ] = '<option value="' . esc_attr( $option_value ) . '"' .
												$is_selected_html . '>' . $label_transl . '</option>';

											$select_opt_added++;
										}
									}
								}

								if ( empty( $event_args[ 'is_sorted' ] ) ) {

									uasort( $select_opt_arr, array( 'self', 'sort_select_opt_by_label' ) );
								}

								$html .= "\n" . '<select ';
								$html .= $input_disabled ? ' disabled="disabled"' : '';
								$html .= ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"';
								$html .= ' title="' . esc_attr( $input_title ) . '"';
								$html .= empty( $input_class ) ? '' : ' class="' . esc_attr( $input_class ) . '"';
								$html .= empty( $input_id ) ? '' : ' id="select_' . $input_id . '"';
								$html .= empty( $default_value ) ? '' : ' data-default-value="' . esc_attr( $default_value ) . '"';
								$html .= empty( $default_text ) ? '' : ' data-default-text="' . esc_attr( $default_text ) . '"';
								$html .= ' ' . $el_attr . '>' . "\n";
								$html .= implode( $glue = "\n", $select_opt_arr );
								$html .= '<!-- ' . $select_opt_added . ' select options added -->' . "\n";
								$html .= '</select>' . "\n";

								foreach ( $event_names as $event_name ) {

									$html .= '<!-- event name: ' . $event_name . ' -->' . "\n";

									switch ( $event_name ) {

										case 'on_focus_load_json':

											$html .= $this->get_event_load_json_script( $event_json_var, $event_args,
												$select_json_arr, 'select_' . $input_id );

											break;
									}
								}

								break;

							case 'image':

								$html .= '<div tabindex="-1" ' . $el_attr . '>' . "\n";

								$html .= $this->get_input_image_upload( $input_name, $holder, $is_disabled, $el_attr );

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

		/**
		 * Automatically localized methods.
		 */
		public function get_options_locale( $opt_key = false, $def_val = null ) {

			if ( false !== $opt_key ) {

				if ( null === $def_val ) {

					/**
					 * Returns an option value or null.
					 *
					 * Note that for non-existing keys or empty strings, this methods will return the default non-localized value.
					 */
					return SucomUtil::get_key_value( $opt_key, $this->options, $mixed = 'current' );
				}
			}

			return $this->get_options( $opt_key, $def_val );
		}

		public function get_defaults_locale( $opt_key = false ) {

			if ( false !== $opt_key ) {

				/**
				 * Returns an option value or null.
				 *
				 * Note that for non-existing keys or empty strings, this methods will return the default non-localized value.
				 */
				return SucomUtil::get_key_value( $opt_key, $this->defaults, $mixed = 'current' );
			}

			return $this->defaults;
		}

		public function get_input_locale( $name, $css_class = '', $css_id = '', $len = 0, $holder = '', $is_disabled = false ) {

			$name = SucomUtil::get_key_locale( $name, $this->options );

			return $this->get_input( $name, $css_class, $css_id, $len, $holder, $is_disabled );
		}

		public function get_input_image_upload_locale( $name_prefix, $holder = '', $is_disabled = false, $el_attr = '' ) {

			$name_prefix = SucomUtil::get_key_locale( $name_prefix, $this->options );

			return $this->get_input_image_upload( $name_prefix, $holder = '', $is_disabled = false, $el_attr = '' );
		}

		public function get_input_image_url_locale( $name_prefix, $url = '', $is_disabled = false ) {

			$name_prefix = SucomUtil::get_key_locale( $name_prefix, $this->options );

			return $this->get_input_image_url( $name_prefix, $url, $is_disabled );
		}

		public function get_input_video_url_locale( $name_prefix, $url = '', $is_disabled = false ) {

			$name_prefix = SucomUtil::get_key_locale( $name_prefix, $this->options );

			return $this->get_input_video_url( $name_prefix, $media_suffix = 'embed', $url, $is_disabled );
		}

		public function get_select_locale( $name, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $is_disabled = false, $selected = false, $event_names = array(), $event_args = null ) {

			$name = SucomUtil::get_key_locale( $name, $this->options );

			return $this->get_select( $name, $values, $css_class, $css_id,
				$is_assoc, $is_disabled, $selected, $event_names, $event_args );
		}

		public function get_textarea_locale( $name, $css_class = '', $css_id = '', $len = 0, $holder = '', $is_disabled = false ) {

			$name = SucomUtil::get_key_locale( $name, $this->options );

			return $this->get_textarea( $name, $css_class, $css_id, $len, $holder, $is_disabled );
		}

		public function get_th_html_locale( $label = '', $css_class = '', $css_id = '', $atts = array() ) {

			$atts[ 'is_locale' ] = true;

			return $this->get_th_html( $label, $css_class, $css_id, $atts );
		}

		/**
		 * Automatically disabled methods.
		 */
		public function get_no_td_checkbox( $name, $comment = '', $extra_css_class = '' ) {

			return '<td class="blank ' . $extra_css_class . '">' . $this->get_no_checkbox_comment( $name, $comment ) . '</td>';
		}

		public function get_no_checkbox( $name, $css_class = '', $css_id = '', $force = null, $group = null ) {

			return $this->get_checkbox( $name, $css_class, $css_id, $is_disabled = true, $force, $group );
		}

		public function get_no_checkbox_options( $name, array $opts, $css_class = '', $css_id = '', $group = null ) {

			$force = empty( $opts[ $name ] ) ? 0 : 1;

			return $this->get_checkbox( $name, $css_class, $css_id, $is_disabled = true, $force, $group );
		}

		public function get_no_checkbox_comment( $name, $comment = '' ) {

			return $this->get_checkbox( $name, $css_class = '', $css_id = '', $is_disabled = true ) . ( empty( $comment ) ? '' : ' ' . $comment );
		}

		public function get_no_checklist( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $is_assoc = null ) {

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc, $is_disabled = true );
		}

		public function get_no_checklist_post_types( $name_prefix, $css_class = 'input_vertical_list', $css_id = '' ) {

			return $this->get_checklist_post_types( $name_prefix, $css_class, $css_id, $is_disabled = true );
		}

		public function get_no_checklist_post_tax_user( $name_prefix, $css_class = 'input_vertical_list', $css_id = '' ) {

			return $this->get_checklist_post_tax_user( $name_prefix, $css_class, $css_id, $is_disabled = true );
		}

		public function get_no_date_time_tz( $name_prefix = '' ) {

			return $this->get_date_time_tz( $name_prefix, $is_disabled = true );
		}

		public function get_no_input( $name = '', $css_class = '', $css_id = '', $holder = '' ) {

			$html   = '';
			$value  = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$holder = $this->get_placeholder_sanitized( $name, $holder );

			if ( ! empty( $name ) ) {

				$html .= $this->get_hidden( $name );
			}

			$html .= $this->get_no_input_value( $value, $css_class, $css_id, $holder );

			return $html;
		}

		public static function get_no_input_clipboard( $value, $css_class = 'wide', $css_id = '' ) {

			if ( empty( $css_id ) ) {	// Make sure we have an ID string.

				$css_id = uniqid();
			}

			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$input_id    = SucomUtil::sanitize_css_id( $css_id );
			$input_text  = '<input type="text"' .
				( empty( $input_class ) ? '' : ' class="' . esc_attr( $input_class ) . '"' ) .
				( empty( $input_id ) ? '' : ' id="text_' . esc_attr( $input_id ) . '"' ) .
				' value="' . esc_attr( $value ) . '" readonly' .
				' onFocus="this.select();"' .
				' onMouseUp="return false;"/>';

			/**
			 * Add a dashicons copy-to-clipboard button to the input text field.
			 */
			if ( ! empty( $input_id ) ) {	// Just in case.

				$html = '<div class="no_input_clipboard">';
				$html .= '<div class="copy_button"><a href="" onClick="return sucomCopyById( \'text_' . $input_id . '\' );">';
				$html .= '<span class="dashicons dashicons-clipboard"></span>';
				$html .= '</a></div><!-- .copy_button -->' . "\n";
				$html .= '<div class="copy_text">' . $input_text . '</div><!-- .copy_text -->' . "\n";
				$html .= '</div><!-- .no_input_clipboard -->' . "\n";

			} else {

				$html = $input_text;
			}

			return $html;
		}

		public function get_no_input_options( $name, array $opts, $css_class = '', $css_id = '', $holder = '' ) {

			$value = isset( $opts[ $name ] ) ? $opts[ $name ] : '';

			return $this->get_no_input_value( $value, $css_class, $css_id, $holder );
		}

		public function get_no_input_holder( $holder = '', $css_class = '', $css_id = '' ) {

			return $this->get_no_input_value( $value = '', $css_class, $css_id, $holder );
		}

		public function get_no_input_date( $name = '' ) {

			return $this->get_input_date( $name, $css_class = '', $css_id = '', $min_date = '', $max_date = '', $is_disabled = true );
		}

		public function get_no_input_date_options( $name, $opts ) {

			$value = isset( $opts[ $name ] ) ? $opts[ $name ] : '';

			return $this->get_no_input_value( $value, 'datepicker', '', 'yyyy-mm-dd' );
		}

		public function get_no_input_image_crop_area( $name, $add_none = false ) {

			return $this->get_input_image_crop_area( $name, $add_none = false, $is_disabled = true );
		}

		public function get_no_input_image_dimensions( $name ) {

			return $this->get_input_image_dimensions( $name, $is_disabled = true );
		}

		public function get_no_input_image_upload( $name_prefix, $holder = '' ) {

			return $this->get_input_image_upload( $name_prefix, $holder, $is_disabled = true );
		}

		public function get_no_input_video_dimensions( $name, $media_info = array() ) {

			return $this->get_input_video_dimensions( $name, $media_info, $is_disabled = true );
		}

		public function get_no_input_multi( $name, $css_class = '', $css_id = '', $start_num = 0, $max_input = 90, $show_first = 5 ) {

			return $this->get_input_multi( $name, $css_class, $css_id, $start_num, $max_input, $show_first, $is_disabled = true );
		}

		public function get_no_input_value( $value = '', $css_class = '', $css_id = '', $holder = '', $max_input = 1 ) {

			$html        = '';
			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$holder      = $this->get_placeholder_sanitized( $name = '', $holder );
			$end_num     = $max_input > 0 ? $max_input - 1 : 0;

			foreach ( range( 0, $end_num, 1 ) as $key_num ) {

				if ( $max_input > 1 ) {

					$input_id = SucomUtil::sanitize_css_id( empty( $css_id ) ? '' : $css_id . '_' . $key_num );

					$html .= '<div class="multi_container">' . "\n";
					$html .= '<div class="multi_number">' . ( $key_num + 1 ) . '.</div>' . "\n";
					$html .= '<div class="multi_input">' . "\n";
				}

				$html .= '<input type="text" disabled="disabled"';
				$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';
				$html .= empty( $input_id ) ? '' : ' id="text_' . $input_id . '"';

				/**
				 * Only show a placeholder and value for input field 0.
				 */
				if ( ! $key_num ) {

					if ( '' !== $holder ) {

						$html .= ' placeholder="' . esc_attr( $holder ) . '"';
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

		public function get_no_mixed_multi( $mixed, $css_class, $css_id, $start_num = 0, $max_input = 10, $show_first = 2 ) {

			return $this->get_mixed_multi( $mixed, $css_class, $css_id, $start_num, $max_input, $show_first, $is_disabled = true );
		}

		public function get_no_radio( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null ) {

			return $this->get_radio( $name, $values, $css_class, $css_id, $is_assoc, $is_disabled = true );
		}

		public function get_no_select( $name, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $selected = false, $event_names = array(), $event_args = null ) {

			return $this->get_select( $name, $values, $css_class, $css_id,
				$is_assoc, $is_disabled = true, $selected, $event_names, $event_args );
		}

		public function get_no_select_country( $name, $css_class = '', $css_id = '', $selected = false ) {

			return $this->get_select_country( $name, $css_class, $css_id, $is_disabled = true, $selected );
		}

		public function get_no_select_country_options( $name, array $opts, $css_class = '', $css_id = '' ) {

			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : false;

			return $this->get_select_country( $name, $css_class, $css_id, $is_disabled = true, $selected );
		}

		public function get_no_select_multi( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$repeat = 3, $is_disabled = true ) {

			$is_disabled = empty( $is_disabled ) ? true : $is_disabled;	// Allow for requirement comment.

			return $this->get_select_multi( $name, $values, $css_class, $css_id, $is_assoc,
				$start_num = 0, $repeat, $repeat, $is_disabled );
		}

		public function get_no_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$selected = false, $event_names = array() ) {

			return $this->get_select_none( $name, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true, $selected, $event_names );
		}

		public function get_no_select_options( $name, array $opts, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $event_names = array(), $event_args = null ) {

			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : true;

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc, $is_disabled = true, $selected, $event_names, $event_args );
		}

		public function get_no_select_time( $name, $css_class = '', $css_id = '', $selected = false, $step_mins = 15, $add_none = false ) {

			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled = true, $selected, $step_mins, $add_none );
		}

		public function get_no_select_time_none( $name, $css_class = '', $css_id = '', $selected = false, $step_mins = 15 ) {

			/**
			 * Set 'none' as the default if no default is defined.
			 */
			if ( ! empty( $name ) ) {

				if ( ! $this->in_defaults( $name ) ) {

					$this->defaults[ $name ] = 'none';
				}
			}

			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled = true, $selected, $step_mins, $add_none = true );
		}

		public function get_no_select_time_options( $name, array $opts, $css_class = '', $css_id = '', $step_mins = 15, $add_none = false ) {

			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : true;

			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled = true, $selected, $step_mins, $add_none );
		}

		public function get_no_select_time_options_none( $name, array $opts, $css_class = '', $css_id = '', $step_mins = 15 ) {

			/**
			 * Set 'none' as the default if no default is defined.
			 */
			if ( ! empty( $name ) ) {

				if ( ! $this->in_defaults( $name ) ) {

					$this->defaults[ $name ] = 'none';
				}
			}

			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : true;

			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled = true, $selected, $step_mins, $add_none = true );
		}

		public function get_no_select_timezone( $name, $css_class = '', $css_id = '', $selected = false ) {

			/**
			 * The "timezone" class is always prefixed to the $css_class value.
			 */
			return $this->get_select_timezone( $name, $css_class, $css_id, $is_disabled = true, $selected );
		}

		public function get_no_textarea( $name, $css_class = '', $css_id = '', $len = 0, $holder = '' ) {

			return $this->get_textarea( $name, $css_class, $css_id, $len, $holder, $is_disabled = true );
		}

		public function get_no_textarea_options( $name, array $opts, $css_class = '', $css_id = '', $len = 0, $holder = '' ) {

			$value = isset( $opts[ $name ] ) ? $opts[ $name ] : '';

			return $this->get_no_textarea_value( $value, $css_class, $css_id, $len, $holder );
		}

		public function get_no_textarea_value( $value = '', $css_class = '', $css_id = '', $len = 0, $holder = '' ) {

			return '<textarea disabled="disabled"' .
				( empty( $css_class ) ? '' : ' class="' . esc_attr( $css_class ) . '"' ) .
				( empty( $css_id ) ? '' : ' id="textarea_' . esc_attr( $css_id ) . '"' ) .
				( empty( $len ) ? '' : ' rows="'.( round( $len / 100 ) + 1 ) . '"' ) .
				'>' . esc_attr( $value ) . '</textarea>';
		}

		/**
		 * Automatically disabled and localized methods.
		 */
		public function get_no_input_locale( $name, $css_class = '', $css_id = '', $len = 0, $holder = '' ) {

			return $this->get_input_locale( $name, $css_class, $css_id, $len, $holder, $is_disabled = true );
		}

		public function get_no_input_image_upload_locale( $name_prefix, $holder = '' ) {

			return $this->get_input_image_upload_locale( $name_prefix, $holder, $is_disabled = true );
		}

		public function get_no_input_image_url_locale( $name_prefix, $url = '' ) {

			return $this->get_input_image_url_locale( $name_prefix, $url, $is_disabled = true );
		}

		public function get_no_input_video_url_locale( $name_prefix, $url = '' ) {

			return $this->get_input_video_url_locale( $name_prefix, $media_suffix = 'embed', $url, $is_disabled = true );
		}

		public function get_no_select_locale( $name, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $selected = false, $event_names = array(), $event_args = null ) {

			return $this->get_select_locale( $name, $values, $css_class, $css_id,
				$is_assoc, $is_disabled = true, $selected, $event_names, $event_args );
		}

		public function get_no_textarea_locale( $name, $css_class = '', $css_id = '', $len = 0, $holder = '' ) {

			return $this->get_textarea_locale( $name, $css_class, $css_id, $len, $holder, $is_disabled = true );
		}

		/**
		 * Private methods.
		 */
		private function split_name_locale( $name_prefix ) {

			$name_suffix = '';

			/**
			 * The '.*?' syntax is required to make the expression ungreedy.
			 */
			if ( preg_match( '/^(.*?)((_[0-9]+)?(#[a-zA-Z_]+)?)$/', $name_prefix, $matches ) ) {

				$name_prefix = $matches[ 1 ];
				$name_suffix = $matches[ 2 ];
			}

			return array( $name_prefix, $name_suffix );
		}

		private static function sort_select_opt_by_label( $a, $b ) {

			/**
			 * Extract the option label, without its qualifier (ie. "(default)").
			 */
			$a_label = preg_replace( '/^.*>(.*)<\/option>$/', '$1', $a, $limit = -1, $a_count );
			$b_label = preg_replace( '/^.*>(.*)<\/option>$/', '$1', $b, $limit = -1, $b_count );

			if ( $a_count && $b_count ) {	// Just in case.

				/**
				 * Option labels in square brackets (ie. "[None]") are always top-most in the select options list.
				 */
				$a_char = substr( $a_label, 0, 1 );
				$b_char = substr( $b_label, 0, 1 );

				if ( $a_char === '[' ) {

					if ( $a_char === $b_char ) {

						return strnatcmp( $a_label, $b_label );
					}

					return -1;	// $a is first.
				}

				if ( $b_char === '[' ) {

					return 1;	// $b is first.
				}

				return strnatcmp( $a_label, $b_label );	// Binary safe case-insensitive string comparison.
			}

			return 0;	// No change.
		}

		private function get_input_media_url( $name_prefix, $media_suffix = 'id', $url = '', $is_disabled = false ) {

			list( $name_prefix, $name_suffix ) = $this->split_name_locale( $name_prefix );

			$input_name_media_locale = $name_prefix . '_' . $media_suffix . $name_suffix;
			$input_name_url_locale   = $name_prefix . '_url' . $name_suffix;
			$input_media_value       = $this->get_options_locale( $input_name_media_locale );

			/**
			 * Disable the image / video URL option if we have an image ID / video embed.
			 */
			if ( ! empty( $input_media_value ) ) {

				$holder      = '';
				$is_disabled = true;

			} else {

				$holder = SucomUtil::esc_url_encode( $url );
			}

			return $this->get_input( $input_name_url_locale, $css_class = 'wide', $css_id = '', $len = 0, $holder, $is_disabled );
		}

		private function get_placeholder_sanitized( $name, $holder = '' ) {

			if ( ! empty( $name ) ) {	// Just in case.

				if ( true === $holder ) {	// Use default value.

					if ( isset( $this->defaults[ $name ] ) ) {	// Not null.

						$holder = $this->defaults[ $name ];
					}
				}

				if ( true === $holder || '' === $holder ) {

					if ( ( $pos = strpos( $name, '#' ) ) > 0 ) {

						$key_default = SucomUtil::get_key_locale( substr( $name, 0, $pos ), $this->options, 'default' );

						if ( $name !== $key_default ) {

							if ( isset( $this->options[ $key_default ] ) ) {

								$holder = $this->options[ $key_default ];

							} elseif ( true === $holder ) {

								if ( isset( $this->defaults[ $key_default ] ) ) {

									$holder = $this->defaults[ $key_default ];
								}
							}
						}
					}
				}
			}

			return is_bool( $holder ) ? '' : $holder;	// Must be numeric or string.
		}

		private function get_placeholder_attrs( $type = 'input', $holder = '', $name = '' ) {

			if ( $holder === '' ) {

				return '';
			}

			/**
			 * Do not pre-populate an empty input field for these option names.
			 */
			if ( preg_match( '/_tid$/', $name ) ) {

				$js_if_empty = '';

			} else {

				$js_if_empty = 'if ( this.value == \'\' ) this.value = \'' . esc_js( $holder ) . '\';';
			}

			$js_if_same  = 'if ( this.value == \'' . esc_js( $holder ) . '\' ) this.value = \'\';';

			$html = ' placeholder="' . esc_attr( $holder ) . '"';
			$html .= $js_if_empty ? ' onClick="' . $js_if_empty . '"' : '';
			$html .= $js_if_empty ? ' onFocus="' . $js_if_empty . '"' : '';
			$html .= $js_if_same ? ' onBlur="' . $js_if_same . '"' : '';

			if ( $type === 'input' ) {

				$html .= $js_if_same ? ' onKeyPress="if ( event.keyCode === 13 ) { ' . $js_if_same . ' }"' : '';
			}

			$html .= $js_if_empty ? ' onMouseEnter="' . $js_if_empty . '"' : '';
			$html .= $js_if_same ? ' onMouseLeave="' . $js_if_same . '"' : '';

			return $html;
		}

		private function get_textlen_script( $input_id ) {

			if ( empty( $input_id ) ) {	// Nothing to do.

				return '';	// Return an empty string.
			}

			$input_id   = SucomUtil::sanitize_css_id( $input_id );
			$doing_ajax = SucomUtilWP::doing_ajax();

			/**
			 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
			 */

			$html = '<script>';
			$html .= $doing_ajax ? '' : 'jQuery( document ).on( \'ready\', function(){';	// Make sure sucomTextLen() is available.
			$html .= 'jQuery( \'#' . $input_id . '\' ).focus( function(){ sucomTextLen( \'' . $input_id . '\' ); } );';
			$html .= 'jQuery( \'#' . $input_id . '\' ).keyup( function(){ sucomTextLen( \'' . $input_id . '\' ); } );';
			$html .= 'jQuery( \'#' . $input_id . '\' ).blur( function(){ sucomTextLenReset( \'' . $input_id . '\' ); } );';
			$html .= $doing_ajax ? '' : '});';
			$html .= '</script>';

			return $html;
		}

		private function get_event_load_json_script( $event_json_var, $event_args, $select_json_arr, $select_id ) {

			$html = '';

			if ( empty( $event_json_var ) || ! is_string( $event_json_var ) ) {	// Just in case.

				return $html;
			}

			/**
			 * Encode the PHP array to JSON only once per page load.
			 */
			if ( empty( $this->json_array_added[ $event_json_var ] ) ) {

				$this->json_array_added[ $event_json_var ] = true;

				/**
				 * json_encode() cannot encode an associative array - only an object or a standard numerically
				 * indexed array - and the object element order, when read by the browser, cannot be controlled.
				 * Firefox, for example, will sort an object numerically instead of maintaining the original object
				 * element order. For this reason, we must use different arrays for the array keys and their
				 * values.
				 */
				$json_array_keys   = SucomUtil::json_encode_array( array_keys( $select_json_arr ) );
				$json_array_values = SucomUtil::json_encode_array( array_values( $select_json_arr ) );

				$script_js = 'var ' . $event_json_var . '_array_keys = ' . $json_array_keys . ';' . "\n";
				$script_js .= 'var ' . $event_json_var . '_array_values = ' . $json_array_values . ';' . "\n";

				$html .= '<!-- adding ' . $event_json_var . ' array -->' . "\n";

				if ( ! empty( $event_args[ 'exp_secs' ] ) ) {

					/**
					 * Array values may be localized, so include the current locale in the cache salt string.
					 */
					$cache_salt = $event_json_var . '_locale:' . SucomUtil::get_locale( 'current' );

					/**
					 * Returns false on error.
					 */
					$script_url = $this->p->cache->get_data_url( $cache_salt, $script_js, $event_args[ 'exp_secs' ], $pre_ext = '.js' );

					if ( ! empty( $script_url ) ) {

						$html .= '<script src="' . $script_url . '" async></script>' . "\n";

					} else {

						/**
						 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
						 */
						$html .= '<script>' . "\n" . $script_js . '</script>' . "\n";
					}

				} else {

					/**
					 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
					 */
					$html .= '<script>' . "\n" . $script_js . '</script>' . "\n";
				}

			} else {

				$html .= '<!-- ' . $event_json_var . ' array already added -->' . "\n";
			}

			/**
			 * The 'mouseenter' event is required for Firefox to render the option list correctly.
			 *
			 * sucomSelectLoadJson() is loaded in the footer, so test to make sure the function is available.
			 *
			 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
			 */
			$select_id_esc = esc_js( $select_id );

			$html .= '<script>' . "\n";
			$html .= 'jQuery( \'select#' . $select_id_esc . ':not( .json_loaded )\' ).on( \'mouseenter focus load_json\', function(){' . "\n";
			$html .= '	if ( \'function\' === typeof sucomSelectLoadJson ) {' . "\n";
			$html .= '		sucomSelectLoadJson( \'select#' . $select_id_esc . '\', \'' . $event_json_var . '\' );' . "\n";
			$html .= '	}' . "\n";
			$html .= '});' . "\n";
			$html .= '</script>' . "\n";

			return $html;
		}

		private function get_show_hide_trigger_script() {

			if ( $this->show_hide_js_added ) {	// Only add the event script once.

				return '';
			}

			$this->show_hide_js_added = true;

			/**
			 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
			 */
			$html = <<<EOF
<script>

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
