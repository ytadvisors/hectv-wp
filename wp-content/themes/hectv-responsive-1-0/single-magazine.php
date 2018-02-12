<?php
/*
Template Name: Magazine Single
*/
?>
<?php get_header(); ?>

<main class="page clearfix" id="magazine-detail">

	<div class="page-inner clearfix">

			<div class="module-wide">
				<div class="inner">
			    	<?php if (have_posts()): while (have_posts()) : the_post(); ?>
					<h2><?php echo get_the_title(); ?></h2>
					<h3><?php echo get_the_excerpt(); ?></h3>
					
					<iframe src="<?php the_field('page_turn_link'); ?>"></iframe>
    	    	    <?php endwhile; ?>
					<?php endif; ?>
				</div>
			</div>

	</div>
	
</main>

<?php get_footer(); ?>
