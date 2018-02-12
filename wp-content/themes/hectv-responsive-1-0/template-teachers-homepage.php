<?php
/*
Template Name: Teachers Landing Page
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="teachers">
	<div class="page-inner clearfix">
    	<section class="page-left">
    		<div class="page-description module-wide">
    			<div class="inner">
    				<h2>Teachers</h2>
    				<?php if (have_posts()): while (have_posts()) : the_post(); ?>
    				<p><?php the_content(); ?></p>
    			</div>
    		</div> <!--End Teachers Module -->
    		<?php endwhile ?>
    		<?php endif ?>
    		<div class="curriculum-search module-wide">
    			<div class="inner">
            		<h3>Find Curriculum</h3>
                    
                    <span>Search by keyword:</span>
                    
                    <div class="keyword-wrap clearfix">
                    
                        <input type="text" id="keyword-query" class="curriculum-keyword" placeholder="Enter the keywords or phrases separated by commas" />
                        <input type="button" value="Search" class="btn" id="js-search-by-keyword">
                        
                    </div>
        			
                    <span><!-- <i class="fa fa-search" style="margin-right:20px;"></i> -->Refine by subject and grade:</span>
            		
                    <div class="search-wrap clearfix">

						<?php $education_topics = get_terms( array( "topic" ) ); ?>
						<?php $subject_topics   = get_terms( array( "topic" ), array( "parent" => 10665, "hide_empty" => false ) ); ?>
						<?php $grade_levels     = get_terms( array( "topic" ), array( "parent" => 10664, "hide_empty" => false ) ); ?>
		    			<?php $feature_types    = get_terms( array( "topic" ), array( "parent" => 10666, "hide_empty" => false ) ); ?>

            			<ul id="subject-filters" class="no-select">
							<?php foreach( $subject_topics as $subject_topic ){ ?>
            				<li>
            					<input type="checkbox" id="<?php echo $subject_topic->slug; ?>" value="<?php echo $subject_topic->slug; ?>" class="curriculum-filter">
								<label for="<?php echo $subject_topic->slug; ?>">
									<div class="button" title="<?php echo $subject_topic->slug; ?>">
										<span class="filter-name"><?php echo $subject_topic->name; ?></span>
									</div>
								</label>
            				</li>
            				<?php } ?>
            			</ul>

            			<ul id="grade-filters" class="no-select">
	            			<?php foreach( $grade_levels as $grade_level ){ ?>
            				<li>
            					<input type="checkbox" id="<?php echo $grade_level->slug; ?>" value="<?php echo $grade_level->slug; ?>" class="curriculum-filter">
								<label for="<?php echo $grade_level->slug; ?>">
									<div class="button" title="<?php echo $grade_level->slug; ?>">
										<span class="filter-name"><?php echo $grade_level->name; ?></span>
									</div>
								</label>
            				</li>
            				<?php } ?>
            			</ul>

            			<ul id="type-filters" class="no-select">
							<?php foreach( $feature_types as $feature_type ){ ?>
            				<li>
            					<input type="checkbox" id="<?php echo $feature_type->slug; ?>" value="<?php echo $feature_type->slug; ?>" class="curriculum-filter">
								<label for="<?php echo $feature_type->slug; ?>">
									<div class="button" title="<?php echo $feature_type->slug; ?>">
										<span class="filter-name"><?php echo $feature_type->name; ?></span>
									</div>
								</label>
            				</li>
            				<?php } ?>
            			</ul>

    				</div> <!-- End Search Wrap -->


	    			<div id="search-wrap-mobile">

						<?php $education_topics = get_terms( array( "topic" ), array( "hide_empty" => false ) ); ?>
						<?php $subject_topics   = get_terms( array( "topic" ), array( "parent" => 10665, "hide_empty" => false ) ); ?>
						<?php $grade_levels     = get_terms( array( "topic" ), array( "parent" => 10664, "hide_empty" => false ) ); ?>
		    			<?php $feature_types    = get_terms( array( "topic" ), array( "parent" => 10666, "hide_empty" => false ) ); ?>

						<div id="subject-mobile" class="clearfix group">
							<span class="title">Subject <i class="fa fa-caret-down"></i></span>

							<ul class="filters" id="subject-filters-mobile">

								<?php foreach( $subject_topics as $subject_topic ){ ?>
	            				<li>
	            					<div class="check-wrap">
		            					<input type="checkbox" id="<?php echo $subject_topic->slug; ?>" value="<?php echo $subject_topic->slug; ?>" class="curriculum-filter">
										<label for="communication-arts">
											<div class="button" title="Communication-Arts" rel="<?php echo $subject_topic->slug; ?>">
												<span class="filter-name"><?php echo $subject_topic->name; ?></span>
											</div>
										</label>
	            					</div>
	            				</li>
	            				<?php } ?>

	            			</ul>

						</div>

						<div id="grade-mobile" class="clearfix group">
							<span class="title">Grade <i class="fa fa-caret-down"></i></span>
	            			<ul class="filters" id="grade-filters">
		            			
								<?php foreach( $grade_levels as $grade_level ){ ?>
	            				<li>
	            					<div class="check-wrap">
		            					<input type="checkbox" id="<?php echo $grade_level->slug; ?>" value="<?php echo $grade_level->slug; ?>" class="curriculum-filter">
										<label for="1st-grade">
											<div class="button" title="<?php echo $grade_level->slug; ?>">
												<span class="filter-name"><?php echo $grade_level->name; ?></span>
											</div>
										</label>
	            					</div>
	            				</li>
	            				<?php } ?>

	            			</ul>
						</div>

						<div id="type-mobile" class="clearfix group">
							<span class="title">Type <i class="fa fa-caret-down"></i></span>
	            			<ul class="filters" id="media-filters">

								<?php foreach( $feature_types as $feature_type ){ ?>

	            				<li>
	            					<div class="check-wrap">
		            					<input type="checkbox" id="<?php echo $feature_type->slug; ?>" value="<?php echo $feature_type->slug; ?>" class="curriculum-filter">
										<label for="<?php echo $feature_type->slug; ?>">
											<div class="button" title="<?php echo $feature_type->slug; ?>">
												<span class="filter-name"><?php echo $feature_type->name; ?></span>
											</div>
										</label>
	            					</div>
	            				</li>
	            				<?php } ?>

	            			</ul>
						</div>

						<div id="hectv-live-mobile" class="clearfix group">
							<span class="title">HEC-TV Live <i class="fa fa-caret-down"></i></span>
						</div>
	    			</div>
    			</div>
        	</div> <!--End Curriculum Module -->

    		<div id="curriculum-results" class="curriculum-content module-wide">
				
 				<?php //$curriculumPages = new WP_Query( array( 'orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => -1, 'post_type' => 'page', 'meta_query' => array( array( 'key' => '_wp_page_template', 'value' => 'template-teachers-curriculum.php' ) ) ) ); ?>
 				
 				<?php //$education_videos = get_posts( array( 'post_type' => 'lb_playlist', 'meta_key' => '' ) ); ?>
				<?php 
				$args = array(
					'post_type' => 'lb_playlist',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'education_page_text',
							'value' => '',
							'compare' => '!=',
						)
					)
				);
				
				$education_videos = new WP_Query($args);
				?>
				
    			<div class="inner">
    				<h2>Curriculum Videos</h2>
    				<span class="results">(<?php echo count( $education_videos->posts ); ?> Results Found)</span>
    				<div class="curriculum-content-posts">
						<?php foreach( $education_videos->posts as $education_video ){ ?>

						<?php $education_video_data = get_post_custom( $education_video->ID ); ?>
						
						<?php $taxonomies     = get_the_terms( $education_video->ID, "topic" ); ?>
						<?php $curriculum_tax = array(); ?>
						<?php if( is_array( $taxonomies ) ){ ?>
							<?php foreach( $taxonomies as $taxonomy ){ ?>
								<?php $curriculum_tax[] = $taxonomy->slug; ?>
							<?php } ?>
						<?php } ?>

    					<div class="content-post clearfix <?php echo implode(" ", $curriculum_tax); ?>">
    						<div class="left">
	    						<a href="<?php echo get_permalink( $education_video->ID ); ?>">
	    							
	    							<?php if( $education_video_data['video_image'][0] ){ ?>
	    							
		    							<?php $thumb = wp_get_attachment_image_src( $education_video_data['video_image'][0], 'media-medium' ); ?>
	    							
	    								<img src="<?php echo $thumb[0]; ?>">
	    							
	    							<?php } ?>
	    							
	    						</a>
    						</div>
    						<div class="right">
    							<h3><a href="<?php echo get_permalink( $education_video->ID ); ?>"><?php echo get_the_title($education_video->post_parent); ?> <?php echo $education_video->post_title; ?></a></h3>
    							<p><?php echo hectv_get_excerpt_by_id( $education_video->ID ); ?> <a class="see-more" style="margin-top:15px;display:inline-block;" href="#">Watch Now</a></p>
    						</div>
    					</div> <!-- End Post -->
    					<?php } ?>
    				</div>
    			</div>
    		</div>
    	</section> <!-- End Page Left-->

    	<section class="page-right">
	    	
	    	<?php //hectv_create_series_episode_list( 5, 17893, "HEC-TV Live" ); ?>
            <?php hectv_create_featured_live( array() ); ?>
			<div id="request" class="dark module">
				<div class="inner">
					<h2>Have A Request?</h2>
					<p>Send a note to our staff educators to receive additional information about our programs; to secure assistance in accessing resources and curriculum; and to involve your students in Live! interactive experiences.</p>
					<div class="btn-wrap">
						<a class="btn" href="<?php echo site_url('/contact/#request');?>">Submit a Request</a>
					</div>
				</div>
			</div>
    	</section>
	</div>
</main>


<?php get_footer(); ?>
