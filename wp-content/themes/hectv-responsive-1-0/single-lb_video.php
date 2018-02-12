<?php require_once('_/inc/preferred-recent-episode-functions.php'); ?>
<?php get_header(); ?>


<main class="page clearfix" id="video-detail" data-video-id="<?php echo $post->ID; ?>">

    <?php if (have_posts()): while (have_posts()) : the_post(); ?>

	<?php $segment      = get_post_custom( $post->ID ); ?>
	<?php $seriesID     = wp_get_post_parent_id( $post->post_parent ); ?>
	<?php echo $seriesID; ?>
	
	<?php $seriesData   = get_post_custom( $seriesID ); ?>

	<?php $remotePath   = get_post_custom_values( "media_path", $seriesID ); ?>
	<?php $playlistData = get_post_custom( $post->post_parent ); ?>

	<?php $segments     = unserialize( $playlistData['segment_child'][0] ); ?>
	<?php $currentSegment = array_search( $post->ID, $segments); ?>

	<?php $thumb        = wp_get_attachment_image_src( $segment['video_image'][0], 'full' ); ?>
	
	<?php //$recent_query = new WP_Query( array( 'post_type' => array( 'lb_playlist', 'lb_video' ), 'orderby' => 'date', 'order' => 'DESC', 'showposts' => 7, 'post_status' => 'publish', 'parent' => $seriesID ) ); ?>
	
	<?php $recent_base_args = array( 'posts_per_page' => 5, 'post_type' => 'lb_playlist', 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish', 'post_parent' => $seriesID ); ?>

	<?php $recent_query = lb_get_preferred_episodes_first( $recent_base_args ); ?>

    <div class="inner">
        
		<section class="video-carousel clearfix">
            
            <?php echo "<!-- single-lb_video Vimeo: ". $segment['vimeo_id'][0]." YT: ".$segment['youtube_id'][0]." -->"; ?>
			
			<?php if( !empty($segment['vimeo_id'][0]) ){ ?>
				
				<iframe src="https://player.vimeo.com/video/<?php echo $segment['vimeo_id'][0]; ?>?color=0c88dd&title=0&byline=0&portrait=0&rel=0&badge=0&autoplay=true&api=1&player_id=vimeo" id="vimeo" class="resize-ratio" width="100%" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
				
			<?php }else if( !empty($segment['youtube_id'][0]) ){ ?>
				
				<iframe width="100%" height="610" class="resize-ratio" src="https://www.youtube.com/embed/<?php echo $segment['youtube_id'][0]; ?>/?modestbranding=1&rel=0&autoplay=1&title=" frameborder="0" allowfullscreen></iframe>
				
			<?php }else{ ?>
			
		    	<div id="video-player" class="resize-ratio"></div>
		    	
	    	<?php } ?>

	    	<div class="carousel-wrap clearfix" data-count="<?php echo count( $segments ); ?>">

		    	<div class="arrow-wrap left <?php echo ( count( $segments ) <= 5 ) ? "hidden" : ""; ?>">
		    		<img id="arrow-left" src="<?php bloginfo('template_directory'); ?>/_/graphics/arrow-left.png">
		    	</div>

				<div id="segments" class="owl-carousel video-wrap clearfix">

					<?php hectv_create_segment_html( $segments, $currentSegment ); ?>

				</div> <!-- End Segments Wrap -->

				<div class="arrow-wrap right <?php echo ( count( $segments ) <= 5 ) ? "hidden" : ""; ?>">
		        	<img id="arrow-right" src="<?php bloginfo('template_directory'); ?>/_/graphics/arrow-right.png">
	            </div>

	    	</div> <!-- End Carousel Wrap -->

    	</section>

    </div>

		<div class="series-title">

			<span><?php echo get_the_title( $seriesID ); ?></span>

		</div>

		<div class="page-inner clearfix">

        	<section class="page-left">
                
            	<div id="series-info" class="module">

            		<ul class="tabs clearfix">

						<?php $relatedLinks = unserialize( $playlistData['links'][0] ); ?>
						<li><a href="#" class="tab-nav active" rel="section-description">Description</a></li>
						<li><a href="#" class="tab-nav <?php echo (is_array( $relatedLinks ) && count( $relatedLinks ) > 1) ? 'disabled':''; ?>" rel="section-related-links">Related Links</a></li>
						<li><a href="#" class="tab-nav <?php echo ( empty( $playlistData['education_page_text'][0] ) ) ? 'disabled':''; ?>" rel="section-educational-material">Educational Material</a></li>

					</ul>

					<section>

						<div id="section-description" class="tab-section" data-series-id="<?php echo $seriesID; ?>">
							<h3><?php the_title(); ?></h3>
							<?php the_content(); ?>
                            <div id="series-description">
                                <p>
                                    <?php echo get_post( $post->post_parent )->post_content; ?>
                                </p>
                                
                                <?php 
                                
                                    $audio_track = get_field('audio_track');
                                    if( $audio_track ):  
                                ?>
                                <div class="audio-download">
                                    <a href="<?php echo $audio_track; ?>" class="btn" style="margin: 15px;" download>Download the Audio</a>
                                </div>
                                <?php endif; ?>
                                
                                <?php 
                                
                                    $itunes_link = get_field('itunes_link');
                                    if( $itunes_link ):  
                                ?>
                                <div class="itunes">
                                    <a href="<?php echo $itunes_link; ?>" class="btn" style="margin: 15px;" target="_blank">View in iTunes</a>
                                </div>
                                <?php endif; ?>
                                
                                <?php 
                                
                                    if( get_field('paypal_allow_purchases') ): 
                                        
                                        $paypal_item_name = get_field('paypal_item_name');
                                        $paypal_item_name = empty( $paypal_item_name )?get_the_title( $seriesID ):$paypal_item_name;
                                        $paypal_item_price = get_field('paypal_item_price');
                                         
                                ?>
                                <div class="paypal-sale">
                                    <form name="_xclick" action="https://www.paypal.com/cgi-bin/webscr" method="post">
                                        <input type="hidden" name="cmd" value="_xclick">
                                        <input type="hidden" name="business" value="creative@hectv.org">
                                        <input type="hidden" name="currency_code" value="USD">
                                        <input type="hidden" name="item_name" value="<?php echo $paypal_item_name; ?>">
                                        <input type="hidden" name="amount" value="<?php echo $paypal_item_price; ?>">
                                        <input type="submit" name="submit" value="Own this program on DVD" class="btn" style="display: block; margin: 15px;">
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
						</div>


						<div id="section-related-links" class="tab-section">
							<h3><?php the_title(); ?></h3>
							<ul id="related-links">
							<?php if( is_array( $relatedLinks ) && count( $relatedLinks ) > 1 ){ ?>
								<?php foreach( $relatedLinks['url'] as $index => $relatedLink ){ ?>

									<li>
										<a target="_blank" href="<?php echo $relatedLinks['url'][0]; ?>"><?php echo $relatedLinks['title'][0]; ?></a>
									</li>

								<?php } ?>
							<?php } ?>
							</ul>
						</div>


						<div id="section-educational-material" class="tab-section">
							<h3><?php the_title(); ?></h3>
							<?php echo wpautop( $playlist['education_page_text'][0] ); ?>
						</div>


						<div class="tab-section-bottom clearfix">
							
														
							<div>
							
								<div class="fb-like" style="margin-bottom:10px;" data-href="<?php the_permalink(); ?>" data-layout="button_count" data-action="like" data-show-faces="false" data-share="true"></div>
								
								<span style="position:relative;display:inline-block;top:3px;">
								<a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php the_permalink(); ?>" data-text="<?php the_title(); ?>" data-via="hec_tv">Tweet</a></span>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>


								<div class="save-video-wrap">
									
									<?php if( is_user_logged_in() ){ ?>
									
										<span class="btn save-btn no-select episode">Save Video</span>
<!-- 										<span class="btn save-btn no-select segment">Save Segment</span> -->
										
									<?php }else{ ?>
									
										<a class="btn save-btn no-select log-in" href="/user-log-in" style="display: block;">Log in to Save Video</a>
									
									<?php } ?>
									
									
								</div>
							</div>

							<?php $tags = wp_get_post_terms( $post->ID, 'topic' ); ?>
							<?php if( count( $tags ) > 0 ){ ?>
							<div class="tags">

								<span>Tags</span>
								<span class="divider">|</span>

								<span class="tags">
									<?php foreach( $tags as $index => $tag ){ ?>
 										<?php $comma = ( $index == ( count( $tags ) - 1 ) ) ? "":","; ?>
										<a class="tag-link" href="<?php echo get_term_link($tag); ?>"><?php echo $tag->name; ?><?php echo $comma; ?></a>
									<?php } ?>
								</span>

							</div>
							<?php } ?>

							<div class="share">

								<span><?php echo ( intval( $playlistData['ga_adjust'][0] ) + $playlistData['ga_pageviews'][0] + $playlistData['views'][0] ); ?> Views</span>
								<span class="divider">|</span>
								<span class="social-icons">

									<a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" class="social-share" data-width="606" data-height="305">
										<i class="fa fa-facebook"></i>
									</a>
									<a href="https://twitter.com/share?text=<?php echo urlencode( $post->post_title . " @hec_tv" ); ?>&url=<?php the_permalink(); ?>" class="social-share" data-width="606" data-height="305">
										<i class="fa fa-twitter"></i>
									</a>

								</span>

								<a class="comment" href="#section-comments"><fb:comments-count href="<?php the_permalink(); ?>"></fb:comments-count> Comments</a>
								<i class="fa fa-caret-right"></i>

							</div>

						</div>

					</section>

					</div>
                
                <?php if ( get_field('series_type') !== "local special" ){ ?>
					<div class="content-row clearfix">

				            <?php hectv_create_media_slat( "recent", $recent_query[0] ); ?>

				            <?php hectv_create_media_slat( "recent", $recent_query[1] ); ?>

					</div>

					<div class="content-row clearfix">

			            <?php hectv_create_media_slat( "recent", $recent_query[2] ); ?>

			            <?php hectv_create_media_slat( "recent", $recent_query[3] ); ?>

						<?php hectv_create_trending( 3, array( "trending-mobile" ) ); ?>

                    </div>
                <?php } ?>

                    <div class="content-row clearfix">

                        <?php hectv_load_facebook_comments(); ?>

                    </div>

            </section>

        	<section class="page-right">

				<?php hectv_create_ad_slat(); ?>
				
				<?php hectv_create_all_episodes( $seriesID ); ?>

	        	<?php
                
                    if( $seriesID == 17893 ) {
                        
                        hectv_create_featured_live( array() );
                        
                    } else {
                        
                        hectv_create_trending( 3 ); 
                        
                    }
                ?>

				<?php hectv_create_media_slat( "recent", $recent_query[4] ); ?>

        	</section>

        	</div>

	</div>

	<?php endwhile; ?>

	<?php else: ?>

		<!-- article -->
		<article>

			<h1><?php _e( 'Sorry, nothing to display.', 'html5blank' ); ?></h1>

		</article>
		<!-- /article -->

	<?php endif; ?>

</main>

<?php $files = array(); ?>

<?php if( $segment['media_type'][0] == "sbr" ){ ?>

	<?php $files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $playlist['sbr_file'][0]; ?>

<?php }else if( $segment['smil_file'][0] ){ ?>

	<?php $segmentVideo = unserialize( $segment["segment_files"][0] ); ?>

	<?php $files[]  = get_bloginfo('template_directory') . "/_/smil/" . $segment['smil_file'][0]; ?>
	<?php $files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $segmentVideo['location'][1]; ?>

<?php } ?>


<script type="text/javascript">
    
//    console.log('single-lb_video.php');
    
    function loadSegment( slideIndex, pushState ) {
     
        
        if(pushState == null) {
            
            pushState = true;
            
        }
        
        jQuery("div#segments").trigger("to.owl.carousel", [ slideIndex, 500, true ] );
        jQuery("div.video-clip.current").removeClass("current");
        jQuery(jQuery("div.owl-item")[slideIndex]).find(".video-clip").addClass("current");
        var videoFile  = jQuery(jQuery(".video-clip.current")[0]).attr("data-segment-file");
        var vimeoId = jQuery(jQuery(".video-clip.current")[0]).attr("data-segment-vimeo-id");
        var segmentUrl = jQuery(jQuery(".video-clip.current a")[0]).attr("href");
                
        if( segmentUrl ) {
            
            if( videoFile ) {
                
                jwplayer("video-player").load([{

                    file: videoFile, 

                }]);
            
            }
    
            if( vimeoId ) {
                
                jQuery('iframe#vimeo').prop('src', 'https://player.vimeo.com/video/'+vimeoId+'?color=0c88dd&title=0&byline=0&portrait=0&rel=0&badge=0&autoplay=true&api=1&player_id=vimeo');

            }
            
            if(pushState) {
                
                window.history.pushState({url:segmentUrl, video:videoFile, slideIndex:slideIndex}, '', segmentUrl);
               
            }
            
            jQuery.get( segmentUrl, function(data){ 

              jQuery('#series-info').empty().append( jQuery(data).find('#series-info').children() );
                
            });
            
        }      
        
    }
    
    //History not working quite right
    window.addEventListener('popstate', function(e) {
     
        if(e.state.slideIndex) {
         
            loadSegment( e.state.slideIndex, false );
            
        }
        
        
    }); 
    
	<?php if( !$segment['youtube_id'][0]  && !$segment['vimeo_id'][0]){ ?>

	jwplayer("video-player").setup({
		autostart: true,
// 		mute: true,
		width: '100%',
		height: 720,
		image: '<?php echo $thumb[0]; ?>',
		skin: 'glow',
		sources: [
		<?php foreach( $files as $file ){ ?>
		{ file: "<?php echo $file; ?>" },
	    <?php } ?>
	    ],
	    primary: 'flash',
	    startparam: 'ec_seek',
	    aspectratio: "16:9",
	    events:{
	        onComplete: function() {

                var nextSegmentIndex = jQuery("div.video-clip.current").parents("div.owl-item").next("div.owl-item").index();
                loadSegment( nextSegmentIndex );

	        }
	    }
    });
    
    <?php }else if( $segment['vimeo_id'][0] ){  ?>

	jQuery(document).ready(function(){
        
        
		if( window.location.hash ){
			
			var hash         = window.location.hash.replace('#', '');
			var segmentIndex = segmentData.slug.indexOf(hash);
        }
        
        if( jQuery('iframe#vimeo').length > 0 ){

            var iframe = jQuery('#vimeo')[0],
            player = $f(iframe),
            status = jQuery('.status');

            // When the player is ready, add listeners for pause, finish, and playProgress
            player.addEvent('ready', function() {
                
                console.log('player ready event');
                
                if( window.location.hash ){

                    player.api('seekTo', segmentData.inpoint[segmentIndex] );

                }
                
                player.addEvent('finish', function(){
		        
                    console.log('finished!');
                    var nextSegmentIndex = jQuery("div.video-clip.current").parents("div.owl-item").next("div.owl-item").index();
                    loadSegment( nextSegmentIndex );
                    
                });

            });

        }
    
    });

	<?php } ?>
    
    jQuery(document).ready(function(){
	    
	    if( window.location.hash ){
			
			var hash         = window.location.hash.replace('#', '');
			var segmentIndex = segmentData.slug.indexOf(hash);
			
			jwplayer().seek( segmentData.inpoint[segmentIndex] );
			
		}

	   /* Series Segments */

		jQuery("div#segments").owlCarousel({

			scrollPerPage: 5,
			items: 5,
			onInitialize: updateItems,
			onInitialized: function(){

				if( jQuery("div#segments").length == 0 ){

					return;

				}

				var items         = jQuery("div#segments").find("div.owl-item.active").length;
				var activeSegment = parseInt( jQuery("div#segments").find("div.video-clip.current").attr("rel") ) + 1;

				if( activeSegment == 0 || activeSegment > items ){

					jQuery("div#segments").trigger("to.owl.carousel", [activeSegment, 1, 300]);

				}

			},
			onResize: updateItems,
			responsive : {
				400:{
					items: 2
				},
				768:{
					items: 4
				},
				1000:{
					items: 5
				}

			}

		});

		var updateItems = function(event){

			jQuery("main").data("items", event.item.count );

		}

		jQuery("img#arrow-left").click(function(){

			jQuery(this).parents("div.carousel-wrap").find("div.owl-carousel").trigger('prev.owl.carousel');

		});

		jQuery("img#arrow-right").click(function(){

			jQuery(this).parents("div.carousel-wrap").find("div.owl-carousel").trigger('next.owl.carousel');

		});
        
        jQuery(".owl-item .video-clip a").on( "click", function(e) {
           
            e.preventDefault();
            loadSegment(jQuery(this).closest('.owl-item').index());
            
        });

    });

</script>

<?php //$seriesBackground = wp_get_attachment_image_src( $seriesData['page_hat'][0], 'full' ); ?>
<?php if( !empty( $seriesBackground ) ){ ?>
<style type="text/css">

body{
	background-image: url(<?php echo $seriesBackground[0]; ?>);
	background-color: #f3f3f3;
	background-position: top center;
	background-repeat: no-repeat;
	background-size: 100% auto;
	background-attachment: fixed;
}

</style>
<?php } ?>

<?php get_footer(); ?>
