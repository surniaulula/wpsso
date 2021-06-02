
jQuery( document ).bind( 'sucom_init_metabox', function( event, container_id, doing_ajax ) {

	sucomInitAdminMedia( container_id, doing_ajax );
} );

function sucomInitAdminMedia( container_id, doing_ajax ) {

	var table_id = 'table.sucom-settings';

	if ( typeof container_id !== 'undefined' && container_id ) {

		table_id = container_id + ' ' + table_id;
	}

	jQuery( table_id + ' .sucom_image_upload_pid' ).each( function( event ) {
	
		sucomShowLibraryImage( this, event );
	} );

	jQuery( document ).on( 'change', table_id + ' .sucom_image_upload_pid', function( event ) {

		sucomShowLibraryImage( this, event );
	} );

	jQuery( document ).on( 'click', table_id + ' .sucom_image_upload_button', function( event ) {

		sucomSelectLibraryImage( this, event );
	} );
}

function sucomShowLibraryImage( t, e ) {

	var pid = jQuery( t ).val();

	if ( ! pid ) {

		pid = jQuery( t ).attr( 'placeholder' );
	}

	var option_prefix  = jQuery( t ).attr( 'id' ).replace( /^text_(.*)$/, '$1' );
	var option_suffix  = '';

	if ( option_prefix.match( /^.*_[0-9]+$/ ) ) {

		option_suffix = option_prefix.replace( /^(.*)(_[0-9]+)$/, '$2' );
		option_prefix = option_prefix.replace( /^(.*)(_[0-9]+)$/, '$1' );
	}

	var container = jQuery( '#preview_' + option_prefix + option_suffix );

	container.empty();

	if ( jQuery.isNumeric( pid ) && pid ) {

		jQuery( '#select_' + option_prefix + '_pre' + option_suffix ).val( 'wp' ).change();

		jQuery( '#text_' + option_prefix + '_url' + option_suffix ).val( '' ).change();
		jQuery( '#text_' + option_prefix + '_url' + option_suffix ).prop( 'disabled', true );

		var q = new wp.media.model.Attachment.get( pid );

		q.fetch( { success:function( ret ) {

			if ( typeof attachment.sizes.thumbnail.url !== 'undefined' ) {

				var thumbnail = attachment.sizes.thumbnail;

				if ( container ) {

					var img_html = '<img src="' + thumbnail.url + '"';

					if ( thumbnail.width ) {
		
						img_html += ' width="' + thumbnail.width + '"';
					}

					if ( thumbnail.height ) {
		
						img_html += ' height="' + thumbnail.height + '"';
					}

					img_html += '/>';

					container.empty();

					container.append( img_html );
				}
			}
		} } );

	} else {

	}
}

function sucomSelectLibraryImage( t, e ) {

	var default_pid   = jQuery( t ).attr( 'data-pid' );
	var option_prefix = jQuery( t ).attr( 'id' ).replace( /^button_(.*)$/, '$1' );
	var option_suffix = '';

	if ( option_prefix.match( /^.*_[0-9]+$/ ) ) {

		option_suffix = option_prefix.replace( /^(.*)(_[0-9]+)$/, '$2' );
		option_prefix = option_prefix.replace( /^(.*)(_[0-9]+)$/, '$1' );
	}

	e.preventDefault();

	if ( typeof window.sucom_image_upload_media !== 'undefined' ) {

		window.sucom_image_upload_media.close();
	}

	window.sucom_image_upload_media = wp.media( {
		title: sucomAdminMediaL10n._select_image,
		button: { text: sucomAdminMediaL10n._select_image },
		multiple: false,
		library: {
			type: 'image',
		},
	} );

	window.sucom_image_upload_media.on( 'open', function() {

		if ( jQuery.isNumeric( default_pid ) && default_pid ) {

			var selection = window.sucom_image_upload_media.state().get( 'selection' );

			var attachment = wp.media.attachment( default_pid );

			selection.add( attachment ? [ attachment ] : [] );
		}

		jQuery( '.media-modal', t.el ).find( '#media-search-input' ).focus();
	} );

	window.sucom_image_upload_media.on( 'select', function() {

		var attachment = window.sucom_image_upload_media.state().get( 'selection' ).first().toJSON();

		jQuery( t ).attr( 'data-pid', attachment.id );

		jQuery( '#text_' + option_prefix + '_id' + option_suffix ).val( attachment.id ).change();
	} );

	window.sucom_image_upload_media.open();
};
