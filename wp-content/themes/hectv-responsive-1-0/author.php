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
            					<span class="author">Posted By <?php the_author_posts_link(); ?></span>

            				</div>

            				<div class="left">

            					<div class="img-wrap">
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

										<a href="#" class="fa fa-facebook"></a>
										<a href="#" class="fa fa-twitter"></a>
										<a href="#" class="fa fa-google-plus"></a>

									</span>

									<a href="#" class="comment">5 Comments</a>
									<a href="#" class="fa fa-caret-right"></a>
    							</div>

            				</div>

            				<div class="right">

            					<div class="post-info-mobile">

	            					<span class="date"><?php the_time( 'F j, Y' ); ?></span>
	            					<span class="divider">|</span>
	            					<span class="author">Posted By <?php the_author_posts_link(); ?></span>

								</div>

            					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

    							<?php hectv_excerpt(); ?>

            				</div>

        				</div> <!-- End Blog Post -->

        				<?php endwhile; ?>

						<?php else: ?>
						<!-- article -->
						<article>
							<h2><?php _e( 'Sorry, nothing to display.', 'html5blank' ); ?></h2>
						</article>
						<!-- /article -->

						<?php endif; ?>



        			</div> <!-- End Blog Posts -->

        			<div class="btn-wrap">
        				<button class="btn">View More</button>
        			</div>

    			</div> <!-- End Inner -->

    		</div> <!-- End Blog Module -->

    		<div class="trending-mobile module clearfix">

                <h2>Trending<br/>on Hec-TV</h2>

                <ul class="video-list clearfix">

                    <li>
                        <article class="trending-article clearfix">

                            <div class="left">

                                <div class="img-wrap">

                                	<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">

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

					<li>
                        <article class="trending-article clearfix">

                            <div class="left">

                                <div class="img-wrap">

                                	<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">

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

                    <li>
                        <article class="trending-article clearfix">

                            <div class="left">

                                <div class="img-wrap">

                                	<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">

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

					<li>
                        <article class="trending-article clearfix">

                            <div class="left">

                                <div class="img-wrap">

                                	<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">

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

                        <button class="btn">View All</button>

                    </div>
                </ul>

            </div> <!-- End Trending Mobile Module -->

    	</section>

    	<section class="page-right">

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

                </ul>

                <div class="btn-wrap">
                    <button class="btn">See All</button>
                </div>

            </div>


    		<div id="partners" class="module">

				 <div class="inner">

					 <h2>Partners</h2>
					 <ul>
						 <li>
						 	<a href="#">Logan College of Chiropractic</a>
						 </li>
						 <li>
						 	<a href="#">Maryville University</a>
						 </li>
						 <li>
						 	<a href="#">McKendree University</a>
						 </li>
						 <li>
						 	<a href="#">Southeast Missouri State University</a>
						 </li>
						 <li>
						 	<a href="#">Southern Illinois University, Edwardsville</a>
						 </li>
						 <li>
						 	<a href="#">St. Charles Community College</a>
						 </li>
						 <li>
						 	<a href="#">St. Louis College of Pharmacy</a>
						 </li>
					 </ul>

					 <button class="btn">View All</button>

				 </div>

			</div>

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

		color: #f24404 !important;

	}

</style>

<?php get_footer(); ?>