<?php function hectv_create_series_episode_list( $display = 5, $series_id, $title, $class = array() ){ ?>
	<?php if( empty( $series_id ) ){ ?>
	<?php return false; ?>
	<?php } ?>
<div class="trending module clearfix <?php echo implode( $class ); ?>">

    <h2><?php echo $title; ?></h2>

    <ul class="video-list clearfix">

	    <?php $series_videos = get_posts( array( "post_status" => "publish", "post_type" => array( "lb_playlist", "lb_video" ), "post_parent" => $series_id ) ); ?>

		<?php foreach( $series_videos as $series_video ){ ?>

		<?php $video_data   = get_post_custom( $series_video->ID ); ?>
		<?php $image_url    = wp_get_attachment_image_src( $video_data['video_image'][0], "video-thumb" ); ?>
		<?php $thumb_url    = ( $image_url ) ? $image_url[0] : get_bloginfo('template_directory') . "/_/graphics/ui-no-image.jpg"; ?>

        <li>
            <article class="trending-article clearfix">
                <div class="left">
                    <div class="img-wrap">
	                    <a href="<?php echo get_permalink( $series_video->ID ); ?>">
		                    <img class="play" src="<?php echo get_bloginfo('template_directory'); ?>/_/graphics/play-button.png">
	                    	<img src="<?php echo $thumb_url; ?>">
	                    </a>
                    </div>
                </div>
                <div class="right clearfix">
					<div class="trending-inner clearfix">
						<a href="<?php echo get_permalink( $series_video->ID ); ?>">
	                        <h3><?php echo $series_video->post_title; ?></h3>
						</a>
					</div>
                </div>
            </article>
        </li>

        <span><hr></span>

        <?php } ?>


        <div class="btn-wrap">
            <a href="<?php echo get_permalink( $series_id ); ?>" class="btn">View All</a>
        </div>
    </ul>

</div> <!-- End Trending Mobile Module -->
<?php } ?>