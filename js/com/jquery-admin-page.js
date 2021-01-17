
/**
 * Convert some HTML tags to spaces first, strip everything else, then convert multiple spaces to a single space.
 */
function sucomStripHtml( html ) {

	html = html.replace( /<(p|pre|ul|li|br\/?)( [^<>]*>|>)/gi, ' ' );

	html = html.replace( /<[^<>]*>/gi, '' );

	html = html.replace( /\s\s+/gi, ' ' );

	return html;
}

function sucomCopyById( cssId, cfgName ) {

	if ( ! cssId ) {	// Just in case.

		return false;
	}

	if ( ! cfgName ) {

		cfgName = 'sucomAdminPageL10n';
	}

	var cfg = window[ cfgName ];

	try {

		var copyClipboardMsg = cfg._copy_clipboard_msg;

		var elem = document.getElementById( cssId );

		/**
		 * Check for input field value first, then container content.
		 */
		var elemVal = elem.value;

		if ( undefined === elemVal ) {

			elemVal = elem.textContent;
		}

		var target = document.createElement( 'textarea' );

		target.id             = 'copy_target_' + cssId;
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

function sucomUpdateContainers( pluginId, cfgName ) {

	if ( ! pluginId ) {

		pluginId = 'sucom';	// Lowercase acronym.
	}

	if ( ! cfgName ) {

		cfgName = 'sucomAdminPageL10n';
	}

	var cfg = window[ cfgName ];

	if ( jQuery.isArray( cfg._mb_container_ids ) ) {

		var post_id = getCurrentPostId();

		for ( var container_key in cfg._mb_container_ids ) {

			var container_id = cfg._mb_container_ids[ container_key ];

			var ajax_action_update_container = 'get_container_id_' + container_id;

			/**
			 * Just in case - sanitize the WP ajax action filter name.
			 */
			ajax_action_update_container = ajax_action_update_container.toLowerCase();
			ajax_action_update_container = ajax_action_update_container.replace( /[:\/\-\. ]+/g, '_' );
			ajax_action_update_container = ajax_action_update_container.replace( /[^a-z0-9_\-]/g, '' );

			var ajaxData = {
				action: ajax_action_update_container,
				post_id: post_id,
				_ajax_nonce: cfg._ajax_nonce,
			}

			jQuery.post( ajaxurl, ajaxData, function( html ) {

				/**
				 * The returned HTML includes javascript to call the sucomInitMetabox() function.
				 */
				if ( html ) {

					jQuery( '#' + container_id ).replaceWith( html );
				}
			} );
		}
	}
}

/**
 * Create block-editor notices first, excluding any toolbar notice types, then update toolbar notices.
 */
function sucomBlockNotices( pluginId, cfgName ) {

	if ( ! pluginId ) {

		pluginId = 'sucom';	// Lowercase acronym.
	}

	if ( ! cfgName ) {

		cfgName = 'sucomAdminPageL10n';
	}

	var cfg = window[ cfgName ];

	var ajaxData = {
		action: cfg._ajax_actions[ 'get_notices_json' ],
		context: 'block_editor',
		_ajax_nonce: cfg._ajax_nonce,
		_exclude_types: cfg._tb_types_showing,	// Exclude the toolbar notice types.
	}

	jQuery.getJSON( ajaxurl, ajaxData, function( data ) {

		/**
		 * Example data:
		 *
		 * Array (
		 *	[err] => Array (
		 *		[post-1086-notice-missing-og-image] => Array (
		 *			[notice_key]   => post-1086-notice-missing-og-image
		 *			[dismiss_time] =>
		 *			[dismiss_diff] =>
		 *			[msg_text]     => <p>Text.</p>
		 *			[msg_spoken]   => Text.
		 *			[msg_html]     => <div class="sucom-notice notice notice-alt notice-error error" id="err-post-1086-notice-missing-og-image" style="display:block;"><div class="notice-label">SSO Notice</div><div class="notice-message">Text.</div></div>
		 *		)
		 *	)
		 * )
		 */
		jQuery.each( data, function( noticeType ) {

			jQuery.each( data[ noticeType ], function( noticeKey ) {

				var noticeObj     = false;
				var noticeStatus  = false;
				var noticeSpoken  = data[ noticeType ][ noticeKey ][ 'msg_spoken' ];

				/**
				 * The current version of the block editor casts the notice message as a string, so we
				 * cannot give createNotice() an html message or RawHTML element.
				 *
				 * var noticeHtml    = data[ noticeType ][ noticeKey ][ 'msg_html' ];
				 * var noticeElement = createElement( RawHTML, {}, noticeHtml );
				 */

				var noticeOptions = {
					id: noticeKey,
					spokenMessage: data[ noticeType ][ noticeKey ][ 'msg_spoken' ],
					isDismissible: data[ noticeType ][ noticeKey ][ 'dismiss_time' ] ? false : true,
				};

				if ( noticeType == 'err' ) {

					noticeStatus = 'error';

				} else if ( noticeType == 'warn' ) {

					noticeStatus = 'warning';

				} else if ( noticeType == 'inf' ) {

					noticeStatus = 'info';

				} else if ( noticeType == 'upd' ) {

					noticeStatus = 'success';
				}

				if ( noticeStatus ) {

					removeNotice( noticeKey );

					/**
					 * The current version of the block editor casts the notice message as a string, so we
					 * cannot give createNotice() an html message or RawHTML element. Until such time as the
					 * block editor can handle an html notice message, we must give it the "spoken" notice
					 * message string instead, which is a plain text string.
					 *
					 * noticeObj = createNotice( noticeStatus, noticeElement, noticeOptions );
					 */
					noticeObj = createNotice( noticeStatus, noticeSpoken, noticeOptions );

					/**
					 * Remove the notices class to fix notice-in-notice padding issues.
					 */
					jQuery( 'div.' + pluginId + '-notice' ).parents( 'div.components-notice' ).removeClass( 'components-notice' );
				}
			} );
		} );

		sucomToolbarNotices( pluginId, cfgName );
	} );
}

function sucomToolbarNotices( pluginId, cfgName ) {

	if ( ! pluginId ) {

		pluginId = 'sucom';
	}

	if ( ! cfgName ) {

		cfgName = 'sucomAdminPageL10n';
	}

	var cfg = window[ cfgName ];

	/**
	 * Just in case - no use getting notices if there's nothing to get.
	 */
	if ( ! cfg._tb_types_showing ) {

		return;
	}

	var menuId    = '#wp-admin-bar-' + pluginId + '-toolbar-notices';
	var subMenuId = '#wp-admin-bar-' + pluginId + '-toolbar-notices-container';
	var counterId = '#' + pluginId + '-toolbar-notices-count';

	var ajaxData = {
		action: cfg._ajax_actions[ 'get_notices_json' ],
		context: 'toolbar_notices',
		_ajax_nonce: cfg._ajax_nonce,
		_notice_types: cfg._tb_types_showing,
	}

	jQuery.getJSON( ajaxurl, ajaxData, function( data ) {

		var noticeHtml       = '';
		var noticeText       = '';
		var noticeTextId     = cfg._notice_text_id;
		var noticeStatus     = '';
		var noticeTotalCount = 0;
		var noticeTypeCount  = {};
		var noNoticesHtml    = cfg._no_notices_html;
		var copyNoticesHtml  = cfg._copy_notices_html;

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

			/**
			 * Add an "inline" class to prevent WordPress from moving the notices.
			 */
			noticeHtml = noticeHtml.replaceAll( ' notice-alt ', ' notice-alt inline ' );

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
