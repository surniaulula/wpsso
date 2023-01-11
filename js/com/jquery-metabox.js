
/**
 * Provide a parent container_id value to initialize a single metabox (when loading a single metabox via ajax, for example).
 */
function sucomInitMetabox( container_id, doing_ajax ) {

	var table_id = 'table.sucom-settings';

	if ( 'string' === typeof container_id && container_id ) {

		table_id = container_id + ' ' + table_id;
	}

	/**
	 * Style the datepicker.
	 */
	jQuery( table_id + ' input.datepicker' ).datepicker( {
		beforeShow:function( input, inst ) {
			jQuery( '#ui-datepicker-div' ).addClass( 'sucom-settings' );
		},
		changeMonth:true,
		changeYear:true,
		showButtonPanel:false,
		dateFormat:'yy-mm-dd'
	} );

	/**
	 * Softly disable input fields using the 'disabled' CSS class instead of using the standard 'disabled' HTML tag attribute
	 * (which prevents values from being submitted).
	 */
	jQuery( table_id + ' input' ).click( sucomBlurDisabled );	// Includes checkbox and radio.
	jQuery( table_id + ' input' ).focus( sucomBlurDisabled );
	jQuery( table_id + ' textarea' ).focus( sucomBlurDisabled );
	jQuery( table_id + ' select' ).focus( sucomBlurDisabled );
	jQuery( table_id + ' select' ).on( 'mousedown', sucomBlurDisabled );	// Prevents dropdown from appearing.

	/**
	 * Add a "changed" the options class when their value might have changed.
	 */
	jQuery( table_id + ' input.colorpicker' ).wpColorPicker( { change:sucomColorChanged } );
	jQuery( table_id + ' input[type="checkbox"]' ).blur( sucomMarkChanged ).change( sucomMarkChanged );
	jQuery( table_id + ' input[type="text"]' ).blur( sucomMarkChanged ).change( sucomMarkChanged );
	jQuery( table_id + ' textarea' ).blur( sucomMarkChanged ).change( sucomMarkChanged );
	jQuery( table_id + ' select' ).blur( sucomMarkChanged ).change( sucomMarkChanged );

	jQuery( document ).on( 'click', table_id + ' input[type="checkbox"][data-group]', function() {

		var actor      = jQuery( this );
		var checked    = actor.prop( 'checked' );
		var group      = actor.data( 'group' );
		var checkboxes = jQuery( 'input[type="checkbox"][data-group="' + group + '"]' );

		checkboxes.prop( 'checked', checked );

		checkboxes.addClass( 'changed' );
	} );

	/**
	 * The 'sucom_init_metabox' event is hooked by sucomInitAdminMedia(), sucomInitToolTips().
	 */
	jQuery( document ).trigger( 'sucom_init_metabox', [ container_id, doing_ajax ] );

	/**
	 * If we're refreshing a metabox via ajax, trigger a 'show' event for each table row displayed.
	 */
	if ( doing_ajax ) {

		jQuery( table_id + ' tr' ).each( function() {

			if ( 'none' !== jQuery( this ).css( 'display' ) ) {

				jQuery( this ).show();
			}
		} );
	}

	/**
	 * When the post editing page is submitted, disable unchanged fields in 'table.sucom-settings'.
	 */
	jQuery( 'form#post' ).submit( function ( event ) {

		sucomDisableUnchanged( container_id );

	} );
}

function sucomSelectLoadJson( select_id, json_name ) {

	/**
	 * A select ID must be provided.
	 *
	 * Example: "select_schema_type_for_home_posts"
	 */
	if ( ! select_id ) {

		return false;
	}

	/**
	 * The variable name of the JSON array.
	 *
	 * Example: "wpsso_select_person_names_4f65aec5b650c0f4acc0f033dc81d39f"
	 */
	if ( ! window[ json_name + '_keys' ] || ! window[ json_name + '_vals' ] ) {

		return false;
	}

	var container = jQuery( select_id + ':not( .json_loaded )' );

	if ( ! container.length ) {

		return false;
	}

	/**
	 * Avoid contention by signaling the json load early.
	 */
	container.addClass( 'json_loaded' );

	var default_value   = container.data( 'default-value' );
	var default_text    = container.data( 'default-text' );
	var selected_val    = container.val();
	var select_opt_html = ''

	/**
	 * json_encode() cannot encode an associative array - only an object or a standard numerically indexed array - and the
	 * object element order, when read by the browser, cannot be controlled. Firefox, for example, will sort an object
	 * numerically instead of maintaining the original object element order. For this reason, we must use different arrays for
	 * the array keys and their values.
	 */
	jQuery.each( window[ json_name + '_keys' ], function ( index, option_value ) {

		label_transl = window[ json_name + '_vals' ][ index ];

		select_opt_html += '<option value="' + option_value + '"';

		if ( option_value == selected_val ) {	/* Allow numeric string/integer comparison. */

			select_opt_html += ' selected="selected"';
		}

		if ( default_value == option_value ) {	/* Allow numeric string/integer comparison. */

			label_transl += ' ' + default_text;
		}

		select_opt_html += '>' + label_transl + '</option>';
	} );

	/**
	 * Update the select option list.
	 *
	 * Do not trigger a change event as the selected option has not changed. ;-)
	 */
	container.empty();

	container.append( select_opt_html );
}

function sucomSelectUniquePair( main_id, other_id ) {

	var main      = jQuery( main_id );
	var other     = jQuery( other_id );
	var main_val  = main.val();
	var other_val = other.val();

	if ( 'none' !== main_val ) {	// If the main select has a value.

		if ( 'none' !== other_val ) {	// Maybe set the other select value to 'none'.

			other.trigger( 'sucom_load_json' ).val( 'none' ).trigger( 'change' );
		}

		other.addClass( 'disabled' );	// Disable the other select.

	} else {

		other.removeClass( 'disabled' );	// Re-enable the other select.
	}
}

function sucomTabs( metabox_name, tab_name ) {

	metabox_name = metabox_name ? metabox_name : '_default';
	tab_name     = tab_name ? tab_name : '_default';

	var location_hash    = window.location.hash;
	var active_tab_class = '.sucom-tabset' + metabox_name + '-tab' + tab_name;
	var scroll_to_tab_id = '';

	if ( location_hash !== '' && location_hash.search( 'sucom-tabset' + metabox_name + '-tab_' ) !== -1 ) {

		active_tab_class = location_hash.replace( '#', '.' );
		scroll_to_tab_id = 'div#sucom-metabox-tabs' + metabox_name;
	}

	jQuery( active_tab_class ).addClass( 'active' );
	jQuery( active_tab_class + '-msg' ).addClass( 'active' );
	jQuery( '.sucom-metabox-tabs' ).show();

	if ( scroll_to_tab_id ) {

		/**
		 * Do not scroll the metabox into view if this is a visual editor page.
		 */
		var editor_content = jQuery( 'div.interface-interface-skeleton__content' );	// Page might be a visual editor container.

		if ( ! editor_content.length ) {	// Scrolling is allowed if the visual editor container length is 0.

			sucomScrollIntoView( scroll_to_tab_id );
		}
	}

	jQuery( 'a.sucom-tablink' + metabox_name ).click( function(){

		jQuery( 'ul.sucom-metabox-tabs' + metabox_name + ' li' ).removeClass( 'active' );
		jQuery( '.sucom-tabset' + metabox_name ).removeClass( 'active' );
		jQuery( '.sucom-tabset' + metabox_name + '-msg' ).removeClass( 'active' );

		/**
		 * Example tablink:
		 *
		 * <a class="sucom-tablink sucom-tablink_sso sucom-tablink-icon sucom-tablink-href_edit_general" href="#sucom-tabset_sso-tab_edit_general"></a>
		 * <a class="sucom-tablink sucom-tablink_sso sucom-tablink-text sucom-tablink-href_edit_general" href="#sucom-tabset_sso-tab_edit_general">Edit General</a>
		 */
		var href = jQuery( this ).attr( 'href' ).replace( '#', '' );

		jQuery( '.' + href ).addClass( 'active' );
		jQuery( '.' + href + '-msg' ).addClass( 'active' );
		jQuery( this ).parent().addClass( 'active' );

		sucomScrollIntoView( 'div#sucom-metabox-tabs' + metabox_name );
	});
}

function sucomScrollIntoView( container_id ) {

	if ( ! container_id ) {	// A container id string is required.

		return false;
	}

	var wpbody_content = jQuery( 'div#wpbody-content' );	// Located below the admin toolbar.
	var container      = jQuery( container_id );

	if ( ! wpbody_content.length || ! container.length ) {

		return false;
	}

	var toolbar_offset = wpbody_content.offset().top;	// Just under the admin toolbar.
	var footer_offset  = 0;
	var editor_content = jQuery( 'div.interface-interface-skeleton__content' );	// Page might be a visual editor container.
	var viewport       = {};
       	var bounds         = {};
	var scroll_offset  = 0;

	viewport.top    = jQuery( window ).scrollTop();
	viewport.bottom = viewport.top + jQuery( window ).height();

	bounds.top    = container.offset().top;
	bounds.bottom = bounds.top + container.outerHeight();

	if ( ! bounds.top || ! bounds.bottom ) {	// Just in case.

		return false;
	}

	if ( editor_content.length ) {	// This is a visual editor page.

		var editor_top    = jQuery( 'div.edit-post-visual-editor' ).offset().top;	// Block editor section.
		var footer_offset = jQuery( 'div.interface-interface-skeleton__footer' ).height();

		toolbar_offset   = editor_content.offset().top;	// Just under the 'interface-interface-skeleton__header'.
		scroll_container = editor_content;
		scroll_offset    = bounds.top - editor_top + 1;

	} else {

		scroll_container = jQuery( 'html, body' );
		scroll_offset    = bounds.top - toolbar_offset;
	}

	if ( bounds.top < viewport.top + toolbar_offset || bounds.bottom > viewport.bottom - footer_offset ) {

		scroll_container.stop().animate( { scrollTop:scroll_offset }, 'fast' );
	}
}

/**
 * Example: sucomViewUnhideRows( 'sucom-tabset_doc_types-tab_schema_types', 'basic' )
 */
function sucomViewUnhideRows( container_id, show_opts_key, hide_in_pre ) {

	hide_in_pre = hide_in_pre ? hide_in_pre : 'hide_in';

	if ( ! container_id ) {	// A container id string is required.

		return false;
	}

	var message = jQuery( 'div.' + container_id + '-msg' );

	if ( ! message.length ) {	// Just in case.

		return false;
	}

	message.hide();

	jQuery( 'div.' + container_id ).find( '.' + hide_in_pre + '_' + show_opts_key ).show();

	var parent_id = message.parent( 'div' ).attr( 'id' );

	sucomScrollIntoView( 'div#' + parent_id );
}

/**
 * Example: sucomSelectChangeUnhideRows( "hide_schema_type", "hide_schema_type_article" );
 */
function sucomSelectChangeUnhideRows( row_hide_class, row_show_class ) {

	if ( row_hide_class ) {

		row_hide_class = row_hide_class.replace( /[:\/\-\. ]+/g, '_' );

		jQuery( 'tr.' + row_hide_class ).hide();
	}

	if ( row_show_class ) {

		row_show_class = row_show_class.replace( /[:\/\-\. ]+/g, '_' );

		jQuery( 'tr.' + row_show_class ).show();
	}
}

function sucomSelectChangeRedirect( name, value, redirect_url ) {

	url = redirect_url + window.location.hash;

        window.location = url.replace( '%%' + name + '%%', value );
}

/**
 * Softly disable input fields using the 'disabled' CSS class instead of using the standard 'disabled' HTML tag attribute (which
 * prevents values from being submitted).
 */
function sucomBlurDisabled( e ) {

	var is_disabled = jQuery( this ).hasClass( 'disabled' );

	if ( is_disabled ) {

		this.blur();

		window.focus();

		e.preventDefault();

		e.stopPropagation();
	}
}

/**
 * Add a "changed" the options class when their value might have changed.
 */
function sucomMarkChanged( e, el ) {

	if ( 'undefined' === typeof el ) {

		el = jQuery( this );
	}

	el.addClass( 'changed' );

	jQuery( el ).trigger( 'sucom_changed' );
}

function sucomPlaceholderDep( container_id, container_dep_id ) {

	var el      = jQuery( container_id );
	var dep_el  = jQuery( container_dep_id );
	var dep_val = dep_el.val();

	if ( '' === dep_val ) dep_val = dep_el.attr( 'placeholder' );

	el.attr( 'placeholder', dep_val ).change();
}

/**
 * A callback function for the wpColorPicker, which does not automatically update the input field value on changes.
 */
function sucomColorChanged( e, ui ) {

	var el    = jQuery( this );
	var name  = el.attr( 'name' );
	var value = ui.color.toString();

	sucomMarkChanged( e, el )
}

/**
 * sucomDisableUnchanged() should be called from a form submit event.
 *
 * Example: container_id = '#sucom_setting_form_general'
 */
function sucomDisableUnchanged( container_id ) {

	var table_id = 'table.sucom-settings';

	if ( 'string' === typeof container_id && container_id ) {

		table_id = container_id + ' ' + table_id;
	}

	jQuery( table_id + ' .changed' ).prop( 'disabled', false );

	jQuery( table_id + ' input[type="checkbox"]:not( .changed )' ).each( function() {

		jQuery( this ).prop( 'disabled', true );

		var checkbox_name = jQuery( this ).attr( 'name' );

		if ( 'undefined' !== typeof checkbox_name && checkbox_name.length ) {

			/**
			 * When disabling a checkbox, also disable it's associated hidden input field.
			 */
			hidden_checkbox_name = checkbox_name.replace( /^(.*)\[(.*)\]$/, '$1\\[is_checkbox_$2\\]' );

			jQuery( table_id + ' input[name="' + hidden_checkbox_name + '"]' ).remove();
		}
	} );

	jQuery( table_id + ' input[type="radio"]:not( .changed )' ).prop( 'disabled', true );
	jQuery( table_id + ' input[type="text"]:not( .changed )' ).prop( 'disabled', true );
	jQuery( table_id + ' textarea:not( .changed )' ).prop( 'disabled', true );
	jQuery( table_id + ' select:not( .changed )' ).prop( 'disabled', true );
}

function sucomToggle( css_id ) {

	jQuery( '#' + css_id ).toggle();

	return false;
}
