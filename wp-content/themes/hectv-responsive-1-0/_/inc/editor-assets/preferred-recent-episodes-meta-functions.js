/* Preferred Related Episodes Meta Box Functions */
jQuery(document).ready(function(){

	"use strict";
    
    jQuery("ul#preferred-related-episodes").on( "click", "a.js-remove-preferred-episode", function(e) {

        e.preventDefault();

        if( jQuery(this).closest("ul#preferred-related-episodes").find("li").length > 1 ){

            jQuery(this).closest("li.episode").remove();

        } else {
            
            
            var $html = jQuery(this).closest("ul#preferred-related-episodes").find("li.episode").eq(0).clone().html();

            jQuery(this).closest("ul#preferred-related-episodes").append( jQuery("<li>").addClass("episode").html( $html ) );

            jQuery(this).closest("ul#preferred-related-episodes").find("li:last-child").find("input[type=text]").val("");

            jQuery(this).closest("ul#preferred-related-episodes").find("li:last-child").find("input[type=text]").eq(0).focus();
            
            jQuery(this).closest("li.episode").remove();
            
        }
            

        return false;

    });

    jQuery("ul#preferred-related-episodes").on( "click", "a.js-add-preferred-episode", function(e) {

        e.preventDefault();

        var $html = jQuery(this).closest("ul#preferred-related-episodes").find("li.episode").eq(0).clone().html();

        jQuery(this).closest("ul#preferred-related-episodes").append( jQuery("<li>").addClass("episode").html( $html ) );

        jQuery(this).closest("ul#preferred-related-episodes").find("li:last-child").find("input[type=text]").val("");

        jQuery(this).closest("ul#preferred-related-episodes").find("li:last-child").find("input[type=text]").eq(0).focus();

        return false;

    });
    
});