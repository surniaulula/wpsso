
var isSavingMetaBoxes = wp.data.select( 'core/edit-post' ).isSavingMetaBoxes;
var wpssoWasSaving    = false;

wp.data.subscribe( function(){

	var pluginId      = 'wpsso';
	var adminPageL10n = 'wpssoAdminPageL10n';
	var wpssoIsSaving = isSavingMetaBoxes();

	if ( wpssoWasSaving ) {	// Last check was saving post meta.

		if ( ! wpssoIsSaving ) {	// Saving the post meta is done.

			sucomBlockPostbox( pluginId, adminPageL10n );

			sucomBlockNotices( pluginId, adminPageL10n );
		}
	}

	wpssoWasSaving = wpssoIsSaving;
});

jQuery( function(){

	var pluginId      = 'wpsso';
	var adminPageL10n = 'wpssoAdminPageL10n';

	sucomBlockNotices( pluginId, adminPageL10n );
});
