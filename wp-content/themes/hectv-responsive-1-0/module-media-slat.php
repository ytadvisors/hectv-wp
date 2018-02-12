<?php function hectv_create_media_slat( $type, $post ){ ?>
<?php

	switch ($type) {

		case "recent":
		case "recent-clip":
			$title = "Recent Clip";
			if( $parent ){
				$baseArgs['post_parent'] = $parent;
			}
			$baseArgs['post_type']       = 'lb_video';
			$posts = get_posts( $baseArgs );
			
		break;

		case "recent-episode":
			$title = "Recent Episode";
            $post_custom = get_post_custom( $post->ID); 
            $legacy_segments = unserialize( $post_custom['segments'][0] );
            $has_legacy_segments = ( is_array( $legacy_segments ) && count( $legacy_segments['title'] ) > 1 );
            $segments_list = unserialize( $post_custom['segment_child'][0] );
            $has_segments_list  = ( is_array( $segments_list ) && count( $segments_list ) > 1 );
            $is_ten_min = ($post_custom['duration'][0] && $post_custom['duration'][0] >= 600);
            $is_episode = ( $has_legacy_segments || $has_segments_list || $is_ten_min );
            
            $title = ($is_episode)?"Recent Episode":"Recent Segment";
//            $title = ($has_legacy_segments)?"Recent Episode (old)":"Recent Segment";
//            $title = ($has_segments_list)?"Recent Episode (new)":$title;
            

		break;

		case "related":
			if( $parent->post_type == "lb_playlist" ){
				$title  = "Related Episode";
			}else{
				$title  = "Related Clip";
			}

			$posts[0] = $parent;
			$parent   = false;


		break;

		case "popular":
		case "popular-clip":
			$title = "Popular Clip";
			if( $parent ){
				$baseArgs['post_parent'] = $parent;
			}
			$baseArgs['post_type']       = 'lb_video';
			$posts = get_posts( $baseArgs );
		break;

		case "popular-episode":
			$title = "Popular Episode";
			if( $parent ){
				$baseArgs['post_parent'] = $parent;
			}
			$posts = get_posts( $baseArgs );
			
			if( $posts == null ){
				
				return false;
				
			}
			
		break;

		case "staff":

			$title               = "Featured";

			$staffPick           = true;
			$staffPickData       = get_field( "staff_pick", "options" );
			$baseArgs['include'] = $staffPickData[0];

			$staffPickPost       = get_post( $staffPickData[0] );

			$post                = $staffPickPost;

		break;
		
		case "saved":

			$title               = "Saved";

		break;
		
	}
	?>

	<?php $data   = get_post_custom( $post->ID ); ?>
	<?php $thumb  = wp_get_attachment_image_src( $data['video_image'][0], 'media-medium' ); ?>
	<?php $parent = ( $parent ) ? $parent : $post->post_parent; ?>


	<div class="module recent-clip clearfix" rel="<?php //echo $parent; ?>" data-video-id="<?php echo $post->ID; ?>">

	<div class="inner">

			<div class="media-type">

	    		<span class="recent"><?php echo $title; ?></span>
                <!--  <?php //print_r(get_post_custom( $post->ID)); ?> --> 
                <!--  <?php //print_r(get_post_custom( $post->ID)["duration"]); ?> --> 
	    		
	    		<?php if( $parent ){ ?>
	    		<span class="divider">|</span>
	    		<span class="show"><a href="<?php echo get_permalink($parent); ?>"><?php echo get_the_title($parent); ?></a></span>
	    		<?php } ?>

			</div>
		
			<a href="<?php echo get_permalink( $post->ID ); ?>">
	    		<h3><?php echo $post->post_title ?></h3>
			</a>
    		<p><?php echo $post->post_excerpt; ?></p>

	</div>

	<div class="img-wrap flex">
		<a href="<?php echo get_permalink( $post->ID ); ?>">
			<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
	    	<img class="thumb" src="<?php echo $thumb[0]; ?>">
		</a>
	</div>
	
	<?php if( $type == 'saved' ){ ?>
		
	<a href="#" class="remove-saved">Delete</a>

	<?php } ?>

</div> <!-- End Module -->
<?php } ?>