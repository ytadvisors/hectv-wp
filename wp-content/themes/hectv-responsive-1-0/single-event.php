<?php
/*
Template Name: Single Event
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="event-detail">

	<div class="page-inner clearfix">

    	<section class="page-left">

	    	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

    		<article class="module-wide">

    			<div class="blog-title clearfix">

        			<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

    			</div>

    			<span class="post-info">

					<span class="date"><?php the_time( 'F j, Y' ); ?></span>
					<span class="divider">|</span>
					<span class="author">Posted By <?php the_author_posts_link(); ?></span>

				</span>

    			<div class="carousel">

    				<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
						<?php the_post_thumbnail(); // Fullsize image for the single post ?>
					</a>


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
								<a href="#" class="fa fa-facebook"></a>
								<a href="#" class="fa fa-twitter"></a>
								<a href="#" class="fa fa-google-plus"></a>
							</span>

							<a class="comment" href="#"><?php comments_popup_link( __( 'Leave your thoughts', 'html5blank' ), __( '1 Comment', 'html5blank' ), __( '% Comments', 'html5blank' )); ?></a>
							<a class="fa fa-caret-right"></a>

						</div>

				</div>

    				<?php the_content(); ?>

					<div class="share blog-post-bottom">

						<span class="social-icons">
							<a href="#" class="fa fa-facebook"></a>
							<a href="#" class="fa fa-twitter"></a>
							<a href="#" class="fa fa-google-plus"></a>
						</span>

						<a href="#" class="comment">5 Comments</a>
						<a href="#" class="fa fa-caret-right"></a>

					</div>

    			</div>

    		</article>

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

			<div class="module clearfix">

				<?php $recentBlogPostsQueryParameters = array( 'posts_per_page' => 2,
															   'orderby' => 'post_date',
															   'order' => 'DESC',
															   'post_type' => 'post' ); ?>

				<?php $recentBlogPosts = get_posts( $recentBlogPostsQueryParameters ); ?>

				<div class="recent-blog-posts">

	        		<h2>Recent Blog <br />Posts</h2>

					<?php foreach ( $recentBlogPosts as $post ) : setup_postdata( $post ); ?>

	            		<article>
	                		<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
	                		<span class="post-info">Posted On: <?php the_time( 'F j, Y' ); ?> By <?php the_author_posts_link(); ?></span>
	                		<?php hectv_excerpt(); ?>
	            		</article>

					<?php endforeach; ?>

				</div>

	    		<div class="flex"></div>

	    	</div> <!-- Recent Blog Posts -->

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

        	 <div class="trending module clearfix">
                <h2>Trending<br/>on Hec-TV</h2>
				<ul class="video-list clearfix">
                    <li>
                        <article class="trending-article clearfix">
                            <div class="left">
                                <div class="img-wrap">
                                	<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp.jpg">
                                </div>
                            </div>
                            <div class="right clearfix">
								<div class="trending-inner clearfix">

                                    <h3>Innovations</h3>
                                    <p>Morbi pretium scelerisque mi a porttitor.</p>
								</div>
                            </div>
                        </article>
                    </li>
					<span><hr></span>

                    <div class="btn-wrap">
                    	<button class="btn">See All</button>
                	</div>

                </ul>

            </div> <!--End Trending Module-->

    	</section><!--- End Page Right -->

	</div>

</main>

<?php get_footer(); ?>
