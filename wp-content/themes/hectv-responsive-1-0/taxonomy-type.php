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
			
			<div id="archives" class="module">
				<div class="inner">
					<h2>Archives</h2>
					<?php $magazine_years = get_terms( array( "type" ), array( "parent" => 10583, "hide_empty" => false ) ); ?>
					<?php $exclude         = array( 10583 ); ?>
					<h3>Magazine</h3>
					<ul>
						<?php foreach( $magazine_years as $magazine_year ){ ?>
						<?php $exclude[] = $magazine_year->term_id; ?>
						<li><a href="<?php echo get_term_link( $magazine_year ); ?>"><?php echo $magazine_year->name; ?></a></li>
						<?php } ?>
					</ul>
					<h3>Special Issues</h3>
					<?php $other_guides   = get_terms( array( "type" ), array( "hide_empty" => false, "exclude" => $exclude ) ); ?>
					<ul>
						<?php foreach( $other_guides as $other_guide ){ ?>
						<li><a href="<?php echo get_term_link( $other_guide ); ?>"><?php echo $other_guide->name; ?></a></li>
						<?php } ?>
					</ul>
				</div>
			</div>
			
			<?php hectv_create_trending( 3 ); ?>
		</section>

	</div>

</main>

<?php get_footer(); ?>