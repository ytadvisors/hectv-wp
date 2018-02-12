<?php
/*
Template Name: Video Detail
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="video-detail">

	<div class="video-active-mobile">
	</div>

    <div class="inner">

		<section class="video-carousel clearfix" style="display: none;">

	    	<div class="video-active">
	    	</div>

	    	<div class="carousel-wrap clearfix">

		    	<div class="arrow-left-wrap">
		    		<img class="arrow-left" src="<?php bloginfo('template_directory'); ?>/_/graphics/arrow-left.png">
		    	</div>

				<div class="video-wrap clearfix">

					<div class="video-clip">

		        		<div class="img-wrap">

		        			<div class="play-wrap">
		        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
		        			</div>

		            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
		        		</div>

		        		<div class="video-clip-info">
		            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
							<span class="time">0:30</span>
						</div>

					</div> <!-- End Video Clip-->

					<div><hr></div>

					<div class="video-clip">

		        		<div class="img-wrap">

		        			<div class="play-wrap">
		        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
		        			</div>

		            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
		        		</div>

		        		<div class="video-clip-info">
		            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
							<span class="time">0:30</span>
						</div>

					</div> <!-- End Video Clip-->

					<div><hr></div>

					<div class="video-clip">

		        		<div class="img-wrap">

		        			<div class="play-wrap">
		        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">

		        			</div>
		            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
		        		</div>

		        		<div class="video-clip-info">
		            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
							<span class="time">0:30</span>
						</div>

					</div> <!-- End Video Clip-->

					<div><hr></div>

					<div class="video-clip">

		        		<div class="img-wrap">

		        			<div class="play-wrap">
		        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
		        			</div>

		            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">

		        		</div>

		        		<div class="video-clip-info">
		            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
							<span class="time">0:30</span>
						</div>

					</div> <!-- End Video Clip-->

					<div><hr></div>

					<div class="video-clip">

		        		<div class="img-wrap">

		        			<div class="play-wrap">
		        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
		        			</div>

		            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
		        		</div>

		        		<div class="video-clip-info">
		            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
							<span class="time">0:30</span>
						</div>

					</div> <!-- End Video Clip-->

				</div> <!-- End Video Wrap -->

				<div class="arrow-right-wrap">
		        	<img class="arrow-right" src="<?php bloginfo('template_directory'); ?>/_/graphics/arrow-right.png">
	            </div>

	    	</div> <!-- End Carousel Wrap -->

    	</section>

    </div>

		<div class="series-title">

			<span>Maryville Talks Books</span>

		</div>

		<div class="page-inner clearfix">

        	<section class="page-left">

            	<div id="series-info" class="module">

            		<ul class="tabs clearfix">

						<li><a href="#" class="tab-nav active" rel="section-description">Description</a></li>
						<li><a href="#" class="tab-nav" rel="section-related-links">Related Links</a></li>
						<li><a href="#" class="tab-nav" rel="section-educational-material">Educational Material</a></li>

					</ul>

					<section>

						<div id="section-description" class="tab-section">
							<h3>Getting to Know Jesus of Nazareth</h3>
							<p>Nemo enim ips sit aspernatur aut odit aut fugit</p>
						</div>


						<div id="section-related-links" class="tab-section">
							<h3>Getting to Know Jesus of Nazareth</h3>
							<p>ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit</p>
						</div>


						<div id="section-educational-material" class="tab-section">
							<h3>Getting to Know Jesus of Nazareth</h3>
							<p>Students learn from three veterans of World War II, one who fought in the Italian Campaign and two who survived the Battle of the Bulge. Students learn from three veterans of World War II, one who fought in the Italian Campaign and two who survived the Battle of the Bulge.</p>
						</div>
						


						<div class="tab-section-bottom clearfix">

							<?php $tags = wp_get_post_terms( $post->ID, 'topic' ); ?>
							<?php if( count( $tags ) > 0 ){ ?>
							<div class="tags">

								<span>Tags</span>
								<span class="divider">|</span>

								<span class="tags">
									<?php foreach( $tags as $index => $tag ){ ?>
 										<?php $comma = ( $index == ( count( $tags ) - 1 ) ) ? "":","; ?>
										<a class="tag-link" href="<?php echo get_term_link($tag); ?>"><?php echo $tag->name; ?><?php echo $comma; ?></a>
									<?php } ?>
								</span>

							</div>
							<?php } ?>

							<div class="share">

								<span>Share</span>
								<span>|</span>
								<span class="social-icons">
									<i class="fa fa-facebook"></i>
									<i class="fa fa-twitter"></i>
									<i class="fa fa-google-plus"></i>
								</span>

								<a class="comment">Comment</a>
								<i class="fa fa-caret-right"></i>

							</div>

						</div>

					</section>

					</div>

				<section class="video-carousel-mobile clearfix">

		    	<div class="carousel-wrap clearfix">

					<div class="video-wrap clearfix">

						<div class="video-clip">

			        		<div class="img-wrap">

			        			<div class="play-wrap">
			        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
			        			</div>

			            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
			        		</div>

			        		<div class="video-clip-info">
			            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
								<span class="time">0:30</span>
							</div>

						</div> <!-- End Video Clip-->

						<div><hr></div>

						<div class="video-clip">

			        		<div class="img-wrap">

			        			<div class="play-wrap">
			        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
			        			</div>

			            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
			        		</div>

			        		<div class="video-clip-info">
			            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
								<span class="time">0:30</span>
							</div>

						</div> <!-- End Video Clip-->

						<div><hr></div>

						<div class="video-clip">

			        		<div class="img-wrap">

			        			<div class="play-wrap">
			        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">

			        			</div>
			            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
			        		</div>

			        		<div class="video-clip-info">
			            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
								<span class="time">0:30</span>
							</div>

						</div> <!-- End Video Clip-->

						<div><hr></div>

						<div class="video-clip">

			        		<div class="img-wrap">

			        			<div class="play-wrap">
			        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
			        			</div>

			            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">

			        		</div>

			        		<div class="video-clip-info">
			            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
								<span class="time">0:30</span>
							</div>

						</div> <!-- End Video Clip-->

						<div><hr></div>

						<div class="video-clip">

			        		<div class="img-wrap">

			        			<div class="play-wrap">
			        				<img class="play" src="<?php bloginfo('template_directory'); ?>/_/graphics/play-button.png">
			        			</div>

			            		<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
			        		</div>

			        		<div class="video-clip-info">
			            		<h3 class="show-title">Predicting Turbulence: Future of Aircraft & Engine Designs</h3>
								<span class="time">0:30</span>
							</div>

						</div> <!-- End Video Clip-->

					</div> <!-- End Video Wrap -->

		    	</div> <!-- End Carousel Wrap -->

			</section> <!-- End Carousel -->


					<div class="content-row">

			            <div class="module clearfix">

				         	<div class="inner">

								<div class="media-type">

					    			<span class="recent">Related Clip</span>
					    			<span class="divider">|</span>
					    			<span class="show">Two On The Aisle</span>

								</div>

					    		<h3>REVIEWS OF STAIRS TO THE ROOF, CHANCERS, ET AL.</h3>

					    		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

								</div>

							<div class="img-wrap flex">
								<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
							</div>

							</div>

			            <div class="module clearfix">

				         	<div class="inner">

								<div class="media-type">

					    			<span class="recent">Related Episode</span>
					    			<span class="divider">|</span>
					    			<span class="show">Two On The Aisle</span>

								</div>

					    		<h3>REVIEWS OF STAIRS TO THE ROOF, CHANCERS, ET AL.</h3>

					    		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

								</div>

							<div class="img-wrap">
								<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
							</div>

						</div>

					</div>

					<div class="content-row">

			            <div class="module clearfix">

				         	<div class="inner">

								<div class="media-type">

					    			<span class="recent">Related Clip</span>
					    			<span class="divider">|</span>
					    			<span class="show">Two On The Aisle</span>

								</div>

					    		<h3>REVIEWS OF STAIRS TO THE ROOF, CHANCERS, ET AL.</h3>

					    		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

								</div>

							<div class="img-wrap flex">
								<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
							</div>

							</div>

			            <div class="module clearfix">

				         	<div class="inner">

								<div class="media-type">

					    			<span class="recent">Related Episode</span>
					    			<span class="divider">|</span>
					    			<span class="show">Two On The Aisle</span>

								</div>

					    		<h3>REVIEWS OF STAIRS TO THE ROOF, CHANCERS, ET AL.</h3>

					    		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

								</div>

							<div class="img-wrap">
								<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
							</div>

						</div> <!-- End Media Modules -->

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



					</div>

	            </section>

        	<section class="page-right">

			<?php hectv_create_trending(3); ?>

        	  <div class="module clearfix">

				<div class="inner">

					<div class="media-type">

		    			<span class="recent">Related Clip</span>
		    			<span class="divider">|</span>
		    			<span class="show">Two On The Aisle</span>

					</div>

		    		<h3>REVIEWS OF STAIRS TO THE ROOF, CHANCERS, ET AL.</h3>

		    		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

				</div>

				<div class="img-wrap flex">
					<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-video-still-temp4.jpg">
				</div>

		</div>

        	</section>

        	</div>

	</div>

</main>

<?php get_footer(); ?>
