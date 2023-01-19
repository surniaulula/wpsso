
/*
 * This script is registered by WpssoScript->admin_enqueue_scripts() as 'wpsso-metabox' and depends on the 'sucom-metabox' script
 * located in js/com/jquery-metabox.js. The 'sucom-metabox' script must be registered since this script depends on it.
 */
jQuery( function() {

	/*
	 * Initialize all the 'table.sucom-settings' metaboxes in the current page.
	 */
	wpssoInitMetabox();
} );

/*
 * Provide a parent container_id value to initialize a single metabox (when loading a single metabox via ajax, for example).
 */
function wpssoInitMetabox( container_id, doing_ajax ) {

	var table_id = 'table.sucom-settings';

	if ( 'string' === typeof container_id && container_id ) {

		table_id = container_id + ' ' + table_id;
	}

	/*
	 * When the Schema type is changed, maybe update the Open Graph type.
	 */
	jQuery( table_id + ' select#select_og_schema_type' ).show( wpssoOgSchemaType );
	jQuery( table_id + ' select#select_og_schema_type' ).change( wpssoOgSchemaType );

	/*
	 * When an Organization ID is selected, disable the Place ID.
	 */
	jQuery( table_id + ' select#select_schema_organization_id' ).show( wpssoSchemaOrgId );
	jQuery( table_id + ' select#select_schema_organization_id' ).change( wpssoSchemaOrgId );

	/*
	 * When a Place ID is selected, disable the Organization ID.
	 */
	jQuery( table_id + ' select#select_schema_place_id' ).show( wpssoSchemaPlaceId );
	jQuery( table_id + ' select#select_schema_place_id' ).change( wpssoSchemaPlaceId );

	/*
	 * The sucomInitMetabox() function is located in the 'sucom-metabox' script located in js/com/jquery-metabox.js.
	 */
	sucomInitMetabox( container_id, doing_ajax);
}

/*
 * When the Schema type is changed, maybe update the Open Graph type.
 */
function wpssoOgSchemaType() {

	var pluginId      = 'wpsso';
	var adminPageL10n = 'wpssoAdminPageL10n';
	var cfg           = window[ adminPageL10n ];

	if ( ! cfg[ '_ajax_actions' ][ 'schema_type_og_type' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_actions schema_type_og_type' );

		return;
	}

	var select_schema_type = jQuery( this );
	var schema_type_val    = select_schema_type.val();

	var ajaxData = {
		action: cfg[ '_ajax_actions' ][ 'schema_type_og_type' ],	// Returns schema_og_type_val.
		schema_type: schema_type_val,
		_ajax_nonce: cfg[ '_ajax_nonce' ],
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

		/*
		 * If we have an associated Open Graph type for this Schema type, then update the Open Graph value and disable the
		 * select.
		 */
		if ( schema_og_type_val ) {	// Can be false.

			var schema_type_label = cfg[ '_option_labels' ][ 'schema_type' ];
			var linked_to_label   = cfg[ '_linked_to_transl' ].formatUnicorn( schema_type_label );

			select_og_type.after( '<div id="og_type_linked" class="dashicons dashicons-admin-links linked_to_label" title="' + linked_to_label + '"></div>' );

			if ( schema_og_type_val !== og_type_val ) {

				og_type_option.removeAttr( 'selected' ).filter( '[value=' + schema_og_type_val + ']' ).attr( 'selected', true )

				select_og_type.trigger( 'sucom_load_json' ).val( schema_og_type_val ).trigger( 'change' );
			}

			select_og_type.addClass( 'disabled' );

		/*
		 * If we don't have an associated Open Graph type for this Schema type, then if previously disabled, reenable and
		 * set to the default value.
		 */
		} else if ( og_type_linked.length ) {

			if ( def_og_type_val.length ) {

				if ( def_og_type_val !== og_type_val ) {

					select_og_type.trigger( 'sucom_load_json' ).val( def_og_type_val ).trigger( 'change' );
				}
			}

			select_og_type.removeClass( 'disabled' );
		}
	} );
}

/*
 * When an Organization ID is selected, disable the Place ID.
 */
function wpssoSchemaOrgId() {

	sucomSelectUniquePair( this, 'select#select_schema_place_id' );
}

/*
 * When a Place ID is selected, disable the Organization ID.
 */
function wpssoSchemaPlaceId() {

	sucomSelectUniquePair( this, 'select#select_schema_organization_id' );
}

