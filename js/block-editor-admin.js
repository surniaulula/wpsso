
var getCurrentPostId   = wp.data.select( 'core/editor' ).getCurrentPostId;
var isSavingMetaBoxes  = wp.data.select( 'core/edit-post' ).isSavingMetaBoxes;
var createNotice       = wp.data.dispatch( 'core/notices' ).createNotice;
var removeNotice       = wp.data.dispatch( 'core/notices' ).removeNotice;
var createElement      = wp.element.createElement;
var RawHTML            = wp.element.RawHTML;
var wasSavingContainer = false;

jQuery( document ).ready( function() {

	wpssoCreateNotices();
} );

wp.data.subscribe( function() {

	var isSavingContainer = isSavingMetaBoxes();

	if ( wasSavingContainer ) {			// Last check was saving post meta.

		if ( ! isSavingContainer ) {		// Saving the post meta is done.

			sucomUpdateContainers();	// Update metaboxes.

			wpssoCreateNotices();		// Get any new notices.
		}
	}

	wasSavingContainer = isSavingContainer;
} );

/**
 * Create block-editor notices first, excluding any toolbar notice types, then update the toolbar notices.
 */
function wpssoCreateNotices() {

	var ajaxData = {
		action: sucomAdminPageL10n._ajax_actions[ 'get_notices_json' ],
		context: 'block_editor',
		_ajax_nonce: sucomAdminPageL10n._ajax_nonce,
		_exclude_types: sucomAdminPageL10n._tb_notices,	// Exclude the toolbar notice types.
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
		 *			[msg_html]     => <div class="wpsso-notice notice notice-alt notice-error error" id="err-post-1086-notice-missing-og-image" style="display:block;"><div class="notice-label">SSO Notice</div><div class="notice-message">Text.</div></div>
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
					jQuery( 'div.wpsso-notice' ).parents( 'div.components-notice' ).removeClass( 'components-notice' );
				}
			} );
		} );

		sucomUpdateToolbar( 'wpsso' );
	} );
}
