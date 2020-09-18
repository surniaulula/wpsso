
function sucomCopyById( css_id ) {
	
	try {

		var copyClipboardMsg = sucomAdminPageL10n._copy_clipboard_msg;

		var elem = document.getElementById( css_id );

		/**
		 * Check for input field value first, then container content.
		 */
		var elemVal = elem.value;

		if ( undefined === elemVal ) {

			elemVal = elem.textContent;
		}

		var target = document.createElement( 'textarea' );

		target.id             = 'copy_target_' + css_id;
		target.style.position = 'absolute';
		target.style.top      = '0';
		target.style.left     = '-9999px';
	
		document.body.appendChild( target );

		target.textContent = elemVal;

		target.focus();
	
		target.setSelectionRange( 0, target.value.length );

		document.execCommand( 'copy' );

		target.textContent = '';

		alert( copyClipboardMsg );

	} catch ( err ) {

		alert( err );
	}

	return false;
}

/**
 * Convert some HTML tags to spaces first, strip everything else, then convert multiple spaces to a single space.
 */
function sucomStripHtml( html ) {

	html = html.replace( /<(p|pre|ul|li|br\/?)( [^<>]*>|>)/gi, ' ' );

	html = html.replace( /<[^<>]*>/gi, '' );

	html = html.replace( /\s\s+/gi, ' ' );

	return html;
}

function sucomUpdateToolbar( lca ) {

	/**
	 * Just in case - no use getting notices if there's nothing to get.
	 */
	if ( ! sucomAdminPageL10n._tb_notices ) {

		return;
	}

	var menuId    = '#wp-admin-bar-' + lca + '-toolbar-notices';
	var subMenuId = '#wp-admin-bar-' + lca + '-toolbar-notices-container';
	var counterId = '#' + lca + '-toolbar-notices-count';

	var ajaxData = {
		action: sucomAdminPageL10n._ajax_actions[ 'get_notices_json' ],
		context: 'toolbar_notices',
		_ajax_nonce: sucomAdminPageL10n._ajax_nonce,
		_notice_types: sucomAdminPageL10n._tb_notices,
	}

	jQuery.getJSON( ajaxurl, ajaxData, function( data ) {

		var noticeHtml       = '';
		var noticeText       = '';
		var noticeTextId     = sucomAdminPageL10n._notice_text_id;
		var noticeStatus     = '';
		var noticeTotalCount = 0;
		var noticeTypeCount  = {};
		var noNoticesHtml    = sucomAdminPageL10n._no_notices_html;
		var copyNoticesHtml  = sucomAdminPageL10n._copy_notices_html;

		jQuery.each( data, function( noticeType ) {

			jQuery.each( data[ noticeType ], function( noticeKey ) {

				if ( ! data[ noticeType ][ noticeKey ][ 'hidden' ] ) {

					noticeHtml += data[ noticeType ][ noticeKey ][ 'msg_html' ];

					noticeTypeCount[ noticeType ] = ++noticeTypeCount[ noticeType ] || 1;

					noticeTotalCount++;
				}

				noticeText += "\n";
				noticeText += '[' + noticeType + '] ';
				noticeText += data[ noticeType ][ noticeKey ][ 'notice_label' ];
				noticeText += ': ';
				noticeText += sucomStripHtml( data[ noticeType ][ noticeKey ][ 'msg_text' ] );
				noticeText += "\n";
			} );
		} );

		/**
		 * Cleanup any pre-existing notice classes.
		 */
		jQuery( 'body.wp-admin' ).removeClass( 'has-toolbar-notices' );
		jQuery( menuId ).removeClass( 'has-toolbar-notices' );
		jQuery( menuId ).removeClass( 'toolbar-notices-error' );
		jQuery( menuId ).removeClass( 'toolbar-notices-warning' );
		jQuery( menuId ).removeClass( 'toolbar-notices-info' );
		jQuery( menuId ).removeClass( 'toolbar-notices-success' );

		if ( noticeHtml ) {

			noticeHtml = '<div style="display:none;" id="' + noticeTextId + '">' + noticeText + '</div>' + copyNoticesHtml + noticeHtml;

			jQuery( subMenuId ).html( noticeHtml );

			jQuery( menuId ).addClass( 'has-toolbar-notices' );

			jQuery( 'body.wp-admin' ).addClass( 'has-toolbar-notices' );

		} else {

			jQuery( subMenuId ).html( noNoticesHtml );
		}

		jQuery( counterId ).html( noticeTotalCount );

		if ( noticeTotalCount ) {

			var noticeStatus = '';

			if ( noticeTypeCount[ 'err' ] ) {

				noticeStatus = 'error';

			} else if ( noticeTypeCount[ 'warn' ] ) {

				noticeStatus = 'warning';

			} else if ( noticeTypeCount[ 'inf' ] ) {

				noticeStatus = 'info';

			} else if ( noticeTypeCount[ 'upd' ] ) {

				noticeStatus = 'success';
			}

			jQuery( menuId ).addClass( 'toolbar-notices-' + noticeStatus );
		}
	} );
}

