<?php
/*
Template Name: Series
*/
?>

<?php $offset     = ( is_numeric( $_GET['spage'] ) ) ? $_GET['spage'] : 0 ; ?>
<?php $offset     = $offset * 4; ?>

<?php get_header(); ?>

<?php $recent_base_args = array( 'posts_per_page' => 7, 'post_type' => 'lb_playlist', "orderby" => "date", "order" => "DESC", "offset" => $offset, 'post_status' => 'publish', 'post_parent' => $post->ID ); ?>

<?php $recents_items    = get_posts( $recent_base_args ); ?>
<?php $series_data       = get_post_custom( $post->ID ); ?>

<main id="series" class="clearfix endless-media">

    <div class="series-title">

    	<span><?php the_title(); ?></span>

    </div>

    <div id="marquee" class="clearfix">

	    <?php $marquee = get_posts( array( 'posts_per_page' => 1, 'post_parent' => $post->ID, 'post_type' => 'lb_playlist' ) ); ?>
	    <?php $data    = get_post_custom( $marquee[0]->ID ); ?>
	    <?php $thumb   = wp_get_attachment_image_src( $data['video_image'][0], 'marquee-large' ); ?>

    	<div class="marquee-item" style="background-image:url(<?php echo $thumb[0]; ?>);" data-min="<?php echo $thumb[2]; ?>">

	    	<div class="marquee-wrap">

				<div class="content">
		        	<a href="<?php echo get_permalink( $marquee[0]->ID ); ?>">
			        	<h3><?php echo get_the_title( $marquee[0]->ID ); ?></h3>
					</a>

		        	<?php echo wpautop( $marquee[0]->post_excerpt ); ?>

		        	<a href="<?php echo get_permalink( $marquee[0]->ID ); ?>" class="btn">Watch Now</a>
				</div>

	    	</div>

		</div>

	</div> <!-- End Marquee -->

    <div class="page-inner clearfix">

        <section id="series-media" class="endless-media page-left" data-elm="div.content-row" data-tgt="section#series-media" rel="1">

            <div id="series-info" class="module">

	            <ul class="tabs clearfix">

		            <?php $relatedLinks = unserialize( $series_data['related_links'][0] ); ?>

					<li><a href="#" class="tab-nav active" rel="section-description">Description</a></li>
					<li><a href="#" class="tab-nav <?php echo ( !is_array( $relatedLinks ) ) ? 'disabled':''; ?>" rel="section-related-links">Related Links</a></li>
					<li><a href="#" class="tab-nav <?php echo ( empty( $series_data['education_copy'][0] ) ) ? 'disabled':''; ?>" rel="section-educational-material">Educational Material</a></li>

				</ul>

				<section>

					<?php if (have_posts()): while (have_posts()) : the_post(); ?>
					<div id="section-description" class="tab-section">
						<?php the_content(); ?>
					</div>
					<?php endwhile; ?>
					<?php endif; ?>


					<div id="section-related-links" class="tab-section">
						<ul id="related-links">
						<?php if( is_array( $relatedLinks ) ){ ?>

							<?php foreach( $relatedLinks as $relatedLink ){ ?>

								<li>
									<a target="_blank" href="<?php echo $relatedLink['link']; ?>"><?php echo $relatedLink['name']; ?></a>
								</li>

							<?php } ?>
						<?php } ?>
						</ul>
					</div>



					<div id="section-educational-material" class="tab-section">

						<p><?php echo $series_data['education_copy'][0]; ?></p>
						
					</div>

				</section>

            </div>

            <?php if ( get_field('series_type') !== "local special" ){ ?>
                <div class="content-row clearfix">

                    <?php hectv_create_media_slat( "recent-episode", $recents_items[0] ); ?>

                    <?php hectv_create_media_slat( "recent-episode", $recents_items[1]  ); ?>

                </div> <!-- End Row -->

                <div class="content-row clearfix">

                    <?php hectv_create_media_slat( "recent-episode", $recents_items[2] ); ?>

                    <?php hectv_create_media_slat( "recent-episode", $recents_items[3]  ); ?>

                </div> <!-- End Row -->

                <?php hectv_create_trending(4, array( "trending-mobile" ) ); ?>

                <a class="pager load-more clear" id="load-more" href="#">
                    <span class="ui-large-button">Load More</span>
                </a>
            <?php } ?>

        </section> <!--- End Page Left -->

        <section class="page-right"> <!--- Start Page Right -->

        	<?php if( has_term( 9334, 'education-topic', $post ) ){ ?>
        	
	        <?php hectv_create_featured_live( array() ); ?>
	        
	        <?php } ?>

        	<?php hectv_create_all_episodes( $post->ID ); ?>

        	<?php hectv_create_trending(4); ?>

			<?php hectv_create_media_slat( "recent-episode", $recents_items[4]  ); ?>

        </section> <!-- End Page Right -->

    </div> <!-- End Page Wrap -->

</main>

<?php //$seriesBackground = wp_get_attachment_image_src( $series_data['page_hat'][0], 'full' ); ?>
<?php if( !empty( $seriesBackground ) ){ ?>
<style type="text/css">

body{
	background-image: url(<?php echo $seriesBackground[0]; ?>);
	background-color: #f3f3f3;
	background-position: top center;
	background-repeat: no-repeat;
	background-size: 100% auto;
	background-attachment: fixed;

}

</style>
<?php } ?>

<?php get_footer(); ?>
