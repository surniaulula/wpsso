
jQuery( document ).bind( 'sucom_init_metabox', function( event, container_id, doing_ajax ) {

	sucomInitAdminMedia( container_id, doing_ajax );
} );

function sucomInitAdminMedia( container_id, doing_ajax ) {

	var sucom_image_upload_media;

	var table_id = 'table.sucom-settings';

	if ( typeof container_id !== 'undefined' && container_id ) {
		table_id = container_id + ' ' + table_id;
	}

	jQuery( document ).on( 'click', table_id + ' .sucom_image_upload_button', function( event ) {
		sucomHandleImageUpload( this, event );
	} );
}

function sucomHandleImageUpload( t, e ) {

	var default_pid = jQuery( t ).attr( 'data-pid' );
	var opt_prefix  = jQuery( t ).attr( 'id' ).replace( /^button_(.*)$/, '$1' );
	var opt_suffix  = '';

	if ( opt_prefix.match( /^.*_[0-9]+$/ ) ) {
		opt_suffix = opt_prefix.replace( /^(.*)(_[0-9]+)$/, '$2' );
		opt_prefix = opt_prefix.replace( /^(.*)(_[0-9]+)$/, '$1' );
	}

	e.preventDefault();

	if ( typeof sucom_image_upload_media !== 'undefined' ) {
		sucom_image_upload_media.close();
	}

	sucom_image_upload_media = wp.media( {
		title: sucomAdminMediaL10n._select_image,
		button: { text: sucomAdminMediaL10n._select_image },
		multiple: false,
		library: {
			type: 'image',
		},
	} );

	sucom_image_upload_media.on( 'open', function() {

		if ( jQuery.isNumeric( default_pid ) && default_pid ) {

			var selection  = sucom_image_upload_media.state().get( 'selection' );
			var attachment = wp.media.attachment( default_pid );

			selection.add( attachment ? [ attachment ] : [] );
		}

		jQuery( '.media-modal', t.el ).find( '#media-search-input' ).focus();
	} );

	sucom_image_upload_media.on( 'select', function() {

		var attachment = sucom_image_upload_media.state().get( 'selection' ).first().toJSON();

		jQuery( '#text_' + opt_prefix + '_id' + opt_suffix ).val( attachment.id ).change();
		jQuery( '#select_' + opt_prefix + '_id_pre' + opt_suffix ).val( 'wp' ).change();
		jQuery( '#text_' + opt_prefix + '_url' + opt_suffix ).val( '' ).change();
		jQuery( '#text_' + opt_prefix + '_url' + opt_suffix ).prop( 'disabled', true );
	} );

	sucom_image_upload_media.open();
};
