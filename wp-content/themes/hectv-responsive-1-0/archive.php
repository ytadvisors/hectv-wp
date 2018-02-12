<?php get_header(); ?>

 <main class="page clearfix" id="blog">

	<div class="page-inner clearfix">

    	<section class="page-left">


			<?php if (have_posts()): while (have_posts()) : the_post(); ?>

			<?php if( $post->post_type == "post" ){ ?>

				<?php $blogPosts[] = hectv_blog_post_html($post); ?>

			<?php }else if( $post->post_type == "lb_playlist" ){ ?>

				<?php $videoPosts[] = hectv_create_media_slat_by_post($post); ?>

			<?php } ?>

			<?php endwhile; ?>

			<?php endif; ?>

			<?php if( is_array( $videoPosts ) ){ ?>

				<?php foreach( $videoPosts as $index => $videoPost ){ ?>

					<?php if ( $index %2 == 0 ) { ?>

						<?php echo ( $index > 0 ) ? '</div>':''; ?>
						<?php echo '<div class="content-row">'; ?>

					<?php } ?>

					<?php echo $videoPost; ?>

				<?php } ?>

			<?php echo '<div>'; // close last content-row ?>

			<?php } ?>

			<?php if( is_array( $blogPosts ) ){ ?>

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

							<?php foreach( $blogPosts as $blogPost ){ ?>

							<?php echo $blogPost; ?>

							<?php } ?>
						</div>

	    			</div> <!-- End Inner -->

	    		</div> <!-- End Blog Module -->

    		<?php } ?>

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