jQuery(document).ready(function($){
	$('input.button.logout').click(function(){
		var data = {
			action : "pplb_logout"
		}
		
		$.post( pplb_ajax.ajaxurl, data, function( response ) {
    		if ( response.log == 1 && typeof console == "object" && typeof console.log != 'undefined' ) {
            	console.log( response );
    		}
			if( response.status == 0 ){
				// nothing hanppens
			}
			else{
				alert( response.message );
			}
			document.location.href = document.location.href;
		}, "JSON" );
	});
});