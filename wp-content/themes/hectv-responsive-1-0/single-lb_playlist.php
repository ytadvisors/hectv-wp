<?php require_once('_/inc/preferred-recent-episode-functions.php'); ?>
<?php get_header(); ?>

<main class="page clearfix" id="video-detail" data-video-id="<?php echo $post->ID; ?>">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

	<?php $playlist   = get_post_custom( $post->ID );?>
	<?php $seriesID   = wp_get_post_parent_id( $post->ID ); ?>
	<?php $seriesData = get_post_custom($seriesID); ?>
	
	<?php $segmentsList  = unserialize( $playlist['segment_child'][0] ); ?>

	<?php if( is_array( $segmentsList ) ){ ?>
		<?php foreach($segmentsList as $segment){ ?>
			<?php $status = get_post_status( $segment ); ?>
			<?php if( $status == "publish" || $status == "inherit" ||  is_user_logged_in() ){ ?>
			<?php $segments[] = $segment; ?>
			<?php } ?>
		<?php } ?>
	<?php } ?>

	<?php $segmentID = ( is_numeric( $segments[0] ) ) ? $segments[0] : $post->ID; ?>

	<?php $thumb = wp_get_attachment_image_src( $playlist['video_image'][0], 'full' ); ?>
 	
 	<?php $recent_base_args = array( 'posts_per_page' => 5, 'post_type' => 'lb_playlist', 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish', 'post_parent' => $seriesID ); ?>

    <?php $recent_query = lb_get_preferred_episodes_first( $recent_base_args ); ?>
	
    <div class="inner">

		<section class="video-carousel clearfix">

            <?php 
            
                $firstSegmentVimeo = get_post_custom_values( "vimeo_id", $segments[0] )[0];
                $firstSegmentYT = get_post_custom_values( "youtube_id", $segments[0] )[0];
                
                //For testing purposes
//                echo "<!--single-lb_playlist Vimeo: $firstSegmentVimeo YT: $firstSegmentYT -->";
//                echo "<!--";
//                echo " user logged in ";
//                print_r( is_user_logged_in() );
//                echo " article status ";
//                print_r( $status );
//                echo " segments list ";
//                print_r( $segmentsList );
//                echo " segments ";
//                print_r( $segments );
//                echo " vimeo stuff ";
//                print_r( get_post_custom_values( "vimeo_id", $segments[0] ) );
//                echo "-->";
//            
            
            ?> 
            
			<?php if( $playlist['playlist_type'][0] == 2 ){ ?>
			
				<?php $firstSegment   = get_post_custom_values( "youtube_id", $segments[0] ); ?>
				<?php $firstSegmentYT = $firstSegment[0]; ?>
				
			<?php } ?>
			
			<?php if( !empty($firstSegmentVimeo) ){ ?>

				<iframe src="https://player.vimeo.com/video/<?php echo $firstSegmentVimeo; ?>?color=0c88dd&title=0&byline=0&portrait=0&rel=0&badge=0&autoplay=true&api=1&player_id=vimeo" id="vimeo" class="resize-ratio" width="100%" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
			
	    	<?php }else if( !empty($firstSegmentYT) ){ ?>
	    		
				<iframe width="100%" height="610" class="resize-ratio" src="https://www.youtube.com/embed/<?php echo $firstSegmentYT; ?>/?modestbranding=1&rel=0&autoplay=1&title=" frameborder="0" allowfullscreen></iframe>
				
			<?php }else{ ?>
			     
		    	<div id="video-player" class="resize-ratio"></div>
		    	
	    	<?php } ?>

	    	<div class="carousel-wrap clearfix <?php echo ( count( $segments ) > 1 ) ? "segments-active" : ""; ?>" data-count="<?php echo count( $segments ); ?>">

				
		    	<div class="arrow-wrap left <?php echo ( count( $segments ) <= 5 ) ? "hidden" : ""; ?>">
		    		<img id="arrow-left" src="<?php bloginfo('template_directory'); ?>/_/graphics/arrow-left.png">
		    	</div>

				<div id="segments" class="owl-carousel video-wrap clearfix">
					<?php hectv_create_segment_html( $segments, 0 ); ?>
					
				</div> <!-- End Segments Wrap -->

				<div class="arrow-wrap right <?php echo ( count( $segments ) <= 5 ) ? "hidden" : ""; ?>">
		        	<img id="arrow-right" src="<?php bloginfo('template_directory'); ?>/_/graphics/arrow-right.png">
	            </div>
				
	    	</div> <!-- End Carousel Wrap -->

    	</section>

    </div>

		<div class="series-title">
			
			<span><a href="<?php echo get_permalink( $seriesID ); ?>"><?php echo get_the_title( $seriesID ); ?></a></span>

		</div>

		<div class="page-inner clearfix">

        	<section class="page-left">

            	<div id="series-info" class="module">

            		<ul class="tabs clearfix">

						<?php $relatedLinks = unserialize( $playlist['links'][0] ); ?>

						<li><a href="#" class="tab-nav active" rel="section-description">Description</a></li>
						<li><a href="#" class="tab-nav <?php echo ( !is_array( $relatedLinks ) || empty( $relatedLinks['url'][0] ) ) ? 'disabled':''; ?>" rel="section-related-links">Related Links</a></li>
						<li><a href="#" class="tab-nav <?php echo ( empty( $playlist['education_page_text'][0] ) ) ? 'disabled':''; ?>" rel="section-educational-material">Educational Material</a></li>

					</ul>

					<section>

						<div id="section-description" class="tab-section">
							<h3><?php echo get_the_title( $segmentID ); ?></h3>
							<?php hectv_get_content_by_id( $post->ID ); ?>
							<div class="segment-info">
                                <p></p>
								<?php //hectv_get_content_by_id( $segmentID ); ?>
							</div>
                            <div id="series-description" data-series-id="<?php echo $seriesID; ?>">
                                <?php hectv_get_content_by_id( $seriesID ); ?>
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
                                    <form name="_xclick" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
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
							<h3><?php echo get_the_title( $segmentID ); ?></h3>
							<ul id="related-links">
							<?php if( is_array( $relatedLinks ) && !empty( $relatedLinks['url'][0] ) ){ ?>

								<?php foreach( $relatedLinks['url'] as $index => $relatedLink ){ ?>

									<li>
										<a target="_blank" href="<?php echo $relatedLinks['url'][$index]; ?>"><?php echo $relatedLinks['title'][$index]; ?></a>
									</li>

								<?php } ?>
							<?php } ?>
							</ul>
						</div>

						<div id="section-educational-material" class="tab-section">
							<h3><?php echo get_the_title( $segmentID ); ?></h3>
							<?php echo wpautop( $playlist['education_page_text'][0] ); ?>

						</div>
					
						<div class="tab-section-bottom clearfix">
	
							<div style="margin-bottom: 1.2rem;">
							
								<div class="fb-like" data-href="<?php the_permalink(); ?>" data-layout="button_count" data-action="like" data-show-faces="false" data-share="true"></div>
								
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
								<ul>
								<?php foreach( $tags as $index => $tag ){ ?>
										<?php $comma = ( $index == ( count( $tags ) - 1 ) ) ? "":","; ?>
									<li><a class="tag-link" href="<?php echo get_term_link($tag); ?>"><?php echo $tag->name; ?><?php echo $comma; ?>&nbsp;</a></li>
								<?php } ?>
								</ul>

							</div>
							<?php } ?>

							<div class="share">

								<span><?php echo ( intval( $playlist['ga_adjust'][0] ) + $playlist['ga_pageviews'][0] + $playlist['views'][0] ); ?> Views</span>
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

                                <?php hectv_create_media_slat( 'recent-episode', $recent_query[0] ); ?>

                                <?php hectv_create_media_slat( 'recent-episode', $recent_query[1] ); ?>

                                <?php hectv_create_trending( 3, array( "trending-mobile" ) ); ?>

                        </div>

                        <div class="content-row clearfix">

                            <?php hectv_create_media_slat( 'recent-episode', $recent_query[2] ); ?>

                            <?php hectv_create_media_slat( 'recent-episode', $recent_query[3] ); ?>

                        </div>
                
                    <?php }?>

					<div class="content-row clearfix">

						<?php hectv_load_facebook_comments(); ?>

					</div>

            </section>

        	<section class="page-right">
	        	
	        	<?php hectv_create_ad_slat(); ?>

				<?php $legacySegments = unserialize( $playlist['segments'][0] ); ?>
				<?php if( is_array( $legacySegments ) && count( $legacySegments['title'] ) > 1 ){ ?>
				<?php $legacySegmentsActive = true; ?>
	        	<div id="legacy-video-segments" class="module">
					<script type="text/javascript">
						var segmentData = <?php echo json_encode($legacySegments); ?>;
					</script>
					<div class="inner">
						<h2>Segments</h2>

						<ul class="segment-wrap" data-segments="<?php echo json_encode( $legacySegments['inpoint'] ); ?>">

							<?php foreach( $legacySegments['title'] as $index => $legacySegment ){ ?>

							<li class="segment clearfix" rel="<?php echo $legacySegments['inpoint'][$index]; ?>">

								<a class="title play" href="#<?php echo $legacySegments['slug'][$index]; ?>" rel="<?php echo $legacySegments['inpoint'][$index]; ?>">
									<div class="play-wrap">
										<i class="fa fa-play-circle"></i>
										<i class="fa fa-pause"></i>
									</div>
									<span class="segment-link"><?php echo $legacySegments['title'][$index]; ?></span>
								</a>

							</li><!-- end segment -->

							<?php } ?>

						</ul> <!-- End segments Wrap -->

					</div>
				</div> <!-- end legacy video segments -->
				<?php } ?>

				<?php hectv_create_all_episodes( $seriesID ); ?>

				<?php 
                
                    if( $seriesID == 17893 ) {
                        
                        hectv_create_featured_live( array() );
                        
                    } else {
                        
                        hectv_create_trending( 3 ); 
                        
                    }
                
                ?>

				<?php hectv_create_media_slat( 'recent-episode', $recent_query[4] ); ?>

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

<?php if( $playlist['playlist_type'][0] == 2 ){ ?>

		<?php $firstSegmentSmil  = get_post_custom_values( "smil_file", $segments[0] ); ?>
		<?php $firstSegmentVideo = get_post_custom_values( "segment_files", $segments[0] ); ?>
		<?php $firstSegmentVideo = unserialize( $firstSegmentVideo[0] ); ?>

		<?php $files[]  = get_bloginfo('template_directory') . "/_/smil/$firstSegmentSmil[0]"; ?>
		<?php $files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $firstSegmentVideo['location'][1]; ?>

		<?php $playNextSegment = true; ?>

<?php }else{ ?>

	<?php if( $playlist['media_type'][0] == "sbr" ){ ?>

    	<?php $files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $playlist['sbr_file'][0]; ?>

	<?php }else if( $playlist['smil_file'][0] ){ ?>

		<?php $playlistVideo = unserialize( $playlist["video_files"][0] ); ?>

		<?php $files[]  = get_bloginfo('template_directory') . "/_/smil/" . $playlist['smil_file'][0] . "?time=" . time(); ?>
		<?php $files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $playlistVideo['location'][1]; ?>

	<?php } ?>

<?php } ?>

<script type="text/javascript">
    
//    console.log('single-lb_playlist.php');
	
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
    
    
	<?php if( !$firstSegmentYT && !$firstSegmentVimeo ){ ?>
	
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
    
    <?php }else if( $firstSegmentVimeo ){  ?>


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

				var items         = jQuery("div#segments").find("div.video-clip.active").length;
				var activeSegment = jQuery("div#segments").find("div.video-clip.current").attr("rel");

				if( activeSegment == 0 ){

					return;

				}else{

					var page = Math.round( activeSegment % items );

				}

				jQuery("div#segments").trigger("to.owl.carousel", [activeSegment, 300]);

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
