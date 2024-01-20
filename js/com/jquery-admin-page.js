/*
 * jquery-admin-page.js
 *
 * Common library for admin pages.
 *
 * Version: 20230704
 *
 * Update the wp_register_script() arguments for the 'sucom-admin-page' script when updating this version number.
 */
function sucomEditorPostbox( pluginId, adminPageL10n, postId ) {

	if ( 'undefined' === typeof wp.data ) return;	// Just in case.

	if ( 'undefined' === typeof postId ) postId = wp.data.select( 'core/editor' ).getCurrentPostId;

	var cfg = window[ adminPageL10n ];

	if ( ! cfg[ '_ajax_nonce' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_nonce' );

		return;

	} else if ( cfg[ '_metabox_postbox_ids' ] ) {	// Backwards compatibility.

		if ( 'undefined' === typeof cfg[ '_ajax_actions' ] ) cfg[ '_ajax_actions' ] = {};

		if ( 'undefined' === typeof cfg[ '_ajax_actions' ][ 'metabox_postboxes' ] ) cfg[ '_ajax_actions' ][ 'metabox_postboxes' ] = {};

		for ( var postbox_key in cfg[ '_metabox_postbox_ids' ] ) {

			var postbox_id = cfg[ '_metabox_postbox_ids' ][ postbox_key ];

			var ajax_action_update_postbox = 'get_metabox_postbox_id_' + postbox_id + '_inside';

			cfg[ '_ajax_actions' ][ 'metabox_postboxes' ][ postbox_id ] = ajax_action_update_postbox;
		}

	} else if ( ! cfg[ '_ajax_actions' ][ 'metabox_postboxes' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_actions metabox_postboxes' );

		return;
	}

	for ( var postbox_id in cfg[ '_ajax_actions' ][ 'metabox_postboxes' ] ) {

		var ajax_action_update_postbox = cfg[ '_ajax_actions' ][ 'metabox_postboxes' ][ postbox_id ];

		/*
		 * Sanitize the ajax action filter name.
		 */
		ajax_action_update_postbox = sucomSanitizeHookname( ajax_action_update_postbox );

		var ajaxData = {
			action: ajax_action_update_postbox,
			post_id: postId,
			_ajax_nonce: cfg[ '_ajax_nonce' ],
		}

		jQuery.post( ajaxurl, ajaxData, function( html ) {

			/*
			 * The returned HTML includes javascript to call the sucomInitMetabox() function.
			 */
			if ( html ) {

				jQuery( '#' + postbox_id + '.postbox div.inside' ).replaceWith( '<div class="inside">' + html + '</div>' );
			}
		} );
	}
}

/*
 * Create block-editor notices first, excluding any toolbar notice types, then update toolbar notices.
 */
function sucomBlockEditorNotices( pluginId, adminPageL10n ) {

	if ( 'undefined' === typeof wp.data ) return;	// Just in case.

	var createNotice  = wp.data.dispatch( 'core/notices' ).createNotice;
	var removeNotice  = wp.data.dispatch( 'core/notices' ).removeNotice;
	var cfg           = window[ adminPageL10n ];

	if ( ! cfg[ '_ajax_nonce' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_nonce' );

		return;

	} else if ( ! cfg[ '_ajax_actions' ][ 'get_notices_json' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_actions get_notices_json' );

		return;
	}

	var ajaxData = {
		action: cfg[ '_ajax_actions' ][ 'get_notices_json' ],
		context: 'block_editor',
		_ajax_nonce: cfg[ '_ajax_nonce' ],
		_exclude_types: cfg[ '_toolbar_notice_types' ],	// Exclude the toolbar notice types.
	}

	jQuery.getJSON( ajaxurl, ajaxData, function( data ) {

		/*
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
		 *			[msg_html]     => <div class="sucom-notice notice notice-alt inline notice-error" id="err-post-1086-notice-missing-og-image" style="display:block;"><div class="notice-label">SSO Notice</div><div class="notice-message">Text.</div></div>
		 *		)
		 *	)
		 * )
		 */
		jQuery.each( data, function( noticeType ) {

			jQuery.each( data[ noticeType ], function( noticeKey ) {

				var noticeObj         = false;
				var noticeStatus      = false;
				var noticeSpoken      = data[ noticeType ][ noticeKey ][ 'msg_spoken' ];
				var noticeDismissable = data[ noticeType ][ noticeKey ][ 'dismiss_time' ] ? true : false;	// True, false, or seconds (0 or more).
				var noticeHidden      = data[ noticeType ][ noticeKey ][ 'hidden' ] ? true : false;

				if ( noticeType == 'err' ) {

					noticeStatus = 'error';

				} else if ( noticeType == 'warn' ) {

					noticeStatus = 'warning';

				} else if ( noticeType == 'inf' ) {

					noticeStatus      = 'info';
					noticeDismissable = true;	// Always make info messages dismissible.

				} else if ( noticeType == 'upd' ) {

					noticeStatus      = 'success';
					noticeDismissable = true;	// Always make success messages dismissible.
				}

				if ( noticeStatus && ! noticeHidden ) {

					var noticeOptions = {
						id: noticeKey,
						spokenMessage: noticeSpoken,
						isDismissible: noticeDismissable,
					};

					removeNotice( noticeKey );

					/*
					 * The current version of the block editor casts the notice message as a string, so we
					 * cannot give createNotice() an html message or RawHTML element. Until such time as the
					 * block editor can handle an html notice message, we must give it the "spoken" notice
					 * message instead, which is just a plain text string.
					 *
					 *	var createElement     = wp.element.createElement;
					 *	var RawHTML           = wp.element.RawHTML;
					 *	var noticeHtml        = data[ noticeType ][ noticeKey ][ 'msg_html' ];
					 *	var noticeHtmlElement = createElement( RawHTML, {}, noticeHtml );
					 *
					 *	noticeObj = createNotice( noticeStatus, noticeHtmlElement, noticeOptions );
					 *
					 * After creating the notice, remove the notices class to fix notice-in-notice padding
					 * issues for RawHTML elements.
					 *
					 *	jQuery( 'div.' + pluginId + '-notice' ).parents( 'div.components-notice' ).removeClass( 'components-notice' );
					 */
					noticeObj = createNotice( noticeStatus, noticeSpoken, noticeOptions );
				}
			} );
		} );

		sucomToolbarNotices( pluginId, adminPageL10n );
	} );
}

function sucomToolbarNotices( pluginId, adminPageL10n ) {

	var cfg = window[ adminPageL10n ];

	if ( ! cfg[ '_ajax_nonce' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_nonce' );

		return;

	} else if ( ! cfg[ '_ajax_actions' ][ 'get_notices_json' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_actions get_notices_json' );

		return;

	} else if ( ! cfg[ '_toolbar_notice_types' ] ) {	// No toolbar notice types to get.

		return;
	}

	var menuId    = '#wp-admin-bar-' + pluginId + '-toolbar-notices';
	var subMenuId = '#wp-admin-bar-' + pluginId + '-toolbar-notices-container';
	var counterId = '#' + pluginId + '-toolbar-notices-count';

	var menuItem = jQuery( menuId );

	var ajaxData = {
		action: cfg[ '_ajax_actions' ][ 'get_notices_json' ],
		context: 'toolbar_notices',
		_ajax_nonce: cfg[ '_ajax_nonce' ],
		_notice_types: cfg[ '_toolbar_notice_types' ],
	}

	jQuery.getJSON( ajaxurl, ajaxData, function( data ) {

		var noticeHtml       = '';
		var noticeText       = '';
		var noticeTextId     = cfg[ '_notice_text_id' ];
		var noticeStatus     = '';
		var noticeTotalCount = 0;
		var noticeTypeCount  = {};
		var noNoticesHtml    = cfg[ '_no_notices_html' ];
		var copyNoticesHtml  = cfg[ '_copy_notices_html' ];
		var countMsgsTransl  = cfg[ '_count_msgs_transl' ];

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

		/*
		 * Cleanup any pre-existing notice classes.
		 */
		jQuery( 'body.wp-admin' ).removeClass( 'has-toolbar-notices' );

		menuItem.removeClass( 'has-toolbar-notices' );
		menuItem.removeClass( 'toolbar-notices-error' );
		menuItem.removeClass( 'toolbar-notices-warning' );
		menuItem.removeClass( 'toolbar-notices-info' );
		menuItem.removeClass( 'toolbar-notices-success' );

		if ( noticeHtml ) {

			noticeHtml = '<div style="display:none;" id="' + noticeTextId + '">' + noticeText + '</div>' + copyNoticesHtml + noticeHtml;

			jQuery( subMenuId ).html( noticeHtml );

			jQuery( 'body.wp-admin' ).addClass( 'has-toolbar-notices' );

			menuItem.addClass( 'has-toolbar-notices' );

		} else {

			jQuery( subMenuId ).html( noNoticesHtml );
		}

		jQuery( counterId ).html( noticeTotalCount );

		if ( noticeTotalCount ) {

			var noticeStatus = '';
			var noticeTime   = -1;

			if ( noticeTypeCount[ 'err' ] )		noticeStatus = 'error';
			else if ( noticeTypeCount[ 'warn' ] )	noticeStatus = 'warning';
			else if ( noticeTypeCount[ 'inf' ] )	noticeStatus = 'info';
			else if ( noticeTypeCount[ 'upd' ] )	noticeStatus = 'success';

			if ( noticeTypeCount[ 'upd' ] )		noticeTime = cfg[ '_toolbar_notice_timeout' ][ 'upd' ] || -1;
			else if ( noticeTypeCount[ 'inf' ] )	noticeTime = cfg[ '_toolbar_notice_timeout' ][ 'inf' ] || -1;
			else if ( noticeTypeCount[ 'warn' ] )	noticeTime = cfg[ '_toolbar_notice_timeout' ][ 'warn' ] || -1;
			else if ( noticeTypeCount[ 'err' ] )	noticeTime = cfg[ '_toolbar_notice_timeout' ][ 'err' ] || -1;

			menuItem.addClass( 'toolbar-notices-' + noticeStatus );

			/*
			 * noticeTime = -1 to skip automatically showing notifications.
			 * noticeTime = 0 to automatically show notifications until click or hover.
			 * noticeTime = milliseconds to automatically show notifications.
			 */
			if ( noticeTime < 0 ) {

				// Nothing to do.

			} else if ( noticeTime > 0 ) {

				menuItem.addClass( 'show-timeout' );

				jQuery( document ).on( 'click', function( event ) {

					/*
					 * Remove the 'show-timeout' class if we're clicking anywhere outside the notices menu.
					 */
					if ( ! menuItem.is( event.target ) && ! menuItem.has( event.target ).length ) {

						menuItem.removeClass( 'show-timeout' );

						jQuery( document ).off( 'click', arguments.callee );
					}
				} );

				setTimeout( function() {

					menuItem.removeClass( 'show-timeout' );

				}, noticeTime );

			} else {

				jQuery( menuId ).addClass( 'hover' );

				jQuery( document ).on( 'click', function( event ) {

					/*
					 * Remove the 'hover' class if we're clicking anywhere outside the notices menu.
					 */
					if ( ! menuItem.is( event.target ) && ! menuItem.has( event.target ).length ) {

						menuItem.removeClass( 'hover' );

						jQuery( document ).off( 'click', arguments.callee );
					}
				} );
			}
		}
	} );
}

function sucomToolbarValidators( pluginId, adminPageL10n, postId ) {

	if ( 'undefined' === typeof postId ) postId = wp.data.select( 'core/editor' ).getCurrentPostId;

	var cfg = window[ adminPageL10n ];

	if ( ! cfg[ '_ajax_nonce' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_nonce' );

		return;

	} else if ( ! cfg[ '_ajax_actions' ][ 'get_validate_submenu' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_actions get_validate_submenu' );

		return;
	}

	var subMenuId = '#wp-admin-bar-' + pluginId + '-validate ul.ab-submenu';

	var ajaxData = {
		action: cfg[ '_ajax_actions' ][ 'get_validate_submenu' ],
		post_id: postId,
		_ajax_nonce: cfg[ '_ajax_nonce' ],
	}

	jQuery.post( ajaxurl, ajaxData, function( html ) {

		/*
		 * The returned HTML includes javascript to call the sucomInitMetabox() function.
		 */
		if ( html ) {

			jQuery( subMenuId ).replaceWith( html );
		}
	} );
}

function sucomDeleteMeta( metaType, objId, metaKey, adminPageL10n ) {

	var cfg = window[ adminPageL10n ];

	var delActionKey = 'delete_' + metaType + '_meta';

	if ( ! cfg[ '_ajax_nonce' ] ) {

		console.error( arguments.callee.name + ': missing _ajax_nonce' );

		return;

	} else if ( ! cfg[ '_ajax_actions' ][ delActionKey ] ) {

		console.error( arguments.callee.name + ': missing _ajax_actions ' + delActionKey );

		return;
	}

	var ajaxData = {
		action: cfg[ '_ajax_actions' ][ delActionKey ],
		obj_id: objId,
		meta_key: metaKey,
		_ajax_nonce: cfg[ '_ajax_nonce' ],
	}

	jQuery.post( ajaxurl, ajaxData, function( table_row_id ) {

		table_row_id = table_row_id.trim();	// Just in case.

		if ( table_row_id ) {

			jQuery( '#' + table_row_id ).hide();

		} else if ( cfg[ '_del_failed_transl' ] ) {

			var failed_msg = cfg[ '_del_failed_transl' ].formatUnicorn( objId, metaKey );

			alert( failed_msg );
		}
	} );
}

function sucomCopyById( cssId, adminPageL10n ) {

	var cfg = window[ adminPageL10n ];

	if ( ! cfg[ '_copy_clipboard_transl' ] ) {

		console.error( arguments.callee.name + ': missing _copy_clipboard_transl' );

		return;
	}

	try {

		var copyClipboardTransl = cfg[ '_copy_clipboard_transl' ];

		var elem = document.getElementById( cssId );

		/*
		 * Check for input field value first, then container content.
		 */
		var elemVal = elem.value;

		if ( 'undefined' === typeof elemVal ) {

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

		alert( copyClipboardTransl );

	} catch ( err ) {

		alert( err );
	}

	return false;	// Prevent the webpage from reloading.
}

/*
 * Convert some HTML tags to spaces first, strip everything else, then convert multiple spaces to a single space.
 */
function sucomStripHtml( html ) {

	html = html.replace( /<(p|pre|ul|li|br\/?)( [^<>]*>|>)/gi, ' ' );
	html = html.replace( /<[^<>]*>/gi, '' );
	html = html.replace( /\s\s+/gi, ' ' );

	return html;
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

/*
 * See SucomUtil::sanitize_css_id().
 */
function sucomSanitizeCssId( string ) {

	string = string.replace( /[^a-zA-Z0-9\-_]+/g, '-' );
	string = string.replace( /^-+|-+$/g, '' );

	return string;
}

/*
 * See SucomUtil::sanitize_hookname().
 */
function sucomSanitizeHookname( string ) {

	string = string.replace( /[#:\/\-\. \[\]]+/g, '_' );
	string = string.replace( /_+$/, '' );

	return sucomSanitizeKey( string );
}

/*
 * See SucomUtil::sanitize_key().
 */
function sucomSanitizeKey( string, allow_upper ) {

	if ( 'undefined' === typeof allow_upper ) allow_upper = false;

	if ( ! allow_upper ) string = string.toLowerCase();

	string = string.replace( /[^a-z0-9\-_:]/g, '' );

	return string;
}

/*
 * Hooked to .focus() and .keyup() by SucomForm->get_textlen_script().
 */
function sucomTextLen( containerId, adminPageL10n ) {

	var cfg = window[ adminPageL10n ];

	if ( 'undefined' === typeof cfg ) {

		cfg = {
			'_min_len_transl': '{0} of {1} characters minimum',
			'_req_len_transl': '{0} of {1} characters recommended',
			'_max_len_transl': '{0} of {1} characters maximum',
			'_len_transl'    : '{0} characters',
		}
	}

	var container = jQuery( '#' + containerId );
	var text_len  = sucomTextLenClean( container.val() ).length;
	var max_len   = Number( container.attr( 'maxLength' ) );
	var warn_len  = Number( container.attr( 'warnLength' ) );
	var min_len   = Number( container.attr( 'minLength' ) );

	if ( ! text_len ) {

		text_len = sucomTextLenClean( container.attr( 'placeholder' ) ).length;
	}

	/*
	 * Sanitize the max_len, warn_len, and min_len values.
	 */
	if ( ! max_len ) {

		max_len = 0;
	}

	if ( ! warn_len ) {

		if ( max_len ) {

			warn_len = 0.9 * max_len;	// Default to 90% of max_len.

		} else {

			warn_len = 0;
		}
	}

	if ( ! min_len ) {

		min_len = 0;
	}

	if ( min_len && max_len && max_len < min_len ) {

		max_len = min_len;
	}

	/*
	 * Select a text string and the character limit.
	 */
	var char_limit = max_len;	// Default value.

	if ( min_len ) {

		if ( ! max_len ) {

			char_limit = min_len;

			msg_transl = cfg[ '_min_len_transl' ];

		} else {

			if ( max_len > min_len ) {

				char_limit = String( min_len ) + '-' + String( max_len );
			}

			msg_transl = cfg[ '_req_len_transl' ];
		}

	} else if ( max_len ) {

		msg_transl = cfg[ '_max_len_transl' ];

	} else {

		msg_transl = cfg[ '_len_transl' ];
	}

	/*
	 * Select a CSS class.
	 */
	if ( max_len && text_len >= ( max_len - 5 ) ) {		// 5 characters from the end.

		css_class = 'maximum';

	} else if ( warn_len && text_len >= warn_len ) {	// Length is over the warning limit.

		css_class = 'long';

	} else if ( min_len && text_len < min_len ) {		// Length is less than the minimum.

		css_class = 'short';

	} else {

		css_class = 'good';
	}

	/*
	 * Create the container HTML.
	 */
	var container_html = '';

	if ( max_len ) {

		var pct_width = Math.round( text_len * 100 / max_len );

		container_html += '<div class="text-len-progress-bar">';
		container_html += '<div class="text-len-progress ' + css_class + '" style="width:' + pct_width + '%;"></div>';
		container_html += '</div>';
	}

	container_html += '<div class="text-len-status ' + css_class + '">';
	container_html += msg_transl.formatUnicorn( text_len, char_limit );
	container_html += '</div>';

	/*
	 * Add the container HTML.
	 */
	jQuery( '#' + containerId + '-text-len-wrapper' ).html( container_html );
}

/*
 * Hooked to .blur() by SucomForm->get_textlen_script().
 */
function sucomTextLenReset( containerId ) {

	jQuery( '#' + containerId + '-text-len-wrapper' ).html( '' )
}

function sucomTextLenClean( str ) {

	if ( 'undefined' === typeof str || ! str.length ) {

		return '';
	}

	try {

		str = str.replace( /<\/?[^>]+>/g, '' );
		str = str.replace( /\[(.+?)\](.+?\[\/\\1\])?/, '' )

	} catch( err ) {}

	return str.trim();
}

/*
 * String.prototype.formatUnicorn from Stack Overflow.
 *
 * Replace {0}, {1}, etc. in strings.
 */
String.prototype.formatUnicorn = function() {

	"use strict";

	var str = this.toString();

	if ( arguments.length ) {

		var t = typeof arguments[ 0 ];
		var key;
		var args = ( "string" === t || "number" === t ) ? Array.prototype.slice.call( arguments ) : arguments[ 0 ];

		for ( key in args ) {

			str = str.replace( new RegExp( "\\{" + key + "\\}", "gi" ), args[ key ] );
		}
	}

	return str;
}

/*
 * Deprecated on 2023/07/04.
 */
function sucomBlockPostbox( pluginId, adminPageL10n, postId ) {

	sucomEditorPostbox( pluginId, adminPageL10n, postId );
}

/*
 * Deprecated on 2023/07/04.
 */
function sucomBlockNotices( pluginId, adminPageL10n ) {

	sucomBlockEditorNotices( pluginId, adminPageL10n );
}
