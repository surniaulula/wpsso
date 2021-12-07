
const wpssoBlockEditor = ( function(){

	const pluginId          = 'wpsso';
	const adminPageL10n     = 'wpssoAdminPageL10n';
	const isSavingMetaBoxes = wp.data.select( 'core/edit-post' ).isSavingMetaBoxes;

	var wasSavingMb = false;

	sucomBlockNotices( pluginId, adminPageL10n );	// Update the notices on startup.

	return {

		refreshPostbox: function(){	// Called by wp.data.subscribe().

			var isSavingMb = isSavingMetaBoxes();	// Check if we're saving metaboxes.

			if ( wasSavingMb && ! isSavingMb ) {	// Check if done saving metaboxes.

				sucomBlockPostbox( pluginId, adminPageL10n );	// Refresh our metabox(es).

				sucomBlockNotices( pluginId, adminPageL10n );	// Refresh the block editor and toolbar notices.

				sucomToolbarValidators( pluginId, adminPageL10n );	// Refresh the toolbar validators.
			}

			wasSavingMb = isSavingMb;
		},
	}
})();

wp.data.subscribe( wpssoBlockEditor.refreshPostbox );

