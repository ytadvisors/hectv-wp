jQuery(document).ready(function(){

	jQuery(window).resize(function(){

		displayActive();

	});

	if(window.location.hash) {
		var hash      = window.location.hash.substring(1);
		var direction = ( hash == "prev" ) ? "prev" : "next";
	}

	if( jQuery("ul#dates").find("li.today").length == 0 ){

		if( direction == "prev" ){

			var total = jQuery("ul#dates").find("li.date").length
			jQuery("ul#dates").find("li.date").eq(total-1).addClass("current");

		}else{

			jQuery("ul#dates").find("li.date").eq(0).addClass("current");

		}


	}else{

		jQuery("ul#dates").find("li.today").addClass("current");

	}

	displayActive();

	jQuery(document).keydown(function(e) {
	    switch(e.which) {

	        case 37: // left
	        	moveCalendar("prev");
	        break;

	        case 39: // right
	        	moveCalendar("next");
	        break;

	        default: return;

	    }
	    e.preventDefault(); // prevent the default action (scroll / move caret)
	});

	jQuery("ul#dates").find("li.date").click(function(){

		jQuery("ul#dates").find("li.date").removeClass("current");
		jQuery(this).addClass("current");

		displayActive();

	});

	jQuery("button#event-next").click(function(){

		moveCalendar("next");

	});

	jQuery("button#event-previous").click(function(){

		moveCalendar("prev");

	});

	function moveCalendar( direction ){

		if( direction == "next" ){

			if( !jQuery("ul#dates").find("li.current").is(":last-child") ){

				jQuery("ul#dates").find("li.current").removeClass("current").next().addClass("current");

			}else{

				window.location = jQuery("#events-calendar").data("next") + "#next";

			}

		}else if( direction == "prev" ){

			if( !jQuery("ul#dates").find("li.current").is(":first-child") ){

				jQuery("ul#dates").find("li.current").removeClass("current").prev().addClass("current");

			}else{

				window.location = jQuery("#events-calendar").data("prev") + "#prev" ;

			}

		}

		if( jQuery("ul#dates").find("li.current").hasClass("prev") || jQuery("ul#dates").find("li.current").hasClass("next") ){

			window.location = jQuery("ul#dates").find("li.current").data("link");

		}

		displayActive();

	}

	function displayActive(){

		var display = 7;

		if( jQuery(window).width() < 530 ){
			display = 4;
			jQuery("ul#dates").addClass("mini");
		}else{
			jQuery("ul#dates").removeClass("mini");
		}

		var pageNumber = Math.floor( jQuery("ul#dates").find("li.current").index() / display );

		jQuery("ul#dates").find("li").hide();

		switch( pageNumber ){

			case 0:

				jQuery("ul#dates").find("li").slice( ( 0 * display ), ( 1 * display ) ).show();

			break;

			case 1:

				jQuery("ul#dates").find("li").slice( ( 1 * display ), ( 2 * display ) ).show();

			break;

			case 2:

				jQuery("ul#dates").find("li").slice( ( 2 * display ), ( 3 * display ) ).show();

			break;

			case 3:

				jQuery("ul#dates").find("li").slice( ( 3 * display ), ( 4 * display ) ).show();

			break;

			case 4:

				jQuery("ul#dates").find("li").slice( ( 4 * display ), ( 5 * display ) ).show();

			break;

			case 5:

				jQuery("ul#dates").find("li").slice( ( 5 * display ), ( 6 * display ) ).show();

			break;

			case 6:

				jQuery("ul#dates").find("li").slice( ( 6 * display ), ( 7 * display ) ).show();

			break;

			case 7:

				jQuery("ul#dates").find("li").slice( ( 7 * display ), ( 8 * display ) ).show();

			break;

			case 8:

				jQuery("ul#dates").find("li").slice( ( 8 * display ), ( 9 * display ) ).show();

			break;

		}

		var query = jQuery("ul#dates").find("li.date.current").attr("rel");

		jQuery("ul#tv-listings").find("li.tv-post").removeClass("hide-border").hide();
		jQuery("ul#tv-listings").find("li.tv-post[data-date='" + query + "']").show();
		jQuery("ul#tv-listings").find("li.tv-post[data-date='" + query + "']").addClass("hide-border");

		if( jQuery("ul#tv-listings").find("li.tv-post:visible").length == 0 ) {

			jQuery("ul#tv-listings").find("li#no-results").show();

		}else{

			jQuery("ul#tv-listings").find("li#no-results").hide();

		}


	}

});
