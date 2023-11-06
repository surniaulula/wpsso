
jQuery( document ).bind( 'sucom_init_metabox', function( event, container_id, doing_ajax ) {

	sucomInitToolTips( container_id, doing_ajax );
} );

function sucomInitToolTips( container_id, doing_ajax ) {

	var table_id = 'table.sucom-settings';

	if ( 'undefined' !== typeof container_id && container_id ) {

		table_id = container_id + ' ' + table_id;
	}

	var tableTooltips = jQuery( 'body.rtl ' + table_id + ' .sucom-tooltip' );
	var qtipCorner    = 'bottom right';

	if ( ! tableTooltips.length ) {

		tableTooltips = jQuery( table_id + ' .sucom-tooltip' );
		qtipCorner = 'bottom left';
	}

	tableTooltips.qtip( {
		content:{
			attr:'data-help',
		},
		position:{
			my:qtipCorner,
			at:'top center',
			adjust:{ x:0, y:-5 },
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
}
