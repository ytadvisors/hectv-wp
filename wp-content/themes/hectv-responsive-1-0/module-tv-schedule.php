<div class="module program-calendar clearfix">

	<div class="inner">

		<h2>Hec-TV Live! Program Calendar</h2>
		

		<?php if( have_rows('upcoming_episodes', 17893) ): ?>

		<ul class="upcoming-episodes">
			<h3>Upcoming Episodes</h3>

				<?php $x = 0; ?>
				<?php while ( have_rows('upcoming_episodes', 17893) ) : the_row(); ?>
				
				<?php list( $upcoming_episode_id ) = get_sub_field('episode_link'); ?>
				<?php $episode_item = get_post( $upcoming_episode_id ); ?>
								
				<li>
					<div class="post-info">
						<span class="date"><?php echo date( 'l, F jS', strtotime( $episode_item->post_date ) ); ?></span>
						<a href="<?php echo get_permalink( $episode_item->ID ); ?>" class="episode"><?php echo $episode_item->post_title; ?></a>
					</div>
					
				</li>
				
				<?php endwhile; ?>
			
			<li class="cta"><a href="<?php echo site_url('/watch/hec-tv-live/'); ?>">View All</a></li>
			
		</ul>
		
		<?php endif; ?>
		
		<?php $past_hectv_live_epiosodes = get_posts( array( 'post_type' => array( 'lb_playlist' ), 'posts_per_page' => 3, 'order_by' => 'post_date', 'order' => 'DESC' ) ); ?>
		
    	<ul class="archived-episodes">
			<h3>Archived Episodes</h3>
			
			<?php foreach( $past_hectv_live_epiosodes as $past_hectv_live_episode ){ ?>
			<li>
				<div class="post-info">
					<span class="date"><?php echo date( 'l, F jS', strtotime( $past_hectv_live_episode->post_date ) ); ?></span>
					<a href="<?php echo get_permalink( $past_hectv_live_episode->ID ); ?>" class="episode"><?php echo $past_hectv_live_episode->post_title; ?></a>
				</div>
				
			</li>
			<?php } ?>
			
			<li class="cta"><a href="<?php echo site_url('/watch/hec-tv-live/'); ?>">View All</a></li>
			
		</ul>	

	</div>

	<div class="flex"></div>

</div> <!-- Program Calendar -->