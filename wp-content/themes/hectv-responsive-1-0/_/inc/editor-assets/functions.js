jQuery(document).ready(function(){

	"use strict";


	/* Segment Sort */

    jQuery( "ul#new-segment-list" ).sortable({
		items: '> li.segment',
		placeholder: "ui-state-highlight",
		stop: function( event, ui ){

// 			indexObjects();

		}

	});


	/* Prevent Scroll to Hash on Page Load */

	var scrolled = false;

	jQuery(window).scroll(function(){

		scrolled = true;

	});

	if ( window.location.hash && scrolled ) {

		jQuery(window).scrollTop( 0 );

	}

	/* Embed Toggle */

	jQuery("a#toggle_embed_state").click(function(e){

		jQuery("div#toggle_embed").toggleClass("active");

		e.preventDefault();
		return false;

	});
	
/*
	jQuery('input#video_embed').change(function(){
		
		alert('hi');
		
	});
*/

	/* Playlist Image Select */

	var file_frame;

	jQuery("button#add-video-thumb").click(function(event){

		event.preventDefault();

		if ( file_frame ) {

			file_frame.open();
			return;

		}

		file_frame = wp.media.frames.file_frame = wp.media({

			title: "Add Playlist Photo",
			button: {
				text: "Add Photo",
			},
			multiple: false

		});

		file_frame.on( "select", function() {

			var attachments = file_frame.state().get("selection").toJSON();

			jQuery.each( attachments, function(i, attachment) {

				jQuery("div#thumb-image").addClass("active").find("img").attr("src", attachment.url ).show();
				jQuery("div#thumb-image").css("maxWidth", attachment.width );
				jQuery("button#add-video-thumb").hide();
				jQuery("input#video_image").val( attachment.id );
				jQuery("a#remove-video-thumb").show();

			});

		});

		file_frame.open();

	});

	jQuery("a#remove-video-thumb").click(function(e){

		jQuery("div#thumb-image").removeClass("large");

		setTimeout(function(){

			jQuery("div#thumb-image").removeClass("active").find("img").attr("src", "" ).hide();
			jQuery("button#add-video-thumb").show();
			jQuery("a#remove-video-thumb").hide();
			jQuery("input#video_image").val("");

		}, 500);

		e.preventDefault();
		return false;

	});

	jQuery("div#thumb-image").dblclick(function(){

		if( !jQuery(this).hasClass("active") ){

			return false;

		}

		jQuery(this).toggleClass("large");

	});

	if( jQuery("input#video_image").val() ){

		jQuery("a#remove-video-thumb").show();
		jQuery("div#thumb-image").find("img").show();
		jQuery("button#add-video-thumb").hide();

	}

	/* Media / Segment Options */

	jQuery("input.playlist-type").change(function(e){

		if( jQuery(this).val() == 1 ){

			jQuery("fieldset.legacy").addClass("active");
			jQuery("fieldset.new").removeClass("active");

		}else{

			jQuery("fieldset.legacy").removeClass("active");
			jQuery("fieldset.new").addClass("active");

		}

	});

	jQuery("ul#new-segment-list").on("dblclick", "li.segment", function(){

		jQuery(this).parents("ul#new-segment-list").find("li.segment").removeClass("active");
		jQuery(this).addClass("active");

	});

	jQuery("ul#new-segment-list").on("contextmenu", "ul#keyword-list li", function(e){

		jQuery(this).remove();

		e.preventDefault();
		return false;

	});

	jQuery("ul#new-segment-list").on("click", "li#create-segment", function(){

		var $html  = jQuery(this).parents("ul#new-segment-list").find("li.segment:first-child").clone(true);
		var path   = jQuery( $html ).find("ul#media-list li:first-child").find("input").data("path");
		
		var random = Math.floor((Math.random() * 1000000) + 1);

		jQuery( $html ).find("input.clear, textarea.clear").val("");
		jQuery( $html ).find("input#segment_id").prop("disabled", true);
		jQuery( $html ).find("input#segment_duration").val("00:00:00");
		jQuery( $html ).find("textarea").trigger("keyup");
		jQuery( $html ).find("input#youtube_id").val("");
		jQuery( $html ).find("select#segment_status").val("draft");
		jQuery( $html ).find("ul#media-list li:gt(0)").remove();

		if( path ){

			jQuery( $html ).find("ul#media-list li:first-child").find("input").val(path);

		}

		jQuery( $html ).attr('id', 'unsaved-post-' + random);
		jQuery( $html ).find("ul#keyword-list li").remove();
		jQuery( $html ).find("span#current-photo").hide();
		jQuery( $html ).find("select#segment_categories").val("");
		jQuery( $html ).find("div.cover h2").html("New Segment");
		jQuery( $html ).find("div.cover h3").html("Draft");
		jQuery( $html ).find("span#file").html("");
		jQuery( $html ).find("span#smil").hide();
		jQuery( $html ).find("div.cover img.thumbnail").removeClass("loaded").attr("src", "").hide();
		jQuery( $html ).find("button#save-segment").html("Save");
		jQuery( $html ).removeClass("live active draft publish private");

		jQuery( $html ).insertBefore("ul#new-segment-list li#create-segment");

	});

	jQuery("ul#new-segment-list").on("click", "button#add-keyword", function() {

		var $input   = jQuery(this).parents("li.segment").find("input#keywords");
		var keyword  = $input.val();
			keyword  = keyword.replace(/,\s*$/, "");
		var $this    = jQuery(this);

		if( keyword && keyword.length >= 3 ){

			var keywords = keyword.split(",");

			jQuery(keywords).each(function(index, item){

				if( item != "" || item != " " ){

					var $keyword = jQuery("<input>").attr("type", "hidden").attr("name", "keyword[]").val( item.replace(/"/g, "&quot;").trim() );

					$this.parents("li.segment").find("ul#keyword-list").append( jQuery("<li>").html( item.trim() ).append( $keyword ) );

					$input.val("").focus();

				}

			});

		}

	});


	jQuery("ul#new-segment-list").on("click", "button#select-photo", function(e) {

		var $subject = jQuery(this);
		var target   = $subject.parents('li.segment').attr('id');
		var file_frame;
		
		alert( target );

		event.preventDefault();

		if ( file_frame ) {

			file_frame.open();
			return;

		}

		file_frame = wp.media.frames.file_frame = wp.media({

			title: "Add Segment Photo",
			button: {
				text: "Add Photo",
			},
			multiple: false

		});

		file_frame.on( "select", function() {

			var attachments = file_frame.state().get("selection").toJSON();

			jQuery.each( attachments, function(i, attachment) {

				$subject.html("Update Photo");
				
				alert(target);
				
				console.log( jQuery('ul#new-segment-list').find('li#' + target ) );
				jQuery('ul#new-segment-list').find('li#' + target ).find("input#thumbnail_id").val( attachment.id );

				jQuery('ul#new-segment-list').find('li#' + target ).find("div.cover img.thumbnail").addClass("loaded").attr("src", attachment.url )
				jQuery('ul#new-segment-list').find('li#' + target ).find("span#current-photo").show().find("img").attr("src", attachment.url )
				
				alert( attachment.url );
/*
				jQuery("div#thumb-image").addClass("active").find("img").attr("src", attachment.url ).show();
				jQuery("div#thumb-image").css("maxWidth", attachment.width );
				jQuery("button#add-video-thumb").hide();
				jQuery("input#video_image").val( attachment.id );
				jQuery("a#remove-video-thumb").show();
*/

			});

		});

		file_frame.open();

		e.preventDefault();
		return false;

	});


	jQuery("ul#new-segment-list").on("keypress", "input#keywords", function(e) {

		if( e.keyCode != 13 ){

            return;

        }

		jQuery(this).parents("div.form").find("button#add-keyword").trigger("click");

		e.preventDefault();
		return false;

	});


	jQuery("ul#new-segment-list").on("click", "button#cancel-segment", function() {

		jQuery(this).parents("li.segment").removeClass("active");

	});

	/* New Segment Save */

	jQuery("ul#new-segment-list").on("click", "button#save-segment", function(e){

		var $this     = jQuery(this);

		var form_data = jQuery(this).parents("div.form").find("input, select, button, textarea").serialize();

		jQuery.ajax({
			url: ajaxurl + '?action=lb_segment_action',
			type: "POST",
			data: form_data,
			dataType: 'json'
		}).done(function(response) {

			$this.parents("li.segment").find("div.data").find("h2").html( response.post_title );
			$this.parents("li.segment").find("div.data").find("h3").html( response.post_status );
			$this.parents("li.segment").addClass( response.post_status );
			$this.parents("li.segment").find("img.thumbnail.loaded").show();
			$this.parents("li.segment").find("input#segment_id").prop("disabled", false).val( response.postID );
			$this.parents("li.segment").find("button#save-segment").html("Update");
			$this.parents("li.segment").attr("id","post-" + response.postID ).addClass("live").removeClass("active");

			if( response.smilFile ){

				$this.parents("li.segment").find("span#smil").find("span#file").html( response.smilFile );
				$this.parents("li.segment").find("span#smil").show();

			}


		}).fail(function( jqXHR, textStatus ) {
			alert( "Saving your segment failed. \n\n" + textStatus );
		});

		e.preventDefault();
		return false;

	});

	jQuery("ul#new-segment-list").on("keypress", "input, textarea", function(e){

		if( e.keyCode != 13 || jQuery(this).attr("id") == "keywords" ){

            return;

        }

		jQuery(this).parents("div.form").find("button#save-segment").trigger("click");

		e.preventDefault();
		return false;

	});

	
	
	jQuery('form#post').submit(function(e){
		
		var $form = jQuery(this),
			errors = 0;
		
		
		$form.find('input, select, textarea').each(function(){
			
			var $inputItem = jQuery(this);
			if( $inputItem.hasClass('required') && $inputItem.val() == '' ){
				
				$inputItem.css('border', '1px solid red');
				errors++;
				
			}else{
				
				$inputItem.css('border', '1px solid white');
				
			}
			
		});
		
		if( errors > 0 ){
			
			e.preventDefault();
			
			alert('Please correct fields highlighted red to continue.');
			return false;
			
		}
		
	});
	

	jQuery("select#media_type").change(function(e){

		if( jQuery(this).val() == "vbr" ){

			jQuery("select#media_type").parents("fieldset").find("div#variable_media_list").show();
			jQuery("select#media_type").parents("fieldset").find("div#single_media_list").hide();

		}else{

			jQuery("select#media_type").parents("fieldset").find("div#variable_media_list").hide();
			jQuery("select#media_type").parents("fieldset").find("div#single_media_list").show();

		}

	});

	/* VBR Add / Remove */

	jQuery("ul#media-list").on( "click", "a.remove", function(e) {

		if( jQuery(this).parents("ul#media-list").find("li").length > 1 ){

			jQuery(this).parents("li.media").remove();

		}else{

			var mediaPath = jQuery(this).parents("ul#media-list").find("li:last-child").find("input[type=text]").data("path");

			jQuery(this).parents("ul#media-list").find("li:last-child").find("input[type=text]").val( "" );

			var default_value = jQuery(this).parents("ul#media-list").find("li:last-child").find("select").find("option").eq(0).val();

			jQuery(this).parents("ul#media-list").find("li:last-child").find("select").val( default_value );

			jQuery(this).parents("ul#media-list").find("li:last-child").find("input[type=text]").eq(0).focus();

		}

		e.preventDefault();
		return false;

	});

	jQuery("ul#media-list").on( "click", "a.add", function(e) {

		var $html = jQuery(this).parents("ul#media-list").find("li").eq(0).clone().html();

		var mediaPath = jQuery(this).parents("ul#media-list").find("li:last-child").find("input[type=text]").data("path");

		jQuery(this).parents("ul#media-list").append( jQuery("<li>").addClass("media").html( $html ) );

		jQuery(this).parents("ul#media-list").find("li:last-child").find("input[type=text]").val( mediaPath );

		var default_value = jQuery(this).parents("ul#media-list").find("li:last-child").find("select").find("option").eq(0).val();

		jQuery(this).parents("ul#media-list").find("li:last-child").find("select").val( default_value );

		jQuery(this).parents("ul#media-list").find("li:last-child").find("input[type=text]").eq(0).focus();



		e.preventDefault();
		return false;

	});


	/* Add "active" class to fields being edited */

	jQuery("input, textarea").on("click", function(){

		jQuery("input, textarea").each(function(){

			jQuery(this).parents("div.field").removeClass("active");

		});

		jQuery(this).parents("div.field").addClass("active");

	});


	/* Tabs */

	if( window.location.hash ) {

		var tabIndex = jQuery("div#playlist-tabs-area").find( window.location.hash ).index()


		jQuery("div#playlist-tabs-area").find( window.location.hash ).show();
		jQuery("div#playlist-tabs").find("li.tab").eq( tabIndex ).addClass("active");

	}else{

		jQuery("div#playlist-tabs").find("li.tab").eq(0).addClass("active");
		jQuery("div#playlist-tabs-area").find("div.tab-section").eq(0).show();

	}

	jQuery("#playlist-tabs nav ul li.tab a").click(function(){

		var target = jQuery(this).attr("href");

		jQuery("li.tab").removeClass("active");
		jQuery(this).parents("li.tab").addClass("active");

		jQuery("div#playlist-tabs-area").find("div.tab-section").hide();
		jQuery("div#playlist-tabs-area").find(target).show();


	});

	/* Education Objectives */

	setInterval(function(){

		var segments = [];

		jQuery("ul#segment-list").find("li").each(function(){

			if( jQuery(this).find("input.title").val() != "" ){

				segments.push( jQuery(this).find("input.title").val() );

			}

		});

		console.log(segments);

	}, 10000);

	jQuery("ul#education-objectives").on( "click", "a.remove", function(e) {

		if( jQuery(this).parents("ul#education-objectives").find("li").length > 1 ){

			jQuery(this).parents("li").remove();

		}

		e.preventDefault();
		return false;

	});

	jQuery("ul#education-objectives").on( "click", "a.add", function(e) {

		var $html = jQuery(this).parents("ul#education-objectives").find("li").eq(0).clone().html();

		jQuery(this).parents("ul#education-objectives").append( jQuery("<li>").html( $html ) );

		jQuery(this).parents("ul#education-objectives").find("li:last-child").find("input[type=text]").val("");

		var default_value = jQuery(this).parents("ul#education-objectives").find("li:last-child").find("select").find("option").eq(0).val();

		jQuery(this).parents("ul#education-objectives").find("li:last-child").find("select").val( default_value );

		jQuery(this).parents("ul#education-objectives").find("li:last-child").find("input[type=text]").eq(0).focus();

		e.preventDefault();
		return false;

	});

	/* Related Links */

	jQuery("ul#related-links").on( "click", "a.remove", function(e) {

		if( jQuery(this).parents("ul#related-links").find("li").length > 1 ){

			jQuery(this).parents("li.link").remove();

		}

		e.preventDefault();
		return false;

	});

	jQuery("ul#related-links").on( "click", "a.add", function(e) {

		var $html = jQuery(this).parents("ul#related-links").find("li.link").eq(0).clone().html();

		jQuery(this).parents("ul#related-links").append( jQuery("<li>").addClass("link").html( $html ) );

		jQuery(this).parents("ul#related-links").find("li:last-child").find("input[type=text]").val("");

		jQuery(this).parents("ul#related-links").find("li:last-child").find("input[type=text]").eq(0).focus();

		e.preventDefault();
		return false;

	});

	jQuery("ul#related-links").on( "blur", "input.link-url", function(e) {

		var $input = jQuery(this);
		var value  = $input.val();

		if( value && !value.match(/^http([s]?):\/\/.*/) ){

			$input.val( "http://" + value );

		}

	});
    
    
	/* Legacy Segments Add / Remove */

	jQuery("ul#segment-list").on( "click", "a.remove", function(e) {

		if( jQuery(this).parents("ul#segment-list").find("li").length > 1 ){

			jQuery(this).parents("li.segment").remove();

		}

		e.preventDefault();
		return false;

	});

	jQuery("ul#segment-list").on( "click", "a.add", function(e) {

		var $html = jQuery(this).parents("ul#segment-list").find("li.segment").eq(0).clone().html();

		jQuery(this).parents("ul#segment-list").append( jQuery("<li>").addClass("segment limited").data("limit", 300).html( $html ) );

		jQuery(this).parents("ul#segment-list").find("li:last-child").find("input[type=text], textarea").val("");

		jQuery(this).parents("ul#segment-list").find("li:last-child").find("input[type=text]").eq(0).focus();

		e.preventDefault();
		return false;

	});

	jQuery("ul#related-links").on( "blur", "input.link-url", function(e) {

		var $input = jQuery(this);
		var value  = $input.val();

		if( value && !value.match(/^http([s]?):\/\/.*/) ){

			$input.val( "http://" + value );

		}

	});

	jQuery("ul#related-links").find("input.link-url").each(function(e) {

		var $input = jQuery(this);
		var value  = $input.val();

		if( !value ){

			return false;

		}


		if( value && !value.match(/^http([s]?):\/\/.*/) ){

			$input.val( "http://" + value );

		}

	});

	/* Duration */

	jQuery("div#playlist-editor").on("keydown", "input.duration", function(event) {

		if ( jQuery(this).val().length == 3 && event.keyCode > 54 && event.keyCode != 8 ){

			event.preventDefault();

		}

		if ( jQuery(this).val().length == 6 && event.keyCode > 54 && event.keyCode != 8 ){

			event.preventDefault();

		}

		if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 || event.keyCode == 13 ||
		(event.keyCode >= 35 && event.keyCode <= 39) ) {
			return;
		} else {

			if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 ) || jQuery(this).val().length >= 8 ) {

			event.preventDefault();

			}

		}

	});

	jQuery("div#playlist-editor").on("keydown", "input.duration", function(event) {

		if ( jQuery(this).val().length == 2 && event.keyCode != 8 ){

			jQuery(this).val( jQuery(this).val() + ":" );
			event.preventDefault();

		}

		if ( jQuery(this).val().length == 5 && event.keyCode != 8 ){

			jQuery(this).val( jQuery(this).val() + ":" );
			event.preventDefault();

		}

	});

	jQuery("div#playlist-editor").on("click focus", "input.duration", function(){

		if( jQuery(this).val() == "00:00:00" ){

			jQuery(this).val( "" );

		}

	});

	jQuery("div#playlist-editor").on("blur", "input.duration", function(){

		if( jQuery(this).val() == "" ){

			jQuery(this).val( "00:00:00" );

		}

	});

	if( jQuery("input.duration").val() == "" ){

		jQuery("input.duration").val( "00:00:00" );

	}

	/* Progress Bar Tool */

	jQuery("div.field").on("keyup click", "textarea, input[type=text]", function(){

		if( !jQuery(this).parents("div.field").data("limit") ){

			return false;

		}

		var max   = jQuery(this).parents("div.field").data("limit");
		var count = jQuery(this).val().length;
		var percent = ( ( count / max ) * 100 );

		if( percent > 0 && percent < 69 ){

		    jQuery(this).parents("div.field").find("span.background").removeClass("warn okay error").addClass("okay");

	    }else if( percent > 69 && percent < 90 ){

		    jQuery(this).parents("div.field").find("span.background").removeClass("warn okay error").addClass("warn");

	    }else if( percent > 91 ){

		    jQuery(this).parents("div.field").find("span.background").removeClass("warn okay error").addClass("error");

	    }

	    jQuery(this).parents("div.field").find("span.background").width( percent + "%" );

	    jQuery(this).parents("div.field").find("span.text").html( count + "/" + max + " characters" );

		jQuery(this).on("keyup click load", function(){

			var $field  = jQuery(this);
			var $parent = jQuery(this).parents("div.field");
			    count   = $field.val().length;
		        percent = ( ( count / max ) * 100 );

		    $field.attr("maxlength", max);

		    if( percent > 0 && percent < 69 ){

			    $parent.find("span.background").removeClass("warn okay error").addClass("okay");

		    }else if( percent > 69 && percent < 90 ){

			    $parent.find("span.background").removeClass("warn okay error").addClass("warn");

		    }else if( percent > 91 ){

			    $parent.find("span.background").removeClass("warn okay error").addClass("error");

		    }

			$parent.find("span.text").html( count + "/" + max + " characters" );
			$parent.find("span.background").width( percent + "%" );

		});

	});

	jQuery("div#segments").on("keyup click", "textarea", function(){

		if( !jQuery(this).parents("li.segment").data("limit") ){

			return false;

		}

		var max   = jQuery(this).parents("li.segment").data("limit");
		var count = jQuery(this).val().length;
		var percent = ( ( count / max ) * 100 );

		if( percent > 0 && percent < 69 ){

		    jQuery(this).parents("li.segment").find("span.background").removeClass("warn okay error").addClass("okay");

	    }else if( percent > 69 && percent < 90 ){

		    jQuery(this).parents("li.segment").find("span.background").removeClass("warn okay error").addClass("warn");

	    }else if( percent > 91 ){

		    jQuery(this).parents("li.segment").find("span.background").removeClass("warn okay error").addClass("error");

	    }

	    jQuery(this).parents("li.segment").find("span.background").width( percent + "%" );

	    jQuery(this).parents("li.segment").find("span.text").html( count + "/" + max + " characters" );

		var $field  = jQuery(this);
		var $parent = jQuery(this).parents("li.segment");
		    count   = $field.val().length;
	        percent = ( ( count / max ) * 100 );

	    $field.attr("maxlength", max);

	    if( percent > 0 && percent < 69 ){

		    $parent.find("span.background").removeClass("warn okay error").addClass("okay");

	    }else if( percent > 69 && percent < 90 ){

		    $parent.find("span.background").removeClass("warn okay error").addClass("warn");

	    }else if( percent > 91 ){

		    $parent.find("span.background").removeClass("warn okay error").addClass("error");

	    }

		$parent.find("span.text").html( count + "/" + max + " characters" );
		$parent.find("span.background").width( percent + "%" );

	});


	jQuery("div#playlist-editor").find(".limited").find("input, textarea").each(function(){

		jQuery(this).trigger("keyup");

	});

	jQuery("select#media_type").trigger("change");

});