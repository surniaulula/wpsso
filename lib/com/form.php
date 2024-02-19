<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://surniaulula.com/)
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

		private $plugin_id          = null;	// Lowercase acronyn for main plugin.
		private $ext_id             = null;	// Lowercase acronyn for main plugin or add-on.
		private $opts_name          = null;
		private $admin_l10n         = 'sucomAdminPageL10n';
		private $text_domain        = false;	// Text domain for plugin or add-on.
		private $def_text_domain    = false;	// Default text domain (fallback).
		private $show_hide_js_added = false;
		private $json_array_added   = array();

		public $options  = array();
		public $defaults = array();

		public function __construct( &$plugin, $opts_name, &$opts, &$defs, $ext_id = '' ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();

				$this->p->debug->log( 'form options name is ' . $opts_name );
			}

			$this->plugin_id = $this->p->id;
			$this->opts_name =& $opts_name;
			$this->options   =& $opts;
			$this->defaults  =& $defs;
			$this->ext_id    = empty( $ext_id ) ? $this->plugin_id : $ext_id;	// Lowercase acronyn for plugin or add-on.

			$this->set_admin_l10n();
			$this->set_text_domain( $this->ext_id );
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

		/*
		 * $ext_id is the lowercase acronyn for the plugin or add-on.
		 */
		public function get_ext_id() {

			return $this->ext_id;
		}

		public function get_text_domain() {

			return $this->text_domain;
		}

		public function get_default_text_domain() {

			return $this->def_text_domain;
		}

		public function set_admin_l10n() {

			$this->admin_l10n = $this->get_plugin_admin_l10n();
		}

		/*
		 * $ext_id is the lowercase acronyn for the plugin or add-on.
		 */
		public function set_text_domain( $ext_id ) {

			$this->text_domain = $this->get_plugin_text_domain( $ext_id );
		}

		/*
		 * Get the text domain for the main plugin.
		 *
		 * $plugin_id is the lowercase acronyn for the main plugin.
		 */
		public function set_default_text_domain( $plugin_id ) {

			$this->def_text_domain = $this->get_plugin_text_domain( $plugin_id );
		}

		/*
		 * $ext_id is the lowercase acronyn for the plugin or add-on.
		 */
		public function get_plugin_text_domain( $ext_id ) {

			if ( isset( $this->p->cf[ 'plugin' ][ $ext_id ][ 'text_domain' ] ) ) {	// Return the main plugin or add-on text domain.

				return $this->p->cf[ 'plugin' ][ $ext_id ][ 'text_domain' ];

			} elseif ( isset( $this->p->cf[ 'plugin' ][ $this->plugin_id ][ 'text_domain' ] ) ) {	// Fallback to the main plugin text domain.

				return $this->p->cf[ 'plugin' ][ $this->plugin_id ][ 'text_domain' ];
			}

			return $this->def_text_domain;	// Return false or the (previously set) main plugin text domain.
		}

		public function get_plugin_admin_l10n() {

			if ( isset( $this->p->cf[ 'plugin' ][ $this->plugin_id ][ 'admin_l10n' ] ) ) {

				return $this->p->cf[ 'plugin' ][ $this->plugin_id ][ 'admin_l10n' ];
			}

			return $this->admin_l10n;
		}

		public function get_option_value_transl( $value, $context = 'option value' ) {

			if ( $this->text_domain ) {	// Just in case.

				$value_transl = _x( $value, $context, $this->text_domain );	// Use text domain of main plugin or add-on.

				if ( $value === $value_transl ) {	// No translation.

					if ( $this->text_domain !== $this->def_text_domain ) {	// Fallback to default text domain of main plugin.

						$value_transl = _x( $value, $context, $this->def_text_domain );
					}
				}

				return $value_transl;

			} elseif ( $this->def_text_domain ) {	// Fallback to default text domain of main plugin.

				return _x( $value, $context, $this->def_text_domain );
			}

			return $value;
		}

		public function get_tr_hide( $in_view, $opt_keys = array() ) {

			$css_class = $this->get_css_class_hide( $in_view, $opt_keys );

			return empty( $css_class ) ? '' : '<tr class="' . $css_class . '">';
		}

		public function get_tr_hide_img_dim( $in_view, $opt_name ) {

			$css_class = $this->get_css_class_hide_img_dim( $in_view, $opt_name );

			return empty( $css_class ) ? '' : '<tr class="' . $css_class . '">';
		}

		public function get_tr_hide_prefix( $in_view, $opt_name_prefix ) {

			$css_class = $this->get_css_class_hide_prefix( $in_view, $opt_name_prefix );

			return empty( $css_class ) ? '' : '<tr class="' . $css_class . '">';
		}

		public function get_tr_hide_vid_dim( $in_view, $opt_name ) {

			$css_class = $this->get_css_class_hide_vid_dim( $in_view, $opt_name );

			return empty( $css_class ) ? '' : '<tr class="' . $css_class . '">';
		}

		public function get_tr_on_change( $select_id, $select_values ) {

			$css_class = $this->get_css_class_on_change( $select_id, $select_values );

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

				$label .= ' <span class="option_locale">[' . SucomUtilWP::get_locale() . ']</span>';
			}

			$html = '<th';
			$html .= empty( $atts[ 'th_colspan' ] ) ? '' : ' colspan="' . $atts[ 'th_colspan' ] . '"';
			$html .= empty( $atts[ 'th_rowspan' ] ) ? '' : ' rowspan="' . $atts[ 'th_rowspan' ] . '"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="th_' . $input_id . '"';	// Already sanitized.
			$html .= '>' . $label . $tooltip_text . '</th>';

			return $html;
		}

		public function get_css_class_hide_img_dim( $in_view, $opt_name ) {

			foreach ( array( 'width', 'height', 'crop', 'crop_x', 'crop_y' ) as $opt_name_suffix ) {

				$opt_keys[] = $opt_name . '_' . $opt_name_suffix;
			}

			return $this->get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide_prefix( $in_view, $opt_name_prefix ) {

			$opt_keys = SucomUtil::get_opts_begin( $this->options, $opt_name_prefix );

			return $this->get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide_vid_dim( $in_view, $opt_name ) {

			foreach ( array( 'width', 'height' ) as $opt_name_suffix ) {

				$opt_keys[] = $opt_name . '_' . $opt_name_suffix;
			}

			return $this->get_css_class_hide( $in_view, $opt_keys );
		}

		public function get_css_class_hide( $in_view, $opt_keys = array() ) {

			$css_class = 'hide_in_' . $in_view;

			if ( empty( $opt_keys ) ) {

				return $css_class;

			} elseif ( ! is_array( $opt_keys ) ) {

				$opt_keys = array( $opt_keys );

			} elseif ( SucomUtil::is_assoc( $opt_keys ) ) {

				$opt_keys = array_keys( $opt_keys );
			}

			$checked_keys = array();

			foreach ( $opt_keys as $opt_key ) {

				$opt_key = preg_replace( '/[#:].*$/', '', $opt_key );	// Just in case.

				if ( empty( $opt_key ) || ! empty( $checked_keys[ $opt_key ] ) ) {

					continue;
				}

				$checked_keys[ $opt_key ] = true;

				/*
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

		public function get_css_class_on_change( $select_id, $select_values ) {

			if ( ! is_array( $select_values ) ) {

				$select_values = array( $select_values );
			}

			$select_id = SucomUtil::sanitize_css_id( $select_id );

			$css_class = 'hide_' . $select_id;

			foreach ( $select_values as $select_value ) {

				$select_value = SucomUtil::sanitize_css_id( $select_value );

				$css_class .= ' hide_' . $select_id . '_' . $select_value;
			}

			return $css_class;
		}

		public function get_md_form_rows( array $table_rows, array $form_rows, array $head = array(), array $mod = array() ) {

			foreach ( $form_rows as $key => $val ) {

				$table_rows[ $key ] = '';

				if ( empty( $val ) ) {	// Placeholder.

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

				/*
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

					$labels = empty( $val[ 'label' ] ) ? array( '' ) : $val[ 'label' ];	// Add at least one empty label.
					$labels = is_array( $labels ) ? $labels : array( $labels );

					foreach ( $labels as $th_num => $th_label ) {

						$table_rows[ $key ] .= $tr_html . $this->get_th_html( $th_label,
							( empty( $val[ 'th_class' ] ) ? '' : $val[ 'th_class' ] ),
							( empty( $val[ 'tooltip' ] ) ? '' : $val[ 'tooltip' ] ) ) . "\n";
					}

					$contents = empty( $val[ 'content' ] ) ? array() : $val[ 'content' ];	// Skip if no content.
					$contents = is_array( $contents ) ? $contents : array( $contents );

					foreach ( $contents as $td_num => $td_content ) {

						$table_rows[ $key ] .= '<td' . $col_span . $td_class . '>';
						$table_rows[ $key ] .= $td_content;
						$table_rows[ $key ] .= '</td>' . "\n";
					}
				}
			}

			return $table_rows;
		}

		/*
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

		/*
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

			$input_class    = $css_class . ( $this->get_options( $name . ':disabled' ) ? ' disabled' : '' );
			$input_class    = SucomUtil::sanitize_css_class( $input_class );
			$input_id       = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );
			$default_status = $this->in_defaults( $name ) && ! empty( $this->defaults[ $name ] ) ? 'checked' : 'unchecked';
			$title_transl   = sprintf( $this->get_option_value_transl( 'default is %s' ), $this->get_option_value_transl( $default_status ) );

			$html = '<input type="checkbox"';
			$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" value="1"';
			$html .= $is_disabled ? ' disabled="disabled"' : '';
			$html .= empty( $group ) ? '' : ' data-group="' . esc_attr( $group ) . '"';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="checkbox_' . $input_id . '"';	// Already sanitized.
			$html .= ' title="' . $title_transl . '"';
			$html .= ' ' . $input_checked . '/>';
			$html .= $is_disabled ? '' : $this->get_hidden( 'is_checkbox_' . $name, 1 );

			return $html;
		}

		/*
		 * Creates a vertical list (by default) of checkboxes.
		 *
		 * The $name_prefix is combined with the $values array names to create the checbox option name.
		 */
		public function get_checklist( $name_prefix, $values = array(), $css_class = 'input_vertical_list', $css_id = '', $is_assoc = null, $is_disabled = false,
			$event_names = array() ) {

			if ( empty( $name_prefix ) || ! is_array( $values ) ) {

				return;
			}

			if ( null === $is_assoc ) {

				$is_assoc = SucomUtil::is_assoc( $values );
			}

			if ( is_string( $event_names ) ) {

				$event_names = array( $event_names );

			} elseif ( ! is_array( $event_names ) ) {	// Ignore true, false, null, etc.

				$event_names = array();
			}

			unset( $values[ 'none' ] );	// Just in case - remove 'none' value for select arrays.

			$doing_ajax      = SucomUtilWP::doing_ajax();
			$container_class = SucomUtil::sanitize_css_class( $css_class );
			$container_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name_prefix : $css_id );

			/*
			 * Use the "input_vertical_list" class to align the checbox input vertically.
			 */
			$html = '<div class="' . $container_class . '" id="checklist_' . $container_id . '">' . "\n";

			foreach ( $values as $name_suffix => $label ) {

				if ( is_array( $label ) ) {	// Just in case.

					$label = implode( $glue = ', ', $label );
				}

				/*
				 * If the array is not associative (so a regular numbered array), then the label / description is
				 * used as the saved value.
				 */
				if ( $is_assoc ) {

					$input_name = $name_prefix . '_' . $name_suffix;

				} else {

					$input_name = $name_prefix . '_' . $label;
				}

				$input_name = SucomUtil::sanitize_input_name( $input_name );

				if ( $this->in_options( $input_name ) ) {

					$input_checked = checked( $this->options[ $input_name ], 1, false );

				} elseif ( $this->in_defaults( $input_name ) ) {	// Returns true or false.

					$input_checked = checked( $this->defaults[ $input_name ], 1, false );

				} else {

					$input_checked = '';
				}

				$input_class    = $this->get_options( $input_name . ':disabled' ) ? 'disabled' : '';
				$input_class    = SucomUtil::sanitize_css_class( $input_class );
				$input_id       = SucomUtil::sanitize_css_id( $input_name );
				$default_status = $this->in_defaults( $input_name ) && ! empty( $this->defaults[ $input_name ] ) ? 'checked' : 'unchecked';
				$title_transl   = sprintf( $this->get_option_value_transl( 'default is %s' ), $this->get_option_value_transl( $default_status ) );
				$label_transl   = $this->get_option_value_transl( $label );

				$html .= $is_disabled ? '' : $this->get_hidden( 'is_checkbox_' . $input_name, 1 );
				$html .= '<span><input type="checkbox"';
				$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '" value="1"';
				$html .= $is_disabled ? ' disabled="disabled"' : '';
				$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
				$html .= $input_id ? ' id="checkbox_' . $input_id . '"' : '';	// Already sanitized.
				$html .= ' title="' . $title_transl . '"';
				$html .= ' ' . $input_checked . '/>';
				$html .= '&nbsp;&nbsp;' . $label_transl . '&nbsp;&nbsp;</span>' . "\n";

				foreach ( $event_names as $event_num => $event_name ) {

					$html .= '<!-- event name: ' . $event_name . ' -->' . "\n";

					switch ( $event_name ) {

						case 'on_change_unhide_rows':

							$def_hide_class = 'hide_' . esc_js( $input_name );
							$def_show_class = 'hide_' . esc_js( $input_name . '_' . ( $input_checked ? 1 : 0 ) );

							$html .= '<script>';
							$html .= 'jQuery( \'#checkbox_' . $input_id . '\' ).on( \'change\', function(){';
							$html .= 'value = this.checked ? 1 : 0;';
							$html .= 'sucomChangeHideUnhideRows( \'' . $def_hide_class . '\', \'hide_' . esc_js( $input_name ) . '_\' + value );';
							$html .= '});';

							if ( $doing_ajax ) {

								$html .= 'sucomChangeHideUnhideRows( \'' . $def_hide_class . '\', \'' . $def_show_class . '\' );';

							} else {

								$html .= 'jQuery( window ).on( \'load\', function(){';
								$html .= 'sucomChangeHideUnhideRows( \'' . $def_hide_class . '\', \'' . $def_show_class . '\' );';
								$html .= '});';
							}

							$html .= '</script>' . "\n";

							break;
					}
				}
			}

			$html .= '</div>' . "\n";

			return $html;
		}


		public function get_checklist_countries( $name_prefix, $css_class = 'input_vertical_list', $css_id = '', $is_disabled = false ) {

			$values = SucomUtil::get_alpha2_countries();

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc = true, $is_disabled );
		}

		public function get_checklist_post_types( $name_prefix, $css_class = 'input_vertical_list', $css_id = '', $is_disabled = false ) {

			$label_prefix = $this->get_option_value_transl( 'Post Type' );

			$values = SucomUtilWP::get_post_type_labels( $val_prefix = '', $label_prefix );

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc = true, $is_disabled );
		}

		public function get_checklist_post_tax_user( $name_prefix, $css_class = 'input_vertical_list', $css_id = '', $is_disabled = false ) {

			$values = $this->get_checklist_post_tax_user_values();

			return $this->get_checklist( $name_prefix, $values, $css_class, $css_id, $is_assoc = true, $is_disabled );
		}

		public function get_columns_post_tax_user( $name_prefix = 'plugin', $col_headers = array(), $table_class = 'plugin_list_table_cols', $is_disabled = false ) {

			$list_cols = '<table class="' . $table_class . '">' . "\n";
			$list_cols .= '<tr>';

			foreach ( $col_headers as $col_key => $col_header ) {

				$list_cols .= '<th>' . $col_header . '</th>';
			}

			$list_cols .= '<td class="underline"></td>';
			$list_cols .= '</tr>' . "\n";

			$values = $this->get_checklist_post_tax_user_values();

			foreach ( $values as $name_suffix => $label_transl ) {

				$list_cols .= '<tr>';

				foreach ( $col_headers as $col_key => $col_header ) {

					$opt_key = $name_prefix . '_' . $col_key . '_col_' . $name_suffix;

					if ( $this->in_defaults( $opt_key ) ) {	// Just in case.

						$list_cols .= '<td class="checkbox' . ( $is_disabled ? ' blank' : '' ) . '">' .
							$this->get_checkbox( $opt_key, $css_class = '', $css_id = '', $is_disabled ) . '</td>';

					} else {

						$list_cols .= '<td class="checkbox"></td>';
					}
				}

				$list_cols .= '<td' . ( $is_disabled ? ' class="blank"' : '' ) . '><p>' . $label_transl . '</p></td>';
				$list_cols .= '</tr>' . "\n";
			}

			$list_cols .= '</table>' . "\n";

			return $list_cols;
		}

		private function get_checklist_post_tax_user_values() {

			$label_prefix = $this->get_option_value_transl( 'Post Type' );

			$values = SucomUtilWP::get_post_type_labels( $val_prefix = '', $label_prefix );

			$label_prefix = $this->get_option_value_transl( 'Taxonomy' );

			$values += SucomUtilWP::get_taxonomy_labels( $val_prefix = 'tax_', $label_prefix );

			$values[ 'user_page' ] = $this->get_option_value_transl( 'User Profiles' );

			asort( $values );	// Sort by label.

			return $values;
		}

		public function get_amount_currency( $amount_name, $currency_name, $css_class = 'price', $css_id = '', $max_len = 0, $holder = '' ) {

			$currencies = SucomUtil::get_currencies_abbrev();

			return $this->get_input( $amount_name, $css_class, $css_id, $max_len, $holder ) . ' ' .
				$this->get_select( $currency_name, $currencies, $css_class = 'currency', $css_id = '',
					$is_assoc = true, $is_disabled = false, $selected = false, $event_names = array( 'on_focus_load_json' ),
						$event_args = array( 'json_var' => 'currencies' ) );
		}

		public function get_date_time_tz( $name_prefix, $is_disabled = false, $step_mins = 15, $add_none = true ) {

			$selected = false;

			$html = $this->get_input_date( $name_prefix . '_date', $css_class = '', $css_id = '', $min_date = '', $max_date = '', $is_disabled ) . ' ';
			$html .= $this->get_option_value_transl( 'at' ) . ' ';
			$html .= $this->get_select_time( $name_prefix . '_time', $css_class = '', $css_id = '', $is_disabled, $selected, $step_mins, $add_none ) . ' ';
			$html .= $this->get_option_value_transl( 'tz' ) . ' ';
			$html .= $this->get_select_timezone( $name_prefix . '_timezone', $css_class = '', $css_id = '', $is_disabled, $selected );

			return $html;
		}

		/*
		 * Text input field.
		 */
		public function get_input( $name, $css_class = '', $css_id = '', $len = 0, $holder = '', $is_disabled = false, $tabidx = null, $el_attr = '' ) {

			if ( empty( $name ) ) {

				return;	// Just in case.
			}

			if ( $is_disabled ) {

				return $this->get_no_input( $name, $css_class, $css_id, $holder );
			}

			$html        = '';
			$holder      = $this->get_placeholder_sanitized( $name, $holder );
			$value       = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$input_class = $css_class . ( $this->get_options( $name . ':disabled' ) ? ' disabled' : '' );
			$input_class = SucomUtil::sanitize_css_class( $input_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );

			if ( ! is_array( $len ) ) {	// A non-array value defaults to a max length.

				$len = empty( $len ) ? array() : array( 'max' => $len );
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
			$html .= empty( $len ) ? '' : '<div id="text_' . $input_id . '-text-len-wrapper"></div>' . "\n";

			if ( ! empty( $len ) ) {

				$html .= $this->get_textlen_script( 'text_' . $input_id );
			}

			return $html;
		}

		public function get_input_dep( $name, $css_class = '', $css_id = '', $len = 0, $holder = '', $is_disabled = false, $dep_id = '' ) {

			$input_id = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );

			$html = $this->get_input( $name, $css_class, $input_id, $len, $holder, $is_disabled );

			if ( $dep_id ) {	// Just in case.

				$html .= $this->get_placeholder_dep_script( 'input#text_' . $input_id, 'input#text_' . $dep_id );
			}

			return $html;
		}

		public function get_input_color( $name = '', $css_class = '', $css_id = '', $is_disabled = false ) {

			$input_class = 'colorpicker ' . $css_class . ( $this->get_options( $name . ':disabled' ) ? ' disabled' : '' );
			$input_class = SucomUtil::sanitize_css_class( $input_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );

			if ( empty( $name ) ) {

				$is_disabled = true;
				$input_value = '';

			} else {

				$input_value = $this->in_options( $name ) ? $this->options[ $name ] : '';
			}

			$html = '<input type="text"';
			$html .= $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
			$html .= $input_id ? ' id="text_' . $input_id . '"' : '';	// Already sanitized.
			$html .= ' placeholder="#000000" value="' . esc_attr( $input_value ) . '"';
			$html .= ' data-default-color="' . esc_attr( $input_value ) . '"';
			$html .= '/>';

			return $html;
		}

		public function get_input_date( $name = '', $css_class = '', $css_id = '', $min_date = '', $max_date = '', $is_disabled = false ) {

			$input_class = 'datepicker ' . $css_class . ( $this->get_options( $name . ':disabled' ) ? ' disabled' : '' );
			$input_class = SucomUtil::sanitize_css_class( $input_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );

			if ( empty( $name ) ) {

				$is_disabled = true;
				$input_value = '';

			} else {

				$input_value = $this->in_options( $name ) ? $this->options[ $name ] : '';
			}

			$html = '<input type="text"';
			$html .= $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
			$html .= $input_id ? ' id="text_' . $input_id . '"' : '';	// Already sanitized.
			$html .= $min_date ? ' min="' . esc_attr( $min_date ) . '"' : '';
			$html .= $max_date ? ' max="' . esc_attr( $max_date ) . '"' : '';
			$html .= ' placeholder="yyyy-mm-dd" value="' . esc_attr( $input_value ) . '" />';

			return $html;
		}

		public function get_input_time_dhms( $name_prefix ) {

			static $days_sep  = null;
			static $hours_sep = null;
			static $mins_sep  = null;
			static $secs_sep  = null;

			if ( null === $days_sep ) {	// Translate only once.

				$days_sep  = ' ' . _x( 'days', 'option comment', 'wpsso' ) . ', ';
				$hours_sep = ' ' . _x( 'hours', 'option comment', 'wpsso' ) . ', ';
				$mins_sep  = ' ' . _x( 'mins', 'option comment', 'wpsso' ) . ', ';
				$secs_sep  = ' ' . _x( 'secs', 'option comment', 'wpsso' );
			}

			return $this->get_input( $name_prefix . '_days', $css_class = 'xshort', $css_id = '', $max_len = 0, $holder = 0 ) . $days_sep .
				$this->get_input( $name_prefix . '_hours', $css_class = 'xshort', $css_id = '', $max_len = 0, $holder = 0 ) . $hours_sep .
				$this->get_input( $name_prefix . '_mins', $css_class = 'xshort', $css_id = '', $max_len = 0, $holder = 0 ) . $mins_sep .
				$this->get_input( $name_prefix . '_secs', $css_class = 'xshort', $css_id = '', $max_len = 0, $holder = 0 ) . $secs_sep;
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

				} else $html .= $this->get_select( $name . '_' . $key, $values, $css_class, $css_id, $is_assoc, $is_disabled );
			}

			return $html;
		}

		public function get_input_image_dimensions( $name_prefix, $is_disabled = false ) {

			$html = $this->get_input( $name_prefix . '_width', $css_class = 'size width', $css_id = '', $len = 0, $holder = '', $is_disabled );
			$html .= 'x' . "\n";
			$html .= $this->get_input( $name_prefix . '_height', $css_class = 'size height', $css_id = '', $len = 0, $holder = '', $is_disabled ) . 'px' . ' ';
			$html .= $this->get_checkbox( $name_prefix . '_crop', '', '', $is_disabled ) . ' ';
			$html .= _x( 'crop', 'option comment', $this->text_domain ) . ' ';
			$html .= '<div class="image_crop_area">' . _x( 'from', 'option comment', $this->text_domain ) . "\n";
			$html .= $this->get_input_image_crop_area( $name_prefix, $add_none = false, $is_disabled );
			$html .= '</div>';

			return $html;
		}

		public function get_input_image_upload( $name_prefix, $holder = '', $is_disabled = false, $input_name_id_attr = '' ) {

			// translators: Please ignore - translation uses a different text domain.
			$img_libs     = array( 'wp' => __( 'Media Library' ) );
			$selected_lib = false;

			list( $name_prefix, $name_suffix ) = $this->split_name_locale( $name_prefix );

			$input_name_id_locale  = $name_prefix . '_id' . $name_suffix;
			$input_name_lib_locale = $name_prefix . '_id_lib' . $name_suffix;
			$input_name_url_locale = $name_prefix . '_url' . $name_suffix;

			$def_id_value  = $this->get_defaults_locale( $input_name_id_locale );
			$def_lib_value = $this->get_defaults_locale( $input_name_lib_locale );

			$holder = $holder && $def_id_value ? $def_id_value : $holder;

			$img_id_value  = $this->get_options_locale( $input_name_id_locale );
			$img_lib_value = $this->get_options_locale( $input_name_lib_locale, $def_lib_value );
			$img_url_value = $this->get_options_locale( $input_name_url_locale );

			$id_disabled_css_class = $this->get_options( $input_name_id_locale . ':disabled' ) ? ' disabled' : '';
			$upload_css_class      = 'sucom_image_upload_button button' . $id_disabled_css_class;
			$img_id_css_class      = 'sucom_image_upload_id' . $id_disabled_css_class;
			$img_lib_css_class     = 'sucom_image_upload_lib' . $id_disabled_css_class;

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

				if ( $img_id_value ) {

					$upload_button_data[ 'wp-img-id' ] = $img_id_value;

				} elseif ( $holder ) {

					$upload_button_data[ 'wp-img-id' ] = $holder;
				}
			}

			$img_libs_count   = count( $img_libs );
			$upload_disabled  = function_exists( 'wp_enqueue_media' ) ? $is_disabled : true;	// Just in case.
			$img_id_disabled  = $is_disabled;
			$img_lib_disabled = $img_libs_count > 1 ? $is_disabled : true;

			/*
			 * Prevent conflicts by removing the image URL if we have an image ID.
			 *
			 * Disable the image ID option if we have an image URL.
			 */
			if ( ! empty( $img_id_value ) ) {

				unset( $this->options[ $input_name_url_locale ] );
				unset( $this->options[ $input_name_url_locale . ':disabled' ] );
				unset( $this->options[ $input_name_url_locale . ':width' ] );
				unset( $this->options[ $input_name_url_locale . ':height' ] );

			} elseif ( ! empty( $img_url_value ) ) {

				unset( $this->options[ $input_name_id_locale ] );
				unset( $this->options[ $input_name_lib_locale ] );

				$holder          = '';	// Just in case.
				$upload_disabled = true;
				$img_id_disabled = true;
			}

			if ( ! empty( $img_libs[ 'wp' ] ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$upload_label  = __( 'Select Image' );
				$upload_button = $this->get_button( $upload_label, $upload_css_class, $upload_css_id,
					$url = '', $newtab = false, $upload_disabled, $upload_button_data );

				if ( 1 === $img_libs_count ) {

					$img_lib_css_class .= ' hidden';
				}
			}

			$select_lib = $this->get_select( $input_name_lib_locale, $img_libs, $img_lib_css_class, $img_lib_css_id,
				$is_assoc = true, $img_lib_disabled, $selected_lib, $event_names = array(), $event_args = array(),
					$input_name_lib_attr );

			$input_id = $this->get_input( $input_name_id_locale, $img_id_css_class, $img_id_css_id,
				$len = 0, $holder, $img_id_disabled, $tabidx = null, $input_name_id_attr );

			$html = '<div class="sucom_image_upload">';
			$html .= $select_lib . ' ';
			$html .= $input_id . ' ';
			$html .= $upload_button . ' ';
			$html .= '</div>';
			$html .= '<div class="sucom_image_upload_preview" id="' . $preview_css_id . '"></div>';

			return $html;
		}

		public function get_input_image_url( $name_prefix, $url = '', $is_disabled = false ) {

			return $this->get_input_media_url( $name_prefix, $primary_suffix = 'id', $url, $is_disabled );
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

			return $this->get_input_media_url( $name_prefix, $primary_suffix = 'embed', $url, $is_disabled );
		}

		/*
		 * Radio input field.
		 */
		public function get_radio( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $is_disabled = false ) {

			if ( empty( $name ) || ! is_array( $values ) ) {

				return;
			}

			if ( null === $is_assoc ) {

				$is_assoc = SucomUtil::is_assoc( $values );
			}

			$container_class = SucomUtil::sanitize_css_class( $css_class );
			$container_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );
			$input_class     = $this->get_options( $name . ':disabled' ) ? 'disabled' : '';
			$input_class     = SucomUtil::sanitize_css_class( $input_class );

			/*
			 * Use the "input_vertical_list" class to align the radio input buttons vertically.
			 */
			$html = '<div class="' . $container_class . '" id="radio_' . $container_id . '">' . "\n";	// Already sanitized.

			foreach ( $values as $val => $label ) {

				if ( is_array( $label ) ) {	// Just in case.

					$label = implode( $glue = ', ', $label );
				}

				/*
				 * If the array is not associative (so a regular numbered array), then the label / description is
				 * used as the saved value.
				 */
				if ( ! $is_assoc ) {

					$val = $label;
				}

				$label_transl = $this->get_option_value_transl( $label );

				$html .= '<span><input type="radio"';
				$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '" value="' . esc_attr( $val ) . '"';
				$html .= $is_disabled ? ' disabled="disabled"' : '';
				$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
				$html .= $this->in_options( $name ) ? checked( $this->options[ $name ], $val, false ) : '';
				$html .= $this->in_defaults( $name ) ? ' title="default is ' . $values[ $this->defaults[ $name ] ] . '"' : '';
				$html .= '/>&nbsp;' . $label_transl . '&nbsp;&nbsp;</span>';
				$html .= "\n";
			}

			$html .= '</div>' . "\n";

			return $html;
		}

		/*
		 * Select drop-down field.
		 *
		 * $is_disabled can be false or an option value for the disabled select.
		 */
		public function get_select( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $is_disabled = false, $selected = false,
			$event_names = array(), $event_args = array(), $el_attr = '' ) {

			if ( empty( $name ) ) return '';	// Just in case.

			$filter_name = SucomUtil::sanitize_hookname( $this->plugin_id . '_form_select_' . $name );
			$values      = apply_filters( $filter_name, $values );

			if ( ! is_array( $values ) ) return '';	// Just in case.

			if ( is_string( $event_names ) ) {

				$event_names = array( $event_names );

			} elseif ( ! is_array( $event_names ) ) {	// Just in case - ignore true, false, null, etc.

				$event_names = array();
			}

			if ( is_string( $event_args ) ) {	// Backwards compatibility.

				$event_args = array( 'json_var' => $event_args );

			} elseif ( ! is_array( $event_args ) ) {	// Just in case - ignore true, false, null, etc.

				$event_args = array();
			}

			if ( 'sorted' === $is_assoc ) {
			
				$event_args[ 'is_sorted' ] = true;

				$is_assoc = null;
			}

			$event_json_var = false;

			if ( in_array( 'on_focus_load_json', $event_names ) ) {

				$event_json_var = $this->plugin_id . '_select';

				if ( ! empty( $event_args[ 'json_var' ] ) ) {

					$event_json_var .= '_' . $event_args[ 'json_var' ];
				}

				$event_json_var .= '_' . md5( serialize( $values ) );

				$event_json_var = SucomUtil::sanitize_hookname( $event_json_var );
			}

			$html           = '';
			$row_id         = empty( $css_id ) ? 'tr_' . $name : 'tr_' . $css_id;
			$input_class    = $css_class . ( $this->get_options( $name . ':disabled' ) ? ' disabled' : '' );
			$input_class    = SucomUtil::sanitize_css_class( $input_class );
			$input_id       = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );
			$in_options     = $this->in_options( $name );	// Optimize and call only once - returns true or false.
			$in_defaults    = $this->in_defaults( $name );	// Optimize and call only once - returns true or false.
			$selected_value = '';

			$select_opt_count = 0;	// Used to check for first option.
			$select_opt_added = 0;
			$select_opt_arr   = array();
			$select_json_arr  = array();
			$default_value    = '';
			$default_text     = '';

			/*
			 * Check for two-dimentional arrays and maybe use option groups.
			 */
			$values = self::maybe_transl_sort_values( $name, $values, $is_assoc, $event_args );

			foreach ( $values as $optgroup_transl => $group_array ) {

				if ( is_array( $group_array ) ) {	// Two dimensional array.

					if ( $event_json_var ) {
	
						if ( empty( $this->json_array_added[ $event_json_var ] ) ) {
	
							$select_json_arr[ $optgroup_transl . ':optgroup-begin' ] = $optgroup_transl;
						}

					} else $select_opt_arr[] = '<optgroup label="' . esc_attr( $optgroup_transl ) . '">';
	
					$group_values = $group_array;
	
				} else $group_values = array( $optgroup_transl => $group_array );

				foreach ( $group_values as $option_value => $label_transl ) {
	
					$select_opt_count++;	// Used to check for first option.

					if ( is_array( $label_transl ) ) {     // Just in case.
					
						$label_transl = implode( $glue = ', ', $label_transl );
					}
	
					/*
					 * Save the option value and translated label for the json array before adding "(default)" suffix.
					 */
					if ( $event_json_var ) {
	
						if ( empty( $this->json_array_added[ $event_json_var ] ) ) {
	
							$select_json_arr[ $option_value ] = $label_transl;
						}
					}
	
					/*
					 * Save the default value and its text so we can add them (as jquery data) to the select.
					 */
					if ( $in_defaults && $option_value === (string) $this->defaults[ $name ] ) {
	
						$default_value = $option_value;
						$default_text  = $this->get_option_value_transl( '(default)' );
						$label_transl  .= ' ' . $default_text;
					}
	
					/*
					 * Maybe get a selected="selected" string for this option.
					 */
					if ( ! is_bool( $selected ) ) {
	
						$is_selected_html = selected( $selected, $option_value, false );
	
					} elseif ( $in_options ) {
	
						$is_selected_html = selected( $this->options[ $name ], $option_value, false );
	
					} elseif ( $in_defaults ) {
	
						$is_selected_html = selected( $this->defaults[ $name ], $option_value, false );
	
					} else $is_selected_html = '';
	
					if ( $is_selected_html || $select_opt_count === 1 ) {
	
						$selected_value = $option_value;
					}
	
					/*
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
				
				if ( is_array( $group_array ) ) {

					if ( $event_json_var ) {
	
						if ( empty( $this->json_array_added[ $event_json_var ] ) ) {
	
							$select_json_arr[ $optgroup_transl . ':optgroup-end' ] = $optgroup_transl;
						}

					} else $select_opt_arr[] = '</optgroup>';
				}
			}
	
			$html .= "\n";
			$html .= '<select ';
			$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= $is_disabled ? ' disabled="disabled"' : '';
			$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
			$html .= $input_id ? ' id="select_' . $input_id . '"' : '';	// Already sanitized.
			$html .= $default_value ? ' data-default-value="' . esc_attr( $default_value ) . '"' : '';
			$html .= $default_text ? ' data-default-text="' . esc_attr( $default_text ) . '"' : '';
			$html .= $el_attr ? ' ' . trim( $el_attr ) : '';
			$html .= '>' . "\n";
			$html .= implode( $glue = "\n", $select_opt_arr );
			$html .= '<!-- ' . $select_opt_added . ' select options added -->' . "\n";
			$html .= '</select>' . "\n";

			foreach ( $event_names as $event_num => $event_name ) {

				$html .= '<!-- event name: ' . $event_name . ' -->' . "\n";

				switch ( $event_name ) {

					case 'on_focus_show':

						$show_id = null;

						if ( ! empty( $event_args[ 'show_id' ] ) ) {

							$show_id = $event_args[ 'show_id' ];

						} elseif ( $event_args && is_string( $event_args ) ) {	// Deprecated.

							$show_id = $event_args;
						}

						if ( $show_id ) {

							$html .= '<script>';
							$html .= 'jQuery( \'#select_' . $input_id . '\' ).on( \'focus\', function(){';
							$html .= 'jQuery( \'' . $show_id . '\' ).show();';
							$html .= '});';
							$html .= '</script>' . "\n";
						}

						break;

					case 'on_focus_load_json':

						$html .= $this->get_event_load_json_script( $event_json_var, $event_args, $select_json_arr, 'select_' . $input_id );

						break;

					case 'on_focus_get_ajax':

						break;

					case 'on_change_redirect':

						$redirect_url = add_query_arg( array( $name => '%%' . $name . '%%' ),
							SucomUtil::get_prot() . '://' . $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REQUEST_URI' ] );

						$redirect_url_encoded = SucomUtil::esc_url_encode( $redirect_url );

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

						$def_hide_class = 'hide_' . esc_js( $name );
						$def_show_class = 'hide_' . esc_js( $name . '_' . SucomUtil::sanitize_css_id( $selected_value ) );

						$html .= '<script>';
						$html .= 'jQuery( \'#select_' . $input_id . '\' ).on( \'change\', function(){';
						$html .= 'sucomChangeHideUnhideRows( \'' . $def_hide_class . '\', \'hide_' . esc_js( $name ) . '_\' + this.value );';
						$html .= '});';
						$html .= '</script>' . "\n";

						$html .= '<!-- selected value: ' . $selected_value . ' -->' . "\n";

						/*
						 * If we have an option selected, unhide those rows.
						 *
						 * Test for a non-empty string to allow for a value of 0.
						 */
						if ( '' !== $selected_value ) {

							$html .= '<script>';

							if ( 'on_show_unhide_rows' === $event_name ) {

								$html .= 'jQuery( \'tr#' . esc_js( $row_id ) . '\' ).on( \'show\', function(){';
								$html .= 'sucomChangeHideUnhideRows( \'' . $def_hide_class . '\', \'' . $def_show_class . '\' );';
								$html .= '});';

							} else {

								$doing_ajax = SucomUtilWP::doing_ajax();

								if ( $doing_ajax ) {

									$html .= 'sucomChangeHideUnhideRows( \'' . $def_hide_class . '\', \'' . $def_show_class . '\' );';

								} else {

									$html .= 'jQuery( window ).on( \'load\', function(){';
									$html .= 'sucomChangeHideUnhideRows( \'' . $def_hide_class . '\', \'' . $def_show_class . '\' );';
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

		public function get_select_country( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false ) {

			/*
			 * Sanity check for older input field values.
			 */
			if ( false === $selected ) {

				if ( empty( $this->options[ $name ] ) || ( 'none' !== $this->options[ $name ] && 2 !== strlen( $this->options[ $name ] ) ) ) {

					$selected = $this->defaults[ $name ];
				}
			}

			return $this->get_select_none( $name, SucomUtil::get_alpha2_countries(), $css_class, $css_id,
				$is_assoc = true, $is_disabled, $selected, $event_names = array( 'on_focus_load_json' ),
					$event_args = array( 'json_var' => 'countries' ));
		}

		public function get_select_education_level( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false ) {

			if ( ! class_exists( 'SucomEducationLevels' ) ) {

				require_once dirname( __FILE__ ) . '/education-levels.php';
			}

			return $this->get_select_none( $name, SucomEducationLevels::get(), $css_class, $css_id,
				$is_assoc = 'sorted', $is_disabled, $selected, $event_names = array( 'on_focus_load_json' ),
					$event_args = array( 'json_var' => 'education_levels' ) );
		}

		/*
		 * Add 'none' as the first array element. Always converts the array to associative.
		 */
		public function get_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $is_disabled = false,
			$selected = false, $event_names = array(), $event_args = array() ) {

			/*
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

			if ( empty( $is_assoc ) ) $is_assoc = true;	// Allow for 'sorted' value.

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc, $is_disabled, $selected, $event_names, $event_args );
		}

		/*
		 * The "time-hh-mm" class is always prefixed to the $css_class value.
		 *
		 * By default, the 'none' array elements is not added.
		 */
		public function get_select_time( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false, $step_mins = 15, $add_none = false ) {

			static $local_cache = array();

			if ( empty( $local_cache[ $step_mins ] ) ) {

				$local_cache[ $step_mins ] = SucomUtil::get_hours( 60 * $step_mins );
			}

			$css_class   = trim( 'time-hh-mm ' . $css_class );
			$event_names = array( 'on_focus_load_json' );
			$event_args  = array( 'json_var' => 'hour_mins_step_' . $step_mins );

			/*
			 * Set 'none' as the default if no default is defined.
			 */
			if ( $add_none ) {

				$event_args[ 'json_var' ] .= '_add_none';

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

			/*
			 * Set 'none' as the default if no default is defined.
			 */
			if ( ! empty( $name ) ) {

				if ( ! $this->in_defaults( $name ) ) {

					$this->defaults[ $name ] = 'none';
				}
			}

			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled, $selected, $step_mins, $add_none = true );
		}

		/*
		 * The "timezone" class is always prefixed to the $css_class value.
		 */
		public function get_select_timezone( $name, $css_class = '', $css_id = '', $is_disabled = false, $selected = false ) {

			$css_class = trim( 'timezone ' . $css_class );

			/*
			 * Returns an associative array of timezone strings (ie. 'Africa/Abidjan'), 'UTC', and offsets (ie. '-07:00').
			 */
			$timezones = SucomUtil::get_timezones();

			if ( ! empty( $name ) ) {

				if ( ! $this->in_defaults( $name ) ) {

					$this->defaults[ $name ] = SucomUtil::get_default_timezone();
				}
			}

			return $this->get_select( $name, $timezones, $css_class, $css_id, $is_assoc = true, $is_disabled, $selected,
				$event_names = array( 'on_focus_load_json' ), $event_args = array( 'json_var' => 'timezones' ) );
		}

		public function get_submit( $value, $css_class = 'button-primary', $css_id = '' ) {

			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$input_id    = SucomUtil::sanitize_css_id( $css_id );

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

			$input_class = $css_class . ( $this->get_options( $name . ':disabled' ) ? ' disabled' : '' );
			$input_class = SucomUtil::sanitize_css_class( $input_class );
			$input_id    = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );
			$input_rows  = '';
			$value       = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$holder      = $this->get_placeholder_sanitized( $name, $holder );

			if ( ! is_array( $len ) ) {

				$len = array( 'max' => $len );
			}

			if ( ! empty( $len[ 'rows' ] ) ) {

				$input_rows = $len[ 'rows' ];

			} elseif ( ! empty( $len[ 'max' ] ) ) {

				$input_rows = round( $len[ 'max' ] / 100 ) + 1;
			}

			$html = '<textarea ';
			$html .= $is_disabled ? ' disabled="disabled"' : ' name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
			$html .= $input_id ? ' id="textarea_' . $input_id . '"' : '';	// Already sanitized.
			$html .= $input_rows ? ' rows="' . $input_rows . '"' : '';
			$html .= empty( $len[ 'max' ] ) || $is_disabled ? '' : ' maxLength="' . esc_attr( $len[ 'max' ] ) . '"';
			$html .= empty( $len[ 'warn' ] ) || $is_disabled ? '' : ' warnLength="' . esc_attr( $len[ 'warn' ] ) . '"';
			$html .= $this->get_placeholder_attrs( $type = 'textarea', $holder ) . '>' . esc_attr( $value ) . '</textarea>' . "\n";
			$html .= empty( $len[ 'max' ] ) || $is_disabled ? '' : '<div id="textarea_' . $input_id . '-text-len-wrapper"></div>' . "\n";

			if ( ! empty( $len[ 'max' ] ) ) {

				$html .= $this->get_textlen_script( 'textarea_' . $input_id );
			}

			return $html;
		}

		public function get_textarea_dep( $name, $css_class = '', $css_id = '', $len = 0, $holder = '', $is_disabled = false, $dep_id = '' ) {

			$input_id = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name : $css_id );

			$html = $this->get_textarea( $name, $css_class, $input_id, $len, $holder, $is_disabled );

			if ( $dep_id ) {	// Just in case.

				$html .= $this->get_placeholder_dep_script( 'textarea#textarea_' . $input_id, 'textarea#textarea_' . $dep_id );
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
			$html .= ' value="' . esc_attr( wp_kses( $value, array() ) ) . '"/>';	// KSES (Kses Strips Evil Scripts).

			return $html;
		}

		/*
		 * MULTIPLE FIELDS METHODS:
		 *
		 *	get_input_multi()
		 *	get_mixed_multi()
		 *	get_select_multi()
		 *	get_textarea_multi()
		 */
		public function get_input_multi( $name, $css_class = '', $css_id = '', $show_max = 10, $show_first = 1, $is_disabled = false ) {

			if ( empty( $name ) ) {

				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $show_max ? $show_max : $show_first;
			$start_num  = 0;
			$end_num    = $show_max > 0 ? $show_max - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$display  = $one_more || $key_num < $show_first ? true : false;
				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;
				$disp_num = $key_num + 1;

				$input_name    = $name . '_' . $key_num;
				$input_class   = $css_class . ( $this->get_options( $input_name . ':disabled' ) ? ' disabled' : '' );
				$input_class   = SucomUtil::sanitize_css_class( $input_class );
				$input_id      = SucomUtil::sanitize_css_id( empty( $css_id ) ? $input_name : $css_id . '_' . $key_num );
				$input_id_prev = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num );
				$input_id_next = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num );
				$input_value   = $this->in_options( $input_name ) ? $this->options[ $input_name ] : '';

				if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {

					continue;
				}

				if ( $start_num === $key_num ) {

					$el_attr = 'onFocus="jQuery(\'div#multi_' . $input_id_next . '\').show();"';

				} else {

					$el_attr = 'onFocus="if ( jQuery(\'input#text_' . $input_id_prev . '\').val().length )' .
						' { jQuery(\'div#multi_' . $input_id_next . '\').show(); } else' .
						' if ( ! jQuery(\'input#textarea_' . $input_id . '\').val().length )' .
						' { jQuery(\'input#text_' . $input_id_prev . '\').focus(); }"';
				}

				$html .= '<div';
				$html .= ' class="multi_container input_multi"';
				$html .= ' id="multi_' . $input_id . '"';
				$html .= $display || '' !== $input_value ? '' : ' style="display:none;"';
				$html .= '>' . "\n";
				$html .= '<div class="multi_number">' . $disp_num . '.</div>' . "\n";
				$html .= '<div class="multi_input">' . "\n";
				$html .= '<div class="multi_input_el">' . "\n";	// Container for each input field.
				$html .= '<input type="text"';
				$html .= $is_disabled ? '' : ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"';
				$html .= $is_disabled ? ' disabled="disabled"' : '';
				$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
				$html .= $input_id ? ' id="text_' . $input_id . '"' : '';	// Already sanitized.
				$html .= ' value="' . esc_attr( $input_value ) . '"';
				$html .= ' ' . $el_attr . '/>' . "\n";
				$html .= '</div><!-- .multi_input_el -->' . "\n";
				$html .= '</div><!-- .multi_input -->' . "\n";
				$html .= '</div><!-- .multi_container.input_multi -->' . "\n";

				$one_more = empty( $input_value ) && ! is_numeric( $input_value ) ? false : true;	// Allow for 0.
			}

			return $html;
		}

		public function get_mixed_multi( $mixed, $css_class, $css_id, $show_max = 5, $show_first = 1, $is_disabled = false ) {

			if ( empty( $mixed ) ) {

				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $show_max ? $show_max : $show_first;
			$start_num  = 0;
			$end_num    = $show_max > 0 ? $show_max - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$display  = $one_more || $key_num < $show_first ? true : false;
				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;

				$multi_class   = trim( 'multi_container mixed_multi ' . $css_class );
				$multi_id      = $css_id . '_' . $key_num;
				$multi_id_prev = $css_id . '_' . $prev_num;
				$multi_id_next = $css_id . '_' . $next_num;

				$el_attr = 'onFocus="jQuery(\'div#multi_' . esc_attr( $multi_id_next ) . '\').show();"';

				$html .= '<div class="' . $multi_class . '" id="multi_' . esc_attr( $multi_id ) . '"';	// .multi_container.mixed_multi.
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";
				$html .= '<div class="multi_number">' . ( $key_num + 1 ) . '.</div>' . "\n";
				$html .= '<div class="multi_input">' . "\n";

				$one_more = false;	// Return to default.

				$multi_label_num = 0;

				foreach ( $mixed as $name => $atts ) {

					$input_name      = $name . '_' . $key_num;
					$input_title     = empty( $atts[ 'input_title' ] ) ? '' : $atts[ 'input_title' ];
					$input_class     = empty( $atts[ 'input_class' ] ) ? '' : $atts[ 'input_class' ];
					$container_class = SucomUtil::sanitize_css_class( $input_class );
					$input_class     .= $this->get_options( $input_name . ':disabled' ) ? ' disabled' : '';
					$input_class     = SucomUtil::sanitize_css_class( $input_class );
					$input_id        = empty( $atts[ 'input_id' ] ) ? $input_name : $atts[ 'input_id' ] . '_' . $key_num;
					$input_id        = SucomUtil::sanitize_css_id( $input_id );
					$input_content   = empty( $atts[ 'input_content' ] ) ? '' : $atts[ 'input_content' ];
					$input_values    = empty( $atts[ 'input_values' ] ) ? array() : $atts[ 'input_values' ];
					$in_options      = $this->in_options( $input_name );	// Optimize and call only once.
					$in_defaults     = $this->in_defaults( $input_name );	// Optimize and call only once.

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

					$event_args = empty( $atts[ 'event_args' ] ) ? array() : $atts[ 'event_args' ];

					if ( isset( $atts[ 'placeholder' ] ) ) {

						$holder = $this->get_placeholder_sanitized( $input_name, $atts[ 'placeholder' ] );

					} else $holder = '';

					if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {

						continue;
					}

					$html .= '<div class="multi_input_el">' . "\n";

					/*
					 * Default paragraph display is an inline-block.
					 */
					if ( ! empty( $atts[ 'input_label' ] ) ) {

						$multi_label_num++;

						$html .= '<div class="multi_input_label ' . $container_class . ( 1 === $multi_label_num ? ' first_label' : '' ) . '">';
						$html .= $atts[ 'input_label' ] . ':';
						$html .= '</div>' . "\n";
					}

					if ( isset( $atts[ 'input_type' ] ) ) {

						switch ( $atts[ 'input_type' ] ) {

							case 'image':

								$html .= '<div tabindex="-1" ' . $el_attr . ' style="display:inline-block;">' . "\n";
								$html .= $this->get_input_image_upload( $input_name, $holder, $is_disabled, $el_attr );
								$html .= '</div>'. "\n";

								break;

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

									$radio_inputs[] = '<input type="radio"' .
										( $is_disabled ? ' disabled="disabled"' :
											' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"' .
											' class="' . $input_class . '"' .	// Already sanitized.
											' value="' . esc_attr( $input_value ) . '"' ) .
										$input_checked . '/>';
								}

								if ( ! empty( $radio_inputs ) ) {

									$html .= '<div';
									$html .= ' class="' . $container_class . '"';	// Already sanitized.
									$html .= ' id="' . $input_id . '"';		// Already sanitized.
									$html .= ' ' . $el_attr . '>';
									$html .= vsprintf( $atts[ 'input_content' ], $radio_inputs );
									$html .= '</div>' . "\n";
								}

								break;

							case 'select':

								$select_options = empty( $atts[ 'select_options' ] ) ||
									! is_array( $atts[ 'select_options' ] ) ?
										array() : $atts[ 'select_options' ];

								$select_selected = empty( $atts[ 'select_selected' ] ) ? null : $atts[ 'select_selected' ];
								$select_default  = empty( $atts[ 'select_default' ] ) ? null : $atts[ 'select_default' ];

								$event_json_var = false;

								if ( in_array( 'on_focus_load_json', $event_names ) ) {

									$event_json_var = $this->plugin_id . '_select';

									if ( $event_args && is_string( $event_args ) ) {

										$event_json_var .= '_' . $event_args;

									} elseif ( ! empty( $event_args[ 'json_var' ] ) ) {

										$event_json_var = '_' . $event_args[ 'json_var' ];
									}

									$event_json_var .= '_' . md5( serialize( $select_options ) );

									$event_json_var = SucomUtil::sanitize_hookname( $event_json_var );
								}

								$select_opt_count = 0;	// Used to check for first option.
								$select_opt_added = 0;
								$select_opt_arr   = array();
								$select_json_arr  = array();
								$default_value    = '';
								$default_text     = '';
								$select_options   = self::maybe_transl_sort_values( $input_name,
									$select_options, $is_assoc = null, $event_args );

								foreach ( $select_options as $option_value => $label_transl ) {

									if ( is_array( $label_transl ) ) {	// Just in case.

										$label_transl = implode( $glue = ', ', $label_transl );
									}

									/*
									 * Save the option value and translated label for the JSON
									 * array before adding the "(default)" suffix.
									 */
									if ( $event_json_var ) {

										if ( empty( $this->json_array_added[ $event_json_var ] ) ) {

											$select_json_arr[ $option_value ] = $label_transl;
										}
									}

									/*
									 * Save the default value and its text so we can add them (as jquery data) to the select.
									 */
									if ( ( $in_defaults && $option_value === (string) $this->defaults[ $input_name ] ) ||
										( null !== $select_default && $option_value === $select_default ) ) {

										$default_value = $option_value;
										$default_text  = $this->get_option_value_transl( '(default)' );
										$label_transl  .= ' ' . $default_text;
									}

									if ( $select_selected !== null ) {

										$is_selected_html = selected( $select_selected, $option_value, false );

									} elseif ( $in_options ) {

										$is_selected_html = selected( $this->options[ $input_name ], $option_value, false );

									} elseif ( $select_default !== null ) {

										$is_selected_html = selected( $select_default, $option_value, false );

									} elseif ( $in_defaults ) {

										$is_selected_html = selected( $this->defaults[ $input_name ], $option_value, false );

									} else $is_selected_html = '';

									$select_opt_count++;	// Used to check for first option.

									/*
									 * For disabled selects, only include the first and/or selected option.
									 */
									if ( ( ! $is_disabled && ! $event_json_var ) || $is_selected_html || $select_opt_count === 1 ) {

										if ( ! isset( $select_opt_arr[ $option_value ] ) ) {

											$select_opt_arr[ $option_value ] = '<option value="' .
												esc_attr( $option_value ) . '"' . $is_selected_html . '>' .
												$label_transl . '</option>';

											$select_opt_added++;
										}
									}
								}

								$html .= "\n" . '<select ';
								$html .= $is_disabled ? ' disabled="disabled"' : '';
								$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
								$html .= $input_id ? ' id="select_' . $input_id . '"' : '';			// Already sanitized.
								$html .= ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"';
								$html .= ' title="' . esc_attr( $input_title ) . '"';
								$html .= ' data-default-value="' . esc_attr( $default_value ) . '"';
								$html .= ' data-default-text="' . esc_attr( $default_text ) . '"';
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

							case 'text':

								$input_value = $in_options ? $this->options[ $input_name ] : '';

								$html .= '<input type="text"';
								$html .= $is_disabled ? ' disabled="disabled"' : '';
								$html .= ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"';
								$html .= ' title="' . esc_attr( $input_title ) . '"';
								$html .= ' class="' . $input_class . '"';	// Already sanitized.
								$html .= ' id="text_' . $input_id . '"';	// Already sanitized.
								$html .= ' value="' . esc_attr( $input_value ) . '"';
								$html .= ' ' . $el_attr . '/>' . "\n";

								if ( $input_value || is_numeric( $input_value ) ) {

									$one_more = true;
								}

								break;

							case 'textarea':

								$input_value = $in_options ? $this->options[ $input_name ] : '';

								$html .= '<textarea';
								$html .= $is_disabled ? ' disabled="disabled"' : '';
								$html .= ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"';
								$html .= ' title="' . esc_attr( $input_title ) . '"';
								$html .= ' class="' . $input_class . '"';	// Already sanitized.
								$html .= ' id="textarea_' . $input_id . '"';	// Already sanitized.
								$html .= $this->get_placeholder_attrs( $type = 'textarea', $holder );
								$html .= ' ' . $el_attr . '>' . esc_attr( $input_value );
								$html .= '</textarea>' . "\n";

								if ( $input_value || is_numeric( $input_value ) ) {

									$one_more = true;
								}

								break;
						}
					}

					$html .= '</div><!-- .multi_input_el -->' . "\n";
				}

				$html .= '</div><!-- .multi_input -->' . "\n";
				$html .= '</div><!-- .multi_container.mixed_multi -->' . "\n";
			}

			return $html;
		}

		/*
		 * $is_disabled can be true, false, or a text string (ie. "WPSSO PLM required").
		 */
		public function get_select_multi( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$show_max = 5, $show_first = 1, $is_disabled = false, $event_names = array(), $event_args = array() ) {

			if ( empty( $name ) ) {

				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $show_max ? $show_max : $show_first;
			$start_num  = 0;
			$end_num    = $show_max > 0 ? $show_max - 1 : 0;

			$event_names[] = 'on_focus_show';

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$display  = $one_more || $key_num < $show_first ? true : false;
				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;
				$disp_num = $key_num + 1;

				$input_name    = $name . '_' . $key_num;
				$input_class   = empty( $css_class ) ? '' : SucomUtil::sanitize_css_class( $css_class );
				$input_id      = SucomUtil::sanitize_css_id( empty( $css_id ) ? $input_name : $css_id . '_' . $key_num );
				$input_id_prev = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num );
				$input_id_next = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num );
				$input_value   = $this->in_options( $input_name ) ? $this->options[ $input_name ] : '';

				if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {

					continue;
				}

				$event_args[ 'show_id' ] = 'div#multi_' . $input_id_next;

				$html .= '<div class="multi_container select_multi" id="multi_' . $input_id . '"';
				$html .= $display ? '' : ' style="display:none;"';
				$html .= '>' . "\n";
				$html .= '<div class="multi_number">' . $disp_num . '.</div>' . "\n";
				$html .= '<div class="multi_input">' . "\n";
				$html .= '<div class="multi_input_el">' . "\n";	// Container for each input field.

				/*
				 * $is_disabled can be true, false, or an option value for the disabled select.
				 */
				$html .= $this->get_select( $input_name, $values, $input_class, $input_id, $is_assoc,
					$is_disabled, $input_value, $event_names, $event_args );

				$html .= is_string( $is_disabled ) ? $is_disabled : '';	// Allow comment.

				$html .= '</div><!-- .multi_input_el -->' . "\n";
				$html .= '</div><!-- .multi_input -->' . "\n";
				$html .= '</div><!-- .multi_container.select_multi -->' . "\n";

				$one_more = 'none' === $input_value || ( empty( $input_value ) && ! is_numeric( $input_value ) ) ? false : true;	// Allow for 0.
			}

			return $html;
		}

		public function get_textarea_multi( $name, $css_class = '', $css_id = '', $len = 0, $show_max = 5, $show_first = 1, $is_disabled = false ) {

			if ( empty( $name ) ) {

				return;	// Just in case.
			}

			$html       = '';
			$display    = true;
			$one_more   = false;
			$show_first = $show_first > $show_max ? $show_max : $show_first;
			$start_num  = 0;
			$end_num    = $show_max > 0 ? $show_max - 1 : 0;

			foreach ( range( $start_num, $end_num, 1 ) as $key_num ) {

				$display  = $one_more || $key_num < $show_first ? true : false;
				$prev_num = $key_num > 0 ? $key_num - 1 : 0;
				$next_num = $key_num + 1;
				$disp_num = $key_num + 1;

				$input_name    = $name . '_' . $key_num;
				$input_class   = empty( $css_class ) ? '' : SucomUtil::sanitize_css_class( $css_class );
				$input_id      = SucomUtil::sanitize_css_id( empty( $css_id ) ? $input_name : $css_id . '_' . $key_num );
				$input_id_prev = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $prev_num : $css_id . '_' . $prev_num );
				$input_id_next = SucomUtil::sanitize_css_id( empty( $css_id ) ? $name . '_' . $next_num : $css_id . '_' . $next_num );
				$input_value   = $this->in_options( $input_name ) ? $this->options[ $input_name ] : '';

				if ( $is_disabled && $key_num >= $show_first && empty( $display ) ) {

					continue;
				}

				if ( $start_num === $key_num ) {

					$el_attr = 'onFocus="jQuery(\'div#multi_' . $input_id_next . '\').show();"';

				} else {

					$el_attr = 'onFocus="if ( jQuery(\'textarea#textarea_' . $input_id_prev . '\').val().length )' .
						' { jQuery(\'div#multi_' . $input_id_next . '\').show(); } else' .
						' if ( ! jQuery(\'textarea#textarea_' . $input_id . '\').val().length )' .
						' { jQuery(\'textarea#textarea_' . $input_id_prev . '\').focus(); }"';
				}

				$html .= '<div';
				$html .= ' class="multi_container textarea_multi"';
				$html .= ' id="multi_' . $input_id . '"';
				$html .= $display || '' !== $input_value ? '' : ' style="display:none;"';
				$html .= '>' . "\n";
				$html .= '<div class="multi_number">' . $disp_num . '.</div>' . "\n";
				$html .= '<div class="multi_input">' . "\n";
				$html .= '<div class="multi_input_el">' . "\n";	// Container for each input field.
				$html .= '<textarea';
				$html .= $is_disabled ? ' disabled="disabled"' : '';
				$html .= ' name="' . esc_attr( $this->opts_name . '[' . $input_name . ']' ) . '"';
				$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
				$html .= $input_id ? ' id="textarea_' . $input_id . '"' : '';	// Already sanitized.
				$html .= ' ' . $el_attr . '>' . esc_attr( $input_value );
				$html .= '</textarea>' . "\n";
				$html .= '</div><!-- .multi_input_el -->' . "\n";
				$html .= '</div><!-- .multi_input -->' . "\n";
				$html .= '</div><!-- .multi_container.textarea_multi -->' . "\n";

				$one_more = empty( $input_value ) && ! is_numeric( $input_value ) ? false : true;	// Allow for 0.
			}

			return $html;
		}

		/*
		 * AUTOMATICALLY LOCALIZED METHODS:
		 *
		 *	get_options_locale()
		 *	get_defaults_locale()
		 *	get_input_locale()
		 *	get_input_image_upload_locale()
		 *	get_input_image_url_locale()
		 *	get_input_video_url_locale()
		 *	get_select_locale()
		 *	get_textarea_locale()
		 *	get_th_html_locale()
		 */
		public function get_options_locale( $opt_key = false, $def_val = null ) {

			if ( false !== $opt_key ) {

				if ( null === $def_val ) {

					/*
					 * Returns an option value or null.
					 *
					 * Note that for non-existing keys, or empty strings, this method will return the default non-localized value.
					 */
					return SucomUtil::get_key_value( $opt_key, $this->options, $mixed = 'current' );
				}
			}

			return $this->get_options( $opt_key, $def_val );
		}

		public function get_defaults_locale( $opt_key = false ) {

			if ( false !== $opt_key ) {

				/*
				 * Returns an option value or null.
				 *
				 * Note that for non-existing keys, or empty strings, this method will return the default non-localized value.
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

			return $this->get_input_video_url( $name_prefix, $primary_suffix = 'embed', $url, $is_disabled );
		}

		public function get_select_locale( $name, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $is_disabled = false, $selected = false, $event_names = array(), $event_args = null ) {

			$name = SucomUtil::get_key_locale( $name, $this->options );

			return $this->get_select( $name, $values, $css_class, $css_id, $is_assoc, $is_disabled, $selected, $event_names, $event_args );
		}

		public function get_textarea_locale( $name, $css_class = '', $css_id = '', $len = 0, $holder = '', $is_disabled = false ) {

			$name = SucomUtil::get_key_locale( $name, $this->options );

			return $this->get_textarea( $name, $css_class, $css_id, $len, $holder, $is_disabled );
		}

		public function get_th_html_locale( $label = '', $css_class = '', $css_id = '', $atts = array() ) {

			$atts[ 'is_locale' ] = true;

			return $this->get_th_html( $label, $css_class, $css_id, $atts );
		}

		/*
		 * AUTOMATICALLY DISABLED METHODS:
		 *
		 *	get_no_td_checkbox()
		 *	get_no_checkbox()
		 *	get_no_checkbox_options()
		 *	get_no_checkbox_comment()
		 *	get_no_checklist()
		 *	get_no_checklist_post_types()
		 *	get_no_checklist_post_tax_user()
		 *	get_no_columns_post_tax_user()
		 *	get_no_date_time_tz()
		 *	get_no_input()
		 *	get_no_input_clipboard()
		 *	get_no_input_options()
		 *	get_no_input_holder()
		 *	get_no_input_date()
		 *	get_no_input_date_options()
		 *	get_no_input_time_dhms()
		 *	get_no_input_image_crop_area()
		 *	get_no_input_image_dimensions()
		 *	get_no_input_image_upload()
		 *	get_no_input_video_dimensions()
		 *	get_no_input_value()
		 *	get_no_radio()
		 *	get_no_select()
		 *	get_no_select_country()
		 *	get_no_select_country_options()
		 *	get_no_select_none()
		 *	get_no_select_options()
		 *	get_no_select_time()
		 *	get_no_select_time_none()
		 *	get_no_select_time_options()
		 *	get_no_select_time_options_none()
		 *	get_no_select_timezone()
		 *	get_no_textarea()
		 *	get_no_textarea_options()
		 *	get_no_textarea_value()
		 *
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

		public function get_no_columns_post_tax_user( $name_prefix, $css_class = 'input_vertical_list', $css_id = '' ) {

			return $this->get_columns_post_tax_user( $name_prefix, $css_class, $css_id, $is_disabled = true );
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

		public function get_no_input_clipboard( $value, $css_class = 'wide', $css_id = '' ) {

			if ( empty( $css_id ) ) {	// Make sure we have an ID string.

				$css_id = uniqid();
			}

			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$input_id    = SucomUtil::sanitize_css_id( $css_id );

			$input_text = '<input type="text"';
			$input_text .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$input_text .= empty( $input_id ) ? '' : ' id="text_' . $input_id . '"';	// Already sanitized.
			$input_text .= ' value="' . esc_attr( $value ) . '" readonly';
			$input_text .= ' onFocus="this.select();"';
			$input_text .= ' onMouseUp="return false;"/>';

			/*
			 * Add a dashicons copy-to-clipboard button to the input text field.
			 */
			if ( ! empty( $input_id ) ) {	// Just in case.

				$html = '<div class="no_input_clipboard">';
				$html .= '<div class="copy_button"><a href="" onClick="return sucomCopyById( \'text_' . $input_id . '\', \'' . $this->admin_l10n . '\' );">';
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

		public function get_no_input_time_dhms() {

			static $days_sep  = null;
			static $hours_sep = null;
			static $mins_sep  = null;
			static $secs_sep  = null;

			if ( null === $days_sep ) {	// Translate only once.

				$days_sep  = ' ' . _x( 'days', 'option comment', 'wpsso' ) . ', ';
				$hours_sep = ' ' . _x( 'hours', 'option comment', 'wpsso' ) . ', ';
				$mins_sep  = ' ' . _x( 'mins', 'option comment', 'wpsso' ) . ', ';
				$secs_sep  = ' ' . _x( 'secs', 'option comment', 'wpsso' );
			}

			return $this->get_no_input_value( $value = '0', $css_class = 'xshort' ) . $days_sep .
				$this->get_no_input_value( $value = '0', $css_class = 'xshort' ) . $hours_sep .
				$this->get_no_input_value( $value = '0', $css_class = 'xshort' ) . $mins_sep .
				$this->get_no_input_value( $value = '0', $css_class = 'xshort' ) . $secs_sep;
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

		public function get_no_input_value( $value = '', $css_class = '', $css_id = '', $holder = '', $show_max = 1 ) {

			$html        = '';
			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$holder      = $this->get_placeholder_sanitized( $name = '', $holder );
			$end_num     = $show_max > 0 ? $show_max - 1 : 0;

			foreach ( range( 0, $end_num, 1 ) as $key_num ) {

				if ( $show_max > 1 ) {

					$input_id = SucomUtil::sanitize_css_id( empty( $css_id ) ? '' : $css_id . '_' . $key_num );

					$html .= '<div class="multi_container">' . "\n";
					$html .= '<div class="multi_number">' . ( $key_num + 1 ) . '.</div>' . "\n";
					$html .= '<div class="multi_input">' . "\n";
					$html .= '<div class="multi_input_el">' . "\n";	// Container for each input field.
				}

				$html .= '<input type="text" disabled="disabled"';
				$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
				$html .= empty( $input_id ) ? '' : ' id="text_' . $input_id . '"';	// Already sanitized.

				/*
				 * Only show a placeholder and value for input field 0.
				 */
				if ( ! $key_num ) {

					if ( '' !== $holder ) {

						$html .= ' placeholder="' . esc_attr( $holder ) . '"';
					}

					$html .= ' value="' . esc_attr( $value ) . '"';
				}

				$html .= '/>' . "\n";

				if ( $show_max > 1 ) {

					$html .= '</div><!-- .multi_input_el -->' . "\n";
					$html .= '</div><!-- .multi_input -->' . "\n";
					$html .= '</div><!-- .multi_container -->' . "\n";
				}
			}

			return $html;
		}

		public function get_no_radio( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null ) {

			return $this->get_radio( $name, $values, $css_class, $css_id, $is_assoc, $is_disabled = true );
		}

		public function get_no_select( $name, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $selected = false, $event_names = array(), $event_args = array() ) {

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

		public function get_no_select_none( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null,
			$selected = false, $event_names = array() ) {

			return $this->get_select_none( $name, $values, $css_class, $css_id, $is_assoc,
				$is_disabled = true, $selected, $event_names );
		}

		public function get_no_select_options( $name, array $opts, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $event_names = array(), $event_args = array() ) {

			$selected = isset( $opts[ $name ] ) ? $opts[ $name ] : true;

			return $this->get_select( $name, $values, $css_class, $css_id,
				$is_assoc, $is_disabled = true, $selected, $event_names, $event_args );
		}

		public function get_no_select_time( $name, $css_class = '', $css_id = '', $selected = false, $step_mins = 15, $add_none = false ) {

			return $this->get_select_time( $name, $css_class, $css_id, $is_disabled = true, $selected, $step_mins, $add_none );
		}

		public function get_no_select_time_none( $name, $css_class = '', $css_id = '', $selected = false, $step_mins = 15 ) {

			/*
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

			/*
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

			/*
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

			$input_class = SucomUtil::sanitize_css_class( $css_class );
			$input_id    = SucomUtil::sanitize_css_id( $css_id );
			$input_rows  = '';

			if ( ! is_array( $len ) ) {	// A non-array value defaults to a max length.

				$len = empty( $len ) ? array() : array( 'max' => $len );
			}

			if ( ! empty( $len[ 'rows' ] ) ) {

				$input_rows = $len[ 'rows' ];

			} elseif ( ! empty( $len[ 'max' ] ) ) {

				$input_rows = round( $len[ 'max' ] / 100 ) + 1;
			}

			$html = '<textarea disabled="disabled"';
			$html .= $input_class ? ' class="' . $input_class . '"' : '';	// Already sanitized.
			$html .= $input_id ? ' id="textarea_' . $input_id . '"' : '';	// Already sanitized.
			$html .= $input_rows ? ' rows="' . $input_rows . '"' : '';
			$html .= '>' . esc_attr( $value ) . '</textarea>';

			return $html;
		}

		/*
		 * AUTOMATICALLY DISABLED AND LOCALIZED METHODS:
		 *
		 *	get_no_input_locale()
		 *	get_no_input_image_upload_locale()
		 *	get_no_input_image_url_locale()
		 *	get_no_input_video_url_locale()
		 *	get_no_select_locale()
		 *	get_no_textarea_locale()
		 */
		public function get_no_input_locale( $name, $css_class = '', $css_id = '', $holder = '' ) {

			return $this->get_input_locale( $name, $css_class, $css_id, $len = 0, $holder, $is_disabled = true );
		}

		public function get_no_input_image_upload_locale( $name_prefix, $holder = '' ) {

			return $this->get_input_image_upload_locale( $name_prefix, $holder, $is_disabled = true );
		}

		public function get_no_input_image_url_locale( $name_prefix, $url = '' ) {

			return $this->get_input_image_url_locale( $name_prefix, $url, $is_disabled = true );
		}

		public function get_no_input_video_url_locale( $name_prefix, $url = '' ) {

			return $this->get_input_video_url_locale( $name_prefix, $primary_suffix = 'embed', $url, $is_disabled = true );
		}

		public function get_no_select_locale( $name, $values = array(), $css_class = '', $css_id = '',
			$is_assoc = null, $selected = false, $event_names = array(), $event_args = array() ) {

			return $this->get_select_locale( $name, $values, $css_class, $css_id,
				$is_assoc, $is_disabled = true, $selected, $event_names, $event_args );
		}

		public function get_no_textarea_locale( $name, $css_class = '', $css_id = '', $len = 0, $holder = '' ) {

			return $this->get_textarea_locale( $name, $css_class, $css_id, $len, $holder, $is_disabled = true );
		}

		/*
		 * AUTOMATICALLY DISABLED MULTIPLE FIELDS METHODS:
		 *
		 *	get_no_input_multi()
		 *	get_no_mixed_multi()
		 *	get_no_select_multi()
		 */
		public function get_no_input_multi( $name, $css_class = '', $css_id = '', $repeat = 1 ) {

			return $this->get_input_multi( $name, $css_class, $css_id, $repeat, $repeat, $is_disabled = true );
		}

		public function get_no_mixed_multi( $mixed, $css_class, $css_id, $repeat = 1 ) {

			return $this->get_mixed_multi( $mixed, $css_class, $css_id, $repeat, $repeat, $is_disabled = true );
		}

		public function get_no_select_multi( $name, $values = array(), $css_class = '', $css_id = '', $is_assoc = null, $repeat = 1, $is_disabled = true ) {

			$is_disabled = empty( $is_disabled ) ? true : $is_disabled;	// Allow a comment string.

			return $this->get_select_multi( $name, $values, $css_class, $css_id, $is_assoc, $repeat, $repeat, $is_disabled );
		}

		/*
		 * PRIVATE METHODS:
		 *
		 *	maybe_transl_sort_values()
		 *	split_name_locale()
		 *	sort_select_labels()
		 *	get_input_media_url()
		 *	get_placeholder_sanitized()
		 *	get_placeholder_attrs()
		 *	get_placeholder_dep_script()
		 *	get_textlen_script()
		 *	get_event_load_json_script()
		 *	get_show_hide_trigger_script()
		 */
		private function maybe_transl_sort_values( $name, array $values, $is_assoc, array $event_args, $optgroup_transl = '' ) {

			$sorted = array();

			$values_assoc = null === $is_assoc ? SucomUtil::is_assoc( $values ) : $is_assoc;

			$sort_by_key = false;

			foreach ( $values as $option_value => $label ) {

				unset( $values[ $option_value ] );	// Optimize and unset as we go.

				if ( is_array( $label ) ) {	// Two dimensional array.

					$label_transl = empty( $event_args[ 'is_transl' ] ) ? $this->get_option_value_transl( $option_value ) : $option_value;

					$sorted[ $label_transl ] = self::maybe_transl_sort_values( $name, $label, null, $event_args, $label_transl );

					$sort_by_key = true;

				} else {

					$option_value = $values_assoc ? (string) $option_value : (string) $label;

					$label_transl = empty( $event_args[ 'is_transl' ] ) ? $this->get_option_value_transl( $label ) : $label;

					if ( 0 === $label ) {
	
						if ( preg_match( '/_img_max/', $name ) ) {
	
							$label_transl = trim( $label_transl . ' ' . $this->get_option_value_transl( '(no images)' ) );
	
						} elseif ( preg_match( '/_vid_max/', $name ) ) {
	
							$label_transl = trim( $label_transl . ' ' . $this->get_option_value_transl( '(no videos)' ) );
						}
	
					} elseif ( '' ===  $label || 'none' === $label || '[None]' === $label ) {
	
						$label_transl = $this->get_option_value_transl( '[None]' );
					}
	
					$sorted[ $option_value ] = trim( $optgroup_transl . ' ' . $label_transl );
				}
			}

			if ( $sort_by_key ) {

				uksort( $sorted, array( __CLASS__, 'sort_select_labels' ) );

			} elseif ( empty( $event_args[ 'is_sorted' ] ) ) {

				uasort( $sorted, array( __CLASS__, 'sort_select_labels' ) );
			}

			return $sorted;
		}

		private function split_name_locale( $name_prefix ) {

			$name_suffix = '';

			/*
			 * The '.*?' syntax is required to make the expression ungreedy.
			 */
			if ( preg_match( '/^(.*?)((_[0-9]+)?(#[a-zA-Z_]+)?)$/', $name_prefix, $matches ) ) {

				$name_prefix = $matches[ 1 ];

				$name_suffix = $matches[ 2 ];
			}

			return array( $name_prefix, $name_suffix );
		}

		private static function sort_select_labels( $a_label, $b_label ) {

			/*
			 * Option labels in square brackets (ie. "[None]") are always top-most in the select options list.
			 */
			$a_char = substr( $a_label, 0, 1 );
			$b_char = substr( $b_label, 0, 1 );

			if ( 'none' === $a_label || $a_char === '[' ) {

				if ( $a_char === $b_char ) {

					return strnatcmp( $a_label, $b_label );
				}

				return -1;	// $a is first.

			} elseif ( 'none' === $b_label || $b_char === '[' ) {

				return 1;	// $b is first.
			}

			return strnatcmp( $a_label, $b_label );	// Binary safe case-insensitive string comparison.
		}

		private function get_input_media_url( $name_prefix, $primary_suffix = 'id', $holder = '', $is_disabled = false ) {

			list( $name_prefix, $name_suffix ) = $this->split_name_locale( $name_prefix );

			$name = $name_prefix . '_url' . $name_suffix;

			$primary_name  = $name_prefix . '_' . $primary_suffix . $name_suffix;
			$primary_value = $this->get_options_locale( $primary_name );

			if ( ! empty( $primary_value ) ) {

				$this->options[ $name ] = '';

				$is_disabled = true;
			}

			$html        = '';
			$holder      = $this->get_placeholder_sanitized( $name, $holder );
			$value       = $this->in_options( $name ) ? $this->options[ $name ] : '';
			$input_class = SucomUtil::sanitize_css_class( $css_class = 'wide' );
			$input_id    = SucomUtil::sanitize_css_id( $name );

			$html .= '<input type="text" name="' . esc_attr( $this->opts_name . '[' . $name . ']' ) . '"';
			$html .= $is_disabled ? ' disabled="disabled"' : '';
			$html .= empty( $input_class ) ? '' : ' class="' . $input_class . '"';	// Already sanitized.
			$html .= empty( $input_id ) ? '' : ' id="text_' . $input_id . '"';	// Already sanitized.
			$html .= $this->get_placeholder_attrs( $type = 'input', $holder, $name );
			$html .= ' value="' . esc_attr( $value ) . '" />' . "\n";

			return $html;
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

			/*
			 * Do not pre-populate an empty input field for these option names.
			 */
			if ( preg_match( '/_tid$/', $name ) ) {

				$js_if_empty = '';

			} else {

				$js_if_empty = 'if ( this.value == \'\' ) this.value = this.getAttribute( \'placeholder\' );';
			}

			$js_if_same = 'if ( this.value == this.getAttribute( \'placeholder\' ) ) this.value = \'\';';

			$html = ' placeholder="' . esc_attr( $holder ) . '"';

			/*
			 * If the value is an empty string, then set the value to the placeholder.
			 */
			$html .= $js_if_empty ? ' onClick="' . $js_if_empty . '"' : '';
			$html .= $js_if_empty ? ' onFocus="' . $js_if_empty . '"' : '';
			$html .= $js_if_empty ? ' onMouseEnter="' . $js_if_empty . '"' : '';

			/*
			 * If the value is the placeholder, then set the value to an empty string.
			 */
			$html .= $js_if_same ? ' onChange="' . $js_if_same . '"' : '';
			$html .= $js_if_same ? ' onBlur="' . $js_if_same . '"' : '';
			$html .= $js_if_same ? ' onMouseLeave="' . $js_if_same . '"' : '';

			/*
			 * Check for the enter key, which submits the current form in an input field.
			 */
			$html .= $js_if_same && 'input' === $type ? ' onKeyPress="if ( event.keyCode === 13 ) { ' . $js_if_same . ' }"' : '';

			return $html;
		}

		private function get_placeholder_dep_script( $container_id, $container_dep_id ) {

			$html = '<script>';
			$html .= 'jQuery( \'' . $container_dep_id . '\' ).on( \'sucom_changed\', function(){';
			$html .= 'sucomPlaceholderDep( \'' . $container_id . '\', \'' . $container_dep_id . '\' );';
			$html .= '});';
			$html .= '</script>' . "\n";

			return $html;
		}

		private function get_textlen_script( $input_id ) {

			if ( empty( $input_id ) ) {	// Nothing to do.

				return '';	// Return an empty string.
			}

			$input_id   = SucomUtil::sanitize_css_id( $input_id );
			$doing_ajax = SucomUtilWP::doing_ajax();

			$html = '<script>';

			$html .= $doing_ajax ? '' : 'jQuery( document ).on( \'ready\', function(){';	// Make sure sucomTextLen() is available.

			$html .= 'jQuery( \'#' . $input_id . '\' )' .
				'.mouseenter( function(){ window.sucom_text_len_t = setTimeout( function() { ' .
					'sucomTextLen( \'' . $input_id . '\', \'' .  $this->admin_l10n . '\' ); }, 300 ) })' .
				'.mouseleave( function(){ clearTimeout( window.sucom_text_len_t ); window.sucom_text_len_t = undefined; ' .
					'sucomTextLenReset( \'' . $input_id . '\' ); })' .
				'.focus( function(){ sucomTextLen( \'' . $input_id . '\', \'' .  $this->admin_l10n . '\' ); })' .
				'.keyup( function(){ sucomTextLen( \'' . $input_id . '\', \'' .  $this->admin_l10n . '\' ); })' .
				'.blur( function(){ sucomTextLenReset( \'' . $input_id . '\' ); });';

			$html .= $doing_ajax ? '' : '});';

			$html .= '</script>' . "\n";

			return $html;
		}

		private function get_event_load_json_script( $event_json_var, $event_args, $select_json_arr, $select_id ) {

			$html = '';

			if ( ! $event_json_var || ! is_string( $event_json_var ) ) {	// Just in case.

				return $html;
			}

			/*
			 * Encode the PHP array to JSON only once per page load.
			 */
			if ( empty( $this->json_array_added[ $event_json_var ] ) ) {

				$this->json_array_added[ $event_json_var ] = true;

				/*
				 * json_encode() cannot encode an associative array - only an object or a standard numerically
				 * indexed array - and the object element order, when read by the browser, cannot be controlled.
				 * Firefox, for example, will sort an object numerically instead of maintaining the original object
				 * element order. For this reason, we must use different arrays for the array keys and their
				 * values.
				 */
				$json_array_keys   = wp_json_encode( array_keys( $select_json_arr ) );
				$json_array_values = wp_json_encode( array_values( $select_json_arr ) );

				$script_js = 'var ' . $event_json_var . '_keys = ' . $json_array_keys . ';' . "\n";
				$script_js .= 'var ' . $event_json_var . '_vals = ' . $json_array_values . ';' . "\n";

				$html .= '<!-- adding ' . $event_json_var . ' array -->' . "\n";

				if ( ! empty( $event_args[ 'exp_secs' ] ) ) {

					/*
					 * Array values may be localized, so include the current locale in the cache salt string.
					 */
					$cache_salt = $event_json_var . '_locale:' . SucomUtilWP::get_locale();

					/*
					 * Returns false on error.
					 */
					$script_url = $this->p->cache->get_data_url( $cache_salt, $script_js, $event_args[ 'exp_secs' ], $pre_ext = '.js' );

					if ( ! empty( $script_url ) ) {

						$html .= '<script src="' . $script_url . '" async></script>' . "\n";

					} else $html .= '<script>' . "\n" . $script_js . '</script>' . "\n";

				} else $html .= '<script>' . "\n" . $script_js . '</script>' . "\n";

			} else $html .= '<!-- ' . $event_json_var . ' array already added -->' . "\n";

			/*
			 * The 'mouseenter' event is required for Firefox to render the option list correctly.
			 *
			 * sucomSelectLoadJson() is loaded in the footer, so test to make sure the function is available.
			 */
			$select_id_esc = esc_js( $select_id );

			$html .= '<script>';
			$html .= 'jQuery( \'select#' . $select_id_esc . ':not( .json_loaded )\' ).on( \'mouseenter focus sucom_load_json\', function(){';
			$html .= 'if ( \'function\' === typeof sucomSelectLoadJson ) {';
			$html .= 'sucomSelectLoadJson( \'select#' . $select_id_esc . '\', \'' . $event_json_var . '\' );';
			$html .= '}';
			$html .= '});';
			$html .= '</script>' . "\n";

			return $html;
		}

		private function get_show_hide_trigger_script() {

			if ( $this->show_hide_js_added ) {	// Only add the event script once.

				return '';
			}

			$this->show_hide_js_added = true;

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
