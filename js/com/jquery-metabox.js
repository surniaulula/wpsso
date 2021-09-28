
jQuery( function(){

	sucomInitMetabox();
});

function sucomInitMetabox( container_id, doing_ajax ) {

	var table_id = 'table.sucom-settings';

	if ( 'undefined' !== typeof container_id && container_id ) {

		table_id = container_id + ' ' + table_id;
	}

	jQuery( table_id + ' input.colorpicker' ).wpColorPicker({ change: sucomColorChanged });
	jQuery( table_id + ' input.datepicker' ).datepicker( { dateFormat:'yy-mm-dd' } );

	/**
	 * When the Schema type is changed, maybe update the Open Graph type.
	 */
	jQuery( table_id + ' select#select_og_schema_type' ).show( sucomOgSchemaType );
	jQuery( table_id + ' select#select_og_schema_type' ).change( sucomOgSchemaType );

	/**
	 * When an Organization ID is selected, disable the Place ID.
	 */
	jQuery( table_id + ' select#select_schema_organization_id' ).show( sucomSchemaOrgId );
	jQuery( table_id + ' select#select_schema_organization_id' ).change( sucomSchemaOrgId );

	/**
	 * When a Place ID is selected, disable the Organization ID.
	 */
	jQuery( table_id + ' select#select_schema_place_id' ).show( sucomSchemaPlaceId );
	jQuery( table_id + ' select#select_schema_place_id' ).change( sucomSchemaPlaceId );

	/**
	 * Add a "changed" the options class when their value might have changed. 
	 */
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
	 * Example: "sucom_form_select_schema_item_types_json"
	 */
	if ( ! window[ json_name + '_array_keys' ] || ! window[ json_name + '_array_values' ] ) {

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
	jQuery.each( window[ json_name + '_array_keys' ], function ( index, option_value ) {

		label_transl = window[ json_name + '_array_values' ][ index ];

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

function sucomEscAttr ( string ) {

	var entity_map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&apos;',
	};

	return String( string ).replace( /[&<>"']/g, function ( s ) {

		return entity_map[ s ];
	} );
}

/**
 * When the Schema type is changed, maybe update the Open Graph type.
 */
function sucomOgSchemaType() {

	var select_schema_type = jQuery( this );
	var schema_type_val    = select_schema_type.val();

	var ajaxData = {
		action: sucomAdminPageL10n._ajax_actions[ 'schema_type_og_type' ],	// Returns schema_og_type_val.
		schema_type: schema_type_val,
		_ajax_nonce: sucomAdminPageL10n._ajax_nonce,
	}

	jQuery.post( ajaxurl, ajaxData, function( schema_og_type_val ) {

		var select_og_type  = jQuery( 'select#select_og_type' );
		var og_type_linked  = jQuery( 'div#og_type_linked' );	/* May not exist. */
		var og_type_option  = jQuery( 'select#select_og_type option' );
		var og_type_val     = select_og_type.val();
		var def_og_type_val = select_og_type.data( 'default-value' );

		if ( og_type_linked.length ) {

			jQuery( og_type_linked ).remove();
		}

		/**
		 * If we have an associated Open Graph type for this Schema type, then update the Open Graph value and disable the
		 * select.
		 */
		if ( schema_og_type_val ) {	// Can be false.

			var schema_type_label = sucomAdminPageL10n._option_labels[ 'schema_type' ];
			var linked_to_label   = sucomAdminPageL10n._linked_to_transl.formatUnicorn( schema_type_label );

			select_og_type.after( '<div id="og_type_linked" class="dashicons dashicons-admin-links linked_to_label" title="' + linked_to_label + '"></div>' );

			if ( schema_og_type_val !== og_type_val ) {

				og_type_option.removeAttr( 'selected' ).filter( '[value=' + schema_og_type_val + ']' ).attr( 'selected', true )

				select_og_type.trigger( 'load_json' ).val( schema_og_type_val ).trigger( 'change' );
			}

			select_og_type.prop( 'disabled', true );

		/**
		 * If we don't have an associated Open Graph type for this Schema type, then if previously disabled, reenable and
		 * set to the default value.
		 */
		} else if ( og_type_linked.length ) {

			if ( def_og_type_val.length ) {

				if ( def_og_type_val !== og_type_val ) {

					select_og_type.trigger( 'load_json' ).val( def_og_type_val ).trigger( 'change' );
				}
			}

			select_og_type.prop( 'disabled', false );
		}
	} );
}

/**
 * When an Organization ID is selected, disable the Place ID.
 */
function sucomSchemaOrgId() {

	var select_schema_org   = jQuery( this );
	var select_schema_place = jQuery( 'select#select_schema_place_id' );

	sucomSelectUniquePair( select_schema_org, select_schema_place );
}

/**
 * When a Place ID is selected, disable the Organization ID.
 */
function sucomSchemaPlaceId() {

	var select_schema_place = jQuery( this );
	var select_schema_org   = jQuery( 'select#select_schema_organization_id' );

	sucomSelectUniquePair( select_schema_place, select_schema_org );

	var main_val = select_schema_place.val();

	/**
	 * The Schema Type option can be overwritten (and disabled) by the Schema Type of the selected place, so re-enable if the
	 * place value is changed to 'none'.
	 *
	 * See WpssoPlmFiltersOptions->update_md_opts().
	 */
	if ( 'none' === main_val ) {

		var select_schema_type = jQuery( 'select#select_og_schema_type' );

		select_schema_type.prop( 'disabled', false );	// Re-enable the Schema Type option.
	}
}

function sucomSelectUniquePair( select_main, select_other ) {

	var main_val  = select_main.val();
	var other_val = select_other.val();

	if ( 'none' !== main_val ) {	// If the main select has a value.

		if ( 'none' !== other_val ) {	// Maybe set the other select value to 'none'.

			other_val = 'none';

			select_other.trigger( 'load_json' ).val( other_val ).trigger( 'change' );
		}

		select_other.prop( 'disabled', true );	// Disable the other select.

	} else {

		select_other.prop( 'disabled', false );	// Re-enable the other select.
	}
}

function sucomTextLen( container_id ) {

	var text_val   = jQuery.trim( sucomClean( jQuery( '#' + container_id ).val() ) );
	var text_len   = text_val.length;
	var min_len    = Number( jQuery( '#' + container_id ).attr( 'minLength' ) );
	var warn_len   = Number( jQuery( '#' + container_id ).attr( 'warnLength' ) );
	var max_len    = Number( jQuery( '#' + container_id ).attr( 'maxLength' ) );
	var msg_transl = '{0} characters';

	/**
	 * If we have a max length, make sure it's larger than the minimum.
	 */
	if ( min_len && max_len && max_len < min_len ) {

		max_len = min_len;
	}

	var len_html   = sucomLenSpan( text_len, max_len, warn_len, min_len );
	var limit_html = max_len;

	if ( min_len ) {

		if ( ! max_len ) {

			limit_html = min_len;
			msg_transl = '{0} of {1} characters minimum';

			if ( ! sucomAdminPageL10n._min_len_transl ) {

				msg_transl = sucomAdminPageL10n._min_len_transl;
			}

		} else {

			if ( max_len > min_len ) {

				limit_html = String( min_len ) + '-' + String( max_len );
			}

			msg_transl = '{0} of {1} characters required';

			if ( ! sucomAdminPageL10n._req_len_transl ) {

				msg_transl = sucomAdminPageL10n._req_len_transl;
			}
		}

	} else if ( max_len ) {

		msg_transl = '{0} of {1} characters maximum';

		if ( ! sucomAdminPageL10n._max_len_transl ) {

			msg_transl = sucomAdminPageL10n._max_len_transl;
		}

	} else if ( ! sucomAdminPageL10n._len_transl ) {

		msg_transl = sucomAdminPageL10n._len_transl;
	}

	jQuery( '#' + container_id + '-text-length-message' ).html( '<div class="text_len_msg">' + msg_transl.formatUnicorn( len_html, limit_html ) + '</div>' )
}

function sucomTextLenReset( container_id ) {

	jQuery( '#' + container_id + '-text-length-message' ).html( '' )
}

function sucomLenSpan( text_len, max_len, warn_len, min_len ) {

	if ( ! min_len ) {

		min_len = 0;
	}

	if ( ! max_len ) {

		max_len = 0;
	}

	if ( ! warn_len ) {

		if ( max_len ) {

			warn_len = max_len - 20;

		} else {

			warn_len = 0;
		}
	}

	var css_class = '';

	if ( min_len && text_len < min_len ) {

		css_class = 'bad';

	} else if ( min_len && text_len >= min_len ) {

		css_class = 'good';

	} else if ( max_len && text_len >= ( max_len - 5 ) ) {

		css_class = 'bad';

	} else if ( warn_len && text_len >= warn_len ) {

		css_class = 'warn';

	} else {

		css_class = 'good';
	}

	return '<span class="' + css_class + '">' + text_len + '</span>';
}

function sucomClean( str ) {

	if ( 'undefined' === typeof str || ! str.length ) {

		return '';
	}

	try {

		str = str.replace( /<\/?[^>]+>/g, '' );
		str = str.replace( /\[(.+?)\](.+?\[\/\\1\])?/, '' )

	} catch( err ) {
	}

	return str;
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
		 * <a class="sucom-tablink sucom-tablink_sso sucom-tablink-icon sucom-tablink-href_edit" href="#sucom-tabset_sso-tab_edit"></a>
		 * <a class="sucom-tablink sucom-tablink_sso sucom-tablink-text sucom-tablink-href_edit" href="#sucom-tabset_sso-tab_edit">Customize</a>
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

	var wpbody    = jQuery( 'div#wpbody' );	// Located below the admin toolbar.
	var container = jQuery( container_id );

	if ( ! wpbody.length || ! container.length ) {

		return false;
	}

	var toolbar_offset = wpbody.offset().top;	// Just under the admin toolbar.
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
 * Add a "changed" the options class when their value might have changed. 
 */
function sucomMarkChanged() {

	jQuery( this ).addClass( 'changed' );
}

/**
 * A callback function for the wpColorPicker, which does not automatically update the input field value on changes.
 */
function sucomColorChanged( e, ui ) {

	var input = jQuery( this );
	var value = ui.color.toString();

	input.attr( 'value', value );

	input.addClass( 'changed' );
}

/**
 * sucomDisableUnchanged() should be called from a form submit event.
 *
 * Example: container_id = '#sucom_setting_form_general'
 */
function sucomDisableUnchanged( container_id ) {

	var table_id = 'table.sucom-settings';

	if ( 'undefined' !== typeof container_id && container_id ) {

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
