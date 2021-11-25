
var isSavingMetaBoxes = wp.data.select( 'core/edit-post' ).isSavingMetaBoxes;
var wpssoWasSavingMb  = false;

wp.data.subscribe( function(){

	var wpssoIsSavingMb = isSavingMetaBoxes();

	if ( wpssoWasSavingMb ) {	// Last check was saving post meta.

		if ( ! wpssoIsSavingMb ) {	// Saving the post meta is done.

			var pluginId      = 'wpsso';
			var adminPageL10n = 'wpssoAdminPageL10n';

			sucomBlockPostbox( pluginId, adminPageL10n );

			sucomBlockNotices( pluginId, adminPageL10n );
		}
	}

	wpssoWasSavingMb = wpssoIsSavingMb;
});

jQuery( function(){

	var pluginId      = 'wpsso';
	var adminPageL10n = 'wpssoAdminPageL10n';

	sucomBlockNotices( pluginId, adminPageL10n );
});
