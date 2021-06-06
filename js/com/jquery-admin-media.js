
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

	var pid            = jQuery( t ).val();
	var preview_css_id = jQuery( t ).attr( 'data-preview-css-id' );

	if ( ! pid ) {

		pid = jQuery( t ).attr( 'placeholder' );
	}

	if ( ! preview_css_id || ! pid ) {	// Nothing to do.

		return;
	}

	var container = jQuery( '#' + preview_css_id );

	container.empty();

	if ( jQuery.isNumeric( pid ) && pid ) {

		var img_lib_css_id = jQuery( t ).attr( 'data-img-lib-css-id' );
		var img_url_css_id = jQuery( t ).attr( 'data-img-url-css-id' );

		if ( img_lib_css_id ) {
		
			jQuery( '#' + img_lib_css_id ).val( 'wp' ).change();
		}

		if ( img_url_css_id ) {
		
			jQuery( '#' + img_url_css_id ).val( '' ).change();
			jQuery( '#' + img_url_css_id ).prop( 'disabled', true );
		}

		var q = new wp.media.model.Attachment.get( pid );

		q.fetch( { success:function( ret ) {

			if ( typeof ret.attributes.sizes.thumbnail.url !== 'undefined' ) {

				var thumbnail = ret.attributes.sizes.thumbnail;

				if ( container ) {

					var img_html = '<img src="' + thumbnail.url + '"';

					if ( thumbnail.width ) {

						img_html += ' width="' + thumbnail.width + '"';
					}

					if ( thumbnail.height ) {

						img_html += ' height="' + thumbnail.height + '"';
					}

					img_html += '/>';

					container.append( img_html );
				}
			}
		} } );
	}
}

function sucomSelectLibraryImage( t, e ) {

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

		var pid = jQuery( t ).attr( 'data-pid' );

		if ( jQuery.isNumeric( pid ) && pid ) {

			var selection  = window.sucom_image_upload_media.state().get( 'selection' );
			var attachment = wp.media.attachment( pid );

			selection.add( attachment ? [ attachment ] : [] );
		}

		jQuery( '.media-modal', t.el ).find( '#media-search-input' ).focus();
	} );

	window.sucom_image_upload_media.on( 'select', function() {

		var attachment    = window.sucom_image_upload_media.state().get( 'selection' ).first().toJSON();
		var img_id_css_id = jQuery( t ).attr( 'data-img-id-css-id' );

		jQuery( t ).attr( 'data-pid', attachment.id );

		jQuery( '#' + img_id_css_id ).val( attachment.id ).change();
	} );

	window.sucom_image_upload_media.open();
};
