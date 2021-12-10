/**
 * Common library for admin pages.
 *
 * Don't forget to update the wp_register_script() arguments for the 'sucom-admin-page' script when updating this version number.
 *
 * Version: 20211210-01
 */

/**
 * Update block-editor metaboxes.
 */
function sucomBlockPostbox( pluginId, adminPageL10n ) {

	if ( 'undefined' === typeof wp.data ) return;	// Just in case.

	var post_id = wp.data.select( 'core/editor' ).getCurrentPostId;
	var cfg     = window[ adminPageL10n ];

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

		/**
		 * Sanitize the ajax action filter name.
		 */
		ajax_action_update_postbox = ajax_action_update_postbox.toLowerCase();
		ajax_action_update_postbox = ajax_action_update_postbox.replace( /[:\/\-\. ]+/g, '_' );
		ajax_action_update_postbox = ajax_action_update_postbox.replace( /[^a-z0-9_\-]/g, '' );

		var ajaxData = {
			action: ajax_action_update_postbox,
			post_id: post_id,
			_ajax_nonce: cfg[ '_ajax_nonce' ],
		}

		jQuery.post( ajaxurl, ajaxData, function( html ) {

			/**
			 * The returned HTML includes javascript to call the sucomInitMetabox() function.
			 */
			if ( html ) {

				jQuery( '#' + postbox_id + '.postbox div.inside' ).replaceWith( '<div class="inside">' + html + '</div>' );
			}
		} );
	}
}

/**
 * Create block-editor notices first, excluding any toolbar notice types, then update toolbar notices.
 */
function sucomBlockNotices( pluginId, adminPageL10n ) {

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
		_exclude_types: cfg[ '_tb_types_showing' ],	// Exclude the toolbar notice types.
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

					/**
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

	} else if ( ! cfg[ '_tb_types_showing' ] ) {	// No toolbar notice types to get.

		return;
	}

	var menuId    = '#wp-admin-bar-' + pluginId + '-toolbar-notices';
	var subMenuId = '#wp-admin-bar-' + pluginId + '-toolbar-notices-container';
	var counterId = '#' + pluginId + '-toolbar-notices-count';

	var ajaxData = {
		action: cfg[ '_ajax_actions' ][ 'get_notices_json' ],
		context: 'toolbar_notices',
		_ajax_nonce: cfg[ '_ajax_nonce' ],
		_notice_types: cfg[ '_tb_types_showing' ],
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

				noticeCount  = noticeTypeCount[ 'err' ];
				noticeStatus = 'error';

			} else if ( noticeTypeCount[ 'warn' ] ) {

				noticeCount  = noticeTypeCount[ 'warn' ];
				noticeStatus = 'warning';

			} else if ( noticeTypeCount[ 'inf' ] ) {

				noticeCount  = noticeTypeCount[ 'inf' ];
				noticeStatus = 'info';

			} else if ( noticeTypeCount[ 'upd' ] ) {

				noticeCount  = noticeTypeCount[ 'upd' ];
				noticeStatus = 'success';
			}

			jQuery( menuId ).addClass( 'toolbar-notices-' + noticeStatus );

			/**
			 * countMsgsTransl is an array with one or more noticeStatus keys and their translated message.
			 *
			 * Array(
			 *	'error' => 'There are {0} important error messages under the notification icon.',
			 * );
			 */
			if ( countMsgsTransl[ noticeStatus ] ) {

				if ( 'undefined' !== typeof wp.data ) {

					var createNotice  = wp.data.dispatch( 'core/notices' ).createNotice;
					var removeNotice  = wp.data.dispatch( 'core/notices' ).removeNotice;
					var noticeKey     = 'notice-count-msg-' + noticeStatus;
					var noticeMessage = countMsgsTransl[ noticeStatus ].formatUnicorn( noticeCount );

					var noticeOptions = {
						id: noticeKey,
						type: 'snackbar',
						spokenMessage: noticeMessage,
					};

					removeNotice( noticeKey );

					noticeObj = createNotice( noticeStatus, noticeMessage, noticeOptions );
				}
			}
		}
	} );
}

function sucomToolbarValidators( pluginId, adminPageL10n ) {

	var post_id = wp.data.select( 'core/editor' ).getCurrentPostId;
	var cfg     = window[ adminPageL10n ];

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
		post_id: post_id,
		_ajax_nonce: cfg[ '_ajax_nonce' ],
	}

	jQuery.post( ajaxurl, ajaxData, function( html ) {

		/**
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

		/**
		 * Check for input field value first, then container content.
		 */
		var elemVal = elem.value;

		if ( 'undefined' === elemVal ) {

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

/**
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

/**
 * Hooked to .focus() and .keyup() by SucomForm->get_textlen_script().
 */
function sucomTextLen( containerId, adminPageL10n ) {

	var cfg = window[ adminPageL10n ];

	if ( 'undefined' === typeof cfg ) {	// Just in case.

		cfg = {
			'_min_len_transl': '{0} of {1} characters minimum',
			'_req_len_transl': '{0} of {1} characters required',
			'_max_len_transl': '{0} of {1} characters maximum',
			'_len_transl'    : '{0} characters',
		}
	}

	var text_val = sucomTextLenClean( jQuery( '#' + containerId ).val() );
	var text_len = text_val.length;
	var min_len  = Number( jQuery( '#' + containerId ).attr( 'minLength' ) );
	var warn_len = Number( jQuery( '#' + containerId ).attr( 'warnLength' ) );
	var max_len  = Number( jQuery( '#' + containerId ).attr( 'maxLength' ) );

	/**
	 * If we have a max length, make sure it's larger than the minimum.
	 */
	if ( min_len && max_len && max_len < min_len ) {

		max_len = min_len;
	}

	var len_span_html = sucomTextLenSpan( text_len, max_len, warn_len, min_len );
	var limit_html    = max_len;

	if ( min_len ) {

		if ( ! max_len ) {

			limit_html = min_len;

			msg_transl = cfg[ '_min_len_transl' ];

		} else {

			if ( max_len > min_len ) {

				limit_html = String( min_len ) + '-' + String( max_len );
			}

			msg_transl = cfg[ '_req_len_transl' ];
		}

	} else if ( max_len ) {

		msg_transl = cfg[ '_max_len_transl' ];

	} else {

		msg_transl = cfg[ '_len_transl' ];
	}

	/**
	 * {0} = len_span_html
	 * {1} = limit_html
	 */
	jQuery( '#' + containerId + '-text-length-message' ).html( '<div class="text_len_msg">' + msg_transl.formatUnicorn( len_span_html, limit_html ) + '</div>' )
}

/**
 * Hooked to .blur() by SucomForm->get_textlen_script().
 */
function sucomTextLenReset( containerId ) {

	jQuery( '#' + containerId + '-text-length-message' ).html( '' )
}

function sucomTextLenSpan( text_len, max_len, warn_len, min_len ) {

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

/**
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
