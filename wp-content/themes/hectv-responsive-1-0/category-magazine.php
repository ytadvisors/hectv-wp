<?php get_header(); ?>

 <main class="page clearfix" id="magazine-landing">

	<div class="page-inner clearfix">

    	<section class="page-left">

			<div class="module-wide">

				<div class="inner">
					<h2><?php single_cat_title(); ?></h2>

<!-- 					<h3>Current Issue</h3> -->
					<?php if (have_posts()): while (have_posts()) : the_post(); ?>

					<div class="magazine current clearfix">

						<div class="img-wrap">
						<a href="<?php echo get_permalink();?>">

							<?php $image = get_field('cover_image'); ?>
							<?php $size = 'full'; ?>

							<?php if( $image ) { ?>

								<?php echo wp_get_attachment_image( $image, $size ); ?>

							<?php } ?>

						</a>
						</div>

						<div class="right">
							<span><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a></span>
							<p><?php echo get_the_excerpt(); ?></p>
							<a class="read-issue" href="<?php echo get_permalink(); ?>">Read Issue</a>
						</div>
					</div>

					<?php endwhile; ?>
					<?php endif; ?>

				</div>
			</div>
    	</section> <!--End Page Left-->

		<section class="page-right">
			<?php hectv_create_trending( 3 ); ?>
		</section>

	</div>

</main>

<?php get_footer(); ?>