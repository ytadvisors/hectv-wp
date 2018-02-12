<?php
/*
Template Name: Teachers Curriculum
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="teachers-curriculum">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

	<?php $educationData = get_post_custom(); ?>

	<div class="page-inner">

    	<section class="page-left">

    		<div class="page-description module-wide">

    			<div class="inner">

	    			<div class="content">

	        			<h2><?php the_title(); ?> <?php //echo get_the_title( $post->post_parent ); ?></h2>
	        			<?php the_content(); ?>
	        			
	        			<?php 
                        
                            //Since the have_rows cannot be trusted, we need to cycle through twice
                            $episode_count = 0;
                            $episodes = [];
                        
                            if( have_rows('education_content') ){ 
                            
                                while ( have_rows('education_content') ) { 
                                    
                                    the_row();
                                
                                    $episode     = get_sub_field('episode_relationship');
                                   
                                    if( !empty( $episode ) ) {
                                     
                                        $episode_info = array(  "episode" => $episode,
                                                                "copy" => get_sub_field('episode_copy'),
                                                                "data" => get_post_custom( $episode ),
                                                                "files" => $files );
                                    
                                        array_push($episodes, $episode_info);
                                        $episode_count++;
                                        
                                    }
                                    
                                }
                                
                            }
                        ?>
                        
						<?php if( $episode_count > 0 ){ ?>

						<h2 class="episodes">Episodes</h2>

						<ul id="episodes">

						<?php $x = 0; ?>
						<?php
                            
                            foreach( $episodes as $episode_array ) {
                                
                                 $episode = $episode_array["episode"];
							     $episodeData = $episode_array["data"];
							     $files = $episode_array["files"];
                                
                        ?>

							<li class="episode">

								<h3 class="title">
                                    <a target="_blank" href="<?php echo get_permalink( $episode ); ?>">
										<?php echo get_the_title( $episode ); ?>
									</a>
								</h3>

								<div class="content">
								<?php echo $episode_array["copy"]; ?>

								<?php $list = array(); ?>

								<?php if( $files > 0 ){ ?>
									<?php for( $x = 0; $x < $files; $x++ ){ ?>

										<?php $fileTitle = $episodeData['files_' . $x . '_title'][0]; ?>
										<?php $fileID    = $episodeData['files_' . $x . '_file'][0]; ?>

										<?php $list[]    = '<a target="_blank" href="' . wp_get_attachment_url( $fileID ) . '">' . $fileTitle . '</a>'; ?>

									<?php } ?>
								<?php } ?>
								</div>

								<?php if( count( $list ) > 0 ){ ?>
								<div class="files">
									<span>Files: </span><?php echo implode( ",", $list ); ?>.
								</div>
								<?php } ?>

							</li>

							<?php $x++; ?>
							
							<?php } ?>

                        </ul>
                        
                         <?php } ?>
	    			</div>
    			</div>
    		</div>


    		<div id="materials" class="module-wide" style="display: none;">
    			<div class="inner">
        			<h2>Materials</h2>
        			<p>HEC-TV creates curriculum for new productions, in order to more fully connect the station with K-12 students and schools. But, what is curriculum and how is it developed? Most of us think of curriculum as a set of courses offered by an institution such as a school or university.</p>
        			<div class="materials-wrap clearfix">
        				<div class="material-content">

        					<div class="img-wrap">
	     						<span class="curriculum-type">Doc</span>
        						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-materials-temp.jpg">
        					</div>

        					<a href="#">Predicting Turbulance: Morbi pretium scelerisque.</a>

        				</div> <!-- End Material Content -->

        				<div class="material-content">
        					<div class="img-wrap">
        						<span class="curriculum-type">Doc</span>
        						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-materials-temp.jpg">
        					</div>
        					<a href="#">Predicting Turbulance: Morbi pretium scelerisque.</a>
        				</div> <!-- End Material Content -->

        				<div class="material-content">
        					<div class="img-wrap">
        						<span class="curriculum-type">Doc</span>
        						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-materials-temp.jpg">
        					</div>

        					<a href="#">Predicting Turbulance: Morbi pretium scelerisque.</a>
        				</div> <!-- End Material Content -->

        			</div> <!-- End Materials Wrap -->

        			<div class="btn-wrap">
        				<button class="btn">View All</button>
        			</div>

    			</div>

    		</div>

			<div id="episodes" class="module-wide" style="display: none;">

    			<div class="inner">
        			<h2>Episodes/Videos</h2>
        			<div class="episodes-wrap clearfix">
        				<div class="episode-content">
        					<div class="img-wrap">
        						<span class="play"></span>
        						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-materials-temp.jpg">
        					</div>

        					<a href="#">Predicting Turbulance: Morbi pretium scelerisque.</a>

        				</div> <!-- End Episode Content -->

        				<div class="episode-content">
        					<div class="img-wrap">
        						<span class="play"></span>
        						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-materials-temp.jpg">
        					</div>
        					<a href="#">Predicting Turbulance: Morbi pretium scelerisque.</a>
        				</div> <!-- End Episode Content -->

						<div class="episode-content">
        					<div class="img-wrap">
        						<span class="play"></span>
        						<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-materials-temp.jpg">
        					</div>
        					<a href="#">Predicting Turbulance: Morbi pretium scelerisque.</a>
        				</div> <!-- End Episode Content -->

        			</div> <!-- End Episodes Wrap -->

        			<div class="btn-wrap">
        				<button class="btn">View All</button>
        			</div>
    			</div>
    		</div>
    	</section>


    	<section class="page-right">

        	<div id="request-materials" class="module dark">

        		<div class="inner">

            		<h2>Request Materials</h2>
            		<p>Send a note to our staff educators to receive additional information about our programs; to secure assistance in accessing resources and curriculum; and to involve your students in Live! interactive experiences.</p>

            		<div class="btn-wrap">
            			<a class="btn" href="http://hectv.staging.wpengine.com/contact/#request">Submit A Request</a>
					</div>
        		</div>
        	</div>


    	</section>

    	<?php endwhile; ?>

    	<?php endif; ?>



	</div>

</main>

<?php get_footer(); ?>
