<?php
/*
Template Name: Blog Landing
*/
?>

<?php get_header(); ?>

 <main class="page clearfix" id="blog">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

	<div class="page-inner clearfix">

    	<section class="page-left">

    		<div class="module-wide">

    			<div class="inner">

        			<h2>Blog</h2>

        			<div id="blog-posts" class="endless-media page-left" data-elm="div.blog-post" data-tgt="div#blog-posts" rel="1">

						<?php $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1; ?>
	        			<?php $blogPostsQueryParameters = array( 'posts_per_page' => 7,
																  'orderby' => 'post_date',
																  'order' => 'DESC',
																  'post_type' => 'post',
																  'paged' => $paged ); ?>
						<?php $blogPosts = get_posts( $blogPostsQueryParameters ); ?>

						<?php foreach ( $blogPosts as $post ) : setup_postdata( $post ); ?>

        				<div class="blog-post clearfix"> <!--Start Blog Post-->

            				<div class="post-info">

            					<span class="date"><?php the_time( 'F j, Y' ); ?></span>
            					<span class="divider">|</span>
            					<span class="author">Posted By <?php echo get_the_author_meta( "user_nicename", $post->post_author ); ?></span>

            				</div>

            				<div class="left">

            					<div class="img-wrap clearfix">
	            					<a href="<?php the_permalink(); ?>">
									<?php if( has_post_thumbnail( $post->ID ) ){ ?>

										<?php $thumbnail = get_the_post_thumbnail( $post->ID, 'event-thumb' ); ?>

	                                    <?php echo $thumbnail; ?>

                                    <?php }else{ ?>


                                    	<?php if ( preg_match( '/<img[^>]+>/is', $post->post_content, $images ) ) { ?>
                                    	<?php echo preg_replace( '/(width|height)="\d*"\s/', "", $images[0] ); ?>
										<?php }else{ ?>

											<span class="no-image">No Image</span>

										<?php } ?>

                                    <?php } ?>
									</a>
            					</div>

    							<div class="share">

									<span class="social-icons">

										<a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" class="social-share" data-width="606" data-height="305">
											<i class="fa fa-facebook"></i>
										</a>
										<a href="https://twitter.com/share?text=<?php echo urlencode( $post->post_title . " @hec_tv" ); ?>&url=<?php the_permalink(); ?>" class="social-share" data-width="606" data-height="305">
											<i class="fa fa-twitter"></i>
										</a>

									</span>

									<a class="comment" href="#section-comments"><fb:comments-count href="<?php the_permalink(); ?>"></fb:comments-count> Comments</a>
									<a href="#" class="fa fa-caret-right"></a>
    							</div>

            				</div>

            				<div class="right">

            					<div class="post-info-mobile">

	            					<span class="date"><?php the_time( 'F j, Y' ); ?></span>
	            					<span class="divider">|</span>
	            					<span class="author">Posted By <?php echo get_the_author_meta( "user_nicename", $post->post_author ); ?></span>

								</div>

            					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

    							<?php hectv_excerpt(); ?>

            				</div>

        				</div> <!-- End Blog Post -->

                        <?php endforeach; ?>
                        
                        <a class="pager load-more clear" id="load-more" href="<?php echo site_url('blog/page/'. ( $paged + 1 ) ); ?>">
							<span class="ui-large-button">Load More</span>
						</a>

        			</div> <!-- End Blog Posts -->

    			</div> <!-- End Inner -->

    		</div> <!-- End Blog Module -->

    		<?php hectv_create_trending(3, array( "trending-mobile" ) ); ?>

    	</section>

    	<section class="page-right">

    		<?php hectv_create_trending(3); ?>

			<?php hectv_create_archives_list(); ?>

			<?php hectv_create_tag_cloud(); ?>

    	</section> <!--End Page Right-->



	</div>

	<?php endwhile; ?>

	<?php else: ?>
	<!-- article -->
	<article>
		<h2><?php _e( 'Sorry, nothing to display.', 'html5blank' ); ?></h2>
	</article>
	<!-- /article -->

	<?php endif; ?>

</main>

<?php get_footer(); ?>