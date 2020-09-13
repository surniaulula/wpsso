
jQuery( document ).bind( 'sucom_init_metabox', function( event, container_id, doing_ajax ) {

	sucomInitToolTips( container_id, doing_ajax );
} );

function sucomInitToolTips( container_id, doing_ajax ) {

	var table_id = 'table.sucom-settings';

	if ( typeof container_id !== 'undefined' && container_id ) {

		table_id = container_id + ' ' + table_id;
	}

	jQuery( table_id + ' .sucom-tooltip' ).qtip( {
		content:{
			attr:'data-help',
		},
		position:{
			my:'bottom left',
			at:'top center',
			adjust:{
				x:5,
				y:-5,
			},
		},
		show:{
			when:{
				event:'mouseover',
			},
		},
		hide:{
			fixed:true,
			delay:500,
			when:{
				event:'mouseleave',
			},
		},
		style:{
			tip:{
				corner:true,
			},
			classes:'sucom-qtip qtip-lime-green qtip-shadow',
			width:500,
		},
	} );

	jQuery( ".sucom-sidebox .sucom-tooltip" ).qtip( {
		content:{
			attr:'data-help',
		},
		position:{
			my:'bottom right',
			at:'top center',
			adjust:{
				x:-5,
				y:-5,
			},
		},
		show:{
			when:{
				event:'mouseover',
			},
		},
		hide:{
			fixed:true,
			delay:500,
			when:{
				event:'mouseleave',
			},
		},
		style:{
			tip:{
				corner:true,
			},
			classes:'sucom-qtip qtip-lime-green qtip-shadow',
			width:300,
		},
	} );
}
