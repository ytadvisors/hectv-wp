<?php
/*
Template Name: Blog Story
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="blog-story">

	<div class="page-inner clearfix">

    	<section class="page-left">

    		<article class="module-wide">

    			<div class="blog-title clearfix">

        			<h3>Slideshow Title Headline Can Go To Two Lines</h3>



    			</div>

    			<span class="post-info">

					<span class="date"></span>
					<span class="divider">|</span>
					<span class="author">Posted By </span>

				</span>

    			<div class="carousel">

    				<div>

	    				<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
							<?php the_post_thumbnail(); // Fullsize image for the single post ?>
						</a>

    				</div>

    			</div>

    			<div class="inner clearfix">

    				<div class="blog-post-top clearfix">

						<div class="tags">

							<span>Tags</span>
							<span class="divider">|</span>
							<span class="tags">
								<a class="tag-link">Education,</a>
								<a class="tag-link">Arts,</a>
								<a class="tag-link">Jazz,</a>
								<a class="tag-link">Religion</a>
								<?php the_category(' '); ?>
							</span>

						</div>

						<div class="share">

							<span>Share</span>
							<span class="divider">|</span>


							<span class="social-icons">
								<i class="fa fa-facebook"></i>
								<i class="fa fa-twitter"></i>
								<i class="fa fa-google-plus"></i>
							</span>

							<a class="comment" href="#">5 Comments</a>
							<i class="fa fa-caret-right"></i>

						</div>

				</div>

    				<h4>Caption Subhead Goes Here</h4>

    				<p>
    					Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse feugiat nisi id nibh feugiat accumsan. Praesent faucibus est quis est accumsan finibus. Quisque volutpat ornare magna vitae scelerisque. Praesent porta mattis magna, quis maximus metus placerat eget. Duis laoreet porta est. Sed porta leo vitae pharetra convallis. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed at magna eu tellus iaculis sagittis vitae ut tortor. Suspendisse et fermentum dolor.
					</p>

					<p>
    					Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse feugiat nisi id nibh feugiat accumsan. Praesent faucibus est quis est accumsan finibus. Quisque volutpat ornare magna vitae scelerisque. Praesent porta mattis magna, quis maximus metus placerat eget. Duis laoreet porta est. Sed porta leo vitae pharetra convallis. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed at magna eu tellus iaculis sagittis vitae ut tortor. Suspendisse et fermentum dolor.
					</p>

					<p>
    					Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse feugiat nisi id nibh feugiat accumsan. Praesent faucibus est quis est accumsan finibus. Quisque volutpat ornare magna vitae scelerisque. Praesent porta mattis magna, quis maximus metus placerat eget. Duis laoreet porta est. Sed porta leo vitae pharetra convallis. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed at magna eu tellus iaculis sagittis vitae ut tortor. Suspendisse et fermentum dolor.
					</p>

					<p>
    					Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse feugiat nisi id nibh feugiat accumsan. Praesent faucibus est quis est accumsan finibus. Quisque volutpat ornare magna vitae scelerisque. Praesent porta mattis magna, quis maximus metus placerat eget. Duis laoreet porta est. Sed porta leo vitae pharetra convallis. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed at magna eu tellus iaculis sagittis vitae ut tortor. Suspendisse et fermentum dolor.
					</p>

					<div class="share blog-post-bottom">

						<span class="social-icons">
							<i class="fa fa-facebook"></i>
							<i class="fa fa-twitter"></i>
							<i class="fa fa-google-plus"></i>
						</span>

						<a class="comment" href="#">5 Comments</a>
						<i class="fa fa-caret-right"></i>

					</div>

    			</div>

    		</article>

    		<div class="next-blog-post module-wide">

    			<div class="inner">

    				<h4>Next Blog Post</h4>

    				<div class="wrap clearfix">

						<div class="left">

    						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-event-post-temp.jpg">

    					</div>

    					<div class="right">

    						<h3>Blog Post Headline Tk Tk Can Go To Two...</h3>
    						<button class="btn">Read Now</button>

    					</div>

    				</div>

    			</div>

    		</article>

    	</section> <!--- End Page Left -->

    	<section class="page-right">

    		<div class="module clearfix">

					<div class="recent-blog-posts">

                		<h2>Recent Blog <br />Posts</h2>


						<article>

                    		<h3>60th Anniversary Celebration of the McDonnell f-101 Voodoo At The Missouri Avaition Historical Society</h3>
                    		<span class="post-info">Posted On: November 12th, 2014 By Christina Chastain</span>

                    		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

                    	</article>

                		<article>

                    		<h3>60th Anniversary Celebration of the McDonnell</h3>
                    		<span class="post-info">Posted On: November 12th, 2014 By Christina Chastain</span>

                    		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

                		</article>

					</div>

            		<div class="flex"></div>

            	</div>

            	<div class="module categories">

            		<div class="inner">

                		<h2>Categories</h2>

                		<ul class="blog-categories">

							<li>
								<a class="category" href="#">St. Louis</span>
								<span class="post-count">(124)</span>
							</li>

							<li>
								<a class="category" href="#">Culture</span>
								<span class="post-count">(91)</span>
							</li>

							<li>
								<a class="category" href="#">Events</span>
								<span class="post-count">(78)</span>
							</li>

							<li>
								<a class="category" href="#">Education</span>
								<span class="post-count">(78)</span>
							</li>

							<li>
								<a class="category" href="#">Education</span>
								<span class="post-count">(78)</span>
							</li>


                		</ul>

                		<button class="btn">View All</button>

                	</div>

            	</div> <!-- End Module -->

            	 <div class="module clearfix" id="trending">

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