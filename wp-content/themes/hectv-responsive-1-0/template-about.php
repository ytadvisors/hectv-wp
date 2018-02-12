<?php
/*
Template Name: About
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="about">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

	 <div class="page-inner clearfix">
		 
	    <section class="page-left">
	        <div class="video-wrap">
		        <?php if( get_field('video_id') ){ ?>
	            <div class="about-video">
					<iframe width="100%" height="500" class="resize-ratio" style="overflow:hidden;" src="<?php echo esc_url( home_url() ); ?>/embed/<?php the_field('video_id'); ?>/"></iframe>
	            </div>
	            <?php } ?>
	        </div>

	        <div class="content-row clearfix">
	            <div class="page-description module">
					<div class="inner">
						<h2>About</h2>
						<p><?php the_content(); ?></p>
					</div>
				</div>

				<div class="contact-module module dark clearfix">

					<div class="inner">
						<address>
							<span class="address"><?php the_field('address'); ?></span>
							<span class="phone">Phone <?php the_field('phone_number'); ?></span>
							<span class="fax">Fax <?php the_field('fax_number'); ?></span>
						</address>

						<div class="tv-providers">
							<?php if( have_rows('tv_providers') ): ?>
							<?php while ( have_rows('tv_providers') ) : the_row(); ?>
							<div class="provider-info">
								<span class="provider"><?php the_sub_field('provider'); ?></span>
								<span class="divider">|</span>
								<span class="channel"><?php the_sub_field('channel'); ?></span>
							</div>
							<?php endwhile; ?>
							<?php endif; ?>
						</div>

						<div class="btn-wrap">
							<a href="<?php echo site_url('contact'); ?>" class="btn">Contact Us</a>
						</div>
					</div>
				</div>
	        </div>

	        <div id="team" class="module-wide our-team clearfix">

	            <div class="inner">

	            	<h2>Our Team</h2>

					<div class="team-wrap clearfix">
						<?php if( have_rows('team') ): ?>
						<?php while ( have_rows('team') ) : the_row(); ?>


						<div class="team-member">

								<div class="team-photo">

								<?php $image = get_sub_field('photo'); ?>
								<?php if( !empty($image) ): ?>

								<img src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>" />

								<?php endif; ?>

							</div>

							<span class="name"><?php the_sub_field('name'); ?></span>

							<span class="team-position"><?php the_sub_field('position'); ?></span>

							<span class="team-email"><a href="mailto:<?php the_sub_field('email'); ?>"><?php the_sub_field('email'); ?></a></span>

						</div>

						<?php endwhile; ?>
						<?php endif; ?>
					</div>

<!--
					<div id="board-of-directors">
						<h4>HEC-TV Board of Directors</h4>
						<span class="denotation">* Chairperson</span>
						<span class="denotation">** Executive Committee</span>
						<ul>
							<?php if( have_rows('board_of_directors') ): ?>
							<?php while ( have_rows('board_of_directors') ) : the_row(); ?>
							<li>
								<span class="school"><?php the_sub_field('school'); ?></span>
								<span class="name"><?php the_sub_field('name'); ?></span>
								<span class="position"><?php the_sub_field('position'); ?></span>
							</li>
							<?php endwhile; ?>
							<?php endif; ?>
						</ul>
					</div>
					<div class="btn-wrap">
						<button id="show-board" class="btn">See More</button>
					</div>
-->
	            </div>

	        </div> <!-- End Our Team Module -->

	        <div id="partners" class="our-partners-mobile module clearfix">

		    	<div class="inner">
		    		<h2>Our Partners</h2>
		    		<div class="partner-logo-wrap clearfix">
					<?php if( have_rows('partner_logos') ): ?>
					<?php while ( have_rows('partner_logos') ) : the_row(); ?>
						<?php $image = get_sub_field('partner_logo'); ?>
						<?php $link  = get_sub_field('partner_link'); ?>
						<?php if( !empty( $image ) ): ?>

						<a href="<?php echo $link; ?>" target="_blank">
							<div class="img-wrap" style="background-image: url(<?php echo $image['url']; ?>)"></div>
						</a>

						<?php endif; ?>
					<?php endwhile; ?>
					<?php endif; ?>
		    		</div>

					<div id="partners-list-mobile" class="clearfix">
			    		<h4>Public School District Partners</h4>
						<ul>
							<?php if( have_rows('public_school_partners') ): ?>
							<?php while ( have_rows('public_school_partners') ) : the_row(); ?>

							<li><?php the_sub_field('partner'); ?></li>

							<?php endwhile; ?>
							<?php endif; ?>
						</ul>
			    		<h4>Higher Education Partners</h4>
			    		<ul>
				    		<?php if( have_rows('higher_education_partners') ): ?>
							<?php while ( have_rows('higher_education_partners') ) : the_row(); ?>

							<li><?php the_sub_field('partner'); ?></li>

							<?php endwhile; ?>
							<?php endif; ?>

			    		</ul>
					</div> <!-- End Partners List -->
				</div>
			</div> <!-- End Our Partners Mobile Module -->
		</section>

	    <!---end page left ---->

	    <section class="page-right">
	    	<?php hectv_create_trending( 3 ); ?>
			<?php hectv_create_tv_schedule(3); ?>
	    </section>

	    <div id="our-partners" class="module clearfix">
	    	<div class="inner">
	    		<h2>Our Partners</h2>
	    		<div class="partner-logo-wrap clearfix">

					<?php if( have_rows('partner_logos') ): ?>
					<?php while ( have_rows('partner_logos') ) : the_row(); ?>
						<?php $image = get_sub_field('partner_logo'); ?>
						<?php $link  = get_sub_field('partner_link'); ?>
						<?php if( !empty( $image ) ): ?>

						<a href="<?php echo $link; ?>" target="_blank">
							<div class="img-wrap" style="background-image: url(<?php echo $image['url']; ?>)"></div>
						</a>

						<?php endif; ?>
					<?php endwhile; ?>
					<?php endif; ?>

	    		</div>

	    		<div id="partners-list" class="clearfix">

		    		<div class="left">
			    		<h4>Public School District Partners</h4>
						<ul>
							<?php if( have_rows('public_school_partners') ): ?>
							<?php while ( have_rows('public_school_partners') ) : the_row(); ?>

							<li><?php the_sub_field('partner'); ?></li>

							<?php endwhile; ?>
							<?php endif; ?>
						</ul>
		    		</div>

		    		<div class="right">
			    		<h4>Higher Education Partners</h4>

			    		<ul>
				    		<?php if( have_rows('higher_education_partners') ): ?>
							<?php while ( have_rows('higher_education_partners') ) : the_row(); ?>

							<li><?php the_sub_field('partner'); ?></li>

							<?php endwhile; ?>
							<?php endif; ?>

			    		</ul>
		    		</div>
	    		</div> <!-- End Partners List -->

			</div> <!-- End Inner -->

	    </div> <!-- End Our Partners Module -->

	 </div> <!-- End Page Inner -->

	 <?php endwhile; ?>
	 <?php endif; ?>
</main>

<?php get_footer(); ?>
