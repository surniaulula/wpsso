
jQuery( document ).bind( 'sucom_init_metabox', function( event, container_id, doing_ajax ) {

	sucomInitAdminMedia( container_id, doing_ajax );
} );

function sucomInitAdminMedia( container_id, doing_ajax ) {

	var table_id = 'table.sucom-settings';

	if ( typeof container_id !== 'undefined' && container_id ) {

		table_id = container_id + ' ' + table_id;
	}

	jQuery( table_id + ' .sucom_image_upload_lib' ).each( function( event ) {

		sucomMaybeShowUpload( this, event );	// Also executes sucomShowLibraryImage().
	} );

	jQuery( document ).on( 'change', table_id + ' .sucom_image_upload_lib', function( event ) {

		sucomMaybeShowUpload( this, event );	// Also executes sucomShowLibraryImage().
	} );

	jQuery( document ).on( 'change', table_id + ' .sucom_image_upload_id', function( event ) {

		sucomShowLibraryImage( this, event );
	} );

	jQuery( document ).on( 'click', table_id + ' .sucom_image_upload_button', function( event ) {

		sucomSelectLibraryImage( this, event );
	} );
}

function sucomMaybeShowUpload( t, e ) {

	var img_lib_value = jQuery( t ).val();
	var img_id_css_id = jQuery( t ).attr( 'data-img-id-css-id' );
	var upload_css_id = jQuery( t ).attr( 'data-upload-css-id' );

	if ( 'wp' === img_lib_value ) {

		jQuery( '#' + upload_css_id ).show();

	} else {

		jQuery( '#' + upload_css_id ).hide();
	}

	if ( img_id_css_id ) {

		var img_id_container = jQuery( '#' + img_id_css_id );

		sucomShowLibraryImage( img_id_container, e );
	}
}

function sucomShowLibraryImage( t, e ) {

	var img_id_value   = jQuery( t ).val();
	var img_lib_css_id = jQuery( t ).attr( 'data-img-lib-css-id' );
	var img_url_css_id = jQuery( t ).attr( 'data-img-url-css-id' );
	var preview_css_id = jQuery( t ).attr( 'data-preview-css-id' );

	if ( ! img_id_value ) {

		img_id_value = jQuery( t ).attr( 'placeholder' );
	}

	if ( ! img_id_value || ! img_lib_css_id || ! preview_css_id ) {	// Nothing to do.

		return;
	}

	var preview_container = jQuery( '#' + preview_css_id );
	var img_lib_value     = jQuery( '#' + img_lib_css_id ).val();

	preview_container.empty();	// Remove any old image preview.

	if ( 'wp' === img_lib_value && jQuery.isNumeric( img_id_value ) ) {

		if ( img_url_css_id ) {

			jQuery( '#' + img_url_css_id ).val( '' ).change();
			jQuery( '#' + img_url_css_id ).prop( 'disabled', true );
		}

		var q = new wp.media.model.Attachment.get( img_id_value );

		q.fetch( { success:function( ret ) {

			if ( typeof ret.attributes.sizes.thumbnail.url !== 'undefined' ) {

				var thumbnail = ret.attributes.sizes.thumbnail;

				if ( preview_container ) {

					var img_html = '<img src="' + thumbnail.url + '"';

					if ( thumbnail.width ) {

						img_html += ' width="' + thumbnail.width + '"';
					}

					if ( thumbnail.height ) {

						img_html += ' height="' + thumbnail.height + '"';
					}

					img_html += '/>';

					preview_container.html( img_html );
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

		var img_id_value = jQuery( t ).attr( 'data-wp-img-id' );

		if ( img_id_value && jQuery.isNumeric( img_id_value ) ) {

			var selection  = window.sucom_image_upload_media.state().get( 'selection' );
			var attachment = wp.media.attachment( img_id_value );

			selection.add( attachment ? [ attachment ] : [] );
		}

		jQuery( '.media-modal', t.el ).find( '#media-search-input' ).focus();
	} );

	window.sucom_image_upload_media.on( 'select', function() {

		var attachment     = window.sucom_image_upload_media.state().get( 'selection' ).first().toJSON();
		var img_id_css_id  = jQuery( t ).attr( 'data-img-id-css-id' );
		var img_lib_css_id = jQuery( t ).attr( 'data-img-lib-css-id' );

		jQuery( t ).attr( 'data-wp-img-id', attachment.id );

		/**
		 * Update the media library before the image id, but do not trigger a change yet.
		 */
		if ( img_lib_css_id ) {

			jQuery( '#' + img_lib_css_id ).val( 'wp' );
		}

		/**
		 * Update the image id and trigger a change event.
		 */
		if ( img_id_css_id ) {

			jQuery( '#' + img_id_css_id ).val( attachment.id ).change();
		}
	} );

	window.sucom_image_upload_media.open();
};
