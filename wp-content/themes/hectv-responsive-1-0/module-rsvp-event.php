<?php function hectv_create_rsvp_slat( $episodeID, $parent = false, $rsvpData ){ ?>
<?php $baseArgs = array( 'posts_per_page' => 1, 'post_type' => 'lb_playlist', "orderby" => "date", "order" => "DESC", "include" => $episodeID ); ?>

	<?php if( $parent ){ ?>

		<?php $baseArgs['post_parent'] = $parent; ?>

	<?php } ?>
	<?php $posts = get_posts( $baseArgs ); ?>

	<?php $data   = get_post_custom( $posts[0]->ID ); ?>
	<?php $thumb  = wp_get_attachment_image_src( $data['video_image'][0], 'media-medium' ); ?>
	<?php $parent = ( $parent ) ? $parent : $posts[0]->post_parent; ?>

	<?php $rsvpData['episode_shoot_date'] = date( "F j, Y", strtotime( $rsvpData['episode_shoot_date'] ) ); ?>
	<?php $rsvpData['parent']             = $parent; ?>
	<div class="module recent-clip clearfix" rel="<?php echo $parent; ?>" data-rsvp='<?php echo json_encode( $rsvpData ); ?>'>

	<div class="inner">

		<div class="media-type">

    		<span class="recent">Upcoming Event</span>
    		<span class="divider">|</span>
    		<span class="show"><a href="<?php echo get_permalink($parent); ?>"><?php echo get_the_title($parent); ?></a></span>

		</div>

			<a href="<?php echo get_permalink( $posts[0]->ID ); ?>">
	    		<h3><?php echo get_the_title( $posts[0]->ID ); ?></h3>
			</a>
    		<p><?php echo $posts[0]->post_excerpt; ?></p>

    		<div class="btn-wrap">
				<button class="btn-register">Register Now</button>
			</div>

	</div>

	<div class="img-wrap flex">
		<a href="<?php echo get_permalink( $posts[0]->ID ); ?>">
			<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
	    	<img class="thumb" src="<?php echo $thumb[0]; ?>">
		</a>
	</div>

</div> <!-- End Module -->
<?php } ?>