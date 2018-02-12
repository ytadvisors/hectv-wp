<?php get_header(); ?>

 <main class="page clearfix" id="blog">

	<div class="page-inner clearfix">

    	<section class="page-left">

    		<div class="module-wide">

    			<div class="inner">

        			<?php if (is_category()) { ?>
						<h2>Archive for the &#8216;<?php single_cat_title(); ?>&#8217; Category</h2>

					<?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
						<h2>Posts Tagged &#8216;<?php single_tag_title(); ?>&#8217;</h2>

					<?php /* If this is a daily archive */ } elseif (is_day()) { ?>
						<h2>Archive for <?php the_time('F jS, Y'); ?></h2>

					<?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
						<h2>Archive for <?php the_time('F, Y'); ?></h2>

					<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
						<h2 class="pagetitle">Archive for <?php the_time('Y'); ?></h2>

					<?php /* If this is an author archive */ } elseif (is_author()) { ?>
						<h2 class="pagetitle">Author Archive</h2>

					<?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
						<h2 class="pagetitle">Blog Archives</h2>

					<?php } ?>

        			<div id="blog-posts">

						<?php if (have_posts()): while (have_posts()) : the_post(); ?>

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

									<a href="<?php the_permalink();?>#section-comments" class="comment"><fb:comments-count href=<?php the_permalink();?>></fb:comments-count> Comments</a>
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



						<?php endwhile; endif; ?>

        			</div> <!-- End Blog Posts -->

        			<div class="btn-wrap">
        				<button class="btn">View More</button>
        			</div>

    			</div> <!-- End Inner -->

    		</div> <!-- End Blog Module -->

    		<?php hectv_create_trending(3, array( "trending-mobile" ) ); ?>

    	</section>

    	<section class="page-right">

    		<?php hectv_create_trending(3); ?>

    		<?php hectv_create_partners_list(7); ?>

			<div id="archives" class="module">

				<?php $args = array( 'type' => 'monthly',
									 'limit' => '22',
									 'order' => 'DESC'
									); ?>

				<div class="inner">

					<h2>Archives</h2>

					<ul>
						<?php wp_get_archives( $args ); ?>
					</ul>


					<div class="btn-wrap">
						<button class="btn">View All Posts</button>
					</div>

				</div>

			 </div> <!--End Archives Module-->

    	</section> <!--End Page Right-->

	</div>

</main>

<style type="text/css">

	li#menu-item-16913 a {

		color: #fff;
		font-weight: 700;

	}

</style>


<?php get_footer(); ?>