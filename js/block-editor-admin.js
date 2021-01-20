
var getCurrentPostId   = wp.data.select( 'core/editor' ).getCurrentPostId;
var isSavingMetaBoxes  = wp.data.select( 'core/edit-post' ).isSavingMetaBoxes;
var createNotice       = wp.data.dispatch( 'core/notices' ).createNotice;
var removeNotice       = wp.data.dispatch( 'core/notices' ).removeNotice;
var createElement      = wp.element.createElement;
var RawHTML            = wp.element.RawHTML;
var wasSavingContainer = false;

wp.data.subscribe( function(){

	var pluginId          = 'wpsso';
	var cfgName           = 'sucomAdminPageL10n';
	var isSavingContainer = isSavingMetaBoxes();

	if ( wasSavingContainer ) {	// Last check was saving post meta.

		if ( ! isSavingContainer ) {	// Saving the post meta is done.

			sucomUpdateContainers( pluginId, cfgName );

			sucomBlockNotices( pluginId, cfgName );
		}
	}

	wasSavingContainer = isSavingContainer;
});

jQuery( function(){

	var pluginId = 'wpsso';
	var cfgName  = 'sucomAdminPageL10n';

	sucomBlockNotices( pluginId, cfgName );
});
