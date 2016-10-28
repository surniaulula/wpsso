jQuery(document).ready(function(){
	jQuery(".datepicker").datepicker({ dateFormat:"yy-mm-dd" });
});
function sucomTextLen( id ) {
	var text = jQuery.trim( sucomClean( jQuery( "#"+id ).val() ) );
	var text_len = text.length;
	var warn_chars = jQuery( "#"+id ).attr( "warnLength" );
	var max_chars = jQuery( "#"+id ).attr( "maxLength" );
	var msg = '<div class="max_chars">'+sucomLenSpan( text_len, max_chars, warn_chars )+" of "+max_chars+" characters maximum</div>";
	jQuery( "#"+id+"-lenMsg" ).html( msg )
}
function sucomLenSpan( text_len, max_chars, warn_chars ) {
	if ( warn_chars == undefined )
		warn_chars = max_chars - 20;
	var css_class = "";
	if ( text_len >= warn_chars ) {
		if ( text_len >= ( max_chars - 5 ) ) {
			css_class = "bad";
		} else {
			css_class = "warn";
		}
	} else {
		css_class = "good";
	}
	return '<span class="'+css_class+'">'+text_len+"</span>";
}
function sucomClean( val ) {
	if ( val == "" || val == undefined ) {
		return "";
	}
	try { 
		val = val.replace( /<\/?[^>]+>/g, "" ); 
		val = val.replace( /\[(.+?)\](.+?\[\/\\1\])?/, "" )
	}
	catch( err ) {
	}
	return val;
}
function sucomTabs( metabox, tab, scroll_to ) {

	metabox = typeof metabox !== "undefined" ? metabox : "_default";
	tab = typeof tab !== "undefined" ? tab : "_default";

	var default_tab = "sucom-tabset"+metabox+"-tab"+tab;
	var hash = window.location.hash;

	if ( hash == "" ) {
		hash = default_tab;
	} else {
		if ( hash.search( "sucom-tabset"+metabox+"-tab_" ) == -1 )
			hash = default_tab;
		else hash = hash.replace( "#", "" );
	}
	jQuery( "."+hash ).addClass( "active" );
	jQuery( "."+hash+"-msg" ).addClass( "active" );
	jQuery( "a.sucom-tablink"+metabox ).click(
		function() {
			jQuery( ".sucom-metabox-tabs"+metabox+" li" ).removeClass( "active" );
			jQuery( ".sucom-tabset"+metabox ).removeClass( "active" );
			jQuery( ".sucom-tabset"+metabox+"-msg" ).removeClass( "active" );
			var href = jQuery( this ).attr( "href" ).replace( "#", "" );
			jQuery( "."+href ).addClass( "active" );
			jQuery( "."+href+"-msg" ).addClass( "active" );
			jQuery( this ).parent().addClass( "active" );
		}
	);
	jQuery(".sucom-metabox-tabs").show();

	if ( scroll_to ) {
		var adjust_height = jQuery( "div#wpadminbar" ).height();
		var scroll_offset = jQuery( scroll_to ).offset().top + adjust_height;
		jQuery( "html, body" ).stop( true, true ).animate( { scrollTop:scroll_offset }, "fast" );
	}
}
function sucomViewUnhideRows( class_href_key, show_opts_key, hide_in_pre ) {
	hide_in_pre = typeof hide_in_pre !== "undefined" ? hide_in_pre : "hide_in";
	jQuery( "div."+class_href_key ).find( "."+hide_in_pre+"_"+show_opts_key ).show();
	jQuery( "."+class_href_key+"-msg" ).hide();
}
function sucomSelectChangeUnhideRows( hide_row, show_row ) {
	show_row = show_row.replace( /[:\/\-\.]+/g, '_' );
	jQuery( "tr."+hide_row ).hide();
	jQuery( "tr."+hide_row+"_"+show_row ).show();
}
function sucomSelectChangeRedirect( name, value, redirect_url ) {
	url = redirect_url + jQuery( location ).attr( "hash" );
        window.location = url.replace( "%%" + name + "%%", value );
}
function sucomCopyInputId( id ) { 
	jQuery( 'input#'+id ).select();
	document.execCommand ( "Copy", false, null );
	jQuery( 'input#'+id ).blur();
	return false;
}
function sucomToggle( id ) { 
	jQuery( "#"+id ).toggle();
	return false;
}
