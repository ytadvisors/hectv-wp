<?php
/*
Template Name: Home
*/

?>

<?php require_once('_/inc/preferred-recent-episode-functions.php'); ?>
<?php get_header(); ?>

	<!-- main#home -->
	<main class="page clearfix" id="home">
        

		<?php if( get_option( "interactive_status" ) == 1 || $_GET['live'] == true ){ ?>

			<?php hectv_interactive_takeover(); ?>

		<?php }else{ ?>

			<?php hectv_marquee_html(); ?>

        <?php } ?>

    	<div class="page-inner clearfix">
            
            <section class="page-left">
                
                <?php 
                    
                    $recent_base_args = array( 
                        'posts_per_page' => 7, 
                        'post_type' => 'lb_playlist', 
                        'orderby' => 'date', 
                        'order' => 'DESC', 
                        'post_status' => 'publish',
                    ); 
                
                ?>
                
				<?php $recents_items = lb_get_preferred_episodes_first( $recent_base_args );  ?>

        		<div class="content-row clearfix">

                	<?php hectv_create_media_slat( "staff", false ); ?>

                	<?php hectv_create_media_slat( "recent-episode", $recents_items[0] ); ?>

                </div> <!-- End Content Row -->

        		<div class="content-row clearfix">

            	    <?php hectv_create_media_slat( "recent-episode", $recents_items[1] ); ?>

                	<?php hectv_create_media_slat( "recent-episode", $recents_items[2] ); ?>

            	</div> <!-- End Content Row -->

        		<div class="content-row clearfix">

					<?php hectv_create_tv_schedule(3); ?>

					<?php hectv_recent_blog(4); ?>

            	</div> <!-- End Content Row -->

        		<div class="content-row clearfix">

            	    <?php hectv_create_media_slat( "recent-episode", $recents_items[3] ); ?>

                	<?php hectv_create_media_slat( "recent-episode", $recents_items[4] ); ?>

            	</div> <!-- End Content Row -->

				<?php hectv_create_trending( 3, array( "trending-mobile" ) ); ?>
                <!-- End Trending Mobile Module -->

        	</section> <!--- End Page Left -->

        	<section class="page-right">

				<?php hectv_create_trending(4); ?>

                <?php //hectv_create_media_slat( "recent", false, 7 ); ?>
                
                <?php hectv_create_theater_module(); ?>
                
                <?php hectv_right_flex(); ?>

				<div class="module dark clearfix" id="magazine">

					<?php $args = array(

								"posts_per_page" => 1,
								"post_type" => "magazine",
								"order_by" => "post_date"

								); ?>

					<?php $recentIssues = get_posts( $args ); ?>

                	<div class="inner clearfix">

                		<div class="media-type">

                    		<span class="show"><a href="<?php echo site_url('magazine'); ?>">HEC-TV Magazine Archive</a></span>

						</div>

						<div class="content-row clearfix">

						<?php if (have_posts()): while (have_posts()) : the_post(); ?>

							<?php foreach( $recentIssues as $recentIssue ){ ?>

								<div class="left">
									<a href="<?php echo get_permalink($recentIssue->ID);?>">

										<?php $image = get_field('cover_image', $recentIssue->ID); ?>

										<?php if( $image ) { ?>

											<?php echo wp_get_attachment_image( $image, "full" ); ?>

										<?php } ?>

									</a>
								</div>

			                    <div class="right">
	                    			<h3><a href="<?php echo get_permalink($recentIssue->ID);?>"><?php echo get_the_date( "F Y", $recentIssue->ID ); ?></a></h3>
									<p><?php echo $recentIssue->post_content; ?></p>
			                    </div>

							<?php } ?>
							
						<?php endwhile; ?>
						<?php endif; ?>

						</div>

                    </div>

                </div> <!-- End Module -->

            	<?php hectv_newsletter_signup(); ?>

        	</section> <!--- End Page Right -->

			<div class="module-twitter clearfix">
			    <div class="inner clearfix">

					<?php $tweets = getTweets($limit = 1); ?>
					<?php $tweetText = preg_replace('/\b([a-zA-Z]+:\/\/[\w_.\-]+\.[a-zA-Z]{2,6}[\/\w\-~.?=&%#+$*!]*)\b/i',"<a href=\"$1\" class=\"twitter-link\">$1</a>", $tweets[0]['text']); ?>
					<?php $tweetText = preg_replace('/\b(?<!:\/\/)(www\.[\w_.\-]+\.[a-zA-Z]{2,6}[\/\w\-~.?=&%#+$*!]*)\b/i',"<a href=\"http://$1\" class=\"twitter-link\">$1</a>", $tweetText); ?>
					<?php $tweetText = preg_replace("/\b([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})\b/i","<a href=\"mailto://$1\" class=\"twitter-link\">$1</a>", $tweetText); ?>
					<?php $tweetText = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)#{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', "$1<a href=\"http://twitter.com/#search?q=$2\" class=\"twitter-link\">#$2</a>$3 ", $tweetText); ?>
					<?php $tweetText = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)@{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', "$1<a href=\"http://twitter.com/$2\" class=\"twitter-user\">@$2</a>$3 ", $tweetText); ?>
					<div class="left">
						<a target="_blank" href="https://www.twitter.com/hec_tv/">
							<span class="twitter-handle">@hec_tv</span>
							<span class="date"><?php echo date( "F j", strtotime( $tweets[0]['created_at'] ) ); ?></span>
						</a>
					</div>
					<div class="right">
						<span class="tweet"><?php echo $tweetText; ?></span>
					</div>

			    </div>

			 </div> <!-- End Twitter Module-->


    	</div> <!-- End Inner Div -->

    </main>
<!-- /main#home -->

<?php get_footer(); ?>