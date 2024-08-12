
const wpssoBlockEditor = ( function(){

	const pluginId          = 'wpsso';
	const adminPageL10n     = 'wpssoAdminPageL10n';
	const postId            = wp.data.select( 'core/editor' ).getCurrentPostId;
	const isSavingMetaBoxes = wp.data.select( 'core/edit-post' ).isSavingMetaBoxes;

	var wasSavingMb = false;

	sucomBlockEditorNotices( pluginId, adminPageL10n );	// Update the notices on startup.

	return {

		refreshPostbox: function(){	// Called by wp.data.subscribe().

			var isSavingMb = isSavingMetaBoxes();	// Check if saving metaboxes.

			if ( isSavingMb && ! wasSavingMb ) {	// Check if starting to save metaboxes.

				sucomEditorUnchanged( pluginId, adminPageL10n );	// Disable unchanged fields in 'table.sucom-settings'.
			}

			if ( wasSavingMb && ! isSavingMb ) {	// Check if done saving metaboxes.

				sucomEditorPostbox( pluginId, adminPageL10n, postId );	// Refresh our metabox(es).

				sucomBlockEditorNotices( pluginId, adminPageL10n );	// Refresh the block editor and toolbar notices.

				sucomToolbarValidators( pluginId, adminPageL10n, postId );	// Refresh the toolbar validators.
			}

			wasSavingMb = isSavingMb;
		},
	}
})();

wp.data.subscribe( wpssoBlockEditor.refreshPostbox );

