<?php function hectv_create_all_episodes( $parent = false ){ ?>

	<?php if( $parent ) { ?>

		<div class="all-episodes module dark clearfix <?php echo implode( $class ); ?>">

			<?php $episodeCount = get_posts( array( 'post_type' => 'lb_playlist', 'posts_per_page' => -1, 'post_parent' => $parent, 'post_status' => 'publish' ) ); ?>

		    <div class="inner">

			    <h2>Episodes</h2>

			    <p>There are <?php echo count( $episodeCount ); ?> more episodes of <?php echo get_the_title( $parent ); ?>.</p>

		        <div class="btn-wrap">
		            <button class="btn-view-all-episodes btn">View All</button>
		        </div>

		    </div>

		    <div id="episode-list" class="dark module" style="display:none;">

			    <div class="inner">

				    <button class="close-modal" title="Close" style="background-image: url('<?php bloginfo('template_directory'); ?>/_/graphics/ui-close.png');"></button>

				     <h2>Episodes</h2>

				    <ul>

				    <?php foreach( $episodeCount as $episode ){ ?>

						<?php $duration = get_post_custom_values( "duration", $episode->ID ); ?>

				    	<li class="clear">
					    	<a href="<?php echo get_permalink( $episode->ID ); ?>">
						    	<h3 class="title"><?php echo $episode->post_title; ?></h3>
						    	<span class="duration"><?php echo hectv_formatDuration( $duration[0] ); ?></span>
					    	</a>
				    	</li>

				    <?php } ?>

				    </ul>

			    </div>

		    </div>

		</div> <!-- End All Episodes Module -->

	<?php } ?>

<?php } ?>