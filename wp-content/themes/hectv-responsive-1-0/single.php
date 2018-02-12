
<?php get_header(); ?>

<main class="page clearfix" id="blog-story">

	<div class="page-inner clearfix">

    	<section class="page-left">

	    	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

    		<article class="module-wide">

	    		<div class="inner clearfix">

	    			<div class="blog-title clearfix">

	        			<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

	    			</div>

	    			<span class="post-info">

						<span class="date"><?php the_time( 'F j, Y' ); ?></span>
						<span class="divider">|</span>
						<span class="author">Posted By <?php echo get_the_author_meta( "user_nicename", $post->post_author ); ?></span>

					</span>

	    		</div>

		    	<div class="carousel">

					<?php $headerImg = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) , 'full' ); ?>

		    			<img class= "blog-thumbnail" src="<?php echo $headerImg[0]; ?>">

	    		</div>

	    			<div class="inner clearfix">

						<div class="blog-post-top clearfix">

							<div class="tags">

								<span>Tags</span>
								<span class="divider">|</span>
								<span class="tags">
								<?php the_tags(''); ?>

								</span>

							</div>

							<div class="share">

								<span>Share</span>
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
								<a class="fa fa-caret-right"></a>
                                <br>
                                <a href="javascript:window.print()" class="print-button">Print</a>

							</div>

						</div>

	    				<?php the_content(); ?>

						<div class="share blog-post-bottom">

							<span class="social-icons">
								<a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" class="social-share" data-width="606" data-height="305">
									<i class="fa fa-facebook"></i>
								</a>
								<a href="https://twitter.com/share?text=<?php echo urlencode( $post->post_title . " @hec_tv" ); ?>&url=<?php the_permalink(); ?>" class="social-share" data-width="606" data-height="305">
									<i class="fa fa-twitter"></i>
								</a>
							</span>
                            <br>
                            <a href="javascript:window.print()" class="print-button">Print</a>

						</div>

		    		</div>

    		</article>


			<?php $next_post = get_adjacent_post(false, '', false); ?>

			<?php if( !empty( $next_post ) ) { ?>
    		<div class="next-blog-post module-wide">

	    		<?php $thumbnail = get_the_post_thumbnail( $next_post->ID, 'event-thumb' ); ?>

    			<div class="inner clearfix">

					<h4>Next Blog Post</h4>

					<div class="wrap clearfix">

						<div class="left">

							<a href="<?php echo get_permalink($next_post->ID); ?>">
                                <?php echo $thumbnail; ?>
							</a>

						</div>

						<div class="right">

							<a href="<?php echo get_permalink($next_post->ID); ?>">
								<h3><?php echo $next_post->post_title; ?></h3>
							</a>

						</div>

					</div>

    			</div>

			</div>
			<?php } ?>


    		<?php hectv_load_facebook_comments( true ); ?>

    		<?php endwhile; ?>

			<?php else: ?>

				<!-- article -->
				<article>

					<h1><?php _e( 'Sorry, nothing to display.', 'html5blank' ); ?></h1>

				</article>
				<!-- /article -->

			<?php endif; ?>

    	</section> <!--- End Page Left -->

    	<section class="page-right">

			<?php hectv_recent_blog( 3 ); ?>

        	<div class="module categories">

        		<div class="inner">

            		<h2>Categories</h2>

            		<ul class="blog-categories">
						<?php the_category(''); ?>
            		</ul>

            		<div class="btn-wrap">
                		<button class="btn">View All</button>
            		</div>

            	</div>

        	</div> <!-- End Module -->

        	<?php hectv_create_trending(3); ?>

    	</section><!--- End Page Right -->

	</div>

</main>

<style type="text/css">

	li#menu-item-16913 a {

		font-weight: 700;

	}

</style>

<?php get_footer(); ?>
