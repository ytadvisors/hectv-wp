<?php function hectv_create_trending( $display = 5, $class = array() ){ ?>
<div class="trending module clearfix <?php echo implode( $class ); ?>">

    <h2>Trending<br/>on Hec-TV</h2>

    <ul class="video-list clearfix">

	    <?php $trendingVideos = get_posts( array( "post_status" => "publish", "post_type" => array( "lb_playlist", "lb_video" ), "orderby" => "meta_value_num, date", "order" => "DESC", "meta_key" => "socialcount_TOTAL", "posts_per_page" => $display, "date_query" => array( "after" => date("Y-m-d", strtotime("-1 month") ) ) ) ); ?>

		<?php foreach( $trendingVideos as $trendingVideo ){ ?>
		
			<?php if( $trendingVideo->ID != 13399 ){ ?>
	
				<?php $segment_data = get_post_custom( $trendingVideo->ID ); ?>
				<?php $image_url    = wp_get_attachment_image_src( $segment_data['video_image'][0], "video-thumb" ); ?>
				<?php $thumb_url    = ( $image_url ) ? $image_url[0] : get_bloginfo('template_directory') . "/_/graphics/ui-no-image.jpg"; ?>
		
		        <li>
		            <article class="trending-article clearfix">
		                <div class="left">
		                    <div class="img-wrap">
			                    <a href="<?php echo get_permalink( $trendingVideo->ID ); ?>">
				                    <img class="play" src="<?php echo get_bloginfo('template_directory'); ?>/_/graphics/play-button.png">
			                    	<img src="<?php echo $thumb_url; ?>">
			                    </a>
		                    </div>
		                </div>
		                <div class="right clearfix">
							<div class="trending-inner clearfix">
								<a href="<?php echo get_permalink( $trendingVideo->ID ); ?>">
			                        <h3><?php echo $trendingVideo->post_title; ?></h3>
								</a>
							</div>
		                </div>
		            </article>
		        </li>
		
		        <span><hr></span>
	
	        <?php } ?>
        
        <?php } ?>
        
    </ul>

</div> <!-- End Trending Mobile Module -->
<?php } ?>